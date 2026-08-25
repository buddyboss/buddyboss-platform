<?php
/**
 * ReadyLaunch WC4BP Downloads Template
 *
 * This template is a Readylaunch-specific version of the WC4BP downloads template
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
	<?php do_action( 'wc4bp_before_downloads_body' ); ?>
	<?php echo wp_kses_post( do_shortcode( '[downloads]' ) ); ?>
	<?php do_action( 'wc4bp_after_downloads_body' ); ?>
</div>

