<?php

/**
 * Forums Classes
 *
 * @package BuddyBoss\Classes
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'BBP_Component' ) ) :
	/**
	 * Forums Component Class
	 *
	 * The Forums component class is responsible for simplifying the creation
	 * of components that share similar behaviors and routines. It is used
	 * internally by Forums to create forums, topics and replies, but can be
	 * extended to create other really neat things.
	 *
	 * @since bbPress (r2688)
	 */
	class BBP_Component {

		/**
		 * @var string Unique name (for internal identification)
		 * @internal
		 */
		var $name;

		/**
		 * @var Unique ID (normally for custom post type)
		 */
		var $id;

		/**
		 * @var string Unique slug (used in query string and permalinks)
		 */
		var $slug;

		/**
		 * @var WP_Query The loop for this component
		 */
		var $query;

		/**
		 * @var string The current ID of the queried object
		 */
		var $current_id;


		/** Methods ***************************************************************/

		/**
		 * Forums Component loader
		 *
		 * @since bbPress (r2700)
		 *
		 * @param mixed $args Required. Supports these args:
		 *  - name: Unique name (for internal identification)
		 *  - id: Unique ID (normally for custom post type)
		 *  - slug: Unique slug (used in query string and permalinks)
		 *  - query: The loop for this component (WP_Query)
		 *  - current_id: The current ID of the queried object
		 * @uses BBP_Component::setup_globals() Setup the globals needed
		 * @uses BBP_Component::includes() Include the required files
		 * @uses BBP_Component::setup_actions() Setup the hooks and actions
		 */
		public function __construct( $args = '' ) {
			if ( empty( $args ) ) {
				return;
			}

			$this->setup_globals( $args );
			$this->includes();
			$this->setup_actions();
		}

		/**
		 * Component global variables
		 *
		 * @since bbPress (r2700)
		 * @access private
		 *
		 * @uses apply_filters() Calls 'bbp_{@link BBP_Component::name}_id'
		 * @uses apply_filters() Calls 'bbp_{@link BBP_Component::name}_slug'
		 */
		private function setup_globals( $args = '' ) {
			$this->name = $args['name'];
			$this->id   = apply_filters( 'bbp_' . $this->name . '_id', $args['id'] );
			$this->slug = apply_filters( 'bbp_' . $this->name . '_slug', $args['slug'] );
		}

		/**
		 * Include required files
		 *
		 * @since bbPress (r2700)
		 * @access private
		 *
		 * @uses do_action() Calls 'bbp_{@link BBP_Component::name}includes'
		 */
		private function includes() {
			do_action( 'bbp_' . $this->name . 'includes' );
		}

		/**
		 * Setup the actions
		 *
		 * @since bbPress (r2700)
		 * @access private
		 *
		 * @uses add_action() To add various actions
		 * @uses do_action() Calls
		 *                    'bbp_{@link BBP_Component::name}setup_actions'
		 */
		private function setup_actions() {
			add_action( 'bbp_register_post_types', array( $this, 'register_post_types' ), 10, 2 ); // Register post types
			add_action( 'bbp_register_taxonomies', array( $this, 'register_taxonomies' ), 10, 2 ); // Register taxonomies
			add_action( 'bbp_add_rewrite_tags', array( $this, 'add_rewrite_tags' ), 10, 2 ); // Add the rewrite tags
			add_action( 'bbp_generate_rewrite_rules', array( $this, 'generate_rewrite_rules' ), 10, 2 ); // Generate rewrite rules

			// Additional actions can be attached here
			do_action( 'bbp_' . $this->name . 'setup_actions' );
		}

		/**
		 * Setup the component post types
		 *
		 * @since bbPress (r2700)
		 *
		 * @uses do_action() Calls 'bbp_{@link BBP_Component::name}_register_post_types'
		 */
		public function register_post_types() {
			do_action( 'bbp_' . $this->name . '_register_post_types' );
		}

		/**
		 * Register component specific taxonomies
		 *
		 * @since bbPress (r2700)
		 *
		 * @uses do_action() Calls 'bbp_{@link BBP_Component::name}_register_taxonomies'
		 */
		public function register_taxonomies() {
			do_action( 'bbp_' . $this->name . '_register_taxonomies' );
		}

		/**
		 * Add any additional rewrite tags
		 *
		 * @since bbPress (r2700)
		 *
		 * @uses do_action() Calls 'bbp_{@link BBP_Component::name}_add_rewrite_tags'
		 */
		public function add_rewrite_tags() {
			do_action( 'bbp_' . $this->name . '_add_rewrite_tags' );
		}

		/**
		 * Generate any additional rewrite rules
		 *
		 * @since bbPress (r2700)
		 *
		 * @uses do_action() Calls 'bbp_{@link BBP_Component::name}_generate_rewrite_rules'
		 */
		public function generate_rewrite_rules( $wp_rewrite ) {
			do_action_ref_array( 'bbp_' . $this->name . '_generate_rewrite_rules', $wp_rewrite );
		}
	}
endif; // BBP_Component

if ( class_exists( 'Walker' ) ) :
	/**
	 * Create HTML dropdown list of forums/topics.
	 *
	 * @since bbPress (r2746)
	 * @uses Walker
	 */
	class BBP_Walker_Dropdown extends Walker {

		/**
		 * @see Walker::$tree_type
		 *
		 * @since bbPress (r2746)
		 *
		 * @var string
		 */
		var $tree_type;

		/**
		 * @see Walker::$db_fields
		 *
		 * @since bbPress (r2746)
		 *
		 * @var array
		 */
		var $db_fields = array(
			'parent' => 'post_parent',
			'id'     => 'ID',
		);

		/** Methods ***************************************************************/

		/**
		 * Set the tree_type
		 *
		 * @since bbPress (r2746)
		 */
		public function __construct() {
			$this->tree_type = bbp_get_forum_post_type();
		}

		/**
		 * @see Walker::start_el()
		 *
		 * @since bbPress (r2746)
		 *
		 * @param string $output Passed by reference. Used to append additional
		 *                        content.
		 * @param object $_post Post data object.
		 * @param int    $depth Depth of post in reference to parent posts. Used
		 *                       for padding.
		 * @param array  $args Uses 'selected' argument for selected post to set
		 *                      selected HTML attribute for option element.
		 * @param int    $current_object_id
		 * @uses bbp_is_forum_category() To check if the forum is a category
		 * @uses current_user_can() To check if the current user can post in
		 *                           closed forums
		 * @uses bbp_is_forum_closed() To check if the forum is closed
		 * @uses apply_filters() Calls 'bbp_walker_dropdown_post_title' with the
		 *                        title, output, post, depth and args
		 */
		public function start_el( &$output, $object, $depth = 0, $args = array(), $current_object_id = 0 ) {
			$pad     = str_repeat( '&nbsp;', (int) $depth * 3 );
			$output .= '<option class="level-' . (int) $depth . '"';

			// Disable the <option> if:
			// - we're told to do so
			// - the post type is a forum
			// - the forum is a category
			// - forum is closed
			if ( ( true === $args['disable_categories'] )
				&& ( bbp_get_forum_post_type() === $object->post_type )
				&& ( bbp_is_forum_category( $object->ID )
					|| ( ! current_user_can( 'edit_forum', $object->ID ) && bbp_is_forum_closed( $object->ID )
				)
			) ) {
				$output .= ' disabled="disabled" value=""';
			} else {
				$output .= ' value="' . (int) $object->ID . '"' . selected( $args['selected'], $object->ID, false );
			}

			/**
			 * Add extra attributes for dropdown options.
			 * Default value empty.
			 *
			 * @since BuddyBoss 1.7.8
			 *
			 * @param string $attribute Option attribute.
			 * @param object $object    Post data.
			 * @param array  $args      Dropdown arguments.
			 * @param int    $depth     Depth of post in reference to parent posts.
			 * @param string $output    Used to append additional content.
			 */
			$output .= apply_filters( 'bb_walker_dropdown_option_attr', '', $object, $args, $depth, $output );

			$output .= '>';
			$title   = apply_filters( 'bbp_walker_dropdown_post_title', $object->post_title, $output, $object, $depth, $args );
			$output .= $pad . esc_html( $title );
			$output .= "</option>\n";
		}
	}

	/**
	 * Create hierarchical list of Forums replies.
	 *
	 * @since bbPress (r4944)
	 */
	class BBP_Walker_Reply extends Walker {

		/**
		 * @see Walker::$tree_type
		 *
		 * @since bbPress (r4944)
		 *
		 * @var string
		 */
		var $tree_type = 'reply';

		/**
		 * @see Walker::$db_fields
		 *
		 * @since bbPress (r4944)
		 *
		 * @var array
		 */
		var $db_fields = array(
			'parent' => 'reply_to',
			'id'     => 'ID',
		);

		/**
		 * @see Walker::start_lvl()
		 *
		 * @since bbPress (r4944)
		 *
		 * @param string $output Passed by reference. Used to append additional content
		 * @param int    $depth Depth of reply
		 * @param array  $args Uses 'style' argument for type of HTML list
		 */
		public function start_lvl( &$output = '', $depth = 0, $args = array() ) {
			bbpress()->reply_query->reply_depth = $depth + 1;

			switch ( $args['style'] ) {
				case 'div':
					break;
				case 'ol':
					echo "<ol class='bbp-threaded-replies'>\n";
					break;
				case 'ul':
				default:
					echo "<ul class='bbp-threaded-replies'>\n";
					break;
			}
		}

		/**
		 * @see Walker::end_lvl()
		 *
		 * @since bbPress (r4944)
		 *
		 * @param string $output Passed by reference. Used to append additional content
		 * @param int    $depth Depth of reply
		 * @param array  $args Will only append content if style argument value is 'ol' or 'ul'
		 */
		public function end_lvl( &$output = '', $depth = 0, $args = array() ) {
			$view_more_depth = $depth + 2;
			bbpress()->reply_query->reply_depth = (int) $view_more_depth;
			switch ( $args['style'] ) {
				case 'div':
					break;
				case 'ol':
					if( !$args['remove_view_more'] ){
						echo "<li class='bp_view_more depth-".$view_more_depth."' data-depth='".$view_more_depth."' data-reply-to = '".bbp_get_reply_to($args['child_id'])."' data-click='1' data-child-count='".$args['children_count']."'><div class='bs-reply-list-item'>View more replies</div></li>";
					}
					echo "</ol>\n";
					break;
				case 'ul':
				default:
					if( !$args['remove_view_more'] ){
						echo "<li class='bp_view_more depth-".$view_more_depth."' data-depth='".$view_more_depth."' data-reply-to = '".bbp_get_reply_to($args['child_id'])."' data-click='1' data-child-count='".$args['children_count']."'><div class='bs-reply-list-item'>View more replies</div></li>";
					}
					echo "</ul>\n";
					break;
			}
		}

		/**
		 * @since bbPress (r4944)
		 */
		public function display_element( $element = false, &$children_elements = array(), $max_depth = 0, $depth = 0, $args = array(), &$output = '' ) {

			if ( empty( $element ) ) {
				return;
			}
			if($element->reply_to && $depth == 0){
				return;
			}
		
			$id_field = $this->db_fields['id'];
			$id = $element->$id_field;
			
			if(isset($_REQUEST['bbp-ajax']) && $_REQUEST['bbp-ajax'] == true && isset($_REQUEST['bbp_ajax_depth']) && $_REQUEST['bbp_ajax_depth'] == true){
				if(!isset($args[0]['is_children'])){
					$depth = $_REQUEST['bbp_ajax_depth']-1;
				}
				$children_elements[$id] = array();
		
				// Default query args
				$default = array(
					'post_type'           => array('reply', 'topic'),         // Only replies
					'orderby'             => 'date',                     // Sorted by date
					'order'               => 'ASC',                      // Oldest to newest
					'hierarchical'        => true,    // Hierarchical replies
					'meta_query' => array(
						array(
							'key'     => '_bbp_reply_to',
							'value'   => $element->ID,
							'compare' => '='
						),
					),
				);
		
				// What are the default allowed statuses (based on user caps)
				if ( bbp_get_view_all() ) {
		
					// Default view=all statuses
					$post_statuses = array(
						bbp_get_public_status_id(),
						bbp_get_closed_status_id(),
						bbp_get_spam_status_id(),
						bbp_get_trash_status_id(),
					);
		
					// Add support for private status
					if ( current_user_can( 'read_private_replies' ) ) {
						$post_statuses[] = bbp_get_private_status_id();
					}
		
					// Join post statuses together
					$default['post_status'] = implode( ',', $post_statuses );
		
					// Lean on the 'perm' query var value of 'readable' to provide statuses
				} else {
					$default['perm'] = 'readable';
				}
		
				/** Setup */
				$new_args = array();
				// Parse arguments against default values
				$r = bbp_parse_args( $new_args, $default, 'has_replies' );
		
				// Set posts_per_page value if replies are threaded
				$replies_per_page = $r['posts_per_page'];
				if ( true === $r['hierarchical'] ) {
					$r['posts_per_page'] = - 1;
				}
		
		
		
				// Call the query
				$bbp_query = new WP_Query( $r );
				if($bbp_query->have_posts()){
					foreach ( $bbp_query->posts as &$post ) {
						$post->reply_to = bbp_get_reply_to($post->ID);
						$children_elements[$id][] = $post;
					}
				}
				wp_reset_postdata();
			}
			$orphans = true;
			$args[0]['remove_view_more'] = false;
		
			// Display this element.
			$this->has_children = ! empty( $children_elements[ $id ] );
			if ( isset( $args[0] ) && is_array( $args[0] ) ) {
				$args[0]['has_children'] = $this->has_children; // Back-compat.
			}
		
			$this->start_el( $output, $element, $depth, ...array_values( $args ) );
			$child_id = 0;
			// Descend only when the depth is right and there are children for this element.
			if ( ( 0 == $max_depth || $max_depth > $depth + 1 ) && isset( $children_elements[ $id ] ) ) {
				$show_limit = 0;
				foreach ( $children_elements[ $id ] as $child ) {
					if($show_limit >= bbp_get_replies_per_page()){
						$orphans = false;
						break;
					}
					if ( ! isset( $newlevel ) ) {
						$newlevel = true;
						// Start the child delimiter.
						if(isset($_REQUEST['bbp-ajax']) && $_REQUEST['bbp-ajax'] == true && isset($_REQUEST['bbp_ajax_depth']) && $_REQUEST['bbp_ajax_depth'] == true){
							$args[0]['is_children'] = true; 
						}
						$this->start_lvl( $output, $depth, ...array_values( $args ) );
					}
		
					$this->display_element( $child, $children_elements, $max_depth, $depth + 1, $args, $output );
					$show_limit = $show_limit + 1;
					$child_id = $child->ID;
				}
				if(bbp_get_replies_per_page()>=count($children_elements[ $id ])){
					$args[0]['remove_view_more'] = true;
				}
				$args[0]['children_count'] = count($children_elements[ $id ]);
				$args[0]['child_id'] = $child_id;
				
				unset( $children_elements[ $id ] );
			}
		
			if ( isset( $newlevel ) && $newlevel ) {
				// End the child delimiter.
				$this->end_lvl( $output, $depth, ...array_values( $args ) );
			}
		
			// End this element.
			$this->end_el( $output, $element, $depth, ...array_values( $args ) );
			if($orphans){
				// If we're at the max depth and the current element still has children, loop over those
				// and display them at this level to prevent them being orphaned to the end of the list.
				if ( ( $max_depth <= (int) $depth + 1 ) && isset( $children_elements[ $id ] ) ) {
					foreach ( $children_elements[ $id ] as $child ) {
						$this->display_element( $child, $children_elements, $max_depth, $depth, $args, $output );
					}
					unset( $children_elements[ $id ] );
				}
			}
		}

		/**
		 * @see Walker:start_el()
		 *
		 * @since bbPress (r4944)
		 */
		public function start_el( &$output, $object, $depth = 0, $args = array(), $current_object_id = 0 ) {

			// Set up reply
			if(isset($args['is_children'])){
				$depth = $depth + 1;
			}
			else{
				$depth++;
			}
			bbpress()->reply_query->reply_depth = $depth;
			bbpress()->reply_query->post        = $object;
			bbpress()->current_reply_id         = $object->ID;

			// Check for a callback and use it if specified
			if ( ! empty( $args['callback'] ) ) {
				call_user_func( $args['callback'], $object, $args, $depth );
				return;
			}

			// Style for div or list element
			if ( ! empty( $args['style'] ) && ( 'div' === $args['style'] ) ) {
				echo "<div class='depth-$depth' data-depth='$depth'>\n";
			} else {
				echo "<li class='depth-$depth' data-depth='$depth'>\n";
			}
			bbp_get_template_part( 'loop', 'single-reply' );
		}

		/**
		 * @since bbPress (r4944)
		 */
		public function end_el( &$output = '', $object = false, $depth = 0, $args = array() ) {

			// Check for a callback and use it if specified
			if ( ! empty( $args['end-callback'] ) ) {
				call_user_func( $args['end-callback'], $object, $args, $depth );
				return;
			}

			// Style for div or list element
			if ( ! empty( $args['style'] ) && ( 'div' === $args['style'] ) ) {
				echo "</div>\n";
			} else {
				echo "</li>\n";
			}
		}
	}
endif; // class_exists check
