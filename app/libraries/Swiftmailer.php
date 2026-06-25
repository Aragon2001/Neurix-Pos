<?php

defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . '../vendor/autoload.php';

class Swiftmailer {

    public function __get($var) {
        return get_instance()->$var;
    }

    public function send_email($to, $subject, $body, $from = NULL, $from_name = NULL, $attachment = NULL, $cc = NULL, $bcc = NULL) {
        $Settings = $this->site->getSettings();

        $transport = (new Swift_SmtpTransport($Settings->smtp_host, $Settings->smtp_port, $Settings->smtp_crypto))
            ->setUsername($Settings->smtp_user)
            ->setPassword($Settings->smtp_pass);

        $mailer = new Swift_Mailer($transport);

        $message = (new Swift_Message($subject))
            ->setFrom([$Settings->default_email => $Settings->site_name])
            ->setTo($to)
            ->setContentType('text/plain; charset=UTF-8')
            ->setSubject($subject);

        if ($attachment) {
            foreach ($attachment as $key => $value) {
                if ($key == 'ruta') {
                    $message->attach(Swift_Attachment::fromPath($value));
                } else {
                    $message->attach(new Swift_Attachment($value, $key . '.xml', 'application/xml'));
                }
            }
        }

        return (bool) $mailer->send($message);
    }

    public function getBody() {
        return '';
    }
}
