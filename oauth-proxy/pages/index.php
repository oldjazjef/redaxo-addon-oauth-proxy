<?php
/**
 * Main page for the OAuth Proxy addon
 */

echo rex_view::title($this->i18n('oauth_proxy_title'));

// Include the subpage based on the current page parameter
include rex_be_controller::getCurrentPageObject()->getSubPath();
