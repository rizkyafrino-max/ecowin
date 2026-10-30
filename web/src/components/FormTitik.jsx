import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { Crosshair } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { MapContainer, Marker, TileLayer, useMap, useMapEvents } from 'react-leaflet';
import { EcoApi } from '../api/endpoints';
import Button from './Button';
import { Input, Textarea } from './Field';

// Penanda sederhana (tanpa berkas gambar bawaan Leaflet).
const PENANDA = L.divIcon({
  className: '',
  html: '<span style="display:block;width:28px;height:28px;border-radius:999px;border:4px solid #fff;background:#059669;box-shadow:0 2px 8px rgba(15,23,42,.4)"></span>',
  iconSize: [28, 28],
  iconAnchor: [14, 14],
});

function KlikPeta({ onPick }) {
  useMapEvents({ click: (e) => onPick(e.latlng.lat, e.latlng.lng) });
  return null;
}

function Pindah({ titik }) {
  const map = useMap();
  useEffect(() => { if (titik) map.setView([titik.lat, titik.lng], Math.max(map.getZoom(), 17)); }, [titik, map]);
  return null;
}

/**
 * Nasabah mengajukan titik Biopori baru. Posisi dipilih dengan "Gunakan lokasi saya" (GPS perangkat) atau
 * mengetuk peta lalu menggeser penanda. Titik berstatus Menunggu sampai disetujui petugas Bank Sampah.
 */
export default function FormTitik({ onDone }) {
  const [form, setForm] = useState({ nama_lokasi: '', deskripsi_lokasi: '' });
  const [titik, setTitik] = useState(null); // { lat, lng }
  const [info, setInfo] = useState('Ketuk peta untuk menandai lokasi, atau gunakan lokasi perangkat.');
  const [errors, setErrors] = useState({});
  const [message, setMessage] = useState(null);
  const [busy, setBusy] = useState(false);
  const [mencari, setMencari] = useState(false);
  const watch = useRef(null);

  const stop = () => {
    if (watch.current !== null) { navigator.geolocation.clearWatch(watch.current); watch.current = null; }
    setMencari(false);
  };
  useEffect(() => stop, []);

  const set = (k) => (e) => setForm((f) => ({ ...f, [k]: e.target.value }));
  const pilih = (lat, lng, pesan) => {
    setTitik({ lat, lng });
    setErrors((x) => ({ ...x, latitude: undefined, longitude: undefined }));
    if (pesan) setInfo(pesan);
  };

  const gunakanLokasi = () => {
    if (!navigator.geolocation) { setInfo('Perangkat tidak mendukung lokasi. Ketuk peta untuk menandai posisi.'); return; }
    stop();
    setMencari(true);
    setInfo('Mencari sinyal terbaik…');
    let terbaik = Infinity;
    watch.current = navigator.geolocation.watchPosition(
      (p) => {
        const a = p.coords.accuracy;
        if (a >= terbaik) return;
        terbaik = a;
        pilih(p.coords.latitude, p.coords.longitude, `Akurasi ±${Math.round(a)} m${a > 100 ? '. Kurang akurat: geser penanda ke posisi yang benar.' : '. Anda masih bisa menggeser penanda.'}`);
        if (a <= 20) stop();
      },
      (err) => { stop(); setInfo(err.code === 1 ? 'Izin lokasi ditolak. Ketuk peta untuk menandai posisi.' : 'Lokasi belum tersedia. Ketuk peta untuk menandai posisi.'); },
      { enableHighAccuracy: true, timeout: 20000, maximumAge: 0 },
    );
    setTimeout(stop, 15000);
  };

  const submit = async (e) => {
    e.preventDefault();
    const local = {};
    if (form.nama_lokasi.trim().length < 3) local.nama_lokasi = 'Isi nama lokasi (minimal 3 karakter).';
    if (!titik) local.latitude = 'Tandai posisi titik di peta.';
    setErrors(local);
    if (Object.keys(local).length) return;

    setBusy(true);
    setMessage(null);
    try {
      await EcoApi.ajukanTitik({
        nama_lokasi: form.nama_lokasi.trim(),
        deskripsi_lokasi: form.deskripsi_lokasi.trim() || null,
        latitude: Number(titik.lat.toFixed(7)),
        longitude: Number(titik.lng.toFixed(7)),
      });
      onDone();
    } catch (err) {
      const flat = Object.fromEntries(Object.entries(err.errors || {}).map(([k, v]) => [k, [].concat(v)[0]]));
      setErrors(flat);
      setMessage(err.message);
      setBusy(false);
    }
  };

  return (
    <form onSubmit={submit} className="space-y-4 pt-2" noValidate>
      <Input label="Nama lokasi" value={form.nama_lokasi} onChange={set('nama_lokasi')} error={errors.nama_lokasi} maxLength={100} placeholder="Contoh: Pojok taman RT 03" />
      <Textarea label="Keterangan (opsional)" value={form.deskripsi_lokasi} onChange={set('deskripsi_lokasi')} error={errors.deskripsi_lokasi} maxLength={255} placeholder="Ciri lokasi agar mudah ditemukan petugas" />

      <div>
        <div className="mb-1.5 flex items-center justify-between gap-3">
          <p className="text-sm font-semibold">Posisi di peta <span className="text-danger">*</span></p>
          <Button type="button" size="sm" variant="soft" icon={Crosshair} loading={mencari} onClick={gunakanLokasi}>Gunakan lokasi saya</Button>
        </div>
        <div className="h-[260px] w-full overflow-hidden rounded-2xl border border-line" role="region" aria-label="Pilih posisi titik di peta">
          <MapContainer center={titik ? [titik.lat, titik.lng] : [-2.5, 118]} zoom={titik ? 17 : 5} scrollWheelZoom={false} className="h-full w-full">
            <TileLayer attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>' url="https://tile.openstreetmap.org/{z}/{x}/{y}.png" />
            <KlikPeta onPick={(lat, lng) => pilih(lat, lng, 'Posisi dipilih dari peta. Geser penanda bila perlu.')} />
            <Pindah titik={titik} />
            {titik && (
              <Marker position={[titik.lat, titik.lng]} icon={PENANDA} draggable
                eventHandlers={{ dragend: (ev) => { const p = ev.target.getLatLng(); pilih(p.lat, p.lng, 'Penanda digeser. Posisi diperbarui.'); } }} />
            )}
          </MapContainer>
        </div>
        <p className="mt-1.5 text-xs text-muted" aria-live="polite">{info}</p>
        {titik && <p className="text-xs text-muted tabular-nums">{titik.lat.toFixed(6)}, {titik.lng.toFixed(6)}</p>}
        {(errors.latitude || errors.longitude) && <p className="mt-1 text-xs font-medium text-danger" role="alert">{errors.latitude || errors.longitude}</p>}
      </div>

      {message && <p className="rounded-2xl bg-danger-tint p-3 text-sm text-danger" role="alert">{message}</p>}
      <Button type="submit" size="lg" className="w-full" loading={busy}>Ajukan titik</Button>
      <p className="text-center text-xs text-muted">Titik akan diperiksa petugas Bank Sampah. Setelah disetujui, titik tampil di peta dan bisa dipakai untuk melapor aktivitas.</p>
    </form>
  );
}
