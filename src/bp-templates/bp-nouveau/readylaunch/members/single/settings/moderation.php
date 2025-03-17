<?php
/**
 * The template for user moderation
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @version 1.0.0
 */

$is_send_ajax_request = bb_is_send_ajax_request();

switch ( bp_current_action() ) :
	case 'blocked-members':
		bp_nouveau_member_hook( 'before', 'moderation_content' );
		?>
		<div class="moderation" data-bp-list="moderation">
			<?php
			if ( $is_send_ajax_request ) {
				echo '<div id="bp-ajax-loader">';
				bp_nouveau_user_feedback( 'moderation-block-member-loading' );
				echo '</div>';
			} else {
				bp_get_template_part( 'moderation/moderation-loop' );
			}
			?>
		</div>
		<?php
		bp_nouveau_member_hook( 'after', 'moderation_content' );
		break;
endswitch;
