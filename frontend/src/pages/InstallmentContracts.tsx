import React, { useState } from 'react';
import { PlusIcon, PencilIcon, TrashIcon, MagnifyingGlassIcon, EyeIcon } from '@heroicons/react/24/outline';

interface Contract {
  id: number;
  contractNumber: string;
  customerName: string;
  productName: string;
  totalAmount: number;
  downPayment: number;
  monthlyPayment: number;
  duration: number;
  startDate: string;
  status: 'active' | 'completed' | 'defaulted';
  paidInstallments: number;
}

const initialContracts: Contract[] = [
  { id: 1, contractNumber: 'CNT-2024-001', customerName: 'أحمد محمد علي', productName: 'تلفزيون سامسونج 55 بوصة', totalAmount: 15000, downPayment: 3000, monthlyPayment: 1000, duration: 12, startDate: '2024-01-15', status: 'active', paidInstallments: 3 },
  { id: 2, contractNumber: 'CNT-2024-002', customerName: 'فاطمة السيد', productName: 'ثلاجة LG 18 قدم', totalAmount: 12000, downPayment: 2400, monthlyPayment: 800, duration: 12, startDate: '2023-12-01', status: 'active', paidInstallments: 4 },
  { id: 3, contractNumber: 'CNT-2023-050', customerName: 'محمد إبراهيم', productName: 'غسالة توشيبا 7 كيلو', totalAmount: 8000, downPayment: 1600, monthlyPayment: 533, duration: 12, startDate: '2023-01-15', status: 'completed', paidInstallments: 12 },
];

export default function InstallmentContracts() {
  const [contracts, setContracts] = useState<Contract[]>(initialContracts);
  const [searchTerm, setSearchTerm] = useState('');
  const [showModal, setShowModal] = useState(false);
  const [showDetailsModal, setShowDetailsModal] = useState(false);
  const [selectedContract, setSelectedContract] = useState<Contract | null>(null);
  const [editingContract, setEditingContract] = useState<Contract | null>(null);
  const [formData, setFormData] = useState<Partial<Contract>>({
    contractNumber: '',
    customerName: '',
    productName: '',
    totalAmount: 0,
    downPayment: 0,
    monthlyPayment: 0,
    duration: 12,
    startDate: new Date().toISOString().split('T')[0],
    status: 'active',
    paidInstallments: 0,
  });

  const filteredContracts = contracts.filter(contract =>
    contract.contractNumber.toLowerCase().includes(searchTerm.toLowerCase()) ||
    contract.customerName.toLowerCase().includes(searchTerm.toLowerCase()) ||
    contract.productName.toLowerCase().includes(searchTerm.toLowerCase())
  );

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    // Handle form submission logic here
  };

  const handleEdit = (contract: Contract) => {
    setEditingContract(contract);
    setFormData(contract);
    setShowModal(true);
  };

  const handleDelete = (id: number) => {
    if (window.confirm('هل أنت متأكد من حذف هذا العقد؟')) {
      setContracts(prev => prev.filter(contract => contract.id !== id));
    }
  };

  const handleViewDetails = (contract: Contract) => {
    setSelectedContract(contract);
    setShowDetailsModal(true);
  };

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'active': return 'bg-green-100 text-green-800';
      case 'completed': return 'bg-blue-100 text-blue-800';
      case 'defaulted': return 'bg-red-100 text-red-800';
      default: return 'bg-gray-100 text-gray-800';
    }
  };

  const getStatusText = (status: string) => {
    switch (status) {
      case 'active': return 'نشط';
      case 'completed': return 'مكتمل';
      case 'defaulted': return 'متأخر';
      default: return status;
    }
  };

  return (
    <div className="p-6">
      <div className="sm:flex sm:items-center">
        <div className="sm:flex-auto">
          <h1 className="text-xl font-semibold text-gray-900">عقود التقسيط</h1>
          <p className="mt-2 text-sm text-gray-700">
            إدارة عقود التقسيط ومتابعة الأقساط
          </p>
        </div>
        <div className="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
          <button
            type="button"
            onClick={() => setShowModal(true)}
            className="inline-flex items-center justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:w-auto"
          >
            <PlusIcon className="h-4 w-4 ml-2" />
            عقد جديد
          </button>
        </div>
      </div>

      <div className="mt-4">
        <div className="relative">
          <div className="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
            <MagnifyingGlassIcon className="h-5 w-5 text-gray-400" />
          </div>
          <input
            type="text"
            placeholder="البحث في العقود..."
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            className="block w-full pr-10 border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
          />
        </div>
      </div>

      <div className="mt-8 flex flex-col">
        <div className="-my-2 -mx-4 overflow-x-auto sm:-mx-6 lg:-mx-8">
          <div className="inline-block min-w-full py-2 align-middle md:px-6 lg:px-8">
            <div className="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
              <table className="min-w-full divide-y divide-gray-300">
                <thead className="bg-gray-50">
                  <tr>
                    <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                      رقم العقد
                    </th>
                    <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                      اسم العميل
                    </th>
                    <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                      المنتج
                    </th>
                    <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                      إجمالي المبلغ
                    </th>
                    <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                      القسط الشهري
                    </th>
                    <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                      الحالة
                    </th>
                    <th className="relative px-6 py-3">
                      <span className="sr-only">الإجراءات</span>
                    </th>
                  </tr>
                </thead>
                <tbody className="bg-white divide-y divide-gray-200">
                  {filteredContracts.map((contract) => (
                    <tr key={contract.id}>
                      <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        {contract.contractNumber}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {contract.customerName}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {contract.productName}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {contract.totalAmount.toLocaleString()} جنيه
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {contract.monthlyPayment.toLocaleString()} جنيه
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${getStatusColor(contract.status)}`}>
                          {getStatusText(contract.status)}
                        </span>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <div className="flex items-center space-x-2 space-x-reverse">
                          <button
                            onClick={() => handleViewDetails(contract)}
                            className="text-indigo-600 hover:text-indigo-900"
                          >
                            <EyeIcon className="h-4 w-4" />
                          </button>
                          <button
                            onClick={() => handleEdit(contract)}
                            className="text-indigo-600 hover:text-indigo-900"
                          >
                            <PencilIcon className="h-4 w-4" />
                          </button>
                          <button
                            onClick={() => handleDelete(contract.id)}
                            className="text-red-600 hover:text-red-900"
                          >
                            <TrashIcon className="h-4 w-4" />
                          </button>
                        </div>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      {showDetailsModal && selectedContract && (
        <div className="fixed z-10 inset-0 overflow-y-auto">
          <div className="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div className="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onClick={() => setShowDetailsModal(false)}></div>
            <div className="inline-block align-bottom bg-white rounded-lg text-right overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
              <div className="bg-white px-4 pt-5 pb-4 sm:p-6">
                <h3 className="text-lg font-medium text-gray-900 mb-4">تفاصيل العقد</h3>
                <div className="space-y-3">
                  <div className="flex justify-between">
                    <span className="text-gray-600">رقم العقد:</span>
                    <span className="font-medium">{selectedContract.contractNumber}</span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-gray-600">اسم العميل:</span>
                    <span className="font-medium">{selectedContract.customerName}</span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-gray-600">المنتج:</span>
                    <span className="font-medium">{selectedContract.productName}</span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-gray-600">إجمالي المبلغ:</span>
                    <span className="font-medium">{selectedContract.totalAmount.toLocaleString()} جنيه</span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-gray-600">المقدم:</span>
                    <span className="font-medium">{selectedContract.downPayment.toLocaleString()} جنيه</span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-gray-600">القسط الشهري:</span>
                    <span className="font-medium">{selectedContract.monthlyPayment.toLocaleString()} جنيه</span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-gray-600">عدد الأقساط المدفوعة:</span>
                    <span className="font-medium">{selectedContract.paidInstallments} من {selectedContract.duration}</span>
                  </div>
                </div>
              </div>
              <div className="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button
                  type="button"
                  className="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm"
                  onClick={() => setShowDetailsModal(false)}
                >
                  إغلاق
                </button>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};