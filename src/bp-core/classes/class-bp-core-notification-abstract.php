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
	 * Subscriptions.
	 *
	 * @since BuddyBoss 2.2.6
	 *
	 * @var array
	 */
	private $subscriptions = array();


	/**
	 * If set bypass the subscription validation.
	 *
	 * @since BuddyBoss 2.2.8
	 *
	 * @var bool
	 */
	public static $no_validate = false;

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
		add_filter( 'bb_register_subscriptions_types', array( $this, 'registered_subscriptions_types' ), 99, 1 );

		// Register the Notifications filters.
		add_action( 'bp_nouveau_notifications_init_filters', array( $this, 'register_notification_filters' ) );

		// Register callback function validate subscription request.
		add_filter( 'bb_subscriptions_validate_before_save', array( $this, 'bb_subscriptions_validate_request' ), 10, 2 );
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
					'key'                       => $preference['notification_type'],
					'label'                     => $preference['notification_label'],
					'admin_label'               => ( isset( $preference['notification_admin_label'] ) && ! empty( $preference['notification_admin_label'] ) ? $preference['notification_admin_label'] : $preference['notification_label'] ),
					'default'                   => ( true === $preference['notification_default'] ? 'yes' : 'no' ),
					'notifications'             => ( ! empty( $all_notifications ) && isset( $all_notifications[ $preference['notification_type'] ] ) ) ? $all_notifications[ $preference['notification_type'] ] : array(),
					'email_types'               => ( ! empty( $all_email_types ) && isset( $all_email_types[ $preference['notification_type'] ] ) ) ? $all_email_types[ $preference['notification_type'] ] : array(),
					'notification_read_only'    => (bool) $preference['notification_read_only'],
					'notification_tooltip_text' => $preference['notification_tooltip_text'],
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
	 * Register the subscription type
	 *
	 * @since BuddyBoss 2.2.6
	 *
	 * @param array $types Subscription types.
	 *
	 * @return array
	 */
	public function registered_subscriptions_types( array $types ) {

		if ( ! empty( $this->subscriptions ) ) {

			$filtered_preferences     = $this->bb_get_subscription_filtered_notification_preferences();
			$notification_preferences = array_column( $filtered_preferences, 'notification_read_only', 'notification_type' );

			foreach ( $this->subscriptions as $type ) {
				if (
					! empty( $type['notification_type'] ) &&
					! is_array( $type['notification_type'] ) &&
					isset( $notification_preferences[ $type['notification_type'] ] )
				) {
					$types[ $type['subscription_type'] ] = $type;
				} elseif (
					! empty( $type['notification_type'] ) &&
					is_array( $type['notification_type'] ) &&
					! key_exists( $type['subscription_type'], $types ) &&
					array_filter( array_map( array( $this, 'bb_filter_read_only_subscription' ), $type['notification_type'] ) )
				) {
					$types[ $type['subscription_type'] ] = $type;
				} elseif (
					true === self::$no_validate &&
					! key_exists( $type['subscription_type'], $types )
				) {
					$types[ $type['subscription_type'] ] = $type;
				}
			}
		}

		return $types;
	}

	/**
	 * Check the subscription is enabled or not from preferences.
	 *
	 * @since BuddyBoss 2.2.6
	 *
	 * @param string $notification_type Notification type.
	 *
	 * @return bool
	 */
	protected function bb_filter_read_only_subscription( $notification_type ) {
		$filtered_preferences     = $this->bb_get_subscription_filtered_notification_preferences();
		$notification_preferences = array_column( $filtered_preferences, 'notification_read_only', 'notification_type' );
		return isset( $notification_preferences[ $notification_type ] );
	}

	/**
	 * Filtered the notification preferences to use for subscription.
	 *
	 * @since BuddyBoss 2.2.6
	 *
	 * @return array
	 */
	protected function bb_get_subscription_filtered_notification_preferences() {
		return array_map(
			function ( $preference ) {
				if (
					(
						isset( $preference['notification_read_only'], $preference['notification_default'] ) &&
						true === (bool) $preference['notification_read_only'] &&
						true === (bool) $preference['notification_default']
					) ||
					(
						! isset( $preference['notification_read_only'] ) ||
						false === (bool) $preference['notification_read_only']
					)
				) {
					return $preference;
				}
			},
			$this->preferences
		);
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
					'text'  => $custom_content['text'],
					'link'  => $custom_content['link'],
					'title' => ( isset( $custom_content['title'] ) ? $custom_content['title'] : '' ),
					'image' => ( isset( $custom_content['image'] ) ? $custom_content['image'] : '' ),
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
	 * @param string $notification_type         Notification Type key.
	 * @param string $notification_label        Notification label.
	 * @param string $notification_admin_label  Notification admin label.
	 * @param string $notification_group        Notification group.
	 * @param bool   $default                   Default status.
	 * @param bool   $notification_read_only    Notification is read only or not.
	 * @param string $notification_tooltip_text Notification setting tooltip text.
	 *
	 * @return void
	 */
	final public function register_notification_type( string $notification_type, string $notification_label, string $notification_admin_label = '', string $notification_group = 'other', bool $default = true, bool $notification_read_only = false, string $notification_tooltip_text = '' ) {
		$this->preferences[] = array(
			'notification_type'         => $notification_type,
			'notification_label'        => $notification_label,
			'notification_admin_label'  => $notification_admin_label,
			'notification_group'        => $notification_group,
			'notification_default'      => $default,
			'notification_read_only'    => $notification_read_only,
			'notification_tooltip_text' => $notification_tooltip_text,
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

	/**
	 * Register Subscription Type.
	 *
	 * @param array $args {
	 *     Used to display the subscription block.
	 *
	 *     @type array  $label               Required. Array of labels with singular and plural.
	 *     @type string $subscription_type   Required. Subscription type key.
	 *     @type string $items_callback      Optional. The render callback function.
	 *     @type string $send_callback       Optional. To send notification callback function.
	 *     @type string $validate_callback   Required. To validate request callback function.
	 *     @type string $notification_type   Required. Notification type key.
	 *     @type string $notification_group  Optional. Notification group key.
	 * }
	 */
	final public function bb_register_subscription_type( $args ) {
		$r = bp_parse_args(
			$args,
			array(
				'label'              => array(
					'singular' => '',
					'plural'   => '',
				),
				'subscription_type'  => '',
				'items_callback'     => '',
				'send_callback'      => '',
				'validate_callback'  => '',
				'notification_type'  => '',
				'notification_group' => '',
			)
		);

		if ( empty( $r['subscription_type'] ) || empty( $r['notification_type'] ) || ! is_array( $r['label'] ) || empty( $r['validate_callback'] ) ) {
			return;
		}

		$this->subscriptions[ $r['subscription_type'] ] = array(
			'label'              => array(
				'singular' => ( ! empty( $r['label']['singular'] ) ? $r['label']['singular'] : $r['subscription_type'] ),
				'plural'   => ( ! empty( $r['label']['plural'] ) ? $r['label']['plural'] : $r['subscription_type'] ),
			),
			'subscription_type'  => $r['subscription_type'],
			'items_callback'     => $r['items_callback'],
			'send_callback'      => $r['send_callback'],
			'validate_callback'  => $r['validate_callback'],
			'notification_type'  => $r['notification_type'],
			'notification_group' => $r['notification_group'],
		);
	}

	/**
	 * Register validate callback function for subscription.
	 *
	 * @param bool             $response      True when subscription request correct otherwise false/WP_Error.
	 * @param BB_Subscriptions $subscriptions Current instance of the subscription item being saved.
	 *
	 * @return bool|WP_Error True on success, false on failure.
	 */
	public function bb_subscriptions_validate_request( $response, $subscriptions ) {
		$type              = $subscriptions->type ?? '';
		$blog_id           = isset( $subscriptions->blog_id ) ? (int) $subscriptions->blog_id : get_current_blog_id();
		$item_id           = isset( $subscriptions->item_id ) ? (int) $subscriptions->item_id : 0;
		$secondary_item_id = isset( $subscriptions->secondary_item_id ) ? (int) $subscriptions->secondary_item_id : 0;

		if ( empty( $item_id ) ) {
			$response = new WP_Error(
				'bb_subscription_required_item_id',
				__( 'The item ID is required.', 'buddyboss' ),
				array(
					'status' => 400,
				)
			);
		} elseif ( empty( $type ) ) {
			$response = new WP_Error(
				'bb_subscription_required_item_type',
				__( 'The item type is required.', 'buddyboss' ),
				array(
					'status' => 400,
				)
			);
		} else {
			$type_data = bb_register_subscriptions_types( $type );

			if (
				! empty( $type_data ) &&
				! empty( $type_data['validate_callback'] ) &&
				is_callable( $type_data['validate_callback'] )
			) {
				$validate_item = call_user_func(
					$type_data['validate_callback'],
					array(
						'type'              => $type,
						'blog_id'           => $blog_id,
						'item_id'           => $item_id,
						'secondary_item_id' => $secondary_item_id,
					)
				);

				if ( is_wp_error( $validate_item ) ) {
					$response = new WP_Error(
						'bb_subscription_invalid_item_request',
						$validate_item->get_error_message(),
						array(
							'status' => 400,
						)
					);
				} elseif ( ! $validate_item ) {
					$response = new WP_Error(
						'bb_subscription_invalid_item_request',
						__( 'The request for subscription item is not valid.', 'buddyboss' ),
						array(
							'status' => 400,
						)
					);
				}
			} else {
				$response = new WP_Error(
					'bb_subscription_invalid_item_type',
					__( 'The item type is not valid.', 'buddyboss' ),
					array(
						'status' => 400,
					)
				);
			}
		}

		return $response;
	}

}
