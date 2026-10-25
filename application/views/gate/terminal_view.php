<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LASMONO - Gate Security Terminal</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        :root {
            --bg-main: #f8fafc;
            --header-dark: #0f172a;
            --accent-gate-in: #059669;
            --accent-gate-out: #dc2626;
            --card-border: #e2e8f0;
        }

        body {
            background-color: var(--bg-main);
            color: #1e293b;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Top Bar */
        .gate-navbar {
            background-color: var(--header-dark);
            color: #ffffff;
            padding: 16px 32px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
        }

        .brand-pill {
            background: #2563eb;
            color: #fff;
            font-weight: 800;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 14px;
            letter-spacing: 0.5px;
        }

        /* Mode Indicator Banner */
        .mode-banner {
            border-radius: 16px;
            padding: 20px 28px;
            color: white;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.06);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .mode-banner.gate-in {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
        }

        .mode-banner.gate-out {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
        }

        .mode-banner.control-tower {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        }

        /* Scanner Main Card */
        .scanner-card {
            background: #ffffff;
            border-radius: 20px;
            border: 1px solid var(--card-border);
            padding: 36px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        /* Animated Barcode Box */
        .barcode-box-wrapper {
            position: relative;
            max-width: 580px;
            margin: 0 auto;
        }

        .barcode-input {
            background: #f8fafc;
            border: 3px solid #cbd5e1;
            color: #0f172a;
            font-size: 28px;
            font-weight: 800;
            text-align: center;
            border-radius: 16px;
            padding: 20px;
            letter-spacing: 3px;
            transition: all 0.25s ease;
            text-transform: uppercase;
        }

        .barcode-input:focus {
            background: #ffffff;
            border-color: #2563eb;
            color: #0f172a;
            box-shadow: 0 0 0 6px rgba(37, 99, 235, 0.15);
            outline: none;
        }

        /* Laser Line Indicator */
        .laser-line-subtle {
            position: absolute;
            bottom: 0;
            left: 10%;
            width: 80%;
            height: 3px;
            background: #2563eb;
            border-radius: 2px;
            box-shadow: 0 0 8px #2563eb;
            animation: pulseLaser 2s infinite ease-in-out;
        }

        @keyframes pulseLaser {
            0% { opacity: 0.2; transform: scaleX(0.8); }
            50% { opacity: 1; transform: scaleX(1); }
            100% { opacity: 0.2; transform: scaleX(0.8); }
        }

        /* Alert Display */
        .status-toast {
            border-radius: 14px;
            font-size: 18px;
            padding: 16px 24px;
            border: none;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.06);
        }

        /* Recent Activity Table */
        .recent-card {
            background: #ffffff;
            border-radius: 20px;
            border: 1px solid var(--card-border);
            padding: 24px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
        }

        .table-custom th {
            background-color: #f1f5f9;
            color: #64748b;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: none;
            padding: 12px 16px;
        }

        .table-custom td {
            padding: 14px 16px;
            vertical-align: middle;
            font-size: 14px;
            border-bottom: 1px solid #f1f5f9;
        }
    </style>
</head>
<body>

    <!-- Top Navbar -->
    <header class="gate-navbar d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-3">
            <span class="brand-pill">LAS</span>
            <div>
                <h5 class="fw-bold mb-0 text-white">LASMONO Security Terminal</h5>
                <small class="text-light opacity-75" style="font-size: 12px;">Sistem Otomasi Gerbang & Transparansi FIFO Depo</small>
            </div>
        </div>
        <div class="d-flex align-items-center gap-3">
            <div class="text-end me-2 d-none d-md-block">
                <small class="d-block text-muted" style="font-size: 11px;">Petugas Aktif</small>
                <span class="fw-bold text-light" style="font-size: 14px;"><?= html_escape($this->session->userdata('name')); ?></span>
            </div>
            <a href="<?= base_url('auth/logout'); ?>" class="btn btn-outline-danger btn-sm rounded-pill px-3 fw-bold">
                <i class="fas fa-sign-out-alt me-1"></i> Logout
            </a>
        </div>
    </header>

    <main class="container-fluid px-4 py-4 flex-grow-1">
        <!-- Mode Banner Header -->
        <div class="row mb-4">
            <div class="col-12">
                <?php 
                    $modeClass = 'gate-in';$modeIcon = 'fa-door-open';
                    $modeTitle = 'GATE IN TERMINAL (GERBANG MASUK)';$modeDesc = 'Mode Scan Pemindaian Truk Masuk Area Depo';

                    if ($role == 'GATE_OUT') {
                        $modeClass = 'gate-out';$modeIcon = 'fa-door-closed';
                        $modeTitle = 'GATE OUT TERMINAL (GERBANG KELUAR)';$modeDesc = 'Mode Scan Pemindaian Penyelesaian Servis & Keluar Depo';
                    } elseif ($role == 'CONTROL_TOWER') {
                        $modeClass = 'control-tower';$modeIcon = 'fa-desktop';
                        $modeTitle = 'SUPERVISOR MASTER TERMINAL';$modeDesc = 'Akses Penuh Pemindaian Gate In & Gate Out';
                    }
                ?>
                <div class="mode-banner <?= $modeClass; ?>">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-white text-dark rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 54px; height: 54px;">
                            <i class="fas <?= $modeIcon; ?> fa-2x"></i>
                        </div>
                        <div>
                            <h4 class="fw-bold mb-0"><?= $modeTitle; ?></h4>
                            <small class="opacity-90"><?= $modeDesc; ?></small>
                        </div>
                    </div>
                    <div class="d-none d-lg-flex align-items-center gap-3">
                        <span class="badge bg-white text-dark fw-bold px-3 py-2 rounded-pill" style="font-size: 13px;">
                            <i class="fas fa-circle text-success me-1"></i> SCANNER STABLE
                        </span>
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
                        <div class="d-inline-flex p-3 bg-light rounded-circle text-primary mb-3">
                            <i class="fas fa-barcode fa-3x"></i>
                        </div>
                        <h4 class="fw-bold text-dark mb-1">Arahkan Scanner ke Barcode Kitir</h4>
                        <p class="text-muted small mb-0">Input terdeteksi otomatis saat pemindai laser memindai kode dokumen supir</p>
                    </div>

                    <!-- Input Box -->
                    <div class="barcode-box-wrapper mb-4">
                        <input type="text" id="gateBarcodeInput" class="form-control barcode-input" placeholder="READY TO SCAN..." autofocus autocomplete="off">
                        <div class="laser-line-subtle"></div>
                    </div>

                    <div class="d-flex justify-content-center align-items-center gap-4 text-muted small">
                        <span><i class="fas fa-keyboard me-1"></i> Auto-Focus Active</span>
                        <span><i class="fas fa-check-circle text-success me-1"></i> Fast USB/Wireless Input</span>
                    </div>
                </div>
            </div>

            <!-- Right Column: Recent Activity Log -->
            <div class="col-lg-5">
                <div class="recent-card h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="fas fa-history text-primary me-2"></i> Transaksi Terakhir Diproses
                        </h6>
                        <span class="badge bg-primary-subtle text-primary fw-bold" id="totalProcessedCount">0 Truk Hari Ini</span>
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
    </main>

    <footer class="bg-white border-top py-3 text-center text-muted small">
        &copy; <?= date('Y'); ?> Depo Petikemas MTKI - LASMONO Gate Integration Terminal. All rights reserved.
    </footer>

    <script>
        const CSRF_NAME = "<?= $this->security->get_csrf_token_name(); ?>";
        const CSRF_HASH = "<?= $this->security->get_csrf_hash(); ?>";

        function esc(value) {
            return $('<div>').text(value === null || value === undefined ? '' : String(value)).html();
        }

        let processedCount = 0;

        // Kunci kursor otomatis agar tidak lepas dari input box
        $(document).ready(function() {$('#gateBarcodeInput').focus();
            
            $(document).click(function() {$('#gateBarcodeInput').focus();
            });
        });

        // Deteksi Otomatis saat Barcode Scanner menekan tombol Enter
        $('#gateBarcodeInput').keypress(function(e) {
            if (e.which === 13) {
                let code = $(this).val().trim();
                if (code !== '') {
                    sendGateScan(code);
                    $(this).val('');
                }
            }
        });

        function sendGateScan(code) {
            $.ajax({
                url: "<?= base_url('gate/process_scan'); ?>",
                type: "POST",
                data: { queue_number: code, [CSRF_NAME]: CSRF_HASH },
                dataType: "json",
                success: function(res) {
                    let alertClass = res.status === 'success' ? 'alert-success text-success' : (res.status === 'warning' ? 'alert-warning text-dark' : 'alert-danger text-danger');
                    let icon = res.status === 'success' ? 'check-circle' : 'exclamation-circle';

                    $('#scanResult')
                        .removeClass('alert-success alert-warning alert-danger text-success text-dark text-danger')
                        .addClass(alertClass)
                        .html('<i class="fas fa-' + icon + ' me-2"></i><b>' + esc(res.message).replace(/\n/g, "<br>") + '</b>')
                        .fadeIn();

                    if (res.status === 'success') {
                        addRecentRow(code, res.message);
                    }

                    setTimeout(function() {
                        $('#scanResult').fadeOut();
                    }, 4000);
                },
                error: function(xhr) {
                    let msg = xhr.status === 401 || xhr.status === 403
                        ? 'Sesi berakhir. Halaman dimuat ulang...'
                        : 'Gagal terhubung ke server. Scan ulang barcode.';
                    $('#scanResult')
                        .removeClass('alert-success alert-warning text-success text-dark')
                        .addClass('alert-danger text-danger')
                        .text(msg)
                        .fadeIn();
                    if (xhr.status === 401 || xhr.status === 403) {
                        setTimeout(function() { window.location.reload(); }, 1500);
                    }
                }
            });
        }

        function addRecentRow(code, msg) {
            $('#emptyScanRow').hide();
            processedCount++;
            $('#totalProcessedCount').text(processedCount + ' Truk Hari Ini');

            let now = new Date();
            let timeStr = (now.getHours()<10?'0':'') + now.getHours() + ':' + (now.getMinutes()<10?'0':'') + now.getMinutes() + ':' + (now.getSeconds()<10?'0':'') + now.getSeconds();

            let statusBadge = '<span class="badge bg-success">SUCCESS</span>';

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