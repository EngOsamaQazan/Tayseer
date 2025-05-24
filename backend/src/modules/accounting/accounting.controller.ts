import { Request, Response } from 'express';
import accountingService from './accounting.service';
import { accountingValidation } from './accounting.validation';
import { ApiError } from '../../shared/utils/api-error';
import { ApiResponse } from '../../shared/utils/api-response';
import { logger } from '../../shared/utils/logger';
import { auditLogUtil } from '../../shared/utils/audit-log';
import { notificationUtil } from '../../shared/utils/notification';
import { cacheUtil } from '../../shared/utils/cache';
import { uploadUtil } from '../../shared/utils/upload';

export class AccountingController {
  // إنشاء حساب محاسبي
  async createAccount(req: Request, res: Response): Promise<void> {
    try {
      const tenantId = req.user?.tenantId;
      const validatedData = accountingValidation.createAccount.parse(req.body);

      const account = await accountingService.createAccount(validatedData, tenantId!);

      // تسجيل النشاط
      await auditLogUtil.log({
        module: 'accounting',
        action: 'CREATE_ACCOUNT',
        entityId: account.id,
        entityType: 'account',
        userId: req.user?.id!,
        tenantId: tenantId!,
        details: {
          accountNumber: account.accountNumber,
          accountName: account.name,
        },
      });

      res.status(201).json(
        ApiResponse.success(account, 'تم إنشاء الحساب بنجاح')
      );
    } catch (error) {
      logger.error('خطأ في إنشاء الحساب', error);
      if (error instanceof ApiError) {
        res.status(error.statusCode).json(ApiResponse.error(error.message));
      } else {
        res.status(500).json(ApiResponse.error('خطأ في إنشاء الحساب'));
      }
    }
  }

  // إنشاء قيد يومية
  async createJournalEntry(req: Request, res: Response): Promise<void> {
    try {
      const tenantId = req.user?.tenantId;
      const validatedData = accountingValidation.createJournalEntry.parse(req.body);

      const entry = await accountingService.createJournalEntry(validatedData, tenantId!);

      // تسجيل النشاط
      await auditLogUtil.log({
        module: 'accounting',
        action: 'CREATE_JOURNAL_ENTRY',
        entityId: entry.id,
        entityType: 'journal_entry',
        userId: req.user?.id!,
        tenantId: tenantId!,
        details: {
          entryNumber: entry.entryNumber,
          totalAmount: entry.totalAmount,
        },
      });

      res.status(201).json(
        ApiResponse.success(entry, 'تم إنشاء القيد بنجاح')
      );
    } catch (error) {
      logger.error('خطأ في إنشاء القيد', error);
      if (error instanceof ApiError) {
        res.status(error.statusCode).json(ApiResponse.error(error.message));
      } else {
        res.status(500).json(ApiResponse.error('خطأ في إنشاء القيد'));
      }
    }
  }

  // ترحيل قيد يومية
  async postJournalEntry(req: Request, res: Response): Promise<void> {
    try {
      const tenantId = req.user?.tenantId;
      const { entryId } = req.params;

      const entry = await accountingService.postJournalEntry(entryId, tenantId!);

      // تسجيل النشاط
      await auditLogUtil.log({
        module: 'accounting',
        action: 'POST_JOURNAL_ENTRY',
        entityId: entry.id,
        entityType: 'journal_entry',
        userId: req.user?.id!,
        tenantId: tenantId!,
        details: {
          entryNumber: entry.entryNumber,
        },
      });

      res.json(
        ApiResponse.success(entry, 'تم ترحيل القيد بنجاح')
      );
    } catch (error) {
      logger.error('خطأ في ترحيل القيد', error);
      if (error instanceof ApiError) {
        res.status(error.statusCode).json(ApiResponse.error(error.message));
      } else {
        res.status(500).json(ApiResponse.error('خطأ في ترحيل القيد'));
      }
    }
  }

  // إنشاء فاتورة
  async createInvoice(req: Request, res: Response): Promise<void> {
    try {
      const tenantId = req.user?.tenantId;
      const validatedData = accountingValidation.createInvoice.parse(req.body);

      const invoice = await accountingService.createInvoice(validatedData, tenantId!);

      // تسجيل النشاط
      await auditLogUtil.log({
        module: 'accounting',
        action: 'CREATE_INVOICE',
        entityId: invoice.id,
        entityType: 'invoice',
        userId: req.user?.id!,
        tenantId: tenantId!,
        details: {
          invoiceNumber: invoice.invoiceNumber,
          totalAmount: invoice.totalAmount,
          customerId: invoice.customerId,
        },
      });

      res.status(201).json(
        ApiResponse.success(invoice, 'تم إنشاء الفاتورة بنجاح')
      );
    } catch (error) {
      logger.error('خطأ في إنشاء الفاتورة', error);
      if (error instanceof ApiError) {
        res.status(error.statusCode).json(ApiResponse.error(error.message));
      } else {
        res.status(500).json(ApiResponse.error('خطأ في إنشاء الفاتورة'));
      }
    }
  }

  // تسجيل دفعة
  async createPayment(req: Request, res: Response): Promise<void> {
    try {
      const tenantId = req.user?.tenantId;
      const validatedData = accountingValidation.createPayment.parse(req.body);

      const payment = await accountingService.createPayment(validatedData, tenantId!);

      // تسجيل النشاط
      await auditLogUtil.log({
        module: 'accounting',
        action: 'CREATE_PAYMENT',
        entityId: payment.id,
        entityType: 'payment',
        userId: req.user?.id!,
        tenantId: tenantId!,
        details: {
          paymentNumber: payment.paymentNumber,
          amount: payment.amount,
          invoiceId: payment.invoiceId,
        },
      });

      res.status(201).json(
        ApiResponse.success(payment, 'تم تسجيل الدفعة بنجاح')
      );
    } catch (error) {
      logger.error('خطأ في تسجيل الدفعة', error);
      if (error instanceof ApiError) {
        res.status(error.statusCode).json(ApiResponse.error(error.message));
      } else {
        res.status(500).json(ApiResponse.error('خطأ في تسجيل الدفعة'));
      }
    }
  }

  // إنشاء تقرير مالي
  async generateFinancialReport(req: Request, res: Response): Promise<void> {
    try {
      const tenantId = req.user?.tenantId;
      const validatedData = accountingValidation.generateReport.parse(req.body);

      const report = await accountingService.generateFinancialReport(
        validatedData.reportType,
        validatedData.startDate,
        validatedData.endDate,
        tenantId!
      );

      // تسجيل النشاط
      await auditLogUtil.log({
        module: 'accounting',
        action: 'GENERATE_REPORT',
        entityType: 'report',
        userId: req.user?.id!,
        tenantId: tenantId!,
        details: {
          reportType: validatedData.reportType,
          dateRange: {
            start: validatedData.startDate,
            end: validatedData.endDate,
          },
        },
      });

      res.json(
        ApiResponse.success(report, 'تم إنشاء التقرير بنجاح')
      );
    } catch (error) {
      logger.error('خطأ في إنشاء التقرير', error);
      if (error instanceof ApiError) {
        res.status(error.statusCode).json(ApiResponse.error(error.message));
      } else {
        res.status(500).json(ApiResponse.error('خطأ في إنشاء التقرير'));
      }
    }
  }

  // إنشاء قائمة الدخل
  async generateIncomeStatement(req: Request, res: Response): Promise<void> {
    try {
      const tenantId = req.user?.tenantId;
      const validatedData = accountingValidation.dateRangeReport.parse(req.query);

      const cacheKey = `income-statement:${tenantId}:${validatedData.startDate}:${validatedData.endDate}`;
      let report = await cacheUtil.get(cacheKey);

      if (!report) {
        report = await accountingService.generateIncomeStatement(
          new Date(validatedData.startDate),
          new Date(validatedData.endDate),
          tenantId!
        );
        await cacheUtil.set(cacheKey, report, 3600); // Cache for 1 hour
      }

      res.json(
        ApiResponse.success(report, 'تم إنشاء قائمة الدخل بنجاح')
      );
    } catch (error) {
      logger.error('خطأ في إنشاء قائمة الدخل', error);
      if (error instanceof ApiError) {
        res.status(error.statusCode).json(ApiResponse.error(error.message));
      } else {
        res.status(500).json(ApiResponse.error('خطأ في إنشاء قائمة الدخل'));
      }
    }
  }

  // إنشاء الميزانية العمومية
  async generateBalanceSheet(req: Request, res: Response): Promise<void> {
    try {
      const tenantId = req.user?.tenantId;
      const { date } = req.query;
      const validatedDate = accountingValidation.dateReport.parse({ date });

      const cacheKey = `balance-sheet:${tenantId}:${validatedDate.date}`;
      let report = await cacheUtil.get(cacheKey);

      if (!report) {
        report = await accountingService.generateBalanceSheet(
          new Date(validatedDate.date),
          tenantId!
        );
        await cacheUtil.set(cacheKey, report, 3600); // Cache for 1 hour
      }

      res.json(
        ApiResponse.success(report, 'تم إنشاء الميزانية العمومية بنجاح')
      );
    } catch (error) {
      logger.error('خطأ في إنشاء الميزانية العمومية', error);
      if (error instanceof ApiError) {
        res.status(error.statusCode).json(ApiResponse.error(error.message));
      } else {
        res.status(500).json(ApiResponse.error('خطأ في إنشاء الميزانية العمومية'));
      }
    }
  }

  // إنشاء بيان التدفقات النقدية
  async generateCashFlowStatement(req: Request, res: Response): Promise<void> {
    try {
      const tenantId = req.user?.tenantId;
      const validatedData = accountingValidation.dateRangeReport.parse(req.query);

      const report = await accountingService.generateCashFlowStatement(
        new Date(validatedData.startDate),
        new Date(validatedData.endDate),
        tenantId!
      );

      res.json(
        ApiResponse.success(report, 'تم إنشاء بيان التدفقات النقدية بنجاح')
      );
    } catch (error) {
      logger.error('خطأ في إنشاء بيان التدفقات النقدية', error);
      if (error instanceof ApiError) {
        res.status(error.statusCode).json(ApiResponse.error(error.message));
      } else {
        res.status(500).json(ApiResponse.error('خطأ في إنشاء بيان التدفقات النقدية'));
      }
    }
  }

  // إنشاء ميزان المراجعة
  async generateTrialBalance(req: Request, res: Response): Promise<void> {
    try {
      const tenantId = req.user?.tenantId;
      const { date } = req.query;
      const validatedDate = accountingValidation.dateReport.parse({ date });

      const report = await accountingService.generateTrialBalance(
        new Date(validatedDate.date),
        tenantId!
      );

      res.json(
        ApiResponse.success(report, 'تم إنشاء ميزان المراجعة بنجاح')
      );
    } catch (error) {
      logger.error('خطأ في إنشاء ميزان المراجعة', error);
      if (error instanceof ApiError) {
        res.status(error.statusCode).json(ApiResponse.error(error.message));
      } else {
        res.status(500).json(ApiResponse.error('خطأ في إنشاء ميزان المراجعة'));
      }
    }
  }

  // إنشاء موازنة
  async createBudget(req: Request, res: Response): Promise<void> {
    try {
      const tenantId = req.user?.tenantId;
      const validatedData = accountingValidation.createBudget.parse(req.body);

      const budget = await accountingService.createBudget(validatedData, tenantId!);

      // تسجيل النشاط
      await auditLogUtil.log({
        module: 'accounting',
        action: 'CREATE_BUDGET',
        entityId: budget.id,
        entityType: 'budget',
        userId: req.user?.id!,
        tenantId: tenantId!,
        details: {
          budgetName: budget.name,
          year: budget.year,
          totalAmount: budget.totalAmount,
        },
      });

      res.status(201).json(
        ApiResponse.success(budget, 'تم إنشاء الموازنة بنجاح')
      );
    } catch (error) {
      logger.error('خطأ في إنشاء الموازنة', error);
      if (error instanceof ApiError) {
        res.status(error.statusCode).json(ApiResponse.error(error.message));
      } else {
        res.status(500).json(ApiResponse.error('خطأ في إنشاء الموازنة'));
      }
    }
  }

  // تحليل انحرافات الموازنة
  async analyzeBudgetVariances(req: Request, res: Response): Promise<void> {
    try {
      const tenantId = req.user?.tenantId;
      const { budgetId } = req.params;
      const validatedData = accountingValidation.dateRangeReport.parse(req.query);

      const analysis = await accountingService.analyzeBudgetVariances(
        budgetId,
        new Date(validatedData.startDate),
        new Date(validatedData.endDate),
        tenantId!
      );

      res.json(
        ApiResponse.success(analysis, 'تم تحليل انحرافات الموازنة بنجاح')
      );
    } catch (error) {
      logger.error('خطأ في تحليل انحرافات الموازنة', error);
      if (error instanceof ApiError) {
        res.status(error.statusCode).json(ApiResponse.error(error.message));
      } else {
        res.status(500).json(ApiResponse.error('خطأ في تحليل انحرافات الموازنة'));
      }
    }
  }

  // إنشاء تقرير الضرائب
  async generateTaxReport(req: Request, res: Response): Promise<void> {
    try {
      const tenantId = req.user?.tenantId;
      const validatedData = accountingValidation.dateRangeReport.parse(req.query);

      const report = await accountingService.generateTaxReport(
        new Date(validatedData.startDate),
        new Date(validatedData.endDate),
        tenantId!
      );

      res.json(
        ApiResponse.success(report, 'تم إنشاء تقرير الضرائب بنجاح')
      );
    } catch (error) {
      logger.error('خطأ في إنشاء تقرير الضرائب', error);
      if (error instanceof ApiError) {
        res.status(error.statusCode).json(ApiResponse.error(error.message));
      } else {
        res.status(500).json(ApiResponse.error('خطأ في إنشاء تقرير الضرائب'));
      }
    }
  }

  // تصدير البيانات المحاسبية
  async exportAccountingData(req: Request, res: Response): Promise<void> {
    try {
      const tenantId = req.user?.tenantId;
      const validatedData = accountingValidation.exportData.parse(req.query);

      const buffer = await accountingService.exportAccountingData(
        validatedData.type,
        validatedData.format,
        validatedData.filters || {},
        tenantId!
      );

      // تسجيل النشاط
      await auditLogUtil.log({
        module: 'accounting',
        action: 'EXPORT_DATA',
        entityType: 'export',
        userId: req.user?.id!,
        tenantId: tenantId!,
        details: {
          exportType: validatedData.type,
          format: validatedData.format,
        },
      });

      // إعداد رؤوس الاستجابة
      const filename = `accounting_${validatedData.type}_${Date.now()}.${validatedData.format.toLowerCase()}`;
      res.setHeader('Content-Type', validatedData.format === 'EXCEL' 
        ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        : 'application/pdf');
      res.setHeader('Content-Disposition', `attachment; filename="${filename}"`);
      res.setHeader('Content-Length', buffer.length);

      res.send(buffer);
    } catch (error) {
      logger.error('خطأ في تصدير البيانات المحاسبية', error);
      if (error instanceof ApiError) {
        res.status(error.statusCode).json(ApiResponse.error(error.message));
      } else {
        res.status(500).json(ApiResponse.error('خطأ في تصدير البيانات'));
      }
    }
  }

  // استيراد دليل الحسابات
  async importChartOfAccounts(req: Request, res: Response): Promise<void> {
    try {
      const tenantId = req.user?.tenantId;
      const file = req.file;

      if (!file) {
        throw new ApiError(400, 'الملف مطلوب');
      }

      // معالجة الملف
      const accounts = await uploadUtil.parseExcelFile(file.path, {
        headers: ['accountNumber', 'name', 'type', 'subType', 'parentId'],
      });

      // إنشاء الحسابات
      const results = [];
      for (const account of accounts) {
        try {
          const created = await accountingService.createAccount(account, tenantId!);
          results.push({ success: true, account: created });
        } catch (error: any) {
          results.push({ success: false, error: error.message, data: account });
        }
      }

      // تسجيل النشاط
      await auditLogUtil.log({
        module: 'accounting',
        action: 'IMPORT_CHART_OF_ACCOUNTS',
        entityType: 'import',
        userId: req.user?.id!,
        tenantId: tenantId!,
        details: {
          totalRecords: accounts.length,
          successCount: results.filter(r => r.success).length,
          failureCount: results.filter(r => !r.success).length,
        },
      });

      res.json(
        ApiResponse.success({
          total: accounts.length,
          success: results.filter(r => r.success).length,
          failed: results.filter(r => !r.success).length,
          results,
        }, 'تم استيراد دليل الحسابات')
      );
    } catch (error) {
      logger.error('خطأ في استيراد دليل الحسابات', error);
      if (error instanceof ApiError) {
        res.status(error.statusCode).json(ApiResponse.error(error.message));
      } else {
        res.status(500).json(ApiResponse.error('خطأ في استيراد دليل الحسابات'));
      }
    }
  }

  // البحث في القيود المحاسبية
  async searchJournalEntries(req: Request, res: Response): Promise<void> {
    try {
      const tenantId = req.user?.tenantId;
      const validatedParams = accountingValidation.searchJournalEntries.parse(req.query);

      const results = await accountingService.searchJournalEntries(
        validatedParams,
        tenantId!
      );

      res.json(
        ApiResponse.success(results, 'تم البحث بنجاح')
      );
    } catch (error) {
      logger.error('خطأ في البحث عن القيود', error);
      if (error instanceof ApiError) {
        res.status(error.statusCode).json(ApiResponse.error(error.message));
      } else {
        res.status(500).json(ApiResponse.error('خطأ في البحث'));
      }
    }
  }

  // الحصول على رصيد حساب
  async getAccountBalance(req: Request, res: Response): Promise<void> {
    try {
      const tenantId = req.user?.tenantId;
      const { accountId } = req.params;
      const { date } = req.query;

      const balance = await accountingService.getAccountBalance(
        accountId,
        date ? new Date(date as string) : new Date(),
        tenantId!
      );

      res.json(
        ApiResponse.success(balance, 'تم الحصول على رصيد الحساب')
      );
    } catch (error) {
      logger.error('خطأ في الحصول على رصيد الحساب', error);
      if (error instanceof ApiError) {
        res.status(error.statusCode).json(ApiResponse.error(error.message));
      } else {
        res.status(500).json(ApiResponse.error('خطأ في الحصول على رصيد الحساب'));
      }
    }
  }

  // الحصول على كشف حساب
  async getAccountStatement(req: Request, res: Response): Promise<void> {
    try {
      const tenantId = req.user?.tenantId;
      const { accountId } = req.params;
      const validatedData = accountingValidation.dateRangeReport.parse(req.query);

      const statement = await accountingService.getAccountStatement(
        accountId,
        new Date(validatedData.startDate),
        new Date(validatedData.endDate),
        tenantId!
      );

      res.json(
        ApiResponse.success(statement, 'تم الحصول على كشف الحساب')
      );
    } catch (error) {
      logger.error('خطأ في الحصول على كشف الحساب', error);
      if (error instanceof ApiError) {
        res.status(error.statusCode).json(ApiResponse.error(error.message));
      } else {
        res.status(500).json(ApiResponse.error('خطأ في الحصول على كشف الحساب'));
      }
    }
  }

  // إلغاء قيد يومية
  async reverseJournalEntry(req: Request, res: Response): Promise<void> {
    try {
      const tenantId = req.user?.tenantId;
      const { entryId } = req.params;
      const { reason } = req.body;

      const reversalEntry = await accountingService.reverseJournalEntry(
        entryId,
        reason,
        tenantId!
      );

      // تسجيل النشاط
      await auditLogUtil.log({
        module: 'accounting',
        action: 'REVERSE_JOURNAL_ENTRY',
        entityId: reversalEntry.id,
        entityType: 'journal_entry',
        userId: req.user?.id!,
        tenantId: tenantId!,
        details: {
          originalEntryId: entryId,
          reversalEntryNumber: reversalEntry.entryNumber,
          reason,
        },
      });

      res.json(
        ApiResponse.success(reversalEntry, 'تم إلغاء القيد بنجاح')
      );
    } catch (error) {
      logger.error('خطأ في إلغاء القيد', error);
      if (error instanceof ApiError) {
        res.status(error.statusCode).json(ApiResponse.error(error.message));
      } else {
        res.status(500).json(ApiResponse.error('خطأ في إلغاء القيد'));
      }
    }
  }
}

export default new AccountingController();