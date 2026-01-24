<?php
/**
 * Script de prueba para detectar coordenadas de firma
 * Uso: test_coordenadas.php?pdf=bodega/tmp/123456.pdf
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Incluir la clase
require_once __DIR__ . '/include/tx/Coordenadas_Firma.php';

// Obtener ruta del PDF
$pdf_relativo = isset($_GET['pdf']) ? $_GET['pdf'] : '';

if (empty($pdf_relativo)) {
    die("Uso: test_coordenadas.php?pdf=bodega/tmp/ARCHIVO.pdf");
}

// Construir ruta completa
$pdf_path = __DIR__ . '/' . $pdf_relativo;

echo "<h2>Test de Detección de Coordenadas</h2>";
echo "<p><strong>Archivo:</strong> $pdf_relativo</p>";
echo "<p><strong>Ruta completa:</strong> $pdf_path</p>";

// Verificar que existe
if (!file_exists($pdf_path)) {
    die("<p style='color:red;'>ERROR: El archivo no existe</p>");
}

echo "<p style='color:green;'>✓ Archivo encontrado</p>";
echo "<hr>";

// Crear instancia y detectar
$detector = new CoordenadasFirma();
$resultado = $detector->detectar_posicion_firma($pdf_path);

// Mostrar resultados
echo "<h3>Resultados:</h3>";
echo "<pre>";
print_r($resultado);
echo "</pre>";

if ($resultado['encontrado']) {
    echo "<p style='color:green; font-size:18px;'><strong>✓ MARCADOR ENCONTRADO</strong></p>";
    echo "<p><strong>Coordenadas detectadas:</strong></p>";
    echo "<ul>";
    echo "<li>LLX (X): {$resultado['llx']}</li>";
    echo "<li>LLY (Y): {$resultado['lly']}</li>";
    echo "<li>Página: {$resultado['pagina']}</li>";
    echo "</ul>";

    // Mostrar URL que se generaría
    $params = $detector->formatear_para_firma($resultado);
    echo "<p><strong>Parámetros para FirmaEC:</strong></p>";
    echo "<code style='background:#f0f0f0; padding:10px; display:block;'>$params</code>";
} else {
    echo "<p style='color:red; font-size:18px;'><strong>✗ MARCADOR NO ENCONTRADO</strong></p>";
    echo "<p>Valores por defecto usados (llx=null, lly=null)</p>";
}

echo "<hr>";

// Intentar extraer y mostrar el XML de pdftotext para debugging
echo "<h3>Debug: Contenido XML de pdftotext</h3>";

$tmp = tempnam("/tmp", "PDF-TEST-");
$cmd = "pdftotext -bbox " . escapeshellarg($pdf_path) . " " . escapeshellarg($tmp) . " 2>&1";
exec($cmd, $out, $code);

if ($code === 0 && file_exists($tmp)) {
    $xml_content = file_get_contents($tmp);
    unlink($tmp);

    // Mostrar primeras 2000 caracteres del XML
    echo "<p>Primeros 2000 caracteres del XML:</p>";
    echo "<textarea style='width:100%; height:300px; font-family:monospace; font-size:11px;'>";
    echo htmlspecialchars(substr($xml_content, 0, 2000));
    echo "</textarea>";

    // Buscar el marcador manualmente en el XML
    if (stripos($xml_content, "Documento firmado") !== false) {
        echo "<p style='color:green;'>✓ La frase 'Documento firmado' SÍ aparece en el XML</p>";
    } else {
        echo "<p style='color:red;'>✗ La frase 'Documento firmado' NO aparece en el XML</p>";
    }
} else {
    echo "<p style='color:red;'>Error al ejecutar pdftotext</p>";
    if (file_exists($tmp)) unlink($tmp);
}
?>
