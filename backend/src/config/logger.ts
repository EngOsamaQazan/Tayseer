import winston from 'winston';
import path from 'path';

// تنسيق مخصص للسجلات
const customFormat = winston.format.combine(
  winston.format.timestamp({
    format: 'YYYY-MM-DD HH:mm:ss'
  }),
  winston.format.errors({ stack: true }),
  winston.format.splat(),
  winston.format.json(),
  winston.format.printf(({ timestamp, level, message, ...metadata }) => {
    let msg = `${timestamp} [${level}] : ${message}`;
    if (Object.keys(metadata).length > 0) {
      msg += ` ${JSON.stringify(metadata)}`;
    }
    return msg;
  })
);

// إنشاء المسجل
export const logger = winston.createLogger({
  level: process.env.LOG_LEVEL || 'info',
  format: customFormat,
  defaultMeta: { service: 'tayseer-backend' },
  transports: [
    // كتابة جميع السجلات مع مستوى `error` وأقل إلى `error.log`
    new winston.transports.File({ 
      filename: path.join('logs', 'error.log'), 
      level: 'error',
      maxsize: 10485760, // 10MB
      maxFiles: 5,
    }),
    // كتابة جميع السجلات مع مستوى `info` وأقل إلى `combined.log`
    new winston.transports.File({ 
      filename: path.join('logs', 'combined.log'),
      maxsize: 10485760, // 10MB
      maxFiles: 5,
    }),
  ],
});

// في بيئة التطوير، سجل أيضًا إلى وحدة التحكم
if (process.env.NODE_ENV !== 'production') {
  logger.add(new winston.transports.Console({
    format: winston.format.combine(
      winston.format.colorize(),
      winston.format.simple()
    )
  }));
}

// دالة لتسجيل طلبات HTTP
export const httpLogger = winston.createLogger({
  level: 'info',
  format: winston.format.json(),
  defaultMeta: { service: 'http' },
  transports: [
    new winston.transports.File({ 
      filename: path.join('logs', 'http.log'),
      maxsize: 10485760, // 10MB
      maxFiles: 5,
    })
  ]
});

// دالة لتسجيل الأحداث المهمة
export const auditLogger = winston.createLogger({
  level: 'info',
  format: winston.format.json(),
  defaultMeta: { service: 'audit' },
  transports: [
    new winston.transports.File({ 
      filename: path.join('logs', 'audit.log'),
      maxsize: 10485760, // 10MB
      maxFiles: 10, // احتفظ بمزيد من ملفات التدقيق
    })
  ]
});

// تصدير دوال مساعدة للتسجيل
export const logError = (error: Error, context?: any) => {
  logger.error({
    message: error.message,
    stack: error.stack,
    context
  });
};

export const logInfo = (message: string, metadata?: any) => {
  logger.info(message, metadata);
};

export const logWarning = (message: string, metadata?: any) => {
  logger.warn(message, metadata);
};

export const logDebug = (message: string, metadata?: any) => {
  logger.debug(message, metadata);
};

export const logAudit = (action: string, userId: string, details: any) => {
  auditLogger.info({
    action,
    userId,
    timestamp: new Date().toISOString(),
    details
  });
};