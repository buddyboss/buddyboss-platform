<?php
/**
 * BuddyBoss Notification Abstract Class.
 *
 * @package BuddyBoss\Core
 *
 * @since   BuddyBoss [BBVERSION]
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
	private $prefernces = array();

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
	 * Initialize.
	 *
	 * @return void
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function start() {
		add_filter( 'bb_register_notification_preferences', array( $this, 'register_notification_preferences' ) );
		add_filter( 'bb_register_notifications', array( $this, 'register_notifications' ) );
		add_filter( 'bp_email_get_schema', array( $this, 'email_schema' ), 999 );
		add_filter( 'bp_email_get_type_schema', array( $this, 'email_type_schema' ), 999 );
		add_filter( 'bb_register_notification_emails', array( $this, 'register_notification_emails' ), 999 );

		add_action( 'bp_init', array( $this, 'register_email_template' ), 60 );

	}

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

		if ( ! empty( $this->prefernces ) ) {
			foreach ( $this->prefernces as $preference ) {
				$notifications[ $preference['notification_group'] ]['fields'][] = array(
					'key'           => $preference['notification_type'],
					'label'         => $preference['notification_label'],
					'admin_label'   => ( isset( $preference['notification_admin_label'] ) && ! empty( $preference['notification_admin_label'] ) ? $preference['notification_admin_label'] : $preference['notification_label'] ),
					'default'       => ( true === $preference['notification_default'] ? 'yes' : 'no' ),
					'notifications' => ( ! empty( $all_notifications ) && isset( $all_notifications[ $preference['notification_type'] ] ) ) ? $all_notifications[ $preference['notification_type'] ] : array(),
					'email_types'   => ( ! empty( $all_email_types ) && isset( $all_email_types[ $preference['notification_type'] ] ) ) ? $all_email_types[ $preference['notification_type'] ] : array(),
				);
			}
		}

		if ( ! empty( $this->prefernce_groups ) ) {
			foreach ( $this->prefernce_groups as $group ) {
				$notifications[ $group['group_key'] ]['label']       = $group['group_label'];
				$notifications[ $group['group_key'] ]['admin_label'] = ( isset( $group['group_admin_label'] ) && ! empty( $group['group_admin_label'] ) ? $group['group_admin_label'] : $group['group_label'] );
				$notifications[ $group['group_key'] ]['priority']    = $group['priority'];
			}
		}

		$priority  = array_column( $notifications, 'priority' );

		array_multisort( $priority, SORT_ASC, $notifications );

		return $notifications;
	}

	/**
	 * Register the notifications.
	 *
	 * @param array $notifications List of notifications.
	 *
	 * @return array
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
			$emails = array();
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

	/************************************ Actions ************************************/

	/**
	 * Register Email template if not exists.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function register_email_template() {

		// Bail if this is an ajax request.
		if ( defined( 'DOING_AJAX' ) ) {
			return;
		}

		$defaults = array(
			'post_status' => 'publish',
			'post_type'   => bp_get_email_post_type(),
		);

		if ( ! empty( $this->email_types ) ) {
			foreach ( $this->email_types as $id => $email ) {

				if (
					term_exists( $id, bp_get_email_tax_type() ) &&
					get_terms(
						array(
							'taxonomy' => bp_get_email_tax_type(),
							'slug'     => $id,
							'fields'   => 'count',
						)
					) > 0
				) {
					continue;
				}

				// Some emails are multisite-only.
				if ( ! is_multisite() && isset( $email['args'] ) && ! empty( $email['args']['multisite'] ) ) {
					continue;
				}

				$post_id = wp_insert_post( bp_parse_args( $email['args'], $defaults, 'install_email_' . $id ) );
				if ( ! $post_id ) {
					continue;
				}

				$tt_ids = wp_set_object_terms( $post_id, $id, bp_get_email_tax_type() );
				foreach ( $tt_ids as $tt_id ) {
					$term = get_term_by( 'term_taxonomy_id', (int) $tt_id, bp_get_email_tax_type() );
					wp_update_term(
						(int) $term->term_id,
						bp_get_email_tax_type(),
						array(
							'description' => $email['schema']['description'],
						)
					);
				}
			}
		}
	}

	/************************************ Functions ************************************/

	/**
	 * Register preference group.
	 *
	 * @param string $group_key         Group key.
	 * @param string $group_label       Group label.
	 * @param string $group_admin_label Group admin label.
	 *
	 * @return void
	 * @since BuddyBoss [BBVERSION]
	 */
	public function register_preferences_group( string $group_key = 'other', string $group_label = '', string $group_admin_label = '', $priority = 10 ) {
		$this->prefernce_groups[] = array(
			'group_key'         => $group_key,
			'group_label'       => ( ! empty( $group_label ) ? $group_label : esc_html__( 'Other', 'buddyboss' ) ),
			'group_admin_label' => $group_admin_label,
			'priority'          => $priority,
		);
	}

	/**
	 * Register preference.
	 *
	 * @param string $notification_group       Notification group.
	 * @param string $notification_type        Notification Type key.
	 * @param string $notification_label       Notification label.
	 * @param string $notification_admin_label Notification admin label.
	 * @param bool   $default                  Default status.
	 *
	 * @return void
	 * @since BuddyBoss [BBVERSION]
	 */
	public function register_preference( string $notification_group, string $notification_type, string $notification_label, string $notification_admin_label = '', bool $default = true ) {
		$this->prefernces[] = array(
			'notification_group'       => $notification_group,
			'notification_type'        => $notification_type,
			'notification_label'       => $notification_label,
			'notification_admin_label' => $notification_admin_label,
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
	 * @since BuddyBoss [BBVERSION]
	 */
	public function register_notification( string $component, string $component_action, string $notification_type ) {
		$this->notifications[] = array(
			'component'         => $component,
			'component_action'  => $component_action,
			'notification_type' => $notification_type,
		);
	}

	/**
	 * Add email schema.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $email_type        Type of email being sent.
	 * @param array  $args              Email arguments.
	 * @param array  $email_schema      Email schema.
	 * @param string $notification_type Notification Type key.
	 */
	public function register_email_type( string $email_type, array $args, array $email_schema, string $notification_type ) {
		$this->email_types[ $email_type ] = array(
			'email_type'        => $email_type,
			'args'              => array(
				'post_title'   => ( $args['post_title'] ?? '' ),
				'post_content' => ( $args['post_content'] ?? '' ),
				'post_excerpt' => ( $args['post_excerpt'] ?? '' ),
				'multisite'    => ( $args['multisite'] ?? '' ),
			),
			'schema'            => array(
				'description' => ( $email_schema['description'] ?? '' ),
				'unsubscribe' => ( $email_schema['unsubscribe'] ?? false ),
			),
			'notification_type' => $notification_type,
		);
	}
}

