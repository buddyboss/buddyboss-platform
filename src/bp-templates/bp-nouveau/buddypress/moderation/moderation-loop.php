<?php
/**
 * BuddyBoss - Moderation Loop
 *
 * @package BuddyBoss\Core
 * @since   BuddyBoss 1.5.4
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
