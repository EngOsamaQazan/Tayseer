import { Router } from 'express';
import { CustomerController } from './customer.controller';
import { authenticate } from '../../middleware/auth.middleware';
import { authorize } from '../../middleware/rbac.middleware';
import { validate } from '../../middleware/validation.middleware';
import { rateLimiter } from '../../middleware/rateLimit.middleware';
import { upload } from '../../middleware/upload.middleware';
import {
  createCustomerSchema,
  updateCustomerSchema,
  customerIdSchema,
  customerSearchSchema,
  customerNoteSchema,
  customerTagSchema
} from './customer.validation';

const router = Router();

// تطبيق المصادقة على جميع المسارات
router.use(authenticate);

/**
 * @swagger
 * /api/v1/customers:
 *   post:
 *     summary: إنشاء عميل جديد
 *     tags: [Customers]
 *     security:
 *       - bearerAuth: []
 *     requestBody:
 *       required: true
 *       content:
 *         application/json:
 *           schema:
 *             $ref: '#/components/schemas/CreateCustomer'
 *     responses:
 *       201:
 *         description: تم إنشاء العميل بنجاح
 *       400:
 *         description: بيانات غير صالحة
 *       401:
 *         description: غير مصرح
 *       409:
 *         description: العميل موجود بالفعل
 */
router.post(
  '/',
  authorize(['customers.create']),
  validate(createCustomerSchema),
  rateLimiter('createCustomer'),
  CustomerController.create
);

/**
 * @swagger
 * /api/v1/customers:
 *   get:
 *     summary: جلب قائمة العملاء
 *     tags: [Customers]
 *     security:
 *       - bearerAuth: []
 *     parameters:
 *       - in: query
 *         name: page
 *         schema:
 *           type: integer
 *         description: رقم الصفحة
 *       - in: query
 *         name: limit
 *         schema:
 *           type: integer
 *         description: عدد العناصر في الصفحة
 *       - in: query
 *         name: search
 *         schema:
 *           type: string
 *         description: البحث بالاسم أو رقم الهاتف
 *       - in: query
 *         name: status
 *         schema:
 *           type: string
 *           enum: [active, inactive]
 *         description: حالة العميل
 *       - in: query
 *         name: type
 *         schema:
 *           type: string
 *           enum: [individual, company]
 *         description: نوع العميل
 *     responses:
 *       200:
 *         description: قائمة العملاء
 *       401:
 *         description: غير مصرح
 */
router.get(
  '/',
  authorize(['customers.view']),
  CustomerController.list
);

/**
 * @swagger
 * /api/v1/customers/search:
 *   get:
 *     summary: البحث المتقدم في العملاء
 *     tags: [Customers]
 *     security:
 *       - bearerAuth: []
 *     parameters:
 *       - in: query
 *         name: query
 *         required: true
 *         schema:
 *           type: string
 *         description: نص البحث
 *       - in: query
 *         name: fields
 *         schema:
 *           type: array
 *           items:
 *             type: string
 *         description: الحقول المراد البحث فيها
 *     responses:
 *       200:
 *         description: نتائج البحث
 *       401:
 *         description: غير مصرح
 */
router.get(
  '/search',
  authorize(['customers.view']),
  validate(customerSearchSchema),
  CustomerController.search
);

/**
 * @swagger
 * /api/v1/customers/export:
 *   get:
 *     summary: تصدير بيانات العملاء
 *     tags: [Customers]
 *     security:
 *       - bearerAuth: []
 *     parameters:
 *       - in: query
 *         name: format
 *         schema:
 *           type: string
 *           enum: [xlsx, csv]
 *           default: xlsx
 *         description: صيغة الملف
 *       - in: query
 *         name: filters
 *         schema:
 *           type: string
 *         description: فلاتر البحث (JSON)
 *     responses:
 *       200:
 *         description: ملف البيانات
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
  authorize(['customers.export']),
  rateLimiter('exportCustomers'),
  CustomerController.export
);

/**
 * @swagger
 * /api/v1/customers/{id}:
 *   get:
 *     summary: جلب بيانات عميل محدد
 *     tags: [Customers]
 *     security:
 *       - bearerAuth: []
 *     parameters:
 *       - in: path
 *         name: id
 *         required: true
 *         schema:
 *           type: string
 *         description: معرف العميل
 *     responses:
 *       200:
 *         description: بيانات العميل
 *       401:
 *         description: غير مصرح
 *       404:
 *         description: العميل غير موجود
 */
router.get(
  '/:id',
  authorize(['customers.view']),
  validate(customerIdSchema),
  CustomerController.getById
);

/**
 * @swagger
 * /api/v1/customers/{id}:
 *   put:
 *     summary: تحديث بيانات عميل
 *     tags: [Customers]
 *     security:
 *       - bearerAuth: []
 *     parameters:
 *       - in: path
 *         name: id
 *         required: true
 *         schema:
 *           type: string
 *         description: معرف العميل
 *     requestBody:
 *       required: true
 *       content:
 *         application/json:
 *           schema:
 *             $ref: '#/components/schemas/UpdateCustomer'
 *     responses:
 *       200:
 *         description: تم تحديث العميل بنجاح
 *       400:
 *         description: بيانات غير صالحة
 *       401:
 *         description: غير مصرح
 *       404:
 *         description: العميل غير موجود
 */
router.put(
  '/:id',
  authorize(['customers.update']),
  validate(updateCustomerSchema),
  CustomerController.update
);

/**
 * @swagger
 * /api/v1/customers/{id}:
 *   delete:
 *     summary: حذف عميل
 *     tags: [Customers]
 *     security:
 *       - bearerAuth: []
 *     parameters:
 *       - in: path
 *         name: id
 *         required: true
 *         schema:
 *           type: string
 *         description: معرف العميل
 *     responses:
 *       200:
 *         description: تم حذف العميل بنجاح
 *       401:
 *         description: غير مصرح
 *       404:
 *         description: العميل غير موجود
 */
router.delete(
  '/:id',
  authorize(['customers.delete']),
  validate(customerIdSchema),
  CustomerController.delete
);

/**
 * @swagger
 * /api/v1/customers/{id}/documents:
 *   post:
 *     summary: رفع مستند للعميل
 *     tags: [Customers]
 *     security:
 *       - bearerAuth: []
 *     parameters:
 *       - in: path
 *         name: id
 *         required: true
 *         schema:
 *           type: string
 *         description: معرف العميل
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
 *                 enum: [id_card, passport, contract, invoice, other]
 *     responses:
 *       201:
 *         description: تم رفع المستند بنجاح
 *       400:
 *         description: ملف غير صالح
 *       401:
 *         description: غير مصرح
 *       404:
 *         description: العميل غير موجود
 */
router.post(
  '/:id/documents',
  authorize(['customers.documents.upload']),
  validate(customerIdSchema),
  upload.single('file'),
  CustomerController.uploadDocument
);

/**
 * @swagger
 * /api/v1/customers/{id}/documents:
 *   get:
 *     summary: جلب مستندات العميل
 *     tags: [Customers]
 *     security:
 *       - bearerAuth: []
 *     parameters:
 *       - in: path
 *         name: id
 *         required: true
 *         schema:
 *           type: string
 *         description: معرف العميل
 *     responses:
 *       200:
 *         description: قائمة المستندات
 *       401:
 *         description: غير مصرح
 *       404:
 *         description: العميل غير موجود
 */
router.get(
  '/:id/documents',
  authorize(['customers.documents.view']),
  validate(customerIdSchema),
  CustomerController.getDocuments
);

/**
 * @swagger
 * /api/v1/customers/{id}/documents/{documentId}:
 *   delete:
 *     summary: حذف مستند
 *     tags: [Customers]
 *     security:
 *       - bearerAuth: []
 *     parameters:
 *       - in: path
 *         name: id
 *         required: true
 *         schema:
 *           type: string
 *         description: معرف العميل
 *       - in: path
 *         name: documentId
 *         required: true
 *         schema:
 *           type: string
 *         description: معرف المستند
 *     responses:
 *       200:
 *         description: تم حذف المستند بنجاح
 *       401:
 *         description: غير مصرح
 *       404:
 *         description: المستند غير موجود
 */
router.delete(
  '/:id/documents/:documentId',
  authorize(['customers.documents.delete']),
  CustomerController.deleteDocument
);

/**
 * @swagger
 * /api/v1/customers/{id}/notes:
 *   post:
 *     summary: إضافة ملاحظة للعميل
 *     tags: [Customers]
 *     security:
 *       - bearerAuth: []
 *     parameters:
 *       - in: path
 *         name: id
 *         required: true
 *         schema:
 *           type: string
 *         description: معرف العميل
 *     requestBody:
 *       required: true
 *       content:
 *         application/json:
 *           schema:
 *             $ref: '#/components/schemas/CustomerNote'
 *     responses:
 *       201:
 *         description: تم إضافة الملاحظة بنجاح
 *       400:
 *         description: بيانات غير صالحة
 *       401:
 *         description: غير مصرح
 *       404:
 *         description: العميل غير موجود
 */
router.post(
  '/:id/notes',
  authorize(['customers.notes.create']),
  validate(customerNoteSchema),
  CustomerController.addNote
);

/**
 * @swagger
 * /api/v1/customers/{id}/notes:
 *   get:
 *     summary: جلب ملاحظات العميل
 *     tags: [Customers]
 *     security:
 *       - bearerAuth: []
 *     parameters:
 *       - in: path
 *         name: id
 *         required: true
 *         schema:
 *           type: string
 *         description: معرف العميل
 *     responses:
 *       200:
 *         description: قائمة الملاحظات
 *       401:
 *         description: غير مصرح
 *       404:
 *         description: العميل غير موجود
 */
router.get(
  '/:id/notes',
  authorize(['customers.notes.view']),
  validate(customerIdSchema),
  CustomerController.getNotes
);

/**
 * @swagger
 * /api/v1/customers/{id}/tags:
 *   put:
 *     summary: إدارة وسوم العميل
 *     tags: [Customers]
 *     security:
 *       - bearerAuth: []
 *     parameters:
 *       - in: path
 *         name: id
 *         required: true
 *         schema:
 *           type: string
 *         description: معرف العميل
 *     requestBody:
 *       required: true
 *       content:
 *         application/json:
 *           schema:
 *             $ref: '#/components/schemas/CustomerTags'
 *     responses:
 *       200:
 *         description: تم تحديث الوسوم بنجاح
 *       400:
 *         description: بيانات غير صالحة
 *       401:
 *         description: غير مصرح
 *       404:
 *         description: العميل غير موجود
 */
router.put(
  '/:id/tags',
  authorize(['customers.tags.manage']),
  validate(customerTagSchema),
  CustomerController.manageTags
);

/**
 * @swagger
 * /api/v1/customers/{id}/statistics:
 *   get:
 *     summary: جلب إحصائيات العميل
 *     tags: [Customers]
 *     security:
 *       - bearerAuth: []
 *     parameters:
 *       - in: path
 *         name: id
 *         required: true
 *         schema:
 *           type: string
 *         description: معرف العميل
 *     responses:
 *       200:
 *         description: إحصائيات العميل
 *       401:
 *         description: غير مصرح
 *       404:
 *         description: العميل غير موجود
 */
router.get(
  '/:id/statistics',
  authorize(['customers.statistics.view']),
  validate(customerIdSchema),
  CustomerController.getStatistics
);

/**
 * @swagger
 * /api/v1/customers/{id}/activity:
 *   get:
 *     summary: جلب سجل نشاطات العميل
 *     tags: [Customers]
 *     security:
 *       - bearerAuth: []
 *     parameters:
 *       - in: path
 *         name: id
 *         required: true
 *         schema:
 *           type: string
 *         description: معرف العميل
 *       - in: query
 *         name: page
 *         schema:
 *           type: integer
 *         description: رقم الصفحة
 *       - in: query
 *         name: limit
 *         schema:
 *           type: integer
 *         description: عدد العناصر في الصفحة
 *     responses:
 *       200:
 *         description: سجل النشاطات
 *       401:
 *         description: غير مصرح
 *       404:
 *         description: العميل غير موجود
 */
router.get(
  '/:id/activity',
  authorize(['customers.activity.view']),
  validate(customerIdSchema),
  CustomerController.getActivityLog
);

export default router;