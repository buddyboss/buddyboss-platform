<?php
/**
 * ReadyLaunch WC4BP Orders Template
 *
 * This template is a Readylaunch-specific version of the WC4BP orders template
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
<div class="woocommerce">
	<?php do_action( 'wc4bp_before_orders_body' ); ?>
	<?php echo do_shortcode( '[orders]' ); ?>
	<?php do_action( 'wc4bp_after_orders_body' ); ?>
</div>

