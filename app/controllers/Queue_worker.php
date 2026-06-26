<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Queue_worker — procesa jobs pendientes en background.
 *
 * Se invoca de dos formas:
 *   1. Cron:   php index.php queue_worker run
 *   2. HTTP:   GET /queue_worker/run  (llamada fire-and-forget desde Pos.php)
 *
 * El método run() usa fastcgi_finish_request() para cerrar la conexión HTTP
 * antes de procesar, de modo que el navegador no espera.
 */
class Queue_worker extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('queue_model');
        $this->load->model('hacienda_model');
    }

    public function run($type = null)
    {
        // Liberar la conexión HTTP inmediatamente si estamos en modo web
        if (function_exists('fastcgi_finish_request')) {
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode(['queued' => true]);
            fastcgi_finish_request();
        } else {
            // Apache / módulo PHP: flush + ignore client disconnect
            ignore_user_abort(true);
            ob_end_clean();
            header('Content-Type: application/json');
            header('Content-Length: 15');
            echo json_encode(['queued' => true]);
            ob_flush();
            flush();
        }

        // Límite de tiempo generoso para el procesamiento en background
        set_time_limit(300);

        $jobs = $this->queue_model->pop($type, 10);

        foreach ($jobs as $job) {
            try {
                $payload = json_decode($job->payload, true);

                switch ($job->type) {
                    case Queue_model::TYPE_EMAIL:
                        $this->_processEmail($job->id, $payload);
                        break;

                    default:
                        $this->queue_model->markFailed($job->id, "Tipo de job desconocido: {$job->type}");
                }
            } catch (Exception $e) {
                log_message('error', "[Queue_worker] Job #{$job->id} excepción: " . $e->getMessage());
                $this->queue_model->markFailed($job->id, $e->getMessage());
            }
        }
    }

    // -------------------------------------------------------------------------

    private function _processEmail($jobId, array $payload)
    {
        $required = ['to', 'subject', 'message'];
        foreach ($required as $key) {
            if (empty($payload[$key])) {
                $this->queue_model->markFailed($jobId, "Payload incompleto: falta '$key'");
                return;
            }
        }

        $this->load->library('Swiftmailer', null, 'Swiftmailer');

        // Reconstruir adjuntos si los hay
        $attach = $payload['attach'] ?? null;

        // Regenerar PDF si se guardó la ruta del html y ya no existe el archivo
        if (!empty($payload['pdf_html']) && !empty($payload['pdf_path']) && !file_exists($payload['pdf_path'])) {
            try {
                $mpdf = new \Mpdf\Mpdf();
                $mpdf->WriteHTML($payload['pdf_html']);
                $mpdf->Output($payload['pdf_path'], 'F');
                if ($attach && isset($attach['ruta'])) {
                    $attach['ruta'] = $payload['pdf_path'];
                }
            } catch (Exception $e) {
                log_message('error', "[Queue_worker] No se pudo regenerar PDF: " . $e->getMessage());
            }
        }

        if ($this->Swiftmailer->send_email($payload['to'], $payload['subject'], $payload['message'], null, null, $attach)) {
            $this->queue_model->markDone($jobId);
            log_message('info', "[Queue_worker] Email enviado OK → job #{$jobId} a {$payload['to']}");
        } else {
            $this->queue_model->markFailed($jobId, 'SwiftMailer devolvió false');
        }
    }
}
