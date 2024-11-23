<?php
class Neoxa_Admin {
    private $rpc;
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_plugin_page'));
        add_action('admin_init', array($this, 'page_init'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    public function enqueue_admin_scripts($hook) {
        if ('toplevel_page_neoxa-payments' !== $hook) {
            return;
        }
        wp_enqueue_style('neoxa-admin-css', NEOXA_PAYMENTS_PLUGIN_URL . 'admin/css/admin.css', array(), NEOXA_PAYMENTS_VERSION);
        wp_enqueue_script('neoxa-admin-js', NEOXA_PAYMENTS_PLUGIN_URL . 'admin/js/admin.js', array('jquery'), NEOXA_PAYMENTS_VERSION, true);
        
        // Add nonce for AJAX
        wp_localize_script('neoxa-admin-js', 'neoxaAdmin', array(
            'nonce' => wp_create_nonce('neoxa_admin'),
            'pluginUrl' => NEOXA_PAYMENTS_PLUGIN_URL
        ));
    }
    
    public function add_plugin_page() {
        add_menu_page(
            'Neoxa Payments Settings',
            'Neoxa Payments',
            'manage_options',
            'neoxa-payments',
            array($this, 'create_admin_page'),
            NEOXA_PAYMENTS_PLUGIN_URL . 'assets/images/neoxa-logo.png'
        );
    }
    
    public function create_admin_page() {
        $settings = get_option('neoxa_payments_settings'); 
        $this->init_rpc();
        ?>
        <div class="wrap">
            <div class="neoxa-admin-header">
                <img src="<?php echo esc_url(NEOXA_PAYMENTS_PLUGIN_URL . 'assets/images/neoxa-logo.png'); ?>" 
                     alt="Neoxa Logo" 
                     class="neoxa-logo"
                     style="height: 32px; margin-right: 10px; vertical-align: middle;">
                <h2>Neoxa Payments Settings</h2>
            </div>
            
            <div class="neoxa-admin-container">
                <div class="neoxa-admin-main">
                    <form method="post" action="options.php">
                        <?php
                        settings_fields('neoxa_payments_settings');
                        do_settings_sections('neoxa-payments');
                        submit_button();
                        ?>
                    </form>
                </div>

                <div class="neoxa-admin-sidebar">
                    <div class="neoxa-admin-box">
                        <h3>Wallet Configuration Requirements</h3>
                        <p>Your Neoxa wallet's configuration file must include these settings:</p>
                        <pre>
daemon=1
txindex=1
assetindex=1
addressindex=1
spentindex=1
rpcuser=your_username
rpcpassword=your_secure_password
rpcport=8332
rpcworkqueue=64
rpcthreads=16
rpcallowip=0.0.0.0/0
rpcbind=0.0.0.0
dbcache=2048
maxmempool=512
mempoolexpiry=72
rpcworkqueue=25000
rpcthreads=16
maxconnections=125
maxuploadtarget=5000</pre>
                        <button class="button copy-config">Copy Configuration</button>
                    </div>

                    <?php if ($this->test_rpc_connection()): ?>
                    <div class="neoxa-admin-box neoxa-success">
                        <h3>✓ RPC Connection Status</h3>
                        <p>Successfully connected to Neoxa wallet!</p>
                    </div>
                    <?php else: ?>
                    <div class="neoxa-admin-box neoxa-error">
                        <h3>⚠ RPC Connection Status</h3>
                        <p>Could not connect to Neoxa wallet. Please check your settings.</p>
                        <button class="button" id="test-rpc-connection">Test Connection</button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($this->test_rpc_connection()): ?>
            <div class="neoxa-admin-assets">
                <h3>Available Assets</h3>
                <?php $this->display_available_assets(); ?>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    private function init_rpc() {
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
    }

    private function test_rpc_connection() {
        if (!$this->rpc) {
            return false;
        }
        return $this->rpc->test_connection();
    }

    private function display_available_assets() {
        if (!$this->rpc) {
            return;
        }

        try {
            $assets = $this->rpc->get_all_assets();
            $settings = get_option('neoxa_payments_settings');
            $accepted_assets = isset($settings['accepted_assets']) ? $settings['accepted_assets'] : array('NEOXA');
            ?>
            <div class="neoxa-assets-grid">
                <div class="neoxa-asset-item neoxa-main">
                    <label>
                        <input type="checkbox" name="neoxa_payments_settings[accepted_assets][]" 
                               value="NEOXA" <?php checked(in_array('NEOXA', $accepted_assets)); ?> 
                               disabled checked>
                        <span class="asset-name">NEOXA (Main Currency)</span>
                    </label>
                </div>
                <?php foreach ($assets as $name => $asset): ?>
                    <?php if ($name !== 'NEOXA'): ?>
                    <div class="neoxa-asset-item">
                        <label>
                            <input type="checkbox" name="neoxa_payments_settings[accepted_assets][]" 
                                   value="<?php echo esc_attr($name); ?>"
                                   <?php checked(in_array($name, $accepted_assets)); ?>>
                            <span class="asset-name"><?php echo esc_html($name); ?></span>
                            <?php if (isset($asset['amount'])): ?>
                            <span class="asset-supply">Supply: <?php echo esc_html($asset['amount']); ?></span>
                            <?php endif; ?>
                        </label>
                    </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            <?php
        } catch (Exception $e) {
            echo '<div class="notice notice-error"><p>Error loading assets: ' . esc_html($e->getMessage()) . '</p></div>';
        }
    }
    
    public function page_init() {
        register_setting(
            'neoxa_payments_settings',
            'neoxa_payments_settings',
            array($this, 'sanitize')
        );
        
        add_settings_section(
            'neoxa_rpc_settings',
            'RPC Settings',
            array($this, 'print_section_info'),
            'neoxa-payments'
        );
        
        add_settings_field(
            'rpc_host',
            'RPC Host',
            array($this, 'rpc_host_callback'),
            'neoxa-payments',
            'neoxa_rpc_settings'
        );
        
        add_settings_field(
            'rpc_port',
            'RPC Port',
            array($this, 'rpc_port_callback'),
            'neoxa-payments',
            'neoxa_rpc_settings'
        );
        
        add_settings_field(
            'rpc_user',
            'RPC Username',
            array($this, 'rpc_user_callback'),
            'neoxa-payments',
            'neoxa_rpc_settings'
        );
        
        add_settings_field(
            'rpc_password',
            'RPC Password',
            array($this, 'rpc_password_callback'),
            'neoxa-payments',
            'neoxa_rpc_settings'
        );
    }
    
    public function sanitize($input) {
        $new_input = array();
        
        if (isset($input['rpc_host']))
            $new_input['rpc_host'] = sanitize_text_field($input['rpc_host']);
        
        if (isset($input['rpc_port']))
            $new_input['rpc_port'] = sanitize_text_field($input['rpc_port']);
        
        if (isset($input['rpc_user']))
            $new_input['rpc_user'] = sanitize_text_field($input['rpc_user']);
        
        if (isset($input['rpc_password']))
            $new_input['rpc_password'] = sanitize_text_field($input['rpc_password']);
        
        if (isset($input['accepted_assets'])) {
            $new_input['accepted_assets'] = array_map('sanitize_text_field', $input['accepted_assets']);
            if (!in_array('NEOXA', $new_input['accepted_assets'])) {
                $new_input['accepted_assets'][] = 'NEOXA';
            }
        } else {
            $new_input['accepted_assets'] = array('NEOXA');
        }
        
        return $new_input;
    }
    
    public function print_section_info() {
        print 'Enter your Neoxa wallet RPC settings below:';
    }
    
    public function rpc_host_callback() {
        $settings = get_option('neoxa_payments_settings');
        printf(
            '<input type="text" id="rpc_host" name="neoxa_payments_settings[rpc_host]" value="%s" class="regular-text" />',
            isset($settings['rpc_host']) ? esc_attr($settings['rpc_host']) : ''
        );
    }
    
    public function rpc_port_callback() {
        $settings = get_option('neoxa_payments_settings');
        printf(
            '<input type="text" id="rpc_port" name="neoxa_payments_settings[rpc_port]" value="%s" class="regular-text" />',
            isset($settings['rpc_port']) ? esc_attr($settings['rpc_port']) : ''
        );
    }
    
    public function rpc_user_callback() {
        $settings = get_option('neoxa_payments_settings');
        printf(
            '<input type="text" id="rpc_user" name="neoxa_payments_settings[rpc_user]" value="%s" class="regular-text" />',
            isset($settings['rpc_user']) ? esc_attr($settings['rpc_user']) : ''
        );
    }
    
    public function rpc_password_callback() {
        $settings = get_option('neoxa_payments_settings');
        printf(
            '<input type="password" id="rpc_password" name="neoxa_payments_settings[rpc_password]" value="%s" class="regular-text" />',
            isset($settings['rpc_password']) ? esc_attr($settings['rpc_password']) : ''
        );
    }
}

// Initialize the admin class
$neoxa_admin = new Neoxa_Admin();
