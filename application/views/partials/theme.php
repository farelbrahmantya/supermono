<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!-- Design system LASMONO: font, warna, dan komponen dasar yang dipakai semua halaman -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">
<style>
    :root {
        --lx-ink: #0b1220;
        --lx-ink-2: #111a2e;
        --lx-ink-3: #1b2742;
        --lx-bg: #f4f6fb;
        --lx-surface: #ffffff;
        --lx-border: #e6eaf2;
        --lx-text: #0f172a;
        --lx-muted: #64748b;
        --lx-primary: #3b5bfd;
        --lx-primary-2: #6d4aff;
        --lx-grad: linear-gradient(135deg, #3b5bfd 0%, #6d4aff 100%);
        --lx-success: #10b981;
        --lx-warning: #f59e0b;
        --lx-danger: #ef4444;
        --lx-info: #0ea5e9;
        --lx-radius: 18px;
        --lx-shadow: 0 1px 2px rgba(16, 24, 40, .04), 0 10px 28px rgba(16, 24, 40, .06);
        --bs-font-sans-serif: 'Plus Jakarta Sans', system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
        --bs-body-font-family: var(--bs-font-sans-serif);
        --bs-primary: #3b5bfd;
        --bs-primary-rgb: 59, 91, 253;
        --bs-success: #10b981;
        --bs-success-rgb: 16, 185, 129;
        --bs-danger: #ef4444;
        --bs-danger-rgb: 239, 68, 68;
        --bs-warning: #f59e0b;
        --bs-warning-rgb: 245, 158, 11;
    }
    body { font-family: var(--bs-font-sans-serif); background: var(--lx-bg); color: var(--lx-text); -webkit-font-smoothing: antialiased; letter-spacing: -0.005em; }
    h1, h2, h3, h4, h5, h6, .fw-bold { letter-spacing: -0.02em; }

    /* Brand */
    .lx-logo { width: 42px; height: 42px; border-radius: 13px; background: var(--lx-grad); color: #fff; display: inline-flex; align-items: center; justify-content: center; font-size: 18px; box-shadow: 0 8px 20px rgba(79, 70, 229, .35); flex-shrink: 0; }
    .lx-brand { font-weight: 800; letter-spacing: .02em; }
    .lx-eyebrow { font-size: 11px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--lx-muted); }

    /* Permukaan */
    .lx-card { background: var(--lx-surface); border: 1px solid var(--lx-border); border-radius: var(--lx-radius); box-shadow: var(--lx-shadow); }
    .lx-topbar { background: var(--lx-ink); color: #fff; padding: 14px 28px; position: sticky; top: 0; z-index: 1020; box-shadow: 0 1px 0 rgba(255, 255, 255, .06); }
    .lx-topbar .lx-sub { color: #94a3b8; font-size: 12px; }
    .lx-user { display: inline-flex; align-items: center; gap: 10px; padding: 6px 6px 6px 14px; border-radius: 50px; background: rgba(255, 255, 255, .06); border: 1px solid rgba(255, 255, 255, .08); color: #e2e8f0; font-size: 13px; }
    .lx-icon-btn { width: 36px; height: 36px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; background: rgba(255, 255, 255, .08); color: #fff; border: 1px solid rgba(255, 255, 255, .12); text-decoration: none; transition: .15s; }
    .lx-icon-btn:hover { background: rgba(239, 68, 68, .85); color: #fff; }

    /* Tombol & form */
    .btn { border-radius: 12px; font-weight: 600; }
    .btn-primary, .lx-btn-primary { background: var(--lx-grad); border: none; color: #fff; box-shadow: 0 8px 20px rgba(79, 70, 229, .25); }
    .btn-primary:hover, .btn-primary:focus, .lx-btn-primary:hover { background: linear-gradient(135deg, #2f4ef0 0%, #5f3cf5 100%); color: #fff; }
    .btn-success { background: var(--lx-success); border-color: var(--lx-success); }
    .btn-success:hover { background: #0ea371; border-color: #0ea371; }
    .form-control, .form-select { border-radius: 12px; border-color: var(--lx-border); padding: .7rem .9rem; }
    .form-control:focus, .form-select:focus { border-color: var(--lx-primary); box-shadow: 0 0 0 4px rgba(59, 91, 253, .12); }
    .badge { font-weight: 600; letter-spacing: .01em; }
    .modal-content { border: none; border-radius: 22px; overflow: hidden; box-shadow: 0 30px 60px rgba(15, 23, 42, .25); }

    /* Status pill lembut */
    .lx-pill { display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; border-radius: 50px; font-size: 12px; font-weight: 700; }
    .lx-pill-blue { background: #e8edff; color: #2f47d6; }
    .lx-pill-green { background: #dcfce7; color: #047857; }
    .lx-pill-amber { background: #fef3c7; color: #b45309; }
    .lx-pill-red { background: #fee2e2; color: #b91c1c; }
    .lx-pill-gray { background: #f1f5f9; color: #475569; }

    /* Tabel */
    .lx-table { margin: 0; }
    .lx-table thead th { background: #f8fafc; color: var(--lx-muted); font-size: 11px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; border-bottom: 1px solid var(--lx-border); padding: 12px 14px; white-space: nowrap; }
    .lx-table tbody td { padding: 13px 14px; font-size: 14px; border-color: #f1f5f9; vertical-align: middle; }
    .lx-table tbody tr:hover td { background: #fafbff; }

    @keyframes lx-pulse { 0%, 100% { opacity: 1; } 50% { opacity: .55; } }
    @keyframes lx-rise { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }
    .lx-rise { animation: lx-rise .45s ease both; }
</style>
