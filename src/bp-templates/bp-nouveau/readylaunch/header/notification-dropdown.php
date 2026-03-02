<?php
/**
 * ReadyLaunch - Header Notification Dropdown template.
 *
 * This template handles the notification dropdown display in the header for ReadyLaunch.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$menu_link                 = trailingslashit( bp_loggedin_user_domain() . bp_get_notifications_slug() );
$notifications             = bp_notifications_get_unread_notification_count( bp_loggedin_user_id() );
$unread_notification_count = ! empty( $notifications ) ? $notifications : 0;
?>
<div id="header-notifications-dropdown-elem" class="notification-wrap menu-item-has-children">
	<a href="javascript:void(0);" ref="notification_bell" class="notification-link" aria-label="<?php esc_html_e( 'Notifications', 'buddyboss' ); ?>">
		<span data-balloon-pos="down" data-balloon="<?php esc_attr_e( 'Notifications', 'buddyboss' ); ?>">
			<i class="bb-icons-rl-bell-simple"></i>
			<?php if ( $unread_notification_count > 0 ) : ?>
				<span class="count"><?php echo esc_html( $unread_notification_count ); ?></span>
			<?php endif; ?>
		</span>
	</a>
	<section class="notification-dropdown">
		<header class="notification-header flex items-center justify-between">
			<h2 class="title"><?php esc_html_e( 'Notifications', 'buddyboss' ); ?></h2>
			<a href="<?php echo esc_url( $menu_link ); ?>" class="bb-rl-button bb-rl-button--secondaryFill message-view-all-link"><?php esc_html_e( 'View all', 'buddyboss' ); ?></a>
			<button class="mark-read-all action-unread" data-notification-id="all" data-balloon-pos="down" data-balloon="<?php esc_html_e( 'Mark all as read', 'buddyboss' ); ?>" style="<?php echo esc_attr( $unread_notification_count > 0 ? 'display:flex;' : 'display:none;' ); ?>">
				<i class="bb-icons-rl-bold bb-icons-rl-checks"></i>
				<span class="screen-reader-text"><?php esc_html_e( 'Mark all as read', 'buddyboss' ); ?></span>
			</button>
		</header>

		<div class="header-ajax-container notification-listing">
			<ul class="notification-list bb-nouveau-list" id="notification-list"></ul>
		</div>
	</section>
</div>