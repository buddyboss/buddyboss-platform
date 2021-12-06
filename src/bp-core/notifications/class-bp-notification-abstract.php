<?php

abstract class BB_Notification_Abstract {

	private static $notification_email_key;
	private static $notification_email_label;
	private static $notification_email_admin_label;
	private static $notification_email_position;

	public $component      = '';
	public $component_name = '';

	public function __construct( $email_key, $email_label, $email_admin_label, $email_position ) {
		$this->notification_email_key         = $email_key;
		$this->notification_email_label       = $email_label;
		$this->notification_email_admin_label = ( ! empty( $email_admin_label ) ? $email_admin_label : $email_label );
		$this->notification_email_position    = $email_position;

		$this->start();
	}

	private function start() {
		add_filter( 'bb_register_notifications', array( $this, 'bb_register_notifications' ), $this->notification_email_position );
		add_filter( 'bp_email_get_schema', array( $this, 'email_schema' ) );
	}

	function bb_register_notifications( $notifications ) {
		if ( empty( $this->component ) ) {
			return $notifications;
		}

		if ( ! isset( $this->component ) ) {
			$notifications[ $this->component ]['label'] = $this->component_name;
		}

		$notifications[ $this->component ]['fields'][] = array(
			'key'         => $this->notification_email_key,
			'label'       => $this->notification_email_label,
			'admin_label' => $this->notification_email_admin_label,
		);

		return $notifications;
	}

	function email_schema( $schema ) {

		$add_schema = $this->add_email_schema();
		if ( ! empty( $add_schema ) ) {
			$schema = array_merge( $schema, ( is_array( $add_schema ) ? $add_schema : array( $add_schema ) ) );
		}

		return $schema;
	}

	abstract public function add_email_schema();
}
