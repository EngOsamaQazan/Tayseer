import { prisma } from '../config/database';
import { logger } from './logger';

export interface AuditLogData {
  userId: string;
  tenantId: string;
  action: string;
  entityType: string;
  entityId?: string;
  oldValue?: any;
  newValue?: any;
  metadata?: any;
}

export const auditLogUtil = {
  async log(data: AuditLogData) {
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
          timestamp: new Date()
        }
      });
    } catch (error) {
      logger.error('Failed to create audit log', error);
    }
  },

  async getAuditLogs(filters: {
    tenantId: string;
    entityType?: string;
    entityId?: string;
    userId?: string;
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
    
    if (filters.startDate || filters.endDate) {
      where.timestamp = {};
      if (filters.startDate) where.timestamp.gte = filters.startDate;
      if (filters.endDate) where.timestamp.lte = filters.endDate;
    }

    return prisma.auditLog.findMany({
      where,
      take: filters.limit || 50,
      skip: filters.offset || 0,
      orderBy: { timestamp: 'desc' }
    });
  }
};