import React from 'react';
import { NavLink } from 'react-router-dom';
import {
  UserGroupIcon,
  CubeIcon,
  DocumentTextIcon,
  CalculatorIcon,
  UsersIcon,
  ClipboardListIcon,
  ScaleIcon,
  PhoneIcon,
  ChartBarIcon,
  CurrencyDollarIcon,
} from '@heroicons/react/24/outline';

const navigation = [
  { name: 'إدارة العملاء', href: '/customers', icon: UserGroupIcon },
  { name: 'إدارة المخزون', href: '/inventory', icon: CubeIcon },
  { name: 'عقود التقسيط', href: '/installments', icon: DocumentTextIcon },
  { name: 'النظام المحاسبي', href: '/accounting', icon: CalculatorIcon },
  { name: 'إدارة الموظفين', href: '/employees', icon: UsersIcon },
  { name: 'إدارة المهام', href: '/tasks', icon: ClipboardListIcon },
  { name: 'الشؤون القانونية', href: '/legal', icon: ScaleIcon },
  { name: 'خدمة العملاء', href: '/customer-service', icon: PhoneIcon },
  { name: 'التقارير والتحليلات', href: '/reports', icon: ChartBarIcon },
  { name: 'نظام المستثمرين', href: '/investors', icon: CurrencyDollarIcon },
];

export default function Sidebar() {
  return (
    <div className="flex h-full w-64 flex-col bg-gray-900">
      <div className="flex h-16 items-center justify-center bg-gray-800">
        <h1 className="text-xl font-bold text-white">نظام تيسير</h1>
      </div>
      <nav className="flex-1 space-y-1 px-2 py-4">
        {navigation.map((item) => (
          <NavLink
            key={item.name}
            to={item.href}
            className={({ isActive }) =>
              `${
                isActive
                  ? 'bg-gray-800 text-white'
                  : 'text-gray-300 hover:bg-gray-700 hover:text-white'
              } group flex items-center rounded-md px-2 py-2 text-sm font-medium transition-colors duration-150`
            }
          >
            <item.icon
              className="ml-3 h-5 w-5 flex-shrink-0"
              aria-hidden="true"
            />
            {item.name}
          </NavLink>
        ))}
      </nav>
    </div>
  );
}