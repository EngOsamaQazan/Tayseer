// واجهات المخزون

export interface Inventory {
  id: string;
  productId: string;
  warehouseId: string;
  quantity: number;
  reservedQuantity: number;
  availableQuantity: number;
  minStockLevel: number;
  reorderPoint: number;
  reorderQuantity: number;
  maxStockLevel?: number;
  lastStockTakeDate?: Date;
  averageCost: number;
  lastCost: number;
  createdAt: Date;
  updatedAt: Date;
  tenantId: string;
  product?: Product;
  warehouse?: Warehouse;
}

export interface InventoryTransaction {
  id: string;
  type: InventoryTransactionType;
  productId: string;
  warehouseId: string;
  quantity: number;
  unitCost?: number;
  totalCost?: number;
  balanceBefore: number;
  balanceAfter: number;
  referenceType?: string;
  referenceId?: string;
  notes?: string;
  metadata?: Record<string, any>;
  performedBy: string;
  performedAt: Date;
  createdAt: Date;
  tenantId: string;
  product?: Product;
  warehouse?: Warehouse;
  user?: User;
}

export enum InventoryTransactionType {
  IN = 'IN',
  OUT = 'OUT',
  ADJUSTMENT = 'ADJUSTMENT',
  TRANSFER = 'TRANSFER',
  RETURN = 'RETURN',
  DAMAGE = 'DAMAGE',
}

export interface StockTake {
  id: string;
  warehouseId: string;
  status: StockTakeStatus;
  startedAt: Date;
  completedAt?: Date;
  performedBy: string;
  approvedBy?: string;
  approvedAt?: Date;
  notes?: string;
  items: StockTakeItem[];
  discrepancyValue?: number;
  createdAt: Date;
  updatedAt: Date;
  tenantId: string;
  warehouse?: Warehouse;
  performer?: User;
  approver?: User;
}

export enum StockTakeStatus {
  IN_PROGRESS = 'IN_PROGRESS',
  COMPLETED = 'COMPLETED',
  APPROVED = 'APPROVED',
  CANCELLED = 'CANCELLED',
}

export interface StockTakeItem {
  id: string;
  stockTakeId: string;
  productId: string;
  systemQuantity: number;
  countedQuantity: number;
  discrepancy: number;
  discrepancyValue?: number;
  notes?: string;
  createdAt: Date;
  product?: Product;
}

export interface InventoryReservation {
  id: string;
  productId: string;
  warehouseId: string;
  quantity: number;
  referenceType: string;
  referenceId: string;
  reservedBy: string;
  reservedAt: Date;
  expiresAt?: Date;
  releasedAt?: Date;
  releasedBy?: string;
  status: ReservationStatus;
  tenantId: string;
  product?: Product;
  warehouse?: Warehouse;
}

export enum ReservationStatus {
  ACTIVE = 'ACTIVE',
  EXPIRED = 'EXPIRED',
  RELEASED = 'RELEASED',
  CONSUMED = 'CONSUMED',
}

export interface Warehouse {
  id: string;
  code: string;
  name: string;
  nameAr?: string;
  type: WarehouseType;
  address?: string;
  city?: string;
  country?: string;
  phone?: string;
  email?: string;
  managerId?: string;
  isActive: boolean;
  capacity?: number;
  currentOccupancy?: number;
  createdAt: Date;
  updatedAt: Date;
  tenantId: string;
  manager?: User;
}

export enum WarehouseType {
  MAIN = 'MAIN',
  BRANCH = 'BRANCH',
  DISTRIBUTION = 'DISTRIBUTION',
  VIRTUAL = 'VIRTUAL',
}

export interface Product {
  id: string;
  sku: string;
  barcode?: string;
  name: string;
  nameAr?: string;
  description?: string;
  descriptionAr?: string;
  categoryId: string;
  unitId: string;
  price: number;
  cost?: number;
  isActive: boolean;
  createdAt: Date;
  updatedAt: Date;
  tenantId: string;
}

export interface User {
  id: string;
  email: string;
  firstName: string;
  lastName: string;
  tenantId: string;
}

// DTOs للإدخال
export interface CreateInventoryTransactionDTO {
  type: InventoryTransactionType;
  productId: string;
  warehouseId: string;
  quantity: number;
  unitCost?: number;
  referenceType?: string;
  referenceId?: string;
  notes?: string;
  metadata?: Record<string, any>;
}

export interface TransferInventoryDTO {
  productId: string;
  fromWarehouseId: string;
  toWarehouseId: string;
  quantity: number;
  reason?: string;
  notes?: string;
}

export interface StockTakeDTO {
  warehouseId: string;
  items: StockTakeItemDTO[];
  notes?: string;
}

export interface StockTakeItemDTO {
  productId: string;
  countedQuantity: number;
  notes?: string;
}

export interface ReserveInventoryDTO {
  productId: string;
  warehouseId: string;
  quantity: number;
  referenceType: string;
  referenceId: string;
  expiresAt?: Date;
}

export interface UpdateReorderLevelDTO {
  productId: string;
  warehouseId: string;
  minStockLevel: number;
  reorderPoint: number;
  reorderQuantity: number;
  maxStockLevel?: number;
}

// واجهات التقارير
export interface InventoryStatistics {
  totalProducts: number;
  totalValue: number;
  lowStockItems: number;
  outOfStockItems: number;
  overstockItems: number;
  totalTransactions: number;
  warehouses: WarehouseStatistics[];
}

export interface WarehouseStatistics {
  warehouseId: string;
  warehouseName: string;
  totalProducts: number;
  totalValue: number;
  occupancyRate: number;
  lowStockItems: number;
  outOfStockItems: number;
}

export interface StockMovementReport {
  warehouseId: string;
  warehouseName: string;
  startDate: Date;
  endDate: Date;
  openingStock: number;
  closingStock: number;
  totalIn: number;
  totalOut: number;
  movements: StockMovement[];
}

export interface StockMovement {
  date: Date;
  type: InventoryTransactionType;
  productId: string;
  productName: string;
  quantity: number;
  unitCost?: number;
  totalCost?: number;
  balance: number;
  referenceType?: string;
  referenceId?: string;
  performedBy: string;
}

export interface InventoryValuationReport {
  valuationDate: Date;
  valuationMethod: ValuationMethod;
  totalValue: number;
  warehouses: WarehouseValuation[];
  categories: CategoryValuation[];
}

export enum ValuationMethod {
  FIFO = 'FIFO',
  LIFO = 'LIFO',
  AVERAGE = 'AVERAGE',
}

export interface WarehouseValuation {
  warehouseId: string;
  warehouseName: string;
  totalValue: number;
  itemCount: number;
}

export interface CategoryValuation {
  categoryId: string;
  categoryName: string;
  totalValue: number;
  itemCount: number;
  percentage: number;
}

// واجهات البحث
export interface InventorySearchParams {
  query?: string;
  warehouseId?: string;
  categoryId?: string;
  lowStock?: boolean;
  outOfStock?: boolean;
  page?: number;
  limit?: number;
  sortBy?: 'name' | 'quantity' | 'value' | 'lastUpdated';
  sortOrder?: 'asc' | 'desc';
}

export interface InventorySearchResult {
  data: InventoryItem[];
  total: number;
  page: number;
  totalPages: number;
}

export interface InventoryItem {
  id: string;
  productId: string;
  productName: string;
  productSku: string;
  warehouseId: string;
  warehouseName: string;
  quantity: number;
  availableQuantity: number;
  reservedQuantity: number;
  unitCost: number;
  totalValue: number;
  minStockLevel: number;
  reorderPoint: number;
  stockStatus: StockStatus;
  lastUpdated: Date;
}

export enum StockStatus {
  IN_STOCK = 'IN_STOCK',
  LOW_STOCK = 'LOW_STOCK',
  OUT_OF_STOCK = 'OUT_OF_STOCK',
  OVERSTOCK = 'OVERSTOCK',
}