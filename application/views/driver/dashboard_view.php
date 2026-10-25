<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LASMONO - Interface Supir</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f0f4f8; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .mobile-container { max-width: 450px; margin: 0 auto; background: #fff; min-height: 100vh; position: relative; padding-bottom: 90px; }
        .header-bg { background-color: #1a365d; color: white; padding: 20px; border-bottom-left-radius: 20px; border-bottom-right-radius: 20px; }
        .card-custom { border-radius: 15px; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.06); }
        .btn-sos { position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%); z-index: 999; border-radius: 50px; padding: 12px 30px; font-weight: bold; box-shadow: 0 4px 15px rgba(220, 53, 69, 0.4); }
        .timer-circle { width: 170px; height: 170px; border-radius: 50%; border: 8px solid #198754; display: flex; flex-direction: column; align-items: center; justify-content: center; margin: 10px auto; transition: all 0.3s ease; }
        .timer-circle.late { border-color: #dc3545; }
        .timer-total { display: inline-flex; align-items: center; gap: 6px; background: #f1f5f9; border-radius: 50px; padding: 6px 14px; font-size: 13px; }
        .timer-total.late { background: #fde2e4; color: #b42318; }
        .stepper { display: flex; justify-content: space-between; margin: 4px 0 8px; }
        .step { flex: 1; text-align: center; font-size: 11px; color: #94a3b8; position: relative; }
        .step .dot { width: 26px; height: 26px; border-radius: 50%; background: #e2e8f0; color: #64748b; display: flex; align-items: center; justify-content: center; margin: 0 auto 4px; font-weight: 700; font-size: 12px; }
        .step.done .dot { background: #198754; color: #fff; }
        .step.active .dot { background: #0d6efd; color: #fff; box-shadow: 0 0 0 4px rgba(13,110,253,0.2); }
        .step.active, .step.done { color: #1e293b; font-weight: 600; }
        .location-box { background: #0b1e38; color: #fff; border-radius: 15px; padding: 14px; }
    </style>
</head>
<body>
    <div class="mobile-container">
        <!-- Header Informasi Armada -->
        <div class="header-bg d-flex justify-content-between align-items-center">
            <div>
                <h5 class="fw-bold mb-0">LASMONO</h5>
                <small class="text-light opacity-75">Armada: <b><?= html_escape($queue->license_plate); ?></b></small>
            </div>
            <a href="<?= base_url('auth/logout'); ?>" class="btn btn-outline-light btn-sm"><i class="fas fa-sign-out-alt"></i></a>
        </div>

        <div class="p-3">
            <!-- Notifikasi Informasi Antrean Aktif -->
            <?php if($this->session->flashdata('info_driver')): ?>
                <div class="alert alert-info alert-dismissible fade show small py-2 border-0 shadow-sm mb-3" role="alert">
                    <i class="fas fa-info-circle me-1"></i> <?= html_escape($this->session->flashdata('info_driver')); ?>
                    <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Notifikasi Error SOS -->
            <?php if($this->session->flashdata('error_sos')): ?>
                <div class="alert alert-danger py-2 small border-0 shadow-sm mb-3"><?= html_escape($this->session->flashdata('error_sos')); ?></div>
            <?php endif; ?>

            <!-- Notifikasi Sukses SOS -->
            <?php if($this->session->flashdata('success_sos')): ?>
                <div class="alert alert-success py-2 small border-0 shadow-sm mb-3"><?= html_escape($this->session->flashdata('success_sos')); ?></div>
            <?php endif; ?>

            <?php
                $status_labels = array(
                    'WAITING_GATE' => 'Menunggu Gate In',
                    'IN_YARD'      => 'Menuju Area Survey',
                    'IN_SURVEY'    => 'Sedang Survey',
                    'AT_YARD'      => 'Menuju Gate Out',
                    'COMPLETED'    => 'Selesai'
                );
                $step_index = array('IN_YARD' => 0, 'IN_SURVEY' => 1, 'AT_YARD' => 2);
                $current_step = isset($step_index[$queue->status]) ? $step_index[$queue->status] : -1;
            ?>

            <!-- Card Nomor Antrean & FIFO -->
            <div class="card card-custom p-3 text-center mb-3">
                <span class="text-muted small">Nomor Antrean Anda:</span>
                <h1 class="fw-bold text-primary display-5 mb-0">#<?= html_escape($queue->queue_number); ?></h1>
                <div class="mt-2">
                    <span id="positionBadge" class="badge bg-primary" <?= $queue_position === NULL ? 'style="display:none;"' : ''; ?>>Urutan ke-<span id="queuePosition"><?= $queue_position; ?></span> (FIFO)</span>
                    <span id="statusBadge" class="badge <?= $queue->status === 'WAITING_GATE' ? 'bg-warning text-dark' : 'bg-primary text-white'; ?>">
                        <?= html_escape(isset($status_labels[$queue->status]) ? $status_labels[$queue->status] : $queue->status); ?>
                    </span>
                </div>
                <small class="text-muted mt-2" id="estimatedWaitText"><?php if ($estimated_wait !== NULL): ?>Estimasi dipanggil Gate In &plusmn;<?= $estimated_wait; ?> menit<?php endif; ?></small>
            </div>

            <?php if (!empty($queue->gate_in_at)): ?>
            <!-- Lokasi Penempatan (ditentukan Yardman setelah survey) -->
            <div class="location-box text-center mb-3">
                <?php if (!empty($queue->zone_code)): ?>
                    <small class="opacity-75 d-block">Lokasi Penempatan Container</small>
                    <div class="h3 fw-bold mb-0"><?= html_escape($queue->depo); ?> &middot; Blok <?= html_escape($queue->zone_code); ?></div>
                <?php else: ?>
                    <small class="opacity-75 d-block">Lokasi Penempatan Container</small>
                    <div class="fw-semibold">Depo &amp; blok ditentukan Yardman setelah survey selesai</div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Timer KPI: besar = tahap berjalan, kecil = total proses -->
            <div class="card card-custom p-3 text-center mb-3" id="timerCard">
                <?php if ($timer !== NULL): ?>
                    <div class="stepper">
                        <?php foreach (array('Gate In', 'Survey', 'Gate Out') as $i => $step_name): ?>
                            <div class="step <?= $i < $current_step ? 'done' : ($i === $current_step ? 'active' : ''); ?>">
                                <div class="dot"><?= $i < $current_step ? '&#10003;' : $i + 1; ?></div><?= $step_name; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <span class="text-muted small">Timer Tahap: <b id="stageLabel"><?= html_escape($timer['stage_label']); ?></b></span>
                    <div class="timer-circle" id="stageCircle">
                        <span class="display-6 fw-bold mb-0" id="stageTimer">--:--</span>
                        <small class="text-muted" style="font-size: 11px;">Target: <?= (int) $timer['stage_target_minutes']; ?> Mnt</small>
                    </div>
                    <div class="timer-total" id="totalBox">
                        <i class="fas fa-stopwatch"></i> Total proses: <b id="totalTimer">--:--</b> / <?= (int) $timer['total_target_minutes']; ?>:00
                    </div>
                    <small class="text-muted d-block mt-2" id="timerStatusText">Timer KPI berjalan sejak Gate In</small>
                <?php else: ?>
                    <i class="fas fa-hourglass-half fa-2x text-warning mb-2"></i>
                    <div class="fw-semibold">Menunggu Gate In</div>
                    <small class="text-muted">Timer KPI (Gate In &middot; Survey &middot; Gate Out, masing-masing 5 menit) mulai berjalan setelah security scan kitir Anda.</small>
                <?php endif; ?>
            </div>

            <!-- Barcode + Data Kitir (muncul setelah Gate In) -->
            <div class="card card-custom p-3 text-center">
                <?php if ($qr_payload !== NULL): ?>
                    <span class="text-muted small mb-2">Tunjukkan Barcode ke Yardman / Petugas Gate Out</span>
                    <div class="d-flex justify-content-center mb-3">
                        <div id="queueQr" data-code="<?= html_escape($qr_payload); ?>"></div>
                    </div>
                    <table class="table table-sm text-start small mb-0">
                        <tr><td class="text-muted">No. Container</td><td class="fw-bold"><?= html_escape($queue->container_no); ?></td></tr>
                        <tr><td class="text-muted">Principal</td><td class="fw-semibold"><?= html_escape($queue->principal); ?></td></tr>
                        <tr><td class="text-muted">Size</td><td class="fw-semibold"><?= html_escape($queue->container_size); ?></td></tr>
                        <tr><td class="text-muted">Customer</td><td class="fw-semibold"><?= html_escape($queue->customer); ?></td></tr>
                    </table>
                <?php else: ?>
                    <i class="fas fa-qrcode fa-2x text-muted mb-2"></i>
                    <div class="fw-semibold">Barcode muncul setelah Gate In</div>
                    <small class="text-muted">Siapkan kitir Anda untuk di-scan oleh security di Gate In.</small>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tombol Darurat SOS Floating -->
        <button class="btn btn-danger btn-sos" data-bs-toggle="modal" data-bs-target="#sosModal">
            <i class="fas fa-exclamation-triangle me-2"></i> LASMONO SOS
        </button>
    </div>

    <!-- Pop Up Tahap Selesai -->
    <div class="modal fade" id="stageModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 text-center">
                <div class="modal-body p-4">
                    <div class="mb-3"><i class="fas fa-check-circle text-success" style="font-size: 64px;"></i></div>
                    <h4 class="fw-bold mb-2" id="stageModalTitle"></h4>
                    <div id="stageModalDuration" class="mb-2"></div>
                    <p class="text-muted mb-3" id="stageModalText"></p>
                    <div id="stageModalNext" class="alert alert-primary py-2 small fw-semibold mb-3"></div>
                    <button type="button" class="btn btn-primary w-100 fw-bold" data-bs-dismiss="modal">OK, MENGERTI</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Form Lapor SOS -->
    <div class="modal fade" id="sosModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title fw-bold"><i class="fas fa-bullhorn me-2"></i> Lapor Pungli / Penyerobotan</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="<?= base_url('driver/submit_sos'); ?>" method="POST" enctype="multipart/form-data" data-csrf-refresh>
                    <div class="modal-body text-dark">
                        <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Jenis Pelanggaran</label>
                            <select name="report_type" class="form-select" required>
                                <option value="PUNGLI">Indikasi Pungli</option>
                                <option value="PENYEROBOTAN">Penyerobotan Antrean</option>
                                <option value="LAINNYA">Lainnya</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Deskripsi Kejadian</label>
                            <textarea name="description" class="form-control" rows="3" maxlength="1000" placeholder="Jelaskan kronologi & lokasi kejadian..." required></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Unggah Bukti Foto / Video <small class="text-muted">(Maks. 10MB)</small></label>
                            <input type="file" name="sos_media" class="form-control" accept="image/jpeg,image/png,video/mp4,video/quicktime">
                            <small class="text-muted mt-1 d-block" style="font-size: 11px;">
                                <i class="fas fa-info-circle me-1"></i> Format: JPG, PNG, MP4, MOV (Maks. 10MB)
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-danger w-100 fw-bold">KIRIM LAPORAN KE CONTROL TOWER</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script>
        // Ambil token CSRF terbaru tepat sebelum form dikirim
        (function() {
            var tokenUrl = "<?= base_url('auth/csrf_token'); ?>";
            var forms = document.querySelectorAll('form[data-csrf-refresh]');
            for (var i = 0; i < forms.length; i++) {
                forms[i].addEventListener('submit', function(e) {
                    var form = this;
                    e.preventDefault();
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
    <script>
        // QR dibuat di browser, data tidak dikirim ke layanan pihak ketiga
        if (document.getElementById("queueQr") && typeof QRCode !== 'undefined') {
            new QRCode(document.getElementById("queueQr"), { text: $('#queueQr').data('code'), width: 180, height: 180 });
        }

        let currentStatus = "<?= $queue->status; ?>";
        let stageEnd = null;
        let totalEnd = null;

        function fmt(seconds) {
            let neg = seconds < 0;
            seconds = Math.abs(Math.floor(seconds));
            let m = Math.floor(seconds / 60), s = seconds % 60;
            return (neg ? '+' : '') + (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
        }

        // Sisa waktu dihitung dari detik yang diberikan server (tidak bergantung jam HP)
        function setTimers(timer) {
            if (!timer) return;
            stageEnd = Date.now() + timer.stage_remaining_seconds * 1000;
            totalEnd = Date.now() + timer.total_remaining_seconds * 1000;
            $('#stageLabel').text(timer.stage_label);
            tick();
        }

        function tick() {
            if (stageEnd === null) return;
            let stageLeft = (stageEnd - Date.now()) / 1000;
            let totalLeft = (totalEnd - Date.now()) / 1000;
            $('#stageTimer').text(fmt(stageLeft));
            $('#totalTimer').text(fmt(totalLeft));
            $('#stageCircle').toggleClass('late', stageLeft < 0);
            $('#stageTimer').toggleClass('text-danger', stageLeft < 0);
            $('#totalBox').toggleClass('late', totalLeft < 0);
            $('#timerStatusText').text(stageLeft < 0 ? 'Tahap ini melewati target KPI ' + <?= (int) MY_Controller::STAGE_KPI_MINUTES; ?> + ' menit' : 'Timer KPI berjalan sejak Gate In');
        }
        setInterval(tick, 1000);
        setTimers(<?= json_encode($timer, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>);

        function updateWaitingInfo(position, estimatedWait) {
            if (position === null) {
                $('#positionBadge').hide();
                $('#estimatedWaitText').text('');
                return;
            }
            $('#queuePosition').text(position);
            $('#positionBadge').show();
            $('#estimatedWaitText').text(estimatedWait === null ? '' : 'Estimasi dipanggil Gate In \u00b1' + estimatedWait + ' menit');
        }

        function showCompleted(durations) {
            stageEnd = null;
            let html = '<i class="fas fa-check-circle fa-2x text-success mb-2"></i><div class="fw-bold mb-2">Servis Selesai (Gate Out)</div>';
            html += '<table class="table table-sm small text-start mb-0">';
            durations.forEach(function(d) {
                html += '<tr><td>' + $('<div>').text(d.label).html() + '</td><td class="text-end fw-bold ' + (d.achieved ? 'text-success' : 'text-danger') + '">' +
                    d.minutes + ' mnt ' + (d.achieved ? '&#10003;' : '&#10007;') + '</td></tr>';
            });
            html += '</table>';
            $('#timerCard').html(html);
            $('#statusBadge').removeClass('bg-warning text-dark bg-primary').addClass('bg-success text-white').text('Selesai');
        }

        // POP UP setiap tahap selesai
        const STAGE_POPUPS = {
            IN_YARD:   { title: 'Gate In Berhasil!', text: 'Container <?= html_escape($queue->container_no); ?> sudah tercatat masuk depo.', next: 'Selanjutnya: menuju AREA SURVEY dan tunjukkan barcode ke Yardman.' },
            IN_SURVEY: { title: 'Masuk Area Survey', text: 'Container sedang disurvey oleh Yardman.', next: 'Selanjutnya: tunggu survey selesai, Yardman akan scan barcode Anda lagi.' },
            AT_YARD:   { title: 'Survey Selesai!', text: <?= json_encode(!empty($queue->zone_code) ? 'Lokasi penempatan: ' . $queue->depo . ' - Blok ' . $queue->zone_code . '.' : 'Survey container sudah selesai.', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>, next: 'Selanjutnya: menuju GATE OUT dan tunjukkan barcode ke petugas.' },
            COMPLETED: { title: 'Gate Out Berhasil!', text: 'Seluruh proses di depo sudah selesai. Terima kasih.', next: 'Hati-hati di jalan.' }
        };

        function notifyDevice() {
            try { if (navigator.vibrate) navigator.vibrate([200, 100, 200]); } catch (e) {}
            try {
                let ctx = new (window.AudioContext || window.webkitAudioContext)();
                let osc = ctx.createOscillator();
                let gain = ctx.createGain();
                osc.frequency.value = 880;
                gain.gain.value = 0.15;
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.start();
                osc.stop(ctx.currentTime + 0.25);
            } catch (e) {}
        }

        // durasi = tahap yang baru selesai (label, menit, capai target?)
        function showStagePopup(status, duration) {
            let popup = STAGE_POPUPS[status];
            if (!popup) return;
            $('#stageModalTitle').text(popup.title);
            $('#stageModalText').text(popup.text);
            $('#stageModalNext').text(popup.next);
            $('#stageModalDuration').html(duration
                ? '<span class="badge fs-6 ' + (duration.achieved ? 'bg-success' : 'bg-danger') + '">Tahap ' + $('<div>').text(duration.label).html() + ': ' + duration.minutes + ' mnt ' + (duration.achieved ? '&#10003;' : '&#10007; lewat target') + '</span>'
                : '');
            bootstrap.Modal.getOrCreateInstance(document.getElementById('stageModal')).show();
            notifyDevice();
        }

        // Setelah halaman dimuat ulang karena tahap berganti (?done=STATUS), tampilkan pop up
        (function() {
            let match = window.location.search.match(/[?&]done=([A-Z_]+)/);
            if (!match || match[1] !== currentStatus) return;
            let durations = <?= json_encode($stage_durations, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
            showStagePopup(currentStatus, currentStatus === 'IN_YARD' ? null : durations[durations.length - 1]);
            if (window.history.replaceState) window.history.replaceState(null, '', window.location.pathname);
        })();

        // AUTO-POLLING: cek status setiap 5 detik, halaman dimuat ulang saat tahap berganti
        setInterval(function() {
            $.ajax({
                url: "<?= base_url('driver/get_status_json'); ?>",
                type: "GET",
                dataType: "json",
                success: function(res) {
                    if (res.status !== 'success') return;

                    if (res.queue_status === 'EXPIRED') {
                        window.location.reload();
                        return;
                    }
                    if (res.queue_status === 'COMPLETED') {
                        if (currentStatus !== 'COMPLETED') {
                            showCompleted(res.stage_durations);
                            showStagePopup('COMPLETED', res.stage_durations[res.stage_durations.length - 1]);
                        }
                        currentStatus = 'COMPLETED';
                        return;
                    }
                    if (currentStatus !== res.queue_status) {
                        window.location.replace(window.location.pathname + '?done=' + res.queue_status);
                        return;
                    }

                    updateWaitingInfo(res.queue_position, res.estimated_wait);
                    setTimers(res.timer);
                },
                error: function(xhr) {
                    if (xhr.status === 401) window.location.reload();
                }
            });
        }, 5000);
    </script>
</body>
</html>