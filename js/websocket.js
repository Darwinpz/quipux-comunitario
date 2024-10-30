/**
 * *       Programa para recibir peticiones de archivos por firmar, sirve para consultar el estado del documento si está firmado o no.
 * *       Código basado de: https://www.flynsarmy.com/2012/02/php-websocket-chat-application-2-0/
 * *       Desarrollado y modificado por la Subsecretaría de Gobierno Electrónico del Ecuador
 * *------------------------------------------------------------------------------
 * *    This program is free software: you can redistribute it and/or modify
 * *    it under the terms of the GNU Affero General Public License as
 * *    published by the Free Software Foundation, either version 3 of the
 * *    License, or (at your option) any later version.
 * *    This program is distributed in the hope that it will be useful,
 * *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 * *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * *    GNU Affero General Public License for more details.
 * *
 * *    You should have received a copy of the GNU Affero General Public License
 * *    along with this program.  If not, see http://www.gnu.org/licenses. 
 * *------------------------------------------------------------------------------
 * **/

/**
 * *       Modificado por          Iniciales               Fecha (dd/mm/aaaa)
 * *       David Gamboa            DG                      15-11-2017
 * *       josedavo@gmail.com
 * *
 * *       Comentado por           Iniciales               Fecha (dd/mm/aaaa)
 * *       David Gamboa            DG                      15-11-2017
 * **/
		

/*Propio de Quipux para abrir la conexión con la aplicación de firma*/
function token(tokencer,tipo_certificado,radicados,api_key_token){
      url = 'firmaec://'+api_key_token+'/firmar?token='+tokencer+'&tipo_certificado='+tipo_certificado+'&llx=222&lly=85&urx=422&ury=49&pre=true';
      windowFirma=window.open(url, 'Firma Electrónica', 'addressbar=no,toolbar=0,scrollbars=0,location=no,statusbar=0,menubar=0,resizable=0,width=500px,height=250px,left = 390,top = 100');
      setTimeout("windowFirma.close()", 10000);
      radicadosGlobal = radicados;
      intentos = 1;
    }
