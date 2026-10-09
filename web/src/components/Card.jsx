import { rupiah } from '../utils/format';

export default function Card({ as: Tag = 'section', className = '', children, ...props }) {
  return (
    <Tag className={`rounded-card border border-line bg-surface p-5 shadow-soft ${className}`} {...props}>{children}</Tag>
  );
}

/** Kartu statistik kecil: ikon, label, angka. */
export function StatCard({ icon: Icon, label, value, hint, tone = 'primary' }) {
  const tones = { primary: 'bg-tint text-primary', info: 'bg-info-tint text-info', warn: 'bg-warn-tint text-warn' };
  return (
    <Card className="flex flex-col gap-3 !p-4 sm:flex-row sm:items-start sm:gap-4 sm:!p-5">
      <span className={`flex h-10 w-10 flex-none items-center justify-center rounded-2xl sm:h-11 sm:w-11 ${tones[tone]}`}>
        <Icon className="h-5 w-5" aria-hidden />
      </span>
      <div className="min-w-0">
        <p className="text-[13px] leading-tight text-muted sm:text-sm">{label}</p>
        <p className="mt-1 break-words text-xl font-bold leading-tight tabular-nums">{value}</p>
        {hint && <p className="mt-1 text-xs leading-snug text-muted">{hint}</p>}
      </div>
    </Card>
  );
}

/** Hero card saldo: angka paling menonjol di dashboard. */
export function BalanceCard({ saldo, tersedia, ditahan, actions }) {
  return (
    <section className="relative overflow-hidden rounded-[24px] bg-primary p-6 text-white shadow-soft sm:p-7">
      <span className="pointer-events-none absolute -right-16 -top-20 h-64 w-64 rounded-full bg-white/10" aria-hidden />
      <span className="pointer-events-none absolute -bottom-24 right-24 h-56 w-56 rounded-full bg-white/[0.07]" aria-hidden />
      <div className="relative">
        <p className="text-sm font-medium text-white/80">Saldo EcoWin</p>
        <p className="mt-1 text-[2rem] font-extrabold leading-tight tabular-nums sm:text-4xl">{rupiah(saldo)}</p>
        {(tersedia !== undefined || ditahan > 0) && (
          <p className="mt-2 text-xs text-white/75">
            Tersedia {rupiah(tersedia ?? saldo)}{ditahan > 0 ? ` · Ditahan ${rupiah(ditahan)} (penarikan diproses)` : ''}
          </p>
        )}
        {actions && <div className="mt-5 flex flex-wrap gap-2">{actions}</div>}
      </div>
    </section>
  );
}
