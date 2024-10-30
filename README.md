# Proyecto descontinuado

> :warning: No está siendo mantenido, no recibe actualizaciones.    No existe garantía de su funcionamiento ni seguridad. 

# Quipux comunitario

Quipux es un sistema basado en el Sistema de Gestión Documental Orfeo en el año 2007, ha sido modificado y adaptado a las necesidades de Instituciones Públicas para la gestión Documental.
La versión comunitaria ha sido adaptado para uso de GADS, Instituciones, Organizaciones, Companías y Empresas.
Licenciado como software Libre, GNU/GPL para la distribución libre, gratuita además de compartir los conocimientos y modificaciones.

# Soporte

* Se recomienda el uso de un software de protección perimetral
* El Sistema no tiene soporte comunitario.
* Verifique ajustes de seguridad como permisos sobre el sistema operativo, configuración de componentes, etc.

# Garantía

Repudio de garantía" ("Disclaimer of warranty").

No se ofrece ninguna garantía sobre el programa de ninguna clase, expresa o implícita. Usted asume cualquier riesgo referente a la calidad, seguridad y prestaciones del programa. Si el programa se prueba como defectuoso, usted asume el coste de cualquier servicio de reparación o corrección.

# Requerimientos
* S.O -> Centos, Ubuntu, Fedora
* Apache
* PostgreSQL
* Git
* PHP 7.4

# Paquetes
* php
* php-soap
* php-pdo
* php-pgsql
* html2ps

# Configuraciones adicionales

Remplace por su dominio o ip local


* cp example.config.php config.php
* cp html_a_pdf/example.config.php html_a_pdf/config.php
* cp html_a_pdf/example.html_a_pdf.wsdl html_a_pdf/html_a_pdf.wsdl

# Configuración de base de datos

Crear dos bases de datos (transacional y documental), descargue los scripts desde:

https://minka.gob.ec/quipux-comunitario/scripts_data


# Requerimientos de Hardware
* Almacenamiento de base de datos anual.

* Servidores de Base de datos

| Tipo | Disco | Memoria GB | Procesador Ghz |
| ------ | ------ | ------ | ------ |
| Transaccional | 30 | 6 | 3.4 | 
| Documental | 8GB | 6 | 2.4 | 

* Servidor Web
Disco 30Gb
Memoria 6Gb
Procesador 3.4 Ghz


Memoria y Procesador son requerimientos mínimos.

# Integración de firmaEC
* Edite el archivo config.php y modifique lo siguiente

Generado en firma
$api_key_token="appquipux"

Edite el archivo include/tx/Tx.php

CONSUMO DE SERVICIO WEB RES, la url es proporcionada de firma

$urlws = "http://segun la configuracion del servicio de firma";


