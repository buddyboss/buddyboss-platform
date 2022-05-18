<?php
/**
 * @todo add description
 *
 * @package BuddyBoss\Search
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Bp_Search_Type' ) ) :

	/**
	 * BuddyPress Global Search Type - Parent class
	 */
	abstract class Bp_Search_Type {

		/**
		 * search term. might be used later for caching purposes
		 *
		 * @var string
		 */
		protected $search_term = '';

		/**
		 * The variable to hold search results.
		 *
		 * @var array
		 */
		protected $search_results = array(
			'total_match_count' => false,
			'items'             => array(),
			'items_title'       => array(),
			'html_generated'    => false,
		);

		/*
		 Magic Methods
		 * ===================================================================
		 */

		/**
		 * A dummy magic method to prevent this class from being cloned.
		 *
		 * @since BuddyBoss 1.0.0
		 */
		public function __clone() {
			_doing_it_wrong( __FUNCTION__, __( 'Cheatin\' huh?', 'buddyboss' ), '1.7' );
		}

		/**
		 * A dummy magic method to prevent this class being unserialized.
		 *
		 * @since BuddyBoss 1.0.0
		 */
		public function __wakeup() {
			_doing_it_wrong( __FUNCTION__, __( 'Cheatin\' huh?', 'buddyboss' ), '1.7' );
		}

		/**
		 * Returns the sql query to be used in performing an 'all' items search.
		 *
		 * @param string $search_term
		 * @return string sql query
		 * @since BuddyBoss 1.0.0
		 */
		public function union_sql( $search_term ) {
			$this->search_term = $search_term;// save it for future reference may be.

			return $this->sql( $search_term );
		}

		public function add_search_item( $item_id ) {
			if ( ! in_array( $item_id, $this->search_results['items'] ) ) {
				$this->search_results['items'][ $item_id ] = '';
			}
		}

		public function get_title( $item_id ) {
			if ( ! $this->search_results['html_generated'] ) {
				$this->generate_html();
				$this->search_results['html_generated'] = true;// do once only
			}

			return isset( $this->search_results['items'][ $item_id ]['title'] ) ? $this->search_results['items'][ $item_id ]['title'] : $this->search_term;
		}

		/**
		 * Get total count for the matched result.
		 *
		 * @param string $search_term Search term will be like whatever enter for search.
		 * @param string $search_type
		 *
		 * @return mixed|string|null
		 */
		public function get_total_match_count( $search_term, $search_type = '' ) {
			$this->search_term = $search_term;// save it for future reference may be.

			global $wpdb;
			static $bbp_search_term = array();
			$cache_key = 'bb_search_term_total_match_count_' . sanitize_title( $search_term );
			if ( ! empty( $search_type ) ) {
				$cache_key .= sanitize_title( $search_type );
			}
			if ( ! isset( $bbp_search_term[ $cache_key ] ) ) {
				$sql    = $this->sql( $search_term, true );
				$result = $wpdb->get_var( $sql );

				$bbp_search_term[ $cache_key ] = $result;
			} else {
				$result = $bbp_search_term[ $cache_key ];
			}

			return $result;
		}

		/**
		 * This function must be overriden by inheritingn classes
		 *
		 * @param string  $search_term
		 * @param boolean $only_totalrow_count
		 */
		abstract function sql( $search_term, $only_totalrow_count = false );

		/**
		 * Get the html for given search result.
		 *
		 * @param int    $itemid
		 * @param string $template_type Optional
		 * @return string
		 */
		public function get_html( $itemid, $template_type = '' ) {
			if ( ! $this->search_results['html_generated'] ) {
				$this->generate_html( $template_type );
				$this->search_results['html_generated'] = true;// do once only
			}

			return isset( $this->search_results['items'][ $itemid ] ) && is_array( $this->search_results['items'][ $itemid ] ) ? $this->search_results['items'][ $itemid ]['html'] : '';
		}
	}

	// End class Bp_Search_Type

endif;

