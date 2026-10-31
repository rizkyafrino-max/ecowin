import { api } from './client';

// Satu tempat untuk semua endpoint — Web dan Android memakai kontrak API yang sama.
export const EcoApi = {
  me: () => api('/auth/me'),
  logout: () => api('/auth/logout', { method: 'POST' }),
  updateProfile: (data) => api('/profile', { method: 'PATCH', body: data }),
  gantiFoto: (blob) => { const fd = new FormData(); fd.append('foto', blob, 'foto.jpg'); return api('/profile/foto', { method: 'POST', body: fd }); },
  hapusFoto: () => api('/profile/foto', { method: 'DELETE' }),
  kirimLaporan: (deskripsi, foto) => { const fd = new FormData(); fd.append('kategori', 'bug'); fd.append('deskripsi', deskripsi); if (foto) fd.append('foto', foto, 'bukti.jpg'); return api('/laporan-kendala', { method: 'POST', body: fd }); },

  dashboard: () => api('/dashboard/summary'),
  statistik: (bulan = 6) => api('/dashboard/statistics', { query: { bulan } }),

  saldo: () => api('/me/saldo'),
  mutasi: (page = 1) => api('/me/mutasi-saldo', { query: { page } }),
  qr: () => api('/me/qr'),

  harga: () => api('/harga'),
  transaksi: (page = 1) => api('/transaksi/anorganik', { query: { page } }),
  transaksiDetail: (id) => api(`/transaksi/anorganik/${id}`),

  organik: (page = 1) => api('/organik', { query: { page } }),

  lokasiBiopori: () => api('/organik/biopori/lokasi'),
  biopori: (page = 1, status) => api('/organik/biopori', { query: { page, status } }),
  bioporiFoto: (id) => api(`/organik/biopori/${id}/foto`, { raw: true }),
  ajukanTitik: (data) => api('/organik/biopori/lokasi', { method: 'POST', body: data }),
  buatBiopori: (formData) => api('/organik/biopori', { method: 'POST', body: formData }),

  notifikasi: () => api('/notifikasi'),
  jumlahNotif: () => api('/notifikasi/jumlah'),
  bacaNotif: (id) => api(`/notifikasi/${id}/baca`, { method: 'POST' }),
  bacaSemuaNotif: () => api('/notifikasi/baca-semua', { method: 'POST' }),
  hapusNotif: (id) => api(`/notifikasi/${id}`, { method: 'DELETE' }),
  hapusNotifSemua: (hanyaDibaca = false) => api(`/notifikasi${hanyaDibaca ? '?hanya_dibaca=1' : ''}`, { method: 'DELETE' }),

  penarikan: (page = 1) => api('/penarikan', { query: { page } }),
  ajukanPenarikan: (data) => api('/penarikan', { method: 'POST', body: data }),
};
