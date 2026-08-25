<?php
/**
 * ReadyLaunch WC4BP Checkout Template
 *
 * This template is a Readylaunch-specific version of the WC4BP checkout template
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch\WC4BP
 * @since BuddyBoss 3.1.0
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="woocommerce woocommerce--bb-rl">
	<?php do_action( 'wc4bp_before_checkout_body' ); ?>

	<?php echo wp_kses_post( do_shortcode( '[woocommerce_checkout]' ) ); ?>

	<?php do_action( 'wc4bp_after_checkout_body' ); ?>
</div>

