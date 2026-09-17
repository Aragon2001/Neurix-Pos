<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
defined('BASEPATH') or exit('No direct script access allowed');

class AuditLog_model extends CI_Model
{
    /**
     * Registra una accion en la bitacora.
     *
     * Nunca debe interrumpir la operacion que la invoca: se llama al cerrar
     * ventas, notas de credito y caja, y un fallo aqui dejaria la operacion
     * a medias. Con db_debug activo (ENVIRONMENT 'development') un INSERT
     * fallido detiene la ejecucion, asi que la escritura va protegida y solo
     * deja rastro en el log de la aplicacion.
     */
    public function log(string $action, string $entity, int $entityId, string $detail = '', float $amount = 0): void
    {
        try {
            if (!$this->db->table_exists('audit_log')) {
                log_message('error', 'Bitacora: falta la tabla audit_log, no se registro "' . $action . '"');
                return;
            }

            $ci = get_instance();

            $this->db->insert($this->db->dbprefix('audit_log'), [
                'user_id'    => (int) $ci->session->userdata('user_id'),
                'user_email' => (string) $ci->session->userdata('email'),
                'action'     => $action,
                'entity'     => $entity,
                'entity_id'  => $entityId,
                'detail'     => $detail,
                'amount'     => $amount,
                'ip'         => $ci->input->ip_address(),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Bitacora: no se pudo registrar "' . $action . '": ' . $e->getMessage());
        }
    }

    /**
     * Ultima fila registrada entre varias acciones. Sirve para saber en que
     * estado quedo algo que se revisa cada tanto, sin guardar ese estado aparte.
     *
     * @param string[] $acciones
     */
    public function ultimaAccion(array $acciones)
    {
        if (!$acciones || !$this->db->table_exists('audit_log')) {
            return NULL;
        }

        $q = $this->db
            ->where_in('action', $acciones)
            ->order_by('id', 'DESC')
            ->limit(1)
            ->get($this->db->dbprefix('audit_log'));

        return $q->num_rows() > 0 ? $q->row() : NULL;
    }

    public function getLog(int $limit = 200, int $offset = 0): array
    {
        return $this->db
            ->order_by('created_at', 'DESC')
            ->limit($limit, $offset)
            ->get($this->db->dbprefix('audit_log'))
            ->result();
    }

    public function countLog(): int
    {
        return $this->db->count_all($this->db->dbprefix('audit_log'));
    }
}
