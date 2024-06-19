<?php
//require_once '../../../wp-blog-header.php';
//require_once './pagofacil-checkout.php';

// Incluir WordPress adecuadamente
require_once '../../../wp-load.php';

try {
    // Recolectar los datos del formulario
    $TransaccionDePago = $_REQUEST['TransaccionDePago'] ?? '';

    // Configurar la URL del servicio de consulta de estado de transacción
    $url = 'http://serviciopagofacil.syscoop.com.bo/api/Factura/consultarEstadoTransaccion';

    // Datos a enviar en la solicitud
    $laDatos = array(
        'tnTransaccionDePago' => $TransaccionDePago,
        'tnCliente' => 3859,
        'tnCaller' => 1,
        'tnIdAccion' => 0,
        'tcApp' => 3
    );

    // Realizar la solicitud POST
    $laServicioLogin = wp_remote_post($url, array(
        'headers' => array('Content-Type' => 'application/json; charset=utf-8'),
        'body' => json_encode($laDatos),
        'method' => 'POST',
        'data_format' => 'body',
    ));

    // Obtener la respuesta del servicio
    $response = wp_remote_retrieve_body($laServicioLogin);
    $response = json_decode($response);

    // Log de respuesta para debug
    error_log("response--consultatransaccion " . date("Y-m-d H:i:s") . json_encode($response));

    // Procesar la respuesta según el estado de pago
    $arreglo = array();
    if (isset($response->values)) {
        $estadotigo = $response->values->estadoPago;

        switch ($estadotigo) {
            case 0:
                // Pago realizado correctamente
                $arreglo = array('mensaje' => "Pago Realizado Correctamente", 'tipo' => 0);
                break;
            case 1:
                // Pago incorrecto
                $arreglo = array('mensaje' => $response->message, 'tipo' => 1);
                break;
            case 3:
                // Pago en progreso
                $arreglo = array('mensaje' => $response->message, 'tipo' => 3);
                break;
            default:
                // Estado desconocido
                $arreglo = array('mensaje' => "Estado desconocido", 'tipo' => -1);
        }
    } else {
        // Error en la respuesta del servicio
        $arreglo = array('mensaje' => "Ocurrió un error en la respuesta", 'tipo' => 4);
    }

} catch (\Throwable $th) {
    // Capturar y manejar excepciones
    $arreglo = array(
        'mensaje' => 'Error: ' . $th->getMessage(),
        'tipo' => -1
    );
}

// Devolver la respuesta como JSON
echo json_encode($arreglo);
?>
