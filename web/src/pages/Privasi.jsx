import { Link } from 'react-router-dom';
import PublikLayout, { Bagian, Butir, DaftarIsi, Item, Pengembang, Ringkasan, Tabel, TautanLuar } from '../components/PublikLayout';
import { KEBIJAKAN_BERLAKU, KONTAK_EMAIL } from '../utils/kontak';

/** Urutan, id, dan judul bagian; dipakai untuk daftar isi dan judul bernomor. */
const BAGIAN = [
  ['pengelola', 'Siapa pengelola data Anda'],
  ['data', 'Data yang kami kumpulkan'],
  ['izin', 'Izin yang diminta aplikasi Android'],
  ['tujuan', 'Tujuan dan dasar penggunaan data'],
  ['penyimpanan', 'Penyimpanan di perangkat, token, dan cookie'],
  ['akses', 'Siapa yang dapat mengakses data Anda'],
  ['luar-negeri', 'Pemrosesan di luar Indonesia'],
  ['retensi', 'Lama penyimpanan data'],
  ['keamanan', 'Keamanan data'],
  ['hak', 'Hak Anda'],
  ['anak', 'Pengguna di bawah umur'],
  ['perubahan', 'Perubahan kebijakan ini'],
  ['kontak', 'Kontak'],
];
const judul = (id) => { const i = BAGIAN.findIndex(([k]) => k === id); return `${i + 1}. ${BAGIAN[i][1]}`; };

/**
 * Kebijakan Privasi EcoWin (halaman publik, tanpa login). Isinya mengikuti data dan izin yang benar-benar dipakai
 * aplikasi Android, web nasabah, dan panel petugas. Perbarui KEBIJAKAN_BERLAKU setiap kali isinya berubah.
 */
export default function Privasi() {
  return (
    <PublikLayout judul="Kebijakan Privasi" ringkas={`Cara EcoWin mengumpulkan, memakai, menyimpan, dan melindungi data Anda. Berlaku sejak ${KEBIJAKAN_BERLAKU}.`}>
      <Ringkasan judul="Ringkasan singkat">
        <Butir>
          <Item>Kami hanya mengumpulkan data yang diperlukan untuk menjalankan Bank Sampah digital: akun Google, data nasabah, dan catatan setoran.</Item>
          <Item>Data Anda <strong>tidak dijual</strong> dan <strong>tidak dipakai untuk iklan</strong>. Aplikasi tidak memakai layanan analitik atau pelacak pihak ketiga.</Item>
          <Item>Lokasi hanya diambil saat Anda mengetuk &ldquo;Gunakan lokasi saya&rdquo;, tidak di latar belakang.</Item>
          <Item>Anda dapat mengubah data dan <Link to="/hapus-akun">menghapus akun</Link> sendiri kapan saja dari menu Profil.</Item>
        </Butir>
      </Ringkasan>

      <DaftarIsi bagian={BAGIAN} />

      <Bagian id="pengelola" judul={judul('pengelola')}>
        <p>EcoWin adalah aplikasi Bank Sampah digital (Android dan web nasabah) beserta panel untuk petugas dan admin. Layanan ini dikembangkan dan dioperasikan oleh <strong><Pengembang /></strong> (&ldquo;kami&rdquo;), bekerja sama dengan pengelola Bank Sampah tempat Anda terdaftar. Dalam kebijakan ini, kami bertindak sebagai pengendali data pribadi untuk data yang Anda berikan lewat EcoWin.</p>
        <p>Dengan mendaftar atau masuk ke EcoWin, Anda menyatakan telah membaca kebijakan ini. Bila Anda tidak setuju, mohon jangan memakai layanan.</p>
      </Bagian>

      <Bagian id="data" judul={judul('data')}>
        <Tabel label="Data yang dikumpulkan" kolom={['Data', 'Isi', 'Sifat', 'Dipakai untuk']} baris={[
          ['Akun Google', 'Nama, alamat email, foto profil, dan pengenal akun Google. Kami tidak menerima kata sandi Google Anda.', 'Wajib', 'Masuk dan mengenali akun Anda'],
          ['Data nasabah', 'Nama, nomor HP, alamat (RT/RW), jenis kelamin, dan Bank Sampah pilihan Anda. Nomor nasabah dan kartu QR dibuat otomatis.', 'Wajib, kecuali jenis kelamin', 'Pendaftaran, verifikasi petugas, dan pelayanan setoran'],
          ['Identitas tambahan', 'NISN atau NIK dan foto KTP/KK, dicatat oleh petugas bila diperlukan. Disimpan privat dan tidak tampil di aplikasi nasabah.', 'Opsional', 'Verifikasi nasabah'],
          ['Transaksi', 'Setoran sampah, saldo, penarikan, dan aktivitas organik.', 'Otomatis saat layanan dipakai', 'Pembukuan dan riwayat Anda'],
          ['Foto', 'Foto profil, foto bukti aktivitas Biopori, dan foto pada laporan bug. Informasi tersemat di foto (seperti lokasi) dibuang saat diproses.', 'Opsional', 'Tampilan profil, verifikasi aktivitas, dan perbaikan aplikasi'],
          ['Lokasi', 'Koordinat titik Biopori yang Anda ajukan, hanya saat Anda mengetuk tombol lokasi.', 'Opsional', 'Menampilkan titik Biopori di peta'],
          ['Laporan bug', 'Uraian masalah dan foto bukti yang Anda kirim.', 'Opsional', 'Memperbaiki aplikasi'],
          ['Token notifikasi', 'Pengenal perangkat untuk layanan notifikasi (FCM), bila notifikasi diaktifkan.', 'Opsional', 'Mengirim pengingat dan pengumuman'],
          ['Data teknis', 'Alamat IP serta jenis perangkat atau browser pada log audit.', 'Otomatis', 'Keamanan dan penelusuran perubahan data'],
        ]} />
        <p>Kami tidak mengumpulkan kontak di ponsel Anda, pesan, riwayat penjelajahan, atau data keuangan seperti nomor kartu atau rekening.</p>
      </Bagian>

      <Bagian id="izin" judul={judul('izin')}>
        <Tabel label="Izin aplikasi Android" kolom={['Izin', 'Untuk apa', 'Sifat']} baris={[
          ['Internet', 'Terhubung ke server EcoWin.', 'Wajib'],
          ['Notifikasi', 'Menampilkan pengingat jadwal dan pengumuman (Android 13 ke atas).', 'Opsional; dapat ditolak atau dimatikan di Pengaturan'],
          ['Lokasi (perkiraan dan tepat)', 'Mengisi koordinat saat Anda mengetuk “Gunakan lokasi saya” untuk mengajukan titik Biopori.', 'Opsional; hanya saat tombol diketuk, tidak di latar belakang'],
        ]} />
        <p>EcoWin <strong>tidak meminta izin kamera maupun akses ke seluruh galeri</strong>. Foto dipilih lewat pemilih foto bawaan Android atau diambil dengan aplikasi kamera perangkat Anda.</p>
      </Bagian>

      <Bagian id="tujuan" judul={judul('tujuan')}>
        <p>Kami memakai data Anda hanya untuk:</p>
        <Butir>
          <Item>menjalankan layanan Bank Sampah: mendaftarkan dan memverifikasi nasabah, mencatat setoran, menghitung saldo, memproses penarikan, serta mencatat aktivitas organik dan titik Biopori;</Item>
          <Item>menjaga keamanan akun dan mencegah penyalahgunaan, termasuk menelusuri perubahan data lewat log audit;</Item>
          <Item>mengirim pengingat jadwal pengumpulan dan pengumuman yang berkaitan dengan layanan;</Item>
          <Item>menyusun laporan pembukuan Bank Sampah dan memperbaiki aplikasi berdasarkan laporan bug.</Item>
        </Butir>
        <p>Dasar pemrosesannya adalah <strong>persetujuan Anda</strong> saat mendaftar dan memakai layanan, <strong>pelaksanaan layanan</strong> yang Anda minta, <strong>kepentingan sah</strong> untuk keamanan, serta <strong>kewajiban pembukuan</strong> Bank Sampah. Kami tidak menjual data, tidak memakainya untuk iklan, dan tidak melakukan pembuatan profil otomatis yang berdampak hukum pada Anda.</p>
      </Bagian>

      <Bagian id="penyimpanan" judul={judul('penyimpanan')}>
        <Butir>
          <Item><strong>Aplikasi Android:</strong> token login disimpan terenkripsi di perangkat dan tidak ikut dicadangkan ke cloud. Menghapus data aplikasi atau keluar akan menghapusnya.</Item>
          <Item><strong>Web nasabah:</strong> token login disimpan di penyimpanan lokal browser (localStorage) agar Anda tetap masuk. Menekan Keluar atau menghapus data situs akan menghapusnya.</Item>
          <Item><strong>Cookie:</strong> halaman masuk dan pendaftaran serta panel petugas memakai cookie sesi yang diperlukan agar Anda tetap masuk dan terlindungi dari pemalsuan permintaan. Kami tidak memakai cookie iklan atau pelacakan.</Item>
          <Item><strong>Lama sesi:</strong> token akses berlaku sekitar 1 hari dan dapat diperbarui hingga 60 hari; tombol Keluar mencabutnya segera.</Item>
        </Butir>
      </Bagian>

      <Bagian id="akses" judul={judul('akses')}>
        <Butir>
          <Item><strong>Petugas dan admin Bank Sampah Anda</strong> dapat melihat data nasabah di bank mereka untuk menjalankan layanan. Setiap perubahan data oleh mereka tercatat di log audit.</Item>
          <Item><strong>Pengguna lain:</strong> titik Biopori yang telah disetujui petugas tampil di peta bagi nasabah di Bank Sampah yang sama (nama lokasi, koordinat, dan deskripsi), <strong>tanpa nama atau kontak pengaju</strong>. Pengajuan yang belum disetujui hanya terlihat oleh Anda dan petugas.</Item>
          <Item><strong>Penyedia layanan (pemroses data):</strong> pihak yang membantu kami menjalankan EcoWin, lihat tabel di bawah. Mereka hanya memproses data sesuai fungsi tersebut.</Item>
        </Butir>
        <Tabel label="Pihak ketiga" kolom={['Pihak', 'Peran', 'Data yang terlibat', 'Kebijakan mereka']} baris={[
          ['Google (Sign-In)', 'Masuk dengan akun Google', 'Nama, email, foto, dan pengenal akun', <TautanLuar key="g" href="https://policies.google.com/privacy">policies.google.com/privacy</TautanLuar>],
          ['Google (Firebase Cloud Messaging)', 'Mengantar notifikasi ke perangkat', 'Token perangkat dan isi notifikasi', <TautanLuar key="f" href="https://firebase.google.com/support/privacy">firebase.google.com/support/privacy</TautanLuar>],
          ['OpenStreetMap', 'Gambar peta pada fitur Biopori', 'Alamat IP perangkat dan area peta yang dibuka', <TautanLuar key="o" href="https://osmfoundation.org/wiki/Privacy_Policy">osmfoundation.org/wiki/Privacy_Policy</TautanLuar>],
          ['Penyedia hosting', 'Menyimpan server dan database EcoWin', 'Seluruh data layanan (tersimpan di server)', 'Mengikuti ketentuan penyedia hosting'],
        ]} />
        <p>Di luar itu, data hanya dibuka bila diwajibkan oleh hukum atau permintaan resmi pihak berwenang.</p>
      </Bagian>

      <Bagian id="luar-negeri" judul={judul('luar-negeri')}>
        <p>Penyedia pihak ketiga di atas, seperti Google, dapat memproses data di luar Indonesia. Kami hanya memakai penyedia yang memiliki kebijakan perlindungan data sendiri dan hanya membagikan data yang diperlukan untuk fungsinya.</p>
      </Bagian>

      <Bagian id="retensi" judul={judul('retensi')}>
        <Tabel label="Lama penyimpanan" kolom={['Jenis data', 'Lama disimpan']} baris={[
          ['Data pribadi akun', 'Selama akun aktif. Dihapus permanen saat Anda menghapus akun.'],
          ['Riwayat transaksi dan aktivitas organik', 'Tetap disimpan tanpa identitas setelah akun dihapus (tampil sebagai "Nasabah dihapus"), karena diperlukan untuk pembukuan Bank Sampah.'],
          ['Laporan bug dan foto buktinya', 'Disimpan untuk perbaikan aplikasi. Setelah akun dihapus, laporan tidak lagi terhubung ke nama atau email Anda.'],
          ['Log audit (termasuk alamat IP dan perangkat)', 'Disimpan untuk keamanan dan penelusuran. Setelah akun dihapus, tidak lagi terhubung ke nama atau email Anda.'],
          ['Cadangan server', 'Cadangan harian disimpan 14 hari lalu dihapus otomatis. Data yang sudah Anda hapus dapat masih ada di cadangan hingga 14 hari.'],
          ['Token login', 'Hingga 60 hari, atau sampai Anda keluar atau menghapus akun.'],
        ]} />
      </Bagian>

      <Bagian id="keamanan" judul={judul('keamanan')}>
        <Butir>
          <Item>Seluruh komunikasi memakai HTTPS.</Item>
          <Item>Akses dibatasi menurut peran (nasabah, petugas, admin); petugas hanya melihat data Bank Sampah yang menjadi tanggung jawabnya.</Item>
          <Item>Berkas pribadi (foto KTP/KK dan foto bukti) disimpan di penyimpanan privat dan hanya dapat dibuka oleh pihak yang berhak.</Item>
          <Item>Percobaan masuk dan unggah dibatasi untuk mencegah penyalahgunaan; perubahan data penting tercatat di log audit.</Item>
          <Item>Cadangan disimpan di server dengan izin akses terbatas.</Item>
        </Butir>
        <p>Tidak ada sistem yang sepenuhnya kebal. Bila terjadi kebocoran data yang berpotensi merugikan Anda, kami akan memberi tahu pengguna terdampak dan pihak berwenang sesuai ketentuan hukum yang berlaku.</p>
      </Bagian>

      <Bagian id="hak" judul={judul('hak')}>
        <p>Sesuai ketentuan perlindungan data pribadi yang berlaku di Indonesia (UU No. 27 Tahun 2022), Anda berhak untuk:</p>
        <Butir>
          <Item><strong>Mengakses dan memperbaiki</strong> nama, nomor HP, alamat, jenis kelamin, dan foto langsung di menu Profil.</Item>
          <Item><strong>Menghapus</strong> data Anda dengan menghapus akun dari menu Profil (&ldquo;Hapus akun saya&rdquo;). Langkah lengkap ada di halaman <Link to="/hapus-akun">Hapus Akun</Link>.</Item>
          <Item><strong>Menarik persetujuan</strong> kapan saja. Dengan menarik persetujuan, layanan tidak dapat dilanjutkan dan akun dihapus.</Item>
          <Item><strong>Meminta salinan, membatasi, atau berkeberatan</strong> atas pemrosesan tertentu dengan menghubungi kami.</Item>
        </Butir>
        <p>Kirim permintaan ke <a href={`mailto:${KONTAK_EMAIL}`}>{KONTAK_EMAIL}</a> dari email Google yang Anda pakai di EcoWin. Kami menanggapi paling lambat 30 hari. Anda juga berhak mengadukan ke lembaga pelindungan data pribadi yang berwenang bila merasa hak Anda dilanggar.</p>
      </Bagian>

      <Bagian id="anak" judul={judul('anak')}>
        <p>EcoWin dapat dipakai pelajar. Bagi pengguna di bawah 18 tahun, pendaftaran sebaiknya dilakukan dengan sepengetahuan dan persetujuan orang tua atau wali, melalui Bank Sampah sekolah atau lingkungan masing-masing. Orang tua atau wali dapat meminta penghapusan data anak lewat kontak di bawah. Kami tidak sengaja mengumpulkan data anak di luar keperluan layanan.</p>
      </Bagian>

      <Bagian id="perubahan" judul={judul('perubahan')}>
        <p>Kami dapat memperbarui kebijakan ini bila layanan atau ketentuan hukum berubah. Versi terbaru selalu tampil di halaman ini beserta tanggal berlakunya; untuk perubahan penting, kami akan memberi pemberitahuan di aplikasi atau lewat Bank Sampah Anda. Versi ini berlaku sejak <strong>{KEBIJAKAN_BERLAKU}</strong>. Kebijakan ini tunduk pada hukum Republik Indonesia.</p>
      </Bagian>

      <Bagian id="kontak" judul={judul('kontak')}>
        <p>Pertanyaan, permintaan terkait data, atau pengaduan privasi:</p>
        <Butir>
          <Item><strong>Pengembang dan pengendali data:</strong> <Pengembang /></Item>
          <Item><strong>Email:</strong> <a href={`mailto:${KONTAK_EMAIL}`}>{KONTAK_EMAIL}</a></Item>
        </Butir>
      </Bagian>
    </PublikLayout>
  );
}
