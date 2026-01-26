<?php
/**
 * Re-verification email template (HTML).
 *
 * @package Military_Discounts
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_email_header', $email_heading, $email );
?>

<p>
	<?php
	$customer_name = $user ? esc_html( $user->display_name ) : esc_html__( 'Customer', 'military-discounts' );
	printf(
		/* translators: %s: customer name */
		esc_html__( 'Hi %s,', 'military-discounts' ),
		$customer_name
	);
	?>
</p>

<p>
	<?php
	printf(
		/* translators: %s: verification type (veteran/military) */
		esc_html__( 'Your %s verification has expired.', 'military-discounts' ),
		esc_html( $verification_type )
	);
	?>
</p>

<p><?php esc_html_e( 'To continue enjoying military discounts, please re-verify your status by clicking the button below:', 'military-discounts' ); ?></p>

<p style="text-align: center; margin: 30px 0;">
	<a href="<?php echo esc_url( $reverification_url ); ?>" style="background-color: #7f54b3; color: #ffffff; padding: 12px 30px; text-decoration: none; border-radius: 4px; display: inline-block;">
		<?php esc_html_e( 'Re-verify Now', 'military-discounts' ); ?>
	</a>
</p>

<?php if ( $additional_content ) : ?>
	<p><?php echo wp_kses_post( $additional_content ); ?></p>
<?php endif; ?>

<?php
do_action( 'woocommerce_email_footer', $email );
