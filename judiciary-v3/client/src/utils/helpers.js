const arDateFormat = new Intl.DateTimeFormat('ar-SA', {
  year: 'numeric',
  month: 'long',
  day: 'numeric',
});

const arDateShort = new Intl.DateTimeFormat('ar-SA', {
  year: 'numeric',
  month: '2-digit',
  day: '2-digit',
});

const arCurrency = new Intl.NumberFormat('ar-SA', {
  style: 'currency',
  currency: 'SAR',
  minimumFractionDigits: 0,
  maximumFractionDigits: 2,
});

const arNumber = new Intl.NumberFormat('ar-SA');

export function formatDate(date) {
  if (!date) return '—';
  const d = typeof date === 'string' ? new Date(date) : date;
  if (isNaN(d.getTime())) return '—';
  return arDateFormat.format(d);
}

export function formatDateShort(date) {
  if (!date) return '—';
  const d = typeof date === 'string' ? new Date(date) : date;
  if (isNaN(d.getTime())) return '—';
  return arDateShort.format(d);
}

export function formatCurrency(amount) {
  if (amount == null) return '—';
  return arCurrency.format(amount);
}

export function formatNumber(num) {
  if (num == null) return '—';
  return arNumber.format(num);
}

export function getStatusColor(status) {
  const map = {
    active: 'bg-green-100 text-green-800',
    closed: 'bg-gray-100 text-gray-800',
    suspended: 'bg-amber-100 text-amber-800',
    archived: 'bg-blue-100 text-blue-800',
    pending: 'bg-yellow-100 text-yellow-800',
  };
  return map[status] || 'bg-gray-100 text-gray-700';
}

export function getNatureColor(nature) {
  const map = {
    إجراء: 'bg-blue-100 text-blue-800',
    طلب: 'bg-purple-100 text-purple-800',
    مذكرة: 'bg-amber-100 text-amber-800',
    حكم: 'bg-green-100 text-green-800',
    تبليغ: 'bg-cyan-100 text-cyan-800',
    جلسة: 'bg-indigo-100 text-indigo-800',
    استئناف: 'bg-orange-100 text-orange-800',
    اعتراض: 'bg-red-100 text-red-800',
  };
  return map[nature] || 'bg-gray-100 text-gray-700';
}

export function getPersistenceColor(indicator) {
  if (indicator === 'red' || indicator === 'danger') return 'bg-red-100 text-red-800';
  if (indicator === 'orange' || indicator === 'warning') return 'bg-orange-100 text-orange-800';
  if (indicator === 'green' || indicator === 'success') return 'bg-green-100 text-green-800';
  return 'bg-gray-100 text-gray-700';
}

const TIME_UNITS = [
  { label: 'سنة', labels: 'سنوات', threshold: 31536000000 },
  { label: 'شهر', labels: 'أشهر', threshold: 2592000000 },
  { label: 'يوم', labels: 'أيام', threshold: 86400000 },
  { label: 'ساعة', labels: 'ساعات', threshold: 3600000 },
  { label: 'دقيقة', labels: 'دقائق', threshold: 60000 },
];

export function timeAgo(date) {
  if (!date) return '';
  const d = typeof date === 'string' ? new Date(date) : date;
  const diff = Date.now() - d.getTime();
  if (diff < 60000) return 'الآن';

  for (const unit of TIME_UNITS) {
    const count = Math.floor(diff / unit.threshold);
    if (count >= 1) {
      const label = count === 1 ? unit.label : count <= 10 ? unit.labels : unit.label;
      return `منذ ${count} ${label}`;
    }
  }
  return formatDate(date);
}

export function downloadFile(blob, filename) {
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = filename;
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  URL.revokeObjectURL(url);
}

export function debounce(fn, delay = 300) {
  let timer;
  return (...args) => {
    clearTimeout(timer);
    timer = setTimeout(() => fn(...args), delay);
  };
}

export function buildQueryString(params) {
  if (!params) return '';
  const filtered = Object.entries(params).filter(
    ([, v]) => v !== undefined && v !== null && v !== ''
  );
  if (!filtered.length) return '';
  return '?' + new URLSearchParams(filtered).toString();
}
