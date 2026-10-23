<style>
/* Palet warna "v3" di atas tampilan yang ada: latar abu kebiruan + semburat warna peran, gradasi pada tombol utama dan menu aktif,
   garis dan bayangan kartu yang lembut. Tidak mengubah bentuk/tata letak. */
:root{--v3-bg:#F1F4F9;--v3-line:#E5E9F0;--v3-sh:0 1px 2px rgba(15,23,42,.04),0 14px 34px -18px rgba(15,23,42,.22);--v3-sh-sm:0 1px 2px rgba(15,23,42,.05),0 6px 16px -10px rgba(15,23,42,.2)}
html,html.dark{background:var(--v3-bg)!important}
.fi-body{background:radial-gradient(900px 420px at 92% -8%,rgba(var(--eco-rgb),.12),transparent 62%),radial-gradient(760px 420px at -8% 100%,rgba(var(--eco-rgb),.07),transparent 58%),var(--v3-bg)!important;background-attachment:fixed}
.fi-topbar{background:rgba(255,255,255,.72)!important;backdrop-filter:blur(20px) saturate(1.4);border-bottom:1px solid rgba(15,23,42,.05)!important}
.fi-sidebar{border-right:1px solid rgba(15,23,42,.05)!important}
.fi-sidebar-item.fi-active>.fi-sidebar-item-btn{background:linear-gradient(135deg,var(--eco-secondary),var(--eco-primary))!important;box-shadow:0 10px 20px -10px rgba(var(--eco-rgb),.75)!important}
.fi-sidebar-group-label{color:#94A3B8!important}
.fi-btn.fi-color-primary{background:linear-gradient(135deg,var(--eco-secondary),var(--eco-primary))!important;box-shadow:0 12px 22px -12px rgba(var(--eco-rgb),.85)}
.fi-btn.fi-color-primary:hover{background:linear-gradient(135deg,var(--eco-primary),var(--eco-deep))!important}
.fi-section,.fi-ta-ctn,.fi-wi-stats-overview-stat,.fi-fo-section,.fi-wi-chart,.fi-wi-table{border:1px solid rgba(15,23,42,.05)!important;box-shadow:var(--v3-sh)!important;--tw-ring-shadow:0 0 #0000!important}
.fi-section.fi-section-not-contained,.fi-wi-widget.fi-wi-stats-overview{border:0!important;box-shadow:none!important}
.fi-wi-stats-overview-stat:hover{box-shadow:0 18px 38px -18px rgba(15,23,42,.32)!important}
.fi-wi-stats-overview-stat-label-ctn .fi-icon{background:rgba(var(--eco-rgb),.1)!important;color:var(--eco-primary)!important}
.fi-ta-header-cell{background:#F8FAFC!important;color:#94A3B8!important}
.fi-ta-row:hover{background:rgba(var(--eco-rgb),.04)!important}
.fi-ta-search-field .fi-input-wrp{background:#fff;border-color:var(--v3-line)!important;box-shadow:var(--v3-sh-sm)!important}
.fi-input-wrp{border-color:#CBD5E1!important}
/* Aksi baris: tinta lembut sesuai makna */
.fi-ta-actions .fi-link{display:inline-flex;align-items:center;gap:.35rem;padding:.35rem .75rem;border-radius:999px;background:rgba(var(--eco-rgb),.09);color:var(--eco-primary)!important;font-size:.78rem;white-space:nowrap;text-decoration:none}
.fi-ta-actions .fi-link:hover{background:rgba(var(--eco-rgb),.16)}
.fi-ta-actions .fi-link.fi-color-danger{background:rgba(220,38,38,.09);color:#B91C1C!important}
.fi-ta-actions .fi-link.fi-color-success{background:rgba(5,150,105,.1);color:#047857!important}
.fi-ta-actions .fi-link.fi-color-warning{background:rgba(217,119,6,.12);color:#B45309!important}
.fi-ta-actions .fi-link.fi-color-gray{background:#F1F5F9;color:#475569!important}
.fi-ta-actions .fi-link .fi-link-label,.fi-ta-actions .fi-link svg{color:inherit!important}
.eco-slide-cta{box-shadow:0 10px 20px -10px rgba(0,0,0,.5)}
.eco-plus{background:linear-gradient(135deg,var(--eco-secondary),var(--eco-primary))}
/* Tombol filter dan kolom tidak saling menempel */
.fi-ta-header-ctn .fi-dropdown{flex:none}
.fi-ta-filters-dropdown .fi-icon-btn{margin-inline-end:.4rem}
</style>
