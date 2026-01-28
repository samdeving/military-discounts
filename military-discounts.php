<?php
/**
 * Plugin Name: Military Discounts
 * Plugin URI: https://github.com/samdeving/military-discounts/
 * Description: This plugin is currently in beta, and should not be used in production at this time. Use at your own risk. WooCommerce plugin that verifies current US military and veterans status and automatically issues coupons. Veteran verification uses V.A. LightHouse API and Military Email OTP.
 * Version: 1.0.0-beta.
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: samdeving.
 * Author URI: https://github.com/samdeving/
 * License: GPL v3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: military-discounts
 * Domain Path: /languages
 * Requires Plugins: woocommerce
 *
 * @package Military_Discounts
 */

defined( 'ABSPATH' ) || exit;

// Plugin constants.
define( 'MD_VERSION', '1.0.0' );
define( 'MD_PLUGIN_FILE', __FILE__ );
define( 'MD_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MD_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'MD_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Autoloader for plugin classes.
 *
 * @param string $class_name The class name to load.
 */
function md_autoloader( $class_name ) {
	// Only autoload classes with MD_ prefix.
	if ( strpos( $class_name, 'MD_' ) !== 0 ) {
		return;
	}

	// Convert class name to file name.
	$file_name = 'class-' . strtolower( str_replace( '_', '-', $class_name ) ) . '.php';

	// Define possible locations.
	$locations = array(
		MD_PLUGIN_DIR . 'includes/',
		MD_PLUGIN_DIR . 'admin/',
		MD_PLUGIN_DIR . 'public/',
		MD_PLUGIN_DIR . 'emails/',
	);

	foreach ( $locations as $location ) {
		$file_path = $location . $file_name;
		if ( file_exists( $file_path ) ) {
			require_once $file_path;
			return;
		}
	}
}
spl_autoload_register( 'md_autoloader' );

/**
 * Check if WooCommerce is active.
 *
 * @return bool
 */
function md_is_woocommerce_active() {
	return class_exists( 'WooCommerce' );
}

/**
 * Display admin notice if WooCommerce is not active.
 */
function md_woocommerce_missing_notice() {
	?>
	<div class="notice notice-error">
		<p>
			<?php
			printf(
				/* translators: %s: WooCommerce plugin name */
				esc_html__( '%s requires WooCommerce to be installed and active.', 'military-discounts' ),
				'<strong>Military Discounts</strong>'
			);
			?>
		</p>
	</div>
	<?php
}

/**
 * Initialize the plugin.
 */
function md_init() {
	// Check for WooCommerce.
	if ( ! md_is_woocommerce_active() ) {
		add_action( 'admin_notices', 'md_woocommerce_missing_notice' );
		return;
	}

	// Include helper functions.
	require_once MD_PLUGIN_DIR . 'includes/md-functions.php';

	// Initialize the loader.
	$loader = new MD_Loader();
	$loader->init();
}
add_action( 'plugins_loaded', 'md_init' );

/**
 * Plugin activation hook.
 */
function md_activate() {
	// Check for WooCommerce.
	if ( ! md_is_woocommerce_active() ) {
		deactivate_plugins( MD_PLUGIN_BASENAME );
		wp_die(
			esc_html__( 'Military Discounts requires WooCommerce to be installed and active.', 'military-discounts' ),
			'Plugin Activation Error',
			array( 'back_link' => true )
		);
	}

	// Set default options.
	$default_general = array(
		'enabled'                    => true,
		'reverification_interval'    => 365,
		'reverification_behavior'    => 'silent',
		'disable_encryption'         => false,
		'page_title'                 => '',
		'menu_order'                 => 10,
	);

	$default_va_api = array(
		'enabled'     => true,
		'api_key'     => '',
		'api_url'     => 'https://api.va.gov/services/veteran-confirmation/v1',
		'sandbox'     => true,
	);

	$default_military_otp = array(
		'enabled'            => true,
		'whitelist_patterns' => '*.mil',
		'blacklist_patterns' => '*ctr.mil,*civ.mil',
		'otp_expiry'         => 15,
	);

	$default_queue = array(
		'retry_interval' => 1,
		'max_retries'    => 5,
	);

	$default_logs = array(
		'retention_days' => 30,
	);

	// Only set defaults if options don't exist.
	if ( false === get_option( 'md_settings_general' ) ) {
		add_option( 'md_settings_general', $default_general );
	}
	if ( false === get_option( 'md_settings_va_api' ) ) {
		add_option( 'md_settings_va_api', $default_va_api );
	}
	if ( false === get_option( 'md_settings_military_otp' ) ) {
		add_option( 'md_settings_military_otp', $default_military_otp );
	}
	if ( false === get_option( 'md_settings_queue' ) ) {
		add_option( 'md_settings_queue', $default_queue );
	}
	if ( false === get_option( 'md_settings_logs' ) ) {
		add_option( 'md_settings_logs', $default_logs );
	}

	// Schedule cron event.
	if ( ! wp_next_scheduled( 'md_process_queue' ) ) {
		wp_schedule_event( time(), 'hourly', 'md_process_queue' );
	}

	// Flush rewrite rules.
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'md_activate' );

/**
 * Plugin deactivation hook.
 */
function md_deactivate() {
	// Clear scheduled cron event.
	$timestamp = wp_next_scheduled( 'md_process_queue' );
	if ( $timestamp ) {
		wp_unschedule_event( $timestamp, 'md_process_queue' );
	}

	// Flush rewrite rules.
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'md_deactivate' );
