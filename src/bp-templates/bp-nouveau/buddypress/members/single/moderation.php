<?php
/**
 * BuddyBoss - Users Groups
 *
 * @since   BuddyBoss 1.5.4
 * @version 1.5.4
 */
?>

<?php if ( bp_is_my_profile() ) : ?>
	<?php bp_get_template_part( 'members/single/parts/item-subnav' ); ?>
<?php endif; ?>

<?php
switch ( bp_current_action() ) :

	// Home/My Groups
	case 'reported-content':
		bp_nouveau_member_hook( 'before', 'moderation_content' );
		?>
        <div class="moderation" data-bp-list="moderation">
            <div id="bp-ajax-loader">
				<?php
				bp_nouveau_user_feedback( 'moderation-requests-loading' );
				?>
            </div>
        </div>
		<?php
		bp_nouveau_member_hook( 'after', 'moderation_content' );
		break;
	// Group Invitations
	case 'invites':
		bp_get_template_part( 'members/single/groups/invites' );
		break;
	// Any other
	default:
		bp_get_template_part( 'members/single/plugins' );
		break;
endswitch;
