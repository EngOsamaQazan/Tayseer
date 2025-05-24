import { Router } from 'express';
import { AccountingController } from './accounting.controller';
import { accountingValidation } from './accounting.validation';
import { validateRequest } from '../../middleware/validation';
import { authenticate, authorize } from '../../middleware/auth';
import { upload } from '../../middleware/upload';

const router = Router();
const controller = new AccountingController();

// تطبيق المصادقة على جميع المسارات
router.use(authenticate);

// مسارات الحسابات
router.post(
  '/accounts',
  authorize(['accounting:create']),
  validateRequest(accountingValidation.createAccount),
  controller.createAccount
);

router.get(
  '/accounts',
  authorize(['accounting:read']),
  controller.getAccounts
);

router.get(
  '/accounts/:id',
  authorize(['accounting:read']),
  controller.getAccountById
);

router.put(
  '/accounts/:id',
  authorize(['accounting:update']),
  validateRequest(accountingValidation.updateAccount),
  controller.updateAccount
);

router.delete(
  '/accounts/:id',
  authorize(['accounting:delete']),
  controller.deleteAccount
);

router.get(
  '/accounts/:id/balance',
  authorize(['accounting:read']),
  controller.getAccountBalance
);

router.get(
  '/accounts/:id/statement',
  authorize(['accounting:read']),
  controller.getAccountStatement
);

// مسارات القيود اليومية
router.post(
  '/journal-entries',
  authorize(['accounting:create']),
  validateRequest(accountingValidation.createJournalEntry),
  controller.createJournalEntry
);

router.get(
  '/journal-entries',
  authorize(['accounting:read']),
  controller.getJournalEntries
);

router.get(
  '/journal-entries/search',
  authorize(['accounting:read']),
  validateRequest(accountingValidation.searchJournalEntries),
  controller.searchJournalEntries
);

router.get(
  '/journal-entries/:id',
  authorize(['accounting:read']),
  controller.getJournalEntryById
);

router.put(
  '/journal-entries/:id',
  authorize(['accounting:update']),
  validateRequest(accountingValidation.updateJournalEntry),
  controller.updateJournalEntry
);

router.post(
  '/journal-entries/:id/post',
  authorize(['accounting:approve']),
  controller.postJournalEntry
);

router.post(
  '/journal-entries/:id/reverse',
  authorize(['accounting:approve']),
  controller.reverseJournalEntry
);

// مسارات الفواتير
router.post(
  '/invoices',
  authorize(['accounting:create']),
  validateRequest(accountingValidation.createInvoice),
  controller.createInvoice
);

router.get(
  '/invoices',
  authorize(['accounting:read']),
  controller.getInvoices
);

router.get(
  '/invoices/:id',
  authorize(['accounting:read']),
  controller.getInvoiceById
);

router.put(
  '/invoices/:id',
  authorize(['accounting:update']),
  validateRequest(accountingValidation.updateInvoice),
  controller.updateInvoice
);

router.delete(
  '/invoices/:id',
  authorize(['accounting:delete']),
  controller.deleteInvoice
);

// مسارات المدفوعات
router.post(
  '/payments',
  authorize(['accounting:create']),
  validateRequest(accountingValidation.createPayment),
  controller.createPayment
);

router.get(
  '/payments',
  authorize(['accounting:read']),
  controller.getPayments
);

router.get(
  '/payments/:id',
  authorize(['accounting:read']),
  controller.getPaymentById
);

// مسارات التقارير المالية
router.post(
  '/reports/income-statement',
  authorize(['accounting:reports']),
  validateRequest(accountingValidation.dateRangeReport),
  controller.generateIncomeStatement
);

router.post(
  '/reports/balance-sheet',
  authorize(['accounting:reports']),
  validateRequest(accountingValidation.dateRangeReport),
  controller.generateBalanceSheet
);

router.post(
  '/reports/cash-flow',
  authorize(['accounting:reports']),
  validateRequest(accountingValidation.dateRangeReport),
  controller.generateCashFlowStatement
);

router.post(
  '/reports/trial-balance',
  authorize(['accounting:reports']),
  validateRequest(accountingValidation.dateRangeReport),
  controller.generateTrialBalance
);

router.post(
  '/reports/tax',
  authorize(['accounting:reports']),
  validateRequest(accountingValidation.dateRangeReport),
  controller.generateTaxReport
);

router.post(
  '/reports/aging',
  authorize(['accounting:reports']),
  validateRequest(accountingValidation.agingReport),
  controller.generateAgingReport
);

// مسارات الموازنة
router.post(
  '/budgets',
  authorize(['accounting:create']),
  validateRequest(accountingValidation.createBudget),
  controller.createBudget
);

router.get(
  '/budgets',
  authorize(['accounting:read']),
  controller.getBudgets
);

router.get(
  '/budgets/:id',
  authorize(['accounting:read']),
  controller.getBudgetById
);

router.put(
  '/budgets/:id',
  authorize(['accounting:update']),
  controller.updateBudget
);

router.delete(
  '/budgets/:id',
  authorize(['accounting:delete']),
  controller.deleteBudget
);

router.post(
  '/budgets/:id/variances',
  authorize(['accounting:reports']),
  controller.analyzeBudgetVariances
);

// مسارات التصدير والاستيراد
router.post(
  '/export',
  authorize(['accounting:export']),
  validateRequest(accountingValidation.exportData),
  controller.exportAccountingData
);

router.post(
  '/import/chart-of-accounts',
  authorize(['accounting:import']),
  upload.single('file'),
  validateRequest(accountingValidation.importChartOfAccounts),
  controller.importChartOfAccounts
);

// مسارات التسوية البنكية
router.post(
  '/bank-reconciliation',
  authorize(['accounting:create']),
  validateRequest(accountingValidation.bankReconciliation),
  controller.createBankReconciliation
);

router.get(
  '/bank-reconciliation',
  authorize(['accounting:read']),
  controller.getBankReconciliations
);

router.get(
  '/bank-reconciliation/:id',
  authorize(['accounting:read']),
  controller.getBankReconciliationById
);

router.put(
  '/bank-reconciliation/:id',
  authorize(['accounting:update']),
  controller.updateBankReconciliation
);

// مسارات إضافية
router.get(
  '/chart-of-accounts',
  authorize(['accounting:read']),
  controller.getChartOfAccounts
);

router.get(
  '/general-ledger',
  authorize(['accounting:read']),
  controller.getGeneralLedger
);

router.get(
  '/cost-centers',
  authorize(['accounting:read']),
  controller.getCostCenters
);

router.post(
  '/cost-centers',
  authorize(['accounting:create']),
  controller.createCostCenter
);

router.get(
  '/payment-methods',
  authorize(['accounting:read']),
  controller.getPaymentMethods
);

router.post(
  '/payment-methods',
  authorize(['accounting:create']),
  controller.createPaymentMethod
);

export default router;