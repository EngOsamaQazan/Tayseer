// Validation utilities for forms and data

export interface ValidationRule {
  required?: boolean;
  minLength?: number;
  maxLength?: number;
  pattern?: RegExp;
  custom?: (value: any) => boolean;
  message?: string;
}

export interface ValidationSchema {
  [key: string]: ValidationRule | ValidationRule[];
}

export interface ValidationError {
  field: string;
  message: string;
}

// Email validation
export const isValidEmail = (email: string): boolean => {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return emailRegex.test(email);
};

// Phone validation (supports international format)
export const isValidPhone = (phone: string): boolean => {
  const phoneRegex = /^[+]?[(]?[0-9]{1,4}[)]?[-\s\.]?[(]?[0-9]{1,4}[)]?[-\s\.]?[0-9]{1,4}[-\s\.]?[0-9]{1,9}$/;
  return phoneRegex.test(phone);
};

// Arabic text validation
export const isArabicText = (text: string): boolean => {
  const arabicRegex = /[\u0600-\u06FF\u0750-\u077F\u08A0-\u08FF\uFB50-\uFDFF\uFE70-\uFEFF]/;
  return arabicRegex.test(text);
};

// National ID validation (Saudi format)
export const isValidNationalId = (id: string): boolean => {
  const idRegex = /^[12]\d{9}$/;
  return idRegex.test(id);
};

// IBAN validation (Saudi format)
export const isValidIBAN = (iban: string): boolean => {
  const ibanRegex = /^SA\d{22}$/;
  return ibanRegex.test(iban.replace(/\s/g, ''));
};

// Date validation
export const isValidDate = (date: string): boolean => {
  const parsedDate = new Date(date);
  return !isNaN(parsedDate.getTime());
};

// Future date validation
export const isFutureDate = (date: string): boolean => {
  const parsedDate = new Date(date);
  const today = new Date();
  today.setHours(0, 0, 0, 0);
  return parsedDate > today;
};

// Past date validation
export const isPastDate = (date: string): boolean => {
  const parsedDate = new Date(date);
  const today = new Date();
  today.setHours(0, 0, 0, 0);
  return parsedDate < today;
};

// Number validation
export const isNumber = (value: any): boolean => {
  return !isNaN(value) && !isNaN(parseFloat(value));
};

// Positive number validation
export const isPositiveNumber = (value: any): boolean => {
  return isNumber(value) && parseFloat(value) > 0;
};

// Integer validation
export const isInteger = (value: any): boolean => {
  return isNumber(value) && Number.isInteger(parseFloat(value));
};

// URL validation
export const isValidURL = (url: string): boolean => {
  try {
    new URL(url);
    return true;
  } catch {
    return false;
  }
};

// Password strength validation
export const getPasswordStrength = (password: string): {
  score: number;
  feedback: string;
} => {
  let score = 0;
  const feedback: string[] = [];

  if (password.length >= 8) score++;
  else feedback.push('يجب أن تكون كلمة المرور 8 أحرف على الأقل');

  if (/[a-z]/.test(password)) score++;
  else feedback.push('يجب أن تحتوي على أحرف صغيرة');

  if (/[A-Z]/.test(password)) score++;
  else feedback.push('يجب أن تحتوي على أحرف كبيرة');

  if (/[0-9]/.test(password)) score++;
  else feedback.push('يجب أن تحتوي على أرقام');

  if (/[^a-zA-Z0-9]/.test(password)) score++;
  else feedback.push('يجب أن تحتوي على رموز خاصة');

  const strengthLevels = [
    'ضعيفة جداً',
    'ضعيفة',
    'متوسطة',
    'جيدة',
    'قوية',
  ];

  return {
    score,
    feedback: feedback.length > 0 ? feedback.join(', ') : strengthLevels[score - 1],
  };
};

// Form validation function
export const validateForm = <T extends Record<string, any>>(
  values: T,
  schema: ValidationSchema
): ValidationError[] => {
  const errors: ValidationError[] = [];

  Object.keys(schema).forEach((field) => {
    const rules = Array.isArray(schema[field]) ? schema[field] : [schema[field]];
    const value = values[field];

    rules.forEach((rule: ValidationRule) => {
      if (rule.required && (!value || value === '')) {
        errors.push({
          field,
          message: rule.message || `${field} مطلوب`,
        });
      }

      if (rule.minLength && value && value.length < rule.minLength) {
        errors.push({
          field,
          message: rule.message || `${field} يجب أن يكون على الأقل ${rule.minLength} أحرف`,
        });
      }

      if (rule.maxLength && value && value.length > rule.maxLength) {
        errors.push({
          field,
          message: rule.message || `${field} يجب ألا يتجاوز ${rule.maxLength} أحرف`,
        });
      }

      if (rule.pattern && value && !rule.pattern.test(value)) {
        errors.push({
          field,
          message: rule.message || `${field} غير صالح`,
        });
      }

      if (rule.custom && value && !rule.custom(value)) {
        errors.push({
          field,
          message: rule.message || `${field} غير صالح`,
        });
      }
    });
  });

  return errors;
};

// Convert validation errors to object format
export const errorsToObject = (errors: ValidationError[]): Record<string, string> => {
  return errors.reduce((acc, error) => {
    acc[error.field] = error.message;
    return acc;
  }, {} as Record<string, string>);
};

// Check if form has errors
export const hasErrors = (errors: ValidationError[]): boolean => {
  return errors.length > 0;
};

// Get first error for a field
export const getFieldError = (errors: ValidationError[], field: string): string | undefined => {
  const error = errors.find(e => e.field === field);
  return error?.message;
};

// Common validation schemas
export const commonSchemas = {
  email: {
    required: true,
    custom: isValidEmail,
    message: 'البريد الإلكتروني غير صالح',
  },
  phone: {
    required: true,
    custom: isValidPhone,
    message: 'رقم الهاتف غير صالح',
  },
  nationalId: {
    required: true,
    custom: isValidNationalId,
    message: 'رقم الهوية الوطنية غير صالح',
  },
  iban: {
    required: true,
    custom: isValidIBAN,
    message: 'رقم IBAN غير صالح',
  },
  required: {
    required: true,
    message: 'هذا الحقل مطلوب',
  },
};