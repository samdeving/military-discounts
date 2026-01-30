<?php
/**
 * Verification Approved email template (HTML)
 *
 * @package Military_Discounts
 */

defined( 'ABSPATH' ) || exit;

/*
 * @hooked WC_Emails::email_header() Output the email header
 */
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

<p>
	<?php
	printf(
		/* translators: %s: verification type */
		esc_html__( 'Great news! Your %s verification has been approved.', 'military-discounts' ),
		esc_html( $verification_type )
	);
	?>
</p>

<p>
	<?php esc_html_e( 'You can now enjoy exclusive military discounts when shopping on our store. These discounts will be automatically applied to eligible products and coupons.', 'military-discounts' ); ?>
</p>

<p style="margin: 30px 0; text-align: center;">
	<a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" 
	   style="background-color: #3C763D; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 4px; display: inline-block;">
		<?php esc_html_e( 'Start Shopping', 'military-discounts' ); ?>
	</a>
</p>

<?php if ( $additional_content ) : ?>
	<p><?php echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) ); ?></p>
<?php endif; ?>

<p>
	<?php esc_html_e( 'Thank you for your service!', 'military-discounts' ); ?>
</p>

<?php
/*
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action( 'woocommerce_email_footer', $email );
