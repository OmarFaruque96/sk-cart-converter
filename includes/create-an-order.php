<?php 
/**
* CREATE ORDER FUNCTIONALITY
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

add_action('wp_ajax_act_redirect_to_checkout', 'act_redirect_to_checkout');
add_action('wp_ajax_nopriv_act_redirect_to_checkout', 'act_redirect_to_checkout');

function act_redirect_to_checkout() {
	
    if (!isset($_POST['cart_data'])) {
        wp_send_json_error('Invalid cart data.');
    }

    WC()->cart->empty_cart();

    // Store cart data in WooCommerce session
    if (!WC()->session) {
        WC()->session = new WC_Session_Handler();
        WC()->session->init();
    }

    WC()->session->set('abandoned_cart_data', $_POST['cart_data']);

    // Add products to the cart
    foreach ($_POST['cart_data']['products'] as $product) {
        WC()->cart->add_to_cart($product['id'], $product['quantity']);
    }

    // Return checkout URL
    wp_send_json_success(array(
        'checkout_url' => wc_get_checkout_url()
    ));
}


add_filter('woocommerce_checkout_get_value', 'prefill_checkout_fields', 10, 2);

function prefill_checkout_fields($value, $input) {
    if (!WC()->session) {
        return $value;
    }

    $cart_data = WC()->session->get('abandoned_cart_data');

    if ($cart_data) {
        switch ($input) {
            case 'billing_first_name':
                return $cart_data['first_name'];
            case 'billing_last_name':
                return $cart_data['last_name'];
            case 'billing_email':
                return $cart_data['email'];
            case 'billing_phone':
                return $cart_data['phone'];
            case 'billing_address_1':
                return $cart_data['address'];
            case 'order_comments':
                return $cart_data['additional_text'];
        }
    }

    return $value;
}