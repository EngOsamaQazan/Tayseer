// واجهات الحسابات
export interface Account {
  id: string;
  accountNumber: string;
  name: string;
  type: AccountType;
  subType?: string;
  parentId?: string;
  parent?: Account;
  children?: Account[];
  description?: string;
  isActive: boolean;
  balance: number;
  currency: string;
  tags?: string[];
  tenantId: string;
  createdAt: Date;
  updatedAt: Date;
  deletedAt?: Date;
}

export enum AccountType {
  ASSET = 'ASSET',
  LIABILITY = 'LIABILITY',
  EQUITY = 'EQUITY',
  REVENUE = 'REVENUE',
  EXPENSE = 'EXPENSE'
}

// واجهات القيود اليومية
export interface JournalEntry {
  id: string;
  entryNumber: string;
  date: Date;
  description: string;
  reference?: string;
  status: JournalEntryStatus;
  entries: JournalEntryLine[];
  totalDebit: number;
  totalCredit: number;
  attachments?: string[];
  postedBy?: string;
  postedAt?: Date;
  reversedBy?: string;
  reversedAt?: Date;
  originalEntryId?: string;
  tenantId: string;
  createdBy: string;
  createdAt: Date;
  updatedAt: Date;
}

export enum JournalEntryStatus {
  DRAFT = 'DRAFT',
  POSTED = 'POSTED',
  CANCELLED = 'CANCELLED',
  REVERSED = 'REVERSED'
}

export interface JournalEntryLine {
  id: string;
  journalEntryId: string;
  accountId: string;
  account?: Account;
  debit?: number;
  credit?: number;
  description?: string;
  costCenterId?: string;
  costCenter?: CostCenter;
  projectId?: string;
  project?: Project;
  createdAt: Date;
}

// واجهات الفواتير
export interface Invoice {
  id: string;
  type: InvoiceType;
  invoiceNumber: string;
  date: Date;
  dueDate: Date;
  customerId?: string;
  customer?: Customer;
  supplierId?: string;
  supplier?: Supplier;
  items: InvoiceItem[];
  subtotal: number;
  discountAmount: number;
  taxAmount: number;
  total: number;
  paidAmount: number;
  balanceDue: number;
  currency: string;
  status: InvoiceStatus;
  notes?: string;
  attachments?: string[];
  journalEntryId?: string;
  journalEntry?: JournalEntry;
  payments?: Payment[];
  tenantId: string;
  createdBy: string;
  createdAt: Date;
  updatedAt: Date;
}

export enum InvoiceType {
  SALES = 'SALES',
  PURCHASE = 'PURCHASE'
}

export enum InvoiceStatus {
  DRAFT = 'DRAFT',
  PENDING = 'PENDING',
  PAID = 'PAID',
  PARTIALLY_PAID = 'PARTIALLY_PAID',
  OVERDUE = 'OVERDUE',
  CANCELLED = 'CANCELLED'
}

export interface InvoiceItem {
  id: string;
  invoiceId: string;
  productId?: string;
  product?: Product;
  description: string;
  quantity: number;
  unitPrice: number;
  discount: number;
  taxRate: number;
  amount: number;
  accountId: string;
  account?: Account;
}

// واجهات المدفوعات
export interface Payment {
  id: string;
  type: PaymentType;
  paymentNumber: string;
  date: Date;
  amount: number;
  currency: string;
  customerId?: string;
  customer?: Customer;
  supplierId?: string;
  supplier?: Supplier;
  invoiceId?: string;
  invoice?: Invoice;
  accountId: string;
  account?: Account;
  paymentMethodId: string;
  paymentMethod?: PaymentMethod;
  reference?: string;
  description?: string;
  status: PaymentStatus;
  attachments?: string[];
  journalEntryId?: string;
  journalEntry?: JournalEntry;
  tenantId: string;
  createdBy: string;
  createdAt: Date;
  updatedAt: Date;
}

export enum PaymentType {
  RECEIPT = 'RECEIPT',
  PAYMENT = 'PAYMENT'
}

export enum PaymentStatus {
  PENDING = 'PENDING',
  COMPLETED = 'COMPLETED',
  FAILED = 'FAILED',
  CANCELLED = 'CANCELLED'
}

// واجهات الموازنة
export interface Budget {
  id: string;
  name: string;
  year: number;
  type: BudgetType;
  startDate: Date;
  endDate: Date;
  items: BudgetItem[];
  totalAmount: number;
  currency: string;
  description?: string;
  status: BudgetStatus;
  approvedBy?: string;
  approvedAt?: Date;
  tenantId: string;
  createdBy: string;
  createdAt: Date;
  updatedAt: Date;
}

export enum BudgetType {
  ANNUAL = 'ANNUAL',
  QUARTERLY = 'QUARTERLY',
  MONTHLY = 'MONTHLY'
}

export enum BudgetStatus {
  DRAFT = 'DRAFT',
  APPROVED = 'APPROVED',
  ACTIVE = 'ACTIVE',
  CLOSED = 'CLOSED'
}

export interface BudgetItem {
  id: string;
  budgetId: string;
  accountId: string;
  account?: Account;
  amount: number;
  period?: string;
  actualAmount?: number;
  variance?: number;
  variancePercentage?: number;
  notes?: string;
}

// واجهات التقارير المالية
export interface FinancialReport {
  id: string;
  type: ReportType;
  name: string;
  startDate: Date;
  endDate: Date;
  data: any;
  format: ReportFormat;
  createdBy: string;
  createdAt: Date;
}

export enum ReportType {
  INCOME_STATEMENT = 'INCOME_STATEMENT',
  BALANCE_SHEET = 'BALANCE_SHEET',
  CASH_FLOW = 'CASH_FLOW',
  TRIAL_BALANCE = 'TRIAL_BALANCE',
  GENERAL_LEDGER = 'GENERAL_LEDGER',
  CHART_OF_ACCOUNTS = 'CHART_OF_ACCOUNTS',
  TAX_REPORT = 'TAX_REPORT',
  AGING_REPORT = 'AGING_REPORT'
}

export enum ReportFormat {
  JSON = 'JSON',
  EXCEL = 'EXCEL',
  PDF = 'PDF'
}

// واجهات التسوية البنكية
export interface BankReconciliation {
  id: string;
  accountId: string;
  account?: Account;
  statementDate: Date;
  statementBalance: number;
  systemBalance: number;
  difference: number;
  transactions: BankTransaction[];
  reconciledBy?: string;
  reconciledAt?: Date;
  status: ReconciliationStatus;
  tenantId: string;
  createdBy: string;
  createdAt: Date;
  updatedAt: Date;
}

export enum ReconciliationStatus {
  DRAFT = 'DRAFT',
  IN_PROGRESS = 'IN_PROGRESS',
  COMPLETED = 'COMPLETED',
  APPROVED = 'APPROVED'
}

export interface BankTransaction {
  id: string;
  reconciliationId: string;
  date: Date;
  description: string;
  amount: number;
  type: TransactionType;
  reference?: string;
  matched: boolean;
  journalEntryId?: string;
  journalEntry?: JournalEntry;
}

export enum TransactionType {
  DEPOSIT = 'DEPOSIT',
  WITHDRAWAL = 'WITHDRAWAL'
}

// واجهات إضافية
export interface CostCenter {
  id: string;
  code: string;
  name: string;
  description?: string;
  parentId?: string;
  parent?: CostCenter;
  children?: CostCenter[];
  isActive: boolean;
  tenantId: string;
  createdAt: Date;
  updatedAt: Date;
}

export interface PaymentMethod {
  id: string;
  name: string;
  type: PaymentMethodType;
  description?: string;
  isActive: boolean;
  accountId?: string;
  account?: Account;
  tenantId: string;
  createdAt: Date;
  updatedAt: Date;
}

export enum PaymentMethodType {
  CASH = 'CASH',
  BANK_TRANSFER = 'BANK_TRANSFER',
  CREDIT_CARD = 'CREDIT_CARD',
  CHEQUE = 'CHEQUE',
  ONLINE = 'ONLINE',
  OTHER = 'OTHER'
}

export interface TaxRate {
  id: string;
  name: string;
  rate: number;
  type: TaxType;
  accountId: string;
  account?: Account;
  isActive: boolean;
  tenantId: string;
  createdAt: Date;
  updatedAt: Date;
}

export enum TaxType {
  SALES = 'SALES',
  PURCHASE = 'PURCHASE',
  VALUE_ADDED = 'VALUE_ADDED'
}

// واجهات مساعدة
export interface Customer {
  id: string;
  name: string;
  email?: string;
  phone?: string;
  address?: string;
  taxNumber?: string;
  accountId?: string;
  account?: Account;
}

export interface Supplier {
  id: string;
  name: string;
  email?: string;
  phone?: string;
  address?: string;
  taxNumber?: string;
  accountId?: string;
  account?: Account;
}

export interface Product {
  id: string;
  name: string;
  code?: string;
  description?: string;
  unitPrice: number;
  salesAccountId?: string;
  purchaseAccountId?: string;
}

export interface Project {
  id: string;
  name: string;
  code?: string;
  description?: string;
  startDate?: Date;
  endDate?: Date;
  status: string;
}

// واجهات البحث والتصفية
export interface AccountingSearchParams {
  query?: string;
  type?: string;
  status?: string;
  startDate?: Date;
  endDate?: Date;
  accountId?: string;
  customerId?: string;
  supplierId?: string;
  minAmount?: number;
  maxAmount?: number;
  page?: number;
  limit?: number;
  sortBy?: string;
  sortOrder?: 'asc' | 'desc';
}

export interface AccountingSearchResult<T> {
  data: T[];
  total: number;
  page: number;
  limit: number;
  pages: number;
}

// واجهات الإحصائيات
export interface AccountingStatistics {
  totalRevenue: number;
  totalExpenses: number;
  netIncome: number;
  totalAssets: number;
  totalLiabilities: number;
  equity: number;
  cashBalance: number;
  accountsReceivable: number;
  accountsPayable: number;
  period: {
    startDate: Date;
    endDate: Date;
  };
}

export interface CashFlowSummary {
  operatingActivities: number;
  investingActivities: number;
  financingActivities: number;
  netCashFlow: number;
  beginningBalance: number;
  endingBalance: number;
}

export interface BudgetVarianceAnalysis {
  budgetId: string;
  budget: Budget;
  totalBudgeted: number;
  totalActual: number;
  totalVariance: number;
  variancePercentage: number;
  items: BudgetVarianceItem[];
}

export interface BudgetVarianceItem {
  accountId: string;
  account: Account;
  budgeted: number;
  actual: number;
  variance: number;
  variancePercentage: number;
}

// واجهات DTOs
export interface CreateAccountDTO {
  accountNumber: string;
  name: string;
  type: AccountType;
  subType?: string;
  parentId?: string;
  description?: string;
  isActive?: boolean;
  tags?: string[];
}

export interface CreateJournalEntryDTO {
  date: Date;
  description: string;
  reference?: string;
  entries: CreateJournalEntryLineDTO[];
  attachments?: string[];
}

export interface CreateJournalEntryLineDTO {
  accountId: string;
  debit?: number;
  credit?: number;
  description?: string;
  costCenterId?: string;
  projectId?: string;
}

export interface CreateInvoiceDTO {
  type: InvoiceType;
  invoiceNumber?: string;
  date: Date;
  dueDate: Date;
  customerId?: string;
  supplierId?: string;
  items: CreateInvoiceItemDTO[];
  subtotal: number;
  discountAmount?: number;
  taxAmount?: number;
  total: number;
  notes?: string;
  status?: InvoiceStatus;
}

export interface CreateInvoiceItemDTO {
  productId?: string;
  description: string;
  quantity: number;
  unitPrice: number;
  discount?: number;
  taxRate?: number;
  accountId: string;
}

export interface CreatePaymentDTO {
  type: PaymentType;
  paymentNumber?: string;
  date: Date;
  amount: number;
  customerId?: string;
  supplierId?: string;
  invoiceId?: string;
  accountId: string;
  paymentMethodId: string;
  reference?: string;
  description?: string;
  attachments?: string[];
}

export interface CreateBudgetDTO {
  name: string;
  year: number;
  type: BudgetType;
  startDate: Date;
  endDate: Date;
  items: CreateBudgetItemDTO[];
  totalAmount: number;
  description?: string;
  status?: BudgetStatus;
}

export interface CreateBudgetItemDTO {
  accountId: string;
  amount: number;
  period?: string;
  notes?: string;
}