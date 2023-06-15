<?php
/**
 * The template for members notifications loop
 *
 * This template can be overridden by copying it to yourtheme/buddypress/members/single/notifications/notifications-loop.php.
 *
 * @since   BuddyPress 3.0.0
 * @version 1.0.0
 */

if ( bp_has_notifications( bp_ajax_querystring( 'notifications' ) ) ) :

	bp_nouveau_pagination( 'top' ); ?>

	<form action="" method="post" id="notifications-bulk-management" class="standard-form">
		<table class="notifications bp-tables-user">
			<thead>
				<tr>
					<th class="icon"></th>
					<th class="bulk-select-all">
						<input id="select-all-notifications" class="bs-styled-checkbox" type="checkbox" />
						<label class="bp-screen-reader-text" for="select-all-notifications"><?php esc_html_e( 'Select all', 'buddyboss' ); ?></label>
					</th>
					<th class="title"><?php esc_html_e( 'Notification', 'buddyboss' ); ?></th>
					<th class="date">
						<?php esc_html_e( 'Date Received', 'buddyboss' ); ?>
						<?php bp_nouveau_notifications_sort_order_links(); ?>
					</th>
					<th class="actions"><?php esc_html_e( 'Actions', 'buddyboss' ); ?></th>
				</tr>
			</thead>

			<tbody>

				<?php
				while ( bp_the_notifications() ) :
					bp_the_notification();
					$bp       = buddypress();
					$readonly = isset( $bp->notifications->query_loop->notification->readonly ) ? $bp->notifications->query_loop->notification->readonly : false;
				?>

					<tr>
						<td></td>
						<td class="bulk-select-check">
							<input id="<?php bp_the_notification_id(); ?>" type="checkbox" name="notifications[]" value="<?php bp_the_notification_id(); ?>" class="notification-check bs-styled-checkbox" data-readonly="<?php echo esc_attr( $readonly ); ?>"/>
							<label for="<?php bp_the_notification_id(); ?>"><span class="bp-screen-reader-text"><?php esc_html_e( 'Select this notification', 'buddyboss' ); ?></span></label>
						</td>
						<td class="notification-description"><?php bp_the_notification_description(); ?></td>
						<td class="notification-since"><?php bp_the_notification_time_since(); ?></td>
						<td class="notification-actions"><?php bp_the_notification_action_links(); ?></td>
					</tr>

				<?php endwhile; ?>

			</tbody>
		</table>

		<div class="notifications-options-nav">
			<?php bp_nouveau_notifications_bulk_management_dropdown(); ?>
		</div><!-- .notifications-options-nav -->

		<?php wp_nonce_field( 'notifications_bulk_nonce', 'notifications_bulk_nonce' ); ?>
	</form>

	<?php bp_nouveau_pagination( 'bottom' ); ?>

<?php else : ?>

	<?php bp_nouveau_user_feedback( 'member-notifications-none' ); ?>

<?php endif;
