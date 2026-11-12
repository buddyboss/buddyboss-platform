<?php
/**
 * BuddyBoss - Groups Requests Loop
 *
 * This template can be overridden by copying it to yourtheme/buddypress/groups/single/requests-loop.php.
 *
 * @since   BuddyPress 3.0.0
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( bboss_group_has_membership_requests( bboss_ajax_querystring( 'membership_requests' ) ) ) {
	?>
	<h2 class="bp-screen-title">
		<?php esc_html_e( 'Manage Membership Requests', 'buddyboss' ); ?>
	</h2>

	<?php bboss_nouveau_pagination( 'top' ); ?>

	<ul id="request-list" class="item-list bp-list membership-requests-list">
		<?php
		while ( bboss_group_membership_requests() ) :
			bboss_group_the_membership_request();
			?>
			<li>
				<div class="item-avatar">
					<?php bboss_group_request_user_avatar_thumb(); ?>
				</div>
				<div class="item">
					<div class="item-title">
						<h3><?php bboss_group_request_user_link(); ?></h3>
					</div>
					<div class="item-meta">
						<span class="comments"><?php bboss_group_request_comment(); ?></span>
						<span class="activity"><?php bboss_group_request_time_since_requested(); ?></span>
						<?php bboss_nouveau_group_hook( '', 'membership_requests_admin_item' ); ?>
					</div>
				</div>
				<?php bboss_nouveau_groups_request_buttons(); ?>
			</li>
		<?php endwhile; ?>
	</ul>

	<?php
	bboss_nouveau_pagination( 'bottom' );
} else {
	bboss_nouveau_user_feedback( 'group-requests-none' );
}
