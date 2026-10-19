# Render model 3D menjadi gambar putar (sprite)

Membuat lembar sprite WebP transparan (pipa BioporiPrint berputar 360°) dari berkas STL, dipakai di popup peta
(web, panel Admin/Petugas, Android). Tanpa pustaka 3D di aplikasi: hanya satu gambar ±120 KB.

## Pakai
```bash
cd tools/render-model3d
npm install                      # three + puppeteer-core
# Windows (Chrome terpasang di lokasi bawaan):
node render.cjs ../../model3d.stl ../../backend/public/images/biopori-putar.webp 36 144 192 6 90
```
Argumen: `<stl> <keluaran.webp> [jumlah-frame=36] [lebar=144] [tinggi=192] [kolom=6] [total-putaran-derajat=90]`.
Atur jalur Chrome dengan variabel `CHROME_PATH` bila berbeda.

Lalu salin hasilnya ke tiga tempat:
- `backend/public/images/biopori-putar.webp` (panel Admin/Petugas)
- `web/public/biopori-putar.webp` (web nasabah)
- `android-app/app/src/main/res/drawable-nodpi/biopori_putar.webp` (Android)

## Catatan penting
- **Total putaran harus kelipatan periode pola** pada model (pola lubang pipa ini berulang tiap **30°**; 90° = 3 periode)
  agar animasi berulang mulus. Pipa ini simetris melingkar, jadi putaran 360° "sungguhan" tampak diam; geser halus
  dalam satu periode memberi kesan berputar yang jelas.
- Bingkai potret 3:4 (144×192); lembar = `kolom × lebar` kali `baris × tinggi`, dengan `kolom × baris` ≥ jumlah frame. Kode peta (web `PipaPutar.jsx`,
  panel `peta-biopori.blade.php`, Android `PipaPutarView.kt`) mengasumsikan **6×6 = 36 frame**; ubah konstanta
  `KOLOM`/`FRAME` di ketiganya bila bentuk lembar berubah.
- **Lisensi model:** berkas STL berasal dari Printables (pihak ketiga). Pastikan lisensinya mengizinkan penggunaan ini dan
  cantumkan kredit bila diwajibkan. Berkas STL tidak disimpan di git (lihat `.gitignore`) sampai lisensi dipastikan.
