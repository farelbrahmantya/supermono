<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0b1220">
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
        .hero::before { width: 420px; height: 420px; background: #3b5bfd; top: -120px; right: -120px; }
        .hero::after { width: 360px; height: 360px; background: #6d4aff; bottom: -140px; left: -100px; }
        .hero > * { position: relative; z-index: 1; }
        .hero-grid { position: absolute; inset: 0; z-index: 0; opacity: .08; background-image: linear-gradient(#fff 1px, transparent 1px), linear-gradient(90deg, #fff 1px, transparent 1px); background-size: 44px 44px; mask-image: radial-gradient(circle at 30% 40%, #000, transparent 70%); }
        .hero h1 { font-size: 44px; font-weight: 800; line-height: 1.1; letter-spacing: -0.03em; }
        .hero h1 span { background: linear-gradient(90deg, #a5b4fc, #c4b5fd); -webkit-background-clip: text; background-clip: text; color: transparent; }
        .hero p.lead-text { color: #cbd5e1; font-size: 16px; max-width: 460px; }
        .feature { display: flex; gap: 14px; align-items: flex-start; padding: 14px 16px; border-radius: 16px; background: rgba(255, 255, 255, .05); border: 1px solid rgba(255, 255, 255, .08); backdrop-filter: blur(8px); }
        .feature .ico { width: 38px; height: 38px; border-radius: 11px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .feature b { display: block; font-size: 14px; }
        .feature small { color: #94a3b8; }

        /* Container bertumpuk dekoratif */
        .stack { display: flex; gap: 8px; align-items: flex-end; margin-bottom: 28px; }
        .stack i { display: block; width: 64px; border-radius: 6px; background: repeating-linear-gradient(90deg, rgba(255,255,255,.18) 0 2px, transparent 2px 8px); border: 1px solid rgba(255,255,255,.18); }

        /* Panel form */
        .panel { display: flex; align-items: center; justify-content: center; padding: 40px 24px; }
        .form-card { width: 100%; max-width: 420px; }
        .seg { display: grid; grid-template-columns: 1fr 1fr; background: #eef1f7; padding: 5px; border-radius: 14px; gap: 4px; }
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
        <section class="hero">
            <div class="hero-grid"></div>
            <div class="d-flex align-items-center gap-3">
                <span class="lx-logo"><i class="fas fa-cubes-stacked"></i></span>
                <div>
                    <div class="lx-brand fs-5">LASMONO</div>
                    <div class="small text-secondary">Depo Petikemas MTKI</div>
                </div>
            </div>

            <div class="my-4 my-lg-0">
                <div class="stack d-none d-lg-flex">
                    <i style="height: 34px; background-color: rgba(59, 91, 253, .55);"></i>
                    <i style="height: 52px; background-color: rgba(109, 74, 255, .55);"></i>
                    <i style="height: 40px; background-color: rgba(16, 185, 129, .45);"></i>
                    <i style="height: 62px; background-color: rgba(245, 158, 11, .45);"></i>
                </div>
                <h1 class="mb-3">Layanan Service <span>Monitoring</span><br>Container Yard</h1>
                <p class="lead-text mb-4">Antrean truk transparan, proses depo terpantau real-time, dan setiap tahap terukur sesuai KPI.</p>

                <div class="features d-grid gap-2" style="max-width: 460px;">
                    <div class="feature">
                        <span class="ico" style="background: rgba(59, 91, 253, .2); color: #a5b4fc;"><i class="fas fa-list-ol"></i></span>
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

            <div class="hero-foot small text-secondary">&copy; <?= date('Y'); ?> Depo Petikemas MTKI &middot; Samudera Logistics</div>
        </section>

        <!-- Form login -->
        <section class="panel">
            <div class="form-card p-1 lx-rise">
                <div class="mb-4">
                    <h3 class="fw-bold mb-1">Selamat datang 👋</h3>
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
                            <p class="hint mb-4"><i class="fas fa-circle-info me-1"></i> Gunakan nomor HP & nopol yang sama selama antrean berlangsung.</p>
                            <button type="submit" class="btn btn-primary btn-go w-100">
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
