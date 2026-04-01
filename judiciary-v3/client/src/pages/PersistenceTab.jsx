import { useState, useEffect, useCallback, useMemo } from 'react';
import { fetchPersistence, refreshPersistence, exportExcel, exportPdf } from '../api';
import { PERSISTENCE_COLORS } from '../utils/constants';
import { formatDate, downloadFile, formatNumber } from '../utils/helpers';
import toast from 'react-hot-toast';

const PAGE_SIZES = [10, 25, 50, 100];

function pageNumbers(current, total) {
  if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);
  const pages = [1];
  const lo = Math.max(2, current - 1);
  const hi = Math.min(total - 1, current + 1);
  if (lo > 2) pages.push('…');
  for (let i = lo; i <= hi; i++) pages.push(i);
  if (hi < total - 1) pages.push('…');
  if (total > 1) pages.push(total);
  return pages;
}

function Spinner({ className = 'w-4 h-4' }) {
  return <span className={`${className} border-2 border-current/30 border-t-current rounded-full animate-spin inline-block`} />;
}

const STAT_CARDS = [
  { key: 'total', label: 'إجمالي القضايا', iconBg: 'bg-emerald-100', iconColor: 'text-emerald-600',
    icon: <path strokeLinecap="round" strokeLinejoin="round" d="M12 3v17.25m0 0c-1.472 0-2.882.265-4.185.75M12 20.25c1.472 0 2.882.265 4.185.75M18.75 4.97A48.416 48.416 0 0012 4.5c-2.291 0-4.545.16-6.75.47m13.5 0c1.01.143 2.01.317 3 .52m-3-.52l2.62 10.726c.122.499-.106 1.028-.589 1.202a5.988 5.988 0 01-2.031.352 5.988 5.988 0 01-2.031-.352c-.483-.174-.711-.703-.59-1.202L18.75 4.971zm-16.5.52c.99-.203 1.99-.377 3-.52m0 0l2.62 10.726c.122.499-.106 1.028-.589 1.202a5.989 5.989 0 01-2.031.352 5.989 5.989 0 01-2.031-.352c-.483-.174-.711-.703-.59-1.202L5.25 4.971z" /> },
  { key: 'red', label: 'تحتاج اهتمام عاجل', iconBg: 'bg-red-100', iconColor: 'text-red-600',
    icon: <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /> },
  { key: 'orange', label: 'تقترب المواعيد', iconBg: 'bg-amber-100', iconColor: 'text-amber-600',
    icon: <path strokeLinecap="round" strokeLinejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /> },
  { key: 'green', label: 'وضع جيد', iconBg: 'bg-green-100', iconColor: 'text-green-600',
    icon: <path strokeLinecap="round" strokeLinejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /> },
];

const COLOR_CHIPS = [
  { key: '', label: 'الكل', idle: 'bg-gray-100 text-gray-700 hover:bg-gray-200', active: 'bg-gray-700 text-white shadow-sm' },
  { key: 'red', label: 'عاجل', idle: 'bg-red-50 text-red-700 hover:bg-red-100', active: 'bg-red-600 text-white shadow-sm' },
  { key: 'orange', label: 'يقترب', idle: 'bg-orange-50 text-orange-700 hover:bg-orange-100', active: 'bg-orange-500 text-white shadow-sm' },
  { key: 'green', label: 'جيد', idle: 'bg-green-50 text-green-700 hover:bg-green-100', active: 'bg-green-600 text-white shadow-sm' },
];

const TABLE_HEADERS = [
  '#', 'رقم الدعوى', 'السنة', 'المحكمة', 'رقم العقد', 'اسم العميل',
  'آخر إجراء', 'تاريخ آخر إجراء', 'المثابرة', 'آخر متابعة عقد',
  'آخر فحص وظيفي', 'المحامي', 'الوظيفة', 'نوع الوظيفة',
];

export default function PersistenceTab() {
  const [allData, setAllData] = useState([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [search, setSearch] = useState('');
  const [colorFilter, setColorFilter] = useState('');
  const [showAll, setShowAll] = useState(false);
  const [page, setPage] = useState(1);
  const [pageSize, setPageSize] = useState(25);
  const [exporting, setExporting] = useState(null);

  const loadData = useCallback(async () => {
    setLoading(true);
    try {
      const res = await fetchPersistence({ pageSize: 99999 });
      setAllData(res.data || []);
    } catch {
      toast.error('فشل تحميل بيانات المثابرة');
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => { loadData(); }, [loadData]);

  const stats = useMemo(() => ({
    total: allData.length,
    red: allData.filter(r => r.color === 'red').length,
    orange: allData.filter(r => r.color === 'orange').length,
    green: allData.filter(r => r.color === 'green').length,
  }), [allData]);

  const filteredData = useMemo(() => {
    let result = allData;
    if (colorFilter) result = result.filter(r => r.color === colorFilter);
    if (search) {
      const term = search.toLowerCase();
      result = result.filter(r =>
        (r.customer_name || '').toLowerCase().includes(term) ||
        (r.court_name || '').toLowerCase().includes(term) ||
        (r.judiciary_number || '').toLowerCase().includes(term) ||
        String(r.contract_number || '').includes(term)
      );
    }
    return result;
  }, [allData, colorFilter, search]);

  const displayData = useMemo(() => {
    if (showAll) return filteredData;
    const start = (page - 1) * pageSize;
    return filteredData.slice(start, start + pageSize);
  }, [filteredData, showAll, page, pageSize]);

  const totalPages = Math.ceil(filteredData.length / pageSize) || 1;

  useEffect(() => { setPage(1); }, [search, colorFilter, pageSize]);

  const handleRefresh = async () => {
    setRefreshing(true);
    try {
      await refreshPersistence();
      toast.success('تم تحديث الكاش بنجاح');
      await loadData();
    } catch {
      toast.error('فشل تحديث الكاش');
    } finally {
      setRefreshing(false);
    }
  };

  const handleExport = async (type) => {
    setExporting(type);
    try {
      const blob = type === 'excel'
        ? await exportExcel('persistence', { search, color: colorFilter })
        : await exportPdf('persistence', { search, color: colorFilter });
      downloadFile(blob, `المثابرة.${type === 'excel' ? 'xlsx' : 'pdf'}`);
      toast.success('تم التصدير بنجاح');
    } catch { toast.error('فشل التصدير'); }
    finally { setExporting(null); }
  };

  const persistenceBadge = (color) => {
    const cfg = PERSISTENCE_COLORS[color];
    if (!cfg) return <span className="badge badge-neutral">—</span>;
    return (
      <span className={`badge ${cfg.badge}`}>
        <span className={`w-2 h-2 rounded-full ${cfg.bg} ml-1.5 shrink-0`} />
        {cfg.label}
      </span>
    );
  };

  return (
    <div className="space-y-5">
      {/* ── Dashboard Cards ── */}
      <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
        {STAT_CARDS.map(card => (
          <div key={card.key} className="stat-card">
            <div className={`stat-icon ${card.iconBg} shrink-0`}>
              <svg className={`w-6 h-6 ${card.iconColor}`} fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
                {card.icon}
              </svg>
            </div>
            <div className="min-w-0">
              {loading ? (
                <div className="skeleton h-7 w-16 rounded mb-1" />
              ) : (
                <p className="text-2xl font-bold text-gray-900 tabular-nums">{formatNumber(stats[card.key])}</p>
              )}
              <p className="text-sm text-gray-500 truncate">{card.label}</p>
            </div>
          </div>
        ))}
      </div>

      {/* ── Toolbar ── */}
      <div className="flex items-center justify-between flex-wrap gap-3">
        <h2 className="section-title">المثابرة والمتابعة</h2>
        <div className="flex items-center gap-2 flex-wrap">
          <button onClick={handleRefresh} disabled={refreshing} className="btn-primary">
            {refreshing ? <Spinner /> : (
              <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182" /></svg>
            )}
            تحديث الكاش
          </button>
          <button onClick={() => handleExport('excel')} disabled={!!exporting} className="btn-secondary">
            {exporting === 'excel' ? <Spinner /> : <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>}
            Excel
          </button>
          <button onClick={() => handleExport('pdf')} disabled={!!exporting} className="btn-secondary">
            {exporting === 'pdf' ? <Spinner /> : <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H6.75a2.25 2.25 0 00-2.25 2.25v13.5a2.25 2.25 0 002.25 2.25h10.5a2.25 2.25 0 002.25-2.25v-.75" /></svg>}
            PDF
          </button>
          <label className="btn-secondary cursor-pointer select-none gap-2">
            <input
              type="checkbox"
              checked={showAll}
              onChange={e => setShowAll(e.target.checked)}
              className="w-4 h-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
            />
            عرض الكل
          </label>
        </div>
      </div>

      {/* ── Filter Bar ── */}
      <div className="card">
        <div className="flex items-center gap-4 flex-wrap">
          <div className="relative flex-1 min-w-[220px] max-w-md">
            <svg className="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
            <input
              className="input pr-10"
              placeholder="بحث بالاسم، المحكمة، رقم الدعوى أو العقد..."
              value={search}
              onChange={e => setSearch(e.target.value)}
            />
          </div>
          <div className="flex items-center gap-2 flex-wrap">
            {COLOR_CHIPS.map(chip => (
              <button
                key={chip.key}
                onClick={() => setColorFilter(chip.key)}
                className={`px-3.5 py-1.5 rounded-full text-sm font-medium transition-all duration-200 ${
                  colorFilter === chip.key ? chip.active : chip.idle
                }`}
              >
                {chip.label}
                {chip.key && !loading && (
                  <span className="mr-1.5 opacity-80">({formatNumber(stats[chip.key] || 0)})</span>
                )}
              </button>
            ))}
          </div>
          <span className="text-sm text-gray-500 mr-auto">
            {formatNumber(filteredData.length)} نتيجة
          </span>
        </div>
      </div>

      {/* ── Table ── */}
      <div className="table-container">
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="table-header">
                {TABLE_HEADERS.map(h => (
                  <th key={h} className="px-3 py-3 text-right font-semibold text-gray-600 whitespace-nowrap">{h}</th>
                ))}
              </tr>
            </thead>
            <tbody>
              {loading ? (
                Array.from({ length: 8 }).map((_, i) => (
                  <tr key={i} className="table-row">
                    {Array.from({ length: 14 }).map((_, j) => (
                      <td key={j} className="px-3 py-3"><div className="skeleton h-4 w-full rounded" /></td>
                    ))}
                  </tr>
                ))
              ) : displayData.length === 0 ? (
                <tr>
                  <td colSpan={14} className="py-16">
                    <div className="empty-state">
                      <svg className="w-14 h-14 mb-3 text-gray-300" fill="none" viewBox="0 0 24 24" strokeWidth={1} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" /></svg>
                      <p className="text-gray-400 font-medium">لا توجد بيانات مثابرة</p>
                    </div>
                  </td>
                </tr>
              ) : displayData.map((row, idx) => {
                const rowNum = showAll ? idx + 1 : (page - 1) * pageSize + idx + 1;
                return (
                  <tr key={row.id || idx} className="table-row">
                    <td className="px-3 py-3 text-gray-400 tabular-nums">{rowNum}</td>
                    <td className="px-3 py-3 font-medium text-gray-900 whitespace-nowrap">{row.judiciary_number || '—'}</td>
                    <td className="px-3 py-3 text-gray-600 tabular-nums">{row.year || '—'}</td>
                    <td className="px-3 py-3 text-gray-600 whitespace-nowrap">{row.court_name || '—'}</td>
                    <td className="px-3 py-3 text-gray-600 tabular-nums">{row.contract_number || '—'}</td>
                    <td className="px-3 py-3 font-medium text-gray-900 whitespace-nowrap">{row.customer_name || '—'}</td>
                    <td className="px-3 py-3 text-gray-600 whitespace-nowrap">{row.last_action || '—'}</td>
                    <td className="px-3 py-3 text-gray-600 whitespace-nowrap">{formatDate(row.last_action_date)}</td>
                    <td className="px-3 py-3">{persistenceBadge(row.color)}</td>
                    <td className="px-3 py-3 text-gray-600 whitespace-nowrap">{formatDate(row.last_contract_follow_up)}</td>
                    <td className="px-3 py-3 text-gray-600 whitespace-nowrap">{formatDate(row.last_job_check)}</td>
                    <td className="px-3 py-3 text-gray-600 whitespace-nowrap">{row.lawyer_name || '—'}</td>
                    <td className="px-3 py-3 text-gray-600 whitespace-nowrap">{row.job_title || '—'}</td>
                    <td className="px-3 py-3 text-gray-600 whitespace-nowrap">{row.job_type || '—'}</td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        </div>

        {/* ── Pagination ── */}
        {!loading && !showAll && filteredData.length > 0 && (
          <div className="flex items-center justify-between px-4 py-3 border-t border-gray-100 flex-wrap gap-3">
            <div className="flex items-center gap-2 text-sm">
              <span className="text-gray-500">عرض</span>
              <select value={pageSize} onChange={e => setPageSize(+e.target.value)} className="select w-20 py-1.5">
                {PAGE_SIZES.map(s => <option key={s} value={s}>{s}</option>)}
              </select>
              <span className="text-gray-500">سجل</span>
            </div>
            <div className="flex items-center gap-1">
              <button disabled={page <= 1} onClick={() => setPage(page - 1)} className="btn-icon">
                <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
              </button>
              {pageNumbers(page, totalPages).map((p, i) =>
                p === '…'
                  ? <span key={`e${i}`} className="px-2 text-gray-400 select-none">…</span>
                  : <button key={p} onClick={() => setPage(p)} className={`w-9 h-9 rounded-lg text-sm font-medium transition-colors ${p === page ? 'bg-primary-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100'}`}>{p}</button>
              )}
              <button disabled={page >= totalPages} onClick={() => setPage(page + 1)} className="btn-icon">
                <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
              </button>
            </div>
            <span className="text-sm text-gray-500">
              صفحة {page} من {totalPages} ({formatNumber(filteredData.length)} سجل)
            </span>
          </div>
        )}
      </div>
    </div>
  );
}
