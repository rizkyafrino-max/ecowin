import { forwardRef, useId } from 'react';

const control = 'w-full rounded-2xl border border-line bg-surface px-4 text-[15px] text-ink placeholder:text-muted/70 focus:border-primary focus:outline-none focus:ring-4 focus:ring-primary/10 disabled:bg-canvas';

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
