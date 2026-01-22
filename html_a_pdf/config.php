<?php
    // Detectar automáticamente el servidor y protocolo
    $protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];

    // Obtener la ruta base usando la ubicación de este archivo config.php
    // __FILE__ = /path/to/quipux-comunitario/html_a_pdf/config.php
    $config_dir = dirname(__FILE__); // /path/to/quipux-comunitario/html_a_pdf
    $project_root = dirname($config_dir); // /path/to/quipux-comunitario

    // Obtener la ruta web del proyecto (eliminar document root)
    $document_root = $_SERVER['DOCUMENT_ROOT'];
    $web_path = str_replace($document_root, '', $project_root);
    $web_path = str_replace('\\', '/', $web_path); // Normalizar barras en Windows

    $nombre_servidor = "$protocolo://$host$web_path/html_a_pdf";
    $tipo_sistema = "Produccion";
?>
