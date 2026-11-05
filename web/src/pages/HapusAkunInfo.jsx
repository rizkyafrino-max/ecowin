import { Link } from 'react-router-dom';
import PublikLayout, { Bagian, Butir, Item } from '../components/PublikLayout';
import { KONTAK_EMAIL } from '../utils/kontak';

/**
 * Cara meminta penghapusan akun EcoWin (halaman publik, tautan ini juga dipakai pada formulir Data Safety Google Play).
 */
export default function HapusAkunInfo() {
  return (
    <PublikLayout judul="Hapus Akun EcoWin" ringkas="Anda dapat menghapus akun dan data pribadi Anda kapan saja, langsung dari aplikasi.">
      <Bagian id="langkah" judul="Cara menghapus akun">
        <ol className="list-decimal space-y-2 pl-5 marker:font-bold marker:text-primary-deep">
          <li>Buka aplikasi EcoWin (Android) atau web nasabah, lalu masuk dengan akun Google Anda.</li>
          <li>Buka menu <strong>Profil</strong> dan gulir ke bawah.</li>
          <li>Ketuk <strong>Hapus akun saya</strong>.</li>
          <li>Ketik <strong>HAPUS</strong> untuk mengonfirmasi, lalu ketuk <strong>Hapus akun saya</strong> sekali lagi.</li>
        </ol>
        <p>Setelah berhasil, Anda otomatis keluar dan tidak dapat masuk dengan akun itu lagi.</p>
      </Bagian>

      <Bagian id="syarat" judul="Syarat penghapusan">
        <Butir>
          <Item>Saldo Anda harus <strong>Rp 0</strong>. Ajukan penarikan lebih dulu bila masih ada saldo.</Item>
          <Item>Tidak ada <strong>penarikan saldo</strong> atau <strong>aktivitas organik</strong> yang masih menunggu keputusan petugas.</Item>
        </Butir>
        <p>Bila syarat belum terpenuhi, aplikasi menampilkan alasannya dan akun Anda tidak berubah.</p>
      </Bagian>

      <Bagian id="data" judul="Data apa yang terhapus">
        <Butir>
          <Item><strong>Dihapus permanen:</strong> nama, email, nomor HP, alamat, NISN/NIK dan foto identitas (bila ada), foto profil, tautan akun Google, token login, dan token notifikasi. Kartu QR lama tidak berlaku lagi.</Item>
          <Item><strong>Tetap tersimpan tanpa identitas:</strong> riwayat transaksi dan log audit, hanya untuk pembukuan Bank Sampah. Riwayat itu tampil sebagai &ldquo;Nasabah dihapus&rdquo; dan tidak lagi terhubung dengan nama atau email Anda.</Item>
        </Butir>
        <p>Penghapusan <strong>tidak dapat dibatalkan</strong>. Untuk memakai EcoWin lagi, Anda perlu mendaftar ulang sebagai nasabah baru.</p>
      </Bagian>

      <Bagian id="bantuan" judul="Tidak bisa membuka aplikasi?">
        <p>Kirim email ke <a href={`mailto:${KONTAK_EMAIL}`}>{KONTAK_EMAIL}</a> dari alamat email Google yang Anda pakai untuk EcoWin, dan sertakan nama serta nomor nasabah Anda. Kami memprosesnya paling lambat 30 hari dengan aturan yang sama (saldo harus Rp 0).</p>
        <p>Akun petugas atau admin dihapus oleh pengelola Bank Sampah; hubungi pengelola Anda.</p>
        <p>Selengkapnya tentang data Anda ada di <Link to="/privasi">Kebijakan Privasi</Link>.</p>
      </Bagian>
    </PublikLayout>
  );
}
