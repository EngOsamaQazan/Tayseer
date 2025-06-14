import { Router } from 'express';
import { SupportController } from './support.controller';
import { authenticateToken } from '../../middleware/auth';
import { validateRequest } from '../../middleware/validation';
import {
  createTicketSchema,
  updateTicketSchema,
  addResponseSchema,
  ticketQuerySchema,
  idParamsSchema
} from './support.validation';

const router = Router();
const supportController = new SupportController();

// Apply authentication to all routes
router.use(authenticateToken);

// Ticket routes
router.get('/tickets', validateRequest(ticketQuerySchema, 'query'), supportController.getTickets);
router.post('/tickets', validateRequest(createTicketSchema), supportController.createTicket);
router.get('/tickets/:id', validateRequest(idParamsSchema, 'params'), supportController.getTicketById);
router.put('/tickets/:id', 
  validateRequest(idParamsSchema, 'params'),
  validateRequest(updateTicketSchema),
  supportController.updateTicket
);
router.delete('/tickets/:id', validateRequest(idParamsSchema, 'params'), supportController.deleteTicket);

// Ticket status and assignment
router.patch('/tickets/:id/status', 
  validateRequest(idParamsSchema, 'params'),
  supportController.updateTicketStatus
);
router.patch('/tickets/:id/assign', 
  validateRequest(idParamsSchema, 'params'),
  supportController.assignTicket
);
router.patch('/tickets/:id/priority', 
  validateRequest(idParamsSchema, 'params'),
  supportController.updateTicketPriority
);

// Ticket responses and communication
router.post('/tickets/:id/responses', 
  validateRequest(idParamsSchema, 'params'),
  validateRequest(addResponseSchema),
  supportController.addTicketResponse
);
router.get('/tickets/:id/responses', 
  validateRequest(idParamsSchema, 'params'),
  supportController.getTicketResponses
);

// Knowledge base routes
router.get('/knowledge-base', supportController.getKnowledgeBaseArticles);
router.post('/knowledge-base', supportController.createKnowledgeBaseArticle);
router.get('/knowledge-base/:id', 
  validateRequest(idParamsSchema, 'params'),
  supportController.getKnowledgeBaseArticleById
);
router.put('/knowledge-base/:id', 
  validateRequest(idParamsSchema, 'params'),
  supportController.updateKnowledgeBaseArticle
);
router.delete('/knowledge-base/:id', 
  validateRequest(idParamsSchema, 'params'),
  supportController.deleteKnowledgeBaseArticle
);

// FAQ routes
router.get('/faq', supportController.getFAQs);
router.post('/faq', supportController.createFAQ);
router.get('/faq/:id', 
  validateRequest(idParamsSchema, 'params'),
  supportController.getFAQById
);
router.put('/faq/:id', 
  validateRequest(idParamsSchema, 'params'),
  supportController.updateFAQ
);
router.delete('/faq/:id', 
  validateRequest(idParamsSchema, 'params'),
  supportController.deleteFAQ
);

// Support analytics and reports
router.get('/analytics/tickets', supportController.getTicketAnalytics);
router.get('/analytics/performance', supportController.getSupportPerformance);
router.get('/reports/tickets', supportController.getTicketReports);
router.get('/reports/customer-satisfaction', supportController.getCustomerSatisfactionReports);

// Customer feedback
router.post('/tickets/:id/feedback', 
  validateRequest(idParamsSchema, 'params'),
  supportController.addCustomerFeedback
);
router.get('/feedback', supportController.getCustomerFeedback);

// Support team management
router.get('/team', supportController.getSupportTeam);
router.get('/team/:id/performance', 
  validateRequest(idParamsSchema, 'params'),
  supportController.getTeamMemberPerformance
);

// Escalation management
router.post('/tickets/:id/escalate', 
  validateRequest(idParamsSchema, 'params'),
  supportController.escalateTicket
);
router.get('/escalations', supportController.getEscalatedTickets);

export default router;