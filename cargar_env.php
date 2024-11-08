<?php

/**
 * Función para cargar variables de entorno desde un archivo .env
 *
 * @param string $rutaArchivoEnv Ruta al archivo .env
 */
function cargarVariablesDeEntorno($rutaArchivoEnv) {
    if (!file_exists($rutaArchivoEnv)) {
        // Lanza un error si no encuentra el archivo .env
        throw new Exception("El archivo .env no existe en la ruta especificada: $rutaArchivoEnv");
    }

    // Lee cada línea del archivo .env, ignorando líneas vacías o comentarios
    $lineas = file($rutaArchivoEnv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lineas as $linea) {
        // Ignora las líneas que comienzan con #
        if (strpos(trim($linea), '#') === 0) {
            continue;
        }

        // Separa el nombre y valor de cada variable
        list($nombre, $valor) = explode('=', $linea, 2);
        $nombre = trim($nombre);
        $valor = trim($valor);

        // Define la variable de entorno si no existe ya
        if (!array_key_exists($nombre, $_ENV) && !getenv($nombre)) {
            putenv("$nombre=$valor");
            $_ENV[$nombre] = $valor;
            $_SERVER[$nombre] = $valor;
        }
    }
}

// Llama a la función para cargar las variables de entorno
cargarVariablesDeEntorno('.env');
