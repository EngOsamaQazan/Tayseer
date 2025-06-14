import { Request, Response } from 'express';
import { LegalService } from './legal.service';
import { logger } from '../../config/logger';

export class LegalController {
  private legalService: LegalService;

  constructor() {
    this.legalService = new LegalService();
  }

  // Document management methods
  getAllDocuments = async (req: Request, res: Response): Promise<void> => {
    try {
      const { page = 1, limit = 10, type, status } = req.query;
      const filters = { type, status };
      const documents = await this.legalService.getAllDocuments(
        Number(page),
        Number(limit),
        filters
      );
      res.json({
        success: true,
        data: documents,
        message: 'تم جلب الوثائق القانونية بنجاح'
      });
    } catch (error) {
      logger.error('Error fetching legal documents:', error);
      res.status(500).json({
        success: false,
        message: 'خطأ في جلب الوثائق القانونية'
      });
    }
  };

  getDocumentById = async (req: Request, res: Response): Promise<void> => {
    try {
      const { id } = req.params;
      const document = await this.legalService.getDocumentById(id);
      if (!document) {
        res.status(404).json({
          success: false,
          message: 'الوثيقة القانونية غير موجودة'
        });
        return;
      }
      res.json({
        success: true,
        data: document,
        message: 'تم جلب الوثيقة القانونية بنجاح'
      });
    } catch (error) {
      logger.error('Error fetching legal document:', error);
      res.status(500).json({
        success: false,
        message: 'خطأ في جلب الوثيقة القانونية'
      });
    }
  };

  createDocument = async (req: Request, res: Response): Promise<void> => {
    try {
      const documentData = req.body;
      const newDocument = await this.legalService.createDocument(documentData);
      res.status(201).json({
        success: true,
        data: newDocument,
        message: 'تم إنشاء الوثيقة القانونية بنجاح'
      });
    } catch (error) {
      logger.error('Error creating legal document:', error);
      res.status(500).json({
        success: false,
        message: 'خطأ في إنشاء الوثيقة القانونية'
      });
    }
  };

  updateDocument = async (req: Request, res: Response): Promise<void> => {
    try {
      const { id } = req.params;
      const updateData = req.body;
      const updatedDocument = await this.legalService.updateDocument(id, updateData);
      if (!updatedDocument) {
        res.status(404).json({
          success: false,
          message: 'الوثيقة القانونية غير موجودة'
        });
        return;
      }
      res.json({
        success: true,
        data: updatedDocument,
        message: 'تم تحديث الوثيقة القانونية بنجاح'
      });
    } catch (error) {
      logger.error('Error updating legal document:', error);
      res.status(500).json({
        success: false,
        message: 'خطأ في تحديث الوثيقة القانونية'
      });
    }
  };

  deleteDocument = async (req: Request, res: Response): Promise<void> => {
    try {
      const { id } = req.params;
      const deleted = await this.legalService.deleteDocument(id);
      if (!deleted) {
        res.status(404).json({
          success: false,
          message: 'الوثيقة القانونية غير موجودة'
        });
        return;
      }
      res.json({
        success: true,
        message: 'تم حذف الوثيقة القانونية بنجاح'
      });
    } catch (error) {
      logger.error('Error deleting legal document:', error);
      res.status(500).json({
        success: false,
        message: 'خطأ في حذف الوثيقة القانونية'
      });
    }
  };

  // Case management methods
  getAllCases = async (req: Request, res: Response): Promise<void> => {
    try {
      const { page = 1, limit = 10, status, type } = req.query;
      const filters = { status, type };
      const cases = await this.legalService.getAllCases(
        Number(page),
        Number(limit),
        filters
      );
      res.json({
        success: true,
        data: cases,
        message: 'تم جلب القضايا القانونية بنجاح'
      });
    } catch (error) {
      logger.error('Error fetching legal cases:', error);
      res.status(500).json({
        success: false,
        message: 'خطأ في جلب القضايا القانونية'
      });
    }
  };

  getCaseById = async (req: Request, res: Response): Promise<void> => {
    try {
      const { id } = req.params;
      const legalCase = await this.legalService.getCaseById(id);
      if (!legalCase) {
        res.status(404).json({
          success: false,
          message: 'القضية القانونية غير موجودة'
        });
        return;
      }
      res.json({
        success: true,
        data: legalCase,
        message: 'تم جلب القضية القانونية بنجاح'
      });
    } catch (error) {
      logger.error('Error fetching legal case:', error);
      res.status(500).json({
        success: false,
        message: 'خطأ في جلب القضية القانونية'
      });
    }
  };

  createCase = async (req: Request, res: Response): Promise<void> => {
    try {
      const caseData = req.body;
      const newCase = await this.legalService.createCase(caseData);
      res.status(201).json({
        success: true,
        data: newCase,
        message: 'تم إنشاء القضية القانونية بنجاح'
      });
    } catch (error) {
      logger.error('Error creating legal case:', error);
      res.status(500).json({
        success: false,
        message: 'خطأ في إنشاء القضية القانونية'
      });
    }
  };

  updateCase = async (req: Request, res: Response): Promise<void> => {
    try {
      const { id } = req.params;
      const updateData = req.body;
      const updatedCase = await this.legalService.updateCase(id, updateData);
      if (!updatedCase) {
        res.status(404).json({
          success: false,
          message: 'القضية القانونية غير موجودة'
        });
        return;
      }
      res.json({
        success: true,
        data: updatedCase,
        message: 'تم تحديث القضية القانونية بنجاح'
      });
    } catch (error) {
      logger.error('Error updating legal case:', error);
      res.status(500).json({
        success: false,
        message: 'خطأ في تحديث القضية القانونية'
      });
    }
  };

  deleteCase = async (req: Request, res: Response): Promise<void> => {
    try {
      const { id } = req.params;
      const deleted = await this.legalService.deleteCase(id);
      if (!deleted) {
        res.status(404).json({
          success: false,
          message: 'القضية القانونية غير موجودة'
        });
        return;
      }
      res.json({
        success: true,
        message: 'تم حذف القضية القانونية بنجاح'
      });
    } catch (error) {
      logger.error('Error deleting legal case:', error);
      res.status(500).json({
        success: false,
        message: 'خطأ في حذف القضية القانونية'
      });
    }
  };

  // Contract management methods
  getAllContracts = async (req: Request, res: Response): Promise<void> => {
    try {
      const { page = 1, limit = 10, status, type } = req.query;
      const filters = { status, type };
      const contracts = await this.legalService.getAllContracts(
        Number(page),
        Number(limit),
        filters
      );
      res.json({
        success: true,
        data: contracts,
        message: 'تم جلب العقود بنجاح'
      });
    } catch (error) {
      logger.error('Error fetching contracts:', error);
      res.status(500).json({
        success: false,
        message: 'خطأ في جلب العقود'
      });
    }
  };

  getContractById = async (req: Request, res: Response): Promise<void> => {
    try {
      const { id } = req.params;
      const contract = await this.legalService.getContractById(id);
      if (!contract) {
        res.status(404).json({
          success: false,
          message: 'العقد غير موجود'
        });
        return;
      }
      res.json({
        success: true,
        data: contract,
        message: 'تم جلب العقد بنجاح'
      });
    } catch (error) {
      logger.error('Error fetching contract:', error);
      res.status(500).json({
        success: false,
        message: 'خطأ في جلب العقد'
      });
    }
  };

  createContract = async (req: Request, res: Response): Promise<void> => {
    try {
      const contractData = req.body;
      const newContract = await this.legalService.createContract(contractData);
      res.status(201).json({
        success: true,
        data: newContract,
        message: 'تم إنشاء العقد بنجاح'
      });
    } catch (error) {
      logger.error('Error creating contract:', error);
      res.status(500).json({
        success: false,
        message: 'خطأ في إنشاء العقد'
      });
    }
  };

  updateContract = async (req: Request, res: Response): Promise<void> => {
    try {
      const { id } = req.params;
      const updateData = req.body;
      const updatedContract = await this.legalService.updateContract(id, updateData);
      if (!updatedContract) {
        res.status(404).json({
          success: false,
          message: 'العقد غير موجود'
        });
        return;
      }
      res.json({
        success: true,
        data: updatedContract,
        message: 'تم تحديث العقد بنجاح'
      });
    } catch (error) {
      logger.error('Error updating contract:', error);
      res.status(500).json({
        success: false,
        message: 'خطأ في تحديث العقد'
      });
    }
  };

  deleteContract = async (req: Request, res: Response): Promise<void> => {
    try {
      const { id } = req.params;
      const deleted = await this.legalService.deleteContract(id);
      if (!deleted) {
        res.status(404).json({
          success: false,
          message: 'العقد غير موجود'
        });
        return;
      }
      res.json({
        success: true,
        message: 'تم حذف العقد بنجاح'
      });
    } catch (error) {
      logger.error('Error deleting contract:', error);
      res.status(500).json({
        success: false,
        message: 'خطأ في حذف العقد'
      });
    }
  };

  // Compliance methods
  getComplianceStatus = async (req: Request, res: Response): Promise<void> => {
    try {
      const complianceStatus = await this.legalService.getComplianceStatus();
      res.json({
        success: true,
        data: complianceStatus,
        message: 'تم جلب حالة الامتثال بنجاح'
      });
    } catch (error) {
      logger.error('Error fetching compliance status:', error);
      res.status(500).json({
        success: false,
        message: 'خطأ في جلب حالة الامتثال'
      });
    }
  };

  createComplianceAudit = async (req: Request, res: Response): Promise<void> => {
    try {
      const auditData = req.body;
      const audit = await this.legalService.createComplianceAudit(auditData);
      res.status(201).json({
        success: true,
        data: audit,
        message: 'تم إنشاء تدقيق الامتثال بنجاح'
      });
    } catch (error) {
      logger.error('Error creating compliance audit:', error);
      res.status(500).json({
        success: false,
        message: 'خطأ في إنشاء تدقيق الامتثال'
      });
    }
  };

  getComplianceReports = async (req: Request, res: Response): Promise<void> => {
    try {
      const { startDate, endDate } = req.query;
      const reports = await this.legalService.getComplianceReports(startDate as string, endDate as string);
      
      res.status(200).json({
        success: true,
        data: reports
      });
    } catch (error) {
      logger.error('Error getting compliance reports:', error);
      res.status(500).json({
        success: false,
        message: 'خطأ في الحصول على تقارير الامتثال'
      });
    }
  };
}