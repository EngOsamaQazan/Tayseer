import { useState, useEffect, useCallback, useMemo } from 'react';
import { Link } from 'react-router-dom';
import { fetchLookups, batchCreateActions, parseCaseNumbers } from '../api';
import { STAGES, STAGE_LABELS, ACTION_NATURE_CONFIG } from '../utils/constants';
import toast from 'react-hot-toast';
import {
  ArrowPathIcon,
  ChevronRightIcon,
  ChevronLeftIcon,
  ClipboardDocumentListIcon,
  TableCellsIcon,
  RocketLaunchIcon,
  CheckIcon,
  CheckCircleIcon,
  ExclamationTriangleIcon,
  XCircleIcon,
  DocumentTextIcon,
  InboxIcon,
} from '@heroicons/react/24/outline';

const STEP_LABELS = ['لصق أرقام الدعاوى', 'مراجعة وتحرير', 'تنفيذ'];
const STEP_ICONS = [ClipboardDocumentListIcon, TableCellsIcon, RocketLaunchIcon];

function StepIndicator({ current }) {
  return (
    <div className="flex items-center justify-center gap-2 mb-6">
      {STEP_LABELS.map((label, i) => {
        const Icon = STEP_ICONS[i];
        const isActive = i === current;
        const isDone = i < current;
        return (
          <div key={i} className="flex items-center gap-2">
            {i > 0 && (
              <div className={`h-0.5 w-8 sm:w-16 rounded ${isDone ? 'bg-primary-500' : 'bg-gray-200'}`} />
            )}
            <div className="flex flex-col items-center gap-1">
              <div
                className={`w-10 h-10 rounded-full flex items-center justify-center transition-all ${
                  isActive
                    ? 'bg-primary-600 text-white shadow-md shadow-primary-200'
                    : isDone
                    ? 'bg-primary-100 text-primary-700'
                    : 'bg-gray-100 text-gray-400'
                }`}
              >
                {isDone ? <CheckIcon className="w-5 h-5" /> : <Icon className="w-5 h-5" />}
              </div>
              <span
                className={`text-xs font-medium whitespace-nowrap hidden sm:block ${
                  isActive ? 'text-primary-700' : isDone ? 'text-primary-600' : 'text-gray-400'
                }`}
              >
                {label}
              </span>
            </div>
          </div>
        );
      })}
    </div>
  );
}

export default function BatchActions() {
  const [step, setStep] = useState(0);
  const [rawText, setRawText] = useState('');
  const [rows, setRows] = useState([]);
  const [selected, setSelected] = useState(new Set());
  const [lookups, setLookups] = useState({ courts: [], lawyers: [], caseTypes: [], actionCatalog: [] });
  const [parsing, setParsing] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [progress, setProgress] = useState(0);
  const [execResult, setExecResult] = useState(null);

  // bulk action toolbar
  const [bulkAction, setBulkAction] = useState('');
  const [bulkDate, setBulkDate] = useState('');
  const [bulkNotes, setBulkNotes] = useState('');

  useEffect(() => {
    let cancelled = false;
    fetchLookups()
      .then((res) => { if (!cancelled) setLookups((prev) => ({ ...prev, ...res })); })
      .catch(() => { if (!cancelled) toast.error('فشل تحميل البيانات المرجعية'); });
    return () => { cancelled = true; };
  }, []);

  const actionsByStage = useMemo(() => {
    const catalog = lookups.actionCatalog || [];
    const groups = {};
    for (const a of catalog) {
      const stage = a.stage || 'general';
      if (!groups[stage]) groups[stage] = [];
      groups[stage].push(a);
    }
    return groups;
  }, [lookups.actionCatalog]);

  const flatActions = useMemo(() => lookups.actionCatalog || [], [lookups.actionCatalog]);

  // ─── Step 1: Parse ───────────────────────────────
  const handleParse = useCallback(async () => {
    if (!rawText.trim()) {
      toast.error('يرجى لصق أرقام الدعاوى أولاً');
      return;
    }

    setParsing(true);
    try {
      const res = await parseCaseNumbers(rawText);
      const parsed = Array.isArray(res) ? res : res?.results || res?.data || [];

      if (parsed.length === 0) {
        const lines = rawText
          .split(/[\n,،\t]+/)
          .map((l) => l.trim())
          .filter(Boolean);
        const localRows = lines.map((line) => ({
          original: line,
          matched: false,
          case_id: null,
          judiciary_number: '',
          year: '',
          court_name: '',
          contract_id: '',
          party_name: '',
          party_type: '',
          action_id: '',
          action_date: '',
          notes: '',
        }));
        setRows(localRows);
        setSelected(new Set(localRows.map((_, i) => i)));
      } else {
        const mapped = parsed.map((p) => ({
          original: p.original || p.text || '',
          matched: p.matched ?? Boolean(p.case_id),
          case_id: p.case_id || null,
          judiciary_number: p.judiciary_number || p.case_number || '',
          year: p.year || '',
          court_name: p.court_name || p.court || '',
          contract_id: p.contract_id || p.contract_number || '',
          party_name: p.party_name || p.customer_name || '',
          party_type: p.party_type || p.role || '',
          action_id: '',
          action_date: '',
          notes: '',
        }));
        setRows(mapped);
        setSelected(new Set(mapped.filter((r) => r.matched).map((_, i) => i)));
      }

      setStep(1);
    } catch (err) {
      toast.error(err.message || 'فشل تحليل الأرقام');
    } finally {
      setParsing(false);
    }
  }, [rawText]);

  // ─── Step 2: Row editing ─────────────────────────
  const updateRow = useCallback((idx, field, value) => {
    setRows((prev) => {
      const next = [...prev];
      next[idx] = { ...next[idx], [field]: value };
      return next;
    });
  }, []);

  const toggleSelect = useCallback((idx) => {
    setSelected((prev) => {
      const next = new Set(prev);
      if (next.has(idx)) next.delete(idx);
      else next.add(idx);
      return next;
    });
  }, []);

  const toggleAll = useCallback(() => {
    if (selected.size === rows.length) {
      setSelected(new Set());
    } else {
      setSelected(new Set(rows.map((_, i) => i)));
    }
  }, [selected, rows]);

  const applyBulkAction = useCallback(() => {
    if (!bulkAction) return;
    setRows((prev) =>
      prev.map((r, i) => (selected.has(i) ? { ...r, action_id: bulkAction } : r))
    );
    toast.success('تم تطبيق الإجراء على الصفوف المحددة');
  }, [bulkAction, selected]);

  const applyBulkDate = useCallback(() => {
    if (!bulkDate) return;
    setRows((prev) =>
      prev.map((r, i) => (selected.has(i) ? { ...r, action_date: bulkDate } : r))
    );
    toast.success('تم تطبيق التاريخ على الصفوف المحددة');
  }, [bulkDate, selected]);

  const applyBulkNotes = useCallback(() => {
    if (!bulkNotes) return;
    setRows((prev) =>
      prev.map((r, i) => (selected.has(i) ? { ...r, notes: bulkNotes } : r))
    );
    toast.success('تم تطبيق الملاحظات على الصفوف المحددة');
  }, [bulkNotes, selected]);

  const matchedCount = rows.filter((r) => r.matched).length;
  const unmatchedCount = rows.length - matchedCount;

  // ─── Step 3: Execute ─────────────────────────────
  const handleExecute = useCallback(async () => {
    const selectedRows = rows.filter((_, i) => selected.has(i));
    const validRows = selectedRows.filter((r) => r.matched && r.action_id);

    if (validRows.length === 0) {
      toast.error('لا توجد صفوف صالحة للتنفيذ');
      return;
    }

    setSubmitting(true);
    setProgress(0);
    setStep(2);

    try {
      const payload = validRows.map((r) => ({
        case_id: r.case_id,
        action_id: r.action_id,
        action_date: r.action_date || undefined,
        notes: r.notes || undefined,
      }));

      const interval = setInterval(() => {
        setProgress((p) => Math.min(p + Math.random() * 15, 90));
      }, 300);

      const res = await batchCreateActions({ actions: payload });

      clearInterval(interval);
      setProgress(100);
      setExecResult({
        total: validRows.length,
        created: res?.created || res?.success || validRows.length,
        errors: res?.errors || [],
        skipped: selectedRows.length - validRows.length,
        details: res?.details || [],
      });
      toast.success('تم تنفيذ الإجراءات بنجاح');
    } catch (err) {
      setExecResult({
        total: validRows.length,
        created: 0,
        errors: [{ message: err.message }],
        skipped: 0,
        details: [],
      });
      toast.error(err.message || 'فشل تنفيذ الإجراءات');
    } finally {
      setSubmitting(false);
    }
  }, [rows, selected]);

  const canGoNext =
    (step === 0 && rawText.trim().length > 0) ||
    (step === 1 && selected.size > 0);

  return (
    <div className="space-y-4">
      {/* Breadcrumb */}
      <div className="flex items-center gap-2 text-sm text-gray-500">
        <Link to="/cases" className="hover:text-primary-600 transition-colors">القضايا</Link>
        <ChevronRightIcon className="w-3.5 h-3.5 rotate-180" />
        <span className="text-gray-800 font-medium">إدخال إجراءات دفعة واحدة</span>
      </div>

      <div className="card">
        <StepIndicator current={step} />

        {/* ═══ Step 1: Paste ═══ */}
        {step === 0 && (
          <div>
            <h2 className="section-title mb-1">لصق أرقام الدعاوى</h2>
            <p className="text-sm text-gray-500 mb-4">
              الصق أرقام الدعاوى مفصولة بسطر جديد أو فاصلة
            </p>

            <textarea
              className="input min-h-[240px] font-mono text-sm leading-relaxed resize-y"
              placeholder={"12345\n67890\n11111, 22222, 33333"}
              value={rawText}
              onChange={(e) => setRawText(e.target.value)}
              dir="ltr"
            />

            <div className="flex items-center justify-between mt-4">
              <p className="text-xs text-gray-400">
                {rawText.trim()
                  ? `${rawText.split(/[\n,،\t]+/).filter((l) => l.trim()).length} رقم مدخل`
                  : 'لم يتم إدخال أي أرقام'}
              </p>
              <button
                className="btn-primary"
                onClick={handleParse}
                disabled={parsing || !rawText.trim()}
              >
                {parsing ? (
                  <ArrowPathIcon className="w-4 h-4 animate-spin" />
                ) : (
                  <TableCellsIcon className="w-4 h-4" />
                )}
                {parsing ? 'جارٍ التحليل...' : 'تحليل'}
              </button>
            </div>
          </div>
        )}

        {/* ═══ Step 2: Review ═══ */}
        {step === 1 && (
          <div>
            <div className="flex items-center justify-between mb-4">
              <div>
                <h2 className="section-title mb-1">مراجعة وتحرير</h2>
                <div className="flex items-center gap-3 text-xs">
                  <span className="badge-success">{matchedCount} متطابق</span>
                  {unmatchedCount > 0 && (
                    <span className="badge-danger">{unmatchedCount} غير متطابق</span>
                  )}
                  <span className="badge-info">{selected.size} محدد</span>
                </div>
              </div>
            </div>

            {/* Bulk toolbar */}
            <div className="bg-gray-50 rounded-lg p-3 mb-4 space-y-3">
              <p className="text-xs font-semibold text-gray-600">تطبيق على المحدد ({selected.size})</p>
              <div className="flex flex-wrap items-end gap-3">
                {/* Bulk action */}
                <div className="flex-1 min-w-[180px]">
                  <label className="text-xs text-gray-500 mb-1 block">الإجراء</label>
                  <div className="flex gap-1.5">
                    <select
                      className="select text-xs flex-1"
                      value={bulkAction}
                      onChange={(e) => setBulkAction(e.target.value)}
                    >
                      <option value="">اختر إجراء...</option>
                      {Object.entries(actionsByStage).map(([stage, actions]) => (
                        <optgroup key={stage} label={STAGE_LABELS[stage] || stage}>
                          {actions.map((a) => (
                            <option key={a.id} value={a.id}>
                              {a.name || a.title}
                            </option>
                          ))}
                        </optgroup>
                      ))}
                    </select>
                    <button className="btn-secondary !py-1.5 !px-2.5 text-xs" onClick={applyBulkAction} disabled={!bulkAction}>
                      تطبيق
                    </button>
                  </div>
                </div>

                {/* Bulk date */}
                <div className="min-w-[160px]">
                  <label className="text-xs text-gray-500 mb-1 block">التاريخ</label>
                  <div className="flex gap-1.5">
                    <input
                      type="date"
                      className="input text-xs flex-1"
                      value={bulkDate}
                      onChange={(e) => setBulkDate(e.target.value)}
                    />
                    <button className="btn-secondary !py-1.5 !px-2.5 text-xs" onClick={applyBulkDate} disabled={!bulkDate}>
                      تطبيق
                    </button>
                  </div>
                </div>

                {/* Bulk notes */}
                <div className="min-w-[160px]">
                  <label className="text-xs text-gray-500 mb-1 block">ملاحظات</label>
                  <div className="flex gap-1.5">
                    <input
                      type="text"
                      className="input text-xs flex-1"
                      placeholder="ملاحظات..."
                      value={bulkNotes}
                      onChange={(e) => setBulkNotes(e.target.value)}
                    />
                    <button className="btn-secondary !py-1.5 !px-2.5 text-xs" onClick={applyBulkNotes} disabled={!bulkNotes}>
                      تطبيق
                    </button>
                  </div>
                </div>
              </div>
            </div>

            {/* Review table */}
            <div className="overflow-x-auto -mx-5 px-5">
              <table className="w-full text-xs">
                <thead className="table-header">
                  <tr>
                    <th className="px-2 py-2.5 w-8">
                      <input
                        type="checkbox"
                        className="w-4 h-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                        checked={selected.size === rows.length && rows.length > 0}
                        onChange={toggleAll}
                      />
                    </th>
                    <th className="px-2 py-2.5 text-right font-semibold text-gray-600 whitespace-nowrap">النص المدخل</th>
                    <th className="px-2 py-2.5 text-right font-semibold text-gray-600 whitespace-nowrap">رقم الدعوى</th>
                    <th className="px-2 py-2.5 text-right font-semibold text-gray-600 whitespace-nowrap">السنة</th>
                    <th className="px-2 py-2.5 text-right font-semibold text-gray-600 whitespace-nowrap">المحكمة</th>
                    <th className="px-2 py-2.5 text-right font-semibold text-gray-600 whitespace-nowrap">رقم العقد</th>
                    <th className="px-2 py-2.5 text-right font-semibold text-gray-600 whitespace-nowrap">الطرف</th>
                    <th className="px-2 py-2.5 text-right font-semibold text-gray-600 whitespace-nowrap min-w-[160px]">الإجراء</th>
                    <th className="px-2 py-2.5 text-right font-semibold text-gray-600 whitespace-nowrap min-w-[130px]">التاريخ</th>
                    <th className="px-2 py-2.5 text-right font-semibold text-gray-600 whitespace-nowrap min-w-[120px]">ملاحظات</th>
                    <th className="px-2 py-2.5 text-center font-semibold text-gray-600 whitespace-nowrap">الحالة</th>
                  </tr>
                </thead>
                <tbody>
                  {rows.map((row, i) => (
                    <tr
                      key={i}
                      className={`table-row ${
                        !row.matched ? 'bg-red-50/50' : selected.has(i) ? 'bg-primary-50/30' : ''
                      }`}
                    >
                      <td className="px-2 py-2">
                        <input
                          type="checkbox"
                          className="w-4 h-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                          checked={selected.has(i)}
                          onChange={() => toggleSelect(i)}
                        />
                      </td>
                      <td className="px-2 py-2 font-mono text-gray-600 max-w-[100px] truncate" title={row.original}>
                        {row.original}
                      </td>
                      <td className="px-2 py-2 font-mono font-semibold text-gray-800">{row.judiciary_number || '—'}</td>
                      <td className="px-2 py-2 text-gray-600">{row.year || '—'}</td>
                      <td className="px-2 py-2 text-gray-600 whitespace-nowrap">{row.court_name || '—'}</td>
                      <td className="px-2 py-2 text-gray-600">{row.contract_id || '—'}</td>
                      <td className="px-2 py-2 whitespace-nowrap">
                        {row.party_name ? (
                          <div className="flex items-center gap-1">
                            <span className={`badge text-[9px] px-1 py-0 ${
                              row.party_type === 'guarantor' ? 'bg-amber-100 text-amber-700' : 'bg-primary-100 text-primary-700'
                            }`}>
                              {row.party_type === 'guarantor' ? 'ضامن' : 'عميل'}
                            </span>
                            <span className="truncate max-w-[80px]">{row.party_name}</span>
                          </div>
                        ) : '—'}
                      </td>
                      <td className="px-2 py-2">
                        <select
                          className="select !py-1 !px-1.5 text-xs"
                          value={row.action_id}
                          onChange={(e) => updateRow(i, 'action_id', e.target.value)}
                        >
                          <option value="">اختر...</option>
                          {Object.entries(actionsByStage).map(([stage, actions]) => (
                            <optgroup key={stage} label={STAGE_LABELS[stage] || stage}>
                              {actions.map((a) => (
                                <option key={a.id} value={a.id}>
                                  {a.name || a.title}
                                </option>
                              ))}
                            </optgroup>
                          ))}
                        </select>
                      </td>
                      <td className="px-2 py-2">
                        <input
                          type="date"
                          className="input !py-1 !px-1.5 text-xs"
                          value={row.action_date}
                          onChange={(e) => updateRow(i, 'action_date', e.target.value)}
                        />
                      </td>
                      <td className="px-2 py-2">
                        <input
                          type="text"
                          className="input !py-1 !px-1.5 text-xs"
                          placeholder="ملاحظات..."
                          value={row.notes}
                          onChange={(e) => updateRow(i, 'notes', e.target.value)}
                        />
                      </td>
                      <td className="px-2 py-2 text-center">
                        {row.matched ? (
                          <CheckCircleIcon className="w-5 h-5 text-green-500 mx-auto" />
                        ) : (
                          <ExclamationTriangleIcon className="w-5 h-5 text-amber-500 mx-auto" />
                        )}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>

            {/* Navigation */}
            <div className="flex items-center justify-between mt-6 pt-4 border-t border-gray-100">
              <button className="btn-secondary" onClick={() => setStep(0)}>
                <ChevronRightIcon className="w-4 h-4" />
                السابق
              </button>
              <button
                className="btn-primary"
                onClick={handleExecute}
                disabled={selected.size === 0}
              >
                تنفيذ ({rows.filter((r, i) => selected.has(i) && r.matched && r.action_id).length})
                <ChevronLeftIcon className="w-4 h-4" />
              </button>
            </div>
          </div>
        )}

        {/* ═══ Step 3: Execute / Results ═══ */}
        {step === 2 && (
          <div>
            <h2 className="section-title mb-4 text-center">تنفيذ الإجراءات</h2>

            {/* Progress */}
            {submitting && (
              <div className="max-w-md mx-auto mb-8">
                <div className="flex items-center justify-between text-sm text-gray-600 mb-2">
                  <span>جارٍ التنفيذ...</span>
                  <span>{Math.round(progress)}%</span>
                </div>
                <div className="h-3 bg-gray-100 rounded-full overflow-hidden">
                  <div
                    className="h-full bg-primary-500 rounded-full transition-all duration-300"
                    style={{ width: `${progress}%` }}
                  />
                </div>
              </div>
            )}

            {/* Results */}
            {execResult && (
              <div className="space-y-6">
                {/* Summary cards */}
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 max-w-xl mx-auto">
                  <div className="text-center p-4 bg-green-50 rounded-xl">
                    <CheckCircleIcon className="w-8 h-8 text-green-500 mx-auto mb-1" />
                    <p className="text-2xl font-bold text-green-700">{execResult.created}</p>
                    <p className="text-xs text-green-600">تم الحفظ</p>
                  </div>
                  <div className="text-center p-4 bg-blue-50 rounded-xl">
                    <DocumentTextIcon className="w-8 h-8 text-blue-500 mx-auto mb-1" />
                    <p className="text-2xl font-bold text-blue-700">{execResult.total}</p>
                    <p className="text-xs text-blue-600">إجمالي القضايا</p>
                  </div>
                  <div className="text-center p-4 bg-red-50 rounded-xl">
                    <XCircleIcon className="w-8 h-8 text-red-500 mx-auto mb-1" />
                    <p className="text-2xl font-bold text-red-700">{execResult.errors?.length || 0}</p>
                    <p className="text-xs text-red-600">أخطاء</p>
                  </div>
                </div>

                {/* Error details */}
                {execResult.errors?.length > 0 && (
                  <div className="card border-red-200 bg-red-50 max-w-xl mx-auto">
                    <h4 className="text-sm font-semibold text-red-700 mb-2">تفاصيل الأخطاء</h4>
                    <ul className="text-xs text-red-600 space-y-1.5">
                      {execResult.errors.map((err, i) => (
                        <li key={i} className="flex items-start gap-1.5">
                          <XCircleIcon className="w-3.5 h-3.5 mt-0.5 shrink-0" />
                          <span>{err.case_id ? `قضية ${err.case_id}: ` : ''}{err.message}</span>
                        </li>
                      ))}
                    </ul>
                  </div>
                )}

                {/* Detail log */}
                {execResult.details?.length > 0 && (
                  <div className="max-w-xl mx-auto">
                    <h4 className="text-sm font-semibold text-gray-700 mb-2">سجل التنفيذ</h4>
                    <div className="space-y-1 max-h-60 overflow-y-auto">
                      {execResult.details.map((d, i) => (
                        <div
                          key={i}
                          className={`flex items-center gap-2 text-xs px-3 py-2 rounded-lg ${
                            d.success
                              ? 'bg-green-50 text-green-700'
                              : 'bg-red-50 text-red-700'
                          }`}
                        >
                          {d.success ? (
                            <CheckCircleIcon className="w-4 h-4 shrink-0" />
                          ) : (
                            <XCircleIcon className="w-4 h-4 shrink-0" />
                          )}
                          <span>
                            قضية {d.case_id || d.judiciary_number || i + 1}
                            {d.message ? ` — ${d.message}` : ''}
                          </span>
                        </div>
                      ))}
                    </div>
                  </div>
                )}

                {/* Actions */}
                <div className="flex items-center justify-center gap-3 pt-4">
                  <Link to="/cases" className="btn-primary">
                    <InboxIcon className="w-4 h-4" />
                    عرض القضايا
                  </Link>
                  <button
                    className="btn-secondary"
                    onClick={() => {
                      setStep(0);
                      setRawText('');
                      setRows([]);
                      setSelected(new Set());
                      setExecResult(null);
                      setProgress(0);
                    }}
                  >
                    <ArrowPathIcon className="w-4 h-4" />
                    دفعة جديدة
                  </button>
                </div>
              </div>
            )}
          </div>
        )}
      </div>
    </div>
  );
}
