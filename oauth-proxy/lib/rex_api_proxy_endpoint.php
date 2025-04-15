<?php
class rex_api_proxy_endpoint extends rex_api_function {
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
            $response = $this->handleProxy();
        } catch (Exception $e) {
            http_response_code($e->getCode() ?: 500);
            header("Content-Type: application/json");
            $response = ['error' => $e->getMessage()];
            echo json_encode($response);
            exit;
        }

        // Response is already echoed in handleProxy
        exit;
    }

    private function handleProxy() {
        // Get target URL from query parameter
        $target = rex_request('target', 'string', '');
        
        if (empty($target)) {
            throw new Exception('Target URL not specified', 400);
        }
        
        // Verify target URL
        if (!filter_var($target, FILTER_VALIDATE_URL)) {
            throw new Exception('Invalid target URL', 400);
        }
        
        // Get the access token - first try to get it from session or storage
        $access_token = $this->getStoredAccessToken();
        
        // If no token or token expired, get a new one
        if (empty($access_token)) {
            $token_data = $this->refreshToken();
            $access_token = $token_data['access_token'] ?? '';
            
            if (empty($access_token)) {
                throw new Exception('Failed to obtain access token', 500);
            }
            
            // Store the token
            $this->storeAccessToken($token_data);
        }
        
        // Handle all original query parameters 
        $query_params = $_GET;
        
        // Remove 'rex-api-call' and 'target' parameters
        unset($query_params['rex-api-call']);
        unset($query_params['target']);
        
        // If target URL already has query parameters, append new ones
        $query_string = http_build_query($query_params);
        if (!empty($query_string)) {
            $target .= (strpos($target, '?') !== false) ? '&' : '?';
            $target .= $query_string;
        }
        
        // Initialize cURL for the target request
        $ch = curl_init($target);
        
        // Set method to match the incoming request
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $_SERVER['REQUEST_METHOD']);
        
        // Get request headers and forward necessary ones
        $request_headers = $this->getRequestHeaders();
        
        // Add the Authorization header with the token
        $request_headers[] = 'Authorization: Bearer ' . $access_token;
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers);
        
        // Handle request body for POST, PUT, etc.
        $input = file_get_contents('php://input');
        if (!empty($input) && in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $input);
        }
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Consider making this configurable
        
        $response = curl_exec($ch);
        
        if ($response === false) {
            throw new Exception('Error connecting to target: ' . curl_error($ch), 500);
        }
        
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header_text = substr($response, 0, $header_size);
        $body = substr($response, $header_size);
        
        curl_close($ch);
        
        // Set response status code
        http_response_code($http_code);
        
        // Forward headers
        $this->forwardResponseHeaders($header_text);
        
        // Forward body
        echo $body;
        
        return true;
    }
    
    private function getRequestHeaders() {
        $headers = [];
        
        // Forward content-type if present
        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers[] = 'Content-Type: ' . $_SERVER['CONTENT_TYPE'];
        }
        
        // Forward other specific headers we want to preserve
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                // Convert HTTP_ACCEPT to Accept etc.
                $header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
                
                // Skip some headers we don't want to forward
                if (!in_array($header, ['Host', 'Authorization', 'Cookie'])) {
                    $headers[] = "$header: $value";
                }
            }
        }
        
        return $headers;
    }
      private function forwardResponseHeaders($header_text) {
        // Parse and forward headers
        $headers = explode("\r\n", $header_text);
        
        // Headers we set manually and don't want to duplicate from the target response
        $skip_headers = [
            'access-control-allow-origin',
            'access-control-allow-methods',
            'access-control-allow-headers',
            'access-control-allow-credentials',
            'vary',
            'content-type' // We already set Content-Type: application/json
        ];
        
        foreach ($headers as $header) {
            // Skip empty lines and HTTP status line
            if (empty($header) || strpos($header, 'HTTP/') === 0) {
                continue;
            }
            
            // Skip headers we manage ourselves or don't want to forward
            if (strpos($header, 'Transfer-Encoding:') === 0) {
                continue;
            }
            
            // Skip CORS and other headers we set manually
            $skip = false;
            foreach ($skip_headers as $skip_header) {
                if (stripos($header, $skip_header . ':') === 0) {
                    $skip = true;
                    break;
                }
            }
            
            if (!$skip) {
                header($header, false);
            }
        }
    }
    
    private function getStoredAccessToken() {
        // Check if we have a token in session
        if (isset($_SESSION['oauth_proxy_token'])) {
            $token_data = $_SESSION['oauth_proxy_token'];
            
            // Check if token is expired
            if (isset($token_data['expires_at']) && $token_data['expires_at'] > time()) {
                return $token_data['access_token'];
            }
        }
        
        return null;
    }
    
    private function storeAccessToken($token_data) {
        // Calculate expiry time
        if (isset($token_data['expires_in'])) {
            $token_data['expires_at'] = time() + (int)$token_data['expires_in'];
        }
        
        // Store in session
        $_SESSION['oauth_proxy_token'] = $token_data;
    }
    
    private function refreshToken() {
        // Use the existing OAuth endpoint to get a token
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
          
        // Prepare the OAuth request data
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
        
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        
        if ($response === false) {
            throw new Exception('Error connecting to provider: ' . curl_error($ch), 500);
        }
        
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        curl_close($ch);
        
        if ($http_code < 200 || $http_code >= 300) {
            throw new Exception('Provider returned error: ' . $response, $http_code);
        }
        
        $result = json_decode($response, true);
        
        if (!$result && json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON response from provider', 500);
        }
        
        return $result;
    }
}
?>
