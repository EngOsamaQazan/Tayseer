import { prisma } from '@/lib/prisma';
import { logger } from '@/utils/logger';

export interface AuditLogData {
  userId: string;
  tenantId: string;
  action: string;
  entityType: string;
  entityId?: string;
  oldValue?: any;
  newValue?: any;
  metadata?: any;
  ipAddress?: string;
  userAgent?: string;
}

export class AuditService {
  private static instance: AuditService;

  private constructor() {}

  public static getInstance(): AuditService {
    if (!AuditService.instance) {
      AuditService.instance = new AuditService();
    }
    return AuditService.instance;
  }

  async log(data: AuditLogData): Promise<void> {
    try {
      await prisma.auditLog.create({
        data: {
          userId: data.userId,
          tenantId: data.tenantId,
          action: data.action,
          entityType: data.entityType,
          entityId: data.entityId,
          oldValue: data.oldValue ? JSON.stringify(data.oldValue) : null,
          newValue: data.newValue ? JSON.stringify(data.newValue) : null,
          metadata: data.metadata ? JSON.stringify(data.metadata) : null,
          ipAddress: data.ipAddress,
          userAgent: data.userAgent,
          timestamp: new Date()
        }
      });
    } catch (error) {
      logger.error('Failed to create audit log:', error);
    }
  }

  async getAuditLogs(filters: {
    tenantId: string;
    entityType?: string;
    entityId?: string;
    userId?: string;
    action?: string;
    startDate?: Date;
    endDate?: Date;
    limit?: number;
    offset?: number;
  }) {
    const where: any = {
      tenantId: filters.tenantId
    };

    if (filters.entityType) where.entityType = filters.entityType;
    if (filters.entityId) where.entityId = filters.entityId;
    if (filters.userId) where.userId = filters.userId;
    if (filters.action) where.action = filters.action;
    
    if (filters.startDate || filters.endDate) {
      where.timestamp = {};
      if (filters.startDate) where.timestamp.gte = filters.startDate;
      if (filters.endDate) where.timestamp.lte = filters.endDate;
    }

    try {
      const [logs, total] = await Promise.all([
        prisma.auditLog.findMany({
          where,
          take: filters.limit || 50,
          skip: filters.offset || 0,
          orderBy: { timestamp: 'desc' },
          include: {
            user: {
              select: {
                id: true,
                name: true,
                email: true
              }
            }
          }
        }),
        prisma.auditLog.count({ where })
      ]);

      return {
        logs,
        total,
        hasMore: (filters.offset || 0) + logs.length < total
      };
    } catch (error) {
      logger.error('Failed to get audit logs:', error);
      throw error;
    }
  }

  async getEntityHistory(entityType: string, entityId: string, tenantId: string) {
    try {
      return await prisma.auditLog.findMany({
        where: {
          entityType,
          entityId,
          tenantId
        },
        orderBy: { timestamp: 'desc' },
        include: {
          user: {
            select: {
              id: true,
              name: true,
              email: true
            }
          }
        }
      });
    } catch (error) {
      logger.error('Failed to get entity history:', error);
      throw error;
    }
  }

  async getUserActivity(userId: string, tenantId: string, limit = 50) {
    try {
      return await prisma.auditLog.findMany({
        where: {
          userId,
          tenantId
        },
        take: limit,
        orderBy: { timestamp: 'desc' }
      });
    } catch (error) {
      logger.error('Failed to get user activity:', error);
      throw error;
    }
  }
}