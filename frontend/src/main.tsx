import React from 'react';
import ReactDOM from 'react-dom/client';
import './index.css';

const App = () => {
  return (
    <div className="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex items-center justify-center p-4">
      <div className="max-w-4xl w-full bg-white rounded-2xl shadow-2xl p-8 md:p-12">
        <div className="text-center mb-8">
          <h1 className="text-4xl md:text-5xl font-bold text-gray-800 mb-4">
            مرحباً بك في منصة تيسير
          </h1>
          <p className="text-xl text-gray-600 mb-8">
            نظام إدارة الأعمال المتكامل
          </p>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
          <div className="bg-blue-50 rounded-lg p-6">
            <h2 className="text-2xl font-semibold text-blue-800 mb-3">إدارة العملاء</h2>
            <p className="text-gray-700">تتبع وإدارة جميع معلومات عملائك في مكان واحد</p>
          </div>
          
          <div className="bg-green-50 rounded-lg p-6">
            <h2 className="text-2xl font-semibold text-green-800 mb-3">إدارة العقود</h2>
            <p className="text-gray-700">إنشاء وتتبع العقود بكل سهولة ويسر</p>
          </div>
          
          <div className="bg-purple-50 rounded-lg p-6">
            <h2 className="text-2xl font-semibold text-purple-800 mb-3">المحاسبة المالية</h2>
            <p className="text-gray-700">نظام محاسبي متكامل لإدارة الحسابات والفواتير</p>
          </div>
          
          <div className="bg-orange-50 rounded-lg p-6">
            <h2 className="text-2xl font-semibold text-orange-800 mb-3">إدارة المخزون</h2>
            <p className="text-gray-700">تحكم كامل في المخزون والمنتجات</p>
          </div>
        </div>

        <div className="text-center">
          <p className="text-lg text-gray-600 mb-4">
            النظام قيد التطوير حالياً
          </p>
          <div className="flex flex-col sm:flex-row gap-4 justify-center">
            <a 
              href="http://localhost:3001/api/health" 
              target="_blank"
              rel="noopener noreferrer"
              className="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors"
            >
              فحص حالة API
            </a>
            <a 
              href="http://localhost:3001/api-docs" 
              target="_blank"
              rel="noopener noreferrer"
              className="bg-gray-600 text-white px-6 py-3 rounded-lg hover:bg-gray-700 transition-colors"
            >
              وثائق API
            </a>
          </div>
        </div>
      </div>
    </div>
  );
};

ReactDOM.createRoot(document.getElementById('root')!).render(
  <React.StrictMode>
    <App />
  </React.StrictMode>
);