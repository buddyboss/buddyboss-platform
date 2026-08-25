<?php
/**
 * ReadyLaunch WC4BP (WooCommerce BuddyPress Integration) Helper Functions
 *
 * @since   BuddyBoss 3.1.0
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
 * @since BuddyBoss 3.1.0
 */
class BB_Readylaunch_WC4BP_Helper {

	/**
	 * The single instance of the class.
	 *
	 * @since BuddyBoss 3.1.0
	 * @var BB_Readylaunch_WC4BP_Helper
	 */
	protected static $instance = null;

	/**
	 * Main BB_Readylaunch_WC4BP_Helper Instance.
	 *
	 * Ensures only one instance of BB_Readylaunch_WC4BP_Helper is loaded or can be loaded.
	 *
	 * @since BuddyBoss 3.1.0
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
	 * @since BuddyBoss 3.1.0
	 */
	public function __construct() {
		// Register Readylaunch template stack for WC4BP templates.
		// This must happen before templates are searched.
		add_action( 'bp_loaded', array( $this, 'bb_rl_wc4bp_register_template_stack' ), 5 );

		// Also register immediately in case bp_loaded already fired.
		// Only execute if BuddyPress is loaded and required functions are available.
		if ( did_action( 'bp_loaded' ) && function_exists( 'bp_register_template_stack' ) ) {
			$this->bb_rl_wc4bp_register_template_stack();
		}
	}

	/**
	 * Register Readylaunch template stack for WC4BP templates.
	 *
	 * @since BuddyBoss 3.1.0
	 */
	public function bb_rl_wc4bp_register_template_stack() {
		// Only process if Readylaunch is enabled.
		if ( ! bb_is_readylaunch_enabled() ) {
			return;
		}

		// Register Readylaunch WC4BP template directory at priority 15 (higher than WC4BP's 14).
		// This ensures Readylaunch templates are checked before WC4BP's default templates.
		bp_register_template_stack( array( $this, 'bb_rl_wc4bp_get_template_directory' ), 15 );
	}

	/**
	 * Get Readylaunch WC4BP template directory.
	 *
	 * Static so it can be reused as the single source of truth for the
	 * template path (e.g. by the members/single/plugins.php template) while
	 * still serving as the template-stack callback registered above.
	 *
	 * @since BuddyBoss 3.1.0
	 * @static
	 *
	 * @return string Template directory path.
	 */
	public static function bb_rl_wc4bp_get_template_directory() {
		$plugin_dir = buddypress()->plugin_dir;
		$plugin_dir = rtrim( $plugin_dir, '/' ) . '/';

		// Check if plugin_dir already includes 'src/'.
		if ( false !== strpos( $plugin_dir, '/src/' ) ) {
			// plugin_dir already includes src/, so use bp-templates directly.
			return $plugin_dir . 'bp-templates/bp-nouveau/readylaunch/wc4bp';
		} else {
			// plugin_dir doesn't include src/, so add it.
			return $plugin_dir . 'src/bp-templates/bp-nouveau/readylaunch/wc4bp';
		}
	}
}
