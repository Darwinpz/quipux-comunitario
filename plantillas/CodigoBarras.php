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
session_start();
$ruta_raiz = "..";
include_once "$ruta_raiz/rec_session.php";
if ($nuevo=="no") {
    $verrad = $valRadio;
    if (!strlen(trim ($valRadio))){
        echo "<link rel='stylesheet' href='$ruta_raiz/estilos/orfeo.css'>";

        include_once "$ruta_raiz/funciones_interfaz.php";
        $mensajeError = "<html>".html_head();
        $mensajeError .= "<center><br><table class='borde_tab' width=100% CELSPACING=5>
                            <tr class=titulosError>
                                <td align='center'>No hay Documento seleccionado para realizar la Impresi&oacute;n
                                </td>
                            </tr>
                            <tr>
                                <td align='center'><input type='button' value='Regresar' onClick='history.back();' name='enviardoc' class='botones' id='Cancelar'></td>
                            </tr>
                        </table></center></body>
                        </html>";
        die ($mensajeError);
    	//die ("<table class='borde_tab' width=100% CELSPACING=5><tr class=listado1><td><h2>No hay Documentos seleccionados para la impresión de comprobante</h2></td><td><A class=vinculos HREF='javascript:history.back();'>Regresar</A></td></tr></table>");
    }
}

function br_style($numero=0){
    $br="";
    for($i=0;$i<$numero;$i++)
        $br.="<br>&nbsp;";
        //echo $br;
    return $br;
}
function strtoupper2($cadena) {
    $cadena = strtoupper($cadena);
    $cadena = str_replace("á","Á",str_replace("é","É",str_replace("í","Í",str_replace("ó","Ó",str_replace("ú","Ú",str_replace("ñ","Ñ",$cadena))))));
    return $cadena;
}
//echo  "br".br_style(10)."aca";die();
include "$ruta_raiz/include/barcode/index.php";
include "$ruta_raiz/class_control/class_gen.php";
include "$ruta_raiz/obtenerdatos.php";

$registro = ObtenerDatosRadicado($verrad,$db);
//$tmp2 = "";
//$usr_login = "";
foreach (explode('-',$registro["usua_rem"]) as $tmp) {
    if (trim($tmp)!="") {
	$usr = ObtenerDatosUsuario($tmp,$db);
	$usr_login = substr($usr["login"],1);
//	$usr_login .= $tmp2 . "C" . $usr["cedula"];
//	$tmp2 = " - ";
    }
}
$institucion = ObtenerDatosInstitucion($registro["inst_actu"],$db);
$usr = ObtenerDatosUsuario($registro["usua_radi"],$db);
//$gen_fecha = new CLASS_GEN();
//$date = substr(ObtenerCampoRadicado("radi_fech_radi",$verrad,$db),0,10);
//$fecha = $gen_fecha->traducefecha($date);
//$fecha = trim(substr($fecha,strpos($fecha,",")+1));
$date = ObtenerCampoRadicado("radi_fech_radi",$verrad,$db);
$fecha = substr($date,0,19)." GMT ".substr($date,-3);

//$tamano_papel = "a4";
//$orientacion_papel = "portrait";
$imagenPDF = str_replace('../bodega/tmp/','',$file).".png";
$imagenPDFS = str_replace('../bodega/tmp/','',$file).".ps";
$inicio = '
<html>
<head>

<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
</head>

<body   >
';

if ($tipo_comp==1 || $tipo_comp==0) {
    $codigo_barras.="<table  align='right'  width='60%'>";
    $codigo_barras.="<tr><td  width='60%'></td><td>";
    $codigo_barras.="<table border='0' width='40%' align='right'>";
   
    $codigo_barras.= '<tr><td align="left">'.$institucion["nombre"].'</td></tr>';
    //$codigo_barras .='<tr><td align="right"><img src="'.$imagenPDF.'" height="30" width="200" type="image/png" ></td></tr>';
    $codigo_barras.= '<tr><td align="left">'.$registro["radi_nume_text"].'</td></tr>';

    $codigo_barras.="</table>";
    $codigo_barras.="</td></tr>";
    $codigo_barras.="</table>";
    $tipo_formato = "B";
 
}


if ($tipo_comp==2 || $tipo_comp==0) {
    //$comprobante.= br_style(30);
    $sizeBody = "0.9";
    $comprobante.="<table border='0' width='100%' align='right' cellpadding='0'>";
    $comprobante.='<tr><td colspan="2" align="left" ><font size="0.0008">'.strtoupper2($institucion["nombre"]).'</font></td></tr>';
    if (trim($institucion['telefono'])!='')
        $comprobante.='<font size="0.0008"> / Teléfono(s):'.$institucion['telefono'].'</font>';
    $comprobante.='</td></tr>';
    $comprobante.='<tr><td width="40%"><font size="'.$sizeBody.'">Documento No.:</font></td><td><font size="'.$sizeBody.'">'.$registro["radi_nume_text"].'</font></td></tr>';
    $comprobante.='<tr><td><font size="'.$sizeBody.'">Fecha:</font></td><td><font size="'.$sizeBody.'">'.$fecha.'</font></td></tr>';
    $comprobante.='<tr><td><font size="'.$sizeBody.'">Recibido por:</font></td><td><font size="'.$sizeBody.'">'.$usr["nombre"].'</font></td></tr>';    
    $comprobante.='<tr><td colspan="2" align="left"><font size="'.$sizeBody.'">Para verificar el estado de su documento ingrese a: '.$nombre_servidor.' </font></td></tr>';
    $comprobante.='<tr><td colspan="2" align="left"><font size="'.$sizeBody.'">con el usuario:'.$usr_login.'</font></td></tr>';
    
    
    $comprobante.='</table>';
    $tipo_formato = "C";
}
//Se imprime el comprobante en ticket
if ($tipo_comp==3 || $tipo_comp==0) {

  
    $sizeBody = "0.9";
    $comprobante.='<center><table align="center" border="0" cellspacing="0"  cellpadding="0" rowspancing="0" width="65%" >';
    $comprobante.='<tr><td colspan="2" align="left" ><font size="0.0008">'.strtoupper2($institucion["nombre"]).'</font></td></tr>';
    if (trim($institucion['telefono'])!='')
        $comprobante.='<font size="0.0008"> / Teléfono(s):'.$institucion['telefono'].'</font>';
    $comprobante.='</td></tr>';
    $comprobante.='<tr><td><font size="'.$sizeBody.'">Documento No.:</font></td><td><font size="'.$sizeBody.'">'.$registro["radi_nume_text"].'</font></td></tr>';
    $comprobante.='<tr><td><font size="'.$sizeBody.'">Fecha:</font></td><td><font size="'.$sizeBody.'">'.$fecha.'</font></td></tr>';
    $comprobante.='<tr><td><font size="'.$sizeBody.'">Recibido por:</font></td><td><font size="'.$sizeBody.'">'.$usr["nombre"].'</font></td></tr>';
    $comprobante.='<tr><td colspan="2"><font size="'.$sizeBody.'">Para verificar el estado de su documento ingrese a:</font></td></tr>';
    $comprobante.='<tr><td colspan="2" align="center"><font size="'.$sizeBody.'">'.$nombre_servidor.'</font></td></tr>'; 
    $comprobante.='<tr><td colspan="2" align="center"><font size="'.$sizeBody.'">con el usuario:'.$usr_login.'</font></td></tr>';
    
    
    $comprobante.='</table></center>';
    $tipo_formato = "T";
  
}
$fin = '
 &nbsp;
</body>
</html>
'; 
require_once("$ruta_raiz/interconexion/generar_pdf.php");
$radi_nume=str_replace("/","-",$registro["radi_nume_text"]);
$html = $inicio.$codigo_barras.$comprobante.$fin;

//$html = "<html><head><meta http-equiv='Content-Type' content='text/html; charset=UTF-8'></head><body>$html</body></html>";
file_put_contents("$ruta_raiz/bodega/tmp/$radi_nume.html", $html);

//echo $plantilla;die();
$pdf = ws_generar_pdf($html, "", $servidor_pdf, "", "", "", 100,$tipo_formato);
$path = "/tmp/$radi_nume.pdf";
$path_archivo = "/tmp/$radi_nume.pdf";
$path_descarga = "$ruta_raiz/archivo_descargar.php?path_arch=$path&nomb_arch=comprobante.pdf";
file_put_contents("$ruta_raiz/bodega/$path_archivo", $pdf);
?>
<iframe  name="ifr_descargar_archivo" id="ifr_descargar_archivo" style="display: none" src="<?=$path_descarga?>">
            Su navegador no soporta iframes, por favor actualicelo.</iframe>
<script>
console.log("Comprobante Autorizado");
setTimeout(retroceder, 500); 
function retroceder(){
    window.history.back();
}
</script>
