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

		$form_text_settings = md_get_form_text_settings();

		wp_localize_script(
			'md-public',
			'mdPublic',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'md_public_nonce' ),
				'strings' => array(
					// Page Header
					'pageSubtitle' => $form_text_settings['page_subtitle'],

					// Verified Status
					'verifiedVeteranTitle' => $form_text_settings['verified_veteran_title'],
					'verifiedMilitaryTitle' => $form_text_settings['verified_military_title'],
					'verifiedValidUntil' => $form_text_settings['verified_valid_until'],
					'verifiedNoExpiration' => $form_text_settings['verified_no_expiration'],
					'verifiedNote' => $form_text_settings['verified_note'],

					// Pending Status
					'pendingTitle' => $form_text_settings['pending_title'],
					'pendingDescription' => $form_text_settings['pending_description'],
					'pendingTypeLabel' => $form_text_settings['pending_type_label'],
					'pendingSubmittedLabel' => $form_text_settings['pending_submitted_label'],
					'pendingStatusLabel' => $form_text_settings['pending_status_label'],

					// Lockout Status
					'lockoutTitle' => $form_text_settings['lockout_title'],
					'lockoutDescription' => $form_text_settings['lockout_description'],

					// Failed Attempts
					'failedVeteranText' => $form_text_settings['failed_veteran_text'],
					'failedMilitaryText' => $form_text_settings['failed_military_text'],

					// Step 1: Type Selection
					'step1Title' => $form_text_settings['step1_title'],
					'veteranRadioLabel' => $form_text_settings['veteran_radio_label'],
					'veteranRadioDesc' => $form_text_settings['veteran_radio_desc'],
					'militaryRadioLabel' => $form_text_settings['military_radio_label'],
					'militaryRadioDesc' => $form_text_settings['military_radio_desc'],

					// Step Labels
					'step1Label' => $form_text_settings['step1_label'],
					'step2Label' => $form_text_settings['step2_label'],
					'step3Label' => $form_text_settings['step3_label'],

					// Step 2: Enter Information
					'step2VeteranTitle' => $form_text_settings['step2_veteran_title'],
					'step2MilitaryTitle' => $form_text_settings['step2_military_title'],

					// Step 3: Veteran Confirmation
					'step3VeteranTitle' => $form_text_settings['step3_veteran_title'],
					'step3VeteranDesc' => $form_text_settings['step3_veteran_desc'],
					'step3VerificationDetails' => $form_text_settings['step3_verification_details'],

					// Step 3: Military OTP
					'step3MilitaryTitle' => $form_text_settings['step3_military_title'],
					'step3MilitaryDesc' => $form_text_settings['step3_military_desc'],
					'step3ResendLink' => $form_text_settings['step3_resend_link'],
					'step3OtpPlaceholder' => $form_text_settings['step3_otp_placeholder'],
					'step3OtpValidation' => $form_text_settings['step3_otp_validation'],

					// Buttons and Actions
					'buttonBack' => $form_text_settings['button_back'],
					'buttonNext' => $form_text_settings['button_next'],
					'buttonSubmit' => $form_text_settings['button_submit'],
					'buttonVerifyCode' => $form_text_settings['button_verify_code'],
					'buttonResendCode' => $form_text_settings['button_resend_code'],

					// Form Fields
					'selectPlaceholder' => $form_text_settings['select_placeholder'],
					'selectStatePlaceholder' => $form_text_settings['select_state_placeholder'],
					'selectCountryPlaceholder' => $form_text_settings['select_country_placeholder'],
					'selectCountryUs' => $form_text_settings['select_country_us'],

					// JavaScript Messages
					'loading' => $form_text_settings['js_loading'],
					'submitting' => $form_text_settings['js_submitting'],
					'sendingOtp' => $form_text_settings['js_sending_otp'],
					'verifyingOtp' => $form_text_settings['js_verifying_otp'],
					'errorOccurred' => $form_text_settings['js_error_occurred'],
					'requiredField' => $form_text_settings['js_required_field'],
					'invalidEmail' => $form_text_settings['js_invalid_email'],
					'selectType' => $form_text_settings['js_select_type'],
					'otpSent' => $form_text_settings['js_otp_sent'],
					'otpResent' => $form_text_settings['js_otp_resent'],
					'redirecting' => $form_text_settings['js_redirecting'],
					'refreshing' => $form_text_settings['js_refreshing'],
				),
			)
		);
	}
}
