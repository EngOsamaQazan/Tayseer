import { Router } from 'express';
import { AuthController } from './auth.controller';
import { authenticate } from '../../middleware/auth.middleware';
import { validateRequest } from '../../middleware/validation.middleware';
import { rateLimiter } from '../../middleware/rateLimit.middleware';
import {
  validateRegister,
  validateLogin,
  validateForgotPassword,
  validateResetPassword,
  validateChangePassword,
  validateVerifyEmail,
  validateResendOTP,
  validateUpdateProfile
} from './auth.validation';

const router = Router();

// معدلات الحد من الطلبات
const authRateLimiter = rateLimiter({
  windowMs: 15 * 60 * 1000, // 15 دقيقة
  max: 5, // 5 محاولات
  message: 'تم تجاوز عدد المحاولات المسموح بها، يرجى المحاولة لاحقاً'
});

const otpRateLimiter = rateLimiter({
  windowMs: 60 * 60 * 1000, // ساعة واحدة
  max: 3, // 3 محاولات
  message: 'تم تجاوز عدد محاولات إرسال رمز التحقق'
});

/**
 * @swagger
 * /api/v1/auth/register:
 *   post:
 *     summary: تسجيل مستخدم جديد
 *     tags: [Auth]
 *     requestBody:
 *       required: true
 *       content:
 *         application/json:
 *           schema:
 *             $ref: '#/components/schemas/RegisterRequest'
 *     responses:
 *       201:
 *         description: تم التسجيل بنجاح
 *       400:
 *         description: بيانات غير صالحة
 *       409:
 *         description: المستخدم موجود مسبقاً
 */
router.post(
  '/register',
  authRateLimiter,
  validateRequest(validateRegister),
  AuthController.register
);

/**
 * @swagger
 * /api/v1/auth/login:
 *   post:
 *     summary: تسجيل الدخول
 *     tags: [Auth]
 *     requestBody:
 *       required: true
 *       content:
 *         application/json:
 *           schema:
 *             $ref: '#/components/schemas/LoginRequest'
 *     responses:
 *       200:
 *         description: تم تسجيل الدخول بنجاح
 *       401:
 *         description: بيانات الدخول غير صحيحة
 */
router.post(
  '/login',
  authRateLimiter,
  validateRequest(validateLogin),
  AuthController.login
);

/**
 * @swagger
 * /api/v1/auth/logout:
 *   post:
 *     summary: تسجيل الخروج
 *     tags: [Auth]
 *     security:
 *       - bearerAuth: []
 *     responses:
 *       200:
 *         description: تم تسجيل الخروج بنجاح
 *       401:
 *         description: غير مصرح
 */
router.post(
  '/logout',
  authenticate,
  AuthController.logout
);

/**
 * @swagger
 * /api/v1/auth/refresh:
 *   post:
 *     summary: تجديد رمز الوصول
 *     tags: [Auth]
 *     requestBody:
 *       required: true
 *       content:
 *         application/json:
 *           schema:
 *             type: object
 *             properties:
 *               refreshToken:
 *                 type: string
 *     responses:
 *       200:
 *         description: تم تجديد الرمز بنجاح
 *       401:
 *         description: رمز التجديد غير صالح
 */
router.post(
  '/refresh',
  AuthController.refreshToken
);

/**
 * @swagger
 * /api/v1/auth/forgot-password:
 *   post:
 *     summary: طلب إعادة تعيين كلمة المرور
 *     tags: [Auth]
 *     requestBody:
 *       required: true
 *       content:
 *         application/json:
 *           schema:
 *             $ref: '#/components/schemas/ForgotPasswordRequest'
 *     responses:
 *       200:
 *         description: تم إرسال رابط إعادة التعيين
 *       404:
 *         description: المستخدم غير موجود
 */
router.post(
  '/forgot-password',
  authRateLimiter,
  validateRequest(validateForgotPassword),
  AuthController.forgotPassword
);

/**
 * @swagger
 * /api/v1/auth/reset-password:
 *   post:
 *     summary: إعادة تعيين كلمة المرور
 *     tags: [Auth]
 *     requestBody:
 *       required: true
 *       content:
 *         application/json:
 *           schema:
 *             $ref: '#/components/schemas/ResetPasswordRequest'
 *     responses:
 *       200:
 *         description: تم تغيير كلمة المرور بنجاح
 *       400:
 *         description: رمز إعادة التعيين غير صالح
 */
router.post(
  '/reset-password',
  validateRequest(validateResetPassword),
  AuthController.resetPassword
);

/**
 * @swagger
 * /api/v1/auth/change-password:
 *   post:
 *     summary: تغيير كلمة المرور
 *     tags: [Auth]
 *     security:
 *       - bearerAuth: []
 *     requestBody:
 *       required: true
 *       content:
 *         application/json:
 *           schema:
 *             $ref: '#/components/schemas/ChangePasswordRequest'
 *     responses:
 *       200:
 *         description: تم تغيير كلمة المرور بنجاح
 *       400:
 *         description: كلمة المرور الحالية غير صحيحة
 */
router.post(
  '/change-password',
  authenticate,
  validateRequest(validateChangePassword),
  AuthController.changePassword
);

/**
 * @swagger
 * /api/v1/auth/verify-email:
 *   post:
 *     summary: تأكيد البريد الإلكتروني
 *     tags: [Auth]
 *     requestBody:
 *       required: true
 *       content:
 *         application/json:
 *           schema:
 *             $ref: '#/components/schemas/VerifyEmailRequest'
 *     responses:
 *       200:
 *         description: تم تأكيد البريد الإلكتروني
 *       400:
 *         description: رمز التحقق غير صالح
 */
router.post(
  '/verify-email',
  validateRequest(validateVerifyEmail),
  AuthController.verifyEmail
);

/**
 * @swagger
 * /api/v1/auth/resend-otp:
 *   post:
 *     summary: إعادة إرسال رمز التحقق
 *     tags: [Auth]
 *     requestBody:
 *       required: true
 *       content:
 *         application/json:
 *           schema:
 *             $ref: '#/components/schemas/ResendOTPRequest'
 *     responses:
 *       200:
 *         description: تم إرسال رمز التحقق
 *       429:
 *         description: تجاوز عدد المحاولات المسموح
 */
router.post(
  '/resend-otp',
  otpRateLimiter,
  validateRequest(validateResendOTP),
  AuthController.resendOTP
);

/**
 * @swagger
 * /api/v1/auth/profile:
 *   get:
 *     summary: الحصول على معلومات المستخدم
 *     tags: [Auth]
 *     security:
 *       - bearerAuth: []
 *     responses:
 *       200:
 *         description: معلومات المستخدم
 *         content:
 *           application/json:
 *             schema:
 *               $ref: '#/components/schemas/UserProfile'
 *       401:
 *         description: غير مصرح
 */
router.get(
  '/profile',
  authenticate,
  AuthController.getProfile
);

/**
 * @swagger
 * /api/v1/auth/profile:
 *   put:
 *     summary: تحديث معلومات المستخدم
 *     tags: [Auth]
 *     security:
 *       - bearerAuth: []
 *     requestBody:
 *       required: true
 *       content:
 *         application/json:
 *           schema:
 *             $ref: '#/components/schemas/UpdateProfileRequest'
 *     responses:
 *       200:
 *         description: تم تحديث المعلومات بنجاح
 *       400:
 *         description: بيانات غير صالحة
 */
router.put(
  '/profile',
  authenticate,
  validateRequest(validateUpdateProfile),
  AuthController.updateProfile
);

export default router;