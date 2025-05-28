// API mocking utilities for tests
import { rest } from 'msw';
import { setupServer } from 'msw/node';

// Base API URL
const API_BASE_URL = process.env.VITE_API_URL || 'http://localhost:8080/api';

// Mock data factories
export const mockFactories = {
  user: (overrides = {}) => ({
    id: 1,
    username: 'testuser',
    email: 'test@example.com',
    role: 'admin',
    department: 'تقنية المعلومات',
    isActive: true,
    createdAt: new Date().toISOString(),
    ...overrides,
  }),

  employee: (overrides = {}) => ({
    id: 1,
    employeeNumber: 'EMP001',
    name: 'محمد أحمد',
    department: 'الموارد البشرية',
    position: 'مدير',
    email: 'mohammed@example.com',
    phone: '0501234567',
    hireDate: '2023-01-15',
    status: 'active',
    ...overrides,
  }),

  document: (overrides = {}) => ({
    id: 1,
    title: 'وثيقة مهمة',
    type: 'عقد',
    status: 'مكتمل',
    department: 'القانونية',
    createdBy: 'أحمد محمد',
    createdAt: new Date().toISOString(),
    fileUrl: '/documents/doc1.pdf',
    ...overrides,
  }),

  project: (overrides = {}) => ({
    id: 1,
    name: 'مشروع التطوير',
    description: 'تطوير نظام جديد',
    status: 'قيد التنفيذ',
    startDate: '2024-01-01',
    endDate: '2024-12-31',
    budget: 100000,
    progress: 45,
    manager: 'سارة أحمد',
    ...overrides,
  }),

  inquiry: (overrides = {}) => ({
    id: 1,
    title: 'استفسار عن الإجازات',
    category: 'إجازات',
    status: 'مفتوح',
    priority: 'عالي',
    submittedBy: 'موظف',
    submittedAt: new Date().toISOString(),
    description: 'أريد معرفة رصيد إجازاتي',
    ...overrides,
  }),

  contract: (overrides = {}) => ({
    id: 1,
    contractNumber: 'CNT-2024-001',
    title: 'عقد توريد',
    type: 'توريد',
    status: 'ساري',
    startDate: '2024-01-01',
    endDate: '2024-12-31',
    value: 50000,
    party: 'شركة التوريدات',
    ...overrides,
  }),
};

// API response helpers
export const successResponse = (data: any) => ({
  success: true,
  data,
  message: 'تمت العملية بنجاح',
});

export const errorResponse = (message: string, statusCode = 400) => ({
  success: false,
  error: message,
  statusCode,
});

export const paginatedResponse = (items: any[], page = 1, limit = 10) => ({
  success: true,
  data: {
    items,
    pagination: {
      page,
      limit,
      total: items.length,
      pages: Math.ceil(items.length / limit),
    },
  },
});

// Default handlers
export const defaultHandlers = [
  // Auth endpoints
  rest.post(`${API_BASE_URL}/auth/login`, (req, res, ctx) => {
    return res(
      ctx.json(successResponse({
        user: mockFactories.user(),
        token: 'mock-jwt-token',
      }))
    );
  }),

  rest.post(`${API_BASE_URL}/auth/logout`, (req, res, ctx) => {
    return res(ctx.json(successResponse({ message: 'تم تسجيل الخروج بنجاح' })));
  }),

  rest.get(`${API_BASE_URL}/auth/me`, (req, res, ctx) => {
    return res(ctx.json(successResponse(mockFactories.user())));
  }),

  // Users endpoints
  rest.get(`${API_BASE_URL}/users`, (req, res, ctx) => {
    const users = Array.from({ length: 10 }, (_, i) =>
      mockFactories.user({ id: i + 1, username: `user${i + 1}` })
    );
    return res(ctx.json(paginatedResponse(users)));
  }),

  rest.get(`${API_BASE_URL}/users/:id`, (req, res, ctx) => {
    const { id } = req.params;
    return res(ctx.json(successResponse(mockFactories.user({ id: Number(id) }))));
  }),

  rest.post(`${API_BASE_URL}/users`, (req, res, ctx) => {
    return res(ctx.json(successResponse(mockFactories.user({ id: Date.now() }))));
  }),

  rest.put(`${API_BASE_URL}/users/:id`, (req, res, ctx) => {
    const { id } = req.params;
    return res(ctx.json(successResponse(mockFactories.user({ id: Number(id) }))));
  }),

  rest.delete(`${API_BASE_URL}/users/:id`, (req, res, ctx) => {
    return res(ctx.json(successResponse({ message: 'تم حذف المستخدم بنجاح' })));
  }),

  // Employees endpoints
  rest.get(`${API_BASE_URL}/employees`, (req, res, ctx) => {
    const employees = Array.from({ length: 10 }, (_, i) =>
      mockFactories.employee({ id: i + 1, employeeNumber: `EMP00${i + 1}` })
    );
    return res(ctx.json(paginatedResponse(employees)));
  }),

  rest.get(`${API_BASE_URL}/employees/:id`, (req, res, ctx) => {
    const { id } = req.params;
    return res(ctx.json(successResponse(mockFactories.employee({ id: Number(id) }))));
  }),

  // Documents endpoints
  rest.get(`${API_BASE_URL}/documents`, (req, res, ctx) => {
    const documents = Array.from({ length: 10 }, (_, i) =>
      mockFactories.document({ id: i + 1, title: `وثيقة ${i + 1}` })
    );
    return res(ctx.json(paginatedResponse(documents)));
  }),

  rest.post(`${API_BASE_URL}/documents/upload`, (req, res, ctx) => {
    return res(ctx.json(successResponse({
      id: Date.now(),
      filename: 'uploaded-file.pdf',
      url: '/documents/uploaded-file.pdf',
    })));
  }),

  // Projects endpoints
  rest.get(`${API_BASE_URL}/projects`, (req, res, ctx) => {
    const projects = Array.from({ length: 5 }, (_, i) =>
      mockFactories.project({ id: i + 1, name: `مشروع ${i + 1}` })
    );
    return res(ctx.json(paginatedResponse(projects)));
  }),

  // Inquiries endpoints
  rest.get(`${API_BASE_URL}/inquiries`, (req, res, ctx) => {
    const inquiries = Array.from({ length: 8 }, (_, i) =>
      mockFactories.inquiry({ id: i + 1, title: `استفسار ${i + 1}` })
    );
    return res(ctx.json(paginatedResponse(inquiries)));
  }),

  // Contracts endpoints
  rest.get(`${API_BASE_URL}/contracts`, (req, res, ctx) => {
    const contracts = Array.from({ length: 6 }, (_, i) =>
      mockFactories.contract({ 
        id: i + 1, 
        contractNumber: `CNT-2024-00${i + 1}` 
      })
    );
    return res(ctx.json(paginatedResponse(contracts)));
  }),

  // Generic error handler for unhandled endpoints
  rest.get('*', (req, res, ctx) => {
    return res(
      ctx.status(404),
      ctx.json(errorResponse('المورد غير موجود', 404))
    );
  }),
];

// Create and export the mock server
export const server = setupServer(...defaultHandlers);

// Helper functions for tests
export const mockApiSuccess = (endpoint: string, data: any, method = 'get') => {
  const handler = rest[method as keyof typeof rest](`${API_BASE_URL}${endpoint}`, (req, res, ctx) => {
    return res(ctx.json(successResponse(data)));
  });
  server.use(handler);
};

export const mockApiError = (endpoint: string, error: string, statusCode = 400, method = 'get') => {
  const handler = rest[method as keyof typeof rest](`${API_BASE_URL}${endpoint}`, (req, res, ctx) => {
    return res(
      ctx.status(statusCode),
      ctx.json(errorResponse(error, statusCode))
    );
  });
  server.use(handler);
};

export const mockApiDelay = (endpoint: string, delay: number, data: any, method = 'get') => {
  const handler = rest[method as keyof typeof rest](`${API_BASE_URL}${endpoint}`, (req, res, ctx) => {
    return res(
      ctx.delay(delay),
      ctx.json(successResponse(data))
    );
  });
  server.use(handler);
};

export const mockApiNetworkError = (endpoint: string, method = 'get') => {
  const handler = rest[method as keyof typeof rest](`${API_BASE_URL}${endpoint}`, (req, res, ctx) => {
    return res.networkError('Failed to connect');
  });
  server.use(handler);
};

// Reset handlers between tests
export const resetApiMocks = () => {
  server.resetHandlers();
};

// Add custom handler
export const addApiMock = (handler: any) => {
  server.use(handler);
};