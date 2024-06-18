<?php
/**
 * Notifications Settings
 *
 * @package BuddyBoss\Notifications
 * @since BuddyBoss 1.9.3
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get the Notification settings sections.
 *
 * @since BuddyBoss 1.9.3
 *
 * @return array
 */
function bb_notification_get_settings_sections() {

	$settings = array(
		'bp_notifications' => array(
			'page'              => 'notifications',
			'title'             => esc_html__( 'On-screen Notifications', 'buddyboss' ),
			'tutorial_callback' => 'bp_admin_on_screen_notification_setting_tutorial',
			'notice'            => ( ! bb_enabled_legacy_email_preference() ) ? __( 'Members can manage which on-screen notifications they receive in their notification preferences by enabling or disabling the "Web" options.', 'buddyboss' ) : '',
		),
	);

	if ( false === bb_enabled_legacy_email_preference() ) {
		$settings['bp_notification_settings_automatic'] = array(
			'page'              => 'notifications',
			'title'             => esc_html__( 'Notification Types', 'buddyboss' ),
			'tutorial_callback' => 'bb_automatic_notifications_tutorial',
			'notice'            => (
				false === bb_enabled_legacy_email_preference() ?
				sprintf(
					wp_kses_post(
					/* translators: Tutorial link. */
						__( 'You can register your own notifications types by following the steps in %s. Once registered, they\'ll be configurable in the options above.', 'buddyboss' )
					),
					'<a href="' .
					'https://www.buddyboss.com/resources/dev-docs/app-development/extending-the-buddyboss-app-plugin/migrating-custom-notifications-to-modern-notifications-api/'
					. '" target="_blank" >' . esc_html__( 'this tutorial', 'buddyboss' ) . '</a>'
				) : ''
			),
		);
	}

	if ( false === bb_enabled_legacy_email_preference() && bp_is_active( 'messages' ) ) {
		$settings['bp_messaging_notification_settings'] = array(
			'page'              => 'notifications',
			'title'             => esc_html__( 'Messaging Notifications', 'buddyboss' ),
			'tutorial_callback' => 'bb_messaging_notifications_tutorial',
		);
	}

	$settings['bp_web_push_notification_settings'] = array(
		'page'              => 'notifications',
		'title'             => esc_html__( 'Web Push Notifications', 'buddyboss' ),
		'tutorial_callback' => 'bb_web_push_notifications_tutorial',
	);

	return (array) apply_filters( 'bb_notification_get_settings_sections', $settings );
}

/**
 * Link to Web Push Notification tutorial.
 *
 * @since BuddyBoss 2.0.4
 */
function bb_web_push_notifications_tutorial() {
	?>

	<p>
		<a class="button" href="
		<?php
		echo esc_url(
			bp_get_admin_url(
				add_query_arg(
					array(
						'page'    => 'bp-help',
						'article' => '125638',
					),
					'admin.php'
				)
			)
		);
		?>
		">
			<?php esc_html_e( 'View Tutorial', 'buddyboss' ); ?>
		</a>
	</p>

	<?php
}

/**
 * Link to Automatic Notification tutorial
 *
 * @since BuddyBoss 1.9.3
 */
function bb_automatic_notifications_tutorial() {
	?>

	<p>
		<a class="button" href="
		<?php
		echo esc_url(
			bp_get_admin_url(
				add_query_arg(
					array(
						'page'    => 'bp-help',
						'article' => 125369,
					),
					'admin.php'
				)
			)
		);
		?>
		"><?php esc_html_e( 'View Tutorial', 'buddyboss' ); ?></a>
	</p>

	<?php
}

/**
 * Get settings fields by section.
 *
 * @since BuddyBoss 1.9.3
 *
 * @param string $section_id Section id.
 *
 * @return mixed False if section is invalid, array of fields otherwise.
 */
function bb_notification_get_settings_fields_for_section( $section_id = '' ) {

	// Bail if section is empty.
	if ( empty( $section_id ) ) {
		return false;
	}

	$fields = bb_notification_get_settings_fields();
	$retval = isset( $fields[ $section_id ] ) ? $fields[ $section_id ] : false;

	return (array) apply_filters( 'bb_notification_get_settings_fields_for_section', $retval, $section_id );
}

/**
 * Get all the settings fields.
 *
 * @since BuddyBoss 1.9.3
 *
 * @return array
 */
function bb_notification_get_settings_fields() {

	$fields = array();

	$fields['bp_notifications'] = array(
		'_bp_on_screen_notifications_enable'        => array(
			'title'             => esc_html__( 'On-screen notifications', 'buddyboss' ),
			'callback'          => 'bb_admin_setting_callback_on_screen_notifications_enable',
			'sanitize_callback' => 'intval',
			'args'              => array(),
		),
		'_bp_on_screen_notification_position'       => array(
			'title'             => esc_html__( 'Position on Screen', 'buddyboss' ),
			'callback'          => 'bb_admin_setting_callback_on_screen_notifications_position',
			'sanitize_callback' => 'intval',
			'args'              => array(),
		),
		'_bp_on_screen_notification_mobile_support' => array(
			'title'             => esc_html__( 'Mobile Support', 'buddyboss' ),
			'callback'          => 'bb_admin_setting_callback_on_screen_notifications_mobile_support',
			'sanitize_callback' => 'intval',
			'args'              => array(),
		),
		'_bp_on_screen_notification_visibility'     => array(
			'title'             => esc_html__( 'Automatically Hide', 'buddyboss' ),
			'callback'          => 'bb_admin_setting_callback_on_screen_notifications_visibility',
			'sanitize_callback' => 'intval',
			'args'              => array(),
		),
		'_bp_on_screen_notification_browser_tab'    => array(
			'title'             => esc_html__( 'Show in Browser Tab', 'buddyboss' ),
			'callback'          => 'bb_admin_setting_callback_on_screen_notifications_browser_tab',
			'sanitize_callback' => 'intval',
			'args'              => array(),
		),
	);

	$fields['bp_notification_settings_automatic'] = array();

	if ( false === bb_enabled_legacy_email_preference() ) {
		$fields['bp_notification_settings_automatic']['infos'] = array(
			'title'             => esc_html__( 'Notes', 'buddyboss' ),
			'callback'          => 'bb_admin_setting_callback_on_automatic_notification_information',
			'sanitize_callback' => 'string',
			'args'              => array( 'class' => 'notes-hidden-header' ),
		);

		$fields['bp_notification_settings_automatic']['fields'] = array(
			'title'             => esc_html__( 'Notification Fields', 'buddyboss' ),
			'callback'          => 'bb_admin_setting_callback_on_automatic_notification_fields',
			'sanitize_callback' => 'string',
			'args'              => array( 'class' => 'notes-hidden-header child-no-padding' ),
		);

		if ( bp_is_active( 'messages' ) && ! bb_hide_messages_from_notification_enabled() && ! bb_delay_email_notifications_enabled() && function_exists( 'bb_pusher_is_enabled' ) && bb_pusher_is_enabled() && function_exists( 'bb_pusher_is_feature_enabled' ) && true === bb_pusher_is_feature_enabled( 'live-messaging' ) ) {
			$fields['bp_messaging_notification_settings']['infos'] = array(
				'title'             => esc_html__( 'Notes', 'buddyboss' ),
				'callback'          => 'bb_admin_setting_callback_messaging_notification_warning',
				'sanitize_callback' => 'string',
				'args'              => array( 'class' => 'notes-hidden-header' ),
			);
		}

		$fields['bp_messaging_notification_settings']['fields'] = array(
			'title'             => esc_html__( 'Messaging Notifications Fields', 'buddyboss' ),
			'callback'          => 'bb_admin_setting_callback_messaging_notification_fields',
			'sanitize_callback' => 'string',
			'args'              => array( 'class' => 'notes-hidden-header' ),
		);

	} else {
		$fields['bp_notification_settings_automatic']['infos'] = array(
			'title'             => esc_html__( 'Notes', 'buddyboss' ),
			'callback'          => 'bb_admin_setting_callback_notification_warning',
			'sanitize_callback' => 'string',
			'args'              => array( 'class' => 'notes-hidden-header' ),
		);
	}

	$fields['bp_web_push_notification_settings'] = array();

	if ( ! function_exists( 'bb_platform_pro' ) ) {
		$fields['bp_web_push_notification_settings']['infos'] = array(
			'title'             => esc_html__( 'Notes', 'buddyboss' ),
			'callback'          => 'bb_admin_setting_callback_push_notification_bbp_pro_not_installed',
			'sanitize_callback' => 'string',
			'args'              => array( 'class' => 'notes-hidden-header' ),
		);
	} elseif (
		function_exists( 'bb_platform_pro' ) &&
		version_compare( bb_platform_pro()->version, '2.0.2', '<=' )
	) {
		$fields['bp_web_push_notification_settings']['infos'] = array(
			'title'             => esc_html__( 'Notes', 'buddyboss' ),
			'callback'          => 'bb_admin_setting_callback_push_notification_bbp_pro_older_version_installed',
			'sanitize_callback' => 'string',
			'args'              => array( 'class' => 'notes-hidden-header' ),
		);
	} elseif (
		function_exists( 'bb_platform_pro' ) &&
		(
			function_exists( 'bb_enabled_legacy_email_preference' ) &&
			bb_enabled_legacy_email_preference()
		)
	) {
		$fields['bp_web_push_notification_settings']['infos'] = array(
			'title'             => esc_html__( 'Notes', 'buddyboss' ),
			'callback'          => 'bb_admin_setting_callback_push_notification_lab_notification_preferences',
			'sanitize_callback' => 'string',
			'args'              => array( 'class' => 'notes-hidden-header' ),
		);
	} else {
		$fields['bp_web_push_notification_settings'] = apply_filters( 'bb_notification_web_push_notification_settings', $fields['bp_web_push_notification_settings'] );
	}

	return (array) apply_filters( 'bb_notification_get_settings_fields', $fields );
}

/**
 * Added instructions for the notification type.
 *
 * @since BuddyBoss 1.9.3
 */
function bb_admin_setting_callback_on_automatic_notification_information() {
	echo '<p class="description notification-information">' .
		esc_html__( 'Select which types of notifications are sent to members when specific actions happen on your site. When a notification is disabled, it will not be generated for any member. Members can configure which notifications they receive via email, web or app in their Notification Preferences.', 'buddyboss' ) .
	'</p>';
}

/**
 * Callback fields for the notification fields options.
 *
 * @since BuddyBoss 1.9.3
 */
function bb_admin_setting_callback_on_automatic_notification_fields() {
	$all_notifications    = bb_register_notification_preferences();
	$enabled_notification = bp_get_option( 'bb_enabled_notification', array() );
	$email_url            = get_admin_url( bp_get_root_blog_id(), 'edit.php?post_type=' . bp_get_email_post_type() . '&popup=yes' );

	if ( ! empty( $all_notifications ) ) {
		echo '<table class="form-table render-dynamic-notification"><tbody>';
		foreach ( $all_notifications as $field_group ) {
			?>
			<tr class="child-no-padding">
				<th><?php echo isset( $field_group['admin_label'] ) ? esc_html( $field_group['admin_label'] ) : ''; ?></th>
				<td class="no-padding">
					<?php
					if ( ! empty( $field_group['fields'] ) ) {
						echo '<div class="field-set">';
						foreach ( $field_group['fields'] as $field ) {
							$checked = isset( $field['default'] ) && 'yes' === $field['default'];
							?>
								<div class="field-block">
									<div class="field-render">
										<?php
										if ( array_key_exists( $field['key'], $enabled_notification ) ) {
											$checked = false;
											if (
												isset( $enabled_notification[ $field['key'] ]['main'] ) &&
												'yes' === $enabled_notification[ $field['key'] ]['main']
											) {
												$checked = true;
											}
										}

										bb_activate_notification( $field, $checked );
										?>
									</div>

									<?php
									$registered_emails = bb_register_notification_email_templates( $field['key'] );
									$total_email_count = 0;

									if ( ! empty( $registered_emails ) ) {
										foreach ( $registered_emails as $email_type ) {
											$total_email_count += get_terms(
												array(
													'taxonomy' => bp_get_email_tax_type(),
													'slug' => $email_type,
													'fields' => 'count',
												)
											);
										}
									}

									if ( ! empty( $registered_emails ) && count( $registered_emails ) > $total_email_count ) {
										$label_text = esc_html__( 'Missing Email Template', 'buddyboss' );

										if ( ( count( $registered_emails ) - $total_email_count ) > 1 ) {
											$label_text = esc_html__( 'Missing Email Templates', 'buddyboss' );
										}
										?>
											<a class="no-email-info" href="<?php echo esc_url( $email_url ); ?>"><?php echo wp_kses_post( $label_text ); ?></a>
										<?php
									} elseif ( ! empty( $registered_emails ) ) {

										$label_text = esc_html__( 'Email Template', 'buddyboss' );
										if ( count( $registered_emails ) > 1 ) {
											$label_text = esc_html__( 'Email Templates', 'buddyboss' );
										}

										$posts = get_posts(
											array(
												'showposts' => 1,
												'post_type' => bp_get_email_post_type(),
												'tax_query' => array(
													array(
														'taxonomy' => bp_get_email_tax_type(),
														'field' => 'slug',
														'terms' => $registered_emails,
													),
												),
												'fields' => 'ids',
											)
										);

										$url = ( count( $registered_emails ) === 1 && ! empty( $posts ) ) ?
											get_edit_post_link(
												current( $posts )
											) : add_query_arg(
												array(
													'post_type' => bp_get_email_post_type(),
													'taxonomy' => bp_get_email_tax_type(),
													'terms'    => implode( ',', $registered_emails ),
												),
												'edit.php'
											);
										?>
										<a class="email-info" href="<?php echo esc_url( $url ); ?>"><?php echo wp_kses_post( $label_text ); ?></a>
										<?php
									}
									?>

									<a href="javascript:void(0);" class="notification-defaults"><?php esc_html_e( 'Manage Defaults', 'buddyboss' ); ?></a>
									<div class="manage-defaults manage-defaults-hide <?php echo esc_attr( $field['key'] ); ?> " data-id="<?php echo esc_attr( $field['key'] ); ?>">
										<?php
										$options = bb_notification_preferences_types( $field );

										if ( ! empty( $options ) ) {
											foreach ( $options as $key => $v ) {
												$parent_disabled = ! empty( $field['notification_read_only'] ) && true === $field['notification_read_only'];
												$is_disabled     = apply_filters( 'bb_is_' . $field['key'] . '_' . $key . '_preference_enabled', ! $checked );
												$is_render       = apply_filters( 'bb_is_' . $field['key'] . '_' . $key . '_preference_type_render', $v['is_render'], $field['key'], $key );
												if ( $is_render ) {
													?>
													<div class="field-wrap <?php echo esc_attr( $key . ( $is_disabled && $parent_disabled ? ' disabled' : '' ) ); ?>">
														<input type="hidden" name="bb_enabled_notification[<?php echo esc_attr( $field['key'] ); ?>][<?php echo esc_attr( $key ); ?>]" class="bs-styled-checkbox" value="no" />
														<input type="checkbox" id="<?php echo esc_attr( $field['key'] . '_' . $key ); ?>" name="bb_enabled_notification[<?php echo esc_attr( $field['key'] ); ?>][<?php echo esc_attr( $key ); ?>]" class="bs-styled-checkbox" value="yes" <?php checked( $v['is_checked'], 'yes' ); disabled( ( $is_disabled && $parent_disabled ), true ); ?> />
														<label for="<?php echo esc_attr( $field['key'] . '_' . $key ); ?>"><?php echo esc_html( $v['label'] ); ?></label>
													</div>
													<?php
												} else {
													?>
													<div class="field-wrap <?php echo esc_attr( $key ); ?>"> -- </div>
													<?php
												}
											}
										}
										?>
									</div>
								</div>
							<?php
						}
						echo '</div>';
					}
					?>
				</td>
			</tr>

			<?php
		}
		echo '</tbody></table>';
	}
}

/**
 * Callback fields for the notification warning.
 *
 * @since BuddyBoss 1.9.3
 */
function bb_admin_setting_callback_notification_warning() {
	echo '<p class="description notification-information bb-lab-notice">' .
		wp_kses_post(
			__( 'Notification Types are not supported when using the legacy notifications system.', 'buddyboss' )
		) .
	'</p>';
}

/**
 * Callback fields for the notification fields options.
 *
 * @since BuddyBoss 1.9.3
 *
 * @param array $field   Fieldset data.
 * @param bool  $checked Is checked or not.
 */
function bb_activate_notification( $field, $checked ) {
	$label        = ( ! empty( $field['admin_label'] ) ? $field['admin_label'] : $field['label'] );
	$tooltip_pos  = '';
	$tooltip_text = '';
	if ( ! empty( $field['notification_tooltip_text'] ) ) {
		$tooltip_pos  = 'up';
		$tooltip_text = $field['notification_tooltip_text'];
	}
	$disabled = ! empty( $field['notification_read_only'] );

	if ( ! empty( $field['notification_read_only'] ) && ! empty( $field['default'] ) && 'no' === $field['default'] ) {
		$checked = false;
	}
	?>

	<input name="bb_enabled_notification[<?php echo esc_attr( $field['key'] ); ?>][main]" type="hidden" value="no" />
	<span class="notification-settings-input"
		<?php
		if ( ! empty( $tooltip_pos ) ) {
			echo ' data-bp-tooltip-pos="' . esc_attr( $tooltip_pos ) . '"';
		}

		if ( ! empty( $tooltip_text ) ) {
			echo ' data-bp-tooltip="' . esc_attr( $tooltip_text ) . '"';
		}
		?>
	>
		<input class="bb-notification-checkbox" id="bb_enabled_notification_<?php echo esc_attr( $field['key'] ); ?>" name="bb_enabled_notification[<?php echo esc_attr( $field['key'] ); ?>][main]" type="checkbox" value="yes"
			<?php
			checked( $checked, 1 );
			disabled( $disabled, 1 );
			?>
		/>
	</span>
	<label class="notification-label" for="bb_enabled_notification_<?php echo esc_attr( $field['key'] ); ?>"><?php echo esc_html( $label ); ?></label>

	<?php
}

/**
 * Callback fields for the push notification platform pro not installed warning.
 *
 * @since BuddyBoss 2.0.4
 */
function bb_admin_setting_callback_push_notification_bbp_pro_not_installed() {
	echo '<p class="description notification-information bb-lab-notice">' .
		sprintf(
			wp_kses_post(
				/* translators: BuddyBoss Pro purchase link */
				__( 'Please install %1$s to use web push notifications on your site.', 'buddyboss' )
			),
			'<a href="' . esc_url( 'https://www.buddyboss.com/platform' ) . '" target="_blank">' . esc_html__( 'BuddyBoss Platform Pro', 'buddyboss' ) . '</a>'
		) .
	'</p>';
}

/**
 * Callback fields for the push notification platform pro older version installed warning.
 *
 * @since BuddyBoss 2.0.4
 */
function bb_admin_setting_callback_push_notification_bbp_pro_older_version_installed() {
	echo '<p class="description notification-information bb-lab-notice">' .
		sprintf(
			wp_kses_post(
				/* translators: BuddyBoss Pro purchase link */
				__( 'Please update %1$s to version 2.0.3 to use web push notifications on your site.', 'buddyboss' )
			),
			'<a target="_blank" href="' . esc_url( 'https://www.buddyboss.com/platform' ) . '">' . esc_html__( 'BuddyBoss Platform Pro', 'buddyboss' ) . '</a>'
		) .
	'</p>';
}

/**
 * Callback fields for the push notification lab preference warning.
 *
 * @since BuddyBoss 2.0.4
 */
function bb_admin_setting_callback_push_notification_lab_notification_preferences() {
	echo '<p class="description notification-information bb-lab-notice">' .
		wp_kses_post(
			__( 'Web Push Notifications are not supported when using the legacy notifications system.', 'buddyboss' )
		) .
	'</p>';
}

/**
 * Callback fields for the Messaging Notifications warning.
 *
 * @since BuddyBoss 2.1.4
 */
function bb_admin_setting_callback_messaging_notification_warning() {
	echo '<p class="description notification-information bp-new-notice-panel-notice">' .
		sprintf(
			wp_kses_post(
			/* translators: %s: Live Messages. */
				__( 'When using %s, we recommend enabling these settings to ensure the optimal experience for your members.', 'buddyboss' )
			),
			'<a href="' .
				esc_url(
					add_query_arg(
						array(
							'page' => 'bp-integrations',
							'tab'  => 'bb-pusher',
						),
						admin_url( 'admin.php' )
					)
				)
			. '">' . esc_html__( 'Live Messages', 'buddyboss' ) . '</a>'
		) .
		'</p>';
}

/**
 * Link to Messaging Notification tutorial.
 *
 * @since BuddyBoss 2.1.4
 */
function bb_messaging_notifications_tutorial() {
	?>

	<p>
		<a class="button" href="
		<?php
		echo esc_url(
			bp_get_admin_url(
				add_query_arg(
					array(
						'page'    => 'bp-help',
						'article' => '125952',
					),
					'admin.php'
				)
			)
		);
		?>
		">
			<?php esc_html_e( 'View Tutorial', 'buddyboss' ); ?>
		</a>
	</p>

	<?php
}

/**
 * Callback fields for the hide message notification fields options.
 *
 * @since BuddyBoss 2.1.4
 *
 * @return void
 */
function bb_admin_setting_callback_messaging_notification_fields() {

	// Bail if messages component is disabled.
	if ( ! bp_is_active( 'messages' ) ) {
		return;
	}

	// Get all defined time.
	$get_delay_times = bb_notification_get_digest_cron_times();
	$db_delay_time   = bb_get_delay_email_notifications_time();

	// Prepare the drop-down for time.
	$html = '<select name="time_delay_email_notification">';

	foreach ( $get_delay_times as $delay_time ) {
		$mins = (int) $delay_time['value'];

		$html .= sprintf(
			'<option value="%s" %s>%s</option>',
			$mins,
			( $db_delay_time === $mins ? 'selected="selected"' : '' ),
			$delay_time['label']
		);
	}
	$html .= '</select>';
	?>
	<table class="form-table render-hide-message-notification">
		<tbody>
			<tr>
				<th><?php echo esc_html__( 'Hide From Notifications', 'buddyboss' ); ?></th>
				<td>
					<input id="hide_message_notification" name="hide_message_notification" type="checkbox" value="1" <?php checked( bb_hide_messages_from_notification_enabled() ); ?> />
					<label for="hide_message_notification"><?php esc_html_e( 'Hide messages from notifications', 'buddyboss' ); ?></label>
					<p class="description"><?php esc_html_e( 'When enabled, notifications for group and private messages will not show in a member\'s list of notifications or be included in the count of unread notifications. However, notifications will still be sent externally (via email, web and/or app) and shown in a member\'s list of messages, as well as the count of unread messages.', 'buddyboss' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><?php echo esc_html__( 'Delay Email Notifications', 'buddyboss' ); ?></th>
				<td>
					<input id="delay_email_notification" name="delay_email_notification" type="checkbox" value="1" <?php checked( bb_delay_email_notifications_enabled() ); ?> />
					<label for="delay_email_notification"><?php esc_html_e( 'Delay email notifications for new messages', 'buddyboss' ); ?></label>
					<p class="description"><?php esc_html_e( 'When enabled, email notifications for new group and private messages will be delayed to allow time for members to read them on your site. After the delay, the emails will only be sent if the messages are still unread. If there are multiple unread messages in a conversation at the time of sending, they will be combined into a single email notification.', 'buddyboss' ); ?></p>

					<p class="description">
						<label for="time_delay_email_notification">
							<?php
							printf(
								wp_kses_post(
								/* translators: Permission validate select box. */
									__( 'Delay notifications for %s', 'buddyboss' )
								),
								$html // phpcs:ignore
							)
							?>
						</label>
					</p>
				</td>
			</tr>
		</tbody>
	</table>
	<?php
}
