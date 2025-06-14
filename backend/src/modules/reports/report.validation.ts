import Joi from 'joi';

class ReportValidation {
  // التحقق من التقرير المخصص
  customReport = {
    body: Joi.object({
      title: Joi.string().min(3).max(100).required().messages({
        'string.empty': 'عنوان التقرير مطلوب',
        'string.min': 'عنوان التقرير يجب أن يكون على الأقل 3 أحرف',
        'string.max': 'عنوان التقرير يجب أن يكون أقل من 100 حرف'
      }),
      description: Joi.string().max(500).optional().messages({
        'string.max': 'وصف التقرير يجب أن يكون أقل من 500 حرف'
      }),
      dateRange: Joi.object({
        startDate: Joi.date().iso().required().messages({
          'date.base': 'تاريخ البداية غير صحيح',
          'any.required': 'تاريخ البداية مطلوب'
        }),
        endDate: Joi.date().iso().min(Joi.ref('startDate')).required().messages({
          'date.base': 'تاريخ النهاية غير صحيح',
          'date.min': 'تاريخ النهاية يجب أن يكون بعد تاريخ البداية',
          'any.required': 'تاريخ النهاية مطلوب'
        })
      }).required(),
      filters: Joi.object({
        department: Joi.string().optional(),
        category: Joi.string().optional(),
        status: Joi.string().valid('active', 'inactive', 'pending', 'completed').optional(),
        minAmount: Joi.number().min(0).optional(),
        maxAmount: Joi.number().min(Joi.ref('minAmount')).optional()
      }).optional(),
      metrics: Joi.array().items(
        Joi.string().valid(
          'revenue',
          'profit',
          'sales_count',
          'customer_count',
          'inventory_value',
          'contracts_count',
          'employee_performance'
        )
      ).min(1).required().messages({
        'array.min': 'يجب اختيار مؤشر واحد على الأقل',
        'any.required': 'المؤشرات مطلوبة'
      }),
      groupBy: Joi.string().valid(
        'day',
        'week',
        'month',
        'quarter',
        'year',
        'department',
        'category',
        'product'
      ).optional(),
      format: Joi.string().valid('table', 'chart', 'summary').default('table')
    })
  };

  // التحقق من معاملات التقرير العامة
  reportQuery = {
    query: Joi.object({
      startDate: Joi.date().iso().optional().messages({
        'date.base': 'تاريخ البداية غير صحيح'
      }),
      endDate: Joi.date().iso().min(Joi.ref('startDate')).optional().messages({
        'date.base': 'تاريخ النهاية غير صحيح',
        'date.min': 'تاريخ النهاية يجب أن يكون بعد تاريخ البداية'
      }),
      productId: Joi.string().uuid().optional().messages({
        'string.guid': 'معرف المنتج غير صحيح'
      }),
      customerId: Joi.string().uuid().optional().messages({
        'string.guid': 'معرف العميل غير صحيح'
      }),
      status: Joi.string().optional(),
      category: Joi.string().optional(),
      department: Joi.string().optional(),
      limit: Joi.number().integer().min(1).max(1000).default(100).optional(),
      offset: Joi.number().integer().min(0).default(0).optional()
    })
  };

  // التحقق من تصدير التقرير
  exportReport = {
    params: Joi.object({
      type: Joi.string().valid(
        'financial',
        'sales',
        'inventory',
        'customers',
        'contracts',
        'performance',
        'custom'
      ).required().messages({
        'any.required': 'نوع التقرير مطلوب',
        'any.only': 'نوع التقرير غير صحيح'
      })
    }),
    body: Joi.object({
      format: Joi.string().valid('pdf', 'excel', 'csv', 'json').default('pdf').messages({
        'any.only': 'تنسيق التصدير غير مدعوم'
      }),
      reportData: Joi.object().required().messages({
        'any.required': 'بيانات التقرير مطلوبة'
      }),
      options: Joi.object({
        includeCharts: Joi.boolean().default(true),
        includeRawData: Joi.boolean().default(false),
        language: Joi.string().valid('ar', 'en').default('ar')
      }).optional()
    })
  };
}

export const reportValidation = new ReportValidation();