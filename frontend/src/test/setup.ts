// Test setup file
import '@testing-library/jest-dom';
import { cleanup } from '@testing-library/react';
import { afterEach, beforeAll, afterAll, vi } from 'vitest';
import { server } from './api-mocks';

// Setup MSW
beforeAll(() => {
  server.listen({ onUnhandledRequest: 'error' });
});

// Cleanup after each test
afterEach(() => {
  server.resetHandlers();
  cleanup();
});

afterAll(() => {
  server.close();
});

// Mock window.matchMedia
Object.defineProperty(window, 'matchMedia', {
  writable: true,
  value: vi.fn().mockImplementation(query => ({
    matches: false,
    media: query,
    onchange: null,
    addListener: vi.fn(), // deprecated
    removeListener: vi.fn(), // deprecated
    addEventListener: vi.fn(),
    removeEventListener: vi.fn(),
    dispatchEvent: vi.fn(),
  })),
});

// Mock IntersectionObserver
global.IntersectionObserver = class IntersectionObserver {
  constructor() {}
  disconnect() {}
  observe() {}
  unobserve() {}
} as any;

// Mock ResizeObserver
global.ResizeObserver = class ResizeObserver {
  constructor() {}
  disconnect() {}
  observe() {}
  unobserve() {}
} as any;

// Mock localStorage
const localStorageMock = {
  getItem: vi.fn(),
  setItem: vi.fn(),
  removeItem: vi.fn(),
  clear: vi.fn(),
  length: 0,
  key: vi.fn(),
};

global.localStorage = localStorageMock as any;

// Mock sessionStorage
const sessionStorageMock = {
  getItem: vi.fn(),
  setItem: vi.fn(),
  removeItem: vi.fn(),
  clear: vi.fn(),
  length: 0,
  key: vi.fn(),
};

global.sessionStorage = sessionStorageMock as any;

// Mock navigator
Object.defineProperty(window, 'navigator', {
  value: {
    userAgent: 'node.js',
    language: 'ar-SA',
    languages: ['ar-SA', 'ar', 'en'],
    onLine: true,
    clipboard: {
      writeText: vi.fn(),
      readText: vi.fn(),
    },
  },
  writable: true,
});

// Mock fetch
global.fetch = vi.fn();

// Mock crypto
Object.defineProperty(window, 'crypto', {
  value: {
    randomUUID: () => 'test-uuid-' + Math.random(),
    subtle: {
      digest: vi.fn(),
      encrypt: vi.fn(),
      decrypt: vi.fn(),
    },
  },
});

// Mock scrollTo
window.scrollTo = vi.fn();

// Mock HTMLElement methods
HTMLElement.prototype.scrollIntoView = vi.fn();

// Environment variables for tests
process.env.NODE_ENV = 'test';

// Mock console methods in test to reduce noise
const originalError = console.error;
const originalWarn = console.warn;

beforeAll(() => {
  console.error = vi.fn();
  console.warn = vi.fn();
});

afterAll(() => {
  console.error = originalError;
  console.warn = originalWarn;
});

// Global test utilities
export const waitForAsync = (ms: number = 0) => 
  new Promise(resolve => setTimeout(resolve, ms));

export const mockConsole = () => {
  const spy = {
    error: vi.spyOn(console, 'error').mockImplementation(() => {}),
    warn: vi.spyOn(console, 'warn').mockImplementation(() => {}),
    log: vi.spyOn(console, 'log').mockImplementation(() => {}),
  };
  
  return {
    restore: () => {
      spy.error.mockRestore();
      spy.warn.mockRestore();
      spy.log.mockRestore();
    },
    ...spy,
  };
};

// Mock date/time
export const mockDate = (date: Date | string) => {
  const mockedDate = new Date(date);
  vi.useFakeTimers();
  vi.setSystemTime(mockedDate);
  
  return {
    restore: () => vi.useRealTimers(),
  };
};

// URL mock
global.URL.createObjectURL = vi.fn(() => 'mock-url');
global.URL.revokeObjectURL = vi.fn();

// Blob mock
global.Blob = class Blob {
  constructor(public parts: any[], public options: any = {}) {}
  
  async text() {
    return this.parts.join('');
  }
  
  async arrayBuffer() {
    return new ArrayBuffer(0);
  }
  
  slice() {
    return new Blob([]);
  }
  
  stream() {
    return new ReadableStream();
  }
  
  get size() {
    return this.parts.join('').length;
  }
  
  get type() {
    return this.options.type || '';
  }
} as any;

// File mock
global.File = class File extends Blob {
  constructor(
    parts: any[],
    public name: string,
    options: any = {}
  ) {
    super(parts, options);
  }
  
  get lastModified() {
    return Date.now();
  }
} as any;