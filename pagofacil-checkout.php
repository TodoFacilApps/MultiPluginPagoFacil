<?php
/*
	Plugin Name: PAGO FACIL Bolivia Payment Gateway 
	Plugin URI: https://pagofacil.com.bo/
	Description: Plugin de integracion entre Wordpress-Woocommerce y la pasarela de pago PagoFacil Bolivia y sus complementos 	
	Version: 1.0
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
	2403
	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/
//add_action('plugins_loaded', 'Pago_Facil_gateway', 0);
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! class_exists( 'WC_Pago_Facil' ) ) :

	class WC_Pago_Facil {
			
		/**
		 * Instance of this class.
		 *
		 * @var object
		 */
		protected static $instance = null;
		//includes();	
				// Add the gateway.
				//	add_filter( 'woocommerce_payment_gateways', array( $this, 'add_gateway' ) );
		//	add_filter( 'woocommerce_payment_gateways', array( $this, 'add_gateway' ) );
		public function __construct() {
		

				$this->includes();	
				// Add the gateway.
				//	add_filter( 'woocommerce_payment_gateways', array( $this, 'add_gateway' ) );
				add_filter( 'woocommerce_payment_gateways', array( $this, 'add_gateway' ) );
				add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_action_links' ) );
				
			
		}
		 
		


		public static function get_instance() {
			// If the single instance hasn't been set, set it now.
			if ( null == self::$instance ) {
				self::$instance = new self;
			}

			return self::$instance;
		}

			/**
		 * Action links.
		 *
		 * @param  array $links
		 *
		 * @return array
		 */		public function plugin_action_links( $links ) {
			$plugin_links = array();

			if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '2.1', '>=' ) ) {
				$plugin_links[] = '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=wc_qr_facil' ) ) . '">' . __( 'Qr Facil Settings', 'PagoFacil-woocommerce' ) . '</a>';
				$plugin_links[] = '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=wc_tigo_facil' ) ) . '">' . __( 'Tigo Facil Settings', 'PagoFacil-woocommerce' ) . '</a>';
				$plugin_links[] = '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=wc_multi_facil' ) ) . '">' . __( 'Multi Facil Settings', 'PagoFacil-woocommerce' ) . '</a>';
		
			} else {
				$plugin_links[] = '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=wc_qr_facil' ) ) . '">' . __( 'Qr Facil Settings', 'PagoFacil-woocommerce' ) . '</a>';
				$plugin_links[] = '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=wc_tigo_facil' ) ) . '">' . __( 'Tigo Facil Settings', 'PagoFacil-woocommerce' ) . '</a>';
				$plugin_links[] = '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=wc_multi_facil' ) ) . '">' . __( 'Multi Facil Settings', 'PagoFacil-woocommerce' ) . '</a>';
		
			}

			return array_merge( $plugin_links, $links );
		}
		private function includes() {
			include_once dirname( __FILE__ ) . '/Metodos/wc_qr_facil.php';
			include_once dirname( __FILE__ ) . '/Metodos/wc_tigo_facil.php';
			include_once dirname( __FILE__ ) . '/Metodos/wc_multi_facil.php';
			
			
		}
		public function add_gateway( $methods ) {
			array_push( $methods, 'WC_Qr_Facil' );
			array_push( $methods	, 'WC_Tigo_Facil' );
			array_push( $methods	, 'WC_Multi_Facil' );
			return $methods;
		}
	}
	add_action( 'plugins_loaded', array( 'WC_Pago_Facil', 'get_instance' ), 0 );


endif;



//add_action( 'plugins_loaded', array( 'Pago_Facil_gateway', 'get_instance' ), 0 );