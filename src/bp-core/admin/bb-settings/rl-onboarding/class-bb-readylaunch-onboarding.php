<?php
/**
 * BuddyBoss ReadyLaunch Onboarding
 *
 * @package BuddyBoss\Core\Administration
 * @subpackage ReadyLaunchOnboarding
 * @since   BuddyBoss 2.9.10
 * @author  BuddyBoss
 * @copyright Copyright (c) 2023, BuddyBoss
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * ReadyLaunch Onboarding Class
 *
 * Handles the onboarding modal for first-time BuddyBoss Platform activation.
 * This class implements singleton pattern to ensure only one instance exists.
 *
 * @since BuddyBoss 2.9.10
 */
class BB_ReadyLaunch_Onboarding {

	/**
	 * The single instance of the class.
	 *
	 * @since BuddyBoss 2.9.10
	 * @var   BB_ReadyLaunch_Onboarding|null
	 */
	protected static $instance = null;

	/**
	 * Option name for storing onboarding completion status.
	 *
	 * @since BuddyBoss 2.9.10
	 * @var   string
	 */
	const ONBOARDING_OPTION = 'bb_rl_onboarding_completed';

	/**
	 * Main BB_ReadyLaunch_Onboarding Instance.
	 *
	 * Ensures only one instance of BB_ReadyLaunch_Onboarding is loaded or can be loaded.
	 *
	 * @since  BuddyBoss 2.9.10
	 * @static
	 * @return BB_ReadyLaunch_Onboarding Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * Private constructor to prevent direct instantiation.
	 *
	 * @since BuddyBoss 2.9.10
	 */
	private function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize WordPress hooks.
	 *
	 * @since BuddyBoss 2.9.10
	 * @return void
	 */
	private function init_hooks() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_onboarding_scripts' ) );
		add_action( 'wp_ajax_bb_rl_should_show_onboarding', array( $this, 'ajax_should_show_onboarding' ) );
		add_action( 'wp_ajax_bb_rl_complete_onboarding', array( $this, 'ajax_complete_onboarding' ) );
	}

	/**
	 * Check if onboarding should be shown.
	 *
	 * Uses the existing BP activation mechanism with is_new_install URL parameter.
	 *
	 * @since BuddyBoss 2.9.10
	 * @return bool True if onboarding should be shown, false otherwise.
	 */
	public function should_show_onboarding() {
		// Check if this is a new install using the existing BP mechanism.
		$is_new_install = isset( $_GET['is_new_install'] ) && '1' === $_GET['is_new_install']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		// Check if onboarding was already completed.
		$onboarding_completed = get_option( self::ONBOARDING_OPTION, false );

		// Show onboarding if it's a new install and hasn't been completed yet.
		return $is_new_install && ! $onboarding_completed;
	}

	/**
	 * Enqueue onboarding scripts and styles.
	 *
	 * @since BuddyBoss 2.9.10
	 * @param string $hook_suffix Current admin page hook suffix.
	 * @return void
	 */
	public function enqueue_onboarding_scripts( $hook_suffix ) {
		// Only load on admin pages - let JavaScript handle the detection.
		if ( ! is_admin() ) {
			return;
		}

		$asset_file = buddypress()->plugin_dir . 'bp-core/admin/bb-settings/rl-onboarding/build/rl-onboarding.asset.php';
		$asset_data = file_exists( $asset_file ) ? include $asset_file : array(
			'dependencies' => array(),
			'version'      => '1.0.0',
		);

		wp_enqueue_script(
			'bb-rl-onboarding',
			buddypress()->plugin_url . 'bp-core/admin/bb-settings/rl-onboarding/build/rl-onboarding.js',
			$asset_data['dependencies'],
			$asset_data['version'],
			true
		);

		// Enqueue compiled SCSS styles.
		wp_enqueue_style(
			'bb-rl-onboarding-styles',
			buddypress()->plugin_url . 'bp-core/admin/bb-settings/rl-onboarding/build/onboarding.css',
			array(),
			$asset_data['version']
		);

		// Localize script with necessary data.
		wp_localize_script(
			'bb-rl-onboarding',
			'bbRlOnboarding',
			array(
				'nonce'   => wp_create_nonce( 'bb_rl_onboarding_nonce' ),
				'assets'  => array(
					'logo'                  => buddypress()->plugin_url . 'bp-core/images/bb-icon.svg',
					'buddybossThemePreview' => buddypress()->plugin_url . 'bp-core/admin/bb-settings/rl-onboarding/assets/buddyboss-theme-preview.jpg',
					'currentThemePreview'   => buddypress()->plugin_url . 'bp-core/admin/bb-settings/rl-onboarding/assets/current-theme-preview.jpg',
				),
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'shouldShow' => $this->should_show_onboarding(),
			)
		);
	}

	/**
	 * AJAX handler to check if onboarding should be shown.
	 *
	 * @since BuddyBoss 2.9.10
	 * @return void
	 */
	public function ajax_should_show_onboarding() {
		// Verify nonce for security.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'bb_rl_onboarding_nonce' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Invalid security token.', 'buddyboss' ),
				)
			);
		}

		$should_show = $this->should_show_onboarding();

		wp_send_json_success(
			array(
				'shouldShow' => $should_show,
			)
		);
	}

	/**
	 * AJAX handler to mark onboarding as completed.
	 *
	 * @since BuddyBoss 2.9.10
	 * @return void
	 */
	public function ajax_complete_onboarding() {
		// Verify nonce for security.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'bb_rl_onboarding_nonce' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Invalid security token.', 'buddyboss' ),
				)
			);
		}

		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'You do not have sufficient permissions to perform this action.', 'buddyboss' ),
				)
			);
		}

		// Sanitize and validate the selected option.
		$selected_option = '';
		if ( isset( $_POST['selectedOption'] ) ) {
			$selected_option = sanitize_text_field( wp_unslash( $_POST['selectedOption'] ) );
		}

		// Mark onboarding as completed.
		update_option( self::ONBOARDING_OPTION, true );

		/**
		 * Fires after the ReadyLaunch onboarding is completed.
		 *
		 * @since BuddyBoss 2.9.10
		 *
		 * @param string $selected_option The option selected by the user during onboarding.
		 */
		do_action( 'bb_readylaunch_onboarding_completed', $selected_option );

		wp_send_json_success(
			array(
				'message'        => __( 'Onboarding completed successfully!', 'buddyboss' ),
				'selectedOption' => $selected_option,
			)
		);
	}

	/**
	 * Reset onboarding status (for development/testing purposes).
	 *
	 * This method should only be used during development or for testing.
	 * It removes the onboarding completion flag, allowing the onboarding to be shown again.
	 *
	 * @since BuddyBoss 2.9.10
	 * @return bool True if the option was deleted, false otherwise.
	 */
	public function reset_onboarding() {
		return delete_option( self::ONBOARDING_OPTION );
	}
}
