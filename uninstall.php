<?php
// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete plugin options
delete_option('neoxa_payments_settings');

// Get all orders with Neoxa payment method
$args = array(
    'payment_method' => 'neoxa',
    'limit' => -1
);

$orders = wc_get_orders($args);

// Remove Neoxa-specific metadata from orders
foreach ($orders as $order) {
    delete_post_meta($order->get_id(), '_neoxa_payment_address');
    delete_post_meta($order->get_id(), '_neoxa_payment_asset');
    delete_post_meta($order->get_id(), '_neoxa_payment_amount');
}

// Remove custom order status
global $wpdb;
$wpdb->delete(
    $wpdb->prefix . 'posts',
    array('post_type' => 'shop_order_status', 'post_name' => 'wc-neoxa-pending'),
    array('%s', '%s')
);

// Clear any scheduled cron jobs
wp_clear_scheduled_hook('check_neoxa_payments');

// Remove any transients we may have created
delete_transient('neoxa_rpc_connection_status');

// Clean up any custom user meta if we added any
// $wpdb->delete($wpdb->prefix . 'usermeta', array('meta_key' => 'neoxa_custom_meta'), array('%s'));

// Optional: Remove any custom database tables if we created any
// $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}neoxa_custom_table");

// Clear any cached data
wp_cache_flush();
