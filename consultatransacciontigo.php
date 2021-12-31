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
        error_log("response--consultatransaccion".Date("y-m-d h:m:s") . json_encode($response));
        $valor= $response->values;
        $arreglo=array();
        error_log("response--consultatransaccion2222".Date("y-m-d h:m:s") . json_encode($valor));
        if(isset($valor))
        {
            $estadotigo =$valor->estadoPago; 
            	/*0= correcto
							1=incorrecto
							3=en progreso*/
							if($estadotigo==0)
							{
								// se hizo el pago correctamente
							//	$finalizarpago= $this->servicios->finalizarpago($tncliente ,$tnTransaccionDePago);
								// aqui debo finalizar el pago llamando al servicio finalizar pago 
                                //----------------------------------------------------
                                $url = 'http://serviciopagofacil.syscoop.com.bo/api/Factura/FinalizarPago';
                                $laDatos = array(  
                                                'tnTransaccionDePago' => $TransaccionDePago ,
                                                 'tnCliente' => 9  , 
                                                 'tcApp'=>3              
                                                );
                                              
                                $laServicioFinalizar = wp_remote_post($url, array(
                                    'headers'     => array('Content-Type' => 'application/json; charset=utf-8' ),
                                    'body'        => json_encode($laDatos, true),
                                    'method'      => 'POST',
                                    'data_format' => 'body',
                                    ));
                                    
                                $loServicioFinalizar = wp_remote_retrieve_body($laServicioFinalizar);
                                $response = json_decode($loServicioFinalizar);
                                //----------------------------------------------------
                                $arreglo=array('mensaje' => $response->message, 'tipo' => 0 );

							}
							if($estadotigo==1)
							{
								//se hizo el pago incorrectamente
							//	$this->servicios->finalizarpago($tncliente ,$tnTransaccionDePago);
								$arreglo=array('mensaje' => $response->message, 'tipo' => 1 );

							}
							if($estadotigo==3)
							{
								//sigue 
								$arreglo=array('mensaje' => $response->message, 'tipo' => 3 );

							}
        }else{
            $arreglo=array('mensaje' => "Ocurrio un error ", 'tipo' => 4 );
        }
        
    } catch (\Throwable $th) {
        //throw $th;
        $arreglo=array("Linea"  =>$th->getLine() ,"Mensaje"  =>$th->getMessage()    );
    }

	echo json_encode($arreglo);

	
?>