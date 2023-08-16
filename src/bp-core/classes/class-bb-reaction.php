<?php
/**
 * Reaction class.
 *
 * @package BuddyBoss\Core
 * @since BuddyBoss [BBVERSION]
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'BB_Reaction' ) ) {

	/**
	 * BuddyBoss Reaction object.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	class BB_Reaction {

		/**
		 * The single instance of the class.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @var self
		 */
		private static $instance = null;

		/**
		 * Post type.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @var mixed|null
		 */
		private static $post_type;

		/**
		 * Get the instance of this class.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return Controller|BB_Reaction|null
		 */
		public static function instance() {

			if ( null === self::$instance ) {
				$class_name     = __CLASS__;
				self::$instance = new $class_name();
			}

			return self::$instance;
		}

		/**
		 * Constructor method.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		public function __construct() {
			self::$post_type = 'bb_reaction';

			// Register post type.
			add_action( 'bp_register_post_types', array( $this, 'bb_register_post_type' ), 10 );
		}

		/**
		 * Register post type.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		public function bb_register_post_type() {
			if ( bp_is_root_blog() && ! is_network_admin() ) {
				register_post_type(
					self::$post_type,
					apply_filters(
						'bb_register_reaction_post_type',
						array(
							'description'         => __( 'Reactions', 'buddyboss' ),
							'labels'              => $this->bb_get_reaction_post_type_labels(),
							'menu_icon'           => 'dashicons-reaction-alt',
							'public'              => false,
							'show_ui'             => false,
							'show_in_rest'        => false,
							'exclude_from_search' => true,
							'show_in_admin_bar'   => false,
							'show_in_nav_menus'   => true,
						)
					)
				);
			}
		}

		/**
		 * Return labels used by the reaction post type.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return array
		 */
		public function bb_get_reaction_post_type_labels() {

			/**
			 * Filters reaction post type labels.
			 *
			 * @since BuddyBoss [BBVERSION]
			 *
			 * @param array $value Associative array (name => label).
			 */
			return apply_filters(
				'bb_get_reaction_post_type_labels',
				array(
					'add_new'               => __( 'New Reaction', 'buddyboss' ),
					'add_new_item'          => __( 'Add New Reaction', 'buddyboss' ),
					'all_items'             => __( 'All Reactions', 'buddyboss' ),
					'edit_item'             => __( 'Edit Reaction', 'buddyboss' ),
					'filter_items_list'     => __( 'Filter Reaction list', 'buddyboss' ),
					'items_list'            => __( 'Reaction list', 'buddyboss' ),
					'items_list_navigation' => __( 'Reaction list navigation', 'buddyboss' ),
					'menu_name'             => __( 'Reactions', 'buddyboss' ),
					'name'                  => __( 'Reactions', 'buddyboss' ),
					'new_item'              => __( 'New Reaction', 'buddyboss' ),
					'not_found'             => __( 'No reactions found', 'buddyboss' ),
					'not_found_in_trash'    => __( 'No reactions found in trash', 'buddyboss' ),
					'search_items'          => __( 'Search Reactions', 'buddyboss' ),
					'singular_name'         => __( 'Reaction', 'buddyboss' ),
					'uploaded_to_this_item' => __( 'Uploaded to this reaction', 'buddyboss' ),
					'view_item'             => __( 'View Reaction', 'buddyboss' ),
				)
			);
		}
	}
}
