<?php (defined('BASEPATH')) OR exit('No direct script access allowed'); ?>
<?php
if (!function_exists('fmtC')) {
    function fmtC($v, $p = '₡') {
        if (abs($v) >= 1000000) return $p . number_format($v/1000000, 2) . 'M';
        if (abs($v) >= 1000)    return $p . number_format($v/1000, 1) . 'K';
        return $p . number_format($v, 0);
    }
    function pct($part, $total) {
        return $total > 0 ? round($part / $total * 100, 1) : 0;
    }
}

/* ── KPIs ventas ── */
$sTotal  = (float)($sales_period->total    ?? 0);
$sPaid   = (float)($sales_period->paid     ?? 0);
$sTax    = (float)($sales_period->tax      ?? 0);
$sCount  = (int)  ($sales_period->count    ?? 0);
$sLast   = (float)($sales_last->total      ?? 0);
$sPct    = $sLast > 0 ? round(($sTotal - $sLast) / $sLast * 100, 1) : 0;

$stPaid    = (float)($sales_status['paid']    ?? 0);
$stPartial = (float)($sales_status['partial'] ?? 0);
$stDue     = (float)($sales_status['due']     ?? 0);
$stOver    = (float)($sales_status['overdue'] ?? 0);
$stPending = $stPartial + $stDue;
$stPendPct = pct($stPending, $sTotal);
$stOverPct = pct($stOver, $sTotal);
$stPaidPct = pct($stPaid, $sTotal);

/* ── Proyección ventas ── */
$daysTotal   = (int)date('t', strtotime($from));
$daysElapsed = max(1, min((int)date('d'), $daysTotal));
$daysLeft    = max(0, $daysTotal - $daysElapsed);
$dailyAvg    = $daysElapsed > 0 ? $sTotal / $daysElapsed : 0;
$projection  = $sTotal + ($dailyAvg * $daysLeft);
$projPct     = $projection > 0 ? min(100, round($sTotal / $projection * 100)) : 100;

/* ── Compras / Por pagar ── */
$pTotal = (float)($purchases_kpi->total ?? 0);
$pCount = (int)  ($purchases_kpi->count ?? 0);

/* ── C×C ── */
$arTotal   = (float)($accounts_rec->total   ?? 0);
$arPaid    = (float)($accounts_rec->paid    ?? 0);
$arBalance = (float)($accounts_rec->balance ?? 0);
$arCount   = (int)  ($accounts_rec->count   ?? 0);
$arPaidPct = pct($arPaid, $arTotal);
$arPendPct = pct($arBalance, $arTotal);

/* ── FE ── */
$feTotal  = (int)($fe_stats['total']      ?? 0);
$feAcept  = (int)($fe_stats['aceptado']   ?? 0);
$feRech   = (int)($fe_stats['rechazado']  ?? 0);
$feProc   = (int)($fe_stats['procesando'] ?? 0);
$feErr    = (int)($fe_stats['error']      ?? 0);
$feAcPct  = pct($feAcept, $feTotal);
$feRePct  = pct($feRech,  $feTotal);
$fePrPct  = pct($feProc,  $feTotal);
$feFE     = (int)($fe_stats['fe_acept']   ?? 0);
$feFeRech = (int)($fe_stats['fe_rech']    ?? 0);
$feTiq    = (int)($fe_stats['tiq_acept']  ?? 0);
$feTiqR   = (int)($fe_stats['tiq_rech']   ?? 0);
$feNC     = (int)($fe_stats['nc_acept']   ?? 0);

/* ── Inventario ── */
$invCost  = (float)($inv_value->cost_val  ?? 0);
$invPrice = (float)($inv_value->price_val ?? 0);
$invTax   = (float)($inv_value->tax_val   ?? 0);
$invProds = (int)  ($inv_value->total_products ?? 0);
$invUtil  = $invPrice - $invCost;
$invMarg  = pct($invUtil, $invPrice);
$lowCnt   = count($low_stock ?? []);

/* ── Gráficas: datos JSON ── */
$fin12m  = $finance_12m ?? [];
$fLabels = $fSales = $fPurch = $fProfit = $fCobrado = [];
foreach ($fin12m as $r) { $fLabels[] = $r['month']; $fSales[] = $r['sales']; $fPurch[] = $r['purchases']; $fProfit[] = $r['profit']; $fCobrado[] = $r['cobrado']; }

$avg6m = $inv_avg_6m ?? [];
$avgL = $avgV = $avgC = [];
foreach ($avg6m as $r) { $avgL[] = $r['month']; $avgV[] = $r['total']; $avgC[] = $r['count']; }

$topDayL = $topDayV = [];
foreach (($top_days ?? []) as $r) { $topDayL[] = $r->label ?? ''; $topDayV[] = (float)($r->total ?? 0); }

$topPN = $topPV = [];
foreach (($top_products ?? []) as $r) { $topPN[] = mb_substr($r->product_name ?? '', 0, 24); $topPV[] = (float)($r->revenue ?? 0); }

$payMap = ['cash'=>'Efectivo','cc'=>'Tarjeta','cheque'=>'Cheque','gift_card'=>'Gift Card','stripe'=>'Stripe','other'=>'Otro'];
$payL = $payV = [];
foreach (($pay_methods ?? []) as $r) { $payL[] = $payMap[$r->paid_by] ?? ucfirst($r->paid_by); $payV[] = (float)$r->total; }

$custN = $custT = [];
foreach (($top_customers ?? []) as $r) { $custN[] = mb_substr($r->customer_name, 0, 22); $custT[] = (float)$r->total; }

/* ── URL builder ── */
$baseUrl = site_url('dashboard');
$storeParam = $current_store ? "&store={$current_store}" : '';
function periodUrl($p, $base, $sp) { return $base . '?period=' . $p . $sp; }

$periodLabels = ['today' => 'Hoy', 'week' => 'Semana', 'month' => 'Mes', 'year' => 'Año'];
?>
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.49.0/dist/apexcharts.min.js"></script>

<style>
/* ── Toolbar ── */
.nx-toolbar {
    display:flex; align-items:center; flex-wrap:wrap; gap:10px;
    background:var(--nx-bg3); border:1px solid var(--nx-border);
    border-radius:14px; padding:12px 18px; margin-bottom:18px;
}
.nx-toolbar-sec { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
.nx-toolbar-sec + .nx-toolbar-sec { margin-left:auto; }
.nx-store-sel {
    background:var(--nx-bg4); border:1px solid var(--nx-border2);
    color:var(--nx-txt1); border-radius:8px; padding:6px 12px;
    font-size:13px; font-weight:600; cursor:pointer; outline:none;
    min-width:170px;
}
.nx-period-pills { display:flex; gap:4px; }
.nx-period-pill {
    padding:5px 14px; border-radius:20px; font-size:12px; font-weight:600;
    border:1px solid var(--nx-border2); color:var(--nx-txt3);
    text-decoration:none !important; transition:all .18s;
}
.nx-period-pill:hover { color:var(--nx-a1); border-color:var(--nx-a1); background:rgba(56,189,248,.07); }
.nx-period-pill.active { background:var(--nx-a1); border-color:var(--nx-a1); color:#fff !important; }
.nx-date-form { display:flex; align-items:center; gap:6px; }
.nx-date-form input[type=date] {
    background:var(--nx-bg4); border:1px solid var(--nx-border2);
    color:var(--nx-txt1); border-radius:8px; padding:5px 9px;
    font-size:12px; outline:none;
}
.nx-date-form input[type=date]:focus { border-color:var(--nx-a1); }
.nx-date-sep { color:var(--nx-txt3); font-size:12px; }

/* ── Accesos rápidos ── */
.nx-qr-outer { position:relative; margin-bottom:18px; }
.nx-qr-wrap {
    display:flex; gap:8px; overflow-x:auto; padding:12px 0 10px;
    scrollbar-width:none;
}
.nx-qr-wrap::-webkit-scrollbar { display:none; }
.nx-qr-item {
    flex-shrink:0;
    display:flex; flex-direction:column; align-items:center; gap:6px;
    background:var(--nx-bg3); border:1px solid var(--nx-border);
    border-radius:13px; padding:14px 16px 10px;
    text-decoration:none !important; color:var(--nx-txt2) !important;
    transition:all .2s; min-width:78px;
}
.nx-qr-item:hover {
    background:rgba(56,189,248,.07); border-color:var(--nx-a1);
    color:var(--nx-a1) !important; transform:translateY(-2px);
    box-shadow:0 6px 18px rgba(56,189,248,.12);
}
[data-theme="light"] .nx-qr-item:hover { background:rgba(14,165,233,.07); box-shadow:0 6px 16px rgba(14,165,233,.12); }
.nx-qr-ico {
    width:42px; height:42px; border-radius:11px;
    background:var(--nx-bg5); display:flex; align-items:center;
    justify-content:center; font-size:19px; transition:background .2s;
}
.nx-qr-item:hover .nx-qr-ico { background:rgba(56,189,248,.13); }
.nx-qr-lbl { font-size:11px; font-weight:600; white-space:nowrap; text-align:center; }

/* ── KPI Cards ── */
.nx-kpi {
    background:var(--nx-bg3); border:1px solid var(--nx-border);
    border-radius:16px; padding:20px; margin-bottom:18px;
    transition:transform .2s, box-shadow .2s;
    position:relative; overflow:hidden;
}
.nx-kpi:hover { transform:translateY(-3px); box-shadow:0 12px 32px rgba(0,0,0,.22); }
[data-theme="light"] .nx-kpi:hover { box-shadow:0 12px 26px rgba(0,0,0,.09); }
.nx-kpi-top { display:flex; align-items:flex-start; justify-content:space-between; gap:10px; }
.nx-kpi-left { flex:1; min-width:0; }
.nx-kpi-badge { display:inline-flex; align-items:center; gap:3px;
    font-size:11px; font-weight:700; border-radius:6px; padding:2px 8px; }
.nx-kpi-label { font-size:11px; font-weight:700; text-transform:uppercase;
    letter-spacing:.7px; color:var(--nx-txt3); margin-bottom:7px; }
.nx-kpi-value { font-size:28px; font-weight:900; color:var(--nx-txt1);
    letter-spacing:-.6px; line-height:1; margin-bottom:6px; }
.nx-kpi-sub  { font-size:12px; color:var(--nx-txt3); margin-bottom:10px; }
.nx-kpi-foot { display:flex; gap:6px; flex-wrap:wrap; margin-top:4px; }
.nx-kpi-trend { font-size:11px; font-weight:600;
    display:inline-flex; align-items:center; gap:3px; }
.nx-kpi-donut { width:110px; flex-shrink:0; }
.nx-kpi-sep { height:1px; background:var(--nx-border); margin:12px -20px; }
.nx-kpi-statrow { display:flex; justify-content:space-between; gap:8px; }
.nx-kpi-stat { text-align:center; flex:1; }
.nx-kpi-stat-val { font-size:15px; font-weight:800; }
.nx-kpi-stat-lbl { font-size:10px; color:var(--nx-txt3); text-transform:uppercase; letter-spacing:.5px; }

/* ── Secciones ── */
.nx-section-title {
    font-size:13px; font-weight:700; text-transform:uppercase; letter-spacing:.8px;
    color:var(--nx-txt3); margin:0 0 14px; display:flex; align-items:center; gap:8px;
}
.nx-section-title i { color:var(--nx-a1); font-size:14px; }

/* ── FE Cards ── */
.nx-fe-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:10px; margin-bottom:14px; }
@media(max-width:768px){ .nx-fe-grid { grid-template-columns:repeat(2,1fr); } }
.nx-fe-card {
    border-radius:12px; padding:14px 12px; text-align:center;
    border:1px solid var(--nx-border);
}
.nx-fe-val { font-size:26px; font-weight:900; line-height:1; margin-bottom:4px; }
.nx-fe-lbl { font-size:10px; color:var(--nx-txt3); text-transform:uppercase; letter-spacing:.6px; }

/* ── Inv cards ── */
.nx-inv-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:10px; margin-bottom:16px; }
@media(max-width:768px){ .nx-inv-grid { grid-template-columns:repeat(2,1fr); } }
.nx-inv-card {
    background:var(--nx-bg4); border:1px solid var(--nx-border);
    border-radius:12px; padding:14px 14px 12px;
}
.nx-inv-card-ico { font-size:18px; margin-bottom:8px; }
.nx-inv-card-val { font-size:18px; font-weight:800; color:var(--nx-txt1); line-height:1; }
.nx-inv-card-lbl { font-size:10px; color:var(--nx-txt3); text-transform:uppercase; letter-spacing:.5px; margin-top:3px; }

/* ── Progress bar ── */
.nx-progbar { height:6px; border-radius:6px; background:var(--nx-bg5); overflow:hidden; margin:6px 0; }
.nx-progbar-fill { height:100%; border-radius:6px; transition:width .6s ease; }

/* ── Table clean ── */
.nx-tbl { width:100%; border-collapse:collapse; }
.nx-tbl th { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.6px;
    color:var(--nx-txt3); padding:9px 12px; border-bottom:2px solid var(--nx-border2);
    background:var(--nx-bg4); }
.nx-tbl td { font-size:12.5px; color:var(--nx-txt1); padding:9px 12px;
    border-bottom:1px solid var(--nx-border); vertical-align:middle; }
.nx-tbl tr:last-child td { border-bottom:none; }
.nx-tbl tr:hover td { background:rgba(56,189,248,.03); }
</style>

<section class="content" style="padding:16px 20px 30px;">

<!-- ════════════════════════════════════════════
     TOOLBAR: Empresa · Período · Fecha · Acciones
═════════════════════════════════════════════ -->
<div class="nx-toolbar">

    <!-- Selector de tienda -->
    <div class="nx-toolbar-sec">
        <i class="fa fa-building-o" style="color:var(--nx-txt3);"></i>
        <select class="nx-store-sel" onchange="location.href=this.value">
            <option value="<?= $baseUrl . '?period=' . $period; ?>">Todas las tiendas</option>
            <?php foreach (($stores ?? []) as $st): ?>
            <option value="<?= $baseUrl . '?period=' . $period . '&store=' . $st->id; ?>"
                    <?= $current_store == $st->id ? 'selected' : ''; ?>>
                <?= htmlspecialchars($st->name); ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Pestañas de período -->
    <div class="nx-toolbar-sec">
        <div class="nx-period-pills">
            <?php foreach ($periodLabels as $key => $lbl): ?>
            <a href="<?= $baseUrl . '?period=' . $key . $storeParam; ?>"
               class="nx-period-pill <?= $period === $key ? 'active' : ''; ?>">
                <?= $lbl; ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Rango personalizado -->
    <div class="nx-toolbar-sec">
        <form class="nx-date-form" method="GET" action="<?= $baseUrl; ?>">
            <input type="hidden" name="period" value="custom">
            <?php if ($current_store): ?><input type="hidden" name="store" value="<?= $current_store; ?>"><?php endif; ?>
            <input type="date" name="from" value="<?= $from; ?>">
            <span class="nx-date-sep">→</span>
            <input type="date" name="to" value="<?= $to; ?>">
            <button type="submit" class="btn btn-primary btn-xs" style="border-radius:8px !important;padding:6px 12px !important;">
                <i class="fa fa-search"></i> Aplicar
            </button>
        </form>
    </div>

    <!-- Acciones -->
    <div class="nx-toolbar-sec" style="margin-left:auto;">
        <button onclick="nxToggleTheme()" class="nx-theme-btn" title="Cambiar tema" style="border-radius:8px !important;">
            <i class="fa fa-adjust"></i>
        </button>
        <a href="<?= current_url() . '?' . ($_SERVER['QUERY_STRING'] ?? ''); ?>"
           class="btn btn-default btn-xs" style="border-radius:8px !important;padding:6px 10px !important;" title="Actualizar">
            <i class="fa fa-refresh"></i>
        </a>
        <span style="font-size:11px;color:var(--nx-txt3);padding-left:6px;">
            <?= date('d M Y'); ?> · <?php
            if ($period === 'today')  echo 'Hoy';
            elseif ($period === 'week')  echo 'Esta semana';
            elseif ($period === 'month') echo date('F Y');
            elseif ($period === 'year')  echo date('Y');
            else echo $from . ' → ' . $to;
            ?>
        </span>
    </div>

</div>

<!-- ════════════════════════════════════════════
     ACCESOS RÁPIDOS
═════════════════════════════════════════════ -->
<div class="nx-qr-outer">
    <div class="nx-qr-wrap" id="nxQR">

        <?php
        /* [url, icon, label, bg-color, ico-color] */
        $qrItems = [
            ['pos',                'fa-shopping-bag',   lang('pos'),              'rgba(34,211,238,.14)',  '#22d3ee'],
            ['sales',              'fa-line-chart',     lang('sales'),            'rgba(34,197,94,.14)',   '#4ade80'],
            ['products',           'fa-cube',           lang('products'),         'rgba(249,115,22,.14)',  '#fb923c'],
            ['customers',          'fa-user-circle-o',  lang('customers'),        'rgba(139,92,246,.14)',  '#a78bfa'],
            ['purchases',          'fa-truck',          lang('purchases'),        'rgba(245,158,11,.14)',  '#fbbf24'],
            ['CreditNotes',        'fa-exchange',       lang('credit_notes'),     'rgba(236,72,153,.14)',  '#f472b6'],
            ['facturascompras',    'fa-cloud',          'FEC / Electrónico',      'rgba(56,189,248,.14)',  '#38bdf8'],
            ['reports/daily_sales','fa-area-chart',     lang('reports'),          'rgba(244,63,94,.14)',   '#fb7185'],
            ['suppliers',          'fa-industry',       lang('list_suppliers'),   'rgba(20,184,166,.14)',  '#2dd4bf'],
            ['categories',         'fa-tags',           lang('categories'),       'rgba(20,184,166,.14)',  '#2dd4bf'],
            ['users',              'fa-users',          lang('list_users'),       'rgba(16,185,129,.14)',  '#34d399'],
            ['settings',           'fa-sliders',        lang('settings'),         'rgba(100,116,139,.14)', '#94a3b8'],
            ['settings/backups',   'fa-database',       lang('backups'),          'rgba(56,189,248,.14)',  '#38bdf8'],
            ['reports/top_products','fa-trophy',        lang('top_products'),     'rgba(234,179,8,.14)',   '#fbbf24'],
        ];
        foreach ($qrItems as $qi): ?>
        <a href="<?= site_url($qi[0]); ?>" class="nx-qr-item">
            <div class="nx-qr-ico" style="background:<?= $qi[3]; ?>;color:<?= $qi[4]; ?>;"><i class="fa <?= $qi[1]; ?>"></i></div>
            <span class="nx-qr-lbl"><?= $qi[2]; ?></span>
        </a>
        <?php endforeach; ?>

    </div>
</div>

<!-- ════════════════════════════════════════════
     KPI CARDS: Ventas · Proyección · Compras · C×C
═════════════════════════════════════════════ -->
<div class="row">

    <!-- ① Total ventas -->
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="nx-kpi" style="border-top:3px solid var(--nx-a1);">
            <div class="nx-kpi-top">
                <div class="nx-kpi-left">
                    <div class="nx-kpi-label"><i class="fa fa-line-chart"></i> &nbsp;Total ventas</div>
                    <div class="nx-kpi-value"><?= fmtC($sTotal); ?></div>
                    <div class="nx-kpi-sub"><?= $sCount; ?> facturas</div>
                    <div class="nx-kpi-foot">
                        <?php if ($sPct >= 0): ?>
                        <span class="nx-kpi-trend" style="color:var(--nx-ok);"><i class="fa fa-caret-up"></i> +<?= $sPct; ?>%</span>
                        <?php else: ?>
                        <span class="nx-kpi-trend" style="color:var(--nx-err);"><i class="fa fa-caret-down"></i> <?= $sPct; ?>%</span>
                        <?php endif; ?>
                        <span style="font-size:11px;color:var(--nx-txt3);">vs período anterior</span>
                    </div>
                </div>
                <div class="nx-kpi-donut" id="kd-ventas"></div>
            </div>
            <div class="nx-kpi-sep"></div>
            <div class="nx-kpi-statrow">
                <div class="nx-kpi-stat">
                    <div class="nx-kpi-stat-val" style="color:var(--nx-a1);"><?= $stPaidPct; ?>%</div>
                    <div class="nx-kpi-stat-lbl">Cobrado</div>
                </div>
                <div class="nx-kpi-stat">
                    <div class="nx-kpi-stat-val" style="color:var(--nx-warn);"><?= $stPendPct; ?>%</div>
                    <div class="nx-kpi-stat-lbl">Pendiente</div>
                </div>
                <div class="nx-kpi-stat">
                    <div class="nx-kpi-stat-val" style="color:var(--nx-err);"><?= $stOverPct; ?>%</div>
                    <div class="nx-kpi-stat-lbl">Vencido</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ② Proyección de ventas -->
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="nx-kpi" style="border-top:3px solid var(--nx-a2);">
            <div class="nx-kpi-top">
                <div class="nx-kpi-left">
                    <div class="nx-kpi-label"><i class="fa fa-rocket"></i> &nbsp;Proyección mes</div>
                    <div class="nx-kpi-value"><?= fmtC($projection); ?></div>
                    <div class="nx-kpi-sub">Promedio/día: <?= fmtC($dailyAvg); ?></div>
                    <div class="nx-kpi-foot">
                        <span style="font-size:11px;color:var(--nx-txt3);"><?= $daysLeft; ?> días restantes</span>
                    </div>
                </div>
                <div class="nx-kpi-donut" id="kd-proj"></div>
            </div>
            <div class="nx-kpi-sep"></div>
            <div class="nx-kpi-statrow">
                <div class="nx-kpi-stat">
                    <div class="nx-kpi-stat-val" style="color:var(--nx-a2);"><?= $projPct; ?>%</div>
                    <div class="nx-kpi-stat-lbl">Avance</div>
                </div>
                <div class="nx-kpi-stat">
                    <div class="nx-kpi-stat-val" style="color:var(--nx-txt2);"><?= fmtC($sTotal); ?></div>
                    <div class="nx-kpi-stat-lbl">Actual</div>
                </div>
                <div class="nx-kpi-stat">
                    <div class="nx-kpi-stat-val" style="color:var(--nx-txt3);"><?= fmtC($projection - $sTotal); ?></div>
                    <div class="nx-kpi-stat-lbl">Faltante</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ③ Total por pagar (compras) -->
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="nx-kpi" style="border-top:3px solid var(--nx-warn);">
            <div class="nx-kpi-top">
                <div class="nx-kpi-left">
                    <div class="nx-kpi-label"><i class="fa fa-truck"></i> &nbsp;Total por pagar</div>
                    <div class="nx-kpi-value" style="color:var(--nx-warn);"><?= fmtC($pTotal); ?></div>
                    <div class="nx-kpi-sub"><?= $pCount; ?> órdenes de compra</div>
                    <div class="nx-kpi-foot">
                        <span style="font-size:11px;color:var(--nx-txt3);">Gastos: <?= fmtC((float)($expenses_kpi->total ?? 0)); ?></span>
                    </div>
                </div>
                <div class="nx-kpi-donut" id="kd-pagar"></div>
            </div>
            <div class="nx-kpi-sep"></div>
            <div class="nx-kpi-statrow">
                <div class="nx-kpi-stat">
                    <div class="nx-kpi-stat-val" style="color:var(--nx-warn);"><?= fmtC($pTotal); ?></div>
                    <div class="nx-kpi-stat-lbl">Compras</div>
                </div>
                <div class="nx-kpi-stat">
                    <div class="nx-kpi-stat-val" style="color:var(--nx-err);"><?= fmtC((float)($expenses_kpi->total ?? 0)); ?></div>
                    <div class="nx-kpi-stat-lbl">Gastos</div>
                </div>
                <div class="nx-kpi-stat">
                    <div class="nx-kpi-stat-val"><?= $pCount; ?></div>
                    <div class="nx-kpi-stat-lbl">Órdenes</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ④ Total por cobrar -->
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="nx-kpi" style="border-top:3px solid var(--nx-err);">
            <div class="nx-kpi-top">
                <div class="nx-kpi-left">
                    <div class="nx-kpi-label"><i class="fa fa-dollar"></i> &nbsp;Total por cobrar</div>
                    <div class="nx-kpi-value" style="color:var(--nx-err);"><?= fmtC($arBalance); ?></div>
                    <div class="nx-kpi-sub"><?= $arCount; ?> facturas sin saldar</div>
                    <div class="nx-kpi-foot">
                        <span class="nx-kpi-badge" style="background:rgba(239,68,68,.1);color:#f87171;">
                            <i class="fa fa-exclamation-circle"></i> <?= $arCount; ?> pendientes
                        </span>
                    </div>
                </div>
                <div class="nx-kpi-donut" id="kd-cobrar"></div>
            </div>
            <div class="nx-kpi-sep"></div>
            <div class="nx-kpi-statrow">
                <div class="nx-kpi-stat">
                    <div class="nx-kpi-stat-val" style="color:var(--nx-ok);"><?= $arPaidPct; ?>%</div>
                    <div class="nx-kpi-stat-lbl">Cobrado</div>
                </div>
                <div class="nx-kpi-stat">
                    <div class="nx-kpi-stat-val" style="color:var(--nx-err);"><?= $arPendPct; ?>%</div>
                    <div class="nx-kpi-stat-lbl">Pendiente</div>
                </div>
                <div class="nx-kpi-stat">
                    <div class="nx-kpi-stat-val"><?= fmtC($arTotal); ?></div>
                    <div class="nx-kpi-stat-lbl">Total facturado</div>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- ════════════════════════════════════════════
     ANÁLISIS FINANCIERO (gráfica 12 meses)
═════════════════════════════════════════════ -->
<div class="box" style="border-top:3px solid var(--nx-a1) !important;margin-bottom:18px;">
    <div class="box-header" style="display:flex;align-items:center;justify-content:space-between;">
        <div style="display:flex;align-items:center;gap:8px;">
            <i class="fa fa-area-chart" style="color:var(--nx-a1);font-size:16px;"></i>
            <span class="nx-section-title" style="margin:0;">Análisis financiero — últimos 12 meses</span>
        </div>
        <div style="display:flex;gap:16px;font-size:12px;">
            <span style="color:var(--nx-a1);"><i class="fa fa-circle"></i> Ventas</span>
            <span style="color:#818cf8;"><i class="fa fa-circle"></i> Compras</span>
            <span style="color:#22c55e;"><i class="fa fa-circle"></i> Utilidad</span>
        </div>
    </div>
    <div class="box-body" style="padding:8px 14px 8px !important;">
        <div id="nx-fin-12m" style="min-height:300px;"></div>
    </div>
</div>

<!-- ════════════════════════════════════════════
     FACTURAS ELECTRÓNICAS
═════════════════════════════════════════════ -->
<div class="box" style="border-top:3px solid var(--nx-a3) !important;margin-bottom:18px;">
    <div class="box-header" style="display:flex;align-items:center;justify-content:space-between;">
        <div style="display:flex;align-items:center;gap:8px;">
            <i class="fa fa-cloud" style="color:var(--nx-a3);font-size:16px;"></i>
            <span class="nx-section-title" style="margin:0;">Facturación Electrónica — Hacienda</span>
        </div>
        <?php if ($feRech > 0): ?>
        <a href="<?= site_url('reports/sale_fe'); ?>" class="btn btn-xs btn-warning" style="border-radius:8px !important;">
            <i class="fa fa-refresh"></i> Reenviar rechazadas (<?= $feRech; ?>)
        </a>
        <?php endif; ?>
    </div>
    <div class="box-body">

        <!-- Métricas FE row -->
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:14px;margin-bottom:18px;">

            <div style="background:var(--nx-bg4);border:1px solid var(--nx-border);border-radius:12px;padding:16px;">
                <div style="font-size:11px;color:var(--nx-txt3);text-transform:uppercase;letter-spacing:.6px;margin-bottom:8px;">Total emitidas</div>
                <div style="font-size:30px;font-weight:900;color:var(--nx-txt1);line-height:1;"><?= number_format($feTotal); ?></div>
                <div style="font-size:11px;color:var(--nx-txt3);margin-top:4px;">documentos en período</div>
            </div>

            <div style="background:rgba(34,197,94,.07);border:1px solid rgba(34,197,94,.2);border-radius:12px;padding:16px;">
                <div style="font-size:11px;color:var(--nx-txt3);text-transform:uppercase;letter-spacing:.6px;margin-bottom:8px;">Tasa de éxito</div>
                <div style="font-size:30px;font-weight:900;color:#4ade80;line-height:1;"><?= $feAcPct; ?>%</div>
                <div class="nx-progbar"><div class="nx-progbar-fill" style="width:<?= $feAcPct; ?>%;background:linear-gradient(90deg,#166534,#22c55e);"></div></div>
                <div style="font-size:11px;color:var(--nx-txt3);"><?= number_format($feAcept); ?> aceptadas</div>
            </div>

            <div style="background:rgba(239,68,68,.07);border:1px solid rgba(239,68,68,.2);border-radius:12px;padding:16px;">
                <div style="font-size:11px;color:var(--nx-txt3);text-transform:uppercase;letter-spacing:.6px;margin-bottom:8px;">Tasa de rechazo</div>
                <div style="font-size:30px;font-weight:900;color:#f87171;line-height:1;"><?= $feRePct; ?>%</div>
                <div class="nx-progbar"><div class="nx-progbar-fill" style="width:<?= $feRePct; ?>%;background:linear-gradient(90deg,#991b1b,#ef4444);"></div></div>
                <div style="font-size:11px;color:var(--nx-txt3);"><?= number_format($feRech); ?> rechazadas</div>
            </div>

            <div style="background:rgba(234,179,8,.07);border:1px solid rgba(234,179,8,.2);border-radius:12px;padding:16px;">
                <div style="font-size:11px;color:var(--nx-txt3);text-transform:uppercase;letter-spacing:.6px;margin-bottom:8px;">Procesando / Error</div>
                <div style="font-size:30px;font-weight:900;color:var(--nx-warn);line-height:1;"><?= $feProc + $feErr; ?></div>
                <div style="font-size:11px;color:var(--nx-txt3);margin-top:8px;">
                    <span style="color:#fde047;"><?= $feProc; ?> procesando</span> &nbsp;·&nbsp;
                    <span style="color:#fb923c;"><?= $feErr; ?> error</span>
                </div>
            </div>

        </div>

        <!-- Desglose por tipo de documento -->
        <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:10px;">

            <div class="nx-fe-card" style="background:rgba(56,189,248,.07);border-color:rgba(56,189,248,.2);">
                <div class="nx-fe-val" style="color:var(--nx-a1);"><?= number_format($feFE); ?></div>
                <div class="nx-fe-lbl">FE Aceptadas<br><small>(Tipo 01)</small></div>
            </div>
            <div class="nx-fe-card" style="background:rgba(239,68,68,.07);border-color:rgba(239,68,68,.2);">
                <div class="nx-fe-val" style="color:#f87171;"><?= number_format($feFeRech); ?></div>
                <div class="nx-fe-lbl">FE Rechazadas<br><small>(Tipo 01)</small></div>
            </div>
            <div class="nx-fe-card" style="background:rgba(34,211,238,.07);border-color:rgba(34,211,238,.2);">
                <div class="nx-fe-val" style="color:var(--nx-a3);"><?= number_format($feTiq); ?></div>
                <div class="nx-fe-lbl">Tiquetes Aprobados<br><small>(Tipo 04)</small></div>
            </div>
            <div class="nx-fe-card" style="background:rgba(249,115,22,.07);border-color:rgba(249,115,22,.2);">
                <div class="nx-fe-val" style="color:#fb923c;"><?= number_format($feTiqR); ?></div>
                <div class="nx-fe-lbl">Tiquetes Rechazados<br><small>(Tipo 04)</small></div>
            </div>
            <div class="nx-fe-card" style="background:rgba(129,140,248,.07);border-color:rgba(129,140,248,.2);">
                <div class="nx-fe-val" style="color:var(--nx-a2);"><?= number_format($feNC); ?></div>
                <div class="nx-fe-lbl">Notas de Crédito<br><small>(Tipo 03)</small></div>
            </div>

        </div>

    </div>
</div>

<!-- ════════════════════════════════════════════
     INVENTARIO
═════════════════════════════════════════════ -->
<div class="box" style="border-top:3px solid var(--nx-a2) !important;margin-bottom:18px;">
    <div class="box-header">
        <i class="fa fa-cubes" style="color:var(--nx-a2);font-size:16px;margin-right:8px;"></i>
        <span class="nx-section-title" style="margin:0;">Inventario</span>
    </div>
    <div class="box-body">

        <!-- 4 métricas -->
        <div class="nx-inv-grid">

            <div class="nx-inv-card" style="border-top:2px solid #818cf8;">
                <div class="nx-inv-card-ico" style="color:#818cf8;"><i class="fa fa-money"></i></div>
                <div class="nx-inv-card-val"><?= fmtC($invCost); ?></div>
                <div class="nx-inv-card-lbl">Costo total</div>
            </div>

            <div class="nx-inv-card" style="border-top:2px solid var(--nx-warn);">
                <div class="nx-inv-card-ico" style="color:var(--nx-warn);"><i class="fa fa-percent"></i></div>
                <div class="nx-inv-card-val"><?= fmtC($invTax); ?></div>
                <div class="nx-inv-card-lbl">Impuestos (IVA)</div>
            </div>

            <div class="nx-inv-card" style="border-top:2px solid var(--nx-a1);">
                <div class="nx-inv-card-ico" style="color:var(--nx-a1);"><i class="fa fa-tag"></i></div>
                <div class="nx-inv-card-val"><?= fmtC($invPrice); ?></div>
                <div class="nx-inv-card-lbl">Precio de venta</div>
            </div>

            <div class="nx-inv-card" style="border-top:2px solid var(--nx-ok);">
                <div class="nx-inv-card-ico" style="color:var(--nx-ok);"><i class="fa fa-line-chart"></i></div>
                <div class="nx-inv-card-val" style="color:var(--nx-ok);"><?= fmtC($invUtil); ?></div>
                <div class="nx-inv-card-lbl">Utilidad bruta (<?= $invMarg; ?>%)</div>
            </div>

        </div>

        <!-- Barra de margen -->
        <div style="background:var(--nx-bg4);border:1px solid var(--nx-border);border-radius:10px;padding:14px;margin-bottom:16px;">
            <div style="display:flex;justify-content:space-between;margin-bottom:8px;">
                <span style="font-size:12px;color:var(--nx-txt2);font-weight:600;">Margen bruto del inventario</span>
                <span style="font-size:14px;font-weight:800;color:var(--nx-ok);"><?= $invMarg; ?>%</span>
            </div>
            <div class="nx-progbar" style="height:8px;">
                <div class="nx-progbar-fill" style="width:<?= min(100,$invMarg); ?>%;background:linear-gradient(90deg,#166534,#22c55e,#4ade80);"></div>
            </div>
            <div style="display:flex;justify-content:space-between;margin-top:7px;font-size:11px;color:var(--nx-txt3);">
                <span>Costo: <?= fmtC($invCost); ?></span>
                <span><?= $invProds; ?> productos en stock</span>
                <span>Precio: <?= fmtC($invPrice); ?></span>
            </div>
        </div>

        <!-- Promedio ventas 6m + stock bajo alerta -->
        <div class="row">
            <div class="col-md-8">
                <div style="font-size:12px;font-weight:700;color:var(--nx-txt3);text-transform:uppercase;letter-spacing:.6px;margin-bottom:8px;">
                    Promedio de ventas — últimos 6 meses
                </div>
                <div id="nx-inv-avg6m" style="min-height:180px;"></div>
            </div>
            <div class="col-md-4">
                <div style="font-size:12px;font-weight:700;color:var(--nx-txt3);text-transform:uppercase;letter-spacing:.6px;margin-bottom:8px;">
                    Resumen de stock <span style="color:var(--nx-err);"><?= $lowCnt > 0 ? "· $lowCnt bajo alerta" : ''; ?></span>
                </div>
                <?php if (!empty($low_stock)): ?>
                <div class="table-responsive">
                <table class="nx-tbl" style="font-size:11.5px;">
                    <thead><tr><th>Producto</th><th>Stock</th><th>Mín</th></tr></thead>
                    <tbody>
                    <?php foreach (array_slice($low_stock, 0, 6) as $ls): ?>
                    <tr>
                        <td style="max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($ls->name); ?></td>
                        <td><span style="background:<?= $ls->stock<=0?'rgba(239,68,68,.15)':'rgba(234,179,8,.12)';?>;color:<?= $ls->stock<=0?'#f87171':'#fde047';?>;border-radius:5px;padding:1px 7px;font-weight:700;"><?= (int)$ls->stock; ?></span></td>
                        <td style="color:var(--nx-txt3);"><?= (int)$ls->alert_quantity; ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
                <?php else: ?>
                <div style="padding:20px;text-align:center;color:#4ade80;font-size:13px;">
                    <i class="fa fa-check-circle" style="font-size:22px;display:block;margin-bottom:6px;"></i>
                    Sin alertas de inventario
                </div>
                <?php endif; ?>
                <!-- Desglose por tasa IVA -->
                <?php if (!empty($inv_by_tax)): ?>
                <div class="table-responsive">
                <table class="nx-tbl" style="margin-top:10px;font-size:11px;">
                    <thead><tr><th>Tasa</th><th>Costo</th><th>Precio</th></tr></thead>
                    <tbody>
                    <?php foreach ($inv_by_tax as $it): ?>
                    <tr>
                        <td><span style="background:rgba(234,179,8,.12);color:var(--nx-warn);border-radius:4px;padding:1px 6px;font-weight:700;"><?= $it->tax; ?>%</span></td>
                        <td><?= fmtC((float)$it->cost_val); ?></td>
                        <td><?= fmtC((float)$it->price_val); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<!-- ════════════════════════════════════════════
     ANÁLISIS DE VENTAS: Top días · Top productos
═════════════════════════════════════════════ -->
<div class="row" style="margin-bottom:18px;">

    <div class="col-12 col-md-6">
        <div class="box box-success" style="margin-bottom:0;">
            <div class="box-header">
                <i class="fa fa-calendar-check-o" style="color:var(--nx-ok);margin-right:8px;"></i>
                <h3 class="box-title">Top días de venta</h3>
            </div>
            <div class="box-body" style="padding:8px 14px 4px !important;">
                <div id="nx-top-days" style="min-height:280px;"></div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6">
        <div class="box box-primary" style="margin-bottom:0;">
            <div class="box-header">
                <i class="fa fa-trophy" style="color:var(--nx-a1);margin-right:8px;"></i>
                <h3 class="box-title">Top productos por ingresos</h3>
                <div class="box-tools float-end">
                    <span style="font-size:11px;color:var(--nx-txt3);"><?= date('F Y'); ?></span>
                </div>
            </div>
            <div class="box-body" style="padding:8px 14px 4px !important;">
                <div id="nx-top-prods" style="min-height:280px;"></div>
            </div>
        </div>
    </div>

</div>

<!-- ════════════════════════════════════════════
     FILA FINAL: Métodos pago · Top clientes · Últimas ventas
═════════════════════════════════════════════ -->
<div class="row">

    <!-- Métodos de pago -->
    <div class="col-12 col-md-3">
        <div class="box box-warning">
            <div class="box-header">
                <i class="fa fa-credit-card" style="color:var(--nx-warn);margin-right:8px;"></i>
                <h3 class="box-title">Métodos de pago</h3>
            </div>
            <div class="box-body" style="padding:8px 14px 4px !important;">
                <div id="nx-pay-donut" style="min-height:240px;"></div>
                <?php if (!empty($pay_methods)): ?>
                <div class="table-responsive">
                <table class="nx-tbl" style="margin-top:6px;">
                    <?php
                    $pmMap = ['cash'=>'Efectivo','cc'=>'Tarjeta','cheque'=>'Cheque','gift_card'=>'Gift Card','stripe'=>'Stripe','other'=>'Otro'];
                    $pmTotal = array_sum($payV);
                    foreach ($pay_methods as $pm): ?>
                    <tr>
                        <td style="font-size:12px;"><?= $pmMap[$pm->paid_by] ?? ucfirst($pm->paid_by); ?></td>
                        <td style="text-align:right;font-weight:700;font-size:12px;"><?= fmtC((float)$pm->total); ?></td>
                        <td style="text-align:right;color:var(--nx-txt3);font-size:11px;"><?= pct((float)$pm->total, $pmTotal); ?>%</td>
                    </tr>
                    <?php endforeach; ?>
                </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Top clientes -->
    <div class="col-12 col-md-4">
        <div class="box box-info">
            <div class="box-header">
                <i class="fa fa-users" style="color:var(--nx-a3);margin-right:8px;"></i>
                <h3 class="box-title">Top clientes — <?= date('F'); ?></h3>
            </div>
            <div class="box-body" style="padding:0 !important;overflow:hidden;">
                <?php if (!empty($top_customers)): ?>
                <div class="table-responsive">
                <table class="nx-tbl">
                    <thead><tr><th>#</th><th>Cliente</th><th style="text-align:right;">Total</th></tr></thead>
                    <tbody>
                    <?php $ci=1; foreach ($top_customers as $tc): ?>
                    <tr>
                        <td style="color:var(--nx-txt3);font-size:11px;width:28px;"><?= $ci++; ?></td>
                        <td style="max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                            <?= htmlspecialchars($tc->customer_name); ?>
                            <div style="font-size:10px;color:var(--nx-txt3);"><?= $tc->count; ?> facturas</div>
                        </td>
                        <td style="text-align:right;font-weight:700;color:var(--nx-a1);"><?= fmtC((float)$tc->total); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
                <?php else: ?>
                <p style="padding:20px;text-align:center;color:var(--nx-txt3);">Sin datos</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Últimas ventas -->
    <div class="col-12 col-md-5">
        <div class="box box-primary">
            <div class="box-header">
                <i class="fa fa-list-alt" style="color:var(--nx-a1);margin-right:8px;"></i>
                <h3 class="box-title">Últimas transacciones</h3>
                <div class="box-tools float-end">
                    <a href="<?= site_url('sales'); ?>" class="btn btn-xs btn-default">Ver todas</a>
                </div>
            </div>
            <div class="box-body" style="padding:0 !important;overflow:hidden;">
                <div class="table-responsive">
                <table class="nx-tbl">
                    <thead><tr><th>#</th><th>Cliente</th><th style="text-align:right;">Total</th><th>Estado</th><th>FE</th></tr></thead>
                    <tbody>
                    <?php if (!empty($recent_sales)): ?>
                    <?php foreach ($recent_sales as $rs): ?>
                    <tr>
                        <td style="font-weight:700;color:var(--nx-a1);font-size:11px;"><?= $rs->id; ?></td>
                        <td style="max-width:110px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:12px;">
                            <?= htmlspecialchars($rs->customer_name); ?>
                            <div style="font-size:10px;color:var(--nx-txt3);"><?= date('d/m H:i', strtotime($rs->date)); ?></div>
                        </td>
                        <td style="text-align:right;font-weight:700;font-size:12px;">₡<?= number_format($rs->grand_total, 0); ?></td>
                        <td><?php
                        $sb = ['paid'=>['#4ade80','rgba(34,197,94,.12)','Pagado'],'partial'=>['#fde047','rgba(234,179,8,.12)','Parcial'],'due'=>['#f87171','rgba(239,68,68,.1)','Pendiente'],'overdue'=>['#f87171','rgba(239,68,68,.18)','Vencida']];
                        $sc = $sb[$rs->status] ?? ['#94a3b8','rgba(148,163,184,.1)',ucfirst($rs->status)];
                        echo '<span style="background:'.$sc[1].';color:'.$sc[0].';border-radius:5px;padding:2px 7px;font-size:10px;font-weight:700;">'.$sc[2].'</span>';
                        ?></td>
                        <td><?php
                        $fe = $rs->estatus_hacienda ?? null;
                        if ($fe==='aceptado')    echo '<i class="fa fa-check-circle" style="color:#4ade80;"></i>';
                        elseif ($fe==='rechazado') echo '<i class="fa fa-times-circle" style="color:#f87171;"></i>';
                        elseif ($fe==='procesando')echo '<i class="fa fa-clock-o" style="color:#fde047;"></i>';
                        else echo '<i class="fa fa-minus" style="color:var(--nx-txt3);"></i>';
                        ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <tr><td colspan="5" style="text-align:center;color:var(--nx-txt3);padding:24px;">Sin ventas recientes</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>

</div>

</section>

<!-- ════════════════════════════════════════════
     APEXCHARTS — Todas las gráficas
═════════════════════════════════════════════ -->
<script>
(function(){
    var dark   = document.documentElement.getAttribute('data-bs-theme') !== 'light';
    var txt    = dark ? '#94a3b8' : '#475569';
    var txt1   = dark ? '#e2e8f0' : '#0f172a';
    var grid   = dark ? 'rgba(56,189,248,.07)' : 'rgba(14,165,233,.09)';
    var bg3    = dark ? '#111827' : '#ffffff';
    var tm     = dark ? 'dark' : 'light';
    var ff     = '-apple-system,BlinkMacSystemFont,"Segoe UI",system-ui,sans-serif';
    var noData = { text:'Sin datos', style:{ color:txt, fontSize:'12px' } };
    var stroke0= { colors:[bg3], width:2 };
    var yl0    = { labels:{ style:{ colors:txt, fontSize:'11px' }, formatter:function(v){ return v>=1000000?(v/1e6).toFixed(1)+'M':v>=1000?(v/1e3).toFixed(0)+'K':v; } } };
    var xl0    = { labels:{ style:{ colors:txt, fontSize:'10px' }, rotate:-30 }, axisBorder:{ color:grid }, axisTicks:{ color:grid } };
    var leg0   = { labels:{ colors:txt }, fontSize:'12px' };
    var tip0   = { theme:tm, style:{ fontSize:'12px' }, y:{ formatter:function(v){ return '₡ '+v.toLocaleString('es-CR'); } } };
    var gr0    = { borderColor:grid, strokeDashArray:3 };

    function mkDonut(el, series, labels, colors, h, total, sub) {
        new ApexCharts(document.querySelector(el), {
            chart:{ type:'donut', height:h||120, background:'transparent', fontFamily:ff, sparkline:{ enabled:false } },
            theme:{ mode:tm },
            series: series,
            labels: labels,
            colors: colors,
            dataLabels:{ enabled:false },
            legend:{ show:false },
            plotOptions:{ pie:{ donut:{ size:'72%',
                labels:{ show:!!total, total:{ show:!!total, label:sub||'', color:txt, fontSize:'10px',
                    formatter:function(){ return total||''; } } } } } },
            stroke: stroke0,
            tooltip:{ theme:tm, style:{ fontSize:'11px' }, y:{ formatter:function(v,w){ return labels[w.seriesIndex]+': ₡'+v.toLocaleString('es-CR'); } } },
            noData:{ text:' ' }
        }).render();
    }

    /* ── KPI Donuts ── */
    mkDonut('#kd-ventas',
        [<?= $stPaid; ?>, <?= $stPending; ?>, <?= $stOver; ?>],
        ['Cobrado','Pendiente','Vencido'],
        ['#38bdf8','#eab308','#ef4444'], 115, '<?= fmtC($sTotal); ?>', 'Ventas');

    mkDonut('#kd-proj',
        [<?= $sTotal; ?>, <?= max(0,$projection-$sTotal); ?>],
        ['Actual','Restante'],
        ['#818cf8','rgba(129,140,248,.2)'], 115, '<?= $projPct; ?>%', 'Avance');

    mkDonut('#kd-pagar',
        [<?= max(0,$pTotal); ?>, <?= max(0,(float)($expenses_kpi->total??0)); ?>],
        ['Compras','Gastos'],
        ['#eab308','#ef4444'], 115, '<?= fmtC($pTotal+((float)($expenses_kpi->total??0))); ?>', 'Total');

    mkDonut('#kd-cobrar',
        [<?= max(0,$arPaid); ?>, <?= max(0,$arBalance); ?>],
        ['Cobrado','Pendiente'],
        ['#22c55e','#ef4444'], 115, '<?= $arPendPct; ?>%', 'Pendiente');

    /* ── Análisis financiero 12 meses ── */
    new ApexCharts(document.querySelector('#nx-fin-12m'), {
        chart:{ type:'area', height:300, background:'transparent', toolbar:{ show:false }, fontFamily:ff, animations:{ speed:600 } },
        theme:{ mode:tm },
        series:[
            { name:'Ventas',   data: <?= json_encode($fSales); ?>   },
            { name:'Compras',  data: <?= json_encode($fPurch); ?>   },
            { name:'Utilidad', data: <?= json_encode($fProfit); ?>  }
        ],
        colors:['#38bdf8','#818cf8','#22c55e'],
        xaxis: Object.assign({ categories: <?= json_encode($fLabels); ?> }, xl0),
        yaxis: yl0,
        grid: gr0,
        legend: Object.assign({}, leg0, { position:'top' }),
        dataLabels:{ enabled:false },
        stroke:{ curve:'smooth', width:2 },
        fill:{ type:'gradient', gradient:{ opacityFrom:.3, opacityTo:.03 } },
        tooltip: tip0,
        noData: noData
    }).render();

    /* ── Promedio ventas 6m (inventario) ── */
    new ApexCharts(document.querySelector('#nx-inv-avg6m'), {
        chart:{ type:'bar', height:180, background:'transparent', toolbar:{ show:false }, fontFamily:ff },
        theme:{ mode:tm },
        series:[{ name:'Ventas', data: <?= json_encode($avgV); ?> }],
        colors:['#818cf8'],
        xaxis: Object.assign({ categories: <?= json_encode($avgL); ?> }, xl0, { labels:{ style:{ colors:txt, fontSize:'11px' }, rotate:0 } }),
        yaxis: yl0,
        grid: gr0,
        plotOptions:{ bar:{ borderRadius:5, columnWidth:'55%' } },
        dataLabels:{ enabled:false },
        tooltip: tip0,
        noData: noData
    }).render();

    /* ── Top días ── */
    var tdL = <?= json_encode(array_reverse($topDayL)); ?>;
    var tdV = <?= json_encode(array_reverse($topDayV)); ?>;
    new ApexCharts(document.querySelector('#nx-top-days'), {
        chart:{ type:'bar', height:280, background:'transparent', toolbar:{ show:false }, fontFamily:ff },
        theme:{ mode:tm },
        series:[{ name:'Ventas', data:tdV }],
        colors:['#22c55e'],
        xaxis:{ categories:tdL, labels:{ style:{ colors:txt, fontSize:'10px' } } },
        yaxis: yl0,
        grid: gr0,
        plotOptions:{ bar:{ horizontal:true, borderRadius:4, barHeight:'60%' } },
        dataLabels:{ enabled:false },
        tooltip: tip0,
        noData: noData
    }).render();

    /* ── Top productos ── */
    var tpN = <?= json_encode(array_reverse($topPN)); ?>;
    var tpV = <?= json_encode(array_reverse($topPV)); ?>;
    new ApexCharts(document.querySelector('#nx-top-prods'), {
        chart:{ type:'bar', height:280, background:'transparent', toolbar:{ show:false }, fontFamily:ff },
        theme:{ mode:tm },
        series:[{ name:'Ingresos', data:tpV }],
        colors:['#38bdf8'],
        xaxis:{ categories:tpN, labels:{ style:{ colors:txt, fontSize:'10px' } } },
        yaxis: yl0,
        grid: gr0,
        plotOptions:{ bar:{ horizontal:true, borderRadius:4, barHeight:'60%' } },
        dataLabels:{ enabled:false },
        tooltip: tip0,
        noData: noData
    }).render();

    /* ── Métodos de pago (donut) ── */
    new ApexCharts(document.querySelector('#nx-pay-donut'), {
        chart:{ type:'donut', height:240, background:'transparent', fontFamily:ff },
        theme:{ mode:tm },
        series: <?= json_encode($payV ?: [1]); ?>,
        labels: <?= json_encode($payL ?: ['Sin datos']); ?>,
        colors:['#38bdf8','#818cf8','#22c55e','#eab308','#f97316','#94a3b8'],
        dataLabels:{ enabled:false },
        legend:{ show:false },
        plotOptions:{ pie:{ donut:{ size:'62%',
            labels:{ show:true, total:{ show:true, label:'Total', color:txt, fontSize:'11px',
                formatter:function(w){ var t=w.globals.seriesTotals.reduce(function(a,b){return a+b;},0); return t>=1000000?'₡'+(t/1e6).toFixed(1)+'M':'₡'+(t/1e3).toFixed(0)+'K'; } } } } } },
        stroke: stroke0,
        tooltip:{ theme:tm, style:{ fontSize:'12px' }, y:{ formatter:function(v){ return '₡ '+v.toLocaleString('es-CR'); } } },
        noData: noData
    }).render();

    /* ── Scroll horizontal accesos ── */
    (function(){
        var qr = document.getElementById('nxQR');
        if (!qr) return;
        var isDown=false, sx=0, sl=0;
        qr.addEventListener('mousedown', function(e){ isDown=true; qr.style.cursor='grabbing'; sx=e.pageX-qr.offsetLeft; sl=qr.scrollLeft; });
        document.addEventListener('mouseup', function(){ isDown=false; qr.style.cursor=''; });
        qr.addEventListener('mousemove', function(e){ if(!isDown) return; e.preventDefault(); var x=e.pageX-qr.offsetLeft; qr.scrollLeft=sl-(x-sx)*1.2; });
    })();

})();
</script>
