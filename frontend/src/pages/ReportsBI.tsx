import React, { useState } from 'react';
import { ArrowTrendingUpIcon, ArrowTrendingDownIcon, ChartBarIcon, DocumentChartBarIcon, CurrencyDollarIcon, UsersIcon, ShoppingCartIcon, ClockIcon } from '@heroicons/react/24/outline';

interface Report {
  id: number;
  name: string;
  type: 'sales' | 'inventory' | 'customers' | 'financial' | 'operational';
  frequency: 'daily' | 'weekly' | 'monthly' | 'quarterly' | 'yearly';
  lastGenerated: string;
  status: 'ready' | 'generating' | 'scheduled';
}

const initialReports: Report[] = [
  { id: 1, name: 'تقرير المبيعات الشهري', type: 'sales', frequency: 'monthly', lastGenerated: '2024-03-01', status: 'ready' },
  { id: 2, name: 'تحليل حركة المخزون', type: 'inventory', frequency: 'weekly', lastGenerated: '2024-03-10', status: 'ready' },
  { id: 3, name: 'تقرير العملاء المتعثرين', type: 'customers', frequency: 'daily', lastGenerated: '2024-03-15', status: 'ready' },
  { id: 4, name: 'التقرير المالي الربعي', type: 'financial', frequency: 'quarterly', lastGenerated: '2024-01-01', status: 'ready' },
  { id: 5, name: 'تقرير الأداء التشغيلي', type: 'operational', frequency: 'monthly', lastGenerated: '2024-03-01', status: 'generating' },
];

export default function ReportsBI() {
  const [reports] = useState<Report[]>(initialReports);
  const [selectedPeriod, setSelectedPeriod] = useState('month');
  const [selectedReportType, setSelectedReportType] = useState('all');

  // بيانات وهمية للوحة المعلومات
  const dashboardData = {
    totalSales: 2450000,
    salesGrowth: 15.5,
    totalCustomers: 1234,
    newCustomers: 87,
    activeContracts: 892,
    contractsGrowth: 8.2,
    collectionRate: 92.5,
    collectionGrowth: 3.1,
    topProducts: [
      { name: 'ثلاجة سامسونج 18 قدم', sales: 145, revenue: 580000 },
      { name: 'غسالة LG أوتوماتيك', sales: 132, revenue: 396000 },
      { name: 'تكييف كاريير 1.5 حصان', sales: 98, revenue: 441000 },
      { name: 'شاشة سوني 55 بوصة', sales: 76, revenue: 380000 },
    ],
    monthlyTrend: [
      { month: 'يناير', sales: 1850000, collections: 1702000 },
      { month: 'فبراير', sales: 2100000, collections: 1932000 },
      { month: 'مارس', sales: 2450000, collections: 2254000 },
    ],
    customerSegments: [
      { segment: 'عملاء ممتازين', count: 234, percentage: 19 },
      { segment: 'عملاء جيدين', count: 567, percentage: 46 },
      { segment: 'عملاء عاديين', count: 321, percentage: 26 },
      { segment: 'عملاء متعثرين', count: 112, percentage: 9 },
    ],
  };

  const getReportTypeText = (type: string) => {
    switch (type) {
      case 'sales':
        return 'مبيعات';
      case 'inventory':
        return 'مخزون';
      case 'customers':
        return 'عملاء';
      case 'financial':
        return 'مالي';
      case 'operational':
        return 'تشغيلي';
      default:
        return type;
    }
  };

  const getFrequencyText = (frequency: string) => {
    switch (frequency) {
      case 'daily':
        return 'يومي';
      case 'weekly':
        return 'أسبوعي';
      case 'monthly':
        return 'شهري';
      case 'quarterly':
        return 'ربع سنوي';
      case 'yearly':
        return 'سنوي';
      default:
        return frequency;
    }
  };

  const getStatusBadge = (status: string) => {
    switch (status) {
      case 'ready':
        return 'bg-green-100 text-green-800';
      case 'generating':
        return 'bg-yellow-100 text-yellow-800';
      case 'scheduled':
        return 'bg-blue-100 text-blue-800';
      default:
        return 'bg-gray-100 text-gray-800';
    }
  };

  const getStatusText = (status: string) => {
    switch (status) {
      case 'ready':
        return 'جاهز';
      case 'generating':
        return 'قيد الإنشاء';
      case 'scheduled':
        return 'مجدول';
      default:
        return status;
    }
  };

  const filteredReports = reports.filter(report => 
    selectedReportType === 'all' || report.type === selectedReportType
  );

  return (
    <div className="space-y-6">
      <div className="sm:flex sm:items-center sm:justify-between">
        <h1 className="text-2xl font-bold text-gray-900">التقارير والذكاء الاصطناعي</h1>
        <div className="mt-3 sm:mt-0 flex gap-2">
          <select
            value={selectedPeriod}
            onChange={(e) => setSelectedPeriod(e.target.value)}
            className="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
          >
            <option value="day">اليوم</option>
            <option value="week">هذا الأسبوع</option>
            <option value="month">هذا الشهر</option>
            <option value="quarter">هذا الربع</option>
            <option value="year">هذه السنة</option>
          </select>
        </div>
      </div>

      {/* لوحة المعلومات الرئيسية */}
      <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <div className="bg-white overflow-hidden shadow rounded-lg">
          <div className="p-5">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm font-medium text-gray-500">إجمالي المبيعات</p>
                <p className="mt-1 text-2xl font-semibold text-gray-900">{dashboardData.totalSales.toLocaleString()} ج.م</p>
              </div>
              <div className="bg-green-100 rounded-md p-3">
                <CurrencyDollarIcon className="h-6 w-6 text-green-600" />
              </div>
            </div>
            <div className="mt-4 flex items-center">
              <ArrowTrendingUpIcon className="h-5 w-5 text-green-500 ml-1" />
              <span className="text-sm text-green-600">{dashboardData.salesGrowth}%</span>
              <span className="text-sm text-gray-500 mr-2">مقارنة بالشهر السابق</span>
            </div>
          </div>
        </div>

        <div className="bg-white overflow-hidden shadow rounded-lg">
          <div className="p-5">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm font-medium text-gray-500">إجمالي العملاء</p>
                <p className="mt-1 text-2xl font-semibold text-gray-900">{dashboardData.totalCustomers}</p>
              </div>
              <div className="bg-blue-100 rounded-md p-3">
                <UsersIcon className="h-6 w-6 text-blue-600" />
              </div>
            </div>
            <div className="mt-4 flex items-center">
              <span className="text-sm text-blue-600">+{dashboardData.newCustomers}</span>
              <span className="text-sm text-gray-500 mr-2">عميل جديد هذا الشهر</span>
            </div>
          </div>
        </div>

        <div className="bg-white overflow-hidden shadow rounded-lg">
          <div className="p-5">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm font-medium text-gray-500">العقود النشطة</p>
                <p className="mt-1 text-2xl font-semibold text-gray-900">{dashboardData.activeContracts}</p>
              </div>
              <div className="bg-indigo-100 rounded-md p-3">
                <DocumentChartBarIcon className="h-6 w-6 text-indigo-600" />
              </div>
            </div>
            <div className="mt-4 flex items-center">
              <ArrowTrendingUpIcon className="h-5 w-5 text-green-500 ml-1" />
              <span className="text-sm text-green-600">{dashboardData.contractsGrowth}%</span>
              <span className="text-sm text-gray-500 mr-2">نمو في العقود</span>
            </div>
          </div>
        </div>

        <div className="bg-white overflow-hidden shadow rounded-lg">
          <div className="p-5">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm font-medium text-gray-500">نسبة التحصيل</p>
                <p className="mt-1 text-2xl font-semibold text-gray-900">{dashboardData.collectionRate}%</p>
              </div>
              <div className="bg-green-100 rounded-md p-3">
                <ChartBarIcon className="h-6 w-6 text-green-600" />
              </div>
            </div>
            <div className="mt-4 flex items-center">
              <ArrowTrendingUpIcon className="h-5 w-5 text-green-500 ml-1" />
              <span className="text-sm text-green-600">+{dashboardData.collectionGrowth}%</span>
              <span className="text-sm text-gray-500 mr-2">تحسن في التحصيل</span>
            </div>
          </div>
        </div>
      </div>

      {/* المنتجات الأكثر مبيعاً */}
      <div className="bg-white shadow rounded-lg">
        <div className="px-4 py-5 sm:p-6">
          <h3 className="text-lg font-medium text-gray-900 mb-4">المنتجات الأكثر مبيعاً</h3>
          <div className="space-y-3">
            {dashboardData.topProducts.map((product, index) => (
              <div key={index} className="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                <div className="flex items-center">
                  <div className="bg-indigo-100 rounded-full p-2 ml-3">
                    <ShoppingCartIcon className="h-5 w-5 text-indigo-600" />
                  </div>
                  <div>
                    <p className="text-sm font-medium text-gray-900">{product.name}</p>
                    <p className="text-sm text-gray-500">{product.sales} وحدة</p>
                  </div>
                </div>
                <div className="text-left">
                  <p className="text-sm font-medium text-gray-900">{product.revenue.toLocaleString()} ج.م</p>
                  <p className="text-xs text-gray-500">إيرادات</p>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>

      {/* اتجاه المبيعات الشهري */}
      <div className="bg-white shadow rounded-lg">
        <div className="px-4 py-5 sm:p-6">
          <h3 className="text-lg font-medium text-gray-900 mb-4">اتجاه المبيعات والتحصيل</h3>
          <div className="space-y-4">
            {dashboardData.monthlyTrend.map((month, index) => (
              <div key={index}>
                <div className="flex justify-between items-center mb-2">
                  <span className="text-sm font-medium text-gray-700">{month.month}</span>
                  <span className="text-sm text-gray-500">
                    مبيعات: {month.sales.toLocaleString()} ج.م | تحصيل: {month.collections.toLocaleString()} ج.م
                  </span>
                </div>
                <div className="w-full bg-gray-200 rounded-full h-2">
                  <div 
                    className="bg-indigo-600 h-2 rounded-full"
                    style={{ width: `${(month.sales / 2500000) * 100}%` }}
                  ></div>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>

      {/* شرائح العملاء */}
      <div className