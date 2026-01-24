<?php
/**
 * Clase que detecta las coordenadas reales (X, Y) de un marcador de texto
 * dentro de un PDF usando pdftotext -bbox
 *
 * IMPORTANTE:
 * - Retorna coordenadas NATIVAS del PDF
 * - No calcula offsets
 * - No considera tamaño de QR
 * - No invierte eje Y
 */

class CoordenadasFirma {

    private $marcador = "bmvQr";

    /**
     * Detecta la posición del marcador en el PDF
     *
     * @param string $pdf_path
     * @return array ['llx'=>X, 'lly'=>Y, 'pagina'=>N, 'encontrado'=>bool]
     */
    public function detectar_posicion_firma($pdf_path) {

        $resultado = [
            'llx' => null,
            'lly' => null,
            'pagina' => 1,
            'encontrado' => false
        ];

        if (!file_exists($pdf_path)) {
            error_log("PDF no encontrado: $pdf_path");
            return $resultado;
        }

        $coords = $this->extraer_texto_y_buscar_marcador($pdf_path);

        if ($coords !== false) {
            return [
                'llx' => $coords['llx'],
                'lly' => $coords['lly'],
                'pagina' => $coords['pagina'],
                'encontrado' => true
            ];
        }

        return $resultado;
    }

    /**
     * Ejecuta pdftotext -bbox y procesa el XML
     */
    private function extraer_texto_y_buscar_marcador($pdf_path) {

        $tmp = tempnam("/tmp", "PDF-");

        $cmd = "pdftotext -bbox " . escapeshellarg($pdf_path) . " " . escapeshellarg($tmp) . " 2>&1";
        exec($cmd, $out, $code);

        if ($code !== 0 || !file_exists($tmp)) {
            error_log("pdftotext -bbox falló");
            if (file_exists($tmp)) unlink($tmp);
            return false;
        }

        $xml = file_get_contents($tmp);
        unlink($tmp);

        return $this->parsear_coordenadas_xml($xml);
    }

    /**
     * Busca el marcador por línea y devuelve X,Y reales del PDF
     */
    private function parsear_coordenadas_xml($xml_content) {

        $xml = simplexml_load_string($xml_content);
        if (!$xml || !isset($xml->page)) return false;

        foreach ($xml->page as $page_num => $page) {
            if (!isset($page->word)) continue;

            $linea = '';
            $xInicio = null;
            $yLinea = null;

            foreach ($page->word as $word) {

                $texto = trim((string)$word);
                $x = (int)$word['xMin'];
                $y = (int)$word['yMin'];

                // Si iniciamos línea
                if ($linea === '') {
                    $linea = $texto;
                    $xInicio = $x;
                    $yLinea = $y;
                }
                // Misma línea (misma Y con tolerancia de 3 puntos)
                elseif (abs($yLinea - $y) < 3) {
                    $linea .= ' ' . $texto;
                }
                // Cambió de línea
                else {
                    $linea = $texto;
                    $xInicio = $x;
                    $yLinea = $y;
                }

                // ¿Encontramos el marcador? (búsqueda flexible)
                // Buscar tanto con acento como sin acento por si hay problemas de encoding
                if (stripos($linea, $this->marcador) !== false ||
                    stripos($linea, "Documento firmado electronicamente") !== false) {

                    return [
                        'llx' => $xInicio,
                        'lly' => $yLinea,
                        'pagina' => $page_num + 1
                    ];
                }
            }
        }

        return false;
    }

    /**
     * Formato mínimo para FirmaEC
     */
    public function formatear_para_firma($coordenadas) {

        if (!$coordenadas['encontrado']) {
            return "";
        }

        $llx = $coordenadas['llx'];
        $lly = $coordenadas['lly'];

        return "&llx=$llx&lly=$lly&estampado=QR&razon=firmaEC";
    }
}
