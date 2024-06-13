<?php

class WC_Qr_Facil extends WC_Payment_Gateway {
    
    public function __construct() {
        $this->id = 'qr';
        $this->icon = apply_filters('woocommerce_checkout_icon', "https://serviciopagofacil.syscoop.com.bo/Imagenes/MP/logo_qr.png");
        $this->has_fields = false;
        $this->method_title = 'Transferencias Qr';
        $this->method_description = 'Integración de Woocommerce para pago con QR';
        $this->title = 'Transferencias Qr';

        $this->init_form_fields();
        $this->init_settings();
        
        $this->CommerceID = $this->get_option('CommerceID');
        $this->TokenServicio = $this->get_option('TokenServicio');
        $this->TokenSecret = $this->get_option('TokenSecret');
        $this->UrlCallBack = $this->get_option('UrlCallBack');
        $this->UrlReturn = $this->get_option('UrlReturn');

        if (version_compare(WC()->version, '2.0.0', '>=')) {
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        } else {
            add_action('woocommerce_update_options_payment_gateways', array($this, 'process_admin_options'));
        }

        add_action('woocommerce_receipt_qr', array($this, 'receipt_page'));
    }

    function init_form_fields() {
        $this->form_fields = array(
            'CommerceID' => array(
                'title' => __('Commerce ID', 'pagofacilcheckout'),
                'type' => 'text',
                'description' => __('credenciales de su empresa.', 'pagofacilcheckout'),
                'default' => ''
            ),
            'TokenServicio' => array(
                'title' => __('Token Servicio', 'pagofacilcheckout'),
                'type' => 'text',
                'description' => __('Token de Servicio que Pagofacil le dará.', 'pagofacilcheckout')
            ),
            'TokenSecret' => array(
                'title' => __('Token secreto', 'pagofacilcheckout'),
                'type' => 'text',
                'description' => __('Token de secreto que Pagofacil le dará.', 'pagofacilcheckout')
            ),
            'UrlReturn' => array(
                'title' => __('Url Return', 'pagofacilcheckout'),
                'type' => 'text',
                'description' => __('URL de la página mostrada después de finalizar el pago. No olvide cambiar su dominio.', 'pagofacilcheckout'),
                'default' => 'https://' . $_SERVER['HTTP_HOST'] . '/wp-content/plugins/MultiPluginPagoFacil/return.php'
            ),
            'UrlCallBack' => array(
                'title' => __('Url Callback', 'pagofacilcheckout'),
                'type' => 'text',
                'description' => __('URL de la página mostrada después de finalizar el pago. No olvide cambiar su dominio.', 'pagofacilcheckout'),
                'default' => 'https://' . $_SERVER['HTTP_HOST'] . '/wp-content/plugins/MultiPluginPagoFacil/callback.php'
            )
        );
    }

    public function admin_options() {
        echo '<h3>' . __('PagoFacil Checkout datos de la empresa', 'PagoFacilCheckout') . '</h3>';
        echo '<table class="form-table">';
        $this->generate_settings_html();
        echo '</table>';
    }

    public function payment_fields() {
        echo '<p> Se creará un QR que deberás escanear con la app de su banco.</p>';
    }

    function receipt_page($order) {
        echo '<p>' . __('Se ha generado con éxito su QR', 'pagofacilcheckout') . '</p>';
        echo $this->generate_checkout_form($order);
    }

    public function get_params_post($order_id) {
        global $woocommerce;

        $order = wc_get_order($order_id);
        $order_data = $order->get_data();
        $currency = get_woocommerce_currency();
        $amount = number_format($order->get_total(), 2, '.', '');
        $lcMoneda = $currency == "USD" ? 1 : 2;

        $lcNombreCliente = ($order_data['billing']['first_name'] ?? '') . ' ' . ($order_data['billing']['last_name'] ?? '');
        $array_items = $order->get_items();
        $array_productos = array();
        $lnSerial = 0;

        foreach ($array_items as $item) {
            $product = $item->get_product();
            $lnSerial++;
            $product_detalle = array(
                "Serial" => $lnSerial,
                "Producto" => $product->get_name(),
                "LinkPago" => 0,
                'Cantidad' => $item->get_quantity(),
                "Precio" => $product->get_price(),
                "Descuento" => 0,
                "Total" => $item->get_quantity() * $product->get_price()
            );
            array_push($array_productos, $product_detalle);
        }

        $lcPedidoID = $order_id;
        $lcEmail = $order_data['billing']['email'];
        $lnTelefono = $order_data['billing']['phone'];
        $lcParametro1 = $this->UrlCallBack;
        $lcParametro2 = $this->UrlReturn;
        $lcParametro3 = json_encode($array_productos);
        $lcParametro4 = trim("");

        $lcCadenaAFirmar = "{$this->TokenServicio}|$lcEmail|$lnTelefono|$lcPedidoID|$amount|$lcMoneda|$lcParametro1|$lcParametro2|$lcParametro3|$lcParametro4";
        $lcFirma = hash('sha256', $lcCadenaAFirmar);
        $lcDatosPago = "$lcFirma|$lcEmail|$lnTelefono|$lcPedidoID|$amount|$lcMoneda|$lcParametro1|$lcParametro2|$lcParametro3|$lcParametro4";

        $lcDatosPago = str_pad($lcDatosPago, (strlen($lcDatosPago) + 8 - (strlen($lcDatosPago) % 8)), "\0");
        $tcParametros = openssl_encrypt($lcDatosPago, "DES-EDE3", $this->TokenSecret, OPENSSL_ZERO_PADDING);

        $url = 'https://servicios.qr.com.bo/api/login';
        $login_data = array("TokenService" => $this->TokenServicio, "TokenSecret" => $this->TokenSecret, 'UrlCallBack' => $this->UrlCallBack, 'UrlReturn' => $this->UrlReturn);

        $response_login = wp_remote_post($url, array(
            'headers' => array('Content-Type' => 'application/json; charset=utf-8'),
            'body' => json_encode($login_data),
            'method' => 'POST',
            'data_format' => 'body',
        ));
        $response = wp_remote_retrieve_body($response_login);
        $responsetoken = json_decode($response);

        $transaccion_data = array(
            "tnCliente" => 9,
            "tcApp" => 3,
            'tcCodigoClienteEmpresa' => 9,
            "tnMetodoPago" => 4,
            'tcCorreo' => $lcEmail,
            'tnTelefono' => $lnTelefono,
            "tcFacturaA" => $lcNombreCliente,
            'tnCiNit' => 123456,
            "tcNroPago" => $order_id,
            'tnMontoClienteEmpresa' => $amount,
            'tnMontoClienteSyscoop' => 1,
            "tcPeriodo" => "Checkout",
            'tcImei' => 123456789,
            "taEntidades" => "1,2,3",
            "tcCommerceID" => $this->CommerceID,
            "tcParametros" => base64_encode($tcParametros),
        );

        $url = 'https://serviciostigomoney.pagofacil.com.bo/api/servicio/generarqr';
        $response_qr = wp_remote_post($url, array(
            'headers' => array('Content-Type' => 'application/json; charset=utf-8'),
            'body' => json_encode($transaccion_data),
            'method' => 'POST',
            'data_format' => 'body',
        ));

        $response = wp_remote_retrieve_body($response_qr);
        $responsejson = json_decode($response);
        return '<img src="' . $responsejson->data . '" />';
    }

    function process_payment($order_id) {
        $order = wc_get_order($order_id);

        $order->update_status('pending-payment', __('Awaiting QR payment', 'pagofacilcheckout'));

        WC()->cart->empty_cart();
        return array(
            'result' => 'success',
            'redirect' => $this->get_return_url($order)
        );
    }
}

function add_qr_gateway_class($methods) {
    $methods[] = 'WC_Qr_Facil';
    return $methods;
}

add_filter('woocommerce_payment_gateways', 'add_qr_gateway_class');
?>
