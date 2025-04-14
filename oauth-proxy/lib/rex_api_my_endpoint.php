<?php
class rex_api_my_endpoint extends rex_api_function {
    protected $published = true; // Allow frontend access

    public function execute() {
        // Handle CORS headers
        header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, X-CSRF-Token");
        header("Access-Control-Allow-Credentials: true");
        header("Vary: Origin");
        header("Content-Type: application/json");

        // Handle preflight requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }

        // Process requests
        $response = [];


        try {
            switch ($_SERVER['REQUEST_METHOD']) {
                case 'GET':
                    $response = $this->handleGet();
                    break;
                case 'POST':
                    $response = $this->handlePost();
                    break;
                default:
                    throw new Exception('Method not allowed', 405);
            }
        } catch (Exception $e) {
            http_response_code($e->getCode());
            $response = ['error' => $e->getMessage()];
        }

        echo json_encode($response);
        exit;
    }

    private function handleGet() {
        return ['message' => 'GET response', 'data' => $_GET];
    }   
    
    private function handlePost() {
        // Get OAuth configuration from addon settings
        $addon = rex_addon::get('oauth_proxy');
        $provider_url = $addon->getConfig('provider_url', '');
        $client_id = $addon->getConfig('client_id', '');
        $client_secret = $addon->getConfig('client_secret', '');
        
        if (empty($provider_url)) {
            throw new Exception('Provider URL not configured', 500);
        }
        
        if (empty($client_id) || empty($client_secret)) {
            throw new Exception('Client credentials not configured', 500);
        }
          // Prepare the OAuth request data
        $post_data = [
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'grant_type' => 'client_credentials'
        ];
        
        // Initialize cURL session
        $ch = curl_init($provider_url);
        
        // Set cURL options
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        
        // Try JSON format since form-urlencoded resulted in 415 error
        $json_data = json_encode($post_data);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($json_data),
            'Accept: application/json'
        ]);
        
        curl_setopt($ch, CURLOPT_HEADER, true); // Get headers to extract cookies
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Consider removing in production
        
        // Execute the request
        $response = curl_exec($ch);
        
        if ($response === false) {
            throw new Exception('Error connecting to provider: ' . curl_error($ch), 500);
        }
        
        // Get HTTP status code
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        // Split headers and body
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header_text = substr($response, 0, $header_size);
        $body = substr($response, $header_size);
        
        curl_close($ch);
        
        // Check for successful response
        if ($http_code < 200 || $http_code >= 300) {
            throw new Exception('Provider returned error: ' . $body, $http_code);
        }
        
        // Parse the response body
        $result = json_decode($body, true);
        
        if (!$result && json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON response from provider', 500);
        }
        
        // Extract and forward cookies from the provider's response
        preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $header_text, $matches);
        
        if (!empty($matches[1])) {
            foreach ($matches[1] as $cookie) {
                header("Set-Cookie: $cookie; SameSite=None; Secure", false);
            }
        }
        
        // Return the token data
        return $result;
    }
}
?>