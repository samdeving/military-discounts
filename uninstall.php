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

// Flush rewrite rules.
flush_rewrite_rules();
