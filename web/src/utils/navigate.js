import { API_BASE } from '../api/client';

// Halaman login & pendaftaran adalah SATU halaman milik Laravel (dipakai semua role).
const origin = new URL(API_BASE, window.location.origin).origin;
export const LOGIN_URL = `${origin}/masuk`;
export const REGISTER_URL = `${origin}/daftar`;

/** Pindah ke halaman Laravel (dibungkus agar mudah di-mock pada tes). */
export const goExternal = (url) => window.location.assign(url);
