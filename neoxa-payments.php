<?php
/**
 * Plugin Name: Neoxa Payments
 * Plugin URI: https://github.com/AndyNeoxa24/neoxa-payments
 * Description: Accept Neoxa and Neoxa asset payments on your WordPress site
 * Version: 1.0.1
 * Author: Andy Niemand - Neoxa Founder
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: neoxa-payments
 * WC requires at least: 3.0.0
 * WC tested up to: 8.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('NEOXA_PAYMENTS_VERSION', '1.0.1');
define('NEOXA_PAYMENTS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('NEOXA_PAYMENTS_PLUGIN_URL', plugin_dir_url(__FILE__));

// Check if WooCommerce is active
function neoxa_payments_check_woocommerce() {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', 'neoxa_payments_missing_wc_notice');
        return false;
    }
    return true;
}

// WooCommerce missing notice
function neoxa_payments_missing_wc_notice() {
    ?>
    <div class="error">
        <p><?php _e('Neoxa Payments requires WooCommerce to be installed and active.', 'neoxa-payments'); ?></p>
    </div>
    <?php
}

// Initialize the plugin
function neoxa_payments_init() {
    if (!neoxa_payments_check_woocommerce()) {
        return;
    }

    // Include required files
    require_once NEOXA_PAYMENTS_PLUGIN_DIR . 'includes/class-neoxa-payments.php';
    require_once NEOXA_PAYMENTS_PLUGIN_DIR . 'includes/class-neoxa-rpc.php';
    require_once NEOXA_PAYMENTS_PLUGIN_DIR . 'admin/class-neoxa-admin.php';
    
    // Only load gateway class if WooCommerce is active
    if (class_exists('WC_Payment_Gateway')) {
        require_once NEOXA_PAYMENTS_PLUGIN_DIR . 'includes/class-wc-neoxa-gateway.php';
    }
    
    // Initialize main plugin class
    $plugin = new Neoxa_Payments();
    $plugin->init();
    
    // Add payment gateway
    add_filter('woocommerce_payment_gateways', 'neoxa_add_gateway_class');
    
    // Load plugin text domain
    load_plugin_textdomain('neoxa-payments', false, dirname(plugin_basename(__FILE__)) . '/languages/');
}
add_action('plugins_loaded', 'neoxa_payments_init');

// Add the Neoxa Gateway to WooCommerce
function neoxa_add_gateway_class($gateways) {
    if (class_exists('WC_Payment_Gateway')) {
        $gateways[] = 'WC_Neoxa_Gateway';
    }
    return $gateways;
}

// Register activation hook
register_activation_hook(__FILE__, 'neoxa_payments_activate');
function neoxa_payments_activate() {
    // Check PHP version
    if (version_compare(PHP_VERSION, '7.0', '<')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die('Neoxa Payments requires PHP 7.0 or higher.');
    }
    
    // Check if WooCommerce is active
    if (!class_exists('WooCommerce')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die('Neoxa Payments requires WooCommerce to be installed and active.');
    }
    
    // Create necessary database tables if needed
    // Set default options
    if (!get_option('neoxa_payments_settings')) {
        add_option('neoxa_payments_settings', array(
            'rpc_host' => 'localhost',
            'rpc_port' => '8332',
            'rpc_user' => '',
            'rpc_password' => '',
            'accepted_assets' => array('NEOXA') // Default to accepting NEOXA
        ));
    }
    
    // Create required directories
    $upload_dir = wp_upload_dir();
    $neoxa_dir = $upload_dir['basedir'] . '/neoxa-payments';
    if (!file_exists($neoxa_dir)) {
        wp_mkdir_p($neoxa_dir);
    }
    
    // Create .htaccess to protect sensitive files
    $htaccess_file = $neoxa_dir . '/.htaccess';
    if (!file_exists($htaccess_file)) {
        $htaccess_content = "deny from all\n";
        file_put_contents($htaccess_file, $htaccess_content);
    }
    
    // Flush rewrite rules
    flush_rewrite_rules();
}

// Register deactivation hook
register_deactivation_hook(__FILE__, 'neoxa_payments_deactivate');
function neoxa_payments_deactivate() {
    // Cleanup if necessary
    flush_rewrite_rules();
}

// Add settings link on plugin page
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'neoxa_payments_settings_link');
function neoxa_payments_settings_link($links) {
    $settings_link = '<a href="admin.php?page=neoxa-payments">' . __('Settings', 'neoxa-payments') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}

// Add AJAX handlers for admin
add_action('wp_ajax_test_neoxa_rpc', 'neoxa_test_rpc_connection');
function neoxa_test_rpc_connection() {
    check_ajax_referer('neoxa_admin', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Unauthorized'));
        return;
    }
    
    $settings = get_option('neoxa_payments_settings');
    $rpc = new Neoxa_RPC(
        $settings['rpc_host'],
        $settings['rpc_port'],
        $settings['rpc_user'],
        $settings['rpc_password']
    );
    
    try {
        if ($rpc->test_connection()) {
            wp_send_json_success(array('message' => 'Connection successful'));
        } else {
            wp_send_json_error(array('message' => 'Connection failed'));
        }
    } catch (Exception $e) {
        wp_send_json_error(array('message' => $e->getMessage()));
    }
}

// Add custom order status for pending Neoxa payments
add_action('init', 'register_neoxa_order_status');
function register_neoxa_order_status() {
    register_post_status('wc-neoxa-pending', array(
        'label' => 'Neoxa Payment Pending',
        'public' => true,
        'exclude_from_search' => false,
        'show_in_admin_all_list' => true,
        'show_in_admin_status_list' => true,
        'label_count' => _n_noop('Neoxa Payment Pending <span class="count">(%s)</span>',
            'Neoxa Payment Pending <span class="count">(%s)</span>')
    ));
}

add_filter('wc_order_statuses', 'add_neoxa_order_status');
function add_neoxa_order_status($order_statuses) {
    $order_statuses['wc-neoxa-pending'] = 'Neoxa Payment Pending';
    return $order_statuses;
}

// Schedule payment verification cron job
add_action('wp', 'schedule_neoxa_payment_check');
function schedule_neoxa_payment_check() {
    if (!wp_next_scheduled('check_neoxa_payments')) {
        wp_schedule_event(time(), 'every_minute', 'check_neoxa_payments');
    }
}

// Add custom cron schedule
add_filter('cron_schedules', 'add_neoxa_cron_interval');
function add_neoxa_cron_interval($schedules) {
    $schedules['every_minute'] = array(
        'interval' => 60,
        'display'  => __('Every Minute', 'neoxa-payments')
    );
    return $schedules;
}

// Cron job to check pending payments
add_action('check_neoxa_payments', 'verify_pending_neoxa_payments');
function verify_pending_neoxa_payments() {
    $orders = wc_get_orders(array(
        'status' => 'neoxa-pending',
        'limit' => -1
    ));
    
    $settings = get_option('neoxa_payments_settings');
    $rpc = new Neoxa_RPC(
        $settings['rpc_host'],
        $settings['rpc_port'],
        $settings['rpc_user'],
        $settings['rpc_password']
    );
    
    foreach ($orders as $order) {
        $payment_address = get_post_meta($order->get_id(), '_neoxa_payment_address', true);
        $payment_asset = get_post_meta($order->get_id(), '_neoxa_payment_asset', true);
        $payment_amount = get_post_meta($order->get_id(), '_neoxa_payment_amount', true);
        
        try {
            if ($payment_asset === 'NEOXA') {
                $balance = $rpc->get_address_balance($payment_address);
                if ($balance['balance'] >= $payment_amount) {
                    $order->payment_complete();
                    $order->add_order_note(__('Neoxa payment received and verified.', 'neoxa-payments'));
                }
            } else {
                $asset_balance = $rpc->get_asset_balance($payment_address, $payment_asset);
                if ($asset_balance >= $payment_amount) {
                    $order->payment_complete();
                    $order->add_order_note(__('Neoxa asset payment received and verified.', 'neoxa-payments'));
                }
            }
        } catch (Exception $e) {
            $order->add_order_note(__('Payment verification failed: ', 'neoxa-payments') . $e->getMessage());
        }
    }
}
