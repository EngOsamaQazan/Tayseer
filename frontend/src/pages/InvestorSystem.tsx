import React, { useState } from 'react';

interface Investor {
  id: number;
  name: string;
  email: string;
  phone: string;
  investmentAmount: number;
  investmentDate: string;
  riskTolerance: 'low' | 'medium' | 'high';
  status: 'active' | 'inactive';
}

interface Investment {
  id: number;
  investorId: number;
  type: 'stocks' | 'bonds' | 'real_estate' | 'crypto';
  amount: number;
  date: string;
  expectedReturn: number;
  actualReturn?: number;
  status: 'active' | 'completed' | 'cancelled';
}

const initialInvestors: Investor[] = [
  {
    id: 1,
    name: 'أحمد محمد',
    email: 'ahmed@example.com',
    phone: '+966501234567',
    investmentAmount: 100000,
    investmentDate: '2024-01-15',
    riskTolerance: 'medium',
    status: 'active'
  },
  {
    id: 2,
    name: 'فاطمة علي',
    email: 'fatima@example.com',
    phone: '+966507654321',
    investmentAmount: 250000,
    investmentDate: '2024-02-20',
    riskTolerance: 'low',
    status: 'active'
  },
  {
    id: 3,
    name: 'محمد سالم',
    email: 'mohammed@example.com',
    phone: '+966509876543',
    investmentAmount: 500000,
    investmentDate: '2024-03-10',
    riskTolerance: 'high',
    status: 'inactive'
  }
];

const initialInvestments: Investment[] = [
  {
    id: 1,
    investorId: 1,
    type: 'stocks',
    amount: 50000,
    date: '2024-01-15',
    expectedReturn: 8.5,
    actualReturn: 9.2,
    status: 'active'
  },
  {
    id: 2,
    investorId: 1,
    type: 'bonds',
    amount: 50000,
    date: '2024-01-20',
    expectedReturn: 5.0,
    actualReturn: 4.8,
    status: 'active'
  },
  {
    id: 3,
    investorId: 2,
    type: 'real_estate',
    amount: 200000,
    date: '2024-02-20',
    expectedReturn: 12.0,
    status: 'active'
  },
  {
    id: 4,
    investorId: 3,
    type: 'crypto',
    amount: 100000,
    date: '2024-03-10',
    expectedReturn: 15.0,
    actualReturn: -5.2,
    status: 'active'
  }
];

const InvestorSystem: React.FC = () => {
  const [activeTab, setActiveTab] = useState<'dashboard' | 'investors' | 'investments'>('dashboard');
  const [investors, setInvestors] = useState<Investor[]>(initialInvestors);
  const [investments, setInvestments] = useState<Investment[]>(initialInvestments);
  const [searchTerm, setSearchTerm] = useState('');
  const [showModal, setShowModal] = useState(false);
  const [editingInvestor, setEditingInvestor] = useState<Investor | null>(null);
  const [showInvestmentModal, setShowInvestmentModal] = useState(false);
  const [editingInvestment, setEditingInvestment] = useState<Investment | null>(null);

  const getRiskToleranceText = (riskTolerance: string) => {
    switch (riskTolerance) {
      case 'low': return 'منخفض';
      case 'medium': return 'متوسط';
      case 'high': return 'مرتفع';
      default: return riskTolerance;
    }
  };

  const getStatusText = (status: string) => {
    switch (status) {
      case 'active': return 'نشط';
      case 'inactive': return 'غير نشط';
      case 'completed': return 'مكتمل';
      case 'cancelled': return 'ملغي';
      default: return status;
    }
  };

  const getInvestmentTypeText = (type: string) => {
    switch (type) {
      case 'stocks': return 'أسهم';
      case 'bonds': return 'سندات';
      case 'real_estate': return 'عقارات';
      case 'crypto': return 'عملات رقمية';
      default: return type;
    }
  };

  const filteredInvestors = investors.filter(investor =>
    investor.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
    investor.email.toLowerCase().includes(searchTerm.toLowerCase())
  );

  const filteredInvestments = investments.filter(investment => {
    const investor = investors.find(inv => inv.id === investment.investorId);
    return investor?.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
           getInvestmentTypeText(investment.type).toLowerCase().includes(searchTerm.toLowerCase());
  });

  const handleSaveInvestor = (investorData: Omit<Investor, 'id'>) => {
    if (editingInvestor) {
      setInvestors(investors.map(investor =>
        investor.id === editingInvestor.id ? { ...investorData, id: editingInvestor.id } : investor
      ));
    } else {
      const newInvestor = {
        ...investorData,
        id: Math.max(...investors.map(i => i.id)) + 1
      };
      setInvestors([...investors, newInvestor]);
    }
    setShowModal(false);
    setEditingInvestor(null);
  };

  const handleDeleteInvestor = (id: number) => {
    if (window.confirm('هل أنت متأكد من حذف هذا المستثمر؟')) {
      setInvestors(investors.filter(investor => investor.id !== id));
      setInvestments(investments.filter(investment => investment.investorId !== id));
    }
  };

  const handleSaveInvestment = (investmentData: Omit<Investment, 'id'>) => {
    if (editingInvestment) {
      setInvestments(investments.map(investment =>
        investment.id === editingInvestment.id ? { ...investmentData, id: editingInvestment.id } : investment
      ));
    } else {
      const newInvestment = {
        ...investmentData,
        id: Math.max(...investments.map(i => i.id)) + 1
      };
      setInvestments([...investments, newInvestment]);
    }
    setShowInvestmentModal(false);
    setEditingInvestment(null);
  };

  const handleDeleteInvestment = (id: number) => {
    if (window.confirm('هل أنت متأكد من حذف هذا الاستثمار؟')) {
      setInvestments(investments.filter(investment => investment.id !== id));
    }
  };

  const totalInvestments = investments.reduce((sum, inv) => sum + inv.amount, 0);
  const activeInvestors = investors.filter(inv => inv.status === 'active').length;
  const averageReturn = investments.length > 0 
    ? investments.reduce((sum, inv) => sum + (inv.actualReturn || inv.expectedReturn), 0) / investments.length
    : 0;

  return (
    <div className="p-6">
      <h1 className="text-3xl font-bold text-gray-800 mb-6">نظام إدارة المستثمرين</h1>
      
      {/* Navigation Tabs */}
      <div className="flex space-x-4 mb-6 border-b">
        <button
          onClick={() => setActiveTab('dashboard')}
          className={`pb-2 px-4 ${activeTab === 'dashboard' ? 'border-b-2 border-blue-500 text-blue-600' : 'text-gray-600'}`}
        >
          لوحة المعلومات
        </button>
        <button
          onClick={() => setActiveTab('investors')}
          className={`pb-2 px-4 ${activeTab === 'investors' ? 'border-b-2 border-blue-500 text-blue-600' : 'text-gray-600'}`}
        >
          المستثمرون
        </button>
        <button
          onClick={() => setActiveTab('investments')}
          className={`pb-2 px-4 ${activeTab === 'investments' ? 'border-b-2 border-blue-500 text-blue-600' : 'text-gray-600'}`}
        >
          الاستثمارات
        </button>
      </div>

      {/* Dashboard Content */}
      {activeTab === 'dashboard' && (
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div className="bg-white p-6 rounded-lg shadow-md">
            <h3 className="text-lg font-semibold text-gray-700 mb-2">إجمالي الاستثمارات</h3>
            <p className="text-3xl font-bold text-blue-600">{totalInvestments.toLocaleString()} ريال</p>
          </div>
          <div className="bg-white p-6 rounded-lg shadow-md">
            <h3 className="text-lg font-semibold text-gray-700 mb-2">المستثمرون النشطون</h3>
            <p className="text-3xl font-bold text-green-600">{activeInvestors}</p>
          </div>
          <div className="bg-white p-6 rounded-lg shadow-md">
            <h3 className="text-lg font-semibold text-gray-700 mb-2">متوسط العائد</h3>
            <p className="text-3xl font-bold text-purple-600">{averageReturn.toFixed(1)}%</p>
          </div>
        </div>
      )}

     {/* Investors Content */}
{activeTab === 'investors' && (
  <div>
    <div className="flex justify-between items-center mb-6">
      <div className="flex items-center space-x-4">
        <input
          type="text"
          placeholder="البحث في المستثمرين..."
          value={searchTerm}
          onChange={(e) => setSearchTerm(e.target.value)}
          className="px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
        />
      </div>
      <button
        onClick={() => {
          setEditingInvestor(null);
          setShowModal(true);
        }}
        className="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600"
      >
        إضافة مستثمر جديد
      </button>
    </div>

    <div className="bg-white rounded-lg shadow overflow-hidden">
      <table className="min-w-full divide-y divide-gray-200">
        <thead className="bg-gray-50">
          <tr>
            <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الاسم</th>
            <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">البريد الإلكتروني</th>
            <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الهاتف</th>
            <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">مبلغ الاستثمار</th>
            <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">تحمل المخاطر</th>
            <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الحالة</th>
            <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">إجراءات</th>
          </tr>
        </thead>
        <tbody className="bg-white divide-y divide-gray-200">
          {filteredInvestors.map((investor) => (
            <tr key={investor.id}>
              <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{investor.name}</td>
              <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{investor.email}</td>
              <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{investor.phone}</td>
              <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{investor.investmentAmount.toLocaleString()} ريال</td>
              <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{getRiskToleranceText(investor.riskTolerance)}</td>
              <td className="px-6 py-4 whitespace-nowrap">
                <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${
                  investor.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                }`}>
                  {getStatusText(investor.status)}
                </span>
              </td>
              <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <button
                  onClick={() => {
                    setEditingInvestor(investor);
                    setShowModal(true);
                  }}
                  className="text-indigo-600 hover:text-indigo-900 mr-4"
                >
                  تعديل
                </button>
                <button
                  onClick={() => handleDeleteInvestor(investor.id)}
                  className="text-red-600 hover:text-red-900"
                >
                  حذف
                </button>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  </div>
)}
      {/* Investments Content */}
      {activeTab === 'investments' && (
        <div>
          <div className="flex justify-between items-center mb-6">
            <div className="flex items-center space-x-4">
              <input
                type="text"
                placeholder="البحث في الاستثمارات..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
            <button
              onClick={() => {
                setEditingInvestment(null);
                setShowInvestmentModal(true);
              }}
              className="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600"
            >
              إضافة استثمار جديد
            </button>
          </div>

          <div className="bg-white rounded-lg shadow overflow-hidden">
            <table className="min-w-full divide-y divide-gray-200">
              <thead className="bg-gray-50">
                <tr>
                  <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">المستثمر</th>
                  <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">نوع الاستثمار</th>
                  <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">المبلغ</th>
                  <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">العائد المتوقع</th>
                  <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">العائد الفعلي</th>
                  <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الحالة</th>
                  <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">إجراءات</th>
                </tr>
              </thead>
              <tbody className="bg-white divide-y divide-gray-200">
                {filteredInvestments.map((investment) => {
                  const investor = investors.find(inv => inv.id === investment.investorId);
                  return (
                    <tr key={investment.id}>
                      <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{investor?.name}</td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{getInvestmentTypeText(investment.type)}</td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{investment.amount.toLocaleString()} ريال</td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{investment.expectedReturn}%</td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {investment.actualReturn ? `${investment.actualReturn}%` : 'غير محدد'}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${
                          investment.status === 'active' ? 'bg-green-100 text-green-800' :
                          investment.status === 'completed' ? 'bg-blue-100 text-blue-800' :
                          'bg-red-100 text-red-800'
                        }`}>
                          {getStatusText(investment.status)}
                        </span>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <button
                          onClick={() => {
                            setEditingInvestment(investment);
                            setShowInvestmentModal(true);
                          }}
                          className="text-indigo-600 hover:text-indigo-900 mr-4"
                        >
                          تعديل
                        </button>
                        <button
                          onClick={() => handleDeleteInvestment(investment.id)}
                          className="text-red-600 hover:text-red-900"
                        >
                          حذف
                        </button>
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* Investor Modal */}
      {showModal && (
        <div className="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
          <div className="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div className="mt-3">
              <h3 className="text-lg font-medium text-gray-900 mb-4">
                {editingInvestor ? 'تعديل المستثمر' : 'إضافة مستثمر جديد'}
              </h3>
              <form onSubmit={(e) => {
                e.preventDefault();
                const formData = new FormData(e.target as HTMLFormElement);
                handleSaveInvestor({
                  name: formData.get('name') as string,
                  email: formData.get('email') as string,
                  phone: formData.get('phone') as string,
                  investmentAmount: Number(formData.get('investmentAmount')),
                  investmentDate: formData.get('investmentDate') as string,
                  riskTolerance: formData.get('riskTolerance') as 'low' | 'medium' | 'high',
                  status: formData.get('status') as 'active' | 'inactive'
                });
              }}>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">الاسم</label>
                  <input
                    type="text"
                    name="name"
                    defaultValue={editingInvestor?.name || ''}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  />
                </div>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">البريد الإلكتروني</label>
                  <input
                    type="email"
                    name="email"
                    defaultValue={editingInvestor?.email || ''}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  />
                </div>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">رقم الهاتف</label>
                  <input
                    type="tel"
                    name="phone"
                    defaultValue={editingInvestor?.phone || ''}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  />
                </div>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">مبلغ الاستثمار</label>
                  <input
                    type="number"
                    name="investmentAmount"
                    defaultValue={editingInvestor?.investmentAmount || ''}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  />
                </div>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">تاريخ الاستثمار</label>
                  <input
                    type="date"
                    name="investmentDate"
                    defaultValue={editingInvestor?.investmentDate || ''}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  />
                </div>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">تحمل المخاطر</label>
                  <select
                    name="riskTolerance"
                    defaultValue={editingInvestor?.riskTolerance || 'medium'}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  >
                    <option value="low">منخفض</option>
                    <option value="medium">متوسط</option>
                    <option value="high">مرتفع</option>
                  </select>
                </div>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">الحالة</label>
                  <select
                    name="status"
                    defaultValue={editingInvestor?.status || 'active'}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  >
                    <option value="active">نشط</option>
                    <option value="inactive">غير نشط</option>
                  </select>
                </div>
                <div className="flex justify-end space-x-4">
                  <button
                    type="button"
                    onClick={() => {
                      setShowModal(false);
                      setEditingInvestor(null);
                    }}
                    className="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600"
                  >
                    إلغاء
                  </button>
                  <button
                    type="submit"
                    className="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600"
                  >
                    حفظ
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      )}

      {/* Investment Modal */}
      {showInvestmentModal && (
        <div className="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
          <div className="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div className="mt-3">
              <h3 className="text-lg font-medium text-gray-900 mb-4">
                {editingInvestment ? 'تعديل الاستثمار' : 'إضافة استثمار جديد'}
              </h3>
              <form onSubmit={(e) => {
                e.preventDefault();
                const formData = new FormData(e.target as HTMLFormElement);
                handleSaveInvestment({
                  investorId: Number(formData.get('investorId')),
                  type: formData.get('type') as 'stocks' | 'bonds' | 'real_estate' | 'crypto',
                  amount: Number(formData.get('amount')),
                  date: formData.get('date') as string,
                  expectedReturn: Number(formData.get('expectedReturn')),
                  actualReturn: formData.get('actualReturn') ? Number(formData.get('actualReturn')) : undefined,
                  status: formData.get('status') as 'active' | 'completed' | 'cancelled'
                });
              }}>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">المستثمر</label>
                  <select
                    name="investorId"
                    defaultValue={editingInvestment?.investorId || ''}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  >
                    <option value="">اختر المستثمر</option>
                    {investors.map(investor => (
                      <option key={investor.id} value={investor.id}>{investor.name}</option>
                    ))}
                  </select>
                </div>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">نوع الاستثمار</label>
                  <select
                    name="type"
                    defaultValue={editingInvestment?.type || 'stocks'}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  >
                    <option value="stocks">أسهم</option>
                    <option value="bonds">سندات</option>
                    <option value="real_estate">عقارات</option>
                    <option value="crypto">عملات رقمية</option>
                  </select>
                </div>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">المبلغ</label>
                  <input
                    type="number"
                    name="amount"
                    defaultValue={editingInvestment?.amount || ''}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  />
                </div>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">تاريخ الاستثمار</label>
                  <input
                    type="date"
                    name="date"
                    defaultValue={editingInvestment?.date || ''}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  />
                </div>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">العائد المتوقع (%)</label>
                  <input
                    type="number"
                    step="0.01"
                    name="expectedReturn"
                    defaultValue={editingInvestment?.expectedReturn || ''}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  />
                </div>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">العائد الفعلي (%) - اختياري</label>
                  <input
                    type="number"
                    step="0.01"
                    name="actualReturn"
                    defaultValue={editingInvestment?.actualReturn || ''}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  />
                </div>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">الحالة</label>
                  <select
                    name="status"
                    defaultValue={editingInvestment?.status || 'active'}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  >
                    <option value="active">نشط</option>
                    <option value="completed">مكتمل</option>
                    <option value="cancelled">ملغى</option>
                  </select>
                </div>
                <div className="flex justify-end space-x-4">
                  <button
                    type="button"
                    onClick={() => {
                      setShowInvestmentModal(false);
                      setEditingInvestment(null);
                    }}
                    className="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600"
                  >
                    إلغاء
                  </button>
                  <button
                    type="submit"
                    className="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600"
                  >
                    حفظ
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default InvestorSystem;
                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                      <button
                        onClick={() => {
                          setEditingInvestor(investor);
                          setShowModal(true);
                        }}
                        className="text-indigo-600 hover:text-indigo-900 mr-4"
                      >
                        تعديل
                      </button>
                      <button
                        onClick={() => handleDeleteInvestor(investor.id)}
                        className="text-red-600 hover:text-red-900"
                      >
                        حذف
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* Investments Content */}
      {activeTab === 'investments' && (
        <div>
          <div className="flex justify-between items-center mb-6">
            <div className="flex items-center space-x-4">
              <input
                type="text"
                placeholder="البحث في الاستثمارات..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
            <button
              onClick={() => {
                setEditingInvestment(null);
                setShowInvestmentModal(true);
              }}
              className="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600"
            >
              إضافة استثمار جديد
            </button>
          </div>

          <div className="bg-white rounded-lg shadow overflow-hidden">
            <table className="min-w-full divide-y divide-gray-200">
              <thead className="bg-gray-50">
                <tr>
                  <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">المستثمر</th>
                  <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">نوع الاستثمار</th>
                  <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">المبلغ</th>
                  <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">العائد المتوقع</th>
                  <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">العائد الفعلي</th>
                  <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الحالة</th>
                  <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">إجراءات</th>
                </tr>
              </thead>
              <tbody className="bg-white divide-y divide-gray-200">
                {filteredInvestments.map((investment) => {
                  const investor = investors.find(inv => inv.id === investment.investorId);
                  return (
                    <tr key={investment.id}>
                      <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{investor?.name}</td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{getInvestmentTypeText(investment.type)}</td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{investment.amount.toLocaleString()} ريال</td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{investment.expectedReturn}%</td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {investment.actualReturn ? `${investment.actualReturn}%` : 'غير محدد'}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${
                          investment.status === 'active' ? 'bg-green-100 text-green-800' :
                          investment.status === 'completed' ? 'bg-blue-100 text-blue-800' :
                          'bg-red-100 text-red-800'
                        }`}>
                          {getStatusText(investment.status)}
                        </span>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <button
                          onClick={() => {
                            setEditingInvestment(investment);
                            setShowInvestmentModal(true);
                          }}
                          className="text-indigo-600 hover:text-indigo-900 mr-4"
                        >
                          تعديل
                        </button>
                        <button
                          onClick={() => handleDeleteInvestment(investment.id)}
                          className="text-red-600 hover:text-red-900"
                        >
                          حذف
                        </button>
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* Investor Modal */}
      {showModal && (
        <div className="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
          <div className="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div className="mt-3">
              <h3 className="text-lg font-medium text-gray-900 mb-4">
                {editingInvestor ? 'تعديل المستثمر' : 'إضافة مستثمر جديد'}
              </h3>
              <form onSubmit={(e) => {
                e.preventDefault();
                const formData = new FormData(e.target as HTMLFormElement);
                handleSaveInvestor({
                  name: formData.get('name') as string,
                  email: formData.get('email') as string,
                  phone: formData.get('phone') as string,
                  investmentAmount: Number(formData.get('investmentAmount')),
                  investmentDate: formData.get('investmentDate') as string,
                  riskTolerance: formData.get('riskTolerance') as 'low' | 'medium' | 'high',
                  status: formData.get('status') as 'active' | 'inactive'
                });
              }}>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">الاسم</label>
                  <input
                    type="text"
                    name="name"
                    defaultValue={editingInvestor?.name || ''}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  />
                </div>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">البريد الإلكتروني</label>
                  <input
                    type="email"
                    name="email"
                    defaultValue={editingInvestor?.email || ''}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  />
                </div>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">رقم الهاتف</label>
                  <input
                    type="tel"
                    name="phone"
                    defaultValue={editingInvestor?.phone || ''}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  />
                </div>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">مبلغ الاستثمار</label>
                  <input
                    type="number"
                    name="investmentAmount"
                    defaultValue={editingInvestor?.investmentAmount || ''}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  />
                </div>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">تاريخ الاستثمار</label>
                  <input
                    type="date"
                    name="investmentDate"
                    defaultValue={editingInvestor?.investmentDate || ''}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  />
                </div>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">تحمل المخاطر</label>
                  <select
                    name="riskTolerance"
                    defaultValue={editingInvestor?.riskTolerance || 'medium'}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  >
                    <option value="low">منخفض</option>
                    <option value="medium">متوسط</option>
                    <option value="high">مرتفع</option>
                  </select>
                </div>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">الحالة</label>
                  <select
                    name="status"
                    defaultValue={editingInvestor?.status || 'active'}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  >
                    <option value="active">نشط</option>
                    <option value="inactive">غير نشط</option>
                  </select>
                </div>
                <div className="flex justify-end space-x-4">
                  <button
                    type="button"
                    onClick={() => {
                      setShowModal(false);
                      setEditingInvestor(null);
                    }}
                    className="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600"
                  >
                    إلغاء
                  </button>
                  <button
                    type="submit"
                    className="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600"
                  >
                    حفظ
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      )}

      {/* Investment Modal */}
      {showInvestmentModal && (
        <div className="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
          <div className="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div className="mt-3">
              <h3 className="text-lg font-medium text-gray-900 mb-4">
                {editingInvestment ? 'تعديل الاستثمار' : 'إضافة استثمار جديد'}
              </h3>
              <form onSubmit={(e) => {
                e.preventDefault();
                const formData = new FormData(e.target as HTMLFormElement);
                handleSaveInvestment({
                  investorId: Number(formData.get('investorId')),
                  type: formData.get('type') as 'stocks' | 'bonds' | 'real_estate' | 'crypto',
                  amount: Number(formData.get('amount')),
                  date: formData.get('date') as string,
                  expectedReturn: Number(formData.get('expectedReturn')),
                  actualReturn: formData.get('actualReturn') ? Number(formData.get('actualReturn')) : undefined,
                  status: formData.get('status') as 'active' | 'completed' | 'cancelled'
                });
              }}>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">المستثمر</label>
                  <select
                    name="investorId"
                    defaultValue={editingInvestment?.investorId || ''}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  >
                    <option value="">اختر المستثمر</option>
                    {investors.map(investor => (
                      <option key={investor.id} value={investor.id}>{investor.name}</option>
                    ))}
                  </select>
                </div>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">نوع الاستثمار</label>
                  <select
                    name="type"
                    defaultValue={editingInvestment?.type || 'stocks'}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  >
                    <option value="stocks">أسهم</option>
                    <option value="bonds">سندات</option>
                    <option value="real_estate">عقارات</option>
                    <option value="crypto">عملات رقمية</option>
                  </select>
                </div>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">المبلغ</label>
                  <input
                    type="number"
                    name="amount"
                    defaultValue={editingInvestment?.amount || ''}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  />
                </div>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">تاريخ الاستثمار</label>
                  <input
                    type="date"
                    name="date"
                    defaultValue={editingInvestment?.date || ''}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  />
                </div>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">العائد المتوقع (%)</label>
                  <input
                    type="number"
                    step="0.01"
                    name="expectedReturn"
                    defaultValue={editingInvestment?.expectedReturn || ''}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  />
                </div>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">العائد الفعلي (%) - اختياري</label>
                  <input
                    type="number"
                    step="0.01"
                    name="actualReturn"
                    defaultValue={editingInvestment?.actualReturn || ''}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  />
                </div>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">الحالة</label>
                  <select
                    name="status"
                    defaultValue={editingInvestment?.status || 'active'}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  >
                    <option value="active">نشط</option>
                    <option value="completed">مكتمل</option>
                    <option value="cancelled">ملغى</option>
                  </select>
                </div>
                <div className="flex justify-end space-x-4">
                  <button
                    type="button"
                    onClick={() => {
                      setShowInvestmentModal(false);
                      setEditingInvestment(null);
                    }}
                    className="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600"
                  >
                    إلغاء
                  </button>
                  <button
                    type="submit"
                    className="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600"
                  >
                    حفظ
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default InvestorSystem;-nowrap text-sm text-gray-500">{investor.email}</td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                      <button
                        onClick={() => {
                          setEditingInvestor(investor);
                          setShowModal(true);
                        }}
                        className="text-indigo-600 hover:text-indigo-900 mr-4"
                      >
                        تعديل
                      </button>
                      <button
                        onClick={() => handleDeleteInvestor(investor.id)}
                        className="text-red-600 hover:text-red-900"
                      >
                        حذف
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* Investments Content */}
      {activeTab === 'investments' && (
        <div>
          <div className="flex justify-between items-center mb-6">
            <div className="flex items-center space-x-4">
              <input
                type="text"
                placeholder="البحث في الاستثمارات..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
            <button
              onClick={() => {
                setEditingInvestment(null);
                setShowInvestmentModal(true);
              }}
              className="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600"
            >
              إضافة استثمار جديد
            </button>
          </div>

          <div className="bg-white rounded-lg shadow overflow-hidden">
            <table className="min-w-full divide-y divide-gray-200">
              <thead className="bg-gray-50">
                <tr>
                  <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">المستثمر</th>
                  <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">نوع الاستثمار</th>
                  <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">المبلغ</th>
                  <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">العائد المتوقع</th>
                  <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">العائد الفعلي</th>
                  <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الحالة</th>
                  <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">إجراءات</th>
                </tr>
              </thead>
              <tbody className="bg-white divide-y divide-gray-200">
                {filteredInvestments.map((investment) => {
                  const investor = investors.find(inv => inv.id === investment.investorId);
                  return (
                    <tr key={investment.id}>
                      <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{investor?.name}</td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{getInvestmentTypeText(investment.type)}</td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{investment.amount.toLocaleString()} ريال</td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{investment.expectedReturn}%</td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {investment.actualReturn ? `${investment.actualReturn}%` : 'غير محدد'}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${
                          investment.status === 'active' ? 'bg-green-100 text-green-800' :
                          investment.status === 'completed' ? 'bg-blue-100 text-blue-800' :
                          'bg-red-100 text-red-800'
                        }`}>
                          {getStatusText(investment.status)}
                        </span>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <button
                          onClick={() => {
                            setEditingInvestment(investment);
                            setShowInvestmentModal(true);
                          }}
                          className="text-indigo-600 hover:text-indigo-900 mr-4"
                        >
                          تعديل
                        </button>
                        <button
                          onClick={() => handleDeleteInvestment(investment.id)}
                          className="text-red-600 hover:text-red-900"
                        >
                          حذف
                        </button>
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* Investor Modal */}
      {showModal && (
        <div className="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
          <div className="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div className="mt-3">
              <h3 className="text-lg font-medium text-gray-900 mb-4">
                {editingInvestor ? 'تعديل المستثمر' : 'إضافة مستثمر جديد'}
              </h3>
              <form onSubmit={(e) => {
                e.preventDefault();
                const formData = new FormData(e.target as HTMLFormElement);
                handleSaveInvestor({
                  name: formData.get('name') as string,
                  email: formData.get('email') as string,
                  phone: formData.get('phone') as string,
                  investmentAmount: Number(formData.get('investmentAmount')),
                  investmentDate: formData.get('investmentDate') as string,
                  riskTolerance: formData.get('riskTolerance') as 'low' | 'medium' | 'high',
                  status: formData.get('status') as 'active' | 'inactive'
                });
              }}>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">الاسم</label>
                  <input
                    type="text"
                    name="name"
                    defaultValue={editingInvestor?.name || ''}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  />
                </div>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">البريد الإلكتروني</label>
                  <input
                    type="email"
                    name="email"
                    defaultValue={editingInvestor?.email || ''}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  />
                </div>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">رقم الهاتف</label>
                  <input
                    type="tel"
                    name="phone"
                    defaultValue={editingInvestor?.phone || ''}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  />
                </div>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">مبلغ الاستثمار</label>
                  <input
                    type="number"
                    name="investmentAmount"
                    defaultValue={editingInvestor?.investmentAmount || ''}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  />
                </div>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">تاريخ الاستثمار</label>
                  <input
                    type="date"
                    name="investmentDate"
                    defaultValue={editingInvestor?.investmentDate || ''}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  />
                </div>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">تحمل المخاطر</label>
                  <select
                    name="riskTolerance"
                    defaultValue={editingInvestor?.riskTolerance || 'medium'}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  >
                    <option value="low">منخفض</option>
                    <option value="medium">متوسط</option>
                    <option value="high">مرتفع</option>
                  </select>
                </div>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">الحالة</label>
                  <select
                    name="status"
                    defaultValue={editingInvestor?.status || 'active'}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  >
                    <option value="active">نشط</option>
                    <option value="inactive">غير نشط</option>
                  </select>
                </div>
                <div className="flex justify-end space-x-4">
                  <button
                    type="button"
                    onClick={() => {
                      setShowModal(false);
                      setEditingInvestor(null);
                    }}
                    className="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600"
                  >
                    إلغاء
                  </button>
                  <button
                    type="submit"
                    className="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600"
                  >
                    حفظ
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      )}

      {/* Investment Modal */}
      {showInvestmentModal && (
        <div className="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
          <div className="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div className="mt-3">
              <h3 className="text-lg font-medium text-gray-900 mb-4">
                {editingInvestment ? 'تعديل الاستثمار' : 'إضافة استثمار جديد'}
              </h3>
              <form onSubmit={(e) => {
                e.preventDefault();
                const formData = new FormData(e.target as HTMLFormElement);
                handleSaveInvestment({
                  investorId: Number(formData.get('investorId')),
                  type: formData.get('type') as 'stocks' | 'bonds' | 'real_estate' | 'crypto',
                  amount: Number(formData.get('amount')),
                  date: formData.get('date') as string,
                  expectedReturn: Number(formData.get('expectedReturn')),
                  actualReturn: formData.get('actualReturn') ? Number(formData.get('actualReturn')) : undefined,
                  status: formData.get('status') as 'active' | 'completed' | 'cancelled'
                });
              }}>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">المستثمر</label>
                  <select
                    name="investorId"
                    defaultValue={editingInvestment?.investorId || ''}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  >
                    <option value="">اختر المستثمر</option>
                    {investors.map(investor => (
                      <option key={investor.id} value={investor.id}>{investor.name}</option>
                    ))}
                  </select>
                </div>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">نوع الاستثمار</label>
                  <select
                    name="type"
                    defaultValue={editingInvestment?.type || 'stocks'}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  >
                    <option value="stocks">أسهم</option>
                    <option value="bonds">سندات</option>
                    <option value="real_estate">عقارات</option>
                    <option value="crypto">عملات رقمية</option>
                  </select>
                </div>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">المبلغ</label>
                  <input
                    type="number"
                    name="amount"
                    defaultValue={editingInvestment?.amount || ''}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  />
                </div>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">تاريخ الاستثمار</label>
                  <input
                    type="date"
                    name="date"
                    defaultValue={editingInvestment?.date || ''}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  />
                </div>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">العائد المتوقع (%)</label>
                  <input
                    type="number"
                    step="0.01"
                    name="expectedReturn"
                    defaultValue={editingInvestment?.expectedReturn || ''}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  />
                </div>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">العائد الفعلي (%) - اختياري</label>
                  <input
                    type="number"
                    step="0.01"
                    name="actualReturn"
                    defaultValue={editingInvestment?.actualReturn || ''}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  />
                </div>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">الحالة</label>
                  <select
                    name="status"
                    defaultValue={editingInvestment?.status || 'active'}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  >
                    <option value="active">نشط</option>
                    <option value="completed">مكتمل</option>
                    <option value="cancelled">ملغى</option>
                  </select>
                </div>
                <div className="flex justify-end space-x-4">
                  <button
                    type="button"
                    onClick={() => {
                      setShowInvestmentModal(false);
                      setEditingInvestment(null);
                    }}
                    className="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600"
                  >
                    إلغاء
                  </button>
                  <button
                    type="submit"
                    className="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600"
                  >
                    حفظ
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default InvestorSystem;-nowrap text-sm text-gray-500">{investor.phone}</td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                      <button
                        onClick={() => {
                          setEditingInvestor(investor);
                          setShowModal(true);
                        }}
                        className="text-indigo-600 hover:text-indigo-900 mr-4"
                      >
                        تعديل
                      </button>
                      <button
                        onClick={() => handleDeleteInvestor(investor.id)}
                        className="text-red-600 hover:text-red-900"
                      >
                        حذف
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* Investments Content */}
      {activeTab === 'investments' && (
        <div>
          <div className="flex justify-between items-center mb-6">
            <div className="flex items-center space-x-4">
              <input
                type="text"
                placeholder="البحث في الاستثمارات..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
            <button
              onClick={() => {
                setEditingInvestment(null);
                setShowInvestmentModal(true);
              }}
              className="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600"
            >
              إضافة استثمار جديد
            </button>
          </div>

          <div className="bg-white rounded-lg shadow overflow-hidden">
            <table className="min-w-full divide-y divide-gray-200">
              <thead className="bg-gray-50">
                <tr>
                  <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">المستثمر</th>
                  <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">نوع الاستثمار</th>
                  <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">المبلغ</th>
                  <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">العائد المتوقع</th>
                  <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">العائد الفعلي</th>
                  <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الحالة</th>
                  <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">إجراءات</th>
                </tr>
              </thead>
              <tbody className="bg-white divide-y divide-gray-200">
                {filteredInvestments.map((investment) => {
                  const investor = investors.find(inv => inv.id === investment.investorId);
                  return (
                    <tr key={investment.id}>
                      <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{investor?.name}</td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{getInvestmentTypeText(investment.type)}</td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{investment.amount.toLocaleString()} ريال</td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{investment.expectedReturn}%</td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {investment.actualReturn ? `${investment.actualReturn}%` : 'غير محدد'}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${
                          investment.status === 'active' ? 'bg-green-100 text-green-800' :
                          investment.status === 'completed' ? 'bg-blue-100 text-blue-800' :
                          'bg-red-100 text-red-800'
                        }`}>
                          {getStatusText(investment.status)}
                        </span>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <button
                          onClick={() => {
                            setEditingInvestment(investment);
                            setShowInvestmentModal(true);
                          }}
                          className="text-indigo-600 hover:text-indigo-900 mr-4"
                        >
                          تعديل
                        </button>
                        <button
                          onClick={() => handleDeleteInvestment(investment.id)}
                          className="text-red-600 hover:text-red-900"
                        >
                          حذف
                        </button>
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* Investor Modal */}
      {showModal && (
        <div className="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
          <div className="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div className="mt-3">
              <h3 className="text-lg font-medium text-gray-900 mb-4">
                {editingInvestor ? 'تعديل المستثمر' : 'إضافة مستثمر جديد'}
              </h3>
              <form onSubmit={(e) => {
                e.preventDefault();
                const formData = new FormData(e.target as HTMLFormElement);
                handleSaveInvestor({
                  name: formData.get('name') as string,
                  email: formData.get('email') as string,
                  phone: formData.get('phone') as string,
                  investmentAmount: Number(formData.get('investmentAmount')),
                  investmentDate: formData.get('investmentDate') as string,
                  riskTolerance: formData.get('riskTolerance') as 'low' | 'medium' | 'high',
                  status: formData.get('status') as 'active' | 'inactive'
                });
              }}>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">الاسم</label>
                  <input
                    type="text"
                    name="name"
                    defaultValue={editingInvestor?.name || ''}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  />
                </div>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">البريد الإلكتروني</label>
                  <input
                    type="email"
                    name="email"
                    defaultValue={editingInvestor?.email || ''}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  />
                </div>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">رقم الهاتف</label>
                  <input
                    type="tel"
                    name="phone"
                    defaultValue={editingInvestor?.phone || ''}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  />
                </div>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">مبلغ الاستثمار</label>
                  <input
                    type="number"
                    name="investmentAmount"
                    defaultValue={editingInvestor?.investmentAmount || ''}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  />
                </div>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">تاريخ الاستثمار</label>
                  <input
                    type="date"
                    name="investmentDate"
                    defaultValue={editingInvestor?.investmentDate || ''}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  />
                </div>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">تحمل المخاطر</label>
                  <select
                    name="riskTolerance"
                    defaultValue={editingInvestor?.riskTolerance || 'medium'}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  >
                    <option value="low">منخفض</option>
                    <option value="medium">متوسط</option>
                    <option value="high">مرتفع</option>
                  </select>
                </div>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">الحالة</label>
                  <select
                    name="status"
                    defaultValue={editingInvestor?.status || 'active'}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  >
                    <option value="active">نشط</option>
                    <option value="inactive">غير نشط</option>
                  </select>
                </div>
                <div className="flex justify-end space-x-4">
                  <button
                    type="button"
                    onClick={() => {
                      setShowModal(false);
                      setEditingInvestor(null);
                    }}
                    className="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600"
                  >
                    إلغاء
                  </button>
                  <button
                    type="submit"
                    className="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600"
                  >
                    حفظ
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      )}

      {/* Investment Modal */}
      {showInvestmentModal && (
        <div className="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
          <div className="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div className="mt-3">
              <h3 className="text-lg font-medium text-gray-900 mb-4">
                {editingInvestment ? 'تعديل الاستثمار' : 'إضافة استثمار جديد'}
              </h3>
              <form onSubmit={(e) => {
                e.preventDefault();
                const formData = new FormData(e.target as HTMLFormElement);
                handleSaveInvestment({
                  investorId: Number(formData.get('investorId')),
                  type: formData.get('type') as 'stocks' | 'bonds' | 'real_estate' | 'crypto',
                  amount: Number(formData.get('amount')),
                  date: formData.get('date') as string,
                  expectedReturn: Number(formData.get('expectedReturn')),
                  actualReturn: formData.get('actualReturn') ? Number(formData.get('actualReturn')) : undefined,
                  status: formData.get('status') as 'active' | 'completed' | 'cancelled'
                });
              }}>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">المستثمر</label>
                  <select
                    name="investorId"
                    defaultValue={editingInvestment?.investorId || ''}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  >
                    <option value="">اختر المستثمر</option>
                    {investors.map(investor => (
                      <option key={investor.id} value={investor.id}>{investor.name}</option>
                    ))}
                  </select>
                </div>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">نوع الاستثمار</label>
                  <select
                    name="type"
                    defaultValue={editingInvestment?.type || 'stocks'}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  >
                    <option value="stocks">أسهم</option>
                    <option value="bonds">سندات</option>
                    <option value="real_estate">عقارات</option>
                    <option value="crypto">عملات رقمية</option>
                  </select>
                </div>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">المبلغ</label>
                  <input
                    type="number"
                    name="amount"
                    defaultValue={editingInvestment?.amount || ''}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  />
                </div>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">تاريخ الاستثمار</label>
                  <input
                    type="date"
                    name="date"
                    defaultValue={editingInvestment?.date || ''}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  />
                </div>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">العائد المتوقع (%)</label>
                  <input
                    type="number"
                    step="0.01"
                    name="expectedReturn"
                    defaultValue={editingInvestment?.expectedReturn || ''}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  />
                </div>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">العائد الفعلي (%) - اختياري</label>
                  <input
                    type="number"
                    step="0.01"
                    name="actualReturn"
                    defaultValue={editingInvestment?.actualReturn || ''}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  />
                </div>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">الحالة</label>
                  <select
                    name="status"
                    defaultValue={editingInvestment?.status || 'active'}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  >
                    <option value="active">نشط</option>
                    <option value="completed">مكتمل</option>
                    <option value="cancelled">ملغى</option>
                  </select>
                </div>
                <div className="flex justify-end space-x-4">
                  <button
                    type="button"
                    onClick={() => {
                      setShowInvestmentModal(false);
                      setEditingInvestment(null);
                    }}
                    className="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600"
                  >
                    إلغاء
                  </button>
                  <button
                    type="submit"
                    className="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600"
                  >
                    حفظ
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default InvestorSystem;-nowrap text-sm text-gray-500">{investor.investmentAmount.toLocaleString()} ريال</td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                      <button
                        onClick={() => {
                          setEditingInvestor(investor);
                          setShowModal(true);
                        }}
                        className="text-indigo-600 hover:text-indigo-900 mr-4"
                      >
                        تعديل
                      </button>
                      <button
                        onClick={() => handleDeleteInvestor(investor.id)}
                        className="text-red-600 hover:text-red-900"
                      >
                        حذف
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* Investments Content */}
      {activeTab === 'investments' && (
        <div>
          <div className="flex justify-between items-center mb-6">
            <div className="flex items-center space-x-4">
              <input
                type="text"
                placeholder="البحث في الاستثمارات..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
            <button
              onClick={() => {
                setEditingInvestment(null);
                setShowInvestmentModal(true);
              }}
              className="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600"
            >
              إضافة استثمار جديد
            </button>
          </div>

          <div className="bg-white rounded-lg shadow overflow-hidden">
            <table className="min-w-full divide-y divide-gray-200">
              <thead className="bg-gray-50">
                <tr>
                  <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">المستثمر</th>
                  <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">نوع الاستثمار</th>
                  <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">المبلغ</th>
                  <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">العائد المتوقع</th>
                  <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">العائد الفعلي</th>
                  <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الحالة</th>
                  <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">إجراءات</th>
                </tr>
              </thead>
              <tbody className="bg-white divide-y divide-gray-200">
                {filteredInvestments.map((investment) => {
                  const investor = investors.find(inv => inv.id === investment.investorId);
                  return (
                    <tr key={investment.id}>
                      <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{investor?.name}</td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{getInvestmentTypeText(investment.type)}</td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{investment.amount.toLocaleString()} ريال</td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{investment.expectedReturn}%</td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {investment.actualReturn ? `${investment.actualReturn}%` : 'غير محدد'}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${
                          investment.status === 'active' ? 'bg-green-100 text-green-800' :
                          investment.status === 'completed' ? 'bg-blue-100 text-blue-800' :
                          'bg-red-100 text-red-800'
                        }`}>
                          {getStatusText(investment.status)}
                        </span>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <button
                          onClick={() => {
                            setEditingInvestment(investment);
                            setShowInvestmentModal(true);
                          }}
                          className="text-indigo-600 hover:text-indigo-900 mr-4"
                        >
                          تعديل
                        </button>
                        <button
                          onClick={() => handleDeleteInvestment(investment.id)}
                          className="text-red-600 hover:text-red-900"
                        >
                          حذف
                        </button>
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* Investor Modal */}
      {showModal && (
        <div className="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
          <div className="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div className="mt-3">
              <h3 className="text-lg font-medium text-gray-900 mb-4">
                {editingInvestor ? 'تعديل المستثمر' : 'إضافة مستثمر جديد'}
              </h3>
              <form onSubmit={(e) => {
                e.preventDefault();
                const formData = new FormData(e.target as HTMLFormElement);
                handleSaveInvestor({
                  name: formData.get('name') as string,
                  email: formData.get('email') as string,
                  phone: formData.get('phone') as string,
                  investmentAmount: Number(formData.get('investmentAmount')),
                  investmentDate: formData.get('investmentDate') as string,
                  riskTolerance: formData.get('riskTolerance') as 'low' | 'medium' | 'high',
                  status: formData.get('status') as 'active' | 'inactive'
                });
              }}>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">الاسم</label>
                  <input
                    type="text"
                    name="name"
                    defaultValue={editingInvestor?.name || ''}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  />
                </div>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">البريد الإلكتروني</label>
                  <input
                    type="email"
                    name="email"
                    defaultValue={editingInvestor?.email || ''}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  />
                </div>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">رقم الهاتف</label>
                  <input
                    type="tel"
                    name="phone"
                    defaultValue={editingInvestor?.phone || ''}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  />
                </div>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">مبلغ الاستثمار</label>
                  <input
                    type="number"
                    name="investmentAmount"
                    defaultValue={editingInvestor?.investmentAmount || ''}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  />
                </div>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">تاريخ الاستثمار</label>
                  <input
                    type="date"
                    name="investmentDate"
                    defaultValue={editingInvestor?.investmentDate || ''}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  />
                </div>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">تحمل المخاطر</label>
                  <select
                    name="riskTolerance"
                    defaultValue={editingInvestor?.riskTolerance || 'medium'}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  >
                    <option value="low">منخفض</option>
                    <option value="medium">متوسط</option>
                    <option value="high">مرتفع</option>
                  </select>
                </div>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">الحالة</label>
                  <select
                    name="status"
                    defaultValue={editingInvestor?.status || 'active'}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  >
                    <option value="active">نشط</option>
                    <option value="inactive">غير نشط</option>
                  </select>
                </div>
                <div className="flex justify-end space-x-4">
                  <button
                    type="button"
                    onClick={() => {
                      setShowModal(false);
                      setEditingInvestor(null);
                    }}
                    className="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600"
                  >
                    إلغاء
                  </button>
                  <button
                    type="submit"
                    className="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600"
                  >
                    حفظ
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      )}

      {/* Investment Modal */}
      {showInvestmentModal && (
        <div className="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
          <div className="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div className="mt-3">
              <h3 className="text-lg font-medium text-gray-900 mb-4">
                {editingInvestment ? 'تعديل الاستثمار' : 'إضافة استثمار جديد'}
              </h3>
              <form onSubmit={(e) => {
                e.preventDefault();
                const formData = new FormData(e.target as HTMLFormElement);
                handleSaveInvestment({
                  investorId: Number(formData.get('investorId')),
                  type: formData.get('type') as 'stocks' | 'bonds' | 'real_estate' | 'crypto',
                  amount: Number(formData.get('amount')),
                  date: formData.get('date') as string,
                  expectedReturn: Number(formData.get('expectedReturn')),
                  actualReturn: formData.get('actualReturn') ? Number(formData.get('actualReturn')) : undefined,
                  status: formData.get('status') as 'active' | 'completed' | 'cancelled'
                });
              }}>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">المستثمر</label>
                  <select
                    name="investorId"
                    defaultValue={editingInvestment?.investorId || ''}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  >
                    <option value="">اختر المستثمر</option>
                    {investors.map(investor => (
                      <option key={investor.id} value={investor.id}>{investor.name}</option>
                    ))}
                  </select>
                </div>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">نوع الاستثمار</label>
                  <select
                    name="type"
                    defaultValue={editingInvestment?.type || 'stocks'}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  >
                    <option value="stocks">أسهم</option>
                    <option value="bonds">سندات</option>
                    <option value="real_estate">عقارات</option>
                    <option value="crypto">عملات رقمية</option>
                  </select>
                </div>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">المبلغ</label>
                  <input
                    type="number"
                    name="amount"
                    defaultValue={editingInvestment?.amount || ''}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  />
                </div>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">تاريخ الاستثمار</label>
                  <input
                    type="date"
                    name="date"
                    defaultValue={editingInvestment?.date || ''}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  />
                </div>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">العائد المتوقع (%)</label>
                  <input
                    type="number"
                    step="0.01"
                    name="expectedReturn"
                    defaultValue={editingInvestment?.expectedReturn || ''}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  />
                </div>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">العائد الفعلي (%) - اختياري</label>
                  <input
                    type="number"
                    step="0.01"
                    name="actualReturn"
                    defaultValue={editingInvestment?.actualReturn || ''}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  />
                </div>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">الحالة</label>
                  <select
                    name="status"
                    defaultValue={editingInvestment?.status || 'active'}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  >
                    <option value="active">نشط</option>
                    <option value="completed">مكتمل</option>
                    <option value="cancelled">ملغى</option>
                  </select>
                </div>
                <div className="flex justify-end space-x-4">
                  <button
                    type="button"
                    onClick={() => {
                      setShowInvestmentModal(false);
                      setEditingInvestment(null);
                    }}
                    className="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600"
                  >
                    إلغاء
                  </button>
                  <button
                    type="submit"
                    className="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600"
                  >
                    حفظ
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default InvestorSystem;-nowrap text-sm text-gray-500">{getRiskToleranceText(investor.riskTolerance)}</td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                      <button
                        onClick={() => {
                          setEditingInvestor(investor);
                          setShowModal(true);
                        }}
                        className="text-indigo-600 hover:text-indigo-900 mr-4"
                      >
                        تعديل
                      </button>
                      <button
                        onClick={() => handleDeleteInvestor(investor.id)}
                        className="text-red-600 hover:text-red-900"
                      >
                        حذف
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* Investments Content */}
      {activeTab === 'investments' && (
        <div>
          <div className="flex justify-between items-center mb-6">
            <div className="flex items-center space-x-4">
              <input
                type="text"
                placeholder="البحث في الاستثمارات..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
            <button
              onClick={() => {
                setEditingInvestment(null);
                setShowInvestmentModal(true);
              }}
              className="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600"
            >
              إضافة استثمار جديد
            </button>
          </div>

          <div className="bg-white rounded-lg shadow overflow-hidden">
            <table className="min-w-full divide-y divide-gray-200">
              <thead className="bg-gray-50">
                <tr>
                  <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">المستثمر</th>
                  <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">نوع الاستثمار</th>
                  <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">المبلغ</th>
                  <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">العائد المتوقع</th>
                  <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">العائد الفعلي</th>
                  <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الحالة</th>
                  <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">إجراءات</th>
                </tr>
              </thead>
              <tbody className="bg-white divide-y divide-gray-200">
                {filteredInvestments.map((investment) => {
                  const investor = investors.find(inv => inv.id === investment.investorId);
                  return (
                    <tr key={investment.id}>
                      <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{investor?.name}</td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{getInvestmentTypeText(investment.type)}</td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{investment.amount.toLocaleString()} ريال</td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{investment.expectedReturn}%</td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {investment.actualReturn ? `${investment.actualReturn}%` : 'غير محدد'}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${
                          investment.status === 'active' ? 'bg-green-100 text-green-800' :
                          investment.status === 'completed' ? 'bg-blue-100 text-blue-800' :
                          'bg-red-100 text-red-800'
                        }`}>
                          {getStatusText(investment.status)}
                        </span>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <button
                          onClick={() => {
                            setEditingInvestment(investment);
                            setShowInvestmentModal(true);
                          }}
                          className="text-indigo-600 hover:text-indigo-900 mr-4"
                        >
                          تعديل
                        </button>
                        <button
                          onClick={() => handleDeleteInvestment(investment.id)}
                          className="text-red-600 hover:text-red-900"
                        >
                          حذف
                        </button>
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* Investor Modal */}
      {showModal && (
        <div className="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
          <div className="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div className="mt-3">
              <h3 className="text-lg font-medium text-gray-900 mb-4">
                {editingInvestor ? 'تعديل المستثمر' : 'إضافة مستثمر جديد'}
              </h3>
              <form onSubmit={(e) => {
                e.preventDefault();
                const formData = new FormData(e.target as HTMLFormElement);
                handleSaveInvestor({
                  name: formData.get('name') as string,
                  email: formData.get('email') as string,
                  phone: formData.get('phone') as string,
                  investmentAmount: Number(formData.get('investmentAmount')),
                  investmentDate: formData.get('investmentDate') as string,
                  riskTolerance: formData.get('riskTolerance') as 'low' | 'medium' | 'high',
                  status: formData.get('status') as 'active' | 'inactive'
                });
              }}>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">الاسم</label>
                  <input
                    type="text"
                    name="name"
                    defaultValue={editingInvestor?.name || ''}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  />
                </div>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">البريد الإلكتروني</label>
                  <input
                    type="email"
                    name="email"
                    defaultValue={editingInvestor?.email || ''}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  />
                </div>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">رقم الهاتف</label>
                  <input
                    type="tel"
                    name="phone"
                    defaultValue={editingInvestor?.phone || ''}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  />
                </div>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">مبلغ الاستثمار</label>
                  <input
                    type="number"
                    name="investmentAmount"
                    defaultValue={editingInvestor?.investmentAmount || ''}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  />
                </div>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">تاريخ الاستثمار</label>
                  <input
                    type="date"
                    name="investmentDate"
                    defaultValue={editingInvestor?.investmentDate || ''}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  />
                </div>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">تحمل المخاطر</label>
                  <select
                    name="riskTolerance"
                    defaultValue={editingInvestor?.riskTolerance || 'medium'}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  >
                    <option value="low">منخفض</option>
                    <option value="medium">متوسط</option>
                    <option value="high">مرتفع</option>
                  </select>
                </div>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">الحالة</label>
                  <select
                    name="status"
                    defaultValue={editingInvestor?.status || 'active'}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  >
                    <option value="active">نشط</option>
                    <option value="inactive">غير نشط</option>
                  </select>
                </div>
                <div className="flex justify-end space-x-4">
                  <button
                    type="button"
                    onClick={() => {
                      setShowModal(false);
                      setEditingInvestor(null);
                    }}
                    className="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600"
                  >
                    إلغاء
                  </button>
                  <button
                    type="submit"
                    className="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600"
                  >
                    حفظ
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      )}

      {/* Investment Modal */}
      {showInvestmentModal && (
        <div className="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
          <div className="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div className="mt-3">
              <h3 className="text-lg font-medium text-gray-900 mb-4">
                {editingInvestment ? 'تعديل الاستثمار' : 'إضافة استثمار جديد'}
              </h3>
              <form onSubmit={(e) => {
                e.preventDefault();
                const formData = new FormData(e.target as HTMLFormElement);
                handleSaveInvestment({
                  investorId: Number(formData.get('investorId')),
                  type: formData.get('type') as 'stocks' | 'bonds' | 'real_estate' | 'crypto',
                  amount: Number(formData.get('amount')),
                  date: formData.get('date') as string,
                  expectedReturn: Number(formData.get('expectedReturn')),
                  actualReturn: formData.get('actualReturn') ? Number(formData.get('actualReturn')) : undefined,
                  status: formData.get('status') as 'active' | 'completed' | 'cancelled'
                });
              }}>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">المستثمر</label>
                  <select
                    name="investorId"
                    defaultValue={editingInvestment?.investorId || ''}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  >
                    <option value="">اختر المستثمر</option>
                    {investors.map(investor => (
                      <option key={investor.id} value={investor.id}>{investor.name}</option>
                    ))}
                  </select>
                </div>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">نوع الاستثمار</label>
                  <select
                    name="type"
                    defaultValue={editingInvestment?.type || 'stocks'}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  >
                    <option value="stocks">أسهم</option>
                    <option value="bonds">سندات</option>
                    <option value="real_estate">عقارات</option>
                    <option value="crypto">عملات رقمية</option>
                  </select>
                </div>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">المبلغ</label>
                  <input
                    type="number"
                    name="amount"
                    defaultValue={editingInvestment?.amount || ''}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  />
                </div>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">تاريخ الاستثمار</label>
                  <input
                    type="date"
                    name="date"
                    defaultValue={editingInvestment?.date || ''}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  />
                </div>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">العائد المتوقع (%)</label>
                  <input
                    type="number"
                    step="0.01"
                    name="expectedReturn"
                    defaultValue={editingInvestment?.expectedReturn || ''}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  />
                </div>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">العائد الفعلي (%) - اختياري</label>
                  <input
                    type="number"
                    step="0.01"
                    name="actualReturn"
                    defaultValue={editingInvestment?.actualReturn || ''}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  />
                </div>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">الحالة</label>
                  <select
                    name="status"
                    defaultValue={editingInvestment?.status || 'active'}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  >
                    <option value="active">نشط</option>
                    <option value="completed">مكتمل</option>
                    <option value="cancelled">ملغى</option>
                  </select>
                </div>
                <div className="flex justify-end space-x-4">
                  <button
                    type="button"
                    onClick={() => {
                      setShowInvestmentModal(false);
                      setEditingInvestment(null);
                    }}
                    className="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600"
                  >
                    إلغاء
                  </button>
                  <button
                    type="submit"
                    className="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600"
                  >
                    حفظ
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default InvestorSystem;-nowrap">
                      <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${
                        investor.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                      }`}>
                        {getStatusText(investor.status)}
                      </span>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                      <button
                        onClick={() => {
                          setEditingInvestor(investor);
                          setShowModal(true);
                        }}
                        className="text-indigo-600 hover:text-indigo-900 mr-4"
                      >
                        تعديل
                      </button>
                      <button
                        onClick={() => handleDeleteInvestor(investor.id)}
                        className="text-red-600 hover:text-red-900"
                      >
                        حذف
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* Investments Content */}
      {activeTab === 'investments' && (
        <div>
          <div className="flex justify-between items-center mb-6">
            <div className="flex items-center space-x-4">
              <input
                type="text"
                placeholder="البحث في الاستثمارات..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
            <button
              onClick={() => {
                setEditingInvestment(null);
                setShowInvestmentModal(true);
              }}
              className="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600"
            >
              إضافة استثمار جديد
            </button>
          </div>

          <div className="bg-white rounded-lg shadow overflow-hidden">
            <table className="min-w-full divide-y divide-gray-200">
              <thead className="bg-gray-50">
                <tr>
                  <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">المستثمر</th>
                  <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">نوع الاستثمار</th>
                  <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">المبلغ</th>
                  <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">العائد المتوقع</th>
                  <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">العائد الفعلي</th>
                  <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الحالة</th>
                  <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">إجراءات</th>
                </tr>
              </thead>
              <tbody className="bg-white divide-y divide-gray-200">
                {filteredInvestments.map((investment) => {
                  const investor = investors.find(inv => inv.id === investment.investorId);
                  return (
                    <tr key={investment.id}>
                      <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{investor?.name}</td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{getInvestmentTypeText(investment.type)}</td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{investment.amount.toLocaleString()} ريال</td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{investment.expectedReturn}%</td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {investment.actualReturn ? `${investment.actualReturn}%` : 'غير محدد'}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${
                          investment.status === 'active' ? 'bg-green-100 text-green-800' :
                          investment.status === 'completed' ? 'bg-blue-100 text-blue-800' :
                          'bg-red-100 text-red-800'
                        }`}>
                          {getStatusText(investment.status)}
                        </span>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <button
                          onClick={() => {
                            setEditingInvestment(investment);
                            setShowInvestmentModal(true);
                          }}
                          className="text-indigo-600 hover:text-indigo-900 mr-4"
                        >
                          تعديل
                        </button>
                        <button
                          onClick={() => handleDeleteInvestment(investment.id)}
                          className="text-red-600 hover:text-red-900"
                        >
                          حذف
                        </button>
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* Investor Modal */}
      {showModal && (
        <div className="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
          <div className="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div className="mt-3">
              <h3 className="text-lg font-medium text-gray-900 mb-4">
                {editingInvestor ? 'تعديل المستثمر' : 'إضافة مستثمر جديد'}
              </h3>
              <form onSubmit={(e) => {
                e.preventDefault();
                const formData = new FormData(e.target as HTMLFormElement);
                handleSaveInvestor({
                  name: formData.get('name') as string,
                  email: formData.get('email') as string,
                  phone: formData.get('phone') as string,
                  investmentAmount: Number(formData.get('investmentAmount')),
                  investmentDate: formData.get('investmentDate') as string,
                  riskTolerance: formData.get('riskTolerance') as 'low' | 'medium' | 'high',
                  status: formData.get('status') as 'active' | 'inactive'
                });
              }}>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">الاسم</label>
                  <input
                    type="text"
                    name="name"
                    defaultValue={editingInvestor?.name || ''}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  />
                </div>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">البريد الإلكتروني</label>
                  <input
                    type="email"
                    name="email"
                    defaultValue={editingInvestor?.email || ''}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  />
                </div>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">رقم الهاتف</label>
                  <input
                    type="tel"
                    name="phone"
                    defaultValue={editingInvestor?.phone || ''}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  />
                </div>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">مبلغ الاستثمار</label>
                  <input
                    type="number"
                    name="investmentAmount"
                    defaultValue={editingInvestor?.investmentAmount || ''}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  />
                </div>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">تاريخ الاستثمار</label>
                  <input
                    type="date"
                    name="investmentDate"
                    defaultValue={editingInvestor?.investmentDate || ''}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  />
                </div>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">تحمل المخاطر</label>
                  <select
                    name="riskTolerance"
                    defaultValue={editingInvestor?.riskTolerance || 'medium'}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  >
                    <option value="low">منخفض</option>
                    <option value="medium">متوسط</option>
                    <option value="high">مرتفع</option>
                  </select>
                </div>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">الحالة</label>
                  <select
                    name="status"
                    defaultValue={editingInvestor?.status || 'active'}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  >
                    <option value="active">نشط</option>
                    <option value="inactive">غير نشط</option>
                  </select>
                </div>
                <div className="flex justify-end space-x-4">
                  <button
                    type="button"
                    onClick={() => {
                      setShowModal(false);
                      setEditingInvestor(null);
                    }}
                    className="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600"
                  >
                    إلغاء
                  </button>
                  <button
                    type="submit"
                    className="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600"
                  >
                    حفظ
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      )}

      {/* Investment Modal */}
      {showInvestmentModal && (
        <div className="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
          <div className="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div className="mt-3">
              <h3 className="text-lg font-medium text-gray-900 mb-4">
                {editingInvestment ? 'تعديل الاستثمار' : 'إضافة استثمار جديد'}
              </h3>
              <form onSubmit={(e) => {
                e.preventDefault();
                const formData = new FormData(e.target as HTMLFormElement);
                handleSaveInvestment({
                  investorId: Number(formData.get('investorId')),
                  type: formData.get('type') as 'stocks' | 'bonds' | 'real_estate' | 'crypto',
                  amount: Number(formData.get('amount')),
                  date: formData.get('date') as string,
                  expectedReturn: Number(formData.get('expectedReturn')),
                  actualReturn: formData.get('actualReturn') ? Number(formData.get('actualReturn')) : undefined,
                  status: formData.get('status') as 'active' | 'completed' | 'cancelled'
                });
              }}>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">المستثمر</label>
                  <select
                    name="investorId"
                    defaultValue={editingInvestment?.investorId || ''}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  >
                    <option value="">اختر المستثمر</option>
                    {investors.map(investor => (
                      <option key={investor.id} value={investor.id}>{investor.name}</option>
                    ))}
                  </select>
                </div>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">نوع الاستثمار</label>
                  <select
                    name="type"
                    defaultValue={editingInvestment?.type || 'stocks'}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  >
                    <option value="stocks">أسهم</option>
                    <option value="bonds">سندات</option>
                    <option value="real_estate">عقارات</option>
                    <option value="crypto">عملات رقمية</option>
                  </select>
                </div>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">المبلغ</label>
                  <input
                    type="number"
                    name="amount"
                    defaultValue={editingInvestment?.amount || ''}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  />
                </div>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">تاريخ الاستثمار</label>
                  <input
                    type="date"
                    name="date"
                    defaultValue={editingInvestment?.date || ''}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  />
                </div>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">العائد المتوقع (%)</label>
                  <input
                    type="number"
                    step="0.01"
                    name="expectedReturn"
                    defaultValue={editingInvestment?.expectedReturn || ''}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  />
                </div>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">العائد الفعلي (%) - اختياري</label>
                  <input
                    type="number"
                    step="0.01"
                    name="actualReturn"
                    defaultValue={editingInvestment?.actualReturn || ''}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  />
                </div>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">الحالة</label>
                  <select
                    name="status"
                    defaultValue={editingInvestment?.status || 'active'}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  >
                    <option value="active">نشط</option>
                    <option value="completed">مكتمل</option>
                    <option value="cancelled">ملغى</option>
                  </select>
                </div>
                <div className="flex justify-end space-x-4">
                  <button
                    type="button"
                    onClick={() => {
                      setShowInvestmentModal(false);
                      setEditingInvestment(null);
                    }}
                    className="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600"
                  >
                    إلغاء
                  </button>
                  <button
                    type="submit"
                    className="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600"
                  >
                    حفظ
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default InvestorSystem;