// أدوات التنسيق والأرقام

// تنسيق الأرقام بالصيغة العربية
export const formatArabicNumber = (number: number): string => {
  const arabicNumerals = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
  return number.toString().replace(/[0-9]/g, (digit) => arabicNumerals[parseInt(digit)]);
};

// تنسيق العملة بالريال السعودي
export const formatCurrency = (
  amount: number,
  options: {
    showCurrency?: boolean;
    decimals?: number;
    useArabicNumerals?: boolean;
  } = {}
): string => {
  const {
    showCurrency = true,
    decimals = 2,
    useArabicNumerals = false
  } = options;
  
  const formatted = amount.toLocaleString('ar-SA', {
    minimumFractionDigits: decimals,
    maximumFractionDigits: decimals
  });
  
  const result = showCurrency ? `${formatted} ريال` : formatted;
  
  return useArabicNumerals ? formatArabicNumber(parseFloat(formatted)) : result;
};

// تنسيق النسبة المئوية
export const formatPercentage = (
  value: number,
  decimals: number = 2,
  useArabicNumerals: boolean = false
): string => {
  const formatted = `${value.toFixed(decimals)}%`;
  return useArabicNumerals ? formatArabicNumber(parseFloat(formatted)) : formatted;
};

// تنسيق رقم الهاتف السعودي
export const formatPhoneNumber = (phone: string): string => {
  // إزالة جميع الأحرف غير الرقمية
  const cleaned = phone.replace(/\D/g, '');
  
  // التحقق من الطول
  if (cleaned.length === 9) {
    // إضافة رمز البلد إذا لم يكن موجوداً
    return `+966 ${cleaned.slice(0, 2)} ${cleaned.slice(2, 5)} ${cleaned.slice(5)}`;
  } else if (cleaned.length === 12 && cleaned.startsWith('966')) {
    // التنسيق مع رمز البلد
    return `+${cleaned.slice(0, 3)} ${cleaned.slice(3, 5)} ${cleaned.slice(5, 8)} ${cleaned.slice(8)}`;
  }
  
  return phone; // إرجاع الرقم كما هو إذا لم يتطابق مع التنسيق المتوقع
};

// تنسيق رقم الهوية الوطنية السعودية
export const formatNationalId = (id: string): string => {
  const cleaned = id.replace(/\D/g, '');
  
  if (cleaned.length === 10) {
    return `${cleaned.slice(0, 1)}-${cleaned.slice(1, 5)}-${cleaned.slice(5, 9)}-${cleaned.slice(9)}`;
  }
  
  return id;
};

// تنسيق رقم السجل التجاري
export const formatCommercialRegister = (cr: string): string => {
  const cleaned = cr.replace(/\D/g, '');
  
  if (cleaned.length === 10) {
    return `${cleaned.slice(0, 4)}-${cleaned.slice(4, 7)}-${cleaned.slice(7)}`;
  }
  
  return cr;
};

// تنسيق رقم الحساب البنكي (IBAN)
export const formatIBAN = (iban: string): string => {
  const cleaned = iban.replace(/\s/g, '').toUpperCase();
  
  if (cleaned.startsWith('SA') && cleaned.length === 24) {
    return cleaned.match(/.{1,4}/g)?.join(' ') || cleaned;
  }
  
  return iban;
};

// تنسيق حجم الملف
export const formatFileSize = (bytes: number): string => {
  const units = ['بايت', 'كيلوبايت', 'ميجابايت', 'جيجابايت', 'تيرابايت'];
  
  if (bytes === 0) return '0 بايت';
  
  const k = 1024;
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  
  return `${parseFloat((bytes / Math.pow(k, i)).toFixed(2))} ${units[i]}`;
};

// تنسيق المدة الزمنية
export const formatDuration = (seconds: number): string => {
  const hours = Math.floor(seconds / 3600);
  const minutes = Math.floor((seconds % 3600) / 60);
  const secs = seconds % 60;
  
  const parts = [];
  
  if (hours > 0) parts.push(`${hours} ساعة`);
  if (minutes > 0) parts.push(`${minutes} دقيقة`);
  if (secs > 0 || parts.length === 0) parts.push(`${secs} ثانية`);
  
  return parts.join(' و ');
};

// تقريب الأرقام
export const roundNumber = (
  value: number,
  decimals: number = 2
): number => {
  return Math.round(value * Math.pow(10, decimals)) / Math.pow(10, decimals);
};

// تنسيق الأرقام الكبيرة
export const formatLargeNumber = (
  value: number,
  useArabicNumerals: boolean = false
): string => {
  const billion = 1000000000;
  const million = 1000000;
  const thousand = 1000;
  
  let result: string;
  
  if (value >= billion) {
    result = `${(value / billion).toFixed(1)} مليار`;
  } else if (value >= million) {
    result = `${(value / million).toFixed(1)} مليون`;
  } else if (value >= thousand) {
    result = `${(value / thousand).toFixed(1)} ألف`;
  } else {
    result = value.toString();
  }
  
  return useArabicNumerals ? formatArabicNumber(parseFloat(result)) : result;
};

// تنسيق العنوان
export const formatAddress = (address: {
  street?: string;
  district?: string;
  city?: string;
  postalCode?: string;
  country?: string;
}): string => {
  const parts = [];
  
  if (address.street) parts.push(address.street);
  if (address.district) parts.push(address.district);
  if (address.city) parts.push(address.city);
  if (address.postalCode) parts.push(address.postalCode);
  if (address.country) parts.push(address.country);
  
  return parts.join('، ');
};

// تنسيق الاسم الكامل
export const formatFullName = (
  firstName: string,
  lastName: string,
  middleName?: string
): string => {
  const parts = [firstName];
  
  if (middleName) parts.push(middleName);
  parts.push(lastName);
  
  return parts.join(' ');
};

// تنويع صيغة الجمع بناءً على العدد
export const pluralize = (
  count: number,
  singular: string,
  dual: string,
  plural: string
): string => {
  if (count === 1) return `${count} ${singular}`;
  if (count === 2) return `${count} ${dual}`;
  if (count >= 3 && count <= 10) return `${count} ${plural}`;
  return `${count} ${singular}`;
};

// إزالة المسافات الزائدة
export const trimExtraSpaces = (text: string): string => {
  return text.replace(/\s+/g, ' ').trim();
};

// تحويل النص إلى حالة العنوان
export const toTitleCase = (text: string): string => {
  return text.replace(/\b\w/g, (char) => char.toUpperCase());
};

// اختصار النص
export const truncateText = (
  text: string,
  maxLength: number,
  ellipsis: string = '...'
): string => {
  if (text.length <= maxLength) return text;
  return text.slice(0, maxLength - ellipsis.length) + ellipsis;
};

// تنسيق رقم الفاتورة
export const formatInvoiceNumber = (
  number: number,
  prefix: string = 'INV',
  padLength: number = 6
): string => {
  return `${prefix}-${number.toString().padStart(padLength, '0')}`;
};

// تنسيق رمز المنتج
export const formatProductCode = (
  category: string,
  id: number,
  variant?: string
): string => {
  const categoryCode = category.substring(0, 3).toUpperCase();
  const productId = id.toString().padStart(4, '0');
  const variantCode = variant ? `-${variant.toUpperCase()}` : '';
  
  return `${categoryCode}-${productId}${variantCode}`;
};

// إخفاء جزء من النص (للبيانات الحساسة)
export const maskText = (
  text: string,
  visibleStart: number = 3,
  visibleEnd: number = 3,
  maskChar: string = '*'
): string => {
  if (text.length <= visibleStart + visibleEnd) return text;
  
  const start = text.slice(0, visibleStart);
  const end = text.slice(-visibleEnd);
  const middle = maskChar.repeat(text.length - visibleStart - visibleEnd);
  
  return `${start}${middle}${end}`;
};

// تنسيق قائمة العناصر
export const formatList = (
  items: string[],
  conjunction: string = 'و'
): string => {
  if (items.length === 0) return '';
  if (items.length === 1) return items[0];
  if (items.length === 2) return items.join(` ${conjunction} `);
  
  const lastItem = items[items.length - 1];
  const otherItems = items.slice(0, -1);
  
  return `${otherItems.join('، ')}، ${conjunction} ${lastItem}`;
};

// تحويل كاميل كيس إلى سنيك كيس
export const camelToSnake = (text: string): string => {
  return text.replace(/[A-Z]/g, (letter) => `_${letter.toLowerCase()}`);
};

// تحويل سنيك كيس إلى كاميل كيس
export const snakeToCamel = (text: string): string => {
  return text.replace(/_([a-z])/g, (_, letter) => letter.toUpperCase());
};

// تنسيق رسالة الخطأ
export const formatErrorMessage = (
  error: Error | string,
  fallbackMessage: string = 'حدث خطأ غير متوقع'
): string => {
  if (typeof error === 'string') return error;
  return error.message || fallbackMessage;
};

// تنسيق كود اللون
export const formatColorCode = (color: string): string => {
  const cleaned = color.replace(/[^a-fA-F0-9]/g, '');
  
  if (cleaned.length === 3) {
    return `#${cleaned}`;
  } else if (cleaned.length === 6) {
    return `#${cleaned}`;
  }
  
  return color;
};