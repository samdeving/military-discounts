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
				'description' => __( 'Comma-separated patterns to block. Example: *ctr.mil,*contractor.mil', 'military-discounts' ),
				'default'     => '*ctr.mil,*contractor.mil',
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
			'require_name_match',
			__( 'Require Name Match', 'military-discounts' ),
			array( $this, 'render_checkbox_field' ),
			'md-settings-military-otp',
			'md_military_otp_section',
			array(
				'option'      => 'md_settings_military_otp',
				'field'       => 'require_name_match',
				'label'       => __( 'Require email username to match customer first/last name', 'military-discounts' ),
				'description' => __( 'The email local part (e.g., john.smith@) must contain the customer\'s first and last name. Allows for middle names and numbers (e.g., john.jones.smith2@).', 'military-discounts' ),
				'default'     => false,
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
		$sanitized['require_name_match']      = ! empty( $input['require_name_match'] );
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
}
