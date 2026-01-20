<?php
/**
 * Admin class for Military Discounts plugin.
 *
 * Handles admin initialization and menu registration.
 *
 * @package Military_Discounts
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class MD_Admin
 *
 * Admin initialization and setup.
 */
class MD_Admin {

	/**
	 * Initialize admin hooks.
	 */
	public function init() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_filter( 'plugin_action_links_' . MD_PLUGIN_BASENAME, array( $this, 'add_plugin_links' ) );

		// User profile hooks.
		add_action( 'show_user_profile', array( $this, 'render_user_military_status' ) );
		add_action( 'edit_user_profile', array( $this, 'render_user_military_status' ) );

		// AJAX handlers for admin actions.
		add_action( 'wp_ajax_md_admin_set_status', array( $this, 'ajax_set_status' ) );
		add_action( 'wp_ajax_md_admin_remove_status', array( $this, 'ajax_remove_status' ) );
	}

	/**
	 * Add admin menu.
	 */
	public function add_menu() {
		add_submenu_page(
			'woocommerce',
			__( 'Military Discounts', 'military-discounts' ),
			__( 'Military Discounts', 'military-discounts' ),
			'manage_woocommerce',
			'military-discounts',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_scripts( $hook ) {
		// Only load on our settings page.
		if ( 'woocommerce_page_military-discounts' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'md-admin',
			MD_PLUGIN_URL . 'assets/css/md-admin.css',
			array(),
			MD_VERSION
		);

		wp_enqueue_script(
			'md-admin',
			MD_PLUGIN_URL . 'assets/js/md-admin.js',
			array( 'jquery', 'jquery-ui-sortable' ),
			MD_VERSION,
			true
		);

		wp_localize_script(
			'md-admin',
			'mdAdmin',
			array(
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( 'md_admin_nonce' ),
				'strings'   => array(
					'confirmClearLogs' => __( 'Are you sure you want to clear all logs?', 'military-discounts' ),
					'clearingLogs'     => __( 'Clearing logs...', 'military-discounts' ),
					'logsCleared'      => __( 'Logs cleared successfully.', 'military-discounts' ),
					'errorClearing'    => __( 'Error clearing logs.', 'military-discounts' ),
					'testingApi'       => __( 'Testing API connection...', 'military-discounts' ),
					'saving'           => __( 'Saving...', 'military-discounts' ),
				),
			)
		);
	}

	/**
	 * Render the settings page.
	 */
	public function render_settings_page() {
		// Check user capabilities.
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		// Get current tab.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Tab navigation only, no data processing.
		$current_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'general';

		// Define tabs.
		$tabs = array(
			'general'      => __( 'General', 'military-discounts' ),
			'va-api'       => __( 'VA API', 'military-discounts' ),
			'military-otp' => __( 'Military OTP', 'military-discounts' ),
			'queue'        => __( 'Queue', 'military-discounts' ),
			'form-builder' => __( 'Form Builder', 'military-discounts' ),
			'logs'         => __( 'VA API Logs', 'military-discounts' ),
		);

		?>
		<div class="wrap md-admin-wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<nav class="nav-tab-wrapper md-tabs">
				<?php
				foreach ( $tabs as $tab_id => $tab_name ) :
					$class = ( $current_tab === $tab_id ) ? 'nav-tab nav-tab-active' : 'nav-tab';
					$url   = add_query_arg(
						array(
							'page' => 'military-discounts',
							'tab'  => $tab_id,
						),
						admin_url( 'admin.php' )
					);
					?>
					<a href="<?php echo esc_url( $url ); ?>" class="<?php echo esc_attr( $class ); ?>">
						<?php echo esc_html( $tab_name ); ?>
					</a>
				<?php endforeach; ?>
			</nav>

			<div class="md-tab-content">
				<?php
				// Include tab content based on current tab.
				switch ( $current_tab ) {
					case 'va-api':
						$this->render_va_api_tab();
						break;
					case 'military-otp':
						$this->render_military_otp_tab();
						break;
					case 'queue':
						$this->render_queue_tab();
						break;
					case 'form-builder':
						$this->render_form_builder_tab();
						break;
					case 'logs':
						$this->render_logs_tab();
						break;
					default:
						$this->render_general_tab();
						break;
				}
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render general settings tab.
	 */
	private function render_general_tab() {
		?>
		<form method="post" action="options.php">
			<?php
			settings_fields( 'md_settings_general' );
			do_settings_sections( 'md-settings-general' );
			submit_button();
			?>
		</form>
		<?php
	}

	/**
	 * Render VA API settings tab.
	 */
	private function render_va_api_tab() {
		?>
		<form method="post" action="options.php">
			<?php
			settings_fields( 'md_settings_va_api' );
			do_settings_sections( 'md-settings-va-api' );
			submit_button();
			?>
		</form>

		<hr>
		<h3><?php esc_html_e( 'Test API Connection', 'military-discounts' ); ?></h3>
		<p><?php esc_html_e( 'Click the button below to test the VA API connection using sandbox test data.', 'military-discounts' ); ?></p>
		<button type="button" class="button" id="md-test-api">
			<?php esc_html_e( 'Test Connection', 'military-discounts' ); ?>
		</button>
		<div id="md-test-result" class="md-test-result"></div>
		<?php
	}

	/**
	 * Render Military OTP settings tab.
	 */
	private function render_military_otp_tab() {
		?>
		<form method="post" action="options.php">
			<?php
			settings_fields( 'md_settings_military_otp' );
			do_settings_sections( 'md-settings-military-otp' );
			submit_button();
			?>
		</form>
		<?php
	}

	/**
	 * Render queue management tab.
	 */
	private function render_queue_tab() {
		?>
		<form method="post" action="options.php">
			<?php
			settings_fields( 'md_settings_queue' );
			do_settings_sections( 'md-settings-queue' );
			submit_button();
			?>
		</form>

		<hr>
		<h3><?php esc_html_e( 'Pending Verifications', 'military-discounts' ); ?></h3>
		<?php
		$queue = new MD_Queue( new MD_Encryption() );
		$pending = $queue->get_all_pending_details();

		if ( empty( $pending ) ) {
			echo '<p>' . esc_html__( 'No pending verifications.', 'military-discounts' ) . '</p>';
		} else {
			?>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'User', 'military-discounts' ); ?></th>
						<th><?php esc_html_e( 'Type', 'military-discounts' ); ?></th>
						<th><?php esc_html_e( 'Created', 'military-discounts' ); ?></th>
						<th><?php esc_html_e( 'Retries', 'military-discounts' ); ?></th>
						<th><?php esc_html_e( 'Status', 'military-discounts' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $pending as $user_id => $item ) : ?>
						<tr>
							<td>
								<?php echo esc_html( $item['user_name'] ); ?><br>
								<small><?php echo esc_html( $item['user_email'] ); ?></small>
							</td>
							<td><?php echo esc_html( ucfirst( $item['queue_item']['type'] ) ); ?></td>
							<td><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $item['queue_item']['created_at'] ) ); ?></td>
							<td><?php echo esc_html( $item['queue_item']['retries'] ); ?></td>
							<td><?php echo esc_html( ucfirst( $item['queue_item']['status'] ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php
		}
	}

	/**
	 * Render form builder tab.
	 */
	private function render_form_builder_tab() {
		$form_builder = new MD_Form_Builder();
		$form_builder->render_builder();
	}

	/**
	 * Render logs tab.
	 */
	private function render_logs_tab() {
		$logger = new MD_Logger();
		$logs   = $logger->get_logs( 50 );
		$count  = $logger->get_log_count();

		?>
		<div class="md-logs-header">
			<h3><?php esc_html_e( 'VA API Request Logs', 'military-discounts' ); ?></h3>
			<p>
				<?php
				printf(
					/* translators: %d: number of logs */
					esc_html__( 'Showing %d most recent logs.', 'military-discounts' ),
					esc_html( min( 50, $count ) )
				);
				?>
			</p>
			<div class="md-logs-actions">
				<button type="button" class="button" id="md-clear-logs">
					<?php esc_html_e( 'Clear All Logs', 'military-discounts' ); ?>
				</button>
				<button type="button" class="button" id="md-export-logs">
					<?php esc_html_e( 'Export Logs', 'military-discounts' ); ?>
				</button>
			</div>
		</div>

		<?php if ( empty( $logs ) ) : ?>
			<p><?php esc_html_e( 'No logs found.', 'military-discounts' ); ?></p>
		<?php else : ?>
			<table class="widefat striped md-logs-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Timestamp', 'military-discounts' ); ?></th>
						<th><?php esc_html_e( 'User', 'military-discounts' ); ?></th>
						<th><?php esc_html_e( 'Status', 'military-discounts' ); ?></th>
						<th><?php esc_html_e( 'Request', 'military-discounts' ); ?></th>
						<th><?php esc_html_e( 'Response', 'military-discounts' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $logs as $log ) : ?>
						<tr class="md-log-status-<?php echo esc_attr( $log['status'] ); ?>">
							<td><?php echo esc_html( $log['timestamp'] ); ?></td>
							<td>
								<?php
								if ( $log['user_id'] ) {
									$user = get_user_by( 'id', $log['user_id'] );
									echo $user ? esc_html( $user->user_email ) : esc_html( $log['user_id'] );
								} else {
									esc_html_e( 'N/A', 'military-discounts' );
								}
								?>
							</td>
							<td>
								<span class="md-log-badge md-log-badge-<?php echo esc_attr( $log['status'] ); ?>">
									<?php echo esc_html( ucfirst( $log['status'] ) ); ?>
								</span>
							</td>
							<td>
								<code class="md-log-code"><?php echo esc_html( wp_json_encode( $log['request'] ) ); ?></code>
							</td>
							<td>
								<code class="md-log-code"><?php echo esc_html( wp_json_encode( $log['response'] ) ); ?></code>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>

		<form method="post" action="options.php" style="margin-top: 20px;">
			<?php
			settings_fields( 'md_settings_logs' );
			do_settings_sections( 'md-settings-logs' );
			submit_button();
			?>
		</form>
		<?php
	}

	/**
	 * Add plugin action links.
	 *
	 * @param array $links Existing links.
	 * @return array Modified links.
	 */
	public function add_plugin_links( $links ) {
		$plugin_links = array(
			'<a href="' . admin_url( 'admin.php?page=military-discounts' ) . '">' . __( 'Settings', 'military-discounts' ) . '</a>',
		);

		return array_merge( $plugin_links, $links );
	}

	/**
	 * Render military status section on user profile.
	 *
	 * @param WP_User $user User object.
	 */
	public function render_user_military_status( $user ) {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$status = md_get_verification_status( $user->ID );
		$queue  = new MD_Queue( new MD_Encryption() );
		$pending_item = $queue->get_from_queue( $user->ID );

		?>
		<h2><?php esc_html_e( 'Military Discount Status', 'military-discounts' ); ?></h2>
		<table class="form-table" id="md-admin-user-status">
			<tr>
				<th><?php esc_html_e( 'Veteran Status', 'military-discounts' ); ?></th>
				<td>
					<?php if ( $status['is_veteran'] ) : ?>
						<span class="md-status-badge md-status-verified">
							<?php esc_html_e( 'Verified', 'military-discounts' ); ?>
						</span>
						<?php if ( ! empty( $status['veteran_expires_at'] ) ) : ?>
							<span class="description">
								<?php
								printf(
									/* translators: %s: expiry date */
									esc_html__( 'Expires: %s', 'military-discounts' ),
									esc_html( date_i18n( get_option( 'date_format' ), $status['veteran_expires_at'] ) )
								);
								?>
							</span>
						<?php endif; ?>
						<button type="button" class="button md-remove-status" data-user="<?php echo esc_attr( $user->ID ); ?>" data-type="veteran">
							<?php esc_html_e( 'Remove', 'military-discounts' ); ?>
						</button>
					<?php else : ?>
						<span class="md-status-badge md-status-none">
							<?php esc_html_e( 'Not Verified', 'military-discounts' ); ?>
						</span>
						<button type="button" class="button button-primary md-set-status" data-user="<?php echo esc_attr( $user->ID ); ?>" data-type="veteran">
							<?php esc_html_e( 'Grant Veteran Status', 'military-discounts' ); ?>
						</button>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Active Military Status', 'military-discounts' ); ?></th>
				<td>
					<?php if ( $status['is_military'] ) : ?>
						<span class="md-status-badge md-status-verified">
							<?php esc_html_e( 'Verified', 'military-discounts' ); ?>
						</span>
						<?php if ( ! empty( $status['military_expires_at'] ) ) : ?>
							<span class="description">
								<?php
								printf(
									/* translators: %s: expiry date */
									esc_html__( 'Expires: %s', 'military-discounts' ),
									esc_html( date_i18n( get_option( 'date_format' ), $status['military_expires_at'] ) )
								);
								?>
							</span>
						<?php endif; ?>
						<button type="button" class="button md-remove-status" data-user="<?php echo esc_attr( $user->ID ); ?>" data-type="military">
							<?php esc_html_e( 'Remove', 'military-discounts' ); ?>
						</button>
					<?php else : ?>
						<span class="md-status-badge md-status-none">
							<?php esc_html_e( 'Not Verified', 'military-discounts' ); ?>
						</span>
						<button type="button" class="button button-primary md-set-status" data-user="<?php echo esc_attr( $user->ID ); ?>" data-type="military">
							<?php esc_html_e( 'Grant Military Status', 'military-discounts' ); ?>
						</button>
					<?php endif; ?>
				</td>
			</tr>
			<?php if ( $pending_item ) : ?>
			<tr>
				<th><?php esc_html_e( 'Pending Request', 'military-discounts' ); ?></th>
				<td>
					<span class="md-status-badge md-status-pending">
						<?php echo esc_html( ucfirst( $pending_item['type'] ) ); ?> <?php esc_html_e( 'verification pending', 'military-discounts' ); ?>
					</span>
					<span class="description">
						<?php
						printf(
							/* translators: %s: date */
							esc_html__( 'Submitted: %s', 'military-discounts' ),
							esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $pending_item['created_at'] ) )
						);
						?>
					</span>
				</td>
			</tr>
			<?php endif; ?>
		</table>
		<style>
			.md-status-badge { display: inline-block; padding: 3px 8px; border-radius: 3px; font-size: 12px; margin-right: 10px; }
			.md-status-verified { background: #d4edda; color: #155724; }
			.md-status-none { background: #f8d7da; color: #721c24; }
			.md-status-pending { background: #fff3cd; color: #856404; }
			#md-admin-user-status .button { margin-left: 10px; }
		</style>
		<script>
		jQuery(function($) {
			$('#md-admin-user-status').on('click', '.md-set-status', function() {
				var btn = $(this);
				var userId = btn.data('user');
				var type = btn.data('type');
				btn.prop('disabled', true).text('<?php esc_html_e( 'Processing...', 'military-discounts' ); ?>');
				$.post(ajaxurl, {
					action: 'md_admin_set_status',
					nonce: '<?php echo esc_js( wp_create_nonce( 'md_admin_nonce' ) ); ?>',
					user_id: userId,
					type: type
				}, function(response) {
					if (response.success) {
						location.reload();
					} else {
						alert(response.data || 'Error');
						btn.prop('disabled', false);
					}
				});
			});
			$('#md-admin-user-status').on('click', '.md-remove-status', function() {
				if (!confirm('<?php esc_html_e( 'Are you sure you want to remove this status?', 'military-discounts' ); ?>')) return;
				var btn = $(this);
				var userId = btn.data('user');
				var type = btn.data('type');
				btn.prop('disabled', true).text('<?php esc_html_e( 'Processing...', 'military-discounts' ); ?>');
				$.post(ajaxurl, {
					action: 'md_admin_remove_status',
					nonce: '<?php echo esc_js( wp_create_nonce( 'md_admin_nonce' ) ); ?>',
					user_id: userId,
					type: type
				}, function(response) {
					if (response.success) {
						location.reload();
					} else {
						alert(response.data || 'Error');
						btn.prop('disabled', false);
					}
				});
			});
		});
		</script>
		<?php
	}

	/**
	 * AJAX handler to set military/veteran status.
	 */
	public function ajax_set_status() {
		check_ajax_referer( 'md_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( __( 'Permission denied.', 'military-discounts' ) );
		}

		$user_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;
		$type    = isset( $_POST['type'] ) ? sanitize_key( $_POST['type'] ) : '';

		if ( ! $user_id || ! in_array( $type, array( 'veteran', 'military' ), true ) ) {
			wp_send_json_error( __( 'Invalid request.', 'military-discounts' ) );
		}

		// Set verified status.
		md_set_verified( $user_id, $type, true );

		// Clear any pending verification.
		$queue = new MD_Queue( new MD_Encryption() );
		$queue->remove_from_queue( $user_id );

		wp_send_json_success();
	}

	/**
	 * AJAX handler to remove military/veteran status.
	 */
	public function ajax_remove_status() {
		check_ajax_referer( 'md_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( __( 'Permission denied.', 'military-discounts' ) );
		}

		$user_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;
		$type    = isset( $_POST['type'] ) ? sanitize_key( $_POST['type'] ) : '';

		if ( ! $user_id || ! in_array( $type, array( 'veteran', 'military' ), true ) ) {
			wp_send_json_error( __( 'Invalid request.', 'military-discounts' ) );
		}

		// Remove verified status.
		md_set_verified( $user_id, $type, false );

		// Clear any pending verification.
		$queue = new MD_Queue( new MD_Encryption() );
		$queue->remove_from_queue( $user_id );

		wp_send_json_success();
	}
}
