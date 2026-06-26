<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|   example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|   http://codeigniter.com/user_guide/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There are three reserved routes:
|
|   $route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|   $route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router which controller/method to use if those
| provided in the URL cannot be matched to a valid route.
|
|   $route['translate_uri_dashes'] = FALSE;
|
| This is not exactly a route, but allows you to automatically route
| controller and method names that contain dashes. '-' isn't a valid
| class or method name character, so it requires translation.
| When you set this option to TRUE, it will replace ALL dashes in the
| controller and method URI segments.
|
| Examples: my-controller/index -> my_controller/index
|       my-controller/my-method -> my_controller/my_method
*/
$route['default_controller'] = 'welcome';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;

// ---- PosView: visualización de comprobantes ----
$route['pos/view/(:any)']                = 'posview/view/$1';
$route['pos/view']                       = 'posview/view';
$route['pos/view_proforma/(:any)']       = 'posview/view_proforma/$1';
$route['pos/view_proforma']              = 'posview/view_proforma';
$route['pos/viewnc/(:any)']              = 'posview/viewnc/$1';
$route['pos/viewnc']                     = 'posview/viewnc';
$route['pos/view_close_register/(:any)'] = 'posview/view_close_register/$1';

// ---- PosEmail: correos de comprobantes ----
$route['pos/email_receipt']              = 'posemail/email_receipt';
$route['pos/email_receipt_credit']       = 'posemail/email_receipt_credit';
$route['pos/email_proforma']             = 'posemail/email_proforma';

// ---- PosRegister: caja y registros ----
$route['pos/register_details']           = 'posregister/register_details';
$route['pos/today_sale']                 = 'posregister/today_sale';
$route['pos/shortcuts']                  = 'posregister/shortcuts';
$route['pos/close_register/(:any)']      = 'posregister/close_register/$1';
$route['pos/close_register']             = 'posregister/close_register';
$route['pos/products_sales_in_register'] = 'posregister/products_sales_in_register';
$route['pos/invoices_in_register']       = 'posregister/invoices_in_register';

// ---- PosPrint: impresión y tickets ----
$route['pos/view_bill']                  = 'posprint/view_bill';
$route['pos/print_parquimetro/(:any)']   = 'posprint/print_parquimetro/$1';
$route['pos/print_comanda/(:any)']       = 'posprint/print_comanda/$1';
$route['pos/print_register/(:any)']      = 'posprint/print_register/$1';
$route['pos/print_register']             = 'posprint/print_register';
$route['pos/print_receipt/(:any)']       = 'posprint/print_receipt/$1';
$route['pos/print_cuenta/(:any)']        = 'posprint/print_cuenta/$1';
$route['pos/receipt_img']                = 'posprint/receipt_img';
$route['pos/open_drawer']                = 'posprint/open_drawer';
$route['pos/p/(:any)']                   = 'posprint/p/$1';
$route['pos/p']                          = 'posprint/p';
$route['pos/invice_barcode/(:any)']      = 'posprint/invice_barcode/$1';
$route['pos/invice_barcode_2/(:any)']    = 'posprint/invice_barcode_2/$1';

// ---- PosCredit: nota de crédito ----
$route['pos/creditnote']                 = 'poscredit/creditnote';

$route['users'] = 'auth/users';
$route['login'] = 'auth/login';
$route['logout'] = 'auth/logout';
$route['pos/(:num)'] = 'pos/index/$1';
$route['users/add'] = 'auth/create_user';
$route['logout/(:any)'] = 'auth/logout/$1';
$route['users/profile/(:num)'] = 'auth/profile/$1';
