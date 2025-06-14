// واجهات الاستجابة العامة للـ API

export interface ApiResponse<T = any> {
  success: boolean;
  message: string;
  data?: T;
  error?: string;
  errors?: ValidationError[];
  meta?: PaginationMeta;
}

export interface ValidationError {
  field: string;
  message: string;
  code?: string;
}

export interface PaginationMeta {
  page: number;
  limit: number;
  total: number;
  totalPages: number;
  hasNext: boolean;
  hasPrevious: boolean;
}

export interface ApiError {
  code: string;
  message: string;
  details?: any;
  statusCode?: number;
}

export interface ApiSuccessResponse<T = any> extends ApiResponse<T> {
  success: true;
  data: T;
}

export interface ApiErrorResponse extends ApiResponse {
  success: false;
  error: string;
  errors?: ValidationError[];
}

// أنواع الاستجابة المختلفة
export type ApiResponseType<T = any> = ApiSuccessResponse<T> | ApiErrorResponse;

// واجهة للبحث والفلترة
export interface SearchParams {
  q?: string;
  page?: number;
  limit?: number;
  sortBy?: string;
  sortOrder?: 'asc' | 'desc';
  filters?: Record<string, any>;
}

// واجهة لنتائج البحث
export interface SearchResult<T = any> {
  items: T[];
  meta: PaginationMeta;
  filters?: Record<string, any>;
}