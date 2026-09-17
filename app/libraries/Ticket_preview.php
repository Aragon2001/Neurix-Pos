<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Impresora falsa para la vista previa del tiquete.
 *
 * Recibe las mismas llamadas que Mike42\Escpos\Printer y las guarda como lineas
 * con su formato. Asi la vista previa sale del mismo codigo que arma los bytes
 * que van a la termica, en vez de una plantilla aparte que se desfasa.
 */
class Ticket_preview
{
    const JUSTIFY_LEFT = 0;
    const JUSTIFY_CENTER = 1;
    const JUSTIFY_RIGHT = 2;

    private $lineas = array();
    private $actual = '';
    private $alinear = 'left';
    private $negrita = false;
    private $ancho = 1;

    public function text($texto = '')
    {
        $partes = explode("\n", str_replace("\r", '', (string) $texto));
        foreach ($partes as $i => $parte) {
            $this->actual .= $parte;
            if ($i < count($partes) - 1) {
                $this->_cerrarLinea();
            }
        }
    }

    public function feed($lineas = 1)
    {
        if ($this->actual !== '') {
            $this->_cerrarLinea();
        }
        for ($i = 0; $i < max(1, (int) $lineas); $i++) {
            $this->lineas[] = array('t' => '', 'a' => $this->alinear, 'b' => false, 'w' => 1);
        }
    }

    public function setJustification($j = self::JUSTIFY_LEFT)
    {
        $this->alinear = $j === self::JUSTIFY_CENTER ? 'center' : ($j === self::JUSTIFY_RIGHT ? 'right' : 'left');
    }

    public function setEmphasis($on = true)
    {
        $this->negrita = (bool) $on;
    }

    public function setTextSize($ancho = 1, $alto = 1)
    {
        $this->ancho = max(1, (int) $ancho);
    }

    public function cut()
    {
        if ($this->actual !== '') {
            $this->_cerrarLinea();
        }
        $this->lineas[] = array('corte' => true);
    }

    public function bitImage($img = null)
    {
        $this->lineas[] = array('imagen' => true, 'a' => $this->alinear);
    }

    public function bitImageColumnFormat($img = null)
    {
        $this->bitImage($img);
    }

    public function pulse()
    {
    }

    public function close()
    {
    }

    /** @return array lineas: t texto, a alineacion, b negrita, w ancho del caracter */
    public function lineas()
    {
        if ($this->actual !== '') {
            $this->_cerrarLinea();
        }
        return $this->lineas;
    }

    private function _cerrarLinea()
    {
        $this->lineas[] = array('t' => $this->actual, 'a' => $this->alinear, 'b' => $this->negrita, 'w' => $this->ancho);
        $this->actual = '';
    }
}
