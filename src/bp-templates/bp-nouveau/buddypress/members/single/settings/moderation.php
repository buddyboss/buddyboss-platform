<?php
/**
 * BuddyBoss - Users Moderation
 *
 * @since BuddyBoss 2.0.0
 * @package BuddyBoss
 * @version 2.0.0
 */


switch ( bp_current_action() ) :

	case 'reported-content':
		bp_nouveau_member_hook( 'before', 'moderation_content' );
		?>
        <div class="moderation" data-bp-list="moderation">
            <div id="bp-ajax-loader">
				<?php
				bp_nouveau_user_feedback( 'moderation-reported-content-loading' );
				?>
            </div>
        </div>
		<?php
		bp_nouveau_member_hook( 'after', 'moderation_content' );
		break;
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
