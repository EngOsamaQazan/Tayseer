import { Router } from 'express';
import { InventoryController } from './inventory.controller';
import { authenticate } from '@/middleware/auth';
import { authorize } from '@/middleware/authorize';
import { validateRequest } from '@/middleware/validateRequest';
import { inventoryValidation } from './inventory.validation';
import { upload } from '@/middleware/upload';

const router = Router();

// جميع المسارات تتطلب المصادقة
router.use(authenticate);

// إنشاء معاملة مخزون
router.post(
  '/transactions',
  authorize(['inventory:create']),
  validateRequest(inventoryValidation.createTransaction),
  InventoryController.createTransaction
);

// نقل المخزون بين المستودعات
router.post(
  '/transfer',
  authorize(['inventory:transfer']),
  validateRequest(inventoryValidation.transferInventory),
  InventoryController.transferInventory
);

// جرد المخزون
router.post(
  '/stock-take',
  authorize(['inventory:stock-take']),
  validateRequest(inventoryValidation.stockTake),
  InventoryController.performStockTake
);

// حجز كمية من المخزون
router.post(
  '/reserve',
  authorize(['inventory:reserve']),
  validateRequest(inventoryValidation.reserveInventory),
  InventoryController.reserveInventory
);

// إلغاء حجز كمية من المخزون
router.post(
  '/unreserve',
  authorize(['inventory:reserve']),
  validateRequest(inventoryValidation.unreserveInventory),
  InventoryController.unreserveInventory
);

// الحصول على إحصائيات المخزون
router.get(
  '/statistics',
  authorize(['inventory:read']),
  InventoryController.getStatistics
);

// الحصول على تقرير حركة المخزون
router.get(
  '/reports/movement',
  authorize(['inventory:reports']),
  validateRequest(inventoryValidation.stockMovementReport, 'query'),
  InventoryController.getStockMovementReport
);

// الحصول على تقرير تقييم المخزون
router.get(
  '/reports/valuation',
  authorize(['inventory:reports']),
  InventoryController.getValuationReport
);

// تصدير بيانات المخزون
router.get(
  '/export',
  authorize(['inventory:export']),
  InventoryController.exportInventory
);

// استيراد بيانات المخزون
router.post(
  '/import',
  authorize(['inventory:import']),
  upload.single('file'),
  validateRequest(inventoryValidation.importInventory),
  InventoryController.importInventory
);

// الحصول على المنتجات منخفضة المخزون
router.get(
  '/low-stock',
  authorize(['inventory:read']),
  InventoryController.getLowStockItems
);

// الحصول على سجل معاملات المخزون
router.get(
  '/transactions',
  authorize(['inventory:read']),
  validateRequest(inventoryValidation.transactionHistory, 'query'),
  InventoryController.getTransactionHistory
);

// تحديث مستويات إعادة الطلب
router.put(
  '/reorder-levels',
  authorize(['inventory:update']),
  validateRequest(inventoryValidation.updateReorderLevels),
  InventoryController.updateReorderLevels
);

// تحديث دفعي للمخزون
router.put(
  '/batch-update',
  authorize(['inventory:update']),
  validateRequest(inventoryValidation.batchUpdate),
  InventoryController.updateReorderLevels
);

// البحث في المخزون
router.get(
  '/search',
  authorize(['inventory:read']),
  validateRequest(inventoryValidation.searchInventory, 'query'),
  InventoryController.getLowStockItems
);

// إنشاء تقرير مخزون
router.post(
  '/reports',
  authorize(['inventory:reports']),
  validateRequest(inventoryValidation.inventoryReport),
  InventoryController.getStatistics
);

export default router;