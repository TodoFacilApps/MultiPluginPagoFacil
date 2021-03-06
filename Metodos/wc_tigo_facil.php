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
        $this->method_description	= 'Integración de Woocommerce  para pagar mediante el metodo de pago TigoMoney ';
        $this->title = 'TigoMoney';
        
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
        add_action('woocommerce_receipt_tigomoney', array(&$this, 'receipt_page'));
       

    }
    
    /**
     * Funcion que define los campos que iran en el formulario en la configuracion
     * de la pasarela de PayU Latamprocess_admin_options
     *
     * @access public
     * @return void
     */
    function init_form_fields() {
    
        $this->form_fields = array(
        
            'CommerceID' => array(
                'CommerceID' => __('Commerce ID', 'tigofacil'),
                'title' => __('Commerce Id', 'tigofacil'),
                'type'=> 'text',
                'description' => __('credenciales de su empresa.', 'pagofacil_checkout'),
                'default' => __('', 'tigofacil')),
            'TokenServicio' => array(
                'title' => __('Token Servicio', 'tigofacil'),
                'type' => 'text',
                'description' => __('Token de Servicio que Pagofacil le dara ', 'pagofacil_checkout')),
            'TokenSecret' => array(
                'title' => __('Token secreto', 'tigofacil'),
                'type' => 'text',
                'description' => __('Token de secreto que Pagofacil le dara ', 'pagofacil_checkout')),
            'UrlReturn' => array(
                'title' => __('Url Return', 'pagofacil_checkout'),
                'type' => 'text',
                'description' => __('URL de la página mostrada después de finalizar el pago. No olvide cambiar su dominio', 'tigofacil'),
                'default' => __('https://'.$_SERVER[ 'HTTP_HOST'].'/wp-content/plugins/MultiPluginPagoFacil/return.php', 'tigofacil')),
            'UrlCallBack' => array(
                'title' => __('Url Callback', 'pagofacil_checkout'),
                'type' => 'text',
                'description' => __('URL de la página mostrada después de finalizar el pago. No olvide cambiar su dominio ', 'tigofacil'),
                'default' => __('https://'.$_SERVER[ 'HTTP_HOST'].'/wp-content/plugins/MultiPluginPagoFacil/callback.php', 'tigofacil'))
            
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
   
    

    public function payment_fields(){
        echo '<p> Se enviara un código de confirmación al número de teléfono </p>' ;
    }

    /**
     * Atiende el evento de checkout y genera la pagina con el formularion de pago.
     * Solo para la versiones anteriores a la 2.1.0 de WC
     *
     * @access public
     * @return void
     */
    function receipt_page($order){
        echo '<p>'.__('Se creo con exito su pedido ,  ingrese el pin en su numero  para confirmar el pago ', 'tigofacil').'</p>';
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

   
    public function get_params_posttigo($order_id, $nuevotelefono=0 ){
        global $woocommerce;
  
      
        try {
            if (!session_id()) {
                session_start();
            }
            
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
        $lcNombreCliente= @$orderdata['datos']['billing']['first_name']." ". @$orderdata['datos']['billing']['last_name'];
        ////----------------------------
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
        }
        //--------------------------------
        /// aqui se empezara a encriptar
        // aqui todas estas variables son las que se van a encriptar para poder ingresar al checkout pagofacil 
        $lcPedidoID=$order_id ;
        $lcEmail= $orderdata['datos']['billing']['email'];
        
        if(isset( $_SESSION['tnTelefono'])  &&  $_SESSION['tnTelefono'] != 0 )
        {
            $lnTelefono=$_SESSION['tnTelefono'] ; //$loFormDatos['Celular'] ;
            $_SESSION['tnTelefono']=0;
        }else{
            $lnTelefono=$orderdata['datos']['billing']['phone'];  //$loFormDatos['Celular'] ;
            //echo "ingreso por false session";
        }

        $lnMonto=$amount;
        $lcParametro1="$this->UrlCallBack";
        $lcParametro2="$this->UrlReturn";
        $lcParametro3= json_encode($ArrayProductos);
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
            $responselogin = wp_remote_retrieve_body($laServicioLogin);
            error_log("response--" . json_encode($responselogin));
            $responsetoken = json_decode($responselogin);
        // aqui esta haciendo el login para poder ocupar los servicios
        // aqui se hara el tema de la genracion de la transaccion
            $url = 'http://serviciopagofacil.syscoop.com.bo/api/Transaccion/CrearTransaccionDePago';
            $laDatos = array("tnCliente" => 9 ,  
                            "tcApp" => 3  ,
                             'tcCodigoClienteEmpresa' => 9,
                             "tnMetodoPago" => 1 ,
                             'tnTelefono' => $lnTelefono,
                             'tcCorreo' => $lcEmail,
                             "tcFacturaA" => $lcNombreCliente , // "nombre usuario" ,
                             'tnCiNit' => 123456,
                             "tcNroPago" => $order_id ,
                             'tnMontoClienteEmpresa' => $lnMonto ,
                             'tnMontoClienteSyscoop' => 1 ,
                             "tcPeriodo" => "Checkout" ,
                             'tcImei' => 123456789,
                             "taEntidades" => "1,2,3",
                             "tcCommerceID" => $this->CommerceID,
                             "tcParametros" => base64_encode($tcParametros),
                              
                            );
        // aqui se ara el ttema de transaccion 
        $tnTransaccionDePago=0;
            // aqui se ra el tema ya de tigo money 
            $url = 'https://serviciostigomoney.pagofacil.com.bo/api/servicio/realizarpagotigomoney';
            $laServicioLogin = wp_remote_post($url, array(
                'headers'     => array('Content-Type' => 'application/json; charset=utf-8' ,   'Authorization' => 'Bearer ' . $responsetoken->values),
                'body'        => json_encode($laDatos, true),
                'method'      => 'POST',
                'data_format' => 'body',
                'timeout'     => 45,
                ));
        
            $response = wp_remote_retrieve_body($laServicioLogin);
            $response = json_decode($response);
            error_log("response--tigo" . json_encode($response));
        // aqui se ara el tema de tigo money 
      
        

            if(isset($response->values)  &&  isset($response->values)     )
            {
                $parameters_args = array(
                'tcParametros' => base64_encode($tcParametros),
                'tcCommerceID' => $tcCommerceID,
                'PedidoId'=>$order_id,
                'TransaccionDePago'=>$response->values ,
                'urlreturn'=>$this->UrlReturn,
                "tnTelefono"=>$lnTelefono
                );
            } else {
                $parameters_args = array(
                    'tcParametros' => base64_encode($tcParametros),
                    'tcCommerceID' => $tcCommerceID,
                    'PedidoId'=>$order_id,
                    'urlreturn'=>$this->UrlReturn,
                    'TransaccionDePago'=> 0 ,
                    "tnTelefono"=>$lnTelefono
                    );
            }

        } catch (\Throwable $th) {
            //throw $th;
            $parameters_args = array(
                'tcParametros' => base64_encode($tcParametros),
                'tcCommerceID' => $tcCommerceID,
                'PedidoId'=>$th->getLine(),
                'urlreturn'=>$th->getMessage(),
                "tnTelefono"=>$lnTelefono
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
        $parameters_args = $this->get_params_posttigo($order_id);
       
      return '<div id="divtigofacil">
                    <center>
                            <div id="content">
                                <h1>Tigo Money  </h1>
                                <div  class="loading">
                                    <img id="imgcarga"  style="width: 100px;" src="https://acegif.com/wp-content/uploads/loading-25.gif" alt="loading" /><br/> 
                                    <label id="lbltexto" for="">La Transaccion esta siendo procesada...</label> 
                                </div>
                            </div>                        
                        <form style="display:none"   action="'.@$parameters_args["urlreturn"].'" method="post">
                            <input type="text" name="Idpedido" value="'.@$parameters_args["PedidoId"].'">
                            <input type="text"  id= "TransaccionDePago" name="TransaccionDePago" value="'.@$parameters_args["TransaccionDePago"].'">
                            <input type="submit" id="btncompletado" value="Completar orden" >
                        </form>
                        <form  id ="formintentar" style="display:none"  target="dummyframe" action="https://'.$_SERVER[ "HTTP_HOST"].'/wp-content/plugins/MultiPluginPagoFacil/actualizarnumero.php" method="post">
                                <input type="text" name="tnTelefono" value="'.@$parameters_args["tnTelefono"].'" placeolder="Introducir numero">
                                <input type="submit"  value="Intentar de nuevo" onclick="setTimeout(cargarpagina, 5000); "   id="btnNuevaTransaccion" >
                        </form>
                        <iframe style="display:none" name="dummyframe" id="dummyframe"></iframe>
                    </center>
                </div>
                <script src="https://code.jquery.com/jquery-3.5.0.min.js" ></script>
            <script>
           var  intervalo ;
            $(document).ready(function() {
                var lnTransaccionDePago = $("#TransaccionDePago").val();
                if(lnTransaccionDePago !=  0 )
                {
                    intervalo=setInterval("verificartransaccion('.@$parameters_args["TransaccionDePago"].')",8000);
                }else{
                    $("#formintentar").show();
                }
            });

            function cargarpagina()
            {
                location.reload();
            }
          
            function verificartransaccion(codigo){
                var trans=codigo;
                  var urlajax="https://'.$_SERVER[ "HTTP_HOST"].'/wp-content/plugins/MultiPluginPagoFacil/consultatransacciontigo.php"; 
                  
              
                  $.ajax({                    
                          url: urlajax,
                          data: {TransaccionDePago:trans },
                          type : "POST",
                          dataType: "json",
                          
                              beforeSend:function( ) {   
                              
                              },                    
                              success:function(response) {
                                console.log(response);
                               
                                    if(response.tipo == 0 )
                                    {
                                        $("#btncompletado").click();
                                    } 
                                    if(response.tipo == 1 )
                                    {
                                       $("#lbltexto").text(response.mensaje);
                                       $("#imgcarga").hide();
                                       $("#formintentar").show();
                                        clearInterval(intervalo);
                                    } 
                              },
                              error: function (data) {
                                console.log(data);
                              },               
                              complete:function( ) {
                              },
                          });  
            }



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
        /*
            $parameters_args = $this->get_params_posttigo($order_id);
            
            $payu_args_array = array();
            foreach($parameters_args as $key => $value){
                $payu_args_array[] = $key . '=' . $value;
            }
            $params_post = implode('&', $payu_args_array);
            */
        
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

