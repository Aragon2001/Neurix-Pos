<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<script>
(function(){
/* ── NEURIX POS — Exception Overlay ── */
var CSS=`
#nx-ov{position:fixed;inset:0;z-index:99999;background:rgba(7,13,26,.94);backdrop-filter:blur(14px);-webkit-backdrop-filter:blur(14px);overflow-y:auto;display:flex;flex-direction:column;align-items:center;gap:16px;padding:32px 20px 40px;animation:nx-fi .3s ease both;}
@keyframes nx-fi{from{opacity:0}to{opacity:1}}
.nx-card{position:relative;background:linear-gradient(145deg,#100d20,#0d1528,#111827);border:1px solid rgba(129,140,248,.18);border-radius:20px;padding:0;max-width:800px;width:100%;box-shadow:0 24px 80px rgba(0,0,0,.7),0 0 0 1px rgba(129,140,248,.05);overflow:hidden;animation:nx-su .4s cubic-bezier(.22,1,.36,1) both;}
@keyframes nx-su{from{opacity:0;transform:translateY(20px) scale(.98)}to{opacity:1;transform:none}}
.nx-card::before,.nx-card::after{content:'';position:absolute;width:16px;height:16px;border-color:rgba(129,140,248,.45);border-style:solid;z-index:1;}
.nx-card::before{top:12px;left:12px;border-width:2px 0 0 2px;border-radius:3px 0 0 0}
.nx-card::after{bottom:12px;right:12px;border-width:0 2px 2px 0;border-radius:0 0 3px 0}
.nx-head{display:flex;align-items:center;gap:12px;padding:18px 22px;background:rgba(129,140,248,.07);border-bottom:1px solid rgba(129,140,248,.12);}
.nx-icon{width:40px;height:40px;border-radius:10px;background:rgba(129,140,248,.12);border:1px solid rgba(129,140,248,.22);display:flex;align-items:center;justify-content:center;color:#818cf8;flex-shrink:0;}
.nx-title-wrap{flex:1;min-width:0;}
.nx-label{font-size:9px;font-weight:700;letter-spacing:2.5px;text-transform:uppercase;color:rgba(129,140,248,.7);margin-bottom:3px;}
.nx-exc-type{font-size:15px;font-weight:700;color:#818cf8;font-family:'Consolas','Monaco',monospace;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.nx-close{margin-left:auto;width:32px;height:32px;border-radius:8px;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);display:flex;align-items:center;justify-content:center;cursor:pointer;color:#64748b;flex-shrink:0;transition:all .15s;font-size:18px;line-height:1;}
.nx-close:hover{background:rgba(239,68,68,.15);border-color:rgba(239,68,68,.3);color:#f87171;}
.nx-body{padding:22px 24px;}
.nx-msg{background:rgba(0,0,0,.35);border:1px solid rgba(129,140,248,.1);border-left:3px solid #818cf8;border-radius:10px;padding:14px 18px;margin-bottom:18px;font-family:'Consolas','Monaco',monospace;font-size:13px;color:#c7d2fe;line-height:1.6;word-break:break-all;}
.nx-msg-lbl{display:block;font-size:9px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:rgba(129,140,248,.65);margin-bottom:6px;font-family:-apple-system,BlinkMacSystemFont,sans-serif;}
.nx-meta{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:18px;}
.nx-meta-item{background:rgba(0,0,0,.25);border:1px solid rgba(129,140,248,.08);border-radius:8px;padding:10px 14px;}
.nx-meta-lbl{font-size:9px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:rgba(129,140,248,.5);margin-bottom:4px;}
.nx-meta-val{font-family:'Consolas','Monaco',monospace;font-size:12px;color:#94a3b8;word-break:break-all;}
.nx-meta-val em{font-style:normal;color:#a5b4fc;}
.nx-trace-toggle{display:inline-flex;align-items:center;gap:7px;background:rgba(129,140,248,.07);border:1px solid rgba(129,140,248,.15);border-radius:8px;padding:8px 16px;font-size:12px;font-weight:600;color:#818cf8;cursor:pointer;transition:all .15s;user-select:none;margin-bottom:14px;}
.nx-trace-toggle:hover{background:rgba(129,140,248,.13);border-color:rgba(129,140,248,.28);}
.nx-trace-toggle svg{transition:transform .2s;}
.nx-trace-toggle.open svg{transform:rotate(90deg);}
.nx-trace{display:none;background:rgba(0,0,0,.4);border:1px solid rgba(129,140,248,.08);border-radius:10px;overflow:hidden;}
.nx-trace.show{display:block;}
.nx-trace-item{padding:10px 16px;border-bottom:1px solid rgba(129,140,248,.06);font-family:'Consolas','Monaco',monospace;font-size:11px;line-height:1.6;color:#4b6180;}
.nx-trace-item:last-child{border-bottom:none}
.nx-trace-item:hover{background:rgba(129,140,248,.04);}
.nx-trace-fn{color:#818cf8;font-weight:600;}
.nx-trace-file{color:#64748b;}
.nx-trace-line{color:#a5b4fc;font-weight:600;}
.nx-actions{display:flex;gap:10px;align-items:center;margin-top:18px;flex-wrap:wrap;}
.nx-btn-copy{display:inline-flex;align-items:center;gap:6px;background:transparent;border:1px solid rgba(129,140,248,.2);color:#818cf8;border-radius:8px;padding:7px 14px;font-size:12px;font-weight:600;cursor:pointer;transition:all .15s;}
.nx-btn-copy:hover{background:rgba(129,140,248,.1);border-color:rgba(129,140,248,.35);}
.nx-btn-home{display:inline-flex;align-items:center;gap:6px;background:linear-gradient(135deg,#818cf8,#a5b4fc);color:#0a0c1e;border:none;border-radius:8px;padding:7px 16px;font-size:12px;font-weight:700;cursor:pointer;text-decoration:none;transition:transform .15s,box-shadow .15s;}
.nx-btn-home:hover{transform:translateY(-1px);box-shadow:0 6px 20px rgba(129,140,248,.38);color:#0a0c1e;text-decoration:none;}
.nx-ts{font-size:10px;color:#334155;margin-left:auto;letter-spacing:.5px;}
`;

if(!document.getElementById('nx-exc-style')){
    var st=document.createElement('style');
    st.id='nx-exc-style';
    st.textContent=CSS;
    document.head.appendChild(st);
}

var ov=document.getElementById('nx-ov');
if(!ov){
    ov=document.createElement('div');
    ov.id='nx-ov';
    document.body.insertBefore(ov,document.body.firstChild);
}

var excType=<?php echo json_encode(get_class($exception)); ?>;
var msg=<?php echo json_encode($exception->getMessage()); ?>;
var file=<?php echo json_encode($exception->getFile()); ?>;
var line=<?php echo json_encode((string)$exception->getLine()); ?>;
var ts=new Date().toLocaleTimeString('es-CR',{hour:'2-digit',minute:'2-digit',second:'2-digit'});

var traceHtml='';
<?php if (defined('SHOW_DEBUG_BACKTRACE') && SHOW_DEBUG_BACKTRACE === TRUE): ?>
<?php foreach ($exception->getTrace() as $i => $err): ?>
<?php if (isset($err['file']) && strpos($err['file'], realpath(BASEPATH)) !== 0): ?>
traceHtml+='<div class="nx-trace-item"><span class="nx-trace-fn"><?php echo htmlspecialchars(isset($err['function']) ? $err['function'] : '?'); ?></span>() &nbsp;<span class="nx-trace-file"><?php echo htmlspecialchars(isset($err['file']) ? $err['file'] : ''); ?></span> : <span class="nx-trace-line"><?php echo isset($err['line']) ? (int)$err['line'] : '?'; ?></span></div>';
<?php endif; ?>
<?php endforeach; ?>
<?php endif; ?>

var uid='nx-exc-'+Date.now();
var card=document.createElement('div');
card.className='nx-card';
card.innerHTML=
    '<div class="nx-head">'+
        '<div class="nx-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg></div>'+
        '<div class="nx-title-wrap"><div class="nx-label">Excepción no capturada</div><div class="nx-exc-type" title="'+excType+'">'+excType+'</div></div>'+
        '<button class="nx-close" onclick="this.closest(\'.nx-card\').remove();if(!document.querySelector(\'.nx-card\')){document.getElementById(\'nx-ov\').remove()}" title="Cerrar">&#215;</button>'+
    '</div>'+
    '<div class="nx-body">'+
        '<div class="nx-msg"><span class="nx-msg-lbl">Mensaje</span>'+escHtml(msg)+'</div>'+
        '<div class="nx-meta">'+
            '<div class="nx-meta-item"><div class="nx-meta-lbl">Archivo</div><div class="nx-meta-val">'+escHtml(file)+'</div></div>'+
            '<div class="nx-meta-item"><div class="nx-meta-lbl">Línea</div><div class="nx-meta-val"><em>'+escHtml(line)+'</em></div></div>'+
        '</div>'+
        (traceHtml?'<div class="nx-trace-toggle" id="'+uid+'-tog" onclick="var t=document.getElementById(\''+uid+'-tr\');var me=this;t.classList.toggle(\'show\');me.classList.toggle(\'open\');me.querySelector(\'span\').textContent=t.classList.contains(\'show\')?\'Ocultar backtrace\':\'Ver backtrace\'"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg><span>Ver backtrace</span></div><div class="nx-trace" id="'+uid+'-tr">'+traceHtml+'</div>':'')+
        '<div class="nx-actions">'+
            '<button class="nx-btn-copy" onclick="copyErr()"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>Copiar</button>'+
            '<a href="/" class="nx-btn-home"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>Inicio</a>'+
            '<span class="nx-ts">'+ts+'</span>'+
        '</div>'+
    '</div>';

card.dataset.nxErr=excType+'|'+msg+'|'+file+'|'+line;
ov.appendChild(card);

function escHtml(s){return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}
function copyErr(){
    var c=document.querySelector('[data-nx-err]');
    if(!c)return;
    var p=c.dataset.nxErr.split('|');
    navigator.clipboard.writeText('Type: '+p[0]+'\nMessage: '+p[1]+'\nFile: '+p[2]+'\nLine: '+p[3]).catch(function(){});
}
})();
</script>
