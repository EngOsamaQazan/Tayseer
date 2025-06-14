// Support module exports
export * from './support.routes';
export * from './support.controller';
export * from './support.service';
export * from './support.validation';

// Re-export specific items for convenience
export { supportRoutes } from './support.routes';
export { SupportController } from './support.controller';
export { SupportService } from './support.service';
export {
  createTicketSchema,
  updateTicketSchema,
  updateTicketStatusSchema,
  assignTicketSchema,
  updateTicketPrioritySchema,
  addTicketReplySchema,
  createKnowledgeBaseSchema,
  updateKnowledgeBaseSchema,
  createFAQSchema,
  updateFAQSchema,
  addCustomerFeedbackSchema,
  escalateTicketSchema,
  queryParamsSchema,
  idParamsSchema,
  analyticsPeriodSchema,
  rateKnowledgeBaseSchema,
  rateFAQSchema
} from './support.validation';