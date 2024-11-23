<?php
class Neoxa_RPC {
    private $host;
    private $port;
    private $username;
    private $password;
    private $timeout = 30;
    
    public function __construct($host, $port, $username, $password) {
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
    }
    
    private function request($method, $params = array()) {
        $url = sprintf('http://%s:%s', $this->host, $this->port);
        
        $headers = array(
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode($this->username . ':' . $this->password)
        );
        
        $data = array(
            'jsonrpc' => '2.0',
            'id' => time(),
            'method' => $method,
            'params' => $params
        );
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception('RPC Error: ' . $error);
        }
        
        $result = json_decode($response, true);
        if (isset($result['error']) && !empty($result['error'])) {
            throw new Exception('RPC Error: ' . $result['error']['message']);
        }
        
        return isset($result['result']) ? $result['result'] : null;
    }
    
    public function get_new_address() {
        return $this->request('getnewaddress');
    }
    
    public function list_assets($asset = "", $verbose = true) {
        return $this->request('listassets', array($asset, $verbose));
    }
    
    public function get_address_balance($address) {
        return $this->request('getaddressbalance', array(array("addresses" => array($address))));
    }
    
    public function get_asset_balance($address, $asset_name) {
        $balances = $this->request('listassetbalancesbyaddress', array($address));
        return isset($balances[$asset_name]) ? $balances[$asset_name] : 0;
    }

    public function get_address_deltas($address) {
        return $this->request('getaddressdeltas', array(array("addresses" => array($address))));
    }

    public function get_address_mempool($address) {
        return $this->request('getaddressmempool', array(array("addresses" => array($address))));
    }

    public function get_address_utxos($address) {
        return $this->request('getaddressutxos', array(array("addresses" => array($address))));
    }

    public function get_asset_data($asset_name) {
        return $this->request('getassetdata', array($asset_name));
    }

    public function list_addresses_by_asset($asset_name, $onlytotal = false) {
        return $this->request('listaddressesbyasset', array($asset_name, $onlytotal));
    }

    public function get_asset_snapshot($asset_name, $block_height) {
        return $this->request('getsnapshot', array($asset_name, $block_height));
    }

    public function get_block_count() {
        return $this->request('getblockcount');
    }

    public function get_mempool_info() {
        return $this->request('getmempoolinfo');
    }

    public function get_tx_out($txid, $n, $include_mempool = true) {
        return $this->request('gettxout', array($txid, $n, $include_mempool));
    }

    public function verify_payment($address, $expected_amount, $asset = 'NEOXA') {
        try {
            $mempool_txs = $this->get_address_mempool($address);
            $mempool_balance = 0;
            foreach ($mempool_txs as $tx) {
                if (isset($tx['satoshis'])) {
                    $mempool_balance += $tx['satoshis'];
                }
            }

            $balance = $this->get_address_balance($address);
            $total_balance = ($balance['balance'] ?? 0) + $mempool_balance;

            if ($asset === 'NEOXA') {
                return $total_balance >= $expected_amount;
            } else {
                $asset_data = $this->get_asset_data($asset);
                if (!$asset_data) {
                    throw new Exception('Asset not found: ' . $asset);
                }

                $asset_balance = $this->get_asset_balance($address, $asset);
                return $asset_balance >= $expected_amount;
            }
        } catch (Exception $e) {
            error_log('Neoxa payment verification error: ' . $e->getMessage());
            return false;
        }
    }

    public function test_connection() {
        try {
            $this->get_block_count();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function get_all_assets() {
        try {
            $assets = $this->list_assets("", true);
            return array_filter($assets, function($asset) {
                return !isset($asset['restricted']) && !isset($asset['qualifier']);
            });
        } catch (Exception $e) {
            error_log('Error fetching Neoxa assets: ' . $e->getMessage());
            return array();
        }
    }

    public function validate_address($address) {
        try {
            $result = $this->request('validateaddress', array($address));
            return isset($result['isvalid']) ? $result['isvalid'] : false;
        } catch (Exception $e) {
            error_log('Error validating Neoxa address: ' . $e->getMessage());
            return false;
        }
    }
}
