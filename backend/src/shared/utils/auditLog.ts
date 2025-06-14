import logger from './logger';

interface AuditLogData {
  userId?: string;
  tenantId?: string;
  action: string;
  resource: string;
  resourceId?: string;
  oldData?: any;
  newData?: any;
  ipAddress?: string;
  userAgent?: string;
  timestamp?: Date;
}

class AuditLogUtil {
  async log(data: AuditLogData): Promise<void> {
    try {
      const auditEntry = {
        ...data,
        timestamp: data.timestamp || new Date(),
      };

      // Log the audit entry
      logger.info('Audit Log', auditEntry);

      // Here you could also save to database if needed
      // await prisma.auditLog.create({ data: auditEntry });
    } catch (error) {
      logger.error('Failed to create audit log', error);
    }
  }

  async logCreate(resource: string, resourceId: string, newData: any, userId?: string, tenantId?: string): Promise<void> {
    await this.log({
      userId,
      tenantId,
      action: 'CREATE',
      resource,
      resourceId,
      newData,
    });
  }

  async logUpdate(resource: string, resourceId: string, oldData: any, newData: any, userId?: string, tenantId?: string): Promise<void> {
    await this.log({
      userId,
      tenantId,
      action: 'UPDATE',
      resource,
      resourceId,
      oldData,
      newData,
    });
  }

  async logDelete(resource: string, resourceId: string, oldData: any, userId?: string, tenantId?: string): Promise<void> {
    await this.log({
      userId,
      tenantId,
      action: 'DELETE',
      resource,
      resourceId,
      oldData,
    });
  }

  async logView(resource: string, resourceId: string, userId?: string, tenantId?: string): Promise<void> {
    await this.log({
      userId,
      tenantId,
      action: 'VIEW',
      resource,
      resourceId,
    });
  }
}

export const auditLogUtil = new AuditLogUtil();
export default auditLogUtil;