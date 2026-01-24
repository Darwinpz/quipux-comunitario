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

/*******************************************************************************
** Clase que detecta coordenadas dinámicas para posicionar el QR de firma     **
** digital en el lugar correcto del documento PDF                             **
*******************************************************************************/

class CoordenadasFirma {

    private $marcador = "Documento firmado electrónicamente";  // Patrón único que aparece en documentos de firma
    private $ancho_qr = 70;  // Ancho del QR en puntos PDF (aprox 2.5cm)
    private $alto_qr = 70;   // Alto del QR en puntos PDF (aprox 2.5cm)

    /**
     * Detecta la posición del marcador de firma en un PDF y calcula
     * dónde debe ubicarse el QR de firma digital
     *
     * @param string $pdf_path Ruta del archivo PDF generado
     * @return array Coordenadas ['llx' => x, 'lly' => y, 'pagina' => n, 'encontrado' => bool]
     */
    public function detectar_posicion_firma($pdf_path) {
        // Valores por defecto si no se encuentra el marcador
        $resultado = [
            'llx' => 100,          // Alineado a la izquierda por defecto
            'lly' => 200,          // Posición vertical por defecto
            'pagina' => 1,         // Primera página por defecto
            'encontrado' => false
        ];

        if (!file_exists($pdf_path)) {
            error_log("CoordenadasFirma: Archivo PDF no encontrado: $pdf_path");
            // Agregar coordenadas URX y URY calculadas
            $resultado['urx'] = $resultado['llx'] + $this->ancho_qr;
            $resultado['ury'] = $resultado['lly'] + $this->alto_qr;
            return $resultado;
        }

        try {
            // Extraer texto del PDF y buscar el marcador
            $coordenadas = $this->extraer_texto_y_buscar_marcador($pdf_path);

            if ($coordenadas !== false) {
                $resultado = $coordenadas;
                $resultado['encontrado'] = true;
            }

        } catch (Exception $e) {
            error_log("CoordenadasFirma: Error al procesar PDF: " . $e->getMessage());
        }

        // Calcular coordenadas URX y URY (esquina superior derecha del rectángulo)
        $resultado['urx'] = $resultado['llx'] + $this->ancho_qr;
        $resultado['ury'] = $resultado['lly'] + $this->alto_qr;

        return $resultado;
    }

    /**
     * Extrae el texto del PDF usando pdftotext y busca el marcador
     * Calcula las coordenadas aproximadas basándose en la posición del texto
     *
     * @param string $pdf_path Ruta del PDF
     * @return array|false Coordenadas si encuentra el marcador, false si no
     */
    private function extraer_texto_y_buscar_marcador($pdf_path) {
        // Usar pdftotext para extraer texto con información de posición
        // Formato: pdftotext -layout -bbox archivo.pdf

        $temp_xml = tempnam("/tmp/", 'PDF-');

        // Extraer texto en formato XML con coordenadas de bounding boxes
        $cmd = "pdftotext -bbox " . escapeshellarg($pdf_path) . " " . escapeshellarg($temp_xml) . " 2>&1";
        exec($cmd, $output, $return_code);

        if ($return_code !== 0 || !file_exists($temp_xml)) {
            // Si pdftotext no está disponible, intentar método alternativo
            unlink($temp_xml);
            return $this->metodo_alternativo_buscar_marcador($pdf_path);
        }

        $xml_content = file_get_contents($temp_xml);
        unlink($temp_xml);

        // Buscar el marcador en el XML
        if (strpos($xml_content, $this->marcador) !== false) {
            // Parsear XML para obtener coordenadas exactas
            $coordenadas = $this->parsear_coordenadas_xml($xml_content);
            if ($coordenadas !== false) {
                return $coordenadas;
            }
        }

        // Si no encuentra con este método, usar alternativo
        return $this->metodo_alternativo_buscar_marcador($pdf_path);
    }

    /**
     * Parsea el XML generado por pdftotext para encontrar coordenadas del marcador
     *
     * @param string $xml_content Contenido XML
     * @return array|false Coordenadas calculadas
     */
    private function parsear_coordenadas_xml($xml_content) {
        try {
            $xml = simplexml_load_string($xml_content);
            if (!$xml) return false;

            // Buscar el elemento que contiene el marcador
            foreach ($xml->page as $page_num => $page) {
                foreach ($page->word as $word) {
                    $texto = (string)$word;
                    if (strpos($texto, $this->marcador) !== false) {
                        // Encontrado! Extraer coordenadas
                        $xMin = (float)$word['xMin'];
                        $yMin = (float)$word['yMin'];
                        $xMax = (float)$word['xMax'];
                        $yMax = (float)$word['yMax'];

                        // Calcular posición para el QR (alineado a la izquierda)
                        // Alineado con el margen izquierdo del texto del firmante
                        $llx = 100; // Margen izquierdo típico de documentos oficiales (100-120 puntos)

                        // pdftotext -bbox usa Y=0 arriba, PDF usa Y=0 abajo
                        // Necesitamos convertir: lly_pdf = altura_pagina - y_pdftotext
                        $alto_pagina = 842;

                        // El texto está en yMin (desde arriba en pdftotext)
                        // Convertimos a coordenadas PDF y posicionamos QR encima
                        $lly_texto_desde_abajo = $alto_pagina - $yMin;

                        // Colocamos el QR justo encima del texto (valores menores = más abajo)
                        // Agregamos espacio para que quede entre "Atentamente," y el texto
                        $lly = round($lly_texto_desde_abajo + 10); // 10 pts de separación

                        return [
                            'llx' => $llx,
                            'lly' => $lly,
                            'pagina' => $page_num + 1
                        ];
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Error parseando XML: " . $e->getMessage());
        }

        return false;
    }

    /**
     * Método alternativo usando extracción simple de texto
     * Estima la posición basándose en el número de líneas y longitud del documento
     *
     * @param string $pdf_path Ruta del PDF
     * @return array|false Coordenadas estimadas
     */
    private function metodo_alternativo_buscar_marcador($pdf_path) {
        // Extraer texto simple
        $temp_txt = tempnam("/tmp/", 'TXT-');
        $cmd = "pdftotext " . escapeshellarg($pdf_path) . " " . escapeshellarg($temp_txt) . " 2>&1";
        exec($cmd, $output, $return_code);

        if ($return_code !== 0 || !file_exists($temp_txt)) {
            if (file_exists($temp_txt)) unlink($temp_txt);
            return false;
        }

        $texto = file_get_contents($temp_txt);
        unlink($temp_txt);

        // Buscar el marcador
        if (strpos($texto, $this->marcador) === false) {
            return false;
        }

        // Dividir en páginas (separadas por form feed \f)
        $paginas = explode("\f", $texto);
        $pagina_encontrada = 0;
        $posicion_en_pagina = 0;

        foreach ($paginas as $num_pag => $contenido_pag) {
            if (strpos($contenido_pag, $this->marcador) !== false) {
                $pagina_encontrada = $num_pag + 1;

                // Calcular línea aproximada donde está el marcador
                $lineas_antes = substr_count($contenido_pag, "\n", 0, strpos($contenido_pag, $this->marcador));
                $posicion_en_pagina = $lineas_antes;
                break;
            }
        }

        if ($pagina_encontrada > 0) {
            // Estimar coordenadas basándose en la línea
            // A4: 842 puntos de alto, márgenes ~70 arriba y abajo = ~700 puntos útiles
            // Aprox 40-50 líneas por página = ~14-17 puntos por línea
            $alto_pagina = 842;
            $margen_superior = 70;
            $puntos_por_linea = 17;

            // Calcular Y (desde abajo hacia arriba en PDF)
            $y_desde_arriba = $margen_superior + ($posicion_en_pagina * $puntos_por_linea);
            // El QR debe ir ENCIMA del texto "Documento firmado electrónicamente"
            // Sumamos espacio adicional para que quede entre "Atentamente," y el texto
            $lly = $alto_pagina - $y_desde_arriba + 20; // +20 puntos para ubicar encima

            // Alinear a la izquierda (mismo margen que el texto del firmante)
            $llx = 100; // Margen izquierdo típico de documentos oficiales

            return [
                'llx' => $llx,
                'lly' => max(50, $lly), // No bajar más de 50 puntos del borde inferior
                'pagina' => $pagina_encontrada
            ];
        }

        return false;
    }

    /**
     * Limpia el marcador del PDF antes de enviarlo a firmar
     * Nota: Como usamos el texto "Documento firmado electrónicamente" como marcador,
     * no es necesario limpiarlo del PDF ya que es parte natural del documento
     *
     * @param string $pdf_path Ruta del PDF
     * @return bool true siempre (método mantenido por compatibilidad)
     */
    public function limpiar_marcador($pdf_path) {
        // No es necesario limpiar el texto "Documento firmado electrónicamente"
        // ya que es parte legítima del documento
        return true;
    }

    /**
     * Formatea las coordenadas para enviar al servicio de FirmaEC
     *
     * @param array $coordenadas Array con llx, lly, pagina
     * @return string Parámetros formateados para la URL
     */
    public function formatear_para_firma($coordenadas) {
        $llx = isset($coordenadas['llx']) ? $coordenadas['llx'] : 250;
        $lly = isset($coordenadas['lly']) ? $coordenadas['lly'] : 200;

        return "&llx=$llx&lly=$lly&estampado=QR&razon=firmaEC";
    }
}
?>
