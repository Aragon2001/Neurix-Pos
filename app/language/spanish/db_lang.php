<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$lang['db_invalid_connection_str'] = 'No se pueden determinar los parámetros de conexión a la base de datos con la cadena de conexión indicada.';
$lang['db_unable_to_connect'] = 'No se puede conectar al servidor de base de datos con los parámetros proporcionados.';
$lang['db_unable_to_select'] = 'No se puede seleccionar la base de datos especificada: %s';
$lang['db_unable_to_create'] = 'No se puede crear la base de datos especificada: %s';
$lang['db_invalid_query'] = 'La consulta enviada no es válida.';
$lang['db_must_set_table'] = 'Debe especificar la tabla de la base de datos a usar en la consulta.';
$lang['db_must_use_set'] = 'Debe usar el método "set" para actualizar un registro.';
$lang['db_must_use_index'] = 'Debe especificar un índice para las actualizaciones por lote.';
$lang['db_batch_missing_index'] = 'Una o más filas enviadas para actualización por lote no tienen el índice especificado.';
$lang['db_must_use_where'] = 'No se permiten actualizaciones sin una cláusula "where".';
$lang['db_del_must_use_where'] = 'No se permiten eliminaciones sin una cláusula "where" o "like".';
$lang['db_field_param_missing'] = 'Para obtener los campos se requiere el nombre de la tabla como parámetro.';
$lang['db_unsupported_function'] = 'Esta función no está disponible para el motor de base de datos que está usando.';
$lang['db_transaction_failure'] = 'Fallo en la transacción: Se realizó un rollback.';
$lang['db_unable_to_drop'] = 'No se puede eliminar la base de datos especificada.';
$lang['db_unsupported_feature'] = 'Funcionalidad no soportada por la plataforma de base de datos que está usando.';
$lang['db_unsupported_compression'] = 'El formato de compresión elegido no está soportado por su servidor.';
$lang['db_filepath_error'] = 'No se puede escribir datos en la ruta de archivo indicada.';
$lang['db_invalid_cache_path'] = 'La ruta de caché indicada no es válida o no tiene permisos de escritura.';
$lang['db_table_name_required'] = 'Se requiere el nombre de una tabla para esa operación.';
$lang['db_column_name_required'] = 'Se requiere el nombre de una columna para esa operación.';
$lang['db_column_definition_required'] = 'Se requiere una definición de columna para esa operación.';
$lang['db_unable_to_set_charset'] = 'No se puede establecer el juego de caracteres de la conexión del cliente: %s';
$lang['db_error_heading'] = 'Ocurrió un Error en la Base de Datos';
