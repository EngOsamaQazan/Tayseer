// Formatting utilities for numbers, currencies, and strings

// Format number to Arabic numerals
export const formatArabicNumber = (num: number | string): string => {
  const arabicNumerals = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
  return num.toString().replace(/[0-9]/g, (digit) => arabicNumerals[parseInt(digit)]);
};

// Format number with thousands separator
export const formatNumber = (num: number, useArabicNumerals: boolean = false): string => {
  const formatted = num.toLocaleString('ar-SA');
  return useArabicNumerals ? formatArabicNumber(formatted) : formatted;
};

// Format currency
export const formatCurrency = (
  amount: number,
  currency: string = 'SAR',
  useArabicNumerals: boolean = false
): string => {
  const formatted = new Intl.NumberFormat('ar-SA', {
    style: 'currency',
    currency: currency,
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(amount);
  
  return useArabicNumerals ? formatArabicNumber(formatted) : formatted;
};

// Format percentage
export const formatPercentage = (
  value: number,
  decimals: number = 2,
  useArabicNumerals: boolean = false
): string => {
  const formatted = `${value.toFixed(decimals)}%`;
  return useArabicNumerals ? formatArabicNumber(formatted) : formatted;
};

// Format file size
export const formatFileSize = (bytes: number, useArabicNumerals: boolean = false): string => {
  const sizes = ['بايت', 'كيلوبايت', 'ميجابايت', 'جيجابايت', 'تيرابايت'];
  
  if (bytes === 0) {
    const zero = useArabicNumerals ? '٠' : '0';
    return `${zero} بايت`;
  }
  
  const i = Math.floor(Math.log(bytes) / Math.log(1024));
  const size = (bytes / Math.pow(1024, i)).toFixed(2);
  const formatted = `${size} ${sizes[i]}`;
  
  return useArabicNumerals ? formatArabicNumber(formatted) : formatted;
};

// Format phone number
export const formatPhoneNumber = (phone: string): string => {
  // Remove all non-digit characters
  const cleaned = phone.replace(/\D/g, '');
  
  // Saudi phone format: +966 5x xxx xxxx
  if (cleaned.startsWith('966')) {
    const number = cleaned.slice(3);
    if (number.length === 9) {
      return `+966 ${number.slice(0, 2)} ${number.slice(2, 5)} ${number.slice(5)}`;
    }
  }
  
  // Local format: 05x xxx xxxx
  if (cleaned.startsWith('05') && cleaned.length === 10) {
    return `${cleaned.slice(0, 3)} ${cleaned.slice(3, 6)} ${cleaned.slice(6)}`;
  }
  
  // Return original if no format matches
  return phone;
};

// Format IBAN
export const formatIBAN = (iban: string): string => {
  // Remove all spaces and convert to uppercase
  const cleaned = iban.replace(/\s/g, '').toUpperCase();
  
  // Saudi IBAN format: SA00 0000 0000 0000 0000 0000
  if (cleaned.startsWith('SA') && cleaned.length === 24) {
    return cleaned.match(/.{1,4}/g)?.join(' ') || iban;
  }
  
  return iban;
};

// Format National ID
export const formatNationalId = (id: string): string => {
  // Remove all non-digit characters
  const cleaned = id.replace(/\D/g, '');
  
  // Saudi ID format: 1234567890
  if (cleaned.length === 10 && /^[12]\d{9}$/.test(cleaned)) {
    return cleaned;
  }
  
  return id;
};

// Truncate text
export const truncateText = (
  text: string,
  maxLength: number,
  suffix: string = '...'
): string => {
  if (text.length <= maxLength) {
    return text;
  }
  
  return text.slice(0, maxLength - suffix.length) + suffix;
};

// Capitalize first letter
export const capitalizeFirst = (text: string): string => {
  if (!text) return '';
  return text.charAt(0).toUpperCase() + text.slice(1);
};

// Title case
export const toTitleCase = (text: string): string => {
  return text
    .split(' ')
    .map(word => capitalizeFirst(word.toLowerCase()))
    .join(' ');
};

// Remove extra spaces
export const normalizeSpaces = (text: string): string => {
  return text.replace(/\s+/g, ' ').trim();
};

// Format name (Arabic)
export const formatArabicName = (firstName: string, lastName: string): string => {
  return normalizeSpaces(`${firstName} ${lastName}`);
};

// Format full name (Arabic with father's name)
export const formatFullArabicName = (
  firstName: string,
  fatherName: string,
  lastName: string
): string => {
  return normalizeSpaces(`${firstName} ${fatherName} ${lastName}`);
};

// Pluralize Arabic word
export const pluralizeArabic = (count: number, singular: string, dual: string, plural: string): string => {
  if (count === 0 || count > 10) {
    return `${count} ${singular}`;
  } else if (count === 1) {
    return `${singular} واحد`;
  } else if (count === 2) {
    return dual;
  } else if (count >= 3 && count <= 10) {
    return `${count} ${plural}`;
  }
  return `${count} ${singular}`;
};

// Format duration
export const formatDuration = (seconds: number): string => {
  const hours = Math.floor(seconds / 3600);
  const minutes = Math.floor((seconds % 3600) / 60);
  const secs = seconds % 60;
  
  const parts = [];
  
  if (hours > 0) {
    parts.push(pluralizeArabic(hours, 'ساعة', 'ساعتان', 'ساعات'));
  }
  
  if (minutes > 0) {
    parts.push(pluralizeArabic(minutes, 'دقيقة', 'دقيقتان', 'دقائق'));
  }
  
  if (secs > 0 && hours === 0) {
    parts.push(pluralizeArabic(secs, 'ثانية', 'ثانيتان', 'ثواني'));
  }
  
  return parts.join(' و ');
};

// Format address
export const formatAddress = (address: {
  street?: string;
  district?: string;
  city?: string;
  postalCode?: string;
  country?: string;
}): string => {
  const parts = [
    address.street,
    address.district,
    address.city,
    address.postalCode,
    address.country,
  ].filter(Boolean);
  
  return parts.join('، ');
};

// Format status
export const formatStatus = (status: string): { text: string; color: string } => {
  const statusMap: Record<string, { text: string; color: string }> = {
    active: { text: 'نشط', color: 'green' },
    inactive: { text: 'غير نشط', color: 'gray' },
    pending: { text: 'قيد الانتظار', color: 'yellow' },
    approved: { text: 'موافق عليه', color: 'green' },
    rejected: { text: 'مرفوض', color: 'red' },
    completed: { text: 'مكتمل', color: 'blue' },
    cancelled: { text: 'ملغي', color: 'red' },
    draft: { text: 'مسودة', color: 'gray' },
    published: { text: 'منشور', color: 'green' },
    expired: { text: 'منتهي الصلاحية', color: 'red' },
  };
  
  return statusMap[status.toLowerCase()] || { text: status, color: 'gray' };
};

// Generate initials
export const getInitials = (name: string): string => {
  const words = name.trim().split(' ');
  if (words.length === 0) return '';
  
  if (words.length === 1) {
    return words[0].charAt(0).toUpperCase();
  }
  
  return words[0].charAt(0).toUpperCase() + words[words.length - 1].charAt(0).toUpperCase();
};

// Format credit card number
export const formatCreditCard = (number: string): string => {
  const cleaned = number.replace(/\s/g, '');
  const groups = cleaned.match(/.{1,4}/g);
  return groups ? groups.join(' ') : number;
};

// Mask sensitive data
export const maskData = (data: string, visibleChars: number = 4): string => {
  if (data.length <= visibleChars) {
    return data;
  }
  
  const masked = '*'.repeat(data.length - visibleChars);
  return masked + data.slice(-visibleChars);
};

// Format list as string
export const formatList = (items: string[], separator: string = '، '): string => {
  if (items.length === 0) return '';
  if (items.length === 1) return items[0];
  if (items.length === 2) return `${items[0]} و ${items[1]}`;
  
  const lastItem = items[items.length - 1];
  const otherItems = items.slice(0, -1);
  return `${otherItems.join(separator)} و ${lastItem}`;
};