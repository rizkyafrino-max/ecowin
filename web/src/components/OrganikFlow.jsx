import { ChevronDown, ChevronRight, Leaf, Printer, Recycle } from 'lucide-react';
import { useState } from 'react';
import Card from './Card';

/**
 * Menjelaskan hubungan Organik → metode (Biopori / BioporiPrint); bukan kategori terpisah.
 * Di HP penjelasan ini diciutkan supaya aktivitas yang sebenarnya tidak terdorong ke bawah layar; di layar lebar selalu terbuka.
 */
export default function OrganikFlow({ compact = false }) {
  const [buka, setBuka] = useState(false);
  const steps = [
    { icon: Recycle, title: 'Organik', text: 'Satu fitur untuk semua aktivitas sampah organik. Tidak menjadi saldo rupiah.' },
    { icon: Leaf, title: 'Pilih metode', text: 'Pilih Biopori atau BioporiPrint, lalu isi data dan unggah foto bukti.' },
    { icon: Printer, title: 'BioporiPrint', text: 'Metode pipa biopori hasil 3D printing dari plastik warga yang didaur ulang.' },
  ];
  return (
    <Card className="bg-tint/60">
      <button type="button" onClick={() => setBuka((b) => !b)} aria-expanded={buka}
        className="flex w-full items-center justify-between text-left sm:pointer-events-none">
        <span className="text-sm font-bold">Cara kerja jalur Organik</span>
        <ChevronDown className={`h-4 w-4 text-muted transition sm:hidden ${buka ? 'rotate-180' : ''}`} aria-hidden />
      </button>
      <ol className={`mt-3 gap-3 ${buka ? 'grid' : 'hidden'} sm:grid ${compact ? '' : 'sm:grid-cols-3'}`}>
        {steps.map((s, i) => (
          <li key={s.title} className="flex items-start gap-3 rounded-2xl bg-surface p-3.5">
            <span className="flex h-10 w-10 flex-none items-center justify-center rounded-xl bg-tint text-primary-deep"><s.icon className="h-5 w-5" aria-hidden /></span>
            <div className="min-w-0">
              <p className="flex items-center gap-1 text-sm font-semibold">{s.title}{i < steps.length - 1 && <ChevronRight className="hidden h-3.5 w-3.5 text-muted sm:block" aria-hidden />}</p>
              <p className="mt-0.5 text-xs leading-relaxed text-muted">{s.text}</p>
            </div>
          </li>
        ))}
      </ol>
    </Card>
  );
}
