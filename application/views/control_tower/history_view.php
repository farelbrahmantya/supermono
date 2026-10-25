<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Trucking - Control Tower LASMONO</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f4f6f9; color: #2b2d42; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        .navbar-custom { background-color: #0b1e38; padding: 15px 30px; }
        .card-kpi { background: #ffffff; border-radius: 12px; border: none; padding: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); height: 100%; }
        .card-section { background: #ffffff; border-radius: 12px; border: none; padding: 24px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); }
        .table-custom th { background-color: #f8f9fa; color: #6c757d; font-size: 12px; font-weight: 600; border-bottom: 2px solid #edf2f7; white-space: nowrap; }
        .table-custom td { padding: 10px 8px; font-size: 13px; color: #2d3748; vertical-align: middle; white-space: nowrap; }
        .stage-ok { color: #16a34a; font-weight: 700; }
        .stage-late { color: #dc2626; font-weight: 700; }
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
    <div class="navbar-custom d-flex justify-content-between align-items-center text-white mb-4">
        <div class="d-flex align-items-center">
            <div class="bg-white text-dark fw-bold rounded p-2 me-3" style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; font-size: 12px; color: #0b1e38 !important;">LAS</div>
            <h4 class="fw-bold mb-0">Riwayat Trucking Selesai</h4>
        </div>
        <div class="d-flex align-items-center">
            <a href="<?= base_url('control_tower'); ?>" class="btn btn-outline-light btn-sm me-3 fw-bold"><i class="fas fa-arrow-left me-1"></i> Dashboard</a>
            <span class="me-3 text-light opacity-75 small">Admin: <?= html_escape($admin_name); ?></span>
            <a href="<?= base_url('auth/logout'); ?>" class="btn btn-outline-light btn-sm"><i class="fas fa-sign-out-alt"></i></a>
        </div>
    </div>

    <div class="container-fluid px-4 pb-5">
        <!-- Filter Rentang Waktu (berdasarkan waktu Gate Out) -->
        <form method="GET" action="<?= base_url('control_tower/history'); ?>" class="card-section mb-4">
            <div class="row g-3 align-items-end">
                <div class="col-sm-6 col-md-3">
                    <label class="form-label small fw-bold text-muted mb-1">DARI TANGGAL (GATE OUT)</label>
                    <input type="date" name="from" class="form-control" value="<?= html_escape($date_from); ?>" required>
                </div>
                <div class="col-sm-6 col-md-3">
                    <label class="form-label small fw-bold text-muted mb-1">SAMPAI TANGGAL</label>
                    <input type="date" name="to" class="form-control" value="<?= html_escape($date_to); ?>" required>
                </div>
                <div class="col-md-6 d-flex flex-wrap gap-2">
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
                        <small class="text-muted d-block mb-1"><?= html_escape($stage['label']); ?> <span class="opacity-75">(&le; <?= $stage['target']; ?> mnt)</span></small>
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
                <h5 class="fw-bold mb-0" style="color: #0f172a;">Daftar Truk Selesai</h5>
                <small class="text-muted">
                    <?= date('d/m/Y', strtotime($date_from)); ?> &ndash; <?= date('d/m/Y', strtotime($date_to)); ?> &middot;
                    durasi dalam menit, <span class="stage-ok">hijau</span> = capai target, <span class="stage-late">merah</span> = lewat target
                </small>
            </div>
            <div class="table-responsive">
                <table class="table table-custom table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Gate Out</th>
                            <th>No. Antrean</th>
                            <th>Nopol</th>
                            <th>Container</th>
                            <th>Principal</th>
                            <th>Size</th>
                            <th>Customer</th>
                            <th>Depo / Blok</th>
                            <th>Antre</th>
                            <th>Gate In</th>
                            <th>Survey</th>
                            <th>Gate Out</th>
                            <th>Total</th>
                            <th>Petugas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($rows)): ?>
                            <tr><td colspan="14" class="text-center text-muted py-4">Tidak ada truk yang selesai pada rentang tanggal ini.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($rows as $r): ?>
                            <tr>
                                <td><?= $time_only($r['gate_out_at']); ?></td>
                                <td class="fw-bold"><?= html_escape($r['queue_number']); ?></td>
                                <td class="fw-semibold"><?= html_escape($r['license_plate']); ?></td>
                                <td><?= html_escape($r['container_no'] ? $r['container_no'] : '-'); ?></td>
                                <td><?= html_escape($r['principal'] ? $r['principal'] : '-'); ?></td>
                                <td><?= html_escape($r['container_size'] ? $r['container_size'] : '-'); ?></td>
                                <td><?= html_escape($r['customer'] ? $r['customer'] : '-'); ?></td>
                                <td><?= $r['zone_code'] ? html_escape($r['depo'] . ' / ' . $r['zone_code']) : '-'; ?></td>
                                <td class="text-muted"><?= $r['wait_minutes'] === NULL ? '-' : $r['wait_minutes']; ?></td>
                                <td><?= $stage_cell($r['stage_gate_in'], $target); ?></td>
                                <td><?= $stage_cell($r['stage_survey'], $target); ?></td>
                                <td><?= $stage_cell($r['stage_gate_out'], $target); ?></td>
                                <td><?= $stage_cell($r['total_minutes'], $target * 3); ?></td>
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
