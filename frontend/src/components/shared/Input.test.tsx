import { describe, it, expect, vi } from 'vitest';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { Input } from './Input';

describe('Input Component', () => {
  const defaultProps = {
    label: 'اسم المستخدم',
    placeholder: 'أدخل اسم المستخدم',
  };

  it('يجب أن يتم عرض Input بشكل صحيح', () => {
    render(<Input {...defaultProps} />);
    
    expect(screen.getByText('اسم المستخدم')).toBeInTheDocument();
    expect(screen.getByPlaceholderText('أدخل اسم المستخدم')).toBeInTheDocument();
  });

  it('يجب أن يتم إدخال النص بشكل صحيح', async () => {
    const onChange = vi.fn();
    render(<Input {...defaultProps} onChange={onChange} />);
    
    const input = screen.getByPlaceholderText('أدخل اسم المستخدم');
    await userEvent.type(input, 'أحمد محمد');
    
    expect(input).toHaveValue('أحمد محمد');
    expect(onChange).toHaveBeenCalledTimes(8); // عدد الأحرف
  });

  it('يجب أن يتم عرض القيمة المحددة', () => {
    render(<Input {...defaultProps} value="قيمة محددة" />);
    
    const input = screen.getByPlaceholderText('أدخل اسم المستخدم');
    expect(input).toHaveValue('قيمة محددة');
  });

  it('يجب أن يتم تعطيل Input عند disabled', () => {
    render(<Input {...defaultProps} disabled />);
    
    const input = screen.getByPlaceholderText('أدخل اسم المستخدم');
    expect(input).toBeDisabled();
  });

  it('يجب أن يتم عرض رسالة الخطأ', () => {
    render(<Input {...defaultProps} error="يرجى إدخال قيمة صحيحة" />);
    
    expect(screen.getByText('يرجى إدخال قيمة صحيحة')).toBeInTheDocument();
    const input = screen.getByPlaceholderText('أدخل اسم المستخدم');
    expect(input).toHaveClass('border-red-500');
  });

  it('يجب أن يتم عرض النص المساعد', () => {
    render(<Input {...defaultProps} helperText="يجب أن يكون الاسم باللغة العربية" />);
    
    expect(screen.getByText('يجب أن يكون الاسم باللغة العربية')).toBeInTheDocument();
  });

  it('يجب أن يتم دعم الأنواع المختلفة', () => {
    const { rerender } = render(<Input {...defaultProps} type="email" />);
    
    let input = screen.getByPlaceholderText('أدخل اسم المستخدم');
    expect(input).toHaveAttribute('type', 'email');
    
    rerender(<Input {...defaultProps} type="password" />);
    input = screen.getByPlaceholderText('أدخل اسم المستخدم');
    expect(input).toHaveAttribute('type', 'password');
    
    rerender(<Input {...defaultProps} type="number" />);
    input = screen.getByPlaceholderText('أدخل اسم المستخدم');
    expect(input).toHaveAttribute('type', 'number');
  });

  it('يجب أن يتم دعم الحقل المطلوب', () => {
    render(<Input {...defaultProps} required />);
    
    const input = screen.getByPlaceholderText('أدخل اسم المستخدم');
    expect(input).toHaveAttribute('required');
    
    const label = screen.getByText('اسم المستخدم');
    expect(label.parentElement).toHaveTextContent('*');
  });

  it('يجب أن يتم دعم الأيقونات', () => {
    const StartIcon = () => <span>👤</span>;
    const EndIcon = () => <span>✓</span>;
    
    render(
      <Input 
        {...defaultProps} 
        startIcon={<StartIcon />}
        endIcon={<EndIcon />}
      />
    );
    
    expect(screen.getByText('👤')).toBeInTheDocument();
    expect(screen.getByText('✓')).toBeInTheDocument();
  });

  it('يجب أن يتم دعم onBlur و onFocus', async () => {
    const onBlur = vi.fn();
    const onFocus = vi.fn();
    
    render(<Input {...defaultProps} onBlur={onBlur} onFocus={onFocus} />);
    
    const input = screen.getByPlaceholderText('أدخل اسم المستخدم');
    
    await userEvent.click(input);
    expect(onFocus).toHaveBeenCalledTimes(1);
    
    await userEvent.click(document.body);
    expect(onBlur).toHaveBeenCalledTimes(1);
  });

  it('يجب أن يتم دعم maxLength', async () => {
    render(<Input {...defaultProps} maxLength={5} />);
    
    const input = screen.getByPlaceholderText('أدخل اسم المستخدم');
    await userEvent.type(input, 'اختبار طويل');
    
    expect(input).toHaveValue('اختبا'); // 5 أحرف فقط
  });

  it('يجب أن يتم دعم minLength', () => {
    render(<Input {...defaultProps} minLength={3} />);
    
    const input = screen.getByPlaceholderText('أدخل اسم المستخدم');
    expect(input).toHaveAttribute('minLength', '3');
  });

  it('يجب أن يتم دعم pattern', () => {
    render(<Input {...defaultProps} pattern="[أ-ي]+" />);
    
    const input = screen.getByPlaceholderText('أدخل اسم المستخدم');
    expect(input).toHaveAttribute('pattern', '[أ-ي]+');
  });

  it('يجب أن يتم دعم autoComplete', () => {
    render(<Input {...defaultProps} autoComplete="username" />);
    
    const input = screen.getByPlaceholderText('أدخل اسم المستخدم');
    expect(input).toHaveAttribute('autocomplete', 'username');
  });

  it('يجب أن يتم دعم autoFocus', () => {
    render(<Input {...defaultProps} autoFocus />);
    
    const input = screen.getByPlaceholderText('أدخل اسم المستخدم');
    expect(document.activeElement).toBe(input);
  });

  it('يجب أن يتم دعم readOnly', async () => {
    const onChange = vi.fn();
    render(<Input {...defaultProps} readOnly value="قراءة فقط" onChange={onChange} />);
    
    const input = screen.getByPlaceholderText('أدخل اسم المستخدم');
    expect(input).toHaveAttribute('readonly');
    
    await userEvent.type(input, 'نص جديد');
    expect(onChange).not.toHaveBeenCalled();
    expect(input).toHaveValue('قراءة فقط');
  });

  it('يجب أن يتم دعم الأحجام المختلفة', () => {
    const { rerender } = render(<Input {...defaultProps} size="small" />);
    
    let input = screen.getByPlaceholderText('أدخل اسم المستخدم');
    expect(input).toHaveClass('h-8');
    
    rerender(<Input {...defaultProps} size="medium" />);
    input = screen.getByPlaceholderText('أدخل اسم المستخدم');
    expect(input).toHaveClass('h-10');
    
    rerender(<Input {...defaultProps} size="large" />);
    input = screen.getByPlaceholderText('أدخل اسم المستخدم');
    expect(input).toHaveClass('h-12');
  });

  it('يجب أن يتم دعم fullWidth', () => {
    render(<Input {...defaultProps} fullWidth />);
    
    const input = screen.getByPlaceholderText('أدخل اسم المستخدم');
    expect(input.parentElement).toHaveClass('w-full');
  });

  it('يجب أن يتم دعم المتغيرات المختلفة', () => {
    const { rerender } = render(<Input {...defaultProps} variant="outlined" />);
    
    let input = screen.getByPlaceholderText('أدخل اسم المستخدم');
    expect(input).toHaveClass('border');
    
    rerender(<Input {...defaultProps} variant="filled" />);
    input = screen.getByPlaceholderText('أدخل اسم المستخدم');
    expect(input).toHaveClass('bg-gray-100');
    
    rerender(<Input {...defaultProps} variant="standard" />);
    input = screen.getByPlaceholderText('أدخل اسم المستخدم');
    expect(input).toHaveClass('border-b');
  });

  it('يجب أن يتم دعم عرض كلمة المرور', async () => {
    render(<Input {...defaultProps} type="password" showPasswordToggle />);
    
    const input = screen.getByPlaceholderText('أدخل اسم المستخدم');
    expect(input).toHaveAttribute('type', 'password');
    
    const toggleButton = screen.getByLabelText('إظهار كلمة المرور');
    await userEvent.click(toggleButton);
    
    expect(input).toHaveAttribute('type', 'text');
    
    await userEvent.click(toggleButton);
    expect(input).toHaveAttribute('type', 'password');
  });

  it('يجب أن يتم دعم مسح المحتوى', async () => {
    const onChange = vi.fn();
    render(<Input {...defaultProps} value="نص للمسح" clearable onChange={onChange} />);
    
    const clearButton = screen.getByLabelText('مسح');
    await userEvent.click(clearButton);
    
    expect(onChange).toHaveBeenCalledWith('');
  });

  it('يجب أن يتم دعم التحميل', () => {
    render(<Input {...defaultProps} loading />);
    
    expect(screen.getByRole('status')).toBeInTheDocument();
    const input = screen.getByPlaceholderText('أدخل اسم المستخدم');
    expect(input).toBeDisabled();
  });

  it('يجب أن يتم دعم onKeyDown و onKeyUp', async () => {
    const onKeyDown = vi.fn();
    const onKeyUp = vi.fn();
    
    render(<Input {...defaultProps} onKeyDown={onKeyDown} onKeyUp={onKeyUp} />);
    
    const input = screen.getByPlaceholderText('أدخل اسم المستخدم');
    
    await userEvent.type(input, 'a');
    
    expect(onKeyDown).toHaveBeenCalled();
    expect(onKeyUp).toHaveBeenCalled();
  });

  it('يجب أن يتم دعم onPaste', async () => {
    const onPaste = vi.fn();
    render(<Input {...defaultProps} onPaste={onPaste} />);
    
    const input = screen.getByPlaceholderText('أدخل اسم المستخدم');
    
    const pasteEvent = new ClipboardEvent('paste', {
      clipboardData: new DataTransfer(),
    });
    
    input.dispatchEvent(pasteEvent);
    
    expect(onPaste).toHaveBeenCalled();
  });

  it('يجب أن يتم دعم RTL', () => {
    render(<Input {...defaultProps} dir="rtl" />);
    
    const input = screen.getByPlaceholderText('أدخل اسم المستخدم');
    expect(input).toHaveAttribute('dir', 'rtl');
  });

  it('يجب أن يتم دعم الوصولية', () => {
    render(
      <Input 
        {...defaultProps} 
        id="username-input"
        aria-describedby="username-help"
        aria-invalid="true"
      />
    );
    
    const input = screen.getByPlaceholderText('أدخل اسم المستخدم');
    expect(input).toHaveAttribute('id', 'username-input');
    expect(input).toHaveAttribute('aria-describedby', 'username-help');
    expect(input).toHaveAttribute('aria-invalid', 'true');
    
    const label = screen.getByText('اسم المستخدم');
    expect(label).toHaveAttribute('for', 'username-input');
  });

  it('يجب أن يتم دعم التحقق من صحة البريد الإلكتروني', async () => {
    const onValidate = vi.fn();
    render(
      <Input 
        {...defaultProps} 
        type="email" 
        onValidate={onValidate}
      />
    );
    
    const input = screen.getByPlaceholderText('أدخل اسم المستخدم');
    
    await userEvent.type(input, 'invalid-email');
    await userEvent.tab();
    
    await waitFor(() => {
      expect(onValidate).toHaveBeenCalledWith(false);
    });
    
    await userEvent.clear(input);
    await userEvent.type(input, 'valid@email.com');
    await userEvent.tab();
    
    await waitFor(() => {
      expect(onValidate).toHaveBeenCalledWith(true);
    });
  });
});