import { ImageOff } from 'lucide-react';
import { useEffect, useState } from 'react';
import { EcoApi } from '../api/endpoints';

/** Foto bukti Biopori bersifat privat: diambil dengan Bearer token lalu ditampilkan sebagai blob. */
export default function AuthImage({ id, alt, className = '' }) {
  const [src, setSrc] = useState(null);
  const [failed, setFailed] = useState(false);

  useEffect(() => {
    let url;
    let alive = true;
    EcoApi.bioporiFoto(id)
      .then((res) => res.blob())
      .then((blob) => { if (alive) { url = URL.createObjectURL(blob); setSrc(url); } })
      .catch(() => alive && setFailed(true));
    return () => { alive = false; if (url) URL.revokeObjectURL(url); };
  }, [id]);

  if (failed) return <div className={`flex items-center justify-center bg-canvas text-muted ${className}`}><ImageOff className="h-6 w-6" aria-label="Foto tidak tersedia" /></div>;
  if (!src) return <div className={`ew-skeleton ${className}`} />;
  return <img src={src} alt={alt} className={`object-cover ${className}`} />;
}
