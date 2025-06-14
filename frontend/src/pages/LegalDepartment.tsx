import React, { useState } from 'react';
import { PlusIcon, PencilIcon, TrashIcon, MagnifyingGlassIcon, DocumentTextIcon, ScaleIcon, ExclamationTriangleIcon } from '@heroicons/react/24/outline';

interface LegalCase {
  id: number;
  caseNumber: string;
  clientName: string;
  type: 'collection' | 'contract' | 'dispute' | 'consultation';
  status: 'open' | 'in-progress' | 'closed' | 'won' | 'lost';
  lawyer: string;
  filingDate: string;
  nextHearing: string;
  amount: number;
  description: string;
  priority: 'low' | 'medium' | 'high';
}

const initialCases: LegalCase[] = [
  { id: 1, caseNumber: 'CASE-2024-001', clientName: 'أحمد محمد سالم', type: 'collection', status: 'in-progress', lawyer: 'محمد عبدالله', filingDate: '2024-01-15', nextHearing: '2024-03-25', amount: 15000, description: 'قضية تحصيل أقساط متأخرة', priority: 'high' },
  { id: 2, caseNumber: 'CASE-2024-002', clientName: 'شركة النور للتجارة', type: 'contract', status: 'open', lawyer: 'فاطمة أحمد', filingDate: '2024-02-10', nextHearing: '2024-03-30', amount: 0, description: 'مراجعة عقد توريد', priority: 'medium' },
  { id: 3, caseNumber: 'CASE-2024-003', clientName: 'خالد عبدالرحمن', type: 'dispute', status: 'in-progress', lawyer: 'محمد عبدالله', filingDate: '2024-02-20', nextHearing: '2024-04-05', amount: 25000, description: 'نزاع على ملكية سلعة', priority: 'high' },
  { id: 4, caseNumber: 'CASE-2023-045', clientName: 'سعيد محمود', type: 'collection', status: 'won', lawyer: 'فاطمة أحمد', filingDate: '2023-11-01', nextHearing: '-', amount: 8000, description: 'قضية تحصيل - حكم لصالحنا', priority: 'low' },
];

const lawyers = ['محمد عبدالله', 'فاطمة أحمد', 'أحمد الشريف', 'سارة محمد'];

export default function LegalDepartment() {
  const [cases, setCases] = useState<LegalCase[]>(initialCases);
  const [searchTerm, setSearchTerm] = useState('');
  const [filterType, setFilterType] = useState<string>('all');
  const [filterStatus, setFilterStatus] = useState<string>('all');
  const [showModal, setShowModal] = useState(false);
  const [showDetailsModal, setShowDetailsModal] = useState(false);
  const [selectedCase, setSelectedCase] = useState<LegalCase | null>(null);
  const [editingCase, setEditingCase] = useState<LegalCase | null>(null);
  const [formData, setFormData] = useState<Partial<LegalCase>>({
    caseNumber: '',
    clientName: '',
    type: 'collection',
    status: 'open',
    lawyer: '',
    filingDate: new Date().toISOString().split('T')[0],
    nextHearing: '',
    amount: 0,
    description: '',
    priority: 'medium',
  });

  const filteredCases = cases.filter(legalCase => {
    const matchesSearch = legalCase.caseNumber.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         legalCase.clientName.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         legalCase.description.toLowerCase().includes(searchTerm.toLowerCase());
    const matchesType = filterType === 'all' || legalCase.type === filterType;
    const matchesStatus = filterStatus === 'all' || legalCase.status === filterStatus;
    return matchesSearch && matchesType && matchesStatus;
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (editingCase) {
      setCases(cases.map(c => c.id === editingCase.id ? { ...c, ...formData } : c));
    } else {
      const newCase: LegalCase = {
        id: cases.length + 1,
        caseNumber: `CASE-${new Date().getFullYear()}-${String(cases.length + 1).padStart(3, '0')}`,
        clientName: formData.clientName!,
        type: formData.type as 'collection' | 'contract' | 'dispute' | 'consultation',
        status: formData.status as 'open' | 'in-progress' | 'closed' | 'won' | 'lost',
        lawyer: formData.lawyer!,
        filingDate: formData.filingDate!,
        nextHearing: formData.nextHearing!,
        amount: formData.amount!,
        description: formData.description!,
        priority: formData.priority as 'low' | 'medium' | 'high',
      };
      setCases([...cases, newCase]);
    }
    handleCloseModal();
  };

  const handleEdit = (legalCase: LegalCase) => {
    setEditingCase(legalCase);
    setFormData(legalCase);
    setShowModal(true);
  };

  const handleDelete = (id: number) => {
    if (window.confirm('هل أنت متأكد من حذف هذه القضية؟')) {
      setCases(cases.filter(c => c.id !== id));
    }
  };

  const handleCloseModal = () => {
    setShowModal(false);
    setEditingCase(null);
    setFormData({
      caseNumber: '',
      clientName: '',
      type: 'collection',
      status: 'open',
      lawyer: '',
      filingDate: new Date().toISOString().split('T')[0],
      nextHearing: '',
      amount: 0,
      description: '',
      priority: 'medium',
    });
  };

  const handleViewDetails = (legalCase: LegalCase) => {
    setSelectedCase(legalCase);
    setShowDetailsModal(true);
  };

  const getTypeText = (type: string) => {
    switch (type) {
      case 'collection':
        return 'تحصيل';
      case 'contract':
        return 'عقود';
      case 'dispute':
        return 'نزاعات';
      case 'consultation':
        return 'استشارة';
      default:
        return type;
    }
  };

  const getStatusBadge = (status: string) => {
    switch (status) {
      case 'open':
        return 'bg-blue-100 text-blue-800';
      case 'in-progress':
        return 'bg-yellow-100 text-yellow-800';
      case 'closed':
        return 'bg-gray-100 text-gray-800';
      case 'won':
        return 'bg-green-100 text-green-800';
      case 'lost':
        return 'bg-red-100 text-red-800';
      default:
        return 'bg-gray-100 text-gray-800';
    }
  };

  const getStatusText = (status: string) => {
    switch (status) {
      case 'open':
        return 'مفتوحة';
      case 'in-progress':
        return 'قيد النظر';
      case 'closed':
        return 'مغلقة';
      case 'won':
        return 'محكوم لصالحنا';
      case 'lost':
        return 'محكوم ضدنا';
      default:
        return status;
    }
  };

  const getPriorityBadge = (priority: string) => {
    switch (priority) {
      case 'low':
        return 'bg-gray-100 text-gray-600';
      case 'medium':
        return 'bg-orange-100 text-orange-600';
      case 'high':
        return 'bg-red-100 text-red-600';
      default:
        return 'bg-gray-100 text-gray-600';
    }
  };

  const getPriorityText = (priority: string) => {
    switch (priority) {
      case 'low':
        return 'منخفضة';
      case 'medium':
        return 'متوسطة';
      case 'high':
        return 'عالية';
      default:
        return priority;
    }
  };

  const getCaseStats = () => {
    const total = cases.length;
    const open = cases.filter(c => c.status === 'open').length;
    const inProgress = cases.filter(c => c.status === 'in-progress').length;
    const won = cases.filter(c => c.status === 'won').length;
    const totalAmount = cases.filter(c => c.type === 'collection').reduce((sum, c) => sum + c.amount, 0);
    return { total, open, inProgress, won, totalAmount };
  };

  const stats = getCaseStats();

  return (
    <div className="space-y-6">
      <div className="sm:flex sm:items-center sm:justify-between">
        <h1 className="text-2xl font-bold text-gray-900">القسم القانوني</h1>
        <button
          onClick={() => setShowModal(true)}
          className="mt-3 sm:mt-0 inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700"
        >
          <PlusIcon className="ml-2 h-4 w-4" />
          إضافة قضية جديدة
        </button>
      </div>

      {/* إحصائيات القضايا */}
      <div className="grid grid-cols-1 gap-5 sm:grid-cols-5">
        <div className="bg-white overflow-hidden shadow rounded-lg">
          <div className="p-5">
            <div className="flex items-center">
              <div className="flex-shrink-0">
                <ScaleIcon className="h-12 w-12 text-gray-400" />
              </div>
              <div className="mr-5 w-0 flex-1">
                <dl>
                  <dt className="text-sm font-medium text-gray-500 truncate">إجمالي القضايا</dt>
                  <dd className="text-lg font-medium text-gray-900">{stats.total}</dd>
                </dl>
              </div>
            </div>
          </div>
        </div>
        <div className="bg-white overflow-hidden shadow rounded-lg">
          <div className="p-5">
            <div className="flex items-center">
              <div className="flex-shrink-0">
                <DocumentTextIcon className="h-12 w-12 text-blue-500" />
              </div>
              <div className="mr-5 w-0 flex-1">
                <dl>
                  <dt className="text-sm font-medium text-gray-500 truncate">مفتوحة</dt>
                  <dd className="text-lg font-medium text-gray-900">{stats.open}</dd>
                </dl>
              </div>
            </div>
          </div>
        </div>
        <div className="bg-white overflow-hidden shadow rounded-lg">
          <div className="p-5">
            <div className="flex items-center">
              <div className="flex-shrink-0">
                <ExclamationTriangleIcon className="h-12 w-12 text-yellow-500" />
              </div>
              <div className="mr-5 w-0 flex-1">
                <dl>
                  <dt className="text-sm font-medium text-gray-500 truncate">قيد النظر</dt>
                  <dd className="text-lg font-medium text-gray-900">{stats.inProgress}</dd>
                </dl>
              </div>
            </div>
          </div>
        </div>
        <div className="bg-white overflow-hidden shadow rounded-lg">
          <div className="p-5">
            <div className="flex items-center">
              <div className="flex-shrink-0">
                <div className="h-12 w-12 bg-green-100 rounded-md flex items-center justify-center">
                  <span className="text-lg font-bold text-green-600">{stats.won}</span>
                </div>
              </div>
              <div className="mr-5 w-0 flex-1">
                <dl>
                  <dt className="text-sm font-medium text-gray-500 truncate">محكوم لصالحنا</dt>
                </dl>
              </div>
            </div>
          </div>
        </div>
        <div className="bg-white overflow-hidden shadow rounded-lg">
          <div className="p-5">
            <div className="flex items-center">
              <div className="flex-shrink-0">
                <div className="text-2xl font-bold text-indigo-600">ج.م</div>
              </div>
              <div className="mr-5 w-0 flex-1">
                <dl>
                  <dt className="text-sm font-medium text-gray-500 truncate">مبالغ التحصيل</dt>
                  <dd className="text-lg font-medium text-gray-900">{stats.totalAmount.toLocaleString()}</dd>
                </dl>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div className="bg-white shadow rounded-lg">
        <div className="p-4 border-b border-gray-200">
          <h3 className="text-lg font-medium text-gray-900">القسم القانوني</h3>
        </div>
        <div className="p-4">
          {/* Content will be added here */}
        </div>
      </div>
    </div>
  );
};