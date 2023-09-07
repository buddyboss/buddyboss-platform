<?php
/**
 * Forums Converter
 *
 * Based on the hard work of Adam Ellis at http://bbconverter.com
 *
 * @package BuddyBoss\Administration
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Main BBP_Converter Class
 */
class BBP_Converter {

	/**
	 * @var int Number of rows
	 */
	public $max = 0;

	/**
	 * @var int Start
	 */
	public $start = 0;

	/**
	 * @var int Step in converter process
	 */
	public $step = 0;

	/**
	 * @var int Number of rows
	 */
	public $rows = 0;

	/**
	 * @var int Maximum number of converter steps
	 */
	public $max_steps = 17;

	/**
	 * @var int Number of rows in the current step
	 */
	public $rows_in_step = 0;

	/**
	 * @var int Percent complete of current step
	 */
	public $step_percentage = 0;

	/**
	 * @var int Percent complete of all step
	 */
	public $total_percentage = 0;

	/**
	 * @var int Name of source forum platform
	 */
	public $platform = '';

	/**
	 * @var BBP_Converter_Base Type of converter to use
	 */
	public $converter = null;

	/**
	 * @var string Path to included platforms
	 */
	public $converters_dir = '';

	/**
	 * @var array Map of steps to methods
	 */
	private $steps = array(
		1  => 'sync_table',
		2  => 'users',
		3  => 'passwords',
		4  => 'forums',
		5  => 'forum_hierarchy',
		6  => 'forum_subscriptions',
		7  => 'topics',
		8  => 'topics_authors',
		9  => 'stickies',
		10 => 'super_stickies',
		11 => 'closed_topics',
		12 => 'topic_tags',
		13 => 'topic_subscriptions',
		14 => 'topic_favorites',
		15 => 'replies',
		16 => 'reply_authors',
		17 => 'reply_hierarchy',
	);

	/**
	 * The main Forums Converter loader
	 *
	 * @since bbPress (r3813)
	 * @uses BBP_Converter::includes() Include the required files
	 * @uses BBP_Converter::setup_actions() Setup the actions
	 */
	public function __construct() {
		$this->setup_globals();
		$this->setup_actions();
	}

	/**
	 * Admin globals
	 *
	 * @since 2.6.0 bbPress (r6598)
	 */
	public function setup_globals() {
		$this->converters_dir = bbpress()->admin->admin_dir . 'converters/';
	}

	/**
	 * Setup the default actions
	 *
	 * @since bbPress (r3813)
	 * @uses add_action() To add various actions
	 */
	private function setup_actions() {

		// Attach to the admin head with our ajax requests cycle and css.
		add_action( 'admin_head-buddyboss_page_bbp-converter', array( $this, 'admin_head' ) );

		// Attach the bbConverter admin settings action to the WordPress admin init action.
		add_action( 'bbp_register_admin_settings', array( $this, 'register_admin_settings' ) );

		// Attach to the admin ajax request to process cycles.
		add_action( 'wp_ajax_bbp_converter_process', array( $this, 'process_callback' ) );
	}

	/**
	 * Register the settings
	 *
	 * @since bbPress (r3813)
	 * @uses add_settings_section() To add our own settings section
	 * @uses add_settings_field() To add various settings fields
	 * @uses register_setting() To register various settings
	 */
	public function register_admin_settings() {
		global $wp_settings_sections;
		// Add the main section.
		add_settings_section( 'bbpress_converter_main', '', 'bbp_converter_setting_callback_main_section', 'bbpress_converter' );
		if ( function_exists( 'bb_admin_icons' ) ) {
			$meta_icon = bb_admin_icons( 'bbpress_converter_main' );
			if ( ! empty( $meta_icon ) ) {
				$wp_settings_sections['bbpress_converter']['bbpress_converter_main']['icon'] = $meta_icon;
			}
		}
		// System Select.
		add_settings_field( '_bbp_converter_platform', __( 'Select Platform', 'buddyboss' ), 'bbp_converter_setting_callback_platform', 'bbpress_converter', 'bbpress_converter_main' );
		register_setting( 'bbpress_converter_main', '_bbp_converter_platform', 'sanitize_text_field' );

		// Database Server.
		add_settings_field( '_bbp_converter_db_server', __( 'Database Server', 'buddyboss' ), 'bbp_converter_setting_callback_dbserver', 'bbpress_converter', 'bbpress_converter_main' );
		register_setting( 'bbpress_converter_main', '_bbp_converter_db_server', 'sanitize_text_field' );

		// Database Server Port.
		add_settings_field( '_bbp_converter_db_port', __( 'Database Port', 'buddyboss' ), 'bbp_converter_setting_callback_dbport', 'bbpress_converter', 'bbpress_converter_main' );
		register_setting( 'bbpress_converter_main', '_bbp_converter_db_port', 'intval' );

		// Database Name.
		add_settings_field( '_bbp_converter_db_name', __( 'Database Name', 'buddyboss' ), 'bbp_converter_setting_callback_dbname', 'bbpress_converter', 'bbpress_converter_main' );
		register_setting( 'bbpress_converter_main', '_bbp_converter_db_name', 'sanitize_text_field' );

		// Database User.
		add_settings_field( '_bbp_converter_db_user', __( 'Database User', 'buddyboss' ), 'bbp_converter_setting_callback_dbuser', 'bbpress_converter', 'bbpress_converter_main' );
		register_setting( 'bbpress_converter_main', '_bbp_converter_db_user', 'sanitize_text_field' );

		// Database Pass.
		add_settings_field( '_bbp_converter_db_pass', __( 'Database Password', 'buddyboss' ), 'bbp_converter_setting_callback_dbpass', 'bbpress_converter', 'bbpress_converter_main' );
		register_setting( 'bbpress_converter_main', '_bbp_converter_db_pass', 'sanitize_text_field' );

		// Database Prefix.
		add_settings_field( '_bbp_converter_db_prefix', __( 'Table Prefix', 'buddyboss' ), 'bbp_converter_setting_callback_dbprefix', 'bbpress_converter', 'bbpress_converter_main' );
		register_setting( 'bbpress_converter_main', '_bbp_converter_db_prefix', 'sanitize_text_field' );

		// Add the options section.
		add_settings_section( 'bbpress_converter_opt', __( '', 'buddyboss' ), 'bbp_converter_setting_callback_options_section', 'bbpress_converter' );

		// Rows Limit.
		add_settings_field( '_bbp_converter_rows', __( 'Rows Limit', 'buddyboss' ), 'bbp_converter_setting_callback_rows', 'bbpress_converter', 'bbpress_converter_opt' );
		register_setting( 'bbpress_converter_opt', '_bbp_converter_rows', 'intval' );

		// Delay Time.
		add_settings_field( '_bbp_converter_delay_time', __( 'Delay Time', 'buddyboss' ), 'bbp_converter_setting_callback_delay_time', 'bbpress_converter', 'bbpress_converter_opt' );
		register_setting( 'bbpress_converter_opt', '_bbp_converter_delay_time', 'intval' );

		// Convert Users ?
		add_settings_field( '_bbp_converter_convert_users', __( 'Convert Users', 'buddyboss' ), 'bbp_converter_setting_callback_convert_users', 'bbpress_converter', 'bbpress_converter_opt' );
		register_setting( 'bbpress_converter_opt', '_bbp_converter_convert_users', 'intval' );

		// Restart.
		add_settings_field( '_bbp_converter_restart', __( 'Start Over', 'buddyboss' ), 'bbp_converter_setting_callback_restart', 'bbpress_converter', 'bbpress_converter_opt' );
		register_setting( 'bbpress_converter_opt', '_bbp_converter_restart', 'intval' );

		// Clean.
		add_settings_field( '_bbp_converter_clean', __( 'Purge Previous Import', 'buddyboss' ), 'bbp_converter_setting_callback_clean', 'bbpress_converter', 'bbpress_converter_opt' );
		register_setting( 'bbpress_converter_opt', '_bbp_converter_clean', 'intval' );
	}

	/**
	 * Admin scripts
	 *
	 * @since bbPress (r3813)
	 */
	public function admin_head() {
		// Enqueue scripts.
		wp_enqueue_script( 'bbp-converter' );

		// Localize JS.
		wp_localize_script(
			'bbp-converter',
			'BBP_Converter',
			array(

				// Nonce.
				'ajax_nonce' => wp_create_nonce( 'bbp_converter_process' ),

				// UI State.
				'state'      => array(
					'delay'         => (int) get_option( '_bbp_converter_delay_time', 2 ),
					'started'       => (bool) get_option( '_bbp_converter_step', 0 ),
					'running'       => false,
					'status'        => false,
					'step_percent'  => $this->step_percentage,
					'total_percent' => $this->total_percentage,
				),

				// Strings.
				'strings'    => array(

					// Button text.
					'button_start'        => esc_html__( 'Start', 'buddyboss' ),
					'button_continue'     => esc_html__( 'Continue', 'buddyboss' ),

					// Start button clicked.
					'start_start'         => esc_html__( 'Starting Import...', 'buddyboss' ),
					'start_continue'      => esc_html__( 'Continuing Import...', 'buddyboss' ),

					// Import.
					'import_complete'     => esc_html__( 'Import Finished.', 'buddyboss' ),
					'import_stopped_user' => esc_html__( 'Import Stopped (by User.)', 'buddyboss' ),
					'import_error_halt'   => esc_html__( 'Import Halted (Error.)', 'buddyboss' ),
					'import_error_db'     => esc_html__( 'Database Connection Failed.', 'buddyboss' ),

					// Status.
					'status_complete'     => esc_html__( 'Finished', 'buddyboss' ),
					'status_stopped'      => esc_html__( 'Stopped', 'buddyboss' ),
					'status_starting'     => esc_html__( 'Starting', 'buddyboss' ),
					'status_up_next'      => esc_html__( 'Doing step %s...', 'buddyboss' ),
					'status_counting'     => esc_html__( 'Next in %s seconds...', 'buddyboss' ),
				),
			)
		);
	}

	/**
	 * Callback processor
	 *
	 * @since bbPress (r3813)
	 */
	public function process_callback() {

		// Ready the converter.
		$this->check_access();
		$this->maybe_set_memory();
		$this->maybe_restart();
		$this->setup_options();
		$this->maybe_update_options();

		// Bail if no converter.
		if ( ! empty( $this->converter ) ) {
			$this->do_steps();
		}
	}

	/**
	 * Wrap the converter output in HTML, so styling can be applied
	 *
	 * @since 2.1.0 bbPress (r4052)
	 *
	 * @param string $output
	 */
	private function converter_response( $output = '' ) {

		// Sanitize output.
		$output = wp_kses_data( $output );

		// Maybe prepend the step.
		if ( ! empty( $this->step ) ) {

			// Include percentage.
			if ( ! empty( $this->rows_in_step ) ) {
				$progress = sprintf( '<span class="step">%s.</span><span class="output">%s</span><span class="mini-step">%s</span>', $this->step, $output, $this->step_percentage . '%' );

				// Don't include percentage.
			} else {
				$progress = sprintf( '<span class="step">%s.</span><span class="output">%s</span>', $this->step, $output );
			}

			// Raw text.
		} else {
			$progress = $output;
		}

		// Output.
		wp_send_json_success(
			array(
				'query'         => get_option( '_bbp_converter_query', '' ),
				'current_step'  => $this->step,
				'final_step'    => $this->max_steps,
				'rows_in_step'  => $this->rows_in_step,
				'step_percent'  => $this->step_percentage,
				'total_percent' => $this->total_percentage,
				'progress'      => $progress,
			)
		);
	}

	/**
	 * Attempt to increase memory and set other system settings
	 *
	 * @since 2.6.0 bbPress (r6460)
	 */
	private function maybe_set_memory() {

		// Filter args.
		$r = apply_filters(
			'bbp_converter_php_ini_overrides',
			array(
				'implicit_flush'     => '1',
				'memory_limit'       => '256M',
				'max_execution_time' => HOUR_IN_SECONDS * 6,
			)
		);

		// Get disabled PHP functions (to avoid using them).
		$disabled = explode( ',', @ini_get( 'disable_functions' ) );

		// Maybe avoid terminating when the client goes away (if function is not disabled).
		if ( ! in_array( 'ignore_user_abort', $disabled, true ) ) {
			@ignore_user_abort( true );
		}

		// Maybe set memory & time limits, and flush style (if function is not disabled).
		if ( ! in_array( 'ini_set', $disabled, true ) ) {
			foreach ( $r as $key => $value ) {
				@ini_set( $key, $value );
			}
		}
	}

	/**
	 * Maybe restart the converter
	 *
	 * @since 2.6.0 bbPress (r6460)
	 */
	private function maybe_restart() {

		// Save step and count so that it can be restarted.
		if ( ! get_option( '_bbp_converter_step' ) || ! empty( $_POST['_bbp_converter_restart'] ) ) {
			$this->step             = 1;
			$this->start            = 0;
			$this->step_percentage  = 0;
			$this->total_percentage = 0;
			$this->rows_in_step     = 0;
			$this->maybe_update_options();
		}
	}

	/**
	 * Maybe update options
	 *
	 * @since 2.6.0 bbPress (r6637)
	 */
	private function maybe_update_options() {

		// Default options
		$options = array(

			// Step & Start.
			'_bbp_converter_step'          => $this->step,
			'_bbp_converter_start'         => $this->start,
			'_bbp_converter_rows_in_step'  => $this->rows_in_step,

			// Halt.
			'_bbp_converter_halt'          => ! empty( $_POST['_bbp_converter_halt'] )
				? (int) $_POST['_bbp_converter_halt']
				: 0,

			// Rows (bound between 1 and 5000).
			'_bbp_converter_rows'          => ! empty( $_POST['_bbp_converter_rows'] )
				? min( max( (int) $_POST['_bbp_converter_rows'], 1 ), 5000 )
				: 0,

			// Platform.
			'_bbp_converter_platform'      => ! empty( $_POST['_bbp_converter_platform'] )
				? sanitize_text_field( $_POST['_bbp_converter_platform'] )
				: '',

			// Convert Users.
			'_bbp_converter_convert_users' => ! empty( $_POST['_bbp_converter_convert_users'] ) ? (bool) $_POST['_bbp_converter_convert_users'] : false,

			// DB User.
			'_bbp_converter_db_user'       => ! empty( $_POST['_bbp_converter_db_user'] )
				? sanitize_text_field( $_POST['_bbp_converter_db_user'] )
				: '',

			// DB Password.
			'_bbp_converter_db_pass'       => ! empty( $_POST['_bbp_converter_db_pass'] )
				? sanitize_text_field( $_POST['_bbp_converter_db_pass'] )
				: '',

			// DB Name.
			'_bbp_converter_db_name'       => ! empty( $_POST['_bbp_converter_db_name'] )
				? sanitize_text_field( $_POST['_bbp_converter_db_name'] )
				: '',

			// DB Server.
			'_bbp_converter_db_server'     => ! empty( $_POST['_bbp_converter_db_server'] )
				? sanitize_text_field( $_POST['_bbp_converter_db_server'] )
				: '',

			// DB Port.
			'_bbp_converter_db_port'       => ! empty( $_POST['_bbp_converter_db_port'] )
				? (int) sanitize_text_field( $_POST['_bbp_converter_db_port'] )
				: '',

			// DB Table Prefix.
			'_bbp_converter_db_prefix'     => ! empty( $_POST['_bbp_converter_db_prefix'] )
				? sanitize_text_field( $_POST['_bbp_converter_db_prefix'] )
				: '',
		);

		// Update/delete options
		foreach ( $options as $key => $value ) {
			$new_val = update_option( $key, $value );
		}
	}

	/**
	 * Setup converter options
	 *
	 * @since 2.6.0 bbPress (r6460)
	 */
	private function setup_options() {

		// Set starting point & rows.
		$this->step         = (int) get_option( '_bbp_converter_step', 1 );
		$this->start        = (int) get_option( '_bbp_converter_start', 0 );
		$this->rows         = (int) get_option( '_bbp_converter_rows', 100 );
		$this->rows_in_step = (int) get_option( '_bbp_converter_rows_in_step', 0 );

		// Set boundaries.
		$this->max = ( $this->start + $this->rows ) - 1;

		// Set platform.
		$this->platform = get_option( '_bbp_converter_platform' );

		// Total percentage.
		$this->total_percentage = round( ( $this->step / $this->max_steps ) * 100, 2 );

		// Total mini steps.
		if ( $this->rows_in_step > 0 ) {
			$total_mini_steps      = ceil( $this->rows_in_step / $this->rows );
			$current_mini_step     = ceil( $this->start / $this->rows );
			$this->step_percentage = round( ( $current_mini_step / $total_mini_steps ) * 100, 2 );
		} else {
			$this->step_percentage = 0;
		}

		// Maybe include the appropriate converter.
		if ( ! empty( $this->platform ) ) {
			$this->converter = bbp_new_converter( $this->platform );
		}
	}

	/**
	 * Check that user can access the converter
	 *
	 * @since 2.6.0 bbPress (r6460)
	 */
	private function check_access() {

		// Bail if user cannot view import page.
		if ( ! current_user_can( 'bbp_tools_import_page' ) ) {
			wp_die( '0' );
		}

		// Verify intent.
		check_ajax_referer( 'bbp_converter_process' );
	}

	/**
	 * Reset the converter
	 *
	 * @since 2.6.0 bbPress (r6460)
	 */
	private function reset() {
		update_option( '_bbp_converter_step', 0 );
		update_option( '_bbp_converter_start', 0 );
		update_option( '_bbp_converter_rows_in_step', 0 );
		update_option( '_bbp_converter_query', '' );
	}

	/**
	 * Bump the step and reset the start
	 *
	 * @since 2.6.0 bbPress (r6460)
	 */
	private function bump_step() {

		// Next step.
		$next_step = (int) ( $this->step + 1 );

		// Don't let step go over max.
		$step = ( $next_step <= $this->max_steps )
			? $next_step
			: 0;

		// Update step and start at 0.
		update_option( '_bbp_converter_step', $step );
		update_option( '_bbp_converter_start', 0 );
		update_option( '_bbp_converter_rows_in_step', 0 );
	}

	/**
	 * Bump the start within the current step
	 *
	 * @since 2.6.0 bbPress (r6460)
	 */
	private function bump_start() {

		// Set rows in step from option.
		$this->rows_in_step = get_option( '_bbp_converter_rows_in_step', 0 );

		// Get rows to start from.
		$start = (int) ( $this->start + $this->rows );

		// Enforce maximum if exists.
		if ( $this->rows_in_step > 0 ) {

			// Start cannot be larger than total rows.
			if ( $start > $this->rows_in_step ) {
				$start = $this->rows_in_step;
			}

			// Max can't be greater than total rows.
			if ( $this->max > $this->rows_in_step ) {
				$this->max = $this->rows_in_step;
			}
		}

		// Update the start option.
		update_option( '_bbp_converter_start', $start );
	}

	/**
	 * Do the converter step
	 *
	 * @since 2.6.0 bbPress (r6460)
	 */
	private function do_steps() {

		// Step exists in map, and method exists.
		if ( isset( $this->steps[ $this->step ] ) && method_exists( $this, "step_{$this->steps[ $this->step ]}" ) ) {
			return call_user_func( array( $this, "step_{$this->steps[ $this->step ]}" ) );
		}

		// Done!
		$this->step_done();
	}

	/** Steps *****************************************************************/

	/**
	 * Maybe clean the sync table
	 *
	 * @since 2.6.0 bbPress (r6513)
	 */
	private function step_sync_table() {
		if ( true === $this->converter->clean ) {
			if ( $this->converter->clean() ) {

				$this->bump_step();
				$this->sync_table( true );

				empty( $this->start )
					? $this->converter_response( esc_html__( 'Readying sync-table', 'buddyboss' ) )
					: $this->converter_response( esc_html__( 'Sync-table ready', 'buddyboss' ) );
			} else {
				$this->bump_start();
				$this->converter_response( sprintf( esc_html__( 'Deleting previously converted data (%1$s through %2$s)', 'buddyboss' ), $this->start, $this->max ) );
			}

			$this->converter->clean = false;
		} else {
			$this->bump_step();
			$this->sync_table( false );
			$this->converter_response( esc_html__( 'Skipping sync-table clean-up', 'buddyboss' ) );
		}
	}

	/**
	 * Maybe convert users
	 *
	 * @since 2.6.0 bbPress (r6513)
	 */
	private function step_users() {
		if ( true === $this->converter->convert_users ) {
			if ( $this->converter->convert_users( $this->start ) ) {
				$this->bump_step();

				empty( $this->start )
					? $this->converter_response( esc_html__( 'No users to import', 'buddyboss' ) )
					: $this->converter_response( esc_html__( 'All users imported', 'buddyboss' ) );
			} else {
				$this->bump_start();
				$this->converter_response( sprintf( esc_html__( 'Converting users (%1$s through %2$s of %3$s)', 'buddyboss' ), $this->start, $this->max, $this->rows_in_step ) );
			}
		} else {
			$this->bump_step();
			$this->converter_response( esc_html__( 'Skipping user clean-up', 'buddyboss' ) );
		}
	}

	/**
	 * Maybe clean up passwords
	 *
	 * @since 2.6.0 bbPress (r6513)
	 */
	private function step_passwords() {
		if ( true === $this->converter->convert_users ) {
			if ( $this->converter->clean_passwords( $this->start ) ) {
				$this->bump_step();

				empty( $this->start )
					? $this->converter_response( esc_html__( 'No passwords to clear', 'buddyboss' ) )
					: $this->converter_response( esc_html__( 'All passwords cleared', 'buddyboss' ) );
			} else {
				$this->bump_start();
				$this->converter_response( sprintf( esc_html__( 'Delete default WordPress user passwords (%1$s through %2$s)', 'buddyboss' ), $this->start, $this->max ) );
			}
		} else {
			$this->bump_step();
			$this->converter_response( esc_html__( 'Skipping password clean-up', 'buddyboss' ) );
		}
	}

	/**
	 * Maybe convert forums
	 *
	 * @since 2.6.0 bbPress (r6513)
	 */
	private function step_forums() {
		if ( $this->converter->convert_forums( $this->start ) ) {
			$this->bump_step();

			empty( $this->start )
				? $this->converter_response( esc_html__( 'No forums to import', 'buddyboss' ) )
				: $this->converter_response( esc_html__( 'All forums imported', 'buddyboss' ) );
		} else {
			$this->bump_start();
			$this->converter_response( sprintf( esc_html__( 'Converting forums (%1$s through %2$s of %3$s)', 'buddyboss' ), $this->start, $this->max, $this->rows_in_step ) );
		}
	}

	/**
	 * Maybe walk the forum hierarchy
	 *
	 * @since 2.6.0 bbPress (r6513)
	 */
	private function step_forum_hierarchy() {
		if ( $this->converter->convert_forum_parents( $this->start ) ) {
			$this->bump_step();

			empty( $this->start )
				? $this->converter_response( esc_html__( 'No forum parents to import', 'buddyboss' ) )
				: $this->converter_response( esc_html__( 'All forum parents imported', 'buddyboss' ) );
		} else {
			$this->bump_start();
			$this->converter_response( sprintf( esc_html__( 'Calculating forum hierarchy (%1$s through %2$s of %3$s)', 'buddyboss' ), $this->start, $this->max, $this->rows_in_step ) );
		}
	}

	/**
	 * Maybe convert forum subscriptions
	 *
	 * @since 2.6.0 bbPress (r6513)
	 */
	private function step_forum_subscriptions() {
		if ( $this->converter->convert_forum_subscriptions( $this->start ) ) {
			$this->bump_step();

			empty( $this->start )
				? $this->converter_response( esc_html__( 'No forum subscriptions to import', 'buddyboss' ) )
				: $this->converter_response( esc_html__( 'All forum subscriptions imported', 'buddyboss' ) );
		} else {
			$this->bump_start();
			$this->converter_response( sprintf( esc_html__( 'Converting forum subscriptions (%1$s through %2$s of %3$s)', 'buddyboss' ), $this->start, $this->max, $this->rows_in_step ) );
		}
	}

	/**
	 * Maybe convert topics
	 *
	 * @since 2.6.0 bbPress (r6513)
	 */
	private function step_topics() {
		if ( $this->converter->convert_topics( $this->start ) ) {

			$this->bump_step();

			empty( $this->start )
				? $this->converter_response( esc_html__( 'No topics to import', 'buddyboss' ) )
				: $this->converter_response( esc_html__( 'All topics imported', 'buddyboss' ) );
		} else {
			$this->bump_start();
			$this->converter_response( sprintf( esc_html__( 'Converting topics (%1$s through %2$s of %3$s)', 'buddyboss' ), $this->start, $this->max, $this->rows_in_step ) );
		}
	}

	/**
	 * Maybe convert topic authors (anonymous)
	 *
	 * @since 2.6.0 bbPress (r6513)
	 */
	private function step_topics_authors() {
		if ( $this->converter->convert_anonymous_topic_authors( $this->start ) ) {
			$this->bump_step();

			empty( $this->start )
				? $this->converter_response( esc_html__( 'No anonymous topic authors to import', 'buddyboss' ) )
				: $this->converter_response( esc_html__( 'All anonymous topic authors imported', 'buddyboss' ) );
		} else {
			$this->bump_start();
			$this->converter_response( sprintf( esc_html__( 'Converting anonymous topic authors (%1$s through %2$s of %3$s)', 'buddyboss' ), $this->start, $this->max, $this->rows_in_step ) );
		}
	}

	/**
	 * Maybe convert sticky topics (not super stickies)
	 *
	 * @since 2.6.0 bbPress (r6513)
	 */
	private function step_stickies() {
		if ( $this->converter->convert_topic_stickies( $this->start ) ) {
			$this->bump_step();

			empty( $this->start )
				? $this->converter_response( esc_html__( 'No stickies to import', 'buddyboss' ) )
				: $this->converter_response( esc_html__( 'All stickies imported', 'buddyboss' ) );
		} else {
			$this->bump_start();
			$this->converter_response( sprintf( esc_html__( 'Calculating topic stickies (%1$s through %2$s of %3$s)', 'buddyboss' ), $this->start, $this->max, $this->rows_in_step ) );
		}
	}

	/**
	 * Maybe convert super-sticky topics (not per-forum)
	 *
	 * @since 2.6.0 bbPress (r6513)
	 */
	private function step_super_stickies() {
		if ( $this->converter->convert_topic_super_stickies( $this->start ) ) {
			$this->bump_step();

			empty( $this->start )
				? $this->converter_response( esc_html__( 'No super stickies to import', 'buddyboss' ) )
				: $this->converter_response( esc_html__( 'All super stickies imported', 'buddyboss' ) );
		} else {
			$this->bump_start();
			$this->converter_response( sprintf( esc_html__( 'Calculating topic super stickies (%1$s through %2$s of %3$s)', 'buddyboss' ), $this->start, $this->max, $this->rows_in_step ) );
		}
	}

	/**
	 * Maybe close converted topics
	 *
	 * @since 2.6.0 bbPress (r6513)
	 */
	private function step_closed_topics() {
		if ( $this->converter->convert_topic_closed_topics( $this->start ) ) {
			$this->bump_step();

			empty( $this->start )
				? $this->converter_response( esc_html__( 'No closed topics to import', 'buddyboss' ) )
				: $this->converter_response( esc_html__( 'All closed topics imported', 'buddyboss' ) );
		} else {
			$this->bump_start();
			$this->converter_response( sprintf( esc_html__( 'Calculating closed topics (%1$s through %2$s of %3$s)', 'buddyboss' ), $this->start, $this->max, $this->rows_in_step ) );
		}
	}

	/**
	 * Maybe convert topic tags
	 *
	 * @since 2.6.0 bbPress (r6513)
	 */
	private function step_topic_tags() {
		if ( $this->converter->convert_tags( $this->start ) ) {
			$this->bump_step();

			empty( $this->start )
				? $this->converter_response( esc_html__( 'No topic tags to import', 'buddyboss' ) )
				: $this->converter_response( esc_html__( 'All topic tags imported', 'buddyboss' ) );
		} else {
			$this->bump_start();
			$this->converter_response( sprintf( esc_html__( 'Converting topic tags (%1$s through %2$s of %3$s)', 'buddyboss' ), $this->start, $this->max, $this->rows_in_step ) );
		}
	}

	/**
	 * Maybe convert topic subscriptions
	 *
	 * @since 2.6.0 bbPress (r6513)
	 */
	private function step_topic_subscriptions() {
		if ( $this->converter->convert_topic_subscriptions( $this->start ) ) {
			$this->bump_step();

			empty( $this->start )
				? $this->converter_response( esc_html__( 'No topic subscriptions to import', 'buddyboss' ) )
				: $this->converter_response( esc_html__( 'All topic subscriptions imported', 'buddyboss' ) );
		} else {
			$this->bump_start();
			$this->converter_response( sprintf( esc_html__( 'Converting topic subscriptions (%1$s through %2$s of %3$s)', 'buddyboss' ), $this->start, $this->max, $this->rows_in_step ) );
		}
	}

	/**
	 * Maybe convert topic favorites
	 *
	 * @since 2.6.0 bbPress (r6513)
	 */
	private function step_topic_favorites() {
		if ( $this->converter->convert_favorites( $this->start ) ) {
			$this->bump_step();

			empty( $this->start )
				? $this->converter_response( esc_html__( 'No favorites to import', 'buddyboss' ) )
				: $this->converter_response( esc_html__( 'All favorites imported', 'buddyboss' ) );
		} else {
			$this->bump_start();
			$this->converter_response( sprintf( esc_html__( 'Converting favorites (%1$s through %2$s of %3$s)', 'buddyboss' ), $this->start, $this->max, $this->rows_in_step ) );
		}
	}

	/**
	 * Maybe convert replies
	 *
	 * @since 2.6.0 bbPress (r6513)
	 */
	private function step_replies() {
		if ( $this->converter->convert_replies( $this->start ) ) {
			$this->bump_step();

			empty( $this->start )
				? $this->converter_response( esc_html__( 'No replies to import', 'buddyboss' ) )
				: $this->converter_response( esc_html__( 'All replies imported', 'buddyboss' ) );
		} else {
			$this->bump_start();
			$this->converter_response( sprintf( esc_html__( 'Converting replies (%1$s through %2$s of %3$s)', 'buddyboss' ), $this->start, $this->max, $this->rows_in_step ) );
		}
	}

	/**
	 * Maybe convert reply authors (anonymous)
	 *
	 * @since 2.6.0 bbPress (r6513)
	 */
	private function step_reply_authors() {
		if ( $this->converter->convert_anonymous_reply_authors( $this->start ) ) {
			$this->bump_step();

			empty( $this->start )
				? $this->converter_response( esc_html__( 'No anonymous reply authors to import', 'buddyboss' ) )
				: $this->converter_response( esc_html__( 'All anonymous reply authors imported', 'buddyboss' ) );
		} else {
			$this->bump_start();
			$this->converter_response( sprintf( esc_html__( 'Converting anonymous reply authors (%1$s through %2$s of %3$s)', 'buddyboss' ), $this->start, $this->max, $this->rows_in_step ) );
		}
	}

	/**
	 * Maybe convert the threaded reply hierarchy
	 *
	 * @since 2.6.0 bbPress (r6513)
	 */
	private function step_reply_hierarchy() {
		if ( $this->converter->convert_reply_to_parents( $this->start ) ) {
			$this->bump_step();

			empty( $this->start )
				? $this->converter_response( esc_html__( 'No threaded replies to import', 'buddyboss' ) )
				: $this->converter_response( esc_html__( 'All threaded replies imported', 'buddyboss' ) );
		} else {
			$this->bump_start();
			$this->converter_response( sprintf( esc_html__( 'Calculating threaded replies parents (%1$s through %2$s of %3$s)', 'buddyboss' ), $this->start, $this->max, $this->rows_in_step ) );
		}
	}

	/**
	 * Done!
	 *
	 * @since 2.6.0 bbPress (r6513)
	 */
	private function step_done() {
		$this->reset();
		$this->converter_response( esc_html__( 'Import Finished', 'buddyboss' ) );
	}

	/** Helper Table **********************************************************/

	/**
	 * Create Tables for fast syncing
	 *
	 * @since 2.1.0 bbPress (r3813)
	 */
	public static function sync_table( $drop = false ) {

		// Setup DB.
		$bbp_db       = bbp_db();
		$table_name   = $bbp_db->prefix . 'bbp_converter_translator';
		$table_exists = $bbp_db->get_var( "SHOW TABLES LIKE '{$table_name}'" ) === $table_name;

		// Maybe drop the sync table.
		if ( ( true === $drop ) && ( true === $table_exists ) ) {
			$bbp_db->query( "DROP TABLE {$table_name}" );
		}

		// Maybe include the upgrade functions, for dbDelta().
		if ( ! function_exists( 'dbDelta' ) ) {
			require_once ABSPATH . '/wp-admin/includes/upgrade.php';
		}

		// Defaults.
		$sql             = array();
		$charset_collate = '';

		// https://bbpress.trac.wordpress.org/ticket/3145
		$max_index_length = 75;

		// Maybe override the character set.
		if ( ! empty( $bbp_db->charset ) ) {
			$charset_collate .= "DEFAULT CHARACTER SET {$bbp_db->charset}";
		}

		// Maybe override the collation.
		if ( ! empty( $bbp_db->collate ) ) {
			$charset_collate .= " COLLATE {$bbp_db->collate}";
		}

		/** Translator */

		$sql[] = "CREATE TABLE {$table_name} (
					meta_id mediumint(8) unsigned not null auto_increment,
					value_type varchar(25) null,
					value_id bigint(20) unsigned not null default '0',
					meta_key varchar({$max_index_length}) null,
					meta_value varchar({$max_index_length}) null,
				PRIMARY KEY (meta_id),
					KEY value_id (value_id),
					KEY meta_join (meta_key({$max_index_length}), meta_value({$max_index_length}))
				) {$charset_collate}";

		dbDelta( $sql );
	}
}

/**
 * Base class to be extended by specific individual importers
 *
 * @since bbPress (r3813)
 */
abstract class BBP_Converter_Base {

	/**
	 * @var array() This is the field mapping array to process.
	 */
	protected $field_map = array();

	/**
	 * @var object This is the connection to the WordPress database.
	 */
	protected $wpdb;

	/**
	 * @var object This is the connection to the other platforms database.
	 */
	protected $opdb;

	/**
	 * @var int Maximum number of rows to convert at 1 time. Default 100.
	 */
	protected $max_rows = 100;

	/**
	 * @var array() Map of topic to forum.  It is for optimization.
	 */
	protected $map_topicid_to_forumid = array();

	/**
	 * @var array() Map of from old forum ids to new forum ids.  It is for optimization.
	 */
	protected $map_forumid = array();

	/**
	 * @var array() Map of from old topic ids to new topic ids.  It is for optimization.
	 */
	protected $map_topicid = array();

	/**
	 * @var array() Map of from old reply_to ids to new reply_to ids.  It is for optimization.
	 */
	protected $map_reply_to = array();

	/**
	 * @var array() Map of from old user ids to new user ids.  It is for optimization.
	 */
	protected $map_userid = array();

	/**
	 * @var string This is the charset for your wp database.
	 */
	public $charset = '';

	/**
	 * @var boolean Sync table available.
	 */
	public $sync_table = false;

	/**
	 * @var string Sync table name.
	 */
	public $sync_table_name = '';

	/**
	 * @var bool Whether users should be converted or not. Default false.
	 */
	public $convert_users = false;

	/**
	 * @var bool Whether to clean up any old converter data. Default false.
	 */
	public $clean = false;

	/**
	 * @var array Custom BBCode class properties in a key => value format
	 */
	public $bbcode_parser_properties = array();

	/** Methods ***************************************************************/

	/**
	 * This is the constructor and it connects to the platform databases.
	 */
	public function __construct() {
		$this->init();
		$this->setup_globals();
	}

	/**
	 * Initialize the converter
	 *
	 * @since 2.1.0
	 */
	private function init() {

		/** BBCode Parse Properties */

		// Setup smiley URL & path.
		$this->bbcode_parser_properties = array(
			'smiley_url' => includes_url( 'images/smilies' ),
			'smiley_dir' => '/' . WPINC . '/images/smilies',
		);

		/** Sanitize Options */

		$this->clean         = ! empty( $_POST['_bbp_converter_clean'] );
		$this->convert_users = (bool) get_option( '_bbp_converter_convert_users', false );
		$this->halt          = (bool) get_option( '_bbp_converter_halt', 0 );
		$this->max_rows      = (int) get_option( '_bbp_converter_rows', 100 );

		/** Sanitize Connection */

		$db_user   = get_option( '_bbp_converter_db_user', DB_USER );
		$db_pass   = get_option( '_bbp_converter_db_pass', DB_PASSWORD );
		$db_name   = get_option( '_bbp_converter_db_name', DB_NAME );
		$db_host   = get_option( '_bbp_converter_db_server', DB_HOST );
		$db_port   = get_option( '_bbp_converter_db_port', '' );
		$db_prefix = get_option( '_bbp_converter_db_prefix', '' );

		// Maybe add port to server.
		if ( ! empty( $db_port ) && ! empty( $db_host ) && ! str_contains( $db_host, ':' ) ) {
			$db_host = "{$db_host}:{$db_port}";
		}

		/** Get database connections */

		// Setup WordPress Database.
		$this->wpdb = bbp_db();

		// Setup old forum Database.
		$this->opdb = new wpdb( $db_user, $db_pass, $db_name, $db_host );

		// Connection failed.
		if ( ! $this->opdb->db_connect( false ) ) {
			$error = new WP_Error( 'bbp_converter_db_connection_failed', esc_html__( 'Database connection failed.', 'buddyboss' ) );
			wp_send_json_error( $error );
		}

		// Maybe setup the database prefix.
		$this->opdb->prefix = $db_prefix;

		/**
		 * Don't wp_die() uncontrollably
		 */
		$this->wpdb->show_errors( false );
		$this->opdb->show_errors( false );

		/**
		 * Syncing
		 */
		$this->sync_table_name = $this->wpdb->prefix . 'bbp_converter_translator';
		$this->sync_table      = $this->sync_table_name === $this->wpdb->get_var( "SHOW TABLES LIKE '{$this->sync_table_name}'" );

		/**
		 * Character set
		 */
		$this->charset = ! empty( $this->wpdb->charset )
			? $this->wpdb->charset
			: 'UTF8';

		/**
		 * Default mapping.
		 */

		/** Forum Section */

		$this->field_map[] = array(
			'to_type'      => 'forum',
			'to_fieldname' => 'post_status',
			'default'      => 'publish',
		);
		$this->field_map[] = array(
			'to_type'      => 'forum',
			'to_fieldname' => 'comment_status',
			'default'      => 'closed',
		);
		$this->field_map[] = array(
			'to_type'      => 'forum',
			'to_fieldname' => 'ping_status',
			'default'      => 'closed',
		);
		$this->field_map[] = array(
			'to_type'      => 'forum',
			'to_fieldname' => 'post_type',
			'default'      => 'forum',
		);

		/** Topic Section */

		$this->field_map[] = array(
			'to_type'      => 'topic',
			'to_fieldname' => 'post_status',
			'default'      => 'publish',
		);
		$this->field_map[] = array(
			'to_type'      => 'topic',
			'to_fieldname' => 'comment_status',
			'default'      => 'closed',
		);
		$this->field_map[] = array(
			'to_type'      => 'topic',
			'to_fieldname' => 'ping_status',
			'default'      => 'closed',
		);
		$this->field_map[] = array(
			'to_type'      => 'topic',
			'to_fieldname' => 'post_type',
			'default'      => 'topic',
		);

		/** Post Section */

		$this->field_map[] = array(
			'to_type'      => 'reply',
			'to_fieldname' => 'post_status',
			'default'      => 'publish',
		);
		$this->field_map[] = array(
			'to_type'      => 'reply',
			'to_fieldname' => 'comment_status',
			'default'      => 'closed',
		);
		$this->field_map[] = array(
			'to_type'      => 'reply',
			'to_fieldname' => 'ping_status',
			'default'      => 'closed',
		);
		$this->field_map[] = array(
			'to_type'      => 'reply',
			'to_fieldname' => 'post_type',
			'default'      => 'reply',
		);

		/** User Section */

		$this->field_map[] = array(
			'to_type'      => 'user',
			'to_fieldname' => 'role',
			'default'      => get_option( 'default_role' ),
		);
	}

	/**
	 * Setup global values
	 */
	public function setup_globals() {}

	/**
	 * Convert Forums
	 */
	public function convert_forums( $start = 1 ) {
		return $this->convert_table( 'forum', $start );
	}

	/**
	 * Convert Topics / Threads
	 */
	public function convert_topics( $start = 1 ) {
		return $this->convert_table( 'topic', $start );
	}

	/**
	 * Convert Posts
	 */
	public function convert_replies( $start = 1 ) {
		return $this->convert_table( 'reply', $start );
	}

	/**
	 * Convert Users
	 */
	public function convert_users( $start = 1 ) {
		return $this->convert_table( 'user', $start );
	}

	/**
	 * Convert Topic Tags
	 */
	public function convert_tags( $start = 1 ) {
		return $this->convert_table( 'tags', $start );
	}

	/**
	 * Convert Forum Subscriptions
	 */
	public function convert_forum_subscriptions( $start = 1 ) {
		return $this->convert_table( 'forum_subscriptions', $start );
	}

	/**
	 * Convert Topic Subscriptions
	 */
	public function convert_topic_subscriptions( $start = 1 ) {
		return $this->convert_table( 'topic_subscriptions', $start );
	}

	/**
	 * Convert Favorites
	 */
	public function convert_favorites( $start = 1 ) {
		return $this->convert_table( 'favorites', $start );
	}

	/**
	 * Convert Table
	 *
	 * @param string to type
	 * @param int Start row
	 */
	public function convert_table( $to_type, $start ) {

		// Set some defaults.
		$has_insert     = false;
		$from_tablename = '';
		$field_list     = $from_tables = $tablefield_array = array();

		// Toggle Table Name based on $to_type (destination).
		switch ( $to_type ) {
			case 'user':
				$tablename = $this->wpdb->users;
				break;

			case 'tags':
				$tablename = '';
				break;

			case 'favorites':
			case 'topic_subscriptions':
			case 'forum_subscriptions':
				$tablename = $this->wpdb->postmeta;
				break;

			default:
				$tablename = $this->wpdb->posts;
		}

		// Get the fields from the destination table.
		if ( ! empty( $tablename ) ) {
			$tablefield_array = $this->get_fields( $tablename );
		}

		/** Step 1 */

		// Loop through the field maps, and look for to_type matches.
		foreach ( $this->field_map as $item ) {

			// Yay a match, and we have a from table, too.
			if ( ( $item['to_type'] === $to_type ) && ! empty( $item['from_tablename'] ) ) {

				// $from_tablename was set from a previous loop iteration.
				if ( ! empty( $from_tablename ) ) {

					// Doing some joining.
					if ( ! in_array( $item['from_tablename'], $from_tables, true ) && in_array( $item['join_tablename'], $from_tables, true ) ) {
						$from_tablename .= ' ' . $item['join_type'] . ' JOIN ' . $this->opdb->prefix . $item['from_tablename'] . ' AS ' . $item['from_tablename'] . ' ' . $item['join_expression'];
					}

					// $from_tablename needs to be set.
				} else {
					$from_tablename = $item['from_tablename'] . ' AS ' . $item['from_tablename'];
				}

				// Specific FROM expression data used.
				if ( ! empty( $item['from_expression'] ) ) {

					// No 'WHERE' in expression.
					if ( stripos( $from_tablename, 'WHERE' ) === false ) {
						$from_tablename .= ' ' . $item['from_expression'];

						// 'WHERE' in expression, so replace with 'AND'.
					} else {
						$from_tablename .= ' ' . str_replace( 'WHERE', 'AND', $item['from_expression'] );
					}
				}

				// Add tablename and fieldname to arrays, formatted for querying.
				$from_tables[] = $item['from_tablename'];
				$field_list[]  = 'convert(' . $item['from_tablename'] . '.' . $item['from_fieldname'] . ' USING "' . $this->charset . '") AS ' . $item['from_fieldname'];
			}
		}

		/** Step 2 */

		// We have a $from_tablename, so we want to get some data to convert.
		if ( ! empty( $from_tablename ) ) {

			// Update rows.
			$this->count_rows_by_table( "{$this->opdb->prefix}{$from_tablename}" );

			// Get some data from the old forums.
			$field_list  = array_unique( $field_list );
			$fields      = implode( ',', $field_list );
			$forum_query = "SELECT {$fields} FROM {$this->opdb->prefix}{$from_tablename} LIMIT {$start}, {$this->max_rows}";

			// Set this query as the last one ran.
			$this->update_query( $forum_query );

			// Get results as an array
			$forum_array = $this->opdb->get_results( $forum_query, ARRAY_A );

			// Query returned some results.
			if ( ! empty( $forum_array ) ) {

				// Loop through results.
				foreach ( (array) $forum_array as $forum ) {

					// Reset some defaults.
					$insert_post = $insert_postmeta = $insert_data = array();

					// Loop through field map, again...
					foreach ( $this->field_map as $row ) {

						// Types match and to_fieldname is present. This means
						// we have some work to do here.
						if ( ( $row['to_type'] === $to_type ) && isset( $row['to_fieldname'] ) ) {

							// This row has a destination that matches one of the
							// columns in this table.
							if ( in_array( $row['to_fieldname'], $tablefield_array, true ) ) {

								// Allows us to set default fields.
								if ( isset( $row['default'] ) ) {
									$insert_post[ $row['to_fieldname'] ] = $row['default'];

									// Translates a field from the old forum.
								} elseif ( isset( $row['callback_method'] ) ) {
									if ( ( 'callback_userid' === $row['callback_method'] ) && ( false === $this->convert_users ) ) {
										$insert_post[ $row['to_fieldname'] ] = $forum[ $row['from_fieldname'] ];
									} else {
										$insert_post[ $row['to_fieldname'] ] = call_user_func_array( array( $this, $row['callback_method'] ), array( $forum[ $row['from_fieldname'] ], $forum ) );
									}

									// Maps the field from the old forum.
								} else {
									$insert_post[ $row['to_fieldname'] ] = $forum[ $row['from_fieldname'] ];
								}

								// Destination field is not empty, so we might need
								// to do some extra work or set a default.
							} elseif ( ! empty( $row['to_fieldname'] ) ) {

								// Allows us to set default fields.
								if ( isset( $row['default'] ) ) {
									$insert_postmeta[ $row['to_fieldname'] ] = $row['default'];

									// Translates a field from the old forum.
								} elseif ( isset( $row['callback_method'] ) ) {
									if ( ( $row['callback_method'] === 'callback_userid' ) && ( false === $this->convert_users ) ) {
										$insert_postmeta[ $row['to_fieldname'] ] = $forum[ $row['from_fieldname'] ];
									} else {
										$insert_postmeta[ $row['to_fieldname'] ] = call_user_func_array( array( $this, $row['callback_method'] ), array( $forum[ $row['from_fieldname'] ], $forum ) );
									}

									// Maps the field from the old forum.
								} else {
									$insert_postmeta[ $row['to_fieldname'] ] = $forum[ $row['from_fieldname'] ];
								}
							}
						}
					}

					/** Step 3 */

					// Something to insert into the destination field.
					switch ( $to_type ) {

						/** New user */

						case 'user':
							if ( 0 === count( $insert_post ) ) {
								break;
							}

							if ( username_exists( $insert_post['user_login'] ) ) {
								$insert_post['user_login'] = "imported_{$insert_post['user_login']}";
							}

							if ( email_exists( $insert_post['user_email'] ) ) {
								$insert_post['user_email'] = "imported_{$insert_post['user_email']}";
							}

							if ( empty( $insert_post['user_pass'] ) ) {
								$insert_post['user_pass'] = '';
							}

							// Internally re-calls _exists() checks above.
							// Also checks for existing nicename.
							$post_id = wp_insert_user( $insert_post );

							if ( is_numeric( $post_id ) ) {
								foreach ( $insert_postmeta as $key => $value ) {
									add_user_meta( $post_id, $key, $value, true );

									if ( '_id' == substr( $key, -3 ) && ( true === $this->sync_table ) ) {
										$this->wpdb->insert(
											$this->sync_table_name,
											array(
												'value_type' => 'user',
												'value_id' => $post_id,
												'meta_key' => $key,
												'meta_value' => $value,
											)
										);
									}
								}
							}
							break;

						/** New Topic-Tag */

						case 'tags':
							if ( 0 === count( $insert_postmeta ) ) {
								break;
							}

							$post_id = wp_set_object_terms( $insert_postmeta['objectid'], $insert_postmeta['name'], 'topic-tag', true );
							$term    = get_term_by( 'name', $insert_postmeta['name'], 'topic-tag' );
							if ( false !== $term ) {
								wp_update_term(
									$term->term_id,
									'topic-tag',
									array(
										'description' => $insert_postmeta['description'],
										'slug'        => $insert_postmeta['slug'],
									)
								);
							}
							break;

						/** Forum Subscriptions */

						case 'forum_subscriptions':
							if ( 0 === count( $insert_postmeta ) ) {
								break;
							}

							$user_id = isset( $insert_postmeta['user_id'] ) ? $insert_postmeta['user_id'] : 0;
							$item_id = isset( $insert_postmeta['_bbp_forum_subscriptions'] ) ? $insert_postmeta['_bbp_forum_subscriptions'] : 0;

							if ( $user_id && $item_id ) {
								bbp_add_user_subscription( $user_id, $this->callback_forumid( $item_id ) );
							}
							break;

						/** Subscriptions */

						case 'topic_subscriptions':
							if ( 0 === count( $insert_postmeta ) ) {
								break;
							}

							$user_id = isset( $insert_postmeta['user_id'] ) ? $insert_postmeta['user_id'] : 0;
							$item_id = isset( $insert_postmeta['_bbp_subscriptions'] ) ? $insert_postmeta['_bbp_subscriptions'] : 0;

							if ( $user_id && $item_id ) {
								bbp_add_user_subscription( $user_id, $this->callback_topicid( $item_id ) );
							}
							break;

						/** Favorites */

						case 'favorites':
							if ( 0 === count( $insert_postmeta ) ) {
								break;
							}

							$user_id = isset( $insert_postmeta['user_id'] ) ? $insert_postmeta['user_id'] : 0;
							$item_id = isset( $insert_postmeta['_bbp_favorites'] ) ? $insert_postmeta['_bbp_favorites'] : 0;

							if ( $user_id && $item_id ) {
								bbp_add_user_favorite( $user_id, $this->callback_topicid( $item_id ) );
							}
							break;

						/** Forum, Topic, Reply */

						default:
							if ( 0 === count( $insert_post ) ) {
								break;
							}

							$post_id = wp_insert_post( $insert_post, true );

							if ( is_numeric( $post_id ) && count( $insert_postmeta ) > 0 ) {
								foreach ( $insert_postmeta as $key => $value ) {
									add_post_meta( $post_id, $key, $value, true );

									/**
									 * If we are using the sync_table add
									 * the meta '_id' keys to the table
									 *
									 * Forums:  _bbp_old_forum_id         // The old forum ID
									 *          _bbp_old_forum_parent_id  // The old forum parent ID
									 *
									 * Topics:  _bbp_forum_id             // The new forum ID
									 *          _bbp_old_topic_id         // The old topic ID
									 *          _bbp_old_closed_status_id // The old topic open/closed status
									 *          _bbp_old_sticky_status_id // The old topic sticky status
									 *
									 * Replies: _bbp_forum_id             // The new forum ID
									 *          _bbp_topic_id             // The new topic ID
									 *          _bbp_old_reply_id         // The old reply ID
									 *          _bbp_old_reply_to_id      // The old reply to ID
									 */
									if ( '_id' === substr( $key, -3 ) && ( true === $this->sync_table ) ) {
										$this->wpdb->insert(
											$this->sync_table_name,
											array(
												'value_type' => 'post',
												'value_id' => $post_id,
												'meta_key' => $key,
												'meta_value' => $value,
											)
										);
									}

									/**
									 * Replies need to save their old reply_to ID for
									 * hierarchical replies association. Later we update
									 * the _bbp_reply_to value with the new bbPress
									 * value using convert_reply_to_parents()
									 */
									if ( ( 'reply' === $to_type ) && ( '_bbp_old_reply_to_id' === $key ) ) {
										add_post_meta( $post_id, '_bbp_reply_to', $value );
									}
								}
							}
							break;
					}
					$has_insert = true;

				}
			}
		}

		return ! $has_insert;
	}

	/**
	 * This method converts old forum hierarchy to new bbPress hierarchy.
	 */
	public function convert_forum_parents( $start = 1 ) {
		$has_update = false;
		$query      = ! empty( $this->sync_table )
			? $this->wpdb->prepare( "SELECT value_id, meta_value FROM {$this->sync_table_name} WHERE meta_key = %s AND meta_value > 0", '_bbp_old_forum_parent_id' )
			: $this->wpdb->prepare( "SELECT post_id AS value_id, meta_value FROM {$this->wpdb->postmeta} WHERE meta_key = %s AND meta_value > 0", '_bbp_old_forum_parent_id' );

		// Set max row in '_bbp_converter_rows_in_step' variable.
		if ( 0 === $start ) {
			$this->count_rows_by_results( $query );
		}

		// Create main query.
		$query = $this->wpdb->prepare( $query . " LIMIT %d, %d", $start, $this->max_rows );

		foreach ( $this->get_results( $query ) as $row ) {
			$parent_id = $this->callback_forumid( $row->meta_value );
			$this->query( $this->wpdb->prepare( "UPDATE {$this->wpdb->posts} SET post_parent = %d WHERE ID = %d LIMIT 1", $parent_id, $row->value_id ) );
			$has_update = true;
		}

		return ! $has_update;
	}

	/**
	 * This method converts old topic stickies to new bbPress stickies.
	 *
	 * @since 2.5.0 bbPress (r5170)
	 */
	public function convert_topic_stickies( $start = 1 ) {
		$has_update = false;
		$query      = ! empty( $this->sync_table )
			? $this->wpdb->prepare( "SELECT value_id, meta_value FROM {$this->sync_table_name} WHERE meta_key = %s AND meta_value = %s", '_bbp_old_sticky_status_id', 'sticky' )
			: $this->wpdb->prepare( "SELECT post_id AS value_id, meta_value FROM {$this->wpdb->postmeta} WHERE meta_key = %s AND meta_value = %s", '_bbp_old_sticky_status_id', 'sticky' );

		// Set max row in '_bbp_converter_rows_in_step' variable.
		if ( 0 === $start ) {
			$this->count_rows_by_results( $query );
		}

		// Create main query.
		$query = $this->wpdb->prepare( $query . " LIMIT %d, %d", $start, $this->max_rows );

		foreach ( $this->get_results( $query ) as $row ) {
			bbp_stick_topic( $row->value_id );
			$has_update = true;
		}

		return ! $has_update;
	}

	/**
	 * This method converts old topic super stickies to new bbPress super stickies.
	 *
	 * @since 2.5.0 bbPress (r5170)
	 */
	public function convert_topic_super_stickies( $start = 1 ) {
		$has_update = false;
		$query      = ! empty( $this->sync_table )
			? $this->wpdb->prepare( "SELECT value_id, meta_value FROM {$this->sync_table_name} WHERE meta_key = %s AND meta_value = %s", '_bbp_old_sticky_status_id', 'super-sticky' )
			: $this->wpdb->prepare( "SELECT post_id AS value_id, meta_value FROM {$this->wpdb->postmeta} WHERE meta_key = %s AND meta_value = %s", '_bbp_old_sticky_status_id', 'super-sticky' );

		// Set max row in '_bbp_converter_rows_in_step' variable.
		if ( 0 === $start ) {
			$this->count_rows_by_results( $query );
		}

		// Create main query.
		$query = $this->wpdb->prepare( $query . " LIMIT %d, %d", $start, $this->max_rows );

		foreach ( $this->get_results( $query ) as $row ) {
			bbp_stick_topic( $row->value_id, true );
			$has_update = true;
		}

		return ! $has_update;
	}

	/**
	 * This method converts old closed topics to bbPress closed topics.
	 *
	 * @since 2.6.0 bbPress (r5425)
	 */
	public function convert_topic_closed_topics( $start = 1 ) {
		$has_update = false;
		$query      = ! empty( $this->sync_table )
			? $this->wpdb->prepare( "SELECT value_id, meta_value FROM {$this->sync_table_name} WHERE meta_key = %s AND meta_value = %s", '_bbp_old_closed_status_id', 'closed' )
			: $this->wpdb->prepare( "SELECT post_id AS value_id, meta_value FROM {$this->wpdb->postmeta} WHERE meta_key = %s AND meta_value = %s", '_bbp_old_closed_status_id', 'closed' );

		// Set max row in '_bbp_converter_rows_in_step' variable.
		if ( 0 === $start ) {
			$this->count_rows_by_results( $query );
		}

		// Create main query.
		$query = $this->wpdb->prepare( $query . " LIMIT %d, %d", $start, $this->max_rows );

		foreach ( $this->get_results( $query ) as $row ) {
			bbp_close_topic( $row->value_id );
			$has_update = true;
		}

		return ! $has_update;
	}

	/**
	 * This method converts old reply_to post id to new bbPress reply_to post id.
	 *
	 * @since 2.4.0 bbPress (r5093)
	 */
	public function convert_reply_to_parents( $start = 1 ) {
		$has_update = false;
		$query      = ! empty( $this->sync_table )
			? $this->wpdb->prepare( "SELECT value_id, meta_value FROM {$this->sync_table_name} WHERE meta_key = %s AND meta_value > 0", '_bbp_old_reply_to_id' )
			: $this->wpdb->prepare( "SELECT post_id AS value_id, meta_value FROM {$this->wpdb->postmeta} WHERE meta_key = %s AND meta_value > 0", '_bbp_old_reply_to_id' );

		// Set max row in '_bbp_converter_rows_in_step' variable.
		if ( 0 === $start ) {
			$this->count_rows_by_results( $query );
		}

		// Create main query.
		$query = $this->wpdb->prepare( $query . " LIMIT %d, %d", $start, $this->max_rows );

		foreach ( $this->get_results( $query ) as $row ) {
			$reply_to = $this->callback_reply_to( $row->meta_value );
			$this->query( $this->wpdb->prepare( "UPDATE {$this->wpdb->postmeta} SET meta_value = %s WHERE meta_key = %s AND post_id = %d LIMIT 1", $reply_to, '_bbp_reply_to', $row->value_id ) );
			$has_update = true;
		}

		return ! $has_update;
	}

	/**
	 * This method converts anonymous topics.
	 *
	 * @since 2.6.0 bbPress (r5538)
	 */
	public function convert_anonymous_topic_authors( $start = 1 ) {
		$has_update = false;

		if ( ! empty( $this->sync_table ) ) {
			$query = $this->wpdb->prepare(
				"SELECT sync_table1.value_id AS topic_id, sync_table1.meta_value AS topic_is_anonymous, sync_table2.meta_value AS topic_author
				FROM {$this->sync_table_name} AS sync_table1
				INNER JOIN {$this->sync_table_name} AS sync_table2
				ON ( sync_table1.value_id = sync_table2.value_id )
				WHERE sync_table1.meta_value = %s
				AND sync_table2.meta_key = %s",
				'true',
				'_bbp_old_topic_author_name_id'
			);
		} else {
			$query = $this->wpdb->prepare(
				"SELECT wp_postmeta1.post_id AS topic_id, wp_postmeta1.meta_value AS topic_is_anonymous, wp_postmeta2.meta_value AS topic_author
				FROM {$this->wpdb->postmeta} AS wp_postmeta1
				INNER JOIN {$this->wpdb->postmeta} AS wp_postmeta2
				ON ( wp_postmeta1.post_id = wp_postmeta2.post_id )
				WHERE wp_postmeta1.meta_value = %s
				AND wp_postmeta2.meta_key = %s",
				'true',
				'_bbp_old_topic_author_name_id'
			);
		}

		// Set max row in '_bbp_converter_rows_in_step' variable.
		if ( 0 === $start ) {
			$this->count_rows_by_results( $query );
		}

		// Create main query.
		$query = $this->wpdb->prepare( $query . " LIMIT %d, %d", $start, $this->max_rows );

		foreach ( $this->get_results( $query ) as $row ) {
			$anonymous_topic_author_id = 0;
			$this->query( $this->wpdb->prepare( "UPDATE {$this->wpdb->posts} SET post_author = %d WHERE ID = %d LIMIT 1", $anonymous_topic_author_id, $row->topic_id ) );

			add_post_meta( $row->topic_id, '_bbp_anonymous_name', $row->topic_author );

			$has_update = true;
		}

		return ! $has_update;
	}

	/**
	 * This method converts anonymous replies.
	 *
	 * @since 2.6.0 bbPress (r5538)
	 */
	public function convert_anonymous_reply_authors( $start = 1 ) {
		$has_update = false;

		if ( ! empty( $this->sync_table ) ) {
			$query = $this->wpdb->prepare(
				"SELECT sync_table1.value_id AS reply_id, sync_table1.meta_value AS reply_is_anonymous, sync_table2.meta_value AS reply_author
				FROM {$this->sync_table_name} AS sync_table1
				INNER JOIN {$this->sync_table_name} AS sync_table2
				ON ( sync_table1.value_id = sync_table2.value_id )
				WHERE sync_table1.meta_value = %s
				AND sync_table2.meta_key = %s",
				'true',
				'_bbp_old_reply_author_name_id'
			);
		} else {
			$query = $this->wpdb->prepare(
				"SELECT wp_postmeta1.post_id AS reply_id, wp_postmeta1.meta_value AS reply_is_anonymous, wp_postmeta2.meta_value AS reply_author
				FROM {$this->wpdb->postmeta} AS wp_postmeta1
				INNER JOIN {$this->wpdb->postmeta} AS wp_postmeta2
				ON ( wp_postmeta1.post_id = wp_postmeta2.post_id )
				WHERE wp_postmeta1.meta_value = %s
				AND wp_postmeta2.meta_key = %s",
				'true',
				'_bbp_old_reply_author_name_id'
			);
		}

		// Set max row in '_bbp_converter_rows_in_step' variable.
		if ( 0 === $start ) {
			$this->count_rows_by_results( $query );
		}

		// Create main query.
		$query = $this->wpdb->prepare( $query . " LIMIT %d, %d", $start, $this->max_rows );

		foreach ( $this->get_results( $query ) as $row ) {
			$anonymous_reply_author_id = 0;
			$this->query( $this->wpdb->prepare( "UPDATE {$this->wpdb->posts} SET post_author = %d WHERE ID = %d LIMIT 1", $anonymous_reply_author_id, $row->reply_id ) );

			add_post_meta( $row->reply_id, '_bbp_anonymous_name', $row->reply_author );

			$has_update = true;
		}

		return ! $has_update;
	}

	/**
	 * This method deletes data from the wp database.
	 *
	 * @since 2.6.0 bbPress (r6456)
	 */
	public function clean() {

		// Defaults
		$has_delete = false;

		/** Delete topics/forums/posts */

		$esc_like = $this->wpdb->esc_like( '_bbp_' ) . '%';
		$query    = ! empty( $this->sync_table )
			? $this->wpdb->prepare( "SELECT value_id FROM {$this->sync_table_name} INNER JOIN {$this->wpdb->posts} ON(value_id = ID) WHERE meta_key LIKE %s AND value_type = %s GROUP BY value_id ORDER BY value_id DESC LIMIT {$this->max_rows}", $esc_like, 'post' )
			: $this->wpdb->prepare( "SELECT post_id AS value_id FROM {$this->wpdb->postmeta} WHERE meta_key LIKE %s GROUP BY post_id ORDER BY post_id DESC LIMIT {$this->max_rows}", $esc_like );

		$posts = $this->get_results( $query, ARRAY_A );

		if ( isset( $posts[0] ) && ! empty( $posts[0]['value_id'] ) ) {
			foreach ( (array) $posts as $value ) {
				$deleted = wp_delete_post( $value['value_id'], true );

				// Only flag if not empty or error.
				if ( ( false === $has_delete ) && ! empty( $deleted ) && ! is_wp_error( $deleted ) ) {
					$has_delete = true;
				}
			}
		}

		/** Delete users */

		$query = ! empty( $this->sync_table )
			? $this->wpdb->prepare( "SELECT value_id FROM {$this->sync_table_name} INNER JOIN {$this->wpdb->users} ON(value_id = ID) WHERE meta_key = %s AND value_type = %s LIMIT {$this->max_rows}", '_bbp_old_user_id', 'user' )
			: $this->wpdb->prepare( "SELECT user_id AS value_id FROM {$this->wpdb->usermeta} WHERE meta_key = %s LIMIT {$this->max_rows}", '_bbp_old_user_id' );

		$users = $this->get_results( $query, ARRAY_A );

		if ( ! empty( $users ) ) {
			foreach ( $users as $value ) {
				$deleted = wp_delete_user( $value['value_id'] );

				// Only flag if not empty or error.
				if ( ( false === $has_delete ) && ! empty( $deleted ) && ! is_wp_error( $deleted ) ) {
					$has_delete = true;
				}
			}
		}

		unset( $posts );
		unset( $users );

		return ! $has_delete;
	}

	/**
	 * This method deletes passwords from the wp database.
	 *
	 * @param int Start row
	 */
	public function clean_passwords( $start = 1 ) {
		$has_delete = false;
		$query      = $this->wpdb->prepare( "SELECT user_id, meta_value FROM {$this->wpdb->usermeta} WHERE meta_key = %s LIMIT {$start}, {$this->max_rows}", '_bbp_password' );
		$converted  = $this->get_results( $query, ARRAY_A );

		if ( ! empty( $converted ) ) {
			foreach ( $converted as $value ) {
				if ( is_serialized( $value['meta_value'] ) ) {
					$this->query( $this->wpdb->prepare( "UPDATE {$this->wpdb->users} SET user_pass = '' WHERE ID = %d", $value['user_id'] ) );
				} else {
					$this->query( $this->wpdb->prepare( "UPDATE {$this->wpdb->users} SET user_pass = %s WHERE ID = %d", $value['meta_value'], $value['user_id'] ) );
					$this->query( $this->wpdb->prepare( "DELETE FROM {$this->wpdb->usermeta} WHERE meta_key = %s AND user_id = %d", '_bbp_password', $value['user_id'] ) );
				}
			}
			$has_delete = true;
		}

		return ! $has_delete;
	}

	/**
	 * This method implements the authentication for the different forums.
	 *
	 * @param string Un-encoded password.
	 */
	abstract protected function authenticate_pass( $password, $hash );

	/**
	 * Info
	 */
	abstract protected function info();

	/**
	 * This method grabs appropriate fields from the table specified
	 *
	 * @param string The table name to grab fields from
	 */
	private function get_fields( $tablename = '' ) {
		$retval      = array();
		$field_array = $this->get_results( "DESCRIBE {$tablename}", ARRAY_A );

		// Bail if no fields.
		if ( empty( $field_array ) ) {
			return $retval;
		}

		// Add fields to array.
		foreach ( $field_array as $field ) {
			if ( ! empty( $field['Field'] ) ) {
				$retval[] = $field['Field'];
			}
		}

		// Add social fields for users table.
		if ( $tablename === $this->wpdb->users ) {
			$retval[] = 'role';
			$retval[] = 'yim';
			$retval[] = 'aim';
			$retval[] = 'jabber';
		}

		return $retval;
	}

	/** Database Wrappers *****************************************************/

	/**
	 * Update the last query option and return results
	 *
	 * @param string $query
	 * @param string $output
	 */
	private function get_row( $query = '' ) {
		$this->update_query( $query );

		return $this->wpdb->get_row( $query );
	}

	/**
	 * Update the last query option and return results
	 *
	 * @param string $query
	 * @param string $output
	 */
	private function get_results( $query = '', $output = OBJECT ) {
		$this->update_query( $query );

		return (array) $this->wpdb->get_results( $query, $output );
	}

	/**
	 * Update the last query option and do a general query
	 *
	 * @param string $query
	 */
	private function query( $query = '' ) {
		$this->update_query( $query );

		return $this->wpdb->query( $query );
	}

	/**
	 * Update the last query ran
	 *
	 * @since 2.6.0 bbPress (r6637)
	 *
	 * @param string $query The literal MySQL query.
	 * @return bool
	 */
	private function update_query( $query = '' ) {
		return update_option( '_bbp_converter_query', $query );
	}

	/**
	 * Update the number of rows in the current step
	 *
	 * @since 2.6.0 bbPress (r6637)
	 *
	 * @param string $query The literal MySQL query.
	 */
	private function count_rows_by_results( $query = '' ) {
		$results = $this->get_results( $query );

		update_option( '_bbp_converter_rows_in_step', count( $results ) );
	}

	/**
	 * Update the number of rows in the current step
	 *
	 * @since 2.6.0 bbPress (r6637)
	 *
	 * @param string $table_name The literal MySQL query.
	 * @return bool
	 */
	private function count_rows_by_table( $table_name = '' ) {
		$count = (int) $this->opdb->get_var( "SELECT COUNT(*) FROM {$table_name}" );

		return update_option( '_bbp_converter_rows_in_step', $count );
	}

	/** Callbacks *************************************************************/

	/**
	 * Run password through wp_hash_password()
	 *
	 * @param string $username.
	 * @param string $password.
	 */
	public function callback_pass( $username, $password ) {
		$user = $this->get_row( $this->wpdb->prepare( "SELECT * FROM {$this->wpdb->users} WHERE user_login = %s AND user_pass = '' LIMIT 1", $username ) );
		if ( ! empty( $user ) ) {
			$usermeta = $this->get_row( $this->wpdb->prepare( "SELECT * FROM {$this->wpdb->usermeta} WHERE meta_key = %s AND user_id = %d LIMIT 1", '_bbp_password', $user->ID ) );

			if ( ! empty( $usermeta ) ) {
				if ( $this->authenticate_pass( $password, $usermeta->meta_value ) ) {
					$this->query( $this->wpdb->prepare( "UPDATE {$this->wpdb->users} SET user_pass = %s WHERE ID = %d", wp_hash_password( $password ), $user->ID ) );
					$this->query( $this->wpdb->prepare( "DELETE FROM {$this->wpdb->usermeta} WHERE meta_key = %s AND user_id = %d", '_bbp_password', $user->ID ) );

					// Clean the cache for this user since their password was
					// upgraded from the old platform to the new.
					clean_user_cache( $user->ID );
				}
			}
		}
	}

	/**
	 * A mini cache system to reduce database calls to forum ID's
	 *
	 * @param string $field.
	 * @return string
	 */
	private function callback_forumid( $field ) {
		if ( ! isset( $this->map_forumid[ $field ] ) ) {
			$row = ! empty( $this->sync_table )
				? $this->get_row( $this->wpdb->prepare( "SELECT value_id, meta_value FROM {$this->sync_table_name} WHERE meta_key = %s AND meta_value = %s LIMIT 1", '_bbp_old_forum_id', $field ) )
				: $this->get_row( $this->wpdb->prepare( "SELECT post_id AS value_id FROM {$this->wpdb->postmeta} WHERE meta_key = %s AND meta_value = %s LIMIT 1", '_bbp_old_forum_id', $field ) );

			$this->map_forumid[ $field ] = ! is_null( $row )
				? $row->value_id
				: 0;
		}

		return $this->map_forumid[ $field ];
	}

	/**
	 * A mini cache system to reduce database calls to topic ID's
	 *
	 * @param string $field.
	 * @return string
	 */
	private function callback_topicid( $field ) {
		if ( ! isset( $this->map_topicid[ $field ] ) ) {
			$row = ! empty( $this->sync_table )
				? $this->get_row( $this->wpdb->prepare( "SELECT value_id, meta_value FROM {$this->sync_table_name} WHERE meta_key = %s AND meta_value = %s LIMIT 1", '_bbp_old_topic_id', $field ) )
				: $this->get_row( $this->wpdb->prepare( "SELECT post_id AS value_id FROM {$this->wpdb->postmeta} WHERE meta_key = %s AND meta_value = %s LIMIT 1", '_bbp_old_topic_id', $field ) );

			$this->map_topicid[ $field ] = ! is_null( $row )
				? $row->value_id
				: 0;
		}

		return $this->map_topicid[ $field ];
	}

	/**
	 * A mini cache system to reduce database calls to reply_to post id.
	 *
	 * @since 2.4.0 bbPress (r5093)
	 *
	 * @param string $field.
	 * @return string
	 */
	private function callback_reply_to( $field ) {
		if ( ! isset( $this->map_reply_to[ $field ] ) ) {
			$row = ! empty( $this->sync_table )
				? $this->get_row( $this->wpdb->prepare( "SELECT value_id, meta_value FROM {$this->sync_table_name} WHERE meta_key = %s AND meta_value = %s LIMIT 1", '_bbp_old_reply_id', $field ) )
				: $this->get_row( $this->wpdb->prepare( "SELECT post_id AS value_id FROM {$this->wpdb->postmeta} WHERE meta_key = %s AND meta_value = %s LIMIT 1", '_bbp_old_reply_id', $field ) );

			$this->map_reply_to[ $field ] = ! is_null( $row )
				? $row->value_id
				: 0;
		}

		return $this->map_reply_to[ $field ];
	}

	/**
	 * A mini cache system to reduce database calls to user ID's
	 *
	 * @param string $field.
	 * @return string
	 */
	private function callback_userid( $field ) {
		if ( ! isset( $this->map_userid[ $field ] ) ) {
			$row = ! empty( $this->sync_table )
				? $this->get_row( $this->wpdb->prepare( "SELECT value_id, meta_value FROM {$this->sync_table_name} WHERE meta_key = %s AND meta_value = %s LIMIT 1", '_bbp_old_user_id', $field ) )
				: $this->get_row( $this->wpdb->prepare( "SELECT user_id AS value_id FROM {$this->wpdb->usermeta} WHERE meta_key = %s AND meta_value = %s LIMIT 1", '_bbp_old_user_id', $field ) );

			if ( ! is_null( $row ) ) {
				$this->map_userid[ $field ] = $row->value_id;
			} else {
				$this->map_userid[ $field ] = ( true === $this->convert_users )
					? 0
					: $field;
			}
		}

		return $this->map_userid[ $field ];
	}

	/**
	 * Check if the topic or reply author is anonymous
	 *
	 * @since 2.6.0 bbPress (r5544)
	 *
	 * @param  string $field.
	 * @return string
	 */
	private function callback_check_anonymous( $field ) {
		$field = ( $this->callback_userid( $field ) == 0 )
			? 'true'
			: 'false';

		return $field;
	}

	/**
	 * A mini cache system to reduce database calls map topics ID's to forum ID's
	 *
	 * @param string $field.
	 * @return string
	 */
	private function callback_topicid_to_forumid( $field ) {
		$topicid = $this->callback_topicid( $field );
		if ( empty( $topicid ) ) {
			$this->map_topicid_to_forumid[ $topicid ] = 0;
		} elseif ( ! isset( $this->map_topicid_to_forumid[ $topicid ] ) ) {
			$row = $this->get_row( $this->wpdb->prepare( "SELECT post_parent FROM {$this->wpdb->posts} WHERE ID = %d LIMIT 1", $topicid ) );

			$this->map_topicid_to_forumid[ $topicid ] = ! is_null( $row )
				? $row->post_parent
				: 0;
		}

		return $this->map_topicid_to_forumid[ $topicid ];
	}

	protected function callback_slug( $field ) {
		return sanitize_title( $field );
	}

	protected function callback_negative( $field ) {
		return ( $field < 0 )
			? 0
			: $field;
	}

	protected function callback_html( $field ) {
		require_once bbpress()->admin->admin_dir . 'parser.php';

		// Setup the BBCode parser
		$bbcode = BBCode::getInstance();

		// Pass BBCode properties to the parser
		foreach ( $this->bbcode_parser_properties as $prop => $value ) {
			$bbcode->{$prop} = $value;
		}

		return html_entity_decode( $bbcode->Parse( $field ), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401 );
	}

	protected function callback_null( $field ) {
		return is_null( $field )
			? ''
			: $field;
	}

	protected function callback_datetime( $field ) {
		return is_numeric( $field )
			? date( 'Y-m-d H:i:s', $field )
			: date( 'Y-m-d H:i:s', strtotime( $field ) );
	}
}

/**
 * This is a function that is purposely written to look like a "new" statement.
 * It is basically a dynamic loader that will load in the platform conversion
 * of your choice.
 *
 * @since 2.0.0
 *
 * @param string $platform Name of valid platform class.
 *
 * @return mixed Object if converter exists, null if not
 */
function bbp_new_converter( $platform = '' ) {
	$found = false;

	if ( $curdir = opendir( bbpress()->admin->admin_dir . 'converters/' ) ) {
		while ( $file = readdir( $curdir ) ) {
			if ( stristr( $file, '.php' ) && stristr( $file, 'index' ) === false ) {
				$file = preg_replace( '/.php/', '', $file );
				if ( $platform == $file ) {
					$found = true;
					continue;
				}
			}
		}
		closedir( $curdir );
	}

	if ( true === $found ) {
		require_once bbpress()->admin->admin_dir . 'converters/' . $platform . '.php';
		return new $platform();
	} else {
		return null;
	}
}
