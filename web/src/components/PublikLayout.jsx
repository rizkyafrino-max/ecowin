import { Link } from 'react-router-dom';
import emblem from '../assets/ecowin-emblem.png';

/** Kerangka halaman publik (tanpa login): kebijakan privasi dan cara menghapus akun. Dapat dibuka siapa saja. */
export default function PublikLayout({ judul, ringkas, children }) {
  return (
    <div className="min-h-dvh bg-canvas">
      <header className="bg-gradient-to-br from-[#065F46] via-primary to-secondary px-5 pb-16 pt-6 text-white sm:px-10">
        <div className="mx-auto flex max-w-3xl items-center justify-between gap-3">
          <Link to="/" className="flex items-center gap-3" aria-label="EcoWin, kembali ke beranda">
            <span className="flex h-10 w-10 items-center justify-center rounded-2xl bg-white shadow-[0_6px_16px_rgba(0,0,0,0.14)]"><img src={emblem} alt="" className="h-6 w-auto" /></span>
            <span className="text-xl font-extrabold tracking-tight">Eco<span className="text-emerald-200">Win</span></span>
          </Link>
          <Link to="/" className="rounded-full bg-white/15 px-4 py-2 text-sm font-semibold hover:bg-white/25">Kembali</Link>
        </div>
        <div className="mx-auto mt-8 max-w-3xl">
          <h1 className="text-3xl font-extrabold leading-tight tracking-tight sm:text-4xl">{judul}</h1>
          {ringkas && <p className="mt-2 max-w-[60ch] text-[15px] leading-relaxed text-emerald-50/90">{ringkas}</p>}
        </div>
      </header>

      <main className="relative z-10 mx-auto -mt-8 max-w-3xl px-4 pb-10 sm:px-6">
        <article className="rounded-[28px] border border-line/90 bg-surface p-6 shadow-[0_24px_50px_-22px_rgba(6,95,70,0.35)] sm:p-9">
          {children}
        </article>
        <nav aria-label="Halaman lain" className="mt-6 flex flex-wrap items-center justify-center gap-x-5 gap-y-2 text-sm font-semibold text-primary-deep">
          <Link to="/privasi" className="hover:underline">Kebijakan Privasi</Link>
          <Link to="/hapus-akun" className="hover:underline">Hapus Akun</Link>
          <Link to="/masuk" className="hover:underline">Masuk</Link>
        </nav>
        <p className="mt-3 text-center text-xs text-slate-400">© {new Date().getFullYear()} EcoWin · Bank Sampah Digital Berkelanjutan</p>
      </main>
    </div>
  );
}

/** Bagian bernomor di dalam artikel. */
export function Bagian({ id, judul, children }) {
  return (
    <section id={id} className="mt-8 first:mt-0 scroll-mt-6">
      <h2 className="text-lg font-extrabold text-ink">{judul}</h2>
      <div className="mt-2 space-y-3 text-[15px] leading-relaxed text-muted [&_strong]:text-ink [&_a]:font-semibold [&_a]:text-primary-deep [&_a]:underline">{children}</div>
    </section>
  );
}

/** Daftar butir dengan penanda hijau. */
export function Butir({ children }) {
  return (
    <ul className="space-y-2">
      {children}
    </ul>
  );
}

export function Item({ children }) {
  return (
    <li className="flex gap-2.5">
      <span aria-hidden className="mt-2 h-1.5 w-1.5 flex-none rounded-full bg-primary" />
      <span>{children}</span>
    </li>
  );
}
