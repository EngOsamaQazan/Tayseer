import { Router } from 'express';
import { reportController } from './report.controller';
import { authMiddleware } from '../../middleware/auth.middleware';
import { validateRequest } from '../../middleware/validation.middleware';
import { reportValidation } from './report.validation';

const router = Router();

// تطبيق middleware المصادقة على جميع المسارات
router.use(authMiddleware);

// مسارات التقارير
router.get('/financial', reportController.getFinancialReport);
router.get('/sales', reportController.getSalesReport);
router.get('/inventory', reportController.getInventoryReport);
router.get('/customers', reportController.getCustomerReport);
router.get('/contracts', reportController.getContractReport);
router.get('/performance', reportController.getPerformanceReport);

// تقارير مخصصة
router.post('/custom', validateRequest(reportValidation.customReport), reportController.generateCustomReport);

// تصدير التقارير
router.get('/export/:type', reportController.exportReport);

export default router;