import { useState, useEffect } from 'react';
import { useParams, Link, useNavigate } from 'react-router-dom';
import { fetchCase, updateActionStatus, deleteCase } from '../api';
import {
  STAGE_LABELS,
  STAGES,
  STATUS_COLORS,
  STATUS_LABELS,
  ACTION_NATURE_CONFIG,
  REQUEST_STATUS_CONFIG,
  ASSET_TYPE_LABELS,
  ASSET_STATUS_CONFIG,
  DEADLINE_TYPE_LABELS,
  DEADLINE_STATUS_CONFIG,
  CORRESPONDENCE_DIRECTION,
} from '../utils/constants';
import { formatDate, formatCurrency, timeAgo } from '../utils/helpers';
import toast from 'react-hot-toast';
import {
  ArrowRightIcon,
  PencilSquareIcon,
  ClockIcon,
  TrashIcon,
  CheckCircleIcon,
  XCircleIcon,
  ChevronDownIcon,
  ChevronUpIcon,
  ExclamationTriangleIcon,
  ArrowPathIcon,
  DocumentTextIcon,
  BanknotesIcon,
  BuildingLibraryIcon,
  UserGroupIcon,
  ShieldCheckIcon,
  CalendarDaysIcon,
  CubeIcon,
  EnvelopeIcon,
  ListBulletIcon,
  InboxIcon,
} from '@heroicons/react/24/outline';

const PIPELINE_STAGES = STAGES.filter(s => s.key !== 'general');

function SkeletonCard({ lines = 3 }) {
  return (
    <div className="card space-y-4 animate-pulse">
      <div className="skeleton h-5 w-1/3" />
      {Array.from({ length: lines }).map((_, i) => (
        <div key={i} className="skeleton h-4" style={{ width: `${80 - i * 15}%` }} />
      ))}
    </div>
  );
}

function EmptyState({ icon: Icon, text }) {
  return (
    <div className="flex flex-col items-center justify-center py-10 text-gray-400">
      <Icon className="w-10 h-10 mb-2" />
      <p className="text-sm">{text}</p>
    </div>
  );
}

function InfoRow({ label, value, className = '' }) {
  return (
    <div className={`flex items-start justify-between py-2 ${className}`}>
      <span className="text-sm text-gray-500 shrink-0">{label}</span>
      <span className="text-sm font-medium text-gray-900 text-left mr-3">{value || '—'}</span>
    </div>
  );
}

function getDaysRemaining(dateStr) {
  if (!dateStr) return null;
  const target = new Date(dateStr);
  const now = new Date();
  now.setHours(0, 0, 0, 0);
  target.setHours(0, 0, 0, 0);
  return Math.ceil((target - now) / 86400000);
}

function getDaysText(days) {
  if (days === null) return '';
  if (days === 0) return { text: 'اليوم', color: 'text-amber-600' };
  if (days < 0) return { text: `متأخر ${Math.abs(days)} يوم`, color: 'text-red-600' };
  if (days <= 3) return { text: `${days} أيام متبقية`, color: 'text-amber-600' };
  return { text: `${days} يوم متبقي`, color: 'text-green-600' };
}

export default function CaseView() {
  const { id } = useParams();
  const navigate = useNavigate();

  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [deleting, setDeleting] = useState(false);
  const [corrFilter, setCorrFilter] = useState('all');
  const [actionsPage, setActionsPage] = useState(1);
  const [expandedNotes, setExpandedNotes] = useState({});
  const [updatingAction, setUpdatingAction] = useState(null);

  const ACTIONS_PER_PAGE = 10;

  async function loadCase() {
    setLoading(true);
    setError(null);
    try {
      const res = await fetchCase(id);
      setData(res.data || res);
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    loadCase();
  }, [id]);

  async function handleDelete() {
    if (!confirm('هل أنت متأكد من حذف هذه القضية؟ لا يمكن التراجع عن هذا الإجراء.')) return;
    setDeleting(true);
    try {
      await deleteCase(id);
      toast.success('تم حذف القضية بنجاح');
      navigate('/');
    } catch (err) {
      toast.error(err.message || 'فشل حذف القضية');
    } finally {
      setDeleting(false);
    }
  }

  async function handleActionStatus(actionId, status) {
    setUpdatingAction(actionId);
    try {
      await updateActionStatus(actionId, status);
      toast.success(status === 'approved' ? 'تمت الموافقة' : 'تم الرفض');
      loadCase();
    } catch (err) {
      toast.error(err.message || 'فشل تحديث الحالة');
    } finally {
      setUpdatingAction(null);
    }
  }

  if (loading) {
    return (
      <div className="space-y-6">
        <SkeletonCard lines={1} />
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <SkeletonCard /><SkeletonCard /><SkeletonCard />
        </div>
        <SkeletonCard lines={4} />
        <SkeletonCard lines={5} />
      </div>
    );
  }

  if (error) {
    return (
      <div className="card flex flex-col items-center justify-center py-16 gap-4">
        <ExclamationTriangleIcon className="w-12 h-12 text-red-400" />
        <p className="text-red-600 font-medium">{error}</p>
        <button onClick={loadCase} className="btn-primary">
          <ArrowPathIcon className="w-4 h-4" />
          إعادة المحاولة
        </button>
      </div>
    );
  }

  const c = data?.case || data || {};
  const parties = data?.parties || [];
  const stages = data?.stages || [];
  const deadlines = data?.deadlines || [];
  const assets = data?.assets || [];
  const correspondence = data?.correspondence || [];
  const actions = data?.actions || [];

  const completedStageKeys = new Set(stages.filter(s => s.is_completed).map(s => s.stage_key));
  const furthestIdx = PIPELINE_STAGES.reduce((max, s, i) => completedStageKeys.has(s.key) ? i : max, -1);
  const bottleneckStage = c.bottleneck_stage;

  const filteredCorr = corrFilter === 'all'
    ? correspondence
    : corrFilter === 'notification'
      ? correspondence.filter(cr => cr.purpose === 'تبليغ' || cr.is_notification)
      : correspondence.filter(cr => cr.direction === corrFilter);

  const totalActionPages = Math.ceil(actions.length / ACTIONS_PER_PAGE);
  const visibleActions = actions.slice(0, actionsPage * ACTIONS_PER_PAGE);

  const activeDeadlines = deadlines.filter(d => d.status !== 'completed');

  const defendantStages = stages.filter(s => s.defendant_name);

  return (
    <div className="space-y-6 pb-10">
      {/* ──── Header Bar ──── */}
      <div className="card !p-4">
        <div className="flex flex-col sm:flex-row items-start sm:items-center gap-3 sm:gap-4">
          <button onClick={() => navigate(-1)} className="btn-icon shrink-0" title="رجوع">
            <ArrowRightIcon className="w-5 h-5" />
          </button>

          <div className="flex-1 min-w-0">
            <h1 className="text-lg font-bold text-gray-900 truncate">
              قضية #{c.id} — {c.case_number}/{c.case_year}
            </h1>
          </div>

          <span className={`badge ${STATUS_COLORS[c.status] || 'bg-gray-100 text-gray-700'}`}>
            {STATUS_LABELS[c.status] || c.status}
          </span>

          <div className="flex items-center gap-2 flex-wrap">
            <Link to={`/case/${id}/edit`} className="btn-secondary text-xs sm:text-sm">
              <PencilSquareIcon className="w-4 h-4" />
              تعديل
            </Link>
            <Link to={`/case/${id}/timeline`} className="btn-secondary text-xs sm:text-sm">
              <ClockIcon className="w-4 h-4" />
              المسار الزمني
            </Link>
            <button
              onClick={handleDelete}
              disabled={deleting}
              className="btn-danger text-xs sm:text-sm"
            >
              <TrashIcon className="w-4 h-4" />
              {deleting ? 'جاري الحذف...' : 'حذف'}
            </button>
          </div>
        </div>
      </div>

      {/* ──── Info Cards Row ──── */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        {/* Case Info */}
        <div className="card">
          <div className="flex items-center gap-2 mb-4">
            <div className="stat-icon bg-primary-100 text-primary-700">
              <DocumentTextIcon className="w-5 h-5" />
            </div>
            <h3 className="font-bold text-gray-900">معلومات القضية</h3>
          </div>
          <div className="divide-y divide-gray-100">
            <InfoRow label="رقم الدعوى" value={`${c.case_number || '—'}/${c.case_year || '—'}`} />
            <InfoRow label="نوع القضية" value={c.case_type_name} />
            <InfoRow label="تاريخ الورود" value={formatDate(c.income_date)} />
            <InfoRow label="آخر طلب إجرائي" value={formatDate(c.last_procedural_request_date)} />
          </div>
        </div>

        {/* Court & Lawyer */}
        <div className="card">
          <div className="flex items-center gap-2 mb-4">
            <div className="stat-icon bg-blue-100 text-blue-700">
              <BuildingLibraryIcon className="w-5 h-5" />
            </div>
            <h3 className="font-bold text-gray-900">المحكمة والمحامي</h3>
          </div>
          <div className="divide-y divide-gray-100">
            <InfoRow label="المحكمة" value={c.court_name} />
            <InfoRow label="المحامي" value={c.lawyer_name} />
            <InfoRow label="أتعاب المحامي" value={formatCurrency(c.lawyer_cost)} />
            <InfoRow label="تكلفة القضية" value={formatCurrency(c.case_cost)} />
          </div>
        </div>

        {/* Contract Info */}
        <div className="card">
          <div className="flex items-center gap-2 mb-4">
            <div className="stat-icon bg-emerald-100 text-emerald-700">
              <BanknotesIcon className="w-5 h-5" />
            </div>
            <h3 className="font-bold text-gray-900">معلومات العقد</h3>
          </div>
          <div className="divide-y divide-gray-100">
            <InfoRow
              label="رقم العقد"
              value={
                c.contract_id ? (
                  <Link to={`/contract/${c.contract_id}`} className="text-primary-600 hover:underline font-medium">
                    {c.contract_number || c.contract_id}
                  </Link>
                ) : '—'
              }
            />
            <InfoRow label="نوع العقد" value={c.contract_type} />
            <InfoRow label="قيمة العقد" value={formatCurrency(c.contract_value)} />
            <InfoRow label="حالة العقد" value={c.contract_status} />
          </div>
        </div>
      </div>

      {/* ──── Parties ──── */}
      <div className="card">
        <div className="flex items-center gap-2 mb-4">
          <UserGroupIcon className="w-5 h-5 text-purple-600" />
          <h3 className="section-title">الأطراف</h3>
          {parties.length > 0 && (
            <span className="badge bg-purple-100 text-purple-700 mr-auto">{parties.length}</span>
          )}
        </div>

        {parties.length === 0 ? (
          <EmptyState icon={UserGroupIcon} text="لا يوجد أطراف مسجلة" />
        ) : (
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            {parties.map((p, i) => (
              <div
                key={p.id || i}
                className="flex items-center gap-3 p-3 rounded-lg border border-gray-100 bg-gray-50/50 hover:bg-gray-50 transition-colors"
              >
                <div className="w-9 h-9 rounded-full bg-purple-100 text-purple-700 flex items-center justify-center text-sm font-bold shrink-0">
                  {(p.customer_name || '?')[0]}
                </div>
                <div className="flex-1 min-w-0">
                  <p className="text-sm font-semibold text-gray-900 truncate">{p.customer_name}</p>
                  {p.id_number && (
                    <p className="text-xs text-gray-500">{p.id_number}</p>
                  )}
                  {p.job_title && (
                    <p className="text-xs text-gray-400">{p.job_title}</p>
                  )}
                </div>
                <span className={`badge text-[10px] shrink-0 ${p.type === 'guarantor' ? 'bg-amber-100 text-amber-800' : 'bg-primary-100 text-primary-800'}`}>
                  {p.type === 'guarantor' ? 'كفيل' : 'عميل'}
                </span>
              </div>
            ))}
          </div>
        )}
      </div>

      {/* ──── Stage Pipeline ──── */}
      <div className="card">
        <div className="flex items-center gap-2 mb-5">
          <ShieldCheckIcon className="w-5 h-5 text-emerald-600" />
          <h3 className="section-title">مراحل القضية</h3>
        </div>

        {/* Pipeline */}
        <div className="overflow-x-auto -mx-5 px-5 pb-2">
          <div className="flex items-center gap-0 min-w-[700px]">
            {PIPELINE_STAGES.map((stage, i) => {
              const completed = completedStageKeys.has(stage.key);
              const isCurrent = i === furthestIdx;
              const isBottleneck = stage.key === bottleneckStage;
              const isFuture = i > furthestIdx;

              let circleClass = 'border-2 border-gray-300 bg-white text-gray-400';
              if (completed && !isCurrent) circleClass = 'bg-emerald-500 text-white border-2 border-emerald-500';
              if (isCurrent) circleClass = 'bg-emerald-500 text-white border-2 border-emerald-500 ring-4 ring-emerald-100';
              if (isBottleneck) circleClass = 'bg-amber-500 text-white border-2 border-amber-500 ring-4 ring-amber-100';

              let lineClass = 'bg-gray-200';
              if (completed && i < furthestIdx) lineClass = 'bg-emerald-400';

              return (
                <div key={stage.key} className="flex items-center flex-1">
                  <div className="flex flex-col items-center gap-1.5 relative">
                    <div className={`w-9 h-9 rounded-full flex items-center justify-center text-xs font-bold transition-all ${circleClass} ${isCurrent ? 'animate-pulse' : ''}`}>
                      {completed || isCurrent ? (
                        <CheckCircleIcon className="w-5 h-5" />
                      ) : (
                        i + 1
                      )}
                    </div>
                    <span className={`text-[10px] text-center leading-tight w-16 ${isFuture ? 'text-gray-400' : 'text-gray-700 font-medium'}`}>
                      {stage.label}
                    </span>
                  </div>
                  {i < PIPELINE_STAGES.length - 1 && (
                    <div className={`flex-1 h-0.5 mx-1 rounded-full ${lineClass}`} />
                  )}
                </div>
              );
            })}
          </div>
        </div>

        {c.overall_status && (
          <p className="text-sm text-gray-600 mt-3 border-t border-gray-100 pt-3">
            <span className="font-medium">الحالة العامة:</span> {c.overall_status}
          </p>
        )}

        {/* Per-defendant stages */}
        {defendantStages.length > 0 && (
          <div className="mt-5 border-t border-gray-100 pt-4">
            <h4 className="text-sm font-bold text-gray-700 mb-3">مراحل كل مدعى عليه</h4>
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="table-header">
                    <th className="text-right px-3 py-2 font-semibold text-gray-600">المدعى عليه</th>
                    <th className="text-right px-3 py-2 font-semibold text-gray-600">المرحلة الحالية</th>
                    <th className="text-right px-3 py-2 font-semibold text-gray-600">الحالة</th>
                    <th className="text-right px-3 py-2 font-semibold text-gray-600">تاريخ التحديث</th>
                  </tr>
                </thead>
                <tbody>
                  {defendantStages.map((ds, i) => (
                    <tr key={ds.id || i} className="table-row">
                      <td className="px-3 py-2.5 font-medium">{ds.defendant_name}</td>
                      <td className="px-3 py-2.5">{STAGE_LABELS[ds.stage_key] || ds.stage_key}</td>
                      <td className="px-3 py-2.5">
                        <span className={`badge text-[10px] ${ds.is_completed ? 'bg-emerald-100 text-emerald-800' : 'bg-blue-100 text-blue-800'}`}>
                          {ds.is_completed ? 'مكتمل' : 'جاري'}
                        </span>
                      </td>
                      <td className="px-3 py-2.5 text-gray-500">{formatDate(ds.updated_at)}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        )}
      </div>

      {/* ──── Active Deadlines ──── */}
      <div className="card">
        <div className="flex items-center gap-2 mb-4">
          <CalendarDaysIcon className="w-5 h-5 text-red-500" />
          <h3 className="section-title">المواعيد النشطة</h3>
          {activeDeadlines.length > 0 && (
            <span className="badge bg-red-100 text-red-700 mr-auto">{activeDeadlines.length}</span>
          )}
        </div>

        {activeDeadlines.length === 0 ? (
          <EmptyState icon={CalendarDaysIcon} text="لا يوجد مواعيد نشطة" />
        ) : (
          <div className="space-y-2.5">
            {activeDeadlines.map((d, i) => {
              const days = getDaysRemaining(d.deadline_date);
              const daysInfo = getDaysText(days);
              const cfg = DEADLINE_STATUS_CONFIG[d.status] || DEADLINE_STATUS_CONFIG.pending;

              return (
                <div
                  key={d.id || i}
                  className="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4 p-3 rounded-lg border border-gray-100 bg-gray-50/30 hover:bg-gray-50 transition-colors"
                >
                  <div className="flex-1 min-w-0">
                    <p className="text-sm font-semibold text-gray-900">
                      {DEADLINE_TYPE_LABELS[d.deadline_type] || d.deadline_type}
                    </p>
                    {d.notes && <p className="text-xs text-gray-500 mt-0.5 truncate">{d.notes}</p>}
                  </div>
                  <span className={`badge ${cfg.color}`}>{cfg.label}</span>
                  <span className="text-sm text-gray-600">{formatDate(d.deadline_date)}</span>
                  {daysInfo && (
                    <span className={`text-xs font-semibold ${daysInfo.color}`}>{daysInfo.text}</span>
                  )}
                </div>
              );
            })}
          </div>
        )}
      </div>

      {/* ──── Seized Assets ──── */}
      <div className="card">
        <div className="flex items-center gap-2 mb-4">
          <CubeIcon className="w-5 h-5 text-indigo-500" />
          <h3 className="section-title">الأصول المحجوزة</h3>
          {assets.length > 0 && (
            <span className="badge bg-indigo-100 text-indigo-700 mr-auto">{assets.length}</span>
          )}
        </div>

        {assets.length === 0 ? (
          <EmptyState icon={CubeIcon} text="لا يوجد أصول محجوزة" />
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="table-header">
                  <th className="text-right px-3 py-2 font-semibold text-gray-600">النوع</th>
                  <th className="text-right px-3 py-2 font-semibold text-gray-600">الوصف</th>
                  <th className="text-right px-3 py-2 font-semibold text-gray-600">المبلغ</th>
                  <th className="text-right px-3 py-2 font-semibold text-gray-600">الحالة</th>
                </tr>
              </thead>
              <tbody>
                {assets.map((a, i) => {
                  const statusCfg = ASSET_STATUS_CONFIG[a.status] || {};
                  return (
                    <tr key={a.id || i} className="table-row">
                      <td className="px-3 py-2.5 font-medium">{ASSET_TYPE_LABELS[a.asset_type] || a.asset_type}</td>
                      <td className="px-3 py-2.5 text-gray-600 max-w-xs truncate">{a.description || '—'}</td>
                      <td className="px-3 py-2.5">{formatCurrency(a.amount)}</td>
                      <td className="px-3 py-2.5">
                        <span className={`badge text-[10px] ${statusCfg.color || 'bg-gray-100 text-gray-700'}`}>
                          {statusCfg.label || a.status}
                        </span>
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        )}
      </div>

      {/* ──── Correspondence ──── */}
      <div className="card">
        <div className="flex flex-col sm:flex-row sm:items-center gap-3 mb-4">
          <div className="flex items-center gap-2">
            <EnvelopeIcon className="w-5 h-5 text-teal-600" />
            <h3 className="section-title">المراسلات</h3>
            {correspondence.length > 0 && (
              <span className="badge bg-teal-100 text-teal-700">{correspondence.length}</span>
            )}
          </div>

          <div className="flex gap-1.5 mr-auto flex-wrap">
            {[
              { key: 'all', label: 'الكل' },
              { key: 'notification', label: 'تبليغات' },
              { key: 'outgoing', label: 'صادر' },
              { key: 'incoming', label: 'وارد' },
            ].map(f => (
              <button
                key={f.key}
                onClick={() => setCorrFilter(f.key)}
                className={`px-3 py-1 rounded-full text-xs font-medium transition-colors ${
                  corrFilter === f.key
                    ? 'bg-teal-600 text-white'
                    : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                }`}
              >
                {f.label}
              </button>
            ))}
          </div>
        </div>

        {filteredCorr.length === 0 ? (
          <EmptyState icon={EnvelopeIcon} text="لا يوجد مراسلات" />
        ) : (
          <div className="space-y-2.5">
            {filteredCorr.map((cr, i) => (
              <div
                key={cr.id || i}
                className="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4 p-3 rounded-lg border border-gray-100 bg-gray-50/30 hover:bg-gray-50 transition-colors"
              >
                <div className="flex-1 min-w-0">
                  <p className="text-sm font-semibold text-gray-900">{cr.purpose || '—'}</p>
                  {cr.recipient_name && (
                    <p className="text-xs text-gray-500 mt-0.5">{cr.recipient_name}</p>
                  )}
                </div>
                <span className={`badge text-[10px] ${
                  cr.direction === 'outgoing'
                    ? 'bg-blue-100 text-blue-800'
                    : cr.direction === 'incoming'
                      ? 'bg-green-100 text-green-800'
                      : 'bg-gray-100 text-gray-700'
                }`}>
                  {CORRESPONDENCE_DIRECTION[cr.direction] || cr.direction}
                </span>
                {cr.status && (
                  <span className="badge bg-gray-100 text-gray-700 text-[10px]">{cr.status}</span>
                )}
                <span className="text-xs text-gray-500">{formatDate(cr.created_at || cr.date)}</span>
              </div>
            ))}
          </div>
        )}
      </div>

      {/* ──── Actions History ──── */}
      <div className="card">
        <div className="flex items-center gap-2 mb-4">
          <ListBulletIcon className="w-5 h-5 text-orange-500" />
          <h3 className="section-title">سجل الإجراءات</h3>
          {actions.length > 0 && (
            <span className="badge bg-orange-100 text-orange-700 mr-auto">{actions.length}</span>
          )}
        </div>

        {actions.length === 0 ? (
          <EmptyState icon={InboxIcon} text="لا يوجد إجراءات مسجلة" />
        ) : (
          <>
            <div className="space-y-2.5">
              {visibleActions.map((act, i) => {
                const natureCfg = ACTION_NATURE_CONFIG[act.action_nature] || {};
                const statusCfg = REQUEST_STATUS_CONFIG[act.request_status] || {};
                const isPending = act.request_status === 'pending';
                const isUpdating = updatingAction === act.id;
                const noteExpanded = expandedNotes[act.id];
                const noteLong = act.notes && act.notes.length > 100;

                return (
                  <div
                    key={act.id || i}
                    className={`p-3 rounded-lg border transition-colors ${
                      isPending ? 'border-amber-200 bg-amber-50/30' : 'border-gray-100 bg-gray-50/30 hover:bg-gray-50'
                    }`}
                  >
                    <div className="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-3">
                      {/* Nature dot + name */}
                      <div className="flex items-center gap-2 flex-1 min-w-0">
                        {natureCfg.dot && (
                          <span className={`w-2 h-2 rounded-full shrink-0 ${natureCfg.dot}`} />
                        )}
                        <span className="text-sm font-semibold text-gray-900 truncate">
                          {act.action_name}
                        </span>
                        {natureCfg.label && (
                          <span className={`badge text-[10px] ${natureCfg.color}`}>{natureCfg.label}</span>
                        )}
                      </div>

                      {/* Status + meta */}
                      <div className="flex items-center gap-2 flex-wrap">
                        {statusCfg.label && (
                          <span className={`badge text-[10px] ${statusCfg.color}`}>{statusCfg.label}</span>
                        )}
                        {act.customer_name && (
                          <span className="text-xs text-gray-500">{act.customer_name}</span>
                        )}
                        <span className="text-xs text-gray-400">{formatDate(act.created_at || act.action_date)}</span>
                        {act.creator_name && (
                          <span className="text-[10px] text-gray-400">بواسطة {act.creator_name}</span>
                        )}
                      </div>

                      {/* Approve / Reject */}
                      {isPending && (
                        <div className="flex items-center gap-1.5 shrink-0">
                          <button
                            onClick={() => handleActionStatus(act.id, 'approved')}
                            disabled={isUpdating}
                            className="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-emerald-600 text-white text-xs font-medium hover:bg-emerald-700 disabled:opacity-50 transition-colors"
                          >
                            <CheckCircleIcon className="w-3.5 h-3.5" />
                            موافقة
                          </button>
                          <button
                            onClick={() => handleActionStatus(act.id, 'rejected')}
                            disabled={isUpdating}
                            className="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-red-600 text-white text-xs font-medium hover:bg-red-700 disabled:opacity-50 transition-colors"
                          >
                            <XCircleIcon className="w-3.5 h-3.5" />
                            رفض
                          </button>
                        </div>
                      )}
                    </div>

                    {/* Notes */}
                    {act.notes && (
                      <div className="mt-2 pr-4">
                        <p className={`text-xs text-gray-500 leading-relaxed ${!noteExpanded && noteLong ? 'line-clamp-2' : ''}`}>
                          {act.notes}
                        </p>
                        {noteLong && (
                          <button
                            onClick={() => setExpandedNotes(prev => ({ ...prev, [act.id]: !prev[act.id] }))}
                            className="text-xs text-primary-600 hover:underline mt-0.5 flex items-center gap-0.5"
                          >
                            {noteExpanded ? (
                              <><ChevronUpIcon className="w-3 h-3" /> أقل</>
                            ) : (
                              <><ChevronDownIcon className="w-3 h-3" /> المزيد</>
                            )}
                          </button>
                        )}
                      </div>
                    )}
                  </div>
                );
              })}
            </div>

            {actionsPage < totalActionPages && (
              <div className="flex justify-center mt-4">
                <button
                  onClick={() => setActionsPage(p => p + 1)}
                  className="btn-secondary text-sm"
                >
                  عرض المزيد ({actions.length - visibleActions.length} متبقي)
                </button>
              </div>
            )}
          </>
        )}
      </div>
    </div>
  );
}
