<?php
/**
 * Performance Lab -- TEMPORARY diagnostic tooling.
 *
 * Three tools that exist to answer one question: does the selective-fields work
 * actually make the REST API faster, and if the app cannot see the difference,
 * where is the time really going?
 *
 * - Seeder:  fills the install with a realistic community, so the queries have
 *            enough to chew on for the difference to be visible at all.
 * - Monitor: records what every REST request costs -- wall time, database time,
 *            query count, memory, payload -- alongside the `_fields` it asked
 *            for, so real app traffic can be compared after the fact.
 * - Bench:   runs the same endpoint twice in one process, with and without a
 *            selection, and reports the difference. Because it dispatches
 *            in-process, it measures the server and nothing else: no network,
 *            no TLS, no WordPress bootstrap.
 *
 * ---------------------------------------------------------------------------
 * THIS DIRECTORY IS MEANT TO BE DELETED.
 *
 * To remove every trace of it:
 *
 *   1. rm -rf src/bb-perf-lab
 *   2. Delete the single `bb-perf-lab.php` require in `src/bp-loader.php`.
 *   3. Visit any admin page once beforehand and use "Uninstall" on the
 *      Performance Lab screen, which drops the log table and every option
 *      this tool created. (Nothing else in the plugin reads them, so skipping
 *      this leaves one unused table behind, nothing more.)
 *
 * Nothing outside this directory depends on anything inside it.
 * ---------------------------------------------------------------------------
 *
 * @package    BuddyBoss\PerfLab
 * @subpackage PerfLab
 * @since      BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

define( 'BB_PERF_LAB_VERSION', '1.0.0' );
define( 'BB_PERF_LAB_DIR', __DIR__ );
define( 'BB_PERF_LAB_SLUG', 'bb-perf-lab' );

/**
 * Option holding the monitor's settings.
 *
 * @since BuddyBoss [BBVERSION]
 */
define( 'BB_PERF_LAB_SETTINGS', 'bb_perf_lab_settings' );

/**
 * Option holding the seeder's in-progress job.
 *
 * @since BuddyBoss [BBVERSION]
 */
define( 'BB_PERF_LAB_JOB', 'bb_perf_lab_job' );

require_once BB_PERF_LAB_DIR . '/classes/class-bb-perf-lab-monitor.php';
require_once BB_PERF_LAB_DIR . '/classes/class-bb-perf-lab-seeder.php';
require_once BB_PERF_LAB_DIR . '/classes/class-bb-perf-lab-bench.php';
require_once BB_PERF_LAB_DIR . '/classes/class-bb-perf-lab-admin.php';

/**
 * Read the Performance Lab settings.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $key      Optional. Setting to read. Default '' for all of them.
 * @param mixed  $fallback Optional. Value to return when the setting is unset.
 *                         Default null.
 *
 * @return mixed The setting, or every setting when `$key` is empty.
 */
function bb_perf_lab_setting( $key = '', $fallback = null ) {
	$defaults = array(
		// The monitor records nothing until this is switched on.
		'monitor_enabled'  => false,
		// Routes the monitor records, as substrings of the route path.
		'monitor_routes'   => 'buddyboss/v1',
		// Whether to time every query individually. Costs a little, tells a lot.
		'monitor_deep'     => false,
		// Rows kept in the log. The oldest are dropped past this.
		'monitor_max_rows' => 5000,
		// Requests from this user only, when set. 0 records everyone.
		'monitor_user_id'  => 0,
	);

	$settings = wp_parse_args( (array) get_option( BB_PERF_LAB_SETTINGS, array() ), $defaults );

	if ( '' === $key ) {
		return $settings;
	}

	return array_key_exists( $key, $settings ) ? $settings[ $key ] : $fallback;
}

/**
 * Boot the Performance Lab.
 *
 * The monitor has to be in place before the REST server dispatches, and it wants
 * `SAVEQUERIES` defined before anything queries the database, so this runs at
 * `plugins_loaded` priority 1 rather than waiting for `bp_loaded`.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return void
 */
function bb_perf_lab_init() {
	BB_Perf_Lab_Monitor::instance();

	if ( is_admin() ) {
		BB_Perf_Lab_Admin::instance();
	}
}
add_action( 'plugins_loaded', 'bb_perf_lab_init', 1 );
