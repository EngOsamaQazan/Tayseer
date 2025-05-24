import { PrismaClient } from '@prisma/client';
import { logger } from './logger';

// إنشاء عميل Prisma واحد للتطبيق
const prisma = new PrismaClient({
  log: [
    {
      emit: 'event',
      level: 'query',
    },
    {
      emit: 'event',
      level: 'error',
    },
    {
      emit: 'event',
      level: 'info',
    },
    {
      emit: 'event',
      level: 'warn',
    },
  ],
});

// تسجيل الاستعلامات في وضع التطوير
if (process.env.NODE_ENV === 'development') {
  prisma.$on('query', (e) => {
    logger.debug('Query: ' + e.query);
    logger.debug('Duration: ' + e.duration + 'ms');
  });
}

// تسجيل الأخطاء
prisma.$on('error', (e) => {
  logger.error('Prisma Error:', e);
});

// دالة الاتصال بقاعدة البيانات
export async function connectDatabase(): Promise<void> {
  try {
    await prisma.$connect();
    logger.info('✅ تم الاتصال بقاعدة البيانات PostgreSQL بنجاح');
  } catch (error) {
    logger.error('❌ فشل الاتصال بقاعدة البيانات PostgreSQL:', error);
    throw error;
  }
}

// دالة قطع الاتصال
export async function disconnectDatabase(): Promise<void> {
  try {
    await prisma.$disconnect();
    logger.info('تم قطع الاتصال بقاعدة البيانات');
  } catch (error) {
    logger.error('خطأ في قطع الاتصال بقاعدة البيانات:', error);
    throw error;
  }
}

// معالجة إيقاف التطبيق
process.on('beforeExit', async () => {
  await disconnectDatabase();
});

export { prisma };
export default prisma;