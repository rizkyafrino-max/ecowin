import { useCallback, useEffect, useRef, useState } from 'react';

/** Daftar berhalaman (Laravel paginate): memuat halaman 1 lalu "muat lebih banyak". */
export function usePaged(fetchPage, deps = []) {
  const [state, setState] = useState({ items: [], page: 0, last: 1, loading: true, loadingMore: false, error: null });
  const fnRef = useRef(fetchPage);
  fnRef.current = fetchPage;
  const alive = useRef(true);

  const load = useCallback(async (page, replace) => {
    setState((s) => ({ ...s, loading: replace, loadingMore: !replace, error: null }));
    try {
      const res = await fnRef.current(page);
      if (!alive.current) return;
      setState((s) => ({
        items: replace ? res.data : [...s.items, ...res.data],
        page: res.meta?.current_page ?? page,
        last: res.meta?.last_page ?? page,
        loading: false, loadingMore: false, error: null,
      }));
    } catch (error) {
      if (alive.current) setState((s) => ({ ...s, loading: false, loadingMore: false, error }));
    }
  }, []);

  useEffect(() => {
    alive.current = true;
    load(1, true);
    return () => { alive.current = false; };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, deps);

  return {
    ...state,
    hasMore: state.page < state.last,
    reload: () => load(1, true),
    more: () => load(state.page + 1, false),
  };
}
