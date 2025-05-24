import * as XLSX from 'xlsx';
import { logger } from '@/utils/logger';
import path from 'path';
import fs from 'fs/promises';

export interface ExcelColumn {
  header: string;
  key: string;
  width?: number;
  type?: 'string' | 'number' | 'date' | 'boolean';
  format?: string;
}

export interface ExcelExportOptions {
  filename: string;
  sheetName?: string;
  columns: ExcelColumn[];
  data: any[];
  title?: string;
  subtitle?: string;
}

export class ExcelService {
  private static instance: ExcelService;

  private constructor() {}

  public static getInstance(): ExcelService {
    if (!ExcelService.instance) {
      ExcelService.instance = new ExcelService();
    }
    return ExcelService.instance;
  }

  async exportToExcel(options: ExcelExportOptions): Promise<string> {
    try {
      const workbook = XLSX.utils.book_new();
      const sheetName = options.sheetName || 'Sheet1';

      // تحضير البيانات
      const worksheetData = this.prepareWorksheetData(options);
      
      // إنشاء ورقة العمل
      const worksheet = XLSX.utils.aoa_to_sheet(worksheetData);
      
      // تطبيق التنسيق
      this.applyFormatting(worksheet, options);
      
      // إضافة ورقة العمل إلى المصنف
      XLSX.utils.book_append_sheet(workbook, worksheet, sheetName);
      
      // إنشاء مجلد التصدير إذا لم يكن موجوداً
      const exportDir = path.join(process.cwd(), 'exports');
      await fs.mkdir(exportDir, { recursive: true });
      
      // مسار الملف
      const filePath = path.join(exportDir, options.filename);
      
      // كتابة الملف
      XLSX.writeFile(workbook, filePath);
      
      logger.info(`Excel file exported successfully: ${filePath}`);
      return filePath;
    } catch (error) {
      logger.error('Failed to export Excel file:', error);
      throw error;
    }
  }

  private prepareWorksheetData(options: ExcelExportOptions): any[][] {
    const data: any[][] = [];
    
    // إضافة العنوان الرئيسي
    if (options.title) {
      data.push([options.title]);
      data.push([]); // سطر فارغ
    }
    
    // إضافة العنوان الفرعي
    if (options.subtitle) {
      data.push([options.subtitle]);
      data.push([]); // سطر فارغ
    }
    
    // إضافة رؤوس الأعمدة
    const headers = options.columns.map(col => col.header);
    data.push(headers);
    
    // إضافة بيانات الصفوف
    options.data.forEach(row => {
      const rowData = options.columns.map(col => {
        const value = row[col.key];
        return this.formatCellValue(value, col.type);
      });
      data.push(rowData);
    });
    
    return data;
  }

  private formatCellValue(value: any, type?: string): any {
    if (value === null || value === undefined) {
      return '';
    }
    
    switch (type) {
      case 'date':
        return value instanceof Date ? value.toLocaleDateString('ar-SA') : value;
      case 'number':
        return typeof value === 'number' ? value : parseFloat(value) || 0;
      case 'boolean':
        return value ? 'نعم' : 'لا';
      default:
        return value.toString();
    }
  }

  private applyFormatting(worksheet: XLSX.WorkSheet, options: ExcelExportOptions) {
    // تطبيق عرض الأعمدة
    const colWidths = options.columns.map(col => ({
      wch: col.width || 15
    }));
    worksheet['!cols'] = colWidths;
    
    // تطبيق تنسيق الخلايا (يمكن توسيعه حسب الحاجة)
    // هذا مثال بسيط، يمكن إضافة المزيد من التنسيقات
  }

  async importFromExcel(filePath: string): Promise<any[]> {
    try {
      const workbook = XLSX.readFile(filePath);
      const sheetName = workbook.SheetNames[0];
      const worksheet = workbook.Sheets[sheetName];
      
      // تحويل ورقة العمل إلى JSON
      const data = XLSX.utils.sheet_to_json(worksheet, {
        header: 1,
        defval: ''
      });
      
      logger.info(`Excel file imported successfully: ${filePath}`);
      return data as any[];
    } catch (error) {
      logger.error('Failed to import Excel file:', error);
      throw error;
    }
  }

  async parseExcelFile(buffer: Buffer): Promise<any[]> {
    try {
      const workbook = XLSX.read(buffer, { type: 'buffer' });
      const sheetName = workbook.SheetNames[0];
      const worksheet = workbook.Sheets[sheetName];
      
      const data = XLSX.utils.sheet_to_json(worksheet, {
        header: 1,
        defval: ''
      });
      
      return data as any[];
    } catch (error) {
      logger.error('Failed to parse Excel buffer:', error);
      throw error;
    }
  }

  validateExcelStructure(data: any[], expectedColumns: string[]): {
    valid: boolean;
    errors: string[];
  } {
    const errors: string[] = [];
    
    if (!data || data.length === 0) {
      errors.push('ملف Excel فارغ');
      return { valid: false, errors };
    }
    
    const headers = data[0] as string[];
    
    // التحقق من وجود جميع الأعمدة المطلوبة
    expectedColumns.forEach(col => {
      if (!headers.includes(col)) {
        errors.push(`العمود المطلوب '${col}' غير موجود`);
      }
    });
    
    return {
      valid: errors.length === 0,
      errors
    };
  }

  async createTemplate(columns: ExcelColumn[], filename: string): Promise<string> {
    const templateData = {
      filename,
      columns,
      data: [], // قالب فارغ
      title: 'قالب استيراد البيانات',
      subtitle: 'يرجى ملء البيانات في الصفوف أدناه'
    };
    
    return await this.exportToExcel(templateData);
  }
}