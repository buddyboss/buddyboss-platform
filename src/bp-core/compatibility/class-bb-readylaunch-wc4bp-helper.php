<?php
/**
 * ReadyLaunch WC4BP (WooCommerce BuddyPress Integration) Helper Functions
 *
 * @since   BuddyBoss 2.9.00
 * @package BuddyBoss\Core
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * ReadyLaunch WC4BP Helper Class
 *
 * This class provides helper functions for WC4BP integration
 * when using ReadyLaunch templates without BuddyBoss theme.
 *
 * @since BuddyBoss 2.9.00
 */
class BB_Readylaunch_WC4BP_Helper {

	/**
	 * The single instance of the class.
	 *
	 * @since BuddyBoss 2.9.00
	 * @var BB_Readylaunch_WC4BP_Helper
	 */
	protected static $instance = null;

	/**
	 * Main BB_Readylaunch_WC4BP_Helper Instance.
	 *
	 * Ensures only one instance of BB_Readylaunch_WC4BP_Helper is loaded or can be loaded.
	 *
	 * @since BuddyBoss 2.9.00
	 * @static
	 * @return BB_Readylaunch_WC4BP_Helper - Main instance.
	 */
	public static function instance(): BB_Readylaunch_WC4BP_Helper {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @since BuddyBoss 2.9.00
	 */
	public function __construct() {
		// Register Readylaunch template stack for WC4BP templates
		// This must happen before templates are searched
		add_action( 'bp_loaded', array( $this, 'bb_rl_wc4bp_register_template_stack' ), 5 );
		
		// Also register immediately in case bp_loaded already fired
		// Only execute if BuddyPress is loaded and required functions are available
		if ( did_action( 'bp_loaded' ) && function_exists( 'bp_register_template_stack' ) ) {
			$this->bb_rl_wc4bp_register_template_stack();
		}
	}

	/**
	 * Register Readylaunch template stack for WC4BP templates.
	 *
	 * @since BuddyBoss 2.9.00
	 */
	public function bb_rl_wc4bp_register_template_stack() {
		// Only process if Readylaunch is enabled
		if ( ! bb_is_readylaunch_enabled() ) {
			return;
		}

		// Register Readylaunch WC4BP template directory at priority 15 (higher than WC4BP's 14)
		// This ensures Readylaunch templates are checked before WC4BP's default templates
		bp_register_template_stack( array( $this, 'bb_rl_wc4bp_get_template_directory' ), 15 );
	}

	/**
	 * Get Readylaunch WC4BP template directory.
	 *
	 * @since BuddyBoss 2.9.00
	 *
	 * @return string Template directory path.
	 */
	public function bb_rl_wc4bp_get_template_directory() {
		$plugin_dir = buddypress()->plugin_dir;
		$plugin_dir = rtrim( $plugin_dir, '/' ) . '/';
		
		// Check if plugin_dir already includes 'src/' (it does based on logs)
		if ( false !== strpos( $plugin_dir, '/src/' ) ) {
			// plugin_dir already includes src/, so use bp-templates directly
			return $plugin_dir . 'bp-templates/bp-nouveau/readylaunch/wc4bp';
		} else {
			// plugin_dir doesn't include src/, so add it
			return $plugin_dir . 'src/bp-templates/bp-nouveau/readylaunch/wc4bp';
		}
	}

}

