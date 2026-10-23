@php
    $ecoIsAdmin = auth()->user()?->isAdmin() ?? false;
    // Identitas warna per role: Admin = Indigo, Petugas = Emerald.
    $eco = $ecoIsAdmin
        ? ['primary' => '#4F46E5', 'secondary' => '#6366F1', 'deep' => '#3730A3', 'soft' => '#EEF2FF', 'rgb' => '79,70,229']
        : ['primary' => '#047857', 'secondary' => '#059669', 'deep' => '#065F46', 'soft' => '#ECFDF5', 'rgb' => '4,120,87'];
@endphp
<style>
:root{color-scheme:light!important;--eco-primary:{{ $eco['primary'] }};--eco-secondary:{{ $eco['secondary'] }};--eco-deep:{{ $eco['deep'] }};--eco-soft:{{ $eco['soft'] }};--eco-rgb:{{ $eco['rgb'] }};--eco-canvas:#F8FAFC;--eco-surface:#FFFFFF;--eco-ink:#0F172A;--eco-muted:#64748B;--eco-line:#E2E8F0}
html,html.dark{color-scheme:light!important;background:var(--eco-canvas)!important}
.fi-body{background:var(--eco-canvas)!important;color:var(--eco-ink)}
.fi-main{padding-bottom:calc(112px + env(safe-area-inset-bottom))!important}
.fi-topbar{background:rgba(255,255,255,.92)!important;backdrop-filter:blur(12px);border-bottom:1px solid var(--eco-line)!important}
.fi-sidebar{background:var(--eco-surface)!important;border-right:1px solid var(--eco-line)!important}
.fi-sidebar-header{padding-block:1.25rem!important;border-bottom:1px solid var(--eco-line)}
.fi-logo{color:var(--eco-primary)!important;font-weight:800!important;}
.fi-sidebar-nav-groups{gap:1.1rem!important}
.fi-sidebar-group-label{font-size:.68rem!important;text-transform:uppercase;letter-spacing:.12em;color:var(--eco-muted)!important;font-weight:700!important}
.fi-sidebar-item-label{font-size:.86rem!important}
.fi-page{max-width:1420px;margin-inline:auto}
.fi-page-header{padding-bottom:1rem!important}
.fi-section-content-ctn{padding:1.25rem!important}
.fi-wi-stats-overview{gap:1rem!important}
.fi-wi-stats-overview-stat-value{font-size:1.6rem!important}
.fi-ta-content{background:#fff!important}
.fi-ta-content-ctn,.fi-ta-ctn{overflow-x:auto}
.fi-header-heading{font-weight:800!important;color:var(--eco-ink)!important}
.fi-section,.fi-ta-ctn,.fi-wi-stats-overview-stat,.fi-wi-widget,.fi-fo-section{border:1px solid var(--eco-line)!important;border-radius:16px!important;box-shadow:0 1px 3px rgba(15,23,42,.06)!important}
.fi-wi-stats-overview-stat{background:var(--eco-surface)!important;padding:1.25rem!important}
.fi-wi-stats-overview-stat-icon,.fi-wi-stats-overview-stat .fi-icon{color:var(--eco-primary)!important}
.fi-wi-stats-overview-stat-label{font-weight:600!important;color:var(--eco-muted)!important}
.fi-header{margin-bottom:.25rem}
.fi-main{gap:1.25rem}
.eco-role{display:inline-block;padding:.05rem .5rem;border-radius:999px;background:var(--eco-soft);color:var(--eco-primary);font-size:.66rem;font-weight:800;letter-spacing:.12em;margin-right:.35rem;vertical-align:1px}
.fi-btn{border-radius:10px!important;font-weight:600!important}
.fi-input-wrp{border-radius:10px!important;border-color:#CBD5E1!important;box-shadow:none!important}
.fi-input-wrp:focus-within{border-color:var(--eco-primary)!important;box-shadow:0 0 0 3px rgba(var(--eco-rgb),.12)!important}
.fi-ta-header-cell{background:#F8FAFC!important;color:var(--eco-muted)!important;text-transform:uppercase;font-size:.68rem!important;letter-spacing:.08em;font-weight:700!important}
.fi-ta-row:hover{background:#F8FAFC!important}
.fi-breadcrumbs{color:var(--eco-muted)!important}
.eco-role-badge{display:inline-flex;align-items:center;gap:.4rem;padding:.25rem .65rem;border-radius:999px;font-size:.7rem;font-weight:800;letter-spacing:.12em;background:var(--eco-soft);color:var(--eco-primary);border:1px solid rgba(var(--eco-rgb),.25)}
.eco-role-badge::before{content:"";width:.45rem;height:.45rem;border-radius:999px;background:var(--eco-primary)}
.eco-role-badge small{font-weight:600;letter-spacing:0;color:var(--eco-muted);font-size:.7rem}
.eco-footer{position:fixed;z-index:20;bottom:14px;left:50%;transform:translateX(-50%);width:min(760px,calc(100vw - 28px));min-height:64px;display:flex;align-items:center;justify-content:space-around;gap:8px;padding:8px 14px calc(8px + env(safe-area-inset-bottom));border:1px solid var(--eco-line);border-radius:20px;background:rgba(255,255,255,.95);box-shadow:0 8px 24px rgba(15,23,42,.10);backdrop-filter:blur(12px);transition:transform .2s ease,opacity .2s ease,visibility .2s ease}
.eco-footer.is-hidden{transform:translate(-50%,calc(100% + 28px));opacity:0;visibility:hidden;pointer-events:none}
.fi-sidebar,.fi-sidebar-ctn,.fi-sidebar-overlay{z-index:50!important}
.eco-footer-link{min-width:62px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:3px;padding:7px 10px;color:var(--eco-muted);text-decoration:none;font-size:10px;font-weight:700;border-radius:12px;transition:background .15s ease,color .15s ease}
.eco-footer-link svg{width:21px;height:21px;stroke:currentColor;stroke-width:1.8;fill:none;stroke-linecap:round;stroke-linejoin:round}
.eco-footer-link:hover,.eco-footer-link.is-active{background:var(--eco-soft);color:var(--eco-primary)}
.eco-footer-scan{width:56px;height:56px;min-width:56px;margin-top:-24px;border:5px solid var(--eco-canvas);border-radius:18px;background:var(--eco-primary);color:#fff;box-shadow:0 6px 16px rgba(var(--eco-rgb),.3)}
.eco-footer-scan:hover,.eco-footer-scan.is-active{background:var(--eco-deep);color:#fff}
.eco-footer-scan svg{width:24px;height:24px}
.eco-report-grid{display:grid;gap:1rem;grid-template-columns:repeat(auto-fit,minmax(260px,1fr))}
.eco-report-card{background:#fff;border:1px solid var(--eco-line);border-radius:16px;padding:1.1rem 1.25rem}
.eco-report-card h3{font-size:.8rem;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:var(--eco-primary);margin-bottom:.75rem}
.eco-report-row{display:flex;justify-content:space-between;gap:1rem;padding:.45rem 0;border-bottom:1px dashed var(--eco-line);font-size:.9rem}
.eco-report-row:last-child{border-bottom:0}
.eco-report-row span{color:var(--eco-muted)}
.eco-report-row b{color:var(--eco-ink);text-align:right}
.eco-login-google{display:flex;align-items:center;justify-content:center;gap:.75rem;width:100%;padding:.8rem 1rem;border-radius:12px;border:1px solid #CBD5E1;background:#fff;color:#0F172A;font-weight:600;text-decoration:none;transition:background .15s ease,border-color .15s ease}
.eco-login-google:hover{background:#F8FAFC;border-color:#94A3B8}
.eco-login-google svg{width:20px;height:20px}
.eco-login-note{margin-top:1rem;font-size:.85rem;color:var(--eco-muted);text-align:center}
.eco-login-error{margin-bottom:1rem;padding:.75rem 1rem;border-radius:12px;background:#FEF2F2;color:#B91C1C;border:1px solid #FECACA;font-size:.875rem}
.fi-wi-widget:has(.eco-home){border:0!important;box-shadow:none!important;background:transparent!important;padding:0!important;--tw-ring-shadow:0 0 #0000!important;box-shadow:none!important}
.eco-home{display:flex;flex-direction:column;gap:1.5rem}
.eco-home-top{display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap}
.eco-home-user{display:flex;align-items:center;gap:.85rem;min-width:0}
.eco-avatar{width:48px;height:48px;border-radius:999px;object-fit:cover;flex:none;box-shadow:0 0 0 3px #fff,0 0 0 4px var(--eco-line)}
.eco-avatar-text{display:inline-flex;align-items:center;justify-content:center;background:var(--eco-soft);color:var(--eco-primary);font-weight:700}
.eco-home-name{font-weight:700;font-size:1.15rem;color:var(--eco-ink);line-height:1.3}
.eco-home-loc{font-size:.82rem;color:var(--eco-muted);margin-top:.15rem}
.eco-home-tools{display:flex;align-items:center;gap:.6rem;flex:1;justify-content:flex-end;min-width:260px}
.eco-search{display:flex;align-items:center;gap:.6rem;flex:1;max-width:420px;padding:0 .9rem;height:42px;border-radius:10px;background:var(--eco-surface);border:1px solid var(--eco-line);color:var(--eco-muted)}
.eco-search:focus-within{border-color:var(--eco-primary);box-shadow:0 0 0 3px rgba(var(--eco-rgb),.12)}
.eco-search input{flex:1;border:0;outline:0;background:transparent;font-size:.875rem;color:var(--eco-ink);box-shadow:none!important;padding:0}
.eco-icon-btn{position:relative;width:42px;height:42px;border-radius:10px;display:inline-flex;align-items:center;justify-content:center;background:var(--eco-surface);border:1px solid var(--eco-line);color:var(--eco-ink)}
.eco-icon-btn:hover{background:var(--eco-canvas)}
.eco-ico{width:20px;height:20px}
.eco-dot{position:absolute;top:-6px;right:-6px;min-width:18px;height:18px;padding:0 5px;border-radius:999px;background:#DC2626;color:#fff;font-size:.65rem;font-weight:700;display:inline-flex;align-items:center;justify-content:center;border:2px solid #fff}
.eco-slides{display:flex;gap:1rem;overflow-x:auto;scroll-snap-type:x mandatory;scrollbar-width:none;-webkit-overflow-scrolling:touch;padding-bottom:2px}
.eco-slides::-webkit-scrollbar{display:none}
.eco-slide{position:relative;flex:0 0 94%;scroll-snap-align:start;display:flex;align-items:stretch;justify-content:space-between;gap:1rem;min-height:230px;padding:1.75rem 2rem;border-radius:20px;background:var(--eco-deep);color:#fff;overflow:hidden;box-shadow:0 1px 2px rgba(15,23,42,.1)}
.eco-slide:nth-child(even){background:var(--eco-primary)}
.eco-slide-text{position:relative;z-index:2;display:flex;flex-direction:column;align-items:flex-start;justify-content:center;min-width:0}
.eco-slide-badge{display:inline-block;padding:.2rem .7rem;border-radius:999px;background:#fff;color:var(--eco-deep);font-size:.72rem;font-weight:700}
.eco-slide-label{margin-top:.9rem;font-size:.9rem;font-weight:500;color:rgba(255,255,255,.82)}
.eco-slide-value{margin-top:.15rem;font-size:2.4rem;font-weight:800;line-height:1.1;font-variant-numeric:tabular-nums;overflow-wrap:anywhere}
.eco-slide-note{margin-top:.5rem;font-size:.8rem;color:rgba(255,255,255,.75)}
.eco-slide-cta{margin-top:1.1rem;display:inline-block;padding:.5rem 1.4rem;border-radius:999px;background:#FACC15;color:#1F2937;font-size:.86rem;font-weight:700;text-decoration:none;transition:background .15s ease}
.eco-slide-cta:hover{background:#FDE047}
.eco-slide-art{position:relative;flex:none;width:260px;display:flex;align-items:center;justify-content:center}
.eco-art-ring{position:absolute;border-radius:999px;background:rgba(255,255,255,.08)}
.eco-art-ring-1{width:250px;height:250px;right:-40px;top:-50px}
.eco-art-ring-2{width:170px;height:170px;right:30px;bottom:-60px;background:rgba(255,255,255,.07)}
.eco-art-core{position:relative;width:112px;height:112px;border-radius:999px;background:rgba(255,255,255,.95);display:flex;align-items:center;justify-content:center;box-shadow:0 12px 28px rgba(0,0,0,.22)}
.eco-art-icon{width:54px;height:54px;color:var(--eco-primary)}
.eco-leaf{position:absolute;color:#86EFAC}
.eco-leaf-1{width:46px;height:46px;left:22px;top:18px;transform:rotate(-18deg)}
.eco-leaf-2{width:30px;height:30px;right:10px;bottom:22px;transform:rotate(24deg);color:#BBF7D0}
.eco-dots{display:flex;justify-content:center;gap:.4rem;margin-top:.85rem}
.eco-dots button{width:8px;height:8px;padding:0;border:0;border-radius:999px;background:var(--eco-line);cursor:pointer;transition:width .2s ease,background .2s ease}
.eco-dots button.is-on{width:22px;background:var(--eco-primary)}
.eco-sec-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:.85rem}
.eco-sec-head h2{font-size:1rem;font-weight:700;color:var(--eco-ink)}
.eco-sec-head a{font-size:.82rem;font-weight:500;color:var(--eco-primary);text-decoration:none}
.eco-sec-head a:hover{text-decoration:underline}
.eco-cats{display:grid;grid-template-columns:repeat(auto-fill,minmax(124px,1fr));gap:.75rem}
.eco-cat{display:flex;align-items:center;gap:.65rem;padding:.7rem .8rem;border-radius:12px;background:var(--eco-surface);border:1px solid var(--eco-line);text-decoration:none;color:var(--eco-ink);font-size:.84rem;font-weight:600;transition:border-color .15s ease,box-shadow .15s ease}
.eco-cat:hover{border-color:rgba(var(--eco-rgb),.45);box-shadow:0 2px 8px rgba(15,23,42,.06)}
.eco-cat-ico{width:34px;height:34px;flex:none;border-radius:9px;display:inline-flex;align-items:center;justify-content:center;background:var(--eco-soft);color:var(--eco-primary)}
.eco-cat-ico .eco-ico{width:18px;height:18px}
.eco-cols{display:grid;grid-template-columns:minmax(0,1.7fr) minmax(0,1fr);gap:1.5rem;align-items:start}
.eco-panel{background:var(--eco-surface);border:1px solid var(--eco-line);border-radius:14px;box-shadow:0 1px 2px rgba(15,23,42,.04);padding:1.1rem 1.25rem}
.eco-rank{display:flex;flex-direction:column}
.eco-rank-row{display:flex;align-items:center;gap:.8rem;padding:.7rem 0;border-bottom:1px solid var(--eco-line)}
.eco-rank-row:last-child{border-bottom:0}
.eco-rank-no{width:28px;height:28px;flex:none;border-radius:8px;background:var(--eco-soft);color:var(--eco-primary);font-size:.75rem;font-weight:700;display:inline-flex;align-items:center;justify-content:center}
.eco-rank-body{flex:1;min-width:0}
.eco-rank-name{font-size:.86rem;font-weight:600;color:var(--eco-ink);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.eco-rank-bar{height:5px;border-radius:999px;background:var(--eco-line);margin-top:.4rem;overflow:hidden}
.eco-rank-bar i{display:block;height:100%;background:var(--eco-primary);border-radius:999px}
.eco-rank-val{font-size:.8rem;font-weight:600;color:var(--eco-ink);white-space:nowrap;font-variant-numeric:tabular-nums}
.eco-items{display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:.9rem}
.eco-item{position:relative;background:var(--eco-surface);border:1px solid var(--eco-line);border-radius:14px;padding:1rem 1.1rem 1rem;box-shadow:0 1px 2px rgba(15,23,42,.04);transition:box-shadow .15s ease,border-color .15s ease}
.eco-item:hover{border-color:rgba(var(--eco-rgb),.4);box-shadow:0 4px 14px rgba(15,23,42,.07)}
.eco-item-badge{display:inline-block;padding:.12rem .55rem;border-radius:6px;background:var(--eco-soft);color:var(--eco-primary);font-size:.68rem;font-weight:600}
.eco-item h3{margin-top:.7rem;font-weight:600;font-size:.95rem;color:var(--eco-ink)}
.eco-item-price{margin-top:.55rem;font-weight:700;font-size:1rem;color:var(--eco-ink);font-variant-numeric:tabular-nums}
.eco-item-meta{font-size:.74rem;color:var(--eco-muted);margin-top:.2rem}
.eco-plus{position:absolute;right:.9rem;bottom:.9rem;width:34px;height:34px;border-radius:999px;display:inline-flex;align-items:center;justify-content:center;background:var(--eco-primary);color:#fff}
.eco-plus:hover{background:var(--eco-deep)}
.eco-plus .eco-ico{width:18px;height:18px}
.eco-empty{font-size:.85rem;color:var(--eco-muted)}
@media(max-width:1100px){.eco-cols{grid-template-columns:1fr}}
@media(max-width:640px){.eco-slide{flex-basis:92%!important;min-height:200px!important;padding:1.25rem!important}.eco-slide-art{width:120px!important}.eco-slide-value{font-size:1.6rem!important}.eco-items{grid-template-columns:repeat(2,minmax(0,1fr));gap:.7rem}.eco-home-tools{min-width:100%}.eco-search{max-width:none}}
@media(max-width:640px){.eco-footer{bottom:calc(8px + env(safe-area-inset-bottom));min-height:60px;border-radius:18px;padding-inline:7px}.eco-footer-link{min-width:52px;padding-inline:5px;font-size:9px}.eco-footer-scan{width:52px;height:52px;min-width:52px;margin-top:-22px}.fi-main{padding-inline:1rem!important;padding-bottom:calc(100px + env(safe-area-inset-bottom))!important}.fi-header-heading{font-size:1.3rem!important}}
@media(min-width:1024px){.fi-main{padding-bottom:112px!important}}
/* ===== EcoWin UI v2 (nama kelas disesuaikan dengan Filament v5) ===== */
.fi-section.fi-section-not-contained{border:0!important;box-shadow:none!important;background:transparent!important}
.fi-section.fi-section-not-contained .fi-section-content-ctn{padding:0!important}
.fi-section{border-radius:20px!important}
.fi-section-header-heading{font-weight:700!important;color:var(--eco-ink)!important}
.fi-sidebar{border-right:1px solid var(--eco-line)!important}
.fi-sidebar-nav{padding-inline:.9rem!important}
.fi-sidebar-item-btn{border-radius:12px!important;padding:.62rem .8rem!important;transition:background .15s ease,color .15s ease}
.fi-sidebar-item-btn:hover{background:var(--eco-soft)!important}
.fi-sidebar-item.fi-active>.fi-sidebar-item-btn{background:var(--eco-primary)!important;box-shadow:0 8px 16px -8px rgba(var(--eco-rgb),.6)}
.fi-sidebar-item.fi-active .fi-sidebar-item-label,.fi-sidebar-item.fi-active .fi-sidebar-item-icon{color:#fff!important;font-weight:600!important}
.fi-sidebar-item-icon{color:#64748B!important}
.fi-sidebar-item-badge-ctn .fi-badge{border-radius:999px!important}
.fi-sidebar-group-label{letter-spacing:.1em!important}
.fi-topbar{box-shadow:none!important}
.fi-header-heading{font-size:1.65rem!important;font-weight:800!important}
.fi-breadcrumbs-item-label{font-size:.8rem}
.fi-header .fi-btn{border-radius:999px!important;padding-inline:1.2rem!important;box-shadow:0 6px 14px -6px rgba(var(--eco-rgb),.55)}
.fi-btn{border-radius:12px!important}
.fi-badge{border-radius:999px!important;font-weight:600!important;padding-inline:.7rem!important}
.fi-ta-ctn{border-radius:20px!important;overflow:hidden;background:var(--eco-surface)!important}
.fi-ta-header-ctn{padding:1rem 1.25rem!important}
.fi-ta-header-toolbar .fi-input-wrp{border-radius:999px!important;background:var(--eco-canvas)}
.fi-ta-header-toolbar .fi-icon-btn{border-radius:12px!important}
.fi-ta-header-cell{background:var(--eco-canvas)!important;padding-block:.8rem!important}
.fi-ta-cell{padding-block:.2rem}
.fi-ta-row{transition:background .12s ease}
.fi-ta-row:hover{background:rgba(var(--eco-rgb),.045)!important}
.fi-ta-actions .fi-link,.fi-ta-actions .fi-btn{font-weight:600}
.fi-input-wrp{border-radius:12px!important}
.fi-fo-field-label{font-weight:600!important;color:var(--eco-ink)}
.fi-wi-stats-overview-stat{border-top:0!important;border-radius:18px!important;padding:1.15rem 1.25rem!important;transition:box-shadow .15s ease}
.fi-wi-stats-overview-stat:hover{box-shadow:0 6px 18px rgba(15,23,42,.08)!important}
.fi-wi-stats-overview-stat .fi-icon{color:var(--eco-primary)!important}
.fi-wi-stats-overview-stat-value{font-weight:800!important}
.fi-wi-stats-overview-stat-label{color:var(--eco-muted)!important;font-weight:600!important}
.fi-wi-stats-overview-stat-label-ctn .fi-icon{padding:6px;border-radius:10px;background:var(--eco-soft);width:2rem;height:2rem}
.fi-btn.fi-color-primary{background:var(--eco-primary)!important;color:#fff!important}
.fi-btn.fi-color-primary:hover{background:var(--eco-deep)!important}
.fi-btn.fi-color-primary .fi-icon,.fi-btn.fi-color-primary .fi-btn-label{color:#fff!important}
@media(max-width:640px){
.fi-ta-content-ctn{overflow:visible!important}
.fi-ta-table,.fi-ta-table tbody{display:block!important;width:100%}
.fi-ta-table thead{display:none!important}
.fi-ta-table tbody.fi-ta-group-header,.fi-ta-table tbody>tr.fi-ta-group-header-row{display:block}
.fi-ta-table .fi-ta-row{display:grid!important;grid-template-columns:repeat(2,minmax(0,1fr));gap:.8rem 1rem;margin:.75rem;padding:1rem;border:1px solid var(--eco-line);border-radius:20px;background:var(--eco-surface);box-shadow:0 1px 3px rgba(15,23,42,.06)}
.fi-ta-table .fi-ta-row:hover{background:var(--eco-surface)!important}
.fi-ta-table .fi-ta-cell{display:flex!important;flex-direction:column;align-items:flex-start;justify-content:flex-start;gap:.22rem;padding:0!important;border:0!important;text-align:left;min-width:0}
.fi-ta-table .fi-ta-cell+.fi-ta-cell{border-top:0!important}
.fi-ta-table .fi-ta-cell[data-label]:not([data-label=""])::before{content:attr(data-label);order:-1;max-width:100%;font-size:.66rem;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:var(--eco-muted)}
.fi-ta-table .fi-ta-cell>*{min-width:0;max-width:100%;overflow-wrap:anywhere}
.fi-ta-table .fi-ta-col,.fi-ta-table .fi-ta-col-wrp,.fi-ta-table .fi-ta-text,.fi-ta-table .fi-ta-text-item{padding:0!important;margin:0!important}
.fi-ta-table .fi-ta-cell:has(.fi-ta-actions){grid-column:1/-1;flex-direction:row!important;flex-wrap:wrap;align-items:center;padding-top:.75rem!important;border-top:1px solid var(--eco-line)!important}
.fi-ta-table .fi-ta-cell:first-child{grid-column:1/-1;padding-bottom:.55rem!important;border-bottom:1px solid var(--eco-line)!important}
.fi-ta-table .fi-ta-cell:first-child .fi-ta-text-item{font-size:1rem;font-weight:800;color:var(--eco-ink)}
.fi-ta-table .fi-ta-actions-cell,.fi-ta-table .fi-ta-cell:last-child:not([data-label]){grid-column:1/-1;flex-direction:row!important;flex-wrap:wrap;align-items:center;gap:.5rem 1.1rem;padding-top:.75rem!important;border-top:1px solid var(--eco-line)!important}
.fi-ta-table .fi-ta-cell:empty{display:none!important}
}
/* ===== Perapian dashboard ===== */
.fi-wi-widget.fi-wi-stats-overview{border:0!important;box-shadow:none!important;background:transparent!important;padding:0!important;--tw-ring-shadow:0 0 #0000!important}
.fi-wi-stats-overview-stat{border-radius:18px!important;padding:1.15rem 1.3rem!important;transition:box-shadow .15s ease,border-color .15s ease}
.fi-wi-stats-overview-stat:hover{border-color:rgba(var(--eco-rgb),.35)!important;box-shadow:0 6px 18px rgba(15,23,42,.07)!important}
.fi-wi-stats-overview-stat-value{font-variant-numeric:tabular-nums;font-weight:800!important}
.fi-wi-stats-overview-stat-description{font-size:.8rem!important}
.eco-cats{grid-template-columns:repeat(var(--cols,4),minmax(0,1fr))}
.eco-cat{min-width:0}
.eco-cat span:last-child{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.eco-home-name{font-size:1.2rem}
.eco-sec-head h2{font-size:1.05rem}
.eco-item,.eco-panel,.eco-card{transition:box-shadow .15s ease,border-color .15s ease}
.eco-item:hover{border-color:rgba(var(--eco-rgb),.4);box-shadow:0 6px 18px rgba(15,23,42,.07)}
.fi-wi-chart,.fi-wi-table,.fi-ta-ctn{overflow:hidden}
@media(max-width:900px){.eco-cats{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:640px){
.eco-item{padding:.95rem 1rem}
.eco-item h3{padding-right:2.4rem}
.eco-plus{top:.8rem;right:.8rem;bottom:auto;width:32px;height:32px}
.eco-item-meta{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.fi-wi-stats-overview .fi-section-content.fi-grid{grid-template-columns:repeat(2,minmax(0,1fr))!important;gap:.75rem!important}
.fi-wi-stats-overview-stat{padding:.9rem 1rem!important;border-radius:16px!important}
.fi-wi-stats-overview-stat-value{font-size:1.25rem!important}
.fi-wi-stats-overview-stat-label{font-size:.78rem!important}
.fi-wi-stats-overview-stat-description{font-size:.72rem!important;line-height:1.3}
}
.eco-report-grid-4{grid-template-columns:repeat(4,minmax(0,1fr))}
@media(max-width:1180px){.eco-report-grid-4{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:640px){.eco-report-grid-4{grid-template-columns:minmax(0,1fr)}}
/* ===== Halaman Organik (cluster): tab, judul, tabel ===== */
.fi-page-has-sub-navigation .fi-breadcrumbs{display:none!important}

.fi-page-has-sub-navigation .fi-header-heading{font-size:1.6rem!important}
/* Organik di HP: tab bisa digeser, judul + tombol sebaris, kartu angka ringkas */
@media(max-width:767px){
.fi-page-sub-navigation-dropdown{display:none!important}
.fi-page-has-sub-navigation .fi-header{flex-direction:row!important;align-items:center!important;justify-content:space-between;gap:.75rem;margin-bottom:.75rem}
.fi-page-has-sub-navigation .fi-header-actions-ctn .fi-btn{padding:.5rem .85rem!important;font-size:.8rem!important;box-shadow:none!important}
.fi-page-has-sub-navigation .fi-wi-stats-overview-stat{padding:.75rem .85rem!important}
.fi-page-has-sub-navigation .fi-wi-stats-overview-stat-label{font-size:.72rem!important}
.fi-page-has-sub-navigation .fi-wi-stats-overview-stat-value{font-size:1.15rem!important}
.fi-page-has-sub-navigation .fi-wi-stats-overview-stat-description{display:none}
.fi-page-has-sub-navigation .fi-ta-header-ctn{padding-block:.5rem 0!important}.fi-page-has-sub-navigation .fi-ta-header-toolbar{padding:.25rem .75rem!important;min-height:0!important}
}
/* ===== Pop-up pengingat tugas menunggu ===== */
.eco-popup{position:fixed;inset:0;z-index:60;display:flex;align-items:center;justify-content:center;padding:1rem;background:rgba(15,23,42,.45);backdrop-filter:blur(2px)}
.eco-popup-card{width:min(440px,100%);max-height:90vh;overflow:auto;background:#fff;border-radius:22px;padding:1.5rem;box-shadow:0 24px 60px rgba(15,23,42,.25);text-align:center}
.eco-popup-ico{width:54px;height:54px;margin:0 auto .75rem;border-radius:16px;display:flex;align-items:center;justify-content:center;background:var(--eco-soft);color:var(--eco-primary)}
.eco-popup-ico .eco-ico{width:28px;height:28px}
.eco-popup-card h2{font-size:1.2rem;font-weight:800;color:var(--eco-ink)}
.eco-popup-card>p{margin:.35rem 0 1rem;font-size:.85rem;color:var(--eco-muted)}
.eco-popup-card ul{list-style:none;margin:0 0 1rem;padding:0;display:grid;gap:.5rem;text-align:left}
.eco-popup-card li a{display:flex;align-items:center;justify-content:space-between;gap:.75rem;padding:.75rem .9rem;border:1px solid var(--eco-line);border-radius:14px;text-decoration:none;color:var(--eco-ink);transition:border-color .15s ease,background .15s ease}
.eco-popup-card li a:hover{border-color:var(--eco-primary);background:var(--eco-soft)}
.eco-popup-card li b{display:block;font-size:.9rem}
.eco-popup-card li small{display:block;font-size:.75rem;color:var(--eco-muted)}
.eco-popup-card li em{font-style:normal;min-width:30px;padding:.15rem .55rem;border-radius:999px;background:#DC2626;color:#fff;font-size:.8rem;font-weight:700;text-align:center}
.eco-popup-card>button{width:100%;padding:.7rem;border-radius:12px;border:1px solid var(--eco-line);background:#fff;font-weight:600;color:var(--eco-muted);cursor:pointer}
.eco-popup-card>button:hover{background:var(--eco-canvas)}
.eco-popup-card li{border:1px solid var(--eco-line);border-radius:14px;overflow:hidden}
.eco-popup-card li a.eco-popup-head{border:0;border-radius:0;background:#fff}
.eco-popup-items{border-top:1px solid var(--eco-line);background:var(--eco-canvas);padding:.25rem .5rem}
.eco-popup-items a{display:flex;align-items:center;justify-content:space-between;gap:.5rem;padding:.5rem .6rem;border:0!important;border-radius:10px!important;font-size:.82rem}
.eco-popup-items a:hover{background:#fff!important}
.eco-popup-items small{margin:0;white-space:nowrap}
.eco-popup-items a.eco-popup-more{color:var(--eco-primary);font-weight:600;justify-content:center}
/* ===== Gaya "aplikasi Nasabah" untuk Dashboard Admin & Petugas =====
   Bentuk, jarak, dan hierarki sama dengan Beranda Nasabah (Web & Android); warna mengikuti role
   (Admin = Indigo, Petugas = Emerald). Semua fitur dan data tetap sama. */
.eco-home{gap:1.25rem}
.eco-avatar{width:52px;height:52px}
.eco-home-hello{font-size:.9rem;color:var(--eco-muted);line-height:1.2}
.eco-home-name{font-size:1.45rem;font-weight:800;letter-spacing:-.015em;line-height:1.2}
.eco-home-loc{margin-top:.3rem}
.eco-search,.eco-icon-btn{border-radius:999px}
.eco-search{height:46px;padding:0 1.1rem}
.eco-icon-btn{width:46px;height:46px}

/* Kartu utama: gradien lembut dengan lingkaran dekoratif, tombol kaca (bukan kuning) */
.eco-slide{min-height:210px;padding:1.6rem 1.75rem;border-radius:28px;background:linear-gradient(135deg,var(--eco-primary),var(--eco-secondary));box-shadow:0 14px 32px rgba(var(--eco-rgb),.22)}
.eco-slide:nth-child(even){background:linear-gradient(135deg,var(--eco-deep),var(--eco-primary))}
.eco-slide::before{content:"";position:absolute;right:-48px;top:-56px;width:210px;height:210px;border-radius:999px;background:rgba(255,255,255,.14);pointer-events:none}
.eco-slide-badge{background:rgba(255,255,255,.2);color:#fff;border:1px solid rgba(255,255,255,.35);font-weight:700}
.eco-slide-label{color:rgba(255,255,255,.88)}
.eco-slide-value{font-size:2.2rem;letter-spacing:-.02em}
.eco-slide-cta{background:rgba(255,255,255,.18);color:#fff;border:1px solid rgba(255,255,255,.55);padding:.55rem 1.3rem}
.eco-slide-cta:hover{background:rgba(255,255,255,.3)}
.eco-art-core{background:#fff}
.eco-slides{padding-bottom:20px;margin-bottom:-14px}

/* Akses cepat: satu kartu putih berisi kapsul yang bisa digeser (seperti "Aksi cepat" Nasabah) */
section[aria-label="Akses cepat"]{background:#fff;border:1px solid var(--eco-line);border-radius:24px;padding:1.1rem 1.25rem 1.25rem;box-shadow:0 1px 3px rgba(15,23,42,.05)}
section[aria-label="Akses cepat"] .eco-sec-head{margin-bottom:.9rem}
section[aria-label="Akses cepat"] .eco-sec-head h2{font-size:1.05rem;font-weight:800}
.eco-cats{display:flex!important;gap:.75rem;overflow-x:auto;scrollbar-width:none;-webkit-overflow-scrolling:touch;padding:2px 2px 4px;scroll-snap-type:x proximity}
.eco-cats::-webkit-scrollbar{display:none}
.eco-cat{flex:none;scroll-snap-align:start;gap:.7rem;padding:.4rem 1.15rem .4rem .4rem;border-radius:999px;background:#F1F5F9;border:1px solid var(--eco-line);font-size:.92rem;font-weight:700;white-space:nowrap}
.eco-cat:first-child{background:linear-gradient(90deg,var(--eco-soft),rgba(var(--eco-rgb),.32));border-color:rgba(var(--eco-rgb),.25)}
.eco-cat-ico{width:46px;height:46px;border-radius:999px;background:#fff;border:1px solid var(--eco-line);box-shadow:0 1px 2px rgba(15,23,42,.06)}
.eco-cat-ico .eco-ico{width:22px;height:22px}

/* Judul bagian dan kartu isi: radius dan bayangan seragam */
.eco-sec-head h2{font-size:1.05rem;font-weight:800}
.eco-panel,.eco-item{border-radius:22px;padding-inline:1.25rem}
.eco-item{padding-block:1.1rem}
.eco-item-badge{border-radius:999px}
.eco-plus{box-shadow:0 4px 10px rgba(var(--eco-rgb),.28)}
.eco-rank-no{border-radius:999px}

/* Kartu statistik Filament: ikon dalam kotak lembut di atas, label, angka tebal, keterangan (seperti StatTile) */
.fi-wi-stats-overview-stat{border-radius:22px!important;padding:1.1rem 1.15rem!important;box-shadow:0 1px 3px rgba(15,23,42,.05)!important}
.fi-wi-stats-overview-stat-label-ctn{flex-direction:column!important;align-items:flex-start!important;gap:.7rem!important}
.fi-wi-stats-overview-stat-label-ctn .fi-icon{box-sizing:border-box;width:42px!important;height:42px!important;padding:10px;border-radius:14px;background:var(--eco-soft);color:var(--eco-primary)!important}
.fi-wi-stats-overview-stat-label{font-size:.86rem!important;font-weight:500!important}
.fi-wi-stats-overview-stat-value{font-size:1.65rem!important;font-weight:800!important;letter-spacing:-.015em}
.fi-wi-stats-overview-stat-description{color:var(--eco-muted)!important}

/* Tabel dan grafik di dashboard: kartu membulat seperti daftar Nasabah */
.fi-wi-widget .fi-ta-ctn,.fi-wi-chart,.fi-wi-widget .fi-section{border-radius:24px!important}
.fi-wi-widget .fi-ta-header-heading,.fi-wi-chart .fi-section-header-heading{font-weight:800!important;font-size:1.05rem!important}
.fi-ta-row{transition:background .12s ease}

@media(max-width:640px){
  .eco-home-name{font-size:1.25rem}
  .eco-slide{border-radius:26px;padding:1.25rem}
  .eco-slide::before{right:-70px;top:-70px;width:170px;height:170px}
  section[aria-label="Akses cepat"]{border-radius:22px;padding:1rem}
  .eco-cat{padding-right:1rem;font-size:.88rem}
  .eco-cat-ico{width:42px;height:42px}
}
/* ===== Formulir: kolom isian harus jelas terlihat (WCAG 1.4.11: garis tepi >= 3:1) =====
   Sebelumnya bayangan/ring Filament dimatikan sehingga kolom tampak kosong tanpa garis. */
.fi-input-wrp{background:#fff!important;border:1.5px solid #8392A6!important;border-radius:12px!important;box-shadow:0 1px 2px rgba(15,23,42,.05)!important;--tw-ring-shadow:0 0 #0000!important;--tw-ring-color:transparent!important;min-height:46px;transition:border-color .12s ease,box-shadow .12s ease}
.fi-input-wrp:hover{border-color:#64748B!important}
.fi-input-wrp:focus-within{border-color:var(--eco-primary)!important;box-shadow:0 0 0 4px rgba(var(--eco-rgb),.20)!important}
.fi-input-wrp:has([aria-invalid="true"]),.fi-input-wrp:has(.fi-invalid){border-color:#DC2626!important}
.fi-input-wrp:has(:disabled){background:#F1F5F9!important;border-style:dashed!important;border-color:#A8B3C4!important}
.fi-input,.fi-select-input{color:var(--eco-ink)!important;font-size:.95rem!important;font-weight:500;background:transparent!important}
.fi-input::placeholder,.fi-select-input::placeholder{color:#64748B!important;opacity:1!important;font-weight:400}
.fi-input:disabled{color:#475569!important;-webkit-text-fill-color:#475569}
textarea.fi-input{min-height:96px;padding-block:.65rem}
.fi-fo-field-label,.fi-fo-field-label-content{color:var(--eco-ink)!important;font-weight:600!important;font-size:.88rem!important}
.fi-fo-field-label-required-mark{color:#DC2626!important}
.fi-fo-field-wrp-hint,.fi-fo-field-wrp-helper-text{color:#475569!important}
/* Awalan/akhiran (Rp, kg): kotak tersendiri yang terbaca */
.fi-fo-field .fi-input-wrp-prefix,.fi-fo-field .fi-input-wrp-suffix,.fi-fo-field .fi-input-wrp [class*="fi-input-wrp-prefix"],.fi-fo-field .fi-input-wrp [class*="fi-input-wrp-suffix"]{color:#334155!important;font-weight:600;background:#F1F5F9;border-color:#CBD5E1!important}
/* Kotak centang, radio, sakelar */
input[type="checkbox"].fi-checkbox-input,input[type="radio"].fi-radio-input{border:1.5px solid #8392A6!important;background-color:#fff;box-shadow:none!important;width:1.15rem;height:1.15rem}
input[type="checkbox"].fi-checkbox-input:checked,input[type="radio"].fi-radio-input:checked{background-color:var(--eco-primary)!important;border-color:var(--eco-primary)!important}
input[type="checkbox"].fi-checkbox-input:focus-visible,input[type="radio"].fi-radio-input:focus-visible{outline:3px solid rgba(var(--eco-rgb),.35);outline-offset:2px}
.fi-toggle{--tw-ring-color:#8392A6}
.fi-toggle.fi-toggle-off{background-color:#94A3B8!important}
/* Unggah berkas */
.filepond--root .filepond--panel-root{background:#F8FAFC!important;border:2px dashed #8392A6;border-radius:14px}
.filepond--drop-label{color:#334155!important;font-weight:500}
.filepond--label-action{color:var(--eco-primary)!important;text-decoration-color:var(--eco-primary)}
/* Pilihan (dropdown) dan pemilih tanggal */
.fi-fo-select .choices__inner,.fi-dropdown-panel{border-color:#8392A6}
.fi-fo-date-time-picker input,.fi-fo-date-time-picker button{color:var(--eco-ink)}
/* Tombol di form: aksi sekunder tetap terbaca */
.fi-btn.fi-color-gray,.fi-btn:not(.fi-color-primary):not(.fi-color-danger):not(.fi-color-success):not(.fi-color-warning){border:1.5px solid #8392A6}
/* ===== Perapian tabel & halaman info ===== */
/* Indikator memuat Livewire tidak boleh tampil sebelum Livewire aktif (Livewire sendiri yang menampilkannya lewat gaya inline). */
.fi-loading-indicator[wire\:loading\.delay\.default],.fi-loading-indicator[wire\:loading]{display:none}
.fi-ta-search-field .fi-input-wrp{border-radius:999px!important;min-height:42px}
.fi-ta-search-field .fi-input-wrp-prefix,.fi-ta-search-field [class*="fi-input-wrp-prefix"]{background:transparent!important;border:0!important}
.fi-pagination-records-per-page-select .fi-input-wrp,.fi-ta-pagination .fi-input-wrp{min-height:40px;border-radius:10px!important}
.fi-pagination-records-per-page-select .fi-input-wrp-prefix{background:transparent!important}
.fi-ta-table .fi-badge{font-weight:700}
.eco-report-row b,.eco-report-row span{overflow-wrap:anywhere;min-width:0}
.eco-report-row{align-items:baseline}

/* ===== Perbaikan UI admin (audit tampilan nyata, 23 Okt 2026) ===== */
/* Tabel: lebih padat dan ada bayangan di tepi bila masih bisa digeser ke samping (sebelumnya kolom terakhir terpotong tanpa petunjuk) */
.fi-ta-table{font-size:.8125rem}
.fi-ta-header-cell{padding:.65rem .5rem .65rem .75rem!important;white-space:nowrap}
.fi-ta-header-cell:first-child{padding-left:1rem!important}
.fi-ta-cell{padding-inline:.5rem .25rem!important}
.fi-ta-cell:first-child{padding-left:1rem!important}
.fi-ta-content-ctn{background:linear-gradient(to right,#fff 30%,rgba(255,255,255,0)) left center,linear-gradient(to left,#fff 30%,rgba(255,255,255,0)) right center,radial-gradient(farthest-side at 0 50%,rgba(15,23,42,.16),rgba(15,23,42,0)) left center,radial-gradient(farthest-side at 100% 50%,rgba(15,23,42,.16),rgba(15,23,42,0)) right center;background-repeat:no-repeat;background-size:44px 100%,44px 100%,16px 100%,16px 100%;background-attachment:local,local,scroll,scroll}
.fi-ta-content-ctn::-webkit-scrollbar{height:10px}
.fi-ta-content-ctn::-webkit-scrollbar-thumb{background:#CBD5E1;border-radius:99px;border:2px solid #fff}
.fi-ta-content-ctn::-webkit-scrollbar-track{background:transparent}
/* Bilah alat tabel: tombol aksi (mis. Scan QR) di kiri, pencarian dan filter di kanan, dalam satu baris */
.fi-ta-header-ctn{display:flex!important;flex-wrap:wrap;align-items:center;gap:.5rem .75rem;padding:.75rem 1rem!important}
.fi-ta-header,.fi-ta-header-toolbar{display:contents!important}
.fi-ta-header-ctn>.fi-ta-header-toolbar>div:last-child{margin-inline-start:auto}
/* Kolom aksi tetap terlihat di kanan saat tabel digeser ke samping */
.fi-ta-table th.fi-ta-actions-header-cell,.fi-ta-table td:has(>.fi-ta-actions){position:sticky;right:0;z-index:2;background:#fff}
.fi-ta-table td:has(>.fi-ta-actions){box-shadow:-10px 0 10px -10px rgba(15,23,42,.22)}
.fi-ta-table .fi-ta-row:hover td:has(>.fi-ta-actions){background:#F8FAFC}
.fi-ta-filters-dropdown .fi-icon-btn svg{width:1.15rem;height:1.15rem}
.fi-ta-filters-dropdown .fi-icon-btn svg path{fill:none;stroke:currentColor;stroke-width:1.5}
.fi-ta-filters-dropdown .fi-icon-btn{border:1px solid var(--eco-line);border-radius:12px;background:#fff}
.fi-ta-record-actions .fi-link,.fi-ta-actions .fi-link{white-space:nowrap}
/* Formulir: bagian selebar penuh, bantalan dalam lebih ramping, kolom isian tidak mengecil */
.fi-sc-form .fi-section-content{padding:1.1rem 1.25rem!important}
.fi-sc-form .fi-section-header{padding:.9rem 1.25rem!important}
.fi-fo-field-label-content{font-size:.8125rem;font-weight:700}
.fi-fo-checkbox-list .fi-fo-checkbox-list-option-label{white-space:nowrap}
.fi-form-actions,.fi-sc-actions{margin-top:.5rem}

/* Tab sub-navigasi (mis. Organik): tombol segmented yang jelas bisa diklik, tab aktif terisi warna peran (menggantikan gaya garis bawah lama) */
.fi-page-sub-navigation-tabs{display:flex!important;margin-inline:0!important;flex-wrap:wrap;gap:.35rem;width:fit-content;max-width:100%;padding:.35rem!important;margin-bottom:1.25rem!important;background:#EEF2F7;border:1px solid var(--eco-line)!important;border-radius:18px}
.fi-page-sub-navigation-tabs .fi-tabs-item{display:inline-flex!important;align-items:center;gap:.5rem;padding:.6rem 1.05rem!important;border-radius:13px!important;border:1px solid transparent;background:transparent;font-size:.875rem;font-weight:700;color:var(--eco-muted)!important;transition:background .15s ease,color .15s ease,box-shadow .15s ease}
.fi-page-sub-navigation-tabs .fi-tabs-item:hover{background:#fff;color:#0F172A!important}
.fi-page-sub-navigation-tabs .fi-tabs-item.fi-active{background:var(--eco-primary)!important;color:#fff!important;box-shadow:0 4px 12px rgba(var(--eco-rgb),.28)}
.fi-page-sub-navigation-tabs .fi-tabs-item.fi-active *{color:#fff!important}
.fi-page-sub-navigation-tabs .fi-tabs-item .fi-badge{margin-inline-start:.15rem}
.fi-page-sub-navigation-tabs .fi-tabs-item.fi-active .fi-badge{background:rgba(255,255,255,.22)!important;--tw-ring-color:rgba(255,255,255,.4)}
@media(max-width:640px){.fi-page-sub-navigation-tabs{width:100%;flex-wrap:nowrap;overflow-x:auto;scrollbar-width:none}.fi-page-sub-navigation-tabs .fi-tabs-item{white-space:nowrap;flex:none}}
/* Kontrol pengelompokan tabel: dua kotak pilihan sejajar, bukan gelembung bulat */
.fi-ta-grouping-settings-fields{display:flex!important;flex-wrap:wrap;align-items:center;gap:.5rem}
.fi-ta-grouping-settings-fields label{margin:0}
.fi-ta-grouping-settings-fields .fi-input-wrp{border-radius:12px!important;min-height:2.5rem;background:#fff;align-items:center}
.fi-ta-grouping-settings-fields .fi-input-wrp-prefix{padding-inline:.75rem .5rem}
.fi-ta-grouping-settings-fields .fi-input-wrp-label{font-size:.78rem;font-weight:600;color:var(--eco-muted)}
.fi-ta-grouping-settings-fields .fi-select-input{height:2.4rem;padding-block:0!important;line-height:2.4rem;font-size:.85rem;font-weight:600}
/* Header kolom aksi menyatu dengan baris header lain; tombol aksi header (mis. Scan QR) tetap di kiri */
.fi-ta-table th.fi-ta-actions-header-cell{background:#F8FAFC!important}
.fi-ta-header-ctn>.fi-ta-header>.fi-ta-actions{margin-inline:0 auto!important}
.fi-ta-header-ctn>.fi-ta-header-toolbar>.fi-ta-actions{margin-inline:0!important}
.fi-ta-filters-dropdown .fi-badge.eco-nol{display:none!important}
</style>
