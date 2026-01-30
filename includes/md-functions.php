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
		'blacklist_patterns'      => '*ctr.mil,*civ.mil',
		'otp_expiry'              => 15,
		'require_name_match'      => 'none', // Deprecated, use name_match_type instead
		'name_match_type'         => 'none', // New setting name
		'lock_billing_first_name' => false,
		'lock_billing_last_name'  => false,
	);

	$settings = wp_parse_args( get_option( 'md_settings_military_otp', array() ), $defaults );

	// Fallback to old setting if new one isn't set
	if ( ! isset( $settings['name_match_type'] ) || empty( $settings['name_match_type'] ) ) {
		$settings['name_match_type'] = $settings['require_name_match'];
	}

	return $settings;
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

/**
 * Get verification security settings.
 *
 * @return array Verification security settings.
 */
function md_get_security_settings() {
	$defaults = array(
		'enable_lockout'           => true,
		'max_failed_veteran_attempts' => 5,
		'veteran_lockout_duration'    => 60,
		'max_failed_military_attempts' => 5,
		'military_lockout_duration'    => 60,
		'send_lockout_notification'    => true,
	);

	return wp_parse_args( get_option( 'md_settings_security', array() ), $defaults );
}

/**
 * Get form text settings.
 *
 * @return array Form text settings.
 */
function md_get_form_text_settings() {
	$defaults = array(
		// Page Header
		'page_subtitle' => __( 'Verify your veteran or active military status to access exclusive discounts.', 'military-discounts' ),

		// Verified Status
		'verified_veteran_title' => __( 'Veteran Status Verified', 'military-discounts' ),
		'verified_military_title' => __( 'Active Military Status Verified', 'military-discounts' ),
		'verified_valid_until' => __( 'Valid until %s', 'military-discounts' ),
		'verified_no_expiration' => __( 'No expiration', 'military-discounts' ),
		'verified_note' => __( 'You are eligible for military discounts on applicable products and coupons.', 'military-discounts' ),

		// Pending Status
		'pending_title' => __( 'Verification Pending', 'military-discounts' ),
		'pending_description' => __( 'Your verification request has been submitted and is being processed. You will receive an email notification once complete.', 'military-discounts' ),
		'pending_type_label' => __( 'Type:', 'military-discounts' ),
		'pending_submitted_label' => __( 'Submitted:', 'military-discounts' ),
		'pending_status_label' => __( 'Status:', 'military-discounts' ),

		// Lockout Status
		'lockout_title' => __( 'Verification Locked', 'military-discounts' ),
		'lockout_description' => __( 'Too many failed %s verification attempts. Please try again in %d minutes.', 'military-discounts' ),

		// Failed Attempts
		'failed_veteran_text' => __( 'Veteran verification: %d/%d failed attempts', 'military-discounts' ),
		'failed_military_text' => __( 'Military verification: %d/%d failed attempts', 'military-discounts' ),

		// Step 1: Type Selection
		'step1_title' => __( 'Select Verification Type', 'military-discounts' ),
		'veteran_radio_label' => __( 'I am a Veteran', 'military-discounts' ),
		'veteran_radio_desc' => __( 'Verify through VA records', 'military-discounts' ),
		'military_radio_label' => __( 'I am Active Military', 'military-discounts' ),
		'military_radio_desc' => __( 'Verify with .mil email', 'military-discounts' ),

		// Step Labels
		'step1_label' => __( 'Select Type', 'military-discounts' ),
		'step2_label' => __( 'Enter Info', 'military-discounts' ),
		'step3_label' => __( 'Submit', 'military-discounts' ),

		// Step 2: Enter Information
		'step2_veteran_title' => __( 'Enter Your Information', 'military-discounts' ),
		'step2_military_title' => __( 'Enter Your Military Email', 'military-discounts' ),

		// Step 3: Veteran Confirmation
		'step3_veteran_title' => __( 'Confirm Your Information', 'military-discounts' ),
		'step3_veteran_desc' => __( 'Please review your information before submitting.', 'military-discounts' ),
		'step3_verification_details' => __( 'Verification Details', 'military-discounts' ),

		// Step 3: Military OTP
		'step3_military_title' => __( 'Verify Your Military Email', 'military-discounts' ),
		'step3_military_desc' => __( 'We\'ll send a verification code to:', 'military-discounts' ),
		'step3_resend_link' => __( 'Resend code', 'military-discounts' ),
		'step3_otp_placeholder' => __( '000000', 'military-discounts' ),
		'step3_otp_validation' => __( 'Please enter a 6-digit code.', 'military-discounts' ),

		// Buttons and Actions
		'button_back' => __( 'Back', 'military-discounts' ),
		'button_next' => __( 'Next', 'military-discounts' ),
		'button_submit' => __( 'Submit', 'military-discounts' ),
		'button_verify_code' => __( 'Verify Code', 'military-discounts' ),
		'button_resend_code' => __( 'Resend Code', 'military-discounts' ),

		// Form Fields
		'select_placeholder' => __( 'Select...', 'military-discounts' ),
		'select_state_placeholder' => __( 'Select State...', 'military-discounts' ),
		'select_country_placeholder' => __( 'Select Country...', 'military-discounts' ),
		'select_country_us' => __( 'United States', 'military-discounts' ),

		// JavaScript Messages
		'js_loading' => __( 'Loading...', 'military-discounts' ),
		'js_submitting' => __( 'Submitting...', 'military-discounts' ),
		'js_sending_otp' => __( 'Sending code...', 'military-discounts' ),
		'js_verifying_otp' => __( 'Verifying...', 'military-discounts' ),
		'js_error_occurred' => __( 'An error occurred. Please try again.', 'military-discounts' ),
		'js_required_field' => __( 'This field is required.', 'military-discounts' ),
		'js_invalid_email' => __( 'Please enter a valid email address.', 'military-discounts' ),
		'js_select_type' => __( 'Please select a verification type.', 'military-discounts' ),
		'js_otp_sent' => __( 'Verification code sent to your email.', 'military-discounts' ),
		'js_otp_resent' => __( "We've sent another code to your email.", 'military-discounts' ),
		'js_redirecting' => __( 'Redirecting...', 'military-discounts' ),
		'js_refreshing' => __( 'Refreshing page...', 'military-discounts' ),
	);

	return wp_parse_args( get_option( 'md_settings_form_text', array() ), $defaults );
}

/**
 * Get failed attempts count.
 *
 * @param int    $user_id User ID.
 * @param string $type    Verification type ('veteran' or 'military').
 * @return int Number of failed attempts.
 */
function md_get_failed_attempts( $user_id, $type ) {
	$meta_key = '_md_failed_' . $type . '_attempts';
	return absint( get_user_meta( $user_id, $meta_key, true ) );
}

/**
 * Increment failed attempts count.
 *
 * @param int    $user_id User ID.
 * @param string $type    Verification type ('veteran' or 'military').
 * @return int New failed attempts count.
 */
function md_increment_failed_attempts( $user_id, $type ) {
	$count = md_get_failed_attempts( $user_id, $type );
	$new_count = $count + 1;

	update_user_meta( $user_id, '_md_failed_' . $type . '_attempts', $new_count );
	update_user_meta( $user_id, '_md_last_' . $type . '_attempt_at', time() );

	return $new_count;
}

/**
 * Reset failed attempts count.
 *
 * @param int    $user_id User ID.
 * @param string $type    Verification type ('veteran' or 'military').
 */
function md_reset_failed_attempts( $user_id, $type ) {
	delete_user_meta( $user_id, '_md_failed_' . $type . '_attempts' );
	delete_user_meta( $user_id, '_md_last_' . $type . '_attempt_at' );
	delete_user_meta( $user_id, '_md_' . $type . '_lockout_until' );
}

/**
 * Check if user is locked out.
 *
 * @param int    $user_id User ID.
 * @param string $type    Verification type ('veteran' or 'military').
 * @return bool True if locked out, false otherwise.
 */
function md_is_locked_out( $user_id, $type ) {
	$lockout_until = get_user_meta( $user_id, '_md_' . $type . '_lockout_until', true );

	if ( ! $lockout_until ) {
		return false;
	}

	return time() < $lockout_until;
}

/**
 * Get remaining lockout time in minutes.
 *
 * @param int    $user_id User ID.
 * @param string $type    Verification type ('veteran' or 'military').
 * @return int Remaining lockout time in minutes.
 */
function md_get_lockout_remaining( $user_id, $type ) {
	$lockout_until = get_user_meta( $user_id, '_md_' . $type . '_lockout_until', true );

	if ( ! $lockout_until || time() >= $lockout_until ) {
		return 0;
	}

	$remaining_seconds = $lockout_until - time();
	return ceil( $remaining_seconds / 60 );
}

/**
 * Set lockout for user.
 *
 * @param int    $user_id  User ID.
 * @param string $type     Verification type ('veteran' or 'military').
 * @param int    $duration Lockout duration in minutes.
 */
function md_set_lockout( $user_id, $type, $duration ) {
	$lockout_until = time() + ( $duration * 60 );
	update_user_meta( $user_id, '_md_' . $type . '_lockout_until', $lockout_until );
	
	// Send lockout notification email if enabled
	$security_settings = md_get_security_settings();
	if ( $security_settings['send_lockout_notification'] ) {
		md_send_lockout_notification( $user_id, $type, $duration );
	}
}

/**
 * Send lockout notification email.
 *
 * @param int    $user_id           User ID.
 * @param string $verification_type Verification type.
 * @param int    $lockout_duration  Lockout duration in minutes.
 */
function md_send_lockout_notification( $user_id, $verification_type, $lockout_duration ) {
	// Try to use WooCommerce email if available
	if ( function_exists( 'WC' ) && WC()->mailer() ) {
		$emails = WC()->mailer()->get_emails();
		
		if ( isset( $emails['MD_Email_Lockout'] ) ) {
			$emails['MD_Email_Lockout']->trigger( $user_id, $verification_type, $lockout_duration );
		}
	}
}
