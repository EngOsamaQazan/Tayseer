// General helper utilities

// Deep clone object
export const deepClone = <T>(obj: T): T => {
  if (obj === null || typeof obj !== 'object') return obj;
  if (obj instanceof Date) return new Date(obj.getTime()) as any;
  if (obj instanceof Array) return obj.map(item => deepClone(item)) as any;
  if (obj instanceof Set) return new Set(Array.from(obj).map(item => deepClone(item))) as any;
  if (obj instanceof Map) return new Map(Array.from(obj).map(([k, v]) => [k, deepClone(v)])) as any;
  
  const clonedObj = Object.create(Object.getPrototypeOf(obj));
  for (const key in obj) {
    if (obj.hasOwnProperty(key)) {
      clonedObj[key] = deepClone(obj[key]);
    }
  }
  return clonedObj;
};

// Deep merge objects
export const deepMerge = <T extends Record<string, any>>(...objects: Partial<T>[]): T => {
  const isObject = (obj: any): obj is Record<string, any> => {
    return obj && typeof obj === 'object' && !Array.isArray(obj);
  };
  
  return objects.reduce((result, current) => {
    Object.keys(current).forEach(key => {
      const resultValue = result[key];
      const currentValue = current[key];
      
      if (isObject(resultValue) && isObject(currentValue)) {
        result[key] = deepMerge(resultValue, currentValue);
      } else {
        result[key] = currentValue;
      }
    });
    return result;
  }, {} as T);
};

// Group array by key
export const groupBy = <T, K extends keyof any>(
  array: T[],
  getKey: (item: T) => K
): Record<K, T[]> => {
  return array.reduce((groups, item) => {
    const key = getKey(item);
    if (!groups[key]) {
      groups[key] = [];
    }
    groups[key].push(item);
    return groups;
  }, {} as Record<K, T[]>);
};

// Unique array values
export const unique = <T>(array: T[], key?: keyof T): T[] => {
  if (key) {
    const seen = new Set();
    return array.filter(item => {
      const value = item[key];
      if (seen.has(value)) return false;
      seen.add(value);
      return true;
    });
  }
  return [...new Set(array)];
};

// Sort array by multiple fields
export const sortBy = <T>(
  array: T[],
  fields: Array<{
    key: keyof T;
    order?: 'asc' | 'desc';
  }>
): T[] => {
  return [...array].sort((a, b) => {
    for (const field of fields) {
      const { key, order = 'asc' } = field;
      const aVal = a[key];
      const bVal = b[key];
      
      if (aVal < bVal) return order === 'asc' ? -1 : 1;
      if (aVal > bVal) return order === 'asc' ? 1 : -1;
    }
    return 0;
  });
};

// Chunk array into smaller arrays
export const chunk = <T>(array: T[], size: number): T[][] => {
  const chunks: T[][] = [];
  for (let i = 0; i < array.length; i += size) {
    chunks.push(array.slice(i, i + size));
  }
  return chunks;
};

// Flatten nested array
export const flatten = <T>(array: any[], depth: number = 1): T[] => {
  if (depth <= 0) return array as T[];
  return array.reduce((flat, item) => {
    if (Array.isArray(item)) {
      return flat.concat(flatten(item, depth - 1));
    }
    return flat.concat(item);
  }, []);
};

// Pick specific properties from object
export const pick = <T, K extends keyof T>(
  obj: T,
  keys: K[]
): Pick<T, K> => {
  const result = {} as Pick<T, K>;
  keys.forEach(key => {
    if (key in obj) {
      result[key] = obj[key];
    }
  });
  return result;
};

// Omit specific properties from object
export const omit = <T, K extends keyof T>(
  obj: T,
  keys: K[]
): Omit<T, K> => {
  const result = { ...obj };
  keys.forEach(key => {
    delete result[key];
  });
  return result;
};

// Check if object is empty
export const isEmpty = (obj: any): boolean => {
  if (obj == null) return true;
  if (typeof obj === 'string' || Array.isArray(obj)) return obj.length === 0;
  if (obj instanceof Map || obj instanceof Set) return obj.size === 0;
  if (typeof obj === 'object') return Object.keys(obj).length === 0;
  return false;
};

// Get nested property value safely
export const get = <T>(
  obj: any,
  path: string,
  defaultValue?: T
): T => {
  const keys = path.split('.');
  let result = obj;
  
  for (const key of keys) {
    if (result == null) return defaultValue as T;
    result = result[key];
  }
  
  return result ?? defaultValue;
};

// Set nested property value
export const set = <T>(
  obj: T,
  path: string,
  value: any
): T => {
  const keys = path.split('.');
  const lastKey = keys.pop()!;
  let current: any = obj;
  
  for (const key of keys) {
    if (!(key in current) || typeof current[key] !== 'object') {
      current[key] = {};
    }
    current = current[key];
  }
  
  current[lastKey] = value;
  return obj;
};

// Generate UUID
export const generateUUID = (): string => {
  if (typeof crypto !== 'undefined' && crypto.randomUUID) {
    return crypto.randomUUID();
  }
  
  // Fallback implementation
  return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
    const r = (Math.random() * 16) | 0;
    const v = c === 'x' ? r : (r & 0x3) | 0x8;
    return v.toString(16);
  });
};

// Sleep/delay function
export const sleep = (ms: number): Promise<void> => {
  return new Promise(resolve => setTimeout(resolve, ms));
};

// Retry function with exponential backoff
export const retry = async <T>(
  fn: () => Promise<T>,
  options: {
    retries?: number;
    delay?: number;
    maxDelay?: number;
    factor?: number;
    onRetry?: (error: Error, attempt: number) => void;
  } = {}
): Promise<T> => {
  const {
    retries = 3,
    delay = 1000,
    maxDelay = 30000,
    factor = 2,
    onRetry,
  } = options;
  
  let lastError: Error;
  
  for (let attempt = 0; attempt <= retries; attempt++) {
    try {
      return await fn();
    } catch (error) {
      lastError = error as Error;
      
      if (attempt < retries) {
        const waitTime = Math.min(delay * Math.pow(factor, attempt), maxDelay);
        onRetry?.(lastError, attempt + 1);
        await sleep(waitTime);
      }
    }
  }
  
  throw lastError!;
};

// Pipe functions
export const pipe = <T>(...fns: Array<(arg: any) => any>) => {
  return (value: T) => fns.reduce((acc, fn) => fn(acc), value);
};

// Compose functions (reverse of pipe)
export const compose = <T>(...fns: Array<(arg: any) => any>) => {
  return (value: T) => fns.reduceRight((acc, fn) => fn(acc), value);
};

// Curry function
export const curry = (fn: Function) => {
  return function curried(...args: any[]): any {
    if (args.length >= fn.length) {
      return fn.apply(null, args);
    }
    return (...nextArgs: any[]) => curried(...args, ...nextArgs);
  };
};

// Create enum from array
export const createEnum = <T extends string>(
  values: T[]
): { [K in T]: K } => {
  return values.reduce((acc, value) => {
    acc[value] = value;
    return acc;
  }, {} as { [K in T]: K });
};

// Type guards
export const isString = (value: unknown): value is string => {
  return typeof value === 'string';
};

export const isNumber = (value: unknown): value is number => {
  return typeof value === 'number' && !isNaN(value);
};

export const isBoolean = (value: unknown): value is boolean => {
  return typeof value === 'boolean';
};

export const isArray = <T = any>(value: unknown): value is T[] => {
  return Array.isArray(value);
};

export const isObject = (value: unknown): value is Record<string, any> => {
  return value !== null && typeof value === 'object' && !Array.isArray(value);
};

export const isFunction = (value: unknown): value is Function => {
  return typeof value === 'function';
};

export const isDate = (value: unknown): value is Date => {
  return value instanceof Date && !isNaN(value.getTime());
};

export const isPromise = <T = any>(value: unknown): value is Promise<T> => {
  return value instanceof Promise || (
    isObject(value) &&
    isFunction((value as any).then) &&
    isFunction((value as any).catch)
  );
};

// Class name builder
export const cn = (...classes: Array<string | undefined | null | false>): string => {
  return classes.filter(Boolean).join(' ');
};

// Create query string from object
export const createQueryString = (params: Record<string, any>): string => {
  const searchParams = new URLSearchParams();
  
  Object.entries(params).forEach(([key, value]) => {
    if (value !== null && value !== undefined && value !== '') {
      if (Array.isArray(value)) {
        value.forEach(v => searchParams.append(key, String(v)));
      } else {
        searchParams.append(key, String(value));
      }
    }
  });
  
  return searchParams.toString();
};

// Parse query string to object
export const parseQueryString = (queryString: string): Record<string, string | string[]> => {
  const params = new URLSearchParams(queryString);
  const result: Record<string, string | string[]> = {};
  
  params.forEach((value, key) => {
    const existing = result[key];
    if (existing) {
      if (Array.isArray(existing)) {
        existing.push(value);
      } else {
        result[key] = [existing, value];
      }
    } else {
      result[key] = value;
    }
  });
  
  return result;
};

// Download file
export const downloadFile = (url: string, filename: string): void => {
  const link = document.createElement('a');
  link.href = url;
  link.download = filename;
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
};

// Copy to clipboard
export const copyToClipboard = async (text: string): Promise<boolean> => {
  try {
    if (navigator.clipboard && window.isSecureContext) {
      await navigator.clipboard.writeText(text);
      return true;
    } else {
      // Fallback for older browsers
      const textArea = document.createElement('textarea');
      textArea.value = text;
      textArea.style.position = 'fixed';
      textArea.style.left = '-999999px';
      document.body.appendChild(textArea);
      textArea.focus();
      textArea.select();
      
      try {
        document.execCommand('copy');
        return true;
      } finally {
        document.body.removeChild(textArea);
      }
    }
  } catch (error) {
    console.error('Failed to copy to clipboard:', error);
    return false;
  }
};

// Format bytes to human readable
export const formatBytes = (bytes: number, decimals: number = 2): string => {
  if (bytes === 0) return '0 Bytes';
  
  const k = 1024;
  const dm = decimals < 0 ? 0 : decimals;
  const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB'];
  
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  
  return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
};

// Calculate percentage
export const calculatePercentage = (
  value: number,
  total: number,
  decimals: number = 2
): number => {
  if (total === 0) return 0;
  return Number(((value / total) * 100).toFixed(decimals));
};

// Generate random color
export const generateRandomColor = (): string => {
  return '#' + Math.floor(Math.random() * 16777215).toString(16).padStart(6, '0');
};

// Check if running in browser
export const isBrowser = (): boolean => {
  return typeof window !== 'undefined' && typeof document !== 'undefined';
};

// Check if running on mobile
export const isMobile = (): boolean => {
  if (!isBrowser()) return false;
  
  return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(
    navigator.userAgent
  );
};

// Get browser info
export const getBrowserInfo = (): {
  name: string;
  version: string;
  os: string;
} => {
  if (!isBrowser()) {
    return { name: 'unknown', version: 'unknown', os: 'unknown' };
  }
  
  const userAgent = navigator.userAgent;
  let name = 'Unknown';
  let version = 'Unknown';
  
  // Detect browser
  if (userAgent.includes('Firefox')) {
    name = 'Firefox';
    version = userAgent.match(/Firefox\/(\S+)/)?.[1] || 'Unknown';
  } else if (userAgent.includes('Chrome')) {
    name = 'Chrome';
    version = userAgent.match(/Chrome\/(\S+)/)?.[1] || 'Unknown';
  } else if (userAgent.includes('Safari') && !userAgent.includes('Chrome')) {
    name = 'Safari';
    version = userAgent.match(/Version\/(\S+)/)?.[1] || 'Unknown';
  } else if (userAgent.includes('Edge')) {
    name = 'Edge';
    version = userAgent.match(/Edge\/(\S+)/)?.[1] || 'Unknown';
  }
  
  // Detect OS
  let os = 'Unknown';
  if (userAgent.includes('Windows')) os = 'Windows';
  else if (userAgent.includes('Mac')) os = 'macOS';
  else if (userAgent.includes('Linux')) os = 'Linux';
  else if (userAgent.includes('Android')) os = 'Android';
  else if (userAgent.includes('iOS')) os = 'iOS';
  
  return { name, version, os };
};

// Arabic number converter
export const toArabicNumbers = (num: string | number): string => {
  const arabicNumbers = '٠١٢٣٤٥٦٧٨٩';
  return String(num).replace(/[0-9]/g, (digit) => arabicNumbers[parseInt(digit)]);
};

export const toEnglishNumbers = (num: string): string => {
  const englishNumbers = '0123456789';
  const arabicNumbers = '٠١٢٣٤٥٦٧٨٩';
  
  return num.replace(/[٠-٩]/g, (digit) => {
    const index = arabicNumbers.indexOf(digit);
    return englishNumbers[index];
  });
};

// Scroll utilities
export const scrollToTop = (smooth: boolean = true): void => {
  if (!isBrowser()) return;
  
  window.scrollTo({
    top: 0,
    behavior: smooth ? 'smooth' : 'auto',
  });
};

export const scrollToElement = (
  elementId: string,
  options: ScrollIntoViewOptions = { behavior: 'smooth', block: 'center' }
): void => {
  if (!isBrowser()) return;
  
  const element = document.getElementById(elementId);
  element?.scrollIntoView(options);
};

// Event emitter
export class EventEmitter<T extends Record<string, any>> {
  private events: Map<keyof T, Set<(data: any) => void>> = new Map();
  
  on<K extends keyof T>(event: K, handler: (data: T[K]) => void): void {
    if (!this.events.has(event)) {
      this.events.set(event, new Set());
    }
    this.events.get(event)!.add(handler);
  }
  
  off<K extends keyof T>(event: K, handler: (data: T[K]) => void): void {
    this.events.get(event)?.delete(handler);
  }
  
  emit<K extends keyof T>(event: K, data: T[K]): void {
    this.events.get(event)?.forEach(handler => handler(data));
  }
  
  once<K extends keyof T>(event: K, handler: (data: T[K]) => void): void {
    const onceHandler = (data: T[K]) => {
      handler(data);
      this.off(event, onceHandler);
    };
    this.on(event, onceHandler);
  }
  
  clear(): void {
    this.events.clear();
  }
}

// Queue implementation
export class Queue<T> {
  private items: T[] = [];
  
  enqueue(item: T): void {
    this.items.push(item);
  }
  
  dequeue(): T | undefined {
    return this.items.shift();
  }
  
  peek(): T | undefined {
    return this.items[0];
  }
  
  isEmpty(): boolean {
    return this.items.length === 0;
  }
  
  size(): number {
    return this.items.length;
  }
  
  clear(): void {
    this.items = [];
  }
}

// Stack implementation
export class Stack<T> {
  private items: T[] = [];
  
  push(item: T): void {
    this.items.push(item);
  }
  
  pop(): T | undefined {
    return this.items.pop();
  }
  
  peek(): T | undefined {
    return this.items[this.items.length - 1];
  }
  
  isEmpty(): boolean {
    return this.items.length === 0;
  }
  
  size(): number {
    return this.items.length;
  }
  
  clear(): void {
    this.items = [];
  }
}

// Simple state machine
export class StateMachine<State extends string, Event extends string> {
  private currentState: State;
  private transitions: Map<string, State> = new Map();
  private listeners: Set<(state: State) => void> = new Set();
  
  constructor(initialState: State) {
    this.currentState = initialState;
  }
  
  addTransition(from: State, event: Event, to: State): void {
    const key = `${from}-${event}`;
    this.transitions.set(key, to);
  }
  
  transition(event: Event): boolean {
    const key = `${this.currentState}-${event}`;
    const nextState = this.transitions.get(key);
    
    if (nextState) {
      this.currentState = nextState;
      this.notifyListeners();
      return true;
    }
    
    return false;
  }
  
  getState(): State {
    return this.currentState;
  }
  
  onStateChange(listener: (state: State) => void): () => void {
    this.listeners.add(listener);
    return () => this.listeners.delete(listener);
  }
  
  private notifyListeners(): void {
    this.listeners.forEach(listener => listener(this.currentState));
  }
}

// Export all utilities
export default {
  deepClone,
  deepMerge,
  groupBy,
  unique,
  sortBy,
  chunk,
  flatten,
  pick,
  omit,
  isEmpty,
  get,
  set,
  generateUUID,
  sleep,
  retry,
  pipe,
  compose,
  curry,
  createEnum,
  isString,
  isNumber,
  isBoolean,
  isArray,
  isObject,
  isFunction,
  isDate,
  isPromise,
  cn,
  createQueryString,
  parseQueryString,
  downloadFile,
  copyToClipboard,
  formatBytes,
  calculatePercentage,
  generateRandomColor,
  isBrowser,
  isMobile,
  getBrowserInfo,
  toArabicNumbers,
  toEnglishNumbers,
  scrollToTop,
  scrollToElement,
  EventEmitter,
  Queue,
  Stack,
  StateMachine,
};