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

bp_nouveau_plugin_hook( 'content' );

bp_nouveau_member_hook( 'after', 'plugin_template' );
