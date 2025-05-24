import { z } from 'zod';

export const inventoryValidation = {
  // التحقق من إنشاء معاملة مخزون
  createTransaction: z.object({
    type: z.enum(['IN', 'OUT', 'ADJUSTMENT', 'TRANSFER', 'RETURN', 'DAMAGE']),
    productId: z.string().uuid('معرف المنتج غير صالح'),
    warehouseId: z.string().uuid('معرف المستودع غير صالح'),
    quantity: z.number().positive('الكمية يجب أن تكون موجبة'),
    unitCost: z.number().min(0, 'تكلفة الوحدة يجب أن تكون صفر أو أكثر').optional(),
    referenceType: z.string().optional(),
    referenceId: z.string().uuid().optional(),
    notes: z.string().max(500, 'الملاحظات يجب ألا تتجاوز 500 حرف').optional(),
    metadata: z.record(z.any()).optional(),
  }),

  // التحقق من نقل المخزون
  transferInventory: z.object({
    productId: z.string().uuid('معرف المنتج غير صالح'),
    fromWarehouseId: z.string().uuid('معرف المستودع المصدر غير صالح'),
    toWarehouseId: z.string().uuid('معرف المستودع الوجهة غير صالح'),
    quantity: z.number().positive('الكمية يجب أن تكون موجبة'),
    reason: z.string().max(200, 'السبب يجب ألا يتجاوز 200 حرف').optional(),
    notes: z.string().max(500, 'الملاحظات يجب ألا تتجاوز 500 حرف').optional(),
  }),

  // التحقق من جرد المخزون
  stockTake: z.object({
    warehouseId: z.string().uuid('معرف المستودع غير صالح'),
    items: z.array(z.object({
      productId: z.string().uuid('معرف المنتج غير صالح'),
      countedQuantity: z.number().min(0, 'الكمية المحسوبة يجب أن تكون صفر أو أكثر'),
      notes: z.string().max(200, 'الملاحظات يجب ألا تتجاوز 200 حرف').optional(),
    })).min(1, 'يجب إضافة منتج واحد على الأقل'),
    notes: z.string().max(500, 'الملاحظات يجب ألا تتجاوز 500 حرف').optional(),
  }),

  // التحقق من تقرير حركة المخزون
  stockMovementReport: z.object({
    warehouseId: z.string().uuid('معرف المستودع غير صالح').optional(),
    startDate: z.string().refine((date) => !isNaN(Date.parse(date)), {
      message: 'تاريخ البداية غير صالح',
    }),
    endDate: z.string().refine((date) => !isNaN(Date.parse(date)), {
      message: 'تاريخ النهاية غير صالح',
    }),
    productId: z.string().uuid().optional(),
    transactionType: z.enum(['IN', 'OUT', 'ADJUSTMENT', 'TRANSFER', 'RETURN', 'DAMAGE']).optional(),
  }),

  // التحقق من حجز المخزون
  reserveInventory: z.object({
    productId: z.string().uuid('معرف المنتج غير صالح'),
    warehouseId: z.string().uuid('معرف المستودع غير صالح'),
    quantity: z.number().positive('الكمية يجب أن تكون موجبة'),
    referenceType: z.string().min(1, 'نوع المرجع مطلوب'),
    referenceId: z.string().uuid('معرف المرجع غير صالح'),
    expiresAt: z.string().datetime().optional(),
  }),

  // التحقق من إلغاء حجز المخزون
  unreserveInventory: z.object({
    productId: z.string().uuid('معرف المنتج غير صالح'),
    warehouseId: z.string().uuid('معرف المستودع غير صالح'),
    quantity: z.number().positive('الكمية يجب أن تكون موجبة'),
  }),

  // التحقق من سجل المعاملات
  transactionHistory: z.object({
    page: z.number().int().positive().optional(),
    limit: z.number().int().positive().max(100).optional(),
    warehouseId: z.string().uuid().optional(),
    productId: z.string().uuid().optional(),
    type: z.enum(['IN', 'OUT', 'ADJUSTMENT', 'TRANSFER', 'RETURN', 'DAMAGE']).optional(),
    startDate: z.string().datetime().optional(),
    endDate: z.string().datetime().optional(),
    sortBy: z.enum(['createdAt', 'quantity', 'type']).optional(),
    sortOrder: z.enum(['asc', 'desc']).optional(),
  }),

  // التحقق من تحديث مستويات إعادة الطلب
  updateReorderLevels: z.object({
    items: z.array(z.object({
      productId: z.string().uuid('معرف المنتج غير صالح'),
      warehouseId: z.string().uuid('معرف المستودع غير صالح'),
      minStockLevel: z.number().min(0, 'الحد الأدنى للمخزون يجب أن يكون صفر أو أكثر'),
      reorderPoint: z.number().min(0, 'نقطة إعادة الطلب يجب أن تكون صفر أو أكثر'),
      reorderQuantity: z.number().positive('كمية إعادة الطلب يجب أن تكون موجبة'),
      maxStockLevel: z.number().positive('الحد الأقصى للمخزون يجب أن يكون موجب').optional(),
    })).min(1, 'يجب إضافة منتج واحد على الأقل'),
  }),

  // التحقق من استيراد المخزون
  importInventory: z.object({
    warehouseId: z.string().uuid('معرف المستودع غير صالح'),
    updateExisting: z.boolean().default(false),
    skipInvalid: z.boolean().default(false),
  }),

  // التحقق من البحث في المخزون
  searchInventory: z.object({
    query: z.string().optional(),
    warehouseId: z.string().uuid().optional(),
    categoryId: z.string().uuid().optional(),
    lowStock: z.boolean().optional(),
    outOfStock: z.boolean().optional(),
    page: z.number().int().positive().optional(),
    limit: z.number().int().positive().max(100).optional(),
    sortBy: z.enum(['name', 'quantity', 'value', 'lastUpdated']).optional(),
    sortOrder: z.enum(['asc', 'desc']).optional(),
  }),

  // التحقق من تحديث المخزون الدوري
  batchUpdate: z.object({
    items: z.array(z.object({
      productId: z.string().uuid('معرف المنتج غير صالح'),
      warehouseId: z.string().uuid('معرف المستودع غير صالح'),
      quantity: z.number().min(0, 'الكمية يجب أن تكون صفر أو أكثر'),
      unitCost: z.number().min(0, 'تكلفة الوحدة يجب أن تكون صفر أو أكثر').optional(),
    })).min(1, 'يجب إضافة منتج واحد على الأقل'),
    reason: z.string().max(200, 'السبب يجب ألا يتجاوز 200 حرف'),
  }),

  // التحقق من تقرير المخزون
  inventoryReport: z.object({
    type: z.enum(['summary', 'detailed', 'valuation', 'movement', 'aging']),
    warehouseId: z.string().uuid().optional(),
    categoryId: z.string().uuid().optional(),
    startDate: z.string().datetime().optional(),
    endDate: z.string().datetime().optional(),
    format: z.enum(['pdf', 'excel', 'csv']).optional(),
  }),
};

// نوع بيانات معاملة المخزون
export type CreateInventoryTransactionInput = z.infer<typeof inventoryValidation.createTransaction>;
export type TransferInventoryInput = z.infer<typeof inventoryValidation.transferInventory>;
export type StockTakeInput = z.infer<typeof inventoryValidation.stockTake>;
export type ReserveInventoryInput = z.infer<typeof inventoryValidation.reserveInventory>;
export type UpdateReorderLevelsInput = z.infer<typeof inventoryValidation.updateReorderLevels>;
export type BatchUpdateInput = z.infer<typeof inventoryValidation.batchUpdate>;