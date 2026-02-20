<?php
/**  Programa para el manejo de gestion documental, oficios, memorandus, circulares, acuerdos
*    Desarrollado y en otros Modificado por la SubSecretaría de Informática del Ecuador
*    Quipux    www.gestiondocumental.gov.ec
*------------------------------------------------------------------------------
*    This program is free software: you can redistribute it and/or modify
*    it under the terms of the GNU Affero General Public License as
*    published by the Free Software Foundation, either version 3 of the
*    License, or (at your option) any later version.
*    This program is distributed in the hope that it will be useful,
*    but WITHOUT ANY WARRANTY; without even the implied warranty of
*    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*    GNU Affero General Public License for more details.
*
*    You should have received a copy of the GNU Affero General Public License
*    along with this program.  If not, see http://www.gnu.org/licenses.
*------------------------------------------------------------------------------
**/

// Archivo de configuracion del sistema QUIPUX

// Las contraseñas y datos importantes se ocultan en un archivo ".env".
require_once 'cargar_env.php';

// Configurar zona horaria para Ecuador
date_default_timezone_set('America/Guayaquil');

// Archivo con algunas configuraciones de términos usados en el sistema
$FILE_LOCAL = getenv('FILE_LOCAL');

// Activa la funcionalidad para bloquear el sistema;
// Se lo debe activar cuando se programe un bloqueo del sistema para disminuir las consultas a la BDD
$activar_bloqueo_sistema = getenv('activar_bloqueo_sistema');

//Mejora algunos queries y bloquea algunas funcionalidades para reducir la carga a los servidores
$version_light = getenv('version_light');
$config_numero_meses = getenv('config_numero_meses');
$numeroCaracteresTexto = getenv('numeroCaracteresTexto');
$config_bloquear_acceso_ciudadano = getenv('config_bloquear_acceso_ciudadano');

// Configuracion de la conexion con la BDD
$usuario = getenv('usuario');
$contrasena= getenv('contrasena'); 
$servidor = getenv('servidor');
$driver = getenv('driver');
$db = getenv('db');

$usuario_bodega = getenv('usuario_bodega');
$contrasena_bodega = getenv('contrasena_bodega');
$servidor_bodega = getenv('servidor_bodega');
$db_bodega = getenv('db_bodega');

// Indica si se manejan replicas o conexiones con otras BDD
$replicacion = getenv('replicacion');

// Se definen las mismas variables que en la configuracion por defecto, seguidas por un guion bajo y un nombre que la distinga
// Para utilizar esta funcionalidad se debe enviar el nombre utilizado en las variables como parametro al crear la conexion
// Si se desea se puede ocultar los datos de la conexion en variables del servidor, como en el caso anterior
$usuario_busqueda = getenv('usuario_busqueda');
$contrasena_busqueda = getenv('contrasena_busqueda');
$servidor_busqueda = getenv('servidor_busqueda');
$db_busqueda = getenv('db_busqueda');

$authUser = getenv('authUser');
$authPassword = getenv('authPassword');

//Codigo de aplicacion (en caso de que se manejen varios servidores para distribución de carga)
$appID = getenv('appID');

//Path en donde se guardan los archivos que anexa el ciudadano para petición de uso de QUIPUX con firma digital
$path_ciudadanos = getenv('path_ciudadanos');

//Logs y Mensajes de la aplicacion
//Muestra en pantalla los queries que se ejecutan en la bdd; 0 no muestra ningun mensaje, 1 muestra los errores, 2 muestra todos
$mostrar_logs = getenv('mostrar_logs');
// Graba en la tabla logs de la bdd los queries (inserts y updates) mas importantes ejecutados; 0 no graba nada, 1 graba los errores, 2 graba todos
$grabar_logs = getenv('grabar_logs');
// Graba en una tabla de logs la página invocada y el IP que la invocó (para identificar posibles ataques desde páginas externas o desde páginas de orfeo...)
$grabar_log_paginas_visitadas = getenv('grabar_log_paginas_visitadas');
$grabar_log_full_backup = getenv('grabar_log_full_backup');

//Email del Super Administrador del Sistema QUIPUX
$amd_email = getenv('amd_email');
// email de la cuenta de soporte
$cuenta_mail_soporte = getenv('cuenta_mail_soporte');
// email de la cuenta desde la que se enviarán los recordatorios a los usuarios
$cuenta_mail_envio = getenv('cuenta_mail_envio');

// Configuración para la conexión con otros servidores adicionales
$nombre_servidor = getenv('nombre_servidor');
$nombre_servidor_reportes = getenv('nombre_servidor_reportes'); // en caso que los reportes se lo quiera enviar a un servidor diferente
$nombre_servidor_respaldos = getenv('nombre_servidor_respaldos'); // en caso que los respaldos se requiera sacar en un servidor diferente

$servidor_firma = getenv('servidor_firma');
$servidor_viajes = getenv('servidor_viajes');
$servidor_pdf = getenv('servidor_pdf');

//Numero Meses en Reportes
$numeroMeses = getenv('numeroMeses');
//path de descarga del archivo
$path_acuerdo = getenv('path_acuerdo');
//Acceso para Institución de Ciudadanos
$acceso_ciudadano_inst = getenv('acceso_ciudadano_inst');
//Tipo de Documentos de Ciudadanos
$tipo_doc_ciudadano = getenv('tipo_doc_ciudadano');
//Número de días de vigencia para descarga de archivos de respaldos
$dias_descarga = getenv('dias_descarga');
//Correo para recibir notificaciones de respaldos para soporte
$cuenta_mail_respaldo = getenv('cuenta_mail_respaldo');
$versionEstable = getenv('versionEstable');//version de firefox menores a 17 es soportada
$api_key_token = getenv('api_key_token');
$sistema_firma = getenv('sistema_firma');
//$api_key_firma = getenv('api_key_firma');
$swEnvioArchivoFirmaConfig = getenv('swEnvioArchivoFirmaConfig');
$url_api_firma = getenv('url_api_firma');
//Determina posición tamano y ambiente Solo para ambiente de pruebas debe estar en 1
//Posición de la firma al pie
$centrado = getenv('centrado');
$derecha = getenv('derecha');
$izquierda = getenv('izquierda');
$parametrosFirmaConfig = getenv('parametrosFirmaConfig');
$parametrosFirmaConfig = rawurlencode($parametrosFirmaConfig);

?>
