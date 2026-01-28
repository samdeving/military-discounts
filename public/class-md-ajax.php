<?php
/**
 * AJAX handler class for Military Discounts plugin.
 *
 * Handles all frontend AJAX requests.
 *
 * @package Military_Discounts
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class MD_Ajax
 *
 * Handles AJAX requests for verification.
 */
class MD_Ajax {

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
	 * Queue instance.
	 *
	 * @var MD_Queue
	 */
	private $queue;

	/**
	 * Encryption instance.
	 *
	 * @var MD_Encryption
	 */
	private $encryption;

	/**
	 * Constructor.
	 *
	 * @param MD_VA_API       $va_api       VA API instance.
	 * @param MD_Military_OTP $military_otp Military OTP instance.
	 * @param MD_Queue        $queue        Queue instance.
	 * @param MD_Encryption   $encryption   Encryption instance.
	 */
	public function __construct( MD_VA_API $va_api, MD_Military_OTP $military_otp, MD_Queue $queue, MD_Encryption $encryption ) {
		$this->va_api       = $va_api;
		$this->military_otp = $military_otp;
		$this->queue        = $queue;
		$this->encryption   = $encryption;
	}

	/**
	 * Initialize AJAX hooks.
	 */
	public function init() {
		add_action( 'wp_ajax_md_get_form_fields', array( $this, 'ajax_get_form_fields' ) );
		add_action( 'wp_ajax_md_submit_veteran_verification', array( $this, 'ajax_submit_veteran_verification' ) );
		add_action( 'wp_ajax_md_send_military_otp', array( $this, 'ajax_send_military_otp' ) );
		add_action( 'wp_ajax_md_verify_military_otp', array( $this, 'ajax_verify_military_otp' ) );
		add_action( 'wp_ajax_md_check_billing_name', array( $this, 'ajax_check_billing_name' ) );
		add_action( 'wp_ajax_md_validate_military_email', array( $this, 'ajax_validate_military_email' ) );
		add_action( 'wp_ajax_md_check_email_name_match', array( $this, 'ajax_check_email_name_match' ) );
	}

	/**
	 * Validate military email.
	 */
	public function ajax_validate_military_email() {
		check_ajax_referer( 'md_public_nonce', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( __( 'Please log in to continue.', 'military-discounts' ) );
		}

		$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';

		if ( empty( $email ) || ! is_email( $email ) ) {
			wp_send_json_error( __( 'Please enter a valid email address.', 'military-discounts' ) );
		}

		// Check if it's a valid military email.
		if ( ! $this->military_otp->is_military_email( $email ) ) {
			wp_send_json_error( __( 'This email address is not recognized as a valid military email. Please use your official .mil email address.', 'military-discounts' ) );
		}

		wp_send_json_success();
	}

	/**
	 * Check if user has billing first and last name.
	 */
	public function ajax_check_billing_name() {
		check_ajax_referer( 'md_public_nonce', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( __( 'Please log in to continue.', 'military-discounts' ) );
		}

		$user_id = get_current_user_id();
		$customer   = new WC_Customer( $user_id );
		$first_name = $customer->get_billing_first_name();
		$last_name  = $customer->get_billing_last_name();

		if ( empty( $first_name ) || empty( $last_name ) ) {
			wp_send_json_error( __( 'Please update your billing first and last name in your account before verifying.', 'military-discounts' ) );
		}

		wp_send_json_success();
	}

	/**
	 * Check if email username matches billing name.
	 */
	public function ajax_check_email_name_match() {
		check_ajax_referer( 'md_public_nonce', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( __( 'Please log in to continue.', 'military-discounts' ) );
		}

		$user_id = get_current_user_id();
		$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';

		if ( empty( $email ) || ! is_email( $email ) ) {
			wp_send_json_error( __( 'Please enter a valid email address.', 'military-discounts' ) );
		}

		// Check name matching if required.
		if ( $this->military_otp->is_name_match_required() ) {
			$customer   = new WC_Customer( $user_id );
			$first_name = $customer->get_billing_first_name();
			$last_name  = $customer->get_billing_last_name();

			if ( empty( $first_name ) || empty( $last_name ) ) {
				wp_send_json_error( __( 'Please update your billing first and last name in your account before verifying.', 'military-discounts' ) );
			}

			if ( ! $this->military_otp->email_matches_name( $email, $first_name, $last_name ) ) {
				wp_send_json_error(
					sprintf(
						/* translators: 1: first name, 2: last name */
						__( 'The email username does not match your billing name (%1$s %2$s). The email local part must contain your first and last name (e.g., firstname.lastname@mail.mil).', 'military-discounts' ),
						esc_html( $first_name ),
						esc_html( $last_name )
					)
				);
			}
		}

		wp_send_json_success();
	}

	/**
	 * Get form fields for a verification type.
	 */
	public function ajax_get_form_fields() {
		check_ajax_referer( 'md_public_nonce', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( __( 'Please log in to continue.', 'military-discounts' ) );
		}

		$type = isset( $_POST['type'] ) ? sanitize_key( $_POST['type'] ) : '';

		if ( ! in_array( $type, array( 'veteran', 'military' ), true ) ) {
			wp_send_json_error( __( 'Invalid verification type.', 'military-discounts' ) );
		}

		$form_builder = new MD_Form_Builder();
		$fields       = $form_builder->get_form_fields( $type );

		ob_start();
		$this->render_form_fields( $fields, $type );
		$html = ob_get_clean();

		wp_send_json_success( array(
			'html'   => $html,
			'type'   => $type,
			'fields' => $fields,
		) );
	}

	/**
	 * Render form fields HTML.
	 *
	 * @param array  $fields Form fields.
	 * @param string $type   Verification type.
	 */
	private function render_form_fields( array $fields, $type ) {
		foreach ( $fields as $field ) {
			$is_required = ! empty( $field['required'] );
			$required_mark = $is_required ? '<span class="required">*</span>' : '';
			?>
			<div class="md-form-field">
				<label for="md-<?php echo esc_attr( $field['id'] ); ?>">
					<?php echo esc_html( $field['label'] ); ?><?php echo wp_kses_post( $required_mark ); ?>
				</label>
				<?php
				switch ( $field['type'] ) {
					case 'date':
						?>
						<input type="date"
							id="md-<?php echo esc_attr( $field['id'] ); ?>"
							name="<?php echo esc_attr( $field['id'] ); ?>"
							class="input-text"
							<?php echo $is_required ? 'required' : ''; ?>
							max="<?php echo esc_attr( gmdate( 'Y-m-d' ) ); ?>">
						<?php
						break;

					case 'state':
						$this->render_state_select( $field );
						break;

					case 'country':
						$this->render_country_select( $field );
						break;

					case 'select':
						?>
						<select id="md-<?php echo esc_attr( $field['id'] ); ?>"
							name="<?php echo esc_attr( $field['id'] ); ?>"
							class="input-select"
							<?php echo $is_required ? 'required' : ''; ?>>
							<option value=""><?php esc_html_e( 'Select...', 'military-discounts' ); ?></option>
							<?php if ( ! empty( $field['options'] ) ) : ?>
								<?php foreach ( $field['options'] as $value => $label ) : ?>
									<option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							<?php endif; ?>
						</select>
						<?php
						break;

					case 'email':
						?>
						<input type="email"
							id="md-<?php echo esc_attr( $field['id'] ); ?>"
							name="<?php echo esc_attr( $field['id'] ); ?>"
							class="input-text"
							placeholder="<?php echo esc_attr( isset( $field['placeholder'] ) ? $field['placeholder'] : '' ); ?>"
							<?php echo $is_required ? 'required' : ''; ?>>
						<?php
						break;

					default:
						?>
						<input type="text"
							id="md-<?php echo esc_attr( $field['id'] ); ?>"
							name="<?php echo esc_attr( $field['id'] ); ?>"
							class="input-text"
							placeholder="<?php echo esc_attr( isset( $field['placeholder'] ) ? $field['placeholder'] : '' ); ?>"
							<?php echo $is_required ? 'required' : ''; ?>>
						<?php
						break;
				}
				?>
			</div>
			<?php
		}
	}

	/**
	 * Render state select field.
	 *
	 * @param array $field Field data.
	 */
	private function render_state_select( array $field ) {
		$states = WC()->countries->get_states( 'US' );
		$is_required = ! empty( $field['required'] );
		?>
		<select id="md-<?php echo esc_attr( $field['id'] ); ?>"
			name="<?php echo esc_attr( $field['id'] ); ?>"
			class="input-select"
			<?php echo $is_required ? 'required' : ''; ?>>
			<option value=""><?php esc_html_e( 'Select State...', 'military-discounts' ); ?></option>
			<?php foreach ( $states as $code => $name ) : ?>
				<option value="<?php echo esc_attr( $code ); ?>"><?php echo esc_html( $name ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Render country select field.
	 *
	 * @param array $field Field data.
	 */
	private function render_country_select( array $field ) {
		$countries = WC()->countries->get_countries();
		$is_required = ! empty( $field['required'] );
		?>
		<select id="md-<?php echo esc_attr( $field['id'] ); ?>"
			name="<?php echo esc_attr( $field['id'] ); ?>"
			class="input-select"
			<?php echo $is_required ? 'required' : ''; ?>>
			<option value=""><?php esc_html_e( 'Select Country...', 'military-discounts' ); ?></option>
			<option value="USA" selected><?php esc_html_e( 'United States', 'military-discounts' ); ?></option>
			<?php foreach ( $countries as $code => $name ) : ?>
				<?php if ( 'US' !== $code ) : ?>
					<option value="<?php echo esc_attr( $code ); ?>"><?php echo esc_html( $name ); ?></option>
				<?php endif; ?>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Submit veteran verification.
	 */
	public function ajax_submit_veteran_verification() {
		check_ajax_referer( 'md_public_nonce', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( __( 'Please log in to continue.', 'military-discounts' ) );
		}

		$user_id = get_current_user_id();

		// Check if already verified.
		if ( md_is_verified_veteran( $user_id ) ) {
			wp_send_json_error( __( 'You are already verified as a veteran.', 'military-discounts' ) );
		}

		// Check for pending verification.
		if ( md_has_pending_verification( $user_id ) ) {
			wp_send_json_error( __( 'You already have a pending verification request.', 'military-discounts' ) );
		}

		// Get and validate form data.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized in loop below.
		$form_data = isset( $_POST['formData'] ) ? wp_unslash( $_POST['formData'] ) : array();
		$form_data = is_array( $form_data ) ? $form_data : array();
		
		if ( empty( $form_data['firstName'] ) || empty( $form_data['lastName'] ) ) {
			wp_send_json_error( __( 'First name and last name are required.', 'military-discounts' ) );
		}

		// Sanitize form data.
		$verification_data = array(
			'user_id' => $user_id,
		);

		// Allowed camelCase keys for VA API - preserve casing for these.
		$allowed_keys = array(
			'firstName', 'lastName', 'middleName', 'birthDate', 'gender',
			'streetAddressLine1', 'streetAddressLine2', 'city', 'state', 'zipCode', 'country',
		);

		foreach ( $form_data as $key => $value ) {
			// Preserve camelCase for allowed API keys, sanitize others.
			if ( in_array( $key, $allowed_keys, true ) ) {
				$verification_data[ $key ] = sanitize_text_field( $value );
			} else {
				$verification_data[ sanitize_key( $key ) ] = sanitize_text_field( $value );
			}
		}

		// Try immediate verification.
		try {
			$result = $this->va_api->verify( $verification_data );

			if ( 'confirmed' === $result['status'] ) {
				// Verification successful.
				md_set_verified( $user_id, 'veteran', true );

				// Get redirect settings from settings
				$settings = get_option( 'md_settings_general', array() );
				$redirect_url = isset( $settings['redirect_url'] ) ? $settings['redirect_url'] : '';
				$redirect_delay = isset( $settings['redirect_delay'] ) ? max( 0, absint( $settings['redirect_delay'] ) ) : 2000;

				wp_send_json_success( array(
					'status'        => 'approved',
					'message'       => __( 'Your veteran status has been verified! You can now use veteran discounts.', 'military-discounts' ),
					'redirect_url'  => $redirect_url,
					'redirect_delay' => $redirect_delay,
				) );
			} elseif ( 'not_confirmed' === $result['status'] ) {
				$reason = isset( $result['reason'] ) ? $result['reason'] : '';

				if ( 'ERROR' === $reason ) {
					// Temporary error, queue for retry.
					$this->queue->add_to_queue( $user_id, 'veteran', $verification_data );

					wp_send_json_success( array(
						'status'  => 'queued',
						'message' => __( 'Your verification request has been submitted and is being processed. You will receive an email notification once complete.', 'military-discounts' ),
					) );
				} else {
					// Permanent denial.
					wp_send_json_error( array(
						'status'  => 'denied',
						'message' => MD_VA_API::get_denial_reason( $reason ),
					) );
				}
			}
		} catch ( Exception $e ) {
			// API error, queue for retry.
			$this->queue->add_to_queue( $user_id, 'veteran', $verification_data );

			wp_send_json_success( array(
				'status'  => 'queued',
				'message' => __( 'Your verification request has been submitted and is being processed. You will receive an email notification once complete.', 'military-discounts' ),
			) );
		}
	}

	/**
	 * Send military OTP.
	 */
	public function ajax_send_military_otp() {
		check_ajax_referer( 'md_public_nonce', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( __( 'Please log in to continue.', 'military-discounts' ) );
		}

		$user_id = get_current_user_id();
		$email   = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';

		if ( empty( $email ) || ! is_email( $email ) ) {
			wp_send_json_error( __( 'Please enter a valid email address.', 'military-discounts' ) );
		}

		// Check if it's a valid military email.
		if ( ! $this->military_otp->is_military_email( $email ) ) {
			wp_send_json_error( __( 'This email address is not recognized as a valid military email. Please use your official .mil email address.', 'military-discounts' ) );
		}

		// Check name matching if required.
		if ( $this->military_otp->is_name_match_required() ) {
			$customer   = new WC_Customer( $user_id );
			$first_name = $customer->get_billing_first_name();
			$last_name  = $customer->get_billing_last_name();

			if ( empty( $first_name ) || empty( $last_name ) ) {
				wp_send_json_error( __( 'Please update your billing first and last name in your account before verifying.', 'military-discounts' ) );
			}

			if ( ! $this->military_otp->email_matches_name( $email, $first_name, $last_name ) ) {
				wp_send_json_error(
					sprintf(
						/* translators: 1: first name, 2: last name */
						__( 'The email username does not match your billing name (%1$s %2$s). The email local part must contain your first and last name (e.g., firstname.lastname@mail.mil).', 'military-discounts' ),
						esc_html( $first_name ),
						esc_html( $last_name )
					)
				);
			}
		}

		// Check cooldown.
		if ( ! $this->military_otp->can_request_otp( $user_id ) ) {
			$remaining = $this->military_otp->get_cooldown_remaining( $user_id );
			wp_send_json_error(
				sprintf(
					/* translators: %d: seconds remaining */
					__( 'Please wait %d seconds before requesting a new code.', 'military-discounts' ),
					$remaining
				)
			);
		}

		// Generate and send OTP.
		$otp = $this->military_otp->generate_otp( $user_id );
		$sent = $this->military_otp->send_otp( $email, $otp, $user_id );

		if ( $sent ) {
			// Store email for verification.
			set_transient( 'md_otp_email_' . $user_id, $email, 15 * MINUTE_IN_SECONDS );

			wp_send_json_success( array(
				'message'     => __( 'Verification code sent! Check your email.', 'military-discounts' ),
				'expiry_time' => $this->military_otp->get_otp_expiry_time( $user_id ),
			) );
		} else {
			wp_send_json_error( __( 'Failed to send verification code. Please try again.', 'military-discounts' ) );
		}
	}

	/**
	 * Verify military OTP.
	 */
	public function ajax_verify_military_otp() {
		check_ajax_referer( 'md_public_nonce', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( __( 'Please log in to continue.', 'military-discounts' ) );
		}

		$user_id = get_current_user_id();
		$otp     = isset( $_POST['otp'] ) ? sanitize_text_field( wp_unslash( $_POST['otp'] ) ) : '';

		if ( empty( $otp ) ) {
			wp_send_json_error( __( 'Please enter the verification code.', 'military-discounts' ) );
		}

		// Validate OTP.
		if ( $this->military_otp->validate_otp( $user_id, $otp ) ) {
			// Mark as verified.
			md_set_verified( $user_id, 'military', true );

			// Clean up transients.
			delete_transient( 'md_otp_email_' . $user_id );

			// Get redirect settings from settings
			$settings = get_option( 'md_settings_general', array() );
			$redirect_url = isset( $settings['redirect_url'] ) ? $settings['redirect_url'] : '';
			$redirect_delay = isset( $settings['redirect_delay'] ) ? max( 0, absint( $settings['redirect_delay'] ) ) : 2000;

			wp_send_json_success( array(
				'status'        => 'approved',
				'message'       => __( 'Your active military status has been verified! You can now use military discounts.', 'military-discounts' ),
				'redirect_url'  => $redirect_url,
				'redirect_delay' => $redirect_delay,
			) );
		} else {
			wp_send_json_error( __( 'Invalid or expired verification code. Please try again.', 'military-discounts' ) );
		}
	}
}
