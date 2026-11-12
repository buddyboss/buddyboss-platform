<?php
/**
 * ReadyLaunch - Groups plugins template.
 *
 * This template provides hooks for group plugins to display their content
 * within the group single page context.
 *
 * @package BuddyBoss\Template
 * @subpackage BBoss_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

bboss_nouveau_group_hook( 'before', 'plugin_template' );
bboss_nouveau_plugin_hook( 'content' );
bboss_nouveau_group_hook( 'after', 'plugin_template' );
