import { logger } from '../../config/logger';

interface Employee {
  id: number;
  firstName: string;
  lastName: string;
  email: string;
  phone: string;
  position: string;
  department: string;
  hireDate: Date;
  salary: number;
  status: 'active' | 'inactive' | 'terminated';
  createdAt: Date;
  updatedAt: Date;
}

interface AttendanceRecord {
  id: number;
  employeeId: number;
  date: Date;
  checkIn?: Date;
  checkOut?: Date;
  totalHours?: number;
  status: 'present' | 'absent' | 'late' | 'holiday';
}

interface PayrollRecord {
  id: number;
  employeeId: number;
  period: string;
  basicSalary: number;
  overtime: number;
  deductions: number;
  netSalary: number;
  payDate: Date;
}

export class EmployeeService {
  // بيانات وهمية للموظفين
  private employees: Employee[] = [
    {
      id: 1,
      firstName: 'أحمد',
      lastName: 'محمد',
      email: 'ahmed.mohamed@tayseer.com',
      phone: '+966501234567',
      position: 'مطور برمجيات',
      department: 'تقنية المعلومات',
      hireDate: new Date('2023-01-15'),
      salary: 8000,
      status: 'active',
      createdAt: new Date(),
      updatedAt: new Date()
    },
    {
      id: 2,
      firstName: 'فاطمة',
      lastName: 'أحمد',
      email: 'fatima.ahmed@tayseer.com',
      phone: '+966507654321',
      position: 'محاسبة',
      department: 'المالية',
      hireDate: new Date('2023-02-01'),
      salary: 6000,
      status: 'active',
      createdAt: new Date(),
      updatedAt: new Date()
    }
  ];

  async getAllEmployees(): Promise<Employee[]> {
    try {
      logger.info('جلب قائمة جميع الموظفين');
      return this.employees;
    } catch (error) {
      logger.error('خطأ في جلب قائمة الموظفين:', error);
      throw error;
    }
  }

  async getEmployeeById(id: number): Promise<Employee | null> {
    try {
      logger.info(`جلب بيانات الموظف برقم: ${id}`);
      const employee = this.employees.find(emp => emp.id === id);
      return employee || null;
    } catch (error) {
      logger.error(`خطأ في جلب بيانات الموظف برقم ${id}:`, error);
      throw error;
    }
  }

  async createEmployee(employeeData: Partial<Employee>): Promise<Employee> {
    try {
      logger.info('إنشاء موظف جديد');
      const newEmployee: Employee = {
        id: Math.max(...this.employees.map(e => e.id), 0) + 1,
        firstName: employeeData.firstName || '',
        lastName: employeeData.lastName || '',
        email: employeeData.email || '',
        phone: employeeData.phone || '',
        position: employeeData.position || '',
        department: employeeData.department || '',
        hireDate: employeeData.hireDate || new Date(),
        salary: employeeData.salary || 0,
        status: employeeData.status || 'active',
        createdAt: new Date(),
        updatedAt: new Date()
      };
      
      this.employees.push(newEmployee);
      return newEmployee;
    } catch (error) {
      logger.error('خطأ في إنشاء الموظف:', error);
      throw error;
    }
  }

  async updateEmployee(id: number, updateData: Partial<Employee>): Promise<Employee | null> {
    try {
      logger.info(`تحديث بيانات الموظف برقم: ${id}`);
      const employeeIndex = this.employees.findIndex(emp => emp.id === id);
      
      if (employeeIndex === -1) {
        return null;
      }
      
      this.employees[employeeIndex] = {
        ...this.employees[employeeIndex],
        ...updateData,
        updatedAt: new Date()
      };
      
      return this.employees[employeeIndex];
    } catch (error) {
      logger.error(`خطأ في تحديث بيانات الموظف برقم ${id}:`, error);
      throw error;
    }
  }

  async deleteEmployee(id: number): Promise<void> {
    try {
      logger.info(`حذف الموظف برقم: ${id}`);
      const employeeIndex = this.employees.findIndex(emp => emp.id === id);
      
      if (employeeIndex !== -1) {
        this.employees.splice(employeeIndex, 1);
      }
    } catch (error) {
      logger.error(`خطأ في حذف الموظف برقم ${id}:`, error);
      throw error;
    }
  }

  async getEmployeeAttendance(employeeId: number): Promise<AttendanceRecord[]> {
    try {
      logger.info(`جلب سجل حضور الموظف برقم: ${employeeId}`);
      // بيانات وهمية لسجل الحضور
      return [
        {
          id: 1,
          employeeId,
          date: new Date(),
          checkIn: new Date('2024-01-01T08:00:00'),
          checkOut: new Date('2024-01-01T17:00:00'),
          totalHours: 8,
          status: 'present'
        }
      ];
    } catch (error) {
      logger.error(`خطأ في جلب سجل حضور الموظف برقم ${employeeId}:`, error);
      throw error;
    }
  }

  async recordAttendance(employeeId: number, attendanceData: any): Promise<AttendanceRecord> {
    try {
      logger.info(`تسجيل حضور للموظف برقم: ${employeeId}`);
      // منطق تسجيل الحضور
      const newRecord: AttendanceRecord = {
        id: Math.floor(Math.random() * 1000),
        employeeId,
        date: new Date(),
        checkIn: attendanceData.checkIn ? new Date(attendanceData.checkIn) : undefined,
        checkOut: attendanceData.checkOut ? new Date(attendanceData.checkOut) : undefined,
        totalHours: attendanceData.totalHours || 0,
        status: attendanceData.status || 'present'
      };
      
      return newRecord;
    } catch (error) {
      logger.error(`خطأ في تسجيل حضور الموظف برقم ${employeeId}:`, error);
      throw error;
    }
  }

  async getEmployeePayroll(employeeId: number): Promise<PayrollRecord[]> {
    try {
      logger.info(`جلب بيانات راتب الموظف برقم: ${employeeId}`);
      const employee = await this.getEmployeeById(employeeId);
      
      if (!employee) {
        return [];
      }
      
      // بيانات وهمية للراتب
      return [
        {
          id: 1,
          employeeId,
          period: '2024-01',
          basicSalary: employee.salary,
          overtime: 500,
          deductions: 200,
          netSalary: employee.salary + 500 - 200,
          payDate: new Date()
        }
      ];
    } catch (error) {
      logger.error(`خطأ في جلب بيانات راتب الموظف برقم ${employeeId}:`, error);
      throw error;
    }
  }
}