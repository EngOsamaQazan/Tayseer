import { useState, useEffect, useCallback } from 'react';
import { Link } from 'react-router-dom';
import { fetchLegal, exportExcel } from '../api';
import { usePaginatedApi } from '../hooks/useApi';
import { formatDate, formatCurrency, downloadFile } from '../utils/helpers';
import toast from 'react-hot-toast';

const STATUS_MAP = {
  judiciary: { label: 'قضائي', cls: 'bg-red-100 text-red-800' },
  active: { label: 'نشط', cls: 'bg-green-100 text-green-800' },
  defaulted: { label: 'متعثر', cls: 'bg-amber-100 text-amber-800' },
  closed: { label: 'مغلق', cls: 'bg-gray-100 text-gray-800' },
};

function legalFetcher(filters, page, pageSize) {
  return fetchLegal({ ...filters, page, pageSize });
}

function SkeletonRows({ count = 6 }) {
  return Array.from({ length: count }, (_, i) => (
    <tr key={i} className="table-row">
      <td className="px-4 py-3"><div className="skeleton w-4 h-4 rounded" /></td>
      <td className="px-4 py-3"><div className="skeleton w-6 h-4" /></td>
      <td className="px-4 py-3"><div className="skeleton w-20 h-4" /></td>
      <td className="px-4 py-3"><div className="skeleton w-32 h-4" /></td>
      <td className="px-4 py-3"><div className="skeleton w-24 h-4" /></td>
      <td className="px-4 py-3"><div className="skeleton w-20 h-4" /></td>
      <td className="px-4 py-3"><div className="skeleton w-20 h-4" /></td>
      <td className="px-4 py-3"><div className="skeleton w-20 h-4" /></td>
      <td className="px-4 py-3"><div className="skeleton w-16 h-5 rounded-full" /></td>
    </tr>
  ));
}

export default function LegalTab() {
  const [selected, setSelected] = useState(new Set());

  const {
    data, total, loading, error, page, setPage, pageSize, filters, setFilters, refetch,
  } = usePaginatedApi(legalFetcher, {});

  const totalPages = Math.ceil(total / pageSize);
  const allSelected = data.length > 0 && selected.size === data.length;

  useEffect(() => {
    setSelected(new Set());
  }, [data]);

  const toggle = useCallback((id) => {
    setSelected((prev) => {
      const s = new Set(prev);
      s.has(id) ? s.delete(id) : s.add(id);
      return s;
    });
  }, []);

  const toggleAll = useCallback(() => {
    setSelected((prev) =>
      prev.size === data.length ? new Set() : new Set(data.map((r) => r.id))
    );
  }, [data]);

  const handleExport = async () => {
    try {
      const blob = await exportExcel('legal', filters);
      downloadFile(blob, 'عقود-قانونية.xlsx');
      toast.success('تم التصدير بنجاح');
    } catch {
      toast.error('فشل تصدير الملف');
    }
  };

  const buildPages = () => {
    if (totalPages <= 1) return [];
    const pages = [];
    const delta = 2;
    const left = Math.max(2, page - delta);
    const right = Math.min(totalPages - 1, page + delta);
    pages.push(1);
    if (left > 2) pages.push('…');
    for (let i = left; i <= right; i++) pages.push(i);
    if (right < totalPages - 1) pages.push('…');
    if (totalPages > 1) pages.push(totalPages);
    return pages;
  };

  const statusBadge = (status) => {
    const cfg = STATUS_MAP[status] || { label: status, cls: 'bg-gray-100 text-gray-700' };
    return <span className={`badge ${cfg.cls}`}>{cfg.label}</span>;
  };

  return (
    <div className="space-y-4">
      {/* Stats Bar */}
      <div className="flex items-center gap-3 flex-wrap">
        <div className="stat-card flex-1 min-w-[180px]">
          <div className="stat-icon bg-blue-100">
            <svg className="w-6 h-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
          </div>
          <div>
            <p className="text-sm text-gray-500">إجمالي العقود</p>
            <p className="text-xl font-bold text-gray-900">{loading ? '—' : total}</p>
          </div>
        </div>

        {selected.size > 0 && (
          <div className="stat-card min-w-[160px] border-primary-200 bg-primary-50/40">
            <div className="stat-icon bg-primary-100">
              <svg className="w-6 h-6 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
              </svg>
            </div>
            <div>
              <p className="text-sm text-gray-500">تم اختيار</p>
              <p className="text-xl font-bold text-primary-700">{selected.size}</p>
            </div>
          </div>
        )}
      </div>

      {/* Toolbar + Filters */}
      <div className="card">
        <div className="flex items-center gap-3 flex-wrap">
          <label className="flex items-center gap-2 text-sm text-gray-600 cursor-pointer select-none">
            <input
              type="checkbox"
              checked={allSelected}
              onChange={toggleAll}
              className="w-4 h-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
              disabled={data.length === 0}
            />
            تحديد الكل
          </label>

          <div className="h-6 w-px bg-gray-200" />

          <div className="relative flex-1 min-w-[200px] max-w-sm">
            <svg className="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input
              type="text"
              placeholder="بحث برقم العقد أو اسم العميل..."
              className="input pr-9"
              value={filters.search || ''}
              onChange={(e) => setFilters({ search: e.target.value })}
            />
          </div>

          <div className="flex-1" />

          <Link
            to={`/batch/cases?contracts=${Array.from(selected).join(',')}`}
            className={`btn-primary ${selected.size === 0 ? 'opacity-50 pointer-events-none' : ''}`}
            tabIndex={selected.size === 0 ? -1 : 0}
            aria-disabled={selected.size === 0}
            onClick={(e) => selected.size === 0 && e.preventDefault()}
          >
            <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            إنشاء قضايا
            {selected.size > 0 && (
              <span className="bg-white/20 text-white text-xs px-1.5 py-0.5 rounded-full">
                {selected.size}
              </span>
            )}
          </Link>

          <button onClick={() => { setSelected(new Set()); refetch(); }} className="btn-secondary">
            <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            تحديث
          </button>

          <button onClick={handleExport} className="btn-secondary" disabled={loading}>
            <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            تصدير Excel
          </button>
        </div>
      </div>

      {/* Error State */}
      {error && (
        <div className="card border-red-200 bg-red-50 text-red-700 flex items-center gap-3">
          <svg className="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <p className="flex-1 text-sm">{error}</p>
          <button onClick={refetch} className="text-sm font-medium underline">إعادة المحاولة</button>
        </div>
      )}

      {/* Table */}
      <div className="table-container">
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="table-header">
                <th className="px-4 py-3 text-center w-10">
                  <input
                    type="checkbox"
                    checked={allSelected}
                    onChange={toggleAll}
                    className="w-4 h-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                    disabled={data.length === 0}
                  />
                </th>
                <th className="px-4 py-3 text-right font-semibold text-gray-600 w-12">#</th>
                <th className="px-4 py-3 text-right font-semibold text-gray-600">رقم العقد</th>
                <th className="px-4 py-3 text-right font-semibold text-gray-600">اسم العميل</th>
                <th className="px-4 py-3 text-right font-semibold text-gray-600">تاريخ البيع</th>
                <th className="px-4 py-3 text-right font-semibold text-gray-600">المبلغ الإجمالي</th>
                <th className="px-4 py-3 text-right font-semibold text-gray-600">المبلغ المدفوع</th>
                <th className="px-4 py-3 text-right font-semibold text-gray-600">المبلغ المتبقي</th>
                <th className="px-4 py-3 text-right font-semibold text-gray-600">حالة العقد</th>
              </tr>
            </thead>
            <tbody>
              {loading && <SkeletonRows />}

              {!loading && data.length === 0 && (
                <tr>
                  <td colSpan={9}>
                    <div className="empty-state">
                      <svg className="w-16 h-16 mb-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                      </svg>
                      <p className="text-gray-500 font-medium">لا توجد عقود في الحالة القانونية</p>
                      <p className="text-gray-400 text-sm mt-1">جرّب تعديل معايير البحث</p>
                    </div>
                  </td>
                </tr>
              )}

              {!loading && data.map((row, i) => (
                <tr
                  key={row.id}
                  className={`table-row cursor-pointer ${selected.has(row.id) ? 'bg-primary-50/60' : ''}`}
                  onClick={() => toggle(row.id)}
                >
                  <td className="px-4 py-3 text-center" onClick={(e) => e.stopPropagation()}>
                    <input
                      type="checkbox"
                      checked={selected.has(row.id)}
                      onChange={() => toggle(row.id)}
                      className="w-4 h-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                    />
                  </td>
                  <td className="px-4 py-3 text-gray-400 text-xs">
                    {(page - 1) * pageSize + i + 1}
                  </td>
                  <td className="px-4 py-3 font-mono font-semibold text-gray-900">
                    {row.contract_number}
                  </td>
                  <td className="px-4 py-3 text-gray-700">{row.customer_names || '—'}</td>
                  <td className="px-4 py-3 text-gray-500 whitespace-nowrap">
                    {formatDate(row.created_at)}
                  </td>
                  <td className="px-4 py-3 font-semibold whitespace-nowrap">
                    {formatCurrency(row.total_amount)}
                  </td>
                  <td className="px-4 py-3 text-green-700 whitespace-nowrap">
                    {formatCurrency(row.paid_amount)}
                  </td>
                  <td className="px-4 py-3 text-red-700 font-semibold whitespace-nowrap">
                    {formatCurrency(row.remaining_amount)}
                  </td>
                  <td className="px-4 py-3">{statusBadge(row.status)}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>

      {/* Pagination */}
      {totalPages > 1 && (
        <div className="flex items-center justify-center gap-1 pt-2">
          <button
            disabled={page === 1}
            onClick={() => setPage(page - 1)}
            className="btn-secondary px-3 py-1.5 text-xs"
          >
            السابق
          </button>

          {buildPages().map((p, i) =>
            typeof p === 'string' ? (
              <span key={`gap-${i}`} className="px-2 py-1.5 text-gray-400 text-sm select-none">
                {p}
              </span>
            ) : (
              <button
                key={p}
                onClick={() => setPage(p)}
                className={`min-w-[32px] px-2 py-1.5 text-xs rounded-lg font-medium transition-colors ${
                  page === p
                    ? 'bg-primary-600 text-white shadow-sm'
                    : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50'
                }`}
              >
                {p}
              </button>
            )
          )}

          <button
            disabled={page === totalPages}
            onClick={() => setPage(page + 1)}
            className="btn-secondary px-3 py-1.5 text-xs"
          >
            التالي
          </button>
        </div>
      )}
    </div>
  );
}
