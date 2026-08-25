<?php
/**
 * Performance Lab -- admin screen.
 *
 * TEMPORARY diagnostic tooling. See `bb-perf-lab.php` for removal instructions.
 *
 * @package    BuddyBoss\PerfLab
 * @subpackage PerfLab
 * @since      BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * The screen the three tools are driven from.
 *
 * Everything here is behind `manage_options` and a nonce. The seeder writes tens
 * of thousands of rows and the uninstaller drops a table, so neither is anything
 * a lesser role should be able to reach.
 *
 * @since BuddyBoss [BBVERSION]
 */
class BB_Perf_Lab_Admin {

	/**
	 * Singleton instance.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var BB_Perf_Lab_Admin|null
	 */
	protected static $instance = null;

	/**
	 * Get the singleton.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return BB_Perf_Lab_Admin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Register the screen and its endpoints.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	protected function __construct() {
		add_action( 'admin_menu', array( $this, 'menu' ) );

		$actions = array( 'seed_start', 'seed_tick', 'seed_purge', 'bench', 'clear_log', 'save_settings', 'uninstall' );

		foreach ( $actions as $action ) {
			add_action( 'wp_ajax_bb_perf_lab_' . $action, array( $this, 'ajax_' . $action ) );
		}
	}

	/**
	 * Add the menu entry.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public function menu() {
		$hook = add_management_page(
			__( 'Performance Lab', 'buddyboss' ),
			__( 'Performance Lab', 'buddyboss' ),
			'manage_options',
			BB_PERF_LAB_SLUG,
			array( $this, 'render' )
		);

		add_action( 'load-' . $hook, array( $this, 'prepare' ) );
	}

	/**
	 * Make sure the log table exists before the screen needs it.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public function prepare() {
		global $wpdb;

		$table = BB_Perf_Lab_Monitor::table();

		if ( ! $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			BB_Perf_Lab_Monitor::install();
		}

		wp_enqueue_script(
			'bb-perf-lab',
			plugins_url( 'assets/perf-lab.js', BB_PERF_LAB_DIR . '/bb-perf-lab.php' ),
			array(),
			BB_PERF_LAB_VERSION,
			true
		);

		wp_enqueue_style(
			'bb-perf-lab',
			plugins_url( 'assets/perf-lab.css', BB_PERF_LAB_DIR . '/bb-perf-lab.php' ),
			array(),
			BB_PERF_LAB_VERSION
		);

		wp_localize_script(
			'bb-perf-lab',
			'bbPerfLab',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'bb_perf_lab' ),
			)
		);
	}

	/**
	 * Reject anyone who should not be here.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	protected function guard() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You are not allowed to do that.', 'buddyboss' ) ), 403 );
		}

		check_ajax_referer( 'bb_perf_lab', 'nonce' );
	}

	// ---- AJAX ----

	/*
	 * Every handler below calls `guard()` first, which checks the capability
	 * and the nonce before a single `$_POST` key is read. The sniff cannot
	 * follow that across a method call, so it is switched off for this block
	 * and back on at the end of it.
	 */
	// phpcs:disable WordPress.Security.NonceVerification.Missing

	/**
	 * Begin a seeding job.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public function ajax_seed_start() {
		$this->guard();

		$plan = array();

		foreach ( array( 'users', 'groups', 'activities', 'comments', 'follows', 'friends', 'reactions', 'meta' ) as $key ) {
			if ( isset( $_POST[ $key ] ) ) {
				$plan[ $key ] = absint( wp_unslash( $_POST[ $key ] ) );
			}
		}

		BB_Perf_Lab_Seeder::start( $plan );

		wp_send_json_success( BB_Perf_Lab_Seeder::tick() );
	}

	/**
	 * Continue a seeding job.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public function ajax_seed_tick() {
		$this->guard();

		$progress = BB_Perf_Lab_Seeder::tick();

		if ( is_wp_error( $progress ) ) {
			wp_send_json_error( array( 'message' => $progress->get_error_message() ) );
		}

		wp_send_json_success( $progress );
	}

	/**
	 * Undo the current seeding job.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public function ajax_seed_purge() {
		$this->guard();

		wp_send_json_success( BB_Perf_Lab_Seeder::purge() );
	}

	/**
	 * Run the A/B comparison.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public function ajax_bench() {
		$this->guard();

		$args = array(
			'route'        => isset( $_POST['route'] ) ? sanitize_text_field( wp_unslash( $_POST['route'] ) ) : '/buddyboss/v1/activity',
			'query'        => isset( $_POST['query'] ) ? sanitize_text_field( wp_unslash( $_POST['query'] ) ) : 'per_page=20',
			'fields'       => isset( $_POST['fields'] ) ? sanitize_text_field( wp_unslash( $_POST['fields'] ) ) : 'id,date,content',
			'embed'        => isset( $_POST['embed'] ) ? sanitize_text_field( wp_unslash( $_POST['embed'] ) ) : '',
			'embed_fields' => isset( $_POST['embed_fields'] ) ? sanitize_text_field( wp_unslash( $_POST['embed_fields'] ) ) : '',
			'runs'         => isset( $_POST['runs'] ) ? absint( wp_unslash( $_POST['runs'] ) ) : 7,
			'flush'        => ! empty( $_POST['flush'] ),
			'user_id'      => isset( $_POST['user_id'] ) ? absint( wp_unslash( $_POST['user_id'] ) ) : 0,
		);

		$result = BB_Perf_Lab_Bench::run( $args );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( $result );
	}

	/**
	 * Empty the log.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public function ajax_clear_log() {
		$this->guard();

		global $wpdb;

		$table = BB_Perf_Lab_Monitor::table();

		$wpdb->query( "TRUNCATE TABLE {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange

		wp_send_json_success();
	}

	/**
	 * Save the monitor settings.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public function ajax_save_settings() {
		$this->guard();

		$settings = array(
			'monitor_enabled'  => ! empty( $_POST['monitor_enabled'] ),
			'monitor_deep'     => ! empty( $_POST['monitor_deep'] ),
			'monitor_routes'   => isset( $_POST['monitor_routes'] ) ? sanitize_text_field( wp_unslash( $_POST['monitor_routes'] ) ) : 'buddyboss/v1',
			'monitor_max_rows' => isset( $_POST['monitor_max_rows'] ) ? absint( wp_unslash( $_POST['monitor_max_rows'] ) ) : 5000,
			'monitor_user_id'  => isset( $_POST['monitor_user_id'] ) ? absint( wp_unslash( $_POST['monitor_user_id'] ) ) : 0,
		);

		update_option( BB_PERF_LAB_SETTINGS, $settings, false );

		wp_send_json_success( $settings );
	}

	/**
	 * Drop everything this tool created.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public function ajax_uninstall() {
		$this->guard();

		BB_Perf_Lab_Monitor::uninstall();

		delete_option( BB_PERF_LAB_SETTINGS );
		delete_option( BB_PERF_LAB_JOB );

		wp_send_json_success();
	}

	// phpcs:enable WordPress.Security.NonceVerification.Missing

	// ---- Screen ----

	/**
	 * Render the screen.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings = bb_perf_lab_setting();
		$job      = BB_Perf_Lab_Seeder::job();
		$counts   = $this->counts();
		?>
		<div class="wrap bb-perf-lab">
			<h1><?php esc_html_e( 'Performance Lab', 'buddyboss' ); ?></h1>

			<div class="notice notice-warning inline">
				<p>
					<strong><?php esc_html_e( 'Temporary diagnostic tooling.', 'buddyboss' ); ?></strong>
					<?php esc_html_e( 'This screen exists to measure the selective-fields work and is meant to be deleted once that is settled. It writes test content and logs request timings. Do not leave it on a production site.', 'buddyboss' ); ?>
				</p>
			</div>

			<h2 class="nav-tab-wrapper">
				<a href="#bench" class="nav-tab nav-tab-active" data-tab="bench"><?php esc_html_e( 'Benchmark', 'buddyboss' ); ?></a>
				<a href="#seed" class="nav-tab" data-tab="seed"><?php esc_html_e( 'Seed data', 'buddyboss' ); ?></a>
				<a href="#log" class="nav-tab" data-tab="log"><?php esc_html_e( 'Request log', 'buddyboss' ); ?></a>
				<a href="#settings" class="nav-tab" data-tab="settings"><?php esc_html_e( 'Settings', 'buddyboss' ); ?></a>
			</h2>

			<?php
			$this->render_bench();
			$this->render_seed( $job, $counts );
			$this->render_log();
			$this->render_settings( $settings );
			?>
		</div>
		<?php
	}

	/**
	 * The benchmark tab.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	protected function render_bench() {
		?>
		<div class="bb-perf-panel" data-panel="bench">
			<p class="description">
				<?php esc_html_e( 'Dispatches the same endpoint three ways in this process and compares them. Because nothing leaves the server, the numbers are the endpoint alone -- no network, no TLS, no WordPress bootstrap. That is the difference selective fields can actually move.', 'buddyboss' ); ?>
			</p>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="bb-perf-route"><?php esc_html_e( 'Route', 'buddyboss' ); ?></label></th>
					<td><input type="text" id="bb-perf-route" class="regular-text code" value="/buddyboss/v1/activity"></td>
				</tr>
				<tr>
					<th scope="row"><label for="bb-perf-query"><?php esc_html_e( 'Query string', 'buddyboss' ); ?></label></th>
					<td>
						<input type="text" id="bb-perf-query" class="regular-text code" value="per_page=20">
						<p class="description"><?php esc_html_e( 'Everything except the field selection, e.g. per_page=20&amp;scope=all&amp;display_comments=threaded', 'buddyboss' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="bb-perf-fields"><?php esc_html_e( '_fields under test', 'buddyboss' ); ?></label></th>
					<td><input type="text" id="bb-perf-fields" class="regular-text code" value="id,date,content,user_id"></td>
				</tr>
				<tr>
					<th scope="row"><label for="bb-perf-embed"><?php esc_html_e( '_embed', 'buddyboss' ); ?></label></th>
					<td>
						<input type="text" id="bb-perf-embed" class="regular-text code" value="" placeholder="user,group">
						<p class="description"><?php esc_html_e( 'Leave empty to skip embedding. Remember _links must be in the selection for embeds to be built.', 'buddyboss' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="bb-perf-embed-fields"><?php esc_html_e( 'embed_fields', 'buddyboss' ); ?></label></th>
					<td><input type="text" id="bb-perf-embed-fields" class="regular-text code" value="" placeholder="id,name,avatar_urls"></td>
				</tr>
				<tr>
					<th scope="row"><label for="bb-perf-runs"><?php esc_html_e( 'Runs per arm', 'buddyboss' ); ?></label></th>
					<td>
						<input type="number" id="bb-perf-runs" min="1" max="25" value="7" class="small-text">
						<label><input type="checkbox" id="bb-perf-flush" checked> <?php esc_html_e( 'Flush the object cache before each run', 'buddyboss' ); ?></label>
						<p class="description"><?php esc_html_e( 'Without a persistent object cache every real request starts cold, so flushing is the honest comparison. Uncheck it to see what a warm cache would give you.', 'buddyboss' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="bb-perf-user"><?php esc_html_e( 'Run as user ID', 'buddyboss' ); ?></label></th>
					<td>
						<input type="number" id="bb-perf-user" min="0" value="0" class="small-text">
						<p class="description"><?php esc_html_e( '0 runs as you. A regular member is the more realistic subject, since admins skip several privacy checks.', 'buddyboss' ); ?></p>
					</td>
				</tr>
			</table>

			<p>
				<button type="button" class="button button-primary" id="bb-perf-run"><?php esc_html_e( 'Run benchmark', 'buddyboss' ); ?></button>
				<span class="bb-perf-status" id="bb-perf-bench-status"></span>
			</p>

			<div id="bb-perf-bench-result"></div>
		</div>
		<?php
	}

	/**
	 * The seeder tab.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array|null $job    Current job.
	 * @param array      $counts What the install holds now.
	 *
	 * @return void
	 */
	protected function render_seed( $job, $counts ) {
		?>
		<div class="bb-perf-panel" data-panel="seed" hidden>
			<p class="description">
				<?php esc_html_e( 'Builds a community with the shape of a real one: members with profile data and a dense follow/friend graph, groups with memberships, and a feed of mixed activity types carrying metadata, comments and favourites. Volume alone makes the query slower; richness is what makes field building expensive, and so what makes declining to build fields worth measuring.', 'buddyboss' ); ?>
			</p>

			<p>
				<strong><?php esc_html_e( 'Currently in the database:', 'buddyboss' ); ?></strong>
				<?php
				printf(
					/* translators: 1: users, 2: activities, 3: activity meta rows, 4: groups */
					esc_html__( '%1$s members, %2$s activities, %3$s activity meta rows, %4$s groups.', 'buddyboss' ),
					esc_html( number_format_i18n( $counts['users'] ) ),
					esc_html( number_format_i18n( $counts['activities'] ) ),
					esc_html( number_format_i18n( $counts['activity_meta'] ) ),
					esc_html( number_format_i18n( $counts['groups'] ) )
				);
				?>
			</p>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Members', 'buddyboss' ); ?></th>
					<td><input type="number" id="bb-seed-users" value="400" min="0" class="small-text"></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Groups', 'buddyboss' ); ?></th>
					<td><input type="number" id="bb-seed-groups" value="30" min="0" class="small-text"></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Activities', 'buddyboss' ); ?></th>
					<td><input type="number" id="bb-seed-activities" value="50000" min="0" step="1000" class="regular-text"></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Comments', 'buddyboss' ); ?></th>
					<td><input type="number" id="bb-seed-comments" value="8000" min="0" step="500" class="regular-text"></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Favourites', 'buddyboss' ); ?></th>
					<td><input type="number" id="bb-seed-reactions" value="15000" min="0" step="500" class="regular-text"></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Follows per member', 'buddyboss' ); ?></th>
					<td><input type="number" id="bb-seed-follows" value="25" min="0" class="small-text"></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Friends per member', 'buddyboss' ); ?></th>
					<td><input type="number" id="bb-seed-friends" value="12" min="0" class="small-text"></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Meta rows per activity', 'buddyboss' ); ?></th>
					<td><input type="number" id="bb-seed-meta" value="4" min="1" max="20" class="small-text"></td>
				</tr>
			</table>

			<p>
				<button type="button" class="button button-primary" id="bb-seed-start"><?php esc_html_e( 'Generate', 'buddyboss' ); ?></button>
				<button type="button" class="button" id="bb-seed-resume" <?php echo ( null === $job || ! empty( $job['finished'] ) ) ? 'disabled' : ''; ?>><?php esc_html_e( 'Resume', 'buddyboss' ); ?></button>
				<button type="button" class="button button-link-delete" id="bb-seed-purge" <?php echo ( null === $job ) ? 'disabled' : ''; ?>><?php esc_html_e( 'Remove last batch', 'buddyboss' ); ?></button>
			</p>

			<p class="description">
				<?php esc_html_e( 'Generating runs in chunks driven by this page -- leave the tab open until it finishes. Running it again adds another batch on top, so you can build up to whatever size you need. "Remove last batch" undoes only the most recent run, and only within the ID range it created.', 'buddyboss' ); ?>
			</p>

			<div id="bb-seed-progress" hidden>
				<div class="bb-perf-bar"><span></span></div>
				<p class="bb-perf-status" id="bb-seed-status"></p>
			</div>
		</div>
		<?php
	}

	/**
	 * The request log tab.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	protected function render_log() {
		global $wpdb;

		$table = BB_Perf_Lab_Monitor::table();

		$rows = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY id DESC LIMIT 200" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		$summary = $wpdb->get_results( "SELECT route, selective, COUNT(*) AS hits, AVG(wall_ms) AS wall, AVG(db_ms) AS db, AVG(queries) AS queries, AVG(payload_kb) AS payload FROM {$table} GROUP BY route, selective ORDER BY route ASC, selective ASC" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		?>
		<div class="bb-perf-panel" data-panel="log" hidden>
			<p class="description">
				<?php esc_html_e( 'What real requests cost, recorded as they arrive. Switch the monitor on under Settings, drive the app, then come back. Each row covers the whole of serving that request, embedded sub-requests included, and stops at the point the payload is handed to the web server -- so the network is not in these numbers.', 'buddyboss' ); ?>
			</p>

			<p>
				<button type="button" class="button" id="bb-perf-clear-log"><?php esc_html_e( 'Clear log', 'buddyboss' ); ?></button>
				<a href="<?php echo esc_url( admin_url( 'tools.php?page=' . BB_PERF_LAB_SLUG ) ); ?>" class="button"><?php esc_html_e( 'Refresh', 'buddyboss' ); ?></a>
			</p>

			<?php if ( ! empty( $summary ) ) : ?>
				<h3><?php esc_html_e( 'Averages by route', 'buddyboss' ); ?></h3>
				<table class="widefat striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Route', 'buddyboss' ); ?></th>
							<th><?php esc_html_e( 'Selection', 'buddyboss' ); ?></th>
							<th><?php esc_html_e( 'Hits', 'buddyboss' ); ?></th>
							<th><?php esc_html_e( 'Server ms', 'buddyboss' ); ?></th>
							<th><?php esc_html_e( 'DB ms', 'buddyboss' ); ?></th>
							<th><?php esc_html_e( 'Queries', 'buddyboss' ); ?></th>
							<th><?php esc_html_e( 'Payload KB', 'buddyboss' ); ?></th>
						</tr>
					</thead>
					<tbody>
					<?php foreach ( $summary as $row ) : ?>
						<tr>
							<td><code><?php echo esc_html( $row->route ); ?></code></td>
							<td><?php echo $row->selective ? esc_html__( 'selective', 'buddyboss' ) : esc_html__( 'full', 'buddyboss' ); ?></td>
							<td><?php echo esc_html( number_format_i18n( (int) $row->hits ) ); ?></td>
							<td><?php echo esc_html( number_format_i18n( (float) $row->wall, 1 ) ); ?></td>
							<td><?php echo esc_html( number_format_i18n( (float) $row->db, 1 ) ); ?></td>
							<td><?php echo esc_html( number_format_i18n( (float) $row->queries, 1 ) ); ?></td>
							<td><?php echo esc_html( number_format_i18n( (float) $row->payload, 1 ) ); ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

			<h3><?php esc_html_e( 'Most recent requests', 'buddyboss' ); ?></h3>

			<?php if ( empty( $rows ) ) : ?>
				<p><?php esc_html_e( 'Nothing recorded yet.', 'buddyboss' ); ?></p>
			<?php else : ?>
				<table class="widefat striped bb-perf-log">
					<thead>
						<tr>
							<th><?php esc_html_e( 'When', 'buddyboss' ); ?></th>
							<th><?php esc_html_e( 'Route', 'buddyboss' ); ?></th>
							<th><?php esc_html_e( '_fields', 'buddyboss' ); ?></th>
							<th><?php esc_html_e( 'Items', 'buddyboss' ); ?></th>
							<th><?php esc_html_e( 'Server ms', 'buddyboss' ); ?></th>
							<th><?php esc_html_e( 'DB ms', 'buddyboss' ); ?></th>
							<th><?php esc_html_e( 'Queries', 'buddyboss' ); ?></th>
							<th><?php esc_html_e( 'Peak KB', 'buddyboss' ); ?></th>
							<th><?php esc_html_e( 'Payload KB', 'buddyboss' ); ?></th>
						</tr>
					</thead>
					<tbody>
					<?php foreach ( $rows as $row ) : ?>
						<tr class="<?php echo $row->selective ? 'is-selective' : ''; ?>">
							<td><?php echo esc_html( $row->logged_at ); ?></td>
							<td><code><?php echo esc_html( $row->route ); ?></code></td>
							<td><code><?php echo esc_html( $row->fields ? $row->fields : '—' ); ?></code></td>
							<td><?php echo esc_html( $row->items ); ?></td>
							<td><strong><?php echo esc_html( $row->wall_ms ); ?></strong></td>
							<td><?php echo esc_html( $row->db_ms ); ?></td>
							<td><?php echo esc_html( $row->queries ); ?></td>
							<td><?php echo esc_html( $row->mem_peak_kb ); ?></td>
							<td><?php echo esc_html( $row->payload_kb ); ?></td>
						</tr>
						<?php if ( ! empty( $row->slow_queries ) ) : ?>
							<tr class="bb-perf-slow">
								<td colspan="9">
									<details>
										<summary><?php esc_html_e( 'Slowest queries', 'buddyboss' ); ?></summary>
										<pre><?php echo esc_html( wp_json_encode( json_decode( $row->slow_queries ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) ); ?></pre>
									</details>
								</td>
							</tr>
						<?php endif; ?>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * The settings tab.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $settings Current settings.
	 *
	 * @return void
	 */
	protected function render_settings( $settings ) {
		?>
		<div class="bb-perf-panel" data-panel="settings" hidden>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Record requests', 'buddyboss' ); ?></th>
					<td>
						<label><input type="checkbox" id="bb-set-enabled" <?php checked( $settings['monitor_enabled'] ); ?>> <?php esc_html_e( 'Log every matching REST request', 'buddyboss' ); ?></label>
						<p class="description"><?php esc_html_e( 'Off by default. Each logged request costs one extra insert, so switch it off again when you are done.', 'buddyboss' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Time every query', 'buddyboss' ); ?></th>
					<td>
						<label><input type="checkbox" id="bb-set-deep" <?php checked( $settings['monitor_deep'] ); ?>> <?php esc_html_e( 'Turn on SAVEQUERIES', 'buddyboss' ); ?></label>
						<p class="description"><?php esc_html_e( 'Needed for the DB-time column and the slow-query list. It makes every request a little slower and holds every query in memory, so read the wall-clock figures with that in mind.', 'buddyboss' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="bb-set-routes"><?php esc_html_e( 'Routes', 'buddyboss' ); ?></label></th>
					<td>
						<input type="text" id="bb-set-routes" class="regular-text code" value="<?php echo esc_attr( $settings['monitor_routes'] ); ?>">
						<p class="description"><?php esc_html_e( 'Comma-separated substrings. Empty records everything.', 'buddyboss' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="bb-set-user"><?php esc_html_e( 'Only this user', 'buddyboss' ); ?></label></th>
					<td>
						<input type="number" id="bb-set-user" min="0" value="<?php echo esc_attr( $settings['monitor_user_id'] ); ?>" class="small-text">
						<p class="description"><?php esc_html_e( 'Set to the account the app signs in as, to keep other traffic out of the log. 0 records everyone.', 'buddyboss' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="bb-set-max"><?php esc_html_e( 'Rows to keep', 'buddyboss' ); ?></label></th>
					<td><input type="number" id="bb-set-max" min="100" step="100" value="<?php echo esc_attr( $settings['monitor_max_rows'] ); ?>" class="regular-text"></td>
				</tr>
			</table>

			<p>
				<button type="button" class="button button-primary" id="bb-perf-save"><?php esc_html_e( 'Save settings', 'buddyboss' ); ?></button>
				<span class="bb-perf-status" id="bb-perf-settings-status"></span>
			</p>

			<hr>

			<h3><?php esc_html_e( 'Removing this tool', 'buddyboss' ); ?></h3>
			<p class="description">
				<?php esc_html_e( 'Press this first, then delete the src/bb-perf-lab directory and the single require line in src/bp-loader.php. It drops the log table and every option this tool created. It does not touch generated content -- use "Remove last batch" on the seeder tab for that.', 'buddyboss' ); ?>
			</p>
			<p>
				<button type="button" class="button button-link-delete" id="bb-perf-uninstall"><?php esc_html_e( 'Drop log table and settings', 'buddyboss' ); ?></button>
			</p>
		</div>
		<?php
	}

	/**
	 * What the install currently holds.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return array
	 */
	protected function counts() {
		global $wpdb;

		return array(
			'users'         => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->users}" ), // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			'activities'    => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}bp_activity" ), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			'activity_meta' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}bp_activity_meta" ), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			'groups'        => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}bp_groups" ), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		);
	}
}
