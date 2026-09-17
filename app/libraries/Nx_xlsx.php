<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Escritor de libros XLSX sin dependencias.
 *
 * La extensión `zip` de PHP está comentada en el `php.ini` de este servidor
 * —igual que `gd`— así que `ZipArchive` no existe y ninguna librería de
 * hojas de cálculo arranca. El contenedor se escribe a mano: la única pieza
 * que hace falta es un ZIP con deflate, y `gzdeflate()` más `crc32()` vienen
 * con `zlib`, que sí está cargada.
 *
 * Las cadenas van en línea (`t="inlineStr"`) en vez de por tabla compartida.
 * El archivo sale algo mayor, pero se genera en una sola pasada y no hay que
 * mantener un índice global, que es de donde salen los libros corruptos.
 *
 * Uso:
 *
 *     $x = new Nx_xlsx();
 *     $h = $x->hoja('Detalle');
 *     $x->titulo($h, 'Ventas de agosto');
 *     $x->cabecera($h, array('Fecha', 'Cliente', 'Total'));
 *     $x->fila($h, array('2026-08-27', 'Juan', 12500), array('fecha', null, 'moneda'));
 *     $x->totales($h, array(null, 'Totales', 12500), array(null, null, 'moneda'));
 *     $x->descargar('ventas.xlsx');
 */
class Nx_xlsx
{
    /** Índice de cada formato en `styles.xml`; el orden define el número. */
    const E_NORMAL   = 0;
    const E_TITULO   = 1;
    const E_SUBTITULO = 2;
    const E_CABECERA = 3;
    const E_MONEDA   = 4;
    const E_NUMERO   = 5;
    const E_ENTERO   = 6;
    const E_PORCENT  = 7;
    const E_FECHA    = 8;
    const E_FECHAHORA = 9;
    const E_TOTAL    = 10;
    const E_TOTAL_MONEDA = 11;
    const E_ETIQUETA = 12;
    const E_OK       = 13;
    const E_ALERTA   = 14;
    const E_ERROR    = 15;
    const E_TEXTO    = 16;

    /** @var array<int,array> hojas del libro */
    private $hojas = array();

    /** @var string símbolo de moneda del sistema */
    private $simbolo = '';

    /** @var array metadatos del documento */
    private $meta = array('titulo' => 'Informe Neurix POS', 'autor' => 'Neurix POS', 'empresa' => '');

    public function __construct($config = array())
    {
        if (isset($config['simbolo'])) {
            $this->simbolo($config['simbolo']);
        }
    }

    /**
     * Símbolo de moneda de los formatos monetarios.
     *
     * Va escapado a la manera de OOXML: en un formato numérico el símbolo
     * literal se escribe `"₡"` entre comillas o Excel lo interpreta como
     * código de formato.
     */
    public function simbolo($s)
    {
        $this->simbolo = str_replace('"', '', (string) $s);
        return $this;
    }

    public function meta($titulo, $empresa = '', $autor = 'Neurix POS')
    {
        $this->meta = array('titulo' => $titulo, 'empresa' => $empresa, 'autor' => $autor);
        return $this;
    }

    // ═══════════════════════════════════════════════════════════════════
    //  CONSTRUCCIÓN DE HOJAS
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Abre una hoja y devuelve su índice, que es lo que reciben los demás
     * métodos.
     *
     * @param  string $nombre     rótulo de la pestaña
     * @param  array  $opciones   `orientacion` (`vertical`|`horizontal`),
     *                            `ajustar` (encajar el ancho en una página)
     * @return int
     */
    public function hoja($nombre, $opciones = array())
    {
        // Excel rechaza estos caracteres en el nombre de una pestaña y aborta
        // la apertura del libro entero sin decir cuál era.
        $nombre = str_replace(array('\\', '/', '?', '*', '[', ']', ':'), '-', (string) $nombre);
        $nombre = mb_substr($nombre, 0, 31);

        $this->hojas[] = array(
            'nombre'      => $nombre ?: ('Hoja' . (count($this->hojas) + 1)),
            'filas'       => array(),
            'anchos'      => array(),
            'combinadas'  => array(),
            'congelar'    => null,
            'autofiltro'  => null,
            'orientacion' => isset($opciones['orientacion']) ? $opciones['orientacion'] : 'vertical',
            'ajustar'     => !empty($opciones['ajustar']),
            'pie'         => isset($opciones['pie']) ? $opciones['pie'] : '',
        );
        return count($this->hojas) - 1;
    }

    /** Ancho de una columna, en caracteres. Sin esto Excel usa 8,43 para todas. */
    public function ancho($h, $columna, $caracteres)
    {
        $this->hojas[$h]['anchos'][(int) $columna] = (float) $caracteres;
        return $this;
    }

    /** Anchos de golpe, desde la columna A. */
    public function anchos($h, array $lista)
    {
        foreach (array_values($lista) as $i => $w) {
            if ($w !== null) {
                $this->ancho($h, $i, $w);
            }
        }
        return $this;
    }

    /**
     * Añade una fila.
     *
     * @param  array $valores
     * @param  array $formatos formato por columna: `moneda`, `numero`, `entero`,
     *                         `porcentaje`, `fecha`, `fechahora`, `texto`, `ok`,
     *                         `alerta`, `error`, `etiqueta`, o un índice de estilo
     * @return int número de fila (base 1)
     */
    public function fila($h, array $valores, array $formatos = array())
    {
        $celdas = array();
        foreach (array_values($valores) as $i => $v) {
            $fmt = isset($formatos[$i]) ? $formatos[$i] : null;
            $celdas[] = array('v' => $v, 's' => $this->_estilo($fmt), 'f' => $fmt);
        }
        $this->hojas[$h]['filas'][] = $celdas;
        return count($this->hojas[$h]['filas']);
    }

    /** Fila en blanco, para separar bloques. */
    public function blanco($h, $cuantas = 1)
    {
        for ($i = 0; $i < $cuantas; $i++) {
            $this->hojas[$h]['filas'][] = array();
        }
        return $this;
    }

    /** Título de bloque, combinado a lo ancho de `$columnas`. */
    public function titulo($h, $texto, $columnas = 6)
    {
        $n = $this->fila($h, array($texto), array(self::E_TITULO));
        if ($columnas > 1) {
            $this->hojas[$h]['combinadas'][] = $this->celda(0, $n) . ':' . $this->celda($columnas - 1, $n);
        }
        return $this;
    }

    public function subtitulo($h, $texto, $columnas = 6)
    {
        $n = $this->fila($h, array($texto), array(self::E_SUBTITULO));
        if ($columnas > 1) {
            $this->hojas[$h]['combinadas'][] = $this->celda(0, $n) . ':' . $this->celda($columnas - 1, $n);
        }
        return $this;
    }

    /** Par etiqueta / valor, el patrón de la hoja de portada. */
    public function dato($h, $etiqueta, $valor, $formato = null)
    {
        return $this->fila($h, array($etiqueta, $valor), array(self::E_ETIQUETA, $formato));
    }

    /**
     * Fila de encabezados de tabla.
     *
     * Deja la fila congelada y el autofiltro puesto sobre ella, que es lo que
     * espera quien recibe el archivo para poder ordenar y filtrar sin tocar nada.
     */
    public function cabecera($h, array $etiquetas, $congelar = true, $autofiltro = true)
    {
        $n = $this->fila($h, $etiquetas, array_fill(0, count($etiquetas), self::E_CABECERA));
        if ($congelar) {
            $this->hojas[$h]['congelar'] = $n;
        }
        if ($autofiltro) {
            $this->hojas[$h]['autofiltro'] = array($n, count($etiquetas));
        }
        return $n;
    }

    /** Fila de totales, resaltada y con línea superior. */
    public function totales($h, array $valores, array $formatos = array())
    {
        $f = array();
        foreach (array_values($valores) as $i => $v) {
            $x = isset($formatos[$i]) ? $formatos[$i] : null;
            $f[$i] = ($x === 'moneda' || $x === 'numero') ? self::E_TOTAL_MONEDA : self::E_TOTAL;
        }
        return $this->fila($h, $valores, $f);
    }

    /** Traduce un nombre de formato a su índice en `styles.xml`. */
    private function _estilo($fmt)
    {
        if (is_int($fmt)) {
            return $fmt;
        }
        switch ($fmt) {
            case 'moneda':     return self::E_MONEDA;
            case 'numero':     return self::E_NUMERO;
            case 'entero':     return self::E_ENTERO;
            case 'porcentaje': return self::E_PORCENT;
            case 'fecha':      return self::E_FECHA;
            case 'fechahora':  return self::E_FECHAHORA;
            case 'texto':      return self::E_TEXTO;
            case 'ok':         return self::E_OK;
            case 'alerta':     return self::E_ALERTA;
            case 'error':      return self::E_ERROR;
            case 'etiqueta':   return self::E_ETIQUETA;
            default:           return self::E_NORMAL;
        }
    }

    /** Referencia de celda: (0,1) → `A1`. */
    public function celda($col, $fila)
    {
        $c = '';
        $n = (int) $col;
        do {
            $c = chr(65 + ($n % 26)) . $c;
            $n = intdiv($n, 26) - 1;
        } while ($n >= 0);
        return $c . (int) $fila;
    }

    // ═══════════════════════════════════════════════════════════════════
    //  SALIDA
    // ═══════════════════════════════════════════════════════════════════

    /** Devuelve el libro como cadena binaria. */
    public function contenido()
    {
        if (!$this->hojas) {
            $this->hoja('Hoja1');
        }

        $partes = array(
            '[Content_Types].xml'     => $this->_contentTypes(),
            '_rels/.rels'             => $this->_rels(),
            'docProps/core.xml'       => $this->_core(),
            'docProps/app.xml'        => $this->_app(),
            'xl/workbook.xml'         => $this->_workbook(),
            'xl/_rels/workbook.xml.rels' => $this->_workbookRels(),
            'xl/styles.xml'           => $this->_styles(),
        );
        foreach ($this->hojas as $i => $hoja) {
            $partes['xl/worksheets/sheet' . ($i + 1) . '.xml'] = $this->_sheet($hoja);
        }

        return $this->_zip($partes);
    }

    /** Escribe el libro en disco. */
    public function guardar($ruta)
    {
        return file_put_contents($ruta, $this->contenido()) !== false;
    }

    /**
     * Manda el libro al navegador como descarga.
     *
     * Limpia cualquier búfer abierto antes de las cabeceras: un aviso de PHP o
     * un BOM impreso antes se cuelan dentro del binario y Excel responde que el
     * archivo está dañado, sin decir por qué.
     */
    public function descargar($nombre = 'informe.xlsx')
    {
        $bin = $this->contenido();

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $nombre = preg_replace('/[^A-Za-z0-9_\-. ]/', '_', $nombre);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $nombre . '"');
        header('Content-Length: ' . strlen($bin));
        header('Cache-Control: max-age=0, must-revalidate');
        header('Pragma: public');
        echo $bin;
    }

    // ═══════════════════════════════════════════════════════════════════
    //  ZIP
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Arma un ZIP con deflate.
     *
     * @param  array<string,string> $partes ruta dentro del zip => contenido
     */
    private function _zip(array $partes)
    {
        $local   = '';
        $central = '';
        $n       = 0;

        foreach ($partes as $ruta => $datos) {
            $crc  = crc32($datos);
            $crud = strlen($datos);
            $comp = gzdeflate($datos, 6);

            // Si comprimir no gana nada se guarda tal cual: un deflate más
            // largo que el original es válido pero absurdo.
            if ($comp === false || strlen($comp) >= $crud) {
                $comp   = $datos;
                $metodo = 0;
            } else {
                $metodo = 8;
            }

            $offset = strlen($local);
            $nom    = str_replace('\\', '/', $ruta);

            $cab = pack('vvvvv', 20, 0x0800, $metodo, 0, 0)   // versión, flags (UTF-8), método, hora, fecha
                 . pack('VVV', $crc, strlen($comp), $crud)
                 . pack('vv', strlen($nom), 0);

            $local .= "PK\x03\x04" . $cab . $nom . $comp;

            $central .= "PK\x01\x02" . pack('v', 20) . $cab
                      . pack('vvv', 0, 0, 0)          // comentario, disco, atributos internos
                      . pack('V', 32)                 // atributos externos: archivo normal
                      . pack('V', $offset) . $nom;
            $n++;
        }

        return $local . $central
             . "PK\x05\x06" . pack('vvvv', 0, 0, $n, $n)
             . pack('VV', strlen($central), strlen($local))
             . pack('v', 0);
    }

    // ═══════════════════════════════════════════════════════════════════
    //  PARTES XML
    // ═══════════════════════════════════════════════════════════════════

    /** Escapa texto para XML y quita los caracteres que OOXML no admite. */
    private function x($s)
    {
        $s = (string) $s;
        // Los de control por debajo de 0x20 (salvo tab, LF y CR) rompen el
        // documento: Excel lo declara ilegible sin señalar la celda.
        $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $s);
        return htmlspecialchars($s, ENT_QUOTES | ENT_XML1 | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function _contentTypes()
    {
        $s = '';
        foreach ($this->hojas as $i => $h) {
            $s .= '<Override PartName="/xl/worksheets/sheet' . ($i + 1) . '.xml" '
                . 'ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
            . '<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>'
            . $s . '</Types>';
    }

    private function _rels()
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
            . '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>'
            . '</Relationships>';
    }

    private function _core()
    {
        $f = gmdate('Y-m-d\TH:i:s\Z');
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" '
            . 'xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" '
            . 'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            . '<dc:title>' . $this->x($this->meta['titulo']) . '</dc:title>'
            . '<dc:creator>' . $this->x($this->meta['autor']) . '</dc:creator>'
            . '<cp:lastModifiedBy>' . $this->x($this->meta['autor']) . '</cp:lastModifiedBy>'
            . '<dcterms:created xsi:type="dcterms:W3CDTF">' . $f . '</dcterms:created>'
            . '<dcterms:modified xsi:type="dcterms:W3CDTF">' . $f . '</dcterms:modified>'
            . '</cp:coreProperties>';
    }

    private function _app()
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" '
            . 'xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">'
            . '<Application>Neurix POS</Application>'
            . '<Company>' . $this->x($this->meta['empresa']) . '</Company>'
            . '</Properties>';
    }

    private function _workbook()
    {
        $s = '';
        foreach ($this->hojas as $i => $h) {
            $s .= '<sheet name="' . $this->x($h['nombre']) . '" sheetId="' . ($i + 1) . '" r:id="rId' . ($i + 1) . '"/>';
        }
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets>' . $s . '</sheets></workbook>';
    }

    private function _workbookRels()
    {
        $s = '';
        foreach ($this->hojas as $i => $h) {
            $s .= '<Relationship Id="rId' . ($i + 1) . '" '
                . 'Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" '
                . 'Target="worksheets/sheet' . ($i + 1) . '.xml"/>';
        }
        $s .= '<Relationship Id="rId' . (count($this->hojas) + 1) . '" '
            . 'Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . $s . '</Relationships>';
    }

    /**
     * Hoja de estilos.
     *
     * El orden de `cellXfs` define las constantes `E_*` de esta clase: mover una
     * entrada cambia el formato de todos los informes, así que se añade al final.
     */
    private function _styles()
    {
        $mon = $this->simbolo !== '' ? '&quot;' . $this->x($this->simbolo) . '&quot;' : '';
        $fmtMoneda = $mon . '#,##0.00;[Red]\-' . $mon . '#,##0.00';

        $numFmts = '<numFmts count="4">'
            . '<numFmt numFmtId="164" formatCode="' . $fmtMoneda . '"/>'
            . '<numFmt numFmtId="165" formatCode="#,##0.00"/>'
            . '<numFmt numFmtId="166" formatCode="#,##0"/>'
            . '<numFmt numFmtId="167" formatCode="0.00%"/>'
            . '</numFmts>';

        $fonts = '<fonts count="8">'
            . '<font><sz val="10"/><name val="Calibri"/></font>'                                        // 0 normal
            . '<font><b/><sz val="16"/><color rgb="FF0F172A"/><name val="Calibri"/></font>'              // 1 título
            . '<font><sz val="11"/><color rgb="FF475569"/><name val="Calibri"/></font>'                  // 2 subtítulo
            . '<font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'              // 3 cabecera
            . '<font><b/><sz val="10"/><color rgb="FF0F172A"/><name val="Calibri"/></font>'              // 4 total
            . '<font><b/><sz val="10"/><color rgb="FF166534"/><name val="Calibri"/></font>'              // 5 ok
            . '<font><b/><sz val="10"/><color rgb="FF92400E"/><name val="Calibri"/></font>'              // 6 alerta
            . '<font><b/><sz val="10"/><color rgb="FF991B1B"/><name val="Calibri"/></font>'              // 7 error
            . '</fonts>';

        $fills = '<fills count="7">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF1E293B"/><bgColor indexed="64"/></patternFill></fill>'  // 2 cabecera
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFF1F5F9"/><bgColor indexed="64"/></patternFill></fill>'  // 3 total
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFDCFCE7"/><bgColor indexed="64"/></patternFill></fill>'  // 4 ok
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFFEF3C7"/><bgColor indexed="64"/></patternFill></fill>'  // 5 alerta
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFFEE2E2"/><bgColor indexed="64"/></patternFill></fill>'  // 6 error
            . '</fills>';

        $borders = '<borders count="3">'
            . '<border><left/><right/><top/><bottom/><diagonal/></border>'
            . '<border><left/><right/><top/><bottom style="thin"><color rgb="FFCBD5E1"/></bottom><diagonal/></border>'
            . '<border><left/><right/><top style="medium"><color rgb="FF475569"/></top><bottom style="double"><color rgb="FF475569"/></bottom><diagonal/></border>'
            . '</borders>';

        // El orden es el de las constantes E_*.
        $xf = array(
            '<xf numFmtId="0"   fontId="0" fillId="0" borderId="0" xfId="0"/>',                                                       // 0  normal
            '<xf numFmtId="0"   fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"><alignment vertical="center"/></xf>',       // 1  título
            '<xf numFmtId="0"   fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1"/>',                                          // 2  subtítulo
            '<xf numFmtId="0"   fontId="3" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1">'
                . '<alignment horizontal="center" vertical="center" wrapText="1"/></xf>',                                              // 3  cabecera
            '<xf numFmtId="164" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1"/>',                 // 4  moneda
            '<xf numFmtId="165" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1"/>',                 // 5  número
            '<xf numFmtId="166" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1"/>',                 // 6  entero
            '<xf numFmtId="167" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1"/>',                 // 7  porcentaje
            '<xf numFmtId="14"  fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1"/>',                 // 8  fecha
            '<xf numFmtId="22"  fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1"/>',                 // 9  fecha y hora
            '<xf numFmtId="0"   fontId="4" fillId="3" borderId="2" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/>',           // 10 total
            '<xf numFmtId="164" fontId="4" fillId="3" borderId="2" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyBorder="1"/>', // 11 total moneda
            '<xf numFmtId="0"   fontId="4" fillId="0" borderId="0" xfId="0" applyFont="1"/>',                                          // 12 etiqueta
            '<xf numFmtId="0"   fontId="5" fillId="4" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/>',           // 13 ok
            '<xf numFmtId="0"   fontId="6" fillId="5" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/>',           // 14 alerta
            '<xf numFmtId="0"   fontId="7" fillId="6" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/>',           // 15 error
            '<xf numFmtId="49"  fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1"/>',                 // 16 texto literal
        );

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . $numFmts . $fonts . $fills . $borders
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="' . count($xf) . '">' . implode('', $xf) . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';
    }

    private function _sheet($h)
    {
        $cols = '';
        if ($h['anchos']) {
            $cols = '<cols>';
            foreach ($h['anchos'] as $i => $w) {
                $cols .= '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="' . $w . '" customWidth="1"/>';
            }
            $cols .= '</cols>';
        }

        $filas = '';
        $maxCol = 0;
        foreach ($h['filas'] as $n => $celdas) {
            $r = $n + 1;
            if (!$celdas) {
                $filas .= '<row r="' . $r . '"/>';
                continue;
            }
            $c = '';
            foreach ($celdas as $i => $cel) {
                $c .= $this->_celda($this->celda($i, $r), $cel);
                $maxCol = max($maxCol, $i + 1);
            }
            $filas .= '<row r="' . $r . '">' . $c . '</row>';
        }

        $panes = '';
        if ($h['congelar']) {
            $y = (int) $h['congelar'];
            $panes = '<pane ySplit="' . $y . '" topLeftCell="A' . ($y + 1) . '" activePane="bottomLeft" state="frozen"/>'
                   . '<selection pane="bottomLeft" activeCell="A' . ($y + 1) . '" sqref="A' . ($y + 1) . '"/>';
        }

        $filtro = '';
        if ($h['autofiltro']) {
            list($fila, $ncols) = $h['autofiltro'];
            $ultima = count($h['filas']);
            $filtro = '<autoFilter ref="' . $this->celda(0, $fila) . ':'
                    . $this->celda(max(0, $ncols - 1), max($fila, $ultima)) . '"/>';
        }

        $comb = '';
        if ($h['combinadas']) {
            $comb = '<mergeCells count="' . count($h['combinadas']) . '">';
            foreach ($h['combinadas'] as $m) {
                $comb .= '<mergeCell ref="' . $m . '"/>';
            }
            $comb .= '</mergeCells>';
        }

        // Encajar el ancho en una página evita que una tabla grande salga
        // partida en columnas huérfanas al imprimir desde Excel.
        $setup = '<pageMargins left="0.4" right="0.4" top="0.6" bottom="0.6" header="0.3" footer="0.3"/>'
               . '<pageSetup orientation="' . ($h['orientacion'] === 'horizontal' ? 'landscape' : 'portrait') . '" '
               . 'fitToWidth="1" fitToHeight="0" paperSize="9"/>'
               . '<headerFooter><oddFooter>&amp;L' . $this->x($h['pie']) . '&amp;RPágina &amp;P de &amp;N</oddFooter></headerFooter>';

        $props = $h['ajustar'] ? '<sheetPr><pageSetUpPr fitToPage="1"/></sheetPr>' : '';

        // Repetir la fila de encabezados en cada página impresa es una
        // definición de nombre a nivel de libro, no de hoja; se omite a
        // propósito para no arrastrar esa complejidad al XML.
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . $props
            . '<sheetViews><sheetView workbookViewId="0" showGridLines="0">' . $panes . '</sheetView></sheetViews>'
            . '<sheetFormatPr defaultRowHeight="15"/>'
            . $cols
            . '<sheetData>' . $filas . '</sheetData>'
            . $filtro . $comb . $setup
            . '</worksheet>';
    }

    /**
     * Una celda.
     *
     * Los números van sin comillas y las cadenas como `inlineStr`. Un valor
     * numérico que llegue como texto —los `DECIMAL` de MySQL llegan así— se
     * convierte, o Excel lo alinea a la izquierda y no lo suma.
     */
    private function _celda($ref, $cel)
    {
        $v = $cel['v'];
        $s = (int) $cel['s'];
        $f = $cel['f'];

        if ($v === null || $v === '') {
            return '<c r="' . $ref . '" s="' . $s . '"/>';
        }

        if ($f === 'fecha' || $f === 'fechahora') {
            $serie = $this->_serieFecha($v, $f === 'fechahora');
            if ($serie !== null) {
                return '<c r="' . $ref . '" s="' . $s . '"><v>' . $serie . '</v></c>';
            }
        }

        $numerico = in_array($f, array('moneda', 'numero', 'entero', 'porcentaje'), true)
            || (is_int($v) || is_float($v));

        if ($numerico && is_numeric($v)) {
            return '<c r="' . $ref . '" s="' . $s . '"><v>' . (0 + $v) . '</v></c>';
        }

        return '<c r="' . $ref . '" s="' . $s . '" t="inlineStr"><is><t xml:space="preserve">'
             . $this->x($v) . '</t></is></c>';
    }

    /**
     * Fecha al número de serie de Excel.
     *
     * El origen es el 30/12/1899 y no el 1/1/1900: la hoja de cálculo arrastra
     * un 1900 bisiesto que nunca existió, y ese día de diferencia es el ajuste.
     *
     * La cuenta se hace sobre la hora de pared, no sobre el epoch: convertir por
     * `strtotime()/86400` mete el desfase de zona horaria dentro del número y
     * una venta de las 00:15 de Costa Rica aparece en Excel a las 06:15.
     *
     * @return float|null null si el valor no es una fecha reconocible
     */
    private function _serieFecha($v, $conHora)
    {
        if (is_numeric($v)) {
            return (float) $v;
        }
        try {
            $d = new DateTime((string) $v);
        } catch (Exception $e) {
            return null;
        }

        $origen = new DateTime('1899-12-30 00:00:00', $d->getTimezone());
        $dias   = (int) $origen->diff(new DateTime($d->format('Y-m-d') . ' 00:00:00', $d->getTimezone()))->format('%r%a');

        if (!$conHora) {
            return $dias;
        }
        $seg = (int) $d->format('H') * 3600 + (int) $d->format('i') * 60 + (int) $d->format('s');
        return round($dias + ($seg / 86400), 8);
    }
}
