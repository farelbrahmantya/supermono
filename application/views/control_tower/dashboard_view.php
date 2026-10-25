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
                <i class="fas fa-bell me-1"></i> SOS (<span id="sosCount"><?php $open_sos = 0; foreach ($sos_reports as $sos) { if (!$sos->is_resolved) $open_sos++; } echo $open_sos; ?></span>)
            </button>
            <a href="<?= base_url('yardman'); ?>" class="btn btn-warning btn-sm me-3 fw-bold"><i class="fas fa-qrcode me-1"></i> Scanner Yardman</a>
            <span class="me-3 text-light opacity-75 small">Admin: <?= html_escape($admin_name); ?></span>
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
                        <small class="text-muted" id="kpiTatDetail">Antre <?= $kpi['avg_wait']; ?> mnt &middot; Layanan <?= $kpi['avg_service']; ?> mnt &middot; <?= $kpi['completed_today']; ?> truk selesai</small>
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
                        <small class="text-muted"><span id="kpiExpired"><?= $kpi['truck_expired']; ?></span> antrean kedaluwarsa</small>
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
                        <small class="text-muted d-block mb-1">Gate In (1 Jam Terakhir)</small>
                        <h2 class="fw-bold mb-0" style="color: #1e293b;"><span id="kpiGateIn"><?= $kpi['gate_in_last_hour']; ?></span> Truk</h2>
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
                                    <td class="fw-bold"><?= html_escape($q['queue_number']); ?></td>
                                    <td class="fw-semibold"><?= html_escape($q['license_plate']); ?></td>
                                    <td><span class="badge badge-pill-status <?= $q['badge_class']; ?>"><?= html_escape($q['status_label']); ?></span></td>
                                    <td><?= $q['elapsed_minutes']; ?> Menit</td>
                                    <td><?= $q['estimated_call'] === NULL ? '-' : $q['estimated_call'] . ' Menit'; ?></td>
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
                    <div class="grid-density mb-3" id="heatmapGrid">
                        <?php foreach($heatmap as $code => $info): ?>
                            <div class="density-box density-<?= $info['level']; ?>" title="<?= $info['count']; ?>/<?= $info['capacity']; ?> truk">
                                <?= $code; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (empty($heatmap)): ?>
                        <small class="text-muted d-block">Belum ada data blok yard (tabel yard_zones kosong).</small>
                    <?php endif; ?>
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
                                <th>Bukti</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="sosTableBody">
                            <?php foreach($sos_reports as $sos): ?>
                            <tr>
                                <td><small><?= html_escape($sos->created_at); ?></small></td>
                                <td><b>#<?= html_escape($sos->queue_number); ?></b></td>
                                <td><?= html_escape($sos->license_plate); ?></td>
                                <td><span class="badge bg-danger"><?= html_escape($sos->report_type); ?></span></td>
                                <td><?= html_escape($sos->description); ?></td>
                                <td>
                                    <?php if(!empty($sos->media_path)): ?>
                                        <a href="<?= html_escape(base_url($sos->media_path)); ?>" rel="noopener" target="_blank" class="btn btn-sm btn-outline-danger fw-bold py-0">
                                            <i class="fas fa-paperclip me-1"></i> Lihat Bukti
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted small">- Tidak ada -</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($sos->is_resolved): ?>
                                        <span class="badge bg-success">Selesai</span>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-sm btn-success fw-bold py-0 btn-resolve-sos" data-id="<?= (int) $sos->id; ?>">Tandai Selesai</button>
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
        const MEDIA_BASE = "<?= base_url(); ?>";
        const CSRF_NAME = "<?= $this->security->get_csrf_token_name(); ?>";
        const CSRF_HASH = "<?= $this->security->get_csrf_hash(); ?>";

        function esc(value) {
            return $('<div>').text(value === null || value === undefined ? '' : String(value)).html();
        }

        function renderQueues(queues) {
            let html = '';
            queues.forEach(function(q) {
                html += '<tr>' +
                    '<td class="fw-bold">' + esc(q.queue_number) + '</td>' +
                    '<td class="fw-semibold">' + esc(q.license_plate) + '</td>' +
                    '<td><span class="badge badge-pill-status ' + esc(q.badge_class) + '">' + esc(q.status_label) + '</span></td>' +
                    '<td>' + esc(q.elapsed_minutes) + ' Menit</td>' +
                    '<td>' + (q.estimated_call === null ? '-' : esc(q.estimated_call) + ' Menit') + '</td>' +
                '</tr>';
            });
            $('#queueTableBody').html(html);
        }

        function renderHeatmap(heatmap) {
            let html = '';
            $.each(heatmap, function(code, info) {
                html += '<div class="density-box density-' + esc(info.level) + '" title="' + esc(info.count) + '/' + esc(info.capacity) + ' truk">' + esc(code) + '</div>';
            });
            $('#heatmapGrid').html(html);
        }

        function renderSos(reports) {
            let html = '';
            let openCount = 0;
            reports.forEach(function(sos) {
                if (sos.is_resolved == 0) openCount++;
                let status = sos.is_resolved == 1
                    ? '<span class="badge bg-success">Selesai</span>'
                    : '<button type="button" class="btn btn-sm btn-success fw-bold py-0 btn-resolve-sos" data-id="' + esc(sos.id) + '">Tandai Selesai</button>';
                let media = sos.media_path
                    ? '<a href="' + esc(MEDIA_BASE + sos.media_path) + '" target="_blank" rel="noopener" class="btn btn-sm btn-outline-danger fw-bold py-0"><i class="fas fa-paperclip me-1"></i> Lihat Bukti</a>'
                    : '<span class="text-muted small">- Tidak ada -</span>';
                html += '<tr>' +
                    '<td><small>' + esc(sos.created_at) + '</small></td>' +
                    '<td><b>#' + esc(sos.queue_number) + '</b></td>' +
                    '<td>' + esc(sos.license_plate) + '</td>' +
                    '<td><span class="badge bg-danger">' + esc(sos.report_type) + '</span></td>' +
                    '<td>' + esc(sos.description) + '</td>' +
                    '<td>' + media + '</td>' +
                    '<td>' + status + '</td>' +
                '</tr>';
            });
            $('#sosTableBody').html(html);
            $('#sosCount').text(openCount);
        }

        $(document).on('click', '.btn-resolve-sos', function() {
            let btn = $(this);
            btn.prop('disabled', true);
            $.ajax({
                url: "<?= base_url('control_tower/resolve_sos'); ?>",
                type: "POST",
                data: { sos_id: btn.data('id'), [CSRF_NAME]: CSRF_HASH },
                dataType: "json",
                success: function() {
                    btn.replaceWith('<span class="badge bg-success">Selesai</span>');
                    let open = parseInt($('#sosCount').text(), 10) || 0;
                    $('#sosCount').text(Math.max(0, open - 1));
                },
                error: function(xhr) {
                    if (xhr.status === 401 || xhr.status === 403) window.location.reload();
                    btn.prop('disabled', false);
                }
            });
        });

        setInterval(function() {
            $.ajax({
                url: "<?= base_url('control_tower/get_live_data'); ?>",
                type: "GET",
                dataType: "json",
                success: function(data) {
                    $('#kpiTat').text(data.kpi.avg_tat);
                    $('#kpiTatDetail').text('Antre ' + data.kpi.avg_wait + ' mnt \u00b7 Layanan ' + data.kpi.avg_service + ' mnt \u00b7 ' + data.kpi.completed_today + ' truk selesai');
                    $('#kpiWaiting').text(data.kpi.truck_waiting);
                    $('#kpiExpired').text(data.kpi.truck_expired);
                    $('#kpiInYard').text(data.kpi.truck_in_yard);
                    $('#kpiGateIn').text(data.kpi.gate_in_last_hour);
                    renderQueues(data.queues);
                    renderHeatmap(data.heatmap);
                    renderSos(data.sos_reports);
                },
                error: function(xhr) {
                    if (xhr.status === 401) window.location.reload();
                }
            });
        }, 5000);
    </script>
</body>
</html>