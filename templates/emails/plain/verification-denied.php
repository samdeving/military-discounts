<?php
/**
 * Verification Denied email template (Plain text)
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
	esc_html__( 'We were unable to verify your %s status at this time.', 'military-discounts' ),
	esc_html( $verification_type )
);
echo "\n\n";

if ( ! empty( $denial_reason ) ) {
	echo esc_html__( 'Reason:', 'military-discounts' ) . ' ' . esc_html( $denial_reason );
	echo "\n\n";
}

echo esc_html__( 'This could happen for several reasons:', 'military-discounts' );
echo "\n";
echo "- " . esc_html__( 'The information provided does not match VA records', 'military-discounts' ) . "\n";
echo "- " . esc_html__( 'There may be a typo in the information submitted', 'military-discounts' ) . "\n";
echo "- " . esc_html__( 'Your records may need to be updated with the VA', 'military-discounts' ) . "\n";
echo "\n";

echo esc_html__( 'You can try verifying again with updated information, or contact us if you need assistance.', 'military-discounts' );
echo "\n\n";

echo esc_html__( 'Try again: ', 'military-discounts' ) . esc_url( wc_get_account_endpoint_url( 'military-discounts' ) );
echo "\n\n";

if ( $additional_content ) {
	echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
	echo "\n\n";
}

echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );
