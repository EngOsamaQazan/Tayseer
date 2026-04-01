import { useState, useEffect, useCallback, useRef } from 'react';
import { Link } from 'react-router-dom';
import {
  fetchDeadlines,
  fetchDeadlineStats,
  completeDeadline,
  deleteDeadline,
  createDeadline,
  syncDeadlines,
  exportExcel,
} from '../api';
import {
  DEADLINE_TYPE_LABELS,
  DEADLINE_STATUS_CONFIG,
} from '../utils/constants';
import { formatDate, formatDateShort, downloadFile, debounce } from '../utils/helpers';
import toast from 'react-hot-toast';

const DEADLINE_TYPES = [
  { value: 'all', label: 'الكل' },
  ...Object.entries(DEADLINE_TYPE_LABELS).map(([value, label]) => ({ value, label })),
];

const STATUS_OPTIONS = [
  { value: 'all', label: 'الكل' },
  { value: 'pending', label: 'قائم' },
  { value: 'approaching', label: 'يقترب' },
  { value: 'expired', label: 'متأخر' },
  { value: 'completed', label: 'مكتمل' },
];

function getDaysRemaining(dateStr) {
  if (!dateStr) return null;
  const target = new Date(dateStr);
  const now = new Date();
  now.setHours(0, 0, 0, 0);
  target.setHours(0, 0, 0, 0);
  return Math.ceil((target - now) / 86400000);
}

function StatCard({ count, label, icon, gradient, sub, onClick, active }) {
  return (
    <button
      onClick={onClick}
      className={`rounded-xl p-4 transition-all duration-200 hover:scale-[1.02] hover:shadow-lg text-right w-full
        ${active ? 'ring-2 ring-offset-2 ring-white shadow-lg scale-[1.02]' : ''}
        ${gradient}`}
    >
      <div className="flex items-center justify-between">
        <div className="min-w-0">
          <p className="text-2xl sm:text-3xl font-extrabold text-white tabular-nums">{count}</p>
          <p className="text-xs sm:text-sm font-semibold text-white/80 mt-0.5 truncate">{label}</p>
          {sub && <p className="text-[10px] text-white/60 mt-0.5">{sub}</p>}
        </div>
        <div className="w-10 h-10 sm:w-11 sm:h-11 rounded-xl flex items-center justify-center bg-white/20 flex-shrink-0">
          {icon}
        </div>
      </div>
    </button>
  );
}

function FilterPanel({ filters, setFilters, onSearch, onReset, loading }) {
  const [open, setOpen] = useState(false);
  const hasFilters = filters.search || filters.deadline_type !== 'all' ||
    filters.status !== 'all' || filters.date_from || filters.date_to;

  return (
    <div className="card !p-0 overflow-hidden">
      <button
        onClick={() => setOpen(!open)}
        className="w-full flex items-center justify-between px-5 py-3 hover:bg-gray-50 transition-colors"
      >
        <div className="flex items-center gap-2">
          <svg className="w-5 h-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
          </svg>
          <span className="font-bold text-sm text-gray-700">فلاتر البحث</span>
          {hasFilters && (
            <span className="badge bg-primary-100 text-primary-700">مفعّل</span>
          )}
        </div>
        <svg className={`w-4 h-4 text-gray-400 transition-transform duration-200 ${open ? 'rotate-180' : ''}`} fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
        </svg>
      </button>

      {open && (
        <div className="border-t border-gray-100 p-4 sm:p-5 bg-gray-50/50">
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
            <div>
              <label className="label">بحث</label>
              <div className="relative">
                <input
                  type="text"
                  className="input pr-9"
                  placeholder="رقم دعوى، عنوان، ملاحظة..."
                  value={filters.search}
                  onChange={(e) => setFilters(f => ({ ...f, search: e.target.value }))}
                  onKeyDown={(e) => e.key === 'Enter' && onSearch()}
                />
                <svg className="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
              </div>
            </div>

            <div>
              <label className="label">نوع الموعد</label>
              <select
                className="select"
                value={filters.deadline_type}
                onChange={(e) => setFilters(f => ({ ...f, deadline_type: e.target.value }))}
              >
                {DEADLINE_TYPES.map(t => (
                  <option key={t.value} value={t.value}>{t.label}</option>
                ))}
              </select>
            </div>

            <div>
              <label className="label">الحالة</label>
              <select
                className="select"
                value={filters.status}
                onChange={(e) => setFilters(f => ({ ...f, status: e.target.value }))}
              >
                {STATUS_OPTIONS.map(s => (
                  <option key={s.value} value={s.value}>{s.label}</option>
                ))}
              </select>
            </div>

            <div className="flex gap-2">
              <div className="flex-1">
                <label className="label">من تاريخ</label>
                <input
                  type="date"
                  className="input"
                  value={filters.date_from}
                  onChange={(e) => setFilters(f => ({ ...f, date_from: e.target.value }))}
                />
              </div>
              <div className="flex-1">
                <label className="label">إلى تاريخ</label>
                <input
                  type="date"
                  className="input"
                  value={filters.date_to}
                  onChange={(e) => setFilters(f => ({ ...f, date_to: e.target.value }))}
                />
              </div>
            </div>
          </div>

          <div className="flex items-center gap-2 mt-4 pt-3 border-t border-gray-200">
            <button onClick={onSearch} disabled={loading} className="btn-primary text-xs !py-2 !px-5">
              <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
              بحث
            </button>
            {hasFilters && (
              <button onClick={onReset} className="btn-secondary text-xs !py-2 !px-4">
                <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                مسح الفلاتر
              </button>
            )}
          </div>
        </div>
      )}
    </div>
  );
}

function ActionToolbar({ onRefresh, onExport, onAdd, onSync, loading, total }) {
  const [exporting, setExporting] = useState(false);
  const [syncing, setSyncing] = useState(false);

  const handleExport = async () => {
    setExporting(true);
    try {
      await onExport();
    } finally {
      setExporting(false);
    }
  };

  const handleSync = async () => {
    setSyncing(true);
    try {
      await onSync();
    } finally {
      setSyncing(false);
    }
  };

  return (
    <div className="flex flex-wrap items-center gap-2">
      <button onClick={onAdd} className="btn-primary text-xs !py-2">
        <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" /></svg>
        إضافة موعد
      </button>

      <button onClick={handleSync} disabled={syncing} className="btn-accent text-xs !py-2" title="ترحيل المواعيد من القضايا والإجراءات والمراسلات">
        {syncing ? (
          <svg className="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
        ) : (
          <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" /></svg>
        )}
        {syncing ? 'جاري المزامنة...' : 'مزامنة المواعيد'}
      </button>

      <button onClick={onRefresh} disabled={loading} className="btn-secondary text-xs !py-2">
        <svg className={`w-4 h-4 ${loading ? 'animate-spin' : ''}`} fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
        تحديث
      </button>

      <button onClick={handleExport} disabled={exporting} className="btn-secondary text-xs !py-2">
        {exporting ? (
          <svg className="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
        ) : (
          <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
        )}
        تصدير Excel
      </button>

      {total > 0 && (
        <span className="text-xs text-gray-500 mr-auto">
          الإجمالي: <span className="font-bold text-gray-700">{total}</span> موعد
        </span>
      )}
    </div>
  );
}

function DeadlineItem({ item, onComplete, onDelete }) {
  const [acting, setActing] = useState(false);
  const [menuOpen, setMenuOpen] = useState(false);
  const menuRef = useRef(null);

  const typeLabel = DEADLINE_TYPE_LABELS[item.deadline_type] || item.deadline_type || '—';
  const statusCfg = DEADLINE_STATUS_CONFIG[item.status] || DEADLINE_STATUS_CONFIG.pending;
  const days = getDaysRemaining(item.deadline_date);
  const isOverdue = days !== null && days < 0;
  const isApproaching = days !== null && days >= 0 && days <= 7;
  const isCompleted = item.status === 'completed';

  let daysText = '';
  let daysColor = 'text-blue-600';
  let daysBg = 'bg-blue-50';
  if (days !== null && !isCompleted) {
    if (isOverdue) {
      daysText = `متأخر ${Math.abs(days)} يوم`;
      daysColor = 'text-red-700';
      daysBg = 'bg-red-50';
    } else if (days === 0) {
      daysText = 'اليوم!';
      daysColor = 'text-red-700';
      daysBg = 'bg-red-50';
    } else if (isApproaching) {
      daysText = `باقي ${days} يوم`;
      daysColor = 'text-amber-700';
      daysBg = 'bg-amber-50';
    } else {
      daysText = `باقي ${days} يوم`;
      daysColor = 'text-blue-700';
      daysBg = 'bg-blue-50';
    }
  }

  const borderColor = isCompleted
    ? 'border-r-green-400'
    : isOverdue
      ? 'border-r-red-400'
      : isApproaching
        ? 'border-r-amber-400'
        : 'border-r-blue-400';

  useEffect(() => {
    function handleClickOutside(e) {
      if (menuRef.current && !menuRef.current.contains(e.target)) setMenuOpen(false);
    }
    if (menuOpen) document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, [menuOpen]);

  const handleComplete = async () => {
    setActing(true);
    try {
      await onComplete(item.id);
    } finally {
      setActing(false);
    }
  };

  const handleDelete = async () => {
    setActing(true);
    try {
      await onDelete(item.id);
    } finally {
      setActing(false);
    }
  };

  return (
    <div className={`group bg-white rounded-lg border border-gray-100 border-r-4 ${borderColor}
      hover:shadow-md transition-all duration-200 ${isCompleted ? 'opacity-60' : ''} ${acting ? 'pointer-events-none opacity-50' : ''}`}>
      <div className="flex flex-col sm:flex-row sm:items-start gap-3 p-4">
        <div className="flex-1 min-w-0">
          <div className="flex flex-wrap items-center gap-2 mb-2">
            <span className={`badge ${statusCfg.color}`}>{statusCfg.label}</span>
            <span className="badge bg-gray-100 text-gray-600">{typeLabel}</span>
            {item.day_type === 'working' && (
              <span className="badge bg-indigo-50 text-indigo-600">أيام عمل</span>
            )}
          </div>

          {item.label && (
            <p className={`text-sm font-bold mb-1.5 ${isCompleted ? 'line-through text-gray-400' : 'text-gray-900'}`}>
              {item.label}
            </p>
          )}

          <div className="flex flex-wrap items-center gap-x-4 gap-y-1.5 text-xs text-gray-500">
            {item.judiciary_id && (
              <Link
                to={`/case/${item.judiciary_id}`}
                className="flex items-center gap-1 text-primary-600 hover:text-primary-700 font-semibold hover:underline"
              >
                <svg className="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                قضية #{item.judiciary_number || item.judiciary_id}
              </Link>
            )}
            {item.party_name && (
              <span className="flex items-center gap-1 text-gray-600">
                <svg className="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                {item.party_name}
              </span>
            )}
            <span className="flex items-center gap-1">
              <svg className="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
              {formatDate(item.deadline_date)}
            </span>
            {item.start_date && (
              <span className="flex items-center gap-1 text-gray-400">
                <svg className="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg>
                بدأ {formatDateShort(item.start_date)}
              </span>
            )}
          </div>

          {item.notes && (
            <p className="text-xs text-gray-500 mt-2 leading-relaxed bg-gray-50 rounded-md px-2.5 py-1.5 border border-gray-100">
              {item.notes}
            </p>
          )}
        </div>

        <div className="flex sm:flex-col items-center sm:items-end gap-2 shrink-0">
          {daysText && (
            <span className={`text-xs font-extrabold px-2.5 py-1 rounded-lg ${daysColor} ${daysBg} whitespace-nowrap`}>
              {daysText}
            </span>
          )}

          <div className="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
            {!isCompleted && (
              <button
                onClick={handleComplete}
                title="إنجاز"
                className="p-1.5 rounded-lg text-green-600 hover:bg-green-50 transition-colors"
              >
                <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" /></svg>
              </button>
            )}

            <div className="relative" ref={menuRef}>
              <button
                onClick={() => setMenuOpen(!menuOpen)}
                className="p-1.5 rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-colors"
              >
                <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" /></svg>
              </button>
              {menuOpen && (
                <div className="absolute left-0 sm:left-auto sm:right-0 top-full mt-1 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-20 min-w-[140px]">
                  {item.judiciary_id && (
                    <Link
                      to={`/case/${item.judiciary_id}`}
                      className="flex items-center gap-2 px-3 py-2 text-xs text-gray-700 hover:bg-gray-50"
                    >
                      <svg className="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                      عرض القضية
                    </Link>
                  )}
                  <button
                    onClick={handleDelete}
                    className="flex items-center gap-2 px-3 py-2 text-xs text-red-600 hover:bg-red-50 w-full"
                  >
                    <svg className="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                    حذف
                  </button>
                </div>
              )}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}

function DeadlineSection({ title, items, color, dotPulse, count, loading, onComplete, onDelete }) {
  const [collapsed, setCollapsed] = useState(false);
  const colorMap = {
    red: { bg: 'bg-red-50', border: 'border-red-100', dot: 'bg-red-500', title: 'text-red-800', badge: 'bg-red-100 text-red-700' },
    amber: { bg: 'bg-amber-50', border: 'border-amber-100', dot: 'bg-amber-500', title: 'text-amber-800', badge: 'bg-amber-100 text-amber-700' },
    blue: { bg: 'bg-blue-50', border: 'border-blue-100', dot: 'bg-blue-500', title: 'text-blue-800', badge: 'bg-blue-100 text-blue-700' },
    green: { bg: 'bg-green-50', border: 'border-green-100', dot: 'bg-green-500', title: 'text-green-800', badge: 'bg-green-100 text-green-700' },
  };
  const c = colorMap[color] || colorMap.blue;

  return (
    <div className="card !p-0 overflow-hidden">
      <button
        onClick={() => setCollapsed(!collapsed)}
        className={`w-full ${c.bg} border-b ${c.border} px-5 py-3 flex items-center gap-2 hover:brightness-95 transition-all`}
      >
        <span className={`w-2.5 h-2.5 rounded-full ${c.dot} ${dotPulse ? 'animate-pulse' : ''}`} />
        <h2 className={`font-bold ${c.title} text-sm`}>{title}</h2>
        {!loading && <span className={`badge ${c.badge} mx-1`}>{count}</span>}
        <svg className={`w-4 h-4 ${c.title} mr-auto transition-transform ${collapsed ? 'rotate-180' : ''}`} fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
        </svg>
      </button>

      {!collapsed && (
        <div className="p-3 sm:p-4 space-y-2">
          {loading ? (
            <SectionSkeleton />
          ) : items.length === 0 ? (
            <SectionEmpty text={`لا توجد ${title.toLowerCase()}`} />
          ) : (
            items.map((item, i) => (
              <DeadlineItem
                key={item.id || i}
                item={item}
                onComplete={onComplete}
                onDelete={onDelete}
              />
            ))
          )}
        </div>
      )}
    </div>
  );
}

function SectionSkeleton() {
  return (
    <div className="space-y-2">
      {[...Array(3)].map((_, i) => (
        <div key={i} className="bg-white rounded-lg border border-gray-100 p-4 space-y-3">
          <div className="flex gap-2">
            <div className="skeleton h-5 w-14 rounded-full" />
            <div className="skeleton h-5 w-20 rounded-full" />
          </div>
          <div className="skeleton h-4 w-48 rounded" />
          <div className="flex gap-4">
            <div className="skeleton h-3 w-24 rounded" />
            <div className="skeleton h-3 w-20 rounded" />
          </div>
        </div>
      ))}
    </div>
  );
}

function SectionEmpty({ text }) {
  return (
    <div className="flex flex-col items-center py-8 text-gray-400">
      <svg className="w-10 h-10 mb-2 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
      <p className="text-sm">{text}</p>
    </div>
  );
}

function AddDeadlineModal({ open, onClose, onSave }) {
  const [form, setForm] = useState({
    judiciary_id: '',
    deadline_type: 'custom',
    label: '',
    deadline_date: '',
    start_date: '',
    day_type: 'calendar',
    notes: '',
  });
  const [saving, setSaving] = useState(false);

  const handleSave = async () => {
    if (!form.deadline_date) {
      toast.error('يرجى تحديد تاريخ الموعد');
      return;
    }
    setSaving(true);
    try {
      await onSave(form);
      setForm({ judiciary_id: '', deadline_type: 'custom', label: '', deadline_date: '', start_date: '', day_type: 'calendar', notes: '' });
    } finally {
      setSaving(false);
    }
  };

  if (!open) return null;

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div className="fixed inset-0 bg-black/40 backdrop-blur-sm" onClick={onClose} />
      <div className="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div className="flex items-center justify-between p-5 border-b border-gray-100">
          <h3 className="font-bold text-gray-900">إضافة موعد جديد</h3>
          <button onClick={onClose} className="btn-icon">
            <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" /></svg>
          </button>
        </div>

        <div className="p-5 space-y-4">
          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className="label">رقم القضية (ID)</label>
              <input
                type="number"
                className="input"
                placeholder="مثال: 150"
                value={form.judiciary_id}
                onChange={(e) => setForm(f => ({ ...f, judiciary_id: e.target.value }))}
              />
            </div>
            <div>
              <label className="label">نوع الموعد</label>
              <select
                className="select"
                value={form.deadline_type}
                onChange={(e) => setForm(f => ({ ...f, deadline_type: e.target.value }))}
              >
                {Object.entries(DEADLINE_TYPE_LABELS).map(([v, l]) => (
                  <option key={v} value={v}>{l}</option>
                ))}
              </select>
            </div>
          </div>

          <div>
            <label className="label">العنوان</label>
            <input
              type="text"
              className="input"
              placeholder="وصف مختصر للموعد"
              value={form.label}
              onChange={(e) => setForm(f => ({ ...f, label: e.target.value }))}
            />
          </div>

          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className="label">تاريخ البدء</label>
              <input
                type="date"
                className="input"
                value={form.start_date}
                onChange={(e) => setForm(f => ({ ...f, start_date: e.target.value }))}
              />
            </div>
            <div>
              <label className="label">تاريخ الموعد *</label>
              <input
                type="date"
                className="input"
                value={form.deadline_date}
                onChange={(e) => setForm(f => ({ ...f, deadline_date: e.target.value }))}
              />
            </div>
          </div>

          <div>
            <label className="label">نوع الأيام</label>
            <div className="flex gap-3">
              <label className="flex items-center gap-2 text-sm cursor-pointer">
                <input type="radio" name="day_type" value="calendar"
                  checked={form.day_type === 'calendar'}
                  onChange={() => setForm(f => ({ ...f, day_type: 'calendar' }))}
                  className="text-primary-600 focus:ring-primary-500"
                />
                تقويمية
              </label>
              <label className="flex items-center gap-2 text-sm cursor-pointer">
                <input type="radio" name="day_type" value="working"
                  checked={form.day_type === 'working'}
                  onChange={() => setForm(f => ({ ...f, day_type: 'working' }))}
                  className="text-primary-600 focus:ring-primary-500"
                />
                عمل
              </label>
            </div>
          </div>

          <div>
            <label className="label">ملاحظات</label>
            <textarea
              className="input !h-20 resize-none"
              placeholder="ملاحظات إضافية..."
              value={form.notes}
              onChange={(e) => setForm(f => ({ ...f, notes: e.target.value }))}
            />
          </div>
        </div>

        <div className="flex items-center justify-end gap-2 p-5 border-t border-gray-100 bg-gray-50/50 rounded-b-2xl">
          <button onClick={onClose} className="btn-secondary text-xs">إلغاء</button>
          <button onClick={handleSave} disabled={saving} className="btn-primary text-xs">
            {saving ? 'جاري الحفظ...' : 'حفظ الموعد'}
          </button>
        </div>
      </div>
    </div>
  );
}

function Pagination({ page, pageSize, total, onChange }) {
  const totalPages = Math.ceil(total / pageSize);
  if (totalPages <= 1) return null;

  const pages = [];
  const maxVisible = 5;
  let start = Math.max(1, page - Math.floor(maxVisible / 2));
  let end = Math.min(totalPages, start + maxVisible - 1);
  if (end - start + 1 < maxVisible) start = Math.max(1, end - maxVisible + 1);

  for (let i = start; i <= end; i++) pages.push(i);

  return (
    <div className="flex items-center justify-center gap-1 mt-4">
      <button
        onClick={() => onChange(1)}
        disabled={page === 1}
        className="btn-icon !w-8 !h-8 text-xs disabled:opacity-30"
      >«</button>
      <button
        onClick={() => onChange(page - 1)}
        disabled={page === 1}
        className="btn-icon !w-8 !h-8 text-xs disabled:opacity-30"
      >‹</button>

      {start > 1 && <span className="text-xs text-gray-400 px-1">...</span>}

      {pages.map(p => (
        <button
          key={p}
          onClick={() => onChange(p)}
          className={`w-8 h-8 rounded-lg text-xs font-bold transition-colors ${
            p === page
              ? 'bg-primary-600 text-white'
              : 'text-gray-600 hover:bg-gray-100'
          }`}
        >{p}</button>
      ))}

      {end < totalPages && <span className="text-xs text-gray-400 px-1">...</span>}

      <button
        onClick={() => onChange(page + 1)}
        disabled={page === totalPages}
        className="btn-icon !w-8 !h-8 text-xs disabled:opacity-30"
      >›</button>
      <button
        onClick={() => onChange(totalPages)}
        disabled={page === totalPages}
        className="btn-icon !w-8 !h-8 text-xs disabled:opacity-30"
      >»</button>

      <span className="text-xs text-gray-400 mr-2">
        صفحة {page} من {totalPages}
      </span>
    </div>
  );
}

const DEFAULT_FILTERS = {
  search: '',
  deadline_type: 'all',
  status: 'all',
  date_from: '',
  date_to: '',
};

export default function DeadlineDashboard() {
  const [groups, setGroups] = useState({ overdue: [], approaching: [], pending: [], completed: [] });
  const [stats, setStats] = useState(null);
  const [loading, setLoading] = useState(true);
  const [filters, setFilters] = useState({ ...DEFAULT_FILTERS });
  const [activeFilters, setActiveFilters] = useState({ ...DEFAULT_FILTERS });
  const [page, setPage] = useState(1);
  const [total, setTotal] = useState(0);
  const [pageSize] = useState(100);
  const [addOpen, setAddOpen] = useState(false);
  const [activeCard, setActiveCard] = useState(null);

  const loadStats = useCallback(async () => {
    try {
      const s = await fetchDeadlineStats();
      setStats(s);
    } catch {}
  }, []);

  const loadData = useCallback(async (f = activeFilters, p = page) => {
    setLoading(true);
    try {
      const queryFilters = {};
      if (f.search) queryFilters.search = f.search;
      if (f.deadline_type !== 'all') queryFilters.deadline_type = f.deadline_type;
      if (f.status !== 'all') queryFilters.status = f.status;
      if (f.date_from) queryFilters.date_from = f.date_from;
      if (f.date_to) queryFilters.date_to = f.date_to;
      queryFilters.page = p;
      queryFilters.pageSize = pageSize;

      const result = await fetchDeadlines(null, queryFilters);

      if (result && result.data) {
        if (Array.isArray(result.data)) {
          const grouped = { overdue: [], approaching: [], pending: [], completed: [] };
          const today = new Date(); today.setHours(0, 0, 0, 0);
          const week = new Date(today); week.setDate(week.getDate() + 7);
          for (const row of result.data) {
            if (row.status === 'completed') grouped.completed.push(row);
            else {
              const dd = row.deadline_date ? new Date(row.deadline_date) : null;
              if (dd && dd < today) grouped.overdue.push(row);
              else if (dd && dd <= week) grouped.approaching.push(row);
              else grouped.pending.push(row);
            }
          }
          setGroups(grouped);
        } else {
          setGroups({
            overdue: result.data.overdue || [],
            approaching: result.data.approaching || [],
            pending: result.data.pending || [],
            completed: result.data.completed || [],
          });
        }
        setTotal(result.total || 0);
      } else {
        setGroups({ overdue: [], approaching: [], pending: [], completed: [] });
        setTotal(0);
      }
    } catch (err) {
      toast.error(err.message || 'فشل تحميل المواعيد');
    } finally {
      setLoading(false);
    }
  }, [activeFilters, page, pageSize]);

  useEffect(() => {
    loadStats();
    loadData();
  }, []);

  const handleSearch = () => {
    setActiveFilters({ ...filters });
    setPage(1);
    loadData(filters, 1);
  };

  const handleReset = () => {
    setFilters({ ...DEFAULT_FILTERS });
    setActiveFilters({ ...DEFAULT_FILTERS });
    setActiveCard(null);
    setPage(1);
    loadData(DEFAULT_FILTERS, 1);
  };

  const handleRefresh = () => {
    loadStats();
    loadData();
  };

  const handlePageChange = (newPage) => {
    setPage(newPage);
    loadData(activeFilters, newPage);
  };

  const handleCardClick = (statusFilter) => {
    if (activeCard === statusFilter) {
      handleReset();
      return;
    }
    setActiveCard(statusFilter);
    const newFilters = { ...DEFAULT_FILTERS, status: statusFilter };
    setFilters(newFilters);
    setActiveFilters(newFilters);
    setPage(1);
    loadData(newFilters, 1);
  };

  const handleExport = async () => {
    try {
      const queryFilters = {};
      if (activeFilters.search) queryFilters.search = activeFilters.search;
      if (activeFilters.deadline_type !== 'all') queryFilters.deadline_type = activeFilters.deadline_type;
      if (activeFilters.status !== 'all') queryFilters.status = activeFilters.status;
      if (activeFilters.date_from) queryFilters.date_from = activeFilters.date_from;
      if (activeFilters.date_to) queryFilters.date_to = activeFilters.date_to;

      const blob = await exportExcel('deadlines', queryFilters);
      downloadFile(blob, `deadlines_${new Date().toISOString().slice(0, 10)}.xlsx`);
      toast.success('تم التصدير بنجاح');
    } catch (err) {
      toast.error(err.message || 'فشل التصدير');
    }
  };

  const handleComplete = async (id) => {
    try {
      await completeDeadline(id);
      toast.success('تم إنجاز الموعد');
      handleRefresh();
    } catch (err) {
      toast.error(err.message || 'فشل تحديث الموعد');
    }
  };

  const handleDelete = async (id) => {
    if (!confirm('هل تريد حذف هذا الموعد؟')) return;
    try {
      await deleteDeadline(id);
      toast.success('تم حذف الموعد');
      handleRefresh();
    } catch (err) {
      toast.error(err.message || 'فشل الحذف');
    }
  };

  const handleSync = async () => {
    try {
      const result = await syncDeadlines();
      const d = result.data || result;
      const created = d.created || 0;
      if (created > 0) {
        toast.success(`تم ترحيل ${created} موعد جديد من القضايا والإجراءات`);
      } else {
        toast.success('جميع المواعيد مُرحّلة بالفعل - لا توجد مواعيد جديدة');
      }
      handleRefresh();
    } catch (err) {
      toast.error(err.message || 'فشل المزامنة');
    }
  };

  const handleAddSave = async (formData) => {
    try {
      await createDeadline(formData);
      toast.success('تم إضافة الموعد');
      setAddOpen(false);
      handleRefresh();
    } catch (err) {
      toast.error(err.message || 'فشل الإضافة');
    }
  };

  const allItems = [
    ...groups.overdue,
    ...groups.approaching,
    ...groups.pending,
    ...groups.completed,
  ];

  return (
    <div className="space-y-5">
      {/* Header + Actions */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <h1 className="section-title text-lg flex items-center gap-2">
          <svg className="w-6 h-6 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          لوحة المواعيد النهائية
        </h1>
        <ActionToolbar
          onRefresh={handleRefresh}
          onExport={handleExport}
          onAdd={() => setAddOpen(true)}
          onSync={handleSync}
          loading={loading}
          total={total}
        />
      </div>

      {/* Stats Cards */}
      <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        <StatCard
          count={stats ? stats.overdue : '—'}
          label="متأخرة"
          gradient="bg-gradient-to-bl from-red-500 to-rose-600"
          icon={<svg className="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z" /></svg>}
          sub={stats?.overdue > 0 ? 'تحتاج متابعة عاجلة' : null}
          onClick={() => handleCardClick('expired')}
          active={activeCard === 'expired'}
        />
        <StatCard
          count={stats ? stats.approaching : '—'}
          label="تقترب"
          gradient="bg-gradient-to-bl from-amber-400 to-orange-500"
          icon={<svg className="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>}
          sub="خلال 7 أيام"
          onClick={() => handleCardClick('approaching')}
          active={activeCard === 'approaching'}
        />
        <StatCard
          count={stats ? stats.pending : '—'}
          label="قائمة"
          gradient="bg-gradient-to-bl from-blue-500 to-indigo-600"
          icon={<svg className="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>}
          onClick={() => handleCardClick('pending')}
          active={activeCard === 'pending'}
        />
        <StatCard
          count={stats ? stats.completed : '—'}
          label="مكتملة"
          gradient="bg-gradient-to-bl from-emerald-500 to-green-600"
          icon={<svg className="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>}
          onClick={() => handleCardClick('completed')}
          active={activeCard === 'completed'}
        />
        <StatCard
          count={stats ? stats.today : '—'}
          label="اليوم"
          gradient="bg-gradient-to-bl from-purple-500 to-violet-600"
          icon={<svg className="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>}
        />
        <StatCard
          count={stats ? stats.cases_with_deadlines : '—'}
          label="قضايا مرتبطة"
          gradient="bg-gradient-to-bl from-cyan-500 to-teal-600"
          icon={<svg className="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>}
        />
      </div>

      {/* Filters */}
      <FilterPanel
        filters={filters}
        setFilters={setFilters}
        onSearch={handleSearch}
        onReset={handleReset}
        loading={loading}
      />

      {/* Deadline Sections */}
      {activeCard === 'completed' ? (
        <DeadlineSection
          title="المواعيد المكتملة"
          items={groups.completed}
          color="green"
          count={groups.completed.length}
          loading={loading}
          onComplete={handleComplete}
          onDelete={handleDelete}
        />
      ) : activeFilters.status !== 'all' ? (
        <DeadlineSection
          title={STATUS_OPTIONS.find(s => s.value === activeFilters.status)?.label || 'النتائج'}
          items={allItems}
          color={activeFilters.status === 'expired' ? 'red' : activeFilters.status === 'approaching' ? 'amber' : 'blue'}
          count={allItems.length}
          loading={loading}
          onComplete={handleComplete}
          onDelete={handleDelete}
        />
      ) : (
        <>
          <DeadlineSection
            title="المواعيد المتأخرة"
            items={groups.overdue}
            color="red"
            dotPulse
            count={groups.overdue.length}
            loading={loading}
            onComplete={handleComplete}
            onDelete={handleDelete}
          />
          <DeadlineSection
            title="المواعيد القريبة"
            items={groups.approaching}
            color="amber"
            count={groups.approaching.length}
            loading={loading}
            onComplete={handleComplete}
            onDelete={handleDelete}
          />
          <DeadlineSection
            title="المواعيد القائمة"
            items={groups.pending}
            color="blue"
            count={groups.pending.length}
            loading={loading}
            onComplete={handleComplete}
            onDelete={handleDelete}
          />
          {groups.completed.length > 0 && (
            <DeadlineSection
              title="المواعيد المكتملة"
              items={groups.completed}
              color="green"
              count={groups.completed.length}
              loading={loading}
              onComplete={handleComplete}
              onDelete={handleDelete}
            />
          )}
        </>
      )}

      {/* Pagination */}
      <Pagination
        page={page}
        pageSize={pageSize}
        total={total}
        onChange={handlePageChange}
      />

      {/* Add Modal */}
      <AddDeadlineModal
        open={addOpen}
        onClose={() => setAddOpen(false)}
        onSave={handleAddSave}
      />
    </div>
  );
}
