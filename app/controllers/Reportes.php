<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Centro de inteligencia, auditoría y control.
 *
 * Convive con `Reports.php`, que conserva los informes históricos. Lo nuevo vive
 * acá porque cambia la arquitectura: un solo motor resuelve el dato y pantalla,
 * PDF y Excel se limitan a pintarlo, en vez de que cada salida haga su propia
 * consulta y acaben discrepando.
 *
 * Todo endpoint que devuelve JSON pasa por `_blindar()`. Sin eso un aviso de PHP
 * o cualquier `echo` suelto viaja delante del JSON con estado 200, el navegador
 * falla al interpretarlo y la pantalla se queda cargando sin decir por qué.
 */
class Reportes extends MY_Controller
{
    /** @var bool si la salida ya se blindó, para no anidar búferes */
    private $blindado = false;

    public function __construct()
    {
        parent::__construct();

        if (!$this->loggedIn) {
            redirect('login');
        }
        // Los informes cruzan ventas, costos y datos fiscales de toda la
        // empresa: no hay vista parcial que tenga sentido para un cajero.
        if (!$this->Admin) {
            $this->session->set_flashdata('error', lang('access_denied'));
            redirect('pos');
        }

        $this->load->helper('reportes');
        $this->load->model('reporte_model');
        $this->load->model('auditoria_model');
        $this->load->library('report_engine', null, 'motor');
    }

    // ═══════════════════════════════════════════════════════════════════
    //  PANTALLAS
    // ═══════════════════════════════════════════════════════════════════

    /** Centro de Inteligencia: el tablero con los indicadores del período. */
    public function index()
    {
        $this->data['catalogo']  = $this->motor->catalogo();
        $this->data['catalogos'] = $this->reporte_model->catalogos();
        $this->data['ambitos']   = rep_ambitos();
        $this->data['error']     = validation_errors() ?: $this->session->flashdata('error');

        $bc = array(array('link' => '#', 'page' => lang('reports')),
                    array('link' => '#', 'page' => 'Centro de Inteligencia'));
        $this->page_construct('reportes/inteligencia', $this->data,
                              array('page_title' => 'Centro de Inteligencia', 'bc' => $bc));
    }

    /**
     * Cualquier informe del catálogo, con la misma pantalla.
     *
     * No hay una vista por informe: la definición ya dice qué columnas tiene y
     * cómo se formatean, así que una pantalla las pinta todas. Añadir un informe
     * es añadir una entrada al catálogo.
     */
    public function ver($clave = 'ventas_dia')
    {
        $cat = $this->motor->catalogo();
        if (!isset($cat[$clave])) {
            show_404();
        }

        $this->data['clave']     = $clave;
        $this->data['definicion'] = $cat[$clave];
        $this->data['catalogo']  = $cat;
        $this->data['catalogos'] = $this->reporte_model->catalogos();
        $this->data['ambitos']   = rep_ambitos();

        $bc = array(array('link' => site_url('reportes'), 'page' => 'Centro de Inteligencia'),
                    array('link' => '#', 'page' => $cat[$clave]['titulo']));
        $this->page_construct('reportes/informe', $this->data,
                              array('page_title' => $cat[$clave]['titulo'], 'bc' => $bc));
    }

    /** Auditoría Integral: los semáforos de todo el sistema en una pantalla. */
    public function auditoria()
    {
        $this->data['catalogos'] = $this->reporte_model->catalogos();
        $this->data['ambitos']   = rep_ambitos();
        $bc = array(array('link' => site_url('reportes'), 'page' => 'Centro de Inteligencia'),
                    array('link' => '#', 'page' => 'Auditoría Integral'));
        $this->page_construct('reportes/auditoria', $this->data,
                              array('page_title' => 'Auditoría Integral', 'bc' => $bc));
    }

    /** Diccionario de datos: de dónde sale cada cifra, en pantalla. */
    public function diccionario()
    {
        $this->data['conceptos'] = rep_conceptos();
        $this->data['ambitos']   = rep_ambitos();
        $this->data['tarifas']   = rep_tarifas_iva();
        $this->data['reglas']    = $this->auditoria_model->reglas();
        $this->data['niveles']   = rep_niveles_anomalia();

        $bc = array(array('link' => site_url('reportes'), 'page' => 'Centro de Inteligencia'),
                    array('link' => '#', 'page' => 'Diccionario de datos'));
        $this->page_construct('reportes/diccionario', $this->data,
                              array('page_title' => 'Diccionario de datos', 'bc' => $bc));
    }

    /** Bitácora: qué informes se generaron, por quién y con qué filtros. */
    public function bitacora()
    {
        $bc = array(array('link' => site_url('reportes'), 'page' => 'Centro de Inteligencia'),
                    array('link' => '#', 'page' => 'Bitácora de informes'));
        $this->page_construct('reportes/bitacora', $this->data,
                              array('page_title' => 'Bitácora de informes', 'bc' => $bc));
    }

    // ═══════════════════════════════════════════════════════════════════
    //  DATOS
    // ═══════════════════════════════════════════════════════════════════

    /** Un informe del catálogo, en JSON, para la pantalla. */
    public function datos($clave = 'ventas_dia')
    {
        $this->_blindar();
        $f   = $this->reporte_model->filtro($this->input->post());
        $doc = $this->motor->documento($clave, $f, array('auditar' => $this->input->post('auditar') ? true : false));

        $this->_bitacora($doc, $f, 'pantalla');
        $this->_json($this->motor->json($doc));
    }

    /** Indicadores del Centro de Inteligencia. */
    public function panel()
    {
        $this->_blindar();
        $f = $this->reporte_model->filtro($this->input->post());

        $res  = $this->reporte_model->resumen($f);
        $anom = $this->auditoria_model->anomalias($f, 20);
        $inte = $this->auditoria_model->integridad($f);
        $conf = $this->auditoria_model->confiabilidad($f, $anom, $inte);

        $this->_json(array(
            'periodo'    => array('desde' => $f['desde'], 'hasta' => $f['hasta'], 'ambito' => $f['ambito']),
            'resumen'    => $res,
            'integridad' => $inte,
            'confiabilidad' => $conf,
            'anomalias'  => array_slice($anom, 0, 20),
            'anomalias_total' => count($anom),
            'series'     => array(
                'dia'        => $this->reporte_model->ventas_por($f, 'dia'),
                'hora'       => $this->reporte_model->ventas_por($f, 'hora'),
                'usuario'    => $this->reporte_model->ventas_por($f, 'usuario', 10),
                'producto'   => $this->reporte_model->ventas_por($f, 'producto', 10),
                'categoria'  => $this->reporte_model->ventas_por($f, 'categoria', 10),
                'cliente'    => $this->reporte_model->ventas_por($f, 'cliente', 10),
                'tarifa'     => $this->_rotularTarifas($this->reporte_model->ventas_por($f, 'tarifa')),
                'medio_pago' => $this->reporte_model->ventas_por_medio_pago($f),
                'estado'     => $this->reporte_model->ventas_por($f, 'estado'),
            ),
        ));
    }

    /** Todo el bloque de auditoría de un período. */
    public function auditoria_datos()
    {
        $this->_blindar();
        $f = $this->reporte_model->filtro($this->input->post());

        $anom = $this->auditoria_model->anomalias($f);
        $inte = $this->auditoria_model->integridad($f);
        $conc = $this->auditoria_model->conciliacion($f);
        $conf = $this->auditoria_model->confiabilidad($f, $anom, $inte);
        $res  = $this->reporte_model->resumen($f);

        $this->_json(array(
            'resumen'       => $res,
            'integridad'    => $inte,
            'anomalias'     => $anom,
            'conciliacion'  => $conc,
            'confiabilidad' => $conf,
            'semaforos'     => $this->_semaforos($res, $inte, $anom, $conc),
        ));
    }

    /** Comparación de una nota de crédito con el documento que corrige. */
    public function nota_comparada($nc_id = 0)
    {
        $this->_blindar();
        $r = $this->reporte_model->nota_vs_origen((int) $nc_id);
        if (!$r) {
            $this->_json(array('error' => 'No existe esa nota de crédito.'), 404);
        }
        $this->_json($r);
    }

    /** Cuadro previo del D-151: qué entra, qué no y por qué. */
    public function d151_previo()
    {
        $this->_blindar();
        $f = $this->reporte_model->filtro($this->input->post());
        $filas = $this->motor->d151($f);
        $this->_json(array(
            'previo' => $this->motor->d151_previo($filas),
            'filas'  => $filas,
        ));
    }

    /** Casillas del D-104 que este sistema no puede alimentar. */
    public function d104_faltantes()
    {
        $this->_blindar();
        $this->_json($this->motor->d104_faltantes());
    }

    /**
     * El HTML del PDF, sin generar el PDF.
     *
     * Sirve para comprobar que el papel dice lo mismo que la pantalla: el PDF
     * lleva las fuentes en subconjunto y su texto no se puede leer con una
     * expresión regular, pero esta es la cadena de la que sale.
     */
    public function pdf_html($clave = 'ventas_dia')
    {
        $f   = $this->reporte_model->filtro($this->_entrada());
        $doc = $this->motor->documento($clave, $f, array('auditar' => true));
        $this->load->library('nx_report_pdf', array('ajustes' => $this->Settings), 'nxpdf');

        $this->output
            ->set_content_type('text/html', 'utf-8')
            ->set_output($this->nxpdf->html($doc));
    }

    /**
     * Devuelve un token vigente.
     *
     * Es un `GET`, así que no consume ninguno: `MY_Controller::_cabecera_csrf()`
     * pone el vigente en la cabecera `X-CSRF-Token` de toda respuesta AJAX.
     *
     * Hace falta porque una respuesta 403 **no** lleva esa cabecera —CSRF falla
     * antes de llegar al controlador— y `cookie_httponly` impide leer el valor
     * desde JavaScript. Sin esto, un token gastado deja la pantalla atascada en
     * 403 hasta que alguien recarga a mano.
     */
    public function token()
    {
        $this->output
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode(array('ok' => true)));
    }

    /** Bitácora de informes generados. */
    public function bitacora_datos()
    {
        $this->_blindar();
        if (!$this->db->table_exists('report_log')) {
            $this->_json(array('data' => array()));
        }
        list($desde, $hasta) = rep_rango_fechas($this->input->post('start_date'), $this->input->post('end_date'));

        $q = $this->db->query(
            "SELECT l.*, CONCAT(COALESCE(u.first_name,''),' ',COALESCE(u.last_name,'')) AS usuario
               FROM `" . $this->db->dbprefix('report_log') . "` l
               LEFT JOIN `" . $this->db->dbprefix('users') . "` u ON u.id = l.user_id
              WHERE l.fecha >= ? AND l.fecha <= ?
              ORDER BY l.fecha DESC LIMIT 1000",
            array($desde, $hasta)
        );
        $this->_json(array('data' => $q ? $q->result_array() : array()));
    }

    // ═══════════════════════════════════════════════════════════════════
    //  EXPORTACIÓN
    // ═══════════════════════════════════════════════════════════════════

    /**
     * PDF de un informe.
     *
     * Se acepta por GET además de POST para que el enlace se pueda abrir en una
     * pestaña; los filtros viajan en la cadena de consulta y quedan escritos en
     * la bitácora igual que los de pantalla.
     */
    public function pdf($clave = 'ventas_dia')
    {
        $f   = $this->reporte_model->filtro($this->_entrada());
        $doc = $this->motor->documento($clave, $f, array('auditar' => true));
        $this->_bitacora($doc, $f, 'pdf');
        $this->motor->pdf($doc, $this->input->get('descargar') ? 'D' : 'I');
    }

    /** Excel de un informe: las cinco hojas, con el mismo dato de la pantalla. */
    public function excel($clave = 'ventas_dia')
    {
        $f   = $this->reporte_model->filtro($this->_entrada());
        $doc = $this->motor->documento($clave, $f, array('auditar' => true));
        $this->_bitacora($doc, $f, 'excel');
        $this->motor->excel($doc);
    }

    /**
     * Informe maestro: consolida todo el período en un PDF.
     *
     * Es el que se archiva al cerrar un mes. Lleva ventas, notas, anulaciones,
     * conciliación y las anomalías con sus semáforos, para que la carpeta del
     * período tenga una sola pieza que responda por el resto.
     */
    public function maestro()
    {
        $f = $this->reporte_model->filtro($this->_entrada());

        $doc = $this->motor->documento('ventas_dia', $f, array('auditar' => true));
        $doc['titulo']    = 'Auditoría Integral · Neurix POS';
        $doc['subtitulo'] = $doc['subtitulo'] . ' · informe maestro del período';

        $res  = $doc['resumen'];
        $inte = $doc['integridad'];
        $conc = $this->auditoria_model->conciliacion($f);

        $sem = $this->_semaforos($res, $inte, $this->auditoria_model->anomalias($f), $conc);
        foreach ($sem as $s) {
            $doc['analisis'][] = $s['icono'] . ' ' . $s['area'] . ': ' . $s['detalle'];
        }
        $doc['nota'] = 'Informe maestro. Los importes salen del detalle de líneas; las comprobaciones '
                     . 'de integridad y las anomalías se recalculan en cada generación y no se archivan. '
                     . 'El folio de este documento queda en la bitácora de informes.';

        $this->_bitacora($doc, $f, 'pdf');
        $this->motor->pdf($doc, $this->input->get('descargar') ? 'D' : 'I');
    }

    // ═══════════════════════════════════════════════════════════════════
    //  AUXILIARES
    // ═══════════════════════════════════════════════════════════════════

    /** Los filtros llegan por POST desde la pantalla y por GET desde un enlace. */
    private function _entrada()
    {
        $post = $this->input->post();
        return $post ? $post : $this->input->get();
    }

    /** Rótulo legible de la tarifa; en la base es un número. */
    private function _rotularTarifas($filas)
    {
        foreach ($filas as &$r) {
            $r['tarifa'] = $r['nombre'];
            $r['nombre'] = rep_tarifa_etiqueta($r['nombre']);
        }
        return $filas;
    }

    /**
     * Semáforos por área para la Auditoría Integral.
     *
     * Cada área responde una pregunta concreta y dice qué mirar cuando no está
     * en verde: un semáforo rojo sin destino obliga a buscar a ciegas.
     */
    private function _semaforos($res, $inte, $anom, $conc)
    {
        $por = array('critico' => 0, 'alto' => 0, 'medio' => 0, 'bajo' => 0);
        foreach ($anom as $a) {
            $por[$a['nivel']]++;
        }
        $de = function ($clave) use ($anom) {
            $n = 0;
            foreach ($anom as $a) {
                if ($a['regla'] === $clave) {
                    $n++;
                }
            }
            return $n;
        };

        $tono = function ($critico, $alto) {
            if ($critico > 0) {
                return array('🔴', 'err');
            }
            if ($alto > 0) {
                return array('🟡', 'warn');
            }
            return array('🟢', 'ok');
        };

        $areas = array();

        list($i, $t) = $tono($inte['diferencias'], 0);
        $areas[] = array('area' => 'Ventas', 'icono' => $i, 'tono' => $t,
            'detalle' => $inte['diferencias'] === 0
                ? 'el detalle, el encabezado y lo declarado coinciden'
                : $inte['diferencias'] . ' comparación(es) de integridad sin cuadrar',
            'ir' => 'reportes/auditoria');

        list($i, $t) = $tono($de('sin_detalle') + $de('descuadre_linea'), $de('cantidad_negativa'));
        $areas[] = array('area' => 'Documentos', 'icono' => $i, 'tono' => $t,
            'detalle' => ($de('sin_detalle') + $de('descuadre_linea')) . ' con descuadre entre encabezado y detalle',
            'ir' => 'reportes/ver/ventas_detalle');

        list($i, $t) = $tono($de('consecutivo_duplicado'), $de('rechazado_sin_reemitir') + $conc['conteo']['descuadre']);
        $areas[] = array('area' => 'Facturación electrónica', 'icono' => $i, 'tono' => $t,
            'detalle' => $conc['conteo']['conciliado'] . ' de ' . $conc['revisados'] . ' comprobantes conciliados, '
                       . $de('rechazado_sin_reemitir') . ' rechazados sin reemitir',
            'ir' => 'reportes/ver/conciliacion');

        list($i, $t) = $tono(0, $de('sin_cabys'));
        $areas[] = array('area' => 'Cumplimiento v4.4', 'icono' => $i, 'tono' => $t,
            'detalle' => $de('sin_cabys') === 0 ? 'todas las líneas llevan CABYS'
                       : $de('sin_cabys') . ' producto(s) facturados sin código CABYS',
            'ir' => 'reportes/ver/ventas_lineas');

        list($i, $t) = $tono(0, $de('stock_negativo') + $de('venta_sin_inventario'));
        $areas[] = array('area' => 'Inventario', 'icono' => $i, 'tono' => $t,
            'detalle' => $de('stock_negativo') . ' existencia(s) negativas, '
                       . $de('venta_sin_inventario') . ' descuadre(s) con el kardex',
            'ir' => 'reportes/ver/anomalias');

        list($i, $t) = $tono(0, $de('estado_pago_incoherente'));
        $areas[] = array('area' => 'Caja y cobros', 'icono' => $i, 'tono' => $t,
            'detalle' => $de('estado_pago_incoherente') === 0 ? 'el estado de cobro cuadra con lo pagado'
                       : $de('estado_pago_incoherente') . ' venta(s) con estado de cobro incoherente',
            'ir' => 'reportes/ver/ventas_medio_pago');

        list($i, $t) = $tono($de('impuesto_descuadrado'), 0);
        $areas[] = array('area' => 'Impuestos', 'icono' => $i, 'tono' => $t,
            'detalle' => $de('impuesto_descuadrado') === 0 ? 'el IVA de cada línea corresponde a su tarifa'
                       : $de('impuesto_descuadrado') . ' línea(s) con IVA que no corresponde a la tarifa',
            'ir' => 'reportes/ver/d104');

        list($i, $t) = $tono(0, $de('exceso_notas'));
        $areas[] = array('area' => 'Notas y anulaciones', 'icono' => $i, 'tono' => $t,
            'detalle' => $res['nc_cantidad'] . ' notas de crédito, ' . $res['nd_cantidad']
                       . ' de débito y ' . $res['anuladas'] . ' anulaciones en el período',
            'ir' => 'reportes/ver/notas_credito');

        list($i, $t) = $tono(0, $de('costo_cero'));
        $areas[] = array('area' => 'Costos y márgenes', 'icono' => $i, 'tono' => $t,
            'detalle' => $de('costo_cero') === 0 ? 'todas las líneas tienen costo registrado'
                       : $de('costo_cero') . ' producto(s) vendidos sin costo: el margen sale inflado',
            'ir' => 'reportes/ver/ventas_producto');

        return $areas;
    }

    /**
     * Deja constancia de la generación.
     *
     * Sin esta fila un PDF impreso es un papel sin origen: no se puede saber con
     * qué filtros salió ni por qué dos copias del mismo período difieren. Nunca
     * interrumpe la entrega del informe: si la bitácora falla se registra y el
     * informe sigue.
     */
    private function _bitacora(&$doc, $f, $formato)
    {
        if (!$this->db->table_exists('report_log')) {
            return;
        }

        // CI muestra su propia pagina de error y termina la peticion cuando una
        // consulta falla con db_debug activo, asi que un try/catch no basta: la
        // excepcion nunca llega. Se apaga mientras dura el registro para que un
        // fallo de la bitacora no pueda impedir la entrega del informe.
        $debug = $this->db->db_debug;
        $this->db->db_debug = FALSE;

        $doc['folio'] = $this->_folioNuevo();

        try {
            $total = 0.0;
            foreach ($doc['totales'] as $v) {
                $total = max($total, (float) $v);
            }
            $this->db->insert('report_log', array(
                'folio'     => $doc['folio'],
                'reporte'   => $doc['clave'],
                'titulo'    => mb_substr($doc['titulo'], 0, 160),
                'formato'   => $formato,
                'user_id'   => $this->session->userdata('user_id'),
                'store_id'  => $f['store_id'],
                'fecha'     => date('Y-m-d H:i:s'),
                'desde'     => $f['desde'],
                'hasta'     => $f['hasta'],
                'ambito'    => $f['ambito'],
                'filtros'   => json_encode($doc['filtros'], JSON_UNESCAPED_UNICODE),
                'registros' => count($doc['filas']),
                'total'     => $total,
                'confiabilidad' => $doc['confiabilidad'] ? $doc['confiabilidad']['pct'] : null,
                'version'   => isset($this->Settings->versionPOS) ? $this->Settings->versionPOS : null,
                'ip'        => $this->input->ip_address(),
            ));
        } catch (Exception $e) {
            log_message('error', 'Bitácora de informes: ' . $e->getMessage());
        }

        $this->db->db_debug = $debug;
    }

    /**
     * Siguiente folio del día: `REP-2026-08-27-000145`.
     *
     * El consecutivo sale de la bitácora y no de la hora, porque dos informes
     * generados en el mismo segundo compartían folio y el segundo chocaba con la
     * clave única. Si aun así choca —dos peticiones simultáneas—, se prueba con
     * el siguiente antes de caer en un sufijo por hora, que nunca se repite
     * dentro del mismo segundo pero no es consecutivo.
     */
    private function _folioNuevo()
    {
        $hoy = date('Y-m-d');
        $q   = $this->db->query(
            'SELECT COUNT(*) n FROM `' . $this->db->dbprefix('report_log') . '` WHERE DATE(fecha) = ?',
            array($hoy)
        );
        $n = $q ? (int) $q->row()->n : 0;

        for ($i = 1; $i <= 3; $i++) {
            $folio = rep_folio($n + $i);
            $y = $this->db->query(
                'SELECT 1 FROM `' . $this->db->dbprefix('report_log') . '` WHERE folio = ? LIMIT 1',
                array($folio)
            );
            if (!$y || $y->num_rows() === 0) {
                return $folio;
            }
        }
        return rep_folio((int) (date('His') . random_int(0, 9)));
    }

    /**
     * Garantiza que la respuesta sea JSON pase lo que pase.
     *
     * Un aviso de PHP impreso antes del JSON llega con estado 200 y el navegador
     * lo rechaza sin poder decir qué pasó. El búfer se descarta y lo que se haya
     * impreso vuelve como un error legible.
     */
    private function _blindar()
    {
        if ($this->blindado) {
            return;
        }
        $this->blindado = true;
        ob_start();

        // Una excepción sin capturar la pinta CI como una pantalla de error con
        // HTML y JavaScript: el navegador la recibe con estado 500 y no puede
        // interpretarla, así que la pantalla se queda cargando sin decir nada.
        // Convertida en JSON, el mensaje llega a quien lo pidió.
        set_exception_handler(function ($e) {
            log_message('error', 'Informe: ' . $e->getMessage() . ' en '
                . $e->getFile() . ':' . $e->getLine());
            $this->_json(array(
                'error'  => 'El informe no se pudo generar.',
                'motivo' => $e->getMessage(),
                'donde'  => basename($e->getFile()) . ':' . $e->getLine(),
            ), 500);
        });

        register_shutdown_function(function () {
            // `_json()` deja `blindado` en falso y termina. Si la ejecución
            // llega acá con el escudo puesto, la petición se cortó antes de
            // enviar la respuesta y lo que haya en el búfer no es JSON.
            if (!$this->blindado) {
                return;
            }

            $suelto = ob_get_length() !== false ? (string) ob_get_contents() : '';
            $fatal  = error_get_last();

            while (ob_get_level() > 0) {
                ob_end_clean();
            }

            if ($fatal && in_array($fatal['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR), true)) {
                $motivo = $fatal['message'];
                $donde  = basename($fatal['file']) . ':' . $fatal['line'];
            } else {
                // Es el caso del error de base de datos: con `db_debug` activo
                // CI imprime su pantalla de error y termina, sin dejar un error
                // fatal que capturar. Del HTML se rescata el texto para que el
                // motivo llegue al navegador en vez de una página entera que no
                // puede interpretar.
                $motivo = trim(preg_replace('/\s+/', ' ',
                    strip_tags(preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', ' ', $suelto))));
                $motivo = $motivo === '' ? 'La petición terminó sin respuesta.' : mb_substr($motivo, 0, 400);
                $donde  = 'sin traza';
            }

            log_message('error', 'Informe interrumpido: ' . $motivo);

            if (!headers_sent()) {
                header('Content-Type: application/json; charset=utf-8', true, 500);
            }
            echo json_encode(array(
                'error'  => 'El informe no se pudo generar.',
                'motivo' => $motivo,
                'donde'  => $donde,
            ), JSON_UNESCAPED_UNICODE);
        });
    }

    /** Envía JSON y corta: nada puede imprimirse después. */
    private function _json($datos, $estado = 200)
    {
        // Se descarta lo impreso antes: un aviso o un BOM delante rompe el JSON.
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        $this->blindado = false;

        $this->output
            ->set_status_header($estado)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR));
        $this->output->_display();
        exit;
    }
}
