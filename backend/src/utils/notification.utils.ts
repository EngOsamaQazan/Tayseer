import { Server as SocketServer } from 'socket.io';
import { PrismaClient } from '@prisma/client';
import { logger } from '../config/logger';
import { sendEmail } from './email.utils';
import { formatDateTime } from './date.utils';
import { formatCurrency } from './format.utils';

const prisma = new PrismaClient();

// أنواع الإشعارات
export enum NotificationType {
  INFO = 'INFO',
  SUCCESS = 'SUCCESS',
  WARNING = 'WARNING',
  ERROR = 'ERROR',
  ALERT = 'ALERT'
}

// أولويات الإشعارات
export enum NotificationPriority {
  LOW = 'LOW',
  MEDIUM = 'MEDIUM',
  HIGH = 'HIGH',
  URGENT = 'URGENT'
}

// قنوات الإشعارات
export enum NotificationChannel {
  IN_APP = 'IN_APP',
  EMAIL = 'EMAIL',
  SMS = 'SMS',
  PUSH = 'PUSH'
}

// واجهة الإشعار
export interface Notification {
  id?: string;
  tenantId: string;
  userId?: string;
  type: NotificationType;
  priority: NotificationPriority;
  title: string;
  message: string;
  data?: any;
  channels: NotificationChannel[];
  read?: boolean;
  readAt?: Date;
  createdAt?: Date;
}

// خدمة الإشعارات
export class NotificationService {
  private io: SocketServer | null = null;

  constructor(io?: SocketServer) {
    if (io) {
      this.io = io;
    }
  }

  // إرسال إشعار
  async sendNotification(notification: Notification): Promise<void> {
    try {
      // حفظ الإشعار في قاعدة البيانات
      const savedNotification = await prisma.notification.create({
        data: {
          tenantId: notification.tenantId,
          userId: notification.userId,
          type: notification.type,
          priority: notification.priority,
          title: notification.title,
          message: notification.message,
          data: notification.data ? JSON.stringify(notification.data) : null,
          channels: notification.channels,
          read: false
        }
      });

      // إرسال عبر القنوات المختلفة
      for (const channel of notification.channels) {
        switch (channel) {
          case NotificationChannel.IN_APP:
            await this.sendInAppNotification(savedNotification);
            break;
          case NotificationChannel.EMAIL:
            await this.sendEmailNotification(savedNotification);
            break;
          case NotificationChannel.SMS:
            await this.sendSMSNotification(savedNotification);
            break;
          case NotificationChannel.PUSH:
            await this.sendPushNotification(savedNotification);
            break;
        }
      }
    } catch (error) {
      logger.error('Error sending notification:', error);
      throw error;
    }
  }

  // إرسال إشعار داخل التطبيق
  private async sendInAppNotification(notification: any): Promise<void> {
    if (!this.io) {
      logger.warn('Socket.IO not initialized for in-app notifications');
      return;
    }

    // إرسال للمستخدم المحدد
    if (notification.userId) {
      this.io.to(`user-${notification.userId}`).emit('notification', {
        id: notification.id,
        type: notification.type,
        priority: notification.priority,
        title: notification.title,
        message: notification.message,
        data: notification.data ? JSON.parse(notification.data) : null,
        createdAt: notification.createdAt
      });
    } else {
      // إرسال لجميع مستخدمي المؤسسة
      this.io.to(`tenant-${notification.tenantId}`).emit('notification', {
        id: notification.id,
        type: notification.type,
        priority: notification.priority,
        title: notification.title,
        message: notification.message,
        data: notification.data ? JSON.parse(notification.data) : null,
        createdAt: notification.createdAt
      });
    }
  }

  // إرسال إشعار بالبريد الإلكتروني
  private async sendEmailNotification(notification: any): Promise<void> {
    try {
      let recipients: string[] = [];

      if (notification.userId) {
        const user = await prisma.user.findUnique({
          where: { id: notification.userId },
          select: { email: true }
        });
        if (user?.email) {
          recipients.push(user.email);
        }
      } else {
        // إرسال لجميع مستخدمي المؤسسة
        const users = await prisma.user.findMany({
          where: { tenantId: notification.tenantId, isActive: true },
          select: { email: true }
        });
        recipients = users.map(u => u.email).filter(Boolean);
      }

      for (const email of recipients) {
        await sendEmail({
          to: email,
          subject: notification.title,
          html: `
            <div style="direction: rtl; font-family: Arial, sans-serif;">
              <h2>${notification.title}</h2>
              <p>${notification.message}</p>
              ${notification.data ? `<pre>${JSON.stringify(JSON.parse(notification.data), null, 2)}</pre>` : ''}
              <p style="color: #666; font-size: 12px;">تم الإرسال في: ${formatDateTime(notification.createdAt)}</p>
            </div>
          `
        });
      }
    } catch (error) {
      logger.error('Error sending email notification:', error);
    }
  }

  // إرسال إشعار SMS (يحتاج تكامل مع مزود SMS)
  private async sendSMSNotification(notification: any): Promise<void> {
    // TODO: تكامل مع مزود SMS
    logger.info('SMS notification not implemented yet', { notificationId: notification.id });
  }

  // إرسال إشعار Push (يحتاج تكامل مع خدمة Push)
  private async sendPushNotification(notification: any): Promise<void> {
    // TODO: تكامل مع خدمة Push Notifications
    logger.info('Push notification not implemented yet', { notificationId: notification.id });
  }

  // جلب إشعارات المستخدم
  async getUserNotifications(
    userId: string,
    options: {
      read?: boolean;
      type?: NotificationType;
      priority?: NotificationPriority;
      limit?: number;
      offset?: number;
    } = {}
  ): Promise<{ notifications: any[]; total: number }> {
    const where: any = { userId };

    if (options.read !== undefined) {
      where.read = options.read;
    }

    if (options.type) {
      where.type = options.type;
    }

    if (options.priority) {
      where.priority = options.priority;
    }

    const [notifications, total] = await Promise.all([
      prisma.notification.findMany({
        where,
        orderBy: { createdAt: 'desc' },
        take: options.limit || 20,
        skip: options.offset || 0
      }),
      prisma.notification.count({ where })
    ]);

    return {
      notifications: notifications.map(n => ({
        ...n,
        data: n.data ? JSON.parse(n.data) : null
      })),
      total
    };
  }

  // تحديد الإشعار كمقروء
  async markAsRead(notificationId: string, userId: string): Promise<void> {
    await prisma.notification.update({
      where: { id: notificationId, userId },
      data: { read: true }
    });
  }

  // تحديد جميع الإشعارات كمقروءة
  async markAllAsRead(userId: string): Promise<void> {
    await prisma.notification.updateMany({
      where: { userId, read: false },
      data: { read: true }
    });
  }

  // حذف إشعار
  async deleteNotification(notificationId: string, userId: string): Promise<void> {
    await prisma.notification.delete({
      where: { id: notificationId, userId }
    });
  }

  // حذف الإشعارات القديمة
  async cleanupOldNotifications(daysToKeep: number = 30): Promise<number> {
    const cutoffDate = new Date();
    cutoffDate.setDate(cutoffDate.getDate() - daysToKeep);

    const result = await prisma.notification.deleteMany({
      where: {
        createdAt: { lt: cutoffDate },
        read: true
      }
    });

    logger.info(`Deleted ${result.count} old notifications`);
    return result.count;
  }

  // إرسال إشعارات محددة
  async sendPaymentNotification(
    tenantId: string,
    userId: string,
    paymentData: {
      invoiceNumber: string;
      amount: number;
      customerName: string;
      paymentMethod: string;
    }
  ): Promise<void> {
    await this.sendNotification({
      tenantId,
      userId,
      type: NotificationType.SUCCESS,
      priority: NotificationPriority.HIGH,
      title: 'دفعة جديدة مستلمة',
      message: `تم استلام دفعة بقيمة ${formatCurrency(paymentData.amount)} من ${paymentData.customerName} للفاتورة رقم ${paymentData.invoiceNumber}`,
      data: paymentData,
      channels: [NotificationChannel.IN_APP, NotificationChannel.EMAIL]
    });
  }

  async sendTaskNotification(
    tenantId: string,
    userId: string,
    taskData: {
      taskId: string;
      taskTitle: string;
      assignedBy: string;
      dueDate: Date;
      priority: string;
    }
  ): Promise<void> {
    await this.sendNotification({
      tenantId,
      userId,
      type: NotificationType.INFO,
      priority: taskData.priority === 'HIGH' ? NotificationPriority.HIGH : NotificationPriority.MEDIUM,
      title: 'مهمة جديدة مسندة إليك',
      message: `تم إسناد مهمة "${taskData.taskTitle}" إليك من قبل ${taskData.assignedBy}. تاريخ الاستحقاق: ${formatDateTime(taskData.dueDate)}`,
      data: taskData,
      channels: [NotificationChannel.IN_APP, NotificationChannel.EMAIL]
    });
  }

  async sendInventoryAlertNotification(
    tenantId: string,
    alertData: {
      productName: string;
      productCode: string;
      currentQuantity: number;
      minimumQuantity: number;
    }
  ): Promise<void> {
    await this.sendNotification({
      tenantId,
      type: NotificationType.WARNING,
      priority: NotificationPriority.HIGH,
      title: 'تنبيه مخزون منخفض',
      message: `المنتج "${alertData.productName}" (${alertData.productCode}) وصل إلى حد المخزون الأدنى. الكمية الحالية: ${alertData.currentQuantity}, الحد الأدنى: ${alertData.minimumQuantity}`,
      data: alertData,
      channels: [NotificationChannel.IN_APP, NotificationChannel.EMAIL]
    });
  }

  async sendContractExpiryNotification(
    tenantId: string,
    contractData: {
      contractNumber: string;
      customerName: string;
      expiryDate: Date;
      daysRemaining: number;
    }
  ): Promise<void> {
    await this.sendNotification({
      tenantId,
      type: NotificationType.ALERT,
      priority: contractData.daysRemaining <= 7 ? NotificationPriority.URGENT : NotificationPriority.HIGH,
      title: 'تنبيه انتهاء عقد',
      message: `العقد رقم ${contractData.contractNumber} مع ${contractData.customerName} سينتهي في ${formatDateTime(contractData.expiryDate)} (متبقي ${contractData.daysRemaining} يوم)`,
      data: contractData,
      channels: [NotificationChannel.IN_APP, NotificationChannel.EMAIL]
    });
  }

  async sendSystemNotification(
    tenantId: string,
    systemData: {
      action: string;
      description: string;
      severity: 'low' | 'medium' | 'high' | 'critical';
    }
  ): Promise<void> {
    const priorityMap = {
      low: NotificationPriority.LOW,
      medium: NotificationPriority.MEDIUM,
      high: NotificationPriority.HIGH,
      critical: NotificationPriority.URGENT
    };

    const typeMap = {
      low: NotificationType.INFO,
      medium: NotificationType.WARNING,
      high: NotificationType.WARNING,
      critical: NotificationType.ERROR
    };

    await this.sendNotification({
      tenantId,
      type: typeMap[systemData.severity],
      priority: priorityMap[systemData.severity],
      title: `تنبيه نظام - ${systemData.action}`,
      message: systemData.description,
      data: systemData,
      channels: [NotificationChannel.IN_APP]
    });
  }
}

// دالة مساعدة لإنشاء خدمة الإشعارات
export const createNotificationService = (io?: SocketServer): NotificationService => {
  return new NotificationService(io);
};

// دالة لإرسال إشعار بسيط
export const sendQuickNotification = async (
  tenantId: string,
  userId: string,
  title: string,
  message: string,
  type: NotificationType = NotificationType.INFO,
  priority: NotificationPriority = NotificationPriority.MEDIUM
): Promise<void> => {
  const notificationService = new NotificationService();
  await notificationService.sendNotification({
    tenantId,
    userId,
    type,
    priority,
    title,
    message,
    channels: [NotificationChannel.IN_APP]
  });
};

// دالة لإرسال إشعار جماعي
export const sendBulkNotification = async (
  tenantId: string,
  userIds: string[],
  notification: Omit<Notification, 'tenantId' | 'userId'>
): Promise<void> => {
  const notificationService = new NotificationService();
  
  for (const userId of userIds) {
    await notificationService.sendNotification({
      ...notification,
      tenantId,
      userId
    });
  }
};

// دالة لجدولة إشعار
export const scheduleNotification = async (
  notification: Notification & { scheduleAt: Date }
): Promise<void> => {
  // TODO: تكامل مع نظام جدولة المهام
  logger.info('Scheduled notification not implemented yet', { notification });
};

// دالة لإحصائيات الإشعارات
export const getNotificationStats = async (
  tenantId: string,
  startDate: Date,
  endDate: Date
): Promise<{
  total: number;
  byType: Record<NotificationType, number>;
  byPriority: Record<NotificationPriority, number>;
  byChannel: Record<NotificationChannel, number>;
  readRate: number;
}> => {
  const notifications = await prisma.notification.findMany({
    where: {
      tenantId,
      createdAt: {
        gte: startDate,
        lte: endDate
      }
    }
  });

  const stats = {
    total: notifications.length,
    byType: {} as Record<NotificationType, number>,
    byPriority: {} as Record<NotificationPriority, number>,
    byChannel: {} as Record<NotificationChannel, number>,
    readRate: 0
  };

  // إحصائيات حسب النوع
  for (const type of Object.values(NotificationType)) {
    stats.byType[type] = notifications.filter(n => n.type === type).length;
  }

  // إحصائيات حسب الأولوية
  for (const priority of Object.values(NotificationPriority)) {
    stats.byPriority[priority] = notifications.filter(n => n.priority === priority).length;
  }

  // إحصائيات حسب القناة
  for (const channel of Object.values(NotificationChannel)) {
    stats.byChannel[channel] = notifications.filter(n => 
      n.channels.includes(channel)
    ).length;
  }

  // معدل القراءة
  const readCount = notifications.filter(n => n.read).length;
  stats.readRate = stats.total > 0 ? (readCount / stats.total) * 100 : 0;

  return stats;
};

// قوالب الإشعارات
export const notificationTemplates = {
  // قوالب المدفوعات
  paymentReceived: (data: { amount: number; customerName: string; invoiceNumber: string }) => ({
    title: 'دفعة جديدة مستلمة',
    message: `تم استلام دفعة بقيمة ${formatCurrency(data.amount)} من ${data.customerName} للفاتورة رقم ${data.invoiceNumber}`
  }),
  
  paymentOverdue: (data: { amount: number; customerName: string; daysOverdue: number }) => ({
    title: 'دفعة متأخرة',
    message: `دفعة بقيمة ${formatCurrency(data.amount)} من ${data.customerName} متأخرة بـ ${data.daysOverdue} يوم`
  }),

  // قوالب المهام
  taskAssigned: (data: { taskTitle: string; assignedBy: string; dueDate: Date }) => ({
    title: 'مهمة جديدة',
    message: `تم إسناد مهمة "${data.taskTitle}" إليك من قبل ${data.assignedBy}. الموعد النهائي: ${formatDateTime(data.dueDate)}`
  }),
  
  taskCompleted: (data: { taskTitle: string; completedBy: string }) => ({
    title: 'مهمة مكتملة',
    message: `تم إكمال المهمة "${data.taskTitle}" بواسطة ${data.completedBy}`
  }),

  // قوالب المخزون
  lowInventory: (data: { productName: string; currentQuantity: number }) => ({
    title: 'مخزون منخفض',
    message: `المنتج "${data.productName}" وصل إلى مستوى منخفض. الكمية المتبقية: ${data.currentQuantity}`
  }),
  
  outOfStock: (data: { productName: string; productCode: string }) => ({
    title: 'نفاد المخزون',
    message: `المنتج "${data.productName}" (${data.productCode}) نفد من المخزون`
  }),

  // قوالب العقود
  contractExpiring: (data: { contractNumber: string; customerName: string; daysRemaining: number }) => ({
    title: 'عقد قارب على الانتهاء',
    message: `العقد رقم ${data.contractNumber} مع ${data.customerName} سينتهي خلال ${data.daysRemaining} يوم`
  }),
  
  contractRenewed: (data: { contractNumber: string; customerName: string; newExpiryDate: Date }) => ({
    title: 'تم تجديد العقد',
    message: `تم تجديد العقد رقم ${data.contractNumber} مع ${data.customerName} حتى ${formatDateTime(data.newExpiryDate)}`
  }),

  // قوالب النظام
  systemUpdate: (data: { version: string; features: string[] }) => ({
    title: 'تحديث النظام',
    message: `تم تحديث النظام إلى الإصدار ${data.version}. المميزات الجديدة: ${data.features.join('، ')}`
  }),
  
  maintenanceScheduled: (data: { startTime: Date; duration: number }) => ({
    title: 'صيانة مجدولة',
    message: `سيتم إجراء صيانة للنظام في ${formatDateTime(data.startTime)} لمدة ${data.duration} دقيقة`
  })
};

// تصدير الأنواع - Notification type is now provided by Prisma
// export type { Notification };