<?php
/**
 * Endpoint information page for the OAuth Proxy addon
 */

$addon = rex_addon::get('oauth_proxy');

// Get the full URL to the OAuth endpoint
$oauth_endpoint_url = rex_url::frontendController(['rex-api-call' => 'oauth_proxy']);

// Get the full URL to the proxy endpoint for API forwarding
$proxy_endpoint_url = rex_url::frontendController(['rex-api-call' => 'proxy']);

echo rex_view::info('<strong>Proxy OAuth Endpoint</strong><br>' . $oauth_endpoint_url);
echo rex_view::info('<strong>Proxy Endpoint URL:</strong><br>' . $proxy_endpoint_url);

?>

<div class="panel panel-default">
    <div class="panel-heading"><strong><?= $this->i18n('oauth_proxy_endpoint_usage') ?></strong></div>    <div class="panel-body">
        <h4>How to use the OAuth Proxy endpoint:</h4>
        
        <p>The OAuth Proxy endpoint acts as a secure intermediary between client applications and the OAuth provider. It allows clients to obtain and refresh tokens without exposing sensitive client credentials.</p>        <h5>Important Notes:</h5>
        <ul>
            <li>The proxy automatically adds <code>client_id</code> and <code>client_secret</code> to all requests</li>
            <li>Token responses include standard OAuth fields: <code>access_token</code>, <code>token_type</code>, <code>expires_in</code>, <code>refresh_token</code> (if supported)</li>
            <li>All headers from the provider's response are preserved</li>
            <li>CORS headers are added to allow cross-origin requests from any domain</li>
            <li>The proxy respects rate limits from the OAuth provider</li>
            <li>For security, store tokens in HttpOnly cookies or secure storage</li>            <li>Configure the provider URL and credentials in the Settings tab</li>
        </ul>

        <h4>OAuth Endpoint Examples</h4>
        <p>Use this endpoint to obtain OAuth tokens from your provider:</p>
        
        <div class="rex-example-code">
            <pre><code class="language-javascript">// Example: Getting an OAuth token with Axios
const axios = require('axios');

// Request an OAuth token through the oauth_proxy endpoint
axios({
  method: 'post',
  url: '<?= $oauth_endpoint_url ?>',
})
.then(response => {
  // The proxy handles storing the token, but you can also access it from the response
  console.log('Authentication successful:', response.data);
  const token = response.data.access_token;
  
  // Use token for authenticated requests
  axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
})
.catch(error => {
  console.error('Authentication failed:', error.response?.data || error.message);
});</code></pre>
        </div>

        <h4>API Proxy Endpoint Examples</h4>
        <p>Use this endpoint to make requests to external APIs through the proxy, with automatic OAuth authentication:</p>
        
        <div class="rex-example-code">
            <pre><code class="language-javascript">// Example: Making a GET request to an API through the proxy
const axios = require('axios');

// The target API you want to access
const targetApiUrl = 'https://api.example.com/data';

// Make request through the proxy
axios({
  method: 'get',
  url: '<?= $proxy_endpoint_url ?>',
  params: {
    target: targetApiUrl, // Required - the API endpoint to call
    // Any additional query parameters for the target API
    limit: 10,
    offset: 0
  },
  withCredentials: true // Important for maintaining session cookies
})
.then(response => {
  console.log('API Response:', response.data);
})
.catch(error => {
  console.error('Error:', error.response?.data || error.message);
});</code></pre>
        </div>

        <div class="rex-example-code">
            <pre><code class="language-javascript">// Example: Making a POST request through the proxy
const axios = require('axios');

// Send data to an API through the proxy
axios({
  method: 'post',
  url: '<?= $proxy_endpoint_url ?>',
  params: {
    target: 'https://api.example.com/users/create' // Target API endpoint
  },
  data: {
    name: 'John Doe',
    email: 'john@example.com'
  },
  headers: {
    'Content-Type': 'application/json'
  },
  withCredentials: true // Important for maintaining session cookies
})
.then(response => {
  console.log('User created:', response.data);
})
.catch(error => {
  console.error('Error creating user:', error.response?.data || error.message);
});</code></pre>
        </div>
    </div>
</div>
