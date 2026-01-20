<?php
/**
 * Logger class for Military Discounts plugin.
 *
 * Handles VA API request/response logging with PII protection.
 *
 * @package Military_Discounts
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class MD_Logger
 *
 * Logs VA API requests and responses for debugging.
 */
class MD_Logger {

	/**
	 * Option name for storing logs.
	 *
	 * @var string
	 */
	private const OPTION_NAME = 'md_va_api_logs';

	/**
	 * Maximum number of logs to retain.
	 *
	 * @var int
	 */
	private const MAX_LOGS = 500;

	/**
	 * Fields to redact for PII protection.
	 *
	 * @var array
	 */
	private const REDACT_FIELDS = array(
		'birthDate',
		'ssn',
		'streetAddressLine1',
		'streetAddressLine2',
	);

	/**
	 * Log a request and response.
	 *
	 * @param int          $user_id       User ID.
	 * @param array        $request_data  Request data.
	 * @param array|object $response_data Response data or WP_Error.
	 */
	public function log_request( $user_id, array $request_data, $response_data ) {
		$logs = $this->get_logs();

		// Sanitize request data (redact PII).
		$sanitized_request = $this->redact_pii( $request_data );

		// Process response.
		if ( is_wp_error( $response_data ) ) {
			$sanitized_response = array(
				'error'   => true,
				'message' => $response_data->get_error_message(),
			);
			$status = 'error';
		} else {
			$response_code = wp_remote_retrieve_response_code( $response_data );
			$response_body = wp_remote_retrieve_body( $response_data );
			$parsed_body   = json_decode( $response_body, true );

			$sanitized_response = array(
				'code' => $response_code,
				'body' => $parsed_body,
			);
			$status = 200 === $response_code ? 'success' : 'failed';
		}

		// Create log entry.
		$log_entry = array(
			'timestamp' => current_time( 'mysql' ),
			'user_id'   => $user_id,
			'request'   => $sanitized_request,
			'response'  => $sanitized_response,
			'status'    => $status,
		);

		// Add to beginning of logs array.
		array_unshift( $logs, $log_entry );

		// Trim to max logs.
		$logs = array_slice( $logs, 0, self::MAX_LOGS );

		// Save logs.
		update_option( self::OPTION_NAME, $logs );
	}

	/**
	 * Get all logs.
	 *
	 * @param int $limit Maximum number of logs to return.
	 * @return array Logs array.
	 */
	public function get_logs( $limit = 100 ) {
		$logs = get_option( self::OPTION_NAME, array() );

		if ( ! is_array( $logs ) ) {
			return array();
		}

		if ( $limit > 0 ) {
			return array_slice( $logs, 0, $limit );
		}

		return $logs;
	}

	/**
	 * Clear all logs.
	 *
	 * @return bool True on success.
	 */
	public function clear_logs() {
		return delete_option( self::OPTION_NAME );
	}

	/**
	 * Get log count.
	 *
	 * @return int Number of logs.
	 */
	public function get_log_count() {
		$logs = get_option( self::OPTION_NAME, array() );
		return is_array( $logs ) ? count( $logs ) : 0;
	}

	/**
	 * Redact PII from data.
	 *
	 * @param array $data Data to redact.
	 * @return array Redacted data.
	 */
	private function redact_pii( array $data ) {
		$redacted = array();

		foreach ( $data as $key => $value ) {
			if ( in_array( $key, self::REDACT_FIELDS, true ) ) {
				$redacted[ $key ] = '[REDACTED]';
			} elseif ( is_array( $value ) ) {
				$redacted[ $key ] = $this->redact_pii( $value );
			} else {
				$redacted[ $key ] = $value;
			}
		}

		return $redacted;
	}

	/**
	 * Get logs filtered by status.
	 *
	 * @param string $status Status to filter by.
	 * @param int    $limit  Maximum number of logs to return.
	 * @return array Filtered logs.
	 */
	public function get_logs_by_status( $status, $limit = 100 ) {
		$logs     = $this->get_logs( 0 );
		$filtered = array();

		foreach ( $logs as $log ) {
			if ( isset( $log['status'] ) && $log['status'] === $status ) {
				$filtered[] = $log;

				if ( count( $filtered ) >= $limit ) {
					break;
				}
			}
		}

		return $filtered;
	}

	/**
	 * Get logs for a specific user.
	 *
	 * @param int $user_id User ID.
	 * @param int $limit   Maximum number of logs to return.
	 * @return array Filtered logs.
	 */
	public function get_logs_for_user( $user_id, $limit = 100 ) {
		$logs     = $this->get_logs( 0 );
		$filtered = array();

		foreach ( $logs as $log ) {
			if ( isset( $log['user_id'] ) && (int) $log['user_id'] === (int) $user_id ) {
				$filtered[] = $log;

				if ( count( $filtered ) >= $limit ) {
					break;
				}
			}
		}

		return $filtered;
	}

	/**
	 * Delete logs older than retention period.
	 *
	 * @return int Number of logs deleted.
	 */
	public function cleanup_old_logs() {
		$settings       = get_option( 'md_settings_logs', array() );
		$retention_days = isset( $settings['retention_days'] ) ? absint( $settings['retention_days'] ) : 30;

		if ( 0 === $retention_days ) {
			return 0;
		}

		$logs       = $this->get_logs( 0 );
		$cutoff     = strtotime( '-' . $retention_days . ' days' );
		$new_logs   = array();
		$deleted    = 0;

		foreach ( $logs as $log ) {
			$log_time = isset( $log['timestamp'] ) ? strtotime( $log['timestamp'] ) : 0;

			if ( $log_time >= $cutoff ) {
				$new_logs[] = $log;
			} else {
				$deleted++;
			}
		}

		if ( $deleted > 0 ) {
			update_option( self::OPTION_NAME, $new_logs );
		}

		return $deleted;
	}

	/**
	 * Export logs as JSON.
	 *
	 * @return string JSON encoded logs.
	 */
	public function export_logs() {
		return wp_json_encode( $this->get_logs( 0 ), JSON_PRETTY_PRINT );
	}
}
