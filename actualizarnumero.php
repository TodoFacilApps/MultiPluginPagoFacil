<?php
require_once '../../../wp-load.php';

try {
    // Recolectar los datos del formulario
    if (isset($_REQUEST['tnTelefono'])) {
        // Iniciar la sesión si no está iniciada
        if (!session_id()) {
            session_start();
        }

        // Obtener y guardar el número de teléfono en la sesión
        $tnTelefono = $_REQUEST['tnTelefono'];
        $_SESSION['tnTelefono'] = $tnTelefono;

        // Devolver el número de teléfono como respuesta (opcional)
        echo $_SESSION['tnTelefono'];
    }

} catch (Exception $e) {
    // Capturar y manejar excepciones si es necesario
    // No se está haciendo nada específico aquí, pero podrías añadir un manejo de errores según tus necesidades.
}
?>
