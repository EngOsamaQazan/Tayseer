import { PrismaClient } from '@prisma/client';
import { logger } from '@/config/logger';

// إنشاء عميل Prisma مع إعدادات مخصصة
export const prisma = new PrismaClient({
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

// تسجيل الاستعلامات في بيئة التطوير
if (process.env.NODE_ENV === 'development') {
  prisma.$on('query', (e) => {
    logger.debug('Query: ' + e.query);
    logger.debug('Params: ' + e.params);
    logger.debug('Duration: ' + e.duration + 'ms');
  });
}

// تسجيل الأخطاء
prisma.$on('error', (e) => {
  logger.error('Prisma Error:', e);
});

// تسجيل المعلومات
prisma.$on('info', (e) => {
  logger.info('Prisma Info:', e.message);
});

// تسجيل التحذيرات
prisma.$on('warn', (e) => {
  logger.warn('Prisma Warning:', e.message);
});

// إغلاق الاتصال عند إنهاء العملية
process.on('beforeExit', async () => {
  await prisma.$disconnect();
});