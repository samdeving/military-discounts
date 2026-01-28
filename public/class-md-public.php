<?php
/**
 * Public class for Military Discounts plugin.
 *
 * Handles frontend initialization.
 *
 * @package Military_Discounts
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class MD_Public
 *
 * Frontend initialization.
 */
class MD_Public {

	/**
	 * Initialize public hooks.
	 */
	public function init() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_filter( 'woocommerce_billing_fields', array( $this, 'maybe_lock_billing_name_fields' ), 20 );
		add_filter( 'woocommerce_my_account_edit_address_field_value', array( $this, 'preserve_locked_billing_name' ), 10, 3 );
	}

	/**
	 * Lock billing name fields if user is verified military and settings require it.
	 *
	 * @param array $fields Billing fields.
	 * @return array Modified fields.
	 */
	public function maybe_lock_billing_name_fields( $fields ) {
		if ( ! is_user_logged_in() ) {
			return $fields;
		}

		$user_id  = get_current_user_id();
		$settings = md_get_military_otp_settings();

		// Only lock if user is verified military.
		if ( ! md_is_verified_military( $user_id ) ) {
			return $fields;
		}

		// Lock first name if enabled.
		if ( ! empty( $settings['lock_billing_first_name'] ) && isset( $fields['billing_first_name'] ) ) {
			$fields['billing_first_name']['custom_attributes']['readonly'] = 'readonly';
			$fields['billing_first_name']['description'] = __( 'This field is locked due to military verification.', 'military-discounts' );
		}

		// Lock last name if enabled.
		if ( ! empty( $settings['lock_billing_last_name'] ) && isset( $fields['billing_last_name'] ) ) {
			$fields['billing_last_name']['custom_attributes']['readonly'] = 'readonly';
			$fields['billing_last_name']['description'] = __( 'This field is locked due to military verification.', 'military-discounts' );
		}

		return $fields;
	}

	/**
	 * Preserve locked billing name values on save.
	 *
	 * @param string $value     Field value.
	 * @param string $key       Field key.
	 * @param string $load_type Load type.
	 * @return string Field value.
	 */
	public function preserve_locked_billing_name( $value, $key, $load_type ) {
		if ( ! is_user_logged_in() || 'billing' !== $load_type ) {
			return $value;
		}

		$user_id  = get_current_user_id();
		$settings = md_get_military_otp_settings();

		if ( ! md_is_verified_military( $user_id ) ) {
			return $value;
		}

		$customer = new WC_Customer( $user_id );

		if ( 'billing_first_name' === $key && ! empty( $settings['lock_billing_first_name'] ) ) {
			return $customer->get_billing_first_name();
		}

		if ( 'billing_last_name' === $key && ! empty( $settings['lock_billing_last_name'] ) ) {
			return $customer->get_billing_last_name();
		}

		return $value;
	}

	/**
	 * Enqueue frontend scripts and styles.
	 */
	public function enqueue_scripts() {
		// Only load on My Account page.
		if ( ! is_account_page() ) {
			return;
		}

		wp_enqueue_style(
			'md-public',
			MD_PLUGIN_URL . 'assets/css/md-public.css',
			array(),
			MD_VERSION
		);

		wp_enqueue_script(
			'md-public',
			MD_PLUGIN_URL . 'assets/js/md-public.js',
			array( 'jquery' ),
			MD_VERSION,
			true
		);

		wp_localize_script(
			'md-public',
			'mdPublic',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'md_public_nonce' ),
				'strings' => array(
					'loading'         => __( 'Loading...', 'military-discounts' ),
					'submitting'      => __( 'Submitting...', 'military-discounts' ),
					'sendingOtp'      => __( 'Sending code...', 'military-discounts' ),
					'verifyingOtp'    => __( 'Verifying...', 'military-discounts' ),
					'errorOccurred'   => __( 'An error occurred. Please try again.', 'military-discounts' ),
					'requiredField'   => __( 'This field is required.', 'military-discounts' ),
					'invalidEmail'    => __( 'Please enter a valid email address.', 'military-discounts' ),
					'otpSent'         => __( 'Verification code sent to your email.', 'military-discounts' ),
					'otpResent'       => __( "We've sent another code to your email.", 'military-discounts' ),
					'selectType'      => __( 'Please select a verification type.', 'military-discounts' ),
					'back'            => __( 'Back', 'military-discounts' ),
					'next'            => __( 'Next', 'military-discounts' ),
					'submit'          => __( 'Submit', 'military-discounts' ),
					'verifyCode'      => __( 'Verify Code', 'military-discounts' ),
					'resendCode'      => __( 'Resend Code', 'military-discounts' ),
				),
			)
		);
	}
}
