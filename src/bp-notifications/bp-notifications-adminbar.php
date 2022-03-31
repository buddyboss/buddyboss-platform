<?php
/**
 * BuddyPress Notifications Admin Bar functions.
 *
 * Admin Bar functions for the Notifications component.
 *
 * @package BuddyBoss\Notifications\Toolbar
 * @since BuddyPress 1.9.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Build the "Notifications" dropdown.
 *
 * @since BuddyPress 1.9.0
 *
 * @return bool
 */
function bp_notifications_toolbar_menu() {
	global $wp_admin_bar;

	if ( ! is_user_logged_in() ) {
		return false;
	}

	if ( bb_enabled_legacy_email_preference() ) {

		$notifications = bp_notifications_get_notifications_for_user( bp_loggedin_user_id(), 'object' );
		$count         = ! empty( $notifications ) ? count( $notifications ) : 0;
		$alert_class   = (int) $count > 0 ? 'pending-count alert' : 'count no-alert';
		$menu_title    = '<span id="ab-pending-notifications" class="' . $alert_class . '">' . bp_core_number_format( $count ) . '</span>';
		$menu_link     = trailingslashit( bp_loggedin_user_domain() . bp_get_notifications_slug() );

		// Add the top-level Notifications button.
		$wp_admin_bar->add_menu(
			array(
				'parent' => 'top-secondary',
				'id'     => 'bp-notifications',
				'title'  => $menu_title,
				'href'   => $menu_link,
			)
		);

		if ( ! empty( $notifications ) ) {
			foreach ( (array) $notifications as $notification ) {
				$wp_admin_bar->add_menu(
					array(
						'parent' => 'bp-notifications',
						'id'     => 'notification-' . $notification->id,
						'title'  => $notification->content,
						'href'   => $notification->href,
					)
				);
			}
		} else {
			$wp_admin_bar->add_menu(
				array(
					'parent' => 'bp-notifications',
					'id'     => 'no-notifications',
					'title'  => __( 'No new notifications', 'buddyboss' ),
					'href'   => $menu_link,
				)
			);
		}
	} else {

		$count       = bp_notifications_get_unread_notification_count( bp_loggedin_user_id() );
		$alert_class = (int) $count > 0 ? 'pending-count alert' : 'count no-alert';
		$menu_title  = '<span id="ab-pending-notifications" class="' . $alert_class . '">' . bp_core_number_format( $count ) . '</span>';
		$menu_link   = trailingslashit( bp_loggedin_user_domain() . bp_get_notifications_slug() );

		// Add the top-level Notifications button.
		$wp_admin_bar->add_menu(
			array(
				'parent' => 'top-secondary',
				'id'     => 'bp-notifications',
				'title'  => $menu_title,
				'href'   => $menu_link,
			)
		);

		if ( bp_has_notifications( bp_ajax_querystring( 'notifications' ) . '&per_page=6&user_id=' . get_current_user_id() . '&is_new=1' ) ) {

			$total = buddypress()->notifications->query_loop->total_notification_count;
			while ( bp_the_notifications() ) :
				bp_the_notification();

				ob_start();
				?>
				<span class="bb-full-link">
					<?php bp_the_notification_description(); ?>
				</span>
				<div class="notification-avatar">
					<?php bb_notification_avatar(); ?>
				</div>
				<div class="notification-content">
					<span class="bb-full-link">
						<?php bp_the_notification_description(); ?>
					</span>
					<span><?php bp_the_notification_description(); ?></span>
					<span class="posted"><?php bp_the_notification_time_since(); ?></span>
				</div>
				<div class="actions">
					<a class="mark-read action-unread primary" data-bp-tooltip-pos="left" data-bp-tooltip="<?php esc_html_e( 'Mark Read', 'buddyboss' ); ?>" data-notification-id="<?php bp_the_notification_id(); ?>">
						<span class="dashicons dashicons-visibility" aria-hidden="true"></span>
					</a>
				</div>
				<?php

				$html = ob_get_clean();
				$wp_admin_bar->add_menu(
					array(
						'parent' => 'bp-notifications',
						'id'     => 'notification-' . bp_get_the_notification_id(),
						'title'  => $html,
						'href'   => bp_get_the_notification_mark_unread_url( get_current_user_id() ),
					)
				);

			endwhile;

			if ( $total ) {
				$menu_link = trailingslashit( bp_loggedin_user_domain() . bp_get_notifications_slug() );
				$wp_admin_bar->add_menu(
					array(
						'parent' => 'bp-notifications',
						'id'     => 'notification-view-all',
						'title'  => esc_html__( 'View Notifications', 'buddyboss' ),
						'href'   => $menu_link,
					)
				);

			}
		} else {
			$wp_admin_bar->add_menu(
				array(
					'parent' => 'bp-notifications',
					'id'     => 'no-notifications',
					'title'  => __( 'No new notifications', 'buddyboss' ),
					'href'   => $menu_link,
				)
			);
		}
	}
}
add_action( 'admin_bar_menu', 'bp_members_admin_bar_notifications_menu', 90 );
