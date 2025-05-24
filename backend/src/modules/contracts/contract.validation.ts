import { z } from 'zod';
import { isValidEmail, isValidPhoneNumber, isValidUUID } from '../../utils/validation.util';

// Contract types
export const contractTypes = ['service', 'rental', 'sales', 'maintenance', 'other'] as const;
export const contractStatuses = ['draft', 'active', 'suspended', 'completed', 'cancelled'] as const;
export const paymentTerms = ['monthly', 'quarterly', 'semi_annual', 'annual', 'one_time'] as const;
export const attachmentTypes = ['contract_copy', 'amendment', 'annex', 'other'] as const;

// Contract item schema
const contractItemSchema = z.object({
  name: z.string().min(1).max(255),
  description: z.string().optional(),
  quantity: z.number().positive(),
  unitPrice: z.number().min(0),
  discount: z.number().min(0).max(100).default(0),
  tax: z.number().min(0).max(100).default(0),
});

// Create contract validation
export const createContractSchema = z.object({
  body: z.object({
    customerId: z.string().refine(isValidUUID, 'معرف العميل غير صحيح'),
    type: z.enum(contractTypes),
    title: z.string().min(3).max(255),
    description: z.string().optional(),
    startDate: z.string().datetime(),
    endDate: z.string().datetime(),
    value: z.number().min(0),
    paymentTerm: z.enum(paymentTerms),
    autoRenew: z.boolean().default(false),
    renewalPeriod: z.number().optional(), // in months
    terms: z.string().optional(),
    items: z.array(contractItemSchema).optional(),
    tags: z.array(z.string()).optional(),
    metadata: z.record(z.any()).optional(),
  }).refine(
    (data) => new Date(data.endDate) > new Date(data.startDate),
    {
      message: 'تاريخ الانتهاء يجب أن يكون بعد تاريخ البداية',
      path: ['endDate'],
    }
  ),
});

// Update contract validation
export const updateContractSchema = z.object({
  params: z.object({
    id: z.string().refine(isValidUUID, 'معرف العقد غير صحيح'),
  }),
  body: z.object({
    type: z.enum(contractTypes).optional(),
    title: z.string().min(3).max(255).optional(),
    description: z.string().optional(),
    startDate: z.string().datetime().optional(),
    endDate: z.string().datetime().optional(),
    value: z.number().min(0).optional(),
    paymentTerm: z.enum(paymentTerms).optional(),
    autoRenew: z.boolean().optional(),
    renewalPeriod: z.number().optional(),
    terms: z.string().optional(),
    tags: z.array(z.string()).optional(),
    metadata: z.record(z.any()).optional(),
  }).refine(
    (data) => {
      if (data.startDate && data.endDate) {
        return new Date(data.endDate) > new Date(data.startDate);
      }
      return true;
    },
    {
      message: 'تاريخ الانتهاء يجب أن يكون بعد تاريخ البداية',
      path: ['endDate'],
    }
  ),
});

// Contract ID validation
export const contractIdSchema = z.object({
  params: z.object({
    id: z.string().refine(isValidUUID, 'معرف العقد غير صحيح'),
  }),
});

// Contract list validation
export const contractListSchema = z.object({
  query: z.object({
    page: z.string().regex(/^\d+$/).transform(Number).optional(),
    limit: z.string().regex(/^\d+$/).transform(Number).optional(),
    status: z.enum(contractStatuses).optional(),
    customerId: z.string().refine(isValidUUID).optional(),
    type: z.enum(contractTypes).optional(),
    search: z.string().optional(),
    sortBy: z.string().optional(),
    sortOrder: z.enum(['asc', 'desc']).optional(),
    startDate: z.string().datetime().optional(),
    endDate: z.string().datetime().optional(),
    minValue: z.string().regex(/^\d+(\.\d+)?$/).transform(Number).optional(),
    maxValue: z.string().regex(/^\d+(\.\d+)?$/).transform(Number).optional(),
  }),
});

// Cancel contract validation
export const cancelContractSchema = z.object({
  params: z.object({
    id: z.string().refine(isValidUUID, 'معرف العقد غير صحيح'),
  }),
  body: z.object({
    reason: z.string().min(10).max(500),
  }),
});

// Renew contract validation
export const renewContractSchema = z.object({
  params: z.object({
    id: z.string().refine(isValidUUID, 'معرف العقد غير صحيح'),
  }),
  body: z.object({
    newEndDate: z.string().datetime(),
    newValue: z.number().min(0).optional(),
    adjustments: z.string().optional(),
  }),
});

// Upload attachment validation
export const uploadAttachmentSchema = z.object({
  params: z.object({
    id: z.string().refine(isValidUUID, 'معرف العقد غير صحيح'),
  }),
  body: z.object({
    title: z.string().min(1).max(255),
    description: z.string().optional(),
    type: z.enum(attachmentTypes),
  }),
});

// Delete attachment validation
export const deleteAttachmentSchema = z.object({
  params: z.object({
    id: z.string().refine(isValidUUID, 'معرف العقد غير صحيح'),
    attachmentId: z.string().refine(isValidUUID, 'معرف المرفق غير صحيح'),
  }),
});

// Add item validation
export const addItemSchema = z.object({
  params: z.object({
    id: z.string().refine(isValidUUID, 'معرف العقد غير صحيح'),
  }),
  body: contractItemSchema,
});

// Update item validation
export const updateItemSchema = z.object({
  params: z.object({
    id: z.string().refine(isValidUUID, 'معرف العقد غير صحيح'),
    itemId: z.string().refine(isValidUUID, 'معرف البند غير صحيح'),
  }),
  body: contractItemSchema.partial(),
});

// Delete item validation
export const deleteItemSchema = z.object({
  params: z.object({
    id: z.string().refine(isValidUUID, 'معرف العقد غير صحيح'),
    itemId: z.string().refine(isValidUUID, 'معرف البند غير صحيح'),
  }),
});

// Contract statistics validation
export const contractStatisticsSchema = z.object({
  query: z.object({
    startDate: z.string().datetime().optional(),
    endDate: z.string().datetime().optional(),
    customerId: z.string().refine(isValidUUID).optional(),
    status: z.enum(contractStatuses).optional(),
    type: z.enum(contractTypes).optional(),
  }),
});

// Export contracts validation
export const exportContractsSchema = z.object({
  query: z.object({
    format: z.enum(['xlsx', 'csv']).default('xlsx'),
    status: z.enum(contractStatuses).optional(),
    customerId: z.string().refine(isValidUUID).optional(),
    type: z.enum(contractTypes).optional(),
    startDate: z.string().datetime().optional(),
    endDate: z.string().datetime().optional(),
    includeItems: z.string().transform(val => val === 'true').optional(),
    includeAttachments: z.string().transform(val => val === 'true').optional(),
  }),
});

// Contract validation helpers
export const validateContractDates = (startDate: Date, endDate: Date): boolean => {
  return endDate > startDate;
};

export const validateContractValue = (value: number, items?: any[]): boolean => {
  if (!items || items.length === 0) return true;
  
  const itemsTotal = items.reduce((sum, item) => {
    const subtotal = item.quantity * item.unitPrice;
    const discountAmount = subtotal * (item.discount / 100);
    const taxAmount = (subtotal - discountAmount) * (item.tax / 100);
    return sum + subtotal - discountAmount + taxAmount;
  }, 0);
  
  // Allow for small rounding differences
  return Math.abs(value - itemsTotal) < 0.01;
};

export const validateRenewalDate = (currentEndDate: Date, newEndDate: Date): boolean => {
  return newEndDate > currentEndDate;
};