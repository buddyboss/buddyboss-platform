<?php
/**
 * ReadyLaunch - Member Friend Requests template.
 *
 * This template handles displaying member connection requests.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>

<div class="bb-rl-container-inner bb-rl-profile-container">
	<div class="bb-rl-members-directory-container">
		<div class="screen-content bb-rl-members-directory-content bb-rl-members">
			<h2 class="screen-heading friendship-requests-screen bb-screen-reader-text">
				<?php esc_html_e( 'Requests to Connect', 'buddyboss' ); ?>
			</h2>
			<?php
			bp_nouveau_member_hook( 'before', 'friend_requests_content' );

			if ( bp_has_members( 'type=alphabetical&include=' . bp_get_friendship_requests() ) ) {
				?>

				<ul id="friend-list" class="<?php bp_nouveau_loop_classes(); ?>" data-bp-list="friendship_requests">
					<?php
					while ( bp_members() ) :
						bp_the_member();
						?>

						<li id="friendship-<?php bp_friend_friendship_id(); ?>" <?php bp_member_class( array( 'item-entry' ) ); ?> data-bp-item-id="<?php bp_friend_friendship_id(); ?>" data-bp-item-component="members">
							<div class="list-wrap footer-buttons-on">
								<div class="list-wrap-inner">
									<div class="item-avatar">
										<a href="<?php bp_member_link(); ?>"><?php bp_member_avatar( array( 'type' => 'full' ) ); ?></a>
									</div>
									<div class="item">
										<h2 class="item-title list-title member-name"><a href="<?php bp_member_link(); ?>"><?php bp_member_name(); ?></a></h2>
										<div class="item-meta bb-rl-item-meta-asset"><span class="activity"><?php bp_member_last_active(); ?></span></div>
										<?php bp_nouveau_friend_hook( 'requests_item' ); ?>
									</div>
									<div class="bb-rl-member-buttons-wrap">
										<?php
										add_filter( 'bp_nouveau_get_members_buttons', 'BB_ReadyLaunch::bb_rl_member_profile_buttons', 10, 3 );
										bp_nouveau_members_loop_buttons();
										remove_filter( 'bp_nouveau_get_members_buttons', 'BB_ReadyLaunch::bb_rl_member_profile_buttons', 10, 3 );
										?>
									</div>
								</div>
							</div>
						</li>

					<?php endwhile; ?>
				</ul>

				<?php
				bp_nouveau_friend_hook( 'requests_content' );
				bp_nouveau_pagination( 'bottom' );
			} else {
				bp_nouveau_user_feedback( 'member-requests-none' );
			}

			bp_nouveau_member_hook( 'after', 'friend_requests_content' );
			?>
		</div>
	</div>
</div>
