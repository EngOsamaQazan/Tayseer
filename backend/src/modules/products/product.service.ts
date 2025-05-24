import { Request, Response, NextFunction } from 'express';
import prisma from '../../config/database.config';
import { RedisService } from '../../services/redis.service';
import { Logger } from '../../utils/logger.util';
import { ApiError } from '../../utils/errors.util';
import { NotificationService } from '../../services/notification.service';
import { AuditService } from '../../services/audit.service';
import { FileUploadService } from '../../services/fileUpload.service';
import * as xlsx from 'xlsx';
import { createObjectCsvStringifier } from 'csv-writer';

interface CreateProductData {
  name: string;
  nameEn?: string;
  sku: string;
  barcode?: string;
  description?: string;
  descriptionEn?: string;
  categoryId: string;
  subcategoryId?: string;
  type: 'product' | 'service';
  unitId: string;
  price: number;
  cost: number;
  taxRate: number;
  minQuantity: number;
  maxQuantity?: number;
  reorderLevel: number;
  trackInventory: boolean;
  allowBackorder: boolean;
  weight?: number;
  dimensions?: {
    length?: number;
    width?: number;
    height?: number;
  };
  images?: string[];
  tags?: string[];
  metadata?: any;
  isActive: boolean;
  tenantId: string;
  createdBy: string;
}

interface UpdateProductData {
  name?: string;
  nameEn?: string;
  description?: string;
  descriptionEn?: string;
  categoryId?: string;
  subcategoryId?: string;
  type?: 'product' | 'service';
  unitId?: string;
  price?: number;
  cost?: number;
  taxRate?: number;
  minQuantity?: number;
  maxQuantity?: number;
  reorderLevel?: number;
  trackInventory?: boolean;
  allowBackorder?: boolean;
  weight?: number;
  dimensions?: {
    length?: number;
    width?: number;
    height?: number;
  };
  images?: string[];
  tags?: string[];
  metadata?: any;
  isActive?: boolean;
  updatedBy: string;
}

interface ProductListParams {
  page?: number;
  limit?: number;
  search?: string;
  categoryId?: string;
  subcategoryId?: string;
  type?: 'product' | 'service';
  minPrice?: number;
  maxPrice?: number;
  inStock?: boolean;
  isActive?: boolean;
  sortBy?: string;
  sortOrder?: 'asc' | 'desc';
  tenantId: string;
}

interface ProductPriceHistoryData {
  productId: string;
  oldPrice: number;
  newPrice: number;
  reason?: string;
  effectiveDate: Date;
  tenantId: string;
  changedBy: string;
}

interface ProductVariantData {
  productId: string;
  name: string;
  sku: string;
  barcode?: string;
  attributes: any;
  price: number;
  cost: number;
  weight?: number;
  images?: string[];
  isActive: boolean;
  tenantId: string;
  createdBy: string;
}

export class ProductService {
  private logger: Logger;
  private redisService: RedisService;
  private notificationService: NotificationService;
  private auditService: AuditService;
  private fileUploadService: FileUploadService;

  constructor() {
    this.logger = new Logger('ProductService');
    this.redisService = RedisService.getInstance();
    this.notificationService = NotificationService.getInstance();
    this.auditService = AuditService.getInstance();
    this.fileUploadService = FileUploadService.getInstance();
  }

  async createProduct(data: CreateProductData): Promise<Product> {
    try {
      // التحقق من عدم تكرار SKU
      const existingProduct = await prisma.product.findFirst({
        where: {
          sku: data.sku,
          tenantId: data.tenantId,
          isDeleted: false,
        },
      });

      if (existingProduct) {
        throw new ApiError(400, 'رمز المنتج (SKU) مستخدم بالفعل');
      }

      // التحقق من وجود الفئة
      const category = await prisma.category.findFirst({
        where: {
          id: data.categoryId,
          tenantId: data.tenantId,
          isDeleted: false,
        },
      });

      if (!category) {
        throw new ApiError(404, 'الفئة غير موجودة');
      }

      // إنشاء المنتج
      const product = await prisma.product.create({
        data: {
          ...data,
          code: await this.generateProductCode(data.tenantId),
        },
        include: {
          category: true,
          unit: true,
          variants: true,
          priceHistory: {
            orderBy: { createdAt: 'desc' },
            take: 1,
          },
        },
      });

      // تسجيل سجل السعر الأولي
      await prisma.productPriceHistory.create({
        data: {
          productId: product.id,
          oldPrice: 0,
          newPrice: product.price,
          reason: 'السعر الأولي',
          effectiveDate: new Date(),
          tenantId: data.tenantId,
          changedBy: data.createdBy,
        },
      });

      // إبطال الكاش
      await this.invalidateProductCache(data.tenantId);

      // تسجيل التدقيق
      await this.auditService.log({
        userId: data.createdBy,
        action: 'product_created',
        entity: 'product',
        entityId: product.id,
        metadata: {
          productName: product.name,
          sku: product.sku,
          type: product.type,
        },
        tenantId: data.tenantId,
      });

      // إرسال إشعار
      await this.notificationService.sendNotification({
        userId: data.createdBy,
        type: 'product_created',
        title: 'تم إنشاء منتج جديد',
        message: `تم إنشاء المنتج ${product.name} بنجاح`,
        metadata: {
          productId: product.id,
          productName: product.name,
        },
        tenantId: data.tenantId,
      });

      this.logger.info('Product created successfully', { productId: product.id });
      return product;
    } catch (error) {
      this.logger.error('Error creating product', error);
      throw error;
    }
  }

  async updateProduct(
    productId: string,
    data: UpdateProductData,
    tenantId: string
  ): Promise<Product> {
    try {
      // التحقق من وجود المنتج
      const product = await prisma.product.findFirst({
        where: {
          id: productId,
          tenantId,
          isDeleted: false,
        },
      });

      if (!product) {
        throw new ApiError(404, 'المنتج غير موجود');
      }

      // تسجيل تغيير السعر إذا تغير
      if (data.price && data.price !== product.price) {
        await prisma.productPriceHistory.create({
          data: {
            productId: product.id,
            oldPrice: product.price,
            newPrice: data.price,
            reason: 'تحديث السعر',
            effectiveDate: new Date(),
            tenantId,
            changedBy: data.updatedBy,
          },
        });
      }

      // تحديث المنتج
      const updatedProduct = await prisma.product.update({
        where: { id: productId },
        data: {
          ...data,
          updatedAt: new Date(),
        },
        include: {
          category: true,
          unit: true,
          variants: true,
          priceHistory: {
            orderBy: { createdAt: 'desc' },
            take: 5,
          },
        },
      });

      // إبطال الكاش
      await this.invalidateProductCache(tenantId);

      // تسجيل التدقيق
      await this.auditService.log({
        userId: data.updatedBy,
        action: 'product_updated',
        entity: 'product',
        entityId: product.id,
        metadata: {
          changes: data,
          oldValues: {
            price: product.price,
            name: product.name,
          },
        },
        tenantId,
      });

      this.logger.info('Product updated successfully', { productId });
      return updatedProduct;
    } catch (error) {
      this.logger.error('Error updating product', error);
      throw error;
    }
  }

  async getProducts(params: ProductListParams): Promise<{
    products: Product[];
    total: number;
    page: number;
    limit: number;
  }> {
    try {
      const {
        page = 1,
        limit = 10,
        search,
        categoryId,
        subcategoryId,
        type,
        minPrice,
        maxPrice,
        inStock,
        isActive,
        sortBy = 'createdAt',
        sortOrder = 'desc',
        tenantId,
      } = params;

      // Try to get from cache
      const cacheKey = `products:${tenantId}:${JSON.stringify(params)}`;
      const cached = await this.redisService.get(cacheKey);
      if (cached) {
        return JSON.parse(cached);
      }

      const where: any = {
        tenantId,
        isDeleted: false,
      };

      if (search) {
        where.OR = [
          { name: { contains: search, mode: 'insensitive' } },
          { nameEn: { contains: search, mode: 'insensitive' } },
          { sku: { contains: search, mode: 'insensitive' } },
          { barcode: { contains: search, mode: 'insensitive' } },
        ];
      }

      if (categoryId) where.categoryId = categoryId;
      if (subcategoryId) where.subcategoryId = subcategoryId;
      if (type) where.type = type;
      if (isActive !== undefined) where.isActive = isActive;

      if (minPrice || maxPrice) {
        where.price = {};
        if (minPrice) where.price.gte = minPrice;
        if (maxPrice) where.price.lte = maxPrice;
      }

      if (inStock !== undefined) {
        if (inStock) {
          where.inventory = {
            some: {
              quantity: { gt: 0 },
            },
          };
        } else {
          where.OR = [
            { inventory: { none: {} } },
            { inventory: { every: { quantity: 0 } } },
          ];
        }
      }

      const [products, total] = await Promise.all([
        prisma.product.findMany({
          where,
          skip: (page - 1) * limit,
          take: limit,
          orderBy: {
            [sortBy]: sortOrder,
          },
          include: {
            category: true,
            unit: true,
            inventory: {
              select: {
                quantity: true,
                warehouseId: true,
              },
            },
          },
        }),
        prisma.product.count({ where }),
      ]);

      const result = {
        products,
        total,
        page,
        limit,
      };

      // Cache for 5 minutes
      await this.redisService.set(cacheKey, JSON.stringify(result), 300);

      return result;
    } catch (error) {
      this.logger.error('Error getting products', error);
      throw error;
    }
  }

  async getProductById(
    productId: string,
    tenantId: string
  ): Promise<Product | null> {
    try {
      const product = await prisma.product.findFirst({
        where: {
          id: productId,
          tenantId,
          isDeleted: false,
        },
        include: {
          category: true,
          subcategory: true,
          unit: true,
          variants: {
            where: { isDeleted: false },
          },
          priceHistory: {
            orderBy: { createdAt: 'desc' },
            take: 10,
          },
          inventory: {
            include: {
              warehouse: true,
            },
          },
        },
      });

      return product;
    } catch (error) {
      this.logger.error('Error getting product by id', error);
      throw error;
    }
  }

  async deleteProduct(
    productId: string,
    userId: string,
    tenantId: string
  ): Promise<void> {
    try {
      const product = await prisma.product.findFirst({
        where: {
          id: productId,
          tenantId,
          isDeleted: false,
        },
      });

      if (!product) {
        throw new ApiError(404, 'المنتج غير موجود');
      }

      // التحقق من عدم وجود معاملات مرتبطة
      const relatedTransactions = await prisma.inventoryTransaction.count({
        where: {
          productId,
          tenantId,
        },
      });

      if (relatedTransactions > 0) {
        throw new ApiError(
          400,
          'لا يمكن حذف المنتج لوجود معاملات مرتبطة به'
        );
      }

      // الحذف الناعم
      await prisma.product.update({
        where: { id: productId },
        data: {
          isDeleted: true,
          deletedAt: new Date(),
          deletedBy: userId,
        },
      });

      // إبطال الكاش
      await this.invalidateProductCache(tenantId);

      // تسجيل التدقيق
      await this.auditService.log({
        userId,
        action: 'product_deleted',
        entity: 'product',
        entityId: productId,
        metadata: {
          productName: product.name,
          sku: product.sku,
        },
        tenantId,
      });

      this.logger.info('Product deleted successfully', { productId });
    } catch (error) {
      this.logger.error('Error deleting product', error);
      throw error;
    }
  }

  async createProductVariant(data: ProductVariantData): Promise<any> {
    try {
      // التحقق من وجود المنتج الأساسي
      const product = await prisma.product.findFirst({
        where: {
          id: data.productId,
          tenantId: data.tenantId,
          isDeleted: false,
        },
      });

      if (!product) {
        throw new ApiError(404, 'المنتج الأساسي غير موجود');
      }

      // التحقق من عدم تكرار SKU
      const existingVariant = await prisma.productVariant.findFirst({
        where: {
          sku: data.sku,
          tenantId: data.tenantId,
          isDeleted: false,
        },
      });

      if (existingVariant) {
        throw new ApiError(400, 'رمز المتغير (SKU) مستخدم بالفعل');
      }

      // إنشاء المتغير
      const variant = await prisma.productVariant.create({
        data,
      });

      // إبطال الكاش
      await this.invalidateProductCache(data.tenantId);

      this.logger.info('Product variant created successfully', {
        variantId: variant.id,
        productId: data.productId,
      });

      return variant;
    } catch (error) {
      this.logger.error('Error creating product variant', error);
      throw error;
    }
  }

  async updateProductVariant(
    variantId: string,
    data: Partial<ProductVariantData>,
    tenantId: string
  ): Promise<any> {
    try {
      const variant = await prisma.productVariant.findFirst({
        where: {
          id: variantId,
          tenantId,
          isDeleted: false,
        },
      });

      if (!variant) {
        throw new ApiError(404, 'المتغير غير موجود');
      }

      const updatedVariant = await prisma.productVariant.update({
        where: { id: variantId },
        data: {
          ...data,
          updatedAt: new Date(),
        },
      });

      // إبطال الكاش
      await this.invalidateProductCache(tenantId);

      return updatedVariant;
    } catch (error) {
      this.logger.error('Error updating product variant', error);
      throw error;
    }
  }

  async deleteProductVariant(
    variantId: string,
    userId: string,
    tenantId: string
  ): Promise<void> {
    try {
      const variant = await prisma.productVariant.findFirst({
        where: {
          id: variantId,
          tenantId,
          isDeleted: false,
        },
      });

      if (!variant) {
        throw new ApiError(404, 'المتغير غير موجود');
      }

      // الحذف الناعم
      await prisma.productVariant.update({
        where: { id: variantId },
        data: {
          isDeleted: true,
          deletedAt: new Date(),
        },
      });

      // إبطال الكاش
      await this.invalidateProductCache(tenantId);

      this.logger.info('Product variant deleted successfully', { variantId });
    } catch (error) {
      this.logger.error('Error deleting product variant', error);
      throw error;
    }
  }

  async bulkUpdatePrices(
    updates: Array<{ productId: string; newPrice: number; reason?: string }>,
    userId: string,
    tenantId: string
  ): Promise<void> {
    try {
      const priceHistoryData = [];

      for (const update of updates) {
        const product = await prisma.product.findFirst({
          where: {
            id: update.productId,
            tenantId,
            isDeleted: false,
          },
        });

        if (product) {
          priceHistoryData.push({
            productId: product.id,
            oldPrice: product.price,
            newPrice: update.newPrice,
            reason: update.reason || 'تحديث جماعي للأسعار',
            effectiveDate: new Date(),
            tenantId,
            changedBy: userId,
          });

          await prisma.product.update({
            where: { id: product.id },
            data: {
              price: update.newPrice,
              updatedAt: new Date(),
              updatedBy: userId,
            },
          });
        }
      }

      // إنشاء سجلات تاريخ الأسعار
      if (priceHistoryData.length > 0) {
        await prisma.productPriceHistory.createMany({
          data: priceHistoryData,
        });
      }

      // إبطال الكاش
      await this.invalidateProductCache(tenantId);

      // تسجيل التدقيق
      await this.auditService.log({
        userId,
        action: 'bulk_price_update',
        entity: 'product',
        metadata: {
          count: updates.length,
          updates,
        },
        tenantId,
      });

      this.logger.info('Bulk price update completed', {
        count: updates.length,
        userId,
      });
    } catch (error) {
      this.logger.error('Error in bulk price update', error);
      throw error;
    }
  }

  async getProductsByCategory(
    categoryId: string,
    tenantId: string
  ): Promise<any[]> {
    try {
      const products = await prisma.product.findMany({
        where: {
          categoryId,
          tenantId,
          isDeleted: false,
          isActive: true,
        },
        include: {
          unit: true,
          inventory: {
            select: {
              quantity: true,
            },
          },
        },
      });

      return products;
    } catch (error) {
      this.logger.error('Error getting products by category', error);
      throw error;
    }
  }

  async getLowStockProducts(tenantId: string): Promise<any[]> {
    try {
      const products = await prisma.product.findMany({
        where: {
          tenantId,
          isDeleted: false,
          isActive: true,
          trackInventory: true,
        },
        include: {
          category: true,
          unit: true,
          inventory: {
            include: {
              warehouse: true,
            },
          },
        },
      });

      // فلترة المنتجات منخفضة المخزون
      const lowStockProducts = products.filter(product => {
        const totalQuantity = product.inventory.reduce(
          (sum: number, inv: any) => sum + inv.quantity,
          0
        );
        return totalQuantity <= product.reorderLevel;
      });

      return lowStockProducts;
    } catch (error) {
      this.logger.error('Error getting low stock products', error);
      throw error;
    }
  }

  async getProductStatistics(tenantId: string): Promise<{
    totalProducts: number;
    activeProducts: number;
    lowStockProducts: number;
    outOfStockProducts: number;
    totalValue: number;
    categoryBreakdown: any[];
  }> {
    try {
      const [total, active, products] = await Promise.all([
        prisma.product.count({
          where: { tenantId, isDeleted: false },
        }),
        prisma.product.count({
          where: { tenantId, isDeleted: false, isActive: true },
        }),
        prisma.product.findMany({
          where: {
            tenantId,
            isDeleted: false,
            trackInventory: true,
          },
          include: {
            inventory: true,
          },
        }),
      ]);

      let lowStock = 0;
      let outOfStock = 0;
      let totalValue = 0;

      products.forEach((product) => {
        const totalQuantity = product.inventory.reduce(
          (sum: number, inv: any) => sum + inv.quantity,
          0
        );
        
        if (totalQuantity === 0) {
          outOfStock++;
        } else if (totalQuantity <= product.reorderLevel) {
          lowStock++;
        }
        
        totalValue += totalQuantity * product.cost;
      });

      // تحليل حسب الفئات
      const categoryBreakdown = await prisma.product.groupBy({
        by: ['categoryId'],
        where: {
          tenantId,
          isDeleted: false,
        },
        _count: {
          id: true,
        },
      });

      // إضافة أسماء الفئات
      const categoriesWithNames = await Promise.all(
        categoryBreakdown.map(async (item) => {
          const category = await prisma.category.findUnique({
            where: { id: item.categoryId },
          });
          return {
            categoryId: item.categoryId,
            categoryName: category?.name || 'غير محدد',
            count: item._count.id,
          };
        })
      );

      return {
        totalProducts: total,
        activeProducts: active,
        lowStockProducts: lowStock,
        outOfStockProducts: outOfStock,
        totalValue,
        categoryBreakdown: categoriesWithNames,
      };
    } catch (error) {
      this.logger.error('Error getting product statistics', error);
      throw error;
    }
  }

  async exportProducts(
    format: 'excel' | 'csv',
    filters: ProductListParams
  ): Promise<Buffer> {
    try {
      // الحصول على البيانات
      const { products } = await this.getProducts({
        ...filters,
        limit: 10000, // حد أقصى للتصدير
      });

      if (format === 'excel') {
        return this.exportToExcel(products);
      } else {
        return this.exportToCSV(products);
      }
    } catch (error) {
      this.logger.error('Error exporting products', error);
      throw error;
    }
  }

  private exportToExcel(products: any[]): Buffer {
    const worksheet = xlsx.utils.json_to_sheet(
      products.map((p) => ({
        'رمز المنتج': p.code,
        'SKU': p.sku,
        'الباركود': p.barcode || '',
        'الاسم': p.name,
        'الاسم بالإنجليزية': p.nameEn || '',
        'الفئة': p.category?.name || '',
        'النوع': p.type === 'product' ? 'منتج' : 'خدمة',
        'الوحدة': p.unit?.name || '',
        'السعر': p.price,
        'التكلفة': p.cost,
        'معدل الضريبة': p.taxRate,
        'الكمية المتاحة': p.inventory?.reduce((sum: number, inv: any) => sum + inv.quantity, 0) || 0,
        'حد إعادة الطلب': p.reorderLevel,
        'تتبع المخزون': p.trackInventory ? 'نعم' : 'لا',
        'السماح بالطلب المسبق': p.allowBackorder ? 'نعم' : 'لا',
        'نشط': p.isActive ? 'نعم' : 'لا',
        'تاريخ الإنشاء': new Date(p.createdAt).toLocaleDateString('ar-SA'),
      }))
    );

    const workbook = xlsx.utils.book_new();
    xlsx.utils.book_append_sheet(workbook, worksheet, 'المنتجات');

    return xlsx.write(workbook, { type: 'buffer', bookType: 'xlsx' });
  }

  private exportToCSV(products: any[]): Buffer {
    const csvStringifier = createObjectCsvStringifier({
      header: [
        { id: 'code', title: 'رمز المنتج' },
        { id: 'sku', title: 'SKU' },
        { id: 'barcode', title: 'الباركود' },
        { id: 'name', title: 'الاسم' },
        { id: 'nameEn', title: 'الاسم بالإنجليزية' },
        { id: 'category', title: 'الفئة' },
        { id: 'type', title: 'النوع' },
        { id: 'unit', title: 'الوحدة' },
        { id: 'price', title: 'السعر' },
        { id: 'cost', title: 'التكلفة' },
        { id: 'taxRate', title: 'معدل الضريبة' },
        { id: 'quantity', title: 'الكمية المتاحة' },
        { id: 'reorderLevel', title: 'حد إعادة الطلب' },
        { id: 'trackInventory', title: 'تتبع المخزون' },
        { id: 'allowBackorder', title: 'السماح بالطلب المسبق' },
        { id: 'isActive', title: 'نشط' },
        { id: 'createdAt', title: 'تاريخ الإنشاء' },
      ],
    });

    const records = products.map((p) => ({
      code: p.code,
      sku: p.sku,
      barcode: p.barcode || '',
      name: p.name,
      nameEn: p.nameEn || '',
      category: p.category?.name || '',
      type: p.type === 'product' ? 'منتج' : 'خدمة',
      unit: p.unit?.name || '',
      price: p.price,
      cost: p.cost,
      taxRate: p.taxRate,
      quantity: p.inventory?.reduce((sum: number, inv: any) => sum + inv.quantity, 0) || 0,
      reorderLevel: p.reorderLevel,
      trackInventory: p.trackInventory ? 'نعم' : 'لا',
      allowBackorder: p.allowBackorder ? 'نعم' : 'لا',
      isActive: p.isActive ? 'نعم' : 'لا',
      createdAt: new Date(p.createdAt).toLocaleDateString('ar-SA'),
    }));

    const header = csvStringifier.getHeaderString();
    const body = csvStringifier.stringifyRecords(records);
    
    return Buffer.from(header + body, 'utf8');
  }

  async importProducts(
    fileBuffer: Buffer,
    format: 'excel' | 'csv',
    userId: string,
    tenantId: string
  ): Promise<{
    success: number;
    failed: number;
    errors: Array<{ row: number; error: string }>;
  }> {
    try {
      let products: any[] = [];
      
      if (format === 'excel') {
        const workbook = xlsx.read(fileBuffer);
        const worksheet = workbook.Sheets[workbook.SheetNames[0]];
        products = xlsx.utils.sheet_to_json(worksheet);
      } else {
        const csvData = fileBuffer.toString('utf8');
        products = await new Promise((resolve, reject) => {
          csvParse(csvData, { columns: true }, (err, records) => {
            if (err) reject(err);
            else resolve(records);
          });
        });
      }

      const results = {
        success: 0,
        failed: 0,
        errors: [] as Array<{ row: number; error: string }>,
      };

      for (let i = 0; i < products.length; i++) {
        try {
          const row = products[i];
          const productData: CreateProductData = {
            name: row['الاسم'] || row.name,
            nameEn: row['الاسم بالإنجليزية'] || row.nameEn,
            sku: row['SKU'] || row.sku,
            barcode: row['الباركود'] || row.barcode,
            type: (row['النوع'] || row.type) === 'خدمة' ? 'service' : 'product',
            price: parseFloat(row['السعر'] || row.price || '0'),
            cost: parseFloat(row['التكلفة'] || row.cost || '0'),
            taxRate: parseFloat(row['معدل الضريبة'] || row.taxRate || '0'),
            reorderLevel: parseInt(row['حد إعادة الطلب'] || row.reorderLevel || '0'),
            trackInventory: (row['تتبع المخزون'] || row.trackInventory) === 'نعم',
            allowBackorder: (row['السماح بالطلب المسبق'] || row.allowBackorder) === 'نعم',
            isActive: (row['نشط'] || row.isActive) !== 'لا',
            categoryId: '', // سيتم تحديده لاحقاً
            unitId: '', // سيتم تحديده لاحقاً
            createdBy: userId,
            tenantId,
          };

          // البحث عن الفئة والوحدة
          const categoryName = row['الفئة'] || row.category;
          if (categoryName) {
            const category = await prisma.category.findFirst({
              where: {
                name: categoryName,
                tenantId,
              },
            });
            if (category) {
              productData.categoryId = category.id;
            }
          }

          const unitName = row['الوحدة'] || row.unit;
          if (unitName) {
            const unit = await prisma.unit.findFirst({
              where: {
                name: unitName,
                tenantId,
              },
            });
            if (unit) {
              productData.unitId = unit.id;
            }
          }

          await this.createProduct(productData, userId);
          results.success++;
        } catch (error: any) {
          results.failed++;
          results.errors.push({
            row: i + 2, // إضافة 1 للصف الأول (العناوين) و1 للفهرسة من 1
            error: error.message || 'خطأ غير معروف',
          });
        }
      }

      // تسجيل التدقيق
      await this.auditService.log({
        userId,
        action: 'products_imported',
        entity: 'product',
        metadata: {
          total: products.length,
          success: results.success,
          failed: results.failed,
          format,
        },
        tenantId,
      });

      return results;
    } catch (error) {
      this.logger.error('Error importing products', error);
      throw error;
    }
  }

  private generateProductCode(): string {
    const prefix = 'PRD';
    const timestamp = Date.now().toString(36).toUpperCase();
    const random = Math.random().toString(36).substring(2, 5).toUpperCase();
    return `${prefix}-${timestamp}-${random}`;
  }

  private async invalidateProductCache(tenantId: string): Promise<void> {
    try {
      const pattern = `products:${tenantId}:*`;
      const keys = await this.redisService.keys(pattern);
      if (keys.length > 0) {
        await this.redisService.del(...keys);
      }
    } catch (error) {
      this.logger.error('Error invalidating product cache', error);
    }
  }
}

export default new ProductService();