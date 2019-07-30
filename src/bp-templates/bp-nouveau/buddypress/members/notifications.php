<?php if ( bp_has_notifications( bp_ajax_querystring( 'notifications' ) . '&user_id=' . get_current_user_id() ) ) : ?>
	<?php while ( bp_the_notifications() ) : bp_the_notification(); ?>
		<li class="read-item">
			<span class="bb-full-link">
				<?php bp_the_notification_description(); ?>
			</span>
		    <div class="notification-avatar">
				<?php buddyboss_notification_avatar(); ?>
		    </div>
		    <div class="notification-content">
				<span class="bb-full-link">
					<?php bp_the_notification_description(); ?>
				</span>
		        <span><?php bp_the_notification_description(); ?></span>
		        <span class="posted"><?php bp_the_notification_time_since(); ?></span>
		    </div>
		</li>
	<?php endwhile; ?>
<?php endif; ?>
