import React, { useState } from 'react';
import { PlusIcon, PencilIcon, TrashIcon, MagnifyingGlassIcon, ArrowDownIcon, ArrowUpIcon } from '@heroicons/react/24/outline';

interface Transaction {
  id: number;
  date: string;
  type: 'income' | 'expense';
  category: string;
  description: string;
  amount: number;
  paymentMethod: string;
  reference: string;
  status: 'completed' | 'pending' | 'cancelled';
}

const initialTransactions: Transaction[] = [
  { id: 1, date: '2024-03-15', type: 'income', category: 'أقساط', description: 'قسط شهري - أحمد محمد', amount: 1000, paymentMethod: 'نقدي', reference: 'CNT-2024-001', status: 'completed' },
  { id: 2, date: '2024-03-14', type: 'expense', category: 'مشتريات', description: 'شراء مخزون - تلفزيونات سامسونج', amount: 75000, paymentMethod: 'تحويل بنكي', reference: 'PO-2024-015', status: 'completed' },
  { id: 3, date: '2024-03-13', type: 'income', category: 'مبيعات', description: 'دفعة مقدمة - فاطمة السيد', amount: 2400, paymentMethod: 'بطاقة ائتمان', reference: 'CNT-2024-002', status: 'completed' },
  { id: 4, date: '2024-03-12', type: 'expense', category: 'رواتب', description: 'رواتب الموظفين - مارس 2024', amount: 45000, paymentMethod: 'تحويل بنكي', reference: 'SAL-2024-03', status: 'pending' },
];

const categories = {
  income: ['مبيعات', 'أقساط', 'دفعات مقدمة', 'عوائد استثمار', 'أخرى'],
  expense: ['مشتريات', 'رواتب', 'إيجار', 'مصاريف تشغيل', 'ضرائب', 'أخرى']
};

export default function AccountingSystem() {
  const [transactions, setTransactions] = useState<Transaction[]>(initialTransactions);
  const [searchTerm, setSearchTerm] = useState('');
  const [filterType, setFilterType] = useState<'all' | 'income' | 'expense'>('all');
  const [showModal, setShowModal] = useState(false);
  const [editingTransaction, setEditingTransaction] = useState<Transaction | null>(null);
  const [formData, setFormData] = useState<Partial<Transaction>>({
    date: new Date().toISOString().split('T')[0],
    type: 'income',
    category: '',
    description: '',
    amount: 0,
    paymentMethod: 'نقدي',
    reference: '',
    status: 'pending',
  });

  const filteredTransactions = transactions.filter(transaction => {
    const matchesSearch = transaction.description.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         transaction.reference.toLowerCase().includes(searchTerm.toLowerCase());
    const matchesType = filterType === 'all' || transaction.type === filterType;
    return matchesSearch && matchesType;
  });

  const calculateTotals = () => {
    const income = transactions
      .filter(t => t.type === 'income' && t.status === 'completed')
      .reduce((sum, t) => sum + t.amount, 0);
    const expense = transactions
      .filter(t => t.type === 'expense' && t.status === 'completed')
      .reduce((sum, t) => sum + t.amount, 0);
    return { income, expense, balance: income - expense };
  };

  const totals = calculateTotals();

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (editingTransaction) {
      setTransactions(transactions.map(t => t.id === editingTransaction.id ? { ...t, ...formData } : t));
    } else {
      const newTransaction: Transaction = {
        id: transactions.length + 1,
        date: formData.date!,
        type: formData.type as 'income' | 'expense',
        category: formData.category!,
        description: formData.description!,
        amount: formData.amount!,
        paymentMethod: formData.paymentMethod!,
        reference: formData.reference!,
        status: formData.status as 'completed' | 'pending' | 'cancelled',
      };
      setTransactions([...transactions, newTransaction]);
    }
    handleCloseModal();
  };

  const handleEdit = (transaction: Transaction) => {
    setEditingTransaction(transaction);
    setFormData(transaction);
    setShowModal(true);
  };

  const handleDelete = (id: number) => {
    if (window.confirm('هل أنت متأكد من حذف هذه المعاملة؟')) {
      setTransactions(transactions.filter(t => t.id !== id));
    }
  };

  const handleCloseModal = () => {
    setShowModal(false);
    setEditingTransaction(null);
    setFormData({
      date: new Date().toISOString().split('T')[0],
      type: 'income',
      category: '',
      description: '',
      amount: 0,
      paymentMethod: 'نقدي',
      reference: '',
      status: 'pending',
    });
  };

  const getStatusBadge = (status: string) => {
    switch (status) {
      case 'completed':
        return 'bg-green-100 text-green-800';
      case 'pending':
        return 'bg-yellow-100 text-yellow-800';
      case 'cancelled':
        return 'bg-red-100 text-red-800';
      default:
        return 'bg-gray-100 text-gray-800';
    }
  };

  const getStatusText = (status: string) => {
    switch (status) {
      case 'completed':
        return 'مكتملة';
      case 'pending':
        return 'معلقة';
      case 'cancelled':
        return 'ملغاة';
      default:
        return status;
    }
  };

  return (
    <div className="space-y-6">
      <div className="sm:flex sm:items-center sm:justify-between">
        <h1 className="text-2xl font-bold text-gray-900">النظام المحاسبي</h1>
        <button
          onClick={() => setShowModal(true)}
          className="mt-3 sm:mt-0 inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700"
        >
          <PlusIcon className="ml-2 h-4 w-4" />
          إضافة معاملة جديدة
        </button>
      </div>

      {/* ملخص مالي */}
      <div className="grid grid-cols-1 gap-5 sm:grid-cols-3">
        <div className="bg-white overflow-hidden shadow rounded-lg">
          <div className="p-5">
            <div className="flex items-center">
              <div className="flex-shrink-0">
                <ArrowUpIcon className="h-6 w-6 text-green-600" />
              </div>
              <div className="mr-5 w-0 flex-1">
                <dl>
                  <dt className="text-sm font-medium text-gray-500 truncate">إجمالي الإيرادات</dt>
                  <dd className="text-lg font-medium text-gray-900">{totals.income.toLocaleString()} جنيه</dd>
                </dl>
              </div>
            </div>
          </div>
        </div>
        <div className="bg-white overflow-hidden shadow rounded-lg">
          <div className="p-5">
            <div className="flex items-center">
              <div className="flex-shrink-0">
                <ArrowDownIcon className="h-6 w-6 text-red-600" />
              </div>
              <div className="mr-5 w-0 flex-1">
                <dl>
                  <dt className="text-sm font-medium text-gray-500 truncate">إجمالي المصروفات</dt>
                  <dd className="text-lg font-medium text-gray-900">{totals.expense.toLocaleString()} جنيه</dd>
                </dl>
              </div>
            </div>
          </div>
        </div>
        <div className="bg-white overflow-hidden shadow rounded-lg">
          <div className="p-5">
            <div className="flex items-center">
              <div className="mr-5 w-0 flex-1">
                <dl>
                  <dt className="text-sm font-medium text-gray-500 truncate">الرصيد</dt>
                  <dd className={`text-lg font-medium ${totals.balance >= 0 ? 'text-green-600' : 'text-red-600'}`}>
                    {totals.balance.toLocaleString()} جنيه
                  </dd>
                </dl>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div className="bg-white shadow rounded-lg">
        <div className="p-4 border-b border-gray-200">
          <div className="sm:flex sm:items-center sm:justify-between">
            <div className="flex-1 ml-4">
              <div className="relative">
                <input
                  type="text"
                  placeholder="البحث في المعاملات..."
                  className="w-full pr-10 pl-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                />
                <MagnifyingGlassIcon className="absolute right-3 top-2.5 h-5 w-5 text-gray-400" />
              </div>
            </div>
            <div className="mt-3 sm:mt-0 sm:mr-0">
              <select
                className="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
                value={filterType}
                onChange={(e) => setFilterType(e.target.value as 'all' | 'income' | 'expense')}
              >
                <option value="all">جميع المعاملات</option>
                <option value="income">الإيرادات</option>
                <option value="expense">المصروفات</option>
              </select>
            </div>
          </div>
        </div>

        <div className="overflow-x-auto">
          <table className="min-w-full divide-y divide-gray-200">
            <thead className="bg-gray-50">
              <tr>
                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">التاريخ</th>
                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">النوع</th>
                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الفئة</th>
                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الوصف</th>
                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">المبلغ</th>
                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">طريقة الدفع</th>
                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الحالة</th>
                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الإجراءات</th>
              </tr>
            </thead>
            <tbody className="bg-white divide-y divide-gray-200">
              {filteredTransactions.map((transaction) => (
                <tr key={transaction.id}>
                  <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{transaction.date}</td>
                  <td className="px-6 py-4 whitespace-nowrap">
                    <span className={`inline-flex items-center px-2 py-1 text-xs font-medium rounded-full ${
                      transaction.type === 'income' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                    }`}>
                      {transaction.type === 'income' ? (
                        <><ArrowUpIcon className="ml-1 h-3 w-3" /> إيراد</>
                      ) : (
                        <><ArrowDownIcon className="ml-1 h-3 w-3" /> مصروف</>
                      )}
                    </span>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{transaction.category}</td>
                  <td className="px-6 py-4 text-sm text-gray-900">{transaction.description}</td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                    {transaction.amount.toLocaleString()} جنيه
                  </td>
                  <td className="px-6