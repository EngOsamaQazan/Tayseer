import { useState, useEffect, useCallback } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { fetchCases, fetchLookups, deleteCase, exportExcel, exportPdf } from '../api';
import { usePaginatedApi } from '../hooks/useApi';
import { STAGES, STAGE_LABELS, STATUS_COLORS, STATUS_LABELS, ACTION_NATURE_CONFIG, YEARS } from '../utils/constants';
import { formatDate, downloadFile } from '../utils/helpers';
import toast from 'react-hot-toast';
import {
  MagnifyingGlassIcon,
  ArrowPathIcon,
  PlusIcon,
  FunnelIcon,
  ChevronDownIcon,
  ChevronUpIcon,
  EyeIcon,
  PencilSquareIcon,
  TrashIcon,
  ClockIcon,
  DocumentArrowDownIcon,
  TableCellsIcon,
  QueueListIcon,
  XMarkIcon,
  InboxIcon,
  ChevronLeftIcon,
  ChevronRightIcon,
} from '@heroicons/react/24/outline';

const INITIAL_FILTERS = {
  judiciary_number: '',
  contract_id: '',
  party_name: '',
  court_id: '',
  type_id: '',
  lawyer_id: '',
  year: '',
  from_income_date: '',
  to_income_date: '',
  furthest_stage: '',
  pending_requests: false,
};

function StagePipeline({ current }) {
  const idx = STAGES.findIndex((s) => s.key === current);
  return (
    <div className="flex items-center gap-0.5">
      {STAGES.filter((s) => s.key !== 'general').map((stage, i) => (
        <div
          key={stage.key}
          title={stage.label}
          className={`h-2 w-3 rounded-sm transition-colors ${
            i <= idx
              ? 'bg-primary-500'
              : 'bg-gray-200'
          }`}
        />
      ))}
    </div>
  );
}

function SkeletonRows({ count = 5, cols = 9 }) {
  return Array.from({ length: count }, (_, r) => (
    <tr key={r} className="table-row">
      {Array.from({ length: cols }, (_, c) => (
        <td key={c} className="px-4 py-3.5">
          <div className="skeleton h-4 w-full" />
        </td>
      ))}
    </tr>
  ));
}

export default function CasesTab() {
  const navigate = useNavigate();
  const [filtersOpen, setFiltersOpen] = useState(true);
  const [localFilters, setLocalFilters] = useState(INITIAL_FILTERS);
  const [lookups, setLookups] = useState({ courts: [], lawyers: [], caseTypes: [] });
  const [lookupsLoading, setLookupsLoading] = useState(true);
  const [deleting, setDeleting] = useState(null);
  const [exporting, setExporting] = useState(null);

  const {
    data: cases,
    total,
    loading,
    error,
    page,
    setPage,
    pageSize,
    setPageSize,
    filters,
    setFilters,
    resetFilters,
    refetch,
  } = usePaginatedApi(fetchCases);

  useEffect(() => {
    let cancelled = false;
    setLookupsLoading(true);
    fetchLookups()
      .then((res) => {
        if (!cancelled) setLookups(res);
      })
      .catch(() => {
        if (!cancelled) toast.error('فشل تحميل البيانات المرجعية');
      })
      .finally(() => {
        if (!cancelled) setLookupsLoading(false);
      });
    return () => { cancelled = true; };
  }, []);

  const totalPages = Math.ceil(total / pageSize) || 1;

  const handleFilterChange = useCallback((field, value) => {
    setLocalFilters((prev) => ({ ...prev, [field]: value }));
  }, []);

  const applyFilters = useCallback(() => {
    const clean = {};
    for (const [k, v] of Object.entries(localFilters)) {
      if (v !== '' && v !== false && v != null) clean[k] = v;
    }
    setFilters(clean);
  }, [localFilters, setFilters]);

  const handleReset = useCallback(() => {
    setLocalFilters(INITIAL_FILTERS);
    resetFilters();
  }, [resetFilters]);

  const handleDelete = useCallback(async (id, label) => {
    if (!window.confirm(`هل أنت متأكد من حذف القضية ${label}؟`)) return;
    setDeleting(id);
    try {
      await deleteCase(id);
      toast.success('تم حذف القضية بنجاح');
      refetch();
    } catch (err) {
      toast.error(err.message || 'فشل حذف القضية');
    } finally {
      setDeleting(null);
    }
  }, [refetch]);

  const handleExport = useCallback(async (type) => {
    setExporting(type);
    try {
      const exportFn = type === 'excel' ? exportExcel : exportPdf;
      const blob = await exportFn('cases', filters);
      const ext = type === 'excel' ? 'xlsx' : 'pdf';
      downloadFile(blob, `cases-export.${ext}`);
      toast.success('تم التصدير بنجاح');
    } catch (err) {
      toast.error(err.message || 'فشل التصدير');
    } finally {
      setExporting(null);
    }
  }, [filters]);

  const handleKeyDown = useCallback((e) => {
    if (e.key === 'Enter') applyFilters();
  }, [applyFilters]);

  const pageNumbers = (() => {
    const pages = [];
    const maxVisible = 5;
    let start = Math.max(1, page - Math.floor(maxVisible / 2));
    let end = Math.min(totalPages, start + maxVisible - 1);
    if (end - start + 1 < maxVisible) start = Math.max(1, end - maxVisible + 1);
    for (let i = start; i <= end; i++) pages.push(i);
    return pages;
  })();

  return (
    <div className="space-y-4">
      {/* ─── Filter Panel ─── */}
      <div className="card">
        <button
          onClick={() => setFiltersOpen(!filtersOpen)}
          className="flex items-center justify-between w-full text-right"
        >
          <div className="flex items-center gap-2">
            <FunnelIcon className="w-5 h-5 text-primary-600" />
            <h2 className="section-title text-base">فلترة البحث</h2>
            {Object.values(localFilters).some((v) => v !== '' && v !== false) && (
              <span className="badge bg-primary-100 text-primary-700 text-xs">فعّال</span>
            )}
          </div>
          {filtersOpen ? (
            <ChevronUpIcon className="w-5 h-5 text-gray-400" />
          ) : (
            <ChevronDownIcon className="w-5 h-5 text-gray-400" />
          )}
        </button>

        {filtersOpen && (
          <div className="mt-4 space-y-4" onKeyDown={handleKeyDown}>
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
              {/* judiciary_number */}
              <div>
                <label className="label">رقم الدعوى</label>
                <input
                  type="text"
                  className="input"
                  placeholder="رقم الدعوى..."
                  value={localFilters.judiciary_number}
                  onChange={(e) => handleFilterChange('judiciary_number', e.target.value)}
                />
              </div>

              {/* contract_id */}
              <div>
                <label className="label">رقم العقد</label>
                <input
                  type="number"
                  className="input"
                  placeholder="رقم العقد..."
                  value={localFilters.contract_id}
                  onChange={(e) => handleFilterChange('contract_id', e.target.value)}
                />
              </div>

              {/* party_name */}
              <div>
                <label className="label">اسم الطرف</label>
                <input
                  type="text"
                  className="input"
                  placeholder="بحث بالاسم..."
                  value={localFilters.party_name}
                  onChange={(e) => handleFilterChange('party_name', e.target.value)}
                />
              </div>

              {/* court_id */}
              <div>
                <label className="label">المحكمة</label>
                <select
                  className="select"
                  value={localFilters.court_id}
                  onChange={(e) => handleFilterChange('court_id', e.target.value)}
                  disabled={lookupsLoading}
                >
                  <option value="">الكل</option>
                  {lookups.courts.map((c) => (
                    <option key={c.id} value={c.id}>{c.name}</option>
                  ))}
                </select>
              </div>

              {/* type_id */}
              <div>
                <label className="label">نوع القضية</label>
                <select
                  className="select"
                  value={localFilters.type_id}
                  onChange={(e) => handleFilterChange('type_id', e.target.value)}
                  disabled={lookupsLoading}
                >
                  <option value="">الكل</option>
                  {lookups.caseTypes.map((t) => (
                    <option key={t.id} value={t.id}>{t.name}</option>
                  ))}
                </select>
              </div>

              {/* lawyer_id */}
              <div>
                <label className="label">المحامي</label>
                <select
                  className="select"
                  value={localFilters.lawyer_id}
                  onChange={(e) => handleFilterChange('lawyer_id', e.target.value)}
                  disabled={lookupsLoading}
                >
                  <option value="">الكل</option>
                  {lookups.lawyers.map((l) => (
                    <option key={l.id} value={l.id}>{l.name}</option>
                  ))}
                </select>
              </div>

              {/* year */}
              <div>
                <label className="label">السنة</label>
                <select
                  className="select"
                  value={localFilters.year}
                  onChange={(e) => handleFilterChange('year', e.target.value)}
                >
                  <option value="">الكل</option>
                  {YEARS.map((y) => (
                    <option key={y} value={y}>{y}</option>
                  ))}
                </select>
              </div>

              {/* furthest_stage */}
              <div>
                <label className="label">المرحلة</label>
                <select
                  className="select"
                  value={localFilters.furthest_stage}
                  onChange={(e) => handleFilterChange('furthest_stage', e.target.value)}
                >
                  <option value="">الكل</option>
                  {STAGES.map((s) => (
                    <option key={s.key} value={s.key}>{s.label}</option>
                  ))}
                </select>
              </div>

              {/* from_income_date */}
              <div>
                <label className="label">من تاريخ الورود</label>
                <input
                  type="date"
                  className="input"
                  value={localFilters.from_income_date}
                  onChange={(e) => handleFilterChange('from_income_date', e.target.value)}
                />
              </div>

              {/* to_income_date */}
              <div>
                <label className="label">إلى تاريخ الورود</label>
                <input
                  type="date"
                  className="input"
                  value={localFilters.to_income_date}
                  onChange={(e) => handleFilterChange('to_income_date', e.target.value)}
                />
              </div>

              {/* pending_requests */}
              <div className="flex items-end pb-1">
                <label className="flex items-center gap-2 cursor-pointer select-none">
                  <input
                    type="checkbox"
                    className="w-4 h-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                    checked={localFilters.pending_requests}
                    onChange={(e) => handleFilterChange('pending_requests', e.target.checked)}
                  />
                  <span className="text-sm text-gray-700">طلبات معلقة فقط</span>
                </label>
              </div>
            </div>

            <div className="flex items-center gap-3 pt-2 border-t border-gray-100">
              <button onClick={applyFilters} className="btn-primary">
                <MagnifyingGlassIcon className="w-4 h-4" />
                بحث
              </button>
              <button onClick={handleReset} className="btn-secondary">
                <XMarkIcon className="w-4 h-4" />
                إعادة تعيين
              </button>
            </div>
          </div>
        )}
      </div>

      {/* ─── Toolbar ─── */}
      <div className="flex flex-wrap items-center gap-3">
        <Link to="/case/new" className="btn-primary">
          <PlusIcon className="w-4 h-4" />
          إضافة قضية
        </Link>
        <Link to="/batch/cases" className="btn-secondary">
          <TableCellsIcon className="w-4 h-4" />
          إدخال مجمّع
        </Link>
        <Link to="/batch/actions" className="btn-secondary">
          <QueueListIcon className="w-4 h-4" />
          إدخال إجراءات
        </Link>

        <div className="flex-1" />

        <button
          onClick={() => handleExport('excel')}
          className="btn-secondary"
          disabled={exporting === 'excel'}
        >
          <DocumentArrowDownIcon className="w-4 h-4" />
          {exporting === 'excel' ? 'جارٍ التصدير...' : 'تصدير Excel'}
        </button>
        <button
          onClick={() => handleExport('pdf')}
          className="btn-secondary"
          disabled={exporting === 'pdf'}
        >
          <DocumentArrowDownIcon className="w-4 h-4" />
          {exporting === 'pdf' ? 'جارٍ التصدير...' : 'تصدير PDF'}
        </button>
        <button onClick={refetch} className="btn-icon" title="تحديث">
          <ArrowPathIcon className={`w-5 h-5 ${loading ? 'animate-spin' : ''}`} />
        </button>

        <span className="badge bg-primary-100 text-primary-700">
          {total} قضية
        </span>
      </div>

      {/* ─── Error ─── */}
      {error && (
        <div className="card border-red-200 bg-red-50 text-red-700 text-sm">
          <p>حدث خطأ: {error}</p>
          <button onClick={refetch} className="underline mt-1 text-red-600 hover:text-red-800">
            إعادة المحاولة
          </button>
        </div>
      )}

      {/* ─── Data Table ─── */}
      <div className="table-container overflow-x-auto">
        <table className="w-full text-sm">
          <thead className="table-header">
            <tr>
              <th className="px-4 py-3 text-right font-semibold text-gray-600 whitespace-nowrap">#</th>
              <th className="px-4 py-3 text-right font-semibold text-gray-600 whitespace-nowrap">رقم العقد</th>
              <th className="px-4 py-3 text-right font-semibold text-gray-600 whitespace-nowrap">الأطراف</th>
              <th className="px-4 py-3 text-right font-semibold text-gray-600 whitespace-nowrap">المحكمة</th>
              <th className="px-4 py-3 text-right font-semibold text-gray-600 whitespace-nowrap">المحامي</th>
              <th className="px-4 py-3 text-right font-semibold text-gray-600 whitespace-nowrap">رقم الدعوى / السنة</th>
              <th className="px-4 py-3 text-right font-semibold text-gray-600 whitespace-nowrap">آخر إجراء</th>
              <th className="px-4 py-3 text-right font-semibold text-gray-600 whitespace-nowrap">المرحلة</th>
              <th className="px-4 py-3 text-center font-semibold text-gray-600 whitespace-nowrap">إجراءات</th>
            </tr>
          </thead>
          <tbody>
            {loading ? (
              <SkeletonRows count={pageSize > 10 ? 10 : pageSize} />
            ) : cases.length === 0 ? (
              <tr>
                <td colSpan={9}>
                  <div className="empty-state py-20">
                    <InboxIcon className="w-16 h-16 mb-4 text-gray-300" />
                    <p className="text-lg font-medium text-gray-500">لا توجد قضايا</p>
                    <p className="text-sm text-gray-400 mt-1">جرّب تعديل معايير البحث أو إضافة قضية جديدة</p>
                    <Link to="/case/new" className="btn-primary mt-4">
                      <PlusIcon className="w-4 h-4" />
                      إضافة قضية
                    </Link>
                  </div>
                </td>
              </tr>
            ) : (
              cases.map((c, idx) => {
                const rowNum = (page - 1) * pageSize + idx + 1;
                const parties = c.parties || c.customers || [];
                const lastAction = c.last_action || c.lastAction;
                const natureConf = lastAction ? ACTION_NATURE_CONFIG[lastAction.nature] : null;
                const stageLabel = STAGE_LABELS[c.furthest_stage] || c.furthest_stage || '—';
                const statusColor = STATUS_COLORS[c.status] || 'bg-gray-100 text-gray-700';

                return (
                  <tr key={c.id} className="table-row group">
                    {/* # */}
                    <td className="px-4 py-3 text-gray-400 font-mono text-xs">{rowNum}</td>

                    {/* رقم العقد */}
                    <td className="px-4 py-3 whitespace-nowrap">
                      <Link
                        to={`/case/${c.id}`}
                        className="font-semibold text-primary-700 hover:text-primary-900 hover:underline"
                      >
                        {c.contract_number || c.contract_id || '—'}
                      </Link>
                    </td>

                    {/* الأطراف */}
                    <td className="px-4 py-3 max-w-[200px]">
                      {parties.length > 0 ? (
                        <div className="space-y-1">
                          {parties.slice(0, 3).map((p, pi) => (
                            <div key={pi} className="flex items-center gap-1.5">
                              <span
                                className={`badge text-[10px] px-1.5 py-0 ${
                                  p.type === 'guarantor' || p.role === 'guarantor'
                                    ? 'bg-amber-100 text-amber-700'
                                    : 'bg-primary-100 text-primary-700'
                                }`}
                              >
                                {p.type === 'guarantor' || p.role === 'guarantor' ? 'ضامن' : 'عميل'}
                              </span>
                              <span className="text-gray-800 text-xs truncate">{p.name || p.customer_name}</span>
                            </div>
                          ))}
                          {parties.length > 3 && (
                            <span className="text-[10px] text-gray-400">+{parties.length - 3} آخرين</span>
                          )}
                        </div>
                      ) : (
                        <span className="text-gray-400">—</span>
                      )}
                    </td>

                    {/* المحكمة */}
                    <td className="px-4 py-3 whitespace-nowrap text-gray-700">
                      {c.court_name || c.court?.name || '—'}
                    </td>

                    {/* المحامي */}
                    <td className="px-4 py-3 whitespace-nowrap text-gray-700">
                      {c.lawyer_name || c.lawyer?.name || '—'}
                    </td>

                    {/* رقم الدعوى / السنة */}
                    <td className="px-4 py-3 whitespace-nowrap">
                      {c.judiciary_number ? (
                        <span className="font-mono text-gray-800">
                          {c.judiciary_number}
                          {c.year ? (
                            <span className="text-gray-400 mr-1">/ {c.year}</span>
                          ) : null}
                        </span>
                      ) : (
                        <span className="text-gray-400">—</span>
                      )}
                    </td>

                    {/* آخر إجراء */}
                    <td className="px-4 py-3 max-w-[220px]">
                      {lastAction ? (
                        <div className="space-y-0.5">
                          <div className="flex items-center gap-1.5">
                            {natureConf && (
                              <span className={`w-1.5 h-1.5 rounded-full ${natureConf.dot}`} />
                            )}
                            <span className="text-xs text-gray-800 truncate">
                              {lastAction.title || lastAction.action_name || lastAction.description}
                            </span>
                          </div>
                          <div className="flex items-center gap-2 text-[10px] text-gray-400">
                            {lastAction.customer_name && (
                              <span>{lastAction.customer_name}</span>
                            )}
                            {lastAction.action_date && (
                              <span>{formatDate(lastAction.action_date)}</span>
                            )}
                          </div>
                        </div>
                      ) : (
                        <span className="text-gray-400 text-xs">لا يوجد</span>
                      )}
                    </td>

                    {/* المرحلة */}
                    <td className="px-4 py-3 whitespace-nowrap">
                      <div className="space-y-1">
                        <span className={`badge text-[11px] ${statusColor}`}>
                          {stageLabel}
                        </span>
                        <StagePipeline current={c.furthest_stage} />
                      </div>
                    </td>

                    {/* إجراءات */}
                    <td className="px-4 py-3">
                      <div className="flex items-center justify-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                        <button
                          onClick={() => navigate(`/case/${c.id}`)}
                          className="btn-icon !w-8 !h-8"
                          title="عرض"
                        >
                          <EyeIcon className="w-4 h-4" />
                        </button>
                        <button
                          onClick={() => navigate(`/case/${c.id}/edit`)}
                          className="btn-icon !w-8 !h-8"
                          title="تعديل"
                        >
                          <PencilSquareIcon className="w-4 h-4" />
                        </button>
                        <button
                          onClick={() => navigate(`/case/${c.id}/timeline`)}
                          className="btn-icon !w-8 !h-8"
                          title="الجدول الزمني"
                        >
                          <ClockIcon className="w-4 h-4" />
                        </button>
                        <button
                          onClick={() => handleDelete(c.id, c.contract_number || c.judiciary_number || c.id)}
                          className="btn-icon !w-8 !h-8 hover:!bg-red-50 hover:!text-red-600"
                          title="حذف"
                          disabled={deleting === c.id}
                        >
                          {deleting === c.id ? (
                            <ArrowPathIcon className="w-4 h-4 animate-spin" />
                          ) : (
                            <TrashIcon className="w-4 h-4" />
                          )}
                        </button>
                      </div>
                    </td>
                  </tr>
                );
              })
            )}
          </tbody>
        </table>
      </div>

      {/* ─── Pagination ─── */}
      {!loading && cases.length > 0 && (
        <div className="flex flex-wrap items-center justify-between gap-4">
          <div className="flex items-center gap-2 text-sm text-gray-600">
            <span>صفحة {page} من {totalPages}</span>
            <span className="text-gray-300">|</span>
            <label className="flex items-center gap-1.5">
              <span>عرض</span>
              <select
                className="select !w-auto !py-1.5 !px-2 text-xs"
                value={pageSize}
                onChange={(e) => {
                  setPageSize(Number(e.target.value));
                  setPage(1);
                }}
              >
                {[10, 20, 50, 100].map((s) => (
                  <option key={s} value={s}>{s}</option>
                ))}
              </select>
              <span>سجل</span>
            </label>
          </div>

          <div className="flex items-center gap-1">
            <button
              onClick={() => setPage(page - 1)}
              disabled={page <= 1}
              className="btn-icon !w-8 !h-8 disabled:opacity-30"
            >
              <ChevronRightIcon className="w-4 h-4" />
            </button>

            {pageNumbers[0] > 1 && (
              <>
                <button
                  onClick={() => setPage(1)}
                  className={`btn-icon !w-8 !h-8 text-xs font-medium ${page === 1 ? 'bg-primary-100 text-primary-700' : ''}`}
                >
                  1
                </button>
                {pageNumbers[0] > 2 && <span className="text-gray-400 px-1">...</span>}
              </>
            )}

            {pageNumbers.map((p) => (
              <button
                key={p}
                onClick={() => setPage(p)}
                className={`btn-icon !w-8 !h-8 text-xs font-medium ${
                  p === page ? 'bg-primary-100 text-primary-700 ring-1 ring-primary-300' : ''
                }`}
              >
                {p}
              </button>
            ))}

            {pageNumbers[pageNumbers.length - 1] < totalPages && (
              <>
                {pageNumbers[pageNumbers.length - 1] < totalPages - 1 && (
                  <span className="text-gray-400 px-1">...</span>
                )}
                <button
                  onClick={() => setPage(totalPages)}
                  className={`btn-icon !w-8 !h-8 text-xs font-medium ${page === totalPages ? 'bg-primary-100 text-primary-700' : ''}`}
                >
                  {totalPages}
                </button>
              </>
            )}

            <button
              onClick={() => setPage(page + 1)}
              disabled={page >= totalPages}
              className="btn-icon !w-8 !h-8 disabled:opacity-30"
            >
              <ChevronLeftIcon className="w-4 h-4" />
            </button>
          </div>
        </div>
      )}
    </div>
  );
}
