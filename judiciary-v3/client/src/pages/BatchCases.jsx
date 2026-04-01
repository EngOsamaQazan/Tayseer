import { useState, useEffect, useCallback } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { fetchLookups, batchCreateCases, fetchLegal } from '../api';
import { YEARS } from '../utils/constants';
import { formatCurrency } from '../utils/helpers';
import toast from 'react-hot-toast';
import {
  ArrowPathIcon,
  PlusIcon,
  TrashIcon,
  ChevronRightIcon,
  DocumentDuplicateIcon,
  CheckIcon,
  InboxIcon,
  XMarkIcon,
} from '@heroicons/react/24/outline';

export default function BatchCases() {
  const navigate = useNavigate();

  const [lookups, setLookups] = useState({
    courts: [],
    lawyers: [],
    caseTypes: [],
    informAddresses: [],
    companies: [],
  });
  const [lookupsLoading, setLookupsLoading] = useState(true);

  const [shared, setShared] = useState({
    court_id: '',
    lawyer_id: '',
    type_id: '',
    judiciary_inform_address_id: '',
    company_id: '',
    year: '',
    lawyer_percentage: '',
  });

  const [contracts, setContracts] = useState([]);
  const [contractInput, setContractInput] = useState('');
  const [addingContract, setAddingContract] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [result, setResult] = useState(null);

  useEffect(() => {
    let cancelled = false;
    setLookupsLoading(true);
    fetchLookups()
      .then((res) => {
        if (!cancelled) setLookups((prev) => ({ ...prev, ...res }));
      })
      .catch(() => {
        if (!cancelled) toast.error('فشل تحميل البيانات المرجعية');
      })
      .finally(() => {
        if (!cancelled) setLookupsLoading(false);
      });
    return () => { cancelled = true; };
  }, []);

  const handleSharedChange = useCallback((field, value) => {
    setShared((prev) => ({ ...prev, [field]: value }));
    if (field === 'lawyer_percentage') {
      const pct = parseFloat(value) || 0;
      setContracts((prev) =>
        prev.map((c) => ({
          ...c,
          lawyer_cost: Math.round(((c.remaining || 0) * pct) / 100 * 100) / 100,
        }))
      );
    }
  }, []);

  const addContract = useCallback(async () => {
    const id = contractInput.trim();
    if (!id) return;
    if (contracts.some((c) => String(c.contract_id) === id)) {
      toast.error('العقد مضاف بالفعل');
      return;
    }

    setAddingContract(true);
    try {
      const res = await fetchLegal({ contract_id: id });
      const list = Array.isArray(res) ? res : res?.data || res?.rows || [];
      if (list.length === 0) {
        toast.error(`لم يتم العثور على عقد رقم ${id}`);
        return;
      }
      const item = list[0];
      const remaining = (item.total || item.contract_total || 0) - (item.paid || item.total_paid || 0);
      const pct = parseFloat(shared.lawyer_percentage) || 0;

      setContracts((prev) => [
        ...prev,
        {
          contract_id: item.contract_id || item.id || id,
          customer_name: item.customer_name || item.party_name || '—',
          sale_date: item.sale_date || item.contract_date || '',
          total: item.total || item.contract_total || 0,
          paid: item.paid || item.total_paid || 0,
          remaining,
          lawyer_cost: Math.round((remaining * pct) / 100 * 100) / 100,
        },
      ]);
      setContractInput('');
    } catch (err) {
      toast.error(err.message || 'فشل جلب بيانات العقد');
    } finally {
      setAddingContract(false);
    }
  }, [contractInput, contracts, shared.lawyer_percentage]);

  const removeContract = useCallback((idx) => {
    setContracts((prev) => prev.filter((_, i) => i !== idx));
  }, []);

  const handleContractKeyDown = useCallback((e) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      addContract();
    }
  }, [addContract]);

  const totalRemaining = contracts.reduce((s, c) => s + (c.remaining || 0), 0);
  const totalLawyerCost = contracts.reduce((s, c) => s + (c.lawyer_cost || 0), 0);

  const canSubmit =
    shared.court_id &&
    shared.lawyer_id &&
    shared.type_id &&
    shared.year &&
    contracts.length > 0;

  const handleSubmit = useCallback(async () => {
    if (!canSubmit) {
      toast.error('يرجى تعبئة البيانات المشتركة وإضافة عقد واحد على الأقل');
      return;
    }

    setSubmitting(true);
    try {
      const payload = {
        shared: {
          court_id: shared.court_id,
          lawyer_id: shared.lawyer_id,
          type_id: shared.type_id,
          judiciary_inform_address_id: shared.judiciary_inform_address_id || undefined,
          company_id: shared.company_id || undefined,
          year: Number(shared.year),
        },
        contracts: contracts.map((c) => ({
          contract_id: c.contract_id,
          lawyer_cost: c.lawyer_cost,
        })),
      };

      const res = await batchCreateCases(payload);
      setResult(res);
      toast.success(`تم إنشاء ${res?.created || contracts.length} قضية بنجاح`);
    } catch (err) {
      toast.error(err.message || 'فشل إنشاء القضايا');
    } finally {
      setSubmitting(false);
    }
  }, [canSubmit, shared, contracts]);

  if (result) {
    return (
      <div className="space-y-4">
        <div className="flex items-center gap-2 text-sm text-gray-500">
          <Link to="/cases" className="hover:text-primary-600 transition-colors">القضايا</Link>
          <ChevronRightIcon className="w-3.5 h-3.5 rotate-180" />
          <span className="text-gray-800 font-medium">نتيجة الإدخال المجمّع</span>
        </div>

        <div className="card text-center py-12">
          <div className="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center mx-auto mb-4">
            <CheckIcon className="w-8 h-8 text-green-600" />
          </div>
          <h2 className="text-xl font-bold text-gray-900 mb-2">تم إنشاء القضايا بنجاح</h2>
          <p className="text-gray-500 mb-6">
            تم إنشاء {result?.created || contracts.length} قضية من أصل {contracts.length} عقد
          </p>

          {result?.errors?.length > 0 && (
            <div className="card border-red-200 bg-red-50 text-right mb-6 max-w-lg mx-auto">
              <h4 className="text-sm font-semibold text-red-700 mb-2">أخطاء ({result.errors.length})</h4>
              <ul className="text-xs text-red-600 space-y-1">
                {result.errors.map((err, i) => (
                  <li key={i}>عقد {err.contract_id}: {err.message}</li>
                ))}
              </ul>
            </div>
          )}

          <div className="flex items-center justify-center gap-3">
            <Link to="/cases" className="btn-primary">
              <DocumentDuplicateIcon className="w-4 h-4" />
              عرض القضايا
            </Link>
            <button
              className="btn-secondary"
              onClick={() => {
                setResult(null);
                setContracts([]);
              }}
            >
              إدخال دفعة جديدة
            </button>
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
        <span className="text-gray-800 font-medium">إدخال قضايا دفعة واحدة</span>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-5 gap-4">
        {/* Left Panel - Shared Fields */}
        <div className="lg:col-span-2">
          <div className="card sticky top-4">
            <h2 className="section-title mb-5">البيانات المشتركة</h2>

            <div className="space-y-4">
              {/* المحكمة */}
              <div>
                <label className="label">
                  المحكمة
                  <span className="text-red-500 mr-1">*</span>
                </label>
                <select
                  className="select"
                  value={shared.court_id}
                  onChange={(e) => handleSharedChange('court_id', e.target.value)}
                  disabled={lookupsLoading}
                >
                  <option value="">اختر المحكمة...</option>
                  {lookups.courts.map((c) => (
                    <option key={c.id} value={c.id}>{c.name}</option>
                  ))}
                </select>
              </div>

              {/* المحامي */}
              <div>
                <label className="label">
                  المحامي
                  <span className="text-red-500 mr-1">*</span>
                </label>
                <select
                  className="select"
                  value={shared.lawyer_id}
                  onChange={(e) => handleSharedChange('lawyer_id', e.target.value)}
                  disabled={lookupsLoading}
                >
                  <option value="">اختر المحامي...</option>
                  {lookups.lawyers.map((l) => (
                    <option key={l.id} value={l.id}>{l.name}</option>
                  ))}
                </select>
              </div>

              {/* نوع القضية */}
              <div>
                <label className="label">
                  نوع القضية
                  <span className="text-red-500 mr-1">*</span>
                </label>
                <select
                  className="select"
                  value={shared.type_id}
                  onChange={(e) => handleSharedChange('type_id', e.target.value)}
                  disabled={lookupsLoading}
                >
                  <option value="">اختر النوع...</option>
                  {lookups.caseTypes.map((t) => (
                    <option key={t.id} value={t.id}>{t.name}</option>
                  ))}
                </select>
              </div>

              {/* عنوان التبليغ */}
              <div>
                <label className="label">عنوان التبليغ</label>
                <select
                  className="select"
                  value={shared.judiciary_inform_address_id}
                  onChange={(e) => handleSharedChange('judiciary_inform_address_id', e.target.value)}
                  disabled={lookupsLoading}
                >
                  <option value="">اختر...</option>
                  {(lookups.informAddresses || []).map((a) => (
                    <option key={a.id} value={a.id}>{a.name || a.address}</option>
                  ))}
                </select>
              </div>

              {/* الشركة */}
              <div>
                <label className="label">الشركة</label>
                <select
                  className="select"
                  value={shared.company_id}
                  onChange={(e) => handleSharedChange('company_id', e.target.value)}
                  disabled={lookupsLoading}
                >
                  <option value="">— اختياري —</option>
                  {(lookups.companies || []).map((c) => (
                    <option key={c.id} value={c.id}>{c.name}</option>
                  ))}
                </select>
              </div>

              {/* السنة */}
              <div>
                <label className="label">
                  السنة
                  <span className="text-red-500 mr-1">*</span>
                </label>
                <select
                  className="select"
                  value={shared.year}
                  onChange={(e) => handleSharedChange('year', e.target.value)}
                >
                  <option value="">اختر السنة...</option>
                  {YEARS.map((y) => (
                    <option key={y} value={y}>{y}</option>
                  ))}
                </select>
              </div>

              {/* نسبة المحامي */}
              <div>
                <label className="label">نسبة المحامي (%)</label>
                <input
                  type="number"
                  className="input"
                  placeholder="مثال: 10"
                  min="0"
                  max="100"
                  step="0.5"
                  value={shared.lawyer_percentage}
                  onChange={(e) => handleSharedChange('lawyer_percentage', e.target.value)}
                />
                {shared.lawyer_percentage && (
                  <p className="text-xs text-gray-400 mt-1">
                    أتعاب المحامي = المتبقي × {shared.lawyer_percentage}%
                  </p>
                )}
              </div>
            </div>
          </div>
        </div>

        {/* Right Panel - Contracts */}
        <div className="lg:col-span-3 space-y-4">
          <div className="card">
            <div className="flex items-center justify-between mb-4">
              <h2 className="section-title">العقود المختارة</h2>
              <span className="badge bg-primary-100 text-primary-700">
                {contracts.length} عقد
              </span>
            </div>

            {/* Add contract input */}
            <div className="flex items-center gap-2 mb-4">
              <input
                type="number"
                className="input flex-1"
                placeholder="أدخل رقم العقد..."
                value={contractInput}
                onChange={(e) => setContractInput(e.target.value)}
                onKeyDown={handleContractKeyDown}
                disabled={addingContract}
              />
              <button
                type="button"
                className="btn-primary shrink-0"
                onClick={addContract}
                disabled={addingContract || !contractInput.trim()}
              >
                {addingContract ? (
                  <ArrowPathIcon className="w-4 h-4 animate-spin" />
                ) : (
                  <PlusIcon className="w-4 h-4" />
                )}
                إضافة
              </button>
            </div>

            {/* Table */}
            {contracts.length === 0 ? (
              <div className="empty-state py-12">
                <InboxIcon className="w-12 h-12 mb-3 text-gray-300" />
                <p className="text-sm text-gray-500">لم يتم إضافة عقود بعد</p>
                <p className="text-xs text-gray-400 mt-1">أدخل رقم العقد أعلاه للبدء</p>
              </div>
            ) : (
              <div className="overflow-x-auto">
                <table className="w-full text-sm">
                  <thead className="table-header">
                    <tr>
                      <th className="px-3 py-2.5 text-right font-semibold text-gray-600 whitespace-nowrap">رقم العقد</th>
                      <th className="px-3 py-2.5 text-right font-semibold text-gray-600 whitespace-nowrap">اسم العميل</th>
                      <th className="px-3 py-2.5 text-right font-semibold text-gray-600 whitespace-nowrap">الإجمالي</th>
                      <th className="px-3 py-2.5 text-right font-semibold text-gray-600 whitespace-nowrap">المدفوع</th>
                      <th className="px-3 py-2.5 text-right font-semibold text-gray-600 whitespace-nowrap">المتبقي</th>
                      <th className="px-3 py-2.5 text-right font-semibold text-gray-600 whitespace-nowrap">أتعاب المحامي</th>
                      <th className="px-3 py-2.5 w-10"></th>
                    </tr>
                  </thead>
                  <tbody>
                    {contracts.map((c, i) => (
                      <tr key={`${c.contract_id}-${i}`} className="table-row">
                        <td className="px-3 py-2.5 font-mono font-semibold text-primary-700">{c.contract_id}</td>
                        <td className="px-3 py-2.5 text-gray-800 whitespace-nowrap">{c.customer_name}</td>
                        <td className="px-3 py-2.5 text-gray-600 whitespace-nowrap">{formatCurrency(c.total)}</td>
                        <td className="px-3 py-2.5 text-gray-600 whitespace-nowrap">{formatCurrency(c.paid)}</td>
                        <td className="px-3 py-2.5 font-semibold text-gray-800 whitespace-nowrap">{formatCurrency(c.remaining)}</td>
                        <td className="px-3 py-2.5 font-semibold text-amber-700 whitespace-nowrap">{formatCurrency(c.lawyer_cost)}</td>
                        <td className="px-3 py-2.5">
                          <button
                            type="button"
                            className="btn-icon !w-7 !h-7 hover:!bg-red-50 hover:!text-red-600"
                            onClick={() => removeContract(i)}
                            title="إزالة"
                          >
                            <TrashIcon className="w-4 h-4" />
                          </button>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                  <tfoot>
                    <tr className="bg-gray-50 border-t-2 border-gray-200">
                      <td className="px-3 py-3 font-bold text-gray-800" colSpan={2}>
                        الإجمالي ({contracts.length} عقد)
                      </td>
                      <td className="px-3 py-3" colSpan={2}></td>
                      <td className="px-3 py-3 font-bold text-gray-900 whitespace-nowrap">
                        {formatCurrency(totalRemaining)}
                      </td>
                      <td className="px-3 py-3 font-bold text-amber-700 whitespace-nowrap">
                        {formatCurrency(totalLawyerCost)}
                      </td>
                      <td></td>
                    </tr>
                  </tfoot>
                </table>
              </div>
            )}
          </div>

          {/* Submit */}
          <div className="flex items-center gap-3">
            <button
              type="button"
              className="btn-primary"
              disabled={!canSubmit || submitting}
              onClick={handleSubmit}
            >
              {submitting ? (
                <ArrowPathIcon className="w-4 h-4 animate-spin" />
              ) : (
                <DocumentDuplicateIcon className="w-4 h-4" />
              )}
              {submitting ? 'جارٍ الإنشاء...' : `إنشاء ${contracts.length} قضية`}
            </button>
            <button
              type="button"
              className="btn-secondary"
              onClick={() => navigate('/cases')}
              disabled={submitting}
            >
              <XMarkIcon className="w-4 h-4" />
              إلغاء
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}
