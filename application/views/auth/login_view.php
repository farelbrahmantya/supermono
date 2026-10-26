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
        .yard-art { display: block; width: 100%; max-width: 460px; height: auto; margin-bottom: 26px; }
        .yard-art .truck { animation: lx-drive 9s ease-in-out infinite; }
        .yard-art .hook { animation: lx-hook 4.5s ease-in-out infinite; transform-box: fill-box; }
        @keyframes lx-drive { 0%, 12% { transform: translateX(0); } 50%, 62% { transform: translateX(-46px); } 100% { transform: translateX(0); } }
        @keyframes lx-hook { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(10px); } }
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
            <div class="d-flex align-items-center gap-3">
                <?php $this->load->view('partials/logo', array('size' => 44)); ?>
                <div>
                    <div class="lx-brand fs-5">LASMONO</div>
                    <div class="small text-secondary">Depo Petikemas MTKI</div>
                </div>
            </div>

            <div class="my-4 my-lg-0">
                <svg class="yard-art d-none d-lg-block" viewBox="0 0 460 150" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Ilustrasi container yard">
                    <defs>
                        <pattern id="ribs" width="6" height="10" patternUnits="userSpaceOnUse"><rect width="6" height="10" fill="none"/><line x1="3" y1="0" x2="3" y2="10" stroke="#000" stroke-opacity=".16" stroke-width="1.4"/></pattern>
                    </defs>
                    <!-- Tanah yard -->
                    <rect x="0" y="128" width="460" height="2" rx="1" fill="#ffffff" opacity=".18"/>
                    <path d="M0 140 Q12 134 24 140 T48 140 T72 140 T96 140 T120 140 T144 140 T168 140 T192 140 T216 140 T240 140 T264 140 T288 140 T312 140 T336 140 T360 140 T384 140 T408 140 T432 140 T456 140" fill="none" stroke="#f6c64a" stroke-opacity=".35" stroke-width="2"/>

                    <!-- Tumpukan container kiri -->
                    <g>
                        <rect x="8" y="104" width="78" height="24" rx="2" fill="#d72a2f"/><rect x="8" y="104" width="78" height="24" rx="2" fill="url(#ribs)"/>
                        <rect x="8" y="80" width="78" height="24" rx="2" fill="#e5a720"/><rect x="8" y="80" width="78" height="24" rx="2" fill="url(#ribs)"/>
                        <rect x="8" y="56" width="78" height="24" rx="2" fill="#f4f1ea"/><rect x="8" y="56" width="78" height="24" rx="2" fill="url(#ribs)"/>
                        <rect x="90" y="104" width="78" height="24" rx="2" fill="#3a3d45"/><rect x="90" y="104" width="78" height="24" rx="2" fill="url(#ribs)"/>
                        <rect x="90" y="80" width="78" height="24" rx="2" fill="#d72a2f"/><rect x="90" y="80" width="78" height="24" rx="2" fill="url(#ribs)"/>
                    </g>

                    <!-- Crane RTG emas di atas tumpukan tengah -->
                    <g>
                        <rect x="184" y="104" width="78" height="24" rx="2" fill="#e5a720"/><rect x="184" y="104" width="78" height="24" rx="2" fill="url(#ribs)"/>
                        <rect x="184" y="80" width="78" height="24" rx="2" fill="#f4f1ea"/><rect x="184" y="80" width="78" height="24" rx="2" fill="url(#ribs)"/>
                        <rect x="176" y="18" width="7" height="110" fill="#f6c64a"/>
                        <rect x="265" y="18" width="7" height="110" fill="#f6c64a"/>
                        <rect x="170" y="12" width="108" height="10" rx="2" fill="#f6c64a"/>
                        <rect x="210" y="22" width="26" height="9" rx="2" fill="#ffffff" opacity=".9"/>
                        <g class="hook">
                            <line x1="216" y1="31" x2="216" y2="50" stroke="#ffffff" stroke-opacity=".7" stroke-width="1.5"/>
                            <line x1="230" y1="31" x2="230" y2="50" stroke="#ffffff" stroke-opacity=".7" stroke-width="1.5"/>
                            <rect x="196" y="50" width="54" height="5" rx="1.5" fill="#ffffff" opacity=".85"/>
                            <rect x="194" y="55" width="58" height="20" rx="2" fill="#d72a2f"/><rect x="194" y="55" width="58" height="20" rx="2" fill="url(#ribs)"/>
                        </g>
                        <rect x="172" y="124" width="15" height="6" rx="3" fill="#24262c"/><rect x="261" y="124" width="15" height="6" rx="3" fill="#24262c"/>
                    </g>

                    <!-- Truk trailer membawa container -->
                    <g class="truck">
                        <rect x="318" y="92" width="88" height="24" rx="2" fill="#f4f1ea"/><rect x="318" y="92" width="88" height="24" rx="2" fill="url(#ribs)"/>
                        <rect x="314" y="116" width="96" height="5" rx="1.5" fill="#6b6f78"/>
                        <path d="M412 98 h22 l12 12 v11 h-34 z" fill="#d72a2f"/>
                        <rect x="418" y="102" width="14" height="8" rx="1.5" fill="#ffffff" opacity=".85"/>
                        <circle cx="330" cy="124" r="6" fill="#1c1d21" stroke="#a9adb6" stroke-width="2"/>
                        <circle cx="346" cy="124" r="6" fill="#1c1d21" stroke="#a9adb6" stroke-width="2"/>
                        <circle cx="398" cy="124" r="6" fill="#1c1d21" stroke="#a9adb6" stroke-width="2"/>
                        <circle cx="434" cy="124" r="6" fill="#1c1d21" stroke="#a9adb6" stroke-width="2"/>
                    </g>
                </svg>
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
