import { X } from 'lucide-react';
import { useEffect } from 'react';
import { createPortal } from 'react-dom';
import Button from './Button';

function useEscape(onClose) {
  useEffect(() => {
    const h = (e) => e.key === 'Escape' && onClose();
    document.addEventListener('keydown', h);
    const prev = document.body.style.overflow;
    document.body.style.overflow = 'hidden';
    return () => { document.removeEventListener('keydown', h); document.body.style.overflow = prev; };
  }, [onClose]);
}

function SheetInner({ onClose, title, children }) {
  useEscape(onClose);
  return createPortal(
    <div className="fixed inset-0 z-50 flex items-end justify-center sm:items-center" role="dialog" aria-modal="true" aria-label={title}>
      <button className="absolute inset-0 bg-ink/40" onClick={onClose} aria-label="Tutup" tabIndex={-1} />
      <div className="ew-sheet relative flex max-h-[92dvh] w-full flex-col rounded-t-[28px] bg-surface shadow-2xl sm:max-w-lg sm:rounded-[28px]">
        <div className="mx-auto mt-2.5 h-1.5 w-10 rounded-full bg-line sm:hidden" aria-hidden />
        <div className="flex items-center justify-between px-6 pb-2 pt-4">
          <h2 className="text-lg font-bold">{title}</h2>
          <button onClick={onClose} className="rounded-full p-2 text-muted hover:bg-canvas" aria-label="Tutup"><X className="h-5 w-5" /></button>
        </div>
        <div className="overflow-y-auto px-6 pb-6">{children}</div>
      </div>
    </div>,
    document.body,
  );
}

/** Bottom sheet di HP, dialog di tengah pada layar lebar. */
export function BottomSheet({ open, onClose, title, children }) {
  if (!open) return null;
  return <SheetInner onClose={onClose} title={title}>{children}</SheetInner>;
}

export const Modal = BottomSheet;

export function ConfirmationDialog({ open, title, message, confirmLabel = 'Ya, lanjutkan', loading, onConfirm, onClose }) {
  return (
    <BottomSheet open={open} onClose={onClose} title={title}>
      <p className="text-sm leading-relaxed text-muted">{message}</p>
      <div className="mt-6 flex gap-3">
        <Button variant="outline" className="flex-1" onClick={onClose}>Batal</Button>
        <Button className="flex-1" loading={loading} onClick={onConfirm}>{confirmLabel}</Button>
      </div>
    </BottomSheet>
  );
}
