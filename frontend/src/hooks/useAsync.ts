import { useCallback, useEffect, useState } from 'react';

interface AsyncState<T> {
  loading: boolean;
  error: Error | null;
  value: T | null;
}

export function useAsync<T>(
  asyncFunction: () => Promise<T>,
  immediate = true
) {
  const [state, setState] = useState<AsyncState<T>>({
    loading: false,
    error: null,
    value: null,
  });

  const execute = useCallback(async () => {
    setState({ loading: true, error: null, value: null });

    try {
      const response = await asyncFunction();
      setState({ loading: false, error: null, value: response });
    } catch (error) {
      setState({ loading: false, error: error as Error, value: null });
    }
  }, [asyncFunction]);

  useEffect(() => {
    if (immediate) {
      execute();
    }
  }, [execute, immediate]);

  return { ...state, execute };
}