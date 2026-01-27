<?php
/**
 * My Account class for Military Discounts plugin.
 *
 * Adds Military Discounts endpoint to WooCommerce My Account.
 *
 * @package Military_Discounts
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class MD_My_Account
 *
 * Handles My Account integration.
 */
class MD_My_Account {

	/**
	 * Initialize My Account hooks.
	 */
	public function init() {
		add_action( 'init', array( $this, 'add_endpoint' ) );
		add_filter( 'woocommerce_account_menu_items', array( $this, 'add_menu_item' ) );
		add_action( 'woocommerce_account_military-discounts_endpoint', array( $this, 'render_endpoint' ) );
		add_filter( 'woocommerce_get_query_vars', array( $this, 'add_query_vars' ) );
	}

	/**
	 * Add rewrite endpoint.
	 */
	public function add_endpoint() {
		add_rewrite_endpoint( 'military-discounts', EP_ROOT | EP_PAGES );
	}

	/**
	 * Add query vars.
	 *
	 * @param array $vars Query vars.
	 * @return array Modified vars.
	 */
	public function add_query_vars( $vars ) {
		$vars['military-discounts'] = 'military-discounts';
		return $vars;
	}

	/**
	 * Add menu item to My Account.
	 *
	 * @param array $items Menu items.
	 * @return array Modified items.
	 */
	public function add_menu_item( $items ) {
		$settings = md_get_general_settings();
		$page_title = ! empty( $settings['page_title'] ) ? $settings['page_title'] : __( 'Military Discounts', 'military-discounts' );
		$menu_order = isset( $settings['menu_order'] ) ? $settings['menu_order'] : 10;

		// Our menu item to add
		$our_item = array( 'military-discounts' => $page_title );
		
		// If menu order is 0 or 1, add at the beginning
		if ( $menu_order <= 1 ) {
			return array_merge( $our_item, $items );
		}

		// Get the default order of WooCommerce menu items
		$default_order = array(
			'dashboard',
			'orders',
			'downloads',
			'edit-address',
			'payment-methods',
			'edit-account',
			'customer-logout'
		);

		// Create a weight map based on default order
		$item_weights = array();
		foreach ( $default_order as $index => $key ) {
			$item_weights[ $key ] = $index + 1; // 1-based index
		}

		// Handle any custom menu items added by other plugins
		$custom_item_weight = 7; // After customer-logout by default
		foreach ( $items as $key => $value ) {
			if ( ! isset( $item_weights[ $key ] ) ) {
				$item_weights[ $key ] = $custom_item_weight++;
			}
		}

		// Build the new menu
		$new_items = array();
		$inserted = false;

		foreach ( $items as $key => $value ) {
			// If we haven't inserted our item yet and the current item's weight is >= our menu order
			if ( ! $inserted && $item_weights[ $key ] >= $menu_order ) {
				$new_items = array_merge( $new_items, $our_item );
				$inserted = true;
			}
			$new_items[ $key ] = $value;
		}

		// If we didn't insert it yet, add it at the end
		if ( ! $inserted ) {
			// If there's a logout item, add before it
			if ( isset( $new_items['customer-logout'] ) ) {
				$logout = $new_items['customer-logout'];
				unset( $new_items['customer-logout'] );
				$new_items = array_merge( $new_items, $our_item );
				$new_items['customer-logout'] = $logout;
			} else {
				$new_items = array_merge( $new_items, $our_item );
			}
		}

		return $new_items;
	}

	/**
	 * Render the endpoint content.
	 */
	public function render_endpoint() {
		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			echo '<p>' . esc_html__( 'Please log in to verify your military status.', 'military-discounts' ) . '</p>';
			return;
		}

		$status = md_get_verification_status( $user_id );
		$settings = md_get_general_settings();

		// Check if plugin is enabled.
		if ( empty( $settings['enabled'] ) ) {
			echo '<p>' . esc_html__( 'Military verification is currently unavailable.', 'military-discounts' ) . '</p>';
			return;
		}

		?>
		<div class="md-verification-container">
			<h2><?php 
				$settings = md_get_general_settings();
				echo esc_html( ! empty( $settings['page_title'] ) ? $settings['page_title'] : __( 'Military Discounts', 'military-discounts' ) ); 
			?></h2>
			<p><?php esc_html_e( 'Verify your veteran or active military status to access exclusive discounts.', 'military-discounts' ); ?></p>

			<?php if ( $status['is_veteran'] || $status['is_military'] ) : ?>
				<!-- Verified Status -->
				<?php $this->render_verified_status( $status ); ?>
			<?php elseif ( $status['has_pending'] ) : ?>
				<!-- Pending Status -->
				<?php $this->render_pending_status(); ?>
			<?php else : ?>
				<!-- Verification Form -->
				<?php $this->render_verification_form(); ?>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render verified status display.
	 *
	 * @param array $status Verification status.
	 */
	private function render_verified_status( array $status ) {
		?>
		<div class="md-status md-status-verified">
			<div class="md-status-icon">
				<span class="dashicons dashicons-yes-alt"></span>
			</div>
			<div class="md-status-content">
				<?php if ( $status['is_veteran'] ) : ?>
					<h3><?php esc_html_e( 'Veteran Status Verified', 'military-discounts' ); ?></h3>
					<p>
						<?php
						if ( ! empty( $status['veteran_expires_at'] ) ) {
							printf(
								/* translators: %s: expiry date */
								esc_html__( 'Valid until %s', 'military-discounts' ),
								esc_html( date_i18n( get_option( 'date_format' ), $status['veteran_expires_at'] ) )
							);
						} else {
							esc_html_e( 'No expiration', 'military-discounts' );
						}
						?>
					</p>
				<?php endif; ?>

				<?php if ( $status['is_military'] ) : ?>
					<h3><?php esc_html_e( 'Active Military Status Verified', 'military-discounts' ); ?></h3>
					<p>
						<?php
						if ( ! empty( $status['military_expires_at'] ) ) {
							printf(
								/* translators: %s: expiry date */
								esc_html__( 'Valid until %s', 'military-discounts' ),
								esc_html( date_i18n( get_option( 'date_format' ), $status['military_expires_at'] ) )
							);
						} else {
							esc_html_e( 'No expiration', 'military-discounts' );
						}
						?>
					</p>
				<?php endif; ?>
			</div>
		</div>

		<p class="md-verified-note">
			<?php esc_html_e( 'You are eligible for military discounts on applicable products and coupons.', 'military-discounts' ); ?>
		</p>
		<?php
	}

	/**
	 * Render pending status display.
	 */
	private function render_pending_status() {
		$user_id = get_current_user_id();
		$queue   = new MD_Queue( new MD_Encryption() );
		$pending = $queue->get_from_queue( $user_id );

		?>
		<div class="md-status md-status-pending">
			<div class="md-status-icon">
				<span class="dashicons dashicons-clock"></span>
			</div>
			<div class="md-status-content">
				<h3><?php esc_html_e( 'Verification Pending', 'military-discounts' ); ?></h3>
				<p><?php esc_html_e( 'Your verification request has been submitted and is being processed. You will receive an email notification once complete.', 'military-discounts' ); ?></p>
				<?php if ( $pending ) : ?>
					<ul class="md-pending-details">
						<li>
							<strong><?php esc_html_e( 'Type:', 'military-discounts' ); ?></strong>
							<?php echo esc_html( 'veteran' === $pending['type'] ? __( 'Veteran', 'military-discounts' ) : __( 'Active Military', 'military-discounts' ) ); ?>
						</li>
						<li>
							<strong><?php esc_html_e( 'Submitted:', 'military-discounts' ); ?></strong>
							<?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $pending['created_at'] ) ); ?>
						</li>
						<li>
							<strong><?php esc_html_e( 'Status:', 'military-discounts' ); ?></strong>
							<?php echo esc_html( ucfirst( $pending['status'] ) ); ?>
						</li>
					</ul>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the verification form.
	 */
	private function render_verification_form() {
		$va_settings  = md_get_va_api_settings();
		$otp_settings = md_get_military_otp_settings();

		$show_veteran  = ! empty( $va_settings['enabled'] );
		$show_military = ! empty( $otp_settings['enabled'] );

		if ( ! $show_veteran && ! $show_military ) {
			echo '<p>' . esc_html__( 'No verification methods are currently available.', 'military-discounts' ) . '</p>';
			return;
		}

		?>
		<div class="md-form-wrapper" id="md-verification-form">
			<!-- Step Indicator -->
			<div class="md-steps">
				<div class="md-step active" data-step="1">
					<span class="md-step-number">1</span>
					<span class="md-step-label"><?php esc_html_e( 'Select Type', 'military-discounts' ); ?></span>
				</div>
				<div class="md-step" data-step="2">
					<span class="md-step-number">2</span>
					<span class="md-step-label"><?php esc_html_e( 'Enter Info', 'military-discounts' ); ?></span>
				</div>
				<div class="md-step" data-step="3">
					<span class="md-step-number">3</span>
					<span class="md-step-label"><?php esc_html_e( 'Submit', 'military-discounts' ); ?></span>
				</div>
			</div>

			<!-- Step 1: Select Type -->
			<div class="md-form-step active" data-step="1">
				<h3><?php esc_html_e( 'Select Verification Type', 'military-discounts' ); ?></h3>

				<div class="md-type-options">
					<?php if ( $show_veteran ) : ?>
						<label class="md-type-option">
							<input type="radio" name="verification_type" value="veteran">
							<span class="md-type-card">
								<span class="md-type-icon">🎖️</span>
								<span class="md-type-title"><?php esc_html_e( 'I am a Veteran', 'military-discounts' ); ?></span>
								<span class="md-type-desc"><?php esc_html_e( 'Verify through VA records', 'military-discounts' ); ?></span>
							</span>
						</label>
					<?php endif; ?>

					<?php if ( $show_military ) : ?>
						<label class="md-type-option">
							<input type="radio" name="verification_type" value="military">
							<span class="md-type-card">
								<span class="md-type-icon">🪖</span>
								<span class="md-type-title"><?php esc_html_e( 'I am Active Military', 'military-discounts' ); ?></span>
								<span class="md-type-desc"><?php esc_html_e( 'Verify with .mil email', 'military-discounts' ); ?></span>
							</span>
						</label>
					<?php endif; ?>
				</div>

				<div class="md-form-actions">
					<button type="button" class="button button-primary md-next-step" disabled>
						<?php esc_html_e( 'Next', 'military-discounts' ); ?>
					</button>
				</div>
			</div>

			<!-- Step 2: Enter Information -->
			<div class="md-form-step" data-step="2">
				<h3 class="md-step-title"></h3>

				<div class="md-form-fields" id="md-dynamic-fields">
					<!-- Fields loaded via AJAX based on selection -->
				</div>

				<div class="md-form-actions">
					<button type="button" class="button md-prev-step">
						<?php esc_html_e( 'Back', 'military-discounts' ); ?>
					</button>
					<button type="button" class="button button-primary md-next-step">
						<?php esc_html_e( 'Next', 'military-discounts' ); ?>
					</button>
				</div>
			</div>

			<!-- Step 3: Submit -->
			<div class="md-form-step" data-step="3">
				<div id="md-step-3-content">
					<!-- Content depends on verification type -->
				</div>
			</div>

			<!-- Messages -->
			<div class="md-messages" id="md-messages" style="display: none;"></div>
		</div>
		<?php
	}
}
