import { Request, Response, NextFunction } from 'express';
import { ContractService } from './contract.service';
import { contractValidation } from './contract.validation';
import { ApiError } from '../../utils/ApiError';
import { ApiResponse } from '../../utils/ApiResponse';
import { logger } from '../../utils/logger';
import { auditLogUtil } from '../../utils/auditLog.util';
import { notificationUtil } from '../../utils/notification.util';
import { cacheUtil } from '../../utils/cache.util';
import { uploadUtil } from '../../utils/upload.util';
import { prisma } from '../../config/database';
import { AuthenticatedRequest } from '../../types/auth.types';

export class ContractController {
  /**
   * إنشاء عقد جديد
   */
  async create(req: AuthenticatedRequest, res: Response, next: NextFunction) {
    try {
      const validatedData = contractValidation.createContractSchema.parse(req.body);
      const { id: userId, tenantId } = req.user!;

      // التحقق من وجود العميل
      const customer = await prisma.customer.findFirst({
        where: {
          id: validatedData.customerId,
          tenantId,
          deletedAt: null
        }
      });

      if (!customer) {
        throw new ApiError(404, 'العميل غير موجود');
      }

      // إنشاء العقد
      const contract = await ContractService.createContract(
        validatedData,
        userId,
        tenantId
      );

      // تسجيل العملية
      await auditLogUtil.log({
        action: 'CREATE_CONTRACT',
        entityType: 'contract',
        entityId: contract.id,
        userId,
        tenantId,
        metadata: {
          contractNumber: contract.contractNumber,
          customerId: contract.customerId,
          type: contract.type,
          totalAmount: contract.totalAmount
        }
      });

      // إرسال إشعار
      await notificationUtil.sendNotification({
        userId,
        tenantId,
        type: 'CONTRACT_CREATED',
        title: 'تم إنشاء عقد جديد',
        message: `تم إنشاء العقد رقم ${contract.contractNumber} بنجاح`,
        metadata: {
          contractId: contract.id,
          contractNumber: contract.contractNumber
        }
      });

      res.status(201).json(
        new ApiResponse(
          201,
          contract,
          'تم إنشاء العقد بنجاح'
        )
      );
    } catch (error) {
      next(error);
    }
  }

  /**
   * الحصول على قائمة العقود
   */
  async list(req: AuthenticatedRequest, res: Response, next: NextFunction) {
    try {
      const validatedQuery = contractValidation.contractListSchema.parse(req.query);
      const { tenantId } = req.user!;

      // محاولة الحصول على البيانات من الكاش
      const cacheKey = cacheUtil.generateKey('contracts', tenantId, JSON.stringify(validatedQuery));
      const cached = await cacheUtil.get(cacheKey);

      if (cached) {
        return res.json(
          new ApiResponse(
            200,
            cached,
            'تم جلب العقود بنجاح (من الكاش)'
          )
        );
      }

      // الحصول على العقود من قاعدة البيانات
      const result = await ContractService.listContracts(validatedQuery, tenantId);

      // حفظ في الكاش
      await cacheUtil.set(cacheKey, result, 300); // 5 دقائق

      res.json(
        new ApiResponse(
          200,
          result,
          'تم جلب العقود بنجاح'
        )
      );
    } catch (error) {
      next(error);
    }
  }

  /**
   * الحصول على تفاصيل عقد محدد
   */
  async getById(req: AuthenticatedRequest, res: Response, next: NextFunction) {
    try {
      const { id } = req.params;
      const { tenantId } = req.user!;

      // محاولة الحصول من الكاش
      const cacheKey = cacheUtil.generateKey('contract', tenantId, id);
      const cached = await cacheUtil.get(cacheKey);

      if (cached) {
        return res.json(
          new ApiResponse(
            200,
            cached,
            'تم جلب تفاصيل العقد بنجاح (من الكاش)'
          )
        );
      }

      // الحصول من قاعدة البيانات
      const contract = await ContractService.getContractById(id, tenantId);

      if (!contract) {
        throw new ApiError(404, 'العقد غير موجود');
      }

      // حفظ في الكاش
      await cacheUtil.set(cacheKey, contract, 600); // 10 دقائق

      res.json(
        new ApiResponse(
          200,
          contract,
          'تم جلب تفاصيل العقد بنجاح'
        )
      );
    } catch (error) {
      next(error);
    }
  }

  /**
   * تحديث عقد
   */
  async update(req: AuthenticatedRequest, res: Response, next: NextFunction) {
    try {
      const { id } = req.params;
      const validatedData = contractValidation.updateContractSchema.parse(req.body);
      const { id: userId, tenantId } = req.user!;

      // التحقق من وجود العقد
      const existingContract = await prisma.contract.findFirst({
        where: {
          id,
          tenantId,
          deletedAt: null
        }
      });

      if (!existingContract) {
        throw new ApiError(404, 'العقد غير موجود');
      }

      // التحقق من حالة العقد
      if (existingContract.status === 'cancelled' || existingContract.status === 'completed') {
        throw new ApiError(400, 'لا يمكن تعديل عقد ملغي أو مكتمل');
      }

      // تحديث العقد
      const updatedContract = await ContractService.updateContract(
        id,
        validatedData,
        userId,
        tenantId
      );

      // إبطال الكاش
      await cacheUtil.deletePattern(
        cacheUtil.generateKey('contract*', tenantId, '*')
      );

      // تسجيل العملية
      await auditLogUtil.log({
        action: 'UPDATE_CONTRACT',
        entityType: 'contract',
        entityId: id,
        userId,
        tenantId,
        metadata: {
          contractNumber: updatedContract.contractNumber,
          changes: validatedData
        }
      });

      res.json(
        new ApiResponse(
          200,
          updatedContract,
          'تم تحديث العقد بنجاح'
        )
      );
    } catch (error) {
      next(error);
    }
  }

  /**
   * حذف عقد (حذف منطقي)
   */
  async delete(req: AuthenticatedRequest, res: Response, next: NextFunction) {
    try {
      const { id } = req.params;
      const { id: userId, tenantId } = req.user!;

      // التحقق من وجود العقد
      const contract = await prisma.contract.findFirst({
        where: {
          id,
          tenantId,
          deletedAt: null
        },
        include: {
          invoices: {
            where: { deletedAt: null }
          }
        }
      });

      if (!contract) {
        throw new ApiError(404, 'العقد غير موجود');
      }

      // التحقق من عدم وجود فواتير مرتبطة
      if (contract.invoices.length > 0) {
        throw new ApiError(400, 'لا يمكن حذف عقد مرتبط بفواتير');
      }

      // حذف العقد
      await ContractService.deleteContract(id, userId, tenantId);

      // إبطال الكاش
      await cacheUtil.deletePattern(
        cacheUtil.generateKey('contract*', tenantId, '*')
      );

      // تسجيل العملية
      await auditLogUtil.log({
        action: 'DELETE_CONTRACT',
        entityType: 'contract',
        entityId: id,
        userId,
        tenantId,
        metadata: {
          contractNumber: contract.contractNumber
        }
      });

      res.json(
        new ApiResponse(
          200,
          null,
          'تم حذف العقد بنجاح'
        )
      );
    } catch (error) {
      next(error);
    }
  }

  /**
   * تفعيل عقد
   */
  async activate(req: AuthenticatedRequest, res: Response, next: NextFunction) {
    try {
      const { id } = req.params;
      const { id: userId, tenantId } = req.user!;

      const contract = await ContractService.activateContract(id, userId, tenantId);

      // إبطال الكاش
      await cacheUtil.deletePattern(
        cacheUtil.generateKey('contract*', tenantId, '*')
      );

      // تسجيل العملية
      await auditLogUtil.log({
        action: 'ACTIVATE_CONTRACT',
        entityType: 'contract',
        entityId: id,
        userId,
        tenantId,
        metadata: {
          contractNumber: contract.contractNumber,
          status: contract.status
        }
      });

      // إرسال إشعار
      await notificationUtil.sendNotification({
        userId,
        tenantId,
        type: 'CONTRACT_ACTIVATED',
        title: 'تم تفعيل العقد',
        message: `تم تفعيل العقد رقم ${contract.contractNumber}`,
        metadata: {
          contractId: contract.id,
          contractNumber: contract.contractNumber
        }
      });

      res.json(
        new ApiResponse(
          200,
          contract,
          'تم تفعيل العقد بنجاح'
        )
      );
    } catch (error) {
      next(error);
    }
  }

  /**
   * إلغاء عقد
   */
  async cancel(req: AuthenticatedRequest, res: Response, next: NextFunction) {
    try {
      const { id } = req.params;
      const validatedData = contractValidation.cancelContractSchema.parse(req.body);
      const { id: userId, tenantId } = req.user!;

      const contract = await ContractService.cancelContract(
        id,
        validatedData.reason,
        userId,
        tenantId
      );

      // إبطال الكاش
      await cacheUtil.deletePattern(
        cacheUtil.generateKey('contract*', tenantId, '*')
      );

      // تسجيل العملية
      await auditLogUtil.log({
        action: 'CANCEL_CONTRACT',
        entityType: 'contract',
        entityId: id,
        userId,
        tenantId,
        metadata: {
          contractNumber: contract.contractNumber,
          reason: validatedData.reason
        }
      });

      // إرسال إشعار
      await notificationUtil.sendNotification({
        userId,
        tenantId,
        type: 'CONTRACT_CANCELLED',
        title: 'تم إلغاء العقد',
        message: `تم إلغاء العقد رقم ${contract.contractNumber}`,
        metadata: {
          contractId: contract.id,
          contractNumber: contract.contractNumber,
          reason: validatedData.reason
        }
      });

      res.json(
        new ApiResponse(
          200,
          contract,
          'تم إلغاء العقد بنجاح'
        )
      );
    } catch (error) {
      next(error);
    }
  }

  /**
   * تجديد عقد
   */
  async renew(req: AuthenticatedRequest, res: Response, next: NextFunction) {
    try {
      const { id } = req.params;
      const validatedData = contractValidation.renewContractSchema.parse(req.body);
      const { id: userId, tenantId } = req.user!;

      const newContract = await ContractService.renewContract(
        id,
        validatedData,
        userId,
        tenantId
      );

      // إبطال الكاش
      await cacheUtil.deletePattern(
        cacheUtil.generateKey('contract*', tenantId, '*')
      );

      // تسجيل العملية
      await auditLogUtil.log({
        action: 'RENEW_CONTRACT',
        entityType: 'contract',
        entityId: newContract.id,
        userId,
        tenantId,
        metadata: {
          originalContractId: id,
          newContractNumber: newContract.contractNumber,
          newEndDate: validatedData.newEndDate
        }
      });

      // إرسال إشعار
      await notificationUtil.sendNotification({
        userId,
        tenantId,
        type: 'CONTRACT_RENEWED',
        title: 'تم تجديد العقد',
        message: `تم تجديد العقد رقم ${newContract.contractNumber}`,
        metadata: {
          contractId: newContract.id,
          contractNumber: newContract.contractNumber
        }
      });

      res.json(
        new ApiResponse(
          201,
          newContract,
          'تم تجديد العقد بنجاح'
        )
      );
    } catch (error) {
      next(error);
    }
  }

  /**
   * رفع مرفق للعقد
   */
  async uploadAttachment(req: AuthenticatedRequest, res: Response, next: NextFunction) {
    try {
      const { id } = req.params;
      const { id: userId, tenantId } = req.user!;
      const validatedData = contractValidation.uploadAttachmentSchema.parse(req.body);

      // التحقق من وجود العقد
      const contract = await prisma.contract.findFirst({
        where: {
          id,
          tenantId,
          deletedAt: null
        }
      });

      if (!contract) {
        throw new ApiError(404, 'العقد غير موجود');
      }

      // التحقق من وجود الملف
      if (!req.file) {
        throw new ApiError(400, 'الملف مطلوب');
      }

      // رفع الملف
      const fileUrl = await uploadUtil.uploadFile(req.file, {
        folder: `contracts/${id}`,
        allowedTypes: ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'],
        maxSize: 10 * 1024 * 1024 // 10MB
      });

      // حفظ المرفق في قاعدة البيانات
      const attachment = await prisma.contractAttachment.create({
        data: {
          contractId: id,
          title: validatedData.title,
          description: validatedData.description,
          type: validatedData.type,
          fileUrl,
          fileName: req.file.originalname,
          fileSize: req.file.size,
          uploadedById: userId,
          tenantId
        }
      });

      // تسجيل العملية
      await auditLogUtil.log({
        action: 'UPLOAD_CONTRACT_ATTACHMENT',
        entityType: 'contract',
        entityId: id,
        userId,
        tenantId,
        metadata: {
          attachmentId: attachment.id,
          fileName: req.file.originalname,
          type: validatedData.type
        }
      });

      res.json(
        new ApiResponse(
          201,
          attachment,
          'تم رفع المرفق بنجاح'
        )
      );
    } catch (error) {
      next(error);
    }
  }

  /**
   * الحصول على مرفقات العقد
   */
  async getAttachments(req: AuthenticatedRequest, res: Response, next: NextFunction) {
    try {
      const { id } = req.params;
      const { tenantId } = req.user!;

      // التحقق من وجود العقد
      const contract = await prisma.contract.findFirst({
        where: {
          id,
          tenantId,
          deletedAt: null
        }
      });

      if (!contract) {
        throw new ApiError(404, 'العقد غير موجود');
      }

      // الحصول على المرفقات
      const attachments = await prisma.contractAttachment.findMany({
        where: {
          contractId: id,
          deletedAt: null
        },
        include: {
          uploadedBy: {
            select: {
              id: true,
              name: true,
              email: true
            }
          }
        },
        orderBy: {
          createdAt: 'desc'
        }
      });

      res.json(
        new ApiResponse(
          200,
          attachments,
          'تم جلب المرفقات بنجاح'
        )
      );
    } catch (error) {
      next(error);
    }
  }

  /**
   * حذف مرفق
   */
  async deleteAttachment(req: AuthenticatedRequest, res: Response, next: NextFunction) {
    try {
      const { id, attachmentId } = req.params;
      const { id: userId, tenantId } = req.user!;

      // التحقق من وجود المرفق
      const attachment = await prisma.contractAttachment.findFirst({
        where: {
          id: attachmentId,
          contractId: id,
          tenantId,
          deletedAt: null
        }
      });

      if (!attachment) {
        throw new ApiError(404, 'المرفق غير موجود');
      }

      // حذف الملف من التخزين
      await uploadUtil.deleteFile(attachment.fileUrl);

      // حذف منطقي للمرفق
      await prisma.contractAttachment.update({
        where: { id: attachmentId },
        data: {
          deletedAt: new Date(),
          deletedById: userId
        }
      });

      // تسجيل العملية
      await auditLogUtil.log({
        action: 'DELETE_CONTRACT_ATTACHMENT',
        entityType: 'contract',
        entityId: id,
        userId,
        tenantId,
        metadata: {
          attachmentId,
          fileName: attachment.fileName
        }
      });

      res.json(
        new ApiResponse(
          200,
          null,
          'تم حذف المرفق بنجاح'
        )
      );
    } catch (error) {
      next(error);
    }
  }

  /**
   * إضافة بند للعقد
   */
  async addItem(req: AuthenticatedRequest, res: Response, next: NextFunction) {
    try {
      const { id } = req.params;
      const validatedData = contractValidation.contractItemSchema.parse(req.body);
      const { id: userId, tenantId } = req.user!;

      // التحقق من وجود العقد
      const contract = await prisma.contract.findFirst({
        where: {
          id,
          tenantId,
          deletedAt: null
        }
      });

      if (!contract) {
        throw new ApiError(404, 'العقد غير موجود');
      }

      // التحقق من حالة العقد
      if (contract.status !== 'draft') {
        throw new ApiError(400, 'لا يمكن إضافة بنود إلا للعقود في حالة المسودة');
      }

      // إضافة البند
      const item = await ContractService.addContractItem(
        id,
        validatedData,
        userId,
        tenantId
      );

      // إبطال الكاش
      await cacheUtil.delete(
        cacheUtil.generateKey('contract', tenantId, id)
      );

      // تسجيل العملية
      await auditLogUtil.log({
        action: 'ADD_CONTRACT_ITEM',
        entityType: 'contract',
        entityId: id,
        userId,
        tenantId,
        metadata: {
          itemId: item.id,
          itemName: item.name
        }
      });

      res.json(
        new ApiResponse(
          201,
          item,
          'تم إضافة البند بنجاح'
        )
      );
    } catch (error) {
      next(error);
    }
  }

  /**
   * تحديث بند في العقد
   */
  async updateItem(req: AuthenticatedRequest, res: Response, next: NextFunction) {
    try {
      const { id, itemId } = req.params;
      const validatedData = contractValidation.contractItemSchema.parse(req.body);
      const { id: userId, tenantId } = req.user!;

      // التحقق من وجود البند
      const item = await prisma.contractItem.findFirst({
        where: {
          id: itemId,
          contractId: id,
          contract: {
            tenantId,
            deletedAt: null
          }
        }
      });

      if (!item) {
        throw new ApiError(404, 'البند غير موجود');
      }

      // تحديث البند
      const updatedItem = await ContractService.updateContractItem(
        itemId,
        validatedData,
        userId,
        tenantId
      );

      // إبطال الكاش
      await cacheUtil.delete(
        cacheUtil.generateKey('contract', tenantId, id)
      );

      // تسجيل العملية
      await auditLogUtil.log({
        action: 'UPDATE_CONTRACT_ITEM',
        entityType: 'contract',
        entityId: id,
        userId,
        tenantId,
        metadata: {
          itemId,
          changes: validatedData
        }
      });

      res.json(
        new ApiResponse(
          200,
          updatedItem,
          'تم تحديث البند بنجاح'
        )
      );
    } catch (error) {
      next(error);
    }
  }

  /**
   * حذف بند من العقد
   */
  async deleteItem(req: AuthenticatedRequest, res: Response, next: NextFunction) {
    try {
      const { id, itemId } = req.params;
      const { id: userId, tenantId } = req.user!;

      await ContractService.deleteContractItem(itemId, userId, tenantId);

      // إبطال الكاش
      await cacheUtil.delete(
        cacheUtil.generateKey('contract', tenantId, id)
      );

      // تسجيل العملية
      await auditLogUtil.log({
        action: 'DELETE_CONTRACT_ITEM',
        entityType: 'contract',
        entityId: id,
        userId,
        tenantId,
        metadata: { itemId }
      });

      res.json(
        new ApiResponse(
          200,
          null,
          'تم حذف البند بنجاح'
        )
      );
    } catch (error) {
      next(error);
    }
  }

  /**
   * الحصول على إحصائيات العقود
   */
  async getStatistics(req: AuthenticatedRequest, res: Response, next: NextFunction) {
    try {
      const { tenantId } = req.user!;
      const validatedQuery = contractValidation.contractStatisticsSchema.parse(req.query);

      const statistics = await ContractService.getContractStatistics(
        tenantId,
        validatedQuery
      );

      res.json(
        new ApiResponse(
          200,
          statistics,
          'تم جلب الإحصائيات بنجاح'
        )
      );
    } catch (error) {
      next(error);
    }
  }

  /**
   * الحصول على الجدول الزمني للعقد
   */
  async getTimeline(req: AuthenticatedRequest, res: Response, next: NextFunction) {
    try {
      const { id } = req.params;
      const { tenantId } = req.user!;

      const timeline = await ContractService.getContractTimeline(id, tenantId);

      res.json(
        new ApiResponse(
          200,
          timeline,
          'تم جلب الجدول الزمني بنجاح'
        )
      );
    } catch (error) {
      next(error);
    }
  }

  /**
   * تصدير العقود
   */
  async export(req: AuthenticatedRequest, res: Response, next: NextFunction) {
    try {
      const { tenantId } = req.user!;
      const validatedQuery = contractValidation.exportContractsSchema.parse(req.query);

      const buffer = await ContractService.exportContracts(
        validatedQuery,
        tenantId
      );

      // تحديد نوع المحتوى حسب الصيغة
      const contentType = validatedQuery.format === 'xlsx'
        ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        : 'text/csv;charset=utf-8';

      const fileName = `contracts_${new Date().toISOString().split('T')[0]}.${validatedQuery.format}`;

      res.setHeader('Content-Type', contentType);
      res.setHeader('Content-Disposition', `attachment; filename="${fileName}"`);
      res.send(buffer);
    } catch (error) {
      next(error);
    }
  }
}

export default new ContractController();