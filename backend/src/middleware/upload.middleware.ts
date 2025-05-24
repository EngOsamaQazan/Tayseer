import { Request, Response, NextFunction } from 'express';
import multer from 'multer';
import path from 'path';
import fs from 'fs/promises';
import crypto from 'crypto';
import { logger } from '../config/logger';
import { FileUploadError } from './error.middleware';

// الأنواع المسموحة للملفات
const ALLOWED_MIME_TYPES = {
  // الصور
  'image/jpeg': ['.jpg', '.jpeg'],
  'image/png': ['.png'],
  'image/gif': ['.gif'],
  'image/webp': ['.webp'],
  'image/svg+xml': ['.svg'],
  
  // المستندات
  'application/pdf': ['.pdf'],
  'application/msword': ['.doc'],
  'application/vnd.openxmlformats-officedocument.wordprocessingml.document': ['.docx'],
  'application/vnd.ms-excel': ['.xls'],
  'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet': ['.xlsx'],
  'application/vnd.ms-powerpoint': ['.ppt'],
  'application/vnd.openxmlformats-officedocument.presentationml.presentation': ['.pptx'],
  
  // ملفات نصية
  'text/plain': ['.txt'],
  'text/csv': ['.csv'],
  
  // ملفات مضغوطة
  'application/zip': ['.zip'],
  'application/x-rar-compressed': ['.rar'],
  'application/x-7z-compressed': ['.7z']
};

// حدود حجم الملفات (بالبايت)
const FILE_SIZE_LIMITS = {
  image: 5 * 1024 * 1024, // 5MB
  document: 10 * 1024 * 1024, // 10MB
  archive: 50 * 1024 * 1024, // 50MB
  default: 10 * 1024 * 1024 // 10MB
};

// الحصول على نوع الملف
const getFileCategory = (mimetype: string): string => {
  if (mimetype.startsWith('image/')) return 'image';
  if (mimetype.includes('pdf') || mimetype.includes('document') || mimetype.includes('sheet') || mimetype.includes('presentation')) return 'document';
  if (mimetype.includes('zip') || mimetype.includes('rar') || mimetype.includes('7z')) return 'archive';
  return 'default';
};

// إنشاء اسم فريد للملف
const generateUniqueFilename = (originalname: string): string => {
  const timestamp = Date.now();
  const randomString = crypto.randomBytes(8).toString('hex');
  const ext = path.extname(originalname);
  const nameWithoutExt = path.basename(originalname, ext);
  // تنظيف اسم الملف من الأحرف الخاصة
  const cleanName = nameWithoutExt.replace(/[^a-zA-Z0-9-_]/g, '_');
  return `${cleanName}_${timestamp}_${randomString}${ext}`;
};

// إعداد التخزين
const createStorage = (uploadPath: string) => {
  return multer.diskStorage({
    destination: async (req, file, cb) => {
      try {
        const tenant = (req as any).tenant;
        const category = getFileCategory(file.mimetype);
        
        // إنشاء مسار التخزين بناءً على المستأجر والتصنيف
        const fullPath = path.join(
          uploadPath,
          tenant?.id || 'common',
          category,
          new Date().getFullYear().toString(),
          (new Date().getMonth() + 1).toString().padStart(2, '0')
        );
        
        // إنشاء المجلد إذا لم يكن موجوداً
        await fs.mkdir(fullPath, { recursive: true });
        
        cb(null, fullPath);
      } catch (error) {
        cb(error as Error, '');
      }
    },
    filename: (req, file, cb) => {
      const uniqueFilename = generateUniqueFilename(file.originalname);
      cb(null, uniqueFilename);
    }
  });
};

// فلتر الملفات
const fileFilter = (allowedTypes?: string[]) => {
  return (req: Request, file: Express.Multer.File, cb: multer.FileFilterCallback) => {
    // التحقق من نوع MIME
    if (!ALLOWED_MIME_TYPES[file.mimetype]) {
      return cb(new FileUploadError('نوع الملف غير مسموح'));
    }
    
    // التحقق من الامتداد
    const ext = path.extname(file.originalname).toLowerCase();
    const allowedExts = ALLOWED_MIME_TYPES[file.mimetype];
    if (!allowedExts.includes(ext)) {
      return cb(new FileUploadError('امتداد الملف لا يتطابق مع نوع المحتوى'));
    }
    
    // التحقق من الأنواع المحددة إن وجدت
    if (allowedTypes && !allowedTypes.includes(file.mimetype)) {
      return cb(new FileUploadError(`يُسمح فقط بالأنواع: ${allowedTypes.join(', ')}`));
    }
    
    cb(null, true);
  };
};

// إنشاء middleware للرفع
export const createUploadMiddleware = (options: {
  fieldName: string;
  multiple?: boolean;
  maxCount?: number;
  allowedTypes?: string[];
  maxSize?: number;
  uploadPath?: string;
}) => {
  const {
    fieldName,
    multiple = false,
    maxCount = 1,
    allowedTypes,
    maxSize,
    uploadPath = process.env.UPLOAD_PATH || './uploads'
  } = options;
  
  // تحديد حجم الملف
  let fileSizeLimit = maxSize;
  if (!fileSizeLimit && allowedTypes && allowedTypes.length > 0) {
    const category = getFileCategory(allowedTypes[0]);
    fileSizeLimit = FILE_SIZE_LIMITS[category];
  }
  fileSizeLimit = fileSizeLimit || FILE_SIZE_LIMITS.default;
  
  const upload = multer({
    storage: createStorage(uploadPath),
    fileFilter: fileFilter(allowedTypes),
    limits: {
      fileSize: fileSizeLimit,
      files: maxCount
    }
  });
  
  // اختيار طريقة الرفع
  const uploadMethod = multiple
    ? upload.array(fieldName, maxCount)
    : upload.single(fieldName);
  
  return (req: Request, res: Response, next: NextFunction) => {
    uploadMethod(req, res, (err: any) => {
      if (err) {
        // معالجة أخطاء multer
        if (err instanceof multer.MulterError) {
          switch (err.code) {
            case 'LIMIT_FILE_SIZE':
              return next(new FileUploadError(`حجم الملف يتجاوز الحد المسموح (${(fileSizeLimit! / (1024 * 1024)).toFixed(2)}MB)`));
            case 'LIMIT_FILE_COUNT':
              return next(new FileUploadError(`عدد الملفات يتجاوز الحد المسموح (${maxCount})`));
            case 'LIMIT_UNEXPECTED_FILE':
              return next(new FileUploadError('حقل غير متوقع للملف'));
            default:
              return next(new FileUploadError('خطأ في رفع الملف'));
          }
        }
        return next(err);
      }
      
      // إضافة معلومات الملفات للطلب
      if (req.file) {
        (req as any).uploadedFile = {
          filename: req.file.filename,
          originalname: req.file.originalname,
          mimetype: req.file.mimetype,
          size: req.file.size,
          path: req.file.path,
          url: `/uploads/${path.relative('./uploads', req.file.path).replace(/\\/g, '/')}`
        };
      }
      
      if (req.files && Array.isArray(req.files)) {
        (req as any).uploadedFiles = req.files.map(file => ({
          filename: file.filename,
          originalname: file.originalname,
          mimetype: file.mimetype,
          size: file.size,
          path: file.path,
          url: `/uploads/${path.relative('./uploads', file.path).replace(/\\/g, '/')}`
        }));
      }
      
      next();
    });
  };
};

// Middleware لرفع الصور
export const uploadImage = createUploadMiddleware({
  fieldName: 'image',
  allowedTypes: ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
  maxSize: FILE_SIZE_LIMITS.image
});

// Middleware لرفع المستندات
export const uploadDocument = createUploadMiddleware({
  fieldName: 'document',
  allowedTypes: [
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
  ],
  maxSize: FILE_SIZE_LIMITS.document
});

// Middleware لرفع ملفات متعددة
export const uploadMultipleFiles = createUploadMiddleware({
  fieldName: 'files',
  multiple: true,
  maxCount: 10
});

// دالة لحذف الملف
export const deleteFile = async (filePath: string): Promise<void> => {
  try {
    await fs.unlink(filePath);
    logger.info(`تم حذف الملف: ${filePath}`);
  } catch (error) {
    logger.error(`فشل حذف الملف: ${filePath}`, error);
    throw new Error('فشل حذف الملف');
  }
};

// دالة للتحقق من وجود الملف
export const fileExists = async (filePath: string): Promise<boolean> => {
  try {
    await fs.access(filePath);
    return true;
  } catch {
    return false;
  }
};

// دالة للحصول على معلومات الملف
export const getFileInfo = async (filePath: string): Promise<{
  size: number;
  created: Date;
  modified: Date;
}> => {
  try {
    const stats = await fs.stat(filePath);
    return {
      size: stats.size,
      created: stats.birthtime,
      modified: stats.mtime
    };
  } catch (error) {
    throw new Error('فشل الحصول على معلومات الملف');
  }
};