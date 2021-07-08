<?php if ( bp_has_notifications( bp_ajax_querystring( 'notifications' ) . '&user_id=' . get_current_user_id() . '&is_new=1' ) ) : ?>
	<?php while ( bp_the_notifications() ) : bp_the_notification(); ?>
        <li class="read-item <?php echo isset( buddypress()->notifications->query_loop->notification->is_new ) && buddypress()->notifications->query_loop->notification->is_new ? 'unread' : ''; ?>">
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
                <a class="action-close primary" data-bp-tooltip-pos="left" data-bp-tooltip="<?php _e( 'Close', 'buddyboss' ); ?>" data-notification-id="<?php bp_the_notification_id(); ?>">
                    <span class="dashicons dashicons-no" aria-hidden="true"></span>
                </a>
            </div>
        </li>
	<?php endwhile; ?>
<?php endif; ?>
