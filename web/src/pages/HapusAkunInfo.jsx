import { Link } from 'react-router-dom';
import PublikLayout, { Bagian, Butir, Item, Pengembang } from '../components/PublikLayout';
import { KONTAK_EMAIL } from '../utils/kontak';

/**
 * Cara meminta penghapusan akun EcoWin (halaman publik, tautan ini juga dipakai pada formulir Data Safety Google Play).
 */
export default function HapusAkunInfo() {
  return (
    <PublikLayout judul="Hapus Akun EcoWin" ringkas="Kamu bisa menghapus akun dan data pribadimu kapan saja, langsung dari aplikasi.">
      <Bagian id="langkah" judul="Cara menghapus akun">
        <ol className="list-decimal space-y-2 pl-5 marker:font-bold marker:text-primary-deep">
          <li>Buka aplikasi EcoWin (Android) atau web nasabah, lalu masuk dengan akun Google-mu.</li>
          <li>Buka menu <strong>Profil</strong> dan geser ke bawah.</li>
          <li>Ketuk <strong>Hapus akun saya</strong>.</li>
          <li>Ketik <strong>HAPUS</strong> untuk memastikan, lalu ketuk <strong>Hapus akun saya</strong> sekali lagi.</li>
        </ol>
        <p>Kalau berhasil, kamu otomatis keluar dan akun itu tidak bisa dipakai masuk lagi.</p>
      </Bagian>

      <Bagian id="syarat" judul="Syarat penghapusan">
        <Butir>
          <Item>Saldomu harus <strong>Rp 0</strong>. Kalau masih ada saldo, ajukan penarikan dulu ya.</Item>
          <Item>Tidak ada <strong>penarikan saldo</strong> atau <strong>aktivitas organik</strong> yang masih menunggu keputusan petugas.</Item>
        </Butir>
        <p>Kalau syaratnya belum terpenuhi, aplikasi akan menjelaskan alasannya dan akunmu tidak berubah.</p>
      </Bagian>

      <Bagian id="data" judul="Data apa yang terhapus">
        <Butir>
          <Item><strong>Dihapus permanen:</strong> nama, email, nomor HP, alamat, NISN/NIK dan foto identitas (kalau ada), foto profil, tautan akun Google, token login, dan token notifikasi. Kartu QR lamamu juga tidak berlaku lagi.</Item>
          <Item><strong>Tetap tersimpan tanpa identitas:</strong> riwayat transaksi dan aktivitas organik (termasuk foto buktinya), laporan bug yang pernah kamu kirim, dan log audit. Semuanya hanya dipakai untuk pembukuan, perbaikan aplikasi, dan keamanan. Datanya tampil sebagai &ldquo;Nasabah dihapus&rdquo; dan tidak lagi terhubung ke nama atau emailmu.</Item>
          <Item><strong>Cadangan server:</strong> salinan cadangan harian disimpan 14 hari lalu terhapus otomatis, jadi data yang sudah kamu hapus mungkin masih ada di cadangan sampai 14 hari.</Item>
        </Butir>
        <p>Penghapusan <strong>tidak bisa dibatalkan</strong>. Kalau nanti mau memakai EcoWin lagi, kamu perlu mendaftar ulang sebagai nasabah baru.</p>
      </Bagian>

      <Bagian id="bantuan" judul="Tidak bisa membuka aplikasi?">
        <p>Kirim email ke <a href={`mailto:${KONTAK_EMAIL}`}>{KONTAK_EMAIL}</a> dari alamat email Google yang kamu pakai di EcoWin, dan sertakan nama serta nomor nasabahmu. Kami akan memprosesnya paling lambat 30 hari dengan aturan yang sama (saldo harus Rp 0).</p>
        <p>Akun petugas atau admin dihapus oleh pengelola Bank Sampah, jadi hubungi pengelolamu ya.</p>
        <p>Penjelasan lengkap tentang datamu ada di <Link to="/privasi">Kebijakan Privasi</Link>. Layanan ini dikembangkan oleh <Pengembang />.</p>
      </Bagian>
    </PublikLayout>
  );
}
