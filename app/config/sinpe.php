<?php defined('BASEPATH') OR exit('No direct script access allowed');

// URL interna del servicio SINPE Móvil (ver www/sinpe-service/README.md).
// Corre en el mismo servidor, en localhost — nunca debe apuntar a un dominio
// público. Se puede sobrescribir con la variable de entorno SINPE_SERVICE_URL
// sin tocar este archivo (útil si el servicio corre en otro puerto/host interno).
$config['sinpe_service_url'] = getenv('SINPE_SERVICE_URL') ?: 'http://127.0.0.1:3001';

// Testigo compartido con el servicio Node (SERVICE_TOKEN en su .env). Vacio
// significa sin exigencia; en cuanto el servicio lo define, aca tiene que estar
// el mismo valor o toda llamada responde 401.
$config['sinpe_service_token'] = getenv('SINPE_SERVICE_TOKEN') ?: '';
