<?php
/**
 * Settings page for the OAuth Proxy addon
 */

$addon = rex_addon::get('oauth_proxy');

// Process form submission
if (rex_post('submit', 'boolean')) {
    // Get form values
    $client_id = rex_post('client_id', 'string');
    $client_secret = rex_post('client_secret', 'string');
    $provider_url = rex_post('provider_url', 'string');
    
    // Save configuration
    $addon->setConfig('client_id', $client_id);
    $addon->setConfig('client_secret', $client_secret);
    $addon->setConfig('provider_url', $provider_url);
    
    // Show success message
    echo rex_view::success($this->i18n('oauth_proxy_settings_saved'));
}

// Get current configuration
$client_id = $addon->getConfig('client_id', '');
$client_secret = $addon->getConfig('client_secret', '');
$provider_url = $addon->getConfig('provider_url', '');
$redirect_uri = $addon->getConfig('redirect_uri', '');
$scope = $addon->getConfig('scope', '');

// Create form
$form = '';
$form .= '<div class="rex-form">';
$form .= '  <form action="' . rex_url::currentBackendPage() . '" method="post">';
$form .= '    <fieldset>';
$form .= '      <div class="panel panel-edit">';
$form .= '        <header class="panel-heading"><div class="panel-title">' . $this->i18n('oauth_proxy_settings') . '</div></header>';
$form .= '        <div class="panel-body">';
$form .= '          <div class="form-group">';
$form .= '            <label for="client_id">' . $this->i18n('oauth_proxy_client_id') . '</label>';
$form .= '            <input class="form-control" type="text" id="client_id" name="client_id" value="' . htmlspecialchars($client_id) . '" />';
$form .= '          </div>';
$form .= '          <div class="form-group">';
$form .= '            <label for="client_secret">' . $this->i18n('oauth_proxy_client_secret') . '</label>';
$form .= '            <input class="form-control" type="text" id="client_secret" name="client_secret" value="' . htmlspecialchars($client_secret) . '" />';
$form .= '          </div>';
$form .= '          <div class="form-group">';
$form .= '            <label for="provider_url">' . $this->i18n('oauth_proxy_provider_url') . '</label>';
$form .= '            <input class="form-control" type="text" id="provider_url" name="provider_url" value="' . htmlspecialchars($provider_url) . '" />';
$form .= '          </div>';
$form .= '        </div>';
$form .= '        <footer class="panel-footer">';
$form .= '          <div class="rex-form-panel-footer">';
$form .= '            <div class="btn-toolbar">';
$form .= '              <button class="btn btn-save rex-form-aligned" type="submit" name="submit" value="1">' . $this->i18n('oauth_proxy_save') . '</button>';
$form .= '            </div>';
$form .= '          </div>';
$form .= '        </footer>';
$form .= '      </div>';
$form .= '    </fieldset>';
$form .= '  </form>';
$form .= '</div>';

// Output form
echo $form;
