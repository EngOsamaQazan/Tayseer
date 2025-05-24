import mongoose from 'mongoose';
import { logger } from './logger';

// إعدادات الاتصال
const options: mongoose.ConnectOptions = {
  maxPoolSize: 10,
  serverSelectionTimeoutMS: 5000,
  socketTimeoutMS: 45000,
};

// دالة الاتصال بـ MongoDB
export async function connectMongoDB(): Promise<void> {
  try {
    const uri = process.env.MONGODB_URI || 'mongodb://localhost:27017/tayseer_logs';
    
    // معالجة أحداث الاتصال
    mongoose.connection.on('connected', () => {
      logger.info('✅ تم الاتصال بـ MongoDB بنجاح');
    });

    mongoose.connection.on('error', (err) => {
      logger.error('خطأ في MongoDB:', err);
    });

    mongoose.connection.on('disconnected', () => {
      logger.warn('تم قطع الاتصال بـ MongoDB');
    });

    // الاتصال
    await mongoose.connect(uri, options);
  } catch (error) {
    logger.error('❌ فشل الاتصال بـ MongoDB:', error);
    throw error;
  }
}

// دالة قطع الاتصال
export async function disconnectMongoDB(): Promise<void> {
  try {
    await mongoose.disconnect();
    logger.info('تم قطع الاتصال بـ MongoDB');
  } catch (error) {
    logger.error('خطأ في قطع الاتصال بـ MongoDB:', error);
    throw error;
  }
}

// نماذج MongoDB للسجلات والأحداث

// نموذج سجلات النظام
const systemLogSchema = new mongoose.Schema({
  level: {
    type: String,
    enum: ['error', 'warn', 'info', 'debug'],
    required: true
  },
  message: {
    type: String,
    required: true
  },
  service: String,
  timestamp: {
    type: Date,
    default: Date.now,
    index: true
  },
  metadata: mongoose.Schema.Types.Mixed,
  stack: String,
  tenantId: {
    type: String,
    index: true
  },
  userId: {
    type: String,
    index: true
  }
}, {
  timeseries: {
    timeField: 'timestamp',
    metaField: 'metadata',
    granularity: 'minutes'
  }
});

// نموذج سجلات التدقيق
const auditLogSchema = new mongoose.Schema({
  action: {
    type: String,
    required: true,
    index: true
  },
  userId: {
    type: String,
    required: true,
    index: true
  },
  tenantId: {
    type: String,
    required: true,
    index: true
  },
  resourceType: {
    type: String,
    index: true
  },
  resourceId: String,
  changes: mongoose.Schema.Types.Mixed,
  metadata: mongoose.Schema.Types.Mixed,
  ipAddress: String,
  userAgent: String,
  timestamp: {
    type: Date,
    default: Date.now,
    index: true
  },
  status: {
    type: String,
    enum: ['success', 'failure'],
    default: 'success'
  }
});

// نموذج سجلات الأداء
const performanceLogSchema = new mongoose.Schema({
  endpoint: {
    type: String,
    required: true,
    index: true
  },
  method: {
    type: String,
    required: true
  },
  statusCode: Number,
  responseTime: {
    type: Number,
    required: true
  },
  tenantId: {
    type: String,
    index: true
  },
  userId: {
    type: String,
    index: true
  },
  timestamp: {
    type: Date,
    default: Date.now,
    index: true
  },
  metadata: mongoose.Schema.Types.Mixed
}, {
  timeseries: {
    timeField: 'timestamp',
    metaField: 'metadata',
    granularity: 'seconds'
  }
});

// إنشاء الفهارس
systemLogSchema.index({ timestamp: -1, level: 1 });
auditLogSchema.index({ timestamp: -1, action: 1 });
auditLogSchema.index({ userId: 1, timestamp: -1 });
performanceLogSchema.index({ timestamp: -1, endpoint: 1 });
performanceLogSchema.index({ responseTime: -1 });

// تصدير النماذج
export const SystemLog = mongoose.model('SystemLog', systemLogSchema);
export const AuditLog = mongoose.model('AuditLog', auditLogSchema);
export const PerformanceLog = mongoose.model('PerformanceLog', performanceLogSchema);

// دالة مساعدة لتنظيف السجلات القديمة
export async function cleanupOldLogs(daysToKeep: number = 30): Promise<void> {
  const cutoffDate = new Date();
  cutoffDate.setDate(cutoffDate.getDate() - daysToKeep);

  try {
    const systemResult = await SystemLog.deleteMany({ timestamp: { $lt: cutoffDate } });
    const auditResult = await AuditLog.deleteMany({ timestamp: { $lt: cutoffDate } });
    const performanceResult = await PerformanceLog.deleteMany({ timestamp: { $lt: cutoffDate } });

    logger.info(`تم حذف السجلات القديمة: ${systemResult.deletedCount} سجل نظام، ${auditResult.deletedCount} سجل تدقيق، ${performanceResult.deletedCount} سجل أداء`);
  } catch (error) {
    logger.error('خطأ في تنظيف السجلات القديمة:', error);
  }
}

// معالجة إيقاف التطبيق
process.on('beforeExit', async () => {
  await disconnectMongoDB();
});