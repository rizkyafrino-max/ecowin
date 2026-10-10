@php
    $ecoIsAdmin = auth()->user()?->isAdmin() ?? false;
    // Identitas warna per role: Admin = Indigo, Petugas = Emerald.
    $eco = $ecoIsAdmin
        ? ['primary' => '#4F46E5', 'secondary' => '#6366F1', 'deep' => '#3730A3', 'soft' => '#EEF2FF', 'rgb' => '79,70,229']
        : ['primary' => '#059669', 'secondary' => '#10B981', 'deep' => '#047857', 'soft' => '#ECFDF5', 'rgb' => '5,150,105'];
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
.fi-ta-table .fi-ta-row{display:block!important;margin:.75rem;padding:.35rem .95rem;border:1px solid var(--eco-line);border-radius:16px;background:var(--eco-surface);box-shadow:0 1px 2px rgba(15,23,42,.05)}
.fi-ta-table .fi-ta-row:hover{background:var(--eco-surface)!important}
.fi-ta-table .fi-ta-cell{display:flex!important;align-items:center;justify-content:space-between;gap:1rem;padding:.55rem 0!important;border:0!important;text-align:right}
.fi-ta-table .fi-ta-cell+.fi-ta-cell{border-top:1px dashed var(--eco-line)!important}
.fi-ta-table .fi-ta-cell[data-label]:not([data-label=""])::before{content:attr(data-label);flex:none;max-width:42%;text-align:left;font-size:.68rem;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:var(--eco-muted)}
.fi-ta-table .fi-ta-cell>*{min-width:0;max-width:100%}
.fi-ta-table .fi-ta-actions-cell,.fi-ta-table .fi-ta-cell:last-child:not([data-label]){justify-content:flex-end}
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

.fi-page-has-sub-navigation .fi-tabs.fi-page-sub-navigation-tabs{margin-bottom:1.25rem!important;background:transparent!important;box-shadow:none!important;border:0!important;border-bottom:1px solid var(--eco-line)!important;border-radius:0!important;padding:0!important;max-width:none!important;width:100%;justify-content:flex-start;gap:.25rem;margin:0!important}
.fi-page-has-sub-navigation .fi-tabs-item{border-radius:0!important;background:transparent!important;padding:.8rem 1.05rem!important;font-weight:600;color:var(--eco-muted);border-bottom:2px solid transparent;margin-bottom:-1px;transition:color .15s ease,border-color .15s ease}
.fi-page-has-sub-navigation .fi-tabs-item:hover{color:var(--eco-primary)}
.fi-page-has-sub-navigation .fi-tabs-item.fi-active{color:var(--eco-primary)!important;border-bottom-color:var(--eco-primary)}
.fi-page-has-sub-navigation .fi-tabs-item.fi-active .fi-tabs-item-label{color:var(--eco-primary)!important}
.fi-page-has-sub-navigation .fi-header-heading{font-size:1.6rem!important}
/* Organik di HP: tab bisa digeser, judul + tombol sebaris, kartu angka ringkas */
@media(max-width:767px){
.fi-page-sub-navigation-dropdown{display:none!important}
.fi-tabs.fi-page-sub-navigation-tabs{display:flex!important;overflow-x:auto;white-space:nowrap;scrollbar-width:none;margin-inline:-1rem!important;padding-inline:1rem!important;width:auto!important;margin-bottom:1rem!important}
.fi-tabs.fi-page-sub-navigation-tabs::-webkit-scrollbar{display:none}
.fi-page-has-sub-navigation .fi-tabs-item{padding:.7rem .85rem!important;font-size:.85rem;flex:none}
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
</style>
