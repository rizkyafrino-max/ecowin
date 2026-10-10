import logo from '../assets/ecowin-logo.png';

/**
 * Layar peluncuran: logo EcoWin asli, tampil singkat selagi sesi tersimpan diperiksa (tidak ada data sensitif).
 * Dipakai saat status auth 'loading' agar tidak ada kilatan antara Login dan Dashboard.
 */
export default function LaunchScreen({ message = 'Menyiapkan EcoWin…' }) {
  return (
    <main className="flex min-h-dvh flex-col items-center justify-center bg-canvas px-6 text-center" role="status" aria-live="polite" aria-label={message}>
      <img src={logo} alt="EcoWin" width="340" height="395" className="ew-launch-logo h-auto w-[116px] sm:w-[132px]" />
      <span className="mt-9 h-1 w-[72px] overflow-hidden rounded-full bg-tint" aria-hidden>
        <i className="ew-bar block h-full w-1/2 rounded-full bg-primary" />
      </span>
    </main>
  );
}
