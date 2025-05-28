import { createSlice } from '@reduxjs/toolkit';

const investorSlice = createSlice({
  name: 'investors',
  initialState: { investors: [], loading: false, error: null },
  reducers: {},
});

export default investorSlice.reducer;