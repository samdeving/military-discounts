<?php
/**
 * OTP Email class for Military Discounts plugin.
 *
 * @package Military_Discounts
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class MD_Email_OTP
 *
 * Email sent with OTP verification code.
 */
class MD_Email_OTP extends WC_Email {

	/**
	 * OTP code.
	 *
	 * @var string
	 */
	public $otp_code;

	/**
	 * User being notified.
	 *
	 * @var WP_User
	 */
	public $user;

	/**
	 * OTP expiry in minutes.
	 *
	 * @var int
	 */
	public $otp_expiry;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id             = 'md_otp';
		$this->customer_email = true;
		$this->title          = __( 'Military OTP Verification', 'military-discounts' );
		$this->description    = __( 'This email is sent when a customer requests military email verification with an OTP code.', 'military-discounts' );

		$this->template_html  = 'emails/otp.php';
		$this->template_plain = 'emails/plain/otp.php';
		$this->template_base  = MD_PLUGIN_DIR . 'templates/';

		$this->placeholders = array(
			'{site_title}'    => $this->get_blogname(),
			'{otp_code}'      => '',
			'{otp_expiry}'    => '',
			'{customer_name}' => '',
		);

		// Call parent constructor.
		parent::__construct();
	}

	/**
	 * Get email subject.
	 *
	 * @return string
	 */
	public function get_default_subject() {
		return __( '[{site_title}] Your Military Verification Code', 'military-discounts' );
	}

	/**
	 * Get email heading.
	 *
	 * @return string
	 */
	public function get_default_heading() {
		return __( 'Your Verification Code', 'military-discounts' );
	}

	/**
	 * Trigger the email.
	 *
	 * @param int    $user_id   User ID.
	 * @param string $otp_code  OTP code.
	 * @param string $recipient Email recipient (military email).
	 */
	public function trigger( $user_id, $otp_code, $recipient = '' ) {
		$this->setup_locale();

		$this->user     = get_user_by( 'id', $user_id );
		$this->otp_code = $otp_code;

		$settings         = md_get_military_otp_settings();
		$this->otp_expiry = isset( $settings['otp_expiry'] ) ? absint( $settings['otp_expiry'] ) : 15;

		if ( ! $this->user ) {
			return;
		}

		$this->recipient = ! empty( $recipient ) ? $recipient : $this->user->user_email;

		$this->placeholders['{otp_code}']      = $this->otp_code;
		$this->placeholders['{otp_expiry}']    = $this->otp_expiry;
		$this->placeholders['{customer_name}'] = esc_html($this->user->display_name);

		if ( $this->is_enabled() && $this->get_recipient() ) {
			$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
		}

		$this->restore_locale();
	}

	/**
	 * Get content HTML.
	 *
	 * @return string
	 */
	public function get_content_html() {
		return wc_get_template_html(
			$this->template_html,
			array(
				'email_heading'      => $this->get_heading(),
				'user'               => $this->user,
				'otp_code'           => $this->otp_code,
				'otp_expiry'         => $this->otp_expiry,
				'blogname'           => $this->get_blogname(),
				'additional_content' => $this->get_additional_content(),
				'sent_to_admin'      => false,
				'plain_text'         => false,
				'email'              => $this,
			),
			'',
			$this->template_base
		);
	}

	/**
	 * Get content plain.
	 *
	 * @return string
	 */
	public function get_content_plain() {
		return wc_get_template_html(
			$this->template_plain,
			array(
				'email_heading'      => $this->get_heading(),
				'user'               => $this->user,
				'otp_code'           => $this->otp_code,
				'otp_expiry'         => $this->otp_expiry,
				'blogname'           => $this->get_blogname(),
				'additional_content' => $this->get_additional_content(),
				'sent_to_admin'      => false,
				'plain_text'         => true,
				'email'              => $this,
			),
			'',
			$this->template_base
		);
	}

	/**
	 * Default content to show below main email content.
	 *
	 * @return string
	 */
	public function get_default_additional_content() {
		return __( 'If you did not request this code, please ignore this email.', 'military-discounts' );
	}

	/**
	 * Initialize settings form fields.
	 */
	public function init_form_fields() {
		parent::init_form_fields();

		$this->form_fields['subject'] = array(
			'title'       => __( 'Subject', 'military-discounts' ),
			'type'        => 'text',
			'desc_tip'    => true,
			'description' => sprintf(
				/* translators: %s: available placeholders */
				__( 'Available placeholders: %s', 'military-discounts' ),
				'{site_title}, {otp_code}, {otp_expiry}, {customer_name}'
			),
			'placeholder' => $this->get_default_subject(),
			'default'     => '',
		);

		$this->form_fields['heading'] = array(
			'title'       => __( 'Email heading', 'military-discounts' ),
			'type'        => 'text',
			'desc_tip'    => true,
			'description' => sprintf(
				/* translators: %s: available placeholders */
				__( 'Available placeholders: %s', 'military-discounts' ),
				'{site_title}, {otp_code}, {otp_expiry}, {customer_name}'
			),
			'placeholder' => $this->get_default_heading(),
			'default'     => '',
		);
	}
}
