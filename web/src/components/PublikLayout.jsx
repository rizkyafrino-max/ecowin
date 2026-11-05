import { Link } from 'react-router-dom';
import emblem from '../assets/ecowin-emblem.png';
import { NAMA_PENGEMBANG, URL_PORTOFOLIO } from '../utils/kontak';

/** Tautan ke situs luar: dibuka di tab baru tanpa mengirim asal halaman (rel noopener noreferrer). */
export const TautanLuar = ({ href, children }) => (
  <a href={href} target="_blank" rel="noopener noreferrer">{children}</a>
);

/** Nama pengembang yang menaut ke portofolionya. */
export const Pengembang = () => <TautanLuar href={URL_PORTOFOLIO}>{NAMA_PENGEMBANG}</TautanLuar>;

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
        <p className="mt-3 text-center text-xs text-slate-400">
          © {new Date().getFullYear()} EcoWin · Bank Sampah Digital Berkelanjutan · Dikembangkan oleh{' '}
          <a href={URL_PORTOFOLIO} target="_blank" rel="noopener noreferrer" className="font-semibold text-primary-deep hover:underline">{NAMA_PENGEMBANG}</a>
        </p>
      </main>
    </div>
  );
}

const TEKS = 'space-y-3 text-[15px] leading-relaxed text-muted [&_strong]:text-ink [&_a]:font-semibold [&_a]:text-primary-deep [&_a]:underline';

/** Bagian di dalam artikel; judul bernomor ditulis oleh pemanggil. */
export function Bagian({ id, judul, children }) {
  return (
    <section id={id} className="mt-9 scroll-mt-6 first:mt-0">
      <h2 className="border-b border-line pb-2 text-lg font-extrabold text-ink">{judul}</h2>
      <div className={`mt-3 ${TEKS}`}>{children}</div>
    </section>
  );
}

/** Daftar butir dengan penanda hijau. */
export function Butir({ children }) {
  return <ul className="space-y-2">{children}</ul>;
}

export function Item({ children }) {
  return (
    <li className="flex gap-2.5">
      <span aria-hidden className="mt-2 h-1.5 w-1.5 flex-none rounded-full bg-primary" />
      <span>{children}</span>
    </li>
  );
}

/** Kotak ringkasan di awal halaman. */
export function Ringkasan({ judul, children }) {
  return (
    <aside className="mb-8 rounded-2xl border border-emerald-200 bg-tint p-5" aria-label={judul}>
      <p className="text-sm font-extrabold uppercase tracking-wider text-primary-deep">{judul}</p>
      <div className={`mt-2 ${TEKS}`}>{children}</div>
    </aside>
  );
}

/** Daftar isi dengan tautan ke tiap bagian. */
export function DaftarIsi({ bagian }) {
  return (
    <nav aria-label="Daftar isi" className="mb-8 rounded-2xl border border-line bg-canvas p-5">
      <p className="text-sm font-extrabold uppercase tracking-wider text-muted">Daftar isi</p>
      <ol className="mt-2 grid gap-x-6 gap-y-1 text-[14px] sm:grid-cols-2">
        {bagian.map(([id, judul], i) => (
          <li key={id} className="flex gap-2"><span className="w-5 flex-none text-right font-bold text-primary-deep">{i + 1}.</span><a href={`#${id}`} className="text-ink hover:text-primary-deep hover:underline">{judul}</a></li>
        ))}
      </ol>
    </nav>
  );
}

/**
 * Tabel yang tetap terbaca di HP: layar lebar memakai tabel, layar sempit menumpuk tiap baris menjadi kartu.
 * `kolom`: judul kolom; `baris`: larik larik sel (sel pertama dicetak tebal).
 */
export function Tabel({ kolom, baris, label }) {
  return (
    <>
      <div className="hidden overflow-hidden rounded-2xl border border-line md:block">
        <table className="w-full border-collapse text-left text-[14px]" aria-label={label}>
          <thead className="bg-canvas text-xs uppercase tracking-wider text-muted">
            <tr>{kolom.map((k) => <th key={k} scope="col" className="px-4 py-2.5 font-bold">{k}</th>)}</tr>
          </thead>
          <tbody className="divide-y divide-line">
            {baris.map((b) => (
              <tr key={b[0]} className="align-top">
                {b.map((sel, i) => <td key={i} className={`px-4 py-3 ${i === 0 ? 'font-bold text-ink' : ''}`}>{sel}</td>)}
              </tr>
            ))}
          </tbody>
        </table>
      </div>
      <ul className="space-y-3 md:hidden" aria-label={label}>
        {baris.map((b) => (
          <li key={b[0]} className="rounded-2xl border border-line bg-canvas p-4">
            <p className="font-bold text-ink">{b[0]}</p>
            <dl className="mt-1.5 space-y-1.5 text-[14px]">
              {b.slice(1).map((sel, i) => (
                <div key={i}><dt className="text-[11px] font-bold uppercase tracking-wider text-muted">{kolom[i + 1]}</dt><dd>{sel}</dd></div>
              ))}
            </dl>
          </li>
        ))}
      </ul>
    </>
  );
}
