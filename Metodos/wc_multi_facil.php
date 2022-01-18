<?php


class WC_Multi_Facil extends WC_Payment_Gateway {
	
    /**
     * Constructor de la pasarela de pago
     *
     * @access public
     * @return void
     */
    public function __construct(){
        $this->id					= 'pagofacilcheckout';
        $this->icon					= apply_filters('woocomerce_checkout_icon', plugins_url('/img/logo_pago_facil.png', __FILE__));
        $this->has_fields			= false;
        $this->method_title			= 'PagoFacil Checkout ';
        $this->method_description	= 'Integración de Woocommerce a la pasarela de pagos de PagoFacil';
        $this->title = 'PagoFacil Bolivia';
        
        //$this->description = 'Plataforma de pagos seguros en Bolivia';
        
        
        $this->init_form_fields();
        $this->init_settings();
        // AQUI TIENEN QUE VENIR LO DATPS QUE SE NESESITAN  PARA EL COMERCIO
        /**
         *  CommerceID Es el identificador del Comercio para la integración con PagoFacil CheckOut.
         *  TokenServicio Es la llave de integración del comercio la cual se usará para firmar los Datos de Pago.
         *  TokenSecret 
         *  UrlCallBack: https://micomercio.com.bo/CallBack
         *  UrlFactura: https://micomercio.com.bo /GetFactura
         *  UrlReturn: https://micomercio.com.bo/PagoRealizado
         * 
         */
        
        $this->CommerceID = $this->settings['CommerceID'];
        $this->TokenServicio = $this->settings['TokenServicio'];
        $this->TokenSecret = $this->settings['TokenSecret'];
        $this->UrlCallBack = $this->settings['UrlCallBack'];
        //$this->UrlFactura = "" ; // $this->settings['UrlFactura'];
        $this->UrlReturn = $this->settings['UrlReturn'];
        $this->description 	        = $this->settings['description'];
        if (version_compare(WOOCOMMERCE_VERSION, '2.0.0', '>=' )) {
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );
         } else {
            add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
        }
        add_action('woocommerce_receipt_pagofacilcheckout', array(&$this, 'receipt_page'));
    }
    
    /**
     * Funcion que define los campos que iran en el formulario en la configuracion
     * de la pasarela de PayU Latam
     *
     * @access public
     * @return void
     */
    function init_form_fields() {
        /* $this->CommerceID = $this->settings['CommerceID'];
        $this->TokenServicio = $this->settings['TokenServicio'];
        $this->TokenSecret = $this->settings['TokenSecret'];
        $this->UrlCallBack = $this->settings['UrlCallBack'];
        $this->UrlFactura = $this->settings['UrlFactura'];
        $this->UrlReturn = $this->settings['UrlReturn'];
         */
        $this->form_fields = array(
        
            'CommerceID' => array(
                'CommerceID' => __('Commerce ID', 'pagofacilcheckout'),
                'title' => __('Commerce Id', 'pagofacilcheckout'),
                'type'=> 'text',
                'description' => __('credenciales de su empresa.', 'pagofacil_checkout'),
                'default' => __('', 'pagofacilcheckout')),
                    
            'TokenServicio' => array(
                'title' => __('Token Servicio', 'pagofacilcheckout'),
                'type' => 'text',
                'description' => __('Token de Servicio que Pagofacil le dara ', 'pagofacil_checkout')),
            'TokenSecret' => array(
                'title' => __('Token secreto', 'pagofacilcheckout'),
                'type' => 'text',
                'description' => __('Token de secreto que Pagofacil le dara ', 'pagofacil_checkout')),
            'UrlReturn' => array(
                'title' => __('Url Return', 'pagofacil_checkout'),
                'type' => 'text',
                'description' => __('URL de la página mostrada después de finalizar el pago.(puede ocupar una suya ) No olvide cambiar su dominio', 'pagofacilcheckout')  . `<style>  .description{ color: red }  </style>`,
                'default' => __("https://".$_SERVER[ 'HTTP_HOST'].'/wp-content/plugins/MultiPluginPagoFacil/return.php', 'pagofacilcheckout')),
            'UrlCallBack' => array(
                'title' => __('Url Callback', 'pagofacil_checkout'),
                'type' => 'text',
                'description' => __('URL de la página mostrada después de finalizar el pago. No olvide cambiar su dominio ', 'pagofacilcheckout'),
                'default' => __("https://".$_SERVER[ 'HTTP_HOST'].'/wp-content/plugins/MultiPluginPagoFacil/callback.php', 'pagofacilcheckout')),
                                
            'description' => array(
                'description' => __('description', 'pagofacilcheckout'),
                'title' => __('Mensaje al cliente ', 'pagofacilcheckout'),
                'type'=> 'text',
                'description' => __('El mensaje que quieres que se muestre al cliente  en la Página de pago .', 'pagofacil_checkout'),
                'default' => __('Plataforma de pagos seguros en Bolivia ', 'pagofacilcheckout'))

        );
    }
    /**
     * Muestra el fomrulario en el admin con los campos de configuracion del gateway PayU Latam
     * 
     * @access public
     * @return void
     */
    public function admin_options() {
        echo '<h3>'.__('PagoFacil Checkout datos de la empresa', 'Pago Facil Checkout ').'</h3>';
        echo '<table class="form-table">';
        $this -> generate_settings_html();
        echo '</table>';
    }		
    /**
     * Atiende el evento de checkout y genera la pagina con el formularion de pago.
     * Solo para la versiones anteriores a la 2.1.0 de WC
     *
     * @access public
     * @return void
     */
    function receipt_page($order){
        echo '<p>'.__('Gracias por su pedido, de clic en el botón que aparece para continuar el pago la Plataforma segura PagoFacil Checkout', 'pagofacilcheckout').'</p>';
        echo $this -> generate_checkout_form($order);
    }
    
    /**
     * Construye un arreglo con todos los parametros que seran enviados al gateway de PayU Latam
     * * por lo que veo aqui parece que es donde se enciptaran todos los datops para generar el formulario que ingrese al checout 
     * 
     *
     * @access public
     * @return void
     */
    public function get_params_post($order_id){
        global $woocommerce;
        $order = new WC_Order( $order_id );
        $orderdata['datos']=$order->get_data();
        $currency = get_woocommerce_currency();
        $amount = number_format(($order -> get_total()),2,'.','');
        $lcMoneda=2;
        if($order->get_currency()=="USD")
        {
            $lcMoneda=1;
        }
        if($order->get_currency()=="BOB")
        {
            $lcMoneda=2;
        }
        //----------------------------------------------------------------------------------------
        $arrayitem=$order->get_items();
            $ArrayProductos= array();
            $lcTokenServiceAux="";
            $lcTokenSecretAux="";
            $lcCommerceIdAux="";
            $lbBandera=false;
            $lnSerial=0;

            foreach ($arrayitem as $key => $value) {
                $product=$arrayitem[$key]->get_product();
                $lnSerial=$lnSerial+1;
                $fecha=$product->get_date_created();
                $product_detalle=array( 
                                        "Serial"=>$lnSerial,
                                        "Producto" =>  $product->get_name(), 
                                        "LinkPago" => 0 , 
                                        'Cantidad'=>  $value->get_quantity(),

                                        "Precio"=>  $product->get_price() ,  
                                        "Descuento" => 0, 
                                        "Total"=> $value->get_quantity() * $product->get_price() 
                                        );
                array_push($ArrayProductos , $product_detalle );

                if($lnSerial==1){
                    $lnUsuarioVendedor = @get_post_field( 'post_author', $value->get_product_id() );
                    $laUsuario = get_userdata( $lnUsuarioVendedor );
                    if(function_exists('yith_get_vendor'))
                    {
                        $laVendedor = @yith_get_vendor($laUsuario->ID, 'user');
                        if(isset($laVendedor)   &&  $laVendedor->id!=0 ){
                            $lcTokenServiceAux=$laVendedor->tokenservice;
                            $lcTokenSecretAux=$laVendedor->tokensecret;
                            $lcCommerceIdAux=$laVendedor->commerceid;
                            $lbBandera=true;					
                        }
                    }
                        }
                
        }
        //----------------------------------------------------------------------------------------
        /// aqui se empezara a encriptar
        // aqui todas estas variables son las que se van a encriptar para poder ingresar al checkout pagofacil 
        $lcPedidoID=$order_id ;
        $lcEmail= $orderdata['datos']['billing']['email'];
        $lnTelefono=$orderdata['datos']['billing']['phone']; ; //$loFormDatos['Celular'] ;
        $lnMonto=$amount;
        if($lbBandera){
            if($lcCommerceIdAux!="" && $lcTokenServiceAux != ""  &&  $lcTokenSecretAux!= "" ){
                $tcCommerceID =$lcCommerceIdAux;
                $lcTokenServicio=$lcTokenServiceAux;
                $lcTokenSecret=$lcTokenSecretAux;
                }else{
                    $tcCommerceID =$this->CommerceID;
                    $lcTokenServicio=$this->TokenServicio;
                    $lcTokenSecret=$this->TokenSecret;
                }
        // Aquí se concatena todos los datos para crear la firma
        }else{
            $tcCommerceID =$this->CommerceID;
            $lcTokenServicio=$this->TokenServicio;
            $lcTokenSecret=$this->TokenSecret;
        // Aquí se concatena todos los datos para crear la firma
        }
        $lcParametro1="$this->UrlCallBack";
        $lcParametro2="$this->UrlReturn";
        $lcParametro3= json_encode($ArrayProductos);
        $lcParametro4=6;
        // Los Tokens Entregados al comercio via Email
        $lcCadenaAFirmar= "$lcTokenServicio|$lcEmail|$lnTelefono|$lcPedidoID|$lnMonto|$lcMoneda|$lcParametro1|$lcParametro2|$lcParametro3|$lcParametro4" ;
        // aqui se genera la firma  con la variable $lcCadenaAFirmar
        $lcFirma= hash('sha256', $lcCadenaAFirmar);
        // aqui  se concatena de nuevo pero utilizando la firma al comienzo 
        $lcDatosPago="$lcFirma|$lcEmail|$lnTelefono|$lcPedidoID|$lnMonto|$lcMoneda|$lcParametro1|$lcParametro2|$lcParametro3|$lcParametro4" ;
        //Esto es el proceso de encriptacion que ocupa php 
        $lnSizeDatosPago=strlen($lcDatosPago);
        $lcDatosPago=str_pad($lcDatosPago,($lnSizeDatosPago+8-($lnSizeDatosPago%8)), "\0");
        //aqui se genera y se guarda  la variable tcparametros que es un encriptado de los datos
        $tcParametros =  openssl_encrypt($lcDatosPago, "DES-EDE3", $lcTokenSecret ,OPENSSL_ZERO_PADDING) ;
            //aqui estaran los datos que se mandaran al formulario  que ingresara al checkout 
        $parameters_args = array(
            'tcParametros' => base64_encode($tcParametros),
            'tcCommerceID' => $tcCommerceID,
        );
        return $parameters_args;
    }
            
    /**
     * Metodo que genera el formulario con los datos de pago
     * aqui se generara el formulario  que tenda los datos de commerceid y de parametros 
     * que seriviran para ingresar al checkout
     * ¿¿
     *
     * @access public
     * @return void
     */
    public function generate_checkout_form($order_id){			
        $parameters_args = $this->get_params_post($order_id);
        $payu_args_array = array();
        foreach($parameters_args as $key => $value){
            $payu_args_array[] = $key . '=' . $value;
        }
        $params_post = implode('&', $payu_args_array);

        $payu_args_array = array();
        foreach($parameters_args as $key => $value){
          $payu_args_array[] = "<input type='hidden' name='$key' value='$value'/>";
        }
        

        return '<form  action="https://checkout.pagofacil.com.bo/es/pay" method="post" id="pagofacil_checkout_form">' . implode('', $payu_args_array) 
            . '<input style="background:#10d8fb;;border-radius:12px;color:aliceblue;-webkit-text-stroke-width:thin;"  type="submit" id="submit_payu_latam" value="' .__('Pagar', 'pagofacilcheckout').'" /></form>'.'<script> document.getElementById("pagofacil_checkout_form").submit(); </script>';
            
    }
    public function payment_fields(){

        
        echo '<p>'.$this->description.'</p>'.'<iframe src="https://pagofacil.com.bo/online/es/vistametodosdepago/'.$this->TokenServicio.'" frameborder="0" height="300" allowfullscreen=""></iframe>' ;
    }
    
    /**
     * Procesa el pago 
     *
     * @access public
     * @return void
     */
    function process_payment($order_id) {
        global $woocommerce;
        $order = new WC_Order($order_id);
        $woocommerce->cart->empty_cart();
        if (version_compare(WOOCOMMERCE_VERSION, '2.0.19', '<=' )) {
            return array('result' => 'success', 'redirect' => add_query_arg('order',
                $order->id, add_query_arg('key', $order->order_key, get_permalink(get_option('woocommerce_pay_page_id'))))
            );
        } else {
        
            $parameters_args = $this->get_params_post($order_id);
            
            $payu_args_array = array();
            foreach($parameters_args as $key => $value){
                $payu_args_array[] = $key . '=' . $value;
            }
            $params_post = implode('&', $payu_args_array);
        
            return array(
                'result' => 'success',
                'redirect' =>  $order->get_checkout_payment_url( true )
            );
        }
    }
    
    /**
     * Retorna la configuracion del api key
     */

}