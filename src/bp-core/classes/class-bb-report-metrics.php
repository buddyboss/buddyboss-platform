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
		 * Transactions read per query when reducing LearnDash revenue in PHP.
		 *
		 * This is a page size, not a limit. The reducer keeps paging until the
		 * store is exhausted, so every order is counted however many there are;
		 * the constant only bounds how many rows are held in memory at once.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @var int
		 */
		const LEARNDASH_CHUNK_SIZE = 500;

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

				/*
				 * No `meta_key` on purpose. This config previously declared
				 * `order_total`, a meta key LearnDash has never written — so
				 * LearnDash revenue was always zero. There is no flat numeric
				 * amount meta to point at instead: `Transaction::get_pricing()`
				 * resolves the amount from one of five gateway-specific shapes,
				 * the modern one being a serialized DTO. The reduction therefore
				 * happens in PHP, in get_learndash_native_metrics().
				 */
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
				// Written with gmdate(), so the GMT window bound applies directly.
				'date_col'       => 'created_at',
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
				// Tutor 3.x sells natively and keeps its own currency in the
				// tutor_option array. WooCommerce is only the fallback for older
				// installs that monetised through WC.
				'currency_key'       => 'tutor_option',
				'currency_index'     => 'currency_code',
				'currency_key_alt'   => 'woocommerce_currency',
				'custom_where'       => 'tutor_order',
			),
			'pmpro'               => array(
				'name'         => 'Paid Memberships Pro',
				'file'         => 'paid-memberships-pro/paid-memberships-pro.php',
				'table'        => 'pmpro_membership_orders',
				'amount_col'   => 'total',
				// Stored from time(), i.e. GMT, despite the column name.
				'date_col'     => 'timestamp',
				'status'       => array( 'success' ),
				'currency_key' => 'pmpro_currency',
			),
			'affiliatewp'         => array(
				'name'          => 'AffiliateWP',
				'file'          => 'affiliate-wp/affiliate-wp.php',
				'table'         => 'affiliate_wp_referrals',
				'amount_col'    => 'amount',
				// Written with gmdate().
				'date_col'      => 'date',
				// Referrals that were actually earned. Counting only 'unpaid'
				// excluded every commission already paid out, so the figure
				// shrank each time a payout ran.
				'status'        => array( 'paid', 'unpaid' ),
				'currency_func' => 'affwp_get_currency',
				'currency_key'  => 'affwp_settings',
			),

			/*
			 * Keyed `the_events_calendar` for continuity — the receiver holds
			 * history under that key — but gated on Event Tickets, which is what
			 * actually owns ticket orders and works with or without the calendar
			 * plugin. Gating on `the-events-calendar` meant a standalone Event
			 * Tickets store reported nothing at all.
			 *
			 * The old `post_type_const` pointed at
			 * Tribe__Tickets__Commerce__PayPal__Orders::ORDER_POST_TYPE, a class
			 * that no longer exists, so it silently fell through to the retired
			 * Tribe Commerce PayPal store while every current sale sits in
			 * `tec_tc_order`. Both stores are read now — see the collector.
			 */
			'the_events_calendar' => array(
				'name'               => 'Event Tickets',
				'file'               => 'event-tickets/event-tickets.php',
				'post_type_fallback' => 'tec_tc_order',
				'status'             => array( 'tec-tc-completed' ),
				'currency_func'      => 'tribe_get_option',
				'currency_option'    => 'tickets-commerce-currency-code',
				// Tribe__Main::OPTIONNAME, read directly when tribe_get_option()
				// is not loaded.
				'currency_key'       => 'tribe_events_calendar_options',
				'currency_index'     => 'tickets-commerce-currency-code',
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
		 * Reporting windows, as GMT lower bounds.
		 *
		 * Every source read here records time in GMT — `post_date_gmt` and
		 * `date_created_gmt` for the post/HPOS stores, and `gmdate()` writes for
		 * the MemberPress, Paid Memberships Pro and AffiliateWP tables, with
		 * Tutor naming its column `created_at_gmt` outright — so one bound is
		 * correct for all of them.
		 *
		 * An empty bound means no lower bound, i.e. all time.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return array Window name => GMT datetime, or '' for all time.
		 */
		private static function get_revenue_windows() {
			$now = time();

			return array(
				'all_time'     => '',
				'last_30_days' => gmdate( 'Y-m-d H:i:s', $now - ( 30 * DAY_IN_SECONDS ) ),
				'last_90_days' => gmdate( 'Y-m-d H:i:s', $now - ( 90 * DAY_IN_SECONDS ) ),
			);
		}

		/**
		 * Collect one plugin's revenue for a single window.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param string $plugin_slug Plugin slug.
		 * @param array  $config      Plugin configuration.
		 * @param string $since       GMT lower bound, '' for all time.
		 *
		 * @return array|object|false Raw collector data.
		 */
		private static function collect_plugin_window( $plugin_slug, $config, $since ) {
			// Try to use native plugin methods first.
			$method_name = 'get_' . $plugin_slug . '_native_metrics';
			if ( method_exists( __CLASS__, $method_name ) ) {
				return self::$method_name( $since );
			}

			if ( isset( $config['table'] ) ) {
				return self::get_table_metrics( $config, $since );
			}

			if ( self::has_post_type_config( $config ) ) {
				/*
				 * Previously gated on `isset( $config['post_type'] )`, a key no
				 * config defines — they all use post_type_fallback/_func/_getter/
				 * _const. That made this branch unreachable and left The Events
				 * Calendar, the only plugin relying on it, permanently at zero.
				 */
				return self::get_post_type_metrics( $config, $since );
			}

			return false;
		}

		/**
		 * Whether a config resolves to any collector at all.
		 *
		 * Distinct from a collector that runs and finds nothing: a plugin with no
		 * collector is dropped from the report, whereas one with no sales still
		 * reports zeros, which is the difference between "not measured" and
		 * "measured, no revenue".
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param string $plugin_slug Plugin slug.
		 * @param array  $config      Plugin configuration.
		 *
		 * @return bool
		 */
		private static function has_collector( $plugin_slug, $config ) {
			return method_exists( __CLASS__, 'get_' . $plugin_slug . '_native_metrics' )
				|| isset( $config['table'] )
				|| self::has_post_type_config( $config );
		}

		/**
		 * Whether a column exists on a table.
		 *
		 * Used before applying a window bound to a third-party table. These
		 * schemas were read from the versions installed at the time, and a later
		 * release can rename a column; probing means such a rename costs the
		 * window rather than producing a SQL error and a false zero.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param string $table  Fully qualified table name.
		 * @param string $column Column to look for.
		 *
		 * @return bool
		 */
		private static function column_exists( $table, $column ) {
			static $cache = array();

			$key = $table . '.' . $column;

			if ( isset( $cache[ $key ] ) ) {
				return $cache[ $key ];
			}

			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared -- Identifier escaped and backtick-quoted; value parameterized.
			$query = self::$wpdb->prepare( 'SHOW COLUMNS FROM `' . esc_sql( $table ) . '` LIKE %s', $column );

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Prepared above; memoized below.
			$cache[ $key ] = (bool) self::$wpdb->get_var( $query );

			return $cache[ $key ];
		}

		/**
		 * Reduce raw collector data to the reported revenue shape.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param array|object $data Raw collector data.
		 *
		 * @return array Revenue, order count and any per-currency breakdown.
		 */
		private static function shape_revenue( $data ) {
			$shaped = array(
				'total_revenue' => (float) ( isset( $data->total_revenue ) ? $data->total_revenue : $data['total_revenue'] ),
				'num_orders'    => (int) ( isset( $data->order_count ) ? $data->order_count : $data['order_count'] ),
			);

			$by_currency = isset( $data['by_currency'] ) ? $data['by_currency'] : array();
			if ( ! empty( $by_currency ) ) {
				$shaped['by_currency'] = $by_currency;
			}

			return $shaped;
		}

		/**
		 * Get metrics for a specific plugin, across every reporting window.
		 *
		 * @since BuddyBoss 2.9.30
		 *
		 * @param string $plugin_slug Plugin slug.
		 * @param array  $config      Plugin configuration.
		 *
		 * @return array|false Plugin metrics or false when nothing can collect them.
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

			if ( ! self::has_collector( $plugin_slug, $config ) ) {
				return false;
			}

			$windows = self::get_revenue_windows();

			/*
			 * All time stays at the top level, unchanged, because the receiver
			 * and every stored payload key off it. The bounded windows are added
			 * alongside so trend and MRR become derivable without breaking any
			 * existing consumer.
			 */
			$data = self::collect_plugin_window( $plugin_slug, $config, $windows['all_time'] );

			unset( $windows['all_time'] );

			$bounded = array();
			foreach ( $windows as $window => $since ) {
				$window_data = self::collect_plugin_window( $plugin_slug, $config, $since );

				$bounded[ $window ] = $window_data
					? self::shape_revenue( $window_data )
					: array(
						'total_revenue' => 0.0,
						'num_orders'    => 0,
					);
			}

			$metrics['windows'] = $bounded;

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
		 * @param array  $config Plugin configuration.
		 * @param string $since  GMT lower bound on post_date_gmt, '' for all time.
		 * @return object|false Database result or false on failure.
		 */
		private static function get_post_type_metrics( $config, $since = '' ) {
			try {
				// Get dynamic post type.
				$post_type = self::get_dynamic_post_type( $config );
				if ( empty( $post_type ) ) {
					return false;
				}

				/*
				 * Nothing to sum without a meta key. LearnDash and Event Tickets
				 * both resolve their amounts in their own collectors and declare
				 * none, and those collectors take priority, so this is only
				 * reached if that dispatch ever changes.
				 */
				if ( empty( $config['meta_key'] ) ) {
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

				$query_args = array_merge( array( $post_type ), $config['status'], array( $config['meta_key'] ) );

				if ( '' !== $since ) {
					$query_parts[] = 'AND p.post_date_gmt >= %s';
					$query_args[]  = $since;
				}

				$query = self::$wpdb->prepare(
					implode( ' ', $query_parts ),
					$query_args
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
		 * @param array  $config Plugin configuration.
		 * @param string $since  GMT lower bound on the config's date column, '' for all time.
		 * @return object|false Database result or false on failure.
		 */
		private static function get_table_metrics( $config, $since = '' ) {
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
				$sql        = "SELECT COUNT(*) as order_count, SUM(CAST($amount_col AS DECIMAL(18,2))) as total_revenue
					FROM %i
					WHERE status IN (" . $status_placeholders . ")
					AND $amount_col IS NOT NULL
					AND $amount_col > 0";

				$args = array_merge( array( $table_name ), $config['status'] );

				// Windowing needs the table's own timestamp column; without one
				// configured, or if a later release renamed it, the window is
				// simply not bounded rather than failing.
				if ( '' !== $since && ! empty( $config['date_col'] ) && self::column_exists( $table_name, $config['date_col'] ) ) {
					$date_col = esc_sql( $config['date_col'] );
					$sql     .= " AND $date_col >= %s";
					$args[]   = $since;
				}

				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared -- Column and table names sanitized above; values parameterized.
				$query = self::$wpdb->prepare( $sql, $args );

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
		 * @param string $since GMT lower bound on order creation, '' for all time.
		 *
		 * @return array|false
		 */
		private static function get_woocommerce_native_metrics( $since = '' ) {
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
					$table_exists = self::$wpdb->get_var( self::$wpdb->prepare( 'SHOW TABLES LIKE %s', $order_table ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					if ( $table_exists === $order_table ) {
						return self::get_woocommerce_hpos_metrics( $order_statuses, $order_table, $since );
					}
				}

				return self::get_woocommerce_legacy_metrics( $order_statuses, $since );
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
		 * @param string $since          GMT lower bound on date_created_gmt, '' for all time.
		 *
		 * @return array|false
		 */
		private static function get_woocommerce_hpos_metrics( $order_statuses, $order_table, $since = '' ) {
			$status_placeholders = implode( ',', array_fill( 0, count( $order_statuses ), '%s' ) );

			/*
			 * Bound on the parent order's creation date in both queries, so a
			 * refund issued inside the window against an older order does not
			 * subtract from revenue the window never counted.
			 */
			$gross_window  = '' === $since ? '' : ' AND o.date_created_gmt >= %s';
			$refund_window = '' === $since ? '' : ' AND o.date_created_gmt >= %s';

			// Gross revenue per currency.
			$gross_sql = "SELECT o.currency AS currency, COUNT( DISTINCT o.id ) AS order_count, SUM( o.total_amount ) AS revenue
				FROM {$order_table} o
				WHERE o.type = %s
				AND o.status IN ( {$status_placeholders} )
				AND o.total_amount > 0{$gross_window}
				GROUP BY o.currency";

			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Table name from wpdb; values parameterized.
			$gross_args = array_merge( array( 'shop_order' ), $order_statuses );
			if ( '' !== $since ) {
				$gross_args[] = $since;
			}

			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Table names from wpdb; values parameterized.
			$gross = self::$wpdb->get_results( self::$wpdb->prepare( $gross_sql, $gross_args ) );

			// Refunds against those same orders. Stored negative by WooCommerce,
			// so ABS() keeps this correct whichever sign convention applies.
			$refund_sql = "SELECT r.currency AS currency, SUM( ABS( r.total_amount ) ) AS refunded
				FROM {$order_table} r
				INNER JOIN {$order_table} o ON o.id = r.parent_order_id
				WHERE r.type = %s
				AND o.type = %s
				AND o.status IN ( {$status_placeholders} ){$refund_window}
				GROUP BY r.currency";

			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Table name from wpdb; values parameterized.
			$refund_args = array_merge( array( 'shop_order_refund', 'shop_order' ), $order_statuses );
			if ( '' !== $since ) {
				$refund_args[] = $since;
			}

			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Table names from wpdb; values parameterized.
			$refunds = self::$wpdb->get_results( self::$wpdb->prepare( $refund_sql, $refund_args ) );

			return self::combine_currency_rows( $gross, $refunds );
		}

		/**
		 * WooCommerce metrics from legacy post storage.
		 *
		 * @since BuddyBoss 2.9.30
		 *
		 * @param array  $order_statuses Statuses that count as revenue.
		 * @param string $since          GMT lower bound on post_date_gmt, '' for all time.
		 *
		 * @return array|false
		 */
		private static function get_woocommerce_legacy_metrics( $order_statuses, $since = '' ) {
			$post_types = array( 'shop_order' );

			// Present during an HPOS migration.
			if ( post_type_exists( 'shop_order_placehold' ) ) {
				$post_types[] = 'shop_order_placehold';
			}

			$type_placeholders   = implode( ',', array_fill( 0, count( $post_types ), '%s' ) );
			$status_placeholders = implode( ',', array_fill( 0, count( $order_statuses ), '%s' ) );

			/*
			 * Bound on the parent order in both queries — `p` is the order in the
			 * gross query and the refund's parent in the refund query — so a
			 * refund cannot subtract from a window that never counted its order.
			 */
			$window = '' === $since ? '' : ' AND p.post_date_gmt >= %s';

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
				AND tot.meta_value > 0{$window}
				GROUP BY currency";

			$gross_args = array_merge(
				array( self::UNKNOWN_CURRENCY, '_order_total', '_order_currency' ),
				$post_types,
				$order_statuses
			);

			if ( '' !== $since ) {
				$gross_args[] = $since;
			}

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
				AND p.post_status IN ( {$status_placeholders} ){$window}
				GROUP BY currency";

			$refund_args = array_merge(
				array( self::UNKNOWN_CURRENCY, '_order_total', '_order_currency', 'shop_order_refund' ),
				$post_types,
				$order_statuses
			);

			if ( '' !== $since ) {
				$refund_args[] = $since;
			}

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
		 * Meta keys that can carry a LearnDash transaction amount or currency.
		 *
		 * Mirrors the resolution order in `Transaction::get_pricing()` and
		 * `Transaction::get_formatted_price()`. Fetching only these keeps the
		 * per-transaction meta read narrow.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return array Meta key names.
		 */
		private static function get_learndash_price_meta_keys() {
			return array(
				// Modern (LearnDash 4.5+). Serialized Learndash_Pricing_DTO or its array form.
				'pricing_info',
				// Legacy coupon meta, carrying its own price/discounted_price.
				'coupon',
				// Legacy Razorpay, serialized pricing array.
				'pricing',
				// Legacy Stripe.
				'stripe_currency',
				'stripe_price',
				'amount',
				'subscribe_price',
				// Legacy PayPal IPN.
				'mc_currency',
				'mc_gross',
				'mc_amount3',
				// Qualifiers.
				'price_type',
				'is_zero_price',
			);
		}

		/**
		 * Resolve the amount actually paid for one LearnDash transaction.
		 *
		 * LearnDash has no single amount meta. `Transaction::get_pricing()`
		 * dispatches on the gateway, and each legacy gateway stored the figure
		 * under its own keys, so this walks the same order and returns the first
		 * shape that resolves.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param array $meta Meta key => raw meta value for one transaction.
		 *
		 * @return array Amount and currency, amount 0 when nothing resolves.
		 */
		private static function resolve_learndash_amount( $meta ) {
			$none = array(
				'amount'   => 0.0,
				'currency' => '',
			);

			// Free enrolments (100% coupon) are not revenue.
			if ( ! empty( $meta['is_zero_price'] ) && '0' !== (string) $meta['is_zero_price'] ) {
				return $none;
			}

			$is_subscription = isset( $meta['price_type'] ) && 'subscribe' === $meta['price_type'];

			// 1. Modern pricing_info, and 2. the legacy coupon meta, share a shape.
			foreach ( array( 'pricing_info', 'coupon', 'pricing' ) as $key ) {
				if ( ! isset( $meta[ $key ] ) ) {
					continue;
				}

				$pricing = maybe_unserialize( $meta[ $key ] );

				// Stored as a Learndash_Pricing_DTO on some gateways, as its
				// array form on others, so normalise before reading.
				if ( is_object( $pricing ) ) {
					$pricing = get_object_vars( $pricing );
				}

				if ( ! is_array( $pricing ) ) {
					continue;
				}

				$price = null;

				if ( ! empty( $pricing['trial_duration_value'] ) && isset( $pricing['trial_price'] ) ) {
					// Subscription still inside its trial.
					$price = $pricing['trial_price'];
				} elseif ( ! empty( $pricing['discount'] ) && isset( $pricing['discounted_price'] ) ) {
					$price = $pricing['discounted_price'];
				} elseif ( isset( $pricing['price'] ) ) {
					$price = $pricing['price'];
				}

				if ( null !== $price ) {
					return array(
						'amount'   => (float) $price,
						'currency' => isset( $pricing['currency'] ) ? $pricing['currency'] : '',
					);
				}
			}

			// 3. Legacy Stripe.
			foreach ( $is_subscription ? array( 'subscribe_price', 'stripe_price', 'amount' ) : array( 'stripe_price', 'amount' ) as $key ) {
				if ( isset( $meta[ $key ] ) && is_numeric( $meta[ $key ] ) ) {
					return array(
						'amount'   => (float) $meta[ $key ],
						'currency' => isset( $meta['stripe_currency'] ) ? $meta['stripe_currency'] : '',
					);
				}
			}

			// 4. Legacy PayPal IPN. mc_gross is the one-off charge, mc_amount3 the recurring one.
			foreach ( array( 'mc_gross', 'mc_amount3' ) as $key ) {
				if ( isset( $meta[ $key ] ) && is_numeric( $meta[ $key ] ) ) {
					return array(
						'amount'   => (float) $meta[ $key ],
						'currency' => isset( $meta['mc_currency'] ) ? $meta['mc_currency'] : '',
					);
				}
			}

			return $none;
		}

		/**
		 * Get LearnDash metrics using native methods.
		 *
		 * Reduced in PHP rather than SQL: see the note on the `learndash` entry
		 * in $supported_plugins for why no meta key can be summed directly.
		 * Transactions are walked in chunks so memory stays bounded on large
		 * stores, and parent order posts are excluded — LearnDash writes one
		 * parent plus one child per product, both published, so counting every
		 * post of the type would inflate the order count.
		 *
		 * @since BuddyBoss 2.9.30
		 *
		 * @param string $since GMT lower bound on post_date_gmt, '' for all time.
		 *
		 * @return array|false
		 */
		private static function get_learndash_native_metrics( $since = '' ) {
			if ( ! defined( 'LEARNDASH_VERSION' ) ) {
				return false;
			}

			try {
				/*
				 * One unbounded walk serves every reporting window: rows are
				 * bucketed against the window bounds during a single pass, and
				 * the result is memoized for the request. Without this the
				 * collector was invoked once per window and re-walked the
				 * store each time. The caller mints its window bounds seconds
				 * after the memo mints its own, so bounds are matched with an
				 * hour of tolerance — irrelevant against 30/90-day windows.
				 */
				static $memo = null;

				if ( null === $memo ) {
					$memo = array(
						'windows' => self::get_revenue_windows(),
					);

					$memo['results'] = self::learndash_scan_transactions( $memo['windows'] );
				}

				if ( '' === $since ) {
					return $memo['results']['all_time'];
				}

				$bound = strtotime( $since );
				foreach ( $memo['windows'] as $window => $cutoff ) {
					if ( '' !== $cutoff && abs( strtotime( $cutoff ) - $bound ) <= HOUR_IN_SECONDS ) {
						return $memo['results'][ $window ];
					}
				}

				// A bound matching no standard window: walk just that window.
				$single = self::learndash_scan_transactions( array( 'requested' => $since ) );

				return $single['requested'];
			} catch ( Exception $e ) {
				// Silently return false on error.
				return false;
			}
		}

		/**
		 * Walk the LearnDash transaction store once and total every window.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param array $windows Window name => GMT lower bound, '' for all time.
		 *
		 * @return array Window name => metrics array, or false for an empty window.
		 */
		private static function learndash_scan_transactions( $windows ) {
			$config    = self::$supported_plugins['learndash'];
			$post_type = self::get_dynamic_post_type( $config );
			$statuses  = $config['status'];
			$meta_keys = self::get_learndash_price_meta_keys();

			$status_placeholders = implode( ', ', array_fill( 0, count( $statuses ), '%s' ) );
			$meta_placeholders   = implode( ', ', array_fill( 0, count( $meta_keys ), '%s' ) );

			// The SQL bound is the loosest requested window, so a purely
			// windowed request never walks outside its own window.
			$floor = null;
			foreach ( $windows as $cutoff ) {
				if ( '' === $cutoff ) {
					$floor = '';
					break;
				}
				if ( null === $floor || $cutoff < $floor ) {
					$floor = $cutoff;
				}
			}
			$floor = (string) $floor;

			$window_sql = '' === $floor ? '' : ' AND p.post_date_gmt >= %s';

			/*
			 * Child transactions only. The parent order carries `is_parent`;
			 * legacy transactions predate parents and have no such row, so
			 * this is a LEFT JOIN rather than a post_parent test.
			 */
			$id_sql = 'SELECT p.ID, p.post_date_gmt
				FROM ' . self::$wpdb->posts . ' p
				LEFT JOIN ' . self::$wpdb->postmeta . " par ON par.post_id = p.ID AND par.meta_key = 'is_parent'
				WHERE p.post_type = %s
				AND p.post_status IN ( {$status_placeholders} )
				AND ( par.meta_id IS NULL OR par.meta_value = '' OR par.meta_value = '0' ){$window_sql}
				AND p.ID > %d
				ORDER BY p.ID ASC
				LIMIT %d";

			$buckets = array_fill_keys( array_keys( $windows ), array() );
			$last_id = 0;

			/*
			 * Pages through every matching transaction — there is no cap on
			 * the number counted. Paging is keyed on `p.ID > $last_id` with an
			 * ascending sort, so each pass strictly advances and no row is
			 * seen twice or skipped.
			 */
			while ( true ) {
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared -- Table names from wpdb; values parameterized.
				$id_query = self::$wpdb->prepare( $id_sql, array_merge( array( $post_type ), $statuses, '' === $floor ? array() : array( $floor ), array( $last_id, self::LEARNDASH_CHUNK_SIZE ) ) );

				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Prepared above.
				$id_rows = self::$wpdb->get_results( $id_query );

				if ( empty( $id_rows ) ) {
					break;
				}

				$ids   = array();
				$dates = array();
				foreach ( $id_rows as $id_row ) {
					$id           = (int) $id_row->ID;
					$ids[]        = $id;
					$dates[ $id ] = $id_row->post_date_gmt;
				}

				$highest = max( $ids );

				/*
				 * IDs are strictly ascending, so this can only fail if the
				 * driver returned something unexpected. Breaking then avoids
				 * an endless loop; it cannot truncate a healthy walk.
				 */
				if ( $highest <= $last_id ) {
					break;
				}

				$last_id = $highest;

				$id_placeholders = implode( ', ', array_fill( 0, count( $ids ), '%d' ) );

				$meta_sql = 'SELECT post_id, meta_key, meta_value
					FROM ' . self::$wpdb->postmeta . "
					WHERE post_id IN ( {$id_placeholders} )
					AND meta_key IN ( {$meta_placeholders} )";

				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared -- Table name from wpdb; values parameterized.
				$meta_query = self::$wpdb->prepare( $meta_sql, array_merge( $ids, $meta_keys ) );

				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Prepared above.
				$rows = self::$wpdb->get_results( $meta_query );

				$grouped = array();
				foreach ( (array) $rows as $row ) {
					$grouped[ (int) $row->post_id ][ $row->meta_key ] = $row->meta_value;
				}

				foreach ( $ids as $id ) {
					if ( empty( $grouped[ $id ] ) ) {
						continue;
					}

					$resolved = self::resolve_learndash_amount( $grouped[ $id ] );

					if ( $resolved['amount'] <= 0 ) {
						continue;
					}

					$code = self::currency_or_unknown( $resolved['currency'] );

					// MySQL datetimes share one format, so the string compare
					// buckets each transaction into every window it falls in.
					foreach ( $windows as $window => $cutoff ) {
						if ( '' !== $cutoff && $dates[ $id ] < $cutoff ) {
							continue;
						}

						if ( isset( $buckets[ $window ][ $code ] ) ) {
							++$buckets[ $window ][ $code ]['orders'];
							$buckets[ $window ][ $code ]['revenue'] += $resolved['amount'];
						} else {
							$buckets[ $window ][ $code ] = array(
								'orders'  => 1,
								'revenue' => $resolved['amount'],
							);
						}
					}
				}

				// A short page means the store is exhausted.
				if ( count( $ids ) < self::LEARNDASH_CHUNK_SIZE ) {
					break;
				}
			}

			$results = array();
			foreach ( $buckets as $window => $by_currency ) {
				if ( empty( $by_currency ) ) {
					$results[ $window ] = false;
					continue;
				}

				$total_orders  = 0;
				$total_revenue = 0.0;
				foreach ( $by_currency as $bucket ) {
					$total_orders  += $bucket['orders'];
					$total_revenue += $bucket['revenue'];
				}

				$results[ $window ] = array(
					'order_count'   => $total_orders,
					'total_revenue' => $total_revenue,
					'by_currency'   => $by_currency,
				);
			}

			return $results;
		}

		/**
		 * Get MemberPress metrics using native methods.
		 *
		 * @since BuddyBoss 2.9.30
		 *
		 * @param string $since GMT lower bound on the source's own timestamp, '' for all time.
		 *
		 * @return array|false
		 */
		private static function get_memberpress_native_metrics( $since = '' ) {
			if ( ! defined( 'MEPR_VERSION' ) || ! class_exists( 'MeprTransaction' ) ) {
				return false;
			}

			try {
				$statuses = self::$supported_plugins['memberpress']['status'];
				$table    = self::$wpdb->prefix . 'mepr_transactions';

				// Check if table exists.
				$table_check = self::$wpdb->get_var( self::$wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				if ( $table_check !== $table ) {
					return false;
				}

				$sql  = 'SELECT COUNT(*) as order_count, SUM(amount) as total_revenue
					FROM %i
					WHERE status = %s
					AND amount > 0';
				$args = array( $table, $statuses[0] );

				if ( '' !== $since && self::column_exists( $table, 'created_at' ) ) {
					$sql   .= ' AND created_at >= %s';
					$args[] = $since;
				}

				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared -- Values parameterized.
				$query = self::$wpdb->prepare( $sql, $args );

				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Prepared above.
				$results = self::$wpdb->get_row( $query );

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
		 * @param string $since GMT lower bound on the source's own timestamp, '' for all time.
		 *
		 * @return array|false
		 */
		private static function get_lifterlms_native_metrics( $since = '' ) {
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

				if ( '' !== $since ) {
					$query       .= ' AND p.post_date_gmt >= %s';
					$query_args[] = $since;
				}

				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared -- Table names from wpdb; values parameterized.
				$prepared = self::$wpdb->prepare( $query, $query_args );

				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Prepared above.
				$results = self::$wpdb->get_row( $prepared );

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
		 * @param string $since GMT lower bound on the source's own timestamp, '' for all time.
		 *
		 * @return array|false Metrics, or false when the table is absent or empty.
		 */
		private static function get_tutor_lms_order_table_metrics( $since = '' ) {
			$table  = self::$wpdb->prefix . 'tutor_orders';
			$exists = self::$wpdb->get_var( self::$wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

			if ( $exists !== $table ) {
				return false;
			}

			$sql  = "SELECT COUNT(*) AS order_count, SUM( CAST( net_payment AS DECIMAL(18,2) ) ) AS total_revenue
				FROM {$table}
				WHERE payment_status = %s
				AND net_payment > 0";
			$args = array( 'paid' );

			/*
			 * Tutor names its timestamp `created_at_gmt`, so the GMT bound applies
			 * directly — but the column is only probed, never assumed. This schema
			 * was read from Tutor 3.x, and a later major could rename it; without
			 * the probe that would turn every windowed query into a SQL error
			 * rather than an unbounded one.
			 */
			if ( '' !== $since && self::column_exists( $table, 'created_at_gmt' ) ) {
				$sql   .= ' AND created_at_gmt >= %s';
				$args[] = $since;
			}

			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared -- Table name from wpdb prefix; values parameterized.
			$query = self::$wpdb->prepare( $sql, $args );

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Prepared above.
			$results = self::$wpdb->get_row( $query );

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
		 * @param string $since GMT lower bound on the source's own timestamp, '' for all time.
		 *
		 * @return array|false
		 */
		private static function get_tutor_lms_native_metrics( $since = '' ) {
			if ( ! function_exists( 'tutor' ) ) {
				return false;
			}

			/*
			 * Tutor 3.x sells through its own order table and no longer requires
			 * WooCommerce. Previously this bailed whenever WC() was absent, so
			 * every Tutor-native store reported zero revenue.
			 */
			$native = self::get_tutor_lms_order_table_metrics( $since );
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
				$sql = 'SELECT COUNT(DISTINCT p.ID) as order_count,
						SUM(CAST(pm_total.meta_value AS DECIMAL(18,2))) as total_revenue
					FROM ' . self::$wpdb->posts . ' p
					INNER JOIN ' . self::$wpdb->postmeta . ' pm_tutor ON p.ID = pm_tutor.post_id
					INNER JOIN ' . self::$wpdb->postmeta . " pm_total ON p.ID = pm_total.post_id
					WHERE p.post_type = %s
					AND p.post_status IN ( {$status_placeholders} )
					AND pm_tutor.meta_key = '_is_tutor_order_for_course'
					AND pm_tutor.meta_value = 'yes'
					AND pm_total.meta_key = %s
					AND pm_total.meta_value > 0";

				$args = array_merge( array( $post_type ), $statuses, array( $meta_key ) );

				if ( '' !== $since ) {
					$sql   .= ' AND p.post_date_gmt >= %s';
					$args[] = $since;
				}

				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared -- Table names from wpdb; values parameterized.
				$query = self::$wpdb->prepare( $sql, $args );

				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Prepared above.
				$results = self::$wpdb->get_row( $query );

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
		 * @param string $since GMT lower bound on the source's own timestamp, '' for all time.
		 *
		 * @return array|false
		 */
		private static function get_pmpro_native_metrics( $since = '' ) {
			if ( ! defined( 'PMPRO_VERSION' ) ) {
				return false;
			}

			try {
				$config              = self::$supported_plugins['pmpro'];
				$statuses            = $config['status'];
				$status_placeholders = implode( ', ', array_fill( 0, count( $statuses ), '%s' ) );
				$table               = self::$wpdb->prefix . 'pmpro_membership_orders';

				// Check if table exists.
				$table_check = self::$wpdb->get_var( self::$wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				if ( $table_check !== $table ) {
					return false;
				}

				$sql  = "SELECT COUNT(*) as order_count, SUM(total) as total_revenue
					FROM %i
					WHERE status IN ( {$status_placeholders} )
					AND total > 0";
				$args = array_merge( array( $table ), $statuses );

				if ( '' !== $since && self::column_exists( $table, 'timestamp' ) ) {
					$sql   .= ' AND `timestamp` >= %s';
					$args[] = $since;
				}

				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared -- Placeholders are generated; values parameterized.
				$query = self::$wpdb->prepare( $sql, $args );

				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Prepared above.
				$results = self::$wpdb->get_row( $query );

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
		 * @param string $since GMT lower bound on the source's own timestamp, '' for all time.
		 *
		 * @return array|false
		 */
		private static function get_affiliatewp_native_metrics( $since = '' ) {
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

				/*
				 * Statuses come from the config rather than being hardcoded. The
				 * config was corrected to count 'paid' alongside 'unpaid', but
				 * this native collector shadows get_table_metrics(), so while it
				 * kept its own 'unpaid' literal that correction never took
				 * effect and the figure still shrank on every payout run.
				 */
				$config              = self::$supported_plugins['affiliatewp'];
				$statuses            = $config['status'];
				$status_placeholders = implode( ', ', array_fill( 0, count( $statuses ), '%s' ) );

				$sql  = "SELECT COUNT(*) as order_count, SUM(amount) as total_revenue
					FROM %i
					WHERE status IN ( {$status_placeholders} )
					AND amount > 0";
				$args = array_merge( array( $table ), $statuses );

				if ( '' !== $since && self::column_exists( $table, 'date' ) ) {
					$sql   .= ' AND `date` >= %s';
					$args[] = $since;
				}

				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared -- Placeholders are generated; values parameterized.
				$query = self::$wpdb->prepare( $sql, $args );

				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Prepared above.
				$results = self::$wpdb->get_row( $query );

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
		/**
		 * Sum one post-type order store, grouped by each order's own currency.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param string $post_type    Order post type.
		 * @param array  $statuses     Post statuses that count as paid.
		 * @param string $total_key    Meta key holding the order total.
		 * @param string $currency_key Meta key holding the order currency.
		 * @param string $since        GMT lower bound on post_date_gmt, '' for all time.
		 *
		 * @return array Rows of currency, order_count and revenue.
		 */
		private static function get_order_rows_by_currency( $post_type, $statuses, $total_key, $currency_key, $since = '' ) {
			if ( empty( $statuses ) ) {
				return array();
			}

			$status_placeholders = implode( ', ', array_fill( 0, count( $statuses ), '%s' ) );
			$window              = '' === $since ? '' : ' AND p.post_date_gmt >= %s';

			$sql = 'SELECT COALESCE( NULLIF( cur.meta_value, \'\' ), %s ) AS currency,
					COUNT( DISTINCT p.ID ) AS order_count,
					SUM( CAST( tot.meta_value AS DECIMAL(18,2) ) ) AS revenue
				FROM ' . self::$wpdb->posts . ' p
				INNER JOIN ' . self::$wpdb->postmeta . ' tot ON p.ID = tot.post_id AND tot.meta_key = %s
				LEFT JOIN ' . self::$wpdb->postmeta . " cur ON p.ID = cur.post_id AND cur.meta_key = %s
				WHERE p.post_type = %s
				AND p.post_status IN ( {$status_placeholders} )
				AND tot.meta_value > 0{$window}
				GROUP BY currency";

			$args = array_merge(
				array( self::UNKNOWN_CURRENCY, $total_key, $currency_key, $post_type ),
				$statuses
			);

			if ( '' !== $since ) {
				$args[] = $since;
			}

			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared -- Table names from wpdb; values parameterized.
			$query = self::$wpdb->prepare( $sql, $args );

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Prepared above.
			return (array) self::$wpdb->get_results( $query );
		}

		/**
		 * Get Event Tickets metrics using native methods.
		 *
		 * Reads both order stores and adds them together:
		 *
		 * - Tickets Commerce (`tec_tc_order`), the current store. Total and
		 *   currency are plain per-order meta, so this groups by the order's own
		 *   currency rather than assuming the site default.
		 * - Tribe Commerce PayPal (`tribe_tpp_orders`), retired but still holding
		 *   historical sales on long-lived sites.
		 *
		 * Tickets sold through the WooCommerce or EDD providers in Event Tickets
		 * Plus are deliberately not counted here — those orders are WooCommerce /
		 * EDD orders and are already collected by that plugin's own entry. A site
		 * selling only that way reports zero here, which is correct, not missing.
		 *
		 * @since BuddyBoss 2.9.30
		 *
		 * @param string $since GMT lower bound on post_date_gmt, '' for all time.
		 *
		 * @return array|false
		 */
		private static function get_the_events_calendar_native_metrics( $since = '' ) {
			// Event Tickets owns orders. The calendar plugin sells nothing.
			if ( ! class_exists( 'Tribe__Tickets__Main' ) ) {
				return false;
			}

			try {
				$config    = self::$supported_plugins['the_events_calendar'];
				$post_type = self::get_dynamic_post_type( $config );
				$statuses  = $config['status'];

				// Current store: Tickets Commerce.
				$rows = self::get_order_rows_by_currency(
					$post_type,
					$statuses,
					'_tec_tc_order_total_value',
					'_tec_tc_order_currency',
					$since
				);

				/*
				 * Historical store: Tribe Commerce PayPal. Orders are plain
				 * published posts there, with the PayPal IPN field names.
				 */
				$legacy = self::get_order_rows_by_currency(
					'tribe_tpp_orders',
					array( 'publish' ),
					'mc_gross',
					'mc_currency',
					$since
				);

				return self::combine_currency_rows( array_merge( $rows, $legacy ), array() );
			} catch ( Exception $e ) {
				// Silently return false on error.
				return false;
			}
		}
	}
}
