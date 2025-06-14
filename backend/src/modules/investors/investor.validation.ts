import Joi from 'joi';

export const investorValidation = {
  createInvestor: Joi.object({
    firstName: Joi.string().required().min(2).max(50).messages({
      'string.empty': 'الاسم الأول مطلوب',
      'string.min': 'الاسم الأول يجب أن يكون أكثر من حرفين',
      'string.max': 'الاسم الأول يجب أن يكون أقل من 50 حرف',
      'any.required': 'الاسم الأول مطلوب'
    }),
    lastName: Joi.string().required().min(2).max(50).messages({
      'string.empty': 'اسم العائلة مطلوب',
      'string.min': 'اسم العائلة يجب أن يكون أكثر من حرفين',
      'string.max': 'اسم العائلة يجب أن يكون أقل من 50 حرف',
      'any.required': 'اسم العائلة مطلوب'
    }),
    email: Joi.string().email().required().messages({
      'string.email': 'البريد الإلكتروني غير صحيح',
      'string.empty': 'البريد الإلكتروني مطلوب',
      'any.required': 'البريد الإلكتروني مطلوب'
    }),
    phone: Joi.string().required().pattern(/^\+966[0-9]{9}$/).messages({
      'string.pattern.base': 'رقم الهاتف يجب أن يكون بصيغة سعودية صحيحة (+966xxxxxxxxx)',
      'string.empty': 'رقم الهاتف مطلوب',
      'any.required': 'رقم الهاتف مطلوب'
    }),
    nationalId: Joi.string().required().pattern(/^[0-9]{10}$/).messages({
      'string.pattern.base': 'رقم الهوية الوطنية يجب أن يكون مكون من 10 أرقام',
      'string.empty': 'رقم الهوية الوطنية مطلوب',
      'any.required': 'رقم الهوية الوطنية مطلوب'
    }),
    dateOfBirth: Joi.date().required().max('now').messages({
      'date.base': 'تاريخ الميلاد غير صحيح',
      'date.max': 'تاريخ الميلاد لا يمكن أن يكون في المستقبل',
      'any.required': 'تاريخ الميلاد مطلوب'
    }),
    address: Joi.string().required().min(10).max(200).messages({
      'string.empty': 'العنوان مطلوب',
      'string.min': 'العنوان يجب أن يكون أكثر من 10 أحرف',
      'string.max': 'العنوان يجب أن يكون أقل من 200 حرف',
      'any.required': 'العنوان مطلوب'
    }),
    investorType: Joi.string().valid('individual', 'corporate').required().messages({
      'any.only': 'نوع المستثمر يجب أن يكون: فرد أو شركة',
      'any.required': 'نوع المستثمر مطلوب'
    }),
    riskProfile: Joi.string().valid('conservative', 'moderate', 'aggressive').required().messages({
      'any.only': 'ملف المخاطر يجب أن يكون: محافظ، معتدل، أو عدواني',
      'any.required': 'ملف المخاطر مطلوب'
    }),
    status: Joi.string().valid('active', 'inactive', 'suspended').default('active').messages({
      'any.only': 'حالة المستثمر يجب أن تكون: نشط، غير نشط، أو معلق'
    })
  }),

  updateInvestor: Joi.object({
    firstName: Joi.string().min(2).max(50).messages({
      'string.min': 'الاسم الأول يجب أن يكون أكثر من حرفين',
      'string.max': 'الاسم الأول يجب أن يكون أقل من 50 حرف'
    }),
    lastName: Joi.string().min(2).max(50).messages({
      'string.min': 'اسم العائلة يجب أن يكون أكثر من حرفين',
      'string.max': 'اسم العائلة يجب أن يكون أقل من 50 حرف'
    }),
    email: Joi.string().email().messages({
      'string.email': 'البريد الإلكتروني غير صحيح'
    }),
    phone: Joi.string().pattern(/^\+966[0-9]{9}$/).messages({
      'string.pattern.base': 'رقم الهاتف يجب أن يكون بصيغة سعودية صحيحة (+966xxxxxxxxx)'
    }),
    nationalId: Joi.string().pattern(/^[0-9]{10}$/).messages({
      'string.pattern.base': 'رقم الهوية الوطنية يجب أن يكون مكون من 10 أرقام'
    }),
    dateOfBirth: Joi.date().max('now').messages({
      'date.base': 'تاريخ الميلاد غير صحيح',
      'date.max': 'تاريخ الميلاد لا يمكن أن يكون في المستقبل'
    }),
    address: Joi.string().min(10).max(200).messages({
      'string.min': 'العنوان يجب أن يكون أكثر من 10 أحرف',
      'string.max': 'العنوان يجب أن يكون أقل من 200 حرف'
    }),
    investorType: Joi.string().valid('individual', 'corporate').messages({
      'any.only': 'نوع المستثمر يجب أن يكون: فرد أو شركة'
    }),
    riskProfile: Joi.string().valid('conservative', 'moderate', 'aggressive').messages({
      'any.only': 'ملف المخاطر يجب أن يكون: محافظ، معتدل، أو عدواني'
    }),
    status: Joi.string().valid('active', 'inactive', 'suspended').messages({
      'any.only': 'حالة المستثمر يجب أن تكون: نشط، غير نشط، أو معلق'
    })
  }),

  createInvestment: Joi.object({
    investmentType: Joi.string().required().min(2).max(100).messages({
      'string.empty': 'نوع الاستثمار مطلوب',
      'string.min': 'نوع الاستثمار يجب أن يكون أكثر من حرفين',
      'string.max': 'نوع الاستثمار يجب أن يكون أقل من 100 حرف',
      'any.required': 'نوع الاستثمار مطلوب'
    }),
    amount: Joi.number().positive().required().min(1000).messages({
      'number.base': 'مبلغ الاستثمار يجب أن يكون رقم',
      'number.positive': 'مبلغ الاستثمار يجب أن يكون أكبر من صفر',
      'number.min': 'الحد الأدنى للاستثمار هو 1000 ريال',
      'any.required': 'مبلغ الاستثمار مطلوب'
    }),
    purchaseDate: Joi.date().max('now').messages({
      'date.base': 'تاريخ الشراء غير صحيح',
      'date.max': 'تاريخ الشراء لا يمكن أن يكون في المستقبل'
    })
  })
};