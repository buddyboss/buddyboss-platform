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

	/**
	 * BB_Notification_Abstract constructor.
	 *
	 * @param string $email_key         Email Key.
	 * @param string $email_label       Email label.
	 * @param string $email_admin_label Email admin label.
	 * @param int    $email_position    Email position.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function __construct( $email_key, $email_label, $email_admin_label, $email_position ) {
		$this->notification_email_key         = $email_key;
		$this->notification_email_label       = $email_label;
		$this->notification_email_admin_label = ( ! empty( $email_admin_label ) ? $email_admin_label : $email_label );
		$this->notification_email_position    = $email_position;

		$this->start();
	}

	/**
	 * Initialize.
	 *
	 * @return void
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	private function start() {
		add_filter( 'bb_register_notifications', array( $this, 'bb_register_notifications' ), $this->notification_email_position );
		add_filter( 'bp_email_get_schema', array( $this, 'email_schema' ) );
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
	public function bb_register_notifications( $notifications ) {
		if ( empty( $this->component ) ) {
			return $notifications;
		}

		if ( ! isset( $notifications[ $this->component ] ) ) {
			$notifications[ $this->component ]['label'] = $this->component_name;
		}

		$notifications[ $this->component ]['fields'][] = array(
			'key'         => $this->notification_email_key,
			'label'       => $this->notification_email_label,
			'admin_label' => $this->notification_email_admin_label,
		);

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
	abstract public function add_email_schema();
}
