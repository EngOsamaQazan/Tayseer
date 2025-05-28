import { createSlice } from '@reduxjs/toolkit';

const investmentSlice = createSlice({
  name: 'investments',
  initialState: { investments: [], loading: false, error: null },
  reducers: {},
});

export default investmentSlice.reducer;