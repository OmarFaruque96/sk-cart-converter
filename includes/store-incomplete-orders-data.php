<?php 
/**
* SAVE INCOMPLETE ORDERS DATA
*/

add_action('init', function() {
    add_action('wp_ajax_act_save_abandoned_cart', 'act_save_abandoned_cart');
    add_action('wp_ajax_nopriv_act_save_abandoned_cart', 'act_save_abandoned_cart');
});

function act_save_abandoned_cart() {

    if (!isset($_POST['form_data'])) {
        wp_send_json_error('No data received');
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'abandoned_carts';

    // Retrieve cart contents
    $cart = WC()->cart->get_cart();
    $products = array();

    foreach ($cart as $cart_item) {
        $product = $cart_item['data'];
        $products[] = array(
            'product_id' => $product->get_id(),
            'product_name' => $product->get_name(),
            'quantity' => $cart_item['quantity'],
            'price' => $product->get_price()
        );
    }

    // Retrieve form data safely
    $form_data = $_POST['form_data'];

    $data = array(
        'order_no' => 'Abandoned-' . uniqid(),
        'products' => serialize($products),
        'user_name' => sanitize_text_field($form_data['billing_first_name'] . ' ' . $form_data['billing_last_name']),
        'first_name' => sanitize_text_field($form_data['billing_first_name']),
        'last_name' => sanitize_text_field($form_data['billing_last_name']),
        'email' => sanitize_email($form_data['billing_email']),
        'phone' => sanitize_text_field($form_data['billing_phone']),
        'address' => serialize(array(
            'billing_address_1' => sanitize_text_field($form_data['billing_address_1']),
            'billing_address_2' => sanitize_text_field($form_data['billing_address_2']),
            'billing_city' => sanitize_text_field($form_data['billing_city']),
            'billing_state' => sanitize_text_field($form_data['billing_state']),
            'billing_postcode' => sanitize_text_field($form_data['billing_postcode']),
            'billing_country' => sanitize_text_field($form_data['billing_country'])
        )),
        'additional_text' => sanitize_textarea_field($form_data['order_comments']),
        'checkout_method' => sanitize_text_field($form_data['payment_method'])
    );

    $existing_cart = $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM $table_name WHERE email = %s OR phone = %s", $form_data['billing_email'], $form_data['billing_phone'])
    );

    if (!$existing_cart) {
        $wpdb->insert($table_name, $data);
    } else {
        $wpdb->update($table_name, $data, array('id' => $existing_cart->id));
    }

    wp_send_json_success('Cart saved successfully');
}

function act_track_abandoned_carts() {
    if (is_checkout() && !is_order_received_page()) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'abandoned_carts';

        // Get cart contents
        $cart = WC()->cart->get_cart();
        $products = array();

        foreach ($cart as $cart_item_key => $cart_item) {
            $product = $cart_item['data'];
            $products[] = array(
                'product_id' => $product->get_id(),
                'product_name' => $product->get_name(),
                'quantity' => $cart_item['quantity'],
                'price' => $product->get_price()
            );
        }

        // Get user information
        $user_id = get_current_user_id();
        $user = $user_id ? get_userdata($user_id) : null;
        $user_name = $user ? $user->display_name : 'Guest';
        $email = $user ? $user->user_email : '';
        $phone = $user ? $user->user_phone : '';

        // Prepare data for insertion
        $data = array(
            'order_no' => 'Abandoned-' . uniqid(), // Generate a unique order number
            'products' => serialize($products),
            'user_name' => $user_name,
            'email' => $email,
            'phone' => '',
            'address' => serialize(array()),
            'additional_text' => '',
            'checkout_method' => ''
        );

        // Insert or update the abandoned cart data
        $existing_cart = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE email = %s OR phone = %s",
                $email,$phone 
            )
        );

        if (!$existing_cart) {
            // Insert new abandoned cart data
            $wpdb->insert($table_name, $data);
        } else {
            // Update existing cart data
            $wpdb->update($table_name, $data, array('id' => $existing_cart->id));
        }
    }
}