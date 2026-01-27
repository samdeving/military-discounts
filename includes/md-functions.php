<?php
/**
 * Helper functions for Military Discounts plugin.
 *
 * @package Military_Discounts
 */

defined( 'ABSPATH' ) || exit;

/**
 * Check if a user is a verified veteran.
 *
 * @param int $user_id User ID.
 * @return bool True if verified veteran, false otherwise.
 */
function md_is_verified_veteran( $user_id ) {
	$is_verified = get_user_meta( $user_id, 'is_verified_veteran', true );

	if ( ! $is_verified ) {
		return false;
	}

	// Check if verification has expired.
	$verified_at = get_user_meta( $user_id, '_md_veteran_verified_at', true );
	if ( $verified_at && md_is_verification_expired( $verified_at ) ) {
		return false;
	}

	return true;
}

/**
 * Check if a user is a verified military member.
 *
 * @param int $user_id User ID.
 * @return bool True if verified military, false otherwise.
 */
function md_is_verified_military( $user_id ) {
	$is_verified = get_user_meta( $user_id, 'is_verified_military', true );

	if ( ! $is_verified ) {
		return false;
	}

	// Check if verification has expired.
	$verified_at = get_user_meta( $user_id, '_md_military_verified_at', true );
	if ( $verified_at && md_is_verification_expired( $verified_at ) ) {
		return false;
	}

	return true;
}

/**
 * Check if a verification timestamp has expired.
 *
 * @param int $verified_at Unix timestamp of verification.
 * @return bool True if expired, false otherwise.
 */
function md_is_verification_expired( $verified_at ) {
	$settings = get_option( 'md_settings_general', array() );
	$interval = isset( $settings['reverification_interval'] ) ? absint( $settings['reverification_interval'] ) : 365;

	if ( 0 === $interval ) {
		return false; // Never expires.
	}

	$expiry_time = $verified_at + ( $interval * DAY_IN_SECONDS );

	return time() > $expiry_time;
}

/**
 * Get verification status for a user.
 *
 * @param int $user_id User ID.
 * @return array Verification status array.
 */
function md_get_verification_status( $user_id ) {
	$status = array(
		'is_veteran'          => md_is_verified_veteran( $user_id ),
		'is_military'         => md_is_verified_military( $user_id ),
		'veteran_verified_at' => get_user_meta( $user_id, '_md_veteran_verified_at', true ),
		'military_verified_at' => get_user_meta( $user_id, '_md_military_verified_at', true ),
		'has_pending'         => md_has_pending_verification( $user_id ),
	);

	// Add expiry dates if verified.
	if ( $status['veteran_verified_at'] ) {
		$status['veteran_expires_at'] = md_get_verification_expiry( $user_id, 'veteran' );
	}
	if ( $status['military_verified_at'] ) {
		$status['military_expires_at'] = md_get_verification_expiry( $user_id, 'military' );
	}

	return $status;
}

/**
 * Get verification expiry timestamp.
 *
 * @param int    $user_id User ID.
 * @param string $type    Verification type ('veteran' or 'military').
 * @return int|null Expiry timestamp or null if no verification.
 */
function md_get_verification_expiry( $user_id, $type ) {
	$meta_key = '_md_' . $type . '_verified_at';
	$verified_at = get_user_meta( $user_id, $meta_key, true );

	if ( ! $verified_at ) {
		return null;
	}

	$settings = get_option( 'md_settings_general', array() );
	$interval = isset( $settings['reverification_interval'] ) ? absint( $settings['reverification_interval'] ) : 365;

	if ( 0 === $interval ) {
		return null; // Never expires.
	}

	return $verified_at + ( $interval * DAY_IN_SECONDS );
}

/**
 * Set verification status for a user.
 *
 * @param int    $user_id User ID.
 * @param string $type    Verification type ('veteran' or 'military').
 * @param bool   $status  Verification status.
 */
function md_set_verified( $user_id, $type, $status ) {
	$meta_key = 'is_verified_' . $type;
	$timestamp_key = '_md_' . $type . '_verified_at';

	update_user_meta( $user_id, $meta_key, $status );

	if ( $status ) {
		update_user_meta( $user_id, $timestamp_key, time() );
	} else {
		delete_user_meta( $user_id, $timestamp_key );
	}
}

/**
 * Check if a user has a pending verification.
 *
 * @param int $user_id User ID.
 * @return bool True if has pending verification.
 */
function md_has_pending_verification( $user_id ) {
	$pending = get_user_meta( $user_id, '_md_pending_verification', true );
	return ! empty( $pending );
}

/**
 * Get general settings.
 *
 * @return array General settings.
 */
function md_get_general_settings() {
	$defaults = array(
		'enabled'                    => true,
		'reverification_interval'    => 365,
		'reverification_behavior'    => 'silent',
		'disable_encryption'         => false,
		'redirect_url'               => '',
		'redirect_delay'             => 2000,
		'page_title'                 => '',
		'menu_order'                 => 10,
	);

	return wp_parse_args( get_option( 'md_settings_general', array() ), $defaults );
}

/**
 * Get VA API settings.
 *
 * @return array VA API settings.
 */
function md_get_va_api_settings() {
	$defaults = array(
		'enabled'     => true,
		'api_key'     => '',
		'api_url'     => '', // Empty by default to let get_api_url() handle it
		'sandbox'     => true,
	);

	return wp_parse_args( get_option( 'md_settings_va_api', array() ), $defaults );
}

/**
 * Get Military OTP settings.
 *
 * @return array Military OTP settings.
 */
function md_get_military_otp_settings() {
	$defaults = array(
		'enabled'                 => true,
		'whitelist_patterns'      => '*.mil',
		'blacklist_patterns'      => '*ctr.mil,*contractor.mil',
		'otp_expiry'              => 15,
		'require_name_match'      => false,
		'lock_billing_first_name' => false,
		'lock_billing_last_name'  => false,
	);

	return wp_parse_args( get_option( 'md_settings_military_otp', array() ), $defaults );
}

/**
 * Get queue settings.
 *
 * @return array Queue settings.
 */
function md_get_queue_settings() {
	$defaults = array(
		'retry_interval' => 1,
		'max_retries'    => 5,
	);

	return wp_parse_args( get_option( 'md_settings_queue', array() ), $defaults );
}

/**
 * Check if encryption is enabled.
 *
 * @return bool True if encryption is enabled.
 */
function md_is_encryption_enabled() {
	$settings = md_get_general_settings();
	return empty( $settings['disable_encryption'] );
}
