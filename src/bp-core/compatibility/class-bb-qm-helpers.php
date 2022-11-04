<?php
/**
 * BuddyBoss QueryMonitor Compatibility.
 *
 * @package BuddyBoss\Core\Compatibility
 *
 * @since BuddyBoss 2.1.3
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BB_QM_Helpers Class
 *
 * This class handles compatibility code for Query Monitor plugins used in conjunction with Platform.
 */
class BB_QM_Helpers {

	/**
	 * The single instance of the class.
	 *
	 * @var self
	 */
	private static $instance = null;

	/**
	 * BB_QM_Helpers constructor.
	 */
	public function __construct() {
		$this->compatibility_init();
	}

	/**
	 * Get the instance of this class.
	 *
	 * @return Controller|null
	 */
	public static function instance() {

		if ( null === self::$instance ) {
			$class_name     = __CLASS__;
			self::$instance = new $class_name();
		}

		return self::$instance;
	}

	/**
	 * Register the compatibility hooks for the plugin.
	 */
	public function compatibility_init() {

		add_action( 'bb_email_customize_preview', array( $this, 'bb_hide_querymonitor_dispatchers' ) );
	}

	/**
	 * Removed Query monitor code from email customizer page.
	 *
	 * @return void
	 */
	public function bb_hide_querymonitor_dispatchers() {
		ob_start(); ?>
		<style>
			#wp-admin-bar-query-monitor,
			#query-monitor-main {
				display: none !important;
			}
		</style>
		<?php
		echo ob_get_clean();
	}

}

BB_QM_Helpers::instance();
