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
      
      
        $url = 'http://serviciopagofacil.syscoop.com.bo/api/Transaccion/getTransaccionDePago';
        $laDatos = array(  
                        'tnTransaccion' => $TransaccionDePago ,
                         'tnCliente' => 3859   ,
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
            $Estado =$valor->Estado; 
            	/*0= correcto
							1=incorrecto
							3=en progreso*/
							if($Estado==2)
							{
								// se hizo el pago correctamente
							 //----------------------------------------------------
                                $arreglo=array('mensaje' => "Pago  Realizado correctamente", 'tipo' => 2 );

							}
							if($Estado==1)
							{
								//se hizo el pago incorrectamente
							//	$this->servicios->finalizarpago($tncliente ,$tnTransaccionDePago);
								$arreglo=array('mensaje' => "Pago Pendiente", 'tipo' => 1 );

							}
							if($Estado==4)
							{
								//sigue 
								$arreglo=array('mensaje' => "Pago anulado", 'tipo' => 4 );

							}

        }else{
            $arreglo=array('mensaje' => "sigue", 'tipo' => 1 );
        }

      
        
    } catch (\Throwable $th) {
        //throw $th;
        $arreglo=array("Linea"  =>$th->getLine() ,"Mensaje"  =>$th->getMessage()    );
    }

	echo json_encode($arreglo);

	
?>