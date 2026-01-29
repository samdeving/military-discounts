<?php
/**
 * Lockout Email class for Military Discounts plugin.
 *
 * @package Military_Discounts
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class MD_Email_Lockout
 *
 * Email sent when a user is locked out due to too many failed verification attempts.
 */
class MD_Email_Lockout extends WC_Email {

	/**
	 * Verification type that caused the lockout.
	 *
	 * @var string
	 */
	public $verification_type;

	/**
	 * Lockout duration in minutes.
	 *
	 * @var int
	 */
	public $lockout_duration;

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
		$this->id             = 'md_lockout';
		$this->customer_email = true;
		$this->title          = __( 'Military Verification Lockout', 'military-discounts' );
		$this->description    = __( 'This email is sent when a customer is locked out due to too many failed verification attempts.', 'military-discounts' );

		$this->template_html  = 'emails/lockout.php';
		$this->template_plain = 'emails/plain/lockout.php';
		$this->template_base  = MD_PLUGIN_DIR . 'templates/';

		$this->placeholders = array(
			'{site_title}'         => $this->get_blogname(),
			'{verification_type}'  => '',
			'{lockout_duration}'   => '',
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
		return __( '[{site_title}] Verification Lockout', 'military-discounts' );
	}

	/**
	 * Get email heading.
	 *
	 * @return string
	 */
	public function get_default_heading() {
		return __( 'Verification Locked', 'military-discounts' );
	}

	/**
	 * Trigger the email.
	 *
	 * @param int    $user_id           User ID.
	 * @param string $verification_type Type of verification that caused lockout.
	 * @param int    $lockout_duration  Lockout duration in minutes.
	 */
	public function trigger( $user_id, $verification_type, $lockout_duration ) {
		$this->setup_locale();

		$this->user = get_user_by( 'id', $user_id );
		$this->verification_type = $verification_type;
		$this->lockout_duration = $lockout_duration;

		if ( ! $this->user ) {
			return;
		}

		$this->recipient = $this->user->user_email;

		$type_label = 'veteran' === $verification_type ? __( 'Veteran', 'military-discounts' ) : __( 'Active Military', 'military-discounts' );
		$this->placeholders['{verification_type}']  = $type_label;
		$this->placeholders['{lockout_duration}']   = $this->lockout_duration;
		$this->placeholders['{customer_name}']      = esc_html( $this->user->display_name );

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
				'lockout_duration'    => $this->lockout_duration,
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
				'lockout_duration'    => $this->lockout_duration,
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
		return __( 'If you believe this is an error, please contact support.', 'military-discounts' );
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
				'{site_title}, {verification_type}, {lockout_duration}, {customer_name}'
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
				'{site_title}, {verification_type}, {lockout_duration}, {customer_name}'
			),
			'placeholder' => $this->get_default_heading(),
			'default'     => '',
		);
	}
}
