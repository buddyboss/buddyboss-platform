<?php
/**
 * BuddyPress Blogs Loader
 *
 * The blogs component tracks posts and comments to member activity feeds,
 * shows blogs the member can post to in their profiles, and caches useful
 * information from those blogs to make querying blogs in bulk more performant.
 *
 * @package BuddyBoss\Blogs\Core
 * @since BuddyPress 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Creates our Blogs component.
 */
#[\AllowDynamicProperties]
class BP_Blogs_Component extends BP_Component {

	/**
	 * Start the blogs component creation process.
	 *
	 * @since BuddyPress 1.5.0
	 */
	public function __construct() {
		parent::start(
			'blogs',
			'Site Directory',
			buddypress()->plugin_dir,
			array(
				'adminbar_myaccount_order' => 30,
				'search_query_arg'         => 'sites_search',
				'features'                 => array( 'site-icon' ),
			)
		);
	}

	/**
	 * Set up global settings for the blogs component.
	 *
	 * The BP_BLOGS_SLUG constant is deprecated, and only used here for
	 * backwards compatibility.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @see BP_Component::setup_globals() for description of parameters.
	 *
	 * @param array $args See {@link BP_Component::setup_globals()}.
	 */
	public function setup_globals( $args = array() ) {
		$bp = buddypress();

		if ( ! defined( 'BP_BLOGS_SLUG' ) ) {
			define( 'BP_BLOGS_SLUG', $this->id );
		}

		// Global tables for messaging component.
		$global_tables = array(
			'table_name'          => $bp->table_prefix . 'bp_user_blogs',
			'table_name_blogmeta' => $bp->table_prefix . 'bp_user_blogs_blogmeta',
		);

		$meta_tables = array(
			'bp_blog' => $bp->table_prefix . 'bp_user_blogs_blogmeta',
		);

		// Fetch the default directory title.
		$default_directory_titles = bp_core_get_directory_page_default_titles();
		$default_directory_title  = $default_directory_titles[ $this->id ];

		// All globals for blogs component.
		$args = array(
			'slug'                  => BP_BLOGS_SLUG,
			'root_slug'             => isset( $bp->pages->blogs->slug ) ? $bp->pages->blogs->slug : BP_BLOGS_SLUG,
			'has_directory'         => false, // Non-multisite installs don't need a top-level Sites directory, since there's only one site.
			'directory_title'       => isset( $bp->pages->blogs->title ) ? $bp->pages->blogs->title : $default_directory_title,
			'notification_callback' => 'bp_blogs_format_notifications',
			'search_string'         => __( 'Search sites&hellip;', 'buddyboss' ),
			'autocomplete_all'      => defined( 'BP_MESSAGES_AUTOCOMPLETE_ALL' ),
			'global_tables'         => $global_tables,
			'meta_tables'           => $meta_tables,
		);

		// Setup the globals.
		parent::setup_globals( $args );

		/**
		 * Filters if a blog is public.
		 *
		 * In case the config is not multisite, the blog_public option is ignored.
		 *
		 * @since BuddyPress 2.3.0
		 *
		 * @param int $value Whether or not the blog is public.
		 */
		if ( 0 !== apply_filters( 'bp_is_blog_public', (int) get_option( 'blog_public' ) ) || ! is_multisite() ) {

			/**
			 * Filters the post types to track for the Blogs component.
			 *
			 * @since BuddyPress 1.5.0
			 * @deprecated 2.3.0
			 *
			 * @param array $value Array of post types to track.
			 */
			$post_types = apply_filters( 'bp_blogs_record_post_post_types', bp_core_get_active_custom_post_type_feed() );

			foreach ( $post_types as $post_type ) {
				add_post_type_support( $post_type, 'buddypress-activity' );
			}
		}
	}

	/**
	 * Include bp-blogs files.
	 *
	 * @see BP_Component::includes() for description of parameters.
	 *
	 * @param array $includes See {@link BP_Component::includes()}.
	 */
	public function includes( $includes = array() ) {

		// Files to include.
		$includes = array(
			'cache',
			'template',
			'filters',
			'functions',
		);

		if ( bp_is_active( 'activity' ) ) {
			$includes[] = 'activity';
		}

		if ( is_multisite() ) {
			$includes[] = 'widgets';
		}

		// Include the files.
		parent::includes( $includes );
	}

	/**
	 * Late includes method.
	 *
	 * Only load up certain code when on specific pages.
	 *
	 * @since BuddyPress 3.0.0
	 */
	public function late_includes() {
		// Bail if PHPUnit is running.
		if ( defined( 'BP_TESTS_DIR' ) ) {
			return;
		}

		// Bail if not on a blogs page or not multisite.
		if ( ! bp_is_blogs_component() || ! is_multisite() ) {
			return;
		}

		// Actions.
		if ( isset( $_GET['random-blog'] ) ) {
			require $this->path . 'bp-blogs/actions/random.php';
		}

		// Screens.
		if ( bp_is_user() ) {
			require $this->path . 'bp-blogs/screens/my-blogs.php';
		} else {
			if ( bp_is_blogs_directory() ) {
				require $this->path . 'bp-blogs/screens/directory.php';
			}

			if ( is_user_logged_in() && bp_is_current_action( 'create' ) ) {
				require $this->path . 'bp-blogs/screens/create.php';
			}

			// Theme compatibility.
			new BP_Blogs_Theme_Compat();
		}
	}

	/**
	 * Set up component navigation for bp-blogs.
	 *
	 * @see BP_Component::setup_nav() for a description of arguments.
	 *
	 * @param array $main_nav Optional. See BP_Component::setup_nav() for
	 *                        description.
	 * @param array $sub_nav  Optional. See BP_Component::setup_nav() for
	 *                        description.
	 */
	public function setup_nav( $main_nav = array(), $sub_nav = array() ) {

		/**
		 * Blog/post/comment menus should not appear on single WordPress setups.
		 * Although comments and posts made by users will still show on their
		 * activity feed.
		 */
		if ( ! is_multisite() ) {
			return false;
		}

		// Determine user to use.
		if ( bp_displayed_user_domain() ) {
			$user_domain = bp_displayed_user_domain();
		} elseif ( bp_loggedin_user_domain() ) {
			$user_domain = bp_loggedin_user_domain();
		} else {
			return;
		}

		$slug       = bp_get_blogs_slug();
		$parent_url = trailingslashit( $user_domain . $slug );

		// Add 'Sites' to the main navigation.
		$count    = (int) bp_get_total_blog_count_for_user();
		$class    = ( 0 === $count ) ? 'no-count' : 'count';
		$nav_text = __( 'Sites', 'buddyboss' );
		$nav_text .= sprintf(
			' <span class="%s">%s</span>',
			esc_attr( $class ),
			bp_core_number_format( $count )
		);

		$main_nav = array(
			'name'                => $nav_text,
			'slug'                => $slug,
			'position'            => 30,
			'screen_function'     => 'bp_blogs_screen_my_blogs',
			'default_subnav_slug' => 'my-sites',
			'item_css_id'         => $this->id,
		);

		$sub_nav[] = array(
			'name'            => __( 'My Sites', 'buddyboss' ),
			'slug'            => 'my-sites',
			'parent_url'      => $parent_url,
			'parent_slug'     => $slug,
			'screen_function' => 'bp_blogs_screen_my_blogs',
			'position'        => 10,
		);

		// Setup navigation.
		parent::setup_nav( $main_nav, $sub_nav );
	}

	/**
	 * Set up bp-blogs integration with the WordPress admin bar.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @see BP_Component::setup_admin_bar() for a description of arguments.
	 *
	 * @param array $wp_admin_nav See BP_Component::setup_admin_bar()
	 *                            for description.
	 * @return bool
	 */
	public function setup_admin_bar( $wp_admin_nav = array() ) {

		/**
		 * Site/post/comment menus should not appear on single WordPress setups.
		 *
		 * Comments and posts made by users will still show in their activity.
		 */
		if ( ! is_multisite() ) {
			return false;
		}

		// Menus for logged in user.
		if ( is_user_logged_in() ) {

			// Setup the logged in user variables.
			$blogs_link = trailingslashit( bp_loggedin_user_domain() . bp_get_blogs_slug() );

			// Add the "Sites" sub menu.
			$wp_admin_nav[] = array(
				'parent' => buddypress()->my_account_menu_id,
				'id'     => 'my-account-' . $this->id,
				'title'  => __( 'Sites', 'buddyboss' ),
				'href'   => $blogs_link,
			);

			// My Sites.
			$wp_admin_nav[] = array(
				'parent'   => 'my-account-' . $this->id,
				'id'       => 'my-account-' . $this->id . '-my-sites',
				'title'    => __( 'My Sites', 'buddyboss' ),
				'href'     => $blogs_link,
				'position' => 10,
			);

			// Create a Site.
			if ( bp_blog_signup_enabled() ) {
				$wp_admin_nav[] = array(
					'parent'   => 'my-account-' . $this->id,
					'id'       => 'my-account-' . $this->id . '-create',
					'title'    => __( 'Create a Site', 'buddyboss' ),
					'href'     => trailingslashit( bp_get_blogs_directory_permalink() . 'create' ),
					'position' => 99,
				);
			}
		}

		parent::setup_admin_bar( $wp_admin_nav );
	}

	/**
	 * Set up the title for pages and <title>.
	 */
	public function setup_title() {

		// Set up the component options navigation for Site.
		if ( bp_is_blogs_component() ) {
			$bp = buddypress();

			if ( bp_is_my_profile() ) {
				if ( bp_is_active( 'xprofile' ) ) {
					$bp->bp_options_title = __( 'My Sites', 'buddyboss' );
				}

				// If we are not viewing the logged in user, set up the current
				// users avatar and name.
			} else {
				$bp->bp_options_avatar = bp_core_fetch_avatar(
					array(
						'item_id' => bp_displayed_user_id(),
						'type'    => 'thumb',
						'alt'     => sprintf( __( 'Profile photo of %s', 'buddyboss' ), bp_get_displayed_user_fullname() ),
					)
				);
				$bp->bp_options_title  = bp_get_displayed_user_fullname();
			}
		}

		parent::setup_title();
	}

	/**
	 * Setup cache groups
	 *
	 * @since BuddyPress 2.2.0
	 */
	public function setup_cache_groups() {

		// Global groups.
		wp_cache_add_global_groups(
			array(
				'bp_blog_meta',
				'bp_blogs',
			)
		);

		parent::setup_cache_groups();
	}

	/**
	 * Init the BuddyBoss REST API.
	 *
	 * @param array $controllers Optional. See BP_Component::rest_api_init() for description.
	 *
	 * @since BuddyBoss 1.3.5
	 */
	public function rest_api_init( $controllers = array() ) {
		if ( is_multisite() ) {
			$controllers = array(
				'BP_REST_Blogs_Endpoint',
			);

			// Support to Blog Avatar.
			if ( bp_is_active( 'blogs', 'site-icon' ) ) {
				$controllers[] = 'BP_REST_Attachments_Blog_Avatar_Endpoint';
			}
		}

		parent::rest_api_init( $controllers );
	}

	/**
	 * Register the Blogs Blocks.
	 *
	 * @since BuddyBoss 2.9.00
	 *
	 * @param array $blocks Optional. See BP_Component::blocks_init() for description.
	 */
	public function blocks_init( $blocks = array() ) {
		parent::blocks_init( array() );
	}
}
