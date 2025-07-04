<?php
/**
 * ReadyLaunch - Groups Requests Loop template.
 *
 * This template displays the loop of membership requests for a group
 * including user avatars, request time, and action buttons.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( bp_group_has_membership_requests( bp_ajax_querystring( 'membership_requests' ) ) ) {
	?>
	<ul id="request-list" class="item-list bp-list membership-requests-list bb-rl-requests-list">
		<?php
		while ( bp_group_membership_requests() ) :
			bp_group_the_membership_request();
			?>
			<li>
				<div class="item-card">
					<div class="item-avatar">
						<?php bp_group_request_user_avatar_thumb(); ?>
					</div>
					<div class="item">
						<div class="item-title">
							<h3><?php bp_group_request_user_link(); ?></h3>
						</div>
						<div class="item-meta">
							<span class="activity"><?php bp_group_request_time_since_requested(); ?></span>
							<?php bp_nouveau_group_hook( '', 'membership_requests_admin_item' ); ?>
						</div>
					</div>
					<?php bp_nouveau_groups_request_buttons(); ?>
				</div>
				<div class="item-comments">
					<?php bp_group_request_comment(); ?>
				</div>
			</li>
		<?php endwhile; ?>
	</ul>

	<?php
	bp_nouveau_pagination( 'bottom' );
} else {
	bp_nouveau_user_feedback( 'group-requests-none' );
}
