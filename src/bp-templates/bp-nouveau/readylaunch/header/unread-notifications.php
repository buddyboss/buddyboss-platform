<?php
/**
 * ReadyLaunch - Header Unread Notifications template.
 *
 * This template handles displaying unread notifications in the header dropdown.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$current_page = ! empty( $args['page'] ) ? (int) $args['page'] : 1;
$page_param   = ! empty( $current_page ) && $current_page > 1 ? '&page=' . $current_page : '';
if ( bp_has_notifications( bp_ajax_querystring( 'notifications' ) . '&user_id=' . get_current_user_id() . '&is_new=1' . $page_param ) ) :

	while ( bp_the_notifications() ) :
		bp_the_notification();
		$description = bp_get_the_notification_description();
		?>
		<li class="read-item <?php echo isset( buddypress()->notifications->query_loop->notification->is_new ) && buddypress()->notifications->query_loop->notification->is_new ? 'unread' : ''; ?>">
			<span class="bb-full-link">
				<?php echo wp_kses_post( $description ); ?>
			</span>
			<div class="notification-avatar">
				<?php bb_notification_avatar(); ?>
			</div>
			<div class="notification-content">
				<span class="bb-full-link">
					<?php echo wp_kses_post( $description ); ?>
				</span>
				<span><?php echo wp_kses_post( $description ); ?></span>
				<span class="posted"><?php bp_the_notification_time_since(); ?></span>
			</div>
			<div class="bb-rl-option-wrap">
				<button class="bb-rl-option-wrap__action bb-rl-button bb-rl-button--tertiaryText" data-balloon-pos="left" data-balloon="<?php esc_html_e( 'More options', 'buddyboss' ); ?>">
					<i class="bb-icons-rl-bold bb-icons-rl-dots-three"></i>
					<span class="screen-reader-text"><?php esc_html_e( 'More Options', 'buddyboss' ); ?></span>
				</button>
				<ul class="bb-rl-option-dropdown">
					<li>
						<button class="bb-rl-option-dropdown__button action-unread" data-notification-id="<?php bp_the_notification_id(); ?>">
							<i class="bb-icons-rl-check"></i>
							<div class="bb-rl-option-dropdown__label">
								<?php esc_html_e( 'Mark as read', 'buddyboss' ); ?>
							</div>
						</button>
					</li>
					<li>
						<button class="bb-rl-option-dropdown__button action-delete" data-notification-id="<?php bp_the_notification_id(); ?>">
							<i class="bb-icons-rl-trash"></i>
							<div class="bb-rl-option-dropdown__label">
								<?php esc_html_e( 'Delete notifications', 'buddyboss' ); ?>
							</div>
						</button>
					</li>
				</ul>
			</div>
		</li>
		<?php
	endwhile;

	$total       = bp_notifications_get_unread_notification_count();
	$total_pages = ceil( $total / 25 );
	$next_page   = $current_page + 1;
	if ( $current_page !== (int) $total_pages ) :
		?>
		<div class="bb-rl-load-more">
			<a class="button full outline" data-page="<?php echo esc_attr( $current_page ); ?>" data-next-page="<?php echo esc_attr( $next_page ); ?>" data-total-pages="<?php echo esc_attr( $total_pages ); ?>">
				<?php esc_html_e( 'Load More', 'buddyboss' ); ?>
			</a>
		</div>
		<?php
	endif;
else :
	?>
	<li class="bs-item-wrap">
		<div class="notification-content">
			<?php esc_html_e( 'You have no notifications right now.', 'buddyboss' ); ?>
		</div>
	</li>
	<?php
endif;
