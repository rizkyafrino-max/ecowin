import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { Camera, MapPin, Printer } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { MapContainer, Marker, Popup, TileLayer, useMap } from 'react-leaflet';
import { bagiTitik } from '../utils/biopori';
import { tanggal } from '../utils/format';
import Button from './Button';
import Card from './Card';
import StatusBadge from './StatusBadge';

// Marker memakai ikon SVG sendiri (tanpa berkas gambar bawaan Leaflet): BioporiPrint = hijau + ikon printer.
function ikon(print) {
  const svg = renderToStaticMarkup(print ? <Printer size={18} color="#fff" strokeWidth={2.2} /> : <MapPin size={18} color="#059669" strokeWidth={2.2} />);
  return L.divIcon({
    className: '',
    html: `<span style="display:flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:999px;border:3px solid #fff;box-shadow:0 2px 8px rgba(15,23,42,.35);background:${print ? '#059669' : '#ffffff'}">${svg}</span>`,
    iconSize: [36, 36],
    iconAnchor: [18, 18],
    popupAnchor: [0, -18],
  });
}

const IKON_PRINT = ikon(true);
const IKON_BIASA = ikon(false);

function FitBounds({ titik }) {
  const map = useMap();
  useEffect(() => {
    if (!titik.length) return;
    const pts = titik.map((t) => [Number(t.latitude), Number(t.longitude)]);
    if (pts.length === 1) map.setView(pts[0], 17);
    else map.fitBounds(pts, { padding: [40, 40], maxZoom: 17 });
  }, [titik, map]);
  return null;
}

/**
 * Peta lokasi Biopori milik Bank Sampah nasabah. BioporiPrint (pipa biopori hasil 3D printing) ditandai
 * khusus dan bisa difilter. Data dari API (sudah dibatasi ke Bank Sampah nasabah di server).
 */
export default function PetaBiopori({ titik = [], onLapor }) {
  const [hanyaPrint, setHanyaPrint] = useState(false);
  const { dipeta, tanpaKoordinat, jumlahPrint } = useMemo(() => bagiTitik(titik, hanyaPrint), [titik, hanyaPrint]);

  return (
    <Card className="!p-0 overflow-hidden">
      <div className="flex flex-wrap items-center justify-between gap-3 p-4 sm:p-5">
        <div>
          <h3 className="font-bold">Peta lokasi Biopori</h3>
          <p className="mt-0.5 text-xs text-muted">Titik Biopori di Bank Sampah Anda. Ketuk penanda untuk detail.</p>
        </div>
        <div className="flex gap-2" role="group" aria-label="Filter peta">
          <button type="button" aria-pressed={!hanyaPrint} onClick={() => setHanyaPrint(false)}
            className={`rounded-full px-3.5 py-1.5 text-xs font-semibold transition ${!hanyaPrint ? 'bg-primary text-white' : 'bg-canvas text-muted hover:bg-tint'}`}>Semua ({titik.length})</button>
          <button type="button" aria-pressed={hanyaPrint} onClick={() => setHanyaPrint(true)}
            className={`flex items-center gap-1.5 rounded-full px-3.5 py-1.5 text-xs font-semibold transition ${hanyaPrint ? 'bg-primary text-white' : 'bg-canvas text-muted hover:bg-tint'}`}><Printer className="h-3.5 w-3.5" aria-hidden />BioporiPrint ({jumlahPrint})</button>
        </div>
      </div>

      {dipeta.length ? (
        <div className="h-[320px] w-full sm:h-[420px]" role="region" aria-label="Peta lokasi Biopori">
          <MapContainer center={[-2.5, 118]} zoom={5} scrollWheelZoom={false} className="h-full w-full">
            <TileLayer attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>' url="https://tile.openstreetmap.org/{z}/{x}/{y}.png" />
            <FitBounds titik={dipeta} />
            {dipeta.map((t) => (
              <Marker key={t.id} position={[Number(t.latitude), Number(t.longitude)]} icon={t.bioporiprint ? IKON_PRINT : IKON_BIASA}>
                <Popup>
                  <div className="min-w-[200px] text-[13px]">
                    <p className="text-sm font-bold">{t.nama_lokasi}</p>
                    <div className="mt-1.5 flex flex-wrap gap-1.5">
                      <StatusBadge status={t.status_panen} />
                      {t.bioporiprint && <span className="inline-flex items-center gap-1 rounded-full bg-tint px-2.5 py-1 text-xs font-semibold text-primary"><Printer className="h-3 w-3" aria-hidden />BioporiPrint</span>}
                    </div>
                    {t.deskripsi_lokasi && <p className="mt-2 text-muted">{t.deskripsi_lokasi}</p>}
                    <p className="mt-2 text-muted">Terakhir diisi: <b className="text-ink">{tanggal(t.terakhir_diisi_at)}</b></p>
                    <p className="text-muted">Estimasi panen: <b className="text-ink">{tanggal(t.estimasi_panen_at)}</b></p>
                    {onLapor && <Button size="sm" className="mt-3 w-full" icon={Camera} onClick={() => onLapor(t)}>Lapor di sini</Button>}
                  </div>
                </Popup>
              </Marker>
            ))}
          </MapContainer>
        </div>
      ) : (
        <div className="px-5 pb-6 text-center text-sm text-muted">
          {hanyaPrint ? 'Belum ada titik BioporiPrint yang berkoordinat di Bank Sampah Anda.' : 'Belum ada titik Biopori berkoordinat di Bank Sampah Anda.'}
        </div>
      )}

      {tanpaKoordinat.length > 0 && (
        <p className="border-t border-line px-5 py-3 text-xs text-muted">{tanpaKoordinat.length} titik belum memiliki koordinat: {tanpaKoordinat.map((t) => t.nama_lokasi).join(', ')}.</p>
      )}
    </Card>
  );
}
