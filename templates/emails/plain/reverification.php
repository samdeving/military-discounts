<?php
/**
 * Re-verification email template (plain text).
 *
 * @package Military_Discounts
 */

defined( 'ABSPATH' ) || exit;

$customer_name = $user ? $user->display_name : __( 'Customer', 'military-discounts' );
printf(
	/* translators: %s: customer name */
	esc_html__( 'Hi %s,', 'military-discounts' ),
	esc_html( $customer_name )
);
echo "\n\n";

printf(
	/* translators: %s: verification type (veteran/military) */
	esc_html__( 'Your %s verification has expired.', 'military-discounts' ),
	esc_html( $verification_type )
);
echo "\n\n";

esc_html_e( 'To continue enjoying military discounts, please re-verify your status by visiting:', 'military-discounts' );
echo "\n";
echo esc_url( $reverification_url );
echo "\n\n";

if ( $additional_content ) {
	echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
	echo "\n\n";
}

echo "\n----------------------------------------\n\n";
echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );
