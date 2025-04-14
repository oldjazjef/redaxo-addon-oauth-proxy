<?php
/**
 * OAuth Proxy Addon Uninstall
 *
 * @author Your Name
 * @package redaxo\oauth_proxy
 */

$addon = rex_addon::get('oauth_proxy');

// Remove configuration
$addon->removeConfig();
