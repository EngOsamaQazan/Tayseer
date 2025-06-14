// مساعدات الاستجابة للـ API

import { Response } from 'express';
import { ApiResponse as IApiResponse, ValidationError, PaginationMeta } from '../interfaces/api.interface';

export class ApiResponse {
  // استجابة نجاح
  static success<T>(
    res: Response,
    data: T,
    message: string = 'تم بنجاح',
    statusCode: number = 200,
    meta?: PaginationMeta
  ): Response {
    const response: IApiResponse<T> = {
      success: true,
      message,
      data,
      meta
    };
    return res.status(statusCode).json(response);
  }

  // استجابة خطأ
  static error(
    res: Response,
    message: string = 'حدث خطأ',
    statusCode: number = 500,
    error?: string,
    errors?: ValidationError[]
  ): Response {
    const response: IApiResponse = {
      success: false,
      message,
      error,
      errors
    };
    return res.status(statusCode).json(response);
  }

  // استجابة إنشاء
  static created<T>(
    res: Response,
    data: T,
    message: string = 'تم الإنشاء بنجاح'
  ): Response {
    return ApiResponse.success(res, data, message, 201);
  }

  // استجابة تحديث
  static updated<T>(
    res: Response,
    data: T,
    message: string = 'تم التحديث بنجاح'
  ): Response {
    return ApiResponse.success(res, data, message, 200);
  }

  // استجابة حذف
  static deleted(
    res: Response,
    message: string = 'تم الحذف بنجاح'
  ): Response {
    return ApiResponse.success(res, null, message, 200);
  }

  // استجابة غير موجود
  static notFound(
    res: Response,
    message: string = 'العنصر غير موجود'
  ): Response {
    return ApiResponse.error(res, message, 404);
  }

  // استجابة طلب غير صحيح
  static badRequest(
    res: Response,
    message: string = 'طلب غير صحيح',
    errors?: ValidationError[]
  ): Response {
    return ApiResponse.error(res, message, 400, undefined, errors);
  }

  // استجابة غير مصرح
  static unauthorized(
    res: Response,
    message: string = 'غير مصرح'
  ): Response {
    return ApiResponse.error(res, message, 401);
  }

  // استجابة ممنوع
  static forbidden(
    res: Response,
    message: string = 'الوصول ممنوع'
  ): Response {
    return ApiResponse.error(res, message, 403);
  }

  // استجابة خطأ في التحقق
  static validationError(
    res: Response,
    errors: ValidationError[],
    message: string = 'خطأ في التحقق من البيانات'
  ): Response {
    return ApiResponse.error(res, message, 422, undefined, errors);
  }

  // استجابة خطأ داخلي
  static internalError(
    res: Response,
    message: string = 'خطأ داخلي في الخادم'
  ): Response {
    return ApiResponse.error(res, message, 500);
  }

  // استجابة مع تصفح الصفحات
  static paginated<T>(
    res: Response,
    data: T[],
    meta: PaginationMeta,
    message: string = 'تم جلب البيانات بنجاح'
  ): Response {
    return ApiResponse.success(res, data, message, 200, meta);
  }
}