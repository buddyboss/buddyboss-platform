<?php
/**
 * BuddyBoss Setup Wizard Manager Base Class
 *
 * Provides common functionality for all setup wizards in the BuddyBoss Platform.
 * This abstract class should be extended by specific wizard implementations.
 *
 * @package BuddyBoss\Core\Administration
 * @subpackage SetupWizard
 * @since   BuddyBoss 2.10.0
 * @author  BuddyBoss
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Base Setup Wizard Manager Class
 *
 * Provides common functionality for all setup wizards in the BuddyBoss Platform.
 * This abstract class should be extended by specific wizard implementations.
 *
 * @since BuddyBoss 2.10.0
 */
abstract class BB_Setup_Wizard_Manager {

	/**
	 * Wizard identifier
	 *
	 * @since BuddyBoss 2.10.0
	 * @var   string
	 */
	protected $wizard_id = '';

	/**
	 * Wizard name
	 *
	 * @since BuddyBoss 2.10.0
	 * @var   string
	 */
	protected $wizard_name = '';

	/**
	 * Wizard version
	 *
	 * @since BuddyBoss 2.10.0
	 * @var   string
	 */
	protected $wizard_version = '1.0.0';

	/**
	 * Assets directory path
	 *
	 * @since BuddyBoss 2.10.0
	 * @var   string
	 */
	protected $assets_dir = '';

	/**
	 * Assets directory URL
	 *
	 * @since BuddyBoss 2.10.0
	 * @var   string
	 */
	protected $assets_url = '';

	/**
	 * Wizard steps
	 *
	 * @since BuddyBoss 2.10.0
	 * @var   array
	 */
	protected $steps = array();

	/**
	 * Current step
	 *
	 * @since BuddyBoss 2.10.0
	 * @var   string
	 */
	protected $current_step = '';

	/**
	 * Configuration options
	 *
	 * @since BuddyBoss 2.10.0
	 * @var   array
	 */
	protected $config = array();

	/**
	 * Default configuration options
	 *
	 * @since BuddyBoss 2.10.0
	 * @var   array
	 */
	private $default_config = array(
		'admin_page'                 => null,
		'option_prefix'              => 'bb_wizard',
		'completion_option'          => null,
		'transient_timeout'          => 30,
		'enable_analytics'           => true,
		'enable_activation_redirect' => true,
		'enable_new_activation'      => false,
		'auto_cleanup'               => true,
		'capability_required'        => 'manage_options',
		'wizard_title'               => 'Setup Wizard',
		'wizard_description'         => 'Complete the setup to get started',
		'skip_on_multisite'          => false,
		'enable_react_frontend'      => false,
		'react_directory'            => null,
		'react_script_handle'        => null,
		'react_style_handle'         => null,
		'react_script_name'          => null,
		'react_style_name'           => null,
		'react_localize_object'      => null,
		'react_assets'               => array(),
		'react_dependencies'         => array(),
		'steps'                      => array(),
		'step_options'               => array(),
		'custom_hooks'               => array(),
	);

	/**
	 * Constructor
	 *
	 * @since BuddyBoss 2.10.0
	 *
	 * @param array $config Configuration options.
	 */
	public function __construct( $config = array() ) {
		$this->config = wp_parse_args( $config, $this->default_config );
		$this->validate_config();
		$this->init();
		$this->setup_hooks();
		$this->init_activation_hooks();
	}

	/**
	 * Initialize the wizard (must be implemented by child classes)
	 *
	 * @since BuddyBoss 2.10.0
	 * @return void
	 */
	abstract protected function init();

	/**
	 * Check if the wizard should be shown (must be implemented by child classes)
	 *
	 * @since BuddyBoss 2.10.0
	 * @return bool
	 */
	abstract public function should_show();

	/**
	 * AJAX handler to complete wizard (must be implemented by child classes)
	 *
	 * @since BuddyBoss 2.10.0
	 * @return void
	 */
	abstract public function ajax_complete();

	/**
	 * Enqueue wizard-specific assets (must be implemented by child classes)
	 *
	 * @since BuddyBoss 2.10.0
	 * @return void
	 */
	abstract protected function enqueue_wizard_assets();

	/**
	 * Localize wizard data for JavaScript (should be implemented by child classes)
	 *
	 * @since BuddyBoss 2.10.0
	 * @return array
	 */
	protected function localize_wizard_data() {
		return $this->get_localize_data();
	}

	/**
	 * Validate required configuration options
	 *
	 * @since BuddyBoss 2.10.0
	 * @return void
	 */
	private function validate_config() {
		// Set default completion option if not provided.
		if ( ! $this->config['completion_option'] ) {
			$this->config['completion_option'] = $this->config['option_prefix'] . '_' . $this->wizard_id . '_completed';
		}

		// Set default admin page if not provided.
		if ( ! $this->config['admin_page'] ) {
			$this->config['admin_page'] = $this->wizard_id . '_setup_wizard';
		}

		// Set default React handles if not provided.
		if ( $this->config['enable_react_frontend'] ) {
			if ( ! $this->config['react_script_handle'] ) {
				$this->config['react_script_handle'] = $this->wizard_id . '_wizard_react';
			}
			if ( ! $this->config['react_style_handle'] ) {
				$this->config['react_style_handle'] = $this->wizard_id . '_wizard_react_styles';
			}
			if ( ! $this->config['react_localize_object'] ) {
				$this->config['react_localize_object'] = $this->wizard_id . 'WizardData';
			}
		}
	}

	/**
	 * Get configuration option
	 *
	 * @since BuddyBoss 2.10.0
	 *
	 * @param string $key     Configuration key.
	 * @param mixed  $default Default value if key doesn't exist.
	 * @return mixed Configuration value.
	 */
	public function get_config( $key, $default = null ) {
		return isset( $this->config[ $key ] ) ? $this->config[ $key ] : $default;
	}

	/**
	 * Update configuration option
	 *
	 * @since BuddyBoss 2.10.0
	 *
	 * @param string $key   Configuration key.
	 * @param mixed  $value New value.
	 * @return void
	 */
	public function set_config( $key, $value ) {
		$this->config[ $key ] = $value;
	}

	/**
	 * Initialise activation hooks for first-time setup
	 *
	 * @since BuddyBoss 2.10.0
	 * @return void
	 */
	private function init_activation_hooks() {
		if ( ! $this->config['enable_activation_redirect'] ) {
			return;
		}

		// Hook into plugin activation.
		add_action( 'bb_do_activation_redirect', array( $this, 'handle_activation_redirect' ) );
	}

	/**
	 * Handle activation redirect to wizard
	 *
	 * @since BuddyBoss 2.10.0
	 * @return void
	 */
	public function handle_activation_redirect( $query_args ) {
		// Bail if no activation redirect.
		if ( isset( $_GET['activate-multi'] ) ) {
			return;
		}

		// Bail if configured to skip on multisite.
		if ( $this->config['skip_on_multisite'] && is_multisite() ) {
			return;
		}

		// Check capability.
		if ( ! current_user_can( $this->config['capability_required'] ) ) {
			return;
		}

		// If this is a new install, call the child class to handle the redirect.
		if ( $this->is_new_install() ) {
			$this->perform_activation_redirect( $query_args );
		}
	}

	/**
	 * Perform the actual activation redirect (should be implemented by child classes)
	 *
	 * @since BuddyBoss 2.10.0
	 * @param array $query_args Query arguments for the redirect.
	 * @return void
	 */
	protected function perform_activation_redirect( $query_args ) {
		// Default implementation - redirect to admin page.
		$query_args['page'] = $this->config['admin_page'];

		$query_args['bb_wizard_activation'] = $this->wizard_id;

		$admin_url = bp_get_admin_url(
			add_query_arg(
				$query_args,
				'admin.php'
			)
		);

		wp_safe_redirect( $admin_url );
		exit;
	}

	/**
	 * Check if this is a new installation
	 *
	 * @since BuddyBoss 2.10.0
	 * @return bool
	 */
	private function is_new_install() {
		// Check if wizard has been completed before.
		$completed = $this->get_option( $this->config['completion_option'], false );
		return ! $completed;
	}

	/**
	 * Set up WordPress hooks
	 *
	 * @since BuddyBoss 2.10.0
	 * @return void
	 */
	protected function setup_hooks() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_' . $this->wizard_id . '_should_show', array( $this, 'ajax_should_show' ) );
		add_action( 'wp_ajax_' . $this->wizard_id . '_save_step', array( $this, 'ajax_save_step' ) );
		add_action( 'wp_ajax_' . $this->wizard_id . '_complete', array( $this, 'ajax_complete' ) );

		// Initialise React frontend if enabled.
		if ( $this->config['enable_react_frontend'] ) {
			$this->init_react_frontend();
		}
	}

	/**
	 * Initialise React frontend integration
	 *
	 * @since BuddyBoss 2.10.0
	 * @return void
	 */
	private function init_react_frontend() {
		// Register additional AJAX handlers for React communication.
		add_action( 'wp_ajax_' . $this->wizard_id . '_save_step_progress', array( $this, 'ajax_save_step_progress' ) );
		add_action( 'wp_ajax_' . $this->wizard_id . '_save_preferences', array( $this, 'ajax_save_preferences' ) );
		add_action( 'wp_ajax_' . $this->wizard_id . '_get_wizard_data', array( $this, 'ajax_get_wizard_data' ) );
	}

	/**
	 * Get wizard completion status
	 *
	 * @since BuddyBoss 2.10.0
	 * @return bool
	 */
	public function is_completed() {
		return (bool) $this->get_option( $this->config['completion_option'], false );
	}

	/**
	 * Mark wizard as completed
	 *
	 * @since BuddyBoss 2.10.0
	 *
	 * @param array $data Completion data.
	 * @return bool
	 */
	public function mark_completed( $data = array() ) {
		$result = $this->update_option( $this->config['completion_option'], true );

		if ( $result ) {
			// Fire custom completion hooks.
			if ( ! empty( $this->config['custom_hooks']['completion'] ) ) {
				foreach ( $this->config['custom_hooks']['completion'] as $hook ) {
					do_action( $hook, $this->wizard_id );
				}
			}

			/**
			 * Fires when a setup wizard is completed
			 *
			 * @since BuddyBoss 2.10.0
			 *
			 * @param string $wizard_id The wizard ID.
			 * @param array  $data      Completion data.
			 */
			do_action( 'bb_setup_wizard_completed', $this->wizard_id, $data );

			// Cleanup old data if enabled.
			if ( $this->config['auto_cleanup'] ) {
				$this->cleanup_wizard_data();
			}
		}

		return $result;
	}

	/**
	 * Get wizard progress data
	 *
	 * @since BuddyBoss 2.10.0
	 * @return array Progress data.
	 */
	public function get_progress() {
		$option_name = $this->config['option_prefix'] . '_progress_' . $this->wizard_id;
		$progress    = $this->get_option( $option_name, array() );

		// Set defaults if empty.
		if ( empty( $progress ) ) {
			$progress = array(
				'current_step'          => 0,
				'max_step_reached'      => 0,
				'total_steps'           => 0,
				'completion_percentage' => 0.0,
				'status'                => 'not_started',
				'started_at'            => null,
				'completed_at'          => null,
				'updated_at'            => current_time( 'mysql' ),
			);
		}

		return $progress;
	}

	/**
	 * Save step progress
	 *
	 * @since BuddyBoss 2.10.0
	 *
	 * @param int   $step Step number.
	 * @param array $data Step data.
	 * @return void
	 */
	public function save_step_progress( $step, $data ) {
		// Save step progress.
		$progress                     = $this->get_progress();
		$progress['current_step']     = $step;
		$progress['max_step_reached'] = max( $progress['max_step_reached'], $step );
		$progress['updated_at']       = current_time( 'mysql' );

		if ( 'not_started' === $progress['status'] ) {
			$progress['status']     = 'in_progress';
			$progress['started_at'] = current_time( 'mysql' );
		}

		$filtered_steps = array_filter(
			$this->steps,
			function ( $step ) {
				return empty( $step['skip_progress'] );
			}
		);

		// Calculate completion percentage.
		$progress['total_steps']           = count( $filtered_steps );
		$progress['completion_percentage'] = $progress['total_steps'] > 0 ? ( $step / $progress['total_steps'] ) * 100 : 0;

		if ( 100 === $progress['completion_percentage'] && 'completed' !== $progress['status'] ) {
			$progress['status']       = 'completed';
			$progress['completed_at'] = current_time( 'mysql' );
		} elseif ( $progress['completion_percentage'] < 100 && 'completed' === $progress['status'] ) {
			$progress['status']       = 'in_progress';
			$progress['completed_at'] = null;
		}

		// Save progress.
		$option_name = $this->config['option_prefix'] . '_progress_' . $this->wizard_id;
		$this->update_option( $option_name, $progress );

		// Save step tracking.
		$this->save_step_tracking( $step, $data );

		// Send to BB_Telemetry if enabled and available.
		if ( $this->config['enable_analytics'] ) {
			$this->send_telemetry_event( 'progress', $progress );
		}

		// Fire custom step completion hooks.
		if ( ! empty( $this->config['custom_hooks']['step_completion'] ) ) {
			foreach ( $this->config['custom_hooks']['step_completion'] as $hook ) {
				do_action( $hook, $this->wizard_id, $step, $data );
			}
		}

		/**
		 * Fires when a setup wizard step is completed
		 *
		 * @since BuddyBoss 2.10.0
		 *
		 * @param string $wizard_id The wizard ID.
		 * @param int    $step      Step number.
		 * @param array  $data      Step data.
		 */
		do_action( 'bb_setup_wizard_step_completed', $this->wizard_id, $step, $data );
	}

	/**
	 * Save step tracking data
	 *
	 * @since BuddyBoss 2.10.0
	 *
	 * @param int   $step Step number.
	 * @param array $data Step data.
	 * @return void
	 */
	private function save_step_tracking( $step, $data ) {
		$option_name = $this->config['option_prefix'] . '_step_tracking_' . $this->wizard_id;
		$tracking    = $this->get_option( $option_name, array( 'steps' => array() ) );

		// Check if this step should skip progress tracking.
		if ( ! empty( $this->steps ) && isset( $this->steps[ $step ] ) && ! empty( $this->steps[ $step ]['skip_progress'] ) ) {
			// If the step is configured to skip progress, do not track it.
			return;
		}

		if ( ! isset( $tracking['steps'][ $step ] ) ) {
			$tracking['steps'][ $step ] = array(
				'step_key'         => $data['step_key'] ?? $this->steps[ $step ]['key'],
				'form_data'        => array(),
				'first_visited_at' => current_time( 'mysql' ),
				'completed_at'     => null,
			);
		}

		// Only mark as completed if we actually have form data (indicates user progressed).
		if (
			(
				isset( $data['form_data'] ) &&
				! empty( $data['form_data'] ) &&
				(
					! isset( $tracking['steps'][ $step ]['status'] ) ||
					'visited' === $tracking['steps'][ $step ]['status']
				)
			) ||
			(
				! empty( $tracking['steps'][ $step ]['step_key'] ) &&
				$tracking['steps'][ $step ]['step_key'] === $data['step_key'] &&
				end( $this->steps )['key'] === $data['step_key']
			)
		) {
			$tracking['steps'][ $step ]['status']       = 'completed';
			$tracking['steps'][ $step ]['form_data']    = isset( $data['form_data'] ) ? $data['form_data'] : array();
			$tracking['steps'][ $step ]['completed_at'] = current_time( 'mysql' );
			// Ensure status is at least visited.
		} elseif (
			! isset( $tracking['steps'][ $step ]['status'] ) ||
			(
				'visited' !== $tracking['steps'][ $step ]['status'] &&
				'completed' !== $tracking['steps'][ $step ]['status']
			)
		) {
			$tracking['steps'][ $step ]['status'] = 'visited';
		}

		$this->update_option( $option_name, $tracking );

		// Send to BB_Telemetry if enabled and available.
		if ( $this->config['enable_analytics'] ) {
			// Filter out steps with skip_progress before sending to telemetry.
			$telemetry_tracking = $tracking;
			if ( ! empty( $telemetry_tracking['steps'] ) ) {
				$telemetry_tracking['steps'] = array_filter(
					$telemetry_tracking['steps'],
					function ( $step_data, $step_index ) {
						return empty( $this->steps[ $step_index ]['skip_progress'] );
					},
					ARRAY_FILTER_USE_BOTH
				);
			}
			$this->send_telemetry_event( 'step_tracking', $telemetry_tracking );
		}
	}

	/**
	 * Get user preferences for this wizard
	 *
	 * @since BuddyBoss 2.10.0
	 * @return array User preferences.
	 */
	public function get_preferences() {
		$option_name = $this->config['option_prefix'] . '_preferences_' . $this->wizard_id;
		return $this->get_option( $option_name, array() );
	}

	/**
	 * Save user preferences
	 *
	 * @since BuddyBoss 2.10.0
	 *
	 * @param array  $preferences User preferences.
	 * @param string $pref_key    Preference key (optional).
	 *
	 * @return void
	 */
	public function save_preferences( $preferences, $pref_key ) {
		$option_name        = $this->config['option_prefix'] . '_preferences_' . $this->wizard_id;
		$stored_preferences = $this->get_preferences();
		$new_preferences    = wp_parse_args( $preferences, $stored_preferences );

		$this->update_option( $option_name, $new_preferences );

		/**
		 * Fires when wizard preferences are saved
		 *
		 * @since BuddyBoss 2.10.0
		 *
		 * @param string $wizard_id   The wizard ID.
		 * @param array  $preferences User preferences.
		 * @param string $pref_key    Preference key.
		 */
		do_action( 'bb_setup_wizard_preferences_saved', $this->wizard_id, $preferences, $pref_key );
	}

	/**
	 * Reset wizard progress
	 *
	 * @since BuddyBoss 2.10.0
	 * @return void
	 */
	public function reset_wizard() {
		$progress_option = $this->config['option_prefix'] . '_progress_' . $this->wizard_id;
		$tracking_option = $this->config['option_prefix'] . '_step_tracking_' . $this->wizard_id;

		delete_option( $progress_option );
		delete_option( $tracking_option );

		// Reset completion status.
		$this->update_option( $this->config['completion_option'], false );

		/**
		 * Fires when a setup wizard is reset
		 *
		 * @since BuddyBoss 2.10.0
		 *
		 * @param string $wizard_id The wizard ID.
		 */
		do_action( 'bb_setup_wizard_reset', $this->wizard_id );
	}

	/**
	 * Send telemetry event if BB_Telemetry is available
	 *
	 * @since BuddyBoss 2.10.0
	 *
	 * @param string $event_type Event type.
	 * @param array  $data       Event data.
	 * @return void
	 */
	private function send_telemetry_event( $event_type, $data ) {
		// Only send telemetry if BB_Telemetry is available and BuddyBoss options are enabled.
		if ( class_exists( 'BB_Telemetry' ) ) {
			// Hook into BB_Telemetry system.
			add_filter(
				'bb_telemetry_platform_options',
				function ( $options ) use ( $event_type ) {
					$options[] = $this->config['option_prefix'] . "_{$event_type}_{$this->wizard_id}";
					return $options;
				}
			);

			// Force immediate telemetry sending after each onboarding step.
			$telemetry_instance = BB_Telemetry::instance();
			if ( $telemetry_instance ) {
				$telemetry_instance->bb_send_telemetry_report_to_analytics();
			}
		}

		// Fire custom analytics hooks.
		if ( ! empty( $this->config['custom_hooks']['analytics'] ) ) {
			foreach ( $this->config['custom_hooks']['analytics'] as $hook ) {
				do_action( $hook, $this->wizard_id, $event_type, $data );
			}
		}

		/**
		 * Fires when a wizard analytics event occurs
		 *
		 * @since BuddyBoss 2.10.0
		 *
		 * @param string $wizard_id  The wizard ID.
		 * @param string $event_type Event type.
		 * @param array  $data       Event data.
		 */
		do_action( 'bb_setup_wizard_analytics_event', $this->wizard_id, $event_type, $data );
	}

	/**
	 * Clean up wizard data after completion
	 *
	 * @since BuddyBoss 2.10.0
	 * @return void
	 */
	private function cleanup_wizard_data() {

		/**
		 * Fires when wizard cleanup occurs
		 *
		 * @since BuddyBoss 2.10.0
		 *
		 * @param string $wizard_id The wizard ID.
		 */
		do_action( 'bb_setup_wizard_cleanup', $this->wizard_id );
	}

	/**
	 * Get option value using the configured method
	 *
	 * @since BuddyBoss 2.10.0
	 *
	 * @param string $option_name Option name.
	 * @param mixed  $default     Default value.
	 * @return mixed Option value.
	 */
	private function get_option( $option_name, $default = false ) {
		if ( function_exists( 'bp_get_option' ) ) {
			return bp_get_option( $option_name, $default );
		}
		return get_option( $option_name, $default );
	}

	/**
	 * Update option value using configured method
	 *
	 * @since BuddyBoss 2.10.0
	 *
	 * @param string $option_name Option name.
	 * @param mixed  $value       Option value.
	 * @return bool Success status.
	 */
	private function update_option( $option_name, $value ) {
		if ( function_exists( 'bp_update_option' ) ) {
			return bp_update_option( $option_name, $value );
		}
		// Force autoload=false for wizard options to prevent them from being loaded on every page.
		return update_option( $option_name, $value, false );
	}

	/**
	 * Enqueue scripts and styles for the wizard
	 *
	 * @since BuddyBoss 2.10.0
	 *
	 * @param string $hook_suffix Current admin page hook suffix.
	 * @return void
	 */
	public function enqueue_scripts( $hook_suffix ) {
		// Only load on admin pages.
		if ( ! is_admin() ) {
			return;
		}

		// Check if we're on the wizard page or should show wizard.
		if ( ! $this->should_enqueue_scripts( $hook_suffix ) ) {
			return;
		}

		// Enqueue wizard-specific assets.
		$this->enqueue_wizard_assets();

		// Localize script with wizard data.
		if ( $this->config['enable_react_frontend'] && $this->config['react_script_handle'] ) {
			wp_localize_script(
				$this->config['react_script_handle'],
				$this->config['react_localize_object'],
				$this->localize_wizard_data()
			);
		}
	}

	/**
	 * Check if scripts should be enqueued
	 *
	 * @since BuddyBoss 2.10.0
	 *
	 * @param string $hook_suffix Current admin page hook suffix.
	 *
	 * @return bool Whether to enqueue scripts.
	 */
	private function should_enqueue_scripts( $hook_suffix ) {
		// Always enqueue if we're on the wizard admin page.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['page'] ) && sanitize_text_field( wp_unslash( $_GET['page'] ) ) === $this->config['admin_page'] ) {
			return true;
		}

		// Check if wizard should be shown (for modal/overlay wizards).
		return $this->should_show();
	}

	/**
	 * Get data to localize for React components
	 *
	 * @since BuddyBoss 2.10.0
	 * @return array Localized data for React.
	 */
	private function get_localize_data() {
		$progress    = $this->get_progress();
		$preferences = $this->get_preferences();

		return array(
			'nonce'       => wp_create_nonce( $this->wizard_id . '_wizard_nonce' ),
			'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
			'wizardKey'   => $this->wizard_id,
			'shouldShow'  => $this->should_show(),
			'config'      => array(
				'title'           => $this->config['wizard_title'],
				'description'     => $this->config['wizard_description'],
				'adminPage'       => $this->config['admin_page'],
				'enableAnalytics' => $this->config['enable_analytics'],
			),
			'steps'       => $this->config['steps'],
			'stepOptions' => $this->config['step_options'],
			'progress'    => $progress,
			'preferences' => $preferences,
			'assets'      => array_merge(
				array(
					'pluginUrl' => buddypress()->plugin_url,
					'reactDir'  => $this->config['react_directory'],
				),
				$this->config['react_assets']
			),
			'actions'     => array(
				'shouldShow'      => $this->wizard_id . '_should_show',
				'saveProgress'    => $this->wizard_id . '_save_step_progress',
				'complete'        => $this->wizard_id . '_complete',
				'savePreferences' => $this->wizard_id . '_save_preferences',
				'getWizardData'   => $this->wizard_id . '_get_wizard_data',
			),
		);
	}

	/**
	 * AJAX handler to check if wizard should be shown
	 *
	 * @since BuddyBoss 2.10.0
	 * @return void
	 */
	public function ajax_should_show() {
		// Verify nonce for security.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), $this->wizard_id . '_wizard_nonce' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Invalid security token.', 'buddyboss' ),
				)
			);
		}

		$should_show = $this->should_show();

		wp_send_json_success(
			array(
				'shouldShow' => $should_show,
			)
		);
	}

	/**
	 * AJAX handler to save step progress
	 *
	 * @since BuddyBoss 2.10.0
	 * @return void
	 */
	public function ajax_save_step_progress() {
		// Verify nonce for security.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), $this->wizard_id . '_wizard_nonce' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Invalid security token.', 'buddyboss' ),
				)
			);
		}

		// Check user capabilities.
		if ( ! current_user_can( $this->config['capability_required'] ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'You do not have sufficient permissions to perform this action.', 'buddyboss' ),
				)
			);
		}

		// Get and sanitize step data.
		$step     = isset( $_POST['step'] ) ? intval( $_POST['step'] ) : 0;
		$raw_data = isset( $_POST['data'] ) ? wp_unslash( $_POST['data'] ) : '';
		$data     = $this->sanitize_step_data( $raw_data );

		// If wrapper keys are present (as sent from React) use only the inner form_data array for tracking.
		$data_for_tracking = $data;

		// Check if this step should skip progress tracking.
		if ( ! empty( $this->steps ) && isset( $this->steps[ $step ] ) && ! empty( $this->steps[ $step ]['skip_progress'] ) ) {
			// Skip progress tracking but still return success.
			wp_send_json_success(
				array(
					'message'  => __( 'Step acknowledged.', 'buddyboss' ),
					'step'     => $step,
					'progress' => $this->get_progress(),
				)
			);
		}

		// Save step progress.
		$this->save_step_progress( $step, $data_for_tracking );

		wp_send_json_success(
			array(
				'message'  => __( 'Step progress saved successfully.', 'buddyboss' ),
				'step'     => $step,
				'progress' => $this->get_progress(),
			)
		);
	}

	/**
	 * AJAX handler to save preferences
	 *
	 * @since BuddyBoss 2.10.0
	 * @return void
	 */
	public function ajax_save_preferences() {
		// Verify nonce for security.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), $this->wizard_id . '_wizard_nonce' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Invalid security token.', 'buddyboss' ),
				)
			);
		}

		// Check user capabilities.
		if ( ! current_user_can( $this->config['capability_required'] ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'You do not have sufficient permissions to perform this action.', 'buddyboss' ),
				)
			);
		}

		// Get and sanitize preferences.
		$raw_prefs   = isset( $_POST['preferences'] ) ? wp_unslash( $_POST['preferences'] ) : '';
		$preferences = $this->sanitize_preferences( $raw_prefs );

		$pref_key = isset( $_POST['preference_key'] ) ? sanitize_key( wp_unslash( $_POST['preference_key'] ) ) : '';

		if ( ! empty( $pref_key ) ) {
			$preferences = array( $pref_key => $preferences );
		}

		// Save preferences.
		$this->save_preferences( $preferences, $pref_key );

		wp_send_json_success(
			array(
				'message'     => __( 'Preferences saved successfully.', 'buddyboss' ),
				'preferences' => $preferences,
			)
		);
	}

	/**
	 * AJAX handler to get wizard data
	 *
	 * @since BuddyBoss 2.10.0
	 * @return void
	 */
	public function ajax_get_wizard_data() {
		// Verify nonce for security.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), $this->wizard_id . '_wizard_nonce' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Invalid security token.', 'buddyboss' ),
				)
			);
		}

		wp_send_json_success(
			array(
				'wizardData' => $this->get_localize_data(),
			)
		);
	}

	/**
	 * AJAX handler for step save (legacy support)
	 *
	 * @since BuddyBoss 2.10.0
	 * @return void
	 */
	public function ajax_save_step() {
		// Redirect to the new save_step_progress handler.
		$this->ajax_save_step_progress();
	}

	/**
	 * Sanitize step data for storage
	 *
	 * @since BuddyBoss 2.10.0
	 *
	 * @param mixed $data Raw step data.
	 * @return array Sanitized step data.
	 */
	private function sanitize_step_data( $data ) {
		// Allow JSON strings coming from JS – decode them first.
		if ( ! is_array( $data ) ) {
			if ( is_string( $data ) ) {
				$maybe_array = json_decode( $data, true );
				if ( json_last_error() === JSON_ERROR_NONE && is_array( $maybe_array ) ) {
					$data = $maybe_array;
				} else {
					return array();
				}
			} else {
				return array();
			}
		}

		$sanitized = array();
		foreach ( $data as $key => $value ) {
			$sanitized_key = sanitize_key( $key );

			if ( is_array( $value ) ) {
				$sanitized[ $sanitized_key ] = $this->sanitize_step_data( $value );
			} else {
				$sanitized[ $sanitized_key ] = sanitize_text_field( $value );
			}
		}

		return $sanitized;
	}

	/**
	 * Sanitize preferences for storage
	 *
	 * @since BuddyBoss 2.10.0
	 *
	 * @param mixed $preferences Raw preferences data.
	 * @return array Sanitized preferences.
	 */
	private function sanitize_preferences( $preferences ) {
		// Accept JSON strings coming from AJAX and decode them first.
		if ( ! is_array( $preferences ) ) {
			if ( is_string( $preferences ) ) {
				$decoded = json_decode( $preferences, true );
				if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded ) ) {
					$preferences = $decoded;
				} else {
					return array();
				}
			} else {
				return array();
			}
		}

		$sanitized = array();
		foreach ( $preferences as $key => $value ) {
			$sanitized_key = sanitize_key( $key );

			if ( is_array( $value ) ) {
				$sanitized[ $sanitized_key ] = $this->sanitize_preferences( $value );
			} elseif ( is_bool( $value ) ) {
				$sanitized[ $sanitized_key ] = (bool) $value;
			} elseif ( is_numeric( $value ) ) {
				$sanitized[ $sanitized_key ] = is_int( $value ) ? (int) $value : (float) $value;
			} else {
				$sanitized[ $sanitized_key ] = sanitize_text_field( $value );
			}
		}

		return $sanitized;
	}
}
