<?php
/**
 * The template for users plugins template
 *
 * 3rd-party plugins should use this template to easily add template
 * support to their plugins for the members component.
 *
 * This template can be overridden by copying it to yourtheme/buddypress/members/single/plugins.php.
 *
 * @since   BuddyPress 3.0.0
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

bboss_nouveau_plugin_hook( 'content' );

bboss_nouveau_member_hook( 'after', 'plugin_template' );
