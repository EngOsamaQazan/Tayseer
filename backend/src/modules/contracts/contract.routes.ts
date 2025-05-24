import { Router } from 'express';
import ContractController from './contract.controller';
import { authenticate, authorize } from '../../middleware/auth.middleware';
import { validateRequest } from '../../middleware/validation.middleware';
import { rateLimiter } from '../../middleware/rateLimit.middleware';
import { upload } from '../../middleware/upload.middleware';
import * as contractValidation from './contract.validation';

const router = Router();

/**
 * @swagger
 * /api/v1/contracts:
 *   post:
 *     summary: إنشاء عقد جديد
 *     tags: [Contracts]
 *     security:
 *       - bearerAuth: []
 *     requestBody:
 *       required: true
 *       content:
 *         application/json:
 *           schema:
 *             $ref: '#/components/schemas/CreateContract'
 *     responses:
 *       201:
 *         description: تم إنشاء العقد بنجاح
 *       400:
 *         description: بيانات غير صحيحة
 *       401:
 *         description: غير مصرح
 */
router.post(
  '/',
  authenticate,
  authorize(['admin', 'manager', 'employee']),
  validateRequest(contractValidation.createContractSchema),
  ContractController.create
);

/**
 * @swagger
 * /api/v1/contracts:
 *   get:
 *     summary: الحصول على قائمة العقود
 *     tags: [Contracts]
 *     security:
 *       - bearerAuth: []
 *     parameters:
 *       - in: query
 *         name: page
 *         schema:
 *           type: integer
 *           default: 1
 *       - in: query
 *         name: limit
 *         schema:
 *           type: integer
 *           default: 10
 *       - in: query
 *         name: status
 *         schema:
 *           type: string
 *           enum: [draft, active, suspended, completed, cancelled]
 *       - in: query
 *         name: customerId
 *         schema:
 *           type: string
 *       - in: query
 *         name: search
 *         schema:
 *           type: string
 *       - in: query
 *         name: sortBy
 *         schema:
 *           type: string
 *           default: createdAt
 *       - in: query
 *         name: sortOrder
 *         schema:
 *           type: string
 *           enum: [asc, desc]
 *           default: desc
 *     responses:
 *       200:
 *         description: قائمة العقود
 *       401:
 *         description: غير مصرح
 */
router.get(
  '/',
  authenticate,
  authorize(['admin', 'manager', 'employee', 'viewer']),
  validateRequest(contractValidation.contractListSchema),
  rateLimiter({ windowMs: 60000, max: 100 }),
  ContractController.list
);

/**
 * @swagger
 * /api/v1/contracts/statistics:
 *   get:
 *     summary: الحصول على إحصائيات العقود
 *     tags: [Contracts]
 *     security:
 *       - bearerAuth: []
 *     parameters:
 *       - in: query
 *         name: startDate
 *         schema:
 *           type: string
 *           format: date
 *       - in: query
 *         name: endDate
 *         schema:
 *           type: string
 *           format: date
 *       - in: query
 *         name: customerId
 *         schema:
 *           type: string
 *       - in: query
 *         name: status
 *         schema:
 *           type: string
 *           enum: [draft, active, suspended, completed, cancelled]
 *     responses:
 *       200:
 *         description: إحصائيات العقود
 *       401:
 *         description: غير مصرح
 */
router.get(
  '/statistics',
  authenticate,
  authorize(['admin', 'manager']),
  validateRequest(contractValidation.contractStatisticsSchema),
  ContractController.getStatistics
);

/**
 * @swagger
 * /api/v1/contracts/export:
 *   get:
 *     summary: تصدير العقود
 *     tags: [Contracts]
 *     security:
 *       - bearerAuth: []
 *     parameters:
 *       - in: query
 *         name: format
 *         schema:
 *           type: string
 *           enum: [xlsx, csv]
 *           default: xlsx
 *       - in: query
 *         name: status
 *         schema:
 *           type: string
 *           enum: [draft, active, suspended, completed, cancelled]
 *       - in: query
 *         name: customerId
 *         schema:
 *           type: string
 *       - in: query
 *         name: startDate
 *         schema:
 *           type: string
 *           format: date
 *       - in: query
 *         name: endDate
 *         schema:
 *           type: string
 *           format: date
 *     responses:
 *       200:
 *         description: ملف التصدير
 *         content:
 *           application/vnd.openxmlformats-officedocument.spreadsheetml.sheet:
 *             schema:
 *               type: string
 *               format: binary
 *           text/csv:
 *             schema:
 *               type: string
 *               format: binary
 *       401:
 *         description: غير مصرح
 */
router.get(
  '/export',
  authenticate,
  authorize(['admin', 'manager']),
  validateRequest(contractValidation.exportContractsSchema),
  ContractController.export
);

/**
 * @swagger
 * /api/v1/contracts/{id}:
 *   get:
 *     summary: الحصول على تفاصيل عقد
 *     tags: [Contracts]
 *     security:
 *       - bearerAuth: []
 *     parameters:
 *       - in: path
 *         name: id
 *         required: true
 *         schema:
 *           type: string
 *     responses:
 *       200:
 *         description: تفاصيل العقد
 *       404:
 *         description: العقد غير موجود
 *       401:
 *         description: غير مصرح
 */
router.get(
  '/:id',
  authenticate,
  authorize(['admin', 'manager', 'employee', 'viewer']),
  validateRequest(contractValidation.contractIdSchema),
  ContractController.getById
);

/**
 * @swagger
 * /api/v1/contracts/{id}:
 *   put:
 *     summary: تحديث عقد
 *     tags: [Contracts]
 *     security:
 *       - bearerAuth: []
 *     parameters:
 *       - in: path
 *         name: id
 *         required: true
 *         schema:
 *           type: string
 *     requestBody:
 *       required: true
 *       content:
 *         application/json:
 *           schema:
 *             $ref: '#/components/schemas/UpdateContract'
 *     responses:
 *       200:
 *         description: تم تحديث العقد بنجاح
 *       400:
 *         description: بيانات غير صحيحة
 *       404:
 *         description: العقد غير موجود
 *       401:
 *         description: غير مصرح
 */
router.put(
  '/:id',
  authenticate,
  authorize(['admin', 'manager']),
  validateRequest(contractValidation.updateContractSchema),
  ContractController.update
);

/**
 * @swagger
 * /api/v1/contracts/{id}:
 *   delete:
 *     summary: حذف عقد
 *     tags: [Contracts]
 *     security:
 *       - bearerAuth: []
 *     parameters:
 *       - in: path
 *         name: id
 *         required: true
 *         schema:
 *           type: string
 *     responses:
 *       200:
 *         description: تم حذف العقد بنجاح
 *       400:
 *         description: لا يمكن حذف العقد
 *       404:
 *         description: العقد غير موجود
 *       401:
 *         description: غير مصرح
 */
router.delete(
  '/:id',
  authenticate,
  authorize(['admin']),
  validateRequest(contractValidation.contractIdSchema),
  ContractController.delete
);

/**
 * @swagger
 * /api/v1/contracts/{id}/activate:
 *   put:
 *     summary: تفعيل عقد
 *     tags: [Contracts]
 *     security:
 *       - bearerAuth: []
 *     parameters:
 *       - in: path
 *         name: id
 *         required: true
 *         schema:
 *           type: string
 *     responses:
 *       200:
 *         description: تم تفعيل العقد بنجاح
 *       400:
 *         description: لا يمكن تفعيل العقد
 *       404:
 *         description: العقد غير موجود
 *       401:
 *         description: غير مصرح
 */
router.put(
  '/:id/activate',
  authenticate,
  authorize(['admin', 'manager']),
  validateRequest(contractValidation.contractIdSchema),
  ContractController.activate
);

/**
 * @swagger
 * /api/v1/contracts/{id}/cancel:
 *   put:
 *     summary: إلغاء عقد
 *     tags: [Contracts]
 *     security:
 *       - bearerAuth: []
 *     parameters:
 *       - in: path
 *         name: id
 *         required: true
 *         schema:
 *           type: string
 *     requestBody:
 *       required: true
 *       content:
 *         application/json:
 *           schema:
 *             type: object
 *             properties:
 *               reason:
 *                 type: string
 *                 description: سبب الإلغاء
 *             required:
 *               - reason
 *     responses:
 *       200:
 *         description: تم إلغاء العقد بنجاح
 *       400:
 *         description: لا يمكن إلغاء العقد
 *       404:
 *         description: العقد غير موجود
 *       401:
 *         description: غير مصرح
 */
router.put(
  '/:id/cancel',
  authenticate,
  authorize(['admin', 'manager']),
  validateRequest(contractValidation.cancelContractSchema),
  ContractController.cancel
);

/**
 * @swagger
 * /api/v1/contracts/{id}/renew:
 *   post:
 *     summary: تجديد عقد
 *     tags: [Contracts]
 *     security:
 *       - bearerAuth: []
 *     parameters:
 *       - in: path
 *         name: id
 *         required: true
 *         schema:
 *           type: string
 *     requestBody:
 *       required: true
 *       content:
 *         application/json:
 *           schema:
 *             $ref: '#/components/schemas/RenewContract'
 *     responses:
 *       201:
 *         description: تم تجديد العقد بنجاح
 *       400:
 *         description: لا يمكن تجديد العقد
 *       404:
 *         description: العقد غير موجود
 *       401:
 *         description: غير مصرح
 */
router.post(
  '/:id/renew',
  authenticate,
  authorize(['admin', 'manager']),
  validateRequest(contractValidation.renewContractSchema),
  ContractController.renew
);

/**
 * @swagger
 * /api/v1/contracts/{id}/timeline:
 *   get:
 *     summary: الحصول على الجدول الزمني للعقد
 *     tags: [Contracts]
 *     security:
 *       - bearerAuth: []
 *     parameters:
 *       - in: path
 *         name: id
 *         required: true
 *         schema:
 *           type: string
 *     responses:
 *       200:
 *         description: الجدول الزمني للعقد
 *       404:
 *         description: العقد غير موجود
 *       401:
 *         description: غير مصرح
 */
router.get(
  '/:id/timeline',
  authenticate,
  authorize(['admin', 'manager', 'employee', 'viewer']),
  validateRequest(contractValidation.contractIdSchema),
  ContractController.getTimeline
);

/**
 * @swagger
 * /api/v1/contracts/{id}/attachments:
 *   post:
 *     summary: رفع مرفق للعقد
 *     tags: [Contracts]
 *     security:
 *       - bearerAuth: []
 *     parameters:
 *       - in: path
 *         name: id
 *         required: true
 *         schema:
 *           type: string
 *     requestBody:
 *       required: true
 *       content:
 *         multipart/form-data:
 *           schema:
 *             type: object
 *             properties:
 *               file:
 *                 type: string
 *                 format: binary
 *               title:
 *                 type: string
 *               description:
 *                 type: string
 *               type:
 *                 type: string
 *                 enum: [contract_copy, amendment, annex, other]
 *             required:
 *               - file
 *               - title
 *               - type
 *     responses:
 *       201:
 *         description: تم رفع المرفق بنجاح
 *       400:
 *         description: بيانات غير صحيحة
 *       404:
 *         description: العقد غير موجود
 *       401:
 *         description: غير مصرح
 */
router.post(
  '/:id/attachments',
  authenticate,
  authorize(['admin', 'manager', 'employee']),
  upload.single('file'),
  validateRequest(contractValidation.uploadAttachmentSchema),
  ContractController.uploadAttachment
);

/**
 * @swagger
 * /api/v1/contracts/{id}/attachments:
 *   get:
 *     summary: الحصول على مرفقات العقد
 *     tags: [Contracts]
 *     security:
 *       - bearerAuth: []
 *     parameters:
 *       - in: path
 *         name: id
 *         required: true
 *         schema:
 *           type: string
 *     responses:
 *       200:
 *         description: قائمة المرفقات
 *       404:
 *         description: العقد غير موجود
 *       401:
 *         description: غير مصرح
 */
router.get(
  '/:id/attachments',
  authenticate,
  authorize(['admin', 'manager', 'employee', 'viewer']),
  validateRequest(contractValidation.contractIdSchema),
  ContractController.getAttachments
);

/**
 * @swagger
 * /api/v1/contracts/{id}/attachments/{attachmentId}:
 *   delete:
 *     summary: حذف مرفق من العقد
 *     tags: [Contracts]
 *     security:
 *       - bearerAuth: []
 *     parameters:
 *       - in: path
 *         name: id
 *         required: true
 *         schema:
 *           type: string
 *       - in: path
 *         name: attachmentId
 *         required: true
 *         schema:
 *           type: string
 *     responses:
 *       200:
 *         description: تم حذف المرفق بنجاح
 *       404:
 *         description: المرفق أو العقد غير موجود
 *       401:
 *         description: غير مصرح
 */
router.delete(
  '/:id/attachments/:attachmentId',
  authenticate,
  authorize(['admin', 'manager']),
  validateRequest(contractValidation.deleteAttachmentSchema),
  ContractController.deleteAttachment
);

/**
 * @swagger
 * /api/v1/contracts/{id}/items:
 *   post:
 *     summary: إضافة بند للعقد
 *     tags: [Contracts]
 *     security:
 *       - bearerAuth: []
 *     parameters:
 *       - in: path
 *         name: id
 *         required: true
 *         schema:
 *           type: string
 *     requestBody:
 *       required: true
 *       content:
 *         application/json:
 *           schema:
 *             $ref: '#/components/schemas/ContractItem'
 *     responses:
 *       201:
 *         description: تم إضافة البند بنجاح
 *       400:
 *         description: بيانات غير صحيحة
 *       404:
 *         description: العقد غير موجود
 *       401:
 *         description: غير مصرح
 */
router.post(
  '/:id/items',
  authenticate,
  authorize(['admin', 'manager']),
  validateRequest(contractValidation.addItemSchema),
  ContractController.addItem
);

/**
 * @swagger
 * /api/v1/contracts/{id}/items/{itemId}:
 *   put:
 *     summary: تحديث بند في العقد
 *     tags: [Contracts]
 *     security:
 *       - bearerAuth: []
 *     parameters:
 *       - in: path
 *         name: id
 *         required: true
 *         schema:
 *           type: string
 *       - in: path
 *         name: itemId
 *         required: true
 *         schema:
 *           type: string
 *     requestBody:
 *       required: true
 *       content:
 *         application/json:
 *           schema:
 *             $ref: '#/components/schemas/UpdateContractItem'
 *     responses:
 *       200:
 *         description: تم تحديث البند بنجاح
 *       400:
 *         description: بيانات غير صحيحة
 *       404:
 *         description: البند أو العقد غير موجود
 *       401:
 *         description: غير مصرح
 */
router.put(
  '/:id/items/:itemId',
  authenticate,
  authorize(['admin', 'manager']),
  validateRequest(contractValidation.updateItemSchema),
  ContractController.updateItem
);

/**
 * @swagger
 * /api/v1/contracts/{id}/items/{itemId}:
 *   delete:
 *     summary: حذف بند من العقد
 *     tags: [Contracts]
 *     security:
 *       - bearerAuth: []
 *     parameters:
 *       - in: path
 *         name: id
 *         required: true
 *         schema:
 *           type: string
 *       - in: path
 *         name: itemId
 *         required: true
 *         schema:
 *           type: string
 *     responses:
 *       200:
 *         description: تم حذف البند بنجاح
 *       404:
 *         description: البند أو العقد غير موجود
 *       401:
 *         description: غير مصرح
 */
router.delete(
  '/:id/items/:itemId',
  authenticate,
  authorize(['admin', 'manager']),
  validateRequest(contractValidation.deleteItemSchema),
  ContractController.deleteItem
);

export default router;