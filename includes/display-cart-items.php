<?php 
/**
* DISPLAY CART ITEMS
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

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
        <a href="?page=abandoned-carts&action=delete&id=' . esc_attr($cart->id) . '" onclick="return confirm(\'Are you sure you want to delete this cart?\');">Delete</a>
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
         wp_redirect(admin_url('admin.php?page=abandoned-carts'));
        exit;
    }
}
