<?php
/*
Plugin Name: Abandoned Cart Tracker
Description: Tracks abandoned carts in WooCommerce and stores specific data in a custom table.
Version: 1.0
Author: Omar
*/

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}


define( 'SS_VERSION', '1.0.0' );
define( 'SS_ASSETS_PATH', plugin_dir_url( __FILE__ ) . 'assets' );

// Create the custom table on plugin activation
register_activation_hook(__FILE__, 'act_create_abandoned_carts_table');

function act_create_abandoned_carts_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'abandoned_carts';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        order_no VARCHAR(255) DEFAULT '',
        products TEXT,
        user_name VARCHAR(255) DEFAULT '',
        email VARCHAR(255) DEFAULT '',
        phone VARCHAR(255) DEFAULT '',
        address TEXT,
        additional_text TEXT,
        checkout_method VARCHAR(255) DEFAULT '',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}


add_action( 'wp_enqueue_scripts', function() { 

        wp_enqueue_styles(
            'ss-core-style',
            SS_ASSETS_PATH. '/sk-style.css',
            array(),
            SS_VERSION
        );

        wp_enqueue_script(
            'ss-default-js',
            SS_ASSETS_PATH. '/checkout-tracker.js',
            array( 'jquery' ),
            SS_VERSION,
            true
        );

       wp_localize_script( 'ss-default-js', 'adminAjax', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );

});

add_action('admin_enqueue_scripts', function() {
    wp_enqueue_script('ss-admin-product-search', SS_ASSETS_PATH . '/admin.js', array('jquery'), SS_VERSION, true);
    wp_localize_script('ss-admin-product-search', 'adminAjax', array('ajax_url' => admin_url('admin-ajax.php')));
});



// Capture abandoned cart data
add_action('wp_ajax_act_save_abandoned_cart', 'act_save_abandoned_cart');
add_action('wp_ajax_nopriv_act_save_abandoned_cart', 'act_save_abandoned_cart');

// function act_save_abandoned_cart() {

//    // $phone_number = sanitize_text_field($_POST['phone_number']);

//     if (isset($_POST['form_data'])) {
//         global $wpdb;
//         $table_name = $wpdb->prefix . 'abandoned_carts';

//         // Get cart contents
//         $cart = WC()->cart->get_cart();
//         $products = array();

//         foreach ($cart as $cart_item_key => $cart_item) {
//             $product = $cart_item['data'];
//             $products[] = array(
//                 'product_id' => $product->get_id(),
//                 'product_name' => $product->get_name(),
//                 'quantity' => $cart_item['quantity'],
//                 'price' => $product->get_price()
//             );
//         }

//         // Get form data
//         $form_data = $_POST['form_data'];

//         // Prepare data for insertion
//         $data = array(
//             'order_no' => 'Abandoned-' . uniqid(), // Generate a unique order number
//             'products' => serialize($products),
//             'user_name' => $form_data['billing_first_name'] . ' ' . $form_data['billing_last_name'],
//             'email' => $form_data['billing_email'],
//             'phone' => $form_data['billing_phone'],
//             'address' => serialize(array(
//                 'billing_address_1' => $form_data['billing_address_1'],
//                 'billing_address_2' => $form_data['billing_address_2'],
//                 'billing_city' => $form_data['billing_city'],
//                 'billing_state' => $form_data['billing_state'],
//                 'billing_postcode' => $form_data['billing_postcode'],
//                 'billing_country' => $form_data['billing_country']
//             )),
//             'additional_text' => $form_data['order_comments'],
//             'checkout_method' => $form_data['payment_method']
//         );

//         // Insert or update the abandoned cart data
//         $existing_cart = $wpdb->get_row(
//             $wpdb->prepare(
//                 "SELECT * FROM $table_name WHERE email = %s",
//                 $form_data['billing_email']
//             )
//         );

//         if (!$existing_cart) {
//             // Insert new abandoned cart data
//             $wpdb->insert($table_name, $data);
//         } else {
//             // Update existing cart data
//             $wpdb->update($table_name, $data, array('id' => $existing_cart->id));
//         }
//     }
//     wp_send_json_success($data);
// }

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

    // Check if the email already exists
    $existing_cart = $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM $table_name WHERE email = %s", $form_data['billing_email'])
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
                "SELECT * FROM $table_name WHERE email = %s",
                $email
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

// Add a menu item to the WordPress admin dashboard
add_action('admin_menu', 'act_add_admin_menu');

function act_add_admin_menu() {
    add_menu_page(
        'Abandoned Carts', // Page title
        'Abandoned Carts', // Menu title
        'manage_options',  // Capability
        'abandoned-carts', // Menu slug
        'act_display_abandoned_carts', // Callback function
        'dashicons-cart', // Icon
        30 // Position
    );
    // Add Edit Cart Page
    add_submenu_page(
        null, // This hides the menu item from the sidebar
        'Edit Abandoned Cart', // Page title
        'Edit Abandoned Cart', // Menu title (not visible)
        'manage_options', // Capability
        'edit-abandoned-cart', // Menu slug
        'act_edit_abandoned_cart' // Callback function
    );

}

// Display abandoned cart data in the admin dashboard
function act_display_abandoned_carts() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'abandoned_carts';

    // Fetch all abandoned carts
    $abandoned_carts = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");

    echo '<div class="wrap">';
    echo '<h1>Abandoned Carts</h1>';
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead>
            <tr>
                <th>Order No</th>
                <th>Products</th>
                <th>User Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Address</th>
                <th>Additional Text</th>
                <th>Checkout Method</th>
                <th>Created At</th>
                <th>Action</th>
            </tr>
          </thead>';
    echo '<tbody>';

    if (!empty($abandoned_carts)) {
        foreach ($abandoned_carts as $cart) {
            $products = unserialize($cart->products);
            $address = unserialize($cart->address);

            echo '<tr>';
            echo '<td>' . esc_html($cart->order_no) . '</td>';
            echo '<td>';
            foreach ($products as $product) {
                echo esc_html($product['product_name']) . ' (ID: ' . esc_html($product['product_id']) . ') - ' . esc_html($product['quantity']) . ' x ' . wc_price($product['price']) . '<br>';
            }
            echo '</td>';
            echo '<td>' . esc_html($cart->user_name) . '</td>';
            echo '<td>' . esc_html($cart->email) . '</td>';
            echo '<td>' . esc_html($cart->phone) . '</td>';
            echo '<td>';
            if (is_array($address)) {
                echo esc_html(implode(', ', array_filter($address)));
            }
            echo '</td>';
            echo '<td>' . esc_html($cart->additional_text) . '</td>';
            echo '<td>' . esc_html($cart->checkout_method) . '</td>';
            echo '<td>' . esc_html($cart->created_at) . '</td>';
            echo '<td>
        <a href="?page=edit-abandoned-cart&action=edit&id=' . esc_attr($cart->id) . '">Edit</a> |
        <a href="?page=act_abandoned_carts&action=delete&id=' . esc_attr($cart->id) . '" onclick="return confirm(\'Are you sure you want to delete this cart?\');">Delete</a>
      </td>';

            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="9">No abandoned carts found.</td></tr>';
    }

    echo '</tbody>';
    echo '</table>';
    echo '</div>';

    
    // DELETE
    if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'abandoned_carts';
        $cart_id = intval($_GET['id']);

        $wpdb->delete($table_name, array('id' => $cart_id));

        // Redirect to remove the query string and prevent duplicate deletions
        wp_redirect(admin_url('admin.php?page=act_abandoned_carts'));
        exit;
    }
}

function act_edit_abandoned_cart() {
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        wp_die('Invalid cart ID.');
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'abandoned_carts';
    $cart_id = intval($_GET['id']);
    
    $cart = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $cart_id));

    if (!$cart) {
        wp_die('Cart not found.');
    }

    $products = unserialize($cart->products);
    $address = unserialize($cart->address);


    if (isset($_POST['update_cart'])) {
        global $wpdb;
        $cart_id = intval($_POST['cart_id']);

        // Sanitize and prepare general cart details
        $data = array(
            'user_name'        => sanitize_text_field($_POST['user_name']),
            'email'            => sanitize_email($_POST['email']),
            'phone'            => sanitize_text_field($_POST['phone']),
            'address'          => maybe_serialize(explode(',', sanitize_textarea_field($_POST['address']))),
            'additional_text'  => sanitize_textarea_field($_POST['additional_text']),
            'checkout_method'  => sanitize_text_field($_POST['checkout_method'])
        );

        // Handle product updates
        if (!empty($_POST['products'])) {
            $updated_products = [];

            foreach ($_POST['products'] as $product) {
                $updated_products[] = array(
                    'product_id'   => intval($product['id']),
                    'product_name' => sanitize_text_field($product['name']),
                    'quantity'     => intval($product['quantity']),
                    'price'        => floatval($product['price'])
                );
            }

            $data['products'] = maybe_serialize($updated_products);
        }

        // Update the database
        $where = array('id' => $cart_id);
        $updated = $wpdb->update($table_name, $data, $where);

        if ($updated !== false) {
            wp_redirect(admin_url('admin.php?page=act_abandoned_carts&updated=true'));
            exit;
        } else {
            echo '<div class="error"><p>Failed to update cart.</p></div>';
        }
    }


    
    echo '<div class="wrap">';
    echo '<h1>Edit Abandoned Cart</h1>';
    
    echo '<form method="post" action="">';
    echo '<input type="hidden" name="cart_id" value="' . esc_attr($cart->id) . '">';

    echo '<table class="form-table">';

    echo '<tr><th>User Name:</th><td><input type="text" name="user_name" value="' . esc_attr($cart->user_name) . '" class="regular-text"></td></tr>';
    echo '<tr><th>Email:</th><td><input type="email" name="email" value="' . esc_attr($cart->email) . '" class="regular-text"></td></tr>';
    echo '<tr><th>Phone:</th><td><input type="text" name="phone" value="' . esc_attr($cart->phone) . '" class="regular-text"></td></tr>';
    echo '<tr><th>Address:</th><td><textarea name="address" class="regular-text">' . esc_textarea(implode(', ', $address)) . '</textarea></td></tr>';
    echo '<tr><th>Additional Notes:</th><td><textarea name="additional_text" class="regular-text">' . esc_textarea($cart->additional_text) . '</textarea></td></tr>';
    echo '<tr><th>Checkout Method:</th><td><input type="text" name="checkout_method" value="' . esc_attr($cart->checkout_method) . '" class="regular-text"></td></tr>';

    echo '</table>';


    // ADD PRODUCT SECTION

    echo '<h2>Search & Add Product</h2>';
    echo '<input type="text" id="product_search" class="regular-text" placeholder="Search products...">';
    echo '<select id="product_variations" style="display:none;"></select>';
    echo '<button type="button" id="add_product" class="button button-primary">Add Product</button>';
    echo '<div id="search_results"></div>';


    

    // PRODUCT SHOWING TABLE

    echo '<h2>Product Details</h2>';
    echo '<table class="widefat fixed striped">';
    echo '<thead><tr><th>Name</th><th>Price</th><th>Quantity</th></tr></thead>';
    echo '<tbody>';
    
    foreach ($products as $product) {
        echo '<tr>';
        echo '<td>' . esc_html($product['product_name']) . '</td>';
        echo '<td>' . esc_html(wc_price($product['price'])) . '</td>';
        echo '<td>' . esc_html($product['quantity']) . '</td>';
        echo '</tr>';
    }

    echo '</tbody></table>';

    echo '<br><br>';
    echo '<button type="submit" name="update_cart" class="button button-primary">Save Changes</button> ';
    echo '<a href="admin.php?page=act_abandoned_carts" class="button">Cancel</a>';
    echo '</form>';
    echo '</div>';
}



// Search for products
add_action('wp_ajax_search_products', function() {
    global $wpdb;
    $query = '%' . $wpdb->esc_like($_POST['query']) . '%';

    $products = $wpdb->get_results($wpdb->prepare(
        "SELECT ID, post_title FROM {$wpdb->posts} WHERE post_type = 'product' AND post_title LIKE %s LIMIT 10",
        $query
    ));

    if ($products) {
        $response = [];
        foreach ($products as $product) {
            $product_obj = wc_get_product($product->ID);
            $response[] = [
                'id' => $product_obj->get_id(),
                'name' => $product_obj->get_name(),
                'price' => wc_price($product_obj->get_price())
            ];
        }
        wp_send_json_success($response);
    } else {
        wp_send_json_error();
    }
});

// Get variations for a product
add_action('wp_ajax_get_product_variations', function() {
    if (!isset($_POST['product_id'])) wp_send_json_error();

    $product = wc_get_product($_POST['product_id']);

    if (!$product->is_type('variable')) wp_send_json_error();

    $variations = [];
    foreach ($product->get_available_variations() as $variation) {
        $variations[] = [
            'id' => $variation['variation_id'],
            'name' => implode(', ', array_values($variation['attributes'])),
            'price' => wc_price($variation['display_price'])
        ];
    }

    wp_send_json_success($variations);
});
