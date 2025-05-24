import dayjs from 'dayjs';
import timezone from 'dayjs/plugin/timezone';
import utc from 'dayjs/plugin/utc';
import relativeTime from 'dayjs/plugin/relativeTime';
import customParseFormat from 'dayjs/plugin/customParseFormat';
import isBetween from 'dayjs/plugin/isBetween';
import isSameOrBefore from 'dayjs/plugin/isSameOrBefore';
import isSameOrAfter from 'dayjs/plugin/isSameOrAfter';
import duration from 'dayjs/plugin/duration';
import 'dayjs/locale/ar';

// تفعيل الإضافات
dayjs.extend(timezone);
dayjs.extend(utc);
dayjs.extend(relativeTime);
dayjs.extend(customParseFormat);
dayjs.extend(isBetween);
dayjs.extend(isSameOrBefore);
dayjs.extend(isSameOrAfter);
dayjs.extend(duration);

// تعيين اللغة العربية والمنطقة الزمنية السعودية
dayjs.locale('ar');
const DEFAULT_TIMEZONE = 'Asia/Riyadh';

// دالة للحصول على التاريخ الحالي بالتوقيت السعودي
export const now = (): dayjs.Dayjs => {
  return dayjs().tz(DEFAULT_TIMEZONE);
};

// دالة لتحويل التاريخ إلى التوقيت السعودي
export const toSaudiTime = (date: string | Date | dayjs.Dayjs): dayjs.Dayjs => {
  return dayjs(date).tz(DEFAULT_TIMEZONE);
};

// دالة لتنسيق التاريخ
export const formatDate = (
  date: string | Date | dayjs.Dayjs,
  format: string = 'YYYY-MM-DD'
): string => {
  return toSaudiTime(date).format(format);
};

// دالة لتنسيق التاريخ والوقت
export const formatDateTime = (
  date: string | Date | dayjs.Dayjs,
  format: string = 'YYYY-MM-DD HH:mm:ss'
): string => {
  return toSaudiTime(date).format(format);
};

// دالة لتنسيق التاريخ بصيغة نسبية (منذ...)
export const formatRelative = (date: string | Date | dayjs.Dayjs): string => {
  return toSaudiTime(date).fromNow();
};

// دالة للحصول على بداية اليوم
export const startOfDay = (date?: string | Date | dayjs.Dayjs): dayjs.Dayjs => {
  return toSaudiTime(date || now()).startOf('day');
};

// دالة للحصول على نهاية اليوم
export const endOfDay = (date?: string | Date | dayjs.Dayjs): dayjs.Dayjs => {
  return toSaudiTime(date || now()).endOf('day');
};

// دالة للحصول على بداية الشهر
export const startOfMonth = (date?: string | Date | dayjs.Dayjs): dayjs.Dayjs => {
  return toSaudiTime(date || now()).startOf('month');
};

// دالة للحصول على نهاية الشهر
export const endOfMonth = (date?: string | Date | dayjs.Dayjs): dayjs.Dayjs => {
  return toSaudiTime(date || now()).endOf('month');
};

// دالة للحصول على بداية السنة
export const startOfYear = (date?: string | Date | dayjs.Dayjs): dayjs.Dayjs => {
  return toSaudiTime(date || now()).startOf('year');
};

// دالة للحصول على نهاية السنة
export const endOfYear = (date?: string | Date | dayjs.Dayjs): dayjs.Dayjs => {
  return toSaudiTime(date || now()).endOf('year');
};

// دالة لإضافة أيام
export const addDays = (
  date: string | Date | dayjs.Dayjs,
  days: number
): dayjs.Dayjs => {
  return toSaudiTime(date).add(days, 'day');
};

// دالة لإضافة شهور
export const addMonths = (
  date: string | Date | dayjs.Dayjs,
  months: number
): dayjs.Dayjs => {
  return toSaudiTime(date).add(months, 'month');
};

// دالة لإضافة سنوات
export const addYears = (
  date: string | Date | dayjs.Dayjs,
  years: number
): dayjs.Dayjs => {
  return toSaudiTime(date).add(years, 'year');
};

// دالة لطرح أيام
export const subtractDays = (
  date: string | Date | dayjs.Dayjs,
  days: number
): dayjs.Dayjs => {
  return toSaudiTime(date).subtract(days, 'day');
};

// دالة لطرح شهور
export const subtractMonths = (
  date: string | Date | dayjs.Dayjs,
  months: number
): dayjs.Dayjs => {
  return toSaudiTime(date).subtract(months, 'month');
};

// دالة لحساب الفرق بين تاريخين
export const diffInDays = (
  date1: string | Date | dayjs.Dayjs,
  date2: string | Date | dayjs.Dayjs
): number => {
  return toSaudiTime(date1).diff(toSaudiTime(date2), 'day');
};

// دالة لحساب الفرق بين تاريخين بالشهور
export const diffInMonths = (
  date1: string | Date | dayjs.Dayjs,
  date2: string | Date | dayjs.Dayjs
): number => {
  return toSaudiTime(date1).diff(toSaudiTime(date2), 'month');
};

// دالة للتحقق من أن التاريخ في الماضي
export const isPast = (date: string | Date | dayjs.Dayjs): boolean => {
  return toSaudiTime(date).isBefore(now());
};

// دالة للتحقق من أن التاريخ في المستقبل
export const isFuture = (date: string | Date | dayjs.Dayjs): boolean => {
  return toSaudiTime(date).isAfter(now());
};

// دالة للتحقق من أن التاريخ اليوم
export const isToday = (date: string | Date | dayjs.Dayjs): boolean => {
  return toSaudiTime(date).isSame(now(), 'day');
};

// دالة للتحقق من أن التاريخ هذا الشهر
export const isThisMonth = (date: string | Date | dayjs.Dayjs): boolean => {
  return toSaudiTime(date).isSame(now(), 'month');
};

// دالة للتحقق من أن التاريخ هذه السنة
export const isThisYear = (date: string | Date | dayjs.Dayjs): boolean => {
  return toSaudiTime(date).isSame(now(), 'year');
};

// دالة للتحقق من أن التاريخ بين تاريخين
export const isBetweenDates = (
  date: string | Date | dayjs.Dayjs,
  startDate: string | Date | dayjs.Dayjs,
  endDate: string | Date | dayjs.Dayjs,
  inclusive: '()' | '[]' | '[)' | '(]' = '[]'
): boolean => {
  return toSaudiTime(date).isBetween(
    toSaudiTime(startDate),
    toSaudiTime(endDate),
    null,
    inclusive
  );
};

// دالة للحصول على أيام العمل بين تاريخين
export const getWorkingDays = (
  startDate: string | Date | dayjs.Dayjs,
  endDate: string | Date | dayjs.Dayjs
): number => {
  let count = 0;
  let current = toSaudiTime(startDate);
  const end = toSaudiTime(endDate);
  
  while (current.isSameOrBefore(end)) {
    const dayOfWeek = current.day();
    // الجمعة = 5، السبت = 6 (عطلة نهاية الأسبوع في السعودية)
    if (dayOfWeek !== 5 && dayOfWeek !== 6) {
      count++;
    }
    current = current.add(1, 'day');
  }
  
  return count;
};

// دالة للحصول على التاريخ التالي لدفعة
export const getNextPaymentDate = (
  startDate: string | Date | dayjs.Dayjs,
  dayOfMonth: number
): dayjs.Dayjs => {
  let nextDate = toSaudiTime(startDate);
  
  // إذا كان اليوم المحدد قد مر في الشهر الحالي
  if (nextDate.date() > dayOfMonth) {
    nextDate = nextDate.add(1, 'month');
  }
  
  // تعيين اليوم المحدد
  nextDate = nextDate.date(dayOfMonth);
  
  // التعامل مع الأشهر التي لا تحتوي على اليوم المحدد
  if (nextDate.date() !== dayOfMonth) {
    nextDate = nextDate.endOf('month');
  }
  
  return nextDate;
};

// دالة لحساب العمر بالسنوات
export const calculateAge = (birthDate: string | Date | dayjs.Dayjs): number => {
  return now().diff(toSaudiTime(birthDate), 'year');
};

// دالة لتنسيق المدة
export const formatDuration = (
  seconds: number,
  format: 'short' | 'long' = 'short'
): string => {
  const duration = dayjs.duration(seconds, 'seconds');
  
  if (format === 'short') {
    if (duration.days() > 0) {
      return `${duration.days()}ي ${duration.hours()}س`;
    } else if (duration.hours() > 0) {
      return `${duration.hours()}س ${duration.minutes()}د`;
    } else if (duration.minutes() > 0) {
      return `${duration.minutes()}د ${duration.seconds()}ث`;
    } else {
      return `${duration.seconds()}ث`;
    }
  } else {
    const parts = [];
    if (duration.days() > 0) parts.push(`${duration.days()} يوم`);
    if (duration.hours() > 0) parts.push(`${duration.hours()} ساعة`);
    if (duration.minutes() > 0) parts.push(`${duration.minutes()} دقيقة`);
    if (duration.seconds() > 0 || parts.length === 0) {
      parts.push(`${duration.seconds()} ثانية`);
    }
    return parts.join(' و ');
  }
};

// دالة للحصول على نطاق تواريخ
export const getDateRange = (
  rangeType: 'today' | 'yesterday' | 'thisWeek' | 'lastWeek' | 
             'thisMonth' | 'lastMonth' | 'thisYear' | 'lastYear' | 
             'last7Days' | 'last30Days' | 'last90Days'
): { start: dayjs.Dayjs; end: dayjs.Dayjs } => {
  const today = now();
  
  switch (rangeType) {
    case 'today':
      return { start: startOfDay(today), end: endOfDay(today) };
    
    case 'yesterday':
      const yesterday = today.subtract(1, 'day');
      return { start: startOfDay(yesterday), end: endOfDay(yesterday) };
    
    case 'thisWeek':
      return { start: today.startOf('week'), end: today.endOf('week') };
    
    case 'lastWeek':
      const lastWeek = today.subtract(1, 'week');
      return { start: lastWeek.startOf('week'), end: lastWeek.endOf('week') };
    
    case 'thisMonth':
      return { start: startOfMonth(today), end: endOfMonth(today) };
    
    case 'lastMonth':
      const lastMonth = today.subtract(1, 'month');
      return { start: startOfMonth(lastMonth), end: endOfMonth(lastMonth) };
    
    case 'thisYear':
      return { start: startOfYear(today), end: endOfYear(today) };
    
    case 'lastYear':
      const lastYear = today.subtract(1, 'year');
      return { start: startOfYear(lastYear), end: endOfYear(lastYear) };
    
    case 'last7Days':
      return { start: startOfDay(today.subtract(7, 'day')), end: endOfDay(today) };
    
    case 'last30Days':
      return { start: startOfDay(today.subtract(30, 'day')), end: endOfDay(today) };
    
    case 'last90Days':
      return { start: startOfDay(today.subtract(90, 'day')), end: endOfDay(today) };
    
    default:
      return { start: startOfDay(today), end: endOfDay(today) };
  }
};

// دالة للتحقق من صحة التاريخ
export const isValidDate = (date: string, format?: string): boolean => {
  if (format) {
    return dayjs(date, format, true).isValid();
  }
  return dayjs(date).isValid();
};

// دالة لتحويل التاريخ إلى ISO string
export const toISOString = (date: string | Date | dayjs.Dayjs): string => {
  return toSaudiTime(date).toISOString();
};

// دالة لتحويل التاريخ إلى Unix timestamp
export const toUnixTimestamp = (date: string | Date | dayjs.Dayjs): number => {
  return toSaudiTime(date).unix();
};

// دالة للحصول على أول يوم عمل في الشهر
export const getFirstWorkingDayOfMonth = (
  date?: string | Date | dayjs.Dayjs
): dayjs.Dayjs => {
  let firstDay = startOfMonth(date);
  
  // التحقق من أن اليوم ليس جمعة أو سبت
  while (firstDay.day() === 5 || firstDay.day() === 6) {
    firstDay = firstDay.add(1, 'day');
  }
  
  return firstDay;
};

// دالة للحصول على آخر يوم عمل في الشهر
export const getLastWorkingDayOfMonth = (
  date?: string | Date | dayjs.Dayjs
): dayjs.Dayjs => {
  let lastDay = endOfMonth(date);
  
  // التحقق من أن اليوم ليس جمعة أو سبت
  while (lastDay.day() === 5 || lastDay.day() === 6) {
    lastDay = lastDay.subtract(1, 'day');
  }
  
  return lastDay;
};

// دالة لحساب تاريخ الاستحقاق
export const calculateDueDate = (
  startDate: string | Date | dayjs.Dayjs,
  termDays: number,
  skipWeekends: boolean = true
): dayjs.Dayjs => {
  let dueDate = toSaudiTime(startDate);
  
  if (skipWeekends) {
    let daysAdded = 0;
    while (daysAdded < termDays) {
      dueDate = dueDate.add(1, 'day');
      if (dueDate.day() !== 5 && dueDate.day() !== 6) {
        daysAdded++;
      }
    }
  } else {
    dueDate = dueDate.add(termDays, 'day');
  }
  
  return dueDate;
};

// دالة للحصول على الربع السنوي
export const getQuarter = (date?: string | Date | dayjs.Dayjs): number => {
  const month = toSaudiTime(date || now()).month();
  return Math.floor(month / 3) + 1;
};

// دالة للحصول على بداية الربع السنوي
export const startOfQuarter = (date?: string | Date | dayjs.Dayjs): dayjs.Dayjs => {
  const d = toSaudiTime(date || now());
  const quarter = getQuarter(d);
  const startMonth = (quarter - 1) * 3;
  return d.month(startMonth).startOf('month');
};

// دالة للحصول على نهاية الربع السنوي
export const endOfQuarter = (date?: string | Date | dayjs.Dayjs): dayjs.Dayjs => {
  const d = toSaudiTime(date || now());
  const quarter = getQuarter(d);
  const endMonth = quarter * 3 - 1;
  return d.month(endMonth).endOf('month');
};

// تصدير dayjs للاستخدام المباشر
export { dayjs };