<?php
/**
 * This file contains the necessary code for the BuddyBoss Performance Tester.
 *
 * @package BuddyBoss
 *
 * @since BuddyBoss 2.6.30
 */

require_once buddypress()->plugin_dir . 'bp-core/admin/templates/benchmark.php';

/**
 * BuddyBoss Performance Tester
 *
 * @package BuddyBoss
 *
 * @since BuddyBoss 2.6.30
 */
class BB_Performance_Tester {

	/**
	 * Store class instance.
	 *
	 * @var BB_Performance_Tester
	 *
	 * @since BuddyBoss 2.6.30
	 */
	private static $instance;

	/**
	 * Class instance
	 *
	 * @since BuddyBoss 2.6.30
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof BB_Performance_Tester ) ) {
			self::$instance = new BB_Performance_Tester();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 *
	 * @since BuddyBoss 2.6.30
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts_and_style' ) );
	}

	/**
	 * Settings page.
	 *
	 * @since BuddyBoss 2.6.30
	 */
	public function settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'buddyboss' ) );
		}
		$bb_perform_test = false;
		// phpcs:ignore WordPress.Security.NonceVerification
		if ( ! empty( $_POST['bb_perform_test'] ) && ( true === (bool) $_POST['bb_perform_test'] ) ) {
			// verify nonce.
			if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) ) ) {
				wp_die( esc_html__( 'Invalid Request. Reload to try again.', 'buddyboss' ) );
			} else {
				$bb_perform_test = true;
			}
		}

		?>
		<div class="wrap wrap--performance">
			<h2><?php esc_html_e( 'Host Performance Tester', 'buddyboss' ); ?></h2>
			<p>
				<?php
				printf(
					/* translators: WPHostingBenchmarks link */
					wp_kses_post( __( 'This tool completes a series of tests to see how well your server performs. The first set tests the raw server performance. The second is WordPress specific. Your results will be displayed and you can see how your results stack up against others. To compare your benchmarks against other hosts, check out %s', 'buddyboss' ) ),
					'<a href="https://wphostingbenchmarks.com/">WPHostingBenchmarks.com</a>'
				);
				?>
			</p>

			<form method="post" action="
			<?php
			echo esc_url(
				bp_get_admin_url(
					add_query_arg(
						array(
							'page' => 'bb-upgrade',
							'tab'  => 'bb-performance-tester',
						),
						'admin.php'
					)
				)
			);
			?>
			" class="wp-performance-check">
				<input type="hidden" name="bb_perform_test" value="true">
				<?php wp_nonce_field(); ?>
				<input type="submit" value="<?php esc_html_e( 'Begin Performance Test', 'buddyboss' ); ?>" onclick="this.value='<?php esc_html_e( 'This may take a minute...', 'buddyboss' ); ?>'">
			</form>

			<?php
			if ( $bb_perform_test ) {
				// do test.
				global $wpdb;
				$arr_cfg = array();

				// We need special handling for hyperdb.
				if ( is_a( $wpdb, 'hyperdb' ) && ! empty( $wpdb->hyper_servers ) ) {
					// Grab a `write` server for the `global` dataset and fallback to `read`.
					// We're not really paying attention to priority or have much in the way of error checking. Use at your own risk :).
					$db_server = false;
					if ( ! empty( $wpdb->hyper_servers['global']['write'] ) ) {
						foreach ( $wpdb->hyper_servers['global']['write'] as $group => $dbs ) {
							$db_server = current( $dbs );
							break;
						}
					} elseif ( ! empty( $wpdb->hyper_servers['global']['read'] ) ) {
						foreach ( $wpdb->hyper_servers['global']['read'] as $group => $dbs ) {
							$db_server = current( $dbs );
							break;
						}
					}

					if ( $db_server ) {
						$arr_cfg['db.host'] = $db_server['host'];
						$arr_cfg['db.user'] = $db_server['user'];
						$arr_cfg['db.pw']   = $db_server['password'];
						$arr_cfg['db.name'] = $db_server['name'];
					}
				} else {
					// Vanilla WordPress install with standard `wpdb`.
					$arr_cfg['db.host'] = DB_HOST;
					$arr_cfg['db.user'] = DB_USER;
					$arr_cfg['db.pw']   = DB_PASSWORD;
					$arr_cfg['db.name'] = DB_NAME;
				}

				$arr_benchmark = bb_test_benchmark( $arr_cfg );
				$arr_wordpress = bb_test_wordpress();

				// charting from results goes here.
				?>
				<h2><?php esc_html_e( 'Performance Test Results (in seconds)', 'buddyboss' ); ?></h2>
				<div id="chartDiv">
					<div id="legendDiv"></div>
					<canvas id="myChart" height="400" width="600"></canvas>
				</div>
				<p><?php esc_html_e( '* Lower (faster) time is better.', 'buddyboss' ); ?></p>
				<script>
					jQuery(document).ready(function(){
						jQuery.getJSON( "https://wphreviews.com/api/wpperformancetester.php?version=<?php echo esc_attr( $this->get_version() ); ?>", function( industryData ) {

							//new code
							const labels = [
								"<?php esc_html_e( 'Math (CPU)', 'buddyboss' ); ?>",
								"<?php esc_html_e( 'String (CPU)', 'buddyboss' ); ?>",
								"<?php esc_html_e( 'Loops (CPU)', 'buddyboss' ); ?>",
								"<?php esc_html_e( 'Conditionals (CPU)', 'buddyboss' ); ?>",
								"<?php esc_html_e( 'MySql (Database)', 'buddyboss' ); ?>",
								"<?php esc_html_e( 'Server Total', 'buddyboss' ); ?>",
								"<?php esc_html_e( 'WordPress Performance', 'buddyboss' ); ?>"
							];
							const data = {
								labels: labels,
								datasets: [
									{
										label: "<?php esc_attr_e( 'Your Results', 'buddyboss' ); ?>",
										backgroundColor: "rgba(151,187,205,0.5)",
										borderColor: "rgba(151,187,205,0.8)",
										hoverBackgroundColor: "rgba(151,187,205,0.75)",
										hoverBorderColor: "rgba(151,187,205,1)",
										data: [
											<?php echo esc_attr( $arr_benchmark['benchmark']['math'] ); ?>,
											<?php echo esc_attr( $arr_benchmark['benchmark']['string'] ); ?>,
											<?php echo esc_attr( $arr_benchmark['benchmark']['loops'] ); ?>,
											<?php echo esc_attr( $arr_benchmark['benchmark']['ifelse'] ); ?>,
											<?php echo esc_attr( $arr_benchmark['benchmark']['mysql_query_benchmark'] ); ?>,
											<?php echo esc_attr( $arr_benchmark['total'] ); ?>,
											<?php echo esc_attr( $arr_wordpress['time'] ); ?>
										]
									},
									{
										label: "<?php esc_html_e( 'Industry Average', 'buddyboss' ); ?>",
										backgroundColor: "rgba(130,130,130,0.5)",
										borderColor: "rgba(130,130,130,0.8)",
										hoverBackgroundColor: "rgba(130,130,130,0.75)",
										hoverBorderColor: "rgba(130,130,130,1)",
										data: industryData
									},
									{
										label: "<?php esc_html_e( 'Rapyd', 'buddyboss' ); ?>",
										backgroundColor: "rgba(219,73,179,1)",
										borderColor: "rgba(219,73,179,1)",
										hoverBackgroundColor: "rgba(219,73,179,1)",
										hoverBorderColor: "rgba(219,73,179,1)",
										data: [0.026, 0.061, 0.005, 0.007, 7.244, 7.342, 0.296]
									}
								]
							};
							const config = {
								type: 'bar',
								data: data,
								options: {
									scales: {
										y: {
											beginAtZero: true
										}
									},
									plugins: {
										legend: {
											display: true
										}
									}
								},
							};
							var myChart = new Chart(
								document.getElementById('myChart'),
								config
							);
						});
					});
				</script>


				<div id="resultTable">
					<table width="600">
						<caption><?php esc_html_e( 'Server Performance Benchmarks', 'buddyboss' ); ?></caption>
						<thead>
						<tr>
							<th width="300"><?php esc_html_e( 'Test', 'buddyboss' ); ?></th>
							<th><?php esc_html_e( 'Execution Time (seconds)', 'buddyboss' ); ?></th>
						</tr>
						</thead>
						<tbody>
						<tr>
							<td>
								<span class="simptip-position-right simptip-smooth" data-tooltip="<?php esc_attr_e( 'Times 10,000 mathematical functions in PHP', 'buddyboss' ); ?>">
									<?php esc_html_e( 'Math', 'buddyboss' ); ?>
								</span>
							</td>
							<td><?php echo esc_html( $arr_benchmark['benchmark']['math'] ); ?></td>
						</tr>
						<tr>
							<td>
								<span class="simptip-position-right simptip-smooth" data-tooltip="<?php esc_attr_e( 'Times 10,000 string manipulation functions in PHP', 'buddyboss' ); ?>">
									<?php esc_html_e( 'String Manipulation', 'buddyboss' ); ?>
								</span>
							</td>
							<td><?php echo esc_html( $arr_benchmark['benchmark']['string'] ); ?></td>
						</tr>
						<tr>
							<td>
								<span class="simptip-position-right simptip-smooth" data-tooltip="<?php esc_attr_e( 'Times 10,000 increments in PHP while and for loops', 'buddyboss' ); ?>">
									<?php esc_html_e( 'Loops', 'buddyboss' ); ?>
								</span>
							</td>
							<td><?php echo esc_html( $arr_benchmark['benchmark']['loops'] ); ?></td>
						</tr>
						<tr>
							<td>
								<span class="simptip-position-right simptip-smooth" data-tooltip="<?php esc_attr_e( 'Times 10,000 conditional checks in PHP', 'buddyboss' ); ?>">
									<?php esc_html_e( 'Conditionals', 'buddyboss' ); ?>
								</span>
							</td>
							<td><?php echo esc_html( $arr_benchmark['benchmark']['ifelse'] ); ?></td>
						</tr>
						<tr>
							<td>
								<span class="simptip-position-right simptip-smooth" data-tooltip="<?php esc_attr_e( 'Time it takes to establish a Mysql Connection', 'buddyboss' ); ?>">
									<?php esc_html_e( 'Mysql Connect', 'buddyboss' ); ?>
								</span>
							</td>
							<td><?php echo esc_html( $arr_benchmark['benchmark']['mysql_connect'] ); ?></td>
						</tr>
						<tr>
							<td>
								<span class="simptip-position-right simptip-smooth" data-tooltip="<?php esc_attr_e( 'Time it takes to query Mysql version information', 'buddyboss' ); ?>">
									<?php esc_html_e( 'Mysql Query Version', 'buddyboss' ); ?>
								</span>
							</td>
							<td><?php echo esc_html( $arr_benchmark['benchmark']['mysql_query_version'] ); ?></td>
						</tr>
						<tr>
							<td>
								<span class="simptip-position-right simptip-smooth" data-tooltip="<?php esc_attr_e( 'Time it takes for 1,000,000 ENCODE()s with a random seed', 'buddyboss' ); ?>">
									<?php esc_html_e( 'Mysql Query Benchmark', 'buddyboss' ); ?>
								</span>
							</td>
							<td><?php echo esc_html( $arr_benchmark['benchmark']['mysql_query_benchmark'] ); ?></td>
						</tr>
						</tbody>
						<tfoot>
						<tr>
							<th><?php esc_html_e( 'Total Time (seconds)', 'buddyboss' ); ?></th>
							<th><?php echo esc_html( $arr_benchmark['total'] ); ?></th>
						</tr>
						</tfoot>
					</table>
					<br />
					<table width="600">
						<caption>
							<span class="simptip-position-bottom simptip-multiline simptip-smooth" data-tooltip="<?php esc_attr_e( 'Performs 250 Insert, Select, Update and Delete functions through $wpdb', 'buddyboss' ); ?>">
								<?php esc_html_e( 'WordPress Performance Benchmarks', 'buddyboss' ); ?>
							</span>
						</caption>
						<thead>
						<tr>
							<th width="300"><?php esc_html_e( 'Execution Time (seconds)', 'buddyboss' ); ?></th>
							<th><?php esc_html_e( 'Queries Per Second', 'buddyboss' ); ?></th>
						</tr>
						</thead>
						<tfoot>
						<tr>
							<td><?php echo esc_html( $arr_wordpress['time'] ); ?></td>
							<td><?php echo esc_html( $arr_wordpress['queries'] ); ?></td>
						</tr>
						</tfoot>
					</table>
					<br />
					<table width="600">
						<caption><?php esc_html_e( 'Your Server Information', 'buddyboss' ); ?></caption>
						<thead>
						<tr>
							<th width="300"><?php esc_html_e( 'Test', 'buddyboss' ); ?></th>
							<th><?php esc_html_e( 'Result', 'buddyboss' ); ?></th>
						</tr>
						</thead>
						<tbody>
						<tr>
							<td><?php esc_html_e( 'WPPerformanceTester Version', 'buddyboss' ); ?></td>
							<td><?php echo esc_html( $this->get_version() ); ?></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'System Time', 'buddyboss' ); ?></td>
							<td><?php echo esc_html( $arr_benchmark['sysinfo']['time'] ); ?></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'Platform', 'buddyboss' ); ?></td>
							<td><?php echo esc_html( $arr_benchmark['sysinfo']['platform'] ); ?></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'Server Name', 'buddyboss' ); ?></td>
							<td><?php echo esc_html( $arr_benchmark['sysinfo']['server_name'] ); ?></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'Server Address', 'buddyboss' ); ?></td>
							<td><?php echo esc_html( $arr_benchmark['sysinfo']['server_addr'] ); ?></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'MySql Server', 'buddyboss' ); ?></td>
							<td><?php echo esc_html( DB_HOST ); ?></td>
						</tr>
						</tbody>
					</table>
				</div>
				<br />
				<?php
			}
			?>
		</div>
		<?php
	}

	/**
	 * Version name of the API.
	 *
	 * @since BuddyBoss 2.6.30
	 *
	 * @return string
	 */
	private function get_version() {
		return '2.0.0';
	}

	/**
	 * Fires when enqueuing scripts for all admin pages.
	 *
	 * @since BuddyBoss 2.6.30
	 *
	 * @param string $hook The current admin page.
	 *
	 * @return void
	 */
	public function enqueue_scripts_and_style( $hook ) {
		if ( 'buddyboss_page_bb-upgrade' !== $hook ) {
			return;
		}
		global $bp;

		$min     = bp_core_get_minified_asset_suffix();
		$version = bp_get_version();

		$css_url = trailingslashit( $bp->plugin_url . 'bp-core/admin/css' ); // Admin css URL.
		$js_url  = trailingslashit( $bp->plugin_url . 'bp-core/admin/js' ); // Admin css URL.

		wp_enqueue_script( 'chart-js-3-7', "{$js_url}lib/Chart.js", array( 'bb-upgrade' ), $version, true );

		wp_enqueue_style( 'bb-upgrade-performance', "{$css_url}performance-tester{$min}.css", array(), $version );
	}
}
