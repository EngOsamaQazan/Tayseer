import { describe, it, expect, vi } from 'vitest';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { Select } from './Select';

describe('Select Component', () => {
  const defaultOptions = [
    { value: 'option1', label: 'الخيار الأول' },
    { value: 'option2', label: 'الخيار الثاني' },
    { value: 'option3', label: 'الخيار الثالث' },
  ];

  const defaultProps = {
    options: defaultOptions,
    label: 'اختر خيار',
    placeholder: 'اختر من القائمة',
  };

  it('يجب أن يتم عرض Select بشكل صحيح', () => {
    render(<Select {...defaultProps} />);
    
    expect(screen.getByText('اختر خيار')).toBeInTheDocument();
    expect(screen.getByText('اختر من القائمة')).toBeInTheDocument();
  });

  it('يجب أن يتم فتح القائمة عند النقر', async () => {
    render(<Select {...defaultProps} />);
    
    const selectButton = screen.getByRole('combobox');
    await userEvent.click(selectButton);
    
    expect(screen.getByText('الخيار الأول')).toBeInTheDocument();
    expect(screen.getByText('الخيار الثاني')).toBeInTheDocument();
    expect(screen.getByText('الخيار الثالث')).toBeInTheDocument();
  });

  it('يجب أن يتم اختيار عنصر عند النقر عليه', async () => {
    const onChange = vi.fn();
    render(<Select {...defaultProps} onChange={onChange} />);
    
    const selectButton = screen.getByRole('combobox');
    await userEvent.click(selectButton);
    
    const option = screen.getByText('الخيار الثاني');
    await userEvent.click(option);
    
    expect(onChange).toHaveBeenCalledWith('option2');
    expect(screen.getByText('الخيار الثاني')).toBeInTheDocument();
  });

  it('يجب أن يتم عرض القيمة المحددة', () => {
    render(<Select {...defaultProps} value="option2" />);
    
    expect(screen.getByText('الخيار الثاني')).toBeInTheDocument();
  });

  it('يجب أن يتم البحث في الخيارات', async () => {
    render(<Select {...defaultProps} searchable />);
    
    const selectButton = screen.getByRole('combobox');
    await userEvent.click(selectButton);
    
    const searchInput = screen.getByPlaceholderText('ابحث...');
    await userEvent.type(searchInput, 'الثاني');
    
    expect(screen.getByText('الخيار الثاني')).toBeInTheDocument();
    expect(screen.queryByText('الخيار الأول')).not.toBeInTheDocument();
    expect(screen.queryByText('الخيار الثالث')).not.toBeInTheDocument();
  });

  it('يجب أن يتم عرض رسالة عند عدم وجود نتائج', async () => {
    render(<Select {...defaultProps} searchable />);
    
    const selectButton = screen.getByRole('combobox');
    await userEvent.click(selectButton);
    
    const searchInput = screen.getByPlaceholderText('ابحث...');
    await userEvent.type(searchInput, 'غير موجود');
    
    expect(screen.getByText('لا توجد نتائج')).toBeInTheDocument();
  });

  it('يجب أن يتم دعم الاختيار المتعدد', async () => {
    const onChange = vi.fn();
    render(<Select {...defaultProps} multiple onChange={onChange} />);
    
    const selectButton = screen.getByRole('combobox');
    await userEvent.click(selectButton);
    
    const option1 = screen.getByText('الخيار الأول');
    const option2 = screen.getByText('الخيار الثاني');
    
    await userEvent.click(option1);
    expect(onChange).toHaveBeenCalledWith(['option1']);
    
    await userEvent.click(option2);
    expect(onChange).toHaveBeenCalledWith(['option1', 'option2']);
  });

  it('يجب أن يتم إلغاء تحديد عنصر في الاختيار المتعدد', async () => {
    const onChange = vi.fn();
    render(
      <Select {...defaultProps} multiple value={['option1', 'option2']} onChange={onChange} />
    );
    
    const selectButton = screen.getByRole('combobox');
    await userEvent.click(selectButton);
    
    const option1 = screen.getByText('الخيار الأول');
    await userEvent.click(option1);
    
    expect(onChange).toHaveBeenCalledWith(['option2']);
  });

  it('يجب أن يتم عرض الشرائح (chips) في الاختيار المتعدد', () => {
    render(<Select {...defaultProps} multiple value={['option1', 'option2']} />);
    
    expect(screen.getByText('الخيار الأول')).toBeInTheDocument();
    expect(screen.getByText('الخيار الثاني')).toBeInTheDocument();
  });

  it('يجب أن يتم تعطيل Select عند disabled', async () => {
    render(<Select {...defaultProps} disabled />);
    
    const selectButton = screen.getByRole('combobox');
    expect(selectButton).toBeDisabled();
    
    await userEvent.click(selectButton);
    expect(screen.queryByText('الخيار الأول')).not.toBeInTheDocument();
  });

  it('يجب أن يتم عرض رسالة الخطأ', () => {
    render(<Select {...defaultProps} error="يرجى اختيار قيمة صحيحة" />);
    
    expect(screen.getByText('يرجى اختيار قيمة صحيحة')).toBeInTheDocument();
  });

  it('يجب أن يتم عرض النص المساعد', () => {
    render(<Select {...defaultProps} helperText="اختر خيار واحد من القائمة" />);
    
    expect(screen.getByText('اختر خيار واحد من القائمة')).toBeInTheDocument();
  });

  it('يجب أن يتم دعم التحميل', () => {
    render(<Select {...defaultProps} loading />);
    
    expect(screen.getByText('جاري التحميل...')).toBeInTheDocument();
  });

  it('يجب أن يتم إغلاق القائمة عند النقر خارجها', async () => {
    render(
      <div>
        <Select {...defaultProps} />
        <button>زر خارجي</button>
      </div>
    );
    
    const selectButton = screen.getByRole('combobox');
    await userEvent.click(selectButton);
    
    expect(screen.getByText('الخيار الأول')).toBeInTheDocument();
    
    const outsideButton = screen.getByText('زر خارجي');
    await userEvent.click(outsideButton);
    
    await waitFor(() => {
      expect(screen.queryByText('الخيار الأول')).not.toBeInTheDocument();
    });
  });

  it('يجب أن يتم التنقل بلوحة المفاتيح', async () => {
    const onChange = vi.fn();
    render(<Select {...defaultProps} onChange={onChange} />);
    
    const selectButton = screen.getByRole('combobox');
    selectButton.focus();
    
    // فتح القائمة بالمسافة
    await userEvent.keyboard(' ');
    expect(screen.getByText('الخيار الأول')).toBeInTheDocument();
    
    // التنقل لأسفل
    await userEvent.keyboard('{ArrowDown}');
    await userEvent.keyboard('{ArrowDown}');
    
    // اختيار بـ Enter
    await userEvent.keyboard('{Enter}');
    expect(onChange).toHaveBeenCalledWith('option2');
  });

  it('يجب أن يتم إغلاق القائمة بـ Escape', async () => {
    render(<Select {...defaultProps} />);
    
    const selectButton = screen.getByRole('combobox');
    await userEvent.click(selectButton);
    
    expect(screen.getByText('الخيار الأول')).toBeInTheDocument();
    
    await userEvent.keyboard('{Escape}');
    
    await waitFor(() => {
      expect(screen.queryByText('الخيار الأول')).not.toBeInTheDocument();
    });
  });

  it('يجب أن يتم دعم الخيارات المعطلة', async () => {
    const optionsWithDisabled = [
      { value: 'option1', label: 'الخيار الأول' },
      { value: 'option2', label: 'الخيار الثاني', disabled: true },
      { value: 'option3', label: 'الخيار الثالث' },
    ];
    
    const onChange = vi.fn();
    render(<Select {...defaultProps} options={optionsWithDisabled} onChange={onChange} />);
    
    const selectButton = screen.getByRole('combobox');
    await userEvent.click(selectButton);
    
    const disabledOption = screen.getByText('الخيار الثاني');
    expect(disabledOption.closest('li')).toHaveClass('opacity-50');
    
    await userEvent.click(disabledOption);
    expect(onChange).not.toHaveBeenCalled();
  });

  it('يجب أن يتم دعم المجموعات', async () => {
    const groupedOptions = [
      {
        label: 'المجموعة الأولى',
        options: [
          { value: 'option1', label: 'الخيار 1-1' },
          { value: 'option2', label: 'الخيار 1-2' },
        ],
      },
      {
        label: 'المجموعة الثانية',
        options: [
          { value: 'option3', label: 'الخيار 2-1' },
          { value: 'option4', label: 'الخيار 2-2' },
        ],
      },
    ];
    
    render(<Select {...defaultProps} options={groupedOptions} />);
    
    const selectButton = screen.getByRole('combobox');
    await userEvent.click(selectButton);
    
    expect(screen.getByText('المجموعة الأولى')).toBeInTheDocument();
    expect(screen.getByText('المجموعة الثانية')).toBeInTheDocument();
    expect(screen.getByText('الخيار 1-1')).toBeInTheDocument();
    expect(screen.getByText('الخيار 2-1')).toBeInTheDocument();
  });

  it('يجب أن يتم دعم التحميل غير المتزامن', async () => {
    const loadOptions = vi.fn().mockResolvedValue([
      { value: 'async1', label: 'خيار غير متزامن 1' },
      { value: 'async2', label: 'خيار غير متزامن 2' },
    ]);
    
    render(<Select {...defaultProps} loadOptions={loadOptions} />);
    
    const selectButton = screen.getByRole('combobox');
    await userEvent.click(selectButton);
    
    expect(screen.getByText('جاري التحميل...')).toBeInTheDocument();
    
    await waitFor(() => {
      expect(screen.getByText('خيار غير متزامن 1')).toBeInTheDocument();
      expect(screen.getByText('خيار غير متزامن 2')).toBeInTheDocument();
    });
  });

  it('يجب أن يتم مسح القيمة', async () => {
    const onChange = vi.fn();
    render(<Select {...defaultProps} value="option1" clearable onChange={onChange} />);
    
    const clearButton = screen.getByLabelText('مسح');
    await userEvent.click(clearButton);
    
    expect(onChange).toHaveBeenCalledWith(null);
  });

  it('يجب أن يتم دعم الأحجام المختلفة', () => {
    const { rerender } = render(<Select {...defaultProps} size="small" />);
    
    let selectButton = screen.getByRole('combobox');
    expect(selectButton).toHaveClass('h-8');
    
    rerender(<Select {...defaultProps} size="medium" />);
    selectButton = screen.getByRole('combobox');
    expect(selectButton).toHaveClass('h-10');
    
    rerender(<Select {...defaultProps} size="large" />);
    selectButton = screen.getByRole('combobox');
    expect(selectButton).toHaveClass('h-12');
  });

  it('يجب أن يتم دعم الأيقونات المخصصة', () => {
    const CustomIcon = () => <span>🔽</span>;
    render(<Select {...defaultProps} icon={<CustomIcon />} />);
    
    expect(screen.getByText('🔽')).toBeInTheDocument();
  });

  it('يجب أن يتم دعم العرض المخصص للخيارات', async () => {
    const customOptions = [
      { value: 'option1', label: 'الخيار الأول', icon: '🏠' },
      { value: 'option2', label: 'الخيار الثاني', icon: '🏢' },
    ];
    
    const renderOption = (option: any) => (
      <div className="flex items-center gap-2">
        <span>{option.icon}</span>
        <span>{option.label}</span>
      </div>
    );
    
    render(
      <Select 
        {...defaultProps} 
        options={customOptions} 
        renderOption={renderOption}
      />
    );
    
    const selectButton = screen.getByRole('combobox');
    await userEvent.click(selectButton);
    
    expect(screen.getByText('🏠')).toBeInTheDocument();
    expect(screen.getByText('🏢')).toBeInTheDocument();
  });

  it('يجب أن يتم دعم التحقق من الصحة', () => {
    render(<Select {...defaultProps} required />);
    
    const selectButton = screen.getByRole('combobox');
    expect(selectButton).toHaveAttribute('aria-required', 'true');
  });

  it('يجب أن يتم دعم RTL', () => {
    render(<Select {...defaultProps} dir="rtl" />);
    
    const selectButton = screen.getByRole('combobox');
    expect(selectButton).toHaveAttribute('dir', 'rtl');
  });

  it('يجب أن يتم دعم الوصولية', async () => {
    render(<Select {...defaultProps} />);
    
    const selectButton = screen.getByRole('combobox');
    expect(selectButton).toHaveAttribute('aria-expanded', 'false');
    expect(selectButton).toHaveAttribute('aria-haspopup', 'listbox');
    
    await userEvent.click(selectButton);
    
    expect(selectButton).toHaveAttribute('aria-expanded', 'true');
    
    const listbox = screen.getByRole('listbox');
    expect(listbox).toBeInTheDocument();
    
    const options = screen.getAllByRole('option');
    expect(options).toHaveLength(3);
  });

  it('يجب أن يتم حفظ موضع القائمة المنسدلة', async () => {
    Object.defineProperty(window, 'innerHeight', {
      writable: true,
      configurable: true,
      value: 500,
    });
    
    const { container } = render(<Select {...defaultProps} />);
    
    const selectButton = screen.getByRole('combobox');
    
    // محاكاة موضع في أسفل الشاشة
    selectButton.getBoundingClientRect = vi.fn(() => ({
      bottom: 480,
      top: 450,
      left: 0,
      right: 100,
      width: 100,
      height: 30,
      x: 0,
      y: 450,
      toJSON: () => {},
    }));
    
    await userEvent.click(selectButton);
    
    const dropdown = container.querySelector('[role="listbox"]');
    expect(dropdown).toHaveClass('bottom-full');
  });

  it('يجب أن يتم التعامل مع القيم الفارغة', () => {
    render(<Select {...defaultProps} value={null} />);
    
    expect(screen.getByText('اختر من القائمة')).toBeInTheDocument();
  });

  it('يجب أن يتم دعم onBlur و onFocus', async () => {
    const onBlur = vi.fn();
    const onFocus = vi.fn();
    
    render(<Select {...defaultProps} onBlur={onBlur} onFocus={onFocus} />);
    
    const selectButton = screen.getByRole('combobox');
    
    await userEvent.click(selectButton);
    expect(onFocus).toHaveBeenCalledTimes(1);
    
    await userEvent.click(document.body);
    expect(onBlur).toHaveBeenCalledTimes(1);
  });

  it('يجب أن يتم تصفية الخيارات بشكل صحيح', async () => {
    const manyOptions = [
      { value: 'apple', label: 'تفاح' },
      { value: 'banana', label: 'موز' },
      { value: 'orange', label: 'برتقال' },
      { value: 'grape', label: 'عنب' },
      { value: 'watermelon', label: 'بطيخ' },
    ];
    
    render(<Select {...defaultProps} options={manyOptions} searchable />);
    
    const selectButton = screen.getByRole('combobox');
    await userEvent.click(selectButton);
    
    const searchInput = screen.getByPlaceholderText('ابحث...');
    await userEvent.type(searchInput, 'ت');
    
    expect(screen.getByText('تفاح')).toBeInTheDocument();
    expect(screen.getByText('برتقال')).toBeInTheDocument();
    expect(screen.queryByText('موز')).not.toBeInTheDocument();
    expect(screen.queryByText('عنب')).not.toBeInTheDocument();
    expect(screen.queryByText('بطيخ')).not.toBeInTheDocument();
  });

  it('يجب أن يتم دعم التمرير في القائمة الطويلة', async () => {
    const longOptions = Array.from({ length: 50 }, (_, i) => ({
      value: `option${i}`,
      label: `الخيار ${i + 1}`,
    }));
    
    render(<Select {...defaultProps} options={longOptions} />);
    
    const selectButton = screen.getByRole('combobox');
    await userEvent.click(selectButton);
    
    const listbox = screen.getByRole('listbox');
    expect(listbox).toHaveClass('overflow-y-auto');
    expect(listbox).toHaveClass('max-h-60');
  });
});