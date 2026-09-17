<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Invierte el impuesto incluido en el precio cuando tax_method == '0'.
 * Retorna el precio base (sin impuesto) formateado a 4 decimales.
 */
if (!function_exists('invert_tax_price')) {
    function invert_tax_price($price, $taxPercent) {
        if ($taxPercent <= 0) return number_format((float)$price, 4, '.', '');
        return number_format((float)$price / (1 + ($taxPercent / 100)), 4, '.', '');
    }
}

/**
 * Subconjunto de los ajustes que puede viajar al navegador.
 *
 * Es una lista blanca a proposito: la fila completa de ajustes incluye el PIN del
 * certificado y las credenciales de Hacienda ya descifradas, y volcarla entera
 * dejaba esas claves legibles en el HTML de cualquier pantalla. Al agregar un
 * ajuste que el front necesite, agregalo aca; nunca sirvas el objeto completo.
 *
 * @param object $Settings fila de ajustes tal como la deja MY_Controller
 * @return array
 */
if (!function_exists('ajustes_publicos')) {
    function ajustes_publicos($Settings) {
        $permitidos = array(
            // Identidad visible del negocio
            'site_name', 'tel', 'language', 'selected_language', 'theme', 'theme_style', 'logo', 'rtl',
            // Formatos de fecha, numeros y moneda
            'dateformat', 'timeformat', 'currency_prefix', 'symbol', 'display_symbol',
            'decimals', 'decimals_sep', 'thousands_sep', 'qty_decimals', 'rounding', 'sac',
            // Comportamiento del POS
            'default_tax_rate', 'default_discount', 'rows_per_page', 'bsty', 'pro_limit', 'display_kb',
            'default_category', 'default_customer', 'default_actividad',
            'item_addition', 'after_sale_page', 'show_categories', 'overselling', 'multi_store',
            'sensibility_search', 'quantity_suggest', 'enable_fractions', 'enable_fastedition',
            'enable_credit', 'enable_layaway', 'enable_quote', 'enable_show_tax', 'enable_auth_open',
            'enable_detail_register', 'enable_detail_caschier', 'enable_btn_pay', 'enable_parquimetro',
            'enablebtn_retiro', 'enablebtn_deposito', 'is_shipping', 'multiprice_enabled',
            'enabled_tax_split', 'propina_enable', 'propina_rate', 'footer_apartado',
            // Impresion
            'auto_print', 'print_img', 'remote_printing',
            // Atajos de teclado
            'focus_add_item', 'edit_last_product', 'add_customer', 'toggle_category_slider',
            'cancel_sale', 'suspend_sale', 'finalize_sale',
            'today_sale', 'open_hold_bills', 'close_register',
            // Comparado en el navegador para autorizar borrados; ya viaja como hash
            'pin_code',
        );

        $publicos = array();
        foreach ($permitidos as $clave) {
            // property_exists y no isset: un ajuste en NULL debe seguir viajando como
            // null, que es lo que el front ya esperaba antes de la lista blanca.
            if (property_exists($Settings, $clave)) {
                $publicos[$clave] = $Settings->$clave;
            }
        }
        return $publicos;
    }
}

/**
 * Tipos de comprobante que el sistema numera, en el orden en que se muestran.
 * Los codigos son los de Hacienda (Anexos v4.4).
 */
if (!function_exists('tipos_comprobante')) {
    function tipos_comprobante() {
        return array(
            '01' => lang('tipo_doc_fe'),
            '04' => lang('tipo_doc_te'),
            '03' => lang('tipo_doc_nc'),
            '02' => lang('tipo_doc_nd'),
            '08' => lang('tipo_doc_fec'),
            '09' => lang('tipo_doc_rep'),
        );
    }
}

/**
 * Descompone una clave numerica de 50 digitos (Anexos v4.4): pais 3, fecha 6,
 * cedula 12, consecutivo 20, situacion 1, seguridad 8. Dentro del consecutivo:
 * casa matriz 3, terminal 5, tipo 2, numero 10.
 *
 * @return array|false partes de la clave, o false si no son 50 digitos
 */
if (!function_exists('leer_clave_hacienda')) {
    function leer_clave_hacienda($clave) {
        $clave = preg_replace('/\D/', '', (string) $clave);
        if (strlen($clave) !== 50) return false;
        $consecutivo = substr($clave, 21, 20);
        return array(
            'pais'         => substr($clave, 0, 3),
            'fecha'        => substr($clave, 3, 6),
            'cedula'       => substr($clave, 9, 12),
            'consecutivo'  => $consecutivo,
            'casa_matriz'  => substr($consecutivo, 0, 3),
            'terminal'     => substr($consecutivo, 3, 5),
            'tipo_doc'     => substr($consecutivo, 8, 2),
            'numero'       => (int) substr($consecutivo, 10, 10),
            'situacion'    => substr($clave, 41, 1),
            'seguridad'    => substr($clave, 42, 8),
        );
    }
}

/**
 * Nombres de los .p12 presentes en la carpeta de un ambiente ('test' | 'prod').
 * El nombre del archivo, sin extension, es lo que se guarda en certificado_ced_*
 * y lo que arma la ruta al firmar.
 *
 * @return array nombre => nombre, listo para form_dropdown()
 */
if (!function_exists('certificados_p12')) {
    function certificados_p12($ambiente) {
        $dir = FCPATH . 'files/certificados/' . ($ambiente === 'prod' ? 'prod' : 'test') . '/';
        $encontrados = array();
        foreach (glob($dir . '*.p12') ?: array() as $ruta) {
            $nombre = pathinfo($ruta, PATHINFO_FILENAME);
            $encontrados[$nombre] = $nombre;
        }
        ksort($encontrados);
        return $encontrados;
    }
}

/**
 * Deja un nombre de archivo apto para usarse como ruta: sin separadores de
 * directorio ni acentos, que es lo unico que el firmador puede abrir despues.
 */
if (!function_exists('nombre_certificado_seguro')) {
    function nombre_certificado_seguro($nombre) {
        $nombre = preg_replace('/[^A-Za-z0-9._-]/', '', (string) $nombre);
        return trim($nombre, '.');
    }
}

/**
 * Formatos de fecha ofrecidos en Ajustes, como opciones para un desplegable.
 * La etiqueta se arma con una fecha de ejemplo real: el usuario elige por lo que
 * va a ver en pantalla, no por el codigo de date().
 *
 * @param string|null $actual formato ya guardado; si no esta en el catalogo se
 *                            agrega al final para no perderlo al guardar.
 */
if (!function_exists('formatos_fecha')) {
    function formatos_fecha($actual = null) {
        // date() imprime meses y dias en ingles, sin importar el idioma de la app:
        // por eso el ejemplo se muestra tal cual, para que no haya sorpresas.
        $ejemplo = mktime(15, 45, 30, 8, 20, 2026);
        $catalogo = array(
            'd/m/Y'   => 'fmt_fecha_dma',
            'd-m-Y'   => 'fmt_fecha_dma',
            'd.m.Y'   => 'fmt_fecha_dma',
            'm/d/Y'   => 'fmt_fecha_mda',
            'Y-m-d'   => 'fmt_fecha_iso',
            'j M Y'   => 'fmt_fecha_texto',
            'j F Y'   => 'fmt_fecha_texto',
            'D j M Y' => 'fmt_fecha_semana',
        );
        return _formatos_opciones($catalogo, $ejemplo, $actual);
    }
}

/** Formatos de hora ofrecidos en Ajustes. Ver formatos_fecha(). */
if (!function_exists('formatos_hora')) {
    function formatos_hora($actual = null) {
        $ejemplo = mktime(15, 45, 30, 8, 20, 2026);
        $catalogo = array(
            'h:i A'   => 'fmt_hora_12',
            'g:i A'   => 'fmt_hora_12',
            'h:i a'   => 'fmt_hora_12',
            'H:i'     => 'fmt_hora_24',
            'H:i:s'   => 'fmt_hora_24_seg',
            'h:i:s A' => 'fmt_hora_12_seg',
        );
        return _formatos_opciones($catalogo, $ejemplo, $actual);
    }
}

if (!function_exists('_formatos_opciones')) {
    function _formatos_opciones($catalogo, $ejemplo, $actual) {
        $opciones = array();
        foreach ($catalogo as $formato => $clave) {
            $opciones[$formato] = date($formato, $ejemplo) . '  —  ' . lang($clave);
        }
        if ($actual !== null && $actual !== '' && !isset($opciones[$actual])) {
            $opciones[$actual] = date($actual, $ejemplo) . '  —  ' . lang('fmt_personalizado');
        }
        return $opciones;
    }
}

/**
 * Tarifa de IVA de una linea de comprobante, en porcentaje.
 *
 * `tax` guarda la tarifa como texto ('13%', '0') y a veces NULL aunque la linea
 * cobro impuesto: en ese caso se deduce del monto, para no rotular como 0 % una
 * linea gravada.
 *
 * @param object $row linea de sale_items, note_credits_items o note_debits_items
 * @return float
 */
if (!function_exists('tasa_iva_linea')) {
    function tasa_iva_linea($row)
    {
        $texto = isset($row->tax) ? trim(str_replace('%', '', (string) $row->tax)) : '';
        if ($texto !== '' && is_numeric($texto)) {
            return (float) $texto;
        }
        $impuesto = isset($row->item_tax) ? (float) $row->item_tax : 0;
        $total    = isset($row->subtotal) ? (float) $row->subtotal : 0;
        $base     = $total - $impuesto;
        return ($impuesto > 0 && $base > 0) ? round($impuesto / $base * 100, 2) : 0.0;
    }
}

/** Rotulo del IVA de una linea en el comprobante: «IVA 13%», «IVA 0,5%». */
if (!function_exists('etiqueta_iva')) {
    function etiqueta_iva($tasa)
    {
        $tasa = (float) $tasa;
        $numero = $tasa == (int) $tasa ? (string) (int) $tasa : str_replace('.', ',', rtrim(rtrim(number_format($tasa, 2, '.', ''), '0'), '.'));
        return 'IVA ' . $numero . '%';
    }
}

if (!function_exists('get_printer_chars_per_line')) {

    function get_printer_chars_per_line() {
        return 42;
    }

}

if (!function_exists('product_name')) {

    function product_name($name, $size = NULL) {
        if (!$size) {
            $size = get_printer_chars_per_line();
        }
        return character_limiter($name, ($size - 5));
    }

}

if (!function_exists('drawLine')) {

    function drawLine($size = NULL) {
        if (!$size) {
            $size = get_printer_chars_per_line();
        }
        $line = '';
        for ($i = 1; $i <= $size; $i++) {
            $line .= '-';
        }
        return $line . "\n";
    }

}

if (!function_exists('printLine')) {

    function printLine($str, $size = NULL, $sep = ":", $space = NULL) {
        if (!$size) {
            $size = get_printer_chars_per_line();
        }
        $size = $space ? $space : $size;
        $lenght = strlen($str);
        list($first, $second) = explode(":", $str, 2);
        $line = $first . ($sep == ":" ? $sep : '');
        for ($i = 1; $i < ($size - $lenght); $i++) {
            $line .= ' ';
        }
        $line .= ($sep != ":" ? $sep : '') . $second;
        return $line;
    }

}

if (!function_exists('printText')) {

    function printText($text, $size = NULL) {
        if (!$size) {
            $size = get_printer_chars_per_line();
        }
        $line = wordwrap($text, $size, "\\n");
        return $line;
    }

}

if (!function_exists('taxLine')) {

    function taxLine($name, $code, $qty, $amt, $tax, $size = NULL) {
        if (!$size) {
            $size = get_printer_chars_per_line();
        }
        return printLine(printLine(printLine(printLine($name . ':' . $code, 16, '') . ':' . $qty, 22, '') . ':' . $amt, 33, '') . ':' . $tax, $size, '');
    }

}

if (!function_exists('character_limiter')) {

    function character_limiter($str, $n = 500, $end_char = '...') {
        if (mb_strlen($str) < $n) {
            return $str;
        }
        $str = preg_replace('/ {2,}/', ' ', str_replace(array("\r", "\n", "\t", "\x0B", "\x0C"), ' ', $str));
        if (mb_strlen($str) <= $n) {
            return $str;
        }

        $out = '';
        foreach (explode(' ', trim($str)) as $val) {
            $out .= $val . ' ';
            if (mb_strlen($out) >= $n) {
                $out = trim($out);
                return (mb_strlen($out) === mb_strlen($str)) ? $out : $out . $end_char;
            }
        }
    }

}

if (!function_exists('word_wrap')) {

    function word_wrap($str, $charlim = 76) {
        is_numeric($charlim) OR $charlim = 76;
        $str = preg_replace('| +|', ' ', $str);
        if (strpos($str, "\r") !== FALSE) {
            $str = str_replace(array("\r\n", "\r"), "\n", $str);
        }
        $unwrap = array();
        if (preg_match_all('|\{unwrap\}(.+?)\{/unwrap\}|s', $str, $matches)) {
            for ($i = 0, $c = count($matches[0]); $i < $c; $i++) {
                $unwrap[] = $matches[1][$i];
                $str = str_replace($matches[0][$i], '{{unwrapped' . $i . '}}', $str);
            }
        }

        $str = wordwrap($str, $charlim, "\n", FALSE);
        $output = '';
        foreach (explode("\n", $str) as $line) {
            if (mb_strlen($line) <= $charlim) {
                $output .= $line . "\n";
                continue;
            }
            $temp = '';
            while (mb_strlen($line) > $charlim) {
                if (preg_match('!\[url.+\]|://|www\.!', $line)) {
                    break;
                }
                $temp .= mb_substr($line, 0, $charlim - 1);
                $line = mb_substr($line, $charlim - 1);
            }
            if ($temp !== '') {
                $output .= $temp . "\n" . $line . "\n";
            } else {
                $output .= $line . "\n";
            }
        }

        if (count($unwrap) > 0) {
            foreach ($unwrap as $key => $val) {
                $output = str_replace('{{unwrapped' . $key . '}}', $val, $output);
            }
        }

        return $output;
    }

}

if (!function_exists('dd')) {

    function dd($var, $exit = true) {
        echo "<pre>";
        var_dump($var);
        echo "</pre>";
        if ($exit) {
            exit();
        }
    }

}

/**
 * Agrupa los cobros de un turno por familia de forma de pago.
 *
 * El valor de `paid_by` cambio con el tiempo ('credit_card' antes, 'card'
 * ahora) y el cierre necesita una sola linea por familia, no una por variante.
 * Lo que no reconoce cae en "otros" en vez de desaparecer.
 *
 * Acepta tanto `paid_by => monto` como `paid_by => ['total' => …, 'pagos' => …]`.
 *
 * @param  array $porMetodo tal como lo devuelve el modelo
 * @return array familia => ['etiqueta' => …, 'total' => float, 'pagos' => int]
 */
if (!function_exists('cobros_por_familia')) {
    function cobros_por_familia(array $porMetodo)
    {
        $etiquetas = array(
            'efectivo'      => lang('pago_efectivo'),
            'tarjeta'       => lang('pago_tarjeta'),
            'sinpe'         => lang('pago_sinpe'),
            'transferencia' => lang('pago_transferencia'),
            'cheque'        => lang('pago_cheque'),
            'otros'         => lang('pago_otros'),
        );

        $familias = array();
        foreach ($etiquetas as $clave => $etiqueta) {
            $familias[$clave] = array('etiqueta' => $etiqueta, 'total' => 0.0, 'pagos' => 0);
        }

        foreach ($porMetodo as $metodo => $dato) {
            $familia = familia_pago($metodo);
            $familias[$familia]['total'] += (float) (is_array($dato) ? $dato['total'] : $dato);
            $familias[$familia]['pagos'] += is_array($dato) ? (int) $dato['pagos'] : 0;
        }

        return $familias;
    }
}

/**
 * Familia a la que pertenece un `paid_by`.
 *
 * Acepta las variantes historicas ('CC', 'credit_card') y las actuales del POS
 * ('card', 'sinpe', 'transfer'). Ver tambien Crearxml::tipoMedioPago(), que
 * hace el mapeo equivalente hacia los codigos de Hacienda.
 */
if (!function_exists('familia_pago')) {
    function familia_pago($paid_by)
    {
        switch (strtolower(trim((string) $paid_by))) {
            case 'cash':
                return 'efectivo';
            case 'cc':
            case 'card':
            case 'credit_card':
            case 'debit_card':
            case 'stripe':
                return 'tarjeta';
            case 'sinpe':
                return 'sinpe';
            case 'transfer':
            case 'transdep':
                return 'transferencia';
            case 'cheque':
                return 'cheque';
            default:
                return 'otros';
        }
    }
}

/**
 * Que dato adicional exige cada medio de pago del catalogo de Hacienda.
 *
 * `iban` para transferencia o deposito, `sinpe` para SINPE Movil, `plataforma`
 * para la digital y `otros` para el 99, que en el comprobante viaja como
 * <MedioPagoOtros> (Anexos v4.4, nota 6).
 *
 * @param  string $codigo codigo de dos digitos del medio de pago
 * @return string cadena vacia si ese medio no pide nada mas
 */
if (!function_exists('medio_pago_exige')) {
    function medio_pago_exige($codigo)
    {
        switch (trim((string) $codigo)) {
            case '04': return 'iban';
            case '06': return 'sinpe';
            case '07': return 'plataforma';
            case '99': return 'otros';
            default:   return '';
        }
    }
}

/**
 * Plataformas digitales de cobro con presencia en Costa Rica.
 *
 * Hacienda no publica catalogo para el medio 07: la lista es de uso interno y
 * lo que se guarde acaba en <MedioPagoOtros> solo si el medio es el 99.
 *
 * @return array lista de nombres
 */
if (!function_exists('plataformas_digitales')) {
    function plataformas_digitales()
    {
        return array('PayPal', 'ONVO Pay', 'Tilopay', 'Greenpay', 'Mercado Pago', 'Stripe', 'Apple Pay', 'Google Pay');
    }
}

/**
 * Codigo y rotulo del medio de pago que se declara a Hacienda.
 *
 * Los codigos son los de la nota 6 del anexo v4.4 (pag. 70). El 08 y el 09 no
 * existen en esa version. Esta es la unica tabla del sistema: Crearxml la usa
 * para armar <TipoMedioPago> y el detalle de la venta para rotular el cobro,
 * de modo que lo que se ve en pantalla sea lo que viajo en el comprobante.
 *
 * @param  string $paid_by valor guardado en tec_payments.paid_by
 * @return array{codigo: string, etiqueta: string}
 */
if (!function_exists('medio_pago_hacienda')) {
    function medio_pago_hacienda($paid_by)
    {
        switch (strtolower(trim((string) $paid_by))) {
            case 'cash':         return array('codigo' => '01', 'etiqueta' => lang('medio_pago_01'));
            case 'cc':
            case 'card':
            case 'credit_card':
            case 'debit_card':
            case 'stripe':       return array('codigo' => '02', 'etiqueta' => lang('medio_pago_02'));
            case 'cheque':       return array('codigo' => '03', 'etiqueta' => lang('medio_pago_03'));
            case 'transdep':
            case 'transfer':     return array('codigo' => '04', 'etiqueta' => lang('medio_pago_04'));
            case 'terceros':     return array('codigo' => '05', 'etiqueta' => lang('medio_pago_05'));
            case 'sinpe':        return array('codigo' => '06', 'etiqueta' => lang('medio_pago_06'));
            case 'digital':
            case 'plataforma':   return array('codigo' => '07', 'etiqueta' => lang('medio_pago_07'));
            default:             return array('codigo' => '99', 'etiqueta' => lang('medio_pago_99'));
        }
    }
}

/**
 * Como se lee un valor de `estatus_hacienda`.
 *
 * `corregible` decide que acciones tienen sentido: un comprobante aceptado es
 * inmutable y solo se corrige anulandolo con una nota de credito.
 *
 * @param  string|null $estatus valor crudo de la columna
 * @return array{clave: string, etiqueta: string, tono: string, corregible: bool, enviado: bool, nota: string}
 */
if (!function_exists('estado_hacienda_info')) {
    function estado_hacienda_info($estatus)
    {
        $crudo = strtolower(trim((string) $estatus));

        $tabla = array(
            ''                     => array('noenviado',  'estado_h_noenviado',  'warn',   true,  false),
            'pendiente'            => array('pendiente',  'estado_h_pendiente',  'orange', true,  false),
            'sin estado'           => array('pendiente',  'estado_h_pendiente',  'orange', true,  false),
            'procesando'           => array('procesando', 'estado_h_procesando', 'info',   false, true),
            'recibido'             => array('procesando', 'estado_h_procesando', 'info',   false, true),
            'aceptado'             => array('aceptado',   'estado_h_aceptado',   'ok',     false, true),
            'aceptado parcialmente' => array('parcial',   'estado_h_parcial',    'violet', false, true),
            'rechazado'            => array('rechazado',  'estado_h_rechazado',  'err',    true,  true),
            'error'                => array('error',      'estado_h_error',      'err',    true,  true),
            'anulado'              => array('anulado',    'estado_h_anulado',    'muted',  false, true),
        );

        $fila = isset($tabla[$crudo]) ? $tabla[$crudo] : array($crudo, 'estado_h_desconocido', 'muted', false, true);

        return array(
            'clave'      => $fila[0],
            'etiqueta'   => lang($fila[1]),
            'tono'       => $fila[2],
            'corregible' => $fila[3],
            'enviado'    => $fila[4],
            'nota'       => lang($fila[1] . '_nota'),
        );
    }
}

/**
 * Resumen legible de un MensajeHacienda.
 *
 * El acuse llega con el espacio de nombres de la v4.4, asi que los nodos se
 * buscan por nombre local: una ruta fija se rompe en cuanto cambia el prefijo.
 *
 * @param  string|null $xml contenido de la columna xml_hacienda
 * @return array{mensaje: string, estado: string, detalle: string, impuesto: string, total: string, emisor: string}|null
 */
if (!function_exists('resumen_mensaje_hacienda')) {
    function resumen_mensaje_hacienda($xml)
    {
        $xml = trim((string) $xml);
        if ($xml === '') {
            return null;
        }

        $anterior = libxml_use_internal_errors(true);
        $doc = new DOMDocument();
        $ok  = $doc->loadXML($xml);
        libxml_clear_errors();
        libxml_use_internal_errors($anterior);

        if (!$ok) {
            return null;
        }

        $leer = function ($nombre) use ($doc) {
            $n = $doc->getElementsByTagNameNS('*', $nombre);
            return $n->length ? trim($n->item(0)->textContent) : '';
        };

        return array(
            'mensaje'  => $leer('Mensaje'),
            'estado'   => $leer('EstadoMensaje'),
            'detalle'  => $leer('DetalleMensaje'),
            'impuesto' => $leer('MontoTotalImpuesto'),
            'total'    => $leer('TotalFactura'),
            'emisor'   => $leer('NombreEmisor'),
        );
    }
}

/**
 * URL de la foto de un usuario, con respaldo cuando no tiene ninguna.
 *
 * La cuenta puede no tener `avatar` ni `gender`, y concatenarlos a ciegas deja
 * una URL terminada en `.png` que responde 404 en cada carga de pantalla.
 *
 * @param  string|null $avatar nombre de archivo guardado en el usuario
 * @param  string|null $gender 'male' o 'female'; se usa solo si no hay avatar
 * @param  bool        $thumb  true para la miniatura de `avatars/thumbs`
 * @return string
 */
if (!function_exists('avatar_usuario')) {
    function avatar_usuario($avatar = null, $gender = null, $thumb = false)
    {
        $carpeta = $thumb ? 'uploads/avatars/thumbs/' : 'uploads/avatars/';
        $archivo = trim((string) $avatar);

        if ($archivo === '') {
            $sexo = trim((string) $gender);
            $archivo = in_array($sexo, array('male', 'female'), true) ? $sexo . '.png' : '';
        }

        if ($archivo !== '' && is_file(FCPATH . $carpeta . $archivo)) {
            return base_url($carpeta . $archivo);
        }

        return base_url('themes/default/assets/images/avatar-default.svg');
    }
}

/**
 * Direccion desde la que sale el correo del sistema.
 *
 * Ajustes guarda dos: `default_email` (la del sistema) y `email_emisor` (la que
 * viaja en el comprobante). Una instalacion puede tener solo la segunda, y
 * PHPMailer rechaza el envio entero con un remitente vacio.
 *
 * @param  object $Settings fila de ajustes
 * @return string cadena vacia si no hay ninguna configurada
 */
if (!function_exists('remitente_correo')) {
    function remitente_correo($Settings)
    {
        foreach (array('default_email', 'email_emisor') as $campo) {
            $valor = isset($Settings->$campo) ? trim((string) $Settings->$campo) : '';
            if ($valor !== '' && filter_var($valor, FILTER_VALIDATE_EMAIL)) {
                return $valor;
            }
        }
        return '';
    }
}

/**
 * Normaliza un numero de identificacion segun su tipo.
 *
 * Las identificaciones de Costa Rica (01-04) son solo digitos; el documento del
 * extranjero no domiciliado (05) y el del no contribuyente (06) admiten letras.
 *
 * @param  string $tipo   codigo de la nota 4 del anexo v4.4
 * @param  string $numero tal como lo escribio el usuario
 * @return string
 */
if (!function_exists('normalizar_identificacion')) {
    function normalizar_identificacion($tipo, $numero)
    {
        $numero = trim((string) $numero);
        return in_array((string) $tipo, array('05', '06'), true)
            ? strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $numero))
            : preg_replace('/\D/', '', $numero);
    }
}

/**
 * Largo y forma que el anexo v4.4 exige a cada tipo de identificacion.
 *
 * Hacienda rechaza el comprobante cuando el numero no calza con su tipo, y ese
 * rechazo llega despues de emitir: por eso se comprueba al guardar la ficha.
 *
 * @param  string $tipo   codigo de la nota 4 (01-06)
 * @param  string $numero ya normalizado o no
 * @return array{ok: bool, error: string} `error` es una clave de idioma
 */
if (!function_exists('identificacion_valida')) {
    function identificacion_valida($tipo, $numero)
    {
        $tipo   = (string) $tipo;
        $numero = normalizar_identificacion($tipo, $numero);

        if ($numero === '') {
            return array('ok' => false, 'error' => 'ident_falta_numero');
        }

        switch ($tipo) {
            case '01':  // Cedula fisica: 9 digitos, sin cero inicial
                if (!preg_match('/^\d{9}$/', $numero) || $numero[0] === '0') {
                    return array('ok' => false, 'error' => 'ident_fisica_9');
                }
                return array('ok' => true, 'error' => '');

            case '02':  // Cedula juridica: 10 digitos
                if (!preg_match('/^\d{10}$/', $numero)) {
                    return array('ok' => false, 'error' => 'ident_juridica_10');
                }
                return array('ok' => true, 'error' => '');

            case '03':  // DIMEX: 11 o 12 digitos, sin ceros iniciales
                if (!preg_match('/^\d{11,12}$/', $numero) || $numero[0] === '0') {
                    return array('ok' => false, 'error' => 'ident_dimex_11_12');
                }
                return array('ok' => true, 'error' => '');

            case '04':  // NITE: 10 digitos
                if (!preg_match('/^\d{10}$/', $numero)) {
                    return array('ok' => false, 'error' => 'ident_nite_10');
                }
                return array('ok' => true, 'error' => '');

            case '05':  // Extranjero No Domiciliado
            case '06':  // No Contribuyente
                if (mb_strlen($numero) > 20) {
                    return array('ok' => false, 'error' => 'ident_max_20');
                }
                return array('ok' => true, 'error' => '');
        }

        return array('ok' => false, 'error' => 'ident_tipo_invalido');
    }
}

/**
 * Codigos de <Codigo> de InformacionReferencia (CodigoReferenciaType, v4.4).
 *
 * Tomados del XSD oficial en files/docs-hacienda/xsd/v4.4/. **El 03 no existe
 * en la v4.4**: la devolucion de mercancia es el 06. Un codigo fuera de esta
 * lista hace que Hacienda rechace la nota.
 *
 * @return array codigo => rotulo
 */
if (!function_exists('codigos_referencia')) {
    function codigos_referencia()
    {
        return array(
            '01' => lang('ref_01'), '02' => lang('ref_02'), '04' => lang('ref_04'),
            '05' => lang('ref_05'), '06' => lang('ref_06'), '07' => lang('ref_07'),
            '08' => lang('ref_08'), '09' => lang('ref_09'), '10' => lang('ref_10'),
            '11' => lang('ref_11'), '12' => lang('ref_12'), '99' => lang('ref_99'),
        );
    }
}

/**
 * Codigos de referencia que el POS ofrece sobre una venta propia.
 *
 * Del catalogo completo solo estos cuatro tienen sentido en una nota de credito
 * emitida desde el punto de venta; los demas son de escenarios que el sistema
 * no maneja (endoso, proveedor no domiciliado, notas financieras).
 */
if (!function_exists('codigos_referencia_pos')) {
    function codigos_referencia_pos()
    {
        $todos = codigos_referencia();
        return array(
            '01' => $todos['01'],
            '06' => $todos['06'],
            '02' => $todos['02'],
            '99' => $todos['99'],
        );
    }
}

/**
 * Normaliza un codigo de referencia a dos digitos validos.
 *
 * @param  mixed  $codigo  lo que haya llegado del formulario
 * @param  string $defecto codigo a usar si el recibido no existe
 * @return string
 */
if (!function_exists('codigo_referencia_valido')) {
    function codigo_referencia_valido($codigo, $defecto = '01')
    {
        $codigo = str_pad(preg_replace('/\D/', '', (string) $codigo), 2, '0', STR_PAD_LEFT);
        return array_key_exists($codigo, codigos_referencia()) ? $codigo : $defecto;
    }
}

/**
 * Decide si un precio y un descuento de linea son admisibles.
 *
 * El comprobante se firma sobre estos numeros y el navegador los elige: sin
 * esta regla, una peticion armada a mano vende en un colon lo que vale
 * cincuenta mil y el cierre de caja cuadra igual.
 *
 * @param  array  $precios_lista precios legitimos del producto (catalogo,
 *                               tienda, oferta y listas de precios)
 * @param  float  $precio        el que mando el navegador, antes del descuento
 * @param  string $descuento     monto o porcentaje ('10%')
 * @param  float  $tope          descuento maximo permitido, en porcentaje
 * @param  bool   $puede_bajar   si quien vende puede cobrar bajo la lista
 * @return array{ok: bool, motivo: string, minimo: float, descuento_pct: float}
 *         `motivo` vale '', 'descuento_sobre_tope' o 'precio_bajo_lista'
 */
if (!function_exists('precio_de_linea_admisible')) {
    function precio_de_linea_admisible(array $precios_lista, $precio, $descuento, $tope = 100, $puede_bajar = false)
    {
        $precio = (float) $precio;
        $tope   = max(0, min(100, (float) $tope));

        // Descuento de linea: puede venir como porcentaje o como monto.
        $texto = trim((string) $descuento);
        $desc_pct = 0.0;
        if ($texto !== '' && strpos($texto, '%') !== false) {
            $desc_pct = (float) str_replace('%', '', $texto);
        } elseif ((float) $texto > 0 && $precio > 0) {
            $desc_pct = ((float) $texto / $precio) * 100;
        }

        if ($desc_pct > $tope + 0.001) {
            return array('ok' => false, 'motivo' => 'descuento_sobre_tope',
                         'minimo' => 0.0, 'descuento_pct' => $desc_pct);
        }

        $precios_lista = array_values(array_filter(array_map('floatval', $precios_lista), function ($x) {
            return $x > 0;
        }));

        // Un producto sin precio definido —o un articulo rapido— no tiene contra
        // que comparar: ahi manda lo que digito el cajero.
        if (!$precios_lista) {
            return array('ok' => true, 'motivo' => '', 'minimo' => 0.0, 'descuento_pct' => $desc_pct);
        }

        $minimo_lista = min($precios_lista);

        // Una milesima de tolerancia por el redondeo del navegador.
        if ($precio + 0.001 >= $minimo_lista) {
            return array('ok' => true, 'motivo' => '', 'minimo' => $minimo_lista, 'descuento_pct' => $desc_pct);
        }

        return array('ok' => (bool) $puede_bajar, 'motivo' => 'precio_bajo_lista',
                     'minimo' => $minimo_lista, 'descuento_pct' => $desc_pct);
    }
}

/**
 * Parsea un XML que llega de afuera, con tope de tamano y sin red.
 *
 * LIBXML_NONET impide que el parseo salga a buscar una entidad externa, y el
 * tope evita que un archivo armado a proposito consuma la memoria del servidor.
 *
 * @param  string $contenido XML crudo
 * @param  int    $tope      bytes admitidos
 * @return SimpleXMLElement|null
 */
if (!function_exists('xml_externo')) {
    function xml_externo($contenido, $tope = 5242880)
    {
        $contenido = (string) $contenido;
        if ($contenido === '' || strlen($contenido) > $tope) {
            log_message('error', '[XML] archivo vacio o mayor al tope de ' . $tope . ' bytes');
            return null;
        }

        $anterior = libxml_use_internal_errors(true);
        $xml = simplexml_load_string($contenido, 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOCDATA);
        $errores = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors($anterior);

        if ($xml === false) {
            $primero = $errores ? trim($errores[0]->message) : 'desconocido';
            log_message('error', '[XML] no se pudo parsear: ' . $primero);
            return null;
        }

        return $xml;
    }
}

/**
 * Nota del usuario lista para mostrar, sin HTML vivo.
 *
 * Las notas se guardan con `Tec::clear_tags()`, que quita las etiquetas fuera de
 * su lista blanca y convierte el resto en entidades. Mostrarlas con
 * `decode_html()` devolvia esas etiquetas a la vida, y `strip_tags` nunca quita
 * atributos: un `<img src=x onerror=...>` sobrevivia entero y se ejecutaba al
 * abrir el comprobante.
 *
 * @param  string|null $texto tal como esta en la base
 * @return string HTML seguro, con los saltos de linea conservados
 */
if (!function_exists('nota_segura')) {
    function nota_segura($texto)
    {
        $texto = (string) $texto;
        if ($texto === '') {
            return '';
        }

        // Se deshacen las entidades para poder quitar lo que escondan, se quita
        // toda etiqueta y recien entonces se vuelve a escapar.
        $plano = html_entity_decode($texto, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $plano = strip_tags($plano);

        return nl2br(html_escape($plano));
    }
}

/**
 * En que quedo una anulacion.
 *
 * Una anulacion interna no le dice nada a Hacienda: se confirma sola. Una
 * fiscal solo esta firme cuando la nota de credito que la respalda fue
 * aceptada; mientras tanto la mercaderia ya volvio al inventario y el cajero ya
 * actuo, asi que la venta queda anulada pero marcada como pendiente en vez de
 * deshacerse en silencio.
 *
 * @param string      $tipo        'fiscal' | 'interna'
 * @param string|null $estatus_nc  estatus_hacienda de la nota de credito
 * @return array{clave: string, etiqueta: string, tono: string, firme: bool, reintentable: bool, nota: string}
 */
if (!function_exists('estado_anulacion_info')) {
    function estado_anulacion_info($tipo, $estatus_nc = null)
    {
        if ($tipo !== 'fiscal') {
            return array(
                'clave' => 'firme', 'etiqueta' => lang('anu_estado_firme'), 'tono' => 'muted',
                'firme' => true, 'reintentable' => false, 'nota' => lang('anu_estado_firme_nota'),
            );
        }

        $nc = estado_hacienda_info($estatus_nc);

        if ($nc['clave'] === 'aceptado' || $nc['clave'] === 'parcial') {
            return array(
                'clave' => 'firme', 'etiqueta' => lang('anu_estado_firme'), 'tono' => 'ok',
                'firme' => true, 'reintentable' => false, 'nota' => lang('anu_estado_firme_nota'),
            );
        }

        if ($nc['clave'] === 'rechazado' || $nc['clave'] === 'error') {
            return array(
                'clave' => 'fallida', 'etiqueta' => lang('anu_estado_fallida'), 'tono' => 'err',
                'firme' => false, 'reintentable' => true, 'nota' => lang('anu_estado_fallida_nota'),
            );
        }

        return array(
            'clave' => 'pendiente', 'etiqueta' => lang('anu_estado_pendiente'), 'tono' => 'warn',
            'firme' => false, 'reintentable' => true, 'nota' => lang('anu_estado_pendiente_nota'),
        );
    }
}

/**
 * Los tres mensajes que admite un MensajeReceptor (Anexos v4.4, nota 12).
 *
 * El codigo del mensaje decide el tipo de documento del consecutivo: 1 -> 05,
 * 2 -> 06, 3 -> 07. No hay un cuarto valor.
 */
if (!function_exists('mensajes_receptor')) {
    function mensajes_receptor()
    {
        return array(
            '1' => lang('accept'),
            '2' => lang('partially_accept'),
            '3' => lang('reject_this_document'),
        );
    }
}

/** Condiciones de impuesto del MensajeReceptor. '00' significa no declararla. */
if (!function_exists('condiciones_impuesto')) {
    function condiciones_impuesto()
    {
        return array(
            '00' => lang('ci_sin_declarar'),
            '01' => lang('ci_credito_iva'),
            '02' => lang('ci_credito_parcial'),
            '03' => lang('ci_bienes_capital'),
            '04' => lang('ci_gasto_corriente'),
            '05' => lang('ci_proporcionalidad'),
        );
    }
}

/**
 * Como se lee la columna `Estatus` de documentoshacienda, que guarda el estado
 * de la aceptacion del comprobante de un proveedor, no el del comprobante.
 *
 * @param  string|null $estatus valor crudo de la columna
 * @return array{clave: string, etiqueta: string, tono: string}
 */
if (!function_exists('estado_aceptacion_info')) {
    function estado_aceptacion_info($estatus)
    {
        $crudo = strtolower(trim((string) $estatus));

        $tabla = array(
            ''                      => array('noproc',     'no_procesado', 'muted'),
            '0'                     => array('noproc',     'no_procesado', 'muted'),
            'procesando'            => array('procesando', 'procesando',   'info'),
            'recibido'              => array('recibido',   'recibido',     'warn'),
            'aceptado'              => array('aceptado',   'aceptado',     'ok'),
            'aceptado parcialmente' => array('parcial',    'partially_accept', 'violet'),
            'rechazado'             => array('rechazado',  'rechazado',    'err'),
            'error'                 => array('error',      'error',        'err'),
        );

        $fila = isset($tabla[$crudo]) ? $tabla[$crudo] : array($crudo, 'no_procesado', 'muted');

        return array('clave' => $fila[0], 'etiqueta' => lang($fila[1]), 'tono' => $fila[2]);
    }
}

/**
 * Un comprobante solo se responde una vez: en cuanto el mensaje receptor sale,
 * el consecutivo se gasto y Hacienda no admite otro por la misma clave.
 */
if (!function_exists('aceptacion_editable')) {
    function aceptacion_editable($estatus)
    {
        $crudo = strtolower(trim((string) $estatus));
        return $crudo === '' || $crudo === '0';
    }
}

/** Sangra un XML para leerlo; si viene mal formado se devuelve tal cual. */
if (!function_exists('xml_sangrado')) {
    function xml_sangrado($xml)
    {
        $xml = trim((string) $xml);
        // En PHP 8 loadXML() con la cadena vacia lanza ValueError, no devuelve false.
        if ($xml === '') {
            return '';
        }

        $anterior = libxml_use_internal_errors(true);
        $doc = new DOMDocument();
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = true;
        $ok = $doc->loadXML($xml);
        libxml_clear_errors();
        libxml_use_internal_errors($anterior);

        return $ok ? $doc->saveXML() : $xml;
    }
}
