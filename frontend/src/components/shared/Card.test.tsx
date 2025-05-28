import { describe, it, expect, vi } from 'vitest';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { Card } from './Card';

describe('Card Component', () => {
  it('يجب أن يتم عرض البطاقة بنجاح', () => {
    render(
      <Card>
        <p>محتوى البطاقة</p>
      </Card>
    );
    
    expect(screen.getByText('محتوى البطاقة')).toBeInTheDocument();
  });

  it('يجب أن يتم تطبيق الصنف الأساسي', () => {
    const { container } = render(
      <Card>
        <p>محتوى</p>
      </Card>
    );
    
    const card = container.firstChild;
    expect(card).toHaveClass('bg-white');
    expect(card).toHaveClass('rounded-lg');
    expect(card).toHaveClass('shadow-sm');
  });

  it('يجب أن يتم تطبيق أصناف إضافية', () => {
    const { container } = render(
      <Card className="custom-class">
        <p>محتوى</p>
      </Card>
    );
    
    const card = container.firstChild;
    expect(card).toHaveClass('custom-class');
    expect(card).toHaveClass('bg-white'); // يجب أن يحتفظ بالأصناف الأساسية
  });

  it('يجب أن يتم عرض العنوان عند تمريره', () => {
    render(
      <Card title="عنوان البطاقة">
        <p>محتوى البطاقة</p>
      </Card>
    );
    
    expect(screen.getByText('عنوان البطاقة')).toBeInTheDocument();
    expect(screen.getByText('عنوان البطاقة').tagName).toBe('H3');
  });

  it('يجب أن يتم عرض الوصف عند تمريره', () => {
    render(
      <Card title="عنوان" description="وصف البطاقة">
        <p>محتوى</p>
      </Card>
    );
    
    expect(screen.getByText('وصف البطاقة')).toBeInTheDocument();
    expect(screen.getByText('وصف البطاقة')).toHaveClass('text-gray-600');
  });

  it('يجب أن يتم عرض الإجراءات عند تمريرها', () => {
    const handleClick = vi.fn();
    
    render(
      <Card 
        title="عنوان"
        actions={
          <button onClick={handleClick}>إجراء</button>
        }
      >
        <p>محتوى</p>
      </Card>
    );
    
    const actionButton = screen.getByText('إجراء');
    expect(actionButton).toBeInTheDocument();
    
    userEvent.click(actionButton);
    expect(handleClick).toHaveBeenCalledTimes(1);
  });

  it('يجب أن يتم عرض البطاقة كاملة مع جميع الخصائص', () => {
    render(
      <Card 
        title="عنوان البطاقة"
        description="وصف مفصل للبطاقة"
        actions={
          <div>
            <button>حفظ</button>
            <button>إلغاء</button>
          </div>
        }
      >
        <div data-testid="card-content">
          <p>محتوى البطاقة الرئيسي</p>
        </div>
      </Card>
    );
    
    // التحقق من العنوان
    expect(screen.getByText('عنوان البطاقة')).toBeInTheDocument();
    
    // التحقق من الوصف
    expect(screen.getByText('وصف مفصل للبطاقة')).toBeInTheDocument();
    
    // التحقق من المحتوى
    expect(screen.getByTestId('card-content')).toBeInTheDocument();
    expect(screen.getByText('محتوى البطاقة الرئيسي')).toBeInTheDocument();
    
    // التحقق من الإجراءات
    expect(screen.getByText('حفظ')).toBeInTheDocument();
    expect(screen.getByText('إلغاء')).toBeInTheDocument();
  });

  it('يجب أن تحافظ البطاقة على البنية الصحيحة', () => {
    const { container } = render(
      <Card title="عنوان" description="وصف" actions={<button>إجراء</button>}>
        <p>محتوى</p>
      </Card>
    );
    
    const card = container.firstChild as HTMLElement;
    const header = card.querySelector('.border-b');
    const footer = card.querySelector('.border-t');
    
    expect(header).toBeInTheDocument();
    expect(footer).toBeInTheDocument();
  });

  it('يجب أن يتم تطبيق padding مناسب', () => {
    const { container } = render(
      <Card padding="large">
        <p>محتوى</p>
      </Card>
    );
    
    const card = container.firstChild;
    expect(card).toHaveClass('p-8');
  });

  it('يجب أن يتم دعم البطاقة القابلة للنقر', () => {
    const handleClick = vi.fn();
    
    const { container } = render(
      <Card onClick={handleClick} clickable>
        <p>محتوى قابل للنقر</p>
      </Card>
    );
    
    const card = container.firstChild as HTMLElement;
    expect(card).toHaveClass('cursor-pointer');
    expect(card).toHaveClass('hover:shadow-md');
    
    userEvent.click(card);
    expect(handleClick).toHaveBeenCalledTimes(1);
  });

  it('يجب أن يتم دعم البطاقة مع حدود', () => {
    const { container } = render(
      <Card bordered>
        <p>محتوى</p>
      </Card>
    );
    
    const card = container.firstChild;
    expect(card).toHaveClass('border');
    expect(card).toHaveClass('border-gray-200');
  });

  it('يجب أن يتم دعم البطاقة بدون ظل', () => {
    const { container } = render(
      <Card shadow={false}>
        <p>محتوى</p>
      </Card>
    );
    
    const card = container.firstChild;
    expect(card).not.toHaveClass('shadow-sm');
  });

  it('يجب أن يتم عرض مؤشر التحميل', () => {
    render(
      <Card loading>
        <p>محتوى</p>
      </Card>
    );
    
    expect(screen.getByText('جاري التحميل...')).toBeInTheDocument();
    expect(screen.queryByText('محتوى')).not.toBeInTheDocument();
  });

  it('يجب أن يتم دعم الخصائص المخصصة', () => {
    render(
      <Card data-testid="custom-card" aria-label="بطاقة مخصصة">
        <p>محتوى</p>
      </Card>
    );
    
    const card = screen.getByTestId('custom-card');
    expect(card).toHaveAttribute('aria-label', 'بطاقة مخصصة');
  });

  it('يجب أن يتم دعم RTL بشكل صحيح', () => {
    const { container } = render(
      <Card title="عنوان عربي" dir="rtl">
        <p>محتوى عربي</p>
      </Card>
    );
    
    const card = container.firstChild;
    expect(card).toHaveAttribute('dir', 'rtl');
  });

  it('يجب أن يتعامل مع المحتوى الفارغ بشكل صحيح', () => {
    const { container } = render(<Card />);
    
    const card = container.firstChild;
    expect(card).toBeInTheDocument();
    expect(card).toHaveClass('bg-white');
  });

  it('يجب أن يتم دعم الألوان المختلفة', () => {
    const { container, rerender } = render(
      <Card variant="primary">
        <p>محتوى</p>
      </Card>
    );
    
    let card = container.firstChild;
    expect(card).toHaveClass('bg-blue-50');
    expect(card).toHaveClass('border-blue-200');
    
    rerender(
      <Card variant="success">
        <p>محتوى</p>
      </Card>
    );
    
    card = container.firstChild;
    expect(card).toHaveClass('bg-green-50');
    expect(card).toHaveClass('border-green-200');
  });

  it('يجب أن يتم دعم العرض الكامل', () => {
    const { container } = render(
      <Card fullWidth>
        <p>محتوى</p>
      </Card>
    );
    
    const card = container.firstChild;
    expect(card).toHaveClass('w-full');
  });

  it('يجب أن يتم التعامل مع الأحداث بشكل صحيح', async () => {
    const handleMouseEnter = vi.fn();
    const handleMouseLeave = vi.fn();
    
    const { container } = render(
      <Card 
        onMouseEnter={handleMouseEnter}
        onMouseLeave={handleMouseLeave}
      >
        <p>محتوى</p>
      </Card>
    );
    
    const card = container.firstChild as HTMLElement;
    
    await userEvent.hover(card);
    expect(handleMouseEnter).toHaveBeenCalledTimes(1);
    
    await userEvent.unhover(card);
    expect(handleMouseLeave).toHaveBeenCalledTimes(1);
  });
});