<?php
//require_once '../../../wp-blog-header.php';
require_once '../../../wp-load.php';
//require_once './pagofacil-checkout.php';

get_header();

if (isset($_REQUEST['Idpedido'])) {
    $PedidoID = $_REQUEST['Idpedido'];
}

// Obtener datos del pedido
$order = wc_get_order($PedidoID);

// Obtener estado del pago
$estadopago = $order->get_status();

// Mensaje según el estado de pago
switch ($estadopago) {
    case 'pending':
        $mensajedepago = "El Pedido está pendiente";
        break;
    case 'completed':
        $mensajedepago = "El Pedido se ha realizado exitosamente";
        break;
    case 'refunded':
        $mensajedepago = "El Pedido se ha revertido";
        break;
    case 'failed':
        $mensajedepago = "El Pedido se ha anulado";
        break;
    case 'processing':
        $mensajedepago = "El Pedido se está procesando";
        break;
    default:
        $mensajedepago = "Estado de pedido desconocido";
}

?>
<center>
    <h1><?= $mensajedepago ?></h1>
    <table style="width: 42%; margin-top: 100px;">
        <tr align="center">
            <th colspan="2">DATOS DE LA COMPRA</th>
        </tr>
        <tr align="right">
            <td>Estado de la transacción</td>
            <td><?php echo $estadopago; ?></td>
        </tr>
        <tr align="right">
            <td>Número del Pedido</td>
            <td><?php echo htmlspecialchars($PedidoID); ?></td>
        </tr>
        <tr align="right">
            <td>Valor total</td>
            <td><?php echo number_format($order->get_total(), 2, '.', ''); ?></td>
        </tr>
    </table>
</center>

<?php
get_footer();
?>
