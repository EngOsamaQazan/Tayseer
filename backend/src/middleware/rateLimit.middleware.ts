import { Request, Response, NextFunction } from 'express';
import logger from '../shared/utils/logger';

interface RateLimitStore {
  [key: string]: {
    count: number;
    resetTime: number;
  };
}

interface RateLimitOptions {
  windowMs?: number; // Time window in milliseconds
  max?: number; // Maximum number of requests per window
  message?: string; // Error message
  standardHeaders?: boolean; // Return rate limit info in headers
  legacyHeaders?: boolean; // Return rate limit info in X-RateLimit-* headers
}

class RateLimiter {
  private store: RateLimitStore = {};
  private options: Required<RateLimitOptions>;

  constructor(options: RateLimitOptions = {}) {
    this.options = {
      windowMs: options.windowMs || 15 * 60 * 1000, // 15 minutes
      max: options.max || 100, // 100 requests per window
      message: options.message || 'معدل الطلبات مرتفع جداً، يرجى المحاولة لاحقاً',
      standardHeaders: options.standardHeaders ?? true,
      legacyHeaders: options.legacyHeaders ?? true,
    };

    // Clean up expired entries every minute
    setInterval(() => this.cleanup(), 60 * 1000);
  }

  middleware() {
    return (req: Request, res: Response, next: NextFunction) => {
      const key = this.generateKey(req);
      const now = Date.now();
      const windowStart = now - this.options.windowMs;

      // Get or create entry for this key
      let entry = this.store[key];
      if (!entry || entry.resetTime <= now) {
        entry = {
          count: 0,
          resetTime: now + this.options.windowMs,
        };
        this.store[key] = entry;
      }

      // Increment request count
      entry.count++;

      // Set headers if enabled
      if (this.options.standardHeaders) {
        res.setHeader('RateLimit-Limit', this.options.max);
        res.setHeader('RateLimit-Remaining', Math.max(0, this.options.max - entry.count));
        res.setHeader('RateLimit-Reset', new Date(entry.resetTime));
      }

      if (this.options.legacyHeaders) {
        res.setHeader('X-RateLimit-Limit', this.options.max);
        res.setHeader('X-RateLimit-Remaining', Math.max(0, this.options.max - entry.count));
        res.setHeader('X-RateLimit-Reset', Math.ceil(entry.resetTime / 1000));
      }

      // Check if limit exceeded
      if (entry.count > this.options.max) {
        logger.warn('Rate limit exceeded', {
          ip: req.ip,
          userAgent: req.get('User-Agent'),
          path: req.path,
          count: entry.count,
          limit: this.options.max,
        });

        res.status(429).json({
          success: false,
          message: this.options.message,
          error: 'TOO_MANY_REQUESTS',
          retryAfter: Math.ceil((entry.resetTime - now) / 1000),
        });
        return;
      }

      next();
    };
  }

  private generateKey(req: Request): string {
    // Use IP address as the key, but you could also use user ID if authenticated
    return req.ip || 'unknown';
  }

  private cleanup(): void {
    const now = Date.now();
    const keysToDelete: string[] = [];

    for (const [key, entry] of Object.entries(this.store)) {
      if (entry.resetTime <= now) {
        keysToDelete.push(key);
      }
    }

    keysToDelete.forEach(key => delete this.store[key]);

    if (keysToDelete.length > 0) {
      logger.debug('Rate limit store cleanup', { removed: keysToDelete.length });
    }
  }

  reset(key?: string): void {
    if (key) {
      delete this.store[key];
    } else {
      this.store = {};
    }
  }
}

// Create default rate limiter instances
export const rateLimiter = new RateLimiter().middleware();

// Stricter rate limiter for authentication endpoints
export const authRateLimiter = new RateLimiter({
  windowMs: 15 * 60 * 1000, // 15 minutes
  max: 5, // 5 attempts per window
  message: 'محاولات دخول كثيرة جداً، يرجى المحاولة بعد 15 دقيقة',
}).middleware();

// More lenient rate limiter for general API use
export const apiRateLimiter = new RateLimiter({
  windowMs: 15 * 60 * 1000, // 15 minutes
  max: 1000, // 1000 requests per window
  message: 'معدل الطلبات مرتفع جداً، يرجى المحاولة لاحقاً',
}).middleware();

export default rateLimiter;