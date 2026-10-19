# Nada notifikasi EcoWin

`buat-nada.cjs` mensintesis tiga nada (WAV mono 16-bit, 32 kHz) tanpa sampel pihak ketiga, sehingga bebas hak cipta:

- `nada_ecowin` — tiga denting naik seperti dompet digital (bawaan)
- `nada_daun` — dua nada kayu lembut
- `nada_koin` — dua "ting" cepat

```bash
node tools/nada-notifikasi/buat-nada.cjs android-app/app/src/main/res/raw
```

Ubah frekuensi/urutan nada di berkas itu lalu jalankan ulang. Nama berkas harus huruf kecil dan garis bawah (aturan resource Android).
Pilihan nada ada di layar Notifikasi aplikasi (setiap nada memakai kanal Android sendiri karena suara kanal tidak bisa diubah setelah dibuat).
