<?php
/**
 * Re-verification Email class for Military Discounts plugin.
 *
 * @package Military_Discounts
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class MD_Email_Reverification
 *
 * Email sent when verification has expired and re-verification is needed.
 */
class MD_Email_Reverification extends WC_Email {

	/**
	 * Verification type.
	 *
	 * @var string
	 */
	public $verification_type;

	/**
	 * User being notified.
	 *
	 * @var WP_User
	 */
	public $user;

	/**
	 * Reverification URL.
	 *
	 * @var string
	 */
	public $reverification_url;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id             = 'md_reverification';
		$this->customer_email = true;
		$this->title          = __( 'Military Verification Expired', 'military-discounts' );
		$this->description    = __( 'This email is sent when a military or veteran verification has expired and re-verification is required.', 'military-discounts' );

		$this->template_html  = 'emails/reverification.php';
		$this->template_plain = 'emails/plain/reverification.php';
		$this->template_base  = MD_PLUGIN_DIR . 'templates/';

		$this->placeholders = array(
			'{site_title}'          => $this->get_blogname(),
			'{verification_type}'   => '',
			'{customer_name}'       => '',
			'{reverification_url}'  => '',
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
		return __( 'Your {verification_type} verification has expired', 'military-discounts' );
	}

	/**
	 * Get email heading.
	 *
	 * @return string
	 */
	public function get_default_heading() {
		return __( 'Re-verification Required', 'military-discounts' );
	}

	/**
	 * Trigger the email.
	 *
	 * @param int    $user_id User ID.
	 * @param string $type    Verification type.
	 */
	public function trigger( $user_id, $type = 'veteran' ) {
		$this->setup_locale();

		$this->user              = get_user_by( 'id', $user_id );
		$this->verification_type = $type;

		if ( ! $this->user ) {
			return;
		}

		$this->recipient          = $this->user->user_email;
		$this->reverification_url = wc_get_account_endpoint_url( 'military-verification' );

		$this->placeholders['{verification_type}']  = 'veteran' === $type
			? __( 'veteran', 'military-discounts' )
			: __( 'military', 'military-discounts' );
		$this->placeholders['{customer_name}']      = esc_html($this->user->display_name);
		$this->placeholders['{reverification_url}'] = $this->reverification_url;

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
				'email_heading'       => $this->get_heading(),
				'user'                => $this->user,
				'verification_type'   => $this->verification_type,
				'reverification_url'  => $this->reverification_url,
				'blogname'            => $this->get_blogname(),
				'additional_content'  => $this->get_additional_content(),
				'sent_to_admin'       => false,
				'plain_text'          => false,
				'email'               => $this,
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
				'email_heading'       => $this->get_heading(),
				'user'                => $this->user,
				'verification_type'   => $this->verification_type,
				'reverification_url'  => $this->reverification_url,
				'blogname'            => $this->get_blogname(),
				'additional_content'  => $this->get_additional_content(),
				'sent_to_admin'       => false,
				'plain_text'          => true,
				'email'               => $this,
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
		return __( 'Please re-verify your status to continue enjoying military discounts.', 'military-discounts' );
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
				'{site_title}, {verification_type}, {customer_name}'
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
				'{site_title}, {verification_type}, {customer_name}'
			),
			'placeholder' => $this->get_default_heading(),
			'default'     => '',
		);
	}
}
