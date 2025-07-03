<?php
/**
 * Financial Metrics Collection Class.
 *
 * @since   BuddyBoss [BBVERSION]
 * @package BuddyBoss\Core
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'BB_Financial_Metrics' ) ) {

	/**
	 * BuddyBoss Financial Metrics Collection object.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	class BB_Financial_Metrics {

		/**
		 * The single instance of the class.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @var self
		 */
		private static $instance = null;

		/**
		 * Global $wpdb object.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @var wpdb
		 */
		private static $wpdb = null;

		/**
		 * Cache for collected metrics.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @var array
		 */
		private static $metrics_cache = null;

		/**
		 * Supported plugins configuration.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @var array
		 */
		private static $supported_plugins = array(
			'learndash'   => array(
				'name'         => 'LearnDash LMS',
				'file'         => 'sfwd-lms/sfwd_lms.php',
				'post_type'    => 'sfwd-transactions',
				'meta_key'     => 'order_total',
				'status'       => array( 'publish' ),
				'currency_key' => 'learndash_settings_payments',
			),
			'memberpress' => array(
				'name'       => 'MemberPress',
				'file'       => 'memberpress/memberpress.php',
				'table'      => 'mepr_transactions',
				'amount_col' => 'amount',
				'status'     => array( 'complete' ),
				'currency'   => 'USD',
			),
			'woocommerce' => array(
				'name'          => 'WooCommerce',
				'file'          => 'woocommerce/woocommerce.php',
				'post_type'     => 'shop_order',
				'meta_key'      => '_order_total',
				'status'        => array( 'wc-completed', 'wc-processing' ),
				'currency_func' => 'get_woocommerce_currency',
			),
			'lifterlms'   => array(
				'name'         => 'LifterLMS',
				'file'         => 'lifterlms/lifterlms.php',
				'post_type'    => 'llms_order',
				'meta_key'     => '_llms_order_total',
				'status'       => array( 'publish' ),
				'currency_key' => 'lifterlms_currency',
			),
			'tutor_lms'   => array(
				'name'         => 'Tutor LMS',
				'file'         => 'tutor/tutor.php',
				'post_type'    => 'tutor_order',
				'meta_key'     => 'tutor_order_total',
				'status'       => array( 'publish' ),
				'currency_key' => 'tutor_currency',
			),
			'pmpro'       => array(
				'name'         => 'Paid Memberships Pro',
				'file'         => 'paid-memberships-pro/paid-memberships-pro.php',
				'table'        => 'pmpro_membership_orders',
				'amount_col'   => 'total',
				'status'       => array( 'success' ),
				'currency_key' => 'pmpro_currency',
			),
			'affiliatewp' => array(
				'name'          => 'AffiliateWP',
				'file'          => 'affiliate-wp/affiliate-wp.php',
				'table'         => 'affiliate_wp_referrals',
				'amount_col'    => 'amount',
				'status'        => array( 'unpaid' ),
				'currency_func' => 'affwp_get_currency',
			),
		);

		/**
		 * Get the instance of this class.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return BB_Financial_Metrics|null
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
		 * @since BuddyBoss [BBVERSION]
		 */
		public function __construct() {
			global $wpdb;
			self::$wpdb = $wpdb;
		}

		/**
		 * Initialize wpdb if not already set.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		private static function init_wpdb() {
			if ( null === self::$wpdb ) {
				global $wpdb;
				self::$wpdb = $wpdb;
			}
		}

		/**
		 * Collect financial metrics from all supported plugins.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param bool $force_refresh Force refresh cache.
		 * @return array Array of financial metrics from all plugins.
		 */
		public static function collect( $force_refresh = false ) {
			self::init_wpdb();

			// Return cached data if available and not forcing refresh.
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

			return $metrics;
		}

		/**
		 * Check if a plugin is active.
		 *
		 * @since BuddyBoss [BBVERSION]
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
		 * @since BuddyBoss [BBVERSION]
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

			// Get financial data based on plugin type.
			if ( isset( $config['post_type'] ) ) {
				$data = self::get_post_type_metrics( $config );
			} elseif ( isset( $config['table'] ) ) {
				$data = self::get_table_metrics( $config );
			} else {
				return false;
			}

			if ( $data ) {
				$metrics['num_orders']    = (int) $data->order_count;
				$metrics['total_revenue'] = (float) $data->total_revenue;
			}

			return $metrics;
		}

		/**
		 * Get currency for a plugin.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param array $config Plugin configuration.
		 * @return string Currency code.
		 */
		private static function get_plugin_currency( $config ) {
			if ( isset( $config['currency'] ) ) {
				return $config['currency'];
			}

			if ( isset( $config['currency_func'] ) && function_exists( $config['currency_func'] ) ) {
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
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param array $config Plugin configuration.
		 * @return object|false Database result or false on failure.
		 */
		private static function get_post_type_metrics( $config ) {
			try {
				$status_placeholders = implode( ',', array_fill( 0, count( $config['status'] ), '%s' ) );

				$query = self::$wpdb->prepare(
					'SELECT COUNT(*) as order_count, SUM(CAST(meta_value AS DECIMAL(10,2))) as total_revenue
					FROM ' . self::$wpdb->posts . ' p
					LEFT JOIN ' . self::$wpdb->postmeta . " pm ON p.ID = pm.post_id
					WHERE p.post_type = %s
					AND p.post_status IN ({$status_placeholders})
					AND pm.meta_key = %s
					AND pm.meta_value IS NOT NULL
					AND pm.meta_value != ''
					AND pm.meta_value != '0'",
					array_merge( array( $config['post_type'] ), $config['status'], array( $config['meta_key'] ) )
				);

				$result = self::$wpdb->get_row( $query );

				// Validate result.
				if ( $result && ( $result->order_count > 0 || $result->total_revenue > 0 ) ) {
					return $result;
				}

				return false;
			} catch ( Exception $e ) {
				// Log error silently and return false.
				error_log( 'BB_Financial_Metrics: Error getting post type metrics for ' . $config['post_type'] . ': ' . $e->getMessage() );
				return false;
			}
		}

		/**
		 * Get metrics from custom tables (MemberPress, PMPro, AffiliateWP).
		 *
		 * @since BuddyBoss [BBVERSION]
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
				$table_exists = self::$wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" );
				if ( ! $table_exists ) {
					return false;
				}

				$status_placeholders = implode( ',', array_fill( 0, count( $config['status'] ), '%s' ) );

				$query = self::$wpdb->prepare(
					"SELECT COUNT(*) as order_count, SUM(CAST({$config['amount_col']} AS DECIMAL(10,2))) as total_revenue
					FROM " . self::$wpdb->prefix . $config['table'] . "
					WHERE status IN ({$status_placeholders})
					AND {$config['amount_col']} IS NOT NULL
					AND {$config['amount_col']} > 0",
					$config['status']
				);

				$result = self::$wpdb->get_row( $query );

				// Validate result.
				if ( $result && ( $result->order_count > 0 || $result->total_revenue > 0 ) ) {
					return $result;
				}

				return false;
			} catch ( Exception $e ) {
				// Log error silently and return false.
				error_log( 'BB_Financial_Metrics: Error getting table metrics for ' . $config['table'] . ': ' . $e->getMessage() );
				return false;
			}
		}

		/**
		 * Clear metrics cache.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		public static function clear_cache() {
			self::$metrics_cache = null;
		}

		/**
		 * Get cache status.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return bool True if cache is available.
		 */
		public static function is_cached() {
			return null !== self::$metrics_cache;
		}
	}
}
