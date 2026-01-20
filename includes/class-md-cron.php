<?php
/**
 * Cron handler class for Military Discounts plugin.
 *
 * Processes pending verifications on schedule.
 *
 * @package Military_Discounts
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class MD_Cron
 *
 * Handles scheduled processing of pending verification requests.
 */
class MD_Cron {

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
	 * Encryption instance.
	 *
	 * @var MD_Encryption
	 */
	private $encryption;

	/**
	 * Constructor.
	 *
	 * @param MD_Queue      $queue      Queue instance.
	 * @param MD_VA_API     $va_api     VA API instance.
	 * @param MD_Encryption $encryption Encryption instance.
	 */
	public function __construct( MD_Queue $queue, MD_VA_API $va_api, MD_Encryption $encryption ) {
		$this->queue      = $queue;
		$this->va_api     = $va_api;
		$this->encryption = $encryption;
	}

	/**
	 * Initialize cron hooks.
	 */
	public function init() {
		add_action( 'md_process_queue', array( $this, 'process_queue' ) );
		add_filter( 'cron_schedules', array( $this, 'add_custom_schedules' ) );
	}

	/**
	 * Add custom cron schedules.
	 *
	 * @param array $schedules Existing schedules.
	 * @return array Modified schedules.
	 */
	public function add_custom_schedules( $schedules ) {
		$settings = md_get_queue_settings();
		$interval = isset( $settings['retry_interval'] ) ? absint( $settings['retry_interval'] ) : 1;

		$schedules['md_retry_interval'] = array(
			'interval' => $interval * HOUR_IN_SECONDS,
			'display'  => sprintf(
				/* translators: %d: number of hours */
				_n( 'Every %d hour', 'Every %d hours', $interval, 'military-discounts' ),
				$interval
			),
		);

		return $schedules;
	}

	/**
	 * Process the pending verification queue.
	 */
	public function process_queue() {
		$pending_users = $this->queue->get_all_pending();

		if ( empty( $pending_users ) ) {
			return;
		}

		foreach ( $pending_users as $user_id ) {
			$this->process_user_verification( $user_id );
		}
	}

	/**
	 * Process a single user's verification.
	 *
	 * @param int $user_id User ID.
	 */
	private function process_user_verification( $user_id ) {
		$queue_item = $this->queue->get_from_queue( $user_id );

		if ( ! $queue_item ) {
			$this->queue->remove_from_queue( $user_id );
			return;
		}

		// Check if ready for retry.
		if ( ! $this->queue->is_ready_for_retry( $queue_item ) ) {
			return;
		}

		// Check if max retries exceeded.
		if ( $this->queue->has_exceeded_retries( $queue_item ) ) {
			$this->handle_max_retries_exceeded( $user_id, $queue_item );
			return;
		}

		// Process based on verification type.
		if ( 'veteran' === $queue_item['type'] ) {
			$this->process_veteran_verification( $user_id, $queue_item );
		}
		// Military OTP verifications don't need queue processing.
	}

	/**
	 * Process a veteran verification.
	 *
	 * @param int   $user_id    User ID.
	 * @param array $queue_item Queue item.
	 */
	private function process_veteran_verification( $user_id, array $queue_item ) {
		$va_settings = md_get_va_api_settings();

		if ( empty( $va_settings['enabled'] ) ) {
			return;
		}

		try {
			$result = $this->va_api->verify( $queue_item['data'] );

			if ( 'confirmed' === $result['status'] ) {
				// Verification successful.
				md_set_verified( $user_id, 'veteran', true );
				$this->queue->remove_from_queue( $user_id );
				$this->send_notification( $user_id, 'approved', 'veteran' );
			} elseif ( 'not_confirmed' === $result['status'] ) {
				// Handle based on reason.
				$reason = isset( $result['reason'] ) ? $result['reason'] : '';

				if ( 'ERROR' === $reason ) {
					// Temporary error, increment retry.
					$this->queue->increment_retry( $user_id );
				} else {
					// Permanent denial.
					$this->queue->remove_from_queue( $user_id );
					$this->send_notification( $user_id, 'denied', 'veteran', $reason );
				}
			}
		} catch ( Exception $e ) {
			// API error, increment retry.
			$this->queue->increment_retry( $user_id );
		}
	}

	/**
	 * Handle max retries exceeded.
	 *
	 * @param int   $user_id    User ID.
	 * @param array $queue_item Queue item.
	 */
	private function handle_max_retries_exceeded( $user_id, array $queue_item ) {
		// Mark as failed and remove from queue.
		$this->queue->remove_from_queue( $user_id );

		// Send denial notification.
		$this->send_notification(
			$user_id,
			'denied',
			$queue_item['type'],
			'MAX_RETRIES_EXCEEDED'
		);
	}

	/**
	 * Send notification email.
	 *
	 * @param int    $user_id User ID.
	 * @param string $status  Notification status ('approved' or 'denied').
	 * @param string $type    Verification type.
	 * @param string $reason  Optional denial reason.
	 */
	private function send_notification( $user_id, $status, $type, $reason = '' ) {
		$emails = WC()->mailer()->get_emails();

		if ( 'approved' === $status && isset( $emails['MD_Email_Verification_Approved'] ) ) {
			$emails['MD_Email_Verification_Approved']->trigger( $user_id, $type );
		} elseif ( 'denied' === $status && isset( $emails['MD_Email_Verification_Denied'] ) ) {
			$emails['MD_Email_Verification_Denied']->trigger( $user_id, $type, $reason );
		}
	}

	/**
	 * Manually trigger queue processing.
	 *
	 * @return int Number of items processed.
	 */
	public function manual_process() {
		$pending_users = $this->queue->get_all_pending();
		$processed     = 0;

		foreach ( $pending_users as $user_id ) {
			$this->process_user_verification( $user_id );
			$processed++;
		}

		return $processed;
	}
}
