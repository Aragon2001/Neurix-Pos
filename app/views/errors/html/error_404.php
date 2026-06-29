<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>404 — Página no encontrada | NEURIX POS</title>
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
        linear-gradient(rgba(56,189,248,.04) 1px,transparent 1px),
        linear-gradient(90deg,rgba(56,189,248,.04) 1px,transparent 1px);
    background-size:44px 44px;
}
.bg-glow{
    position:fixed;pointer-events:none;
    width:650px;height:650px;border-radius:50%;
    background:radial-gradient(circle,rgba(56,189,248,.07) 0%,transparent 65%);
    top:50%;left:50%;transform:translate(-50%,-50%);
}
.bg-glow2{
    position:fixed;pointer-events:none;
    width:300px;height:300px;border-radius:50%;
    background:radial-gradient(circle,rgba(129,140,248,.06) 0%,transparent 65%);
    top:15%;left:80%;transform:translate(-50%,-50%);
}
.scan{
    position:fixed;left:0;right:0;height:1px;z-index:2;pointer-events:none;
    background:linear-gradient(90deg,transparent,rgba(56,189,248,.55),transparent);
    animation:scan 7s linear infinite;
}
@keyframes scan{from{top:-1px}to{top:100%}}
.particles{position:fixed;inset:0;pointer-events:none;overflow:hidden}
.particles span{
    position:absolute;border-radius:50%;background:#38bdf8;
    animation:drift linear infinite;
}
@keyframes drift{
    from{transform:translateY(0) scale(1);opacity:.4}
    to{transform:translateY(-110vh) translateX(18px) scale(0);opacity:0}
}
.card{
    position:relative;z-index:10;
    background:linear-gradient(145deg,#0d1528 0%,#0f1c32 50%,#111827 100%);
    border:1px solid rgba(56,189,248,.13);border-radius:24px;
    padding:52px 48px 44px;
    max-width:600px;width:calc(100% - 28px);
    text-align:center;
    animation:fadeUp .6s cubic-bezier(.22,1,.36,1) both;
    box-shadow:0 0 0 1px rgba(56,189,248,.04),0 28px 80px rgba(0,0,0,.65);
}
.card::before,.card::after{
    content:'';position:absolute;width:20px;height:20px;
    border-color:rgba(56,189,248,.5);border-style:solid;
}
.card::before{top:14px;left:14px;border-width:2px 0 0 2px;border-radius:4px 0 0 0}
.card::after{bottom:14px;right:14px;border-width:0 2px 2px 0;border-radius:0 0 4px 0}
@keyframes fadeUp{from{opacity:0;transform:translateY(32px)}to{opacity:1;transform:translateY(0)}}
.badge{
    display:inline-flex;align-items:center;gap:8px;
    background:rgba(56,189,248,.08);border:1px solid rgba(56,189,248,.22);
    border-radius:999px;padding:5px 16px;
    font-size:10px;font-weight:700;letter-spacing:2.5px;text-transform:uppercase;
    color:#38bdf8;margin-bottom:22px;
}
.dot{width:6px;height:6px;border-radius:50%;background:#38bdf8;animation:blink 1.4s ease-in-out infinite;}
@keyframes blink{0%,100%{opacity:1}50%{opacity:.1}}
/* Glitch 404 */
.code-wrap{
    position:relative;display:inline-block;
    margin-bottom:8px;
    animation:float 5s ease-in-out infinite;
}
@keyframes float{0%,100%{transform:translateY(0)}50%{transform:translateY(-14px)}}
.code-wrap span{
    display:block;
    font-size:110px;font-weight:900;line-height:1;letter-spacing:-6px;
}
.c-main{
    background:linear-gradient(135deg,#38bdf8 0%,#818cf8 100%);
    -webkit-background-clip:text;background-clip:text;
    -webkit-text-fill-color:transparent;
    position:relative;z-index:3;
}
.c-g1,.c-g2{position:absolute;top:0;left:0;width:100%;}
.c-g1{-webkit-text-fill-color:#f87171;opacity:.45;z-index:2;animation:g1 2.7s infinite linear alternate-reverse;}
.c-g2{-webkit-text-fill-color:#818cf8;opacity:.3;z-index:1;animation:g2 2.0s infinite linear alternate;}
@keyframes g1{
    0%{clip-path:inset(38% 0 60% 0);transform:skewX(-.6deg) translateX(-2px)}
    25%{clip-path:inset(90% 0 2% 0);transform:skewX(.5deg) translateX(3px)}
    50%{clip-path:inset(20% 0 72% 0);transform:skewX(.2deg) translateX(-1px)}
    75%{clip-path:inset(55% 0 10% 0);transform:skewX(-.4deg) translateX(2px)}
    100%{clip-path:inset(5% 0 88% 0);transform:skewX(.3deg) translateX(-3px)}
}
@keyframes g2{
    0%{clip-path:inset(70% 0 5% 0);transform:skewX(.4deg) translateX(3px)}
    33%{clip-path:inset(10% 0 75% 0);transform:skewX(-.3deg) translateX(-2px)}
    66%{clip-path:inset(45% 0 30% 0);transform:skewX(.5deg) translateX(1px)}
    100%{clip-path:inset(85% 0 2% 0);transform:skewX(-.2deg) translateX(-3px)}
}
h1{font-size:21px;font-weight:700;margin-bottom:10px;letter-spacing:-.3px}
.sub{font-size:14px;color:#94a3b8;line-height:1.7;margin-bottom:26px}
.sub p{margin:0}
.detail{
    background:rgba(0,0,0,.3);
    border:1px solid rgba(56,189,248,.1);border-left:3px solid rgba(56,189,248,.45);
    border-radius:10px;padding:13px 16px;margin-bottom:26px;
    text-align:left;font-size:12px;color:#64748b;line-height:1.6;
}
.detail-lbl{
    display:block;font-size:9px;font-weight:700;
    letter-spacing:2px;text-transform:uppercase;
    color:rgba(56,189,248,.65);margin-bottom:5px;
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
    background:linear-gradient(135deg,#38bdf8,#22d3ee);color:#060e1c;
    box-shadow:0 4px 20px rgba(56,189,248,.3);
}
.btn-p:hover{transform:translateY(-2px);box-shadow:0 8px 30px rgba(56,189,248,.45);color:#060e1c;text-decoration:none}
.btn-s{
    background:transparent;color:#94a3b8;
    border:1px solid rgba(56,189,248,.15);
}
.btn-s:hover{border-color:rgba(56,189,248,.4);color:#e2e8f0;text-decoration:none}
.ft{
    margin-top:30px;padding-top:16px;
    border-top:1px solid rgba(56,189,248,.08);
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
    <div class="badge"><span class="dot"></span> Error 404</div>

    <div class="code-wrap">
        <span class="c-g1">404</span>
        <span class="c-g2">404</span>
        <span class="c-main">404</span>
    </div>

    <h1>Página no encontrada</h1>
    <p class="sub">La ruta solicitada no existe en el sistema.<br>
    Verifica la URL o regresa al inicio.</p>

    <?php if (!empty($message)): ?>
    <div class="detail">
        <span class="detail-lbl">Detalle del sistema</span>
        <?php echo $message; ?>
    </div>
    <?php endif; ?>

    <div class="actions">
        <a href="/" class="btn-p">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            Ir al inicio
        </a>
        <a href="javascript:history.back()" class="btn-s">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
            Volver
        </a>
    </div>

    <div class="ft">NEURIX POS &nbsp;·&nbsp; ARASOFT SOLUTIONS</div>
</main>

<script>
(function(){
    var c=document.getElementById('px');
    for(var i=0;i<18;i++){
        var s=document.createElement('span'),sz=(Math.random()*2.5+.8).toFixed(1);
        s.style.cssText='left:'+Math.random()*100+'%;bottom:'+(Math.random()*25)+'%;width:'+sz+'px;height:'+sz+'px;opacity:'+(Math.random()*.4+.08).toFixed(2)+';animation-duration:'+(Math.random()*10+8).toFixed(1)+'s;animation-delay:'+(Math.random()*6).toFixed(1)+'s';
        c.appendChild(s);
    }
})();
</script>
</body>
</html>
