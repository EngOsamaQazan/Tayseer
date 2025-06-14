// نظام السجلات

import winston from 'winston';
import path from 'path';

// إعداد تنسيق السجلات
const logFormat = winston.format.combine(
  winston.format.timestamp({ format: 'YYYY-MM-DD HH:mm:ss' }),
  winston.format.errors({ stack: true }),
  winston.format.json(),
  winston.format.printf(({ timestamp, level, message, stack, ...meta }) => {
    return JSON.stringify({
      timestamp,
      level,
      message,
      stack,
      ...meta
    });
  })
);

// إنشاء مسجل السجلات
export const logger = winston.createLogger({
  level: process.env.LOG_LEVEL || 'info',
  format: logFormat,
  defaultMeta: { service: 'tayseer-backend' },
  transports: [
    // سجل الأخطاء
    new winston.transports.File({
      filename: path.join(process.cwd(), 'logs', 'error.log'),
      level: 'error',
      maxsize: 5242880, // 5MB
      maxFiles: 5
    }),
    // سجل مدمج
    new winston.transports.File({
      filename: path.join(process.cwd(), 'logs', 'combined.log'),
      maxsize: 5242880, // 5MB
      maxFiles: 5
    })
  ]
});

// إضافة طباعة وحدة التحكم في بيئة التطوير
if (process.env.NODE_ENV !== 'production') {
  logger.add(
    new winston.transports.Console({
      format: winston.format.combine(
        winston.format.colorize(),
        winston.format.simple(),
        winston.format.printf(({ timestamp, level, message, stack }) => {
          return `${timestamp} [${level}]: ${message}${stack ? '\n' + stack : ''}`;
        })
      )
    })
  );
}

// فئة Logger للاستخدام في أماكن أخرى
export class Logger {
  private context: string;

  constructor(context: string = 'Application') {
    this.context = context;
  }

  private formatMessage(message: string): string {
    return `[${this.context}] ${message}`;
  }

  info(message: string, meta?: any): void {
    logger.info(this.formatMessage(message), meta);
  }

  error(message: string, error?: Error | any, meta?: any): void {
    logger.error(this.formatMessage(message), {
      error: error instanceof Error ? {
        message: error.message,
        stack: error.stack,
        name: error.name
      } : error,
      ...meta
    });
  }

  warn(message: string, meta?: any): void {
    logger.warn(this.formatMessage(message), meta);
  }

  debug(message: string, meta?: any): void {
    logger.debug(this.formatMessage(message), meta);
  }

  verbose(message: string, meta?: any): void {
    logger.verbose(this.formatMessage(message), meta);
  }
}

// إنشاء مسجل افتراضي
export const defaultLogger = new Logger();

export default logger;