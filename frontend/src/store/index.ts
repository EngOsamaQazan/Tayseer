import { configureStore } from '@reduxjs/toolkit';
import { TypedUseSelectorHook, useDispatch, useSelector } from 'react-redux';
import authReducer from './slices/authSlice';
import customerReducer from './slices/customerSlice';
import productReducer from './slices/productSlice';
import contractReducer from './slices/contractSlice';
import transactionReducer from './slices/transactionSlice';
import employeeReducer from './slices/employeeSlice';
import taskReducer from './slices/taskSlice';
import legalCaseReducer from './slices/legalCaseSlice';
import ticketReducer from './slices/ticketSlice';
import investorReducer from './slices/investorSlice';
import investmentReducer from './slices/investmentSlice';
import notificationReducer from './slices/notificationSlice';

export const store = configureStore({
  reducer: {
    auth: authReducer,
    customers: customerReducer,
    products: productReducer,
    contracts: contractReducer,
    transactions: transactionReducer,
    employees: employeeReducer,
    tasks: taskReducer,
    legalCases: legalCaseReducer,
    tickets: ticketReducer,
    investors: investorReducer,
    investments: investmentReducer,
    notifications: notificationReducer,
  },
});

export type RootState = ReturnType<typeof store.getState>;
export type AppDispatch = typeof store.dispatch;

// Typed hooks
export const useAppDispatch = () => useDispatch<AppDispatch>();
export const useAppSelector: TypedUseSelectorHook<RootState> = useSelector;