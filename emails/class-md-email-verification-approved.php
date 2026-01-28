<?php
/**
 * Verification Approved Email class for Military Discounts plugin.
 *
 * @package Military_Discounts
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class MD_Email_Verification_Approved
 *
 * Email sent when verification is approved.
 */
class MD_Email_Verification_Approved extends WC_Email {

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
	 * Constructor.
	 */
	public function __construct() {
		$this->id             = 'md_verification_approved';
		$this->customer_email = true;
		$this->title          = __( 'Military Verification Approved', 'military-discounts' );
		$this->description    = __( 'This email is sent when a military or veteran verification is approved.', 'military-discounts' );

		$this->template_html  = 'emails/verification-approved.php';
		$this->template_plain = 'emails/plain/verification-approved.php';
		$this->template_base  = MD_PLUGIN_DIR . 'templates/';

		$this->placeholders = array(
			'{site_title}'         => $this->get_blogname(),
			'{verification_type}'  => '',
			'{customer_name}'      => '',
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
		return __( 'Your {verification_type} status has been verified!', 'military-discounts' );
	}

	/**
	 * Get email heading.
	 *
	 * @return string
	 */
	public function get_default_heading() {
		return __( 'Verification Approved', 'military-discounts' );
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

		$this->recipient = $this->user->user_email;

		$this->placeholders['{verification_type}'] = 'veteran' === $type 
			? __( 'veteran', 'military-discounts' ) 
			: __( 'military', 'military-discounts' );
		$this->placeholders['{customer_name}']     = esc_html($this->user->display_name);

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
				'verification_type'  => $this->verification_type,
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
				'verification_type'  => $this->verification_type,
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
		return __( 'You can now enjoy exclusive military discounts on our store!', 'military-discounts' );
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
