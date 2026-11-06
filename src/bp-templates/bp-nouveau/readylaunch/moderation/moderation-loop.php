<?php
/**
 * ReadyLaunch - Moderation Loop template.
 *
 * This template handles displaying the moderation loop.
 *
 * @package BuddyBoss\Template
 * @subpackage BBoss_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
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
