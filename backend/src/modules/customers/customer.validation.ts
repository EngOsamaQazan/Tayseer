import { z } from 'zod';
import { 
  emailSchema, 
  phoneSchema, 
  uuidSchema,
  paginationSchema
} from '../../utils/validation.util';

// أنواع العملاء
export const customerTypeEnum = z.enum(['individual', 'company']);

// حالات العملاء
export const customerStatusEnum = z.enum(['active', 'inactive', 'suspended']);

// أنواع المستندات
export const documentTypeEnum = z.enum([
  'id_card',
  'passport',
  'commercial_register',
  'tax_certificate',
  'contract',
  'invoice',
  'other'
]);

// مخطط العنوان
export const addressSchema = z.object({
  street: z.string().min(1).max(255),
  city: z.string().min(1).max(100),
  state: z.string().min(1).max(100).optional(),
  postalCode: z.string().min(1).max(20).optional(),
  country: z.string().min(1).max(100),
  isDefault: z.boolean().default(false)
});

// مخطط إنشاء عميل
export const createCustomerSchema = z.object({
  body: z.object({
    // بيانات أساسية
    name: z.string().min(2).max(255),
    email: emailSchema,
    phone: phoneSchema,
    alternatePhone: phoneSchema.optional(),
    
    // نوع العميل
    type: customerTypeEnum,
    
    // بيانات الشركة (مطلوبة إذا كان النوع شركة)
    companyName: z.string().min(2).max(255).optional(),
    taxNumber: z.string().min(5).max(50).optional(),
    commercialRegister: z.string().min(5).max(50).optional(),
    
    // بيانات شخصية (مطلوبة إذا كان النوع فرد)
    nationalId: z.string().min(5).max(50).optional(),
    dateOfBirth: z.string().datetime().optional(),
    
    // العنوان
    address: addressSchema.optional(),
    
    // معلومات إضافية
    notes: z.string().max(1000).optional(),
    tags: z.array(z.string().max(50)).max(10).optional(),
    
    // الحالة
    status: customerStatusEnum.default('active'),
    
    // حدود الائتمان
    creditLimit: z.number().min(0).optional(),
    
    // اللغة المفضلة
    preferredLanguage: z.enum(['ar', 'en']).default('ar'),
    
    // طريقة التواصل المفضلة
    preferredContactMethod: z.enum(['email', 'phone', 'sms', 'whatsapp']).default('phone')
  }).refine(
    (data) => {
      // التحقق من البيانات المطلوبة حسب نوع العميل
      if (data.type === 'company') {
        return !!data.companyName && !!data.taxNumber;
      }
      if (data.type === 'individual') {
        return !!data.nationalId;
      }
      return true;
    },
    {
      message: 'البيانات المطلوبة غير مكتملة حسب نوع العميل'
    }
  )
});

// مخطط تحديث عميل
export const updateCustomerSchema = z.object({
  params: z.object({
    id: uuidSchema
  }),
  body: z.object({
    name: z.string().min(2).max(255).optional(),
    email: emailSchema.optional(),
    phone: phoneSchema.optional(),
    alternatePhone: phoneSchema.optional(),
    type: customerTypeEnum.optional(),
    companyName: z.string().min(2).max(255).optional(),
    taxNumber: z.string().min(5).max(50).optional(),
    commercialRegister: z.string().min(5).max(50).optional(),
    nationalId: z.string().min(5).max(50).optional(),
    dateOfBirth: z.string().datetime().optional(),
    address: addressSchema.optional(),
    notes: z.string().max(1000).optional(),
    tags: z.array(z.string().max(50)).max(10).optional(),
    status: customerStatusEnum.optional(),
    creditLimit: z.number().min(0).optional(),
    preferredLanguage: z.enum(['ar', 'en']).optional(),
    preferredContactMethod: z.enum(['email', 'phone', 'sms', 'whatsapp']).optional()
  }).refine(
    (data) => {
      // التحقق من وجود حقل واحد على الأقل للتحديث
      return Object.keys(data).length > 0;
    },
    {
      message: 'يجب توفير حقل واحد على الأقل للتحديث'
    }
  )
});

// مخطط معرف العميل
export const customerIdSchema = z.object({
  params: z.object({
    id: uuidSchema
  })
});

// مخطط البحث في العملاء
export const customerSearchSchema = z.object({
  query: z.object({
    query: z.string().min(1).max(100),
    fields: z.array(z.enum([
      'name',
      'email',
      'phone',
      'companyName',
      'nationalId',
      'taxNumber',
      'tags'
    ])).optional()
  })
});

// مخطط قائمة العملاء
export const customerListSchema = z.object({
  query: z.object({
    ...paginationSchema.shape,
    search: z.string().max(100).optional(),
    status: customerStatusEnum.optional(),
    type: customerTypeEnum.optional(),
    tags: z.array(z.string()).optional(),
    sortBy: z.enum(['name', 'createdAt', 'updatedAt', 'creditLimit']).optional(),
    sortOrder: z.enum(['asc', 'desc']).optional()
  })
});

// مخطط رفع مستند
export const uploadDocumentSchema = z.object({
  params: z.object({
    id: uuidSchema
  }),
  body: z.object({
    title: z.string().min(1).max(255),
    description: z.string().max(500).optional(),
    type: documentTypeEnum,
    expiryDate: z.string().datetime().optional()
  })
});

// مخطط حذف مستند
export const deleteDocumentSchema = z.object({
  params: z.object({
    id: uuidSchema,
    documentId: uuidSchema
  })
});

// مخطط ملاحظة العميل
export const customerNoteSchema = z.object({
  params: z.object({
    id: uuidSchema
  }),
  body: z.object({
    content: z.string().min(1).max(1000),
    isPrivate: z.boolean().default(false),
    category: z.enum(['general', 'financial', 'support', 'complaint', 'other']).default('general')
  })
});

// مخطط وسوم العميل
export const customerTagSchema = z.object({
  params: z.object({
    id: uuidSchema
  }),
  body: z.object({
    action: z.enum(['add', 'remove', 'replace']),
    tags: z.array(z.string().min(1).max(50)).min(1).max(10)
  })
});

// مخطط تصدير العملاء
export const exportCustomersSchema = z.object({
  query: z.object({
    format: z.enum(['xlsx', 'csv']).default('xlsx'),
    filters: z.string().optional().transform((val) => {
      if (!val) return {};
      try {
        return JSON.parse(val);
      } catch {
        return {};
      }
    })
  })
});

// مخطط إحصائيات العميل
export const customerStatisticsSchema = z.object({
  params: z.object({
    id: uuidSchema
  }),
  query: z.object({
    period: z.enum(['week', 'month', 'quarter', 'year', 'all']).optional(),
    startDate: z.string().datetime().optional(),
    endDate: z.string().datetime().optional()
  })
});

// مخطط سجل النشاط
export const activityLogSchema = z.object({
  params: z.object({
    id: uuidSchema
  }),
  query: z.object({
    ...paginationSchema.shape,
    action: z.enum([
      'created',
      'updated',
      'deleted',
      'viewed',
      'exported',
      'note_added',
      'document_uploaded',
      'document_deleted',
      'tag_updated'
    ]).optional(),
    startDate: z.string().datetime().optional(),
    endDate: z.string().datetime().optional()
  })
});

// تصدير الأنواع
export type CreateCustomerDto = z.infer<typeof createCustomerSchema>['body'];
export type UpdateCustomerDto = z.infer<typeof updateCustomerSchema>['body'];
export type CustomerSearchDto = z.infer<typeof customerSearchSchema>['query'];
export type CustomerListDto = z.infer<typeof customerListSchema>['query'];
export type UploadDocumentDto = z.infer<typeof uploadDocumentSchema>['body'];
export type CustomerNoteDto = z.infer<typeof customerNoteSchema>['body'];
export type CustomerTagDto = z.infer<typeof customerTagSchema>['body'];
export type ExportCustomersDto = z.infer<typeof exportCustomersSchema>['query'];
export type CustomerStatisticsDto = z.infer<typeof customerStatisticsSchema>['query'];
export type ActivityLogDto = z.infer<typeof activityLogSchema>['query'];