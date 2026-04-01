import { useState, useEffect, useCallback } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import { fetchCase, createCase, updateCase, fetchLookups } from '../api';
import { CASE_STATUS_OPTIONS, YEARS } from '../utils/constants';
import { formatDate, formatCurrency } from '../utils/helpers';
import toast from 'react-hot-toast';
import {
  ArrowPathIcon,
  CheckIcon,
  XMarkIcon,
  ChevronRightIcon,
  MagnifyingGlassIcon,
  CheckCircleIcon,
  XCircleIcon,
} from '@heroicons/react/24/outline';

const REQUIRED_FIELDS = ['court_id', 'type_id', 'lawyer_id', 'contract_id', 'judiciary_number', 'year'];

const EMPTY_FORM = {
  court_id: '',
  type_id: '',
  company_id: '',
  lawyer_id: '',
  lawyer_cost: '',
  case_cost: '',
  judiciary_number: '',
  year: '',
  income_date: '',
  judiciary_inform_address_id: '',
  case_status: 'open',
  contract_id: '',
};

function SearchableSelect({ label, value, onChange, options, placeholder, required, disabled }) {
  const [search, setSearch] = useState('');
  const [open, setOpen] = useState(false);

  const filtered = options.filter((o) =>
    o.name.toLowerCase().includes(search.toLowerCase())
  );

  const selected = options.find((o) => String(o.id) === String(value));

  return (
    <div className="relative">
      <label className="label">
        {label}
        {required && <span className="text-red-500 mr-1">*</span>}
      </label>
      <button
        type="button"
        className={`input text-right flex items-center justify-between gap-2 ${
          !value ? 'text-gray-400' : 'text-gray-900'
        }`}
        onClick={() => !disabled && setOpen(!open)}
        disabled={disabled}
      >
        <span className="truncate">{selected ? selected.name : placeholder || 'اختر...'}</span>
        <ChevronRightIcon className={`w-4 h-4 text-gray-400 shrink-0 transition-transform ${open ? '-rotate-90' : ''}`} />
      </button>

      {open && (
        <>
          <div className="fixed inset-0 z-40" onClick={() => setOpen(false)} />
          <div className="absolute z-50 top-full mt-1 w-full bg-white rounded-lg shadow-lg border border-gray-200 max-h-64 overflow-hidden">
            <div className="p-2 border-b border-gray-100">
              <div className="relative">
                <MagnifyingGlassIcon className="w-4 h-4 absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400" />
                <input
                  type="text"
                  className="input !pr-8 !py-2 text-xs"
                  placeholder="بحث..."
                  value={search}
                  onChange={(e) => setSearch(e.target.value)}
                  autoFocus
                />
              </div>
            </div>
            <ul className="overflow-y-auto max-h-48">
              <li>
                <button
                  type="button"
                  className="w-full text-right px-3 py-2 text-sm text-gray-400 hover:bg-gray-50"
                  onClick={() => { onChange(''); setOpen(false); setSearch(''); }}
                >
                  — بدون —
                </button>
              </li>
              {filtered.map((o) => (
                <li key={o.id}>
                  <button
                    type="button"
                    className={`w-full text-right px-3 py-2 text-sm hover:bg-primary-50 transition-colors ${
                      String(o.id) === String(value) ? 'bg-primary-50 text-primary-700 font-medium' : 'text-gray-700'
                    }`}
                    onClick={() => { onChange(o.id); setOpen(false); setSearch(''); }}
                  >
                    {o.name}
                  </button>
                </li>
              ))}
              {filtered.length === 0 && (
                <li className="px-3 py-4 text-sm text-gray-400 text-center">لا توجد نتائج</li>
              )}
            </ul>
          </div>
        </>
      )}
    </div>
  );
}

export default function CaseForm() {
  const { id } = useParams();
  const navigate = useNavigate();
  const isEdit = Boolean(id);

  const [form, setForm] = useState(EMPTY_FORM);
  const [errors, setErrors] = useState({});
  const [lookups, setLookups] = useState({
    courts: [],
    lawyers: [],
    caseTypes: [],
    informAddresses: [],
    companies: [],
  });
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [caseData, setCaseData] = useState(null);

  useEffect(() => {
    let cancelled = false;

    async function load() {
      setLoading(true);
      try {
        const lookupsData = await fetchLookups();
        if (cancelled) return;
        setLookups((prev) => ({ ...prev, ...lookupsData }));

        if (isEdit) {
          const data = await fetchCase(id);
          if (cancelled) return;
          setCaseData(data);
          setForm({
            court_id: data.court_id || '',
            type_id: data.type_id || '',
            company_id: data.company_id || '',
            lawyer_id: data.lawyer_id || '',
            lawyer_cost: data.lawyer_cost ?? '',
            case_cost: data.case_cost ?? '',
            judiciary_number: data.judiciary_number || '',
            year: data.year || '',
            income_date: data.income_date ? data.income_date.slice(0, 10) : '',
            judiciary_inform_address_id: data.judiciary_inform_address_id || '',
            case_status: data.case_status || data.status || 'open',
            contract_id: data.contract_id || '',
          });
        }
      } catch (err) {
        if (!cancelled) toast.error(err.message || 'فشل تحميل البيانات');
      } finally {
        if (!cancelled) setLoading(false);
      }
    }

    load();
    return () => { cancelled = true; };
  }, [id, isEdit]);

  const handleChange = useCallback((field, value) => {
    setForm((prev) => ({ ...prev, [field]: value }));
    setErrors((prev) => {
      if (prev[field]) {
        const next = { ...prev };
        delete next[field];
        return next;
      }
      return prev;
    });
  }, []);

  const validate = useCallback(() => {
    const errs = {};
    for (const f of REQUIRED_FIELDS) {
      if (!form[f] && form[f] !== 0) errs[f] = 'حقل مطلوب';
    }
    setErrors(errs);
    return Object.keys(errs).length === 0;
  }, [form]);

  const handleSubmit = useCallback(async (e) => {
    e.preventDefault();
    if (!validate()) {
      toast.error('يرجى تعبئة الحقول المطلوبة');
      return;
    }

    setSaving(true);
    try {
      const payload = { ...form };
      if (payload.lawyer_cost !== '') payload.lawyer_cost = Number(payload.lawyer_cost);
      if (payload.case_cost !== '') payload.case_cost = Number(payload.case_cost);
      if (payload.judiciary_number) payload.judiciary_number = Number(payload.judiciary_number);
      if (payload.contract_id) payload.contract_id = Number(payload.contract_id);
      if (payload.year) payload.year = Number(payload.year);

      let result;
      if (isEdit) {
        result = await updateCase(id, payload);
        toast.success('تم تحديث القضية بنجاح');
      } else {
        result = await createCase(payload);
        toast.success('تم إنشاء القضية بنجاح');
      }

      const caseId = result?.id || id;
      navigate(`/case/${caseId}`);
    } catch (err) {
      toast.error(err.message || 'فشل حفظ القضية');
    } finally {
      setSaving(false);
    }
  }, [form, validate, isEdit, id, navigate]);

  const parties = caseData?.parties || caseData?.customers || [];
  const pendingParties = parties.filter((p) => p.status === 'pending');

  if (loading) {
    return (
      <div className="space-y-4">
        <div className="card">
          <div className="flex items-center gap-3 mb-6">
            <div className="skeleton h-6 w-40" />
          </div>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
            {Array.from({ length: 8 }, (_, i) => (
              <div key={i}>
                <div className="skeleton h-4 w-24 mb-2" />
                <div className="skeleton h-10 w-full" />
              </div>
            ))}
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-4">
      {/* Breadcrumb */}
      <div className="flex items-center gap-2 text-sm text-gray-500">
        <Link to="/cases" className="hover:text-primary-600 transition-colors">القضايا</Link>
        <ChevronRightIcon className="w-3.5 h-3.5 rotate-180" />
        <span className="text-gray-800 font-medium">
          {isEdit ? `تعديل القضية #${caseData?.judiciary_number || id}` : 'إضافة قضية جديدة'}
        </span>
      </div>

      {/* Form */}
      <form onSubmit={handleSubmit} className="card">
        <h2 className="section-title mb-6">
          {isEdit ? 'تعديل بيانات القضية' : 'إضافة قضية جديدة'}
        </h2>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
          {/* المحكمة */}
          <SearchableSelect
            label="المحكمة"
            value={form.court_id}
            onChange={(v) => handleChange('court_id', v)}
            options={lookups.courts}
            placeholder="اختر المحكمة..."
            required
          />

          {/* نوع القضية */}
          <div>
            <label className="label">
              نوع القضية
              <span className="text-red-500 mr-1">*</span>
            </label>
            <select
              className={`select ${errors.type_id ? 'border-red-300 ring-1 ring-red-300' : ''}`}
              value={form.type_id}
              onChange={(e) => handleChange('type_id', e.target.value)}
            >
              <option value="">اختر نوع القضية...</option>
              {lookups.caseTypes.map((t) => (
                <option key={t.id} value={t.id}>{t.name}</option>
              ))}
            </select>
            {errors.type_id && <p className="text-red-500 text-xs mt-1">{errors.type_id}</p>}
          </div>

          {/* الشركة */}
          <div>
            <label className="label">الشركة</label>
            <select
              className="select"
              value={form.company_id}
              onChange={(e) => handleChange('company_id', e.target.value)}
            >
              <option value="">— اختياري —</option>
              {(lookups.companies || []).map((c) => (
                <option key={c.id} value={c.id}>{c.name}</option>
              ))}
            </select>
          </div>

          {/* المحامي */}
          <SearchableSelect
            label="المحامي"
            value={form.lawyer_id}
            onChange={(v) => handleChange('lawyer_id', v)}
            options={lookups.lawyers}
            placeholder="اختر المحامي..."
            required
          />

          {/* أتعاب المحامي */}
          <div>
            <label className="label">أتعاب المحامي</label>
            <input
              type="number"
              className="input"
              placeholder="0.00"
              min="0"
              step="0.01"
              value={form.lawyer_cost}
              onChange={(e) => handleChange('lawyer_cost', e.target.value)}
            />
          </div>

          {/* رسوم القضية */}
          <div>
            <label className="label">رسوم القضية</label>
            <input
              type="number"
              className="input"
              placeholder="0.00"
              min="0"
              step="0.01"
              value={form.case_cost}
              onChange={(e) => handleChange('case_cost', e.target.value)}
            />
          </div>

          {/* رقم الدعوى */}
          <div>
            <label className="label">
              رقم الدعوى
              <span className="text-red-500 mr-1">*</span>
            </label>
            <input
              type="number"
              className={`input ${errors.judiciary_number ? 'border-red-300 ring-1 ring-red-300' : ''}`}
              placeholder="أدخل رقم الدعوى..."
              value={form.judiciary_number}
              onChange={(e) => handleChange('judiciary_number', e.target.value)}
            />
            {errors.judiciary_number && <p className="text-red-500 text-xs mt-1">{errors.judiciary_number}</p>}
          </div>

          {/* السنة */}
          <div>
            <label className="label">
              السنة
              <span className="text-red-500 mr-1">*</span>
            </label>
            <select
              className={`select ${errors.year ? 'border-red-300 ring-1 ring-red-300' : ''}`}
              value={form.year}
              onChange={(e) => handleChange('year', e.target.value)}
            >
              <option value="">اختر السنة...</option>
              {YEARS.map((y) => (
                <option key={y} value={y}>{y}</option>
              ))}
            </select>
            {errors.year && <p className="text-red-500 text-xs mt-1">{errors.year}</p>}
          </div>

          {/* تاريخ الورود */}
          <div>
            <label className="label">تاريخ الورود</label>
            <input
              type="date"
              className="input"
              value={form.income_date}
              onChange={(e) => handleChange('income_date', e.target.value)}
            />
          </div>

          {/* عنوان التبليغ */}
          <div>
            <label className="label">عنوان التبليغ</label>
            <select
              className="select"
              value={form.judiciary_inform_address_id}
              onChange={(e) => handleChange('judiciary_inform_address_id', e.target.value)}
            >
              <option value="">اختر عنوان التبليغ...</option>
              {(lookups.informAddresses || []).map((a) => (
                <option key={a.id} value={a.id}>{a.name || a.address}</option>
              ))}
            </select>
          </div>

          {/* حالة القضية - edit only */}
          {isEdit && (
            <div>
              <label className="label">حالة القضية</label>
              <select
                className="select"
                value={form.case_status}
                onChange={(e) => handleChange('case_status', e.target.value)}
              >
                {CASE_STATUS_OPTIONS.map((s) => (
                  <option key={s.value} value={s.value}>{s.label}</option>
                ))}
              </select>
            </div>
          )}

          {/* رقم العقد */}
          <div>
            <label className="label">
              رقم العقد
              <span className="text-red-500 mr-1">*</span>
            </label>
            <input
              type="number"
              className={`input ${errors.contract_id ? 'border-red-300 ring-1 ring-red-300' : ''}`}
              placeholder="أدخل رقم العقد..."
              value={form.contract_id}
              onChange={(e) => handleChange('contract_id', e.target.value)}
            />
            {errors.contract_id && <p className="text-red-500 text-xs mt-1">{errors.contract_id}</p>}
          </div>
        </div>

        {/* Actions */}
        <div className="flex items-center gap-3 mt-8 pt-5 border-t border-gray-100">
          <button
            type="submit"
            className="btn-primary"
            disabled={saving}
          >
            {saving ? (
              <ArrowPathIcon className="w-4 h-4 animate-spin" />
            ) : (
              <CheckIcon className="w-4 h-4" />
            )}
            {saving ? 'جارٍ الحفظ...' : 'حفظ'}
          </button>
          <button
            type="button"
            className="btn-secondary"
            onClick={() => navigate(isEdit ? `/case/${id}` : '/cases')}
            disabled={saving}
          >
            <XMarkIcon className="w-4 h-4" />
            إلغاء
          </button>
        </div>
      </form>

      {/* Party actions - edit mode only */}
      {isEdit && pendingParties.length > 0 && (
        <div className="card">
          <h3 className="section-title mb-4">طلبات الأطراف المعلقة</h3>
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead className="table-header">
                <tr>
                  <th className="px-4 py-3 text-right font-semibold text-gray-600">الاسم</th>
                  <th className="px-4 py-3 text-right font-semibold text-gray-600">النوع</th>
                  <th className="px-4 py-3 text-right font-semibold text-gray-600">التاريخ</th>
                  <th className="px-4 py-3 text-center font-semibold text-gray-600">إجراء</th>
                </tr>
              </thead>
              <tbody>
                {pendingParties.map((p, i) => (
                  <tr key={p.id || i} className="table-row">
                    <td className="px-4 py-3 text-gray-800">{p.name || p.customer_name}</td>
                    <td className="px-4 py-3">
                      <span className={`badge text-xs ${
                        p.type === 'guarantor' || p.role === 'guarantor'
                          ? 'bg-amber-100 text-amber-700'
                          : 'bg-primary-100 text-primary-700'
                      }`}>
                        {p.type === 'guarantor' || p.role === 'guarantor' ? 'ضامن' : 'عميل'}
                      </span>
                    </td>
                    <td className="px-4 py-3 text-gray-600">{formatDate(p.created_at)}</td>
                    <td className="px-4 py-3">
                      <div className="flex items-center justify-center gap-2">
                        <button
                          type="button"
                          className="btn-icon !w-8 !h-8 hover:!bg-green-50 hover:!text-green-600"
                          title="قبول"
                        >
                          <CheckCircleIcon className="w-5 h-5" />
                        </button>
                        <button
                          type="button"
                          className="btn-icon !w-8 !h-8 hover:!bg-red-50 hover:!text-red-600"
                          title="رفض"
                        >
                          <XCircleIcon className="w-5 h-5" />
                        </button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}
    </div>
  );
}
