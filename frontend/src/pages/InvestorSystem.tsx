import React, { useState } from 'react';
import { PlusIcon, PencilIcon, TrashIcon, MagnifyingGlassIcon, CurrencyDollarIcon, ChartBarIcon, UserGroupIcon, ArrowTrendingUpIcon, ArrowTrendingDownIcon } from '@heroicons/react/24/outline';

interface Investor {
  id: number;
  name: string;
  email: string;
  phone: string;
  nationalId: string;
  type: 'individual' | 'corporate' | 'institutional';
  status: 'active' | 'inactive' | 'pending';
  joinDate: string;
  totalInvestment: number;
  currentValue: number;
  returnRate: number;
  portfolioDetails: {
    contracts: number;
    activeContracts: number;
    completedContracts: number;
    defaultedContracts: number;
  };
}

interface Investment {
  id: number;
  investorId: number;
  investorName: string;
  amount: number;
  date: string;
  type: 'new' | 'additional' | 'withdrawal';
  status: 'completed' | 'pending' | 'cancelled';
  description: string;
}

const initialInvestors: Investor[] = [
  {
    id: 1,
    name: 'أحمد محمد سالم',
    email: 'ahmed@example.com',
    phone: '01012345678',
    nationalId: '29901011234567',
    type: 'individual',
    status: 'active',
    joinDate: '2023-01-15',
    totalInvestment: 500000,
    currentValue: 575000,
    returnRate: 15,
    portfolioDetails: {
      contracts: 45,
      activeContracts: 32,
      completedContracts: 12,
      defaultedContracts: 1
    }
  },
  {
    id: 2,
    name: 'شركة الأمان للاستثمار',
    email: 'info@alamaninvest.com',
    phone: '01123456789',
    nationalId: '100234567',
    type: 'corporate',
    status: 'active',
    joinDate: '2022-06-20',
    totalInvestment: 2000000,
    currentValue: 2450000,
    returnRate: 22.5,
    portfolioDetails: {
      contracts: 180,
      activeContracts: 145,
      completedContracts: 30,
      defaultedContracts: 5
    }
  },
  {
    id: 3,
    name: 'فاطمة عبدالله',
    email: 'fatma@example.com',
    phone: '01234567890',
    nationalId: '29812011234567',
    type: 'individual',
    status: 'active',
    joinDate: '2023-09-10',
    totalInvestment: 250000,
    currentValue: 268000,
    returnRate: 7.2,
    portfolioDetails: {
      contracts: 22,
      activeContracts: 20,
      completedContracts: 2,
      defaultedContracts: 0
    }
  },
  {
    id: 4,
    name: 'صندوق التنمية المحلية',
    email: 'contact@localdevelopment.gov',
    phone: '01345678901',
    nationalId: '200345678',
    type: 'institutional',
    status: 'active',
    joinDate: '2021-03-01',
    totalInvestment: 5000000,
    currentValue: 6250000,
    returnRate: 25,
    portfolioDetails: {
      contracts: 450,
      activeContracts: 380,
      completedContracts: 65,
      defaultedContracts: 5
    }
  }
];

const initialInvestments: Investment[] = [
  { id: 1, investorId: 1, investorName: 'أحمد محمد سالم', amount: 500000, date: '2023-01-15', type: 'new', status: 'completed', description: 'استثمار أولي' },
  { id: 2, investorId: 2, investorName: 'شركة الأمان للاستثمار', amount: 1500000, date: '2022-06-20', type: 'new', status: 'completed', description: 'استثمار أولي' },
  { id: 3, investorId: 2, investorName: 'شركة الأمان للاستثمار', amount: 500000, date: '2023-03-15', type: 'additional', status: 'completed', description: 'استثمار إضافي' },
  { id: 4, investorId: 3, investorName: 'فاطمة عبدالله', amount: 250000, date: '2023-09-10', type: 'new', status: 'completed', description: 'استثمار أولي' },
  { id: 5, investorId: 4, investorName: 'صندوق التنمية المحلية', amount: 5000000, date: '2021-03-01', type: 'new', status: 'completed', description: 'استثمار أولي' },
];

export default function InvestorSystem() {
  const [investors, setInvestors] = useState<Investor[]>(initialInvestors);
  const [investments, setInvestments] = useState<Investment[]>(initialInvestments);
  const [activeTab, setActiveTab] = useState<'investors' | 'investments' | 'dashboard'>('dashboard');
  const [searchTerm, setSearchTerm] = useState('');
  const [showModal, setShowModal] = useState(false);
  const [showInvestmentModal, setShowInvestmentModal] = useState(false);
  const [editingInvestor, setEditingInvestor] = useState<Investor | null>(null);
  const [formData, setFormData] = useState<Partial<Investor>>({
    name: '',
    email: '',
    phone: '',
    nationalId: '',
    type: 'individual',
    status: 'pending',
    totalInvestment: 0,
  });
  const [investmentFormData, setInvestmentFormData] = useState<Partial<Investment>>({
    investorId: 0,
    amount: 0,
    type: 'new',
    description: '',
  });

  const filteredInvestors = investors.filter(investor =>
    investor.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
    investor.email.toLowerCase().includes(searchTerm.toLowerCase()) ||
    investor.phone.includes(searchTerm) ||
    investor.nationalId.includes(searchTerm)
  );

  const filteredInvestments = investments.filter(investment =>
    investment.investorName.toLowerCase().includes(searchTerm.toLowerCase()) ||
    investment.description.toLowerCase().includes(searchTerm.toLowerCase())
  );

  // حساب إحصائيات النظام
  const systemStats = {
    totalInvestors: investors.length,
    activeInvestors: investors.filter(i => i.status === 'active').length,
    totalInvestment: investors.reduce((sum, i) => sum + i.totalInvestment, 0),
    totalCurrentValue: investors.reduce((sum, i) => sum + i.currentValue, 0),
    totalProfit: investors.reduce((sum, i) => sum + (i.currentValue - i.totalInvestment), 0),
    averageReturn: investors.reduce((sum, i) => sum + i.returnRate, 0) / investors.length,
    totalContracts: investors.reduce((sum, i) => sum + i.portfolioDetails.contracts, 0),
    activeContracts: investors.reduce((sum, i) => sum + i.portfolioDetails.activeContracts, 0),
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (editingInvestor) {
      setInvestors(investors.map(i => i.id === editingInvestor.id ? { ...i, ...formData } : i));
    } else {
      const newInvestor: Investor = {
        id: investors.length + 1,
        name: formData.name!,
        email: formData.email!,
        phone: formData.phone!,
        nationalId: formData.nationalId!,
        type: formData.type as 'individual' | 'corporate' | 'institutional',
        status: formData.status as 'active' | 'inactive' | 'pending',
        joinDate: new Date().toISOString().split('T')[0],
        totalInvestment: formData.totalInvestment!,
        currentValue: formData.totalInvestment!,
        returnRate: 0,
        portfolioDetails: {
          contracts: 0,
          activeContracts: 0,
          completedContracts: 0,
          defaultedContracts: 0
        }
      };
      setInvestors([...investors, newInvestor]);
    }
    handleCloseModal();
  };

  const handleInvestmentSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    const investor = investors.find(i => i.id === investmentFormData.investorId);
    if (investor) {
      const newInvestment: Investment = {
        id: investments.length + 1,
        investorId: investmentFormData.investorId!,
        investorName: investor.name,
        amount: investmentFormData.amount!,
        date: new Date().toISOString().split('T')[0],
        type: investmentFormData.type as 'new' | 'additional' | 'withdrawal',
        status: 'pending',
        description: investmentFormData.description!,
      };
      setInvestments([...investments, newInvestment]);
      
      // تحديث مبلغ الاستثمار للمستثمر
      if (investmentFormData.type === 'withdrawal') {
        setInvestors(investors.map(i => i.id === investor.id ? { ...i, totalInvestment: i.totalInvestment - investmentFormData.amount! } : i));
      } else {
        setInvestors(investors.map(i => i.id === investor.id ? { ...i, totalInvestment: i.totalInvestment + investmentFormData.amount! } : i));
      }
    }
    handleCloseInvestmentModal();
  };

  const handleEdit = (investor: Investor) => {
    setEditingInvestor(investor);
    setFormData(investor);
    setShowModal(true);
  };

  const handleDelete = (id: number) => {
    if (window.confirm('هل أنت متأكد من حذف هذا المستثمر؟')) {
      setInvestors(investors.filter(i => i.id !== id));
      setInvestments(investments.filter(i => i.investorId !== id));
    }
  };

  const handleCloseModal = () => {
    setShowModal(false);
    setEditingInvestor(null);
    setFormData({
      name: '',
      email: '',
      phone: '',
      nationalId: '',
      type: 'individual',
      status: 'pending',
      totalInvestment: 0,
    });
  };

  const handleCloseInvestmentModal = () => {
    setShowInvestmentModal(false);
    setInvestmentFormData({
      investorId: 0,
      amount: 0,
      type: 'new',
      description: '',
    });
  };

  const getTypeText = (type: string) => {
    switch (type) {
      case 'individual':
        return 'فرد';
      case 'corporate':
        return 'شركة';
      case 'institutional':
        return 'مؤسسة';
      default:
        return type;
    }
  };

  const getStatusBadge = (status: string) => {
    switch (status) {
      case 'active':
        return 'bg-green-100 text-green-800';
      case 'inactive':
        return 'bg-gray-100 text-gray-800';
      case 'pending':
        return 'bg-yellow-100 text-yellow-800';
      default:
        return 'bg-gray-100 text-gray-800';
    }
  };

  const getStatusText = (status: string) => {
    switch (status) {
      case 'active':
        return 'نشط';
      case 'inactive':
        return 'غير نشط';
      case 'pending':
        return 'قيد المراجعة';
      default:
        return status;
    }
  };

  const getInvestmentTypeText = (type: string) => {
    switch (type) {
      case 'new':
        return 'استثمار جديد';
      case 'additional':
        return 'استثمار إضافي';
      case 'withdrawal':
        return 'سحب';
      default:
        return type;
    }
  };

  return (
    <div className="space-y-6">
      <div className="sm:flex sm:items-center sm:justify-between">
        <h1 className="text-2xl font-bold text-gray-900">نظام المستثمرين</h1>
        <div className="mt-3 sm:mt-0 flex gap-2">
          <button
            onClick={() => setActiveTab('dashboard')}
            className={`px-4 py-2 text-sm font-medium rounded-md ${
              activeTab === 'dashboard'
                ? 'bg-indigo-600 text-white'
                : 'bg-white text-gray-700 hover:bg-gray-50'
            }`}
          >
            لوحة المعلومات
          </button>
          <button
            onClick={() => setActiveTab('investors')}
            className={`px-4 py-2 text-sm font-medium rounded-m