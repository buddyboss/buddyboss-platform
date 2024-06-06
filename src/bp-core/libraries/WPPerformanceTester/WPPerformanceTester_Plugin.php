<?php
require_once( 'WPPerformanceTester_LifeCycle.php' );
require_once( 'benchmark.php' );

class WPPerformanceTester_Plugin extends WPPerformanceTester_LifeCycle {

    /**
    * Override settingsPage()
    */
    public function settingsPage() {

        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        $performTest = false;
        if ( !empty( $_POST['performTest'] ) && ( $_POST['performTest'] == true ) ) {
            //verify nonce
            if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ '_wpnonce' ] ) ) ) ) {
                wp_die( 'Invalid Request. Reload to try again.' );
            }else{
                $performTest=true;
            }
        }
        ?>
        <div class="wrap">
            <h2>WPPerformanceTester</h2>
            <p>WPPerformanceTester performs a series of tests to see how well your server performs. The first set test the raw server performance. The second is WordPress specific. Your results will be displayed and you can see how your results stack up against others.</p>

            <form method="post" action="<?php echo esc_url( bp_get_admin_url(
				add_query_arg(
					array(
						'page' => 'bb-upgrade',
						'tab'  => 'bb-performance-tester',
					),
					'admin.php'
				)
			) ); ?>">
                <input type="hidden" name="performTest" value="true">
                <?php wp_nonce_field(); ?>
                <input type="submit" value="Begin Performance Test" onclick="this.value='This may take a minute...'">
            </form>

            <?php
            if ( $performTest ) {
                //do test
                global $wpdb;
                $arr_cfg = array();

                // We need special handling for hyperdb
                if ( is_a( $wpdb, 'hyperdb' ) && ! empty( $wpdb->hyper_servers ) ) {
                  // Grab a `write` server for the `global` dataset and fallback to `read`.
                  // We're not really paying attention to priority or have much in the way of error checking. Use at your own risk :)
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
                    $arr_cfg['db.pw'] = $db_server['password'];
                    $arr_cfg['db.name'] = $db_server['name'];
                  }
                } else {
                  // Vanilla WordPress install with standard `wpdb`
                  $arr_cfg['db.host'] = DB_HOST;
                  $arr_cfg['db.user'] = DB_USER;
                  $arr_cfg['db.pw'] = DB_PASSWORD;
                  $arr_cfg['db.name'] = DB_NAME;
                }

                $arr_benchmark = test_benchmark($arr_cfg);
                $arr_wordpress = test_wordpress();


                //charting from results goes here
                ?>
                <h2>Performance Test Results (in seconds)</h2>
                <div id="chartDiv">
                    <div id="legendDiv"></div>
                    <canvas id="myChart" height="400" width="600"></canvas>
                </div>
                <p>* Lower (faster) time is better. Please submit your results to improve our industry average data :)</p>
                <script>
                jQuery(document).ready(function(){
                    jQuery.getJSON( "https://wphreviews.com/api/wpperformancetester.php?version=<?php echo $this->getVersion(); ?>", function( industryData ) {

                        //new code
                        const labels = ["Math (CPU)", "String (CPU)", "Loops (CPU)", "Conditionals (CPU)", "MySql (Database)", "Server Total", "WordPress Performance"];
                        const data = {
                          labels: labels,
                          datasets: [
                                {
                                    label: "Your Results",
                                    backgroundColor: "rgba(151,187,205,0.5)",
                                    borderColor: "rgba(151,187,205,0.8)",
                                    hoverBackgroundColor: "rgba(151,187,205,0.75)",
                                    hoverBorderColor: "rgba(151,187,205,1)",
                                    data: [<?php echo $arr_benchmark['benchmark']['math']; ?>, <?php echo $arr_benchmark['benchmark']['string']; ?>, <?php echo $arr_benchmark['benchmark']['loops']; ?>, <?php echo $arr_benchmark['benchmark']['ifelse']; ?>, <?php echo $arr_benchmark['benchmark']['mysql_query_benchmark']; ?>, <?php echo $arr_benchmark['total']; ?>, <?php echo $arr_wordpress['time']; ?>]
                                },
                                {
                                    label: "Industry Average",
                                    backgroundColor: "rgba(130,130,130,0.5)",
                                    borderColor: "rgba(130,130,130,0.8)",
                                    hoverBackgroundColor: "rgba(130,130,130,0.75)",
                                    hoverBorderColor: "rgba(130,130,130,1)",
                                    data: industryData
                                },
							  {
								  label: "Rapyd",
								  backgroundColor: "rgba(219,73,179,1)",
								  borderColor: "rgba(219,73,179,1)",
								  hoverBackgroundColor: "rgba(219,73,179,1)",
								  hoverBorderColor: "rgba(219,73,179,1)",
								  data: [0.372, 1.909, 0.121, 0.258, 5.404, 8.064, 2.279]
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


                       /* var myChart = new Chart(ctx).Bar(data, {
                            barShowStroke: false,
                            multiTooltipTemplate: "<%= datasetLabel %> - <%= value %> Seconds",
                        });
                        var legendHolder = document.createElement('div');
                        legendHolder.innerHTML = myChart.generateLegend();

                        document.getElementById('legendDiv').appendChild(legendHolder.firstChild);*/
                    });

                });
                </script>


                <div id="resultTable">
                  <table width="600">
                    <caption>Server Performance Benchmarks</caption>
                    <thead>
                      <tr>
                        <th width="300">Test</th>
                        <th>Execution Time (seconds)</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr>
                        <td><span class="simptip-position-right simptip-smooth" data-tooltip="Times 10,000 mathematical functions in PHP">Math</span></td>
                        <td><?php echo $arr_benchmark['benchmark']['math']; ?></td>
                      </tr>
                      <tr>
                        <td><span class="simptip-position-right simptip-smooth" data-tooltip="Times 10,000 string manipulation functions in PHP">String Manipulation</span></td>
                        <td><?php echo $arr_benchmark['benchmark']['string']; ?></td>
                      </tr>
                      <tr>
                        <td><span class="simptip-position-right simptip-smooth" data-tooltip="Times 10,000 increments in PHP while and for loops">Loops</span></td>
                        <td><?php echo $arr_benchmark['benchmark']['loops']; ?></td>
                      </tr>
                      <tr>
                        <td><span class="simptip-position-right simptip-smooth" data-tooltip="Times 10,000 conditional checks in PHP">Conditionals</span></td>
                        <td><?php echo $arr_benchmark['benchmark']['ifelse']; ?></td>
                      </tr>
                      <tr>
                        <td><span class="simptip-position-right simptip-smooth" data-tooltip="Time it takes to establish a Mysql Connection">Mysql Connect</span></td>
                        <td><?php echo $arr_benchmark['benchmark']['mysql_connect']; ?></td>
                      </tr>
                      <tr>
                        <td><span class="simptip-position-right simptip-smooth" data-tooltip="Time it takes to query Mysql version information">Mysql Query Version</span></td>
                        <td><?php echo $arr_benchmark['benchmark']['mysql_query_version']; ?></td>
                      </tr>
                      <tr>
                        <td><span class="simptip-position-right simptip-smooth" data-tooltip="Time it takes for 1,000,000 ENCODE()s with a random seed">Mysql Query Benchmark</span></td>
                        <td><?php echo $arr_benchmark['benchmark']['mysql_query_benchmark']; ?></td>
                      </tr>
                    </tbody>
                    <tfoot>
                      <tr>
                        <th>Total Time (seconds)</th>
                        <th><?php echo $arr_benchmark['total']; ?></th>
                      </tr>
                    </tfoot>
                  </table>
                  <br />
                  <table width="600">
                    <caption><span class="simptip-position-bottom simptip-multiline simptip-smooth" data-tooltip="Performs 250 Insert, Select, Update and Delete functions through $wpdb">WordPress Performance Benchmark</span></caption>
                    <thead>
                      <tr>
                        <th width="300">Execution Time (seconds)</th>
                        <th>Queries Per Second</th>
                      </tr>
                    </thead>
                    <tfoot>
                      <tr>
                        <td><?php echo $arr_wordpress['time']; ?></td>
                        <td><?php echo $arr_wordpress['queries']; ?></td>
                      </tr>
                    </tfoot>
                  </table>
                  <br />
                  <table width="600">
                    <caption>Your Server Information</caption>
                    <thead>
                      <tr>
                        <th width="300">Test</th>
                        <th>Result</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr>
                        <td>WPPerformanceTester Version</td>
                        <td><?php echo $this->getVersion(); ?></td>
                      </tr>
                      <tr>
                        <td>System Time</td>
                        <td><?php echo $arr_benchmark['sysinfo']['time']; ?></td>
                      </tr>
                      <tr>
                        <td>Platform</td>
                        <td><?php echo $arr_benchmark['sysinfo']['platform']; ?></td>
                      </tr>
                      <tr>
                        <td>Server Name</td>
                        <td><?php echo $arr_benchmark['sysinfo']['server_name'] ; ?></td>
                      </tr>
                      <tr>
                        <td>Server Address</td>
                        <td><?php echo $arr_benchmark['sysinfo']['server_addr'] ; ?></td>
                      </tr>
                      <tr>
                        <td>MySql Server</td>
                        <td><?php echo DB_HOST; ?></td>
                      </tr>
                    </tbody>
                  </table>
                </div>
            <br />
            <br />
            <?php
            }
            ?>



        </div>


        <?php
    }

    /**
     * See: http://plugin.michael-simpson.com/?page_id=31
     * @return array of option meta data.
     */
    public function getOptionMetaData() {
        //  http://plugin.michael-simpson.com/?page_id=31
        return array(
            //'_version' => array('Installed Version'), // Leave this one commented-out. Uncomment to test upgrades.
            'ATextInput' => array(__('Enter in some text', 'my-awesome-plugin')),
            'AmAwesome' => array(__('I like this awesome plugin', 'my-awesome-plugin'), 'false', 'true'),
            'CanDoSomething' => array(__('Which user role can do something', 'my-awesome-plugin'),
                                        'Administrator', 'Editor', 'Author', 'Contributor', 'Subscriber', 'Anyone')
        );
    }

//    protected function getOptionValueI18nString($optionValue) {
//        $i18nValue = parent::getOptionValueI18nString($optionValue);
//        return $i18nValue;
//    }

    protected function initOptions() {
        $options = $this->getOptionMetaData();
        if (!empty($options)) {
            foreach ($options as $key => $arr) {
                if (is_array($arr) && count($arr) > 1) {
                    $this->addOption($key, $arr[1]);
                }
            }
        }
    }

    public function getPluginDisplayName() {
        return 'WP Performance Tester';
    }

    protected function getMainPluginFileName() {
        return 'wp-performance-tester.php';
    }

    /**
     * See: http://plugin.michael-simpson.com/?page_id=101
     * Called by install() to create any database tables if needed.
     * Best Practice:
     * (1) Prefix all table names with $wpdb->prefix
     * (2) make table names lower case only
     * @return void
     */
    protected function installDatabaseTables() {
        //        global $wpdb;
        //        $tableName = $this->prefixTableName('mytable');
        //        $wpdb->query("CREATE TABLE IF NOT EXISTS `$tableName` (
        //            `id` INTEGER NOT NULL");
    }

    /**
     * See: http://plugin.michael-simpson.com/?page_id=101
     * Drop plugin-created tables on uninstall.
     * @return void
     */
    protected function unInstallDatabaseTables() {
        //        global $wpdb;
        //        $tableName = $this->prefixTableName('mytable');
        //        $wpdb->query("DROP TABLE IF EXISTS `$tableName`");
    }


    /**
     * Perform actions when upgrading from version X to version Y
     * See: http://plugin.michael-simpson.com/?page_id=35
     * @return void
     */
    public function upgrade() {
    }

    public function enqueue_scripts_and_style( $hook ) {
		if ( $hook != 'buddyboss_page_bb-upgrade' ) {
            return;
        }
        wp_enqueue_script( 'chart-js-3-7', plugins_url('/js/Chart.js', __FILE__) );
        wp_enqueue_script( 'jquery');
        wp_enqueue_style( 'wppt-style', plugins_url('/css/wppt.css', __FILE__) );
        wp_enqueue_style( 'simptip-style', plugins_url('/css/simptip.css', __FILE__) );
    }

    public function addActionsAndFilters() {

        // Add options administration page
        // http://plugin.michael-simpson.com/?page_id=47
        add_action('admin_menu', array( $this, 'addSettingsSubMenuPage'));
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts_and_style' ) );
    }
}
