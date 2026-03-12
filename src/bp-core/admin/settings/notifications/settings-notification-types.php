<?php
/**
 * BuddyBoss Admin Settings - Notification Types Panel.
 *
 * Registers sections and fields for the Notification Types side panel.
 * This includes the dynamic notification types table and messaging notification fields.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Notification Types panel sections and fields.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_notifications_register_types_panel_fields() {

	// Only show notification types when using modern notification system.
	if ( bb_enabled_legacy_email_preference() ) {

		// -------------------------------------------------------------------------
		// SECTION: Legacy Notice
		// -------------------------------------------------------------------------
		bb_register_feature_section(
			'notifications',
			'notification_types',
			'notification_types_legacy',
			array(
				'title' => __( 'Notification Types', 'buddyboss' ),
				'order' => 10,
			)
		);

		bb_register_feature_field(
			'notifications',
			'notification_types',
			'notification_types_legacy',
			array(
				'name'    => '_bb_notification_types_legacy_notice',
				'label'   => '',
				'type'    => 'notice',
				'default' => __( 'Notification Types are not supported when using the legacy notifications system.', 'buddyboss' ),
				'order'   => 10,
			)
		);

		return;
	}

	// -------------------------------------------------------------------------
	// SECTION: Notification Types
	// -------------------------------------------------------------------------
	bb_register_feature_section(
		'notifications',
		'notification_types',
		'notification_types',
		array(
			'title'       => __( 'Notification Types', 'buddyboss' ),
			'description' => __( 'Select which types of notifications are sent to members when specific actions happen on your site. When a notification is disabled, it will not be generated for any member. Members can configure which notifications they receive via email, web or app in their Notification Preferences.', 'buddyboss' ),
			'order'       => 10,
		)
	);

	// Build notification types data for the custom field.
	$all_notifications    = bb_register_notification_preferences();
	$enabled_notification = bp_get_option( 'bb_enabled_notification', array() );
	$notification_groups  = array();

	if ( ! empty( $all_notifications ) ) {
		foreach ( $all_notifications as $group_key => $field_group ) {
			$group_data = array(
				'key'         => $group_key,
				'admin_label' => isset( $field_group['admin_label'] ) ? $field_group['admin_label'] : '',
				'fields'      => array(),
			);

			if ( ! empty( $field_group['fields'] ) ) {
				foreach ( $field_group['fields'] as $field ) {
					$checked = isset( $field['default'] ) && 'yes' === $field['default'];

					if ( array_key_exists( $field['key'], $enabled_notification ) ) {
						$checked = isset( $enabled_notification[ $field['key'] ]['main'] ) && 'yes' === $enabled_notification[ $field['key'] ]['main'];
					}

					// Get email template info.
					$registered_emails = bb_register_notification_email_templates( $field['key'] );
					$email_template    = array(
						'has_templates' => ! empty( $registered_emails ),
						'count'         => count( $registered_emails ),
					);

					if ( ! empty( $registered_emails ) ) {
						$total_email_count = 0;
						foreach ( $registered_emails as $email_type ) {
							$total_email_count += get_terms(
								array(
									'taxonomy' => bp_get_email_tax_type(),
									'slug'     => $email_type,
									'fields'   => 'count',
								)
							);
						}

						$email_template['existing_count'] = $total_email_count;
						$email_template['missing']        = count( $registered_emails ) > $total_email_count;

						if ( ! $email_template['missing'] ) {
							$posts = get_posts(
								array(
									'showposts' => 1,
									'post_type' => bp_get_email_post_type(),
									'tax_query' => array(
										array(
											'taxonomy' => bp_get_email_tax_type(),
											'field'    => 'slug',
											'terms'    => $registered_emails,
										),
									),
									'fields'    => 'ids',
								)
							);

							if ( count( $registered_emails ) === 1 && ! empty( $posts ) ) {
								$email_template['url'] = get_edit_post_link( current( $posts ), 'raw' );
							} else {
								$email_template['url'] = add_query_arg(
									array(
										'post_type' => bp_get_email_post_type(),
										'taxonomy'  => bp_get_email_tax_type(),
										'terms'     => implode( ',', $registered_emails ),
									),
									admin_url( 'edit.php' )
								);
							}
						} else {
							$email_template['url'] = get_admin_url(
								bp_get_root_blog_id(),
								'edit.php?post_type=' . bp_get_email_post_type() . '&popup=yes'
							);
						}
					}

					// Get preference sub-types (email, web, app).
					$sub_types = array();
					$options   = bb_notification_preferences_types( $field );
					if ( ! empty( $options ) ) {
						foreach ( $options as $key => $v ) {
							$parent_disabled = ! empty( $field['notification_read_only'] ) && true === $field['notification_read_only'];
							$is_disabled     = apply_filters( 'bb_is_' . $field['key'] . '_' . $key . '_preference_enabled', ! $checked );
							$is_render       = apply_filters( 'bb_is_' . $field['key'] . '_' . $key . '_preference_type_render', $v['is_render'], $field['key'], $key );

							$sub_types[ $key ] = array(
								'label'      => $v['label'],
								'is_checked' => $v['is_checked'],
								'is_render'  => $is_render,
								'disabled'   => $is_disabled && $parent_disabled,
							);
						}
					}

					$group_data['fields'][] = array(
						'key'            => $field['key'],
						'label'          => ! empty( $field['admin_label'] ) ? $field['admin_label'] : $field['label'],
						'checked'        => $checked,
						'read_only'      => ! empty( $field['notification_read_only'] ),
						'tooltip'        => ! empty( $field['notification_tooltip_text'] ) ? $field['notification_tooltip_text'] : '',
						'email_template' => $email_template,
						'sub_types'      => $sub_types,
					);
				}
			}

			$notification_groups[] = $group_data;
		}
	}

	// FIELD: Notification Types (custom field type for React rendering).
	bb_register_feature_field(
		'notifications',
		'notification_types',
		'notification_types',
		array(
			'name'                => 'bb_enabled_notification',
			'label'               => __( 'Notification Types', 'buddyboss' ),
			'type'                => 'notification_types',
			'default'             => $enabled_notification,
			'sanitize_callback'   => 'bb_notifications_sanitize_types_noop',
			'notification_groups' => $notification_groups,
			'order'               => 10,
		)
	);

	/**
	 * Fires after Notification Types section fields are registered.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	do_action( 'bb_notifications_types_after_settings_fields' );

	// -------------------------------------------------------------------------
	// SECTION: Messaging Notifications (conditional)
	// -------------------------------------------------------------------------
	if ( bp_is_active( 'messages' ) ) {

		bb_register_feature_section(
			'notifications',
			'notification_types',
			'messaging_notifications',
			array(
				'title'       => __( 'Messaging Notifications', 'buddyboss' ),
				'description' => '',
				'order'       => 20,
			)
		);

		// Show warning notice if pusher is enabled with specific conditions.
		if (
			! bb_hide_messages_from_notification_enabled() &&
			! bb_delay_email_notifications_enabled() &&
			function_exists( 'bb_pusher_is_enabled' ) &&
			bb_pusher_is_enabled() &&
			function_exists( 'bb_pusher_is_feature_enabled' ) &&
			true === bb_pusher_is_feature_enabled( 'live-messaging' )
		) {
			bb_register_feature_field(
				'notifications',
				'notification_types',
				'messaging_notifications',
				array(
					'name'    => '_bb_messaging_notification_warning',
					'label'   => '',
					'type'    => 'notice',
					'default' => sprintf(
						/* translators: %s: Live Messages link. */
						__( 'When using %s, we recommend enabling these settings to ensure the optimal experience for your members.', 'buddyboss' ),
						'<a href="' . esc_url(
							add_query_arg(
								array(
									'page' => 'bp-integrations',
									'tab'  => 'bb-pusher',
								),
								admin_url( 'admin.php' )
							)
						) . '">' . __( 'Live Messages', 'buddyboss' ) . '</a>'
					),
					'order'   => 10,
				)
			);
		}

		// FIELD: Hide From Notifications.
		bb_register_feature_field(
			'notifications',
			'notification_types',
			'messaging_notifications',
			array(
				'name'              => 'hide_message_notification',
				'label'             => __( 'Hide From Notifications', 'buddyboss' ),
				'type'              => 'toggle',
				'description'       => __( 'Hide messages from notifications', 'buddyboss' ),
				'help_text'         => __( 'When enabled, notifications for group and private messages will not show in a member\'s list of notifications or be included in the count of unread notifications. However, notifications will still be sent externally (via email, web and/or app) and shown in a member\'s list of messages, as well as the count of unread messages.', 'buddyboss' ),
				'default'           => bb_hide_messages_from_notification_enabled(),
				'sanitize_callback' => 'intval',
				'order'             => 20,
			)
		);

		// Build delay time options.
		$delay_time_options = array();
		if ( function_exists( 'bb_notification_get_digest_cron_times' ) ) {
			foreach ( bb_notification_get_digest_cron_times() as $time ) {
				$delay_time_options[] = array(
					'label' => $time['label'],
					'value' => (int) $time['value'],
				);
			}
		}

		// FIELD: Delay Email Notifications.
		bb_register_feature_field(
			'notifications',
			'notification_types',
			'messaging_notifications',
			array(
				'name'                 => 'delay_email_notification',
				'label'                => __( 'Delay Email Notifications', 'buddyboss' ),
				'type'                 => 'toggle',
				// translators: %s: Delay time select control.
				'description'          => __( 'Delay email notifications for new messages', 'buddyboss' ),
				'help_text'            => __( 'When enabled, email notifications for new group and private messages will be delayed to allow time for members to read them on your site. After the delay, the emails will only be sent if the messages are still unread. If there are multiple unread messages in a conversation at the time of sending, they will be combined into a single email notification.', 'buddyboss' ),
				'default'              => bb_delay_email_notifications_enabled(),
				'sanitize_callback'    => 'intval',
				'description_controls' => array(
					array(
						'type'              => 'select',
						'name'              => 'time_delay_email_notification',
						'default'           => bb_get_delay_email_notifications_time(),
						'options'           => $delay_time_options,
						'sanitize_callback' => 'bb_notifications_sanitize_delay_time',
					),
				),
				'order'                => 30,
			)
		);

		/**
		 * Fires after Messaging Notifications section fields are registered.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		do_action( 'bb_notifications_messaging_after_settings_fields' );
	}
}
