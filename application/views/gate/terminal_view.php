<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LASMONO - Gate Security Terminal</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <?php $this->load->view('partials/theme'); ?>
    <style>
        body { min-height: 100vh; display: flex; flex-direction: column; }

        /* Banner mode terminal */
        .mode-banner { position: relative; overflow: hidden; border-radius: 22px; padding: 22px 28px; color: #fff; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 16px 36px rgba(15, 23, 42, .12); }
        .mode-banner::after { content: ""; position: absolute; right: -60px; top: -80px; width: 280px; height: 280px; border-radius: 50%; background: rgba(255, 255, 255, .12); }
        .mode-banner > * { position: relative; z-index: 1; }
        .mode-banner.gate-in { background: linear-gradient(135deg, #059669 0%, #0d9488 100%); }
        .mode-banner.gate-out { background: linear-gradient(135deg, #e11d48 0%, #be123c 100%); }
        .mode-banner.control-tower { background: linear-gradient(135deg, #1c1d21 0%, #30323a 100%); border-bottom: 4px solid var(--lx-primary); }
        .mode-icon { width: 58px; height: 58px; border-radius: 18px; background: rgba(255, 255, 255, .18); border: 1px solid rgba(255, 255, 255, .25); display: inline-flex; align-items: center; justify-content: center; font-size: 24px; }
        .live-chip { display: inline-flex; align-items: center; gap: 8px; background: rgba(255, 255, 255, .16); border: 1px solid rgba(255, 255, 255, .25); padding: 8px 14px; border-radius: 50px; font-size: 13px; font-weight: 700; }
        .live-chip i { color: #86efac; animation: lx-pulse 1.6s infinite; }

        /* Kartu pemindai */
        .scanner-card, .recent-card { background: #fff; border: 1px solid var(--lx-border); border-radius: 22px; box-shadow: var(--lx-shadow); }
        .scanner-card { padding: 36px; text-align: center; }
        .recent-card { padding: 22px; }
        .scan-icon { width: 76px; height: 76px; border-radius: 24px; background: #fff3d1; color: var(--lx-primary); display: inline-flex; align-items: center; justify-content: center; font-size: 32px; margin-bottom: 14px; }
        .barcode-box-wrapper { position: relative; max-width: 600px; margin: 0 auto; }
        .barcode-input { background: #f8fafc; border: 2px dashed #cbd5e1; color: var(--lx-text); font-size: 26px; font-weight: 800; text-align: center; border-radius: 18px; padding: 22px; letter-spacing: 4px; text-transform: uppercase; transition: all .2s ease; }
        .barcode-input::placeholder { color: #b6c0d1; letter-spacing: 6px; }
        .barcode-input:focus { background: #fff; border: 2px solid var(--lx-primary); box-shadow: 0 0 0 6px rgba(229, 167, 32, .12); outline: none; }
        .laser-line-subtle { position: absolute; bottom: 10px; left: 15%; width: 70%; height: 3px; border-radius: 3px; background: var(--lx-grad); box-shadow: 0 0 12px rgba(229, 167, 32, .6); animation: pulseLaser 2s infinite ease-in-out; }
        @keyframes pulseLaser { 0%, 100% { opacity: .25; transform: scaleX(.8); } 50% { opacity: 1; transform: scaleX(1); } }
        .scan-tips { display: inline-flex; gap: 18px; color: var(--lx-muted); font-size: 13px; font-weight: 600; }
        .scan-tips i { color: var(--lx-success); }

        .status-toast { border-radius: 16px; font-size: 18px; padding: 16px 22px; border: none; }
        .alert-success.status-toast { background: #ecfdf5; color: #047857 !important; }
        .alert-warning.status-toast { background: #fffbeb; color: #92400e !important; }
        .alert-danger.status-toast { background: #fef2f2; color: #b91c1c !important; }

        /* Panel kitir */
        #kitirPanel { background: linear-gradient(180deg, #fffbf0, #fff); border: 1px solid #f6e2a8 !important; border-radius: 20px !important; padding: 20px !important; }
        #kitirPanel .table td { border-color: #eef1f7; padding: 8px 4px; }

        /* Tabel */
        .table-custom { margin: 0; }
        .table-custom th { background: #f8fafc; color: var(--lx-muted); font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; border-bottom: 1px solid var(--lx-border); padding: 12px 14px; }
        .table-custom td { padding: 13px 14px; vertical-align: middle; font-size: 14px; border-bottom: 1px solid #f1f5f9; }
        #pendingGateInBody tr[data-queue]:hover td { background: #fff8e6; }
        .section-title { font-weight: 800; font-size: 16px; }
        .section-title i { width: 32px; height: 32px; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center; font-size: 14px; margin-right: 8px; }
    </style>
</head>
<body>

    <!-- Top Navbar -->
    <header class="lx-topbar d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-3">
            <?php $this->load->view('partials/logo', array('size' => 44)); ?>
            <div>
                <div class="lx-brand">LASMONO <span class="fw-semibold" style="color: #94a3b8;">Security Terminal</span></div>
                <div class="lx-sub">Otomasi gerbang &amp; transparansi FIFO depo</div>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="lx-user d-none d-md-inline-flex">
                <i class="fas fa-user-shield" style="color: #f6c64a;"></i>
                <span><span style="color: #94a3b8;">Petugas</span> <b><?= html_escape($this->session->userdata('name')); ?></b></span>
                <a href="<?= base_url('auth/logout'); ?>" class="lx-icon-btn" title="Keluar"><i class="fas fa-arrow-right-from-bracket"></i></a>
            </span>
            <a href="<?= base_url('auth/logout'); ?>" class="lx-icon-btn d-md-none" title="Keluar"><i class="fas fa-arrow-right-from-bracket"></i></a>
        </div>
    </header>

    <main class="container-fluid px-4 py-4 flex-grow-1">
        <!-- Mode Banner Header -->
        <div class="row mb-4">
            <div class="col-12">
                <?php 
                    $modeClass = 'gate-in';$modeIcon = 'fa-door-open';
                    $modeTitle = 'Gate In Terminal';$modeDesc = 'Gerbang masuk: scan barcode kitir, pilih truk, konfirmasi';

                    if ($role == 'GATE_OUT') {
                        $modeClass = 'gate-out';$modeIcon = 'fa-door-closed';
                        $modeTitle = 'Gate Out Terminal';$modeDesc = 'Gerbang keluar: scan barcode di HP supir setelah survey selesai';
                    } elseif ($role == 'CONTROL_TOWER') {
                        $modeClass = 'control-tower';$modeIcon = 'fa-desktop';
                        $modeTitle = 'Supervisor Master Terminal';$modeDesc = 'Akses Penuh Pemindaian Gate In & Gate Out';
                    }
                ?>
                <div class="mode-banner <?= $modeClass; ?>">
                    <div class="d-flex align-items-center gap-3">
                        <span class="mode-icon"><i class="fas <?= $modeIcon; ?>"></i></span>
                        <div>
                            <h4 class="fw-bold mb-0"><?= $modeTitle; ?></h4>
                            <small style="opacity: .9;"><?= $modeDesc; ?></small>
                        </div>
                    </div>
                    <div class="d-none d-lg-flex align-items-center gap-3">
                        <span class="live-chip"><i class="fas fa-circle" style="font-size: 8px;"></i> Scanner siap</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Left Column: Scanner Main Box -->
            <div class="col-lg-7">
                <div class="scanner-card h-100 d-flex flex-column justify-content-center">
                    
                    <!-- Alert Message Toast -->
                    <div id="scanResult" style="display:none;" class="alert status-toast fw-bold mb-4"></div>

                    <div class="mb-4">
                        <span class="scan-icon"><i class="fas fa-barcode"></i></span>
                        <h4 class="fw-bold text-dark mb-1"><?= $role == 'GATE_OUT' ? 'Scan Barcode Kitir / QR di HP Supir' : 'Arahkan Scanner ke Barcode Kitir'; ?></h4>
                        <p class="text-muted small mb-0"><?= $role == 'GATE_OUT' ? 'Export & Repo Out: scan barcode kitir. Repo In & Import: scan kitir atau QR supir' : 'Kitir Z = Repo In, A = Import, D = Export, E = Repo Out. Scan, pilih truk, lalu konfirmasi'; ?></p>
                    </div>

                    <!-- Input Box -->
                    <div class="barcode-box-wrapper mb-4">
                        <input type="text" id="gateBarcodeInput" class="form-control barcode-input" placeholder="SIAP SCAN" autofocus autocomplete="off">
                        <div class="laser-line-subtle"></div>
                    </div>

                    <!-- Panel Konfirmasi Gate In (muncul setelah scan barcode kitir) -->
                    <div id="kitirPanel" class="text-start border rounded-4 p-3 mb-4" style="display:none;">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="fw-bold mb-0"><i class="fas fa-file-alt text-primary me-2"></i>Data Kitir <span id="kitirBarcode" class="text-muted small"></span></h6>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="kitirCancel">Batal</button>
                        </div>
                        <table class="table table-sm small mb-3">
                            <tr><td class="text-muted" style="width: 35%;">Aktivitas</td><td id="kitirActivity"></td></tr>
                            <tr><td class="text-muted">No. Container</td><td class="fw-bold" id="kitirContainer"></td></tr>
                            <tr><td class="text-muted">Principal</td><td class="fw-semibold" id="kitirPrincipal"></td></tr>
                            <tr><td class="text-muted">Size</td><td class="fw-semibold" id="kitirSize"></td></tr>
                            <tr><td class="text-muted">Customer</td><td class="fw-semibold" id="kitirCustomer"></td></tr>
                            <tr><td class="text-muted">Nopol di Kitir</td><td class="fw-semibold" id="kitirNopol"></td></tr>
                        </table>
                        <label class="form-label small fw-bold text-muted mb-1">TRUK YANG GATE IN (urut FIFO)</label>
                        <select id="kitirQueueSelect" class="form-select fw-bold mb-3"></select>
                        <button type="button" class="btn btn-success w-100 fw-bold py-2" id="kitirConfirm">
                            <i class="fas fa-check me-1"></i> KONFIRMASI GATE IN
                        </button>
                        <small class="text-muted d-block text-center mt-1">atau tekan Enter</small>
                    </div>

                    <div class="scan-tips justify-content-center">
                        <span><i class="fas fa-circle-check me-1"></i> Fokus otomatis</span>
                        <span><i class="fas fa-circle-check me-1"></i> Scanner USB / wireless</span>
                    </div>
                </div>
            </div>

            <!-- Right Column: Recent Activity Log -->
            <div class="col-lg-5">
                <div class="recent-card h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="section-title mb-0">
                            <i class="fas fa-clock-rotate-left" style="background: #fff3d1; color: var(--lx-primary);"></i>Transaksi Terakhir
                        </h6>
                        <span class="lx-pill lx-pill-blue" id="totalProcessedCount">0 Truk Hari Ini</span>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-custom align-middle">
                            <thead>
                                <tr>
                                    <th>Waktu</th>
                                    <th>No. Antrean</th>
                                    <th>Status Baru</th>
                                </tr>
                            </thead>
                            <tbody id="recentScanBody">
                                <tr id="emptyScanRow">
                                    <td colspan="3" class="text-center text-muted py-4">
                                        <i class="fas fa-inbox fa-2x mb-2 d-block opacity-50"></i>
                                        Belum ada aktivitas pemindaian pada sesi ini.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Antrean Pending (belum di-scan) -->
        <div class="row g-4 mt-1">
            <?php if (in_array($role, array('GATE_IN', 'CONTROL_TOWER'))): ?>
                <div class="col-lg-6">
                    <div class="recent-card">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="section-title mb-0">
                                <i class="fas fa-hourglass-half" style="background: #fef3c7; color: #d97706;"></i>Antre Gate In <small class="text-secondary fw-semibold">(klik untuk memilih)</small>
                            </h6>
                            <span class="lx-pill lx-pill-amber" id="pendingGateInCount">0 Truk</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-custom align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>No. Antrean</th>
                                        <th>Nopol</th>
                                        <th>Menunggu</th>
                                    </tr>
                                </thead>
                                <tbody id="pendingGateInBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            <?php if (in_array($role, array('GATE_OUT', 'CONTROL_TOWER'))): ?>
                <div class="col-lg-6">
                    <div class="recent-card">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="section-title mb-0">
                                <i class="fas fa-flag-checkered" style="background: #dcfce7; color: #059669;"></i>Siap Gate Out
                            </h6>
                            <span class="lx-pill lx-pill-green" id="pendingGateOutCount">0 Truk</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-custom align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>No. Antrean</th>
                                        <th>Nopol</th>
                                        <th>Blok</th>
                                        <th>Menunggu</th>
                                    </tr>
                                </thead>
                                <tbody id="pendingGateOutBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <footer class="bg-white border-top py-3 text-center text-muted small">
        &copy; <?= date('Y'); ?> Depo Petikemas MTKI - LASMONO Gate Integration Terminal. All rights reserved.
    </footer>

    <script>
        const CSRF_NAME = "<?= $this->security->get_csrf_token_name(); ?>";
        let CSRF_HASH = "<?= $this->security->get_csrf_hash(); ?>";

        // Token di halaman bisa basi: ambil token baru lalu ulangi request sekali
        function refreshCsrf(done) {
            $.ajax({ url: "<?= base_url('auth/csrf_token'); ?>", data: { _: Date.now() }, dataType: "json" })
                .done(function(res) { CSRF_HASH = res.hash; done(true); })
                .fail(function() { done(false); });
        }

        function esc(value) {
            return $('<div>').text(value === null || value === undefined ? '' : String(value)).html();
        }

        let processedCount = 0;

        function renderPending(key, rows, showZone) {
            let body = $('#pending' + key + 'Body');
            if (!body.length) return;
            $('#pending' + key + 'Count').text(rows.length + ' Truk');
            if (!rows.length) {
                body.html('<tr><td colspan="' + (showZone ? 4 : 3) + '" class="text-center text-muted py-3">Tidak ada antrean pending.</td></tr>');
                return;
            }
            let html = '';
            rows.forEach(function(r) {
                html += '<tr data-queue="' + esc(r.queue_number) + '" style="cursor: pointer;">' +
                    '<td class="fw-bold text-primary">' + esc(r.queue_number) + (showZone ? ' <span class="act-tag act-' + esc(r.activity) + '">' + esc(r.activity_label) + '</span>' : '') + '</td>' +
                    '<td>' + esc(r.license_plate) + '</td>' +
                    (showZone ? '<td>' + esc(r.zone_code || '-') + '</td>' : '') +
                    '<td><span class="badge ' + (r.overdue ? 'bg-danger' : 'bg-light text-dark border') + '">' + esc(r.waiting_minutes) + ' mnt</span></td>' +
                '</tr>';
            });
            body.html(html);
        }

        function loadPending() {
            $.ajax({
                url: "<?= base_url('gate/pending_json'); ?>",
                data: { _: Date.now() },
                dataType: "json",
                success: function(res) {
                    if (res.gate_in) renderPending('GateIn', res.gate_in, false);
                    if (res.gate_out) renderPending('GateOut', res.gate_out, true);
                }
            });
        }
        loadPending();
        setInterval(loadPending, 5000);

        // Kunci kursor otomatis agar tidak lepas dari input box
        $(document).ready(function() {$('#gateBarcodeInput').focus();
            
            $(document).click(function(e) {
                // Jangan rebut fokus saat petugas memilih truk di panel kitir
                if (!$(e.target).closest('#kitirPanel').length) $('#gateBarcodeInput').focus();
            });
        });

        const IS_GATE_IN = <?= in_array($role, array('GATE_IN', 'CONTROL_TOWER')) ? 'true' : 'false'; ?>;
        let currentKitir = null;

        // Deteksi Otomatis saat Barcode Scanner menekan tombol Enter
        $('#gateBarcodeInput').keypress(function(e) {
            if (e.which === 13) {
                let code = $(this).val().trim().toUpperCase();
                // Enter tanpa isi saat panel kitir terbuka = konfirmasi Gate In
                if (code === '' && currentKitir) {
                    $('#kitirConfirm').click();
                    return;
                }
                if (code !== '') {
                    // Barcode kitir (Z/A/D/E + angka) di Gate In = proses Gate In, selain itu Gate Out (kitir / QR supir)
                    if (IS_GATE_IN && /^[ZADE]\d{6,12}$/.test(code)) {
                        lookupKitir(code);
                    } else {
                        sendGateScan(code);
                    }
                    $(this).val('');
                }
            }
        });

        function showResult(res) {
            let alertClass = res.status === 'success' ? 'alert-success text-success' : (res.status === 'warning' ? 'alert-warning text-dark' : 'alert-danger text-danger');
            let icon = res.status === 'success' ? 'check-circle' : 'exclamation-circle';

            $('#scanResult')
                .stop(true, true)
                .removeClass('alert-success alert-warning alert-danger text-success text-dark text-danger')
                .addClass(alertClass)
                .html('<i class="fas fa-' + icon + ' me-2"></i><b>' + esc(res.message).replace(/\n/g, "<br>") + '</b>')
                .fadeIn();

            clearTimeout(window.scanResultTimer);
            window.scanResultTimer = setTimeout(function() { $('#scanResult').fadeOut(); }, 5000);
        }

        // POST dengan token CSRF; jika token basi (403) ambil token baru lalu ulangi sekali
        function postJson(url, data, onSuccess, retried) {
            data[CSRF_NAME] = CSRF_HASH;
            $.ajax({
                url: url,
                type: "POST",
                data: data,
                dataType: "json",
                success: onSuccess,
                error: function(xhr) {
                    if (xhr.status === 403 && !retried) {
                        refreshCsrf(function(ok) { if (ok) postJson(url, data, onSuccess, true); });
                        return;
                    }
                    let msg = xhr.status === 401 || xhr.status === 403
                        ? 'Sesi berakhir. Halaman dimuat ulang...'
                        : 'Gagal terhubung ke server. Scan ulang barcode.';
                    showResult({ status: 'error', message: msg });
                    if (xhr.status === 401 || xhr.status === 403) {
                        setTimeout(function() { window.location.reload(); }, 1500);
                    }
                }
            });
        }

        function sendGateScan(code) {
            postJson("<?= base_url('gate/process_scan'); ?>", { queue_number: code }, function(res) {
                showResult(res);
                if (res.status === 'success') {
                    let match = code.match(/A-\d{8}-\d{3}/);
                    addRecentRow(match ? match[0] : code, res.message);
                    loadPending();
                }
            });
        }

        function closeKitir() {
            currentKitir = null;
            $('#kitirPanel').hide();
            $('#gateBarcodeInput').focus();
        }

        function lookupKitir(code) {
            postJson("<?= base_url('gate/lookup_kitir'); ?>", { barcode: code }, function(res) {
                if (res.status !== 'success') {
                    closeKitir();
                    showResult(res);
                    return;
                }
                currentKitir = res.kitir;
                $('#kitirBarcode').text('(' + res.kitir.barcode + ')');
                $('#kitirActivity').html('<span class="act-tag act-' + esc(res.kitir.activity) + '">' + esc(res.kitir.activity_label) + '</span>');
                if (res.kitir.container_no) {
                    $('#kitirContainer').text(res.kitir.container_no);
                } else {
                    $('#kitirContainer').html('<span class="text-muted fw-normal">Dipilih Yardman saat muat</span>');
                }
                $('#kitirPrincipal').text(res.kitir.principal || '-');
                $('#kitirSize').text(res.kitir.size || '-');
                $('#kitirCustomer').text(res.kitir.customer || '-');
                $('#kitirNopol').text(res.kitir.nopol || '-');

                let options = '';
                res.candidates.forEach(function(c, i) {
                    options += '<option value="' + esc(c.queue_number) + '"' + (c.queue_number === res.suggested ? ' selected' : '') + '>' +
                        (i + 1) + '. ' + esc(c.license_plate) + ' - #' + esc(c.queue_number) + ' (' + esc(c.waiting_minutes) + ' mnt)</option>';
                });
                $('#kitirQueueSelect').html(options);
                $('#kitirPanel').show();
                $('#gateBarcodeInput').focus();
            });
        }

        $('#kitirCancel').on('click', closeKitir);

        $('#kitirConfirm').on('click', function() {
            if (!currentKitir) return;
            let btn = $(this).prop('disabled', true);
            let queueNumber = $('#kitirQueueSelect').val();
            postJson("<?= base_url('gate/process_gate_in'); ?>", { barcode: currentKitir.barcode, queue_number: queueNumber }, function(res) {
                btn.prop('disabled', false);
                showResult(res);
                if (res.status === 'success') {
                    addRecentRow(queueNumber, res.message);
                    closeKitir();
                    loadPending();
                }
            });
        });

        // Klik truk di daftar pending untuk memilihnya di panel kitir
        $(document).on('click', '#pendingGateInBody tr[data-queue]', function() {
            if (currentKitir) $('#kitirQueueSelect').val($(this).data('queue'));
        });

        function addRecentRow(code, msg) {
            $('#emptyScanRow').hide();
            processedCount++;
            $('#totalProcessedCount').text(processedCount + ' Truk Hari Ini');

            let now = new Date();
            let timeStr = (now.getHours()<10?'0':'') + now.getHours() + ':' + (now.getMinutes()<10?'0':'') + now.getMinutes() + ':' + (now.getSeconds()<10?'0':'') + now.getSeconds();

            let statusBadge = '<span class="lx-pill lx-pill-green"><i class="fas fa-check"></i> Berhasil</span>';

            let newRow = `
                <tr>
                    <td class="text-muted"><small>${timeStr}</small></td>
                    <td class="fw-bold text-primary">#${esc(code)}</td>
                    <td>${statusBadge}</td>
                </tr>
            `;

            $('#recentScanBody').prepend(newRow);

            if ($('#recentScanBody tr').length > 6) {
                $('#recentScanBody tr:last').remove();
            }
        }
    </script>
</body>
</html>