<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Errors {

    public function error_404() {
        header("Location: /");
        exit();
    }

    public function show_error($msg) {
        die($msg);
    }

}