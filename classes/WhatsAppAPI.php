<?php
// classes/WhatsAppAPI.php
class WhatsAppAPI {
    private $api_key;
    private $api_url;
    private $db;

    public function __construct($database) {
        $this->db = $database;
        $this->loadConfig();
    }

    private function loadConfig() {
        $query = "SELECT api_key, api_url FROM api_config WHERE is_active = 1 LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $config = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($config) {
            $this->api_key = $config['api_key'];
            $this->api_url = $config['api_url'];
        } else {
            throw new Exception("API configuration not found");
        }
    }

    public function sendMessage($target, $message, $isGroup = false) {
        $curl = curl_init();
        
        $postData = [
            'target' => $target,
            'message' => $message,
            'countryCode' => '62' // Indonesia
        ];

        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->api_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_HTTPHEADER => array(
                'Authorization: ' . $this->api_key
            ),
        ));

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        $result = json_decode($response, true);
        
        return [
            'success' => $httpCode == 200 && isset($result['status']) && $result['status'] == true,
            'response' => $result,
            'http_code' => $httpCode
        ];
    }

    public function testConnection() {
        $curl = curl_init();
        
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.fonnte.com/device',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_HTTPHEADER => array(
                'Authorization: ' . $this->api_key
            ),
        ));

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        return [
            'success' => $httpCode == 200,
            'response' => json_decode($response, true),
            'http_code' => $httpCode
        ];
    }
}
?>