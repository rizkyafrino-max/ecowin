import { Link } from 'react-router-dom';
import PublikLayout, { Bagian, Butir, DaftarIsi, Item, Pengembang, Ringkasan, Tabel, TautanLuar } from '../components/PublikLayout';
import { KEBIJAKAN_BERLAKU, KONTAK_EMAIL } from '../utils/kontak';

/** Urutan, id, dan judul bagian; dipakai untuk daftar isi dan judul bernomor. */
const BAGIAN = [
  ['pengelola', 'Siapa pengelola datamu'],
  ['data', 'Data yang kami kumpulkan'],
  ['izin', 'Izin yang diminta aplikasi Android'],
  ['tujuan', 'Untuk apa data dipakai'],
  ['penyimpanan', 'Penyimpanan di perangkat, token, dan cookie'],
  ['akses', 'Siapa saja yang bisa melihat datamu'],
  ['luar-negeri', 'Pemrosesan di luar Indonesia'],
  ['retensi', 'Berapa lama data disimpan'],
  ['keamanan', 'Keamanan data'],
  ['hak', 'Hak kamu'],
  ['anak', 'Pengguna di bawah umur'],
  ['perubahan', 'Kalau kebijakan ini berubah'],
  ['kontak', 'Kontak'],
];
const judul = (id) => { const i = BAGIAN.findIndex(([k]) => k === id); return `${i + 1}. ${BAGIAN[i][1]}`; };

/**
 * Kebijakan Privasi EcoWin (halaman publik, tanpa login), ditulis dengan bahasa sehari-hari yang sopan. Isinya mengikuti data
 * dan izin yang benar-benar dipakai aplikasi Android, web nasabah, dan panel petugas. Perbarui KEBIJAKAN_BERLAKU bila isinya berubah.
 */
export default function Privasi() {
  return (
    <PublikLayout judul="Kebijakan Privasi" ringkas={`Cara EcoWin mengumpulkan, memakai, menyimpan, dan menjaga datamu. Berlaku sejak ${KEBIJAKAN_BERLAKU}.`}>
      <Ringkasan judul="Singkatnya">
        <Butir>
          <Item>Kami hanya mengumpulkan data yang memang dibutuhkan untuk menjalankan Bank Sampah digital: akun Google, data nasabah, dan catatan setoran.</Item>
          <Item>Datamu <strong>tidak kami jual</strong> dan <strong>tidak dipakai untuk iklan</strong>. Aplikasi ini juga tidak memakai layanan analitik atau pelacak pihak ketiga.</Item>
          <Item>Lokasi hanya diambil saat kamu mengetuk &ldquo;Gunakan lokasi saya&rdquo;, tidak di latar belakang.</Item>
          <Item>Kamu bisa mengubah data dan <Link to="/hapus-akun">menghapus akun</Link> sendiri kapan saja dari menu Profil.</Item>
        </Butir>
      </Ringkasan>

      <DaftarIsi bagian={BAGIAN} />

      <Bagian id="pengelola" judul={judul('pengelola')}>
        <p>EcoWin adalah aplikasi Bank Sampah digital (Android dan web nasabah) lengkap dengan panel untuk petugas dan admin. Layanan ini dikembangkan dan dijalankan oleh <strong><Pengembang /></strong> (&ldquo;kami&rdquo;), bekerja sama dengan pengelola Bank Sampah tempat kamu terdaftar. Untuk data yang kamu berikan lewat EcoWin, kamilah yang bertanggung jawab sebagai pengendali data pribadi.</p>
        <p>Dengan mendaftar atau masuk ke EcoWin, kamu menyatakan sudah membaca kebijakan ini. Kalau belum setuju, mohon jangan memakai layanan ini dulu ya.</p>
      </Bagian>

      <Bagian id="data" judul={judul('data')}>
        <Tabel label="Data yang dikumpulkan" kolom={['Data', 'Isi', 'Sifat', 'Dipakai untuk']} baris={[
          ['Akun Google', 'Nama, alamat email, foto profil, dan pengenal akun Google. Kami tidak pernah menerima kata sandi Google-mu.', 'Wajib', 'Masuk dan mengenali akunmu'],
          ['Data nasabah', 'Nama, nomor HP, alamat (RT/RW), jenis kelamin, dan Bank Sampah pilihanmu. Nomor nasabah dan kartu QR dibuat otomatis.', 'Wajib, kecuali jenis kelamin', 'Pendaftaran, verifikasi petugas, dan layanan setoran'],
          ['Identitas tambahan', 'NISN atau NIK dan foto KTP/KK, dicatat petugas kalau diperlukan. Disimpan secara privat dan tidak tampil di aplikasi nasabah.', 'Opsional', 'Verifikasi nasabah'],
          ['Transaksi', 'Setoran sampah, saldo, penarikan, dan aktivitas organik.', 'Otomatis saat layanan dipakai', 'Pembukuan dan riwayatmu'],
          ['Foto', 'Foto profil, foto bukti aktivitas Biopori, dan foto pada laporan bug. Informasi yang tersemat di foto (misalnya lokasi) dibuang saat diproses.', 'Opsional', 'Tampilan profil, verifikasi aktivitas, dan perbaikan aplikasi'],
          ['Lokasi', 'Koordinat titik Biopori yang kamu ajukan, hanya saat kamu mengetuk tombol lokasi.', 'Opsional', 'Menampilkan titik Biopori di peta'],
          ['Laporan bug', 'Cerita masalah dan foto bukti yang kamu kirim.', 'Opsional', 'Memperbaiki aplikasi'],
          ['Token notifikasi', 'Pengenal perangkat untuk layanan notifikasi (FCM), kalau notifikasi diaktifkan.', 'Opsional', 'Mengirim pengingat dan pengumuman'],
          ['Data teknis', 'Alamat IP serta jenis perangkat atau browser pada log audit.', 'Otomatis', 'Keamanan dan penelusuran perubahan data'],
        ]} />
        <p>Kami tidak mengambil kontak di ponselmu, pesan, riwayat penjelajahan, atau data keuangan seperti nomor kartu dan rekening.</p>
      </Bagian>

      <Bagian id="izin" judul={judul('izin')}>
        <Tabel label="Izin aplikasi Android" kolom={['Izin', 'Untuk apa', 'Sifat']} baris={[
          ['Internet', 'Terhubung ke server EcoWin.', 'Wajib'],
          ['Notifikasi', 'Menampilkan pengingat jadwal dan pengumuman (Android 13 ke atas).', 'Opsional; boleh ditolak atau dimatikan di Pengaturan'],
          ['Lokasi (perkiraan dan tepat)', 'Mengisi koordinat saat kamu mengetuk “Gunakan lokasi saya” untuk mengajukan titik Biopori.', 'Opsional; hanya saat tombol diketuk, tidak di latar belakang'],
        ]} />
        <p>EcoWin <strong>tidak meminta izin kamera maupun akses ke seluruh galeri</strong>. Fotonya dipilih lewat pemilih foto bawaan Android atau diambil dengan aplikasi kamera di perangkatmu.</p>
      </Bagian>

      <Bagian id="tujuan" judul={judul('tujuan')}>
        <p>Kami memakai datamu hanya untuk:</p>
        <Butir>
          <Item>menjalankan layanan Bank Sampah: mendaftarkan dan memverifikasi nasabah, mencatat setoran, menghitung saldo, memproses penarikan, serta mencatat aktivitas organik dan titik Biopori;</Item>
          <Item>menjaga keamanan akun dan mencegah penyalahgunaan, termasuk menelusuri perubahan data lewat log audit;</Item>
          <Item>mengirim pengingat jadwal pengumpulan dan pengumuman yang berkaitan dengan layanan;</Item>
          <Item>menyusun laporan pembukuan Bank Sampah dan memperbaiki aplikasi berdasarkan laporan bug.</Item>
        </Butir>
        <p>Dasar kami memproses data adalah <strong>persetujuanmu</strong> saat mendaftar dan memakai layanan, <strong>pelaksanaan layanan</strong> yang kamu minta, <strong>kepentingan yang sah</strong> untuk keamanan, serta <strong>kewajiban pembukuan</strong> Bank Sampah. Kami tidak menjual data, tidak memakainya untuk iklan, dan tidak membuat keputusan otomatis yang berdampak hukum padamu.</p>
      </Bagian>

      <Bagian id="penyimpanan" judul={judul('penyimpanan')}>
        <Butir>
          <Item><strong>Aplikasi Android:</strong> token login disimpan terenkripsi di perangkatmu dan tidak ikut dicadangkan ke cloud. Kalau kamu keluar atau menghapus data aplikasi, token itu ikut terhapus.</Item>
          <Item><strong>Web nasabah:</strong> token login disimpan di penyimpanan lokal browser (localStorage) supaya kamu tetap masuk. Menekan Keluar atau menghapus data situs akan menghapusnya.</Item>
          <Item><strong>Cookie:</strong> halaman masuk dan pendaftaran serta panel petugas memakai cookie sesi yang dibutuhkan agar kamu tetap masuk dan terlindungi dari pemalsuan permintaan. Kami tidak memakai cookie iklan atau pelacak.</Item>
          <Item><strong>Lama sesi:</strong> token akses berlaku sekitar 1 hari dan bisa diperbarui sampai 60 hari. Tombol Keluar langsung mencabutnya.</Item>
        </Butir>
      </Bagian>

      <Bagian id="akses" judul={judul('akses')}>
        <Butir>
          <Item><strong>Petugas dan admin Bank Sampahmu</strong> bisa melihat data nasabah di bank mereka supaya layanan bisa berjalan. Setiap perubahan data oleh mereka tercatat di log audit.</Item>
          <Item><strong>Pengguna lain:</strong> titik Biopori yang sudah disetujui petugas tampil di peta untuk nasabah di Bank Sampah yang sama (nama lokasi, koordinat, dan keterangan), <strong>tanpa nama atau kontak pengaju</strong>. Pengajuan yang belum disetujui hanya terlihat olehmu dan petugas.</Item>
          <Item><strong>Penyedia layanan (pemroses data):</strong> pihak yang membantu kami menjalankan EcoWin, lihat tabel di bawah. Mereka hanya memproses data sesuai fungsinya.</Item>
        </Butir>
        <Tabel label="Pihak ketiga" kolom={['Pihak', 'Peran', 'Data yang terlibat', 'Kebijakan mereka']} baris={[
          ['Google (Sign-In)', 'Masuk dengan akun Google', 'Nama, email, foto, dan pengenal akun', <TautanLuar key="g" href="https://policies.google.com/privacy">policies.google.com/privacy</TautanLuar>],
          ['Google (Firebase Cloud Messaging)', 'Mengantar notifikasi ke perangkat', 'Token perangkat dan isi notifikasi', <TautanLuar key="f" href="https://firebase.google.com/support/privacy">firebase.google.com/support/privacy</TautanLuar>],
          ['OpenStreetMap', 'Gambar peta pada fitur Biopori', 'Alamat IP perangkat dan area peta yang dibuka', <TautanLuar key="o" href="https://osmfoundation.org/wiki/Privacy_Policy">osmfoundation.org/wiki/Privacy_Policy</TautanLuar>],
          ['Penyedia hosting', 'Menyimpan server dan database EcoWin', 'Seluruh data layanan (tersimpan di server)', 'Mengikuti ketentuan penyedia hosting'],
        ]} />
        <p>Di luar itu, data hanya dibuka kalau hukum mewajibkannya atau ada permintaan resmi dari pihak berwenang.</p>
      </Bagian>

      <Bagian id="luar-negeri" judul={judul('luar-negeri')}>
        <p>Penyedia pihak ketiga di atas, seperti Google, bisa saja memproses data di luar Indonesia. Kami hanya memakai penyedia yang punya kebijakan perlindungan data sendiri, dan hanya membagikan data yang memang dibutuhkan untuk fungsinya.</p>
      </Bagian>

      <Bagian id="retensi" judul={judul('retensi')}>
        <Tabel label="Lama penyimpanan" kolom={['Jenis data', 'Lama disimpan']} baris={[
          ['Data pribadi akun', 'Selama akunmu aktif. Dihapus permanen saat kamu menghapus akun.'],
          ['Riwayat transaksi dan aktivitas organik', 'Tetap disimpan tanpa identitas setelah akun dihapus (tampil sebagai "Nasabah dihapus"), karena dibutuhkan untuk pembukuan Bank Sampah.'],
          ['Laporan bug dan foto buktinya', 'Disimpan untuk memperbaiki aplikasi. Setelah akunmu dihapus, laporan itu tidak lagi terhubung ke nama atau emailmu.'],
          ['Log audit (termasuk alamat IP dan perangkat)', 'Disimpan untuk keamanan dan penelusuran. Setelah akunmu dihapus, log itu tidak lagi terhubung ke nama atau emailmu.'],
          ['Cadangan server', 'Cadangan harian disimpan 14 hari lalu terhapus otomatis. Data yang sudah kamu hapus mungkin masih ada di cadangan sampai 14 hari.'],
          ['Token login', 'Sampai 60 hari, atau sampai kamu keluar atau menghapus akun.'],
        ]} />
      </Bagian>

      <Bagian id="keamanan" judul={judul('keamanan')}>
        <Butir>
          <Item>Semua komunikasi memakai HTTPS.</Item>
          <Item>Akses dibatasi sesuai peran (nasabah, petugas, admin). Petugas hanya melihat data Bank Sampah yang menjadi tanggung jawabnya.</Item>
          <Item>Berkas pribadi (foto KTP/KK dan foto bukti) disimpan di penyimpanan privat dan hanya bisa dibuka oleh pihak yang berhak.</Item>
          <Item>Percobaan masuk dan unggah dibatasi untuk mencegah penyalahgunaan, dan perubahan data penting tercatat di log audit.</Item>
          <Item>Cadangan disimpan di server dengan izin akses terbatas.</Item>
        </Butir>
        <p>Tidak ada sistem yang benar-benar kebal. Kalau suatu saat terjadi kebocoran data yang berpotensi merugikanmu, kami akan memberi tahu pengguna yang terdampak dan pihak berwenang sesuai aturan yang berlaku.</p>
      </Bagian>

      <Bagian id="hak" judul={judul('hak')}>
        <p>Sesuai aturan perlindungan data pribadi di Indonesia (UU No. 27 Tahun 2022), kamu berhak untuk:</p>
        <Butir>
          <Item><strong>Melihat dan memperbaiki</strong> nama, nomor HP, alamat, jenis kelamin, dan foto langsung di menu Profil.</Item>
          <Item><strong>Menghapus</strong> datamu dengan menghapus akun dari menu Profil (&ldquo;Hapus akun saya&rdquo;). Langkah lengkapnya ada di halaman <Link to="/hapus-akun">Hapus Akun</Link>.</Item>
          <Item><strong>Menarik persetujuan</strong> kapan saja. Kalau persetujuan ditarik, layanan tidak bisa dilanjutkan dan akunmu dihapus.</Item>
          <Item><strong>Meminta salinan, membatasi, atau keberatan</strong> atas pemrosesan tertentu dengan menghubungi kami.</Item>
        </Butir>
        <p>Kirim permintaanmu ke <a href={`mailto:${KONTAK_EMAIL}`}>{KONTAK_EMAIL}</a> dari email Google yang kamu pakai di EcoWin. Kami akan menanggapi paling lambat 30 hari. Kamu juga berhak mengadu ke lembaga pelindungan data pribadi yang berwenang kalau merasa hakmu dilanggar.</p>
      </Bagian>

      <Bagian id="anak" judul={judul('anak')}>
        <p>EcoWin bisa dipakai pelajar. Kalau kamu belum berusia 18 tahun, sebaiknya mendaftar dengan sepengetahuan dan persetujuan orang tua atau wali, lewat Bank Sampah sekolah atau lingkunganmu. Orang tua atau wali juga bisa meminta data anak dihapus lewat kontak di bawah. Kami tidak sengaja mengumpulkan data anak di luar keperluan layanan.</p>
      </Bagian>

      <Bagian id="perubahan" judul={judul('perubahan')}>
        <p>Kebijakan ini bisa kami perbarui kalau layanan atau aturan hukum berubah. Versi terbaru selalu tampil di halaman ini beserta tanggal berlakunya, dan untuk perubahan penting kami akan memberi tahu lewat aplikasi atau Bank Sampahmu. Versi ini berlaku sejak <strong>{KEBIJAKAN_BERLAKU}</strong>. Kebijakan ini tunduk pada hukum Republik Indonesia.</p>
      </Bagian>

      <Bagian id="kontak" judul={judul('kontak')}>
        <p>Ada pertanyaan, permintaan soal datamu, atau pengaduan privasi? Hubungi kami:</p>
        <Butir>
          <Item><strong>Pengembang dan pengendali data:</strong> <Pengembang /></Item>
          <Item><strong>Email:</strong> <a href={`mailto:${KONTAK_EMAIL}`}>{KONTAK_EMAIL}</a></Item>
        </Butir>
      </Bagian>
    </PublikLayout>
  );
}
