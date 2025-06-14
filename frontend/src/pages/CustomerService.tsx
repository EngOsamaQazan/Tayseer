import React, { useState } from 'react';
import { PlusIcon, PencilIcon, TrashIcon, MagnifyingGlassIcon, ChatBubbleLeftRightIcon, CheckCircleIcon, ClockIcon, ExclamationCircleIcon } from '@heroicons/react/24/outline';

interface Ticket {
  id: number;
  ticketNumber: string;
  customerName: string;
  customerPhone: string;
  type: 'complaint' | 'inquiry' | 'request' | 'suggestion';
  category: 'product' | 'payment' | 'delivery' | 'service' | 'other';
  priority: 'low' | 'medium' | 'high' | 'urgent';
  status: 'open' | 'in-progress' | 'resolved' | 'closed';
  assignedTo: string;
  createdDate: string;
  lastUpdate: string;
  subject: string;
  description: string;
  resolution?: string;
}

const initialTickets: Ticket[] = [
  { id: 1, ticketNumber: 'TKT-2024-001', customerName: 'أحمد محمد', customerPhone: '01012345678', type: 'complaint', category: 'payment', priority: 'high', status: 'in-progress', assignedTo: 'سارة أحمد', createdDate: '2024-03-14', lastUpdate: '2024-03-15', subject: 'مشكلة في دفع القسط', description: 'العميل يواجه مشكلة في دفع القسط الشهري عبر التطبيق' },
  { id: 2, ticketNumber: 'TKT-2024-002', customerName: 'فاطمة السيد', customerPhone: '01123456789', type: 'inquiry', category: 'product', priority: 'medium', status: 'resolved', assignedTo: 'محمد علي', createdDate: '2024-03-13', lastUpdate: '2024-03-14', subject: 'استفسار عن مواصفات منتج', description: 'العميلة تريد معرفة مواصفات الثلاجة موديل XYZ', resolution: 'تم إرسال المواصفات التفصيلية للعميلة عبر البريد الإلكتروني' },
  { id: 3, ticketNumber: 'TKT-2024-003', customerName: 'محمود حسن', customerPhone: '01234567890', type: 'request', category: 'delivery', priority: 'urgent', status: 'open', assignedTo: 'أحمد سالم', createdDate: '2024-03-15', lastUpdate: '2024-03-15', subject: 'طلب تغيير موعد التسليم', description: 'العميل يريد تغيير موعد تسليم الطلب رقم ORD-2024-123' },
  { id: 4, ticketNumber: 'TKT-2024-004', customerName: 'سعاد عبدالله', customerPhone: '01345678901', type: 'suggestion', category: 'service', priority: 'low', status: 'closed', assignedTo: 'سارة أحمد', createdDate: '2024-03-10', lastUpdate: '2024-03-11', subject: 'اقتراح تحسين الخدمة', description: 'اقتراح إضافة خدمة الدفع بالبطاقات الائتمانية', resolution: 'تم رفع الاقتراح للإدارة للدراسة' },
];

const agents = ['سارة أحمد', 'محمد علي', 'أحمد سالم', 'فاطمة محمود'];

export default function CustomerService() {
  const [tickets, setTickets] = useState<Ticket[]>(initialTickets);
  const [searchTerm, setSearchTerm] = useState('');
  const [filterStatus, setFilterStatus] = useState<string>('all');
  const [filterType, setFilterType] = useState<string>('all');
  const [showModal, setShowModal] = useState(false);
  const [showDetailsModal, setShowDetailsModal] = useState(false);
  const [selectedTicket, setSelectedTicket] = useState<Ticket | null>(null);
  const [editingTicket, setEditingTicket] = useState<Ticket | null>(null);
  const [formData, setFormData] = useState<Partial<Ticket>>({
    customerName: '',
    customerPhone: '',
    type: 'inquiry',
    category: 'product',
    priority: 'medium',
    status: 'open',
    assignedTo: '',
    subject: '',
    description: '',
    resolution: '',
  });

  const filteredTickets = tickets.filter(ticket => {
    const matchesSearch = ticket.ticketNumber.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         ticket.customerName.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         ticket.customerPhone.includes(searchTerm) ||
                         ticket.subject.toLowerCase().includes(searchTerm.toLowerCase());
    const matchesStatus = filterStatus === 'all' || ticket.status === filterStatus;
    const matchesType = filterType === 'all' || ticket.type === filterType;
    return matchesSearch && matchesStatus && matchesType;
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (editingTicket) {
      setTickets(tickets.map(t => t.id === editingTicket.id ? { ...t, ...formData, lastUpdate: new Date().toISOString().split('T')[0] } : t));
    } else {
      const newTicket: Ticket = {
        id: tickets.length + 1,
        ticketNumber: `TKT-${new Date().getFullYear()}-${String(tickets.length + 1).padStart(3, '0')}`,
        customerName: formData.customerName!,
        customerPhone: formData.customerPhone!,
        type: formData.type as 'complaint' | 'inquiry' | 'request' | 'suggestion',
        category: formData.category as 'product' | 'payment' | 'delivery' | 'service' | 'other',
        priority: formData.priority as 'low' | 'medium' | 'high' | 'urgent',
        status: formData.status as 'open' | 'in-progress' | 'resolved' | 'closed',
        assignedTo: formData.assignedTo!,
        createdDate: new Date().toISOString().split('T')[0],
        lastUpdate: new Date().toISOString().split('T')[0],
        subject: formData.subject!,
        description: formData.description!,
        resolution: formData.resolution,
      };
      setTickets([...tickets, newTicket]);
    }
    handleCloseModal();
  };

  const handleEdit = (ticket: Ticket) => {
    setEditingTicket(ticket);
    setFormData(ticket);
    setShowModal(true);
  };

  const handleDelete = (id: number) => {
    if (window.confirm('هل أنت متأكد من حذف هذه التذكرة؟')) {
      setTickets(tickets.filter(t => t.id !== id));
    }
  };

  const handleCloseModal = () => {
    setShowModal(false);
    setEditingTicket(null);
    setFormData({
      customerName: '',
      customerPhone: '',
      type: 'inquiry',
      category: 'product',
      priority: 'medium',
      status: 'open',
      assignedTo: '',
      subject: '',
      description: '',
      resolution: '',
    });
  };

  const handleViewDetails = (ticket: Ticket) => {
    setSelectedTicket(ticket);
    setShowDetailsModal(true);
  };

  const getTypeText = (type: string) => {
    switch (type) {
      case 'complaint':
        return 'شكوى';
      case 'inquiry':
        return 'استفسار';
      case 'request':
        return 'طلب';
      case 'suggestion':
        return 'اقتراح';
      default:
        return type;
    }
  };

  const getCategoryText = (category: string) => {
    switch (category) {
      case 'product':
        return 'منتج';
      case 'payment':
        return 'دفع';
      case 'delivery':
        return 'توصيل';
      case 'service':
        return 'خدمة';
      case 'other':
        return 'أخرى';
      default:
        return category;
    }
  };

  const getPriorityBadge = (priority: string) => {
    switch (priority) {
      case 'low':
        return 'bg-gray-100 text-gray-800';
      case 'medium':
        return 'bg-blue-100 text-blue-800';
      case 'high':
        return 'bg-orange-100 text-orange-800';
      case 'urgent':
        return 'bg-red-100 text-red-800';
      default:
        return 'bg-gray-100 text-gray-800';
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
      case 'urgent':
        return 'عاجلة';
      default:
        return priority;
    }
  };

  const getStatusIcon = (status: string) => {
    switch (status) {
      case 'open':
        return <ClockIcon className="h-5 w-5 text-blue-500" />;
      case 'in-progress':
        return <ExclamationCircleIcon className="h-5 w-5 text-yellow-500" />;
      case 'resolved':
        return <CheckCircleIcon className="h-5 w-5 text-green-500" />;
      case 'closed':
        return <CheckCircleIcon className="h-5 w-5 text-gray-500" />;
      default:
        return null;
    }
  };

  const getStatusText = (status: string) => {
    switch (status) {
      case 'open':
        return 'مفتوحة';
      case 'in-progress':
        return 'قيد المعالجة';
      case 'resolved':
        return 'تم الحل';
      case 'closed':
        return 'مغلقة';
      default:
        return status;
    }
  };

  const getTicketStats = () => {
    const total = tickets.length;
    const open = tickets.filter(t => t.status === 'open').length;
    const inProgress = tickets.filter(t => t.status === 'in-progress').length;
    const resolved = tickets.filter(t => t.status === 'resolved').length;
    const urgent = tickets.filter(t => t.priority === 'urgent' && (t.status === 'open' || t.status === 'in-progress')).length;
    return { total, open, inProgress, resolved, urgent };
  };

  const stats = getTicketStats();

  return (
    <div className="space-y-6">
      <div className="sm:flex sm:items-center sm:justify-between">
        <h1 className="text-2xl font-bold text-gray-900">خدمة العملاء</h1>
        <button
          onClick={() => setShowModal(true)}
          className="mt-3 sm:mt-0 inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700"
        >
          <PlusIcon className="ml-2 h-4 w-4" />
          إضافة تذكرة جديدة
        </button>
      </div>

      {/* إحصائيات التذاكر */}
      <div className="grid grid-cols-1 gap-5 sm:grid-cols-5">
        <div className="bg-white overflow-hidden shadow rounded-lg">
          <div className="p-5">
            <div className="flex items-center">
              <div className="flex-shrink-0">
                <ChatBubbleLeftRightIcon className="h-12 w-12 text-gray-400" />
              </div>
              <div className="mr-5 w-0 flex-1">
                <dl>
                  <dt className="text-sm font-medium text-gray-500 truncate">إجمالي التذاكر</dt>
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
                <ClockIcon className="h-12 w-12 text-blue-500" />
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
                <ExclamationCircleIcon className="h-12 w-12 text-yellow-500" />
              </div>
              <div className="mr-5 w-0 flex-1">
                <dl>
                  <dt className="text-sm font-medium text-gray-500 truncate">قيد المعالجة</dt>
                  <dd className="text-lg font-medium text-gray-900">15</dd>
                </dl>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};