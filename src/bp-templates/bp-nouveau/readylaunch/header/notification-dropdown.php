<?php
/**
 * The Notification template in the header for ReadyLaunch.
 *
 * @since   BuddyBoss [BBVERSION]
 *
 * @package ReadyLaunch
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
			<a class="mark-read-all action-unread" data-notification-id="all" style="<?php echo esc_attr( $unread_notification_count > 0 ? 'display:block;' : 'display:none;' ); ?>">
				<i class="bb-icons-rl-bold bb-icons-rl-checks"></i>
				<span class="screen-reader-text"><?php esc_html_e( 'Mark all as read', 'buddyboss' ); ?></span>
			</a>
		</header>

		<div class="notification-header-tabs">
			<button href="#" class="bbrl-button bbrl-button--tertiaryText notification-header-tab-action active ">
				<?php esc_html_e( 'All', 'buddyboss' ); ?>
			</button>
			<button href="#" class="bbrl-button bbrl-button--tertiaryText notification-header-tab-action">
				<?php esc_html_e( 'Unread', 'buddyboss' ); ?>
			</button>
		</div>

		<div class="header-ajax-container notification-listing" id="notification-list">
			<ul class="notification-list bb-nouveau-list"></ul>

			<footer class="notification-footer">
				<a href="<?php echo esc_url( $menu_link ); ?>" class="delete-all">
					<?php esc_html_e( 'View Notifications', 'buddyboss' ); ?>
					<i class="bb-icon-l bb-icon-angle-right"></i>
				</a>
			</footer>
		</div>
	</section>
</div>