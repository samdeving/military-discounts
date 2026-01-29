<?php
/**
 * Main loader class for Military Discounts plugin.
 *
 * Orchestrates hook registration and initialization of all plugin components.
 *
 * @package Military_Discounts
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class MD_Loader
 *
 * Central hook registration and initialization orchestration.
 */
class MD_Loader {

	/**
	 * Encryption instance.
	 *
	 * @var MD_Encryption
	 */
	private $encryption;

	/**
	 * Queue instance.
	 *
	 * @var MD_Queue
	 */
	private $queue;

	/**
	 * VA API instance.
	 *
	 * @var MD_VA_API
	 */
	private $va_api;

	/**
	 * Military OTP instance.
	 *
	 * @var MD_Military_OTP
	 */
	private $military_otp;

	/**
	 * Logger instance.
	 *
	 * @var MD_Logger
	 */
	private $logger;

	/**
	 * Initialize all plugin components.
	 */
	public function init() {
		// Initialize core classes.
		$this->encryption   = new MD_Encryption();
		$this->logger       = new MD_Logger();
		$this->queue        = new MD_Queue( $this->encryption );
		$this->va_api       = new MD_VA_API( $this->logger );
		$this->military_otp = new MD_Military_OTP();

		// Initialize cron handler.
		$cron = new MD_Cron( $this->queue, $this->va_api, $this->encryption );
		$cron->init();

		// Initialize admin components.
		if ( is_admin() ) {
			$this->init_admin();
		}

		// Initialize public/frontend components.
		$this->init_public();

		// Initialize WooCommerce email classes.
		add_filter( 'woocommerce_email_classes', array( $this, 'register_email_classes' ) );
	}

	/**
	 * Initialize admin components.
	 */
	private function init_admin() {
		$admin = new MD_Admin();
		$admin->init();

		$settings = new MD_Settings( $this->logger );
		$settings->init();

		$form_builder = new MD_Form_Builder();
		$form_builder->init();

		$coupon_admin = new MD_Coupon_Admin();
		$coupon_admin->init();
	}

	/**
	 * Initialize public/frontend components.
	 */
	private function init_public() {
		$public = new MD_Public();
		$public->init();

		$my_account = new MD_My_Account();
		$my_account->init();

		$ajax = new MD_Ajax( $this->va_api, $this->military_otp, $this->queue, $this->encryption );
		$ajax->init();

		$coupon_validation = new MD_Coupon_Validation();
		$coupon_validation->init();
	}

	/**
	 * Register WooCommerce email classes.
	 *
	 * @param array $emails Existing email classes.
	 * @return array Modified email classes.
	 */
	public function register_email_classes( $emails ) {
		$emails['MD_Email_Verification_Approved'] = new MD_Email_Verification_Approved();
		$emails['MD_Email_Verification_Denied']   = new MD_Email_Verification_Denied();
		$emails['MD_Email_OTP']                   = new MD_Email_OTP();
		$emails['MD_Email_Reverification']        = new MD_Email_Reverification();
		$emails['MD_Email_Lockout']               = new MD_Email_Lockout();

		return $emails;
	}
}
