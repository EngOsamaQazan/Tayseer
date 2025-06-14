import { Request, Response, NextFunction } from 'express';
import { AuthRequest } from './auth.middleware';
import logger from '../shared/utils/logger';

interface Permission {
  resource: string;
  action: string;
}

interface Role {
  id: string;
  name: string;
  permissions: Permission[];
}

// Mock role definitions - in a real app, these would come from database
const ROLES: Role[] = [
  {
    id: 'admin',
    name: 'Administrator',
    permissions: [
      { resource: '*', action: '*' }, // Admin has all permissions
    ],
  },
  {
    id: 'manager',
    name: 'Manager',
    permissions: [
      { resource: 'users', action: 'read' },
      { resource: 'users', action: 'update' },
      { resource: 'products', action: '*' },
      { resource: 'inventory', action: '*' },
      { resource: 'reports', action: 'read' },
      { resource: 'customers', action: '*' },
      { resource: 'contracts', action: '*' },
    ],
  },
  {
    id: 'employee',
    name: 'Employee',
    permissions: [
      { resource: 'products', action: 'read' },
      { resource: 'inventory', action: 'read' },
      { resource: 'customers', action: 'read' },
      { resource: 'tasks', action: '*' },
    ],
  },
  {
    id: 'viewer',
    name: 'Viewer',
    permissions: [
      { resource: 'products', action: 'read' },
      { resource: 'inventory', action: 'read' },
      { resource: 'customers', action: 'read' },
      { resource: 'reports', action: 'read' },
    ],
  },
];

class RBACMiddleware {
  private getRolePermissions(roleId: string): Permission[] {
    const role = ROLES.find(r => r.id === roleId);
    return role ? role.permissions : [];
  }

  private hasPermission(userPermissions: Permission[], resource: string, action: string): boolean {
    return userPermissions.some(permission => {
      const resourceMatch = permission.resource === '*' || permission.resource === resource;
      const actionMatch = permission.action === '*' || permission.action === action;
      return resourceMatch && actionMatch;
    });
  }

  authorize(resource: string, action: string = 'read') {
    return (req: AuthRequest, res: Response, next: NextFunction) => {
      try {
        if (!req.user) {
          logger.warn('Authorization failed: No user in request', {
            path: req.path,
            method: req.method,
          });
          return res.status(401).json({
            success: false,
            message: 'غير مصرح بالوصول - يجب تسجيل الدخول أولاً',
            error: 'UNAUTHORIZED',
          });
        }

        const userRole = req.user.role || 'viewer';
        const permissions = this.getRolePermissions(userRole);

        if (!this.hasPermission(permissions, resource, action)) {
          logger.warn('Authorization failed: Insufficient permissions', {
            userId: req.user.id,
            userRole,
            resource,
            action,
            path: req.path,
            method: req.method,
          });
          return res.status(403).json({
            success: false,
            message: 'ليس لديك صلاحية للوصول إلى هذا المورد',
            error: 'FORBIDDEN',
          });
        }

        logger.debug('Authorization successful', {
          userId: req.user.id,
          userRole,
          resource,
          action,
        });

        next();
      } catch (error) {
        logger.error('Authorization error', error);
        res.status(500).json({
          success: false,
          message: 'خطأ في التحقق من الصلاحيات',
          error: 'INTERNAL_SERVER_ERROR',
        });
      }
    };
  }

  requireRole(requiredRole: string) {
    return (req: AuthRequest, res: Response, next: NextFunction) => {
      try {
        if (!req.user) {
          return res.status(401).json({
            success: false,
            message: 'غير مصرح بالوصول - يجب تسجيل الدخول أولاً',
            error: 'UNAUTHORIZED',
          });
        }

        const userRole = req.user.role || 'viewer';
        
        if (userRole !== requiredRole && userRole !== 'admin') {
          logger.warn('Role authorization failed', {
            userId: req.user.id,
            userRole,
            requiredRole,
            path: req.path,
          });
          return res.status(403).json({
            success: false,
            message: `يتطلب صلاحية ${requiredRole} للوصول إلى هذا المورد`,
            error: 'FORBIDDEN',
          });
        }

        next();
      } catch (error) {
        logger.error('Role authorization error', error);
        res.status(500).json({
          success: false,
          message: 'خطأ في التحقق من الصلاحيات',
          error: 'INTERNAL_SERVER_ERROR',
        });
      }
    };
  }

  requireAnyRole(roles: string[]) {
    return (req: AuthRequest, res: Response, next: NextFunction) => {
      try {
        if (!req.user) {
          return res.status(401).json({
            success: false,
            message: 'غير مصرح بالوصول - يجب تسجيل الدخول أولاً',
            error: 'UNAUTHORIZED',
          });
        }

        const userRole = req.user.role || 'viewer';
        
        if (!roles.includes(userRole) && userRole !== 'admin') {
          logger.warn('Multi-role authorization failed', {
            userId: req.user.id,
            userRole,
            requiredRoles: roles,
            path: req.path,
          });
          return res.status(403).json({
            success: false,
            message: `يتطلب إحدى الصلاحيات التالية: ${roles.join(', ')}`,
            error: 'FORBIDDEN',
          });
        }

        next();
      } catch (error) {
        logger.error('Multi-role authorization error', error);
        res.status(500).json({
          success: false,
          message: 'خطأ في التحقق من الصلاحيات',
          error: 'INTERNAL_SERVER_ERROR',
        });
      }
    };
  }
}

const rbacMiddleware = new RBACMiddleware();

// Export commonly used authorization functions
export const authorize = rbacMiddleware.authorize.bind(rbacMiddleware);
export const requireRole = rbacMiddleware.requireRole.bind(rbacMiddleware);
export const requireAnyRole = rbacMiddleware.requireAnyRole.bind(rbacMiddleware);

export default rbacMiddleware;