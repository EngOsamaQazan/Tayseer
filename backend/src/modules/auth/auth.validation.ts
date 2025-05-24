import { z } from 'zod';
import {
  emailSchema,
  passwordSchema,
  nameSchema,
  saudiPhoneSchema,
  uuidSchema
} from '../../utils/validation.utils';

/**
 * مخطط التحقق من بيانات التسجيل
 */
export const validateRegister = z.object({
  body: z.object({
    name: nameSchema,
    email: emailSchema,
    password: passwordSchema,
    phone: saudiPhoneSchema.optional(),
    tenantId: uuidSchema,
    role: z.enum(['admin', 'manager', 'accountant', 'employee', 'viewer']).default('viewer'),
    permissions: z.array(z.string()).optional()
  })
});

/**
 * مخطط التحقق من بيانات تسجيل الدخول
 */
export const validateLogin = z.object({
  body: z.object({
    email: emailSchema,
    password: z.string().min(1, 'كلمة المرور مطلوبة'),
    rememberMe: z.boolean().optional().default(false)
  })
});

/**
 * مخطط التحقق من طلب إعادة تعيين كلمة المرور
 */
export const validateForgotPassword = z.object({
  body: z.object({
    email: emailSchema
  })
});

/**
 * مخطط التحقق من إعادة تعيين كلمة المرور
 */
export const validateResetPassword = z.object({
  body: z.object({
    token: z.string().min(1, 'رمز إعادة التعيين مطلوب'),
    password: passwordSchema
  })
});

/**
 * مخطط التحقق من تغيير كلمة المرور
 */
export const validateChangePassword = z.object({
  body: z.object({
    currentPassword: z.string().min(1, 'كلمة المرور الحالية مطلوبة'),
    newPassword: passwordSchema,
    confirmPassword: z.string().min(1, 'تأكيد كلمة المرور مطلوب')
  }).refine((data) => data.newPassword === data.confirmPassword, {
    message: 'كلمات المرور غير متطابقة',
    path: ['confirmPassword']
  })
});

/**
 * مخطط التحقق من تأكيد البريد الإلكتروني
 */
export const validateVerifyEmail = z.object({
  body: z.object({
    email: emailSchema,
    otp: z.string().length(6, 'رمز التحقق يجب أن يكون 6 أرقام')
  })
});

/**
 * مخطط التحقق من إعادة إرسال رمز التحقق
 */
export const validateResendOTP = z.object({
  body: z.object({
    email: emailSchema,
    type: z.enum(['email_verification', 'password_reset'])
  })
});

/**
 * مخطط التحقق من تحديث الملف الشخصي
 */
export const validateUpdateProfile = z.object({
  body: z.object({
    name: nameSchema.optional(),
    phone: saudiPhoneSchema.optional(),
    avatar: z.string().url('رابط الصورة غير صالح').optional()
  })
});

/**
 * مخطط التحقق من رمز التحديث
 */
export const validateRefreshToken = z.object({
  body: z.object({
    refreshToken: z.string().min(1, 'رمز التحديث مطلوب')
  })
});

/**
 * مخطط التحقق من تسجيل الخروج من جميع الأجهزة
 */
export const validateLogoutAll = z.object({
  body: z.object({
    password: z.string().min(1, 'كلمة المرور مطلوبة للتأكيد')
  })
});

/**
 * مخطط التحقق من حذف الحساب
 */
export const validateDeleteAccount = z.object({
  body: z.object({
    password: z.string().min(1, 'كلمة المرور مطلوبة للتأكيد'),
    reason: z.string().max(500, 'السبب يجب ألا يتجاوز 500 حرف').optional()
  })
});

/**
 * مخطط التحقق من تفعيل المصادقة الثنائية
 */
export const validateEnable2FA = z.object({
  body: z.object({
    password: z.string().min(1, 'كلمة المرور مطلوبة للتأكيد')
  })
});

/**
 * مخطط التحقق من تأكيد المصادقة الثنائية
 */
export const validateVerify2FA = z.object({
  body: z.object({
    token: z.string().length(6, 'رمز المصادقة يجب أن يكون 6 أرقام')
  })
});

/**
 * مخطط التحقق من تعطيل المصادقة الثنائية
 */
export const validateDisable2FA = z.object({
  body: z.object({
    password: z.string().min(1, 'كلمة المرور مطلوبة للتأكيد'),
    token: z.string().length(6, 'رمز المصادقة يجب أن يكون 6 أرقام')
  })
});

/**
 * أنواع البيانات المستخرجة من المخططات
 */
export type RegisterInput = z.infer<typeof validateRegister>['body'];
export type LoginInput = z.infer<typeof validateLogin>['body'];
export type ForgotPasswordInput = z.infer<typeof validateForgotPassword>['body'];
export type ResetPasswordInput = z.infer<typeof validateResetPassword>['body'];
export type ChangePasswordInput = z.infer<typeof validateChangePassword>['body'];
export type VerifyEmailInput = z.infer<typeof validateVerifyEmail>['body'];
export type ResendOTPInput = z.infer<typeof validateResendOTP>['body'];
export type UpdateProfileInput = z.infer<typeof validateUpdateProfile>['body'];
export type RefreshTokenInput = z.infer<typeof validateRefreshToken>['body'];
export type LogoutAllInput = z.infer<typeof validateLogoutAll>['body'];
export type DeleteAccountInput = z.infer<typeof validateDeleteAccount>['body'];
export type Enable2FAInput = z.infer<typeof validateEnable2FA>['body'];
export type Verify2FAInput = z.infer<typeof validateVerify2FA>['body'];
export type Disable2FAInput = z.infer<typeof validateDisable2FA>['body'];