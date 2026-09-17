<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Categories extends MY_Controller
{

    function __construct() {
        parent::__construct();

        if (!$this->loggedIn) {
            redirect('login');
        }

        $this->load->library('form_validation');
        $this->load->model('categories_model');
        $this->load->model('AuditLog_model', 'audit_log');
    }

    /** En CI3 redirect() no corta la peticion desde un metodo llamado. */
    private function _solo_admin() {
        if (!$this->Admin) {
            $this->session->set_flashdata('error', lang('access_denied'));
            redirect('pos');
            exit;
        }
    }

    private function _tienda() {
        return (int) ($this->session->userdata('store_id') ?: 1);
    }

    /** La categoria que se esta editando, para que el codigo pueda repetirse a si mismo. */
    private $_editando = NULL;

    /**
     * Regla de unicidad del codigo. Va como callback y no como comprobacion
     * suelta para que el formulario se repinte con lo que el usuario escribio.
     */
    public function _codigo_libre($code) {
        if ($this->categories_model->codigoUsado(trim($code), $this->_editando)) {
            $this->form_validation->set_message('_codigo_libre',
                lang('check_category') . ' (' . trim($code) . '). ' . lang('category_already_exist'));
            return FALSE;
        }
        return TRUE;
    }

    function index() {
        $this->data['error']   = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
        $this->data['message'] = $this->session->flashdata('message');
        // El borrado va por enlace: token_accion() es protegido y la vista no
        // puede pedirlo por su cuenta.
        $this->data['token'] = $this->token_accion();

        $this->data['page_title'] = lang('categories');
        $bc = array(array('link' => '#', 'page' => lang('categories')));
        $meta = array('page_title' => lang('categories'), 'bc' => $bc);
        $this->page_construct('categories/index', $this->data, $meta);
    }

    /**
     * El conteo de productos va en una subconsulta: CI no prefija lo que esta
     * dentro de los parentesis, asi que ahi el nombre de la tabla va completo.
     */
    function get_categories() {
        $this->load->library('datatables');
        $pr = $this->db->dbprefix('products');
        $ca = $this->db->dbprefix('categories');

        // Las acciones las dibuja la vista a partir del id: necesitan SVG, y el
        // re-estilizado de NxTable solo conserva iconos <i>.
        $this->datatables->select("categories.id as id, image, code, categories.name as name,"
            . " (SELECT COUNT(*) FROM `{$pr}` WHERE `{$pr}`.category_id = `{$ca}`.id) as productos", FALSE);
        $this->datatables->from('categories');

        echo $this->datatables->generate();
    }

    /**
     * Ficha de la categoria para el modal del listado. Solo AJAX: no tiene
     * pantalla propia, el listado es su unico punto de entrada.
     */
    function view($id = NULL) {
        $categoria = $this->site->getCategoryByID((int) $id);
        if (!$categoria) {
            show_404();
        }

        $tienda = $this->_tienda();
        $this->data['category']  = $categoria;
        $this->data['resumen']   = $this->categories_model->resumen($categoria->id, $tienda);
        $this->data['productos'] = $this->categories_model->productosDe($categoria->id, $tienda);

        $this->load->view($this->theme . 'categories/view', $this->data);
    }

    function add() {
        $this->_solo_admin();

        $this->form_validation->set_rules('code', lang('category_code'), 'required|trim|max_length[30]|callback__codigo_libre');
        $this->form_validation->set_rules('name', lang('category_name'), 'required|trim|max_length[100]');

        if ($this->form_validation->run() == true) {
            if (DEMO) {
                $this->session->set_flashdata('error', lang('disabled_in_demo'));
                redirect('categories');
            }

            $code = trim($this->input->post('code'));
            $data = array('code' => $code, 'name' => trim($this->input->post('name')));
            $imagen = $this->_subir_imagen('categories/add');
            if ($imagen) {
                $data['image'] = $imagen;
            }

            if ($nuevo = $this->categories_model->addCategory($data)) {
                $this->audit_log->log('categoria_creada', 'category', (int) $nuevo, $code . ' / ' . $data['name']);
                $this->session->set_flashdata('message', lang('category_added'));
                redirect('categories');
            }

            $this->session->set_flashdata('error', lang('error'));
            redirect('categories/add');
        }

        $this->data['error']      = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
        $this->data['page_title'] = lang('add_category');
        $bc = array(array('link' => site_url('categories'), 'page' => lang('categories')), array('link' => '#', 'page' => lang('add_category')));
        $meta = array('page_title' => lang('add_category'), 'bc' => $bc);
        $this->page_construct('categories/add', $this->data, $meta);
    }

    function edit($id = NULL) {
        $this->_solo_admin();

        if ($this->input->get('id')) {
            $id = $this->input->get('id');
        }
        $categoria = $this->site->getCategoryByID((int) $id);
        if (!$categoria) {
            show_404();
        }

        $this->_editando = (int) $categoria->id;
        $this->form_validation->set_rules('code', lang('category_code'), 'required|trim|max_length[30]|callback__codigo_libre');
        $this->form_validation->set_rules('name', lang('category_name'), 'required|trim|max_length[100]');

        if ($this->form_validation->run() == true) {
            if (DEMO) {
                $this->session->set_flashdata('error', lang('disabled_in_demo'));
                redirect('categories');
            }

            $code = trim($this->input->post('code'));
            $data = array('code' => $code, 'name' => trim($this->input->post('name')));

            if ($this->input->post('quitar_imagen')) {
                $data['image'] = NULL;
            }
            $imagen = $this->_subir_imagen('categories/edit/' . $categoria->id);
            if ($imagen) {
                $data['image'] = $imagen;
            }

            if ($this->categories_model->updateCategory($categoria->id, $data)) {
                $this->audit_log->log('categoria_editada', 'category', (int) $categoria->id, $code . ' / ' . $data['name']);
                $this->session->set_flashdata('message', lang('category_updated'));
                redirect('categories');
            }

            $this->session->set_flashdata('error', lang('error'));
            redirect('categories/edit/' . $categoria->id);
        }

        $this->data['error']      = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
        $this->data['category']   = $categoria;
        $this->data['productos']  = $this->categories_model->contarProductos($categoria->id);
        $this->data['page_title'] = lang('edit_category');
        $bc = array(array('link' => site_url('categories'), 'page' => lang('categories')), array('link' => '#', 'page' => lang('edit_category')));
        $meta = array('page_title' => lang('edit_category'), 'bc' => $bc);
        $this->page_construct('categories/edit', $this->data, $meta);
    }

    /**
     * Sube la imagen y devuelve su nombre, o NULL si no venia ninguna.
     *
     * @param string $volver a donde redirigir si el archivo es rechazado
     */
    private function _subir_imagen($volver) {
        if (empty($_FILES['userfile']['size'])) {
            return NULL;
        }

        $this->load->library('upload');
        $this->upload->initialize(array(
            'upload_path'   => 'uploads/',
            'allowed_types' => 'gif|jpg|jpeg|png|webp',
            'max_size'      => 2048,
            'max_width'     => 2000,
            'max_height'    => 2000,
            'overwrite'     => FALSE,
            'encrypt_name'  => TRUE,
        ));

        if (!$this->upload->do_upload()) {
            $this->session->set_flashdata('error', $this->upload->display_errors());
            redirect($volver);
            exit;
        }

        $foto = $this->upload->file_name;
        $this->_miniatura($foto);
        return $foto;
    }

    /**
     * La miniatura es opcional: sin la extension `gd` no hay redimensionado,
     * y el original copiado sirve igual porque el listado siempre busca el
     * archivo en uploads/thumbs.
     */
    private function _miniatura($foto) {
        $origen  = 'uploads/' . $foto;
        $destino = 'uploads/thumbs/' . $foto;

        if (extension_loaded('gd')) {
            $this->load->library('image_lib');
            $this->image_lib->clear();
            $this->image_lib->initialize(array(
                'image_library'  => 'gd2',
                'source_image'   => $origen,
                'new_image'      => $destino,
                'maintain_ratio' => TRUE,
                'width'          => 96,
                'height'         => 96,
            ));
            if ($this->image_lib->resize()) {
                return;
            }
            log_message('error', 'Categorias: no se pudo reducir la imagen. ' . $this->image_lib->display_errors(''));
        }

        @copy($origen, $destino);
    }

    /**
     * Borrar deja a los productos apuntando a una categoria inexistente, asi
     * que solo se admite sobre una categoria vacia.
     */
    function delete($id = NULL) {
        // El enlace tiene que venir de una pantalla de esta sesion.
        $this->exigir_token_accion();
        $this->_solo_admin();

        if (DEMO) {
            $this->session->set_flashdata('error', lang('disabled_in_demo'));
            redirect('categories');
        }
        if ($this->input->get('id')) {
            $id = $this->input->get('id');
        }

        $categoria = $this->site->getCategoryByID((int) $id);
        if (!$categoria) {
            show_404();
        }

        $usados = $this->categories_model->contarProductos($categoria->id);
        if ($usados > 0) {
            $this->session->set_flashdata('error', sprintf(lang('categoria_con_productos'), $usados));
            redirect('categories');
        }

        if ($this->categories_model->deleteCategory($categoria->id)) {
            $this->audit_log->log('categoria_borrada', 'category', (int) $categoria->id, $categoria->code . ' / ' . $categoria->name);
            $this->session->set_flashdata('message', lang('category_deleted'));
        } else {
            $this->session->set_flashdata('error', lang('error'));
        }
        redirect('categories');
    }

    /* ══════════════════════════ Importación ══════════════════════════ */

    /** Columnas admitidas del CSV, buscadas por nombre y no por posicion. */
    private function _columnas_import() {
        return array('code', 'name');
    }

    function import() {
        $this->_solo_admin();

        $this->data['error']   = $this->session->flashdata('error');
        $this->data['message'] = $this->session->flashdata('message');
        // En userdata y no en flashdata: recargar la pantalla no puede perder
        // la revision, que es lo unico que el paso de confirmacion acepta.
        $this->data['revision'] = $this->session->userdata('revision_categorias');

        $this->data['page_title'] = lang('import_categories');
        $bc = array(array('link' => site_url('categories'), 'page' => lang('categories')), array('link' => '#', 'page' => lang('import_categories')));
        $meta = array('page_title' => lang('import_categories'), 'bc' => $bc);
        $this->page_construct('categories/import', $this->data, $meta);
    }

    /** Paso 1: lee el archivo, valida fila por fila y no escribe nada. */
    function revisar_import() {
        $this->_solo_admin();

        if (DEMO) {
            $this->session->set_flashdata('error', lang('disabled_in_demo'));
            redirect('categories/import');
        }

        $modo = $this->input->post('modo');
        if (!in_array($modo, array('crear', 'actualizar', 'ambos'), true)) {
            $modo = 'crear';
        }

        if (empty($_FILES['userfile']['size'])) {
            $this->session->set_flashdata('error', lang('import_falta_archivo'));
            redirect('categories/import');
        }

        $this->load->library('upload');
        $this->upload->initialize(array(
            'upload_path'   => 'uploads/',
            'allowed_types' => 'csv',
            'max_size'      => 4096,
            'overwrite'     => FALSE,
            'encrypt_name'  => TRUE,
        ));

        if (!$this->upload->do_upload()) {
            $this->session->set_flashdata('error', $this->upload->display_errors());
            redirect('categories/import');
        }

        $revision = $this->_revisar_csv('uploads/' . $this->upload->file_name, $modo);
        $revision['modo']    = $modo;
        $revision['archivo'] = $this->upload->file_name;

        $this->session->set_userdata('revision_categorias', $revision);
        redirect('categories/import');
    }

    /**
     * Lee el CSV y clasifica cada fila en crear, actualizar o rechazada.
     *
     * @return array{cabecera: array, crear: array, actualizar: array, errores: array, total: int}
     */
    private function _revisar_csv($ruta, $modo) {
        $vacia = function ($errores, $cabecera = array(), $total = 0) {
            return array('cabecera' => $cabecera, 'crear' => array(), 'actualizar' => array(),
                         'errores' => $errores, 'total' => $total);
        };

        $contenido = file_get_contents($ruta);

        // Excel guarda con BOM y a veces en Latin-1: sin normalizar, las tildes
        // entran corruptas al catalogo y el primer encabezado no coincide.
        $contenido = preg_replace('/^\xEF\xBB\xBF/', '', $contenido);
        if (!mb_check_encoding($contenido, 'UTF-8')) {
            $contenido = mb_convert_encoding($contenido, 'UTF-8', 'ISO-8859-1');
        }

        $filas = array();
        foreach (preg_split('/\r\n|\r|\n/', $contenido) as $n => $linea) {
            if (trim($linea) === '') { continue; }
            $filas[$n + 1] = str_getcsv($linea, $this->_separador_csv($linea));
        }

        if (!$filas) {
            return $vacia(array(array('linea' => 0, 'error' => lang('import_archivo_vacio'))));
        }

        // array_shift() reindexaria las claves y con ellas el numero de linea
        // que se le muestra al usuario para que encuentre la fila en su archivo.
        $primer_n = array_key_first($filas);
        $primera  = $filas[$primer_n];
        unset($filas[$primer_n]);

        $cabecera = array_map(function ($c) {
            return strtolower(trim(preg_replace('/[^A-Za-z_]/', '', $c)));
        }, $primera);

        $admitidas = $this->_columnas_import();
        $faltan = array_diff($admitidas, $cabecera);
        if ($faltan) {
            return $vacia(array(array('linea' => 1, 'error' => sprintf(lang('import_faltan_columnas'), implode(', ', $faltan)))), $cabecera);
        }

        if (count($filas) > 1000) {
            return $vacia(array(array('linea' => 0, 'error' => lang('more_than_allowed'))), $cabecera, count($filas));
        }

        $crear = array();
        $actualizar = array();
        $errores = array();
        $vistos = array();

        foreach ($filas as $n => $columnas) {
            $fila = array();
            foreach ($cabecera as $i => $nombre) {
                if (in_array($nombre, $admitidas, true)) {
                    $fila[$nombre] = isset($columnas[$i]) ? trim($columnas[$i]) : '';
                }
            }

            $error = $this->_validar_fila_import($fila, $vistos);
            if ($error) {
                $errores[] = array('linea' => $n, 'code' => isset($fila['code']) ? $fila['code'] : '', 'error' => $error);
                continue;
            }

            $vistos[mb_strtolower($fila['code'])] = true;
            $existente = $this->site->getCategoryByCode($fila['code']);

            if ($existente && $modo === 'crear') {
                $errores[] = array('linea' => $n, 'code' => $fila['code'], 'error' => lang('category_already_exist'));
                continue;
            }
            if (!$existente && $modo === 'actualizar') {
                $errores[] = array('linea' => $n, 'code' => $fila['code'], 'error' => lang('cat_import_no_existe'));
                continue;
            }

            $fila['linea'] = $n;
            if ($existente) {
                $fila['id']    = (int) $existente->id;
                $fila['antes'] = $existente->name;
                $actualizar[] = $fila;
            } else {
                $crear[] = $fila;
            }
        }

        return array('cabecera' => $cabecera, 'crear' => $crear, 'actualizar' => $actualizar,
                     'errores' => $errores, 'total' => count($filas));
    }

    /** Coma o punto y coma: Excel en espanol exporta con punto y coma. */
    private function _separador_csv($linea) {
        return (substr_count($linea, ';') > substr_count($linea, ',')) ? ';' : ',';
    }

    /**
     * @return string cadena vacia si la fila esta bien, o el motivo del rechazo
     */
    private function _validar_fila_import(array $fila, array $vistos) {
        if (empty($fila['code'])) { return lang('import_falta_codigo'); }
        if (empty($fila['name'])) { return lang('import_falta_nombre'); }
        if (isset($vistos[mb_strtolower($fila['code'])])) { return lang('import_codigo_repetido'); }
        // El codigo entra en `categories`.`code`, de 30 caracteres.
        if (!preg_match('/^[A-Za-z0-9._-]{1,30}$/', $fila['code'])) { return lang('cat_import_codigo_invalido'); }
        if (mb_strlen($fila['name']) > 100) { return lang('import_nombre_largo'); }
        return '';
    }

    /** Paso 2: aplica lo que el paso 1 dejo aprobado, todo o nada. */
    function confirmar_import() {
        $this->_solo_admin();

        if (DEMO) {
            $this->session->set_flashdata('error', lang('disabled_in_demo'));
            redirect('categories/import');
        }

        $revision = json_decode((string) $this->input->post('revision'), true);
        if (!is_array($revision) || (empty($revision['crear']) && empty($revision['actualizar']))) {
            $this->session->set_flashdata('error', lang('import_nada_que_aplicar'));
            redirect('categories/import');
        }

        $this->db->trans_begin();
        $creadas = 0;
        $editadas = 0;

        foreach ((array) $revision['crear'] as $fila) {
            if (empty($fila['code']) || empty($fila['name'])) { continue; }
            if ($this->categories_model->addCategory(array('code' => $fila['code'], 'name' => $fila['name']))) {
                $creadas++;
            }
        }

        foreach ((array) $revision['actualizar'] as $fila) {
            if (empty($fila['id']) || empty($fila['name'])) { continue; }
            if ($this->categories_model->updateCategory((int) $fila['id'], array('name' => $fila['name']))) {
                $editadas++;
            }
        }

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            $this->session->set_flashdata('error', lang('import_fallo_transaccion'));
            redirect('categories/import');
        }
        $this->db->trans_commit();
        $this->session->unset_userdata('revision_categorias');

        $this->audit_log->log('categorias_importadas', 'category', 0, $creadas . ' creadas / ' . $editadas . ' actualizadas');
        $this->session->set_flashdata('message', sprintf(lang('cat_import_resultado'), $creadas, $editadas));
        redirect('categories');
    }

    /** Descarta la revision pendiente sin aplicar nada. */
    function descartar_import() {
        $this->_solo_admin();
        $this->session->unset_userdata('revision_categorias');
        redirect('categories/import');
    }

    /** Las filas rechazadas, en CSV, con el numero de linea del archivo. */
    function descargar_rechazos_import() {
        $this->_solo_admin();

        $errores = json_decode((string) $this->input->post('errores'), true);
        if (!is_array($errores)) { $errores = array(); }

        $salida = "linea,codigo,error\n";
        foreach ($errores as $e) {
            $salida .= sprintf("%d,\"%s\",\"%s\"\n",
                isset($e['linea']) ? (int) $e['linea'] : 0,
                str_replace('"', '""', isset($e['code']) ? $e['code'] : ''),
                str_replace('"', '""', isset($e['error']) ? $e['error'] : ''));
        }

        $this->output
            ->set_content_type('text/csv', 'utf-8')
            ->set_header('Content-Disposition: attachment; filename="categorias-rechazadas.csv"')
            ->set_output("\xEF\xBB\xBF" . $salida);
    }

}
