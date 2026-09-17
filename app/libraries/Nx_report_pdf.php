<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Plantilla corporativa de informes en PDF sobre mPDF.
 *
 * Recibe el mismo documento de informe que el exportador de Excel y la
 * pantalla, así que las tres salidas no pueden discrepar: si hay una cifra de
 * menos en el PDF es porque falta en el documento, no porque el PDF la calcule
 * por su cuenta.
 *
 * La orientación no se pide: se deduce del ancho que declaran las columnas. Una
 * tabla de catorce columnas en vertical sale con los números partidos, y esa es
 * la queja habitual de un informe exportado.
 */
class Nx_report_pdf
{
    /** Ancho útil de un A4 en milímetros, descontados los márgenes. */
    const UTIL_VERTICAL   = 186;
    const UTIL_HORIZONTAL = 273;

    /**
     * Fuente del informe.
     *
     * Se nombra en vez de dejar el alias `sans-serif`: es la que se comprobó que
     * dibuja el colón (U+20A1) tanto en redonda como en negrita. `FreeSansBold`,
     * por ejemplo, no lo lleva.
     */
    const FUENTE = 'dejavusanscondensed';

    /** @var array documento de informe */
    private $doc;

    /** @var object ajustes del sistema */
    private $ajustes;

    /** @var \Mpdf\Mpdf|null instancia viva, para consultar métricas de la fuente */
    private $mpdf = null;

    /** @var string|null símbolo ya resuelto; null mientras no se ha comprobado */
    private $simbolo = null;

    public function __construct($params = array())
    {
        $this->ajustes = isset($params['ajustes']) ? $params['ajustes'] : null;
    }

    /**
     * Genera el PDF.
     *
     * @param  array  $doc     documento de informe (ver `Report_engine`)
     * @param  string $destino `I` abrir en el navegador, `D` descargar, o una ruta
     * @return string|null ruta escrita cuando `$destino` es una ruta
     */
    public function generar(array $doc, $destino = 'I')
    {
        $this->doc = $doc + array(
            'titulo' => 'Informe', 'subtitulo' => '', 'folio' => '', 'meta' => array(),
            'filtros' => array(), 'kpis' => array(), 'columnas' => array(), 'filas' => array(),
            'totales' => array(), 'analisis' => array(), 'auditoria' => array(),
            'confiabilidad' => null, 'nota' => '',
        );

        $horizontal = $this->_anchoTabla() > self::UTIL_VERTICAL;

        $mpdf = new \Mpdf\Mpdf(array(
            'tempDir'        => sys_get_temp_dir(),
            'format'         => $horizontal ? 'A4-L' : 'A4',
            'margin_left'    => 12,
            'margin_right'   => 12,
            'margin_top'     => 14,
            'margin_bottom'  => 16,
            'margin_footer'  => 8,
            'CSSselectMedia' => 'print',
        ));

        $this->mpdf    = $mpdf;
        $this->simbolo = null;

        // La fuente se fija antes de medir: sobre una instancia recien creada
        // todas las metricas devuelven el mismo valor por defecto y la
        // comprobacion del simbolo daria un falso positivo.
        $mpdf->SetFont(self::FUENTE);
        $this->_sustituirSimbolo();

        $mpdf->SetTitle($this->doc['titulo']);
        $mpdf->SetAuthor('Neurix POS');
        $mpdf->SetCreator('Neurix POS');

        // El folio va en el pie de todas las páginas: es lo que permite volver a
        // la bitácora y reconstruir con qué filtros se generó este papel.
        $pie = '<table width="100%" style="font-size:7pt;color:#64748b;border-top:0.4pt solid #cbd5e1;padding-top:2mm">'
             . '<tr><td>' . $this->e($this->doc['folio']) . '</td>'
             . '<td align="center">' . $this->e($this->empresa()) . '</td>'
             . '<td align="right">Página {PAGENO} de {nbpg}</td></tr></table>';
        $mpdf->SetHTMLFooter($pie);

        $mpdf->WriteHTML($this->css(), \Mpdf\HTMLParserMode::HEADER_CSS);
        $mpdf->WriteHTML($this->cuerpo(), \Mpdf\HTMLParserMode::HTML_BODY);

        $nombre = $this->nombreArchivo();

        if ($destino === 'I' || $destino === 'D') {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            $mpdf->Output($nombre, $destino);
            return null;
        }

        $mpdf->Output($destino, 'F');
        return $destino;
    }

    /**
     * El HTML que recibe mPDF, sin generar el PDF.
     *
     * Existe para poder afirmar sobre el contenido en las pruebas: el PDF sale
     * con las fuentes en subconjunto y su texto no se puede leer con una
     * expresión regular, así que la comprobación de que el papel dice lo mismo
     * que la pantalla se hace sobre esta cadena, que es de donde sale el PDF.
     */
    public function html(array $doc)
    {
        $this->simbolo = null;
        $this->doc = $doc + array(
            'titulo' => 'Informe', 'subtitulo' => '', 'folio' => '', 'meta' => array(),
            'filtros' => array(), 'kpis' => array(), 'columnas' => array(), 'filas' => array(),
            'totales' => array(), 'analisis' => array(), 'auditoria' => array(),
            'confiabilidad' => null, 'nota' => '',
        );
        return $this->css() . $this->cuerpo();
    }

    // ═══════════════════════════════════════════════════════════════════
    //  COMPOSICIÓN
    // ═══════════════════════════════════════════════════════════════════

    private function cuerpo()
    {
        $h = $this->encabezado();

        if ($this->doc['kpis']) {
            $h .= $this->bloqueKpis();
        }
        if ($this->doc['confiabilidad']) {
            $h .= $this->bloqueConfiabilidad();
        }
        if ($this->doc['filtros']) {
            $h .= $this->bloqueFiltros();
        }
        if ($this->doc['analisis']) {
            $h .= $this->bloqueAnalisis();
        }

        $h .= $this->bloqueTabla();

        if ($this->doc['auditoria']) {
            $h .= $this->bloqueAuditoria();
        }
        if ($this->doc['nota']) {
            $h .= '<p class="nota">' . $this->e($this->doc['nota']) . '</p>';
        }

        return $h;
    }

    private function encabezado()
    {
        $logo = $this->logo();
        $meta = '';
        foreach ($this->doc['meta'] as $k => $v) {
            if ($v === null || $v === '') {
                continue;
            }
            $meta .= '<tr><td class="mk">' . $this->e($k) . '</td><td class="mv">' . $this->e($v) . '</td></tr>';
        }
        // El folio va también en el cuerpo y no solo en el pie: una fotocopia
        // recortada o una impresión sin pies deja el papel sin forma de volver
        // a la bitácora que dice con qué filtros salió.
        if ($this->doc['folio'] !== '') {
            $meta .= '<tr><td class="mk">Folio</td><td class="mv">' . $this->e($this->doc['folio']) . '</td></tr>';
        }

        return '<table class="cab"><tr>'
            . '<td class="cab-l">'
            . ($logo ? '<img src="' . $logo . '" class="logo">' : '')
            . '<div class="marca">NEURIX POS</div>'
            . '<div class="emp">' . $this->e($this->empresa()) . '</div>'
            . ($this->cedula() ? '<div class="ced">Céd. ' . $this->e($this->cedula()) . '</div>' : '')
            . '</td>'
            . '<td class="cab-r">'
            . '<div class="tit">' . $this->e($this->doc['titulo']) . '</div>'
            . ($this->doc['subtitulo'] ? '<div class="sub">' . $this->e($this->doc['subtitulo']) . '</div>' : '')
            . '<table class="meta">' . $meta . '</table>'
            . '</td></tr></table><div class="regla"></div>';
    }

    private function bloqueKpis()
    {
        // Cuatro por fila: con más, el número se encoge hasta ser ilegible.
        $celdas = array();
        foreach ($this->doc['kpis'] as $k) {
            $tono = isset($k['tono']) ? $k['tono'] : '';
            $celdas[] = '<td class="kpi ' . $this->e($tono) . '">'
                . '<div class="kpi-e">' . $this->e($k['etiqueta']) . '</div>'
                . '<div class="kpi-v">' . $this->e($k['texto']) . '</div>'
                . (isset($k['pie']) && $k['pie'] !== '' ? '<div class="kpi-p">' . $this->e($k['pie']) . '</div>' : '')
                . '</td>';
        }

        $h = '<div class="bt">Indicadores del período</div>';
        foreach (array_chunk($celdas, 4) as $fila) {
            while (count($fila) < 4) {
                $fila[] = '<td class="kpi vacio"></td>';
            }
            $h .= '<table class="kpis"><tr>' . implode('', $fila) . '</tr></table>';
        }
        return $h;
    }

    private function bloqueConfiabilidad()
    {
        $c = $this->doc['confiabilidad'];
        $h = '<table class="conf ' . $this->e($c['tono']) . '"><tr>'
           . '<td class="conf-v">' . number_format((float) $c['pct'], 1, ',', '.') . ' %</td>'
           . '<td class="conf-t"><b>Índice de confiabilidad</b><br>' . $this->e($c['resumen']) . '</td>'
           . '</tr></table>';
        return $h;
    }

    private function bloqueFiltros()
    {
        $h = '<div class="bt">Filtros aplicados</div><table class="filtros">';
        $par = array();
        foreach ($this->doc['filtros'] as $k => $v) {
            $par[] = '<b>' . $this->e($k) . ':</b> ' . $this->e($v);
        }
        foreach (array_chunk($par, 3) as $fila) {
            $h .= '<tr>';
            foreach ($fila as $c) {
                $h .= '<td>' . $c . '</td>';
            }
            for ($i = count($fila); $i < 3; $i++) {
                $h .= '<td></td>';
            }
            $h .= '</tr>';
        }
        return $h . '</table>';
    }

    private function bloqueAnalisis()
    {
        $h = '<div class="bt">Resumen ejecutivo</div><ul class="analisis">';
        foreach ($this->doc['analisis'] as $t) {
            $h .= '<li>' . $this->e($t) . '</li>';
        }
        return $h . '</ul>';
    }

    private function bloqueTabla()
    {
        $cols = $this->doc['columnas'];
        if (!$cols) {
            return '';
        }

        $total = $this->_anchoTabla();
        $th    = '';
        foreach ($cols as $c) {
            $pct = $total > 0 ? round($this->_ancho($c) / $total * 100, 3) : 0;
            $th .= '<th width="' . $pct . '%" class="' . $this->alineacion($c) . '">'
                 . $this->e($c['titulo']) . '</th>';
        }

        $tb = '';
        foreach ($this->doc['filas'] as $r) {
            $tb .= '<tr>';
            foreach ($cols as $c) {
                $v    = isset($r[$c['clave']]) ? $r[$c['clave']] : '';
                $tono = isset($c['tono']) && is_callable($c['tono']) ? call_user_func($c['tono'], $r) : '';
                $tb  .= '<td class="' . $this->alineacion($c) . ' ' . $this->e($tono) . '">'
                      . $this->e($this->formatear($v, $c)) . '</td>';
            }
            $tb .= '</tr>';
        }

        if (!$this->doc['filas']) {
            $tb = '<tr><td colspan="' . count($cols) . '" class="vacia">'
                . 'El período y los filtros seleccionados no devolvieron ningún registro.</td></tr>';
        }

        $tf = '';
        if ($this->doc['totales']) {
            $tf = '<tr class="tot">';
            $primera = true;
            foreach ($cols as $c) {
                $k = $c['clave'];
                if (array_key_exists($k, $this->doc['totales'])) {
                    $tf .= '<td class="' . $this->alineacion($c) . '">'
                         . $this->e($this->formatear($this->doc['totales'][$k], $c)) . '</td>';
                } else {
                    $tf .= '<td class="' . $this->alineacion($c) . '">' . ($primera ? 'TOTALES' : '') . '</td>';
                }
                $primera = false;
            }
            $tf .= '</tr>';
        }

        $n = count($this->doc['filas']);
        return '<div class="bt">Detalle <span class="cnt">' . number_format($n, 0, ',', '.')
            . ' ' . ($n === 1 ? 'registro' : 'registros') . '</span></div>'
            . '<table class="datos"><thead><tr>' . $th . '</tr></thead>'
            . '<tbody>' . $tb . '</tbody>'
            . ($tf ? '<tfoot>' . $tf . '</tfoot>' : '')
            . '</table>';
    }

    private function bloqueAuditoria()
    {
        $h = '<div class="bt salto">Observaciones de auditoría</div>'
           . '<table class="datos aud"><thead><tr>'
           . '<th width="12%">Nivel</th><th width="26%">Hallazgo</th><th width="16%">Documento</th>'
           . '<th width="14%">Monto</th><th width="32%">Acción recomendada</th></tr></thead><tbody>';

        foreach ($this->doc['auditoria'] as $a) {
            $h .= '<tr>'
                . '<td class="c ' . $this->e(isset($a['tono']) ? $a['tono'] : '') . '">'
                . $this->e(isset($a['nivel']) ? $a['nivel'] : '') . '</td>'
                . '<td>' . $this->e(isset($a['descripcion']) ? $a['descripcion'] : '') . '</td>'
                . '<td>' . $this->e(isset($a['documento']) ? $a['documento'] : '') . '</td>'
                . '<td class="r">' . $this->e(isset($a['monto']) ? $this->moneda($a['monto']) : '') . '</td>'
                . '<td>' . $this->e(isset($a['accion']) ? $a['accion'] : '') . '</td>'
                . '</tr>';
        }
        return $h . '</tbody></table>';
    }

    // ═══════════════════════════════════════════════════════════════════
    //  FORMATO
    // ═══════════════════════════════════════════════════════════════════

    /** Ancho declarado de una columna, con un mínimo razonable. */
    private function _ancho($c)
    {
        return isset($c['ancho']) && $c['ancho'] > 0 ? (float) $c['ancho'] : 22;
    }

    private function _anchoTabla()
    {
        $t = 0;
        foreach ($this->doc['columnas'] as $c) {
            $t += $this->_ancho($c);
        }
        return $t;
    }

    private function alineacion($c)
    {
        $f = isset($c['formato']) ? $c['formato'] : '';
        if (in_array($f, array('moneda', 'numero', 'entero', 'porcentaje'), true)) {
            return 'r';
        }
        if (in_array($f, array('fecha', 'fechahora', 'estado'), true)) {
            return 'c';
        }
        return '';
    }

    private function formatear($v, $c)
    {
        $f = isset($c['formato']) ? $c['formato'] : '';
        switch ($f) {
            case 'moneda':     return $this->moneda($v);
            case 'numero':     return number_format((float) $v, 2, ',', '.');
            case 'entero':     return number_format((float) $v, 0, ',', '.');
            case 'porcentaje': return number_format((float) $v, 2, ',', '.') . ' %';
            case 'fecha':      return $v ? date('d/m/Y', strtotime($v)) : '';
            case 'fechahora':  return $v ? date('d/m/Y H:i', strtotime($v)) : '';
            default:           return $v;
        }
    }

    private function moneda($v)
    {
        $d = $this->ajustes && isset($this->ajustes->decimals) ? (int) $this->ajustes->decimals : 2;
        return $this->simbolo() . number_format((float) $v, $d, ',', '.');
    }

    /**
     * Cambia el símbolo en los textos que llegan ya formateados.
     *
     * Los indicadores y el resumen los compone el motor, que no conoce la
     * fuente del PDF. Si acá se decide que el símbolo no se puede dibujar, hay
     * que cambiarlo también en esas cadenas o el papel saldría con dos criterios
     * distintos: el código en la tabla y un recuadro vacío en los indicadores.
     */
    private function _sustituirSimbolo()
    {
        $original = $this->ajustes && isset($this->ajustes->symbol)
            ? trim((string) $this->ajustes->symbol) : '';
        $usable = $this->simbolo();

        if ($original === '' || $original === $usable) {
            return;
        }

        $cambiar = function ($t) use ($original, $usable) {
            return is_string($t) ? str_replace($original, $usable, $t) : $t;
        };

        foreach ($this->doc['kpis'] as &$k) {
            $k['texto'] = $cambiar($k['texto']);
            if (isset($k['pie'])) {
                $k['pie'] = $cambiar($k['pie']);
            }
        }
        unset($k);

        foreach ($this->doc['analisis'] as &$a) {
            $a = $cambiar($a);
        }
        unset($a);

        if (!empty($this->doc['confiabilidad']['resumen'])) {
            $this->doc['confiabilidad']['resumen'] = $cambiar($this->doc['confiabilidad']['resumen']);
        }
    }

    /**
     * Símbolo de moneda que la fuente del PDF puede dibujar.
     *
     * No todas las fuentes que trae mPDF llevan el colón (U+20A1):
     * `FreeSansBold`, por ejemplo, no lo tiene, y el símbolo sale como un
     * recuadro vacío o no sale. Si el símbolo configurado no se puede dibujar se
     * usa el código de tres letras, que siempre se lee.
     *
     * La comprobación es por ancho: un carácter ausente devuelve el avance del
     * glifo `notdef`, el mismo para todos, así que se compara contra uno que
     * ninguna de estas fuentes tiene.
     */
    private function simbolo()
    {
        if ($this->simbolo !== null) {
            return $this->simbolo;
        }

        $s = $this->ajustes && isset($this->ajustes->symbol) ? trim((string) $this->ajustes->symbol) : '';
        if ($s === '' || $this->mpdf === null) {
            return $this->simbolo = $s;
        }

        try {
            $ausente = $this->mpdf->GetStringWidth("\u{1F600}");   // no está en ninguna
            $tiene   = $this->mpdf->GetStringWidth($s);
            $ok      = abs($tiene - $ausente) > 0.001 && $tiene > 0;
        } catch (\Exception $e) {
            $ok = true;   // ante la duda se respeta lo configurado
        }

        if ($ok) {
            return $this->simbolo = $s;
        }

        log_message('error', 'Nx_report_pdf: la fuente del informe no puede dibujar «' . $s
            . '»; se usa el código de moneda en su lugar.');

        $codigos = array('₡' => 'CRC ', '₱' => 'PHP ', '₲' => 'PYG ', '₴' => 'UAH ', '₸' => 'KZT ');
        return $this->simbolo = isset($codigos[$s]) ? $codigos[$s] : $s;
    }

    private function empresa()
    {
        if (!$this->ajustes) {
            return 'Neurix POS';
        }
        foreach (array('nombre_comercial', 'nombre_emisor', 'site_name') as $k) {
            if (!empty($this->ajustes->$k)) {
                return $this->ajustes->$k;
            }
        }
        return 'Neurix POS';
    }

    private function cedula()
    {
        return $this->ajustes && !empty($this->ajustes->cedula_emisor) ? $this->ajustes->cedula_emisor : '';
    }

    /**
     * Ruta del logo para mPDF.
     *
     * mPDF necesita una ruta del sistema de archivos: una URL obliga a salir a
     * la red desde el propio servidor y, si el sitio pide autenticación, la
     * imagen llega vacía y el PDF sale sin logo.
     */
    private function logo()
    {
        $candidatos = array();
        if ($this->ajustes && !empty($this->ajustes->logo)) {
            $candidatos[] = FCPATH . 'uploads/logos/' . $this->ajustes->logo;
            $candidatos[] = FCPATH . 'uploads/' . $this->ajustes->logo;
            $candidatos[] = FCPATH . 'themes/default/assets/base/' . $this->ajustes->logo;
        }
        $candidatos[] = FCPATH . 'uploads/base/logo1.png';
        $candidatos[] = FCPATH . 'themes/default/assets/base/logo1.png';

        foreach ($candidatos as $r) {
            if (is_file($r)) {
                return $r;
            }
        }
        return '';
    }

    private function nombreArchivo()
    {
        $n = preg_replace('/[^A-Za-z0-9]+/', '_', $this->doc['titulo']);
        return trim($n, '_') . '_' . date('Ymd_His') . '.pdf';
    }

    private function e($s)
    {
        return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Hoja de estilo del informe.
     *
     * mPDF no aplica flexbox ni grid: la maquetación va con tablas y anchos en
     * porcentaje, que es lo único que reparte bien entre páginas.
     */
    private function css()
    {
        return '<style>
        /* Se nombra la fuente en vez de dejar el alias genérico: es la que
           se comprobó que dibuja el colón (U+20A1) en redonda y en negrita. */
        body { font-family: ' . self::FUENTE . ', sans-serif; font-size: 8.5pt; color: #1e293b; }
        .cab { width: 100%; margin-bottom: 2mm; }
        .cab-l { width: 42%; vertical-align: top; }
        .cab-r { width: 58%; vertical-align: top; text-align: right; }
        .logo { max-height: 16mm; margin-bottom: 1.5mm; }
        .marca { font-size: 7pt; letter-spacing: 1.4pt; color: #64748b; font-weight: bold; }
        .emp { font-size: 11pt; font-weight: bold; color: #0f172a; }
        .ced { font-size: 7.5pt; color: #64748b; }
        .tit { font-size: 15pt; font-weight: bold; color: #0f172a; }
        .sub { font-size: 8.5pt; color: #475569; margin-bottom: 1.5mm; }
        .meta { width: 100%; font-size: 7.5pt; }
        .meta .mk { text-align: right; color: #64748b; padding-right: 2mm; width: 58%; }
        .meta .mv { text-align: right; color: #1e293b; font-weight: bold; width: 42%; }
        .regla { border-bottom: 1.2pt solid #1e293b; margin: 1mm 0 3mm 0; }

        .bt { font-size: 8pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.8pt;
              color: #475569; border-bottom: 0.4pt solid #cbd5e1; padding-bottom: 1mm; margin: 4mm 0 2mm 0; }
        .bt .cnt { float: right; font-weight: normal; text-transform: none; letter-spacing: 0; color: #94a3b8; }
        .salto { page-break-before: auto; }

        .kpis { width: 100%; margin-bottom: 1.5mm; }
        .kpi { width: 25%; border: 0.4pt solid #e2e8f0; background: #f8fafc; padding: 2mm; vertical-align: top; }
        .kpi.vacio { border: none; background: none; }
        .kpi-e { font-size: 6.5pt; text-transform: uppercase; letter-spacing: 0.5pt; color: #64748b; }
        .kpi-v { font-size: 12pt; font-weight: bold; color: #0f172a; }
        .kpi-p { font-size: 6.5pt; color: #94a3b8; }
        .kpi.ok  { background: #f0fdf4; border-color: #bbf7d0; }
        .kpi.warn{ background: #fffbeb; border-color: #fde68a; }
        .kpi.err { background: #fef2f2; border-color: #fecaca; }

        .conf { width: 100%; margin: 2mm 0; border: 0.6pt solid #cbd5e1; }
        .conf-v { width: 22%; font-size: 20pt; font-weight: bold; text-align: center; padding: 3mm; }
        .conf-t { font-size: 8pt; padding: 3mm; }
        .conf.ok   { border-color: #86efac; } .conf.ok .conf-v   { color: #166534; background: #f0fdf4; }
        .conf.warn { border-color: #fcd34d; } .conf.warn .conf-v { color: #92400e; background: #fffbeb; }
        .conf.err  { border-color: #fca5a5; } .conf.err .conf-v  { color: #991b1b; background: #fef2f2; }

        .filtros { width: 100%; font-size: 7.5pt; }
        .filtros td { padding: 0.6mm 2mm 0.6mm 0; width: 33%; color: #334155; }
        .analisis { font-size: 8pt; margin: 0 0 2mm 4mm; padding: 0; }
        .analisis li { margin-bottom: 0.8mm; }

        table.datos { width: 100%; border-collapse: collapse; font-size: 7.5pt; }
        table.datos thead th { background: #1e293b; color: #fff; font-weight: bold; padding: 1.6mm 1.4mm;
                               text-align: left; font-size: 7pt; }
        table.datos tbody td { border-bottom: 0.3pt solid #e2e8f0; padding: 1.3mm 1.4mm; }
        table.datos tbody tr:nth-child(even) td { background: #f8fafc; }
        table.datos .r { text-align: right; }
        table.datos .c { text-align: center; }
        table.datos .vacia { text-align: center; color: #94a3b8; padding: 8mm; font-style: italic; }
        table.datos tfoot .tot td { border-top: 1pt solid #475569; border-bottom: 2pt double #475569;
                                    font-weight: bold; background: #f1f5f9; padding: 1.8mm 1.4mm; }
        table.datos .ok  { color: #166534; }
        table.datos .warn{ color: #92400e; }
        table.datos .err { color: #991b1b; font-weight: bold; }
        .aud td { vertical-align: top; }
        .nota { font-size: 7pt; color: #64748b; margin-top: 3mm; font-style: italic; }
        </style>';
    }
}
