<?php
/**
 * ReadyLaunch WC4BP Cart Template
 *
 * This template is a Readylaunch-specific version of the WC4BP cart template
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
	<?php do_action( 'wc4bp_before_cart_body' ); ?>

	<?php
	/*
	 * WC4BP routes the checkout endpoint through the cart page as a sub-page
	 * (wc4bp_is_subpage( 'checkout' )), so the cart template intentionally
	 * renders the checkout shortcode here. This dual responsibility mirrors
	 * WC4BP's own cart template and is not a mistake — checkout.php only
	 * applies when checkout is reached as its own component action.
	 */
	if ( function_exists( 'wc4bp_is_subpage' ) && wc4bp_is_subpage( 'checkout' ) ) {
		?>
		<?php echo wp_kses_post( do_shortcode( '[woocommerce_checkout]' ) ); ?>
	<?php } else { ?>
		<?php echo wp_kses_post( do_shortcode( '[woocommerce_cart]' ) ); ?>
	<?php } ?>

	<?php do_action( 'wc4bp_after_cart_body' ); ?>
</div>
<?php

