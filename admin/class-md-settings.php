<?php
/**
 * Settings class for Military Discounts plugin.
 *
 * Registers and manages plugin settings using WordPress Settings API.
 *
 * @package Military_Discounts
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class MD_Settings
 *
 * Handles settings registration and rendering.
 */
class MD_Settings {

	/**
	 * Logger instance.
	 *
	 * @var MD_Logger
	 */
	private $logger;

	/**
	 * Constructor.
	 *
	 * @param MD_Logger $logger Logger instance.
	 */
	public function __construct( MD_Logger $logger ) {
		$this->logger = $logger;
	}

	/**
	 * Initialize settings hooks.
	 */
	public function init() {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'wp_ajax_md_test_api', array( $this, 'ajax_test_api' ) );
		add_action( 'wp_ajax_md_clear_logs', array( $this, 'ajax_clear_logs' ) );
		add_action( 'wp_ajax_md_export_logs', array( $this, 'ajax_export_logs' ) );
		add_action( 'wp_ajax_md_cancel_pending_verification', array( $this, 'ajax_cancel_pending_verification' ) );
		add_action( 'wp_ajax_md_cancel_all_pending_verifications', array( $this, 'ajax_cancel_all_pending_verifications' ) );
	}

	/**
	 * Register all settings.
	 */
	public function register_settings() {
		$this->register_general_settings();
		$this->register_va_api_settings();
		$this->register_military_otp_settings();
		$this->register_queue_settings();
		$this->register_logs_settings();
		$this->register_security_settings();
		$this->register_form_text_settings();
	}

	/**
	 * Register form text customization settings.
	 */
	private function register_form_text_settings() {
		register_setting(
			'md_settings_form_text',
			'md_settings_form_text',
			array(
				'sanitize_callback' => array( $this, 'sanitize_form_text_settings' ),
			)
		);

		add_settings_section(
			'md_form_text_section',
			__( 'Form Text Customization', 'military-discounts' ),
			array( $this, 'render_form_text_section' ),
			'md-settings-form-text'
		);

		// Page Header
		add_settings_field(
			'page_subtitle',
			__( 'Page Subtitle', 'military-discounts' ),
			array( $this, 'render_text_field' ),
			'md-settings-form-text',
			'md_form_text_section',
			array(
				'option'      => 'md_settings_form_text',
				'field'       => 'page_subtitle',
				'description' => __( 'Subtitle text for the Military Discounts page.', 'military-discounts' ),
				'default'     => __( 'Verify your veteran or active military status to access exclusive discounts.', 'military-discounts' ),
			)
		);

		// Verified Status
		add_settings_field(
			'verified_veteran_title',
			__( 'Verified Veteran Title', 'military-discounts' ),
			array( $this, 'render_text_field' ),
			'md-settings-form-text',
			'md_form_text_section',
			array(
				'option'      => 'md_settings_form_text',
				'field'       => 'verified_veteran_title',
				'description' => __( 'Title for verified veteran status.', 'military-discounts' ),
				'default'     => __( 'Veteran Status Verified', 'military-discounts' ),
			)
		);

		add_settings_field(
			'verified_military_title',
			__( 'Verified Military Title', 'military-discounts' ),
			array( $this, 'render_text_field' ),
			'md-settings-form-text',
			'md_form_text_section',
			array(
				'option'      => 'md_settings_form_text',
				'field'       => 'verified_military_title',
				'description' => __( 'Title for verified active military status.', 'military-discounts' ),
				'default'     => __( 'Active Military Status Verified', 'military-discounts' ),
			)
		);

		add_settings_field(
			'verified_valid_until',
			__( 'Valid Until Text', 'military-discounts' ),
			array( $this, 'render_text_field' ),
			'md-settings-form-text',
			'md_form_text_section',
			array(
				'option'      => 'md_settings_form_text',
				'field'       => 'verified_valid_until',
				'description' => __( 'Text for "Valid until" date display.', 'military-discounts' ),
				/* translators: %s: Expiration date */
				'default'     => __( 'Valid until %s', 'military-discounts' ),
			)
		);

		add_settings_field(
			'verified_no_expiration',
			__( 'No Expiration Text', 'military-discounts' ),
			array( $this, 'render_text_field' ),
			'md-settings-form-text',
			'md_form_text_section',
			array(
				'option'      => 'md_settings_form_text',
				'field'       => 'verified_no_expiration',
				'description' => __( 'Text for "No expiration" display.', 'military-discounts' ),
				'default'     => __( 'No expiration', 'military-discounts' ),
			)
		);

		add_settings_field(
			'verified_note',
			__( 'Verified Note', 'military-discounts' ),
			array( $this, 'render_text_field' ),
			'md-settings-form-text',
			'md_form_text_section',
			array(
				'option'      => 'md_settings_form_text',
				'field'       => 'verified_note',
				'description' => __( 'Note text displayed when user is verified.', 'military-discounts' ),
				'default'     => __( 'You are eligible for military discounts on applicable products and coupons.', 'military-discounts' ),
			)
		);

		// Pending Status
		add_settings_field(
			'pending_title',
			__( 'Pending Status Title', 'military-discounts' ),
			array( $this, 'render_text_field' ),
			'md-settings-form-text',
			'md_form_text_section',
			array(
				'option'      => 'md_settings_form_text',
				'field'       => 'pending_title',
				'description' => __( 'Title for pending verification status.', 'military-discounts' ),
				'default'     => __( 'Verification Pending', 'military-discounts' ),
			)
		);

		add_settings_field(
			'pending_description',
			__( 'Pending Status Description', 'military-discounts' ),
			array( $this, 'render_textarea_field' ),
			'md-settings-form-text',
			'md_form_text_section',
			array(
				'option'      => 'md_settings_form_text',
				'field'       => 'pending_description',
				'description' => __( 'Description text for pending verification status.', 'military-discounts' ),
				'default'     => __( 'Your verification request has been submitted and is being processed. You will receive an email notification once complete.', 'military-discounts' ),
			)
		);

		add_settings_field(
			'pending_type_label',
			__( 'Pending Type Label', 'military-discounts' ),
			array( $this, 'render_text_field' ),
			'md-settings-form-text',
			'md_form_text_section',
			array(
				'option'      => 'md_settings_form_text',
				'field'       => 'pending_type_label',
				'description' => __( 'Label for verification type in pending status.', 'military-discounts' ),
				'default'     => __( 'Type:', 'military-discounts' ),
			)
		);

		add_settings_field(
			'pending_submitted_label',
			__( 'Pending Submitted Label', 'military-discounts' ),
			array( $this, 'render_text_field' ),
			'md-settings-form-text',
			'md_form_text_section',
			array(
				'option'      => 'md_settings_form_text',
				'field'       => 'pending_submitted_label',
				'description' => __( 'Label for submission date in pending status.', 'military-discounts' ),
				'default'     => __( 'Submitted:', 'military-discounts' ),
			)
		);

		add_settings_field(
			'pending_status_label',
			__( 'Pending Status Label', 'military-discounts' ),
			array( $this, 'render_text_field' ),
			'md-settings-form-text',
			'md_form_text_section',
			array(
				'option'      => 'md_settings_form_text',
				'field'       => 'pending_status_label',
				'description' => __( 'Label for status in pending status.', 'military-discounts' ),
				'default'     => __( 'Status:', 'military-discounts' ),
			)
		);

		// Lockout Status
		add_settings_field(
			'lockout_title',
			__( 'Lockout Status Title', 'military-discounts' ),
			array( $this, 'render_text_field' ),
			'md-settings-form-text',
			'md_form_text_section',
			array(
				'option'      => 'md_settings_form_text',
				'field'       => 'lockout_title',
				'description' => __( 'Title for locked out status.', 'military-discounts' ),
				'default'     => __( 'Verification Locked', 'military-discounts' ),
			)
		);

		add_settings_field(
			'lockout_description',
			__( 'Lockout Status Description', 'military-discounts' ),
			array( $this, 'render_text_field' ),
			'md-settings-form-text',
			'md_form_text_section',
			array(
				'option'      => 'md_settings_form_text',
				'field'       => 'lockout_description',
				/* translators: %1$s: Verification type, %2$d: Minutes until retry */
				'description' => __( 'Description text for locked out status. Use %1$s for verification type and %2$d for minutes.', 'military-discounts' ),
				/* translators: %1$s: Verification type, %2$d: Minutes until retry */
				'default'     => __( 'Too many failed %1$s verification attempts. Please try again in %2$d minutes.', 'military-discounts' ),
			)
		);

		// Failed Attempts
		add_settings_field(
			'failed_veteran_text',
			__( 'Veteran Failed Attempts Text', 'military-discounts' ),
			array( $this, 'render_text_field' ),
			'md-settings-form-text',
			'md_form_text_section',
			array(
				'option'      => 'md_settings_form_text',
				'field'       => 'failed_veteran_text',
				/* translators: %1$d: Current failed attempts, %2$d: Maximum allowed attempts */
				'description' => __( 'Text for veteran failed attempts. Use %1$d for count and %2$d for max.', 'military-discounts' ),
				/* translators: %1$d: Current failed attempts, %2$d: Maximum allowed attempts */
				'default'     => __( 'Veteran verification: %1$d/%2$d failed attempts', 'military-discounts' ),
			)
		);

		add_settings_field(
			'failed_military_text',
			__( 'Military Failed Attempts Text', 'military-discounts' ),
			array( $this, 'render_text_field' ),
			'md-settings-form-text',
			'md_form_text_section',
			array(
				'option'      => 'md_settings_form_text',
				'field'       => 'failed_military_text',
				/* translators: %1$d: Current failed attempts, %2$d: Maximum allowed attempts */
				'description' => __( 'Text for military failed attempts. Use %1$d for count and %2$d for max.', 'military-discounts' ),
				/* translators: %1$d: Current failed attempts, %2$d: Maximum allowed attempts */
				'default'     => __( 'Military verification: %1$d/%2$d failed attempts', 'military-discounts' ),
			)
		);

		// Step 1: Type Selection
		add_settings_field(
			'step1_title',
			__( 'Step 1 Title', 'military-discounts' ),
			array( $this, 'render_text_field' ),
			'md-settings-form-text',
			'md_form_text_section',
			array(
				'option'      => 'md_settings_form_text',
				'field'       => 'step1_title',
				'description' => __( 'Title for step 1 (Select Type).', 'military-discounts' ),
				'default'     => __( 'Select Verification Type', 'military-discounts' ),
			)
		);

		add_settings_field(
			'veteran_radio_label',
			__( 'Veteran Radio Button Label', 'military-discounts' ),
			array( $this, 'render_text_field' ),
			'md-settings-form-text',
			'md_form_text_section',
			array(
				'option'      => 'md_settings_form_text',
				'field'       => 'veteran_radio_label',
				'description' => __( 'Label for veteran radio button.', 'military-discounts' ),
				'default'     => __( 'I am a Veteran', 'military-discounts' ),
			)
		);

		add_settings_field(
			'veteran_radio_desc',
			__( 'Veteran Radio Button Description', 'military-discounts' ),
			array( $this, 'render_text_field' ),
			'md-settings-form-text',
			'md_form_text_section',
			array(
				'option'      => 'md_settings_form_text',
				'field'       => 'veteran_radio_desc',
				'description' => __( 'Description for veteran radio button.', 'military-discounts' ),
				'default'     => __( 'Verify through VA records', 'military-discounts' ),
			)
		);

		add_settings_field(
			'military_radio_label',
			__( 'Military Radio Button Label', 'military-discounts' ),
			array( $this, 'render_text_field' ),
			'md-settings-form-text',
			'md_form_text_section',
			array(
				'option'      => 'md_settings_form_text',
				'field'       => 'military_radio_label',
				'description' => __( 'Label for active military radio button.', 'military-discounts' ),
				'default'     => __( 'I am Active Military', 'military-discounts' ),
			)
		);

		add_settings_field(
			'military_radio_desc',
			__( 'Military Radio Button Description', 'military-discounts' ),
			array( $this, 'render_text_field' ),
			'md-settings-form-text',
			'md_form_text_section',
			array(
				'option'      => 'md_settings_form_text',
				'field'       => 'military_radio_desc',
				'description' => __( 'Description for active military radio button.', 'military-discounts' ),
				'default'     => __( 'Verify with .mil email', 'military-discounts' ),
			)
		);

		// Step Labels
		add_settings_field(
			'step1_label',
			__( 'Step 1 Label', 'military-discounts' ),
			array( $this, 'render_text_field' ),
			'md-settings-form-text',
			'md_form_text_section',
			array(
				'option'      => 'md_settings_form_text',
				'field'       => 'step1_label',
				'description' => __( 'Label for step 1 in the progress indicator.', 'military-discounts' ),
				'default'     => __( 'Select Type', 'military-discounts' ),
			)
		);

		add_settings_field(
			'step2_label',
			__( 'Step 2 Label', 'military-discounts' ),
			array( $this, 'render_text_field' ),
			'md-settings-form-text',
			'md_form_text_section',
			array(
				'option'      => 'md_settings_form_text',
				'field'       => 'step2_label',
				'description' => __( 'Label for step 2 in the progress indicator.', 'military-discounts' ),
				'default'     => __( 'Enter Info', 'military-discounts' ),
			)
		);

		add_settings_field(
			'step3_label',
			__( 'Step 3 Label', 'military-discounts' ),
			array( $this, 'render_text_field' ),
			'md-settings-form-text',
			'md_form_text_section',
			array(
				'option'      => 'md_settings_form_text',
				'field'       => 'step3_label',
				'description' => __( 'Label for step 3 in the progress indicator.', 'military-discounts' ),
				'default'     => __( 'Submit', 'military-discounts' ),
			)
		);

		// Step 2: Enter Information
		add_settings_field(
			'step2_veteran_title',
			__( 'Step 2 Veteran Title', 'military-discounts' ),
			array( $this, 'render_text_field' ),
			'md-settings-form-text',
			'md_form_text_section',
			array(
				'option'      => 'md_settings_form_text',
				'field'       => 'step2_veteran_title',
				'description' => __( 'Title for step 2 when veteran type is selected.', 'military-discounts' ),
				'default'     => __( 'Enter Your Information', 'military-discounts' ),
			)
		);

		add_settings_field(
			'step2_military_title',
			__( 'Step 2 Military Title', 'military-discounts' ),
			array( $this, 'render_text_field' ),
			'md-settings-form-text',
			'md_form_text_section',
			array(
				'option'      => 'md_settings_form_text',
				'field'       => 'step2_military_title',
				'description' => __( 'Title for step 2 when military type is selected.', 'military-discounts' ),
				'default'     => __( 'Enter Your Military Email', 'military-discounts' ),
			)
		);

		// Step 3: Veteran Confirmation
		add_settings_field(
			'step3_veteran_title',
			__( 'Step 3 Veteran Title', 'military-discounts' ),
			array( $this, 'render_text_field' ),
			'md-settings-form-text',
			'md_form_text_section',
			array(
				'option'      => 'md_settings_form_text',
				'field'       => 'step3_veteran_title',
				'description' => __( 'Title for step 3 when veteran type is selected.', 'military-discounts' ),
				'default'     => __( 'Confirm Your Information', 'military-discounts' ),
			)
		);

		add_settings_field(
			'step3_veteran_desc',
			__( 'Step 3 Veteran Description', 'military-discounts' ),
			array( $this, 'render_text_field' ),
			'md-settings-form-text',
			'md_form_text_section',
			array(
				'option'      => 'md_settings_form_text',
				'field'       => 'step3_veteran_desc',
				'description' => __( 'Description for step 3 when veteran type is selected.', 'military-discounts' ),
				'default'     => __( 'Please review your information before submitting.', 'military-discounts' ),
			)
		);

		add_settings_field(
			'step3_verification_details',
			__( 'Verification Details Label', 'military-discounts' ),
			array( $this, 'render_text_field' ),
			'md-settings-form-text',
			'md_form_text_section',
			array(
				'option'      => 'md_settings_form_text',
				'field'       => 'step3_verification_details',
				'description' => __( 'Label for verification details section.', 'military-discounts' ),
				'default'     => __( 'Verification Details', 'military-discounts' ),
			)
		);

		// Step 3: Military OTP
		add_settings_field(
			'step3_military_title',
			__( 'Step 3 Military Title', 'military-discounts' ),
			array( $this, 'render_text_field' ),
			'md-settings-form-text',
			'md_form_text_section',
			array(
				'option'      => 'md_settings_form_text',
				'field'       => 'step3_military_title',
				'description' => __( 'Title for step 3 when military type is selected.', 'military-discounts' ),
				'default'     => __( 'Verify Your Military Email', 'military-discounts' ),
			)
		);

		add_settings_field(
			'step3_military_desc',
			__( 'Step 3 Military Description', 'military-discounts' ),
			array( $this, 'render_text_field' ),
			'md-settings-form-text',
			'md_form_text_section',
			array(
				'option'      => 'md_settings_form_text',
				'field'       => 'step3_military_desc',
				'description' => __( 'Description for step 3 when military type is selected.', 'military-discounts' ),
				'default'     => __( 'We\'ll send a verification code to:', 'military-discounts' ),
			)
		);

		add_settings_field(
			'step3_resend_link',
			__( 'Resend Code Link Text', 'military-discounts' ),
			array( $this, 'render_text_field' ),
			'md-settings-form-text',
			'md_form_text_section',
			array(
				'option'      => 'md_settings_form_text',
				'field'       => 'step3_resend_link',
				'description' => __( 'Text for the resend code link.', 'military-discounts' ),
				'default'     => __( 'Resend code', 'military-discounts' ),
			)
		);

		add_settings_field(
			'step3_otp_placeholder',
			__( 'OTP Input Placeholder', 'military-discounts' ),
			array( $this, 'render_text_field' ),
			'md-settings-form-text',
			'md_form_text_section',
			array(
				'option'      => 'md_settings_form_text',
				'field'       => 'step3_otp_placeholder',
				'description' => __( 'Placeholder for OTP input field.', 'military-discounts' ),
				'default'     => __( '000000', 'military-discounts' ),
			)
		);

		add_settings_field(
			'step3_otp_validation',
			__( 'OTP Validation Message', 'military-discounts' ),
			array( $this, 'render_text_field' ),
			'md-settings-form-text',
			'md_form_text_section',
			array(
				'option'      => 'md_settings_form_text',
				'field'       => 'step3_otp_validation',
				'description' => __( 'Message for OTP validation.', 'military-discounts' ),
				'default'     => __( 'Please enter a 6-digit code.', 'military-discounts' ),
			)
		);

		// Buttons and Actions
		add_settings_field(
			'button_back',
			__( 'Back Button Text', 'military-discounts' ),
			array( $this, 'render_text_field' ),
			'md-settings-form-text',
			'md_form_text_section',
			array(
				'option'      => 'md_settings_form_text',
				'field'       => 'button_back',
				'description' => __( 'Text for back buttons.', 'military-discounts' ),
				'default'     => __( 'Back', 'military-discounts' ),
			)
		);

		add_settings_field(
			'button_next',
			__( 'Next Button Text', 'military-discounts' ),
			array( $this, 'render_text_field' ),
			'md-settings-form-text',
			'md_form_text_section',
			array(
				'option'      => 'md_settings_form_text',
				'field'       => 'button_next',
				'description' => __( 'Text for next buttons.', 'military-discounts' ),
				'default'     => __( 'Next', 'military-discounts' ),
			)
		);

		add_settings_field(
			'button_submit',
			__( 'Submit Button Text', 'military-discounts' ),
			array( $this, 'render_text_field' ),
			'md-settings-form-text',
			'md_form_text_section',
			array(
				'option'      => 'md_settings_form_text',
				'field'       => 'button_submit',
				'description' => __( 'Text for submit buttons.', 'military-discounts' ),
				'default'     => __( 'Submit', 'military-discounts' ),
			)
		);

		add_settings_field(
			'button_verify_code',
			__( 'Verify Code Button Text', 'military-discounts' ),
			array( $this, 'render_text_field' ),
			'md-settings-form-text',
			'md_form_text_section',
			array(
				'option'      => 'md_settings_form_text',
				'field'       => 'button_verify_code',
				'description' => __( 'Text for verify code button.', 'military-discounts' ),
				'default'     => __( 'Verify Code', 'military-discounts' ),
			)
		);

		add_settings_field(
			'button_resend_code',
			__( 'Resend Code Button Text', 'military-discounts' ),
			array( $this, 'render_text_field' ),
			'md-settings-form-text',
			'md_form_text_section',
			array(
				'option'      => 'md_settings_form_text',
				'field'       => 'button_resend_code',
				'description' => __( 'Text for resend code button.', 'military-discounts' ),
				'default'     => __( 'Resend Code', 'military-discounts' ),
			)
		);

		// Form Fields
		add_settings_field(
			'select_placeholder',
			__( 'Select Placeholder', 'military-discounts' ),
			array( $this, 'render_text_field' ),
			'md-settings-form-text',
			'md_form_text_section',
			array(
				'option'      => 'md_settings_form_text',
				'field'       => 'select_placeholder',
				'description' => __( 'Placeholder text for select dropdowns.', 'military-discounts' ),
				'default'     => __( 'Select...', 'military-discounts' ),
			)
		);

		add_settings_field(
			'select_state_placeholder',
			__( 'State Select Placeholder', 'military-discounts' ),
			array( $this, 'render_text_field' ),
			'md-settings-form-text',
			'md_form_text_section',
			array(
				'option'      => 'md_settings_form_text',
				'field'       => 'select_state_placeholder',
				'description' => __( 'Placeholder text for state dropdown.', 'military-discounts' ),
				'default'     => __( 'Select State...', 'military-discounts' ),
			)
		);

		add_settings_field(
			'select_country_placeholder',
			__( 'Country Select Placeholder', 'military-discounts' ),
			array( $this, 'render_text_field' ),
			'md-settings-form-text',
			'md_form_text_section',
			array(
				'option'      => 'md_settings_form_text',
				'field'       => 'select_country_placeholder',
				'description' => __( 'Placeholder text for country dropdown.', 'military-discounts' ),
				'default'     => __( 'Select Country...', 'military-discounts' ),
			)
		);

		add_settings_field(
			'select_country_us',
			__( 'United States Option Text', 'military-discounts' ),
			array( $this, 'render_text_field' ),
			'md-settings-form-text',
			'md_form_text_section',
			array(
				'option'      => 'md_settings_form_text',
				'field'       => 'select_country_us',
				'description' => __( 'Text for United States country option.', 'military-discounts' ),
				'default'     => __( 'United States', 'military-discounts' ),
			)
		);

		// JavaScript Messages
		add_settings_field(
			'js_loading',
			__( 'Loading Message', 'military-discounts' ),
			array( $this, 'render_text_field' ),
			'md-settings-form-text',
			'md_form_text_section',
			array(
				'option'      => 'md_settings_form_text',
				'field'       => 'js_loading',
				'description' => __( 'Loading indicator text.', 'military-discounts' ),
				'default'     => __( 'Loading...', 'military-discounts' ),
			)
		);

		add_settings_field(
			'js_submitting',
			__( 'Submitting Message', 'military-discounts' ),
			array( $this, 'render_text_field' ),
			'md-settings-form-text',
			'md_form_text_section',
			array(
				'option'      => 'md_settings_form_text',
				'field'       => 'js_submitting',
				'description' => __( 'Submitting indicator text.', 'military-discounts' ),
				'default'     => __( 'Submitting...', 'military-discounts' ),
			)
		);

		add_settings_field(
			'js_sending_otp',
			__( 'Sending OTP Message', 'military-discounts' ),
			array( $this, 'render_text_field' ),
			'md-settings-form-text',
			'md_form_text_section',
			array(
				'option'      => 'md_settings_form_text',
				'field'       => 'js_sending_otp',
				'description' => __( 'Sending OTP indicator text.', 'military-discounts' ),
				'default'     => __( 'Sending code...', 'military-discounts' ),
			)
		);

		add_settings_field(
			'js_verifying_otp',
			__( 'Verifying OTP Message', 'military-discounts' ),
			array( $this, 'render_text_field' ),
			'md-settings-form-text',
			'md_form_text_section',
			array(
				'option'      => 'md_settings_form_text',
				'field'       => 'js_verifying_otp',
				'description' => __( 'Verifying OTP indicator text.', 'military-discounts' ),
				'default'     => __( 'Verifying...', 'military-discounts' ),
			)
		);

		add_settings_field(
			'js_error_occurred',
			__( 'Error Occurred Message', 'military-discounts' ),
			array( $this, 'render_text_field' ),
			'md-settings-form-text',
			'md_form_text_section',
			array(
				'option'      => 'md_settings_form_text',
				'field'       => 'js_error_occurred',
				'description' => __( 'Generic error message.', 'military-discounts' ),
				'default'     => __( 'An error occurred. Please try again.', 'military-discounts' ),
			)
		);

		add_settings_field(
			'js_required_field',
			__( 'Required Field Message', 'military-discounts' ),
			array( $this, 'render_text_field' ),
			'md-settings-form-text',
			'md_form_text_section',
			array(
				'option'      => 'md_settings_form_text',
				'field'       => 'js_required_field',
				'description' => __( 'Required field validation message.', 'military-discounts' ),
				'default'     => __( 'This field is required.', 'military-discounts' ),
			)
		);

		add_settings_field(
			'js_invalid_email',
			__( 'Invalid Email Message', 'military-discounts' ),
			array( $this, 'render_text_field' ),
			'md-settings-form-text',
			'md_form_text_section',
			array(
				'option'      => 'md_settings_form_text',
				'field'       => 'js_invalid_email',
				'description' => __( 'Invalid email validation message.', 'military-discounts' ),
				'default'     => __( 'Please enter a valid email address.', 'military-discounts' ),
			)
		);

		add_settings_field(
			'js_select_type',
			__( 'Select Type Message', 'military-discounts' ),
			array( $this, 'render_text_field' ),
			'md-settings-form-text',
			'md_form_text_section',
			array(
				'option'      => 'md_settings_form_text',
				'field'       => 'js_select_type',
				'description' => __( 'Message when no verification type is selected.', 'military-discounts' ),
				'default'     => __( 'Please select a verification type.', 'military-discounts' ),
			)
		);

		add_settings_field(
			'js_otp_sent',
			__( 'OTP Sent Message', 'military-discounts' ),
			array( $this, 'render_text_field' ),
			'md-settings-form-text',
			'md_form_text_section',
			array(
				'option'      => 'md_settings_form_text',
				'field'       => 'js_otp_sent',
				'description' => __( 'Message when OTP is sent.', 'military-discounts' ),
				'default'     => __( 'Verification code sent to your email.', 'military-discounts' ),
			)
		);

		add_settings_field(
			'js_otp_resent',
			__( 'OTP Resent Message', 'military-discounts' ),
			array( $this, 'render_text_field' ),
			'md-settings-form-text',
			'md_form_text_section',
			array(
				'option'      => 'md_settings_form_text',
				'field'       => 'js_otp_resent',
				'description' => __( 'Message when OTP is resent.', 'military-discounts' ),
				'default'     => __( "We've sent another code to your email.", 'military-discounts' ),
			)
		);

		add_settings_field(
			'js_redirecting',
			__( 'Redirecting Message', 'military-discounts' ),
			array( $this, 'render_text_field' ),
			'md-settings-form-text',
			'md_form_text_section',
			array(
				'option'      => 'md_settings_form_text',
				'field'       => 'js_redirecting',
				'description' => __( 'Redirecting message.', 'military-discounts' ),
				'default'     => __( 'Redirecting...', 'military-discounts' ),
			)
		);

		add_settings_field(
			'js_refreshing',
			__( 'Refreshing Message', 'military-discounts' ),
			array( $this, 'render_text_field' ),
			'md-settings-form-text',
			'md_form_text_section',
			array(
				'option'      => 'md_settings_form_text',
				'field'       => 'js_refreshing',
				'description' => __( 'Refreshing page message.', 'military-discounts' ),
				'default'     => __( 'Refreshing page...', 'military-discounts' ),
			)
		);
	}

	/**
	 * Register verification security settings.
	 */
	private function register_security_settings() {
		register_setting(
			'md_settings_security',
			'md_settings_security',
			array(
				'sanitize_callback' => array( $this, 'sanitize_security_settings' ),
			)
		);

		add_settings_section(
			'md_security_section',
			__( 'Verification Security', 'military-discounts' ),
			array( $this, 'render_security_section' ),
			'md-settings-security'
		);

		add_settings_field(
			'enable_lockout',
			__( 'Enable Lockout Feature', 'military-discounts' ),
			array( $this, 'render_checkbox_field' ),
			'md-settings-security',
			'md_security_section',
			array(
				'option'  => 'md_settings_security',
				'field'   => 'enable_lockout',
				'label'   => __( 'Enable failed attempts tracking and lockout feature', 'military-discounts' ),
				'default' => true,
			)
		);

		add_settings_field(
			'max_failed_veteran_attempts',
			__( 'Max Failed Veteran Attempts', 'military-discounts' ),
			array( $this, 'render_number_field' ),
			'md-settings-security',
			'md_security_section',
			array(
				'option'      => 'md_settings_security',
				'field'       => 'max_failed_veteran_attempts',
				'description' => __( 'Number of failed veteran verification attempts before lockout', 'military-discounts' ),
				'default'     => 5,
				'min'         => 1,
				'max'         => 20,
			)
		);

		add_settings_field(
			'veteran_lockout_duration',
			__( 'Veteran Lockout Duration (minutes)', 'military-discounts' ),
			array( $this, 'render_number_field' ),
			'md-settings-security',
			'md_security_section',
			array(
				'option'      => 'md_settings_security',
				'field'       => 'veteran_lockout_duration',
				'description' => __( 'Minutes to lock out after maximum failed veteran attempts', 'military-discounts' ),
				'default'     => 60,
				'min'         => 5,
				'max'         => 1440,
			)
		);

		add_settings_field(
			'max_failed_military_attempts',
			__( 'Max Failed Military Attempts', 'military-discounts' ),
			array( $this, 'render_number_field' ),
			'md-settings-security',
			'md_security_section',
			array(
				'option'      => 'md_settings_security',
				'field'       => 'max_failed_military_attempts',
				'description' => __( 'Number of failed military verification attempts before lockout', 'military-discounts' ),
				'default'     => 5,
				'min'         => 1,
				'max'         => 20,
			)
		);

		add_settings_field(
			'military_lockout_duration',
			__( 'Military Lockout Duration (minutes)', 'military-discounts' ),
			array( $this, 'render_number_field' ),
			'md-settings-security',
			'md_security_section',
			array(
				'option'      => 'md_settings_security',
				'field'       => 'military_lockout_duration',
				'description' => __( 'Minutes to lock out after maximum failed military attempts', 'military-discounts' ),
				'default'     => 60,
				'min'         => 5,
				'max'         => 1440,
			)
		);

		add_settings_field(
			'send_lockout_notification',
			__( 'Lockout Notification Email', 'military-discounts' ),
			array( $this, 'render_checkbox_field' ),
			'md-settings-security',
			'md_security_section',
			array(
				'option'      => 'md_settings_security',
				'field'       => 'send_lockout_notification',
				'label'       => __( 'Send email notification when user is locked out', 'military-discounts' ),
				'default'     => true,
			)
		);
	}

	/**
	 * Register general settings.
	 */
	private function register_general_settings() {
		register_setting(
			'md_settings_general',
			'md_settings_general',
			array(
				'sanitize_callback' => array( $this, 'sanitize_general_settings' ),
			)
		);

		add_settings_section(
			'md_general_section',
			__( 'General Settings', 'military-discounts' ),
			array( $this, 'render_general_section' ),
			'md-settings-general'
		);

		add_settings_field(
			'enabled',
			__( 'Enable Plugin', 'military-discounts' ),
			array( $this, 'render_checkbox_field' ),
			'md-settings-general',
			'md_general_section',
			array(
				'option'  => 'md_settings_general',
				'field'   => 'enabled',
				'label'   => __( 'Enable military discount verification', 'military-discounts' ),
				'default' => true,
			)
		);

		add_settings_field(
			'reverification_interval',
			__( 'Reverification Interval', 'military-discounts' ),
			array( $this, 'render_number_field' ),
			'md-settings-general',
			'md_general_section',
			array(
				'option'      => 'md_settings_general',
				'field'       => 'reverification_interval',
				'description' => __( 'Days before users must re-verify. Set to 0 for never.', 'military-discounts' ),
				'default'     => 365,
				'min'         => 0,
			)
		);

		add_settings_field(
			'reverification_behavior',
			__( 'Reverification Behavior', 'military-discounts' ),
			array( $this, 'render_select_field' ),
			'md-settings-general',
			'md_general_section',
			array(
				'option'  => 'md_settings_general',
				'field'   => 'reverification_behavior',
				'options' => array(
					'silent'  => __( 'Silent - Just expire verification', 'military-discounts' ),
					'notify'  => __( 'Notify - Send reminder email', 'military-discounts' ),
					'both'    => __( 'Both - Expire and send reminder', 'military-discounts' ),
				),
				'default' => 'silent',
			)
		);

		add_settings_field(
			'disable_encryption',
			__( 'Disable Encryption', 'military-discounts' ),
			array( $this, 'render_checkbox_field' ),
			'md-settings-general',
			'md_general_section',
			array(
				'option'      => 'md_settings_general',
				'field'       => 'disable_encryption',
				'label'       => __( 'Disable encryption (testing only)', 'military-discounts' ),
				'description' => __( 'WARNING: Only enable for testing. Sensitive data will not be encrypted.', 'military-discounts' ),
				'default'     => false,
			)
		);

		add_settings_field(
			'redirect_url',
			__( 'Success Redirect URL', 'military-discounts' ),
			array( $this, 'render_text_field' ),
			'md-settings-general',
			'md_general_section',
			array(
				'option'      => 'md_settings_general',
				'field'       => 'redirect_url',
				'description' => __( 'URL to redirect users to after successful verification. Leave empty to refresh the current page.', 'military-discounts' ),
				'placeholder' => __( 'https://example.com/success', 'military-discounts' ),
				'default'     => '',
			)
		);

		add_settings_field(
			'redirect_delay',
			__( 'Redirect Delay (ms)', 'military-discounts' ),
			array( $this, 'render_number_field' ),
			'md-settings-general',
			'md_general_section',
			array(
				'option'      => 'md_settings_general',
				'field'       => 'redirect_delay',
				'description' => __( 'Time in milliseconds to wait before redirecting after successful verification. Default is 2000ms (2 seconds).', 'military-discounts' ),
				'default'     => 2000,
				'min'         => 0,
				'max'         => 10000,
			)
		);

		add_settings_field(
			'page_title',
			__( 'Page Title', 'military-discounts' ),
			array( $this, 'render_text_field' ),
			'md-settings-general',
			'md_general_section',
			array(
				'option'      => 'md_settings_general',
				'field'       => 'page_title',
				'description' => __( 'Custom title for the Military Discounts page in My Account. Leave empty to use default.', 'military-discounts' ),
				'placeholder' => __( 'Military Discounts', 'military-discounts' ),
				'default'     => '',
			)
		);

		add_settings_field(
			'menu_order',
			__( 'Menu Order', 'military-discounts' ),
			array( $this, 'render_number_field' ),
			'md-settings-general',
			'md_general_section',
			array(
				'option'      => 'md_settings_general',
				'field'       => 'menu_order',
				'description' => __( 'Order of the Military Discounts link in My Account menu. Lower numbers appear first. Default is 10.', 'military-discounts' ),
				'default'     => 10,
				'min'         => 0,
				'max'         => 100,
			)
		);
	}

	/**
	 * Register VA API settings.
	 */
	private function register_va_api_settings() {
		register_setting(
			'md_settings_va_api',
			'md_settings_va_api',
			array(
				'sanitize_callback' => array( $this, 'sanitize_va_api_settings' ),
			)
		);

		add_settings_section(
			'md_va_api_section',
			__( 'VA API Settings', 'military-discounts' ),
			array( $this, 'render_va_api_section' ),
			'md-settings-va-api'
		);

		add_settings_field(
			'enabled',
			__( 'Enable VA API', 'military-discounts' ),
			array( $this, 'render_checkbox_field' ),
			'md-settings-va-api',
			'md_va_api_section',
			array(
				'option'  => 'md_settings_va_api',
				'field'   => 'enabled',
				'label'   => __( 'Enable veteran verification via VA API', 'military-discounts' ),
				'default' => true,
			)
		);

		add_settings_field(
			'api_key',
			__( 'API Key', 'military-discounts' ),
			array( $this, 'render_password_field' ),
			'md-settings-va-api',
			'md_va_api_section',
			array(
				'option'      => 'md_settings_va_api',
				'field'       => 'api_key',
				'description' => __( 'Your VA API key from developer.va.gov', 'military-discounts' ),
			)
		);

		add_settings_field(
			'api_url',
			__( 'Custom API URL', 'military-discounts' ),
			array( $this, 'render_text_field' ),
			'md-settings-va-api',
			'md_va_api_section',
			array(
				'option'      => 'md_settings_va_api',
				'field'       => 'api_url',
				'description' => __( 'Leave empty to use default. Use for testing with custom endpoints.', 'military-discounts' ),
				'placeholder' => 'https://api.va.gov/services/veteran-confirmation/v1',
			)
		);

		add_settings_field(
			'sandbox',
			__( 'Sandbox Mode', 'military-discounts' ),
			array( $this, 'render_checkbox_field' ),
			'md-settings-va-api',
			'md_va_api_section',
			array(
				'option'      => 'md_settings_va_api',
				'field'       => 'sandbox',
				'label'       => __( 'Use sandbox API (for testing)', 'military-discounts' ),
				'description' => __( 'Enable for testing. Disable for production.', 'military-discounts' ),
				'default'     => true,
			)
		);
	}

	/**
	 * Register Military OTP settings.
	 */
	private function register_military_otp_settings() {
		register_setting(
			'md_settings_military_otp',
			'md_settings_military_otp',
			array(
				'sanitize_callback' => array( $this, 'sanitize_military_otp_settings' ),
			)
		);

		add_settings_section(
			'md_military_otp_section',
			__( 'Military Email OTP Settings', 'military-discounts' ),
			array( $this, 'render_military_otp_section' ),
			'md-settings-military-otp'
		);

		add_settings_field(
			'enabled',
			__( 'Enable Military OTP', 'military-discounts' ),
			array( $this, 'render_checkbox_field' ),
			'md-settings-military-otp',
			'md_military_otp_section',
			array(
				'option'  => 'md_settings_military_otp',
				'field'   => 'enabled',
				'label'   => __( 'Enable military verification via email OTP', 'military-discounts' ),
				'default' => true,
			)
		);

		add_settings_field(
			'whitelist_patterns',
			__( 'Email Whitelist', 'military-discounts' ),
			array( $this, 'render_textarea_field' ),
			'md-settings-military-otp',
			'md_military_otp_section',
			array(
				'option'      => 'md_settings_military_otp',
				'field'       => 'whitelist_patterns',
				'description' => __( 'Comma-separated patterns. Use * as wildcard. Example: *.mil', 'military-discounts' ),
				'default'     => '*.mil',
			)
		);

		add_settings_field(
			'blacklist_patterns',
			__( 'Email Blacklist', 'military-discounts' ),
			array( $this, 'render_textarea_field' ),
			'md-settings-military-otp',
			'md_military_otp_section',
			array(
				'option'      => 'md_settings_military_otp',
				'field'       => 'blacklist_patterns',
				'description' => __( 'Comma-separated patterns to block. Example: *ctr.mil,*civ.mil', 'military-discounts' ),
				'default'     => '*ctr.mil,*civ.mil',
			)
		);

		add_settings_field(
			'otp_expiry',
			__( 'OTP Expiry (minutes)', 'military-discounts' ),
			array( $this, 'render_number_field' ),
			'md-settings-military-otp',
			'md_military_otp_section',
			array(
				'option'  => 'md_settings_military_otp',
				'field'   => 'otp_expiry',
				'default' => 15,
				'min'     => 5,
				'max'     => 60,
			)
		);

		add_settings_field(
			'name_match_type',
			__( 'Require Name Match', 'military-discounts' ),
			array( $this, 'render_select_field' ),
			'md-settings-military-otp',
			'md_military_otp_section',
			array(
				'option'      => 'md_settings_military_otp',
				'field'       => 'name_match_type',
				'options'     => array(
					'both'   => __( 'Both - Email must contain first and last name', 'military-discounts' ),
					'first'  => __( 'First - Email must contain first name', 'military-discounts' ),
					'none'   => __( 'None - No name matching required', 'military-discounts' ),
				),
				'description' => __( 'The email local part (e.g., john.smith@) must contain the specified name(s). Allows for middle names and numbers (e.g., john.jones.smith2@).', 'military-discounts' ),
				'default'     => 'none',
			)
		);

		add_settings_field(
			'lock_billing_first_name',
			__( 'Lock Billing First Name', 'military-discounts' ),
			array( $this, 'render_checkbox_field' ),
			'md-settings-military-otp',
			'md_military_otp_section',
			array(
				'option'      => 'md_settings_military_otp',
				'field'       => 'lock_billing_first_name',
				'label'       => __( 'Lock billing first name after military verification', 'military-discounts' ),
				'description' => __( 'Prevents customers from changing their billing first name after verification.', 'military-discounts' ),
				'default'     => false,
			)
		);

		add_settings_field(
			'lock_billing_last_name',
			__( 'Lock Billing Last Name', 'military-discounts' ),
			array( $this, 'render_checkbox_field' ),
			'md-settings-military-otp',
			'md_military_otp_section',
			array(
				'option'      => 'md_settings_military_otp',
				'field'       => 'lock_billing_last_name',
				'label'       => __( 'Lock billing last name after military verification', 'military-discounts' ),
				'description' => __( 'Prevents customers from changing their billing last name after verification.', 'military-discounts' ),
				'default'     => false,
			)
		);
	}

	/**
	 * Register queue settings.
	 */
	private function register_queue_settings() {
		register_setting(
			'md_settings_queue',
			'md_settings_queue',
			array(
				'sanitize_callback' => array( $this, 'sanitize_queue_settings' ),
			)
		);

		add_settings_section(
			'md_queue_section',
			__( 'Queue Settings', 'military-discounts' ),
			array( $this, 'render_queue_section' ),
			'md-settings-queue'
		);

		add_settings_field(
			'retry_interval',
			__( 'Retry Interval (hours)', 'military-discounts' ),
			array( $this, 'render_number_field' ),
			'md-settings-queue',
			'md_queue_section',
			array(
				'option'      => 'md_settings_queue',
				'field'       => 'retry_interval',
				'description' => __( 'Hours between retry attempts for failed verifications.', 'military-discounts' ),
				'default'     => 1,
				'min'         => 1,
			)
		);

		add_settings_field(
			'max_retries',
			__( 'Maximum Retries', 'military-discounts' ),
			array( $this, 'render_number_field' ),
			'md-settings-queue',
			'md_queue_section',
			array(
				'option'      => 'md_settings_queue',
				'field'       => 'max_retries',
				'description' => __( 'Maximum retry attempts before marking as failed.', 'military-discounts' ),
				'default'     => 5,
				'min'         => 1,
				'max'         => 20,
			)
		);
	}

	/**
	 * Register logs settings.
	 */
	private function register_logs_settings() {
		register_setting(
			'md_settings_logs',
			'md_settings_logs',
			array(
				'sanitize_callback' => array( $this, 'sanitize_logs_settings' ),
			)
		);

		add_settings_section(
			'md_logs_section',
			__( 'Log Settings', 'military-discounts' ),
			null,
			'md-settings-logs'
		);

		add_settings_field(
			'retention_days',
			__( 'Log Retention (days)', 'military-discounts' ),
			array( $this, 'render_number_field' ),
			'md-settings-logs',
			'md_logs_section',
			array(
				'option'      => 'md_settings_logs',
				'field'       => 'retention_days',
				'description' => __( 'Days to retain logs. Set to 0 to keep indefinitely.', 'military-discounts' ),
				'default'     => 30,
				'min'         => 0,
			)
		);
	}

	/**
	 * Render section descriptions.
	 */
	public function render_general_section() {
		echo '<p>' . esc_html__( 'Configure general plugin settings.', 'military-discounts' ) . '</p>';

		// Show encryption status.
		$encryption = new MD_Encryption();
		if ( $encryption->has_env_key() ) {
			echo '<div class="notice notice-success inline"><p>' . esc_html__( 'Encryption key detected from .env file.', 'military-discounts' ) . '</p></div>';
		} else {
			echo '<div class="notice notice-warning inline"><p>' . esc_html__( 'Using auto-generated encryption key stored in database. For better security, define MD_ENCRYPTION_KEY in your .env file.', 'military-discounts' ) . '</p></div>';
		}
	}

	public function render_va_api_section() {
		echo '<p>' . esc_html__( 'Configure VA Veteran Confirmation API settings. Get your API key from', 'military-discounts' ) . ' <a href="https://developer.va.gov" target="_blank">developer.va.gov</a></p>';
	}

	public function render_military_otp_section() {
		echo '<p>' . esc_html__( 'Configure military email verification via one-time passcode.', 'military-discounts' ) . '</p>';
	}

	public function render_queue_section() {
		echo '<p>' . esc_html__( 'Configure how failed verification attempts are retried.', 'military-discounts' ) . '</p>';
	}

	public function render_security_section() {
		echo '<p>' . esc_html__( 'Configure security settings for verification attempts.', 'military-discounts' ) . '</p>';
	}

	public function render_form_text_section() {
		echo '<p>' . esc_html__( 'Customize all text strings displayed in the verification form and process.', 'military-discounts' ) . '</p>';
	}

	/**
	 * Sanitize form text settings.
	 *
	 * @param array $input Input settings.
	 * @return array Sanitized settings.
	 */
	public function sanitize_form_text_settings( $input ) {
		$sanitized = array();

		// Page Header
		$sanitized['page_subtitle'] = isset( $input['page_subtitle'] ) ? sanitize_text_field( $input['page_subtitle'] ) : __( 'Verify your veteran or active military status to access exclusive discounts.', 'military-discounts' );

		// Verified Status
		$sanitized['verified_veteran_title'] = isset( $input['verified_veteran_title'] ) ? sanitize_text_field( $input['verified_veteran_title'] ) : __( 'Veteran Status Verified', 'military-discounts' );
		$sanitized['verified_military_title'] = isset( $input['verified_military_title'] ) ? sanitize_text_field( $input['verified_military_title'] ) : __( 'Active Military Status Verified', 'military-discounts' );
		$sanitized['verified_valid_until'] = isset( $input['verified_valid_until'] ) ? sanitize_text_field( $input['verified_valid_until'] ) : (
			/* translators: %s: Expiration date */
			__( 'Valid until %s', 'military-discounts' )
			);
		$sanitized['verified_no_expiration'] = isset( $input['verified_no_expiration'] ) ? sanitize_text_field( $input['verified_no_expiration'] ) : __( 'No expiration', 'military-discounts' );
		$sanitized['verified_note'] = isset( $input['verified_note'] ) ? sanitize_text_field( $input['verified_note'] ) : __( 'You are eligible for military discounts on applicable products and coupons.', 'military-discounts' );

		// Pending Status
		$sanitized['pending_title'] = isset( $input['pending_title'] ) ? sanitize_text_field( $input['pending_title'] ) : __( 'Verification Pending', 'military-discounts' );
		$sanitized['pending_description'] = isset( $input['pending_description'] ) ? sanitize_textarea_field( $input['pending_description'] ) : __( 'Your verification request has been submitted and is being processed. You will receive an email notification once complete.', 'military-discounts' );
		$sanitized['pending_type_label'] = isset( $input['pending_type_label'] ) ? sanitize_text_field( $input['pending_type_label'] ) : __( 'Type:', 'military-discounts' );
		$sanitized['pending_submitted_label'] = isset( $input['pending_submitted_label'] ) ? sanitize_text_field( $input['pending_submitted_label'] ) : __( 'Submitted:', 'military-discounts' );
		$sanitized['pending_status_label'] = isset( $input['pending_status_label'] ) ? sanitize_text_field( $input['pending_status_label'] ) : __( 'Status:', 'military-discounts' );

		// Lockout Status
		$sanitized['lockout_title'] = isset( $input['lockout_title'] ) ? sanitize_text_field( $input['lockout_title'] ) : __( 'Verification Locked', 'military-discounts' );
		$sanitized['lockout_description'] = isset( $input['lockout_description'] ) ? sanitize_text_field( $input['lockout_description'] ) : (
			/* translators: %1$s: Verification type, %2$d: Minutes until retry */
			__( 'Too many failed %1$s verification attempts. Please try again in %2$d minutes.', 'military-discounts' )
		);

		// Failed Attempts
		$sanitized['failed_veteran_text'] = isset( $input['failed_veteran_text'] ) ? sanitize_text_field( $input['failed_veteran_text'] ) : (
			/* translators: %1$d: Current failed attempts, %2$d: Maximum allowed attempts */
			__( 'Veteran verification: %1$d/%2$d failed attempts', 'military-discounts' )
		);
		$sanitized['failed_military_text'] = isset( $input['failed_military_text'] ) ? sanitize_text_field( $input['failed_military_text'] ) : (
			/* translators: %1$d: Current failed attempts, %2$d: Maximum allowed attempts */
			__( 'Military verification: %1$d/%2$d failed attempts', 'military-discounts' )
		);

		// Step 1: Type Selection
		$sanitized['step1_title'] = isset( $input['step1_title'] ) ? sanitize_text_field( $input['step1_title'] ) : __( 'Select Verification Type', 'military-discounts' );
		$sanitized['veteran_radio_label'] = isset( $input['veteran_radio_label'] ) ? sanitize_text_field( $input['veteran_radio_label'] ) : __( 'I am a Veteran', 'military-discounts' );
		$sanitized['veteran_radio_desc'] = isset( $input['veteran_radio_desc'] ) ? sanitize_text_field( $input['veteran_radio_desc'] ) : __( 'Verify through VA records', 'military-discounts' );
		$sanitized['military_radio_label'] = isset( $input['military_radio_label'] ) ? sanitize_text_field( $input['military_radio_label'] ) : __( 'I am Active Military', 'military-discounts' );
		$sanitized['military_radio_desc'] = isset( $input['military_radio_desc'] ) ? sanitize_text_field( $input['military_radio_desc'] ) : __( 'Verify with .mil email', 'military-discounts' );

		// Step Labels
		$sanitized['step1_label'] = isset( $input['step1_label'] ) ? sanitize_text_field( $input['step1_label'] ) : __( 'Select Type', 'military-discounts' );
		$sanitized['step2_label'] = isset( $input['step2_label'] ) ? sanitize_text_field( $input['step2_label'] ) : __( 'Enter Info', 'military-discounts' );
		$sanitized['step3_label'] = isset( $input['step3_label'] ) ? sanitize_text_field( $input['step3_label'] ) : __( 'Submit', 'military-discounts' );

		// Step 2: Enter Information
		$sanitized['step2_veteran_title'] = isset( $input['step2_veteran_title'] ) ? sanitize_text_field( $input['step2_veteran_title'] ) : __( 'Enter Your Information', 'military-discounts' );
		$sanitized['step2_military_title'] = isset( $input['step2_military_title'] ) ? sanitize_text_field( $input['step2_military_title'] ) : __( 'Enter Your Military Email', 'military-discounts' );

		// Step 3: Veteran Confirmation
		$sanitized['step3_veteran_title'] = isset( $input['step3_veteran_title'] ) ? sanitize_text_field( $input['step3_veteran_title'] ) : __( 'Confirm Your Information', 'military-discounts' );
		$sanitized['step3_veteran_desc'] = isset( $input['step3_veteran_desc'] ) ? sanitize_text_field( $input['step3_veteran_desc'] ) : __( 'Please review your information before submitting.', 'military-discounts' );
		$sanitized['step3_verification_details'] = isset( $input['step3_verification_details'] ) ? sanitize_text_field( $input['step3_verification_details'] ) : __( 'Verification Details', 'military-discounts' );

		// Step 3: Military OTP
		$sanitized['step3_military_title'] = isset( $input['step3_military_title'] ) ? sanitize_text_field( $input['step3_military_title'] ) : __( 'Verify Your Military Email', 'military-discounts' );
		$sanitized['step3_military_desc'] = isset( $input['step3_military_desc'] ) ? sanitize_text_field( $input['step3_military_desc'] ) : __( 'We\'ll send a verification code to:', 'military-discounts' );
		$sanitized['step3_resend_link'] = isset( $input['step3_resend_link'] ) ? sanitize_text_field( $input['step3_resend_link'] ) : __( 'Resend code', 'military-discounts' );
		$sanitized['step3_otp_placeholder'] = isset( $input['step3_otp_placeholder'] ) ? sanitize_text_field( $input['step3_otp_placeholder'] ) : __( '000000', 'military-discounts' );
		$sanitized['step3_otp_validation'] = isset( $input['step3_otp_validation'] ) ? sanitize_text_field( $input['step3_otp_validation'] ) : __( 'Please enter a 6-digit code.', 'military-discounts' );

		// Buttons and Actions
		$sanitized['button_back'] = isset( $input['button_back'] ) ? sanitize_text_field( $input['button_back'] ) : __( 'Back', 'military-discounts' );
		$sanitized['button_next'] = isset( $input['button_next'] ) ? sanitize_text_field( $input['button_next'] ) : __( 'Next', 'military-discounts' );
		$sanitized['button_submit'] = isset( $input['button_submit'] ) ? sanitize_text_field( $input['button_submit'] ) : __( 'Submit', 'military-discounts' );
		$sanitized['button_verify_code'] = isset( $input['button_verify_code'] ) ? sanitize_text_field( $input['button_verify_code'] ) : __( 'Verify Code', 'military-discounts' );
		$sanitized['button_resend_code'] = isset( $input['button_resend_code'] ) ? sanitize_text_field( $input['button_resend_code'] ) : __( 'Resend Code', 'military-discounts' );

		// Form Fields
		$sanitized['select_placeholder'] = isset( $input['select_placeholder'] ) ? sanitize_text_field( $input['select_placeholder'] ) : __( 'Select...', 'military-discounts' );
		$sanitized['select_state_placeholder'] = isset( $input['select_state_placeholder'] ) ? sanitize_text_field( $input['select_state_placeholder'] ) : __( 'Select State...', 'military-discounts' );
		$sanitized['select_country_placeholder'] = isset( $input['select_country_placeholder'] ) ? sanitize_text_field( $input['select_country_placeholder'] ) : __( 'Select Country...', 'military-discounts' );
		$sanitized['select_country_us'] = isset( $input['select_country_us'] ) ? sanitize_text_field( $input['select_country_us'] ) : __( 'United States', 'military-discounts' );

		// JavaScript Messages
		$sanitized['js_loading'] = isset( $input['js_loading'] ) ? sanitize_text_field( $input['js_loading'] ) : __( 'Loading...', 'military-discounts' );
		$sanitized['js_submitting'] = isset( $input['js_submitting'] ) ? sanitize_text_field( $input['js_submitting'] ) : __( 'Submitting...', 'military-discounts' );
		$sanitized['js_sending_otp'] = isset( $input['js_sending_otp'] ) ? sanitize_text_field( $input['js_sending_otp'] ) : __( 'Sending code...', 'military-discounts' );
		$sanitized['js_verifying_otp'] = isset( $input['js_verifying_otp'] ) ? sanitize_text_field( $input['js_verifying_otp'] ) : __( 'Verifying...', 'military-discounts' );
		$sanitized['js_error_occurred'] = isset( $input['js_error_occurred'] ) ? sanitize_text_field( $input['js_error_occurred'] ) : __( 'An error occurred. Please try again.', 'military-discounts' );
		$sanitized['js_required_field'] = isset( $input['js_required_field'] ) ? sanitize_text_field( $input['js_required_field'] ) : __( 'This field is required.', 'military-discounts' );
		$sanitized['js_invalid_email'] = isset( $input['js_invalid_email'] ) ? sanitize_text_field( $input['js_invalid_email'] ) : __( 'Please enter a valid email address.', 'military-discounts' );
		$sanitized['js_select_type'] = isset( $input['js_select_type'] ) ? sanitize_text_field( $input['js_select_type'] ) : __( 'Please select a verification type.', 'military-discounts' );
		$sanitized['js_otp_sent'] = isset( $input['js_otp_sent'] ) ? sanitize_text_field( $input['js_otp_sent'] ) : __( 'Verification code sent to your email.', 'military-discounts' );
		$sanitized['js_otp_resent'] = isset( $input['js_otp_resent'] ) ? sanitize_text_field( $input['js_otp_resent'] ) : __( "We've sent another code to your email.", 'military-discounts' );
		$sanitized['js_redirecting'] = isset( $input['js_redirecting'] ) ? sanitize_text_field( $input['js_redirecting'] ) : __( 'Redirecting...', 'military-discounts' );
		$sanitized['js_refreshing'] = isset( $input['js_refreshing'] ) ? sanitize_text_field( $input['js_refreshing'] ) : __( 'Refreshing page...', 'military-discounts' );

		return $sanitized;
	}

	/**
	 * Sanitize security settings.
	 *
	 * @param array $input Input settings.
	 * @return array Sanitized settings.
	 */
	public function sanitize_security_settings( $input ) {
		$sanitized = array();

		$sanitized['enable_lockout']           = ! empty( $input['enable_lockout'] );
		$sanitized['max_failed_veteran_attempts'] = isset( $input['max_failed_veteran_attempts'] ) ? min( 20, max( 1, absint( $input['max_failed_veteran_attempts'] ) ) ) : 5;
		$sanitized['veteran_lockout_duration']    = isset( $input['veteran_lockout_duration'] ) ? min( 1440, max( 5, absint( $input['veteran_lockout_duration'] ) ) ) : 60;
		$sanitized['max_failed_military_attempts'] = isset( $input['max_failed_military_attempts'] ) ? min( 20, max( 1, absint( $input['max_failed_military_attempts'] ) ) ) : 5;
		$sanitized['military_lockout_duration']    = isset( $input['military_lockout_duration'] ) ? min( 1440, max( 5, absint( $input['military_lockout_duration'] ) ) ) : 60;
		$sanitized['send_lockout_notification']    = ! empty( $input['send_lockout_notification'] );

		return $sanitized;
	}

	/**
	 * Render field types.
	 */
	public function render_checkbox_field( $args ) {
		$options = get_option( $args['option'], array() );
		$value   = isset( $options[ $args['field'] ] ) ? $options[ $args['field'] ] : ( $args['default'] ?? false );
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( $args['option'] . '[' . $args['field'] . ']' ); ?>"
				value="1" <?php checked( $value, true ); ?>>
			<?php echo isset( $args['label'] ) ? esc_html( $args['label'] ) : ''; ?>
		</label>
		<?php if ( isset( $args['description'] ) ) : ?>
			<p class="description"><?php echo esc_html( $args['description'] ); ?></p>
		<?php endif; ?>
		<?php
	}

	public function render_text_field( $args ) {
		$options = get_option( $args['option'], array() );
		$value   = isset( $options[ $args['field'] ] ) ? $options[ $args['field'] ] : ( $args['default'] ?? '' );
		?>
		<input type="text" class="regular-text"
			name="<?php echo esc_attr( $args['option'] . '[' . $args['field'] . ']' ); ?>"
			value="<?php echo esc_attr( $value ); ?>"
			<?php echo isset( $args['placeholder'] ) ? 'placeholder="' . esc_attr( $args['placeholder'] ) . '"' : ''; ?>>
		<?php if ( isset( $args['description'] ) ) : ?>
			<p class="description"><?php echo esc_html( $args['description'] ); ?></p>
		<?php endif; ?>
		<?php
	}

	public function render_password_field( $args ) {
		$options = get_option( $args['option'], array() );
		$value   = isset( $options[ $args['field'] ] ) ? $options[ $args['field'] ] : '';
		?>
		<input type="password" class="regular-text"
			name="<?php echo esc_attr( $args['option'] . '[' . $args['field'] . ']' ); ?>"
			value="<?php echo esc_attr( $value ); ?>">
		<?php if ( isset( $args['description'] ) ) : ?>
			<p class="description"><?php echo esc_html( $args['description'] ); ?></p>
		<?php endif; ?>
		<?php
	}

	public function render_number_field( $args ) {
		$options = get_option( $args['option'], array() );
		$value   = isset( $options[ $args['field'] ] ) ? $options[ $args['field'] ] : ( $args['default'] ?? 0 );
		?>
		<input type="number" class="small-text"
			name="<?php echo esc_attr( $args['option'] . '[' . $args['field'] . ']' ); ?>"
			value="<?php echo esc_attr( $value ); ?>"
			<?php echo isset( $args['min'] ) ? 'min="' . esc_attr( $args['min'] ) . '"' : ''; ?>
			<?php echo isset( $args['max'] ) ? 'max="' . esc_attr( $args['max'] ) . '"' : ''; ?>>
		<?php if ( isset( $args['description'] ) ) : ?>
			<p class="description"><?php echo esc_html( $args['description'] ); ?></p>
		<?php endif; ?>
		<?php
	}

	public function render_select_field( $args ) {
		$options = get_option( $args['option'], array() );
		$value   = isset( $options[ $args['field'] ] ) ? $options[ $args['field'] ] : ( $args['default'] ?? '' );
		?>
		<select name="<?php echo esc_attr( $args['option'] . '[' . $args['field'] . ']' ); ?>">
			<?php foreach ( $args['options'] as $option_value => $option_label ) : ?>
				<option value="<?php echo esc_attr( $option_value ); ?>" <?php selected( $value, $option_value ); ?>>
					<?php echo esc_html( $option_label ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php if ( isset( $args['description'] ) ) : ?>
			<p class="description"><?php echo esc_html( $args['description'] ); ?></p>
		<?php endif; ?>
		<?php
	}

	public function render_textarea_field( $args ) {
		$options = get_option( $args['option'], array() );
		$value   = isset( $options[ $args['field'] ] ) ? $options[ $args['field'] ] : ( $args['default'] ?? '' );
		?>
		<textarea class="large-text" rows="3"
			name="<?php echo esc_attr( $args['option'] . '[' . $args['field'] . ']' ); ?>"><?php echo esc_textarea( $value ); ?></textarea>
		<?php if ( isset( $args['description'] ) ) : ?>
			<p class="description"><?php echo esc_html( $args['description'] ); ?></p>
		<?php endif; ?>
		<?php
	}

	/**
	 * Sanitize callbacks.
	 */
	public function sanitize_general_settings( $input ) {
		$sanitized = array();

		$sanitized['enabled']                 = ! empty( $input['enabled'] );
		$sanitized['reverification_interval'] = isset( $input['reverification_interval'] ) ? absint( $input['reverification_interval'] ) : 365;
		$sanitized['reverification_behavior'] = isset( $input['reverification_behavior'] ) ? sanitize_key( $input['reverification_behavior'] ) : 'silent';
		$sanitized['disable_encryption']      = ! empty( $input['disable_encryption'] );
		$sanitized['redirect_url']            = isset( $input['redirect_url'] ) ? esc_url_raw( $input['redirect_url'] ) : '';
		$sanitized['redirect_delay']          = isset( $input['redirect_delay'] ) ? max( 0, absint( $input['redirect_delay'] ) ) : 2000;
		$sanitized['page_title']              = isset( $input['page_title'] ) ? sanitize_text_field( $input['page_title'] ) : '';
		$sanitized['menu_order']              = isset( $input['menu_order'] ) ? min( 100, max( 0, absint( $input['menu_order'] ) ) ) : 10;

		return $sanitized;
	}

	public function sanitize_va_api_settings( $input ) {
		$sanitized = array();

		$sanitized['enabled'] = ! empty( $input['enabled'] );
		$sanitized['api_key'] = isset( $input['api_key'] ) ? sanitize_text_field( $input['api_key'] ) : '';
		$sanitized['api_url'] = isset( $input['api_url'] ) ? esc_url_raw( $input['api_url'] ) : '';
		$sanitized['sandbox'] = ! empty( $input['sandbox'] );

		return $sanitized;
	}

	public function sanitize_military_otp_settings( $input ) {
		$sanitized = array();

		$sanitized['enabled']                 = ! empty( $input['enabled'] );
		$sanitized['whitelist_patterns']      = isset( $input['whitelist_patterns'] ) ? sanitize_textarea_field( $input['whitelist_patterns'] ) : '*.mil';
		$sanitized['blacklist_patterns']      = isset( $input['blacklist_patterns'] ) ? sanitize_textarea_field( $input['blacklist_patterns'] ) : '';
		$sanitized['otp_expiry']              = isset( $input['otp_expiry'] ) ? absint( $input['otp_expiry'] ) : 15;
		$valid_options = array( 'both', 'first', 'none' );
		// Sanitize new setting
		$sanitized['name_match_type']         = isset( $input['name_match_type'] ) && in_array( $input['name_match_type'], $valid_options, true ) ? $input['name_match_type'] : 'none';
		// Maintain backward compatibility with old setting
		$sanitized['require_name_match']      = $sanitized['name_match_type'];
		$sanitized['lock_billing_first_name'] = ! empty( $input['lock_billing_first_name'] );
		$sanitized['lock_billing_last_name']  = ! empty( $input['lock_billing_last_name'] );

		return $sanitized;
	}

	public function sanitize_queue_settings( $input ) {
		$sanitized = array();

		$sanitized['retry_interval'] = isset( $input['retry_interval'] ) ? max( 1, absint( $input['retry_interval'] ) ) : 1;
		$sanitized['max_retries']    = isset( $input['max_retries'] ) ? min( 20, max( 1, absint( $input['max_retries'] ) ) ) : 5;

		return $sanitized;
	}

	public function sanitize_logs_settings( $input ) {
		$sanitized = array();

		$sanitized['retention_days'] = isset( $input['retention_days'] ) ? absint( $input['retention_days'] ) : 30;

		return $sanitized;
	}

	/**
	 * AJAX handlers.
	 */
	public function ajax_test_api() {
		check_ajax_referer( 'md_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( __( 'Unauthorized.', 'military-discounts' ) );
		}

		$va_api = new MD_VA_API( $this->logger );
		$result = $va_api->test_connection();

		if ( $result['success'] ) {
			wp_send_json_success( $result['message'] );
		} else {
			wp_send_json_error( $result['message'] );
		}
	}

	public function ajax_clear_logs() {
		check_ajax_referer( 'md_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( __( 'Unauthorized.', 'military-discounts' ) );
		}

		$this->logger->clear_logs();
		wp_send_json_success( __( 'Logs cleared successfully.', 'military-discounts' ) );
	}

	public function ajax_export_logs() {
		check_ajax_referer( 'md_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( __( 'Unauthorized.', 'military-discounts' ) );
		}

		$logs = $this->logger->export_logs();
		wp_send_json_success( array( 'logs' => $logs ) );
	}

	/**
	 * Cancel a pending verification.
	 */
	public function ajax_cancel_pending_verification() {
		check_ajax_referer( 'md_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( __( 'Unauthorized.', 'military-discounts' ) );
		}

		$user_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;

		if ( empty( $user_id ) ) {
			wp_send_json_error( __( 'Invalid user ID.', 'military-discounts' ) );
		}

		$encryption = new MD_Encryption();
		$queue = new MD_Queue( $encryption );

		if ( $queue->cancel_pending_verification( $user_id ) ) {
			wp_send_json_success( __( 'Verification cancelled successfully.', 'military-discounts' ) );
		} else {
			wp_send_json_error( __( 'Failed to cancel verification.', 'military-discounts' ) );
		}
	}

	/**
	 * Cancel all pending verifications.
	 */
	public function ajax_cancel_all_pending_verifications() {
		check_ajax_referer( 'md_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( __( 'Unauthorized.', 'military-discounts' ) );
		}

		$encryption = new MD_Encryption();
		$queue = new MD_Queue( $encryption );

		$cancelled_count = $queue->cancel_all_pending_verifications();

		wp_send_json_success( array(
			/* translators: %d: Number of cancelled verifications */
			'message' => sprintf( __( 'Cancelled %d pending verifications.', 'military-discounts' ), $cancelled_count ),
			'count' => $cancelled_count
		) );
	}
}
