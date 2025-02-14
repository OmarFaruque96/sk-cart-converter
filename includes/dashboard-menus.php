<?php 
/**
* ADMIN MENUS
*/ 

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

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
