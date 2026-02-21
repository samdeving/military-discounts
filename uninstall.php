<?php
/**
 * Uninstall script for Military Discounts plugin.
 *
 * This file is executed when the plugin is deleted via the WordPress admin.
 * It removes all plugin data including options, user meta, and scheduled events.
 *
 * @package Military_Discounts
 */

// Exit if not called by WordPress uninstaller.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete plugin options.
$options_to_delete = array(
	'md_settings_general',
	'md_settings_va_api',
	'md_settings_military_otp',
	'md_settings_queue',
	'md_settings_logs',
	'md_settings_security',
	'md_settings_form_text',
	'md_form_fields_veteran',
	'md_form_fields_military',
	'md_pending_verification_index',
	'md_encryption_key',
	'md_va_api_logs',
	'md_disable_encryption',
);

foreach ( $options_to_delete as $option ) {
	delete_option( $option );
}

// Delete user meta for all users.
global $wpdb;

$user_meta_keys = array(
	'is_verified_veteran',
	'is_verified_military',
	'_md_veteran_verified_at',
	'_md_military_verified_at',
	'_md_pending_verification',
	'_md_failed_veteran_attempts',
	'_md_failed_military_attempts',
	'_md_last_veteran_attempt_at',
	'_md_last_military_attempt_at',
	'_md_veteran_lockout_until',
	'_md_military_lockout_until',
);

foreach ( $user_meta_keys as $meta_key ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Uninstall cleanup, caching not relevant.
	$wpdb->delete(
		$wpdb->usermeta,
		array( 'meta_key' => $meta_key ),
		array( '%s' )
	);
}

// Clear any scheduled cron events.
$timestamp = wp_next_scheduled( 'md_process_queue' );
if ( $timestamp ) {
	wp_unschedule_event( $timestamp, 'md_process_queue' );
}

// Clear all transients related to OTP.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Uninstall cleanup, caching not relevant.
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
		'_transient_md_otp_%',
		'_transient_timeout_md_otp_%'
	)
);
