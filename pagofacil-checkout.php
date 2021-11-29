<?php
/*
Plugin Name: PagoFacil Checkout Payment Gateway
Plugin URI: https://pagofacil.com.bo/
Description: Plugin de integracion entre Wordpress-Woocommerce con la plataforma PagoFacil Checkout
Version: 2.0
Author: PagoFacil Bolivia
Author URI: https://pagofacil.com.bo/
License: GPLv2 
one line to give the program's name and an idea of what it does.
Copyright (C) 2020  Leonardoayala25

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/
add_action('plugins_loaded', 'pagofacil_checkout_gateway', 0);
function pagofacil_checkout_gateway() {
	if(!class_exists('WC_Payment_Gateway')) return;
	
	class WC_Chechout_PagoFacil extends WC_Payment_Gateway {
	
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
			$this->UrlFactura = $this->settings['UrlFactura'];
			$this->UrlReturn = $this->settings['UrlReturn'];
			
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
					'default' => __('http://su.dominio.com/wp-content/plugins/woocomerce-pagofacil-checkout-v3/return.php', 'pagofacilcheckout')),
				'UrlCallBack' => array(
					'title' => __('Url Callback', 'pagofacil_checkout'),
					'type' => 'text',
					'description' => __('URL de la página mostrada después de finalizar el pago. No olvide cambiar su dominio ', 'pagofacilcheckout'),
					'default' => __('http://su.dominio.com/wp-content/plugins/woocomerce-pagofacil-checkout-v3/callback.php', 'pagofacilcheckout'))
                
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
			$parameters_args = array(
				'tcParametros' => base64_encode($tcParametros),
				'tcCommerceID' => $tcCommerceID
			);
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
			foreach($parameters_args as $key => $value){
				$payu_args_array[] = $key . '=' . $value;
			}
			$params_post = implode('&', $payu_args_array);

			$payu_args_array = array();
			foreach($parameters_args as $key => $value){
			  $payu_args_array[] = "<input type='hidden' name='$key' value='$value'/>";
			}
			

			return '<form  action="https://checkout.pagofacil.com.bo/es/pay" method="post" id="pagofacil_checkout_form">' . implode('', $payu_args_array) 
				. '<input style="background:#10d8fb;;border-radius:12px;color:aliceblue;-webkit-text-stroke-width:thin;"  type="submit" id="submit_payu_latam" value="' .__('Pagar', 'pagofacilcheckout').'" /></form>';
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

	/**
	 * Ambas funciones son utilizadas para notifcar a WC la existencia de PagoFacil Checkout
	 */
	function add_pagofacil_checkout($methods) {
		$methods[] = 'WC_Chechout_PagoFacil';
		return $methods;
	}
	add_filter('woocommerce_payment_gateways', 'add_pagofacil_checkout' );
}