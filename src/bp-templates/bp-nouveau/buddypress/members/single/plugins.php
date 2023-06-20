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

bp_nouveau_member_hook( 'before', 'plugin_template' ); ?>

<?php if ( ! bp_is_current_component_core() ) : ?>

	<?php bp_get_template_part( 'members/single/parts/item-subnav' ); ?>

	<?php bp_nouveau_member_hook( '', 'plugin_options_nav' ); ?>

<?php endif; ?>

<?php if ( has_action( 'bp_template_title' ) ) : ?>

	<h2><?php bp_nouveau_plugin_hook( 'title' ); ?></h2>

<?php endif; ?>

<?php
bp_nouveau_plugin_hook( 'content' );

bp_nouveau_member_hook( 'after', 'plugin_template' );
