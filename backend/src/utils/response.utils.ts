import { Response } from 'express';
import { logger } from '../config/logger';

// واجهة الاستجابة الموحدة
interface ApiResponse<T = any> {
  success: boolean;
  message?: string;
  data?: T;
  meta?: {
    page?: number;
    limit?: number;
    total?: number;
    totalPages?: number;
  };
  errors?: any[];
  code?: string;
}

// واجهة خيارات الاستجابة
interface ResponseOptions {
  statusCode?: number;
  headers?: Record<string, string>;
}

// دالة إرسال استجابة ناجحة
export const sendSuccess = <T>(
  res: Response,
  data?: T,
  message: string = 'تمت العملية بنجاح',
  options: ResponseOptions = {}
): Response => {
  const { statusCode = 200, headers = {} } = options;
  
  const response: ApiResponse<T> = {
    success: true,
    message,
    data
  };
  
  // إضافة الرؤوس المخصصة
  Object.entries(headers).forEach(([key, value]) => {
    res.setHeader(key, value);
  });
  
  return res.status(statusCode).json(response);
};

// دالة إرسال استجابة خطأ
export const sendError = (
  res: Response,
  message: string = 'حدث خطأ',
  statusCode: number = 400,
  errors?: any[],
  code?: string
): Response => {
  const response: ApiResponse = {
    success: false,
    message,
    errors,
    code
  };
  
  // تسجيل الخطأ
  logger.error('استجابة خطأ:', {
    statusCode,
    message,
    errors,
    code
  });
  
  return res.status(statusCode).json(response);
};

// دالة إرسال استجابة مع ترقيم الصفحات
export const sendPaginated = <T>(
  res: Response,
  data: T[],
  pagination: {
    page: number;
    limit: number;
    total: number;
  },
  message: string = 'تم جلب البيانات بنجاح'
): Response => {
  const { page, limit, total } = pagination;
  const totalPages = Math.ceil(total / limit);
  
  const response: ApiResponse<T[]> = {
    success: true,
    message,
    data,
    meta: {
      page,
      limit,
      total,
      totalPages
    }
  };
  
  return res.status(200).json(response);
};

// دالة إرسال استجابة تم الإنشاء
export const sendCreated = <T>(
  res: Response,
  data: T,
  message: string = 'تم الإنشاء بنجاح'
): Response => {
  return sendSuccess(res, data, message, { statusCode: 201 });
};

// دالة إرسال استجابة بدون محتوى
export const sendNoContent = (res: Response): Response => {
  return res.status(204).send();
};

// دالة إرسال استجابة غير مصرح
export const sendUnauthorized = (
  res: Response,
  message: string = 'غير مصرح'
): Response => {
  return sendError(res, message, 401, undefined, 'UNAUTHORIZED');
};

// دالة إرسال استجابة ممنوع
export const sendForbidden = (
  res: Response,
  message: string = 'غير مسموح'
): Response => {
  return sendError(res, message, 403, undefined, 'FORBIDDEN');
};

// دالة إرسال استجابة غير موجود
export const sendNotFound = (
  res: Response,
  resource: string = 'المورد'
): Response => {
  return sendError(res, `${resource} غير موجود`, 404, undefined, 'NOT_FOUND');
};

// دالة إرسال استجابة تعارض
export const sendConflict = (
  res: Response,
  message: string = 'تعارض في البيانات'
): Response => {
  return sendError(res, message, 409, undefined, 'CONFLICT');
};

// دالة إرسال استجابة خطأ في التحقق
export const sendValidationError = (
  res: Response,
  errors: any[],
  message: string = 'بيانات غير صحيحة'
): Response => {
  return sendError(res, message, 400, errors, 'VALIDATION_ERROR');
};

// دالة إرسال استجابة خطأ في الخادم
export const sendServerError = (
  res: Response,
  message: string = 'خطأ في الخادم'
): Response => {
  return sendError(res, message, 500, undefined, 'SERVER_ERROR');
};

// دالة إرسال استجابة ملف
export const sendFile = (
  res: Response,
  filePath: string,
  filename?: string
): void => {
  const options: any = {};
  
  if (filename) {
    res.setHeader('Content-Disposition', `attachment; filename="${filename}"`);
  }
  
  res.sendFile(filePath, options, (err) => {
    if (err) {
      logger.error('خطأ في إرسال الملف:', err);
      sendServerError(res, 'فشل إرسال الملف');
    }
  });
};

// دالة إرسال استجابة JSON للتنزيل
export const sendJsonDownload = <T>(
  res: Response,
  data: T,
  filename: string = 'data.json'
): Response => {
  res.setHeader('Content-Type', 'application/json');
  res.setHeader('Content-Disposition', `attachment; filename="${filename}"`);
  return res.json(data);
};

// دالة إرسال استجابة CSV
export const sendCSV = (
  res: Response,
  csvContent: string,
  filename: string = 'data.csv'
): Response => {
  res.setHeader('Content-Type', 'text/csv; charset=utf-8');
  res.setHeader('Content-Disposition', `attachment; filename="${filename}"`);
  // إضافة BOM لدعم اللغة العربية في Excel
  return res.send('\uFEFF' + csvContent);
};

// دالة إرسال استجابة مع التخزين المؤقت
export const sendWithCache = <T>(
  res: Response,
  data: T,
  cacheTime: number = 300, // 5 دقائق افتراضياً
  message?: string
): Response => {
  res.setHeader('Cache-Control', `public, max-age=${cacheTime}`);
  res.setHeader('Expires', new Date(Date.now() + cacheTime * 1000).toUTCString());
  return sendSuccess(res, data, message);
};

// دالة إرسال استجابة مع رؤوس CORS مخصصة
export const sendWithCORS = <T>(
  res: Response,
  data: T,
  allowedOrigin: string = '*',
  message?: string
): Response => {
  const headers = {
    'Access-Control-Allow-Origin': allowedOrigin,
    'Access-Control-Allow-Methods': 'GET, POST, PUT, DELETE, OPTIONS',
    'Access-Control-Allow-Headers': 'Content-Type, Authorization'
  };
  
  return sendSuccess(res, data, message, { headers });
};

// دالة معالجة استجابة البث (Streaming)
export const streamResponse = (
  res: Response,
  stream: NodeJS.ReadableStream,
  contentType: string = 'application/octet-stream'
): void => {
  res.setHeader('Content-Type', contentType);
  res.setHeader('Transfer-Encoding', 'chunked');
  
  stream.pipe(res);
  
  stream.on('error', (err) => {
    logger.error('خطأ في البث:', err);
    if (!res.headersSent) {
      sendServerError(res, 'خطأ في نقل البيانات');
    }
  });
};

// دالة إرسال استجابة SSE (Server-Sent Events)
export const initSSE = (res: Response): void => {
  res.setHeader('Content-Type', 'text/event-stream');
  res.setHeader('Cache-Control', 'no-cache');
  res.setHeader('Connection', 'keep-alive');
  res.setHeader('Access-Control-Allow-Origin', '*');
  
  // إرسال تعليق للحفاظ على الاتصال
  res.write(':ok\n\n');
};

export const sendSSEMessage = (
  res: Response,
  data: any,
  event?: string,
  id?: string
): void => {
  if (id) res.write(`id: ${id}\n`);
  if (event) res.write(`event: ${event}\n`);
  res.write(`data: ${JSON.stringify(data)}\n\n`);
  // Note: flush is not available on Express Response object
};