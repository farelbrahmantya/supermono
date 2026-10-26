<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!-- Design system LASMONO bertema Samudera: emas, merah, putih, charcoal + motif ombak -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Marcellus&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">
<?php $this->load->view('partials/favicon'); ?>
<style>
    :root {
        --lx-ink: #1c1d21;
        --lx-ink-2: #24262c;
        --lx-ink-3: #30323a;
        --lx-bg: #f6f5f2;
        --lx-surface: #ffffff;
        --lx-border: #ebe7de;
        --lx-text: #1c1d21;
        --lx-muted: #6b6f78;
        --lx-primary: #e5a720;
        --lx-primary-2: #f6c64a;
        --lx-gold-text: #8a5a00;
        --lx-red: #d72a2f;
        --lx-grad: linear-gradient(135deg, #f6c64a 0%, #e9a51f 100%);
        --lx-grad-text: linear-gradient(135deg, #c98a0c 0%, #e9a51f 100%);
        --lx-success: #10b981;
        --lx-warning: #f59e0b;
        --lx-danger: #d72a2f;
        --lx-info: #0ea5e9;
        --lx-radius: 18px;
        --lx-shadow: 0 1px 2px rgba(28, 29, 33, .04), 0 10px 28px rgba(28, 29, 33, .06);
        --lx-font-display: 'Marcellus', 'Optima', 'Candara', serif;
        --bs-font-sans-serif: 'Plus Jakarta Sans', system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
        --bs-body-font-family: var(--bs-font-sans-serif);
        --bs-primary: #e5a720;
        --bs-primary-rgb: 229, 167, 32;
        --bs-success: #10b981;
        --bs-success-rgb: 16, 185, 129;
        --bs-danger: #d72a2f;
        --bs-danger-rgb: 215, 42, 47;
        --bs-warning: #f59e0b;
        --bs-warning-rgb: 245, 158, 11;
    }
    body { font-family: var(--bs-font-sans-serif); background: var(--lx-bg); color: var(--lx-text); -webkit-font-smoothing: antialiased; letter-spacing: -0.005em; }
    h1, h2, h3, h4, h5, h6, .fw-bold { letter-spacing: -0.02em; }

    /* Brand */
    .lx-avatar { flex-shrink: 0; border-radius: 50%; box-shadow: 0 6px 18px rgba(229, 167, 32, .35); }
    .lx-brand { font-family: var(--lx-font-display); font-weight: 400; letter-spacing: .08em; text-transform: uppercase; }
    .lx-display { font-family: var(--lx-font-display); font-weight: 400; letter-spacing: .02em; }
    /* Garis gelombang emas seperti di judul samudera.id */
    .lx-squiggle { display: block; width: 64px; height: 10px; background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='64' height='10' viewBox='0 0 64 10'%3E%3Cpath d='M1 5 Q5 1 9 5 T17 5 T25 5 T33 5 T41 5 T49 5 T57 5 T63 5' fill='none' stroke='%23e5a720' stroke-width='2' stroke-linecap='round'/%3E%3C/svg%3E") no-repeat center; }
    .lx-squiggle.center { margin-left: auto; margin-right: auto; }
    /* Motif ombak tipis untuk header gelap */
    .lx-waves { background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='160' height='60' viewBox='0 0 160 60'%3E%3Cpath d='M0 30 Q20 10 40 30 T80 30 T120 30 T160 30' fill='none' stroke='%23ffffff' stroke-opacity='.06' stroke-width='2'/%3E%3Cpath d='M0 50 Q20 30 40 50 T80 50 T120 50 T160 50' fill='none' stroke='%23ffffff' stroke-opacity='.04' stroke-width='2'/%3E%3C/svg%3E"); }
    /* Aksen garis merah-putih khas Samudera */
    .lx-stripes { display: inline-flex; flex-direction: column; gap: 3px; }
    .lx-stripes i { display: block; width: 26px; height: 4px; border-radius: 2px; background: var(--lx-red); }
    .lx-eyebrow { font-size: 11px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--lx-muted); }

    /* Permukaan */
    .lx-card { background: var(--lx-surface); border: 1px solid var(--lx-border); border-radius: var(--lx-radius); box-shadow: var(--lx-shadow); }
    .lx-topbar { background: var(--lx-ink); color: #fff; padding: 12px 28px; position: sticky; top: 0; z-index: 1020; border-bottom: 3px solid var(--lx-primary); }
    .lx-topbar .lx-sub { color: #a9adb6; font-size: 12px; }
    .lx-topbar .lx-brand { font-size: 18px; }
    .lx-user { display: inline-flex; align-items: center; gap: 10px; padding: 6px 6px 6px 14px; border-radius: 50px; background: rgba(255, 255, 255, .06); border: 1px solid rgba(255, 255, 255, .08); color: #e2e8f0; font-size: 13px; }
    .lx-icon-btn { width: 36px; height: 36px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; background: rgba(255, 255, 255, .08); color: #fff; border: 1px solid rgba(255, 255, 255, .12); text-decoration: none; transition: .15s; }
    .lx-icon-btn:hover { background: var(--lx-red); color: #fff; }

    /* Tombol & form */
    .btn { border-radius: 12px; font-weight: 600; }
    .btn-primary, .lx-btn-primary { background: var(--lx-grad); border: none; color: var(--lx-ink); font-weight: 700; box-shadow: 0 8px 20px rgba(229, 167, 32, .3); }
    .btn-primary:hover, .btn-primary:focus, .btn-primary:active, .lx-btn-primary:hover { background: linear-gradient(135deg, #f2bb33 0%, #dc9612 100%) !important; color: var(--lx-ink) !important; }
    .btn-primary:disabled { background: #f1d48a; color: #6b5a2c; }
    .btn-success { background: var(--lx-success); border-color: var(--lx-success); }
    .btn-success:hover { background: #0ea371; border-color: #0ea371; }
    .form-control, .form-select { border-radius: 12px; border-color: var(--lx-border); padding: .7rem .9rem; }
    .form-control:focus, .form-select:focus { border-color: var(--lx-primary); box-shadow: 0 0 0 4px rgba(229, 167, 32, .18); }
    .text-primary { color: var(--lx-gold-text) !important; }
    .alert-primary { background: #fff6dc; color: #7a5100; border-color: #f6e2a8; }
    .badge { font-weight: 600; letter-spacing: .01em; }
    .modal-content { border: none; border-radius: 22px; overflow: hidden; box-shadow: 0 30px 60px rgba(15, 23, 42, .25); }

    /* Status pill lembut */
    .lx-pill { display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; border-radius: 50px; font-size: 12px; font-weight: 700; }
    .lx-pill-blue { background: #fff3d1; color: var(--lx-gold-text); }
    .lx-pill-green { background: #dcfce7; color: #047857; }
    .lx-pill-amber { background: #fef3c7; color: #b45309; }
    .lx-pill-red { background: #fee2e2; color: #b91c1c; }
    .lx-pill-gray { background: #f1f5f9; color: #475569; }

    /* Label aktivitas kitir: Z Repo In, A Import, D Export, E Repo Out */
    .act-tag { display: inline-block; padding: 2px 8px; border-radius: 6px; font-size: 10.5px; font-weight: 800; letter-spacing: .03em; text-transform: uppercase; background: #f1f2f4; color: #4b5059; white-space: nowrap; }
    .act-tag.act-Z { background: #fff3d1; color: var(--lx-gold-text); }
    .act-tag.act-A { background: #dcfce7; color: #047857; }
    .act-tag.act-D { background: #e0ecff; color: #1d4ed8; }
    .act-tag.act-E { background: #f3e8ff; color: #7e22ce; }

    /* Tabel */
    .lx-table { margin: 0; }
    .lx-table thead th { background: #f8fafc; color: var(--lx-muted); font-size: 11px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; border-bottom: 1px solid var(--lx-border); padding: 12px 14px; white-space: nowrap; }
    .lx-table tbody td { padding: 13px 14px; font-size: 14px; border-color: #f1f5f9; vertical-align: middle; }
    .lx-table tbody tr:hover td { background: #fffaf0; }

    @keyframes lx-pulse { 0%, 100% { opacity: 1; } 50% { opacity: .55; } }
    @keyframes lx-rise { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }
    .lx-rise { animation: lx-rise .45s ease both; }
</style>
