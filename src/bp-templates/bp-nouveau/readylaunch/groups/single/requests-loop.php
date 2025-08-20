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
	$is_first_page = empty( $_POST['page'] ) || 1 === (int) $_POST['page']; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	if ( $is_first_page ) {
		?>
	<ul id="request-list" class="item-list bp-list membership-requests-list bb-rl-requests-list bb-rl-list">
		<?php
	}
	?>

		<?php
		global $requests_template;
		$total_request_count   = $requests_template->total_request_count;
		$requests_current_page = $requests_template->pag_page;
		$requests_per_page     = $requests_template->pag_num;
		$requests_page_arg     = $requests_template->pag_arg;
		$requests_total_pages  = ceil( $total_request_count / $requests_per_page );

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
		<?php
		if ( $requests_total_pages > $requests_current_page ) {
			$requests_url  = bp_get_groups_action_link( 'admin', 'membership-requests' );
			$next_page     = $requests_current_page + 1;
			$next_page_url = add_query_arg(
				$requests_page_arg,
				$next_page,
				$requests_url
			);
			?>
			<li class="bb-rl-view-more bb-rl-view-more--pagination" data-bp-pagination="<?php echo esc_attr( $requests_page_arg ); ?>">
				<a class="bb-rl-button bb-rl-button--secondaryFill bb-rl-button--small" href="<?php echo esc_url( $next_page_url ); ?>" data-method="append">
					<?php esc_html_e( 'Show More', 'buddyboss' ); ?>
					<i class="bb-icons-rl-caret-down"></i>
				</a>
			</li>
			<?php
		}
		?>

	<?php
	if ( $is_first_page ) {
		?>
		</ul>
		<?php
	}
} else {
	bp_nouveau_user_feedback( 'group-requests-none' );
}
