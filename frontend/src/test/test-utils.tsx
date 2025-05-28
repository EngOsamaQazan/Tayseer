// Custom test utilities
import React, { ReactElement } from 'react';
import { render, RenderOptions } from '@testing-library/react';
import { Provider } from 'react-redux';
import { BrowserRouter } from 'react-router-dom';
import { configureStore } from '@reduxjs/toolkit';
import { store as realStore } from '../store';
import authReducer from '../store/slices/authSlice';
import customerReducer from '../store/slices/customerSlice';
import productReducer from '../store/slices/productSlice';
import orderReducer from '../store/slices/orderSlice';
import employeeReducer from '../store/slices/employeeSlice';
import reportReducer from '../store/slices/reportSlice';
import inventoryReducer from '../store/slices/inventorySlice';
import hrReducer from '../store/slices/hrSlice';
import legalReducer from '../store/slices/legalSlice';
import backupReducer from '../store/slices/backupSlice';
import notificationReducer from '../store/slices/notificationSlice';

// Custom render options interface
interface CustomRenderOptions extends Omit<RenderOptions, 'wrapper'> {
  preloadedState?: any;
  store?: any;
  route?: string;
}

// Create test store
export function createTestStore(preloadedState?: any) {
  return configureStore({
    reducer: {
      auth: authReducer,
      customers: customerReducer,
      products: productReducer,
      orders: orderReducer,
      employees: employeeReducer,
      reports: reportReducer,
      inventory: inventoryReducer,
      hr: hrReducer,
      legal: legalReducer,
      backup: backupReducer,
      notifications: notificationReducer,
    },
    preloadedState,
  });
}

// All providers wrapper
const AllProviders = ({ 
  children, 
  store = realStore,
  route = '/',
}: { 
  children: React.ReactNode;
  store?: any;
  route?: string;
}) => {
  window.history.pushState({}, 'Test page', route);
  
  return (
    <Provider store={store}>
      <BrowserRouter>
        {children}
      </BrowserRouter>
    </Provider>
  );
};

// Custom render function
export function customRender(
  ui: ReactElement,
  {
    preloadedState,
    store = createTestStore(preloadedState),
    route = '/',
    ...renderOptions
  }: CustomRenderOptions = {}
) {
  const Wrapper = ({ children }: { children: React.ReactNode }) => (
    <AllProviders store={store} route={route}>
      {children}
    </AllProviders>
  );

  return {
    store,
    ...render(ui, { wrapper: Wrapper, ...renderOptions }),
  };
}

// Test data generators
export const generateTestUser = (overrides = {}) => ({
  id: 1,
  username: 'testuser',
  email: 'test@example.com',
  role: 'admin',
  token: 'test-token',
  ...overrides,
});

export const generateTestCustomer = (overrides = {}) => ({
  id: 1,
  name: 'عميل تجريبي',
  email: 'customer@example.com',
  phone: '0501234567',
  address: 'الرياض',
  createdAt: new Date().toISOString(),
  ...overrides,
});

export const generateTestProduct = (overrides = {}) => ({
  id: 1,
  name: 'منتج تجريبي',
  description: 'وصف المنتج التجريبي',
  price: 100,
  stock: 50,
  category: 'إلكترونيات',
  createdAt: new Date().toISOString(),
  ...overrides,
});

export const generateTestOrder = (overrides = {}) => ({
  id: 1,
  customerId: 1,
  items: [
    { productId: 1, quantity: 2, price: 100 },
  ],
  total: 200,
  status: 'pending',
  createdAt: new Date().toISOString(),
  ...overrides,
});

export const generateTestEmployee = (overrides = {}) => ({
  id: 1,
  name: 'موظف تجريبي',
  email: 'employee@example.com',
  phone: '0501234567',
  department: 'المبيعات',
  position: 'مدير',
  salary: 10000,
  joinDate: new Date().toISOString(),
  ...overrides,
});

// Mock API responses
export const mockApiResponse = <T>(data: T, delay = 0): Promise<T> => {
  return new Promise((resolve) => {
    setTimeout(() => resolve(data), delay);
  });
};

export const mockApiError = (message: string, delay = 0): Promise<never> => {
  return new Promise((_, reject) => {
    setTimeout(() => reject(new Error(message)), delay);
  });
};

// Wait utilities
export const waitFor = (ms: number): Promise<void> => {
  return new Promise(resolve => setTimeout(resolve, ms));
};

// Form helpers
export const fillForm = async (
  container: HTMLElement,
  values: Record<string, string>
) => {
  const { getByLabelText } = { getByLabelText: (text: string) => container.querySelector(`[aria-label="${text}"]`) as HTMLElement };
  
  for (const [label, value] of Object.entries(values)) {
    const input = getByLabelText(label) as HTMLInputElement;
    input.value = value;
    input.dispatchEvent(new Event('change', { bubbles: true }));
  }
};

// Redux state helpers
export const createAuthState = (overrides = {}) => ({
  user: null,
  isAuthenticated: false,
  loading: false,
  error: null,
  ...overrides,
});

export const createCustomerState = (overrides = {}) => ({
  customers: [],
  loading: false,
  error: null,
  totalCount: 0,
  currentPage: 1,
  ...overrides,
});

export const createProductState = (overrides = {}) => ({
  products: [],
  loading: false,
  error: null,
  totalCount: 0,
  currentPage: 1,
  categories: [],
  ...overrides,
});

// Assertion helpers
export const expectToBeInDocument = (element: HTMLElement | null) => {
  expect(element).toBeInTheDocument();
};

export const expectNotToBeInDocument = (element: HTMLElement | null) => {
  expect(element).not.toBeInTheDocument();
};

export const expectToHaveClass = (element: HTMLElement, className: string) => {
  expect(element).toHaveClass(className);
};

export const expectToHaveText = (element: HTMLElement, text: string) => {
  expect(element).toHaveTextContent(text);
};

// Export everything
export * from '@testing-library/react';
export { customRender as render };