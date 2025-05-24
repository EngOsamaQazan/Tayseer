import { Contract, ContractItem, ContractAttachment } from '@prisma/client';
import prisma from '../../config/database.config';
import { RedisService } from '../../services/redis.service';
import { Logger } from '../../utils/logger.util';
import { ApiError } from '../../utils/errors.util';
import { NotificationService } from '../../services/notification.service';
import { AuditService } from '../../services/audit.service';
import { FileUploadService } from '../../services/fileUpload.service';
import * as xlsx from 'xlsx';
import { createObjectCsvStringifier } from 'csv-writer';
import { encryptData, decryptData } from '../../utils/encryption.util';

interface CreateContractData {
  customerId: string;
  type: string;
  title: string;
  description?: string;
  startDate: Date;
  endDate: Date;
  value: number;
  paymentTerm: string;
  autoRenew?: boolean;
  renewalPeriod?: number;
  terms?: string;
  items?: any[];
  tags?: string[];
  metadata?: any;
  tenantId: string;
  createdBy: string;
}

interface UpdateContractData {
  type?: string;
  title?: string;
  description?: string;
  startDate?: Date;
  endDate?: Date;
  value?: number;
  paymentTerm?: string;
  autoRenew?: boolean;
  renewalPeriod?: number;
  terms?: string;
  tags?: string[];
  metadata?: any;
  updatedBy: string;
}

interface ContractListParams {
  page?: number;
  limit?: number;
  search?: string;
  status?: string;
  type?: string;
  customerId?: string;
  startDate?: string;
  endDate?: string;
  sortBy?: string;
  sortOrder?: 'asc' | 'desc';
  tenantId: string;
}

interface ContractStatisticsParams {
  tenantId: string;
  startDate?: string;
  endDate?: string;
  customerId?: string;
  status?: string;
  type?: string;
}

export class ContractService {
  private logger: Logger;
  private redisService: RedisService;
  private notificationService: NotificationService;
  private auditService: AuditService;
  private fileUploadService: FileUploadService;

  constructor() {
    this.logger = new Logger('ContractService');
    this.redisService = RedisService.getInstance();
    this.notificationService = NotificationService.getInstance();
    this.auditService = AuditService.getInstance();
    this.fileUploadService = FileUploadService.getInstance();
  }

  async createContract(data: CreateContractData): Promise<Contract> {
    try {
      // Validate customer exists
      const customer = await prisma.customer.findFirst({
        where: {
          id: data.customerId,
          tenantId: data.tenantId,
          isDeleted: false,
        },
      });

      if (!customer) {
        throw new ApiError(404, 'العميل غير موجود');
      }

      // Generate contract number
      const contractNumber = await this.generateContractNumber(data.tenantId);

      // Create contract
      const contract = await prisma.contract.create({
        data: {
          contractNumber,
          customerId: data.customerId,
          type: data.type,
          title: data.title,
          description: data.description,
          startDate: data.startDate,
          endDate: data.endDate,
          value: data.value,
          paymentTerm: data.paymentTerm,
          autoRenew: data.autoRenew || false,
          renewalPeriod: data.renewalPeriod,
          terms: data.terms,
          status: 'draft',
          tags: data.tags || [],
          metadata: data.metadata || {},
          tenantId: data.tenantId,
          createdBy: data.createdBy,
          updatedBy: data.createdBy,
        },
        include: {
          customer: true,
          items: true,
          attachments: true,
        },
      });

      // Create contract items if provided
      if (data.items && data.items.length > 0) {
        await Promise.all(
          data.items.map((item) =>
            prisma.contractItem.create({
              data: {
                contractId: contract.id,
                ...item,
              },
            })
          )
        );
      }

      // Log audit
      await this.auditService.log({
        userId: data.createdBy,
        tenantId: data.tenantId,
        entityType: 'contract',
        entityId: contract.id,
        action: 'created',
        changes: data,
      });

      // Invalidate cache
      await this.invalidateContractCache(data.tenantId);

      this.logger.info(`Contract created: ${contract.id}`);
      return contract;
    } catch (error) {
      this.logger.error('Error creating contract:', error);
      throw error;
    }
  }

  async listContracts(params: ContractListParams): Promise<any> {
    const {
      page = 1,
      limit = 10,
      search,
      status,
      type,
      customerId,
      startDate,
      endDate,
      sortBy = 'createdAt',
      sortOrder = 'desc',
      tenantId,
    } = params;

    const cacheKey = `contracts:${tenantId}:${JSON.stringify(params)}`;

    try {
      // Check cache
      const cached = await this.redisService.get(cacheKey);
      if (cached) {
        return JSON.parse(cached);
      }

      const where: any = { tenantId, isDeleted: false };

      if (search) {
        where.OR = [
          { contractNumber: { contains: search, mode: 'insensitive' } },
          { title: { contains: search, mode: 'insensitive' } },
          { customer: { name: { contains: search, mode: 'insensitive' } } },
        ];
      }

      if (status) where.status = status;
      if (type) where.type = type;
      if (customerId) where.customerId = customerId;

      if (startDate || endDate) {
        where.startDate = {};
        if (startDate) where.startDate.gte = new Date(startDate);
        if (endDate) where.startDate.lte = new Date(endDate);
      }

      const [total, contracts] = await Promise.all([
        prisma.contract.count({ where }),
        prisma.contract.findMany({
          where,
          include: {
            customer: true,
            items: { where: { isDeleted: false } },
            attachments: { where: { isDeleted: false } },
          },
          orderBy: { [sortBy]: sortOrder },
          skip: (page - 1) * limit,
          take: limit,
        }),
      ]);

      const result = {
        items: contracts,
        total,
        page,
        limit,
        totalPages: Math.ceil(total / limit),
      };

      // Cache result
      await this.redisService.setex(cacheKey, 300, JSON.stringify(result));

      return result;
    } catch (error) {
      this.logger.error('Error listing contracts:', error);
      throw error;
    }
  }

  async getContractById(id: string, tenantId: string): Promise<Contract | null> {
    const cacheKey = `contract:${tenantId}:${id}`;

    try {
      // Check cache
      const cached = await this.redisService.get(cacheKey);
      if (cached) {
        return JSON.parse(cached);
      }

      const contract = await prisma.contract.findFirst({
        where: { id, tenantId, isDeleted: false },
        include: {
          customer: true,
          items: { where: { isDeleted: false } },
          attachments: { where: { isDeleted: false } },
        },
      });

      if (contract) {
        // Cache result
        await this.redisService.setex(cacheKey, 3600, JSON.stringify(contract));
      }

      return contract;
    } catch (error) {
      this.logger.error('Error getting contract:', error);
      throw error;
    }
  }

  async updateContract(id: string, data: UpdateContractData, tenantId: string): Promise<Contract> {
    try {
      const contract = await this.getContractById(id, tenantId);
      if (!contract) {
        throw new ApiError(404, 'العقد غير موجود');
      }

      if (contract.status === 'cancelled' || contract.status === 'completed') {
        throw new ApiError(400, 'لا يمكن تعديل عقد ملغي أو مكتمل');
      }

      const updatedContract = await prisma.contract.update({
        where: { id },
        data: {
          ...data,
          updatedAt: new Date(),
        },
        include: {
          customer: true,
          items: true,
          attachments: true,
        },
      });

      // Log audit
      await this.auditService.log({
        userId: data.updatedBy,
        tenantId,
        entityType: 'contract',
        entityId: id,
        action: 'updated',
        changes: data,
      });

      // Invalidate cache
      await this.invalidateContractCache(tenantId, id);

      this.logger.info(`Contract updated: ${id}`);
      return updatedContract;
    } catch (error) {
      this.logger.error('Error updating contract:', error);
      throw error;
    }
  }

  async deleteContract(id: string, userId: string, tenantId: string): Promise<void> {
    try {
      const contract = await this.getContractById(id, tenantId);
      if (!contract) {
        throw new ApiError(404, 'العقد غير موجود');
      }

      // Check if contract has any associated invoices
      const invoices = await prisma.invoice.count({
        where: { contractId: id, isDeleted: false },
      });

      if (invoices > 0) {
        throw new ApiError(400, 'لا يمكن حذف عقد له فواتير مرتبطة');
      }

      // Soft delete
      await prisma.contract.update({
        where: { id },
        data: {
          isDeleted: true,
          deletedAt: new Date(),
          deletedBy: userId,
        },
      });

      // Log audit
      await this.auditService.log({
        userId,
        tenantId,
        entityType: 'contract',
        entityId: id,
        action: 'deleted',
      });

      // Invalidate cache
      await this.invalidateContractCache(tenantId, id);

      this.logger.info(`Contract deleted: ${id}`);
    } catch (error) {
      this.logger.error('Error deleting contract:', error);
      throw error;
    }
  }

  async activateContract(id: string, userId: string, tenantId: string): Promise<Contract> {
    try {
      const contract = await this.getContractById(id, tenantId);
      if (!contract) {
        throw new ApiError(404, 'العقد غير موجود');
      }

      if (contract.status !== 'draft') {
        throw new ApiError(400, 'يمكن تفعيل العقود المسودة فقط');
      }

      const updatedContract = await prisma.contract.update({
        where: { id },
        data: {
          status: 'active',
          activatedAt: new Date(),
          updatedBy: userId,
        },
        include: {
          customer: true,
          items: true,
          attachments: true,
        },
      });

      // Send notification
      await this.notificationService.sendNotification({
        userId: updatedContract.customer.id,
        type: 'contract_cancelled',
        title: 'تم إلغاء العقد',
        message: `تم إلغاء العقد رقم ${updatedContract.contractNumber}`,
        data: { contractId: id, reason },
      });

      // Log audit
      await this.auditService.log({
        userId,
        tenantId,
        entityType: 'contract',
        entityId: id,
        action: 'cancelled',
        changes: { reason },
      });

      // Invalidate cache
      await this.invalidateContractCache(tenantId, id);

      this.logger.info(`Contract cancelled: ${id}`);
      return updatedContract;
    } catch (error) {
      this.logger.error('Error cancelling contract:', error);
      throw error;
    }
  }

  async renewContract(id: string, renewalData: any, userId: string, tenantId: string): Promise<Contract> {
    try {
      const contract = await this.getContractById(id, tenantId);
      if (!contract) {
        throw new ApiError(404, 'العقد غير موجود');
      }

      if (contract.status !== 'active') {
        throw new ApiError(400, 'يمكن تجديد العقود النشطة فقط');
      }

      // Create new contract based on current
      const newContract = await this.createContract({
        customerId: contract.customerId,
        type: contract.type,
        title: `${contract.title} - تجديد`,
        description: renewalData.description || contract.description,
        startDate: renewalData.startDate || contract.endDate,
        endDate: renewalData.endDate,
        value: renewalData.value || contract.value,
        paymentTerm: renewalData.paymentTerm || contract.paymentTerm,
        autoRenew: renewalData.autoRenew || contract.autoRenew,
        renewalPeriod: renewalData.renewalPeriod || contract.renewalPeriod,
        terms: renewalData.terms || contract.terms,
        tags: contract.tags,
        metadata: {
          ...contract.metadata,
          renewedFrom: contract.id,
        },
        tenantId,
        createdBy: userId,
      });

      // Update old contract
      await prisma.contract.update({
        where: { id },
        data: {
          status: 'completed',
          completedAt: new Date(),
          metadata: {
            ...contract.metadata,
            renewedTo: newContract.id,
          },
        },
      });

      // Send notification
      await this.notificationService.sendNotification({
        userId: contract.customer.id,
        type: 'contract_renewed',
        title: 'تم تجديد العقد',
        message: `تم تجديد العقد رقم ${contract.contractNumber}`,
        data: { oldContractId: id, newContractId: newContract.id },
      });

      // Log audit
      await this.auditService.log({
        userId,
        tenantId,
        entityType: 'contract',
        entityId: id,
        action: 'renewed',
        changes: { newContractId: newContract.id },
      });

      this.logger.info(`Contract renewed: ${id} -> ${newContract.id}`);
      return newContract;
    } catch (error) {
      this.logger.error('Error renewing contract:', error);
      throw error;
    }
  }

  async getContractStatistics(params: ContractStatisticsParams): Promise<any> {
    const { tenantId, startDate, endDate, customerId, status, type } = params;
    const cacheKey = `contract-stats:${tenantId}:${JSON.stringify(params)}`;

    try {
      // Check cache
      const cached = await this.redisService.get(cacheKey);
      if (cached) {
        return JSON.parse(cached);
      }

      const where: any = { tenantId, isDeleted: false };

      if (startDate || endDate) {
        where.createdAt = {};
        if (startDate) where.createdAt.gte = new Date(startDate);
        if (endDate) where.createdAt.lte = new Date(endDate);
      }

      if (customerId) where.customerId = customerId;
      if (status) where.status = status;
      if (type) where.type = type;

      const [total, byStatus, byType, totalValue] = await Promise.all([
        prisma.contract.count({ where }),
        prisma.contract.groupBy({
          by: ['status'],
          where,
          _count: true,
        }),
        prisma.contract.groupBy({
          by: ['type'],
          where,
          _count: true,
        }),
        prisma.contract.aggregate({
          where,
          _sum: { value: true },
        }),
      ]);

      const result = {
        total,
        totalValue: totalValue._sum.value || 0,
        byStatus: byStatus.reduce((acc, item) => {
          acc[item.status] = item._count;
          return acc;
        }, {} as any),
        byType: byType.reduce((acc, item) => {
          acc[item.type] = item._count;
          return acc;
        }, {} as any),
      };

      // Cache result
      await this.redisService.setex(cacheKey, 600, JSON.stringify(result));

      return result;
    } catch (error) {
      this.logger.error('Error getting contract statistics:', error);
      throw error;
    }
  }

  async getContractTimeline(contractId: string, tenantId: string): Promise<any[]> {
    try {
      const auditLogs = await this.auditService.getAuditLogs({
        entityType: 'contract',
        entityId: contractId,
        tenantId,
        limit: 50,
      });

      return auditLogs.map(log => ({
        id: log.id,
        action: log.action,
        timestamp: log.createdAt,
        userId: log.userId,
        changes: log.changes,
      }));
    } catch (error) {
      this.logger.error('Error getting contract timeline:', error);
      throw error;
    }
  }

  async exportContracts(params: ContractListParams, format: 'excel' | 'csv'): Promise<Buffer> {
    try {
      const contracts = await this.listContracts({ ...params, limit: 10000 });

      const data = contracts.items.map((contract: any) => ({
        'رقم العقد': contract.contractNumber,
        'العميل': contract.customer.name,
        'النوع': this.getTypeLabel(contract.type),
        'العنوان': contract.title,
        'القيمة': contract.value,
        'تاريخ البداية': new Date(contract.startDate).toLocaleDateString('ar-SA'),
        'تاريخ النهاية': new Date(contract.endDate).toLocaleDateString('ar-SA'),
        'الحالة': this.getStatusLabel(contract.status),
        'شروط الدفع': this.getPaymentTermLabel(contract.paymentTerm),
      }));

      if (format === 'excel') {
        const worksheet = xlsx.utils.json_to_sheet(data);
        const workbook = xlsx.utils.book_new();
        xlsx.utils.book_append_sheet(workbook, worksheet, 'العقود');
        return xlsx.write(workbook, { type: 'buffer', bookType: 'xlsx' });
      } else {
        const csvStringifier = createObjectCsvStringifier({
          header: Object.keys(data[0]).map(key => ({ id: key, title: key })),
        });
        const records = csvStringifier.stringifyRecords(data);
        const header = csvStringifier.getHeaderString();
        return Buffer.from(`\uFEFF${header}${records}`, 'utf8');
      }
    } catch (error) {
      this.logger.error('Error exporting contracts:', error);
      throw error;
    }
  }

  // Contract Items Management
  async addContractItem(contractId: string, item: any, tenantId: string): Promise<ContractItem> {
    try {
      const contract = await this.getContractById(contractId, tenantId);
      if (!contract) {
        throw new ApiError(404, 'العقد غير موجود');
      }

      const contractItem = await prisma.contractItem.create({
        data: {
          contractId,
          ...item,
        },
      });

      // Update contract value
      const newValue = contract.value + (item.quantity * item.unitPrice);
      await prisma.contract.update({
        where: { id: contractId },
        data: { value: newValue },
      });

      // Invalidate cache
      await this.invalidateContractCache(tenantId, contractId);

      return contractItem;
    } catch (error) {
      this.logger.error('Error adding contract item:', error);
      throw error;
    }
  }

  async updateContractItem(itemId: string, data: any, tenantId: string): Promise<ContractItem> {
    try {
      const item = await prisma.contractItem.findUnique({
        where: { id: itemId },
        include: { contract: true },
      });

      if (!item || item.contract.tenantId !== tenantId) {
        throw new ApiError(404, 'عنصر العقد غير موجود');
      }

      const updatedItem = await prisma.contractItem.update({
        where: { id: itemId },
        data,
      });

      // Recalculate contract value
      const items = await prisma.contractItem.findMany({
        where: { contractId: item.contractId, isDeleted: false },
      });

      const newValue = items.reduce((sum, i) => sum + (i.quantity * i.unitPrice), 0);
      await prisma.contract.update({
        where: { id: item.contractId },
        data: { value: newValue },
      });

      // Invalidate cache
      await this.invalidateContractCache(tenantId, item.contractId);

      return updatedItem;
    } catch (error) {
      this.logger.error('Error updating contract item:', error);
      throw error;
    }
  }

  async deleteContractItem(itemId: string, tenantId: string): Promise<void> {
    try {
      const item = await prisma.contractItem.findUnique({
        where: { id: itemId },
        include: { contract: true },
      });

      if (!item || item.contract.tenantId !== tenantId) {
        throw new ApiError(404, 'عنصر العقد غير موجود');
      }

      await prisma.contractItem.update({
        where: { id: itemId },
        data: { isDeleted: true },
      });

      // Recalculate contract value
      const items = await prisma.contractItem.findMany({
        where: { contractId: item.contractId, isDeleted: false },
      });

      const newValue = items.reduce((sum, i) => sum + (i.quantity * i.unitPrice), 0);
      await prisma.contract.update({
        where: { id: item.contractId },
        data: { value: newValue },
      });

      // Invalidate cache
      await this.invalidateContractCache(tenantId, item.contractId);
    } catch (error) {
      this.logger.error('Error deleting contract item:', error);
      throw error;
    }
  }

  // Contract Attachments Management
  async uploadAttachment(contractId: string, file: any, userId: string, tenantId: string): Promise<ContractAttachment> {
    try {
      const contract = await this.getContractById(contractId, tenantId);
      if (!contract) {
        throw new ApiError(404, 'العقد غير موجود');
      }

      // Upload file
      const uploadResult = await this.fileUploadService.uploadFile(file, {
        folder: `contracts/${contractId}`,
        allowedTypes: ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png'],
        maxSize: 10 * 1024 * 1024, // 10MB
      });

      // Create attachment record
      const attachment = await prisma.contractAttachment.create({
        data: {
          contractId,
          fileName: file.originalname,
          fileUrl: uploadResult.url,
          fileSize: file.size,
          mimeType: file.mimetype,
          uploadedBy: userId,
        },
      });

      // Invalidate cache
      await this.invalidateContractCache(tenantId, contractId);

      this.logger.info(`Attachment uploaded for contract: ${contractId}`);
      return attachment;
    } catch (error) {
      this.logger.error('Error uploading attachment:', error);
      throw error;
    }
  }

  async getAttachments(contractId: string, tenantId: string): Promise<ContractAttachment[]> {
    try {
      const contract = await this.getContractById(contractId, tenantId);
      if (!contract) {
        throw new ApiError(404, 'العقد غير موجود');
      }

      return await prisma.contractAttachment.findMany({
        where: { contractId, isDeleted: false },
        orderBy: { createdAt: 'desc' },
      });
    } catch (error) {
      this.logger.error('Error getting attachments:', error);
      throw error;
    }
  }

  async deleteAttachment(attachmentId: string, tenantId: string): Promise<void> {
    try {
      const attachment = await prisma.contractAttachment.findUnique({
        where: { id: attachmentId },
        include: { contract: true },
      });

      if (!attachment || attachment.contract.tenantId !== tenantId) {
        throw new ApiError(404, 'المرفق غير موجود');
      }

      // Delete file from storage
      await this.fileUploadService.deleteFile(attachment.fileUrl);

      // Delete record
      await prisma.contractAttachment.update({
        where: { id: attachmentId },
        data: { isDeleted: true },
      });

      // Invalidate cache
      await this.invalidateContractCache(tenantId, attachment.contractId);

      this.logger.info(`Attachment deleted: ${attachmentId}`);
    } catch (error) {
      this.logger.error('Error deleting attachment:', error);
      throw error;
    }
  }

  // Auto-renewal
  async processAutoRenewals(): Promise<void> {
    try {
      const contracts = await prisma.contract.findMany({
        where: {
          status: 'active',
          autoRenew: true,
          endDate: {
            lte: new Date(Date.now() + 30 * 24 * 60 * 60 * 1000), // 30 days
          },
        },
        include: {
          customer: true,
        },
      });

      for (const contract of contracts) {
        try {
          const renewalPeriod = contract.renewalPeriod || 'yearly';
          const endDate = this.calculateEndDate(contract.endDate, renewalPeriod);

          await this.renewContract(
            contract.id,
            {
              endDate,
              description: `تجديد تلقائي - ${contract.description}`,
            },
            'system',
            contract.tenantId
          );

          // Send notification
          await this.notificationService.sendNotification({
            userId: contract.customer.id,
            type: 'contract_auto_renewed',
            title: 'تجديد تلقائي للعقد',
            message: `تم تجديد العقد رقم ${contract.contractNumber} تلقائياً`,
            data: { contractId: contract.id },
          });
        } catch (error) {
          this.logger.error(`Error auto-renewing contract ${contract.id}:`, error);
        }
      }
    } catch (error) {
      this.logger.error('Error processing auto-renewals:', error);
    }
  }

  // Helper methods
  private async invalidateContractCache(tenantId: string, contractId?: string): Promise<void> {
    const patterns = [
      `contract-list:${tenantId}:*`,
      `contract-stats:${tenantId}:*`,
    ];

    if (contractId) {
      patterns.push(`contract:${tenantId}:${contractId}`);
    }

    for (const pattern of patterns) {
      const keys = await this.redisService.keys(pattern);
      if (keys.length > 0) {
        await this.redisService.del(...keys);
      }
    }
  }

  private generateContractNumber(type: string): string {
    const prefix = this.getTypePrefix(type);
    const timestamp = Date.now().toString().slice(-6);
    const random = Math.floor(Math.random() * 1000).toString().padStart(3, '0');
    return `${prefix}-${timestamp}-${random}`;
  }

  private getTypePrefix(type: string): string {
    const prefixes: Record<string, string> = {
      service: 'SRV',
      sales: 'SLS',
      employment: 'EMP',
      partnership: 'PRT',
      lease: 'LSE',
      maintenance: 'MNT',
      consulting: 'CNS',
      other: 'OTH',
    };
    return prefixes[type] || 'CTR';
  }

  private getTypeLabel(type: string): string {
    const labels: Record<string, string> = {
      service: 'خدمات',
      sales: 'مبيعات',
      employment: 'توظيف',
      partnership: 'شراكة',
      lease: 'إيجار',
      maintenance: 'صيانة',
      consulting: 'استشارات',
      other: 'أخرى',
    };
    return labels[type] || type;
  }

  private getStatusLabel(status: string): string {
    const labels: Record<string, string> = {
      draft: 'مسودة',
      pending: 'قيد المراجعة',
      active: 'نشط',
      expired: 'منتهي',
      cancelled: 'ملغي',
      completed: 'مكتمل',
    };
    return labels[status] || status;
  }

  private getPaymentTermLabel(term: string): string {
    const labels: Record<string, string> = {
      monthly: 'شهري',
      quarterly: 'ربع سنوي',
      yearly: 'سنوي',
      'one-time': 'دفعة واحدة',
      custom: 'مخصص',
    };
    return labels[term] || term;
  }

  private calculateEndDate(startDate: Date, renewalPeriod: string): Date {
    const date = new Date(startDate);
    switch (renewalPeriod) {
      case 'monthly':
        date.setMonth(date.getMonth() + 1);
        break;
      case 'quarterly':
        date.setMonth(date.getMonth() + 3);
        break;
      case 'yearly':
        date.setFullYear(date.getFullYear() + 1);
        break;
      default:
        date.setFullYear(date.getFullYear() + 1);
    }
    return date;
  }

  // Scheduled tasks
  @Cron('0 0 * * *') // Daily at midnight
  async checkExpiredContracts(): Promise<void> {
    try {
      const expiredContracts = await prisma.contract.findMany({
        where: {
          status: 'active',
          endDate: {
            lt: new Date(),
          },
        },
        include: {
          customer: true,
        },
      });

      for (const contract of expiredContracts) {
        await prisma.contract.update({
          where: { id: contract.id },
          data: { status: 'expired' },
        });

        // Send notification
        await this.notificationService.sendNotification({
          userId: contract.customer.id,
          type: 'contract_expired',
          title: 'انتهى العقد',
          message: `انتهى العقد رقم ${contract.contractNumber}`,
          data: { contractId: contract.id },
        });

        // Invalidate cache
        await this.invalidateContractCache(contract.tenantId, contract.id);
      }

      this.logger.info(`Processed ${expiredContracts.length} expired contracts`);
    } catch (error) {
      this.logger.error('Error checking expired contracts:', error);
    }
  }

  @Cron('0 9 * * *') // Daily at 9 AM
  async sendRenewalReminders(): Promise<void> {
    try {
      const contractsNearExpiry = await prisma.contract.findMany({
        where: {
          status: 'active',
          autoRenew: false,
          endDate: {
            gte: new Date(),
            lte: new Date(Date.now() + 30 * 24 * 60 * 60 * 1000), // 30 days
          },
        },
        include: {
          customer: true,
        },
      });

      for (const contract of contractsNearExpiry) {
        const daysUntilExpiry = Math.ceil(
          (contract.endDate.getTime() - Date.now()) / (24 * 60 * 60 * 1000)
        );

        if ([30, 15, 7, 3, 1].includes(daysUntilExpiry)) {
          await this.notificationService.sendNotification({
            userId: contract.customer.id,
            type: 'contract_expiry_reminder',
            title: 'تذكير بانتهاء العقد',
            message: `العقد رقم ${contract.contractNumber} سينتهي خلال ${daysUntilExpiry} يوم`,
            data: { contractId: contract.id, daysUntilExpiry },
          });
        }
      }

      this.logger.info(`Sent renewal reminders for ${contractsNearExpiry.length} contracts`);
    } catch (error) {
      this.logger.error('Error sending renewal reminders:', error);
    }
  }

  async cancelContract(id: string, reason: string, userId: string, tenantId: string): Promise<Contract> {
    try {
      const contract = await this.getContractById(id, tenantId);
      if (!contract) {
        throw new ApiError(404, 'العقد غير موجود');
      }

      if (contract.status === 'cancelled' || contract.status === 'completed') {
        throw new ApiError(400, 'العقد ملغي أو مكتمل بالفعل');
      }

      // Cancel contract
      const updatedContract = await prisma.contract.update({
        where: { id },
        data: {
          status: 'cancelled',
          cancelledAt: new Date(),
          cancelReason: reason,
          updatedBy: userId,
        },
        include: {
          customer: true,
          items: true,
          attachments: true,
        },
      });

      // Send notification
      await this.notificationService.sendNotification({
        userId: updatedContract.customer.id,
        type: 'contract_cancelled',
        title: 'تم إلغاء العقد',
        message: `تم إلغاء العقد رقم ${updatedContract.contractNumber}`,
        data: { contractId: id, reason },
      });

      // Log audit
      await this.auditService.log({
        userId,
        tenantId,
        entityType: 'contract',
        entityId: id,
        action: 'cancelled',
        changes: { reason },
      });

      // Invalidate cache
      await this.invalidateContractCache(tenantId, id);

      this.logger.info(`Contract cancelled: ${id}`);
      return updatedContract;
    } catch (error) {
      this.logger.error('Error cancelling contract:', error);
      throw error;
    }
  }

  async renewContract(id: string, renewalData: any, userId: string, tenantId: string): Promise<Contract> {
    try {
      const contract = await this.getContractById(id, tenantId);
      if (!contract) {
        throw new ApiError(404, 'العقد غير موجود');
      }

      if (contract.status !== 'active') {
        throw new ApiError(400, 'يمكن تجديد العقود النشطة فقط');
      }

      // Create new contract based on current
      const newContract = await this.createContract({
        customerId: contract.customerId,
        type: contract.type,
        title: `${contract.title} - تجديد`,
        description: renewalData.description || contract.description,
        startDate: renewalData.startDate || contract.endDate,
        endDate: renewalData.endDate,
        value: renewalData.value || contract.value,
        paymentTerm: renewalData.paymentTerm || contract.paymentTerm,
        autoRenew: renewalData.autoRenew || contract.autoRenew,
        renewalPeriod: renewalData.renewalPeriod || contract.renewalPeriod,
        terms: renewalData.terms || contract.terms,
        tags: contract.tags,
        metadata: {
          ...contract.metadata,
          renewedFrom: contract.id,
        },
        tenantId,
        createdBy: userId,
      });

      // Update old contract
      await prisma.contract.update({
        where: { id },
        data: {
          status: 'completed',
          completedAt: new Date(),
          metadata: {
            ...contract.metadata,
            renewedTo: newContract.id,
          },
        },
      });

      // Send notification
      await this.notificationService.sendNotification({
        userId: contract.customer.id,
        type: 'contract_renewed',
        title: 'تم تجديد العقد',
        message: `تم تجديد العقد رقم ${contract.contractNumber}`,
        data: { oldContractId: id, newContractId: newContract.id },
      });

      // Log audit
      await this.auditService.log({
        userId,
        tenantId,
        entityType: 'contract',
        entityId: id,
        action: 'renewed',
        changes: { newContractId: newContract.id },
      });

      this.logger.info(`Contract renewed: ${id} -> ${newContract.id}`);
      return newContract;
    } catch (error) {
      this.logger.error('Error renewing contract:', error);
      throw error;
    }
  }

  async getContractStatistics(params: ContractStatisticsParams): Promise<any> {
    const { tenantId, startDate, endDate, customerId, status, type } = params;
    const cacheKey = `contract-stats:${tenantId}:${JSON.stringify(params)}`;

    try {
      // Check cache
      const cached = await this.redisService.get(cacheKey);
      if (cached) {
        return JSON.parse(cached);
      }

      const where: any = { tenantId, isDeleted: false };

      if (startDate || endDate) {
        where.createdAt = {};
        if (startDate) where.createdAt.gte = new Date(startDate);
        if (endDate) where.createdAt.lte = new Date(endDate);
      }

      if (customerId) where.customerId = customerId;
      if (status) where.status = status;
      if (type) where.type = type;

      const [total, byStatus, byType, totalValue] = await Promise.all([
        prisma.contract.count({ where }),
        prisma.contract.groupBy({
          by: ['status'],
          where,
          _count: true,
        }),
        prisma.contract.groupBy({
          by: ['type'],
          where,
          _count: true,
        }),
        prisma.contract.aggregate({
          where,
          _sum: { value: true },
        }),
      ]);

      const result = {
        total,
        totalValue: totalValue._sum.value || 0,
        byStatus: byStatus.reduce((acc, item) => {
          acc[item.status] = item._count;
          return acc;
        }, {} as any),
        byType: byType.reduce((acc, item) => {
          acc[item.type] = item._count;
          return acc;
        }, {} as any),
      };

      // Cache result
      await this.redisService.setex(cacheKey, 600, JSON.stringify(result));

      return result;
    } catch (error) {
      this.logger.error('Error getting contract statistics:', error);
      throw error;
    }
  }

  async getContractTimeline(contractId: string, tenantId: string): Promise<any[]> {
    try {
      const auditLogs = await this.auditService.getAuditLogs({
        entityType: 'contract',
        entityId: contractId,
        tenantId,
        limit: 50,
      });

      return auditLogs.map(log => ({
        id: log.id,
        action: log.action,
        timestamp: log.createdAt,
        userId: log.userId,
        changes: log.changes,
      }));
    } catch (error) {
      this.logger.error('Error getting contract timeline:', error);
      throw error;
    }
  }

  async exportContracts(params: ContractListParams, format: 'excel' | 'csv'): Promise<Buffer> {
    try {
      const contracts = await this.listContracts({ ...params, limit: 10000 });

      const data = contracts.items.map((contract: any) => ({
        'رقم العقد': contract.contractNumber,
        'العميل': contract.customer.name,
        'النوع': this.getTypeLabel(contract.type),
        'العنوان': contract.title,
        'القيمة': contract.value,
        'تاريخ البداية': new Date(contract.startDate).toLocaleDateString('ar-SA'),
        'تاريخ النهاية': new Date(contract.endDate).toLocaleDateString('ar-SA'),
        'الحالة': this.getStatusLabel(contract.status),
        'شروط الدفع': this.getPaymentTermLabel(contract.paymentTerm),
      }));

      if (format === 'excel') {
        const worksheet = xlsx.utils.json_to_sheet(data);
        const workbook = xlsx.utils.book_new();
        xlsx.utils.book_append_sheet(workbook, worksheet, 'العقود');
        return xlsx.write(workbook, { type: 'buffer', bookType: 'xlsx' });
      } else {
        const csvStringifier = createObjectCsvStringifier({
          header: Object.keys(data[0]).map(key => ({ id: key, title: key })),
        });
        const records = csvStringifier.stringifyRecords(data);
        const header = csvStringifier.getHeaderString();
        return Buffer.from(`\uFEFF${header}${records}`, 'utf8');
      }
    } catch (error) {
      this.logger.error('Error exporting contracts:', error);
      throw error;
    }
  }

  // Contract Items Management
  async addContractItem(contractId: string, item: any, tenantId: string): Promise<ContractItem> {
    try {
      const contract = await this.getContractById(contractId, tenantId);
      if (!contract) {
        throw new ApiError(404, 'العقد غير موجود');
      }

      const contractItem = await prisma.contractItem.create({
        data: {
          contractId,
          ...item,
        },
      });

      // Update contract value
      const newValue = contract.value + (item.quantity * item.unitPrice);
      await prisma.contract.update({
        where: { id: contractId },
        data: { value: newValue },
      });

      // Invalidate cache
      await this.invalidateContractCache(tenantId, contractId);

      return contractItem;
    } catch (error) {
      this.logger.error('Error adding contract item:', error);
      throw error;
    }
  }

  async updateContractItem(itemId: string, data: any, tenantId: string): Promise<ContractItem> {
    try {
      const item = await prisma.contractItem.findUnique({
        where: { id: itemId },
        include: { contract: true },
      });

      if (!item || item.contract.tenantId !== tenantId) {
        throw new ApiError(404, 'عنصر العقد غير موجود');
      }

      const updatedItem = await prisma.contractItem.update({
        where: { id: itemId },
        data,
      });

      // Recalculate contract value
      const items = await prisma.contractItem.findMany({
        where: { contractId: item.contractId, isDeleted: false },
      });

      const newValue = items.reduce((sum, i) => sum + (i.quantity * i.unitPrice), 0);
      await prisma.contract.update({
        where: { id: item.contractId },
        data: { value: newValue },
      });

      // Invalidate cache
      await this.invalidateContractCache(tenantId, item.contractId);

      return updatedItem;
    } catch (error) {
      this.logger.error('Error updating contract item:', error);
      throw error;
    }
  }

  async deleteContractItem(itemId: string, tenantId: string): Promise<void> {
    try {
      const item = await prisma.contractItem.findUnique({
        where: { id: itemId },
        include: { contract: true },
      });

      if (!item || item.contract.tenantId !== tenantId) {
        throw new ApiError(404, 'عنصر العقد غير موجود');
      }

      await prisma.contractItem.update({
        where: { id: itemId },
        data: { isDeleted: true },
      });

      // Recalculate contract value
      const items = await prisma.contractItem.findMany({
        where: { contractId: item.contractId, isDeleted: false },
      });

      const newValue = items.reduce((sum, i) => sum + (i.quantity * i.unitPrice), 0);
      await prisma.contract.update({
        where: { id: item.contractId },
        data: { value: newValue },
      });

      // Invalidate cache
      await this.invalidateContractCache(tenantId, item.contractId);
    } catch (error) {
      this.logger.error('Error deleting contract item:', error);
      throw error;
    }
  }

  // Contract Attachments Management
  async uploadAttachment(contractId: string, file: any, userId: string, tenantId: string): Promise<ContractAttachment> {
    try {
      const contract = await this.getContractById(contractId, tenantId);
      if (!contract) {
        throw new ApiError(404, 'العقد غير موجود');
      }

      // Upload file
      const uploadResult = await this.fileUploadService.uploadFile(file, {
        folder: `contracts/${contractId}`,
        allowedTypes: ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png'],
        maxSize: 10 * 1024 * 1024, // 10MB
      });

      // Create attachment record
      const attachment = await prisma.contractAttachment.create({
        data: {
          contractId,
          fileName: file.originalname,
          fileUrl: uploadResult.url,
          fileSize: file.size,
          mimeType: file.mimetype,
          uploadedBy: userId,
        },
      });

      // Invalidate cache
      await this.invalidateContractCache(tenantId, contractId);

      this.logger.info(`Attachment uploaded for contract: ${contractId}`);
      return attachment;
    } catch (error) {
      this.logger.error('Error uploading attachment:', error);
      throw error;
    }
  }

  async getAttachments(contractId: string, tenantId: string): Promise<ContractAttachment[]> {
    try {
      const contract = await this.getContractById(contractId, tenantId);
      if (!contract) {
        throw new ApiError(404, 'العقد غير موجود');
      }

      return await prisma.contractAttachment.findMany({
        where: { contractId, isDeleted: false },
        orderBy: { createdAt: 'desc' },
      });
    } catch (error) {
      this.logger.error('Error getting attachments:', error);
      throw error;
    }
  }

  async deleteAttachment(attachmentId: string, tenantId: string): Promise<void> {
    try {
      const attachment = await prisma.contractAttachment.findUnique({
        where: { id: attachmentId },
        include: { contract: true },
      });

      if (!attachment || attachment.contract.tenantId !== tenantId) {
        throw new ApiError(404, 'المرفق غير موجود');
      }

      // Delete file from storage
      await this.fileUploadService.deleteFile(attachment.fileUrl);

      // Delete record
      await prisma.contractAttachment.update({
        where: { id: attachmentId },
        data: { isDeleted: true },
      });

      // Invalidate cache
      await this.invalidateContractCache(tenantId, attachment.contractId);

      this.logger.info(`Attachment deleted: ${attachmentId}`);
    } catch (error) {
      this.logger.error('Error deleting attachment:', error);
      throw error;
    }
  }

  // Auto-renewal
  async processAutoRenewals(): Promise<void> {
    try {
      const contracts = await prisma.contract.findMany({
        where: {
          status: 'active',
          autoRenew: true,
          endDate: {
            lte: new Date(Date.now() + 30 * 24 * 60 * 60 * 1000), // 30 days
          },
        },
        include: {
          customer: true,
        },
      });

      for (const contract of contracts) {
        try {
          const renewalPeriod = contract.renewalPeriod || 'yearly';
          const endDate = this.calculateEndDate(contract.endDate, renewalPeriod);

          await this.renewContract(
            contract.id,
            {
              endDate,
              description: `تجديد تلقائي - ${contract.description}`,
            },
            'system',
            contract.tenantId
          );

          // Send notification
          await this.notificationService.sendNotification({
            userId: contract.customer.id,
            type: 'contract_auto_renewed',
            title: 'تجديد تلقائي للعقد',
            message: `تم تجديد العقد رقم ${contract.contractNumber} تلقائياً`,
            data: { contractId: contract.id },
          });
        } catch (error) {
          this.logger.error(`Error auto-renewing contract ${contract.id}:`, error);
        }
      }
    } catch (error) {
      this.logger.error('Error processing auto-renewals:', error);
    }
  }

  // Helper methods
  private async invalidateContractCache(tenantId: string, contractId?: string): Promise<void> {
    const patterns = [
      `contract-list:${tenantId}:*`,
      `contract-stats:${tenantId}:*`,
    ];

    if (contractId) {
      patterns.push(`contract:${tenantId}:${contractId}`);
    }

    for (const pattern of patterns) {
      const keys = await this.redisService.keys(pattern);
      if (keys.length > 0) {
        await this.redisService.del(...keys);
      }
    }
  }

  private generateContractNumber(type: string): string {
    const prefix = this.getTypePrefix(type);
    const timestamp = Date.now().toString().slice(-6);
    const random = Math.floor(Math.random() * 1000).toString().padStart(3, '0');
    return `${prefix}-${timestamp}-${random}`;
  }

  private getTypePrefix(type: string): string {
    const prefixes: Record<string, string> = {
      service: 'SRV',
      sales: 'SLS',
      employment: 'EMP',
      partnership: 'PRT',
      lease: 'LSE',
      maintenance: 'MNT',
      consulting: 'CNS',
      other: 'OTH',
    };
    return prefixes[type] || 'CTR';
  }

  private getTypeLabel(type: string): string {
    const labels: Record<string, string> = {
      service: 'خدمات',
      sales: 'مبيعات',
      employment: 'توظيف',
      partnership: 'شراكة',
      lease: 'إيجار',
      maintenance: 'صيانة',
      consulting: 'استشارات',
      other: 'أخرى',
    };
    return labels[type] || type;
  }

  private getStatusLabel(status: string): string {
    const labels: Record<string, string> = {
      draft: 'مسودة',
      pending: 'قيد المراجعة',
      active: 'نشط',
      expired: 'منتهي',
      cancelled: 'ملغي',
      completed: 'مكتمل',
    };
    return labels[status] || status;
  }

  private getPaymentTermLabel(term: string): string {
    const labels: Record<string, string> = {
      monthly: 'شهري',
      quarterly: 'ربع سنوي',
      yearly: 'سنوي',
      'one-time': 'دفعة واحدة',
      custom: 'مخصص',
    };
    return labels[term] || term;
  }

  private calculateEndDate(startDate: Date, renewalPeriod: string): Date {
    const date = new Date(startDate);
    switch (renewalPeriod) {
      case 'monthly':
        date.setMonth(date.getMonth() + 1);
        break;
      case 'quarterly':
        date.setMonth(date.getMonth() + 3);
        break;
      case 'yearly':
        date.setFullYear(date.getFullYear() + 1);
        break;
      default:
        date.setFullYear(date.getFullYear() + 1);
    }
    return date;
  }

  // Scheduled tasks
  @Cron('0 0 * * *') // Daily at midnight
  async checkExpiredContracts(): Promise<void> {
    try {
      const expiredContracts = await prisma.contract.findMany({
        where: {
          status: 'active',
          endDate: {
            lt: new Date(),
          },
        },
        include: {
          customer: true,
        },
      });

      for (const contract of expiredContracts) {
        await prisma.contract.update({
          where: { id: contract.id },
          data: { status: 'expired' },
        });

        // Send notification
        await this.notificationService.sendNotification({
          userId: contract.customer.id,
          type: 'contract_expired',
          title: 'انتهى العقد',
          message: `انتهى العقد رقم ${contract.contractNumber}`,
          data: { contractId: contract.id },
        });

        // Invalidate cache
        await this.invalidateContractCache(contract.tenantId, contract.id);
      }

      this.logger.info(`Processed ${expiredContracts.length} expired contracts`);
    } catch (error) {
      this.logger.error('Error checking expired contracts:', error);
    }
  }

  @Cron('0 9 * * *') // Daily at 9 AM
  async sendRenewalReminders(): Promise<void> {
    try {
      const contractsNearExpiry = await prisma.contract.findMany({
        where: {
          status: 'active',
          autoRenew: false,
          endDate: {
            gte: new Date(),
            lte: new Date(Date.now() + 30 * 24 * 60 * 60 * 1000), // 30 days
          },
        },
        include: {
          customer: true,
        },
      });

      for (const contract of contractsNearExpiry) {
        const daysUntilExpiry = Math.ceil(
          (contract.endDate.getTime() - Date.now()) / (24 * 60 * 60 * 1000)
        );

        if ([30, 15, 7, 3, 1].includes(daysUntilExpiry)) {
          await this.notificationService.sendNotification({
            userId: contract.customer.id,
            type: 'contract_expiry_reminder',
            title: 'تذكير بانتهاء العقد',
            message: `العقد رقم ${contract.contractNumber} سينتهي خلال ${daysUntilExpiry} يوم`,
            data: { contractId: contract.id, daysUntilExpiry },
          });
        }
      }

      this.logger.info(`Sent renewal reminders for ${contractsNearExpiry.length} contracts`);
    } catch (error) {
      this.logger.error('Error sending renewal reminders:', error);
    }
  }
}