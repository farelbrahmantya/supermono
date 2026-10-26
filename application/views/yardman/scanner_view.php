<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#1c1d21">
    <title>LASMONO - Yardman Scanner</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php $this->load->view('partials/theme'); ?>
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js" type="text/javascript"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        :root { --ink: #1c1d21; --ink-2: #26282e; --blue: #e5a720; --green: #10b981; --amber: #f59e0b; --red: #ef4444; }
        .top { position: relative; overflow: hidden; }
        .top::before { content: ""; position: absolute; width: 280px; height: 280px; border-radius: 50%; background: #e5a720; filter: blur(90px); opacity: .35; top: -150px; right: -80px; }
        .top > * { position: relative; z-index: 1; }
        .app { max-width: 480px; margin: 0 auto; min-height: 100vh; }

        /* Header + panel kamera gelap ala aplikasi kamera */
        .top { background: var(--ink); color: #fff; padding: 16px 16px 20px; border-bottom-left-radius: 24px; border-bottom-right-radius: 24px; }
        .brand-badge { font-size: 10px; letter-spacing: .5px; }
        .icon-btn { width: 40px; height: 40px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; background: rgba(255,255,255,.08); color: #fff; border: 1px solid rgba(255,255,255,.15); }
        .icon-btn:hover { color: #fff; background: rgba(255,255,255,.18); }

        .stat-chips { display: flex; gap: 8px; margin: 14px 0; }
        .stat-chip { flex: 1; background: var(--ink-2); border: 1px solid rgba(255,255,255,.08); border-radius: 14px; padding: 10px 12px; color: #fff; text-align: left; }
        .stat-chip .num { font-size: 22px; font-weight: 800; line-height: 1; }
        .stat-chip small { color: #94a3b8; font-size: 11px; }
        .stat-chip.late { border-color: var(--red); box-shadow: inset 0 0 0 1px var(--red); }

        .cam { --ar: 3 / 4; position: relative; width: 100%; max-width: 100%; aspect-ratio: var(--ar); margin: 0 auto; border-radius: 20px; overflow: hidden; background: #000; }
        #reader { position: absolute; inset: 0; border: none !important; }
        #reader > div { display: none !important; } /* label "Scanner paused" bawaan library */
        #reader video { width: 100% !important; height: 100% !important; object-fit: cover; }
        #reader img, #reader canvas, #qr-shaded-region { display: none !important; }

        /* Bingkai pindai: area luar digelapkan, sudut bingkai, garis pindai */
        .cam-frame { position: absolute; top: 50%; left: 50%; width: 68%; aspect-ratio: 1 / 1; transform: translate(-50%, -50%); border-radius: 18px; box-shadow: 0 0 0 999px rgba(5, 10, 20, .55); pointer-events: none; }
        .cam-frame i { position: absolute; width: 34px; height: 34px; border: 4px solid #fff; }
        .cam-frame i:nth-child(1) { top: -2px; left: -2px; border-right: 0; border-bottom: 0; border-top-left-radius: 18px; }
        .cam-frame i:nth-child(2) { top: -2px; right: -2px; border-left: 0; border-bottom: 0; border-top-right-radius: 18px; }
        .cam-frame i:nth-child(3) { bottom: -2px; left: -2px; border-right: 0; border-top: 0; border-bottom-left-radius: 18px; }
        .cam-frame i:nth-child(4) { bottom: -2px; right: -2px; border-left: 0; border-top: 0; border-bottom-right-radius: 18px; }
        .cam-frame .scanline { position: absolute; left: 8%; width: 84%; height: 2px; background: linear-gradient(90deg, transparent, #34d399, transparent); box-shadow: 0 0 12px #34d399; animation: scan 2.2s ease-in-out infinite; }
        @keyframes scan { 0%, 100% { top: 8%; } 50% { top: 92%; } }
        .cam.detected .cam-frame i { border-color: var(--green); }
        .cam.paused .cam-frame .scanline { display: none; }

        .cam-status { position: absolute; left: 50%; bottom: 14px; transform: translateX(-50%); background: rgba(15, 23, 42, .75); backdrop-filter: blur(6px); color: #fff; font-size: 12px; font-weight: 600; padding: 7px 14px; border-radius: 50px; white-space: nowrap; }
        .cam-tools { position: absolute; top: 12px; right: 12px; display: flex; flex-direction: column; gap: 8px; }
        .cam-tools .icon-btn { background: rgba(15, 23, 42, .6); backdrop-filter: blur(6px); }
        .cam-tools .icon-btn.active { background: var(--amber); color: #111; border-color: var(--amber); }
        .cam-off { position: absolute; inset: 0; display: none; flex-direction: column; align-items: center; justify-content: center; text-align: center; color: #cbd5e1; padding: 24px; background: radial-gradient(circle at center, #1e293b, #000); }
        .cam.off .cam-off { display: flex; }

        .manual { display: flex; gap: 8px; margin-top: 12px; }
        .manual input { background: var(--ink-2); border: 1px solid rgba(255,255,255,.12); color: #fff; border-radius: 12px; font-weight: 700; text-transform: uppercase; }
        .manual input::placeholder { color: #64748b; text-transform: none; font-weight: 500; }
        .manual input:focus { background: var(--ink-2); color: #fff; border-color: var(--blue); box-shadow: none; }

        .section { padding: 16px; }
        .card-soft { background: #fff; border-radius: 18px; box-shadow: 0 4px 18px rgba(15, 23, 42, .05); padding: 14px 16px; }
        .recent-item, .pending-item { display: flex; align-items: center; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f1f5f9; }
        .recent-item:last-child, .pending-item:last-child { border-bottom: none; }

        /* Lembar konfirmasi dari bawah */
        .sheet.offcanvas-bottom { height: auto; max-height: 88vh; border-top-left-radius: 24px; border-top-right-radius: 24px; }
        .sheet .grabber { width: 44px; height: 5px; border-radius: 5px; background: #cbd5e1; margin: 10px auto 0; }
        .step-pill { display: inline-flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 700; padding: 5px 12px; border-radius: 50px; }
        .step-1 { background: #fff3d1; color: #8a5a00; }
        .step-2 { background: #d1fae5; color: #047857; }
        .step-3 { background: #e0ecff; color: #1d4ed8; }
        .pick-box { background: #f5f8ff; border: 1px dashed #b9cdf5; border-radius: 14px; padding: 12px; }
        .pick-box input { font-size: 20px; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; }
        .pick-result { font-size: 13px; }
        .container-no { font-size: 30px; font-weight: 800; letter-spacing: .5px; color: #0f172a; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
        .info-grid div { background: #f8fafc; border-radius: 12px; padding: 8px 10px; }
        .info-grid small { display: block; color: #64748b; font-size: 11px; }
        .depo-tabs { display: flex; gap: 6px; background: #f1f5f9; padding: 4px; border-radius: 12px; }
        .depo-tabs button { flex: 1; border: none; background: transparent; padding: 8px; border-radius: 9px; font-weight: 700; color: #64748b; }
        .depo-tabs button.active { background: #fff; color: #0f172a; box-shadow: 0 1px 4px rgba(0,0,0,.08); }
        .block-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 8px; }
        .block-grid button { border: 2px solid #e2e8f0; background: #fff; border-radius: 12px; padding: 12px 0; font-weight: 800; color: #334155; }
        .block-grid button { position: relative; border-left-width: 6px; }
        .block-grid button.active { border-color: var(--green); background: #ecfdf5; color: #047857; }
        .block-grid button.dim { opacity: .45; }
        .block-grid button .tag { display: block; font-size: 9px; font-weight: 700; color: #6b6f78; letter-spacing: .02em; }
        .principal-note { font-size: 12px; border-radius: 10px; padding: 8px 10px; }
        .btn-confirm { padding: 14px; border-radius: 14px; font-weight: 800; font-size: 16px; }

        /* Kilat hasil sukses */
        .flash { position: fixed; inset: 0; z-index: 2000; display: none; align-items: center; justify-content: center; flex-direction: column; color: #fff; text-align: center; padding: 24px; background: rgba(16, 185, 129, .96); }
        .flash.show { display: flex; animation: pop .25s ease-out; }
        .flash i { font-size: 84px; margin-bottom: 14px; }
        @keyframes pop { from { opacity: 0; transform: scale(.96); } to { opacity: 1; transform: scale(1); } }
    </style>
</head>
<body>
    <div class="app">
        <div class="top lx-waves">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="d-flex align-items-center gap-2">
                        <?php $this->load->view('partials/logo', array('size' => 36)); ?>
                        <div>
                            <div class="d-flex align-items-center gap-2"><span class="lx-brand">LASMONO</span><span class="badge bg-warning text-dark fw-bold brand-badge">YARDMAN</span></div>
                            <small style="color: #94a3b8;"><?= html_escape($this->session->userdata('name')); ?></small>
                        </div>
                    </div>
                </div>
                <a href="<?= base_url('auth/logout'); ?>" class="icon-btn" title="Keluar"><i class="fas fa-power-off"></i></a>
            </div>

            <!-- Ringkasan pending (ketuk untuk melihat daftar) -->
            <div class="stat-chips">
                <button type="button" class="stat-chip" id="chipWaiting" data-bs-toggle="offcanvas" data-bs-target="#pendingSheet">
                    <div class="num" id="countWaiting">0</div><small>Menunggu survey / muat</small>
                </button>
                <button type="button" class="stat-chip" id="chipSurvey" data-bs-toggle="offcanvas" data-bs-target="#pendingSheet">
                    <div class="num" id="countSurvey">0</div><small>Sedang survey</small>
                </button>
            </div>

            <!-- Kamera -->
            <div class="cam off" id="cam">
                <div id="reader"></div>
                <div class="cam-frame"><i></i><i></i><i></i><i></i><div class="scanline"></div></div>
                <div class="cam-tools">
                    <button type="button" class="icon-btn" id="btnTorch" title="Flash" style="display:none;"><i class="fas fa-bolt"></i></button>
                    <button type="button" class="icon-btn" id="btnSwitch" title="Ganti kamera" style="display:none;"><i class="fas fa-sync-alt"></i></button>
                </div>
                <div class="cam-status" id="camStatus">Arahkan kamera ke QR di HP supir</div>
                <div class="cam-off">
                    <i class="fas fa-camera fa-3x mb-3 opacity-75"></i>
                    <div class="fw-semibold mb-1" id="camOffTitle">Kamera belum aktif</div>
                    <small class="mb-3 opacity-75" id="camOffText">Ketuk tombol di bawah untuk mulai memindai.</small>
                    <button type="button" class="btn btn-light fw-bold px-4 rounded-pill" id="btnStartCam"><i class="fas fa-play me-1"></i> Aktifkan Kamera</button>
                </div>
            </div>

            <div class="small text-center mt-2" style="color: #a9adb6;"><i class="fas fa-circle-info me-1"></i> Scan QR supir atau barcode kitir &middot; jarak ideal 20&ndash;30 cm</div>

            <!-- Input manual -->
            <div class="manual">
                <input type="text" id="manualQueueInput" class="form-control" placeholder="No. antrean / kitir, contoh D1578461" autocomplete="off">
                <button type="button" id="btnManualSubmit" class="btn btn-primary fw-bold px-3" style="border-radius: 12px;"><i class="fas fa-arrow-right"></i></button>
            </div>
        </div>

        <!-- Riwayat scan sesi ini -->
        <div class="section">
            <div class="card-soft">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="fw-bold small text-secondary"><i class="fas fa-history me-1"></i> RIWAYAT SCAN</span>
                    <small class="text-muted" id="recentCount">0 scan</small>
                </div>
                <div id="recentScanList">
                    <div class="text-center py-3 text-muted small" id="emptyRecentText">Belum ada scan pada sesi ini.</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Lembar konfirmasi hasil scan -->
    <div class="offcanvas offcanvas-bottom sheet" tabindex="-1" id="confirmSheet" data-bs-backdrop="static">
        <div class="grabber"></div>
        <div class="offcanvas-body pt-3 pb-4">
            <div id="sheetMessage" class="alert mb-3" style="display:none;"></div>

            <div id="sheetTruck">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="step-pill" id="stepPill"></span>
                    <small class="text-muted" id="stageTime"></small>
                </div>
                <div class="container-no" id="sheetContainer"></div>
                <div class="text-muted mb-3"><i class="fas fa-truck me-1"></i> <b id="sheetPlate"></b> &middot; #<span id="sheetQueue"></span></div>
                <div class="info-grid mb-3">
                    <div><small>Principal</small><b id="sheetPrincipal"></b></div>
                    <div><small>Size</small><b id="sheetSize"></b></div>
                    <div style="grid-column: span 2;"><small>Customer</small><b id="sheetCustomer"></b></div>
                </div>

                <!-- Export: nomor container dipilih customer & Yardman di lapangan, dicek ke database depo -->
                <div id="containerPicker" class="pick-box mb-3" style="display:none;">
                    <div class="fw-bold small text-secondary mb-2">NOMOR CONTAINER YANG DIMUAT</div>
                    <div class="d-flex gap-2">
                        <input type="text" id="pickContainerInput" class="form-control" placeholder="ONEU1234567" maxlength="13" autocomplete="off">
                        <button type="button" class="btn btn-primary fw-bold px-3" id="btnCheckContainer">Cek</button>
                    </div>
                    <div class="pick-result mt-2" id="pickResult"></div>
                </div>

                <!-- Pilih Depo & Blok (survey selesai / muat container) -->
                <div id="zonePicker" style="display:none;">
                    <div class="fw-bold small text-secondary mb-2" id="zonePickerTitle">PILIH DEPO &amp; BLOK PENEMPATAN</div>
                    <div class="depo-tabs mb-2" id="depoTabs"></div>
                    <div class="block-grid mb-2" id="blockGrid"></div>
                    <div class="principal-note alert alert-warning mb-3" id="principalNote" style="display:none;"></div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="button" class="btn btn-light fw-bold btn-confirm" style="flex: 0 0 34%;" id="btnSheetCancel">Batal</button>
                <button type="button" class="btn btn-success btn-confirm flex-fill" id="btnSheetConfirm"></button>
            </div>
        </div>
    </div>

    <!-- Daftar pending -->
    <div class="offcanvas offcanvas-bottom sheet" tabindex="-1" id="pendingSheet">
        <div class="grabber"></div>
        <div class="offcanvas-body pt-3">
            <div class="fw-bold small text-secondary mb-1"><i class="fas fa-hourglass-half me-1"></i> MENUNGGU MASUK SURVEY (REPO IN / IMPORT)</div>
            <div id="pendingWaitingList" class="mb-3"></div>
            <div class="fw-bold small text-secondary mb-1"><i class="fas fa-dolly me-1"></i> MENUNGGU MUAT (EXPORT / REPO OUT)</div>
            <div id="pendingMuatList" class="mb-3"></div>
            <div class="fw-bold small text-secondary mb-1"><i class="fas fa-search me-1"></i> SEDANG SURVEY (BELUM SELESAI)</div>
            <div id="pendingSurveyList"></div>
        </div>
    </div>

    <!-- Kilat sukses -->
    <div class="flash" id="flash"><i class="fas fa-check-circle"></i><div class="h4 fw-bold mb-1" id="flashTitle"></div><div id="flashText"></div></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const CSRF_NAME = "<?= $this->security->get_csrf_token_name(); ?>";
        let CSRF_HASH = "<?= $this->security->get_csrf_hash(); ?>";
        const STAGE_KPI = <?= (int) MY_Controller::STAGE_KPI_MINUTES; ?>;

        // Blok penempatan dikelompokkan per depo
        const ZONES = {};
        <?php foreach ($zones as $zone): ?>
        (ZONES[<?= json_encode($zone->depo, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>] = ZONES[<?= json_encode($zone->depo, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>] || []).push(<?= json_encode(array('code' => $zone->zone_code, 'principal' => isset($zone->principal) ? $zone->principal : NULL, 'color' => isset($zone->principal_color) ? $zone->principal_color : NULL), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>);
        <?php endforeach; ?>

        function esc(value) {
            return $('<div>').text(value === null || value === undefined ? '' : String(value)).html();
        }

        // Token di halaman bisa basi: ambil token baru lalu ulangi request sekali
        function refreshCsrf(done) {
            $.ajax({ url: "<?= base_url('auth/csrf_token'); ?>", data: { _: Date.now() }, dataType: "json" })
                .done(function(res) { CSRF_HASH = res.hash; done(true); })
                .fail(function() { done(false); });
        }

        function postJson(url, data, onSuccess, onFail, retried) {
            data[CSRF_NAME] = CSRF_HASH;
            $.ajax({ url: url, type: "POST", data: data, dataType: "json" })
                .done(onSuccess)
                .fail(function(xhr) {
                    if (xhr.status === 403 && !retried) {
                        refreshCsrf(function(ok) { ok ? postJson(url, data, onSuccess, onFail, true) : onFail(xhr); });
                        return;
                    }
                    if (xhr.status === 401) {
                        window.location.reload();
                        return;
                    }
                    onFail(xhr);
                });
        }

        function feedback(success) {
            try { if (navigator.vibrate) navigator.vibrate(success ? [80] : [100, 60, 100]); } catch (e) {}
            try {
                let ctx = new (window.AudioContext || window.webkitAudioContext)();
                let osc = ctx.createOscillator();
                let gain = ctx.createGain();
                osc.frequency.value = success ? 880 : 300;
                gain.gain.value = 0.12;
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.start();
                osc.stop(ctx.currentTime + 0.15);
            } catch (e) {}
        }

        /* ===================== KAMERA ===================== */
        let scanner = null;
        let cameras = [];
        let cameraIndex = -1;
        let torchOn = false;
        let busy = false;
        let lastDone = { code: '', time: 0 };

        function camStatus(text) { $('#camStatus').text(text); }

        function showCamOff(title, text) {
            $('#cam').addClass('off');
            $('#camOffTitle').text(title);
            $('#camOffText').text(text);
            $('#btnTorch, #btnSwitch').hide();
        }

        // Mode kamera: 0 = kamera belakang 1280x720, 1 = kamera belakang tanpa syarat resolusi (cadangan iPhone)
        let simpleMode = 0;
        let watchdog = null;

        function startCamera() {
            if (typeof Html5Qrcode === 'undefined') {
                showCamOff('Pemindai gagal dimuat', 'Periksa koneksi internet, lalu muat ulang halaman. Input manual tetap bisa dipakai.');
                return;
            }
            if (!window.isSecureContext || !navigator.mediaDevices) {
                showCamOff('Kamera tidak tersedia', 'Kamera hanya bisa dipakai lewat HTTPS. Gunakan input manual.');
                return;
            }
            if (!scanner) {
                // QR supir + barcode garis di kertas kitir
                scanner = new Html5Qrcode('reader', { verbose: false, formatsToSupport: [Html5QrcodeSupportedFormats.QR_CODE, Html5QrcodeSupportedFormats.CODE_128, Html5QrcodeSupportedFormats.CODE_39] });
            }
            // Kamera dipilih manual lewat tombol ganti kamera; bawaan = kamera belakang utama dari browser
            let useDevice = cameraIndex >= 0 && cameras[cameraIndex];
            let source = useDevice ? { deviceId: { exact: cameras[cameraIndex].id } } : { facingMode: 'environment' };
            let config = { fps: 10 };
            if (simpleMode === 0) {
                config.videoConstraints = $.extend({}, useDevice ? { deviceId: { exact: cameras[cameraIndex].id } } : { facingMode: { ideal: 'environment' } },
                    { width: { ideal: 1280 }, height: { ideal: 720 } });
            }

            $('#cam').removeClass('off');
            camStatus('Menyalakan kamera...');
            scanner.start(source, config, onDecoded, function() {})
                .then(function() {
                    camStatus('Arahkan kamera ke QR di HP supir');
                    ensureVideoPlaying();
                    fitCameraBox();
                    setupCameraTools();
                })
                .catch(function(err) {
                    let text = String(err);
                    let denied = text.toLowerCase().indexOf('permission') !== -1 || text.indexOf('NotAllowed') !== -1;
                    // Resolusi tidak didukung: coba lagi tanpa syarat resolusi
                    if (!denied && simpleMode === 0) {
                        simpleMode = 1;
                        startCamera();
                        return;
                    }
                    showCamOff(denied ? 'Izin kamera ditolak' : 'Kamera tidak bisa dibuka',
                        denied ? 'Izinkan akses kamera di pengaturan browser, lalu ketuk Aktifkan Kamera.' : 'Tutup aplikasi lain yang memakai kamera, lalu coba lagi.');
                });
        }

        // Safari iPhone: video wajib inline + muted dan diputar manual, jika tidak gambar tetap hitam
        function ensureVideoPlaying() {
            let video = document.querySelector('#reader video');
            if (!video) return;
            video.setAttribute('playsinline', '');
            video.setAttribute('webkit-playsinline', '');
            video.setAttribute('autoplay', '');
            video.muted = true;
            try { let p = video.play(); if (p && p.catch) p.catch(function() {}); } catch (e) {}

            // Jika 3 detik gambar belum bergerak, nyalakan ulang kamera dengan mode cadangan
            clearTimeout(watchdog);
            let startTime = video.currentTime;
            watchdog = setTimeout(function() {
                let v = document.querySelector('#reader video');
                let frozen = !v || v.readyState < 2 || !v.videoWidth || v.currentTime === startTime;
                if (frozen && simpleMode === 0 && !$('#cam').hasClass('paused')) {
                    simpleMode = 1;
                    camStatus('Menyesuaikan kamera...');
                    scanner.stop().then(startCamera).catch(startCamera);
                }
            }, 3000);
        }

        function setupCameraTools() {
            torchOn = false;
            $('#btnTorch').removeClass('active');
            try {
                let caps = scanner.getRunningTrackCapabilities();
                $('#btnTorch').toggle(!!(caps && caps.torch));
            } catch (e) { $('#btnTorch').hide(); }

            Html5Qrcode.getCameras().then(function(list) {
                cameras = list || [];
                $('#btnSwitch').toggle(cameras.length > 1);
            }).catch(function() { $('#btnSwitch').hide(); });
        }

        // Kotak kamera mengikuti rasio video asli agar gambar tidak terpotong
        function fitCameraBox() {
            let video = document.querySelector('#reader video');
            if (!video) return;
            let apply = function() {
                if (video.videoWidth && video.videoHeight) {
                    // Lebar dibatasi agar tinggi kotak maksimal 62% layar
                    let ratio = video.videoWidth / video.videoHeight;
                    let width = Math.min($('#cam').parent().width(), window.innerHeight * 0.62 * ratio);
                    $('#cam').css({ '--ar': video.videoWidth + ' / ' + video.videoHeight, width: Math.round(width) + 'px' });
                }
            };
            apply();
            video.addEventListener('loadedmetadata', apply);
            video.addEventListener('resize', apply);
        }

        $('#btnStartCam').on('click', startCamera);

        $('#btnTorch').on('click', function() {
            torchOn = !torchOn;
            scanner.applyVideoConstraints({ advanced: [{ torch: torchOn }] })
                .then(function() { $('#btnTorch').toggleClass('active', torchOn); })
                .catch(function() { torchOn = !torchOn; });
        });

        $('#btnSwitch').on('click', function() {
            if (!cameras.length) return;
            cameraIndex = (cameraIndex + 1) % cameras.length;
            simpleMode = 0;
            let label = cameras[cameraIndex].label || ('Kamera ' + (cameraIndex + 1));
            scanner.stop().then(startCamera).catch(startCamera);
            setTimeout(function() { camStatus(label); }, 1200);
            setTimeout(function() { if (!$('#cam').hasClass('paused')) camStatus('Arahkan kamera ke QR di HP supir'); }, 3500);
        });

        function pauseCamera() {
            $('#cam').addClass('paused');
            try { if (scanner && scanner.getState && scanner.getState() === Html5QrcodeScannerState.SCANNING) scanner.pause(true); } catch (e) {}
        }

        function resumeCamera() {
            $('#cam').removeClass('paused detected');
            camStatus('Arahkan kamera ke QR di HP supir');
            try { if (scanner && scanner.getState && scanner.getState() === Html5QrcodeScannerState.PAUSED) scanner.resume(); } catch (e) {}
            ensureVideoPlaying();
        }

        function onDecoded(text) {
            if (busy) return;
            let raw = String(text).trim().toUpperCase();
            let match = raw.match(/A-\d{8}-\d{3}/);
            let code = match ? match[0] : raw;
            // QR yang baru diproses masih di depan kamera: abaikan beberapa detik
            if (code === lastDone.code && Date.now() - lastDone.time < 6000) return;
            $('#cam').addClass('detected');
            lookupQueue(code);
        }

        /* ===================== KONFIRMASI ===================== */
        const sheet = new bootstrap.Offcanvas(document.getElementById('confirmSheet'));
        let current = null;
        let selectedZone = '';
        let pickedContainer = '';

        function sheetMessage(type, text) {
            $('#sheetMessage').removeClass('alert-danger alert-warning alert-success').addClass('alert-' + type)
                .html(esc(text).replace(/\n/g, '<br>')).toggle(!!text);
        }

        function lookupQueue(code) {
            busy = true;
            pauseCamera();
            camStatus('Membaca data truk...');
            feedback(true);
            postJson("<?= base_url('yardman/lookup'); ?>", { queue_number: code }, function(res) {
                current = res.status === 'success' ? res : null;
                current && (current.code = code);
                renderSheet(res);
                sheet.show();
            }, function() {
                renderSheet({ status: 'error', message: 'Gagal terhubung ke server. Coba scan ulang.' });
                sheet.show();
            });
        }

        function renderSheet(res) {
            selectedZone = '';
            pickedContainer = '';
            $('#containerPicker').hide();
            $('#pickContainerInput').val('');
            $('#pickResult').html('');
            if (res.status !== 'success') {
                feedback(false);
                $('#sheetTruck').hide();
                sheetMessage(res.status === 'warning' ? 'warning' : 'danger', res.message);
                $('#btnSheetConfirm').text('Scan Berikutnya').removeClass('btn-success btn-primary').addClass('btn-dark').prop('disabled', false).data('action', 'close');
                return;
            }

            let q = res.queue;
            sheetMessage('', '');
            $('#sheetTruck').show();
            $('#sheetContainer').text(q.container_no || '-');
            $('#sheetPlate').text(q.license_plate);
            $('#sheetQueue').text(q.queue_number);
            $('#sheetPrincipal').text(q.principal || '-');
            $('#sheetSize').text(q.container_size || '-');
            $('#sheetCustomer').text(q.customer || '-');
            let target = q.stage_target || STAGE_KPI;
            $('#stageTime').html('<i class="fas fa-stopwatch me-1"></i>' + esc(q.stage_minutes) + ' / ' + esc(target) + ' mnt di tahap ini')
                .toggleClass('text-danger fw-bold', q.stage_minutes > target)
                .toggleClass('text-muted', q.stage_minutes <= target);
            $('#zonePickerTitle').html(res.step === 3 ? 'PILIH DEPO &amp; BLOK PENGAMBILAN CONTAINER' : 'PILIH DEPO &amp; BLOK PENEMPATAN');

            if (res.step === 3) {
                // Export / Repo Out: tanpa survey, langsung muat container
                $('#stepPill').attr('class', 'step-pill step-3').html('<i class="fas fa-dolly"></i> MUAT CONTAINER &middot; ' + esc(String(q.activity_label).toUpperCase()));
                $('#principalNote').hide();
                if (q.need_container) {
                    $('#sheetContainer').html('<span class="text-muted fs-5">Pilih container</span>');
                    $('#containerPicker').show();
                }
                renderZonePicker();
                $('#zonePicker').toggle(Object.keys(ZONES).length > 0);
                $('#btnSheetConfirm').html('<i class="fas fa-check me-1"></i> SUBMIT MUAT').removeClass('btn-dark btn-primary').addClass('btn-success')
                    .data('action', 'confirm');
                updateConfirmState();
            } else if (res.step === 1) {
                $('#stepPill').attr('class', 'step-pill step-1').html('<i class="fas fa-sign-in-alt"></i> LANGKAH 1 &middot; MASUK SURVEY');
                $('#zonePicker').hide();
                $('#btnSheetConfirm').html('<i class="fas fa-check me-1"></i> MULAI SURVEY').removeClass('btn-dark btn-success').addClass('btn-primary').prop('disabled', false).data('action', 'confirm');
            } else {
                $('#stepPill').attr('class', 'step-pill step-2').html('<i class="fas fa-flag-checkered"></i> LANGKAH 2 &middot; SURVEY SELESAI');
                $('#principalNote').hide();
                renderZonePicker();
                $('#zonePicker').toggle(Object.keys(ZONES).length > 0);
                $('#btnSheetConfirm').html('<i class="fas fa-check me-1"></i> SELESAI SURVEY').removeClass('btn-dark btn-primary').addClass('btn-success')
                    .prop('disabled', Object.keys(ZONES).length > 0).data('action', 'confirm');
            }
        }

        // Cocokkan principal kitir dengan principal blok (contoh: "CMA" ~ "CMA CGM", "WHL" ~ "WAN HAI")
        const PRINCIPAL_ALIAS = { WHL: 'WANHAI', WANHAILINES: 'WANHAI', KMTCLINES: 'KMTC', CMA: 'CMACGM' };
        function normPrincipal(name) {
            let n = String(name || '').toUpperCase().replace(/[^A-Z]/g, '');
            return PRINCIPAL_ALIAS[n] || n;
        }
        function principalMatch(zone) {
            let want = normPrincipal(current && current.queue ? current.queue.principal : '');
            return !want || !zone.principal || normPrincipal(zone.principal) === want;
        }
        function findZone(code) {
            let found = null;
            $.each(ZONES, function(depo, list) { list.forEach(function(z) { if (z.code === code) found = z; }); });
            return found;
        }

        function renderZonePicker(activeDepo) {
            let depos = Object.keys(ZONES);
            if (!activeDepo) {
                // Buka depo yang punya blok sesuai principal container
                activeDepo = depos.filter(function(d) { return ZONES[d].some(principalMatch); })[0] || depos[0];
            }
            $('#depoTabs').html(depos.map(function(d) {
                return '<button type="button" data-depo="' + esc(d) + '" class="' + (d === activeDepo ? 'active' : '') + '">' + esc(d) + '</button>';
            }).join(''));
            $('#blockGrid').html((ZONES[activeDepo] || []).map(function(z) {
                let cls = (z.code === selectedZone ? 'active ' : '') + (principalMatch(z) ? '' : 'dim');
                return '<button type="button" data-zone="' + esc(z.code) + '" class="' + cls + '" style="border-left-color: ' + esc(z.color || '#e2e8f0') + ';">' +
                    esc(z.code) + '<span class="tag">' + esc(z.principal || '') + '</span></button>';
            }).join(''));
        }

        $('#depoTabs').on('click', 'button', function() { renderZonePicker($(this).data('depo')); });
        $('#blockGrid').on('click', 'button', function() {
            selectedZone = $(this).data('zone');
            $('#blockGrid button').removeClass('active');
            $(this).addClass('active');
            let zone = findZone(selectedZone);
            let mismatch = zone && !principalMatch(zone);
            $('#principalNote').toggle(!!mismatch).html(mismatch
                ? '<i class="fas fa-triangle-exclamation me-1"></i> Blok ' + esc(zone.code) + ' untuk principal <b>' + esc(zone.principal) + '</b>, container ini <b>' + esc(current.queue.principal) + '</b>. Pastikan penempatan benar.'
                : '');
            if (current.step === 3) {
                updateConfirmState();
            } else {
                $('#btnSheetConfirm').prop('disabled', false).html('<i class="fas fa-check me-1"></i> SELESAI SURVEY &middot; BLOK ' + esc(selectedZone));
            }
        });

        // Tombol submit muat aktif jika blok sudah dipilih & (Export) container sudah lolos cek
        function updateConfirmState() {
            if (!current || current.step !== 3) return;
            let needZone = Object.keys(ZONES).length > 0;
            let ready = (!needZone || selectedZone) && (!current.queue.need_container || pickedContainer);
            $('#btnSheetConfirm').prop('disabled', !ready).html('<i class="fas fa-check me-1"></i> SUBMIT MUAT' +
                (pickedContainer ? ' &middot; ' + esc(pickedContainer) : '') + (selectedZone ? ' &middot; BLOK ' + esc(selectedZone) : ''));
        }

        $('#pickContainerInput').on('input', function() {
            pickedContainer = '';
            $('#pickResult').html('');
            updateConfirmState();
        }).on('keypress', function(e) { if (e.which === 13) $('#btnCheckContainer').click(); });

        $('#btnCheckContainer').on('click', function() {
            if (!current) return;
            let no = $('#pickContainerInput').val().toUpperCase().replace(/[^A-Z0-9]/g, '');
            if (!no) return;
            let btn = $(this).prop('disabled', true);
            $('#pickResult').html('<span class="text-muted">Mengecek ke database depo...</span>');
            postJson("<?= base_url('yardman/check_container'); ?>", { queue_number: current.queue.queue_number, container_no: no }, function(res) {
                btn.prop('disabled', false);
                if (res.status !== 'success') {
                    feedback(false);
                    pickedContainer = '';
                    $('#pickResult').html('<span class="text-danger fw-semibold"><i class="fas fa-circle-xmark me-1"></i>' + esc(res.message).replace(/\n/g, '<br>') + '</span>');
                } else {
                    let c = res.container;
                    pickedContainer = c.container_no;
                    $('#pickContainerInput').val(c.container_no);
                    $('#sheetContainer').text(c.container_no);
                    $('#sheetSize').text(c.size || '-');
                    $('#pickResult').html('<span class="text-success fw-semibold"><i class="fas fa-circle-check me-1"></i>Cocok: ' + esc(c.principal) + ' &middot; ' + esc(c.size || c.iso_code || '-') + ' &middot; status ' + esc(c.last_act) + ' / ' + esc(c.last_cond) + ' (boleh dimuat)</span>');
                }
                updateConfirmState();
            }, function() {
                btn.prop('disabled', false);
                $('#pickResult').html('<span class="text-danger">Gagal terhubung ke server.</span>');
            });
        });

        $('#btnSheetCancel').on('click', function() { sheet.hide(); });
        document.getElementById('confirmSheet').addEventListener('hidden.bs.offcanvas', function() {
            busy = false;
            current = null;
            resumeCamera();
        });

        $('#btnSheetConfirm').on('click', function() {
            if ($(this).data('action') !== 'confirm' || !current) {
                sheet.hide();
                return;
            }
            let btn = $(this).prop('disabled', true);
            let code = current.queue.queue_number;
            postJson("<?= base_url('yardman/process_scan'); ?>", { queue_number: code, zone_code: selectedZone, container_no: pickedContainer }, function(res) {
                if (res.status !== 'success') {
                    feedback(false);
                    btn.prop('disabled', false);
                    sheetMessage(res.status === 'warning' ? 'warning' : 'danger', res.message);
                    return;
                }
                lastDone = { code: code, time: Date.now() };
                showFlash(res.message);
                addRecentScan(code, pickedContainer || (current ? current.queue.container_no : ''), res.message);
                loadPending();
                sheet.hide();
            }, function() {
                btn.prop('disabled', false);
                sheetMessage('danger', 'Gagal terhubung ke server. Coba lagi.');
            });
        });

        function showFlash(message) {
            let lines = String(message).split('\n');
            $('#flashTitle').text(lines[0]);
            $('#flashText').html(esc(lines.slice(1).join('\n')).replace(/\n/g, '<br>'));
            $('#flash').addClass('show');
            feedback(true);
            setTimeout(function() { $('#flash').removeClass('show'); }, 1600);
        }

        /* ===================== INPUT MANUAL ===================== */
        $('#btnManualSubmit').on('click', function() {
            let code = $('#manualQueueInput').val().trim().toUpperCase();
            if (code === '' || busy) return;
            $('#manualQueueInput').val('').blur();
            lookupQueue(code);
        });
        $('#manualQueueInput').on('keypress', function(e) { if (e.which === 13) $('#btnManualSubmit').click(); });

        /* ===================== RIWAYAT & PENDING ===================== */
        let recentCount = 0;
        function addRecentScan(code, container, msg) {
            $('#emptyRecentText').remove();
            recentCount++;
            $('#recentCount').text(recentCount + ' scan');
            let now = new Date();
            let time = (now.getHours() < 10 ? '0' : '') + now.getHours() + ':' + (now.getMinutes() < 10 ? '0' : '') + now.getMinutes();
            let done = /SURVEY SELESAI|MUAT CONTAINER SELESAI/.test(msg);
            $('#recentScanList').prepend(
                '<div class="recent-item"><div>' +
                    '<div class="fw-bold">' + esc(container || code) + '</div>' +
                    '<small class="text-muted">' + esc(code) + ' &middot; ' + esc(String(msg).split('\n')[0]) + '</small>' +
                '</div><span class="badge ' + (done ? 'bg-success' : 'bg-primary') + '">' + time + '</span></div>'
            );
            $('#recentScanList .recent-item').slice(6).remove();
        }

        function renderPendingList(rows, listId) {
            if (!rows.length) {
                $(listId).html('<div class="text-muted small py-2">Tidak ada truk.</div>');
                return;
            }
            $(listId).html(rows.map(function(r) {
                return '<div class="pending-item"><div>' +
                    '<div class="fw-bold">' + esc(r.container_no || r.queue_number) + ' <span class="act-tag act-' + esc(r.activity) + '">' + esc(r.activity_label) + '</span></div>' +
                    '<small class="text-muted">' + esc(r.license_plate) + ' &middot; #' + esc(r.queue_number) + '</small></div>' +
                    '<span class="badge ' + (r.overdue ? 'bg-danger' : 'bg-light text-dark border') + '">' + esc(r.waiting_minutes) + ' mnt</span></div>';
            }).join(''));
        }

        function loadPending() {
            $.ajax({ url: "<?= base_url('yardman/pending_json'); ?>", data: { _: Date.now() }, dataType: "json" })
                .done(function(res) {
                    let all = res.waiting_survey || [], survey = res.in_survey || [];
                    let isMuat = function(r) { return r.activity === 'D' || r.activity === 'E'; };
                    let waiting = all.filter(function(r) { return !isMuat(r); });
                    let muat = all.filter(isMuat);
                    $('#countWaiting').text(all.length);
                    $('#countSurvey').text(survey.length);
                    $('#chipWaiting').toggleClass('late', all.some(function(r) { return r.overdue; }));
                    $('#chipSurvey').toggleClass('late', survey.some(function(r) { return r.overdue; }));
                    renderPendingList(waiting, '#pendingWaitingList');
                    renderPendingList(muat, '#pendingMuatList');
                    renderPendingList(survey, '#pendingSurveyList');
                })
                .fail(function(xhr) { if (xhr.status === 401) window.location.reload(); });
        }
        loadPending();
        setInterval(loadPending, 5000);

        // Kamera langsung menyala saat halaman dibuka (jika izin sudah pernah diberikan)
        startCamera();
    </script>
</body>
</html>
