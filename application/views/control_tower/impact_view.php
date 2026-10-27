<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dampak LASMONO - Control Tower</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php $this->load->view('partials/theme'); ?>
    <style>
        .card-section { background: #fff; border: 1px solid var(--lx-border); border-radius: 22px; padding: 24px; box-shadow: var(--lx-shadow); }
        .period-tag { display: inline-flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 700; padding: 4px 10px; border-radius: 50px; }
        .period-a { background: #f1f2f4; color: #4b5059; }
        .period-b { background: #fff3d1; color: var(--lx-gold-text); }
        .hero-saving { background: var(--lx-ink); color: #fff; border-radius: 22px; padding: 26px 28px; position: relative; overflow: hidden; }
        .hero-saving::after { content: ""; position: absolute; width: 260px; height: 260px; border-radius: 50%; background: #e5a720; filter: blur(80px); opacity: .35; right: -60px; top: -90px; }
        .hero-saving > * { position: relative; z-index: 1; }
        .hero-saving .big { font-family: var(--lx-font-display); font-size: 54px; line-height: 1; color: #f6c64a; }
        .metric { background: #fff; border: 1px solid var(--lx-border); border-radius: 20px; padding: 18px 20px; height: 100%; box-shadow: var(--lx-shadow); }
        .metric .label { color: var(--lx-muted); font-size: 13px; font-weight: 600; }
        .metric .vals { display: flex; align-items: baseline; gap: 10px; margin: 6px 0 4px; font-variant-numeric: tabular-nums; }
        .metric .from { color: #9aa0a9; font-size: 18px; font-weight: 700; text-decoration: line-through; text-decoration-color: rgba(0,0,0,.2); }
        .metric .to { font-size: 30px; font-weight: 800; }
        .delta { display: inline-flex; align-items: center; gap: 4px; font-size: 12px; font-weight: 800; padding: 3px 9px; border-radius: 50px; }
        .delta.good { background: #dcfce7; color: #047857; }
        .delta.bad { background: #fee2e2; color: #b91c1c; }
        .delta.flat { background: #f1f2f4; color: #4b5059; }
        .table-stage th { background: #f8f7f3; color: var(--lx-muted); font-size: 11px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; }
        .table-stage td { vertical-align: middle; font-variant-numeric: tabular-nums; }
        .chart { display: flex; align-items: flex-end; gap: 6px; height: 200px; padding-top: 18px; border-bottom: 1px solid var(--lx-border); }
        .chart .col-bar { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: flex-end; height: 100%; min-width: 0; }
        .chart .bar { width: 100%; max-width: 34px; border-radius: 8px 8px 3px 3px; position: relative; }
        .chart .bar.a { background: #cfd3d9; }
        .chart .bar.b { background: var(--lx-grad); }
        .chart .bar span { position: absolute; top: -18px; left: 50%; transform: translateX(-50%); font-size: 10px; font-weight: 700; color: #4b5059; white-space: nowrap; }
        .chart-labels { display: flex; gap: 6px; }
        .chart-labels div { flex: 1; text-align: center; font-size: 10px; color: var(--lx-muted); min-width: 0; overflow: hidden; }
        .chart-sep { width: 2px; background: repeating-linear-gradient(180deg, #d6d3cc 0 6px, transparent 6px 12px); align-self: stretch; }
        @media print { .lx-topbar, form.card-section, .no-print { display: none !important; } body { background: #fff; } }
    </style>
</head>
<body>
    <?php
        $fmt = function ($v, $suffix = '') {
            return $v === NULL ? '-' : str_replace('.', ',', (string) $v) . $suffix;
        };
        // Perubahan dari periode awal ke periode sekarang; $lower_better = nilai turun berarti membaik
        $delta = function ($a, $b, $lower_better, $unit = '%') {
            if ($a === NULL || $b === NULL) return '<span class="delta flat">-</span>';
            $diff = $b - $a;
            if (abs($diff) < 0.05) return '<span class="delta flat">tetap</span>';
            $good = $lower_better ? $diff < 0 : $diff > 0;
            if ($unit === '%' && $a != 0) {
                $text = round(abs($diff) / abs($a) * 100) . '%';
            } elseif ($unit === '%') {
                $text = str_replace('.', ',', (string) round(abs($diff), 1));
            } else {
                $text = str_replace('.', ',', (string) round(abs($diff), 1)) . ' ' . $unit;
            }
            return '<span class="delta ' . ($good ? 'good' : 'bad') . '"><i class="fas fa-arrow-' . ($diff < 0 ? 'down' : 'up') . '"></i> ' . $text . '</span>';
        };
        $range_text = function ($from, $to) {
            return date('d/m/Y', strtotime($from)) . ' &ndash; ' . date('d/m/Y', strtotime($to));
        };
        $qs = function ($af, $at, $bf, $bt) use ($include_demo) {
            return base_url('control_tower/impact?a_from=' . $af . '&a_to=' . $at . '&b_from=' . $bf . '&b_to=' . $bt . '&demo=' . ($include_demo ? '1' : '0'));
        };
        $first_this_month = date('Y-m-01');
        $first_last_month = date('Y-m-01', strtotime($first_this_month . ' -1 month'));
        $last_last_month  = date('Y-m-t', strtotime($first_last_month));
    ?>

    <header class="lx-topbar d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center gap-3">
            <?php $this->load->view('partials/logo', array('size' => 44)); ?>
            <div>
                <div class="lx-brand fs-5">Dampak LASMONO</div>
                <div class="lx-sub">Perbandingan kinerja depo antar periode</div>
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
        <form method="GET" action="<?= base_url('control_tower/impact'); ?>" class="card-section mb-4">
            <div class="row g-3 align-items-end">
                <div class="col-lg-4">
                    <span class="period-tag period-a mb-2"><i class="fas fa-circle" style="font-size: 7px;"></i> Periode awal (pembanding)</span>
                    <div class="d-flex gap-2">
                        <input type="date" name="a_from" class="form-control" value="<?= html_escape($a_from); ?>" required>
                        <input type="date" name="a_to" class="form-control" value="<?= html_escape($a_to); ?>" required>
                    </div>
                </div>
                <div class="col-lg-4">
                    <span class="period-tag period-b mb-2"><i class="fas fa-circle" style="font-size: 7px;"></i> Periode sekarang</span>
                    <div class="d-flex gap-2">
                        <input type="date" name="b_from" class="form-control" value="<?= html_escape($b_from); ?>" required>
                        <input type="date" name="b_to" class="form-control" value="<?= html_escape($b_to); ?>" required>
                    </div>
                </div>
                <div class="col-lg-4 d-flex flex-wrap gap-2 align-items-center">
                    <input type="hidden" name="demo" value="0">
                    <?php if ($demo_has_data): ?>
                        <label class="form-check-label small d-flex align-items-center gap-2 me-1"><input type="checkbox" class="form-check-input mt-0" name="demo" value="1" <?= $include_demo ? 'checked' : ''; ?>> Sertakan data demo</label>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-primary fw-bold"><i class="fas fa-scale-balanced me-1"></i> Bandingkan</button>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2 mt-3 small">
                <span class="text-muted">Cepat:</span>
                <a class="btn btn-sm btn-outline-secondary rounded-pill" href="<?= $qs(date('Y-m-d', strtotime('-13 days')), date('Y-m-d', strtotime('-7 days')), date('Y-m-d', strtotime('-6 days')), date('Y-m-d')); ?>">7 hari terakhir vs 7 hari sebelumnya</a>
                <a class="btn btn-sm btn-outline-secondary rounded-pill" href="<?= $qs($first_last_month, $last_last_month, $first_this_month, date('Y-m-d')); ?>">Bulan ini vs bulan lalu</a>
            </div>
        </form>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= html_escape($error); ?></div>
        <?php else: ?>
        <?php $a = $periods['a']; $b = $periods['b']; ?>

        <?php if ($include_demo): ?>
            <div class="alert alert-warning small no-print"><i class="fas fa-flask me-1"></i> Perbandingan ini <b>termasuk data Mode Demo (simulasi)</b>. Untuk angka resmi, hilangkan centang "Sertakan data demo".</div>
        <?php endif; ?>

        <?php if (!$a['trucks'] || !$b['trucks']): ?>
            <div class="alert alert-info">Belum cukup data: periode awal <?= $a['trucks']; ?> truk, periode sekarang <?= $b['trucks']; ?> truk. Pilih rentang tanggal lain, atau jalankan Mode Demo dari dashboard untuk simulasi.</div>
        <?php endif; ?>

        <!-- Sorotan utama -->
        <?php
            $saved_minutes = ($a['avg_tat'] !== NULL && $b['avg_tat'] !== NULL) ? ($a['avg_tat'] - $b['avg_tat']) * $b['trucks'] : NULL;
        ?>
        <div class="hero-saving mb-4">
            <div class="row align-items-center g-4">
                <div class="col-lg-5">
                    <div class="lx-eyebrow" style="color: #a9adb6;">Waktu truk yang dihemat (periode sekarang)</div>
                    <?php if ($saved_minutes !== NULL && $saved_minutes > 0): ?>
                        <div class="big my-2">&plusmn;<?= str_replace('.', ',', (string) round($saved_minutes / 60, 1)); ?> jam</div>
                        <div style="color: #cbd5e1;">TAT rata-rata turun <b style="color: #fff;"><?= $fmt(round($a['avg_tat'] - $b['avg_tat'], 1), ' menit'); ?></b> per truk &times; <?= $b['trucks']; ?> truk</div>
                    <?php elseif ($saved_minutes !== NULL): ?>
                        <div class="big my-2" style="color: #fca5a5;">Belum ada</div>
                        <div style="color: #cbd5e1;">TAT rata-rata periode sekarang belum lebih cepat dari periode awal.</div>
                    <?php else: ?>
                        <div class="big my-2">-</div>
                        <div style="color: #cbd5e1;">Data belum cukup untuk dihitung.</div>
                    <?php endif; ?>
                </div>
                <div class="col-lg-7">
                    <div class="row g-3 small" style="color: #cbd5e1;">
                        <div class="col-sm-6"><span class="period-tag period-a">Periode awal</span><div class="mt-1"><?= $range_text($a['from'], $a['to']); ?> &middot; <?= $a['trucks']; ?> truk</div></div>
                        <div class="col-sm-6"><span class="period-tag period-b">Periode sekarang</span><div class="mt-1"><?= $range_text($b['from'], $b['to']); ?> &middot; <?= $b['trucks']; ?> truk</div></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <?php
                $metrics = array(
                    array('Rata-rata TAT (daftar &rarr; Gate Out)', $a['avg_tat'], $b['avg_tat'], ' mnt', TRUE),
                    array('Rata-rata antre di luar', $a['avg_wait'], $b['avg_wait'], ' mnt', TRUE),
                    array('Rata-rata layanan di depo', $a['avg_service'], $b['avg_service'], ' mnt', TRUE),
                    array('Truk capai KPI semua tahap', $a['kpi_percent'], $b['kpi_percent'], '%', FALSE),
                    array('Truk dilayani per hari', $a['per_day'], $b['per_day'], '', FALSE),
                    array('Laporan SOS', $a['sos'], $b['sos'], '', TRUE)
                );
            ?>
            <?php foreach ($metrics as $m): ?>
                <div class="col-md-6 col-xl-4">
                    <div class="metric">
                        <div class="label"><?= $m[0]; ?></div>
                        <div class="vals"><span class="from"><?= $fmt($m[1], $m[3]); ?></span><i class="fas fa-arrow-right text-muted small"></i><span class="to"><?= $fmt($m[2], $m[3]); ?></span></div>
                        <?= $delta($m[1], $m[2], $m[4], $m[3] === '%' ? 'poin' : '%'); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="row g-4">
            <!-- Per tahap -->
            <div class="col-xl-6">
                <div class="card-section h-100">
                    <h5 class="fw-bold mb-3">Per tahap proses</h5>
                    <div class="table-responsive">
                        <table class="table table-stage mb-0">
                            <thead><tr><th>Tahap</th><th class="text-end">Awal</th><th class="text-end">Sekarang</th><th class="text-end">Perubahan</th></tr></thead>
                            <tbody>
                            <?php
                                $index = array();
                                foreach ($a['stages'] as $st) $index[$st['label']]['a'] = $st;
                                foreach ($b['stages'] as $st) $index[$st['label']]['b'] = $st;
                            ?>
                            <?php foreach ($index as $label => $pair): ?>
                                <?php $sa = isset($pair['a']) ? $pair['a'] : NULL; $sb = isset($pair['b']) ? $pair['b'] : NULL; ?>
                                <tr>
                                    <td class="fw-semibold"><?= html_escape($label); ?><?php if (($sa && $sa['target']) || ($sb && $sb['target'])): ?> <small class="text-muted">(&le; <?= $sa ? $sa['target'] : $sb['target']; ?> mnt)</small><?php endif; ?></td>
                                    <td class="text-end"><?= $sa ? $fmt($sa['avg'], ' mnt') : '-'; ?><br><small class="text-muted"><?= $sa && $sa['percent'] !== NULL ? $sa['percent'] . '% capai' : ''; ?></small></td>
                                    <td class="text-end fw-bold"><?= $sb ? $fmt($sb['avg'], ' mnt') : '-'; ?><br><small class="text-muted fw-normal"><?= $sb && $sb['percent'] !== NULL ? $sb['percent'] . '% capai' : ''; ?></small></td>
                                    <td class="text-end"><?= $delta($sa ? $sa['avg'] : NULL, $sb ? $sb['avg'] : NULL, TRUE); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Grafik harian -->
            <div class="col-xl-6">
                <div class="card-section h-100">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <h5 class="fw-bold mb-0">TAT rata-rata per hari</h5>
                        <small class="d-flex gap-2"><span class="period-tag period-a">Awal</span><span class="period-tag period-b">Sekarang</span></small>
                    </div>
                    <?php
                        $max = 1;
                        foreach (array($a['daily'], $b['daily']) as $daily) {
                            foreach ($daily as $d) if ($d['avg'] !== NULL) $max = max($max, $d['avg']);
                        }
                        $too_many = count($a['daily']) + count($b['daily']) > 40;
                    ?>
                    <?php if ($too_many): ?>
                        <p class="text-muted small mt-3">Grafik harian ditampilkan untuk total maksimal 40 hari.</p>
                    <?php else: ?>
                        <div class="chart">
                            <?php foreach (array('a' => $a['daily'], 'b' => $b['daily']) as $key => $daily): ?>
                                <?php if ($key === 'b'): ?><div class="chart-sep"></div><?php endif; ?>
                                <?php foreach ($daily as $day => $d): ?>
                                    <div class="col-bar" title="<?= date('d/m', strtotime($day)); ?> &middot; <?= $d['count']; ?> truk">
                                        <div class="bar <?= $key; ?>" style="height: <?= $d['avg'] === NULL ? 2 : max(4, round($d['avg'] / $max * 165)); ?>px;"><?php if ($d['avg'] !== NULL): ?><span><?= round($d['avg']); ?></span><?php endif; ?></div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </div>
                        <div class="chart-labels mt-1">
                            <?php foreach (array('a' => $a['daily'], 'b' => $b['daily']) as $key => $daily): ?>
                                <?php if ($key === 'b'): ?><div style="flex: 0 0 2px;"></div><?php endif; ?>
                                <?php foreach ($daily as $day => $d): ?><div><?= date('d/m', strtotime($day)); ?></div><?php endforeach; ?>
                            <?php endforeach; ?>
                        </div>
                        <small class="text-muted d-block mt-2">Angka di atas batang = menit TAT rata-rata. Batang kosong = tidak ada truk selesai hari itu.</small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
