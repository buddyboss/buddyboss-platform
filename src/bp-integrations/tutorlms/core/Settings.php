<?php
/**
 * BuddyBoss TutorLMS integration Settings class.
 *
 * @package BuddyBoss\TutorLMS
 * @since BuddyBoss 1.0.0
 */

namespace Buddyboss\TutorLMSIntegration\Core;

use Buddyboss\TutorLMSIntegration\Library\ValueLoader;

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
	protected $optionKey = 'bp_tutor_settings';

	/**
	 * Constructor
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function __construct() {
		 $this->installDefaultSettings();
		$this->loader = new ValueLoader( $this->options );

		add_action( 'bb_tutorlms/setting_updated', array( $this, 'setGroupSyncTimestamp' ), 10, 2 );
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
		do_action( 'bb_tutorlms/setting_updated', $this->options, $oldOptions );
		return $this;
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
