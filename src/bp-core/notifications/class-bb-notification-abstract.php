<?php
/**
 * BuddyBoss Notification Abstract Class.
 *
 * @package BuddyBoss
 *
 * @since   BuddyBoss [BBVERSION]
 */

defined( 'ABSPATH' ) || exit;

/**
 * Set up the Notification Abstract class.
 *
 * @since BuddyBoss [BBVERSION]
 */
abstract class BB_Notification_Abstract {

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

	private $notifications = array();
	private $prefernces = array();
	private $prefernce_groups = array();

	/**
	 * Initialize.
	 *
	 * @return void
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function start() {
		add_filter( 'bb_register_notifications', array( $this, 'register_notification_preferences' ), $this->notification_email_position );
//		add_filter( 'bp_email_get_schema', array( $this, 'email_schema' ) );
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
			foreach ( $this->prefernces as $prefernce ) {
				$notifications[$prefernce['pref_group'] ]['fields'][] = array(
					'key'         => $prefernce['pref_key'],
					'label'       => $prefernce['pref_label'],
					'admin_label' => $prefernce['pref_admin_label'],
				);
			}
		}

		if( !empty( $this->prefernce_groups ) ) {
			foreach ( $this->prefernce_groups as $group ) {
				$notifications[ $group['group_key'] ]['label'] = $group['group_label'];
				$notifications[ $group['group_key'] ]['admin_label'] = $group['group_admin_label'];
			}
		}

//		if( $this->prefernce_groups)
//
//
//		if ( ! isset( $notifications[ $this->component ] ) ) {
//			$notifications[ $this->component ]['label'] = $this->component_name;
//		}

//		$notifications[ $this->component ]['fields'][] = array(
//			'name'         => $this->notification_email_key,
//			'label'       => $this->notification_email_label,
//			'admin_label' => $this->notification_email_admin_label,
//		);

		error_log( print_r( $notifications, 1 ) );

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

		$add_schema = $this->add_email_schema();
		if ( ! empty( $add_schema ) ) {
			$schema = array_merge( $schema, ( is_array( $add_schema ) ? $add_schema : array( $add_schema ) ) );
		}

		return $schema;
	}

	/**
	 * Add email schema.
	 *
	 * @return mixed
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
//	public function add_email_schema();

	/**
	 * Register notification
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

	public function register_preferences_group( $group_key, $group_label, $group_admin_label ) {
		$this->prefernce_groups[] = array(
			'group_key'         => $group_key,
			'group_label'       => $group_label,
			'group_admin_label' => $group_admin_label,
		);
	}

	public function register_preference( $pref_key, $pref_group, $pref_label, $pref_admin_label ) {
		$this->prefernces[] = array(
			'pref_key'         => $pref_key,
			'pref_group'       => $pref_group,
			'pref_label'       => $pref_label,
			'pref_admin_label' => $pref_admin_label,
		);
	}

}
