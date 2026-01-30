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
			<p><?php echo esc_html( md_get_form_text_settings()['page_subtitle'] ); ?></p>

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
		$form_text_settings = md_get_form_text_settings();
		?>
		<div class="md-status md-status-verified">
			<div class="md-status-icon">
				<span class="dashicons dashicons-yes-alt"></span>
			</div>
			<div class="md-status-content">
				<?php if ( $status['is_veteran'] ) : ?>
					<h3><?php echo esc_html( $form_text_settings['verified_veteran_title'] ); ?></h3>
					<p>
						<?php
						if ( ! empty( $status['veteran_expires_at'] ) ) {
							printf(
								esc_html( $form_text_settings['verified_valid_until'] ),
								esc_html( date_i18n( get_option( 'date_format' ), $status['veteran_expires_at'] ) )
							);
						} else {
							echo esc_html( $form_text_settings['verified_no_expiration'] );
						}
						?>
					</p>
				<?php endif; ?>

				<?php if ( $status['is_military'] ) : ?>
					<h3><?php echo esc_html( $form_text_settings['verified_military_title'] ); ?></h3>
					<p>
						<?php
						if ( ! empty( $status['military_expires_at'] ) ) {
							printf(
								esc_html( $form_text_settings['verified_valid_until'] ),
								esc_html( date_i18n( get_option( 'date_format' ), $status['military_expires_at'] ) )
							);
						} else {
							echo esc_html( $form_text_settings['verified_no_expiration'] );
						}
						?>
					</p>
				<?php endif; ?>
			</div>
		</div>

		<p class="md-verified-note">
			<?php echo esc_html( $form_text_settings['verified_note'] ); ?>
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
		$form_text_settings = md_get_form_text_settings();

		?>
		<div class="md-status md-status-pending">
			<div class="md-status-icon">
				<span class="dashicons dashicons-clock"></span>
			</div>
			<div class="md-status-content">
				<h3><?php echo esc_html( $form_text_settings['pending_title'] ); ?></h3>
				<p><?php echo esc_html( $form_text_settings['pending_description'] ); ?></p>
				<?php if ( $pending ) : ?>
					<ul class="md-pending-details">
						<li>
							<strong><?php echo esc_html( $form_text_settings['pending_type_label'] ); ?></strong>
							<?php echo esc_html( 'veteran' === $pending['type'] ? __( 'Veteran', 'military-discounts' ) : __( 'Active Military', 'military-discounts' ) ); ?>
						</li>
						<li>
							<strong><?php echo esc_html( $form_text_settings['pending_submitted_label'] ); ?></strong>
							<?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $pending['created_at'] ) ); ?>
						</li>
						<li>
							<strong><?php echo esc_html( $form_text_settings['pending_status_label'] ); ?></strong>
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
		$security_settings = md_get_security_settings();
		$user_id = get_current_user_id();

		$show_veteran  = ! empty( $va_settings['enabled'] );
		$show_military = ! empty( $otp_settings['enabled'] );

		if ( ! $show_veteran && ! $show_military ) {
			echo '<p>' . esc_html__( 'No verification methods are currently available.', 'military-discounts' ) . '</p>';
			return;
		}

		// Check lockout status
		if ( $security_settings['enable_lockout'] ) {
			$is_locked_veteran = $show_veteran && md_is_locked_out( $user_id, 'veteran' );
			$is_locked_military = $show_military && md_is_locked_out( $user_id, 'military' );

			if ( $is_locked_veteran || $is_locked_military ) {
				$remaining = 0;
				$type = '';
				
				if ( $is_locked_veteran ) {
					$remaining = md_get_lockout_remaining( $user_id, 'veteran' );
					$type = __( 'veteran', 'military-discounts' );
				} elseif ( $is_locked_military ) {
					$remaining = md_get_lockout_remaining( $user_id, 'military' );
					$type = __( 'military', 'military-discounts' );
				}

				?>
				<div class="md-status md-status-locked">
					<div class="md-status-icon">
						<span class="dashicons dashicons-lock"></span>
					</div>
					<div class="md-status-content">
						<h3><?php echo esc_html( md_get_form_text_settings()['lockout_title'] ); ?></h3>
						<p><?php printf( esc_html( md_get_form_text_settings()['lockout_description'] ), $type, $remaining ); ?></p>
					</div>
				</div>
				<?php

				// If both methods are locked, don't show the form
				if ( $is_locked_veteran && $is_locked_military ) {
					return;
				}
			}

			// Show failed attempts info with progress
			$failed_veteran = $show_veteran ? md_get_failed_attempts( $user_id, 'veteran' ) : 0;
			$failed_military = $show_military ? md_get_failed_attempts( $user_id, 'military' ) : 0;

			if ( $failed_veteran > 0 || $failed_military > 0 ) {
				$form_text_settings = md_get_form_text_settings();
				?>
				<div class="md-failed-attempts">
					<?php if ( $failed_veteran > 0 ) : ?>
						<p class="md-failed-veteran">
							<?php printf( esc_html( $form_text_settings['failed_veteran_text'] ), $failed_veteran, $security_settings['max_failed_veteran_attempts'] ); ?>
							<span class="md-attempts-bar">
								<span class="md-attempts-progress-fill <?php echo ( $failed_veteran >= $security_settings['max_failed_veteran_attempts'] * 0.8 ) ? 'danger' : ( $failed_veteran >= $security_settings['max_failed_veteran_attempts'] * 0.5 ? 'warning' : '' ); ?>" 
									  style="width: <?php echo ( $failed_veteran / $security_settings['max_failed_veteran_attempts'] ) * 100; ?>%;"></span>
							</span>
						</p>
					<?php endif; ?>
					<?php if ( $failed_military > 0 ) : ?>
						<p class="md-failed-military">
							<?php printf( esc_html( $form_text_settings['failed_military_text'] ), $failed_military, $security_settings['max_failed_military_attempts'] ); ?>
							<span class="md-attempts-bar">
								<span class="md-attempts-progress-fill <?php echo ( $failed_military >= $security_settings['max_failed_military_attempts'] * 0.8 ) ? 'danger' : ( $failed_military >= $security_settings['max_failed_military_attempts'] * 0.5 ? 'warning' : '' ); ?>" 
									  style="width: <?php echo ( $failed_military / $security_settings['max_failed_military_attempts'] ) * 100; ?>%;"></span>
							</span>
						</p>
					<?php endif; ?>
				</div>
				<?php
			}
		}

		?>
		<div class="md-form-wrapper" id="md-verification-form">
			<!-- Step Indicator -->
			<div class="md-steps">
				<?php $form_text_settings = md_get_form_text_settings(); ?>
				<div class="md-step active" data-step="1">
					<span class="md-step-number">1</span>
					<span class="md-step-label"><?php echo esc_html( $form_text_settings['step1_label'] ); ?></span>
				</div>
				<div class="md-step" data-step="2">
					<span class="md-step-number">2</span>
					<span class="md-step-label"><?php echo esc_html( $form_text_settings['step2_label'] ); ?></span>
				</div>
				<div class="md-step" data-step="3">
					<span class="md-step-number">3</span>
					<span class="md-step-label"><?php echo esc_html( $form_text_settings['step3_label'] ); ?></span>
				</div>
			</div>

			<!-- Step 1: Select Type -->
			<div class="md-form-step active" data-step="1">
				<?php $form_text_settings = md_get_form_text_settings(); ?>
				<h3><?php echo esc_html( $form_text_settings['step1_title'] ); ?></h3>

				<div class="md-type-options">
					<?php if ( $show_veteran ) : ?>
						<label class="md-type-option">
							<input type="radio" name="verification_type" value="veteran">
							<span class="md-type-card">
								<span class="md-type-icon">🎖️</span>
								<span class="md-type-title"><?php echo esc_html( $form_text_settings['veteran_radio_label'] ); ?></span>
								<span class="md-type-desc"><?php echo esc_html( $form_text_settings['veteran_radio_desc'] ); ?></span>
							</span>
						</label>
					<?php endif; ?>

					<?php if ( $show_military ) : ?>
						<label class="md-type-option">
							<input type="radio" name="verification_type" value="military">
							<span class="md-type-card">
								<span class="md-type-icon">🪖</span>
								<span class="md-type-title"><?php echo esc_html( $form_text_settings['military_radio_label'] ); ?></span>
								<span class="md-type-desc"><?php echo esc_html( $form_text_settings['military_radio_desc'] ); ?></span>
							</span>
						</label>
					<?php endif; ?>
				</div>

				<div class="md-form-actions">
					<button type="button" class="button button-primary wp-element-button md-next-step" disabled>
						<?php echo esc_html( md_get_form_text_settings()['button_next'] ); ?>
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
					<button type="button" class="button wp-element-button md-prev-step">
						<?php echo esc_html( md_get_form_text_settings()['button_back'] ); ?>
					</button>
					<button type="button" class="button button-primary wp-element-button md-next-step">
						<?php echo esc_html( md_get_form_text_settings()['button_next'] ); ?>
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
