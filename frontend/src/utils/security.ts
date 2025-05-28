// Security utilities for data protection and validation

import { AES, enc, SHA256, HmacSHA256 } from 'crypto-js';

// XSS Prevention
export const sanitizeHtml = (input: string): string => {
  const map: Record<string, string> = {
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#x27;',
    '/': '&#x2F;',
  };
  
  return input.replace(/[&<>"'\/]/g, (match) => map[match] || match);
};

// SQL Injection Prevention (for query parameters)
export const sanitizeSqlParam = (input: string): string => {
  // Remove or escape potentially dangerous characters
  return input
    .replace(/['"\\]/g, '\\$&') // Escape quotes and backslashes
    .replace(/[\x00-\x1F\x7F]/g, ''); // Remove control characters
};

// Input validation for preventing injection attacks
export const validateInput = (input: string, type: 'alphanumeric' | 'numeric' | 'email' | 'url'): boolean => {
  const patterns = {
    alphanumeric: /^[a-zA-Z0-9\s\u0600-\u06FF]+$/,
    numeric: /^\d+$/,
    email: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
    url: /^https?:\/\/(www\.)?[-a-zA-Z0-9@:%._\+~#=]{1,256}\.[a-zA-Z0-9()]{1,6}\b([-a-zA-Z0-9()@:%_\+.~#?&//=]*)$/,
  };
  
  return patterns[type]?.test(input) || false;
};

// Password strength checker
export const checkPasswordStrength = (password: string): {
  score: number;
  strength: 'weak' | 'medium' | 'strong' | 'very-strong';
  suggestions: string[];
} => {
  let score = 0;
  const suggestions: string[] = [];
  
  // Length check
  if (password.length >= 8) score += 1;
  else suggestions.push('استخدم 8 أحرف على الأقل');
  
  if (password.length >= 12) score += 1;
  
  // Character variety checks
  if (/[a-z]/.test(password)) score += 1;
  else suggestions.push('أضف أحرف صغيرة');
  
  if (/[A-Z]/.test(password)) score += 1;
  else suggestions.push('أضف أحرف كبيرة');
  
  if (/\d/.test(password)) score += 1;
  else suggestions.push('أضف أرقام');
  
  if (/[^a-zA-Z0-9]/.test(password)) score += 1;
  else suggestions.push('أضف رموز خاصة');
  
  // Common patterns check
  const commonPatterns = [
    /12345/,
    /password/i,
    /qwerty/i,
    /abc123/i,
    /admin/i,
  ];
  
  if (!commonPatterns.some(pattern => pattern.test(password))) {
    score += 1;
  } else {
    suggestions.push('تجنب الأنماط الشائعة');
  }
  
  // Determine strength
  let strength: 'weak' | 'medium' | 'strong' | 'very-strong';
  if (score <= 2) strength = 'weak';
  else if (score <= 4) strength = 'medium';
  else if (score <= 6) strength = 'strong';
  else strength = 'very-strong';
  
  return { score, strength, suggestions };
};

// Encrypt sensitive data
export const encryptData = (data: string, secretKey: string): string => {
  try {
    return AES.encrypt(data, secretKey).toString();
  } catch (error) {
    console.error('Encryption error:', error);
    throw new Error('فشل تشفير البيانات');
  }
};

// Decrypt sensitive data
export const decryptData = (encryptedData: string, secretKey: string): string => {
  try {
    const bytes = AES.decrypt(encryptedData, secretKey);
    return bytes.toString(enc.Utf8);
  } catch (error) {
    console.error('Decryption error:', error);
    throw new Error('فشل فك تشفير البيانات');
  }
};

// Generate hash
export const generateHash = (data: string): string => {
  return SHA256(data).toString();
};

// Generate HMAC
export const generateHMAC = (data: string, secret: string): string => {
  return HmacSHA256(data, secret).toString();
};

// Mask sensitive data
export const maskSensitiveData = (data: string, type: 'email' | 'phone' | 'credit-card' | 'iban'): string => {
  switch (type) {
    case 'email': {
      const [username, domain] = data.split('@');
      if (!domain) return data;
      const maskedUsername = username.slice(0, 2) + '*'.repeat(username.length - 2);
      return `${maskedUsername}@${domain}`;
    }
    
    case 'phone': {
      if (data.length < 6) return data;
      return data.slice(0, 3) + '*'.repeat(data.length - 6) + data.slice(-3);
    }
    
    case 'credit-card': {
      const cleaned = data.replace(/\s/g, '');
      if (cleaned.length < 8) return data;
      return '*'.repeat(cleaned.length - 4) + cleaned.slice(-4);
    }
    
    case 'iban': {
      const cleaned = data.replace(/\s/g, '');
      if (cleaned.length < 8) return data;
      return cleaned.slice(0, 4) + '*'.repeat(cleaned.length - 8) + cleaned.slice(-4);
    }
    
    default:
      return data;
  }
};

// Rate limiting helper
export class RateLimiter {
  private attempts: Map<string, { count: number; resetTime: number }> = new Map();
  
  constructor(
    private maxAttempts: number,
    private windowMs: number
  ) {}
  
  check(identifier: string): { allowed: boolean; remainingAttempts: number; resetTime?: number } {
    const now = Date.now();
    const record = this.attempts.get(identifier);
    
    // Clean up expired records
    this.cleanup();
    
    if (!record || now > record.resetTime) {
      // First attempt or window expired
      this.attempts.set(identifier, {
        count: 1,
        resetTime: now + this.windowMs,
      });
      
      return {
        allowed: true,
        remainingAttempts: this.maxAttempts - 1,
      };
    }
    
    if (record.count >= this.maxAttempts) {
      return {
        allowed: false,
        remainingAttempts: 0,
        resetTime: record.resetTime,
      };
    }
    
    // Increment attempt count
    record.count++;
    
    return {
      allowed: true,
      remainingAttempts: this.maxAttempts - record.count,
    };
  }
  
  reset(identifier: string): void {
    this.attempts.delete(identifier);
  }
  
  private cleanup(): void {
    const now = Date.now();
    for (const [key, value] of this.attempts.entries()) {
      if (now > value.resetTime) {
        this.attempts.delete(key);
      }
    }
  }
}

// CSRF Token management
export const generateCSRFToken = (): string => {
  const array = new Uint8Array(32);
  crypto.getRandomValues(array);
  return Array.from(array, byte => byte.toString(16).padStart(2, '0')).join('');
};

// Validate CSRF token
export const validateCSRFToken = (token: string, storedToken: string): boolean => {
  if (!token || !storedToken) return false;
  return token === storedToken;
};

// Content Security Policy helper
export const generateCSPHeader = (options?: {
  defaultSrc?: string[];
  scriptSrc?: string[];
  styleSrc?: string[];
  imgSrc?: string[];
  connectSrc?: string[];
  fontSrc?: string[];
  objectSrc?: string[];
  mediaSrc?: string[];
  frameSrc?: string[];
}): string => {
  const defaultOptions = {
    defaultSrc: ["'self'"],
    scriptSrc: ["'self'", "'unsafe-inline'"],
    styleSrc: ["'self'", "'unsafe-inline'"],
    imgSrc: ["'self'", 'data:', 'https:'],
    connectSrc: ["'self'"],
    fontSrc: ["'self'"],
    objectSrc: ["'none'"],
    mediaSrc: ["'self'"],
    frameSrc: ["'none'"],
  };
  
  const merged = { ...defaultOptions, ...options };
  
  const directives = Object.entries(merged)
    .map(([key, values]) => {
      const directiveName = key.replace(/([A-Z])/g, '-$1').toLowerCase();
      return `${directiveName} ${values.join(' ')}`;
    })
    .join('; ');
  
  return directives;
};

// Session timeout manager
export class SessionManager {
  private timeoutId: NodeJS.Timeout | null = null;
  
  constructor(
    private timeoutMs: number,
    private onTimeout: () => void,
    private warningMs?: number,
    private onWarning?: () => void
  ) {}
  
  start(): void {
    this.stop();
    
    if (this.warningMs && this.onWarning) {
      setTimeout(() => {
        this.onWarning?.();
      }, this.timeoutMs - this.warningMs);
    }
    
    this.timeoutId = setTimeout(() => {
      this.onTimeout();
    }, this.timeoutMs);
  }
  
  reset(): void {
    this.start();
  }
  
  stop(): void {
    if (this.timeoutId) {
      clearTimeout(this.timeoutId);
      this.timeoutId = null;
    }
  }
}

// Secure random string generator
export const generateSecureRandomString = (length: number): string => {
  const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
  const array = new Uint8Array(length);
  crypto.getRandomValues(array);
  
  return Array.from(array, byte => chars[byte % chars.length]).join('');
};

// Input sanitization for file names
export const sanitizeFileName = (fileName: string): string => {
  // Remove path traversal attempts
  let sanitized = fileName.replace(/\.\.[\\\/]/g, '');
  
  // Remove special characters except dots, dashes, and underscores
  sanitized = sanitized.replace(/[^a-zA-Z0-9\.\-_\u0600-\u06FF]/g, '_');
  
  // Ensure it doesn't start with a dot (hidden file)
  if (sanitized.startsWith('.')) {
    sanitized = '_' + sanitized.slice(1);
  }
  
  return sanitized;
};

// Validate file type
export const validateFileType = (file: File, allowedTypes: string[]): boolean => {
  const fileType = file.type.toLowerCase();
  const fileName = file.name.toLowerCase();
  const extension = fileName.split('.').pop() || '';
  
  // Check MIME type
  const typeValid = allowedTypes.some(type => {
    if (type.includes('*')) {
      const [mainType] = type.split('/');
      return fileType.startsWith(mainType + '/');
    }
    return fileType === type;
  });
  
  // Additional extension check for security
  const dangerousExtensions = ['exe', 'bat', 'cmd', 'sh', 'ps1', 'vbs', 'js', 'jar'];
  const extensionSafe = !dangerousExtensions.includes(extension);
  
  return typeValid && extensionSafe;
};

// Safe JSON parse
export const safeJsonParse = <T = any>(json: string, fallback?: T): T | undefined => {
  try {
    return JSON.parse(json);
  } catch (error) {
    console.error('JSON parse error:', error);
    return fallback;
  }
};

// URL parameter sanitization
export const sanitizeUrlParams = (params: Record<string, any>): URLSearchParams => {
  const sanitized = new URLSearchParams();
  
  Object.entries(params).forEach(([key, value]) => {
    if (value !== null && value !== undefined) {
      // Sanitize both key and value
      const sanitizedKey = encodeURIComponent(key);
      const sanitizedValue = encodeURIComponent(String(value));
      sanitized.append(sanitizedKey, sanitizedValue);
    }
  });
  
  return sanitized;
};