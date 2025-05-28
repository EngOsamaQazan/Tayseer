// Date utilities for handling dates and times

// Arabic month names
const arabicMonths = [
  'يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو',
  'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'
];

// Arabic day names
const arabicDays = [
  'الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'
];

// Format date to Arabic format
export const formatArabicDate = (date: Date | string, includeTime: boolean = false): string => {
  const d = typeof date === 'string' ? new Date(date) : date;
  
  if (isNaN(d.getTime())) {
    return 'تاريخ غير صالح';
  }
  
  const day = d.getDate();
  const month = arabicMonths[d.getMonth()];
  const year = d.getFullYear();
  
  let formatted = `${day} ${month} ${year}`;
  
  if (includeTime) {
    const hours = d.getHours().toString().padStart(2, '0');
    const minutes = d.getMinutes().toString().padStart(2, '0');
    formatted += ` - ${hours}:${minutes}`;
  }
  
  return formatted;
};

// Format date to ISO format
export const formatISODate = (date: Date | string): string => {
  const d = typeof date === 'string' ? new Date(date) : date;
  return d.toISOString().split('T')[0];
};

// Format time
export const formatTime = (date: Date | string, use24Hour: boolean = true): string => {
  const d = typeof date === 'string' ? new Date(date) : date;
  
  if (use24Hour) {
    const hours = d.getHours().toString().padStart(2, '0');
    const minutes = d.getMinutes().toString().padStart(2, '0');
    return `${hours}:${minutes}`;
  } else {
    let hours = d.getHours();
    const minutes = d.getMinutes().toString().padStart(2, '0');
    const period = hours >= 12 ? 'م' : 'ص';
    hours = hours % 12 || 12;
    return `${hours}:${minutes} ${period}`;
  }
};

// Get relative time in Arabic
export const getRelativeTime = (date: Date | string): string => {
  const d = typeof date === 'string' ? new Date(date) : date;
  const now = new Date();
  const diffInSeconds = Math.floor((now.getTime() - d.getTime()) / 1000);
  
  if (diffInSeconds < 60) {
    return 'الآن';
  }
  
  const diffInMinutes = Math.floor(diffInSeconds / 60);
  if (diffInMinutes < 60) {
    if (diffInMinutes === 1) return 'منذ دقيقة';
    if (diffInMinutes === 2) return 'منذ دقيقتين';
    if (diffInMinutes <= 10) return `منذ ${diffInMinutes} دقائق`;
    return `منذ ${diffInMinutes} دقيقة`;
  }
  
  const diffInHours = Math.floor(diffInMinutes / 60);
  if (diffInHours < 24) {
    if (diffInHours === 1) return 'منذ ساعة';
    if (diffInHours === 2) return 'منذ ساعتين';
    if (diffInHours <= 10) return `منذ ${diffInHours} ساعات`;
    return `منذ ${diffInHours} ساعة`;
  }
  
  const diffInDays = Math.floor(diffInHours / 24);
  if (diffInDays < 30) {
    if (diffInDays === 1) return 'أمس';
    if (diffInDays === 2) return 'منذ يومين';
    if (diffInDays <= 10) return `منذ ${diffInDays} أيام`;
    return `منذ ${diffInDays} يوم`;
  }
  
  const diffInMonths = Math.floor(diffInDays / 30);
  if (diffInMonths < 12) {
    if (diffInMonths === 1) return 'منذ شهر';
    if (diffInMonths === 2) return 'منذ شهرين';
    if (diffInMonths <= 10) return `منذ ${diffInMonths} أشهر`;
    return `منذ ${diffInMonths} شهر`;
  }
  
  const diffInYears = Math.floor(diffInMonths / 12);
  if (diffInYears === 1) return 'منذ سنة';
  if (diffInYears === 2) return 'منذ سنتين';
  if (diffInYears <= 10) return `منذ ${diffInYears} سنوات`;
  return `منذ ${diffInYears} سنة`;
};

// Get day name in Arabic
export const getArabicDayName = (date: Date | string): string => {
  const d = typeof date === 'string' ? new Date(date) : date;
  return arabicDays[d.getDay()];
};

// Get month name in Arabic
export const getArabicMonthName = (monthIndex: number): string => {
  return arabicMonths[monthIndex] || '';
};

// Check if date is today
export const isToday = (date: Date | string): boolean => {
  const d = typeof date === 'string' ? new Date(date) : date;
  const today = new Date();
  return (
    d.getDate() === today.getDate() &&
    d.getMonth() === today.getMonth() &&
    d.getFullYear() === today.getFullYear()
  );
};

// Check if date is yesterday
export const isYesterday = (date: Date | string): boolean => {
  const d = typeof date === 'string' ? new Date(date) : date;
  const yesterday = new Date();
  yesterday.setDate(yesterday.getDate() - 1);
  return (
    d.getDate() === yesterday.getDate() &&
    d.getMonth() === yesterday.getMonth() &&
    d.getFullYear() === yesterday.getFullYear()
  );
};

// Check if date is this week
export const isThisWeek = (date: Date | string): boolean => {
  const d = typeof date === 'string' ? new Date(date) : date;
  const now = new Date();
  const weekStart = new Date(now.getFullYear(), now.getMonth(), now.getDate() - now.getDay());
  const weekEnd = new Date(now.getFullYear(), now.getMonth(), now.getDate() + (6 - now.getDay()));
  return d >= weekStart && d <= weekEnd;
};

// Add days to date
export const addDays = (date: Date | string, days: number): Date => {
  const d = typeof date === 'string' ? new Date(date) : new Date(date);
  d.setDate(d.getDate() + days);
  return d;
};

// Add months to date
export const addMonths = (date: Date | string, months: number): Date => {
  const d = typeof date === 'string' ? new Date(date) : new Date(date);
  d.setMonth(d.getMonth() + months);
  return d;
};

// Add years to date
export const addYears = (date: Date | string, years: number): Date => {
  const d = typeof date === 'string' ? new Date(date) : new Date(date);
  d.setFullYear(d.getFullYear() + years);
  return d;
};

// Get date difference in days
export const getDaysDifference = (date1: Date | string, date2: Date | string): number => {
  const d1 = typeof date1 === 'string' ? new Date(date1) : date1;
  const d2 = typeof date2 === 'string' ? new Date(date2) : date2;
  const diffInTime = Math.abs(d2.getTime() - d1.getTime());
  return Math.ceil(diffInTime / (1000 * 60 * 60 * 24));
};

// Get start of day
export const getStartOfDay = (date: Date | string): Date => {
  const d = typeof date === 'string' ? new Date(date) : new Date(date);
  d.setHours(0, 0, 0, 0);
  return d;
};

// Get end of day
export const getEndOfDay = (date: Date | string): Date => {
  const d = typeof date === 'string' ? new Date(date) : new Date(date);
  d.setHours(23, 59, 59, 999);
  return d;
};

// Get start of month
export const getStartOfMonth = (date: Date | string): Date => {
  const d = typeof date === 'string' ? new Date(date) : new Date(date);
  return new Date(d.getFullYear(), d.getMonth(), 1);
};

// Get end of month
export const getEndOfMonth = (date: Date | string): Date => {
  const d = typeof date === 'string' ? new Date(date) : new Date(date);
  return new Date(d.getFullYear(), d.getMonth() + 1, 0);
};

// Get start of year
export const getStartOfYear = (date: Date | string): Date => {
  const d = typeof date === 'string' ? new Date(date) : new Date(date);
  return new Date(d.getFullYear(), 0, 1);
};

// Get end of year
export const getEndOfYear = (date: Date | string): Date => {
  const d = typeof date === 'string' ? new Date(date) : new Date(date);
  return new Date(d.getFullYear(), 11, 31);
};

// Format date range
export const formatDateRange = (startDate: Date | string, endDate: Date | string): string => {
  const start = typeof startDate === 'string' ? new Date(startDate) : startDate;
  const end = typeof endDate === 'string' ? new Date(endDate) : endDate;
  
  if (start.getFullYear() === end.getFullYear()) {
    if (start.getMonth() === end.getMonth()) {
      if (start.getDate() === end.getDate()) {
        return formatArabicDate(start);
      }
      return `${start.getDate()} - ${end.getDate()} ${arabicMonths[start.getMonth()]} ${start.getFullYear()}`;
    }
    return `${start.getDate()} ${arabicMonths[start.getMonth()]} - ${end.getDate()} ${arabicMonths[end.getMonth()]} ${start.getFullYear()}`;
  }
  
  return `${formatArabicDate(start)} - ${formatArabicDate(end)}`;
};

// Parse Arabic date string
export const parseArabicDate = (dateString: string): Date | null => {
  try {
    // Try parsing different formats
    const patterns = [
      /^(\d{1,2})\s+(\S+)\s+(\d{4})$/,  // 15 يناير 2024
      /^(\d{4})-(\d{2})-(\d{2})$/,        // 2024-01-15
      /^(\d{1,2})\/(\d{1,2})\/(\d{4})$/,  // 15/01/2024
    ];
    
    for (const pattern of patterns) {
      const match = dateString.match(pattern);
      if (match) {
        if (pattern === patterns[0]) {
          const day = parseInt(match[1]);
          const monthIndex = arabicMonths.indexOf(match[2]);
          const year = parseInt(match[3]);
          if (monthIndex !== -1) {
            return new Date(year, monthIndex, day);
          }
        } else if (pattern === patterns[1]) {
          return new Date(match[0]);
        } else if (pattern === patterns[2]) {
          const day = parseInt(match[1]);
          const month = parseInt(match[2]) - 1;
          const year = parseInt(match[3]);
          return new Date(year, month, day);
        }
      }
    }
    
    // Try native parsing as fallback
    const date = new Date(dateString);
    return isNaN(date.getTime()) ? null : date;
  } catch {
    return null;
  }
};