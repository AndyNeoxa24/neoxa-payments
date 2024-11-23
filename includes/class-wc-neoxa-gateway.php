<?php
if (!defined('ABSPATH')) {
    exit;
}

class WC_Neoxa_Gateway extends WC_Payment_Gateway {
    private $rpc;
    
    public function __construct() {
        $this->id = 'neoxa';
        $this->icon = NEOXA_PAYMENTS_PLUGIN_URL . 'assets/images/neoxa-logo.png';
        $this->has_fields = true;
        $this->method_title = 'Neoxa Payments';
        $this->method_description = 'Accept Neoxa cryptocurrency and asset payments';
        
        // Load settings
        $this->init_form_fields();
        $this->init_settings();
        
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        
        // Initialize RPC connection
        $settings = get_option('neoxa_payments_settings');
        if (!empty($settings['rpc_host']) && !empty($settings['rpc_port']) && 
            !empty($settings['rpc_user']) && !empty($settings['rpc_password'])) {
            $this->rpc = new Neoxa_RPC(
                $settings['rpc_host'],
                $settings['rpc_port'],
                $settings['rpc_user'],
                $settings['rpc_password']
            );
        }
        
        // Hooks
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_thankyou_' . $this->id, array($this, 'thank_you_page'));
        add_action('woocommerce_email_before_order_table', array($this, 'email_instructions'), 10, 3);
        
        // Payment verification hooks
        add_action('woocommerce_order_status_on-hold_to_processing', array($this, 'payment_complete'));
        add_action('woocommerce_order_status_on-hold_to_completed', array($this, 'payment_complete'));
        add_action('woocommerce_order_status_pending_to_processing', array($this, 'payment_complete'));
        add_action('woocommerce_order_status_pending_to_completed', array($this, 'payment_complete'));

        // Add custom styling for the gateway icon
        add_action('wp_head', array($this, 'add_icon_styles'));
    }

    public function add_icon_styles() {
        ?>
        <style type="text/css">
            .payment_method_neoxa img {
                max-height: 32px;
                vertical-align: middle;
            }
        </style>
        <?php
    }
    
    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title' => 'Enable/Disable',
                'type' => 'checkbox',
                'label' => 'Enable Neoxa Payments',
                'default' => 'yes'
            ),
            'title' => array(
                'title' => 'Title',
                'type' => 'text',
                'description' => 'Payment method title that the customer will see during checkout.',
                'default' => 'Neoxa Cryptocurrency',
                'desc_tip' => true,
            ),
            'description' => array(
                'title' => 'Description',
                'type' => 'textarea',
                'description' => 'Payment method description that the customer will see during checkout.',
                'default' => 'Pay with Neoxa cryptocurrency or assets.',
                'desc_tip' => true,
            ),
            'confirmation_threshold' => array(
                'title' => 'Confirmation Threshold',
                'type' => 'number',
                'description' => 'Number of confirmations required before accepting payment.',
                'default' => '6',
                'desc_tip' => true,
            )
        );
    }
    
    public function payment_fields() {
        if ($description = $this->get_description()) {
            echo wpautop(wptexturize($description));
        }
        
        $settings = get_option('neoxa_payments_settings');
        $accepted_assets = isset($settings['accepted_assets']) ? $settings['accepted_assets'] : array('NEOXA');
        
        echo '<div class="neoxa-payment-assets form-row form-row-wide">';
        echo '<label>Select Payment Asset</label>';
        echo '<select name="neoxa_payment_asset" class="select">';
        
        foreach ($accepted_assets as $asset) {
            echo '<option value="' . esc_attr($asset) . '">' . esc_html($asset) . '</option>';
        }
        
        echo '</select>';
        echo '</div>';
    }
    
    public function process_payment($order_id) {
        global $woocommerce;
        $order = wc_get_order($order_id);
        
        if (!$this->rpc) {
            wc_add_notice('Payment error: Neoxa RPC connection not configured.', 'error');
            return;
        }
        
        try {
            // Generate new payment address
            $payment_address = $this->rpc->get_new_address();
            
            if (!$payment_address) {
                throw new Exception('Could not generate payment address.');
            }
            
            // Get selected asset
            $payment_asset = sanitize_text_field($_POST['neoxa_payment_asset']);
            if (!in_array($payment_asset, get_option('neoxa_payments_settings')['accepted_assets'])) {
                throw new Exception('Invalid payment asset selected.');
            }
            
            // Store payment details
            update_post_meta($order_id, '_neoxa_payment_address', $payment_address);
            update_post_meta($order_id, '_neoxa_payment_asset', $payment_asset);
            update_post_meta($order_id, '_neoxa_payment_amount', $order->get_total());
            
            // Update order status
            $order->update_status('on-hold', 'Awaiting Neoxa payment.');
            
            // Reduce stock levels
            wc_reduce_stock_levels($order_id);
            
            // Remove cart
            $woocommerce->cart->empty_cart();
            
            // Return thankyou redirect
            return array(
                'result' => 'success',
                'redirect' => $this->get_return_url($order)
            );
            
        } catch (Exception $e) {
            wc_add_notice('Payment error: ' . $e->getMessage(), 'error');
            return;
        }
    }
    
    public function thank_you_page($order_id) {
        $order = wc_get_order($order_id);
        if ($order->get_payment_method() !== $this->id) {
            return;
        }
        
        $payment_address = get_post_meta($order_id, '_neoxa_payment_address', true);
        $payment_asset = get_post_meta($order_id, '_neoxa_payment_asset', true);
        $payment_amount = get_post_meta($order_id, '_neoxa_payment_amount', true);
        
        if ($payment_address && $payment_asset && $payment_amount) {
            ?>
            <h2>Neoxa Payment Details</h2>
            <div class="neoxa-payment-details">
                <p><strong>Payment Address:</strong> 
                   <span class="payment-address"><?php echo esc_html($payment_address); ?></span>
                   <button class="copy-address" data-address="<?php echo esc_attr($payment_address); ?>">
                       Copy
                   </button>
                </p>
                <p><strong>Asset:</strong> 
                   <?php echo esc_html($payment_asset); ?>
                </p>
                <p><strong>Amount:</strong> 
                   <?php echo esc_html($payment_amount); ?> <?php echo esc_html($payment_asset); ?>
                </p>
                <div class="qr-code">
                    <?php
                    $qr_data = "neoxa:{$payment_address}?amount={$payment_amount}&asset={$payment_asset}";
                    echo '<img src="https://chart.googleapis.com/chart?chs=200x200&cht=qr&chl=' . urlencode($qr_data) . '" alt="Payment QR Code">';
                    ?>
                </div>
            </div>
            <?php
        }
    }
    
    public function email_instructions($order, $sent_to_admin, $plain_text = false) {
        if ($sent_to_admin || $order->get_payment_method() !== $this->id || $order->get_status() !== 'on-hold') {
            return;
        }
        
        $payment_address = get_post_meta($order->get_id(), '_neoxa_payment_address', true);
        $payment_asset = get_post_meta($order->get_id(), '_neoxa_payment_asset', true);
        $payment_amount = get_post_meta($order->get_id(), '_neoxa_payment_amount', true);
        
        if ($payment_address && $payment_asset && $payment_amount) {
            echo '<h2>Payment Details</h2>' . "\n\n";
            echo 'Please send exactly ' . $payment_amount . ' ' . $payment_asset . "\n";
            echo 'To address: ' . $payment_address . "\n\n";
        }
    }
    
    public function payment_complete($order_id) {
        $order = wc_get_order($order_id);
        if ($order->get_payment_method() !== $this->id) {
            return;
        }
        
        $payment_address = get_post_meta($order_id, '_neoxa_payment_address', true);
        $payment_asset = get_post_meta($order_id, '_neoxa_payment_asset', true);
        $payment_amount = get_post_meta($order_id, '_neoxa_payment_amount', true);
        
        try {
            if ($payment_asset === 'NEOXA') {
                $balance = $this->rpc->get_address_balance($payment_address);
                if ($balance['balance'] >= $payment_amount) {
                    $order->payment_complete();
                }
            } else {
                $asset_balance = $this->rpc->get_asset_balance($payment_address, $payment_asset);
                if ($asset_balance >= $payment_amount) {
                    $order->payment_complete();
                }
            }
        } catch (Exception $e) {
            $order->add_order_note('Payment verification failed: ' . $e->getMessage());
        }
    }
}
