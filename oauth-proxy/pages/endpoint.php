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
