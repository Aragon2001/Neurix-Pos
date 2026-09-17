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
 *  Package : laminas-barcode
 *  License : New BSD License
 *  ==============================================================================
 */

use Laminas\Barcode\Barcode;

class Tec_barcode
{
    public function __construct() {
    }

    public function __get($var) {
        return get_instance()->$var;
    }

    public function generate($text, $bcs = 'code128', $height = 50, $drawText = true, $get_be = false) {
        // Barcode::setBarcodeFont('my_font.ttf');
        $check = $this->prepareForChecksum($text, $bcs);

        $barcodeOptions = ['text' => $check['text'], 'barHeight' => $height, 'drawText' => $drawText, 'withChecksum' => $check['checksum'], 'withChecksumInText' => $check['checksum']]; //'fontSize' => 12, 'factor' => 1.5,

        $rendererOptions = ['imageType' => 'png', 'horizontalPosition' => 'center', 'verticalPosition' => 'middle'];
        $imageResource = Barcode::draw($bcs, 'image', $barcodeOptions, $rendererOptions);
        ob_start();
        imagepng($imageResource);
        $imagedata = ob_get_contents();
        ob_end_clean();
        if ($get_be) {
            return 'data:image/png;base64,'.base64_encode($imagedata);
        }
        return "<img  src='data:image/png;base64,".base64_encode($imagedata)."' alt='{$text}' class='bcimg' />";
    }

    public function generateonlycode64($text, $bcs = 'code128', $height = 50, $drawText = true, $get_be = false) {
        // Barcode::setBarcodeFont('my_font.ttf');
        $check = $this->prepareForChecksum($text, $bcs);

        $barcodeOptions = ['text' => $check['text'], 'barHeight' => $height, 'drawText' => $drawText, 'withChecksum' => $check['checksum'], 'withChecksumInText' => $check['checksum']]; //'fontSize' => 12, 'factor' => 1.5,

        $rendererOptions = ['imageType' => 'png', 'horizontalPosition' => 'center', 'verticalPosition' => 'middle'];
        $imageResource = Barcode::draw($bcs, 'image', $barcodeOptions, $rendererOptions);
        ob_start();
        imagepng($imageResource);
        $imagedata = ob_get_contents();
        ob_end_clean();
        if ($get_be) {
            return 'data:image/png;base64,'.base64_encode($imagedata);
        }
        return "data:image/png;base64,".base64_encode($imagedata);
    }

    /**
     * El codigo de barras en SVG, dibujado a mano desde las instrucciones.
     *
     * El renderizador de imagen de Laminas exige la extension GD, y el de SVG
     * saca una etiqueta blanca de fondo y un `<polygon>` por barra, sin
     * `viewBox`. Aca se leen las instrucciones del objeto y se emite una sola
     * `<path>`: fondo transparente, coordenadas enteras y `crispEdges`, que es
     * lo que hace que la barra salga con el filo limpio y no gris.
     *
     * @param  string $ajuste 'none' estira el codigo hasta el borde de la
     *                        etiqueta —el lector mide la proporcion entre
     *                        barras, no el alto—; 'meet' lo deja proporcional.
     * @return string documento SVG completo
     */
    public function generateSvg($text, $bcs = 'code128', $height = 100, $ajuste = 'none') {
        $check = $this->prepareForChecksum($text, $bcs);

        $codigo = Barcode::makeBarcode($bcs, array(
            'text'               => $check['text'],
            'barHeight'          => $height,
            'drawText'           => false,
            'withChecksum'       => $check['checksum'],
            'withChecksumInText' => $check['checksum'],
        ));

        $ancho = (int) $codigo->getWidth();
        $alto  = (int) $codigo->getHeight();

        $barras = array();
        foreach ($codigo->draw() as $orden) {
            // El color 0 es la barra; el resto es el fondo blanco que la
            // etiqueta no necesita.
            if ($orden['type'] !== 'polygon' || $orden['color'] !== 0) { continue; }

            $xs = array(); $ys = array();
            foreach ($orden['points'] as $punto) { $xs[] = $punto[0]; $ys[] = $punto[1]; }
            $x = (int) round(min($xs));
            $y = (int) round(min($ys));
            // El poligono llega de ancho cero: es Laminas quien le suma el
            // modulo al dibujarlo (Renderer\Svg::drawPolygon).
            $w = (int) round(max($xs)) - $x + 1;
            $h = (int) round(max($ys)) - $y;
            if ($h <= 0) { continue; }

            $barras[] = array($x, $y, $w, $h);
        }

        usort($barras, function ($a, $b) { return $a[0] - $b[0]; });

        // Una barra gruesa llega partida en varias de un modulo, pegadas. Sin
        // fusionarlas, `crispEdges` puede dejar una costura blanca entre ellas.
        $trazo = '';
        $actual = NULL;
        foreach ($barras as $b) {
            if ($actual && $b[1] === $actual[1] && $b[3] === $actual[3] && $b[0] <= $actual[0] + $actual[2]) {
                $actual[2] = max($actual[2], $b[0] + $b[2] - $actual[0]);
                continue;
            }
            if ($actual) { $trazo .= $this->rectangulo($actual); }
            $actual = $b;
        }
        if ($actual) { $trazo .= $this->rectangulo($actual); }

        $ajuste = $ajuste === 'meet' ? 'xMidYMid meet' : 'none';

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $ancho . ' ' . $alto . '"'
             . ' width="' . $ancho . '" height="' . $alto . '"'
             . ' preserveAspectRatio="' . $ajuste . '" shape-rendering="crispEdges">'
             . '<path d="' . $trazo . '" fill="#000"/></svg>';
    }

    /** Un rectangulo de la trayectoria: x, y, ancho, alto. */
    private function rectangulo(array $r) {
        return 'M' . $r[0] . ' ' . $r[1] . 'h' . $r[2] . 'v' . $r[3] . 'h-' . $r[2] . 'z';
    }

    protected function prepareForChecksum($text, $bcs) {
        if ($bcs == 'code25' || $bcs == 'code39') {
            return ['text' => $text, 'checksum' => false];
        } elseif ($bcs == 'code128') {
            return ['text' => $text, 'checksum' => true];
        }
        return ['text' => substr($text, 0, -1), 'checksum' => true];
    }
}
