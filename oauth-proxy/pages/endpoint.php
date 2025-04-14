<?php
/**
 * Endpoint information page for the OAuth Proxy addon
 */

$addon = rex_addon::get('oauth_proxy');

// Get the full URL to the proxy endpoint
$endpoint_url = rex_url::frontend('redaxo/src/addons/oauth_proxy/lib/rex_api_my_endpoint.php', [], true);

echo rex_view::info('<strong>' . $this->i18n('oauth_proxy_endpoint_url') . ':</strong><br>' . $endpoint_url);

?>

<div class="panel panel-default">
    <div class="panel-heading"><strong><?= $this->i18n('oauth_proxy_endpoint_usage') ?></strong></div>    <div class="panel-body">
        <h4>How to use the OAuth Proxy endpoint:</h4>
        
        <p>The OAuth Proxy endpoint acts as a secure intermediary between client applications and the OAuth provider. It allows clients to obtain and refresh tokens without exposing sensitive client credentials.</p>
        
        <h5>Authorization Code Flow:</h5>
        <pre><code>// Step 1: Redirect user to authorization page
const authUrl = "<?= $endpoint_url ?>?action=authorize&redirect_uri=YOUR_REDIRECT_URI&scope=YOUR_SCOPES";
window.location.href = authUrl;

// Step 2: Exchange code for token (in your redirect handler)
fetch("<?= $endpoint_url ?>", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
        grant_type: "authorization_code",
        code: "CODE_FROM_REDIRECT",
        redirect_uri: "YOUR_REDIRECT_URI"
    })
})
.then(response => response.json())
.then(data => {
    // Store tokens securely
    localStorage.setItem("access_token", data.access_token);
    if (data.refresh_token) {
        localStorage.setItem("refresh_token", data.refresh_token);
    }
});</code></pre>

        <h5>Password Grant (if supported by provider):</h5>
        <pre><code>fetch("<?= $endpoint_url ?>", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
        grant_type: "password",
        username: "user@example.com",
        password: "user_password",
        scope: "YOUR_SCOPES"
    })
})
.then(response => response.json())
.then(data => {
    // Store and use tokens
    console.log(data);
});</code></pre>

        <h5>Refreshing Tokens:</h5>
        <pre><code>fetch("<?= $endpoint_url ?>", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
        grant_type: "refresh_token",
        refresh_token: "YOUR_REFRESH_TOKEN"
    })
})
.then(response => response.json())
.then(data => {
    // Update stored tokens
    localStorage.setItem("access_token", data.access_token);
    if (data.refresh_token) {
        localStorage.setItem("refresh_token", data.refresh_token);
    }
});</code></pre>

        <h5>Using with JavaScript Frameworks:</h5>
        <div class="row">
            <div class="col-md-6">
                <strong>Vue.js Example:</strong>
                <pre><code>// In your authentication service
async login(username, password) {
  try {
    const response = await fetch("<?= $endpoint_url ?>", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        grant_type: "password",
        username,
        password
      })
    });
    const data = await response.json();
    return data;
  } catch (error) {
    console.error("Authentication error:", error);
    throw error;
  }
}</code></pre>
            </div>
            <div class="col-md-6">
                <strong>Using Axios:</strong>
                <pre><code>axios.post("<?= $endpoint_url ?>", {
  grant_type: "password",
  username: "user@example.com",
  password: "user_password"
})
.then(response => {
  // Handle response
  const token = response.data.access_token;
  // Use token for authenticated requests
  axios.defaults.headers.common['Authorization'] = 
    `Bearer ${token}`;
})
.catch(error => {
  console.error("Error:", error);
});</code></pre>
            </div>
        </div>

        <h5>Important Notes:</h5>
        <ul>
            <li>The proxy automatically adds <code>client_id</code> and <code>client_secret</code> to all requests</li>
            <li>Token responses include standard OAuth fields: <code>access_token</code>, <code>token_type</code>, <code>expires_in</code>, <code>refresh_token</code> (if supported)</li>
            <li>All headers from the provider's response are preserved</li>
            <li>CORS headers are added to allow cross-origin requests from any domain</li>
            <li>The proxy respects rate limits from the OAuth provider</li>
            <li>For security, store tokens in HttpOnly cookies or secure storage</li>
            <li>Configure the provider URL and credentials in the Settings tab</li>
        </ul>
    </div>
</div>
