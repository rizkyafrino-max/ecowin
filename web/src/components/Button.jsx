import { Loader2 } from 'lucide-react';

const base = 'inline-flex items-center justify-center gap-2 rounded-full font-semibold transition active:scale-[0.98] disabled:opacity-50 disabled:pointer-events-none';
const variants = {
  primary: 'bg-primary text-white hover:bg-primary-deep shadow-sm',
  soft: 'bg-tint text-primary hover:bg-emerald-100',
  outline: 'border border-line bg-surface text-ink hover:bg-canvas',
  danger: 'bg-danger text-white hover:bg-red-700',
  ghost: 'text-muted hover:bg-canvas hover:text-ink',
};
const sizes = { md: 'h-11 px-5 text-sm', lg: 'h-12 px-6 text-[15px]', sm: 'h-9 px-4 text-sm' };

export default function Button({ variant = 'primary', size = 'md', loading = false, icon: Icon, className = '', children, ...props }) {
  return (
    <button className={`${base} ${variants[variant]} ${sizes[size]} ${className}`} disabled={loading || props.disabled} {...props}>
      {loading ? <Loader2 className="h-4 w-4 animate-spin" aria-hidden /> : Icon ? <Icon className="h-4 w-4" aria-hidden /> : null}
      {children}
    </button>
  );
}
