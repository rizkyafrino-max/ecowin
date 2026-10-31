import { Bug, CheckCircle2, ImagePlus, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { useLocation, useNavigate } from 'react-router-dom';
import { EcoApi } from '../api/endpoints';
import Button from '../components/Button';
import Card from '../components/Card';
import { Textarea } from '../components/Field';
import PageHeader from '../components/PageHeader';
import { perkecilFoto } from '../utils/foto';
import { BATAS_TEKS, MINIMAL_TEKS, infoPerangkat, susunLaporan } from '../utils/laporan';

/** Lapor bug: teks, foto bukti opsional, dan info teknis. Laporan masuk ke panel admin (kategori "bug"). */
export default function LaporBug() {
  const navigate = useNavigate();
  const { state } = useLocation();
  const layarAsal = state?.dari || 'Web';
  const [teks, setTeks] = useState('');
  const [foto, setFoto] = useState(null); // { blob, url }
  const [menyiapkan, setMenyiapkan] = useState(false);
  const [mengirim, setMengirim] = useState(false);
  const [galat, setGalat] = useState('');
  const [terkirim, setTerkirim] = useState(false);
  const berkas = useRef(null);
  const cukup = teks.trim().length >= MINIMAL_TEKS;

  // Alamat pratinjau dilepas saat foto diganti, dihapus, atau halaman ditutup.
  useEffect(() => () => { if (foto?.url) URL.revokeObjectURL(foto.url); }, [foto]);

  const pilihFoto = async (e) => {
    const file = e.target.files?.[0];
    e.target.value = ''; // boleh memilih berkas yang sama lagi
    if (!file) return;
    setGalat(''); setMenyiapkan(true);
    try {
      // Diperkecil dan digambar ulang di perangkat (metadata terbuang); server memeriksa dan menggambar ulang lagi.
      const blob = await perkecilFoto(file, 1600);
      setFoto({ blob, url: typeof URL.createObjectURL === 'function' ? URL.createObjectURL(blob) : null });
    } catch (err) {
      setGalat(err.message || 'Foto tidak dapat dibaca. Coba foto lain.');
    } finally { setMenyiapkan(false); }
  };

  const kirim = async (e) => {
    e.preventDefault();
    if (!cukup || mengirim) return;
    setMengirim(true); setGalat('');
    try {
      await EcoApi.kirimLaporan(susunLaporan(teks, infoPerangkat(layarAsal)), foto?.blob);
      setTeks(''); setFoto(null); setTerkirim(true);
    } catch (err) {
      setGalat(err.errors?.foto?.[0] || err.errors?.deskripsi?.[0] || err.message || 'Laporan gagal dikirim. Coba lagi.');
    } finally { setMengirim(false); }
  };

  if (terkirim) {
    return (
      <div className="space-y-5">
        <PageHeader title="Lapor bug" subtitle="Laporkan masalah yang Anda temui di aplikasi." />
        <Card className="max-w-xl space-y-3">
          <CheckCircle2 className="h-8 w-8 text-primary" aria-hidden />
          <h3 className="text-lg font-bold">Terima kasih, laporan terkirim</h3>
          <p role="status" className="text-sm text-muted">Laporan Anda sudah masuk ke tim EcoWin dan akan kami periksa.</p>
          <div className="flex flex-col gap-2 pt-2 sm:flex-row">
            <Button type="button" onClick={() => navigate(-1)}>Kembali</Button>
            <Button type="button" variant="outline" onClick={() => setTerkirim(false)}>Kirim laporan lain</Button>
          </div>
        </Card>
      </div>
    );
  }

  return (
    <div className="space-y-5">
      <PageHeader title="Lapor bug" subtitle="Laporkan masalah yang Anda temui di aplikasi." />
      <Card className="max-w-xl">
        <form onSubmit={kirim} className="space-y-5" noValidate>
          <p className="text-sm text-muted">Ceritakan masalah yang Anda temui: apa yang Anda lakukan, apa yang terjadi, dan apa yang seharusnya terjadi.</p>

          <Textarea
            label="Apa masalahnya?" rows={6} value={teks} maxLength={BATAS_TEKS}
            onChange={(e) => { setTeks(e.target.value.slice(0, BATAS_TEKS)); setGalat(''); }}
            placeholder="Contoh: saat menekan Tarik Saldo, layar menjadi putih."
            hint={cukup ? `${teks.length}/${BATAS_TEKS}` : `Minimal ${MINIMAL_TEKS} karakter. ${teks.length}/${BATAS_TEKS}`}
          />

          <div>
            <p className="mb-1.5 text-sm font-semibold">Foto bukti (opsional)</p>
            <input ref={berkas} type="file" accept="image/jpeg,image/png,image/webp" className="hidden" onChange={pilihFoto} aria-label="Pilih foto bukti" data-testid="input-foto-bug" />
            {foto ? (
              <div className="relative overflow-hidden rounded-2xl border border-line bg-slate-100">
                {foto.url && <img src={foto.url} alt="Foto bukti laporan" className="mx-auto max-h-64 w-auto object-contain" />}
                {!foto.url && <p className="p-4 text-sm text-muted">Foto siap dikirim.</p>}
                <button type="button" onClick={() => setFoto(null)} aria-label="Hapus foto" disabled={mengirim}
                  className="absolute right-2 top-2 flex h-9 w-9 items-center justify-center rounded-full bg-black/60 text-white transition hover:bg-black/75 disabled:opacity-50">
                  <X className="h-[18px] w-[18px]" aria-hidden />
                </button>
              </div>
            ) : (
              <Button type="button" variant="outline" icon={ImagePlus} className="w-full !h-12 !justify-center" loading={menyiapkan} disabled={mengirim} onClick={() => berkas.current?.click()}>
                {menyiapkan ? 'Menyiapkan foto…' : 'Tambah foto atau tangkapan layar'}
              </Button>
            )}
            <p className="mt-1.5 text-xs text-muted">Foto membantu kami melihat masalahnya. Hindari menampilkan data pribadi Anda; lokasi dan metadata foto dihapus otomatis.</p>
          </div>

          <p className="text-xs text-muted">Info teknis aplikasi (jenis peramban dan ukuran layar) ikut terkirim untuk membantu kami memperbaiki masalah. Tidak ada data pribadi atau saldo yang dikirim.</p>

          {galat && <p role="alert" className="text-sm font-semibold text-danger">{galat}</p>}
          <Button type="submit" size="lg" icon={Bug} className="w-full sm:w-auto" loading={mengirim} disabled={!cukup || menyiapkan}>Kirim laporan</Button>
        </form>
      </Card>
    </div>
  );
}
