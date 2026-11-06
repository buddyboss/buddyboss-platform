<?php
/**
 * The template for members connections requests
 *
 * This template can be overridden by copying it to yourtheme/buddypress/members/single/friends/requests.php.
 *
 * @since   BuddyPress 3.0.0
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>

<h2 class="screen-heading friendship-requests-screen"><?php esc_html_e( 'Requests to Connect', 'buddyboss-platform' ); ?></h2>

<?php
bboss_nouveau_member_hook( 'before', 'friend_requests_content' );

if ( bboss_has_members( 'type=alphabetical&include=' . bboss_get_friendship_requests() ) ) {
	bboss_nouveau_pagination( 'top' );
	?>

	<ul id="friend-list" class="<?php bboss_nouveau_loop_classes(); ?>" data-bp-list="friendship_requests">
		<?php
		while ( bboss_members() ) :
			bboss_the_member();
			?>

			<li id="friendship-<?php bboss_friend_friendship_id(); ?>" <?php bboss_member_class( array( 'item-entry' ) ); ?> data-bp-item-id="<?php bboss_friend_friendship_id(); ?>" data-bp-item-component="members">
				<div class="item-avatar">
					<a href="<?php bboss_member_link(); ?>"><?php bboss_member_avatar( array( 'type' => 'full' ) ); ?></a>
				</div>

				<div class="item">
					<div class="item-title"><a href="<?php bboss_member_link(); ?>"><?php bboss_member_name(); ?></a></div>
					<div class="item-meta"><span class="activity"><?php bboss_member_last_active(); ?></span></div>

					<?php bboss_nouveau_friend_hook( 'requests_item' ); ?>
				</div>
				<?php bboss_nouveau_members_loop_buttons(); ?>
			</li>

		<?php endwhile; ?>
	</ul>

	<?php
	bboss_nouveau_friend_hook( 'requests_content' );
	bboss_nouveau_pagination( 'bottom' );
} else {
	bboss_nouveau_user_feedback( 'member-requests-none' );
}

bboss_nouveau_member_hook( 'after', 'friend_requests_content' );
