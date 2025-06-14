import { Router } from 'express';
import { LegalController } from './legal.controller';
import { authMiddleware } from '../../middleware/auth.middleware';
import { validateRequest } from '../../middleware/validation.middleware';
import { legalValidation } from './legal.validation';

const router = Router();
const legalController = new LegalController();

// Apply authentication middleware to all routes
router.use(authMiddleware);

// Legal document routes
router.get('/documents', legalController.getAllDocuments);
router.get('/documents/:id', legalController.getDocumentById);
router.post('/documents', validateRequest(legalValidation.createDocument), legalController.createDocument);
router.put('/documents/:id', validateRequest(legalValidation.updateDocument), legalController.updateDocument);
router.delete('/documents/:id', legalController.deleteDocument);

// Legal case routes
router.get('/cases', legalController.getAllCases);
router.get('/cases/:id', legalController.getCaseById);
router.post('/cases', validateRequest(legalValidation.createCase), legalController.createCase);
router.put('/cases/:id', validateRequest(legalValidation.updateCase), legalController.updateCase);
router.delete('/cases/:id', legalController.deleteCase);

// Contract routes
router.get('/contracts', legalController.getAllContracts);
router.get('/contracts/:id', legalController.getContractById);
router.post('/contracts', validateRequest(legalValidation.createContract), legalController.createContract);
router.put('/contracts/:id', validateRequest(legalValidation.updateContract), legalController.updateContract);
router.delete('/contracts/:id', legalController.deleteContract);

// Compliance routes
router.get('/compliance', legalController.getComplianceStatus);
router.post('/compliance/audit', validateRequest(legalValidation.createAudit), legalController.createComplianceAudit);
router.get('/compliance/reports', legalController.getComplianceReports);

export default router;