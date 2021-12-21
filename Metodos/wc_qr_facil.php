<?php

class WC_Qr_Facil extends WC_Payment_Gateway {
	
    /**
     * Constructor de la pasarela de pago
     *
     * @access public
     * @return void
     */
    public function __construct(){
        $this->id					= 'qrfacil3';
        $this->icon					= apply_filters('woocomerce_checkout_icon', "https://serviciopagofacil.syscoop.com.bo/Imagenes/MP/logo_qr.png");
        $this->has_fields			= false;
        $this->method_title			= 'Qr Facil solos';
        $this->method_description	= 'Integración de Woocommerce  a nuestro QRFACIL  solo';
        $this->title = 'QRFACIL';
        
        $this->init_form_fields();
        $this->init_settings();
        // AQUI TIENEN QUE VENIR LO SDTAOS QUE SE NESESITAN  PARA EL COMERCIO
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
        //$this->UrlFactura = $this->settings['UrlFactura'];
        $this->UrlReturn = $this->settings['UrlReturn'];
        
        if (version_compare(WOOCOMMERCE_VERSION, '2.0.0', '>=' )) {
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );
         } else {
            add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
        }
        add_action('woocommerce_receipt_qrfacil', array(&$this, 'receipt_page'));
    }
    
    /**
     * Funcion que define los campos que iran en el formulario en la configuracion
     * de la pasarela de PayU Latam
     *
     * @access public
     * @return void
     */
    function init_form_fields() {
    
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
                'description' => __('URL de la página mostrada después de finalizar el pago. No olvide cambiar su dominio', 'pagofacilcheckout'),
                'default' => __($_SERVER[ 'HTTP_HOST'].'/wp-content/plugins/pluginqr/return.php', 'pagofacilcheckout')),
            'UrlCallBack' => array(
                'title' => __('Url Callback', 'pagofacil_checkout'),
                'type' => 'text',
                'description' => __('URL de la página mostrada después de finalizar el pago. No olvide cambiar su dominio ', 'pagofacilcheckout'),
                'default' => __($_SERVER[ 'HTTP_HOST'].'/wp-content/plugins/pluginqr/callback.php', 'pagofacilcheckout'))
            
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
        echo '<p>'.__('Se ha generado con exito su QR', 'pagofacilcheckout').'</p>';
        echo $this -> generate_checkout_form($order);
    }
    
    /**
     * Metodo cargar datos de qr 
     * * como tenemos los datos aqui haremos las llamdas al servicio de qrFacil de pagofacil  
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

        /// aqui se empezara a encriptar
        // aqui todas estas variables son las que se van a encriptar para poder ingresar al checkout pagofacil 
        $lcPedidoID=$order_id ;
        $lcEmail= $orderdata['datos']['billing']['email'];
        $lnTelefono=$orderdata['datos']['billing']['phone']; ; //$loFormDatos['Celular'] ;
        $lnMonto=$amount;
        $lcParametro1="$this->UrlCallBack";
        $lcParametro2="$this->UrlReturn";
        $lcParametro3="";
        $lcParametro4=trim("");

        // Los Tokens Entregados al comercio via Email
        $tcCommerceID =$this->CommerceID;
        $lcTokenServicio=$this->TokenServicio;
        $lcTokenSecret=$this->TokenSecret;
        // Aquí se concatena todos los datos para crear la firma
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
        // aqui vendra la parte de  login ------------------------------------------------------------------------
        
        // aqui esta haciendo el login para poder ocupar los servicios
            $url = 'https://servicios.qr.com.bo/api/login';
            $laDatos = array("TokenService" => $lcTokenServicio,  "TokenSecret" => $lcTokenSecret , 'UrlCallBack' =>  $this->UrlCallBack  , 'UrlReturn' => $this->UrlReturn );
            $laServicioLogin = wp_remote_post($url, array(
                'headers'     => array('Content-Type' => 'application/json; charset=utf-8'  ),
                'body'        => json_encode($laDatos, true),
                'method'      => 'POST',
                'data_format' => 'body',
            ));
            $response = wp_remote_retrieve_body($laServicioLogin);
            error_log("response--" . json_encode($response));
            $responsetoken = json_decode($response);
            echo '<pre>'; 
            print_r($responsetoken );
            echo '</pre>' ;


            // aqui se hara el tema de la genracion de la transaccion
            error_log("response--values" . json_encode($responsetoken->values));
            $url = 'http://serviciopagofacil.syscoop.com.bo/api/Transaccion/CrearTransaccionDePago';
            $laDatos = array("tnCliente" => 9 ,  
                             //"tnEmpresa" => 61 ,
                             'tcCodigoClienteEmpresa' => 9,
                             "tnMetodoPago" => 4 ,
                             'tnTelefono' => $lnTelefono,
                             "tcFacturaA" => "nombre usuario" ,
                             'tnCiNit' => 123456,
                             "tcNroPago" => $order_id ,
                             'tnMontoClienteEmpresa' => $lnMonto ,
                             'tnMontoClienteSyscoop' => 1 ,
                             "tcPeriodo" => "Checkout" ,
                             'tcImei' => 123456789,
                             "taEntidades" => "1,2,3" 
                            );
            $laServicioTransaccion = wp_remote_post($url, array(
                'headers'     => array('Content-Type' => 'application/json; charset=utf-8' ,   'Authorization' => 'Bearer ' . $responsetoken->values),
                'body'        => json_encode($laDatos, true),
                'method'      => 'POST',
                'data_format' => 'body',
                ));
        
            $response = wp_remote_retrieve_body($laServicioTransaccion);
           // error_log("response--" . json_encode($response));
            $response = json_decode($response);
            echo '<pre>'; 
            echo "resultado tranaccion ";
            print_r($response );
            echo '</pre>' ;

            
        
        error_log("response--values" . json_encode($response->values));
            $url = 'https://servicios.qr.com.bo/api/servicio/generarqr';
            $laDatos = array("tnTransaccionDePago" =>  $response->values  
                            );
            $laServicioLogin = wp_remote_post($url, array(
                'headers'     => array('Content-Type' => 'application/json; charset=utf-8' ,   'Authorization' => 'Bearer ' . $responsetoken->values),
                'body'        => json_encode($laDatos, true),
                'method'      => 'POST',
                'data_format' => 'body',
                ));
        
            $response = wp_remote_retrieve_body($laServicioLogin);
           // error_log("response--" . json_encode($response));
            $response = json_decode($response);
            echo '<pre>'; 
            echo "resultado generar qr ";
            print_r($response );
            echo '</pre>' ;

            //	$tnTokenLogin=$response->token;
            if(isset($response->values)  &&  isset($response->values)   )
            {
                error_log("response--values genearaqr" . json_encode($response->values));
                $laValues=explode(";", $response->values);
            
                //error_log("response--values" . json_encode($laValues));
                $laDatosQr=json_decode($laValues[1]);
                
                $parameters_args = array(
                'tcParametros' => base64_encode($tcParametros),
                'tcCommerceID' => $tcCommerceID,
                'tnImagenQr'=> $laDatosQr->qrImage,
                'PedidoId'=>$order_id,
                'urlreturn'=>$this->UrlReturn
                );
            }else{
                error_log("response--values genearaqr" . json_encode($response->values));
                $parameters_args = array(
                'PedidoId'=>$order_id,
                'urlreturn'=>$this->UrlReturn
                );
            }
            
            
        return $parameters_args;

    }
            
    /**
     * Metodo que genera el formulario con los datos de pago
     * aqui se generara el formulario  que tenda los datos de commerceid y de parametros 
     * que seriviran para ingresar al checkout
     * 
     *
     * @access public
     * @return void
     */
    public function generate_checkout_form($order_id){			
        $parameters_args = $this->get_params_post($order_id);
        $payu_args_array = array();
        
        /*foreach($parameters_args as $key => $value){
            $payu_args_array[] = $key . '=' . $value;
        }
        $params_post = implode('&', $payu_args_array);

        $payu_args_array = array();
        foreach($parameters_args as $key => $value){
          $payu_args_array[] = "<input type='hidden' name='$key' value='$value'/>";
        }*/
        

        //return '<form  action="https://checkout.pagofacil.com.bo/es/pay" method="post" id="pagofacil_checkout_form123"><input style="background:#10d8fb;;border-radius:12px;color:aliceblue;-webkit-text-stroke-width:thin;"  type="submit" id="submit_payu_latam" value="' .__('Pagar', 'pagofacilcheckout').'" /></form>';
        return '<div id="divqrfacil">
                    <center>
                            <h1>QR FACIL </h1>
                        <img id="idimagen" style="width:300px ; height:300px" src="data:image/jpeg;base64 , '.@$parameters_args["tnImagenQr"].'" alt="">
                        <button onclick="descargar()" style="background:#10d8fb;;border-radius:12px;color:aliceblue;-webkit-text-stroke-width:thin;"  > Descargar qr</button>
                        <button  onclick="consultarqr()" style="background:#10d8fb;;border-radius:12px;color:aliceblue;-webkit-text-stroke-width:thin;"  > Consultar estado qr</button>
                        <form action="'.$parameters_args["urlreturn"].'" method="post">
                            <input type="text" name="Idpedido" value="'.$parameters_args["PedidoId"].'">
                            <input type="submit" value="Completar orden" >
                        </form>
                    </center>
                </div>
                <script src="https://code.jquery.com/jquery-3.5.0.min.js" ></script>
            <script>
                function consultarqr()
                {
                    alert("hola mnundo 	lo de consultar qr ");
                    $("#idimagen").css("border-radius","50px");
                }
            
                function descargar()
                {
                    //alert("hola descargar") ;
                    var a = document.createElement("a"); //Create <a>
                    a.href = "data:image/png;base64," + "'.@$parameters_args["tnImagenQr"].'"; //Image Base64 Goes here
                    a.download = "QR.png"; //File name Here
                    a.click(); //Downloaded file

                }

                $("html, body").animate({scrollTop: $("#divqrfacil").offset().top }, 2000);
            </script>';	
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
    function get_api_key() {
        return $this->settings['api_key'];
    }
}