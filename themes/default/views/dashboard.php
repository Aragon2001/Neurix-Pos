<?php (defined('BASEPATH')) OR exit('No direct script access allowed'); ?>

<?php
/* ── Preparar datos para gráficas ── */
$months = $salesArr = $taxArr = $discountArr = [];
$totalMesActual = $taxMesActual = $discountMesActual = 0;
$currentYM = date('Y-m');

if ($chartData) {
    foreach ($chartData as $row) {
        $months[]      = date('M Y', strtotime($row->month));
        $salesArr[]    = (float)$row->total;
        $taxArr[]      = (float)$row->tax;
        $discountArr[] = (float)$row->discount;
        if (substr($row->month, 0, 7) === $currentYM) {
            $totalMesActual    = (float)$row->total;
            $taxMesActual      = (float)$row->tax;
            $discountMesActual = (float)$row->discount;
        }
    }
} else {
    $months = [date('M Y')];
    $salesArr = $taxArr = $discountArr = [0];
}

$topProductNames = $topProductQty = [];
if ($topProducts) {
    foreach ($topProducts as $tp) {
        $topProductNames[] = substr(str_replace("'", '', $tp->product_name), 0, 22);
        $topProductQty[]   = (float)$tp->quantity;
    }
}

$invCost = $invPrice = 0;
if ($costAndPriceInv) {
    foreach ($costAndPriceInv as $row) {
        $invCost  = (float)$row->cost;
        $invPrice = (float)$row->price;
    }
}

$numTopProducts = count($topProducts ?? []);

if (!function_exists('fmtMoney')) {
    function fmtMoney($val) {
        if ($val >= 1000000) return number_format($val / 1000000, 1) . 'M';
        if ($val >= 1000)    return number_format($val / 1000, 1) . 'K';
        return number_format($val, 0, '.', ',');
    }
}
?>

<!-- ApexCharts CDN -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.49.0/dist/apexcharts.min.js"></script>

<style>
/* ── Dashboard local styles ── */
.nx-dash { padding: 18px 20px; }

/* Stat row */
.nx-stats-row { display: grid; grid-template-columns: repeat(4,1fr); gap: 14px; margin-bottom: 20px; }
@media(max-width:900px) { .nx-stats-row { grid-template-columns: repeat(2,1fr); } }
@media(max-width:500px) { .nx-stats-row { grid-template-columns: 1fr; } }

.nx-stat {
    border-radius: 16px;
    padding: 20px;
    display: flex;
    align-items: flex-start;
    gap: 14px;
    background: var(--nx-card-bg);
    border: 1px solid var(--nx-border);
    position: relative;
    overflow: hidden;
    transition: all .22s cubic-bezier(.4,0,.2,1);
    cursor: default;
}
.nx-stat:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 30px rgba(0,0,0,.4);
    border-color: var(--nx-border3);
}
.nx-stat::after {
    content: '';
    position: absolute;
    top: -30px; right: -30px;
    width: 100px; height: 100px;
    border-radius: 50%;
    opacity: .05;
}
.nx-stat.c-blue  { border-top: 2px solid var(--nx-a1);  }
.nx-stat.c-blue::after  { background: var(--nx-a1); }
.nx-stat.c-blue  .nx-stat-ico { background: rgba(56,189,248,.12); color: var(--nx-a1); }

.nx-stat.c-green { border-top: 2px solid var(--nx-ok); }
.nx-stat.c-green::after { background: var(--nx-ok); }
.nx-stat.c-green .nx-stat-ico { background: rgba(34,197,94,.12); color: var(--nx-ok); }

.nx-stat.c-yellow { border-top: 2px solid var(--nx-warn); }
.nx-stat.c-yellow::after { background: var(--nx-warn); }
.nx-stat.c-yellow .nx-stat-ico { background: rgba(234,179,8,.12); color: var(--nx-warn); }

.nx-stat.c-purple { border-top: 2px solid var(--nx-a2); }
.nx-stat.c-purple::after { background: var(--nx-a2); }
.nx-stat.c-purple .nx-stat-ico { background: rgba(129,140,248,.12); color: var(--nx-a2); }

.nx-stat-ico {
    width: 46px; height: 46px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 19px; flex-shrink: 0;
}
.nx-stat-body { flex: 1; min-width: 0; }
.nx-stat-val {
    font-size: 24px; font-weight: 800;
    color: var(--nx-txt1); letter-spacing: -1px; line-height: 1.1;
    margin-bottom: 3px;
}
.nx-stat-lbl {
    font-size: 11px; font-weight: 600;
    color: var(--nx-txt3); text-transform: uppercase; letter-spacing: .5px;
}

/* Charts row */
.nx-chart-row { display: grid; grid-template-columns: 8fr 4fr; gap: 14px; margin-bottom: 20px; }
@media(max-width:900px) { .nx-chart-row { grid-template-columns: 1fr; } }

.nx-chart-row-2 { display: grid; grid-template-columns: 5fr 7fr; gap: 14px; }
@media(max-width:900px) { .nx-chart-row-2 { grid-template-columns: 1fr; } }

/* Chart cards */
.nx-card {
    background: var(--nx-card-bg);
    border: 1px solid var(--nx-border);
    border-radius: 16px;
    overflow: hidden;
}
.nx-card-hdr {
    display: flex; align-items: center; gap: 8px;
    padding: 14px 18px;
    border-bottom: 1px solid var(--nx-border);
    background: var(--nx-card-bg2);
}
.nx-card-hdr i { font-size: 14px; }
.nx-card-hdr-title { font-size: 13.5px; font-weight: 700; color: var(--nx-txt1); flex: 1; }
.nx-card-hdr-sub { font-size: 11px; color: var(--nx-txt4); }
.nx-card-body { padding: 14px 18px 10px; }

/* Quick links */
.nx-quick {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    gap: 10px;
    padding: 4px 0 2px;
}
.nx-q-item {
    display: flex; flex-direction: column; align-items: center;
    justify-content: center; gap: 9px; padding: 16px 6px 12px;
    background: var(--nx-card-bg2);
    border: 1px solid var(--nx-border);
    border-radius: 13px;
    color: var(--nx-txt3);
    font-size: 11px; font-weight: 600; text-align: center;
    text-decoration: none;
    transition: all .2s cubic-bezier(.4,0,.2,1);
    cursor: pointer; position: relative; overflow: hidden;
}
.nx-q-item::before {
    content: '';
    position: absolute; inset: 0;
    background: radial-gradient(circle at center, rgba(56,189,248,.07), transparent 70%);
    opacity: 0; transition: opacity .2s;
}
.nx-q-item:hover {
    border-color: var(--nx-a1);
    color: var(--nx-txt1);
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(56,189,248,.14);
    text-decoration: none;
}
.nx-q-item:hover::before { opacity: 1; }

.nx-q-icon {
    width: 40px; height: 40px; border-radius: 11px;
    display: flex; align-items: center; justify-content: center;
    font-size: 17px;
    transition: transform .2s cubic-bezier(.4,0,.2,1);
}
.nx-q-item:hover .nx-q-icon { transform: scale(1.15); }

.nx-q-label {
    line-height: 1.25; white-space: nowrap;
    overflow: hidden; text-overflow: ellipsis; max-width: 100%;
}

/* Icon color palette for quick links */
.nqi-cyan    { background: rgba(34,211,238,.12);  color: #22d3ee; }
.nqi-orange  { background: rgba(249,115,22,.12);  color: #f97316; }
.nqi-green   { background: rgba(34,197,94,.12);   color: #22c55e; }
.nqi-amber   { background: rgba(251,191,36,.12);  color: #fbbf24; }
.nqi-teal    { background: rgba(45,212,191,.12);  color: #2dd4bf; }
.nqi-violet  { background: rgba(167,139,250,.12); color: #a78bfa; }
.nqi-pink    { background: rgba(236,72,153,.12);  color: #ec4899; }
.nqi-sky     { background: rgba(56,189,248,.12);  color: #38bdf8; }
.nqi-indigo  { background: rgba(99,102,241,.12);  color: #818cf8; }
.nqi-rose    { background: rgba(251,113,133,.12); color: #fb7185; }
.nqi-blue    { background: rgba(56,189,248,.12);  color: #38bdf8; }
.nqi-emerald { background: rgba(52,211,153,.12);  color: #34d399; }
.nqi-slate   { background: rgba(148,163,184,.12); color: #94a3b8; }
.nqi-purple  { background: rgba(168,85,247,.12);  color: #c084fc; }
.nqi-red     { background: rgba(239,68,68,.12);   color: #f87171; }

/* Section label */
.nx-qlabel {
    font-size: 10px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .8px; color: var(--nx-txt4);
    padding: 4px 0 6px;
}
</style>

<div class="nx-dash">

<!-- ═══ STAT CARDS ═══ -->
<div class="nx-stats-row">
    <div class="nx-stat c-blue">
        <div class="nx-stat-ico"><i class="fa fa-line-chart"></i></div>
        <div class="nx-stat-body">
            <div class="nx-stat-val">₡<?= fmtMoney($totalMesActual); ?></div>
            <div class="nx-stat-lbl">Ventas · <?= date('M'); ?></div>
        </div>
    </div>
    <div class="nx-stat c-green">
        <div class="nx-stat-ico"><i class="fa fa-percent"></i></div>
        <div class="nx-stat-body">
            <div class="nx-stat-val">₡<?= fmtMoney($taxMesActual); ?></div>
            <div class="nx-stat-lbl">IVA · <?= date('M'); ?></div>
        </div>
    </div>
    <div class="nx-stat c-yellow">
        <div class="nx-stat-ico"><i class="fa fa-tag"></i></div>
        <div class="nx-stat-body">
            <div class="nx-stat-val">₡<?= fmtMoney($discountMesActual); ?></div>
            <div class="nx-stat-lbl">Descuentos · <?= date('M'); ?></div>
        </div>
    </div>
    <div class="nx-stat c-purple">
        <div class="nx-stat-ico"><i class="fa fa-cubes"></i></div>
        <div class="nx-stat-body">
            <div class="nx-stat-val"><?= $numTopProducts; ?></div>
            <div class="nx-stat-lbl">Productos Top</div>
        </div>
    </div>
</div>

<!-- ═══ GRÁFICAS: VENTAS + INVENTARIO ═══ -->
<div class="nx-chart-row">

    <div class="nx-card">
        <div class="nx-card-hdr">
            <i class="fa fa-bar-chart" style="color:var(--nx-a1);"></i>
            <span class="nx-card-hdr-title"><?= lang('sales_chart'); ?></span>
            <span class="nx-card-hdr-sub"><?= date('Y'); ?></span>
        </div>
        <div class="nx-card-body">
            <div id="nx-chart-sales" style="min-height:280px;"></div>
        </div>
    </div>

    <div class="nx-card">
        <div class="nx-card-hdr">
            <i class="fa fa-pie-chart" style="color:var(--nx-a3);"></i>
            <span class="nx-card-hdr-title"><?= lang('cost_inv'); ?></span>
            <span class="nx-card-hdr-sub"><?= date('M Y'); ?></span>
        </div>
        <div class="nx-card-body">
            <div id="nx-chart-inv" style="min-height:280px;"></div>
        </div>
    </div>

</div>

<!-- ═══ TOP PRODUCTOS + ACCESOS RÁPIDOS ═══ -->
<div class="nx-chart-row-2">

    <div class="nx-card">
        <div class="nx-card-hdr">
            <i class="fa fa-star" style="color:var(--nx-warn);"></i>
            <span class="nx-card-hdr-title"><?= lang('top_products'); ?></span>
            <span class="nx-card-hdr-sub"><?= date('M Y'); ?></span>
        </div>
        <div class="nx-card-body">
            <div id="nx-chart-top" style="min-height:280px;"></div>
        </div>
    </div>

    <div class="nx-card">
        <div class="nx-card-hdr">
            <i class="fa fa-th" style="color:var(--nx-a1);"></i>
            <span class="nx-card-hdr-title"><?= lang('quick_links'); ?></span>
        </div>
        <div class="nx-card-body">
            <div class="nx-quick">

                <?php if ($this->session->userdata('store_id')): ?>
                <a class="nx-q-item" href="<?= site_url('pos'); ?>">
                    <span class="nx-q-icon nqi-cyan"><i class="fa fa-shopping-bag"></i></span>
                    <span class="nx-q-label"><?= lang('pos'); ?></span>
                </a>
                <?php endif; ?>

                <a class="nx-q-item" href="<?= site_url('products'); ?>">
                    <span class="nx-q-icon nqi-orange"><i class="fa fa-cube"></i></span>
                    <span class="nx-q-label"><?= lang('products'); ?></span>
                </a>

                <a class="nx-q-item" href="<?= site_url('categories'); ?>">
                    <span class="nx-q-icon nqi-teal"><i class="fa fa-tags"></i></span>
                    <span class="nx-q-label"><?= lang('categories'); ?></span>
                </a>

                <a class="nx-q-item" href="<?= site_url('customers'); ?>">
                    <span class="nx-q-icon nqi-violet"><i class="fa fa-users"></i></span>
                    <span class="nx-q-label"><?= lang('customers'); ?></span>
                </a>

                <?php if ($this->session->userdata('store_id')): ?>
                <a class="nx-q-item" href="<?= site_url('sales'); ?>">
                    <span class="nx-q-icon nqi-green"><i class="fa fa-line-chart"></i></span>
                    <span class="nx-q-label"><?= lang('sales'); ?></span>
                </a>
                <a class="nx-q-item" href="<?= site_url('sales/opened'); ?>">
                    <span class="nx-q-icon nqi-amber"><i class="fa fa-clock-o"></i></span>
                    <span class="nx-q-label"><?= lang('opened_bills'); ?></span>
                </a>
                <?php endif; ?>

                <?php if ($Admin): ?>
                <a class="nx-q-item" href="<?= site_url('purchases'); ?>">
                    <span class="nx-q-icon nqi-amber"><i class="fa fa-truck"></i></span>
                    <span class="nx-q-label"><?= lang('purchases'); ?></span>
                </a>
                <a class="nx-q-item" href="<?= site_url('CreditNotes'); ?>">
                    <span class="nx-q-icon nqi-pink"><i class="fa fa-exchange"></i></span>
                    <span class="nx-q-label"><?= lang('credit_notes'); ?></span>
                </a>
                <a class="nx-q-item" href="<?= site_url('facturascompras'); ?>">
                    <span class="nx-q-icon nqi-sky"><i class="fa fa-cloud"></i></span>
                    <span class="nx-q-label">FEC</span>
                </a>
                <a class="nx-q-item" href="<?= site_url('reports/daily_sales'); ?>">
                    <span class="nx-q-icon nqi-rose"><i class="fa fa-calendar-check-o"></i></span>
                    <span class="nx-q-label">Ventas Diarias</span>
                </a>
                <a class="nx-q-item" href="<?= site_url('reports/monthly_sale_tax'); ?>">
                    <span class="nx-q-icon nqi-indigo"><i class="fa fa-percent"></i></span>
                    <span class="nx-q-label">Ventas c/ IVA</span>
                </a>
                <a class="nx-q-item" href="<?= site_url('reports/registers'); ?>">
                    <span class="nx-q-icon nqi-blue"><i class="fa fa-calculator"></i></span>
                    <span class="nx-q-label">Caja</span>
                </a>
                <a class="nx-q-item" href="<?= site_url('reports/credit_customers'); ?>">
                    <span class="nx-q-icon nqi-indigo"><i class="fa fa-credit-card"></i></span>
                    <span class="nx-q-label">Cta. Clientes</span>
                </a>
                <a class="nx-q-item" href="<?= site_url('products/ajuste'); ?>">
                    <span class="nx-q-icon nqi-cyan"><i class="fa fa-balance-scale"></i></span>
                    <span class="nx-q-label">Ajuste Inv.</span>
                </a>
                <a class="nx-q-item" href="<?= site_url('customers/add'); ?>">
                    <span class="nx-q-icon nqi-pink"><i class="fa fa-user-plus"></i></span>
                    <span class="nx-q-label">Nuevo Cliente</span>
                </a>
                <a class="nx-q-item" href="<?= site_url('users'); ?>">
                    <span class="nx-q-icon nqi-emerald"><i class="fa fa-list-ul"></i></span>
                    <span class="nx-q-label">Usuarios</span>
                </a>
                <a class="nx-q-item" href="<?= site_url('users/add'); ?>">
                    <span class="nx-q-icon nqi-teal"><i class="fa fa-user-plus"></i></span>
                    <span class="nx-q-label">Nuevo Usuario</span>
                </a>
                <a class="nx-q-item" href="<?= site_url('settings'); ?>">
                    <span class="nx-q-icon nqi-slate"><i class="fa fa-sliders"></i></span>
                    <span class="nx-q-label"><?= lang('settings'); ?></span>
                </a>
                <?php if ($this->db->dbdriver != 'sqlite3'): ?>
                <a class="nx-q-item" href="<?= site_url('settings/backups'); ?>">
                    <span class="nx-q-icon nqi-orange"><i class="fa fa-database"></i></span>
                    <span class="nx-q-label"><?= lang('backups'); ?></span>
                </a>
                <?php endif; ?>
                <?php endif; ?>

            </div>
        </div>
    </div>

</div>
</div>

<!-- ═══ JAVASCRIPT: APEXCHARTS ═══ -->
<script>
(function(){
    var isDark = document.documentElement.getAttribute('data-theme') !== 'light';
    var txtColor  = isDark ? '#94a3b8' : '#475569';
    var gridColor = isDark ? 'rgba(56,189,248,.07)' : 'rgba(14,165,233,.1)';
    var tooltipBg = isDark ? '#111827' : '#ffffff';

    /* ── Gráfica 1: Ventas mensuales ── */
    var salesOpts = {
        chart: {
            type: 'bar', height: 280, stacked: true,
            background: 'transparent', toolbar: { show: false },
            fontFamily: '-apple-system,BlinkMacSystemFont,"Segoe UI",system-ui,sans-serif',
            animations: { enabled: true, speed: 700 }
        },
        theme: { mode: isDark ? 'dark' : 'light' },
        colors: ['#38bdf8', '#22c55e', '#818cf8'],
        series: [
            { name: '<?= $this->lang->line("sales"); ?>',    data: <?= json_encode($salesArr); ?> },
            { name: '<?= $this->lang->line("tax"); ?>',      data: <?= json_encode($taxArr); ?> },
            { name: '<?= $this->lang->line("discount"); ?>', data: <?= json_encode($discountArr); ?> }
        ],
        xaxis: {
            categories: <?= json_encode($months); ?>,
            labels: { style: { colors: txtColor, fontSize: '11px' }, rotate: -30, rotateAlways: false },
            axisBorder: { color: gridColor }, axisTicks: { color: gridColor }
        },
        yaxis: {
            labels: {
                style: { colors: txtColor, fontSize: '11px' },
                formatter: function(v) {
                    return v >= 1000000 ? (v/1000000).toFixed(1)+'M' : v >= 1000 ? (v/1000).toFixed(0)+'K' : v;
                }
            }
        },
        grid: { borderColor: gridColor, strokeDashArray: 4 },
        legend: { labels: { colors: txtColor }, position: 'top', fontSize: '12px' },
        plotOptions: { bar: { borderRadius: 4, columnWidth: '60%' } },
        dataLabels: { enabled: false },
        tooltip: {
            theme: isDark ? 'dark' : 'light',
            style: { fontSize: '12px' },
            y: { formatter: function(v) { return '₡ ' + v.toLocaleString('es-CR'); } }
        }
    };

    <?php if ($chartData): ?>
    try {
        new ApexCharts(document.querySelector('#nx-chart-sales'), salesOpts).render();
    } catch(e) {
        document.querySelector('#nx-chart-sales').innerHTML = '<p style="padding:60px 20px;text-align:center;color:'+txtColor+';font-size:13px;">Sin datos de ventas</p>';
    }
    <?php else: ?>
    document.querySelector('#nx-chart-sales').innerHTML = '<p style="padding:60px 20px;text-align:center;color:'+txtColor+';font-size:13px;">Sin datos de ventas disponibles</p>';
    <?php endif; ?>

    /* ── Gráfica 2: Inventario (radial) ── */
    <?php if ($costAndPriceInv && $invCost + $invPrice > 0): ?>
    var totalInv = <?= $invCost + $invPrice; ?>;
    var pctCost  = totalInv > 0 ? Math.round(<?= $invCost; ?> / totalInv * 100) : 0;
    var pctPrice = totalInv > 0 ? Math.round(<?= $invPrice; ?> / totalInv * 100) : 0;
    new ApexCharts(document.querySelector('#nx-chart-inv'), {
        chart: { type: 'radialBar', height: 280, background: 'transparent', toolbar: { show: false } },
        theme: { mode: isDark ? 'dark' : 'light' },
        series: [pctCost, pctPrice],
        labels: ['<?= $this->lang->line("cost"); ?>', '<?= $this->lang->line("price"); ?>'],
        colors: ['#818cf8', '#22d3ee'],
        plotOptions: {
            radialBar: {
                hollow: { size: '38%' },
                track: { background: isDark ? 'rgba(56,189,248,.07)' : 'rgba(14,165,233,.1)' },
                dataLabels: {
                    name:  { color: txtColor, fontSize: '12px' },
                    value: { color: isDark ? '#e2e8f0' : '#0f172a', fontSize: '16px', fontWeight: 700 },
                    total: {
                        show: true, label: 'Total Inv.',
                        color: txtColor, fontSize: '11px',
                        formatter: function() {
                            return '₡' + (<?= $invCost + $invPrice; ?> / 1000).toFixed(0) + 'K';
                        }
                    }
                }
            }
        },
        legend: { show: true, labels: { colors: txtColor }, fontSize: '12px', position: 'bottom' },
        stroke: { lineCap: 'round' }
    }).render();
    <?php else: ?>
    document.querySelector('#nx-chart-inv').innerHTML = '<p style="padding:60px 20px;text-align:center;color:'+txtColor+';font-size:13px;">Sin datos de inventario</p>';
    <?php endif; ?>

    /* ── Gráfica 3: Top productos (donut) ── */
    <?php if ($topProducts): ?>
    new ApexCharts(document.querySelector('#nx-chart-top'), {
        chart: { type: 'donut', height: 280, background: 'transparent' },
        theme: { mode: isDark ? 'dark' : 'light' },
        series: <?= json_encode($topProductQty); ?>,
        labels: <?= json_encode($topProductNames); ?>,
        colors: ['#38bdf8','#818cf8','#22d3ee','#22c55e','#eab308','#ef4444','#f97316','#ec4899'],
        dataLabels: { enabled: false },
        legend: { show: true, position: 'bottom', fontSize: '12px', labels: { colors: txtColor } },
        plotOptions: {
            pie: {
                donut: {
                    size: '60%',
                    labels: {
                        show: true,
                        total: {
                            show: true, label: 'Total vendido',
                            color: txtColor, fontSize: '11px',
                            formatter: function(w) {
                                return w.globals.seriesTotals.reduce(function(a,b){return a+b;},0).toLocaleString();
                            }
                        }
                    }
                }
            }
        },
        stroke: { colors: [isDark ? '#0a1020' : '#ffffff'], width: 2 },
        tooltip: { theme: isDark ? 'dark' : 'light', style: { fontSize: '12px' } }
    }).render();
    <?php else: ?>
    document.querySelector('#nx-chart-top').innerHTML = '<p style="padding:60px 20px;text-align:center;color:'+txtColor+';font-size:13px;">Sin datos de productos</p>';
    <?php endif; ?>

})();
</script>
