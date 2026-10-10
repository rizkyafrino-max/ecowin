/** Judul halaman + aksi. Di desktop judul sudah ada di TopBar, jadi hanya tampil di HP. */
export default function PageHeader({ title, subtitle, action }) {
  return (
    <div className="mb-5 flex items-end justify-between gap-3">
      <div>
        <h2 className="text-2xl font-extrabold md:hidden">{title}</h2>
        {subtitle && <p className="mt-0.5 text-sm text-muted md:mt-0">{subtitle}</p>}
      </div>
      {action}
    </div>
  );
}
