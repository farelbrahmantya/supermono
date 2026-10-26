<!DOCTYPE html>
<html lang="id" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control Tower LASMONO - Depo MTKI</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <?php $this->load->view('partials/theme'); ?>
    <style>
        /* Tema gelap "ruang kendali" untuk layar monitor Control Tower */
        :root { --ct-bg: #1c1d21; --ct-card: #24262c; --ct-card-2: #2a2c33; --ct-line: rgba(148, 163, 184, .12); --ct-text: #e2e8f0; --ct-muted: #8b9bb4; }
        body { background: var(--ct-bg); color: var(--ct-text); background-image: radial-gradient(circle at 10% -10%, rgba(229, 167, 32, .18), transparent 40%), radial-gradient(circle at 100% 0%, rgba(246, 198, 74, .14), transparent 35%); background-attachment: fixed; }
        .text-muted, .text-secondary { color: var(--ct-muted) !important; }
        .lx-topbar { background: rgba(11, 18, 32, .82); backdrop-filter: blur(12px); border-bottom: 1px solid var(--ct-line); }
        .section-label { font-size: 11px; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; color: var(--ct-muted); margin-bottom: 12px; }
        .section-label small { text-transform: none; letter-spacing: 0; font-weight: 500; }

        .card-kpi, .card-section, .stage-card { background: linear-gradient(180deg, var(--ct-card-2), var(--ct-card)); border: 1px solid var(--ct-line); border-radius: 20px; box-shadow: 0 10px 30px rgba(0, 0, 0, .25); }
        .card-kpi { padding: 20px; height: 100%; }
        .card-kpi h2, .card-kpi h3 { color: #fff; font-variant-numeric: tabular-nums; }
        .card-section { padding: 22px; }
        .card-section h5 { color: #fff !important; }
        .icon-kpi-wrapper { width: 46px; height: 46px; border-radius: 14px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }

        .stage-card { padding: 16px 18px; cursor: pointer; transition: all .15s ease; height: 100%; }
        .stage-card:hover { border-color: rgba(246, 198, 74, .45); transform: translateY(-2px); }
        .stage-card.active { border-color: #f6c64a; box-shadow: 0 0 0 3px rgba(246, 198, 74, .25), 0 10px 30px rgba(0, 0, 0, .25); }
        .stage-card.has-overdue { box-shadow: inset 4px 0 0 #ef4444, 0 10px 30px rgba(0, 0, 0, .25); }
        .stage-card h3 { color: #fff; }
        .stage-late { color: #f87171 !important; font-weight: 700; }

        /* Heatmap */
        .grid-density { display: grid; grid-template-columns: repeat(5, 1fr); gap: 6px; }
        .density-box { padding: 11px 4px; border-radius: 10px; text-align: center; font-weight: 700; font-size: 12px; border: 1px solid transparent; }
        .density-low { background: rgba(16, 185, 129, .12); color: #6ee7b7; border-color: rgba(16, 185, 129, .2); }
        .density-medium { background: rgba(245, 158, 11, .18); color: #fcd34d; border-color: rgba(245, 158, 11, .35); }
        .density-high { background: rgba(239, 68, 68, .85); color: #fff; box-shadow: 0 0 16px rgba(239, 68, 68, .45); }

        /* Tabel antrean */
        .table-custom { --bs-table-bg: transparent; margin: 0; }
        .table-custom th { background: transparent; color: var(--ct-muted); font-size: 11px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; border-bottom: 1px solid var(--ct-line); white-space: nowrap; }
        .table-custom td { padding: 14px 10px; font-size: 14px; color: var(--ct-text); vertical-align: middle; border-color: var(--ct-line); }
        .table-custom tbody tr:hover td { background: rgba(255, 255, 255, .02); }
        .badge-pill-status { border-radius: 50px; padding: 6px 12px; font-weight: 700; font-size: 12px; }
        .badge-yard { background: rgba(229, 167, 32, .18); color: #f6c64a; }
        .badge-waiting { background: rgba(245, 158, 11, .16); color: #fcd34d; }
        .badge-over-sla { background: rgba(239, 68, 68, .9); color: #fff; animation: lx-pulse 1.5s infinite; }
        .badge.bg-light { background: rgba(255, 255, 255, .08) !important; color: var(--ct-text) !important; border-color: var(--ct-line) !important; }

        /* Tombol header */
        .btn-ct { background: rgba(255, 255, 255, .06); border: 1px solid var(--ct-line); color: #e2e8f0; font-size: 13px; padding: 8px 14px; border-radius: 12px; }
        .btn-ct:hover { background: rgba(255, 255, 255, .12); color: #fff; }
        #sosButton { border-radius: 12px; font-size: 13px; padding: 8px 14px; background: linear-gradient(135deg, #ef4444, #dc2626); border: none; }
        .sos-active { animation: lx-pulse 1s infinite; box-shadow: 0 0 0 4px rgba(239, 68, 68, .35); }
        .live-dot { width: 8px; height: 8px; border-radius: 50%; background: #34d399; box-shadow: 0 0 0 4px rgba(52, 211, 153, .18); animation: lx-pulse 1.6s infinite; display: inline-block; }
        .modal-content { background: var(--ct-card); border: 1px solid var(--ct-line); }
        #sosTableBody td, #queueTableBody td { white-space: nowrap; }
        #queueTableBody td:nth-child(4) { white-space: normal; }
        #sosTableBody td:nth-child(5) { white-space: normal; min-width: 220px; }
        .clock { font-variant-numeric: tabular-nums; font-weight: 700; color: #fff; }
    </style>
</head>
<body>
    <!-- Top Header -->
    <header class="lx-topbar d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div class="d-flex align-items-center gap-3">
            <?php $this->load->view('partials/logo', array('size' => 44)); ?>
            <div>
                <div class="lx-brand fs-5">Control Tower <span style="color: #f6c64a;">LASMONO</span></div>
                <div class="lx-sub"><span class="live-dot me-1"></span> Live &middot; Depo Petikemas MTKI &middot; <span class="clock" id="ctClock"></span></div>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <button id="sosButton" class="btn btn-danger fw-bold" data-bs-toggle="modal" data-bs-target="#sosModal">
                <i class="fas fa-bell me-1"></i> SOS <span class="badge bg-white text-danger ms-1" id="sosCount"><?php $open_sos = 0; foreach ($sos_reports as $sos) { if (!$sos->is_resolved) $open_sos++; } echo $open_sos; ?></span>
            </button>
            <a href="<?= base_url('control_tower/history'); ?>" class="btn btn-ct fw-semibold"><i class="fas fa-clock-rotate-left me-1"></i> Riwayat</a>
            <a href="<?= base_url('yardman'); ?>" class="btn btn-ct fw-semibold"><i class="fas fa-qrcode me-1"></i> Scanner Yardman</a>
            <span class="lx-user">
                <i class="fas fa-user-tie" style="color: #f6c64a;"></i> <?= html_escape($admin_name); ?>
                <a href="<?= base_url('auth/logout'); ?>" class="lx-icon-btn" title="Keluar"><i class="fas fa-arrow-right-from-bracket"></i></a>
            </span>
        </div>
    </header>

    <div class="container-fluid px-4">
        <!-- KPI Cards -->
        <div class="section-label">Ringkasan Hari Ini</div>
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card-kpi d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-muted d-block mb-1">Rata-rata TAT (Turn Around Time)</small>
                        <h2 class="fw-bold mb-0"><span id="kpiTat"><?= $kpi['avg_tat']; ?></span> Menit</h2>
                        <small class="text-muted" id="kpiTatDetail">Antre <?= $kpi['avg_wait']; ?> mnt &middot; Layanan <?= $kpi['avg_service']; ?> mnt &middot; <?= $kpi['completed_today']; ?> truk selesai</small>
                    </div>
                    <div class="icon-kpi-wrapper" style="background-color: rgba(234, 88, 12, .16); color: #fdba74;">
                        <i class="fas fa-stopwatch fa-lg"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card-kpi d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-muted d-block mb-1">Truk Antre (Luar)</small>
                        <h2 class="fw-bold mb-0" id="kpiWaiting"><?= $kpi['truck_waiting']; ?></h2>
                        <small class="text-muted"><span id="kpiExpired"><?= $kpi['truck_expired']; ?></span> antrean kedaluwarsa</small>
                    </div>
                    <div class="icon-kpi-wrapper" style="background-color: rgba(229, 167, 32, .18); color: #f6c64a;">
                        <i class="fas fa-truck fa-lg"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card-kpi d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-muted d-block mb-1">Truk di Yard</small>
                        <h2 class="fw-bold mb-0" id="kpiInYard"><?= $kpi['truck_in_yard']; ?></h2>
                    </div>
                    <div class="icon-kpi-wrapper" style="background-color: rgba(245, 158, 11, .16); color: #fcd34d;">
                        <i class="fas fa-boxes-stacked fa-lg"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card-kpi d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-muted d-block mb-1">Gate In (1 Jam Terakhir)</small>
                        <h2 class="fw-bold mb-0"><span id="kpiGateIn"><?= $kpi['gate_in_last_hour']; ?></span> Truk</h2>
                    </div>
                    <div class="icon-kpi-wrapper" style="background-color: rgba(16, 185, 129, .16); color: #6ee7b7;">
                        <i class="fas fa-door-open fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- KPI per Tahap hari ini: proses mana yang tidak mencapai target -->
        <div class="section-label">KPI per tahap hari ini <small>(target <?= MY_Controller::STAGE_KPI_MINUTES; ?> menit per tahap, truk yang sudah Gate Out)</small></div>
        <div class="row g-3 mb-4" id="stageKpiCards"></div>

        <!-- Pending per Tahap: klik untuk menyaring tabel Live Antrean -->
        <div class="section-label">Pending per tahap <small>(klik untuk melihat detail)</small></div>
        <div class="row g-3 mb-4" id="stageCards">
            <div class="col-md-6 col-xl-3">
                <div class="stage-card" data-stage="antre" role="button" tabindex="0">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <small class="text-muted d-block mb-1">Antre Gate In</small>
                            <h3 class="fw-bold mb-0"><span class="stage-count">0</span> <small class="fs-6 text-muted">truk</small></h3>
                            <small class="stage-overdue text-muted">Antre di luar depo</small>
                        </div>
                        <div class="icon-kpi-wrapper" style="background-color: rgba(148, 163, 184, .16); color: #cbd5e1;"><i class="fas fa-truck fa-lg"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="stage-card" data-stage="gate_in" role="button" tabindex="0">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <small class="text-muted d-block mb-1">Tahap Gate In</small>
                            <h3 class="fw-bold mb-0"><span class="stage-count">0</span> <small class="fs-6 text-muted">truk</small></h3>
                            <small class="stage-overdue text-muted">Gate In &rarr; masuk survey</small>
                        </div>
                        <div class="icon-kpi-wrapper" style="background-color: rgba(229, 167, 32, .18); color: #f6c64a;"><i class="fas fa-sign-in-alt fa-lg"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="stage-card" data-stage="survey" role="button" tabindex="0">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <small class="text-muted d-block mb-1">Tahap Survey (At Yard)</small>
                            <h3 class="fw-bold mb-0"><span class="stage-count">0</span> <small class="fs-6 text-muted">truk</small></h3>
                            <small class="stage-overdue text-muted">Masuk &rarr; selesai survey</small>
                        </div>
                        <div class="icon-kpi-wrapper" style="background-color: rgba(245, 158, 11, .16); color: #fcd34d;"><i class="fas fa-search fa-lg"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="stage-card" data-stage="gate_out" role="button" tabindex="0">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <small class="text-muted d-block mb-1">Tahap Gate Out</small>
                            <h3 class="fw-bold mb-0"><span class="stage-count">0</span> <small class="fs-6 text-muted">truk</small></h3>
                            <small class="stage-overdue text-muted">Selesai survey &rarr; Gate Out</small>
                        </div>
                        <div class="icon-kpi-wrapper" style="background-color: rgba(16, 185, 129, .16); color: #6ee7b7;"><i class="fas fa-sign-out-alt fa-lg"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="row g-4">
            <!-- Live Antrean (SLA Monitor) -->
            <div class="col-lg-8">
                <div class="card-section">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="fw-bold mb-0">Live Antrean (SLA Monitor)</h5>
                        <span id="stageFilterBadge" class="badge bg-primary rounded-pill px-3 py-2" role="button" style="display:none;"></span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-custom border-0">
                            <thead>
                                <tr>
                                    <th>Queue No.</th>
                                    <th>License Plate</th>
                                    <th>Container</th>
                                    <th>Status</th>
                                    <th>Di Tahap Ini</th>
                                    <th>Elapsed Time</th>
                                    <th>Estimated Call</th>
                                </tr>
                            </thead>
                            <tbody id="queueTableBody">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Kepadatan Yard (Heatmap Matrix) -->
            <div class="col-lg-4">
                <div class="card-section">
                    <h5 class="fw-bold mb-4">Kepadatan Yard</h5>
                    <div id="heatmapGrid"></div>
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

    <!-- Pop Up SOS Baru -->
    <div class="modal fade" id="sosAlertModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-0 text-white" style="background: linear-gradient(135deg, #ef4444, #b91c1c);">
                    <h5 class="modal-title fw-bold"><i class="fas fa-exclamation-triangle me-2"></i> SOS BARU MASUK!</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="badge bg-danger fs-6" id="sosAlertType"></span>
                        <small class="text-muted" id="sosAlertTime"></small>
                    </div>
                    <div class="mb-1"><b>No. Antrean:</b> #<span id="sosAlertQueue"></span></div>
                    <div class="mb-2"><b>Nopol:</b> <span id="sosAlertPlate"></span></div>
                    <div class="p-2 bg-light rounded" id="sosAlertDesc"></div>
                    <div class="mt-2" id="sosAlertMedia"></div>
                    <small class="text-muted d-block mt-2" id="sosAlertMore"></small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="button" class="btn btn-danger fw-bold" id="sosAlertOpenList">Lihat Semua SOS</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal SOS Reports -->
    <div class="modal fade" id="sosModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content border-0">
                <div class="modal-header border-0 text-white" style="background: linear-gradient(135deg, #ef4444, #b91c1c);">
                    <h5 class="modal-title fw-bold"><i class="fas fa-bullhorn me-2"></i> Laporan SOS Pungli & Penyerobotan</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="table-responsive">
                    <table class="table table-custom align-middle mb-0">
                        <thead>
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
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const MEDIA_BASE = "<?= base_url(); ?>";

        // Jam di header
        function tickClock() {
            let d = new Date();
            $('#ctClock').text(d.toLocaleDateString('id-ID', { weekday: 'short', day: '2-digit', month: 'short' }) + ' ' + d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }));
        }
        tickClock();
        setInterval(tickClock, 1000);
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

        const STAGE_KPI = <?= MY_Controller::STAGE_KPI_MINUTES; ?>;
        const STAGE_TITLES = { antre: 'Antre Gate In', gate_in: 'Tahap Gate In', survey: 'Tahap Survey', gate_out: 'Tahap Gate Out' };
        let activeStage = null;
        let lastQueues = [];

        function renderStageCards(queues) {
            $.each(STAGE_TITLES, function(stage) {
                let rows = queues.filter(function(q) { return q.stage === stage; });
                let late = rows.filter(function(q) { return q.stage_overdue; }).length;
                let card = $('.stage-card[data-stage="' + stage + '"]');
                card.find('.stage-count').text(rows.length);
                card.toggleClass('has-overdue', late > 0);
                if (stage !== 'antre') {
                    card.find('.stage-overdue')
                        .toggleClass('stage-late', late > 0)
                        .text(late > 0 ? late + ' truk lewat ' + STAGE_KPI + ' menit!' : 'Semua dalam target \u2264 ' + STAGE_KPI + ' menit');
                }
            });
        }

        function renderQueues(queues) {
            lastQueues = queues;
            renderStageCards(queues);

            let rows = activeStage ? queues.filter(function(q) { return q.stage === activeStage; }) : queues;
            if (activeStage) {
                // Detail pending: yang paling lama menunggu di tahap ini ditaruh paling atas
                rows = rows.slice().sort(function(a, b) { return b.stage_minutes - a.stage_minutes; });
            }

            let html = '';
            rows.forEach(function(q) {
                html += '<tr>' +
                    '<td class="fw-bold">' + esc(q.queue_number) + '</td>' +
                    '<td class="fw-semibold">' + esc(q.license_plate) + '</td>' +
                    '<td>' + esc(q.container_no || '-') + '</td>' +
                    '<td><span class="badge badge-pill-status ' + esc(q.badge_class) + '">' + esc(q.status_label) + '</span></td>' +
                    '<td><span class="badge ' + (q.stage_overdue ? 'bg-danger' : 'bg-light text-dark border') + '">' + esc(q.stage_minutes) + ' mnt</span></td>' +
                    '<td>' + esc(q.elapsed_minutes) + ' Menit</td>' +
                    '<td>' + (q.estimated_call === null ? '-' : esc(q.estimated_call) + ' Menit') + '</td>' +
                '</tr>';
            });
            if (!rows.length) {
                html = '<tr><td colspan="7" class="text-center text-muted py-4">' + (activeStage ? 'Tidak ada antrean pending di tahap ini.' : 'Tidak ada antrean aktif.') + '</td></tr>';
            }
            $('#queueTableBody').html(html);
        }

        function setStageFilter(stage) {
            activeStage = (activeStage === stage) ? null : stage;
            $('.stage-card').removeClass('active');
            if (activeStage) {
                $('.stage-card[data-stage="' + activeStage + '"]').addClass('active');
                $('#stageFilterBadge').text('Filter: ' + STAGE_TITLES[activeStage] + '  \u2715').show();
            } else {
                $('#stageFilterBadge').hide();
            }
            renderQueues(lastQueues);
        }

        $('.stage-card').on('click', function() { setStageFilter($(this).data('stage')); });
        $('.stage-card').on('keydown', function(e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); setStageFilter($(this).data('stage')); } });
        $('#stageFilterBadge').on('click', function() { setStageFilter(activeStage); });

        renderQueues(<?= json_encode($queues, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>);

        // Heatmap dikelompokkan per depo
        function renderHeatmap(heatmap) {
            let groups = {};
            $.each(heatmap, function(code, info) {
                (groups[info.depo] = groups[info.depo] || []).push('<div class="density-box density-' + esc(info.level) + '" title="' + esc(info.count) + '/' + esc(info.capacity) + ' truk">' + esc(code) + (info.count ? ' (' + esc(info.count) + ')' : '') + '</div>');
            });
            let html = '';
            $.each(groups, function(depo, boxes) {
                html += '<small class="fw-bold text-muted d-block mb-1">' + esc(depo) + '</small><div class="grid-density mb-3">' + boxes.join('') + '</div>';
            });
            $('#heatmapGrid').html(html);
        }
        renderHeatmap(<?= json_encode($heatmap, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>);

        function renderStageKpi(stages) {
            let html = '';
            stages.forEach(function(k) {
                let has = k.total > 0;
                let good = has && k.percent >= 80;
                let color = !has ? 'text-muted' : (good ? 'text-success' : 'text-danger');
                html += '<div class="col-md-4"><div class="card-kpi' + (has && !good ? ' border-start border-4 border-danger' : '') + '">' +
                    '<small class="text-muted d-block mb-1">' + esc(k.label) + '</small>' +
                    '<h3 class="fw-bold mb-0 ' + color + '">' + (has ? esc(k.percent) + '%' : '-') + ' <small class="fs-6 text-muted">capai target</small></h3>' +
                    '<small class="text-muted">' + (has ? 'Rata-rata ' + esc(k.avg_minutes) + ' mnt &middot; ' + esc(k.achieved) + '/' + esc(k.total) + ' truk' : 'Belum ada truk selesai hari ini') + '</small>' +
                '</div></div>';
            });
            $('#stageKpiCards').html(html);
        }
        renderStageKpi(<?= json_encode($stage_kpi, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>);

        // Pop up SOS: tampilkan laporan yang id-nya lebih baru dari yang sudah pernah terlihat
        let lastSosId = <?php $max_sos = 0; foreach ($sos_reports as $sos) { $max_sos = max($max_sos, (int) $sos->id); } echo $max_sos; ?>;

        function playAlarm() {
            try {
                let ctx = new (window.AudioContext || window.webkitAudioContext)();
                [0, 0.35, 0.7].forEach(function(t) {
                    let osc = ctx.createOscillator();
                    let gain = ctx.createGain();
                    osc.frequency.value = 880;
                    gain.gain.value = 0.15;
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.start(ctx.currentTime + t);
                    osc.stop(ctx.currentTime + t + 0.2);
                });
            } catch (e) {}
        }

        function showSosAlert(reports) {
            let fresh = reports.filter(function(s) { return parseInt(s.id, 10) > lastSosId && s.is_resolved == 0; });
            reports.forEach(function(s) { lastSosId = Math.max(lastSosId, parseInt(s.id, 10)); });
            if (!fresh.length) return;

            let sos = fresh[0];
            $('#sosAlertType').text(sos.report_type);
            $('#sosAlertTime').text(sos.created_at);
            $('#sosAlertQueue').text(sos.queue_number);
            $('#sosAlertPlate').text(sos.license_plate);
            $('#sosAlertDesc').text(sos.description);
            $('#sosAlertMedia').html(sos.media_path
                ? '<a href="' + esc(MEDIA_BASE + sos.media_path) + '" target="_blank" rel="noopener" class="btn btn-sm btn-outline-danger fw-bold"><i class="fas fa-paperclip me-1"></i> Lihat Bukti</a>'
                : '');
            $('#sosAlertMore').text(fresh.length > 1 ? '+' + (fresh.length - 1) + ' laporan SOS baru lainnya' : '');
            bootstrap.Modal.getOrCreateInstance(document.getElementById('sosAlertModal')).show();
            playAlarm();
        }

        $('#sosAlertOpenList').on('click', function() {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('sosAlertModal')).hide();
            bootstrap.Modal.getOrCreateInstance(document.getElementById('sosModal')).show();
        });

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
            $('#sosButton').toggleClass('sos-active', openCount > 0);
        }

        function resolveSos(btn, retried) {
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
                    if (xhr.status === 403 && !retried) {
                        refreshCsrf(function(ok) { ok ? resolveSos(btn, true) : btn.prop('disabled', false); });
                        return;
                    }
                    if (xhr.status === 401 || xhr.status === 403) window.location.reload();
                    btn.prop('disabled', false);
                }
            });
        }

        $(document).on('click', '.btn-resolve-sos', function() {
            let btn = $(this);
            btn.prop('disabled', true);
            resolveSos(btn, false);
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
                    renderStageKpi(data.stage_kpi);
                    renderSos(data.sos_reports);
                    showSosAlert(data.sos_reports);
                },
                error: function(xhr) {
                    if (xhr.status === 401) window.location.reload();
                }
            });
        }, 5000);
    </script>
</body>
</html>