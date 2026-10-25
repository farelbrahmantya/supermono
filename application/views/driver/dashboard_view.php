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
                <small class="text-light opacity-75">Armada: <b><?= $queue->license_plate; ?></b></small>
            </div>
            <a href="<?= base_url('auth/logout'); ?>" class="btn btn-outline-light btn-sm"><i class="fas fa-sign-out-alt"></i></a>
        </div>

        <div class="p-3">
            <!-- Notifikasi Informasi Antrean Aktif -->
            <?php if($this->session->flashdata('info_driver')): ?>
                <div class="alert alert-info alert-dismissible fade show small py-2 border-0 shadow-sm mb-3" role="alert">
                    <i class="fas fa-info-circle me-1"></i> <?= $this->session->flashdata('info_driver'); ?>
                    <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Notifikasi Error SOS -->
            <?php if($this->session->flashdata('error_sos')): ?>
                <div class="alert alert-danger py-2 small border-0 shadow-sm mb-3"><?= $this->session->flashdata('error_sos'); ?></div>
            <?php endif; ?>

            <!-- Notifikasi Sukses SOS -->
            <?php if($this->session->flashdata('success_sos')): ?>
                <div class="alert alert-success py-2 small border-0 shadow-sm mb-3"><?= $this->session->flashdata('success_sos'); ?></div>
            <?php endif; ?>

            <!-- Card Nomor Antrean & FIFO -->
            <div class="card card-custom p-3 text-center mb-3">
                <span class="text-muted small">Nomor Antrean Anda:</span>
                <h1 class="fw-bold text-primary display-5 mb-0">#<?= $queue->queue_number; ?></h1>
                <div class="mt-2">
                    <span class="badge bg-primary">Urutan ke-<?= $queue_position; ?> (FIFO)</span>
                    <span id="statusBadge" class="badge <?= in_array($queue->status, array('IN_YARD', 'AT_YARD')) ? 'bg-primary text-white' : ($queue->status == 'COMPLETED' ? 'bg-success text-white' : 'bg-warning text-dark'); ?>">
                        <?= $queue->status; ?>
                    </span>
                </div>
            </div>

            <!-- Countdown SLA Timer -->
            <div class="card card-custom p-3 text-center mb-3">
                <span class="text-muted small">Countdown Target SLA Pelayanan</span>
                <div class="timer-circle" id="timerBorder">
                    <span class="h3 fw-bold mb-0" id="slaTimerDisplay">15:00</span>
                    <small class="text-muted" style="font-size: 10px;">Target: 15 Mnt</small>
                </div>
                <small class="text-muted" id="slaStatusText">Menunggu Scan Gate In oleh Petugas</small>
            </div>

            <!-- QR Barcode Kitir -->
            <div class="card card-custom p-3 text-center">
                <span class="text-muted small mb-2">Tunjukkan Barcode ke Petugas Gate / Yardman</span>
                <div class="d-flex justify-content-center">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=160x160&data=<?= $queue->queue_number; ?>" alt="QR Antrean" class="img-fluid">
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
                <form action="<?= base_url('driver/submit_sos'); ?>" method="POST" enctype="multipart/form-data">
                    <div class="modal-body text-dark">
                        <input type="hidden" name="queue_id" value="<?= $queue->id; ?>">
                        
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
                            <textarea name="description" class="form-control" rows="3" placeholder="Jelaskan kronologi & lokasi kejadian..." required></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Unggah Bukti Foto / Video <small class="text-muted">(Maks. 10MB)</small></label>
                            <input type="file" name="sos_media" class="form-control" accept="image/*,video/*">
                            <small class="text-muted mt-1 d-block" style="font-size: 11px;">
                                <i class="fas fa-info-circle me-1"></i> Format: JPG, PNG, MP4, AVI, MOV (Maks. 10MB)
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-danger w-100 fw-bold">KIRIM LAPORAN ANONYMOUS</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentStatus = "<?= $queue->status; ?>";
        let gateInAt = "<?= $queue->gate_in_at; ?>";
        let targetMinutes = <?= $queue->sla_target_minutes; ?>;
        let timerInterval = null;

        function startCountdown(startTimeStr) {
            if (timerInterval) clearInterval(timerInterval);

            let startTime = new Date(startTimeStr).getTime();
            let endTime = startTime + (targetMinutes * 60 * 1000);

            timerInterval = setInterval(function() {
                let now = new Date().getTime();
                let distance = endTime - now;

                if (distance < 0) {
                    clearInterval(timerInterval);
                    document.getElementById("slaTimerDisplay").innerHTML = "OVER SLA";
                    document.getElementById("slaTimerDisplay").classList.add("text-danger");
                    document.getElementById("timerBorder").style.borderColor = "#dc3545";
                    document.getElementById("slaStatusText").innerHTML = "Servis melebihi batas target waktu!";
                } else {
                    let minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                    let seconds = Math.floor((distance % (1000 * 60)) / 1000);
                    document.getElementById("slaTimerDisplay").innerHTML = 
                        (minutes < 10 ? "0" : "") + minutes + ":" + (seconds < 10 ? "0" : "") + seconds;
                    document.getElementById("slaStatusText").innerHTML = "SLA Sedang Berjalan (Di Area Yard)";
                }
            }, 1000);
        }

        // Jalankan timer jika status awal sudah IN_YARD atau AT_YARD
        if ((currentStatus === 'IN_YARD' || currentStatus === 'AT_YARD') && gateInAt !== '') {
            startCountdown(gateInAt);
        }

        // AUTO-POLLING: Cek perubahan status setiap 2 detik
        setInterval(function() {
            $.ajax({
                url: "<?= base_url('driver/get_status_json'); ?>",
                type: "GET",
                dataType: "json",
                success: function(res) {
                    if (res.status === 'success') {
                        // Jika status diperbarui oleh Yardman (misal WAITING_GATE -> IN_YARD / AT_YARD)
                        if (currentStatus !== res.queue_status) {
                            currentStatus = res.queue_status;
                            gateInAt = res.gate_in_at;

                            if (res.queue_status === 'IN_YARD' || res.queue_status === 'AT_YARD') {
                                // Perbarui Badge Status
                                $('#statusBadge')
                                    .removeClass('bg-warning text-dark bg-success')
                                    .addClass('bg-primary text-white')
                                    .text(res.queue_status);
                                
                                // Jalankan countdown jika belum aktif
                                if (res.gate_in_at) {
                                    startCountdown(res.gate_in_at);
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
                    }
                }
            });
        }, 2000);
    </script>
</body>
</html>