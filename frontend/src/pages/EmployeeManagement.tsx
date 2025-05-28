import React, { useState } from 'react';
import { PlusIcon, PencilIcon, TrashIcon, MagnifyingGlassIcon, PhoneIcon, EnvelopeIcon } from '@heroicons/react/24/outline';

interface Employee {
  id: number;
  employeeCode: string;
  name: string;
  position: string;
  department: string;
  phone: string;
  email: string;
  startDate: string;
  salary: number;
  status: 'active' | 'vacation' | 'resigned';
}

const initialEmployees: Employee[] = [
  { id: 1, employeeCode: 'EMP-001', name: 'محمود أحمد السيد', position: 'مدير المبيعات', department: 'المبيعات', phone: '01012345678', email: 'mahmoud@tayseer.com', startDate: '2022-01-15', salary: 12000, status: 'active' },
  { id: 2, employeeCode: 'EMP-002', name: 'سارة محمد عبدالله', position: 'محاسب', department: 'المحاسبة', phone: '01098765432', email: 'sara@tayseer.com', startDate: '2022-03-01', salary: 8000, status: 'active' },
  { id: 3, employeeCode: 'EMP-003', name: 'أحمد علي حسن', position: 'مندوب مبيعات', department: 'المبيعات', phone: '01234567890', email: 'ahmed@tayseer.com', startDate: '2023-06-15', salary: 6000, status: 'vacation' },
  { id: 4, employeeCode: 'EMP-004', name: 'فاطمة السيد محمد', position: 'خدمة عملاء', department: 'خدمة العملاء', phone: '01122334455', email: 'fatma@tayseer.com', startDate: '2023-09-01', salary: 5000, status: 'active' },
];

const departments = ['المبيعات', 'المحاسبة', 'المخزن', 'خدمة العملاء', 'الإدارة', 'القانونية'];
const positions = ['مدير عام', 'مدير قسم', 'مدير المبيعات', 'محاسب', 'مندوب مبيعات', 'أمين مخزن', 'خدمة عملاء', 'محامي'];

export default function EmployeeManagement() {
  const [employees, setEmployees] = useState<Employee[]>(initialEmployees);
  const [searchTerm, setSearchTerm] = useState('');
  const [filterDepartment, setFilterDepartment] = useState<string>('all');
  const [showModal, setShowModal] = useState(false);
  const [editingEmployee, setEditingEmployee] = useState<Employee | null>(null);
  const [formData, setFormData] = useState<Partial<Employee>>({
    employeeCode: '',
    name: '',
    position: '',
    department: '',
    phone: '',
    email: '',
    startDate: new Date().toISOString().split('T')[0],
    salary: 0,
    status: 'active',
  });

  const filteredEmployees = employees.filter(employee => {
    const matchesSearch = employee.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         employee.employeeCode.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         employee.phone.includes(searchTerm);
    const matchesDepartment = filterDepartment === 'all' || employee.department === filterDepartment;
    return matchesSearch && matchesDepartment;
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (editingEmployee) {
      setEmployees(employees.map(emp => emp.id === editingEmployee.id ? { ...emp, ...formData } : emp));
    } else {
      const newEmployee: Employee = {
        id: employees.length + 1,
        employeeCode: `EMP-${String(employees.length + 1).padStart(3, '0')}`,
        name: formData.name!,
        position: formData.position!,
        department: formData.department!,
        phone: formData.phone!,
        email: formData.email!,
        startDate: formData.startDate!,
        salary: formData.salary!,
        status: formData.status as 'active' | 'vacation' | 'resigned',
      };
      setEmployees([...employees, newEmployee]);
    }
    handleCloseModal();
  };

  const handleEdit = (employee: Employee) => {
    setEditingEmployee(employee);
    setFormData(employee);
    setShowModal(true);
  };

  const handleDelete = (id: number) => {
    if (window.confirm('هل أنت متأكد من حذف هذا الموظف؟')) {
      setEmployees(employees.filter(emp => emp.id !== id));
    }
  };

  const handleCloseModal = () => {
    setShowModal(false);
    setEditingEmployee(null);
    setFormData({
      employeeCode: '',
      name: '',
      position: '',
      department: '',
      phone: '',
      email: '',
      startDate: new Date().toISOString().split('T')[0],
      salary: 0,
      status: 'active',
    });
  };

  const getStatusBadge = (status: string) => {
    switch (status) {
      case 'active':
        return 'bg-green-100 text-green-800';
      case 'vacation':
        return 'bg-yellow-100 text-yellow-800';
      case 'resigned':
        return 'bg-gray-100 text-gray-800';
      default:
        return 'bg-gray-100 text-gray-800';
    }
  };

  const getStatusText = (status: string) => {
    switch (status) {
      case 'active':
        return 'نشط';
      case 'vacation':
        return 'إجازة';
      case 'resigned':
        return 'مستقيل';
      default:
        return status;
    }
  };

  return (
    <div className="space-y-6">
      <div className="sm:flex sm:items-center sm:justify-between">
        <h1 className="text-2xl font-bold text-gray-900">إدارة الموظفين</h1>
        <button
          onClick={() => setShowModal(true)}
          className="mt-3 sm:mt-0 inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700"
        >
          <PlusIcon className="ml-2 h-4 w-4" />
          إضافة موظف جديد
        </button>
      </div>

      <div className="bg-white shadow rounded-lg">
        <div className="p-4 border-b border-gray-200">
          <div className="sm:flex sm:items-center sm:justify-between">
            <div className="flex-1 ml-4">
              <div className="relative">
                <input
                  type="text"
                  placeholder="البحث بالاسم أو الرقم الوظيفي أو الهاتف..."
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
                value={filterDepartment}
                onChange={(e) => setFilterDepartment(e.target.value)}
              >
                <option value="all">جميع الأقسام</option>
                {departments.map(dept => (
                  <option key={dept} value={dept}>{dept}</option>
                ))}
              </select>
            </div>
          </div>
        </div>

        <div className="overflow-x-auto">
          <table className="min-w-full divide-y divide-gray-200">
            <thead className="bg-gray-50">
              <tr>
                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الرقم الوظيفي</th>
                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الاسم</th>
                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">المنصب</th>
                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">القسم</th>
                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">التواصل</th>
                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الراتب</th>
                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الحالة</th>
                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الإجراءات</th>
              </tr>
            </thead>
            <tbody className="bg-white divide-y divide-gray-200">
              {filteredEmployees.map((employee) => (
                <tr key={employee.id}>
                  <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{employee.employeeCode}</td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{employee.name}</td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{employee.position}</td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{employee.department}</td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    <div className="flex flex-col space-y-1">
                      <div className="flex items-center">
                        <PhoneIcon className="h-3 w-3 text-gray-400 ml-1" />
                        <span className="text-xs">{employee.phone}</span>
                      </div>
                      <div className="flex items-center">
                        <EnvelopeIcon className="h-3 w-3 text-gray-400 ml-1" />
                        <span className="text-xs">{employee.email}</span>
                      </div>
                    </div>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{employee.salary.toLocaleString()} جنيه</td>
                  <td className="px-6 py-4 whitespace-nowrap">
                    <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${getStatusBadge(employee.status)}`}>
                      {getStatusText(employee.status)}
                    </span>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    <button
                      onClick={() => handleEdit(employee)}
                      className="text-indigo-600 hover:text-indigo-900 ml-3"
                    >
                      <PencilIcon className="h-4 w-4" />
                    </button>
                    <button
                      onClick={() => handleDelete(employee.id)}
                      className="text-red-600 hover:text-red-900"
                    >
                      <TrashIcon className="h-4 w-4" />
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>

      {/* نموذج إضافة/تعديل موظف */}
      {showModal && (
        <div className="fixed z-10 inset-0 overflow-y-auto">
          <div className="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div className="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onClick={handleCloseModal}></div>
            <div className="inline-block align-bottom bg-white rounded-lg text-right overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
              <form onSubmit={handleSubmit}>
                <div className="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                  <h3 className="text-lg font-medium text-gray-900 mb-4">
                    {editingEmployee ? 'تعديل بيانات الموظف' : 'إضافة موظف جديد'}
                  </h3>
                  <div className="space-y-4">
                    <div>
                      <label className="block text-sm font-medium text-gray-700">الاسم الكامل</label>
                      <input
                        type="text"
                        required
                        className="mt-1 block w-full rounded-md border-