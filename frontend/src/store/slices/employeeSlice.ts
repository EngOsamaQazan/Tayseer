import { createSlice } from '@reduxjs/toolkit';

const employeeSlice = createSlice({
  name: 'employees',
  initialState: { employees: [], loading: false, error: null },
  reducers: {},
});

export default employeeSlice.reducer;