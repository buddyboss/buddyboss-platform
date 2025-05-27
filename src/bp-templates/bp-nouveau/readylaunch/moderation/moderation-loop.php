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

bp_nouveau_before_loop();

if ( bp_has_moderation( bp_ajax_querystring( 'moderation' ) ) ) :
	bp_get_template_part( 'moderation/blocked-members-loop' );
else :
	bp_nouveau_user_feedback( 'moderation-requests-none' );
endif;

bp_nouveau_after_loop();
