import { QrCode } from 'lucide-react';
import Card from './Card';
import { LoadingState } from './States';

/** QR Card nasabah. Isi QR hanya token acak (tanpa data pribadi); bukan pengganti login. */
export default function QRCard({ data, loading }) {
  if (loading) return <Card><LoadingState rows={2} /></Card>;
  if (!data) return null;
  return (
    <Card className="flex flex-col items-center text-center">
      <div className="flex items-center gap-2 text-sm font-semibold text-primary"><QrCode className="h-4 w-4" aria-hidden /> QR Card Nasabah</div>
      <img className="mt-4 h-52 w-52 rounded-2xl border border-line p-2" alt={`QR nasabah ${data.nomor_nasabah}`} src={`data:image/png;base64,${data.qr_png_base64}`} />
      <p className="mt-4 text-lg font-bold">{data.nama}</p>
      <p className="font-mono text-sm tracking-wider text-muted">{data.nomor_nasabah}</p>
      <p className="mt-3 max-w-xs text-xs text-muted">Tunjukkan QR ini kepada petugas Bank Sampah. QR hanya berisi kode acak, bukan data pribadi.</p>
    </Card>
  );
}
