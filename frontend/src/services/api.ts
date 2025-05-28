import axios, { AxiosInstance, AxiosRequestConfig, AxiosResponse } from 'axios';

// Create axios instance with base configuration
const apiClient: AxiosInstance = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost:3001/api',
  timeout: 10000,
  headers: {
    'Content-Type': 'application/json',
  },
});

// Request interceptor for authentication
apiClient.interceptors.request.use(
  (config) => {
    // Get token from localStorage or sessionStorage
    const token = localStorage.getItem('authToken');
    
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Response interceptor for error handling
apiClient.interceptors.response.use(
  (response: AxiosResponse) => {
    return response;
  },
  (error) => {
    if (error.response) {
      // Handle specific error statuses
      switch (error.response.status) {
        case 401:
          // Unauthorized - redirect to login
          localStorage.removeItem('authToken');
          window.location.href = '/login';
          break;
        case 403:
          // Forbidden
          console.error('Access denied');
          break;
        case 404:
          // Not found
          console.error('Resource not found');
          break;
        case 500:
          // Server error
          console.error('Server error');
          break;
      }
    } else if (error.request) {
      // Network error
      console.error('Network error:', error.message);
    }
    
    return Promise.reject(error);
  }
);

// Generic API methods
export const api = {
  get: <T>(url: string, config?: AxiosRequestConfig): Promise<AxiosResponse<T>> => 
    apiClient.get<T>(url, config),
    
  post: <T>(url: string, data?: any, config?: AxiosRequestConfig): Promise<AxiosResponse<T>> => 
    apiClient.post<T>(url, data, config),
    
  put: <T>(url: string, data?: any, config?: AxiosRequestConfig): Promise<AxiosResponse<T>> => 
    apiClient.put<T>(url, data, config),
    
  patch: <T>(url: string, data?: any, config?: AxiosRequestConfig): Promise<AxiosResponse<T>> => 
    apiClient.patch<T>(url, data, config),
    
  delete: <T>(url: string, config?: AxiosRequestConfig): Promise<AxiosResponse<T>> => 
    apiClient.delete<T>(url, config),
};

// API service modules
export const customerService = {
  getAll: () => api.get('/customers'),
  getById: (id: number) => api.get(`/customers/${id}`),
  create: (data: any) => api.post('/customers', data),
  update: (id: number, data: any) => api.put(`/customers/${id}`, data),
  delete: (id: number) => api.delete(`/customers/${id}`),
};

export const productService = {
  getAll: () => api.get('/products'),
  getById: (id: number) => api.get(`/products/${id}`),
  create: (data: any) => api.post('/products', data),
  update: (id: number, data: any) => api.put(`/products/${id}`, data),
  delete: (id: number) => api.delete(`/products/${id}`),
};

export const contractService = {
  getAll: () => api.get('/contracts'),
  getById: (id: number) => api.get(`/contracts/${id}`),
  create: (data: any) => api.post('/contracts', data),
  update: (id: number, data: any) => api.put(`/contracts/${id}`, data),
  delete: (id: number) => api.delete(`/contracts/${id}`),
};

export const transactionService = {
  getAll: () => api.get('/transactions'),
  getById: (id: number) => api.get(`/transactions/${id}`),
  create: (data: any) => api.post('/transactions', data),
  update: (id: number, data: any) => api.put(`/transactions/${id}`, data),
  delete: (id: number) => api.delete(`/transactions/${id}`),
};

export const employeeService = {
  getAll: () => api.get('/employees'),
  getById: (id: number) => api.get(`/employees/${id}`),
  create: (data: any) => api.post('/employees', data),
  update: (id: number, data: any) => api.put(`/employees/${id}`, data),
  delete: (id: number) => api.delete(`/employees/${id}`),
};

export const taskService = {
  getAll: () => api.get('/tasks'),
  getById: (id: number) => api.get(`/tasks/${id}`),
  create: (data: any) => api.post('/tasks', data),
  update: (id: number, data: any) => api.put(`/tasks/${id}`, data),
  delete: (id: number) => api.delete(`/tasks/${id}`),
};

export const legalCaseService = {
  getAll: () => api.get('/legal-cases'),
  getById: (id: number) => api.get(`/legal-cases/${id}`),
  create: (data: any) => api.post('/legal-cases', data),
  update: (id: number, data: any) => api.put(`/legal-cases/${id}`, data),
  delete: (id: number) => api.delete(`/legal-cases/${id}`),
};

export const ticketService = {
  getAll: () => api.get('/tickets'),
  getById: (id: number) => api.get(`/tickets/${id}`),
  create: (data: any) => api.post('/tickets', data),
  update: (id: number, data: any) => api.put(`/tickets/${id}`, data),
  delete: (id: number) => api.delete(`/tickets/${id}`),
};

export const investorService = {
  getAll: () => api.get('/investors'),
  getById: (id: number) => api.get(`/investors/${id}`),
  create: (data: any) => api.post('/investors', data),
  update: (id: number, data: any) => api.put(`/investors/${id}`, data),
  delete: (id: number) => api.delete(`/investors/${id}`),
};

export const investmentService = {
  getAll: () => api.get('/investments'),
  getById: (id: number) => api.get(`/investments/${id}`),
  create: (data: any) => api.post('/investments', data),
  update: (id: number, data: any) => api.put(`/investments/${id}`, data),
  delete: (id: number) => api.delete(`/investments/${id}`),
};

export default apiClient;