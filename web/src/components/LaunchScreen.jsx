import emblem from '../assets/ecowin-emblem.png';

/**
 * Layar peluncuran: tampil singkat selagi sesi tersimpan diperiksa (tidak ada data sensitif).
 * Dipakai saat status auth 'loading' agar tidak ada kilatan antara Login dan Dashboard.
 */
export default function LaunchScreen({ message = 'Menyiapkan EcoWin…' }) {
  return (
    <main className="flex min-h-dvh flex-col items-center justify-center bg-canvas px-6 text-center" role="status" aria-live="polite" aria-label={message}>
      <span className="ew-launch-mark flex h-20 w-20 items-center justify-center rounded-[26px] bg-black shadow-soft">
        <img src={emblem} alt="" className="h-12 w-auto" />
      </span>
      <p className="mt-5 text-3xl font-extrabold tracking-tight">Eco<span className="text-primary">Win</span></p>
      <p className="mt-1.5 text-sm font-medium text-muted">Langkah kecil, dampak besar.</p>
      <span className="mt-8 flex gap-1.5" aria-hidden>
        <i className="ew-dot h-2 w-2 rounded-full bg-primary" />
        <i className="ew-dot h-2 w-2 rounded-full bg-primary" style={{ animationDelay: '.15s' }} />
        <i className="ew-dot h-2 w-2 rounded-full bg-primary" style={{ animationDelay: '.3s' }} />
      </span>
    </main>
  );
}
