<?php
/**
 * ReadyLaunch WC4BP Cart Template
 *
 * This template is a Readylaunch-specific version of the WC4BP cart template
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
	<?php do_action( 'wc4bp_before_cart_body' ); ?>

	<?php if ( function_exists( 'wc4bp_is_subpage' ) && wc4bp_is_subpage( 'checkout' ) ) { ?>
		<?php echo do_shortcode( '[woocommerce_checkout]' ); ?>
	<?php } else { ?>
		<?php echo do_shortcode( '[woocommerce_cart]' ); ?>
	<?php } ?>

	<?php do_action( 'wc4bp_after_cart_body' ); ?>
</div>
<?php

