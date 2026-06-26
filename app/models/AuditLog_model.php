<?php

defined('BASEPATH') or exit('No direct script access allowed');

class AuditLog_model extends CI_Model
{
    public function log(string $action, string $entity, int $entityId, string $detail = '', float $amount = 0): void
    {
        $ci = get_instance();
        $userId    = (int) $ci->session->userdata('user_id');
        $userEmail = (string) $ci->session->userdata('email');

        $this->db->insert($this->db->dbprefix('audit_log'), [
            'user_id'    => $userId,
            'user_email' => $userEmail,
            'action'     => $action,
            'entity'     => $entity,
            'entity_id'  => $entityId,
            'detail'     => $detail,
            'amount'     => $amount,
            'ip'         => $ci->input->ip_address(),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
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
