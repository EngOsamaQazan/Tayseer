import { useState, useEffect, useCallback, useRef } from 'react';

export function useApi(fetchFn, ...args) {
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const mountedRef = useRef(true);

  const refetch = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const result = await fetchFn(...args);
      if (mountedRef.current) {
        setData(result);
      }
    } catch (err) {
      if (mountedRef.current) {
        setError(err.message || 'حدث خطأ غير متوقع');
      }
    } finally {
      if (mountedRef.current) {
        setLoading(false);
      }
    }
  }, [fetchFn, ...args]);

  useEffect(() => {
    mountedRef.current = true;
    refetch();
    return () => {
      mountedRef.current = false;
    };
  }, [refetch]);

  return { data, loading, error, refetch, setData };
}

export function usePaginatedApi(fetchFn, initialFilters = {}) {
  const [filters, setFilters] = useState(initialFilters);
  const [page, setPage] = useState(1);
  const [pageSize, setPageSize] = useState(20);
  const [data, setData] = useState([]);
  const [total, setTotal] = useState(0);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const mountedRef = useRef(true);

  const refetch = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const result = await fetchFn(filters, page, pageSize);
      if (mountedRef.current) {
        setData(result.data || result.rows || []);
        setTotal(result.total || result.count || 0);
      }
    } catch (err) {
      if (mountedRef.current) {
        setError(err.message || 'حدث خطأ غير متوقع');
      }
    } finally {
      if (mountedRef.current) {
        setLoading(false);
      }
    }
  }, [fetchFn, filters, page, pageSize]);

  useEffect(() => {
    mountedRef.current = true;
    refetch();
    return () => {
      mountedRef.current = false;
    };
  }, [refetch]);

  const updateFilters = useCallback((newFilters) => {
    setFilters((prev) => ({ ...prev, ...newFilters }));
    setPage(1);
  }, []);

  const resetFilters = useCallback(() => {
    setFilters(initialFilters);
    setPage(1);
  }, []);

  return {
    data,
    total,
    loading,
    error,
    page,
    setPage,
    pageSize,
    setPageSize,
    filters,
    setFilters: updateFilters,
    resetFilters,
    refetch,
  };
}
