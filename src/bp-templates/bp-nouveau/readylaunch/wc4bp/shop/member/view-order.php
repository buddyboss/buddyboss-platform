<?php
/**
 * ReadyLaunch WC4BP View Order Template
 *
 * This template is a Readylaunch-specific version of the WC4BP view-order template
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch\WC4BP
 * @since BuddyBoss 3.1.0
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Global WP_Post object used by the WooCommerce order template.
global $bp, $wp_query, $post;

// Preserve the original post fields before overriding them for the order view,
// so the global $post state isn't corrupted for hooks, sidebars, or anything
// else that runs after this template.
$bb_rl_original_post_name  = isset( $post->post_name ) ? $post->post_name : '';
$bb_rl_original_post_title = isset( $post->post_title ) ? $post->post_title : '';

$post->post_name     = 'view-order';
$post->post_title    = __( 'Order Details', 'buddyboss-platform' );
$bp_action_variables = $bp->action_variables;
?>

<div class="woocommerce woocommerce--view-order woocommerce--bb-rl">
	<?php
	if ( ! empty( $bp_action_variables ) ) {
		if ( isset( $bp_action_variables[0] ) && ! empty( $bp_action_variables[1] ) && 'view-order' === $bp_action_variables[0] && is_numeric( $bp_action_variables[1] ) ) {
			$order_id = absint( $bp_action_variables[1] );
			woocommerce_account_view_order( $order_id );
		}
	} else {
		printf( '<div class="woocommerce-error">%s</div>', esc_html__( 'Please enter a valid order ID', 'buddyboss-platform' ) );
	}
	?>
</div>

<?php
// Restore the original post fields.
$post->post_name  = $bb_rl_original_post_name;
$post->post_title = $bb_rl_original_post_title;

