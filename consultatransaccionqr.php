<?php
//require_once '../../../wp-blog-header.php';
//require_once './pagofacil-checkout.php';

// Incluir WordPress adecuadamente
require_once '../../../wp-load.php';

try {
    // Recolectar los datos del formulario
    $TransaccionDePago = $_REQUEST['TransaccionDePago'] ?? '';

    // Configurar la URL del servicio para obtener la transacción de pago
    $url = 'http://serviciopagofacil.syscoop.com.bo/api/Transaccion/getTransaccionDePago';

    // Datos a enviar en la solicitud
    $laDatos = array(
        'tnTransaccion' => $TransaccionDePago,
        'tnCliente' => 3859,
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
        $Estado = $response->values->Estado;

        switch ($Estado) {
            case 2:
                // Pago realizado correctamente
                $arreglo = array('mensaje' => "Pago Realizado correctamente", 'tipo' => 2);
                break;
            case 1:
                // Pago pendiente
                $arreglo = array('mensaje' => "Pago Pendiente", 'tipo' => 1);
                break;
            case 4:
                // Pago anulado
                $arreglo = array('mensaje' => "Pago Anulado", 'tipo' => 4);
                break;
            default:
                // Estado desconocido
                $arreglo = array('mensaje' => "Estado desconocido", 'tipo' => -1);
        }
    } else {
        // No se recibió respuesta válida del servicio
        $arreglo = array('mensaje' => "No se recibió respuesta válida", 'tipo' => -1);
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
