<?php
/**
 * Lockout email plain text template.
 *
 * @package Military_Discounts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

echo "= " . $email_heading . " =\n\n";

echo sprintf( __( 'Hi %s,', 'military-discounts' ), $user->display_name ) . "\n\n";

$type_label = 'veteran' === $verification_type ? __( 'veteran', 'military-discounts' ) : __( 'active military', 'military-discounts' );
echo sprintf(
	__( 'Your %s verification has been temporarily locked due to too many failed attempts.', 'military-discounts' ),
	$type_label
) . "\n\n";

echo sprintf(
	__( 'The lockout will expire in %d minutes. Please try again after this time period.', 'military-discounts' ),
	$lockout_duration
) . "\n\n";

echo __( 'If you believe this is an error or need assistance, please contact our support team.', 'military-discounts' ) . "\n\n";

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo apply_filters( 'woocommerce_email_footer_text', get_bloginfo( 'name', 'display' ) );
