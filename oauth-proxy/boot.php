<?php
if (rex::isFrontend()) {
    // Register the OAuth token endpoint
    rex_api_function::register('oauth_proxy', 'rex_api_my_endpoint');
    
    // Register the proxy endpoint
    rex_api_function::register('proxy', 'rex_api_proxy_endpoint');
    
    // Start session to store OAuth tokens
    if (!session_id()) {
        session_start();
    }
}
?>