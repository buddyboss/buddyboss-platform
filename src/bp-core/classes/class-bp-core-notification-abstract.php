<?php
/**
 * BuddyBoss Notification Abstract Class.
 *
 * @package BuddyBoss\Core
 *
 * @since BuddyBoss [BBVERSION]
 */

defined( 'ABSPATH' ) || exit;

/**
 * Set up the Notification Abstract class.
 *
 * @since BuddyBoss [BBVERSION]
 */
abstract class BP_Core_Notification_Abstract {

	/**
	 * Preferences Group.
	 *
	 * @var array
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	private $prefernce_groups = array();

	/**
	 * Preferences.
	 *
	 * @var array
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	private $preferences = array();

	/**
	 * Notifications.
	 *
	 * @var array
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	private $notifications = array();

	/** Email Types.
	 *
	 * @var array
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	private $email_types = array();

	/**
	 * Notification load default priority.
	 *
	 * @var int
	 */
	private $priority = 20;

	/**
	 * Initialize.
	 *
	 * @return void
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	final public function start() {
		$this->load();
		add_filter( 'bb_register_notification_preferences', array( $this, 'register_notification_preferences' ) );
		add_filter( 'bb_register_notifications', array( $this, 'register_notifications' ) );
		add_filter( 'bp_email_get_schema', array( $this, 'email_schema' ), 999 );
		add_filter( 'bp_email_get_type_schema', array( $this, 'email_type_schema' ), 999 );
		add_filter( 'bb_register_notification_emails', array( $this, 'register_notification_emails' ), 999 );
		add_filter( 'bp_notifications_get_notifications_for_user', array( $this, 'get_notifications_for_user' ), 99, 8 );
		add_filter( 'bp_notifications_get_registered_components', array( $this, 'get_registered_components' ), 99, 1 );

		// Register the Notifications filters.
		add_action( 'bp_nouveau_notifications_init_filters', array( $this, 'register_notification_filters' ) );
	}

	/**
	 * Abstract method to call the other methods inside it.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return mixed|void
	 */
	abstract public function load();

	/************************************ Filters ************************************/

	/**
	 * Register notifications.
	 *
	 * @param array $notifications Notification array.
	 *
	 * @return array|mixed
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function register_notification_preferences( array $notifications ) {

		$all_notifications = array();
		$all_email_types   = array();

		if ( ! empty( $this->notifications ) ) {
			foreach ( $this->notifications as $data ) {
				if ( ! empty( $data['notification_type'] ) ) {
					$notification_key = $data['notification_type'];
					unset( $data['notification_type'] );
					if ( array_key_exists( $notification_key, $all_notifications ) ) {
						$all_notifications[ $notification_key ][] = $data;
					} else {
						$all_notifications[ $notification_key ] = array( $data );
					}
				}
			}
		}

		if ( $this->email_types ) {
			foreach ( $this->email_types as $email ) {
				if ( ! empty( $email ) ) {
					$notification_key = $email['notification_type'];
					unset( $email['notification_type'] );
					if ( array_key_exists( $notification_key, $all_email_types ) ) {
						$all_email_types[ $notification_key ][] = $email;
					} else {
						$all_email_types[ $notification_key ] = array( $email );
					}
				}
			}
		}

		if ( ! empty( $this->preferences ) ) {
			foreach ( $this->preferences as $preference ) {
				$notifications[ $preference['notification_group'] ]['fields'][] = array(
					'key'           => $preference['notification_type'],
					'label'         => $preference['notification_label'],
					'admin_label'   => ( isset( $preference['notification_admin_label'] ) && ! empty( $preference['notification_admin_label'] ) ? $preference['notification_admin_label'] : $preference['notification_label'] ),
					'default'       => ( true === $preference['notification_default'] ? 'yes' : 'no' ),
					'notifications' => ( ! empty( $all_notifications ) && isset( $all_notifications[ $preference['notification_type'] ] ) ) ? $all_notifications[ $preference['notification_type'] ] : array(),
					'email_types'   => ( ! empty( $all_email_types ) && isset( $all_email_types[ $preference['notification_type'] ] ) ) ? $all_email_types[ $preference['notification_type'] ] : array(),
				);

				if ( 'other' === $preference['notification_group'] ) {
					$notifications[ $preference['notification_group'] ]['label']       = esc_html__( 'Other', 'buddyboss' );
					$notifications[ $preference['notification_group'] ]['admin_label'] = esc_html__( 'Other Notifications', 'buddyboss' );
					$notifications[ $preference['notification_group'] ]['priority']    = $this->priority;
				}
			}
		}

		if ( ! empty( $this->prefernce_groups ) ) {
			foreach ( $this->prefernce_groups as $group ) {
				$notifications[ $group['group_key'] ]['label']       = $group['group_label'];
				$notifications[ $group['group_key'] ]['admin_label'] = ( isset( $group['group_admin_label'] ) && ! empty( $group['group_admin_label'] ) ? $group['group_admin_label'] : $group['group_label'] );
				$notifications[ $group['group_key'] ]['priority']    = $group['priority'];
			}
		}

		$priority = array_column( $notifications, 'priority' );

		array_multisort( $priority, SORT_ASC, $notifications );

		return $notifications;
	}

	/**
	 * Register the notifications.
	 *
	 * @param array $notifications List of notifications.
	 *
	 * @return array
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function register_notifications( array $notifications ) {

		$notifications = array_merge( $notifications, $this->notifications );

		return $notifications;

	}

	/**
	 * Email Schema.
	 *
	 * @param array $schema List of schema.
	 *
	 * @return array|mixed
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function email_schema( array $schema ) {

		if ( ! empty( $this->email_types ) ) {
			$new_schema = array_column( $this->email_types, 'args', 'email_type' );
			if ( ! empty( $new_schema ) ) {
				$schema = array_merge( $schema, $new_schema );
			}
		}

		return $schema;
	}

	/**
	 * Email Type Schema.
	 *
	 * @param array $type_schema List of types schema.
	 *
	 * @return array
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function email_type_schema( array $type_schema ) {

		if ( ! empty( $this->email_types ) ) {
			$new_schema = array_column( $this->email_types, 'schema', 'email_type' );
			if ( ! empty( $new_schema ) ) {
				$type_schema = array_merge( $type_schema, $new_schema );
			}
		}

		return $type_schema;
	}

	/**
	 * Register email with associated preference type.
	 *
	 * @param array $emails Registered Emails.
	 *
	 * @return array
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function register_notification_emails( array $emails ) {
		if ( ! empty( $this->email_types ) ) {
			foreach ( $this->email_types as $key => $val ) {
				if ( ! empty( $val['notification_type'] ) && isset( $emails[ $val['notification_type'] ] ) ) {
					if ( ! in_array( $key, $emails[ $val['notification_type'] ], true ) ) {
						$emails[ $val['notification_type'] ][] = $key;
					}
				} elseif ( ! empty( $val['notification_type'] ) ) {
					$emails[ $val['notification_type'] ][] = $key;
				}
			}
		}

		return $emails;
	}

	/**
	 * Filters the notification content for notifications.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $content               Component action.
	 * @param int    $item_id               Notification item ID.
	 * @param int    $secondary_item_id     Notification secondary item ID.
	 * @param int    $action_item_count     Number of notifications with the same action.
	 * @param string $format                Format of return. Either 'string' or 'object'.
	 * @param string $component_action_name Canonical notification action.
	 * @param string $component_name        Notification component ID.
	 * @param int    $notification_id       Notification ID.
	 *
	 * @return string|array If $format is 'string', return a string of the notification content.
	 *                      If $format is 'object', return an array formatted like:
	 *                      array( 'text' => 'CONTENT', 'link' => 'LINK' ).
	 */
	public function get_notifications_for_user( $content, $item_id, $secondary_item_id, $action_item_count, $format, $component_action_name, $component_name, $notification_id ) {

		$custom_content = $this->format_notification( $item_id, $secondary_item_id, $action_item_count, $format, $component_action_name, $component_name, $notification_id );

		// Validate the return value & return if validated.
		if (
			is_array( $custom_content ) &&
			isset( $custom_content['text'] ) &&
			isset( $custom_content['link'] )
		) {
			if ( 'string' === $format ) {
				if ( empty( $custom_content['link'] ) ) {
					$content = esc_html( $custom_content['text'] );
				} else {
					$content = '<a href="' . esc_url( $custom_content['link'] ) . '">' . esc_html( $custom_content['text'] ) . '</a>';
				}
			} else {
				$content = array(
					'text' => $custom_content['text'],
					'link' => $custom_content['link'],
				);
			}
		}

		return $content;
	}

	/**
	 * Filters active components with registered notifications callbacks.
	 *
	 * @since BuddyPress [BBVERSION]
	 *
	 * @param array $component_names   Array of registered component names.
	 */
	public function get_registered_components( $component_names ) {

		if ( ! empty( $this->notifications ) ) {
			$custom_component = array_unique( array_column( $this->notifications, 'component' ) );

			if ( ! empty( $custom_component ) ) {
				$component_names = array_unique( array_merge( $component_names, $custom_component ) );
			}
		}

		return $component_names;
	}

	/************************************ Actions ************************************/


	/************************************ Functions ************************************/

	/**
	 * Register Notification Group.
	 *
	 * @param string $group_key         Group key.
	 * @param string $group_label       Group label.
	 * @param string $group_admin_label Group admin label.
	 * @param int    $priority          Priority of the group.
	 *
	 * @return void
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	final public function register_notification_group( string $group_key, string $group_label, string $group_admin_label = '', int $priority = 0 ) {
		$this->prefernce_groups[] = array(
			'group_key'         => $group_key,
			'group_label'       => $group_label,
			'group_admin_label' => $group_admin_label,
			'priority'          => ( 0 === $priority ? $this->priority : $priority ),
		);
	}

	/**
	 * Register Notification Type.
	 *
	 * @param string $notification_type        Notification Type key.
	 * @param string $notification_label       Notification label.
	 * @param string $notification_admin_label Notification admin label.
	 * @param string $notification_group       Notification group.
	 * @param bool   $default                  Default status.
	 *
	 * @return void
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	final public function register_notification_type( string $notification_type, string $notification_label, string $notification_admin_label = '', string $notification_group = 'other', bool $default = true ) {
		$this->preferences[] = array(
			'notification_type'        => $notification_type,
			'notification_label'       => $notification_label,
			'notification_admin_label' => $notification_admin_label,
			'notification_group'       => $notification_group,
			'notification_default'     => $default,
		);
	}

	/**
	 * Register notification.
	 *
	 * @param string $component         Component name.
	 * @param string $component_action  Component action.
	 * @param string $notification_type Notification Type key.
	 *
	 * @return void
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	final public function register_notification( string $component, string $component_action, string $notification_type, bool $notification_filter = true, string $notification_filter_label = '', int $notification_position = 0 ) {
		$this->notifications[] = array(
			'component'           => $component,
			'component_action'    => $component_action,
			'notification_type'   => $notification_type,
			'notification_filter' => $notification_filter,
			'id'                  => $component_action,
			'label'               => $notification_filter_label,
			'position'            => $notification_position,
		);
	}

	/**
	 * Add email schema.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $email_type        Type of email being sent.
	 * @param array  $args              Email arguments.
	 * @param string $notification_type Notification Type key.
	 */
	final public function register_email_type( string $email_type, array $args, string $notification_type ) {
		$this->email_types[ $email_type ] = array(
			'email_type'        => $email_type,
			'args'              => array(
				'post_title'   => ( $args['email_title'] ?? '' ),
				'post_content' => ( $args['email_content'] ?? '' ),
				'post_excerpt' => ( $args['email_plain_content'] ?? '' ),
				'multisite'    => ( $args['multisite'] ?? '' ),
			),
			'schema'            => array(
				'description' => ( $args['situation_label'] ?? '' ),
				'unsubscribe' => array(
					'meta_key' => $notification_type,
					'message'  => ( $args['unsubscribe_text'] ?? '' ),
				),
			),
			'notification_type' => $notification_type,
		);
	}

	/**
	 * Format the notifications.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param int    $item_id               Notification item ID.
	 * @param int    $secondary_item_id     Notification secondary item ID.
	 * @param int    $action_item_count     Number of notifications with the same action.
	 * @param string $format                Format of return. Either 'string' or 'object'.
	 * @param string $component_action_name Canonical notification action.
	 * @param string $component_name        Notification component ID.
	 * @param int    $notification_id       Notification ID.
	 *
	 * @return array {
	 *  'link' => '' // Notification URL.
	 *  'text' => '' // Notification Text
	 * }
	 */
	abstract public function format_notification( $item_id, $secondary_item_id, $action_item_count, $format, $component_action_name, $component_name, $notification_id );

	/**
	 * Register the notification filters.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function register_notification_filters() {

		if ( ! empty( $this->notifications ) ) {
			foreach ( $this->notifications as $filter ) {

				// Check admin settings enabled or not.
				if ( isset( $filter ) && isset( $filter['notification_filter'] ) && $filter['notification_filter'] && isset( $filter['id'] ) && isset( $filter['label'] ) && bb_get_modern_notification_admin_settings_is_enabled( $filter['notification_type'], $filter['component'] ) && bp_is_active( 'notifications' ) ) {
					unset( $filter['notification_type'] );
					unset( $filter['notification_label'] );
					unset( $filter['notification_admin_label'] );
					unset( $filter['notification_group'] );
					unset( $filter['notification_default'] );
					unset( $filter['notification_filter'] );
					bp_nouveau_notifications_register_filter( $filter );
				}
			}
		}

	}

}

