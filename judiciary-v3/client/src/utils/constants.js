export const STAGES = [
  { key: 'case_preparation', label: 'إعداد الملف' },
  { key: 'fee_payment', label: 'دفع الرسوم' },
  { key: 'case_registration', label: 'تسجيل الدعوى' },
  { key: 'notification', label: 'التبليغ' },
  { key: 'procedural_requests', label: 'الطلبات الإجرائية' },
  { key: 'correspondence', label: 'المراسلات' },
  { key: 'follow_up', label: 'المتابعة' },
  { key: 'payment_settlement', label: 'التسوية والسداد' },
  { key: 'case_closure', label: 'إغلاق القضية' },
  { key: 'general', label: 'عام' },
];

export const STAGE_LABELS = Object.fromEntries(STAGES.map(s => [s.key, s.label]));

export const CASE_STATUS_OPTIONS = [
  { value: 'open', label: 'مفتوحة' },
  { value: 'closed', label: 'مغلقة' },
  { value: 'suspended', label: 'معلقة' },
  { value: 'archived', label: 'مؤرشفة' },
];

export const STATUS_LABELS = {
  open: 'مفتوحة',
  closed: 'مغلقة',
  suspended: 'معلقة',
  archived: 'مؤرشفة',
};

export const STATUS_COLORS = {
  open: 'bg-green-100 text-green-800',
  closed: 'bg-gray-100 text-gray-800',
  suspended: 'bg-amber-100 text-amber-800',
  archived: 'bg-blue-100 text-blue-800',
};

export const ACTION_NATURE_CONFIG = {
  request: { label: 'طلب', color: 'bg-blue-100 text-blue-800', dot: 'bg-blue-500' },
  document: { label: 'مستند', color: 'bg-green-100 text-green-800', dot: 'bg-green-500' },
  doc_status: { label: 'حالة مستند', color: 'bg-orange-100 text-orange-800', dot: 'bg-orange-500' },
  process: { label: 'إجراء', color: 'bg-purple-100 text-purple-800', dot: 'bg-purple-500' },
};

export const REQUEST_STATUS_CONFIG = {
  printed: { label: 'مطبوع', color: 'bg-gray-100 text-gray-700' },
  submitted: { label: 'مقدّم', color: 'bg-blue-100 text-blue-700' },
  pending: { label: 'معلق', color: 'bg-amber-100 text-amber-800' },
  approved: { label: 'موافق عليه', color: 'bg-green-100 text-green-800' },
  rejected: { label: 'مرفوض', color: 'bg-red-100 text-red-800' },
};

export const ASSET_TYPE_LABELS = {
  vehicle: 'مركبة',
  real_estate: 'عقار',
  bank_account: 'حساب بنكي',
  salary: 'راتب',
  shares: 'أسهم',
  e_payment: 'دفع إلكتروني',
};

export const ASSET_STATUS_CONFIG = {
  seizure_requested: { label: 'طلب حجز', color: 'bg-amber-100 text-amber-800' },
  seized: { label: 'محجوز', color: 'bg-red-100 text-red-800' },
  valued: { label: 'مقيّم', color: 'bg-blue-100 text-blue-700' },
  auction_requested: { label: 'طلب مزاد', color: 'bg-purple-100 text-purple-800' },
  auctioned: { label: 'تم المزاد', color: 'bg-indigo-100 text-indigo-800' },
  released: { label: 'مفرج عنه', color: 'bg-green-100 text-green-800' },
};

export const DEADLINE_TYPE_LABELS = {
  registration_3wd: 'تسجيل (3 أيام عمل)',
  notification_check: 'فحص تبليغ',
  notification_16cd: 'تبليغ (16 يوم)',
  request_decision: 'قرار طلب',
  correspondence_10wd: 'مراسلة (10 أيام عمل)',
  property_7cd: 'ملكية (7 أيام)',
  salary_3m: 'راتب (3 أشهر)',
  custom: 'مخصص',
};

export const DEADLINE_STATUS_CONFIG = {
  pending: { label: 'قائم', color: 'bg-blue-100 text-blue-800' },
  approaching: { label: 'يقترب', color: 'bg-amber-100 text-amber-800' },
  expired: { label: 'متأخر', color: 'bg-red-100 text-red-800' },
  completed: { label: 'مكتمل', color: 'bg-green-100 text-green-800' },
};

export const CORRESPONDENCE_DIRECTION = {
  incoming: 'وارد',
  outgoing: 'صادر',
};

export const PERSISTENCE_COLORS = {
  red: { label: 'عاجل', bg: 'bg-red-500', text: 'text-red-700', badge: 'bg-red-100 text-red-800' },
  orange: { label: 'يقترب', bg: 'bg-orange-500', text: 'text-orange-700', badge: 'bg-orange-100 text-orange-800' },
  green: { label: 'جيد', bg: 'bg-green-500', text: 'text-green-700', badge: 'bg-green-100 text-green-800' },
};

const currentYear = new Date().getFullYear();
export const YEARS = Array.from(
  { length: currentYear - 2010 + 1 },
  (_, i) => currentYear - i
);
