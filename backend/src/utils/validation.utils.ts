import { z } from 'zod';

// مخططات التحقق الأساسية
export const commonSchemas = {
  // معرف UUID
  uuid: z.string().uuid('معرف غير صالح'),
  
  // البريد الإلكتروني
  email: z.string().email('بريد إلكتروني غير صالح'),
  
  // رقم الهاتف السعودي
  saudiPhone: z.string().regex(
    /^(\+966|966|0)?5[0-9]{8}$/,
    'رقم هاتف سعودي غير صالح'
  ),
  
  // كلمة المرور
  password: z.string()
    .min(8, 'كلمة المرور يجب أن تكون 8 أحرف على الأقل')
    .regex(/[A-Z]/, 'يجب أن تحتوي على حرف كبير')
    .regex(/[a-z]/, 'يجب أن تحتوي على حرف صغير')
    .regex(/\d/, 'يجب أن تحتوي على رقم')
    .regex(/[!@#$%^&*(),.?":{}|<>]/, 'يجب أن تحتوي على رمز خاص'),
  
  // الاسم
  name: z.string()
    .min(2, 'الاسم قصير جداً')
    .max(50, 'الاسم طويل جداً')
    .regex(/^[\u0600-\u06FFa-zA-Z\s]+$/, 'الاسم يحتوي على أحرف غير صالحة'),
  
  // التاريخ
  date: z.string().datetime('تاريخ غير صالح'),
  
  // النطاق الزمني
  dateRange: z.object({
    from: z.string().datetime(),
    to: z.string().datetime()
  }).refine(data => new Date(data.from) <= new Date(data.to), {
    message: 'تاريخ البداية يجب أن يكون قبل تاريخ النهاية'
  }),
  
  // المبلغ المالي
  amount: z.number()
    .positive('المبلغ يجب أن يكون موجباً')
    .multipleOf(0.01, 'المبلغ يجب أن يكون بدقة هللتين'),
  
  // النسبة المئوية
  percentage: z.number()
    .min(0, 'النسبة لا يمكن أن تكون سالبة')
    .max(100, 'النسبة لا يمكن أن تتجاوز 100%'),
  
  // رقم الهوية الوطنية السعودية
  saudiId: z.string().regex(
    /^[12]\d{9}$/,
    'رقم هوية وطنية غير صالح'
  ),
  
  // رقم الإقامة
  iqamaNumber: z.string().regex(
    /^2\d{9}$/,
    'رقم إقامة غير صالح'
  ),
  
  // رقم السجل التجاري
  commercialRegister: z.string().regex(
    /^\d{10}$/,
    'رقم سجل تجاري غير صالح'
  ),
  
  // رقم ضريبي
  vatNumber: z.string().regex(
    /^3\d{14}$/,
    'رقم ضريبي غير صالح'
  ),
  
  // العنوان
  address: z.object({
    street: z.string().min(1, 'الشارع مطلوب'),
    building: z.string().optional(),
    unit: z.string().optional(),
    city: z.string().min(1, 'المدينة مطلوبة'),
    district: z.string().optional(),
    postalCode: z.string().optional(),
    country: z.string().default('المملكة العربية السعودية')
  }),
  
  // الإحداثيات الجغرافية
  coordinates: z.object({
    latitude: z.number().min(-90).max(90),
    longitude: z.number().min(-180).max(180)
  }),
  
  // ترقيم الصفحات
  pagination: z.object({
    page: z.number().int().positive().default(1),
    limit: z.number().int().positive().max(100).default(20),
    sortBy: z.string().optional(),
    sortOrder: z.enum(['asc', 'desc']).default('desc')
  }),
  
  // البحث
  searchQuery: z.object({
    q: z.string().optional(),
    fields: z.array(z.string()).optional()
  }),
  
  // الفلترة
  filters: z.record(z.union([
    z.string(),
    z.number(),
    z.boolean(),
    z.array(z.string())
  ]))
};

// مخططات خاصة بالعملاء
export const customerSchemas = {
  // إنشاء عميل
  create: z.object({
    name: commonSchemas.name,
    nationalId: z.union([
      commonSchemas.saudiId,
      commonSchemas.iqamaNumber
    ]),
    phone: commonSchemas.saudiPhone,
    email: commonSchemas.email.optional(),
    address: commonSchemas.address,
    birthDate: z.string().optional(),
    employmentStatus: z.enum(['employed', 'self_employed', 'retired', 'unemployed']),
    monthlyIncome: commonSchemas.amount.optional(),
    bankAccount: z.string().optional(),
    notes: z.string().optional()
  }),
  
  // تحديث عميل
  update: z.object({
    name: commonSchemas.name.optional(),
    phone: commonSchemas.saudiPhone.optional(),
    email: commonSchemas.email.optional(),
    address: commonSchemas.address.optional(),
    employmentStatus: z.enum(['employed', 'self_employed', 'retired', 'unemployed']).optional(),
    monthlyIncome: commonSchemas.amount.optional(),
    bankAccount: z.string().optional(),
    notes: z.string().optional()
  })
};

// مخططات خاصة بالمنتجات
export const productSchemas = {
  // إنشاء منتج
  create: z.object({
    name: z.string().min(1, 'اسم المنتج مطلوب'),
    sku: z.string().min(1, 'رمز المنتج مطلوب'),
    category: z.string().min(1, 'الفئة مطلوبة'),
    brand: z.string().optional(),
    description: z.string().optional(),
    price: commonSchemas.amount,
    cost: commonSchemas.amount.optional(),
    quantity: z.number().int().nonnegative(),
    minQuantity: z.number().int().nonnegative().default(0),
    unit: z.string().default('قطعة'),
    barcode: z.string().optional(),
    images: z.array(z.string()).optional(),
    specifications: z.record(z.string()).optional(),
    isActive: z.boolean().default(true)
  }),
  
  // تحديث مخزون
  updateStock: z.object({
    type: z.enum(['add', 'subtract', 'set']),
    quantity: z.number().int().positive(),
    reason: z.string().optional(),
    reference: z.string().optional()
  })
};

// مخططات خاصة بعقود التقسيط
export const contractSchemas = {
  // إنشاء عقد
  create: z.object({
    customerId: commonSchemas.uuid,
    items: z.array(z.object({
      productId: commonSchemas.uuid,
      quantity: z.number().int().positive(),
      price: commonSchemas.amount,
      discount: commonSchemas.amount.optional()
    })),
    downPayment: commonSchemas.amount,
    installmentMonths: z.number().int().positive().max(60),
    profitRate: commonSchemas.percentage,
    insuranceAmount: commonSchemas.amount.optional(),
    administrativeFees: commonSchemas.amount.optional(),
    guarantors: z.array(z.object({
      name: commonSchemas.name,
      nationalId: z.union([commonSchemas.saudiId, commonSchemas.iqamaNumber]),
      phone: commonSchemas.saudiPhone,
      relationship: z.string(),
      monthlyIncome: commonSchemas.amount.optional()
    })).optional(),
    notes: z.string().optional()
  }),
  
  // دفعة
  payment: z.object({
    amount: commonSchemas.amount,
    method: z.enum(['cash', 'bank_transfer', 'check', 'online']),
    reference: z.string().optional(),
    date: commonSchemas.date.optional(),
    notes: z.string().optional()
  })
};

// مخططات خاصة بالموظفين
export const employeeSchemas = {
  // إنشاء موظف
  create: z.object({
    name: commonSchemas.name,
    email: commonSchemas.email,
    phone: commonSchemas.saudiPhone,
    nationalId: z.union([commonSchemas.saudiId, commonSchemas.iqamaNumber]),
    department: z.string(),
    position: z.string(),
    hireDate: commonSchemas.date,
    salary: commonSchemas.amount,
    role: z.string(),
    permissions: z.array(z.string()).optional(),
    isActive: z.boolean().default(true)
  }),
  
  // تحديث موظف
  update: z.object({
    name: commonSchemas.name.optional(),
    email: commonSchemas.email.optional(),
    phone: commonSchemas.saudiPhone.optional(),
    department: z.string().optional(),
    position: z.string().optional(),
    salary: commonSchemas.amount.optional(),
    role: z.string().optional(),
    permissions: z.array(z.string()).optional(),
    isActive: z.boolean().optional()
  })
};

// مخططات خاصة بالمهام
export const taskSchemas = {
  // إنشاء مهمة
  create: z.object({
    title: z.string().min(1, 'عنوان المهمة مطلوب'),
    description: z.string().optional(),
    type: z.enum(['collection', 'delivery', 'maintenance', 'legal', 'administrative', 'other']),
    priority: z.enum(['low', 'medium', 'high', 'urgent']).default('medium'),
    assigneeId: commonSchemas.uuid.optional(),
    customerId: commonSchemas.uuid.optional(),
    contractId: commonSchemas.uuid.optional(),
    dueDate: commonSchemas.date.optional(),
    location: commonSchemas.address.optional(),
    coordinates: commonSchemas.coordinates.optional(),
    attachments: z.array(z.string()).optional(),
    tags: z.array(z.string()).optional()
  }),
  
  // تحديث حالة المهمة
  updateStatus: z.object({
    status: z.enum(['pending', 'in_progress', 'completed', 'cancelled', 'failed']),
    notes: z.string().optional(),
    completedAt: commonSchemas.date.optional(),
    failureReason: z.string().optional()
  })
};

// مخططات خاصة بالتقارير
export const reportSchemas = {
  // معايير التقرير
  criteria: z.object({
    type: z.enum([
      'sales', 'collections', 'inventory', 'customers', 
      'contracts', 'employees', 'tasks', 'financial'
    ]),
    dateRange: commonSchemas.dateRange,
    groupBy: z.enum(['day', 'week', 'month', 'quarter', 'year']).optional(),
    filters: commonSchemas.filters.optional(),
    format: z.enum(['json', 'csv', 'pdf', 'excel']).default('json')
  })
};

// دوال مساعدة للتحقق
export const validateData = <T>(
  schema: z.ZodSchema<T>,
  data: unknown
): { success: true; data: T } | { success: false; errors: z.ZodError } => {
  const result = schema.safeParse(data);
  
  if (result.success) {
    return { success: true, data: result.data };
  }
  
  return { success: false, errors: result.error };
};

// Duplicate functions removed - they are already defined earlier in the file

// تصدير أنواع TypeScript - تم نقلها إلى بداية الملف

// دالة لتنسيق أخطاء Zod
export const formatZodErrors = (error: z.ZodError): Record<string, string[]> => {
  const formatted: Record<string, string[]> = {};
  
  error.errors.forEach((err) => {
    const path = err.path.join('.');
    if (!formatted[path]) {
      formatted[path] = [];
    }
    formatted[path].push(err.message);
  });
  
  return formatted;
};

// دالة للتحقق الجزئي
export const validatePartial = <T>(
  schema: z.ZodSchema<T>,
  data: unknown
): { success: true; data: Partial<T> } | { success: false; errors: z.ZodError } => {
  const partialSchema = (schema as any).partial();
  const result = partialSchema.safeParse(data);
  
  if (result.success) {
    return { success: true, data: result.data };
  }
  
  return { success: false, errors: result.error };
};

// Duplicate functions removed - they are already defined earlier in the file

// تصدير أنواع TypeScript - تم نقلها إلى بداية الملف