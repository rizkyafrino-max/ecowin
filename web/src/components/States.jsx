import { AlertTriangle, Inbox } from 'lucide-react';
import Button from './Button';

export function EmptyState({ icon: Icon = Inbox, title, message, action }) {
  return (
    <div className="flex flex-col items-center px-6 py-12 text-center">
      <span className="flex h-14 w-14 items-center justify-center rounded-3xl bg-tint text-primary"><Icon className="h-7 w-7" aria-hidden /></span>
      <p className="mt-4 font-semibold">{title}</p>
      {message && <p className="mt-1 max-w-xs text-sm text-muted">{message}</p>}
      {action && <div className="mt-5">{action}</div>}
    </div>
  );
}

export function ErrorState({ error, onRetry }) {
  return (
    <div className="flex flex-col items-center px-6 py-12 text-center" role="alert">
      <span className="flex h-14 w-14 items-center justify-center rounded-3xl bg-danger-tint text-danger"><AlertTriangle className="h-7 w-7" aria-hidden /></span>
      <p className="mt-4 font-semibold">Gagal memuat data</p>
      <p className="mt-1 max-w-xs text-sm text-muted">{error?.message || 'Terjadi kesalahan. Coba lagi.'}</p>
      {onRetry && <Button className="mt-5" variant="outline" onClick={onRetry}>Coba lagi</Button>}
    </div>
  );
}

export const Skeleton = ({ className = 'h-16 w-full' }) => <div className={`ew-skeleton ${className}`} aria-hidden />;

export function LoadingState({ rows = 3 }) {
  return (
    <div className="space-y-3" role="status" aria-label="Memuat">
      {Array.from({ length: rows }, (_, i) => <Skeleton key={i} />)}
    </div>
  );
}
