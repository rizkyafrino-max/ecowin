// Status selalu tampil sebagai teks + warna (tidak hanya warna) agar mudah dibaca semua pengguna.
const MAP = {
  pending: ['Menunggu', 'bg-warn-tint text-warn'],
  approved: ['Disetujui', 'bg-info-tint text-info'],
  rejected: ['Ditolak', 'bg-danger-tint text-danger'],
  completed: ['Selesai', 'bg-tint text-primary'],
  aktif: ['Aktif', 'bg-tint text-primary'],
  diproses: ['Diproses', 'bg-info-tint text-info'],
  selesai: ['Selesai', 'bg-tint text-primary'],
  belum_panen: ['Belum dipanen', 'bg-canvas text-muted'],
  siap_panen: ['Siap panen', 'bg-warn-tint text-warn'],
  sudah_panen: ['Sudah dipanen', 'bg-tint text-primary'],
};

export default function StatusBadge({ status, label }) {
  const [text, tone] = MAP[status] ?? [label ?? String(status ?? '-'), 'bg-canvas text-muted'];
  return <span className={`inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ${tone}`}>{label ?? text}</span>;
}
