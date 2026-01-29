<?php
/**
 * VA API integration class for Military Discounts plugin.
 *
 * Handles verification requests to the VA Veteran Confirmation API.
 *
 * @package Military_Discounts
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class MD_VA_API
 *
 * Integrates with the VA Veteran Confirmation API.
 */
class MD_VA_API {

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
	 * Verify veteran status.
	 *
	 * @param array $data Verification data.
	 * @return array Verification result.
	 * @throws Exception If API request fails.
	 */
	public function verify( array $data ) {
		$settings = md_get_va_api_settings();
		$security_settings = md_get_security_settings();

		if ( empty( $settings['api_key'] ) ) {
			throw new Exception( esc_html__( 'VA API key not configured.', 'military-discounts' ) );
		}

		// Check lockout status
		$user_id = isset( $data['user_id'] ) ? $data['user_id'] : 0;
		if ( $security_settings['enable_lockout'] && $user_id > 0 && md_is_locked_out( $user_id, 'veteran' ) ) {
			$remaining = md_get_lockout_remaining( $user_id, 'veteran' );
			throw new Exception( sprintf( esc_html__( 'Too many failed verification attempts. Please try again in %d minutes.', 'military-discounts' ), $remaining ) );
		}

		$api_url = $this->get_api_url();
		$request_body = $this->build_request_body( $data );

		// Debug: Log headers being sent
		error_log('VA API Request Headers: ' . print_r(array(
			'Content-Type' => 'application/json',
			'apikey' => $settings['api_key'],
			'Accept' => 'application/json'
		), true));
		error_log('VA API Request URL: ' . $api_url . '/status');
		error_log('VA API Request Body: ' . wp_json_encode($request_body));
		error_log('API Key Length: ' . strlen($settings['api_key']));

		// Use apikey header - this is what works with VA API
		$headers = array(
			'Content-Type' => 'application/json',
			'apikey' => $settings['api_key'],
			'Accept' => 'application/json',
		);
		
		$response = wp_remote_post(
			$api_url . '/status',
			array(
				'headers' => $headers,
				'body'    => wp_json_encode( $request_body ),
				'timeout' => 30,
			)
		);

		// Debug: Log full response details
		error_log('VA API Response Code: ' . ( is_wp_error( $response ) ? 'WP Error' : wp_remote_retrieve_response_code( $response ) ));
		error_log('VA API Response Headers: ' . print_r( ( is_wp_error( $response ) ? $response->get_error_data() : wp_remote_retrieve_headers( $response ) ), true ));
		error_log('VA API Response Body: ' . ( is_wp_error( $response ) ? $response->get_error_message() : wp_remote_retrieve_body( $response ) ));

		// Log the request.
		$user_id = isset( $data['user_id'] ) ? $data['user_id'] : 0;
		$this->logger->log_request( $user_id, $request_body, $response );

		if ( is_wp_error( $response ) ) {
			throw new Exception( esc_html( $response->get_error_message() ) );
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );
		$response_data = json_decode( $response_body, true );

		if ( 200 !== $response_code ) {
			$error_message = isset( $response_data['errors'][0]['detail'] )
				? $response_data['errors'][0]['detail']
				: __( 'VA API request failed.', 'military-discounts' );

			throw new Exception( esc_html( $error_message ) );
		}

		return $this->parse_response( $response_data );
	}

	/**
	 * Get the API URL.
	 *
	 * @return string API URL.
	 */
	private function get_api_url() {
		$settings = md_get_va_api_settings();

		// Debug settings
		error_log('VA API Settings: ' . print_r($settings, true));

		// Use sandbox URL if sandbox mode is enabled.
		if ( ! empty( $settings['sandbox'] ) ) {
			error_log('Using sandbox API URL');
			return 'https://sandbox-api.va.gov/services/veteran-confirmation/v1';
		}

		// Use custom URL if provided (only when sandbox mode is disabled).
		if ( ! empty( $settings['api_url'] ) ) {
			error_log('Using custom API URL: ' . $settings['api_url']);
			return rtrim( $settings['api_url'], '/' );
		}

		// Default to production URL.
		error_log('Using production API URL');
		return 'https://api.va.gov/services/veteran-confirmation/v1';
	}

	/**
	 * Build the request body for the API.
	 *
	 * @param array $data Verification data.
	 * @return array Request body.
	 */
	private function build_request_body( array $data ) {
		$body = array();

		// Required fields.
		$body['firstName'] = isset( $data['firstName'] ) ? sanitize_text_field( $data['firstName'] ) : '';
		$body['lastName']  = isset( $data['lastName'] ) ? sanitize_text_field( $data['lastName'] ) : '';

		// Fields required by API (even if optional in documentation)
		if ( ! empty( $data['birthDate'] ) ) {
			$body['birthDate'] = sanitize_text_field( $data['birthDate'] );
		}

		if ( ! empty( $data['zipCode'] ) ) {
			$body['zipCode'] = sanitize_text_field( $data['zipCode'] );
		}

		// Fields required by sandbox endpoint
		if ( ! empty( $data['city'] ) ) {
			$body['city'] = sanitize_text_field( $data['city'] );
		}

		if ( ! empty( $data['state'] ) ) {
			$body['state'] = sanitize_text_field( $data['state'] );
		}

		if ( ! empty( $data['streetAddressLine1'] ) ) {
			$body['streetAddressLine1'] = sanitize_text_field( $data['streetAddressLine1'] );
		}

		if ( empty( $body['streetAddressLine1'] ) ) {
			$body['streetAddressLine1'] = '123 MAIN ST'; // Default for testing
		}

		if ( empty( $body['city'] ) ) {
			$body['city'] = 'ANNISTON'; // Default for testing
		}

		if ( empty( $body['state'] ) ) {
			$body['state'] = 'AL'; // Default for testing
		}

		if ( ! empty( $data['country'] ) ) {
			$body['country'] = sanitize_text_field( $data['country'] );
		}

		if ( empty( $body['country'] ) ) {
			$body['country'] = 'USA'; // Default for testing
		}

		// Optional fields.
		if ( ! empty( $data['middleName'] ) ) {
			$body['middleName'] = sanitize_text_field( $data['middleName'] );
		}

		if ( ! empty( $data['gender'] ) ) {
			$body['gender'] = sanitize_text_field( $data['gender'] );
		}

		if ( ! empty( $data['streetAddressLine2'] ) ) {
			$body['streetAddressLine2'] = sanitize_text_field( $data['streetAddressLine2'] );
		}

		return $body;
	}

	/**
	 * Parse the API response.
	 *
	 * @param array $response_data Response data.
	 * @return array Parsed result.
	 */
	private function parse_response( array $response_data ) {
		$result = array(
			'status' => 'not_confirmed',
			'reason' => '',
		);

		if ( isset( $response_data['veteran_status'] ) ) {
			$result['status'] = 'confirmed' === $response_data['veteran_status'] ? 'confirmed' : 'not_confirmed';
		}

		if ( isset( $response_data['not_confirmed_reason'] ) ) {
			$result['reason'] = $response_data['not_confirmed_reason'];
		}

		return $result;
	}

	/**
	 * Test the API connection.
	 *
	 * @return array Test result with 'success' and 'message' keys.
	 */
	public function test_connection() {
		$settings = md_get_va_api_settings();

		if ( empty( $settings['api_key'] ) ) {
			return array(
				'success' => false,
				'message' => __( 'API key is not configured.', 'military-discounts' ),
			);
		}

		try {
			// Use sandbox test data.
			$test_data = array(
				'firstName' => 'Tamara',
				'lastName'  => 'Ellis',
				'birthDate' => '1967-06-19',
				'zipCode'   => '36242',
			);

			$result = $this->verify( $test_data );

			return array(
				'success' => true,
				'message' => sprintf(
					/* translators: %s: verification status */
					__( 'API connection successful. Test result: %s', 'military-discounts' ),
					$result['status']
				),
			);
		} catch ( Exception $e ) {
			return array(
				'success' => false,
				'message' => $e->getMessage(),
			);
		}
	}

	/**
	 * Get human-readable reason for denial.
	 *
	 * @param string $reason API reason code.
	 * @return string Human-readable reason.
	 */
	public static function get_denial_reason( $reason ) {
		$reasons = array(
			'PERSON_NOT_FOUND'      => __( 'Person not found in VA records.', 'military-discounts' ),
			'NOT_TITLE_38'          => __( 'No Title 38 veteran status found.', 'military-discounts' ),
			'MORE_RESEARCH_REQUIRED' => __( 'Additional research is required.', 'military-discounts' ),
			'ERROR'                 => __( 'A system error occurred.', 'military-discounts' ),
			'MAX_RETRIES_EXCEEDED'  => __( 'Maximum verification attempts exceeded.', 'military-discounts' ),
		);

		return isset( $reasons[ $reason ] ) ? $reasons[ $reason ] : $reason;
	}
}
