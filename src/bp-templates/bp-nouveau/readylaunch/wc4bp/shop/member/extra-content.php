<?php
/**
 * ReadyLaunch WC4BP Extra Content Template
 *
 * This template is a Readylaunch-specific version of the WC4BP extra-content template
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
	<?php do_action( 'wc4bp_before_extra_content_body' ); ?>
	<div id="wc4bp-hidden-content" style="display:none;"></div>
	<div id="extra-content-tab"></div>
	<div id="extra-content-complement" style="display:none;"></div>
	<?php do_action( 'wc4bp_after_extra_content_body' ); ?>
</div>

