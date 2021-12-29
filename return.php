<?php
//require_once '../../../wp-blog-header.php';

require_once '../../../wp-load.php';
require_once './pagofacil-checkout.php';
get_header();

	if(isset($_REQUEST['Idpedido'])){
		$PedidoID = $_REQUEST['Idpedido'];
	}
	// aqui obtendo los datos de la orden y le mando el numero del pedido 
	$order = new WC_Order($PedidoID);
	// aqui obtego los datos del pago 
	$estadopago=$order->get_status();
	//aqui guardo lo  datos del pedido 
	$orderdata['datos']=$order->get_data();


	if($estadopago=='pending'){
		//pendiente
		$mensajedepago="El Pedido esta pendiente ";
	}	
	if($estadopago== 'completed'){
		//pagada
		$mensajedepago="El Pedido  se ha realizado exito  ";
		
	}
	if($estadopago=='refunded'){
		//revertido
		$mensajedepago="El Pedido se ha revertido ";
		
	}
	if($estadopago=='failed'){
		//anulado
		$mensajedepago="El Pedido se ha anulado";
		
	}
	if($estadopago=='processing'){
		//anulado
		$mensajedepago="El Pedido se esta procesando";
		
	}
?>
	<center>
	<H1><?= @$mensajedepago ?></H1>
		<table style="width: 42%; margin-top: 100px;">
			<tr align="center">
				<th colspan="2">DATOS DE LA COMPRA</th>
			</tr>
			<tr align="right">
				<td>Estado de la transacci&oacute;n</td>
				<td><?php echo $estadopago; ?></td>
			</tr>
			<tr align="right">
				<td>Numero del Pedido</td>
				<td><?php echo @$PedidoID; ?></td>
			</tr>		
				
		
			<tr align="right">
				<td>Valor total</td>
				<td> <?php echo $amount = number_format(($order -> get_total()),2,'.',''); ?> </td>
			</tr>
			
		</table>
		
	</center>

	<?php





get_footer();
?>