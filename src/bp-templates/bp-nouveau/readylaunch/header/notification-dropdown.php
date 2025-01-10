<?php

$menu_link                 = trailingslashit( bp_loggedin_user_domain() . bp_get_notifications_slug() );
$notifications             = bp_notifications_get_unread_notification_count( bp_loggedin_user_id() );
$unread_notification_count = ! empty( $notifications ) ? $notifications : 0;
?>
<div id="header-notifications-dropdown-elem" class="notification-wrap menu-item-has-children">
	<a href="javascript:void(0);" ref="notification_bell" class="notification-link" <?php echo bb_elementor_pro_disable_page_transition(); ?> aria-label="<?php esc_html_e( 'Notifications', 'buddyboss' ); ?>">
<!--		<span data-balloon-pos="down" data-balloon="--><?php //_e( 'Notifications', 'buddyboss' ); ?><!--">-->
<!--			<i class="bb-icon-l bb-icon-bell"></i>-->
<!--			--><?php //if ( $unread_notification_count > 0 ): ?>
<!--				<span class="count">--><?php //echo $unread_notification_count; ?><!--</span>-->
<!--			--><?php //endif; ?>
<!--		</span>-->
		Notifications
	</a>
	<section class="notification-dropdown">
		<header class="notification-header">
			<h2 class="title"><?php _e( 'Notifications', 'buddyboss' ); ?></h2>
			<a class="mark-read-all action-unread" data-notification-id="all" style="<?php echo esc_attr( $unread_notification_count > 0 ? 'display:block;' : 'display:none;' ); ?>">
				<?php _e( 'Mark all as read', 'buddyboss' ); ?>
			</a>
		</header>

		<div class="header-ajax-container" id="notification-list">
			<ul class="notification-list bb-nouveau-list"></ul>

			<footer class="notification-footer">
				<a href="<?php echo $menu_link ?>" class="delete-all">
					<?php _e( 'View Notifications', 'buddyboss' ); ?>
					<i class="bb-icon-l bb-icon-angle-right"></i>
				</a>
			</footer>
		</div>
	</section>
</div>
