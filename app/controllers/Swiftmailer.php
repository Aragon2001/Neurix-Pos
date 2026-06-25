<?php defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . '../vendor/autoload.php';

class Swiftmailer extends MY_Controller {

    public function send_email($to, $subject, $body, $from = NULL, $from_name = NULL, $attachment = NULL, $cc = NULL, $bcc = NULL)
    {
        $Settings = $this->site->getSettings();

        $transport = (new Swift_SmtpTransport($Settings->smtp_host, $Settings->smtp_port, $Settings->smtp_crypto))
            ->setUsername($Settings->smtp_user)
            ->setPassword($Settings->smtp_pass);

        $mailer = new Swift_Mailer($transport);

        $message = (new Swift_Message($subject))
            ->setFrom([$Settings->default_email => $Settings->site_name])
            ->setTo($to)
            ->setContentType('text/html; charset=UTF-8')
            ->setSubject($subject)
            ->setBody($body);

        if ($attachment && is_array($attachment)) {
            foreach ($attachment as $key => $value) {
                $doc = new \DOMDocument();
                $doc->loadXml($value);
                $tmpPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $key . '.xml';
                $doc->save($tmpPath);
                $message->attach(Swift_Attachment::fromPath($tmpPath));
            }
        }

        return (bool) $mailer->send($message);
    }
}
