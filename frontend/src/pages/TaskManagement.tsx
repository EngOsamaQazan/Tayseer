import React, { useState } from 'react';
import { PlusIcon, PencilIcon, TrashIcon, MagnifyingGlassIcon, CheckCircleIcon, ClockIcon, ExclamationCircleIcon } from '@heroicons/react/24/outline';

interface Task {
  id: number;
  title: string;
  description: string;
  assignedTo: string;
  priority: 'low' | 'medium' | 'high' | 'urgent';
  status: 'pending' | 'in-progress' | 'completed' | 'cancelled';
  dueDate: string;
  createdDate: string;
  category: string;
}

const initialTasks: Task[] = [
  { id: 1, title: 'متابعة العميل أحمد محمد', description: 'متابعة سداد القسط الشهري المتأخر', assignedTo: 'محمود أحمد', priority: 'high', status: 'in-progress', dueDate: '2024-03-20', createdDate: '2024-03-10', category: 'تحصيل' },
  { id: 2, title: 'جرد المخزن الشهري', description: 'إجراء جرد شامل للمخزن وتحديث البيانات', assignedTo: 'علي حسن', priority: 'medium', status: 'pending', dueDate: '2024-03-25', createdDate: '2024-03-12', category: 'مخزن' },
  { id: 3, title: 'إعداد تقرير المبيعات', description: 'تقرير مفصل عن مبيعات الربع الأول', assignedTo: 'سارة محمد', priority: 'medium', status: 'completed', dueDate: '2024-03-15', createdDate: '2024-03-05', category: 'تقارير' },
  { id: 4, title: 'مراجعة عقد جديد', description: 'مراجعة قانونية لعقد التقسيط للعميل خالد', assignedTo: 'فاطمة السيد', priority: 'urgent', status: 'pending', dueDate: '2024-03-18', createdDate: '2024-03-14', category: 'قانوني' },
];

const employees = ['محمود أحمد', 'سارة محمد', 'أحمد علي', 'فاطمة السيد', 'علي حسن'];
const categories = ['مبيعات', 'تحصيل', 'مخزن', 'تقارير', 'قانوني', 'خدمة عملاء', 'أخرى'];

export default function TaskManagement() {
  const [tasks, setTasks] = useState<Task[]>(initialTasks);
  const [searchTerm, setSearchTerm] = useState('');
  const [filterStatus, setFilterStatus] = useState<string>('all');
  const [showModal, setShowModal] = useState(false);
  const [editingTask, setEditingTask] = useState<Task | null>(null);
  const [formData, setFormData] = useState<Partial<Task>>({
    title: '',
    description: '',
    assignedTo: '',
    priority: 'medium',
    status: 'pending',
    dueDate: '',
    category: '',
  });

  const filteredTasks = tasks.filter(task => {
    const matchesSearch = task.title.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         task.description.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         task.assignedTo.toLowerCase().includes(searchTerm.toLowerCase());
    const matchesStatus = filterStatus === 'all' || task.status === filterStatus;
    return matchesSearch && matchesStatus;
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (editingTask) {
      setTasks(tasks.map(task => task.id === editingTask.id ? { ...task, ...formData } : task));
    } else {
      const newTask: Task = {
        id: tasks.length + 1,
        title: formData.title!,
        description: formData.description!,
        assignedTo: formData.assignedTo!,
        priority: formData.priority as 'low' | 'medium' | 'high' | 'urgent',
        status: formData.status as 'pending' | 'in-progress' | 'completed' | 'cancelled',
        dueDate: formData.dueDate!,
        createdDate: new Date().toISOString().split('T')[0],
        category: formData.category!,
      };
      setTasks([...tasks, newTask]);
    }
    handleCloseModal();
  };

  const handleEdit = (task: Task) => {
    setEditingTask(task);
    setFormData(task);
    setShowModal(true);
  };

  const handleDelete = (id: number) => {
    if (window.confirm('هل أنت متأكد من حذف هذه المهمة؟')) {
      setTasks(tasks.filter(task => task.id !== id));
    }
  };

  const handleCloseModal = () => {
    setShowModal(false);
    setEditingTask(null);
    setFormData({
      title: '',
      description: '',
      assignedTo: '',
      priority: 'medium',
      status: 'pending',
      dueDate: '',
      category: '',
    });
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
      case 'pending':
        return <ClockIcon className="h-5 w-5 text-gray-500" />;
      case 'in-progress':
        return <ExclamationCircleIcon className="h-5 w-5 text-blue-500" />;
      case 'completed':
        return <CheckCircleIcon className="h-5 w-5 text-green-500" />;
      default:
        return null;
    }
  };

  const getStatusText = (status: string) => {
    switch (status) {
      case 'pending':
        return 'قيد الانتظار';
      case 'in-progress':
        return 'قيد التنفيذ';
      case 'completed':
        return 'مكتملة';
      case 'cancelled':
        return 'ملغاة';
      default:
        return status;
    }
  };

  const getTaskStats = () => {
    const total = tasks.length;
    const completed = tasks.filter(t => t.status === 'completed').length;
    const inProgress = tasks.filter(t => t.status === 'in-progress').length;
    const pending = tasks.filter(t => t.status === 'pending').length;
    const urgent = tasks.filter(t => t.priority === 'urgent' && t.status !== 'completed').length;
    return { total, completed, inProgress, pending, urgent };
  };

  const stats = getTaskStats();

  return (
    <div className="space-y-6">
      <div className="sm:flex sm:items-center sm:justify-between">
        <h1 className="text-2xl font-bold text-gray-900">إدارة المهام</h1>
        <button
          onClick={() => setShowModal(true)}
          className="mt-3 sm:mt-0 inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700"
        >
          <PlusIcon className="ml-2 h-4 w-4" />
          إضافة مهمة جديدة
        </button>
      </div>

      {/* إحصائيات المهام */}
      <div className="grid grid-cols-1 gap-5 sm:grid-cols-5">
        <div className="bg-white overflow-hidden shadow rounded-lg">
          <div className="p-5">
            <div className="flex items-center">
              <div className="flex-shrink-0">
                <div className="h-12 w-12 bg-gray-100 rounded-md flex items-center justify-center">
                  <span className="text-lg font-bold text-gray-900">{stats.total}</span>
                </div>
              </div>
              <div className="mr-5 w-0 flex-1">
                <dl>
                  <dt className="text-sm font-medium text-gray-500 truncate">إجمالي المهام</dt>
                </dl>
              </div>
            </div>
          </div>
        </div>
        <div className="bg-white overflow-hidden shadow rounded-lg">
          <div className="p-5">
            <div className="flex items-center">
              <div className="flex-shrink-0">
                <CheckCircleIcon className="h-12 w-12 text-green-500" />
              </div>
              <div className="mr-5 w-0 flex-1">
                <dl>
                  <dt className="text-sm font-medium text-gray-500 truncate">مكتملة</dt>
                  <dd className="text-lg font-medium text-gray-900">{stats.completed}</dd>
                </dl>
              </div>
            </div>
          </div>
        </div>
        <div className="bg-white overflow-hidden shadow rounded-lg">
          <div className="p-5">
            <div className="flex items-center">
              <div className="flex-shrink-0">
                <ExclamationCircleIcon className="h-12 w-12 text-blue-500" />
              </div>
              <div className="mr-5 w-0 flex-1">
                <dl>
                  <dt className="text-sm font-medium text-gray-500 truncate">قيد التنفيذ</dt>
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
                <ClockIcon className="h-12 w-12 text-gray-500" />
              </div>
              <div className="mr-5 w-0 flex-1">
                <dl>
                  <dt className="text-sm font-medium text-gray-500 truncate">قيد الانتظار</dt>
                  <dd className="text-lg font-medium text-gray-900">{stats.pending}</dd>
                </dl>
              </div>
            </div>
          </div>
        </div>
        <div className="bg-white overflow-hidden shadow rounded-lg">
          <div className="p-5">
            <div className="flex items-center">
              <div className="flex-shrink-0">
                <div className="h-12 w-12 bg-red-100 rounded-md flex items-center justify-center">
                  <span className="text-lg font-bold text-red-600">{stats.urgent}</span>
                </div>
              </div>
              <div className="mr-5 w-0 flex-1">
                <dl>
                  <dt className="text-sm font-medium text-gray-500 truncate">عاجلة</dt>
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
                  placeholder="البحث في المهام..."
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
                value={filterStatus}
                onChange={(e) => setFilterStatus(e.target.value)}
              >
                <option value="all">جميع المهام</option>
                <option value="pending">قيد الانتظار</option>
                <option value="in-progress">قيد التنفيذ</option>
                <option value="completed">مكتملة</option>
                <option value="cancelled">ملغاة</option>
              </select>
            </div>
          </div>
        </div>

        <div className="overflow-