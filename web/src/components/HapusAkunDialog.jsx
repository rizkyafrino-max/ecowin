import { useState } from 'react';
import { EcoApi } from '../api/endpoints';
import Button from './Button';
import { Input } from './Field';
import { BottomSheet } from './Modal';

const KATA_KUNCI = 'HAPUS';

/**
 * Hapus akun oleh nasabah sendiri (wajib untuk Google Play). Pengguna harus mengetik HAPUS; server menolak bila
 * saldo belum Rp 0 atau masih ada permintaan yang menunggu, dan pesannya ditampilkan di sini.
 */
export default function HapusAkunDialog({ open, onClose, onDeleted }) {
  const [kata, setKata] = useState('');
  const [busy, setBusy] = useState(false);
  const [galat, setGalat] = useState('');

  const tutup = () => {
    if (busy) return;
    setKata(''); setGalat('');
    onClose();
  };

  const hapus = async () => {
    setBusy(true); setGalat('');
    try {
      await EcoApi.hapusAkun(kata);
      await onDeleted(); // keluar dan kembali ke halaman masuk
    } catch (err) {
      setGalat((err.errors?.akun && [].concat(err.errors.akun)[0]) || err.message || 'Akun gagal dihapus. Coba lagi.');
      setBusy(false);
    }
  };

  return (
    <BottomSheet open={open} onClose={tutup} title="Hapus akun?">
      <p className="text-sm leading-relaxed text-muted">
        Data pribadimu (nama, email, nomor HP, alamat, foto, dan tautan akun Google) akan dihapus <strong className="text-ink">permanen</strong> dan
        tidak bisa dikembalikan. Riwayat transaksi tetap tersimpan tanpa identitas untuk pembukuan Bank Sampah.
      </p>
      <p className="mt-2 text-sm leading-relaxed text-muted">Syaratnya: saldo Rp 0 dan tidak ada permintaan yang masih menunggu. Kalau masih ada saldo, tarik dulu ya.</p>
      <div className="mt-4">
        <Input label={`Ketik ${KATA_KUNCI} untuk melanjutkan`} value={kata} onChange={(e) => { setKata(e.target.value); setGalat(''); }}
          autoComplete="off" autoCapitalize="characters" spellCheck={false} error={galat} />
      </div>
      <div className="mt-6 flex gap-3">
        <Button variant="outline" className="flex-1" onClick={tutup} disabled={busy}>Batal</Button>
        <Button variant="danger" className="flex-1" loading={busy} disabled={kata !== KATA_KUNCI} onClick={hapus}>Hapus akun saya</Button>
      </div>
    </BottomSheet>
  );
}
