<?php
/**
 * BuddyBoss global search main classes.
 *
 * @package BuddyBoss\Activity
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Bp_Search_Helper' ) ) :

	/**
	 * BuddyPress Global Search Main Controller class
	 */
	class BP_Search {
		/**
		 * The variable to hold the helper class objects for each type of searches.
		 * E.g
		 * [
		 *    'posts' => an object of Bp_Search_Posts,
		 *  'groups' => an object of Bp_Search_Groups
		 * ]
		 *
		 * @var array
		 * @since BuddyBoss 1.0.0
		 */
		public $search_helpers = array();

		public $searchable_items;

		/**
		 * The variable to hold arguments used for search.
		 * It will be used by other methods later on.
		 *
		 * @var array
		 */
		public $search_args = array();

		/**
		 * The variable to hold search results.
		 * The results will be grouped into different types(e.g: posts, members, etc..)
		 *
		 * The structure of array after being populated should be:
		 *        'posts'        => [
		 *            'total_match_count'    => 34,
		 *            'items'                =>
		 *        ],
		 *        'members'    =>
		 *
		 * @var array
		 */
		public $search_results = array();

		/**
		 * Insures that only one instance of Class exists in memory at any
		 * one time. Also prevents needing to define globals all over the place.
		 *
		 * @since BuddyBoss 1.0.0
		 *
		 * @see BP_Search::instance()
		 *
		 * @return object bp_search_Plugin
		 */
		public static function instance() {
			// Store the instance locally to avoid private static replication
			static $instance = null;

			// Only run these methods if they haven't been run previously
			if ( null === $instance ) {
				$instance = new self();

				// create instances of helpers and associate them with types
				add_action( 'init', array( $instance, 'load_search_helpers' ), 80 );

				add_action( 'wp_ajax_bp_search_ajax', array( $instance, 'ajax_search' ) );
				add_action( 'wp_ajax_nopriv_bp_search_ajax', array( $instance, 'ajax_search' ) );
			}

			// Always return the instance
			return $instance;
		}

		/*
		 Magic Methods
		 * ===================================================================
		 */

		/**
		 * A dummy constructor to prevent this class from being loaded more than once.
		 *
		 * @since BuddyBoss 1.0.0
		 */
		private function __construct() {
			/* Do nothing here */
		}

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
		 * @todo add title/description
		 *
		 * @since BuddyBoss 1.0.0
		 */
		public function load_search_helpers() {
			global $bp;

			// load the helper type parent class.
			require_once $bp->plugin_dir . 'bp-search/classes/class-bp-search-types.php';

			// load and associate helpers one by one.
			if ( bp_is_search_post_type_enable( 'post' ) ) {
				require_once $bp->plugin_dir . 'bp-search/classes/class-bp-search-posts.php';
				$this->search_helpers['posts'] = new Bp_Search_Posts( 'post', 'posts' );
				$this->searchable_items[]      = 'posts';
			}

			if ( bp_is_search_post_type_enable( 'page' ) ) {
				require_once $bp->plugin_dir . 'bp-search/classes/class-bp-search-posts.php';
				$this->search_helpers['pages'] = new Bp_Search_Posts( 'page', 'pages' );
				$this->searchable_items[]      = 'pages';
			}

			if ( bp_is_active( 'forums' ) && bp_is_search_post_type_enable( bbp_get_forum_post_type() ) ) {

				require_once $bp->plugin_dir . 'bp-search/classes/class-bp-search-bbpress.php';

				if ( bp_is_search_post_type_enable( bbp_get_forum_post_type() ) ) {
					require_once $bp->plugin_dir . 'bp-search/classes/class-bp-search-bbpress-forums.php';
					$this->search_helpers['forum'] = Bp_Search_bbPress_Forums::instance();
					$this->searchable_items[]      = 'forum';
				}

				if ( bp_is_search_post_type_enable( bbp_get_topic_post_type() ) ) {
					require_once $bp->plugin_dir . 'bp-search/classes/class-bp-search-bbpress-forums-topics.php';
					$this->search_helpers['topic'] = Bp_Search_bbPress_Topics::instance();
					$this->searchable_items[]      = 'topic';
				}

				if ( bp_is_search_post_type_enable( bbp_get_reply_post_type() ) ) {
					require_once $bp->plugin_dir . 'bp-search/classes/class-bp-search-bbpress-forums-replies.php';
					$this->search_helpers['reply'] = Bp_Search_bbPress_Replies::instance();
					$this->searchable_items[]      = 'reply';
				}
			}

			// Check BuddyPress is active.
			if ( bp_is_search_members_enable() ) {
				require_once $bp->plugin_dir . 'bp-search/classes/class-bp-search-members.php';
				$this->search_helpers['members'] = Bp_Search_Members::instance();
				$this->searchable_items[]        = 'members';
			}

			if ( bp_is_active( 'groups' ) && bp_is_search_groups_enable() ) {
				require_once $bp->plugin_dir . 'bp-search/classes/class-bp-search-groups.php';
				$this->search_helpers['groups'] = Bp_Search_Groups::instance();
				$this->searchable_items[]       = 'groups';
			}

			if ( bp_is_active( 'media' ) && bp_is_search_photos_enable() ) {
				require_once $bp->plugin_dir . 'bp-search/classes/class-bp-search-media.php';
				$this->search_helpers['photos'] = Bp_Search_Media::instance();
				$this->searchable_items[]       = 'photos';
			}

			if ( bp_is_active( 'media' ) && bp_is_search_albums_enable() ) {
				require_once $bp->plugin_dir . 'bp-search/classes/class-bp-search-albums.php';
				$this->search_helpers['albums'] = Bp_Search_Albums::instance();
				$this->searchable_items[]       = 'albums';
			}

			if ( bp_is_active( 'media' ) && bp_is_search_documents_enable() && ( bp_is_group_document_support_enabled() || bp_is_profile_document_support_enabled() ) ) {
				require_once $bp->plugin_dir . 'bp-search/classes/class-bp-search-documents.php';
				$this->search_helpers['documents'] = Bp_Search_Documents::instance();
				$this->searchable_items[]          = 'documents';
			}

			if ( bp_is_active( 'media' ) && bp_is_active( 'video' ) && bp_is_search_videos_enable() && ( bp_is_group_video_support_enabled() || bp_is_profile_video_support_enabled() ) ) {
				require_once $bp->plugin_dir . 'bp-search/classes/class-bp-search-video.php';
				$this->search_helpers['videos'] = Bp_Search_Video::instance();
				$this->searchable_items[]       = 'videos';
			}

			if ( bp_is_active( 'media' ) && bp_is_search_folders_enable() && ( bp_is_group_document_support_enabled() || bp_is_profile_document_support_enabled() ) ) {
				require_once $bp->plugin_dir . 'bp-search/classes/class-bp-search-folders.php';
				$this->search_helpers['folders'] = Bp_Search_Folders::instance();
				$this->searchable_items[]        = 'folders';
			}

			if ( bp_is_active( 'activity' ) && bp_is_search_activity_enable() ) {
				require_once $bp->plugin_dir . 'bp-search/classes/class-bp-search-activities.php';
				$this->search_helpers['activity'] = Bp_Search_Activities::instance();
				$this->searchable_items[]         = 'activity';

				if ( bp_is_search_activity_comments_enable() ) {
					require_once $bp->plugin_dir . 'bp-search/classes/class-bp-search-activity-comments.php';
					$this->search_helpers['activity_comment'] = Bp_Search_Activity_Comment::instance();
					$this->searchable_items[]                 = 'activity_comment';

				}
			}

			/**
			 * Hook to load helper classes for additional search types.
			 */
			$additional_search_helpers = apply_filters( 'bp_search_additional_search_helpers', array() );
			if ( ! empty( $additional_search_helpers ) ) {
				foreach ( $additional_search_helpers as $search_type => $helper_object ) {
					/**
					 * All helper classes must inherit from bp_search_Type
					 */
					if ( ! isset( $this->search_helpers[ $search_type ] ) && is_a( $helper_object, 'BP_Search_Type' ) ) {
						$this->search_helpers[ $search_type ] = $helper_object;
					}
				}
			}

		}

		public function ajax_search() {
			check_ajax_referer( 'bp_search_ajax', 'nonce' );

			if ( isset( $_POST['view'] ) && $_POST['view'] == 'content' ) {

				$_GET['s'] = $_POST['s'];
				if ( ! empty( $_POST['subset'] ) ) {
					$_GET['subset'] = $_POST['subset'];
				}

				if ( ! empty( $_POST['list'] ) ) {
					$_GET['list'] = $_POST['list'];
				}

				$content = '';

				self::instance()->prepare_search_page();
				$content = bp_search_buffer_template_part( 'results-page-content', '', false );

				echo $content;

				die();
			}

			$args = array(
				'search_term'   => $_REQUEST['search_term'],
				// How many results should be displyed in autosuggest?
				// @todo: give a settings field for this value
				'ajax_per_page' => $_REQUEST['per_page'],
				'count_total'   => true,
				'template_type' => 'ajax',
			);

			if ( isset( $_REQUEST['forum_search_term'] ) ) {
				$args['forum_search'] = true;
			}

			$this->do_search( $args );

			$search_results = array();
			if ( isset( $this->search_results['all']['items'] ) && ! empty( $this->search_results['all']['items'] ) ) {
				/*
				 ++++++++++++++++++++++++++++++++++
				group items of same type together
				++++++++++++++++++++++++++++++++++ */
				$types = array();
				foreach ( $this->search_results['all']['items'] as $item_id => $item ) {
					$type = $item['type'];
					if ( empty( $types ) || ! in_array( $type, $types ) ) {
						$types[] = $type;
					}
				}

				$new_items = array();
				foreach ( $types as $type ) {
					$first_html_changed = false;
					foreach ( $this->search_results['all']['items'] as $item_id => $item ) {
						if ( $item['type'] != $type ) {
							continue;
						}

						// Filter by default will be false.
						$bp_search_group_or_type_title = apply_filters( 'bp_search_group_or_type_title', false );
						if ( true === $bp_search_group_or_type_title ) {
							// add group/type title in first one.
							if ( ! $first_html_changed ) {
								// this filter can be used to change display of 'posts' to 'Blog Posts' etc..
								$label              = apply_filters( 'bp_search_label_search_type', $type );
								$item['html']       = "<div class='results-group results-group-{$type}'><span class='results-group-title'>{$label}</span></div>" . $item['html'];
								$first_html_changed = true;
							}
						}

						$new_items[ $item_id ] = $item;
					}
				}

				$this->search_results['all']['items'] = $new_items;

				/* _______________________________ */
				$url = $this->search_page_search_url( false );

				if ( true === $this->search_args['forum_search'] ) {
					$url = $url;
				} else {
					$url = esc_url(
						add_query_arg(
							array(
								'view'      => 'content',
								'no_frame'  => '1',
								'bp_search' => 1,
							),
							$url
						)
					);
				}

				$type_mem = '';
				foreach ( $this->search_results['all']['items'] as $item_id => $item ) {
					$new_row               = array( 'value' => $item['html'] );
					$type_label            = apply_filters( 'bp_search_label_search_type', $item['type'] );
					$new_row['type']       = $item['type'];
					$new_row['type_label'] = '';
					$new_row['value']      = $item['html'];
					if ( isset( $item['title'] ) ) {
						$new_row['label'] = $item['title'];
					}

					// Filter by default will be false.
					$bp_search_raw_type = apply_filters( 'bp_search_raw_type', false );

					if ( true === $bp_search_raw_type ) {
						if ( $type_mem != $new_row['type'] ) {
							$type_mem              = $new_row['type'];
							$cat_row               = $new_row;
							$cat_row['type']       = $item['type'];
							$cat_row['type_label'] = $type_label;
							$category_search_url   = esc_url( add_query_arg( array( 'subset' => $item['type'] ), $url ) );
							$html                  = "<span><a href='" . esc_url( $category_search_url ) . "'>" . $type_label . '</a></span>';
							$cat_row['value']      = apply_filters( 'buddypress_gs_autocomplete_category', $html, $item['type'], $url, $type_label );
							$search_results[]      = $cat_row;
						}
					}

					$search_results[] = $new_row;
				}

				// Show "View All" link.
				if ( absint( $this->search_results['all']['total_match_count'] ) > absint( bp_search_get_form_option( 'bp_search_number_of_results', 5 ) ) ) {
					$all_results_row  = array(
						'value'      => "<div class='bp-search-ajax-item allresults'><a href='" . esc_url( $url ) . "'>" . __( 'View all', 'buddyboss' ) . '</a></div>',
						'type'       => 'view_all_type',
						'type_label' => '',
					);
					$search_results[] = $all_results_row;
				}
			} else {
				// @todo give a settings screen for this field
				$search_results[] = array(
					'value' => '<div class="bp-search-ajax-item ui-state-disabled noresult">' .
						esc_html__( 'No results found.', 'buddyboss' ) .
					'</div>',
					'label' => $this->search_args['search_term'],
				);
			}

			die( json_encode( $search_results ) );
		}

		/**
		 * Perform search and generate search results
		 *
		 * @param mixed $args
		 *
		 * @since BuddyBoss 1.0.0
		 */
		public function do_search( $args = '' ) {
			global $wpdb;

			$args = $this->sanitize_args( $args );

			$defaults = array(
				// the search term
				'search_term'   => '',
				// Restrict search results to only this subset. eg: posts, members, groups, etc.
				// See Setting > what to search?
				'search_subset' => 'all',
				//
				// What all to search for. e.g: members.
				// See Setting > what to search?
				// The options passed here must be a subset of all options available on Setting > what to search, nothing extra can be passed here.
				//
				// This is different from search_subset.
				// If search_subset is 'all', then search is performed for all searchable items.
				// If search_subset is 'members' then only total match count for other searchable_items is calculated( so that it can be displayed in tabs)
				// members(23) | posts(201) | groups(2) and so on.
				// 'searchable_items' => BP_Search::instance()->option( 'items-to-search' ),
				// how many search results to display per page
				'per_page'      => 20,
				// current page
				'current_page'  => 1,
				// should we calculate total match count for all different types?
				// it should be set to false while calling this function for ajax search
				'count_total'   => true,
				// template type to load for each item
				// search results will be styled differently(minimal) while in ajax search
				// options ''|'minimal'
				'template_type' => '',
				'forum_search'  => false,
				'number'        => 3,
			);

			$args = bp_parse_args( $args, $defaults );

			if ( true === $args['forum_search'] ) {
				$this->searchable_items = array( 'forum', 'topic', 'reply' );
			}

			$this->search_args = $args;// save it for using in other methods

			// bail out if nothing to search for
			if ( ! $args['search_term'] ) {
				return;
			}

			$total = array();

			if ( 'all' == $args['search_subset'] ) {

				/**
				 * 1. Generate a 'UNION' sql query for all searchable items with only ID, RANK, TYPE(posts|members|..) as columns, order by RANK DESC.
				 * 3. Generate html for each of them
				 */
				/*
				 an example UNION query :-
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
				UNION
				(
					SELECT
						DISTINCT g.id, 'groups' as type, g.name LIKE '%ho%' AS relevance, gm2.meta_value as entry_date
					FROM
						wp_bp_groups_groupmeta gm1, wp_bp_groups_groupmeta gm2, wp_bp_groups g
					WHERE
						1=1
						AND g.id = gm1.group_id
						AND g.id = gm2.group_id
						AND gm2.meta_key = 'last_activity'
						AND gm1.meta_key = 'total_member_count'
						AND ( g.name LIKE '%ho%' OR g.description LIKE '%ho%' )
				)

				ORDER BY
					relevance DESC, entry_date DESC LIMIT 0, 10
				----------------------------------------------------
				*/

				$sql_queries = array();

				if ( empty( $this->searchable_items ) && ! is_array( $this->searchable_items ) ) {
					return;
				}

				foreach ( $this->searchable_items as $search_type ) {
					if ( ! isset( $this->search_helpers[ $search_type ] ) ) {
						continue;
					}

					/**
					 * the following variable will be an object of current search type helper class
					 * e.g: an object of Bp_Search_Groups or Bp_Search_Posts etc.
					 * so we can safely call the public methods defined in those classes.
					 * This also means that all such classes must have a common set of methods.
					 */
					$obj                   = $this->search_helpers[ $search_type ];
					$limit                 = isset( $_REQUEST['view'] ) ? ' LIMIT ' . ( $args['number'] ) : '';
					$sql_queries[]         = '( ' . $obj->union_sql( $args['search_term'] ) . " ORDER BY relevance DESC, entry_date DESC $limit ) ";
					$total[ $search_type ] = $obj->get_total_match_count( $args['search_term'], $search_type );
				}

				if ( empty( $sql_queries ) ) {
					// thigs will get messy if program reaches here!!
					return;
				}

				$pre_search_query = implode( ' UNION ', $sql_queries );

				if ( isset( $args['ajax_per_page'] ) && $args['ajax_per_page'] > 0 ) {
					$pre_search_query .= " LIMIT {$args['ajax_per_page']} ";
				}

				$results = $wpdb->get_results( $pre_search_query );
				/*
				 $results will have a structure like below */
				/*
				id | type | relevance | entry_date
				45 | groups | 1 | 2014-10-28 17:05:18
				40 | posts | 1 | 2014-10-26 13:52:06
				4 | groups | 0 | 2014-10-21 15:15:36
				*/
				$results = apply_filters( 'bp_search_query_results', $results, $this );

				if ( ! empty( $results ) ) {
					$this->search_results['all'] = array(
						'total_match_count' => 0,
						'items'             => array(),
						'items_title'       => array(),
					);
					// segregate items of a type together and pass it to corresponsing search handler, so that an aggregate query can be done
					// e.g one single WordPress loop can be done for all posts

					foreach ( $results as $item ) {
						$obj = $this->search_helpers[ $item->type ];
						$obj->add_search_item( $item->id );
					}

					// now get html for each item
					foreach ( $results as $item ) {

						$obj = $this->search_helpers[ $item->type ];

						$result = array(
							'id'    => $item->id,
							'type'  => $item->type,
							'html'  => $obj->get_html( $item->id, $args['template_type'] ),
							'title' => $obj->get_title( $item->id ),
						);

						$this->search_results['all']['items'][ $item->type . '_' . $item->id ] = $result;
					}
					// now we've html saved for search results

					if ( ! empty( $this->search_results['all']['items'] ) && $args['template_type'] != 'ajax' ) {
						/*
						 ++++++++++++++++++++++++++++++++++
						group items of same type together
						++++++++++++++++++++++++++++++++++ */
						// create another copy, of items, this time, items of same type grouped together
						$ordered_items_group = array();
						foreach ( $this->search_results['all']['items'] as $item_id => $item ) {
							$type = $item['type'];
							if ( ! isset( $ordered_items_group[ $type ] ) ) {
								$ordered_items_group[ $type ] = array();
							}
							$item_id                                  = absint( str_replace( $type . '_', '', $item_id ) );
							$ordered_items_group[ $type ][ $item_id ] = $item;
						}

						$search_items = bp_search_items();
						$search_url   = $this->search_page_search_url();

						foreach ( $ordered_items_group as $type => &$items ) {
							// now prepend html (opening tags) to first item of each
							$category_search_url = esc_url(
								add_query_arg(
									array(
										'subset'    => $type,
										'bp_search' => 1,
									),
									$search_url
								)
							);
							$label               = isset( $search_items[ $type ] ) ? trim( $search_items[ $type ] ) : trim( $type );
							$first_item          = reset( $items );
							$total_results       = $total[ $type ];
							$start_html          = "<div class='results-group results-group-{$type} " . apply_filters( 'bp_search_class_search_wrap', 'bp-search-results-wrap', $label ) . "'>"
										  . "<header class='results-group-header clearfix'>"
										  . "<h3 class='results-group-title'><span>" . apply_filters( 'bp_search_label_search_type', $label ) . '</span></h3>'
										  . "<span class='total-results'>" . sprintf( _n( '%d result', '%d results', $total_results, 'buddyboss' ), $total_results ) . '</a>'
										  . '</header>'
										  . "<ul id='{$type}-stream' class='item-list {$type}-list bp-list " . apply_filters( 'bp_search_class_search_list', 'bp-search-results-list', $label ) . "'>";

							$group_start_html = apply_filters( 'bp_search_results_group_start_html', $start_html, $type );

							$first_item['html']         = $group_start_html . $first_item['html'];
							$items[ $first_item['id'] ] = $first_item;

							// and append html (closing tags) to last item of each type
							$last_item = end( $items );
							$end_html  = '</ul>';

							if ( $total_results > $args['number'] ) {
								$end_html .= "<footer class='results-group-footer'>";
								$end_html .= "<a href='" . $category_search_url . "' class='view-all-link'>" .
											   sprintf( esc_html__( 'View (%d) more', 'buddyboss' ), $total_results - $args['number'] ) .
											 '</a>';
								$end_html .= '</footer>';
							}

							$end_html .= '</div>';

							$group_end_html = apply_filters( 'bp_search_results_group_end_html', $end_html, $type );

							$last_item['html']         = $last_item['html'] . $group_end_html;
							$items[ $last_item['id'] ] = $last_item;
						}

						// replace orginal items with this new, grouped set of items
						$this->search_results['all']['items'] = array();
						foreach ( $ordered_items_group as $type => $grouped_items ) {

							// Remove last item from list
							if ( count( $grouped_items ) > $args['number'] ) {
								array_pop( $grouped_items );
							}

							foreach ( $grouped_items as $item_id => $item ) {
								$this->search_results['all']['items'][ $type . '_' . $item_id ] = $item;
							}
						}
						/* ________________________________ */
					}
				}
			} else {
				// if subset not in searchable items, bail out.
				if ( ! in_array( $args['search_subset'], $this->searchable_items ) ) {
					return;
				}

				if ( ! isset( $this->search_helpers[ $args['search_subset'] ] ) ) {
					return;
				}

				/**
				 * 1. Search top top 20( $args['per_page'] ) item( posts|members|..)
				 * 2. Generate html for each of them
				 */
				// $args['per_page'] = get_option( 'posts_per_page' );
				$obj              = $this->search_helpers[ $args['search_subset'] ];
				$pre_search_query = $obj->union_sql( $args['search_term'] ) . ' ORDER BY relevance DESC, entry_date DESC ';

				if ( $args['per_page'] > 0 ) {
					$offset            = ( $args['current_page'] * $args['per_page'] ) - $args['per_page'];
					$pre_search_query .= " LIMIT {$offset}, {$args['per_page']} ";
				}

				$results = $wpdb->get_results( $pre_search_query );

				/*
				 $results will have a structure like below */
				/*
				id | type | relevance | entry_date
				45 | groups | 1 | 2014-10-28 17:05:18
				40 | posts | 1 | 2014-10-26 13:52:06
				4 | groups | 0 | 2014-10-21 15:15:36
				*/
				$results = apply_filters( 'bp_search_query_results', $results, $this );

				if ( ! empty( $results ) ) {
					$obj = $this->search_helpers[ $args['search_subset'] ];
					$this->search_results[ $args['search_subset'] ] = array(
						'total_match_count' => 0,
						'items'             => array(),
					);
					// segregate items of a type together and pass it to corresponsing search handler, so that an aggregate query can be done
					// e.g one single WordPress loop can be done for all posts
					foreach ( $results as $item ) {
						$obj->add_search_item( $item->id );
					}

					// now get html for each item
					foreach ( $results as $item ) {
						$html = $obj->get_html( $item->id, $args['template_type'] );

						$result = array(
							'id'    => $item->id,
							'type'  => $args['search_subset'],
							'html'  => $obj->get_html( $item->id, $args['template_type'] ),
							'title' => $obj->get_title( $item->id ),
						);

						$this->search_results[ $args['search_subset'] ]['items'][ $item->id ] = $result;
					}

					// now prepend html (opening tags) to first item of each type
					$first_item = reset( $this->search_results[ $args['search_subset'] ]['items'] );
					$start_html = "<div class='results-group results-group-{$args['search_subset']} " . apply_filters( 'bp_search_class_search_wrap', 'bp-search-results-wrap', $args['search_subset'] ) . "'>"
								  . "<ul id='{$args['search_subset']}-stream' class='item-list {$args['search_subset']}-list bp-list " . apply_filters( 'bp_search_class_search_list', 'bp-search-results-list', $args['search_subset'] ) . "'>";

					$group_start_html = apply_filters( 'bp_search_results_group_start_html', $start_html, $args['search_subset'] );

					$first_item['html'] = $group_start_html . $first_item['html'];
					$this->search_results[ $args['search_subset'] ]['items'][ $first_item['id'] ] = $first_item;

					// and append html (closing tags) to last item of each type
					$last_item = end( $this->search_results[ $args['search_subset'] ]['items'] );
					$end_html  = '</ul></div>';

					$group_end_html = apply_filters( 'bp_search_results_group_end_html', $end_html, $args['search_subset'] );

					$last_item['html'] = $last_item['html'] . $group_end_html;
					$this->search_results[ $args['search_subset'] ]['items'][ $last_item['id'] ] = $last_item;
				}
			}

			// html for search results is generated.
			// now, lets calculate the total number of search results, for all different types
			if ( $args['count_total'] ) {
				$all_items_count = 0;
				foreach ( $this->searchable_items as $search_type ) {
					if ( ! isset( $this->search_helpers[ $search_type ] ) ) {
						continue;
					}

					if ( ! isset( $total[ $search_type ] ) ) {
						$obj               = $this->search_helpers[ $search_type ];
						$total_match_count = $obj->get_total_match_count( $this->search_args['search_term'], $search_type );
						$this->search_results[ $search_type ]['total_match_count'] = (int) $total_match_count;
					} else {
						$this->search_results[ $search_type ]['total_match_count'] = (int) $total[ $search_type ];
					}

					$all_items_count += $this->search_results[ $search_type ]['total_match_count'];
				}

				$this->search_results['all']['total_match_count'] = $all_items_count;
			}

			/**
			 * Filters out the search results.
			 *
			 * @since BuddyBoss 2.3.50
			 *
			 * @param array  $search_results Array of search results.
			 * @param object $this           Object of BP_Search class.
			 */
			$this->search_results = apply_filters( 'bp_search_query_final_results', $this->search_results, $this );
		}

		/**
		 * setup everything before starting to display content for search page.
		 */
		public function prepare_search_page() {
			$args = array();
			if ( isset( $_GET['subset'] ) && ! empty( $_GET['subset'] ) ) {
				$args['search_subset'] = $_GET['subset'];
			}

			if ( isset( $_GET['s'] ) && ! empty( $_GET['s'] ) ) {
				$args['search_term'] = $_GET['s'];
			}

			if ( isset( $_GET['list'] ) && ! empty( $_GET['list'] ) ) {
				$current_page = (int) $_GET['list'];
				if ( $current_page > 0 ) {
					$args['current_page'] = $current_page;
				}
			}

			$args = apply_filters( 'bp_search_search_page_args', $args );
			$this->do_search( $args );
		}

		/**
		 * Sanitize user inputs before performing search.
		 *
		 * @param mixed $args
		 *
		 * @return array
		 */
		public function sanitize_args( $args = '' ) {
			$args = bp_parse_args( $args, array() );

			if ( isset( $args['search_term'] ) ) {
				$args['search_term'] = sanitize_text_field( $args['search_term'] );
			}

			if ( isset( $args['search_subset'] ) ) {
				$args['search_subset'] = sanitize_text_field( $args['search_subset'] );
			}

			if ( isset( $args['per_page'] ) ) {
				$args['per_page'] = absint( $args['per_page'] );
			}

			if ( isset( $args['current_page'] ) ) {
				$args['current_page'] = absint( $args['current_page'] );
			}

			return $args;
		}

		/**
		 * Returns the url of the page which is selected to display search results.
		 *
		 * @since BuddyBoss 1.0.0
		 * @return string url of the serach results page
		 */
		public function search_page_url( $value = '' ) {
			$url = home_url( '/' );

			if ( ! empty( $value ) ) {
				$url = esc_url( add_query_arg( 's', urlencode( $value ), $url ) );
			}

			return $url;
		}

		/**
		 * function to return full search url, added with search terms and other filters
		 */
		private function search_page_search_url( $default = true ) {

			if ( true == $this->search_args['forum_search'] ) {
				// Full search url for bbpress forum search
				if ( true === $default ) {
					$base_url = bbp_get_search_url( false );
				} else {
					$base_url = bbp_get_search_url( false ) . $this->search_args['search_term'];
				}

				if ( true === $this->search_args['forum_search'] ) {
					$full_url = esc_url( $base_url );
				} else {
					$full_url = esc_url( add_query_arg( 'bbp_search', urlencode( $this->search_args['search_term'] ), $base_url ) );
				}
			} else {
				$base_url = $this->search_page_url();
				$full_url = esc_url( add_query_arg( 's', urlencode( stripslashes( $this->search_args['search_term'] ) ), $base_url ) );
				// for now we only have one filter in url
			}

			return $full_url;
		}

		public function print_tabs() {

			$search_url = $this->search_page_search_url();

			// first print the 'all results' tab.
			$class = 'all' == $this->search_args['search_subset'] ? 'active current selected' : '';
			// this filter can be used to change display of 'all' to 'Everything' etc..
			$all_label = __( 'All Results', 'buddyboss' );
			$label     = apply_filters( 'bp_search_label_search_type', $all_label );

			if ( $this->search_args['count_total'] && isset( $this->search_results['all'] ) ) {
				$label .= "<span class='count'>" . $this->search_results['all']['total_match_count'] . '</span>';
			}

			$tab_url = $search_url;
			echo "<li class='{$class}'><a href='" . esc_url( $tab_url ) . "'>{$label}</a></li>";

			// then other tabs.
			$search_items = bp_search_items();

			if ( empty( $this->searchable_items ) && ! is_array( $this->searchable_items ) ) {
				return;
			}

			foreach ( $this->searchable_items as $item ) {
				$class = $item == $this->search_args['search_subset'] ? 'active current' : '';
				// this filter can be used to change display of 'posts' to 'Blog Posts' etc..

				$label = isset( $search_items[ $item ] ) ? $search_items[ $item ] : $item;

				$label = apply_filters( 'bp_search_label_search_type', $label );

				if ( empty( $this->search_results[ $item ]['total_match_count'] ) ) {
					continue; // skip tab.
				}

				if ( $this->search_args['count_total'] ) {
					$label .= "<span class='count'>" . (int) $this->search_results[ $item ]['total_match_count'] . '</span>';
				}

				$tab_url = esc_url( add_query_arg( 'subset', $item, $search_url ) );
				echo "<li class='{$class} {$item}' data-item='{$item}'><a href='" . esc_url( $tab_url ) . "'>{$label}</a></li>";
			}
		}

		public function print_results() {

			if ( $this->has_search_results() ) {
				$current_tab = $this->search_args['search_subset'];

				foreach ( $this->search_results[ $current_tab ]['items'] as $item_id => $item ) {
					echo $item['html'];
				}

				if ( $current_tab != 'all' ) {
					$page_slug = untrailingslashit( str_replace( home_url(), '', $this->search_page_url() ) );

					bp_search_pagination_page_counts(
						$this->search_results[ $current_tab ]['total_match_count'],
						$this->search_args['per_page'],
						$this->search_args['current_page']
					);

					bp_search_pagination(
						$this->search_results[ $current_tab ]['total_match_count'],
						$this->search_args['per_page'],
						$this->search_args['current_page'],
						$page_slug
					);
				}
			} else {
				bp_search_buffer_template_part( 'no-results' );
			}
		}

		public function get_search_term() {
			return isset( $this->search_args['search_term'] ) ? $this->search_args['search_term'] : '';
		}

		public function has_search_results() {
			$current_tab = isset( $this->search_args['search_subset'] ) ? $this->search_args['search_subset'] : '';
			return isset( $this->search_results[ $current_tab ]['items'] ) && ! empty( $this->search_results[ $current_tab ]['items'] );
		}
	}

	// End class Bp_Search_Helper.

endif;

