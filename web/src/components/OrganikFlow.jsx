import { ChevronRight, Leaf, Printer, Recycle } from 'lucide-react';
import Card from './Card';

/** Menjelaskan hubungan Organik → metode (Biopori / BioporiPrint); bukan kategori terpisah. */
export default function OrganikFlow({ compact = false }) {
  const steps = [
    { icon: Recycle, title: 'Organik', text: 'Satu fitur untuk semua aktivitas sampah organik. Tidak menjadi saldo rupiah.' },
    { icon: Leaf, title: 'Pilih metode', text: 'Biopori atau BioporiPrint, isi data dan unggah foto bukti.' },
    { icon: Printer, title: 'BioporiPrint', text: 'Metode pipa biopori hasil 3D printing dari plastik warga yang didaur ulang.' },
  ];
  return (
    <Card className="bg-tint/60">
      <p className="text-sm font-bold">Cara kerja jalur Organik</p>
      <ol className={`mt-3 grid gap-3 ${compact ? '' : 'sm:grid-cols-3'}`}>
        {steps.map((s, i) => (
          <li key={s.title} className="flex items-start gap-3 rounded-2xl bg-surface p-3.5">
            <span className="flex h-10 w-10 flex-none items-center justify-center rounded-xl bg-tint text-primary"><s.icon className="h-5 w-5" aria-hidden /></span>
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
