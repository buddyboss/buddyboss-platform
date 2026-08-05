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
		 * Reported when a plugin's currency cannot be resolved.
		 *
		 * Deliberately not 'USD'. Attributing unknown revenue to USD inflated the
		 * USD bucket with every store whose currency could not be read.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @var string
		 */
		const UNKNOWN_CURRENCY = 'unknown';

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
				// `learndash_settings_payments` does not exist; the currency lives
				// in the payments defaults, with the PayPal settings as a fallback
				// for sites still on the older gateway configuration.
				'currency_key'       => 'learndash_settings_payments_defaults',
				'currency_index'     => 'currency',
				'currency_key_alt'   => 'learndash_settings_paypal',
				'currency_index_alt' => 'paypal_currency',
			),
			'memberpress'         => array(
				'name'           => 'MemberPress',
				'file'           => 'memberpress/memberpress.php',
				'table'          => 'mepr_transactions',
				'amount_col'     => 'amount',
				'status'         => array( 'complete' ),
				// Was hardcoded to USD, which mislabelled every non-US store.
				'currency_key'   => 'mepr_options',
				'currency_index' => 'currency_code',
			),
			'woocommerce'         => array(
				'name'               => 'WooCommerce',
				'file'               => 'woocommerce/woocommerce.php',
				'post_type_fallback' => 'shop_order',
				'meta_key'           => '_order_total',
				'status_func'        => 'wc_get_order_statuses',
				'status'             => array( 'wc-completed', 'wc-processing' ),
				'currency_func'      => 'get_woocommerce_currency',
				'currency_key'       => 'woocommerce_currency',
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
				'currency_key'       => 'woocommerce_currency',
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
				'currency_key'  => 'affwp_settings',
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
			} elseif ( isset( $config['table'] ) ) {
				$data = self::get_table_metrics( $config );
			} elseif ( self::has_post_type_config( $config ) ) {
				/*
				 * Previously gated on `isset( $config['post_type'] )`, a key no
				 * config defines — they all use post_type_fallback/_func/_getter/
				 * _const. That made this branch unreachable and left The Events
				 * Calendar, the only plugin relying on it, permanently at zero.
				 */
				$data = self::get_post_type_metrics( $config );
			} else {
				return false;
			}

			if ( $data ) {
				$metrics['num_orders']    = (int) ( isset( $data->order_count ) ? $data->order_count : $data['order_count'] );
				$metrics['total_revenue'] = (float) ( isset( $data->total_revenue ) ? $data->total_revenue : $data['total_revenue'] );

				// Per-currency breakdown, when the source could provide one.
				$by_currency = isset( $data['by_currency'] ) ? $data['by_currency'] : array();
				if ( ! empty( $by_currency ) ) {
					$metrics['by_currency'] = $by_currency;

					// More than one currency means a single total is meaningless.
					$metrics['mixed_currency'] = count( $by_currency ) > 1;

					// Attribute the headline figure to the largest bucket rather
					// than to the store's base currency.
					$largest = '';
					$highest = null;
					foreach ( $by_currency as $code => $bucket ) {
						$amount = isset( $bucket['revenue'] ) ? (float) $bucket['revenue'] : 0;
						if ( null === $highest || $amount > $highest ) {
							$highest = $amount;
							$largest = $code;
						}
					}

					if ( '' !== $largest ) {
						$metrics['currency'] = $largest;
					}
				}
			}

			return $metrics;
		}

		/**
		 * Whether a config can resolve a post type.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param array $config Plugin configuration.
		 *
		 * @return bool
		 */
		private static function has_post_type_config( $config ) {
			foreach ( array( 'post_type', 'post_type_func', 'post_type_getter', 'post_type_const', 'post_type_fallback' ) as $key ) {
				if ( ! empty( $config[ $key ] ) ) {
					return true;
				}
			}

			return false;
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
			// Explicit override, when a plugin genuinely has a fixed currency.
			if ( ! empty( $config['currency'] ) ) {
				return self::normalize_currency( $config['currency'] );
			}

			// The plugin's own accessor is the most reliable source, but only
			// when the plugin is loaded — on a deactivated plugin it does not
			// exist and we fall through to the stored option below.
			if ( isset( $config['currency_func'] ) && function_exists( $config['currency_func'] ) ) {
				$currency = isset( $config['currency_option'] )
					? call_user_func( $config['currency_func'], $config['currency_option'], '' )
					: call_user_func( $config['currency_func'] );

				$currency = self::normalize_currency( $currency );
				if ( '' !== $currency ) {
					return $currency;
				}
			}

			// Stored option, either a plain string or a settings array.
			$currency = self::get_currency_from_option(
				isset( $config['currency_key'] ) ? $config['currency_key'] : '',
				isset( $config['currency_index'] ) ? $config['currency_index'] : 'currency'
			);

			if ( '' !== $currency ) {
				return $currency;
			}

			// Secondary option, for plugins that moved their settings between versions.
			$currency = self::get_currency_from_option(
				isset( $config['currency_key_alt'] ) ? $config['currency_key_alt'] : '',
				isset( $config['currency_index_alt'] ) ? $config['currency_index_alt'] : 'currency'
			);

			if ( '' !== $currency ) {
				return $currency;
			}

			// Nothing resolved. Reported explicitly rather than as a fabricated
			// USD, so aggregation can exclude it instead of skewing the USD total.
			return self::UNKNOWN_CURRENCY;
		}

		/**
		 * Read a currency code out of a stored option.
		 *
		 * Handles both shapes in use: a plain string option such as
		 * `woocommerce_currency`, and a settings array such as `mepr_options`
		 * where the code sits under a named index.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param string $option_name Option to read.
		 * @param string $index       Index to look for when the option is an array.
		 *
		 * @return string Currency code, or an empty string when unresolved.
		 */
		private static function get_currency_from_option( $option_name, $index = 'currency' ) {
			if ( empty( $option_name ) ) {
				return '';
			}

			$value = get_option( $option_name, '' );

			if ( is_array( $value ) ) {
				$value = isset( $value[ $index ] ) ? $value[ $index ] : '';
			} elseif ( is_object( $value ) ) {
				// MemberPress stores mepr_options as an object on some versions.
				$value = isset( $value->{$index} ) ? $value->{$index} : '';
			}

			return self::normalize_currency( $value );
		}

		/**
		 * Normalise a currency value to an ISO-4217-shaped code.
		 *
		 * Guards against the non-string values the previous implementation could
		 * return — arrays from settings options, and null when a configured
		 * `currency_func` did not exist.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param mixed $currency Raw currency value.
		 *
		 * @return string Three-letter code, or an empty string when unusable.
		 */
		private static function normalize_currency( $currency ) {
			if ( ! is_string( $currency ) ) {
				return '';
			}

			$currency = strtoupper( trim( $currency ) );

			return preg_match( '/^[A-Z]{3}$/', $currency ) ? $currency : '';
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
					'SELECT COUNT(*) as order_count, SUM(CAST(meta_value AS DECIMAL(18,2))) as total_revenue',
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
					"SELECT COUNT(*) as order_count, SUM(CAST($amount_col AS DECIMAL(18,2))) as total_revenue
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
		 * Get WooCommerce metrics, per currency and net of refunds.
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
				// Resolve the statuses that count as revenue.
				$order_statuses = array( 'wc-completed', 'wc-processing' );
				$config         = self::$supported_plugins['woocommerce'];
				if ( isset( $config['status_func'] ) && function_exists( $config['status_func'] ) ) {
					$all_statuses   = call_user_func( $config['status_func'] );
					$order_statuses = array_values( array_intersect( $order_statuses, array_keys( $all_statuses ) ) );
				}

				if ( empty( $order_statuses ) ) {
					return false;
				}

				$using_hpos = false;
				if (
					class_exists( 'Automattic\\WooCommerce\\Utilities\\OrderUtil' ) &&
					method_exists( 'Automattic\\WooCommerce\\Utilities\\OrderUtil', 'custom_orders_table_usage_is_enabled' )
				) {
					$using_hpos = \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
				}

				if ( $using_hpos ) {
					$order_table  = self::$wpdb->prefix . 'wc_orders';
					$table_exists = self::$wpdb->get_var( self::$wpdb->prepare( 'SHOW TABLES LIKE %s', $order_table ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
					if ( $table_exists === $order_table ) {
						return self::get_woocommerce_hpos_metrics( $order_statuses, $order_table );
					}
				}

				return self::get_woocommerce_legacy_metrics( $order_statuses );
			} catch ( \Exception $e ) {
				return false;
			}
		}

		/**
		 * WooCommerce metrics from the HPOS order tables.
		 *
		 * Groups by the order's own currency rather than reporting everything in
		 * the store's base currency, and subtracts refunds instead of discarding
		 * refunded orders.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param array  $order_statuses Statuses that count as revenue.
		 * @param string $order_table    Fully qualified HPOS orders table.
		 *
		 * @return array|false
		 */
		private static function get_woocommerce_hpos_metrics( $order_statuses, $order_table ) {
			$status_placeholders = implode( ',', array_fill( 0, count( $order_statuses ), '%s' ) );

			// Gross revenue per currency.
			$gross_sql = "SELECT o.currency AS currency, COUNT( DISTINCT o.id ) AS order_count, SUM( o.total_amount ) AS revenue
				FROM {$order_table} o
				WHERE o.type = %s
				AND o.status IN ( {$status_placeholders} )
				AND o.total_amount > 0
				GROUP BY o.currency";

			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Table name from wpdb; values parameterized.
			$gross = self::$wpdb->get_results( self::$wpdb->prepare( $gross_sql, array_merge( array( 'shop_order' ), $order_statuses ) ) );

			// Refunds against those same orders. Stored negative by WooCommerce,
			// so ABS() keeps this correct whichever sign convention applies.
			$refund_sql = "SELECT r.currency AS currency, SUM( ABS( r.total_amount ) ) AS refunded
				FROM {$order_table} r
				INNER JOIN {$order_table} o ON o.id = r.parent_order_id
				WHERE r.type = %s
				AND o.type = %s
				AND o.status IN ( {$status_placeholders} )
				GROUP BY r.currency";

			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Table name from wpdb; values parameterized.
			$refunds = self::$wpdb->get_results( self::$wpdb->prepare( $refund_sql, array_merge( array( 'shop_order_refund', 'shop_order' ), $order_statuses ) ) );

			return self::combine_currency_rows( $gross, $refunds );
		}

		/**
		 * WooCommerce metrics from legacy post storage.
		 *
		 * @since BuddyBoss 2.9.30
		 *
		 * @param array $order_statuses Statuses that count as revenue.
		 *
		 * @return array|false
		 */
		private static function get_woocommerce_legacy_metrics( $order_statuses ) {
			$post_types = array( 'shop_order' );

			// Present during an HPOS migration.
			if ( post_type_exists( 'shop_order_placehold' ) ) {
				$post_types[] = 'shop_order_placehold';
			}

			$type_placeholders   = implode( ',', array_fill( 0, count( $post_types ), '%s' ) );
			$status_placeholders = implode( ',', array_fill( 0, count( $order_statuses ), '%s' ) );

			/*
			 * Gross revenue grouped by each order's own currency.
			 *
			 * The previous query excluded any order that had a refund child
			 * (`p2.ID IS NULL`), so a single partial refund erased that order's
			 * entire revenue. Refunds are now subtracted below instead.
			 */
			$gross_sql = 'SELECT COALESCE( NULLIF( cur.meta_value, \'\' ), %s ) AS currency,
					COUNT( DISTINCT p.ID ) AS order_count,
					SUM( CAST( tot.meta_value AS DECIMAL(18,2) ) ) AS revenue
				FROM ' . self::$wpdb->posts . ' p
				INNER JOIN ' . self::$wpdb->postmeta . ' tot ON p.ID = tot.post_id AND tot.meta_key = %s
				LEFT JOIN ' . self::$wpdb->postmeta . " cur ON p.ID = cur.post_id AND cur.meta_key = %s
				WHERE p.post_type IN ( {$type_placeholders} )
				AND p.post_status IN ( {$status_placeholders} )
				AND tot.meta_value > 0
				GROUP BY currency";

			$gross_args = array_merge(
				array( self::UNKNOWN_CURRENCY, '_order_total', '_order_currency' ),
				$post_types,
				$order_statuses
			);

			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Table names from wpdb; values parameterized.
			$gross = self::$wpdb->get_results( self::$wpdb->prepare( $gross_sql, $gross_args ) );

			// Refunds attached to those orders.
			$refund_sql = 'SELECT COALESCE( NULLIF( cur.meta_value, \'\' ), %s ) AS currency,
					SUM( ABS( CAST( tot.meta_value AS DECIMAL(18,2) ) ) ) AS refunded
				FROM ' . self::$wpdb->posts . ' r
				INNER JOIN ' . self::$wpdb->posts . ' p ON p.ID = r.post_parent
				INNER JOIN ' . self::$wpdb->postmeta . ' tot ON r.ID = tot.post_id AND tot.meta_key = %s
				LEFT JOIN ' . self::$wpdb->postmeta . " cur ON p.ID = cur.post_id AND cur.meta_key = %s
				WHERE r.post_type = %s
				AND p.post_type IN ( {$type_placeholders} )
				AND p.post_status IN ( {$status_placeholders} )
				GROUP BY currency";

			$refund_args = array_merge(
				array( self::UNKNOWN_CURRENCY, '_order_total', '_order_currency', 'shop_order_refund' ),
				$post_types,
				$order_statuses
			);

			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Table names from wpdb; values parameterized.
			$refunds = self::$wpdb->get_results( self::$wpdb->prepare( $refund_sql, $refund_args ) );

			return self::combine_currency_rows( $gross, $refunds );
		}

		/**
		 * Net gross rows against refund rows into a per-currency breakdown.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param array $gross_rows  Rows of currency, order_count, revenue.
		 * @param array $refund_rows Rows of currency, refunded.
		 *
		 * @return array|false Order count, total revenue and a by_currency map.
		 */
		private static function combine_currency_rows( $gross_rows, $refund_rows ) {
			if ( empty( $gross_rows ) ) {
				return false;
			}

			$refund_by_currency = array();
			foreach ( (array) $refund_rows as $row ) {
				$code                        = self::currency_or_unknown( $row->currency );
				$refund_by_currency[ $code ] = ( isset( $refund_by_currency[ $code ] ) ? $refund_by_currency[ $code ] : 0 ) + (float) $row->refunded;
			}

			$by_currency   = array();
			$total_orders  = 0;
			$total_revenue = 0;

			foreach ( (array) $gross_rows as $row ) {
				$code    = self::currency_or_unknown( $row->currency );
				$orders  = (int) $row->order_count;
				$revenue = (float) $row->revenue - ( isset( $refund_by_currency[ $code ] ) ? $refund_by_currency[ $code ] : 0 );

				// A refund larger than recorded revenue would otherwise report negative.
				$revenue = max( 0, $revenue );

				if ( isset( $by_currency[ $code ] ) ) {
					$by_currency[ $code ]['orders']  += $orders;
					$by_currency[ $code ]['revenue'] += $revenue;
				} else {
					$by_currency[ $code ] = array(
						'orders'  => $orders,
						'revenue' => $revenue,
					);
				}

				$total_orders  += $orders;
				$total_revenue += $revenue;
			}

			if ( 0 === $total_orders && 0.0 === (float) $total_revenue ) {
				return false;
			}

			return array(
				'order_count'   => $total_orders,
				'total_revenue' => $total_revenue,
				'by_currency'   => $by_currency,
			);
		}

		/**
		 * Normalise a currency code coming back from a query row.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param mixed $currency Raw value.
		 *
		 * @return string Currency code or the unknown marker.
		 */
		private static function currency_or_unknown( $currency ) {
			$code = self::normalize_currency( $currency );

			return '' === $code ? self::UNKNOWN_CURRENCY : $code;
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
					       SUM(CAST(pm.meta_value AS DECIMAL(18,2))) as total_revenue
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
				$table_check = self::$wpdb->get_var( self::$wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
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
					       SUM(CAST(pm.meta_value AS DECIMAL(18,2))) as total_revenue
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
		 * Get Tutor LMS metrics from its own order table.
		 *
		 * Tutor 3.x introduced native monetisation with a `tutor_orders` table.
		 * `net_payment` is the refund-adjusted figure, so it is summed in
		 * preference to `total_price`.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return array|false Metrics, or false when the table is absent or empty.
		 */
		private static function get_tutor_lms_order_table_metrics() {
			$table  = self::$wpdb->prefix . 'tutor_orders';
			$exists = self::$wpdb->get_var( self::$wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared

			if ( $exists !== $table ) {
				return false;
			}

			$sql = "SELECT COUNT(*) AS order_count, SUM( CAST( net_payment AS DECIMAL(18,2) ) ) AS total_revenue
				FROM {$table}
				WHERE payment_status = %s
				AND net_payment > 0";

			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Table name from wpdb prefix; value parameterized.
			$results = self::$wpdb->get_row( self::$wpdb->prepare( $sql, 'paid' ) );

			if ( ! $results || ( 0 === (int) $results->order_count && 0.0 === (float) $results->total_revenue ) ) {
				return false;
			}

			return array(
				'order_count'   => (int) $results->order_count,
				'total_revenue' => (float) $results->total_revenue,
			);
		}

		/**
		 * Get Tutor LMS metrics using native methods.
		 *
		 * @since BuddyBoss 2.9.30
		 *
		 * @return array|false
		 */
		private static function get_tutor_lms_native_metrics() {
			if ( ! function_exists( 'tutor' ) ) {
				return false;
			}

			/*
			 * Tutor 3.x sells through its own order table and no longer requires
			 * WooCommerce. Previously this bailed whenever WC() was absent, so
			 * every Tutor-native store reported zero revenue.
			 */
			$native = self::get_tutor_lms_order_table_metrics();
			if ( false !== $native ) {
				return $native;
			}

			// Older Tutor installs record sales as WooCommerce orders.
			if ( ! function_exists( 'WC' ) ) {
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
					       SUM(CAST(pm_total.meta_value AS DECIMAL(18,2))) as total_revenue
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
				$table_check = self::$wpdb->get_var( self::$wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
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
					       SUM(CAST(pm.meta_value AS DECIMAL(18,2))) as total_revenue
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
