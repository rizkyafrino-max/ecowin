/**
 * Nasabah yang mendaftar sendiri berstatus "pending" sampai petugas Bank Sampah memverifikasinya. Selama itu halaman data dan
 * layanan disensor (diganti layar terkunci) dan server menolak permintaannya (403, code "belum_diverifikasi").
 * Status kosong diperlakukan sebagai belum diverifikasi: lebih aman terkunci daripada terbuka.
 */
export const akunTerkunci = (user) => user?.role === 'nasabah' && user?.nasabah?.status_verifikasi !== 'verified';
