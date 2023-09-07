<?php
/**
 * BuddyBoss Document Component Class.
 *
 * @package BuddyBoss\Document\Loader
 * @since   BuddyBoss 1.4.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Creates Document component.
 *
 * @since BuddyBoss 1.4.0
 */
#[\AllowDynamicProperties]
class BP_Document_Component extends BP_Component {

	/**
	 * The folder being currently accessed.
	 *
	 * @since BuddyBoss 1.4.0
	 * @var BP_Document_Folder
	 */
	public $current_folder;

	/**
	 * Default document extension.
	 *
	 * @since BuddyBoss 1.4.0
	 * @todo  Is this used anywhere? Is this a duplicate of $default_extension?
	 * @var string
	 */
	var $default_component;

	/**
	 * Default document extension.
	 *
	 * @since BuddyBoss 1.4.0
	 * @var string
	 */
	public $default_extension;

	/**
	 * Illegal document names/slugs.
	 *
	 * @since BuddyBoss 1.4.0
	 * @var array
	 */
	public $forbidden_names;

	/**
	 * The acceptable visibility levels for document.
	 *
	 * @see   bp_document_get_visibility_levels()
	 * @since BuddyBoss 1.4.0
	 * @var array
	 */
	public $visibility_levels = array();

	/**
	 * Start the document component creation process.
	 *
	 * @since BuddyBoss 1.4.0
	 */
	public function __construct() {
		parent::start(
			'document',
			__( 'Documents', 'buddyboss' ),
			buddypress()->plugin_dir,
			array(
				'adminbar_myaccount_order' => 100,
				'search_query_arg'         => 'document_search',
			)
		);

	}

	/**
	 * Include Document component files.
	 *
	 * @param array $includes See BP_Component::includes() for a description.
	 *
	 * @see   BP_Component::includes() for a description of arguments.
	 * @since BuddyBoss 1.4.0
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
	 * Only load up certain code when on specific pages.
	 *
	 * @since BuddyBoss 1.4.0
	 */
	public function late_includes() {
		// Bail if PHPUnit is running.
		if ( defined( 'BP_TESTS_DIR' ) ) {
			return;
		}

		if ( bp_is_document_component() ) {

			// Screens - Directory.
			if ( bp_is_document_directory() && ( bp_is_profile_document_support_enabled() || bp_is_group_document_support_enabled() ) ) {
				require $this->path . 'bp-document/screens/directory.php';
			}

			// Screens - User profile integration.
			if ( bp_is_user() && bp_is_profile_document_support_enabled() ) {
				require $this->path . 'bp-document/screens/document.php';

				/*
				 * Nav items.
				 *
				 * 'folder' is not a registered nav item, but we add a screen handler manually.
				 */
				if ( bp_is_user_document() && in_array( bp_current_action(), array( 'folders' ), true ) ) {
					require $this->path . 'bp-document/screens/' . bp_current_action() . '.php';
				}
			}

			// Theme compatibility.
			new BP_Document_Theme_Compat();
		}
	}

	/**
	 * Set up component global data.
	 *
	 * @param array $args See BP_Component::setup_globals() for a description.
	 *
	 * @see   BP_Component::setup_globals() for a description of arguments.
	 * @since BuddyBoss 1.4.0
	 */
	public function setup_globals( $args = array() ) {
		$bp = buddypress();

		// Define a slug, if necessary.
		if ( ! defined( 'BP_DOCUMENT_SLUG' ) ) {
			define( 'BP_DOCUMENT_SLUG', $this->id );
		}

		// Register the visibility levels. See bp_document_get_visibility_levels() to filter.
		$this->visibility_levels = array(
			'public'   => __( 'Public', 'buddyboss' ),
			'loggedin' => __( 'All Members', 'buddyboss' ),
		);

		if ( bp_is_active( 'friends' ) ) {
			$this->visibility_levels['friends'] = __( 'My Connections', 'buddyboss' );
		}

		if ( bp_is_active( 'groups' ) ) {
			$this->visibility_levels['grouponly'] = __( 'Group members', 'buddyboss' );
		}

		$this->visibility_levels['onlyme'] = __( 'Only Me', 'buddyboss' );

		// Global tables for document component.
		$global_tables = array(
			'table_name'             => $bp->table_prefix . 'bp_document',
			'table_name_meta'        => $bp->table_prefix . 'bp_document_meta',
			'table_name_folder'      => $bp->table_prefix . 'bp_document_folder',
			'table_name_folder_meta' => $bp->table_prefix . 'bp_document_folder_meta',
		);

		// Metadata tables for groups component.
		$meta_tables = array(
			'document' => $bp->table_prefix . 'bp_document_meta',
			'folder'   => $bp->table_prefix . 'bp_document_folder_meta',
		);

		// Fetch the default directory title.
		$default_directory_titles = bp_core_get_directory_page_default_titles();
		$default_directory_title  = $default_directory_titles[ $this->id ];

		// All globals for document component.
		// Note that global_tables is included in this array.
		parent::setup_globals(
			array(
				'slug'            => 'documents',
				'root_slug'       => isset( $bp->pages->document->slug ) ? $bp->pages->document->slug : BP_DOCUMENT_SLUG,
				'has_directory'   => true,
				'global_tables'   => $global_tables,
				'directory_title' => isset( $bp->pages->document->title ) ? $bp->pages->document->title : $default_directory_title,
				'search_string'   => __( 'Search Documents&hellip;', 'buddyboss' ),
				'meta_tables'     => $meta_tables,
			)
		);

		/* Single Folder Globals **********************************************/

		// Are we viewing a single folder?
		if ( bp_is_document_component() && bp_is_single_folder() && ( $folder_id = BP_Document_Folder::folder_exists( bp_action_variable( 0 ) ) ) ) {
			$bp->is_single_item   = true;
			$this->current_folder = folders_get_folder( $folder_id );

			// Set current_folder to 0 to prevent debug errors.
		} else {
			$this->current_folder = 0;
		}

	}

	/**
	 * Set up the actions.
	 *
	 * @since BuddyBoss 1.4.0
	 */
	public function setup_actions() {

		// Perform a daily tidy up.
		if ( ! wp_next_scheduled( 'bp_document_delete_orphaned_attachments_hook' ) ) {
			wp_schedule_event( strtotime('tomorrow midnight'), 'daily', 'bp_document_delete_orphaned_attachments_hook' );
		}

		add_action( 'bp_document_delete_orphaned_attachments_hook', 'bp_document_delete_orphaned_attachments' );

		parent::setup_actions();
	}

	/**
	 * Set up component navigation.
	 *
	 * @param array $main_nav Optional. See BP_Component::setup_nav() for description.
	 * @param array $sub_nav  Optional. See BP_Component::setup_nav() for description.
	 *
	 * @since BuddyBoss 1.4.0
	 * @see   BP_Component::setup_nav() for a description of arguments.
	 */
	public function setup_nav( $main_nav = array(), $sub_nav = array() ) {

		if ( bp_is_active( 'media' ) && bp_is_profile_document_support_enabled() ) {

			// Determine user to use.
			if ( bp_displayed_user_domain() ) {
				$user_domain = bp_displayed_user_domain();
			} elseif ( bp_loggedin_user_domain() ) {
				$user_domain = bp_loggedin_user_domain();
			} else {
				return;
			}

			$slug          = bp_get_document_slug();
			$document_link = trailingslashit( $user_domain . $slug );

			// Only grab count if we're on a user page and current user has access.
			if ( bp_is_user() ) {
				$nav_name = __( 'Documents', 'buddyboss' );
			} else {
				$nav_name = __( 'Documents', 'buddyboss' );
			}

			// Add 'Documents' to the main navigation.
			$main_nav = array(
				'name'                => $nav_name,
				'slug'                => $slug,
				'position'            => 90,
				'screen_function'     => 'document_screen',
				'default_subnav_slug' => 'my-document',
				'item_css_id'         => $this->id,
			);

			// Add the subnav items to the profile.
			$sub_nav[] = array(
				'name'            => $nav_name,
				'slug'            => 'my-document',
				'parent_url'      => $document_link,
				'parent_slug'     => $slug,
				'screen_function' => 'document_screen',
				'position'        => 10,
				'item_css_id'     => 'document-my-documents',
			);

		}

		parent::setup_nav( $main_nav, $sub_nav );

	}

	/**
	 * Set up the component entries in the WordPress Admin Bar.
	 *
	 * @param array $wp_admin_nav See BP_Component::setup_admin_bar() for a description.
	 *
	 * @see   BP_Component::setup_nav() for a description of the $wp_admin_nav
	 *        parameter array.
	 * @since BuddyBoss 1.4.0
	 */
	public function setup_admin_bar( $wp_admin_nav = array() ) {

		// Menus for logged in user.
		if ( is_user_logged_in() && bp_is_active( 'media' ) && bp_is_profile_document_support_enabled() ) {

			// Setup the logged in user variables.
			$document_link = trailingslashit( bp_loggedin_user_domain() . bp_get_document_slug() );

			// Add main Messages menu.
			$wp_admin_nav[] = array(
				'parent' => buddypress()->my_account_menu_id,
				'id'     => 'my-account' . $this->id,
				'title'  => __( 'Documents', 'buddyboss' ),
				'href'   => $document_link,
			);

			// Document.
			$wp_admin_nav[] = array(
				'parent'   => 'my-account' . $this->id,
				'id'       => 'my-account-' . $this->id . '-my-document',
				'title'    => __( 'My Documents', 'buddyboss' ),
				'href'     => $document_link,
				'position' => 10,
			);

		}

		parent::setup_admin_bar( $wp_admin_nav );
	}

	/**
	 * Set up the title for pages and <title>.
	 *
	 * @since BuddyBoss 1.4.0
	 */
	public function setup_title() {

		if ( bp_is_document_component() ) {
			$bp = buddypress();

			if ( bp_is_my_profile() && ! bp_is_single_folder() ) {
				$bp->bp_options_title = __( 'My Documents', 'buddyboss' );

			} elseif ( ! bp_is_my_profile() && ! bp_is_single_folder() ) {
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
	 * @since BuddyBoss 1.4.0
	 */
	public function setup_cache_groups() {

		// Global groups.
		wp_cache_add_global_groups(
			array(
				'bp_document',
				'bp_document_folder',
				'document_meta',
				'document_folder_meta',
			)
		);

		parent::setup_cache_groups();
	}

	/**
	 * Init the BuddyBoss REST API.
	 *
	 * @param array $controllers Optional. See BP_Component::rest_api_init() for description.
	 *
	 * @since BuddyBoss 1.5.1
	 */
	public function rest_api_init( $controllers = array() ) {
		parent::rest_api_init( array(
			'BP_REST_Document_Endpoint',
			'BP_REST_Document_Folder_Endpoint',
			'BP_REST_Document_Details_Endpoint',
		) );
	}
}
