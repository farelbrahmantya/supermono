<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control Tower LASMONO - Depo MTKI</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <?php $this->load->view('partials/theme'); ?>
    <style>
        /* Control Tower tema terang Samudera (senada halaman Riwayat) */
        .section-label { font-size: 11px; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; color: var(--lx-muted); margin-bottom: 12px; }
        .section-label small { text-transform: none; letter-spacing: 0; font-weight: 500; }

        .card-kpi, .card-section, .stage-card { background: #fff; border: 1px solid var(--lx-border); border-radius: 20px; box-shadow: var(--lx-shadow); }
        .card-kpi { padding: 20px; height: 100%; }
        .card-kpi h2, .card-kpi h3 { font-variant-numeric: tabular-nums; }
        .card-section { padding: 22px; }
        .icon-kpi-wrapper { width: 46px; height: 46px; border-radius: 14px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }

        .stage-card { padding: 16px 18px; cursor: pointer; transition: all .15s ease; height: 100%; }
        .stage-card:hover { border-color: #f1d48a; transform: translateY(-2px); }
        .stage-card.active { border-color: var(--lx-primary); background: #fffbf0; box-shadow: 0 0 0 3px rgba(229, 167, 32, .2), var(--lx-shadow); }
        .stage-card.has-overdue { box-shadow: inset 4px 0 0 var(--lx-red), var(--lx-shadow); }
        .stage-late { color: var(--lx-red) !important; font-weight: 700; }

        /* Heatmap: warna isi = traffic truk, garis kiri = warna principal */
        .grid-density { display: grid; grid-template-columns: repeat(5, 1fr); gap: 6px; }
        .density-box { position: relative; padding: 10px 4px 9px 8px; border-radius: 10px; text-align: center; font-weight: 700; font-size: 12px; border: 1px solid transparent; border-left-width: 5px; line-height: 1.15; }
        .density-box small { display: block; font-size: 10px; font-weight: 600; opacity: .8; }
        .density-low { background: #ecfdf5; color: #047857; }
        .density-medium { background: #fff4d6; color: #92400e; }
        .density-high { background: #fee2e2; color: #b91c1c; animation: lx-pulse 1.6s infinite; }
        .principal-legend span { display: inline-flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 600; color: #3b3f47; }
        .principal-legend i { width: 14px; height: 14px; border-radius: 4px; display: inline-block; }

        .demo-banner { background: #fff6dc; border: 1px solid #f6e2a8; color: #7a5100; border-radius: 14px; padding: 10px 16px; font-size: 13px; }
        .btn-ct.demo-on { background: var(--lx-grad) !important; color: var(--lx-ink) !important; }

        /* Tab & filter aktivitas */
        .ct-tabs { gap: 6px; border-bottom: 1px solid var(--lx-border); }
        .ct-tabs .nav-link { color: var(--lx-muted); font-weight: 700; border: none; border-bottom: 3px solid transparent; border-radius: 0; padding: 10px 14px; }
        .ct-tabs .nav-link.active { color: var(--lx-text); border-bottom-color: var(--lx-primary); background: transparent; }
        .activity-filter button { border: 1px solid var(--lx-border); background: #fff; border-radius: 50px; padding: 5px 14px; font-size: 13px; font-weight: 600; color: #4b5059; margin-right: 4px; }
        .activity-filter button.active { background: var(--lx-ink); border-color: var(--lx-ink); color: #fff; }
        .yard-wrap { max-width: 760px; }
        .btn-tv { background: var(--lx-grad); color: var(--lx-ink); border: none; }

        /* Tabel antrean */
        .table-custom { margin: 0; }
        .table-custom th { background: #f8f7f3; color: var(--lx-muted); font-size: 11px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; border-bottom: 1px solid var(--lx-border); white-space: nowrap; }
        .table-custom td { padding: 14px 10px; font-size: 14px; vertical-align: middle; border-color: #f1eee6; }
        .table-custom tbody tr:hover td { background: #fffaf0; }
        .badge-pill-status { border-radius: 50px; padding: 6px 12px; font-weight: 700; font-size: 12px; }
        .badge-yard { background: #fff3d1; color: #8a5a00; }
        .badge-waiting { background: #f1f2f4; color: #4b5059; }
        /* Waktu vs target: hijau aman, kuning >= 80% target, merah lewat target */
        .sla-chip { display: inline-block; min-width: 74px; text-align: center; padding: 4px 10px; border-radius: 8px; font-size: 12.5px; font-weight: 700; white-space: nowrap; }
        .sla-ok { background: #dcfce7; color: #047857; }
        .sla-warning { background: #fef3c7; color: #b45309; }
        .sla-over { background: var(--lx-red); color: #fff; animation: lx-pulse 1.5s infinite; }
        .sla-none { background: #f1f2f4; color: #4b5059; }
        tr.row-over td { background: #fff5f5; }
        tr.row-over td:first-child { box-shadow: inset 4px 0 0 var(--lx-red); }
        tr.row-warning td:first-child { box-shadow: inset 4px 0 0 #f59e0b; }
        .sla-legend { font-size: 12px; color: var(--lx-muted); }
        .sla-legend i { display: inline-block; width: 10px; height: 10px; border-radius: 3px; margin: 0 4px 0 10px; vertical-align: -1px; }
        .badge-over-sla { background: var(--lx-red); color: #fff; animation: lx-pulse 1.5s infinite; }

        /* Tombol header */
        .btn-ct { background: rgba(255, 255, 255, .08); border: 1px solid rgba(255, 255, 255, .14); color: #fff; font-size: 13px; padding: 8px 14px; border-radius: 12px; }
        .btn-ct:hover { background: rgba(255, 255, 255, .16); color: #fff; }
        #sosButton { border-radius: 12px; font-size: 13px; padding: 8px 14px; background: linear-gradient(135deg, #e5393e, #c4161c); border: none; }
        .sos-active { animation: lx-pulse 1s infinite; box-shadow: 0 0 0 4px rgba(215, 42, 47, .35); }
        .live-dot { width: 8px; height: 8px; border-radius: 50%; background: #34d399; box-shadow: 0 0 0 4px rgba(52, 211, 153, .18); animation: lx-pulse 1.6s infinite; display: inline-block; }
        .clock { font-variant-numeric: tabular-nums; font-weight: 700; color: #fff; }
        #sosTableBody td, #queueTableBody td { white-space: nowrap; }
        #queueTableBody td:nth-child(4), #sosTableBody td:nth-child(5) { white-space: normal; }
        #sosTableBody td:nth-child(5) { min-width: 220px; }
        /* Isi pop up SOS selalu teks gelap di atas latar terang */
        #sosAlertModal .modal-body, #sosModal .modal-body { color: var(--lx-text); background: #fff; }
        #sosAlertDesc { background: #f6f5f2 !important; color: var(--lx-text) !important; border: 1px solid var(--lx-border); }
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
            <a href="<?= html_escape($tv_url); ?>" target="_blank" rel="noopener" class="btn btn-tv fw-bold" title="Buka di browser TV, tanpa login"><i class="fas fa-tv me-1"></i> Layar TV</a>
            <a href="<?= base_url('control_tower/impact'); ?>" class="btn btn-ct fw-semibold"><i class="fas fa-chart-line me-1"></i> Dampak</a>
            <?php if ($demo_ready): ?>
            <div class="dropdown">
                <button class="btn btn-ct fw-semibold dropdown-toggle<?= $demo_active ? ' demo-on' : ''; ?>" type="button" data-bs-toggle="dropdown"><i class="fas fa-flask me-1"></i> Demo</button>
                <div class="dropdown-menu dropdown-menu-end p-2" style="min-width: 260px;">
                    <small class="text-muted d-block px-2 mb-2">Truk simulasi bergerak otomatis untuk presentasi. Tidak menyentuh database depo.</small>
                    <form method="POST" action="<?= base_url('control_tower/demo_start'); ?>" onsubmit="return confirm('Mulai Mode Demo? Data demo lama akan diganti.');">
                        <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
                        <button class="dropdown-item rounded"><i class="fas fa-play me-2 text-success"></i><?= $demo_active ? 'Mulai ulang demo' : 'Mulai Mode Demo'; ?></button>
                    </form>
                    <?php if ($demo_active): ?>
                    <form method="POST" action="<?= base_url('control_tower/demo_stop'); ?>">
                        <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
                        <button class="dropdown-item rounded"><i class="fas fa-pause me-2 text-warning"></i>Hentikan truk baru</button>
                    </form>
                    <?php endif; ?>
                    <?php if ($demo_has_data): ?>
                    <form method="POST" action="<?= base_url('control_tower/demo_clear'); ?>" onsubmit="return confirm('Hapus semua data demo?');">
                        <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
                        <button class="dropdown-item rounded text-danger"><i class="fas fa-trash me-2"></i>Hapus semua data demo</button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            <a href="<?= base_url('control_tower/history'); ?>" class="btn btn-ct fw-semibold"><i class="fas fa-clock-rotate-left me-1"></i> Riwayat</a>
            <a href="<?= base_url('yardman'); ?>" class="btn btn-ct fw-semibold"><i class="fas fa-qrcode me-1"></i> Scanner Yardman</a>
            <span class="lx-user">
                <i class="fas fa-user-tie" style="color: #f6c64a;"></i> <?= html_escape($admin_name); ?>
                <a href="<?= base_url('auth/logout'); ?>" class="lx-icon-btn" title="Keluar"><i class="fas fa-arrow-right-from-bracket"></i></a>
            </span>
        </div>
    </header>

    <div class="container-fluid px-4">
        <?php if ($demo_active || $demo_has_data): ?>
            <div class="demo-banner mb-3"><i class="fas fa-flask me-2"></i><b>Mode Demo <?= $demo_active ? 'aktif' : '(berhenti)'; ?></b> &middot; data di dashboard, Layar TV, Riwayat, dan Dampak termasuk truk simulasi (nomor antrean DM-). Hapus lewat menu Demo setelah presentasi.</div>
        <?php endif; ?>
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
                    <div class="icon-kpi-wrapper" style="background-color: #ffedd5; color: #ea580c;">
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
                    <div class="icon-kpi-wrapper" style="background-color: #fff3d1; color: #b7790a;">
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
                    <div class="icon-kpi-wrapper" style="background-color: #fef3c7; color: #d97706;">
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
                    <div class="icon-kpi-wrapper" style="background-color: #dcfce7; color: #059669;">
                        <i class="fas fa-door-open fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab: ringkas per kebutuhan (Live untuk operasional, KPI & Yard untuk evaluasi) -->
        <ul class="nav ct-tabs mb-3" id="ctTabs" role="tablist">
            <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabLive" type="button"><i class="fas fa-satellite-dish me-1"></i> Live Antrean</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabKpi" type="button"><i class="fas fa-gauge-high me-1"></i> KPI Tahap</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabYard" type="button"><i class="fas fa-border-all me-1"></i> Kepadatan Yard</button></li>
        </ul>
        <div class="tab-content pb-4">
        <div class="tab-pane fade show active" id="tabLive" role="tabpanel">
        <!-- Pending per Tahap: klik untuk menyaring tabel Live Antrean -->
        <div class="section-label">Pending per tahap <small>(klik untuk melihat detail)</small></div>
        <div class="row g-3 mb-4" id="stageCards">
            <div class="col-md-6 col-xl">
                <div class="stage-card" data-stage="antre" role="button" tabindex="0">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <small class="text-muted d-block mb-1">Antre Gate In</small>
                            <h3 class="fw-bold mb-0"><span class="stage-count">0</span> <small class="fs-6 text-muted">truk</small></h3>
                            <small class="stage-overdue text-muted">Antre di luar depo</small>
                        </div>
                        <div class="icon-kpi-wrapper" style="background-color: #f1f2f4; color: #4b5059;"><i class="fas fa-truck fa-lg"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl">
                <div class="stage-card" data-stage="gate_in" role="button" tabindex="0">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <small class="text-muted d-block mb-1">Tahap Gate In</small>
                            <h3 class="fw-bold mb-0"><span class="stage-count">0</span> <small class="fs-6 text-muted">truk</small></h3>
                            <small class="stage-overdue text-muted">Gate In &rarr; masuk survey</small>
                        </div>
                        <div class="icon-kpi-wrapper" style="background-color: #fff3d1; color: #b7790a;"><i class="fas fa-sign-in-alt fa-lg"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl">
                <div class="stage-card" data-stage="survey" role="button" tabindex="0">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <small class="text-muted d-block mb-1">Tahap Survey (At Yard)</small>
                            <h3 class="fw-bold mb-0"><span class="stage-count">0</span> <small class="fs-6 text-muted">truk</small></h3>
                            <small class="stage-overdue text-muted">Masuk &rarr; selesai survey</small>
                        </div>
                        <div class="icon-kpi-wrapper" style="background-color: #fef3c7; color: #d97706;"><i class="fas fa-search fa-lg"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl">
                <div class="stage-card" data-stage="lift_off" role="button" tabindex="0">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <small class="text-muted d-block mb-1">Tahap Lift Off (Repo In / Import)</small>
                            <h3 class="fw-bold mb-0"><span class="stage-count">0</span> <small class="fs-6 text-muted">truk</small></h3>
                            <small class="stage-overdue text-muted">Survey selesai &rarr; container diturunkan</small>
                        </div>
                        <div class="icon-kpi-wrapper" style="background-color: #ede4ff; color: #6d28d9;"><i class="fas fa-arrows-down-to-line fa-lg"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl">
                <div class="stage-card" data-stage="muat" role="button" tabindex="0">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <small class="text-muted d-block mb-1">Tahap Muat (Export / Repo Out)</small>
                            <h3 class="fw-bold mb-0"><span class="stage-count">0</span> <small class="fs-6 text-muted">truk</small></h3>
                            <small class="stage-overdue text-muted">Gate In &rarr; container dimuat</small>
                        </div>
                        <div class="icon-kpi-wrapper" style="background-color: #e0ecff; color: #1d4ed8;"><i class="fas fa-dolly fa-lg"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl">
                <div class="stage-card" data-stage="gate_out" role="button" tabindex="0">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <small class="text-muted d-block mb-1">Tahap Gate Out</small>
                            <h3 class="fw-bold mb-0"><span class="stage-count">0</span> <small class="fs-6 text-muted">truk</small></h3>
                            <small class="stage-overdue text-muted">Lift off / muat &rarr; Gate Out</small>
                        </div>
                        <div class="icon-kpi-wrapper" style="background-color: #dcfce7; color: #059669;"><i class="fas fa-sign-out-alt fa-lg"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="activity-filter mb-3" id="activityFilter">
            <span class="small text-muted fw-semibold me-1">Aktivitas:</span>
            <button type="button" class="active" data-act="">Semua</button>
            <button type="button" data-act="Z">Repo In</button>
            <button type="button" data-act="A">Import</button>
            <button type="button" data-act="D">Export</button>
            <button type="button" data-act="E">Repo Out</button>
        </div>
        <div class="card-section">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="fw-bold mb-0">Live Antrean (SLA Monitor)</h5>
                <span class="sla-legend d-none d-md-inline">Time vs target:<i class="sla-ok"></i>On track<i class="sla-warning"></i>&ge; 80%<i class="sla-over"></i>Over SLA</span>
                <span id="stageFilterBadge" class="badge bg-primary rounded-pill px-3 py-2" role="button" style="display:none;"></span>
            </div>
            <div class="table-responsive">
                <table class="table table-custom border-0">
                    <thead>
                        <tr>
                            <th>Queue No.</th>
                            <th>Activity</th>
                            <th>License Plate</th>
                            <th>Container</th>
                            <th>Status</th>
                            <th title="Time in the current stage / stage target">Stage Time</th>
                            <th title="Total time since Gate In / SLA target (queue time before Gate In)">Elapsed Time</th>
                            <th>Estimated Call</th>
                        </tr>
                    </thead>
                    <tbody id="queueTableBody">
                    </tbody>
                </table>
            </div>
        </div>
        </div>

        <div class="tab-pane fade" id="tabKpi" role="tabpanel">
        <!-- KPI per Tahap hari ini: proses mana yang tidak mencapai target -->
        <div class="section-label">KPI per tahap hari ini <small>(target <?= MY_Controller::STAGE_KPI_MINUTES; ?> menit per tahap, pilih container Export <?= MY_Controller::EXPORT_PICK_KPI_MINUTES; ?> menit, truk yang sudah Gate Out)</small></div>
        <div class="row g-3 mb-4" id="stageKpiCards"></div>

        <a href="<?= base_url('control_tower/history'); ?>" class="btn btn-outline-secondary btn-sm"><i class="fas fa-clock-rotate-left me-1"></i> Lihat riwayat &amp; export Excel</a>
        </div>

        <div class="tab-pane fade" id="tabYard" role="tabpanel">
        <div class="yard-wrap">
        <div class="card-section">
            <h5 class="fw-bold mb-1">Kepadatan Yard</h5>
            <div class="small text-muted mb-3">Traffic truk per blok &middot; angka = truk menuju / di blok</div>
            <div id="heatmapGrid"></div>
            <?php if (empty($heatmap)): ?>
                <small class="text-muted d-block">Belum ada data blok yard (tabel yard_zones kosong).</small>
            <?php endif; ?>
            <div class="mt-3 pt-3 border-top">
                <small class="fw-bold text-muted d-block mb-2">Principal (warna garis kiri blok)</small>
                <div class="principal-legend d-flex flex-wrap gap-3 mb-3" id="principalLegend"></div>
                <small class="fw-bold text-muted d-block mb-2">Traffic truk di jalan akses blok</small>
                <div class="d-flex flex-wrap align-items-center gap-3 small text-muted">
                    <span class="d-flex align-items-center"><span class="rounded me-1" style="width: 12px; height: 12px; background: #a7f3d0;"></span> Lancar (&lt; <?= Control_tower::TRAFFIC_MEDIUM; ?> truk)</span>
                    <span class="d-flex align-items-center"><span class="rounded me-1" style="width: 12px; height: 12px; background: #fcd34d;"></span> Ramai (<?= Control_tower::TRAFFIC_MEDIUM; ?>&ndash;<?= Control_tower::TRAFFIC_HIGH - 1; ?>)</span>
                    <span class="d-flex align-items-center"><span class="rounded me-1" style="width: 12px; height: 12px; background: #f87171;"></span> Padat (&ge; <?= Control_tower::TRAFFIC_HIGH; ?>)</span>
                </div>
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
                    <h5 class="modal-title fw-bold"><i class="fas fa-bullhorn me-2"></i> Laporan SOS</h5>
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
                            <?php $sos_labels = array('UNSAFE' => 'NEAR MISS / UNSAFE', 'PUNGLI' => 'PEMUNGUTAN LIAR', 'PENYEROBOTAN' => 'PENYEROBOTAN ANTREAN', 'LAINNYA' => 'LAINNYA'); ?>
                            <?php foreach($sos_reports as $sos): ?>
                            <tr>
                                <td><small><?= html_escape($sos->created_at); ?></small></td>
                                <td><b>#<?= html_escape($sos->queue_number); ?></b></td>
                                <td><?= html_escape($sos->license_plate); ?></td>
                                <td><span class="badge <?= $sos->report_type === 'UNSAFE' ? 'bg-warning text-dark' : 'bg-danger'; ?>"><?= html_escape(isset($sos_labels[$sos->report_type]) ? $sos_labels[$sos->report_type] : $sos->report_type); ?></span></td>
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

        // Label jenis laporan SOS
        const SOS_LABELS = { UNSAFE: 'NEAR MISS / UNSAFE', PUNGLI: 'PEMUNGUTAN LIAR', PENYEROBOTAN: 'PENYEROBOTAN ANTREAN', LAINNYA: 'LAINNYA' };
        function sosLabel(type) { return SOS_LABELS[type] || type; }
        function esc(value) {
            return $('<div>').text(value === null || value === undefined ? '' : String(value)).html();
        }

        const STAGE_KPI = <?= MY_Controller::STAGE_KPI_MINUTES; ?>;
        const EXPORT_KPI = <?= MY_Controller::EXPORT_PICK_KPI_MINUTES; ?>;
        const LIFT_OFF_KPI = <?= MY_Controller::LIFT_OFF_KPI_MINUTES; ?>;
        const STAGE_TITLES = { antre: 'Antre Gate In', gate_in: 'Tahap Gate In', survey: 'Tahap Survey', lift_off: 'Tahap Lift Off', muat: 'Tahap Muat', gate_out: 'Tahap Gate Out' };
        // Tahap pilih container (Export) & muat (Repo Out) digabung di kartu "Tahap Muat"
        function cardStage(q) { return (q.stage === 'pick' || q.stage === 'load') ? 'muat' : q.stage; }
        let activeStage = null;
        let lastQueues = [];

        function renderStageCards(queues) {
            $.each(STAGE_TITLES, function(stage) {
                let rows = queues.filter(function(q) { return cardStage(q) === stage; });
                let late = rows.filter(function(q) { return q.stage_overdue; }).length;
                let card = $('.stage-card[data-stage="' + stage + '"]');
                card.find('.stage-count').text(rows.length);
                card.toggleClass('has-overdue', late > 0);
                if (stage !== 'antre') {
                    card.find('.stage-overdue')
                        .toggleClass('stage-late', late > 0)
                        .text(late > 0 ? late + ' truk lewat target!' : (stage === 'muat' ? 'Dalam target (Export \u2264 ' + EXPORT_KPI + ', Repo Out \u2264 ' + STAGE_KPI + ' mnt)' : 'Semua dalam target \u2264 ' + (stage === 'lift_off' ? LIFT_OFF_KPI : STAGE_KPI) + ' menit'));
                }
            });
        }

        let activeAct = '';
        function renderQueues(queues) {
            lastQueues = queues;
            if (activeAct) queues = queues.filter(function(q) { return q.activity === activeAct; });
            renderStageCards(queues);

            let rows = activeStage ? queues.filter(function(q) { return cardStage(q) === activeStage; }) : queues;
            if (activeStage) {
                // Detail pending: yang paling lama menunggu di tahap ini ditaruh paling atas
                rows = rows.slice().sort(function(a, b) { return b.stage_minutes - a.stage_minutes; });
            }

            let html = '';
            function chip(minutes, target, level) {
                return '<span class="sla-chip sla-' + (level || 'none') + '">' + esc(minutes) + (target ? ' / ' + esc(target) : '') + ' min</span>';
            }
            rows.forEach(function(q) {
                let worst = (q.stage_level === 'over' || q.sla_level === 'over') ? 'over' : ((q.stage_level === 'warning' || q.sla_level === 'warning') ? 'warning' : '');
                html += '<tr class="' + (worst ? 'row-' + worst : '') + '">' +
                    '<td class="fw-bold">' + esc(q.queue_number) + '</td>' +
                    '<td><span class="act-tag act-' + esc(q.activity) + '">' + esc(q.activity_label) + '</span></td>' +
                    '<td class="fw-semibold">' + esc(q.license_plate) + '</td>' +
                    '<td>' + esc(q.container_no || '-') + '</td>' +
                    '<td><span class="badge badge-pill-status ' + esc(q.badge_class) + '">' + esc(q.status_label) + '</span></td>' +
                    '<td>' + chip(q.stage_minutes, q.stage_target, q.stage_level) + '</td>' +
                    '<td>' + chip(q.elapsed_minutes, q.sla_target, q.sla_level) + '</td>' +
                    '<td>' + (q.estimated_call === null ? '-' : esc(q.estimated_call) + ' min') + '</td>' +
                '</tr>';
            });
            if (!rows.length) {
                html = '<tr><td colspan="8" class="text-center text-muted py-4">' + (activeStage ? 'Tidak ada antrean pending di tahap ini.' : 'Tidak ada antrean aktif.') + '</td></tr>';
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
        $('#activityFilter').on('click', 'button', function() {
            activeAct = $(this).data('act');
            $('#activityFilter button').removeClass('active');
            $(this).addClass('active');
            renderQueues(lastQueues);
        });
        // Ingat tab terakhir di browser ini
        try {
            let savedTab = localStorage.getItem('lasmonoCtTab');
            if (savedTab && document.querySelector('#ctTabs [data-bs-target="' + savedTab + '"]')) {
                bootstrap.Tab.getOrCreateInstance(document.querySelector('#ctTabs [data-bs-target="' + savedTab + '"]')).show();
            }
        } catch (e) {}
        $('#ctTabs button').on('shown.bs.tab', function() { try { localStorage.setItem('lasmonoCtTab', $(this).data('bs-target')); } catch (e) {} });

        renderQueues(<?= json_encode($queues, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>);

        // Heatmap dikelompokkan per depo: warna isi = traffic, garis kiri = warna principal
        function renderHeatmap(heatmap) {
            let groups = {}, principals = {};
            $.each(heatmap, function(code, info) {
                let color = info.principal_color || '#cbd5e1';
                if (info.principal) principals[info.principal] = color;
                (groups[info.depo] = groups[info.depo] || []).push(
                    '<div class="density-box density-' + esc(info.level) + '" style="border-left-color: ' + esc(color) + ';" title="' + esc(info.principal || '-') + ' \u00b7 ' + esc(info.count) + ' truk">' +
                        esc(code) + '<small>' + (info.count ? esc(info.count) + ' truk' : '&nbsp;') + '</small></div>');
            });
            let html = '';
            $.each(groups, function(depo, boxes) {
                html += '<small class="fw-bold text-muted d-block mb-1">' + esc(depo) + '</small><div class="grid-density mb-3">' + boxes.join('') + '</div>';
            });
            $('#heatmapGrid').html(html);
            $('#principalLegend').html($.map(principals, function(color, name) {
                return '<span><i style="background: ' + esc(color) + ';"></i>' + esc(name) + '</span>';
            }).join(''));
        }
        renderHeatmap(<?= json_encode($heatmap, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>);

        function renderStageKpi(stages) {
            let html = '';
            stages.forEach(function(k) {
                let has = k.total > 0;
                let good = has && k.percent >= 80;
                let color = !has ? 'text-muted' : (good ? 'text-success' : 'text-danger');
                html += '<div class="col-md-6 col-xl"><div class="card-kpi' + (has && !good ? ' border-start border-4 border-danger' : '') + '">' +
                    '<small class="text-muted d-block mb-1">' + esc(k.label) + ' <span class="opacity-75">(\u2264 ' + esc(k.target) + ' mnt)</span></small>' +
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
            $('#sosAlertType').text(sosLabel(sos.report_type)).toggleClass('bg-warning text-dark', sos.report_type === 'UNSAFE').toggleClass('bg-danger', sos.report_type !== 'UNSAFE');
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
                    '<td><span class="badge ' + (sos.report_type === 'UNSAFE' ? 'bg-warning text-dark' : 'bg-danger') + '">' + esc(sosLabel(sos.report_type)) + '</span></td>' +
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