import React from 'react';
import { useNavigate } from 'react-router-dom';
import { UserCircleIcon, ArrowRightOnRectangleIcon } from '@heroicons/react/24/outline';

export default function Header() {
  const navigate = useNavigate();

  const handleLogout = () => {
    localStorage.removeItem('isAuthenticated');
    navigate('/login');
  };

  return (
    <header className="bg-white shadow">
      <div className="flex h-16 items-center justify-between px-4 sm:px-6 lg:px-8">
        <h2 className="text-lg font-semibold text-gray-900">لوحة التحكم</h2>
        <div className="flex items-center space-x-4 space-x-reverse">
          <div className="flex items-center space-x-2 space-x-reverse">
            <UserCircleIcon className="h-8 w-8 text-gray-400" />
            <span className="text-sm font-medium text-gray-700">مدير النظام</span>
          </div>
          <button
            onClick={handleLogout}
            className="flex items-center space-x-2 space-x-reverse rounded-md bg-red-600 px-3 py-2 text-sm font-medium text-white hover:bg-red-700 transition-colors duration-150"
          >
            <ArrowRightOnRectangleIcon className="h-4 w-4" />
            <span>تسجيل الخروج</span>
          </button>
        </div>
      </div>
    </header>
  );
}