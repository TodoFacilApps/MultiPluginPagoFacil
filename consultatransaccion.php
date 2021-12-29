<?php
//require_once '../../../wp-blog-header.php';
//require_once './pagofacil-checkout.php';
require_once '../../../wp-load.php';
// ESTE CODIGO ES PARA CONFIRMAR EL PAGO Y DARLO COMO PAGADO EN EL WORDPRES
	// aqui recolecto los datos 
    try {
        //code...
        if(isset($_REQUEST['TransaccionDePago'])){
            $TransaccionDePago = $_REQUEST['TransaccionDePago'];
        } 
      
      
        $url = 'http://serviciopagofacil.syscoop.com.bo/api/Factura/consultarEstadoTransaccion';
        $laDatos = array(  
                        'tnTransaccionDePago' => $TransaccionDePago ,
                        // "tnEmpresa" => $Empresa ,
                         'tnCliente' => 3859   ,
                         'tnCaller' => 1 ,
                         'tnIdAccion' => 0  , 
                         'tcApp'=>3              
                        );
                      
        $laServicioLogin = wp_remote_post($url, array(
            'headers'     => array('Content-Type' => 'application/json; charset=utf-8' ),
            'body'        => json_encode($laDatos, true),
            'method'      => 'POST',
            'data_format' => 'body',
            ));
            
        $response = wp_remote_retrieve_body($laServicioLogin);
        $response = json_decode($response);
        
    } catch (\Throwable $th) {
        //throw $th;
        $response=array("Linea"  =>$th->getLine() ,"Mensaje"  =>$th->getMessage()    );
    }

	echo json_encode($response);

	
?>