<?php

class WC_Multi_Facil extends WC_Payment_Gateway {

    public function __construct() {
        $this->id = 'pagofacilcheckout';
        $this->icon = apply_filters('woocommerce_checkout_icon', plugins_url('/img/logo_pago_facil.png', __FILE__));
        $this->has_fields = false;
        $this->method_title = 'PagoFacil Checkout';
        $this->method_description = 'Integración de WooCommerce con la pasarela de pagos de PagoFacil';
        $this->title = 'PagoFacil Bolivia';

        $this->init_form_fields();
        $this->init_settings();

        $this->CommerceID = $this->get_option('CommerceID');
        $this->TokenServicio = $this->get_option('TokenServicio');
        $this->TokenSecret = $this->get_option('TokenSecret');
        $this->UrlCallBack = $this->get_option('UrlCallBack');
        $this->UrlReturn = $this->get_option('UrlReturn');
        $this->description = $this->get_option('description');

        if (version_compare(WC()->version, '2.0.0', '>=')) {
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        } else {
            add_action('woocommerce_update_options_payment_gateways', array($this, 'process_admin_options'));
        }

        add_action('woocommerce_receipt_pagofacilcheckout', array($this, 'receipt_page'));
        add_action('woocommerce_payment_fields', array($this, 'payment_fields'));
    }

    public function init_form_fields() {
        $this->form_fields = array(
            'CommerceID' => array(
                'title' => __('Commerce ID', 'pagofacilcheckout'),
                'type' => 'text',
                'description' => __('ID de comercio proporcionado por PagoFacil.', 'pagofacilcheckout'),
                'default' => ''
            ),
            'TokenServicio' => array(
                'title' => __('Token de Servicio', 'pagofacilcheckout'),
                'type' => 'text',
                'description' => __('Token de servicio proporcionado por PagoFacil.', 'pagofacilcheckout'),
                'default' => ''
            ),
            'TokenSecret' => array(
                'title' => __('Token Secreto', 'pagofacilcheckout'),
                'type' => 'text',
                'description' => __('Token secreto proporcionado por PagoFacil.', 'pagofacilcheckout'),
                'default' => ''
            ),
            'UrlReturn' => array(
                'title' => __('URL de Retorno', 'pagofacilcheckout'),
                'type' => 'text',
                'description' => __('URL a la que se redirige después de finalizar el pago.', 'pagofacilcheckout'),
                'default' => ''
            ),
            'UrlCallBack' => array(
                'title' => __('URL de Callback', 'pagofacilcheckout'),
                'type' => 'text',
                'description' => __('URL a la que PagoFacil notificará sobre el estado del pago.', 'pagofacilcheckout'),
                'default' => ''
            ),
            'description' => array(
                'title' => __('Descripción', 'pagofacilcheckout'),
                'type' => 'textarea',
                'description' => __('Mensaje que se mostrará al cliente durante el proceso de pago.', 'pagofacilcheckout'),
                'default' => 'Plataforma de pagos seguros en Bolivia.'
            )
        );
    }

    public function admin_options() {
        echo '<h3>' . __('Configuración de PagoFacil Checkout', 'pagofacilcheckout') . '</h3>';
        echo '<p>' . __('Integra WooCommerce con la pasarela de pagos de PagoFacil.', 'pagofacilcheckout') . '</p>';
        echo '<table class="form-table">';
        $this->generate_settings_html();
        echo '</table>';
    }

    public function receipt_page($order_id) {
        $order = wc_get_order($order_id);
        echo '<p>' . __('Gracias por su pedido. Haga clic en el botón para continuar con el pago seguro mediante PagoFacil Checkout.', 'pagofacilcheckout') . '</p>';
        echo $this->generate_checkout_form($order_id);
    }

    public function get_params_post($order_id) {
        $order = wc_get_order($order_id);
        $order_data = $order->get_data();
        $currency = get_woocommerce_currency();
        $amount = number_format($order->get_total(), 2, '.', '');
        $lcMoneda = $currency == "USD" ? 1 : 2;

        $ArrayProductos = array();
        $lnSerial = 0;

        foreach ($order->get_items() as $item_id => $item) {
            $product = $item->get_product();
            $lnSerial++;
            $product_detalle = array(
                "Serial" => $lnSerial,
                "Producto" => $product->get_name(),
                "LinkPago" => 0,
                "Cantidad" => $item->get_quantity(),
                "Precio" => $product->get_price(),
                "Descuento" => 0,
                "Total" => $item->get_total()
            );
            array_push($ArrayProductos, $product_detalle);
        }

        $lcPedidoID = $order_id;
        $lcEmail = $order_data['billing']['email'];
        $lnTelefono = $order_data['billing']['phone'];

        $lcCadenaAFirmar = "{$this->TokenServicio}|$lcEmail|$lnTelefono|$lcPedidoID|$amount|$lcMoneda|$this->UrlCallBack|$this->UrlReturn|" . json_encode($ArrayProductos) . "|6";
        $lcFirma = hash('sha256', $lcCadenaAFirmar);
        $lcDatosPago = "$lcFirma|$lcEmail|$lnTelefono|$lcPedidoID|$amount|$lcMoneda|$this->UrlCallBack|$this->UrlReturn|" . json_encode($ArrayProductos) . "|6";
        $lcDatosPago = str_pad($lcDatosPago, (strlen($lcDatosPago) + 8 - (strlen($lcDatosPago) % 8)), "\0");
        $tcParametros = openssl_encrypt($lcDatosPago, "DES-EDE3", $this->TokenSecret, OPENSSL_ZERO_PADDING);

        return array(
            'tcParametros' => base64_encode($tcParametros),
            'tcCommerceID' => $this->CommerceID,
        );
    }

    public function generate_checkout_form($order_id) {
        $parameters_args = $this->get_params_post($order_id);

        $form = '<form action="https://checkout.pagofacil.com.bo/es/pay" method="post" id="pagofacil_checkout_form">';
        foreach ($parameters_args as $key => $value) {
            $form .= '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '"/>';
        }
        $form .= '<input type="submit" style="background-color: #10d8fb; border-radius: 12px; color: #fff; -webkit-text-stroke-width: thin;" value="' . __('Pagar', 'pagofacilcheckout') . '" />';
        $form .= '</form>';

        $form .= '<script>document.getElementById("pagofacil_checkout_form").submit();</script>';

        return $form;
    }

    public function payment_fields() {
        echo '<p>' . $this->description . '</p>';
        echo '<iframe src="https://pagofacil.com.bo/online/es/vistametodosdepago/' . $this->TokenServicio . '" frameborder="0" height="300" allowfullscreen=""></iframe>';
    }

    public function process_payment($order_id) {
        $order = wc_get_order($order_id);
        $order->update_status('pending', __('Awaiting payment confirmation from PagoFacil.', 'pagofacilcheckout'));

        WC()->cart->empty_cart();

        return array(
            'result' => 'success',
            'redirect' => $this->get_return_url($order)
        );
    }
}

function add_pagofacilcheckout_gateway_class($methods) {
    $methods[] = 'WC_Multi_Facil';
    return $methods;
}

add_filter('woocommerce_payment_gateways', 'add_pagofacilcheckout_gateway_class');
