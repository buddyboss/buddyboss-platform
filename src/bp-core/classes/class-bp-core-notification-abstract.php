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
	public function register_notification_preferences( $notifications ) {

		if ( ! empty( $this->prefernces ) ) {
			foreach ( $this->prefernces as $preference ) {
				$notifications[ $preference['pref_group'] ]['fields'][] = array(
					'key'         => $preference['pref_key'],
					'label'       => $preference['pref_label'],
					'admin_label' => ( isset( $preference['pref_admin_label'] ) && ! empty( $preference['pref_admin_label'] ) ? $preference['pref_admin_label'] : $preference['pref_label'] ),
					'default'     => ( true === $preference['pref_default'] ? 'yes' : 'no' ),
				);
			}
		}

		if ( ! empty( $this->prefernce_groups ) ) {
			foreach ( $this->prefernce_groups as $group ) {
				$notifications[ $group['group_key'] ]['label']       = $group['group_label'];
				$notifications[ $group['group_key'] ]['admin_label'] = ( isset( $group['group_admin_label'] ) && ! empty( $group['group_admin_label'] ) ? $group['group_admin_label'] : $group['group_label'] );
			}
		}

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
	public function register_notifications( $notifications ) {

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
	public function email_schema( $schema ) {

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
	public function email_type_schema( $type_schema ) {

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
	public function register_notification_emails( $emails ) {
		if ( ! empty( $this->email_types ) ) {
			$emails = array();
			foreach ( $this->email_types as $key => $val ) {
				if ( ! empty( $val['pref_key'] ) && isset( $emails[ $val['pref_key'] ] ) ) {
					if ( ! in_array( $key, $emails[ $val['pref_key'] ], true ) ) {
						$emails[ $val['pref_key'] ][] = $key;
					}
				} elseif ( ! empty( $val['pref_key'] ) ) {
					$emails[ $val['pref_key'] ][] = $key;
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
							'fields'   => 'count'
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
							'description' => $email['schema']['description']
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
	public function register_preferences_group( $group_key, $group_label, $group_admin_label ) {
		$this->prefernce_groups[] = array(
			'group_key'         => $group_key,
			'group_label'       => $group_label,
			'group_admin_label' => $group_admin_label,
		);
	}

	/**
	 * Register preference.
	 *
	 * @param string $pref_key         Preference key.
	 * @param string $pref_group       Preference group.
	 * @param string $pref_label       Preference label.
	 * @param string $pref_admin_label Preference admin label.
	 * @param bool   $default          Default status.
	 *
	 * @return void
	 * @since BuddyBoss [BBVERSION]
	 */
	public function register_preference( $pref_key, $pref_group, $pref_label, $pref_admin_label = '', $default = true ) {
		$this->prefernces[] = array(
			'pref_key'         => $pref_key,
			'pref_group'       => $pref_group,
			'pref_label'       => $pref_label,
			'pref_admin_label' => $pref_admin_label,
			'pref_default'     => $default,
		);
	}

	/**
	 * Register notification.
	 *
	 * @param string $component                Component name.
	 * @param string $component_action         Component action.
	 * @param string $notification_label       Notification label.
	 * @param string $notification_admin_label Notification admin label.
	 * @param string $pref_key                 Preference key.
	 * @param string $email_type               Email type.
	 *
	 * @return void
	 * @since BuddyBoss [BBVERSION]
	 */
	public function register_notification( $component, $component_action, $notification_label, $notification_admin_label, $pref_key = '', $email_type = '' ) {
		$this->notifications[] = array(
			'component'        => $component,
			'component_action' => $component_action,
			'label'            => $notification_label,
			'admin_label'      => $notification_admin_label,
			'preference_key'   => $pref_key,
			'email_type'       => $email_type,
		);
	}

	/**
	 * Add email schema.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $email_type   Type of email being sent.
	 * @param array  $args         Email arguments.
	 * @param array  $email_schema Email schema.
	 * @param string $pref_key     Preference key.
	 */
	public function register_email_type( $email_type, $args, $email_schema, $pref_key ) {
		$this->email_types[ $email_type ] = array(
			'email_type' => $email_type,
			'args'       => array(
				'post_title'   => ( $args['post_title'] ?? '' ),
				'post_content' => ( $args['post_content'] ?? '' ),
				'post_excerpt' => ( $args['post_excerpt'] ?? '' ),
				'multisite'    => ( $args['multisite'] ?? '' ),
			),
			'schema'     => array(
				'description' => ( $email_schema['description'] ?? '' ),
				'unsubscribe' => ( $email_schema['unsubscribe'] ?? false ),
			),
			'pref_key'   => $pref_key,
		);
	}
}

