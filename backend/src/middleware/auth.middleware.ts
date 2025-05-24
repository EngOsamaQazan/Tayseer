import { Request, Response, NextFunction } from 'express';
import jwt from 'jsonwebtoken';
import { prisma } from '../config/database';
import { logger } from '../config/logger';
import { SessionService } from '../config/redis';

// تعريف واجهة للمستخدم في الطلب
export interface AuthRequest extends Request {
  user?: {
    id: string;
    email: string;
    tenantId: string;
    role: string;
    permissions: string[];
  };
  session?: {
    id: string;
    deviceInfo?: any;
  };
}

// واجهة للـ JWT payload
interface JWTPayload {
  userId: string;
  email: string;
  tenantId: string;
  sessionId: string;
  iat?: number;
  exp?: number;
}

// Middleware للمصادقة
export const authMiddleware = async (
  req: AuthRequest,
  res: Response,
  next: NextFunction
): Promise<void> => {
  try {
    // استخراج الرمز من الرأس
    const authHeader = req.headers.authorization;
    
    if (!authHeader || !authHeader.startsWith('Bearer ')) {
      res.status(401).json({
        success: false,
        message: 'رمز المصادقة مفقود'
      });
      return;
    }

    const token = authHeader.substring(7);

    // التحقق من الرمز
    const decoded = jwt.verify(
      token,
      process.env.JWT_SECRET || 'default-secret'
    ) as JWTPayload;

    // التحقق من الجلسة في Redis
    const session = await SessionService.get(decoded.sessionId);
    
    if (!session) {
      res.status(401).json({
        success: false,
        message: 'الجلسة غير صالحة أو منتهية'
      });
      return;
    }

    // الحصول على معلومات المستخدم من قاعدة البيانات
    const user = await prisma.user.findUnique({
      where: { id: decoded.userId },
      include: {
        role: {
          include: {
            permissions: true
          }
        },
        tenant: true
      }
    });

    if (!user || !user.isActive) {
      res.status(401).json({
        success: false,
        message: 'المستخدم غير موجود أو غير نشط'
      });
      return;
    }

    // التحقق من حالة المستأجر
    if (!user.tenant?.isActive) {
      res.status(403).json({
        success: false,
        message: 'حساب الشركة غير نشط'
      });
      return;
    }

    // تحديث وقت آخر نشاط للجلسة
    await SessionService.refresh(decoded.sessionId);

    // إضافة معلومات المستخدم للطلب
    req.user = {
      id: user.id,
      email: user.email,
      tenantId: user.tenantId,
      role: user.role.name,
      permissions: user.role.permissions.map(p => p.name)
    };

    req.session = {
      id: decoded.sessionId,
      deviceInfo: session.deviceInfo
    };

    next();
  } catch (error) {
    if (error instanceof jwt.TokenExpiredError) {
      res.status(401).json({
        success: false,
        message: 'انتهت صلاحية رمز المصادقة',
        code: 'TOKEN_EXPIRED'
      });
      return;
    }

    if (error instanceof jwt.JsonWebTokenError) {
      res.status(401).json({
        success: false,
        message: 'رمز مصادقة غير صالح'
      });
      return;
    }

    logger.error('خطأ في middleware المصادقة:', error);
    res.status(500).json({
      success: false,
      message: 'خطأ في المصادقة'
    });
  }
};

// Middleware للتحقق من الصلاحيات
export const requirePermission = (permission: string) => {
  return (req: AuthRequest, res: Response, next: NextFunction): void => {
    if (!req.user) {
      res.status(401).json({
        success: false,
        message: 'غير مصرح'
      });
      return;
    }

    if (!req.user.permissions.includes(permission)) {
      logger.warn(`محاولة وصول غير مصرحة: المستخدم ${req.user.id} حاول الوصول إلى ${permission}`);
      res.status(403).json({
        success: false,
        message: 'ليس لديك الصلاحية للقيام بهذا الإجراء'
      });
      return;
    }

    next();
  };
};

// Middleware للتحقق من الأدوار
export const requireRole = (roles: string[]) => {
  return (req: AuthRequest, res: Response, next: NextFunction): void => {
    if (!req.user) {
      res.status(401).json({
        success: false,
        message: 'غير مصرح'
      });
      return;
    }

    if (!roles.includes(req.user.role)) {
      logger.warn(`محاولة وصول غير مصرحة: المستخدم ${req.user.id} بدور ${req.user.role} حاول الوصول إلى مورد محدد للأدوار: ${roles.join(', ')}`);
      res.status(403).json({
        success: false,
        message: 'دورك لا يسمح بالوصول إلى هذا المورد'
      });
      return;
    }

    next();
  };
};

// Middleware اختياري للمصادقة (لا يتطلب تسجيل دخول)
export const optionalAuth = async (
  req: AuthRequest,
  res: Response,
  next: NextFunction
): Promise<void> => {
  try {
    const authHeader = req.headers.authorization;
    
    if (!authHeader || !authHeader.startsWith('Bearer ')) {
      // لا يوجد رمز، متابعة بدون مستخدم
      next();
      return;
    }

    const token = authHeader.substring(7);
    const decoded = jwt.verify(
      token,
      process.env.JWT_SECRET || 'default-secret'
    ) as JWTPayload;

    const session = await SessionService.get(decoded.sessionId);
    
    if (session) {
      const user = await prisma.user.findUnique({
        where: { id: decoded.userId },
        include: {
          role: {
            include: {
              permissions: true
            }
          }
        }
      });

      if (user && user.isActive) {
        req.user = {
          id: user.id,
          email: user.email,
          tenantId: user.tenantId,
          role: user.role.name,
          permissions: user.role.permissions.map(p => p.name)
        };
      }
    }
  } catch (error) {
    // تجاهل الأخطاء في المصادقة الاختيارية
    logger.debug('خطأ في المصادقة الاختيارية:', error);
  }

  next();
};