import dotenv from 'dotenv';
import { z } from 'zod';
import { logger } from './logger';

// تحميل متغيرات البيئة
dotenv.config();

// ==================== مخطط التحقق من البيئة ====================

const envSchema = z.object({
  // بيئة التطبيق
  NODE_ENV: z.enum(['development', 'test', 'staging', 'production']).default('development'),
  PORT: z.string().transform(Number).default('3000'),
  API_VERSION: z.string().default('v1'),
  
  // قاعدة البيانات الرئيسية (PostgreSQL)
  DATABASE_URL: z.string().url(),
  DATABASE_POOL_MIN: z.string().transform(Number).default('2'),
  DATABASE_POOL_MAX: z.string().transform(Number).default('10'),
  DATABASE_POOL_IDLE: z.string().transform(Number).default('10000'),
  
  // MongoDB
  MONGODB_URI: z.string().url(),
  MONGODB_DB_NAME: z.string().default('tayseer_logs'),
  
  // Redis
  REDIS_HOST: z.string().default('localhost'),
  REDIS_PORT: z.string().transform(Number).default('6379'),
  REDIS_PASSWORD: z.string().optional(),
  REDIS_DB: z.string().transform(Number).default('0'),
  REDIS_KEY_PREFIX: z.string().default('tayseer:'),
  
  // JWT والمصادقة
  JWT_SECRET: z.string().min(32),
  JWT_REFRESH_SECRET: z.string().min(32),
  JWT_ACCESS_EXPIRY: z.string().default('15m'),
  JWT_REFRESH_EXPIRY: z.string().default('7d'),
  JWT_ISSUER: z.string().default('tayseer-platform'),
  SESSION_SECRET: z.string().min(32),
  SESSION_TIMEOUT: z.string().transform(Number).default('3600000'), // 1 hour
  
  // التشفير
  ENCRYPTION_KEY: z.string().length(32),
  ENCRYPTION_IV: z.string().length(16),
  
  // CORS
  CORS_ORIGIN: z.string().default('*'),
  CORS_CREDENTIALS: z.string().transform(val => val === 'true').default('true'),
  
  // معدل الطلبات
  RATE_LIMIT_WINDOW: z.string().transform(Number).default('900000'), // 15 minutes
  RATE_LIMIT_MAX: z.string().transform(Number).default('100'),
  RATE_LIMIT_SKIP_SUCCESSFUL_REQUESTS: z.string().transform(val => val === 'true').default('false'),
  
  // البريد الإلكتروني
  SMTP_HOST: z.string(),
  SMTP_PORT: z.string().transform(Number).default('587'),
  SMTP_SECURE: z.string().transform(val => val === 'true').default('false'),
  SMTP_USER: z.string().email(),
  SMTP_PASS: z.string(),
  EMAIL_FROM: z.string().email(),
  EMAIL_FROM_NAME: z.string().default('منصة تيسير'),
  
  // الرسائل النصية (SMS)
  SMS_PROVIDER: z.enum(['twilio', 'unifonic', 'local']).default('local'),
  SMS_API_KEY: z.string().optional(),
  SMS_API_SECRET: z.string().optional(),
  SMS_FROM_NUMBER: z.string().optional(),
  SMS_APP_SID: z.string().optional(),
  
  // التخزين
  STORAGE_TYPE: z.enum(['local', 's3', 'azure', 'gcs']).default('local'),
  STORAGE_PATH: z.string().default('./uploads'),
  
  // AWS S3 (اختياري)
  AWS_ACCESS_KEY_ID: z.string().optional(),
  AWS_SECRET_ACCESS_KEY: z.string().optional(),
  AWS_REGION: z.string().default('us-east-1').optional(),
  AWS_S3_BUCKET: z.string().optional(),
  
  // Azure Storage (اختياري)
  AZURE_STORAGE_ACCOUNT: z.string().optional(),
  AZURE_STORAGE_KEY: z.string().optional(),
  AZURE_STORAGE_CONTAINER: z.string().optional(),
  
  // Google Cloud Storage (اختياري)
  GCS_PROJECT_ID: z.string().optional(),
  GCS_KEY_FILE: z.string().optional(),
  GCS_BUCKET: z.string().optional(),
  
  // بوابة الدفع
  PAYMENT_PROVIDER: z.enum(['stripe', 'paypal', 'tap', 'moyasar', 'local']).default('local'),
  PAYMENT_API_KEY: z.string().optional(),
  PAYMENT_API_SECRET: z.string().optional(),
  PAYMENT_WEBHOOK_SECRET: z.string().optional(),
  PAYMENT_CURRENCY: z.string().default('SAR'),
  
  // الإشعارات الفورية
  PUSH_PROVIDER: z.enum(['fcm', 'onesignal', 'pusher', 'local']).default('local'),
  PUSH_API_KEY: z.string().optional(),
  PUSH_APP_ID: z.string().optional(),
  
  // Webhook
  WEBHOOK_TIMEOUT: z.string().transform(Number).default('30000'), // 30 seconds
  WEBHOOK_MAX_RETRIES: z.string().transform(Number).default('3'),
  WEBHOOK_RETRY_DELAY: z.string().transform(Number).default('60000'), // 1 minute
  
  // التسجيل والمراقبة
  LOG_LEVEL: z.enum(['error', 'warn', 'info', 'debug', 'verbose']).default('info'),
  LOG_FORMAT: z.enum(['json', 'simple', 'colored']).default('json'),
  LOG_TO_FILE: z.string().transform(val => val === 'true').default('true'),
  LOG_FILE_PATH: z.string().default('./logs'),
  LOG_MAX_SIZE: z.string().default('20m'),
  LOG_MAX_FILES: z.string().default('14d'),
  
  // Sentry (اختياري)
  SENTRY_DSN: z.string().optional(),
  SENTRY_ENVIRONMENT: z.string().optional(),
  SENTRY_TRACES_SAMPLE_RATE: z.string().transform(Number).default('0.1').optional(),
  
  // الأداء
  ENABLE_REQUEST_LOGGING: z.string().transform(val => val === 'true').default('true'),
  ENABLE_RESPONSE_TIME: z.string().transform(val => val === 'true').default('true'),
  ENABLE_COMPRESSION: z.string().transform(val => val === 'true').default('true'),
  COMPRESSION_LEVEL: z.string().transform(Number).default('6'),
  
  // الأمان
  BCRYPT_ROUNDS: z.string().transform(Number).default('10'),
  PASSWORD_MIN_LENGTH: z.string().transform(Number).default('8'),
  PASSWORD_REQUIRE_UPPERCASE: z.string().transform(val => val === 'true').default('true'),
  PASSWORD_REQUIRE_LOWERCASE: z.string().transform(val => val === 'true').default('true'),
  PASSWORD_REQUIRE_NUMBER: z.string().transform(val => val === 'true').default('true'),
  PASSWORD_REQUIRE_SPECIAL: z.string().transform(val => val === 'true').default('true'),
  MAX_LOGIN_ATTEMPTS: z.string().transform(Number).default('5'),
  LOGIN_LOCKOUT_DURATION: z.string().transform(Number).default('900000'), // 15 minutes
  
  // التطبيق
  APP_NAME: z.string().default('تيسير'),
  APP_URL: z.string().url().default('http://localhost:3000'),
  FRONTEND_URL: z.string().url().default('http://localhost:3001'),
  MOBILE_DEEP_LINK_SCHEME: z.string().default('tayseer'),
  
  // المنطقة الزمنية واللغة
  DEFAULT_TIMEZONE: z.string().default('Asia/Riyadh'),
  DEFAULT_LOCALE: z.string().default('ar-SA'),
  DEFAULT_CURRENCY: z.string().default('SAR'),
  
  // حدود النظام
  MAX_FILE_SIZE: z.string().transform(Number).default('10485760'), // 10MB
  MAX_REQUEST_SIZE: z.string().default('50mb'),
  PAGINATION_DEFAULT_LIMIT: z.string().transform(Number).default('20'),
  PAGINATION_MAX_LIMIT: z.string().transform(Number).default('100'),
  
  // التخزين المؤقت
  CACHE_TTL: z.string().transform(Number).default('3600'), // 1 hour
  CACHE_CHECK_PERIOD: z.string().transform(Number).default('600'), // 10 minutes
  
  // الصيانة
  MAINTENANCE_MODE: z.string().transform(val => val === 'true').default('false'),
  MAINTENANCE_MESSAGE: z.string().default('النظام تحت الصيانة، نعتذر عن الإزعاج'),
  MAINTENANCE_ALLOWED_IPS: z.string().transform(val => val ? val.split(',') : []).default(''),
  
  // ميزات تجريبية
  ENABLE_EXPERIMENTAL_FEATURES: z.string().transform(val => val === 'true').default('false'),
  ENABLE_API_DOCS: z.string().transform(val => val === 'true').default('true'),
  ENABLE_GRAPHQL: z.string().transform(val => val === 'true').default('false'),
  ENABLE_WEBSOCKETS: z.string().transform(val => val === 'true').default('true'),
});

// ==================== نوع البيئة ====================

export type EnvConfig = z.infer<typeof envSchema>;

// ==================== تحميل والتحقق من البيئة ====================

class EnvironmentConfig {
  private config: EnvConfig;
  private validationErrors: z.ZodError | null = null;

  constructor() {
    this.loadAndValidate();
  }

  private loadAndValidate(): void {
    try {
      // التحقق من المتغيرات
      this.config = envSchema.parse(process.env);
      
      // التحقق من الإعدادات الحرجة في الإنتاج
      if (this.config.NODE_ENV === 'production') {
        this.validateProductionConfig();
      }
      
      logger.info('تم تحميل إعدادات البيئة بنجاح', {
        environment: this.config.NODE_ENV,
        port: this.config.PORT,
        database: this.config.DATABASE_URL.split('@')[1] || 'محلي'
      });
    } catch (error) {
      if (error instanceof z.ZodError) {
        this.validationErrors = error;
        this.logValidationErrors(error);
        
        // في بيئة الإنتاج، أوقف التطبيق
        if (process.env.NODE_ENV === 'production') {
          logger.error('فشل التحقق من إعدادات البيئة في الإنتاج', { errors: error.errors });
          process.exit(1);
        }
      }
      throw error;
    }
  }

  private validateProductionConfig(): void {
    const criticalErrors: string[] = [];

    // التحقق من الإعدادات الحرجة
    if (this.config.JWT_SECRET === 'default-secret') {
      criticalErrors.push('يجب تعيين JWT_SECRET قوي في بيئة الإنتاج');
    }

    if (this.config.ENCRYPTION_KEY.length < 32) {
      criticalErrors.push('يجب أن يكون ENCRYPTION_KEY 32 حرفًا على الأقل');
    }

    if (this.config.SESSION_SECRET === 'default-session-secret') {
      criticalErrors.push('يجب تعيين SESSION_SECRET قوي في بيئة الإنتاج');
    }

    if (this.config.CORS_ORIGIN === '*') {
      criticalErrors.push('يجب تحديد CORS_ORIGIN في بيئة الإنتاج');
    }

    if (this.config.ENABLE_API_DOCS) {
      logger.warn('وثائق API مفعلة في بيئة الإنتاج');
    }

    if (criticalErrors.length > 0) {
      throw new Error(`أخطاء حرجة في إعدادات الإنتاج:\n${criticalErrors.join('\n')}`);
    }
  }

  private logValidationErrors(error: z.ZodError): void {
    logger.error('أخطاء في التحقق من متغيرات البيئة:');
    error.errors.forEach((err) => {
      logger.error(`- ${err.path.join('.')}: ${err.message}`);
    });
  }

  public get<K extends keyof EnvConfig>(key: K): EnvConfig[K] {
    return this.config[key];
  }

  public getAll(): EnvConfig {
    return { ...this.config };
  }

  public isProduction(): boolean {
    return this.config.NODE_ENV === 'production';
  }

  public isDevelopment(): boolean {
    return this.config.NODE_ENV === 'development';
  }

  public isTest(): boolean {
    return this.config.NODE_ENV === 'test';
  }

  public getValidationErrors(): z.ZodError | null {
    return this.validationErrors;
  }

  public getDatabaseConfig() {
    return {
      url: this.config.DATABASE_URL,
      pool: {
        min: this.config.DATABASE_POOL_MIN,
        max: this.config.DATABASE_POOL_MAX,
        idle: this.config.DATABASE_POOL_IDLE
      }
    };
  }

  public getRedisConfig() {
    return {
      host: this.config.REDIS_HOST,
      port: this.config.REDIS_PORT,
      password: this.config.REDIS_PASSWORD,
      db: this.config.REDIS_DB,
      keyPrefix: this.config.REDIS_KEY_PREFIX
    };
  }

  public getMongoConfig() {
    return {
      uri: this.config.MONGODB_URI,
      dbName: this.config.MONGODB_DB_NAME
    };
  }

  public getJwtConfig() {
    return {
      secret: this.config.JWT_SECRET,
      refreshSecret: this.config.JWT_REFRESH_SECRET,
      accessExpiry: this.config.JWT_ACCESS_EXPIRY,
      refreshExpiry: this.config.JWT_REFRESH_EXPIRY,
      issuer: this.config.JWT_ISSUER
    };
  }

  public getEmailConfig() {
    return {
      host: this.config.SMTP_HOST,
      port: this.config.SMTP_PORT,
      secure: this.config.SMTP_SECURE,
      auth: {
        user: this.config.SMTP_USER,
        pass: this.config.SMTP_PASS
      },
      from: {
        email: this.config.EMAIL_FROM,
        name: this.config.EMAIL_FROM_NAME
      }
    };
  }

  public getSmsConfig() {
    return {
      provider: this.config.SMS_PROVIDER,
      apiKey: this.config.SMS_API_KEY,
      apiSecret: this.config.SMS_API_SECRET,
      fromNumber: this.config.SMS_FROM_NUMBER,
      appSid: this.config.SMS_APP_SID
    };
  }

  public getStorageConfig() {
    const baseConfig = {
      type: this.config.STORAGE_TYPE,
      path: this.config.STORAGE_PATH
    };

    switch (this.config.STORAGE_TYPE) {
      case 's3':
        return {
          ...baseConfig,
          aws: {
            accessKeyId: this.config.AWS_ACCESS_KEY_ID,
            secretAccessKey: this.config.AWS_SECRET_ACCESS_KEY,
            region: this.config.AWS_REGION,
            bucket: this.config.AWS_S3_BUCKET
          }
        };
      case 'azure':
        return {
          ...baseConfig,
          azure: {
            account: this.config.AZURE_STORAGE_ACCOUNT,
            key: this.config.AZURE_STORAGE_KEY,
            container: this.config.AZURE_STORAGE_CONTAINER
          }
        };
      case 'gcs':
        return {
          ...baseConfig,
          gcs: {
            projectId: this.config.GCS_PROJECT_ID,
            keyFile: this.config.GCS_KEY_FILE,
            bucket: this.config.GCS_BUCKET
          }
        };
      default:
        return baseConfig;
    }
  }

  public getPaymentConfig() {
    return {
      provider: this.config.PAYMENT_PROVIDER,
      apiKey: this.config.PAYMENT_API_KEY,
      apiSecret: this.config.PAYMENT_API_SECRET,
      webhookSecret: this.config.PAYMENT_WEBHOOK_SECRET,
      currency: this.config.PAYMENT_CURRENCY
    };
  }

  public getSecurityConfig() {
    return {
      bcryptRounds: this.config.BCRYPT_ROUNDS,
      password: {
        minLength: this.config.PASSWORD_MIN_LENGTH,
        requireUppercase: this.config.PASSWORD_REQUIRE_UPPERCASE,
        requireLowercase: this.config.PASSWORD_REQUIRE_LOWERCASE,
        requireNumber: this.config.PASSWORD_REQUIRE_NUMBER,
        requireSpecial: this.config.PASSWORD_REQUIRE_SPECIAL
      },
      login: {
        maxAttempts: this.config.MAX_LOGIN_ATTEMPTS,
        lockoutDuration: this.config.LOGIN_LOCKOUT_DURATION
      },
      encryption: {
        key: this.config.ENCRYPTION_KEY,
        iv: this.config.ENCRYPTION_IV
      }
    };
  }

  public getRateLimitConfig() {
    return {
      windowMs: this.config.RATE_LIMIT_WINDOW,
      max: this.config.RATE_LIMIT_MAX,
      skipSuccessfulRequests: this.config.RATE_LIMIT_SKIP_SUCCESSFUL_REQUESTS
    };
  }

  public getCorsConfig() {
    return {
      origin: this.config.CORS_ORIGIN === '*' ? true : this.config.CORS_ORIGIN.split(','),
      credentials: this.config.CORS_CREDENTIALS
    };
  }

  public getAppConfig() {
    return {
      name: this.config.APP_NAME,
      url: this.config.APP_URL,
      frontendUrl: this.config.FRONTEND_URL,
      mobileDeepLinkScheme: this.config.MOBILE_DEEP_LINK_SCHEME,
      timezone: this.config.DEFAULT_TIMEZONE,
      locale: this.config.DEFAULT_LOCALE,
      currency: this.config.DEFAULT_CURRENCY
    };
  }

  public getPaginationConfig() {
    return {
      defaultLimit: this.config.PAGINATION_DEFAULT_LIMIT,
      maxLimit: this.config.PAGINATION_MAX_LIMIT
    };
  }

  public getMaintenanceConfig() {
    return {
      enabled: this.config.MAINTENANCE_MODE,
      message: this.config.MAINTENANCE_MESSAGE,
      allowedIps: this.config.MAINTENANCE_ALLOWED_IPS
    };
  }

  public getFeatureFlags() {
    return {
      experimental: this.config.ENABLE_EXPERIMENTAL_FEATURES,
      apiDocs: this.config.ENABLE_API_DOCS,
      graphql: this.config.ENABLE_GRAPHQL,
      websockets: this.config.ENABLE_WEBSOCKETS
    };
  }
}

// ==================== إنشاء وتصدير المثيل ====================

const envConfig = new EnvironmentConfig();

export default envConfig;

// تصدير دوال مساعدة
export const getEnv = <K extends keyof EnvConfig>(key: K): EnvConfig[K] => envConfig.get(key);
export const isProduction = () => envConfig.isProduction();
export const isDevelopment = () => envConfig.isDevelopment();
export const isTest = () => envConfig.isTest();