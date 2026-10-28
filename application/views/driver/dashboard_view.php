<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#1c1d21">
    <title>LASMONO - Interface Supir</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php $this->load->view('partials/theme'); ?>
    <style>
        body { background: var(--lx-bg); }
        .app { max-width: 460px; margin: 0 auto; min-height: 100vh; position: relative; padding-bottom: 110px; }

        /* Header gradasi */
        .head { position: relative; overflow: hidden; background: var(--lx-ink); color: #fff; padding: 20px 20px 92px; border-bottom-left-radius: 30px; border-bottom-right-radius: 30px; }
        .head::before { content: ""; position: absolute; width: 300px; height: 300px; border-radius: 50%; background: #e5a720; filter: blur(80px); opacity: .55; top: -140px; right: -90px; }
        .head::after { content: ""; position: absolute; width: 240px; height: 240px; border-radius: 50%; background: #d72a2f; filter: blur(80px); opacity: .3; bottom: -150px; left: -60px; }
        .head > * { position: relative; z-index: 1; }
        .plate { display: inline-flex; align-items: center; gap: 8px; background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.14); border-radius: 10px; padding: 6px 12px; font-weight: 800; letter-spacing: .06em; }

        .content { padding: 0 16px; margin-top: -72px; position: relative; z-index: 2; }
        .card-app { background: #fff; border-radius: 22px; border: 1px solid var(--lx-border); box-shadow: var(--lx-shadow); padding: 20px; margin-bottom: 14px; }

        /* Nomor antrean */
        .queue-no { font-size: clamp(24px, 8.4vw, 34px); white-space: nowrap; font-weight: 800; letter-spacing: -0.02em; background: var(--lx-grad-text); -webkit-background-clip: text; background-clip: text; color: transparent; line-height: 1.15; }
        .meta-row { display: flex; justify-content: center; flex-wrap: wrap; gap: 8px; }

        /* Lokasi */
        .location-box { background: linear-gradient(135deg, #0f9b6e, #10b981); color: #fff; border-radius: 22px; padding: 16px 18px; display: flex; align-items: center; gap: 14px; box-shadow: 0 12px 26px rgba(16, 185, 129, .28); margin-bottom: 14px; }
        .location-box.pending { background: #fff; color: var(--lx-text); border: 1px dashed #cbd5e1; box-shadow: none; }
        .location-box .ico { width: 46px; height: 46px; border-radius: 14px; background: rgba(255,255,255,.18); display: inline-flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; }
        .location-box.pending .ico { background: #f1f5f9; color: var(--lx-muted); }

        /* Stepper */
        .stepper { display: flex; align-items: flex-start; margin-bottom: 14px; }
        .step { flex: 1; text-align: center; font-size: 11px; font-weight: 600; color: #94a3b8; position: relative; }
        .step:not(:last-child)::after { content: ""; position: absolute; top: 15px; left: calc(50% + 18px); right: calc(-50% + 18px); height: 3px; border-radius: 3px; background: #e2e8f0; }
        .step.done:not(:last-child)::after { background: var(--lx-success); }
        .step .dot { width: 32px; height: 32px; border-radius: 50%; background: #f1ede4; color: #94a3b8; display: flex; align-items: center; justify-content: center; margin: 0 auto 6px; font-weight: 800; font-size: 13px; }
        .step.done .dot { background: var(--lx-success); color: #fff; }
        .step.active .dot { background: var(--lx-grad); color: var(--lx-ink); box-shadow: 0 0 0 5px rgba(229, 167, 32, .15); }
        .step.active, .step.done { color: var(--lx-text); }

        /* Cincin timer */
        .ring { position: relative; width: 210px; height: 210px; margin: 6px auto 12px; }
        .ring svg { width: 100%; height: 100%; transform: rotate(-90deg); }
        .ring .track { stroke: #f1ede4; }
        .ring .bar { stroke: url(#ringGrad); stroke-linecap: round; transition: stroke-dashoffset 1s linear; }
        .ring.late .bar { stroke: var(--lx-danger); }
        .ring .center { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; }
        .ring .time { font-size: 44px; font-weight: 800; letter-spacing: -0.03em; font-variant-numeric: tabular-nums; }
        .ring.late .time { color: var(--lx-danger); }
        .timer-total { display: inline-flex; align-items: center; gap: 8px; background: #fff6dc; color: #8a5a00; border-radius: 50px; padding: 8px 16px; font-size: 13px; font-weight: 600; }
        .timer-total b { font-variant-numeric: tabular-nums; }
        .timer-total.late { background: #fee2e2; color: #b91c1c; }

        /* QR & data kitir */
        .qr-wrap { display: inline-block; padding: 14px; border-radius: 20px; background: #fff; border: 1px solid var(--lx-border); box-shadow: 0 10px 24px rgba(15, 23, 42, .06); }
        .kv { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; text-align: left; }
        .kv div { background: #f8fafc; border-radius: 14px; padding: 10px 12px; }
        .kv small { display: block; color: var(--lx-muted); font-size: 11px; font-weight: 600; }
        .kv b { font-size: 14px; }
        .empty-state .ico { width: 64px; height: 64px; border-radius: 20px; display: inline-flex; align-items: center; justify-content: center; font-size: 26px; margin-bottom: 12px; }

        /* SOS */
        .btn-sos { position: fixed; bottom: calc(18px + env(safe-area-inset-bottom)); left: 50%; transform: translateX(-50%); z-index: 999; border-radius: 50px; padding: 14px 28px; font-weight: 800; letter-spacing: .03em; border: none; color: #fff; background: linear-gradient(135deg, #ef4444, #dc2626); box-shadow: 0 12px 28px rgba(239, 68, 68, .45); }
        .btn-sos:hover { color: #fff; }
        .alert { border-radius: 14px; }
        .stage-icon { width: 84px; height: 84px; border-radius: 50%; background: #dcfce7; color: var(--lx-success); display: inline-flex; align-items: center; justify-content: center; font-size: 40px; box-shadow: 0 0 0 10px #f0fdf4; }
    </style>
</head>
<body>
    <?php
        $status_labels = array(
            'WAITING_GATE' => 'Menunggu Gate In',
            'IN_YARD'      => 'Menuju Area Survey',
            'IN_SURVEY'    => 'Sedang Survey',
            'LIFT_OFF'     => 'Menuju Blok (Lift Off)',
            'AT_YARD'      => 'Menuju Gate Out',
            'COMPLETED'    => 'Selesai'
        );
        // Export / Repo Out: tanpa survey, Yardman langsung memuat container
        $is_muat = in_array($queue->activity, array('D', 'E'), TRUE);
        if ($is_muat) {
            $status_labels['IN_YARD'] = 'Menuju Yardman (Muat)';
            $step_names = array('Gate In', 'Muat', 'Gate Out');
            $step_index = array('IN_YARD' => 1, 'AT_YARD' => 2);
        } else {
            $step_names = array('Gate In', 'Survey', 'Lift Off', 'Gate Out');
            $step_index = array('IN_YARD' => 0, 'IN_SURVEY' => 1, 'LIFT_OFF' => 2, 'AT_YARD' => 3);
        }
        $current_step = isset($step_index[$queue->status]) ? $step_index[$queue->status] : -1;
    ?>
    <div class="app">
        <!-- Header Informasi Armada -->
        <div class="head lx-waves">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <?php $this->load->view('partials/logo', array('size' => 36)); ?>
                    <div>
                        <div class="lx-brand">LASMONO</div>
                        <div class="small" style="color: #94a3b8;">Portal Supir</div>
                    </div>
                </div>
                <a href="<?= base_url('auth/logout'); ?>" class="lx-icon-btn" title="Keluar"><i class="fas fa-arrow-right-from-bracket"></i></a>
            </div>
            <div class="mt-3"><span class="plate"><i class="fas fa-truck-front" style="color: #f6c64a;"></i><?= html_escape($queue->license_plate); ?></span></div>
        </div>

        <div class="content">
            <?php if($this->session->flashdata('info_driver')): ?>
                <div class="alert alert-primary alert-dismissible fade show small py-2 border-0 shadow-sm mb-3" role="alert">
                    <i class="fas fa-circle-info me-1"></i> <?= html_escape($this->session->flashdata('info_driver')); ?>
                    <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if($this->session->flashdata('error_sos')): ?>
                <div class="alert alert-danger py-2 small border-0 shadow-sm mb-3"><?= html_escape($this->session->flashdata('error_sos')); ?></div>
            <?php endif; ?>
            <?php if($this->session->flashdata('success_sos')): ?>
                <div class="alert alert-success py-2 small border-0 shadow-sm mb-3"><i class="fas fa-circle-check me-1"></i> <?= html_escape($this->session->flashdata('success_sos')); ?></div>
            <?php endif; ?>

            <!-- Nomor Antrean & FIFO -->
            <div class="card-app text-center lx-rise">
                <div class="lx-eyebrow mb-1">Nomor Antrean Anda</div>
                <span class="lx-squiggle center mb-2"></span>
                <div class="queue-no mb-2">#<?= html_escape($queue->queue_number); ?></div>
                <div class="meta-row">
                    <span id="positionBadge" class="lx-pill lx-pill-blue" <?= $queue_position === NULL ? 'style="display:none;"' : ''; ?>><i class="fas fa-list-ol"></i><span>Urutan ke-<span id="queuePosition"><?= $queue_position; ?></span></span></span>
                    <?php $act_labels = array('Z' => 'Repo In', 'A' => 'Import', 'D' => 'Export', 'E' => 'Repo Out'); ?>
                    <span class="act-tag act-<?= html_escape($queue->activity); ?>" style="font-size: 12px; padding: 5px 10px; border-radius: 50px;"><?= html_escape(isset($act_labels[$queue->activity]) ? $act_labels[$queue->activity] : $queue->activity); ?></span>
                    <span id="statusBadge" class="lx-pill <?= $queue->status === 'WAITING_GATE' ? 'lx-pill-amber' : 'lx-pill-green'; ?>">
                        <i class="fas fa-circle" style="font-size: 7px;"></i> <?= html_escape(isset($status_labels[$queue->status]) ? $status_labels[$queue->status] : $queue->status); ?>
                    </span>
                </div>
                <div class="small text-secondary mt-2" id="estimatedWaitText"><?php if ($estimated_wait !== NULL): ?><?= $estimated_wait > 0 ? 'Estimasi dipanggil Gate In &plusmn;' . $estimated_wait . ' menit' : 'Anda antrean berikutnya, siapkan kitir'; ?><?php endif; ?></div>
            </div>

            <?php if (!empty($queue->gate_in_at)): ?>
            <!-- Lokasi Penempatan (ditentukan Yardman setelah survey) -->
            <div class="location-box <?= empty($queue->zone_code) ? 'pending' : ''; ?> lx-rise">
                <span class="ico"><i class="fas <?= empty($queue->zone_code) ? 'fa-map-location-dot' : 'fa-location-dot'; ?>"></i></span>
                <div>
                    <div class="small" style="opacity: .8;"><?= $is_muat ? 'Lokasi Pengambilan Container' : 'Lokasi Penempatan Container'; ?></div>
                    <?php if (!empty($queue->zone_code)): ?>
                        <div class="h4 fw-bold mb-0"><?= html_escape($queue->depo); ?> &middot; Blok <?= html_escape($queue->zone_code); ?></div>
                    <?php else: ?>
                        <div class="fw-semibold"><?= $is_muat ? 'Ditentukan Yardman saat muat container' : 'Ditentukan Yardman setelah survey selesai'; ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Timer KPI: besar = tahap berjalan, kecil = total proses -->
            <div class="card-app text-center lx-rise" id="timerCard">
                <?php if ($timer !== NULL): ?>
                    <div class="stepper">
                        <?php foreach ($step_names as $i => $step_name): ?>
                            <div class="step <?= $i < $current_step ? 'done' : ($i === $current_step ? 'active' : ''); ?>">
                                <div class="dot"><?= $i < $current_step ? '<i class="fas fa-check"></i>' : $i + 1; ?></div><?= $step_name; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="lx-eyebrow">Timer Tahap &middot; <span id="stageLabel" style="color: var(--lx-primary);"><?= html_escape($timer['stage_label']); ?></span></div>
                    <div class="ring" id="stageCircle">
                        <svg viewBox="0 0 200 200">
                            <defs><linearGradient id="ringGrad" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="#e5a720"/><stop offset="100%" stop-color="#f6c64a"/></linearGradient></defs>
                            <circle class="track" cx="100" cy="100" r="88" fill="none" stroke-width="14"/>
                            <circle class="bar" id="stageBar" cx="100" cy="100" r="88" fill="none" stroke-width="14" stroke-dasharray="552.92" stroke-dashoffset="0"/>
                        </svg>
                        <div class="center">
                            <span class="time" id="stageTimer">--:--</span>
                            <small class="text-secondary fw-semibold">Target <?= (int) $timer['stage_target_minutes']; ?> menit</small>
                        </div>
                    </div>
                    <div class="timer-total" id="totalBox">
                        <i class="fas fa-stopwatch"></i> Total proses <b id="totalTimer">--:--</b> / <?= (int) $timer['total_target_minutes']; ?>:00
                    </div>
                    <div class="small text-secondary mt-2" id="timerStatusText">Timer KPI berjalan sejak Gate In</div>
                <?php else: ?>
                    <div class="empty-state py-2">
                        <span class="ico" style="background: #fef3c7; color: #d97706;"><i class="fas fa-hourglass-half"></i></span>
                        <div class="fw-bold mb-1">Menunggu Gate In</div>
                        <div class="small text-secondary">Timer KPI per tahap (<?= $is_muat ? 'Gate In &middot; Muat &middot; Gate Out' : 'Gate In &middot; Survey &middot; Lift Off &middot; Gate Out'; ?>) mulai berjalan setelah security scan kitir Anda.</div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Barcode + Data Kitir (muncul setelah Gate In) -->
            <div class="card-app text-center lx-rise">
                <?php if ($qr_payload !== NULL): ?>
                    <div class="lx-eyebrow mb-3">Tunjukkan ke Yardman / Petugas Gate Out</div>
                    <div class="qr-wrap mb-3"><div id="queueQr" data-code="<?= html_escape($qr_payload); ?>"></div></div>
                    <div class="kv">
                        <div style="grid-column: span 2;"><small>No. Container</small><b style="font-size: 18px;"><?= html_escape($queue->container_no); ?></b></div>
                        <div><small>Principal</small><b><?= html_escape($queue->principal); ?></b></div>
                        <div><small>Size</small><b><?= html_escape($queue->container_size); ?></b></div>
                        <div style="grid-column: span 2;"><small>Customer</small><b><?= html_escape($queue->customer); ?></b></div>
                    </div>
                <?php else: ?>
                    <div class="empty-state py-2">
                        <span class="ico" style="background: #fff3d1; color: var(--lx-primary);"><i class="fas fa-qrcode"></i></span>
                        <div class="fw-bold mb-1">Barcode muncul setelah Gate In</div>
                        <div class="small text-secondary">Siapkan kitir Anda untuk di-scan oleh security di Gate In.</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tombol Darurat SOS Floating -->
        <button class="btn btn-sos" data-bs-toggle="modal" data-bs-target="#sosModal">
            <i class="fas fa-triangle-exclamation me-2"></i> LAPOR SOS
        </button>
    </div>

    <!-- Pop Up Tahap Selesai -->
    <div class="modal fade" id="stageModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-center">
                <div class="modal-body p-4">
                    <div class="mb-3 mt-2"><span class="stage-icon"><i class="fas fa-check"></i></span></div>
                    <h4 class="fw-bold mb-2" id="stageModalTitle"></h4>
                    <div id="stageModalDuration" class="mb-2"></div>
                    <p class="text-secondary mb-3" id="stageModalText"></p>
                    <div id="stageModalNext" class="alert alert-primary border-0 py-2 small fw-semibold mb-3"></div>
                    <button type="button" class="btn btn-primary w-100 py-3 fw-bold" data-bs-dismiss="modal">OK, Mengerti</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Form Lapor SOS -->
    <div class="modal fade" id="sosModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 text-white" style="background: linear-gradient(135deg, #ef4444, #b91c1c);">
                    <div>
                        <h5 class="modal-title fw-bold mb-0"><i class="fas fa-bullhorn me-2"></i> Lapor SOS</h5>
                        <small style="opacity: .85;">Laporan langsung diterima Control Tower</small>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="<?= base_url('driver/submit_sos'); ?>" method="POST" enctype="multipart/form-data" data-csrf-refresh>
                    <div class="modal-body">
                        <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">JENIS LAPORAN</label>
                            <select name="report_type" class="form-select fw-semibold" required>
                                <option value="UNSAFE">Kondisi Tidak Aman (Unsafe Condition)</option>
                                <option value="PUNGLI">Pemungutan Liar</option>
                                <option value="PENYEROBOTAN">Penyerobotan Antrean</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">DESKRIPSI KEJADIAN</label>
                            <textarea name="description" class="form-control" rows="3" maxlength="1000" placeholder="Jelaskan kronologi & lokasi kejadian..." required></textarea>
                        </div>
                        <div class="mb-1">
                            <label class="form-label small fw-bold text-secondary">BUKTI FOTO / VIDEO <span class="fw-normal">(opsional, maks. 10MB)</span></label>
                            <input type="file" name="sos_media" class="form-control" accept="image/jpeg,image/png,video/mp4,video/quicktime">
                            <small class="text-secondary mt-1 d-block" style="font-size: 11px;">Format: JPG, PNG, MP4, MOV</small>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="submit" class="btn btn-danger w-100 py-3 fw-bold" style="background: linear-gradient(135deg, #ef4444, #dc2626); border: none;">
                            <i class="fas fa-paper-plane me-1"></i> Kirim Laporan
                        </button>
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
        let STAGE_SECONDS = <?= (int) MY_Controller::STAGE_KPI_MINUTES; ?> * 60;
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
            STAGE_SECONDS = timer.stage_target_minutes * 60;
            tick();
        }

        function tick() {
            if (stageEnd === null) return;
            let stageLeft = (stageEnd - Date.now()) / 1000;
            let totalLeft = (totalEnd - Date.now()) / 1000;
            $('#stageTimer').text(fmt(stageLeft));
            $('#totalTimer').text(fmt(totalLeft));
            $('#stageCircle').toggleClass('late', stageLeft < 0);
            // Cincin berkurang mengikuti sisa waktu tahap (penuh = 5 menit)
            let ratio = Math.max(0, Math.min(1, stageLeft / (STAGE_SECONDS)));
            $('#stageBar').attr('stroke-dashoffset', (552.92 * (1 - (stageLeft < 0 ? 1 : ratio))).toFixed(1));
            $('#totalBox').toggleClass('late', totalLeft < 0);
            $('#timerStatusText').text(stageLeft < 0 ? 'Tahap ini melewati target KPI ' + Math.round(STAGE_SECONDS / 60) + ' menit' : 'Timer KPI berjalan sejak Gate In');
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
            $('#estimatedWaitText').text(estimatedWait === null ? '' : (estimatedWait > 0 ? 'Estimasi dipanggil Gate In \u00b1' + estimatedWait + ' menit' : 'Anda antrean berikutnya, siapkan kitir'));
        }

        function showCompleted(durations) {
            stageEnd = null;
            let html = '<div class="empty-state py-1"><span class="ico" style="background: #dcfce7; color: #10b981;"><i class="fas fa-flag-checkered"></i></span><div class="fw-bold mb-3">Servis Selesai (Gate Out)</div></div>';
            html += '<table class="table table-sm small text-start mb-0">';
            durations.forEach(function(d) {
                html += '<tr><td>' + $('<div>').text(d.label).html() + '</td><td class="text-end fw-bold ' + (d.achieved ? 'text-success' : 'text-danger') + '">' +
                    d.minutes + ' mnt ' + (d.achieved ? '&#10003;' : '&#10007;') + '</td></tr>';
            });
            html += '</table>';
            $('#timerCard').html(html);
            $('#statusBadge').attr('class', 'lx-pill lx-pill-green').html('<i class="fas fa-check"></i> Selesai');
        }

        // POP UP setiap tahap selesai
        const STAGE_POPUPS = <?= $is_muat ? 'MUAT_POPUPS' : 'SURVEY_POPUPS'; ?>();
        function MUAT_POPUPS() { return {
            IN_YARD:   { title: 'Gate In Berhasil!', text: 'Kitir <?= $queue->activity === 'D' ? 'Export' : 'Repo Out'; ?> sudah tercatat masuk depo.', next: 'Selanjutnya: menuju YARDMAN untuk muat container.' },
            AT_YARD:   { title: 'Container Sudah Dimuat!', text: <?= json_encode(trim(($queue->container_no ? 'Container ' . $queue->container_no . '. ' : '') . (!empty($queue->zone_code) ? 'Diambil di ' . $queue->depo . ' - Blok ' . $queue->zone_code . '.' : '')), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>, next: 'Selanjutnya: menuju GATE OUT dan serahkan kitir ke petugas.' },
            COMPLETED: { title: 'Gate Out Berhasil!', text: 'Seluruh proses di depo sudah selesai. Terima kasih.', next: 'Hati-hati di jalan.' }
        }; }
        function SURVEY_POPUPS() { return {
            IN_YARD:   { title: 'Gate In Berhasil!', text: 'Container <?= html_escape($queue->container_no); ?> sudah tercatat masuk depo.', next: 'Selanjutnya: menuju AREA SURVEY dan tunjukkan barcode ke Yardman.' },
            IN_SURVEY: { title: 'Masuk Area Survey', text: 'Container sedang disurvey oleh Yardman.', next: 'Selanjutnya: tunggu survey selesai, Yardman akan scan barcode Anda lagi.' },
            LIFT_OFF:  { title: 'Survey Selesai!', text: <?= json_encode(!empty($queue->zone_code) ? 'Lokasi penempatan: ' . $queue->depo . ' - Blok ' . $queue->zone_code . '.' : 'Survey container sudah selesai.', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>, next: 'Selanjutnya: menuju blok untuk LIFT OFF container, lalu tunjukkan barcode ke Yardman.' },
            AT_YARD:   { title: 'Lift Off Selesai!', text: <?= json_encode('Container ' . $queue->container_no . ' sudah diturunkan' . (!empty($queue->zone_code) ? ' di ' . $queue->depo . ' - Blok ' . $queue->zone_code : '') . '.', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>, next: 'Selanjutnya: menuju GATE OUT dan tunjukkan barcode ke petugas.' },
            COMPLETED: { title: 'Gate Out Berhasil!', text: 'Seluruh proses di depo sudah selesai. Terima kasih.', next: 'Hati-hati di jalan.' }
        }; }

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
                ? '<span class="lx-pill ' + (duration.achieved ? 'lx-pill-green' : 'lx-pill-red') + '" style="font-size: 13px;">Tahap ' + $('<div>').text(duration.label).html() + ': ' + duration.minutes + ' mnt ' + (duration.achieved ? '&#10003;' : '&#10007; lewat target') + '</span>'
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