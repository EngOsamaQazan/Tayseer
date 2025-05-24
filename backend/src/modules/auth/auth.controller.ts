import { Request, Response } from 'express';
import bcrypt from 'bcrypt';
import jwt from 'jsonwebtoken';
import { v4 as uuidv4 } from 'uuid';
import { prisma } from '../../config/database';
import { redis } from '../../config/redis';
import { logger } from '../../config/logger';
import envConfig from '../../config/env.config';
import {
  sendSuccess,
  sendError,
  sendUnauthorized,
  sendValidationError,
  sendForbidden
} from '../../utils/response.utils';
import {
  hashPassword,
  comparePassword,
  generateToken,
  generateRefreshToken,
  generateOTP
} from '../../utils/crypto.utils';
import { sendEmail } from '../../utils/email.utils';
import { formatDateTime } from '../../utils/date.utils';
import { maskEmail, maskPhone } from '../../utils/format.utils';
import {
  validateLogin,
  validateRegister,
  validateForgotPassword,
  validateResetPassword,
  validateChangePassword,
  validateVerifyOTP,
  validateRefreshToken,
  validateVerifyEmail
} from './auth.validation';
import { AuthRequest } from '../../types/express';

const JWT_SECRET = envConfig.get('JWT_SECRET');
const JWT_EXPIRES_IN = envConfig.get('JWT_EXPIRES_IN');
const JWT_REFRESH_SECRET = envConfig.get('JWT_REFRESH_SECRET');
const JWT_REFRESH_EXPIRES_IN = envConfig.get('JWT_REFRESH_EXPIRES_IN');
const SESSION_EXPIRES_IN = envConfig.get('SESSION_TIMEOUT');

export class AuthController {
  /**
   * تسجيل مستخدم جديد
   */
  static async register(req: Request, res: Response) {
    try {
      const validation = validateRegister.safeParse(req.body);
      if (!validation.success) {
        return sendValidationError(res, validation.error.errors);
      }

      const { email, password, name, phone, tenantName, plan = 'basic' } = validation.data;

      // التحقق من وجود المستخدم
      const existingUser = await prisma.user.findUnique({
        where: { email }
      });

      if (existingUser) {
        return sendError(res, 'البريد الإلكتروني مستخدم بالفعل', 400);
      }

      // إنشاء المستأجر
      const tenant = await prisma.tenant.create({
        data: {
          id: uuidv4(),
          name: tenantName,
          plan,
          isActive: true,
          settings: {
            language: 'ar',
            timezone: 'Asia/Riyadh',
            currency: 'SAR',
            dateFormat: 'DD/MM/YYYY',
            enableNotifications: true
          }
        }
      });

      // تشفير كلمة المرور
      const hashedPassword = await hashPassword(password);

      // إنشاء المستخدم
      const user = await prisma.user.create({
        data: {
          id: uuidv4(),
          email,
          password: hashedPassword,
          name,
          phone,
          tenantId: tenant.id,
          role: 'ADMIN',
          isActive: true,
          isEmailVerified: false,
          settings: {
            language: 'ar',
            notifications: {
              email: true,
              sms: true,
              push: true
            }
          }
        }
      });

      // توليد OTP للتحقق من البريد الإلكتروني
      const otp = generateOTP();
      const otpExpiry = new Date(Date.now() + 15 * 60 * 1000); // 15 دقيقة

      // حفظ OTP في Redis
      await redis.setex(
        `otp:email:${user.id}`,
        900, // 15 دقيقة
        JSON.stringify({ otp, type: 'email_verification' })
      );

      // إرسال بريد التحقق
      await sendEmail({
        to: email,
        subject: 'تأكيد البريد الإلكتروني - منصة تيسير',
        template: 'email-verification',
        data: {
          name,
          otp,
          expiryTime: '15 دقيقة'
        }
      });

      // توليد رموز المصادقة
      const accessToken = generateToken({ userId: user.id, tenantId: tenant.id });
      const refreshToken = generateRefreshToken({ userId: user.id });

      // حفظ الجلسة في Redis
      const sessionId = uuidv4();
      await redis.setex(
        `session:${sessionId}`,
        SESSION_EXPIRES_IN,
        JSON.stringify({
          userId: user.id,
          tenantId: tenant.id,
          role: user.role,
          createdAt: new Date().toISOString()
        })
      );

      // تسجيل النشاط
      await prisma.auditLog.create({
        data: {
          id: uuidv4(),
          tenantId: tenant.id,
          userId: user.id,
          action: 'USER_REGISTERED',
          entity: 'user',
          entityId: user.id,
          details: {
            email,
            tenantName
          },
          ipAddress: req.ip || 'unknown',
          userAgent: req.headers['user-agent'] || 'unknown'
        }
      });

      logger.info('New user registered', {
        userId: user.id,
        tenantId: tenant.id,
        email: maskEmail(email)
      });

      return sendSuccess(res, {
        user: {
          id: user.id,
          email: user.email,
          name: user.name,
          role: user.role,
          isEmailVerified: user.isEmailVerified
        },
        tenant: {
          id: tenant.id,
          name: tenant.name,
          plan: tenant.plan
        },
        tokens: {
          accessToken,
          refreshToken
        },
        sessionId,
        message: 'تم التسجيل بنجاح. تم إرسال رمز التحقق إلى بريدك الإلكتروني'
      }, 201);

    } catch (error) {
      logger.error('Registration error', error);
      return sendError(res, 'حدث خطأ أثناء التسجيل');
    }
  }

  /**
   * تسجيل الدخول
   */
  static async login(req: Request, res: Response) {
    try {
      const validation = validateLogin.safeParse(req.body);
      if (!validation.success) {
        return sendValidationError(res, validation.error.errors);
      }

      const { email, password, rememberMe = false } = validation.data;

      // البحث عن المستخدم
      const user = await prisma.user.findUnique({
        where: { email },
        include: {
          tenant: true
        }
      });

      if (!user || !await comparePassword(password, user.password)) {
        return sendUnauthorized(res, 'البريد الإلكتروني أو كلمة المرور غير صحيحة');
      }

      // التحقق من حالة المستخدم
      if (!user.isActive) {
        return sendForbidden(res, 'الحساب معطل. يرجى التواصل مع الدعم الفني');
      }

      // التحقق من حالة المستأجر
      if (!user.tenant.isActive) {
        return sendForbidden(res, 'الاشتراك معطل. يرجى التواصل مع الدعم الفني');
      }

      // توليد رموز المصادقة
      const accessToken = generateToken({ 
        userId: user.id, 
        tenantId: user.tenantId,
        role: user.role 
      });
      const refreshToken = generateRefreshToken({ userId: user.id });

      // إدارة الجلسة
      const sessionId = uuidv4();
      const sessionExpiry = rememberMe ? 30 * 24 * 60 * 60 : SESSION_EXPIRES_IN; // 30 يوم أو الافتراضي
      
      await redis.setex(
        `session:${sessionId}`,
        sessionExpiry,
        JSON.stringify({
          userId: user.id,
          tenantId: user.tenantId,
          role: user.role,
          createdAt: new Date().toISOString(),
          rememberMe
        })
      );

      // تحديث آخر تسجيل دخول
      await prisma.user.update({
        where: { id: user.id },
        data: {
          lastLoginAt: new Date(),
          lastLoginIp: req.ip || 'unknown'
        }
      });

      // تسجيل النشاط
      await prisma.auditLog.create({
        data: {
          id: uuidv4(),
          tenantId: user.tenantId,
          userId: user.id,
          action: 'USER_LOGIN',
          entity: 'user',
          entityId: user.id,
          details: {
            email,
            rememberMe,
            loginMethod: 'password'
          },
          ipAddress: req.ip || 'unknown',
          userAgent: req.headers['user-agent'] || 'unknown'
        }
      });

      logger.info('User logged in', {
        userId: user.id,
        tenantId: user.tenantId,
        email: maskEmail(email)
      });

      return sendSuccess(res, {
        user: {
          id: user.id,
          email: user.email,
          name: user.name,
          role: user.role,
          isEmailVerified: user.isEmailVerified,
          avatar: user.avatar,
          lastLoginAt: user.lastLoginAt
        },
        tenant: {
          id: user.tenant.id,
          name: user.tenant.name,
          plan: user.tenant.plan,
          logo: user.tenant.logo
        },
        tokens: {
          accessToken,
          refreshToken
        },
        sessionId
      });

    } catch (error) {
      logger.error('Login error', error);
      return sendError(res, 'حدث خطأ أثناء تسجيل الدخول');
    }
  }

  /**
   * تسجيل الخروج
   */
  static async logout(req: AuthRequest, res: Response) {
    try {
      const sessionId = req.headers['x-session-id'] as string;
      const userId = req.user?.userId;

      if (sessionId) {
        // حذف الجلسة من Redis
        await redis.del(`session:${sessionId}`);
      }

      // تسجيل النشاط
      if (userId) {
        await prisma.auditLog.create({
          data: {
            id: uuidv4(),
            tenantId: req.user!.tenantId,
            userId,
            action: 'USER_LOGOUT',
            entity: 'user',
            entityId: userId,
            details: {},
            ipAddress: req.ip || 'unknown',
            userAgent: req.headers['user-agent'] || 'unknown'
          }
        });
      }

      logger.info('User logged out', {
        userId,
        sessionId
      });

      return sendSuccess(res, {
        message: 'تم تسجيل الخروج بنجاح'
      });

    } catch (error) {
      logger.error('Logout error', error);
      return sendError(res, 'حدث خطأ أثناء تسجيل الخروج');
    }
  }

  /**
   * تجديد رمز الوصول
   */
  static async refreshToken(req: Request, res: Response) {
    try {
      const validation = validateRefreshToken.safeParse(req.body);
      if (!validation.success) {
        return sendValidationError(res, validation.error.errors);
      }

      const { refreshToken } = validation.data;

      // التحقق من رمز التجديد
      let decoded: any;
      try {
        decoded = jwt.verify(refreshToken, JWT_REFRESH_SECRET);
      } catch (error) {
        return sendUnauthorized(res, 'رمز التجديد غير صالح');
      }

      // البحث عن المستخدم
      const user = await prisma.user.findUnique({
        where: { id: decoded.userId },
        include: { tenant: true }
      });

      if (!user || !user.isActive || !user.tenant.isActive) {
        return sendUnauthorized(res, 'المستخدم غير موجود أو غير نشط');
      }

      // توليد رمز وصول جديد
      const accessToken = generateToken({
        userId: user.id,
        tenantId: user.tenantId,
        role: user.role
      });

      logger.info('Token refreshed', {
        userId: user.id
      });

      return sendSuccess(res, {
        accessToken
      });

    } catch (error) {
      logger.error('Token refresh error', error);
      return sendError(res, 'حدث خطأ أثناء تجديد رمز الوصول');
    }
  }

  /**
   * نسيت كلمة المرور
   */
  static async forgotPassword(req: Request, res: Response) {
    try {
      const validation = validateForgotPassword.safeParse(req.body);
      if (!validation.success) {
        return sendValidationError(res, validation.error.errors);
      }

      const { email } = validation.data;

      // البحث عن المستخدم
      const user = await prisma.user.findUnique({
        where: { email }
      });

      if (!user) {
        // لأسباب أمنية، نرد برسالة عامة
        return sendSuccess(res, {
          message: 'إذا كان البريد الإلكتروني موجود، سيتم إرسال رمز إعادة التعيين'
        });
      }

      // توليد رمز إعادة التعيين
      const resetToken = generateOTP();
      const resetExpiry = new Date(Date.now() + 30 * 60 * 1000); // 30 دقيقة

      // حفظ الرمز في Redis
      await redis.setex(
        `reset:${user.id}`,
        1800, // 30 دقيقة
        JSON.stringify({
          token: resetToken,
          email,
          attempts: 0
        })
      );

      // إرسال البريد الإلكتروني
      await sendEmail({
        to: email,
        subject: 'إعادة تعيين كلمة المرور - منصة تيسير',
        template: 'reset-password',
        data: {
          name: user.name,
          resetToken,
          expiryTime: '30 دقيقة'
        }
      });

      // تسجيل النشاط
      await prisma.auditLog.create({
        data: {
          id: uuidv4(),
          tenantId: user.tenantId,
          userId: user.id,
          action: 'PASSWORD_RESET_REQUESTED',
          entity: 'user',
          entityId: user.id,
          details: {
            email: maskEmail(email)
          },
          ipAddress: req.ip || 'unknown',
          userAgent: req.headers['user-agent'] || 'unknown'
        }
      });

      logger.info('Password reset requested', {
        userId: user.id,
        email: maskEmail(email)
      });

      return sendSuccess(res, {
        message: 'تم إرسال رمز إعادة التعيين إلى بريدك الإلكتروني'
      });

    } catch (error) {
      logger.error('Forgot password error', error);
      return sendError(res, 'حدث خطأ أثناء معالجة الطلب');
    }
  }

  /**
   * إعادة تعيين كلمة المرور
   */
  static async resetPassword(req: Request, res: Response) {
    try {
      const validation = validateResetPassword.safeParse(req.body);
      if (!validation.success) {
        return sendValidationError(res, validation.error.errors);
      }

      const { email, token, newPassword } = validation.data;

      // البحث عن المستخدم
      const user = await prisma.user.findUnique({
        where: { email }
      });

      if (!user) {
        return sendError(res, 'رمز إعادة التعيين غير صالح', 400);
      }

      // التحقق من الرمز
      const resetData = await redis.get(`reset:${user.id}`);
      if (!resetData) {
        return sendError(res, 'رمز إعادة التعيين منتهي الصلاحية', 400);
      }

      const { token: savedToken, attempts } = JSON.parse(resetData);

      // التحقق من عدد المحاولات
      if (attempts >= 3) {
        await redis.del(`reset:${user.id}`);
        return sendError(res, 'تم تجاوز عدد المحاولات المسموح', 400);
      }

      if (savedToken !== token) {
        // زيادة عدد المحاولات
        await redis.setex(
          `reset:${user.id}`,
          await redis.ttl(`reset:${user.id}`),
          JSON.stringify({
            token: savedToken,
            email,
            attempts: attempts + 1
          })
        );
        return sendError(res, 'رمز إعادة التعيين غير صحيح', 400);
      }

      // تشفير كلمة المرور الجديدة
      const hashedPassword = await hashPassword(newPassword);

      // تحديث كلمة المرور
      await prisma.user.update({
        where: { id: user.id },
        data: {
          password: hashedPassword,
          passwordChangedAt: new Date()
        }
      });

      // حذف رمز إعادة التعيين
      await redis.del(`reset:${user.id}`);

      // إنهاء جميع الجلسات النشطة
      const sessions = await redis.keys(`session:*`);
      for (const session of sessions) {
        const sessionData = await redis.get(session);
        if (sessionData) {
          const { userId } = JSON.parse(sessionData);
          if (userId === user.id) {
            await redis.del(session);
          }
        }
      }

      // تسجيل النشاط
      await prisma.auditLog.create({
        data: {
          id: uuidv4(),
          tenantId: user.tenantId,
          userId: user.id,
          action: 'PASSWORD_RESET',
          entity: 'user',
          entityId: user.id,
          details: {},
          ipAddress: req.ip || 'unknown',
          userAgent: req.headers['user-agent'] || 'unknown'
        }
      });

      // إرسال بريد تأكيد
      await sendEmail({
        to: email,
        subject: 'تم تغيير كلمة المرور - منصة تيسير',
        template: 'password-changed',
        data: {
          name: user.name,
          changedAt: formatDateTime(new Date())
        }
      });

      logger.info('Password reset successful', {
        userId: user.id
      });

      return sendSuccess(res, {
        message: 'تم تغيير كلمة المرور بنجاح'
      });

    } catch (error) {
      logger.error('Reset password error', error);
      return sendError(res, 'حدث خطأ أثناء إعادة تعيين كلمة المرور');
    }
  }

  /**
   * تغيير كلمة المرور
   */
  static async changePassword(req: AuthRequest, res: Response) {
    try {
      const validation = validateChangePassword.safeParse(req.body);
      if (!validation.success) {
        return sendValidationError(res, validation.error.errors);
      }

      const { currentPassword, newPassword } = validation.data;
      const userId = req.user!.userId;

      // البحث عن المستخدم
      const user = await prisma.user.findUnique({
        where: { id: userId }
      });

      if (!user) {
        return sendUnauthorized(res, 'المستخدم غير موجود');
      }

      // التحقق من كلمة المرور الحالية
      if (!await comparePassword(currentPassword, user.password)) {
        return sendError(res, 'كلمة المرور الحالية غير صحيحة', 400);
      }

      // تشفير كلمة المرور الجديدة
      const hashedPassword = await hashPassword(newPassword);

      // تحديث كلمة المرور
      await prisma.user.update({
        where: { id: userId },
        data: {
          password: hashedPassword,
          passwordChangedAt: new Date()
        }
      });

      // تسجيل النشاط
      await prisma.auditLog.create({
        data: {
          id: uuidv4(),
          tenantId: req.user!.tenantId,
          userId,
          action: 'PASSWORD_CHANGED',
          entity: 'user',
          entityId: userId,
          details: {},
          ipAddress: req.ip || 'unknown',
          userAgent: req.headers['user-agent'] || 'unknown'
        }
      });

      // إرسال بريد تأكيد
      await sendEmail({
        to: user.email,
        subject: 'تم تغيير كلمة المرور - منصة تيسير',
        template: 'password-changed',
        data: {
          name: user.name,
          changedAt: formatDateTime(new Date())
        }
      });

      logger.info('Password changed', {
        userId
      });

      return sendSuccess(res, {
        message: 'تم تغيير كلمة المرور بنجاح'
      });

    } catch (error) {
      logger.error('Change password error', error);
      return sendError(res, 'حدث خطأ أثناء تغيير كلمة المرور');
    }
  }

  /**
   * التحقق من البريد الإلكتروني
   */
  static async verifyEmail(req: Request, res: Response) {
    try {
      const validation = validateVerifyEmail.safeParse(req.body);
      if (!validation.success) {
        return sendValidationError(res, validation.error.errors);
      }

      const { email, otp } = validation.data;

      // البحث عن المستخدم
      const user = await prisma.user.findUnique({
        where: { email }
      });

      if (!user) {
        return sendError(res, 'المستخدم غير موجود', 404);
      }

      if (user.isEmailVerified) {
        return sendSuccess(res, {
          message: 'البريد الإلكتروني محقق بالفعل'
        });
      }

      // التحقق من OTP
      const otpData = await redis.get(`otp:email:${user.id}`);
      if (!otpData) {
        return sendError(res, 'رمز التحقق منتهي الصلاحية', 400);
      }

      const { otp: savedOtp } = JSON.parse(otpData);
      if (savedOtp !== otp) {
        return sendError(res, 'رمز التحقق غير صحيح', 400);
      }

      // تحديث حالة التحقق
      await prisma.user.update({
        where: { id: user.id },
        data: {
          isEmailVerified: true,
          emailVerifiedAt: new Date()
        }
      });

      // حذف OTP
      await redis.del(`otp:email:${user.id}`);

      // تسجيل النشاط
      await prisma.auditLog.create({
        data: {
          id: uuidv4(),
          tenantId: user.tenantId,
          userId: user.id,
          action: 'EMAIL_VERIFIED',
          entity: 'user',
          entityId: user.id,
          details: {
            email
          },
          ipAddress: req.ip || 'unknown',
          userAgent: req.headers['user-agent'] || 'unknown'
        }
      });

      logger.info('Email verified', {
        userId: user.id,
        email: maskEmail(email)
      });

      return sendSuccess(res, {
        message: 'تم التحقق من البريد الإلكتروني بنجاح'
      });

    } catch (error) {
      logger.error('Email verification error', error);
      return sendError(res, 'حدث خطأ أثناء التحقق من البريد الإلكتروني');
    }
  }

  /**
   * إعادة إرسال OTP
   */
  static async resendOTP(req: Request, res: Response) {
    try {
      const { email, type = 'email_verification' } = req.body;

      // البحث عن المستخدم
      const user = await prisma.user.findUnique({
        where: { email }
      });

      if (!user) {
        return sendError(res, 'المستخدم غير موجود', 404);
      }

      // التحقق من معدل الإرسال
      const rateLimitKey = `otp:rate:${user.id}`;
      const attempts = await redis.get(rateLimitKey);
      if (attempts && parseInt(attempts) >= 3) {
        return sendError(res, 'تم تجاوز الحد الأقصى لإرسال رمز التحقق. حاول مرة أخرى بعد ساعة', 429);
      }

      // توليد OTP جديد
      const otp = generateOTP();

      // حفظ OTP
      await redis.setex(
        `otp:${type}:${user.id}`,
        900, // 15 دقيقة
        JSON.stringify({ otp, email })
      );

      // زيادة عداد المحاولات
      await redis.setex(rateLimitKey, 3600, (parseInt(attempts || '0') + 1).toString());

      // إرسال البريد الإلكتروني
      const template = type === 'email_verification' ? 'verify-email' : 'reset-password';
      const subject = type === 'email_verification' 
        ? 'تأكيد البريد الإلكتروني - منصة تيسير'
        : 'إعادة تعيين كلمة المرور - منصة تيسير';

      await sendEmail({
        to: email,
        subject,
        template,
        data: {
          name: user.name,
          otp,
          expiryTime: '15 دقيقة'
        }
      });

      logger.info('OTP resent', {
        userId: user.id,
        type,
        email: maskEmail(email)
      });

      return sendSuccess(res, {
        message: 'تم إرسال رمز التحقق بنجاح'
      });

    } catch (error) {
      logger.error('Resend OTP error', error);
      return sendError(res, 'حدث خطأ أثناء إرسال رمز التحقق');
    }
  }

  /**
   * الحصول على معلومات المستخدم الحالي
   */
  static async getProfile(req: AuthRequest, res: Response) {
    try {
      const userId = req.user!.userId;

      const user = await prisma.user.findUnique({
        where: { id: userId },
        select: {
          id: true,
          name: true,
          email: true,
          phone: true,
          avatar: true,
          role: true,
          permissions: true,
          isActive: true,
          isEmailVerified: true,
          emailVerifiedAt: true,
          lastLoginAt: true,
          createdAt: true,
          tenant: {
            select: {
              id: true,
              name: true,
              logo: true,
              subdomain: true,
              isActive: true
            }
          }
        }
      });

      if (!user) {
        return sendUnauthorized(res, 'المستخدم غير موجود');
      }

      return sendSuccess(res, user);

    } catch (error) {
      logger.error('Get profile error', error);
      return sendError(res, 'حدث خطأ أثناء جلب معلومات المستخدم');
    }
  }

  /**
   * تحديث معلومات المستخدم
   */
  static async updateProfile(req: AuthRequest, res: Response) {
    try {
      const validation = validateUpdateProfile.safeParse(req.body);
      if (!validation.success) {
        return sendValidationError(res, validation.error.errors);
      }

      const userId = req.user!.userId;
      const updateData = validation.data;

      // تحديث المستخدم
      const updatedUser = await prisma.user.update({
        where: { id: userId },
        data: updateData,
        select: {
          id: true,
          name: true,
          email: true,
          phone: true,
          avatar: true,
          role: true,
          permissions: true
        }
      });

      // تسجيل النشاط
      await prisma.auditLog.create({
        data: {
          id: uuidv4(),
          tenantId: req.user!.tenantId,
          userId,
          action: 'PROFILE_UPDATED',
          entity: 'user',
          entityId: userId,
          details: updateData,
          ipAddress: req.ip || 'unknown',
          userAgent: req.headers['user-agent'] || 'unknown'
        }
      });

      logger.info('Profile updated', {
        userId,
        updates: Object.keys(updateData)
      });

      return sendSuccess(res, {
        message: 'تم تحديث معلومات المستخدم بنجاح',
        user: updatedUser
      });

    } catch (error) {
      logger.error('Update profile error', error);
      return sendError(res, 'حدث خطأ أثناء تحديث معلومات المستخدم');
    }
  }
}