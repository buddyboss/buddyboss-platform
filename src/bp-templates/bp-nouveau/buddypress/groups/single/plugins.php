<?php
/**
 * BuddyBoss - Groups plugins
 *
 * This template can be overridden by copying it to yourtheme/buddypress/groups/single/plugins.php.
 *
 * @since   BuddyPress 3.0.0
 * @version 1.0.0
 */

bp_nouveau_group_hook( 'before', 'plugin_template' );

bp_nouveau_plugin_hook( 'content' );

bp_nouveau_group_hook( 'after', 'plugin_template' );
