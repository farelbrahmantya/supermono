<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control Tower LASMONO - Depo MTKI</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body { background-color: #f4f6f9; color: #2b2d42; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        .navbar-custom { background-color: #0b1e38; padding: 15px 30px; }
        .card-kpi { background: #ffffff; border-radius: 12px; border: none; padding: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); }
        .card-section { background: #ffffff; border-radius: 12px; border: none; padding: 24px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); }
        .icon-kpi-wrapper { width: 42px; height: 42px; border-radius: 10px; display: flex; align-items: center; justify-content: center; }
        .grid-density { display: grid; grid-template-columns: repeat(5, 1fr); gap: 4px; }
        .density-box { padding: 10px 4px; border-radius: 4px; text-align: center; font-weight: 600; font-size: 11px; }
        .density-low { background-color: #72efdd; color: #10393b; }
        .density-medium { background-color: #ffd166; color: #433203; }
        .density-high { background-color: #ef476f; color: #ffffff; }
        .table-custom th { background-color: #f8f9fa; color: #6c757d; font-size: 13px; font-weight: 600; border-bottom: 2px solid #edf2f7; }
        .table-custom td { padding: 14px 12px; font-size: 14px; color: #2d3748; vertical-align: middle; }
        .badge-pill-status { border-radius: 20px; padding: 6px 14px; font-weight: 600; font-size: 12px; }
        .badge-yard { background-color: #3b82f6; color: #ffffff; }
        .badge-waiting { background-color: #f59e0b; color: #ffffff; }
        .badge-over-sla { background-color: #ef476f; color: #ffffff; animation: pulse 1.5s infinite; }
        @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.6; } 100% { opacity: 1; } }
    </style>
</head>
<body>
    <!-- Top Header -->
    <div class="navbar-custom d-flex justify-content-between align-items-center text-white mb-4">
        <div class="d-flex align-items-center">
            <div class="bg-white text-dark fw-bold rounded p-2 me-3" style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; font-size: 12px; color: #0b1e38 !important;">LAS</div>
            <h4 class="fw-bold mb-0">Control Tower LASMONO - Depo MTKI</h4>
        </div>
        <div class="d-flex align-items-center">
            <button class="btn btn-danger btn-sm me-3 fw-bold rounded-pill" data-bs-toggle="modal" data-bs-target="#sosModal">
                <i class="fas fa-bell me-1"></i> SOS (<span id="sosCount"><?= count($sos_reports); ?></span>)
            </button>
            <a href="<?= base_url('yardman'); ?>" class="btn btn-warning btn-sm me-3 fw-bold"><i class="fas fa-qrcode me-1"></i> Scanner Yardman</a>
            <span class="me-3 text-light opacity-75 small">Admin: Supervisor</span>
            <a href="<?= base_url('auth/logout'); ?>" class="btn btn-outline-light btn-sm"><i class="fas fa-sign-out-alt"></i></a>
        </div>
    </div>

    <div class="container-fluid px-4">
        <!-- KPI Cards -->
        <h6 class="fw-bold text-muted mb-3">KPI</h6>
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card-kpi d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-muted d-block mb-1">Rata-rata TAT (Turn Around Time)</small>
                        <h2 class="fw-bold mb-0" style="color: #1e293b;"><span id="kpiTat"><?= $kpi['avg_tat']; ?></span> Menit</h2>
                    </div>
                    <div class="icon-kpi-wrapper" style="background-color: #ffedd5; color: #ea580c;">
                        <i class="fas fa-stopwatch fa-lg"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card-kpi d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-muted d-block mb-1">Truk Antre (Luar)</small>
                        <h2 class="fw-bold mb-0" style="color: #1e293b;" id="kpiWaiting"><?= $kpi['truck_waiting']; ?></h2>
                    </div>
                    <div class="icon-kpi-wrapper" style="background-color: #dbeafe; color: #2563eb;">
                        <i class="fas fa-truck fa-lg"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card-kpi d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-muted d-block mb-1">Truk di Yard</small>
                        <h2 class="fw-bold mb-0" style="color: #1e293b;" id="kpiInYard"><?= $kpi['truck_in_yard']; ?></h2>
                    </div>
                    <div class="icon-kpi-wrapper" style="background-color: #fef3c7; color: #d97706;">
                        <i class="fas fa-boxes fa-lg"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card-kpi d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-muted d-block mb-1">Gate In Aktif</small>
                        <h2 class="fw-bold mb-0" style="color: #1e293b;"><?= $kpi['gate_active']; ?></h2>
                    </div>
                    <div class="icon-kpi-wrapper" style="background-color: #dcfce7; color: #16a34a;">
                        <i class="fas fa-door-open fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="row g-4">
            <!-- Live Antrean (SLA Monitor) -->
            <div class="col-lg-8">
                <div class="card-section">
                    <h5 class="fw-bold mb-4" style="color: #0f172a;">Live Antrean (SLA Monitor)</h5>
                    <div class="table-responsive">
                        <table class="table table-custom border-0">
                            <thead>
                                <tr>
                                    <th>Queue No.</th>
                                    <th>License Plate</th>
                                    <th>Status</th>
                                    <th>Elapsed Time</th>
                                    <th>Estimated Call</th>
                                </tr>
                            </thead>
                            <tbody id="queueTableBody">
                                <?php foreach($queues as $q): ?>
                                <tr>
                                    <td class="fw-bold"><?= $q->queue_number; ?></td>
                                    <td class="fw-semibold"><?= $q->license_plate; ?></td>
                                    <td>
                                        <?php if($q->status == 'IN_YARD'): ?>
                                            <span class="badge badge-pill-status badge-yard">Di Yard (Blok B)</span>
                                        <?php elseif($q->status == 'IN_YARD' && $q->elapsed_minutes > 15): ?>
                                            <span class="badge badge-pill-status badge-over-sla">Over SLA</span>
                                        <?php else: ?>
                                            <span class="badge badge-pill-status badge-waiting">Menunggu Gate</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $q->elapsed_minutes ? $q->elapsed_minutes : 0; ?> Menit</td>
                                    <td>15 Menit</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Kepadatan Yard (Heatmap Matrix) -->
            <div class="col-lg-4">
                <div class="card-section">
                    <h5 class="fw-bold mb-4" style="color: #0f172a;">Kepadatan Yard</h5>
                    <div class="grid-density mb-3">
                        <?php foreach($heatmap as $code => $info): ?>
                            <div class="density-box density-<?= $info['level']; ?>">
                                <?= $code; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-4 pt-2 border-top">
                        <small class="fw-bold text-muted d-block mb-2">Density</small>
                        <div class="d-flex align-items-center gap-3 small text-muted">
                            <span class="d-flex align-items-center"><span class="rounded me-1" style="width: 12px; height: 12px; background: #72efdd;"></span> Low</span>
                            <span class="d-flex align-items-center"><span class="rounded me-1" style="width: 12px; height: 12px; background: #ffd166;"></span> Medium</span>
                            <span class="d-flex align-items-center"><span class="rounded me-1" style="width: 12px; height: 12px; background: #ef476f;"></span> High</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal SOS Reports -->
    <div class="modal fade" id="sosModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title fw-bold"><i class="fas fa-bullhorn me-2"></i> Laporan SOS Pungli & Penyerobotan</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <table class="table table-striped align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Waktu</th>
                                <th>No. Antrean</th>
                                <th>Nopol</th>
                                <th>Jenis</th>
                                <th>Deskripsi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($sos_reports as $sos): ?>
                            <tr>
                                <td><small><?= $sos->created_at; ?></small></td>
                                <td><b>#<?= $sos->queue_number; ?></b></td>
                                <td><?= $sos->license_plate; ?></td>
                                <td><span class="badge bg-danger"><?= $sos->report_type; ?></span></td>
                                <td><?= $sos->description; ?></td>
                                <td>
                                    <?php if(!empty($sos->media_path)): ?>
                                        <a href="<?= base_url($sos->media_path); ?>" target="_blank" class="btn btn-sm btn-outline-danger fw-bold py-0">
                                            <i class="fas fa-paperclip me-1"></i> Lihat Bukti
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted small">- Tidak ada -</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                    </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        setInterval(function() {
            $.ajax({
                url: "<?= base_url('control_tower/get_live_data'); ?>",
                type: "GET",
                dataType: "json",
                success: function(data) {
                    $('#kpiTat').text(data.kpi.avg_tat);
                    $('#kpiWaiting').text(data.kpi.truck_waiting);
                    $('#kpiInYard').text(data.kpi.truck_in_yard);
                    $('#sosCount').text(data.sos_reports.length);
                }
            });
        }, 5000);
    </script>
</body>
</html>