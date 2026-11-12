<?php
/**
 * BuddyBoss - Moderation Loop
 *
 * The template for displaying the moderation loop.
 *
 * This template can be overridden by copying it to yourtheme/buddypress/moderation/moderation-loop.php.
 *
 * @since   BuddyBoss 1.5.6
 * @package BuddyBoss\Core
 * @version 1.5.6
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

bboss_nouveau_before_loop();

if ( bboss_has_moderation( bboss_ajax_querystring( 'moderation' ) ) ) :
	bboss_get_template_part( 'moderation/blocked-members-loop' );
else :
	bboss_nouveau_user_feedback( 'moderation-requests-none' );
endif;

bboss_nouveau_after_loop();
