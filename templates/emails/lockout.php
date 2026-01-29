<?php
/**
 * Lockout email template.
 *
 * @package Military_Discounts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

?>

<?php do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<p><?php printf( esc_html__( 'Hi %s,', 'military-discounts' ), esc_html( $user->display_name ) ); ?></p>

<p><?php 
	$type_label = 'veteran' === $verification_type ? __( 'veteran', 'military-discounts' ) : __( 'active military', 'military-discounts' );
	printf(
		esc_html__( 'Your %s verification has been temporarily locked due to too many failed attempts.', 'military-discounts' ),
		$type_label
	);
?></p>

<p><?php 
	printf(
		esc_html__( 'The lockout will expire in %d minutes. Please try again after this time period.', 'military-discounts' ),
		$lockout_duration
	);
?></p>

<p><?php esc_html_e( 'If you believe this is an error or need assistance, please contact our support team.', 'military-discounts' ); ?></p>

<?php do_action( 'woocommerce_email_footer', $email ); ?>
