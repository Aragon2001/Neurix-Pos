<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Search extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();

        if (!$this->loggedIn) {
            http_response_code(401);
            die(json_encode(['error' => 'No autorizado']));
        }

        $this->load->model('products_model');
        $this->load->model('customers_model');
        $this->load->model('sales_model');
    }

    /**
     * Búsqueda global en el sistema
     * Busca en: Menú, Productos, Clientes, Ventas, Facturas
     */
    public function global_search()
    {
        if (!$this->input->is_ajax_request()) {
            http_response_code(400);
            die(json_encode(['error' => 'Solo AJAX']));
        }

        $query = trim($this->input->get('q'));
        if (strlen($query) < 2) {
            echo json_encode([]);
            return;
        }

        $results = [
            'menu' => $this->_search_menu($query),
            'products' => $this->_search_products($query),
            'customers' => $this->_search_customers($query),
            'sales' => $this->_search_sales($query),
        ];

        // Filtrar categorías vacías
        $results = array_filter($results, fn($v) => !empty($v));

        echo json_encode($results);
    }

    /**
     * Búsqueda en menú del sistema
     */
    private function _search_menu($query)
    {
        $menu_items = [
            ['title' => lang('dashboard'), 'icon' => 'chart-line', 'url' => 'dashboard', 'category' => 'Menu'],
            ['title' => lang('pos'), 'icon' => 'shopping-cart', 'url' => 'pos', 'category' => 'Menu'],
            ['title' => lang('sales'), 'icon' => 'receipt', 'url' => 'sales', 'category' => 'Menu'],
            ['title' => lang('customers'), 'icon' => 'users', 'url' => 'customers', 'category' => 'Menu'],
            ['title' => lang('products'), 'icon' => 'package', 'url' => 'products', 'category' => 'Menu'],
            ['title' => lang('categories'), 'icon' => 'tags', 'url' => 'categories', 'category' => 'Menu'],
            ['title' => lang('suppliers'), 'icon' => 'truck', 'url' => 'suppliers', 'category' => 'Menu'],
            ['title' => lang('purchases'), 'icon' => 'shopping-bag', 'url' => 'purchases', 'category' => 'Menu'],
            ['title' => lang('reports'), 'icon' => 'bar-chart', 'url' => 'reports', 'category' => 'Menu'],
            ['title' => lang('creditnotes'), 'icon' => 'file-text', 'url' => 'creditnotes', 'category' => 'Menu'],
            ['title' => lang('debitnotes'), 'icon' => 'file-text', 'url' => 'debitnotes', 'category' => 'Menu'],
            ['title' => lang('settings'), 'icon' => 'settings', 'url' => 'settings', 'category' => 'Menu'],
        ];

        $results = [];
        $query_lower = strtolower($query);

        foreach ($menu_items as $item) {
            if (strpos(strtolower($item['title']), $query_lower) !== false) {
                $results[] = [
                    'type' => 'menu',
                    'title' => $item['title'],
                    'icon' => $item['icon'],
                    'url' => site_url($item['url']),
                    'category' => $item['category'],
                ];
            }
        }

        return $results;
    }

    /**
     * Búsqueda de productos por nombre o código
     */
    private function _search_products($query)
    {
        $this->db->select('id, code, name, price, category_id');
        $this->db->where('(`name` LIKE ? OR `code` LIKE ?)', ["%{$query}%", "%{$query}%"], false);
        $this->db->limit(8);
        $products = $this->db->get('products')->result();

        $results = [];
        foreach ($products as $product) {
            $formatted_price = $this->settings->currency_prefix . ' ' .
                number_format($product->price, $this->settings->decimals,
                $this->settings->decimals_sep, $this->settings->thousands_sep);

            $results[] = [
                'type' => 'product',
                'id' => $product->id,
                'title' => $product->name . ' (' . $product->code . ')',
                'subtitle' => lang('price') . ': ' . $formatted_price,
                'url' => site_url('products/edit/' . $product->id),
                'icon' => 'package',
            ];
        }

        return $results;
    }

    /**
     * Búsqueda de clientes por nombre o cédula
     */
    private function _search_customers($query)
    {
        $this->db->select('id, name, cf2, email');
        $this->db->where('(`name` LIKE ? OR `cf2` LIKE ? OR `email` LIKE ?)', ["%{$query}%", "%{$query}%", "%{$query}%"], false);
        $this->db->limit(8);
        $customers = $this->db->get('customers')->result();

        $results = [];
        foreach ($customers as $customer) {
            $subtitle = [];
            if ($customer->cf2) $subtitle[] = $customer->cf2;
            if ($customer->email) $subtitle[] = $customer->email;

            $results[] = [
                'type' => 'customer',
                'id' => $customer->id,
                'title' => $customer->name,
                'subtitle' => implode(' • ', $subtitle),
                'url' => site_url('customers/edit/' . $customer->id),
                'icon' => 'user',
            ];
        }

        return $results;
    }

    /**
     * Búsqueda de ventas/facturas por número
     */
    private function _search_sales($query)
    {
        $this->db->select('s.id, s.customer_name, s.total, s.date, h.consecutivo');
        $this->db->from('sales s');
        $this->db->join('hacienda_tiketes h', 'h.sale_id = s.id', 'left');

        $this->db->where('(CAST(s.id AS CHAR) LIKE ? OR s.customer_name LIKE ? OR h.consecutivo LIKE ?)',
                        ["%{$query}%", "%{$query}%", "%{$query}%"], false);
        $this->db->limit(8);
        $sales = $this->db->get()->result();

        $results = [];
        foreach ($sales as $sale) {
            $numero = $sale->consecutivo ? $sale->consecutivo : '#' . $sale->id;
            $formatted_total = $this->settings->currency_prefix . ' ' .
                number_format($sale->total, $this->settings->decimals,
                $this->settings->decimals_sep, $this->settings->thousands_sep);

            $results[] = [
                'type' => 'sale',
                'id' => $sale->id,
                'title' => $sale->customer_name . ' - ' . $numero,
                'subtitle' => $formatted_total . ' • ' . date($this->settings->dateformat, strtotime($sale->date)),
                'url' => site_url('pos/view/' . $sale->id),
                'icon' => 'shopping-cart',
            ];
        }

        return $results;
    }
}
