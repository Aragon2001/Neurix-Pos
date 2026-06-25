<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
 *  ==============================================================================
 *  Author  : Rhonald Brito
 *  Email   : admin@gi3-softsolutions.com
 *  For     : ESC/POS Print Driver for PHP
 *  License : MIT License
 *  ==============================================================================
 */

use Mike42\Escpos\Printer;
use Mike42\Escpos\EscposImage;
use Mike42\Escpos\CapabilityProfile;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\ImagickEscposImage;
use Mike42\Escpos\Imagick;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;

class Escpos
{

    public $printer;
    public $char_per_line;
    private $barcodeprinter = true;

    public function __construct()
    {
        $this->load->helper('text');
        $this->char_per_line = get_printer_chars_per_line();
    }

    public function __get($var)
    {
        return get_instance()->$var;
    }

    function intLowHigh($input, $length)
    {
        // Function to encode a number as two bytes. This is straight out of Mike42\Escpos\Printer
        $outp = "";
        for ($i = 0; $i < $length; $i++) {
            $outp .= chr($input % 256);
            $input = (int) ($input / 256);
        }
        return $outp;
    }

    function load($printer)
    {
        $div = explode("/", $printer->ip_address);
        if (isset($div[1])) {
            $this->barcodeprinter = $div[1];
            $printer->ip_address = $div[0];
        }
        if ($printer->type == 'network') {
            set_time_limit(30);
            $connector = new WindowsPrintConnector($printer->path);
        } elseif ($printer->type == 'linux') {
            $connector = new FilePrintConnector($printer->path);
        } else {
            $connector = new WindowsPrintConnector($printer->path);
        }
        $connector->write(Printer::GS . 'L' . $this->intLowHigh(8, 8));
        $this->char_per_line = $printer->char_per_line;
        $profile = CapabilityProfile::load($printer->profile);
        $this->printer = new Printer($connector, $profile);
    }

    function print_img($img, $cd = false)
    {

        $this->printer->setJustification(Printer::JUSTIFY_CENTER);
        $img = EscposImage::load(FCPATH . 'files' . DIRECTORY_SEPARATOR . 'receipts' . DIRECTORY_SEPARATOR . $img, false);
        $this->printer->bitImageColumnFormat($img);
        $this->printer->feed(2);
        $this->printer->cut();
        if ($cd) {
            $this->printer->pulse();
        }
        $this->printer->close();
    }

    function print_data($data, $store = null)
    {


        if (isset($data->headingTiquete) && !empty($data->headingTiquete)) {
            $this->printer->setJustification(Printer::JUSTIFY_CENTER);
            $this->printer->setEmphasis(true);
            $this->printer->setTextSize(2, 2);
            $this->printer->text($data->headingTiquete . "\n");
            $this->printer->setEmphasis(false);
            $this->printer->setTextSize(1, 1);
            $this->printer->feed();
            $this->printer->feed();
            $this->printer->feed();
        }

        if ($store) {
            $this->printer->setJustification(Printer::JUSTIFY_CENTER);
            $this->printer->setEmphasis(true);
            $this->printer->setTextSize(2, 2);
            $this->printer->text($store->name . "\n");
            $this->printer->setEmphasis(false);
            $this->printer->setTextSize(1, 1);
            $this->printer->text($store->address1 . "\n");
            if ($store->address2) {
                $this->printer->text($store->address2 . "\n");
            }
            $this->printer->text($store->city . "\n");
            $this->printer->text(lang('tel') . ': ' . $store->phone . "\n");
        }

        $this->printer->setJustification(Printer::JUSTIFY_CENTER);
        if (isset($data->logo) && !empty($data->logo)) {
            $logo = EscposImage::load(FCPATH . 'uploads' . DIRECTORY_SEPARATOR . $data->logo, false);
            $this->printer->bitImage($logo);
        }

        if (isset($data->heading) && !empty($data->heading)) {
            $this->printer->setEmphasis(true);
            $this->printer->setTextSize(2, 2);
            $this->printer->text($data->heading . "\n");
            $this->printer->setEmphasis(false);
            $this->printer->setTextSize(1, 1);
            $this->printer->feed();
        }
        $this->printer->setJustification(Printer::JUSTIFY_LEFT);

        if (isset($data->info) && !empty($data->info)) {
            foreach ($data->info as $info) {
                if ($info->label == 'line') {
                    $this->printer->text($this->drawLine());
                } else if ($info->label == 'space') {
                    $this->printer->text(' ' . $info->value . "\n");
                } else {
                    $this->printer->text($info->label . ': ' . $info->value . "\n");
                }
            }
            $this->printer->feed();
        }

        if (isset($data->infoTiquete) && !empty($data->infoTiquete)) {
            $this->printer->setJustification(Printer::JUSTIFY_CENTER);
            $this->printer->setTextSize(2, 2);
            foreach ($data->infoTiquete as $info) {
                if ($info->label == 'line') {
                    $this->printer->text($this->drawLine());
                } else {
                    $this->printer->text($info->label . ': ' . $info->value . "\n");
                }
            }
            $this->printer->setTextSize(1, 1);
            $this->printer->feed();
            $this->printer->feed();
            $this->printer->setJustification(Printer::JUSTIFY_LEFT);
        }

        if (isset($data->items) && !empty($data->items)) {
            $r = 1;
            foreach ($data->items as $item) {
                $this->printer->text('#' . $r . ' ' . $this->product_name(addslashes($item->product_name)) . "\n");
                $this->printer->text($this->printLine('   ' . $item->quantity . " x " . $item->unit_price . ":  " . $item->subtotal) . "\n");
                $r++;
            }
            $this->printer->feed();
        }

        if (isset($data->totals) && !empty($data->totals)) {
            foreach ($data->totals as $total) {
                if ($total) {
                    if ($total->label == 'line') {
                        $this->printer->text($this->drawLine());
                    } else {
                        $this->printer->text($this->printLine($total->label . ': ' . $total->value) . "\n");
                    }
                }
            }
            $this->printer->feed();
        }


        if (isset($data->sign) && !empty($data->sign)) {
            $this->printer->setJustification(Printer::JUSTIFY_CENTER);
            $this->printer->feed();
            foreach ($data->sign as $sign) {
                if ($sign->label == 'line') {
                    $this->printer->text($this->drawLine());
                } else if ($sign->label == 'text') {
                    $this->printer->text($sign->value);
                } else {
                    $this->printer->text($this->printLine($sign->label . ': ' . $sign->value) . "\n");
                }
            }
            $this->printer->feed();
            $this->printer->feed();
            $this->printer->feed(2);
            $this->printer->feed();
        }

        if (isset($data->footer) && !empty($data->footer)) {
            $this->printer->setJustification(Printer::JUSTIFY_CENTER);
            $this->printer->feed(2);
            $this->printer->text($data->footer . "\n");
            $this->printer->feed();
        }

        $this->printer->feed();
        $this->printer->feed();
        $this->printer->cut();
        $this->printer->close();
    }

    function print_comanda($data, $store = null)
    {


        if (isset($data->headingTiquete) && !empty($data->headingTiquete)) {
            $this->printer->setJustification(Printer::JUSTIFY_CENTER);
            $this->printer->setEmphasis(true);
            $this->printer->setTextSize(2, 2);
            $this->printer->text($data->headingTiquete . "\n");
            $this->printer->setEmphasis(false);
            $this->printer->setTextSize(1, 1);
            $this->printer->feed();
            $this->printer->feed();
            $this->printer->feed();
        }

        if ($store) {
            $this->printer->setJustification(Printer::JUSTIFY_CENTER);
            $this->printer->setEmphasis(true);
            $this->printer->setTextSize(2, 2);
            $this->printer->text($store->name . "\n");
            $this->printer->setEmphasis(false);
            $this->printer->setTextSize(1, 1);
            $this->printer->text($store->address1 . "\n");
            if ($store->address2) {
                $this->printer->text($store->address2 . "\n");
            }
            $this->printer->text($store->city . "\n");
            $this->printer->text(lang('tel') . ': ' . $store->phone . "\n");
        }

        $this->printer->setJustification(Printer::JUSTIFY_CENTER);
        if (isset($data->logo) && !empty($data->logo)) {
            $logo = EscposImage::load(FCPATH . 'uploads' . DIRECTORY_SEPARATOR . $data->logo, false);
            $this->printer->bitImage($logo);
        }

        if (isset($data->heading) && !empty($data->heading)) {
            $this->printer->setEmphasis(true);
            $this->printer->setTextSize(2, 2);
            $this->printer->text($data->heading . "\n");
            $this->printer->setEmphasis(false);
            $this->printer->setTextSize(1, 1);
            $this->printer->feed();
        }
        $this->printer->setJustification(Printer::JUSTIFY_LEFT);

        if (isset($data->info) && !empty($data->info)) {
            foreach ($data->info as $info) {
                if ($info->label == 'line') {
                    $this->printer->text($this->drawLine());
                } else if ($info->label == 'space') {
                    $this->printer->text(' ' . $info->value . "\n");
                } else {
                    $this->printer->text($info->label . ': ' . $info->value . "\n");
                }
            }
            $this->printer->feed();
        }

        if (isset($data->infoTiquete) && !empty($data->infoTiquete)) {
            $this->printer->setJustification(Printer::JUSTIFY_CENTER);
            $this->printer->setTextSize(2, 2);
            foreach ($data->infoTiquete as $info) {
                if ($info->label == 'line') {
                    $this->printer->text($this->drawLine());
                } else {
                    $this->printer->text($info->label . ': ' . $info->value . "\n");
                }
            }
            $this->printer->setTextSize(1, 1);
            $this->printer->feed();
            $this->printer->feed();
            $this->printer->setJustification(Printer::JUSTIFY_LEFT);
        }

        if (isset($data->items) && !empty($data->items)) {
            $r = 1;
            foreach ($data->items as $item) {
                $this->printer->text('#' . $r . ' ' . $this->product_name(addslashes($item->product_name)) . "\n");
                $this->printer->text($this->printLine('   ' . $item->quantity . " x " . $item->unit_price . ":  " . $item->subtotal) . "\n");
                $r++;
            }
            $this->printer->feed();
        }

        if (isset($data->totals) && !empty($data->totals)) {
            foreach ($data->totals as $total) {
                if ($total) {
                    if ($total->label == 'line') {
                        $this->printer->text($this->drawLine());
                    } else {
                        $this->printer->text($this->printLine($total->label . ': ' . $total->value) . "\n");
                    }
                }
            }
            $this->printer->feed();
        }


        if (isset($data->sign) && !empty($data->sign)) {
            $this->printer->setJustification(Printer::JUSTIFY_CENTER);
            $this->printer->feed();
            foreach ($data->sign as $sign) {
                if ($sign->label == 'line') {
                    $this->printer->text($this->drawLine());
                } else if ($sign->label == 'text') {
                    $this->printer->text($sign->value);
                } else {
                    $this->printer->text($this->printLine($sign->label . ': ' . $sign->value) . "\n");
                }
            }
            $this->printer->feed();
            $this->printer->feed();
            $this->printer->feed(2);
            $this->printer->feed();
        }

        if (isset($data->footer) && !empty($data->footer)) {
            $this->printer->setJustification(Printer::JUSTIFY_CENTER);
            $this->printer->feed(2);
            $this->printer->text($data->footer . "\n");
            $this->printer->feed();
        }

        $this->printer->feed();
        $this->printer->feed();
        $this->printer->cut();
        $this->printer->close();
    }

    function print_receipt($store, $sale, $items, $payments, $created_by, $open_drawer = false, $bill = false)
    {

        if ($open_drawer) {
            $this->printer->pulse();
            sleep(1);
        }
        $this->printer->setJustification(Printer::JUSTIFY_CENTER);
        $this->printer->setEmphasis(true);
        $this->printer->setTextSize(2, 2);
        $this->printer->text($store->name . "\n");
        $this->printer->setEmphasis(false);
        $this->printer->setTextSize(1, 1);
        $this->printer->text($store->address1 . "\n");
        if ($store->address2) {
            $this->printer->text($store->address2 . "\n");
        }
        $this->printer->text($store->city . "\n");
        $this->printer->text(lang('tel') . ': ' . $store->phone . "\n");
        $this->printer->text($store->receipt_header . "\n");
        $this->printer->setJustification(Printer::JUSTIFY_LEFT);

        if ($this->Settings->fe == '0') {
            $this->printer->text('Factura' . "\n");
        } else {
            if (isset($sale->hacienda->tipo_doc)) {
                if ($sale->hacienda->tipo_doc === "4") {
                    $this->printer->text("Tiquete Electronico" . "\n");
                } else if ($sale->hacienda->tipo_doc === "1") {
                    $this->printer->text("Factura Electronica" . "\n");
                }
            } else if ($sale->type_doc === "Nota de Credito Electronica") {
                $this->printer->text($sale->type_doc . "\n");
            }
            // else if($sale->hacienda->tipo_doc === "3"){
            //     $this->printer->text("Factura Nota de credito" . "\n");
            // }
        }

        $this->printer->text(lang('date') . ': ' . $this->tec->hrld($sale->date) . "\n");
        if (isset($sale->hacienda->consecutivo)) {
            if ($this->Settings->fe == '0') {
                $this->printer->text(lang('Factura N°') . ": " . $sale->id . "\n");
            } else {
                $this->printer->text(lang('Consecutivo N°') . ": " . $sale->hacienda->consecutivo . "\n");
            }
        } else if ($sale->type_doc == "Recibo de apartado") {
            $this->printer->text(lang('Apartado N°') . ": " . $sale->id . "\n");
        } else if ($sale->type_doc == "Proforma") {
            $this->printer->text(lang('Proforma N°') . ": " . $sale->id . "\n");
        }

        $this->printer->text(lang("customer") . ": " . $sale->customer_name . "\n");
        if (isset($sale->customer->cf2)) {
            $this->printer->text("Identificacion: " . $sale->customer->cf2 . "\n");
        }
        $this->printer->text(lang("sales_person") . ": " . $created_by->first_name . " " . $created_by->last_name . "\n");
        $this->printer->feed();


        $r = 1;
        $totImpuesto = 0;
        $totImpuesto1 = 0;
        $totImpuesto2 = 0;
        $totImpuesto3 = 0;
        $totImpuesto4 = 0;
        $totImpuesto5 = 0;
        $totImpuesto6 = 0;
        $totImpuesto7 = 0;
        $totImpuesto8 = 0;
        $totImpuesto9 = 0;
        $totImpuesto10 = 0;
        $totImpuesto11 = 0;
        $totImpuesto12 = 0;
        $totImpuesto13 = 0;
        $totSimpuesto = 0;
        $totCimpuesto = 0;
        $montoExoneracion = 0;
        $servicio = null;
        $subtotal = 0;
        foreach ($items as $item) {
            $subtotal += $item->subtotal;
            if ($item->product_code == "9r091n4") {
                $servicio = $item;
                continue;
            }
            if ($item->tax > 0) {
                $tt = "(G " . $item->tax . "%)";
            } else {
                $tt = "(E)";
            }

            if ($item->tax_method == '0') {
                $item->item_tax = (($item->net_unit_price * $item->tax) / 100);
                $totImpuesto = $totImpuesto + ($item->item_tax * $item->quantity);
            } else if ($item->tax_method == '1') {
                $item->real_unit_price = $item->net_unit_price + (($item->net_unit_price * $item->tax) / 100);
                $totImpuesto = $totImpuesto + ($item->item_tax);
            }

            switch ($item->tax) {
                case '1':
                    $totImpuesto1 = $totImpuesto1 + $item->item_tax;
                    break;
                case '2':
                    $totImpuesto2 = $totImpuesto2 + $item->item_tax;
                    break;
                case '3':
                    $totImpuesto3 = $totImpuesto3 + $item->item_tax;
                    break;
                case '4':
                    $totImpuesto4 = $totImpuesto4 + $item->item_tax;
                    break;
                case '5':
                    $totImpuesto5 = $totImpuesto5 + $item->item_tax;
                    break;
                case '6':
                    $totImpuesto6 = $totImpuesto6 + $item->item_tax;
                    break;
                case '7':
                    $totImpuesto7 = $totImpuesto7 + $item->item_tax;
                    break;
                case '8':
                    $totImpuesto8 = $totImpuesto8 + $item->item_tax;
                    break;
                case '9':
                    $totImpuesto9 = $totImpuesto9 + $item->item_tax;
                    break;
                case '10':
                    $totImpuesto10 = $totImpuesto10 + $item->item_tax;
                    break;
                case '11':
                    $totImpuesto11 = $totImpuesto11 + $item->item_tax;
                    break;
                case '12':
                    $totImpuesto12 = $totImpuesto12 + $item->item_tax;
                    break;
                case '13':
                    $totImpuesto13 = $totImpuesto13 + $item->item_tax;
                    break;
            };

            if ($item->tax > 0) {
                $totCimpuesto += ($item->real_unit_price * $item->quantity);
            } else {
                $totSimpuesto += ($item->net_unit_price * $item->quantity);
            }
            if (isset($sale->MontoExoneracion)) {
                if ($sale->MontoExoneracion  != 0 && $sale->MontoExoneracion != null) {
                    $montoExoneracion = $sale->MontoExoneracion;
                }
            }
            $this->printer->text('#' . $r . ' ' . $this->product_name(addslashes($item->product_name . ' ' . $tt)) . "\n");
            $this->printer->text(
                $this->printLine(
                    '   ' . '(' . $item->unit_of_measurement . ')' . $this->tec->formatQuantity($item->quantity) . " x " . $this->tec->formatMoney($item->real_unit_price) . ":  " . $this->tec->formatMoney(($item->real_unit_price * $item->quantity))
                ) . "\n"
            );
            $r++;
        }
        if ($totCimpuesto > 0) {
            $this->printer->text($this->drawLine());
            $this->printer->text($this->printLine(lang("Total con Impuesto") . ":" . $this->tec->formatMoney($totCimpuesto)) . "\n");
        }
        $this->printer->text($this->drawLine());
        $this->printer->text($this->printLine(lang("Total sin Impuesto") . ":" . $this->tec->formatMoney($totSimpuesto)) . "\n");
        if ($servicio) {
            $this->printer->text($this->printLine(lang($servicio->product_name) . ":" . $this->tec->formatMoney($servicio->real_unit_price)) . "\n");
        }

        if ($item->tax > 0) {
            $this->printer->text($this->drawLine());
        }

        if ($totImpuesto1) {
            $this->printer->text($this->printLine($this->Settings->enable_show_tax . ' cobrado' . " 1%:" . $this->tec->formatMoney($totImpuesto1)) . "\n");
        }

        if ($totImpuesto2) {
            $this->printer->text($this->printLine($this->Settings->enable_show_tax . ' cobrado' . " 2%:" . $this->tec->formatMoney($totImpuesto2)) . "\n");
        }

        if ($totImpuesto3) {
            $this->printer->text($this->printLine($this->Settings->enable_show_tax . ' cobrado' . " 3%:" . $this->tec->formatMoney($totImpuesto3)) . "\n");
        }

        if ($totImpuesto4) {
            $this->printer->text($this->printLine($this->Settings->enable_show_tax . ' cobrado' . " 4%:" . $this->tec->formatMoney($totImpuesto4)) . "\n");
        }

        if ($totImpuesto5) {
            $this->printer->text($this->printLine($this->Settings->enable_show_tax . ' cobrado' . " 5%:" . $this->tec->formatMoney($totImpuesto5)) . "\n");
        }

        if ($totImpuesto6) {
            $this->printer->text($this->printLine($this->Settings->enable_show_tax . ' cobrado' . " 6%:" . $this->tec->formatMoney($totImpuesto6)) . "\n");
        }

        if ($totImpuesto7) {
            $this->printer->text($this->printLine($this->Settings->enable_show_tax . ' cobrado' . " 7%:" . $this->tec->formatMoney($totImpuesto7)) . "\n");
        }

        if ($totImpuesto8) {
            $this->printer->text($this->printLine($this->Settings->enable_show_tax . ' cobrado' . " 8%:" . $this->tec->formatMoney($totImpuesto8)) . "\n");
        }

        if ($totImpuesto9) {
            $this->printer->text($this->printLine($this->Settings->enable_show_tax . ' cobrado' . " 9%:" . $this->tec->formatMoney($totImpuesto9)) . "\n");
        }

        if ($totImpuesto10) {
            $this->printer->text($this->printLine($this->Settings->enable_show_tax . ' cobrado' . " 10%:" . $this->tec->formatMoney($totImpuesto10)) . "\n");
        }

        if ($totImpuesto11) {
            $this->printer->text($this->printLine($this->Settings->enable_show_tax . ' cobrado' . " 11%:" . $this->tec->formatMoney($totImpuesto11)) . "\n");
        }

        if ($totImpuesto12) {
            $this->printer->text($this->printLine($this->Settings->enable_show_tax . ' cobrado' . " 12%:" . $this->tec->formatMoney($totImpuesto12)) . "\n");
        }

        if ($totImpuesto13) {
            $this->printer->text($this->printLine($this->Settings->enable_show_tax . ' cobrado' . " 13%:" . $this->tec->formatMoney($totImpuesto13)) . "\n");
        }
        $this->printer->text($this->drawLine());
        $this->printer->text($this->printLine(lang("subtotal") . ":" . $this->tec->formatMoney(($subtotal + $sale->total_discount) - $totImpuesto)) . "\n");
        if ($sale->total_discount != 0) {
            $this->printer->text($this->printLine(lang("discount") . ":" . "-" . $this->tec->formatMoney($sale->total_discount)) . "\n");
        }
        if ($this->Settings->enable_show_tax != "") {
            $this->printer->text($this->printLine("Total " . $this->Settings->enable_show_tax . ' cobrado' . ":" . $this->tec->formatMoney($totImpuesto)) . "\n");
        }

        //dd($sale);
        if ($montoExoneracion != 0 && $montoExoneracion != null) {
            $this->printer->text($this->drawLine());
            $this->printer->text($this->printLine(lang("Exoneracion") . " %" . $sale->PorcentajeExoneracion . ": -" . $this->tec->formatMoney($montoExoneracion)) . "\n");
        }

        if ($this->Settings->rounding) {
            $round_total = $this->tec->roundNumber($sale->grand_total, $this->Settings->rounding);
            $rounding = $round_total - $sale->grand_total;
            $this->printer->text($this->printLine(lang("rounding") . ":" . $this->tec->formatMoney($rounding)) . "\n");
            $this->printer->text($this->printLine(lang("grand_total") . ":" . $this->tec->formatMoney($sale->grand_total + $rounding)) . "\n");
            if ($sale->total_discount != 0) {
                $this->printer->text($this->printLine(lang("grand_total_discount") . ":" . $this->tec->formatMoney($sale->grand_total + $rounding + $sale->total_discount)) . "\n");
            }
        } else {
            $round_total = $sale->grand_total;
            $this->printer->text($this->printLine(lang("grand_total") . ":" . $this->tec->formatMoney($sale->grand_total)) . "\n");
            if ($sale->total_discount != 0) {
                $this->printer->text($this->printLine(lang("grand_total_discount") . ":" . $this->tec->formatMoney($sale->grand_total + $sale->total_discount)) . "\n");
            }
        }

        if (!$sale->haciendaInvo) {
            if ($sale->paid < $round_total && !$bill) {
                $this->printer->text($this->printLine(lang("paid_amount") . ":" . $this->tec->formatMoney($sale->paid)) . "\n");
                $this->printer->text($this->printLine(lang("due_amount") . ":" . $this->tec->formatMoney($sale->grand_total - $sale->paid)) . "\n");
            }
        }

        if (!$bill) {
            $this->printer->text($this->drawLine());
            if (!$sale->haciendaInvo) {
                if ($payments) {

                    foreach ($payments as $payment) {
                        if ($payment->paid_by == 'cash' && $payment->amount) {
                            $this->printer->text($this->printLine(lang($payment->paid_by) . ":" . $this->tec->formatMoney($payment->amount)) . "\n");
                            $this->printer->setEmphasis(true);
                            $this->printer->text($this->printLine(lang("change") . ":" . ($payment->pos_balance > 0 ? $this->tec->formatMoney($payment->pos_balance) : 0)) . "\n");
                            $this->printer->setEmphasis(false);
                        } elseif ($payment->paid_by == 'CC') {
                            $this->printer->text($this->printLine($payment->cc_type . ":" . $this->tec->formatMoney($payment->amount)) . "\n");
                        } elseif ($payment->paid_by == 'gift_card') {
                            $this->printer->text($this->printLine(lang("paid_by") . ":" . lang($payment->paid_by)) . "\n");
                            $this->printer->text($this->printLine(lang("amount") . ":" . $this->tec->formatMoney($payment->pos_paid)) . "\n");
                            $this->printer->text($this->printLine(lang("card_no") . ":" . $payment->gc_no) . "\n");
                        } elseif ($payment->paid_by == 'Cheque' || $payment->paid_by == 'cheque' && $payment->cheque_no) {
                            $this->printer->text($this->printLine(lang("paid_by") . ":" . lang($payment->paid_by)) . "\n");
                            $this->printer->text($this->printLine(lang("amount") . ":" . $this->tec->formatMoney($payment->pos_paid)) . "\n");
                            $this->printer->text($this->printLine(lang("cheque_no") . ":" . $payment->cheque_no) . "\n");
                        } elseif ($payment->paid_by == 'other' && $payment->amount) {
                            $this->printer->text($this->printLine(lang("paid_by") . ":" . lang($payment->paid_by)) . "\n");
                            $this->printer->text($this->printLine(lang("amount") . ":" . $this->tec->formatMoney($payment->amount)) . "\n");
                            $this->printer->text($this->printLine(lang("payment_note") . ":" . $payment->note) . "\n");
                        }
                    }
                }
            }

            if ($sale->note) {
                $this->printer->feed();
                $this->printer->text($this->drawLine());
                $this->printer->setEmphasis(true);
                $this->printer->text("NOTA: " . $sale->note . "\n");
                $this->printer->setEmphasis(false);
                $this->printer->text($this->drawLine());
            }

            $this->printer->feed();
            $this->printer->text("Gravado (G), Exento (E)" . "\n");


            $this->printer->setJustification(Printer::JUSTIFY_CENTER);
            if ($this->Settings->fe == '1') {
                if ($sale->haciendaInvo) {
                    $this->printer->text(lang('reason_credit_note') . ': ' . $sale->hold_ref . "\n");
                    $this->printer->text(lang('consecutive_referenced_document') . ': ' . $sale->haciendaInvo->consecutivo . "\n");
                    $this->printer->text(lang('key_referenced_document') . ': ' . $sale->haciendaInvo->clave . "\n");
                    $this->printer->feed();
                }


                if ($sale->hacienda) {
                    $this->printer->text("Clave del Comprobante Electronico" . "\n");
                    $this->printer->text($this->LineWrap($sale->hacienda->clave . "\n"));
                    $this->printer->feed();
                }
            }



            if ($this->Settings->fe == '0') {
                $this->printer->text($this->LineWrap("Autorizado mediante Oficio 11-97 de D.G.T.D" . "\n"));
                $this->printer->feed();
            } else {
                $this->printer->text($this->LineWrap($sale->footerhacienda . "\n"));
                $this->printer->feed();
            }

            $this->printer->feed();
            if (isset($sale->hacienda->consecutivo)) {
                if ($this->barcodeprinter === true) {
                    $this->printer->text(lang('internal_id') . "\n");
                    $this->printer->feed();

                    $uri = $sale->invice_barcode;
                    $file_path = realpath(dirname(__FILE__));
                    $folder_path = dirname(sys_get_temp_dir());
                    $file = date('Y-m-d-H-i-s-') . uniqid() . '.png';
                    $filename = $folder_path . "/" . $file;
                    $imgData = str_replace('data:image/png;base64,', '', $uri);
                    $imgData = str_replace(' ', '+', $imgData);
                    $imgData = base64_decode($imgData);
                    file_put_contents($filename, $imgData);
                    $img = EscposImage::load($filename, false);
                    $this->printer->bitImageColumnFormat($img, Printer::IMG_DOUBLE_WIDTH);
                    $this->printer->feed();
                }
            }
        } else {
            $this->printer->setJustification(Printer::JUSTIFY_CENTER);
            $this->printer->feed(2);
            $this->printer->text($this->LineWrap(lang('bill_note') . "\n"));
        }

        $this->printer->feed();
        $this->printer->cut();
        $this->printer->close();
    }

    function print_receipt_suspended($store, $sale, $items, $payments, $created_by, $open_drawer = false, $bill = false)
    {

        if ($open_drawer) {
            $this->printer->pulse();
            sleep(1);
        }
        $this->printer->setJustification(Printer::JUSTIFY_CENTER);
        $this->printer->setEmphasis(true);
        $this->printer->setTextSize(2, 2);
        $this->printer->text($store->name . "\n");
        $this->printer->setEmphasis(false);
        $this->printer->setTextSize(1, 1);
        $this->printer->text($store->address1 . "\n");
        if ($store->address2) {
            $this->printer->text($store->address2 . "\n");
        }
        $this->printer->text($store->city . "\n");
        $this->printer->text(lang('tel') . ': ' . $store->phone . "\n");
        $this->printer->text($store->receipt_header . "\n");
        $this->printer->setJustification(Printer::JUSTIFY_LEFT);

        if ($this->Settings->fe == '0') {
            $this->printer->text('Factura' . "\n");
        } else {
                $this->printer->text("Factura en espera" . "\n");
            
        }

        $this->printer->text(lang('date') . ': ' . $this->tec->hrld($sale->date) . "\n");
        if (isset($sale->hacienda->consecutivo)) {
            if ($this->Settings->fe == '0') {
                $this->printer->text(lang('Factura N°') . ": " . $sale->id . "\n");
            } else {
                $this->printer->text(lang('Consecutivo N°') . ": " . $sale->hacienda->consecutivo . "\n");
            }
        } else if ($sale->type_doc == "Recibo de apartado") {
            $this->printer->text(lang('Apartado N°') . ": " . $sale->id . "\n");
        } else if ($sale->type_doc == "Proforma") {
            $this->printer->text(lang('Proforma N°') . ": " . $sale->id . "\n");
        }

        $this->printer->text(lang("customer") . ": " . $sale->customer_name . "\n");
        if ($sale->customer->cf2) {
            $this->printer->text("Identificacion: " . $sale->customer->cf2 . "\n");
        }
        $this->printer->text(lang("sales_person") . ": " . $created_by->first_name . " " . $created_by->last_name . "\n");
        $this->printer->feed();


        $r = 1;
        $totImpuesto = 0;
        $totImpuesto1 = 0;
        $totImpuesto2 = 0;
        $totImpuesto3 = 0;
        $totImpuesto4 = 0;
        $totImpuesto5 = 0;
        $totImpuesto6 = 0;
        $totImpuesto7 = 0;
        $totImpuesto8 = 0;
        $totImpuesto9 = 0;
        $totImpuesto10 = 0;
        $totImpuesto11 = 0;
        $totImpuesto12 = 0;
        $totImpuesto13 = 0;
        $totSimpuesto = 0;
        $totCimpuesto = 0;
        $servicio = null;
        // dd($items);
        foreach ($items as $item) {
            if ($item->product_code == "9r091n4") {
                $servicio = $item;
                continue;
            }
            if ($item->tax > 0) {
                $tt = "(G " . $item->tax . "%)";
            } else {
                $tt = "(E)";
            }
            // if ($item->tax_method == '0') {
            //     $item->item_tax = (($item->net_unit_price * $item->tax) / 100);
            //     $totImpuesto = $totImpuesto + ($item->item_tax * $item->quantity);
            // } else if ($item->tax_method == '1') {
            //     $item->real_unit_price = $item->net_unit_price + (($item->net_unit_price * $item->tax) / 100);
            //     $totImpuesto = $totImpuesto + ($item->item_tax);
            // }

            switch ($item->tax) {
                case '1':
                    $totImpuesto1 = $totImpuesto1 + $item->item_tax;
                    break;
                case '2':
                    $totImpuesto2 = $totImpuesto2 + $item->item_tax;
                    break;
                case '3':
                    $totImpuesto3 = $totImpuesto3 + $item->item_tax;
                    break;
                case '4':
                    $totImpuesto4 = $totImpuesto4 + $item->item_tax;
                    break;
                case '5':
                    $totImpuesto5 = $totImpuesto5 + $item->item_tax;
                    break;
                case '6':
                    $totImpuesto6 = $totImpuesto6 + $item->item_tax;
                    break;
                case '7':
                    $totImpuesto7 = $totImpuesto7 + $item->item_tax;
                    break;
                case '8':
                    $totImpuesto8 = $totImpuesto8 + $item->item_tax;
                    break;
                case '9':
                    $totImpuesto9 = $totImpuesto9 + $item->item_tax;
                    break;
                case '10':
                    $totImpuesto10 = $totImpuesto10 + $item->item_tax;
                    break;
                case '11':
                    $totImpuesto11 = $totImpuesto11 + $item->item_tax;
                    break;
                case '12':
                    $totImpuesto12 = $totImpuesto12 + $item->item_tax;
                    break;
                case '13':
                    $totImpuesto13 = $totImpuesto13 + $item->item_tax;
                    break;
            };

            if ($item->tax > 0) {
                $totCimpuesto += ($item->real_unit_price * $item->quantity);
            } else {
                $totSimpuesto += ($item->net_unit_price * $item->quantity);
            }

            $this->printer->text('#' . $r . ' ' . $this->product_name(addslashes($item->product_name . ' ' . $tt)) . "\n");
            $this->printer->text(
                $this->printLine(
                    '   ' . $this->tec->formatQuantity($item->quantity) . " x " . $this->tec->formatMoney($item->real_unit_price) . ":  " . $this->tec->formatMoney(($item->real_unit_price * $item->quantity))
                ) . "\n"
            );
            $r++;
        }
        if ($totCimpuesto > 0) {
            $this->printer->text($this->drawLine());
            $this->printer->text($this->printLine(lang("Total con Impuesto") . ":" . $this->tec->formatMoney($totCimpuesto)) . "\n");
        }
        $this->printer->text($this->drawLine());
        $this->printer->text($this->printLine(lang("Total sin Impuesto") . ":" . $this->tec->formatMoney($totSimpuesto)) . "\n");
        if ($servicio) {
            $this->printer->text($this->printLine(lang($servicio->product_name) . ":" . $this->tec->formatMoney($servicio->real_unit_price)) . "\n");
        }

        if ($item->tax > 0) {
            $this->printer->text($this->drawLine());
        }

        if ($totImpuesto1) {
            $this->printer->text($this->printLine($this->Settings->enable_show_tax . ' cobrado' . " 1%:" . $this->tec->formatMoney($totImpuesto1)) . "\n");
        }

        if ($totImpuesto2) {
            $this->printer->text($this->printLine($this->Settings->enable_show_tax . ' cobrado' . " 2%:" . $this->tec->formatMoney($totImpuesto2)) . "\n");
        }

        if ($totImpuesto3) {
            $this->printer->text($this->printLine($this->Settings->enable_show_tax . ' cobrado' . " 3%:" . $this->tec->formatMoney($totImpuesto3)) . "\n");
        }

        if ($totImpuesto4) {
            $this->printer->text($this->printLine($this->Settings->enable_show_tax . ' cobrado' . " 4%:" . $this->tec->formatMoney($totImpuesto4)) . "\n");
        }

        if ($totImpuesto5) {
            $this->printer->text($this->printLine($this->Settings->enable_show_tax . ' cobrado' . " 5%:" . $this->tec->formatMoney($totImpuesto5)) . "\n");
        }

        if ($totImpuesto6) {
            $this->printer->text($this->printLine($this->Settings->enable_show_tax . ' cobrado' . " 6%:" . $this->tec->formatMoney($totImpuesto6)) . "\n");
        }

        if ($totImpuesto7) {
            $this->printer->text($this->printLine($this->Settings->enable_show_tax . ' cobrado' . " 7%:" . $this->tec->formatMoney($totImpuesto7)) . "\n");
        }

        if ($totImpuesto8) {
            $this->printer->text($this->printLine($this->Settings->enable_show_tax . ' cobrado' . " 8%:" . $this->tec->formatMoney($totImpuesto8)) . "\n");
        }

        if ($totImpuesto9) {
            $this->printer->text($this->printLine($this->Settings->enable_show_tax . ' cobrado' . " 9%:" . $this->tec->formatMoney($totImpuesto9)) . "\n");
        }

        if ($totImpuesto10) {
            $this->printer->text($this->printLine($this->Settings->enable_show_tax . ' cobrado' . " 10%:" . $this->tec->formatMoney($totImpuesto10)) . "\n");
        }

        if ($totImpuesto11) {
            $this->printer->text($this->printLine($this->Settings->enable_show_tax . ' cobrado' . " 11%:" . $this->tec->formatMoney($totImpuesto11)) . "\n");
        }

        if ($totImpuesto12) {
            $this->printer->text($this->printLine($this->Settings->enable_show_tax . ' cobrado' . " 12%:" . $this->tec->formatMoney($totImpuesto12)) . "\n");
        }

        if ($totImpuesto13) {
            $this->printer->text($this->printLine($this->Settings->enable_show_tax . ' cobrado' . " 13%:" . $this->tec->formatMoney($totImpuesto13)) . "\n");
        }
        $this->printer->text($this->drawLine());

        if ($this->Settings->enable_show_tax != "") {
            $this->printer->text($this->printLine("Total " . $this->Settings->enable_show_tax . ' cobrado' . ":" . $this->tec->formatMoney($totImpuesto)) . "\n");
        }

        if ($sale->total_discount != 0) {
            $this->printer->text($this->printLine(lang("discount") . ":" . "-" . $this->tec->formatMoney($sale->total_discount)) . "\n");
        }
        //dd($sale);

        if ($this->Settings->rounding) {
            $round_total = $this->tec->roundNumber($sale->grand_total, $this->Settings->rounding);
            $rounding = $round_total - $sale->grand_total;
            $this->printer->text($this->printLine(lang("rounding") . ":" . $this->tec->formatMoney($rounding)) . "\n");
            $this->printer->text($this->printLine(lang("grand_total") . ":" . $this->tec->formatMoney($sale->grand_total + $rounding)) . "\n");
        } else {
            $round_total = $sale->grand_total;
            $this->printer->text($this->printLine(lang("grand_total") . ":" . $this->tec->formatMoney($sale->grand_total)) . "\n");
        }

        if (!$sale->haciendaInvo) {
            if ($sale->paid < $round_total && !$bill) {
                $this->printer->text($this->printLine(lang("paid_amount") . ":" . $this->tec->formatMoney($sale->paid)) . "\n");
                $this->printer->text($this->printLine(lang("due_amount") . ":" . $this->tec->formatMoney($sale->grand_total - $sale->paid)) . "\n");
            }
        }

        if (!$bill) {
            $this->printer->text($this->drawLine());
            if (!$sale->haciendaInvo) {
                if ($payments) {

                    foreach ($payments as $payment) {
                        if ($payment->paid_by == 'cash' && $payment->amount) {
                            $this->printer->text($this->printLine(lang($payment->paid_by) . ":" . $this->tec->formatMoney($payment->amount)) . "\n");
                            $this->printer->setEmphasis(true);
                            $this->printer->text($this->printLine(lang("change") . ":" . ($payment->pos_balance > 0 ? $this->tec->formatMoney($payment->pos_balance) : 0)) . "\n");
                            $this->printer->setEmphasis(false);
                        } elseif ($payment->paid_by == 'CC') {
                            $this->printer->text($this->printLine($payment->cc_type . ":" . $this->tec->formatMoney($payment->amount)) . "\n");
                        } elseif ($payment->paid_by == 'gift_card') {
                            $this->printer->text($this->printLine(lang("paid_by") . ":" . lang($payment->paid_by)) . "\n");
                            $this->printer->text($this->printLine(lang("amount") . ":" . $this->tec->formatMoney($payment->pos_paid)) . "\n");
                            $this->printer->text($this->printLine(lang("card_no") . ":" . $payment->gc_no) . "\n");
                        } elseif ($payment->paid_by == 'Cheque' || $payment->paid_by == 'cheque' && $payment->cheque_no) {
                            $this->printer->text($this->printLine(lang("paid_by") . ":" . lang($payment->paid_by)) . "\n");
                            $this->printer->text($this->printLine(lang("amount") . ":" . $this->tec->formatMoney($payment->pos_paid)) . "\n");
                            $this->printer->text($this->printLine(lang("cheque_no") . ":" . $payment->cheque_no) . "\n");
                        } elseif ($payment->paid_by == 'other' && $payment->amount) {
                            $this->printer->text($this->printLine(lang("paid_by") . ":" . lang($payment->paid_by)) . "\n");
                            $this->printer->text($this->printLine(lang("amount") . ":" . $this->tec->formatMoney($payment->amount)) . "\n");
                            $this->printer->text($this->printLine(lang("payment_note") . ":" . $payment->note) . "\n");
                        }
                    }
                }
            }

            if ($sale->note) {
                $this->printer->feed();
                $this->printer->text($this->drawLine());
                $this->printer->setEmphasis(true);
                $this->printer->text("NOTA: " . $sale->note . "\n");
                $this->printer->setEmphasis(false);
                $this->printer->text($this->drawLine());
            }

            $this->printer->feed();
            $this->printer->text("Gravado (G), Exento (E)" . "\n");


            $this->printer->setJustification(Printer::JUSTIFY_CENTER);
            if ($this->Settings->fe == '1') {
                if ($sale->haciendaInvo) {
                    $this->printer->text(lang('reason_credit_note') . ': ' . $sale->hold_ref . "\n");
                    $this->printer->text(lang('consecutive_referenced_document') . ': ' . $sale->haciendaInvo->consecutivo . "\n");
                    $this->printer->text(lang('key_referenced_document') . ': ' . $sale->haciendaInvo->clave . "\n");
                    $this->printer->feed();
                }


                if ($sale->hacienda) {
                    $this->printer->text("Clave del Comprobante Electronico" . "\n");
                    $this->printer->text($this->LineWrap($sale->hacienda->clave . "\n"));
                    $this->printer->feed();
                }
            }



            if ($this->Settings->fe == '0') {
                $this->printer->text($this->LineWrap("Autorizado mediante Oficio 11-97 de D.G.T.D" . "\n Régimen Simplificado"));
                $this->printer->feed();
            } else {
                // $this->printer->text($this->LineWrap($sale->footerhacienda . "\n"));
                // $this->printer->feed();
            }

            $this->printer->feed();
            if (isset($sale->hacienda->consecutivo)) {
                if ($this->barcodeprinter === true) {
                    $this->printer->text(lang('internal_id') . "\n");
                    $this->printer->feed();

                    $uri = $sale->invice_barcode;
                    $file_path = realpath(dirname(__FILE__));
                    $folder_path = dirname(sys_get_temp_dir());
                    $file = date('Y-m-d-H-i-s-') . uniqid() . '.png';
                    $filename = $folder_path . "/" . $file;
                    $imgData = str_replace('data:image/png;base64,', '', $uri);
                    $imgData = str_replace(' ', '+', $imgData);
                    $imgData = base64_decode($imgData);
                    file_put_contents($filename, $imgData);
                    $img = EscposImage::load($filename, false);
                    $this->printer->bitImageColumnFormat($img, Printer::IMG_DOUBLE_WIDTH);
                    $this->printer->feed();
                }
            }
        } else {
            $this->printer->setJustification(Printer::JUSTIFY_CENTER);
            $this->printer->feed(2);
            $this->printer->text($this->LineWrap(lang('bill_note') . "\n"));
        }

        $this->printer->feed(2);
        $this->printer->cut();
        $this->printer->close();
    }

    function open_drawer()
    {
        $this->printer->pulse();
        $this->printer->close();
    }

    function print_order($store, $sale, $items, $created_by)
    {

        $this->printer->setJustification(Printer::JUSTIFY_CENTER);
        //      $logo = EscposImage::load(FCPATH.'uploads'.DIRECTORY_SEPARATOR.$store->logo, false);
        //      $this->printer->bitImage($logo);
        //      $this->printer->feed();
        $this->printer->setEmphasis(true);
        $this->printer->setTextSize(2, 2);
        $this->printer->text($store->name . "\n");
        $this->printer->setEmphasis(false);
        $this->printer->feed();
        $this->printer->setTextSize(1, 1);
        $this->printer->text($store->name . ' (' . $store->code . ')' . "\n");
        if (!empty($store->address1)) {
            $this->printer->text($store->address1 . "\n");
        }
        if (!empty($store->address2)) {
            $this->printer->text($store->address2 . "\n");
        }
        if (!empty($store->city)) {
            $this->printer->text($store->city . "\n");
        }
        $this->printer->text(lang('tel') . ': ' . $store->phone . "\n");
        $this->printer->feed();
        $this->printer->text($store->receipt_header . "\n");
        $this->printer->feed();

        $this->printer->setJustification(Printer::JUSTIFY_LEFT);
        $this->printer->text('C: ' . $sale->customer_name . "\n");
        $this->printer->text('R: ' . $sale->hold_ref . "\n");
        $this->printer->text('U: ' . $created_by->first_name . " " . $created_by->last_name . "\n");
        $this->printer->text('T: ' . $this->tec->hrld($sale->date) . "\n");
        $this->printer->feed();

        $r = 1;
        foreach ($items as $item) {
            $item->quantity = $item->quantity - ($item->ordered ? $item->ordered : 0);
            $this->printer->text($this->printLine('#' . $r . ' ' . $this->product_name(addslashes($item->product_name)) . ' (' . $item->product_code . ') : [ ' . ($item->quantity > 0 ? $this->tec->formatQuantity($item->quantity) : 'xxxx') . " ]") . "\n");
            if (!empty($item->comment)) {
                $comments = explode(PHP_EOL, $item->comment);
                foreach ($comments as $cmt) {
                    $this->printer->text(' * ' . $cmt . "\n");
                }
            }
            $this->printer->feed();
            $r++;
        }

        $this->printer->setJustification(Printer::JUSTIFY_CENTER);
        $this->printer->feed(2);
        $this->printer->text($sale->note . "\n");

        $this->printer->feed();
        $this->printer->cut();
        $this->printer->close();
    }

    function product_name($name, $charpername = null)
    {
        if ($charpername) {
            return wordwrap($name, ($charpername - 8), "\n", true);
            //            return character_limiter($name, ($charpername - 8));
        } else {
            return wordwrap($name, ($this->char_per_line - 8), "\n", true);
            //            return character_limiter($name, ($this->char_per_line - 8));
        }
    }

    function LineWrap($name, $charpername = null)
    {
        if ($charpername) {
            return wordwrap($name, ($charpername - 8), "\n", true);
            //            return character_limiter($name, ($charpername - 8));
        } else {
            return wordwrap($name, ($this->char_per_line - 8), "\n", true);
            //            return character_limiter($name, ($this->char_per_line - 8));
        }
    }

    function drawLine()
    {
        $new = '';
        for ($i = 1; $i < $this->char_per_line; $i++) {
            $new .= '-';
        }
        return $new . "\n";
    }

    function printLine($str, $sep = '', $space = null)
    {
        $size = $space ? $space : $this->char_per_line;
        $lenght = strlen($str);
        list($first, $second) = explode(":", $str, 2);
        $new = $first . $sep;
        for ($i = 1; $i < ($size - $lenght); $i++) {
            $new .= ' ';
        }
        $new .= $sep . $second;
        return $new;
    }

    function printText($text)
    {
        $new = wordwrap($text, $this->char_per_line, " \\n");
        return $new;
    }

    function taxLine($name, $code, $qty, $amt, $tax)
    {
        $new = $this->printLine(
            $this->printLine(
                $this->printLine(
                    $this->printLine($name . ':' . $code, '', 18)
                        . ':' . $qty,
                    '',
                    25
                ) . ':' . $amt,
                '',
                35
            ) . ':' . $tax
        );
        return $new;
    }
}
