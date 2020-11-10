<?php
/**
 * BuddyBoss Moderation Loader.
 *
 * An moderation component, for users, groups moderation.
 *
 * @package BuddyBoss\Moderation
 * @since BuddyBoss 2.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Main Moderation Class.
 *
 * @since BuddyBoss 2.0.0
 */
class BP_Moderation_Component extends BP_Component {

	/**
	 * Start the Moderation component setup process.
	 *
	 * @since BuddyBoss 2.0.0
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
	 * @since BuddyBoss 2.0.0
	 *
	 * @see BP_Component::includes() for a description of arguments.
	 *
	 * @param array $includes See BP_Component::includes() for a description.
	 */
	public function includes( $includes = array() ) {
		// Files to include.
		$includes = array(
			'cssjs',
			'filters',
			'template',
			'functions',
			'settings',
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
	 * @since BuddyBoss 2.0.0
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
	 * @since BuddyBoss 2.0.0
	 *
	 * @see BP_Component::setup_globals() for a description of arguments.
	 *
	 * @param array $args See BP_Component::setup_globals() for a description.
	 */
	public function setup_globals( $args = array() ) {
		$bp = buddypress();

		// Define a slug, if necessary.
		if ( ! defined( 'BP_MODERATION_SLUG' ) ) {
			define( 'BP_MODERATION_SLUG', $this->id );
		}

		// Global tables for activity component.
		$global_tables = array(
			'table_name'         => $bp->table_prefix . 'bp_moderation',
			'table_name_reports' => $bp->table_prefix . 'bp_moderation_reports',
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
	 * Set up component navigation.
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @see BP_Component::setup_nav() for a description of arguments.
	 *
	 * @param array $main_nav Optional. See BP_Component::setup_nav() for description.
	 * @param array $sub_nav  Optional. See BP_Component::setup_nav() for description.
	 */
	public function setup_nav( $main_nav = array(), $sub_nav = array() ) {
		// Stop if there is no user displayed or logged in.
		if ( ! is_user_logged_in() && ! bp_displayed_user_id() ) {
			return;
		}

		// Determine user to use.
		if ( bp_loggedin_user_domain() ) {
			$user_domain = bp_loggedin_user_domain();
		}

		$slug            = bp_get_moderation_slug();
		$moderation_link = trailingslashit( $user_domain . $slug );

		$main_nav = array(
			'name'                => __( 'Moderation', 'buddyboss' ),
			'slug'                => $slug,
			'position'            => 100,
			'screen_function'     => 'bp_moderation_screen',
			'default_subnav_slug' => 'reported-content',
			'item_css_id'         => $this->id,
		);

		$sub_nav[] = array(
			'name'            => __( 'Reported Content', 'buddyboss' ),
			'slug'            => 'reported-content',
			'parent_url'      => $moderation_link,
			'parent_slug'     => $slug,
			'screen_function' => 'bp_moderation_screen',
			'position'        => 10,
			'item_css_id'     => 'reported-content',
		);

		$sub_nav[] = array(
			'name'            => __( 'Blocked Members', 'buddyboss' ),
			'slug'            => 'blocked-members',
			'parent_url'      => $moderation_link,
			'parent_slug'     => $slug,
			'screen_function' => 'bp_moderation_screen',
			'position'        => 15,
			'item_css_id'     => 'blocked-members',
		);

		parent::setup_nav( $main_nav, $sub_nav );
	}

	/**
	 * Set up the component entries in the WordPress Admin Bar.
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @see BP_Component::setup_nav() for a description of the $wp_admin_nav
	 *      parameter array.
	 *
	 * @param array $wp_admin_nav See BP_Component::setup_admin_bar() for a
	 *                            description.
	 */
	public function setup_admin_bar( $wp_admin_nav = array() ) {
		if ( is_user_logged_in() ) {

			$moderation_link = trailingslashit( bp_loggedin_user_domain() . bp_get_moderation_slug() );

			// Add the "Activity" sub menu.
			$wp_admin_nav[] = array(
				'parent' => buddypress()->my_account_menu_id,
				'id'     => 'my-account-' . $this->id,
				'title'  => __( 'Moderation', 'buddyboss' ),
				'href'   => $moderation_link,
			);

			// Personal.
			$wp_admin_nav[] = array(
				'parent'   => 'my-account-' . $this->id,
				'id'       => 'my-account-' . $this->id . '-reporting',
				'title'    => __( 'My Reporting', 'buddyboss' ),
				'href'     => $moderation_link,
				'position' => 10,
			);

		}

		parent::setup_admin_bar( $wp_admin_nav );
	}

	/**
	 * Set up the title for pages and <title>.
	 *
	 * @since BuddyBoss 2.0.0
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
	 * @since BuddyBoss 2.0.0
	 */
	public function setup_cache_groups() {
	}

	/**
	 * Set up taxonomies.
	 *
	 * @since BuddyPress 2.6.0
	 */
	public function register_taxonomies() {
		// Group Type.
		register_taxonomy(
			'bpm_category',
			'bp_moderation',
			array(
				'label'              => __( 'Reporting Category', 'buddyboss' ),
				'labels'             => array(
					'name'          => __( 'Reporting Categories', 'buddyboss' ),
					'singular_name' => __( 'Reporting Category', 'buddyboss' ),
					'menu_name'     => __( 'Reporting Categories', 'buddyboss' ),
					'all_items'     => __( 'All Reporting Categories', 'buddyboss' ),
					'edit_item'     => __( 'Edit Reporting Category', 'buddyboss' ),
					'view_item'     => __( 'View Reporting Category', 'buddyboss' ),
					'update_item'   => __( 'Update Reporting Category', 'buddyboss' ),
					'add_new_item'  => __( 'Add Reporting Category', 'buddyboss' ),
					'new_item_name' => __( 'New Reporting', 'buddyboss' ),
				),
				'public'             => true,
				'publicly_queryable' => false,
				'rewrite'            => false,
				'hierarchical'       => false,
			)
		);
		
		$is_moderation_terms = get_option( 'added_moderation_category', false );

		if ( false === $is_moderation_terms ) {

			$moderation_terms = array(
				'abuse' => array( 'name' => __( 'Abuse', 'buddyboss' ), 'description' => 'abuse category description' ),
				'adult' => array( 'name' => __( 'Adult', 'buddyboss' ), 'description' => 'adult category description' ),
			);

			foreach ( $moderation_terms as $moderation_term ) {

				$insert_term = wp_insert_term( $moderation_term['name'], 'bpm_category', array( 'description' => $moderation_term['description'] ) );

				if ( ! is_wp_error( $insert_term ) ) {
					$register = true;
				} else {
					$register = false;
				}
			}

			if ( true === $register ) {
				update_option( 'added_moderation_category', true, false );
			}
		}
	}

	/**
	 * Init the BuddyBoss REST API.
	 *
	 * @param array $controllers Optional. See BP_Component::rest_api_init() for description.
	 *
	 * @since BuddyBoss 2.0.0
	 */
	public function rest_api_init( $controllers = array() ) {
	}
}
