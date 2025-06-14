import { Router } from 'express';
import { EmployeeController } from './employee.controller';
import { authMiddleware } from '../../middleware/auth.middleware';
import { validate } from '../../middleware/validation.middleware';
import { employeeValidation } from './employee.validation';

const router = Router();
const employeeController = new EmployeeController();

// جميع المسارات تتطلب المصادقة
router.use(authMiddleware);

// مسارات الموظفين
router.get('/', employeeController.getAllEmployees.bind(employeeController));
router.get('/:id', employeeController.getEmployeeById.bind(employeeController));
router.post('/', validate(employeeValidation.createEmployee), employeeController.createEmployee.bind(employeeController));
router.put('/:id', validate(employeeValidation.updateEmployee), employeeController.updateEmployee.bind(employeeController));
router.delete('/:id', employeeController.deleteEmployee.bind(employeeController));

// مسارات إضافية
router.get('/:id/attendance', employeeController.getEmployeeAttendance.bind(employeeController));
router.post('/:id/attendance', employeeController.recordAttendance.bind(employeeController));
router.get('/:id/payroll', employeeController.getEmployeePayroll.bind(employeeController));

export default router;