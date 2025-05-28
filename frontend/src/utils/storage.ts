// Storage utilities for local storage, session storage, and cookies

// Storage types
export enum StorageType {
  LOCAL = 'localStorage',
  SESSION = 'sessionStorage',
}

// Storage key prefix to avoid conflicts
const STORAGE_PREFIX = 'tayseer_';

// Generic storage interface
interface StorageOptions {
  expires?: number; // Expiration time in milliseconds
  encrypt?: boolean; // Whether to encrypt the data
  prefix?: string; // Custom prefix for the key
}

// Storage item structure
interface StorageItem<T> {
  value: T;
  expires?: number;
  created: number;
}

// Base storage class
class BaseStorage {
  constructor(private storage: Storage) {}
  
  // Set item with options
  setItem<T>(key: string, value: T, options?: StorageOptions): void {
    try {
      const prefixedKey = (options?.prefix || STORAGE_PREFIX) + key;
      const item: StorageItem<T> = {
        value,
        created: Date.now(),
      };
      
      if (options?.expires) {
        item.expires = Date.now() + options.expires;
      }
      
      const serialized = JSON.stringify(item);
      this.storage.setItem(prefixedKey, serialized);
    } catch (error) {
      console.error('Storage setItem error:', error);
      // Handle quota exceeded error
      if (error instanceof DOMException && error.code === 22) {
        this.clearExpired();
        throw new Error('مساحة التخزين ممتلئة');
      }
      throw error;
    }
  }
  
  // Get item with expiration check
  getItem<T>(key: string, options?: { prefix?: string }): T | null {
    try {
      const prefixedKey = (options?.prefix || STORAGE_PREFIX) + key;
      const serialized = this.storage.getItem(prefixedKey);
      
      if (!serialized) return null;
      
      const item: StorageItem<T> = JSON.parse(serialized);
      
      // Check expiration
      if (item.expires && Date.now() > item.expires) {
        this.removeItem(key, options);
        return null;
      }
      
      return item.value;
    } catch (error) {
      console.error('Storage getItem error:', error);
      return null;
    }
  }
  
  // Remove item
  removeItem(key: string, options?: { prefix?: string }): void {
    const prefixedKey = (options?.prefix || STORAGE_PREFIX) + key;
    this.storage.removeItem(prefixedKey);
  }
  
  // Clear all items with prefix
  clear(prefix?: string): void {
    const targetPrefix = prefix || STORAGE_PREFIX;
    const keys: string[] = [];
    
    for (let i = 0; i < this.storage.length; i++) {
      const key = this.storage.key(i);
      if (key && key.startsWith(targetPrefix)) {
        keys.push(key);
      }
    }
    
    keys.forEach(key => this.storage.removeItem(key));
  }
  
  // Clear expired items
  clearExpired(): void {
    const keys: string[] = [];
    
    for (let i = 0; i < this.storage.length; i++) {
      const key = this.storage.key(i);
      if (key && key.startsWith(STORAGE_PREFIX)) {
        keys.push(key);
      }
    }
    
    keys.forEach(key => {
      try {
        const serialized = this.storage.getItem(key);
        if (serialized) {
          const item = JSON.parse(serialized);
          if (item.expires && Date.now() > item.expires) {
            this.storage.removeItem(key);
          }
        }
      } catch (error) {
        // Remove corrupted items
        this.storage.removeItem(key);
      }
    });
  }
  
  // Get all items with prefix
  getAllItems<T>(prefix?: string): Record<string, T> {
    const targetPrefix = prefix || STORAGE_PREFIX;
    const items: Record<string, T> = {};
    
    for (let i = 0; i < this.storage.length; i++) {
      const key = this.storage.key(i);
      if (key && key.startsWith(targetPrefix)) {
        const cleanKey = key.replace(targetPrefix, '');
        const value = this.getItem<T>(cleanKey, { prefix: targetPrefix });
        if (value !== null) {
          items[cleanKey] = value;
        }
      }
    }
    
    return items;
  }
  
  // Get storage size
  getSize(): number {
    let size = 0;
    for (let i = 0; i < this.storage.length; i++) {
      const key = this.storage.key(i);
      if (key) {
        const value = this.storage.getItem(key);
        if (value) {
          size += key.length + value.length;
        }
      }
    }
    return size;
  }
}

// Local storage instance
export const localStorage = typeof window !== 'undefined' 
  ? new BaseStorage(window.localStorage)
  : null;

// Session storage instance
export const sessionStorage = typeof window !== 'undefined'
  ? new BaseStorage(window.sessionStorage)
  : null;

// Cookie utilities
export const cookies = {
  // Set cookie
  set(name: string, value: string, options?: {
    expires?: number | Date; // Days or Date object
    path?: string;
    domain?: string;
    secure?: boolean;
    sameSite?: 'strict' | 'lax' | 'none';
  }): void {
    if (typeof document === 'undefined') return;
    
    let cookieString = `${encodeURIComponent(name)}=${encodeURIComponent(value)}`;
    
    if (options?.expires) {
      let expiresDate: Date;
      if (typeof options.expires === 'number') {
        expiresDate = new Date();
        expiresDate.setTime(expiresDate.getTime() + options.expires * 24 * 60 * 60 * 1000);
      } else {
        expiresDate = options.expires;
      }
      cookieString += `; expires=${expiresDate.toUTCString()}`;
    }
    
    if (options?.path) {
      cookieString += `; path=${options.path}`;
    } else {
      cookieString += '; path=/';
    }
    
    if (options?.domain) {
      cookieString += `; domain=${options.domain}`;
    }
    
    if (options?.secure) {
      cookieString += '; secure';
    }
    
    if (options?.sameSite) {
      cookieString += `; samesite=${options.sameSite}`;
    }
    
    document.cookie = cookieString;
  },
  
  // Get cookie
  get(name: string): string | null {
    if (typeof document === 'undefined') return null;
    
    const nameEQ = encodeURIComponent(name) + '=';
    const cookies = document.cookie.split(';');
    
    for (let cookie of cookies) {
      cookie = cookie.trim();
      if (cookie.indexOf(nameEQ) === 0) {
        return decodeURIComponent(cookie.substring(nameEQ.length));
      }
    }
    
    return null;
  },
  
  // Remove cookie
  remove(name: string, options?: {
    path?: string;
    domain?: string;
  }): void {
    this.set(name, '', {
      expires: -1,
      path: options?.path,
      domain: options?.domain,
    });
  },
  
  // Get all cookies
  getAll(): Record<string, string> {
    if (typeof document === 'undefined') return {};
    
    const cookiesObj: Record<string, string> = {};
    const cookies = document.cookie.split(';');
    
    for (let cookie of cookies) {
      cookie = cookie.trim();
      const [name, value] = cookie.split('=');
      if (name && value) {
        cookiesObj[decodeURIComponent(name)] = decodeURIComponent(value);
      }
    }
    
    return cookiesObj;
  },
};

// IndexedDB wrapper for large data storage
export class IndexedDBStorage {
  private db: IDBDatabase | null = null;
  
  constructor(
    private dbName: string = 'TayseerDB',
    private storeName: string = 'dataStore',
    private version: number = 1
  ) {}
  
  // Initialize database
  async init(): Promise<void> {
    if (typeof window === 'undefined' || !window.indexedDB) {
      throw new Error('IndexedDB غير مدعوم في هذا المتصفح');
    }
    
    return new Promise((resolve, reject) => {
      const request = indexedDB.open(this.dbName, this.version);
      
      request.onerror = () => reject(request.error);
      request.onsuccess = () => {
        this.db = request.result;
        resolve();
      };
      
      request.onupgradeneeded = (event) => {
        const db = (event.target as IDBOpenDBRequest).result;
        if (!db.objectStoreNames.contains(this.storeName)) {
          db.createObjectStore(this.storeName, { keyPath: 'id' });
        }
      };
    });
  }
  
  // Set item
  async setItem<T>(key: string, value: T): Promise<void> {
    if (!this.db) await this.init();
    
    return new Promise((resolve, reject) => {
      const transaction = this.db!.transaction([this.storeName], 'readwrite');
      const store = transaction.objectStore(this.storeName);
      const request = store.put({ id: key, value, updated: Date.now() });
      
      request.onsuccess = () => resolve();
      request.onerror = () => reject(request.error);
    });
  }
  
  // Get item
  async getItem<T>(key: string): Promise<T | null> {
    if (!this.db) await this.init();
    
    return new Promise((resolve, reject) => {
      const transaction = this.db!.transaction([this.storeName], 'readonly');
      const store = transaction.objectStore(this.storeName);
      const request = store.get(key);
      
      request.onsuccess = () => {
        const result = request.result;
        resolve(result ? result.value : null);
      };
      request.onerror = () => reject(request.error);
    });
  }
  
  // Remove item
  async removeItem(key: string): Promise<void> {
    if (!this.db) await this.init();
    
    return new Promise((resolve, reject) => {
      const transaction = this.db!.transaction([this.storeName], 'readwrite');
      const store = transaction.objectStore(this.storeName);
      const request = store.delete(key);
      
      request.onsuccess = () => resolve();
      request.onerror = () => reject(request.error);
    });
  }
  
  // Clear all items
  async clear(): Promise<void> {
    if (!this.db) await this.init();
    
    return new Promise((resolve, reject) => {
      const transaction = this.db!.transaction([this.storeName], 'readwrite');
      const store = transaction.objectStore(this.storeName);
      const request = store.clear();
      
      request.onsuccess = () => resolve();
      request.onerror = () => reject(request.error);
    });
  }
  
  // Close database connection
  close(): void {
    if (this.db) {
      this.db.close();
      this.db = null;
    }
  }
}

// Cache manager for API responses
export class CacheManager {
  constructor(
    private storage: BaseStorage | null = localStorage,
    private defaultTTL: number = 5 * 60 * 1000 // 5 minutes
  ) {}
  
  // Generate cache key
  private getCacheKey(endpoint: string, params?: any): string {
    const paramString = params ? JSON.stringify(params) : '';
    return `cache_${endpoint}_${paramString}`;
  }
  
  // Set cached data
  set<T>(endpoint: string, data: T, params?: any, ttl?: number): void {
    if (!this.storage) return;
    
    const key = this.getCacheKey(endpoint, params);
    this.storage.setItem(key, data, {
      expires: ttl || this.defaultTTL,
    });
  }
  
  // Get cached data
  get<T>(endpoint: string, params?: any): T | null {
    if (!this.storage) return null;
    
    const key = this.getCacheKey(endpoint, params);
    return this.storage.getItem<T>(key);
  }
  
  // Invalidate cache
  invalidate(endpoint: string, params?: any): void {
    if (!this.storage) return;
    
    const key = this.getCacheKey(endpoint, params);
    this.storage.removeItem(key);
  }
  
  // Clear all cache
  clearAll(): void {
    if (!this.storage) return;
    this.storage.clear('cache_');
  }
}

// Export instances
export const cacheManager = new CacheManager();
export const indexedDB = new IndexedDBStorage();