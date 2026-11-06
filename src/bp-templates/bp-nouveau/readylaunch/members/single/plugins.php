<?php
/**
 * ReadyLaunch - Member Plugins template.
 *
 * 3rd-party plugins should use this template to easily add template
 * support to their plugins for the members component.
 *
 * @package BuddyBoss\Template
 * @subpackage BBoss_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

bboss_nouveau_member_hook( 'before', 'plugin_template' );

if ( ! bboss_is_current_component_core() ) {
	bboss_get_template_part( 'members/single/parts/item-subnav' );
	bboss_nouveau_member_hook( '', 'plugin_options_nav' );
}

if ( has_action( 'bp_template_title' ) ) {
	?>
	<h2><?php bboss_nouveau_plugin_hook( 'title' ); ?></h2>
	<?php
}

// Check if we're on a WC4BP shop page and load our template directly.
if ( bboss_is_readylaunch_enabled() && class_exists( 'WC4BP_Manager' ) && bboss_is_current_component( WC4BP_Manager::get_shop_slug() ) ) {
	global $bp;
	$current_action = sanitize_file_name( bboss_current_action() );

	// Special handling for orders -> view-order.
	if ( 'orders' === $current_action && ! empty( $bp->action_variables ) ) {
		foreach ( $bp->action_variables as $var ) {
			if ( 'view-order' === $var ) {
				$current_action = 'view-order';
				break;
			}
		}
	}

	if ( ! empty( $current_action ) && preg_match( '/^[a-z][a-z0-9_-]*$/', $current_action ) ) {
		$shop_template_name = 'shop/member/' . $current_action . '.php';

		// Resolve the template directory through the WC4BP helper so the path
		// logic lives in a single place. Load the helper if it isn't already.
		if ( ! class_exists( 'BBoss_Readylaunch_WC4BP_Helper' ) ) {
			require_once bboss()->compatibility_dir . '/class-bb-readylaunch-wc4bp-helper.php';
		}
		$readylaunch_template_path = trailingslashit( BBoss_Readylaunch_WC4BP_Helper::bb_rl_wc4bp_get_template_directory() ) . $shop_template_name;

		if ( file_exists( $readylaunch_template_path ) ) {
			include $readylaunch_template_path;
		} else {
			// Fall back to normal hook system.
			bboss_nouveau_plugin_hook( 'content' );
		}
	} else {
		// Fall back to normal hook system if action is invalid or empty.
		bboss_nouveau_plugin_hook( 'content' );
	}
} else {
	// Not a WC4BP shop page, use normal hook system.
	bboss_nouveau_plugin_hook( 'content' );
}

bboss_nouveau_member_hook( 'after', 'plugin_template' );
