import { Request, Response, NextFunction } from 'express';
import { PrismaClient } from '@prisma/client';
import { v4 as uuidv4 } from 'uuid';
import { CustomerService } from './customer.service';
import { apiResponse } from '../../utils/api.utils';
import { AppError } from '../../utils/errors';
import { logger } from '../../services/logger.service';
import { auditService } from '../../services/audit.service';
import { notificationService } from '../../services/notification.service';
import { redis } from '../../services/redis.service';
import { uploadService } from '../../services/upload.service';
import {
  createCustomerSchema,
  updateCustomerSchema,
  customerIdSchema,
  customerSearchSchema,
  customerDocumentSchema,
  customerNoteSchema,
  customerTagSchema
} from './customer.validation';

const prisma = new PrismaClient();

export class CustomerController {
  /**
   * إنشاء عميل جديد
   */
  static async create(req: Request, res: Response, next: NextFunction) {
    try {
      const validatedData = createCustomerSchema.parse(req.body);
      const tenantId = req.user!.tenantId;
      const userId = req.user!.id;

      // التحقق من وجود عميل بنفس البريد الإلكتروني أو الهاتف
      const existingCustomer = await prisma.customer.findFirst({
        where: {
          tenantId,
          OR: [
            { email: validatedData.email },
            { phone: validatedData.phone }
          ]
        }
      });

      if (existingCustomer) {
        throw new AppError('عميل بنفس البريد الإلكتروني أو رقم الهاتف موجود بالفعل', 409);
      }

      // إنشاء العميل
      const customer = await CustomerService.createCustomer({
        ...validatedData,
        tenantId,
        createdBy: userId
      });

      // تسجيل النشاط
      await auditService.log({
        action: 'customer.create',
        entityType: 'customer',
        entityId: customer.id,
        userId,
        tenantId,
        metadata: {
          customerName: customer.name,
          customerType: customer.type
        }
      });

      // إرسال إشعار
      await notificationService.send({
        userId,
        tenantId,
        type: 'USER',
        title: 'عميل جديد',
        message: `تم إضافة العميل ${customer.name} بنجاح`,
        priority: 'LOW'
      });

      logger.info('Customer created', { customerId: customer.id, userId });

      return apiResponse(res, 201, 'تم إنشاء العميل بنجاح', customer);
    } catch (error) {
      next(error);
    }
  }

  /**
   * الحصول على قائمة العملاء
   */
  static async list(req: Request, res: Response, next: NextFunction) {
    try {
      const tenantId = req.user!.tenantId;
      const { page = 1, limit = 10, search, type, status, sortBy = 'createdAt', sortOrder = 'desc' } = req.query;

      // التحقق من الصفحات المخزنة مؤقتاً
      const cacheKey = `customers:${tenantId}:${page}:${limit}:${search || ''}:${type || ''}:${status || ''}:${sortBy}:${sortOrder}`;
      const cached = await redis.get(cacheKey);

      if (cached) {
        return apiResponse(res, 200, 'تم جلب قائمة العملاء بنجاح', JSON.parse(cached));
      }

      const customers = await CustomerService.getCustomers({
        tenantId,
        page: Number(page),
        limit: Number(limit),
        search: search as string,
        type: type as any,
        status: status as any,
        sortBy: sortBy as string,
        sortOrder: sortOrder as 'asc' | 'desc'
      });

      // تخزين مؤقت لمدة 5 دقائق
      await redis.setex(cacheKey, 300, JSON.stringify(customers));

      return apiResponse(res, 200, 'تم جلب قائمة العملاء بنجاح', customers);
    } catch (error) {
      next(error);
    }
  }

  /**
   * الحصول على تفاصيل عميل
   */
  static async getById(req: Request, res: Response, next: NextFunction) {
    try {
      const { id } = customerIdSchema.parse(req.params);
      const tenantId = req.user!.tenantId;

      const customer = await CustomerService.getCustomerById(id, tenantId);

      if (!customer) {
        throw new AppError('العميل غير موجود', 404);
      }

      return apiResponse(res, 200, 'تم جلب بيانات العميل بنجاح', customer);
    } catch (error) {
      next(error);
    }
  }

  /**
   * تحديث بيانات عميل
   */
  static async update(req: Request, res: Response, next: NextFunction) {
    try {
      const { id } = customerIdSchema.parse(req.params);
      const validatedData = updateCustomerSchema.parse(req.body);
      const tenantId = req.user!.tenantId;
      const userId = req.user!.id;

      // التحقق من وجود العميل
      const existingCustomer = await prisma.customer.findFirst({
        where: { id, tenantId }
      });

      if (!existingCustomer) {
        throw new AppError('العميل غير موجود', 404);
      }

      // التحقق من عدم تكرار البريد الإلكتروني أو الهاتف
      if (validatedData.email || validatedData.phone) {
        const duplicate = await prisma.customer.findFirst({
          where: {
            tenantId,
            id: { not: id },
            OR: [
              validatedData.email ? { email: validatedData.email } : {},
              validatedData.phone ? { phone: validatedData.phone } : {}
            ]
          }
        });

        if (duplicate) {
          throw new AppError('عميل آخر يستخدم نفس البريد الإلكتروني أو رقم الهاتف', 409);
        }
      }

      // تحديث العميل
      const updatedCustomer = await CustomerService.updateCustomer(id, {
        ...validatedData,
        tenantId,
        updatedBy: userId
      });

      // مسح الذاكرة المؤقتة
      const cacheKeys = await redis.keys(`customers:${tenantId}:*`);
      if (cacheKeys.length > 0) {
        await redis.del(...cacheKeys);
      }

      // تسجيل النشاط
      await auditService.log({
        action: 'customer.update',
        entityType: 'customer',
        entityId: id,
        userId,
        tenantId,
        metadata: {
          changes: validatedData
        }
      });

      logger.info('Customer updated', { customerId: id, userId });

      return apiResponse(res, 200, 'تم تحديث بيانات العميل بنجاح', updatedCustomer);
    } catch (error) {
      next(error);
    }
  }

  /**
   * حذف عميل
   */
  static async delete(req: Request, res: Response, next: NextFunction) {
    try {
      const { id } = customerIdSchema.parse(req.params);
      const tenantId = req.user!.tenantId;
      const userId = req.user!.id;

      // التحقق من وجود العميل
      const customer = await prisma.customer.findFirst({
        where: { id, tenantId }
      });

      if (!customer) {
        throw new AppError('العميل غير موجود', 404);
      }

      // التحقق من عدم وجود عقود أو معاملات نشطة
      const activeContracts = await prisma.contract.count({
        where: {
          customerId: id,
          status: { in: ['ACTIVE', 'PENDING'] }
        }
      });

      if (activeContracts > 0) {
        throw new AppError('لا يمكن حذف العميل لوجود عقود نشطة', 400);
      }

      // حذف العميل (soft delete)
      await CustomerService.deleteCustomer(id, tenantId);

      // مسح الذاكرة المؤقتة
      const cacheKeys = await redis.keys(`customers:${tenantId}:*`);
      if (cacheKeys.length > 0) {
        await redis.del(...cacheKeys);
      }

      // تسجيل النشاط
      await auditService.log({
        action: 'customer.delete',
        entityType: 'customer',
        entityId: id,
        userId,
        tenantId,
        metadata: {
          customerName: customer.name
        }
      });

      logger.info('Customer deleted', { customerId: id, userId });

      return apiResponse(res, 200, 'تم حذف العميل بنجاح');
    } catch (error) {
      next(error);
    }
  }

  /**
   * البحث في العملاء
   */
  static async search(req: Request, res: Response, next: NextFunction) {
    try {
      const validatedQuery = customerSearchSchema.parse(req.query);
      const tenantId = req.user!.tenantId;

      const results = await CustomerService.searchCustomers({
        ...validatedQuery,
        tenantId
      });

      return apiResponse(res, 200, 'تم البحث بنجاح', results);
    } catch (error) {
      next(error);
    }
  }

  /**
   * رفع مستند للعميل
   */
  static async uploadDocument(req: Request, res: Response, next: NextFunction) {
    try {
      const { id } = customerIdSchema.parse(req.params);
      const { title, description, type } = customerDocumentSchema.parse(req.body);
      const tenantId = req.user!.tenantId;
      const userId = req.user!.id;

      // التحقق من وجود العميل
      const customer = await prisma.customer.findFirst({
        where: { id, tenantId }
      });

      if (!customer) {
        throw new AppError('العميل غير موجود', 404);
      }

      // التحقق من وجود الملف
      if (!req.file) {
        throw new AppError('يجب رفع ملف', 400);
      }

      // رفع الملف
      const uploadResult = await uploadService.uploadFile(req.file, {
        folder: `customers/${id}/documents`,
        allowedTypes: ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'],
        maxSize: 10 * 1024 * 1024 // 10MB
      });

      // حفظ معلومات المستند
      const document = await prisma.customerDocument.create({
        data: {
          id: uuidv4(),
          customerId: id,
          title,
          description,
          type,
          fileUrl: uploadResult.url,
          fileName: uploadResult.fileName,
          fileSize: uploadResult.size,
          mimeType: uploadResult.mimeType,
          uploadedBy: userId
        }
      });

      // تسجيل النشاط
      await auditService.log({
        action: 'customer.document.upload',
        entityType: 'customer',
        entityId: id,
        userId,
        tenantId,
        metadata: {
          documentId: document.id,
          documentTitle: title,
          documentType: type
        }
      });

      logger.info('Customer document uploaded', { customerId: id, documentId: document.id, userId });

      return apiResponse(res, 201, 'تم رفع المستند بنجاح', document);
    } catch (error) {
      next(error);
    }
  }

  /**
   * الحصول على مستندات العميل
   */
  static async getDocuments(req: Request, res: Response, next: NextFunction) {
    try {
      const { id } = customerIdSchema.parse(req.params);
      const tenantId = req.user!.tenantId;

      // التحقق من وجود العميل
      const customer = await prisma.customer.findFirst({
        where: { id, tenantId }
      });

      if (!customer) {
        throw new AppError('العميل غير موجود', 404);
      }

      const documents = await prisma.customerDocument.findMany({
        where: {
          customerId: id,
          deletedAt: null
        },
        include: {
          uploadedByUser: {
            select: {
              id: true,
              name: true
            }
          }
        },
        orderBy: { createdAt: 'desc' }
      });

      return apiResponse(res, 200, 'تم جلب المستندات بنجاح', documents);
    } catch (error) {
      next(error);
    }
  }

  /**
   * حذف مستند
   */
  static async deleteDocument(req: Request, res: Response, next: NextFunction) {
    try {
      const { id, documentId } = req.params;
      const tenantId = req.user!.tenantId;
      const userId = req.user!.id;

      // التحقق من وجود العميل
      const customer = await prisma.customer.findFirst({
        where: { id, tenantId }
      });

      if (!customer) {
        throw new AppError('العميل غير موجود', 404);
      }

      // التحقق من وجود المستند
      const document = await prisma.customerDocument.findFirst({
        where: {
          id: documentId,
          customerId: id,
          deletedAt: null
        }
      });

      if (!document) {
        throw new AppError('المستند غير موجود', 404);
      }

      // حذف المستند (soft delete)
      await prisma.customerDocument.update({
        where: { id: documentId },
        data: { deletedAt: new Date() }
      });

      // حذف الملف من التخزين
      await uploadService.deleteFile(document.fileUrl);

      // تسجيل النشاط
      await auditService.log({
        action: 'customer.document.delete',
        entityType: 'customer',
        entityId: id,
        userId,
        tenantId,
        metadata: {
          documentId: document.id,
          documentTitle: document.title
        }
      });

      logger.info('Customer document deleted', { customerId: id, documentId, userId });

      return apiResponse(res, 200, 'تم حذف المستند بنجاح');
    } catch (error) {
      next(error);
    }
  }

  /**
   * إضافة ملاحظة للعميل
   */
  static async addNote(req: Request, res: Response, next: NextFunction) {
    try {
      const { id } = customerIdSchema.parse(req.params);
      const { content, isPrivate } = customerNoteSchema.parse(req.body);
      const tenantId = req.user!.tenantId;
      const userId = req.user!.id;

      // التحقق من وجود العميل
      const customer = await prisma.customer.findFirst({
        where: { id, tenantId }
      });

      if (!customer) {
        throw new AppError('العميل غير موجود', 404);
      }

      // إنشاء الملاحظة
      const note = await prisma.customerNote.create({
        data: {
          id: uuidv4(),
          customerId: id,
          content,
          isPrivate: isPrivate || false,
          createdBy: userId
        },
        include: {
          createdByUser: {
            select: {
              id: true,
              name: true
            }
          }
        }
      });

      // تسجيل النشاط
      await auditService.log({
        action: 'customer.note.add',
        entityType: 'customer',
        entityId: id,
        userId,
        tenantId,
        metadata: {
          noteId: note.id,
          isPrivate: note.isPrivate
        }
      });

      logger.info('Customer note added', { customerId: id, noteId: note.id, userId });

      return apiResponse(res, 201, 'تم إضافة الملاحظة بنجاح', note);
    } catch (error) {
      next(error);
    }
  }

  /**
   * الحصول على ملاحظات العميل
   */
  static async getNotes(req: Request, res: Response, next: NextFunction) {
    try {
      const { id } = customerIdSchema.parse(req.params);
      const tenantId = req.user!.tenantId;
      const userId = req.user!.id;

      // التحقق من وجود العميل
      const customer = await prisma.customer.findFirst({
        where: { id, tenantId }
      });

      if (!customer) {
        throw new AppError('العميل غير موجود', 404);
      }

      // جلب الملاحظات (مع مراعاة الملاحظات الخاصة)
      const notes = await prisma.customerNote.findMany({
        where: {
          customerId: id,
          OR: [
            { isPrivate: false },
            { createdBy: userId }
          ]
        },
        include: {
          createdByUser: {
            select: {
              id: true,
              name: true
            }
          }
        },
        orderBy: { createdAt: 'desc' }
      });

      return apiResponse(res, 200, 'تم جلب الملاحظات بنجاح', notes);
    } catch (error) {
      next(error);
    }
  }

  /**
   * إضافة أو إزالة وسم للعميل
   */
  static async manageTags(req: Request, res: Response, next: NextFunction) {
    try {
      const { id } = customerIdSchema.parse(req.params);
      const { tags, action } = customerTagSchema.parse(req.body);
      const tenantId = req.user!.tenantId;
      const userId = req.user!.id;

      // التحقق من وجود العميل
      const customer = await prisma.customer.findFirst({
        where: { id, tenantId }
      });

      if (!customer) {
        throw new AppError('العميل غير موجود', 404);
      }

      let updatedTags: string[] = customer.tags || [];

      if (action === 'add') {
        // إضافة الوسوم الجديدة
        updatedTags = [...new Set([...updatedTags, ...tags])];
      } else if (action === 'remove') {
        // إزالة الوسوم
        updatedTags = updatedTags.filter(tag => !tags.includes(tag));
      } else if (action === 'replace') {
        // استبدال جميع الوسوم
        updatedTags = tags;
      }

      // تحديث الوسوم
      const updatedCustomer = await prisma.customer.update({
        where: { id },
        data: { tags: updatedTags }
      });

      // مسح الذاكرة المؤقتة
      const cacheKeys = await redis.keys(`customers:${tenantId}:*`);
      if (cacheKeys.length > 0) {
        await redis.del(...cacheKeys);
      }

      // تسجيل النشاط
      await auditService.log({
        action: 'customer.tags.update',
        entityType: 'customer',
        entityId: id,
        userId,
        tenantId,
        metadata: {
          action,
          tags,
          updatedTags
        }
      });

      logger.info('Customer tags updated', { customerId: id, action, userId });

      return apiResponse(res, 200, 'تم تحديث الوسوم بنجاح', { tags: updatedTags });
    } catch (error) {
      next(error);
    }
  }

  /**
   * الحصول على إحصائيات العميل
   */
  static async getStatistics(req: Request, res: Response, next: NextFunction) {
    try {
      const { id } = customerIdSchema.parse(req.params);
      const tenantId = req.user!.tenantId;

      // التحقق من وجود العميل
      const customer = await prisma.customer.findFirst({
        where: { id, tenantId }
      });

      if (!customer) {
        throw new AppError('العميل غير موجود', 404);
      }

      const statistics = await CustomerService.getCustomerStatistics(id, tenantId);

      return apiResponse(res, 200, 'تم جلب الإحصائيات بنجاح', statistics);
    } catch (error) {
      next(error);
    }
  }

  /**
   * الحصول على سجل نشاطات العميل
   */
  static async getActivityLog(req: Request, res: Response, next: NextFunction) {
    try {
      const { id } = customerIdSchema.parse(req.params);
      const tenantId = req.user!.tenantId;
      const { page = 1, limit = 20 } = req.query;

      // التحقق من وجود العميل
      const customer = await prisma.customer.findFirst({
        where: { id, tenantId }
      });

      if (!customer) {
        throw new AppError('العميل غير موجود', 404);
      }

      const activities = await auditService.getEntityLogs({
        entityType: 'customer',
        entityId: id,
        tenantId,
        page: Number(page),
        limit: Number(limit)
      });

      return apiResponse(res, 200, 'تم جلب سجل النشاطات بنجاح', activities);
    } catch (error) {
      next(error);
    }
  }

  /**
   * تصدير بيانات العملاء
   */
  static async export(req: Request, res: Response, next: NextFunction) {
    try {
      const tenantId = req.user!.tenantId;
      const { format = 'xlsx', filters } = req.query;

      const exportData = await CustomerService.exportCustomers({
        tenantId,
        format: format as 'xlsx' | 'csv',
        filters: filters ? JSON.parse(filters as string) : {}
      });

      // تحديد نوع المحتوى حسب الصيغة
      const contentType = format === 'xlsx' 
        ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        : 'text/csv';

      res.setHeader('Content-Type', contentType);
      res.setHeader('Content-Disposition', `attachment; filename=customers-${new Date().toISOString().split('T')[0]}.${format}`);
      res.send(exportData);
    } catch (error) {
      next(error);
    }
  }
}