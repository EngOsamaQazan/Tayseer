import { Express } from 'express';
import swaggerJsdoc from 'swagger-jsdoc';
import swaggerUi from 'swagger-ui-express';
import { logger } from './logger';
import envConfig from './env.config';

// ==================== إعدادات API ====================

export const API_PREFIX = `/api/${envConfig.get('API_VERSION')}`;

export const API_CONFIG = {
  version: envConfig.get('API_VERSION'),
  prefix: API_PREFIX,
  pagination: {
    defaultLimit: envConfig.get('PAGINATION_DEFAULT_LIMIT'),
    maxLimit: envConfig.get('PAGINATION_MAX_LIMIT')
  },
  rateLimit: {
    windowMs: envConfig.get('RATE_LIMIT_WINDOW'),
    max: envConfig.get('RATE_LIMIT_MAX'),
    skipSuccessfulRequests: envConfig.get('RATE_LIMIT_SKIP_SUCCESSFUL_REQUESTS')
  },
  cors: {
    origin: envConfig.get('CORS_ORIGIN'),
    credentials: envConfig.get('CORS_CREDENTIALS'),
    methods: ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
    allowedHeaders: ['Content-Type', 'Authorization', 'X-Tenant-ID', 'X-Request-ID'],
    exposedHeaders: ['X-Total-Count', 'X-Page-Count']
  },
  compression: {
    enabled: envConfig.get('ENABLE_COMPRESSION'),
    level: envConfig.get('COMPRESSION_LEVEL')
  },
  upload: {
    maxFileSize: envConfig.get('MAX_FILE_SIZE'),
    maxRequestSize: envConfig.get('MAX_REQUEST_SIZE')
  }
};

// ==================== إعدادات Swagger ====================

const swaggerOptions: swaggerJsdoc.Options = {
  definition: {
    openapi: '3.0.0',
    info: {
      title: 'منصة تيسير - API Documentation',
      version: envConfig.get('API_VERSION'),
      description: `
        وثائق API الخاصة بمنصة تيسير لإدارة الأعمال
        
        ## المميزات الرئيسية:
        - إدارة العملاء والموردين
        - إدارة المخزون والمنتجات
        - إدارة العقود والمشاريع
        - المحاسبة والفواتير
        - إدارة الموظفين والمهام
        - التقارير والإحصائيات
        
        ## المصادقة:
        يستخدم API نظام JWT للمصادقة. يجب تضمين رمز المصادقة في رأس الطلب:
        
        \`Authorization: Bearer <token>\`
        
        ## معدل الطلبات:
        - الحد الأقصى: ${API_CONFIG.rateLimit.max} طلب لكل ${API_CONFIG.rateLimit.windowMs / 60000} دقيقة
        - يتم إعادة تعيين العداد كل ${API_CONFIG.rateLimit.windowMs / 60000} دقيقة
      `,
      contact: {
        name: 'فريق الدعم الفني',
        email: 'support@tayseer.sa',
        url: 'https://tayseer.sa/support'
      },
      license: {
        name: 'رخصة خاصة',
        url: 'https://tayseer.sa/license'
      }
    },
    servers: [
      {
        url: `${envConfig.get('APP_URL')}${API_PREFIX}`,
        description: envConfig.isProduction() ? 'خادم الإنتاج' : 
                     envConfig.isDevelopment() ? 'خادم التطوير' : 'خادم الاختبار'
      }
    ],
    components: {
      securitySchemes: {
        bearerAuth: {
          type: 'http',
          scheme: 'bearer',
          bearerFormat: 'JWT',
          description: 'أدخل رمز JWT'
        },
        apiKey: {
          type: 'apiKey',
          in: 'header',
          name: 'X-API-Key',
          description: 'مفتاح API (للاستخدامات الخاصة)'
        }
      },
      schemas: {
        Error: {
          type: 'object',
          properties: {
            success: {
              type: 'boolean',
              example: false
            },
            error: {
              type: 'string',
              example: 'حدث خطأ في معالجة الطلب'
            },
            message: {
              type: 'string',
              example: 'تفاصيل الخطأ'
            },
            code: {
              type: 'string',
              example: 'ERROR_CODE'
            },
            statusCode: {
              type: 'integer',
              example: 400
            }
          }
        },
        Pagination: {
          type: 'object',
          properties: {
            page: {
              type: 'integer',
              minimum: 1,
              default: 1,
              description: 'رقم الصفحة'
            },
            limit: {
              type: 'integer',
              minimum: 1,
              maximum: API_CONFIG.pagination.maxLimit,
              default: API_CONFIG.pagination.defaultLimit,
              description: 'عدد العناصر في الصفحة'
            },
            total: {
              type: 'integer',
              description: 'إجمالي عدد العناصر'
            },
            totalPages: {
              type: 'integer',
              description: 'إجمالي عدد الصفحات'
            }
          }
        },
        Tenant: {
          type: 'object',
          properties: {
            id: {
              type: 'string',
              format: 'uuid',
              description: 'معرف المستأجر'
            },
            name: {
              type: 'string',
              description: 'اسم الشركة'
            },
            plan: {
              type: 'string',
              enum: ['basic', 'professional', 'enterprise'],
              description: 'الخطة المشترك بها'
            },
            isActive: {
              type: 'boolean',
              description: 'حالة التفعيل'
            }
          }
        },
        AuditLog: {
          type: 'object',
          properties: {
            id: {
              type: 'string',
              format: 'uuid'
            },
            userId: {
              type: 'string',
              format: 'uuid'
            },
            action: {
              type: 'string',
              description: 'نوع الإجراء'
            },
            entity: {
              type: 'string',
              description: 'الكيان المتأثر'
            },
            entityId: {
              type: 'string',
              description: 'معرف الكيان'
            },
            changes: {
              type: 'object',
              description: 'التغييرات التي تمت'
            },
            timestamp: {
              type: 'string',
              format: 'date-time'
            },
            ipAddress: {
              type: 'string',
              format: 'ipv4'
            },
            userAgent: {
              type: 'string'
            }
          }
        }
      },
      parameters: {
        tenantId: {
          name: 'X-Tenant-ID',
          in: 'header',
          required: true,
          description: 'معرف المستأجر',
          schema: {
            type: 'string',
            format: 'uuid'
          }
        },
        pageParam: {
          name: 'page',
          in: 'query',
          description: 'رقم الصفحة',
          schema: {
            type: 'integer',
            minimum: 1,
            default: 1
          }
        },
        limitParam: {
          name: 'limit',
          in: 'query',
          description: 'عدد العناصر في الصفحة',
          schema: {
            type: 'integer',
            minimum: 1,
            maximum: API_CONFIG.pagination.maxLimit,
            default: API_CONFIG.pagination.defaultLimit
          }
        },
        searchParam: {
          name: 'search',
          in: 'query',
          description: 'نص البحث',
          schema: {
            type: 'string'
          }
        },
        sortParam: {
          name: 'sort',
          in: 'query',
          description: 'ترتيب النتائج (field:asc أو field:desc)',
          schema: {
            type: 'string',
            pattern: '^[a-zA-Z]+:(asc|desc)$'
          }
        },
        filterParam: {
          name: 'filter',
          in: 'query',
          description: 'فلترة النتائج',
          schema: {
            type: 'object',
            additionalProperties: true
          },
          style: 'deepObject',
          explode: true
        }
      },
      responses: {
        NotFound: {
          description: 'المورد غير موجود',
          content: {
            'application/json': {
              schema: {
                $ref: '#/components/schemas/Error'
              },
              example: {
                success: false,
                error: 'المورد المطلوب غير موجود',
                statusCode: 404
              }
            }
          }
        },
        Unauthorized: {
          description: 'غير مصرح',
          content: {
            'application/json': {
              schema: {
                $ref: '#/components/schemas/Error'
              },
              example: {
                success: false,
                error: 'يجب تسجيل الدخول للوصول إلى هذا المورد',
                statusCode: 401
              }
            }
          }
        },
        Forbidden: {
          description: 'ممنوع الوصول',
          content: {
            'application/json': {
              schema: {
                $ref: '#/components/schemas/Error'
              },
              example: {
                success: false,
                error: 'ليس لديك صلاحية الوصول إلى هذا المورد',
                statusCode: 403
              }
            }
          }
        },
        BadRequest: {
          description: 'طلب خاطئ',
          content: {
            'application/json': {
              schema: {
                $ref: '#/components/schemas/Error'
              },
              example: {
                success: false,
                error: 'البيانات المرسلة غير صحيحة',
                statusCode: 400
              }
            }
          }
        },
        ServerError: {
          description: 'خطأ في الخادم',
          content: {
            'application/json': {
              schema: {
                $ref: '#/components/schemas/Error'
              },
              example: {
                success: false,
                error: 'حدث خطأ في الخادم',
                statusCode: 500
              }
            }
          }
        },
        RateLimitExceeded: {
          description: 'تجاوز حد معدل الطلبات',
          content: {
            'application/json': {
              schema: {
                $ref: '#/components/schemas/Error'
              },
              example: {
                success: false,
                error: 'تم تجاوز الحد الأقصى لعدد الطلبات',
                statusCode: 429
              }
            }
          },
          headers: {
            'X-RateLimit-Limit': {
              description: 'الحد الأقصى لعدد الطلبات',
              schema: {
                type: 'integer'
              }
            },
            'X-RateLimit-Remaining': {
              description: 'عدد الطلبات المتبقية',
              schema: {
                type: 'integer'
              }
            },
            'X-RateLimit-Reset': {
              description: 'وقت إعادة تعيين العداد',
              schema: {
                type: 'string',
                format: 'date-time'
              }
            }
          }
        }
      }
    },
    tags: [
      {
        name: 'المصادقة',
        description: 'عمليات تسجيل الدخول والمصادقة'
      },
      {
        name: 'العملاء',
        description: 'إدارة العملاء والموردين'
      },
      {
        name: 'المخزون',
        description: 'إدارة المخزون والمنتجات'
      },
      {
        name: 'العقود',
        description: 'إدارة العقود والمشاريع'
      },
      {
        name: 'المحاسبة',
        description: 'العمليات المحاسبية والمالية'
      },
      {
        name: 'الموظفون',
        description: 'إدارة الموظفين والصلاحيات'
      },
      {
        name: 'المهام',
        description: 'إدارة المهام والمشاريع'
      },
      {
        name: 'القانونية',
        description: 'الشؤون القانونية والامتثال'
      }
    ]
  }
};