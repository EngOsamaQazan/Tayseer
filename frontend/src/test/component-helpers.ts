// Component testing helpers
import { screen, fireEvent, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

// Button interaction helpers
export const clickButton = async (buttonText: string | RegExp) => {
  const button = screen.getByRole('button', { name: buttonText });
  await userEvent.click(button);
};

export const clickButtonByTestId = async (testId: string) => {
  const button = screen.getByTestId(testId);
  await userEvent.click(button);
};

// Form interaction helpers
export const typeInInput = async (labelText: string | RegExp, value: string) => {
  const input = screen.getByLabelText(labelText);
  await userEvent.clear(input);
  await userEvent.type(input, value);
};

export const typeInInputByPlaceholder = async (placeholder: string | RegExp, value: string) => {
  const input = screen.getByPlaceholderText(placeholder);
  await userEvent.clear(input);
  await userEvent.type(input, value);
};

export const selectOption = async (labelText: string | RegExp, optionText: string) => {
  const select = screen.getByLabelText(labelText) as HTMLSelectElement;
  await userEvent.selectOptions(select, optionText);
};

export const checkCheckbox = async (labelText: string | RegExp) => {
  const checkbox = screen.getByLabelText(labelText) as HTMLInputElement;
  if (!checkbox.checked) {
    await userEvent.click(checkbox);
  }
};

export const uncheckCheckbox = async (labelText: string | RegExp) => {
  const checkbox = screen.getByLabelText(labelText) as HTMLInputElement;
  if (checkbox.checked) {
    await userEvent.click(checkbox);
  }
};

// Modal helpers
export const openModal = async (triggerText: string | RegExp) => {
  await clickButton(triggerText);
  await waitFor(() => {
    expect(screen.getByRole('dialog')).toBeInTheDocument();
  });
};

export const closeModal = async () => {
  const closeButton = screen.getByLabelText(/إغلاق|close/i);
  await userEvent.click(closeButton);
  await waitFor(() => {
    expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
  });
};

// Table helpers
export const getTableRows = () => {
  return screen.getAllByRole('row').slice(1); // Skip header row
};

export const getTableCellContent = (row: HTMLElement, columnIndex: number): string => {
  const cells = row.querySelectorAll('td');
  return cells[columnIndex]?.textContent || '';
};

export const sortTableByColumn = async (columnHeader: string | RegExp) => {
  const header = screen.getByText(columnHeader);
  await userEvent.click(header);
};

// Navigation helpers
export const navigateToLink = async (linkText: string | RegExp) => {
  const link = screen.getByRole('link', { name: linkText });
  await userEvent.click(link);
};

export const expectToBeOnPage = (pathname: string) => {
  expect(window.location.pathname).toBe(pathname);
};

// Loading state helpers
export const waitForLoadingToFinish = async () => {
  await waitFor(() => {
    expect(screen.queryByText(/جاري التحميل|loading/i)).not.toBeInTheDocument();
  });
};

export const expectLoadingState = () => {
  expect(screen.getByText(/جاري التحميل|loading/i)).toBeInTheDocument();
};

// Error state helpers
export const expectErrorMessage = (message: string | RegExp) => {
  expect(screen.getByRole('alert')).toHaveTextContent(message);
};

export const expectNoErrorMessage = () => {
  expect(screen.queryByRole('alert')).not.toBeInTheDocument();
};

// Success state helpers
export const expectSuccessMessage = (message: string | RegExp) => {
  expect(screen.getByText(message)).toHaveClass('text-green-600');
};

// Accessibility helpers
export const expectToBeAccessible = async (container: HTMLElement) => {
  const results = await screen.findAllByRole('*');
  results.forEach(element => {
    if (element.tagName === 'IMG') {
      expect(element).toHaveAttribute('alt');
    }
    if (element.tagName === 'BUTTON' || element.tagName === 'A') {
      expect(element).toHaveAccessibleName();
    }
  });
};

export const expectFocusToBeOn = (element: HTMLElement) => {
  expect(document.activeElement).toBe(element);
};

// Keyboard navigation helpers
export const pressTab = async () => {
  await userEvent.tab();
};

export const pressEnter = async () => {
  await userEvent.keyboard('{Enter}');
};

export const pressEscape = async () => {
  await userEvent.keyboard('{Escape}');
};

export const pressArrowDown = async () => {
  await userEvent.keyboard('{ArrowDown}');
};

export const pressArrowUp = async () => {
  await userEvent.keyboard('{ArrowUp}');
};

// File upload helpers
export const uploadFile = async (inputLabel: string | RegExp, file: File) => {
  const input = screen.getByLabelText(inputLabel) as HTMLInputElement;
  await userEvent.upload(input, file);
};

export const createMockFile = (name: string, content: string, type: string): File => {
  return new File([content], name, { type });
};

// Drag and drop helpers
export const dragAndDrop = async (dragElement: HTMLElement, dropTarget: HTMLElement) => {
  fireEvent.dragStart(dragElement);
  fireEvent.dragEnter(dropTarget);
  fireEvent.dragOver(dropTarget);
  fireEvent.drop(dropTarget);
  fireEvent.dragEnd(dragElement);
};

// Hover helpers
export const hoverElement = async (element: HTMLElement) => {
  await userEvent.hover(element);
};

export const unhoverElement = async (element: HTMLElement) => {
  await userEvent.unhover(element);
};

// Clipboard helpers
export const copyToClipboard = async (text: string) => {
  await navigator.clipboard.writeText(text);
};

export const pasteFromClipboard = async (element: HTMLElement) => {
  const clipboardText = await navigator.clipboard.readText();
  await userEvent.paste(clipboardText);
};

// Viewport helpers
export const setViewportSize = (width: number, height: number) => {
  Object.defineProperty(window, 'innerWidth', {
    writable: true,
    configurable: true,
    value: width,
  });
  Object.defineProperty(window, 'innerHeight', {
    writable: true,
    configurable: true,
    value: height,
  });
  window.dispatchEvent(new Event('resize'));
};

export const setMobileViewport = () => setViewportSize(375, 667);
export const setTabletViewport = () => setViewportSize(768, 1024);
export const setDesktopViewport = () => setViewportSize(1920, 1080);

// RTL specific helpers
export const expectRTLLayout = (element: HTMLElement) => {
  const computedStyle = window.getComputedStyle(element);
  expect(computedStyle.direction).toBe('rtl');
};

export const expectArabicText = (element: HTMLElement) => {
  const text = element.textContent || '';
  const arabicPattern = /[\u0600-\u06FF]/;
  expect(arabicPattern.test(text)).toBe(true);
};

// Date helpers for testing
export const mockCurrentDate = (date: Date) => {
  const originalDate = Date;
  global.Date = class extends originalDate {
    constructor(...args: any[]) {
      if (args.length === 0) {
        super(date.getTime());
      } else {
        super(...args);
      }
    }
    static now() {
      return date.getTime();
    }
  } as any;
};

export const restoreDate = () => {
  global.Date = Date;
};

// Network helpers
export const mockSuccessfulAPICall = () => {
  global.fetch = jest.fn(() =>
    Promise.resolve({
      ok: true,
      json: () => Promise.resolve({ success: true }),
    })
  ) as any;
};

export const mockFailedAPICall = (errorMessage: string) => {
  global.fetch = jest.fn(() =>
    Promise.resolve({
      ok: false,
      json: () => Promise.resolve({ error: errorMessage }),
    })
  ) as any;
};

export const mockNetworkError = () => {
  global.fetch = jest.fn(() =>
    Promise.reject(new Error('Network error'))
  ) as any;
};

// Animation helpers
export const waitForAnimation = async (duration: number = 300) => {
  await waitFor(() => new Promise(resolve => setTimeout(resolve, duration)));
};

export const mockRequestAnimationFrame = () => {
  let lastTime = 0;
  global.requestAnimationFrame = ((callback: FrameRequestCallback) => {
    const currentTime = Date.now();
    const timeToCall = Math.max(0, 16 - (currentTime - lastTime));
    const id = setTimeout(() => {
      callback(currentTime + timeToCall);
    }, timeToCall);
    lastTime = currentTime + timeToCall;
    return id;
  }) as any;
};

// Export all helpers
export default {
  // Button interactions
  clickButton,
  clickButtonByTestId,
  
  // Form interactions
  typeInInput,
  typeInInputByPlaceholder,
  selectOption,
  checkCheckbox,
  uncheckCheckbox,
  
  // Modal helpers
  openModal,
  closeModal,
  
  // Table helpers
  getTableRows,
  getTableCellContent,
  sortTableByColumn,
  
  // Navigation
  navigateToLink,
  expectToBeOnPage,
  
  // Loading states
  waitForLoadingToFinish,
  expectLoadingState,
  
  // Error states
  expectErrorMessage,
  expectNoErrorMessage,
  
  // Success states
  expectSuccessMessage,
  
  // Accessibility
  expectToBeAccessible,
  expectFocusToBeOn,
  
  // Keyboard navigation
  pressTab,
  pressEnter,
  pressEscape,
  pressArrowDown,
  pressArrowUp,
  
  // File uploads
  uploadFile,
  createMockFile,
  
  // Drag and drop
  dragAndDrop,
  
  // Hover
  hoverElement,
  unhoverElement,
  
  // Clipboard
  copyToClipboard,
  pasteFromClipboard,
  
  // Viewport
  setViewportSize,
  setMobileViewport,
  setTabletViewport,
  setDesktopViewport,
  
  // RTL
  expectRTLLayout,
  expectArabicText,
  
  // Date
  mockCurrentDate,
  restoreDate,
  
  // Network
  mockSuccessfulAPICall,
  mockFailedAPICall,
  mockNetworkError,
  
  // Animation
  waitForAnimation,
  mockRequestAnimationFrame,
};