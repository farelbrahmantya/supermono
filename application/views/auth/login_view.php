<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#1c1d21">
    <title>LASMONO - Portal Akses Layanan Service Monitoring</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php $this->load->view('partials/theme'); ?>
    <style>
        body { min-height: 100vh; background: var(--lx-bg); }
        .shell { min-height: 100vh; display: grid; grid-template-columns: 1.1fr 1fr; }

        /* Panel hero */
        .hero { position: relative; overflow: hidden; background: var(--lx-ink); color: #fff; padding: 48px 56px; display: flex; flex-direction: column; justify-content: space-between; }
        .hero::before, .hero::after { content: ""; position: absolute; border-radius: 50%; filter: blur(70px); opacity: .55; }
        .hero::before { width: 420px; height: 420px; background: #e5a720; top: -120px; right: -120px; }
        .hero::after { width: 360px; height: 360px; background: #d72a2f; opacity: .35; bottom: -140px; left: -100px; }
        .hero > * { position: relative; z-index: 1; }
        .hero-grid { position: absolute; inset: 0; z-index: 0; opacity: .08; background-image: linear-gradient(#fff 1px, transparent 1px), linear-gradient(90deg, #fff 1px, transparent 1px); background-size: 44px 44px; mask-image: radial-gradient(circle at 30% 40%, #000, transparent 70%); }
        .hero h1 { font-family: var(--lx-font-display); font-size: 46px; font-weight: 400; line-height: 1.12; letter-spacing: .01em; }
        .hero h1 span { background: linear-gradient(90deg, #f6c64a, #fde3a0); -webkit-background-clip: text; background-clip: text; color: transparent; }
        .hero p.lead-text { color: #cbd5e1; font-size: 16px; max-width: 460px; }
        .feature { display: flex; gap: 14px; align-items: flex-start; padding: 14px 16px; border-radius: 16px; background: rgba(255, 255, 255, .05); border: 1px solid rgba(255, 255, 255, .08); backdrop-filter: blur(8px); }
        .feature .ico { width: 38px; height: 38px; border-radius: 11px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .feature b { display: block; font-size: 14px; }
        .feature small { color: #94a3b8; }

        /* Ilustrasi yard: tumpukan container, crane RTG, dan truk */
        .yard-art { display: block; width: 46%; max-width: 280px; height: auto; flex-shrink: 1; }
        .yard-art .truck { animation: lx-drive 9s ease-in-out infinite; }
        .yard-art .hook { animation: lx-hook 4.5s ease-in-out infinite; transform-box: fill-box; }
        @keyframes lx-drive { 0%, 15% { transform: translateX(0); } 50%, 65% { transform: translateX(-22px); } 100% { transform: translateX(0); } }
        @keyframes lx-hook { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(14px); } }
        @media (prefers-reduced-motion: reduce) { .yard-art .truck, .yard-art .hook { animation: none; } }

        /* Pilihan kegiatan supir: wajib dipilih, tanpa pilihan bawaan */
        .act-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
        .act-grid input { position: absolute; opacity: 0; pointer-events: none; }
        .act-grid label { position: relative; display: flex; align-items: center; gap: 10px; padding: 12px; border: 2px solid var(--lx-border); border-radius: 14px; background: #fff; cursor: pointer; transition: .15s; line-height: 1.15; }
        .act-grid label .ic { width: 34px; height: 34px; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 15px; }
        .act-grid label b { display: block; font-size: 14px; }
        .act-grid label .check { position: absolute; top: 6px; right: 8px; color: var(--lx-success); display: none; }
        .act-grid input:checked + label { border-color: var(--lx-primary); background: #fffaf0; box-shadow: 0 0 0 3px rgba(229, 167, 32, .18); }
        .act-grid input:checked + label .check { display: block; }
        .act-grid input:focus-visible + label { outline: 2px solid var(--lx-primary); }
        .act-grid.invalid label { border-color: #f3b3b5; }
        .act-error { display: none; color: var(--lx-red); font-size: 12px; font-weight: 600; margin-top: 6px; }
        .act-grid.invalid + .act-error { display: block; }

        /* Panel form */
        .panel { display: flex; align-items: center; justify-content: center; padding: 40px 24px; }
        .form-card { width: 100%; max-width: 420px; }
        .seg { display: grid; grid-template-columns: 1fr 1fr; background: #f1ede4; padding: 5px; border-radius: 14px; gap: 4px; }
        .seg .nav-link { border-radius: 10px; color: var(--lx-muted); font-weight: 700; font-size: 14px; padding: 10px; border: none; background: transparent; width: 100%; }
        .seg .nav-link.active { background: #fff; color: var(--lx-text); box-shadow: 0 2px 8px rgba(15, 23, 42, .08); }
        .field { position: relative; }
        .field > i { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #94a3b8; }
        .field .form-control { padding-left: 44px; height: 52px; font-weight: 600; background: #fff; }
        .field .form-control::placeholder { color: #a3adbf; font-weight: 500; }
        .field .toggle-pass { position: absolute; right: 8px; top: 50%; transform: translateY(-50%); border: none; background: transparent; color: #94a3b8; width: 40px; height: 40px; }
        .form-label { font-size: 12px; font-weight: 700; color: #475569; letter-spacing: .02em; }
        .btn-go { height: 54px; font-size: 15px; font-weight: 800; letter-spacing: .02em; border-radius: 14px; }
        .hint { font-size: 12px; color: var(--lx-muted); }
        .alert-soft { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; border-radius: 12px; font-size: 13px; }

        /* Desktop: satu layar penuh tanpa scroll */
        @media (min-width: 992px) {
            .shell { height: 100vh; min-height: 560px; }
            .hero { padding: clamp(24px, 4.5vh, 48px) clamp(32px, 4vw, 56px); }
            .hero h1 { font-size: clamp(30px, 5.2vh, 46px); }
            .hero p.lead-text { font-size: clamp(14px, 1.9vh, 16px); margin-bottom: clamp(12px, 2.6vh, 24px) !important; }
            .panel { padding: 16px 24px; overflow-y: auto; }
        }
        /* Layar laptop pendek (misal 1366x768): rapatkan jarak */
        @media (min-width: 992px) and (max-height: 820px) {
            .feature { padding: 9px 14px; }
            .feature .ico { width: 32px; height: 32px; }
            .form-card .mb-4 { margin-bottom: 14px !important; }
            .form-card .mb-3 { margin-bottom: 10px !important; }
            .form-card h3.lx-display { font-size: 26px !important; }
            .field .form-control { height: 46px; }
            .act-grid label { padding: 9px 12px; }
            .btn-go { height: 48px; }
        }
        @media (min-width: 992px) and (max-height: 700px) {
            .hero .features .feature small { display: none; }
            .form-card > .mb-4 p { display: none; }
        }

        @media (max-width: 991.98px) {
            .shell { grid-template-columns: 1fr; }
            .hero { padding: 28px 24px 64px; border-bottom-left-radius: 28px; border-bottom-right-radius: 28px; }
            .hero h1 { font-size: 28px; }
            .hero .features, .hero .stack, .hero .hero-foot { display: none !important; }
            .panel { padding: 0 16px 32px; margin-top: -44px; align-items: flex-start; }
            .form-card { background: #fff; border-radius: 22px; padding: 22px !important; box-shadow: var(--lx-shadow); border: 1px solid var(--lx-border); }
        }
    </style>
</head>
<body>
    <?php $staff_error = $this->session->flashdata('error_staff'); $driver_error = $this->session->flashdata('error_driver'); ?>
    <div class="shell">
        <!-- Hero / branding -->
        <section class="hero lx-waves">
            <div class="hero-top d-flex align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3">
                    <?php $this->load->view('partials/logo', array('size' => 44)); ?>
                    <div>
                        <div class="lx-brand fs-5">LASMONO</div>
                        <div class="small text-secondary">Depo Petikemas MTKI</div>
                    </div>
                </div>
                    <svg class="yard-art d-none d-lg-block" viewBox="0 0 460 170" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Ilustrasi container yard">
                        <defs>
                            <radialGradient id="yaSun" cx="50%" cy="50%" r="50%"><stop offset="0" stop-color="#f6c64a" stop-opacity=".55"/><stop offset="1" stop-color="#f6c64a" stop-opacity="0"/></radialGradient>
                            <linearGradient id="yaGold" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#f6c64a"/><stop offset="1" stop-color="#d9951a"/></linearGradient>
                            <linearGradient id="yaAmber" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#c98a1c"/><stop offset="1" stop-color="#a86f12"/></linearGradient>
                            <linearGradient id="yaRed" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#e5484d"/><stop offset="1" stop-color="#b8242a"/></linearGradient>
                            <linearGradient id="yaCream" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#fbf7ee"/><stop offset="1" stop-color="#e6dfcf"/></linearGradient>
                            <linearGradient id="yaFade" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="#fff" stop-opacity="0"/><stop offset=".2" stop-color="#fff" stop-opacity=".35"/><stop offset=".8" stop-color="#fff" stop-opacity=".35"/><stop offset="1" stop-color="#fff" stop-opacity="0"/></linearGradient>
                            <pattern id="yaRibs" width="5" height="10" patternUnits="userSpaceOnUse"><line x1="2.5" y1="0" x2="2.5" y2="10" stroke="#000" stroke-opacity=".13" stroke-width="1"/></pattern>
                        </defs>

                        <!-- Cahaya senja di belakang crane -->
                        <circle cx="300" cy="70" r="70" fill="url(#yaSun)"/>
                        <circle cx="300" cy="70" r="22" fill="#f6c64a" opacity=".22"/>

                        <!-- Siluet tumpukan jauh -->
                        <path d="M20 132 V108 H64 V96 H118 V108 H150 V132 Z M330 132 V100 H382 V112 H440 V132 Z" fill="#ffffff" opacity=".05"/>

                        <!-- Tumpukan container utama -->
                        <g>
                            <rect x="40" y="110" width="64" height="22" rx="2.5" fill="url(#yaAmber)"/><rect x="40" y="110" width="64" height="22" rx="2.5" fill="url(#yaRibs)"/>
                            <rect x="40" y="88" width="64" height="22" rx="2.5" fill="url(#yaCream)"/><rect x="40" y="88" width="64" height="22" rx="2.5" fill="url(#yaRibs)"/>
                            <rect x="108" y="110" width="64" height="22" rx="2.5" fill="url(#yaGold)"/><rect x="108" y="110" width="64" height="22" rx="2.5" fill="url(#yaRibs)"/>
                            <rect x="108" y="88" width="64" height="22" rx="2.5" fill="url(#yaRed)"/><rect x="108" y="88" width="64" height="22" rx="2.5" fill="url(#yaRibs)"/>
                            <rect x="108" y="66" width="64" height="22" rx="2.5" fill="url(#yaGold)"/><rect x="108" y="66" width="64" height="22" rx="2.5" fill="url(#yaRibs)"/>
                            <rect x="252" y="110" width="64" height="22" rx="2.5" fill="url(#yaCream)"/><rect x="252" y="110" width="64" height="22" rx="2.5" fill="url(#yaRibs)"/>
                            <rect x="252" y="88" width="64" height="22" rx="2.5" fill="url(#yaAmber)"/><rect x="252" y="88" width="64" height="22" rx="2.5" fill="url(#yaRibs)"/>
                        </g>

                        <!-- Crane RTG garis tipis -->
                        <g fill="none" stroke="#fbf7ee" stroke-opacity=".85" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M190 132 V40 M334 132 V40 M184 40 H340"/>
                            <path d="M190 58 L208 40 M334 58 L316 40" stroke-opacity=".45" stroke-width="1.6"/>
                        </g>
                        <g class="hook">
                            <rect x="246" y="36" width="20" height="9" rx="2" fill="#fbf7ee"/>
                            <path d="M251 45 V64 M261 45 V64" stroke="#fbf7ee" stroke-opacity=".6" stroke-width="1.2"/>
                            <rect x="222" y="64" width="68" height="4" rx="1.5" fill="#fbf7ee" opacity=".9"/>
                            <rect x="224" y="68" width="64" height="20" rx="2.5" fill="url(#yaRed)"/><rect x="224" y="68" width="64" height="20" rx="2.5" fill="url(#yaRibs)"/>
                        </g>

                        <!-- Truk kecil -->
                        <g class="truck">
                            <rect x="356" y="112" width="56" height="16" rx="2" fill="url(#yaCream)"/><rect x="356" y="112" width="56" height="16" rx="2" fill="url(#yaRibs)"/>
                            <rect x="353" y="128" width="62" height="3" rx="1.5" fill="#8a8f99"/>
                            <path d="M416 116 h14 l8 8 v7 h-22 z" fill="url(#yaGold)"/>
                            <rect x="420" y="119" width="9" height="5" rx="1" fill="#1c1d21" opacity=".55"/>
                            <circle cx="366" cy="133" r="4" fill="#1c1d21" stroke="#fbf7ee" stroke-opacity=".6" stroke-width="1.5"/>
                            <circle cx="402" cy="133" r="4" fill="#1c1d21" stroke="#fbf7ee" stroke-opacity=".6" stroke-width="1.5"/>
                            <circle cx="428" cy="133" r="4" fill="#1c1d21" stroke="#fbf7ee" stroke-opacity=".6" stroke-width="1.5"/>
                        </g>

                        <!-- Dermaga & ombak -->
                        <rect x="10" y="137" width="440" height="1.5" fill="url(#yaFade)"/>
                        <path d="M20 152 q10 -6 20 0 t20 0 t20 0 t20 0 t20 0 t20 0 t20 0 t20 0 t20 0 t20 0 t20 0 t20 0 t20 0 t20 0 t20 0 t20 0 t20 0 t20 0 t20 0 t20 0 t20 0" fill="none" stroke="#f6c64a" stroke-opacity=".4" stroke-width="1.6" stroke-linecap="round"/>
                        <path d="M60 163 q10 -5 20 0 t20 0 t20 0 t20 0 t20 0 t20 0 t20 0 t20 0 t20 0 t20 0 t20 0 t20 0 t20 0 t20 0 t20 0 t20 0 t20 0" fill="none" stroke="#f6c64a" stroke-opacity=".18" stroke-width="1.4" stroke-linecap="round"/>
                    </svg>
            </div>

            <div class="my-4 my-lg-0">
                <h1 class="mb-2">Layanan Service <span>Monitoring</span><br>Container Yard</h1>
                <span class="lx-squiggle mb-3"></span>
                <p class="lead-text mb-4">Antrean truk transparan, proses depo terpantau real-time, dan setiap tahap terukur sesuai KPI.</p>

                <div class="features d-grid gap-2" style="max-width: 460px;">
                    <div class="feature">
                        <span class="ico" style="background: rgba(229, 167, 32, .2); color: #f6c64a;"><i class="fas fa-list-ol"></i></span>
                        <div><b>Antrean FIFO transparan</b><small>Nomor & posisi antrean terlihat langsung di HP supir</small></div>
                    </div>
                    <div class="feature">
                        <span class="ico" style="background: rgba(16, 185, 129, .2); color: #6ee7b7;"><i class="fas fa-stopwatch"></i></span>
                        <div><b>KPI per tahap</b><small>Gate In, Survey, Gate Out masing-masing terukur 5 menit</small></div>
                    </div>
                    <div class="feature">
                        <span class="ico" style="background: rgba(239, 68, 68, .2); color: #fca5a5;"><i class="fas fa-shield-halved"></i></span>
                        <div><b>SOS anti pungli</b><small>Laporan langsung ke Control Tower beserta bukti foto</small></div>
                    </div>
                </div>
            </div>

            <div class="hero-foot small d-flex align-items-center gap-2" style="color: #a9adb6;">
                <span class="lx-stripes"><i></i><i></i><i></i></span>
                <span>&copy; <?= date('Y'); ?> Depo Petikemas MTKI &middot; Bagian dari <b style="color: #fff;">Samudera Group</b></span>
            </div>
        </section>

        <!-- Form login -->
        <section class="panel">
            <div class="form-card p-1 lx-rise">
                <div class="mb-4">
                    <h3 class="lx-display mb-1" style="font-size: 30px;">Selamat Datang</h3>
                    <span class="lx-squiggle mb-2"></span>
                    <p class="text-secondary mb-0">Masuk sesuai peran Anda untuk melanjutkan.</p>
                </div>

                <ul class="nav seg mb-4" id="loginTab" role="tablist">
                    <li role="presentation">
                        <button class="nav-link <?= $staff_error ? '' : 'active'; ?>" id="driver-tab" data-bs-toggle="pill" data-bs-target="#driver-pane" type="button" role="tab">
                            <i class="fas fa-truck me-1"></i> Supir
                        </button>
                    </li>
                    <li role="presentation">
                        <button class="nav-link <?= $staff_error ? 'active' : ''; ?>" id="staff-tab" data-bs-toggle="pill" data-bs-target="#staff-pane" type="button" role="tab">
                            <i class="fas fa-user-shield me-1"></i> Petugas Depo
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="loginTabContent">
                    <!-- FORM LOGIN SUPIR -->
                    <div class="tab-pane fade <?= $staff_error ? '' : 'show active'; ?>" id="driver-pane" role="tabpanel">
                        <?php if ($driver_error): ?>
                            <div class="alert alert-soft py-2 px-3 mb-3"><i class="fas fa-circle-exclamation me-1"></i> <?= html_escape($driver_error); ?></div>
                        <?php endif; ?>

                        <form action="<?= base_url('auth/process_login_driver'); ?>" method="POST" data-csrf-refresh>
                            <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
                            <div class="mb-3">
                                <label class="form-label">NOMOR HANDPHONE</label>
                                <div class="field">
                                    <i class="fas fa-mobile-screen"></i>
                                    <input type="tel" name="driver_phone" class="form-control" placeholder="08123456789" inputmode="numeric" autocomplete="tel" required>
                                </div>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">NOMOR POLISI TRUK</label>
                                <div class="field">
                                    <i class="fas fa-truck-front"></i>
                                    <input type="text" name="license_plate" class="form-control text-uppercase" placeholder="B 9123 UQK" autocomplete="off" required>
                                </div>
                            </div>
                            <p class="hint mb-3"><i class="fas fa-circle-info me-1"></i> Gunakan nomor HP & nopol yang sama selama antrean berlangsung.</p>
                            <div class="mb-4">
                                <label class="form-label">JENIS KEGIATAN <span class="fw-normal text-muted">(sesuai kitir)</span></label>
                                <div class="act-grid" id="actGrid">
                                    <?php
                                        // Urutan grid 2 kolom: Import | Export, lalu Repo In | Repo Out
                                        $driver_acts = array(
                                            'A' => array('Import', 'fa-box-open', '#dcfce7', '#047857'),
                                            'D' => array('Export', 'fa-ship', '#e0ecff', '#1d4ed8'),
                                            'Z' => array('Repo In', 'fa-arrow-right-to-bracket', '#fff3d1', '#8a5a00'),
                                            'E' => array('Repo Out', 'fa-arrow-right-from-bracket', '#f3e8ff', '#7e22ce')
                                        );
                                    ?>
                                    <?php foreach ($driver_acts as $code => $act): ?>
                                        <input type="radio" name="activity" value="<?= $code; ?>" id="act<?= $code; ?>">
                                        <label for="act<?= $code; ?>">
                                            <span class="ic" style="background: <?= $act[2]; ?>; color: <?= $act[3]; ?>;"><i class="fas <?= $act[1]; ?>"></i></span>
                                            <span><b><?= $act[0]; ?></b></span>
                                            <i class="fas fa-circle-check check"></i>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                                <div class="act-error"><i class="fas fa-circle-exclamation me-1"></i>Pilih jenis kegiatan terlebih dahulu.</div>
                            </div>
                            <button type="submit" class="btn btn-primary btn-go w-100" id="driverSubmit">
                                Masuk Antrean <i class="fas fa-arrow-right ms-1"></i>
                            </button>
                        </form>
                    </div>

                    <!-- FORM LOGIN PETUGAS -->
                    <div class="tab-pane fade <?= $staff_error ? 'show active' : ''; ?>" id="staff-pane" role="tabpanel">
                        <?php if ($staff_error): ?>
                            <div class="alert alert-soft py-2 px-3 mb-3"><i class="fas fa-circle-exclamation me-1"></i> <?= html_escape($staff_error); ?></div>
                        <?php endif; ?>

                        <form action="<?= base_url('auth/process_login_staff'); ?>" method="POST" data-csrf-refresh>
                            <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
                            <div class="mb-3">
                                <label class="form-label">USERNAME</label>
                                <div class="field">
                                    <i class="fas fa-user"></i>
                                    <input type="text" name="username" class="form-control" placeholder="Masukkan username" autocomplete="username" required>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="form-label">PASSWORD</label>
                                <div class="field">
                                    <i class="fas fa-lock"></i>
                                    <input type="password" name="password" id="passwordInput" class="form-control" placeholder="Masukkan password" autocomplete="current-password" required>
                                    <button type="button" class="toggle-pass" id="togglePass" aria-label="Tampilkan password"><i class="fas fa-eye"></i></button>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-dark btn-go w-100" style="background: var(--lx-ink); border: none;">
                                Masuk sebagai Petugas <i class="fas fa-arrow-right-to-bracket ms-1"></i>
                            </button>
                            <p class="hint text-center mt-3 mb-0">Gate In &middot; Gate Out &middot; Yardman &middot; Control Tower</p>
                        </form>
                    </div>
                </div>

                <div class="d-flex align-items-center justify-content-center gap-2 mt-4 hint">
                    <span class="rounded-circle" style="width: 8px; height: 8px; background: var(--lx-success); box-shadow: 0 0 0 4px rgba(16,185,129,.15);"></span>
                    Sistem aktif &middot; Depo Petikemas MTKI
                </div>
            </div>
        </section>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Tampilkan / sembunyikan password
        document.getElementById('togglePass').addEventListener('click', function() {
            var input = document.getElementById('passwordInput');
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            this.innerHTML = show ? '<i class="fas fa-eye-slash"></i>' : '<i class="fas fa-eye"></i>';
        });

        // Kegiatan supir: tombol masuk menyebut kegiatan yang dipilih agar salah pilih mudah terlihat
        (function() {
            var grid = document.getElementById('actGrid');
            if (!grid) return;
            var radios = grid.querySelectorAll('input[name="activity"]');
            for (var i = 0; i < radios.length; i++) {
                radios[i].addEventListener('change', function() {
                    grid.classList.remove('invalid');
                    var name = grid.querySelector('label[for="' + this.id + '"] b').textContent;
                    document.getElementById('driverSubmit').innerHTML = 'Masuk Antrean &middot; ' + name + ' <i class="fas fa-arrow-right ms-1"></i>';
                });
            }
            grid.closest('form').addEventListener('submit', function(e) {
                if (!grid.querySelector('input[name="activity"]:checked')) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    grid.classList.add('invalid');
                    grid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            });
        })();

        // Ambil token CSRF terbaru tepat sebelum form dikirim
        (function() {
            var tokenUrl = "<?= base_url('auth/csrf_token'); ?>";
            var forms = document.querySelectorAll('form[data-csrf-refresh]');
            for (var i = 0; i < forms.length; i++) {
                forms[i].addEventListener('submit', function(e) {
                    var form = this;
                    e.preventDefault();
                    var button = form.querySelector('button[type="submit"]');
                    if (button) { button.disabled = true; button.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Memproses...'; }
                    var xhr = new XMLHttpRequest();
                    xhr.open('GET', tokenUrl + '?_=' + Date.now(), true);
                    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                    xhr.onloadend = function() {
                        try {
                            var res = JSON.parse(xhr.responseText);
                            var input = form.querySelector('input[name="' + res.name + '"]');
                            if (input) input.value = res.hash;
                        } catch (err) {}
                        form.submit();
                    };
                    xhr.send();
                });
            }
        })();
    </script>
</body>
</html>
