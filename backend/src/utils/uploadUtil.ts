import path from 'path';
import fs from 'fs/promises';
import { logger } from './logger';
import crypto from 'crypto';

export interface UploadedFile {
  filename: string;
  originalName: string;
  mimetype: string;
  size: number;
  path: string;
}

export const uploadUtil = {
  async saveFile(file: Express.Multer.File, directory: string): Promise<UploadedFile> {
    try {
      // Generate unique filename
      const ext = path.extname(file.originalname);
      const uniqueSuffix = `${Date.now()}-${crypto.randomBytes(6).toString('hex')}`;
      const filename = `${path.basename(file.originalname, ext)}-${uniqueSuffix}${ext}`;
      
      // Ensure directory exists
      const uploadDir = path.join(process.cwd(), 'uploads', directory);
      await fs.mkdir(uploadDir, { recursive: true });
      
      // Save file
      const filePath = path.join(uploadDir, filename);
      await fs.writeFile(filePath, file.buffer);
      
      return {
        filename,
        originalName: file.originalname,
        mimetype: file.mimetype,
        size: file.size,
        path: filePath
      };
    } catch (error) {
      logger.error('File upload error:', error);
      throw new Error('Failed to upload file');
    }
  },

  async deleteFile(filePath: string): Promise<void> {
    try {
      await fs.unlink(filePath);
    } catch (error) {
      logger.error('File deletion error:', error);
      throw new Error('Failed to delete file');
    }
  },

  async moveFile(oldPath: string, newPath: string): Promise<void> {
    try {
      // Ensure new directory exists
      await fs.mkdir(path.dirname(newPath), { recursive: true });
      // Move file
      await fs.rename(oldPath, newPath);
    } catch (error) {
      logger.error('File move error:', error);
      throw new Error('Failed to move file');
    }
  },

  async getFileInfo(filePath: string): Promise<{
    exists: boolean;
    size?: number;
    createdAt?: Date;
    modifiedAt?: Date;
  }> {
    try {
      const stats = await fs.stat(filePath);
      return {
        exists: true,
        size: stats.size,
        createdAt: stats.birthtime,
        modifiedAt: stats.mtime
      };
    } catch (error) {
      return { exists: false };
    }
  },

  validateFile(file: Express.Multer.File, options: {
    maxSize?: number;
    allowedTypes?: string[];
  }): { valid: boolean; error?: string } {
    // Check file size
    if (options.maxSize && file.size > options.maxSize) {
      return {
        valid: false,
        error: `File size exceeds maximum allowed size of ${options.maxSize} bytes`
      };
    }
    
    // Check file type
    if (options.allowedTypes && !options.allowedTypes.includes(file.mimetype)) {
      return {
        valid: false,
        error: `File type ${file.mimetype} is not allowed`
      };
    }
    
    return { valid: true };
  },

  generatePublicUrl(filename: string, directory: string): string {
    return `/uploads/${directory}/${filename}`;
  }
};