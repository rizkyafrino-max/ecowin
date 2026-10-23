<style>
/* =====================================================================================================
   EcoWin UI v3 — bahasa desain "aplikasi modern": kartu sangat membulat dan melayang, chip berbentuk pil,
   kolom cari pil dengan tombol bulat, ikon dalam lingkaran, bilah bawah gelap berbentuk pil.
   Dimuat setelah global-ui-styles sehingga menggantikan aturan lamanya. Warna peran lewat --eco-* (Admin indigo, Petugas zamrud).
   ===================================================================================================== */
:root{
  --v3-bg:#F1F4F9;--v3-card:#FFFFFF;--v3-ink:#0F172A;--v3-muted:#64748B;--v3-line:#E5E9F0;--v3-field:#CBD5E1;
  --v3-r-card:28px;--v3-r-md:18px;--v3-r-field:16px;
  --v3-sh:0 1px 2px rgba(15,23,42,.04),0 14px 34px -18px rgba(15,23,42,.22);
  --v3-sh-sm:0 1px 2px rgba(15,23,42,.05),0 6px 16px -10px rgba(15,23,42,.2);
  --v3-glow:rgba(var(--eco-rgb),.12);
}
html,html.dark{background:var(--v3-bg)!important}
.fi-body{background:radial-gradient(900px 420px at 92% -8%,var(--v3-glow),transparent 62%),radial-gradient(760px 420px at -8% 100%,rgba(var(--eco-rgb),.07),transparent 58%),var(--v3-bg)!important;background-attachment:fixed}

/* ---- Topbar & sidebar: kaca tipis dan kartu melayang ---- */
.fi-topbar{background:rgba(255,255,255,.72)!important;backdrop-filter:blur(20px) saturate(1.4);border-bottom:1px solid rgba(15,23,42,.05)!important}
.fi-topbar .fi-icon-btn{border-radius:999px!important;background:#fff;box-shadow:var(--v3-sh-sm)}
.fi-topbar .fi-user-menu .fi-dropdown-trigger{border-radius:999px;box-shadow:0 0 0 3px #fff,0 0 0 4px var(--v3-line)}
@media(min-width:1024px){
  .fi-sidebar{background:transparent!important;border:0!important}
  .fi-sidebar-nav{margin:.6rem 0 .75rem .75rem;height:calc(100% - 1.35rem)!important;border-radius:var(--v3-r-card);background:var(--v3-card);border:1px solid rgba(15,23,42,.05);box-shadow:var(--v3-sh);padding-block:1rem!important}
}
@media(max-width:1023px){.fi-sidebar{background:#fff!important}}
.fi-sidebar-item-btn{border-radius:999px!important;padding:.6rem .9rem!important}
.fi-sidebar-item-btn:hover{background:rgba(var(--eco-rgb),.07)!important}
.fi-sidebar-item.fi-active>.fi-sidebar-item-btn{background:linear-gradient(135deg,var(--eco-secondary),var(--eco-primary))!important;box-shadow:0 10px 20px -10px rgba(var(--eco-rgb),.75)!important}
.fi-sidebar-group-label{font-size:.66rem!important;letter-spacing:.14em!important;color:#94A3B8!important}
.fi-sidebar-group-btn{padding-inline:.9rem!important}

/* ---- Judul halaman ---- */
.fi-header-heading{font-size:1.85rem!important;letter-spacing:-.02em;font-weight:800!important}
.fi-breadcrumbs{font-size:.78rem}
.fi-header .fi-btn,.fi-header-actions-ctn .fi-btn{box-shadow:0 12px 22px -12px rgba(var(--eco-rgb),.8)!important}

/* ---- Kartu: bagian formulir, tabel, widget ---- */
.fi-section,.fi-ta-ctn,.fi-wi-stats-overview-stat,.fi-fo-section,.fi-wi-chart,.fi-wi-table,.fi-in-section{border:1px solid rgba(15,23,42,.05)!important;border-radius:var(--v3-r-card)!important;background:var(--v3-card)!important;box-shadow:var(--v3-sh)!important;--tw-ring-shadow:0 0 #0000!important}
.fi-section.fi-section-not-contained,.fi-wi-widget.fi-wi-stats-overview{border:0!important;box-shadow:none!important;background:transparent!important}
.fi-section-header{padding:1.15rem 1.5rem .25rem!important;border:0!important}
.fi-section-header-heading{font-size:1.02rem!important;font-weight:800!important}
.fi-section-content-ctn{border:0!important}
.fi-section-content{padding:1.1rem 1.5rem 1.4rem!important}
.fi-wi-stats-overview-stat{padding:1.2rem 1.35rem!important;transition:transform .15s ease,box-shadow .15s ease}
.fi-wi-stats-overview-stat:hover{transform:translateY(-2px);box-shadow:0 18px 38px -18px rgba(15,23,42,.32)!important}
.fi-wi-stats-overview-stat-label-ctn .fi-icon{width:2.4rem!important;height:2.4rem!important;padding:.6rem!important;border-radius:999px!important;background:rgba(var(--eco-rgb),.1)!important;color:var(--eco-primary)!important}
.fi-wi-stats-overview-stat-value{font-size:1.75rem!important;letter-spacing:-.02em}

/* ---- Tombol ---- */
.fi-btn{border-radius:999px!important;font-weight:700!important;min-height:2.6rem;padding-inline:1.2rem}
.fi-btn.fi-color-primary{background:linear-gradient(135deg,var(--eco-secondary),var(--eco-primary))!important;box-shadow:0 12px 22px -12px rgba(var(--eco-rgb),.85)}
.fi-btn.fi-color-primary:hover{background:linear-gradient(135deg,var(--eco-primary),var(--eco-deep))!important}
.fi-btn.fi-color-gray,.fi-btn.fi-outlined{background:#fff!important;color:var(--v3-ink)!important;box-shadow:var(--v3-sh-sm)!important;--tw-ring-color:var(--v3-line)}
.fi-icon-btn{border-radius:999px}
.fi-link{font-weight:700}

/* ---- Isian formulir ---- */
.fi-input-wrp{border-radius:var(--v3-r-field)!important;border-color:var(--v3-field)!important;background:#fff;min-height:3rem}
.fi-input-wrp .fi-input,.fi-input-wrp .fi-select-input{font-size:.92rem;min-height:2.9rem}
.fi-fo-field-label-content{font-size:.82rem;font-weight:700;color:var(--v3-ink)}
.fi-fo-field-wrp-hint,.fi-fo-field-wrp-helper-text{color:var(--v3-muted)}
.fi-fo-checkbox-list label:has(input[type=checkbox]){display:flex;align-items:center;gap:.6rem;padding:.6rem .85rem;border:1px solid var(--v3-field);border-radius:14px;background:#fff;font-weight:600;cursor:pointer;transition:background .12s ease,border-color .12s ease}
.fi-fo-checkbox-list label:has(input[type=checkbox]:checked){background:rgba(var(--eco-rgb),.09);border-color:var(--eco-primary);color:var(--eco-primary)}
.fi-fo-file-upload .filepond--root,.fi-fo-file-upload .filepond--panel-root{border-radius:var(--v3-r-field)}
.fi-form-actions,.fi-sc-actions{margin-top:.75rem}

/* ---- Tab sub-navigasi dan tab biasa: chip pil ---- */
.fi-page-sub-navigation-tabs,.fi-tabs:not(.fi-vertical){display:flex!important;flex-wrap:nowrap;gap:.5rem;width:100%;max-width:100%;margin:0 0 1.25rem!important;padding:.15rem .1rem .35rem!important;background:transparent!important;border:0!important;box-shadow:none!important;border-radius:0;overflow-x:auto;scrollbar-width:none}
.fi-page-sub-navigation-tabs::-webkit-scrollbar,.fi-tabs::-webkit-scrollbar{display:none}
.fi-page-sub-navigation-tabs .fi-tabs-item,.fi-tabs:not(.fi-vertical) .fi-tabs-item{flex:none;display:inline-flex!important;align-items:center;gap:.5rem;padding:.65rem 1.15rem!important;border-radius:999px!important;border:1px solid var(--v3-line)!important;background:#fff!important;box-shadow:var(--v3-sh-sm);font-size:.875rem;font-weight:700;white-space:nowrap;color:var(--v3-muted)!important;transition:background .15s ease,color .15s ease,box-shadow .15s ease}
.fi-page-sub-navigation-tabs .fi-tabs-item:hover,.fi-tabs:not(.fi-vertical) .fi-tabs-item:hover{color:var(--v3-ink)!important;border-color:#CBD5E1!important}
.fi-page-sub-navigation-tabs .fi-tabs-item.fi-active,.fi-tabs:not(.fi-vertical) .fi-tabs-item.fi-active{background:var(--v3-ink)!important;border-color:var(--v3-ink)!important;color:#fff!important;box-shadow:0 12px 22px -12px rgba(15,23,42,.7)}
.fi-page-sub-navigation-tabs .fi-tabs-item.fi-active *,.fi-tabs:not(.fi-vertical) .fi-tabs-item.fi-active *{color:#fff!important}
.fi-page-sub-navigation-tabs .fi-tabs-item .fi-badge{margin-inline-start:.1rem}
.fi-page-sub-navigation-tabs .fi-tabs-item.fi-active .fi-badge{background:rgba(255,255,255,.2)!important;--tw-ring-color:rgba(255,255,255,.4)}
.fi-page-sub-navigation-dropdown{display:none!important}
.fi-page-has-sub-navigation .fi-breadcrumbs{display:none!important}

/* ---- Tabel: bilah alat (aksi | cari + tombol bulat), isi lega, header menyatu ---- */
.fi-ta-ctn{overflow:hidden}
.fi-ta-header-ctn{display:flex!important;flex-wrap:wrap;align-items:center;gap:.65rem .75rem;padding:1rem 1.25rem!important}
.fi-ta-header,.fi-ta-header-toolbar{display:contents!important}
.fi-ta-header-ctn>.fi-ta-header>.fi-ta-actions{order:1;margin-inline:0!important}
.fi-ta-header-ctn>.fi-ta-header-toolbar>.fi-ta-actions{order:2;margin:0!important}
.fi-ta-header-ctn .fi-ta-grouping-settings{order:3}
.fi-ta-header-ctn>.fi-ta-header-toolbar>div:last-child{order:4;display:flex;align-items:center;gap:.5rem;flex:1 1 300px;justify-content:flex-end;min-width:0}
.fi-ta-search-field{flex:1 1 auto;max-width:420px;min-width:0}
.fi-ta-search-field .fi-input-wrp{border-radius:999px!important;background:#fff;border-color:var(--v3-line)!important;box-shadow:var(--v3-sh-sm)!important;min-height:2.9rem}
.fi-ta-filters-dropdown .fi-icon-btn,.fi-ta-col-manager-dropdown .fi-icon-btn{width:2.9rem;height:2.9rem;display:inline-flex;align-items:center;justify-content:center;border-radius:999px;background:#fff;border:1px solid var(--v3-line);box-shadow:var(--v3-sh-sm);color:var(--v3-ink)}
.fi-ta-filters-dropdown .fi-icon-btn:hover,.fi-ta-col-manager-dropdown .fi-icon-btn:hover{background:var(--v3-ink);color:#fff;border-color:var(--v3-ink)}
.fi-ta-filters-dropdown .fi-icon-btn svg path{fill:none;stroke:currentColor;stroke-width:1.5}
.fi-ta-filters-dropdown .fi-badge.eco-nol{display:none!important}
.fi-ta-grouping-settings .fi-input-wrp{border-radius:999px!important;background:#fff;border-color:var(--v3-line)!important;box-shadow:var(--v3-sh-sm)!important;min-height:2.9rem}
.fi-ta-grouping-settings .fi-input-wrp-label{font-size:.74rem;font-weight:700;color:var(--v3-muted)}
.fi-ta-grouping-settings .fi-select-input{font-weight:700;font-size:.85rem}
.fi-ta-table{font-size:.84rem}
.fi-ta-header-cell{background:transparent!important;border-block:1px solid var(--v3-line)!important;padding:.8rem .5rem .8rem .75rem!important;font-size:.68rem!important;letter-spacing:.09em;color:#94A3B8!important;white-space:nowrap}
.fi-ta-header-cell:first-child{padding-left:1.25rem!important}
.fi-ta-cell{padding-inline:.5rem .25rem!important}
.fi-ta-cell:first-child{padding-left:1.25rem!important}
.fi-ta-row{border-top:1px solid #EEF1F6}
.fi-ta-row:hover{background:rgba(var(--eco-rgb),.04)!important}
.fi-ta-footer-ctn,.fi-ta-footer{padding:.75rem 1.25rem!important}
.fi-ta-content-ctn::-webkit-scrollbar{height:10px}
.fi-ta-content-ctn::-webkit-scrollbar-thumb{background:#CBD5E1;border-radius:99px;border:2px solid #fff}
.fi-ta-content-ctn{background:linear-gradient(to right,#fff 30%,rgba(255,255,255,0)) left center,linear-gradient(to left,#fff 30%,rgba(255,255,255,0)) right center,radial-gradient(farthest-side at 0 50%,rgba(15,23,42,.14),rgba(15,23,42,0)) left center,radial-gradient(farthest-side at 100% 50%,rgba(15,23,42,.14),rgba(15,23,42,0)) right center;background-repeat:no-repeat;background-size:44px 100%,44px 100%,16px 100%,16px 100%;background-attachment:local,local,scroll,scroll}
.fi-ta-table th.fi-ta-actions-header-cell,.fi-ta-table td:has(>.fi-ta-actions){position:sticky;right:0;z-index:2;background:#fff}
.fi-ta-table th.fi-ta-actions-header-cell{background:#fff!important}
.fi-ta-table td:has(>.fi-ta-actions){box-shadow:-12px 0 12px -12px rgba(15,23,42,.2)}
.fi-ta-table .fi-ta-row:hover td:has(>.fi-ta-actions){background:#FAFBFD}
/* Aksi baris: pil lembut berwarna sesuai maknanya */
.fi-ta-actions .fi-link{display:inline-flex;align-items:center;gap:.35rem;padding:.4rem .8rem;border-radius:999px;background:rgba(var(--eco-rgb),.09);color:var(--eco-primary)!important;font-size:.78rem;white-space:nowrap;text-decoration:none}
.fi-ta-actions .fi-link:hover{background:rgba(var(--eco-rgb),.16)}
.fi-ta-actions .fi-link.fi-color-danger{background:rgba(220,38,38,.09);color:#B91C1C!important}
.fi-ta-actions .fi-link.fi-color-success{background:rgba(5,150,105,.1);color:#047857!important}
.fi-ta-actions .fi-link.fi-color-warning{background:rgba(217,119,6,.12);color:#B45309!important}
.fi-ta-actions .fi-link.fi-color-gray{background:#F1F5F9;color:#475569!important}
.fi-ta-actions .fi-link .fi-link-label,.fi-ta-actions .fi-link svg{color:inherit!important}
.fi-badge{font-size:.72rem;font-weight:700!important;padding:.2rem .7rem!important}
.fi-pagination-item-btn,.fi-pagination-records-per-page-select .fi-input-wrp{border-radius:999px!important}
.fi-pagination-item.fi-active .fi-pagination-item-btn{background:var(--v3-ink)!important;color:#fff!important}

/* ---- Kartu tabel di HP: kartu bulat melayang ---- */
@media(max-width:640px){
  .fi-ta-header-ctn{padding:.85rem .85rem .25rem!important;gap:.6rem}
  .fi-ta-header-ctn>.fi-ta-header>.fi-ta-actions{flex:1 1 100%}
  .fi-ta-header-ctn>.fi-ta-header>.fi-ta-actions .fi-btn{width:100%;justify-content:center}
  .fi-ta-header-ctn>.fi-ta-header-toolbar>div:last-child{flex:1 1 100%;justify-content:space-between}
  .fi-ta-search-field{flex:1 1 auto;max-width:none}
  .fi-ta-table .fi-ta-row{border:1px solid rgba(15,23,42,.05)!important;border-radius:24px!important;box-shadow:var(--v3-sh-sm)!important;margin:.65rem .75rem!important}
  .fi-ta-table .fi-ta-cell,.fi-ta-table .fi-ta-cell:first-child{padding-left:0!important}
  .fi-header-heading{font-size:1.5rem!important}
}

/* ---- Modal, dropdown, notifikasi ---- */
.fi-modal-window{border-radius:var(--v3-r-card)!important;box-shadow:0 30px 70px -28px rgba(15,23,42,.5)!important}
.fi-dropdown-panel{border-radius:20px!important;box-shadow:0 20px 50px -22px rgba(15,23,42,.45)!important;border:1px solid rgba(15,23,42,.06)}
.fi-no-notification{border-radius:20px!important;box-shadow:0 18px 44px -22px rgba(15,23,42,.45)!important}

/* ---- Bilah bawah: kapsul gelap, ikon aktif dalam lingkaran/pil terang ---- */
.eco-footer{width:min(560px,calc(100vw - 28px));min-height:66px;padding:.45rem .6rem calc(.45rem + env(safe-area-inset-bottom));border:1px solid rgba(255,255,255,.08);border-radius:999px;background:rgba(15,23,42,.9);backdrop-filter:blur(18px) saturate(1.3);box-shadow:0 22px 44px -16px rgba(15,23,42,.6);gap:.15rem}
.eco-footer-link{min-width:60px;padding:.45rem .7rem;border-radius:999px;color:rgba(255,255,255,.68);font-size:10px}
.eco-footer-link:hover{background:rgba(255,255,255,.1);color:#fff}
.eco-footer-link.is-active{background:#fff;color:var(--v3-ink)}
.eco-footer-scan{width:54px;height:54px;min-width:54px;margin-top:0;border:0;border-radius:999px;background:linear-gradient(135deg,var(--eco-secondary),var(--eco-primary));color:#fff;box-shadow:0 12px 24px -8px rgba(var(--eco-rgb),.8)}
.eco-footer-scan:hover,.eco-footer-scan.is-active{background:var(--eco-deep);color:#fff}
@media(max-width:640px){.eco-footer{min-height:62px;padding-inline:.4rem}.eco-footer-link{min-width:50px;padding-inline:.45rem}.eco-footer-scan{width:50px;height:50px;min-width:50px}}

/* ---- Dasbor: kartu dan chip bergaya aplikasi ---- */
.eco-slide{border-radius:var(--v3-r-card)}
.eco-slide-cta{box-shadow:0 10px 20px -10px rgba(0,0,0,.5)}
.eco-search{border-radius:999px;height:46px;box-shadow:var(--v3-sh-sm);border-color:var(--v3-line)}
.eco-icon-btn{border-radius:999px;width:46px;height:46px;box-shadow:var(--v3-sh-sm);border-color:var(--v3-line)}
.eco-cats{gap:.65rem}
.eco-cat{border-radius:999px;padding:.5rem .95rem .5rem .5rem;border-color:var(--v3-line);box-shadow:var(--v3-sh-sm)}
.eco-cat-ico{width:38px;height:38px;border-radius:999px}
.eco-panel,.eco-item,.eco-report-card{border:1px solid rgba(15,23,42,.05);border-radius:var(--v3-r-card);box-shadow:var(--v3-sh)}
.eco-item{padding:1.15rem 1.25rem}
.eco-item-badge{border-radius:999px;padding:.2rem .65rem}
.eco-plus{width:38px;height:38px;background:linear-gradient(135deg,var(--eco-secondary),var(--eco-primary));box-shadow:0 10px 18px -8px rgba(var(--eco-rgb),.8)}
.eco-rank-no{border-radius:999px}
.eco-avatar{box-shadow:0 0 0 3px #fff,0 0 0 4px var(--v3-line),var(--v3-sh-sm)}

/* ---- Perapian lanjutan ---- */
/* Kartu statistik sejajar dengan bagian lain (tidak ikut bantalan bagian formulir) */
.fi-wi-stats-overview .fi-section-content,.fi-wi-stats-overview .fi-section-content-ctn{padding:0!important}
.fi-wi-stats-overview .fi-section-content.fi-grid{gap:1rem!important}
.fi-page-has-sub-navigation .fi-page-content{gap:1.25rem}
/* Tombol bulat toolbar tidak saling menempel */
.fi-ta-header-ctn .fi-dropdown{flex:none}
.fi-ta-filters-dropdown,.fi-ta-col-manager-dropdown{width:2.9rem;height:2.9rem;flex:none}
.fi-ta-filters-dropdown .fi-dropdown-trigger,.fi-ta-col-manager-dropdown .fi-dropdown-trigger{display:flex;width:100%;height:100%}
.fi-ta-header-ctn>.fi-ta-header-toolbar>div:last-child{gap:.6rem}
/* Header tabel: satu warna, termasuk sel aksi yang menempel */
.fi-ta-header-cell,.fi-ta-table th.fi-ta-actions-header-cell{background:#F8FAFC!important}

/* Di HP kolom aksi tidak menempel (bukan tabel geser) */
@media(max-width:640px){.fi-ta-table td:has(>.fi-ta-actions){position:static!important;box-shadow:none!important;background:transparent!important}.fi-ta-table .fi-ta-row:hover td:has(>.fi-ta-actions){background:transparent!important}}
/* Ruang antara tab dan kartu angka lebih rapat */
.fi-page-has-sub-navigation .fi-page-main,.fi-page-has-sub-navigation .fi-page-content{row-gap:1rem}
.fi-page-sub-navigation-tabs{margin-bottom:.35rem!important}

/* Halaman Peta Biopori (gaya khusus di halamannya) disamakan dengan kartu v3 */
.fi-page .eco-biopori-page .eco-stat{border-radius:24px;border-color:rgba(15,23,42,.05);box-shadow:var(--v3-sh)}
.fi-page .eco-biopori-page .eco-panel{border-radius:var(--v3-r-card);border-color:rgba(15,23,42,.05);box-shadow:var(--v3-sh)}
.fi-page .eco-biopori-page .eco-button{border-radius:999px;background:linear-gradient(135deg,var(--eco-secondary),var(--eco-primary));padding:.6rem 1.1rem}
</style>
