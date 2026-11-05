import { Link } from 'react-router-dom';
import PublikLayout, { Bagian, Butir, Item } from '../components/PublikLayout';
import { KEBIJAKAN_BERLAKU, KONTAK_EMAIL } from '../utils/kontak';

/**
 * Kebijakan Privasi EcoWin (halaman publik, tanpa login). Isinya mengikuti data yang benar-benar dikumpulkan aplikasi
 * (Android dan web nasabah) dan panel Bank Sampah. Ubah KEBIJAKAN_BERLAKU bila isi berubah.
 */
export default function Privasi() {
  return (
    <PublikLayout judul="Kebijakan Privasi" ringkas={`EcoWin adalah aplikasi Bank Sampah digital. Berlaku sejak ${KEBIJAKAN_BERLAKU}.`}>
      <Bagian id="siapa" judul="1. Siapa kami">
        <p>EcoWin dikelola oleh pengelola Bank Sampah tempat Anda terdaftar dan dikembangkan oleh Rizky Afrino. Kebijakan ini menjelaskan data apa yang kami kumpulkan lewat aplikasi Android, web nasabah, dan panel petugas, untuk apa data itu dipakai, serta hak Anda atasnya.</p>
      </Bagian>

      <Bagian id="data" judul="2. Data yang kami kumpulkan">
        <Butir>
          <Item><strong>Akun Google:</strong> nama, alamat email, foto profil, dan pengenal akun Google Anda, hanya untuk masuk. Kami tidak pernah menerima atau menyimpan kata sandi Google Anda.</Item>
          <Item><strong>Data nasabah:</strong> nama, nomor HP, alamat (RT/RW), jenis kelamin (opsional), Bank Sampah yang dipilih, serta nomor nasabah dan kartu QR. Petugas Bank Sampah dapat mencatat tambahan seperti NISN atau NIK dan foto KTP/KK untuk verifikasi; berkas ini disimpan secara privat dan tidak tampil di aplikasi nasabah.</Item>
          <Item><strong>Foto yang Anda unggah (opsional):</strong> foto profil, foto bukti aktivitas Biopori, dan foto pada laporan bug. Metadata foto (misalnya lokasi di dalam berkas) dibuang saat diproses.</Item>
          <Item><strong>Lokasi:</strong> hanya bila Anda mengetuk &ldquo;Gunakan lokasi saya&rdquo; saat mengajukan titik Biopori. Koordinat itu disimpan sebagai titik Biopori yang Anda ajukan. Kami tidak melacak lokasi Anda di latar belakang.</Item>
          <Item><strong>Transaksi:</strong> setoran sampah, saldo, penarikan, dan aktivitas organik Anda.</Item>
          <Item><strong>Notifikasi:</strong> token perangkat (FCM) agar pengingat dan pengumuman dapat sampai, bila Anda mengaktifkan notifikasi.</Item>
          <Item><strong>Catatan keamanan:</strong> alamat IP dan jenis perangkat atau browser pada log audit, untuk mendeteksi penyalahgunaan dan menelusuri perubahan data.</Item>
        </Butir>
      </Bagian>

      <Bagian id="tujuan" judul="3. Untuk apa data dipakai">
        <Butir>
          <Item>Mengenali Anda dan menjaga akun tetap aman.</Item>
          <Item>Mencatat setoran, saldo, penarikan, dan aktivitas organik; membuat laporan Bank Sampah.</Item>
          <Item>Memverifikasi nasabah oleh petugas dan menampilkan titik Biopori di peta.</Item>
          <Item>Mengirim pengingat jadwal pengumpulan dan pengumuman.</Item>
        </Butir>
        <p>Kami <strong>tidak menjual</strong> data Anda dan tidak memakainya untuk iklan.</p>
      </Bagian>

      <Bagian id="berbagi" judul="4. Siapa yang dapat melihat data Anda">
        <Butir>
          <Item><strong>Petugas dan admin Bank Sampah Anda</strong> dapat melihat data nasabah di bank mereka untuk menjalankan layanan.</Item>
          <Item><strong>Google:</strong> untuk proses masuk dengan akun Google, dan Firebase Cloud Messaging untuk mengantar notifikasi.</Item>
          <Item><strong>OpenStreetMap:</strong> saat Anda membuka peta, perangkat Anda meminta gambar peta dari server OpenStreetMap, sehingga alamat IP perangkat terlihat oleh mereka.</Item>
          <Item><strong>Penyedia server (hosting):</strong> data disimpan di server yang kami sewa, di dalam sambungan HTTPS.</Item>
        </Butir>
        <p>Selain itu, data hanya dibuka bila diwajibkan oleh hukum.</p>
      </Bagian>

      <Bagian id="keamanan" judul="5. Keamanan">
        <p>Seluruh komunikasi memakai HTTPS. Berkas pribadi (foto KTP/KK dan foto bukti) disimpan di penyimpanan privat dan hanya dapat dibuka oleh yang berhak. Akses aplikasi memakai token yang dapat dicabut. Tidak ada sistem yang sepenuhnya kebal, namun kami membatasi akses dan memeriksa perubahan data lewat log audit.</p>
      </Bagian>

      <Bagian id="simpan" judul="6. Berapa lama data disimpan">
        <p>Data Anda disimpan selama akun aktif. Bila Anda menghapus akun, <strong>data pribadi dihapus permanen</strong> (nama, email, nomor HP, alamat, NIK/NISN, foto, dan tautan akun Google). <strong>Riwayat transaksi tetap disimpan tanpa identitas</strong> (tampil sebagai &ldquo;Nasabah dihapus&rdquo;) karena diperlukan untuk pembukuan Bank Sampah. Log audit yang tersisa tidak lagi terhubung dengan nama atau email Anda.</p>
      </Bagian>

      <Bagian id="hak" judul="7. Hak Anda">
        <Butir>
          <Item><strong>Melihat dan mengubah</strong> nama, nomor HP, alamat, jenis kelamin, dan foto langsung di menu Profil.</Item>
          <Item><strong>Menghapus akun</strong> kapan saja dari menu Profil, lalu &ldquo;Hapus akun saya&rdquo;. Langkah lengkapnya ada di halaman <Link to="/hapus-akun">Hapus Akun</Link>. Syaratnya saldo Rp 0 dan tidak ada permintaan yang masih menunggu.</Item>
          <Item><strong>Meminta bantuan</strong> bila Anda tidak dapat membuka aplikasi: kirim email ke <a href={`mailto:${KONTAK_EMAIL}`}>{KONTAK_EMAIL}</a>.</Item>
        </Butir>
      </Bagian>

      <Bagian id="anak" judul="8. Pengguna di bawah umur">
        <p>EcoWin dapat dipakai pelajar. Bagi pengguna di bawah 18 tahun, pendaftaran sebaiknya dilakukan dengan sepengetahuan orang tua atau wali dan melalui Bank Sampah sekolah atau lingkungan masing-masing.</p>
      </Bagian>

      <Bagian id="perubahan" judul="9. Perubahan kebijakan">
        <p>Bila kebijakan ini berubah, versi terbaru akan tampil di halaman ini beserta tanggal berlakunya. Berlaku sejak <strong>{KEBIJAKAN_BERLAKU}</strong>.</p>
      </Bagian>

      <Bagian id="kontak" judul="10. Kontak">
        <p>Pertanyaan tentang privasi atau data Anda: <a href={`mailto:${KONTAK_EMAIL}`}>{KONTAK_EMAIL}</a>.</p>
      </Bagian>
    </PublikLayout>
  );
}
