import { createClient, RedisClientType } from 'redis';
import { logger } from './logger';

let redisClient: RedisClientType;

// إنشاء والاتصال بعميل Redis
export async function connectRedis(): Promise<void> {
  try {
    redisClient = createClient({
      socket: {
        host: process.env.REDIS_HOST || 'localhost',
        port: parseInt(process.env.REDIS_PORT || '6379'),
      },
      password: process.env.REDIS_PASSWORD || undefined,
    });

    // معالجة الأخطاء
    redisClient.on('error', (err) => {
      logger.error('خطأ في Redis:', err);
    });

    redisClient.on('connect', () => {
      logger.info('🔗 جاري الاتصال بـ Redis...');
    });

    redisClient.on('ready', () => {
      logger.info('✅ تم الاتصال بـ Redis بنجاح');
    });

    // الاتصال
    await redisClient.connect();
  } catch (error) {
    logger.error('❌ فشل الاتصال بـ Redis:', error);
    throw error;
  }
}

// دالة قطع الاتصال
export async function disconnectRedis(): Promise<void> {
  if (redisClient) {
    await redisClient.quit();
    logger.info('تم قطع الاتصال بـ Redis');
  }
}

// دوال مساعدة للتخزين المؤقت
export class CacheService {
  // تخزين قيمة
  static async set(key: string, value: any, ttl?: number): Promise<void> {
    try {
      const stringValue = JSON.stringify(value);
      if (ttl) {
        await redisClient.setEx(key, ttl, stringValue);
      } else {
        await redisClient.set(key, stringValue);
      }
    } catch (error) {
      logger.error('خطأ في تخزين القيمة في Redis:', error);
      throw error;
    }
  }

  // الحصول على قيمة
  static async get<T>(key: string): Promise<T | null> {
    try {
      const value = await redisClient.get(key);
      return value ? JSON.parse(value) : null;
    } catch (error) {
      logger.error('خطأ في الحصول على القيمة من Redis:', error);
      throw error;
    }
  }

  // حذف قيمة
  static async delete(key: string): Promise<void> {
    try {
      await redisClient.del(key);
    } catch (error) {
      logger.error('خطأ في حذف القيمة من Redis:', error);
      throw error;
    }
  }

  // حذف جميع المفاتيح بنمط معين
  static async deletePattern(pattern: string): Promise<void> {
    try {
      const keys = await redisClient.keys(pattern);
      if (keys.length > 0) {
        await redisClient.del(keys);
      }
    } catch (error) {
      logger.error('خطأ في حذف المفاتيح من Redis:', error);
      throw error;
    }
  }

  // التحقق من وجود مفتاح
  static async exists(key: string): Promise<boolean> {
    try {
      const result = await redisClient.exists(key);
      return result === 1;
    } catch (error) {
      logger.error('خطأ في التحقق من وجود المفتاح في Redis:', error);
      throw error;
    }
  }

  // تعيين مدة انتهاء الصلاحية
  static async expire(key: string, seconds: number): Promise<void> {
    try {
      await redisClient.expire(key, seconds);
    } catch (error) {
      logger.error('خطأ في تعيين مدة انتهاء الصلاحية في Redis:', error);
      throw error;
    }
  }
}

// دوال للجلسات
export class SessionService {
  private static readonly SESSION_PREFIX = 'session:';
  private static readonly SESSION_TTL = 86400; // 24 ساعة

  static async create(sessionId: string, data: any): Promise<void> {
    await CacheService.set(
      `${this.SESSION_PREFIX}${sessionId}`,
      data,
      this.SESSION_TTL
    );
  }

  static async get(sessionId: string): Promise<any> {
    return CacheService.get(`${this.SESSION_PREFIX}${sessionId}`);
  }

  static async update(sessionId: string, data: any): Promise<void> {
    await this.create(sessionId, data);
  }

  static async destroy(sessionId: string): Promise<void> {
    await CacheService.delete(`${this.SESSION_PREFIX}${sessionId}`);
  }

  static async refresh(sessionId: string): Promise<void> {
    await CacheService.expire(
      `${this.SESSION_PREFIX}${sessionId}`,
      this.SESSION_TTL
    );
  }
}

// معالجة إيقاف التطبيق
process.on('beforeExit', async () => {
  await disconnectRedis();
});

export { redisClient };