<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
$ruta_raiz = "..";
include_once "$ruta_raiz/config.php";

if ($_SERVER['PHP_AUTH_USER'] != $authUser && $_SERVER['PHP_AUTH_PW'] != $authPassword)
    die('permissions not supported');

$output = array();
$output = shell_exec("df -h $ruta_raiz/bodega/respaldos");

$output = explode("Mounted on",$output);

echo '<table class="borde_tab" border="0" cellpadding="0" cellspacing="3">
          <tr><td colspan="4" align="center"><b>Espacio Disponible en Disco</b></td></tr>';
for ($i=0; $i<count($output) ; ++$i) {
    $output[$i] = preg_replace('/\s\s+/', ' ', $output[$i]);
    $datos = explode(" ", $output[$i]);
    $tag = ($i == 0) ? $tag = "th" : "td";
    echo "<tr><$tag>".$datos[1]."</$tag><$tag>".$datos[2]."</$tag><$tag>".$datos[3]."</$tag><$tag>".$datos[4]."</$tag></tr>";
}
echo "</table>";

?>
