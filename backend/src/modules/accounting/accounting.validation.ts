import { z } from 'zod';

// مخطط إنشاء حساب
const createAccount = z.object({
  accountNumber: z.string().min(1, 'رقم الحساب مطلوب'),
  name: z.string().min(1, 'اسم الحساب مطلوب'),
  type: z.enum(['ASSET', 'LIABILITY', 'EQUITY', 'REVENUE', 'EXPENSE']),
  subType: z.string().optional(),
  parentId: z.string().optional(),
  description: z.string().optional(),
  isActive: z.boolean().optional().default(true),
  tags: z.array(z.string()).optional(),
});

// مخطط إنشاء قيد يومية
const createJournalEntry = z.object({
  date: z.string().datetime(),
  description: z.string().min(1, 'وصف القيد مطلوب'),
  reference: z.string().optional(),
  entries: z.array(z.object({
    accountId: z.string().min(1, 'معرف الحساب مطلوب'),
    debit: z.number().min(0).optional(),
    credit: z.number().min(0).optional(),
    description: z.string().optional(),
    costCenterId: z.string().optional(),
    projectId: z.string().optional(),
  })).min(2, 'القيد يجب أن يحتوي على سطرين على الأقل'),
  attachments: z.array(z.string()).optional(),
});

// مخطط إنشاء فاتورة
const createInvoice = z.object({
  type: z.enum(['SALES', 'PURCHASE']),
  invoiceNumber: z.string().optional(),
  date: z.string().datetime(),
  dueDate: z.string().datetime(),
  customerId: z.string().optional(),
  supplierId: z.string().optional(),
  items: z.array(z.object({
    productId: z.string().optional(),
    description: z.string().min(1),
    quantity: z.number().positive(),
    unitPrice: z.number().positive(),
    discount: z.number().min(0).max(100).optional().default(0),
    taxRate: z.number().min(0).max(100).optional().default(0),
    accountId: z.string().min(1),
  })).min(1),
  subtotal: z.number().positive(),
  discountAmount: z.number().min(0).optional().default(0),
  taxAmount: z.number().min(0).optional().default(0),
  total: z.number().positive(),
  notes: z.string().optional(),
  status: z.enum(['DRAFT', 'PENDING', 'PAID', 'CANCELLED']).optional().default('PENDING'),
});

// مخطط إنشاء دفعة
const createPayment = z.object({
  type: z.enum(['RECEIPT', 'PAYMENT']),
  paymentNumber: z.string().optional(),
  date: z.string().datetime(),
  amount: z.number().positive(),
  customerId: z.string().optional(),
  supplierId: z.string().optional(),
  invoiceId: z.string().optional(),
  accountId: z.string().min(1, 'الحساب البنكي مطلوب'),
  paymentMethodId: z.string().min(1, 'طريقة الدفع مطلوبة'),
  reference: z.string().optional(),
  description: z.string().optional(),
  attachments: z.array(z.string()).optional(),
});

// مخطط تقرير النطاق الزمني
const dateRangeReport = z.object({
  startDate: z.string().datetime(),
  endDate: z.string().datetime(),
  format: z.enum(['JSON', 'EXCEL', 'PDF']).optional().default('JSON'),
});

// مخطط إنشاء موازنة
const createBudget = z.object({
  name: z.string().min(1, 'اسم الموازنة مطلوب'),
  year: z.number().int().min(2000).max(2100),
  type: z.enum(['ANNUAL', 'QUARTERLY', 'MONTHLY']),
  startDate: z.string().datetime(),
  endDate: z.string().datetime(),
  items: z.array(z.object({
    accountId: z.string().min(1),
    amount: z.number(),
    period: z.string().optional(),
    notes: z.string().optional(),
  })).min(1),
  totalAmount: z.number(),
  description: z.string().optional(),
  status: z.enum(['DRAFT', 'APPROVED', 'ACTIVE', 'CLOSED']).optional().default('DRAFT'),
});

// مخطط تصدير البيانات
const exportData = z.object({
  type: z.enum(['CHART_OF_ACCOUNTS', 'JOURNAL_ENTRIES', 'GENERAL_LEDGER', 'TRIAL_BALANCE']),
  format: z.enum(['EXCEL', 'PDF']),
  filters: z.object({
    startDate: z.string().datetime().optional(),
    endDate: z.string().datetime().optional(),
    accountIds: z.array(z.string()).optional(),
    status: z.string().optional(),
  }).optional(),
});

// مخطط البحث في القيود
const searchJournalEntries = z.object({
  query: z.string().optional(),
  startDate: z.string().datetime().optional(),
  endDate: z.string().datetime().optional(),
  accountId: z.string().optional(),
  status: z.enum(['DRAFT', 'POSTED', 'CANCELLED']).optional(),
  minAmount: z.number().optional(),
  maxAmount: z.number().optional(),
  page: z.number().int().positive().optional().default(1),
  limit: z.number().int().positive().max(100).optional().default(20),
  sortBy: z.enum(['date', 'entryNumber', 'amount']).optional().default('date'),
  sortOrder: z.enum(['asc', 'desc']).optional().default('desc'),
});

// مخطط تحديث حساب
const updateAccount = z.object({
  name: z.string().min(1).optional(),
  description: z.string().optional(),
  isActive: z.boolean().optional(),
  tags: z.array(z.string()).optional(),
});

// مخطط تحديث قيد يومية
const updateJournalEntry = z.object({
  description: z.string().min(1).optional(),
  reference: z.string().optional(),
  attachments: z.array(z.string()).optional(),
});

// مخطط تحديث فاتورة
const updateInvoice = z.object({
  dueDate: z.string().datetime().optional(),
  notes: z.string().optional(),
  status: z.enum(['DRAFT', 'PENDING', 'PAID', 'CANCELLED']).optional(),
});

// مخطط التحقق من التوازن
const validateBalance = z.object({
  entries: z.array(z.object({
    debit: z.number().min(0).optional(),
    credit: z.number().min(0).optional(),
  })),
}).refine((data) => {
  const totalDebit = data.entries.reduce((sum, entry) => sum + (entry.debit || 0), 0);
  const totalCredit = data.entries.reduce((sum, entry) => sum + (entry.credit || 0), 0);
  return Math.abs(totalDebit - totalCredit) < 0.01;
}, {
  message: 'القيد غير متوازن - مجموع المدين يجب أن يساوي مجموع الدائن',
});

// مخطط استيراد دليل الحسابات
const importChartOfAccounts = z.object({
  file: z.any(),
  mapping: z.object({
    accountNumber: z.string(),
    name: z.string(),
    type: z.string(),
    subType: z.string().optional(),
    parentAccountNumber: z.string().optional(),
  }).optional(),
});

// مخطط تقرير الحسابات المدينة/الدائنة
const agingReport = z.object({
  type: z.enum(['RECEIVABLES', 'PAYABLES']),
  asOfDate: z.string().datetime(),
  periods: z.array(z.number().int().positive()).optional().default([30, 60, 90, 120]),
  customerId: z.string().optional(),
  supplierId: z.string().optional(),
});

// مخطط التسوية البنكية
const bankReconciliation = z.object({
  accountId: z.string().min(1, 'الحساب البنكي مطلوب'),
  statementDate: z.string().datetime(),
  statementBalance: z.number(),
  transactions: z.array(z.object({
    date: z.string().datetime(),
    description: z.string(),
    amount: z.number(),
    type: z.enum(['DEPOSIT', 'WITHDRAWAL']),
    reference: z.string().optional(),
    matched: z.boolean().optional().default(false),
    journalEntryId: z.string().optional(),
  })),
});

// تصدير جميع المخططات
export const accountingValidation = {
  createAccount,
  createJournalEntry,
  createInvoice,
  createPayment,
  dateRangeReport,
  createBudget,
  exportData,
  searchJournalEntries,
  updateAccount,
  updateJournalEntry,
  updateInvoice,
  validateBalance,
  importChartOfAccounts,
  agingReport,
  bankReconciliation,
};

// تصدير الأنواع
export type CreateAccountInput = z.infer<typeof createAccount>;
export type CreateJournalEntryInput = z.infer<typeof createJournalEntry>;
export type CreateInvoiceInput = z.infer<typeof createInvoice>;
export type CreatePaymentInput = z.infer<typeof createPayment>;
export type DateRangeReportInput = z.infer<typeof dateRangeReport>;
export type CreateBudgetInput = z.infer<typeof createBudget>;
export type ExportDataInput = z.infer<typeof exportData>;
export type SearchJournalEntriesInput = z.infer<typeof searchJournalEntries>;
export type UpdateAccountInput = z.infer<typeof updateAccount>;
export type UpdateJournalEntryInput = z.infer<typeof updateJournalEntry>;
export type UpdateInvoiceInput = z.infer<typeof updateInvoice>;
export type ImportChartOfAccountsInput = z.infer<typeof importChartOfAccounts>;
export type AgingReportInput = z.infer<typeof agingReport>;
export type BankReconciliationInput = z.infer<typeof bankReconciliation>;