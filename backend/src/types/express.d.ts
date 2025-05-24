import { User } from '@prisma/client';

declare global {
  namespace Express {
    interface Request {
      user?: {
        id: string;
        email: string;
        tenantId: string;
        role: string;
        permissions: string[];
      };
      session?: {
        id: string;
        deviceInfo?: any;
      };
      tenantId?: string;
    }
  }
}

export {};