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
        $addon = rex_addon::get('oauth_proxy');
        $provider_url = $addon->getConfig('provider_url', '');
        $client_id = $addon->getConfig('client_id', '');
        $client_secret = $addon->getConfig('client_secret', '');
        $grant_type = $addon->getConfig('grant_type', 'client_credentials');
        
        if (empty($provider_url)) {
            throw new Exception('Provider URL not configured', 500);
        }
        
        if (empty($client_id) || empty($client_secret)) {
            throw new Exception('Client credentials not configured', 500);
        }
          
        // Prepare the OAuth request data - only include grant_type in POST body
        $post_data = [
            'grant_type' => $grant_type
        ];
        
        // Initialize cURL session
        $ch = curl_init($provider_url);
        
        // Set cURL options
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        
        // Use application/x-www-form-urlencoded as per OAuth 2.0 standard
        $post_fields = http_build_query($post_data);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
        
        // Create the Basic Authentication header using client_id and client_secret
        $auth = base64_encode($client_id . ':' . $client_secret);
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Basic ' . $auth,
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: application/json'
        ]);
        
        curl_setopt($ch, CURLOPT_HEADER, true); // Get headers to extract cookies

        // TODO verify if needed to remove
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
        
        $response = curl_exec($ch);
        
        if ($response === false) {
            throw new Exception('Error connecting to provider: ' . curl_error($ch), 500);
        }
        
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header_text = substr($response, 0, $header_size);
        $body = substr($response, $header_size);
        
        curl_close($ch);
        
        if ($http_code < 200 || $http_code >= 300) {
            throw new Exception('Provider returned error: ' . $body, $http_code);
        }
        
        $result = json_decode($body, true);
        
        if (!$result && json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON response from provider', 500);
        }
        
        // Extract and forward cookies from the provider's response in case it uses cookies to store token information
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