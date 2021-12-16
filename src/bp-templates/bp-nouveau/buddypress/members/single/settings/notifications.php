<?php
/**
 * BuddyBoss - Members Settings ( Notifications )
 *
 * @since BuddyPress 3.0.0
 * @version 3.0.0
 */

bp_nouveau_member_hook( 'before', 'settings_template' ); ?>

<h2 class="screen-heading email-settings-screen">
	<?php _e( 'Notification Preferences', 'buddyboss' ); ?>
</h2>

<p class="bp-help-text email-notifications-info">
	<?php esc_html_e( 'Choose which notifications to receive across all your devices.', 'buddyboss' ); ?>
</p>

<form action="<?php echo esc_url( bp_displayed_user_domain() . bp_get_settings_slug() . '/notifications' ); ?>" method="post" class="standard-form" id="settings-form">

	<div class="notification_info">

		<div class="notification_type email_notification">
			<span class="notification_type_icon">
				<i class="bb-icon bb-icon-mail"></i>
			</span>

			<div class="notification_type_info">
				<h3>Email</h3>
				<p>A notification sent to your inbox</p>
			</div>
		</div><!-- .notification_type -->

		<?php if ( bb_is_web_notification_is_enabled() ) { ?>
		<div class="notification_type web_notification">
			<span class="notification_type_icon">
				<i class="bb-icon bb-icon-monitor"></i>
			</span>

			<div class="notification_type_info">
				<h3>Web</h3>
				<p>A notification in the corner of your screen</p>
			</div>
		</div><!-- .notification_type -->
        <?php } ?>

        <?php if ( bb_is_app_notification_is_enabled() ) { ?>
		<div class="notification_type app_notification">
			<span class="notification_type_icon">
				<i class="bb-icon bb-icon-smartphone"></i>
			</span>

			<div class="notification_type_info">
				<h3>App</h3>
				<p>A notification pushed to your mobile device</p>
			</div>
		</div><!-- .notification_type -->
        <?php } ?>

	</div><!-- .notification_info -->

	<table class="main-notification-settings">
		<thead>
			<tr>
				<th class="title">Enable notifications</th>
				<th class="email">
					<input type="checkbox" id="main_notification_email" name="" class="bs-styled-checkbox" />
					<label for="main_notification_email">Email</label>
				</th>

                <?php if ( bb_is_web_notification_is_enabled() ) { ?>
				<th class="web">
					<input type="checkbox" id="main_notification_web" name="" class="bs-styled-checkbox" />
					<label for="main_notification_web">Web</label>
				</th>
                <?php } ?>

                <?php if ( bb_is_app_notification_is_enabled() ) { ?>
				<th class="app">
					<input type="checkbox" id="main_notification_app" name="" class="bs-styled-checkbox" />
					<label for="main_notification_app">App</label>
				</th>
                <?php } ?>
			</tr>
		</thead>

		<tbody>

			<tr class="section-end">
				<td>A manual notification from a site admin</td>
				<td class="email notification_no_option">
					-
				</td>

                <?php if ( bb_is_web_notification_is_enabled() ) { ?>
				<td class="web">
					<input type="checkbox" id="admin_notification_web" name="" class="bs-styled-checkbox" />
					<label for="admin_notification_web">Web</label>
				</td>
                <?php } ?>

                <?php if ( bb_is_app_notification_is_enabled() ) { ?>
				<td class="app">
					<input type="checkbox" id="admin_notification_app" name="" class="bs-styled-checkbox" />
					<label for="admin_notification_app">App</label>
				</td>
                <?php } ?>
			</tr>
		</tbody>
	</table>

	<?php bp_nouveau_member_email_notice_settings(); ?>

	<?php bp_nouveau_submit_button( 'member-notifications-settings' ); ?>

</form>

<?php
bp_nouveau_member_hook( 'after', 'settings_template' );
