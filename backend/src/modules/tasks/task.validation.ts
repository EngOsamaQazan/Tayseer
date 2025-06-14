import Joi from 'joi';

export const taskValidation = {
  createTask: Joi.object({
    title: Joi.string()
      .required()
      .min(3)
      .max(200)
      .messages({
        'string.empty': 'عنوان المهمة مطلوب',
        'string.min': 'عنوان المهمة يجب أن يكون على الأقل 3 أحرف',
        'string.max': 'عنوان المهمة يجب أن لا يتجاوز 200 حرف',
        'any.required': 'عنوان المهمة مطلوب'
      }),
    
    description: Joi.string()
      .required()
      .min(10)
      .max(1000)
      .messages({
        'string.empty': 'وصف المهمة مطلوب',
        'string.min': 'وصف المهمة يجب أن يكون على الأقل 10 أحرف',
        'string.max': 'وصف المهمة يجب أن لا يتجاوز 1000 حرف',
        'any.required': 'وصف المهمة مطلوب'
      }),
    
    status: Joi.string()
      .valid('pending', 'in_progress', 'completed', 'cancelled')
      .default('pending')
      .messages({
        'any.only': 'حالة المهمة يجب أن تكون واحدة من: pending, in_progress, completed, cancelled'
      }),
    
    priority: Joi.string()
      .valid('low', 'medium', 'high', 'urgent')
      .default('medium')
      .messages({
        'any.only': 'أولوية المهمة يجب أن تكون واحدة من: low, medium, high, urgent'
      }),
    
    assigneeId: Joi.string()
      .optional()
      .messages({
        'string.base': 'معرف المكلف يجب أن يكون نص'
      }),
    
    projectId: Joi.string()
      .optional()
      .messages({
        'string.base': 'معرف المشروع يجب أن يكون نص'
      }),
    
    dueDate: Joi.date()
      .optional()
      .min('now')
      .messages({
        'date.base': 'تاريخ الاستحقاق يجب أن يكون تاريخ صالح',
        'date.min': 'تاريخ الاستحقاق لا يمكن أن يكون في الماضي'
      }),
    
    estimatedHours: Joi.number()
      .optional()
      .min(0.5)
      .max(1000)
      .messages({
        'number.base': 'الساعات المقدرة يجب أن تكون رقم',
        'number.min': 'الساعات المقدرة يجب أن تكون على الأقل 0.5 ساعة',
        'number.max': 'الساعات المقدرة يجب أن لا تتجاوز 1000 ساعة'
      }),
    
    tags: Joi.array()
      .items(Joi.string().min(2).max(50))
      .optional()
      .messages({
        'array.base': 'العلامات يجب أن تكون مصفوفة',
        'string.min': 'كل علامة يجب أن تكون على الأقل حرفين',
        'string.max': 'كل علامة يجب أن لا تتجاوز 50 حرف'
      })
  }),

  updateTask: Joi.object({
    title: Joi.string()
      .optional()
      .min(3)
      .max(200)
      .messages({
        'string.min': 'عنوان المهمة يجب أن يكون على الأقل 3 أحرف',
        'string.max': 'عنوان المهمة يجب أن لا يتجاوز 200 حرف'
      }),
    
    description: Joi.string()
      .optional()
      .min(10)
      .max(1000)
      .messages({
        'string.min': 'وصف المهمة يجب أن يكون على الأقل 10 أحرف',
        'string.max': 'وصف المهمة يجب أن لا يتجاوز 1000 حرف'
      }),
    
    status: Joi.string()
      .optional()
      .valid('pending', 'in_progress', 'completed', 'cancelled')
      .messages({
        'any.only': 'حالة المهمة يجب أن تكون واحدة من: pending, in_progress, completed, cancelled'
      }),
    
    priority: Joi.string()
      .optional()
      .valid('low', 'medium', 'high', 'urgent')
      .messages({
        'any.only': 'أولوية المهمة يجب أن تكون واحدة من: low, medium, high, urgent'
      }),
    
    assigneeId: Joi.string()
      .optional()
      .allow(null)
      .messages({
        'string.base': 'معرف المكلف يجب أن يكون نص'
      }),
    
    projectId: Joi.string()
      .optional()
      .allow(null)
      .messages({
        'string.base': 'معرف المشروع يجب أن يكون نص'
      }),
    
    dueDate: Joi.date()
      .optional()
      .allow(null)
      .messages({
        'date.base': 'تاريخ الاستحقاق يجب أن يكون تاريخ صالح'
      }),
    
    estimatedHours: Joi.number()
      .optional()
      .min(0.5)
      .max(1000)
      .messages({
        'number.base': 'الساعات المقدرة يجب أن تكون رقم',
        'number.min': 'الساعات المقدرة يجب أن تكون على الأقل 0.5 ساعة',
        'number.max': 'الساعات المقدرة يجب أن لا تتجاوز 1000 ساعة'
      }),
    
    actualHours: Joi.number()
      .optional()
      .min(0)
      .max(1000)
      .messages({
        'number.base': 'الساعات الفعلية يجب أن تكون رقم',
        'number.min': 'الساعات الفعلية لا يمكن أن تكون سالبة',
        'number.max': 'الساعات الفعلية يجب أن لا تتجاوز 1000 ساعة'
      }),
    
    tags: Joi.array()
      .items(Joi.string().min(2).max(50))
      .optional()
      .messages({
        'array.base': 'العلامات يجب أن تكون مصفوفة',
        'string.min': 'كل علامة يجب أن تكون على الأقل حرفين',
        'string.max': 'كل علامة يجب أن لا تتجاوز 50 حرف'
      })
  }),

  assignTask: Joi.object({
    assigneeId: Joi.string()
      .required()
      .messages({
        'string.empty': 'معرف المكلف مطلوب',
        'any.required': 'معرف المكلف مطلوب'
      })
  }),

  updateTaskStatus: Joi.object({
    status: Joi.string()
      .required()
      .valid('pending', 'in_progress', 'completed', 'cancelled')
      .messages({
        'string.empty': 'حالة المهمة مطلوبة',
        'any.required': 'حالة المهمة مطلوبة',
        'any.only': 'حالة المهمة يجب أن تكون واحدة من: pending, in_progress, completed, cancelled'
      })
  }),

  addComment: Joi.object({
    userId: Joi.string()
      .required()
      .messages({
        'string.empty': 'معرف المستخدم مطلوب',
        'any.required': 'معرف المستخدم مطلوب'
      }),
    
    content: Joi.string()
      .required()
      .min(5)
      .max(500)
      .messages({
        'string.empty': 'محتوى التعليق مطلوب',
        'string.min': 'التعليق يجب أن يكون على الأقل 5 أحرف',
        'string.max': 'التعليق يجب أن لا يتجاوز 500 حرف',
        'any.required': 'محتوى التعليق مطلوب'
      })
  })
};