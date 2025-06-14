import { prisma } from '../config/database';
import { logger } from './logger';
import { sendEmail } from './email.utils';

export interface NotificationData {
  tenantId: string;
  userId: string;
  type: string;
  title: string;
  message: string;
  metadata?: any;
  channels?: string[];
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
          data: data.metadata ? JSON.stringify(data.metadata) : null,
          priority: data.priority || 'medium',
          channels: data.channels || [],
          read: false
        }
      });

      // Get user details
      const user = await prisma.user.findUnique({
        where: { id: data.userId }
      });

      // Send email notification if user has email
      if (user?.email && (data.priority === 'high' || data.priority === 'urgent')) {
        await sendEmail({
          to: user.email,
          subject: data.title,
          template: `<h3>${data.title}</h3><p>${data.message}</p>`,
          data: { title: data.title, message: data.message }
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
      data: { read: true }
    });
  },
  async markAllAsRead(userId: string) {
    return prisma.notification.updateMany({
      where: { userId },
      data: { read: true }
    });
  },
  async getUnreadCount(userId: string) {
    return prisma.notification.count({
      where: { userId, read: false }
    });
  },
  async getUserNotifications(userId: string, options?: {
    limit?: number;
    offset?: number;
    unreadOnly?: boolean;
  }) {
    const where: any = { userId };
    if (options?.unreadOnly) where.read = false;

    return prisma.notification.findMany({
      where,
      take: options?.limit || 50,
      skip: options?.offset || 0,
      orderBy: { createdAt: 'desc' }
    });
  }
};