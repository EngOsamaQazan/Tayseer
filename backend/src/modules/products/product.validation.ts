import { z } from 'zod';

export const productValidation = {
  create: z.object({
    name: z.string().min(1, 'اسم المنتج مطلوب').max(255),
    nameEn: z.string().optional(),
    sku: z.string().min(1, 'رمز المنتج مطلوب').max(50),
    barcode: z.string().optional(),
    description: z.string().optional(),
    descriptionEn: z.string().optional(),
    categoryId: z.string().uuid('معرف الفئة غير صالح'),
    type: z.enum(['product', 'service'], {
      errorMap: () => ({ message: 'نوع المنتج يجب أن يكون منتج أو خدمة' }),
    }),
    unitId: z.string().uuid('معرف الوحدة غير صالح').optional(),
    price: z.number().min(0, 'السعر يجب أن يكون 0 أو أكثر'),
    cost: z.number().min(0, 'التكلفة يجب أن تكون 0 أو أكثر').optional(),
    taxRate: z.number().min(0).max(100, 'معدل الضريبة يجب أن يكون بين 0 و 100').default(0),
    trackInventory: z.boolean().default(true),
    allowBackorder: z.boolean().default(false),
    minStockLevel: z.number().min(0).default(0),
    reorderLevel: z.number().min(0).default(0),
    isActive: z.boolean().default(true),
    imageUrl: z.string().url('رابط الصورة غير صالح').optional(),
    attributes: z.record(z.any()).optional(),
  }),

  update: z.object({
    name: z.string().min(1).max(255).optional(),
    nameEn: z.string().optional(),
    sku: z.string().min(1).max(50).optional(),
    barcode: z.string().optional(),
    description: z.string().optional(),
    descriptionEn: z.string().optional(),
    categoryId: z.string().uuid().optional(),
    type: z.enum(['product', 'service']).optional(),
    unitId: z.string().uuid().optional(),
    price: z.number().min(0).optional(),
    cost: z.number().min(0).optional(),
    taxRate: z.number().min(0).max(100).optional(),
    trackInventory: z.boolean().optional(),
    allowBackorder: z.boolean().optional(),
    minStockLevel: z.number().min(0).optional(),
    reorderLevel: z.number().min(0).optional(),
    isActive: z.boolean().optional(),
    imageUrl: z.string().url().optional(),
    attributes: z.record(z.any()).optional(),
  }),

  createVariant: z.object({
    name: z.string().min(1, 'اسم المتغير مطلوب').max(255),
    sku: z.string().min(1, 'رمز المتغير مطلوب').max(50),
    price: z.number().min(0, 'السعر يجب أن يكون 0 أو أكثر'),
    cost: z.number().min(0).optional(),
    attributes: z.record(z.string()).optional(),
    isActive: z.boolean().default(true),
  }),

  updateVariant: z.object({
    name: z.string().min(1).max(255).optional(),
    sku: z.string().min(1).max(50).optional(),
    price: z.number().min(0).optional(),
    cost: z.number().min(0).optional(),
    attributes: z.record(z.string()).optional(),
    isActive: z.boolean().optional(),
  }),

  bulkUpdatePrices: z.object({
    updates: z.array(
      z.object({
        productId: z.string().uuid('معرف المنتج غير صالح'),
        price: z.number().min(0, 'السعر يجب أن يكون 0 أو أكثر'),
        cost: z.number().min(0).optional(),
      })
    ).min(1, 'يجب تحديد منتج واحد على الأقل'),
  }),

  importProducts: z.object({
    products: z.array(
      z.object({
        name: z.string().min(1),
        sku: z.string().min(1),
        categoryId: z.string().uuid(),
        type: z.enum(['product', 'service']),
        price: z.number().min(0),
        cost: z.number().min(0).optional(),
        taxRate: z.number().min(0).max(100).default(0),
        trackInventory: z.boolean().default(true),
        isActive: z.boolean().default(true),
      })
    ),
  }),
};

// تحقق من صحة معايير البحث
export const productSearchValidation = z.object({
  page: z.number().int().min(1).default(1),
  limit: z.number().int().min(1).max(100).default(10),
  search: z.string().optional(),
  categoryId: z.string().uuid().optional(),
  type: z.enum(['product', 'service']).optional(),
  minPrice: z.number().min(0).optional(),
  maxPrice: z.number().min(0).optional(),
  inStock: z.boolean().optional(),
  sortBy: z.enum(['name', 'price', 'createdAt', 'updatedAt']).default('createdAt'),
  sortOrder: z.enum(['asc', 'desc']).default('desc'),
});

// تحقق من صحة تحديث المخزون
export const inventoryUpdateValidation = z.object({
  productId: z.string().uuid(),
  quantity: z.number().int(),
  type: z.enum(['in', 'out', 'adjustment']),
  reason: z.string().optional(),
  reference: z.string().optional(),
});