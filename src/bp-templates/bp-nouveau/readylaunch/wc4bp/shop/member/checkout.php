<?php
/**
 * ReadyLaunch WC4BP Checkout Template
 *
 * This template is a Readylaunch-specific version of the WC4BP checkout template
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
	<?php do_action( 'wc4bp_before_checkout_body' ); ?>

	<?php echo do_shortcode( '[woocommerce_checkout]' ); ?>

	<?php do_action( 'wc4bp_after_checkout_body' ); ?>
</div>

