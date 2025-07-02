<?php
/**
 * Financial Metrics Collection Class.
 *
 * @since   BuddyBoss 2.7.40
 * @package BuddyBoss\Core
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'BB_Financial_Metrics' ) ) {

	/**
	 * BuddyBoss Financial Metrics Collection object.
	 *
	 * @since BuddyBoss 2.7.40
	 */
	class BB_Financial_Metrics {

		/**
		 * The single instance of the class.
		 *
		 * @since BuddyBoss 2.7.40
		 *
		 * @var self
		 */
		private static $instance = null;

		/**
		 * Global $wpdb object.
		 *
		 * @since BuddyBoss 2.7.40
		 *
		 * @var wpdb
		 */
		public static $wpdb;

		/**
		 * Get the instance of this class.
		 *
		 * @since BuddyBoss 2.7.40
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
		 * @since BuddyBoss 2.7.40
		 */
		public function __construct() {
			global $wpdb;
			self::$wpdb = $wpdb;
		}

		/**
		 * Collect financial metrics from all supported plugins.
		 *
		 * @since BuddyBoss 2.7.40
		 *
		 * @return array Array of financial metrics from all plugins.
		 */
		public static function collect() {
			global $wpdb;
			self::$wpdb = $wpdb;

			$metrics = array();

			// LearnDash LMS
			if ( self::is_learndash_active() ) {
				$metrics[] = self::get_learndash_metrics();
			}

			// MemberPress
			if ( self::is_memberpress_active() ) {
				$metrics[] = self::get_memberpress_metrics();
			}

			// WooCommerce
			if ( self::is_woocommerce_active() ) {
				$woo_data                     = self::get_woocommerce_metrics();
				$metrics[ $woo_data->plugin ] = $woo_data;
			}

			// LifterLMS
			if ( self::is_lifterlms_active() ) {
				$metrics[] = self::get_lifterlms_metrics();
			}

			// Tutor LMS
			if ( self::is_tutor_lms_active() ) {
				$metrics[] = self::get_tutor_lms_metrics();
			}

			// Paid Memberships Pro
			if ( self::is_pmpro_active() ) {
				$metrics[] = self::get_pmpro_metrics();
			}

			// AffiliateWP
			if ( self::is_affiliatewp_active() ) {
				$metrics[] = self::get_affiliatewp_metrics();
			}

			return $metrics;
		}

		/**
		 * Check if LearnDash is active.
		 *
		 * @since BuddyBoss 2.7.40
		 *
		 * @return bool
		 */
		private static function is_learndash_active() {
			return function_exists( 'is_plugin_active' ) && is_plugin_active( 'sfwd-lms/sfwd_lms.php' );
		}

		/**
		 * Check if MemberPress is active.
		 *
		 * @since BuddyBoss 2.7.40
		 *
		 * @return bool
		 */
		private static function is_memberpress_active() {
			return function_exists( 'is_plugin_active' ) && is_plugin_active( 'memberpress/memberpress.php' );
		}

		/**
		 * Check if WooCommerce is active.
		 *
		 * @since BuddyBoss 2.7.40
		 *
		 * @return bool
		 */
		private static function is_woocommerce_active() {
			return function_exists( 'is_plugin_active' ) && is_plugin_active( 'woocommerce/woocommerce.php' );
		}

		/**
		 * Check if LifterLMS is active.
		 *
		 * @since BuddyBoss 2.7.40
		 *
		 * @return bool
		 */
		private static function is_lifterlms_active() {
			return function_exists( 'is_plugin_active' ) && is_plugin_active( 'lifterlms/lifterlms.php' );
		}

		/**
		 * Check if Tutor LMS is active.
		 *
		 * @since BuddyBoss 2.7.40
		 *
		 * @return bool
		 */
		private static function is_tutor_lms_active() {
			return function_exists( 'is_plugin_active' ) && is_plugin_active( 'tutor/tutor.php' );
		}

		/**
		 * Check if Paid Memberships Pro is active.
		 *
		 * @since BuddyBoss 2.7.40
		 *
		 * @return bool
		 */
		private static function is_pmpro_active() {
			return function_exists( 'is_plugin_active' ) && is_plugin_active( 'paid-memberships-pro/paid-memberships-pro.php' );
		}

		/**
		 * Check if AffiliateWP is active.
		 *
		 * @since BuddyBoss 2.7.40
		 *
		 * @return bool
		 */
		private static function is_affiliatewp_active() {
			return function_exists( 'is_plugin_active' ) && is_plugin_active( 'affiliate-wp/affiliate-wp.php' );
		}

		/**
		 * Get LearnDash financial metrics.
		 *
		 * @since BuddyBoss 2.7.40
		 *
		 * @return array
		 */
		private static function get_learndash_metrics() {
			global $wpdb;
			if ( empty( self::$wpdb ) ) {
				self::$wpdb = $wpdb;
			}

			$metrics = array(
				'plugin'        => 'learndash',
				'plugin_name'   => 'LearnDash LMS',
				'total_revenue' => 0,
				'currency'      => get_option( 'learndash_settings_payments', array() ),
				'num_orders'    => 0,
				'period'        => 'all_time',
			);

			// Get LearnDash orders from wp_posts
			$query = self::$wpdb->prepare(
				"SELECT COUNT(*) as order_count, SUM(meta_value) as total_revenue
				FROM " . self::$wpdb->posts . " p
				LEFT JOIN " . self::$wpdb->postmeta . " pm ON p.ID = pm.post_id
				WHERE p.post_type = %s
				AND p.post_status = 'publish'
				AND pm.meta_key = 'order_total'",
				'sfwd-transactions'
			);

			$result = self::$wpdb->get_row( $query );

			if ( $result ) {
				$metrics['num_orders']    = (int) $result->order_count;
				$metrics['total_revenue'] = (float) $result->total_revenue;
			}

			// Get currency from LearnDash settings
			$learndash_settings = get_option( 'learndash_settings_payments', array() );
			$metrics['currency'] = isset( $learndash_settings['currency'] ) ? $learndash_settings['currency'] : 'USD';

			return $metrics;
		}

		/**
		 * Get MemberPress financial metrics.
		 *
		 * @since BuddyBoss 2.7.40
		 *
		 * @return array
		 */
		private static function get_memberpress_metrics() {
			global $wpdb;
			if ( empty( self::$wpdb ) ) {
				self::$wpdb = $wpdb;
			}

			$metrics = array(
				'plugin'        => 'memberpress',
				'plugin_name'   => 'MemberPress',
				'total_revenue' => 0,
				'currency'      => 'USD',
				'num_orders'    => 0,
				'period'        => 'all_time',
			);

			// Check if MemberPress classes exist
			if ( class_exists( 'MeprTransaction' ) ) {
				$query = self::$wpdb->prepare(
					"SELECT COUNT(*) as order_count, SUM(amount) as total_revenue
					FROM " . self::$wpdb->prefix . "mepr_transactions
					WHERE status = %s",
					'complete'
				);

				$result = self::$wpdb->get_row( $query );

				if ( $result ) {
					$metrics['num_orders']    = (int) $result->order_count;
					$metrics['total_revenue'] = (float) $result->total_revenue;
				}
			}

			return $metrics;
		}

		/**
		 * Get WooCommerce financial metrics.
		 *
		 * @since BuddyBoss 2.7.40
		 *
		 * @return array
		 */
		private static function get_woocommerce_metrics() {
			global $wpdb;
			if ( empty( self::$wpdb ) ) {
				self::$wpdb = $wpdb;
			}

			$metrics = array(
				'plugin'        => 'woocommerce',
				'plugin_name'   => 'WooCommerce',
				'total_revenue' => 0,
				'currency'      => get_woocommerce_currency(),
				'num_orders'    => 0,
			);

			// Get WooCommerce orders.
			$query = self::$wpdb->prepare(
				"SELECT COUNT(*) as order_count, SUM(meta_value) as total_revenue
				FROM " . self::$wpdb->posts . " p
				LEFT JOIN " . self::$wpdb->postmeta . " pm ON p.ID = pm.post_id
				WHERE p.post_type = %s
				AND p.post_status IN ('wc-completed', 'wc-processing')
				AND pm.meta_key = '_order_total'",
				'shop_order'
			);

			$result = self::$wpdb->get_row( $query );

			if ( $result ) {
				$metrics['num_orders']    = (int) $result->order_count;
				$metrics['total_revenue'] = (float) $result->total_revenue;
			}

			return $metrics;
		}

		/**
		 * Get LifterLMS financial metrics.
		 *
		 * @since BuddyBoss 2.7.40
		 *
		 * @return array
		 */
		private static function get_lifterlms_metrics() {
			global $wpdb;
			if ( empty( self::$wpdb ) ) {
				self::$wpdb = $wpdb;
			}

			$metrics = array(
				'plugin'        => 'lifterlms',
				'plugin_name'   => 'LifterLMS',
				'total_revenue' => 0,
				'currency'      => get_option( 'lifterlms_currency', 'USD' ),
				'num_orders'    => 0,
				'period'        => 'all_time',
			);

			// Get LifterLMS orders
			$query = self::$wpdb->prepare(
				"SELECT COUNT(*) as order_count, SUM(meta_value) as total_revenue
				FROM " . self::$wpdb->posts . " p
				LEFT JOIN " . self::$wpdb->postmeta . " pm ON p.ID = pm.post_id
				WHERE p.post_type = %s
				AND p.post_status = 'publish'
				AND pm.meta_key = '_llms_order_total'",
				'llms_order'
			);

			$result = self::$wpdb->get_row( $query );

			if ( $result ) {
				$metrics['num_orders']    = (int) $result->order_count;
				$metrics['total_revenue'] = (float) $result->total_revenue;
			}

			return $metrics;
		}

		/**
		 * Get Tutor LMS financial metrics.
		 *
		 * @since BuddyBoss 2.7.40
		 *
		 * @return array
		 */
		private static function get_tutor_lms_metrics() {
			global $wpdb;
			if ( empty( self::$wpdb ) ) {
				self::$wpdb = $wpdb;
			}

			$metrics = array(
				'plugin'        => 'tutor_lms',
				'plugin_name'   => 'Tutor LMS',
				'total_revenue' => 0,
				'currency'      => get_option( 'tutor_currency', 'USD' ),
				'num_orders'    => 0,
				'period'        => 'all_time',
			);

			// Get Tutor LMS orders
			$query = self::$wpdb->prepare(
				"SELECT COUNT(*) as order_count, SUM(meta_value) as total_revenue
				FROM " . self::$wpdb->posts . " p
				LEFT JOIN " . self::$wpdb->postmeta . " pm ON p.ID = pm.post_id
				WHERE p.post_type = %s
				AND p.post_status = 'publish'
				AND pm.meta_key = 'tutor_order_total'",
				'tutor_order'
			);

			$result = self::$wpdb->get_row( $query );

			if ( $result ) {
				$metrics['num_orders']    = (int) $result->order_count;
				$metrics['total_revenue'] = (float) $result->total_revenue;
			}

			return $metrics;
		}

		/**
		 * Get Paid Memberships Pro financial metrics.
		 *
		 * @since BuddyBoss 2.7.40
		 *
		 * @return array
		 */
		private static function get_pmpro_metrics() {
			global $wpdb;
			if ( empty( self::$wpdb ) ) {
				self::$wpdb = $wpdb;
			}

			$metrics = array(
				'plugin'        => 'pmpro',
				'plugin_name'   => 'Paid Memberships Pro',
				'total_revenue' => 0,
				'currency'      => get_option( 'pmpro_currency', 'USD' ),
				'num_orders'    => 0,
				'period'        => 'all_time',
			);

			// Get PMPro orders
			$query = self::$wpdb->prepare(
				"SELECT COUNT(*) as order_count, SUM(total) as total_revenue
				FROM " . self::$wpdb->prefix . "pmpro_membership_orders
				WHERE status = %s",
				'success'
			);

			$result = self::$wpdb->get_row( $query );

			if ( $result ) {
				$metrics['num_orders']    = (int) $result->order_count;
				$metrics['total_revenue'] = (float) $result->total_revenue;
			}

			return $metrics;
		}

		/**
		 * Get AffiliateWP financial metrics.
		 *
		 * @since BuddyBoss 2.7.40
		 *
		 * @return array
		 */
		private static function get_affiliatewp_metrics() {
			global $wpdb;
			if ( empty( self::$wpdb ) ) {
				self::$wpdb = $wpdb;
			}

			$metrics = array(
				'plugin'        => 'affiliatewp',
				'plugin_name'   => 'AffiliateWP',
				'total_revenue' => 0,
				'currency'      => affwp_get_currency(),
				'num_orders'    => 0,
				'period'        => 'all_time',
			);

			// Get AffiliateWP referrals
			$query = self::$wpdb->prepare(
				"SELECT COUNT(*) as order_count, SUM(amount) as total_revenue
				FROM " . self::$wpdb->prefix . "affiliate_wp_referrals
				WHERE status = %s",
				'unpaid'
			);

			$result = self::$wpdb->get_row( $query );

			if ( $result ) {
				$metrics['num_orders']    = (int) $result->order_count;
				$metrics['total_revenue'] = (float) $result->total_revenue;
			}

			return $metrics;
		}

		/**
		 * Get dashboard table data for admin display.
		 *
		 * @since BuddyBoss 2.7.40
		 *
		 * @return array
		 */
		public static function get_dashboard_data() {
			global $wpdb;
			self::$wpdb = $wpdb;

			$metrics = self::collect();
			$total_revenue = 0;
			$total_orders = 0;

			foreach ( $metrics as $metric ) {
				$total_revenue += $metric['total_revenue'];
				$total_orders += $metric['num_orders'];
			}

			return array(
				'plugins'        => $metrics,
				'total_revenue'  => $total_revenue,
				'total_orders'   => $total_orders,
				'currency'       => get_option( 'woocommerce_currency', 'USD' ),
			);
		}
	}
}
