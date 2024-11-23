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
    
    public function get_asset_data($asset_name) {
        return $this->request('getassetdata', array($asset_name));
    }

    public function test_connection() {
        try {
            $this->request('getblockcount');
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function get_all_assets() {
        try {
            $assets = $this->list_assets("", true);
            // Filter out restricted assets and qualifiers
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

    public function get_transaction($txid) {
        try {
            return $this->request('getrawtransaction', array($txid, true));
        } catch (Exception $e) {
            error_log('Error getting Neoxa transaction: ' . $e->getMessage());
            return null;
        }
    }
}
