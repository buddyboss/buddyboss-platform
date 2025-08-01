<?php
/**
 * ReadyLaunch - Member Notifications Loop template.
 *
 * This template handles displaying member notifications in a loop.
 *
 * @since      BuddyBoss 2.9.00
 * @subpackage BP_Nouveau\ReadyLaunch
 * @package    BuddyBoss\Template
 * @version    1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( bp_has_notifications( bp_ajax_querystring( 'notifications' ) ) ) {

	$is_first_page = empty( $_POST['page'] ) || 1 === (int) $_POST['page']; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	if ( $is_first_page ) {
		?>
	<form action="" method="post" id="notifications-bulk-management" class="standard-form">
		<ul class="notification-list bb-nouveau-list bs-item-list list-view bb-rl-list">
		<?php
	}
	$notifications_template    = buddypress()->notifications->query_loop;
	$total_notification_count  = $notifications_template->total_notification_count;
	$current_page              = $notifications_template->pag_page;
	$notifications_per_page    = $notifications_template->pag_num;
	$notifications_page_arg    = $notifications_template->pag_arg;
	$notifications_total_pages = ceil( $total_notification_count / $notifications_per_page );

	while ( bp_the_notifications() ) :
		bp_the_notification();
		$bp                     = buddypress();
		$bp_the_notification_id = bp_get_the_notification_id();
		$readonly               = isset( $bp->notifications->query_loop->notification->readonly )
			? $bp->notifications->query_loop->notification->readonly
			: false;
		?>
		<li class="bs-item-wrap">
			<div class="bulk-select-check">
				<span class="bb-input-wrap">
					<input id="<?php echo esc_attr( $bp_the_notification_id ); ?>"
						type="checkbox"
						name="notifications[]"
						value="<?php echo esc_attr( $bp_the_notification_id ); ?>"
						class="notification-check bs-styled-checkbox"
						data-readonly="<?php echo esc_attr( $readonly ); ?>"/>
					<label for="<?php echo esc_attr( $bp_the_notification_id ); ?>"></label>
				</span>
			</div>
			<div class="notification-avatar">
				<?php bb_notification_avatar(); ?>
			</div>
			<div class="notification-content">
				<span><?php bp_the_notification_description(); ?></span>
				<span class="posted"><?php bp_the_notification_time_since(); ?></span>
			</div>
			<div class="actions">
				<div class="bb-rl-more_dropdown-wrap">
					<a href="#"
						class="bb_rl_more_dropdown__action"
						data-balloon-pos="up"
						data-balloon="More actions">
						<i class="bb-icons-rl-dots-three"></i>
					</a>
					<div class="bb_rl_more_dropdown">
						<?php bp_the_notification_action_links(); ?>
					</div>
				</div>
			</div>
		</li>
		<?php
	endwhile;
	if ( $notifications_total_pages > $current_page ) {
		$notifications_url = bp_get_notifications_permalink();
		$next_page         = $current_page + 1;
		$next_page_url     = add_query_arg(
			$notifications_page_arg,
			$next_page,
			$notifications_url
		);
		?>
		<li class="bb-rl-view-more bb-rl-view-more--pagination" data-bp-pagination="<?php echo esc_attr( $notifications_page_arg ); ?>">
			<a class="bb-rl-button bb-rl-button--secondaryFill bb-rl-button--small" href="<?php echo esc_url( $next_page_url ); ?>" data-method="append">
				<?php
				printf(
					/* translators: %d: number of remaining notifications */
					esc_html( _n( 'View More (%d)', 'View More (%d)', $total_notification_count - ( $current_page * $notifications_per_page ), 'buddyboss' ) ),
					esc_html( number_format_i18n( $total_notification_count - ( $current_page * $notifications_per_page ) ) )
				);
				?>
				<i class="bb-icons-rl-caret-down"></i>
			</a>
		</li>
		<?php
	}
	if ( $is_first_page ) {
		?>
		</ul>
	</form>
		<?php
	}
} else {
	bp_nouveau_user_feedback( 'member-notifications-none' );
}
