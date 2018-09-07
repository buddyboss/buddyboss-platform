<?php
/**
 * BuddyBoss - Members Connections Following
 *
 * @since BuddyPress 3.1.1
 * @version 3.0.0
 */
?>

    <h2 class="screen-heading friendship-following-screen"><?php esc_html_e( 'Following', 'buddyboss' ); ?></h2>

<?php bp_nouveau_member_hook( 'before', 'friend_following_content' ); ?>

<?php if ( bp_has_members( 'type=alphabetical&include=' . implode( ',', (array) bp_follow_get_following() ) ) ) : ?>

	<?php bp_nouveau_pagination( 'top' ); ?>

    <ul id="following-list" class="<?php bp_nouveau_loop_classes(); ?>" data-bp-list="following">
		<?php
		while ( bp_members() ) :
			bp_the_member();
			?>

            <li id="follow-<?php bp_member_user_id(); ?>" <?php bp_member_class( array( 'item-entry' ) ); ?> data-bp-item-id="<?php bp_member_user_id(); ?>" data-bp-item-component="members">
                <div class="item-avatar">
                    <a href="<?php bp_member_link(); ?>"><?php bp_member_avatar( array( 'type' => 'full' ) ); ?></a>
                </div>

                <div class="item">
                    <div class="item-title"><a href="<?php bp_member_link(); ?>"><?php bp_member_name(); ?></a></div>
                    <div class="item-meta"><span class="activity"><?php bp_member_last_active(); ?></span></div>

					<?php bp_nouveau_friend_hook( 'following_item' ); ?>
                </div>

				<?php bp_nouveau_members_loop_buttons(); ?>
            </li>

		<?php endwhile; ?>
    </ul>

	<?php bp_nouveau_friend_hook( 'following_content' ); ?>

	<?php bp_nouveau_pagination( 'bottom' ); ?>

<?php else : ?>

	<?php bp_nouveau_user_feedback( 'member-following-none' ); ?>

<?php endif; ?>

<?php
bp_nouveau_member_hook( 'after', 'friend_following_content' );
