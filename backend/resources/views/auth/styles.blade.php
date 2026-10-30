<style>
        /* Masuk / Daftar — kepala hijau EcoWin bergradasi, kartu putih yang menimpa tepinya, tipografi lega. */
        .ecl-root{--em:#059669;--em-d:#047857;--em-dd:#065F46;--ink:#0F172A;--mut:#64748B;--ln:#E2E8F0;--fld:#CBD5E1;position:fixed;inset:0;z-index:100;overflow:auto;background:#F8FAFC;color:var(--ink);-webkit-text-size-adjust:100%}
        .ecl-bg{display:none}

        /* Kepala */
        .ecl-top{position:relative;overflow:hidden;z-index:1;display:flex;flex-direction:column;gap:1.1rem;padding:1.4rem 1.5rem 5.6rem;color:#fff;background:linear-gradient(150deg,#065F46 0%,#059669 55%,#10B981 100%);border-radius:0 0 36px 36px}
        .ecl-top::before{content:"";position:absolute;right:-90px;top:-110px;width:300px;height:300px;border-radius:50%;background:rgba(255,255,255,.10)}
        .ecl-top::after{content:"";position:absolute;left:-80px;bottom:-90px;width:220px;height:220px;border-radius:50%;background:rgba(255,255,255,.07)}
        .ecl-top>*{position:relative;z-index:1}
        .ecl-brand{display:flex;align-items:center;gap:.7rem}
        .ecl-mark{width:44px;height:44px;border-radius:14px;background:#fff;display:inline-flex;align-items:center;justify-content:center;box-shadow:0 6px 16px rgba(0,0,0,.14)}
        .ecl-mark img{width:29px;height:auto}
        .ecl-word{font-size:1.5rem;font-weight:800;letter-spacing:-.01em;color:#fff}
        .ecl-word b{color:#A7F3D0}
        .ecl-hero h1{margin:0;font-size:1.85rem;line-height:1.12;font-weight:800;letter-spacing:-.02em;color:#fff}
        .ecl-hero h1 em{display:block;font-style:normal;color:#A7F3D0}
        .ecl-hero p{margin:.6rem 0 0;max-width:30ch;font-size:.95rem;line-height:1.55;color:rgba(236,253,245,.85)}
        .ecl-illus,.ecl-tagline,.ecl-bottom,.ecl-roles,.ecl-icon{display:none}

        /* Kartu */
        .ecl-form{position:relative;z-index:2;margin-top:-3.9rem;padding:0 1rem 1.5rem;display:block}
        .ecl-card{width:100%;max-width:480px;margin:0 auto;padding:1.6rem 1.35rem;background:#fff;border:1px solid rgba(226,232,240,.9);border-radius:28px;box-shadow:0 24px 50px -22px rgba(6,95,70,.45),0 2px 6px rgba(15,23,42,.05)}
        .ecl-kicker{display:inline-block;padding:.28rem .75rem;border-radius:999px;background:#ECFDF5;color:var(--em-d);font-size:.7rem;font-weight:700;letter-spacing:.08em}
        .ecl-card h2{margin-top:.9rem;font-size:1.65rem;line-height:1.15;font-weight:800;letter-spacing:-.015em}
        .ecl-sub{margin-top:.5rem;font-size:.95rem;line-height:1.6;color:var(--mut)}
        .ecl-btn{margin-top:1.5rem;display:flex;align-items:center;justify-content:center;gap:.75rem;width:100%;min-height:56px;border-radius:999px;border:1.5px solid var(--fld);background:#fff;color:var(--ink);font-size:1rem;font-weight:600;text-decoration:none;transition:border-color .15s ease,background .15s ease,box-shadow .15s ease,transform .1s ease}
        .ecl-btn:hover{border-color:var(--em);background:#F0FDF4;box-shadow:0 0 0 4px rgba(5,150,105,.12)}
        .ecl-btn:focus-visible{outline:3px solid var(--em);outline-offset:3px}
        .ecl-btn:active{transform:translateY(1px)}
        .ecl-btn svg{width:22px;height:22px;flex:none}
        .ecl-btn.is-loading{pointer-events:none;border-color:#A7F3D0;background:#F0FDF4;color:var(--em-d)}
        .ecl-btn.is-loading svg{display:none}
        .ecl-btn.is-loading::before{content:"";width:20px;height:20px;border-radius:999px;border:3px solid #A7F3D0;border-top-color:var(--em);animation:ecl-spin .8s linear infinite;flex:none}
        @keyframes ecl-spin{to{transform:rotate(360deg)}}
        @media (prefers-reduced-motion:reduce){.ecl-btn.is-loading::before{animation:none}}
        .ecl-err{margin-top:1.1rem;padding:.8rem 1rem;border-radius:14px;background:#FEF2F2;color:#B91C1C;border:1px solid #FECACA;font-size:.875rem;line-height:1.5}
        .ecl-ok{margin-top:1rem;padding:.75rem 1rem;border-radius:14px;background:#F0FDF4;color:var(--em-d);border:1px solid #BBF7D0;font-size:.875rem;font-weight:600}
        .ecl-note{margin-top:1.15rem;display:flex;gap:.6rem;align-items:flex-start;font-size:.86rem;line-height:1.55;color:var(--mut)}
        .ecl-note svg{width:18px;height:18px;flex:none;margin-top:1px;color:var(--em)}
        .ecl-or{margin-top:1.5rem;padding-top:1.2rem;border-top:1px solid var(--ln);text-align:center;font-size:.92rem;color:var(--mut)}
        .ecl-link{color:var(--em-d);font-weight:700;text-decoration:none}
        .ecl-link:hover{text-decoration:underline}
        .ecl-link-btn{background:none;border:0;padding:0;font:inherit;cursor:pointer}
        .ecl-foot{margin-top:1rem;text-align:center;font-size:.74rem;color:#94A3B8}

        /* Keunggulan: tiga ikon kecil di bawah kartu (HP) */
        .ecl-feats{list-style:none;margin:1.25rem auto 0;padding:0 1rem 2rem;max-width:480px;display:none}
        .ecl-feat{display:flex;flex-direction:column;align-items:center;gap:.45rem;text-align:center}
        .ecl-feat-ico{width:44px;height:44px;border-radius:14px;background:#ECFDF5;color:var(--em-d);display:flex;align-items:center;justify-content:center}
        .ecl-feat-ico svg{width:21px;height:21px}
        .ecl-feat b{display:block;font-size:.78rem;font-weight:700;line-height:1.25}
        .ecl-feat div>span{display:none}

        /* Formulir */
        .ecl-field{margin-top:1.1rem}
        .ecl-field label{display:block;margin-bottom:.4rem;font-size:.86rem;font-weight:600;color:var(--ink)}
        .ecl-input{width:100%;min-height:52px;padding:.65rem 1rem;border-radius:16px;border:1.5px solid var(--fld);background:#fff;color:var(--ink);font:inherit;font-size:1rem}
        .ecl-input::placeholder{color:#94A3B8}
        .ecl-input:focus{outline:0;border-color:var(--em);box-shadow:0 0 0 4px rgba(5,150,105,.14)}
        .ecl-input[readonly]{background:#F1F5F9;color:var(--mut)}
        .ecl-hint{margin-top:.35rem;font-size:.78rem;color:var(--mut)}
        .ecl-field-err{margin-top:.35rem;font-size:.78rem;font-weight:600;color:#B91C1C}
        .ecl-check{display:flex;gap:.65rem;align-items:flex-start;margin-top:1.25rem;font-size:.85rem;line-height:1.5;color:#475569}
        .ecl-check input{margin-top:.2rem;width:18px;height:18px;accent-color:var(--em);flex:none}
        .ecl-btn-primary{margin-top:1.5rem;display:flex;align-items:center;justify-content:center;width:100%;min-height:56px;border:0;border-radius:999px;background:linear-gradient(135deg,#059669,#10B981);color:#fff;font:inherit;font-size:1rem;font-weight:700;cursor:pointer;text-decoration:none;box-shadow:0 10px 24px -10px rgba(5,150,105,.7);transition:filter .15s ease}
        .ecl-btn-primary:hover{filter:brightness(.95)}
        .ecl-btn-primary:focus-visible{outline:3px solid var(--em);outline-offset:3px}
        .ecl-who{margin-top:1.1rem;display:flex;gap:.75rem;align-items:center;padding:.8rem 1rem;border-radius:16px;background:#F8FAFC;border:1px solid var(--ln)}
        .ecl-who img{width:40px;height:40px;border-radius:999px;object-fit:cover;flex:none}
        .ecl-who b{display:block;font-size:.9rem}
        .ecl-who span{font-size:.78rem;color:var(--mut)}

        /* Pendaftaran */
        .ecl-stepper{list-style:none;margin:0 0 1.25rem;padding:0;display:flex;align-items:flex-start}
        .ecl-stepper li{flex:1;display:flex;flex-direction:column;align-items:center;gap:.4rem;position:relative;font-size:.72rem;font-weight:600;color:var(--mut);text-align:center}
        .ecl-stepper li+li::before{content:"";position:absolute;top:15px;right:50%;width:100%;height:2px;background:var(--ln);z-index:0}
        .ecl-stepper li.done+li::before,.ecl-stepper li.on::before{background:#10B981}
        .ecl-stepper i{position:relative;z-index:1;width:30px;height:30px;border-radius:999px;border:2px solid var(--fld);background:#fff;color:var(--mut);font-style:normal;font-size:.8rem;font-weight:700;display:flex;align-items:center;justify-content:center}
        .ecl-stepper i svg{width:15px;height:15px}
        .ecl-stepper li.on i{border-color:var(--em);background:var(--em);color:#fff;box-shadow:0 0 0 4px rgba(5,150,105,.15)}
        .ecl-stepper li.done i{border-color:var(--em);background:var(--em);color:#fff}
        .ecl-stepper li.on,.ecl-stepper li.done{color:var(--ink)}
        .ecl-stepper li.on span{font-weight:800}
        .ecl-banks{border:0;padding:0;margin:1.1rem 0 0;min-width:0;display:grid;gap:.6rem}
        .ecl-banks legend{padding:0;margin-bottom:.5rem;font-size:.86rem;font-weight:600}
        .ecl-field .ecl-bank,.ecl-bank{position:relative;display:flex;align-items:center;gap:.8rem;padding:.8rem .9rem;min-height:64px;margin:0;border:1.5px solid var(--fld);border-radius:18px;background:#fff;cursor:pointer;transition:border-color .15s ease,background .15s ease,box-shadow .15s ease}
        .ecl-bank:hover{border-color:#10B981}
        .ecl-bank input{position:absolute;opacity:0;inset:0;width:100%;height:100%;margin:0;cursor:pointer}
        .ecl-jk{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.6rem}
        .ecl-jk .ecl-bank{padding:.7rem;gap:.55rem;min-width:0}
        .ecl-jk-img{width:38px;height:38px;flex:none;border-radius:999px;object-fit:cover;background:#ECFDF5}
        .ecl-jk .ecl-bank-tick{display:none}
        .ecl-bank-ico{width:42px;height:42px;flex:none;border-radius:12px;background:#ECFDF5;color:var(--em);display:flex;align-items:center;justify-content:center}
        .ecl-bank-ico svg{width:22px;height:22px}
        .ecl-bank-txt{flex:1;min-width:0}
        .ecl-bank-txt b{display:block;font-size:.95rem;font-weight:700}
        .ecl-bank-txt small{display:block;margin-top:.1rem;font-size:.78rem;color:var(--mut)}
        .ecl-bank-tick{width:24px;height:24px;flex:none;border-radius:999px;border:2px solid var(--fld);color:transparent;display:flex;align-items:center;justify-content:center}
        .ecl-bank-tick svg{width:14px;height:14px}
        .ecl-bank:has(input:checked){border-color:var(--em);background:#F0FDF4;box-shadow:0 0 0 3px rgba(5,150,105,.14)}
        .ecl-bank:has(input:checked) .ecl-bank-tick{border-color:var(--em);background:var(--em);color:#fff}
        .ecl-bank:has(input:focus-visible){outline:3px solid var(--em);outline-offset:2px}
        .ecl-submitbar{position:sticky;bottom:0;margin:1.25rem -1.35rem -1.6rem;padding:.9rem 1.35rem calc(.9rem + env(safe-area-inset-bottom));background:linear-gradient(to bottom,rgba(255,255,255,0),#fff 30%);border-radius:0 0 28px 28px}
        .ecl-submitbar .ecl-btn-primary{margin-top:0}
        /* Halaman daftar di HP: kepala ringkas (hanya merek), formulir langsung terlihat */
        .ecl-reg .ecl-hero,.ecl-reg .ecl-feats{display:none}
        .ecl-reg .ecl-top{padding-bottom:4.6rem}
        .ecl-reg .ecl-form{margin-top:-3.1rem}

        @media(max-width:380px){.ecl-stepper li{font-size:.66rem}.ecl-card{padding:1.35rem 1.1rem}.ecl-hero h1{font-size:1.65rem}}

        /* Desktop / tablet lebar: panel hijau penuh di kiri, kartu bersih di kanan */
        @media(min-width:960px){
            .ecl-root{display:grid;grid-template-columns:minmax(0,1.1fr) minmax(0,1fr);grid-template-rows:1fr auto;grid-template-areas:"top form" "feat form";background:#fff}
            .ecl-bg{display:block;grid-column:1;grid-row:1/3;background:linear-gradient(155deg,#065F46 0%,#059669 55%,#10B981 100%);border-radius:0 56px 56px 0;position:relative;overflow:hidden}
            .ecl-bg::before{content:"";position:absolute;right:-120px;top:-140px;width:460px;height:460px;border-radius:50%;background:rgba(255,255,255,.09)}
            .ecl-bg::after{content:"";position:absolute;left:-120px;bottom:-130px;width:380px;height:380px;border-radius:50%;background:rgba(255,255,255,.07)}
            .ecl-top{grid-area:top;background:transparent;border-radius:0;overflow:visible;padding:clamp(2rem,4vw,3.25rem) clamp(2rem,5vw,4.5rem) 0;justify-content:center;gap:1.5rem;position:relative}
            .ecl-top::before,.ecl-top::after{display:none}
            .ecl-hero h1{font-size:clamp(2.2rem,3.5vw,3.2rem)}
            .ecl-hero p{margin-top:1rem;font-size:1.1rem;max-width:34ch}
            .ecl-feats{grid-area:feat;position:relative;z-index:1;display:grid;grid-template-columns:1fr;gap:.9rem;margin:0;max-width:none;padding:1rem clamp(2rem,5vw,4.5rem) clamp(2rem,4vw,3.25rem)}
            .ecl-feat{flex-direction:row;align-items:center;text-align:left;gap:.9rem;color:#fff}
            .ecl-feat-ico{background:rgba(255,255,255,.16);color:#fff;border:1px solid rgba(255,255,255,.25)}
            .ecl-feat b{font-size:.95rem}
            .ecl-feat div>span{display:block;margin-top:.1rem;font-size:.82rem;line-height:1.45;color:rgba(236,253,245,.82)}
            .ecl-form{grid-area:form;margin:0;padding:2.5rem 2rem;display:flex;background:#fff}
            .ecl-card{max-width:440px;margin:auto;border:0;box-shadow:none;padding:0;border-radius:0}
            .ecl-card h2{font-size:2rem}
            .ecl-submitbar{position:static;margin:0;padding:0;background:none}
            .ecl-submitbar .ecl-btn-primary{margin-top:1.5rem}
            .ecl-reg .ecl-hero,.ecl-reg .ecl-feats{display:block}
            .ecl-reg .ecl-feats{display:grid}
            .ecl-reg .ecl-top{padding-bottom:0}
            .ecl-reg .ecl-form{margin:0}
        }
        @media(min-width:600px) and (max-width:959px){
            .ecl-top{padding-inline:2.5rem;padding-bottom:6rem}
            .ecl-hero h1{font-size:2.3rem}
            .ecl-card{padding:2rem 1.75rem}
        }
    </style>
