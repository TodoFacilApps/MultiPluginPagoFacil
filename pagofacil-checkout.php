<?php
/*
	Plugin Name: PagoFacil Bolivia Payment Gateway
	Plugin URI: https://pagofacil.com.bo/
	Description: Plugin de integracion entre Wordpress-Woocommerce y la pasarela de pago PagoFacil Bolivia y sus complementos PHP8.1
	Version: 1.2
	Author: PagoFacil Bolivia
	Author URI: https://pagofacil.com.bo/
	License: GPLv2
*/

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WC_Pago_Facil' ) ) :

class WC_Pago_Facil {

	protected static $instance = null;

	public function __construct() {
		$this->includes();

		// Add the gateways
		add_filter( 'woocommerce_payment_gateways', array( $this, 'add_gateways' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_action_links' ) );
	}

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function plugin_action_links( $links ) {
		$plugin_links = array();

		if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '2.1', '>=' ) ) {
			$plugin_links[] = '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=wc_qr_facil' ) ) . '">' . __( 'Qr Settings', 'PagoFacil-woocommerce' ) . '</a>';
			$plugin_links[] = '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=wc_tigo_facil' ) ) . '">' . __( 'Tigo Money Settings', 'PagoFacil-woocommerce' ) . '</a>';
			$plugin_links[] = '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=wc_multi_facil' ) ) . '">' . __( 'Multi Facil Settings', 'PagoFacil-woocommerce' ) . '</a>';
		} else {
			$plugin_links[] = '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=wc_qr_facil' ) ) . '">' . __( 'Qr Settings', 'PagoFacil-woocommerce' ) . '</a>';
			$plugin_links[] = '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=wc_tigo_facil' ) ) . '">' . __( 'Tigo Money Settings', 'PagoFacil-woocommerce' ) . '</a>';
			$plugin_links[] = '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=wc_multi_facil' ) ) . '">' . __( 'Multi Facil Settings', 'PagoFacil-woocommerce' ) . '</a>';
		}

		return array_merge( $plugin_links, $links );
	}

	private function includes() {
		include_once dirname( __FILE__ ) . '/Metodos/wc_qr_facil.php';
		include_once dirname( __FILE__ ) . '/Metodos/wc_tigo_facil.php';
		include_once dirname( __FILE__ ) . '/Metodos/wc_multi_facil.php';
	}

	public function add_gateways( $gateways ) {
		$gateways[] = 'WC_Qr_Facil';
		$gateways[] = 'WC_Tigo_Facil';
		$gateways[] = 'WC_Multi_Facil';
		return $gateways;
	}
}

add_action( 'plugins_loaded', array( 'WC_Pago_Facil', 'get_instance' ), 0 );

endif;
