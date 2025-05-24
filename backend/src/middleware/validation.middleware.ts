import { Request, Response, NextFunction } from 'express';
import { z, ZodError, ZodSchema } from 'zod';
import { logger } from '../config/logger';

// واجهة للطلب مع البيانات المُحققة
interface ValidatedRequest extends Request {
  validatedData?: {
    body?: any;
    query?: any;
    params?: any;
  };
}

// دالة مساعدة لتنسيق أخطاء Zod
const formatZodErrors = (error: ZodError): any[] => {
  return error.errors.map(err => ({
    field: err.path.join('.'),
    message: err.message,
    code: err.code
  }));
};

// Middleware للتحقق من صحة البيانات
export const validate = (schemas: {
  body?: ZodSchema;
  query?: ZodSchema;
  params?: ZodSchema;
}) => {
  return async (
    req: ValidatedRequest,
    res: Response,
    next: NextFunction
  ): Promise<void> => {
    try {
      // تهيئة كائن البيانات المُحققة
      req.validatedData = {};

      // التحقق من body
      if (schemas.body) {
        try {
          req.validatedData.body = await schemas.body.parseAsync(req.body);
        } catch (error) {
          if (error instanceof ZodError) {
            res.status(400).json({
              success: false,
              message: 'بيانات الطلب غير صحيحة',
              errors: formatZodErrors(error)
            });
            return;
          }
          throw error;
        }
      }

      // التحقق من query
      if (schemas.query) {
        try {
          req.validatedData.query = await schemas.query.parseAsync(req.query);
        } catch (error) {
          if (error instanceof ZodError) {
            res.status(400).json({
              success: false,
              message: 'معاملات الاستعلام غير صحيحة',
              errors: formatZodErrors(error)
            });
            return;
          }
          throw error;
        }
      }

      // التحقق من params
      if (schemas.params) {
        try {
          req.validatedData.params = await schemas.params.parseAsync(req.params);
        } catch (error) {
          if (error instanceof ZodError) {
            res.status(400).json({
              success: false,
              message: 'معاملات المسار غير صحيحة',
              errors: formatZodErrors(error)
            });
            return;
          }
          throw error;
        }
      }

      next();
    } catch (error) {
      logger.error('خطأ في التحقق من صحة البيانات:', error);
      res.status(500).json({
        success: false,
        message: 'خطأ في التحقق من صحة البيانات'
      });
    }
  };
};

// مخططات مشتركة للتحقق
export const commonSchemas = {
  // معرف UUID
  uuid: z.string().uuid('معرف غير صالح'),
  
  // البريد الإلكتروني
  email: z.string().email('بريد إلكتروني غير صالح'),
  
  // رقم الهاتف (مصري)
  phoneNumber: z.string().regex(
    /^(\+20|0)?1[0125][0-9]{8}$/,
    'رقم هاتف غير صالح'
  ),
  
  // التاريخ
  date: z.string().datetime('تاريخ غير صالح'),
  
  // المبلغ المالي
  amount: z.number().positive('المبلغ يجب أن يكون موجباً').multipleOf(0.01),
  
  // النسبة المئوية
  percentage: z.number().min(0, 'النسبة لا يمكن أن تكون سالبة').max(100, 'النسبة لا يمكن أن تتجاوز 100'),
  
  // معاملات الترقيم والترتيب
  pagination: z.object({
    page: z.number().int().positive().default(1),
    limit: z.number().int().positive().max(100).default(20),
    sort: z.string().optional(),
    order: z.enum(['asc', 'desc']).default('desc')
  }),
  
  // معاملات البحث
  search: z.object({
    q: z.string().min(1).optional(),
    filters: z.record(z.any()).optional()
  })
};

// دالة مساعدة لإنشاء مخطط مع حقول اختيارية
export const partial = <T extends ZodSchema>(schema: T) => {
  return schema.partial();
};

// دالة مساعدة للتحقق من البيانات يدوياً
export const validateData = async <T>(
  schema: ZodSchema<T>,
  data: unknown
): Promise<{ success: true; data: T } | { success: false; errors: any[] }> => {
  try {
    const validatedData = await schema.parseAsync(data);
    return { success: true, data: validatedData };
  } catch (error) {
    if (error instanceof ZodError) {
      return { success: false, errors: formatZodErrors(error) };
    }
    throw error;
  }
};

// Middleware للتحقق من نوع المحتوى
export const requireContentType = (contentType: string) => {
  return (req: Request, res: Response, next: NextFunction): void => {
    const requestContentType = req.get('Content-Type');
    
    if (!requestContentType || !requestContentType.includes(contentType)) {
      res.status(415).json({
        success: false,
        message: `نوع المحتوى يجب أن يكون ${contentType}`,
        received: requestContentType
      });
      return;
    }
    
    next();
  };
};

// Middleware للتحقق من حجم الطلب
export const limitRequestSize = (maxSizeInMB: number) => {
  return (req: Request, res: Response, next: NextFunction): void => {
    const contentLength = req.get('Content-Length');
    
    if (contentLength) {
      const sizeInBytes = parseInt(contentLength);
      const maxSizeInBytes = maxSizeInMB * 1024 * 1024;
      
      if (sizeInBytes > maxSizeInBytes) {
        res.status(413).json({
          success: false,
          message: `حجم الطلب يتجاوز الحد المسموح (${maxSizeInMB}MB)`,
          maxSize: `${maxSizeInMB}MB`,
          receivedSize: `${(sizeInBytes / (1024 * 1024)).toFixed(2)}MB`
        });
        return;
      }
    }
    
    next();
  };
};