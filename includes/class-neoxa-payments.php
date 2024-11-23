<?php
class Neoxa_Payments {
    private $rpc;
    
    public function init() {
        // Initialize RPC connection
        $settings = get_option('neoxa_payments_settings');
        $this->rpc = new Neoxa_RPC(
            $settings['rpc_host'],
            $settings['rpc_port'],
            $settings['rpc_user'],
            $settings['rpc_password']
        );
        
        // Add hooks
        add_action('init', array($this, 'init_hooks'));
    }
    
    public function init_hooks() {
        // Initialize payment gateway
        add_filter('woocommerce_payment_gateways', array($this, 'add_gateway_class'));
        
        // Add shortcodes
        add_shortcode('neoxa_payment_address', array($this, 'payment_address_shortcode'));
    }
    
    public function add_gateway_class($gateways) {
        $gateways[] = 'WC_Neoxa_Gateway';
        return $gateways;
    }
    
    public function payment_address_shortcode($atts) {
        // Implementation for displaying payment address
        $address = $this->rpc->get_new_address();
        return '<div class="neoxa-payment-address">' . esc_html($address) . '</div>';
    }

    public function verify_payment($address, $expected_amount, $asset = 'NEOXA') {
        try {
            $balance = $this->rpc->get_address_balance($address);
            
            if ($asset === 'NEOXA') {
                return $balance['balance'] >= $expected_amount;
            } else {
                $asset_balance = $this->rpc->get_asset_balance($address, $asset);
                return $asset_balance >= $expected_amount;
            }
        } catch (Exception $e) {
            error_log('Neoxa payment verification error: ' . $e->getMessage());
            return false;
        }
    }

    public function get_accepted_assets() {
        $settings = get_option('neoxa_payments_settings');
        return isset($settings['accepted_assets']) ? $settings['accepted_assets'] : array('NEOXA');
    }
}
