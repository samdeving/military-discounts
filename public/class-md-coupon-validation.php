<?php
/**
 * Coupon Validation class for Military Discounts plugin.
 *
 * Validates coupon usage based on verification status.
 *
 * @package Military_Discounts
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class MD_Coupon_Validation
 *
 * Handles coupon validation for military discounts.
 */
class MD_Coupon_Validation {

	/**
	 * Initialize coupon validation hooks.
	 */
	public function init() {
		add_filter( 'woocommerce_coupon_is_valid', array( $this, 'validate_coupon' ), 10, 3 );
		add_filter( 'woocommerce_coupon_error', array( $this, 'customize_error_message' ), 10, 3 );
	}

	/**
	 * Validate coupon based on verification requirements.
	 *
	 * @param bool      $valid    Whether coupon is valid.
	 * @param WC_Coupon $coupon   Coupon object.
	 * @param WC_Discounts $discounts Discounts object.
	 * @return bool Whether coupon is valid.
	 * @throws Exception If validation fails.
	 */
	public function validate_coupon( $valid, $coupon, $discounts ) {
		// Skip if coupon is already invalid.
		if ( ! $valid ) {
			return $valid;
		}

		// Check if plugin is enabled.
		$settings = md_get_general_settings();
		if ( empty( $settings['enabled'] ) ) {
			return $valid;
		}

		// Get requirements.
		$requires_veteran  = $coupon->get_meta( '_requires_veteran_verification' ) === 'yes';
		$requires_military = $coupon->get_meta( '_requires_military_verification' ) === 'yes';

		// No verification required.
		if ( ! $requires_veteran && ! $requires_military ) {
			return $valid;
		}

		// Check if user is logged in.
		if ( ! is_user_logged_in() ) {
			throw new Exception(
				esc_html__( 'Please log in to use this coupon.', 'military-discounts' )
			);
		}

		$user_id = get_current_user_id();

		// Check veteran verification.
		if ( $requires_veteran && ! md_is_verified_veteran( $user_id ) ) {
			throw new Exception(
				sprintf(
					/* translators: %s: link to verification page */
					esc_html__( 'This coupon requires veteran verification. %s', 'military-discounts' ),
					'<a href="' . esc_url( wc_get_account_endpoint_url( 'military-discounts' ) ) . '">' . esc_html__( 'Verify now', 'military-discounts' ) . '</a>'
				)
			);
		}

		// Check military verification.
		if ( $requires_military && ! md_is_verified_military( $user_id ) ) {
			throw new Exception(
				sprintf(
					/* translators: %s: link to verification page */
					esc_html__( 'This coupon requires active military verification. %s', 'military-discounts' ),
					'<a href="' . esc_url( wc_get_account_endpoint_url( 'military-discounts' ) ) . '">' . esc_html__( 'Verify now', 'military-discounts' ) . '</a>'
				)
			);
		}

		return $valid;
	}

	/**
	 * Customize error messages for verification failures.
	 *
	 * @param string $err      Error message.
	 * @param int    $err_code Error code.
	 * @param WC_Coupon $coupon Coupon object.
	 * @return string Modified error message.
	 */
	public function customize_error_message( $err, $err_code, $coupon ) {
		// Allow HTML in our custom error messages.
		if ( strpos( $err, 'military-discounts' ) !== false ) {
			return wp_kses(
				$err,
				array(
					'a' => array(
						'href' => array(),
					),
				)
			);
		}

		return $err;
	}
}
