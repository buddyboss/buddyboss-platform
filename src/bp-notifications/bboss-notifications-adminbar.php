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
function bboss_notifications_toolbar_menu() {
	global $wp_admin_bar;

	if ( ! is_user_logged_in() ) {
		return false;
	}

	$count       = bboss_notifications_get_unread_notification_count( bboss_loggedin_user_id() );
	$alert_class = (int) $count > 0 ? 'pending-count alert' : 'count no-alert';
	$menu_title  = '<span id="ab-pending-notifications" class="' . $alert_class . '">' . bboss_core_number_format( $count ) . '</span>';
	$menu_link   = trailingslashit( bboss_loggedin_user_domain() . bboss_get_notifications_slug() );

	// Add the top-level Notifications button.
	$wp_admin_bar->add_menu(
		array(
			'parent' => 'top-secondary',
			'id'     => 'bp-notifications',
			'title'  => $menu_title,
			'href'   => $menu_link,
		)
	);

	if ( bboss_has_notifications( bboss_ajax_querystring( 'notifications' ) . '&per_page=6&user_id=' . get_current_user_id() . '&is_new=1' ) ) {

		$total = bboss()->notifications->query_loop->total_notification_count;
		while ( bboss_the_notifications() ) :
			bboss_the_notification();

			ob_start();
			?>
				<div class="notification-avatar">
				<?php bboss_notification_avatar(); ?>
				</div>
				<div class="notification-content">
					<div class="bb-full-link">
						<?php bboss_the_notification_description(); ?>
					</div>
					<div class="posted"><?php bboss_the_notification_time_since(); ?></div>
				</div>
				<?php

				$html = ob_get_clean();
				$wp_admin_bar->add_menu(
					array(
						'parent' => 'bp-notifications',
						'id'     => 'notification-' . bboss_get_the_notification_id(),
						'title'  => $html,
					)
				);

			endwhile;

		if ( $total > 6 ) {
			$menu_link = trailingslashit( bboss_loggedin_user_domain() . bboss_get_notifications_slug() );
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
add_action( 'admin_bar_menu', 'bboss_members_admin_bar_notifications_menu', 90 );
