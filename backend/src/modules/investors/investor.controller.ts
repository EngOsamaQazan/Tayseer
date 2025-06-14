import { Request, Response } from 'express';
import { InvestorService } from './investor.service';
import { logger } from '../../config/logger';

export class InvestorController {
  private investorService: InvestorService;

  constructor() {
    this.investorService = new InvestorService();
  }

  async getAllInvestors(req: Request, res: Response) {
    try {
      const investors = await this.investorService.getAllInvestors();
      res.json({
        success: true,
        data: investors,
        message: 'تم جلب قائمة المستثمرين بنجاح'
      });
    } catch (error) {
      logger.error('خطأ في جلب قائمة المستثمرين:', error);
      res.status(500).json({
        success: false,
        message: 'خطأ داخلي في الخادم'
      });
    }
  }

  async getInvestorById(req: Request, res: Response) {
    try {
      const { id } = req.params;
      const investor = await this.investorService.getInvestorById(parseInt(id));
      
      if (!investor) {
        return res.status(404).json({
          success: false,
          message: 'المستثمر غير موجود'
        });
      }

      res.json({
        success: true,
        data: investor,
        message: 'تم جلب بيانات المستثمر بنجاح'
      });
    } catch (error) {
      logger.error('خطأ في جلب بيانات المستثمر:', error);
      res.status(500).json({
        success: false,
        message: 'خطأ داخلي في الخادم'
      });
    }
  }

  async createInvestor(req: Request, res: Response) {
    try {
      const investor = await this.investorService.createInvestor(req.body);
      res.status(201).json({
        success: true,
        data: investor,
        message: 'تم إنشاء المستثمر بنجاح'
      });
    } catch (error) {
      logger.error('خطأ في إنشاء المستثمر:', error);
      res.status(500).json({
        success: false,
        message: 'خطأ داخلي في الخادم'
      });
    }
  }

  async updateInvestor(req: Request, res: Response) {
    try {
      const { id } = req.params;
      const investor = await this.investorService.updateInvestor(parseInt(id), req.body);
      
      if (!investor) {
        return res.status(404).json({
          success: false,
          message: 'المستثمر غير موجود'
        });
      }

      res.json({
        success: true,
        data: investor,
        message: 'تم تحديث بيانات المستثمر بنجاح'
      });
    } catch (error) {
      logger.error('خطأ في تحديث بيانات المستثمر:', error);
      res.status(500).json({
        success: false,
        message: 'خطأ داخلي في الخادم'
      });
    }
  }

  async deleteInvestor(req: Request, res: Response) {
    try {
      const { id } = req.params;
      await this.investorService.deleteInvestor(parseInt(id));
      
      res.json({
        success: true,
        message: 'تم حذف المستثمر بنجاح'
      });
    } catch (error) {
      logger.error('خطأ في حذف المستثمر:', error);
      res.status(500).json({
        success: false,
        message: 'خطأ داخلي في الخادم'
      });
    }
  }

  async getInvestorInvestments(req: Request, res: Response) {
    try {
      const { id } = req.params;
      const investments = await this.investorService.getInvestorInvestments(parseInt(id));
      
      res.json({
        success: true,
        data: investments,
        message: 'تم جلب قائمة الاستثمارات بنجاح'
      });
    } catch (error) {
      logger.error('خطأ في جلب قائمة الاستثمارات:', error);
      res.status(500).json({
        success: false,
        message: 'خطأ داخلي في الخادم'
      });
    }
  }

  async createInvestment(req: Request, res: Response) {
    try {
      const { id } = req.params;
      const investment = await this.investorService.createInvestment(parseInt(id), req.body);
      
      res.status(201).json({
        success: true,
        data: investment,
        message: 'تم إنشاء الاستثمار بنجاح'
      });
    } catch (error) {
      logger.error('خطأ في إنشاء الاستثمار:', error);
      res.status(500).json({
        success: false,
        message: 'خطأ داخلي في الخادم'
      });
    }
  }

  async getInvestorPortfolio(req: Request, res: Response) {
    try {
      const { id } = req.params;
      const portfolio = await this.investorService.getInvestorPortfolio(parseInt(id));
      
      res.json({
        success: true,
        data: portfolio,
        message: 'تم جلب محفظة الاستثمار بنجاح'
      });
    } catch (error) {
      logger.error('خطأ في جلب محفظة الاستثمار:', error);
      res.status(500).json({
        success: false,
        message: 'خطأ داخلي في الخادم'
      });
    }
  }

  async getInvestorReturns(req: Request, res: Response) {
    try {
      const { id } = req.params;
      const returns = await this.investorService.getInvestorReturns(parseInt(id));
      
      res.json({
        success: true,
        data: returns,
        message: 'تم جلب عوائد الاستثمار بنجاح'
      });
    } catch (error) {
      logger.error('خطأ في جلب عوائد الاستثمار:', error);
      res.status(500).json({
        success: false,
        message: 'خطأ داخلي في الخادم'
      });
    }
  }
}