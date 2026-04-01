import { useState, useEffect, useCallback } from 'react';
import { Link } from 'react-router-dom';
import { api, fetchActions, fetchLookups, createAction, updateActionStatus, exportExcel, exportPdf } from '../api';
import { usePaginatedApi } from '../hooks/useApi';
import { ACTION_NATURE_CONFIG, REQUEST_STATUS_CONFIG, YEARS } from '../utils/constants';
import { formatDate, downloadFile, formatNumber } from '../utils/helpers';
import toast from 'react-hot-toast';

const PAGE_SIZES = [10, 20, 50, 100];
const EMPTY_FORM = { judiciary_id: '', customers_id: '', judiciary_actions_id: '', action_date: '', note: '' };

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

function InfoRow({ label, value }) {
  return (
    <div className="flex items-start gap-3 py-2.5 border-b border-gray-50 last:border-0">
      <span className="text-sm font-medium text-gray-500 min-w-[100px] shrink-0">{label}</span>
      <span className="text-sm text-gray-900">{value || '—'}</span>
    </div>
  );
}

export default function ActionsTab() {
  const [lookups, setLookups] = useState({ courts: [], lawyers: [], actionCatalog: [], users: [] });
  const [filtersOpen, setFiltersOpen] = useState(false);
  const [localFilters, setLocalFilters] = useState({});
  const [modal, setModal] = useState({ type: null, data: null });
  const [formData, setFormData] = useState(EMPTY_FORM);
  const [formLoading, setFormLoading] = useState(false);
  const [exporting, setExporting] = useState(null);

  const {
    data, total, loading, error,
    page, setPage, pageSize, setPageSize,
    filters, setFilters, resetFilters, refetch,
  } = usePaginatedApi(fetchActions);

  useEffect(() => {
    (async () => {
      try {
        const [lk, usersRes] = await Promise.all([
          fetchLookups(),
          api.get('/lookups/users'),
        ]);
        setLookups({
          courts: lk.courts?.data || lk.courts || [],
          lawyers: lk.lawyers?.data || lk.lawyers || [],
          actionCatalog: lk.actionCatalog?.data || lk.actionCatalog || [],
          users: usersRes?.data || usersRes || [],
        });
      } catch {
        toast.error('فشل تحميل البيانات المرجعية');
      }
    })();
  }, []);

  const f = (key, val) => setLocalFilters(p => ({ ...p, [key]: val }));
  const applyFilters = () => setFilters(localFilters);
  const clearFilters = () => { setLocalFilters({}); resetFilters(); };

  const handleExport = async (type) => {
    setExporting(type);
    try {
      const blob = type === 'excel'
        ? await exportExcel('actions', filters)
        : await exportPdf('actions', filters);
      downloadFile(blob, `الإجراءات.${type === 'excel' ? 'xlsx' : 'pdf'}`);
      toast.success('تم التصدير بنجاح');
    } catch { toast.error('فشل التصدير'); }
    finally { setExporting(null); }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setFormLoading(true);
    try {
      if (modal.type === 'edit') {
        await api.put(`/actions/${modal.data.id}`, formData);
        toast.success('تم تحديث الإجراء');
      } else {
        await createAction(formData);
        toast.success('تم إضافة الإجراء');
      }
      closeModal();
      refetch();
    } catch (err) { toast.error(err.message || 'حدث خطأ'); }
    finally { setFormLoading(false); }
  };

  const handleDelete = async () => {
    if (!modal.data) return;
    try {
      await api.delete(`/actions/${modal.data.id}`);
      toast.success('تم حذف الإجراء');
      closeModal();
      refetch();
    } catch { toast.error('فشل حذف الإجراء'); }
  };

  const handleStatusChange = async (row, status) => {
    try {
      await updateActionStatus(row.id, status);
      toast.success(status === 'approved' ? 'تمت الموافقة' : 'تم الرفض');
      refetch();
    } catch { toast.error('فشل تحديث الحالة'); }
  };

  const openAdd = () => { setFormData(EMPTY_FORM); setModal({ type: 'add', data: null }); };
  const openEdit = (row) => {
    setFormData({
      judiciary_id: row.judiciary_id || '',
      customers_id: row.customers_id || '',
      judiciary_actions_id: row.judiciary_actions_id || '',
      action_date: row.action_date ? String(row.action_date).slice(0, 10) : '',
      note: row.note || '',
    });
    setModal({ type: 'edit', data: row });
  };
  const openView = (row) => setModal({ type: 'view', data: row });
  const openDelete = (row) => setModal({ type: 'delete', data: row });
  const closeModal = () => { setModal({ type: null, data: null }); setFormData(EMPTY_FORM); };

  const totalPages = Math.ceil(total / pageSize) || 1;

  const natureBadge = (nature) => {
    const cfg = ACTION_NATURE_CONFIG[nature];
    if (!cfg) return null;
    return (
      <span className={`badge ${cfg.color}`}>
        <span className={`w-1.5 h-1.5 rounded-full ${cfg.dot} ml-1.5`} />
        {cfg.label}
      </span>
    );
  };

  const statusBadge = (row) => {
    const st = row.request_status;
    const cfg = REQUEST_STATUS_CONFIG[st];
    if (!cfg) return <span className="badge badge-neutral">{st ?? '—'}</span>;
    return (
      <div className="flex items-center gap-1.5 flex-wrap">
        <span className={`badge ${cfg.color}`}>{cfg.label}</span>
        {st === 'pending' && (
          <>
            <button
              onClick={(e) => { e.stopPropagation(); handleStatusChange(row, 'approved'); }}
              className="w-6 h-6 rounded-full bg-green-100 text-green-700 hover:bg-green-200 inline-flex items-center justify-center text-xs transition-colors"
              title="موافقة"
            >✓</button>
            <button
              onClick={(e) => { e.stopPropagation(); handleStatusChange(row, 'rejected'); }}
              className="w-6 h-6 rounded-full bg-red-100 text-red-700 hover:bg-red-200 inline-flex items-center justify-center text-xs transition-colors"
              title="رفض"
            >✗</button>
          </>
        )}
      </div>
    );
  };

  return (
    <div className="space-y-4">
      {/* ── Toolbar ── */}
      <div className="flex items-center justify-between flex-wrap gap-3">
        <div className="flex items-center gap-3">
          <h2 className="section-title">الإجراءات</h2>
          <span className="badge badge-info">{formatNumber(total)} سجل</span>
        </div>
        <div className="flex items-center gap-2 flex-wrap">
          <button onClick={openAdd} className="btn-primary">
            <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            إضافة إجراء
          </button>
          <button
            onClick={() => setFiltersOpen(v => !v)}
            className={`btn-secondary ${filtersOpen ? 'ring-2 ring-primary-500 bg-primary-50' : ''}`}
          >
            <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" /></svg>
            الفلاتر
          </button>
          <button onClick={refetch} className="btn-secondary" title="تحديث">
            <svg className={`w-4 h-4 ${loading ? 'animate-spin' : ''}`} fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182" /></svg>
          </button>
          <button onClick={() => handleExport('excel')} disabled={!!exporting} className="btn-secondary">
            {exporting === 'excel' ? <Spinner /> : <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>}
            Excel
          </button>
          <button onClick={() => handleExport('pdf')} disabled={!!exporting} className="btn-secondary">
            {exporting === 'pdf' ? <Spinner /> : <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H6.75a2.25 2.25 0 00-2.25 2.25v13.5a2.25 2.25 0 002.25 2.25h10.5a2.25 2.25 0 002.25-2.25v-.75" /></svg>}
            PDF
          </button>
        </div>
      </div>

      {/* ── Filter Panel ── */}
      <div className={`overflow-hidden transition-all duration-300 ${filtersOpen ? 'max-h-[600px] opacity-100' : 'max-h-0 opacity-0'}`}>
        <div className="card">
          <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
            <div>
              <label className="label">رقم القضية</label>
              <input className="input" placeholder="ابحث برقم القضية..." value={localFilters.judiciary_number || ''} onChange={e => f('judiciary_number', e.target.value)} />
            </div>
            <div>
              <label className="label">المدعى عليه</label>
              <input className="input" placeholder="رقم أو اسم العميل..." value={localFilters.customers_id || ''} onChange={e => f('customers_id', e.target.value)} />
            </div>
            <div>
              <label className="label">نوع الإجراء</label>
              <select className="select" value={localFilters.judiciary_actions_id || ''} onChange={e => f('judiciary_actions_id', e.target.value)}>
                <option value="">الكل</option>
                {lookups.actionCatalog.map(a => <option key={a.id} value={a.id}>{a.name}</option>)}
              </select>
            </div>
            <div>
              <label className="label">السنة</label>
              <select className="select" value={localFilters.year || ''} onChange={e => f('year', e.target.value)}>
                <option value="">الكل</option>
                {YEARS.map(y => <option key={y} value={y}>{y}</option>)}
              </select>
            </div>
            <div>
              <label className="label">رقم العقد</label>
              <input type="number" className="input" placeholder="رقم العقد" value={localFilters.contract_id || ''} onChange={e => f('contract_id', e.target.value)} />
            </div>
            <div>
              <label className="label">المحكمة</label>
              <select className="select" value={localFilters.court_id || ''} onChange={e => f('court_id', e.target.value)}>
                <option value="">الكل</option>
                {lookups.courts.map(c => <option key={c.id} value={c.id}>{c.name}</option>)}
              </select>
            </div>
            <div>
              <label className="label">المحامي</label>
              <select className="select" value={localFilters.lawyer_id || ''} onChange={e => f('lawyer_id', e.target.value)}>
                <option value="">الكل</option>
                {lookups.lawyers.map(l => <option key={l.id} value={l.id}>{l.name}</option>)}
              </select>
            </div>
            <div>
              <label className="label">المنشئ</label>
              <select className="select" value={localFilters.created_by || ''} onChange={e => f('created_by', e.target.value)}>
                <option value="">الكل</option>
                {lookups.users.map(u => <option key={u.id} value={u.id}>{u.username}</option>)}
              </select>
            </div>
            <div>
              <label className="label">من تاريخ</label>
              <input type="date" className="input" value={localFilters.from_action_date || ''} onChange={e => f('from_action_date', e.target.value)} />
            </div>
            <div>
              <label className="label">إلى تاريخ</label>
              <input type="date" className="input" value={localFilters.to_action_date || ''} onChange={e => f('to_action_date', e.target.value)} />
            </div>
          </div>
          <div className="flex items-center gap-2 mt-4 pt-4 border-t border-gray-100">
            <button onClick={applyFilters} className="btn-primary">
              <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
              بحث
            </button>
            <button onClick={clearFilters} className="btn-secondary">مسح الفلاتر</button>
          </div>
        </div>
      </div>

      {/* ── Table ── */}
      {error ? (
        <div className="card text-center py-10">
          <p className="text-red-600 mb-3">{error}</p>
          <button onClick={refetch} className="btn-secondary">إعادة المحاولة</button>
        </div>
      ) : (
        <div className="table-container">
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="table-header">
                  {['#', 'القضية', 'المدعى عليه', 'الإجراء', 'الملاحظات', 'المنشئ', 'المحامي', 'المحكمة', 'رقم العقد', 'التاريخ', 'حالة الطلب', 'إجراءات'].map(h => (
                    <th key={h} className="px-3 py-3 text-right font-semibold text-gray-600 whitespace-nowrap">{h}</th>
                  ))}
                </tr>
              </thead>
              <tbody>
                {loading ? (
                  Array.from({ length: 6 }).map((_, i) => (
                    <tr key={i} className="table-row">
                      {Array.from({ length: 12 }).map((_, j) => (
                        <td key={j} className="px-3 py-3"><div className="skeleton h-4 w-full rounded" /></td>
                      ))}
                    </tr>
                  ))
                ) : data.length === 0 ? (
                  <tr>
                    <td colSpan={12} className="py-16">
                      <div className="empty-state">
                        <svg className="w-14 h-14 mb-3 text-gray-300" fill="none" viewBox="0 0 24 24" strokeWidth={1} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m6 4.125l2.25 2.25m0 0l2.25 2.25M12 13.875l2.25-2.25M12 13.875l-2.25 2.25M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" /></svg>
                        <p className="text-gray-400 font-medium">لا توجد إجراءات</p>
                      </div>
                    </td>
                  </tr>
                ) : data.map((row, idx) => (
                  <tr key={row.id} className="table-row">
                    <td className="px-3 py-3 text-gray-400 tabular-nums">{(page - 1) * pageSize + idx + 1}</td>
                    <td className="px-3 py-3">
                      <Link to={`/case/${row.judiciary_id}`} className="text-primary-600 hover:text-primary-700 font-medium hover:underline whitespace-nowrap">
                        {row.judiciary_number || row.judiciary_id}
                      </Link>
                    </td>
                    <td className="px-3 py-3 font-medium whitespace-nowrap">{row.customer_name || '—'}</td>
                    <td className="px-3 py-3">
                      <div className="flex items-center gap-2 whitespace-nowrap">
                        {natureBadge(row.action_nature)}
                        <span>{row.action_name || '—'}</span>
                      </div>
                    </td>
                    <td className="px-3 py-3 max-w-[200px]">
                      <span className="line-clamp-2 text-gray-600" title={row.note || ''}>{row.note || '—'}</span>
                    </td>
                    <td className="px-3 py-3 text-gray-600 whitespace-nowrap">{row.created_by_name || '—'}</td>
                    <td className="px-3 py-3 text-gray-600 whitespace-nowrap">{row.lawyer_name || '—'}</td>
                    <td className="px-3 py-3 text-gray-600 whitespace-nowrap">{row.court_name || '—'}</td>
                    <td className="px-3 py-3 text-gray-600 tabular-nums">{row.contract_id || '—'}</td>
                    <td className="px-3 py-3 text-gray-600 whitespace-nowrap">{formatDate(row.action_date)}</td>
                    <td className="px-3 py-3">{statusBadge(row)}</td>
                    <td className="px-3 py-3">
                      <div className="flex items-center justify-center gap-0.5">
                        <button onClick={() => openView(row)} className="btn-icon" title="عرض">
                          <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path strokeLinecap="round" strokeLinejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                        </button>
                        <button onClick={() => openEdit(row)} className="btn-icon" title="تعديل">
                          <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" /></svg>
                        </button>
                        <button onClick={() => openDelete(row)} className="btn-icon text-red-500 hover:text-red-700 hover:bg-red-50" title="حذف">
                          <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                        </button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>

          {/* ── Pagination ── */}
          {!loading && data.length > 0 && (
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
              <span className="text-sm text-gray-500">صفحة {page} من {totalPages}</span>
            </div>
          )}
        </div>
      )}

      {/* ── Modal ── */}
      {modal.type && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
          <div className="absolute inset-0 bg-black/40 backdrop-blur-sm" onClick={closeModal} />
          <div className="relative bg-white rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto animate-in fade-in zoom-in-95">
            <div className="flex items-center justify-between p-5 border-b border-gray-100 sticky top-0 bg-white rounded-t-2xl z-10">
              <h3 className="text-lg font-bold text-gray-900">
                {{ add: 'إضافة إجراء جديد', edit: 'تعديل الإجراء', view: 'تفاصيل الإجراء', delete: 'حذف الإجراء' }[modal.type]}
              </h3>
              <button onClick={closeModal} className="btn-icon">
                <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
              </button>
            </div>

            <div className="p-5">
              {(modal.type === 'add' || modal.type === 'edit') && (
                <form onSubmit={handleSubmit} className="space-y-4">
                  <div className="grid grid-cols-2 gap-4">
                    <div>
                      <label className="label">رقم القضية (ID)</label>
                      <input type="number" className="input" required value={formData.judiciary_id} onChange={e => setFormData(p => ({ ...p, judiciary_id: e.target.value }))} />
                    </div>
                    <div>
                      <label className="label">رقم العميل (ID)</label>
                      <input type="number" className="input" required value={formData.customers_id} onChange={e => setFormData(p => ({ ...p, customers_id: e.target.value }))} />
                    </div>
                    <div>
                      <label className="label">نوع الإجراء</label>
                      <select className="select" required value={formData.judiciary_actions_id} onChange={e => setFormData(p => ({ ...p, judiciary_actions_id: e.target.value }))}>
                        <option value="">اختر الإجراء...</option>
                        {lookups.actionCatalog.map(a => <option key={a.id} value={a.id}>{a.name}</option>)}
                      </select>
                    </div>
                    <div>
                      <label className="label">تاريخ الإجراء</label>
                      <input type="date" className="input" value={formData.action_date} onChange={e => setFormData(p => ({ ...p, action_date: e.target.value }))} />
                    </div>
                  </div>
                  <div>
                    <label className="label">ملاحظات</label>
                    <textarea className="input min-h-[80px] resize-y" rows={3} value={formData.note} onChange={e => setFormData(p => ({ ...p, note: e.target.value }))} />
                  </div>
                  <div className="flex items-center justify-end gap-2 pt-4 border-t border-gray-100">
                    <button type="button" onClick={closeModal} className="btn-secondary">إلغاء</button>
                    <button type="submit" disabled={formLoading} className="btn-primary">
                      {formLoading && <Spinner />}
                      {modal.type === 'add' ? 'إضافة' : 'حفظ التعديلات'}
                    </button>
                  </div>
                </form>
              )}

              {modal.type === 'view' && modal.data && (
                <div className="space-y-1">
                  <InfoRow label="القضية" value={modal.data.judiciary_number} />
                  <InfoRow label="المدعى عليه" value={modal.data.customer_name} />
                  <InfoRow label="الإجراء" value={modal.data.action_name} />
                  <InfoRow label="المحكمة" value={modal.data.court_name} />
                  <InfoRow label="المحامي" value={modal.data.lawyer_name} />
                  <InfoRow label="التاريخ" value={formatDate(modal.data.action_date)} />
                  <InfoRow label="المنشئ" value={modal.data.created_by_name} />
                  <InfoRow label="الملاحظات" value={modal.data.note} />
                  <div className="pt-4 border-t border-gray-100 mt-3">
                    <button onClick={closeModal} className="btn-secondary w-full">إغلاق</button>
                  </div>
                </div>
              )}

              {modal.type === 'delete' && modal.data && (
                <div className="text-center space-y-4">
                  <div className="w-14 h-14 mx-auto rounded-full bg-red-100 flex items-center justify-center">
                    <svg className="w-7 h-7 text-red-600" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                  </div>
                  <p className="text-gray-700">
                    هل أنت متأكد من حذف إجراء <strong className="text-gray-900">{modal.data.action_name}</strong> للقضية <strong className="text-gray-900">{modal.data.judiciary_number}</strong>؟
                  </p>
                  <p className="text-sm text-gray-500">لا يمكن التراجع عن هذا الإجراء</p>
                  <div className="flex items-center justify-center gap-3 pt-2">
                    <button onClick={closeModal} className="btn-secondary">إلغاء</button>
                    <button onClick={handleDelete} className="btn-danger">نعم، احذف</button>
                  </div>
                </div>
              )}
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
