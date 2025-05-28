// Error handling utilities

export class AppError extends Error {
  constructor(
    public message: string,
    public code?: string,
    public statusCode?: number,
    public details?: any
  ) {
    super(message);
    this.name = 'AppError';
    Object.setPrototypeOf(this, AppError.prototype);
  }
}

export class ValidationError extends AppError {
  constructor(message: string, details?: any) {
    super(message, 'VALIDATION_ERROR', 400, details);
    this.name = 'ValidationError';
  }
}

export class AuthenticationError extends AppError {
  constructor(message: string = 'غير مصرح') {
    super(message, 'AUTHENTICATION_ERROR', 401);
    this.name = 'AuthenticationError';
  }
}

export class AuthorizationError extends AppError {
  constructor(message: string = 'غير مسموح') {
    super(message, 'AUTHORIZATION_ERROR', 403);
    this.name = 'AuthorizationError';
  }
}

export class NotFoundError extends AppError {
  constructor(message: string = 'غير موجود') {
    super(message, 'NOT_FOUND_ERROR', 404);
    this.name = 'NotFoundError';
  }
}

export class NetworkError extends AppError {
  constructor(message: string = 'خطأ في الشبكة') {
    super(message, 'NETWORK_ERROR', 0);
    this.name = 'NetworkError';
  }
}

// Error messages mapping
export const errorMessages: Record<string, string> = {
  // Authentication errors
  'auth/invalid-credentials': 'بيانات الدخول غير صحيحة',
  'auth/user-not-found': 'المستخدم غير موجود',
  'auth/wrong-password': 'كلمة المرور غير صحيحة',
  'auth/email-already-in-use': 'البريد الإلكتروني مستخدم بالفعل',
  'auth/weak-password': 'كلمة المرور ضعيفة',
  'auth/invalid-email': 'البريد الإلكتروني غير صالح',
  'auth/account-exists-with-different-credential': 'الحساب موجود بالفعل مع بيانات دخول مختلفة',
  'auth/credential-already-in-use': 'بيانات الدخول مستخدمة بالفعل',
  'auth/timeout': 'انتهت مدة الجلسة',
  
  // Network errors
  'network/offline': 'لا يوجد اتصال بالإنترنت',
  'network/timeout': 'انتهت مهلة الطلب',
  'network/server-error': 'خطأ في الخادم',
  
  // Validation errors
  'validation/required': 'هذا الحقل مطلوب',
  'validation/invalid-format': 'التنسيق غير صالح',
  'validation/too-short': 'القيمة قصيرة جداً',
  'validation/too-long': 'القيمة طويلة جداً',
  
  // Business logic errors
  'business/insufficient-funds': 'الرصيد غير كافي',
  'business/duplicate-entry': 'الإدخال مكرر',
  'business/limit-exceeded': 'تم تجاوز الحد المسموح',
  'business/not-eligible': 'غير مؤهل',
  
  // Generic errors
  'unknown': 'حدث خطأ غير متوقع',
  'permission-denied': 'ليس لديك صلاحية للقيام بهذا الإجراء',
  'resource-not-found': 'المورد المطلوب غير موجود',
  'operation-failed': 'فشلت العملية',
};

// Get user-friendly error message
export const getErrorMessage = (error: any): string => {
  if (error instanceof AppError) {
    return error.message;
  }
  
  if (error?.code && errorMessages[error.code]) {
    return errorMessages[error.code];
  }
  
  if (error?.message) {
    return error.message;
  }
  
  return errorMessages.unknown;
};

// Error handler for async functions
export const handleAsyncError = async <T>(
  asyncFn: () => Promise<T>,
  errorHandler?: (error: any) => void
): Promise<T | null> => {
  try {
    return await asyncFn();
  } catch (error) {
    if (errorHandler) {
      errorHandler(error);
    } else {
      console.error('Unhandled error:', error);
    }
    return null;
  }
};

// Retry logic for failed operations
export const retryOperation = async <T>(
  operation: () => Promise<T>,
  maxRetries: number = 3,
  delay: number = 1000,
  backoff: number = 2
): Promise<T> => {
  let lastError: any;
  
  for (let i = 0; i < maxRetries; i++) {
    try {
      return await operation();
    } catch (error) {
      lastError = error;
      
      if (i < maxRetries - 1) {
        await new Promise(resolve => setTimeout(resolve, delay * Math.pow(backoff, i)));
      }
    }
  }
  
  throw lastError;
};

// Global error boundary error handler
export const logError = (error: Error, errorInfo?: any) => {
  console.error('Error caught by error boundary:', error);
  
  if (errorInfo) {
    console.error('Error info:', errorInfo);
  }
  
  // Send to error tracking service (e.g., Sentry)
  // if (window.Sentry) {
  //   window.Sentry.captureException(error, {
  //     contexts: {
  //       react: {
  //         componentStack: errorInfo?.componentStack,
  //       },
  //     },
  //   });
  // }
};

// Format error for display
export const formatError = (error: any): {
  title: string;
  message: string;
  details?: any;
} => {
  if (error instanceof AppError) {
    return {
      title: getErrorTypeTitle(error),
      message: error.message,
      details: error.details,
    };
  }
  
  return {
    title: 'خطأ',
    message: getErrorMessage(error),
  };
};

// Get error type title
const getErrorTypeTitle = (error: AppError): string => {
  switch (error.constructor) {
    case ValidationError:
      return 'خطأ في البيانات';
    case AuthenticationError:
      return 'خطأ في المصادقة';
    case AuthorizationError:
      return 'خطأ في الصلاحيات';
    case NotFoundError:
      return 'غير موجود';
    case NetworkError:
      return 'خطأ في الشبكة';
    default:
      return 'خطأ';
  }
};

// Check if error is retryable
export const isRetryableError = (error: any): boolean => {
  if (error instanceof NetworkError) {
    return true;
  }
  
  if (error instanceof AppError && error.statusCode) {
    // Retry on 5xx errors or specific 4xx errors
    return error.statusCode >= 500 || error.statusCode === 429;
  }
  
  return false;
};

// Create error from API response
export const createErrorFromResponse = (response: any): AppError => {
  const status = response?.status || response?.statusCode;
  const message = response?.data?.message || response?.message || 'حدث خطأ';
  const code = response?.data?.code || response?.code;
  const details = response?.data?.details || response?.details;
  
  switch (status) {
    case 400:
      return new ValidationError(message, details);
    case 401:
      return new AuthenticationError(message);
    case 403:
      return new AuthorizationError(message);
    case 404:
      return new NotFoundError(message);
    default:
      return new AppError(message, code, status, details);
  }
};