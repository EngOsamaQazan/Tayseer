import { describe, it, expect, vi } from 'vitest';
import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { Table } from './Table';

interface TestData {
  id: number;
  name: string;
  email: string;
  role: string;
  status: 'active' | 'inactive';
}

describe('Table Component', () => {
  const columns = [
    { key: 'name', header: 'الاسم', sortable: true },
    { key: 'email', header: 'البريد الإلكتروني' },
    { key: 'role', header: 'الدور', sortable: true },
    { 
      key: 'status', 
      header: 'الحالة',
      render: (value: string) => (
        <span className={value === 'active' ? 'text-green-600' : 'text-red-600'}>
          {value === 'active' ? 'نشط' : 'غير نشط'}
        </span>
      )
    },
  ];

  const data: TestData[] = [
    { id: 1, name: 'أحمد محمد', email: 'ahmad@example.com', role: 'مدير', status: 'active' },
    { id: 2, name: 'فاطمة علي', email: 'fatima@example.com', role: 'موظف', status: 'active' },
    { id: 3, name: 'محمد سالم', email: 'mohamed@example.com', role: 'موظف', status: 'inactive' },
  ];

  const defaultProps = {
    columns,
    data,
  };

  it('يجب أن يتم عرض الجدول بشكل صحيح', () => {
    render(<Table {...defaultProps} />);
    
    // التحقق من العناوين
    expect(screen.getByText('الاسم')).toBeInTheDocument();
    expect(screen.getByText('البريد الإلكتروني')).toBeInTheDocument();
    expect(screen.getByText('الدور')).toBeInTheDocument();
    expect(screen.getByText('الحالة')).toBeInTheDocument();
    
    // التحقق من البيانات
    expect(screen.getByText('أحمد محمد')).toBeInTheDocument();
    expect(screen.getByText('ahmad@example.com')).toBeInTheDocument();
    expect(screen.getByText('مدير')).toBeInTheDocument();
  });

  it('يجب أن يتم عرض رسالة عندما لا توجد بيانات', () => {
    render(<Table {...defaultProps} data={[]} />);
    
    expect(screen.getByText('لا توجد بيانات للعرض')).toBeInTheDocument();
  });

  it('يجب أن يتم دعم التحديد المتعدد', async () => {
    const onSelectionChange = vi.fn();
    render(
      <Table 
        {...defaultProps} 
        selectable 
        onSelectionChange={onSelectionChange}
      />
    );
    
    // التحقق من checkbox للتحديد الكلي
    const selectAllCheckbox = screen.getByRole('checkbox', { name: 'تحديد الكل' });
    expect(selectAllCheckbox).toBeInTheDocument();
    
    // تحديد صف واحد
    const firstRowCheckbox = screen.getAllByRole('checkbox')[1];
    await userEvent.click(firstRowCheckbox);
    
    expect(onSelectionChange).toHaveBeenCalledWith([data[0]]);
    
    // تحديد الكل
    await userEvent.click(selectAllCheckbox);
    expect(onSelectionChange).toHaveBeenCalledWith(data);
  });

  it('يجب أن يتم دعم الترتيب', async () => {
    const onSort = vi.fn();
    render(<Table {...defaultProps} onSort={onSort} />);
    
    // النقر على عمود قابل للترتيب
    const nameHeader = screen.getByText('الاسم');
    await userEvent.click(nameHeader);
    
    expect(onSort).toHaveBeenCalledWith('name', 'asc');
    
    // النقر مرة أخرى للترتيب التنازلي
    await userEvent.click(nameHeader);
    expect(onSort).toHaveBeenCalledWith('name', 'desc');
  });

  it('يجب أن يتم دعم التحميل', () => {
    render(<Table {...defaultProps} loading />);
    
    expect(screen.getByRole('status')).toBeInTheDocument();
    expect(screen.getByText('جاري التحميل...')).toBeInTheDocument();
  });

  it('يجب أن يتم دعم الإجراءات على الصفوف', async () => {
    const onEdit = vi.fn();
    const onDelete = vi.fn();
    
    const actionsColumn = {
      key: 'actions',
      header: 'الإجراءات',
      render: (_: any, row: TestData) => (
        <div className="flex gap-2">
          <button onClick={() => onEdit(row)}>تعديل</button>
          <button onClick={() => onDelete(row)}>حذف</button>
        </div>
      ),
    };
    
    render(
      <Table 
        {...defaultProps} 
        columns={[...columns, actionsColumn]}
      />
    );
    
    const firstRow = screen.getByText('أحمد محمد').closest('tr')!;
    const editButton = within(firstRow).getByText('تعديل');
    const deleteButton = within(firstRow).getByText('حذف');
    
    await userEvent.click(editButton);
    expect(onEdit).toHaveBeenCalledWith(data[0]);
    
    await userEvent.click(deleteButton);
    expect(onDelete).toHaveBeenCalledWith(data[0]);
  });

  it('يجب أن يتم دعم تمييز الصفوف عند التمرير', async () => {
    const { container } = render(<Table {...defaultProps} hoverable />);
    
    const firstRow = container.querySelector('tbody tr')!;
    expect(firstRow).toHaveClass('hover:bg-gray-50');
  });

  it('يجب أن يتم دعم الحدود المخططة', () => {
    const { container } = render(<Table {...defaultProps} striped />);
    
    const rows = container.querySelectorAll('tbody tr');
    expect(rows[1]).toHaveClass('bg-gray-50');
  });

  it('يجب أن يتم دعم الحدود', () => {
    const { container } = render(<Table {...defaultProps} bordered />);
    
    const table = container.querySelector('table');
    expect(table).toHaveClass('border');
  });

  it('يجب أن يتم دعم الأحجام المختلفة', () => {
    const { rerender, container } = render(<Table {...defaultProps} size="small" />);
    
    let cells = container.querySelectorAll('td');
    cells.forEach(cell => {
      expect(cell).toHaveClass('p-2');
    });
    
    rerender(<Table {...defaultProps} size="medium" />);
    cells = container.querySelectorAll('td');
    cells.forEach(cell => {
      expect(cell).toHaveClass('p-4');
    });
    
    rerender(<Table {...defaultProps} size="large" />);
    cells = container.querySelectorAll('td');
    cells.forEach(cell => {
      expect(cell).toHaveClass('p-6');
    });
  });

  it('يجب أن يتم دعم التمرير الأفقي', () => {
    const { container } = render(<Table {...defaultProps} />);
    
    const wrapper = container.querySelector('.overflow-x-auto');
    expect(wrapper).toBeInTheDocument();
  });

  it('يجب أن يتم دعم العناوين الثابتة', () => {
    const { container } = render(<Table {...defaultProps} stickyHeader />);
    
    const thead = container.querySelector('thead');
    expect(thead).toHaveClass('sticky');
    expect(thead).toHaveClass('top-0');
  });

  it('يجب أن يتم دعم تنسيق مخصص للخلايا', () => {
    const customColumns = [
      {
        key: 'name',
        header: 'الاسم',
        className: 'font-bold text-blue-600',
      },
    ];
    
    const { container } = render(
      <Table columns={customColumns} data={data.slice(0, 1)} />
    );
    
    const nameCell = screen.getByText('أحمد محمد');
    expect(nameCell).toHaveClass('font-bold');
    expect(nameCell).toHaveClass('text-blue-600');
  });

  it('يجب أن يتم دعم دمج الخلايا', () => {
    const columnsWithColspan = [
      {
        key: 'name',
        header: 'الاسم',
        colspan: 2,
      },
    ];
    
    const { container } = render(
      <Table columns={columnsWithColspan} data={data.slice(0, 1)} />
    );
    
    const th = container.querySelector('th');
    expect(th).toHaveAttribute('colspan', '2');
  });

  it('يجب أن يتم دعم التوسيع', async () => {
    const expandableRow = (row: TestData) => (
      <div className="p-4">
        <p>تفاصيل إضافية لـ {row.name}</p>
      </div>
    );
    
    render(
      <Table 
        {...defaultProps} 
        expandable
        expandableRow={expandableRow}
      />
    );
    
    // النقر على زر التوسيع
    const expandButton = screen.getAllByLabelText('توسيع الصف')[0];
    await userEvent.click(expandButton);
    
    expect(screen.getByText('تفاصيل إضافية لـ أحمد محمد')).toBeInTheDocument();
  });

  it('يجب أن يتم دعم التصفية', () => {
    const columnsWithFilter = columns.map(col => ({
      ...col,
      filterable: true,
    }));
    
    render(<Table columns={columnsWithFilter} data={data} />);
    
    // التحقق من وجود أيقونات التصفية
    const filterIcons = screen.getAllByLabelText('تصفية');
    expect(filterIcons).toHaveLength(4);
  });

  it('يجب أن يتم دعم النقر على الصفوف', async () => {
    const onRowClick = vi.fn();
    render(<Table {...defaultProps} onRowClick={onRowClick} />);
    
    const firstRow = screen.getByText('أحمد محمد').closest('tr')!;
    await userEvent.click(firstRow);
    
    expect(onRowClick).toHaveBeenCalledWith(data[0]);
  });

  it('يجب أن يتم دعم تعطيل الصفوف', () => {
    const dataWithDisabled = data.map((item, index) => ({
      ...item,
      disabled: index === 1,
    }));
    
    const { container } = render(
      <Table {...defaultProps} data={dataWithDisabled} selectable />
    );
    
    const checkboxes = container.querySelectorAll('input[type="checkbox"]');
    expect(checkboxes[2]).toBeDisabled();
  });

  it('يجب أن يتم دعم التذييل', () => {
    const footer = (
      <tfoot>
        <tr>
          <td colSpan={4} className="text-center font-bold">
            إجمالي: 3 سجلات
          </td>
        </tr>
      </tfoot>
    );
    
    render(<Table {...defaultProps} footer={footer} />);
    
    expect(screen.getByText('إجمالي: 3 سجلات')).toBeInTheDocument();
  });

  it('يجب أن يتم دعم الفئات المخصصة', () => {
    const { container } = render(
      <Table 
        {...defaultProps} 
        className="custom-table"
        containerClassName="custom-container"
      />
    );
    
    const table = container.querySelector('table');
    expect(table).toHaveClass('custom-table');
    
    const wrapper = container.firstChild;
    expect(wrapper).toHaveClass('custom-container');
  });

  it('يجب أن يتم دعم العرض الثابت للجدول', () => {
    const { container } = render(<Table {...defaultProps} fixedLayout />);
    
    const table = container.querySelector('table');
    expect(table).toHaveClass('table-fixed');
  });

  it('يجب أن يتم دعم التحكم في عرض الأعمدة', () => {
    const columnsWithWidth = columns.map((col, index) => ({
      ...col,
      width: index === 0 ? '200px' : 'auto',
    }));
    
    const { container } = render(
      <Table columns={columnsWithWidth} data={data} />
    );
    
    const firstTh = container.querySelector('th');
    expect(firstTh).toHaveStyle({ width: '200px' });
  });

  it('يجب أن يتم دعم محاذاة النص', () => {
    const columnsWithAlign = columns.map((col, index) => ({
      ...col,
      align: index === 0 ? 'center' : index === 1 ? 'right' : 'left',
    }));
    
    const { container } = render(
      <Table columns={columnsWithAlign} data={data} />
    );
    
    const cells = container.querySelectorAll('td');
    expect(cells[0]).toHaveClass('text-center');
    expect(cells[1]).toHaveClass('text-right');
    expect(cells[2]).toHaveClass('text-left');
  });

  it('يجب أن يتم دعم وضع RTL', () => {
    const { container } = render(<Table {...defaultProps} rtl />);
    
    const table = container.querySelector('table');
    expect(table).toHaveAttribute('dir', 'rtl');
  });
});