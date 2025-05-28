import { createSlice, createAsyncThunk, PayloadAction } from '@reduxjs/toolkit';
import { customerService } from '../../services/api';
import { Customer, PaginatedResponse } from '../../types';

interface CustomerState {
  customers: Customer[];
  selectedCustomer: Customer | null;
  loading: boolean;
  error: string | null;
  totalCount: number;
  currentPage: number;
  pageSize: number;
}

const initialState: CustomerState = {
  customers: [],
  selectedCustomer: null,
  loading: false,
  error: null,
  totalCount: 0,
  currentPage: 1,
  pageSize: 10,
};

export const fetchCustomers = createAsyncThunk(
  'customers/fetchAll',
  async ({ page = 1, pageSize = 10 }: { page?: number; pageSize?: number }) => {
    const response = await customerService.getAll({ page, pageSize });
    return response.data;
  }
);

export const fetchCustomerById = createAsyncThunk(
  'customers/fetchById',
  async (id: number) => {
    const response = await customerService.getById(id);
    return response.data;
  }
);

export const createCustomer = createAsyncThunk(
  'customers/create',
  async (customer: Omit<Customer, 'id'>) => {
    const response = await customerService.create(customer);
    return response.data;
  }
);

export const updateCustomer = createAsyncThunk(
  'customers/update',
  async ({ id, data }: { id: number; data: Partial<Customer> }) => {
    const response = await customerService.update(id, data);
    return response.data;
  }
);

export const deleteCustomer = createAsyncThunk(
  'customers/delete',
  async (id: number) => {
    await customerService.delete(id);
    return id;
  }
);

const customerSlice = createSlice({
  name: 'customers',
  initialState,
  reducers: {
    clearError: (state) => {
      state.error = null;
    },
    setSelectedCustomer: (state, action: PayloadAction<Customer | null>) => {
      state.selectedCustomer = action.payload;
    },
    setCurrentPage: (state, action: PayloadAction<number>) => {
      state.currentPage = action.payload;
    },
  },
  extraReducers: (builder) => {
    builder
      // Fetch Customers
      .addCase(fetchCustomers.pending, (state) => {
        state.loading = true;
        state.error = null;
      })
      .addCase(fetchCustomers.fulfilled, (state, action) => {
        state.loading = false;
        state.customers = action.payload.items;
        state.totalCount = action.payload.total;
      })
      .addCase(fetchCustomers.rejected, (state, action) => {
        state.loading = false;
        state.error = action.error.message || 'فشل في جلب العملاء';
      })
      // Fetch Customer By Id
      .addCase(fetchCustomerById.fulfilled, (state, action) => {
        state.selectedCustomer = action.payload;
      })
      // Create Customer
      .addCase(createCustomer.fulfilled, (state, action) => {
        state.customers.unshift(action.payload);
        state.totalCount += 1;
      })
      // Update Customer
      .addCase(updateCustomer.fulfilled, (state, action) => {
        const index = state.customers.findIndex(c => c.id === action.payload.id);
        if (index !== -1) {
          state.customers[index] = action.payload;
        }
        if (state.selectedCustomer?.id === action.payload.id) {
          state.selectedCustomer = action.payload;
        }
      })
      // Delete Customer
      .addCase(deleteCustomer.fulfilled, (state, action) => {
        state.customers = state.customers.filter(c => c.id !== action.payload);
        state.totalCount -= 1;
        if (state.selectedCustomer?.id === action.payload) {
          state.selectedCustomer = null;
        }
      });
  },
});

export const { clearError, setSelectedCustomer, setCurrentPage } = customerSlice.actions;
export default customerSlice.reducer;