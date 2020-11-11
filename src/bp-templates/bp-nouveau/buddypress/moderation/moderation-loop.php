<?php
/**
 * BuddyBoss - Moderation Loop
 *
 * @since   BuddyBoss 2.0.0
 * @package BuddyBoss\Core
 */

bp_nouveau_before_loop();
$template = 'moderation/reported-content-loop';
if ( 'blocked-members' === bp_current_action() ) {
	$template = 'moderation/blocked-members-loop';
}
if ( bp_has_moderation( bp_ajax_querystring( 'moderation' ) ) ) :
	bp_get_template_part( $template );
else :
	bp_nouveau_user_feedback( 'moderation-requests-none' );
endif;
?>
<?php
bp_nouveau_after_loop();
