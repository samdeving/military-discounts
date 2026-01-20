<?php
/**
 * Military OTP verification class for Military Discounts plugin.
 *
 * Handles military email verification via one-time passcode.
 *
 * @package Military_Discounts
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class MD_Military_OTP
 *
 * Handles OTP generation, sending, and validation for military email verification.
 */
class MD_Military_OTP {

	/**
	 * OTP length.
	 *
	 * @var int
	 */
	private const OTP_LENGTH = 6;

	/**
	 * Generate an OTP for a user.
	 *
	 * @param int $user_id User ID.
	 * @return string Generated OTP.
	 */
	public function generate_otp( $user_id ) {
		// Generate a random numeric OTP.
		$otp = '';
		for ( $i = 0; $i < self::OTP_LENGTH; $i++ ) {
			$otp .= wp_rand( 0, 9 );
		}

		// Store the OTP as a transient.
		$settings = md_get_military_otp_settings();
		$expiry   = isset( $settings['otp_expiry'] ) ? absint( $settings['otp_expiry'] ) : 15;

		set_transient( 'md_otp_' . $user_id, $otp, $expiry * MINUTE_IN_SECONDS );

		// Store the attempt timestamp.
		set_transient( 'md_otp_sent_' . $user_id, time(), $expiry * MINUTE_IN_SECONDS );

		return $otp;
	}

	/**
	 * Send OTP to a military email address.
	 *
	 * @param string $email   Email address.
	 * @param string $otp     OTP to send.
	 * @param int    $user_id User ID (optional).
	 * @return bool True if email was sent successfully.
	 */
	public function send_otp( $email, $otp, $user_id = 0 ) {
		// Try to use WooCommerce email if available.
		if ( function_exists( 'WC' ) && WC()->mailer() ) {
			$emails = WC()->mailer()->get_emails();
			if ( isset( $emails['MD_Email_OTP'] ) ) {
				$emails['MD_Email_OTP']->trigger( $user_id, $otp, $email );
				return true;
			}
		}

		// Fallback to wp_mail.
		$site_name = get_bloginfo( 'name' );

		$subject = sprintf(
			/* translators: %s: site name */
			__( '[%s] Your Military Verification Code', 'military-discounts' ),
			$site_name
		);

		$settings   = md_get_military_otp_settings();
		$otp_expiry = isset( $settings['otp_expiry'] ) ? absint( $settings['otp_expiry'] ) : 15;

		$message = sprintf(
			/* translators: 1: OTP code, 2: expiry minutes, 3: site name */
			__(
				'Your military verification code is: %1$s

This code will expire in %2$d minutes.

If you did not request this code, please ignore this email.

%3$s',
				'military-discounts'
			),
			$otp,
			$otp_expiry,
			$site_name
		);

		$headers = array( 'Content-Type: text/plain; charset=UTF-8' );

		return wp_mail( $email, $subject, $message, $headers );
	}

	/**
	 * Validate an OTP.
	 *
	 * @param int    $user_id   User ID.
	 * @param string $input_otp OTP to validate.
	 * @return bool True if valid.
	 */
	public function validate_otp( $user_id, $input_otp ) {
		$stored_otp = get_transient( 'md_otp_' . $user_id );

		if ( empty( $stored_otp ) ) {
			return false;
		}

		// Compare OTPs.
		$is_valid = hash_equals( $stored_otp, $input_otp );

		if ( $is_valid ) {
			// Delete the OTP after successful validation.
			delete_transient( 'md_otp_' . $user_id );
			delete_transient( 'md_otp_sent_' . $user_id );
		}

		return $is_valid;
	}

	/**
	 * Check if an email is a valid military email.
	 *
	 * @param string $email Email to check.
	 * @return bool True if valid military email.
	 */
	public function is_military_email( $email ) {
		$settings = md_get_military_otp_settings();

		// Get whitelist and blacklist patterns.
		$whitelist = isset( $settings['whitelist_patterns'] ) ? $settings['whitelist_patterns'] : '*.mil';
		$blacklist = isset( $settings['blacklist_patterns'] ) ? $settings['blacklist_patterns'] : '';

		// Parse patterns.
		$whitelist_patterns = array_filter( array_map( 'trim', explode( ',', $whitelist ) ) );
		$blacklist_patterns = array_filter( array_map( 'trim', explode( ',', $blacklist ) ) );

		// Check against blacklist first.
		foreach ( $blacklist_patterns as $pattern ) {
			if ( $this->email_matches_pattern( $email, $pattern ) ) {
				return false;
			}
		}

		// Check against whitelist.
		foreach ( $whitelist_patterns as $pattern ) {
			if ( $this->email_matches_pattern( $email, $pattern ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if an email matches a pattern.
	 *
	 * @param string $email   Email to check.
	 * @param string $pattern Pattern to match (supports * wildcard).
	 * @return bool True if matches.
	 */
	private function email_matches_pattern( $email, $pattern ) {
		// Convert wildcard pattern to regex.
		$regex = '/^' . str_replace(
			array( '\*', '\?' ),
			array( '.*', '.' ),
			preg_quote( $pattern, '/' )
		) . '$/i';

		return (bool) preg_match( $regex, $email );
	}

	/**
	 * Check if user can request a new OTP.
	 *
	 * @param int $user_id User ID.
	 * @return bool True if can request.
	 */
	public function can_request_otp( $user_id ) {
		$last_sent = get_transient( 'md_otp_sent_' . $user_id );

		if ( empty( $last_sent ) ) {
			return true;
		}

		// Allow new request after 60 seconds.
		return ( time() - $last_sent ) >= 60;
	}

	/**
	 * Get remaining time until OTP expires.
	 *
	 * @param int $user_id User ID.
	 * @return int Seconds remaining, or 0 if expired.
	 */
	public function get_otp_expiry_time( $user_id ) {
		$last_sent = get_transient( 'md_otp_sent_' . $user_id );

		if ( empty( $last_sent ) ) {
			return 0;
		}

		$settings = md_get_military_otp_settings();
		$expiry   = isset( $settings['otp_expiry'] ) ? absint( $settings['otp_expiry'] ) : 15;

		$expiry_time = $last_sent + ( $expiry * MINUTE_IN_SECONDS );
		$remaining   = $expiry_time - time();

		return max( 0, $remaining );
	}

	/**
	 * Get time until user can request new OTP.
	 *
	 * @param int $user_id User ID.
	 * @return int Seconds until can request, or 0 if can request now.
	 */
	public function get_cooldown_remaining( $user_id ) {
		$last_sent = get_transient( 'md_otp_sent_' . $user_id );

		if ( empty( $last_sent ) ) {
			return 0;
		}

		$cooldown_end = $last_sent + 60;
		$remaining    = $cooldown_end - time();

		return max( 0, $remaining );
	}

	/**
		* Check if email username matches customer's first and last name.
		*
		* @param string $email      Email address to check.
		* @param string $first_name Customer's first name.
		* @param string $last_name  Customer's last name.
		* @return bool True if names match.
		*/
	public function email_matches_name( $email, $first_name, $last_name ) {
		// Extract local part (before @).
		$parts = explode( '@', $email );
		if ( count( $parts ) < 2 ) {
			return false;
		}

		$local_part = strtolower( $parts[0] );
		$first_name = strtolower( trim( $first_name ) );
		$last_name  = strtolower( trim( $last_name ) );

		if ( empty( $first_name ) || empty( $last_name ) ) {
			return false;
		}

		// Remove numbers from local part for matching.
		$local_no_numbers = preg_replace( '/[0-9]+/', '', $local_part );

		// Split local part by common separators (., _, -).
		$local_parts = preg_split( '/[._\-]+/', $local_no_numbers );
		$local_parts = array_filter( $local_parts );

		// Check if first name and last name are both present in the local parts.
		$has_first = false;
		$has_last  = false;

		foreach ( $local_parts as $part ) {
			if ( $part === $first_name ) {
				$has_first = true;
			}
			if ( $part === $last_name ) {
				$has_last = true;
			}
		}

		return $has_first && $has_last;
	}

	/**
		* Check if name matching is required.
		*
		* @return bool True if name matching is enabled.
		*/
	public function is_name_match_required() {
		$settings = md_get_military_otp_settings();
		return ! empty( $settings['require_name_match'] );
	}
}
