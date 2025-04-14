<?php
if (rex::isFrontend()) {
    rex_api_function::register('oauth_proxy', 'rex_api_my_endpoint');
}
?>