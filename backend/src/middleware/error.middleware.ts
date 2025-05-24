import { Request, Response, NextFunction } from 'express';
import { Prisma } from '@prisma/client';
import { ZodError } from 'zod';
import { logger } from '../config/logger';
import { SystemLog } from '../config/mongodb';

// أنواع الأخطاء المخصصة
export class AppError extends Error {
  statusCode: number;
  code?: string;
  isOperational: boolean;

  constructor(
    message: string,
    statusCode: number = 500,
    code?: string,
    isOperational: boolean = true
  ) {
    super(message);
    this.statusCode = statusCode;
    this.code = code;
    this.isOperational = isOperational;
    Error.captureStackTrace(this, this.constructor);
  }
}

// أخطاء المصادقة
export class AuthenticationError extends AppError {
  constructor(message: string = 'غير مصرح') {
    super(message, 401, 'AUTHENTICATION_ERROR');
  }
}

// أخطاء التخويل
export class AuthorizationError extends AppError {
  constructor(message: string = 'غير مسموح') {
    super(message, 403, 'AUTHORIZATION_ERROR');
  }
}

// أخطاء التحقق
export class ValidationError extends AppError {
  constructor(message: string = 'بيانات غير صحيحة') {
    super(message, 400, 'VALIDATION_ERROR');
  }
}

// أخطاء عدم العثور
export class NotFoundError extends AppError {
  constructor(resource: string = 'المورد') {
    super(`${resource} غير موجود`, 404, 'NOT_FOUND');
  }
}

// أخطاء التعارض
export class ConflictError extends AppError {
  constructor(message: string = 'تعارض في البيانات') {
    super(message, 409, 'CONFLICT_ERROR');
  }
}

// أخطاء معدل الطلبات
export class RateLimitError extends AppError {
  constructor(message: string = 'تم تجاوز حد الطلبات') {
    super(message, 429, 'RATE_LIMIT_ERROR');
  }
}

// معالج الأخطاء العام
export const errorHandler = async (
  err: Error,
  req: Request,
  res: Response,
  next: NextFunction
): Promise<void> => {
  let error = err as AppError;

  // معالجة أخطاء Prisma
  if (err instanceof Prisma.PrismaClientKnownRequestError) {
    switch (err.code) {
      case 'P2002':
        // انتهاك القيد الفريد
        const field = (err.meta?.target as string[])?.[0] || 'حقل';
        error = new ConflictError(`${field} موجود بالفعل`);
        break;
      case 'P2025':
        // السجل غير موجود
        error = new NotFoundError('السجل المطلوب');
        break;
      case 'P2003':
        // انتهاك قيد المفتاح الأجنبي
        error = new ValidationError('مرجع غير صالح');
        break;
      default:
        error = new AppError('خطأ في قاعدة البيانات', 500, err.code);
    }
  }

  // معالجة أخطاء Zod
  if (err instanceof ZodError) {
    const errors = err.errors.map(e => ({
      field: e.path.join('.'),
      message: e.message
    }));
    error = new ValidationError('بيانات غير صحيحة');
    (error as any).errors = errors;
  }

  // معالجة أخطاء JWT
  if (err.name === 'JsonWebTokenError') {
    error = new AuthenticationError('رمز مصادقة غير صالح');
  } else if (err.name === 'TokenExpiredError') {
    error = new AuthenticationError('انتهت صلاحية رمز المصادقة');
  }

  // معالجة أخطاء MongoDB
  if (err.name === 'MongoError' || err.name === 'MongoServerError') {
    if ((err as any).code === 11000) {
      error = new ConflictError('البيانات موجودة بالفعل');
    } else {
      error = new AppError('خطأ في قاعدة البيانات', 500);
    }
  }

  // تعيين رمز الحالة الافتراضي
  if (!error.statusCode) {
    error.statusCode = 500;
  }

  // تسجيل الخطأ
  if (error.statusCode >= 500) {
    logger.error('خطأ في الخادم:', {
      error: error.message,
      stack: error.stack,
      url: req.url,
      method: req.method,
      ip: req.ip,
      user: (req as any).user?.id
    });

    // حفظ في MongoDB للأخطاء الحرجة
    try {
      await SystemLog.create({
        level: 'error',
        message: error.message,
        service: 'api',
        metadata: {
          url: req.url,
          method: req.method,
          statusCode: error.statusCode,
          headers: req.headers,
          body: req.body,
          user: (req as any).user,
          tenant: (req as any).tenant?.id
        },
        stack: error.stack,
        tenantId: (req as any).tenant?.id,
        userId: (req as any).user?.id
      });
    } catch (logError) {
      logger.error('فشل حفظ سجل الخطأ:', logError);
    }
  } else {
    logger.warn('خطأ من جهة العميل:', {
      error: error.message,
      url: req.url,
      method: req.method,
      statusCode: error.statusCode
    });
  }

  // إعداد الاستجابة
  const response: any = {
    success: false,
    message: error.message
  };

  // إضافة رمز الخطأ إن وجد
  if (error.code) {
    response.code = error.code;
  }

  // إضافة تفاصيل الأخطاء للتحقق
  if ((error as any).errors) {
    response.errors = (error as any).errors;
  }

  // في بيئة التطوير، إضافة تفاصيل إضافية
  if (process.env.NODE_ENV === 'development') {
    response.stack = error.stack;
    response.details = err;
  }

  res.status(error.statusCode).json(response);
};

// معالج للمسارات غير الموجودة
export const notFoundHandler = (req: Request, res: Response): void => {
  res.status(404).json({
    success: false,
    message: 'المسار المطلوب غير موجود',
    path: req.path,
    method: req.method
  });
};

// معالج للأخطاء غير المتزامنة
export const asyncHandler = (
  fn: (req: Request, res: Response, next: NextFunction) => Promise<any>
) => {
  return (req: Request, res: Response, next: NextFunction): void => {
    Promise.resolve(fn(req, res, next)).catch(next);
  };
};

// معالج لأخطاء التحميل
export class FileUploadError extends AppError {
  constructor(message: string = 'خطأ في رفع الملف') {
    super(message, 400, 'FILE_UPLOAD_ERROR');
  }
}

// معالج لأخطاء الدفع
export class PaymentError extends AppError {
  constructor(message: string = 'خطأ في عملية الدفع') {
    super(message, 402, 'PAYMENT_ERROR');
  }
}

// معالج لأخطاء الخدمات الخارجية
export class ExternalServiceError extends AppError {
  constructor(service: string, message?: string) {
    super(
      message || `خطأ في الاتصال بخدمة ${service}`,
      503,
      'EXTERNAL_SERVICE_ERROR'
    );
  }
}