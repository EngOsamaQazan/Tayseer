import Joi from 'joi';

export const employeeValidation = {
  createEmployee: Joi.object({
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
    position: Joi.string().required().min(2).max(100).messages({
      'string.empty': 'المنصب مطلوب',
      'string.min': 'المنصب يجب أن يكون أكثر من حرفين',
      'string.max': 'المنصب يجب أن يكون أقل من 100 حرف',
      'any.required': 'المنصب مطلوب'
    }),
    department: Joi.string().required().min(2).max(100).messages({
      'string.empty': 'القسم مطلوب',
      'string.min': 'القسم يجب أن يكون أكثر من حرفين',
      'string.max': 'القسم يجب أن يكون أقل من 100 حرف',
      'any.required': 'القسم مطلوب'
    }),
    hireDate: Joi.date().required().messages({
      'date.base': 'تاريخ التوظيف غير صحيح',
      'any.required': 'تاريخ التوظيف مطلوب'
    }),
    salary: Joi.number().positive().required().messages({
      'number.base': 'الراتب يجب أن يكون رقم',
      'number.positive': 'الراتب يجب أن يكون أكبر من صفر',
      'any.required': 'الراتب مطلوب'
    }),
    status: Joi.string().valid('active', 'inactive', 'terminated').default('active').messages({
      'any.only': 'حالة الموظف يجب أن تكون: نشط، غير نشط، أو منتهي الخدمة'
    })
  }),

  updateEmployee: Joi.object({
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
    position: Joi.string().min(2).max(100).messages({
      'string.min': 'المنصب يجب أن يكون أكثر من حرفين',
      'string.max': 'المنصب يجب أن يكون أقل من 100 حرف'
    }),
    department: Joi.string().min(2).max(100).messages({
      'string.min': 'القسم يجب أن يكون أكثر من حرفين',
      'string.max': 'القسم يجب أن يكون أقل من 100 حرف'
    }),
    hireDate: Joi.date().messages({
      'date.base': 'تاريخ التوظيف غير صحيح'
    }),
    salary: Joi.number().positive().messages({
      'number.base': 'الراتب يجب أن يكون رقم',
      'number.positive': 'الراتب يجب أن يكون أكبر من صفر'
    }),
    status: Joi.string().valid('active', 'inactive', 'terminated').messages({
      'any.only': 'حالة الموظف يجب أن تكون: نشط، غير نشط، أو منتهي الخدمة'
    })
  }),

  recordAttendance: Joi.object({
    checkIn: Joi.date().messages({
      'date.base': 'وقت الدخول غير صحيح'
    }),
    checkOut: Joi.date().messages({
      'date.base': 'وقت الخروج غير صحيح'
    }),
    status: Joi.string().valid('present', 'absent', 'late', 'holiday').required().messages({
      'any.only': 'حالة الحضور يجب أن تكون: حاضر، غائب، متأخر، أو إجازة',
      'any.required': 'حالة الحضور مطلوبة'
    }),
    totalHours: Joi.number().min(0).max(24).messages({
      'number.base': 'عدد الساعات يجب أن يكون رقم',
      'number.min': 'عدد الساعات لا يمكن أن يكون أقل من صفر',
      'number.max': 'عدد الساعات لا يمكن أن يكون أكثر من 24 ساعة'
    })
  })
};