import { forwardRef, useId } from 'react';

const control = 'w-full rounded-2xl border-[1.5px] border-field bg-surface px-4 text-[15px] font-medium text-ink shadow-sm placeholder:font-normal placeholder:text-muted hover:border-muted focus:border-primary focus:outline-none focus:ring-4 focus:ring-primary/20 aria-[invalid=true]:border-danger disabled:border-dashed disabled:bg-canvas disabled:text-muted';

export function Field({ label, error, hint, children, htmlFor }) {
  return (
    <div>
      {label && <label htmlFor={htmlFor} className="mb-1.5 block text-sm font-semibold text-ink">{label}</label>}
      {children}
      {error ? <p className="mt-1.5 text-xs font-medium text-danger" role="alert">{error}</p> : hint ? <p className="mt-1.5 text-xs text-muted">{hint}</p> : null}
    </div>
  );
}

export const Input = forwardRef(function Input({ label, error, hint, className = '', ...props }, ref) {
  const id = useId();
  return (
    <Field label={label} error={error} hint={hint} htmlFor={id}>
      <input id={id} ref={ref} className={`${control} h-12 ${className}`} aria-invalid={!!error} {...props} />
    </Field>
  );
});

export const Textarea = forwardRef(function Textarea({ label, error, hint, className = '', ...props }, ref) {
  const id = useId();
  return (
    <Field label={label} error={error} hint={hint} htmlFor={id}>
      <textarea id={id} ref={ref} rows={3} className={`${control} py-3 ${className}`} aria-invalid={!!error} {...props} />
    </Field>
  );
});

export const Select = forwardRef(function Select({ label, error, hint, children, className = '', ...props }, ref) {
  const id = useId();
  return (
    <Field label={label} error={error} hint={hint} htmlFor={id}>
      <select id={id} ref={ref} className={`${control} h-12 ${className}`} aria-invalid={!!error} {...props}>{children}</select>
    </Field>
  );
});
