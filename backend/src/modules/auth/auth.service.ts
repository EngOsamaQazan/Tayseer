import { PrismaClient } from '@prisma/client';
import { Redis } from 'ioredis';
import { v4 as uuidv4 } from 'uuid';
import {
  generateAccessToken,
  generateRefreshToken,
  verifyToken,
  hashPassword,
  comparePassword,
  generateOTP,
  generateSecureToken
} from '../../utils/crypto.utils';
import { sendEmail } from '../../utils/email.utils';
import { logger } from '../../config/logger';
import { NotificationService } from '../../utils/notification.utils';
import { AppError } from '../../middleware/error.middleware';
import { RegisterInput, LoginInput } from './auth.validation';

const prisma = new PrismaClient();
const redis = new Redis(process.env.REDIS_URL!);
const notificationService = new NotificationService();

export class AuthService {
  /**
   * إنشاء مستخدم جديد
   */
  static async createUser(data: RegisterInput) {
    // التحقق من وجود المستخدم
    const existingUser = await prisma.user.findUnique({
      where: { email: data.email }
    });

    if (existingUser) {
      throw new AppError('المستخدم موجود بالفعل', 409);
    }

    // التحقق من صحة المؤسسة
    const tenant = await prisma.tenant.findUnique({
      where: { id: data.tenantId }
    });

    if (!tenant || !tenant.isActive) {
      throw new AppError('المؤسسة غير موجودة أو غير نشطة', 404);
    }

    // تشفير كلمة المرور
    const hashedPassword = await hashPassword(data.password);

    // إنشاء المستخدم
    const user = await prisma.user.create({
      data: {
        id: uuidv4(),
        name: data.name,
        email: data.email,
        password: hashedPassword,
        phone: data.phone,
        tenantId: data.tenantId,
        role: data.role || 'viewer',
        permissions: data.permissions || [],
        isActive: true,
        isEmailVerified: false
      },
      select: {
        id: true,
        name: true,
        email: true,
        role: true,
        tenantId: true
      }
    });

    // إرسال بريد التحقق
    const otp = generateOTP();
    await this.saveOTP(user.id, otp, 'email_verification');
    
    await sendEmail({
      to: user.email,
      subject: 'تأكيد البريد الإلكتروني - منصة تيسير',
      template: 'verify-email',
      data: {
        name: user.name,
        otp
      }
    });

    // إرسال إشعار للمسؤولين
    await notificationService.send({
      tenantId: tenant.id,
      userId: tenant.ownerId,
      type: 'USER',
      title: 'مستخدم جديد',
      message: `تم تسجيل مستخدم جديد: ${user.name}`,
      priority: 'LOW',
      data: { userId: user.id }
    });

    logger.info('User created', {
      userId: user.id,
      tenantId: tenant.id,
      role: user.role
    });

    return user;
  }

  /**
   * تسجيل دخول المستخدم
   */
  static async authenticateUser(data: LoginInput, userAgent: string, ipAddress: string) {
    // البحث عن المستخدم
    const user = await prisma.user.findUnique({
      where: { email: data.email },
      include: {
        tenant: {
          select: {
            id: true,
            name: true,
            isActive: true,
            subscription: true
          }
        }
      }
    });

    if (!user) {
      throw new AppError('بيانات الدخول غير صحيحة', 401);
    }

    // التحقق من كلمة المرور
    const isPasswordValid = await comparePassword(data.password, user.password);
    if (!isPasswordValid) {
      // تسجيل محاولة فاشلة
      await this.logFailedAttempt(user.id, ipAddress);
      throw new AppError('بيانات الدخول غير صحيحة', 401);
    }

    // التحقق من حالة المستخدم
    if (!user.isActive) {
      throw new AppError('الحساب معطل', 403);
    }

    if (!user.tenant.isActive) {
      throw new AppError('المؤسسة معطلة', 403);
    }

    // إنشاء جلسة
    const sessionId = uuidv4();
    const { accessToken, payload: accessPayload } = generateAccessToken({
      userId: user.id,
      tenantId: user.tenantId,
      email: user.email,
      role: user.role,
      permissions: user.permissions,
      sessionId
    });

    const { refreshToken, payload: refreshPayload } = generateRefreshToken({
      userId: user.id,
      sessionId
    });

    // حفظ الجلسة في Redis
    const sessionData = {
      userId: user.id,
      tenantId: user.tenantId,
      userAgent,
      ipAddress,
      createdAt: new Date().toISOString(),
      expiresAt: new Date(accessPayload.exp! * 1000).toISOString()
    };

    const sessionTTL = data.rememberMe ? 30 * 24 * 60 * 60 : 24 * 60 * 60; // 30 أيام أو يوم واحد
    await redis.setex(`session:${sessionId}`, sessionTTL, JSON.stringify(sessionData));

    // تحديث آخر تسجيل دخول
    await prisma.user.update({
      where: { id: user.id },
      data: { lastLoginAt: new Date() }
    });

    // إنشاء سجل تسجيل الدخول
    await prisma.loginHistory.create({
      data: {
        id: uuidv4(),
        userId: user.id,
        tenantId: user.tenantId,
        ipAddress,
        userAgent,
        status: 'success',
        sessionId
      }
    });

    logger.info('User authenticated', {
      userId: user.id,
      sessionId,
      rememberMe: data.rememberMe
    });

    return {
      user: {
        id: user.id,
        name: user.name,
        email: user.email,
        role: user.role,
        permissions: user.permissions,
        tenant: user.tenant,
        isEmailVerified: user.isEmailVerified
      },
      tokens: {
        accessToken,
        refreshToken,
        expiresIn: accessPayload.exp! - Math.floor(Date.now() / 1000)
      }
    };
  }

  /**
   * تجديد رمز الوصول
   */
  static async refreshAccessToken(refreshToken: string) {
    try {
      // التحقق من رمز التحديث
      const payload = verifyToken(refreshToken);
      
      if (!payload.userId || !payload.sessionId) {
        throw new AppError('رمز التحديث غير صالح', 401);
      }

      // التحقق من الجلسة
      const sessionData = await redis.get(`session:${payload.sessionId}`);
      if (!sessionData) {
        throw new AppError('الجلسة منتهية', 401);
      }

      const session = JSON.parse(sessionData);

      // الحصول على معلومات المستخدم
      const user = await prisma.user.findUnique({
        where: { id: payload.userId },
        include: {
          tenant: {
            select: {
              id: true,
              isActive: true
            }
          }
        }
      });

      if (!user || !user.isActive || !user.tenant.isActive) {
        throw new AppError('المستخدم غير مصرح', 401);
      }

      // إنشاء رمز وصول جديد
      const { accessToken, payload: newPayload } = generateAccessToken({
        userId: user.id,
        tenantId: user.tenantId,
        email: user.email,
        role: user.role,
        permissions: user.permissions,
        sessionId: payload.sessionId
      });

      // تحديث وقت انتهاء الجلسة
      const newSessionData = {
        ...session,
        expiresAt: new Date(newPayload.exp! * 1000).toISOString()
      };

      await redis.setex(
        `session:${payload.sessionId}`,
        30 * 24 * 60 * 60,
        JSON.stringify(newSessionData)
      );

      logger.info('Token refreshed', {
        userId: user.id,
        sessionId: payload.sessionId
      });

      return {
        accessToken,
        expiresIn: newPayload.exp! - Math.floor(Date.now() / 1000)
      };

    } catch (error) {
      logger.error('Token refresh error', error);
      throw new AppError('فشل تجديد رمز الوصول', 401);
    }
  }

  /**
   * إنهاء الجلسة
   */
  static async terminateSession(sessionId: string, userId: string) {
    // حذف الجلسة من Redis
    await redis.del(`session:${sessionId}`);

    // تحديث سجل تسجيل الدخول
    await prisma.loginHistory.updateMany({
      where: {
        sessionId,
        userId,
        logoutAt: null
      },
      data: {
        logoutAt: new Date()
      }
    });

    logger.info('Session terminated', { sessionId, userId });
  }

  /**
   * إنهاء جميع الجلسات
   */
  static async terminateAllSessions(userId: string) {
    // البحث عن جميع الجلسات النشطة
    const sessions = await redis.keys(`session:*`);
    
    for (const sessionKey of sessions) {
      const sessionData = await redis.get(sessionKey);
      if (sessionData) {
        const session = JSON.parse(sessionData);
        if (session.userId === userId) {
          await redis.del(sessionKey);
        }
      }
    }

    // تحديث سجلات تسجيل الدخول
    await prisma.loginHistory.updateMany({
      where: {
        userId,
        logoutAt: null
      },
      data: {
        logoutAt: new Date()
      }
    });

    logger.info('All sessions terminated', { userId });
  }

  /**
   * حفظ رمز OTP
   */
  static async saveOTP(userId: string, otp: string, type: 'email_verification' | 'password_reset') {
    const key = `otp:${type}:${userId}`;
    await redis.setex(key, 900, JSON.stringify({ otp, createdAt: new Date().toISOString() })); // 15 دقيقة
  }

  /**
   * التحقق من رمز OTP
   */
  static async verifyOTP(userId: string, otp: string, type: 'email_verification' | 'password_reset') {
    const key = `otp:${type}:${userId}`;
    const data = await redis.get(key);

    if (!data) {
      throw new AppError('رمز التحقق منتهي الصلاحية', 400);
    }

    const { otp: savedOTP } = JSON.parse(data);

    if (savedOTP !== otp) {
      throw new AppError('رمز التحقق غير صحيح', 400);
    }

    // حذف OTP بعد الاستخدام
    await redis.del(key);

    return true;
  }

  /**
   * تسجيل محاولة دخول فاشلة
   */
  private static async logFailedAttempt(userId: string, ipAddress: string) {
    const key = `failed_login:${userId}`;
    const attempts = await redis.get(key);
    const count = attempts ? parseInt(attempts) + 1 : 1;

    await redis.setex(key, 3600, count.toString()); // ساعة واحدة

    // قفل الحساب بعد 5 محاولات فاشلة
    if (count >= 5) {
      await prisma.user.update({
        where: { id: userId },
        data: { 
          isActive: false,
          lockedAt: new Date(),
          lockedReason: 'محاولات دخول فاشلة متكررة'
        }
      });

      logger.warn('Account locked due to failed attempts', { userId, attempts: count });
    }

    // تسجيل المحاولة الفاشلة
    await prisma.loginHistory.create({
      data: {
        id: uuidv4(),
        userId,
        tenantId: (await prisma.user.findUnique({ where: { id: userId } }))!.tenantId,
        ipAddress,
        userAgent: '',
        status: 'failed'
      }
    });
  }

  /**
   * إنشاء رمز إعادة تعيين كلمة المرور
   */
  static async createPasswordResetToken(email: string) {
    const user = await prisma.user.findUnique({
      where: { email }
    });

    if (!user) {
      // لا نكشف عن عدم وجود المستخدم لأسباب أمنية
      return { message: 'تم إرسال تعليمات إعادة التعيين إلى بريدك الإلكتروني' };
    }

    // إنشاء رمز إعادة التعيين
    const resetToken = generateSecureToken();
    const hashedToken = await hashPassword(resetToken);

    // حفظ الرمز في Redis
    const key = `reset_token:${user.id}`;
    await redis.setex(key, 3600, JSON.stringify({
      token: hashedToken,
      createdAt: new Date().toISOString()
    })); // ساعة واحدة

    // إرسال البريد الإلكتروني
    await sendEmail({
      to: user.email,
      subject: 'إعادة تعيين كلمة المرور - منصة تيسير',
      template: 'reset-password',
      data: {
        name: user.name,
        resetLink: `${process.env.FRONTEND_URL}/auth/reset-password?token=${resetToken}&email=${email}`
      }
    });

    logger.info('Password reset token created', { userId: user.id });

    return { message: 'تم إرسال تعليمات إعادة التعيين إلى بريدك الإلكتروني' };
  }

  /**
   * إعادة تعيين كلمة المرور
   */
  static async resetPassword(token: string, newPassword: string, email: string) {
    const user = await prisma.user.findUnique({
      where: { email }
    });

    if (!user) {
      throw new AppError('رمز إعادة التعيين غير صالح', 400);
    }

    // التحقق من الرمز
    const key = `reset_token:${user.id}`;
    const data = await redis.get(key);

    if (!data) {
      throw new AppError('رمز إعادة التعيين منتهي الصلاحية', 400);
    }

    const { token: hashedToken } = JSON.parse(data);
    const isValidToken = await comparePassword(token, hashedToken);

    if (!isValidToken) {
      throw new AppError('رمز إعادة التعيين غير صالح', 400);
    }

    // تحديث كلمة المرور
    const hashedPassword = await hashPassword(newPassword);
    await prisma.user.update({
      where: { id: user.id },
      data: {
        password: hashedPassword,
        passwordChangedAt: new Date()
      }
    });

    // حذف الرمز
    await redis.del(key);

    // إنهاء جميع الجلسات
    await this.terminateAllSessions(user.id);

    // إرسال بريد تأكيد
    await sendEmail({
      to: user.email,
      subject: 'تم تغيير كلمة المرور - منصة تيسير',
      template: 'password-changed',
      data: {
        name: user.name,
        changedAt: new Date().toLocaleDateString('ar-SA')
      }
    });

    logger.info('Password reset completed', { userId: user.id });

    return { message: 'تم تغيير كلمة المرور بنجاح' };
  }

  /**
   * تغيير كلمة المرور
   */
  static async changePassword(userId: string, currentPassword: string, newPassword: string) {
    const user = await prisma.user.findUnique({
      where: { id: userId }
    });

    if (!user) {
      throw new AppError('المستخدم غير موجود', 404);
    }

    // التحقق من كلمة المرور الحالية
    const isPasswordValid = await comparePassword(currentPassword, user.password);
    if (!isPasswordValid) {
      throw new AppError('كلمة المرور الحالية غير صحيحة', 401);
    }

    // تحديث كلمة المرور
    const hashedPassword = await hashPassword(newPassword);
    await prisma.user.update({
      where: { id: userId },
      data: {
        password: hashedPassword,
        passwordChangedAt: new Date()
      }
    });

    // إرسال بريد تأكيد
    await sendEmail({
      to: user.email,
      subject: 'تم تغيير كلمة المرور - منصة تيسير',
      template: 'password-changed',
      data: {
        name: user.name,
        changedAt: new Date().toLocaleDateString('ar-SA')
      }
    });

    logger.info('Password changed', { userId });

    return { message: 'تم تغيير كلمة المرور بنجاح' };
  }

  /**
   * التحقق من البريد الإلكتروني
   */
  static async verifyEmail(email: string, otp: string) {
    const user = await prisma.user.findUnique({
      where: { email }
    });

    if (!user) {
      throw new AppError('المستخدم غير موجود', 404);
    }

    if (user.isEmailVerified) {
      return { message: 'البريد الإلكتروني مُحقق بالفعل' };
    }

    // التحقق من OTP
    await this.verifyOTP(user.id, otp, 'email_verification');

    // تحديث حالة التحقق
    await prisma.user.update({
      where: { id: user.id },
      data: {
        isEmailVerified: true,
        emailVerifiedAt: new Date()
      }
    });

    // إرسال إشعار
    await notificationService.send({
      userId: user.id,
      tenantId: user.tenantId,
      type: 'USER',
      title: 'تم تأكيد البريد الإلكتروني',
      message: 'تم تأكيد بريدك الإلكتروني بنجاح',
      priority: 'LOW'
    });

    logger.info('Email verified', { userId: user.id });

    return { message: 'تم تأكيد البريد الإلكتروني بنجاح' };
  }

  /**
   * إعادة إرسال رمز التحقق
   */
  static async resendVerificationCode(email: string, type: 'email_verification' | 'password_reset') {
    const user = await prisma.user.findUnique({
      where: { email }
    });

    if (!user) {
      throw new AppError('المستخدم غير موجود', 404);
    }

    // التحقق من معدل الإرسال
    const rateLimitKey = `otp_rate_limit:${type}:${user.id}`;
    const attempts = await redis.get(rateLimitKey);
    
    if (attempts && parseInt(attempts) >= 3) {
      throw new AppError('لقد تجاوزت الحد المسموح به، حاول مرة أخرى لاحقاً', 429);
    }

    // إنشاء وحفظ OTP جديد
    const otp = generateOTP();
    await this.saveOTP(user.id, otp, type);

    // تحديث عداد المحاولات
    const currentAttempts = attempts ? parseInt(attempts) : 0;
    await redis.setex(rateLimitKey, 3600, (currentAttempts + 1).toString());

    // إرسال البريد الإلكتروني
    const emailData = type === 'email_verification' ? {
      subject: 'تأكيد البريد الإلكتروني - منصة تيسير',
      template: 'verify-email',
      data: {
        name: user.name,
        otp
      }
    } : {
      subject: 'إعادة تعيين كلمة المرور - منصة تيسير',
      template: 'reset-password-otp',
      data: {
        name: user.name,
        otp
      }
    };

    await sendEmail({
      to: user.email,
      ...emailData
    });

    logger.info('Verification code resent', { userId: user.id, type });

    return { 
      message: 'تم إرسال رمز التحقق إلى بريدك الإلكتروني',
      attemptsRemaining: 3 - (currentAttempts + 1)
    };
  }

  /**
   * الحصول على الجلسات النشطة
   */
  static async getActiveSessions(userId: string) {
    const sessions = [];
    const sessionKeys = await redis.keys(`session:*`);

    for (const key of sessionKeys) {
      const sessionData = await redis.get(key);
      if (sessionData) {
        const session = JSON.parse(sessionData);
        if (session.userId === userId) {
          const sessionId = key.replace('session:', '');
          sessions.push({
            sessionId,
            ...session,
            isCurrent: false // سيتم تحديدها في Controller
          });
        }
      }
    }

    return sessions;
  }

  /**
   * الحصول على سجل تسجيل الدخول
   */
  static async getLoginHistory(userId: string, limit: number = 10) {
    const history = await prisma.loginHistory.findMany({
      where: { userId },
      orderBy: { createdAt: 'desc' },
      take: limit,
      select: {
        id: true,
        ipAddress: true,
        userAgent: true,
        status: true,
        createdAt: true,
        logoutAt: true
      }
    });

    return history;
  }
}