// Common types for the Tayseer Platform

// User and Authentication Types
export interface User {
  id: number;
  username: string;
  email: string;
  role: UserRole;
  permissions: string[];
  createdAt: Date;
  updatedAt: Date;
}

export enum UserRole {
  ADMIN = 'admin',
  MANAGER = 'manager',
  EMPLOYEE = 'employee',
  CUSTOMER = 'customer',
}

export interface AuthState {
  user: User | null;
  token: string | null;
  isAuthenticated: boolean;
  loading: boolean;
  error: string | null;
}

// Customer Types
export interface Customer {
  id: number;
  name: string;
  phone: string;
  email: string;
  nationalId: string;
  address: string;
  registrationDate: string;
  status: CustomerStatus;
  creditLimit: number;
  totalPurchases: number;
  totalPayments: number;
  balance: number;
}

export enum CustomerStatus {
  ACTIVE = 'active',
  INACTIVE = 'inactive',
  BLOCKED = 'blocked',
}

// Product Types
export interface Product {
  id: number;
  name: string;
  description: string;
  category: string;
  price: number;
  stock: number;
  minStock: number;
  unit: string;
  sku: string;
  status: ProductStatus;
  createdAt: string;
  updatedAt: string;
}

export enum ProductStatus {
  AVAILABLE = 'available',
  OUT_OF_STOCK = 'out_of_stock',
  DISCONTINUED = 'discontinued',
}

// Contract Types
export interface Contract {
  id: number;
  contractNumber: string;
  customerId: number;
  customerName: string;
  productId: number;
  productName: string;
  totalAmount: number;
  downPayment: number;
  monthlyInstallment: number;
  installmentCount: number;
  remainingAmount: number;
  startDate: string;
  endDate: string;
  status: ContractStatus;
  nextPaymentDate: string;
  paidInstallments: number;
}

export enum ContractStatus {
  ACTIVE = 'active',
  COMPLETED = 'completed',
  CANCELLED = 'cancelled',
  OVERDUE = 'overdue',
}

// Transaction Types
export interface Transaction {
  id: number;
  transactionNumber: string;
  contractId: number;
  customerId: number;
  customerName: string;
  type: TransactionType;
  amount: number;
  date: string;
  paymentMethod: PaymentMethod;
  reference: string;
  notes: string;
  status: TransactionStatus;
  createdBy: string;
}

export enum TransactionType {
  PAYMENT = 'payment',
  REFUND = 'refund',
  DOWN_PAYMENT = 'down_payment',
  PENALTY = 'penalty',
}

export enum PaymentMethod {
  CASH = 'cash',
  CARD = 'card',
  BANK_TRANSFER = 'bank_transfer',
  CHECK = 'check',
}

export enum TransactionStatus {
  COMPLETED = 'completed',
  PENDING = 'pending',
  CANCELLED = 'cancelled',
}

// Employee Types
export interface Employee {
  id: number;
  name: string;
  email: string;
  phone: string;
  department: string;
  position: string;
  hireDate: string;
  salary: number;
  status: EmployeeStatus;
  nationalId: string;
  address: string;
}

export enum EmployeeStatus {
  ACTIVE = 'active',
  INACTIVE = 'inactive',
  ON_LEAVE = 'on_leave',
  TERMINATED = 'terminated',
}

// Task Types
export interface Task {
  id: number;
  title: string;
  description: string;
  assignedTo: string;
  assignedToId: number;
  createdBy: string;
  priority: TaskPriority;
  status: TaskStatus;
  dueDate: string;
  createdAt: string;
  updatedAt: string;
  tags: string[];
}

export enum TaskPriority {
  LOW = 'low',
  MEDIUM = 'medium',
  HIGH = 'high',
  URGENT = 'urgent',
}

export enum TaskStatus {
  TODO = 'todo',
  IN_PROGRESS = 'in_progress',
  COMPLETED = 'completed',
  CANCELLED = 'cancelled',
}

// Legal Case Types
export interface LegalCase {
  id: number;
  caseNumber: string;
  customerId: number;
  customerName: string;
  contractId: number;
  type: LegalCaseType;
  status: LegalCaseStatus;
  priority: LegalCasePriority;
  description: string;
  filingDate: string;
  courtDate: string;
  lawyer: string;
  amount: number;
  notes: string;
}

export enum LegalCaseType {
  COLLECTION = 'collection',
  CONTRACT_BREACH = 'contract_breach',
  FRAUD = 'fraud',
  OTHER = 'other',
}

export enum LegalCaseStatus {
  OPEN = 'open',
  IN_PROGRESS = 'in_progress',
  RESOLVED = 'resolved',
  CLOSED = 'closed',
}

export enum LegalCasePriority {
  LOW = 'low',
  MEDIUM = 'medium',
  HIGH = 'high',
  CRITICAL = 'critical',
}

// Support Ticket Types
export interface Ticket {
  id: number;
  ticketNumber: string;
  customerId: number;
  customerName: string;
  subject: string;
  description: string;
  category: TicketCategory;
  priority: TicketPriority;
  status: TicketStatus;
  assignedTo: string;
  createdAt: string;
  updatedAt: string;
  resolvedAt: string | null;
  satisfaction: number | null;
}

export enum TicketCategory {
  TECHNICAL = 'technical',
  BILLING = 'billing',
  CONTRACT = 'contract',
  COMPLAINT = 'complaint',
  INQUIRY = 'inquiry',
  OTHER = 'other',
}

export enum TicketPriority {
  LOW = 'low',
  MEDIUM = 'medium',
  HIGH = 'high',
  URGENT = 'urgent',
}

export enum TicketStatus {
  NEW = 'new',
  OPEN = 'open',
  IN_PROGRESS = 'in_progress',
  RESOLVED = 'resolved',
  CLOSED = 'closed',
}

// Investor Types
export interface Investor {
  id: number;
  name: string;
  email: string;
  phone: string;
  type: InvestorType;
  investmentAmount: number;
  investmentDate: string;
  returnRate: number;
  status: InvestorStatus;
  portfolio: Investment[];
}

export enum InvestorType {
  INDIVIDUAL = 'individual',
  CORPORATE = 'corporate',
  INSTITUTIONAL = 'institutional',
}

export enum InvestorStatus {
  ACTIVE = 'active',
  INACTIVE = 'inactive',
  PENDING = 'pending',
}

export interface Investment {
  id: number;
  investorId: number;
  amount: number;
  date: string;
  type: InvestmentType;
  returnRate: number;
  maturityDate: string;
  status: InvestmentStatus;
  returns: number;
}

export enum InvestmentType {
  FIXED_DEPOSIT = 'fixed_deposit',
  EQUITY = 'equity',
  BONDS = 'bonds',
  MIXED = 'mixed',
}

export enum InvestmentStatus {
  ACTIVE = 'active',
  MATURED = 'matured',
  WITHDRAWN = 'withdrawn',
}

// Report Types
export interface Report {
  id: number;
  name: string;
  type: ReportType;
  period: ReportPeriod;
  status: ReportStatus;
  generatedDate: string;
  generatedBy: string;
  fileUrl?: string;
}

export enum ReportType {
  SALES = 'sales',
  COLLECTIONS = 'collections',
  INVENTORY = 'inventory',
  CUSTOMERS = 'customers',
  FINANCIAL = 'financial',
}

export enum ReportPeriod {
  DAILY = 'daily',
  WEEKLY = 'weekly',
  MONTHLY = 'monthly',
  QUARTERLY = 'quarterly',
  YEARLY = 'yearly',
}

export enum ReportStatus {
  PENDING = 'pending',
  GENERATING = 'generating',
  COMPLETED = 'completed',
  FAILED = 'failed',
}

// Dashboard Types
export interface DashboardMetrics {
  totalSales: number;
  totalCollections: number;
  activeContracts: number;
  overdueContracts: number;
  totalCustomers: number;
  activeCustomers: number;
  inventoryValue: number;
  lowStockItems: number;
  openTickets: number;
  resolvedTickets: number;
  employeeCount: number;
  totalInvestments: number;
}

// Common API Response Types
export interface ApiResponse<T> {
  success: boolean;
  data: T;
  message?: string;
  error?: string;
}

export interface PaginatedResponse<T> {
  items: T[];
  total: number;
  page: number;
  pageSize: number;
  totalPages: number;
}

// Form Types
export interface FormField {
  name: string;
  label: string;
  type: 'text' | 'number' | 'email' | 'password' | 'select' | 'textarea' | 'date' | 'checkbox';
  required?: boolean;
  placeholder?: string;
  options?: SelectOption[];
  validation?: ValidationRule[];
}

export interface SelectOption {
  value: string | number;
  label: string;
}

export interface ValidationRule {
  type: 'required' | 'min' | 'max' | 'email' | 'pattern';
  value?: any;
  message: string;
}

// Error Types
export interface AppError {
  code: string;
  message: string;
  details?: any;
  timestamp: Date;
}

// Notification Types
export interface Notification {
  id: string;
  type: 'success' | 'error' | 'warning' | 'info';
  title: string;
  message: string;
  duration?: number;
  action?: {
    label: string;
    handler: () => void;
  };
}