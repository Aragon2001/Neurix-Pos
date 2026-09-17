<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
defined('BASEPATH') OR exit('No direct script access allowed');
/*
 *  ==============================================================================
 *  Author  : Jostin Aragon Barboza
 *  Email   : arasoftsolutions@outlook.com
 *  Company : ARASOFT SOLUTIONS
 *  ==============================================================================
 */

class Tec
{

    public function __construct() {

    }

    public function __get($var) {
        return get_instance()->$var;
    }

    /**
     * Limpia una nota antes de guardarla.
     *
     * `strip_tags` quita etiquetas pero nunca atributos, asi que `<a>`, `<img>`
     * y `<div>` entraban con su `onerror` o su `href="javascript:"` intactos.
     * La lista blanca se reduce a lo que no lleva atributos utiles.
     */
    public function clear_tags($str) {
        return htmlentities(
            strip_tags($str, '<br><p><b><i><u><strong><em><small><ul><ol><li><hr>'),
            ENT_QUOTES | ENT_XHTML | ENT_HTML5, 'UTF-8'
        );
    }

    public function decode_html($str) {
        return html_entity_decode($str, ENT_QUOTES | ENT_XHTML | ENT_HTML5, 'UTF-8');
    }


    public function formatMoney($number, $decimal = false) {
        if ($this->Settings->sac) {
            return ($this->Settings->display_symbol == 1 ? $this->Settings->symbol : '') .
            $this->formatSAC($this->formatDecimal($number)) .
            ($this->Settings->display_symbol == 2 ? $this->Settings->symbol : '');
        }
        $decimals = $decimal !== false ? $decimal : $this->Settings->decimals;
        $ts = $this->Settings->thousands_sep == '0' ? ' ' : $this->Settings->thousands_sep;
        $ds = $this->Settings->decimals_sep;
        return ($this->Settings->display_symbol == 1 ? $this->Settings->symbol : '') .
        number_format($number, $decimals, $ds, $ts) .
        ($this->Settings->display_symbol == 2 ? $this->Settings->symbol : '');
    }

    public function formatQuantity($number, $decimals = null) {
        if (!$decimals) {
            $decimals = $this->Settings->qty_decimals;
        }
        if ($this->Settings->sac) {
            return $this->formatSAC($this->formatDecimal($number, $decimals));
        }
        $ts = $this->Settings->thousands_sep == '0' ? ' ' : $this->Settings->thousands_sep;
        $ds = $this->Settings->decimals_sep;
        return number_format($number, $decimals, $ds, $ts);
    }

    public function formatNumber($number, $decimals = null) {
        if (!$decimals) {
            $decimals = $this->Settings->decimals;
        }
        if ($this->Settings->sac) {
            return $this->formatSAC($this->formatDecimal($number, $decimals));
        }
        $ts = $this->Settings->thousands_sep == '0' ? ' ' : $this->Settings->thousands_sep;
        $ds = $this->Settings->decimals_sep;
        return number_format($number, $decimals, $ds, $ts);
    }

    public function formatDecimal($number, $decimals = null) {
        if (!is_numeric($number)) {
            return null;
        }
        if (!$decimals) {
            $decimals = $this->Settings->decimals;
        }
        return number_format($number, $decimals, '.', '');
    }

    public function roundNumber($number, $toref = NULL) {
        switch($toref) {
            case 1:
                $rn = round($number * 20)/20;
                break;
            case 2:
                $rn = round($number * 2)/2;
                break;
            case 3:
                $rn = round($number);
                break;
            case 4:
                $rn = ceil($number);
                break;
            default:
                $rn = $number;
        }
        return $rn;
    }

    public function unset_data($ud) {
        if($this->session->userdata($ud)) {
            $this->session->unset_userdata($ud);
            return true;
        }
        return FALSE;
    }

    public function hrsd($sdate) {
        if ($sdate) {
            return date($this->Settings->dateformat, strtotime($sdate));
        }
        return FALSE;
    }

    public function hrld($ldate) {
        if ($ldate) {
            return date($this->Settings->dateformat.' '.$this->Settings->timeformat, strtotime($ldate));
        }
        return FALSE;
    }

    public function send_email($to, $subject, $message, $from = NULL, $from_name = NULL, $attachment = NULL, $cc = NULL, $bcc = NULL) {
        $this->load->library('tec_mail');
        return $this->tec_mail->send_mail($to, $subject, $message, $from, $from_name, $attachment, $cc, $bcc);
    }

    public function print_arrays() {
        $args = func_get_args();
        echo "<pre>";
        foreach($args as $arg){
            print_r($arg);
        }
        echo "</pre>";
        die();
    }

    public function getUsers() {
        return $this->site->getUsers();
    }

    public function getUser($user_id = NULL) {
        return $this->site->getUser($user_id);
    }

    public function logged_in() {
        return (bool) $this->session->userdata('identity');
    }

    public function in_group($check_group, $id = false) {
        if ( ! $id) { $id = $this->session->userdata('user_id'); }
        $group = $this->site->getUserGroup($id);
        if($group && $group->name === $check_group) {
            return TRUE;
        }
        return FALSE;
    }

    private function _rglobRead($source, &$array = array()) {
        if (!$source || trim($source) == "") {
            $source = ".";
        }
        foreach ((array)glob($source . "/*/") as $key => $value) {
            $this->_rglobRead(str_replace("//", "/", $value), $array);
        }
        $hidden_files = glob($source . ".*") AND $htaccess = preg_grep('/\.htaccess$/', $hidden_files);
        $files = array_merge(glob($source . "*.*"), $htaccess);
        foreach ($files as $key => $value) {
            $array[] = str_replace("//", "/", $value);
        }
    }

    private function _zip($array, $part, $destination, $output_name = 'sma') {
        $zip = new ZipArchive;
        @mkdir($destination, 0777, true);

        if ($zip->open(str_replace("//", "/", "{$destination}/{$output_name}" . ($part ? '_p' . $part : '') . ".zip"), ZipArchive::CREATE)) {
            foreach ((array)$array as $key => $value) {
                $zip->addFile($value, str_replace(array("../", "./"), NULL, $value));
            }
            $zip->close();
        }
    }

    public function zip($source = NULL, $destination = "./", $output_name = 'sma', $limit = 5000) {
        if (!$destination || trim($destination) == "") {
            $destination = "./";
        }

        $this->_rglobRead($source, $input);
        $maxinput = count($input);
        $splitinto = (($maxinput / $limit) > round($maxinput / $limit, 0)) ? round($maxinput / $limit, 0) + 1 : round($maxinput / $limit, 0);

        for ($i = 0; $i < $splitinto; $i++) {
            $this->_zip(array_slice($input, ($i * $limit), $limit, true), $i, $destination, $output_name);
        }

        unset($input);
        return;
    }

    public function unzip($source, $destination = './') {

        // @chmod($destination, 0777);
        $zip = new ZipArchive;
        if ($zip->open(str_replace("//", "/", $source)) === true) {
            $zip->extractTo($destination);
            $zip->close();
        }
        // @chmod($destination,0755);

        return TRUE;
    }

    public function view_rights($check_id, $js = NULL, $page = NULL) {
        if (!$this->Admin) {
            if ($check_id != $this->session->userdata('user_id')) {
                $this->session->set_flashdata('error', $this->data['access_denied']);
                if ($js) {
                    die("<script type='text/javascript'>setTimeout(function(){ window.top.location.href = '" . ($page ? $page : (isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : site_url('welcome'))) . "'; }, 10);</script>");
                } else {
                    redirect($page ? $page : (isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : 'welcome'));
                }
            }
        }
        return TRUE;
    }

    public function dd() {
        die("<script type='text/javascript'>setTimeout(function(){ window.top.location.href = '" . (isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : site_url('pos')) . "'; }, 10);</script>");
    }

    public function get_base64($file_name) {
        $type = pathinfo($file_name, PATHINFO_EXTENSION);
        $data = file_get_contents($file_name);
        $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
        return $base64;
    }

    public function barcode($text = null, $bcs = 'code128', $height = 74, $stext = 1, $get_be = false) {
        $drawText = ($stext != 1) ? false : true;
        $this->load->library('tec_barcode', '', 'bc');
        return $this->bc->generate($text, $bcs, $height, $drawText, $get_be);
    }

    /** El mismo codigo de barras en SVG: no necesita GD y escala sin dentarse. */
    public function barcode_svg($text = null, $bcs = 'code128', $height = 100, $ajuste = 'none') {
        $this->load->library('tec_barcode', '', 'bc');
        return $this->bc->generateSvg($text, $bcs, $height, $ajuste);
    }

    public function barcode64($text = null, $bcs = 'code128', $height = 74, $stext = 1, $get_be = false) {
        $drawText = ($stext != 1) ? false : true;
        $this->load->library('tec_barcode', '', 'bc');
        return $this->bc->generateonlycode64($text, $bcs, $height, $drawText, $get_be);
    }

    /**
     * Deja el HTML de un comprobante listo para mPDF.
     *
     * Las vistas de comprobante ya traen su hoja de estilo en linea, pensada
     * para que el PDF se vea igual que la pantalla. Aqui solo se retira lo que
     * mPDF no puede procesar: los bloques de botones y scripts (delimitados por
     * los marcadores de recorte de la vista), la hoja de estilo externa y la
     * etiqueta <base>. Las imagenes locales pasan a ruta de disco para que mPDF
     * no tenga que descargarlas por HTTP.
     *
     * @param string $html HTML completo de la vista
     * @return string
     */
    public function pdf_html($html) {
        $html = preg_replace('#<!-- start -->(.+)<!-- end -->#Usi', '', $html);
        $html = preg_replace('#<link[^>]*rel=[\x22\x27]?stylesheet[\x22\x27]?[^>]*>#i', '', $html);
        $html = preg_replace('#<base[^>]*>#i', '', $html);
        $html = preg_replace('#<script\b[^>]*>.*?</script>#is', '', $html);
        $html = str_replace(base_url('uploads/'), FCPATH . 'uploads/', $html);

        // El bloque @media print maqueta el comprobante como tiquete de 80 mm.
        // Se retira del HTML que va a mPDF en vez de confiar en CSSselectMedia,
        // porque su parser tropieza con la @page anidada y desmaqueta el PDF.
        // .no-print vive dentro de ese bloque, asi que se repone la regla.
        $html = $this->quitar_media_print($html);
        $html = str_ireplace('</head>', '<style>.no-print{display:none !important;}</style></head>', $html);

        return $html;
    }

    /**
     * Elimina los bloques `@media print { ... }` contando llaves, porque
     * pueden contener reglas anidadas (@page) que una expresion regular
     * simple cortaria a la mitad.
     *
     * @param string $css_html
     * @return string
     */
    private function quitar_media_print($css_html) {
        $pos = 0;
        while (($ini = stripos($css_html, '@media print', $pos)) !== FALSE) {
            $llave = strpos($css_html, '{', $ini);
            if ($llave === FALSE) { break; }

            $nivel = 0;
            $fin   = NULL;
            for ($i = $llave, $n = strlen($css_html); $i < $n; $i++) {
                if ($css_html[$i] === '{') {
                    $nivel++;
                } elseif ($css_html[$i] === '}') {
                    $nivel--;
                    if ($nivel === 0) { $fin = $i; break; }
                }
            }
            if ($fin === NULL) { break; }

            $css_html = substr($css_html, 0, $ini) . substr($css_html, $fin + 1);
            $pos = $ini;
        }
        return $css_html;
    }

    /**
     * Genera un codigo QR como PNG en data URI.
     *
     * Se usa data URI (y no un archivo) para que la misma marca sirva tanto en
     * el navegador como dentro del PDF que arma mPDF, sin peticiones HTTP.
     *
     * @param string $text    Contenido a codificar
     * @param int    $scale   Tamano de cada modulo en px
     * @param bool   $get_src TRUE devuelve solo el data URI; FALSE la etiqueta <img>
     * @return string Cadena vacia si el texto viene vacio o falla la generacion
     */
    public function qrcode($text = null, $scale = 5, $get_src = false) {
        if ($text === null || $text === '') {
            return '';
        }

        try {
            $options = new \chillerlan\QRCode\QROptions(array(
                'outputInterface' => \chillerlan\QRCode\Output\QRGdImagePNG::class,
                'eccLevel'        => \chillerlan\QRCode\Common\EccLevel::M,
                'scale'           => (int) $scale,
                'outputBase64'    => true,
                'quietzoneSize'   => 2,
            ));
            $src = (new \chillerlan\QRCode\QRCode($options))->render($text);
        } catch (\Throwable $e) {
            log_message('error', 'No se pudo generar el QR: ' . $e->getMessage());
            return '';
        }

        if ($get_src) {
            return $src;
        }
        return '<img src="' . $src . '" alt="' . html_escape($text) . '" class="qrimg" />';
    }

    public function send_json($data) {
        header('Content-Type: application/json');
        die(json_encode($data));
        exit;
    }

    public function makecomma($input)
    {
        if (strlen($input) <= 2) {return $input;}
        $length = substr($input, 0, strlen($input) - 2);
        $formatted_input = $this->makecomma($length) . "," . substr($input, -2);
        return $formatted_input;
    }

    public function formatSAC($num)
    {
        $pos = strpos((string) $num, ".");
        if ($pos === false) {
            $decimalpart = "00";
        } else {
            $decimalpart = substr($num, $pos + 1, 2);
            $num = substr($num, 0, $pos);
        }

        if (strlen($num) > 3 & strlen($num) <= 12) {
            $last3digits = substr($num, -3);
            $numexceptlastdigits = substr($num, 0, -3);
            $formatted = $this->makecomma($numexceptlastdigits);
            $stringtoreturn = $formatted . "," . $last3digits . "." . $decimalpart;
        } elseif (strlen($num) <= 3) {
            $stringtoreturn = $num . "." . $decimalpart;
        } elseif (strlen($num) > 12) {
            $stringtoreturn = number_format($num, 2);
        }

        if (substr($stringtoreturn, 0, 2) == "-,") {
            $stringtoreturn = "-" . substr($stringtoreturn, 2);
        }

        return $stringtoreturn;
    }
}
