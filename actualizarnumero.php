<?php

require_once '../../../wp-load.php';

try {
    	// aqui recolecto los datos 
	
	if(isset($_REQUEST['tnTelefono'])){
        if (!session_id()) {
            session_start();
        }
   		$tnTelefono = $_REQUEST['tnTelefono'];
        $_SESSION['tnTelefono'] = $tnTelefono ; // A string
        
	} 


		

} catch (Exception $e) {
}




?>