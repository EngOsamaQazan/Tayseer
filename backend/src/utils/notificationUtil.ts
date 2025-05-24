import { prisma } from '../config/database';
import { logger } from './logger';
import { emailUtil } from './email.utils';

export interface NotificationData {
  tenantId: string;
  userId: string;
  type: string;
  title: string;
  message: string;
  metadata?: any;
  priority?: 'low' | 'medium' | 'high' | 'urgent';
}

export const notificationUtil = {
  async send(data: NotificationData) {
    try {
      // Create notification in database
      const notification = await prisma.notification.create({
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

      // Get user details
      const user = await prisma.user.findUnique({
        where: { id: data.userId }
      });

      // Send email notification if user has email
      if (user?.email && (data.priority === 'high' || data.priority === 'urgent')) {
        await emailUtil.sendNotification({
          to: user.email,
          subject: data.title,
          body: data.message,
          priority: data.priority
        });
      }

      return notification;
    } catch (error) {
      logger.error('Failed to send notification', error);
      throw error;
    }
  },

  async markAsRead(notificationId: string) {
    return prisma.notification.update({
      where: { id: notificationId },
      data: { isRead: true }
    });
  },

  async markAllAsRead(userId: string) {
    return prisma.notification.updateMany({
      where: { userId, isRead: false },
      data: { isRead: true }
    });
  },

  async getUnreadCount(userId: string) {
    return prisma.notification.count({
      where: { userId, isRead: false }
    });
  },

  async getUserNotifications(userId: string, options?: {
    limit?: number;
    offset?: number;
    unreadOnly?: boolean;
  }) {
    const where: any = { userId };
    if (options?.unreadOnly) where.isRead = false;

    return prisma.notification.findMany({
      where,
      take: options?.limit || 50,
      skip: options?.offset || 0,
      orderBy: { createdAt: 'desc' }
    });
  }
};