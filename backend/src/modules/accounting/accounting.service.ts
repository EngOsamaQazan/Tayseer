import { prisma } from '@/lib/prisma';
import { ApiError } from '@/utils/ApiError';
import { RedisService } from '@/services/redis.service';
import { Logger } from '@/utils/logger';
import { AuditService } from '@/services/audit.service';
import { NotificationService } from '@/services/notification.service';
import { ExcelService } from '@/services/excel.service';
import { PDFService } from '@/services/pdf.service';
import type {
  Account,
  JournalEntry,
  Invoice,
  Payment,
  CreateAccountInput,
  CreateJournalEntryInput,
  CreateInvoiceInput,
  CreatePaymentInput,
  FinancialReport,
  BudgetInput,
  TaxReportInput,
} from './accounting.model';

export class AccountingService {
  private redis = RedisService.getInstance();
  private logger = Logger.getInstance();
  private auditService = new AuditService();
  private notificationService = new NotificationService();
  private excelService = new ExcelService();
  private pdfService = new PDFService();

  // إدارة الحسابات
  async createAccount(data: CreateAccountInput, userId: string, tenantId: string): Promise<Account> {
    try {
      // التحقق من عدم تكرار رقم الحساب
      const existingAccount = await prisma.account.findFirst({
        where: {
          accountNumber: data.accountNumber,
          tenantId,
        },
      });

      if (existingAccount) {
        throw new ApiError(400, 'رقم الحساب موجود مسبقاً');
      }

      // إنشاء الحساب
      const account = await prisma.account.create({
        data: {
          ...data,
          balance: 0,
          tenantId,
        },
        include: {
          parentAccount: true,
          subAccounts: true,
        },
      });

      // تسجيل العملية في سجل التدقيق
      await this.auditService.log('ACCOUNT_CREATED', userId, {
        accountId: account.id,
        accountNumber: account.accountNumber,
      });

      // إبطال ذاكرة التخزين المؤقت
      await this.invalidateAccountCache(tenantId);

      return account;
    } catch (error) {
      this.logger.error('خطأ في إنشاء الحساب', error);
      throw error;
    }
  }

  // إنشاء قيد يومية
  async createJournalEntry(
    data: CreateJournalEntryInput,
    userId: string,
    tenantId: string
  ): Promise<JournalEntry> {
    try {
      // التحقق من توازن القيد
      const totalDebits = data.entries.reduce((sum, entry) => sum + (entry.debitAmount || 0), 0);
      const totalCredits = data.entries.reduce((sum, entry) => sum + (entry.creditAmount || 0), 0);

      if (totalDebits !== totalCredits) {
        throw new ApiError(400, 'القيد غير متوازن - مجموع المدين يجب أن يساوي مجموع الدائن');
      }

      // إنشاء القيد
      const journalEntry = await prisma.$transaction(async (tx) => {
        // إنشاء القيد الرئيسي
        const entry = await tx.journalEntry.create({
          data: {
            entryNumber: await this.generateEntryNumber(tenantId),
            date: new Date(data.date),
            description: data.description,
            reference: data.reference,
            type: data.type,
            status: 'DRAFT',
            totalAmount: totalDebits,
            tenantId,
            createdById: userId,
          },
        });

        // إنشاء تفاصيل القيد
        for (const line of data.entries) {
          await tx.journalEntryLine.create({
            data: {
              journalEntryId: entry.id,
              accountId: line.accountId,
              debitAmount: line.debitAmount || 0,
              creditAmount: line.creditAmount || 0,
              description: line.description,
              tenantId,
            },
          });
        }

        return entry;
      });

      // تسجيل العملية في سجل التدقيق
      await this.auditService.log('JOURNAL_ENTRY_CREATED', userId, {
        entryId: journalEntry.id,
        entryNumber: journalEntry.entryNumber,
      });

      return journalEntry;
    } catch (error) {
      this.logger.error('خطأ في إنشاء قيد اليومية', error);
      throw error;
    }
  }

  // ترحيل قيد يومية
  async postJournalEntry(entryId: string, userId: string, tenantId: string): Promise<void> {
    try {
      const entry = await prisma.journalEntry.findFirst({
        where: { id: entryId, tenantId },
        include: { lines: true },
      });

      if (!entry) {
        throw new ApiError(404, 'القيد غير موجود');
      }

      if (entry.status !== 'DRAFT') {
        throw new ApiError(400, 'لا يمكن ترحيل قيد مرحل مسبقاً');
      }

      await prisma.$transaction(async (tx) => {
        // تحديث أرصدة الحسابات
        for (const line of entry.lines) {
          await tx.account.update({
            where: { id: line.accountId },
            data: {
              balance: {
                increment: line.debitAmount - line.creditAmount,
              },
            },
          });
        }

        // تحديث حالة القيد
        await tx.journalEntry.update({
          where: { id: entryId },
          data: {
            status: 'POSTED',
            postedAt: new Date(),
            postedById: userId,
          },
        });
      });

      // تسجيل العملية في سجل التدقيق
      await this.auditService.log('JOURNAL_ENTRY_POSTED', userId, {
        entryId: entry.id,
        entryNumber: entry.entryNumber,
      });

      // إبطال ذاكرة التخزين المؤقت
      await this.invalidateAccountCache(tenantId);
    } catch (error) {
      this.logger.error('خطأ في ترحيل قيد اليومية', error);
      throw error;
    }
  }

  // إنشاء فاتورة
  async createInvoice(
    data: CreateInvoiceInput,
    userId: string,
    tenantId: string
  ): Promise<Invoice> {
    try {
      // حساب الإجماليات
      const subtotal = data.items.reduce((sum, item) => sum + item.quantity * item.unitPrice, 0);
      const taxAmount = subtotal * (data.taxRate || 0) / 100;
      const discountAmount = data.discountAmount || 0;
      const totalAmount = subtotal + taxAmount - discountAmount;

      // إنشاء الفاتورة
      const invoice = await prisma.$transaction(async (tx) => {
        const inv = await tx.invoice.create({
          data: {
            invoiceNumber: await this.generateInvoiceNumber(data.type, tenantId),
            type: data.type,
            customerId: data.customerId,
            issueDate: new Date(data.issueDate),
            dueDate: new Date(data.dueDate),
            currency: data.currency || 'SAR',
            subtotal,
            taxRate: data.taxRate || 0,
            taxAmount,
            discountAmount,
            totalAmount,
            paidAmount: 0,
            balanceDue: totalAmount,
            status: 'DRAFT',
            notes: data.notes,
            tenantId,
            createdById: userId,
          },
        });

        // إنشاء بنود الفاتورة
        for (const item of data.items) {
          await tx.invoiceItem.create({
            data: {
              invoiceId: inv.id,
              productId: item.productId,
              description: item.description,
              quantity: item.quantity,
              unitPrice: item.unitPrice,
              totalPrice: item.quantity * item.unitPrice,
              tenantId,
            },
          });
        }

        return inv;
      });

      // إرسال إشعار للعميل
      await this.notificationService.send({
        type: 'INVOICE_CREATED',
        recipientId: data.customerId,
        data: {
          invoiceId: invoice.id,
          invoiceNumber: invoice.invoiceNumber,
          totalAmount: invoice.totalAmount,
        },
      });

      return invoice;
    } catch (error) {
      this.logger.error('خطأ في إنشاء الفاتورة', error);
      throw error;
    }
  }

  // تسجيل دفعة
  async createPayment(
    data: CreatePaymentInput,
    userId: string,
    tenantId: string
  ): Promise<Payment> {
    try {
      const invoice = await prisma.invoice.findFirst({
        where: { id: data.invoiceId, tenantId },
      });

      if (!invoice) {
        throw new ApiError(404, 'الفاتورة غير موجودة');
      }

      if (data.amount > invoice.balanceDue) {
        throw new ApiError(400, 'مبلغ الدفعة أكبر من المبلغ المستحق');
      }

      const payment = await prisma.$transaction(async (tx) => {
        // إنشاء الدفعة
        const pmt = await tx.payment.create({
          data: {
            paymentNumber: await this.generatePaymentNumber(tenantId),
            invoiceId: data.invoiceId,
            amount: data.amount,
            paymentDate: new Date(data.paymentDate),
            paymentMethod: data.paymentMethod,
            reference: data.reference,
            notes: data.notes,
            status: 'COMPLETED',
            tenantId,
            createdById: userId,
          },
        });

        // تحديث الفاتورة
        const newPaidAmount = invoice.paidAmount + data.amount;
        const newBalanceDue = invoice.totalAmount - newPaidAmount;

        await tx.invoice.update({
          where: { id: data.invoiceId },
          data: {
            paidAmount: newPaidAmount,
            balanceDue: newBalanceDue,
            status: newBalanceDue === 0 ? 'PAID' : 'PARTIALLY_PAID',
          },
        });

        // إنشاء قيد يومية للدفعة
        const journalEntry = await tx.journalEntry.create({
          data: {
            entryNumber: await this.generateEntryNumber(tenantId),
            date: new Date(data.paymentDate),
            description: `دفعة للفاتورة رقم ${invoice.invoiceNumber}`,
            reference: pmt.paymentNumber,
            type: 'PAYMENT',
            status: 'POSTED',
            totalAmount: data.amount,
            tenantId,
            createdById: userId,
          },
        });

        // إضافة تفاصيل القيد
        // مدين: حساب النقدية/البنك
        await tx.journalEntryLine.create({
          data: {
            journalEntryId: journalEntry.id,
            accountId: data.accountId,
            debitAmount: data.amount,
            creditAmount: 0,
            tenantId,
          },
        });

        // دائن: حساب العميل
        const receivableAccountId = await this.getReceivableAccountId(tenantId);
        await tx.journalEntryLine.create({
          data: {
            journalEntryId: journalEntry.id,
            accountId: receivableAccountId,
            debitAmount: 0,
            creditAmount: data.amount,
            tenantId,
          },
        });

        return pmt;
      });

      // إرسال إشعار
      await this.notificationService.send({
        type: 'PAYMENT_RECEIVED',
        recipientId: invoice.customerId,
        data: {
          paymentId: payment.id,
          paymentNumber: payment.paymentNumber,
          amount: payment.amount,
        },
      });

      return payment;
    } catch (error) {
      this.logger.error('خطأ في تسجيل الدفعة', error);
      throw error;
    }
  }

  // إنشاء التقارير المالية
  async generateFinancialReport(
    type: string,
    startDate: Date,
    endDate: Date,
    tenantId: string
  ): Promise<FinancialReport> {
    try {
      switch (type) {
        case 'INCOME_STATEMENT':
          return await this.generateIncomeStatement(startDate, endDate, tenantId);
        case 'BALANCE_SHEET':
          return await this.generateBalanceSheet(endDate, tenantId);
        case 'CASH_FLOW':
          return await this.generateCashFlowStatement(startDate, endDate, tenantId);
        case 'TRIAL_BALANCE':
          return await this.generateTrialBalance(endDate, tenantId);
        default:
          throw new ApiError(400, 'نوع التقرير غير مدعوم');
      }
    } catch (error) {
      this.logger.error('خطأ في إنشاء التقرير المالي', error);
      throw error;
    }
  }

  // إنشاء قائمة الدخل
  private async generateIncomeStatement(
    startDate: Date,
    endDate: Date,
    tenantId: string
  ): Promise<FinancialReport> {
    const revenues = await this.getAccountBalances('REVENUE', startDate, endDate, tenantId);
    const expenses = await this.getAccountBalances('EXPENSE', startDate, endDate, tenantId);
    const totalRevenue = revenues.reduce((sum, acc) => sum + acc.balance, 0);
    const totalExpense = expenses.reduce((sum, acc) => sum + acc.balance, 0);
    const netIncome = totalRevenue - totalExpense;

    return {
      type: 'INCOME_STATEMENT',
      title: 'قائمة الدخل',
      titleAr: 'قائمة الدخل',
      startDate,
      endDate,
      generatedAt: new Date(),
      sections: [
        {
          title: 'الإيرادات',
          accounts: revenues,
          total: totalRevenue,
        },
        {
          title: 'المصروفات',
          accounts: expenses,
          total: totalExpense,
        },
      ],
      summary: {
        netIncome,
        totalRevenue,
        totalExpense,
      },
    };
  }

  // إنشاء الميزانية العمومية
  private async generateBalanceSheet(
    date: Date,
    tenantId: string
  ): Promise<FinancialReport> {
    const assets = await this.getAccountBalances('ASSET', null, date, tenantId);
    const liabilities = await this.getAccountBalances('LIABILITY', null, date, tenantId);
    const equity = await this.getAccountBalances('EQUITY', null, date, tenantId);

    const totalAssets = assets.reduce((sum, acc) => sum + acc.balance, 0);
    const totalLiabilities = liabilities.reduce((sum, acc) => sum + acc.balance, 0);
    const totalEquity = equity.reduce((sum, acc) => sum + acc.balance, 0);

    return {
      type: 'BALANCE_SHEET',
      title: 'الميزانية العمومية',
      titleAr: 'الميزانية العمومية',
      startDate: date,
      endDate: date,
      generatedAt: new Date(),
      sections: [
        {
          title: 'الأصول',
          accounts: assets,
          total: totalAssets,
        },
        {
          title: 'الالتزامات',
          accounts: liabilities,
          total: totalLiabilities,
        },
        {
          title: 'حقوق الملكية',
          accounts: equity,
          total: totalEquity,
        },
      ],
      summary: {
        totalAssets,
        totalLiabilities,
        totalEquity,
        balanceCheck: totalAssets === (totalLiabilities + totalEquity),
      },
    };
  }

  // إنشاء بيان التدفقات النقدية
  private async generateCashFlowStatement(
    startDate: Date,
    endDate: Date,
    tenantId: string
  ): Promise<FinancialReport> {
    // التدفقات من الأنشطة التشغيلية
    const operatingActivities = await this.getCashFlowByActivity('OPERATING', startDate, endDate, tenantId);
    // التدفقات من الأنشطة الاستثمارية
    const investingActivities = await this.getCashFlowByActivity('INVESTING', startDate, endDate, tenantId);
    // التدفقات من الأنشطة التمويلية
    const financingActivities = await this.getCashFlowByActivity('FINANCING', startDate, endDate, tenantId);

    const totalOperating = operatingActivities.reduce((sum, flow) => sum + flow.amount, 0);
    const totalInvesting = investingActivities.reduce((sum, flow) => sum + flow.amount, 0);
    const totalFinancing = financingActivities.reduce((sum, flow) => sum + flow.amount, 0);
    const netCashFlow = totalOperating + totalInvesting + totalFinancing;

    return {
      type: 'CASH_FLOW',
      title: 'بيان التدفقات النقدية',
      titleAr: 'بيان التدفقات النقدية',
      startDate,
      endDate,
      generatedAt: new Date(),
      sections: [
        {
          title: 'التدفقات النقدية من الأنشطة التشغيلية',
          items: operatingActivities,
          total: totalOperating,
        },
        {
          title: 'التدفقات النقدية من الأنشطة الاستثمارية',
          items: investingActivities,
          total: totalInvesting,
        },
        {
          title: 'التدفقات النقدية من الأنشطة التمويلية',
          items: financingActivities,
          total: totalFinancing,
        },
      ],
      summary: {
        netCashFlow,
        totalOperating,
        totalInvesting,
        totalFinancing,
      },
    };
  }

  // إنشاء ميزان المراجعة
  private async generateTrialBalance(
    date: Date,
    tenantId: string
  ): Promise<FinancialReport> {
    const accounts = await prisma.account.findMany({
      where: {
        tenantId,
        balance: { not: 0 },
      },
      orderBy: { accountNumber: 'asc' },
    });

    let totalDebits = 0;
    let totalCredits = 0;

    const accountBalances = accounts.map(account => {
      const isDebit = ['ASSET', 'EXPENSE'].includes(account.type);
      if (isDebit) {
        totalDebits += Math.abs(account.balance);
      } else {
        totalCredits += Math.abs(account.balance);
      }

      return {
        accountNumber: account.accountNumber,
        accountName: account.name,
        debit: isDebit ? Math.abs(account.balance) : 0,
        credit: !isDebit ? Math.abs(account.balance) : 0,
      };
    });

    return {
      type: 'TRIAL_BALANCE',
      title: 'ميزان المراجعة',
      titleAr: 'ميزان المراجعة',
      startDate: date,
      endDate: date,
      generatedAt: new Date(),
      accounts: accountBalances,
      summary: {
        totalDebits,
        totalCredits,
        isBalanced: Math.abs(totalDebits - totalCredits) < 0.01,
      },
    };
  }

  // إدارة الموازنة
  async createBudget(data: BudgetInput, userId: string, tenantId: string): Promise<any> {
    try {
      const budget = await prisma.budget.create({
        data: {
          ...data,
          status: 'DRAFT',
          tenantId,
          createdById: userId,
        },
      });

      // إنشاء بنود الموازنة
      for (const item of data.items) {
        await prisma.budgetItem.create({
          data: {
            budgetId: budget.id,
            accountId: item.accountId,
            plannedAmount: item.plannedAmount,
            tenantId,
          },
        });
      }

      await this.auditService.log('BUDGET_CREATED', userId, {
        budgetId: budget.id,
        name: budget.name,
      });

      return budget;
    } catch (error) {
      this.logger.error('خطأ في إنشاء الموازنة', error);
      throw error;
    }
  }

  // تحليل الانحرافات
  async analyzeBudgetVariances(budgetId: string, tenantId: string): Promise<any> {
    try {
      const budget = await prisma.budget.findFirst({
        where: { id: budgetId, tenantId },
        include: { items: { include: { account: true } } },
      });

      if (!budget) {
        throw new ApiError(404, 'الموازنة غير موجودة');
      }

      const variances = [];

      for (const item of budget.items) {
        const actualAmount = await this.getAccountBalance(
          item.accountId,
          budget.startDate,
          budget.endDate,
          tenantId
        );

        const variance = actualAmount - item.plannedAmount;
        const variancePercentage = (variance / item.plannedAmount) * 100;

        variances.push({
          accountId: item.accountId,
          accountName: item.account.name,
          plannedAmount: item.plannedAmount,
          actualAmount,
          variance,
          variancePercentage,
          status: Math.abs(variancePercentage) > 10 ? 'ALERT' : 'OK',
        });
      }

      return {
        budgetId,
        budgetName: budget.name,
        period: { start: budget.startDate, end: budget.endDate },
        variances,
        summary: {
          totalPlanned: variances.reduce((sum, v) => sum + v.plannedAmount, 0),
          totalActual: variances.reduce((sum, v) => sum + v.actualAmount, 0),
          totalVariance: variances.reduce((sum, v) => sum + v.variance, 0),
        },
      };
    } catch (error) {
      this.logger.error('خطأ في تحليل انحرافات الموازنة', error);
      throw error;
    }
  }

  // إعداد التقارير الضريبية
  async generateTaxReport(data: TaxReportInput, tenantId: string): Promise<any> {
    try {
      // حساب المبيعات الخاضعة للضريبة
      const taxableSales = await prisma.invoice.aggregate({
        where: {
          tenantId,
          type: 'SALES',
          status: { in: ['PAID', 'PARTIALLY_PAID'] },
          issueDate: {
            gte: data.startDate,
            lte: data.endDate,
          },
        },
        _sum: {
          subtotal: true,
          taxAmount: true,
        },
      });

      // حساب المشتريات الخاضعة للضريبة
      const taxablePurchases = await prisma.invoice.aggregate({
        where: {
          tenantId,
          type: 'PURCHASE',
          status: { in: ['PAID', 'PARTIALLY_PAID'] },
          issueDate: {
            gte: data.startDate,
            lte: data.endDate,
          },
        },
        _sum: {
          subtotal: true,
          taxAmount: true,
        },
      });

      const outputVat = taxableSales._sum.taxAmount || 0;
      const inputVat = taxablePurchases._sum.taxAmount || 0;
      const netVat = outputVat - inputVat;

      return {
        reportType: data.reportType,
        period: { start: data.startDate, end: data.endDate },
        sales: {
          total: taxableSales._sum.subtotal || 0,
          taxAmount: outputVat,
        },
        purchases: {
          total: taxablePurchases._sum.subtotal || 0,
          taxAmount: inputVat,
        },
        netTax: netVat,
        status: netVat > 0 ? 'PAYABLE' : 'REFUNDABLE',
      };
    } catch (error) {
      this.logger.error('خطأ في إنشاء التقرير الضريبي', error);
      throw error;
    }
  }

  // وظائف مساعدة
  private async generateEntryNumber(tenantId: string): Promise<string> {
    const lastEntry = await prisma.journalEntry.findFirst({
      where: { tenantId },
      orderBy: { entryNumber: 'desc' },
    });

    const lastNumber = lastEntry ? parseInt(lastEntry.entryNumber.slice(2)) : 0;
    return `JE${(lastNumber + 1).toString().padStart(6, '0')}`;
  }

  private async generateInvoiceNumber(type: string, tenantId: string): Promise<string> {
    const prefix = type === 'SALES' ? 'INV' : 'BILL';
    const lastInvoice = await prisma.invoice.findFirst({
      where: { tenantId, type },
      orderBy: { invoiceNumber: 'desc' },
    });

    const lastNumber = lastInvoice ? parseInt(lastInvoice.invoiceNumber.slice(3)) : 0;
    return `${prefix}${(lastNumber + 1).toString().padStart(6, '0')}`;
  }

  private async generatePaymentNumber(tenantId: string): Promise<string> {
    const lastPayment = await prisma.payment.findFirst({
      where: { tenantId },
      orderBy: { paymentNumber: 'desc' },
    });

    const lastNumber = lastPayment ? parseInt(lastPayment.paymentNumber.slice(3)) : 0;
    return `PMT${(lastNumber + 1).toString().padStart(6, '0')}`;
  }

  private async getAccountBalances(
    accountType: string,
    startDate: Date | null,
    endDate: Date,
    tenantId: string
  ): Promise<any[]> {
    const accounts = await prisma.account.findMany({
      where: {
        tenantId,
        type: accountType,
      },
      include: {
        entries: {
          where: {
            journalEntry: {
              status: 'POSTED',
              date: {
                gte: startDate || undefined,
                lte: endDate,
              },
            },
          },
        },
      },
    });

    return accounts.map(account => {
      const balance = account.entries.reduce((sum, entry) => {
        return sum + (entry.type === 'DEBIT' ? entry.amount : -entry.amount);
      }, 0);

      return {
        accountNumber: account.accountNumber,
        accountName: account.name,
        balance: Math.abs(balance),
      };
    }).filter(acc => acc.balance > 0);
  }

  private async getAccountBalance(
    accountId: string,
    startDate: Date,
    endDate: Date,
    tenantId: string
  ): Promise<number> {
    const entries = await prisma.journalEntryLine.aggregate({
      where: {
        accountId,
        journalEntry: {
          status: 'POSTED',
          date: {
            gte: startDate,
            lte: endDate,
          },
          tenantId,
        },
      },
      _sum: {
        amount: true,
      },
      groupBy: ['type'],
    });

    const debits = entries.find(e => e.type === 'DEBIT')?._sum.amount || 0;
    const credits = entries.find(e => e.type === 'CREDIT')?._sum.amount || 0;

    return debits - credits;
  }

  private async getCashFlowByActivity(
    activity: string,
    startDate: Date,
    endDate: Date,
    tenantId: string
  ): Promise<any[]> {
    // هنا يمكن تطبيق منطق أكثر تعقيداً لتصنيف التدفقات النقدية
    const cashAccounts = await prisma.account.findMany({
      where: {
        tenantId,
        type: 'ASSET',
        subType: 'CASH',
      },
      include: {
        entries: {
          where: {
            journalEntry: {
              status: 'POSTED',
              date: {
                gte: startDate,
                lte: endDate,
              },
            },
          },
          include: {
            journalEntry: true,
          },
        },
      },
    });

    const flows = [];
    cashAccounts.forEach(account => {
      account.entries.forEach(entry => {
        // تصنيف بسيط - يمكن تحسينه لاحقاً
        flows.push({
          description: entry.journalEntry.description,
          amount: entry.type === 'DEBIT' ? entry.amount : -entry.amount,
          date: entry.journalEntry.date,
        });
      });
    });

    return flows;
  }

  // إبطال ذاكرة التخزين المؤقت
  private async invalidateAccountingCache(tenantId: string): Promise<void> {
    await this.redisService.deletePattern(`accounting:${tenantId}:*`);
  }

  // تصدير البيانات المحاسبية
  async exportAccountingData(
    type: string,
    format: 'EXCEL' | 'PDF',
    filters: any,
    tenantId: string
  ): Promise<Buffer> {
    try {
      let data: any;

      switch (type) {
        case 'CHART_OF_ACCOUNTS':
          data = await this.getChartOfAccounts(tenantId);
          break;
        case 'JOURNAL_ENTRIES':
          data = await this.getJournalEntries(filters, tenantId);
          break;
        case 'GENERAL_LEDGER':
          data = await this.getGeneralLedger(filters, tenantId);
          break;
        default:
          throw new ApiError(400, 'نوع التصدير غير مدعوم');
      }

      if (format === 'EXCEL') {
        return await this.excelService.generateAccountingReport(type, data);
      } else {
        return await this.pdfService.generateAccountingReport(type, data);
      }
    } catch (error) {
      this.logger.error('خطأ في تصدير البيانات المحاسبية', error);
      throw error;
    }
  }

  private async getChartOfAccounts(tenantId: string): Promise<any> {
    return await prisma.account.findMany({
      where: { tenantId },
      orderBy: { accountNumber: 'asc' },
    });
  }

  private async getJournalEntries(filters: any, tenantId: string): Promise<any> {
    return await prisma.journalEntry.findMany({
      where: {
        tenantId,
        date: {
          gte: filters.startDate,
          lte: filters.endDate,
        },
      },
      include: {
        lines: {
          include: {
            account: true,
          },
        },
      },
      orderBy: { date: 'desc' },
    });
  }

  private async getGeneralLedger(filters: any, tenantId: string): Promise<any> {
    const accounts = await prisma.account.findMany({
      where: { tenantId },
      include: {
        entries: {
          where: {
            journalEntry: {
              date: {
                gte: filters.startDate,
                lte: filters.endDate,
              },
            },
          },
          include: {
            journalEntry: true,
          },
          orderBy: {
            journalEntry: {
              date: 'asc',
            },
          },
        },
      },
    });

    return accounts.map(account => {
      let runningBalance = 0;
      const entries = account.entries.map(entry => {
        if (entry.type === 'DEBIT') {
          runningBalance += entry.amount;
        } else {
          runningBalance -= entry.amount;
        }

        return {
          ...entry,
          runningBalance,
        };
      });

      return {
        ...account,
        entries,
        finalBalance: runningBalance,
      };
    });
  }
}

export default new AccountingService();