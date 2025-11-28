<?php
/**
 * ReadyLaunch WC4BP Track Order Template
 *
 * This template is a Readylaunch-specific version of the WC4BP track template
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch\WC4BP
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="woocommerce woocommerce--bb-rl">
	<?php do_action( 'wc4bp_before_track_body' ); ?>

	<h3><?php esc_html_e( 'Track your order', 'wc4bp' ); ?></h3>

	<?php do_action( 'wc4bp_after_track_heading' ); ?>

	<?php echo do_shortcode( '[woocommerce_order_tracking]' ); ?>

	<?php do_action( 'wc4bp_after_track_body' ); ?>
</div>

