<?php

class WC_Tigo_Facil extends WC_Payment_Gateway {
	
    /**
     * Constructor de la pasarela de pago
     *
     * @access public
     * @return void
     */
    public function __construct(){
        $this->id					= 'tigomoney';
        $this->icon					= apply_filters('woocomerce_checkout_icon',"https://serviciopagofacil.syscoop.com.bo/Imagenes/MP/tigo-money.png");
        $this->has_fields			= false;
        $this->method_title			= 'TigoMoney';
        $this->method_description	= 'Integración de Woocommerce para pagar mediante el método de pago TigoMoney';
        $this->title = 'TigoMoney';
        
        $this->init_form_fields();
        $this->init_settings();
        
        // Configuración de los campos necesarios para la integración
        $this->CommerceID = $this->settings['CommerceID'];
        $this->TokenServicio = $this->settings['TokenServicio'];
        $this->TokenSecret = $this->settings['TokenSecret'];
        $this->UrlCallBack = $this->settings['UrlCallBack'];
        $this->UrlReturn = $this->settings['UrlReturn'];
        
        // Manejo de opciones de administración dependiendo de la versión de WooCommerce
        if (version_compare(WOOCOMMERCE_VERSION, '2.0.0', '>=' )) {
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
         } else {
            add_action( 'woocommerce_update_options_payment_gateways', array( $this, 'process_admin_options' ) );
        }
        
        add_action('woocommerce_receipt_tigomoney', array( $this, 'receipt_page' ));
    }
    
    /**
     * Función que define los campos que irán en el formulario en la configuración
     * de la pasarela de TigoMoney
     *
     * @access public
     * @return void
     */
    function init_form_fields() {
    
        $this->form_fields = array(
        
            'CommerceID' => array(
                'title' => __('Commerce ID', 'tigofacil'),
                'type'=> 'text',
                'description' => __('ID del comercio para la integración con PagoFacil CheckOut.', 'tigofacil'),
                'default' => '',
            ),
            'TokenServicio' => array(
                'title' => __('Token Servicio', 'tigofacil'),
                'type' => 'text',
                'description' => __('Token de Servicio proporcionado por PagoFacil.', 'tigofacil'),
            ),
            'TokenSecret' => array(
                'title' => __('Token secreto', 'tigofacil'),
                'type' => 'text',
                'description' => __('Token secreto proporcionado por PagoFacil.', 'tigofacil'),
            ),
            'UrlReturn' => array(
                'title' => __('Url Return', 'tigofacil'),
                'type' => 'text',
                'description' => __('URL a la que se redirige después de finalizar el pago.', 'tigofacil'),
                'default' => 'https://' . $_SERVER['HTTP_HOST'] . '/wp-content/plugins/MultiPluginPagoFacil/return.php',
            ),
            'UrlCallBack' => array(
                'title' => __('Url Callback', 'tigofacil'),
                'type' => 'text',
                'description' => __('URL de callback para recibir notificaciones de PagoFacil.', 'tigofacil'),
                'default' => 'https://' . $_SERVER['HTTP_HOST'] . '/wp-content/plugins/MultiPluginPagoFacil/callback.php',
            ),
            
        );
    }

    
    /**
     * Muestra el formulario en el administrador con los campos de configuración del gateway
     * TigoMoney
     * 
     * @access public
     * @return void
     */
    public function admin_options() {
        echo '<h3>'.__('PagoFacil Checkout datos de la empresa', 'Pago Facil Checkout ').'</h3>';
        echo '<table class="form-table">';
        $this->generate_settings_html();
        echo '</table>';
    }
   
    

    public function payment_fields(){
        echo '<p> Se enviará un código de confirmación al número de teléfono </p>' ;
    }

    /**
     * Atiende el evento de checkout y genera la página con el formulario de pago.
     * Solo para versiones anteriores a la 2.1.0 de WooCommerce
     *
     * @access public
     * @return void
     */
    function receipt_page($order){
        echo '<p>'.__('Se creó con éxito su pedido, ingrese el PIN en su número para confirmar el pago', 'tigofacil').'</p>';
        echo $this->generate_checkout_form($order);
    }
    
    /**
     * Método para obtener los parámetros necesarios para el pago con TigoMoney
     * 
     * @param int $order_id ID del pedido
     * @param int $nuevotelefono (Opcional) Nuevo número de teléfono
     * @return array Arreglo con los parámetros para enviar al formulario de pago
     */
    public function get_params_posttigo($order_id, $nuevotelefono=0 ){
        global $woocommerce;
  
        try {
            if (!session_id()) {
                session_start();
            }
            
            $order = new WC_Order( $order_id );
            $orderdata['datos'] = $order->get_data();
            $currency = get_woocommerce_currency();
            $amount = number_format(($order->get_total()), 2, '.', '');
            $lcMoneda = 2;
            if ($order->get_currency() == "USD") {
                $lcMoneda = 1;
            }
            if ($order->get_currency() == "BOB") {
                $lcMoneda = 2;
            }
            $lcNombreCliente = @$orderdata['datos']['billing']['first_name'] . " " . @$orderdata['datos']['billing']['last_name'];
            ////----------------------------
            $arrayitem = $order->get_items();
            $ArrayProductos = array();
            $lcTokenServiceAux = "";
            $lcTokenSecretAux = "";
            $lcCommerceIdAux = "";
            $lbBandera = false;
            $lnSerial = 0;
            foreach ($arrayitem as $key => $value) {
                $product = $arrayitem[$key]->get_product();
                $lnSerial = $lnSerial + 1;
                $fecha = $product->get_date_created();
                $product_detalle = array( 
                    "Serial"=>$lnSerial,
                    "Producto" =>  $product->get_name(), 
                    "LinkPago" => 0 , 
                    'Cantidad'=>  $value->get_quantity(),
                    "Precio"=>  $product->get_price() ,  
                    "Descuento" => 0, 
                    "Total"=> $value->get_quantity() * $product->get_price() 
                );
                array_push($ArrayProductos , $product_detalle );
            }
            //--------------------------------
            /// aquí se empezará a encriptar
            // aquí todas estas variables son las que se van a encriptar para poder ingresar al checkout pagofacil 
            $lcPedidoID = $order_id ;
            $lcEmail = $orderdata['datos']['billing']['email'];
            
            if (isset( $_SESSION['tnTelefono'])  &&  $_SESSION['tnTelefono'] != 0 ) {
                $lnTelefono = $_SESSION['tnTelefono'] ; //$loFormDatos['Celular'] ;
                $_SESSION['tnTelefono'] = 0;
            } else {
                $lnTelefono = $orderdata['datos']['billing']['phone'];  //$loFormDatos['Celular'] ;
                //echo "ingreso por false session";
            }
    
            $lnMonto = $amount;
            $lcParametro1 = "$this->UrlCallBack";
            $lcParametro2 = "$this->UrlReturn";
            $lcParametro3 = json_encode($ArrayProductos);
            $lcParametro4 = trim("");
    
            // Los Tokens Entregados al comercio via Email
            $tcCommerceID = $this->CommerceID;
            $lcTokenServicio = $this->TokenServicio;
            $lcTokenSecret = $this->TokenSecret;
            // Aquí se concatena todos los datos para crear la firma
            $lcCadenaAFirmar = "$lcTokenServicio|$lcEmail|$lnTelefono|$lcPedidoID|$lnMonto|$lcMoneda|$lcParametro1|$lcParametro2|$lcParametro3|$lcParametro4" ;
            // aquí se genera la firma  con la variable $lcCadenaAFirmar
            $lcFirma = hash('sha256', $lcCadenaAFirmar);
            // aquí  se concatena de nuevo pero utilizando la firma al comienzo 
            $lcDatosPago = "$lcFirma|$lcEmail|$lnTelefono|$lcPedidoID|$lnMonto|$lcMoneda|$lcParametro1|$lcParametro2|$lcParametro3|$lcParametro4" ;
            //echo " antes de enviar " .  $lcCadenaAFirmar . "<br>";
            // Aquí enviamos el datosPago al checkout de pagofacil 
            return $lcDatosPago ;
        } catch (Exception $e) {
            echo 'Excepción capturada: ',  $e->getMessage(), "\n";
        }
    }

   
    /**
     * Genera el formulario de pago de TigoMoney.
     *
     * @param int $order_id ID del pedido
     * @return string HTML del formulario de pago
     */
    public function generate_checkout_form($order_id){
        global $woocommerce;
        
        $order = new WC_Order( $order_id );
        $params = $this->get_params_posttigo($order_id);
        $datos =  explode("|" , $params );
        $orderdata['datos'] = $order->get_data();
        $total = $orderdata['datos']['total'];
        $amount = number_format(($order->get_total()), 2, '.', '');
        ?>
            <style>
            .mf-plazo-pago-l{
                width: 100%;
                display: block;
                float: left;
                margin-bottom: 10px;
                background: white;
                color: black;
                border-radius: 5px;
                margin: 0px;
                border: 2px solid;
                padding: 10px;
                border-color: black;
            }
            </style>

           <?php  $this->the_payment_form($order_id); ?>
             </br>

                    <div class="mf-checkout-item">
                        <span class="mf-checkout-label">Tigo Money PagoFacil:</span>
                        <div class="mf-item-info">
                        </div>
                    </div>
        
        <?php
    }
    /**
     * La estructura a de pagofacil checkout de solicitud
     * al guardarse el procesar se ha comprobado la llamada
     */

    public function afterbhooks_checkout_tigo( ) {
        global $woocommerce;
        // si el mensaje es de la opción que se espera un mensaje
        if(isset($_GET['message'])){
            
        }
    }
    function the_payment_form($order_id)
    {
        global $woocommerce;
    }
    
} 
