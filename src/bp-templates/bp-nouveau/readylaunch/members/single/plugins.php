<?php
/**
 * ReadyLaunch - Member Plugins template.
 *
 * 3rd-party plugins should use this template to easily add template
 * support to their plugins for the members component.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

bp_nouveau_member_hook( 'before', 'plugin_template' );

if ( ! bp_is_current_component_core() ) {
	bp_get_template_part( 'members/single/parts/item-subnav' );
	bp_nouveau_member_hook( '', 'plugin_options_nav' );
}

if ( has_action( 'bp_template_title' ) ) {
	?>
	<h2><?php bp_nouveau_plugin_hook( 'title' ); ?></h2>
	<?php
}

// Check if we're on a WC4BP shop page and load our template directly
if ( bb_is_readylaunch_enabled() && class_exists( 'WC4BP_Manager' ) && bp_is_current_component( wc4bp_Manager::get_shop_slug() ) ) {
	$current_action = bp_current_action();
	if ( ! empty( $current_action ) ) {
		$shop_template_name = 'shop/member/' . $current_action . '.php';
		$plugin_dir = buddypress()->plugin_dir;
		$plugin_dir = rtrim( $plugin_dir, '/' ) . '/';

		if ( false !== strpos( $plugin_dir, '/src/' ) ) {
			$readylaunch_template_path = $plugin_dir . 'bp-templates/bp-nouveau/readylaunch/wc4bp/' . $shop_template_name;
		} else {
			$readylaunch_template_path = $plugin_dir . 'src/bp-templates/bp-nouveau/readylaunch/wc4bp/' . $shop_template_name;
		}

		if ( file_exists( $readylaunch_template_path ) ) {
			include $readylaunch_template_path;
		} else {
			// Fall back to normal hook system
			bp_nouveau_plugin_hook( 'content' );
		}
	} else {
		// Fall back to normal hook system
		bp_nouveau_plugin_hook( 'content' );
	}
} else {
	// Not a WC4BP shop page, use normal hook system
	bp_nouveau_plugin_hook( 'content' );
}

bp_nouveau_member_hook( 'after', 'plugin_template' );
