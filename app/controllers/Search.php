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
     * Busca en: Menú, Productos, Clientes, Ventas, Facturas, Compras,
     * Notas de crédito/débito, Proveedores, Categorías, Usuarios y Configuración
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
            'creditnotes' => $this->_search_creditnotes($query),
            'debitnotes' => $this->_search_debitnotes($query),
            'purchases' => $this->_search_purchases($query),
            'suppliers' => $this->_search_suppliers($query),
            'categories' => $this->_search_categories($query),
            'users' => $this->_search_users($query),
        ];

        // Filtrar categorías vacías
        $results = array_filter($results, fn($v) => !empty($v));

        echo json_encode($results);
    }

    /**
     * Búsqueda en menú del sistema (páginas y configuración)
     * Refleja la visibilidad real del sidebar: las secciones de gestión
     * (productos, compras, proveedores, configuración, informes, etc.)
     * solo son visibles/buscables para usuarios Admin.
     */
    private function _search_menu($query)
    {
        $menu_items = [
            ['title' => lang('dashboard'), 'icon' => 'chart-line', 'url' => 'dashboard', 'category' => 'Menu'],
            ['title' => lang('pos'), 'icon' => 'shopping-cart', 'url' => 'pos', 'category' => 'Menu'],
            ['title' => lang('customers'), 'icon' => 'users', 'url' => 'customers', 'category' => 'Menu'],
            ['title' => lang('add_customer'), 'icon' => 'users', 'url' => 'customers/add', 'category' => 'Menu'],
        ];

        if ($this->Settings->fe == "1") {
            $menu_items[] = ['title' => lang('documents_upload'), 'icon' => 'cloud', 'url' => 'cargadocumentos', 'category' => 'Menu'];
        }

        if ($this->Admin) {
            $menu_items = array_merge($menu_items, [
                ['title' => lang('products'), 'icon' => 'package', 'url' => 'products', 'category' => 'Menu'],
                ['title' => lang('add_product'), 'icon' => 'package', 'url' => 'products/add', 'category' => 'Menu'],
                ['title' => lang('categories'), 'icon' => 'tags', 'url' => 'categories', 'category' => 'Menu'],
                ['title' => lang('sales'), 'icon' => 'receipt', 'url' => 'sales', 'category' => 'Menu'],
                ['title' => lang('list_opened_bills'), 'icon' => 'receipt', 'url' => 'sales/opened', 'category' => 'Menu'],
                ['title' => lang('credit_notes'), 'icon' => 'file-text', 'url' => 'CreditNotes', 'category' => 'Menu'],
                ['title' => lang('notas_debito'), 'icon' => 'file-text', 'url' => 'debitnotes', 'category' => 'Menu'],
                ['title' => lang('purchases'), 'icon' => 'shopping-bag', 'url' => 'purchases', 'category' => 'Menu'],
                ['title' => lang('add_purchase'), 'icon' => 'shopping-bag', 'url' => 'purchases/add', 'category' => 'Menu'],
                ['title' => lang('list_expenses'), 'icon' => 'coin', 'url' => 'purchases/expenses', 'category' => 'Menu'],
                ['title' => lang('list_fec'), 'icon' => 'file-text', 'url' => 'facturascompras', 'category' => 'Menu'],
                ['title' => lang('suppliers'), 'icon' => 'truck', 'url' => 'suppliers', 'category' => 'Menu'],
                ['title' => lang('list_users'), 'icon' => 'user', 'url' => 'users', 'category' => 'Menu'],
                ['title' => lang('add_user'), 'icon' => 'user', 'url' => 'users/add', 'category' => 'Menu'],
                ['title' => lang('settings'), 'icon' => 'settings', 'url' => 'settings', 'category' => 'Configuración'],
                ['title' => lang('printers'), 'icon' => 'settings', 'url' => 'settings/printers', 'category' => 'Configuración'],
                ['title' => lang('stores'), 'icon' => 'store', 'url' => 'settings/stores', 'category' => 'Configuración'],
                ['title' => lang('reports'), 'icon' => 'bar-chart', 'url' => 'reports', 'category' => 'Menu'],
                ['title' => lang('daily_sales'), 'icon' => 'bar-chart', 'url' => 'reports/daily_sales', 'category' => 'Menu'],
                ['title' => lang('monthly_sales'), 'icon' => 'bar-chart', 'url' => 'reports/monthly_sales', 'category' => 'Menu'],
                ['title' => lang('top_products'), 'icon' => 'bar-chart', 'url' => 'reports/top_products', 'category' => 'Menu'],
            ]);

            if ($this->Settings->is_shipping == 1) {
                $menu_items[] = ['title' => lang('shipping_method'), 'icon' => 'truck', 'url' => 'settings/shipping', 'category' => 'Configuración'];
            }
            if ($this->Settings->propina_enable == '1') {
                $menu_items[] = ['title' => lang('lista_mesas'), 'icon' => 'settings', 'url' => 'settings/waiting_tables', 'category' => 'Configuración'];
            }
            if ($this->db->dbdriver != 'sqlite3') {
                $menu_items[] = ['title' => lang('backups'), 'icon' => 'database', 'url' => 'settings/backups', 'category' => 'Configuración'];
            }
        }

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
        $q = $this->db->escape_like_str($query);
        $this->db->select('id, code, name, price, category_id');
        $this->db->where("(`name` LIKE '%{$q}%' OR `code` LIKE '%{$q}%')", NULL, FALSE);
        $this->db->limit(8);
        $products = $this->db->get('products')->result();

        $results = [];
        foreach ($products as $product) {
            $formatted_price = $this->Settings->currency_prefix . ' ' .
                number_format($product->price, $this->Settings->decimals,
                $this->Settings->decimals_sep, $this->Settings->thousands_sep);

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
        $q = $this->db->escape_like_str($query);
        $this->db->select('id, name, cf2, email');
        $this->db->where("(`name` LIKE '%{$q}%' OR `cf2` LIKE '%{$q}%' OR `email` LIKE '%{$q}%')", NULL, FALSE);
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
        $q = $this->db->escape_like_str($query);
        $this->db->select('s.id, s.customer_name, s.total, s.date, h.consecutivo');
        $this->db->from('sales s');
        $this->db->join('hacienda_tiketes h', 'h.sale_id = s.id', 'left');

        $this->db->where("(CAST(s.id AS CHAR) LIKE '%{$q}%' OR s.customer_name LIKE '%{$q}%' OR h.consecutivo LIKE '%{$q}%')", NULL, FALSE);
        $this->db->limit(8);
        $sales = $this->db->get()->result();

        $results = [];
        foreach ($sales as $sale) {
            $numero = $sale->consecutivo ? $sale->consecutivo : '#' . $sale->id;
            $formatted_total = $this->Settings->currency_prefix . ' ' .
                number_format($sale->total, $this->Settings->decimals,
                $this->Settings->decimals_sep, $this->Settings->thousands_sep);

            $results[] = [
                'type' => 'sale',
                'id' => $sale->id,
                'title' => $sale->customer_name . ' - ' . $numero,
                'subtitle' => $formatted_total . ' • ' . date($this->Settings->dateformat, strtotime($sale->date)),
                'url' => site_url('pos/view/' . $sale->id),
                'icon' => 'shopping-cart',
            ];
        }

        return $results;
    }

    /**
     * Búsqueda de notas de crédito por número/consecutivo o cliente
     */
    private function _search_creditnotes($query)
    {
        if (!$this->Admin) return [];

        $q = $this->db->escape_like_str($query);
        $this->db->select('note_credits.id, note_credits.customer_name, note_credits.grand_total, note_credits.date, cn.consecutivo');
        $this->db->from('note_credits');
        $this->db->join('hacienda_cn cn', 'cn.id_cn = note_credits.id', 'left');
        $ncTable = $this->db->dbprefix('note_credits');
        $this->db->where('note_credits.store_id', $this->session->userdata('store_id'));
        $this->db->where("(CAST({$ncTable}.id AS CHAR) LIKE '%{$q}%' OR {$ncTable}.customer_name LIKE '%{$q}%' OR cn.consecutivo LIKE '%{$q}%')", NULL, FALSE);
        $this->db->limit(8);
        $rows = $this->db->get()->result();

        $results = [];
        foreach ($rows as $row) {
            $numero = $row->consecutivo ? $row->consecutivo : '#' . $row->id;
            $formatted_total = $this->Settings->currency_prefix . ' ' .
                number_format($row->grand_total, $this->Settings->decimals,
                $this->Settings->decimals_sep, $this->Settings->thousands_sep);

            $results[] = [
                'type' => 'creditnote',
                'id' => $row->id,
                'title' => $row->customer_name . ' - ' . $numero,
                'subtitle' => $formatted_total . ' • ' . date($this->Settings->dateformat, strtotime($row->date)),
                'url' => site_url('creditnotes/viewnc/' . $row->id),
                'icon' => 'file-text',
            ];
        }

        return $results;
    }

    /**
     * Búsqueda de notas de débito por número/consecutivo o cliente
     */
    private function _search_debitnotes($query)
    {
        if (!$this->Admin) return [];

        $q = $this->db->escape_like_str($query);
        $this->db->select('nd.id, nd.customer_name, nd.grand_total, nd.date, hn.consecutivo');
        $this->db->from('note_debits nd');
        $this->db->join('hacienda_nd hn', 'hn.nd_id = nd.id', 'left');
        $this->db->where('nd.store_id', $this->session->userdata('store_id'));
        $this->db->where("(CAST(nd.id AS CHAR) LIKE '%{$q}%' OR nd.customer_name LIKE '%{$q}%' OR hn.consecutivo LIKE '%{$q}%')", NULL, FALSE);
        $this->db->limit(8);
        $rows = $this->db->get()->result();

        $results = [];
        foreach ($rows as $row) {
            $numero = $row->consecutivo ? $row->consecutivo : '#' . $row->id;
            $formatted_total = $this->Settings->currency_prefix . ' ' .
                number_format($row->grand_total, $this->Settings->decimals,
                $this->Settings->decimals_sep, $this->Settings->thousands_sep);

            $results[] = [
                'type' => 'debitnote',
                'id' => $row->id,
                'title' => $row->customer_name . ' - ' . $numero,
                'subtitle' => $formatted_total . ' • ' . date($this->Settings->dateformat, strtotime($row->date)),
                'url' => site_url('debitnotes/viewnd/' . $row->id),
                'icon' => 'file-text',
            ];
        }

        return $results;
    }

    /**
     * Búsqueda de compras por referencia o nota
     */
    private function _search_purchases($query)
    {
        if (!$this->Admin) return [];

        $q = $this->db->escape_like_str($query);
        $this->db->select('id, reference, note, total, date');
        $this->db->from('purchases');
        $this->db->where('store_id', $this->session->userdata('store_id'));
        $this->db->where("(CAST(id AS CHAR) LIKE '%{$q}%' OR reference LIKE '%{$q}%' OR note LIKE '%{$q}%')", NULL, FALSE);
        $this->db->limit(8);
        $rows = $this->db->get()->result();

        $results = [];
        foreach ($rows as $row) {
            $formatted_total = $this->Settings->currency_prefix . ' ' .
                number_format($row->total, $this->Settings->decimals,
                $this->Settings->decimals_sep, $this->Settings->thousands_sep);

            $results[] = [
                'type' => 'purchase',
                'id' => $row->id,
                'title' => ($row->reference ?: '#' . $row->id),
                'subtitle' => $formatted_total . ' • ' . date($this->Settings->dateformat, strtotime($row->date)),
                'url' => site_url('purchases/edit/' . $row->id),
                'icon' => 'shopping-bag',
            ];
        }

        return $results;
    }

    /**
     * Búsqueda de proveedores por nombre, cédula o correo
     */
    private function _search_suppliers($query)
    {
        if (!$this->Admin) return [];

        $q = $this->db->escape_like_str($query);
        $this->db->select('id, name, cf2, email');
        $this->db->where("(`name` LIKE '%{$q}%' OR `cf2` LIKE '%{$q}%' OR `email` LIKE '%{$q}%')", NULL, FALSE);
        $this->db->limit(8);
        $rows = $this->db->get('suppliers')->result();

        $results = [];
        foreach ($rows as $row) {
            $subtitle = [];
            if ($row->cf2) $subtitle[] = $row->cf2;
            if ($row->email) $subtitle[] = $row->email;

            $results[] = [
                'type' => 'supplier',
                'id' => $row->id,
                'title' => $row->name,
                'subtitle' => implode(' • ', $subtitle),
                'url' => site_url('suppliers/edit/' . $row->id),
                'icon' => 'truck',
            ];
        }

        return $results;
    }

    /**
     * Búsqueda de categorías por nombre o código
     */
    private function _search_categories($query)
    {
        if (!$this->Admin) return [];

        $q = $this->db->escape_like_str($query);
        $this->db->select('id, code, name');
        $this->db->where("(`name` LIKE '%{$q}%' OR `code` LIKE '%{$q}%')", NULL, FALSE);
        $this->db->limit(8);
        $rows = $this->db->get('categories')->result();

        $results = [];
        foreach ($rows as $row) {
            $results[] = [
                'type' => 'category',
                'id' => $row->id,
                'title' => $row->name,
                'subtitle' => $row->code,
                'url' => site_url('categories/edit/' . $row->id),
                'icon' => 'tags',
            ];
        }

        return $results;
    }

    /**
     * Búsqueda de usuarios del sistema por nombre o correo
     */
    private function _search_users($query)
    {
        if (!$this->Admin) return [];

        $q = $this->db->escape_like_str($query);
        $this->db->select('id, first_name, last_name, email');
        $this->db->where("(first_name LIKE '%{$q}%' OR last_name LIKE '%{$q}%' OR email LIKE '%{$q}%')", NULL, FALSE);
        $this->db->limit(8);
        $rows = $this->db->get('users')->result();

        $results = [];
        foreach ($rows as $row) {
            $results[] = [
                'type' => 'user',
                'id' => $row->id,
                'title' => trim($row->first_name . ' ' . $row->last_name),
                'subtitle' => $row->email,
                'url' => site_url('users/profile/' . $row->id),
                'icon' => 'user',
            ];
        }

        return $results;
    }
}
