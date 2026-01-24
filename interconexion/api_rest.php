<?php
/**
 * API REST para recibir documentos firmados desde el servicio FirmaEC
 * Basado en el proyecto oficial firmadigital-tester
 */

// Iniciar output buffering para capturar cualquier salida no deseada (warnings, notices, etc)
ob_start();

// Desactivar visualización de errores para que no interfieran con la respuesta
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/api_rest_errors.log');

// Recibir JSON del servicio FirmaEC
$json = file_get_contents('php://input');

if ($json != "" && $_SERVER["REQUEST_METHOD"] == "POST") {
    $data = json_decode($json);

    if (!$data) {
        ob_clean(); // Limpiar cualquier salida previa
        echo "ERROR: Invalid JSON";
        exit;
    }

    // Extraer datos del JSON
    $nombreDocumento = isset($data->nombreDocumento) ? $data->nombreDocumento : '';
    $archivoBase64 = isset($data->archivo) ? $data->archivo : '';

    if (empty($nombreDocumento) || empty($archivoBase64)) {
        ob_clean(); // Limpiar cualquier salida previa
        echo "ERROR: Missing required fields";
        exit;
    }

    // Decodificar el archivo
    $archivo = base64_decode($archivoBase64);

    if ($archivo === false) {
        ob_clean(); // Limpiar cualquier salida previa
        echo "ERROR: Invalid base64";
        exit;
    }

    // Extraer información adicional del JSON
    // Los datos del firmante vienen en el objeto certificado[0]
    $certificado = isset($data->certificado) && is_array($data->certificado) && count($data->certificado) > 0
                   ? $data->certificado[0]
                   : null;

    if ($certificado) {
        // Extraer datos del certificado digital
        $cedula = isset($certificado->cedula) ? $certificado->cedula : (isset($data->cedula) ? $data->cedula : '');

        // Concatenar nombre y apellido
        $nombre = '';
        if (isset($certificado->nombre) && isset($certificado->apellido)) {
            $nombre = trim($certificado->nombre . ' ' . $certificado->apellido);
        } elseif (isset($certificado->emitidoPara)) {
            $nombre = $certificado->emitidoPara;
        }

        $institucion = isset($certificado->institucion) ? $certificado->institucion : '';
        $cargo = isset($certificado->cargo) ? $certificado->cargo : '';

        // Usar la fecha de la firma del certificado
        $fecha = isset($certificado->fechaFirma) ? $certificado->fechaFirma : date('Y-m-d H:i:s');

        // Si la fecha viene en formato ISO, convertir a d/m/Y H:i:s
        if (strpos($fecha, '-') !== false && strpos($fecha, ':') !== false) {
            $fechaObj = DateTime::createFromFormat('Y-m-d H:i:s', $fecha);
            if ($fechaObj) {
                $fecha = $fechaObj->format('d/m/Y H:i:s');
            }
        }
    } else {
        // Fallback si no hay certificado (no debería pasar)
        $cedula = isset($data->cedula) ? $data->cedula : '';
        $nombre = isset($data->nombre) ? $data->nombre : '';
        $institucion = isset($data->institucion) ? $data->institucion : '';
        $cargo = isset($data->cargo) ? $data->cargo : '';
        $fecha = isset($data->fecha) ? $data->fecha : date('d/m/Y H:i:s');
    }

    // Log para debug (comentar en producción)
    $log_file = __DIR__ . "/api_rest.log";
    $log_data = date('Y-m-d H:i:s') . " - Documento: " . $nombreDocumento . " Cedula: " . $cedula .
                " Nombre: " . $nombre . " Institucion: " . $institucion . " Cargo: " . $cargo .
                " Fecha: " . $fecha . "\n";
    file_put_contents($log_file, $log_data, FILE_APPEND);

    // Log JSON sin el archivo (para no saturar el log con base64)
    $data_log = clone $data;
    if (isset($data_log->archivo)) {
        $data_log->archivo = "[BASE64_" . strlen($data_log->archivo) . "_BYTES]";
    }
    file_put_contents($log_file, "JSON (sin archivo): " . json_encode($data_log) . "\n", FILE_APPEND);

    // Incluir solo las dependencias necesarias (evitamos incluir ws_firma_digital.php porque tiene código SOAP)
    $ruta_raiz = "..";
    include_once "$ruta_raiz/funciones.php";
    include_once "$ruta_raiz/obtenerdatos.php";
    include_once "$ruta_raiz/include/db/ConnectionHandler.php";
    include_once "$ruta_raiz/include/tx/Tx.php";
    include_once "$ruta_raiz/include/tx/Firma_Digital.php";

    // Función copiada de ws_firma_digital.php para evitar conflictos con SOAP
    function grabar_archivos_firmados_rest($cedula_firmante, $nombre_doc, $archivo, $datos_firmante, $fecha, $institucion, $cargo) {
        $ruta_raiz = "..";
        include_once "$ruta_raiz/config.php";

        $db = new ConnectionHandler($ruta_raiz);
        $db_bodega = new ConnectionHandler($ruta_raiz, "bodega");
        $tx = new Tx($db);

        $radicado = ObtenerDatosRadicado($nombre_doc, $db);

        // Validar que se obtuvo el radicado
        if (!$radicado || !is_array($radicado) || !isset($radicado["usua_rem"])) {
            error_log("ERROR: No se encontró el radicado $nombre_doc");
            return 0;
        }

        $usr = ObtenerDatosUsuario(str_replace("-", "", $radicado["usua_rem"]), $db);

        // Validar que se obtuvo el usuario remitente
        if (!$usr || !is_array($usr) || !isset($usr["usua_codi"])) {
            error_log("ERROR: No se encontró el usuario para el radicado $nombre_doc");
            return 0;
        }

        // Obtener datos del usuario firmante por su cédula para completar institución y cargo
        $usr_firmante = ObtenerDatosUsuario($cedula_firmante, $db, "C"); // "C" indica búsqueda por cédula

        // Si encontramos el usuario firmante, extraer su cargo e institución
        if ($usr_firmante && is_array($usr_firmante)) {
            // Si la institución viene vacía del certificado, usar la dependencia del usuario
            if (empty($institucion) && isset($usr_firmante["depe_nomb"])) {
                $institucion = $usr_firmante["depe_nomb"];
            }
            // Si el cargo viene vacío del certificado, usar el cargo del usuario
            if (empty($cargo) && isset($usr_firmante["carg_nomb"])) {
                $cargo = $usr_firmante["carg_nomb"];
            }
        }

        $arch64 = base64_encode($archivo);
        $archivo5 = md5($arch64);

        $fechadia = substr($fecha, 0, 2);
        $fechames = substr($fecha, 3, 2);
        $fechaanio = substr($fecha, 6, 4);
        $fechahora = substr($fecha, 11, 8);
        $fecha = "$fechaanio-$fechames-$fechadia $fechahora (GMT-5)";

        $nombre = $datos_firmante;
        $datos_firmante = "<table><tr><th>Cédula</th><th>Nombre</th><th>Institución</th><th>Cargo</th><th>Fecha</th></tr>";
        $datos_firmante .= "<tr><td>$cedula_firmante</td><td>$nombre</td><td>$institucion</td><td>$cargo</td><td>$fecha</td></tr></table>";

        $rs_archivo = $db_bodega->query("select func_grabar_archivo(E'$nombre_doc.pdf', E'$arch64') as arch_codi");

        if (!$rs_archivo or $rs_archivo->EOF or (0 + $rs_archivo->fields["ARCH_CODI"]) == 0)
            return 0;

        $arch_codi_firma = 0 + $rs_archivo->fields["ARCH_CODI"];

        $sql = "update radicado set radi_fech_firma='$fecha', radi_tipo_archivo=1, radi_nomb_usua_firma = '$datos_firmante', arch_codi = $arch_codi_firma, arch_codi_firma=$arch_codi_firma where radi_nume_temp = $nombre_doc and (esta_codi=4 or esta_codi=3 or radi_nume_radi=$nombre_doc)";

        $ok = $db->conn->Execute($sql);
        $tx->insertarHistorico($nombre_doc, $usr["usua_codi"], $usr["usua_codi"], "Documento Firmado Electrónicamente", 40);
        $respFirma = $tx->envioElectronicoDocumento($nombre_doc, $usr["usua_codi"]);

        if (!$ok)
            return 0;
        else
            return 1;
    }

    // Llamar a la función para grabar el archivo firmado
    // La función espera: ($cedula_firmante, $nombre_doc, $archivo, $datos_firmante, $fecha, $institucion, $cargo)
    try {
        $resultado = grabar_archivos_firmados_rest(
            $cedula,
            $nombreDocumento,
            $archivo,
            $nombre,
            $fecha,
            $institucion,
            $cargo
        );

        if ($resultado == 1) {
            // Limpiar cualquier warning/notice capturado y devolver solo "OK"
            ob_clean();
            echo "OK";
            file_put_contents($log_file, date('Y-m-d H:i:s') . " - SUCCESS: " . $nombreDocumento . "\n", FILE_APPEND);
        } else {
            ob_clean(); // Limpiar cualquier salida previa
            echo "ERROR: No se pudo guardar el documento";
            file_put_contents($log_file, date('Y-m-d H:i:s') . " - ERROR: Resultado=" . $resultado . " - Documento no encontrado o usuario inválido\n", FILE_APPEND);
        }
    } catch (Exception $e) {
        ob_clean(); // Limpiar cualquier salida previa
        echo "ERROR: " . $e->getMessage();
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - EXCEPTION: " . $e->getMessage() . "\n", FILE_APPEND);
    }
} else {
    ob_clean(); // Limpiar cualquier salida previa
    echo "Invalid Request";
}

// Enviar la salida limpia al cliente
ob_end_flush();
?>
