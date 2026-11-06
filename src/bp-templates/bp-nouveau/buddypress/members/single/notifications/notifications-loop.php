<?php
/**
 * The template for members notifications loop
 *
 * This template can be overridden by copying it to yourtheme/buddypress/members/single/notifications/notifications-loop.php.
 *
 * @since   BuddyPress 3.0.0
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( bboss_has_notifications( bboss_ajax_querystring( 'notifications' ) ) ) :

	bboss_nouveau_pagination( 'top' ); ?>

	<form action="" method="post" id="notifications-bulk-management" class="standard-form">
		<table class="notifications bp-tables-user">
			<thead>
				<tr>
					<th class="icon"></th>
					<th class="bulk-select-all">
						<input id="select-all-notifications" class="bs-styled-checkbox" type="checkbox" aria-label="<?php esc_attr_e( 'Select all', 'buddyboss-platform' ); ?>" />
						<label class="bp-screen-reader-text" for="select-all-notifications"><?php esc_html_e( 'Select all', 'buddyboss-platform' ); ?></label>
					</th>
					<th class="title"><?php esc_html_e( 'Notification', 'buddyboss-platform' ); ?></th>
					<th class="date">
						<?php esc_html_e( 'Date Received', 'buddyboss-platform' ); ?>
						<?php bboss_nouveau_notifications_sort_order_links(); ?>
					</th>
					<th class="actions"><?php esc_html_e( 'Actions', 'buddyboss-platform' ); ?></th>
				</tr>
			</thead>

			<tbody>
				<?php
				while ( bboss_the_notifications() ) :
					bboss_the_notification();
					$bp                 = bboss();
					$bp_notification_id = bboss_get_the_notification_id();
					$readonly           = isset( $bp->notifications->query_loop->notification->readonly ) ? $bp->notifications->query_loop->notification->readonly : false;
					?>
					<tr>
						<td></td>
						<td class="bulk-select-check">
							<input id="<?php echo esc_attr( $bp_notification_id ); ?>" type="checkbox" name="notifications[]" value="<?php echo esc_attr( $bp_notification_id ); ?>" class="notification-check bs-styled-checkbox" data-readonly="<?php echo esc_attr( $readonly ); ?>"/>
							<label for="<?php echo esc_attr( $bp_notification_id ); ?>"><span class="bp-screen-reader-text"><?php esc_html_e( 'Select this notification', 'buddyboss-platform' ); ?></span></label>
						</td>
						<td class="notification-description"><?php bboss_the_notification_description(); ?></td>
						<td class="notification-since"><?php bboss_the_notification_time_since(); ?></td>
						<td class="notification-actions"><?php bboss_the_notification_action_links(); ?></td>
					</tr>
				<?php endwhile; ?>
			</tbody>
		</table>

		<div class="notifications-options-nav">
			<?php bboss_nouveau_notifications_bulk_management_dropdown(); ?>
		</div><!-- .notifications-options-nav -->

		<?php wp_nonce_field( 'notifications_bulk_nonce', 'notifications_bulk_nonce' ); ?>
	</form>

	<?php
	bboss_nouveau_pagination( 'bottom' );
else :
	bboss_nouveau_user_feedback( 'member-notifications-none' );
endif;
