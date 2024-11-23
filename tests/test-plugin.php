<?php
/**
 * Neoxa Payments Plugin Test Script
 * 
 * This script performs basic functionality tests for the Neoxa Payments plugin.
 * Run this before deploying to ensure core features are working.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Neoxa_Payments_Tests {
    private $rpc;
    private $test_results = array();
    
    public function __construct() {
        $this->init_rpc();
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
    
    public function run_tests() {
        echo "Starting Neoxa Payments Plugin Tests...\n\n";
        
        // Test RPC Connection
        $this->test_rpc_connection();
        
        // Test Asset Listing
        $this->test_asset_listing();
        
        // Test Address Generation
        $this->test_address_generation();
        
        // Test Payment Verification
        $this->test_payment_verification();
        
        // Test WooCommerce Integration
        $this->test_woocommerce_integration();
        
        // Display Results
        $this->display_results();
    }
    
    private function test_rpc_connection() {
        echo "Testing RPC Connection...\n";
        try {
            $result = $this->rpc->test_connection();
            $this->test_results['rpc_connection'] = array(
                'status' => $result ? 'PASS' : 'FAIL',
                'message' => $result ? 'Successfully connected to Neoxa wallet' : 'Failed to connect to Neoxa wallet'
            );
        } catch (Exception $e) {
            $this->test_results['rpc_connection'] = array(
                'status' => 'FAIL',
                'message' => 'Error: ' . $e->getMessage()
            );
        }
    }
    
    private function test_asset_listing() {
        echo "Testing Asset Listing...\n";
        try {
            $assets = $this->rpc->get_all_assets();
            $this->test_results['asset_listing'] = array(
                'status' => !empty($assets) ? 'PASS' : 'FAIL',
                'message' => !empty($assets) ? 'Successfully retrieved ' . count($assets) . ' assets' : 'No assets found'
            );
        } catch (Exception $e) {
            $this->test_results['asset_listing'] = array(
                'status' => 'FAIL',
                'message' => 'Error: ' . $e->getMessage()
            );
        }
    }
    
    private function test_address_generation() {
        echo "Testing Address Generation...\n";
        try {
            $address = $this->rpc->get_new_address();
            $this->test_results['address_generation'] = array(
                'status' => !empty($address) ? 'PASS' : 'FAIL',
                'message' => !empty($address) ? 'Successfully generated address: ' . $address : 'Failed to generate address'
            );
        } catch (Exception $e) {
            $this->test_results['address_generation'] = array(
                'status' => 'FAIL',
                'message' => 'Error: ' . $e->getMessage()
            );
        }
    }
    
    private function test_payment_verification() {
        echo "Testing Payment Verification...\n";
        try {
            // Generate test address
            $address = $this->rpc->get_new_address();
            $balance = $this->rpc->get_address_balance($address);
            
            $this->test_results['payment_verification'] = array(
                'status' => isset($balance['balance']) ? 'PASS' : 'FAIL',
                'message' => isset($balance['balance']) ? 'Successfully verified balance checking' : 'Failed to verify balance'
            );
        } catch (Exception $e) {
            $this->test_results['payment_verification'] = array(
                'status' => 'FAIL',
                'message' => 'Error: ' . $e->getMessage()
            );
        }
    }
    
    private function test_woocommerce_integration() {
        echo "Testing WooCommerce Integration...\n";
        
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            $this->test_results['woocommerce'] = array(
                'status' => 'FAIL',
                'message' => 'WooCommerce is not active'
            );
            return;
        }
        
        // Check if our gateway is registered
        $gateways = WC()->payment_gateways->payment_gateways();
        $has_gateway = isset($gateways['neoxa']);
        
        $this->test_results['woocommerce'] = array(
            'status' => $has_gateway ? 'PASS' : 'FAIL',
            'message' => $has_gateway ? 'Payment gateway successfully registered' : 'Payment gateway not found'
        );
    }
    
    private function display_results() {
        echo "\nTest Results:\n";
        echo "=============\n\n";
        
        foreach ($this->test_results as $test => $result) {
            echo sprintf(
                "%s: [%s]\n%s\n\n",
                ucwords(str_replace('_', ' ', $test)),
                $result['status'],
                $result['message']
            );
        }
        
        // Calculate overall status
        $failed_tests = array_filter($this->test_results, function($result) {
            return $result['status'] === 'FAIL';
        });
        
        echo "=============\n";
        echo sprintf(
            "Overall Status: [%s]\n",
            empty($failed_tests) ? 'PASS' : 'FAIL'
        );
        echo sprintf(
            "%d tests passed, %d tests failed\n",
            count($this->test_results) - count($failed_tests),
            count($failed_tests)
        );
    }
}

// Run tests if called directly
if (defined('WP_CLI') && WP_CLI) {
    $tester = new Neoxa_Payments_Tests();
    $tester->run_tests();
}
