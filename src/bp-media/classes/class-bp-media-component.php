<?php
/**
 * BuddyBoss Media Component Class.
 *
 * @package BuddyBoss\Media\Loader
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Creates Media component.
 *
 * @since BuddyBoss 1.0.0
 */
#[\AllowDynamicProperties]
class BP_Media_Component extends BP_Component {

	/**
	 * The album being currently accessed.
	 *
	 * @since BuddyBoss 1.0.0
	 * @var BP_Media_Album
	 */
	public $current_album;

	/**
	 * Default media extension.
	 *
	 * @since BuddyBoss 1.0.0
	 * @todo Is this used anywhere? Is this a duplicate of $default_extension?
	 * @var string
	 */
	var $default_component;

	/**
	 * Default media extension.
	 *
	 * @since BuddyBoss 1.0.0
	 * @var string
	 */
	public $default_extension;

	/**
	 * Illegal media names/slugs.
	 *
	 * @since BuddyBoss 1.0.0
	 * @var array
	 */
	public $forbidden_names;

	/**
	 * The acceptable visibility levels for media.
	 *
	 * @see bp_media_get_visibility_levels()
	 *
	 * @since BuddyBoss 1.2.3
	 * @var array
	 */
	public $visibility_levels = array();

	/**
	 * Start the media component creation process.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function __construct() {
		parent::start(
			'media',
			__( 'Photos', 'buddyboss' ),
			buddypress()->plugin_dir,
			array(
				'adminbar_myaccount_order' => 100,
				'search_query_arg'         => 'media_search',
			)
		);

	}

	/**
	 * Include Media component files.
	 *
	 * @since BuddyBoss 1.0.0
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

		if ( class_exists( 'BP_Media_Rotation' ) ) {
			$GLOBALS['bp_media_rotation'] = new BP_Media_Rotation();
		}

		parent::includes( $includes );
	}

	/**
	 * Late includes method.
	 *
	 * Only load up certain code when on specific pages.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function late_includes() {

		if ( ! class_exists( 'BP_Media_Stream' ) ) {
			// Include Media Streamline.
			require $this->path . 'bp-media/classes/class-bp-media-stream.php';
		}

		// Bail if PHPUnit is running.
		if ( defined( 'BP_TESTS_DIR' ) ) {
			return;
		}

		if ( bp_is_media_component() ) {

			// Screens - Directory.
			if ( bp_is_media_directory() ) {
				require $this->path . 'bp-media/screens/directory.php';
			}

			// Screens - User profile integration.
			if ( bp_is_user() ) {
				require $this->path . 'bp-media/screens/media.php';

				/*
				 * Nav items.
				 *
				 * 'album' is not a registered nav item, but we add a screen handler manually.
				 */
				if ( bp_is_user_media() && in_array( bp_current_action(), array( 'albums' ), true ) ) {
					require $this->path . 'bp-media/screens/' . bp_current_action() . '.php';
				}
			}

			// Theme compatibility.
			new BP_Media_Theme_Compat();
		}
	}

	/**
	 * Set up component global data.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @see BP_Component::setup_globals() for a description of arguments.
	 *
	 * @param array $args See BP_Component::setup_globals() for a description.
	 */
	public function setup_globals( $args = array() ) {
		$bp = buddypress();

		// Define a slug, if necessary.
		if ( ! defined( 'BP_MEDIA_SLUG' ) ) {
			define( 'BP_MEDIA_SLUG', $this->id );
		}

		// Register the visibility levels. See bp_media_get_visibility_levels() to filter.
		$this->visibility_levels = array(
			'public'   => __( 'Public', 'buddyboss' ),
			'loggedin' => __( 'All Members', 'buddyboss' ),
		);

		if ( bp_is_active( 'friends' ) ) {
			$this->visibility_levels['friends'] = __( 'My Connections', 'buddyboss' );
		}

		$this->visibility_levels['onlyme'] = __( 'Only Me', 'buddyboss' );

		// Global tables for media component.
		$global_tables = array(
			'table_name'        => $bp->table_prefix . 'bp_media',
			'table_name_albums' => $bp->table_prefix . 'bp_media_albums',
		);

		// Fetch the default directory title.
		$default_directory_titles = bp_core_get_directory_page_default_titles();
		$default_directory_title  = $default_directory_titles[ $this->id ];

		// All globals for media component.
		// Note that global_tables is included in this array.
		parent::setup_globals(
			array(
				'slug'            => 'photos',
				'root_slug'       => isset( $bp->pages->media->slug ) ? $bp->pages->media->slug : BP_MEDIA_SLUG,
				'has_directory'   => true,
				'global_tables'   => $global_tables,
				'directory_title' => isset( $bp->pages->media->title ) ? $bp->pages->media->title : $default_directory_title,
				'search_string'   => __( 'Search Photos&hellip;', 'buddyboss' ),
			)
		);

		/* Single Album Globals **********************************************/

		// Are we viewing a single album?
		if ( bp_is_media_component() && bp_is_single_album()
			 && ( $album_id = BP_Media_Album::album_exists( bp_action_variable( 0 ) ) )
		) {
			$bp->is_single_item  = true;
			$this->current_album = albums_get_album( $album_id );

			// Set current_album to 0 to prevent debug errors.
		} else {
			$this->current_album = 0;
		}

	}

	/**
	 * Set up the actions.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function setup_actions() {

		// Perform a daily tidy up.
		if ( ! wp_next_scheduled( 'bp_media_delete_orphaned_attachments_hook' ) ) {
			wp_schedule_event( strtotime('tomorrow midnight'), 'daily', 'bp_media_delete_orphaned_attachments_hook' );
		}

		add_action( 'bp_media_delete_orphaned_attachments_hook', 'bp_media_delete_orphaned_attachments' );

		parent::setup_actions();
	}

	/**
	 * Set up component navigation.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @see BP_Component::setup_nav() for a description of arguments.
	 *
	 * @param array $main_nav Optional. See BP_Component::setup_nav() for description.
	 * @param array $sub_nav  Optional. See BP_Component::setup_nav() for description.
	 */
	public function setup_nav( $main_nav = array(), $sub_nav = array() ) {

		if ( bp_is_profile_media_support_enabled() ) {

			// Determine user to use.
			if ( bp_displayed_user_domain() ) {
				$user_domain = bp_displayed_user_domain();
			} elseif ( bp_loggedin_user_domain() ) {
				$user_domain = bp_loggedin_user_domain();
			} else {
				return;
			}

			$slug       = bp_get_media_slug();
			$media_link = trailingslashit( $user_domain . $slug );

			// Only grab count if we're on a user page and current user has access.
			if ( bp_is_user() ) {
				$count     = bp_media_get_total_media_count( bp_displayed_user_id() );
				$class     = ( 0 === $count ) ? 'no-count' : 'count';
				$nav_name  = __( 'Photos', 'buddyboss' );
				$nav_name .= sprintf(
					' <span class="%s">%s</span>',
					esc_attr( $class ),
					$count
				);
			} else {
				$nav_name = __( 'Photos', 'buddyboss' );
			}

			// Add 'Photos' to the main navigation.
			$main_nav = array(
				'name'                => $nav_name,
				'slug'                => $slug,
				'position'            => 80,
				'screen_function'     => 'media_screen',
				'default_subnav_slug' => 'my-media',
				'item_css_id'         => $this->id,
			);

			// Add the subnav items to the profile.
			$sub_nav[] = array(
				'name'            => $nav_name,
				'slug'            => 'my-media',
				'parent_url'      => $media_link,
				'parent_slug'     => $slug,
				'screen_function' => 'media_screen',
				'position'        => 10,
				'item_css_id'     => 'media-my-media',
			);

			if ( bp_is_profile_albums_support_enabled() ) {

				// Add the subnav items to the profile.
				$sub_nav[] = array(
					'name'            => __( 'Albums', 'buddyboss' ),
					'slug'            => 'albums',
					'parent_url'      => $media_link,
					'parent_slug'     => $slug,
					'screen_function' => 'media_screen',
					'position'        => 10,
				);
			}
		}

		parent::setup_nav( $main_nav, $sub_nav );

	}

	/**
	 * Set up the component entries in the WordPress Admin Bar.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @see BP_Component::setup_nav() for a description of the $wp_admin_nav
	 *      parameter array.
	 *
	 * @param array $wp_admin_nav See BP_Component::setup_admin_bar() for a description.
	 */
	public function setup_admin_bar( $wp_admin_nav = array() ) {
		// Menus for logged in user.
		if ( is_user_logged_in() && bp_is_profile_media_support_enabled() ) {

			// Setup the logged in user variables.
			$media_link = trailingslashit( bp_loggedin_user_domain() . bp_get_media_slug() );

			// Add main Messages menu.
			$wp_admin_nav[] = array(
				'parent' => buddypress()->my_account_menu_id,
				'id'     => 'my-account-' . $this->id,
				'title'  => __( 'Photos', 'buddyboss' ),
				'href'   => $media_link,
			);

			// Media.
			$wp_admin_nav[] = array(
				'parent'   => 'my-account-' . $this->id,
				'id'       => 'my-account-' . $this->id . '-my-media',
				'title'    => __( 'My Photos', 'buddyboss' ),
				'href'     => $media_link,
				'position' => 10,
			);

			if ( bp_is_profile_albums_support_enabled() ) {
				// Albums.
				$wp_admin_nav[] = array(
					'parent'   => 'my-account-' . $this->id,
					'id'       => 'my-account-' . $this->id . '-albums',
					'title'    => __( 'My Albums', 'buddyboss' ),
					'href'     => trailingslashit( $media_link . 'albums' ),
					'position' => 20,
				);
			}
		}

		parent::setup_admin_bar( $wp_admin_nav );
	}

	/**
	 * Set up the title for pages and <title>.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function setup_title() {

		if ( bp_is_media_component() ) {
			$bp = buddypress();

			if ( bp_is_my_profile() && ! bp_is_single_album() ) {
				$bp->bp_options_title = __( 'My Photos', 'buddyboss' );

			} elseif ( ! bp_is_my_profile() && ! bp_is_single_album() ) {
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
	 * Setup cache groups.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function setup_cache_groups() {

		// Global groups.
		wp_cache_add_global_groups(
			array(
				'bp_media',
				'bp_media_album',
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
		parent::rest_api_init(
			array(
				'BP_REST_Media_Endpoint',
				'BP_REST_Media_Albums_Endpoint',
				'BP_REST_Media_Details_Endpoint',
			)
		);
	}
}
