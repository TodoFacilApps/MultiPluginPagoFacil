<?php
require_once '../../../wp-blog-header.php';
require_once './pagofacil-checkout.php';
// ESTE CODIGO ES PARA CONFIRMAR EL PAGO Y DARLO COMO PAGADO EN EL WORDPRES
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

	if($Estado==1){
		//pendiente
		$respuesta=array('error' => 0, 'status' => 1 , 'message'=> "Pago Pendiente" ,  'messageMostrar' => 0 , 'values' =>true );
		
		$order->update_status( 'pending payment' );
	}	
	if($Estado==2){
		//pagada
		$order->update_status( 'completed' );
		$respuesta=array('error' => 0, 'status' => 1 , 'message'=> "Pago realizado correctamente" ,  "messageMostrar"=> 0, 'values' =>true );
	}
	if($Estado==3){
		//revertido
		$order->update_status( 'refunded' );

		$respuesta=array('error' => 0, 'status' => 1 , 'message'=> "Pago Revertido" ,  "messageMostrar"=> 0, 'values' =>true );
	}
	if($Estado==4){
		//anulado
		$order->update_status( 'failed' );
		$respuesta=array('error' => 0, 'status' => 1 , 'message'=> "Pago Anulado" ,  "messageMostrar"=> 0,'values' =>true );
	}

	echo json_encode($respuesta);
	
?>