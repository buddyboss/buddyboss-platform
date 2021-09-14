<?php
/**
 * BuddyBoss LearnDash integration Settings class.
 *
 * @package BuddyBoss\LearnDash
 * @since BuddyBoss 1.0.0
 */

namespace Buddyboss\LearndashIntegration\Core;

use Buddyboss\LearndashIntegration\Library\ValueLoader;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class to handle options saving/loading
 *
 * @since BuddyBoss 1.0.0
 */
class Settings {

	protected $loader;
	protected $options   = array();
	protected $optionKey = 'bp_ld_sync_settings';

	/**
	 * Constructor
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function __construct() {
		 $this->installDefaultSettings();
		$this->loader = new ValueLoader( $this->options );

		add_action( 'bp_ld_sync/setting_updated', array( $this, 'setGroupSyncTimestamp' ), 10, 2 );
	}

	/**
	 * Convert . seperated name to array syntax
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function getName( $key = '' ) {
		$name = $this->optionKey;

		foreach ( array_filter( explode( '.', $key ) ) as $peice ) {
			$name .= "[{$peice}]";
		}

		return $name;
	}

	/**
	 * Get the option from loader
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function get( $key = null, $default = null ) {
		return $this->loader->get( $key, $default );
	}

	/**
	 * Set the option to loader
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function set( $key = null, $value = null ) {
		$this->loader->set( $key, $value );
		return $this;
	}

	/**
	 * Presist the loader value to db
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function update() {
		$oldOptions = $this->options;
		bp_update_option( $this->optionKey, $this->options = $this->loader->get() );
		do_action( 'bp_ld_sync/setting_updated', $this->options, $oldOptions );
		return $this;
	}

	/**
	 * Set the group sync timestamp to determine if we need a full sync of simple sync
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function setGroupSyncTimestamp( $options, $oldOptions ) {
		$new  = new ValueLoader( $options );
		$old  = new ValueLoader( $oldOptions );
		$time = time();

		if ( $new->get( 'buddypress.enabled' ) && ! $old->get( 'buddypress.enabled' ) ) {
			bp_update_option( 'bp_ld_sync/bp_last_synced', $time, false );
		}

		if ( $new->get( 'learndash.enabled' ) && ! $old->get( 'learndash.enabled' ) ) {
			bp_update_option( 'bp_ld_sync/ld_last_synced', $time, false );
		}
	}

	/**
	 * Default options
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function defaultOptions() {
		return array(
			'buddypress' => array(
				'enabled'               => false,
				'show_in_bp_create'     => true,
				'show_in_bp_manage'     => true,
				'tab_access'            => 'anyone',
				'default_auto_sync'     => true,
				'delete_ld_on_delete'   => false,
				'default_admin_sync_to' => 'admin',
				'default_mod_sync_to'   => 'admin',
				'default_user_sync_to'  => 'user',
			),
			'learndash'  => array(
				'enabled'                  => false,
				'default_auto_sync'        => true,
				'default_bp_privacy'       => 'private',
				'default_bp_invite_status' => 'admin',
				'default_admin_sync_to'    => 'admin',
				'default_user_sync_to'     => 'user',
				'delete_bp_on_delete'      => false,
			),
			'reports'    => array(
				'enabled'    => false,
				'access'     => array( 'admin', 'mod' ),
				'per_page'   => 20,
				'cache_time' => 60,
			),
		);
	}

	/**
	 * Presist the default option into db if not exists
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function installDefaultSettings() {
		if ( ! $options = bp_get_option( $this->optionKey ) ) {
			$options = $this->defaultOptions();
			bp_update_option( $this->optionKey, $options );
		}

		$this->options = $options;
	}
}
