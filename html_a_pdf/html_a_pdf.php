<?php

// Este archivo contiene el codigo necesario para generar los archivos pdf a partir de codigo html, 
// utilizando como plantilla con encabezados y pies de página un archivo pdf.
// requiere las siguientes librerias:
// - TCPDF (descargar internet) - http://www.tecnick.com/public/code/cp_dpage.php?aiocp_dp=tcpdf
// - FPDI (descargar internet) - http://www.setasign.de/products/pdf-php-solutions/fpdi/
// - html2ps (instalar paquete) - Ver en internet html_topdf http://www.rustyparts.com/pdf.php
// - ps2pdf (instalar paquete phostcript)

function html_a_pdf($html, $plantilla, $estado="", $numDocu="", $fechaDocu="", $numPaginas="", $orientacionPagina="V") {

    // file_put_contents("/tmp/variable.txt", $estado."--".$numDocu);
    // Definimos los nombres de los archivos temporales que necesitamos para transformar a pdf
    $tmp_html 	= tempnam("/tmp/", 'HTML-');
    $tmp_ps 	= str_replace("HTML-", 'PS-', $tmp_html);
    $tmp_pdf 	= str_replace("HTML-", 'PDF-', $tmp_html);
    $tmp_pl	= str_replace("HTML-", 'PL-', $tmp_html);

    $tamano_hoja = "-sPAPERSIZE=a4";
    $orientacion = '';
    switch ($orientacionPagina) {
        case "H": // Hoja en orizontal
            $tmp_conf = "psh.conf";
            $orientacion = ' --landscape ';
            break;
        case "S": // Sobre, tamaño mas pequeño y otros márgenes
            $tmp_conf = "pss.conf";
            $orientacion = ' --landscape ';
            $tamano_hoja = "-sPAPERSIZE=a5";
            break;
        case "R": // Reportes (margen de 3 cm)
            $tmp_conf = "psr.conf";
            break;
        case "I": // Letra cursiva, mismo margen que el documento Quipux
            $tmp_conf = "psi.conf";
            break;
        case "A": // Acuerdos
            $tmp_conf = "psa.conf";
            break;
        default: // Documento normal, márgenes según norma INEN
            $tmp_conf 	= "ps.conf"; //Archivo con la configuracion con que se generara el pdf (tamaño de la pagina, margenes, etc.)
        break;
    }
    $tmp_result	= array();

    // Guardamos el string del html y la plantilla en archivos temporales
    file_put_contents($tmp_html, validar_html(base64_decode($html)));
    if (trim($plantilla) != "")
        file_put_contents($tmp_pl, base64_decode($plantilla));

    // Pasamos el codigo html a postscript (formato de impresión)
    // Comprimir o expandir tamaño de letra en el archivo pdf a ser generado
    if($numPaginas!="")
        $numPaginas = $numPaginas/100;
    else
        $numPaginas = 1;

    $cmd = "/usr/bin/html2ps $orientacion -f $tmp_conf  -s $numPaginas -o $tmp_ps $tmp_html 2>&1";
    //echo "<hr>$cmd<hr>";
    exec($cmd, $tmp_result, $retCode);
    //var_dump($tmp_result);

    //pasamos de postscript a pdf (sin encabezados)
    $cmd = "/usr/bin/ps2pdf $tamano_hoja -I -dAutoRotatePages=/All -dAutoFilterColorImages=false -dColorImageFilter=/FlateEncode $tmp_ps '$tmp_pdf' 2>&1";
    //echo "<hr>$cmd<hr>";
    exec($cmd, $tmp_result, $retCode);
    //var_dump($tmp_result);

    if ($orientacionPagina=="S") {
        // Devolvemos el archivo sin poner plantilla
	$resp = base64_encode(file_get_contents($tmp_pdf));
    	unlink($tmp_html);
    	unlink($tmp_ps);
    	unlink($tmp_pdf);
	return $resp;
    }

    // Ponemos la plantilla y numeramos en todos los demás casos
    include "poner_plantilla.php";
    // Inicializamos el pdf
    $pdf = new PDF();

    //Setear datos de estado y numero de documento
    $pdf->estado_docu = $estado;
    $pdf->numero_docu = $numDocu;
    $pdf->fecha_docu = $fechaDocu;
    if ($orientacionPagina == "I") //Si se debe imprimir en cursiva
        $pdf->tipo_letra_estilo = "BI"; // Negritas e Itálicas
    // Tipo de sistema desde donde se genera el pdf
    include ("config.php");
    $pdf->_tipo_sistema = $tipo_sistema;
    // Ponemos a $tmp_pl como plantilla de $tmp_pdf
    if (trim($plantilla) != "")
        $pdf->mezclar_pdf($tmp_pdf, $tmp_pl);
    else
        $pdf->mezclar_pdf($tmp_pdf);

    // borramos los archivos temporales
    unlink($tmp_html);
    unlink($tmp_ps);
    unlink($tmp_pdf);

    if (trim($plantilla) != "")
        unlink($tmp_pl);

    // devolvemos el PDF
    return base64_encode($pdf->Output("documento.pdf", 'S'));
}


// Esta función permite unir varios archivos PDF en uno solo
// recibe un arreglo de archivos en base 64 y retorna un archivo unificado en base 64
// requiere las siguientes librerias:
// - TCPDF (descargar internet) - http://www.tecnick.com/public/code/cp_dpage.php?aiocp_dp=tcpdf
// - FPDI (descargar internet) - http://www.setasign.de/products/pdf-php-solutions/fpdi/

function unir_archivos_pdf($lista_archivos) {
    $tmp_result	= array();

    // Definimos los nombres de los archivos temporales que necesitamos para transformar a pdf
    $tmp_nombre	= tempnam("/tmp/", 'UPDF-');

    $i = 0;
    $archivos = array();
    foreach ($lista_archivos as $archivo_base64) {
        if (trim($archivo_base64) != "") {
            $archivos[] = $tmp_nombre.$i;
            file_put_contents($tmp_nombre.$i, base64_decode($archivo_base64));
            ++$i;
        }
    }
    unlink($tmp_nombre);

    // Unimos los archivos
    include "unir_archivos_pdf.php";
    $pdf = new UnirPDFQ();
    $pdf->unir_pdf($archivos);

    foreach ($archivos as $nombre_archivo) {
        unlink($nombre_archivo);
    }

    // devolvemos el PDF
    return base64_encode($pdf->Output("documento.pdf", 'S'));
}

/*Elimina ciertas etiquetas html que podrían ocacionar errores al generar el pdf */
function validar_html($html) {
    $html = preg_replace(':<input (.*?)type=["\']?(hidden|submit|button|image|reset|file)["\']?.*?>:i', '', $html);
    $html = preg_replace(':<style.*?>.*?</style>:is', '', $html);
    /*$html = preg_replace(':<img.*?>:is', '', $html);*/
    $html = preg_replace(':<!--.*?-->:is', '', $html);
    $html = preg_replace(':<p .*?>:is', '', $html); //aumentado por SC para evitar que se dañen las viñetas al pegar desde OOo
    $html = preg_replace(':<col .*?>:is', '', $html); //aumentado por SC para evitar que se dañen las tablas al pegar desde OOo
    $html = str_replace("</p>", "<br>", $html);
    $html = str_replace("<pre>", "", $html);
    $html = str_replace("</pre>", "", $html);
    $html = str_replace("text-align: left;","text-align: justify;",$html);
    $html = str_replace("text-align:left;","text-align: justify;",$html);
    
    $origen = array("á","é","í","ó","ú","ñ","Á","É","Í","Ó","Ú","Ñ");
    $destino = array("&aacute;","&eacute;","&iacute;","&oacute;","&uacute;","&ntilde;","&Aacute;","&Eacute;","&Iacute;","&Oacute;","&Uacute;","&Ntilde;");
    $html = str_replace($origen, $destino, $html);
    $origen = array("à","è","ì","ò","ù","À","È","Ì","Ò","Ù");
    $destino = array("&agrave;","&egrave;","&igrave;","&ograve;","&ugrave;","&Agrave;","&Egrave;","&Igrave;","&Ograve;","&Ugrave;");
    $html = str_replace($origen, $destino, $html);
    $origen = array("ä","ë","ï","ö","ü","Ä","Ë","Ï","Ö","Ü");
    $destino = array("&auml;","&euml;","&iuml;","&ouml;","&uuml;","&Auml;","&Euml;","&Iuml;","&Ouml;","&Uuml;");
    $html = str_replace($origen, $destino, $html);
/*
            // prepend relative image paths with the default domain and path
            $this->_htmlString = preg_replace(':<img (.*?)src=["\']((?!/)(?!http\://).*?)["\']:i', '<img \\1 src="http://'.$this->defaultDomain.$this->defaultPath.'\\2"', $this->_htmlString);
            // prepend absolute image paths with the default domain
            $this->_htmlString = preg_replace(':<img (.*?)src=["\'](/.*?)["\']:i', '<img \\1 src="http://'.$this->defaultDomain.'\\2"', $this->_htmlString);
*/
    return $html;
}

    include "config.php";
    ini_set("soap.wsdl_cache_enabled", "0");  
    $sServer = new SoapServer("$nombre_servidor/html_a_pdf.wsdl");
    $sServer->addFunction("html_a_pdf");
    $sServer->addFunction("unir_archivos_pdf");
    $sServer->handle();  
?>
