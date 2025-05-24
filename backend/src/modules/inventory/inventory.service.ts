import { PrismaClient } from '@prisma/client';
import { ApiError } from '../../shared/utils/ApiError';
import { RedisService } from '../../shared/services/redis.service';
import { Logger } from '../../shared/utils/logger';
import { AuditService } from '../audit/audit.service';
import { NotificationService } from '../notifications/notification.service';
import * as ExcelJS from 'exceljs';
import { Parser } from 'json2csv';
import {
  Inventory,
  InventoryTransaction,
  CreateInventoryTransactionInput,
  UpdateInventoryInput,
  InventorySearchParams,
  InventoryStatistics,
  StockMovementReport,
  InventoryValuationReport,
  StockTakeInput,
  TransferInventoryInput,
} from './inventory.model';

const prisma = new PrismaClient();
const redisService = new RedisService();
const logger = new Logger();
const auditService = new AuditService();
const notificationService = new NotificationService();

export class InventoryService {
  // إنشاء معاملة مخزون جديدة
  async createTransaction(
    data: CreateInventoryTransactionInput,
    userId: string,
    tenantId: string
  ): Promise<InventoryTransaction> {
    try {
      // التحقق من وجود المخزون
      const inventory = await prisma.inventory.findFirst({
        where: {
          productId: data.productId,
          warehouseId: data.warehouseId,
          variantId: data.variantId,
          tenantId,
        },
        include: {
          product: true,
          warehouse: true,
        },
      });

      if (!inventory) {
        // إنشاء سجل مخزون جديد إذا لم يكن موجودًا
        const newInventory = await prisma.inventory.create({
          data: {
            productId: data.productId,
            warehouseId: data.warehouseId,
            variantId: data.variantId,
            quantity: 0,
            reservedQuantity: 0,
            availableQuantity: 0,
            tenantId,
          },
        });
        
        return this.processTransaction(newInventory, data, userId, tenantId);
      }

      return this.processTransaction(inventory, data, userId, tenantId);
    } catch (error) {
      logger.error('Error creating inventory transaction', error);
      throw new ApiError(500, 'حدث خطأ في إنشاء معاملة المخزون');
    }
  }

  // معالجة معاملة المخزون
  private async processTransaction(
    inventory: any,
    data: CreateInventoryTransactionInput,
    userId: string,
    tenantId: string
  ): Promise<InventoryTransaction> {
    const previousQuantity = inventory.quantity;
    let newQuantity = previousQuantity;
    let actualQuantity = data.quantity;

    // حساب الكمية الجديدة بناءً على نوع المعاملة
    switch (data.type) {
      case 'in':
        newQuantity += actualQuantity;
        break;
      case 'out':
        if (previousQuantity < actualQuantity) {
          throw new ApiError(400, 'الكمية المطلوبة غير متوفرة في المخزون');
        }
        newQuantity -= actualQuantity;
        actualQuantity = -actualQuantity; // كمية سالبة للخروج
        break;
      case 'adjustment':
        newQuantity = actualQuantity; // التعديل يحدد الكمية الجديدة مباشرة
        actualQuantity = newQuantity - previousQuantity; // حساب الفرق
        break;
      case 'transfer':
        if (previousQuantity < data.quantity) {
          throw new ApiError(400, 'الكمية المطلوبة للنقل غير متوفرة');
        }
        newQuantity -= data.quantity;
        actualQuantity = -data.quantity;
        break;
    }

    // بدء المعاملة
    const result = await prisma.$transaction(async (tx) => {
      // تحديث المخزون
      const updatedInventory = await tx.inventory.update({
        where: { id: inventory.id },
        data: {
          quantity: newQuantity,
          availableQuantity: newQuantity - inventory.reservedQuantity,
          updatedAt: new Date(),
        },
      });

      // إنشاء سجل المعاملة
      const transaction = await tx.inventoryTransaction.create({
        data: {
          inventoryId: inventory.id,
          type: data.type,
          quantity: actualQuantity,
          previousQuantity,
          newQuantity,
          reference: data.reference,
          referenceType: data.referenceType,
          referenceId: data.referenceId,
          reason: data.reason,
          createdBy: userId,
          tenantId,
        },
        include: {
          inventory: {
            include: {
              product: true,
              warehouse: true,
            },
          },
        },
      });

      return transaction;
    });

    // إبطال ذاكرة التخزين المؤقت
    await this.invalidateInventoryCache(inventory.productId, tenantId);

    // تسجيل التدقيق
    await auditService.log('inventory_transaction', 'create', {
      entityId: result.id,
      oldData: { quantity: previousQuantity },
      newData: { quantity: newQuantity },
      userId,
      tenantId,
    });

    // إرسال إشعار إذا كان المخزون منخفضًا
    if (inventory.product.trackInventory && newQuantity <= inventory.product.reorderLevel) {
      await notificationService.send({
        type: 'low_stock',
        title: 'مخزون منخفض',
        message: `المنتج ${inventory.product.name} وصل إلى حد إعادة الطلب`,
        recipientId: userId,
        data: {
          productId: inventory.productId,
          currentStock: newQuantity,
          reorderLevel: inventory.product.reorderLevel,
        },
      });
    }

    return result;
  }

  // الحصول على المخزون حسب المنتج
  async getInventoryByProduct(
    productId: string,
    tenantId: string
  ): Promise<Inventory[]> {
    const cacheKey = `inventory:product:${productId}:${tenantId}`;
    const cached = await redisService.get(cacheKey);
    
    if (cached) {
      return JSON.parse(cached);
    }

    const inventory = await prisma.inventory.findMany({
      where: {
        productId,
        tenantId,
      },
      include: {
        warehouse: true,
        variant: true,
      },
      orderBy: {
        warehouse: {
          name: 'asc',
        },
      },
    });

    await redisService.set(cacheKey, JSON.stringify(inventory), 300);
    return inventory;
  }

  // الحصول على المخزون حسب المستودع
  async getInventoryByWarehouse(
    warehouseId: string,
    params: InventorySearchParams,
    tenantId: string
  ): Promise<{ items: Inventory[]; total: number }> {
    const { page = 1, limit = 10, search, categoryId, lowStock } = params;
    const skip = (page - 1) * limit;

    const where: any = {
      warehouseId,
      tenantId,
    };

    if (search) {
      where.OR = [
        { product: { name: { contains: search, mode: 'insensitive' } } },
        { product: { sku: { contains: search, mode: 'insensitive' } } },
        { product: { barcode: { contains: search, mode: 'insensitive' } } },
      ];
    }

    if (categoryId) {
      where.product = { categoryId };
    }

    if (lowStock) {
      where.OR = [
        { quantity: { lte: prisma.inventory.fields.product.reorderLevel } },
      ];
    }

    const [items, total] = await Promise.all([
      prisma.inventory.findMany({
        where,
        include: {
          product: {
            include: {
              category: true,
              unit: true,
            },
          },
          variant: true,
          transactions: {
            take: 5,
            orderBy: { createdAt: 'desc' },
          },
        },
        skip,
        take: limit,
        orderBy: { updatedAt: 'desc' },
      }),
      prisma.inventory.count({ where }),
    ]);

    return { items, total };
  }

  // نقل المخزون بين المستودعات
  async transferInventory(
    data: TransferInventoryInput,
    userId: string,
    tenantId: string
  ): Promise<{ fromTransaction: InventoryTransaction; toTransaction: InventoryTransaction }> {
    try {
      // التحقق من وجود المستودعات
      const [fromWarehouse, toWarehouse] = await Promise.all([
        prisma.warehouse.findFirst({
          where: { id: data.fromWarehouseId, tenantId },
        }),
        prisma.warehouse.findFirst({
          where: { id: data.toWarehouseId, tenantId },
        }),
      ]);

      if (!fromWarehouse || !toWarehouse) {
        throw new ApiError(404, 'المستودع غير موجود');
      }

      // إنشاء معاملة خروج من المستودع المصدر
      const fromTransaction = await this.createTransaction(
        {
          productId: data.productId,
          variantId: data.variantId,
          warehouseId: data.fromWarehouseId,
          type: 'transfer',
          quantity: data.quantity,
          reason: `نقل إلى ${toWarehouse.name}`,
          reference: `TRANSFER-${Date.now()}`,
          referenceType: 'transfer',
        },
        userId,
        tenantId
      );

      // إنشاء معاملة دخول إلى المستودع المستهدف
      const toTransaction = await this.createTransaction(
        {
          productId: data.productId,
          variantId: data.variantId,
          warehouseId: data.toWarehouseId,
          type: 'in',
          quantity: data.quantity,
          reason: `نقل من ${fromWarehouse.name}`,
          reference: fromTransaction.reference,
          referenceType: 'transfer',
          referenceId: fromTransaction.id,
        },
        userId,
        tenantId
      );

      return { fromTransaction, toTransaction };
    } catch (error) {
      logger.error('Error transferring inventory', error);
      if (error instanceof ApiError) throw error;
      throw new ApiError(500, 'حدث خطأ في نقل المخزون');
    }
  }

  // جرد المخزون
  async performStockTake(
    data: StockTakeInput,
    userId: string,
    tenantId: string
  ): Promise<InventoryTransaction[]> {
    try {
      const transactions: InventoryTransaction[] = [];

      for (const item of data.items) {
        const inventory = await prisma.inventory.findFirst({
          where: {
            productId: item.productId,
            warehouseId: data.warehouseId,
            variantId: item.variantId,
            tenantId,
          },
        });

        if (!inventory) {
          continue;
        }

        if (inventory.quantity !== item.actualQuantity) {
          const transaction = await this.createTransaction(
            {
              productId: item.productId,
              variantId: item.variantId,
              warehouseId: data.warehouseId,
              type: 'adjustment',
              quantity: item.actualQuantity,
              reason: data.reason || 'جرد دوري',
              reference: `STOCKTAKE-${Date.now()}`,
              referenceType: 'stocktake',
            },
            userId,
            tenantId
          );

          transactions.push(transaction);
        }

        // تحديث تاريخ آخر جرد
        await prisma.inventory.update({
          where: { id: inventory.id },
          data: { lastStockTake: new Date() },
        });
      }

      // تسجيل التدقيق
      await auditService.log('inventory_stocktake', 'create', {
        entityId: data.warehouseId,
        newData: {
          itemsCount: data.items.length,
          adjustmentsCount: transactions.length,
        },
        userId,
        tenantId,
      });

      return transactions;
    } catch (error) {
      logger.error('Error performing stock take', error);
      if (error instanceof ApiError) throw error;
      throw new ApiError(500, 'حدث خطأ في عملية الجرد');
    }
  }

  // الحصول على إحصائيات المخزون
  async getInventoryStatistics(tenantId: string): Promise<InventoryStatistics> {
    const cacheKey = `inventory:statistics:${tenantId}`;
    const cached = await redisService.get(cacheKey);
    
    if (cached) {
      return JSON.parse(cached);
    }

    const [totalItems, totalValue, lowStockItems, outOfStockItems, warehouseStats] = await Promise.all([
      prisma.inventory.count({ where: { tenantId } }),
      prisma.inventory.aggregate({
        where: { tenantId },
        _sum: {
          quantity: true,
        },
      }),
      prisma.inventory.count({
        where: {
          tenantId,
          quantity: { lte: prisma.inventory.fields.product.reorderLevel },
          product: { trackInventory: true },
        },
      }),
      prisma.inventory.count({
        where: {
          tenantId,
          quantity: 0,
          product: { trackInventory: true },
        },
      }),
      prisma.warehouse.findMany({
        where: { tenantId },
        include: {
          _count: {
            select: { inventory: true },
          },
        },
      }),
    ]);

    const statistics: InventoryStatistics = {
      totalItems,
      totalValue: totalValue._sum.quantity || 0,
      lowStockItems,
      outOfStockItems,
      warehouseCount: warehouseStats.length,
      warehouseStats: warehouseStats.map((w) => ({
        warehouseId: w.id,
        warehouseName: w.name,
        itemCount: w._count.inventory,
      })),
    };

    await redisService.set(cacheKey, JSON.stringify(statistics), 300);
    return statistics;
  }

  // الحصول على تقرير حركة المخزون
  async getStockMovementReport(
    warehouseId: string,
    startDate: Date,
    endDate: Date,
    tenantId: string
  ): Promise<StockMovementReport[]> {
    const transactions = await prisma.inventoryTransaction.findMany({
      where: {
        inventory: {
          warehouseId,
          tenantId,
        },
        createdAt: {
          gte: startDate,
          lte: endDate,
        },
      },
      include: {
        inventory: {
          include: {
            product: true,
            variant: true,
          },
        },
      },
      orderBy: { createdAt: 'desc' },
    });

    const reportMap = new Map<string, StockMovementReport>();

    transactions.forEach((trans) => {
      const key = `${trans.inventory.productId}-${trans.inventory.variantId || 'main'}`;
      
      if (!reportMap.has(key)) {
        reportMap.set(key, {
          productId: trans.inventory.productId,
          productName: trans.inventory.product.name,
          variantId: trans.inventory.variantId,
          variantName: trans.inventory.variant?.name,
          openingStock: 0,
          incomingQuantity: 0,
          outgoingQuantity: 0,
          adjustmentQuantity: 0,
          closingStock: trans.inventory.quantity,
          transactions: [],
        });
      }

      const report = reportMap.get(key)!;
      report.transactions.push(trans);

      switch (trans.type) {
        case 'in':
          report.incomingQuantity += Math.abs(trans.quantity);
          break;
        case 'out':
        case 'transfer':
          report.outgoingQuantity += Math.abs(trans.quantity);
          break;
        case 'adjustment':
          report.adjustmentQuantity += trans.quantity;
          break;
      }
    });

    return Array.from(reportMap.values());
  }

  // الحصول على تقرير تقييم المخزون
  async getInventoryValuationReport(
    warehouseId: string | null,
    tenantId: string
  ): Promise<InventoryValuationReport> {
    const where: any = { tenantId };
    if (warehouseId) {
      where.warehouseId = warehouseId;
    }

    const inventory = await prisma.inventory.findMany({
      where,
      include: {
        product: true,
        variant: true,
        warehouse: true,
      },
    });

    const items = inventory.map((inv) => {
      const unitCost = inv.variant?.costPrice || inv.product.costPrice || 0;
      const totalValue = inv.quantity * unitCost;

      return {
        productId: inv.productId,
        productName: inv.product.name,
        variantId: inv.variantId,
        variantName: inv.variant?.name,
        warehouseId: inv.warehouseId,
        warehouseName: inv.warehouse.name,
        quantity: inv.quantity,
        unitCost,
        totalValue,
      };
    });

    const totalValue = items.reduce((sum, item) => sum + item.totalValue, 0);
    const totalQuantity = items.reduce((sum, item) => sum + item.quantity, 0);

    return {
      reportDate: new Date(),
      totalValue,
      totalQuantity,
      itemCount: items.length,
      items,
    };
  }

  // تصدير بيانات المخزون
  async exportInventory(
    warehouseId: string | null,
    format: 'excel' | 'csv',
    tenantId: string
  ): Promise<Buffer> {
    const where: any = { tenantId };
    if (warehouseId) {
      where.warehouseId = warehouseId;
    }

    const inventory = await prisma.inventory.findMany({
      where,
      include: {
        product: {
          include: {
            category: true,
            unit: true,
          },
        },
        variant: true,
        warehouse: true,
      },
      orderBy: [
        { warehouse: { name: 'asc' } },
        { product: { name: 'asc' } },
      ],
    });

    if (format === 'excel') {
      const workbook = new ExcelJS.Workbook();
      const worksheet = workbook.addWorksheet('المخزون');

      // إضافة العناوين
      worksheet.columns = [
        { header: 'المستودع', key: 'warehouse', width: 20 },
        { header: 'رمز المنتج', key: 'sku', width: 15 },
        { header: 'اسم المنتج', key: 'productName', width: 30 },
        { header: 'المتغير', key: 'variantName', width: 20 },
        { header: 'الفئة', key: 'category', width: 20 },
        { header: 'الوحدة', key: 'unit', width: 10 },
        { header: 'الكمية', key: 'quantity', width: 10 },
        { header: 'الكمية المحجوزة', key: 'reservedQuantity', width: 15 },
        { header: 'الكمية المتاحة', key: 'availableQuantity', width: 15 },
        { header: 'سعر التكلفة', key: 'costPrice', width: 12 },
        { header: 'القيمة الإجمالية', key: 'totalValue', width: 15 },
        { header: 'آخر تحديث', key: 'updatedAt', width: 20 },
      ];

      // تنسيق العناوين
      worksheet.getRow(1).font = { bold: true };
      worksheet.getRow(1).alignment = { horizontal: 'center' };

      // إضافة البيانات
      inventory.forEach((inv) => {
        const unitCost = inv.variant?.costPrice || inv.product.costPrice || 0;
        worksheet.addRow({
          warehouse: inv.warehouse.name,
          sku: inv.product.sku,
          productName: inv.product.name,
          variantName: inv.variant?.name || '-',
          category: inv.product.category?.name || '-',
          unit: inv.product.unit?.name || '-',
          quantity: inv.quantity,
          reservedQuantity: inv.reservedQuantity,
          availableQuantity: inv.availableQuantity,
          costPrice: unitCost,
          totalValue: inv.quantity * unitCost,
          updatedAt: inv.updatedAt.toLocaleString('ar-SA'),
        });
      });

      return await workbook.xlsx.writeBuffer() as Buffer;
    } else {
      const data = inventory.map((inv) => {
        const unitCost = inv.variant?.costPrice || inv.product.costPrice || 0;
        return {
          warehouse: inv.warehouse.name,
          sku: inv.product.sku,
          productName: inv.product.name,
          variantName: inv.variant?.name || '',
          category: inv.product.category?.name || '',
          unit: inv.product.unit?.name || '',
          quantity: inv.quantity,
          reservedQuantity: inv.reservedQuantity,
          availableQuantity: inv.availableQuantity,
          costPrice: unitCost,
          totalValue: inv.quantity * unitCost,
          updatedAt: inv.updatedAt.toISOString(),
        };
      });

      const parser = new Parser({
        fields: [
          'warehouse',
          'sku',
          'productName',
          'variantName',
          'category',
          'unit',
          'quantity',
          'reservedQuantity',
          'availableQuantity',
          'costPrice',
          'totalValue',
          'updatedAt',
        ],
      });

      return Buffer.from(parser.parse(data), 'utf-8');
    }
  }

  // إبطال ذاكرة التخزين المؤقت للمخزون
  private async invalidateInventoryCache(productId: string, tenantId: string): Promise<void> {
    const keys = [
      `inventory:product:${productId}:${tenantId}`,
      `inventory:statistics:${tenantId}`,
    ];

    await Promise.all(keys.map((key) => redisService.del(key)));
  }

  // حجز كمية من المخزون
  async reserveInventory(
    productId: string,
    warehouseId: string,
    quantity: number,
    referenceId: string,
    referenceType: string,
    tenantId: string
  ): Promise<void> {
    const inventory = await prisma.inventory.findFirst({
      where: {
        productId,
        warehouseId,
        tenantId,
      },
    });

    if (!inventory) {
      throw new ApiError(404, 'المخزون غير موجود');
    }

    if (inventory.availableQuantity < quantity) {
      throw new ApiError(400, 'الكمية المطلوبة غير متاحة');
    }

    await prisma.inventory.update({
      where: { id: inventory.id },
      data: {
        reservedQuantity: inventory.reservedQuantity + quantity,
        availableQuantity: inventory.availableQuantity - quantity,
      },
    });

    // إبطال ذاكرة التخزين المؤقت
    await this.invalidateInventoryCache(productId, tenantId);
  }

  // إلغاء حجز كمية من المخزون
  async unreserveInventory(
    productId: string,
    warehouseId: string,
    quantity: number,
    tenantId: string
  ): Promise<void> {
    const inventory = await prisma.inventory.findFirst({
      where: {
        productId,
        warehouseId,
        tenantId,
      },
    });

    if (!inventory) {
      throw new ApiError(404, 'المخزون غير موجود');
    }

    await prisma.inventory.update({
      where: { id: inventory.id },
      data: {
        reservedQuantity: Math.max(0, inventory.reservedQuantity - quantity),
        availableQuantity: inventory.availableQuantity + quantity,
      },
    });

    // إبطال ذاكرة التخزين المؤقت
    await this.invalidateInventoryCache(productId, tenantId);
  }
}