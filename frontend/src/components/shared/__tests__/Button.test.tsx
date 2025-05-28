// Button component tests
import React from 'react';
import { describe, it, expect, vi } from 'vitest';
import { render, screen } from '../../../test/test-utils';
import { clickButton, expectToBeAccessible } from '../../../test/component-helpers';
import Button from '../Button';

describe('Button Component', () => {
  it('should render with text content', () => {
    render(<Button>إرسال</Button>);
    expect(screen.getByRole('button')).toHaveTextContent('إرسال');
  });

  it('should handle click events', async () => {
    const handleClick = vi.fn();
    render(<Button onClick={handleClick}>انقر هنا</Button>);
    
    await clickButton('انقر هنا');
    expect(handleClick).toHaveBeenCalledTimes(1);
  });

  it('should render different variants', () => {
    const { rerender } = render(<Button variant="primary">أساسي</Button>);
    expect(screen.getByRole('button')).toHaveClass('bg-blue-600');

    rerender(<Button variant="secondary">ثانوي</Button>);
    expect(screen.getByRole('button')).toHaveClass('bg-gray-600');

    rerender(<Button variant="danger">خطر</Button>);
    expect(screen.getByRole('button')).toHaveClass('bg-red-600');

    rerender(<Button variant="success">نجاح</Button>);
    expect(screen.getByRole('button')).toHaveClass('bg-green-600');
  });

  it('should render different sizes', () => {
    const { rerender } = render(<Button size="small">صغير</Button>);
    expect(screen.getByRole('button')).toHaveClass('px-3 py-1.5 text-sm');

    rerender(<Button size="medium">متوسط</Button>);
    expect(screen.getByRole('button')).toHaveClass('px-4 py-2 text-base');

    rerender(<Button size="large">كبير</Button>);
    expect(screen.getByRole('button')).toHaveClass('px-6 py-3 text-lg');
  });

  it('should handle disabled state', async () => {
    const handleClick = vi.fn();
    render(
      <Button disabled onClick={handleClick}>
        معطل
      </Button>
    );

    const button = screen.getByRole('button');
    expect(button).toBeDisabled();
    expect(button).toHaveClass('opacity-50 cursor-not-allowed');

    // Click should not work when disabled
    await clickButton('معطل');
    expect(handleClick).not.toHaveBeenCalled();
  });

  it('should show loading state', () => {
    render(<Button loading>جاري التحميل</Button>);
    
    const button = screen.getByRole('button');
    expect(button).toBeDisabled();
    expect(screen.getByText('جاري التحميل...')).toBeInTheDocument();
  });

  it('should render as full width', () => {
    render(<Button fullWidth>عرض كامل</Button>);
    expect(screen.getByRole('button')).toHaveClass('w-full');
  });

  it('should render as a link when href is provided', () => {
    render(<Button href="/dashboard">لوحة التحكم</Button>);
    
    const link = screen.getByRole('link');
    expect(link).toHaveAttribute('href', '/dashboard');
    expect(link).toHaveTextContent('لوحة التحكم');
  });

  it('should render with icon', () => {
    const Icon = () => <svg data-testid="icon" />;
    render(<Button icon={<Icon />}>مع أيقونة</Button>);
    
    expect(screen.getByTestId('icon')).toBeInTheDocument();
    expect(screen.getByText('مع أيقونة')).toBeInTheDocument();
  });

  it('should apply custom className', () => {
    render(<Button className="custom-class">مخصص</Button>);
    expect(screen.getByRole('button')).toHaveClass('custom-class');
  });

  it('should be accessible', async () => {
    const { container } = render(
      <Button aria-label="زر الإرسال">إرسال</Button>
    );
    
    await expectToBeAccessible(container);
    expect(screen.getByRole('button')).toHaveAccessibleName('زر الإرسال');
  });

  it('should handle different button types', () => {
    const { rerender } = render(<Button type="submit">إرسال</Button>);
    expect(screen.getByRole('button')).toHaveAttribute('type', 'submit');

    rerender(<Button type="reset">إعادة تعيين</Button>);
    expect(screen.getByRole('button')).toHaveAttribute('type', 'reset');

    rerender(<Button type="button">زر</Button>);
    expect(screen.getByRole('button')).toHaveAttribute('type', 'button');
  });

  it('should handle form submission', async () => {
    const handleSubmit = vi.fn((e) => e.preventDefault());
    
    render(
      <form onSubmit={handleSubmit}>
        <Button type="submit">إرسال النموذج</Button>
      </form>
    );

    await clickButton('إرسال النموذج');
    expect(handleSubmit).toHaveBeenCalledTimes(1);
  });

  it('should maintain focus after click', async () => {
    render(<Button>تركيز</Button>);
    
    const button = screen.getByRole('button');
    await clickButton('تركيز');
    expect(document.activeElement).toBe(button);
  });

  it('should handle keyboard navigation', async () => {
    const handleClick = vi.fn();
    render(<Button onClick={handleClick}>مفتاح</Button>);
    
    const button = screen.getByRole('button');
    button.focus();
    
    // Simulate Enter key press
    const enterEvent = new KeyboardEvent('keydown', { key: 'Enter' });
    button.dispatchEvent(enterEvent);
    
    // Simulate Space key press
    const spaceEvent = new KeyboardEvent('keydown', { key: ' ' });
    button.dispatchEvent(spaceEvent);
  });

  it('should handle long text with ellipsis', () => {
    const longText = 'هذا نص طويل جداً يجب أن يتم قطعه بعلامة الحذف';
    render(
      <Button className="max-w-xs overflow-hidden text-ellipsis">
        {longText}
      </Button>
    );
    
    const button = screen.getByRole('button');
    expect(button).toHaveTextContent(longText);
    expect(button).toHaveClass('overflow-hidden text-ellipsis');
  });

  it('should handle RTL layout correctly', () => {
    render(<Button>زر عربي</Button>);
    
    const button = screen.getByRole('button');
    expect(button).toHaveAttribute('dir', 'rtl');
  });
});

// Integration tests
describe('Button Integration Tests', () => {
  it('should work within a form with validation', async () => {
    const handleSubmit = vi.fn();
    let isValid = false;

    const FormWithButton = () => {
      const [error, setError] = React.useState('');

      const onSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!isValid) {
          setError('يرجى ملء جميع الحقول المطلوبة');
          return;
        }
        handleSubmit();
      };

      return (
        <form onSubmit={onSubmit}>
          {error && <div role="alert">{error}</div>}
          <Button type="submit">إرسال</Button>
        </form>
      );
    };

    render(<FormWithButton />);

    // Submit with invalid form
    await clickButton('إرسال');
    expect(screen.getByRole('alert')).toHaveTextContent('يرجى ملء جميع الحقول المطلوبة');
    expect(handleSubmit).not.toHaveBeenCalled();

    // Make form valid and submit
    isValid = true;
    await clickButton('إرسال');
    expect(handleSubmit).toHaveBeenCalledTimes(1);
  });

  it('should work with tooltip', async () => {
    render(
      <div>
        <Button title="هذا زر مهم">زر مع تلميح</Button>
      </div>
    );

    const button = screen.getByRole('button');
    expect(button).toHaveAttribute('title', 'هذا زر مهم');
  });
});