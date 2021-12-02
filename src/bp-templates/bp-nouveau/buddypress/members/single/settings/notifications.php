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

		<div class="notification_type web_notification">
			<span class="notification_type_icon">
				<i class="bb-icon bb-icon-monitor"></i>
			</span>

			<div class="notification_type_info">
				<h3>Web</h3>
				<p>A notification in the corner of your screen</p>
			</div>
		</div><!-- .notification_type -->

		<div class="notification_type app_notification">
			<span class="notification_type_icon">
				<i class="bb-icon bb-icon-smartphone"></i>
			</span>

			<div class="notification_type_info">
				<h3>App</h3>
				<p>A notification pushed to your mobile device</p>
			</div>
		</div><!-- .notification_type -->

	</div><!-- .notification_info -->

	<table class="main-notification-settings">
		<thead>
			<tr>
				<th class="title">Enable notifications</th>
				<th class="email">
					<input type="checkbox" id="main_notification_email" name="" class="bs-styled-checkbox" />
					<label for="main_notification_email">Email</label>
				</th>
				<th class="web">
					<input type="checkbox" id="main_notification_web" name="" class="bs-styled-checkbox" />
					<label for="main_notification_web">Web</label>
				</th>
				<th class="app">
					<input type="checkbox" id="main_notification_app" name="" class="bs-styled-checkbox" />
					<label for="main_notification_app">App</label>
				</th>
			</tr>
		</thead>

		<tbody>

			<tr class="section-end">
				<td>A manual notification from a site admin</td>
				<td class="email notification_no_option">
					-
				</td>
				<td class="web">
					<input type="checkbox" id="admin_notification_web" name="" class="bs-styled-checkbox" />
					<label for="admin_notification_web">Web</label>
				</td>
				<td class="app">
					<input type="checkbox" id="admin_notification_app" name="" class="bs-styled-checkbox" />
					<label for="admin_notification_app">App</label>
				</td>
			</tr>

			<tr class="notification_heading">
				<td class="title" colspan="3">Activity Feed</td>
			</tr>

			<tr>
				<td>A member mentions you in an update using "@john"</td>
				<td class="email">
					<input type="checkbox" id="mention_notification_email" name="" class="bs-styled-checkbox" />
					<label for="mention_notification_email">Email</label>
				</td>
				<td class="web">
					<input type="checkbox" id="mention_notification_web" name="" class="bs-styled-checkbox" />
					<label for="mention_notification_web">Web</label>
				</td>
				<td class="app">
					<input type="checkbox" id="mention_notification_app" name="" class="bs-styled-checkbox" />
					<label for="mention_notification_app">App</label>
				</td>
			</tr>

			<tr>
				<td>A member replies to an update or comment you've posted</td>
				<td class="email">
					<input type="checkbox" id="reply_notification_email" name="" class="bs-styled-checkbox" />
					<label for="reply_notification_email">Email</label>
				</td>
				<td class="web">
					<input type="checkbox" id="reply_notification_web" name="" class="bs-styled-checkbox" />
					<label for="reply_notification_web">Web</label>
				</td>
				<td class="app">
					<input type="checkbox" id="reply_notification_app" name="" class="bs-styled-checkbox" />
					<label for="reply_notification_app">App</label>
				</td>
			</tr>

			<tr class="notification_heading">
				<td class="title" colspan="3">Messages</td>
			</tr>

			<tr>
				<td>A member sends you a new message</td>
				<td class="email">
					<input type="checkbox" id="message_notification_email" name="" class="bs-styled-checkbox" />
					<label for="message_notification_email">Email</label>
				</td>
				<td class="web">
					<input type="checkbox" id="message_notification_web" name="" class="bs-styled-checkbox" />
					<label for="message_notification_web">Web</label>
				</td>
				<td class="app">
					<input type="checkbox" id="message_notification_app" name="" class="bs-styled-checkbox" />
					<label for="message_notification_app">App</label>
				</td>
			</tr>

			<tr class="notification_heading">
				<td class="title" colspan="3">Social Groups</td>
			</tr>

			<tr>
				<td>A member sends you a new message</td>
				<td class="email">
					<input type="checkbox" id="group_message_notification_email" name="" class="bs-styled-checkbox" />
					<label for="group_message_notification_email">Email</label>
				</td>
				<td class="web">
					<input type="checkbox" id="group_message_notification_web" name="" class="bs-styled-checkbox" />
					<label for="group_message_notification_web">Web</label>
				</td>
				<td class="app">
					<input type="checkbox" id="group_message_notification_app" name="" class="bs-styled-checkbox" />
					<label for="group_message_notification_app">App</label>
				</td>
			</tr>

		</tbody>

	</table>

	<?php bp_nouveau_member_email_notice_settings(); ?>

	<?php bp_nouveau_submit_button( 'member-notifications-settings' ); ?>

</form>

<?php
bp_nouveau_member_hook( 'after', 'settings_template' );
