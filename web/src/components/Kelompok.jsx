import { ChevronRight, ExternalLink } from 'lucide-react';
import { Link } from 'react-router-dom';

/**
 * Daftar berkelompok yang sama dengan Android (Profil dan Pengaturan): satu kartu putih per kelompok berisi baris-baris
 * dengan ikon berlatar lembut, judul, keterangan, dan penanda di kanan; catatan kecil ada di bawah kartu.
 */
export function Kelompok({ judul, catatan, children, className = '' }) {
  return (
    <section className={className}>
      <h3 className="mb-2 pl-1.5 text-xs font-bold uppercase tracking-wider text-muted">{judul}</h3>
      <div className="divide-y divide-slate-100 overflow-hidden rounded-[20px] border border-line bg-surface shadow-soft">{children}</div>
      {catatan && <p className="mt-2 px-1.5 text-xs leading-relaxed text-muted">{catatan}</p>}
    </section>
  );
}

const WARNA = {
  hijau: 'bg-tint text-primary-deep',
  netral: 'bg-slate-100 text-muted',
  amber: 'bg-warn-tint text-warn',
  biru: 'bg-info-tint text-info',
};

/** Satu baris: tautan dalam aplikasi (`to`), tautan luar (`href`, dibuka di tab baru), tombol (`onClick`), atau hanya informasi. */
export function Baris({ ikon: Ikon, judul, keterangan, warna = 'hijau', to, state, href, onClick, kanan }) {
  const isi = (
    <>
      <span className={`flex h-10 w-10 flex-none items-center justify-center rounded-xl ${WARNA[warna]}`}><Ikon className="h-5 w-5" aria-hidden /></span>
      <span className="min-w-0 flex-1">
        <span className="block text-[15px] font-semibold text-ink">{judul}</span>
        {keterangan && <span className="block text-[12.5px] leading-snug text-muted">{keterangan}</span>}
      </span>
      {kanan ?? (href ? <ExternalLink className="h-[18px] w-[18px] flex-none text-slate-400" aria-hidden /> : to ? <ChevronRight className="h-5 w-5 flex-none text-slate-400" aria-hidden /> : null)}
    </>
  );
  const kelas = 'flex w-full items-center gap-3.5 px-4 py-3.5 text-left transition hover:bg-canvas';
  if (to) return <Link to={to} state={state} className={kelas}>{isi}</Link>;
  if (href) return <a href={href} target="_blank" rel="noopener noreferrer" className={kelas}>{isi}</a>;
  if (onClick) return <button type="button" onClick={onClick} className={kelas}>{isi}</button>;
  return <div className={kelas}>{isi}</div>;
}
