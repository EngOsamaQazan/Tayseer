import { Request, Response } from 'express';
import { z } from 'zod';
import productService from './product.service';
import { productValidation } from './product.validation';
import { ApiError } from '../../shared/errors/ApiError';
import { ApiResponse } from '../../shared/utils/ApiResponse';
import logger from '../../shared/utils/logger';
import { auditLogUtil } from '../../shared/utils/auditLog';
import { notificationUtil } from '../../shared/utils/notification';
import { cacheUtil } from '../../shared/utils/cache';
import { uploadUtil } from '../../shared/utils/upload';
import { AuthRequest } from '../../shared/types/auth';

export class ProductController {
  async create(req: AuthRequest, res: Response) {
    try {
      const validatedData = productValidation.create.parse(req.body);
      
      const product = await productService.createProduct(
        {
          ...validatedData,
          createdBy: req.user!.id,
          tenantId: req.user!.tenantId,
        },
        req.user!.id
      );

      res.status(201).json(
        ApiResponse.success(product, 'تم إنشاء المنتج بنجاح')
      );
    } catch (error) {
      if (error instanceof z.ZodError) {
        return res.status(400).json(
          ApiResponse.error('بيانات غير صالحة', 400, error.errors)
        );
      }
      logger.error('Error creating product', error);
      res.status(500).json(
        ApiResponse.error('حدث خطأ في إنشاء المنتج')
      );
    }
  }

  async update(req: AuthRequest, res: Response) {
    try {
      const { id } = req.params;
      const validatedData = productValidation.update.parse(req.body);
      
      const product = await productService.updateProduct(
        id,
        validatedData,
        req.user!.id,
        req.user!.tenantId
      );

      res.json(
        ApiResponse.success(product, 'تم تحديث المنتج بنجاح')
      );
    } catch (error) {
      if (error instanceof z.ZodError) {
        return res.status(400).json(
          ApiResponse.error('بيانات غير صالحة', 400, error.errors)
        );
      }
      if (error instanceof ApiError) {
        return res.status(error.statusCode).json(
          ApiResponse.error(error.message, error.statusCode)
        );
      }
      logger.error('Error updating product', error);
      res.status(500).json(
        ApiResponse.error('حدث خطأ في تحديث المنتج')
      );
    }
  }

  async list(req: AuthRequest, res: Response) {
    try {
      const {
        page = '1',
        limit = '10',
        search,
        categoryId,
        type,
        minPrice,
        maxPrice,
        inStock,
        sortBy = 'createdAt',
        sortOrder = 'desc',
      } = req.query;

      const pageNum = parseInt(page as string);
      const limitNum = parseInt(limit as string);

      const cacheKey = cacheUtil.generateKey('products', {
        tenantId: req.user!.tenantId,
        page: pageNum,
        limit: limitNum,
        search,
        categoryId,
        type,
        minPrice,
        maxPrice,
        inStock,
        sortBy,
        sortOrder,
      });

      const cached = await cacheUtil.get(cacheKey);
      if (cached) {
        return res.json(cached);
      }

      const products = await productService.getProducts(
        req.user!.tenantId,
        {
          page: pageNum,
          limit: limitNum,
          search: search as string,
          categoryId: categoryId as string,
          type: type as 'product' | 'service',
          minPrice: minPrice ? parseFloat(minPrice as string) : undefined,
          maxPrice: maxPrice ? parseFloat(maxPrice as string) : undefined,
          inStock: inStock === 'true',
          sortBy: sortBy as string,
          sortOrder: sortOrder as 'asc' | 'desc',
        }
      );

      const response = ApiResponse.success(products);
      await cacheUtil.set(cacheKey, response, 300); // 5 دقائق

      res.json(response);
    } catch (error) {
      logger.error('Error listing products', error);
      res.status(500).json(
        ApiResponse.error('حدث خطأ في جلب المنتجات')
      );
    }
  }

  async getById(req: AuthRequest, res: Response) {
    try {
      const { id } = req.params;
      
      const product = await productService.getProductById(
        id,
        req.user!.tenantId
      );

      res.json(ApiResponse.success(product));
    } catch (error) {
      if (error instanceof ApiError) {
        return res.status(error.statusCode).json(
          ApiResponse.error(error.message, error.statusCode)
        );
      }
      logger.error('Error getting product', error);
      res.status(500).json(
        ApiResponse.error('حدث خطأ في جلب المنتج')
      );
    }
  }

  async delete(req: AuthRequest, res: Response) {
    try {
      const { id } = req.params;
      
      await productService.deleteProduct(
        id,
        req.user!.id,
        req.user!.tenantId
      );

      res.json(
        ApiResponse.success(null, 'تم حذف المنتج بنجاح')
      );
    } catch (error) {
      if (error instanceof ApiError) {
        return res.status(error.statusCode).json(
          ApiResponse.error(error.message, error.statusCode)
        );
      }
      logger.error('Error deleting product', error);
      res.status(500).json(
        ApiResponse.error('حدث خطأ في حذف المنتج')
      );
    }
  }

  async createVariant(req: AuthRequest, res: Response) {
    try {
      const { productId } = req.params;
      const validatedData = productValidation.createVariant.parse(req.body);
      
      const variant = await productService.createProductVariant(
        productId,
        {
          ...validatedData,
          tenantId: req.user!.tenantId,
        },
        req.user!.id,
        req.user!.tenantId
      );

      res.status(201).json(
        ApiResponse.success(variant, 'تم إنشاء متغير المنتج بنجاح')
      );
    } catch (error) {
      if (error instanceof z.ZodError) {
        return res.status(400).json(
          ApiResponse.error('بيانات غير صالحة', 400, error.errors)
        );
      }
      if (error instanceof ApiError) {
        return res.status(error.statusCode).json(
          ApiResponse.error(error.message, error.statusCode)
        );
      }
      logger.error('Error creating product variant', error);
      res.status(500).json(
        ApiResponse.error('حدث خطأ في إنشاء متغير المنتج')
      );
    }
  }

  async updateVariant(req: AuthRequest, res: Response) {
    try {
      const { variantId } = req.params;
      const validatedData = productValidation.updateVariant.parse(req.body);
      
      const variant = await productService.updateProductVariant(
        variantId,
        validatedData,
        req.user!.id,
        req.user!.tenantId
      );

      res.json(
        ApiResponse.success(variant, 'تم تحديث متغير المنتج بنجاح')
      );
    } catch (error) {
      if (error instanceof z.ZodError) {
        return res.status(400).json(
          ApiResponse.error('بيانات غير صالحة', 400, error.errors)
        );
      }
      if (error instanceof ApiError) {
        return res.status(error.statusCode).json(
          ApiResponse.error(error.message, error.statusCode)
        );
      }
      logger.error('Error updating product variant', error);
      res.status(500).json(
        ApiResponse.error('حدث خطأ في تحديث متغير المنتج')
      );
    }
  }

  async deleteVariant(req: AuthRequest, res: Response) {
    try {
      const { variantId } = req.params;
      
      await productService.deleteProductVariant(
        variantId,
        req.user!.id,
        req.user!.tenantId
      );

      res.json(
        ApiResponse.success(null, 'تم حذف متغير المنتج بنجاح')
      );
    } catch (error) {
      if (error instanceof ApiError) {
        return res.status(error.statusCode).json(
          ApiResponse.error(error.message, error.statusCode)
        );
      }
      logger.error('Error deleting product variant', error);
      res.status(500).json(
        ApiResponse.error('حدث خطأ في حذف متغير المنتج')
      );
    }
  }

  async bulkUpdatePrices(req: AuthRequest, res: Response) {
    try {
      const validatedData = productValidation.bulkUpdatePrices.parse(req.body);
      
      const results = await productService.bulkUpdatePrices(
        validatedData.updates,
        req.user!.id,
        req.user!.tenantId
      );

      res.json(
        ApiResponse.success(results, 'تم تحديث الأسعار بنجاح')
      );
    } catch (error) {
      if (error instanceof z.ZodError) {
        return res.status(400).json(
          ApiResponse.error('بيانات غير صالحة', 400, error.errors)
        );
      }
      logger.error('Error bulk updating prices', error);
      res.status(500).json(
        ApiResponse.error('حدث خطأ في تحديث الأسعار')
      );
    }
  }

  async getByCategory(req: AuthRequest, res: Response) {
    try {
      const { categoryId } = req.params;
      
      const products = await productService.getProductsByCategory(
        categoryId,
        req.user!.tenantId
      );

      res.json(ApiResponse.success(products));
    } catch (error) {
      logger.error('Error getting products by category', error);
      res.status(500).json(
        ApiResponse.error('حدث خطأ في جلب المنتجات')
      );
    }
  }

  async getLowStock(req: AuthRequest, res: Response) {
    try {
      const products = await productService.getLowStockProducts(
        req.user!.tenantId
      );

      res.json(ApiResponse.success(products));
    } catch (error) {
      logger.error('Error getting low stock products', error);
      res.status(500).json(
        ApiResponse.error('حدث خطأ في جلب المنتجات منخفضة المخزون')
      );
    }
  }

  async getStatistics(req: AuthRequest, res: Response) {
    try {
      const stats = await productService.getProductStatistics(
        req.user!.tenantId
      );

      res.json(ApiResponse.success(stats));
    } catch (error) {
      logger.error('Error getting product statistics', error);
      res.status(500).json(
        ApiResponse.error('حدث خطأ في جلب إحصائيات المنتجات')
      );
    }
  }

  async export(req: AuthRequest, res: Response) {
    try {
      const { format = 'excel' } = req.query;
      
      const result = await productService.exportProducts(
        req.user!.tenantId,
        format as 'excel' | 'csv'
      );

      res.setHeader('Content-Type', result.mimeType);
      res.setHeader(
        'Content-Disposition',
        `attachment; filename="${result.filename}"`
      );
      res.send(result.buffer);
    } catch (error) {
      logger.error('Error exporting products', error);
      res.status(500).json(
        ApiResponse.error('حدث خطأ في تصدير المنتجات')
      );
    }
  }

  async import(req: AuthRequest, res: Response) {
    try {
      if (!req.file) {
        return res.status(400).json(
          ApiResponse.error('الرجاء رفع ملف')
        );
      }

      const format = req.file.mimetype.includes('excel') || 
                    req.file.originalname.endsWith('.xlsx') ? 'excel' : 'csv';
      
      const results = await productService.importProducts(
        req.file.buffer,
        format,
        req.user!.id,
        req.user!.tenantId
      );

      res.json(
        ApiResponse.success(results, 'تم استيراد المنتجات')
      );
    } catch (error) {
      logger.error('Error importing products', error);
      res.status(500).json(
        ApiResponse.error('حدث خطأ في استيراد المنتجات')
      );
    }
  }

  async uploadImage(req: AuthRequest, res: Response) {
    try {
      const { id } = req.params;
      
      if (!req.file) {
        return res.status(400).json(
          ApiResponse.error('الرجاء رفع صورة')
        );
      }

      // التحقق من وجود المنتج
      const product = await productService.getProductById(
        id,
        req.user!.tenantId
      );

      // رفع الصورة
      const uploadResult = await uploadUtil.uploadFile(
        req.file,
        {
          folder: `products/${req.user!.tenantId}`,
          allowedTypes: ['image/jpeg', 'image/png', 'image/webp'],
          maxSize: 5 * 1024 * 1024, // 5MB
        }
      );

      // تحديث المنتج بمسار الصورة
      const updatedProduct = await productService.updateProduct(
        id,
        { imageUrl: uploadResult.url },
        req.user!.id,
        req.user!.tenantId
      );

      res.json(
        ApiResponse.success(updatedProduct, 'تم رفع الصورة بنجاح')
      );
    } catch (error) {
      if (error instanceof ApiError) {
        return res.status(error.statusCode).json(
          ApiResponse.error(error.message, error.statusCode)
        );
      }
      logger.error('Error uploading product image', error);
      res.status(500).json(
        ApiResponse.error('حدث خطأ في رفع الصورة')
      );
    }
  }
}

export default new ProductController();