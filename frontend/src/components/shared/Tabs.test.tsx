import { describe, it, expect, vi } from 'vitest';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { Tabs } from './Tabs';

describe('Tabs Component', () => {
  const defaultTabs = [
    { id: 'tab1', label: 'المعلومات الأساسية', content: <div>محتوى المعلومات الأساسية</div> },
    { id: 'tab2', label: 'التفاصيل', content: <div>محتوى التفاصيل</div> },
    { id: 'tab3', label: 'الإعدادات', content: <div>محتوى الإعدادات</div>, disabled: true },
  ];

  const defaultProps = {
    tabs: defaultTabs,
  };

  it('يجب أن يتم عرض التبويبات بشكل صحيح', () => {
    render(<Tabs {...defaultProps} />);
    
    expect(screen.getByText('المعلومات الأساسية')).toBeInTheDocument();
    expect(screen.getByText('التفاصيل')).toBeInTheDocument();
    expect(screen.getByText('الإعدادات')).toBeInTheDocument();
    
    // التحقق من المحتوى الافتراضي
    expect(screen.getByText('محتوى المعلومات الأساسية')).toBeInTheDocument();
  });

  it('يجب أن يتم التبديل بين التبويبات', async () => {
    render(<Tabs {...defaultProps} />);
    
    // التحقق من المحتوى الأول
    expect(screen.getByText('محتوى المعلومات الأساسية')).toBeInTheDocument();
    expect(screen.queryByText('محتوى التفاصيل')).not.toBeInTheDocument();
    
    // النقر على التبويب الثاني
    await userEvent.click(screen.getByText('التفاصيل'));
    
    // التحقق من تغيير المحتوى
    expect(screen.queryByText('محتوى المعلومات الأساسية')).not.toBeInTheDocument();
    expect(screen.getByText('محتوى التفاصيل')).toBeInTheDocument();
  });

  it('يجب أن يتم تعطيل التبويبات المعطلة', async () => {
    render(<Tabs {...defaultProps} />);
    
    const disabledTab = screen.getByText('الإعدادات');
    expect(disabledTab.closest('button')).toBeDisabled();
    
    // محاولة النقر على التبويب المعطل
    await userEvent.click(disabledTab);
    
    // التحقق من عدم تغيير المحتوى
    expect(screen.getByText('محتوى المعلومات الأساسية')).toBeInTheDocument();
  });

  it('يجب أن يتم دعم التبويب الافتراضي', () => {
    render(<Tabs {...defaultProps} defaultActiveTab="tab2" />);
    
    expect(screen.getByText('محتوى التفاصيل')).toBeInTheDocument();
    expect(screen.queryByText('محتوى المعلومات الأساسية')).not.toBeInTheDocument();
  });

  it('يجب أن يتم دعم وضع التحكم الخارجي', async () => {
    const onChange = vi.fn();
    render(
      <Tabs 
        {...defaultProps} 
        activeTab="tab1" 
        onChange={onChange}
      />
    );
    
    await userEvent.click(screen.getByText('التفاصيل'));
    expect(onChange).toHaveBeenCalledWith('tab2');
  });

  it('يجب أن يتم دعم الأيقونات في التبويبات', () => {
    const tabsWithIcons = [
      {
        id: 'tab1',
        label: 'المعلومات',
        icon: <span data-testid="info-icon">ℹ️</span>,
        content: <div>محتوى</div>,
      },
    ];
    
    render(<Tabs tabs={tabsWithIcons} />);
    
    expect(screen.getByTestId('info-icon')).toBeInTheDocument();
  });

  it('يجب أن يتم دعم الشارات في التبويبات', () => {
    const tabsWithBadges = [
      {
        id: 'tab1',
        label: 'الرسائل',
        badge: '5',
        content: <div>محتوى</div>,
      },
    ];
    
    render(<Tabs tabs={tabsWithBadges} />);
    
    expect(screen.getByText('5')).toBeInTheDocument();
  });

  it('يجب أن يتم دعم الأنماط المختلفة', () => {
    const { rerender, container } = render(<Tabs {...defaultProps} variant="pills" />);
    
    let tabButtons = container.querySelectorAll('button');
    tabButtons.forEach(button => {
      expect(button).toHaveClass('rounded-full');
    });
    
    rerender(<Tabs {...defaultProps} variant="underline" />);
    tabButtons = container.querySelectorAll('button');
    expect(tabButtons[0]).toHaveClass('border-b-2');
  });

  it('يجب أن يتم دعم الأحجام المختلفة', () => {
    const { rerender, container } = render(<Tabs {...defaultProps} size="small" />);
    
    let tabButtons = container.querySelectorAll('button');
    tabButtons.forEach(button => {
      expect(button).toHaveClass('text-sm');
    });
    
    rerender(<Tabs {...defaultProps} size="large" />);
    tabButtons = container.querySelectorAll('button');
    tabButtons.forEach(button => {
      expect(button).toHaveClass('text-lg');
    });
  });

  it('يجب أن يتم دعم التنقل بلوحة المفاتيح', async () => {
    render(<Tabs {...defaultProps} />);
    
    const firstTab = screen.getByText('المعلومات الأساسية');
    const secondTab = screen.getByText('التفاصيل');
    
    // التركيز على التبويب الأول
    firstTab.focus();
    expect(document.activeElement).toBe(firstTab.closest('button'));
    
    // التنقل باستخدام السهم الأيمن
    await userEvent.keyboard('{ArrowRight}');
    expect(document.activeElement).toBe(secondTab.closest('button'));
  });

  it('يجب أن يتم دعم التبويبات القابلة للإغلاق', async () => {
    const onClose = vi.fn();
    const closableTabs = [
      {
        id: 'tab1',
        label: 'تبويب قابل للإغلاق',
        closable: true,
        content: <div>محتوى</div>,
      },
    ];
    
    render(<Tabs tabs={closableTabs} onClose={onClose} />);
    
    const closeButton = screen.getByLabelText('إغلاق التبويب');
    await userEvent.click(closeButton);
    
    expect(onClose).toHaveBeenCalledWith('tab1');
  });

  it('يجب أن يتم دعم التبويبات العمودية', () => {
    const { container } = render(<Tabs {...defaultProps} orientation="vertical" />);
    
    const tabsContainer = container.querySelector('.tabs-container');
    expect(tabsContainer).toHaveClass('flex-col');
  });

  it('يجب أن يتم دعم ملء العرض الكامل', () => {
    const { container } = render(<Tabs {...defaultProps} fullWidth />);
    
    const tabButtons = container.querySelectorAll('button');
    tabButtons.forEach(button => {
      expect(button).toHaveClass('flex-1');
    });
  });

  it('يجب أن يتم دعم التبويبات اللاصقة', () => {
    const { container } = render(<Tabs {...defaultProps} sticky />);
    
    const tabsHeader = container.querySelector('.tabs-header');
    expect(tabsHeader).toHaveClass('sticky');
    expect(tabsHeader).toHaveClass('top-0');
  });

  it('يجب أن يتم دعم التمرير للتبويبات الكثيرة', () => {
    const manyTabs = Array.from({ length: 20 }, (_, i) => ({
      id: `tab${i}`,
      label: `تبويب ${i + 1}`,
      content: <div>محتوى {i + 1}</div>,
    }));
    
    const { container } = render(<Tabs tabs={manyTabs} scrollable />);
    
    const tabsContainer = container.querySelector('.tabs-container');
    expect(tabsContainer).toHaveClass('overflow-x-auto');
  });

  it('يجب أن يتم دعم تحميل المحتوى بشكل كسول', async () => {
    const lazyContent = vi.fn(() => <div>محتوى محمل</div>);
    const lazyTabs = [
      {
        id: 'tab1',
        label: 'تبويب كسول',
        content: lazyContent,
        lazy: true,
      },
    ];
    
    render(<Tabs tabs={lazyTabs} />);
    
    await screen.findByText('محتوى محمل');
    expect(lazyContent).toHaveBeenCalled();
  });

  it('يجب أن يتم دعم التبويبات مع روابط', () => {
    const tabsWithLinks = [
      {
        id: 'tab1',
        label: 'رابط خارجي',
        href: 'https://example.com',
        content: <div>محتوى</div>,
      },
    ];
    
    render(<Tabs tabs={tabsWithLinks} />);
    
    const linkTab = screen.getByText('رابط خارجي').closest('a');
    expect(linkTab).toHaveAttribute('href', 'https://example.com');
  });

  it('يجب أن يتم دعم التبويبات المخصصة', () => {
    const customTab = ({ tab, isActive }: any) => (
      <div className={`custom-tab ${isActive ? 'active' : ''}`}>
        {tab.label}
      </div>
    );
    
    render(<Tabs {...defaultProps} renderTab={customTab} />);
    
    expect(screen.getByText('المعلومات الأساسية').parentElement).toHaveClass('custom-tab');
    expect(screen.getByText('المعلومات الأساسية').parentElement).toHaveClass('active');
  });

  it('يجب أن يتم دعم الفئات المخصصة', () => {
    const { container } = render(
      <Tabs 
        {...defaultProps} 
        className="custom-tabs"
        tabClassName="custom-tab"
        contentClassName="custom-content"
      />
    );
    
    expect(container.firstChild).toHaveClass('custom-tabs');
    
    const tabs = container.querySelectorAll('button');
    tabs.forEach(tab => {
      expect(tab).toHaveClass('custom-tab');
    });
    
    const content = container.querySelector('.tab-content');
    expect(content).toHaveClass('custom-content');
  });

  it('يجب أن يتم دعم خطافات دورة الحياة', async () => {
    const onBeforeChange = vi.fn(() => true);
    const onAfterChange = vi.fn();
    
    render(
      <Tabs 
        {...defaultProps} 
        onBeforeChange={onBeforeChange}
        onAfterChange={onAfterChange}
      />
    );
    
    await userEvent.click(screen.getByText('التفاصيل'));
    
    expect(onBeforeChange).toHaveBeenCalledWith('tab1', 'tab2');
    expect(onAfterChange).toHaveBeenCalledWith('tab2');
  });

  it('يجب أن يتم دعم إلغاء التبديل', async () => {
    const onBeforeChange = vi.fn(() => false);
    
    render(<Tabs {...defaultProps} onBeforeChange={onBeforeChange} />);
    
    await userEvent.click(screen.getByText('التفاصيل'));
    
    // التحقق من بقاء المحتوى الأول
    expect(screen.getByText('محتوى المعلومات الأساسية')).toBeInTheDocument();
  });

  it('يجب أن يتم دعم التبويبات مع تلميحات', () => {
    const tabsWithTooltips = [
      {
        id: 'tab1',
        label: 'تبويب',
        tooltip: 'هذا تلميح للتبويب',
        content: <div>محتوى</div>,
      },
    ];
    
    render(<Tabs tabs={tabsWithTooltips} />);
    
    const tab = screen.getByText('تبويب').closest('button');
    expect(tab).toHaveAttribute('title', 'هذا تلميح للتبويب');
  });

  it('يجب أن يتم دعم الرسوم المتحركة', () => {
    const { container } = render(<Tabs {...defaultProps} animated />);
    
    const content = container.querySelector('.tab-content');
    expect(content).toHaveClass('transition-opacity');
  });

  it('يجب أن يتم دعم وضع RTL', () => {
    const { container } = render(<Tabs {...defaultProps} rtl />);
    
    const tabsContainer = container.querySelector('.tabs-container');
    expect(tabsContainer).toHaveAttribute('dir', 'rtl');
  });
});