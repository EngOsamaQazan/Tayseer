import React from 'react';
import { Routes, Route, Navigate } from 'react-router-dom';
import Layout from './components/Layout';
import Login from './pages/Login';
import CustomerManagement from './pages/CustomerManagement';
import InventoryManagement from './pages/InventoryManagement';
import InstallmentContracts from './pages/InstallmentContracts';
import AccountingSystem from './pages/AccountingSystem';
import EmployeeManagement from './pages/EmployeeManagement';
import TaskManagement from './pages/TaskManagement';
import LegalDepartment from './pages/LegalDepartment';
import CustomerService from './pages/CustomerService';
import ReportsBI from './pages/ReportsBI';
import InvestorSystem from './pages/InvestorSystem';

// Protected Route Component
const ProtectedRoute = ({ children }: { children: React.ReactNode }) => {
  const isAuthenticated = localStorage.getItem('isAuthenticated') === 'true';
  return isAuthenticated ? <>{children}</> : <Navigate to="/login" />;
};

function App() {
  return (
    <Routes>
      <Route path="/login" element={<Login />} />
      <Route
        path="/"
        element={
          <ProtectedRoute>
            <Layout />
          </ProtectedRoute>
        }
      >
        <Route index element={<Navigate to="/customers" />} />
        <Route path="customers" element={<CustomerManagement />} />
        <Route path="inventory" element={<InventoryManagement />} />
        <Route path="installments" element={<InstallmentContracts />} />
        <Route path="accounting" element={<AccountingSystem />} />
        <Route path="employees" element={<EmployeeManagement />} />
        <Route path="tasks" element={<TaskManagement />} />
        <Route path="legal" element={<LegalDepartment />} />
        <Route path="customer-service" element={<CustomerService />} />
        <Route path="reports" element={<ReportsBI />} />
        <Route path="investors" element={<InvestorSystem />} />
      </Route>
    </Routes>
  );
}

export default App;