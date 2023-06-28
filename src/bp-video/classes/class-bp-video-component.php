<?php
/**
 * BuddyBoss Video Component Class.
 *
 * @package BuddyBoss\Video\Loader
 * @since BuddyBoss 1.7.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Creates Video component.
 *
 * @since BuddyBoss 1.7.0
 */
#[\AllowDynamicProperties]
class BP_Video_Component extends BP_Component {

	/**
	 * Default video extension.
	 *
	 * @since BuddyBoss 1.7.0
	 * @todo Is this used anywhere? Is this a duplicate of $default_extension?
	 * @var string
	 */
	public $default_component;

	/**
	 * Default video extension.
	 *
	 * @since BuddyBoss 1.7.0
	 * @var string
	 */
	public $default_extension;

	/**
	 * Illegal video names/slugs.
	 *
	 * @since BuddyBoss 1.7.0
	 * @var array
	 */
	public $forbidden_names;

	/**
	 * The acceptable visibility levels for video.
	 *
	 * @see bp_video_get_visibility_levels()
	 *
	 * @since BuddyBoss 1.7.0
	 * @var array
	 */
	public $visibility_levels = array();

	/**
	 * Start the video component creation process.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	public function __construct() {
		parent::start(
			'video',
			__( 'Videos', 'buddyboss' ),
			buddypress()->plugin_dir,
			array(
				'adminbar_myaccount_order' => 100,
				'search_query_arg'         => 'video_search',
			)
		);

	}

	/**
	 * Include Video component files.
	 *
	 * @since BuddyBoss 1.7.0
	 *
	 * @see BP_Component::includes() for a description of arguments.
	 *
	 * @param array $includes See BP_Component::includes() for a description.
	 */
	public function includes( $includes = array() ) {
		$includes = array(
			'filters',
			'template',
			'functions',
			'settings',
			'cache',
		);

		parent::includes( $includes );
	}

	/**
	 * Late includes method.
	 *
	 * Only load up certain code when on specific pages.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	public function late_includes() {
		// Bail if PHPUnit is running.
		if ( defined( 'BP_TESTS_DIR' ) ) {
			return;
		}

		if ( bp_is_video_component() ) {

			// Screens - Directory.
			if ( bp_is_video_directory() ) {
				require $this->path . 'bp-video/screens/directory.php';
			}

			// Screens - User profile integration.
			if ( bp_is_user() ) {
				require $this->path . 'bp-video/screens/video.php';
			}

			// Theme compatibility.
			new BP_Video_Theme_Compat();
		}
	}

	/**
	 * Set up component global data.
	 *
	 * @since BuddyBoss 1.7.0
	 *
	 * @see BP_Component::setup_globals() for a description of arguments.
	 *
	 * @param array $args See BP_Component::setup_globals() for a description.
	 */
	public function setup_globals( $args = array() ) {
		$bp = buddypress();

		// Define a slug, if necessary.
		if ( ! defined( 'BP_VIDEO_SLUG' ) ) {
			define( 'BP_VIDEO_SLUG', $this->id );
		}

		// Register the visibility levels. See bp_video_get_visibility_levels() to filter.
		$this->visibility_levels = array(
			'public'   => __( 'Public', 'buddyboss' ),
			'loggedin' => __( 'All Members', 'buddyboss' ),
		);

		if ( bp_is_active( 'friends' ) ) {
			$this->visibility_levels['friends'] = __( 'My Connections', 'buddyboss' );
		}

		$this->visibility_levels['onlyme'] = __( 'Only Me', 'buddyboss' );

		// Global tables for video component.
		$global_tables = array(
			'table_name'        => $bp->table_prefix . 'bp_media',
			'table_name_albums' => $bp->table_prefix . 'bp_media_albums',
		);

		// Fetch the default directory title.
		$default_directory_titles = bp_core_get_directory_page_default_titles();
		$default_directory_title  = $default_directory_titles[ $this->id ];

		// All globals for video component.
		// Note that global_tables is included in this array.
		parent::setup_globals(
			array(
				'slug'            => 'videos',
				'root_slug'       => isset( $bp->pages->video->slug ) ? $bp->pages->video->slug : BP_VIDEO_SLUG,
				'has_directory'   => true,
				'global_tables'   => $global_tables,
				'directory_title' => isset( $bp->pages->video->title ) ? $bp->pages->video->title : $default_directory_title,
				'search_string'   => __( 'Search Videos&hellip;', 'buddyboss' ),
			)
		);
	}

	/**
	 * Set up the actions.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	public function setup_actions() {

		// Perform a daily tidy up.
		if ( ! wp_next_scheduled( 'bp_video_delete_orphaned_attachments_hook' ) ) {
			wp_schedule_event( strtotime('tomorrow midnight'), 'daily', 'bp_video_delete_orphaned_attachments_hook' );
		}

		add_action( 'bp_video_delete_orphaned_attachments_hook', 'bp_video_delete_orphaned_attachments' );

		parent::setup_actions();
	}

	/**
	 * Set up component navigation.
	 *
	 * @since BuddyBoss 1.7.0
	 *
	 * @see BP_Component::setup_nav() for a description of arguments.
	 *
	 * @param array $main_nav Optional. See BP_Component::setup_nav() for description.
	 * @param array $sub_nav  Optional. See BP_Component::setup_nav() for description.
	 */
	public function setup_nav( $main_nav = array(), $sub_nav = array() ) {

		if ( bp_is_profile_video_support_enabled() ) {

			// Determine user to use.
			if ( bp_displayed_user_domain() ) {
				$user_domain = bp_displayed_user_domain();
			} elseif ( bp_loggedin_user_domain() ) {
				$user_domain = bp_loggedin_user_domain();
			} else {
				return;
			}

			$slug       = bp_get_video_slug();
			$video_link = trailingslashit( $user_domain . $slug );

			// Only grab count if we're on a user page and current user has access.
			if ( bp_is_user() ) {
				$count     = bp_video_get_total_video_count( bp_displayed_user_id() );
				$class     = ( 0 === $count ) ? 'no-count' : 'count';
				$nav_name  = __( 'Videos', 'buddyboss' );
				$nav_name .= sprintf(
					' <span class="%s">%s</span>',
					esc_attr( $class ),
					$count
				);
			} else {
				$nav_name = __( 'Videos', 'buddyboss' );
			}

			// Add 'Videos' to the main navigation.
			$main_nav = array(
				'name'                => $nav_name,
				'slug'                => $slug,
				'position'            => 80,
				'screen_function'     => 'video_screen',
				'default_subnav_slug' => 'my-video',
				'item_css_id'         => $this->id,
			);

			// Add the sub nav items to the profile.
			$sub_nav[] = array(
				'name'            => $nav_name,
				'slug'            => 'my-video',
				'parent_url'      => $video_link,
				'parent_slug'     => $slug,
				'screen_function' => 'video_screen',
				'position'        => 10,
				'item_css_id'     => 'video-my-video',
			);
		}

		parent::setup_nav( $main_nav, $sub_nav );
	}

	/**
	 * Set up the component entries in the WordPress Admin Bar.
	 *
	 * @since BuddyBoss 1.7.0
	 *
	 * @see BP_Component::setup_nav() for a description of the $wp_admin_nav
	 *      parameter array.
	 *
	 * @param array $wp_admin_nav See BP_Component::setup_admin_bar() for a description.
	 */
	public function setup_admin_bar( $wp_admin_nav = array() ) {
		// Menus for logged in user.
		if ( is_user_logged_in() && bp_is_profile_video_support_enabled() ) {

			// Setup the logged in user variables.
			$video_link = trailingslashit( bp_loggedin_user_domain() . bp_get_video_slug() );

			// Add main Messages menu.
			$wp_admin_nav[] = array(
				'parent' => buddypress()->my_account_menu_id,
				'id'     => 'my-account-' . $this->id,
				'title'  => __( 'Videos', 'buddyboss' ),
				'href'   => $video_link,
			);

			// Video.
			$wp_admin_nav[] = array(
				'parent'   => 'my-account-' . $this->id,
				'id'       => 'my-account-' . $this->id . '-my-video',
				'title'    => __( 'My Videos', 'buddyboss' ),
				'href'     => $video_link,
				'position' => 10,
			);
		}

		parent::setup_admin_bar( $wp_admin_nav );
	}

	/**
	 * Set up the title for pages and <title>.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	public function setup_title() {

		if ( bp_is_video_component() ) {
			$bp = buddypress();

			if ( bp_is_my_profile() && ! bp_is_single_album() ) {
				$bp->bp_options_title = __( 'My Videos', 'buddyboss' );

			} elseif ( ! bp_is_my_profile() && ! bp_is_single_album() ) {
				$bp->bp_options_avatar = bp_core_fetch_avatar(
					array(
						'item_id' => bp_displayed_user_id(),
						'type'    => 'thumb',
						'alt'     => sprintf(
							/* translators: User Display Name. */
							__( 'Profile video of %s', 'buddyboss' ),
							bp_get_displayed_user_fullname()
						),
					)
				);
				$bp->bp_options_title  = bp_get_displayed_user_fullname();
			}
		}

		parent::setup_title();
	}

	/**
	 * Setup cache groups.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	public function setup_cache_groups() {

		// Global groups.
		wp_cache_add_global_groups(
			array(
				'bp_video',
				'bp_video_album',
			)
		);

		parent::setup_cache_groups();
	}

	/**
	 * Init the BuddyBoss REST API.
	 *
	 * @param array $controllers Optional. See BP_Component::rest_api_init() for description.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	public function rest_api_init( $controllers = array() ) {
		parent::rest_api_init(
			array(
				'BP_REST_Video_Endpoint',
				'BP_REST_Video_Poster_Endpoint',
				'BP_REST_Video_Details_Endpoint',
			)
		);
	}
}
