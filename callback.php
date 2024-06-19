<?php
//require_once '../../../wp-blog-header.php';
require_once '../../../wp-load.php';
//require_once './pagofacil-checkout.php';

try {
    // Recolectar los datos del formulario
    $PedidoID = $_REQUEST['PedidoID'] ?? '';
    $Estado = $_REQUEST['Estado'] ?? '';

    // Verificar si se recibieron los datos esperados
    if (empty($PedidoID) || empty($Estado)) {
        throw new Exception('PedidoID o Estado no están definidos');
    }

    // Obtener el objeto de pedido de WooCommerce
    $order = wc_get_order($PedidoID);

    // Actualizar el estado del pedido según el valor de Estado
    switch ($Estado) {
        case 1:
            $order->update_status('pending');
            $respuesta = array(
                'error' => 0,
                'status' => 1,
                'message' => 'Pago Pendiente',
                'values' => true
            );
            break;
        case 2:
            $order->payment_complete();
            $respuesta = array(
                'error' => 0,
                'status' => 1,
                'message' => 'Pago realizado correctamente',
                'values' => true
            );
            break;
        case 3:
            $order->update_status('refunded');
            $respuesta = array(
                'error' => 0,
                'status' => 1,
                'message' => 'Pago Revertido',
                'values' => true
            );
            break;
        case 4:
            $order->update_status('failed');
            $respuesta = array(
                'error' => 0,
                'status' => 1,
                'message' => 'Pago Anulado',
                'values' => true
            );
            break;
        default:
            $respuesta = array(
                'error' => 0,
                'status' => 1,
                'message' => 'Estado desconocido',
                'values' => false
            );
    }

} catch (Exception $e) {
    // Capturar y manejar excepciones
    $respuesta = array(
        'error' => 1,
        'status' => 0,
        'message' => 'Error: ' . $e->getMessage(),
        'values' => false
    );
}

// Devolver la respuesta como JSON
echo json_encode($respuesta);
?>
