<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class FEC_model extends CI_Model {

    public function __construct() {
        parent::__construct();
    }
    public function addFEC($data, $items, $payment = array(), $did = NULL, $payment2 = array(), $payment3 = array(), $payment4 = array(), $otrostextos) {
        if ($this->db->insert('fec', $data)) {
            $sale_id = $this->db->insert_id();
            foreach ($items as $item) {
                // unset($item["type"]);
                $item['sale_id'] = $sale_id;

                $editcantidad = false;
                $esta_fraccionado = $item['esta_fraccionado'];
                $quantity_edit = $item['quantity_edit'];
                $qty_fracc_edit = $item['qty_fracc_edit'];
                unset($item['quantity_edit']);
                unset($item['qty_fracc_edit']);
               # $this->db->save_queries = true;
                if ($this->db->insert('fec_items', $item)) {
                    // if ($item['product_id'] > 0 && $product = $this->site->getProductByID($item['product_id'])) {
                    //     if ($product->type == 'standard') {
                    //         if ($this->Settings->enable_fractions == "1") {
                    //             if ($esta_fraccionado == "1") {
                    //                 $datosInventario = $this->getFraccion(array(
                    //                     'fracionCaja' => $product->caja_fraccionada,
                    //                     'CajasDisponibles' => $product->quantity,
                    //                     'FaccionesDisponibles' => $product->qty_fracc,
                    //                     'CantidadVendidas' => $item['quantity']));
                    //                 $this->db->update('product_store_qty', $datosInventario, array('product_id' => $product->id, 'store_id' => $data['store_id']));
                    //                 $editcantidad = true;
                    //             }
                    //         }
                    //         if (!$editcantidad) {
                    //             $this->db->update('product_store_qty', array('quantity' => ($product->quantity - $item['quantity'])), array('product_id' => $product->id, 'store_id' => $data['store_id']));
                    //         }
                    //     } elseif ($product->type == 'combo') {
                    //         $combo_items = $this->getComboItemsByPID($product->id);
                    //         foreach ($combo_items as $combo_item) {
                    //             $cpr = $this->site->getProductByID($combo_item->id);
                    //             if ($cpr->type == 'standard') {

                    //                 if ($this->Settings->enable_fractions == "1") {
                    //                     if ($esta_fraccionado == "1") {

                    //                         $datosInventario = $this->getFraccion(array(
                    //                             'fracionCaja' => $product->caja_fraccionada,
                    //                             'CajasDisponibles' => $product->quantity,
                    //                             'FaccionesDisponibles' => $product->qty_fracc,
                    //                             'CantidadVendidas' => $item['quantity']));

                    //                         $this->db->update('product_store_qty', $datosInventario, array('product_id' => $cpr->id, 'store_id' => $data['store_id']));

                    //                         $editcantidad = true;
                    //                     }
                    //                 }

                    //                 if (!$editcantidad) {
                    //                     $qty = $combo_item->qty * $item['quantity'];
                    //                     $this->db->update('product_store_qty', array('quantity' => ($cpr->quantity - $qty)), array('product_id' => $cpr->id, 'store_id' => $data['store_id']));
                    //                 }
                    //             }
                    //         }
                    //     }
                    // }
                }
            }

            // if ($otrostextos) {
            // $this->db->delete('sales_otros_textos', array('sale_id' => $sale_id));
            //     foreach ($otrostextos as $texto) {
            //         $texto['sale_id'] = $sale_id;
            //         $this->db->insert('sales_otros_textos', $texto);
            //     }
            // }

            // if ($did) {
            //     $this->db->delete('suspended_sales', array('id' => $did));
            //     $this->db->delete('suspended_items', array('suspend_id' => $did));
            // }
            $msg = array();
            if (!empty($payment)) {
                if ($payment['amount'] > 0) {

                    if ($payment['paid_by'] == 'stripe') {
                        $card_info = array("number" => $payment['cc_no'], "exp_month" => $payment['cc_month'], "exp_year" => $payment['cc_year'], "cvc" => $payment['cc_cvv2'], 'type' => $payment['cc_type']);
                        $result = $this->stripe($payment['amount'], $card_info);
                        if (!isset($result['error']) && !empty($result['transaction_id'])) {
                            $payment['transaction_id'] = $result['transaction_id'];
                            $payment['date'] = $result['created_at'];
                            $payment['amount'] = $result['amount'];
                            $payment['currency'] = $result['currency'];
                            unset($payment['cc_cvv2']);
                            $payment['sale_id'] = $sale_id;
                            $this->db->insert('payments_fec', $payment);
                        } else {
                            $this->db->update('sales', ['paid' => 0, 'status' => 'due'], ['id' => $sale_id]);
                            $msg[] = lang('payment_failed');
                            $msg[] = '<p class="text-danger">' . $result['code'] . ': ' . $result['message'] . '</p>';
                        }
                    } else {
                        if ($payment['paid_by'] == 'gift_card') {
                            $gc = $this->getGiftCardByNO($payment['gc_no']);
                            $this->db->update('gift_cards', array('balance' => ($gc->balance - $payment['amount'])), array('card_no' => $payment['gc_no']));
                        }
                        unset($payment['cc_cvv2']);
                        $payment['sale_id'] = $sale_id;
                        $this->db->insert('payments_fec', $payment);
                    }
                }
            }

            if (!empty($payment2)) {
                if ($payment2['amount'] > 0) {
                    if ($payment2['paid_by'] == 'stripe') {
                        $card_info = array("number" => $payment2['cc_no'], "exp_month" => $payment2['cc_month'], "exp_year" => $payment2['cc_year'], "cvc" => $payment2['cc_cvv2'], 'type' => $payment2['cc_type']);
                        $result = $this->stripe($payment2['amount'], $card_info);
                        if (!isset($result['error']) && !empty($result['transaction_id'])) {
                            $payment2['transaction_id'] = $result['transaction_id'];
                            $payment2['date'] = $result['created_at'];
                            $payment2['amount'] = $result['amount'];
                            $payment2['currency'] = $result['currency'];
                            unset($payment2['cc_cvv2']);
                            $payment2['sale_id'] = $sale_id;
                            $this->db->insert('payments_fec', $payment2);
                        } else {
                            $this->db->update('sales', ['paid' => 0, 'status' => 'due'], ['id' => $sale_id]);
                            $msg[] = lang('payment_failed');
                            $msg[] = '<p class="text-danger">' . $result['code'] . ': ' . $result['message'] . '</p>';
                        }
                    } else {
                        if ($payment2['paid_by'] == 'gift_card') {
                            $gc = $this->getGiftCardByNO($payment2['gc_no']);
                            $this->db->update('gift_cards', array('balance' => ($gc->balance - $payment2['amount'])), array('card_no' => $payment2['gc_no']));
                        }
                        unset($payment2['cc_cvv2']);
                        $payment2['sale_id'] = $sale_id;
                        $this->db->insert('payments_fec', $payment2);
                    }
                }
            }

            if (!empty($payment3)) {
                if ($payment3['amount'] > 0) {
                    if ($payment3['paid_by'] == 'stripe') {
                        $card_info = array("number" => $payment3['cc_no'], "exp_month" => $payment3['cc_month'], "exp_year" => $payment3['cc_year'], "cvc" => $payment3['cc_cvv2'], 'type' => $payment3['cc_type']);
                        $result = $this->stripe($payment3['amount'], $card_info);
                        if (!isset($result['error']) && !empty($result['transaction_id'])) {
                            $payment3['transaction_id'] = $result['transaction_id'];
                            $payment3['date'] = $result['created_at'];
                            $payment3['amount'] = $result['amount'];
                            $payment3['currency'] = $result['currency'];
                            unset($payment3['cc_cvv2']);
                            $payment3['sale_id'] = $sale_id;
                            $this->db->insert('payments_fec', $payment3);
                        } else {
                            $this->db->update('sales', ['paid' => 0, 'status' => 'due'], ['id' => $sale_id]);
                            $msg[] = lang('payment_failed');
                            $msg[] = '<p class="text-danger">' . $result['code'] . ': ' . $result['message'] . '</p>';
                        }
                    } else {
                        if ($payment3['paid_by'] == 'gift_card') {
                            $gc = $this->getGiftCardByNO($payment3['gc_no']);
                            $this->db->update('gift_cards', array('balance' => ($gc->balance - $payment3['amount'])), array('card_no' => $payment3['gc_no']));
                        }
                        unset($payment3['cc_cvv2']);
                        $payment3['sale_id'] = $sale_id;
                        $this->db->insert('payments_fec', $payment3);
                    }
                }
            }

            if (!empty($payment4)) {
                if ($payment4['amount'] > 0) {
                    if ($payment4['paid_by'] == 'stripe') {
                        $card_info = array("number" => $payment4['cc_no'], "exp_month" => $payment4['cc_month'], "exp_year" => $payment4['cc_year'], "cvc" => $payment4['cc_cvv2'], 'type' => $payment4['cc_type']);
                        $result = $this->stripe($payment4['amount'], $card_info);
                        if (!isset($result['error']) && !empty($result['transaction_id'])) {
                            $payment4['transaction_id'] = $result['transaction_id'];
                            $payment4['date'] = $result['created_at'];
                            $payment4['amount'] = $result['amount'];
                            $payment4['currency'] = $result['currency'];
                            unset($payment4['cc_cvv2']);
                            $payment4['sale_id'] = $sale_id;
                            $this->db->insert('payments_fec', $payment4);
                        } else {
                            $this->db->update('sales', ['paid' => 0, 'status' => 'due'], ['id' => $sale_id]);
                            $msg[] = lang('payment_failed');
                            $msg[] = '<p class="text-danger">' . $result['code'] . ': ' . $result['message'] . '</p>';
                        }
                    } else {
                        if ($payment4['paid_by'] == 'gift_card') {
                            $gc = $this->getGiftCardByNO($payment4['gc_no']);
                            $this->db->update('gift_cards', array('balance' => ($gc->balance - $payment4['amount'])), array('card_no' => $payment4['gc_no']));
                        }
                        unset($payment4['cc_cvv2']);
                        $payment4['sale_id'] = $sale_id;
                        $this->db->insert('payments_fec', $payment4);
                    }
                }
            }
            return array('sale_id' => $sale_id, 'message' => $msg);
        }

        return false;
    }

    public function getAllFecItems($sale_id) {
        $this->db->select("fec_items.*", FALSE)
        ->order_by('fec_items.id');
        $q = $this->db->get_where('fec_items', array('sale_id' => $sale_id));
        if($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }

    public function getFecByID($id) {
        $q = $this->db->get_where('fec', array('id' => $id), 1);
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }

    public function getAllFecPayments($sale_id){
        $q = $this->db->get_where('payments_fec', array('sale_id' => $sale_id));
        if ($q->num_rows() > 0) {
         return $q->result();
        }
        return FALSE;
    }

    public function getSuppliersByID($id) {
        $q = $this->db->get_where('suppliers', array('id' => $id), 1);
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }

    public function getNombreProvincia($id){
        $q = $this->db->get_where('provincia_cr', array('codigo_provincia' => $id), 1);
        if ($q->num_rows() > 0) {
            return $q->row()->nombre_provincia;
        }
        return FALSE;
    }

    public function getNombreCanton($id){
        $q = $this->db->get_where('canton_cr', array('codigo_canton' => $id), 1);
        if ($q->num_rows() > 0) {
            return $q->row()->nombre_canton;
        }
        return FALSE;
    }

    public function getNombreDistrito($id){
        $q = $this->db->get_where('distrito_cr', array('codigo_distrito' => $id), 1);
        if ($q->num_rows() > 0) {
            return $q->row()->nombre_distrito;
        }
        return FALSE;
    }
    
    public function getNombreBarrio($id){
        $q = $this->db->get_where('barrio_cr', array('codigo_barrio' => $id), 1);
        if ($q->num_rows() > 0) {
            return $q->row()->nombre_barrio;
        }
        return FALSE;
    }
}