<?php
/**
 * OTP verification email template (HTML).
 *
 * @package Military_Discounts
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_email_header', $email_heading, $email );
?>

<p>
	<?php
	$customer_name = $user ? $user->display_name : __( 'Customer', 'military-discounts' );
	printf(
		/* translators: %s: customer name */
		esc_html__( 'Hi %s,', 'military-discounts' ),
		esc_html( $customer_name )
	);
	?>
</p>

<p><?php esc_html_e( 'Your military verification code is:', 'military-discounts' ); ?></p>

<h2 style="text-align: center; font-size: 32px; letter-spacing: 8px; background: #f5f5f5; padding: 20px; margin: 20px 0;">
	<?php echo esc_html( $otp_code ); ?>
</h2>

<p>
	<?php
	printf(
		/* translators: %d: expiry time in minutes */
		esc_html__( 'This code will expire in %d minutes.', 'military-discounts' ),
		absint( $otp_expiry )
	);
	?>
</p>

<?php if ( $additional_content ) : ?>
	<p><?php echo wp_kses_post( $additional_content ); ?></p>
<?php endif; ?>

<?php
do_action( 'woocommerce_email_footer', $email );
