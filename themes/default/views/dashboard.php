<?php (defined('BASEPATH')) OR exit('No direct script access allowed'); ?>

<?php
/* ── Preparar datos para gráficas ── */
$months = $salesArr = $taxArr = $discountArr = [];
$totalMesActual = $taxMesActual = $discountMesActual = 0;
$currentYM = date('Y-m');

if ($chartData) {
    foreach ($chartData as $row) {
        $months[]    = date('M Y', strtotime($row->month));
        $salesArr[]  = (float)$row->total;
        $taxArr[]    = (float)$row->tax;
        $discountArr[] = (float)$row->discount;
        if (substr($row->month, 0, 7) === $currentYM) {
            $totalMesActual   = (float)$row->total;
            $taxMesActual     = (float)$row->tax;
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
$currencySymbol = '₡';

if (!function_exists('fmtMoney')) { function fmtMoney($val) {
    if ($val >= 1000000) return number_format($val/1000000, 1) . 'M';
    if ($val >= 1000)    return number_format($val/1000, 1) . 'K';
    return number_format($val, 0, '.', ',');
} }
?>

<!-- ApexCharts CDN -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.49.0/dist/apexcharts.min.js"></script>

<section class="content" style="padding:16px 20px;">

<!-- ═══ FILA 1: STAT CARDS ═══ -->
<div class="row">

    <div class="col-xs-12 col-sm-6 col-md-3">
        <div class="nx-stat c-blue">
            <div class="nx-stat-ico"><i class="fa fa-line-chart"></i></div>
            <div class="nx-stat-val"><?= $currencySymbol . fmtMoney($totalMesActual); ?></div>
            <div class="nx-stat-lbl">Ventas — <?= date('F'); ?></div>
        </div>
    </div>

    <div class="col-xs-12 col-sm-6 col-md-3">
        <div class="nx-stat c-green">
            <div class="nx-stat-ico"><i class="fa fa-percent"></i></div>
            <div class="nx-stat-val"><?= $currencySymbol . fmtMoney($taxMesActual); ?></div>
            <div class="nx-stat-lbl">IVA — <?= date('F'); ?></div>
        </div>
    </div>

    <div class="col-xs-12 col-sm-6 col-md-3">
        <div class="nx-stat c-yellow">
            <div class="nx-stat-ico"><i class="fa fa-tag"></i></div>
            <div class="nx-stat-val"><?= $currencySymbol . fmtMoney($discountMesActual); ?></div>
            <div class="nx-stat-lbl">Descuentos — <?= date('F'); ?></div>
        </div>
    </div>

    <div class="col-xs-12 col-sm-6 col-md-3">
        <div class="nx-stat c-purple">
            <div class="nx-stat-ico"><i class="fa fa-trophy"></i></div>
            <div class="nx-stat-val"><?= $numTopProducts; ?></div>
            <div class="nx-stat-lbl">Productos activos</div>
        </div>
    </div>

</div>

<!-- ═══ FILA 2: GRÁFICA VENTAS + INVENTARIO ═══ -->
<div class="row">

    <!-- Gráfica de barras: ventas mensuales -->
    <div class="col-xs-12 col-md-8">
        <div class="box box-primary">
            <div class="box-header">
                <i class="fa fa-bar-chart" style="color:var(--nx-a1);margin-right:8px;"></i>
                <h3 class="box-title"><?= lang('sales_chart'); ?></h3>
                <div class="box-tools pull-right" style="display:flex;align-items:center;gap:6px;margin-top:2px;">
                    <span style="font-size:11px;color:var(--nx-txt3);"><?= date('Y'); ?></span>
                </div>
            </div>
            <div class="box-body" style="padding:10px 14px 4px;">
                <div id="nx-chart-sales" style="min-height:290px;"></div>
            </div>
        </div>
    </div>

    <!-- Inventario costo vs precio -->
    <div class="col-xs-12 col-md-4">
        <div class="box box-info">
            <div class="box-header">
                <i class="fa fa-pie-chart" style="color:var(--nx-a3);margin-right:8px;"></i>
                <h3 class="box-title"><?= lang('cost_inv') . ' (' . date('M Y') . ')'; ?></h3>
            </div>
            <div class="box-body" style="padding:10px 14px 4px;">
                <div id="nx-chart-inv" style="min-height:290px;"></div>
            </div>
        </div>
    </div>

</div>

<!-- ═══ FILA 3: TOP PRODUCTOS + ACCESOS RÁPIDOS ═══ -->
<div class="row">

    <!-- Top productos -->
    <div class="col-xs-12 col-md-5">
        <div class="box box-success">
            <div class="box-header">
                <i class="fa fa-star" style="color:var(--nx-ok);margin-right:8px;"></i>
                <h3 class="box-title"><?= lang('top_products') . ' (' . date('M Y') . ')'; ?></h3>
            </div>
            <div class="box-body" style="padding:10px 14px 4px;">
                <div id="nx-chart-top" style="min-height:290px;"></div>
            </div>
        </div>
    </div>

    <!-- Accesos rápidos -->
    <div class="col-xs-12 col-md-7">
        <div class="box box-primary">
            <div class="box-header">
                <i class="fa fa-th" style="color:var(--nx-a1);margin-right:8px;"></i>
                <h3 class="box-title"><?= lang('quick_links'); ?></h3>
            </div>
            <div class="box-body">
                <div class="nx-quick">

                    <?php if ($this->session->userdata('store_id')): ?>
                    <a class="nx-q-item" href="<?= site_url('pos'); ?>">
                        <i class="fa fa-shopping-bag nx-ico-cyan"></i>
                        <span><?= lang('pos'); ?></span>
                    </a>
                    <?php endif; ?>

                    <a class="nx-q-item" href="<?= site_url('products'); ?>">
                        <i class="fa fa-cube nx-ico-orange"></i>
                        <span><?= lang('products'); ?></span>
                    </a>

                    <?php if ($this->session->userdata('store_id')): ?>
                    <a class="nx-q-item" href="<?= site_url('sales'); ?>">
                        <i class="fa fa-line-chart nx-ico-green"></i>
                        <span><?= lang('sales'); ?></span>
                    </a>
                    <a class="nx-q-item" href="<?= site_url('sales/opened'); ?>">
                        <i class="fa fa-clock-o nx-ico-amber"></i>
                        <span><?= lang('opened_bills'); ?></span>
                    </a>
                    <?php endif; ?>

                    <a class="nx-q-item" href="<?= site_url('categories'); ?>">
                        <i class="fa fa-tags nx-ico-teal"></i>
                        <span><?= lang('categories'); ?></span>
                    </a>

                    <a class="nx-q-item" href="<?= site_url('customers'); ?>">
                        <i class="fa fa-users nx-ico-violet"></i>
                        <span><?= lang('customers'); ?></span>
                    </a>

                    <?php if ($Admin): ?>
                    <a class="nx-q-item" href="<?= site_url('purchases'); ?>">
                        <i class="fa fa-truck nx-ico-amber"></i>
                        <span><?= lang('purchases'); ?></span>
                    </a>
                    <a class="nx-q-item" href="<?= site_url('CreditNotes'); ?>">
                        <i class="fa fa-exchange nx-ico-pink"></i>
                        <span><?= lang('credit_notes'); ?></span>
                    </a>
                    <a class="nx-q-item" href="<?= site_url('facturascompras'); ?>">
                        <i class="fa fa-cloud nx-ico-sky"></i>
                        <span>FEC</span>
                    </a>
                    <a class="nx-q-item" href="<?= site_url('dashboard'); ?>">
                        <i class="fa fa-bar-chart nx-ico-indigo"></i>
                        <span>Inicio</span>
                    </a>
                    <a class="nx-q-item" href="<?= site_url('reports/daily_sales'); ?>">
                        <i class="fa fa-calendar-check-o nx-ico-rose"></i>
                        <span>Ventas Diarias</span>
                    </a>
                    <a class="nx-q-item" href="<?= site_url('reports/monthly_sale_tax'); ?>">
                        <i class="fa fa-percent nx-ico-yellow"></i>
                        <span>Ventas c/ IVA</span>
                    </a>
                    <a class="nx-q-item" href="<?= site_url('reports/registers'); ?>">
                        <i class="fa fa-calculator nx-ico-blue"></i>
                        <span>Caja</span>
                    </a>
                    <a class="nx-q-item" href="<?= site_url('reports/credit_customers'); ?>">
                        <i class="fa fa-credit-card nx-ico-indigo"></i>
                        <span>Cta. Clientes</span>
                    </a>
                    <a class="nx-q-item" href="<?= site_url('products/ajuste'); ?>">
                        <i class="fa fa-balance-scale nx-ico-cyan"></i>
                        <span>Ajuste Inv.</span>
                    </a>
                    <a class="nx-q-item" href="<?= site_url('customers'); ?>">
                        <i class="fa fa-address-book-o nx-ico-violet"></i>
                        <span>Lista Clientes</span>
                    </a>
                    <a class="nx-q-item" href="<?= site_url('customers/add'); ?>">
                        <i class="fa fa-user-plus nx-ico-pink"></i>
                        <span>Agregar Cliente</span>
                    </a>
                    <a class="nx-q-item" href="<?= site_url('users'); ?>">
                        <i class="fa fa-list-ul nx-ico-emerald"></i>
                        <span>Lista Usuarios</span>
                    </a>
                    <a class="nx-q-item" href="<?= site_url('users/add'); ?>">
                        <i class="fa fa-user-plus nx-ico-teal"></i>
                        <span>Agregar Usuario</span>
                    </a>
                    <a class="nx-q-item" href="<?= site_url('settings'); ?>">
                        <i class="fa fa-sliders nx-ico-slate"></i>
                        <span><?= lang('settings'); ?></span>
                    </a>
                    <?php if ($this->db->dbdriver != 'sqlite3'): ?>
                    <a class="nx-q-item" href="<?= site_url('settings/backups'); ?>">
                        <i class="fa fa-database nx-ico-orange"></i>
                        <span><?= lang('backups'); ?></span>
                    </a>
                    <?php endif; ?>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>

</div>
</section>

<!-- ═══ JAVASCRIPT: APEXCHARTS ═══ -->
<script>
(function(){
    var isDark = document.documentElement.getAttribute('data-theme') !== 'light';
    var txtColor   = isDark ? '#94a3b8' : '#475569';
    var gridColor  = isDark ? 'rgba(56,189,248,.08)' : 'rgba(14,165,233,.1)';
    var tooltipBg  = isDark ? '#111827' : '#ffffff';
    var accentBlue = isDark ? '#38bdf8' : '#0284c7';

    /* ── Gráfica 1: Ventas mensuales (barras apiladas) ── */
    var salesOpts = {
        chart: {
            type: 'bar',
            height: 290,
            stacked: true,
            background: 'transparent',
            toolbar: { show: false },
            fontFamily: '-apple-system,BlinkMacSystemFont,"Segoe UI",system-ui,sans-serif',
            animations: { enabled: true, speed: 600 }
        },
        theme: { mode: isDark ? 'dark' : 'light' },
        colors: ['#38bdf8','#22c55e','#818cf8'],
        series: [
            { name: '<?= $this->lang->line("sales"); ?>', data: <?= json_encode($salesArr); ?> },
            { name: '<?= $this->lang->line("tax"); ?>',   data: <?= json_encode($taxArr); ?> },
            { name: '<?= $this->lang->line("discount"); ?>',data: <?= json_encode($discountArr); ?> }
        ],
        xaxis: {
            categories: <?= json_encode($months); ?>,
            labels: { style: { colors: txtColor, fontSize: '11px' }, rotate: -30, rotateAlways: false },
            axisBorder: { color: gridColor },
            axisTicks: { color: gridColor }
        },
        yaxis: {
            labels: {
                style: { colors: txtColor, fontSize: '11px' },
                formatter: function(v){ return v >= 1000000 ? (v/1000000).toFixed(1)+'M' : v >= 1000 ? (v/1000).toFixed(0)+'K' : v; }
            }
        },
        grid: { borderColor: gridColor, strokeDashArray: 3 },
        legend: { labels: { colors: txtColor }, position: 'top', fontSize: '12px' },
        plotOptions: { bar: { borderRadius: 4, columnWidth: '62%' } },
        dataLabels: { enabled: false },
        tooltip: {
            theme: isDark ? 'dark' : 'light',
            style: { fontSize: '12px' },
            y: { formatter: function(v){ return '₡ ' + v.toLocaleString('es-CR'); } }
        },
        fill: { opacity: 1 }
    };

    <?php if ($chartData): ?>
    try {
        new ApexCharts(document.querySelector('#nx-chart-sales'), salesOpts).render();
    } catch(e) { document.querySelector('#nx-chart-sales').innerHTML = '<p style="padding:40px;text-align:center;color:'+txtColor+'">Sin datos de ventas</p>'; }
    <?php else: ?>
    document.querySelector('#nx-chart-sales').innerHTML = '<p style="padding:60px 20px;text-align:center;color:'+txtColor+';font-size:13px;">Sin datos de ventas disponibles</p>';
    <?php endif; ?>

    /* ── Gráfica 2: Costo vs Precio inventario (radialBar) ── */
    <?php if ($costAndPriceInv && $invCost + $invPrice > 0): ?>
    var totalInv = <?= $invCost + $invPrice; ?>;
    var pctCost  = totalInv > 0 ? Math.round(<?= $invCost; ?> / totalInv * 100) : 0;
    var pctPrice = totalInv > 0 ? Math.round(<?= $invPrice; ?> / totalInv * 100) : 0;
    new ApexCharts(document.querySelector('#nx-chart-inv'), {
        chart: { type: 'radialBar', height: 290, background: 'transparent', toolbar: { show: false } },
        theme: { mode: isDark ? 'dark' : 'light' },
        series: [pctCost, pctPrice],
        labels: ['<?= $this->lang->line("cost"); ?>', '<?= $this->lang->line("price"); ?>'],
        colors: ['#818cf8', '#22d3ee'],
        plotOptions: {
            radialBar: {
                hollow: { size: '38%' },
                track: { background: isDark ? 'rgba(56,189,248,.08)' : 'rgba(14,165,233,.1)' },
                dataLabels: {
                    name:  { color: txtColor, fontSize: '12px' },
                    value: { color: isDark ? '#e2e8f0' : '#0f172a', fontSize: '16px', fontWeight: 700 },
                    total: {
                        show: true, label: 'Inventario',
                        color: txtColor, fontSize: '11px',
                        formatter: function(){ return '₡ ' + (<?= $invCost + $invPrice; ?> / 1000).toFixed(0) + 'K'; }
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
        chart: { type: 'donut', height: 290, background: 'transparent' },
        theme: { mode: isDark ? 'dark' : 'light' },
        series: <?= json_encode($topProductQty); ?>,
        labels: <?= json_encode($topProductNames); ?>,
        colors: ['#38bdf8','#818cf8','#22d3ee','#22c55e','#eab308','#ef4444','#f97316','#ec4899'],
        dataLabels: { enabled: false },
        legend: {
            show: true, position: 'bottom', fontSize: '12px',
            labels: { colors: txtColor }
        },
        plotOptions: {
            pie: {
                donut: {
                    size: '60%',
                    labels: {
                        show: true,
                        total: {
                            show: true, label: 'Total vendido',
                            color: txtColor, fontSize: '11px',
                            formatter: function(w){ return w.globals.seriesTotals.reduce(function(a,b){return a+b;},0).toLocaleString(); }
                        }
                    }
                }
            }
        },
        stroke: { colors: [isDark ? '#111827' : '#ffffff'], width: 2 },
        tooltip: { theme: isDark ? 'dark' : 'light', style: { fontSize: '12px' } }
    }).render();
    <?php else: ?>
    document.querySelector('#nx-chart-top').innerHTML = '<p style="padding:60px 20px;text-align:center;color:'+txtColor+';font-size:13px;">Sin datos de productos</p>';
    <?php endif; ?>

})();
</script>
