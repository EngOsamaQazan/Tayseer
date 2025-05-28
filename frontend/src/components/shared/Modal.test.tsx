import { describe, it, expect, vi } from 'vitest';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { Modal } from './Modal';

describe('Modal Component', () => {
  const defaultProps = {
    isOpen: true,
    onClose: vi.fn(),
    title: 'عنوان النافذة',
    children: <p>محتوى النافذة</p>,
  };

  it('يجب أن يتم عرض النافذة عندما تكون مفتوحة', () => {
    render(<Modal {...defaultProps} />);
    
    expect(screen.getByText('عنوان النافذة')).toBeInTheDocument();
    expect(screen.getByText('محتوى النافذة')).toBeInTheDocument();
  });

  it('يجب ألا يتم عرض النافذة عندما تكون مغلقة', () => {
    render(<Modal {...defaultProps} isOpen={false} />);
    
    expect(screen.queryByText('عنوان النافذة')).not.toBeInTheDocument();
    expect(screen.queryByText('محتوى النافذة')).not.toBeInTheDocument();
  });

  it('يجب أن يتم استدعاء onClose عند النقر على زر الإغلاق', async () => {
    const onClose = vi.fn();
    render(<Modal {...defaultProps} onClose={onClose} />);
    
    const closeButton = screen.getByLabelText('إغلاق');
    await userEvent.click(closeButton);
    
    expect(onClose).toHaveBeenCalledTimes(1);
  });

  it('يجب أن يتم استدعاء onClose عند النقر على الخلفية', async () => {
    const onClose = vi.fn();
    render(<Modal {...defaultProps} onClose={onClose} />);
    
    const backdrop = screen.getByTestId('modal-backdrop');
    await userEvent.click(backdrop);
    
    expect(onClose).toHaveBeenCalledTimes(1);
  });

  it('يجب ألا يتم إغلاق النافذة عند النقر على المحتوى', async () => {
    const onClose = vi.fn();
    render(<Modal {...defaultProps} onClose={onClose} />);
    
    const content = screen.getByText('محتوى النافذة');
    await userEvent.click(content);
    
    expect(onClose).not.toHaveBeenCalled();
  });

  it('يجب أن يتم استدعاء onClose عند الضغط على Escape', async () => {
    const onClose = vi.fn();
    render(<Modal {...defaultProps} onClose={onClose} />);
    
    await userEvent.keyboard('{Escape}');
    
    expect(onClose).toHaveBeenCalledTimes(1);
  });

  it('يجب أن يتم عرض أحجام مختلفة بشكل صحيح', () => {
    const { container, rerender } = render(
      <Modal {...defaultProps} size="small" />
    );
    
    let modalContent = container.querySelector('.max-w-md');
    expect(modalContent).toBeInTheDocument();
    
    rerender(<Modal {...defaultProps} size="medium" />);
    modalContent = container.querySelector('.max-w-lg');
    expect(modalContent).toBeInTheDocument();
    
    rerender(<Modal {...defaultProps} size="large" />);
    modalContent = container.querySelector('.max-w-2xl');
    expect(modalContent).toBeInTheDocument();
    
    rerender(<Modal {...defaultProps} size="full" />);
    modalContent = container.querySelector('.max-w-full');
    expect(modalContent).toBeInTheDocument();
  });

  it('يجب أن يتم عرض Footer عند تمريره', () => {
    const footer = (
      <div>
        <button>حفظ</button>
        <button>إلغاء</button>
      </div>
    );
    
    render(<Modal {...defaultProps} footer={footer} />);
    
    expect(screen.getByText('حفظ')).toBeInTheDocument();
    expect(screen.getByText('إلغاء')).toBeInTheDocument();
  });

  it('يجب أن يتم منع الإغلاق عند closeOnBackdropClick = false', async () => {
    const onClose = vi.fn();
    render(
      <Modal {...defaultProps} onClose={onClose} closeOnBackdropClick={false} />
    );
    
    const backdrop = screen.getByTestId('modal-backdrop');
    await userEvent.click(backdrop);
    
    expect(onClose).not.toHaveBeenCalled();
  });

  it('يجب أن يتم منع الإغلاق عند closeOnEscape = false', async () => {
    const onClose = vi.fn();
    render(
      <Modal {...defaultProps} onClose={onClose} closeOnEscape={false} />
    );
    
    await userEvent.keyboard('{Escape}');
    
    expect(onClose).not.toHaveBeenCalled();
  });

  it('يجب أن يتم إخفاء زر الإغلاق عند showCloseButton = false', () => {
    render(<Modal {...defaultProps} showCloseButton={false} />);
    
    expect(screen.queryByLabelText('إغلاق')).not.toBeInTheDocument();
  });

  it('يجب أن يتم تطبيق className مخصص', () => {
    const { container } = render(
      <Modal {...defaultProps} className="custom-modal" />
    );
    
    const modalContent = container.querySelector('.custom-modal');
    expect(modalContent).toBeInTheDocument();
  });

  it('يجب أن يتم عرض حالة التحميل', () => {
    render(<Modal {...defaultProps} loading />);
    
    expect(screen.getByText('جاري التحميل...')).toBeInTheDocument();
    expect(screen.queryByText('محتوى النافذة')).not.toBeInTheDocument();
  });

  it('يجب أن يتم دعم الرسوم المتحركة', async () => {
    const { rerender } = render(<Modal {...defaultProps} isOpen={false} />);
    
    expect(screen.queryByText('عنوان النافذة')).not.toBeInTheDocument();
    
    rerender(<Modal {...defaultProps} isOpen={true} />);
    
    await waitFor(() => {
      expect(screen.getByText('عنوان النافذة')).toBeInTheDocument();
    });
  });

  it('يجب أن يتم التعامل مع body scroll lock', () => {
    const { unmount } = render(<Modal {...defaultProps} />);
    
    // التحقق من أن body overflow مخفي
    expect(document.body.style.overflow).toBe('hidden');
    
    unmount();
    
    // التحقق من استعادة body overflow
    expect(document.body.style.overflow).toBe('');
  });

  it('يجب أن يتم دعم z-index مخصص', () => {
    const { container } = render(
      <Modal {...defaultProps} zIndex={9999} />
    );
    
    const backdrop = container.querySelector('.fixed');
    expect(backdrop).toHaveStyle({ zIndex: '9999' });
  });

  it('يجب أن يتم دعم التمركز المختلف', () => {
    const { container, rerender } = render(
      <Modal {...defaultProps} centered />
    );
    
    let modalWrapper = container.querySelector('.items-center');
    expect(modalWrapper).toBeInTheDocument();
    
    rerender(<Modal {...defaultProps} centered={false} />);
    modalWrapper = container.querySelector('.items-start');
    expect(modalWrapper).toBeInTheDocument();
  });

  it('يجب أن يتم التعامل مع المحتوى الطويل بشكل صحيح', () => {
    const longContent = (
      <div>
        {Array.from({ length: 50 }, (_, i) => (
          <p key={i}>فقرة {i + 1}</p>
        ))}
      </div>
    );
    
    render(<Modal {...defaultProps}>{longContent}</Modal>);
    
    const modalContent = screen.getByRole('dialog');
    expect(modalContent).toHaveClass('overflow-y-auto');
  });

  it('يجب أن يتم دعم الوصولية', () => {
    render(<Modal {...defaultProps} />);
    
    const modal = screen.getByRole('dialog');
    expect(modal).toHaveAttribute('aria-modal', 'true');
    expect(modal).toHaveAttribute('aria-labelledby');
    
    const title = screen.getByText('عنوان النافذة');
    expect(title).toHaveAttribute('id');
  });

  it('يجب أن يتم دعم التركيز التلقائي', async () => {
    render(
      <Modal {...defaultProps}>
        <input type="text" data-testid="input" autoFocus />
      </Modal>
    );
    
    await waitFor(() => {
      const input = screen.getByTestId('input');
      expect(document.activeElement).toBe(input);
    });
  });

  it('يجب أن يتم دعم مستويات متعددة من النوافذ', () => {
    render(
      <>
        <Modal {...defaultProps} zIndex={50}>
          <p>نافذة أولى</p>
        </Modal>
        <Modal {...defaultProps} zIndex={60} title="نافذة ثانية">
          <p>نافذة ثانية</p>
        </Modal>
      </>
    );
    
    expect(screen.getByText('نافذة أولى')).toBeInTheDocument();
    expect(screen.getByText('نافذة ثانية')).toBeInTheDocument();
  });

  it('يجب أن يتم تنفيذ onAfterOpen callback', async () => {
    const onAfterOpen = vi.fn();
    const { rerender } = render(
      <Modal {...defaultProps} isOpen={false} onAfterOpen={onAfterOpen} />
    );
    
    expect(onAfterOpen).not.toHaveBeenCalled();
    
    rerender(<Modal {...defaultProps} isOpen={true} onAfterOpen={onAfterOpen} />);
    
    await waitFor(() => {
      expect(onAfterOpen).toHaveBeenCalledTimes(1);
    });
  });

  it('يجب أن يتم تنفيذ onAfterClose callback', async () => {
    const onAfterClose = vi.fn();
    const { rerender } = render(
      <Modal {...defaultProps} isOpen={true} onAfterClose={onAfterClose} />
    );
    
    expect(onAfterClose).not.toHaveBeenCalled();
    
    rerender(<Modal {...defaultProps} isOpen={false} onAfterClose={onAfterClose} />);
    
    await waitFor(() => {
      expect(onAfterClose).toHaveBeenCalledTimes(1);
    });
  });

  it('يجب أن يتم دعم RTL', () => {
    render(<Modal {...defaultProps} dir="rtl" />);
    
    const modal = screen.getByRole('dialog');
    expect(modal).toHaveAttribute('dir', 'rtl');
  });
});