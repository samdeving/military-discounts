<?php
/**
 * Lockout email plain text template.
 *
 * @package Military_Discounts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

echo '= ' . esc_html( $email_heading ) . " =\n\n";

echo sprintf(
	/* translators: %s: user display name */
	esc_html__( 'Hi %s,', 'military-discounts' ),
	esc_html( $user->display_name )
) . "\n\n";

$type_label = 'veteran' === $verification_type ? esc_html__( 'veteran', 'military-discounts' ) : esc_html__( 'active military', 'military-discounts' );

echo sprintf(
	/* translators: %s: verification type (veteran or active military) */
	esc_html__( 'Your %s verification has been temporarily locked due to too many failed attempts.', 'military-discounts' ),
	esc_html( $type_label )
) . "\n\n";

echo sprintf(
	/* translators: %d: lockout duration in minutes */
	esc_html__( 'The lockout will expire in %d minutes. Please try again after this time period.', 'military-discounts' ),
	esc_html( $lockout_duration )
) . "\n\n";

echo esc_html__( 'If you believe this is an error or need assistance, please contact our support team.', 'military-discounts' ) . "\n\n";

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo esc_html( apply_filters( 'woocommerce_email_footer_text', get_bloginfo( 'name', 'display' ) ) );
