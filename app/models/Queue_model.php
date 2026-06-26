<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Queue_model extends CI_Model
{
    const TYPE_EMAIL = 'email';

    /**
     * Encola un job. Devuelve el ID insertado.
     */
    public function push($type, array $payload, $maxAttempts = 3)
    {
        $this->db->insert('queue', [
            'type'            => $type,
            'payload'         => json_encode($payload),
            'status'          => 'pending',
            'attempts'        => 0,
            'max_attempts'    => $maxAttempts,
            'next_attempt_at' => date('Y-m-d H:i:s'),
            'created_at'      => date('Y-m-d H:i:s'),
        ]);
        return $this->db->insert_id();
    }

    /**
     * Obtiene jobs pendientes listos para procesar y los marca como 'processing'.
     */
    public function pop($type = null, $limit = 5)
    {
        $this->db->where('status', 'pending')
                 ->where('next_attempt_at <=', date('Y-m-d H:i:s'))
                 ->where('attempts <', $this->db->protect_identifiers('max_attempts', false))
                 ->order_by('id', 'ASC')
                 ->limit($limit);

        if ($type) {
            $this->db->where('type', $type);
        }

        $jobs = $this->db->get('queue')->result();

        if (empty($jobs)) return [];

        $ids = array_column($jobs, 'id');
        $this->db->where_in('id', $ids)
                 ->update('queue', ['status' => 'processing']);

        return $jobs;
    }

    /**
     * Marca un job como completado.
     */
    public function markDone($id)
    {
        $this->db->where('id', (int)$id)->update('queue', [
            'status'  => 'done',
            'done_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Marca un job como fallido y programa retry con backoff exponencial.
     * Si se agotaron los intentos lo marca como 'failed' definitivo.
     */
    public function markFailed($id, $error = '')
    {
        $job = $this->db->get_where('queue', ['id' => (int)$id])->row();
        if (!$job) return;

        $attempts = (int)$job->attempts + 1;

        if ($attempts >= (int)$job->max_attempts) {
            $this->db->where('id', (int)$id)->update('queue', [
                'status'     => 'failed',
                'attempts'   => $attempts,
                'last_error' => substr($error, 0, 1000),
            ]);
        } else {
            $delaySeconds = pow(2, $attempts) * 30; // 30s, 60s, 120s...
            $this->db->where('id', (int)$id)->update('queue', [
                'status'          => 'pending',
                'attempts'        => $attempts,
                'last_error'      => substr($error, 0, 1000),
                'next_attempt_at' => date('Y-m-d H:i:s', time() + $delaySeconds),
            ]);
        }
    }
}
