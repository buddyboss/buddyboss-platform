<?php
/**
 * BuddyBoss Moderation Loader.
 *
 * An moderation component, for users, groups moderation.
 *
 * @since   BuddyBoss 1.5.6
 * @package BuddyBoss\Moderation
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Main Moderation Class.
 *
 * @since BuddyBoss 1.5.6
 */
#[\AllowDynamicProperties]
class BP_Moderation_Component extends BP_Component {

	/**
	 * Start the Moderation component setup process.
	 *
	 * @since BuddyBoss 1.5.6
	 */
	public function __construct() {
		parent::start(
			'moderation',
			__( 'Moderation', 'buddyboss' ),
			buddypress()->plugin_dir,
			array(
				'adminbar_myaccount_order' => 100,
			)
		);
	}

	/**
	 * Include component files.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param array $includes See BP_Component::includes() for a description.
	 *
	 * @see   BP_Component::includes() for a description of arguments.
	 */
	public function includes( $includes = array() ) {
		// Files to include.
		$includes = array(
			'cssjs',
			'settings',
			'functions',
			'filters',
			'template',
		);

		if ( is_admin() ) {
			$includes[] = 'admin';
		}

		parent::includes( $includes );
	}

	/**
	 * Late includes method.
	 *
	 * Only load up certain code when on specific pages.
	 *
	 * @since BuddyBoss 1.5.6
	 */
	public function late_includes() {
		// Bail if PHPUnit is running.
		if ( defined( 'BP_TESTS_DIR' ) ) {
			return;
		}

		if ( bp_is_user() ) {
			require $this->path . 'bp-moderation/screens/user/my-moderation.php';
		}
	}

	/**
	 * Set up component global variables.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param array $args See BP_Component::setup_globals() for a description.
	 *
	 * @see   BP_Component::setup_globals() for a description of arguments.
	 */
	public function setup_globals( $args = array() ) {
		$bp = buddypress();

		// Define a slug, if necessary.
		if ( ! defined( 'BP_MODERATION_SLUG' ) ) {
			define( 'BP_MODERATION_SLUG', $this->id );
		}

		// Global tables for activity component.
		$global_tables = array(
			'table_name'         => $bp->table_prefix . 'bp_suspend',
			'table_name_reports' => $bp->table_prefix . 'bp_moderation',
			'table_name_meta'    => $bp->table_prefix . 'bp_moderation_meta',
		);

		// Metadata tables for groups component.
		$meta_tables = array(
			'moderation' => $bp->table_prefix . 'bp_moderation_meta',
		);

		// Fetch the default directory title.
		$default_directory_titles = bp_core_get_directory_page_default_titles();
		$default_directory_title  = $default_directory_titles[ $this->id ];

		// All globals for moderation component.
		// Note that global_tables is included in this array.
		parent::setup_globals(
			array(
				'slug'            => 'moderation',
				'root_slug'       => isset( $bp->pages->moderation->slug ) ? $bp->pages->moderation->slug : BP_MODERATION_SLUG,
				'has_directory'   => false,
				'global_tables'   => $global_tables,
				'meta_tables'     => $meta_tables,
				'directory_title' => isset( $bp->pages->moderation->title ) ? $bp->pages->moderation->title : $default_directory_title,
			)
		);
	}

	/**
	 * Set up the title for pages and <title>.
	 *
	 * @since BuddyBoss 1.5.6
	 */
	public function setup_title() {
		// Adjust title based on view.
		if ( bp_is_moderation_component() ) {
			$bp = buddypress();

			if ( bp_is_my_profile() ) {
				$bp->bp_options_title = __( 'My Moderation', 'buddyboss' );
			}
		}

		parent::setup_title();
	}

	/**
	 * Setup cache moderation.
	 *
	 * @since BuddyBoss 1.5.6
	 */
	public function setup_cache_groups() {
		// Global groups.
		wp_cache_add_global_groups(
			array(
				'bp_moderation',
				'bp_moderation_reporters',
			)
		);

		parent::setup_cache_groups();
	}

	/**
	 * Set up taxonomies.
	 *
	 * @since BuddyBoss 1.5.6
	 */
	public function register_taxonomies() {
		// Group Type.
		register_taxonomy(
			'bpm_category',
			'bp_moderation',
			array(
				'label'              => __( 'Reporting Category', 'buddyboss' ),
				'description'        => __( 'Create reporting categories for members to select when reporting content and members on the front-end. An additional "Other" category will also be available for members to specify their own reason for reporting.', 'buddyboss' ),
				'labels'             => array(
					'name'                       => _x( 'Reporting Categories', 'taxonomy general name', 'buddyboss' ),
					'singular_name'              => _x( 'Reporting Category', 'taxonomy singular name', 'buddyboss' ),
					'search_items'               => __( 'Search Categories', 'buddyboss' ),
					'popular_items'              => __( 'Popular Categories', 'buddyboss' ),
					'all_items'                  => __( 'All Categories', 'buddyboss' ),
					'edit_item'                  => __( 'Edit Category', 'buddyboss' ),
					'update_item'                => __( 'Update Category', 'buddyboss' ),
					'add_new_item'               => __( 'Add New Category', 'buddyboss' ),
					'new_item_name'              => __( 'New Category Name', 'buddyboss' ),
					'separate_items_with_commas' => __( 'Separate categories with commas', 'buddyboss' ),
					'add_or_remove_items'        => __( 'Add or remove categories', 'buddyboss' ),
					'choose_from_most_used'      => __( 'Choose from the most used categories', 'buddyboss' ),
					'not_found'                  => __( 'No reporting categories found.', 'buddyboss' ),
					'menu_name'                  => __( 'Reporting Categories', 'buddyboss' ),
					'back_to_items'              => __( '&larr; Back to Reporting Categories', 'buddyboss' ),
				),
				'public'             => true,
				'publicly_queryable' => false,
				'rewrite'            => false,
				'hierarchical'       => false,
				'show_in_nav_menus'  => false,
			)
		);

		$is_moderation_terms = get_option( 'moderation_default_category_added', false );
		if ( false === $is_moderation_terms ) {

			$moderation_terms = array(
				'offensive'      => array(
					'name'        => __( 'Offensive', 'buddyboss' ),
					'description' => __( 'Contains abusive or derogatory content', 'buddyboss' ),
				),
				'inappropriate'  => array(
					'name'        => __( 'Inappropriate', 'buddyboss' ),
					'description' => __( 'Contains mature or sensitive content', 'buddyboss' ),
				),
				'misinformation' => array(
					'name'        => __( 'Misinformation', 'buddyboss' ),
					'description' => __( 'Contains misleading or false information', 'buddyboss' ),
				),
				'suspicious'     => array(
					'name'        => __( 'Suspicious', 'buddyboss' ),
					'description' => __( 'Contains spam, fake content or potential malware', 'buddyboss' ),
				),
				'harassment'     => array(
					'name'        => __( 'Harassment', 'buddyboss' ),
					'description' => __( 'Harassment or bullying behavior', 'buddyboss' ),
				),
			);

			foreach ( $moderation_terms as $moderation_term ) {
				$term = term_exists( $moderation_term['name'], 'bpm_category' );
				if ( empty( $term ) ) {
					$term = wp_insert_term( $moderation_term['name'], 'bpm_category', array( 'description' => $moderation_term['description'] ) );
					if ( isset( $term['term_id'] ) && ! empty( $term['term_id'] ) ) {
						update_term_meta(
							$term['term_id'],
							'bb_category_show_when_reporting',
							'content_members'
						);
					}
				}
			}

			update_option( 'moderation_default_category_added', true, false );
		}
	}

	/**
	 * Init the BuddyBoss REST API.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param array $controllers Optional. See BP_Component::rest_api_init() for description.
	 */
	public function rest_api_init( $controllers = array() ) {
		parent::rest_api_init(
			array(
				'BP_REST_Moderation_Endpoint',
				'BP_REST_Moderation_Report_Endpoint',
			)
		);
	}
}
