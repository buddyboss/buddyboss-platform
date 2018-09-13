<?php
/**
 * BuddyBoss - Members Mutual Connections
 *
 * @since BuddyBoss 3.1.1
 * @version 3.1.1
 */
?>

	<h2 class="screen-heading friendship-mutual-friends-screen"><?php esc_html_e( 'Mutual Connections', 'buddyboss' ); ?></h2>

<?php bp_nouveau_member_hook( 'before', 'friend_mutual_friends_content' ); ?>

<?php if ( bp_has_members( 'type=alphabetical&include=' . bp_get_mutual_friendships() ) ) : ?>

	<?php bp_nouveau_pagination( 'top' ); ?>

	<ul id="friend-list" class="<?php bp_nouveau_loop_classes(); ?>" data-bp-list="mutual_friendship">
		<?php
		while ( bp_members() ) :
			bp_the_member();
			?>

			<li id="friendship-<?php bp_friend_friendship_id(); ?>" <?php bp_member_class( array( 'item-entry' ) ); ?> data-bp-item-id="<?php bp_friend_friendship_id(); ?>" data-bp-item-component="members">
				<div class="item-avatar">
					<a href="<?php bp_member_link(); ?>"><?php bp_member_avatar( array( 'type' => 'full' ) ); ?></a>
				</div>

				<div class="item">
					<div class="item-title"><a href="<?php bp_member_link(); ?>"><?php bp_member_name(); ?></a></div>
					<div class="item-meta"><span class="activity"><?php bp_member_last_active(); ?></span></div>

					<?php bp_nouveau_friend_hook( 'mutual_friends_item' ); ?>
				</div>

				<?php bp_nouveau_members_loop_buttons(); ?>
			</li>

		<?php endwhile; ?>
	</ul>

	<?php bp_nouveau_friend_hook( 'mutual_friends_content' ); ?>

	<?php bp_nouveau_pagination( 'bottom' ); ?>

<?php else : ?>

	<?php bp_nouveau_user_feedback( 'member-mutual-friends-none' ); ?>

<?php endif; ?>

<?php
bp_nouveau_member_hook( 'after', 'friend_mutual_friends_content' );
