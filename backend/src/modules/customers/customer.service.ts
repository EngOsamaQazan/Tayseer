import { PrismaClient, Customer, Prisma } from '@prisma/client';
import { Redis } from 'ioredis';
import { logger } from '../../utils/logger.util';
import { AppError } from '../../middleware/error.middleware';
import { encryptionUtil } from '../../utils/encryption.util';
import { notificationService } from '../../services/notification.service';
import { auditService } from '../../services/audit.service';
import { storageService } from '../../services/storage.service';
import { exportService } from '../../services/export.service';
import { CreateCustomerDto, UpdateCustomerDto, CustomerSearchDto } from './customer.validation';
import { envConfig } from '../../config/env.config';

export class CustomerService {
  private prisma: PrismaClient;
  private redis: Redis;
  private cachePrefix = 'customer:';
  private cacheTTL = 3600; // 1 hour

  constructor(prisma: PrismaClient, redis: Redis) {
    this.prisma = prisma;
    this.redis = redis;
  }

  /**
   * إنشاء عميل جديد
   */
  async createCustomer(
    data: CreateCustomerDto,
    tenantId: string,
    userId: string
  ): Promise<Customer> {
    try {
      // التحقق من عدم تكرار البريد الإلكتروني أو رقم الهاتف
      const existingCustomer = await this.prisma.customer.findFirst({
        where: {
          tenantId,
          OR: [
            { email: data.email },
            { phone: data.phone }
          ],
          deletedAt: null
        }
      });

      if (existingCustomer) {
        if (existingCustomer.email === data.email) {
          throw new AppError('البريد الإلكتروني مستخدم بالفعل', 409);
        }
        if (existingCustomer.phone === data.phone) {
          throw new AppError('رقم الهاتف مستخدم بالفعل', 409);
        }
      }

      // تشفير البيانات الحساسة
      const encryptedData = {
        ...data,
        nationalId: data.nationalId ? encryptionUtil.encrypt(data.nationalId) : undefined,
        taxNumber: data.taxNumber ? encryptionUtil.encrypt(data.taxNumber) : undefined
      };

      // إنشاء العميل
      const customer = await this.prisma.customer.create({
        data: {
          ...encryptedData,
          tenantId,
          createdBy: userId,
          customerCode: await this.generateCustomerCode(tenantId)
        },
        include: {
          tags: true,
          _count: {
            select: {
              contracts: true,
              documents: true,
              notes: true
            }
          }
        }
      });

      // تسجيل النشاط
      await auditService.log({
        action: 'customer.created',
        resourceType: 'customer',
        resourceId: customer.id,
        tenantId,
        userId,
        details: { customerName: customer.name }
      });

      // إرسال إشعار ترحيب
      await notificationService.sendWelcomeNotification(customer);

      // حذف كاش القائمة
      await this.invalidateListCache(tenantId);

      logger.info('Customer created successfully', { customerId: customer.id });
      return customer;
    } catch (error) {
      logger.error('Error creating customer', error);
      throw error;
    }
  }

  /**
   * جلب قائمة العملاء
   */
  async listCustomers(
    tenantId: string,
    options: {
      page?: number;
      limit?: number;
      search?: string;
      status?: string;
      type?: string;
      tags?: string[];
      sortBy?: string;
      sortOrder?: 'asc' | 'desc';
    }
  ) {
    try {
      const {
        page = 1,
        limit = 20,
        search,
        status,
        type,
        tags,
        sortBy = 'createdAt',
        sortOrder = 'desc'
      } = options;

      // محاولة الحصول على البيانات من الكاش
      const cacheKey = `${this.cachePrefix}list:${tenantId}:${JSON.stringify(options)}`;
      const cached = await this.redis.get(cacheKey);
      if (cached) {
        return JSON.parse(cached);
      }

      // بناء شروط البحث
      const where: Prisma.CustomerWhereInput = {
        tenantId,
        deletedAt: null,
        ...(search && {
          OR: [
            { name: { contains: search, mode: 'insensitive' } },
            { email: { contains: search, mode: 'insensitive' } },
            { phone: { contains: search } },
            { companyName: { contains: search, mode: 'insensitive' } }
          ]
        }),
        ...(status && { status }),
        ...(type && { type }),
        ...(tags && tags.length > 0 && {
          tags: {
            some: {
              name: { in: tags }
            }
          }
        })
      };

      // حساب العدد الإجمالي
      const total = await this.prisma.customer.count({ where });

      // جلب البيانات
      const customers = await this.prisma.customer.findMany({
        where,
        skip: (page - 1) * limit,
        take: limit,
        orderBy: {
          [sortBy]: sortOrder
        },
        include: {
          tags: true,
          _count: {
            select: {
              contracts: true,
              documents: true,
              notes: true
            }
          }
        }
      });

      // فك تشفير البيانات الحساسة
      const decryptedCustomers = customers.map(customer => ({
        ...customer,
        nationalId: customer.nationalId ? encryptionUtil.decrypt(customer.nationalId) : null,
        taxNumber: customer.taxNumber ? encryptionUtil.decrypt(customer.taxNumber) : null
      }));

      const result = {
        data: decryptedCustomers,
        pagination: {
          page,
          limit,
          total,
          totalPages: Math.ceil(total / limit)
        }
      };

      // حفظ في الكاش
      await this.redis.setex(cacheKey, this.cacheTTL, JSON.stringify(result));

      return result;
    } catch (error) {
      logger.error('Error listing customers', error);
      throw error;
    }
  }

  /**
   * جلب عميل بواسطة المعرف
   */
  async getCustomerById(
    id: string,
    tenantId: string,
    includeRelations = true
  ): Promise<Customer | null> {
    try {
      // محاولة الحصول من الكاش
      const cacheKey = `${this.cachePrefix}${id}`;
      const cached = await this.redis.get(cacheKey);
      if (cached) {
        return JSON.parse(cached);
      }

      const customer = await this.prisma.customer.findFirst({
        where: {
          id,
          tenantId,
          deletedAt: null
        },
        include: includeRelations ? {
          tags: true,
          contracts: {
            where: { deletedAt: null },
            orderBy: { createdAt: 'desc' },
            take: 5
          },
          documents: {
            where: { deletedAt: null },
            orderBy: { createdAt: 'desc' }
          },
          notes: {
            orderBy: { createdAt: 'desc' },
            take: 10,
            include: {
              createdByUser: {
                select: {
                  id: true,
                  name: true,
                  email: true
                }
              }
            }
          },
          _count: {
            select: {
              contracts: true,
              documents: true,
              notes: true,
              invoices: true
            }
          }
        } : undefined
      });

      if (!customer) {
        return null;
      }

      // فك تشفير البيانات الحساسة
      const decryptedCustomer = {
        ...customer,
        nationalId: customer.nationalId ? encryptionUtil.decrypt(customer.nationalId) : null,
        taxNumber: customer.taxNumber ? encryptionUtil.decrypt(customer.taxNumber) : null
      };

      // حفظ في الكاش
      await this.redis.setex(cacheKey, this.cacheTTL, JSON.stringify(decryptedCustomer));

      return decryptedCustomer;
    } catch (error) {
      logger.error('Error getting customer by id', error);
      throw error;
    }
  }

  /**
   * تحديث بيانات عميل
   */
  async updateCustomer(
    id: string,
    data: UpdateCustomerDto,
    tenantId: string,
    userId: string
  ): Promise<Customer> {
    try {
      // التحقق من وجود العميل
      const existingCustomer = await this.getCustomerById(id, tenantId, false);
      if (!existingCustomer) {
        throw new AppError('العميل غير موجود', 404);
      }

      // التحقق من عدم تكرار البريد الإلكتروني أو رقم الهاتف
      if (data.email || data.phone) {
        const duplicate = await this.prisma.customer.findFirst({
          where: {
            tenantId,
            id: { not: id },
            OR: [
              ...(data.email ? [{ email: data.email }] : []),
              ...(data.phone ? [{ phone: data.phone }] : [])
            ],
            deletedAt: null
          }
        });

        if (duplicate) {
          if (duplicate.email === data.email) {
            throw new AppError('البريد الإلكتروني مستخدم بالفعل', 409);
          }
          if (duplicate.phone === data.phone) {
            throw new AppError('رقم الهاتف مستخدم بالفعل', 409);
          }
        }
      }

      // تشفير البيانات الحساسة
      const encryptedData = {
        ...data,
        nationalId: data.nationalId ? encryptionUtil.encrypt(data.nationalId) : undefined,
        taxNumber: data.taxNumber ? encryptionUtil.encrypt(data.taxNumber) : undefined
      };

      // تحديث العميل
      const updatedCustomer = await this.prisma.customer.update({
        where: { id },
        data: {
          ...encryptedData,
          updatedBy: userId,
          updatedAt: new Date()
        },
        include: {
          tags: true,
          _count: {
            select: {
              contracts: true,
              documents: true,
              notes: true
            }
          }
        }
      });

      // تسجيل النشاط
      await auditService.log({
        action: 'customer.updated',
        resourceType: 'customer',
        resourceId: id,
        tenantId,
        userId,
        details: {
          changes: data,
          customerName: updatedCustomer.name
        }
      });

      // إبطال الكاش
      await this.invalidateCustomerCache(id, tenantId);

      logger.info('Customer updated successfully', { customerId: id });
      return updatedCustomer;
    } catch (error) {
      logger.error('Error updating customer', error);
      throw error;
    }
  }

  /**
   * حذف عميل (حذف ناعم)
   */
  async deleteCustomer(
    id: string,
    tenantId: string,
    userId: string
  ): Promise<void> {
    try {
      // التحقق من وجود العميل
      const customer = await this.getCustomerById(id, tenantId, false);
      if (!customer) {
        throw new AppError('العميل غير موجود', 404);
      }

      // التحقق من عدم وجود عقود نشطة
      const activeContracts = await this.prisma.contract.count({
        where: {
          customerId: id,
          status: 'active',
          deletedAt: null
        }
      });

      if (activeContracts > 0) {
        throw new AppError('لا يمكن حذف العميل لوجود عقود نشطة', 400);
      }

      // حذف ناعم
      await this.prisma.customer.update({
        where: { id },
        data: {
          deletedAt: new Date(),
          deletedBy: userId
        }
      });

      // تسجيل النشاط
      await auditService.log({
        action: 'customer.deleted',
        resourceType: 'customer',
        resourceId: id,
        tenantId,
        userId,
        details: { customerName: customer.name }
      });

      // إبطال الكاش
      await this.invalidateCustomerCache(id, tenantId);

      logger.info('Customer deleted successfully', { customerId: id });
    } catch (error) {
      logger.error('Error deleting customer', error);
      throw error;
    }
  }

  /**
   * البحث المتقدم في العملاء
   */
  async searchCustomers(
    tenantId: string,
    searchDto: CustomerSearchDto
  ) {
    try {
      const { query, fields = ['name', 'email', 'phone', 'companyName'] } = searchDto;

      // بناء شروط البحث
      const searchConditions = fields.map(field => {
        if (field === 'tags') {
          return {
            tags: {
              some: {
                name: { contains: query, mode: 'insensitive' as const }
              }
            }
          };
        }
        return {
          [field]: { contains: query, mode: 'insensitive' as const }
        };
      });

      const customers = await this.prisma.customer.findMany({
        where: {
          tenantId,
          deletedAt: null,
          OR: searchConditions
        },
        take: 20,
        include: {
          tags: true,
          _count: {
            select: {
              contracts: true,
              documents: true
            }
          }
        }
      });

      // فك تشفير البيانات الحساسة
      const decryptedCustomers = customers.map(customer => ({
        ...customer,
        nationalId: customer.nationalId ? encryptionUtil.decrypt(customer.nationalId) : null,
        taxNumber: customer.taxNumber ? encryptionUtil.decrypt(customer.taxNumber) : null
      }));

      return decryptedCustomers;
    } catch (error) {
      logger.error('Error searching customers', error);
      throw error;
    }
  }

  /**
   * إضافة ملاحظة للعميل
   */
  async addNote(
    customerId: string,
    tenantId: string,
    userId: string,
    noteData: {
      content: string;
      isPrivate: boolean;
      category: string;
    }
  ) {
    try {
      // التحقق من وجود العميل
      const customer = await this.getCustomerById(customerId, tenantId, false);
      if (!customer) {
        throw new AppError('العميل غير موجود', 404);
      }

      const note = await this.prisma.customerNote.create({
        data: {
          ...noteData,
          customerId,
          tenantId,
          createdBy: userId
        },
        include: {
          createdByUser: {
            select: {
              id: true,
              name: true,
              email: true
            }
          }
        }
      });

      // تسجيل النشاط
      await auditService.log({
        action: 'customer.note.added',
        resourceType: 'customer',
        resourceId: customerId,
        tenantId,
        userId,
        details: {
          noteId: note.id,
          category: note.category
        }
      });

      // إبطال كاش العميل
      await this.invalidateCustomerCache(customerId, tenantId);

      return note;
    } catch (error) {
      logger.error('Error adding customer note', error);
      throw error;
    }
  }

  /**
   * إدارة وسوم العميل
   */
  async manageTags(
    customerId: string,
    tenantId: string,
    userId: string,
    action: 'add' | 'remove' | 'replace',
    tags: string[]
  ) {
    try {
      // التحقق من وجود العميل
      const customer = await this.getCustomerById(customerId, tenantId, false);
      if (!customer) {
        throw new AppError('العميل غير موجود', 404);
      }

      let updatedCustomer;

      if (action === 'add') {
        // إضافة وسوم جديدة
        const tagRecords = await Promise.all(
          tags.map(async (tagName) => {
            return await this.prisma.tag.upsert({
              where: {
                name_tenantId: {
                  name: tagName,
                  tenantId
                }
              },
              update: {},
              create: {
                name: tagName,
                tenantId
              }
            });
          })
        );

        updatedCustomer = await this.prisma.customer.update({
          where: { id: customerId },
          data: {
            tags: {
              connect: tagRecords.map(tag => ({ id: tag.id }))
            }
          },
          include: { tags: true }
        });
      } else if (action === 'remove') {
        // إزالة وسوم
        const tagRecords = await this.prisma.tag.findMany({
          where: {
            name: { in: tags },
            tenantId
          }
        });

        updatedCustomer = await this.prisma.customer.update({
          where: { id: customerId },
          data: {
            tags: {
              disconnect: tagRecords.map(tag => ({ id: tag.id }))
            }
          },
          include: { tags: true }
        });
      } else {
        // استبدال جميع الوسوم
        const tagRecords = await Promise.all(
          tags.map(async (tagName) => {
            return await this.prisma.tag.upsert({
              where: {
                name_tenantId: {
                  name: tagName,
                  tenantId
                }
              },
              update: {},
              create: {
                name: tagName,
                tenantId
              }
            });
          })
        );

        updatedCustomer = await this.prisma.customer.update({
          where: { id: customerId },
          data: {
            tags: {
              set: tagRecords.map(tag => ({ id: tag.id }))
            }
          },
          include: { tags: true }
        });
      }

      // تسجيل النشاط
      await auditService.log({
        action: 'customer.tags.updated',
        resourceType: 'customer',
        resourceId: customerId,
        tenantId,
        userId,
        details: { action, tags }
      });

      // إبطال الكاش
      await this.invalidateCustomerCache(customerId, tenantId);

      return updatedCustomer;
    } catch (error) {
      logger.error('Error managing customer tags', error);
      throw error;
    }
  }

  /**
   * جلب إحصائيات العميل
   */
  async getCustomerStatistics(
    customerId: string,
    tenantId: string,
    options?: {
      period?: string;
      startDate?: Date;
      endDate?: Date;
    }
  ) {
    try {
      const customer = await this.getCustomerById(customerId, tenantId, false);
      if (!customer) {
        throw new AppError('العميل غير موجود', 404);
      }

      const { startDate, endDate } = this.getDateRange(options?.period, options?.startDate, options?.endDate);

      // إحصائيات العقود
      const contractStats = await this.prisma.contract.groupBy({
        by: ['status'],
        where: {
          customerId,
          tenantId,
          deletedAt: null,
          ...(startDate && endDate && {
            createdAt: {
              gte: startDate,
              lte: endDate
            }
          })
        },
        _count: true,
        _sum: {
          totalAmount: true
        }
      });

      // إحصائيات الفواتير
      const invoiceStats = await this.prisma.invoice.aggregate({
        where: {
          customerId,
          tenantId,
          deletedAt: null,
          ...(startDate && endDate && {
            createdAt: {
              gte: startDate,
              lte: endDate
            }
          })
        },
        _count: true,
        _sum: {
          totalAmount: true,
          paidAmount: true
        }
      });

      // إحصائيات المدفوعات
      const paymentStats = await this.prisma.payment.aggregate({
        where: {
          invoice: {
            customerId,
            tenantId
          },
          status: 'completed',
          ...(startDate && endDate && {
            createdAt: {
              gte: startDate,
              lte: endDate
            }
          })
        },
        _count: true,
        _sum: {
          amount: true
        }
      });

      // عدد المستندات
      const documentCount = await this.prisma.customerDocument.count({
        where: {
          customerId,
          deletedAt: null
        }
      });

      // عدد الملاحظات
      const noteCount = await this.prisma.customerNote.count({
        where: {
          customerId
        }
      });

      return {
        customer: {
          id: customer.id,
          name: customer.name,
          type: customer.type,
          status: customer.status,
          creditLimit: customer.creditLimit,
          createdAt: customer.createdAt
        },
        contracts: {
          total: contractStats.reduce((sum, stat) => sum + stat._count, 0),
          byStatus: contractStats.reduce((acc, stat) => {
            acc[stat.status] = {
              count: stat._count,
              totalAmount: stat._sum.totalAmount || 0
            };
            return acc;
          }, {} as Record<string, any>)
        },
        invoices: {
          total: invoiceStats._count,
          totalAmount: invoiceStats._sum.totalAmount || 0,
          paidAmount: invoiceStats._sum.paidAmount || 0,
          unpaidAmount: (invoiceStats._sum.totalAmount || 0) - (invoiceStats._sum.paidAmount || 0)
        },
        payments: {
          total: paymentStats._count,
          totalAmount: paymentStats._sum.amount || 0
        },
        documents: documentCount,
        notes: noteCount,
        period: options?.period || 'all',
        dateRange: startDate && endDate ? { startDate, endDate } : null
      };
    } catch (error) {
      logger.error('Error getting customer statistics', error);
      throw error;
    }
  }

  /**
   * جلب سجل نشاطات العميل
   */
  async getActivityLog(
    customerId: string,
    tenantId: string,
    options: {
      page?: number;
      limit?: number;
      action?: string;
      startDate?: Date;
      endDate?: Date;
    }
  ) {
    try {
      const customer = await this.getCustomerById(customerId, tenantId, false);
      if (!customer) {
        throw new AppError('العميل غير موجود', 404);
      }

      const { page = 1, limit = 20, action, startDate, endDate } = options;

      const where: any = {
        resourceType: 'customer',
        resourceId: customerId,
        tenantId,
        ...(action && { action: { contains: action } }),
        ...(startDate && endDate && {
          createdAt: {
            gte: startDate,
            lte: endDate
          }
        })
      };

      const [activities, total] = await Promise.all([
        this.prisma.auditLog.findMany({
          where,
          skip: (page - 1) * limit,
          take: limit,
          orderBy: { createdAt: 'desc' },
          include: {
            user: {
              select: {
                id: true,
                name: true,
                email: true
              }
            }
          }
        }),
        this.prisma.auditLog.count({ where })
      ]);

      return {
        data: activities,
        pagination: {
          page,
          limit,
          total,
          totalPages: Math.ceil(total / limit)
        }
      };
    } catch (error) {
      logger.error('Error getting customer activity log', error);
      throw error;
    }
  }

  /**
   * تصدير بيانات العملاء
   */
  async exportCustomers(
    tenantId: string,
    format: 'xlsx' | 'csv',
    filters?: any
  ): Promise<Buffer> {
    try {
      // جلب البيانات
      const customers = await this.prisma.customer.findMany({
        where: {
          tenantId,
          deletedAt: null,
          ...filters
        },
        include: {
          tags: true,
          _count: {
            select: {
              contracts: true,
              invoices: true
            }
          }
        }
      });

      // تحضير البيانات للتصدير
      const exportData = customers.map(customer => ({
        'رقم العميل': customer.customerCode,
        'الاسم': customer.name,
        'البريد الإلكتروني': customer.email,
        'رقم الهاتف': customer.phone,
        'النوع': customer.type === 'individual' ? 'فرد' : 'شركة',
        'اسم الشركة': customer.companyName || '-',
        'الرقم الضريبي': customer.taxNumber ? encryptionUtil.decrypt(customer.taxNumber) : '-',
        'الحالة': this.getStatusLabel(customer.status),
        'حد الائتمان': customer.creditLimit || 0,
        'المستخدم': customer.creditUsed || 0,
        'المتاح': (customer.creditLimit || 0) - (customer.creditUsed || 0),
        'عدد العقود': customer._count.contracts,
        'عدد الفواتير': customer._count.invoices,
        'الوسوم': customer.tags.map(tag => tag.name).join(', ') || '-',
        'تاريخ الإنشاء': customer.createdAt.toISOString().split('T')[0]
      }));

      // إنشاء ملف Excel أو CSV
      if (format === 'xlsx') {
        const XLSX = await import('xlsx');
        const worksheet = XLSX.utils.json_to_sheet(exportData);
        const workbook = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(workbook, worksheet, 'العملاء');
        return Buffer.from(XLSX.write(workbook, { type: 'buffer', bookType: 'xlsx' }));
      } else {
        const csvUtil = await import('../../utils/csv.util');
        return Buffer.from(await csvUtil.convertToCSV(exportData));
      }
    } catch (error) {
      logger.error('Error exporting customers', error);
      throw error;
    }
  }

  /**
   * دالة مساعدة لإبطال كاش العميل
   */
  private async invalidateCustomerCache(customerId: string, tenantId: string) {
    const patterns = [
      cacheUtil.generateKey('customer', tenantId, customerId),
      cacheUtil.generateKey('customers', tenantId, '*')
    ];

    for (const pattern of patterns) {
      await cacheUtil.deletePattern(pattern);
    }
  }

  /**
   * دالة مساعدة للحصول على تسمية الحالة
   */
  private getStatusLabel(status: string): string {
    const statusMap: Record<string, string> = {
      active: 'نشط',
      inactive: 'غير نشط',
      suspended: 'معلق'
    };
    return statusMap[status] || status;
  }

  /**
   * دالة مساعدة لحساب نطاق التاريخ
   */
  private getDateRange(period?: string, startDate?: Date, endDate?: Date) {
    if (startDate && endDate) {
      return { startDate, endDate };
    }

    const now = new Date();
    let start: Date;
    let end: Date = now;

    switch (period) {
      case 'today':
        start = new Date(now.setHours(0, 0, 0, 0));
        break;
      case 'week':
        start = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000);
        break;
      case 'month':
        start = new Date(now.getFullYear(), now.getMonth(), 1);
        break;
      case 'quarter':
        const quarter = Math.floor(now.getMonth() / 3);
        start = new Date(now.getFullYear(), quarter * 3, 1);
        break;
      case 'year':
        start = new Date(now.getFullYear(), 0, 1);
        break;
      default:
        return {};
    }

    return { startDate: start, endDate: end };
  }

  /**
   * التحقق من وجود رقم الهاتف
   */
  private async checkPhoneDuplicate(
    phone: string,
    tenantId: string,
    excludeId?: string
  ): Promise<boolean> {
    const existing = await this.prisma.customer.findFirst({
      where: {
        phone,
        tenantId,
        deletedAt: null,
        ...(excludeId && { id: { not: excludeId } })
      }
    });
    return !!existing;
  }

  /**
   * التحقق من وجود البريد الإلكتروني
   */
  private async checkEmailDuplicate(
    email: string,
    tenantId: string,
    excludeId?: string
  ): Promise<boolean> {
    const existing = await this.prisma.customer.findFirst({
      where: {
        email,
        tenantId,
        deletedAt: null,
        ...(excludeId && { id: { not: excludeId } })
      }
    });
    return !!existing;
  }
}

export default new CustomerService();