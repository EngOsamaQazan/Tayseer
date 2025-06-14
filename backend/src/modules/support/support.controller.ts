import { Request, Response } from 'express';
import { SupportService } from './support.service';
import { logger } from '../../utils/logger';

export class SupportController {
  private supportService: SupportService;

  constructor() {
    this.supportService = new SupportService();
  }

  // Ticket management
  getTickets = async (req: Request, res: Response): Promise<void> => {
    try {
      const tickets = await this.supportService.getTickets(req.query);
      res.json({
        success: true,
        data: tickets,
        message: 'تم جلب التذاكر بنجاح'
      });
    } catch (error) {
      logger.error('خطأ في جلب التذاكر:', error);
      res.status(500).json({
        success: false,
        message: 'خطأ في جلب التذاكر',
        error: error instanceof Error ? error.message : 'خطأ غير معروف'
      });
    }
  };

  createTicket = async (req: Request, res: Response): Promise<void> => {
    try {
      const ticket = await this.supportService.createTicket(req.body);
      res.status(201).json({
        success: true,
        data: ticket,
        message: 'تم إنشاء التذكرة بنجاح'
      });
    } catch (error) {
      logger.error('خطأ في إنشاء التذكرة:', error);
      res.status(500).json({
        success: false,
        message: 'خطأ في إنشاء التذكرة',
        error: error instanceof Error ? error.message : 'خطأ غير معروف'
      });
    }
  };

  getTicketById = async (req: Request, res: Response): Promise<void> => {
    try {
      const ticket = await this.supportService.getTicketById(req.params.id);
      if (!ticket) {
        res.status(404).json({
          success: false,
          message: 'التذكرة غير موجودة'
        });
        return;
      }
      res.json({
        success: true,
        data: ticket,
        message: 'تم جلب التذكرة بنجاح'
      });
    } catch (error) {
      logger.error('خطأ في جلب التذكرة:', error);
      res.status(500).json({
        success: false,
        message: 'خطأ في جلب التذكرة',
        error: error instanceof Error ? error.message : 'خطأ غير معروف'
      });
    }
  };

  updateTicket = async (req: Request, res: Response): Promise<void> => {
    try {
      const ticket = await this.supportService.updateTicket(req.params.id, req.body);
      if (!ticket) {
        res.status(404).json({
          success: false,
          message: 'التذكرة غير موجودة'
        });
        return;
      }
      res.json({
        success: true,
        data: ticket,
        message: 'تم تحديث التذكرة بنجاح'
      });
    } catch (error) {
      logger.error('خطأ في تحديث التذكرة:', error);
      res.status(500).json({
        success: false,
        message: 'خطأ في تحديث التذكرة',
        error: error instanceof Error ? error.message : 'خطأ غير معروف'
      });
    }
  };

  deleteTicket = async (req: Request, res: Response): Promise<void> => {
    try {
      const success = await this.supportService.deleteTicket(req.params.id);
      if (!success) {
        res.status(404).json({
          success: false,
          message: 'التذكرة غير موجودة'
        });
        return;
      }
      res.json({
        success: true,
        message: 'تم حذف التذكرة بنجاح'
      });
    } catch (error) {
      logger.error('خطأ في حذف التذكرة:', error);
      res.status(500).json({
        success: false,
        message: 'خطأ في حذف التذكرة',
        error: error instanceof Error ? error.message : 'خطأ غير معروف'
      });
    }
  };

  // Ticket status and assignment
  updateTicketStatus = async (req: Request, res: Response): Promise<void> => {
    try {
      const ticket = await this.supportService.updateTicketStatus(req.params.id, req.body.status);
      if (!ticket) {
        res.status(404).json({
          success: false,
          message: 'التذكرة غير موجودة'
        });
        return;
      }
      res.json({
        success: true,
        data: ticket,
        message: 'تم تحديث حالة التذكرة بنجاح'
      });
    } catch (error) {
      logger.error('خطأ في تحديث حالة التذكرة:', error);
      res.status(500).json({
        success: false,
        message: 'خطأ في تحديث حالة التذكرة',
        error: error instanceof Error ? error.message : 'خطأ غير معروف'
      });
    }
  };

  assignTicket = async (req: Request, res: Response): Promise<void> => {
    try {
      const ticket = await this.supportService.assignTicket(req.params.id, req.body.assignedTo);
      if (!ticket) {
        res.status(404).json({
          success: false,
          message: 'التذكرة غير موجودة'
        });
        return;
      }
      res.json({
        success: true,
        data: ticket,
        message: 'تم تعيين التذكرة بنجاح'
      });
    } catch (error) {
      logger.error('خطأ في تعيين التذكرة:', error);
      res.status(500).json({
        success: false,
        message: 'خطأ في تعيين التذكرة',
        error: error instanceof Error ? error.message : 'خطأ غير معروف'
      });
    }
  };

  updateTicketPriority = async (req: Request, res: Response): Promise<void> => {
    try {
      const ticket = await this.supportService.updateTicketPriority(req.params.id, req.body.priority);
      if (!ticket) {
        res.status(404).json({
          success: false,
          message: 'التذكرة غير موجودة'
        });
        return;
      }
      res.json({
        success: true,
        data: ticket,
        message: 'تم تحديث أولوية التذكرة بنجاح'
      });
    } catch (error) {
      logger.error('خطأ في تحديث أولوية التذكرة:', error);
      res.status(500).json({
        success: false,
        message: 'خطأ في تحديث أولوية التذكرة',
        error: error instanceof Error ? error.message : 'خطأ غير معروف'
      });
    }
  };

  // Ticket responses
  addTicketResponse = async (req: Request, res: Response): Promise<void> => {
    try {
      const response = await this.supportService.addTicketResponse(req.params.id, req.body);
      res.status(201).json({
        success: true,
        data: response,
        message: 'تم إضافة الرد بنجاح'
      });
    } catch (error) {
      logger.error('خطأ في إضافة الرد:', error);
      res.status(500).json({
        success: false,
        message: 'خطأ في إضافة الرد',
        error: error instanceof Error ? error.message : 'خطأ غير معروف'
      });
    }
  };

  getTicketResponses = async (req: Request, res: Response): Promise<void> => {
    try {
      const responses = await this.supportService.getTicketResponses(req.params.id);
      res.json({
        success: true,
        data: responses,
        message: 'تم جلب الردود بنجاح'
      });
    } catch (error) {
      logger.error('خطأ في جلب الردود:', error);
      res.status(500).json({
        success: false,
        message: 'خطأ في جلب الردود',
        error: error instanceof Error ? error.message : 'خطأ غير معروف'
      });
    }
  };

  // Knowledge base
  getKnowledgeBaseArticles = async (req: Request, res: Response): Promise<void> => {
    try {
      const articles = await this.supportService.getKnowledgeBaseArticles(req.query);
      res.json({
        success: true,
        data: articles,
        message: 'تم جلب مقالات قاعدة المعرفة بنجاح'
      });
    } catch (error) {
      logger.error('خطأ في جلب مقالات قاعدة المعرفة:', error);
      res.status(500).json({
        success: false,
        message: 'خطأ في جلب مقالات قاعدة المعرفة',
        error: error instanceof Error ? error.message : 'خطأ غير معروف'
      });
    }
  };

  createKnowledgeBaseArticle = async (req: Request, res: Response): Promise<void> => {
    try {
      const article = await this.supportService.createKnowledgeBaseArticle(req.body);
      res.status(201).json({
        success: true,
        data: article,
        message: 'تم إنشاء المقال بنجاح'
      });
    } catch (error) {
      logger.error('خطأ في إنشاء المقال:', error);
      res.status(500).json({
        success: false,
        message: 'خطأ في إنشاء المقال',
        error: error instanceof Error ? error.message : 'خطأ غير معروف'
      });
    }
  };

  getKnowledgeBaseArticleById = async (req: Request, res: Response): Promise<void> => {
    try {
      const article = await this.supportService.getKnowledgeBaseArticleById(req.params.id);
      if (!article) {
        res.status(404).json({
          success: false,
          message: 'المقال غير موجود'
        });
        return;
      }
      res.json({
        success: true,
        data: article,
        message: 'تم جلب المقال بنجاح'
      });
    } catch (error) {
      logger.error('خطأ في جلب المقال:', error);
      res.status(500).json({
        success: false,
        message: 'خطأ في جلب المقال',
        error: error instanceof Error ? error.message : 'خطأ غير معروف'
      });
    }
  };

  updateKnowledgeBaseArticle = async (req: Request, res: Response): Promise<void> => {
    try {
      const article = await this.supportService.updateKnowledgeBaseArticle(req.params.id, req.body);
      if (!article) {
        res.status(404).json({
          success: false,
          message: 'المقال غير موجود'
        });
        return;
      }
      res.json({
        success: true,
        data: article,
        message: 'تم تحديث المقال بنجاح'
      });
    } catch (error) {
      logger.error('خطأ في تحديث المقال:', error);
      res.status(500).json({
        success: false,
        message: 'خطأ في تحديث المقال',
        error: error instanceof Error ? error.message : 'خطأ غير معروف'
      });
    }
  };

  deleteKnowledgeBaseArticle = async (req: Request, res: Response): Promise<void> => {
    try {
      const success = await this.supportService.deleteKnowledgeBaseArticle(req.params.id);
      if (!success) {
        res.status(404).json({
          success: false,
          message: 'المقال غير موجود'
        });
        return;
      }
      res.json({
        success: true,
        message: 'تم حذف المقال بنجاح'
      });
    } catch (error) {
      logger.error('خطأ في حذف المقال:', error);
      res.status(500).json({
        success: false,
        message: 'خطأ في حذف المقال',
        error: error instanceof Error ? error.message : 'خطأ غير معروف'
      });
    }
  };

  // FAQ management
  getFAQs = async (req: Request, res: Response): Promise<void> => {
    try {
      const faqs = await this.supportService.getFAQs(req.query);
      res.json({
        success: true,
        data: faqs,
        message: 'تم جلب الأسئلة الشائعة بنجاح'
      });
    } catch (error) {
      logger.error('خطأ في جلب الأسئلة الشائعة:', error);
      res.status(500).json({
        success: false,
        message: 'خطأ في جلب الأسئلة الشائعة',
        error: error instanceof Error ? error.message : 'خطأ غير معروف'
      });
    }
  };

  createFAQ = async (req: Request, res: Response): Promise<void> => {
    try {
      const faq = await this.supportService.createFAQ(req.body);
      res.status(201).json({
        success: true,
        data: faq,
        message: 'تم إنشاء السؤال الشائع بنجاح'
      });
    } catch (error) {
      logger.error('خطأ في إنشاء السؤال الشائع:', error);
      res.status(500).json({
        success: false,
        message: 'خطأ في إنشاء السؤال الشائع',
        error: error instanceof Error ? error.message : 'خطأ غير معروف'
      });
    }
  };

  getFAQById = async (req: Request, res: Response): Promise<void> => {
    try {
      const faq = await this.supportService.getFAQById(req.params.id);
      if (!faq) {
        res.status(404).json({
          success: false,
          message: 'السؤال الشائع غير موجود'
        });
        return;
      }
      res.json({
        success: true,
        data: faq,
        message: 'تم جلب السؤال الشائع بنجاح'
      });
    } catch (error) {
      logger.error('خطأ في جلب السؤال الشائع:', error);
      res.status(500).json({
        success: false,
        message: 'خطأ في جلب السؤال الشائع',
        error: error instanceof Error ? error.message : 'خطأ غير معروف'
      });
    }
  };

  updateFAQ = async (req: Request, res: Response): Promise<void> => {
    try {
      const faq = await this.supportService.updateFAQ(req.params.id, req.body);
      if (!faq) {
        res.status(404).json({
          success: false,
          message: 'السؤال الشائع غير موجود'
        });
        return;
      }
      res.json({
        success: true,
        data: faq,
        message: 'تم تحديث السؤال الشائع بنجاح'
      });
    } catch (error) {
      logger.error('خطأ في تحديث السؤال الشائع:', error);
      res.status(500).json({
        success: false,
        message: 'خطأ في تحديث السؤال الشائع',
        error: error instanceof Error ? error.message : 'خطأ غير معروف'
      });
    }
  };

  deleteFAQ = async (req: Request, res: Response): Promise<void> => {
    try {
      const success = await this.supportService.deleteFAQ(req.params.id);
      if (!success) {
        res.status(404).json({
          success: false,
          message: 'السؤال الشائع غير موجود'
        });
        return;
      }
      res.json({
        success: true,
        message: 'تم حذف السؤال الشائع بنجاح'
      });
    } catch (error) {
      logger.error('خطأ في حذف السؤال الشائع:', error);
      res.status(500).json({
        success: false,
        message: 'خطأ في حذف السؤال الشائع',
        error: error instanceof Error ? error.message : 'خطأ غير معروف'
      });
    }
  };

  // Analytics and reports
  getTicketAnalytics = async (req: Request, res: Response): Promise<void> => {
    try {
      const analytics = await this.supportService.getTicketAnalytics(req.query);
      res.json({
        success: true,
        data: analytics,
        message: 'تم جلب إحصائيات التذاكر بنجاح'
      });
    } catch (error) {
      logger.error('خطأ في جلب إحصائيات التذاكر:', error);
      res.status(500).json({
        success: false,
        message: 'خطأ في جلب إحصائيات التذاكر',
        error: error instanceof Error ? error.message : 'خطأ غير معروف'
      });
    }
  };

  getSupportPerformance = async (req: Request, res: Response): Promise<void> => {
    try {
      const performance = await this.supportService.getSupportPerformance(req.query);
      res.json({
        success: true,
        data: performance,
        message: 'تم جلب تقرير الأداء بنجاح'
      });
    } catch (error) {
      logger.error('خطأ في جلب تقرير الأداء:', error);
      res.status(500).json({
        success: false,
        message: 'خطأ في جلب تقرير الأداء',
        error: error instanceof Error ? error.message : 'خطأ غير معروف'
      });
    }
  };

  getTicketReports = async (req: Request, res: Response): Promise<void> => {
    try {
      const reports = await this.supportService.getTicketReports(req.query);
      res.json({
        success: true,
        data: reports,
        message: 'تم جلب تقارير التذاكر بنجاح'
      });
    } catch (error) {
      logger.error('خطأ في جلب تقارير التذاكر:', error);
      res.status(500).json({
        success: false,
        message: 'خطأ في جلب تقارير التذاكر',
        error: error instanceof Error ? error.message : 'خطأ غير معروف'
      });
    }
  };

  getCustomerSatisfactionReports = async (req: Request, res: Response): Promise<void> => {
    try {
      const reports = await this.supportService.getCustomerSatisfactionReports(req.query);
      res.json({
        success: true,
        data: reports,
        message: 'تم جلب تقارير رضا العملاء بنجاح'
      });
    } catch (error) {
      logger.error('خطأ في جلب تقارير رضا العملاء:', error);
      res.status(500).json({
        success: false,
        message: 'خطأ في جلب تقارير رضا العملاء',
        error: error instanceof Error ? error.message : 'خطأ غير معروف'
      });
    }
  };

  // Customer feedback
  addCustomerFeedback = async (req: Request, res: Response): Promise<void> => {
    try {
      const feedback = await this.supportService.addCustomerFeedback(req.params.id, req.body);
      res.status(201).json({
        success: true,
        data: feedback,
        message: 'تم إضافة تقييم العميل بنجاح'
      });
    } catch (error) {
      logger.error('خطأ في إضافة تقييم العميل:', error);
      res.status(500).json({
        success: false,
        message: 'خطأ في إضافة تقييم العميل',
        error: error instanceof Error ? error.message : 'خطأ غير معروف'
      });
    }
  };

  getCustomerFeedback = async (req: Request, res: Response): Promise<void> => {
    try {
      const feedback = await this.supportService.getCustomerFeedback(req.query);
      res.json({
        success: true,
        data: feedback,
        message: 'تم جلب تقييمات العملاء بنجاح'
      });
    } catch (error) {
      logger.error('خطأ في جلب تقييمات العملاء:', error);
      res.status(500).json({
        success: false,
        message: 'خطأ في جلب تقييمات العملاء',
        error: error instanceof Error ? error.message : 'خطأ غير معروف'
      });
    }
  };

  // Support team management
  getSupportTeam = async (req: Request, res: Response): Promise<void> => {
    try {
      const team = await this.supportService.getSupportTeam(req.query);
      res.json({
        success: true,
        data: team,
        message: 'تم جلب فريق الدعم بنجاح'
      });
    } catch (error) {
      logger.error('خطأ في جلب فريق الدعم:', error);
      res.status(500).json({
        success: false,
        message: 'خطأ في جلب فريق الدعم',
        error: error instanceof Error ? error.message : 'خطأ غير معروف'
      });
    }
  };

  getTeamMemberPerformance = async (req: Request, res: Response): Promise<void> => {
    try {
      const performance = await this.supportService.getTeamMemberPerformance(req.params.id, req.query);
      res.json({
        success: true,
        data: performance,
        message: 'تم جلب تقرير أداء عضو الفريق بنجاح'
      });
    } catch (error) {
      logger.error('خطأ في جلب تقرير أداء عضو الفريق:', error);
      res.status(500).json({
        success: false,
        message: 'خطأ في جلب تقرير أداء عضو الفريق',
        error: error instanceof Error ? error.message : 'خطأ غير معروف'
      });
    }
  };

  // Escalation management
  escalateTicket = async (req: Request, res: Response): Promise<void> => {
    try {
      const ticket = await this.supportService.escalateTicket(req.params.id, req.body);
      if (!ticket) {
        res.status(404).json({
          success: false,
          message: 'التذكرة غير موجودة'
        });
        return;
      }
      res.json({
        success: true,
        data: ticket,
        message: 'تم تصعيد التذكرة بنجاح'
      });
    } catch (error) {
      logger.error('خطأ في تصعيد التذكرة:', error);
      res.status(500).json({
        success: false,
        message: 'خطأ في تصعيد التذكرة',
        error: error instanceof Error ? error.message : 'خطأ غير معروف'
      });
    }
  };

  getEscalatedTickets = async (req: Request, res: Response): Promise<void> => {
    try {
      const tickets = await this.supportService.getEscalatedTickets(req.query);
      res.json({
        success: true,
        data: tickets,
        message: 'تم جلب التذاكر المصعدة بنجاح'
      });
    } catch (error) {
      logger.error('خطأ في جلب التذاكر المصعدة:', error);
      res.status(500).json({
        success: false,
        message: 'خطأ في جلب التذاكر المصعدة',
        error: error instanceof Error ? error.message : 'خطأ غير معروف'
      });
    }
  };
}