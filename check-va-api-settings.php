<?php
/**
 * Check current VA API settings in the database
 */

// Load WordPress
define('WP_USE_THEMES', false);
require_once('../../../wp-load.php');

// Get VA API settings
$settings = get_option('md_settings_va_api', array());

echo "=== Current VA API Settings ===\n";
echo "Enabled: " . (isset($settings['enabled']) ? ($settings['enabled'] ? 'Yes' : 'No') : 'Not set') . "\n";
echo "API Key: '" . (isset($settings['api_key']) ? $settings['api_key'] : 'Not set') . "'\n";
echo "API Key Length: " . (isset($settings['api_key']) ? strlen($settings['api_key']) : 0) . " characters\n";
echo "API URL: '" . (isset($settings['api_url']) ? $settings['api_url'] : 'Not set') . "'\n";
echo "Sandbox Mode: " . (isset($settings['sandbox']) ? ($settings['sandbox'] ? 'Yes' : 'No') : 'Not set') . "\n\n";

// Get default settings
$defaults = array(
    'enabled'     => true,
    'api_key'     => '',
    'api_url'     => '',
    'sandbox'     => true,
);

echo "=== Default Settings ===\n";
print_r($defaults);
echo "\n\n";

// Test get_api_url() method
if (file_exists('includes/class-md-va-api.php')) {
    require_once('includes/class-md-va-api.php');
    
    // Create mock logger
    class MockLogger {
        public function log_request($user_id, $request, $response) {
            // Do nothing
        }
    }
    
    $logger = new MockLogger();
    $va_api = new MD_VA_API($logger);
    
    // Get API URL
    $api_url = $va_api->get_api_url();
    echo "=== API URL (from get_api_url()) ===\n";
    echo $api_url . "\n";
}
