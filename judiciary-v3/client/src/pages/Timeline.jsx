import { useState, useEffect } from 'react';
import { useParams, Link } from 'react-router-dom';
import { fetchTimeline } from '../api';
import {
  ACTION_NATURE_CONFIG,
  REQUEST_STATUS_CONFIG,
  DEADLINE_TYPE_LABELS,
  DEADLINE_STATUS_CONFIG,
  CORRESPONDENCE_DIRECTION,
} from '../utils/constants';
import { formatDate, timeAgo } from '../utils/helpers';
import toast from 'react-hot-toast';

const TYPE_THEME = {
  action: { border: 'border-r-blue-500', dot: 'bg-blue-500', label: 'إجراء' },
  correspondence: { border: 'border-r-teal-500', dot: 'bg-teal-500', label: 'مراسلة' },
  deadline: { border: 'border-r-amber-500', dot: 'bg-amber-500', label: 'موعد نهائي' },
};

function isSameDay(a, b) {
  return (
    a.getFullYear() === b.getFullYear() &&
    a.getMonth() === b.getMonth() &&
    a.getDate() === b.getDate()
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

function SkeletonTimeline() {
  return (
    <div className="relative pr-8 space-y-6">
      <div className="absolute top-0 bottom-0 right-3 w-0.5 bg-gray-200" />
      {[...Array(5)].map((_, i) => (
        <div key={i} className="relative flex gap-4" style={{ animationDelay: `${i * 80}ms` }}>
          <div className="absolute right-0 top-4 w-6 h-6 rounded-full skeleton" />
          <div className="flex-1 mr-6 card space-y-3">
            <div className="skeleton h-4 w-24 rounded" />
            <div className="skeleton h-5 w-48 rounded" />
            <div className="skeleton h-3 w-32 rounded" />
            <div className="skeleton h-3 w-40 rounded" />
          </div>
        </div>
      ))}
    </div>
  );
}

function ActionCard({ entry }) {
  const nature = ACTION_NATURE_CONFIG[entry.nature] || ACTION_NATURE_CONFIG.process;
  const reqStatus = entry.request_status ? REQUEST_STATUS_CONFIG[entry.request_status] : null;

  return (
    <>
      <div className="flex flex-wrap items-center gap-2 mb-2">
        <span className={`badge ${nature.color}`}>{nature.label}</span>
        {reqStatus && <span className={`badge ${reqStatus.color}`}>{reqStatus.label}</span>}
      </div>
      <h4 className="font-bold text-gray-900 text-sm mb-1">{entry.action_name || entry.name}</h4>
      {entry.customer_name && (
        <p className="text-xs text-gray-600 flex items-center gap-1">
          <svg className="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
          {entry.customer_name}
        </p>
      )}
      {entry.notes && <p className="text-xs text-gray-500 mt-1 leading-relaxed">{entry.notes}</p>}
      {entry.created_by_name && (
        <p className="text-[11px] text-gray-400 mt-2">بواسطة: {entry.created_by_name}</p>
      )}
    </>
  );
}

function CorrespondenceCard({ entry }) {
  const dir = CORRESPONDENCE_DIRECTION[entry.direction] || entry.direction;

  return (
    <>
      <div className="flex flex-wrap items-center gap-2 mb-2">
        <span className="badge bg-teal-100 text-teal-800">{dir}</span>
        {entry.status && <span className="badge bg-sky-100 text-sky-800">{entry.status}</span>}
      </div>
      {entry.purpose && <h4 className="font-bold text-gray-900 text-sm mb-1">{entry.purpose}</h4>}
      {entry.recipient && (
        <p className="text-xs text-gray-600 flex items-center gap-1">
          <svg className="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
          {entry.recipient}
        </p>
      )}
      {entry.reference_number && (
        <p className="text-xs text-gray-500 mt-1">رقم المرجع: {entry.reference_number}</p>
      )}
    </>
  );
}

function DeadlineCard({ entry }) {
  const typeLabel = DEADLINE_TYPE_LABELS[entry.deadline_type] || entry.deadline_type;
  const statusCfg = DEADLINE_STATUS_CONFIG[entry.status] || DEADLINE_STATUS_CONFIG.pending;
  const days = getDaysRemaining(entry.deadline_date);
  const isOverdue = days !== null && days < 0;

  return (
    <>
      <div className="flex flex-wrap items-center gap-2 mb-2">
        <span className={`badge ${statusCfg.color}`}>{statusCfg.label}</span>
        <span className="badge bg-gray-100 text-gray-700">{typeLabel}</span>
      </div>
      <p className="text-sm font-semibold text-gray-900 mb-1">{entry.label || typeLabel}</p>
      {days !== null && (
        <p className={`text-xs font-bold ${isOverdue ? 'text-red-600' : 'text-amber-600'}`}>
          {isOverdue ? `متأخر ${Math.abs(days)} يوم` : `باقي ${days} يوم`}
        </p>
      )}
      {entry.notes && <p className="text-xs text-gray-500 mt-1 leading-relaxed">{entry.notes}</p>}
    </>
  );
}

function TimelineEntry({ entry, index }) {
  const theme = TYPE_THEME[entry.type] || TYPE_THEME.action;
  const date = entry.date || entry.action_date || entry.created_at || entry.deadline_date;

  return (
    <div
      className="timeline-entry relative flex gap-4 opacity-0 animate-[fadeSlideIn_0.4s_ease_forwards]"
      style={{ animationDelay: `${index * 60}ms` }}
    >
      {/* Dot on the line */}
      <div className="absolute right-0 top-5 z-10 flex items-center justify-center">
        <span className={`w-3.5 h-3.5 rounded-full ring-4 ring-white ${theme.dot}`} />
      </div>

      {/* Card */}
      <div className={`flex-1 mr-6 card border-r-4 ${theme.border} hover:shadow-md transition-shadow duration-200`}>
        <div className="flex items-center justify-between mb-1">
          <span className="text-[11px] font-medium text-gray-400 uppercase tracking-wide">{theme.label}</span>
          <div className="flex items-center gap-2 text-[11px] text-gray-400">
            <span>{formatDate(date)}</span>
            <span className="text-gray-300">·</span>
            <span>{timeAgo(date)}</span>
          </div>
        </div>

        {entry.type === 'action' && <ActionCard entry={entry} />}
        {entry.type === 'correspondence' && <CorrespondenceCard entry={entry} />}
        {entry.type === 'deadline' && <DeadlineCard entry={entry} />}
      </div>
    </div>
  );
}

export default function Timeline() {
  const { id } = useParams();
  const [data, setData] = useState([]);
  const [loading, setLoading] = useState(true);
  const [partyFilter, setPartyFilter] = useState('');

  useEffect(() => {
    setLoading(true);
    fetchTimeline(id)
      .then((res) => setData(Array.isArray(res) ? res : res?.data || []))
      .catch((err) => toast.error(err.message || 'فشل تحميل المخطط الزمني'))
      .finally(() => setLoading(false));
  }, [id]);

  const parties = [...new Set(data.map((e) => e.customer_name).filter(Boolean))];

  const filtered = partyFilter
    ? data.filter((e) => e.customer_name === partyFilter)
    : data;

  const sorted = [...filtered].sort((a, b) => {
    const da = new Date(a.date || a.action_date || a.created_at || a.deadline_date || 0);
    const db = new Date(b.date || b.action_date || b.created_at || b.deadline_date || 0);
    return db - da;
  });

  let lastDateLabel = null;

  return (
    <div className="space-y-5">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div className="flex items-center gap-3">
          <Link
            to={`/case/${id}`}
            className="btn-icon shrink-0"
            title="رجوع"
          >
            <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" /></svg>
          </Link>
          <h1 className="section-title text-lg">
            المخطط الزمني — قضية <span className="text-primary-600">#{id}</span>
          </h1>
        </div>

        {parties.length > 0 && (
          <select
            className="select max-w-xs text-sm"
            value={partyFilter}
            onChange={(e) => setPartyFilter(e.target.value)}
          >
            <option value="">جميع الأطراف</option>
            {parties.map((p) => (
              <option key={p} value={p}>{p}</option>
            ))}
          </select>
        )}
      </div>

      {/* Filter chips */}
      {partyFilter && (
        <div className="flex items-center gap-2 flex-wrap">
          <span className="text-xs text-gray-500">تصفية حسب:</span>
          <button
            onClick={() => setPartyFilter('')}
            className="badge bg-primary-100 text-primary-800 gap-1 cursor-pointer hover:bg-primary-200 transition-colors"
          >
            {partyFilter}
            <svg className="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" /></svg>
          </button>
        </div>
      )}

      {/* Timeline */}
      {loading ? (
        <SkeletonTimeline />
      ) : sorted.length === 0 ? (
        <div className="empty-state">
          <svg className="w-16 h-16 mb-3 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
          <p className="text-sm font-medium">لا توجد بيانات في المخطط الزمني</p>
        </div>
      ) : (
        <div className="relative pr-8">
          {/* Vertical line */}
          <div className="absolute top-0 bottom-0 right-[6.5px] w-0.5 bg-gradient-to-b from-gray-200 via-gray-200 to-transparent" />

          <div className="space-y-4">
            {sorted.map((entry, i) => {
              const entryDate = new Date(entry.date || entry.action_date || entry.created_at || entry.deadline_date || 0);
              let showDateSep = false;
              if (!lastDateLabel || !isSameDay(lastDateLabel, entryDate)) {
                showDateSep = true;
                lastDateLabel = entryDate;
              }

              return (
                <div key={entry.id || i}>
                  {showDateSep && (
                    <div className="relative flex items-center gap-3 py-2 mb-2">
                      <span className="absolute right-0 w-3.5 h-3.5 rounded-full bg-gray-300 ring-4 ring-white z-10" />
                      <span className="mr-6 text-xs font-bold text-gray-500 bg-gray-100 px-3 py-1 rounded-full">
                        {formatDate(entryDate)}
                      </span>
                      <span className="flex-1 h-px bg-gray-100" />
                    </div>
                  )}
                  <TimelineEntry entry={entry} index={i} />
                </div>
              );
            })}
          </div>
        </div>
      )}

      {/* Inline keyframes */}
      <style>{`
        @keyframes fadeSlideIn {
          from { opacity: 0; transform: translateY(12px); }
          to   { opacity: 1; transform: translateY(0); }
        }
      `}</style>
    </div>
  );
}
