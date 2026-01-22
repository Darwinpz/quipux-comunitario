<?php
    // Detectar automáticamente el servidor y protocolo
    $protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $base_path = dirname(dirname($_SERVER['SCRIPT_NAME'])); // Obtiene /quipux-comunitario

    $nombre_servidor = "$protocolo://$host$base_path/html_a_pdf";
    $tipo_sistema = "Produccion";
?>
