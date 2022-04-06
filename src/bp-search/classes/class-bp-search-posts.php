<?php
/**
 * @todo add description
 *
 * @package BuddyBoss\Search
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Bp_Search_Posts' ) ) :

	/**
	 * BuddyPress Global Search  - search posts class
	 */
	class Bp_Search_Posts extends Bp_Search_Type {
		private $pt_name;
		private $search_type;

		/**
		 * A real constructor. Since we do want multiple copies of this class.
		 * The idea is to have one object for each searchable custom post type.
		 *
		 * @since BuddyBoss 1.0.0
		 */
		public function __construct( $pt_name, $search_type ) {
			$this->pt_name     = $pt_name;
			$this->search_type = $search_type;

			add_action( "bp_search_settings_item_{$this->search_type}", array( $this, 'print_search_options' ) );
		}


		public function sql( $search_term, $only_totalrow_count = false ) {
			/*
			An example UNION query :-
			-----------------------------------------------------
			(
				SELECT
					wp_posts.id , 'posts' as type, wp_posts.post_title LIKE '%ho%' AS relevance, wp_posts.post_date as entry_date
				FROM
					wp_posts
				WHERE
					1=1
					AND (
							(
									(wp_posts.post_title LIKE '%ho%')
								OR 	(wp_posts.post_content LIKE '%ho%')
							)
						)
					AND wp_posts.post_type IN ('post', 'page', 'attachment')
					AND (
						wp_posts.post_status = 'publish'
						OR wp_posts.post_author = 1
						AND wp_posts.post_status = 'private'
					)
			)
			----------------------------------------------------
			*/
			global $wpdb;

			$select      = '';
			$where       = '';
			$placeholder = '%' . $search_term . '%';
			$tax         = array();

			// Get the search words from the search string.
			$search_term_array = $this->get_search_terms( $search_term );

			// Get taxonomy.
			$taxonomies = get_object_taxonomies( $this->pt_name );
			foreach ( $taxonomies as $taxonomy ) {
				if ( bp_is_search_post_type_taxonomy_enable( $taxonomy, $this->pt_name ) ) {
					$tax[] = $taxonomy;
				}
			}

			$select .= ' SELECT ';

			if ( $only_totalrow_count ) {
				$select .= ' COUNT( DISTINCT id ) ';
			} else {
				$select .= $wpdb->prepare( ' DISTINCT id , %s as type, post_title LIKE %s AS relevance, post_date as entry_date ', $this->search_type, $placeholder );
			}

			$select .= " FROM {$wpdb->posts} p ";

			// Tax query left join.
			if ( ! empty( $tax ) ) {
				$select .= " LEFT JOIN {$wpdb->term_relationships} r ON p.ID = r.object_id ";
			}

			// WHERE.
			$where .= ' WHERE 1=1 AND (';
			$where .= $this->parse_search_query( $search_term_array );

			// Tax query.
			if ( ! empty( $tax ) ) {

				$tax_in_arr = array_map(
					function( $t_name ) {
						return "'" . $t_name . "'";
					},
					$tax
				);

				$tax_in = implode( ', ', $tax_in_arr );

				$where .= $wpdb->prepare( " OR  r.term_taxonomy_id IN (SELECT tt.term_taxonomy_id FROM {$wpdb->term_taxonomy} tt INNER JOIN {$wpdb->terms} t ON t.term_id = tt.term_id WHERE ( t.slug LIKE %s OR t.name LIKE %s ) AND  tt.taxonomy IN (%s) )", $placeholder, $placeholder, $tax_in );
			}

			// Meta query.
			if ( bp_is_search_post_type_meta_enable( $this->pt_name ) ) {
				$where .= $this->parse_search_meta_query( $search_term_array );
			}

			// Post should be published.
			$where .= ") AND p.post_type = '{$this->pt_name}' AND p.post_status = 'publish' ";

			$sql_query = "{$select}{$where}";

			return apply_filters(
				'Bp_Search_Posts_sql',
				$sql_query,
				array(
					'search_term'         => $search_term,
					'only_totalrow_count' => $only_totalrow_count,
				)
			);
		}

		protected function generate_html( $template_type = '' ) {
			$post_ids = array();
			foreach ( $this->search_results['items'] as $item_id => $item_html ) {
				$post_ids[] = $item_id;
			}

			// now we have all the posts
			// lets do a wp_query and generate html for all posts
			$qry = new WP_Query(
				array(
					'post_type' => $this->pt_name,
					'post__in'  => $post_ids,
				)
			);

			if ( $qry->have_posts() ) {
				while ( $qry->have_posts() ) {
					$qry->the_post();
					$result = array(
						'id'    => get_the_ID(),
						'type'  => $this->search_type,
						'title' => get_the_title(),
						'html'  => bp_search_buffer_template_part( 'loop/post', $template_type, false ),
					);

					$this->search_results['items'][ get_the_ID() ] = $result;
				}
			}
			wp_reset_postdata();
		}


		/**
		 * What taxonomy should be searched on?
		 * Should search on the Post Meta?
		 *
		 * Prints options to search through all registered taxonomies with give
		 * post type e.g $this->cpt_name
		 *
		 * Print options to search through Post Meta
		 */
		function print_search_options( $items_to_search ) {
			global $wp_taxonomies;
			echo "<div class='wp-posts-fields' style='margin: 10px 0 10px 30px'>";

			/**  Post Meta Field */

			$label   = sprintf( __( '%s Meta', 'buddyboss' ), ucfirst( $this->pt_name ) );
			$item    = 'post_field_' . $this->pt_name . '_meta';
			$checked = ! empty( $items_to_search ) && in_array( $item, $items_to_search ) ? ' checked' : '';

			echo "<label><input type='checkbox' value='{$item}' name='bp_search_plugin_options[items-to-search][]' {$checked}>{$label}</label><br>";

			/** Post Taxonomies Fields */
			$pt_taxonomy = get_object_taxonomies( $this->pt_name );

			foreach ( $pt_taxonomy as $tax ) {

				$label   = ucwords( str_replace( '_', ' ', $tax ) );
				$value   = $this->search_type . '-tax-' . $tax;
				$checked = ! empty( $items_to_search ) && in_array( $value, $items_to_search ) ? ' checked' : '';

				echo "<label><input type='checkbox' value='{$value}' name='bp_search_plugin_options[items-to-search][]' {$checked}>{$label}</label><br>";
			}

			echo '</div><!-- .wp-user-fields -->';
		}

		public function parse_search_query( $search_terms = array() ) {
			global $wpdb;

			$search    = '';
			$searchand = '';

			if ( empty( $search_terms ) ) {
				return $search;
			}

			foreach ( $search_terms as $term ) {
				$like      = '%' . $wpdb->esc_like( $term ) . '%';
				$search   .= $searchand . $wpdb->prepare( '((p.post_title LIKE %s) OR (p.post_excerpt LIKE %s) OR (p.post_content LIKE %s))', $like, $like, $like );
				$searchand = ' AND ';
			}

			return $search;
		}

		public function parse_search_meta_query( $search_terms = array() ) {
			global $wpdb;

			$meta_query = '';
			$meta_where = '';
			$searchand  = '';

			if ( empty( $search_terms ) ) {
				return $meta_query;
			}

			foreach ( $search_terms as $term ) {
				$like        = '%' . $wpdb->esc_like( $term ) . '%';
				$meta_where .= $wpdb->prepare( "{$searchand}( meta_value LIKE %s )", $like );
				$searchand   = ' AND ';
			}

			if ( ! empty( $meta_where ) ) {
				$meta_query = " OR p.ID IN ( SELECT post_id FROM {$wpdb->postmeta} WHERE ({$meta_where}) )";
			}

			return $meta_query;
		}

		public function get_search_terms( $search_term = '' ) {
			$search_term_array = array();

			if ( empty( $search_term ) ) {
				return $search_term_array;
			}

			// There are no line breaks in <input /> fields.
			$search_term = str_replace( array( "\r", "\n" ), '', stripslashes( $search_term ) );

			if ( preg_match_all( '/".*?("|$)|((?<=[\t ",+])|^)[^\t ",+]+/', $search_term, $matches ) ) {
				$search_term_array = $this->parse_search_terms( $matches[0] );

				// If the search string has only short terms or stopwords, or is 10+ terms long, match it as sentence.
				if ( empty( $search_term_array ) || count( $search_term_array ) > 9 ) {
					$search_term_array = array( $search_term );
				}
			} else {
				$search_term_array = array( $search_term );
			}

			return $search_term_array;
		}

		public function parse_search_terms( $terms ) {
			$strtolower = function_exists( 'mb_strtolower' ) ? 'mb_strtolower' : 'strtolower';
			$checked    = array();

			$stopwords = $this->get_search_stopwords();

			foreach ( $terms as $term ) {
				// Keep before/after spaces when term is for exact match.
				if ( preg_match( '/^".+"$/', $term ) ) {
					$term = trim( $term, "\"'" );
				} else {
					$term = trim( $term, "\"' " );
				}

				// Avoid single A-Z and single dashes.
				if ( ! $term || ( 1 === strlen( $term ) && preg_match( '/^[a-z\-]$/i', $term ) ) ) {
					continue;
				}

				if ( in_array( call_user_func( $strtolower, $term ), $stopwords, true ) ) {
					continue;
				}

				$checked[] = $term;
			}

			return $checked;
		}

		public function get_search_stopwords() {
			static $stoped_keywords = array();

			if ( ! empty( $stoped_keywords ) ) {
				return $stoped_keywords;
			}

			/*
			 * translators: This is a comma-separated list of very common words that should be excluded from a search,
			 * like a, an, and the. These are usually called "stopwords". You should not simply translate these individual
			 * words into your language. Instead, look for and provide commonly accepted stopwords in your language.
			 */
			$words = explode(
				',',
				_x(
					'about,an,are,as,at,be,by,com,for,from,how,in,is,it,of,on,or,that,the,this,to,was,what,when,where,who,will,with,www',
					'Comma-separated list of search stopwords in your language'
				)
			);

			foreach ( $words as $word ) {
				$word = trim( $word, "\r\n\t " );
				if ( $word ) {
					$stoped_keywords[] = $word;
				}
			}

			return $stoped_keywords;
		}

	}

	// End class Bp_Search_Posts

endif;

