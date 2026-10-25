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
        .timer-circle { width: 140px; height: 140px; border-radius: 50%; border: 6px solid #198754; display: flex; flex-direction: column; align-items: center; justify-content: center; margin: 10px auto; transition: all 0.3s ease; }
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

            <!-- Card Nomor Antrean & FIFO -->
            <div class="card card-custom p-3 text-center mb-3">
                <span class="text-muted small">Nomor Antrean Anda:</span>
                <h1 class="fw-bold text-primary display-5 mb-0">#<?= html_escape($queue->queue_number); ?></h1>
                <div class="mt-2">
                    <span id="positionBadge" class="badge bg-primary" <?= $queue_position === NULL ? 'style="display:none;"' : ''; ?>>Urutan ke-<span id="queuePosition"><?= $queue_position; ?></span> (FIFO)</span>
                    <span id="statusBadge" class="badge <?= in_array($queue->status, array('IN_YARD', 'AT_YARD')) ? 'bg-primary text-white' : ($queue->status == 'COMPLETED' ? 'bg-success text-white' : 'bg-warning text-dark'); ?>">
                        <?= $queue->status; ?>
                    </span>
                </div>
                <small class="text-muted mt-2" id="estimatedWaitText"><?php if ($estimated_wait !== NULL): ?>Estimasi dipanggil Gate In &plusmn;<?= $estimated_wait; ?> menit<?php endif; ?></small>
            </div>

            <!-- Countdown SLA Timer -->
            <div class="card card-custom p-3 text-center mb-3">
                <span class="text-muted small">Countdown Target SLA Pelayanan</span>
                <div class="timer-circle" id="timerBorder">
                    <span class="h3 fw-bold mb-0" id="slaTimerDisplay"><?= (int) $queue->sla_target_minutes; ?>:00</span>
                    <small class="text-muted" style="font-size: 10px;">Target: <?= (int) $queue->sla_target_minutes; ?> Mnt</small>
                </div>
                <small class="text-muted" id="slaStatusText">Menunggu Scan Gate In oleh Petugas</small>
            </div>

            <!-- QR Barcode Kitir -->
            <div class="card card-custom p-3 text-center">
                <span class="text-muted small mb-2">Tunjukkan Barcode ke Petugas Gate / Yardman</span>
                <div class="d-flex justify-content-center">
                    <div id="queueQr" data-code="<?= html_escape($queue->queue_number); ?>"></div>
                </div>
            </div>
        </div>

        <!-- Tombol Darurat SOS Floating -->
        <button class="btn btn-danger btn-sos" data-bs-toggle="modal" data-bs-target="#sosModal">
            <i class="fas fa-exclamation-triangle me-2"></i> LASMONO SOS
        </button>
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
        // QR dibuat di browser, nomor antrean tidak dikirim ke layanan pihak ketiga
        new QRCode(document.getElementById("queueQr"), { text: $('#queueQr').data('code'), width: 160, height: 160 });

        let currentStatus = "<?= $queue->status; ?>";
        let timerInterval = null;

        // Countdown memakai sisa detik dari server (tidak bergantung jam HP / format tanggal browser)
        function startCountdown(remainingSeconds) {
            if (timerInterval) clearInterval(timerInterval);

            let endTime = Date.now() + remainingSeconds * 1000;

            function tick() {
                let distance = endTime - Date.now();

                if (distance < 0) {
                    clearInterval(timerInterval);
                    document.getElementById("slaTimerDisplay").innerHTML = "OVER SLA";
                    document.getElementById("slaTimerDisplay").classList.add("text-danger");
                    document.getElementById("timerBorder").style.borderColor = "#dc3545";
                    document.getElementById("slaStatusText").innerHTML = "Servis melebihi batas target waktu!";
                } else {
                    let minutes = Math.floor(distance / (1000 * 60));
                    let seconds = Math.floor((distance % (1000 * 60)) / 1000);
                    document.getElementById("slaTimerDisplay").innerHTML = 
                        (minutes < 10 ? "0" : "") + minutes + ":" + (seconds < 10 ? "0" : "") + seconds;
                    document.getElementById("slaStatusText").innerHTML = "SLA Sedang Berjalan (Di Area Yard)";
                }
            }

            tick();
            timerInterval = setInterval(tick, 1000);
        }

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

        // Jalankan timer jika status awal sudah IN_YARD atau AT_YARD
        <?php if ($sla_remaining_seconds !== NULL): ?>
        startCountdown(<?= (int) $sla_remaining_seconds; ?>);
        <?php endif; ?>

        // AUTO-POLLING: Cek perubahan status setiap 5 detik
        setInterval(function() {
            $.ajax({
                url: "<?= base_url('driver/get_status_json'); ?>",
                type: "GET",
                dataType: "json",
                success: function(res) {
                    if (res.status !== 'success') return;

                    updateWaitingInfo(res.queue_position, res.estimated_wait);

                    if (res.queue_status === 'EXPIRED') {
                        window.location.reload();
                        return;
                    }

                    // Jika status diperbarui oleh petugas (misal WAITING_GATE -> IN_YARD / AT_YARD)
                    if (currentStatus !== res.queue_status) {
                        currentStatus = res.queue_status;

                        if (res.queue_status === 'IN_YARD' || res.queue_status === 'AT_YARD') {
                            // Perbarui Badge Status
                            $('#statusBadge')
                                .removeClass('bg-warning text-dark bg-success')
                                .addClass('bg-primary text-white')
                                .text(res.queue_status);
                            
                            if (res.sla_remaining_seconds !== null) {
                                startCountdown(res.sla_remaining_seconds);
                            }
                        } else if (res.queue_status === 'COMPLETED') {
                            // Hentikan countdown jika servis selesai (Gate Out)
                            if (timerInterval) clearInterval(timerInterval);
                            document.getElementById("slaTimerDisplay").innerHTML = "SELESAI";
                            document.getElementById("slaStatusText").innerHTML = "Servis Telah Selesai (Gate Out)";
                            $('#statusBadge')
                                .removeClass('bg-warning bg-primary text-dark')
                                .addClass('bg-success text-white')
                                .text('COMPLETED');
                        }
                    }
                },
                error: function(xhr) {
                    if (xhr.status === 401) window.location.reload();
                }
            });
        }, 5000);
    </script>
</body>
</html>