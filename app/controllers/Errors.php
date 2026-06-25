<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Errors {

    public function error_404() {
        header("Location: http://gi3-softsolutions.com/");
        exit();
    }

    public function show_error($msg) {
        die($msg);
    }

}