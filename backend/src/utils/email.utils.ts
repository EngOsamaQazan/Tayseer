// أدوات البريد الإلكتروني
import nodemailer from 'nodemailer';
import { logger } from '@/config/logger';
import handlebars from 'handlebars';
import fs from 'fs/promises';
import path from 'path';

// إعدادات البريد الإلكتروني
const emailConfig = {
  host: process.env.SMTP_HOST || 'smtp.gmail.com',
  port: parseInt(process.env.SMTP_PORT || '587'),
  secure: process.env.SMTP_SECURE === 'true',
  auth: {
    user: process.env.SMTP_USER || '',
    pass: process.env.SMTP_PASS || ''
  },
  from: {
    name: process.env.SMTP_FROM_NAME || 'منصة تيسير',
    email: process.env.SMTP_FROM_EMAIL || 'noreply@tayseer.com'
  }
};

// إنشاء محول البريد
const transporter = nodemailer.createTransport({
  host: emailConfig.host,
  port: emailConfig.port,
  secure: emailConfig.secure,
  auth: emailConfig.auth
});

// واجهة خيارات البريد
interface EmailOptions {
  to: string | string[];
  subject: string;
  template?: string;
  data?: Record<string, any>;
  html?: string;
  text?: string;
  attachments?: Array<{
    filename: string;
    path?: string;
    content?: Buffer;
    contentType?: string;
  }>;
  cc?: string | string[];
  bcc?: string | string[];
  replyTo?: string;
}

// قوالب البريد الافتراضية
const defaultTemplates = {
  welcome: {
    subject: 'مرحباً بك في منصة تيسير',
    template: 'welcome'
  },
  resetPassword: {
    subject: 'إعادة تعيين كلمة المرور',
    template: 'reset-password'
  },
  verification: {
    subject: 'تأكيد البريد الإلكتروني',
    template: 'email-verification'
  },
  invoice: {
    subject: 'فاتورة جديدة',
    template: 'invoice'
  },
  payment: {
    subject: 'تأكيد الدفع',
    template: 'payment-confirmation'
  },
  notification: {
    subject: 'إشعار جديد',
    template: 'notification'
  },
  reminder: {
    subject: 'تذكير',
    template: 'reminder'
  },
  report: {
    subject: 'تقرير جديد',
    template: 'report'
  }
};

// تحميل وتجميع القالب
export const loadTemplate = async (
  templateName: string,
  data: Record<string, any>
): Promise<string> => {
  try {
    const templatePath = path.join(
      process.cwd(),
      'templates',
      'emails',
      `${templateName}.hbs`
    );
    
    const templateContent = await fs.readFile(templatePath, 'utf-8');
    const template = handlebars.compile(templateContent);
    
    // إضافة مساعدات Handlebars
    handlebars.registerHelper('formatDate', (date: Date) => {
      return new Date(date).toLocaleDateString('ar-SA');
    });
    
    handlebars.registerHelper('formatCurrency', (amount: number) => {
      return `${amount.toFixed(2)} ريال`;
    });
    
    handlebars.registerHelper('if_eq', function(a: any, b: any, opts: any) {
      if (a === b) {
        return opts.fn(this);
      } else {
        return opts.inverse(this);
      }
    });
    
    return template(data);
  } catch (error) {
    logger.error('Error loading email template:', error);
    throw new Error(`Failed to load template: ${templateName}`);
  }
};

// إرسال بريد إلكتروني
export const sendEmail = async (options: EmailOptions): Promise<boolean> => {
  try {
    let html = options.html;
    
    // تحميل القالب إذا تم تحديده
    if (options.template && options.data) {
      html = await loadTemplate(options.template, options.data);
    }
    
    // إعداد خيارات البريد
    const mailOptions = {
      from: `${emailConfig.from.name} <${emailConfig.from.email}>`,
      to: Array.isArray(options.to) ? options.to.join(', ') : options.to,
      subject: options.subject,
      html: html,
      text: options.text,
      attachments: options.attachments,
      cc: options.cc ? (Array.isArray(options.cc) ? options.cc.join(', ') : options.cc) : undefined,
      bcc: options.bcc ? (Array.isArray(options.bcc) ? options.bcc.join(', ') : options.bcc) : undefined,
      replyTo: options.replyTo
    };
    
    // إرسال البريد
    const info = await transporter.sendMail(mailOptions);
    
    logger.info('Email sent successfully:', {
      messageId: info.messageId,
      to: options.to,
      subject: options.subject
    });
    
    return true;
  } catch (error) {
    logger.error('Error sending email:', error);
    return false;
  }
};

// إرسال بريد الترحيب
export const sendWelcomeEmail = async (
  email: string,
  data: {
    name: string;
    tenantName: string;
    loginUrl: string;
  }
): Promise<boolean> => {
  return sendEmail({
    to: email,
    subject: defaultTemplates.welcome.subject,
    template: defaultTemplates.welcome.template,
    data
  });
};

// إرسال بريد إعادة تعيين كلمة المرور
export const sendPasswordResetEmail = async (
  email: string,
  data: {
    name: string;
    resetLink: string;
    expiresIn: string;
  }
): Promise<boolean> => {
  return sendEmail({
    to: email,
    subject: defaultTemplates.resetPassword.subject,
    template: defaultTemplates.resetPassword.template,
    data
  });
};

// إرسال بريد التحقق
export const sendVerificationEmail = async (
  email: string,
  data: {
    name: string;
    verificationLink: string;
    code?: string;
  }
): Promise<boolean> => {
  return sendEmail({
    to: email,
    subject: defaultTemplates.verification.subject,
    template: defaultTemplates.verification.template,
    data
  });
};

// إرسال الفاتورة بالبريد
export const sendInvoiceEmail = async (
  email: string,
  data: {
    customerName: string;
    invoiceNumber: string;
    amount: number;
    dueDate: Date;
    items: Array<{
      description: string;
      quantity: number;
      price: number;
      total: number;
    }>;
  },
  attachment?: Buffer
): Promise<boolean> => {
  const attachments = attachment
    ? [{
        filename: `invoice-${data.invoiceNumber}.pdf`,
        content: attachment,
        contentType: 'application/pdf'
      }]
    : undefined;
  
  return sendEmail({
    to: email,
    subject: `${defaultTemplates.invoice.subject} - ${data.invoiceNumber}`,
    template: defaultTemplates.invoice.template,
    data,
    attachments
  });
};

// إرسال تأكيد الدفع
export const sendPaymentConfirmationEmail = async (
  email: string,
  data: {
    customerName: string;
    paymentId: string;
    amount: number;
    paymentMethod: string;
    date: Date;
    invoiceNumber?: string;
  }
): Promise<boolean> => {
  return sendEmail({
    to: email,
    subject: defaultTemplates.payment.subject,
    template: defaultTemplates.payment.template,
    data
  });
};

// إرسال إشعار عام
export const sendNotificationEmail = async (
  email: string | string[],
  subject: string,
  data: {
    title: string;
    message: string;
    actionUrl?: string;
    actionText?: string;
  }
): Promise<boolean> => {
  return sendEmail({
    to: email,
    subject: subject || defaultTemplates.notification.subject,
    template: defaultTemplates.notification.template,
    data
  });
};

// إرسال تذكير
export const sendReminderEmail = async (
  email: string,
  data: {
    name: string;
    title: string;
    description: string;
    dueDate?: Date;
    actionUrl?: string;
    actionText?: string;
  }
): Promise<boolean> => {
  return sendEmail({
    to: email,
    subject: `${defaultTemplates.reminder.subject}: ${data.title}`,
    template: defaultTemplates.reminder.template,
    data
  });
};

// إرسال تقرير
export const sendReportEmail = async (
  email: string | string[],
  data: {
    reportTitle: string;
    reportPeriod: string;
    summary: string;
    highlights?: string[];
  },
  attachment?: Buffer
): Promise<boolean> => {
  const attachments = attachment
    ? [{
        filename: `report-${Date.now()}.pdf`,
        content: attachment,
        contentType: 'application/pdf'
      }]
    : undefined;
  
  return sendEmail({
    to: email,
    subject: `${defaultTemplates.report.subject}: ${data.reportTitle}`,
    template: defaultTemplates.report.template,
    data,
    attachments
  });
};

// إرسال بريد مخصص
export const sendCustomEmail = async (
  to: string | string[],
  subject: string,
  html: string,
  options?: {
    text?: string;
    attachments?: EmailOptions['attachments'];
    cc?: string | string[];
    bcc?: string | string[];
    replyTo?: string;
  }
): Promise<boolean> => {
  return sendEmail({
    to,
    subject,
    html,
    ...options
  });
};

// التحقق من صحة البريد الإلكتروني
export const validateEmail = (email: string): boolean => {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return emailRegex.test(email);
};

// التحقق من إعدادات البريد
export const verifyEmailConfiguration = async (): Promise<boolean> => {
  try {
    await transporter.verify();
    logger.info('Email configuration verified successfully');
    return true;
  } catch (error) {
    logger.error('Email configuration verification failed:', error);
    return false;
  }
};

// قائمة انتظار البريد الإلكتروني
interface EmailQueueItem {
  id: string;
  options: EmailOptions;
  retries: number;
  createdAt: Date;
}

const emailQueue: EmailQueueItem[] = [];
let isProcessingQueue = false;

// إضافة بريد إلى قائمة الانتظار
export const queueEmail = (options: EmailOptions): string => {
  const id = `email-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
  
  emailQueue.push({
    id,
    options,
    retries: 0,
    createdAt: new Date()
  });
  
  // بدء معالجة القائمة إذا لم تكن قيد المعالجة
  if (!isProcessingQueue) {
    processEmailQueue();
  }
  
  return id;
};

// معالجة قائمة انتظار البريد
const processEmailQueue = async (): Promise<void> => {
  if (isProcessingQueue || emailQueue.length === 0) return;
  
  isProcessingQueue = true;
  
  while (emailQueue.length > 0) {
    const item = emailQueue.shift();
    if (!item) continue;
    
    try {
      await sendEmail(item.options);
    } catch (error) {
      logger.error(`Failed to send queued email ${item.id}:`, error);
      
      // إعادة المحاولة إذا لم يتجاوز عدد المحاولات الحد الأقصى
      if (item.retries < 3) {
        item.retries++;
        emailQueue.push(item);
      }
    }
    
    // تأخير بسيط بين الرسائل لتجنب حد المعدل
    await new Promise(resolve => setTimeout(resolve, 1000));
  }
  
  isProcessingQueue = false;
};

// حالة قائمة انتظار البريد
export const getEmailQueueStatus = (): {
  pending: number;
  processing: boolean;
} => {
  return {
    pending: emailQueue.length,
    processing: isProcessingQueue
  };
};

// مسح قائمة انتظار البريد
export const clearEmailQueue = (): void => {
  emailQueue.length = 0;
  logger.info('Email queue cleared');
};