import { createSlice } from '@reduxjs/toolkit';

const legalCaseSlice = createSlice({
  name: 'legalCases',
  initialState: { legalCases: [], loading: false, error: null },
  reducers: {},
});

export default legalCaseSlice.reducer;