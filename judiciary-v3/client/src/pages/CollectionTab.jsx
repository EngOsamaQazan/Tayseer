import { useState, useCallback } from 'react';
import { fetchCollection, exportExcel, exportPdf, api } from '../api';
import { usePaginatedApi } from '../hooks/useApi';
import { formatDate, formatCurrency, downloadFile } from '../utils/helpers';
import toast from 'react-hot-toast';

const EMPTY_FORM = {
  reference_number: '',
  total_amount: '',
  collected_amount: '',
  note: '',
};

function collectionFetcher(filters, page, pageSize) {
  return fetchCollection({ ...filters, page, pageSize });
}

function toDate(v) {
  if (!v) return null;
  if (typeof v === 'number') return new Date(v < 1e12 ? v * 1000 : v);
  return v;
}

function SkeletonRows({ count = 5 }) {
  return Array.from({ length: count }, (_, i) => (
    <tr key={i} className="table-row">
      <td className="px-4 py-3"><div className="skeleton w-6 h-4" /></td>
      <td className="px-4 py-3"><div className="skeleton w-20 h-4" /></td>
      <td className="px-4 py-3"><div className="skeleton w-24 h-4" /></td>
      <td className="px-4 py-3"><div className="skeleton w-20 h-4" /></td>
      <td className="px-4 py-3"><div className="skeleton w-28 h-4" /></td>
      <td className="px-4 py-3"><div className="skeleton w-20 h-4" /></td>
      <td className="px-4 py-3"><div className="skeleton w-20 h-4" /></td>
      <td className="px-4 py-3"><div className="skeleton w-20 h-4" /></td>
    </tr>
  ));
}

export default function CollectionTab() {
  const [showForm, setShowForm] = useState(false);
  const [editId, setEditId] = useState(null);
  const [form, setForm] = useState(EMPTY_FORM);
  const [saving, setSaving] = useState(false);
  const [expandedRow, setExpandedRow] = useState(null);

  const {
    data, total, loading, error, page, setPage, pageSize, filters, setFilters, refetch,
  } = usePaginatedApi(collectionFetcher, {});

  const totalPages = Math.ceil(total / pageSize);
  const totalAvailable = data.reduce((sum, r) => sum + (Number(r.available_amount) || 0), 0);

  const setField = useCallback((key, value) => {
    setForm((prev) => ({ ...prev, [key]: value }));
  }, []);

  const openAdd = () => {
    setEditId(null);
    setForm(EMPTY_FORM);
    setShowForm(true);
  };

  const openEdit = (row) => {
    setEditId(row.id);
    setForm({
      reference_number: row.reference_number || '',
      total_amount: row.total_amount ?? '',
      collected_amount: row.collected_amount ?? '',
      note: row.note || '',
    });
    setShowForm(true);
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  const closeForm = () => {
    setShowForm(false);
    setEditId(null);
    setForm(EMPTY_FORM);
  };

  const handleSave = async () => {
    if (!form.total_amount && form.total_amount !== 0) {
      toast.error('يرجى إدخال المبلغ');
      return;
    }
    setSaving(true);
    try {
      const payload = {
        reference_number: form.reference_number || null,
        total_amount: Number(form.total_amount) || 0,
        collected_amount: Number(form.collected_amount) || 0,
        note: form.note || null,
      };
      if (editId) {
        await api.put(`/collection/${editId}`, payload);
        toast.success('تم التعديل بنجاح');
      } else {
        await api.post('/collection', payload);
        toast.success('تمت الإضافة بنجاح');
      }
      closeForm();
      refetch();
    } catch (err) {
      toast.error(err.message || 'حدث خطأ أثناء الحفظ');
    } finally {
      setSaving(false);
    }
  };

  const handleDelete = async (row) => {
    if (!window.confirm(`هل تريد حذف سجل التحصيل رقم ${row.reference_number || row.id}؟`)) return;
    try {
      await api.delete(`/collection/${row.id}`);
      toast.success('تم الحذف بنجاح');
      refetch();
    } catch (err) {
      toast.error(err.message || 'فشل الحذف');
    }
  };

  const handleExportExcel = async () => {
    try {
      const blob = await exportExcel('collection', filters);
      downloadFile(blob, 'تحصيل.xlsx');
      toast.success('تم التصدير بنجاح');
    } catch {
      toast.error('فشل تصدير الملف');
    }
  };

  const handleExportPdf = async () => {
    try {
      const blob = await exportPdf('collection', filters);
      downloadFile(blob, 'تحصيل.pdf');
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

  return (
    <div className="space-y-4">
      {/* Summary Cards */}
      <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div className="stat-card border-teal-100">
          <div className="stat-icon bg-teal-100">
            <svg className="w-6 h-6 text-teal-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
          </div>
          <div>
            <p className="text-sm text-gray-500">عدد حالات التحصيل</p>
            <p className="text-2xl font-bold text-teal-700">{loading ? '—' : total}</p>
          </div>
        </div>

        <div className="stat-card border-emerald-100">
          <div className="stat-icon bg-emerald-100">
            <svg className="w-6 h-6 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <div>
            <p className="text-sm text-gray-500">المبلغ المتاح للتحصيل</p>
            <p className="text-2xl font-bold text-emerald-700">
              {loading ? '—' : formatCurrency(totalAvailable)}
            </p>
          </div>
        </div>
      </div>

      {/* Toolbar + Filters */}
      <div className="card">
        <div className="flex items-center gap-3 flex-wrap">
          <div className="relative flex-1 min-w-[200px] max-w-sm">
            <svg className="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input
              type="text"
              placeholder="بحث برقم العقد أو ملاحظات..."
              className="input pr-9"
              value={filters.search || ''}
              onChange={(e) => setFilters({ search: e.target.value })}
            />
          </div>

          <div className="flex items-center gap-2">
            <label className="text-xs text-gray-500 whitespace-nowrap">من</label>
            <input
              type="date"
              className="input w-auto text-sm"
              value={filters.dateFrom || ''}
              onChange={(e) => setFilters({ dateFrom: e.target.value })}
            />
            <label className="text-xs text-gray-500 whitespace-nowrap">إلى</label>
            <input
              type="date"
              className="input w-auto text-sm"
              value={filters.dateTo || ''}
              onChange={(e) => setFilters({ dateTo: e.target.value })}
            />
          </div>

          <div className="flex-1" />

          <button onClick={openAdd} className="btn-primary">
            <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            إضافة تحصيل
          </button>

          <button onClick={refetch} className="btn-secondary">
            <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            تحديث
          </button>

          <button onClick={handleExportExcel} className="btn-secondary" disabled={loading}>
            <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Excel
          </button>

          <button onClick={handleExportPdf} className="btn-secondary" disabled={loading}>
            <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
            </svg>
            PDF
          </button>
        </div>
      </div>

      {/* Inline Add/Edit Form */}
      {showForm && (
        <div className="card border-primary-200 bg-primary-50/20">
          <div className="flex items-center justify-between mb-4">
            <h3 className="section-title">
              {editId ? 'تعديل حالة تحصيل' : 'إضافة حالة تحصيل جديدة'}
            </h3>
            <button onClick={closeForm} className="btn-icon">
              <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
              <label className="label">رقم العقد</label>
              <input
                className="input"
                value={form.reference_number}
                onChange={(e) => setField('reference_number', e.target.value)}
                placeholder="مثال: CON-001"
              />
            </div>
            <div>
              <label className="label">المبلغ الإجمالي</label>
              <input
                className="input"
                type="number"
                min="0"
                step="0.01"
                value={form.total_amount}
                onChange={(e) => setField('total_amount', e.target.value)}
                placeholder="0.00"
              />
            </div>
            <div>
              <label className="label">المبلغ المحصّل</label>
              <input
                className="input"
                type="number"
                min="0"
                step="0.01"
                value={form.collected_amount}
                onChange={(e) => setField('collected_amount', e.target.value)}
                placeholder="0.00"
              />
            </div>
            <div>
              <label className="label">الملاحظات</label>
              <input
                className="input"
                value={form.note}
                onChange={(e) => setField('note', e.target.value)}
                placeholder="ملاحظات إضافية..."
              />
            </div>
          </div>

          <div className="flex items-center gap-2 mt-4">
            <button onClick={handleSave} className="btn-primary" disabled={saving}>
              {saving ? (
                <>
                  <svg className="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                  </svg>
                  جارِ الحفظ...
                </>
              ) : (
                <>
                  <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                  </svg>
                  {editId ? 'تحديث' : 'حفظ'}
                </>
              )}
            </button>
            <button onClick={closeForm} className="btn-secondary" disabled={saving}>
              إلغاء
            </button>
          </div>
        </div>
      )}

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
                <th className="px-4 py-3 text-right font-semibold text-gray-600 w-12">#</th>
                <th className="px-4 py-3 text-right font-semibold text-gray-600">رقم العقد</th>
                <th className="px-4 py-3 text-right font-semibold text-gray-600">التاريخ</th>
                <th className="px-4 py-3 text-right font-semibold text-gray-600">المبلغ</th>
                <th className="px-4 py-3 text-right font-semibold text-gray-600">الملاحظات</th>
                <th className="px-4 py-3 text-right font-semibold text-gray-600">الموظف</th>
                <th className="px-4 py-3 text-right font-semibold text-gray-600">المتاح للتحصيل</th>
                <th className="px-4 py-3 text-center font-semibold text-gray-600 w-28">إجراءات</th>
              </tr>
            </thead>
            <tbody>
              {loading && <SkeletonRows />}

              {!loading && data.length === 0 && (
                <tr>
                  <td colSpan={8}>
                    <div className="empty-state">
                      <svg className="w-16 h-16 mb-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1} d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                      </svg>
                      <p className="text-gray-500 font-medium">لا توجد سجلات تحصيل</p>
                      <p className="text-gray-400 text-sm mt-1">أضف سجل تحصيل جديد للبدء</p>
                    </div>
                  </td>
                </tr>
              )}

              {!loading && data.map((row, i) => {
                const isExpanded = expandedRow === row.id;
                return [
                  <tr key={row.id} className={`table-row ${isExpanded ? 'bg-gray-50/50' : ''}`}>
                    <td className="px-4 py-3 text-gray-400 text-xs">
                      {(page - 1) * pageSize + i + 1}
                    </td>
                    <td className="px-4 py-3 font-mono font-semibold text-gray-900">
                      {row.reference_number || '—'}
                    </td>
                    <td className="px-4 py-3 text-gray-500 whitespace-nowrap">
                      {formatDate(toDate(row.created_at))}
                    </td>
                    <td className="px-4 py-3 font-semibold whitespace-nowrap">
                      {formatCurrency(row.total_amount)}
                    </td>
                    <td className="px-4 py-3 text-gray-600 max-w-[200px] truncate" title={row.note}>
                      {row.note || '—'}
                    </td>
                    <td className="px-4 py-3 text-gray-700">
                      {row.employee_name || '—'}
                    </td>
                    <td className="px-4 py-3 whitespace-nowrap">
                      <span className={`font-semibold ${Number(row.available_amount) > 0 ? 'text-emerald-700' : 'text-gray-400'}`}>
                        {formatCurrency(row.available_amount)}
                      </span>
                    </td>
                    <td className="px-4 py-3">
                      <div className="flex items-center justify-center gap-1">
                        <button
                          onClick={() => setExpandedRow(isExpanded ? null : row.id)}
                          className="btn-icon"
                          title="عرض"
                        >
                          <svg className={`w-4 h-4 transition-transform ${isExpanded ? 'rotate-180' : ''}`} fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                          </svg>
                        </button>
                        <button
                          onClick={() => openEdit(row)}
                          className="btn-icon text-blue-500 hover:text-blue-700 hover:bg-blue-50"
                          title="تعديل"
                        >
                          <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                          </svg>
                        </button>
                        <button
                          onClick={() => handleDelete(row)}
                          className="btn-icon text-red-400 hover:text-red-600 hover:bg-red-50"
                          title="حذف"
                        >
                          <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                          </svg>
                        </button>
                      </div>
                    </td>
                  </tr>,

                  isExpanded && (
                    <tr key={`${row.id}-detail`} className="bg-gray-50/80">
                      <td colSpan={8} className="px-6 py-4 border-b border-gray-100">
                        <div className="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                          <div>
                            <p className="text-gray-400 text-xs mb-1">رقم السجل</p>
                            <p className="font-semibold text-gray-800">{row.id}</p>
                          </div>
                          <div>
                            <p className="text-gray-400 text-xs mb-1">المبلغ المحصّل</p>
                            <p className="font-semibold text-green-700">
                              {formatCurrency(row.collected_amount)}
                            </p>
                          </div>
                          <div>
                            <p className="text-gray-400 text-xs mb-1">آخر تحديث</p>
                            <p className="text-gray-600">
                              {formatDate(toDate(row.updated_at))}
                            </p>
                          </div>
                          <div>
                            <p className="text-gray-400 text-xs mb-1">الملاحظات الكاملة</p>
                            <p className="text-gray-600">{row.note || 'لا توجد ملاحظات'}</p>
                          </div>
                        </div>
                      </td>
                    </tr>
                  ),
                ];
              })}
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
