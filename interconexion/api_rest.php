<?php
/**
 * API REST para recibir documentos firmados desde el servicio FirmaEC
 * Basado en el proyecto oficial firmadigital-tester
 */

// Recibir JSON del servicio FirmaEC
$json = file_get_contents('php://input');

if ($json != "" && $_SERVER["REQUEST_METHOD"] == "POST") {
    $data = json_decode($json);

    if (!$data) {
        echo "ERROR: Invalid JSON";
        exit;
    }

    // Extraer datos del JSON
    $nombreDocumento = isset($data->nombreDocumento) ? $data->nombreDocumento : '';
    $archivoBase64 = isset($data->archivo) ? $data->archivo : '';

    if (empty($nombreDocumento) || empty($archivoBase64)) {
        echo "ERROR: Missing required fields";
        exit;
    }

    // Decodificar el archivo
    $archivo = base64_decode($archivoBase64);

    if ($archivo === false) {
        echo "ERROR: Invalid base64";
        exit;
    }

    // Extraer información adicional del JSON
    $cedula = isset($data->cedula) ? $data->cedula : '';
    $nombre = isset($data->nombre) ? $data->nombre : '';
    $institucion = isset($data->institucion) ? $data->institucion : '';
    $cargo = isset($data->cargo) ? $data->cargo : '';
    $fecha = isset($data->fecha) ? $data->fecha : date('d/m/Y H:i:s');

    // Log para debug (comentar en producción)
    $log_file = __DIR__ . "/api_rest.log";
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - Documento: " . $nombreDocumento . " Cedula: " . $cedula . "\n", FILE_APPEND);

    // Incluir solo las dependencias necesarias (evitamos incluir ws_firma_digital.php porque tiene código SOAP)
    $ruta_raiz = "..";
    include_once "$ruta_raiz/funciones.php";
    include_once "$ruta_raiz/obtenerdatos.php";
    include_once "$ruta_raiz/include/db/ConnectionHandler.php";
    include_once "$ruta_raiz/include/tx/Tx.php";
    include_once "$ruta_raiz/include/tx/Firma_Digital.php";

    // Función copiada de ws_firma_digital.php para evitar conflictos con SOAP
    function grabar_archivos_firmados_rest($usuario, $nombre_doc, $archivo, $datos_firmante, $fecha, $institucion, $cargo) {
        $ruta_raiz = "..";
        include_once "$ruta_raiz/config.php";

        $db = new ConnectionHandler($ruta_raiz);
        $db_bodega = new ConnectionHandler($ruta_raiz, "bodega");
        $tx = new Tx($db);

        $radicado = ObtenerDatosRadicado($nombre_doc, $db);
        $usr = ObtenerDatosUsuario(str_replace("-", "", $radicado["usua_rem"]), $db);

        $arch64 = base64_encode($archivo);
        $archivo5 = md5($arch64);

        $fechadia = substr($fecha, 0, 2);
        $fechames = substr($fecha, 3, 2);
        $fechaanio = substr($fecha, 6, 4);
        $fechahora = substr($fecha, 11, 8);
        $fecha = "$fechaanio-$fechames-$fechadia $fechahora (GMT-5)";

        $nombre = $datos_firmante;
        $datos_firmante = "<table><tr><th>Cédula</th><th>Nombre</th><th>Institución</th><th>Cargo</th><th>Fecha</th></tr>";
        $datos_firmante .= "<tr><td>$usuario</td><td>$nombre</td><td>$institucion</td><td>$cargo</td><td>$fecha</td></tr></table>";

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
    // La función espera: ($usuario, $nombre_doc, $archivo, $datos_firmante, $fecha, $institucion, $cargo)
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
            echo "OK";
            file_put_contents($log_file, date('Y-m-d H:i:s') . " - SUCCESS: " . $nombreDocumento . "\n", FILE_APPEND);
        } else {
            echo "ERROR: No se pudo guardar el documento";
            file_put_contents($log_file, date('Y-m-d H:i:s') . " - ERROR: Resultado=" . $resultado . "\n", FILE_APPEND);
        }
    } catch (Exception $e) {
        echo "ERROR: " . $e->getMessage();
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - EXCEPTION: " . $e->getMessage() . "\n", FILE_APPEND);
    }
} else {
    echo "Invalid Request";
}
?>
