import { describe, it, expect, vi } from 'vitest';
import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { Sidebar } from './Sidebar';
import { BrowserRouter } from 'react-router-dom';

// Mock للسياق والخطافات
vi.mock('../contexts/AuthContext', () => ({
  useAuth: () => ({
    user: { name: 'أحمد محمد', role: 'admin' },
    permissions: ['read', 'write', 'delete'],
  }),
}));

vi.mock('react-router-dom', async () => {
  const actual = await vi.importActual('react-router-dom');
  return {
    ...actual,
    useLocation: () => ({ pathname: '/dashboard' }),
  };
});

describe('Sidebar Component', () => {
  const defaultProps = {
    isOpen: true,
    onToggle: vi.fn(),
  };

  const renderSidebar = (props = {}) => {
    return render(
      <BrowserRouter>
        <Sidebar {...defaultProps} {...props} />
      </BrowserRouter>
    );
  };

  it('يجب أن يتم عرض Sidebar بشكل صحيح', () => {
    renderSidebar();
    
    const sidebar = screen.getByRole('navigation', { name: 'القائمة الرئيسية' });
    expect(sidebar).toBeInTheDocument();
    expect(sidebar).toHaveClass('open');
  });

  it('يجب أن يتم عرض جميع عناصر القائمة الرئيسية', () => {
    renderSidebar();
    
    expect(screen.getByText('لوحة التحكم')).toBeInTheDocument();
    expect(screen.getByText('المشاريع')).toBeInTheDocument();
    expect(screen.getByText('المهام')).toBeInTheDocument();
    expect(screen.getByText('التقارير')).toBeInTheDocument();
    expect(screen.getByText('المستخدمون')).toBeInTheDocument();
    expect(screen.getByText('الإعدادات')).toBeInTheDocument();
  });

  it('يجب أن يتم إغلاق الشريط الجانبي', () => {
    renderSidebar({ isOpen: false });
    
    const sidebar = screen.getByRole('navigation', { name: 'القائمة الرئيسية' });
    expect(sidebar).toHaveClass('closed');
  });

  it('يجب أن يتم تمييز العنصر النشط', () => {
    renderSidebar();
    
    const activeItem = screen.getByText('لوحة التحكم').closest('li');
    expect(activeItem).toHaveClass('active');
  });

  it('يجب أن يتم توسيع القوائم الفرعية', async () => {
    renderSidebar();
    
    const projectsItem = screen.getByText('المشاريع');
    await userEvent.click(projectsItem);
    
    expect(screen.getByText('جميع المشاريع')).toBeInTheDocument();
    expect(screen.getByText('مشروع جديد')).toBeInTheDocument();
    expect(screen.getByText('المشاريع المؤرشفة')).toBeInTheDocument();
  });

  it('يجب أن يتم طي القوائم الفرعية', async () => {
    renderSidebar();
    
    const projectsItem = screen.getByText('المشاريع');
    await userEvent.click(projectsItem);
    
    expect(screen.getByText('جميع المشاريع')).toBeInTheDocument();
    
    await userEvent.click(projectsItem);
    
    expect(screen.queryByText('جميع المشاريع')).not.toBeInTheDocument();
  });

  it('يجب أن يتم عرض الأيقونات', () => {
    renderSidebar();
    
    const dashboardItem = screen.getByText('لوحة التحكم').closest('li');
    const icon = within(dashboardItem).getByRole('img', { hidden: true });
    
    expect(icon).toHaveClass('icon-dashboard');
  });

  it('يجب أن يتم التنقل عند النقر على الروابط', async () => {
    const navigate = vi.fn();
    vi.mock('react-router-dom', async () => {
      const actual = await vi.importActual('react-router-dom');
      return {
        ...actual,
        useNavigate: () => navigate,
      };
    });

    renderSidebar();
    
    const reportsLink = screen.getByText('التقارير');
    await userEvent.click(reportsLink);
    
    expect(navigate).toHaveBeenCalledWith('/reports');
  });

  it('يجب أن يتم عرض شارات الإشعارات', () => {
    renderSidebar();
    
    const tasksItem = screen.getByText('المهام').closest('li');
    const badge = within(tasksItem).getByText('12');
    
    expect(badge).toHaveClass('notification-badge');
  });

  it('يجب أن يتم البحث في القائمة', async () => {
    renderSidebar();
    
    const searchInput = screen.getByPlaceholderText('البحث في القائمة...');
    await userEvent.type(searchInput, 'تقارير');
    
    expect(screen.getByText('التقارير')).toBeInTheDocument();
    expect(screen.queryByText('المشاريع')).not.toBeInTheDocument();
  });

  it('يجب أن يتم إخفاء العناصر بناءً على الأذونات', () => {
    const { useAuth } = vi.mocked(await import('../contexts/AuthContext'));
    vi.mocked(useAuth).mockReturnValue({
      user: { name: 'فاطمة علي', role: 'user' },
      permissions: ['read'],
    });

    renderSidebar();
    
    expect(screen.queryByText('المستخدمون')).not.toBeInTheDocument();
    expect(screen.queryByText('الإعدادات')).not.toBeInTheDocument();
  });

  it('يجب أن يتم عرض معلومات المستخدم', () => {
    renderSidebar();
    
    const userInfo = screen.getByTestId('user-info');
    expect(within(userInfo).getByText('أحمد محمد')).toBeInTheDocument();
    expect(within(userInfo).getByText('مدير')).toBeInTheDocument();
  });

  it('يجب أن يتم دعم وضع مصغر', () => {
    renderSidebar({ collapsed: true });
    
    const sidebar = screen.getByRole('navigation', { name: 'القائمة الرئيسية' });
    expect(sidebar).toHaveClass('collapsed');
    
    // يجب أن تظهر الأيقونات فقط
    expect(screen.queryByText('لوحة التحكم')).not.toBeVisible();
  });

  it('يجب أن يتم عرض تلميحات الأدوات في الوضع المصغر', async () => {
    renderSidebar({ collapsed: true });
    
    const dashboardIcon = screen.getByRole('img', { name: 'لوحة التحكم' });
    await userEvent.hover(dashboardIcon);
    
    const tooltip = await screen.findByRole('tooltip');
    expect(tooltip).toHaveTextContent('لوحة التحكم');
  });

  it('يجب أن يتم دعم التنقل بلوحة المفاتيح', async () => {
    renderSidebar();
    
    const firstItem = screen.getByText('لوحة التحكم');
    firstItem.focus();
    
    await userEvent.keyboard('{ArrowDown}');
    expect(screen.getByText('المشاريع')).toHaveFocus();
    
    await userEvent.keyboard('{ArrowUp}');
    expect(screen.getByText('لوحة التحكم')).toHaveFocus();
    
    await userEvent.keyboard('{Enter}');
    // التحقق من التنقل
  });

  it('يجب أن يتم حفظ حالة القوائم الفرعية', async () => {
    const localStorage = {
      getItem: vi.fn().mockReturnValue(JSON.stringify({ projects: true })),
      setItem: vi.fn(),
    };
    Object.defineProperty(window, 'localStorage', { value: localStorage });

    renderSidebar();
    
    // يجب أن تكون قائمة المشاريع مفتوحة
    expect(screen.getByText('جميع المشاريع')).toBeInTheDocument();
    
    // إغلاق القائمة
    const projectsItem = screen.getByText('المشاريع');
    await userEvent.click(projectsItem);
    
    expect(localStorage.setItem).toHaveBeenCalledWith(
      'sidebar-submenu-state',
      JSON.stringify({ projects: false })
    );
  });

  it('يجب أن يتم دعم السحب والإفلات لإعادة الترتيب', async () => {
    renderSidebar({ allowReorder: true });
    
    const dashboardItem = screen.getByText('لوحة التحكم').closest('li');
    const projectsItem = screen.getByText('المشاريع').closest('li');
    
    // محاكاة السحب والإفلات
    await userEvent.pointer([
      { keys: '[MouseLeft>]', target: dashboardItem },
      { coords: { x: 0, y: 100 } },
      { keys: '[/MouseLeft]', target: projectsItem },
    ]);
    
    // التحقق من تغيير الترتيب
    const items = screen.getAllByRole('listitem');
    expect(items[0]).toHaveTextContent('المشاريع');
    expect(items[1]).toHaveTextContent('لوحة التحكم');
  });

  it('يجب أن يتم عرض قسم المفضلة', () => {
    renderSidebar();
    
    const favoritesSection = screen.getByText('المفضلة').closest('section');
    expect(favoritesSection).toBeInTheDocument();
    
    within(favoritesSection).getByText('تقرير المبيعات');
    within(favoritesSection).getByText('مشروع التطوير');
  });

  it('يجب أن يتم إضافة/إزالة عناصر من المفضلة', async () => {
    renderSidebar();
    
    const reportsItem = screen.getByText('التقارير').closest('li');
    const starButton = within(reportsItem).getByLabelText('إضافة إلى المفضلة');
    
    await userEvent.click(starButton);
    
    expect(starButton).toHaveAttribute('aria-pressed', 'true');
    expect(screen.getByText('التقارير', { selector: '.favorites-section li' })).toBeInTheDocument();
  });

  it('يجب أن يتم دعم الوضع الداكن', () => {
    renderSidebar({ theme: 'dark' });
    
    const sidebar = screen.getByRole('navigation', { name: 'القائمة الرئيسية' });
    expect(sidebar).toHaveClass('theme-dark');
  });

  it('يجب أن يتم عرض مؤشر النشاط', () => {
    renderSidebar();
    
    const projectsItem = screen.getByText('المشاريع').closest('li');
    const activityIndicator = within(projectsItem).getByTestId('activity-indicator');
    
    expect(activityIndicator).toHaveClass('active');
    expect(activityIndicator).toHaveAttribute('aria-label', 'نشاط جديد');
  });

  it('يجب أن يتم دعم القوائم المخصصة', () => {
    const customItems = [
      { id: 'custom1', label: 'عنصر مخصص 1', icon: 'custom-icon-1', path: '/custom1' },
      { id: 'custom2', label: 'عنصر مخصص 2', icon: 'custom-icon-2', path: '/custom2' },
    ];

    renderSidebar({ customItems });
    
    expect(screen.getByText('عنصر مخصص 1')).toBeInTheDocument();
    expect(screen.getByText('عنصر مخصص 2')).toBeInTheDocument();
  });

  it('يجب أن يتم عرض زر طي/توسيع الشريط الجانبي', async () => {
    const onToggle = vi.fn();
    renderSidebar({ onToggle });
    
    const toggleButton = screen.getByLabelText('طي الشريط الجانبي');
    await userEvent.click(toggleButton);
    
    expect(onToggle).toHaveBeenCalledTimes(1);
  });

  it('يجب أن يتم دعم البحث الصوتي', async () => {
    renderSidebar({ voiceSearchEnabled: true });
    
    const voiceButton = screen.getByLabelText('البحث الصوتي');
    expect(voiceButton).toBeIn