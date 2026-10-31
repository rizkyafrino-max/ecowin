import { Leaf, MapPin } from 'lucide-react';
import emblem from '../assets/ecowin-emblem.png';
import Card from './Card';
import { LoadingState } from './States';

/**
 * Kartu Nasabah melintang (rasio kartu bank 85,6 x 54 mm), sama dengan di Android: merek dan data nasabah di kiri, QR putih di
 * kanan. Ukuran isi mengikuti lebar kartu lewat satuan container (cqw), jadi rapi dari HP sampai desktop.
 * Isi QR hanya token acak (tanpa data pribadi); bukan pengganti login.
 */
export default function QRCard({ data, loading, bank, tanpaCatatan = false }) {
  if (loading) return <Card><LoadingState rows={2} /></Card>;
  if (!data) return null;
  return (
    <div className="[container-type:inline-size]">
      <section
        aria-label="Kartu Nasabah"
        className="relative aspect-[1.586/1] w-full overflow-hidden rounded-[28px] bg-gradient-to-br from-[#064E3B] via-primary to-accent p-[6.5cqw] text-white shadow-[0_18px_40px_-18px_rgb(6_78_59/0.75)] ring-1 ring-inset ring-white/25"
      >
        <span aria-hidden className="pointer-events-none absolute -right-[12cqw] -top-[22cqw] h-[60cqw] w-[60cqw] rounded-full bg-white/[0.09]" />
        <span aria-hidden className="pointer-events-none absolute -bottom-[26cqw] -left-[10cqw] h-[44cqw] w-[44cqw] rounded-full bg-white/[0.07]" />
        <Leaf aria-hidden className="pointer-events-none absolute -bottom-[4cqw] -right-[2cqw] h-[56cqw] w-[56cqw] text-white/[0.07]" />

        <div className="relative flex h-full gap-[5cqw]">
          <div className="flex min-w-0 flex-1 flex-col justify-between">
            <div className="flex items-center gap-[2.4cqw]">
              <span className="flex h-[10cqw] w-[10cqw] flex-none items-center justify-center rounded-[2.4cqw] bg-white p-[1.2cqw]">
                <img src={emblem} alt="" className="h-full w-auto" />
              </span>
              <span className="leading-tight">
                <span className="block text-[clamp(13px,4.2cqw,26px)] font-extrabold">Eco<span className="text-emerald-200">Win</span></span>
                <span className="block text-[clamp(7px,2.1cqw,12px)] font-bold tracking-[0.1em] text-emerald-200">BANK SAMPAH DIGITAL</span>
              </span>
            </div>

            <div>
              <p className="text-[clamp(7px,2.3cqw,13px)] font-bold tracking-[0.15em] text-emerald-200">KARTU NASABAH</p>
              <p className="mt-[0.8cqw] line-clamp-2 break-words text-[clamp(14px,5.6cqw,40px)] font-extrabold leading-[1.15]">{data.nama}</p>
              <p className="mt-[1cqw] font-mono text-[clamp(10px,3.6cqw,22px)] font-semibold tracking-[0.12em] text-emerald-100">{data.nomor_nasabah}</p>
            </div>

            <p className="flex items-center gap-[1.6cqw] text-[clamp(9px,2.9cqw,16px)] font-medium text-white/90">
              <MapPin aria-hidden className="h-[3.4cqw] w-[3.4cqw] min-h-3 min-w-3 flex-none text-emerald-200" />
              <span className="truncate">{bank || 'EcoWin'}</span>
            </p>
          </div>

          <div className="flex flex-none flex-col items-center justify-center">
            <div className="rounded-[3.6cqw] bg-white p-[1.6cqw]" style={{ width: '37cqw', height: '37cqw' }}>
              <img className="h-full w-full [image-rendering:pixelated]" alt={`QR nasabah ${data.nomor_nasabah}`} src={`data:image/png;base64,${data.qr_png_base64}`} />
            </div>
            <p className="mt-[1.6cqw] text-[clamp(6.5px,2cqw,12px)] font-bold tracking-[0.1em] text-emerald-100">PINDAI OLEH PETUGAS</p>
          </div>
        </div>
      </section>
      {!tanpaCatatan && <p className="mx-auto mt-3 max-w-sm text-center text-xs text-muted">Tunjukkan kartu ini kepada petugas Bank Sampah. QR hanya berisi kode acak, bukan data pribadi.</p>}
    </div>
  );
}
