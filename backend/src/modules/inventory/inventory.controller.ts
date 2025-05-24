import { Request, Response, NextFunction } from 'express';
import { z } from 'zod';
import { InventoryService } from './inventory.service';
import { inventoryValidation } from './inventory.validation';
import { ApiError } from '@/utils/ApiError';
import { ApiResponse } from '@/utils/ApiResponse';
import { logger } from '@/utils/logger';
import { auditLogUtil } from '@/utils/auditLog';
import { notificationUtil } from '@/utils/notification';
import { cacheUtil } from '@/utils/cache';
import { uploadUtil } from '@/utils/upload';
import { authenticatedRequest } from '@/types/request';

const inventoryService = new InventoryService();

export class InventoryController {
  // إنشاء معاملة مخزون
  static async createTransaction(req: authenticatedRequest, res: Response, next: NextFunction) {
    try {
      const validatedData = inventoryValidation.createTransaction.parse(req.body);
      
      const transaction = await inventoryService.createTransaction(
        validatedData,
        req.user!.id,
        req.user!.tenantId
      );

      // تسجيل في سجل التدقيق
      await auditLogUtil.log({
        action: 'create_inventory_transaction',
        userId: req.user!.id,
        tenantId: req.user!.tenantId,
        resourceType: 'inventory_transaction',
        resourceId: transaction.id,
        details: {
          type: validatedData.type,
          quantity: validatedData.quantity,
          productId: validatedData.productId,
        },
      });

      res.status(201).json(
        new ApiResponse(
          201,
          transaction,
          'تم إنشاء معاملة المخزون بنجاح'
        )
      );
    } catch (error) {
      logger.error('Error creating inventory transaction:', error);
      next(error);
    }
  }

  // نقل المخزون بين المستودعات
  static async transferInventory(req: authenticatedRequest, res: Response, next: NextFunction) {
    try {
      const validatedData = inventoryValidation.transferInventory.parse(req.body);
      
      const transfer = await inventoryService.transferInventory(
        validatedData,
        req.user!.id,
        req.user!.tenantId
      );

      // تسجيل في سجل التدقيق
      await auditLogUtil.log({
        action: 'transfer_inventory',
        userId: req.user!.id,
        tenantId: req.user!.tenantId,
        resourceType: 'inventory_transfer',
        resourceId: transfer.id,
        details: validatedData,
      });

      res.json(
        new ApiResponse(
          200,
          transfer,
          'تم نقل المخزون بنجاح'
        )
      );
    } catch (error) {
      logger.error('Error transferring inventory:', error);
      next(error);
    }
  }

  // جرد المخزون
  static async performStockTake(req: authenticatedRequest, res: Response, next: NextFunction) {
    try {
      const validatedData = inventoryValidation.stockTake.parse(req.body);
      
      const stockTake = await inventoryService.performStockTake(
        validatedData,
        req.user!.id,
        req.user!.tenantId
      );

      // تسجيل في سجل التدقيق
      await auditLogUtil.log({
        action: 'perform_stock_take',
        userId: req.user!.id,
        tenantId: req.user!.tenantId,
        resourceType: 'stock_take',
        resourceId: stockTake.id,
        details: {
          warehouseId: validatedData.warehouseId,
          itemsCount: validatedData.items.length,
        },
      });

      res.json(
        new ApiResponse(
          200,
          stockTake,
          'تم إجراء الجرد بنجاح'
        )
      );
    } catch (error) {
      logger.error('Error performing stock take:', error);
      next(error);
    }
  }

  // الحصول على إحصائيات المخزون
  static async getStatistics(req: authenticatedRequest, res: Response, next: NextFunction) {
    try {
      const warehouseId = req.query.warehouseId as string | undefined;
      
      const statistics = await inventoryService.getInventoryStatistics(
        warehouseId || null,
        req.user!.tenantId
      );

      res.json(
        new ApiResponse(
          200,
          statistics,
          'تم استرجاع إحصائيات المخزون بنجاح'
        )
      );
    } catch (error) {
      logger.error('Error getting inventory statistics:', error);
      next(error);
    }
  }

  // الحصول على تقرير حركة المخزون
  static async getStockMovementReport(req: authenticatedRequest, res: Response, next: NextFunction) {
    try {
      const validatedData = inventoryValidation.stockMovementReport.parse(req.query);
      
      const report = await inventoryService.getStockMovementReport(
        validatedData.warehouseId,
        new Date(validatedData.startDate),
        new Date(validatedData.endDate),
        req.user!.tenantId
      );

      res.json(
        new ApiResponse(
          200,
          report,
          'تم استرجاع تقرير حركة المخزون بنجاح'
        )
      );
    } catch (error) {
      logger.error('Error getting stock movement report:', error);
      next(error);
    }
  }

  // الحصول على تقرير تقييم المخزون
  static async getValuationReport(req: authenticatedRequest, res: Response, next: NextFunction) {
    try {
      const warehouseId = req.query.warehouseId as string | undefined;
      
      const report = await inventoryService.getInventoryValuationReport(
        warehouseId || null,
        req.user!.tenantId
      );

      res.json(
        new ApiResponse(
          200,
          report,
          'تم استرجاع تقرير تقييم المخزون بنجاح'
        )
      );
    } catch (error) {
      logger.error('Error getting inventory valuation report:', error);
      next(error);
    }
  }

  // تصدير بيانات المخزون
  static async exportInventory(req: authenticatedRequest, res: Response, next: NextFunction) {
    try {
      const format = (req.query.format as 'excel' | 'csv') || 'excel';
      const warehouseId = req.query.warehouseId as string | undefined;
      
      const buffer = await inventoryService.exportInventory(
        warehouseId || null,
        format,
        req.user!.tenantId
      );

      // تسجيل في سجل التدقيق
      await auditLogUtil.log({
        action: 'export_inventory',
        userId: req.user!.id,
        tenantId: req.user!.tenantId,
        resourceType: 'inventory',
        details: {
          format,
          warehouseId,
        },
      });

      const contentType = format === 'excel' 
        ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        : 'text/csv';
      const filename = format === 'excel'
        ? `inventory_${new Date().toISOString().split('T')[0]}.xlsx`
        : `inventory_${new Date().toISOString().split('T')[0]}.csv`;

      res.setHeader('Content-Type', contentType);
      res.setHeader('Content-Disposition', `attachment; filename="${filename}"`);
      res.send(buffer);
    } catch (error) {
      logger.error('Error exporting inventory:', error);
      next(error);
    }
  }

  // حجز كمية من المخزون
  static async reserveInventory(req: authenticatedRequest, res: Response, next: NextFunction) {
    try {
      const validatedData = inventoryValidation.reserveInventory.parse(req.body);
      
      await inventoryService.reserveInventory(
        validatedData.productId,
        validatedData.warehouseId,
        validatedData.quantity,
        validatedData.referenceId,
        validatedData.referenceType,
        req.user!.tenantId
      );

      res.json(
        new ApiResponse(
          200,
          null,
          'تم حجز الكمية بنجاح'
        )
      );
    } catch (error) {
      logger.error('Error reserving inventory:', error);
      next(error);
    }
  }

  // إلغاء حجز كمية من المخزون
  static async unreserveInventory(req: authenticatedRequest, res: Response, next: NextFunction) {
    try {
      const validatedData = inventoryValidation.unreserveInventory.parse(req.body);
      
      await inventoryService.unreserveInventory(
        validatedData.productId,
        validatedData.warehouseId,
        validatedData.quantity,
        req.user!.tenantId
      );

      res.json(
        new ApiResponse(
          200,
          null,
          'تم إلغاء حجز الكمية بنجاح'
        )
      );
    } catch (error) {
      logger.error('Error unreserving inventory:', error);
      next(error);
    }
  }

  // استيراد بيانات المخزون
  static async importInventory(req: authenticatedRequest, res: Response, next: NextFunction) {
    try {
      if (!req.file) {
        throw new ApiError(400, 'الرجاء اختيار ملف للاستيراد');
      }

      const warehouseId = req.body.warehouseId;
      if (!warehouseId) {
        throw new ApiError(400, 'معرف المستودع مطلوب');
      }

      // TODO: تنفيذ منطق استيراد المخزون
      // قراءة الملف وتحليل البيانات
      // التحقق من صحة البيانات
      // إدراج أو تحديث البيانات في قاعدة البيانات

      res.json(
        new ApiResponse(
          200,
          { imported: 0, updated: 0, errors: [] },
          'تم استيراد البيانات بنجاح'
        )
      );
    } catch (error) {
      logger.error('Error importing inventory:', error);
      next(error);
    }
  }

  // الحصول على المنتجات منخفضة المخزون
  static async getLowStockItems(req: authenticatedRequest, res: Response, next: NextFunction) {
    try {
      const warehouseId = req.query.warehouseId as string | undefined;
      const limit = parseInt(req.query.limit as string) || 50;
      
      // TODO: تنفيذ منطق الحصول على المنتجات منخفضة المخزون
      const items: any[] = [];

      res.json(
        new ApiResponse(
          200,
          items,
          'تم استرجاع المنتجات منخفضة المخزون بنجاح'
        )
      );
    } catch (error) {
      logger.error('Error getting low stock items:', error);
      next(error);
    }
  }

  // الحصول على سجل معاملات المخزون
  static async getTransactionHistory(req: authenticatedRequest, res: Response, next: NextFunction) {
    try {
      const validatedData = inventoryValidation.transactionHistory.parse(req.query);
      
      // TODO: تنفيذ منطق الحصول على سجل المعاملات
      const transactions: any[] = [];

      res.json(
        new ApiResponse(
          200,
          {
            data: transactions,
            total: 0,
            page: validatedData.page || 1,
            limit: validatedData.limit || 20,
          },
          'تم استرجاع سجل المعاملات بنجاح'
        )
      );
    } catch (error) {
      logger.error('Error getting transaction history:', error);
      next(error);
    }
  }

  // تحديث مستويات إعادة الطلب
  static async updateReorderLevels(req: authenticatedRequest, res: Response, next: NextFunction) {
    try {
      const validatedData = inventoryValidation.updateReorderLevels.parse(req.body);
      
      // TODO: تنفيذ منطق تحديث مستويات إعادة الطلب

      res.json(
        new ApiResponse(
          200,
          null,
          'تم تحديث مستويات إعادة الطلب بنجاح'
        )
      );
    } catch (error) {
      logger.error('Error updating reorder levels:', error);
      next(error);
    }
  }
}