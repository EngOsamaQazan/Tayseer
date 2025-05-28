import { createSlice } from '@reduxjs/toolkit';

const transactionSlice = createSlice({
  name: 'transactions',
  initialState: { transactions: [], loading: false, error: null },
  reducers: {},
});

export default transactionSlice.reducer;