import Button from './Button';
import Card from './Card';
import { EmptyState, ErrorState, LoadingState } from './States';

/** Kartu daftar dengan state loading / error / kosong / muat lebih banyak (dipakai semua halaman riwayat). */
export default function PagedList({ list, empty, renderItem }) {
  const { items, loading, error, hasMore, loadingMore, reload, more } = list;
  return (
    <Card className="!p-2 sm:!p-3">
      {loading ? <div className="p-3"><LoadingState rows={4} /></div>
        : error ? <ErrorState error={error} onRetry={reload} />
          : items.length === 0 ? <EmptyState {...empty} />
            : (
              <>
                <ul className="divide-y divide-line px-3">
                  {items.map((item) => <li key={item.id}>{renderItem(item)}</li>)}
                </ul>
                {hasMore && <div className="p-3 text-center"><Button variant="soft" size="sm" loading={loadingMore} onClick={more}>Muat lebih banyak</Button></div>}
              </>
            )}
    </Card>
  );
}
