<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Error de base de datos | NEURIX POS</title>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{
    background:#070d1a;color:#e2e8f0;
    font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',system-ui,sans-serif;
    min-height:100vh;display:flex;align-items:center;justify-content:center;
    overflow:hidden;position:relative;
}
.bg-grid{
    position:fixed;inset:0;pointer-events:none;
    background-image:
        linear-gradient(rgba(239,68,68,.04) 1px,transparent 1px),
        linear-gradient(90deg,rgba(239,68,68,.04) 1px,transparent 1px);
    background-size:44px 44px;
}
.bg-glow{
    position:fixed;pointer-events:none;
    width:650px;height:650px;border-radius:50%;
    background:radial-gradient(circle,rgba(239,68,68,.08) 0%,transparent 65%);
    top:50%;left:50%;transform:translate(-50%,-50%);
}
.bg-glow2{
    position:fixed;pointer-events:none;
    width:280px;height:280px;border-radius:50%;
    background:radial-gradient(circle,rgba(248,113,113,.05) 0%,transparent 65%);
    top:20%;left:15%;transform:translate(-50%,-50%);
}
.scan{
    position:fixed;left:0;right:0;height:1px;z-index:2;pointer-events:none;
    background:linear-gradient(90deg,transparent,rgba(239,68,68,.55),transparent);
    animation:scan 6s linear infinite;
}
@keyframes scan{from{top:-1px}to{top:100%}}
.particles{position:fixed;inset:0;pointer-events:none;overflow:hidden}
.particles span{position:absolute;border-radius:50%;background:#ef4444;animation:drift linear infinite;}
@keyframes drift{
    from{transform:translateY(0) scale(1);opacity:.35}
    to{transform:translateY(-110vh) translateX(18px) scale(0);opacity:0}
}
.card{
    position:relative;z-index:10;
    background:linear-gradient(145deg,#0d1528 0%,#170c0c 50%,#111827 100%);
    border:1px solid rgba(239,68,68,.15);border-radius:24px;
    padding:52px 48px 44px;
    max-width:620px;width:calc(100% - 28px);
    text-align:center;
    animation:fadeUp .6s cubic-bezier(.22,1,.36,1) both;
    box-shadow:0 0 0 1px rgba(239,68,68,.04),0 28px 80px rgba(0,0,0,.65);
}
.card::before,.card::after{
    content:'';position:absolute;width:20px;height:20px;
    border-color:rgba(239,68,68,.5);border-style:solid;
}
.card::before{top:14px;left:14px;border-width:2px 0 0 2px;border-radius:4px 0 0 0}
.card::after{bottom:14px;right:14px;border-width:0 2px 2px 0;border-radius:0 0 4px 0}
@keyframes fadeUp{from{opacity:0;transform:translateY(32px)}to{opacity:1;transform:translateY(0)}}
.badge{
    display:inline-flex;align-items:center;gap:8px;
    background:rgba(239,68,68,.09);border:1px solid rgba(239,68,68,.26);
    border-radius:999px;padding:5px 16px;
    font-size:10px;font-weight:700;letter-spacing:2.5px;text-transform:uppercase;
    color:#ef4444;margin-bottom:24px;
}
.dot{width:6px;height:6px;border-radius:50%;background:#ef4444;animation:blink 1.2s ease-in-out infinite;}
@keyframes blink{0%,100%{opacity:1}50%{opacity:.1}}
.icon-wrap{
    width:80px;height:80px;margin:0 auto 24px;
    animation:float 4.5s ease-in-out infinite;
    color:#ef4444;
    filter:drop-shadow(0 0 16px rgba(239,68,68,.5));
}
@keyframes float{0%,100%{transform:translateY(0)}50%{transform:translateY(-12px)}}
.icon-ring{
    display:flex;align-items:center;justify-content:center;
    width:80px;height:80px;border-radius:50%;
    background:rgba(239,68,68,.08);
    border:1.5px solid rgba(239,68,68,.25);
    position:relative;
}
.icon-ring::before{
    content:'';position:absolute;
    width:100%;height:100%;border-radius:50%;
    border:1.5px solid rgba(239,68,68,.15);
    animation:pulse-ring 2s ease-out infinite;
}
@keyframes pulse-ring{0%{transform:scale(1);opacity:.7}100%{transform:scale(1.6);opacity:0}}
h1{font-size:21px;font-weight:700;margin-bottom:10px;letter-spacing:-.3px}
.sub{font-size:14px;color:#94a3b8;line-height:1.7;margin-bottom:26px}
.sub p{margin:0}
.detail{
    background:rgba(0,0,0,.35);
    border:1px solid rgba(239,68,68,.1);border-left:3px solid rgba(239,68,68,.5);
    border-radius:10px;padding:13px 16px;margin-bottom:26px;
    text-align:left;font-size:12px;color:#6b7280;line-height:1.6;
}
.detail-lbl{
    display:block;font-size:9px;font-weight:700;
    letter-spacing:2px;text-transform:uppercase;
    color:rgba(239,68,68,.7);margin-bottom:5px;
}
.detail code{
    font-family:'Consolas','Monaco',monospace;
    font-size:11px;color:#f87171;
    background:rgba(239,68,68,.08);
    border:1px solid rgba(239,68,68,.15);
    border-radius:4px;padding:2px 6px;
    display:inline-block;margin-top:4px;
}
.actions{display:flex;gap:12px;justify-content:center;flex-wrap:wrap}
.btn-p,.btn-s{
    display:inline-flex;align-items:center;gap:7px;
    font-size:13px;font-weight:600;
    padding:11px 26px;border-radius:10px;
    text-decoration:none;border:none;cursor:pointer;
    transition:transform .15s,box-shadow .15s,border-color .15s;
}
.btn-p{
    background:linear-gradient(135deg,#ef4444,#f87171);color:#fff;
    box-shadow:0 4px 20px rgba(239,68,68,.3);
}
.btn-p:hover{transform:translateY(-2px);box-shadow:0 8px 30px rgba(239,68,68,.48);color:#fff;text-decoration:none}
.btn-s{background:transparent;color:#94a3b8;border:1px solid rgba(239,68,68,.15);}
.btn-s:hover{border-color:rgba(239,68,68,.4);color:#e2e8f0;text-decoration:none}
.ft{
    margin-top:30px;padding-top:16px;
    border-top:1px solid rgba(239,68,68,.08);
    font-size:11px;color:#334155;letter-spacing:.8px;
}
</style>
</head>
<body>
<div class="bg-grid"></div>
<div class="bg-glow"></div>
<div class="bg-glow2"></div>
<div class="scan"></div>
<div class="particles" id="px"></div>

<main class="card">
    <div class="badge"><span class="dot"></span> Error de base de datos</div>

    <div class="icon-wrap">
        <div class="icon-ring">
            <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <ellipse cx="12" cy="5" rx="9" ry="3"/>
                <path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/>
                <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/>
                <line x1="9" y1="10" x2="15" y2="16" stroke="#f87171" stroke-width="2"/>
                <line x1="15" y1="10" x2="9" y2="16" stroke="#f87171" stroke-width="2"/>
            </svg>
        </div>
    </div>

    <h1><?php echo isset($heading) ? $heading : 'Error de base de datos'; ?></h1>
    <p class="sub">Se produjo un error al conectar o consultar la base de datos.<br>
    El equipo técnico ha sido notificado.</p>

    <?php if (!empty($message)): ?>
    <div class="detail">
        <span class="detail-lbl">Detalle del error</span>
        <?php echo $message; ?>
    </div>
    <?php endif; ?>

    <div class="actions">
        <a href="/" class="btn-p">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            Ir al inicio
        </a>
        <a href="javascript:location.reload()" class="btn-s">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 102.13-9.36L1 10"/></svg>
            Reintentar
        </a>
    </div>

    <div class="ft">NEURIX POS &nbsp;·&nbsp; ARASOFT SOLUTIONS</div>
</main>

<script>
(function(){
    var c=document.getElementById('px');
    for(var i=0;i<16;i++){
        var s=document.createElement('span'),sz=(Math.random()*2.5+.8).toFixed(1);
        s.style.cssText='left:'+Math.random()*100+'%;bottom:'+(Math.random()*25)+'%;width:'+sz+'px;height:'+sz+'px;opacity:'+(Math.random()*.35+.07).toFixed(2)+';animation-duration:'+(Math.random()*10+8).toFixed(1)+'s;animation-delay:'+(Math.random()*6).toFixed(1)+'s';
        c.appendChild(s);
    }
})();
</script>
</body>
</html>
