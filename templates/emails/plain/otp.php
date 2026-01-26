<?php
/**
 * OTP verification email template (plain text).
 *
 * @package Military_Discounts
 */

defined( 'ABSPATH' ) || exit;

$customer_name = $user ? esc_html( $user->display_name ) : esc_html__( 'Customer', 'military-discounts' );
printf(
	/* translators: %s: customer name */
	esc_html__( 'Hi %s,', 'military-discounts' ),
	$customer_name
);
echo "\n\n";

esc_html_e( 'Your military verification code is:', 'military-discounts' );
echo "\n\n";

echo esc_html( $otp_code );
echo "\n\n";

printf(
	/* translators: %d: expiry time in minutes */
	esc_html__( 'This code will expire in %d minutes.', 'military-discounts' ),
	absint( $otp_expiry )
);
echo "\n\n";

if ( $additional_content ) {
	echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
	echo "\n\n";
}

echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );
