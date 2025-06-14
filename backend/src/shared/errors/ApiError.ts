// فئة خطأ API المخصصة

export class ApiError extends Error {
  public statusCode: number;
  public code: string;
  public details?: any;
  public isOperational: boolean;

  constructor(
    message: string,
    statusCode: number = 500,
    code: string = 'INTERNAL_ERROR',
    details?: any,
    isOperational: boolean = true
  ) {
    super(message);
    this.name = 'ApiError';
    this.statusCode = statusCode;
    this.code = code;
    this.details = details;
    this.isOperational = isOperational;

    // الحفاظ على مكدس الاستدعاءات
    Error.captureStackTrace(this, this.constructor);
  }

  // أخطاء شائعة
  static badRequest(message: string = 'طلب غير صحيح', details?: any): ApiError {
    return new ApiError(message, 400, 'BAD_REQUEST', details);
  }

  static unauthorized(message: string = 'غير مصرح', details?: any): ApiError {
    return new ApiError(message, 401, 'UNAUTHORIZED', details);
  }

  static forbidden(message: string = 'ممنوع', details?: any): ApiError {
    return new ApiError(message, 403, 'FORBIDDEN', details);
  }

  static notFound(message: string = 'غير موجود', details?: any): ApiError {
    return new ApiError(message, 404, 'NOT_FOUND', details);
  }

  static conflict(message: string = 'تضارب في البيانات', details?: any): ApiError {
    return new ApiError(message, 409, 'CONFLICT', details);
  }

  static validationError(message: string = 'خطأ في التحقق', details?: any): ApiError {
    return new ApiError(message, 422, 'VALIDATION_ERROR', details);
  }

  static internal(message: string = 'خطأ داخلي في الخادم', details?: any): ApiError {
    return new ApiError(message, 500, 'INTERNAL_ERROR', details);
  }

  // تحويل إلى JSON
  toJSON() {
    return {
      name: this.name,
      message: this.message,
      statusCode: this.statusCode,
      code: this.code,
      details: this.details,
      stack: this.stack
    };
  }
}