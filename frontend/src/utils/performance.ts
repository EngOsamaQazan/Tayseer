// Performance monitoring utilities

export const measurePerformance = (markName: string) => {
  if (typeof window !== 'undefined' && window.performance) {
    window.performance.mark(markName);
  }
};

export const measureBetween = (startMark: string, endMark: string, measureName: string) => {
  if (typeof window !== 'undefined' && window.performance) {
    try {
      window.performance.measure(measureName, startMark, endMark);
      const measure = window.performance.getEntriesByName(measureName)[0];
      console.log(`${measureName}: ${measure.duration.toFixed(2)}ms`);
      return measure.duration;
    } catch (error) {
      console.error('Performance measurement error:', error);
      return null;
    }
  }
  return null;
};

export const reportWebVitals = (onPerfEntry?: (metric: any) => void) => {
  if (onPerfEntry && onPerfEntry instanceof Function) {
    import('web-vitals').then(({ getCLS, getFID, getFCP, getLCP, getTTFB }) => {
      getCLS(onPerfEntry);
      getFID(onPerfEntry);
      getFCP(onPerfEntry);
      getLCP(onPerfEntry);
      getTTFB(onPerfEntry);
    });
  }
};

// Lazy load components
export const lazyLoadComponent = (importFunc: () => Promise<any>) => {
  return import('react').then(({ lazy, Suspense }) => {
    const LazyComponent = lazy(importFunc);
    return (props: any) => (
      <Suspense fallback={<div className="flex justify-center items-center h-full"><div className="spinner" /></div>}>
        <LazyComponent {...props} />
      </Suspense>
    );
  });
};

// Debounce function for performance optimization
export const debounce = <T extends (...args: any[]) => any>(
  func: T,
  wait: number
): ((...args: Parameters<T>) => void) => {
  let timeout: NodeJS.Timeout | null = null;
  
  return (...args: Parameters<T>) => {
    if (timeout) clearTimeout(timeout);
    timeout = setTimeout(() => func(...args), wait);
  };
};

// Throttle function for performance optimization
export const throttle = <T extends (...args: any[]) => any>(
  func: T,
  limit: number
): ((...args: Parameters<T>) => void) => {
  let inThrottle: boolean = false;
  
  return (...args: Parameters<T>) => {
    if (!inThrottle) {
      func(...args);
      inThrottle = true;
      setTimeout(() => inThrottle = false, limit);
    }
  };
};

// Memoize function for caching expensive computations
export const memoize = <T extends (...args: any[]) => any>(
  func: T
): T => {
  const cache = new Map();
  
  return ((...args: Parameters<T>) => {
    const key = JSON.stringify(args);
    if (cache.has(key)) {
      return cache.get(key);
    }
    const result = func(...args);
    cache.set(key, result);
    return result;
  }) as T;
};

// Batch DOM updates
export const batchUpdate = (updates: (() => void)[]) => {
  requestAnimationFrame(() => {
    updates.forEach(update => update());
  });
};

// Virtual scrolling helper
export const calculateVisibleItems = <T>(
  items: T[],
  containerHeight: number,
  itemHeight: number,
  scrollTop: number,
  buffer: number = 5
) => {
  const startIndex = Math.max(0, Math.floor(scrollTop / itemHeight) - buffer);
  const endIndex = Math.min(
    items.length - 1,
    Math.ceil((scrollTop + containerHeight) / itemHeight) + buffer
  );
  
  return {
    visibleItems: items.slice(startIndex, endIndex + 1),
    startIndex,
    endIndex,
    totalHeight: items.length * itemHeight,
    offsetY: startIndex * itemHeight,
  };
};

// Image lazy loading
export const lazyLoadImage = (src: string, placeholder?: string): Promise<string> => {
  return new Promise((resolve, reject) => {
    const img = new Image();
    img.onload = () => resolve(src);
    img.onerror = reject;
    img.src = src;
  });
};

// Request idle callback polyfill
export const requestIdleCallback = 
  window.requestIdleCallback ||
  function (cb: IdleRequestCallback) {
    const start = Date.now();
    return setTimeout(() => {
      cb({
        didTimeout: false,
        timeRemaining: () => Math.max(0, 50 - (Date.now() - start)),
      } as IdleDeadline);
    }, 1);
  };

export const cancelIdleCallback = 
  window.cancelIdleCallback ||
  function (id: number) {
    clearTimeout(id);
  };