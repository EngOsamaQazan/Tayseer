import Joi from 'joi';

// Document validation schemas
export const createDocumentSchema = Joi.object({
  title: Joi.string().required().min(2).max(200).messages({
    'string.empty': 'عنوان الوثيقة مطلوب',
    'string.min': 'عنوان الوثيقة يجب أن يكون على الأقل حرفين',
    'string.max': 'عنوان الوثيقة يجب أن لا يتجاوز 200 حرف',
    'any.required': 'عنوان الوثيقة مطلوب'
  }),
  type: Joi.string().valid('contract', 'agreement', 'policy', 'regulation', 'license', 'other').required().messages({
    'any.only': 'نوع الوثيقة يجب أن يكون أحد القيم المسموحة',
    'any.required': 'نوع الوثيقة مطلوب'
  }),
  description: Joi.string().required().min(10).max(1000).messages({
    'string.empty': 'وصف الوثيقة مطلوب',
    'string.min': 'وصف الوثيقة يجب أن يكون على الأقل 10 أحرف',
    'string.max': 'وصف الوثيقة يجب أن لا يتجاوز 1000 حرف',
    'any.required': 'وصف الوثيقة مطلوب'
  }),
  content: Joi.string().required().min(1).messages({
    'string.empty': 'محتوى الوثيقة مطلوب',
    'any.required': 'محتوى الوثيقة مطلوب'
  }),
  status: Joi.string().valid('draft', 'review', 'approved', 'expired', 'archived').default('draft').messages({
    'any.only': 'حالة الوثيقة يجب أن تكون أحد القيم المسموحة'
  }),
  version: Joi.string().optional().pattern(/^\d+\.\d+$/).messages({
    'string.pattern.base': 'رقم الإصدار يجب أن يكون بصيغة صحيحة (مثل 1.0)'
  }),
  tags: Joi.array().items(Joi.string().min(1).max(50)).optional().messages({
    'array.base': 'العلامات يجب أن تكون مصفوفة من النصوص'
  }),
  expiryDate: Joi.date().optional().greater('now').messages({
    'date.greater': 'تاريخ انتهاء الصلاحية يجب أن يكون في المستقبل'
  }),
  attachments: Joi.array().items(Joi.string()).optional().messages({
    'array.base': 'المرفقات يجب أن تكون مصفوفة من النصوص'
  })
});

export const updateDocumentSchema = Joi.object({
  title: Joi.string().optional().min(2).max(200).messages({
    'string.min': 'عنوان الوثيقة يجب أن يكون على الأقل حرفين',
    'string.max': 'عنوان الوثيقة يجب أن لا يتجاوز 200 حرف'
  }),
  type: Joi.string().valid('contract', 'agreement', 'policy', 'regulation', 'license', 'other').optional().messages({
    'any.only': 'نوع الوثيقة يجب أن يكون أحد القيم المسموحة'
  }),
  description: Joi.string().optional().min(10).max(1000).messages({
    'string.min': 'وصف الوثيقة يجب أن يكون على الأقل 10 أحرف',
    'string.max': 'وصف الوثيقة يجب أن لا يتجاوز 1000 حرف'
  }),
  content: Joi.string().optional().min(1).messages({
    'string.empty': 'محتوى الوثيقة لا يمكن أن يكون فارغاً'
  }),
  status: Joi.string().valid('draft', 'review', 'approved', 'expired', 'archived').optional().messages({
    'any.only': 'حالة الوثيقة يجب أن تكون أحد القيم المسموحة'
  }),
  version: Joi.string().optional().pattern(/^\d+\.\d+$/).messages({
    'string.pattern.base': 'رقم الإصدار يجب أن يكون بصيغة صحيحة (مثل 1.0)'
  }),
  tags: Joi.array().items(Joi.string().min(1).max(50)).optional().messages({
    'array.base': 'العلامات يجب أن تكون مصفوفة من النصوص'
  }),
  expiryDate: Joi.date().optional().greater('now').messages({
    'date.greater': 'تاريخ انتهاء الصلاحية يجب أن يكون في المستقبل'
  }),
  attachments: Joi.array().items(Joi.string()).optional().messages({
    'array.base': 'المرفقات يجب أن تكون مصفوفة من النصوص'
  })
});

// Case validation schemas
export const createCaseSchema = Joi.object({
  caseNumber: Joi.string().optional().pattern(/^CASE-\d{4}-\d{3}$/).messages({
    'string.pattern.base': 'رقم القضية يجب أن يكون بصيغة CASE-YYYY-XXX'
  }),
  title: Joi.string().required().min(3).max(200).messages({
    'string.empty': 'عنوان القضية مطلوب',
    'string.min': 'عنوان القضية يجب أن يكون على الأقل 3 أحرف',
    'string.max': 'عنوان القضية يجب أن لا يتجاوز 200 حرف',
    'any.required': 'عنوان القضية مطلوب'
  }),
  description: Joi.string().required().min(10).max(2000).messages({
    'string.empty': 'وصف القضية مطلوب',
    'string.min': 'وصف القضية يجب أن يكون على الأقل 10 أحرف',
    'string.max': 'وصف القضية يجب أن لا يتجاوز 2000 حرف',
    'any.required': 'وصف القضية مطلوب'
  }),
  type: Joi.string().valid('litigation', 'arbitration', 'mediation', 'consultation', 'other').required().messages({
    'any.only': 'نوع القضية يجب أن يكون أحد القيم المسموحة',
    'any.required': 'نوع القضية مطلوب'
  }),
  status: Joi.string().valid('open', 'in_progress', 'pending', 'closed', 'settled').default('open').messages({
    'any.only': 'حالة القضية يجب أن تكون أحد القيم المسموحة'
  }),
  priority: Joi.string().valid('low', 'medium', 'high', 'urgent').default('medium').messages({
    'any.only': 'أولوية القضية يجب أن تكون أحد القيم المسموحة'
  }),
  assignedLawyer: Joi.string().required().min(2).max(100).messages({
    'string.empty': 'المحامي المكلف مطلوب',
    'string.min': 'اسم المحامي يجب أن يكون على الأقل حرفين',
    'string.max': 'اسم المحامي يجب أن لا يتجاوز 100 حرف',
    'any.required': 'المحامي المكلف مطلوب'
  }),
  client: Joi.string().required().min(2).max(100).messages({
    'string.empty': 'العميل مطلوب',
    'string.min': 'اسم العميل يجب أن يكون على الأقل حرفين',
    'string.max': 'اسم العميل يجب أن لا يتجاوز 100 حرف',
    'any.required': 'العميل مطلوب'
  }),
  opponent: Joi.string().optional().min(2).max(100).messages({
    'string.min': 'اسم الخصم يجب أن يكون على الأقل حرفين',
    'string.max': 'اسم الخصم يجب أن لا يتجاوز 100 حرف'
  }),
  courtName: Joi.string().optional().min(2).max(100).messages({
    'string.min': 'اسم المحكمة يجب أن يكون على الأقل حرفين',
    'string.max': 'اسم المحكمة يجب أن لا يتجاوز 100 حرف'
  }),
  caseDate: Joi.date().required().messages({
    'date.base': 'تاريخ القضية يجب أن يكون تاريخ صحيح',
    'any.required': 'تاريخ القضية مطلوب'
  }),
  nextHearing: Joi.date().optional().greater('now').messages({
    'date.greater': 'تاريخ الجلسة القادمة يجب أن يكون في المستقبل'
  }),
  documents: Joi.array().items(Joi.string()).optional().messages({
    'array.base': 'الوثائق يجب أن تكون مصفوفة من النصوص'
  }),
  notes: Joi.string().optional().max(5000).messages({
    'string.max': 'الملاحظات يجب أن لا تتجاوز 5000 حرف'
  }),
  outcome: Joi.string().optional().max(2000).messages({
    'string.max': 'النتيجة يجب أن لا تتجاوز 2000 حرف'
  })
});

// Contract validation schemas
export const createContractSchema = Joi.object({
  contractNumber: Joi.string().optional().pattern(/^CNT-\d{4}-\d{3}$/).messages({
    'string.pattern.base': 'رقم العقد يجب أن يكون بصيغة CNT-YYYY-XXX'
  }),
  title: Joi.string().required().min(3).max(200).messages({
    'string.empty': 'عنوان العقد مطلوب',
    'string.min': 'عنوان العقد يجب أن يكون على الأقل 3 أحرف',
    'string.max': 'عنوان العقد يجب أن لا يتجاوز 200 حرف',
    'any.required': 'عنوان العقد مطلوب'
  }),
  description: Joi.string().required().min(10).max(2000).messages({
    'string.empty': 'وصف العقد مطلوب',
    'string.min': 'وصف العقد يجب أن يكون على الأقل 10 أحرف',
    'string.max': 'وصف العقد يجب أن لا يتجاوز 2000 حرف',
    'any.required': 'وصف العقد مطلوب'
  }),
  type: Joi.string().valid('service', 'purchase', 'employment', 'partnership', 'lease', 'license', 'other').required().messages({
    'any.only': 'نوع العقد يجب أن يكون أحد القيم المسموحة',
    'any.required': 'نوع العقد مطلوب'
  }),
  status: Joi.string().valid('draft', 'review', 'approved', 'signed', 'active', 'expired', 'terminated').default('draft').messages({
    'any.only': 'حالة العقد يجب أن تكون أحد القيم المسموحة'
  }),
  parties: Joi.array().items(Joi.string().min(2).max(100)).min(2).required().messages({
    'array.base': 'أطراف العقد يجب أن تكون مصفوفة',
    'array.min': 'العقد يجب أن يحتوي على طرفين على الأقل',
    'any.required': 'أطراف العقد مطلوبة'
  }),
  startDate: Joi.date().required().messages({
    'date.base': 'تاريخ بداية العقد يجب أن يكون تاريخ صحيح',
    'any.required': 'تاريخ بداية العقد مطلوب'
  }),
  endDate: Joi.date().optional().greater(Joi.ref('startDate')).messages({
    'date.greater': 'تاريخ انتهاء العقد يجب أن يكون بعد تاريخ البداية'
  }),
  value: Joi.number().optional().min(0).messages({
    'number.min': 'قيمة العقد يجب أن تكون رقم موجب'
  }),
  currency: Joi.string().optional().length(3).uppercase().messages({
    'string.length': 'رمز العملة يجب أن يكون 3 أحرف',
    'string.uppercase': 'رمز العملة يجب أن يكون بأحرف كبيرة'
  }),
  terms: Joi.string().required().min(10).messages({
    'string.empty': 'شروط العقد مطلوبة',
    'string.min': 'شروط العقد يجب أن تكون على الأقل 10 أحرف',
    'any.required': 'شروط العقد مطلوبة'
  }),
  clauses: Joi.array().items(Joi.string().min(5)).optional().messages({
    'array.base': 'بنود العقد يجب أن تكون مصفوفة من النصوص'
  }),
  attachments: Joi.array().items(Joi.string()).optional().messages({
    'array.base': 'مرفقات العقد يجب أن تكون مصفوفة من النصوص'
  })
});

export const updateContractSchema = Joi.object({
  contractNumber: Joi.string().optional().pattern(/^CNT-\d{4}-\d{3}$/).messages({
    'string.pattern.base': 'رقم العقد يجب أن يكون بصيغة CNT-YYYY-XXX'
  }),
  title: Joi.string().optional().min(3).max(200).messages({
    'string.min': 'عنوان العقد يجب أن يكون على الأقل 3 أحرف',
    'string.max': 'عنوان العقد يجب أن لا يتجاوز 200 حرف'
  }),
  description: Joi.string().optional().min(10).max(2000).messages({
    'string.min': 'وصف العقد يجب أن يكون على الأقل 10 أحرف',
    'string.max': 'وصف العقد يجب أن لا يتجاوز 2000 حرف'
  }),
  type: Joi.string().valid('service', 'purchase', 'employment', 'partnership', 'lease', 'license', 'other').optional().messages({
    'any.only': 'نوع العقد يجب أن يكون أحد القيم المسموحة'
  }),
  status: Joi.string().valid('draft', 'review', 'approved', 'signed', 'active', 'expired', 'terminated').optional().messages({
    'any.only': 'حالة العقد يجب أن تكون أحد القيم المسموحة'
  }),
  parties: Joi.array().items(Joi.string().min(2).max(100)).min(2).optional().messages({
    'array.base': 'أطراف العقد يجب أن تكون مصفوفة',
    'array.min': 'العقد يجب أن يحتوي على طرفين على الأقل'
  }),
  startDate: Joi.date().optional().messages({
    'date.base': 'تاريخ بداية العقد يجب أن يكون تاريخ صحيح'
  }),
  endDate: Joi.date().optional().when('startDate', {
    is: Joi.exist(),
    then: Joi.date().greater(Joi.ref('startDate')),
    otherwise: Joi.date()
  }).messages({
    'date.greater': 'تاريخ انتهاء العقد يجب أن يكون بعد تاريخ البداية'
  }),
  value: Joi.number().optional().min(0).messages({
    'number.min': 'قيمة العقد يجب أن تكون رقم موجب'
  }),
  currency: Joi.string().optional().length(3).uppercase().messages({
    'string.length': 'رمز العملة يجب أن يكون 3 أحرف',
    'string.uppercase': 'رمز العملة يجب أن يكون بأحرف كبيرة'
  }),
  terms: Joi.string().optional().min(10).messages({
    'string.min': 'شروط العقد يجب أن تكون على الأقل 10 أحرف'
  }),
  clauses: Joi.array().items(Joi.string().min(5)).optional().messages({
    'array.base': 'بنود العقد يجب أن تكون مصفوفة من النصوص'
  }),
  attachments: Joi.array().items(Joi.string()).optional().messages({
    'array.base': 'مرفقات العقد يجب أن تكون مصفوفة من النصوص'
  })
});

// Compliance audit validation schemas
export const createAuditSchema = Joi.object({
  title: Joi.string().required().min(3).max(200).messages({
    'string.empty': 'عنوان التدقيق مطلوب',
    'string.min': 'عنوان التدقيق يجب أن يكون على الأقل 3 أحرف',
    'string.max': 'عنوان التدقيق يجب أن لا يتجاوز 200 حرف',
    'any.required': 'عنوان التدقيق مطلوب'
  }),
  description: Joi.string().required().min(10).max(1000).messages({
    'string.empty': 'وصف التدقيق مطلوب',
    'string.min': 'وصف التدقيق يجب أن يكون على الأقل 10 أحرف',
    'string.max': 'وصف التدقيق يجب أن لا يتجاوز 1000 حرف',
    'any.required': 'وصف التدقيق مطلوب'
  }),
  type: Joi.string().valid('internal', 'external', 'regulatory', 'financial', 'operational', 'security').required().messages({
    'any.only': 'نوع التدقيق يجب أن يكون أحد القيم المسموحة',
    'any.required': 'نوع التدقيق مطلوب'
  }),
  status: Joi.string().valid('planned', 'in_progress', 'completed', 'cancelled').default('planned').messages({
    'any.only': 'حالة التدقيق يجب أن تكون أحد القيم المسموحة'
  }),
  auditor: Joi.string().required().min(2).max(100).messages({
    'string.empty': 'المدقق مطلوب',
    'string.min': 'اسم المدقق يجب أن يكون على الأقل حرفين',
    'string.max': 'اسم المدقق يجب أن لا يتجاوز 100 حرف',
    'any.required': 'المدقق مطلوب'
  }),
  startDate: Joi.date().required().messages({
    'date.base': 'تاريخ بداية التدقيق يجب أن يكون تاريخ صحيح',
    'any.required': 'تاريخ بداية التدقيق مطلوب'
  }),
  endDate: Joi.date().optional().greater(Joi.ref('startDate')).messages({
    'date.greater': 'تاريخ انتهاء التدقيق يجب أن يكون بعد تاريخ البداية'
  }),
  scope: Joi.array().items(Joi.string().min(2)).required().min(1).messages({
    'array.base': 'نطاق التدقيق يجب أن يكون مصفوفة',
    'array.min': 'نطاق التدقيق يجب أن يحتوي على عنصر واحد على الأقل',
    'any.required': 'نطاق التدقيق مطلوب'
  }),
  score: Joi.number().optional().min(0).max(100).messages({
    'number.min': 'نتيجة التدقيق يجب أن تكون بين 0 و 100',
    'number.max': 'نتيجة التدقيق يجب أن تكون بين 0 و 100'
  })
});

// Query parameter validation schemas
export const queryParamsSchema = Joi.object({
  page: Joi.number().integer().min(1).default(1).messages({
    'number.integer': 'رقم الصفحة يجب أن يكون عدد صحيح',
    'number.min': 'رقم الصفحة يجب أن يكون 1 أو أكثر'
  }),
  limit: Joi.number().integer().min(1).max(100).default(10).messages({
    'number.integer': 'حد العناصر يجب أن يكون عدد صحيح',
    'number.min': 'حد العناصر يجب أن يكون 1 أو أكثر',
    'number.max': 'حد العناصر يجب أن لا يتجاوز 100'
  }),
  search: Joi.string().optional().min(1).max(100).messages({
    'string.min': 'نص البحث يجب أن يكون حرف واحد على الأقل',
    'string.max': 'نص البحث يجب أن لا يتجاوز 100 حرف'
  }),
  status: Joi.string().optional().messages({
    'string.base': 'الحالة يجب أن تكون نص'
  }),
  type: Joi.string().optional().messages({
    'string.base': 'النوع يجب أن يكون نص'
  }),
  sortBy: Joi.string().optional().valid('createdAt', 'updatedAt', 'title', 'status', 'type').default('createdAt').messages({
    'any.only': 'ترتيب النتائج يجب أن يكون أحد القيم المسموحة'
  }),
  sortOrder: Joi.string().optional().valid('asc', 'desc').default('desc').messages({
    'any.only': 'اتجاه الترتيب يجب أن يكون تصاعدي أو تنازلي'
  })
});

// ID parameter validation
export const idParamsSchema = Joi.object({
  id: Joi.string().required().pattern(/^[0-9a-fA-F]{24}$/).messages({
    'string.pattern.base': 'معرف الكائن يجب أن يكون رقم تعريف صحيح',
    'any.required': 'المعرف مطلوب'
  })
});

export const updateCaseSchema = Joi.object({
  caseNumber: Joi.string().optional().pattern(/^CASE-\d{4}-\d{3}$/).messages({
    'string.pattern.base': 'رقم القضية يجب أن يكون بصيغة CASE-YYYY-XXX'
  }),
  title: Joi.string().optional().min(3).max(200).messages({
    'string.min': 'عنوان القضية يجب أن يكون على الأقل 3 أحرف',
    'string.max': 'عنوان القضية يجب أن لا يتجاوز 200 حرف'
  }),
  description: Joi.string().optional().min(10).max(2000).messages({
    'string.min': 'وصف القضية يجب أن يكون على الأقل 10 أحرف',
    'string.max': 'وصف القضية يجب أن لا يتجاوز 2000 حرف'
  }),
  type: Joi.string().valid('litigation', 'arbitration', 'mediation', 'consultation', 'other').optional().messages({
    'any.only': 'نوع القضية يجب أن يكون أحد القيم المسموحة'
  }),
  status: Joi.string().valid('open', 'in_progress', 'pending', 'closed', 'settled').optional().messages({
    'any.only': 'حالة القضية يجب أن تكون أحد القيم المسموحة'
  }),
  priority: Joi.string().valid('low', 'medium', 'high', 'urgent').optional().messages({
    'any.only': 'أولوية القضية يجب أن تكون أحد القيم المسموحة'
  }),
  assignedLawyer: Joi.string().optional().min(2).max(100).messages({
    'string.min': 'اسم المحامي يجب أن يكون على الأقل حرفين',
    'string.max': 'اسم المحامي يجب أن لا يتجاوز 100 حرف'
  }),
  client: Joi.string().optional().min(2).max(100).messages({
    'string.min': 'اسم العميل يجب أن يكون على الأقل حرفين',
    'string.max': 'اسم العميل يجب أن لا يتجاوز 100 حرف'
  }),
  opponent: Joi.string().optional().min(2).max(100).messages({
    'string.min': 'اسم الخصم يجب أن يكون على الأقل حرفين',
    'string.max': 'اسم الخصم يجب أن لا يتجاوز 100 حرف'
  }),
  courtName: Joi.string().optional().min(2).max(100).messages({
    'string.min': 'اسم المحكمة يجب أن يكون على الأقل حرفين',
    'string.max': 'اسم المحكمة يجب أن لا يتجاوز 100 حرف'
  }),
  caseDate: Joi.date().optional().messages({
    'date.base': 'تاريخ القضية يجب أن يكون تاريخ صحيح'
  }),
  nextHearing: Joi.date().optional().greater('now').messages({
    'date.greater': 'تاريخ الجلسة القادمة يجب أن يكون في المستقبل'
  }),
  documents: Joi.array().items(Joi.string()).optional().messages({
    'array.base': 'الوثائق يجب أن تكون مصفوفة من النصوص'
  }),
  notes: Joi.string().optional().max(5000).messages({
    'string.max': 'الملاحظات يجب أن لا تتجاوز 5000 حرف'
  }),
  outcome: Joi.string().optional().max(2000).messages({
    'string.max': 'النتيجة يجب أن لا تتجاوز 2000 حرف'
  })
});

// Contract validation schemas
export const createContractSchema = Joi.object({
  contractNumber: Joi.string().optional().pattern(/^CNT-\d{4}-\d{3}$/).messages({
    'string.pattern.base': 'رقم العقد يجب أن يكون بصيغة CNT-YYYY-XXX'
  }),
  title: Joi.string().required().min(3).max(200).messages({
    'string.empty': 'عنوان العقد مطلوب',
    'string.min': 'عنوان العقد يجب أن يكون على الأقل 3 أحرف',
    'string.max': 'عنوان العقد يجب أن لا يتجاوز 200 حرف',
    'any.required': 'عنوان العقد مطلوب'
  }),
  description: Joi.string().required().min(10).max(2000).messages({
    'string.empty': 'وصف العقد مطلوب',
    'string.min': 'وصف العقد يجب أن يكون على الأقل 10 أحرف',
    'string.max': 'وصف العقد يجب أن لا يتجاوز 2000 حرف',
    'any.required': 'وصف العقد مطلوب'
  }),
  type: Joi.string().valid('service', 'purchase', 'employment', 'partnership', 'lease', 'license', 'other').required().messages({
    'any.only': 'نوع العقد يجب أن يكون أحد القيم المسموحة',
    'any.required': 'نوع العقد مطلوب'
  }),
  status: Joi.string().valid('draft', 'review', 'approved', 'signed', 'active', 'expired', 'terminated').default('draft').messages({
    'any.only': 'حالة العقد يجب أن تكون أحد القيم المسموحة'
  }),
  parties: Joi.array().items(Joi.string().min(2).max(100)).min(2).required().messages({
    'array.base': 'أطراف العقد يجب أن تكون مصفوفة',
    'array.min': 'العقد يجب أن يحتوي على طرفين على الأقل',
    'any.required': 'أطراف العقد مطلوبة'
  }),
  startDate: Joi.date().required().messages({
    'date.base': 'تاريخ بداية العقد يجب أن يكون تاريخ صحيح',
    'any.required': 'تاريخ بداية العقد مطلوب'
  }),
  endDate: Joi.date().optional().greater(Joi.ref('startDate')).messages({
    'date.greater': 'تاريخ انتهاء العقد يجب أن يكون بعد تاريخ البداية'
  }),
  value: Joi.number().optional().min(0).messages({
    'number.min': 'قيمة العقد يجب أن تكون رقم موجب'
  }),
  currency: Joi.string().optional().length(3).uppercase().messages({
    'string.length': 'رمز العملة يجب أن يكون 3 أحرف',
    'string.uppercase': 'رمز العملة يجب أن يكون بأحرف كبيرة'
  }),
  terms: Joi.string().required().min(10).messages({
    'string.empty': 'شروط العقد مطلوبة',
    'string.min': 'شروط العقد يجب أن تكون على الأقل 10 أحرف',
    'any.required': 'شروط العقد مطلوبة'
  }),
  clauses: Joi.array().items(Joi.string().min(5)).optional().messages({
    'array.base': 'بنود العقد يجب أن تكون مصفوفة من النصوص'
  }),
  attachments: Joi.array().items(Joi.string()).optional().messages({
    'array.base': 'مرفقات العقد يجب أن تكون مصفوفة من النصوص'
  })
});

export const updateContractSchema = Joi.object({
  contractNumber: Joi.string().optional().pattern(/^CNT-\d{4}-\d{3}$/).messages({
    'string.pattern.base': 'رقم العقد يجب أن يكون بصيغة CNT-YYYY-XXX'
  }),
  title: Joi.string().optional().min(3).max(200).messages({
    'string.min': 'عنوان العقد يجب أن يكون على الأقل 3 أحرف',
    'string.max': 'عنوان العقد يجب أن لا يتجاوز 200 حرف'
  }),
  description: Joi.string().optional().min(10).max(2000).messages({
    'string.min': 'وصف العقد يجب أن يكون على الأقل 10 أحرف',
    'string.max': 'وصف العقد يجب أن لا يتجاوز 2000 حرف'
  }),
  type: Joi.string().valid('service', 'purchase', 'employment', 'partnership', 'lease', 'license', 'other').optional().messages({
    'any.only': 'نوع العقد يجب أن يكون أحد القيم المسموحة'
  }),
  status: Joi.string().valid('draft', 'review', 'approved', 'signed', 'active', 'expired', 'terminated').optional().messages({
    'any.only': 'حالة العقد يجب أن تكون أحد القيم المسموحة'
  }),
  parties: Joi.array().items(Joi.string().min(2).max(100)).min(2).optional().messages({
    'array.base': 'أطراف العقد يجب أن تكون مصفوفة',
    'array.min': 'العقد يجب أن يحتوي على طرفين على الأقل'
  }),
  startDate: Joi.date().optional().messages({
    'date.base': 'تاريخ بداية العقد يجب أن يكون تاريخ صحيح'
  }),
  endDate: Joi.date().optional().when('startDate', {
    is: Joi.exist(),
    then: Joi.date().greater(Joi.ref('startDate')),
    otherwise: Joi.date()
  }).messages({
    'date.greater': 'تاريخ انتهاء العقد يجب أن يكون بعد تاريخ البداية'
  }),
  value: Joi.number().optional().min(0).messages({
    'number.min': 'قيمة العقد يجب أن تكون رقم موجب'
  }),
  currency: Joi.string().optional().length(3).uppercase().messages({
    'string.length': 'رمز العملة يجب أن يكون 3 أحرف',
    'string.uppercase': 'رمز العملة يجب أن يكون بأحرف كبيرة'
  }),
  terms: Joi.string().optional().min(10).messages({
    'string.min': 'شروط العقد يجب أن تكون على الأقل 10 أحرف'
  }),
  clauses: Joi.array().items(Joi.string().min(5)).optional().messages({
    'array.base': 'بنود العقد يجب أن تكون مصفوفة من النصوص'
  }),
  attachments: Joi.array().items(Joi.string()).optional().messages({
    'array.base': 'مرفقات العقد يجب أن تكون مصفوفة من النصوص'
  })
});

// Compliance audit validation schemas
export const createAuditSchema = Joi.object({
  title: Joi.string().required().min(3).max(200).messages({
    'string.empty': 'عنوان التدقيق مطلوب',
    'string.min': 'عنوان التدقيق يجب أن يكون على الأقل 3 أحرف',
    'string.max': 'عنوان التدقيق يجب أن لا يتجاوز 200 حرف',
    'any.required': 'عنوان التدقيق مطلوب'
  }),
  description: Joi.string().required().min(10).max(1000).messages({
    'string.empty': 'وصف التدقيق مطلوب',
    'string.min': 'وصف التدقيق يجب أن يكون على الأقل 10 أحرف',
    'string.max': 'وصف التدقيق يجب أن لا يتجاوز 1000 حرف',
    'any.required': 'وصف التدقيق مطلوب'
  }),
  type: Joi.string().valid('internal', 'external', 'regulatory', 'financial', 'operational', 'security').required().messages({
    'any.only': 'نوع التدقيق يجب أن يكون أحد القيم المسموحة',
    'any.required': 'نوع التدقيق مطلوب'
  }),
  status: Joi.string().valid('planned', 'in_progress', 'completed', 'cancelled').default('planned').messages({
    'any.only': 'حالة التدقيق يجب أن تكون أحد القيم المسموحة'
  }),
  auditor: Joi.string().required().min(2).max(100).messages({
    'string.empty': 'المدقق مطلوب',
    'string.min': 'اسم المدقق يجب أن يكون على الأقل حرفين',
    'string.max': 'اسم المدقق يجب أن لا يتجاوز 100 حرف',
    'any.required': 'المدقق مطلوب'
  }),
  startDate: Joi.date().required().messages({
    'date.base': 'تاريخ بداية التدقيق يجب أن يكون تاريخ صحيح',
    'any.required': 'تاريخ بداية التدقيق مطلوب'
  }),
  endDate: Joi.date().optional().greater(Joi.ref('startDate')).messages({
    'date.greater': 'تاريخ انتهاء التدقيق يجب أن يكون بعد تاريخ البداية'
  }),
  scope: Joi.array().items(Joi.string().min(2)).required().min(1).messages({
    'array.base': 'نطاق التدقيق يجب أن يكون مصفوفة',
    'array.min': 'نطاق التدقيق يجب أن يحتوي على عنصر واحد على الأقل',
    'any.required': 'نطاق التدقيق مطلوب'
  }),
  score: Joi.number().optional().min(0).max(100).messages({
    'number.min': 'نتيجة التدقيق يجب أن تكون بين 0 و 100',
    'number.max': 'نتيجة التدقيق يجب أن تكون بين 0 و 100'
  })
});

// Query parameter validation schemas
export const queryParamsSchema = Joi.object({
  page: Joi.number().integer().min(1).default(1).messages({
    'number.integer': 'رقم الصفحة يجب أن يكون عدد صحيح',
    'number.min': 'رقم الصفحة يجب أن يكون 1 أو أكثر'
  }),
  limit: Joi.number().integer().min(1).max(100).default(10).messages({
    'number.integer': 'حد العناصر يجب أن يكون عدد صحيح',
    'number.min': 'حد العناصر يجب أن يكون 1 أو أكثر',
    'number.max': 'حد العناصر يجب أن لا يتجاوز 100'
  }),
  search: Joi.string().optional().min(1).max(100).messages({
    'string.min': 'نص البحث يجب أن يكون حرف واحد على الأقل',
    'string.max': 'نص البحث يجب أن لا يتجاوز 100 حرف'
  }),
  status: Joi.string().optional().messages({
    'string.base': 'الحالة يجب أن تكون نص'
  }),
  type: Joi.string().optional().messages({
    'string.base': 'النوع يجب أن يكون نص'
  })
});