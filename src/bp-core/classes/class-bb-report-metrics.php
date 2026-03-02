<?php
/**
 * Report Metrics Collection Class.
 *
 * @since   BuddyBoss 2.9.30
 * @package BuddyBoss\Core
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'BB_Report_Metrics' ) ) {

	/**
	 * BuddyBoss Report Metrics Collection object.
	 *
	 * @since BuddyBoss 2.9.30
	 */
	class BB_Report_Metrics {

		/**
		 * The single instance of the class.
		 *
		 * @since BuddyBoss 2.9.30
		 *
		 * @var self
		 */
		private static $instance = null;

		/**
		 * Global $wpdb object.
		 *
		 * @since BuddyBoss 2.9.30
		 *
		 * @var wpdb
		 */
		private static $wpdb = null;

		/**
		 * Cache for collected metrics.
		 *
		 * @since BuddyBoss 2.9.30
		 *
		 * @var array
		 */
		private static $metrics_cache = null;

		/**
		 * Supported plugins configuration.
		 *
		 * @since BuddyBoss 2.9.30
		 *
		 * @var array
		 */
		private static $supported_plugins = array(
			'learndash'           => array(
				'name'               => 'LearnDash LMS',
				'file'               => 'sfwd-lms/sfwd_lms.php',
				'post_type_func'     => 'get_learndash_post_type',
				'post_type_const'    => 'LEARNDASH_TRANSACTION_CPT',
				'post_type_fallback' => 'sfwd-transactions',
				'meta_key'           => 'order_total',
				'status'             => array( 'publish' ),
				'currency_key'       => 'learndash_settings_payments',
			),
			'memberpress'         => array(
				'name'       => 'MemberPress',
				'file'       => 'memberpress/memberpress.php',
				'table'      => 'mepr_transactions',
				'amount_col' => 'amount',
				'status'     => array( 'complete' ),
				'currency'   => 'USD',
			),
			'woocommerce'         => array(
				'name'               => 'WooCommerce',
				'file'               => 'woocommerce/woocommerce.php',
				'post_type_fallback' => 'shop_order',
				'meta_key'           => '_order_total',
				'status_func'        => 'wc_get_order_statuses',
				'status'             => array( 'wc-completed', 'wc-processing' ),
				'currency_func'      => 'get_woocommerce_currency',
			),
			'lifterlms'           => array(
				'name'               => 'LifterLMS',
				'file'               => 'lifterlms/lifterlms.php',
				'post_type_getter'   => array( 'LLMS_Post_Types', 'get_order_post_type' ),
				'post_type_fallback' => 'llms_order',
				'meta_key'           => '_llms_total',
				'status'             => array( 'llms-completed', 'llms-active' ),
				'currency_key'       => 'lifterlms_currency',
			),
			'tutor_lms'           => array(
				'name'               => 'Tutor LMS',
				'file'               => 'tutor/tutor.php',
				'post_type_fallback' => 'shop_order',
				'meta_key'           => '_order_total',
				'status'             => array( 'wc-completed', 'wc-processing' ),
				'currency_func'      => 'get_woocommerce_currency',
				'custom_where'       => 'tutor_order',
			),
			'pmpro'               => array(
				'name'         => 'Paid Memberships Pro',
				'file'         => 'paid-memberships-pro/paid-memberships-pro.php',
				'table'        => 'pmpro_membership_orders',
				'amount_col'   => 'total',
				'status'       => array( 'success' ),
				'currency_key' => 'pmpro_currency',
			),
			'affiliatewp'         => array(
				'name'          => 'AffiliateWP',
				'file'          => 'affiliate-wp/affiliate-wp.php',
				'table'         => 'affiliate_wp_referrals',
				'amount_col'    => 'amount',
				'status'        => array( 'unpaid' ),
				'currency_func' => 'affwp_get_currency',
			),
			'the_events_calendar' => array(
				'name'               => 'The Events Calendar',
				'file'               => 'the-events-calendar/the-events-calendar.php',
				'post_type_const'    => 'Tribe__Tickets__Commerce__PayPal__Orders::ORDER_POST_TYPE',
				'post_type_fallback' => 'tribe_tpp_orders',
				'meta_key'           => 'mc_gross',
				'status'             => array( 'publish' ),
				'currency_func'      => 'tribe_get_option',
				'currency_option'    => 'tribe_currency_code',
			),
		);

		/**
		 * Get the instance of this class.
		 *
		 * @since BuddyBoss 2.9.30
		 *
		 * @return BB_Report_Metrics|null
		 */
		public static function instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Constructor method.
		 *
		 * @since BuddyBoss 2.9.30
		 */
		public function __construct() {
			global $wpdb;
			self::$wpdb = $wpdb;
		}

		/**
		 * Initialize wpdb if not already set.
		 *
		 * @since BuddyBoss 2.9.30
		 */
		private static function init_wpdb() {
			if ( null === self::$wpdb ) {
				global $wpdb;
				self::$wpdb = $wpdb;
			}
		}

		/**
		 * Collect report metrics from all supported plugins.
		 *
		 * @since BuddyBoss 2.9.30
		 *
		 * @param bool $force_refresh Force refresh cache.
		 * @return array Array of report metrics from all plugins.
		 */
		public static function collect( $force_refresh = false ) {
			self::init_wpdb();

			// Try to get from WordPress cache first.
			$cache_key   = 'bb_report_metrics_data';
			$cached_data = wp_cache_get( $cache_key, 'bb_metrics' );

			if ( ! $force_refresh && false !== $cached_data ) {
				return $cached_data;
			}

			// Return memory cache if available and not forcing refresh.
			if ( ! $force_refresh && null !== self::$metrics_cache ) {
				return self::$metrics_cache;
			}

			$metrics = array();

			foreach ( self::$supported_plugins as $plugin_slug => $config ) {
				if ( self::is_plugin_active( $config['file'] ) ) {
					$plugin_metrics = self::get_plugin_metrics( $plugin_slug, $config );
					if ( ! empty( $plugin_metrics ) ) {
						$metrics[ $plugin_slug ] = $plugin_metrics;
					}
				}
			}

			// Cache the results.
			self::$metrics_cache = $metrics;

			// Cache in WordPress cache for 1 hour.
			wp_cache_set( $cache_key, $metrics, 'bb_metrics', HOUR_IN_SECONDS );

			return $metrics;
		}

		/**
		 * Check if a plugin is active.
		 *
		 * @since BuddyBoss 2.9.30
		 *
		 * @param string $plugin_file Plugin file path.
		 * @return bool
		 */
		private static function is_plugin_active( $plugin_file ) {
			return function_exists( 'is_plugin_active' ) && is_plugin_active( $plugin_file );
		}

		/**
		 * Get metrics for a specific plugin.
		 *
		 * @since BuddyBoss 2.9.30
		 *
		 * @param string $plugin_slug Plugin slug.
		 * @param array  $config      Plugin configuration.
		 * @return array|false Plugin metrics or false on failure.
		 */
		private static function get_plugin_metrics( $plugin_slug, $config ) {
			$metrics = array(
				'plugin'        => $plugin_slug,
				'plugin_name'   => $config['name'],
				'total_revenue' => 0,
				'currency'      => self::get_plugin_currency( $config ),
				'num_orders'    => 0,
				'period'        => 'all_time',
			);

			// Try to use native plugin methods first.
			$method_name = 'get_' . $plugin_slug . '_native_metrics';
			if ( method_exists( __CLASS__, $method_name ) ) {
				$data = self::$method_name();
			} elseif ( isset( $config['post_type'] ) ) {
				$data = self::get_post_type_metrics( $config );
			} elseif ( isset( $config['table'] ) ) {
				$data = self::get_table_metrics( $config );
			} else {
				return false;
			}

			if ( $data ) {
				$metrics['num_orders']    = (int) ( isset( $data->order_count ) ? $data->order_count : $data['order_count'] );
				$metrics['total_revenue'] = (float) ( isset( $data->total_revenue ) ? $data->total_revenue : $data['total_revenue'] );
			}

			return $metrics;
		}

		/**
		 * Get dynamic post type for a plugin.
		 *
		 * @since BuddyBoss 2.9.30
		 *
		 * @param array $config Plugin configuration.
		 * @return string Post type.
		 */
		private static function get_dynamic_post_type( $config ) {
			// Try function first.
			if ( isset( $config['post_type_func'] ) && function_exists( $config['post_type_func'] ) ) {
				$post_type = call_user_func( $config['post_type_func'] );
				if ( ! empty( $post_type ) ) {
					return $post_type;
				}
			}

			// Try class method.
			if ( isset( $config['post_type_getter'] ) && is_array( $config['post_type_getter'] ) ) {
				if ( class_exists( $config['post_type_getter'][0] ) && method_exists( $config['post_type_getter'][0], $config['post_type_getter'][1] ) ) {
					$post_type = call_user_func( $config['post_type_getter'] );
					if ( ! empty( $post_type ) ) {
						return $post_type;
					}
				}
			}

			// Try constant.
			if ( isset( $config['post_type_const'] ) ) {
				// Handle class constants.
				if ( strpos( $config['post_type_const'], '::' ) !== false ) {
					$parts = explode( '::', $config['post_type_const'] );
					if ( class_exists( $parts[0] ) && defined( $config['post_type_const'] ) ) {
						return constant( $config['post_type_const'] );
					}
				} elseif ( defined( $config['post_type_const'] ) ) {
					return constant( $config['post_type_const'] );
				}
			}

			// Fallback to static value.
			return isset( $config['post_type_fallback'] ) ? $config['post_type_fallback'] : '';
		}

		/**
		 * Get currency for a plugin.
		 *
		 * @since BuddyBoss 2.9.30
		 *
		 * @param array $config Plugin configuration.
		 * @return string Currency code.
		 */
		private static function get_plugin_currency( $config ) {
			if ( isset( $config['currency'] ) ) {
				return $config['currency'];
			}

			if ( isset( $config['currency_func'] ) && function_exists( $config['currency_func'] ) ) {
				if ( isset( $config['currency_option'] ) ) {
					return call_user_func( $config['currency_func'], $config['currency_option'], 'USD' );
				}
				return call_user_func( $config['currency_func'] );
			}

			if ( isset( $config['currency_key'] ) ) {
				$settings = get_option( $config['currency_key'], array() );
				if ( is_array( $settings ) && isset( $settings['currency'] ) ) {
					return $settings['currency'];
				}
				return get_option( $config['currency_key'], 'USD' );
			}

			return 'USD';
		}

		/**
		 * Get metrics from post types (LearnDash, WooCommerce, LifterLMS, Tutor LMS).
		 *
		 * @since BuddyBoss 2.9.30
		 *
		 * @param array $config Plugin configuration.
		 * @return object|false Database result or false on failure.
		 */
		private static function get_post_type_metrics( $config ) {
			try {
				// Get dynamic post type.
				$post_type = self::get_dynamic_post_type( $config );
				if ( empty( $post_type ) ) {
					return false;
				}

				$status_placeholders = implode( ',', array_fill( 0, count( $config['status'] ), '%s' ) );

				// Build the base query.
				$query_parts = array(
					'SELECT COUNT(*) as order_count, SUM(CAST(meta_value AS DECIMAL(10,2))) as total_revenue',
					'FROM ' . self::$wpdb->posts . ' p',
					'LEFT JOIN ' . self::$wpdb->postmeta . ' pm ON p.ID = pm.post_id',
					'WHERE p.post_type = %s',
					"AND p.post_status IN ({$status_placeholders})",
					'AND pm.meta_key = %s',
					'AND pm.meta_value IS NOT NULL',
					'AND pm.meta_value != \'\'',
					'AND pm.meta_value != \'0\'',
				);

				// Add custom WHERE clause for Tutor LMS.
				if ( isset( $config['custom_where'] ) && 'tutor_order' === $config['custom_where'] ) {
					$query_parts[] = 'AND EXISTS (
						SELECT 1 FROM ' . self::$wpdb->postmeta . ' pm2
						WHERE pm2.post_id = p.ID
						AND pm2.meta_key = \'_is_tutor_order_for_course\'
					)';
				}

				$query = self::$wpdb->prepare(
					implode( ' ', $query_parts ),
					array_merge( array( $post_type ), $config['status'], array( $config['meta_key'] ) )
				);

				$result = self::$wpdb->get_row( $query );

				// Validate result.
				if ( $result && ( $result->order_count > 0 || $result->total_revenue > 0 ) ) {
					return $result;
				}

				return false;
			} catch ( \Exception $e ) {
				// Silently return false on error.
				return false;
			}
		}

		/**
		 * Get metrics from custom tables (MemberPress, PMPro, AffiliateWP).
		 *
		 * @since BuddyBoss 2.9.30
		 *
		 * @param array $config Plugin configuration.
		 * @return object|false Database result or false on failure.
		 */
		private static function get_table_metrics( $config ) {
			try {
				// Special handling for MemberPress.
				if ( 'mepr_transactions' === $config['table'] && ! class_exists( 'MeprTransaction' ) ) {
					return false;
				}

				// Check if table exists.
				$table_name   = self::$wpdb->prefix . $config['table'];
				$table_exists = self::$wpdb->get_var( self::$wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) );
				if ( ! $table_exists ) {
					return false;
				}

				$status_placeholders = implode( ',', array_fill( 0, count( $config['status'] ), '%s' ) );

				// Build safe query with proper placeholders.
				$amount_col = esc_sql( $config['amount_col'] );
				$query = self::$wpdb->prepare(
					"SELECT COUNT(*) as order_count, SUM(CAST($amount_col AS DECIMAL(10,2))) as total_revenue
					FROM %i
					WHERE status IN (" . $status_placeholders . ")
					AND $amount_col IS NOT NULL
					AND $amount_col > 0",
					array_merge( array( $table_name ), $config['status'] )
				);

				$result = self::$wpdb->get_row( $query ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared

				// Validate result.
				if ( $result && ( $result->order_count > 0 || $result->total_revenue > 0 ) ) {
					return $result;
				}

				return false;
			} catch ( \Exception $e ) {
				// Silently return false on error.
				return false;
			}
		}

		/**
		 * Clear metrics cache.
		 *
		 * @since BuddyBoss 2.9.30
		 */
		public static function clear_cache() {
			self::$metrics_cache = null;
			wp_cache_delete( 'bb_report_metrics_data', 'bb_metrics' );
		}

		/**
		 * Get cache status.
		 *
		 * @since BuddyBoss 2.9.30
		 *
		 * @return bool True if cache is available.
		 */
		public static function is_cached() {
			return null !== self::$metrics_cache;
		}

		/**
		 * Get WooCommerce metrics using native methods.
		 *
		 * @since BuddyBoss 2.9.30
		 *
		 * @return array|false
		 */
		private static function get_woocommerce_native_metrics() {
			if ( ! function_exists( 'WC' ) ) {
				return false;
			}

			try {
				// Check if WooCommerce is using HPOS (High Performance Order Storage).
				$using_hpos = false;
				if ( class_exists( 'Automattic\WooCommerce\Utilities\OrderUtil' ) && 
				     method_exists( 'Automattic\WooCommerce\Utilities\OrderUtil', 'custom_orders_table_usage_is_enabled' ) ) {
					$using_hpos = \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
				}

				// Get order statuses.
				$order_statuses = array( 'wc-completed', 'wc-processing' );
				$config = self::$supported_plugins['woocommerce'];
				if ( isset( $config['status_func'] ) && function_exists( $config['status_func'] ) ) {
					$all_statuses = call_user_func( $config['status_func'] );
					// Filter to completed and processing only.
					$order_statuses = array_intersect( array( 'wc-completed', 'wc-processing' ), array_keys( $all_statuses ) );
				}

				if ( $using_hpos ) {
					// Use HPOS tables directly for better performance.
					$order_table      = self::$wpdb->prefix . 'wc_orders';
					$order_meta_table = self::$wpdb->prefix . 'wc_order_meta';
					
					// Check if HPOS tables exist.
					$table_exists = self::$wpdb->get_var( self::$wpdb->prepare( 'SHOW TABLES LIKE %s', $order_table ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
					if ( ! $table_exists ) {
						// Fallback to legacy method if tables don't exist.
						return self::get_woocommerce_legacy_metrics( $order_statuses );
					}

					// HPOS stores statuses WITH the 'wc-' prefix, so we use them as-is.
					$status_placeholders = implode( ',', array_fill( 0, count( $order_statuses ), '%s' ) );

					// Build the query with proper placeholders.
					$query = 'SELECT COUNT(DISTINCT o.id) as order_count,
							 SUM(o.total_amount) as total_revenue
							 FROM ' . esc_sql( $order_table ) . ' o
							 WHERE o.type = %s
							 AND o.status IN (' . $status_placeholders . ')
							 AND o.total_amount > 0';

					// Prepare the query arguments.
					$query_args = array_merge( array( 'shop_order' ), $order_statuses );

					$results = self::$wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
						self::$wpdb->prepare( $query, $query_args )
					);

					if ( $results ) {
						return array(
							'order_count'   => (int) $results->order_count,
							'total_revenue' => (float) $results->total_revenue,
						);
					}
				} else {
					// Use legacy post-based storage.
					return self::get_woocommerce_legacy_metrics( $order_statuses );
				}

				return false;
			} catch ( Exception $e ) {
				// Silently return false on error.
				return false;
			}
		}

		/**
		 * Get WooCommerce metrics using legacy post storage.
		 *
		 * @since BuddyBoss 2.9.30
		 *
		 * @param array $order_statuses Order statuses to query.
		 * @return array|false
		 */
		private static function get_woocommerce_legacy_metrics( $order_statuses ) {
			$placeholders = array_fill( 0, count( $order_statuses ), '%s' );

			// Query both shop_order and shop_order_placehold post types.
			$post_types = array( 'shop_order' );

			// Check if placeholder post type exists (used during HPOS migration).
			if ( post_type_exists( 'shop_order_placehold' ) ) {
				$post_types[] = 'shop_order_placehold';
			}

			$type_placeholders = implode( ',', array_fill( 0, count( $post_types ), '%s' ) );

			// Build query with proper placeholders.
			$query = '
				SELECT COUNT(DISTINCT p.ID) as order_count,
				       SUM(CAST(pm.meta_value AS DECIMAL(10,2))) as total_revenue
				FROM ' . self::$wpdb->posts . ' p
				INNER JOIN ' . self::$wpdb->postmeta . ' pm ON p.ID = pm.post_id
				LEFT JOIN ' . self::$wpdb->posts . ' p2 ON p.ID = p2.post_parent AND p2.post_type = %s
				WHERE p.post_type IN (' . $type_placeholders . ')
				AND p.post_status IN (' . implode( ',', $placeholders ) . ')
				AND pm.meta_key = %s
				AND pm.meta_value > 0
				AND p2.ID IS NULL
			';

			// Prepare query arguments.
			$query_args = array_merge(
				array( 'shop_order_refund' ),
				$post_types,
				$order_statuses,
				array( '_order_total' )
			);

			$results = self::$wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
				self::$wpdb->prepare( $query, $query_args )
			);

			if ( $results ) {
				return array(
					'order_count'   => (int) $results->order_count,
					'total_revenue' => (float) $results->total_revenue,
				);
			}

			return false;
		}

		/**
		 * Get LearnDash metrics using native methods.
		 *
		 * @since BuddyBoss 2.9.30
		 *
		 * @return array|false
		 */
		private static function get_learndash_native_metrics() {
			if ( ! defined( 'LEARNDASH_VERSION' ) ) {
				return false;
			}

			try {
				// Get dynamic configuration.
				$config    = self::$supported_plugins['learndash'];
				$post_type = self::get_dynamic_post_type( $config );
				$meta_key  = $config['meta_key'];
				$statuses  = $config['status'];

				// LearnDash uses custom post type for transactions.
				$query = '
					SELECT COUNT(*) as order_count,
					       SUM(CAST(pm.meta_value AS DECIMAL(10,2))) as total_revenue
					FROM ' . self::$wpdb->posts . ' p
					INNER JOIN ' . self::$wpdb->postmeta . ' pm ON p.ID = pm.post_id
					WHERE p.post_type = %s
					AND p.post_status = %s
					AND pm.meta_key = %s
					AND pm.meta_value > 0
				';

				$results = self::$wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
					self::$wpdb->prepare( $query, $post_type, $statuses[0], $meta_key )
				);

				if ( $results ) {
					return array(
						'order_count'   => (int) $results->order_count,
						'total_revenue' => (float) $results->total_revenue,
					);
				}

				return false;
			} catch ( Exception $e ) {
				// Silently return false on error.
				return false;
			}
		}

		/**
		 * Get MemberPress metrics using native methods.
		 *
		 * @since BuddyBoss 2.9.30
		 *
		 * @return array|false
		 */
		private static function get_memberpress_native_metrics() {
			if ( ! defined( 'MEPR_VERSION' ) || ! class_exists( 'MeprTransaction' ) ) {
				return false;
			}

			try {
				$table = self::$wpdb->prefix . 'mepr_transactions';

				// Check if table exists.
				$table_check = self::$wpdb->get_var( self::$wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				if ( $table_check !== $table ) {
					return false;
				}

				// Use direct query for efficiency.
				$results = self::$wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					self::$wpdb->prepare(
						'SELECT COUNT(*) as order_count, SUM(amount) as total_revenue
						FROM %i
						WHERE status = %s
						AND amount > 0',
						$table,
						'complete'
					)
				);

				if ( $results ) {
					return array(
						'order_count'   => (int) $results->order_count,
						'total_revenue' => (float) $results->total_revenue,
					);
				}

				return false;
			} catch ( Exception $e ) {
				// Silently return false on error.
				return false;
			}
		}

		/**
		 * Get LifterLMS metrics using native methods.
		 *
		 * @since BuddyBoss 2.9.30
		 *
		 * @return array|false
		 */
		private static function get_lifterlms_native_metrics() {
			if ( ! function_exists( 'llms' ) || ! class_exists( 'LLMS_Order' ) ) {
				return false;
			}

			try {
				// Get dynamic configuration.
				$config              = self::$supported_plugins['lifterlms'];
				$post_type           = self::get_dynamic_post_type( $config );
				$meta_key            = $config['meta_key'];
				$statuses            = $config['status'];
				$status_placeholders = implode( ',', array_fill( 0, count( $statuses ), '%s' ) );

				// LifterLMS stores order data in postmeta.
				$query = '
					SELECT COUNT(DISTINCT p.ID) as order_count,
					       SUM(CAST(pm.meta_value AS DECIMAL(10,2))) as total_revenue
					FROM ' . self::$wpdb->posts . ' p
					INNER JOIN ' . self::$wpdb->postmeta . ' pm ON p.ID = pm.post_id
					WHERE p.post_type = %s
					AND p.post_status IN (' . $status_placeholders . ')
					AND pm.meta_key = %s
					AND pm.meta_value > 0
				';

				$query_args = array_merge( array( $post_type ), $statuses, array( $meta_key ) );

				$results = self::$wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
					self::$wpdb->prepare( $query, $query_args )
				);

				if ( $results ) {
					return array(
						'order_count'   => (int) $results->order_count,
						'total_revenue' => (float) $results->total_revenue,
					);
				}

				return false;
			} catch ( Exception $e ) {
				// Silently return false on error.
				return false;
			}
		}

		/**
		 * Get Tutor LMS metrics using native methods.
		 *
		 * @since BuddyBoss 2.9.30
		 *
		 * @return array|false
		 */
		private static function get_tutor_lms_native_metrics() {
			if ( ! function_exists( 'tutor' ) || ! function_exists( 'WC' ) ) {
				return false;
			}

			try {
				// Get dynamic configuration.
				$config              = self::$supported_plugins['tutor_lms'];
				$post_type           = self::get_dynamic_post_type( $config );
				$meta_key            = $config['meta_key'];
				$statuses            = $config['status'];
				$status_placeholders = implode( ',', array_fill( 0, count( $statuses ), '%s' ) );

				// Tutor uses WooCommerce orders with special meta.
				$results = self::$wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					self::$wpdb->prepare(
						'
					SELECT COUNT(DISTINCT p.ID) as order_count,
					       SUM(CAST(pm_total.meta_value AS DECIMAL(10,2))) as total_revenue
					FROM ' . self::$wpdb->posts . ' p
					INNER JOIN ' . self::$wpdb->postmeta . ' pm_tutor ON p.ID = pm_tutor.post_id
					INNER JOIN ' . self::$wpdb->postmeta . " pm_total ON p.ID = pm_total.post_id
					WHERE p.post_type = %s
					AND p.post_status IN (" . $status_placeholders . ")
					AND pm_tutor.meta_key = '_is_tutor_order_for_course'
					AND pm_tutor.meta_value = 'yes'
					AND pm_total.meta_key = %s
					AND pm_total.meta_value > 0
				",
						array_merge( array( $post_type ), $statuses, array( $meta_key ) )
					)
				);

				if ( $results ) {
					return array(
						'order_count'   => (int) $results->order_count,
						'total_revenue' => (float) $results->total_revenue,
					);
				}

				return false;
			} catch ( Exception $e ) {
				// Silently return false on error.
				return false;
			}
		}

		/**
		 * Get Paid Memberships Pro metrics using native methods.
		 *
		 * @since BuddyBoss 2.9.30
		 *
		 * @return array|false
		 */
		private static function get_pmpro_native_metrics() {
			if ( ! defined( 'PMPRO_VERSION' ) ) {
				return false;
			}

			try {
				$table = self::$wpdb->prefix . 'pmpro_membership_orders';

				// Check if table exists.
				$table_check = self::$wpdb->get_var( self::$wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				if ( $table_check !== $table ) {
					return false;
				}

				// Use direct query.
				$results = self::$wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					self::$wpdb->prepare(
						"SELECT COUNT(*) as order_count, SUM(total) as total_revenue
						FROM %i
						WHERE status = %s
						AND total > 0",
						$table,
						'success'
					)
				);

				if ( $results ) {
					return array(
						'order_count'   => (int) $results->order_count,
						'total_revenue' => (float) $results->total_revenue,
					);
				}

				return false;
			} catch ( Exception $e ) {
				// Silently return false on error.
				return false;
			}
		}

		/**
		 * Get AffiliateWP metrics using native methods.
		 *
		 * @since BuddyBoss 2.9.30
		 *
		 * @return array|false
		 */
		private static function get_affiliatewp_native_metrics() {
			if ( ! function_exists( 'affiliate_wp' ) ) {
				return false;
			}

			try {
				$affiliatewp = affiliate_wp();
				if ( ! isset( $affiliatewp->referrals ) ) {
					return false;
				}

				// Use AffiliateWP's database class.
				$table = $affiliatewp->referrals->table_name;

				$results = self::$wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					self::$wpdb->prepare(
						"SELECT COUNT(*) as order_count, SUM(amount) as total_revenue
						FROM %i
						WHERE status = %s
						AND amount > 0",
						$table,
						'unpaid'
					)
				);

				if ( $results ) {
					return array(
						'order_count'   => (int) $results->order_count,
						'total_revenue' => (float) $results->total_revenue,
					);
				}

				return false;
			} catch ( Exception $e ) {
				// Silently return false on error.
				return false;
			}
		}

		/**
		 * Get The Events Calendar metrics using native methods.
		 *
		 * @since BuddyBoss 2.9.30
		 *
		 * @return array|false
		 */
		private static function get_the_events_calendar_native_metrics() {
			if ( ! class_exists( 'Tribe__Events__Main' ) ) {
				return false;
			}

			try {
				// Get dynamic configuration.
				$config    = self::$supported_plugins['the_events_calendar'];
				$post_type = self::get_dynamic_post_type( $config );
				$meta_key  = $config['meta_key'];
				$statuses  = $config['status'];

				// The Events Calendar uses custom post type for orders.
				$results = self::$wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					self::$wpdb->prepare(
						'
					SELECT COUNT(*) as order_count,
					       SUM(CAST(pm.meta_value AS DECIMAL(10,2))) as total_revenue
					FROM ' . self::$wpdb->posts . ' p
					INNER JOIN ' . self::$wpdb->postmeta . ' pm ON p.ID = pm.post_id
					WHERE p.post_type = %s
					AND p.post_status = %s
					AND pm.meta_key = %s
					AND pm.meta_value > 0
				',
						$post_type,
						$statuses[0],
						$meta_key
					)
				);

				if ( $results ) {
					return array(
						'order_count'   => (int) $results->order_count,
						'total_revenue' => (float) $results->total_revenue,
					);
				}

				return false;
			} catch ( Exception $e ) {
				// Silently return false on error.
				return false;
			}
		}
	}
}
