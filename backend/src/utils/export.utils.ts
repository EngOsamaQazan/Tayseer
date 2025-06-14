// أدوات التصدير
import ExcelJS from 'exceljs';
import PDFDocument from 'pdfkit';
import fs from 'fs';
import path from 'path';
import { Parser } from 'json2csv';
import { logger } from '@/config/logger';
import { formatCurrency } from './format.utils';
import { formatDate } from './date.utils';

// واجهة خيارات التصدير
interface ExportOptions {
  format: 'excel' | 'pdf' | 'csv';
  filename: string;
  title?: string;
  headers?: string[];
  data: any[];
  columns?: Array<{
    key: string;
    header: string;
    width?: number;
    type?: 'string' | 'number' | 'date' | 'currency' | 'percentage';
  }>;
  rtl?: boolean;
  fontSize?: number;
  orientation?: 'portrait' | 'landscape';
}

// تصدير إلى Excel
export const exportToExcel = async (options: ExportOptions): Promise<Buffer> => {
  const workbook = new ExcelJS.Workbook();
  const worksheet = workbook.addWorksheet(options.title || 'تقرير');
  
  // تعيين اتجاه الورقة للعربية
  if (options.rtl !== false) {
    worksheet.views = [{ rightToLeft: true }];
  }
  
  // إضافة العنوان
  if (options.title) {
    worksheet.mergeCells('A1:' + String.fromCharCode(65 + (options.columns?.length || options.headers?.length || 1) - 1) + '1');
    worksheet.getCell('A1').value = options.title;
    worksheet.getCell('A1').font = { size: 16, bold: true };
    worksheet.getCell('A1').alignment = { horizontal: 'center', vertical: 'middle' };
    worksheet.getRow(1).height = 30;
  }
  
  // إضافة الأعمدة
  const startRow = options.title ? 3 : 1;
  
  if (options.columns) {
    worksheet.columns = options.columns.map(col => ({
      header: col.header,
      key: col.key,
      width: col.width || 15
    }));
    
    // تنسيق رؤوس الأعمدة
    worksheet.getRow(startRow).font = { bold: true };
    worksheet.getRow(startRow).alignment = { horizontal: 'center', vertical: 'middle' };
    worksheet.getRow(startRow).height = 25;
    
    // إضافة البيانات
    options.data.forEach((item) => {
      const rowData: any = {};
      
      options.columns!.forEach(col => {
        let value = item[col.key];
        
        // تنسيق القيم حسب النوع
        switch (col.type) {
          case 'date':
            value = value ? formatDate(value) : '';
            break;
          case 'currency':
            value = value ? formatCurrency(value) : '';
            break;
          case 'percentage':
            value = value ? `${value}%` : '';
            break;
        }
        
        rowData[col.key] = value;
      });
      
      worksheet.addRow(rowData);
    });
  } else if (options.headers) {
    // استخدام الرؤوس المخصصة
    const headerRow = worksheet.getRow(startRow);
    options.headers.forEach((header, index) => {
      headerRow.getCell(index + 1).value = header;
    });
    
    headerRow.font = { bold: true };
    headerRow.alignment = { horizontal: 'center', vertical: 'middle' };
    headerRow.height = 25;
    
    // إضافة البيانات
    options.data.forEach((row, rowIndex) => {
      const dataRow = worksheet.getRow(startRow + rowIndex + 1);
      
      if (Array.isArray(row)) {
        row.forEach((cell, cellIndex) => {
          dataRow.getCell(cellIndex + 1).value = cell;
        });
      } else {
        Object.values(row).forEach((cell, cellIndex) => {
          dataRow.getCell(cellIndex + 1).value = cell as any;
        });
      }
    });
  }
  
  // تطبيق الحدود
  const lastRow = worksheet.lastRow?.number || startRow;
  const lastCol = options.columns?.length || options.headers?.length || 1;
  
  for (let row = startRow; row <= lastRow; row++) {
    for (let col = 1; col <= lastCol; col++) {
      const cell = worksheet.getRow(row).getCell(col);
      cell.border = {
        top: { style: 'thin' },
        left: { style: 'thin' },
        bottom: { style: 'thin' },
        right: { style: 'thin' }
      };
    }
  }
  
  // إضافة التاريخ والوقت في التذييل
  const footerRow = worksheet.getRow(lastRow + 2);
  footerRow.getCell(1).value = `تم التصدير في: ${formatDate(new Date(), 'full')}`;
  footerRow.getCell(1).font = { italic: true, size: 10 };
  
  // تحويل إلى Buffer
  const buffer = await workbook.xlsx.writeBuffer();
  return Buffer.from(buffer);
};

// تصدير إلى PDF
export const exportToPDF = async (options: ExportOptions): Promise<Buffer> => {
  return new Promise((resolve, reject) => {
    try {
      const doc = new PDFDocument({
        size: 'A4',
        layout: options.orientation || 'portrait',
        margin: 50
      });
      
      const chunks: Buffer[] = [];
      doc.on('data', chunk => chunks.push(chunk));
      doc.on('end', () => resolve(Buffer.concat(chunks)));
      
      // تحميل الخط العربي
      const fontPath = path.join(process.cwd(), 'fonts', 'NotoSansArabic-Regular.ttf');
      if (fs.existsSync(fontPath)) {
        doc.registerFont('Arabic', fontPath);
        doc.font('Arabic');
      }
      
      // إضافة العنوان
      if (options.title) {
        doc.fontSize(20)
           .text(options.title, { align: 'center' })
           .moveDown(2);
      }
      
      // حساب عرض الأعمدة
      const pageWidth = doc.page.width - doc.page.margins.left - doc.page.margins.right;
      const columns = options.columns || [];
      const colCount = columns.length || options.headers?.length || 1;
      const colWidth = pageWidth / colCount;
      
      // إضافة رؤوس الأعمدة
      doc.fontSize(options.fontSize || 12);
      let x = doc.page.margins.left;
      const y = doc.y;
      
      if (options.columns) {
        options.columns.forEach((col) => {
          doc.text(col.header, x, y, {
            width: colWidth,
            align: 'center'
          });
          x += colWidth;
        });
      } else if (options.headers) {
        options.headers.forEach((header) => {
          doc.text(header, x, y, {
            width: colWidth,
            align: 'center'
          });
          x += colWidth;
        });
      }
      
      // رسم خط تحت الرؤوس
      doc.moveTo(doc.page.margins.left, doc.y + 5)
         .lineTo(doc.page.width - doc.page.margins.right, doc.y + 5)
         .stroke();
      
      doc.moveDown();
      
      // إضافة البيانات
      options.data.forEach((item) => {
        x = doc.page.margins.left;
        const rowY = doc.y;
        
        if (options.columns) {
          options.columns.forEach((col) => {
            let value = item[col.key];
            
            // تنسيق القيم حسب النوع
            switch (col.type) {
              case 'date':
                value = value ? formatDate(value) : '';
                break;
              case 'currency':
                value = value ? formatCurrency(value) : '';
                break;
              case 'percentage':
                value = value ? `${value}%` : '';
                break;
            }
            
            doc.text(String(value || ''), x, rowY, {
              width: colWidth,
              align: 'center'
            });
            x += colWidth;
          });
        } else if (Array.isArray(item)) {
          item.forEach((cell) => {
            doc.text(String(cell || ''), x, rowY, {
              width: colWidth,
              align: 'center'
            });
            x += colWidth;
          });
        } else {
          Object.values(item).forEach((cell) => {
            doc.text(String(cell || ''), x, rowY, {
              width: colWidth,
              align: 'center'
            });
            x += colWidth;
          });
        }
        
        doc.moveDown(0.5);
        
        // إضافة صفحة جديدة إذا لزم الأمر
        if (doc.y > doc.page.height - doc.page.margins.bottom - 50) {
          doc.addPage();
        }
      });
      
      // إضافة التذييل
      doc.fontSize(10)
         .text(`تم التصدير في: ${formatDate(new Date(), 'full')}`, {
           align: 'center',
           width: pageWidth
         });
      
      // إنهاء المستند
      doc.end();
    } catch (error) {
      reject(error);
    }
  });
};

// تصدير إلى CSV
export const exportToCSV = (options: ExportOptions): Buffer => {
  try {
    let csvData: string;
    
    if (options.columns) {
      // استخدام json2csv مع الأعمدة المحددة
      const fields = options.columns.map(col => ({
        label: col.header,
        value: (row: any) => {
          let value = row[col.key];
          
          // تنسيق القيم حسب النوع
          switch (col.type) {
            case 'date':
              value = value ? formatDate(value) : '';
              break;
            case 'currency':
              value = value ? formatCurrency(value) : '';
              break;
            case 'percentage':
              value = value ? `${value}%` : '';
              break;
          }
          
          return value;
        }
      }));
      
      const parser = new Parser({ fields });
      csvData = parser.parse(options.data);
    } else if (options.headers) {
      // استخدام الرؤوس المخصصة
      const parser = new Parser({ fields: options.headers });
      csvData = parser.parse(options.data);
    } else {
      // تصدير جميع الحقول
      const parser = new Parser();
      csvData = parser.parse(options.data);
    }
    
    // إضافة BOM للتوافق مع Excel والعربية
    const bom = '\ufeff';
    return Buffer.from(bom + csvData, 'utf8');
  } catch (error) {
    logger.error('Error exporting to CSV:', error);
    throw error;
  }
};

// دالة التصدير الرئيسية
export const exportData = async (options: ExportOptions): Promise<Buffer> => {
  try {
    let buffer: Buffer;
    
    switch (options.format) {
      case 'excel':
        buffer = await exportToExcel(options);
        break;
      case 'pdf':
        buffer = await exportToPDF(options);
        break;
      case 'csv':
        buffer = exportToCSV(options);
        break;
      default:
        throw new Error(`Unsupported export format: ${options.format}`);
    }
    
    logger.info('Data exported successfully', {
      format: options.format,
      filename: options.filename,
      recordCount: options.data.length
    });
    
    return buffer;
  } catch (error) {
    logger.error('Error exporting data:', error);
    throw error;
  }
};

// دالة مساعدة لحفظ الملف المصدر
export const saveExportFile = async (
  buffer: Buffer,
  filename: string,
  directory: string = 'exports'
): Promise<string> => {
  try {
    const exportDir = path.join(process.cwd(), directory);
    
    // إنشاء المجلد إذا لم يكن موجوداً
    if (!fs.existsSync(exportDir)) {
      fs.mkdirSync(exportDir, { recursive: true });
    }
    
    const filePath = path.join(exportDir, filename);
    fs.writeFileSync(filePath, buffer);
    
    logger.info('Export file saved', { filePath });
    return filePath;
  } catch (error) {
    logger.error('Error saving export file:', error);
    throw error;
  }
};

// دالة لتنظيف الملفات المصدرة القديمة
export const cleanupOldExports = async (
  directory: string = 'exports',
  daysToKeep: number = 7
): Promise<number> => {
  try {
    const exportDir = path.join(process.cwd(), directory);
    
    if (!fs.existsSync(exportDir)) {
      return 0;
    }
    
    const files = fs.readdirSync(exportDir);
    const now = Date.now();
    const maxAge = daysToKeep * 24 * 60 * 60 * 1000;
    let deletedCount = 0;
    
    for (const file of files) {
      const filePath = path.join(exportDir, file);
      const stats = fs.statSync(filePath);
      
      if (now - stats.mtimeMs > maxAge) {
        fs.unlinkSync(filePath);
        deletedCount++;
      }
    }
    
    logger.info(`Cleaned up ${deletedCount} old export files`);
    return deletedCount;
  } catch (error) {
    logger.error('Error cleaning up old exports:', error);
    throw error;
  }
};

// دالة لإنشاء تقرير مخصص
export const createCustomReport = async (
  title: string,
  sections: Array<{
    title: string;
    data: any[];
    columns: ExportOptions['columns'];
  }>,
  format: ExportOptions['format'] = 'excel'
): Promise<Buffer> => {
  if (format === 'excel') {
    const workbook = new ExcelJS.Workbook();
    
    sections.forEach((section) => {
      const worksheet = workbook.addWorksheet(section.title);
      worksheet.views = [{ rightToLeft: true }];
      
      // إضافة العنوان
      const colCount = section.columns?.length || 1;
      worksheet.mergeCells('A1:' + String.fromCharCode(65 + colCount - 1) + '1');
      worksheet.getCell('A1').value = section.title;
      worksheet.getCell('A1').font = { size: 16, bold: true };
      worksheet.getCell('A1').alignment = { horizontal: 'center', vertical: 'middle' };
      worksheet.getRow(1).height = 30;
      
      // إضافة الأعمدة والبيانات
      if (section.columns) {
        worksheet.columns = section.columns.map(col => ({
        header: col.header,
        key: col.key,
        width: col.width || 15
      }));
      
      worksheet.getRow(3).font = { bold: true };
      worksheet.getRow(3).alignment = { horizontal: 'center', vertical: 'middle' };
      worksheet.getRow(3).height = 25;
      
      section.data.forEach(item => {
        const rowData: any = {};
        
        section.columns?.forEach(col => {
          let value = item[col.key];
          
          switch (col.type) {
            case 'date':
              value = value ? formatDate(value) : '';
              break;
            case 'currency':
              value = value ? formatCurrency(value) : '';
              break;
            case 'percentage':
              value = value ? `${value}%` : '';
              break;
          }
          
          rowData[col.key] = value;
        });
        
        worksheet.addRow(rowData);
      });
      }
      
      // تطبيق الحدود
      const lastRow = worksheet.lastRow?.number || 3;
      const lastCol = section.columns?.length || 1;
      
      for (let row = 3; row <= lastRow; row++) {
        for (let col = 1; col <= lastCol; col++) {
          const cell = worksheet.getRow(row).getCell(col);
          cell.border = {
            top: { style: 'thin' },
            left: { style: 'thin' },
            bottom: { style: 'thin' },
            right: { style: 'thin' }
          };
        }
      }
    });
    
    const buffer = await workbook.xlsx.writeBuffer();
    return Buffer.from(buffer);
  } else {
    // للتنسيقات الأخرى، دمج جميع الأقسام
    const allData: any[] = [];
    const allColumns: ExportOptions['columns'] = [];
    
    sections.forEach(section => {
      allData.push({ sectionTitle: section.title });
      allData.push(...section.data);
      allData.push({}); // سطر فارغ
      
      if (allColumns.length === 0 && section.columns) {
        allColumns.push(...section.columns);
      }
    });
    
    return exportData({
      format,
      filename: `${title}.${format}`,
      title,
      data: allData,
      columns: allColumns
    });
  }
};

// دالة لتصدير الفواتير
export const exportInvoices = async (
  invoices: Array<{
    invoiceNumber: string;
    date: Date;
    customerName: string;
    total: number;
    tax: number;
    grandTotal: number;
    status: string;
  }>,
  format: ExportOptions['format'] = 'excel'
): Promise<Buffer> => {
  const columns: ExportOptions['columns'] = [
    { key: 'invoiceNumber', header: 'رقم الفاتورة', width: 20 },
    { key: 'date', header: 'التاريخ', width: 15, type: 'date' },
    { key: 'customerName', header: 'اسم العميل', width: 25 },
    { key: 'total', header: 'المجموع', width: 15, type: 'currency' },
    { key: 'tax', header: 'الضريبة', width: 15, type: 'currency' },
    { key: 'grandTotal', header: 'الإجمالي', width: 15, type: 'currency' },
    { key: 'status', header: 'الحالة', width: 15 }
  ];
  
  return exportData({
    format,
    filename: `invoices-${Date.now()}.${format}`,
    title: 'تقرير الفواتير',
    data: invoices,
    columns
  });
};

// دالة لتصدير المخزون
export const exportInventory = async (
  products: Array<{
    code: string;
    name: string;
    category: string;
    quantity: number;
    unit: string;
    price: number;
    totalValue: number;
    status: string;
  }>,
  format: ExportOptions['format'] = 'excel'
): Promise<Buffer> => {
  const columns: ExportOptions['columns'] = [
    { key: 'code', header: 'كود المنتج', width: 15 },
    { key: 'name', header: 'اسم المنتج', width: 25 },
    { key: 'category', header: 'الفئة', width: 20 },
    { key: 'quantity', header: 'الكمية', width: 12, type: 'number' },
    { key: 'unit', header: 'الوحدة', width: 10 },
    { key: 'price', header: 'السعر', width: 15, type: 'currency' },
    { key: 'totalValue', header: 'القيمة الإجمالية', width: 18, type: 'currency' },
    { key: 'status', header: 'الحالة', width: 15 }
  ];
  
  return exportData({
    format,
    filename: `inventory-${Date.now()}.${format}`,
    title: 'تقرير المخزون',
    data: products,
    columns
  });
};

// دالة لتصدير تقرير مالي
export const exportFinancialReport = async (
  data: {
    summary: {
      totalRevenue: number;
      totalExpenses: number;
      netProfit: number;
      profitMargin: number;
    };
    transactions: Array<{
      date: Date;
      description: string;
      type: string;
      amount: number;
      balance: number;
    }>;
  },
  format: ExportOptions['format'] = 'excel'
): Promise<Buffer> => {
  if (format === 'excel') {
    return createCustomReport(
      'التقرير المالي',
      [
        {
          title: 'الملخص المالي',
          data: [
            {
              metric: 'إجمالي الإيرادات',
              value: data.summary.totalRevenue
            },
            {
              metric: 'إجمالي المصروفات',
              value: data.summary.totalExpenses
            },
            {
              metric: 'صافي الربح',
              value: data.summary.netProfit
            },
            {
              metric: 'هامش الربح',
              value: data.summary.profitMargin
            }
          ],
          columns: [
            { key: 'metric', header: 'البند', width: 25 },
            { key: 'value', header: 'القيمة', width: 20, type: 'currency' }
          ]
        },
        {
          title: 'الحركات المالية',
          data: data.transactions,
          columns: [
            { key: 'date', header: 'التاريخ', width: 15, type: 'date' },
            { key: 'description', header: 'الوصف', width: 30 },
            { key: 'type', header: 'النوع', width: 15 },
            { key: 'amount', header: 'المبلغ', width: 15, type: 'currency' },
            { key: 'balance', header: 'الرصيد', width: 15, type: 'currency' }
          ]
        }
      ],
      format
    );
  } else {
    // للتنسيقات الأخرى
    const allData = [
      { description: 'إجمالي الإيرادات', amount: data.summary.totalRevenue },
      { description: 'إجمالي المصروفات', amount: data.summary.totalExpenses },
      { description: 'صافي الربح', amount: data.summary.netProfit },
      { description: 'هامش الربح (%)', amount: data.summary.profitMargin },
      {},
      ...data.transactions
    ];
    
    return exportData({
      format,
      filename: `financial-report-${Date.now()}.${format}`,
      title: 'التقرير المالي',
      data: allData,
      columns: [
        { key: 'date', header: 'التاريخ', type: 'date' },
        { key: 'description', header: 'الوصف' },
        { key: 'type', header: 'النوع' },
        { key: 'amount', header: 'المبلغ', type: 'currency' },
        { key: 'balance', header: 'الرصيد', type: 'currency' }
      ]
    });
  }
};