<?php
/**
 * OAuth Proxy Addon Installation
 *
 * @author Your Name
 * @package redaxo\oauth_proxy
 */

$addon = rex_addon::get('oauth_proxy');


// Create default configuration if needed
if (!$addon->hasConfig()) {
    $addon->setConfig('client_id', '');
    $addon->setConfig('client_secret', '');
    $addon->setConfig('provider_url', '');
}

// Create necessary directories
$directories = [
    'lib',
    'pages',
    'lang'
];

foreach ($directories as $dir) {
    if (!is_dir($addon->getPath($dir))) {
        mkdir($addon->getPath($dir), 0777, true);
    }
}
