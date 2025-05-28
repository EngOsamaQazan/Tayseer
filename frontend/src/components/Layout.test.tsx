import { describe, it, expect, vi } from 'vitest';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { Layout } from './Layout';
import { BrowserRouter } from 'react-router-dom';

// Mock للمكونات الفرعية
vi.mock('./Header', () => ({
  Header: () => <header data-testid="header">رأس الصفحة</header>,
}));

vi.mock('./Sidebar', () => ({
  Sidebar: ({ isOpen }: { isOpen: boolean }) => (
    <aside data-testid="sidebar" className={isOpen ? 'open' : 'closed'}>
      الشريط الجانبي
    </aside>
  ),
}));

vi.mock('../contexts/ThemeContext', () => ({
  useTheme: () => ({
    theme: 'light',
    direction: 'rtl',
  }),
}));

describe('Layout Component', () => {
  const renderLayout = (children?: React.ReactNode) => {
    return render(
      <BrowserRouter>
        <Layout>{children || <div>محتوى الصفحة</div>}</Layout>
      </BrowserRouter>
    );
  };

  it('يجب أن يتم عرض Layout بشكل صحيح', () => {
    renderLayout();
    
    expect(screen.getByTestId('header')).toBeInTheDocument();
    expect(screen.getByTestId('sidebar')).toBeInTheDocument();
    expect(screen.getByText('محتوى الصفحة')).toBeInTheDocument();
  });

  it('يجب أن يتم تطبيق الهيكل الصحيح', () => {
    const { container } = renderLayout();
    
    const layout = container.querySelector('.layout-container');
    expect(layout).toBeInTheDocument();
    expect(layout).toHaveClass('rtl');
  });

  it('يجب أن يتم عرض المحتوى الفرعي', () => {
    renderLayout(
      <div>
        <h1>عنوان الصفحة</h1>
        <p>محتوى مخصص</p>
      </div>
    );
    
    expect(screen.getByText('عنوان الصفحة')).toBeInTheDocument();
    expect(screen.getByText('محتوى مخصص')).toBeInTheDocument();
  });

  it('يجب أن يتم تبديل الشريط الجانبي', async () => {
    renderLayout();
    
    const toggleButton = screen.getByLabelText('تبديل الشريط الجانبي');
    const sidebar = screen.getByTestId('sidebar');
    
    expect(sidebar).toHaveClass('open');
    
    await userEvent.click(toggleButton);
    expect(sidebar).toHaveClass('closed');
    
    await userEvent.click(toggleButton);
    expect(sidebar).toHaveClass('open');
  });

  it('يجب أن يتم تطبيق السمة الصحيحة', () => {
    const { container } = renderLayout();
    
    expect(container.firstChild).toHaveClass('theme-light');
  });

  it('يجب أن يتم تطبيق السمة الداكنة', () => {
    const { useTheme } = vi.mocked(await import('../contexts/ThemeContext'));
    vi.mocked(useTheme).mockReturnValue({
      theme: 'dark',
      direction: 'rtl',
    });

    const { container } = renderLayout();
    
    expect(container.firstChild).toHaveClass('theme-dark');
  });

  it('يجب أن يتم عرض تذييل الصفحة', () => {
    renderLayout();
    
    const footer = screen.getByRole('contentinfo');
    expect(footer).toBeInTheDocument();
    expect(footer).toHaveTextContent('جميع الحقوق محفوظة');
  });

  it('يجب أن يتم عرض شريط التنقل الفرعي', () => {
    renderLayout();
    
    const breadcrumb = screen.getByRole('navigation', { name: 'شريط التنقل' });
    expect(breadcrumb).toBeInTheDocument();
  });

  it('يجب أن يتم التعامل مع حالة التحميل', () => {
    render(
      <BrowserRouter>
        <Layout loading>
          <div>محتوى الصفحة</div>
        </Layout>
      </BrowserRouter>
    );
    
    expect(screen.getByText('جاري التحميل...')).toBeInTheDocument();
    expect(screen.queryByText('محتوى الصفحة')).not.toBeInTheDocument();
  });

  it('يجب أن يتم عرض رسائل الخطأ', () => {
    render(
      <BrowserRouter>
        <Layout error="حدث خطأ في تحميل البيانات">
          <div>محتوى الصفحة</div>
        </Layout>
      </BrowserRouter>
    );
    
    expect(screen.getByText('حدث خطأ في تحميل البيانات')).toBeInTheDocument();
    expect(screen.getByRole('alert')).toHaveClass('error');
  });

  it('يجب أن يتم التعامل مع التخطيط في وضع ملء الشاشة', () => {
    render(
      <BrowserRouter>
        <Layout fullScreen>
          <div>محتوى ملء الشاشة</div>
        </Layout>
      </BrowserRouter>
    );
    
    const { container } = render(
      <BrowserRouter>
        <Layout fullScreen>
          <div>محتوى ملء الشاشة</div>
        </Layout>
      </BrowserRouter>
    );
    
    expect(container.querySelector('.layout-container')).toHaveClass('fullscreen');
    expect(screen.queryByTestId('header')).not.toBeInTheDocument();
    expect(screen.queryByTestId('sidebar')).not.toBeInTheDocument();
  });

  it('يجب أن يتم تطبيق الحشو المخصص', () => {
    const { container } = render(
      <BrowserRouter>
        <Layout padding="large">
          <div>محتوى الصفحة</div>
        </Layout>
      </BrowserRouter>
    );
    
    const content = container.querySelector('.layout-content');
    expect(content).toHaveClass('padding-large');
  });

  it('يجب أن يتم دعم التخطيط المرن', () => {
    const { container } = render(
      <BrowserRouter>
        <Layout flex>
          <div>محتوى مرن</div>
        </Layout>
      </BrowserRouter>
    );
    
    const content = container.querySelector('.layout-content');
    expect(content).toHaveStyle({ display: 'flex' });
  });

  it('يجب أن يتم حفظ حالة الشريط الجانبي', async () => {
    const localStorage = {
      getItem: vi.fn().mockReturnValue('closed'),
      setItem: vi.fn(),
    };
    Object.defineProperty(window, 'localStorage', { value: localStorage });

    renderLayout();
    
    const sidebar = screen.getByTestId('sidebar');
    expect(sidebar).toHaveClass('closed');
    
    const toggleButton = screen.getByLabelText('تبديل الشريط الجانبي');
    await userEvent.click(toggleButton);
    
    expect(localStorage.setItem).toHaveBeenCalledWith('sidebar-state', 'open');
  });

  it('يجب أن يتم تطبيق التخطيط المخصص', () => {
    const { container } = render(
      <BrowserRouter>
        <Layout className="custom-layout" maxWidth="1200px">
          <div>محتوى مخصص</div>
        </Layout>
      </BrowserRouter>
    );
    
    const layout = container.querySelector('.layout-container');
    expect(layout).toHaveClass('custom-layout');
    
    const content = container.querySelector('.layout-content');
    expect(content).toHaveStyle({ maxWidth: '1200px' });
  });

  it('يجب أن يتم عرض شريط التقدم أثناء التنقل', async () => {
    renderLayout();
    
    // محاكاة بدء التنقل
    window.dispatchEvent(new Event('navigation-start'));
    
    const progressBar = screen.getByRole('progressbar');
    expect(progressBar).toBeInTheDocument();
    
    // محاكاة انتهاء التنقل
    window.dispatchEvent(new Event('navigation-end'));
    
    expect(screen.queryByRole('progressbar')).not.toBeInTheDocument();
  });

  it('يجب أن يتم دعم التخطيط المتجاوب', () => {
    const { container } = renderLayout();
    
    // شاشة كبيرة
    global.innerWidth = 1200;
    global.dispatchEvent(new Event('resize'));
    
    expect(container.querySelector('.layout-container')).toHaveClass('desktop');
    
    // شاشة متوسطة
    global.innerWidth = 768;
    global.dispatchEvent(new Event('resize'));
    
    expect(container.querySelector('.layout-container')).toHaveClass('tablet');
    
    // شاشة صغيرة
    global.innerWidth = 480;
    global.dispatchEvent(new Event('resize'));
    
    expect(container.querySelector('.layout-container')).toHaveClass('mobile');
  });

  it('يجب أن يتم عرض إشعارات النظام', () => {
    render(
      <BrowserRouter>
        <Layout>
          <div>محتوى الصفحة</div>
        </Layout>
      </BrowserRouter>
    );
    
    // إضافة إشعار
    window.dispatchEvent(new CustomEvent('show-notification', {
      detail: { message: 'تم حفظ البيانات بنجاح', type: 'success' }
    }));
    
    expect(screen.getByText('تم حفظ البيانات بنجاح')).toBeInTheDocument();
    expect(screen.getByRole('alert')).toHaveClass('success');
  });

  it('يجب أن يتم دعم اختصارات لوحة المفاتيح للتنقل', async () => {
    renderLayout();
    
    // اختصار تبديل الشريط الجانبي (Alt + S)
    await userEvent.keyboard('{Alt>}s{/Alt}');
    
    const sidebar = screen.getByTestId('sidebar');
    expect(sidebar).toHaveClass('closed');
  });

  it('يجب أن يتم عرض مؤشر الاتصال بالخادم', () => {
    renderLayout();
    
    // محاكاة فقدان الاتصال
    window.dispatchEvent(new CustomEvent('server-disconnected'));
    
    expect(screen.getByText('الاتصال بالخادم مفقود')).toBeInTheDocument();
    expect(screen.getByRole('status')).toHaveClass('offline');
    
    // محاكاة استعادة الاتصال
    window.dispatchEvent(new CustomEvent('server-connected'));
    
    expect(screen.getByText('متصل بالخادم')).toBeInTheDocument();
    expect(screen.getByRole('status')).toHaveClass('online');
  });

  it('يجب أن يتم دعم تخطيطات متعددة الأعمدة', () => {
    const { container } = render(
      <BrowserRouter>
        <Layout columns={2}>
          <div>العمود الأول</div>
          <div>العمود الثاني</div>
        </Layout>
      </BrowserRouter>
    );
    
    const content = container.querySelector('.layout-content');
    expect(content).toHaveClass('columns-2');
    expect(content).toHaveStyle({ display: 'grid' });
  });

  it('يجب أن يتم التعامل مع أذونات المستخدم', () => {
    vi.mock('../contexts/AuthContext', () => ({
      useAuth: () => ({
        user: { role: 'viewer' },
        permissions: ['read'],
      }),
    }));

    renderLayout();
    
    // التحقق من إخفاء العناصر المحظورة
    expect(screen.queryByText('إضافة جديد')).not.toBeInTheDocument();
    expect(screen.queryByText('حذف')).not.toBeInTheDocument();
  });

  it('يجب أن يتم دعم وضع الطباعة', () => {
    const { container } = renderLayout();
    
    // محاكاة وضع الطباعة
    window.matchMedia = vi.fn().mockImplementation(query => ({
      matches: query === 'print',
      media: query,
      addEventListener: vi.fn(),
      removeEventListener: vi.fn(),
    }));
    
    window.dispatchEvent(new Event('beforeprint'));
    
    expect(container.querySelector('.layout-container')).toHaveClass('print-mode');
    expect(screen.queryByTestId('sidebar')).not.toBeVisible();
  });
});