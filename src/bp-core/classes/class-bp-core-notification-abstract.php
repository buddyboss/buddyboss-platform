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
	 * Notification Email Key.
	 *
	 * @var string
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	private $notification_email_key;

	/**
	 * Notification Email lable.
	 *
	 * @var string
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	private $notification_email_label;

	/**
	 * Notification Email admin label.
	 *
	 * @var string
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	private $notification_email_admin_label;

	/**
	 * Notification Email Position.
	 *
	 * @var string
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	private $notification_email_position;

	/**
	 * Component.
	 *
	 * @var string
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public $component = '';

	/**
	 * Component name.
	 *
	 * @var string
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public $component_name = '';

	/**
	 * Notifications.
	 *
	 * @var string
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	private $notifications = array();

	/**
	 * Preferences.
	 *
	 * @var string
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	private $prefernces = array();

	/**
	 * Preferences Group.
	 *
	 * @var string
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	private $prefernce_groups = array();

	private $email_schema = array();

	/**
	 * Initialize.
	 *
	 * @return void
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function start() {
		add_filter( 'bb_register_notifications', array( $this, 'register_notification_preferences' ), $this->notification_email_position );
		add_filter( 'bp_email_get_schema', array( $this, 'email_schema' ), 999 );
	}

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
					'admin_label' => $preference['pref_admin_label'],
				);
			}
		}

		if ( ! empty( $this->prefernce_groups ) ) {
			foreach ( $this->prefernce_groups as $group ) {
				$notifications[ $group['group_key'] ]['label']       = $group['group_label'];
				$notifications[ $group['group_key'] ]['admin_label'] = $group['group_admin_label'];
			}
		}

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

		if ( ! empty( $this->email_schema ) ) {
			$new_schema = array_column( $this->email_schema, 'args', 'email_key' );
			if ( ! empty( $new_schema ) ) {
				$schema = array_merge( $schema, $new_schema );
			}
		}

		return $schema;
	}

	/**
	 * Add email schema.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $pref_key  Preference key.
	 * @param string $email_key Email key.
	 * @param array  $args      Email arguments.
	 */
	public function add_email_schema( $pref_key, $email_key, $args ) {
		$this->email_schema[ $email_key ] = array(
			'pref_key'  => $pref_key,
			'email_key' => $email_key,
			'args'      => array(
				'post_title'   => ( isset( $args['post_title'] ) ? $args['post_title'] : '' ),
				'post_content' => ( isset( $args['post_content'] ) ? $args['post_content'] : '' ),
				'post_excerpt' => ( isset( $args['post_excerpt'] ) ? $args['post_excerpt'] : '' ),
			),
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
	 *
	 * @return void
	 * @since BuddyBoss [BBVERSION]
	 */
	public function register_notification( $component, $component_action, $notification_label, $notification_admin_label, $pref_key = '' ) {
		$this->notifications[] = array(
			'component'        => $component,
			'component_action' => $component_action,
			'label'            => $notification_label,
			'admin_label'      => $notification_admin_label,
			'preference_key'   => $pref_key,
		);
	}

	/**
	 * Register preference group.
	 *
	 * @param string $group_key Group key.
	 * @param string $group_label Group label.
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
	 * @param string $pref_key Preference key.
	 * @param string $pref_group Preference group.
	 * @param string $pref_label Preference label.
	 * @param string $pref_admin_label Preference admin label.
	 *
	 * @return void
	 * @since BuddyBoss [BBVERSION]
	 */
	public function register_preference( $pref_key, $pref_group, $pref_label, $pref_admin_label ) {
		$this->prefernces[] = array(
			'pref_key'         => $pref_key,
			'pref_group'       => $pref_group,
			'pref_label'       => $pref_label,
			'pref_admin_label' => $pref_admin_label,
		);
	}

}
