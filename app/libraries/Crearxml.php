<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Crearxml
{

    public function __construct()
    { }

    public function __get($var)
    {
        return get_instance()->$var;
    }

    public function getInvoice($invoice, $itemsInvoices, $payment, $otrostextos)
    {
        ini_set("memory_limit", "8162M");
        ini_set( 'max_input_vars' , 16000 );
        $this->load->model('customers_model');
        $this->load->model('hacienda_model');
        $sale_id = $invoice['id'];
        $sale_items = $this->db->get_where('sale_items', array('sale_id' => $sale_id))->result();
        $totalItems = count($sale_items);
        $customer_id = $invoice['customer_id'];
        $receptor = $this->customers_model->getCustomerByID($invoice['customer_id']);
        $CodActividad = $invoice['id_actividad'];

        $moneda = $this->Settings->currency_prefix;
        $CondicionVenta = "";
        $PlazoCredito = "";

        if ($invoice['status'] == 'paid' || $invoice['id_shipping_method'] != NULL) {
            $CondicionVenta = '01';
        } else {
            $CondicionVenta = '02';
            $PlazoCredito = '30 dias';
        }
        if ($payment) {
            if ($payment['paid_by'] == 'cash') {
                $MedioPago = '01';
            } elseif ($payment['paid_by'] == 'CC') {
                $MedioPago = '02';
            } elseif ($payment['paid_by'] == 'Cheque') {
                $MedioPago = '03';
            } elseif ($payment['paid_by'] == 'TransDep') {
                $MedioPago = '04';
            } elseif ($payment['paid_by'] == 'SINPE') {
                $MedioPago = '08';
            } elseif ($payment['paid_by'] == 'digital') {
                $MedioPago = '09';
            } else {
                $MedioPago = '99';
            }
        } else {
            $MedioPago = '99';
        }
        $tipo_receptor = '05';
        $identificacion = '';

        if ($customer_id != 1) {
            $receptor->pre_id_number = $receptor->cf1;
            $receptor->id_number_proveedor = $receptor->cf2;
            $identificacion = str_replace('-', '', trim($receptor->id_number_proveedor));
            $identifivalid = str_replace('-', '', trim($receptor->id_number_proveedor));
            $tipo_receptor = $receptor->pre_id_number;
            if (strlen($identificacion) < 12) {
                $dif = 12 - strlen($identificacion);
                $ceros = '';
                for ($ce = 1; $ce <= $dif; $ce++) {
                    $ceros .= '0';
                }
                $identificacion = $ceros . $identificacion;
            }
            $identificacion = substr($identificacion, 0, 12);
        }

        if (isset($receptor->pre_id_number)) {

            switch ($receptor->pre_id_number) {
                case "01":
                    $tipo_receptor = "01";
                    if (strlen(trim($identifivalid)) < 9 || strlen(trim($identifivalid)) > 9) {
                        $tipo_receptor = "05";
                    }
                    break;
                case "02":
                    $tipo_receptor = "02";
                    if (strlen($identifivalid) < 10 || strlen($identifivalid) > 10) {
                        $tipo_receptor = "05";
                    }
                    break;
                case "03":
                    $tipo_receptor = "03";
                    if (strlen($identifivalid) < 11 || strlen($identifivalid) > 11) {
                        if (strlen($identifivalid) < 12 || strlen($identifivalid) > 12) {
                            $tipo_receptor = "05";
                        }
                    }
                    break;
                case "04":
                    $tipo_receptor = "04";
                    if (strlen($identifivalid) < 10 || strlen($identifivalid) > 10) {
                        $tipo_receptor = "05";
                    }
                    break;
                default:
                    $tipo_receptor = "05";
                    break;
            }
        }

        date_default_timezone_set('America/Costa_Rica');
        date_default_timezone_get();
        $fecha = date('Y-m-d\TH:i:s');
        $cabeceraticket = '<?xml version="1.0" encoding="UTF-8"?><TiqueteElectronico xmlns="https://cdn.comprobanteselectronicos.go.cr/xml-schemas/v4.4/tiqueteElectronico" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="https://cdn.comprobanteselectronicos.go.cr/xml-schemas/v4.4/tiqueteElectronico https://cdn.comprobanteselectronicos.go.cr/xml-schemas/v4.4/TiqueteElectronico_V4.4.xsd">';

        $cabecerafactura = '<?xml version="1.0" encoding="UTF-8"?><FacturaElectronica xmlns="https://cdn.comprobanteselectronicos.go.cr/xml-schemas/v4.4/facturaElectronica" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="https://cdn.comprobanteselectronicos.go.cr/xml-schemas/v4.4/facturaElectronica https://cdn.comprobanteselectronicos.go.cr/xml-schemas/v4.4/FacturaElectronica_V4.4.xsd">';
        $sireceptor = false;
        if ($customer_id == "1" || $tipo_receptor == '05' || strtolower(trim($receptor->name)) == "cliente de paso" || strtolower(trim($receptor->name)) == "cliente de contado") {
            $invo = $cabeceraticket;
            $sireceptor = false;
            $tipodoc = '04';
        } else {
            $invo = $cabecerafactura;
            $sireceptor = true;
            $tipodoc = '01';
        }


        $consecutivo = $this->hacienda_model->ccsctv($tipodoc);

        if ($consecutivo) {
            $NumConse = substr($consecutivo, 10, 20);
        } else {
            $NumConse = 0;
        }

        $consecutive = $this->generate_consecutive($NumConse + 1, $tipodoc);

        $param = [
            $consecutive,
            $invoice['date']
        ];
        $key = $this->generate_key($param);


        $invo .= '
                  <Clave>' . $key . '</Clave>
                  <CodigoActividad>' . $CodActividad . '</CodigoActividad>
                  <NumeroConsecutivo>' . $consecutive . '</NumeroConsecutivo>
                  <FechaEmision>' . $fecha . '</FechaEmision>
                  <Emisor>
                    <Nombre>' . $this->quitatilde(trim($this->Settings->nombre_emisor)) . '</Nombre>
                    <Identificacion>
                      <Tipo>' . $this->Settings->tipo_doc_emisor . '</Tipo>
                      <Numero>' . trim(str_replace("-", "", $this->Settings->cedula_emisor)) . '</Numero>
                    </Identificacion>
                    <NombreComercial>' . $this->quitatilde(trim($this->Settings->nombre_comercial)) . '</NombreComercial>
                    <Ubicacion>
                      <Provincia>' . substr($this->Settings->cod_provincia, -2) . '</Provincia>
                      <Canton>' . substr(trim($this->Settings->cod_canton), -2) . '</Canton>
                      <Distrito>' . substr($this->Settings->cod_distrito, -2) . '</Distrito>
                      <Barrio>' . substr($this->Settings->cod_barrio, -2) . '</Barrio>
                      <OtrasSenas>' . trim($this->Settings->otras_senas) . '</OtrasSenas>
                    </Ubicacion>
                    <Telefono>
                      <CodigoPais>' . trim($this->Settings->cod_telefono_emisor) . '</CodigoPais>
                      <NumTelefono>' . trim(str_replace("-", "", $this->Settings->telefono_emisor)) . '</NumTelefono>
                    </Telefono>
                    <CorreoElectronico>' . trim($this->Settings->email_emisor) . '</CorreoElectronico>
                  </Emisor>';

        if ($sireceptor) {
            $invo .= '<Receptor>
            <Nombre>' . trim($this->quitatilde($receptor->name)) . '</Nombre>
            <Identificacion>
                    <Tipo>' . $tipo_receptor . '</Tipo>
                    <Numero>' . $receptor->id_number_proveedor . '</Numero>
            </Identificacion>';

            if ($receptor->business_name) {
                $invo .= '<NombreComercial>' . $this->quitatilde($receptor->business_name) . '</NombreComercial>';
            }

            if (!empty($receptor->codigo_actividad)) {
                $invo .= '<CodigoActividad>' . $receptor->codigo_actividad . '</CodigoActividad>';
            }

            if (strlen(trim(str_replace("-", "", $receptor->phone))) == 8) {
                $invo .= '
                <Telefono>
                    <CodigoPais>506</CodigoPais>
                    <NumTelefono>' . trim(str_replace("-", "", $receptor->phone)) . '</NumTelefono>
                </Telefono>';
            }

            if ($receptor->email) {
                $invo .= '<CorreoElectronico>' . $receptor->email . '</CorreoElectronico>';
            }

            $invo .= '</Receptor>';
        }


        $invo .= '<CondicionVenta>' . $CondicionVenta . '</CondicionVenta>';

        if ($PlazoCredito) {
            $invo .= '<PlazoCredito>' . $PlazoCredito . '</PlazoCredito>';
        }

        $invo .= '<MedioPago>' . $MedioPago . '</MedioPago>';

        $NumeroLinea = 0;
        $TotalServGravados = 0.00000;
        $TotalServExentos = 0.00000;
        $TotalServExonerado = 0.00000;
        $TotalMercanciasGravadas = 0.00000;
        $TotalMercanciasExentas = 0.00000;
        $TotalMercanciaExonerada = 0.00000;
        $TotalGravado = 0.00000;
        $TotalExento = 0.00000;
        $TotalExonerado = 0.00000;
        $TotalVenta = 0.00000;
        $TotalDescuentos = 0.00000;
        $TotalVentaNeta = 0.00000;
        $TotalImpuesto = 0.00000;
        $TotalComprobante = 0.00000;
        $disAux = 0.00000;
        $is_service = false;
        $MontoCargo = 0.00000;
        // dd($invoice);

        $itemsInvoices = $this->reClacDiscount($itemsInvoices, $invoice);
        $count = 0;
        
        $itemlength = count($itemsInvoices);
        
        $invo .= '<DetalleServicio>';
        if ($itemsInvoices == null || count($itemsInvoices) == 0) {
            return null;
        }
        if ($totalItems != count($itemsInvoices)) {
            return null;
        }
        $i = 0;
        foreach ($itemsInvoices as $items) {
            if ($items["product_id"] != "9r091n4" && $items["product_code"] != "9r091n4" ) {
                if (strpos($items["discount"], '%') === false) {
                    $items["item_discount"] = $items["discount"];
                };

                $NumeroLinea = $NumeroLinea + 1;
                $CodigoTipo = "03";
                $CodigoCodigo = $items["product_code"];
                $Cantidad = $items['quantity'];

                if ($items['item_tax']) {
                    $ImpuestoTarifa = (int) str_replace('%', '', $items["tax"]);
                    $calc_imp = $ImpuestoTarifa / 100;
                } else {
                    $calc_imp = 1;
                }

                $Detalle = $items["product_name"];

                $row = $this->site->getProductByID($items['product_id']);
                $this->db->select("{$this->db->dbprefix('impuestos')}.*", FALSE);
                $qq = $this->db->get_where('impuestos', array('id_impuesto' => isset($row->id_tax) ? $row->id_tax : 8), 1);
                if ($qq->num_rows() > 0) {
                    $im = $qq->row();
                    $items['id_impuesto'] = $im->id_impuesto;
                    $items['codigo_impuesto'] = $im->codigo_impuesto;
                    $items['codigo_tarifa'] = $im->codigo_tarifa;
                } else {
                    $items['id_impuesto'] = 0;
                    $items['codigo_impuesto'] = 0;
                    $items['codigo_tarifa'] = 0;
                }


                if ($row) {
                    if ($row->tax_method == "1") {
                        $PrecioUnitario = $items["real_unit_price"];
                        $InvertirImpuestoTarifa = 1;
                    } else if ($row->tax_method == "0") {
                        if (!$items['item_tax']) {
                            $PrecioUnitario = $items["real_unit_price"];
                        } else {
                            $InvertirImpuestoTarifa = 1 + (floatval(str_replace('%', '', $items["tax"])) / 100);
                            $PrecioUnitario = number_format($items["real_unit_price"], 5, '.', '') / number_format($InvertirImpuestoTarifa, 5, '.', '');
                        }
                    }
                } else {
                    $PrecioUnitario = $items["real_unit_price"];
                    $InvertirImpuestoTarifa = 1;
                }

                $MontoTotal = number_format($items["quantity"], 5, '.', '') * number_format($PrecioUnitario, 5, '.', '');

                $NaturalezaDescuento = null;
                if ($items["item_discount"] > 0) {
                    $NaturalezaDescuento = $items["product_name"];
                }

                $MontoDescuento = 0;

                if ($items["discount"] > 0) {
                    $disAux = str_replace('%', '', $items["discount"]);
                    $MontoDescuento = number_format($MontoTotal, 5, '.', '') * (number_format($disAux, 5, '.', '') / 100);
                }

                $SubTotal = number_format($MontoTotal, 5, '.', '') - number_format($MontoDescuento, 5, '.', '');

                $ImpuestoCodigo = $items["codigo_impuesto"];
                $TarifaCodigo = $items["codigo_tarifa"];
                $ImpuestoTarifa = str_replace('%', '', $items["tax"]);
                //$ImpuestoMonto =  $items["item_tax"];
                $ImpuestoMonto = number_format($SubTotal, 5, '.', '') * (number_format($ImpuestoTarifa, 5, '.', '') / 100);

                $invo .= '<LineaDetalle>
                            <NumeroLinea>' . $NumeroLinea . '</NumeroLinea>
                            <CodigoComercial>
                            <Tipo>' . $CodigoTipo . '</Tipo>
                            <Codigo>' . substr($CodigoCodigo, 0, 19) . '</Codigo>
                            </CodigoComercial>'
                            . (!empty($row->cabys) ? '<CodigoComercial><Tipo>05</Tipo><Codigo>' . htmlspecialchars($row->cabys) . '</Codigo></CodigoComercial>' : '')
                            . '<Cantidad>' . $Cantidad . '</Cantidad>
                            <UnidadMedida>' . $items["unit_of_measurement"] . '</UnidadMedida>
                            <Detalle>' . substr($this->quitatilde($Detalle), 0, 80) . '</Detalle>
                            <PrecioUnitario>' . $PrecioUnitario . '</PrecioUnitario>
                            <MontoTotal>' . number_format($MontoTotal, 5, '.', '') . '</MontoTotal>';

                if ($MontoDescuento > 0) {
                    $invo .= '<Descuento><MontoDescuento>' . number_format($MontoDescuento, 5, '.', '') . '</MontoDescuento>
                      <NaturalezaDescuento>' . substr($this->quitatilde($NaturalezaDescuento), 0, 80) . '</NaturalezaDescuento></Descuento>';
                }

                $invo .= '<SubTotal>' . number_format($SubTotal, 5, '.', '') . '</SubTotal>';
                $MontoExoneracion = 0.00000;
                $PorcentajeExoneracion = 0;
                $ImpuestoNeto = 0.00000;

                if ($TarifaCodigo != '01') {
                    $invo .= '<Impuesto>
                            <Codigo>' . $ImpuestoCodigo . '</Codigo>
                            <CodigoTarifa>' . $TarifaCodigo . '</CodigoTarifa>
                            <Tarifa>' . $ImpuestoTarifa . '</Tarifa>
                            <Monto>' . number_format($ImpuestoMonto, 5, '.', '') . '</Monto>';

                    if ($invoice['TipoDocumentoE'] && $customer_id != 1 && $tipo_receptor != '05') {
                        $PorcentajeExoneracion = $invoice['PorcentajeExoneracion'];
                        $MontoExoneracion = number_format($ImpuestoMonto, 5, '.', '') * (number_format($PorcentajeExoneracion, 5, '.', '') / 100);
                        $invo .= '
                    <Exoneracion>
                      <TipoDocumento>' . $invoice['TipoDocumentoE'] . '</TipoDocumento>
                      <NumeroDocumento>' . $invoice['NumeroDocumentoE'] . '</NumeroDocumento>
                      <NombreInstitucion>' . $this->quitatilde($invoice['NombreInstitucionE']) . '</NombreInstitucion>
                      <FechaEmision>' . $invoice['FechaEmisionE'] . '</FechaEmision>
                      <PorcentajeExoneracion>' . $invoice['PorcentajeExoneracion'] . '</PorcentajeExoneracion>
                      <MontoExoneracion>' . number_format($MontoExoneracion, 5, '.', '') . '</MontoExoneracion>
                    </Exoneracion>';
                    }

                    $invo .= '</Impuesto>';
                    $ImpuestoNeto = number_format($ImpuestoMonto, 5, '.', '') - number_format($MontoExoneracion, 5, '.', '');
                    $invo .= '<ImpuestoNeto>' . number_format($ImpuestoNeto, 5, '.', '') . '</ImpuestoNeto>';
                }

                $MontoTotalLinea = number_format($SubTotal, 5, '.', '') + number_format($ImpuestoNeto, 5, '.', '');

                $invo .= '<MontoTotalLinea>' . number_format($MontoTotalLinea, 5, '.', '') . '</MontoTotalLinea>
                    </LineaDetalle>';
                if (!isset($items['type']) || !$items['type']) {
                    $items['type'] = 'standard';
                }

                if ($items['type'] == 'service') {
                    if ($TarifaCodigo == '01') {
                        $TotalServExentos = number_format($TotalServExentos, 5, '.', '') + number_format($MontoTotal, 5, '.', '');
                    } else {
                        $TotalServGravados = number_format($TotalServGravados, 5, '.', '') + (number_format($MontoTotal, 5, '.', '') - (number_format($MontoTotal, 5, '.', '') * (number_format($PorcentajeExoneracion, 5, '.', '') / 100)));

                        $TotalServExonerado = number_format($TotalServExonerado, 5, '.', '') + (number_format($MontoTotal, 5, '.', '') * (number_format($PorcentajeExoneracion, 5, '.', '') / 100));
                    }
                } else {
                    if ($TarifaCodigo == '01') {
                        $TotalMercanciasExentas = number_format($TotalMercanciasExentas, 5, '.', '') + number_format($MontoTotal, 5, '.', '');
                    } else {

                        $TotalMercanciasGravadas = number_format($TotalMercanciasGravadas, 5, '.', '') + (number_format($MontoTotal, 5, '.', '') - (number_format($MontoTotal, 5, '.', '') * (number_format($PorcentajeExoneracion, 5, '.', '') / 100)));

                        $TotalMercanciaExonerada = number_format($TotalMercanciaExonerada, 5, '.', '') + (number_format($MontoTotal, 5, '.', '') * (number_format($PorcentajeExoneracion, 5, '.', '') / 100));
                    }
                }

                $TotalDescuentos = number_format($TotalDescuentos, 5, '.', '')  + number_format($MontoDescuento, 5, '.', '');
                $TotalImpuesto = number_format($TotalImpuesto, 5, '.', '') + number_format($ImpuestoNeto, 5, '.', '');
                $i = $i + 1;
            } else {
                // --------------------------Otros Cargos------------------------------------------
                $TipoDocumentoOtros = '06';
                $NumeroIdentidadTercero = trim(str_replace("-", "", $this->Settings->cedula_emisor));
                $NombreTercero = trim($receptor->name);
                $DetalleOtros = $items["product_name"];
                $Porcentaje = 10;
                $MontoCargo = $items["net_unit_price"];
                $is_service = true;
                //---------------------------------------------------------------------------------
                $i = $i + 1;
            }
        }
        if ($totalItems != $i) {
            return null;
        }
        $invo .= '</DetalleServicio>';
        // --------------------------Otros Cargos------------------------------------------
        if ($is_service) {
            $invo .= '<OtrosCargos>';
            $invo .= '<TipoDocumento>' . $TipoDocumentoOtros . '</TipoDocumento>';
            $invo .= '<NumeroIdentidadTercero>' . $NumeroIdentidadTercero . '</NumeroIdentidadTercero>';
            $invo .= '<NombreTercero>' . $this->quitatilde($NombreTercero) . '</NombreTercero>';
            $invo .= '<Detalle>' . $this->quitatilde($DetalleOtros) . '</Detalle>';
            $invo .= '<Porcentaje>' . number_format($Porcentaje, 5, '.', '') . '</Porcentaje>';
            $invo .= '<MontoCargo>' . number_format($MontoCargo, 5, '.', '') . '</MontoCargo>';
            $invo .= '</OtrosCargos>';
        }
        //---------------------------------------------------------------------------------
        $TotalExento = number_format($TotalServExentos, 5, '.', '') + number_format($TotalMercanciasExentas, 5, '.', '');
        $TotalGravado = number_format($TotalMercanciasGravadas, 5, '.', '') + number_format($TotalServGravados, 5, '.', '');
        $TotalExonerado = number_format($TotalServExonerado, 5, '.', '') + number_format($TotalMercanciaExonerada, 5, '.', '');
        $TotalVenta = number_format($TotalGravado, 5, '.', '') + number_format($TotalExento, 5, '.', '') + number_format($TotalExonerado, 5, '.', '');
        $TotalVentaNeta = number_format($TotalVenta, 5, '.', '') - number_format($TotalDescuentos, 5, '.', '');
        $TotalComprobante = number_format($TotalVentaNeta, 5, '.', '') + number_format($TotalImpuesto, 5, '.', '') + number_format($MontoCargo, 5, '.', '');
        $invo .= '
                  <ResumenFactura>
                  <CodigoTipoMoneda>
                    <CodigoMoneda>' . $this->Settings->currency_prefix . '</CodigoMoneda>
                    <TipoCambio>' . $this->Settings->value_changue . '</TipoCambio>
                  </CodigoTipoMoneda>';

        $invo .= '<TotalServGravados>' . number_format($TotalServGravados, 5, '.', '') . '</TotalServGravados>
        <TotalServExentos>' . number_format($TotalServExentos, 5, '.', '') . '</TotalServExentos>'
            . '';

        if ($TotalServExonerado > 0) {
            $invo .= '<TotalServExonerado>' . number_format($TotalServExonerado, 5, '.', '') . '</TotalServExonerado>';
        }

        $invo .= '<TotalMercanciasGravadas>' . number_format($TotalMercanciasGravadas, 5, '.', '') . '</TotalMercanciasGravadas>
                     <TotalMercanciasExentas>' . number_format($TotalMercanciasExentas, 5, '.', '') . '</TotalMercanciasExentas>';

        if ($TotalMercanciaExonerada > 0) {
            $invo .= '<TotalMercExonerada>' . number_format($TotalMercanciaExonerada, 5, '.', '') . '</TotalMercExonerada>';
        }

        $invo .= '<TotalGravado>' . number_format($TotalGravado, 5, '.', '') . '</TotalGravado>
                    <TotalExento>' . number_format($TotalExento, 5, '.', '') . '</TotalExento>';
        if ($TotalMercanciaExonerada > 0 || $TotalServExonerado > 0) {
            $invo .= '<TotalExonerado>' . number_format($TotalExonerado, 5, '.', '') . '</TotalExonerado>';
        }
        $invo .= '<TotalVenta>' . number_format($TotalVenta, 5, '.', '') . '</TotalVenta>
                    <TotalDescuentos>' . number_format($TotalDescuentos, 5, '.', '') . '</TotalDescuentos>
                    <TotalVentaNeta>' . number_format($TotalVentaNeta, 5, '.', '') . '</TotalVentaNeta>
                    <TotalImpuesto>' . number_format($TotalImpuesto, 5, '.', '') . '</TotalImpuesto>';
        if ($is_service) {
            $invo .=    '<TotalOtrosCargos>' . number_format($MontoCargo, 5, '.', '') . '</TotalOtrosCargos>';
        }
        $invo .=    '<TotalComprobante>' . number_format($TotalComprobante, 5, '.', '') . '</TotalComprobante>
                  </ResumenFactura>';
        if ($otrostextos) {
            $invo .= '<Otros>';
            foreach ($otrostextos as $texto) {
                $texto = (array) $texto;
                $invo .= '<OtroTexto codigo="' . $texto['titulo_texto'] . '" >' . $this->quitatilde($texto['otrotexto']) . '</OtroTexto>';
            }
            $invo .= '</Otros>';
        }

        $pieticket = '</TiqueteElectronico>';
        $piefactura = '</FacturaElectronica>';

        if ($customer_id == 1 || $tipo_receptor == '05')
            $invo .= $pieticket;
        else
            $invo .= $piefactura;

        return ['xml' => $invo, 'clave' => $key, 'consecutivo' => $consecutive, 'fecha_emision' => $fecha, 'tipo_doc' => $tipodoc];
    }

    public function getFEC($invoice, $itemsInvoices, $payment, $otrostextos)
    {
        ini_set("memory_limit", "-1");
        $this->load->model('Suppliers_model');
        $this->load->model('hacienda_model');
        $customer_id = $invoice['customer_id'];
        $receptor = $this->Suppliers_model->getSupplierByID($invoice['customer_id']);
        $CodActividad = $invoice['id_actividad'];

        $moneda = $this->Settings->currency_prefix;
        $CondicionVenta = "";
        $PlazoCredito = "";

        if ($invoice['status'] == 'paid') {
            $CondicionVenta = '01';
        } else {
            $CondicionVenta = '02';
            $PlazoCredito = $invoice['paymentmethod'] . ' dias';
        }
        if ($payment) {
            if ($payment['paid_by'] == 'cash') {
                $MedioPago = '01';
            } elseif ($payment['paid_by'] == 'CC') {
                $MedioPago = '02';
            } elseif ($payment['paid_by'] == 'Cheque') {
                $MedioPago = '03';
            } elseif ($payment['paid_by'] == 'TransDep') {
                $MedioPago = '04';
            } elseif ($payment['paid_by'] == 'SINPE') {
                $MedioPago = '08';
            } elseif ($payment['paid_by'] == 'digital') {
                $MedioPago = '09';
            } else {
                $MedioPago = '99';
            }
        } else {
            $MedioPago = '99';
        }
        $tipo_receptor = '05';
        $identificacion = '';

        if ($customer_id != 1) {
            $receptor->pre_id_number = $receptor->cf1;
            $receptor->id_number_proveedor = $receptor->cf2;
            $identificacion = str_replace('-', '', trim($receptor->id_number_proveedor));
            $identifivalid = str_replace('-', '', trim($receptor->id_number_proveedor));
            $tipo_receptor = $receptor->pre_id_number;
            if (strlen($identificacion) < 12) {
                $dif = 12 - strlen($identificacion);
                $ceros = '';
                for ($ce = 1; $ce <= $dif; $ce++) {
                    $ceros .= '0';
                }
                $identificacion = $ceros . $identificacion;
            }
            $identificacion = substr($identificacion, 0, 12);
        }

        if (isset($receptor->pre_id_number)) {

            switch ($receptor->pre_id_number) {
                case "01":
                    $tipo_receptor = "01";
                    if (strlen(trim($identifivalid)) < 9 || strlen(trim($identifivalid)) > 9) {
                        $tipo_receptor = "05";
                    }
                    break;
                case "02":
                    $tipo_receptor = "02";
                    if (strlen($identifivalid) < 10 || strlen($identifivalid) > 10) {
                        $tipo_receptor = "05";
                    }
                    break;
                case "03":
                    $tipo_receptor = "03";
                    if (strlen($identifivalid) < 11 || strlen($identifivalid) > 11) {
                        if (strlen($identifivalid) < 12 || strlen($identifivalid) > 12) {
                            $tipo_receptor = "05";
                        }
                    }
                    break;
                case "04":
                    $tipo_receptor = "04";
                    if (strlen($identifivalid) < 10 || strlen($identifivalid) > 10) {
                        $tipo_receptor = "05";
                    }
                    break;
                default:
                    $tipo_receptor = "05";
                    break;
            }
        }

        date_default_timezone_set('America/Costa_Rica');
        date_default_timezone_get();
        $fecha = date('Y-m-d\TH:i:s');

        $cabecerafactura = '<?xml version="1.0" encoding="UTF-8"?><FacturaElectronicaCompra xmlns="https://cdn.comprobanteselectronicos.go.cr/xml-schemas/v4.4/facturaElectronicaCompra" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="https://cdn.comprobanteselectronicos.go.cr/xml-schemas/v4.4/facturaElectronicaCompra https://cdn.comprobanteselectronicos.go.cr/xml-schemas/v4.4/FacturaElectronicaCompra_V4.4.xsd">';
        $sireceptor = false;
        if ($customer_id == "1" || $tipo_receptor == '05' || strtolower(trim($receptor->name)) == "cliente de paso" || strtolower(trim($receptor->name)) == "cliente de contado") {
            $invo = $cabecerafactura;
            $sireceptor = false;
            $tipodoc = '08';
        } else {
            $invo = $cabecerafactura;
            $sireceptor = true;
            $tipodoc = '08';
        }


        $consecutivo = $this->hacienda_model->ccsctvfec($tipodoc);

        if ($consecutivo) {
            $NumConse = substr($consecutivo, 10, 20);
        } else {
            $NumConse = 0;
        }

        $consecutive = $this->generate_consecutive_fec($NumConse + 1, '08');

        $param = [
            $consecutive,
            $invoice['date']
        ];
        $key = $this->generate_key($param);


        $invo .= '
                <Clave>' . $key . '</Clave>
                <CodigoActividad>' . $CodActividad . '</CodigoActividad>
                <NumeroConsecutivo>' . $consecutive . '</NumeroConsecutivo>
                <FechaEmision>' . $fecha . '</FechaEmision>
                <Emisor>
                    <Nombre>' . trim($this->quitatilde($receptor->name)) . '</Nombre>
                    <Identificacion>
                    <Tipo>' . $receptor->cf1 . '</Tipo>
                    <Numero>' . $receptor->cf2 . '</Numero>
                    </Identificacion>
                    <NombreComercial>' . $this->quitatilde($receptor->name) . '</NombreComercial>
                    <Ubicacion>
                    <Provincia>' . substr($receptor->codigo_provincia, -1) . '</Provincia>
                    <Canton>' . substr($receptor->codigo_canton, -2) . '</Canton>
                    <Distrito>' . substr($receptor->codigo_distrito, -2) . '</Distrito>
                    <Barrio>' . substr($receptor->codigo_barrio, -2) . '</Barrio>
                    <OtrasSenas>' . trim($receptor->direccion) . '</OtrasSenas>
                    </Ubicacion>
                    <Telefono>
                    <CodigoPais>506</CodigoPais>
                    <NumTelefono>' . trim(str_replace("-", "", $receptor->phone)) . '</NumTelefono>
                    </Telefono>
                    <CorreoElectronico>' . $receptor->email . '</CorreoElectronico>
                </Emisor>';

        if ($sireceptor) {
            $invo .= '<Receptor>
            <Nombre>' . $this->quitatilde(trim($this->Settings->nombre_emisor)) . '</Nombre>
            <Identificacion>
                    <Tipo>' . $this->Settings->tipo_doc_emisor . '</Tipo>
                    <Numero>' . trim(str_replace("-", "", $this->Settings->cedula_emisor))  . '</Numero>
            </Identificacion>';
            $invo .= '<NombreComercial>' . $this->quitatilde(trim($this->Settings->nombre_comercial)) . '</NombreComercial>';

            $invo .= '
                <Telefono>
                    <CodigoPais>' . trim($this->Settings->cod_telefono_emisor) . '</CodigoPais>
                    <NumTelefono>' . trim(str_replace("-", "", $this->Settings->telefono_emisor))  . '</NumTelefono>
                </Telefono>';


            if ($receptor->email) {
                $invo .= '<CorreoElectronico>' . trim($this->Settings->email_emisor)  . '</CorreoElectronico>';
            }

            $invo .= '</Receptor>';
        }


        $invo .= '<CondicionVenta>' . $CondicionVenta . '</CondicionVenta>';

        if ($PlazoCredito) {
            $invo .= '<PlazoCredito>' . $PlazoCredito . '</PlazoCredito>';
        }

        $invo .= '<MedioPago>' . $MedioPago . '</MedioPago>
        <DetalleServicio>';

        $NumeroLinea = 0;
        $TotalServGravados = 0.00000;
        $TotalServExentos = 0.00000;
        $TotalServExonerado = 0.00000;
        $TotalMercanciasGravadas = 0.00000;
        $TotalMercanciasExentas = 0.00000;
        $TotalMercanciaExonerada = 0.00000;
        $TotalGravado = 0.00000;
        $TotalExento = 0.00000;
        $TotalExonerado = 0.00000;
        $TotalVenta = 0.00000;
        $TotalDescuentos = 0.00000;
        $TotalVentaNeta = 0.00000;
        $TotalImpuesto = 0.00000;
        $TotalComprobante = 0.00000;


        $itemsInvoices = $this->reClacDiscount($itemsInvoices, $invoice);
        $count = 0;
        foreach ($itemsInvoices as $items) {
            if (strpos($items["discount"], '%') === false) {
                $items["item_discount"] = $items["discount"];
            };

            $NumeroLinea = $NumeroLinea + 1;
            if ($items['type'] == 'service') {
                $CodigoTipo = "02";
            } elseif ($items['type'] == 'standard') {
                $CodigoTipo = "01";
            } else {
                $CodigoTipo = "01";
            }


            $CodigoCodigo = $items["product_code"];
            $Cantidad = $items['quantity'];

            if ($items['item_tax']) {
                $ImpuestoTarifa = (int) str_replace('%', '', $items["tax"]);
                $calc_imp = $ImpuestoTarifa / 100;
            } else {
                $calc_imp = 1;
            }

            $Detalle = $items["product_name"];

            $row = $this->site->getProductByID($items['product_id']);
            $this->db->select("{$this->db->dbprefix('impuestos')}.*", FALSE);
            $qq = $this->db->get_where('impuestos', array('id_impuesto' => isset($items['id_tax']) ? $items['id_tax'] : 8), 1);
            if ($qq->num_rows() > 0) {
                $im = $qq->row();
                $items['id_impuesto'] = $im->id_impuesto;
                $items['codigo_impuesto'] = $im->codigo_impuesto;
                $items['codigo_tarifa'] = $im->codigo_tarifa;
            } else {
                $items['id_impuesto'] = 0;
                $items['codigo_impuesto'] = 0;
                $items['codigo_tarifa'] = 0;
            }

            // if ($row) {
            //     if ($row->tax_method == "1") {
            //         $PrecioUnitario = $items["real_unit_price"];
            //         $InvertirImpuestoTarifa = 1;
            //     } else if ($row->tax_method == "0") {
            //         if (!$items['item_tax']) {
            //             $PrecioUnitario = $items["real_unit_price"];
            //         } else {
            //             $InvertirImpuestoTarifa = 1 + (floatval(str_replace('%', '', $items["tax"])) / 100);
            //             $PrecioUnitario = number_format($items["real_unit_price"], 5, '.', '') / number_format($InvertirImpuestoTarifa, 5, '.', '');
            //         }
            //     }
            // } else {
                $PrecioUnitario = $items["real_unit_price"];
                $InvertirImpuestoTarifa = 1;
            // }

            $MontoTotal = $items["quantity"] * $PrecioUnitario;

            $NaturalezaDescuento = null;
            if ($items["item_discount"] > 0) {
                $NaturalezaDescuento = $items["product_name"];
            }

            $MontoDescuento = 0;

            if ($items["discount"] > 0) {
                $MontoDescuento = $items['discount'];
            }

            $SubTotal = $MontoTotal - $MontoDescuento;

            $ImpuestoCodigo = $items["codigo_impuesto"];
            $TarifaCodigo = $items["codigo_tarifa"];
            $ImpuestoTarifa = str_replace('%', '', $items["tax"]);
            $ImpuestoMonto = $items['item_tax'];

            

            if (!$items['item_tax']) {
            $ImpuestoCodigo = "98";
            $ImpuestoTarifa = "0";
            $ImpuestoMonto = 0;
            } else {
            $ImpuestoCodigo = "01";
            $ImpuestoTarifa = str_replace('%', '', $items["tax"]);
            $ImpuestoMonto = number_format($SubTotal * str_replace('%', '', $items["tax"]) / 100, 4, '.', '');
            }
            

            $invo .= '<LineaDetalle>
            <NumeroLinea>' . $NumeroLinea . '</NumeroLinea>
            <CodigoComercial>
            <Tipo>' . $CodigoTipo . '</Tipo>
            <Codigo>' . substr($CodigoCodigo, 0, 19) . '</Codigo>
            </CodigoComercial>'
            . (!empty($row->cabys) ? '<CodigoComercial><Tipo>05</Tipo><Codigo>' . htmlspecialchars($row->cabys) . '</Codigo></CodigoComercial>' : '')
            . '<Cantidad>' . $Cantidad . '</Cantidad>
            <UnidadMedida>' . $items["unit_of_measurement"] . '</UnidadMedida>
            <Detalle>' . substr($this->quitatilde($Detalle), 0, 80) . '</Detalle>
            <PrecioUnitario>' . $PrecioUnitario . '</PrecioUnitario>
            <MontoTotal>' . number_format($MontoTotal, 5, '.', '') . '</MontoTotal>';

            if ($MontoDescuento > 0) {
                $invo .= '<Descuento><MontoDescuento>' . number_format($MontoDescuento, 5, '.', '') . '</MontoDescuento>
                    <NaturalezaDescuento>' . substr($this->quitatilde($NaturalezaDescuento), 0, 80) . '</NaturalezaDescuento></Descuento>';
            }

            $invo .= '<SubTotal>' . number_format($SubTotal, 5, '.', '') . '</SubTotal>';
            $MontoExoneracion = 0.00000;
            $PorcentajeExoneracion = 0;
            $ImpuestoNeto = 0.00000;

            if ($TarifaCodigo != '01') {
                $invo .= '<Impuesto>
                            <Codigo>' . $ImpuestoCodigo . '</Codigo>
                            <CodigoTarifa>' . $TarifaCodigo . '</CodigoTarifa>
                            <Tarifa>' . $ImpuestoTarifa . '</Tarifa>
                            <Monto>' . number_format($ImpuestoMonto, 5, '.', '') . '</Monto>';

                if (isset($invoice['TipoDocumentoE']) && $invoice['TipoDocumentoE'] != '') {
                    $PorcentajeExoneracion = $invoice['PorcentajeExoneracion'];
                    $MontoExoneracion = number_format($ImpuestoMonto, 5, '.', '') * (number_format($PorcentajeExoneracion, 5, '.', '') / 100);
                    $invo .= '
                    <Exoneracion>
                    <TipoDocumento>' . $invoice['TipoDocumentoE'] . '</TipoDocumento>
                    <NumeroDocumento>' . $invoice['NumeroDocumentoE'] . '</NumeroDocumento>
                    <NombreInstitucion>' . $this->quitatilde($invoice['NombreInstitucionE']) . '</NombreInstitucion>
                    <FechaEmision>' . $invoice['FechaEmisionE'] . '</FechaEmision>
                    <PorcentajeExoneracion>' . $invoice['PorcentajeExoneracion'] . '</PorcentajeExoneracion>
                    <MontoExoneracion>' . number_format($MontoExoneracion, 5, '.', '') . '</MontoExoneracion>
                    </Exoneracion>';
                }

                $invo .= '</Impuesto>';
                $ImpuestoNeto = number_format($ImpuestoMonto, 5, '.', '') - number_format($MontoExoneracion, 5, '.', '');
                $invo .= '<ImpuestoNeto>' . number_format($ImpuestoNeto, 5, '.', '') . '</ImpuestoNeto>';
            }

            $MontoTotalLinea = number_format($SubTotal, 5, '.', '') + number_format($ImpuestoNeto, 5, '.', '');

            $invo .= '<MontoTotalLinea>' . number_format($MontoTotalLinea, 5, '.', '') . '</MontoTotalLinea>
                    </LineaDetalle>';
            if (!isset($items['type']) || !$items['type']) {
                $items['type'] = 'standard';
            }

            if ($items['type'] == 'service') {
                if ($TarifaCodigo == '01') {
                    $TotalServExentos = $TotalServExentos + $MontoTotal;
                } else {
                    $TotalServGravados = $TotalServGravados + ($MontoTotal - ($MontoTotal * ($PorcentajeExoneracion / 100)));

                    $TotalServExonerado = $TotalServExonerado + ($MontoTotal * ($PorcentajeExoneracion / 100));
                }
            } else {
                if ($TarifaCodigo == '01') {
                    $TotalMercanciasExentas = $TotalMercanciasExentas + $MontoTotal;
                } else {

                    $TotalMercanciasGravadas = $TotalMercanciasGravadas + ($MontoTotal - ($MontoTotal * ($PorcentajeExoneracion / 100)));

                    $TotalMercanciaExonerada = $TotalMercanciaExonerada + ($MontoTotal * ($PorcentajeExoneracion / 100));
                }
            }

            $TotalDescuentos = $TotalDescuentos + $MontoDescuento;
            $TotalImpuesto = $TotalImpuesto + $ImpuestoNeto;
            $count++;
        }

        $TotalExento = $TotalServExentos + $TotalMercanciasExentas;
        $TotalGravado = $TotalMercanciasGravadas + $TotalServGravados;
        $TotalExonerado = $TotalServExonerado + $TotalMercanciaExonerada;
        $TotalVenta = $TotalGravado + $TotalExento + $TotalExonerado;
        $TotalVentaNeta = $TotalVenta - $TotalDescuentos;
        $TotalComprobante = $TotalVentaNeta + $TotalImpuesto;

        $invo .= '</DetalleServicio>';
        $TotalExento = number_format($TotalServExentos, 5, '.', '') + number_format($TotalMercanciasExentas, 5, '.', '');
        $TotalGravado = number_format($TotalMercanciasGravadas, 5, '.', '') + number_format($TotalServGravados, 5, '.', '');
        $TotalExonerado = number_format($TotalServExonerado, 5, '.', '') + number_format($TotalMercanciaExonerada, 5, '.', '');
        $TotalVenta = number_format($TotalGravado, 5, '.', '') + number_format($TotalExento, 5, '.', '') + number_format($TotalExonerado, 5, '.', '');
        $TotalVentaNeta = number_format($TotalVenta, 5, '.', '') - number_format($TotalDescuentos, 5, '.', '');
        $TotalComprobante = number_format($TotalVentaNeta, 5, '.', '') + number_format($TotalImpuesto, 5, '.', '');
        $invo .= '
                  <ResumenFactura>
                  <CodigoTipoMoneda>
                    <CodigoMoneda>' . $this->Settings->currency_prefix . '</CodigoMoneda>
                    <TipoCambio>' . $this->Settings->value_changue . '</TipoCambio>
                  </CodigoTipoMoneda>';

        $invo .= '<TotalServGravados>' . number_format($TotalServGravados, 5, '.', '') . '</TotalServGravados>
        <TotalServExentos>' . number_format($TotalServExentos, 5, '.', '') . '</TotalServExentos>'
            . '';

        if ($TotalServExonerado > 0) {
            $invo .= '<TotalServExonerado>' . number_format($TotalServExonerado, 5, '.', '') . '</TotalServExonerado>';
        }

        $invo .= '<TotalMercanciasGravadas>' . number_format($TotalMercanciasGravadas, 5, '.', '') . '</TotalMercanciasGravadas>
                     <TotalMercanciasExentas>' . number_format($TotalMercanciasExentas, 5, '.', '') . '</TotalMercanciasExentas>';

        if ($TotalMercanciaExonerada > 0) {
            $invo .= '<TotalMercExonerada>' . number_format($TotalMercanciaExonerada, 5, '.', '') . '</TotalMercExonerada>';
        }

        $invo .= '<TotalGravado>' . number_format($TotalGravado, 5, '.', '') . '</TotalGravado>
                    <TotalExento>' . number_format($TotalExento, 5, '.', '') . '</TotalExento>';
        if ($TotalMercanciaExonerada > 0) {
            $invo .= '<TotalExonerado>' . number_format($TotalExonerado, 5, '.', '') . '</TotalExonerado>';
        }
        $invo .= '<TotalVenta>' . number_format($TotalVenta, 5, '.', '') . '</TotalVenta>
                    <TotalDescuentos>' . number_format($TotalDescuentos, 5, '.', '') . '</TotalDescuentos>
                    <TotalVentaNeta>' . number_format($TotalVentaNeta, 5, '.', '') . '</TotalVentaNeta>
                    <TotalImpuesto>' . number_format($TotalImpuesto, 5, '.', '') . '</TotalImpuesto>';
        $invo .=    '<TotalComprobante>' . number_format($TotalComprobante, 5, '.', '') . '</TotalComprobante>
                  </ResumenFactura>';
        if ($otrostextos) {
            $invo .= '<Otros>';
            foreach ($otrostextos as $texto) {
                $texto = (array) $texto;
                $invo .= '<OtroTexto codigo="' . $texto['titulo_texto'] . '" >' . $this->quitatilde($texto['otrotexto']) . '</OtroTexto>';
            }
            $invo .= '</Otros>';
        }

        $pieticket = '</FacturaElectronicaCompra>';
        $piefactura = '</FacturaElectronicaCompra>';

        if ($customer_id == 1 || $tipo_receptor == '05')
            $invo .= $pieticket;
        else
            $invo .= $piefactura;
        return ['xml' => $invo, 'clave' => $key, 'consecutivo' => $consecutive, 'fecha_emision' => $fecha, 'tipo_doc' => $tipodoc];
    }

    public function getNotaCredito($invoice, $itemsInvoices, $referencia, $otrostextos)
    {
        $this->load->model('customers_model');
        $this->load->model('hacienda_model');
        $receptor = $this->customers_model->getCustomerByID($invoice['customer_id']);
        $customer_id = $invoice['customer_id'];
        $moneda = $this->Settings->currency_prefix;
        $CondicionVenta = "";
        $PlazoCredito = "";

        if ($invoice['status'] == 'paid') {
            $CondicionVenta = '01';
        } else {
            $CondicionVenta = '02';
            $PlazoCredito = '30 dias';
        }

        $MedioPago = '01';


        $consecutivo = $this->hacienda_model->ccsctvcn();

        if ($consecutivo) {
            $consecutivo = $consecutivo['consecutivo'];
            $NumConse = substr($consecutivo, 10, 20);
        } else {
            $NumConse = 0;
        }

        $consecutive = $this->generate_consecutive($NumConse + 1, '03');

        $param = [
            $consecutive,
            $invoice['date']
        ];
        $key = $this->generate_key($param);
        $receptor->pre_id_number = $receptor->cf1;
        $receptor->id_number_proveedor = $receptor->cf2;
        $identifivalid = str_replace('-', '', trim($receptor->id_number_proveedor));
        $identificacion = str_replace('-', '', trim($receptor->id_number_proveedor));

        if (isset($receptor->pre_id_number)) {

            switch ($receptor->pre_id_number) {
                case "01":
                    $tipo_receptor = "01";
                    if (strlen(trim($identifivalid)) < 9 || strlen(trim($identifivalid)) > 9) {
                        $tipo_receptor = "05";
                    }
                    break;
                case "02":
                    $tipo_receptor = "02";
                    if (strlen($identifivalid) < 10 || strlen($identifivalid) > 10) {
                        $tipo_receptor = "05";
                    }
                    break;
                case "03":
                    $tipo_receptor = "03";
                    if (strlen($identifivalid) < 11 || strlen($identifivalid) > 11) {
                        if (strlen($identifivalid) < 12 || strlen($identifivalid) > 12) {
                            $tipo_receptor = "05";
                        }
                    }
                    break;
                case "04":
                    $tipo_receptor = "04";
                    if (strlen($identifivalid) < 10 || strlen($identifivalid) > 10) {
                        $tipo_receptor = "05";
                    }
                    break;
                default:
                    $tipo_receptor = "05";
                    break;
            }
        }

        date_default_timezone_set('America/Costa_Rica');
        date_default_timezone_get();
        $fecha = date('Y-m-d\TH:i:s');

        $sireceptor = false;
        if ($tipo_receptor == '05' || strtolower(trim($receptor->name)) == "cliente de paso" || strtolower(trim($receptor->name)) == "cliente de contado") {
            $sireceptor = false;
        } else {
            $sireceptor = true;
        }

        $invo = '<?xml version="1.0" encoding="UTF-8"?>
                <NotaCreditoElectronica xmlns="https://cdn.comprobanteselectronicos.go.cr/xml-schemas/v4.4/notaCreditoElectronica" xsi:schemaLocation="https://cdn.comprobanteselectronicos.go.cr/xml-schemas/v4.4/notaCreditoElectronica https://cdn.comprobanteselectronicos.go.cr/xml-schemas/v4.4/NotaCreditoElectronica_V4.4.xsd" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                    <Clave>' . $key . '</Clave>
                  <NumeroConsecutivo>' . $consecutive . '</NumeroConsecutivo>
                  <FechaEmision>' . $fecha . '</FechaEmision>
                  <Emisor>
                    <Nombre>' . $this->quitatilde(trim($this->Settings->nombre_emisor)) . '</Nombre>
                    <Identificacion>
                      <Tipo>' . $this->Settings->tipo_doc_emisor . '</Tipo>
                      <Numero>' . trim(str_replace("-", "", $this->Settings->cedula_emisor)) . '</Numero>
                    </Identificacion>
                    <NombreComercial>' . $this->quitatilde(trim($this->Settings->nombre_comercial)) . '</NombreComercial>
                    <Ubicacion>
                      <Provincia>' . substr($this->Settings->cod_provincia, -2) . '</Provincia>
                      <Canton>' . substr(trim($this->Settings->cod_canton), -2) . '</Canton>
                      <Distrito>' . substr($this->Settings->cod_distrito, -2) . '</Distrito>
                      <Barrio>' . substr($this->Settings->cod_barrio, -2) . '</Barrio>
                      <OtrasSenas>' . trim($this->Settings->otras_senas) . '</OtrasSenas>
                    </Ubicacion>
                    <Telefono>
                      <CodigoPais>' . trim($this->Settings->cod_telefono_emisor) . '</CodigoPais>
                      <NumTelefono>' . trim(str_replace("-", "", $this->Settings->telefono_emisor)) . '</NumTelefono>
                    </Telefono>
                    <CorreoElectronico>' . trim($this->Settings->email_emisor) . '</CorreoElectronico>
                  </Emisor>';

        if ($sireceptor) {
            $invo .= '<Receptor>
            <Nombre>' . trim($receptor->name) . '</Nombre>
            <Identificacion>
                    <Tipo>' . $tipo_receptor . '</Tipo>
                    <Numero>' . $receptor->id_number_proveedor . '</Numero>
            </Identificacion>';

            if ($receptor->business_name) {
                $invo .= '<NombreComercial>' . $receptor->business_name . '</NombreComercial>';
            }

            if (!empty($receptor->codigo_actividad)) {
                $invo .= '<CodigoActividad>' . $receptor->codigo_actividad . '</CodigoActividad>';
            }

            if (strlen(trim(str_replace("-", "", $receptor->phone))) == 8) {
                $invo .= '
                <Telefono>
                    <CodigoPais>506</CodigoPais>
                    <NumTelefono>' . trim(str_replace("-", "", $receptor->phone)) . '</NumTelefono>
                </Telefono>';
            }

            if ($receptor->email) {
                $invo .= '<CorreoElectronico>' . $receptor->email . '</CorreoElectronico>';
            }

            $invo .= '</Receptor>';
        }


        $invo .= '<CondicionVenta>' . $CondicionVenta . '</CondicionVenta>';

        if ($PlazoCredito) {
            $invo .= '<PlazoCredito>' . $PlazoCredito . '</PlazoCredito>';
        }

        $invo .= '<MedioPago>' . $MedioPago . '</MedioPago>
        <DetalleServicio>';

        $NumeroLinea = 0;
        $TotalServGravados = 0.0000;
        $TotalServExentos = 0.0000;
        $TotalMercanciasGravadas = 0.0000;
        $TotalMercanciasExentas = 0.0000;
        $TotalGravado = 0.0000;
        $TotalExento = 0.0000;
        $TotalVenta = 0.0000;
        $TotalDescuentos = 0.0000;
        $TotalVentaNeta = 0.0000;
        $TotalImpuesto = 0.0000;
        $TotalComprobante = 0.0000;

        $itemsInvoices = $this->reClacDiscount($itemsInvoices, $invoice);

        foreach ($itemsInvoices as $items) {

            if (strpos($items["discount"], '%') === false) {
                $items["item_discount"] = $items["discount"];
            };

            $NumeroLinea = $NumeroLinea + 1;
            $CodigoTipo = "03";
            $CodigoCodigo = $items["product_code"];
            $Cantidad = $items['quantity'];

            if ($items['item_tax']) {
                $ImpuestoTarifa = (int) str_replace('%', '', $items["tax"]);
                $calc_imp = $ImpuestoTarifa / 100;
            } else {
                $calc_imp = 1;
            }

            $Detalle = $items["product_name"];

            $row = $this->site->getProductByID($items['product_id']);
            $this->db->select("{$this->db->dbprefix('impuestos')}.*", FALSE);
            $qq = $this->db->get_where('impuestos', array('id_impuesto' => isset($row->id_tax) ? $row->id_tax : 8), 1);
            if ($qq->num_rows() > 0) {
                $im = $qq->row();
                $items['codigo_impuesto'] = $im->codigo_impuesto;
                $items['codigo_tarifa']   = $im->codigo_tarifa;
            } else {
                $items['codigo_impuesto'] = '01';
                $items['codigo_tarifa']   = '08';
            }
            if ($row->tax_method == "1") {
                $PrecioUnitario = $items["real_unit_price"];
                $InvertirImpuestoTarifa = 1;
            } else if ($row->tax_method == "0") {
                if (!$items['item_tax']) {
                    $PrecioUnitario = $items["real_unit_price"];
                } else {
                    $InvertirImpuestoTarifa = 1 + (floatval(str_replace('%', '', $items["tax"])) / 100);
                    $PrecioUnitario = number_format($items["real_unit_price"] / $InvertirImpuestoTarifa, 5, '.', '');
                }
            }
            $MontoTotal = $items["quantity"] * $PrecioUnitario;

            $NaturalezaDescuento = null;
            if ($items["item_discount"] > 0) {
                $NaturalezaDescuento = $items["product_name"];
            }

            $MontoDescuento = 0;

            if ($items["discount"] > 0) {
                $MontoDescuento = $MontoTotal * (str_replace('%', '', $items["discount"]) / 100);
            }

            $SubTotal = $MontoTotal - $MontoDescuento;

            $ImpuestoCodigo = $items['codigo_impuesto'];
            $TarifaCodigo   = $items['codigo_tarifa'];
            $ImpuestoTarifa = str_replace('%', '', $items["tax"]);
            $ImpuestoMonto  = 0;
            $ImpuestoNeto   = 0;
            if ($TarifaCodigo != '01') {
                $ImpuestoMonto = number_format($SubTotal * floatval($ImpuestoTarifa) / 100, 5, '.', '');
                $ImpuestoNeto  = $ImpuestoMonto;
            }
            $MontoTotalLinea = number_format($SubTotal, 5, '.', '') + number_format($ImpuestoNeto, 5, '.', '');
            $invo .= '<LineaDetalle>
                        <NumeroLinea>' . $NumeroLinea . '</NumeroLinea>
                        <CodigoComercial>
                        <Tipo>' . $CodigoTipo . '</Tipo>
                        <Codigo>' . substr($CodigoCodigo, 0, 19) . '</Codigo>
                        </CodigoComercial>'
                        . (!empty($row->cabys) ? '<CodigoComercial><Tipo>05</Tipo><Codigo>' . htmlspecialchars($row->cabys) . '</Codigo></CodigoComercial>' : '')
                        . '<Cantidad>' . $Cantidad . '</Cantidad>
                        <UnidadMedida>' . $items["unit_of_measurement"] . '</UnidadMedida>
                        <Detalle>' . substr($this->quitatilde($Detalle), 0, 80) . '</Detalle>
                        <PrecioUnitario>' . $PrecioUnitario . '</PrecioUnitario>
                        <MontoTotal>' . number_format($MontoTotal, 5, '.', '') . '</MontoTotal>';

            if ($MontoDescuento > 0) {
                $invo .= '<Descuento><MontoDescuento>' . number_format($MontoDescuento, 5, '.', '') . '</MontoDescuento>
                      <NaturalezaDescuento>' . substr($this->quitatilde($NaturalezaDescuento), 0, 80) . '</NaturalezaDescuento></Descuento>';
            }

            $invo .= '<SubTotal>' . number_format($SubTotal, 5, '.', '') . '</SubTotal>';
            if ($TarifaCodigo != '01') {
                $invo .= '<Impuesto>
                            <Codigo>' . $ImpuestoCodigo . '</Codigo>
                            <CodigoTarifa>' . $TarifaCodigo . '</CodigoTarifa>
                            <Tarifa>' . $ImpuestoTarifa . '</Tarifa>
                            <Monto>' . number_format($ImpuestoMonto, 5, '.', '') . '</Monto>
                        </Impuesto>';
                $invo .= '<ImpuestoNeto>' . number_format($ImpuestoNeto, 5, '.', '') . '</ImpuestoNeto>';
            }

            $invo .= '<MontoTotalLinea>' . number_format($MontoTotalLinea, 5, '.', '') . '</MontoTotalLinea>
                    </LineaDetalle>';

            if (!isset($items['type']) || !$items['type']) {
                $items['type'] = 'standard';
            }

            if ($items['type'] == 'service') {
                if (!$items['item_tax']) {
                    $TotalServExentos = $TotalServExentos + $MontoTotal;
                    $TotalExento = $TotalExento + $MontoTotal;
                } else {
                    $TotalServGravados = $TotalServGravados + $MontoTotal;
                    $TotalGravado = $TotalGravado + $MontoTotal;
                }
            } else {
                if (!$items['item_tax']) {
                    $TotalMercanciasExentas = $TotalMercanciasExentas + $MontoTotal;
                    $TotalExento = $TotalExento + $MontoTotal;
                } else {
                    $TotalMercanciasGravadas = $TotalMercanciasGravadas + $MontoTotal;
                    $TotalGravado = $TotalGravado + $MontoTotal;
                }
            }

            $TotalVenta = $TotalVenta + $MontoTotal;

            $TotalDescuentos = $TotalDescuentos;
            $TotalVentaNeta = $TotalVentaNeta + $SubTotal;
            $TotalImpuesto = $TotalImpuesto + $ImpuestoNeto;
            $TotalDescuentos = $TotalDescuentos + $MontoDescuento;
        }
        $TotalComprobante = $TotalVentaNeta + $TotalImpuesto;

        $invo .= '</DetalleServicio>';
        $invo .= '
                  <ResumenFactura>
                  <CodigoTipoMoneda>
                    <CodigoMoneda>' . $this->Settings->currency_prefix . '</CodigoMoneda>
                    <TipoCambio>' . $this->Settings->value_changue . '</TipoCambio>
                  </CodigoTipoMoneda>
                  <TotalServGravados>' . number_format($TotalServGravados, 5, '.', '') . '</TotalServGravados>
                  <TotalServExentos>' . number_format($TotalServExentos, 5, '.', '') . '</TotalServExentos>
                  <TotalMercanciasGravadas>' . number_format($TotalMercanciasGravadas, 5, '.', '') . '</TotalMercanciasGravadas>
                  <TotalMercanciasExentas>' . number_format($TotalMercanciasExentas, 5, '.', '') . '</TotalMercanciasExentas>
                  <TotalGravado>' . number_format($TotalGravado, 5, '.', '') . '</TotalGravado>
                  <TotalExento>' . number_format($TotalExento, 5, '.', '') . '</TotalExento>
                  <TotalVenta>' . number_format($TotalVenta, 5, '.', '') . '</TotalVenta>
                  <TotalDescuentos>' . number_format($TotalDescuentos, 5, '.', '') . '</TotalDescuentos>
                  <TotalVentaNeta>' . number_format($TotalVentaNeta, 5, '.', '') . '</TotalVentaNeta>
                  <TotalImpuesto>' . number_format($TotalImpuesto, 5, '.', '') . '</TotalImpuesto>
                  <TotalComprobante>' . number_format($TotalComprobante, 5, '.', '') . '</TotalComprobante>
                  </ResumenFactura>';
                  
                    <InformacionReferencia>
                      <TipoDoc>04</TipoDoc>
                      <Numero>' . $referencia->clave . '</Numero>
                      <FechaEmision>' . $referencia->fecha_emision . '</FechaEmision>
                      <Codigo>' . str_pad($invoice['type_nc'], 2, "0", STR_PAD_LEFT) . '</Codigo>
                      <Razon>' . $invoice['hold_ref'] . '</Razon>
                      </InformacionReferencia>
  
        if ($otrostextos) {
            $invo .= '<Otros>';
            foreach ($otrostextos as $texto) {
                $texto = (array) $texto;
                $invo .= '<OtroTexto codigo="' . $texto['titulo_texto'] . '" >' . $texto['otrotexto'] . '</OtroTexto>';
            }
            $invo .= '</Otros>';
        }
        $invo .= '    </NotaCreditoElectronica>
                    ';
        return ['xml' => $invo, 'clave' => $key, 'consecutivo' => $consecutive, 'fecha_emision' => $fecha];
    }

    public function getMensajeReceptor($invoice)
    {

        if ($invoice['Mensaje'] == "1") {
            $tipoDocumento = "05";
        } elseif ($invoice['Mensaje'] == "2") {
            $tipoDocumento = "06";
        } elseif ($invoice['Mensaje'] == "3") {
            $tipoDocumento = "07";
        }

        $consecutive = $this->generate_consecutive($invoice['id_documento'], $tdoc = $tipoDocumento);

        $invo = '<?xml version="1.0" encoding="utf-8"?>
        <MensajeReceptor 
        xmlns="https://cdn.comprobanteselectronicos.go.cr/xml-schemas/v4.4/mensajeReceptor" 
        xmlns:xs="http://www.w3.org/2001/XMLSchema" 
        xmlns:vc="http://www.w3.org/2007/XMLSchema-versioning" 
        xmlns:ds="http://www.w3.org/2000/09/xmldsig#">
          <Clave>' . $invoice['ClaveDocEmisor'] . '</Clave>
          <NumeroCedulaEmisor>' . str_pad($invoice['NumeroCedulaEmisor'], 12, "0", STR_PAD_LEFT) . '</NumeroCedulaEmisor>
          <FechaEmisionDoc>' . $invoice['FechaEmisionDoc'] . '</FechaEmisionDoc>
          <Mensaje>' . $invoice['Mensaje'] . '</Mensaje>
          <DetalleMensaje>' . $invoice['DetalleMensaje'] . '</DetalleMensaje>';

        if (isset($invoice['MontoTotalImpuesto']) && $invoice['MontoTotalImpuesto'] > 0) {
            $invo .= '<MontoTotalImpuesto>' . $invoice['MontoTotalImpuesto'] . '</MontoTotalImpuesto>';
        }

        if ($invoice['CondicionImpuesto'] != "00") {
            $invo .= '<CondicionImpuesto>' . $invoice['CondicionImpuesto'] . '</CondicionImpuesto>';
        }


        if ($invoice['MontoTotalImpuestoAcreditar']  > 0) {
            $invo .= '<MontoTotalImpuestoAcreditar>' . $invoice['MontoTotalImpuestoAcreditar'] . '</MontoTotalImpuestoAcreditar>';
        }


        if ($invoice['MontoTotalDeGastoAplicable']  > 0) {
            $invo .= '<MontoTotalDeGastoAplicable>' . $invoice['MontoTotalDeGastoAplicable'] . '</MontoTotalDeGastoAplicable>';
        }



        $invo .= '
          <TotalFactura>' . $invoice['TotalFactura'] . '</TotalFactura>
          <NumeroCedulaReceptor>' . str_pad($this->Settings->cedula_emisor, 12, "0", STR_PAD_LEFT) . '</NumeroCedulaReceptor>
          <NumeroConsecutivoReceptor>' . $consecutive . '</NumeroConsecutivoReceptor>
        </MensajeReceptor>
        ';

        return array($invo, $consecutive, $invoice['Mensaje'], $tipoDocumento);
    }

    public function getNotaDebito($invoice, $itemsInvoices, $referencia, $otrostextos)
    {
        $this->load->model('customers_model');
        $this->load->model('hacienda_model');
        $receptor = $this->customers_model->getCustomerByID($invoice['customer_id']);
        $customer_id = $invoice['customer_id'];
        $CodActividad = $invoice['id_actividad'];
        $CondicionVenta = '';
        $PlazoCredito = '';

        if ($invoice['status'] == 'paid') {
            $CondicionVenta = '01';
        } else {
            $CondicionVenta = '02';
            $PlazoCredito = '30 dias';
        }

        $MedioPago = '01';

        $consecutivo = $this->hacienda_model->ccsctv_nd();
        if ($consecutivo) {
            $NumConse = substr($consecutivo, 10, 20);
        } else {
            $NumConse = 0;
        }

        $consecutive = $this->generate_consecutive($NumConse + 1, '02');

        $param = [$consecutive, $invoice['date']];
        $key = $this->generate_key($param);

        $receptor->pre_id_number = $receptor->cf1;
        $receptor->id_number_proveedor = $receptor->cf2;
        $identifivalid = str_replace('-', '', trim($receptor->id_number_proveedor));

        if (isset($receptor->pre_id_number)) {
            switch ($receptor->pre_id_number) {
                case "01":
                    $tipo_receptor = "01";
                    if (strlen(trim($identifivalid)) < 9 || strlen(trim($identifivalid)) > 9) {
                        $tipo_receptor = "05";
                    }
                    break;
                case "02":
                    $tipo_receptor = "02";
                    if (strlen($identifivalid) < 10 || strlen($identifivalid) > 10) {
                        $tipo_receptor = "05";
                    }
                    break;
                case "03":
                    $tipo_receptor = "03";
                    if (strlen($identifivalid) < 11 || strlen($identifivalid) > 11) {
                        if (strlen($identifivalid) < 12 || strlen($identifivalid) > 12) {
                            $tipo_receptor = "05";
                        }
                    }
                    break;
                case "04":
                    $tipo_receptor = "04";
                    if (strlen($identifivalid) < 10 || strlen($identifivalid) > 10) {
                        $tipo_receptor = "05";
                    }
                    break;
                default:
                    $tipo_receptor = "05";
                    break;
            }
        }

        $sireceptor = ($tipo_receptor != '05'
            && strtolower(trim($receptor->name)) != "cliente de paso"
            && strtolower(trim($receptor->name)) != "cliente de contado");

        date_default_timezone_set('America/Costa_Rica');
        $fecha = date('Y-m-d\TH:i:s');

        $invo = '<?xml version="1.0" encoding="UTF-8"?>
        <NotaDebitoElectronica xmlns="https://cdn.comprobanteselectronicos.go.cr/xml-schemas/v4.4/notaDebitoElectronica"
        xsi:schemaLocation="https://cdn.comprobanteselectronicos.go.cr/xml-schemas/v4.4/notaDebitoElectronica https://cdn.comprobanteselectronicos.go.cr/xml-schemas/v4.4/NotaDebitoElectronica_V4.4.xsd"
        xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
        <Clave>' . $key . '</Clave>
        <CodigoActividad>' . $CodActividad . '</CodigoActividad>
        <NumeroConsecutivo>' . $consecutive . '</NumeroConsecutivo>
        <FechaEmision>' . $fecha . '</FechaEmision>
        <Emisor>
            <Nombre>' . $this->quitatilde(trim($this->Settings->nombre_emisor)) . '</Nombre>
            <Identificacion>
                <Tipo>' . $this->Settings->tipo_doc_emisor . '</Tipo>
                <Numero>' . trim(str_replace("-", "", $this->Settings->cedula_emisor)) . '</Numero>
            </Identificacion>
            <NombreComercial>' . $this->quitatilde(trim($this->Settings->nombre_comercial)) . '</NombreComercial>
            <Ubicacion>
                <Provincia>' . substr($this->Settings->cod_provincia, -2) . '</Provincia>
                <Canton>' . substr(trim($this->Settings->cod_canton), -2) . '</Canton>
                <Distrito>' . substr($this->Settings->cod_distrito, -2) . '</Distrito>
                <Barrio>' . substr($this->Settings->cod_barrio, -2) . '</Barrio>
                <OtrasSenas>' . trim($this->Settings->otras_senas) . '</OtrasSenas>
            </Ubicacion>
            <Telefono>
                <CodigoPais>' . trim($this->Settings->cod_telefono_emisor) . '</CodigoPais>
                <NumTelefono>' . trim(str_replace("-", "", $this->Settings->telefono_emisor)) . '</NumTelefono>
            </Telefono>
            <CorreoElectronico>' . trim($this->Settings->email_emisor) . '</CorreoElectronico>
        </Emisor>';

        if ($sireceptor) {
            $invo .= '<Receptor>
            <Nombre>' . trim($this->quitatilde($receptor->name)) . '</Nombre>
            <Identificacion>
                <Tipo>' . $tipo_receptor . '</Tipo>
                <Numero>' . $receptor->id_number_proveedor . '</Numero>
            </Identificacion>';

            if ($receptor->business_name) {
                $invo .= '<NombreComercial>' . $this->quitatilde($receptor->business_name) . '</NombreComercial>';
            }

            if (!empty($receptor->codigo_actividad)) {
                $invo .= '<CodigoActividad>' . $receptor->codigo_actividad . '</CodigoActividad>';
            }

            if (strlen(trim(str_replace("-", "", $receptor->phone))) == 8) {
                $invo .= '
                <Telefono>
                    <CodigoPais>506</CodigoPais>
                    <NumTelefono>' . trim(str_replace("-", "", $receptor->phone)) . '</NumTelefono>
                </Telefono>';
            }

            if ($receptor->email) {
                $invo .= '<CorreoElectronico>' . $receptor->email . '</CorreoElectronico>';
            }

            $invo .= '</Receptor>';
        }

        $invo .= '<CondicionVenta>' . $CondicionVenta . '</CondicionVenta>';

        if ($PlazoCredito) {
            $invo .= '<PlazoCredito>' . $PlazoCredito . '</PlazoCredito>';
        }

        $invo .= '<MedioPago>' . $MedioPago . '</MedioPago>
        <DetalleServicio>';

        $NumeroLinea = 0;
        $TotalServGravados = 0.0000;
        $TotalServExentos = 0.0000;
        $TotalMercanciasGravadas = 0.0000;
        $TotalMercanciasExentas = 0.0000;
        $TotalGravado = 0.0000;
        $TotalExento = 0.0000;
        $TotalVenta = 0.0000;
        $TotalDescuentos = 0.0000;
        $TotalVentaNeta = 0.0000;
        $TotalImpuesto = 0.0000;

        $itemsInvoices = $this->reClacDiscount($itemsInvoices, $invoice);

        foreach ($itemsInvoices as $items) {
            if (strpos($items["discount"], '%') === false) {
                $items["item_discount"] = $items["discount"];
            }

            $NumeroLinea++;
            $Cantidad = $items['quantity'];
            $Detalle = $items["product_name"];

            $row = $this->site->getProductByID($items['product_id']);
            $this->db->select("{$this->db->dbprefix('impuestos')}.*", FALSE);
            $qq = $this->db->get_where('impuestos', array('id_impuesto' => isset($row->id_tax) ? $row->id_tax : 8), 1);
            if ($qq->num_rows() > 0) {
                $im = $qq->row();
                $items['codigo_impuesto'] = $im->codigo_impuesto;
                $items['codigo_tarifa']   = $im->codigo_tarifa;
            } else {
                $items['codigo_impuesto'] = '01';
                $items['codigo_tarifa']   = '08';
            }

            if ($row->tax_method == "1") {
                $PrecioUnitario = $items["real_unit_price"];
            } else {
                if (!$items['item_tax']) {
                    $PrecioUnitario = $items["real_unit_price"];
                } else {
                    $divisor = 1 + (floatval(str_replace('%', '', $items["tax"])) / 100);
                    $PrecioUnitario = number_format($items["real_unit_price"] / $divisor, 5, '.', '');
                }
            }

            $MontoTotal = $items["quantity"] * $PrecioUnitario;
            $MontoDescuento = 0;
            $NaturalezaDescuento = null;

            if ($items["discount"] > 0) {
                $MontoDescuento = $MontoTotal * (str_replace('%', '', $items["discount"]) / 100);
                $NaturalezaDescuento = $items["product_name"];
            }

            $SubTotal = $MontoTotal - $MontoDescuento;

            $ImpuestoCodigo = $items['codigo_impuesto'];
            $TarifaCodigo   = $items['codigo_tarifa'];
            $ImpuestoTarifa = str_replace('%', '', $items["tax"]);
            $ImpuestoMonto  = 0;
            $ImpuestoNeto   = 0;
            if ($TarifaCodigo != '01') {
                $ImpuestoMonto = number_format($SubTotal * floatval($ImpuestoTarifa) / 100, 5, '.', '');
                $ImpuestoNeto  = $ImpuestoMonto;
            }
            $MontoTotalLinea = number_format($SubTotal, 5, '.', '') + number_format($ImpuestoNeto, 5, '.', '');

            $invo .= '<LineaDetalle>
                        <NumeroLinea>' . $NumeroLinea . '</NumeroLinea>
                        <CodigoComercial>
                            <Tipo>03</Tipo>
                            <Codigo>' . substr($items["product_code"], 0, 19) . '</Codigo>
                        </CodigoComercial>'
                        . (!empty($row->cabys) ? '<CodigoComercial><Tipo>05</Tipo><Codigo>' . htmlspecialchars($row->cabys) . '</Codigo></CodigoComercial>' : '')
                        . '<Cantidad>' . $Cantidad . '</Cantidad>
                        <UnidadMedida>' . $items["unit_of_measurement"] . '</UnidadMedida>
                        <Detalle>' . substr($this->quitatilde($Detalle), 0, 80) . '</Detalle>
                        <PrecioUnitario>' . $PrecioUnitario . '</PrecioUnitario>
                        <MontoTotal>' . number_format($MontoTotal, 5, '.', '') . '</MontoTotal>';

            if ($MontoDescuento > 0) {
                $invo .= '<Descuento>
                            <MontoDescuento>' . number_format($MontoDescuento, 5, '.', '') . '</MontoDescuento>
                            <NaturalezaDescuento>' . substr($this->quitatilde($NaturalezaDescuento), 0, 80) . '</NaturalezaDescuento>
                          </Descuento>';
            }

            $invo .= '<SubTotal>' . number_format($SubTotal, 5, '.', '') . '</SubTotal>';

            if ($TarifaCodigo != '01') {
                $invo .= '<Impuesto>
                            <Codigo>' . $ImpuestoCodigo . '</Codigo>
                            <CodigoTarifa>' . $TarifaCodigo . '</CodigoTarifa>
                            <Tarifa>' . $ImpuestoTarifa . '</Tarifa>
                            <Monto>' . number_format($ImpuestoMonto, 5, '.', '') . '</Monto>
                          </Impuesto>';
                $invo .= '<ImpuestoNeto>' . number_format($ImpuestoNeto, 5, '.', '') . '</ImpuestoNeto>';
            }

            $invo .= '<MontoTotalLinea>' . number_format($MontoTotalLinea, 5, '.', '') . '</MontoTotalLinea>
                    </LineaDetalle>';

            if (!isset($items['type']) || !$items['type']) {
                $items['type'] = 'standard';
            }

            if ($items['type'] == 'service') {
                if (!$items['item_tax']) {
                    $TotalServExentos += $MontoTotal;
                    $TotalExento += $MontoTotal;
                } else {
                    $TotalServGravados += $MontoTotal;
                    $TotalGravado += $MontoTotal;
                }
            } else {
                if (!$items['item_tax']) {
                    $TotalMercanciasExentas += $MontoTotal;
                    $TotalExento += $MontoTotal;
                } else {
                    $TotalMercanciasGravadas += $MontoTotal;
                    $TotalGravado += $MontoTotal;
                }
            }

            $TotalVenta += $MontoTotal;
            $TotalVentaNeta += $SubTotal;
            $TotalImpuesto += $ImpuestoNeto;
            $TotalDescuentos += $MontoDescuento;
        }
        $TotalComprobante = $TotalVentaNeta + $TotalImpuesto;

        $invo .= '</DetalleServicio>
        <ResumenFactura>
          <CodigoTipoMoneda>
            <CodigoMoneda>' . $this->Settings->currency_prefix . '</CodigoMoneda>
            <TipoCambio>' . $this->Settings->value_changue . '</TipoCambio>
          </CodigoTipoMoneda>
          <TotalServGravados>' . number_format($TotalServGravados, 5, '.', '') . '</TotalServGravados>
          <TotalServExentos>' . number_format($TotalServExentos, 5, '.', '') . '</TotalServExentos>
          <TotalMercanciasGravadas>' . number_format($TotalMercanciasGravadas, 5, '.', '') . '</TotalMercanciasGravadas>
          <TotalMercanciasExentas>' . number_format($TotalMercanciasExentas, 5, '.', '') . '</TotalMercanciasExentas>
          <TotalGravado>' . number_format($TotalGravado, 5, '.', '') . '</TotalGravado>
          <TotalExento>' . number_format($TotalExento, 5, '.', '') . '</TotalExento>
          <TotalVenta>' . number_format($TotalVenta, 5, '.', '') . '</TotalVenta>
          <TotalDescuentos>' . number_format($TotalDescuentos, 5, '.', '') . '</TotalDescuentos>
          <TotalVentaNeta>' . number_format($TotalVentaNeta, 5, '.', '') . '</TotalVentaNeta>
          <TotalImpuesto>' . number_format($TotalImpuesto, 5, '.', '') . '</TotalImpuesto>
          <TotalComprobante>' . number_format($TotalComprobante, 5, '.', '') . '</TotalComprobante>
        </ResumenFactura>
        <InformacionReferencia>
          <TipoDoc>' . str_pad($invoice['type_nd'] ?? '01', 2, "0", STR_PAD_LEFT) . '</TipoDoc>
          <Numero>' . $referencia->clave . '</Numero>
          <FechaEmision>' . $referencia->fecha_emision . '</FechaEmision>
          <Codigo>' . str_pad($invoice['motivo_nd'] ?? '01', 2, "0", STR_PAD_LEFT) . '</Codigo>
          <Razon>' . ($invoice['hold_ref'] ?? '') . '</Razon>
        </InformacionReferencia>';

        if ($otrostextos) {
            $invo .= '<Otros>';
            foreach ($otrostextos as $texto) {
                $texto = (array) $texto;
                $invo .= '<OtroTexto codigo="' . $texto['titulo_texto'] . '" >' . $texto['otrotexto'] . '</OtroTexto>';
            }
            $invo .= '</Otros>';
        }

        $invo .= '    </NotaDebitoElectronica>';

        return ['xml' => $invo, 'clave' => $key, 'consecutivo' => $consecutive, 'fecha_emision' => $fecha];
    }

    public function getREP($payment, $sale, $referencia)
    {
        $this->load->model('customers_model');
        $this->load->model('hacienda_model');
        $receptor = $this->customers_model->getCustomerByID($sale['customer_id']);
        $customer_id = $sale['customer_id'];
        $CodActividad = $sale['id_actividad'];

        $consecutivo = $this->hacienda_model->ccsctv_rep();
        if ($consecutivo) {
            $NumConse = substr($consecutivo, 10, 20);
        } else {
            $NumConse = 0;
        }
        $consecutive = $this->generate_consecutive($NumConse + 1, '09');

        $param = [$consecutive, $payment['date']];
        $key   = $this->generate_key($param);

        $receptor->pre_id_number      = $receptor->cf1;
        $receptor->id_number_proveedor = $receptor->cf2;
        $identifivalid = str_replace('-', '', trim($receptor->id_number_proveedor));

        $tipo_receptor = '05';
        if (isset($receptor->pre_id_number)) {
            switch ($receptor->pre_id_number) {
                case "01":
                    $tipo_receptor = "01";
                    if (strlen(trim($identifivalid)) < 9 || strlen(trim($identifivalid)) > 9) $tipo_receptor = "05";
                    break;
                case "02":
                    $tipo_receptor = "02";
                    if (strlen($identifivalid) < 10 || strlen($identifivalid) > 10) $tipo_receptor = "05";
                    break;
                case "03":
                    $tipo_receptor = "03";
                    if (strlen($identifivalid) < 11 || strlen($identifivalid) > 11) {
                        if (strlen($identifivalid) < 12 || strlen($identifivalid) > 12) $tipo_receptor = "05";
                    }
                    break;
                case "04":
                    $tipo_receptor = "04";
                    if (strlen($identifivalid) < 10 || strlen($identifivalid) > 10) $tipo_receptor = "05";
                    break;
                default:
                    $tipo_receptor = "05";
            }
        }

        $sireceptor = ($customer_id != 1
            && $tipo_receptor != '05'
            && strtolower(trim($receptor->name)) != "cliente de paso"
            && strtolower(trim($receptor->name)) != "cliente de contado");

        switch ($payment['paid_by']) {
            case 'cash':      $MedioPago = '01'; break;
            case 'CC':        $MedioPago = '02'; break;
            case 'Cheque':    $MedioPago = '03'; break;
            case 'TransDep':  $MedioPago = '04'; break;
            case 'SINPE':     $MedioPago = '08'; break;
            case 'digital':   $MedioPago = '09'; break;
            default:          $MedioPago = '99';
        }

        date_default_timezone_set('America/Costa_Rica');
        $fecha = date('Y-m-d\TH:i:s');

        $invo = '<?xml version="1.0" encoding="UTF-8"?>
        <ReciboPago
        xmlns="https://cdn.comprobanteselectronicos.go.cr/xml-schemas/v4.4/reciboPago"
        xmlns:xsd="http://www.w3.org/2001/XMLSchema"
        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:schemaLocation="https://cdn.comprobanteselectronicos.go.cr/xml-schemas/v4.4/reciboPago https://cdn.comprobanteselectronicos.go.cr/xml-schemas/v4.4/ReciboPago_V4.4.xsd">
        <Clave>' . $key . '</Clave>
        <CodigoActividad>' . $CodActividad . '</CodigoActividad>
        <NumeroConsecutivo>' . $consecutive . '</NumeroConsecutivo>
        <FechaEmision>' . $fecha . '</FechaEmision>
        <Emisor>
            <Nombre>' . $this->quitatilde(trim($this->Settings->nombre_emisor)) . '</Nombre>
            <Identificacion>
                <Tipo>' . $this->Settings->tipo_doc_emisor . '</Tipo>
                <Numero>' . trim(str_replace("-", "", $this->Settings->cedula_emisor)) . '</Numero>
            </Identificacion>
            <NombreComercial>' . $this->quitatilde(trim($this->Settings->nombre_comercial)) . '</NombreComercial>
            <Ubicacion>
                <Provincia>' . substr($this->Settings->cod_provincia, -2) . '</Provincia>
                <Canton>' . substr(trim($this->Settings->cod_canton), -2) . '</Canton>
                <Distrito>' . substr($this->Settings->cod_distrito, -2) . '</Distrito>
                <Barrio>' . substr($this->Settings->cod_barrio, -2) . '</Barrio>
                <OtrasSenas>' . trim($this->Settings->otras_senas) . '</OtrasSenas>
            </Ubicacion>
            <Telefono>
                <CodigoPais>' . trim($this->Settings->cod_telefono_emisor) . '</CodigoPais>
                <NumTelefono>' . trim(str_replace("-", "", $this->Settings->telefono_emisor)) . '</NumTelefono>
            </Telefono>
            <CorreoElectronico>' . trim($this->Settings->email_emisor) . '</CorreoElectronico>
        </Emisor>';

        if ($sireceptor) {
            $invo .= '<Receptor>
            <Nombre>' . trim($this->quitatilde($receptor->name)) . '</Nombre>
            <Identificacion>
                <Tipo>' . $tipo_receptor . '</Tipo>
                <Numero>' . $receptor->id_number_proveedor . '</Numero>
            </Identificacion>';

            if ($receptor->business_name) {
                $invo .= '<NombreComercial>' . $this->quitatilde($receptor->business_name) . '</NombreComercial>';
            }

            if (!empty($receptor->codigo_actividad)) {
                $invo .= '<CodigoActividad>' . $receptor->codigo_actividad . '</CodigoActividad>';
            }

            if (strlen(trim(str_replace("-", "", $receptor->phone))) == 8) {
                $invo .= '<Telefono>
                    <CodigoPais>506</CodigoPais>
                    <NumTelefono>' . trim(str_replace("-", "", $receptor->phone)) . '</NumTelefono>
                </Telefono>';
            }

            if ($receptor->email) {
                $invo .= '<CorreoElectronico>' . $receptor->email . '</CorreoElectronico>';
            }

            $invo .= '</Receptor>';
        }

        $invo .= '<CondicionVenta>02</CondicionVenta>
        <MedioPago>' . $MedioPago . '</MedioPago>
        <InformacionReferencia>
            <TipoDoc>' . str_pad($referencia->tipo_doc, 2, "0", STR_PAD_LEFT) . '</TipoDoc>
            <Numero>' . $referencia->clave . '</Numero>
            <FechaEmision>' . $referencia->fecha_emision . '</FechaEmision>
            <Codigo>03</Codigo>
            <Razon>Pago de factura</Razon>
        </InformacionReferencia>
        <ResumenFactura>
            <CodigoTipoMoneda>
                <CodigoMoneda>' . $this->Settings->currency_prefix . '</CodigoMoneda>
                <TipoCambio>' . $this->Settings->value_changue . '</TipoCambio>
            </CodigoTipoMoneda>
            <TotalComprobante>' . number_format((float)$payment['amount'], 5, '.', '') . '</TotalComprobante>
        </ResumenFactura>
        </ReciboPago>';

        return ['xml' => $invo, 'clave' => $key, 'consecutivo' => $consecutive, 'fecha_emision' => $fecha];
    }

    public function generate_key($param = "")
    {
        $fecha = explode(" ", $param[1]);
        $fecha = explode("-", $fecha[0]);
        $cod_pais = "506";
        $dia = $fecha[2];
        $mes = $fecha[1];
		$year = substr( $fecha[0], -2);
        $situacion = "1";
        $cedula = str_pad($this->Settings->cedula_emisor, 12, "0", STR_PAD_LEFT);
        $seguridad = $this->Settings->telefono_emisor;
        $clave = $cod_pais . $dia . $mes . $year . $cedula . $param[0] . $situacion . $seguridad;
        return $clave;
    }

    public function generate_consecutive($consecutivo, $tdoc = "01")
    {
        $numCompElectronico = str_pad($consecutivo, 10, "0", STR_PAD_LEFT);
        $cmatriz = $this->Settings->casa_matriz;
        $tPOS = $this->Settings->terminal_pos;
        $tDocumento = $tdoc;
        return $cmatriz . $tPOS . $tDocumento . $numCompElectronico;
    }

    public function generate_consecutive_fec($consecutivo, $tdoc = "08")
    {
        $numCompElectronico = str_pad($consecutivo, 10, "0", STR_PAD_LEFT);
        $cmatriz = $this->Settings->casa_matriz;
        $tPOS = $this->Settings->terminal_pos;
        $tDocumento = $tdoc;
        return $cmatriz . $tPOS . $tDocumento . $numCompElectronico;
    }

    public function quitatilde($cadena)
    {
        $cadena = str_replace("&", "y", $cadena);
        $originales = 'Ã€ÃÃ‚ÃƒÃ„Ã…Ã†Ã‡ÃˆÃ‰ÃŠÃ‹ÃŒÃÃŽÃÃÃ‘Ã’Ã“Ã”Ã•Ã–Ã˜Ã™ÃšÃ›ÃœÃÃžÃŸÃ Ã¡Ã¢Ã£Ã¤Ã¥Ã¦Ã§Ã¨Ã©ÃªÃ«Ã¬Ã­Ã®Ã¯Ã°Ã±Ã²Ã³Ã´ÃµÃ¶Ã¸Ã¹ÃºÃ»Ã½Ã½Ã¾Ã¿Å”Å•';
        $modificadas = 'aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr';
        $cadena = utf8_decode($cadena);
        $cadena = strtr($cadena, utf8_decode($originales), $modificadas);
        $cadena = utf8_encode($cadena);
        return str_replace(['<', '>', '"', "\r", "\n"], ['', '', '', ' ', ' '], $cadena);
    }

    public function reClacDiscount($itemsInvoices, $invoice)
    {


        /*
         * real_unit_price =  es el precio real del articulo
         * unit_price = real_unit_price - item_discount
         * net_unit_price = unit_price - item_tax
         * discount = al porcentaje de descuento
         * item_discount = el monto descontado
         * tax = porcentaje de descuento
         * item_tax = net_unit_price * tax / 100
         */

        if ($itemsInvoices) {
            foreach ($itemsInvoices as $row) {
                if ($row["discount"] != "0") {

                    if (strpos($row["discount"], '%') !== false) {
                        $rate_discount = $row["discount"];
                        $PorcentDiscount = (int) str_replace("%", "", $row["discount"]);
                    } else {
                        $t = $row["real_unit_price"] * $row["quantity"];
                        $tdd = $row["item_discount"];
                        $PorcentDiscount = $tdd * 100 / $t;
                        $rate_discount = $PorcentDiscount . '%';
                    }

                    $calc_discount = ($PorcentDiscount / 100);
                    $monto_impuesto = 0;
                    if ($row['tax'] != "0") {
                        $taxrate = (int) str_replace("%", "", $row['tax']);
                        $calc_inverse_tax = ($taxrate / 100) + 1;
                        $calc_tax = ($taxrate / 100);
                    }

                    $item_discount = $row["real_unit_price"] * $calc_discount;
                    $unit_price = $row["real_unit_price"] - $item_discount;
                    $tax = $taxrate;
                    $item_tax = $unit_price * $calc_tax;
                    $net_unit_price = $unit_price - $item_tax;
                    $discount = $rate_discount;
                    $subtotal = $unit_price * $row["quantity"];


                    $row["item_discount"] = $item_discount;
                    $row["unit_price"] = $unit_price;
                    $row["net_unit_price"] = $net_unit_price;
                    $row["tax"] = $tax;
                    $row["item_tax"] = $item_tax;
                    $row["discount"] = $discount;
                    $row["subtotal"] = $subtotal;
                }
            }
        }
        /*
         * real_unit_price =  es el precio real del articulo
         * unit_price = real_unit_price - item_discount
         * net_unit_price = unit_price - item_tax
         * discount = al porcentaje de descuento
         * item_discount = el monto descontado
         * tax = porcentaje de descuento  
         * item_tax = net_unit_price * tax / 100
         */
        $order_discount_id = $invoice['order_discount_id'];
        if ($order_discount_id) {

            if (strpos($order_discount_id, '%') !== false) {
                $PorcentDiscount = (int) str_replace("%", "", $order_discount_id);
                $rate_discount = $order_discount_id;
            } else {
                $t = $invoice['grand_total'] + $invoice['order_discount_id'];
                $tdd = $order_discount_id;
                $PorcentDiscount = $tdd * 100 / $t;
                $rate_discount = $PorcentDiscount . '%';
            }

            $calc_discount = ($PorcentDiscount / 100);


            foreach ($itemsInvoices as &$row) {
                if ($row["discount"] != "0") {

                    $otrodescuento = ($row["subtotal"] * $calc_discount) / $row["quantity"];
                    $precioInicial = $row["real_unit_price"];
                    $precioFinal = ($row["subtotal"] / $row["quantity"]) - $otrodescuento;

                    $PorcentDiscount = 100 - (($precioFinal * 100) / $precioInicial);
                    $rate_discount = $PorcentDiscount . '%';
                    $calc_discount = ($PorcentDiscount / 100);
                }


                $monto_impuesto = 0;
                if ($row['tax'] != "0") {
                    $taxrate = (int) str_replace("%", "", $row['tax']);
                    $calc_inverse_tax = ($taxrate / 100) + 1;
                    $calc_tax = ($taxrate / 100);
                }

                $monto_impuesto = 0;
                if ($row['tax'] != "0") {
                    $taxrate = (int) str_replace("%", "", $row['tax']);
                    $calc_inverse_tax = ($taxrate / 100) + 1;
                    $calc_tax = ($taxrate / 100);
                }

                $item_discount = $row["real_unit_price"] * $calc_discount;
                $unit_price = $row["real_unit_price"] - $item_discount;
                $tax = $taxrate;
                $item_tax = $unit_price * $calc_tax;
                $net_unit_price = $unit_price - $item_tax;
                $discount = $rate_discount;
                $subtotal = $unit_price * $row["quantity"];


                $row["item_discount"] = $item_discount;
                $row["unit_price"] = $unit_price;
                $row["net_unit_price"] = $net_unit_price;
                $row["tax"] = $tax;
                $row["item_tax"] = $item_tax;
                $row["discount"] = $discount;
                $row["subtotal"] = $subtotal;
            }
        }

        return $itemsInvoices;
    }

    public function tofloat($num)
    {
        $dotPos = strrpos($num, '.');
        $commaPos = strrpos($num, ',');
        $sep = (($dotPos > $commaPos) && $dotPos) ? $dotPos : ((($commaPos > $dotPos) && $commaPos) ? $commaPos : false);

        if (!$sep) {
            return floatval(preg_replace("/[^0-9]/", "", $num));
        }

        return floatval(
            preg_replace("/[^0-9]/", "", substr($num, 0, $sep)) . '.' .
                preg_replace("/[^0-9]/", "", substr($num, $sep + 1, strlen($num)))
        );
    }
}



