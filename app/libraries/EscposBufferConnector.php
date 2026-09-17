<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
defined('BASEPATH') or exit('No direct script access allowed');

use Mike42\Escpos\PrintConnectors\PrintConnector;

/**
 * Print connector that accumulates ESC/POS bytes in memory instead of sending
 * them to a physical connector. Used to hand the raw bytes to the browser
 * (QZ Tray), which prints them on whichever local printer the terminal has
 * configured. Unlike Mike42\Escpos\PrintConnectors\DummyPrintConnector (final,
 * and its finalize() discards the buffer), getData() here stays readable
 * after finalize() since Printer::close() always calls finalize() before the
 * controller gets a chance to read the bytes.
 */
class EscposBufferConnector implements PrintConnector
{
    private $buffer = [];
    private $finalized = false;

    public function __destruct()
    {
    }

    public function finalize()
    {
        $this->finalized = true;
    }

    public function read($len)
    {
        return '';
    }

    public function write($data)
    {
        $this->buffer[] = $data;
    }

    public function getData()
    {
        return implode('', $this->buffer);
    }
}
