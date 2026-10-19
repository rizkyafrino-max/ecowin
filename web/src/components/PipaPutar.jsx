import { useEffect, useState } from 'react';

// Lembar sprite 6x6 (36 frame) pipa BioporiPrint yang berputar; dirender dari model 3D dan berulang mulus.
const KOLOM = 6;
const FRAME = 36;
const FPS = 12;

const kurangiGerakan = () => typeof window !== 'undefined' && !!window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;

/**
 * Gambar pipa BioporiPrint berputar 360°. Ringan: satu gambar WebP (~120 KB) yang digeser per frame, tanpa pustaka 3D.
 * Animasi hanya berjalan selama komponen tampil (popup terbuka) dan berhenti bila pengguna memilih "kurangi gerakan".
 */
export default function PipaPutar({ ukuran = 84, className = '' }) {
  const tinggi = Math.round((ukuran * 4) / 3); // bingkai potret 3:4
  const [f, setF] = useState(0);
  const diam = kurangiGerakan();

  useEffect(() => {
    if (diam) return undefined;
    const t = setInterval(() => setF((x) => (x + 1) % FRAME), 1000 / FPS);
    return () => clearInterval(t);
  }, [diam]);

  const kol = f % KOLOM;
  const baris = Math.floor(f / KOLOM);
  const geser = (n) => `${(n / (KOLOM - 1)) * 100}%`;

  return (
    <div
      role="img"
      aria-label="Model 3D pipa BioporiPrint yang berputar"
      className={className}
      style={{
        width: ukuran,
        height: tinggi,
        flex: 'none',
        backgroundImage: 'url(/biopori-putar.webp)',
        backgroundRepeat: 'no-repeat',
        backgroundSize: `${KOLOM * 100}% ${KOLOM * 100}%`,
        backgroundPosition: `${geser(kol)} ${geser(baris)}`,
      }}
    />
  );
}
