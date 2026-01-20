<?php
/**
 * Coupon Admin class for Military Discounts plugin.
 *
 * Adds verification requirements to WooCommerce coupon settings.
 *
 * @package Military_Discounts
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class MD_Coupon_Admin
 *
 * Extends WooCommerce coupon meta box.
 */
class MD_Coupon_Admin {

	/**
	 * Initialize coupon admin hooks.
	 */
	public function init() {
		add_action( 'woocommerce_coupon_options', array( $this, 'add_coupon_options' ), 10, 2 );
		add_action( 'woocommerce_coupon_options_save', array( $this, 'save_coupon_options' ), 10, 2 );
	}

	/**
	 * Add verification options to coupon settings.
	 *
	 * @param int     $coupon_id Coupon ID.
	 * @param WC_Coupon $coupon    Coupon object.
	 */
	public function add_coupon_options( $coupon_id, $coupon ) {
		echo '<div class="options_group">';
		echo '<p class="form-field"><strong>' . esc_html__( 'Military Discounts', 'military-discounts' ) . '</strong></p>';

		// Requires Veteran Verification checkbox.
		woocommerce_wp_checkbox(
			array(
				'id'          => '_requires_veteran_verification',
				'label'       => __( 'Requires Veteran Verification', 'military-discounts' ),
				'description' => __( 'Only verified veterans can use this coupon.', 'military-discounts' ),
				'value'       => $coupon->get_meta( '_requires_veteran_verification' ) === 'yes' ? 'yes' : 'no',
			)
		);

		// Requires Military Verification checkbox.
		woocommerce_wp_checkbox(
			array(
				'id'          => '_requires_military_verification',
				'label'       => __( 'Requires Military Verification', 'military-discounts' ),
				'description' => __( 'Only verified active military can use this coupon.', 'military-discounts' ),
				'value'       => $coupon->get_meta( '_requires_military_verification' ) === 'yes' ? 'yes' : 'no',
			)
		);

		echo '</div>';
	}

	/**
	 * Save verification options when coupon is saved.
	 *
	 * @param int     $coupon_id Coupon ID.
	 * @param WC_Coupon $coupon    Coupon object.
	 */
	public function save_coupon_options( $coupon_id, $coupon ) {
		// Verify nonce for security (WooCommerce's coupon nonce).
		if ( ! isset( $_POST['woocommerce_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['woocommerce_meta_nonce'] ) ), 'woocommerce_save_data' ) ) {
			return;
		}

		// Save veteran verification requirement.
		$requires_veteran = isset( $_POST['_requires_veteran_verification'] ) ? 'yes' : 'no';
		$coupon->update_meta_data( '_requires_veteran_verification', $requires_veteran );

		// Save military verification requirement.
		$requires_military = isset( $_POST['_requires_military_verification'] ) ? 'yes' : 'no';
		$coupon->update_meta_data( '_requires_military_verification', $requires_military );

		$coupon->save_meta_data();
	}
}
