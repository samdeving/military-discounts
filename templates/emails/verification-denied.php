<?php
/**
 * Verification Denied email template (HTML)
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
		/* translators: %s: verification type */
		esc_html__( 'We were unable to verify your %s status at this time.', 'military-discounts' ),
		esc_html( $verification_type )
	);
	?>
</p>

<?php if ( ! empty( $denial_reason ) ) : ?>
<p>
	<strong><?php esc_html_e( 'Reason:', 'military-discounts' ); ?></strong>
	<?php echo esc_html( $denial_reason ); ?>
</p>
<?php endif; ?>

<p>
	<?php esc_html_e( 'This could happen for several reasons:', 'military-discounts' ); ?>
</p>

<ul>
	<li><?php esc_html_e( 'The information provided does not match VA records', 'military-discounts' ); ?></li>
	<li><?php esc_html_e( 'There may be a typo in the information submitted', 'military-discounts' ); ?></li>
	<li><?php esc_html_e( 'Your records may need to be updated with the VA', 'military-discounts' ); ?></li>
</ul>

<p>
	<?php esc_html_e( 'You can try verifying again with updated information, or contact us if you need assistance.', 'military-discounts' ); ?>
</p>

<p style="margin: 30px 0; text-align: center;">
	<a href="<?php echo esc_url( wc_get_account_endpoint_url( 'military-discounts' ) ); ?>" 
	   style="background-color: #337AB7; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 4px; display: inline-block;">
		<?php esc_html_e( 'Try Again', 'military-discounts' ); ?>
	</a>
</p>

<?php if ( $additional_content ) : ?>
	<p><?php echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) ); ?></p>
<?php endif; ?>

<?php
/*
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action( 'woocommerce_email_footer', $email );
