<?php defined('BASEPATH') OR exit('No direct script access allowed');

// Cliente OAuth de Google para las dos casillas de correo (envío por SMTP y
// recepción de facturas de compra por IMAP). Pertenece al proveedor del sistema,
// no al negocio que lo usa: el cliente final solo autoriza su cuenta de Gmail
// desde Ajustes, nunca ingresa estas credenciales.
//
// Se pueden sobrescribir por entorno (GOOGLE_MAIL_CLIENT_ID / _SECRET) sin tocar
// este archivo. El redirect que hay que registrar en Google Cloud es
// <base_url>/mailauth/callback.
$config['google_mail_client_id']     = getenv('GOOGLE_MAIL_CLIENT_ID') ?: '';
$config['google_mail_client_secret'] = getenv('GOOGLE_MAIL_CLIENT_SECRET') ?: '';
