<?php
/**
 * BuddyBoss - Users Moderation
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
