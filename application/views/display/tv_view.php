<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>LASMONO - Live Antrean</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php $this->load->view('partials/theme'); ?>
    <style>
        /* Ukuran mengikuti lebar layar: 1920px (TV 43" Full HD) = 16px */
        html { font-size: calc(100vw / 120); }
        * { box-sizing: border-box; }
        html, body { height: 100%; margin: 0; overflow: hidden; }
        body { display: flex; flex-direction: column; cursor: none; }

        .tv-head { display: flex; align-items: center; justify-content: space-between; background: var(--lx-ink); color: #fff; padding: .8rem 1.6rem; border-bottom: .25rem solid var(--lx-primary); }
        .tv-head .lx-avatar { width: 3.4rem; height: 3.4rem; }
        .tv-title { font-family: var(--lx-font-display); font-size: 1.7rem; letter-spacing: .08em; text-transform: uppercase; line-height: 1.1; }
        .tv-title span { color: #f6c64a; }
        .tv-sub { color: #a9adb6; font-size: .95rem; }
        .tv-clock { text-align: right; font-variant-numeric: tabular-nums; }
        .tv-clock b { display: block; font-size: 2.6rem; line-height: 1; }
        .tv-clock small { color: #a9adb6; font-size: .95rem; }

        .tv-kpis { display: grid; grid-template-columns: repeat(5, 1fr); gap: 1rem; padding: 1rem 1.6rem .4rem; }
        .tv-kpi { background: #fff; border: 1px solid var(--lx-border); border-radius: 1rem; padding: .7rem 1.1rem; display: flex; align-items: center; gap: .9rem; box-shadow: var(--lx-shadow); }
        .tv-kpi .ic { width: 3rem; height: 3rem; border-radius: .8rem; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; flex-shrink: 0; }
        .tv-kpi .num { font-size: 2.2rem; font-weight: 800; line-height: 1; font-variant-numeric: tabular-nums; }
        .tv-kpi .lbl { color: var(--lx-muted); font-size: .95rem; font-weight: 600; }
        .tv-kpi.alert-on { border-color: var(--lx-red); background: #fff5f5; }
        .tv-kpi.alert-on .num { color: var(--lx-red); }

        .tv-board { flex: 1; min-height: 0; display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; padding: .8rem 1.6rem; }
        .tv-col { display: flex; flex-direction: column; min-height: 0; background: #fbfaf7; border: 1px solid var(--lx-border); border-radius: 1rem; overflow: hidden; }
        .tv-col-head { display: flex; align-items: center; justify-content: space-between; padding: .75rem 1rem; background: #fff; border-top: .35rem solid var(--col); border-bottom: 1px solid var(--lx-border); }
        .tv-col-head h2 { margin: 0; font-size: 1.25rem; font-weight: 800; letter-spacing: -.01em; }
        .tv-col-head h2 i { color: var(--col); margin-right: .4rem; }
        .tv-col-head small { display: block; color: var(--lx-muted); font-size: .8rem; font-weight: 600; }
        .tv-count { min-width: 2.6rem; height: 2.6rem; padding: 0 .6rem; border-radius: .8rem; background: var(--col); color: #fff; font-size: 1.4rem; font-weight: 800; display: flex; align-items: center; justify-content: center; }
        .tv-cards { flex: 1; min-height: 0; padding: .7rem; display: flex; flex-direction: column; gap: .6rem; overflow: hidden; }
        .tv-page { text-align: center; color: var(--lx-muted); font-size: .8rem; font-weight: 700; padding-bottom: .4rem; min-height: 1.3rem; }

        .tv-card { flex-shrink: 0; height: 5.6rem; background: #fff; border: 1px solid var(--lx-border); border-left: .4rem solid #d6d3cc; border-radius: .8rem; padding: .55rem .8rem; display: flex; align-items: center; justify-content: space-between; gap: .6rem; box-shadow: 0 1px 2px rgba(28, 29, 33, .04); }
        .tv-card.lv-warning { border-left-color: #f59e0b; }
        .tv-card.lv-over { border-left-color: var(--lx-red); background: #fff5f5; }
        .tv-plate { font-size: 1.55rem; font-weight: 800; letter-spacing: .02em; line-height: 1.1; white-space: nowrap; }
        .tv-meta { color: var(--lx-muted); font-size: .85rem; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-top: .2rem; }
        .tv-meta .act-tag { font-size: .72rem; margin-right: .3rem; vertical-align: .05rem; }
        .tv-left { min-width: 0; }
        .tv-time { flex-shrink: 0; text-align: center; min-width: 5.4rem; padding: .35rem .5rem; border-radius: .7rem; font-variant-numeric: tabular-nums; }
        .tv-time b { display: block; font-size: 1.5rem; line-height: 1; }
        .tv-time small { font-size: .72rem; font-weight: 700; opacity: .85; }
        .tv-time.lv-ok { background: #dcfce7; color: #047857; }
        .tv-time.lv-warning { background: #fef3c7; color: #b45309; }
        .tv-time.lv-over { background: var(--lx-red); color: #fff; animation: lx-pulse 1.5s infinite; }
        .tv-time.lv-none { background: #f1f2f4; color: #4b5059; }
        .tv-empty { color: #a3a7af; text-align: center; padding: 2rem .5rem; font-size: 1rem; font-weight: 600; }

        .tv-foot { display: flex; justify-content: space-between; align-items: center; padding: .4rem 1.6rem .7rem; color: var(--lx-muted); font-size: .85rem; font-weight: 600; }
        .tv-foot .lg i { display: inline-block; width: .8rem; height: .8rem; border-radius: .2rem; margin: 0 .35rem 0 1rem; vertical-align: -.05rem; }
        .tv-foot .acts .act-tag { font-size: .72rem; margin-left: .4rem; }
        .tv-offline { color: var(--lx-red); display: none; }
        .tv-offline.show { display: inline; }
    </style>
</head>
<body>
    <header class="tv-head">
        <div class="d-flex align-items-center" style="display: flex; align-items: center; gap: 1rem;">
            <?php $this->load->view('partials/logo', array('size' => 54)); ?>
            <div>
                <div class="tv-title">Live Antrean <span>LASMONO</span></div>
                <div class="tv-sub">Depo Petikemas MTKI &middot; Samudera Group</div>
            </div>
        </div>
        <div class="tv-clock"><b id="tvClock">--:--</b><small id="tvDate"></small></div>
    </header>

    <section class="tv-kpis">
        <div class="tv-kpi"><span class="ic" style="background: #f1f2f4; color: #4b5059;"><i class="fas fa-truck"></i></span><div><div class="num" id="kWaiting">0</div><div class="lbl">Antre di Luar</div></div></div>
        <div class="tv-kpi"><span class="ic" style="background: #fff3d1; color: #b7790a;"><i class="fas fa-warehouse"></i></span><div><div class="num" id="kInYard">0</div><div class="lbl">Di Dalam Depo</div></div></div>
        <div class="tv-kpi"><span class="ic" style="background: #dcfce7; color: #059669;"><i class="fas fa-flag-checkered"></i></span><div><div class="num" id="kDone">0</div><div class="lbl">Selesai Hari Ini</div></div></div>
        <div class="tv-kpi"><span class="ic" style="background: #ffedd5; color: #ea580c;"><i class="fas fa-stopwatch"></i></span><div><div class="num"><span id="kTat">0</span><small style="font-size: 1rem;"> mnt</small></div><div class="lbl">Rata-rata TAT</div></div></div>
        <div class="tv-kpi" id="kOverBox"><span class="ic" style="background: #fee2e2; color: #b91c1c;"><i class="fas fa-triangle-exclamation"></i></span><div><div class="num" id="kOver">0</div><div class="lbl">Lewat Target</div></div></div>
    </section>

    <main class="tv-board">
        <div class="tv-col" data-col="antre" style="--col: #6b6f78;">
            <div class="tv-col-head"><div><h2><i class="fas fa-hourglass-half"></i>Antre Gate In</h2><small>Urutan FIFO &middot; siapkan kitir</small></div><span class="tv-count">0</span></div>
            <div class="tv-cards"></div><div class="tv-page"></div>
        </div>
        <div class="tv-col" data-col="survey" style="--col: #e5a720;">
            <div class="tv-col-head"><div><h2><i class="fas fa-magnifying-glass"></i>Survey</h2><small>Repo In &amp; Import</small></div><span class="tv-count">0</span></div>
            <div class="tv-cards"></div><div class="tv-page"></div>
        </div>
        <div class="tv-col" data-col="muat" style="--col: #1d4ed8;">
            <div class="tv-col-head"><div><h2><i class="fas fa-dolly"></i>Muat Container</h2><small>Export &amp; Repo Out</small></div><span class="tv-count">0</span></div>
            <div class="tv-cards"></div><div class="tv-page"></div>
        </div>
        <div class="tv-col" data-col="gate_out" style="--col: #10b981;">
            <div class="tv-col-head"><div><h2><i class="fas fa-right-from-bracket"></i>Menuju Gate Out</h2><small>Survey / muat selesai</small></div><span class="tv-count">0</span></div>
            <div class="tv-cards"></div><div class="tv-page"></div>
        </div>
    </main>

    <footer class="tv-foot">
        <div class="lg">Waktu tahap / target:<i class="sla-ok" style="background: #dcfce7;"></i>Aman<i style="background: #fef3c7;"></i>&ge; 80% target<i style="background: var(--lx-red);"></i>Lewat target
            <span class="acts"><span class="act-tag act-Z">Repo In</span><span class="act-tag act-A">Import</span><span class="act-tag act-D">Export</span><span class="act-tag act-E">Repo Out</span></span>
        </div>
        <div><span class="tv-offline" id="tvOffline"><i class="fas fa-plug-circle-xmark me-1"></i> Koneksi terputus, mencoba lagi &middot; </span>Diperbarui <span id="tvUpdated">-</span></div>
    </footer>

    <script>
    (function() {
        var DATA_URL = <?= json_encode($data_url); ?>;
        var PAGE_SECONDS = 8;
        var DAYS = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        var MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        var columns = { antre: [], survey: [], muat: [], gate_out: [] };
        var page = 0;

        function esc(v) {
            return String(v === null || v === undefined ? '' : v).replace(/[&<>"']/g, function(c) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
            });
        }
        function pad(n) { return (n < 10 ? '0' : '') + n; }

        function tickClock() {
            var d = new Date();
            document.getElementById('tvClock').textContent = pad(d.getHours()) + ':' + pad(d.getMinutes());
            document.getElementById('tvDate').textContent = DAYS[d.getDay()] + ', ' + d.getDate() + ' ' + MONTHS[d.getMonth()] + ' ' + d.getFullYear();
        }

        // Kolom tampilan per tahap: survey (Repo In/Import), muat (Export/Repo Out)
        function columnOf(q) {
            if (q.stage === 'antre') return 'antre';
            if (q.stage === 'gate_out') return 'gate_out';
            if (q.stage === 'pick' || q.stage === 'load') return 'muat';
            return 'survey';
        }

        function card(q) {
            var waiting = q.stage === 'antre';
            var level = waiting ? 'none' : (q.stage_level || 'none');
            var cardLevel = (q.stage_level === 'over' || q.sla_level === 'over') ? 'over' : (q.stage_level === 'warning' || q.sla_level === 'warning' ? 'warning' : '');
            var detail = waiting
                ? '#' + q.queue_number + (q.estimated_call !== null ? ' · ±' + q.estimated_call + ' mnt' : '')
                : (q.zone ? q.zone : (q.container_no || '#' + q.queue_number));
            return '<div class="tv-card' + (cardLevel ? ' lv-' + cardLevel : '') + '">' +
                '<div class="tv-left"><div class="tv-plate">' + esc(q.license_plate) + '</div>' +
                '<div class="tv-meta"><span class="act-tag act-' + esc(q.activity) + '">' + esc(q.activity_label) + '</span>' + esc(detail) + '</div></div>' +
                '<div class="tv-time lv-' + level + '"><b>' + esc(q.stage_minutes) + '</b><small>' + (waiting ? 'mnt antre' : '/ ' + esc(q.stage_target) + ' mnt') + '</small></div>' +
            '</div>';
        }

        // Jika kartu tidak muat satu layar, tampilkan bergantian per halaman
        function renderColumns() {
            Object.keys(columns).forEach(function(key) {
                var col = document.querySelector('.tv-col[data-col="' + key + '"]');
                var box = col.querySelector('.tv-cards');
                var list = columns[key];
                col.querySelector('.tv-count').textContent = list.length;
                if (!list.length) {
                    box.innerHTML = '<div class="tv-empty">Tidak ada truk</div>';
                    col.querySelector('.tv-page').textContent = '';
                    return;
                }
                var rem = parseFloat(getComputedStyle(document.documentElement).fontSize);
                var perPage = Math.max(1, Math.floor((box.clientHeight - 0.7 * rem) / (6.2 * rem)));
                var pages = Math.ceil(list.length / perPage);
                var current = page % pages;
                box.innerHTML = list.slice(current * perPage, current * perPage + perPage).map(card).join('');
                col.querySelector('.tv-page').textContent = pages > 1 ? 'Halaman ' + (current + 1) + ' / ' + pages : '';
            });
        }

        function load() {
            var xhr = new XMLHttpRequest();
            xhr.open('GET', DATA_URL + '?_=' + Date.now(), true);
            xhr.timeout = 8000;
            xhr.onload = function() {
                if (xhr.status !== 200) { offline(); return; }
                try {
                    var res = JSON.parse(xhr.responseText);
                    columns = { antre: [], survey: [], muat: [], gate_out: [] };
                    res.queues.forEach(function(q) { columns[columnOf(q)].push(q); });
                    // Di dalam depo: yang paling lama di tahapnya tampil paling atas
                    ['survey', 'muat', 'gate_out'].forEach(function(k) {
                        columns[k].sort(function(a, b) { return b.stage_minutes - a.stage_minutes; });
                    });
                    document.getElementById('kWaiting').textContent = res.kpi.waiting;
                    document.getElementById('kInYard').textContent = res.kpi.in_yard;
                    document.getElementById('kDone').textContent = res.kpi.completed;
                    document.getElementById('kTat').textContent = res.kpi.avg_tat;
                    document.getElementById('kOver').textContent = res.kpi.over_sla;
                    document.getElementById('kOverBox').classList.toggle('alert-on', res.kpi.over_sla > 0);
                    document.getElementById('tvUpdated').textContent = res.time;
                    document.getElementById('tvOffline').classList.remove('show');
                    renderColumns();
                } catch (e) { offline(); }
            };
            xhr.onerror = xhr.ontimeout = offline;
            xhr.send();
        }
        function offline() { document.getElementById('tvOffline').classList.add('show'); }

        tickClock();
        setInterval(tickClock, 1000);
        load();
        setInterval(load, 5000);
        setInterval(function() { page++; renderColumns(); }, PAGE_SECONDS * 1000);
        window.addEventListener('resize', renderColumns);
    })();
    </script>
</body>
</html>
