import { createContext, useCallback, useContext, useEffect, useMemo, useRef, useState } from 'react';
import { EcoApi } from '../api/endpoints';

const NotifContext = createContext({ belumDibaca: 0, refresh: () => {}, setBelumDibaca: () => {} });

/**
 * Jumlah notifikasi belum dibaca untuk bel di bilah atas. Diperbarui tiap 60 detik saat tab terlihat, saat tab
 * kembali aktif, dan setelah pengguna membaca notifikasi. Kegagalan jaringan diabaikan (bel tetap menampilkan nilai terakhir).
 */
export function NotifProvider({ children }) {
  const [belumDibaca, setBelumDibaca] = useState(0);
  const hidup = useRef(true);

  const refresh = useCallback(async () => {
    try {
      const r = await EcoApi.jumlahNotif();
      if (hidup.current && Number.isFinite(r?.belum_dibaca)) setBelumDibaca(r.belum_dibaca);
    } catch { /* abaikan */ }
  }, []);

  useEffect(() => {
    hidup.current = true;
    refresh();
    const t = setInterval(() => { if (document.visibilityState === 'visible') refresh(); }, 60_000);
    const onVisible = () => { if (document.visibilityState === 'visible') refresh(); };
    document.addEventListener('visibilitychange', onVisible);
    return () => { hidup.current = false; clearInterval(t); document.removeEventListener('visibilitychange', onVisible); };
  }, [refresh]);

  const nilai = useMemo(() => ({ belumDibaca, refresh, setBelumDibaca }), [belumDibaca, refresh]);
  return <NotifContext.Provider value={nilai}>{children}</NotifContext.Provider>;
}

export const useNotif = () => useContext(NotifContext);
