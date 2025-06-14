import { Request, Response } from 'express';
import { EmployeeService } from './employee.service';
import { logger } from '../../config/logger';

export class EmployeeController {
  private employeeService: EmployeeService;

  constructor() {
    this.employeeService = new EmployeeService();
  }

  async getAllEmployees(req: Request, res: Response) {
    try {
      const employees = await this.employeeService.getAllEmployees();
      res.json({
        success: true,
        data: employees,
        message: 'تم جلب قائمة الموظفين بنجاح'
      });
    } catch (error) {
      logger.error('خطأ في جلب قائمة الموظفين:', error);
      res.status(500).json({
        success: false,
        message: 'خطأ داخلي في الخادم'
      });
    }
  }

  async getEmployeeById(req: Request, res: Response) {
    try {
      const { id } = req.params;
      const employee = await this.employeeService.getEmployeeById(parseInt(id));
      
      if (!employee) {
        return res.status(404).json({
          success: false,
          message: 'الموظف غير موجود'
        });
      }

      res.json({
        success: true,
        data: employee,
        message: 'تم جلب بيانات الموظف بنجاح'
      });
    } catch (error) {
      logger.error('خطأ في جلب بيانات الموظف:', error);
      res.status(500).json({
        success: false,
        message: 'خطأ داخلي في الخادم'
      });
    }
  }

  async createEmployee(req: Request, res: Response) {
    try {
      const employee = await this.employeeService.createEmployee(req.body);
      res.status(201).json({
        success: true,
        data: employee,
        message: 'تم إنشاء الموظف بنجاح'
      });
    } catch (error) {
      logger.error('خطأ في إنشاء الموظف:', error);
      res.status(500).json({
        success: false,
        message: 'خطأ داخلي في الخادم'
      });
    }
  }

  async updateEmployee(req: Request, res: Response) {
    try {
      const { id } = req.params;
      const employee = await this.employeeService.updateEmployee(parseInt(id), req.body);
      
      if (!employee) {
        return res.status(404).json({
          success: false,
          message: 'الموظف غير موجود'
        });
      }

      res.json({
        success: true,
        data: employee,
        message: 'تم تحديث بيانات الموظف بنجاح'
      });
    } catch (error) {
      logger.error('خطأ في تحديث بيانات الموظف:', error);
      res.status(500).json({
        success: false,
        message: 'خطأ داخلي في الخادم'
      });
    }
  }

  async deleteEmployee(req: Request, res: Response) {
    try {
      const { id } = req.params;
      await this.employeeService.deleteEmployee(parseInt(id));
      
      res.json({
        success: true,
        message: 'تم حذف الموظف بنجاح'
      });
    } catch (error) {
      logger.error('خطأ في حذف الموظف:', error);
      res.status(500).json({
        success: false,
        message: 'خطأ داخلي في الخادم'
      });
    }
  }

  async getEmployeeAttendance(req: Request, res: Response) {
    try {
      const { id } = req.params;
      const attendance = await this.employeeService.getEmployeeAttendance(parseInt(id));
      
      res.json({
        success: true,
        data: attendance,
        message: 'تم جلب سجل الحضور بنجاح'
      });
    } catch (error) {
      logger.error('خطأ في جلب سجل الحضور:', error);
      res.status(500).json({
        success: false,
        message: 'خطأ داخلي في الخادم'
      });
    }
  }

  async recordAttendance(req: Request, res: Response) {
    try {
      const { id } = req.params;
      const attendance = await this.employeeService.recordAttendance(parseInt(id), req.body);
      
      res.status(201).json({
        success: true,
        data: attendance,
        message: 'تم تسجيل الحضور بنجاح'
      });
    } catch (error) {
      logger.error('خطأ في تسجيل الحضور:', error);
      res.status(500).json({
        success: false,
        message: 'خطأ داخلي في الخادم'
      });
    }
  }

  async getEmployeePayroll(req: Request, res: Response) {
    try {
      const { id } = req.params;
      const payroll = await this.employeeService.getEmployeePayroll(parseInt(id));
      
      res.json({
        success: true,
        data: payroll,
        message: 'تم جلب بيانات الراتب بنجاح'
      });
    } catch (error) {
      logger.error('خطأ في جلب بيانات الراتب:', error);
      res.status(500).json({
        success: false,
        message: 'خطأ داخلي في الخادم'
      });
    }
  }
}