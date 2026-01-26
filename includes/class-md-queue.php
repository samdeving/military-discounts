<?php
/**
 * Queue management class for Military Discounts plugin.
 *
 * Handles pending verification requests with encrypted storage.
 *
 * @package Military_Discounts
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class MD_Queue
 *
 * Manages the queue of pending verification requests.
 */
class MD_Queue {

	/**
	 * Encryption instance.
	 *
	 * @var MD_Encryption
	 */
	private $encryption;

	/**
	 * Constructor.
	 *
	 * @param MD_Encryption $encryption Encryption instance.
	 */
	public function __construct( MD_Encryption $encryption ) {
		$this->encryption = $encryption;
	}

	/**
	 * Add a verification request to the queue.
	 *
	 * @param int    $user_id User ID.
	 * @param string $type    Verification type ('veteran' or 'military').
	 * @param array  $data    Verification data.
	 * @return bool True on success.
	 */
	public function add_to_queue( $user_id, $type, array $data ) {
		$queue_item = array(
			'type'       => $type,
			'data'       => $data,
			'created_at' => time(),
			'retries'    => 0,
			'last_retry' => null,
			'status'     => 'pending',
		);

		// Encrypt the queue item.
		$encrypted = $this->encryption->encrypt( wp_json_encode( $queue_item ) );

		// Store in user meta.
		update_user_meta( $user_id, '_md_pending_verification', $encrypted );

		// Add to global index.
		$this->add_to_index( $user_id );

		return true;
	}

	/**
	 * Get a pending verification from the queue.
	 *
	 * @param int $user_id User ID.
	 * @return array|null Queue item or null if not found.
	 */
	public function get_from_queue( $user_id ) {
		$encrypted = get_user_meta( $user_id, '_md_pending_verification', true );

		if ( empty( $encrypted ) ) {
			return null;
		}

		try {
			$decrypted = $this->encryption->decrypt( $encrypted );
			return json_decode( $decrypted, true );
		} catch ( Exception $e ) {
			// If decryption fails, remove the corrupted data.
			$this->remove_from_queue( $user_id );
			return null;
		}
	}

	/**
	 * Update a queue item.
	 *
	 * @param int   $user_id    User ID.
	 * @param array $queue_item Updated queue item.
	 * @return bool True on success.
	 */
	public function update_queue_item( $user_id, array $queue_item ) {
		$encrypted = $this->encryption->encrypt( wp_json_encode( $queue_item ) );
		update_user_meta( $user_id, '_md_pending_verification', $encrypted );

		return true;
	}

	/**
	 * Remove a verification from the queue.
	 *
	 * @param int $user_id User ID.
	 * @return bool True on success.
	 */
	public function remove_from_queue( $user_id ) {
		delete_user_meta( $user_id, '_md_pending_verification' );
		$this->remove_from_index( $user_id );

		return true;
	}

	/**
	 * Get all pending verifications.
	 *
	 * @return array Array of user IDs with pending verifications.
	 */
	public function get_all_pending() {
		return get_option( 'md_pending_verification_index', array() );
	}

	/**
	 * Get all pending verifications with details.
	 *
	 * @return array Array of pending verification details.
	 */
	public function get_all_pending_details() {
		$pending_users = $this->get_all_pending();
		$details       = array();

		foreach ( $pending_users as $user_id ) {
			$queue_item = $this->get_from_queue( $user_id );
			if ( $queue_item ) {
				$user              = get_user_by( 'id', $user_id );
				$details[ $user_id ] = array(
					'user_id'    => $user_id,
					'user_email' => $user ? $user->user_email : '',
					'user_name'  => $user ? $user->display_name : '',
					'queue_item' => $queue_item,
				);
			}
		}

		return $details;
	}

	/**
	 * Add user to the global index.
	 *
	 * @param int $user_id User ID.
	 */
	private function add_to_index( $user_id ) {
		$index = get_option( 'md_pending_verification_index', array() );

		if ( ! in_array( $user_id, $index, true ) ) {
			$index[] = $user_id;
			update_option( 'md_pending_verification_index', $index );
		}
	}

	/**
	 * Remove user from the global index.
	 *
	 * @param int $user_id User ID.
	 */
	private function remove_from_index( $user_id ) {
		$index = get_option( 'md_pending_verification_index', array() );
		$key   = array_search( $user_id, $index, true );

		if ( false !== $key ) {
			unset( $index[ $key ] );
			update_option( 'md_pending_verification_index', array_values( $index ) );
		}
	}

	/**
	 * Get count of pending verifications.
	 *
	 * @return int Number of pending verifications.
	 */
	public function get_pending_count() {
		return count( $this->get_all_pending() );
	}

	/**
	 * Increment retry count for a queue item.
	 *
	 * @param int $user_id User ID.
	 * @return array|null Updated queue item or null.
	 */
	public function increment_retry( $user_id ) {
		$queue_item = $this->get_from_queue( $user_id );

		if ( ! $queue_item ) {
			return null;
		}

		$queue_item['retries']++;
		$queue_item['last_retry'] = time();

		$this->update_queue_item( $user_id, $queue_item );

		return $queue_item;
	}

	/**
	 * Check if a queue item has exceeded max retries.
	 *
	 * @param array $queue_item Queue item.
	 * @return bool True if max retries exceeded.
	 */
	public function has_exceeded_retries( array $queue_item ) {
		$settings    = md_get_queue_settings();
		$max_retries = isset( $settings['max_retries'] ) ? absint( $settings['max_retries'] ) : 5;

		return $queue_item['retries'] >= $max_retries;
	}

	/**
	 * Check if a queue item is ready for retry.
	 *
	 * @param array $queue_item Queue item.
	 * @return bool True if ready for retry.
	 */
	public function is_ready_for_retry( array $queue_item ) {
		if ( empty( $queue_item['last_retry'] ) ) {
			return true;
		}

		$settings       = md_get_queue_settings();
		$retry_interval = isset( $settings['retry_interval'] ) ? absint( $settings['retry_interval'] ) : 1;
		$next_retry     = $queue_item['last_retry'] + ( $retry_interval * HOUR_IN_SECONDS );

		return time() >= $next_retry;
	}

	/**
	 * Cancel a pending verification.
	 *
	 * @param int $user_id User ID.
	 * @return bool True on success.
	 */
	public function cancel_pending_verification( $user_id ) {
		return $this->remove_from_queue( $user_id );
	}

	/**
	 * Cancel all pending verifications.
	 *
	 * @return int Number of cancelled verifications.
	 */
	public function cancel_all_pending_verifications() {
		$pending_users = $this->get_all_pending();
		$cancelled_count = 0;

		foreach ( $pending_users as $user_id ) {
			if ( $this->remove_from_queue( $user_id ) ) {
				$cancelled_count++;
			}
		}

		return $cancelled_count;
	}
}
