<?php
/**
 * BuddyBoss ReadyLaunch Onboarding
 *
 * @package BuddyBoss\Core
 * @since   BuddyBoss [BBVERSION]
 * @author  BuddyBoss
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * ReadyLaunch Onboarding Class
 *
 * Handles the onboarding modal for first-time BuddyBoss Platform activation.
 * Extends the base Setup Wizard Manager to provide ReadyLaunch-specific functionality.
 *
 * @since BuddyBoss [BBVERSION]
 */
class BB_ReadyLaunch_Onboarding extends BB_Setup_Wizard_Manager {

	/**
	 * The single instance of the class.
	 *
	 * @since BuddyBoss [BBVERSION]
	 * @var   BB_ReadyLaunch_Onboarding|null
	 */
	protected static $instance = null;

	/**
	 * Main BB_ReadyLaunch_Onboarding Instance.
	 *
	 * Ensures only one instance of BB_ReadyLaunch_Onboarding is loaded or can be loaded.
	 *
	 * @since  BuddyBoss [BBVERSION]
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
	 * @since BuddyBoss [BBVERSION]
	 */
	private function __construct() {
		parent::__construct();
	}

	/**
	 * Initialize the ReadyLaunch onboarding wizard.
	 *
	 * @since BuddyBoss [BBVERSION]
	 * @return void
	 */
	protected function init() {
		$this->wizard_id         = 'rl_onboarding';
		$this->wizard_name       = __( 'ReadyLaunch Onboarding', 'buddyboss' );
		$this->wizard_version    = '1.0.0';
		$this->completion_option = 'bb_rl_onboarding_completed';
		$this->assets_dir        = __DIR__ . '/assets/';
		$this->assets_url        = buddypress()->plugin_url . 'bp-core/admin/bb-settings/rl-onboarding/assets/';

		$this->steps = array(
			'welcome'       => array(
				'name'    => __( 'Welcome', 'buddyboss' ),
				'view'    => array( $this, 'welcome_step' ),
				'handler' => array( $this, 'welcome_step_save' ),
			),
			'configuration' => array(
				'name'    => __( 'Configuration', 'buddyboss' ),
				'view'    => array( $this, 'configuration_step' ),
				'handler' => array( $this, 'configuration_step_save' ),
			),
		);

		$this->current_step = 'welcome';
	}

	/**
	 * Check if onboarding should be shown.
	 *
	 * Uses the existing BP activation mechanism with is_new_install URL parameter.
	 *
	 * @since BuddyBoss [BBVERSION]
	 * @return bool True if onboarding should be shown, false otherwise.
	 */
	public function should_show() {
		// Check if this is a new install using the existing BP mechanism.
		$is_new_install = isset( $_GET['is_new_install'] ) && '1' === $_GET['is_new_install']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		// Check if onboarding was already completed.
		$onboarding_completed = $this->is_completed();

		// Show onboarding if it's a new install and hasn't been completed yet.
		return $is_new_install && ! $onboarding_completed;
	}

	/**
	 * Enqueue wizard-specific assets.
	 *
	 * @since BuddyBoss [BBVERSION]
	 * @return void
	 */
	protected function enqueue_wizard_assets() {
		$asset_file = buddypress()->plugin_dir . 'bp-core/admin/bb-settings/rl-onboarding/build/rl-onboarding.asset.php';
		$asset_data = file_exists( $asset_file ) ? include $asset_file : array(
			'dependencies' => array(),
			'version'      => $this->wizard_version,
		);

		wp_enqueue_script(
			$this->wizard_id,
			buddypress()->plugin_url . 'bp-core/admin/bb-settings/rl-onboarding/build/rl-onboarding.js',
			$asset_data['dependencies'],
			$asset_data['version'],
			true
		);

		// Enqueue compiled SCSS styles.
		wp_enqueue_style(
			$this->wizard_id . '-styles',
			buddypress()->plugin_url . 'bp-core/admin/bb-settings/rl-onboarding/build/onboarding.css',
			array(),
			$asset_data['version']
		);
	}

	/**
	 * Localize wizard data for JavaScript.
	 *
	 * @since BuddyBoss [BBVERSION]
	 * @return void
	 */
	protected function localize_wizard_data() {
		// Localize script with necessary data.
		wp_localize_script(
			$this->wizard_id,
			'bbRlOnboarding',
			array(
				'wizardId'    => $this->wizard_id,
				'nonce'       => $this->create_nonce(),
				'assets'      => $this->get_wizard_assets(),
				'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
				'shouldShow'  => $this->should_show(),
				'steps'       => $this->get_steps(),
				'currentStep' => $this->get_current_step(),
			)
		);
	}

	/**
	 * Get wizard assets.
	 *
	 * @since BuddyBoss [BBVERSION]
	 * @return array
	 */
	protected function get_wizard_assets() {
		return array(
			'logo'                  => $this->assets_url . 'bb-icon.svg',
			'buddybossThemePreview' => $this->assets_url . 'buddyboss-theme-preview.svg',
			'currentThemePreview'   => $this->assets_url . 'current-theme-preview.svg',
		);
	}

	/**
	 * AJAX handler to complete wizard.
	 *
	 * @since BuddyBoss [BBVERSION]
	 * @return void
	 */
	public function ajax_complete() {
		// Verify nonce.
		if ( ! isset( $_POST['nonce'] ) || ! $this->validate_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) ) ) {
			$this->send_json_error(
				array(
					'message' => __( 'Invalid security token.', 'buddyboss' ),
				)
			);
		}

		// Check user permissions.
		if ( ! $this->validate_permissions() ) {
			$this->send_json_error(
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

		// Check if the onboarding was skipped.
		$skipped = false;
		if ( isset( $_POST['skipped'] ) && '1' === $_POST['skipped'] ) {
			$skipped = true;
		}

		// Prepare completion data.
		$completion_data = array(
			'selected_option' => $selected_option,
			'skipped'         => $skipped,
			'completion_time' => current_time( 'timestamp' ),
		);

		// Mark onboarding as completed.
		$this->mark_completed( $completion_data );

		// Store the selected option for later use.
		if ( ! empty( $selected_option ) ) {
			update_option( 'bb_rl_onboarding_selected_option', $selected_option );
		}

		// Handle specific configurations based on selected option.
		if ( ! $skipped && 'readylaunch' === $selected_option ) {
			$this->configure_readylaunch();
		}

		$this->send_json_success(
			array(
				'message'        => __( 'Onboarding completed successfully!', 'buddyboss' ),
				'selectedOption' => $selected_option,
				'skipped'        => $skipped,
			)
		);
	}

	/**
	 * Configure ReadyLaunch settings.
	 *
	 * @since BuddyBoss [BBVERSION]
	 * @return void
	 */
	protected function configure_readylaunch() {
		// Enable ReadyLaunch if it's not already enabled.
		if ( ! bb_is_readylaunch_enabled() ) {
			update_option( 'bb_rl_enabled', 1 );
		}

		// Set default ReadyLaunch settings for better user experience.
		update_option( 'bb_rl_theme_mode', 'light' );
		update_option( 'bb_rl_enabled_pages', array( 'activity', 'members', 'groups' ) );
	}

	/**
	 * Welcome step view.
	 *
	 * @since BuddyBoss [BBVERSION]
	 * @return void
	 */
	public function welcome_step() {
		// This is handled by the React component.
	}

	/**
	 * Save welcome step.
	 *
	 * @since BuddyBoss [BBVERSION]
	 * @param array $step_data Step data.
	 * @return bool
	 */
	public function welcome_step_save( $step_data ) {
		// Process welcome step data.
		if ( isset( $step_data['selected_option'] ) ) {
			update_option( 'bb_rl_onboarding_welcome_option', sanitize_text_field( $step_data['selected_option'] ) );
		}

		return true;
	}

	/**
	 * Configuration step view.
	 *
	 * @since BuddyBoss [BBVERSION]
	 * @return void
	 */
	public function configuration_step() {
		// This is handled by the React component.
	}

	/**
	 * Save configuration step.
	 *
	 * @since BuddyBoss [BBVERSION]
	 * @param array $step_data Step data.
	 * @return bool
	 */
	public function configuration_step_save( $step_data ) {
		// Process configuration step data.
		if ( isset( $step_data['readylaunch_settings'] ) ) {
			$settings = $step_data['readylaunch_settings'];

			// Save ReadyLaunch configuration.
			if ( isset( $settings['theme_mode'] ) ) {
				update_option( 'bb_rl_theme_mode', sanitize_text_field( $settings['theme_mode'] ) );
			}

			if ( isset( $settings['enabled_pages'] ) && is_array( $settings['enabled_pages'] ) ) {
				update_option( 'bb_rl_enabled_pages', array_map( 'sanitize_text_field', $settings['enabled_pages'] ) );
			}
		}

		return true;
	}

	/**
	 * Process step data.
	 *
	 * @since BuddyBoss [BBVERSION]
	 * @param array $step_data Step data to process.
	 * @return bool
	 */
	protected function process_step_data( $step_data ) {
		$current_step = $this->get_current_step();

		if ( isset( $this->steps[ $current_step ]['handler'] ) ) {
			$handler = $this->steps[ $current_step ]['handler'];

			if ( is_callable( $handler ) ) {
				return call_user_func( $handler, $step_data );
			}
		}

		return false;
	}

	/**
	 * Reset onboarding status (for development/testing purposes).
	 *
	 * This method should only be used during development or for testing.
	 * It removes the onboarding completion flag, allowing the onboarding to be shown again.
	 *
	 * @since BuddyBoss [BBVERSION]
	 * @return bool True if the option was deleted, false otherwise.
	 */
	public function reset_onboarding() {
		$result = $this->reset();

		// Also clean up related options.
		delete_option( 'bb_rl_onboarding_selected_option' );
		delete_option( 'bb_rl_onboarding_welcome_option' );

		return $result;
	}

	/**
	 * Get onboarding configuration.
	 *
	 * @since BuddyBoss [BBVERSION]
	 * @return array
	 */
	public function get_onboarding_config() {
		return array(
			'wizard_id'       => $this->wizard_id,
			'wizard_name'     => $this->wizard_name,
			'is_completed'    => $this->is_completed(),
			'selected_option' => get_option( 'bb_rl_onboarding_selected_option', '' ),
			'should_show'     => $this->should_show(),
			'current_step'    => $this->get_current_step(),
			'steps'           => $this->get_steps(),
			'assets'          => $this->get_wizard_assets(),
		);
	}

	/**
	 * Setup additional hooks specific to ReadyLaunch onboarding.
	 *
	 * @since BuddyBoss [BBVERSION]
	 * @return void
	 */
	protected function setup_hooks() {
		parent::setup_hooks();

		// Add ReadyLaunch specific hooks.
		add_action( 'bb_setup_wizard_' . $this->wizard_id . '_completed', array( $this, 'handle_onboarding_completion' ) );
	}

	/**
	 * Handle onboarding completion.
	 *
	 * @since BuddyBoss [BBVERSION]
	 * @param array $data Completion data.
	 * @return void
	 */
	public function handle_onboarding_completion( $data ) {
		// Log completion for analytics.
		error_log( 'ReadyLaunch onboarding completed: ' . wp_json_encode( $data ) );

		// Trigger additional actions based on selected option.
		if ( isset( $data['selected_option'] ) ) {
			$selected_option = $data['selected_option'];

			/**
			 * Fires after ReadyLaunch onboarding is completed with specific option.
			 *
			 * @since BuddyBoss [BBVERSION]
			 *
			 * @param string $selected_option The selected option.
			 * @param array  $data           Complete completion data.
			 */
			do_action( 'bb_readylaunch_onboarding_option_' . $selected_option, $data );
		}
	}
}
