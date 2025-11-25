<?php
/**
 * ReadyLaunch WC4BP View Order Template
 *
 * This template is a Readylaunch-specific version of the WC4BP view-order template
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch\WC4BP
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @var WP_Post $post */
global $bp, $wp_query, $post;
$post->post_name     = 'view-order';
$post->post_title    = __( 'Order Details', 'wc4bp' );
$bp_action_variables = $bp->action_variables;
?>

<div class="woocommerce woocommerce--view-order">
	<?php
	if ( ! empty( $bp_action_variables ) ) {
		if ( isset( $bp_action_variables[0] ) && ! empty( $bp_action_variables[1] ) && 'view-order' === $bp_action_variables[0] && is_numeric( $bp_action_variables[1] ) ) {
			$order_id = absint( $bp_action_variables[1] );
			woocommerce_account_view_order( $order_id );
		}
	} else {
		echo esc_attr( sprintf( '<div class="woocommerce-error">%s</div>', __( 'Please enter a valid order ID', 'wc4bp' ) ) );
	}
	?>
</div>

