<?php 
/**
* ALL ENQUEUE FILES
*/ 

add_action( 'wp_enqueue_scripts', function() { 
    wp_enqueue_style( 'ss-core-style', SS_ASSETS_PATH. '/sk-style.css', array(), SS_VERSION );
    wp_enqueue_script( 'ss-default-js', SS_ASSETS_PATH. '/js/checkout-tracker.js', array( 'jquery' ), SS_VERSION, true );
    wp_localize_script( 'ss-default-js', 'adminAjax', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
});

add_action('admin_enqueue_scripts', function() {
    wp_enqueue_script('ss-admin-product-delete', SS_ASSETS_PATH . '/js/delete-product-on-edit.js', array(), SS_VERSION, true);
    wp_enqueue_script('ss-admin-product-search', SS_ASSETS_PATH . '/js/admin.js', array('jquery'), SS_VERSION, true);
    wp_localize_script('ss-admin-product-search', 'adminAjax', array('ajax_url' => admin_url('admin-ajax.php')));
});
