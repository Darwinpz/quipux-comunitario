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
        if (!$xml) return false;

        // pdftotext -bbox envuelve el XML en HTML: <html><body><doc><page>
        // Intentar acceder a través de la estructura HTML
        $pages = null;
        if (isset($xml->body->doc->page)) {
            $pages = $xml->body->doc->page;
        } elseif (isset($xml->page)) {
            $pages = $xml->page;
        }

        if (!$pages) return false;

        $num_pagina = 1; // Contador manual de página
        foreach ($pages as $page) {
            if (!isset($page->word)) {
                $num_pagina++;
                continue;
            }

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
                    // ANTES de cambiar de línea, verificar si la línea actual tiene el marcador
                    if (stripos($linea, $this->marcador) !== false) {
                        return [
                            'llx' => $xInicio,
                            'lly' => $yLinea,
                            'pagina' => $num_pagina
                        ];
                    }

                    // Ahora sí, cambiar a nueva línea
                    $linea = $texto;
                    $xInicio = $x;
                    $yLinea = $y;
                }

                // También verificar después de agregar palabras a la línea actual
                if (stripos($linea, $this->marcador) !== false) {
                    return [
                        'llx' => $xInicio,
                        'lly' => $yLinea,
                        'pagina' => $num_pagina
                    ];
                }
            }

            // Verificar la última línea procesada de la página
            if ($linea !== '' && stripos($linea, $this->marcador) !== false) {
                return [
                    'llx' => $xInicio,
                    'lly' => $yLinea,
                    'pagina' => $num_pagina
                ];
            }

            $num_pagina++; // Incrementar contador para siguiente página
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
