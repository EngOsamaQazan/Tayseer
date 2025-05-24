import crypto from 'crypto';
import bcrypt from 'bcrypt';
import jwt from 'jsonwebtoken';
import { logger } from '../config/logger';

// إعدادات التشفير
const ALGORITHM = 'aes-256-gcm';
const IV_LENGTH = 16;
const TAG_LENGTH = 16;
const SALT_LENGTH = 32;
const KEY_LENGTH = 32;
const ITERATIONS = 100000;

// توليد مفتاح من كلمة المرور
const deriveKey = (password: string, salt: Buffer): Buffer => {
  return crypto.pbkdf2Sync(password, salt, ITERATIONS, KEY_LENGTH, 'sha256');
};

// تشفير النص
export const encrypt = (text: string, password?: string): string => {
  try {
    const encryptionPassword = password || process.env.ENCRYPTION_KEY!;
    if (!encryptionPassword) {
      throw new Error('مفتاح التشفير غير موجود');
    }
    
    // توليد salt و IV عشوائيين
    const salt = crypto.randomBytes(SALT_LENGTH);
    const iv = crypto.randomBytes(IV_LENGTH);
    
    // اشتقاق المفتاح من كلمة المرور
    const key = deriveKey(encryptionPassword, salt);
    
    // إنشاء cipher
    const cipher = crypto.createCipheriv(ALGORITHM, key, iv);
    
    // تشفير النص
    const encrypted = Buffer.concat([
      cipher.update(text, 'utf8'),
      cipher.final()
    ]);
    
    // الحصول على tag المصادقة
    const tag = cipher.getAuthTag();
    
    // دمج جميع المكونات
    const combined = Buffer.concat([salt, iv, tag, encrypted]);
    
    return combined.toString('base64');
  } catch (error) {
    logger.error('خطأ في التشفير:', error);
    throw new Error('فشل تشفير البيانات');
  }
};

// فك التشفير
export const decrypt = (encryptedText: string, password?: string): string => {
  try {
    const decryptionPassword = password || process.env.ENCRYPTION_KEY!;
    if (!decryptionPassword) {
      throw new Error('مفتاح فك التشفير غير موجود');
    }
    
    // تحويل من base64
    const combined = Buffer.from(encryptedText, 'base64');
    
    // استخراج المكونات
    const salt = combined.slice(0, SALT_LENGTH);
    const iv = combined.slice(SALT_LENGTH, SALT_LENGTH + IV_LENGTH);
    const tag = combined.slice(SALT_LENGTH + IV_LENGTH, SALT_LENGTH + IV_LENGTH + TAG_LENGTH);
    const encrypted = combined.slice(SALT_LENGTH + IV_LENGTH + TAG_LENGTH);
    
    // اشتقاق المفتاح من كلمة المرور
    const key = deriveKey(decryptionPassword, salt);
    
    // إنشاء decipher
    const decipher = crypto.createDecipheriv(ALGORITHM, key, iv);
    decipher.setAuthTag(tag);
    
    // فك التشفير
    const decrypted = Buffer.concat([
      decipher.update(encrypted),
      decipher.final()
    ]);
    
    return decrypted.toString('utf8');
  } catch (error) {
    logger.error('خطأ في فك التشفير:', error);
    throw new Error('فشل فك تشفير البيانات');
  }
};

// تجزئة كلمة المرور
export const hashPassword = async (password: string): Promise<string> => {
  const saltRounds = parseInt(process.env.BCRYPT_ROUNDS || '10');
  return bcrypt.hash(password, saltRounds);
};

// مقارنة كلمة المرور
export const comparePassword = async (
  password: string,
  hashedPassword: string
): Promise<boolean> => {
  return bcrypt.compare(password, hashedPassword);
};

// توليد رمز عشوائي
export const generateRandomToken = (length: number = 32): string => {
  return crypto.randomBytes(length).toString('hex');
};

// توليد رمز رقمي (OTP)
export const generateOTP = (length: number = 6): string => {
  const digits = '0123456789';
  let otp = '';
  
  for (let i = 0; i < length; i++) {
    const randomIndex = crypto.randomInt(0, digits.length);
    otp += digits[randomIndex];
  }
  
  return otp;
};

// توليد UUID
export const generateUUID = (): string => {
  return crypto.randomUUID();
};

// واجهة معلومات JWT
interface JWTPayload {
  userId: string;
  tenantId: string;
  email: string;
  role: string;
  permissions?: string[];
}

// واجهة خيارات JWT
interface JWTOptions {
  expiresIn?: string | number;
  audience?: string;
  issuer?: string;
}

// توليد JWT
export const generateJWT = (
  payload: JWTPayload,
  options: JWTOptions = {}
): string => {
  const secret = process.env.JWT_SECRET!;
  if (!secret) {
    throw new Error('JWT secret غير موجود');
  }
  
  const defaultOptions: jwt.SignOptions = {
    expiresIn: options.expiresIn || process.env.JWT_EXPIRY || '24h',
    audience: options.audience || process.env.JWT_AUDIENCE,
    issuer: options.issuer || process.env.JWT_ISSUER || 'tayseer-platform',
    algorithm: 'HS256'
  };
  
  return jwt.sign(payload, secret, defaultOptions);
};

// التحقق من JWT
export const verifyJWT = <T = JWTPayload>(
  token: string,
  options: jwt.VerifyOptions = {}
): T => {
  const secret = process.env.JWT_SECRET!;
  if (!secret) {
    throw new Error('JWT secret غير موجود');
  }
  
  const defaultOptions: jwt.VerifyOptions = {
    audience: options.audience || process.env.JWT_AUDIENCE,
    issuer: options.issuer || process.env.JWT_ISSUER || 'tayseer-platform',
    algorithms: ['HS256'],
    ...options
  };
  
  return jwt.verify(token, secret, defaultOptions) as T;
};

// توليد refresh token
export const generateRefreshToken = (): {
  token: string;
  hashedToken: string;
} => {
  const token = generateRandomToken(64);
  const hashedToken = crypto
    .createHash('sha256')
    .update(token)
    .digest('hex');
  
  return { token, hashedToken };
};

// تجزئة البيانات باستخدام SHA256
export const hashData = (data: string): string => {
  return crypto
    .createHash('sha256')
    .update(data)
    .digest('hex');
};

// التحقق من قوة كلمة المرور
export const validatePasswordStrength = (password: string): {
  isValid: boolean;
  errors: string[];
} => {
  const errors: string[] = [];
  
  if (password.length < 8) {
    errors.push('كلمة المرور يجب أن تكون 8 أحرف على الأقل');
  }
  
  if (!/[A-Z]/.test(password)) {
    errors.push('كلمة المرور يجب أن تحتوي على حرف كبير');
  }
  
  if (!/[a-z]/.test(password)) {
    errors.push('كلمة المرور يجب أن تحتوي على حرف صغير');
  }
  
  if (!/\d/.test(password)) {
    errors.push('كلمة المرور يجب أن تحتوي على رقم');
  }
  
  if (!/[!@#$%^&*(),.?":{}|<>]/.test(password)) {
    errors.push('كلمة المرور يجب أن تحتوي على رمز خاص');
  }
  
  return {
    isValid: errors.length === 0,
    errors
  };
};

// إخفاء البيانات الحساسة
export const maskSensitiveData = (
  data: string,
  visibleChars: number = 4
): string => {
  if (data.length <= visibleChars * 2) {
    return '*'.repeat(data.length);
  }
  
  const start = data.slice(0, visibleChars);
  const end = data.slice(-visibleChars);
  const masked = '*'.repeat(Math.max(data.length - visibleChars * 2, 4));
  
  return `${start}${masked}${end}`;
};

// تشفير وفك تشفير الكائنات
export const encryptObject = <T>(obj: T, password?: string): string => {
  const jsonString = JSON.stringify(obj);
  return encrypt(jsonString, password);
};

export const decryptObject = <T>(encryptedText: string, password?: string): T => {
  const jsonString = decrypt(encryptedText, password);
  return JSON.parse(jsonString) as T;
};

// التوقيع الرقمي
export const signData = (data: string, privateKey: string): string => {
  const sign = crypto.createSign('RSA-SHA256');
  sign.update(data);
  return sign.sign(privateKey, 'base64');
};

// التحقق من التوقيع
export const verifySignature = (
  data: string,
  signature: string,
  publicKey: string
): boolean => {
  const verify = crypto.createVerify('RSA-SHA256');
  verify.update(data);
  return verify.verify(publicKey, signature, 'base64');
};

// توليد مفاتيح RSA
export const generateKeyPair = (): {
  publicKey: string;
  privateKey: string;
} => {
  const { publicKey, privateKey } = crypto.generateKeyPairSync('rsa', {
    modulusLength: 2048,
    publicKeyEncoding: {
      type: 'spki',
      format: 'pem'
    },
    privateKeyEncoding: {
      type: 'pkcs8',
      format: 'pem'
    }
  });
  
  return { publicKey, privateKey };
};

// تحقق من صحة البريد الإلكتروني
export const isValidEmail = (email: string): boolean => {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return emailRegex.test(email);
};

// تحقق من صحة رقم الهاتف السعودي
export const isValidSaudiPhone = (phone: string): boolean => {
  // إزالة المسافات والشرطات
  const cleanPhone = phone.replace(/[\s-]/g, '');
  // التحقق من الصيغة السعودية
  const saudiPhoneRegex = /^(\+966|966|0)?5[0-9]{8}$/;
  return saudiPhoneRegex.test(cleanPhone);
};

// تنسيق رقم الهاتف السعودي
export const formatSaudiPhone = (phone: string): string => {
  const cleanPhone = phone.replace(/[\s-]/g, '');
  const match = cleanPhone.match(/^(\+966|966|0)?(5[0-9]{8})$/);
  
  if (match) {
    return `+966${match[2]}`;
  }
  
  return phone;
};