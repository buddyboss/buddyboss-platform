<?php
/**
 * BuddyBoss - Users Moderation
 *
 * @since BuddyBoss 1.5.6
 * @package BuddyBoss
 * @version 2.0.0
 */


switch ( bp_current_action() ) :
	case 'blocked-members':
		bp_nouveau_member_hook( 'before', 'moderation_content' );
		?>
        <div class="moderation" data-bp-list="moderation">
            <div id="bp-ajax-loader">
				<?php
				bp_nouveau_user_feedback( 'moderation-block-member-loading' );
				?>
            </div>
        </div>
		<?php
		bp_nouveau_member_hook( 'after', 'moderation_content' );
		break;
endswitch;
