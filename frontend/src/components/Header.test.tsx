import { describe, it, expect, vi } from 'vitest';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { Header } from './Header';
import { BrowserRouter } from 'react-router-dom';

// Mock للسياق والخطافات
vi.mock('../contexts/AuthContext', () => ({
  useAuth: () => ({
    user: { name: 'أحمد محمد', email: 'ahmad@example.com', role: 'admin' },
    logout: vi.fn(),
  }),
}));

vi.mock('../hooks/useTheme', () => ({
  useTheme: () => ({
    theme: 'light',
    toggleTheme: vi.fn(),
  }),
}));

describe('Header Component', () => {
  const renderHeader = () => {
    return render(
      <BrowserRouter>
        <Header />
      </BrowserRouter>
    );
  };

  it('يجب أن يتم عرض Header بشكل صحيح', () => {
    renderHeader();
    
    // التحقق من شعار منصة التيسير
    expect(screen.getByText('منصة التيسير')).toBeInTheDocument();
  });

  it('يجب أن يتم عرض معلومات المستخدم', () => {
    renderHeader();
    
    expect(screen.getByText('أحمد محمد')).toBeInTheDocument();
  });

  it('يجب أن يتم عرض قائمة التنقل', () => {
    renderHeader();
    
    expect(screen.getByText('الرئيسية')).toBeInTheDocument();
    expect(screen.getByText('المشاريع')).toBeInTheDocument();
    expect(screen.getByText('التقارير')).toBeInTheDocument();
    expect(screen.getByText('الإعدادات')).toBeInTheDocument();
  });

  it('يجب أن يتم تسجيل الخروج عند النقر على زر الخروج', async () => {
    const { useAuth } = await import('../contexts/AuthContext');
    const logout = vi.fn();
    vi.mocked(useAuth).mockReturnValue({
      user: { name: 'أحمد محمد', email: 'ahmad@example.com', role: 'admin' },
      logout,
    });

    renderHeader();
    
    const logoutButton = screen.getByText('تسجيل الخروج');
    await userEvent.click(logoutButton);
    
    expect(logout).toHaveBeenCalledTimes(1);
  });

  it('يجب أن يتم تبديل السمة عند النقر على زر السمة', async () => {
    const { useTheme } = await import('../hooks/useTheme');
    const toggleTheme = vi.fn();
    vi.mocked(useTheme).mockReturnValue({
      theme: 'light',
      toggleTheme,
    });

    renderHeader();
    
    const themeButton = screen.getByLabelText('تبديل السمة');
    await userEvent.click(themeButton);
    
    expect(toggleTheme).toHaveBeenCalledTimes(1);
  });

  it('يجب أن يتم عرض أيقونة القمر في الوضع الفاتح', () => {
    renderHeader();
    
    const themeButton = screen.getByLabelText('تبديل السمة');
    expect(themeButton.querySelector('svg')).toHaveClass('moon-icon');
  });

  it('يجب أن يتم عرض أيقونة الشمس في الوضع الداكن', async () => {
    const { useTheme } = await import('../hooks/useTheme');
    vi.mocked(useTheme).mockReturnValue({
      theme: 'dark',
      toggleTheme: vi.fn(),
    });

    renderHeader();
    
    const themeButton = screen.getByLabelText('تبديل السمة');
    expect(themeButton.querySelector('svg')).toHaveClass('sun-icon');
  });

  it('يجب أن يتم عرض قائمة المستخدم المنسدلة', async () => {
    renderHeader();
    
    const userMenuButton = screen.getByLabelText('قائمة المستخدم');
    await userEvent.click(userMenuButton);
    
    expect(screen.getByText('الملف الشخصي')).toBeInTheDocument();
    expect(screen.getByText('إعدادات الحساب')).toBeInTheDocument();
    expect(screen.getByText('تسجيل الخروج')).toBeInTheDocument();
  });

  it('يجب أن يتم إغلاق القائمة عند النقر خارجها', async () => {
    renderHeader();
    
    const userMenuButton = screen.getByLabelText('قائمة المستخدم');
    await userEvent.click(userMenuButton);
    
    expect(screen.getByText('الملف الشخصي')).toBeInTheDocument();
    
    // النقر خارج القائمة
    await userEvent.click(document.body);
    
    expect(screen.queryByText('الملف الشخصي')).not.toBeInTheDocument();
  });

  it('يجب أن يتم عرض شارة الإشعارات', () => {
    renderHeader();
    
    const notificationButton = screen.getByLabelText('الإشعارات');
    const badge = notificationButton.querySelector('.notification-badge');
    
    expect(badge).toBeInTheDocument();
    expect(badge).toHaveTextContent('5');
  });

  it('يجب أن يتم عرض قائمة الإشعارات', async () => {
    renderHeader();
    
    const notificationButton = screen.getByLabelText('الإشعارات');
    await userEvent.click(notificationButton);
    
    expect(screen.getByText('الإشعارات')).toBeInTheDocument();
    expect(screen.getByText('مشروع جديد تم إضافته')).toBeInTheDocument();
    expect(screen.getByText('تم تحديث التقرير الشهري')).toBeInTheDocument();
  });

  it('يجب أن يتم تمييز الصفحة النشطة', () => {
    renderHeader();
    
    const activeLink = screen.getByText('الرئيسية').closest('a');
    expect(activeLink).toHaveClass('active');
  });

  it('يجب أن يتم عرض قائمة الهامبرغر في الشاشات الصغيرة', () => {
    // تغيير حجم الشاشة
    global.innerWidth = 500;
    global.dispatchEvent(new Event('resize'));
    
    renderHeader();
    
    const hamburgerButton = screen.getByLabelText('قائمة التنقل');
    expect(hamburgerButton).toBeInTheDocument();
  });

  it('يجب أن يتم فتح القائمة الجانبية عند النقر على الهامبرغر', async () => {
    global.innerWidth = 500;
    global.dispatchEvent(new Event('resize'));
    
    renderHeader();
    
    const hamburgerButton = screen.getByLabelText('قائمة التنقل');
    await userEvent.click(hamburgerButton);
    
    const mobileMenu = screen.getByRole('navigation', { name: 'القائمة الجانبية' });
    expect(mobileMenu).toHaveClass('open');
  });

  it('يجب أن يتم البحث عند إدخال نص في حقل البحث', async () => {
    const onSearch = vi.fn();
    render(
      <BrowserRouter>
        <Header onSearch={onSearch} />
      </BrowserRouter>
    );
    
    const searchInput = screen.getByPlaceholderText('بحث...');
    await userEvent.type(searchInput, 'مشروع جديد');
    await userEvent.keyboard('{Enter}');
    
    expect(onSearch).toHaveBeenCalledWith('مشروع جديد');
  });

  it('يجب أن يتم عرض اقتراحات البحث', async () => {
    renderHeader();
    
    const searchInput = screen.getByPlaceholderText('بحث...');
    await userEvent.type(searchInput, 'مشر');
    
    // انتظار ظهور الاقتراحات
    await screen.findByText('مشروع التطوير');
    expect(screen.getByText('مشروع التحديث')).toBeInTheDocument();
  });

  it('يجب أن يتم تطبيق صنف RTL', () => {
    const { container } = renderHeader();
    
    const header = container.querySelector('header');
    expect(header).toHaveAttribute('dir', 'rtl');
  });

  it('يجب أن يتم عرض دور المستخدم', () => {
    renderHeader();
    
    expect(screen.getByText('مدير')).toBeInTheDocument();
  });

  it('يجب أن يتم إخفاء عناصر معينة للمستخدمين غير المصرح لهم', () => {
    const { useAuth } = vi.mocked(await import('../contexts/AuthContext'));
    vi.mocked(useAuth).mockReturnValue({
      user: { name: 'فاطمة علي', email: 'fatima@example.com', role: 'user' },
      logout: vi.fn(),
    });

    renderHeader();
    
    expect(screen.queryByText('الإعدادات')).not.toBeInTheDocument();
  });

  it('يجب أن يتم تحديث شارة الإشعارات عند قراءة الإشعارات', async () => {
    renderHeader();
    
    const notificationButton = screen.getByLabelText('الإشعارات');
    await userEvent.click(notificationButton);
    
    const markAsReadButton = screen.getByText('تحديد الكل كمقروء');
    await userEvent.click(markAsReadButton);
    
    const badge = notificationButton.querySelector('.notification-badge');
    expect(badge).not.toBeInTheDocument();
  });

  it('يجب أن يتم دعم اختصارات لوحة المفاتيح', async () => {
    renderHeader();
    
    // اختصار البحث (Ctrl + K)
    await userEvent.keyboard('{Control>}k{/Control}');
    
    const searchInput = screen.getByPlaceholderText('بحث...');
    expect(searchInput).toHaveFocus();
  });

  it('يجب أن يتم عرض حالة الاتصال بالشبكة', () => {
    renderHeader();
    
    // محاكاة فقدان الاتصال
    window.dispatchEvent(new Event('offline'));
    
    expect(screen.getByText('غير متصل بالشبكة')).toBeInTheDocument();
    
    // محاكاة استعادة الاتصال
    window.dispatchEvent(new Event('online'));
    
    expect(screen.queryByText('غير متصل بالشبكة')).not.toBeInTheDocument();
  });

  it('يجب أن يتم حفظ تفضيلات المستخدم', async () => {
    const localStorage = {
      getItem: vi.fn(),
      setItem: vi.fn(),
    };
    Object.defineProperty(window, 'localStorage', { value: localStorage });

    renderHeader();
    
    const themeButton = screen.getByLabelText('تبديل السمة');
    await userEvent.click(themeButton);
    
    expect(localStorage.setItem).toHaveBeenCalledWith('theme', 'dark');
  });

  it('يجب أن يتم عرض مؤشر التحميل أثناء العمليات', async () => {
    renderHeader();
    
    const refreshButton = screen.getByLabelText('تحديث');
    await userEvent.click(refreshButton);
    
    expect(screen.getByLabelText('جاري التحميل...')).toBeInTheDocument();
  });
});