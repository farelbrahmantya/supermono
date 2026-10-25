<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>LASMONO - Yardman Scanner</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        :root { --primary-dark: #0b1e38; --accent-blue: #2563eb; --accent-green: #10b981; }
        body { background-color: #f1f5f9; color: #1e293b; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        .app-header { background-color: var(--primary-dark); color: white; padding: 18px 20px; border-bottom-left-radius: 20px; border-bottom-right-radius: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.12); }
        .card-custom { background: #ffffff; border-radius: 18px; border: none; box-shadow: 0 4px 20px rgba(0,0,0,0.04); overflow: hidden; }
        
        /* Bounding Frame & Laser Animation */
        .scanner-container { position: relative; border-radius: 14px; overflow: hidden; background: #000; }
        #reader { width: 100%; min-height: 250px; }
        .laser-line {
            position: absolute; top: 0; left: 5%; width: 90%; height: 3px;
            background: linear-gradient(90deg, rgba(239,68,68,0) 0%, #ef4444 50%, rgba(239,68,68,0) 100%);
            box-shadow: 0 0 12px #ef4444; z-index: 10;
            animation: scanLaser 2s infinite ease-in-out;
        }
        @keyframes scanLaser {
            0% { top: 5%; }
            50% { top: 90%; }
            100% { top: 5%; }
        }

        .btn-torch { background: rgba(255,255,255,0.2); backdrop-filter: blur(5px); color: #fff; border: 1px solid rgba(255,255,255,0.3); border-radius: 50px; padding: 6px 16px; font-size: 12px; font-weight: 600; }
        .btn-torch:hover, .btn-torch.active { background: #f59e0b; color: #000; border-color: #f59e0b; }
        
        .recent-item { padding: 12px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; }
        .recent-item:last-child { border-bottom: none; }
    </style>
</head>
<body>
    <div class="container p-0" style="max-width: 480px;">
        <!-- Header -->
        <div class="app-header mb-3 d-flex justify-content-between align-items-center">
            <div>
                <div class="d-flex align-items-center gap-2">
                    <h5 class="fw-bold mb-0">LASMONO</h5>
                    <span class="badge bg-warning text-dark fw-bold" style="font-size: 10px;">YARDMAN PRO</span>
                </div>
                <small class="text-light opacity-75">Operational Verification Terminal</small>
            </div>
            <a href="<?= base_url('auth/logout'); ?>" class="btn btn-outline-light btn-sm rounded-circle" style="width: 38px; height: 38px; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-power-off"></i>
            </a>
        </div>

        <div class="px-3">
            <!-- Alert Notifikasi Toast -->
            <div id="scanResult" style="display:none;" class="alert border-0 shadow-sm mb-3"></div>

            <!-- Card Scanner Utama -->
            <div class="card-custom p-3 mb-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="fw-bold text-secondary small"><i class="fas fa-camera me-1"></i> PEMINDAI KAMERA</span>
                    <div>
                        <button id="btnTorch" class="btn btn-torch" style="display:none;">
                            <i class="fas fa-bolt me-1"></i> Flash
                        </button>
                        <span id="scanStatusBadge" class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">Kamera Aktif</span>
                    </div>
                </div>

                <!-- Bounding Box Frame Kamera -->
                <div class="scanner-container mb-2">
                    <div class="laser-line"></div>
                    <div id="reader"></div>
                </div>
                <small class="text-muted text-center d-block" style="font-size: 11px;">Posisikan QR / Barcode Kitir tepat di dalam bingkai</small>
            </div>

            <!-- Card Input Manual -->
            <div class="card-custom p-3 mb-3">
                <span class="fw-bold text-secondary small d-block mb-2"><i class="fas fa-keyboard me-1"></i> INPUT MANUAL KODE ANTREAN</span>
                <div class="input-group">
                    <input type="text" id="manualQueueInput" class="form-control fw-bold text-uppercase border-end-0" placeholder="Contoh: A-20261021-001" style="border-radius: 10px 0 0 10px;">
                    <button id="btnManualSubmit" class="btn btn-primary fw-bold px-4" style="border-radius: 0 10px 10px 0;">PROSES</button>
                </div>
            </div>

            <!-- Card Riwayat Pindai Terakhir -->
            <div class="card-custom p-3 mb-4">
                <span class="fw-bold text-secondary small d-block mb-2"><i class="fas fa-history me-1"></i> RIWAYAT SCAN TERAKHIR</span>
                <div id="recentScanList">
                    <div class="text-center py-3 text-muted small" id="emptyRecentText">Belum ada riwayat pemindaian.</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let isProcessing = false;
        let html5QrCode = null;

        // Inisialisasi Audio Beep Bawaan Browser (Web Audio API)
        function playBeepSound() {
            try {
                let audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                let osc = audioCtx.createOscillator();
                let gain = audioCtx.createGain();
                osc.type = "sine";
                osc.frequency.value = 880; // Nada Beep tinggi (880Hz)
                gain.gain.value = 0.1;
                osc.connect(gain);
                gain.connect(audioCtx.destination);
                osc.start();
                setTimeout(() => { osc.stop(); audioCtx.close(); }, 150);
            } catch(e) {}
        }

        function triggerVibration() {
            if ("vibrate" in navigator) {
                navigator.vibrate([100, 50, 100]); // Getar 2x
            }
        }

        function sendScanData(queueNumber) {
            if (isProcessing) return;
            isProcessing = true;

            playBeepSound();
            triggerVibration();

            $('#scanStatusBadge').removeClass('bg-success-subtle text-success').addClass('bg-warning-subtle text-warning').text('Memproses...');

            $.ajax({
                url: "<?= base_url('yardman/process_scan'); ?>",
                type: "POST",
                data: { queue_number: queueNumber },
                dataType: "json",
                success: function(res) {
                    let alertClass = res.status === 'success' ? 'alert-success text-success' : (res.status === 'warning' ? 'alert-warning text-dark' : 'alert-danger text-danger');
                    let icon = res.status === 'success' ? 'check-circle' : 'exclamation-circle';
                    
                    $('#scanResult')
                        .removeClass('alert-success alert-warning alert-danger text-success text-dark text-danger')
                        .addClass(alertClass)
                        .html('<i class="fas fa-' + icon + ' me-2"></i><b>' + res.message.replace(/\n/g, "<br>") + '</b>')
                        .fadeIn();

                    if (res.status === 'success') {
                        addRecentScan(queueNumber, res.message);
                    }

                    setTimeout(function() {
                        isProcessing = false;
                        $('#scanStatusBadge').removeClass('bg-warning-subtle text-warning').addClass('bg-success-subtle text-success').text('Kamera Aktif');
                    }, 3500);
                },
                error: function() {
                    isProcessing = false;
                    $('#scanStatusBadge').removeClass('bg-warning-subtle text-warning').addClass('bg-success-subtle text-success').text('Kamera Aktif');
                }
            });
        }

        function addRecentScan(code, msg) {
            $('#emptyRecentText').hide();
            let now = new Date();
            let timeStr = (now.getHours()<10?'0':'') + now.getHours() + ':' + (now.getMinutes()<10?'0':'') + now.getMinutes();

            let itemHtml = `
                <div class="recent-item">
                    <div>
                        <div class="fw-bold text-dark mb-0">${code}</div>
                        <small class="text-muted" style="font-size: 11px;">${msg.split('\n')[0]}</small>
                    </div>
                    <span class="badge bg-light text-dark border">${timeStr}</span>
                </div>
            `;
            $('#recentScanList').prepend(itemHtml);
            
            // Batasi 4 baris riwayat terakhir
            if ($('#recentScanList .recent-item').length > 4) {
                $('#recentScanList .recent-item:last').remove();
            }
        }

        $('#btnManualSubmit').click(function() {
            let qNum = $('#manualQueueInput').val().trim();
            if (qNum !== '') {
                sendScanData(qNum);
                $('#manualQueueInput').val('');
            }
        });

        $('#manualQueueInput').keypress(function(e) {
            if (e.which === 13) {
                $('#btnManualSubmit').click();
            }
        });

        // Initialize Html5Qrcode Scanner Engine
        let html5QrcodeScanner = new Html5QrcodeScanner("reader", { fps: 15, qrbox: 240 });
        html5QrcodeScanner.render(function(decodedText) {
            sendScanData(decodedText);
        });
    </script>
</body>
</html>