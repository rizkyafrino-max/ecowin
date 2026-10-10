<style>
        .ecl-root{--g1:#A3E635;--g2:#22C55E;--g3:#15803D;position:fixed;inset:0;z-index:100;display:grid;grid-template-columns:minmax(0,1.05fr) minmax(0,1fr);grid-template-rows:1fr auto;grid-template-areas:"top form" "bottom form";background:#fff;overflow:auto;color:#0F172A;-webkit-text-size-adjust:100%}
        .ecl-bg{grid-column:1;grid-row:1/3;background-color:#050B07;background-image:radial-gradient(560px 460px at 85% 0%,rgba(34,197,94,.28),transparent 70%),radial-gradient(460px 400px at 0% 100%,rgba(163,230,53,.10),transparent 70%)}
        .ecl-top,.ecl-bottom{color:#fff;position:relative;z-index:1}
        .ecl-top{grid-area:top;padding:clamp(1.75rem,4vw,3.25rem) clamp(1.5rem,5vw,4rem) 1rem;display:flex;flex-direction:column;justify-content:center;gap:clamp(1.5rem,3vw,2.5rem)}
        .ecl-bottom{grid-area:bottom;padding:1.25rem clamp(1.5rem,5vw,4rem) clamp(1.75rem,4vw,3.25rem)}
        .ecl-logo{display:block;width:clamp(120px,14vw,168px);height:auto;margin-left:-.5rem}
        .ecl-eyebrow{display:inline-flex;align-items:center;gap:.5rem;font-size:.74rem;font-weight:600;letter-spacing:.14em;text-transform:uppercase;color:var(--g1)}
        .ecl-eyebrow::before{content:"";width:22px;height:2px;border-radius:2px;background:var(--g1)}
        .ecl-top h1{margin-top:.9rem;font-size:clamp(1.85rem,3.4vw,2.9rem);line-height:1.15;font-weight:800;max-width:20ch}
        .ecl-top h1 em{font-style:normal;background:linear-gradient(90deg,var(--g1),var(--g2));-webkit-background-clip:text;background-clip:text;color:transparent}
        .ecl-top p{margin-top:1.1rem;max-width:50ch;color:rgba(255,255,255,.72);font-size:clamp(.9rem,1.2vw,1.02rem);line-height:1.65}
        .ecl-feats{display:grid;gap:1rem;border-top:1px solid rgba(255,255,255,.12);padding-top:1.25rem}
        .ecl-feat{display:flex;gap:.9rem;align-items:flex-start}
        .ecl-feat-ico{width:38px;height:38px;flex:none;border-radius:11px;display:flex;align-items:center;justify-content:center;background:rgba(34,197,94,.14);border:1px solid rgba(163,230,53,.28);color:var(--g1)}
        .ecl-feat-ico svg{width:19px;height:19px}
        .ecl-feat b{display:block;font-size:.92rem;font-weight:700}
        .ecl-feat div>span{display:block;margin-top:.15rem;font-size:.8rem;line-height:1.55;color:rgba(255,255,255,.62)}
        .ecl-form{grid-area:form;display:flex;align-items:center;justify-content:center;padding:2.5rem 1.5rem}
        .ecl-card{width:100%;max-width:440px}
        .ecl-icon{display:none}
        .ecl-icon img{width:38px;height:auto}
        .ecl-kicker{display:inline-block;padding:.3rem .8rem;border-radius:999px;background:#F0FDF4;border:1px solid #BBF7D0;color:#15803D;font-size:.72rem;font-weight:700;letter-spacing:.06em}
        .ecl-card h2{margin-top:1.1rem;font-size:2.1rem;line-height:1.15;font-weight:800;color:#0F172A}
        .ecl-sub{margin-top:.6rem;font-size:1rem;color:#64748B;line-height:1.6}
        .ecl-btn{margin-top:2rem;display:flex;align-items:center;justify-content:center;gap:.75rem;width:100%;min-height:58px;border-radius:12px;border:1px solid #CBD5E1;background:#fff;color:#0F172A;font-size:1rem;font-weight:600;text-decoration:none;transition:border-color .15s ease,box-shadow .15s ease,background .15s ease}
        .ecl-btn:hover{border-color:#16A34A;background:#F0FDF4;box-shadow:0 0 0 3px rgba(22,163,74,.14)}
        .ecl-btn svg{width:22px;height:22px;flex:none}
        .ecl-err{margin-top:1.5rem;padding:.8rem 1rem;border-radius:12px;background:#FEF2F2;color:#B91C1C;border:1px solid #FECACA;font-size:.875rem;line-height:1.5}
        .ecl-note{margin-top:1.4rem;display:flex;gap:.6rem;align-items:flex-start;font-size:.88rem;line-height:1.55;color:#64748B}
        .ecl-note svg{width:18px;height:18px;flex:none;margin-top:1px;color:#16A34A}
        .ecl-roles{margin-top:1.75rem;padding-top:1.25rem;border-top:1px solid #E2E8F0;display:flex;gap:.5rem;flex-wrap:wrap;align-items:center;font-size:.84rem;color:#64748B}
        .ecl-pill{padding:.2rem .65rem;border-radius:999px;font-weight:700;font-size:.68rem;letter-spacing:.1em}
        .ecl-pill-admin{background:#EEF2FF;color:#4F46E5}
        .ecl-pill-petugas{background:#ECFDF5;color:#059669}
        .ecl-foot{margin-top:1.75rem;font-size:.74rem;color:#94A3B8}
        @media(max-width:960px){
            .ecl-root{display:flex;flex-direction:column;overflow-x:hidden;background:#050B07}
            .ecl-bg{display:none}
            .ecl-root>*{flex:none}
            .ecl-top{padding:1.75rem 1.25rem 1.5rem;gap:1rem;align-items:center;text-align:center;background-image:radial-gradient(420px 320px at 50% 0%,rgba(34,197,94,.32),transparent 72%)}
            .ecl-logo{width:104px;margin:0 auto}
            .ecl-eyebrow{font-size:.66rem;letter-spacing:.12em}
            .ecl-eyebrow::before{display:none}
            .ecl-top h1{max-width:none;margin-top:.6rem;font-size:1.5rem}
            .ecl-top p{display:none}
            .ecl-form{order:2;padding:0 1rem;align-items:flex-start;background:transparent}
            .ecl-card{max-width:520px;padding:1.5rem 1.25rem;background:#fff;border-radius:20px;box-shadow:0 16px 36px rgba(0,0,0,.35)}
            .ecl-card h2{margin-top:.9rem;font-size:1.4rem}
            .ecl-sub{font-size:.88rem}
            .ecl-btn{margin-top:1.25rem}
            .ecl-bottom{display:none}
            .ecl-feats{border-top:0;padding-top:0;gap:1.1rem}
            .ecl-feat-ico{background:rgba(34,197,94,.22);border-color:rgba(163,230,53,.5)}
        }
        @media(max-width:380px){.ecl-top h1{font-size:1.35rem}.ecl-card{padding:1.25rem 1rem}}
        .ecl-hint{margin-top:.35rem;font-size:.78rem;color:#64748B}
        .ecl-ok{margin-top:1rem;padding:.75rem 1rem;border-radius:12px;background:#F0FDF4;color:#15803D;border:1px solid #BBF7D0;font-size:.875rem;font-weight:600}
        .ecl-link-btn{background:none;border:0;padding:0;font:inherit;cursor:pointer}
        .ecl-link{color:#15803D;font-weight:700;text-decoration:none}
        .ecl-link:hover{text-decoration:underline}
        .ecl-or{margin-top:1.4rem;text-align:center;font-size:.9rem;color:#64748B}
        .ecl-field{margin-top:1rem}
        .ecl-field label{display:block;margin-bottom:.4rem;font-size:.84rem;font-weight:600;color:#0F172A}
        .ecl-input{width:100%;min-height:48px;padding:.65rem .9rem;border-radius:12px;border:1px solid #CBD5E1;background:#fff;color:#0F172A;font:inherit;font-size:.95rem}
        .ecl-input:focus{outline:0;border-color:#16A34A;box-shadow:0 0 0 3px rgba(22,163,74,.14)}
        .ecl-input[readonly]{background:#F1F5F9;color:#64748B}
        .ecl-field-err{margin-top:.35rem;font-size:.78rem;font-weight:600;color:#B91C1C}
        .ecl-check{display:flex;gap:.6rem;align-items:flex-start;margin-top:1.1rem;font-size:.84rem;line-height:1.5;color:#475569}
        .ecl-check input{margin-top:.2rem;width:18px;height:18px;accent-color:#16A34A;flex:none}
        .ecl-btn-primary{margin-top:1.5rem;display:flex;align-items:center;justify-content:center;width:100%;min-height:54px;border:0;border-radius:12px;background:#15803D;color:#fff;font:inherit;font-size:1rem;font-weight:700;cursor:pointer;text-decoration:none}
        .ecl-btn-primary:hover{background:#166534}
        .ecl-steps{margin-top:1.25rem;display:flex;gap:.5rem;font-size:.74rem;font-weight:700;color:#64748B}
        .ecl-steps span{padding:.25rem .65rem;border-radius:999px;background:#F1F5F9}
        .ecl-steps .on{background:#DCFCE7;color:#15803D}
        .ecl-who{margin-top:1.25rem;display:flex;gap:.75rem;align-items:center;padding:.8rem 1rem;border-radius:14px;background:#F8FAFC;border:1px solid #E2E8F0}
        .ecl-who img{width:40px;height:40px;border-radius:999px;object-fit:cover;flex:none}
        .ecl-who b{display:block;font-size:.88rem}
        .ecl-who span{font-size:.78rem;color:#64748B}
    
        /* ===== Desain ulang: panel kiri terang, ilustrasi organik, tipografi tegas ===== */
        .ecl-root{background:#F8FAFC;grid-template-columns:minmax(0,1.05fr) minmax(0,1fr);grid-template-rows:1fr;grid-template-areas:"top form"}
        .ecl-bg{background:#ECFDF5;grid-row:1}
        .ecl-top{grid-area:top;color:#0F172A;justify-content:space-between;gap:1.5rem;padding:clamp(1.75rem,4vw,3rem) clamp(1.5rem,5vw,4.5rem)}
        .ecl-brand{display:flex;align-items:center;gap:.7rem}
        .ecl-mark{width:44px;height:44px;border-radius:14px;background:#000;display:inline-flex;align-items:center;justify-content:center}
        .ecl-mark img{width:30px;height:auto}
        .ecl-word{font-size:1.45rem;font-weight:800;color:#0F172A;letter-spacing:-.01em}
        .ecl-word b{color:#059669}
        .ecl-hero h1{margin:0;font-size:clamp(2rem,3.6vw,3.1rem);line-height:1.1;font-weight:800;color:#0F172A;letter-spacing:-.02em;max-width:none}
        .ecl-hero h1 em{font-style:normal;color:#059669;background:none;-webkit-text-fill-color:#059669}
        .ecl-hero p{margin:1rem 0 0;max-width:34ch;color:#475569;font-size:clamp(1rem,1.3vw,1.12rem);line-height:1.6}
        .ecl-illus{width:min(100%,520px);height:auto;align-self:center;margin:0 auto}
        .ecl-top .ecl-tagline{margin:0;color:#047857;max-width:none;font-size:.95rem;font-weight:600;letter-spacing:.01em}
        .ecl-tagline::before{content:"";display:inline-block;width:26px;height:2px;border-radius:2px;background:#10B981;margin-right:.6rem;vertical-align:middle}
        .ecl-form{background:#fff}
        .ecl-btn{border-radius:14px;min-height:56px;border-color:#D1D5DB}
        .ecl-btn:hover{border-color:#059669;background:#F0FDF4;box-shadow:0 0 0 3px rgba(5,150,105,.14)}
        .ecl-btn:focus-visible{outline:3px solid #059669;outline-offset:2px}
        .ecl-btn:active{transform:translateY(1px)}
        .ecl-kicker{background:#ECFDF5;border-color:#A7F3D0;color:#047857}
        .ecl-btn-primary{background:#059669;border-radius:14px}
        .ecl-btn-primary:hover{background:#047857}
        .ecl-link{color:#047857}
        @media(max-width:960px){
            .ecl-root{display:flex;flex-direction:column;background:#ECFDF5}
            .ecl-top{padding:1.25rem 1.25rem .5rem;gap:.75rem;align-items:flex-start;text-align:left;background-image:none}
            .ecl-hero h1{font-size:1.7rem}
            .ecl-hero p{font-size:.92rem;margin-top:.5rem}
            .ecl-illus{width:min(78%,300px);margin:0 auto}
            .ecl-tagline{display:none}
            .ecl-top .ecl-hero p{display:block}
            .ecl-form{background:transparent;padding:0 1rem 1.5rem}
            .ecl-card{box-shadow:0 12px 30px rgba(15,23,42,.10);border:1px solid #E2E8F0}
        }
        @media(min-width:600px) and (max-width:960px){
            .ecl-top{padding-inline:2.5rem}
            .ecl-illus{width:min(60%,340px)}
            .ecl-form{padding-inline:2.5rem}
        }
    </style>
