<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */

/**
 * Lector de ZIP para las pruebas del escritor de libros.
 *
 * La extensión `zip` está comentada en el `php.ini` de este servidor, así que
 * `ZipArchive` no existe ni para escribir ni para leer. Comprobar el libro con
 * la misma clase que lo escribe no probaría nada, así que la lectura se
 * implementa aparte y desde el directorio central, que es como lo abre Excel.
 */
class ZipLector
{
    /**
     * Devuelve `ruta dentro del zip => contenido`.
     *
     * @throws RuntimeException si el contenedor no es un ZIP legible
     */
    public static function abrir($binario)
    {
        // El fin del directorio central está al final; se busca hacia atrás
        // porque puede llevar un comentario detrás.
        $fin = strrpos($binario, "PK\x05\x06");
        if ($fin === false) {
            throw new RuntimeException('no se encontró el fin del directorio central');
        }

        $eocd    = unpack('vdisco/vdiscoCd/ventradasDisco/ventradas/Vtam/Voff', substr($binario, $fin + 4, 16));
        $puntero = $eocd['off'];
        $salida  = array();

        for ($i = 0; $i < $eocd['entradas']; $i++) {
            if (substr($binario, $puntero, 4) !== "PK\x01\x02") {
                throw new RuntimeException('entrada ' . $i . ' del directorio central corrupta');
            }

            // Los campos se leen por desplazamiento explícito. Una sola cadena
            // de formato para toda la cabecera se desalinea en cuanto se
            // mezclan campos de 2 y de 4 bytes, y el error aparece más tarde
            // como un CRC que no cuadra.
            $u16 = static fn (int $off): int => unpack('v', substr($binario, $puntero + $off, 2))[1];
            $u32 = static fn (int $off): int => unpack('V', substr($binario, $puntero + $off, 4))[1];

            $c = array(
                'metodo' => $u16(10),
                'crc'    => $u32(16),
                'comp'   => $u32(20),
                'crudo'  => $u32(24),
                'lnom'   => $u16(28),
                'lextra' => $u16(30),
                'lcom'   => $u16(32),
                'off'    => $u32(42),
            );

            $nombre = substr($binario, $puntero + 46, $c['lnom']);

            // Cabecera local: su longitud de nombre y extra puede diferir de la
            // del directorio central, así que se leen de ahí.
            $lnomL   = unpack('v', substr($binario, $c['off'] + 26, 2))[1];
            $lextraL = unpack('v', substr($binario, $c['off'] + 28, 2))[1];
            $datos   = substr($binario, $c['off'] + 30 + $lnomL + $lextraL, $c['comp']);

            if ($c['metodo'] === 8) {
                $datos = gzinflate($datos);
                if ($datos === false) {
                    throw new RuntimeException('no se pudo descomprimir ' . $nombre);
                }
            }

            if (crc32($datos) !== $c['crc']) {
                throw new RuntimeException('CRC no coincide en ' . $nombre);
            }
            if (strlen($datos) !== $c['crudo']) {
                throw new RuntimeException('tamaño no coincide en ' . $nombre);
            }

            $salida[$nombre] = $datos;
            $puntero += 46 + $c['lnom'] + $c['lextra'] + $c['lcom'];
        }

        return $salida;
    }

    /**
     * Celdas de una hoja como `referencia => array('v' => valor, 's' => estilo)`.
     *
     * @param string $xml contenido de `xl/worksheets/sheetN.xml`
     */
    public static function celdas($xml)
    {
        $doc = new DOMDocument();
        if (!$doc->loadXML($xml)) {
            throw new RuntimeException('la hoja no es XML válido');
        }

        $out = array();
        foreach ($doc->getElementsByTagName('c') as $c) {
            $ref = $c->getAttribute('r');
            $tipo = $c->getAttribute('t');

            if ($tipo === 'inlineStr') {
                $t = $c->getElementsByTagName('t')->item(0);
                $valor = $t ? $t->nodeValue : '';
            } else {
                $v = $c->getElementsByTagName('v')->item(0);
                $valor = $v ? $v->nodeValue : null;
            }

            $out[$ref] = array(
                'v'    => $valor,
                's'    => $c->getAttribute('s'),
                'tipo' => $tipo === 'inlineStr' ? 'texto' : ($valor === null ? 'vacio' : 'numero'),
            );
        }
        return $out;
    }

    /** Nombres de las hojas, en orden. */
    public static function hojas($workbookXml)
    {
        $doc = new DOMDocument();
        $doc->loadXML($workbookXml);
        $n = array();
        foreach ($doc->getElementsByTagName('sheet') as $s) {
            $n[] = $s->getAttribute('name');
        }
        return $n;
    }
}
