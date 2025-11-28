<?php
/**
 * ReadyLaunch WC4BP Edit Account Template
 *
 * This template is a Readylaunch-specific version of the WC4BP edit-account template
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
	<?php do_action( 'wc4bp_before_edit_account_body' ); ?>
	<?php echo do_shortcode( '[edit-account]' ); ?>
	<?php do_action( 'wc4bp_after_edit_account_body' ); ?>
</div>

