import { useState, useCallback, ChangeEvent } from 'react';

interface UseFormOptions<T> {
  initialValues: T;
  onSubmit: (values: T) => void | Promise<void>;
  validate?: (values: T) => Partial<Record<keyof T, string>>;
}

export function useForm<T extends Record<string, any>>({
  initialValues,
  onSubmit,
  validate,
}: UseFormOptions<T>) {
  const [values, setValues] = useState<T>(initialValues);
  const [errors, setErrors] = useState<Partial<Record<keyof T, string>>>({});
  const [touched, setTouched] = useState<Partial<Record<keyof T, boolean>>>({});
  const [isSubmitting, setIsSubmitting] = useState(false);

  const handleChange = useCallback(
    (e: ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>) => {
      const { name, value, type } = e.target;
      const checked = (e.target as HTMLInputElement).checked;
      
      setValues(prev => ({
        ...prev,
        [name]: type === 'checkbox' ? checked : value,
      }));

      // Clear error when user starts typing
      if (errors[name as keyof T]) {
        setErrors(prev => ({ ...prev, [name]: undefined }));
      }
    },
    [errors]
  );

  const handleBlur = useCallback(
    (e: ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>) => {
      const { name } = e.target;
      setTouched(prev => ({ ...prev, [name]: true }));

      // Validate on blur
      if (validate) {
        const validationErrors = validate(values);
        setErrors(validationErrors);
      }
    },
    [validate, values]
  );

  const handleSubmit = useCallback(
    async (e: React.FormEvent) => {
      e.preventDefault();
      
      // Touch all fields
      const allTouched = Object.keys(initialValues).reduce(
        (acc, key) => ({ ...acc, [key]: true }),
        {}
      ) as Partial<Record<keyof T, boolean>>;
      setTouched(allTouched);

      // Validate
      if (validate) {
        const validationErrors = validate(values);
        setErrors(validationErrors);
        
        if (Object.keys(validationErrors).length > 0) {
          return;
        }
      }

      setIsSubmitting(true);
      try {
        await onSubmit(values);
      } finally {
        setIsSubmitting(false);
      }
    },
    [initialValues, validate, values, onSubmit]
  );

  const reset = useCallback(() => {
    setValues(initialValues);
    setErrors({});
    setTouched({});
    setIsSubmitting(false);
  }, [initialValues]);

  const setFieldValue = useCallback((name: keyof T, value: any) => {
    setValues(prev => ({ ...prev, [name]: value }));
  }, []);

  const setFieldError = useCallback((name: keyof T, error: string) => {
    setErrors(prev => ({ ...prev, [name]: error }));
  }, []);

  return {
    values,
    errors,
    touched,
    isSubmitting,
    handleChange,
    handleBlur,
    handleSubmit,
    reset,
    setFieldValue,
    setFieldError,
  };
}