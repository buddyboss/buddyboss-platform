<?php
/**
 * ReadyLaunch - Moderation Loop template.
 *
 * This template handles displaying the moderation loop.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

bp_nouveau_before_loop();

if ( bp_has_moderation( bp_ajax_querystring( 'moderation' ) ) ) :
	bp_get_template_part( 'moderation/blocked-members-loop' );
else :
	bp_nouveau_user_feedback( 'moderation-requests-none' );
endif;

bp_nouveau_after_loop();
