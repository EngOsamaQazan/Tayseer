export interface Product {
  id: string;
  name: string;
  nameEn?: string | null;
  sku: string;
  barcode?: string | null;
  description?: string | null;
  descriptionEn?: string | null;
  categoryId: string;
  type: 'product' | 'service';
  unitId?: string | null;
  price: number;
  cost?: number | null;
  taxRate: number;
  trackInventory: boolean;
  allowBackorder: boolean;
  minStockLevel: number;
  reorderLevel: number;
  isActive: boolean;
  imageUrl?: string | null;
  attributes?: Record<string, any> | null;
  tenantId: string;
  createdBy: string;
  updatedBy?: string | null;
  deletedAt?: Date | null;
  createdAt: Date;
  updatedAt: Date;
  category?: ProductCategory;
  unit?: Unit;
  variants?: ProductVariant[];
  priceHistory?: ProductPriceHistory[];
  inventory?: Inventory[];
}

export interface ProductCategory {
  id: string;
  name: string;
  nameEn?: string | null;
  description?: string | null;
  parentId?: string | null;
  isActive: boolean;
  tenantId: string;
  createdAt: Date;
  updatedAt: Date;
  parent?: ProductCategory | null;
  children?: ProductCategory[];
  products?: Product[];
}

export interface Unit {
  id: string;
  name: string;
  nameEn?: string | null;
  symbol: string;
  isActive: boolean;
  tenantId: string;
  createdAt: Date;
  updatedAt: Date;
}

export interface ProductVariant {
  id: string;
  productId: string;
  name: string;
  sku: string;
  price: number;
  cost?: number | null;
  attributes?: Record<string, string> | null;
  isActive: boolean;
  tenantId: string;
  createdAt: Date;
  updatedAt: Date;
  product?: Product;
  inventory?: Inventory[];
}

export interface ProductPriceHistory {
  id: string;
  productId: string;
  oldPrice: number;
  newPrice: number;
  oldCost?: number | null;
  newCost?: number | null;
  changedBy: string;
  reason?: string | null;
  createdAt: Date;
  product?: Product;
}

export interface Inventory {
  id: string;
  productId: string;
  variantId?: string | null;
  warehouseId: string;
  quantity: number;
  reservedQuantity: number;
  availableQuantity: number;
  lastStockTake?: Date | null;
  tenantId: string;
  createdAt: Date;
  updatedAt: Date;
  product?: Product;
  variant?: ProductVariant | null;
  warehouse?: Warehouse;
  transactions?: InventoryTransaction[];
}

export interface Warehouse {
  id: string;
  name: string;
  code: string;
  address?: string | null;
  isActive: boolean;
  isDefault: boolean;
  tenantId: string;
  createdAt: Date;
  updatedAt: Date;
}

export interface InventoryTransaction {
  id: string;
  inventoryId: string;
  type: 'in' | 'out' | 'adjustment' | 'transfer';
  quantity: number;
  previousQuantity: number;
  newQuantity: number;
  reference?: string | null;
  referenceType?: string | null;
  referenceId?: string | null;
  reason?: string | null;
  createdBy: string;
  tenantId: string;
  createdAt: Date;
  inventory?: Inventory;
}

// DTOs للإنشاء والتحديث
export interface CreateProductInput {
  name: string;
  nameEn?: string;
  sku: string;
  barcode?: string;
  description?: string;
  descriptionEn?: string;
  categoryId: string;
  type: 'product' | 'service';
  unitId?: string;
  price: number;
  cost?: number;
  taxRate?: number;
  trackInventory?: boolean;
  allowBackorder?: boolean;
  minStockLevel?: number;
  reorderLevel?: number;
  isActive?: boolean;
  imageUrl?: string;
  attributes?: Record<string, any>;
}

export interface UpdateProductInput {
  name?: string;
  nameEn?: string;
  sku?: string;
  barcode?: string;
  description?: string;
  descriptionEn?: string;
  categoryId?: string;
  type?: 'product' | 'service';
  unitId?: string;
  price?: number;
  cost?: number;
  taxRate?: number;
  trackInventory?: boolean;
  allowBackorder?: boolean;
  minStockLevel?: number;
  reorderLevel?: number;
  isActive?: boolean;
  imageUrl?: string;
  attributes?: Record<string, any>;
}

export interface CreateProductVariantInput {
  productId: string;
  name: string;
  sku: string;
  price: number;
  cost?: number;
  attributes?: Record<string, string>;
  isActive?: boolean;
}

export interface UpdateProductVariantInput {
  name?: string;
  sku?: string;
  price?: number;
  cost?: number;
  attributes?: Record<string, string>;
  isActive?: boolean;
}

export interface ProductSearchParams {
  page?: number;
  limit?: number;
  search?: string;
  categoryId?: string;
  type?: 'product' | 'service';
  minPrice?: number;
  maxPrice?: number;
  inStock?: boolean;
  sortBy?: string;
  sortOrder?: 'asc' | 'desc';
}

export interface ProductStatistics {
  totalProducts: number;
  activeProducts: number;
  lowStockProducts: number;
  outOfStockProducts: number;
  totalInventoryValue: number;
  productsByCategory: Record<string, number>;
}

export interface ProductImportResult {
  success: number;
  failed: number;
  errors: Array<{
    row: number;
    message: string;
    data?: any;
  }>;
}

export interface BulkPriceUpdateInput {
  productId: string;
  price: number;
  cost?: number;
}