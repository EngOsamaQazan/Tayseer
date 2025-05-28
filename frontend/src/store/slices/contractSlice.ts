import { createSlice } from '@reduxjs/toolkit';

const contractSlice = createSlice({
  name: 'contracts',
  initialState: { contracts: [], loading: false, error: null },
  reducers: {},
});

export default contractSlice.reducer;