import React from 'react';
import { Link } from 'react-router-dom';
import {
  UserGroupIcon,
  CubeIcon,
  DocumentTextIcon,
  CurrencyDollarIcon,
  UsersIcon,
  ClipboardDocumentListIcon,
  ScaleIcon,
  ChatBubbleLeftRightIcon,
  ChartBarIcon,
  BanknotesIcon
} from '@heroicons/react/24/outline';

const modules = [
  {
    title: 'إدارة العملاء',
    description: 'إدارة معلومات العملاء والتواصل معهم',
    icon: UserGroupIcon,
    link: '/customers',
    color: 'bg-blue-500'
  },
  {
    title: 'إدارة المخزون',
    description: 'تتبع المنتجات والمخزون المتاح',
    icon: CubeIcon,
    link: '/inventory',
    color: 'bg-green-500'
  },
  {
    title: 'عقود التقسيط',
    description: 'إنشاء وإدارة عقود التقسيط',
    icon: DocumentTextIcon,
    link: '/contracts',
    color: 'bg-purple-500'
  },
  {
    title: 'النظام المحاسبي',
    description: 'إدارة الحسابات والمعاملات المالية',
    icon: CurrencyDollarIcon,
    link: '/accounting',
    color: 'bg-yellow-500'
  },
  {
    title: 'إدارة الموظفين',
    description: 'إدارة بيانات الموظفين والرواتب',
    icon: UsersIcon,
    link: '/employees',
    color: 'bg-indigo-500'
  },
  {
    title: 'إدارة المهام',
    description: 'تتبع المهام والمشاريع',
    icon: ClipboardDocumentListIcon,
    link: '/tasks',
    color: 'bg-pink-500'
  },
  {
    title: 'القسم القانوني',
    description: 'إدارة القضايا والمستندات القانونية',
    icon: ScaleIcon,
    link: '/legal',
    color: 'bg-red-500'
  },
  {
    title: 'خدمة العملاء',
    description: 'إدارة الشكاوى والدعم الفني',
    icon: ChatBubbleLeftRightIcon,
    link: '/customer-service',
    color: 'bg-teal-500'
  },
  {
    title: 'التقارير والذكاء الاصطناعي',
    description: 'تحليلات وتقارير ذكية',
    icon: ChartBarIcon,
    link: '/reports',
    color: 'bg-orange-500'
  },
  {
    title: 'نظام المستثمرين',
    description: 'إدارة المستثمرين والاستثمارات',
    icon: BanknotesIcon,
    link: '/investors',
    color: 'bg-gray-600'
  }
];

export default function Home() {
  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="bg-white shadow rounded-lg p-6">
        <h1 className="text-3xl font-bold text-gray-900 mb-2">مرحباً بك في نظام تيسير</h1>
        <p className="text-gray-600">نظام متكامل لإدارة شركات التقسيط</p>
      </div>

      {/* Quick Stats */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div className="bg-white p-6 rounded-lg shadow">
          <div className="flex items-center">
            <div className="flex-shrink-0">
              <UserGroupIcon className="h-8 w-8 text-blue-500" />
            </div>
            <div className="mr-5 w-0 flex-1">
              <p className="text-sm font-medium text-gray-500">إجمالي العملاء</p>
              <p className="text-2xl font-semibold text-gray-900">1,234</p>
            </div>
          </div>
        </div>
        <div className="bg-white p-6 rounded-lg shadow">
          <div className="flex items-center">
            <div className="flex-shrink-0">
              <DocumentTextIcon className="h-8 w-8 text-green-500" />
            </div>
            <div className="mr-5 w-0 flex-1">
              <p className="text-sm font-medium text-gray-500">العقود النشطة</p>
              <p className="text-2xl font-semibold text-gray-900">567</p>
            </div>
          </div>
        </div>
        <div className="bg-white p-6 rounded-lg shadow">
          <div className="flex items-center">
            <div className="flex-shrink-0">
              <CurrencyDollarIcon className="h-8 w-8 text-yellow-500" />
            </div>
            <div className="mr-5 w-0 flex-1">
              <p className="text-sm font-medium text-gray-500">إجمالي المبيعات</p>
              <p className="text-2xl font-semibold text-gray-900">2.5M</p>
            </div>
          </div>
        </div>
        <div className="bg-white p-6 rounded-lg shadow">
          <div className="flex items-center">
            <div className="flex-shrink-0">
              <ChartBarIcon className="h-8 w-8 text-purple-500" />
            </div>
            <div className="mr-5 w-0 flex-1">
              <p className="text-sm font-medium text-gray-500">نسبة التحصيل</p>
              <p className="text-2xl font-semibold text-gray-900">87%</p>
            </div>
          </div>
        </div>
      </div>

      {/* Modules Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        {modules.map((module, index) => {
          const Icon = module.icon;
          return (
            <Link
              key={index}
              to={module.link}
              className="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow duration-200 p-6 border border-gray-200 hover:border-gray-300"
            >
              <div className="flex items-center mb-4">
                <div className={`${module.color} p-3 rounded-lg`}>
                  <Icon className="h-6 w-6 text-white" />
                </div>
                <h3 className="mr-4 text-lg font-semibold text-gray-900">{module.title}</h3>
              </div>
              <p className="text-gray-600">{module.description}</p>
            </Link>
          );
        })}
      </div>
    </div>
  );
}