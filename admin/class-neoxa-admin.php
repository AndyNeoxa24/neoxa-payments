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
            'dashicons-money-alt'
        );
    }
    
    public function create_admin_page() {
        $settings = get_option('neoxa_payments_settings'); 
        $this->init_rpc();
        ?>
        <div class="wrap">
            <h1>Neoxa Payments Settings</h1>
            
            <div class="neoxa-admin-container">
                <div class="neoxa-admin-main">
                    <div class="neoxa-admin-box">
                        <h2>RPC Configuration</h2>
                        <form method="post" action="options.php">
                            <?php
                            settings_fields('neoxa_payments_settings');
                            do_settings_sections('neoxa-payments');
                            ?>

                            <?php if ($this->test_rpc_connection()): ?>
                                <div class="neoxa-admin-box neoxa-success">
                                    <h3>✓ RPC Connection Status</h3>
                                    <p>Successfully connected to Neoxa wallet!</p>
                                </div>

                                <div class="neoxa-admin-box">
                                    <h3>Asset Selection</h3>
                                    <?php $this->display_available_assets(); ?>
                                </div>
                            <?php else: ?>
                                <div class="neoxa-admin-box neoxa-error">
                                    <h3>⚠ RPC Connection Status</h3>
                                    <p>Could not connect to Neoxa wallet. Please check your settings.</p>
                                    <button type="button" class="button" id="test-rpc-connection">Test Connection</button>
                                </div>
                            <?php endif; ?>

                            <?php submit_button(); ?>
                        </form>
                    </div>
                </div>

                <div class="neoxa-admin-sidebar">
                    <div class="neoxa-admin-box">
                        <h3>Wallet Configuration Requirements</h3>
                        <p>Your Neoxa wallet's configuration file must include these settings:</p>
                        <div class="config-box">
                            <pre>daemon=1
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
                        </div>
                        <button type="button" class="button copy-config">Copy Configuration</button>
                    </div>
                </div>
            </div>
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
            <div class="neoxa-asset-selection">
                <p>Select which assets you want to accept as payment:</p>
                
                <!-- NEOXA is always accepted -->
                <input type="hidden" name="neoxa_payments_settings[accepted_assets][]" value="NEOXA">
                
                <div class="asset-select-container">
                    <select name="neoxa_payments_settings[accepted_assets][]" multiple="multiple" class="asset-select">
                        <option value="NEOXA" selected disabled>NEOXA (Main Currency - Always Enabled)</option>
                        <?php foreach ($assets as $name => $asset): ?>
                            <?php if ($name !== 'NEOXA'): ?>
                                <option value="<?php echo esc_attr($name); ?>" 
                                        <?php selected(in_array($name, $accepted_assets)); ?>>
                                    <?php echo esc_html($name); ?>
                                    <?php if (isset($asset['amount'])): ?>
                                        (Supply: <?php echo esc_html($asset['amount']); ?>)
                                    <?php endif; ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <p class="description">Hold Ctrl (Windows) or Command (Mac) to select multiple assets.</p>
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
