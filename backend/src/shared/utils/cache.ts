import logger from './logger';

interface CacheOptions {
  ttl?: number; // Time to live in seconds
  tags?: string[];
}

class CacheUtil {
  private cache: Map<string, { value: any; expires: number; tags?: string[] }> = new Map();

  async get<T>(key: string): Promise<T | null> {
    try {
      const item = this.cache.get(key);
      
      if (!item) {
        return null;
      }

      if (Date.now() > item.expires) {
        this.cache.delete(key);
        return null;
      }

      logger.debug('Cache hit', { key });
      return item.value as T;
    } catch (error) {
      logger.error('Cache get error', { key, error });
      return null;
    }
  }

  async set(key: string, value: any, options: CacheOptions = {}): Promise<void> {
    try {
      const ttl = options.ttl || 3600; // Default 1 hour
      const expires = Date.now() + (ttl * 1000);

      this.cache.set(key, {
        value,
        expires,
        tags: options.tags,
      });

      logger.debug('Cache set', { key, ttl });
    } catch (error) {
      logger.error('Cache set error', { key, error });
    }
  }

  async del(key: string): Promise<void> {
    try {
      this.cache.delete(key);
      logger.debug('Cache delete', { key });
    } catch (error) {
      logger.error('Cache delete error', { key, error });
    }
  }

  async clear(): Promise<void> {
    try {
      this.cache.clear();
      logger.debug('Cache cleared');
    } catch (error) {
      logger.error('Cache clear error', error);
    }
  }

  async invalidateByTag(tag: string): Promise<void> {
    try {
      const keysToDelete: string[] = [];
      
      for (const [key, item] of this.cache.entries()) {
        if (item.tags && item.tags.includes(tag)) {
          keysToDelete.push(key);
        }
      }

      keysToDelete.forEach(key => this.cache.delete(key));
      logger.debug('Cache invalidated by tag', { tag, count: keysToDelete.length });
    } catch (error) {
      logger.error('Cache invalidate by tag error', { tag, error });
    }
  }

  async has(key: string): Promise<boolean> {
    try {
      const item = this.cache.get(key);
      
      if (!item) {
        return false;
      }

      if (Date.now() > item.expires) {
        this.cache.delete(key);
        return false;
      }

      return true;
    } catch (error) {
      logger.error('Cache has error', { key, error });
      return false;
    }
  }

  async remember<T>(key: string, callback: () => Promise<T>, options: CacheOptions = {}): Promise<T> {
    try {
      const cached = await this.get<T>(key);
      
      if (cached !== null) {
        return cached;
      }

      const value = await callback();
      await this.set(key, value, options);
      return value;
    } catch (error) {
      logger.error('Cache remember error', { key, error });
      throw error;
    }
  }
}

export const cacheUtil = new CacheUtil();
export default cacheUtil;