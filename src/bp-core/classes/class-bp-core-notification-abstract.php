<?php
/**
 * BuddyBoss Notification Abstract Class.
 *
 * @package BuddyBoss\Core
 *
 * @since BuddyBoss 1.9.3
 */

defined( 'ABSPATH' ) || exit;

/**
 * Set up the Notification Abstract class.
 *
 * @since BuddyBoss 1.9.3
 */
abstract class BP_Core_Notification_Abstract {

	/**
	 * Preferences Group.
	 *
	 * @since BuddyBoss 1.9.3
	 *
	 * @var array
	 */
	private $prefernce_groups = array();

	/**
	 * Preferences.
	 *
	 * @since BuddyBoss 1.9.3
	 *
	 * @var array
	 */
	private $preferences = array();

	/**
	 * Notifications.
	 *
	 * @since BuddyBoss 1.9.3
	 *
	 * @var array
	 */
	private $notifications = array();

	/** Email Types.
	 *
	 * @since BuddyBoss 1.9.3
	 *
	 * @var array
	 */
	private $email_types = array();

	/**
	 * Notification load default priority.
	 *
	 * @since BuddyBoss 1.9.3
	 *
	 * @var int
	 */
	private $priority = 20;

	/**
	 * Notifications filters.
	 *
	 * @since BuddyBoss 1.9.3
	 *
	 * @var array
	 */
	private $notifications_filters = array();

	/**
	 * Initialize.
	 *
	 * @since BuddyBoss 1.9.3
	 *
	 * @return void
	 */
	final public function start() {
		$this->load();
		add_filter( 'bb_register_notification_preferences', array( $this, 'register_notification_preferences' ) );
		add_filter( 'bb_register_notifications', array( $this, 'register_notifications' ) );
		add_filter( 'bp_email_get_schema', array( $this, 'email_schema' ), 999 );
		add_filter( 'bp_email_get_type_schema', array( $this, 'email_type_schema' ), 999 );
		add_filter( 'bb_register_notification_emails', array( $this, 'register_notification_emails' ), 999 );
		add_filter( 'bb_notifications_get_component_notification', array( $this, 'get_notifications_for_user' ), 9999, 9 );
		add_filter( 'bp_notifications_get_notifications_for_user', array( $this, 'get_notifications_for_user' ), 9999, 9 );
		add_filter( 'bp_notifications_get_registered_components', array( $this, 'get_registered_components' ), 99, 1 );

		// Register the Notifications filters.
		add_action( 'bp_nouveau_notifications_init_filters', array( $this, 'register_notification_filters' ) );
	}

	/**
	 * Abstract method to call the other methods inside it.
	 *
	 * @since BuddyBoss 1.9.3
	 *
	 * @return mixed|void
	 */
	abstract public function load();

	/************************************ Filters ************************************/

	/**
	 * Register notifications.
	 *
	 * @since BuddyBoss 1.9.3
	 *
	 * @param array $notifications Notification array.
	 *
	 * @return array|mixed
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
	 * @since BuddyBoss 1.9.3
	 *
	 * @param array $notifications List of notifications.
	 *
	 * @return array
	 */
	public function register_notifications( array $notifications ) {

		$notifications = array_merge( $notifications, $this->notifications );

		return $notifications;

	}

	/**
	 * Email Schema.
	 *
	 * @since BuddyBoss 1.9.3
	 *
	 * @param array $schema List of schema.
	 *
	 * @return array|mixed
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
	 * @since BuddyBoss 1.9.3
	 *
	 * @param array $type_schema List of types schema.
	 *
	 * @return array
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
	 * @since BuddyBoss 1.9.3
	 *
	 * @param array $emails Registered Emails.
	 *
	 * @return array
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
	 * @since BuddyBoss 1.9.3
	 *
	 * @param string $content               Component action.
	 * @param int    $item_id               Notification item ID.
	 * @param int    $secondary_item_id     Notification secondary item ID.
	 * @param int    $total_items           Number of notifications with the same action.
	 * @param string $format                Format of return. Either 'string' or 'object'.
	 * @param string $component_action_name Canonical notification action.
	 * @param string $component_name        Notification component ID.
	 * @param int    $notification_id       Notification ID.
	 * @param string $screen                Notification Screen type.
	 *
	 * @return string|array If $format is 'string', return a string of the notification content.
	 *                      If $format is 'object', return an array formatted like:
	 *                      array( 'text' => 'CONTENT', 'link' => 'LINK' ).
	 */
	public function get_notifications_for_user( $content, $item_id, $secondary_item_id, $total_items, $format, $component_action_name, $component_name, $notification_id, $screen = 'web' ) {

		$custom_content = $this->format_notification( $content, $item_id, $secondary_item_id, $total_items, $component_action_name, $component_name, $notification_id, $screen );

		// Validate the return value & return if validated.
		if (
			! empty( $custom_content ) &&
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
	 * @since BuddyPress 1.9.3
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
	 * @since BuddyBoss 1.9.3
	 *
	 * @param string $group_key         Group key.
	 * @param string $group_label       Group label.
	 * @param string $group_admin_label Group admin label.
	 * @param int    $priority          Priority of the group.
	 *
	 * @return void
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
	 * @since BuddyBoss 1.9.3
	 *
	 * @param string $notification_type        Notification Type key.
	 * @param string $notification_label       Notification label.
	 * @param string $notification_admin_label Notification admin label.
	 * @param string $notification_group       Notification group.
	 * @param bool   $default                  Default status.
	 *
	 * @return void
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
	 * @since BuddyBoss 1.9.3
	 *
	 * @param string $component         Component name.
	 * @param string $component_action  Component action.
	 * @param string $notification_type Notification Type key.
	 * @param string $icon_class        Notification Small Icon.
	 *
	 * @return void
	 */
	final public function register_notification( string $component, string $component_action, string $notification_type, string $icon_class = '' ) {
		$this->notifications[] = array(
			'component'         => $component,
			'component_action'  => $component_action,
			'notification_type' => $notification_type,
			'icon_class'        => $icon_class,
		);
	}

	/**
	 * Add email schema.
	 *
	 * @since BuddyBoss 1.9.3
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
	 * @since BuddyBoss 1.9.3
	 *
	 * @param string $content               Notification content.
	 * @param int    $item_id               Notification item ID.
	 * @param int    $secondary_item_id     Notification secondary item ID.
	 * @param int    $total_items           Number of notifications with the same action.
	 * @param string $component_action_name Canonical notification action.
	 * @param string $component_name        Notification component ID.
	 * @param int    $notification_id       Notification ID.
	 * @param string $screen                Notification Screen type.
	 *
	 * @return array {
	 *  'link' => '' // Notification URL.
	 *  'text' => '' // Notification Text
	 * }
	 */
	abstract public function format_notification( $content, $item_id, $secondary_item_id, $total_items, $component_action_name, $component_name, $notification_id, $screen );

	/**
	 * Register the notification filters.
	 *
	 * @since BuddyBoss 1.9.3
	 */
	public function register_notification_filters() {
		if ( ! empty( $this->notifications_filters ) && ! empty( $this->notifications ) ) {
			$filtered_notifications = array_column( bb_register_notifications(), 'component_action', 'notification_type' );
			foreach ( $this->notifications_filters as $filters ) {
				$label             = ( isset( $filters['label'] ) ? $filters['label'] : '' );
				$position          = ( isset( $filters['position'] ) ? $filters['position'] : 0 );
				$n_types           = ( isset( $filters['notification_types'] ) ? $filters['notification_types'] : '' );
				$component_actions = array();

				if ( ! empty( $n_types ) ) {
					if ( is_array( $n_types ) ) {
						foreach ( $n_types as $k => $type ) {
							$component_action = isset( $filtered_notifications[ $type ] ) ? $filtered_notifications[ $type ] : '';
							if ( ! empty( $component_action ) ) {
								$component_actions[] = $component_action;
							}
						}
					} else {
						$component_action = isset( $filtered_notifications[ $n_types ] ) ? $filtered_notifications[ $n_types ] : '';
						if ( ! empty( $component_action ) ) {
							$component_actions[] = $component_action;
						}
					}
				}

				if ( ! empty( $component_actions ) && ! empty( $label ) ) {
					bp_nouveau_notifications_register_filter(
						array(
							'id'       => ( is_array( $component_actions ) ? implode( ',', $component_actions ) : $component_actions ),
							'label'    => $label,
							'position' => $position,
						)
					);
				}
			}
		}
	}

	/**
	 * Register Notification Filter.
	 *
	 * @since BuddyBoss 1.9.3
	 *
	 * @param string $notification_label    Notification label.
	 * @param array  $notification_types    Notification types.
	 * @param int    $notification_position Notification position.
	 *
	 * @return void
	 */
	public function register_notification_filter( string $notification_label, array $notification_types, int $notification_position = 0 ) {
		$this->notifications_filters[] = array(
			'label'              => $notification_label,
			'notification_types' => $notification_types,
			'position'           => $notification_position,
		);
	}

}

