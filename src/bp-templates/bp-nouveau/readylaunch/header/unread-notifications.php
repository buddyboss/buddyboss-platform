<?php

$current_page = ! empty( $args['page'] ) ? (int) $args['page'] : 1;
$page_param   = ! empty( $current_page ) && $current_page > 1 ? '&page=' . $current_page : '';
if ( bp_has_notifications( bp_ajax_querystring( 'notifications' ) . '&user_id=' . get_current_user_id() . '&is_new=1' . $page_param ) ) :

	while ( bp_the_notifications() ) :
		bp_the_notification();
		$description = bp_get_the_notification_description();
		?>
		<li class="read-item <?php echo isset( buddypress()->notifications->query_loop->notification->is_new ) && buddypress()->notifications->query_loop->notification->is_new ? 'unread' : ''; ?>">
			<span class="bb-full-link">
				<?php echo $description; ?>
			</span>
			<div class="notification-avatar">
				<?php bb_notification_avatar(); ?>
			</div>
			<div class="notification-content">
				<span class="bb-full-link">
					<?php echo $description; ?>
				</span>
				<span><?php echo $description; ?></span>
				<span class="posted"><?php bp_the_notification_time_since(); ?></span>
			</div>
			<div class="actions">
				<a class="mark-read action-unread primary" data-bp-tooltip-pos="left" data-bp-tooltip="<?php esc_html_e( 'Mark as Read', 'buddyboss' ); ?>" data-notification-id="<?php bp_the_notification_id(); ?>">
					<span class="dashicons dashicons-visibility" aria-hidden="true"></span>
				</a>
			</div>
		</li>
	<?php
	endwhile;

	$total       = bp_notifications_get_unread_notification_count();
	$total_pages = ceil( $total / 25 );
	$next_page   = $current_page + 1;
	if ( $current_page != (int) $total_pages ) :
		?>
		<div class="load-more">
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
