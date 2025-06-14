import Joi from 'joi';

// Validation schema for creating a new support ticket
export const createTicketSchema = Joi.object({
  title: Joi.string()
    .min(3)
    .max(200)
    .required()
    .messages({
      'string.base': 'عنوان التذكرة يجب أن يكون نصاً',
      'string.min': 'عنوان التذكرة يجب أن يحتوي على 3 أحرف على الأقل',
      'string.max': 'عنوان التذكرة يجب ألا يتجاوز 200 حرف',
      'any.required': 'عنوان التذكرة مطلوب'
    }),
  description: Joi.string()
    .min(10)
    .max(2000)
    .required()
    .messages({
      'string.base': 'وصف التذكرة يجب أن يكون نصاً',
      'string.min': 'وصف التذكرة يجب أن يحتوي على 10 أحرف على الأقل',
      'string.max': 'وصف التذكرة يجب ألا يتجاوز 2000 حرف',
      'any.required': 'وصف التذكرة مطلوب'
    }),
  category: Joi.string()
    .valid('technical', 'billing', 'general', 'bug_report', 'feature_request', 'account')
    .required()
    .messages({
      'any.only': 'فئة التذكرة يجب أن تكون إحدى القيم المسموحة',
      'any.required': 'فئة التذكرة مطلوبة'
    }),
  priority: Joi.string()
    .valid('low', 'medium', 'high', 'urgent')
    .default('medium')
    .messages({
      'any.only': 'أولوية التذكرة يجب أن تكون إحدى القيم المسموحة'
    }),
  customerId: Joi.string()
    .pattern(/^[0-9a-fA-F]{24}$/)
    .required()
    .messages({
      'string.pattern.base': 'معرف العميل غير صحيح',
      'any.required': 'معرف العميل مطلوب'
    }),
  attachments: Joi.array()
    .items(Joi.object({
      filename: Joi.string().required(),
      url: Joi.string().uri().required(),
      size: Joi.number().positive().required()
    }))
    .max(5)
    .messages({
      'array.max': 'لا يمكن إرفاق أكثر من 5 ملفات'
    })
});

// Validation schema for updating a support ticket
export const updateTicketSchema = Joi.object({
  title: Joi.string()
    .min(3)
    .max(200)
    .messages({
      'string.base': 'عنوان التذكرة يجب أن يكون نصاً',
      'string.min': 'عنوان التذكرة يجب أن يحتوي على 3 أحرف على الأقل',
      'string.max': 'عنوان التذكرة يجب ألا يتجاوز 200 حرف'
    }),
  description: Joi.string()
    .min(10)
    .max(2000)
    .messages({
      'string.base': 'وصف التذكرة يجب أن يكون نصاً',
      'string.min': 'وصف التذكرة يجب أن يحتوي على 10 أحرف على الأقل',
      'string.max': 'وصف التذكرة يجب ألا يتجاوز 2000 حرف'
    }),
  category: Joi.string()
    .valid('technical', 'billing', 'general', 'bug_report', 'feature_request', 'account')
    .messages({
      'any.only': 'فئة التذكرة يجب أن تكون إحدى القيم المسموحة'
    }),
  priority: Joi.string()
    .valid('low', 'medium', 'high', 'urgent')
    .messages({
      'any.only': 'أولوية التذكرة يجب أن تكون إحدى القيم المسموحة'
    })
}).min(1).messages({
  'object.min': 'يجب توفير حقل واحد على الأقل للتحديث'
});

// Validation schema for changing ticket status
export const updateTicketStatusSchema = Joi.object({
  status: Joi.string()
    .valid('open', 'in_progress', 'pending', 'resolved', 'closed', 'escalated')
    .required()
    .messages({
      'any.only': 'حالة التذكرة يجب أن تكون إحدى القيم المسموحة',
      'any.required': 'حالة التذكرة مطلوبة'
    }),
  resolution: Joi.string()
    .min(10)
    .max(1000)
    .when('status', {
      is: Joi.string().valid('resolved', 'closed'),
      then: Joi.required(),
      otherwise: Joi.optional()
    })
    .messages({
      'string.base': 'وصف الحل يجب أن يكون نصاً',
      'string.min': 'وصف الحل يجب أن يحتوي على 10 أحرف على الأقل',
      'string.max': 'وصف الحل يجب ألا يتجاوز 1000 حرف',
      'any.required': 'وصف الحل مطلوب عند إغلاق التذكرة'
    })
});

// Validation schema for assigning ticket
export const assignTicketSchema = Joi.object({
  assignedTo: Joi.string()
    .pattern(/^[0-9a-fA-F]{24}$/)
    .required()
    .messages({
      'string.pattern.base': 'معرف الموظف غير صحيح',
      'any.required': 'معرف الموظف مطلوب'
    }),
  notes: Joi.string()
    .max(500)
    .optional()
    .messages({
      'string.max': 'الملاحظات يجب ألا تتجاوز 500 حرف'
    })
});

// Validation schema for updating ticket priority
export const updateTicketPrioritySchema = Joi.object({
  priority: Joi.string()
    .valid('low', 'medium', 'high', 'urgent')
    .required()
    .messages({
      'any.only': 'أولوية التذكرة يجب أن تكون إحدى القيم المسموحة',
      'any.required': 'أولوية التذكرة مطلوبة'
    }),
  reason: Joi.string()
    .min(5)
    .max(200)
    .optional()
    .messages({
      'string.min': 'سبب تغيير الأولوية يجب أن يحتوي على 5 أحرف على الأقل',
      'string.max': 'سبب تغيير الأولوية يجب ألا يتجاوز 200 حرف'
    })
});

// Validation schema for adding ticket response
export const addTicketResponseSchema = Joi.object({
  message: Joi.string()
    .min(5)
    .max(2000)
    .required()
    .messages({
      'string.base': 'الرسالة يجب أن تكون نصاً',
      'string.min': 'الرسالة يجب أن تحتوي على 5 أحرف على الأقل',
      'string.max': 'الرسالة يجب ألا تتجاوز 2000 حرف',
      'any.required': 'الرسالة مطلوبة'
    }),
  isInternal: Joi.boolean()
    .default(false)
    .messages({
      'boolean.base': 'نوع الرسالة يجب أن يكون قيمة منطقية'
    }),
  attachments: Joi.array()
    .items(Joi.object({
      filename: Joi.string().required(),
      url: Joi.string().uri().required(),
      size: Joi.number().positive().required()
    }))
    .max(3)
    .messages({
      'array.max': 'لا يمكن إرفاق أكثر من 3 ملفات في الرد'
    })
});

// Validation schema for creating knowledge base article
export const createKnowledgeBaseSchema = Joi.object({
  title: Joi.string()
    .min(5)
    .max(200)
    .required()
    .messages({
      'string.base': 'عنوان المقال يجب أن يكون نصاً',
      'string.min': 'عنوان المقال يجب أن يحتوي على 5 أحرف على الأقل',
      'string.max': 'عنوان المقال يجب ألا يتجاوز 200 حرف',
      'any.required': 'عنوان المقال مطلوب'
    }),
  content: Joi.string()
    .min(50)
    .max(10000)
    .required()
    .messages({
      'string.base': 'محتوى المقال يجب أن يكون نصاً',
      'string.min': 'محتوى المقال يجب أن يحتوي على 50 حرف على الأقل',
      'string.max': 'محتوى المقال يجب ألا يتجاوز 10000 حرف',
      'any.required': 'محتوى المقال مطلوب'
    }),
  category: Joi.string()
    .valid('technical', 'billing', 'general', 'troubleshooting', 'getting_started')
    .required()
    .messages({
      'any.only': 'فئة المقال يجب أن تكون إحدى القيم المسموحة',
      'any.required': 'فئة المقال مطلوبة'
    }),
  tags: Joi.array()
    .items(Joi.string().min(2).max(30))
    .max(10)
    .messages({
      'array.max': 'لا يمكن إضافة أكثر من 10 علامات'
    }),
  isPublic: Joi.boolean()
    .default(true)
    .messages({
      'boolean.base': 'حالة النشر يجب أن تكون قيمة منطقية'
    })
});

// Validation schema for updating knowledge base article
export const updateKnowledgeBaseSchema = Joi.object({
  title: Joi.string()
    .min(5)
    .max(200)
    .messages({
      'string.base': 'عنوان المقال يجب أن يكون نصاً',
      'string.min': 'عنوان المقال يجب أن يحتوي على 5 أحرف على الأقل',
      'string.max': 'عنوان المقال يجب ألا يتجاوز 200 حرف'
    }),
  content: Joi.string()
    .min(50)
    .max(10000)
    .messages({
      'string.base': 'محتوى المقال يجب أن يكون نصاً',
      'string.min': 'محتوى المقال يجب أن يحتوي على 50 حرف على الأقل',
      'string.max': 'محتوى المقال يجب ألا يتجاوز 10000 حرف'
    }),
  category: Joi.string()
    .valid('technical', 'billing', 'general', 'troubleshooting', 'getting_started')
    .messages({
      'any.only': 'فئة المقال يجب أن تكون إحدى القيم المسموحة'
    }),
  tags: Joi.array()
    .items(Joi.string().min(2).max(30))
    .max(10)
    .messages({
      'array.max': 'لا يمكن إضافة أكثر من 10 علامات'
    }),
  isPublic: Joi.boolean()
    .messages({
      'boolean.base': 'حالة النشر يجب أن تكون قيمة منطقية'
    })
}).min(1).messages({
  'object.min': 'يجب توفير حقل واحد على الأقل للتحديث'
});

// Validation schema for creating FAQ
export const createFAQSchema = Joi.object({
  question: Joi.string()
    .min(5)
    .max(300)
    .required()
    .messages({
      'string.base': 'السؤال يجب أن يكون نصاً',
      'string.min': 'السؤال يجب أن يحتوي على 5 أحرف على الأقل',
      'string.max': 'السؤال يجب ألا يتجاوز 300 حرف',
      'any.required': 'السؤال مطلوب'
    }),
  answer: Joi.string()
    .min(10)
    .max(2000)
    .required()
    .messages({
      'string.base': 'الإجابة يجب أن تكون نصاً',
      'string.min': 'الإجابة يجب أن تحتوي على 10 أحرف على الأقل',
      'string.max': 'الإجابة يجب ألا تتجاوز 2000 حرف',
      'any.required': 'الإجابة مطلوبة'
    }),
  category: Joi.string()
    .valid('general', 'technical', 'billing', 'account', 'features')
    .required()
    .messages({
      'any.only': 'فئة السؤال يجب أن تكون إحدى القيم المسموحة',
      'any.required': 'فئة السؤال مطلوبة'
    }),
  tags: Joi.array()
    .items(Joi.string().min(2).max(30))
    .max(5)
    .messages({
      'array.max': 'لا يمكن إضافة أكثر من 5 علامات'
    }),
  isActive: Joi.boolean()
    .default(true)
    .messages({
      'boolean.base': 'حالة النشاط يجب أن تكون قيمة منطقية'
    })
});

// Validation schema for updating FAQ
export const updateFAQSchema = Joi.object({
  question: Joi.string()
    .min(5)
    .max(300)
    .messages({
      'string.base': 'السؤال يجب أن يكون نصاً',
      'string.min': 'السؤال يجب أن يحتوي على 5 أحرف على الأقل',
      'string.max': 'السؤال يجب ألا يتجاوز 300 حرف'
    }),
  answer: Joi.string()
    .min(10)
    .max(2000)
    .messages({
      'string.base': 'الإجابة يجب أن تكون نصاً',
      'string.min': 'الإجابة يجب أن تحتوي على 10 أحرف على الأقل',
      'string.max': 'الإجابة يجب ألا تتجاوز 2000 حرف'
    }),
  category: Joi.string()
    .valid('general', 'technical', 'billing', 'account', 'features')
    .messages({
      'any.only': 'فئة السؤال يجب أن تكون إحدى القيم المسموحة'
    }),
  tags: Joi.array()
    .items(Joi.string().min(2).max(30))
    .max(5)
    .messages({
      'array.max': 'لا يمكن إضافة أكثر من 5 علامات'
    }),
  isActive: Joi.boolean()
    .messages({
      'boolean.base': 'حالة النشاط يجب أن تكون قيمة منطقية'
    })
}).min(1).messages({
  'object.min': 'يجب توفير حقل واحد على الأقل للتحديث'
});

// Validation schema for adding customer feedback
export const addCustomerFeedbackSchema = Joi.object({
  ticketId: Joi.string()
    .pattern(/^[0-9a-fA-F]{24}$/)
    .required()
    .messages({
      'string.pattern.base': 'معرف التذكرة غير صحيح',
      'any.required': 'معرف التذكرة مطلوب'
    }),
  rating: Joi.number()
    .integer()
    .min(1)
    .max(5)
    .required()
    .messages({
      'number.base': 'التقييم يجب أن يكون رقماً',
      'number.integer': 'التقييم يجب أن يكون رقماً صحيحاً',
      'number.min': 'التقييم يجب أن يكون بين 1 و 5',
      'number.max': 'التقييم يجب أن يكون بين 1 و 5',
      'any.required': 'التقييم مطلوب'
    }),
  comment: Joi.string()
    .min(5)
    .max(1000)
    .optional()
    .messages({
      'string.base': 'التعليق يجب أن يكون نصاً',
      'string.min': 'التعليق يجب أن يحتوي على 5 أحرف على الأقل',
      'string.max': 'التعليق يجب ألا يتجاوز 1000 حرف'
    })
});

// Validation schema for escalating ticket
export const escalateTicketSchema = Joi.object({
  reason: Joi.string()
    .min(10)
    .max(500)
    .required()
    .messages({
      'string.base': 'سبب التصعيد يجب أن يكون نصاً',
      'string.min': 'سبب التصعيد يجب أن يحتوي على 10 أحرف على الأقل',
      'string.max': 'سبب التصعيد يجب ألا يتجاوز 500 حرف',
      'any.required': 'سبب التصعيد مطلوب'
    }),
  escalatedTo: Joi.string()
    .pattern(/^[0-9a-fA-F]{24}$/)
    .required()
    .messages({
      'string.pattern.base': 'معرف الموظف المصعد إليه غير صحيح',
      'any.required': 'معرف الموظف المصعد إليه مطلوب'
    }),
  urgencyLevel: Joi.string()
    .valid('medium', 'high', 'critical')
    .default('medium')
    .messages({
      'any.only': 'مستوى الإلحاح يجب أن يكون إحدى القيم المسموحة'
    })
});

// Validation schema for query parameters
export const queryParamsSchema = Joi.object({
  page: Joi.number()
    .integer()
    .min(1)
    .default(1)
    .messages({
      'number.base': 'رقم الصفحة يجب أن يكون رقماً',
      'number.integer': 'رقم الصفحة يجب أن يكون رقماً صحيحاً',
      'number.min': 'رقم الصفحة يجب أن يكون أكبر من 0'
    }),
  limit: Joi.number()
    .integer()
    .min(1)
    .max(100)
    .default(10)
    .messages({
      'number.base': 'حد العرض يجب أن يكون رقماً',
      'number.integer': 'حد العرض يجب أن يكون رقماً صحيحاً',
      'number.min': 'حد العرض يجب أن يكون أكبر من 0',
      'number.max': 'حد العرض يجب ألا يتجاوز 100'
    }),
  sortBy: Joi.string()
    .valid('createdAt', 'updatedAt', 'title', 'priority', 'status')
    .default('createdAt')
    .messages({
      'any.only': 'حقل الترتيب يجب أن يكون إحدى القيم المسموحة'
    }),
  sortOrder: Joi.string()
    .valid('asc', 'desc')
    .default('desc')
    .messages({
      'any.only': 'اتجاه الترتيب يجب أن يكون إحدى القيم المسموحة'
    }),
  status: Joi.string()
    .valid('open', 'in_progress', 'pending', 'resolved', 'closed', 'escalated')
    .messages({
      'any.only': 'حالة التذكرة يجب أن تكون إحدى القيم المسموحة'
    }),
  priority: Joi.string()
    .valid('low', 'medium', 'high', 'urgent')
    .messages({
      'any.only': 'أولوية التذكرة يجب أن تكون إحدى القيم المسموحة'
    }),
  category: Joi.string()
    .valid('technical', 'billing', 'general', 'bug_report', 'feature_request', 'account')
    .messages({
      'any.only': 'فئة التذكرة يجب أن تكون إحدى القيم المسموحة'
    }),
  assignedTo: Joi.string()
    .pattern(/^[0-9a-fA-F]{24}$/)
    .messages({
      'string.pattern.base': 'معرف الموظف غير صحيح'
    }),
  customerId: Joi.string()
    .pattern(/^[0-9a-fA-F]{24}$/)
    .messages({
      'string.pattern.base': 'معرف العميل غير صحيح'
    }),
  search: Joi.string()
    .min(2)
    .max(100)
    .messages({
      'string.min': 'نص البحث يجب أن يحتوي على حرفين على الأقل',
      'string.max': 'نص البحث يجب ألا يتجاوز 100 حرف'
    }),
  dateFrom: Joi.date()
    .iso()
    .messages({
      'date.format': 'تاريخ البداية يجب أن يكون بصيغة ISO صحيحة'
    }),
  dateTo: Joi.date()
    .iso()
    .min(Joi.ref('dateFrom'))
    .messages({
      'date.format': 'تاريخ النهاية يجب أن يكون بصيغة ISO صحيحة',
      'date.min': 'تاريخ النهاية يجب أن يكون بعد تاريخ البداية'
    })
});

// Validation schema for ID parameters
export const idParamsSchema = Joi.object({
  id: Joi.string()
    .pattern(/^[0-9a-fA-F]{24}$/)
    .required()
    .messages({
      'string.pattern.base': 'المعرف غير صحيح',
      'any.required': 'المعرف مطلوب'
    })
});

// Validation schema for analytics period
export const analyticsPeriodSchema = Joi.object({
  period: Joi.string()
    .valid('7d', '30d', '90d', '1y')
    .default('30d')
    .messages({
      'any.only': 'فترة التحليل يجب أن تكون إحدى القيم المسموحة'
    })
});

// Validation schema for rating knowledge base articles
export const rateKnowledgeBaseSchema = Joi.object({
  rating: Joi.string()
    .valid('helpful', 'not_helpful')
    .required()
    .messages({
      'any.only': 'التقييم يجب أن يكون مفيد أو غير مفيد',
      'any.required': 'التقييم مطلوب'
    }),
  feedback: Joi.string()
    .max(500)
    .optional()
    .messages({
      'string.max': 'الملاحظات يجب ألا تتجاوز 500 حرف'
    })
});

// Validation schema for rating FAQ
export const rateFAQSchema = Joi.object({
  rating: Joi.string()
    .valid('helpful', 'not_helpful')
    .required()
    .messages({
      'any.only': 'التقييم يجب أن يكون مفيد أو غير مفيد',
      'any.required': 'التقييم مطلوب'
    }),
  feedback: Joi.string()
    .max(500)
    .optional()
    .messages({
      'string.max': 'الملاحظات يجب ألا تتجاوز 500 حرف'
    })
});