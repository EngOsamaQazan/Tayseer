import { Decimal } from 'decimal.js';
import { logger } from '../config/logger';

// تكوين Decimal.js
Decimal.set({
  precision: 20,
  rounding: Decimal.ROUND_HALF_UP,
  toExpNeg: -9,
  toExpPos: 9
});

// ==================== الحسابات المالية ====================

// حساب ضريبة القيمة المضافة
export const calculateVAT = (amount: number, vatRate: number = 15): {
  vatAmount: number;
  totalWithVat: number;
  netAmount: number;
} => {
  const amountDecimal = new Decimal(amount);
  const vatRateDecimal = new Decimal(vatRate).dividedBy(100);
  
  const vatAmount = amountDecimal.times(vatRateDecimal);
  const totalWithVat = amountDecimal.plus(vatAmount);
  
  return {
    vatAmount: vatAmount.toNumber(),
    totalWithVat: totalWithVat.toNumber(),
    netAmount: amount
  };
};

// استخراج المبلغ الأساسي من المبلغ الإجمالي
export const extractNetAmount = (totalAmount: number, vatRate: number = 15): {
  netAmount: number;
  vatAmount: number;
  totalAmount: number;
} => {
  const totalDecimal = new Decimal(totalAmount);
  const vatRateDecimal = new Decimal(vatRate).dividedBy(100);
  
  const netAmount = totalDecimal.dividedBy(new Decimal(1).plus(vatRateDecimal));
  const vatAmount = totalDecimal.minus(netAmount);
  
  return {
    netAmount: netAmount.toNumber(),
    vatAmount: vatAmount.toNumber(),
    totalAmount: totalAmount
  };
};

// حساب الخصم
export const calculateDiscount = (
  amount: number,
  discountPercentage?: number,
  discountAmount?: number
): {
  discountAmount: number;
  finalAmount: number;
  discountPercentage: number;
} => {
  const amountDecimal = new Decimal(amount);
  let discount: Decimal;
  let percentage: number;
  
  if (discountPercentage !== undefined) {
    percentage = discountPercentage;
    discount = amountDecimal.times(new Decimal(discountPercentage).dividedBy(100));
  } else if (discountAmount !== undefined) {
    discount = new Decimal(discountAmount);
    percentage = discount.dividedBy(amountDecimal).times(100).toNumber();
  } else {
    discount = new Decimal(0);
    percentage = 0;
  }
  
  const finalAmount = amountDecimal.minus(discount);
  
  return {
    discountAmount: discount.toNumber(),
    finalAmount: finalAmount.toNumber(),
    discountPercentage: percentage
  };
};

// حساب هامش الربح
export const calculateProfitMargin = (
  revenue: number,
  cost: number
): {
  profit: number;
  profitMargin: number;
  profitPercentage: number;
  markupPercentage: number;
} => {
  const revenueDecimal = new Decimal(revenue);
  const costDecimal = new Decimal(cost);
  
  const profit = revenueDecimal.minus(costDecimal);
  const profitMargin = profit.dividedBy(revenueDecimal).times(100);
  const profitPercentage = profit.dividedBy(costDecimal).times(100);
  const markupPercentage = revenueDecimal.dividedBy(costDecimal).minus(1).times(100);
  
  return {
    profit: profit.toNumber(),
    profitMargin: profitMargin.toNumber(),
    profitPercentage: profitPercentage.toNumber(),
    markupPercentage: markupPercentage.toNumber()
  };
};

// ==================== حسابات الفواتير ====================

// حساب إجمالي الفاتورة
export const calculateInvoiceTotal = (
  items: Array<{
    quantity: number;
    unitPrice: number;
    discountPercentage?: number;
    vatRate?: number;
  }>,
  globalDiscount?: number,
  globalVatRate: number = 15
): {
  subtotal: number;
  totalDiscount: number;
  totalVat: number;
  grandTotal: number;
  itemsBreakdown: Array<{
    total: number;
    discount: number;
    vat: number;
    netAmount: number;
  }>;
} => {
  let subtotal = new Decimal(0);
  let totalDiscount = new Decimal(0);
  const itemsBreakdown: Array<any> = [];
  
  // حساب البنود
  for (const item of items) {
    const itemTotal = new Decimal(item.quantity).times(item.unitPrice);
    let itemDiscount = new Decimal(0);
    
    if (item.discountPercentage) {
      itemDiscount = itemTotal.times(new Decimal(item.discountPercentage).dividedBy(100));
    }
    
    const netAmount = itemTotal.minus(itemDiscount);
    const vatRate = item.vatRate !== undefined ? item.vatRate : globalVatRate;
    const itemVat = netAmount.times(new Decimal(vatRate).dividedBy(100));
    
    subtotal = subtotal.plus(itemTotal);
    totalDiscount = totalDiscount.plus(itemDiscount);
    
    itemsBreakdown.push({
      total: itemTotal.toNumber(),
      discount: itemDiscount.toNumber(),
      vat: itemVat.toNumber(),
      netAmount: netAmount.toNumber()
    });
  }
  
  // تطبيق الخصم العام
  if (globalDiscount) {
    const globalDiscountAmount = subtotal.minus(totalDiscount).times(new Decimal(globalDiscount).dividedBy(100));
    totalDiscount = totalDiscount.plus(globalDiscountAmount);
  }
  
  // حساب الضريبة على المبلغ بعد الخصم
  const netTotal = subtotal.minus(totalDiscount);
  const totalVat = netTotal.times(new Decimal(globalVatRate).dividedBy(100));
  const grandTotal = netTotal.plus(totalVat);
  
  return {
    subtotal: subtotal.toNumber(),
    totalDiscount: totalDiscount.toNumber(),
    totalVat: totalVat.toNumber(),
    grandTotal: grandTotal.toNumber(),
    itemsBreakdown
  };
};

// ==================== حسابات الأقساط ====================

// حساب الأقساط
export const calculateInstallments = (
  totalAmount: number,
  numberOfInstallments: number,
  downPaymentPercentage: number = 0,
  interestRate: number = 0
): {
  downPayment: number;
  remainingAmount: number;
  installmentAmount: number;
  totalWithInterest: number;
  totalInterest: number;
  installments: Array<{
    installmentNumber: number;
    amount: number;
    dueDate: Date;
    principal: number;
    interest: number;
  }>;
} => {
  const total = new Decimal(totalAmount);
  const downPayment = total.times(new Decimal(downPaymentPercentage).dividedBy(100));
  const remainingAmount = total.minus(downPayment);
  
  let totalWithInterest = remainingAmount;
  let totalInterest = new Decimal(0);
  let installmentAmount: Decimal;
  
  if (interestRate > 0) {
    const monthlyRate = new Decimal(interestRate).dividedBy(100).dividedBy(12);
    const compound = new Decimal(1).plus(monthlyRate).pow(numberOfInstallments);
    
    installmentAmount = remainingAmount.times(monthlyRate).times(compound).dividedBy(compound.minus(1));
    totalWithInterest = installmentAmount.times(numberOfInstallments);
    totalInterest = totalWithInterest.minus(remainingAmount);
  } else {
    installmentAmount = remainingAmount.dividedBy(numberOfInstallments);
  }
  
  // توليد جدول الأقساط
  const installments: Array<any> = [];
  let remainingBalance = remainingAmount;
  const currentDate = new Date();
  
  for (let i = 1; i <= numberOfInstallments; i++) {
    const dueDate = new Date(currentDate);
    dueDate.setMonth(dueDate.getMonth() + i);
    
    let interest = new Decimal(0);
    let principal = installmentAmount;
    
    if (interestRate > 0) {
      interest = remainingBalance.times(new Decimal(interestRate).dividedBy(100).dividedBy(12));
      principal = installmentAmount.minus(interest);
    }
    
    remainingBalance = remainingBalance.minus(principal);
    
    installments.push({
      installmentNumber: i,
      amount: installmentAmount.toNumber(),
      dueDate,
      principal: principal.toNumber(),
      interest: interest.toNumber()
    });
  }
  
  return {
    downPayment: downPayment.toNumber(),
    remainingAmount: remainingAmount.toNumber(),
    installmentAmount: installmentAmount.toNumber(),
    totalWithInterest: totalWithInterest.toNumber(),
    totalInterest: totalInterest.toNumber(),
    installments
  };
};

// ==================== حسابات المخزون ====================

// حساب قيمة المخزون
export const calculateInventoryValue = (
  items: Array<{
    quantity: number;
    unitCost: number;
  }>
): {
  totalQuantity: number;
  totalValue: number;
  averageCost: number;
} => {
  let totalQuantity = new Decimal(0);
  let totalValue = new Decimal(0);
  
  for (const item of items) {
    const quantity = new Decimal(item.quantity);
    const value = quantity.times(item.unitCost);
    
    totalQuantity = totalQuantity.plus(quantity);
    totalValue = totalValue.plus(value);
  }
  
  const averageCost = totalQuantity.isZero() ? new Decimal(0) : totalValue.dividedBy(totalQuantity);
  
  return {
    totalQuantity: totalQuantity.toNumber(),
    totalValue: totalValue.toNumber(),
    averageCost: averageCost.toNumber()
  };
};

// حساب نقطة إعادة الطلب
export const calculateReorderPoint = (
  averageDailyUsage: number,
  leadTimeDays: number,
  safetyStockDays: number = 7
): number => {
  const usage = new Decimal(averageDailyUsage);
  const leadTime = new Decimal(leadTimeDays);
  const safetyDays = new Decimal(safetyStockDays);
  
  const reorderPoint = usage.times(leadTime.plus(safetyDays));
  
  return reorderPoint.toNumber();
};

// ==================== حسابات الرواتب ====================

// حساب الراتب الصافي
export const calculateNetSalary = (
  basicSalary: number,
  allowances: number = 0,
  deductions: number = 0,
  socialInsuranceRate: number = 9.75,
  taxRate: number = 0
): {
  grossSalary: number;
  socialInsurance: number;
  tax: number;
  totalDeductions: number;
  netSalary: number;
} => {
  const basic = new Decimal(basicSalary);
  const allow = new Decimal(allowances);
  const deduct = new Decimal(deductions);
  
  const grossSalary = basic.plus(allow);
  const socialInsurance = grossSalary.times(new Decimal(socialInsuranceRate).dividedBy(100));
  const taxableAmount = grossSalary.minus(socialInsurance);
  const tax = taxableAmount.times(new Decimal(taxRate).dividedBy(100));
  
  const totalDeductions = deduct.plus(socialInsurance).plus(tax);
  const netSalary = grossSalary.minus(totalDeductions);
  
  return {
    grossSalary: grossSalary.toNumber(),
    socialInsurance: socialInsurance.toNumber(),
    tax: tax.toNumber(),
    totalDeductions: totalDeductions.toNumber(),
    netSalary: netSalary.toNumber()
  };
};

// ==================== حسابات الأداء ====================

// حساب معدل النمو
export const calculateGrowthRate = (
  currentValue: number,
  previousValue: number
): {
  growthAmount: number;
  growthRate: number;
  isPositive: boolean;
} => {
  const current = new Decimal(currentValue);
  const previous = new Decimal(previousValue);
  
  if (previous.isZero()) {
    return {
      growthAmount: current.toNumber(),
      growthRate: 100,
      isPositive: current.greaterThan(0)
    };
  }
  
  const growthAmount = current.minus(previous);
  const growthRate = growthAmount.dividedBy(previous).times(100);
  
  return {
    growthAmount: growthAmount.toNumber(),
    growthRate: growthRate.toNumber(),
    isPositive: growthAmount.greaterThanOrEqualTo(0)
  };
};

// حساب العائد على الاستثمار
export const calculateROI = (
  gain: number,
  cost: number
): {
  roi: number;
  roiPercentage: number;
  netGain: number;
} => {
  const gainDecimal = new Decimal(gain);
  const costDecimal = new Decimal(cost);
  
  if (costDecimal.isZero()) {
    return {
      roi: 0,
      roiPercentage: 0,
      netGain: gainDecimal.toNumber()
    };
  }
  
  const netGain = gainDecimal.minus(costDecimal);
  const roi = netGain.dividedBy(costDecimal);
  const roiPercentage = roi.times(100);
  
  return {
    roi: roi.toNumber(),
    roiPercentage: roiPercentage.toNumber(),
    netGain: netGain.toNumber()
  };
};

// ==================== دوال مساعدة ====================

// تقريب إلى منازل عشرية محددة
export const roundToDecimal = (value: number, decimals: number = 2): number => {
  const decimal = new Decimal(value);
  return decimal.toDecimalPlaces(decimals).toNumber();
};

// تحويل النسبة المئوية إلى كسر عشري
export const percentageToDecimal = (percentage: number): number => {
  return new Decimal(percentage).dividedBy(100).toNumber();
};

// تحويل الكسر العشري إلى نسبة مئوية
export const decimalToPercentage = (decimal: number): number => {
  return new Decimal(decimal).times(100).toNumber();
};

// حساب المتوسط
export const calculateAverage = (values: number[]): number => {
  if (values.length === 0) return 0;
  
  const sum = values.reduce((acc, val) => acc.plus(val), new Decimal(0));
  return sum.dividedBy(values.length).toNumber();
};

// حساب المتوسط المرجح
export const calculateWeightedAverage = (
  values: Array<{ value: number; weight: number }>
): number => {
  if (values.length === 0) return 0;
  
  let totalWeight = new Decimal(0);
  let weightedSum = new Decimal(0);
  
  for (const item of values) {
    const weight = new Decimal(item.weight);
    const value = new Decimal(item.value);
    
    totalWeight = totalWeight.plus(weight);
    weightedSum = weightedSum.plus(value.times(weight));
  }
  
  if (totalWeight.isZero()) return 0;
  
  return weightedSum.dividedBy(totalWeight).toNumber();
};

// حساب النسبة
export const calculateRatio = (numerator: number, denominator: number): number => {
  const num = new Decimal(numerator);
  const den = new Decimal(denominator);
  
  if (den.isZero()) return 0;
  
  return num.dividedBy(den).toNumber();
};

// حساب الحد الأدنى والأقصى والمجموع
export const calculateStats = (values: number[]): {
  min: number;
  max: number;
  sum: number;
  average: number;
  count: number;
} => {
  if (values.length === 0) {
    return {
      min: 0,
      max: 0,
      sum: 0,
      average: 0,
      count: 0
    };
  }
  
  const decimals = values.map(v => new Decimal(v));
  const sum = decimals.reduce((acc, val) => acc.plus(val), new Decimal(0));
  
  return {
    min: Decimal.min(...decimals).toNumber(),
    max: Decimal.max(...decimals).toNumber(),
    sum: sum.toNumber(),
    average: sum.dividedBy(values.length).toNumber(),
    count: values.length
  };
};

// ==================== حسابات التواريخ المالية ====================

// حساب عدد الأيام بين تاريخين
export const calculateDaysBetween = (startDate: Date, endDate: Date): number => {
  const start = new Date(startDate);
  const end = new Date(endDate);
  const diffTime = Math.abs(end.getTime() - start.getTime());
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
  return diffDays;
};

// حساب الفائدة البسيطة
export const calculateSimpleInterest = (
  principal: number,
  rate: number,
  timeInDays: number
): {
  interest: number;
  totalAmount: number;
} => {
  const p = new Decimal(principal);
  const r = new Decimal(rate).dividedBy(100).dividedBy(365);
  const t = new Decimal(timeInDays);
  
  const interest = p.times(r).times(t);
  const totalAmount = p.plus(interest);
  
  return {
    interest: interest.toNumber(),
    totalAmount: totalAmount.toNumber()
  };
};

// حساب الفائدة المركبة
export const calculateCompoundInterest = (
  principal: number,
  rate: number,
  timeInYears: number,
  compoundingFrequency: number = 12
): {
  interest: number;
  totalAmount: number;
} => {
  const p = new Decimal(principal);
  const r = new Decimal(rate).dividedBy(100);
  const n = new Decimal(compoundingFrequency);
  const t = new Decimal(timeInYears);
  
  const amount = p.times(new Decimal(1).plus(r.dividedBy(n)).pow(n.times(t)));
  const interest = amount.minus(p);
  
  return {
    interest: interest.toNumber(),
    totalAmount: amount.toNumber()
  };
};

// ==================== حسابات العمولات ====================

// حساب العمولة
export const calculateCommission = (
  amount: number,
  commissionRate: number,
  minimumCommission: number = 0,
  maximumCommission?: number
): number => {
  const amountDecimal = new Decimal(amount);
  const rate = new Decimal(commissionRate).dividedBy(100);
  
  let commission = amountDecimal.times(rate);
  
  // تطبيق الحد الأدنى
  if (minimumCommission > 0) {
    commission = Decimal.max(commission, new Decimal(minimumCommission));
  }
  
  // تطبيق الحد الأقصى
  if (maximumCommission !== undefined && maximumCommission > 0) {
    commission = Decimal.min(commission, new Decimal(maximumCommission));
  }
  
  return commission.toNumber();
};

// حساب العمولة المتدرجة
export const calculateTieredCommission = (
  amount: number,
  tiers: Array<{
    min: number;
    max?: number;
    rate: number;
  }>
): {
  commission: number;
  breakdown: Array<{
    tierAmount: number;
    rate: number;
    commission: number;
  }>;
} => {
  const amountDecimal = new Decimal(amount);
  let totalCommission = new Decimal(0);
  const breakdown: Array<any> = [];
  
  // ترتيب المستويات حسب الحد الأدنى
  const sortedTiers = [...tiers].sort((a, b) => a.min - b.min);
  
  for (let i = 0; i < sortedTiers.length; i++) {
    const tier = sortedTiers[i];
    const tierMin = new Decimal(tier.min);
    const tierMax = tier.max ? new Decimal(tier.max) : amountDecimal;
    const rate = new Decimal(tier.rate).dividedBy(100);
    
    if (amountDecimal.lessThanOrEqualTo(tierMin)) {
      break;
    }
    
    const tierAmount = Decimal.min(amountDecimal, tierMax).minus(tierMin);
    const tierCommission = tierAmount.times(rate);
    
    totalCommission = totalCommission.plus(tierCommission);
    
    breakdown.push({
      tierAmount: tierAmount.toNumber(),
      rate: tier.rate,
      commission: tierCommission.toNumber()
    });
    
    if (amountDecimal.lessThanOrEqualTo(tierMax)) {
      break;
    }
  }
  
  return {
    commission: totalCommission.toNumber(),
    breakdown
  };
};

// ==================== التحقق من الصحة ====================

// التحقق من صحة المبلغ
export const isValidAmount = (amount: number): boolean => {
  try {
    const decimal = new Decimal(amount);
    return decimal.isFinite() && decimal.greaterThanOrEqualTo(0);
  } catch (error) {
    return false;
  }
};

// التحقق من صحة النسبة المئوية
export const isValidPercentage = (percentage: number): boolean => {
  try {
    const decimal = new Decimal(percentage);
    return decimal.isFinite() && decimal.greaterThanOrEqualTo(0) && decimal.lessThanOrEqualTo(100);
  } catch (error) {
    return false;
  }
};

// التحقق من صحة السعر
export const isValidPrice = (price: number): boolean => {
  try {
    const decimal = new Decimal(price);
    return decimal.isFinite() && decimal.greaterThan(0);
  } catch (error) {
    return false;
  }
};

// ==================== تصدير الأنواع ====================

export interface InvoiceItem {
  quantity: number;
  unitPrice: number;
  discountPercentage?: number;
  vatRate?: number;
}

export interface InstallmentPlan {
  downPayment: number;
  remainingAmount: number;
  installmentAmount: number;
  totalWithInterest: number;
  totalInterest: number;
  installments: Array<{
    installmentNumber: number;
    amount: number;
    dueDate: Date;
    principal: number;
    interest: number;
  }>;
}

export interface CommissionTier {
  min: number;
  max?: number;
  rate: number;
}

export interface CalculationError extends Error {
  code: 'INVALID_INPUT' | 'DIVISION_BY_ZERO' | 'CALCULATION_ERROR';
  details?: any;
}

// دالة مساعدة لإنشاء أخطاء حسابية
export const createCalculationError = (
  message: string,
  code: CalculationError['code'],
  details?: any
): CalculationError => {
  const error = new Error(message) as CalculationError;
  error.code = code;
  error.details = details;
  return error;
};