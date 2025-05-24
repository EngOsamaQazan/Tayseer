import { prisma } from '@/lib/prisma';
import { logger } from '@/utils/logger';
import { emailUtil } from '@/utils/email.utils';

export interface NotificationData {
  tenantId: string;
  userId: string;
  type: string;
  title: string;
  message: string;
  metadata?: any;
  priority?: 'low' | 'medium' | 'high' | 'urgent';
  channels?: ('database' | 'email' | 'sms' | 'push')[];
}

export class NotificationService {
  private static instance: NotificationService;

  private constructor() {}

  public static getInstance(): NotificationService {
    if (!NotificationService.instance) {
      NotificationService.instance = new NotificationService();
    }
    return NotificationService.instance;
  }

  async send(data: NotificationData) {
    try {
      const channels = data.channels || ['database'];
      const results: any = {};

      // إرسال إشعار قاعدة البيانات
      if (channels.includes('database')) {
        results.database = await this.sendDatabaseNotification(data);
      }

      // إرسال إشعار بريد إلكتروني
      if (channels.includes('email')) {
        results.email = await this.sendEmailNotification(data);
      }

      // إرسال إشعار SMS (يمكن تنفيذه لاحقاً)
      if (channels.includes('sms')) {
        results.sms = await this.sendSMSNotification(data);
      }

      // إرسال إشعار Push (يمكن تنفيذه لاحقاً)
      if (channels.includes('push')) {
        results.push = await this.sendPushNotification(data);
      }

      return results;
    } catch (error) {
      logger.error('Failed to send notification:', error);
      throw error;
    }
  }

  private async sendDatabaseNotification(data: NotificationData) {
    return await prisma.notification.create({
      data: {
        tenantId: data.tenantId,
        userId: data.userId,
        type: data.type,
        title: data.title,
        message: data.message,
        metadata: data.metadata ? JSON.stringify(data.metadata) : null,
        priority: data.priority || 'medium',
        isRead: false,
        createdAt: new Date()
      }
    });
  }

  private async sendEmailNotification(data: NotificationData) {
    try {
      const user = await prisma.user.findUnique({
        where: { id: data.userId },
        select: { email: true, name: true }
      });

      if (!user?.email) {
        throw new Error('User email not found');
      }

      return await emailUtil.sendNotification({
        to: user.email,
        subject: data.title,
        body: data.message,
        priority: data.priority || 'medium'
      });
    } catch (error) {
      logger.error('Failed to send email notification:', error);
      throw error;
    }
  }

  private async sendSMSNotification(data: NotificationData) {
    // تنفيذ إرسال SMS
    logger.info('SMS notification not implemented yet');
    return { status: 'not_implemented' };
  }

  private async sendPushNotification(data: NotificationData) {
    // تنفيذ إرسال Push notification
    logger.info('Push notification not implemented yet');
    return { status: 'not_implemented' };
  }

  async markAsRead(notificationId: string, userId: string) {
    return await prisma.notification.updateMany({
      where: {
        id: notificationId,
        userId: userId
      },
      data: { isRead: true }
    });
  }

  async markAllAsRead(userId: string, tenantId: string) {
    return await prisma.notification.updateMany({
      where: {
        userId,
        tenantId,
        isRead: false
      },
      data: { isRead: true }
    });
  }

  async getUnreadCount(userId: string, tenantId: string) {
    return await prisma.notification.count({
      where: {
        userId,
        tenantId,
        isRead: false
      }
    });
  }

  async getUserNotifications(userId: string, tenantId: string, options?: {
    limit?: number;
    offset?: number;
    unreadOnly?: boolean;
    type?: string;
  }) {
    const where: any = { userId, tenantId };
    
    if (options?.unreadOnly) where.isRead = false;
    if (options?.type) where.type = options.type;

    const [notifications, total] = await Promise.all([
      prisma.notification.findMany({
        where,
        take: options?.limit || 50,
        skip: options?.offset || 0,
        orderBy: { createdAt: 'desc' }
      }),
      prisma.notification.count({ where })
    ]);

    return {
      notifications,
      total,
      hasMore: (options?.offset || 0) + notifications.length < total
    };
  }

  async deleteNotification(notificationId: string, userId: string) {
    return await prisma.notification.deleteMany({
      where: {
        id: notificationId,
        userId: userId
      }
    });
  }

  async bulkSend(notifications: NotificationData[]) {
    const results = [];
    
    for (const notification of notifications) {
      try {
        const result = await this.send(notification);
        results.push({ success: true, result });
      } catch (error) {
        results.push({ success: false, error: error.message });
      }
    }
    
    return results;
  }
}