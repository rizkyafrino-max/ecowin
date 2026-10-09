import { api } from './client';

// Satu tempat untuk semua endpoint — Web dan Android memakai kontrak API yang sama.
export const EcoApi = {
  me: () => api('/auth/me'),
  logout: () => api('/auth/logout', { method: 'POST' }),
  updateProfile: (data) => api('/profile', { method: 'PATCH', body: data }),

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
  buatBiopori: (formData) => api('/organik/biopori', { method: 'POST', body: formData }),

  penarikan: (page = 1) => api('/penarikan', { query: { page } }),
  ajukanPenarikan: (data) => api('/penarikan', { method: 'POST', body: data }),
};
