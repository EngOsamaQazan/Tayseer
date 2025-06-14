import { Request, Response } from 'express';
import { reportService } from './report.service';
import logger from '../../shared/utils/logger';
import { ApiResponse } from '../../shared/interfaces/api.interface';

class ReportController {
  // تقرير مالي
  async getFinancialReport(req: Request, res: Response) {
    try {
      const { startDate, endDate } = req.query;
      const tenantId = req.user?.tenantId;

      const report = await reportService.generateFinancialReport({
        tenantId,
        startDate: startDate as string,
        endDate: endDate as string
      });

      const response: ApiResponse = {
        success: true,
        message: 'تم إنشاء التقرير المالي بنجاح',
        data: report
      };

      res.json(response);
    } catch (error) {
      logger.error('خطأ في إنشاء التقرير المالي:', error);
      res.status(500).json({
        success: false,
        message: 'حدث خطأ في إنشاء التقرير المالي'
      });
    }
  }

  // تقرير المبيعات
  async getSalesReport(req: Request, res: Response) {
    try {
      const { startDate, endDate, productId } = req.query;
      const tenantId = req.user?.tenantId;

      const report = await reportService.generateSalesReport({
        tenantId,
        startDate: startDate as string,
        endDate: endDate as string,
        productId: productId as string
      });

      const response: ApiResponse = {
        success: true,
        message: 'تم إنشاء تقرير المبيعات بنجاح',
        data: report
      };

      res.json(response);
    } catch (error) {
      logger.error('خطأ في إنشاء تقرير المبيعات:', error);
      res.status(500).json({
        success: false,
        message: 'حدث خطأ في إنشاء تقرير المبيعات'
      });
    }
  }

  // تقرير المخزون
  async getInventoryReport(req: Request, res: Response) {
    try {
      const tenantId = req.user?.tenantId;
      const report = await reportService.generateInventoryReport(tenantId);

      const response: ApiResponse = {
        success: true,
        message: 'تم إنشاء تقرير المخزون بنجاح',
        data: report
      };

      res.json(response);
    } catch (error) {
      logger.error('خطأ في إنشاء تقرير المخزون:', error);
      res.status(500).json({
        success: false,
        message: 'حدث خطأ في إنشاء تقرير المخزون'
      });
    }
  }

  // تقرير العملاء
  async getCustomerReport(req: Request, res: Response) {
    try {
      const { startDate, endDate } = req.query;
      const tenantId = req.user?.tenantId;

      const report = await reportService.generateCustomerReport({
        tenantId,
        startDate: startDate as string,
        endDate: endDate as string
      });

      const response: ApiResponse = {
        success: true,
        message: 'تم إنشاء تقرير العملاء بنجاح',
        data: report
      };

      res.json(response);
    } catch (error) {
      logger.error('خطأ في إنشاء تقرير العملاء:', error);
      res.status(500).json({
        success: false,
        message: 'حدث خطأ في إنشاء تقرير العملاء'
      });
    }
  }

  // تقرير العقود
  async getContractReport(req: Request, res: Response) {
    try {
      const { startDate, endDate, status } = req.query;
      const tenantId = req.user?.tenantId;

      const report = await reportService.generateContractReport({
        tenantId,
        startDate: startDate as string,
        endDate: endDate as string,
        status: status as string
      });

      const response: ApiResponse = {
        success: true,
        message: 'تم إنشاء تقرير العقود بنجاح',
        data: report
      };

      res.json(response);
    } catch (error) {
      logger.error('خطأ في إنشاء تقرير العقود:', error);
      res.status(500).json({
        success: false,
        message: 'حدث خطأ في إنشاء تقرير العقود'
      });
    }
  }

  // تقرير الأداء
  async getPerformanceReport(req: Request, res: Response) {
    try {
      const { startDate, endDate } = req.query;
      const tenantId = req.user?.tenantId;

      const report = await reportService.generatePerformanceReport({
        tenantId,
        startDate: startDate as string,
        endDate: endDate as string
      });

      const response: ApiResponse = {
        success: true,
        message: 'تم إنشاء تقرير الأداء بنجاح',
        data: report
      };

      res.json(response);
    } catch (error) {
      logger.error('خطأ في إنشاء تقرير الأداء:', error);
      res.status(500).json({
        success: false,
        message: 'حدث خطأ في إنشاء تقرير الأداء'
      });
    }
  }

  // تقرير مخصص
  async generateCustomReport(req: Request, res: Response) {
    try {
      const tenantId = req.user?.tenantId;
      const reportConfig = req.body;

      const report = await reportService.generateCustomReport(tenantId, reportConfig);

      const response: ApiResponse = {
        success: true,
        message: 'تم إنشاء التقرير المخصص بنجاح',
        data: report
      };

      res.json(response);
    } catch (error) {
      logger.error('خطأ في إنشاء التقرير المخصص:', error);
      res.status(500).json({
        success: false,
        message: 'حدث خطأ في إنشاء التقرير المخصص'
      });
    }
  }

  // تصدير التقرير
  async exportReport(req: Request, res: Response) {
    try {
      const { type } = req.params;
      const { format, reportData } = req.body;
      const tenantId = req.user?.tenantId;

      const exportedReport = await reportService.exportReport({
        tenantId,
        type,
        format,
        data: reportData
      });

      res.setHeader('Content-Type', 'application/octet-stream');
      res.setHeader('Content-Disposition', `attachment; filename="report.${format}"`);
      res.send(exportedReport);
    } catch (error) {
      logger.error('خطأ في تصدير التقرير:', error);
      res.status(500).json({
        success: false,
        message: 'حدث خطأ في تصدير التقرير'
      });
    }
  }
}

export const reportController = new ReportController();