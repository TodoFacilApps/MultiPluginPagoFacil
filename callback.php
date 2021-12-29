<?php




//require_once '../../../wp-blog-header.php';
require_once '../../../wp-load.php';
//require_once './pagofacil-checkout.php';

try {
    	// aqui recolecto los datos 
	if(isset($_REQUEST['PedidoID'])){
		$PedidoID = $_REQUEST['PedidoID'];
	} 
	if(isset($_REQUEST['Estado'])){
		$Estado = $_REQUEST['Estado'];
	} 
	// aqui obtengo el pedido en base al numero del pedido 
	$order = new WC_Order($PedidoID);
	/***
	 * 	1 = PENDIENTE / EN PROCESO
	 * 2 = PAGADO
	 * 3 = REVERTIDO
	 * 4 = ANULADO
	 */
	$var=$Estado;
			switch ($var) {
				case 1:
					$respuesta=array("error" => '0', "status" => 1 , "message"=> "Pago Pendiente" ,  "messageMostrar" =>' ' , "values" =>true );
					$order->update_status( 'pending payment' );
				break;

				case 2:
					$order->update_status( "completed" );
					$respuesta=array("error" =>'0', "status" => 1 , "message"=> "Pago realizado correctamente" ,  "values" => true );
				break;
				case 3:
					$order->update_status( 'refunded' );
					$respuesta=array("error" => '0', "status" => 1 , "message"=> "Pago Revertido" ,  "values" =>true );
					
				break;
				case 4:
					$order->update_status( 'failed' );
					$respuesta=array("error" => '0', "status" => 1 , "message"=> "Pago Anulado" ,"values" =>true );
				break;
				default:
				$respuesta=array("error" => '0', "status" => 1 , "message"=> "Error" ,"values" =>false );
				
			}

		

} catch (Exception $e) {

	$respuesta=array("error" => '0', "status" => 1 , "message"=> "Error try catch".$e->getMessage() ,"values" =>false );
}
echo json_encode($respuesta);



?>