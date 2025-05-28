import { describe, it, expect, vi } from 'vitest';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { Pagination } from './Pagination';

describe('Pagination Component', () => {
  const defaultProps = {
    currentPage: 1,
    totalPages: 10,
    onPageChange: vi.fn(),
  };

  it('يجب أن يتم عرض أرقام الصفحات بشكل صحيح', () => {
    render(<Pagination {...defaultProps} />);
    
    // التحقق من وجود أزرار التنقل
    expect(screen.getByLabelText('الصفحة السابقة')).toBeInTheDocument();
    expect(screen.getByLabelText('الصفحة التالية'))
    .toBeInTheDocument();
    
    // التحقق من أرقام الصفحات
    expect(screen.getByText('1')).toBeInTheDocument();
    expect(screen.getByText('2')).toBeInTheDocument();
    expect(screen.getByText('3')).toBeInTheDocument();
  });

  it('يجب أن يتم التنقل بين الصفحات', async () => {
    const onPageChange = vi.fn();
    render(<Pagination {...defaultProps} onPageChange={onPageChange} />);
    
    // النقر على الصفحة الثانية
    await userEvent.click(screen.getByText('2'));
    expect(onPageChange).toHaveBeenCalledWith(2);
    
    // النقر على الصفحة التالية
    await userEvent.click(screen.getByLabelText('الصفحة التالية'));
    expect(onPageChange).toHaveBeenCalledWith(2);
  });

  it('يجب أن يتم تعطيل أزرار التنقل في الصفحات الأطراف', () => {
    const { rerender } = render(<Pagination {...defaultProps} currentPage={1} />);
    
    // في الصفحة الأولى
    expect(screen.getByLabelText('الصفحة السابقة')).toBeDisabled();
    expect(screen.getByLabelText('الصفحة التالية')).not.toBeDisabled();
    
    // في الصفحة الأخيرة
    rerender(<Pagination {...defaultProps} currentPage={10} />);
    expect(screen.getByLabelText('الصفحة السابقة')).not.toBeDisabled();
    expect(screen.getByLabelText('الصفحة التالية')).toBeDisabled();
  });

  it('يجب أن يتم دعم عدد العناصر لكل صفحة', () => {
    render(
      <Pagination 
        {...defaultProps} 
        totalItems={100}
        itemsPerPage={10}
        showItemsInfo
      />
    );
    
    expect(screen.getByText('عرض 1-10 من 100')).toBeInTheDocument();
  });

  it('يجب أن يتم دعم القفز إلى صفحة محددة', async () => {
    const onPageChange = vi.fn();
    render(
      <Pagination 
        {...defaultProps} 
        onPageChange={onPageChange}
        showGoTo
      />
    );
    
    const input = screen.getByPlaceholderText('رقم الصفحة');
    await userEvent.type(input, '5');
    await userEvent.keyboard('{Enter}');
    
    expect(onPageChange).toHaveBeenCalledWith(5);
  });

  it('يجب أن يتم دعم تغيير عدد العناصر لكل صفحة', async () => {
    const onItemsPerPageChange = vi.fn();
    render(
      <Pagination 
        {...defaultProps} 
        itemsPerPage={10}
        onItemsPerPageChange={onItemsPerPageChange}
        showItemsPerPage
        itemsPerPageOptions={[10, 20, 50]}
      />
    );
    
    const select = screen.getByLabelText('عناصر لكل صفحة');
    await userEvent.selectOptions(select, '20');
    
    expect(onItemsPerPageChange).toHaveBeenCalledWith(20);
  });

  it('يجب أن يتم دعم النقاط الفاصلة للصفحات الكثيرة', () => {
    render(<Pagination currentPage={5} totalPages={100} onPageChange={vi.fn()} />);
    
    // التحقق من وجود النقاط الفاصلة
    expect(screen.getAllByText('...')).toHaveLength(2);
    
    // التحقق من عدم عرض جميع الصفحات
    expect(screen.queryByText('50')).not.toBeInTheDocument();
  });

  it('يجب أن يتم دعم الأزرار الأولى والأخيرة', async () => {
    const onPageChange = vi.fn();
    render(
      <Pagination 
        currentPage={5} 
        totalPages={10} 
        onPageChange={onPageChange}
        showFirstLast
      />
    );
    
    // النقر على الصفحة الأولى
    await userEvent.click(screen.getByLabelText('الصفحة الأولى'));
    expect(onPageChange).toHaveBeenCalledWith(1);
    
    // النقر على الصفحة الأخيرة
    await userEvent.click(screen.getByLabelText('الصفحة الأخيرة'));
    expect(onPageChange).toHaveBeenCalledWith(10);
  });

  it('يجب أن يتم دعم الأحجام المختلفة', () => {
    const { rerender, container } = render(
      <Pagination {...defaultProps} size="small" />
    );
    
    let buttons = container.querySelectorAll('button');
    buttons.forEach(button => {
      expect(button).toHaveClass('text-sm');
    });
    
    rerender(<Pagination {...defaultProps} size="large" />);
    buttons = container.querySelectorAll('button');
    buttons.forEach(button => {
      expect(button).toHaveClass('text-lg');
    });
  });

  it('يجب أن يتم دعم الأنماط المختلفة', () => {
    const { rerender, container } = render(
      <Pagination {...defaultProps} variant="minimal" />
    );
    
    // النمط البسيط - فقط الأزرار السابقة والتالية
    expect(screen.queryByText('1')).not.toBeInTheDocument();
    expect(screen.getByLabelText('الصفحة السابقة')).toBeInTheDocument();
    expect(screen.getByLabelText('الصفحة التالية')).toBeInTheDocument();
    
    rerender(<Pagination {...defaultProps} variant="compact" />);
    // النمط المضغوط - أرقام أقل
    const pageNumbers = container.querySelectorAll('.page-number');
    expect(pageNumbers.length).toBeLessThan(7);
  });

  it('يجب أن يتم دعم التنقل بلوحة المفاتيح', async () => {
    const onPageChange = vi.fn();
    render(<Pagination {...defaultProps} onPageChange={onPageChange} />);
    
    // التركيز على زر الصفحة
    const pageButton = screen.getByText('2');
    pageButton.focus();
    
    // استخدام مفتاح Enter
    await userEvent.keyboard('{Enter}');
    expect(onPageChange).toHaveBeenCalledWith(2);
    
    // استخدام مفتاح Space
    await userEvent.keyboard(' ');
    expect(onPageChange).toHaveBeenCalledTimes(2);
  });

  it('يجب أن يتم دعم التحميل', () => {
    render(<Pagination {...defaultProps} loading />);
    
    const buttons = screen.getAllByRole('button');
    buttons.forEach(button => {
      expect(button).toBeDisabled();
    });
    
    expect(screen.getByText('جاري التحميل...')).toBeInTheDocument();
  });

  it('يجب أن يتم دعم إخفاء عند صفحة واحدة', () => {
    const { container } = render(
      <Pagination 
        currentPage={1} 
        totalPages={1} 
        onPageChange={vi.fn()}
        hideOnSinglePage
      />
    );
    
    expect(container.firstChild).toBeEmptyDOMElement();
  });

  it('يجب أن يتم دعم الفئات المخصصة', () => {
    const { container } = render(
      <Pagination 
        {...defaultProps} 
        className="custom-pagination"
        buttonClassName="custom-button"
        activeClassName="custom-active"
      />
    );
    
    expect(container.firstChild).toHaveClass('custom-pagination');
    
    const buttons = container.querySelectorAll('button');
    buttons.forEach(button => {
      expect(button).toHaveClass('custom-button');
    });
    
    const activeButton = screen.getByText('1').closest('button');
    expect(activeButton).toHaveClass('custom-active');
  });

  it('يجب أن يتم دعم عرض إجمالي الصفحات', () => {
    render(<Pagination {...defaultProps} showTotal />);
    
    expect(screen.getByText('من 10 صفحات')).toBeInTheDocument();
  });

  it('يجب أن يتم دعم التقسيم المتجاوب', () => {
    const { container } = render(
      <Pagination {...defaultProps} responsive />
    );
    
    const pagination = container.firstChild;
    expect(pagination).toHaveClass('flex-wrap');
  });

  it('يجب أن يتم دعم الأيقونات المخصصة', () => {
    render(
      <Pagination 
        {...defaultProps}
        prevIcon={<span data-testid="custom-prev">→</span>}
        nextIcon={<span data-testid="custom-next">←</span>}
      />
    );
    
    expect(screen.getByTestId('custom-prev')).toBeInTheDocument();
    expect(screen.getByTestId('custom-next')).toBeInTheDocument();
  });

  it('يجب أن يتم دعم الحدوب القصوى للأرقام المعروضة', () => {
    render(
      <Pagination 
        currentPage={50} 
        totalPages={100} 
        onPageChange={vi.fn()}
        maxPageNumbers={5}
      />
    );
    
    const pageNumbers = screen.getAllByRole('button', { name: /^\d+$/ });
    expect(pageNumbers).toHaveLength(5);
  });

  it('يجب أن يتم دعم حساب الصفحات من العناصر الكلية', () => {
    render(
      <Pagination 
        currentPage={1} 
        totalItems={95} 
        itemsPerPage={10}
        onPageChange={vi.fn()}
      />
    );
    
    // 95 عنصر / 10 لكل صفحة = 10 صفحات
    expect(screen.getByText('10')).toBeInTheDocument();
  });

  it('يجب أن يتم دعم التحقق من صحة إدخال الصفحة', async () => {
    const onPageChange = vi.fn();
    render(
      <Pagination 
        {...defaultProps} 
        onPageChange={onPageChange}
        showGoTo
      />
    );
    
    const input = screen.getByPlaceholderText('رقم الصفحة');
    
    // إدخال رقم غير صالح
    await userEvent.type(input, '999');
    await userEvent.keyboard('{Enter}');
    expect(onPageChange).not.toHaveBeenCalled();
    
    // إدخال رقم سالب
    await userEvent.clear(input);
    await userEvent.type(input, '-1');
    await userEvent.keyboard('{Enter}');
    expect(onPageChange).not.toHaveBeenCalled();
  });

  it('يجب أن يتم دعم الوضع المضغوط للأجهزة المحمولة', () => {
    const { container } = render(
      <Pagination {...defaultProps} mobileCompact />
    );
    
    const pagination = container.firstChild;
    expect(pagination).toHaveClass('sm:hidden');
    
    // التحقق من وجود النسخة المضغوطة
    const compactVersion = container.querySelector('.pagination-compact');
    expect(compactVersion).toBeInTheDocument();
  });

  it('يجب أن يتم دعم خطافات دورة الحياة', async () => {
    const onBeforePageChange = vi.fn(() => true);
    const onAfterPageChange = vi.fn();
    
    render(
      <Pagination 
        {...defaultProps} 
        onPageChange={vi.fn()}
        onBeforePageChange={onBeforePageChange}
        onAfterPageChange={onAfterPageChange}
      />
    );
    
    await userEvent.click(screen.getByText('2'));
    
    expect(onBeforePageChange).toHaveBeenCalledWith(1, 2);
    expect(onAfterPageChange).toHaveBeenCalledWith(2);
  });

  it('يجب أن يتم دعم وضع RTL', () => {
    const { container } = render(<Pagination {...defaultProps} rtl />);
    
    const pagination = container.querySelector('.pagination-container');
    expect(pagination).toHaveAttribute('dir', 'rtl');
    
    // التحقق من ترتيب الأزرار
    const buttons = container.querySelectorAll('button');
    const prevButton = buttons[0];
    const nextButton = buttons[buttons.length - 1];
    
    expect(prevButton).toHaveAttribute('aria-label', 'الصفحة السابقة');
    expect(nextButton).toHaveAttribute('aria-label', 'الصفحة التالية');
  });
});