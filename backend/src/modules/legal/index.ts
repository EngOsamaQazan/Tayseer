// Legal module exports
export * from './legal.controller';
export * from './legal.service';
export * from './legal.validation';

// Re-export specific items for convenience
export { LegalController } from './legal.controller';
export { LegalService } from './legal.service';
export {
  createLegalDocumentSchema,
  updateLegalDocumentSchema,
  createLegalCaseSchema,
  updateLegalCaseSchema,
  createContractSchema,
  updateContractSchema,
  createComplianceAuditSchema,
  queryParamsSchema,
  idParamsSchema
} from './legal.validation';