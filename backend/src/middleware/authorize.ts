import { Request, Response, NextFunction } from 'express';
import { AuthRequest } from './auth.middleware';

export const authorize = (requiredPermissions: string[]) => {
  return async (req: Request, res: Response, next: NextFunction) => {
    const authReq = req as AuthRequest;
    
    if (!authReq.user) {
      return res.status(401).json({
        success: false,
        message: 'غير مصدق'
      });
    }

    const hasPermission = requiredPermissions.some(permission => 
      authReq.user!.permissions.includes(permission)
    );

    if (!hasPermission) {
      return res.status(403).json({
        success: false,
        message: 'غير مصرح'
      });
    }

    next();
  };
};