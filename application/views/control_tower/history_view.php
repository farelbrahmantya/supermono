<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Trucking - Control Tower LASMONO</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php $this->load->view('partials/theme'); ?>
    <style>
        .card-kpi { background: #fff; border: 1px solid var(--lx-border); border-radius: 20px; padding: 20px; box-shadow: var(--lx-shadow); height: 100%; }
        .card-section { background: #fff; border: 1px solid var(--lx-border); border-radius: 22px; padding: 24px; box-shadow: var(--lx-shadow); }
        .table-custom th { background: #f8fafc; color: var(--lx-muted); font-size: 11px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; border-bottom: 1px solid var(--lx-border); white-space: nowrap; padding: 12px 10px; }
        .table-custom td { padding: 12px 10px; font-size: 13px; vertical-align: middle; white-space: nowrap; border-color: #f1f5f9; }
        .table-custom tbody tr:hover td { background: #fffaf0; }
        .stage-ok { display: inline-block; min-width: 42px; text-align: center; padding: 3px 8px; border-radius: 8px; background: #dcfce7; color: #047857; font-weight: 700; }
        .stage-late { display: inline-block; min-width: 42px; text-align: center; padding: 3px 8px; border-radius: 8px; background: #fee2e2; color: #b91c1c; font-weight: 700; }
        .quick a { border-radius: 50px; }
        .page-title h4 { font-weight: 800; }
        @media print { .lx-topbar, form.card-section { display: none !important; } body { background: #fff; } }
    </style>
</head>
<body>
    <?php
        $target = MY_Controller::STAGE_KPI_MINUTES;
        // Tampilkan durasi tahap dengan warna sesuai target KPI
        $stage_cell = function ($minutes, $limit) {
            if ($minutes === NULL) {
                return '<span class="text-muted">-</span>';
            }
            return '<span class="' . ($minutes <= $limit ? 'stage-ok' : 'stage-late') . '">' . $minutes . '</span>';
        };
        $time_only = function ($datetime) {
            return $datetime ? date('d/m H:i', strtotime($datetime)) : '-';
        };
    ?>

    <!-- Top Header -->
    <header class="lx-topbar d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center gap-3">
            <?php $this->load->view('partials/logo', array('size' => 44)); ?>
            <div>
                <div class="lx-brand fs-5">Riwayat Trucking</div>
                <div class="lx-sub">Control Tower LASMONO &middot; truk yang sudah Gate Out</div>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="<?= base_url('control_tower'); ?>" class="btn btn-sm fw-semibold" style="background: rgba(255,255,255,.08); color: #fff; border: 1px solid rgba(255,255,255,.14); padding: 8px 14px;"><i class="fas fa-arrow-left me-1"></i> Dashboard</a>
            <button type="button" onclick="window.print()" class="btn btn-sm fw-semibold d-none d-md-inline-block" style="background: rgba(255,255,255,.08); color: #fff; border: 1px solid rgba(255,255,255,.14); padding: 8px 14px;"><i class="fas fa-print me-1"></i> Cetak</button>
            <span class="lx-user d-none d-md-inline-flex">
                <i class="fas fa-user-tie" style="color: #f6c64a;"></i> <?= html_escape($admin_name); ?>
                <a href="<?= base_url('auth/logout'); ?>" class="lx-icon-btn" title="Keluar"><i class="fas fa-arrow-right-from-bracket"></i></a>
            </span>
        </div>
    </header>

    <div class="container-fluid px-4 pb-5">
        <!-- Filter Rentang Waktu (berdasarkan waktu Gate Out) -->
        <form method="GET" action="<?= base_url('control_tower/history'); ?>" class="card-section mb-4">
            <div class="row g-3 align-items-end">
                <div class="col-sm-6 col-md-3">
                    <label class="lx-eyebrow mb-1 d-block">Dari tanggal (Gate Out)</label>
                    <input type="date" name="from" class="form-control" value="<?= html_escape($date_from); ?>" required>
                </div>
                <div class="col-sm-6 col-md-3">
                    <label class="lx-eyebrow mb-1 d-block">Sampai tanggal</label>
                    <input type="date" name="to" class="form-control" value="<?= html_escape($date_to); ?>" required>
                </div>
                <div class="col-md-6 d-flex flex-wrap gap-2 quick">
                    <button type="submit" class="btn btn-primary fw-bold"><i class="fas fa-search me-1"></i> Tampilkan</button>
                    <a href="<?= base_url('control_tower/history?from=' . date('Y-m-d') . '&to=' . date('Y-m-d')); ?>" class="btn btn-outline-secondary">Hari Ini</a>
                    <a href="<?= base_url('control_tower/history?from=' . date('Y-m-d', strtotime('-6 days')) . '&to=' . date('Y-m-d')); ?>" class="btn btn-outline-secondary">7 Hari</a>
                    <a href="<?= base_url('control_tower/history?from=' . date('Y-m-01') . '&to=' . date('Y-m-d')); ?>" class="btn btn-outline-secondary">Bulan Ini</a>
                    <?php if (!$error && !empty($rows)): ?>
                        <a href="<?= html_escape(base_url('control_tower/history_csv?from=' . $date_from . '&to=' . $date_to)); ?>" class="btn btn-success fw-bold"><i class="fas fa-file-excel me-1"></i> Export Excel (CSV)</a>
                    <?php endif; ?>
                </div>
            </div>
        </form>

        <?php if (!empty($include_demo)): ?>
            <div class="alert alert-warning small"><i class="fas fa-flask me-1"></i> Data <b>Mode Demo</b> (simulasi) ikut ditampilkan dan ditandai <span class="badge bg-warning text-dark">DEMO</span>. Hapus data demo dari dashboard Control Tower sebelum dipakai untuk laporan resmi.</div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= html_escape($error); ?></div>
        <?php else: ?>

        <!-- Ringkasan KPI rentang terpilih -->
        <div class="row g-3 mb-4">
            <div class="col-md-6 col-xl">
                <div class="card-kpi">
                    <small class="text-muted d-block mb-1">Truk Selesai</small>
                    <h3 class="fw-bold mb-0"><?= $summary['total']; ?> <small class="fs-6 text-muted">truk</small></h3>
                    <small class="text-muted"><?= $summary['all_achieved']; ?> truk capai KPI di semua tahap</small>
                </div>
            </div>
            <?php foreach ($summary['stages'] as $stage): ?>
                <div class="col-md-6 col-xl">
                    <div class="card-kpi<?= $stage['percent'] !== NULL && $stage['percent'] < 80 ? ' border-start border-4 border-danger' : ''; ?>">
                        <small class="text-muted d-block mb-1"><?= html_escape($stage['label']); ?><?php if ($stage['target'] !== NULL): ?> <span class="opacity-75">(&le; <?= $stage['target']; ?> mnt)</span><?php endif; ?></small>
                        <h3 class="fw-bold mb-0 <?= $stage['percent'] === NULL ? 'text-muted' : ($stage['percent'] >= 80 ? 'text-success' : 'text-danger'); ?>">
                            <?= $stage['percent'] === NULL ? '-' : $stage['percent'] . '%'; ?>
                            <small class="fs-6 text-muted">capai target</small>
                        </h3>
                        <small class="text-muted"><?= $stage['avg'] === NULL ? 'Belum ada data' : 'Rata-rata ' . $stage['avg'] . ' mnt &middot; ' . $stage['count'] . ' truk'; ?></small>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Tabel Riwayat -->
        <div class="card-section">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0">Daftar Truk Selesai</h5>
                <small class="text-muted">
                    <?= date('d/m/Y', strtotime($date_from)); ?> &ndash; <?= date('d/m/Y', strtotime($date_to)); ?> &middot;
                    durasi dalam menit, <span class="stage-ok">hijau</span> = capai target, <span class="stage-late">merah</span> = lewat target
                </small>
            </div>
            <div class="table-responsive">
                <table class="table table-custom mb-0">
                    <thead>
                        <tr>
                            <th>Gate Out</th>
                            <th>No. Antrean</th>
                            <th>Aktivitas</th>
                            <th>Nopol</th>
                            <th>Container</th>
                            <th>Principal</th>
                            <th>Size</th>
                            <th>Customer</th>
                            <th>Depo / Blok</th>
                            <th>Antre</th>
                            <th>Gate In</th>
                            <th>Survey</th>
                            <th>Lift Off</th>
                            <th>Muat</th>
                            <th>Gate Out</th>
                            <th>Total</th>
                            <th>Petugas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($rows)): ?>
                            <tr><td colspan="17" class="text-center text-muted py-4">Tidak ada truk yang selesai pada rentang tanggal ini.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($rows as $r): ?>
                            <tr>
                                <td><?= $time_only($r['gate_out_at']); ?></td>
                                <td class="fw-bold"><?= html_escape($r['queue_number']); ?><?php if (!empty($r['is_demo'])): ?> <span class="badge bg-warning text-dark">DEMO</span><?php endif; ?></td>
                                <td><span class="act-tag act-<?= html_escape($r['activity']); ?>"><?= html_escape($r['activity_label']); ?></span></td>
                                <td class="fw-semibold"><?= html_escape($r['license_plate']); ?></td>
                                <td><?= html_escape($r['container_no'] ? $r['container_no'] : '-'); ?></td>
                                <td><?= html_escape($r['principal'] ? $r['principal'] : '-'); ?></td>
                                <td><?= html_escape($r['container_size'] ? $r['container_size'] : '-'); ?></td>
                                <td><?= html_escape($r['customer'] ? $r['customer'] : '-'); ?></td>
                                <td><?= $r['zone_code'] ? html_escape($r['depo'] . ' / ' . $r['zone_code']) : '-'; ?></td>
                                <td class="text-muted"><?= $r['wait_minutes'] === NULL ? '-' : $r['wait_minutes']; ?></td>
                                <td><?= $stage_cell($r['stage_gate_in'], $target); ?></td>
                                <td><?= $stage_cell($r['stage_survey'], $target); ?></td>
                                <td><?= $stage_cell($r['stage_lift_off'], MY_Controller::LIFT_OFF_KPI_MINUTES); ?></td>
                                <td><?= $stage_cell($r['stage_muat'], $r['muat_target']); ?></td>
                                <td><?= $stage_cell($r['stage_gate_out'], $target); ?></td>
                                <td><?= $stage_cell($r['total_minutes'], $r['total_target']); ?></td>
                                <td>
                                    <small class="text-muted" title="Gate In / Yardman / Gate Out">
                                        <?= html_escape(($r['gate_in_name'] ? $r['gate_in_name'] : '-') . ' / ' . ($r['yard_scan_name'] ? $r['yard_scan_name'] : '-') . ' / ' . ($r['gate_out_name'] ? $r['gate_out_name'] : '-')); ?>
                                    </small>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
