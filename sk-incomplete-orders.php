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
        first_name VARCHAR(255) DEFAULT '',
        last_name VARCHAR(255) DEFAULT '',
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

require_once ( 'includes/enqueue.php' );
require_once ( 'includes/dashboard-menus.php' );
require_once ( 'includes/store-incomplete-orders-data.php' );
require_once ( 'includes/display-cart-items.php' );
require_once ( 'includes/edit-item.php' );
require_once ( 'includes/product-search.php' );
require_once ( 'includes/create-an-order.php' );
