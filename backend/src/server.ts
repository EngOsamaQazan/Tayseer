import express, { Application } from 'express';
import cors from 'cors';
import helmet from 'helmet';
import dotenv from 'dotenv';
import { createServer } from 'http';
import { Server as SocketIOServer } from 'socket.io';
import rateLimit from 'express-rate-limit';

// تحميل متغيرات البيئة
dotenv.config();

// استيراد الإعدادات
import { logger } from './config/logger';
import { connectDatabase } from './config/database';
import { connectRedis } from './config/redis';
import { connectMongoDB } from './config/mongodb';

// استيراد المسارات
import authRoutes from './modules/auth/auth.routes';
import customerRoutes from './modules/customers/customer.routes';
import inventoryRoutes from './modules/inventory/inventory.routes';
import contractRoutes from './modules/contracts/contract.routes';
import accountingRoutes from './modules/accounting/accounting.routes';
import employeeRoutes from './modules/employees/employee.routes';
import taskRoutes from './modules/tasks/task.routes';
import legalRoutes from './modules/legal/legal.routes';
import supportRoutes from './modules/support/support.routes';
import reportRoutes from './modules/reports/report.routes';
import investorRoutes from './modules/investors/investor.routes';

// استيراد الوسطاء
import { errorHandler } from './middleware/error.middleware';
import { authMiddleware } from './middleware/auth.middleware';
import { tenantMiddleware } from './middleware/tenant.middleware';

class Server {
  private app: Application;
  private httpServer: any;
  private io: SocketIOServer;
  private port: number;

  constructor() {
    this.app = express();
    this.httpServer = createServer(this.app);
    this.io = new SocketIOServer(this.httpServer, {
      cors: {
        origin: process.env.CORS_ORIGIN || '*',
        methods: ['GET', 'POST', 'PUT', 'DELETE']
      }
    });
    this.port = parseInt(process.env.PORT || '3000', 10);
    
    this.initializeMiddlewares();
    this.initializeRoutes();
    this.initializeErrorHandling();
    this.initializeSocketIO();
  }

  private initializeMiddlewares(): void {
    // الأمان
    this.app.use(helmet());
    
    // CORS
    this.app.use(cors({
      origin: process.env.CORS_ORIGIN || '*',
      credentials: true
    }));

    // معدل الطلبات
    const limiter = rateLimit({
      windowMs: parseInt(process.env.RATE_LIMIT_WINDOW_MS || '900000'),
      max: parseInt(process.env.RATE_LIMIT_MAX_REQUESTS || '100')
    });
    this.app.use('/api/', limiter);

    // تحليل الجسم
    this.app.use(express.json({ limit: '10mb' }));
    this.app.use(express.urlencoded({ extended: true, limit: '10mb' }));

    // السجلات - يمكن إضافة middleware للسجلات هنا لاحقاً

    // الصحة
    this.app.get('/health', (req, res) => {
      res.status(200).json({ 
        status: 'ok',
        timestamp: new Date().toISOString(),
        uptime: process.uptime()
      });
    });
  }

  private initializeRoutes(): void {
    // المسارات العامة
    this.app.use('/api/auth', authRoutes);
    
    // المسارات المحمية
    this.app.use('/api', authMiddleware, tenantMiddleware);
    this.app.use('/api/customers', customerRoutes);
    this.app.use('/api/inventory', inventoryRoutes);
    this.app.use('/api/contracts', contractRoutes);
    this.app.use('/api/accounting', accountingRoutes);
    this.app.use('/api/employees', employeeRoutes);
    this.app.use('/api/tasks', taskRoutes);
    this.app.use('/api/legal', legalRoutes);
    this.app.use('/api/support', supportRoutes);
    this.app.use('/api/reports', reportRoutes);
    this.app.use('/api/investors', investorRoutes);

    // توثيق API
    if (process.env.NODE_ENV === 'development') {
      this.app.use('/api/docs', express.static('docs/api'));
    }
  }

  private initializeErrorHandling(): void {
    this.app.use(errorHandler);
  }

  private initializeSocketIO(): void {
    this.io.on('connection', (socket) => {
      logger.info(`عميل جديد متصل: ${socket.id}`);

      socket.on('join-tenant', (tenantId: string) => {
        socket.join(`tenant-${tenantId}`);
        logger.info(`العميل ${socket.id} انضم إلى المستأجر ${tenantId}`);
      });

      socket.on('disconnect', () => {
        logger.info(`العميل ${socket.id} قطع الاتصال`);
      });
    });

    // تعيين Socket.IO في التطبيق
    this.app.set('io', this.io);
  }

  public async start(): Promise<void> {
    try {
      // الاتصال بقواعد البيانات
      await connectDatabase();
      await connectRedis();
      await connectMongoDB();

      // بدء الخادم
      this.httpServer.listen(this.port, () => {
        logger.info(`🚀 الخادم يعمل على المنفذ ${this.port}`);
        logger.info(`📝 البيئة: ${process.env.NODE_ENV}`);
      });
    } catch (error) {
      logger.error('فشل في بدء الخادم:', error);
      process.exit(1);
    }
  }
}

// بدء الخادم
const server = new Server();
server.start();

// معالجة إيقاف التشغيل
process.on('SIGTERM', async () => {
  logger.info('تم استلام SIGTERM، إيقاف الخادم بأمان...');
  process.exit(0);
});

process.on('SIGINT', async () => {
  logger.info('تم استلام SIGINT، إيقاف الخادم بأمان...');
  process.exit(0);
});