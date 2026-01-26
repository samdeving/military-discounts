<?php
/**
 * Verification Approved email template (Plain text)
 *
 * @package Military_Discounts
 */

defined( 'ABSPATH' ) || exit;

echo "= " . esc_html( $email_heading ) . " =\n\n";

$customer_name = $user ? esc_html( $user->display_name ) : esc_html__( 'Customer', 'military-discounts' );
printf(
	/* translators: %s: customer name */
	esc_html__( 'Hi %s,', 'military-discounts' ),
	$customer_name
);
echo "\n\n";

printf(
	/* translators: %s: verification type */
	esc_html__( 'Great news! Your %s verification has been approved.', 'military-discounts' ),
	esc_html( $verification_type )
);
echo "\n\n";

echo esc_html__( 'You can now enjoy exclusive military discounts when shopping on our store. These discounts will be automatically applied to eligible products and coupons.', 'military-discounts' );
echo "\n\n";

echo esc_html__( 'Start shopping: ', 'military-discounts' ) . esc_url( wc_get_page_permalink( 'shop' ) );
echo "\n\n";

if ( $additional_content ) {
	echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
	echo "\n\n";
}

echo esc_html__( 'Thank you for your service!', 'military-discounts' );
echo "\n\n";

echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );
