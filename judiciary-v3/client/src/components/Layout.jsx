import { useState, useEffect } from 'react';
import { Outlet, NavLink, useLocation } from 'react-router-dom';
import {
  ScaleIcon,
  ClipboardDocumentListIcon,
  ChartBarIcon,
  BuildingLibraryIcon,
  BanknotesIcon,
  ClockIcon,
  Bars3Icon,
  XMarkIcon,
  BriefcaseIcon,
  DocumentTextIcon,
  ExclamationTriangleIcon,
} from '@heroicons/react/24/outline';
import { fetchStats } from '../api';

const NAV_ITEMS = [
  { to: '/cases', label: 'القضايا', icon: ScaleIcon },
  { to: '/actions', label: 'الإجراءات', icon: ClipboardDocumentListIcon },
  { to: '/persistence', label: 'المثابرة', icon: ChartBarIcon },
  { to: '/legal', label: 'الشؤون القانونية', icon: BuildingLibraryIcon },
  { to: '/collection', label: 'التحصيل', icon: BanknotesIcon },
  { to: '/deadlines', label: 'المواعيد', icon: ClockIcon },
];

const SECTION_TITLES = {
  '/cases': 'إدارة القضايا',
  '/actions': 'الإجراءات والطلبات',
  '/persistence': 'تقارير المثابرة',
  '/legal': 'الشؤون القانونية',
  '/collection': 'التحصيل والتنفيذ',
  '/deadlines': 'لوحة المواعيد',
  '/case/new': 'إضافة قضية جديدة',
  '/batch/cases': 'إدخال قضايا دفعة واحدة',
  '/batch/actions': 'إدخال إجراءات دفعة واحدة',
};

function getSectionTitle(pathname) {
  if (/^\/case\/\d+\/edit/.test(pathname)) return 'تعديل القضية';
  if (/^\/case\/\d+\/timeline/.test(pathname)) return 'المخطط الزمني';
  if (/^\/case\/\d+/.test(pathname)) return 'تفاصيل القضية';
  return SECTION_TITLES[pathname] || 'نظام إدارة القضايا';
}

export default function Layout() {
  const location = useLocation();
  const [mobileOpen, setMobileOpen] = useState(false);
  const [stats, setStats] = useState(null);

  useEffect(() => {
    fetchStats()
      .then(setStats)
      .catch(() => {});
  }, []);

  useEffect(() => {
    setMobileOpen(false);
  }, [location.pathname]);

  const sectionTitle = getSectionTitle(location.pathname);

  return (
    <div className="flex h-screen overflow-hidden">
      {/* Desktop Sidebar */}
      <aside className="hidden lg:flex lg:flex-col w-64 gradient-sidebar text-white flex-shrink-0">
        <div className="p-5 border-b border-white/10">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-xl bg-white/10 flex items-center justify-center">
              <ScaleIcon className="w-6 h-6 text-emerald-300" />
            </div>
            <div>
              <h1 className="font-bold text-base leading-tight">نظام القضايا</h1>
              <p className="text-xs text-emerald-300/70">الإصدار 3.0</p>
            </div>
          </div>
        </div>

        <nav className="flex-1 p-3 space-y-1 overflow-y-auto">
          {NAV_ITEMS.map((item) => (
            <NavLink
              key={item.to}
              to={item.to}
              className={({ isActive }) =>
                `flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 ${
                  isActive
                    ? 'bg-white/15 text-white shadow-sm'
                    : 'text-white/70 hover:bg-white/10 hover:text-white'
                }`
              }
            >
              <item.icon className="w-5 h-5 flex-shrink-0" />
              <span>{item.label}</span>
            </NavLink>
          ))}
        </nav>

        {stats && (
          <div className="p-4 border-t border-white/10">
            <div className="grid grid-cols-2 gap-2 text-xs">
              <div className="bg-white/10 rounded-lg p-2.5 text-center">
                <div className="text-lg font-bold text-white">{stats.totalCases ?? 0}</div>
                <div className="text-emerald-300/70">إجمالي القضايا</div>
              </div>
              <div className="bg-white/10 rounded-lg p-2.5 text-center">
                <div className="text-lg font-bold text-amber-300">{stats.pendingRequests ?? 0}</div>
                <div className="text-emerald-300/70">طلبات معلقة</div>
              </div>
            </div>
          </div>
        )}
      </aside>

      {/* Mobile overlay */}
      {mobileOpen && (
        <div
          className="fixed inset-0 bg-black/50 z-40 lg:hidden"
          onClick={() => setMobileOpen(false)}
        />
      )}

      {/* Mobile sidebar */}
      <aside
        className={`fixed inset-y-0 right-0 z-50 w-72 gradient-sidebar text-white transform transition-transform duration-300 lg:hidden ${
          mobileOpen ? 'translate-x-0' : 'translate-x-full'
        }`}
      >
        <div className="flex items-center justify-between p-4 border-b border-white/10">
          <div className="flex items-center gap-3">
            <div className="w-9 h-9 rounded-xl bg-white/10 flex items-center justify-center">
              <ScaleIcon className="w-5 h-5 text-emerald-300" />
            </div>
            <h1 className="font-bold text-sm">نظام القضايا</h1>
          </div>
          <button
            onClick={() => setMobileOpen(false)}
            className="p-1.5 rounded-lg hover:bg-white/10 transition-colors"
          >
            <XMarkIcon className="w-5 h-5" />
          </button>
        </div>

        <nav className="p-3 space-y-1">
          {NAV_ITEMS.map((item) => (
            <NavLink
              key={item.to}
              to={item.to}
              className={({ isActive }) =>
                `flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 ${
                  isActive
                    ? 'bg-white/15 text-white'
                    : 'text-white/70 hover:bg-white/10 hover:text-white'
                }`
              }
            >
              <item.icon className="w-5 h-5 flex-shrink-0" />
              <span>{item.label}</span>
            </NavLink>
          ))}
        </nav>
      </aside>

      {/* Main content area */}
      <div className="flex-1 flex flex-col min-w-0">
        {/* Top header */}
        <header className="bg-white border-b border-gray-200 flex-shrink-0">
          <div className="flex items-center justify-between px-4 lg:px-6 h-14">
            <div className="flex items-center gap-3">
              <button
                onClick={() => setMobileOpen(true)}
                className="lg:hidden p-1.5 rounded-lg hover:bg-gray-100 transition-colors"
              >
                <Bars3Icon className="w-5 h-5 text-gray-600" />
              </button>
              <h2 className="text-base font-bold text-gray-900">{sectionTitle}</h2>
            </div>

            {stats && (
              <div className="hidden md:flex items-center gap-4 text-xs">
                <div className="flex items-center gap-1.5 text-gray-500">
                  <BriefcaseIcon className="w-4 h-4" />
                  <span className="font-semibold text-gray-900">{stats.totalCases ?? 0}</span>
                  <span>قضية</span>
                </div>
                <div className="w-px h-4 bg-gray-200" />
                <div className="flex items-center gap-1.5 text-gray-500">
                  <DocumentTextIcon className="w-4 h-4" />
                  <span className="font-semibold text-gray-900">{stats.pendingRequests ?? 0}</span>
                  <span>طلب معلق</span>
                </div>
                {stats.overdueDeadlines > 0 && (
                  <>
                    <div className="w-px h-4 bg-gray-200" />
                    <div className="flex items-center gap-1.5 text-red-500">
                      <ExclamationTriangleIcon className="w-4 h-4" />
                      <span className="font-semibold">{stats.overdueDeadlines}</span>
                      <span>موعد متأخر</span>
                    </div>
                  </>
                )}
              </div>
            )}
          </div>
        </header>

        {/* Page content */}
        <main className="flex-1 overflow-y-auto bg-gray-50 p-4 lg:p-6">
          <Outlet />
        </main>
      </div>

      {/* Mobile bottom nav */}
      <nav className="fixed bottom-0 inset-x-0 z-30 bg-white border-t border-gray-200 lg:hidden">
        <div className="flex items-center justify-around h-14">
          {NAV_ITEMS.slice(0, 5).map((item) => (
            <NavLink
              key={item.to}
              to={item.to}
              className={({ isActive }) =>
                `flex flex-col items-center gap-0.5 px-2 py-1 text-[10px] font-medium transition-colors ${
                  isActive ? 'text-primary-600' : 'text-gray-400'
                }`
              }
            >
              <item.icon className="w-5 h-5" />
              <span>{item.label}</span>
            </NavLink>
          ))}
        </div>
      </nav>
    </div>
  );
}
