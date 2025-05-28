import React from 'react';
import { clsx } from 'clsx';
import { ChevronUpIcon, ChevronDownIcon } from '@heroicons/react/24/outline';

interface Column<T> {
  key: keyof T | string;
  header: string;
  render?: (item: T) => React.ReactNode;
  sortable?: boolean;
  className?: string;
}

interface TableProps<T> {
  data: T[];
  columns: Column<T>[];
  keyExtractor: (item: T) => string | number;
  onSort?: (key: string, direction: 'asc' | 'desc') => void;
  sortKey?: string;
  sortDirection?: 'asc' | 'desc';
  onRowClick?: (item: T) => void;
  emptyMessage?: string;
  loading?: boolean;
  className?: string;
}

export function Table<T>({
  data,
  columns,
  keyExtractor,
  onSort,
  sortKey,
  sortDirection,
  onRowClick,
  emptyMessage = 'لا توجد بيانات',
  loading = false,
  className,
}: TableProps<T>) {
  const handleSort = (column: Column<T>) => {
    if (!column.sortable || !onSort) return;
    
    const key = column.key as string;
    const direction = sortKey === key && sortDirection === 'asc' ? 'desc' : 'asc';
    onSort(key, direction);
  };

  const getCellValue = (item: T, column: Column<T>) => {
    if (column.render) {
      return column.render(item);
    }
    
    const keys = (column.key as string).split('.');
    let value: any = item;
    
    for (const key of keys) {
      value = value?.[key];
    }
    
    return value?.toString() || '-';
  };

  if (loading) {
    return (
      <div className="flex justify-center items-center py-12">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
      </div>
    );
  }

  return (
    <div className={clsx('overflow-x-auto', className)}>
      <table className="min-w-full divide-y divide-gray-200">
        <thead className="bg-gray-50">
          <tr>
            {columns.map((column) => (
              <th
                key={column.key as string}
                className={clsx(
                  'px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider',
                  column.sortable && 'cursor-pointer select-none hover:bg-gray-100',
                  column.className
                )}
                onClick={() => handleSort(column)}
              >
                <div className="flex items-center justify-end gap-1">
                  {column.header}
                  {column.sortable && (
                    <div className="flex flex-col">
                      <ChevronUpIcon
                        className={clsx(
                          'h-3 w-3',
                          sortKey === column.key && sortDirection === 'asc'
                            ? 'text-indigo-600'
                            : 'text-gray-400'
                        )}
                      />
                      <ChevronDownIcon
                        className={clsx(
                          'h-3 w-3 -mt-1',
                          sortKey === column.key && sortDirection === 'desc'
                            ? 'text-indigo-600'
                            : 'text-gray-400'
                        )}
                      />
                    </div>
                  )}
                </div>
              </th>
            ))}
          </tr>
        </thead>
        <tbody className="bg-white divide-y divide-gray-200">
          {data.length === 0 ? (
            <tr>
              <td
                colSpan={columns.length}
                className="px-6 py-12 text-center text-gray-500"
              >
                {emptyMessage}
              </td>
            </tr>
          ) : (
            data.map((item) => (
              <tr
                key={keyExtractor(item)}
                className={clsx(
                  'hover:bg-gray-50',
                  onRowClick && 'cursor-pointer'
                )}
                onClick={() => onRowClick?.(item)}
              >
                {columns.map((column) => (
                  <td
                    key={column.key as string}
                    className={clsx(
                      'px-6 py-4 whitespace-nowrap text-sm text-gray-900',
                      column.className
                    )}
                  >
                    {getCellValue(item, column)}
                  </td>
                ))}
              </tr>
            ))
          )}
        </tbody>
      </table>
    </div>
  );
}