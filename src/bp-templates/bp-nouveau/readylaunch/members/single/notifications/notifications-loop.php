<?php
/**
 * The template for members notifications loop
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @version 1.0.0
 */

if ( bp_has_notifications( bp_ajax_querystring( 'notifications' ) ) ) :

?>

	<form action="" method="post" id="notifications-bulk-management" class="standard-form">
		<ul class="notification-list bb-nouveau-list bs-item-list list-view">

			<li class="bs-item-wrap bs-header-item align-items-center no-hover-effect">
				<div class="bulk-select-all">
					<input id="select-all-notifications" type="checkbox" class="bs-styled-checkbox" />
					<label for="select-all-notifications"></label>
				</div>
				<div class="notifications-options-nav flex-1">
					<?php bp_nouveau_notifications_bulk_management_dropdown(); ?>
				</div><!-- .notifications-options-nav -->

				<?php wp_nonce_field( 'notifications_bulk_nonce', 'notifications_bulk_nonce' ); ?>

				<div class="push-right bb-sort-by-date">
					<?php esc_html_e( 'Sort by date', 'buddyboss' ); ?>
					<?php bp_nouveau_notifications_sort_order_links(); ?>
				</div>
			</li>

				<?php
				while ( bp_the_notifications() ) :
					bp_the_notification();
					$bp                 = buddypress();
					$bp_notification_id = bp_get_the_notification_id();
					$readonly           = isset( $bp->notifications->query_loop->notification->readonly ) ? $bp->notifications->query_loop->notification->readonly : false;
					?>
					<li class="bs-item-wrap">
						<div class="bulk-select-check">
							<span class="bb-input-wrap">
								<input id="<?php echo esc_attr( $bp_the_notification_id ); ?>" type="checkbox" name="notifications[]" value="<?php echo esc_attr(  $bp_the_notification_id); ?>" class="notification-check bs-styled-checkbox" data-readonly="<?php echo esc_attr( $readonly ); ?>"/>
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
							<?php bp_the_notification_action_links(); ?>
						</div>
					</li>
				<?php endwhile; ?>
		</ul>
	</form>

	<?php
	bp_nouveau_pagination( 'bottom' );
else :
	bp_nouveau_user_feedback( 'member-notifications-none' );
endif;
