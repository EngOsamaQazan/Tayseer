import logger from './logger';
import * as fs from 'fs';
import * as path from 'path';

interface UploadOptions {
  maxSize?: number; // in bytes
  allowedTypes?: string[];
  destination?: string;
  filename?: string;
}

interface UploadedFile {
  originalName: string;
  filename: string;
  path: string;
  size: number;
  mimetype: string;
  url: string;
}

class UploadUtil {
  private defaultOptions: UploadOptions = {
    maxSize: 10 * 1024 * 1024, // 10MB
    allowedTypes: ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'text/csv'],
    destination: 'uploads',
  };

  async uploadFile(file: any, options: UploadOptions = {}): Promise<UploadedFile> {
    try {
      const opts = { ...this.defaultOptions, ...options };
      
      // Validate file size
      if (file.size > opts.maxSize!) {
        throw new Error(`File size exceeds maximum allowed size of ${opts.maxSize! / 1024 / 1024}MB`);
      }

      // Validate file type
      if (opts.allowedTypes && !opts.allowedTypes.includes(file.mimetype)) {
        throw new Error(`File type ${file.mimetype} is not allowed`);
      }

      // Generate filename
      const filename = opts.filename || this.generateFilename(file.originalname);
      const destination = opts.destination!;
      const filePath = path.join(destination, filename);

      // Ensure destination directory exists
      await this.ensureDirectory(destination);

      // Save file (this is a mock implementation)
      // In a real implementation, you would save the actual file
      const uploadedFile: UploadedFile = {
        originalName: file.originalname,
        filename,
        path: filePath,
        size: file.size,
        mimetype: file.mimetype,
        url: `/uploads/${filename}`,
      };

      logger.info('File uploaded successfully', {
        filename,
        size: file.size,
        mimetype: file.mimetype,
      });

      return uploadedFile;
    } catch (error) {
      logger.error('Upload error', error);
      throw error;
    }
  }

  async uploadMultiple(files: any[], options: UploadOptions = {}): Promise<UploadedFile[]> {
    try {
      const uploadPromises = files.map(file => this.uploadFile(file, options));
      return await Promise.all(uploadPromises);
    } catch (error) {
      logger.error('Multiple upload error', error);
      throw error;
    }
  }

  async deleteFile(filename: string): Promise<void> {
    try {
      const filePath = path.join(this.defaultOptions.destination!, filename);
      
      if (fs.existsSync(filePath)) {
        fs.unlinkSync(filePath);
        logger.info('File deleted successfully', { filename });
      }
    } catch (error) {
      logger.error('Delete file error', { filename, error });
      throw error;
    }
  }

  private generateFilename(originalName: string): string {
    const timestamp = Date.now();
    const extension = path.extname(originalName);
    const name = path.basename(originalName, extension);
    return `${name}_${timestamp}${extension}`;
  }

  private async ensureDirectory(dir: string): Promise<void> {
    try {
      if (!fs.existsSync(dir)) {
        fs.mkdirSync(dir, { recursive: true });
      }
    } catch (error) {
      logger.error('Ensure directory error', { dir, error });
      throw error;
    }
  }

  validateFileType(filename: string, allowedTypes: string[]): boolean {
    const extension = path.extname(filename).toLowerCase();
    const mimeTypeMap: { [key: string]: string } = {
      '.jpg': 'image/jpeg',
      '.jpeg': 'image/jpeg',
      '.png': 'image/png',
      '.gif': 'image/gif',
      '.pdf': 'application/pdf',
      '.csv': 'text/csv',
    };

    const mimetype = mimeTypeMap[extension];
    return mimetype ? allowedTypes.includes(mimetype) : false;
  }

  formatFileSize(bytes: number): string {
    if (bytes === 0) return '0 Bytes';
    
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
  }
}

export const uploadUtil = new UploadUtil();
export default uploadUtil;