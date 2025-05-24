import { Router } from 'express';
import productController from './product.controller';
import { authenticate } from '../../shared/middleware/auth';
import { authorize } from '../../shared/middleware/authorize';
import { validateRequest } from '../../shared/middleware/validateRequest';
import { upload } from '../../shared/middleware/upload';
import { productValidation } from './product.validation';

const router = Router();

// جميع المسارات تتطلب المصادقة
router.use(authenticate);

// مسارات المنتجات الأساسية
router.post(
  '/',
  authorize('products:create'),
  validateRequest(productValidation.create),
  productController.create
);

router.get(
  '/',
  authorize('products:read'),
  productController.list
);

router.get(
  '/statistics',
  authorize('products:read'),
  productController.getStatistics
);

router.get(
  '/low-stock',
  authorize('products:read'),
  productController.getLowStock
);

router.get(
  '/export',
  authorize('products:export'),
  productController.export
);

router.post(
  '/import',
  authorize('products:import'),
  upload.single('file'),
  productController.import
);

router.get(
  '/category/:categoryId',
  authorize('products:read'),
  productController.getByCategory
);

router.get(
  '/:id',
  authorize('products:read'),
  productController.getById
);

router.put(
  '/:id',
  authorize('products:update'),
  validateRequest(productValidation.update),
  productController.update
);

router.delete(
  '/:id',
  authorize('products:delete'),
  productController.delete
);

router.post(
  '/:id/upload-image',
  authorize('products:update'),
  upload.single('image'),
  productController.uploadImage
);

// مسارات متغيرات المنتجات
router.post(
  '/:productId/variants',
  authorize('products:update'),
  validateRequest(productValidation.createVariant),
  productController.createVariant
);

router.put(
  '/variants/:variantId',
  authorize('products:update'),
  validateRequest(productValidation.updateVariant),
  productController.updateVariant
);

router.delete(
  '/variants/:variantId',
  authorize('products:update'),
  productController.deleteVariant
);

// تحديث الأسعار بشكل جماعي
router.post(
  '/bulk/update-prices',
  authorize('products:update'),
  validateRequest(productValidation.bulkUpdatePrices),
  productController.bulkUpdatePrices
);

export default router;