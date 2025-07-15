<?php
/**
 * BuddyBoss Setup Wizard Manager Base Class
 *
 * @package BuddyBoss\Core
 * @since   BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Base Setup Wizard Manager Class
 *
 * Provides common functionality for all setup wizards in the BuddyBoss Platform.
 * Specific wizard implementations should extend this abstract class.
 *
 * @since BuddyBoss [BBVERSION]
 */
abstract class BB_Setup_Wizard_Manager {

	/**
	 * Wizard identifier
	 *
	 * @since BuddyBoss [BBVERSION]
	 * @var   string
	 */
	protected $wizard_id = '';

	/**
	 * Wizard name
	 *
	 * @since BuddyBoss [BBVERSION]
	 * @var   string
	 */
	protected $wizard_name = '';

	/**
	 * Wizard version
	 *
	 * @since BuddyBoss [BBVERSION]
	 * @var   string
	 */
	protected $wizard_version = '1.0.0';

	/**
	 * Assets directory path
	 *
	 * @since BuddyBoss [BBVERSION]
	 * @var   string
	 */
	protected $assets_dir = '';

	/**
	 * Assets URL
	 *
	 * @since BuddyBoss [BBVERSION]
	 * @var   string
	 */
	protected $assets_url = '';

	/**
	 * Wizard steps
	 *
	 * @since BuddyBoss [BBVERSION]
	 * @var   array
	 */
	protected $steps = array();

	/**
	 * Current step
	 *
	 * @since BuddyBoss [BBVERSION]
	 * @var   string
	 */
	protected $current_step = '';

	/**
	 * Wizard completion option name
	 *
	 * @since BuddyBoss [BBVERSION]
	 * @var   string
	 */
	protected $completion_option = '';

	/**
	 * Constructor
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function __construct() {
		$this->init();
		$this->setup_hooks();
	}

	/**
	 * Initialize the wizard
	 *
	 * @since BuddyBoss [BBVERSION]
	 * @return void
	 */
	abstract protected function init();

	/**
	 * Setup WordPress hooks
	 *
	 * @since BuddyBoss [BBVERSION]
	 * @return void
	 */
	protected function setup_hooks() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_' . $this->wizard_id . '_should_show', array( $this, 'ajax_should_show' ) );
		add_action( 'wp_ajax_' . $this->wizard_id . '_complete', array( $this, 'ajax_complete' ) );
		add_action( 'wp_ajax_' . $this->wizard_id . '_save_step', array( $this, 'ajax_save_step' ) );
	}

	/**
	 * Check if the wizard should be shown
	 *
	 * @since BuddyBoss [BBVERSION]
	 * @return bool
	 */
	abstract public function should_show();

	/**
	 * Get wizard completion status
	 *
	 * @since BuddyBoss [BBVERSION]
	 * @return bool
	 */
	public function is_completed() {
		return (bool) get_option( $this->completion_option, false );
	}

	/**
	 * Mark wizard as completed
	 *
	 * @since BuddyBoss [BBVERSION]
	 * @param array $data Completion data.
	 * @return bool
	 */
	public function mark_completed( $data = array() ) {
		$result = update_option( $this->completion_option, true );

		if ( $result ) {
			/**
			 * Fires when a setup wizard is completed
			 *
			 * @since BuddyBoss [BBVERSION]
			 *
			 * @param string $wizard_id Wizard identifier.
			 * @param array  $data      Completion data.
			 */
			do_action( 'bb_setup_wizard_completed', $this->wizard_id, $data );

			/**
			 * Fires when a specific setup wizard is completed
			 *
			 * @since BuddyBoss [BBVERSION]
			 *
			 * @param array $data Completion data.
			 */
			do_action( 'bb_setup_wizard_' . $this->wizard_id . '_completed', $data );
		}

		return $result;
	}

	/**
	 * Reset wizard completion status
	 *
	 * @since BuddyBoss [BBVERSION]
	 * @return bool
	 */
	public function reset() {
		$result = delete_option( $this->completion_option );

		if ( $result ) {
			/**
			 * Fires when a setup wizard is reset
			 *
			 * @since BuddyBoss [BBVERSION]
			 *
			 * @param string $wizard_id Wizard identifier.
			 */
			do_action( 'bb_setup_wizard_reset', $this->wizard_id );
		}

		return $result;
	}

	/**
	 * Enqueue wizard scripts and styles
	 *
	 * @since BuddyBoss [BBVERSION]
	 * @param string $hook_suffix Current admin page hook suffix.
	 * @return void
	 */
	public function enqueue_scripts( $hook_suffix ) {
		// Only load on admin pages.
		if ( ! is_admin() ) {
			return;
		}

		$this->enqueue_wizard_assets();
		$this->localize_wizard_data();
	}

	/**
	 * Enqueue wizard-specific assets
	 *
	 * @since BuddyBoss [BBVERSION]
	 * @return void
	 */
	abstract protected function enqueue_wizard_assets();

	/**
	 * Localise wizard data for JavaScript
	 *
	 * @since BuddyBoss [BBVERSION]
	 * @return void
	 */
	abstract protected function localize_wizard_data();

	/**
	 * Get wizard steps
	 *
	 * @since BuddyBoss [BBVERSION]
	 * @return array
	 */
	public function get_steps() {
		return $this->steps;
	}

	/**
	 * Get current step
	 *
	 * @since BuddyBoss [BBVERSION]
	 * @return string
	 */
	public function get_current_step() {
		return $this->current_step;
	}

	/**
	 * Set current step
	 *
	 * @since BuddyBoss [BBVERSION]
	 * @param string $step Step identifier.
	 * @return void
	 */
	public function set_current_step( $step ) {
		$this->current_step = $step;
	}

	/**
	 * Get wizard ID
	 *
	 * @since BuddyBoss [BBVERSION]
	 * @return string
	 */
	public function get_wizard_id() {
		return $this->wizard_id;
	}

	/**
	 * Get wizard name
	 *
	 * @since BuddyBoss [BBVERSION]
	 * @return string
	 */
	public function get_wizard_name() {
		return $this->wizard_name;
	}

	/**
	 * Validate user permissions
	 *
	 * @since BuddyBoss [BBVERSION]
	 * @return bool
	 */
	protected function validate_permissions() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Validate nonce
	 *
	 * @since BuddyBoss [BBVERSION]
	 * @param string $nonce Nonce to validate.
	 * @return bool
	 */
	protected function validate_nonce( $nonce ) {
		return wp_verify_nonce( $nonce, $this->wizard_id . '_nonce' );
	}

	/**
	 * Send a JSON success response
	 *
	 * @since BuddyBoss [BBVERSION]
	 * @param mixed $data Response data.
	 * @return void
	 */
	protected function send_json_success( $data = null ) {
		wp_send_json_success( $data );
	}

	/**
	 * Send JSON error response
	 *
	 * @since BuddyBoss [BBVERSION]
	 * @param mixed $data Error data.
	 * @return void
	 */
	protected function send_json_error( $data = null ) {
		wp_send_json_error( $data );
	}

	/**
	 * AJAX handler to check if wizard should be shown
	 *
	 * @since BuddyBoss [BBVERSION]
	 * @return void
	 */
	public function ajax_should_show() {
		// Verify nonce.
		if ( ! isset( $_POST['nonce'] ) || ! $this->validate_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) ) ) {
			$this->send_json_error(
				array(
					'message' => __( 'Invalid security token.', 'buddyboss' ),
				)
			);
		}

		$should_show = $this->should_show();

		$this->send_json_success(
			array(
				'shouldShow' => $should_show,
			)
		);
	}

	/**
	 * AJAX handler to complete wizard
	 *
	 * @since BuddyBoss [BBVERSION]
	 * @return void
	 */
	abstract public function ajax_complete();

	/**
	 * AJAX handler to save the current step
	 *
	 * @since BuddyBoss [BBVERSION]
	 * @return void
	 */
	public function ajax_save_step() {
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

		// Get step data.
		$step_data = array();
		if ( isset( $_POST['stepData'] ) ) {
			$step_data = json_decode( wp_unslash( $_POST['stepData'] ), true );
		}

		// Process step data.
		$result = $this->process_step_data( $step_data );

		if ( $result ) {
			$this->send_json_success(
				array(
					'message' => __( 'Step saved successfully.', 'buddyboss' ),
				)
			);
		} else {
			$this->send_json_error(
				array(
					'message' => __( 'Failed to save step data.', 'buddyboss' ),
				)
			);
		}
	}

	/**
	 * Process step data
	 *
	 * @since BuddyBoss [BBVERSION]
	 * @param array $step_data Step data to process.
	 * @return bool
	 */
	protected function process_step_data( $step_data ) {
		// Override in child classes.
		return true;
	}

	/**
	 * Get wizard assets
	 *
	 * @since BuddyBoss [BBVERSION]
	 * @return array
	 */
	protected function get_wizard_assets() {
		return array();
	}

	/**
	 * Create nonce for wizard
	 *
	 * @since BuddyBoss [BBVERSION]
	 * @return string
	 */
	protected function create_nonce() {
		return wp_create_nonce( $this->wizard_id . '_nonce' );
	}
}
