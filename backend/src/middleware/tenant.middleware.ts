import { Response, NextFunction } from 'express';
import { prisma } from '../config/database';
import { logger } from '../config/logger';
import { AuthRequest } from './auth.middleware';

// Middleware للتحقق من المستأجر وإضافة السياق
export const tenantMiddleware = async (
  req: AuthRequest,
  res: Response,
  next: NextFunction
): Promise<void> => {
  try {
    // التحقق من وجود معلومات المستخدم
    if (!req.user || !req.user.tenantId) {
      res.status(401).json({
        success: false,
        message: 'معلومات المستأجر مفقودة'
      });
      return;
    }

    // الحصول على معلومات المستأجر
    const tenant = await prisma.tenant.findUnique({
      where: { id: req.user.tenantId },
      select: {
        id: true,
        name: true,
        subdomain: true,
        isActive: true,
        settings: true,
        subscription: {
          select: {
            plan: true,
            status: true,
            endDate: true,
            features: true
          }
        }
      }
    });

    if (!tenant) {
      res.status(404).json({
        success: false,
        message: 'المستأجر غير موجود'
      });
      return;
    }

    // التحقق من حالة المستأجر
    if (!tenant.isActive) {
      res.status(403).json({
        success: false,
        message: 'حساب الشركة معطل',
        code: 'TENANT_INACTIVE'
      });
      return;
    }

    // التحقق من الاشتراك
    if (tenant.subscription) {
      if (tenant.subscription.status !== 'active') {
        res.status(403).json({
          success: false,
          message: 'الاشتراك غير نشط',
          code: 'SUBSCRIPTION_INACTIVE'
        });
        return;
      }

      // التحقق من انتهاء الاشتراك
      if (tenant.subscription.endDate && new Date(tenant.subscription.endDate) < new Date()) {
        res.status(403).json({
          success: false,
          message: 'انتهى الاشتراك',
          code: 'SUBSCRIPTION_EXPIRED'
        });
        return;
      }
    }

    // إضافة معلومات المستأجر للطلب
    (req as any).tenant = {
      id: tenant.id,
      name: tenant.name,
      subdomain: tenant.subdomain,
      settings: tenant.settings || {},
      features: tenant.subscription?.features || []
    };

    // تعيين سياق المستأجر لـ Prisma (Row Level Security)
    // يمكن استخدام هذا لتطبيق عزل البيانات تلقائياً
    (req as any).prisma = prisma.$extends({
      query: {
        $allModels: {
          async $allOperations({ operation, model, args, query }) {
            // تطبيق فلتر المستأجر تلقائياً للعمليات
            if (['findUnique', 'findFirst', 'findMany', 'count', 'aggregate', 'groupBy'].includes(operation)) {
              args.where = {
                ...args.where,
                tenantId: tenant.id
              };
            }

            // إضافة tenantId تلقائياً عند الإنشاء
            if (operation === 'create') {
              args.data = {
                ...args.data,
                tenantId: tenant.id
              };
            }

            // إضافة tenantId تلقائياً عند الإنشاء المتعدد
            if (operation === 'createMany') {
              if (Array.isArray(args.data)) {
                args.data = args.data.map(item => ({
                  ...item,
                  tenantId: tenant.id
                }));
              } else {
                args.data = {
                  ...args.data,
                  tenantId: tenant.id
                };
              }
            }

            // التحقق من tenantId عند التحديث والحذف
            if (['update', 'updateMany', 'delete', 'deleteMany'].includes(operation)) {
              args.where = {
                ...args.where,
                tenantId: tenant.id
              };
            }

            return query(args);
          }
        }
      }
    });

    next();
  } catch (error) {
    logger.error('خطأ في middleware المستأجر:', error);
    res.status(500).json({
      success: false,
      message: 'خطأ في معالجة طلب المستأجر'
    });
  }
};

// Middleware للتحقق من ميزة معينة في اشتراك المستأجر
export const requireFeature = (feature: string) => {
  return (req: AuthRequest, res: Response, next: NextFunction): void => {
    const tenant = (req as any).tenant;
    
    if (!tenant) {
      res.status(500).json({
        success: false,
        message: 'معلومات المستأجر مفقودة'
      });
      return;
    }

    if (!tenant.features.includes(feature)) {
      logger.warn(`المستأجر ${tenant.id} حاول الوصول إلى ميزة غير متاحة: ${feature}`);
      res.status(403).json({
        success: false,
        message: 'هذه الميزة غير متاحة في خطة اشتراكك الحالية',
        code: 'FEATURE_NOT_AVAILABLE',
        requiredFeature: feature
      });
      return;
    }

    next();
  };
};

// Middleware للتحقق من حدود الاستخدام
export const checkUsageLimit = (resource: string) => {
  return async (req: AuthRequest, res: Response, next: NextFunction): Promise<void> => {
    try {
      const tenant = (req as any).tenant;
      
      if (!tenant) {
        res.status(500).json({
          success: false,
          message: 'معلومات المستأجر مفقودة'
        });
        return;
      }

      // الحصول على حدود الاستخدام من قاعدة البيانات
      const usage = await prisma.tenantUsage.findUnique({
        where: {
          tenantId_resource: {
            tenantId: tenant.id,
            resource: resource
          }
        }
      });

      if (usage && usage.current >= usage.limit) {
        res.status(429).json({
          success: false,
          message: `تم تجاوز حد استخدام ${resource}`,
          code: 'USAGE_LIMIT_EXCEEDED',
          resource: resource,
          current: usage.current,
          limit: usage.limit
        });
        return;
      }

      next();
    } catch (error) {
      logger.error('خطأ في التحقق من حدود الاستخدام:', error);
      next(); // السماح بالمتابعة في حالة الخطأ
    }
  };
};