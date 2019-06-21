<?php
/**
 * Plugin Dependency Action Hooks.
 *
 * The purpose of the following hooks is to mimic the behavior of something
 * called 'plugin dependency' which enables a plugin to have plugins of their
 * own in a safe and reliable way.
 *
 * We do this in BuddyPress by mirroring existing WordPress hooks in many places
 * allowing dependant plugins to hook into the BuddyPress specific ones, thus
 * guaranteeing proper code execution only when BuddyPress is active.
 *
 * The following functions are wrappers for hooks, allowing them to be
 * manually called and/or piggy-backed on top of other hooks if needed.
 *
 * @package BuddyBoss\Core
 * @since BuddyPress 1.7.0
 */

/**
 * Fire the 'bp_include' action, where plugins should include files.
 *
 * @since BuddyPress 1.2.5
 */
function bp_include() {

	/**
	 * Fires inside the 'bp_include' function, where plugins should include files.
	 *
	 * @since BuddyPress 1.2.5
	 */
	do_action( 'bp_include' );
}

/**
 * Fire the 'bp_late_include' action for loading conditional files.
 *
 * @since BuddyPress 3.0.0
 */
function bp_late_include() {

	/**
	 * Fires the 'bp_late_include' action.
	 *
	 * Allow for conditional includes on certain pages.
	 *
	 * @since BuddyPress 3.0.0
	 */
	do_action( 'bp_late_include' );
}

/**
 * Fire the 'bp_setup_components' action, where plugins should initialize components.
 *
 * @since BuddyPress 1.6.0
 */
function bp_setup_components() {

	/**
	 * Fires inside the 'bp_setup_components' function, where plugins should initialize components.
	 *
	 * @since BuddyPress 1.6.0
	 */
	do_action( 'bp_setup_components' );
}

/**
 * Fire the 'bp_setup_components' action, where plugins should initialize components.
 *
 * @since BuddyPress 1.6.0
 */
function bp_setup_integrations() {

	/**
	 * Fires inside the 'bp_setup_integrations' function, where plugins should initialize components.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	do_action( 'bp_setup_integrations' );
}

/**
 * Fire the 'bp_setup_canonical_stack' action, where plugins should set up their canonical URL.
 *
 * @since BuddyPress 2.1.0
 */
function bp_setup_canonical_stack() {

	/**
	 * Fires inside the 'bp_setup_canonical_stack' function, where plugins should set up their canonical URL.
	 *
	 * @since BuddyPress 2.1.0
	 */
	do_action( 'bp_setup_canonical_stack' );
}

/**
 * Fire the 'bp_register_taxonomies' action, where plugins should register taxonomies.
 *
 * @since BuddyPress 2.2.0
 */
function bp_register_taxonomies() {

	/**
	 * Fires inside the 'bp_register_taxonomies' function, where plugins should register taxonomies.
	 *
	 * @since BuddyPress 2.2.0
	 */
	do_action( 'bp_register_taxonomies' );
}

/**
 * Fire the 'bp_register_post_types' action, where plugins should register post types.
 *
 * @since BuddyPress 2.5.0
 */
function bp_register_post_types() {

	/**
	 * Fires inside the 'bp_register_post_types' function, where plugins should register post types.
	 *
	 * @since BuddyPress 2.5.0
	 */
	do_action( 'bp_register_post_types' );
}

/**
 * Fire the 'bp_setup_globals' action, where plugins should initialize global settings.
 *
 * @since BuddyPress 1.2.0
 */
function bp_setup_globals() {

	/**
	 * Fires inside the 'bp_setup_globals' function, where plugins should initialize global settings.
	 *
	 * @since BuddyPress 1.2.0
	 */
	do_action( 'bp_setup_globals' );
}

/**
 * Fire the 'bp_setup_nav' action, where plugins should register their navigation items.
 *
 * @since BuddyPress 1.2.0
 */
function bp_setup_nav() {

	/**
	 * Fires inside the 'bp_setup_nav' function, where plugins should register their navigation items.
	 *
	 * @since BuddyPress 1.2.0
	 */
	do_action( 'bp_setup_nav' );
}

/**
 * Fire the 'bp_setup_admin_bar' action, where plugins should add items to the WP admin bar.
 *
 * @since BuddyPress 1.5.0
 */
function bp_setup_admin_bar() {
	if ( bp_use_wp_admin_bar() ) {

		/**
		 * Fires inside the 'bp_setup_admin_bar' function, where plugins should add items to the WP admin bar.
		 *
		 * This hook will only fire if bp_use_wp_admin_bar() returns true.
		 *
		 * @since BuddyPress 1.5.0
		 */
		do_action( 'bp_setup_admin_bar', array() );
	}
}

/**
 * Fire the 'bp_setup_title' action, where plugins should modify the page title.
 *
 * @since BuddyPress 1.5.0
 */
function bp_setup_title() {

	/**
	 * Fires inside the 'bp_setup_title' function, where plugins should modify the page title.
	 *
	 * @since BuddyPress 1.5.0
	 */
	do_action( 'bp_setup_title' );
}

/**
 * Fire the 'bp_register_widgets' action, where plugins should register widgets.
 *
 * @since BuddyPress 1.2.0
 */
function bp_setup_widgets() {

	/**
	 * Fires inside the 'bp_register_widgets' function, where plugins should register widgets.
	 *
	 * @since BuddyPress 1.2.0
	 */
	do_action( 'bp_register_widgets' );
}

/**
 * Fire the 'bp_register_member_types' action, where plugins should register profile types.
 *
 * @since BuddyPress 2.3.0
 */
function bp_register_member_types() {

	/**
	 * Fires inside bp_register_member_types(), so plugins can register profile types.
	 *
	 * @since BuddyPress 2.3.0
	 */
	do_action( 'bp_register_member_types' );
}

/**
 * Fire the 'bp_setup_cache_groups' action, where cache groups are registered.
 *
 * @since BuddyPress 2.2.0
 */
function bp_setup_cache_groups() {

	/**
	 * Fires inside the 'bp_setup_cache_groups' function, where cache groups are registered.
	 *
	 * @since BuddyPress 2.2.0
	 */
	do_action( 'bp_setup_cache_groups' );
}

/**
 * Set up the currently logged-in user.
 *
 * @since BuddyPress 1.7.0
 *
 * @link https://buddypress.trac.wordpress.org/ticket/6046
 * @link https://core.trac.wordpress.org/ticket/24169
 */
function bp_setup_current_user() {

	/**
	 * Fires to set up the current user setup process.
	 *
	 * @since BuddyPress 1.7.0
	 */
	do_action( 'bp_setup_current_user' );
}

/**
 * Fire the 'bp_init' action, BuddyPress's main initialization hook.
 *
 * @since BuddyPress 1.2.5
 */
function bp_init() {

	/**
	 * Fires inside the 'bp_init' function, BuddyPress' main initialization hook.
	 *
	 * @since BuddyPress 1.2.0
	 */
	do_action( 'bp_init' );
}

/**
 * Fire the 'bp_rest_api_init' action, where BuddyPress registers REST API endpoints.
 *
 * @since BuddyPress 2.6.0
 */
function bp_rest_api_init() {

	/**
	 * Fires the 'bp_rest_api_init' function, where BuddyPress registers REST API endpoints.
	 *
	 * @since BuddyPress 2.6.0
	 */
	do_action( 'bp_rest_api_init' );
}

/**
 * Fire the 'bp_customize_register' action when the Customizer has loaded,
 * allowing scripts and styles to be initialized.
 *
 * @since BuddyPress 2.5.0
 *
 * @param WP_Customize_Manager $customizer Customizer instance.
 */
function bp_customize_register( WP_Customize_Manager $customizer ) {

	/**
	 * Fires once the Customizer has loaded, allow scripts and styles to be initialized.
	 *
	 * @since BuddyPress 2.5.0
	 *
	 * @param WP_Customize_Manager $customizer Customizer instance.
	 */
	do_action( 'bp_customize_register', $customizer );
}

/**
 * Fire the 'bp_loaded' action, which fires after BP's core plugin files have been loaded.
 *
 * Attached to 'plugins_loaded'.
 *
 * @since BuddyPress 1.2.0
 */
function bp_loaded() {

	/**
	 * Fires inside the 'bp_loaded' function, which fires after BP's core plugin files have been loaded.
	 *
	 * @since BuddyPress 1.2.5
	 */
	do_action( 'bp_loaded' );
}

/**
 * Fire the 'bp_ready' action, which runs after BP is set up and the page is about to render.
 *
 * Attached to 'wp'.
 *
 * @since BuddyPress 1.6.0
 */
function bp_ready() {

	/**
	 * Fires inside the 'bp_ready' function, which runs after BP is set up and the page is about to render.
	 *
	 * @since BuddyPress 1.6.0
	 */
	do_action( 'bp_ready' );
}

/**
 * Fire the 'bp_actions' action, which runs just before rendering.
 *
 * Attach potential template actions, such as catching form requests or routing
 * custom URLs.
 *
 * @since BuddyPress 1.5.0
 */
function bp_actions() {

	/**
	 * Fires inside the 'bp_actions' function, which runs just before rendering.
	 *
	 * @since BuddyPress 1.5.0
	 */
	do_action( 'bp_actions' );
}

/**
 * Fire the 'bp_screens' action, which runs just before rendering.
 *
 * Runs just after 'bp_actions'. Use this hook to attach your template
 * loaders.
 *
 * @since BuddyPress 1.5.0
 */
function bp_screens() {

	/**
	 * Fires inside the 'bp_screens' function, which runs just before rendering.
	 *
	 * Runs just after 'bp_actions'. Use this hook to attach your template loaders.
	 *
	 * @since BuddyPress 1.5.0
	 */
	do_action( 'bp_screens' );
}

/**
 * Fire 'bp_widgets_init', which runs after widgets have been set up.
 *
 * Hooked to 'widgets_init'.
 *
 * @since BuddyPress 1.6.0
 */
function bp_widgets_init() {

	/**
	 * Fires inside the 'bp_widgets_init' function, which runs after widgets have been set up.
	 *
	 * Hooked to 'widgets_init'.
	 *
	 * @since BuddyPress 1.6.0
	 */
	do_action( 'bp_widgets_init' );
}

/**
 * Fire 'bp_head', which is used to hook scripts and styles in the <head>.
 *
 * Hooked to 'wp_head'.
 *
 * @since BuddyPress 1.6.0
 */
function bp_head() {

	/**
	 * Fires inside the 'bp_head' function, which runs on 'wp_head'.
	 *
	 * @since BuddyPress 1.6.0
	 */
	do_action( 'bp_head' );
}

/** Theme Permissions *********************************************************/

/**
 * Fire the 'bp_template_redirect' action.
 *
 * Run at 'template_redirect', just before WordPress selects and loads a theme
 * template. The main purpose of this hook in BuddyPress is to redirect users
 * who do not have the proper permission to access certain content.
 *
 * @since BuddyPress 1.6.0
 */
function bp_template_redirect() {

	/**
	 * Fires inside the 'bp_template_redirect' function.
	 *
	 * @since BuddyPress 1.6.0
	 */
	do_action( 'bp_template_redirect' );
}

/** Theme Helpers *************************************************************/

/**
 * Fire the 'bp_register_theme_packages' action.
 *
 * The main action used registering theme packages.
 *
 * @since BuddyPress 1.7.0
 */
function bp_register_theme_packages() {

	/**
	 * Fires inside the 'bp_register_theme_packages' function.
	 *
	 * @since BuddyPress 1.7.0
	 */
	do_action( 'bp_register_theme_packages' );
}

/**
 * Fire the 'bp_enqueue_scripts' action, where BP enqueues its CSS and JS.
 *
 * @since BuddyPress 1.6.0
 */
function bp_enqueue_scripts() {

	/**
	 * Fires inside the 'bp_enqueue_scripts' function, where BP enqueues its CSS and JS.
	 *
	 * @since BuddyPress 1.6.0
	 */
	do_action( 'bp_enqueue_scripts' );
}

/**
 * Fires the 'bp_enqueue_embed_scripts' action in the <head> for BP oEmbeds.
 *
 * @since BuddyPress 2.6.0
 */
function bp_enqueue_embed_scripts() {
	if ( ! is_buddypress() ) {
		return;
	}

	/**
	 * Enqueue CSS and JS files for BuddyPress embeds.
	 *
	 * @since BuddyPress 2.6.0
	 */
	do_action( 'bp_enqueue_embed_scripts' );
}

/**
 * Fire the 'bp_add_rewrite_tag' action, where BP adds its custom rewrite tags.
 *
 * @since BuddyPress 1.8.0
 */
function bp_add_rewrite_tags() {

	/**
	 * Fires inside the 'bp_add_rewrite_tags' function, where BP adds its custom rewrite tags.
	 *
	 * @since BuddyPress 1.8.0
	 */
	do_action( 'bp_add_rewrite_tags' );
}

/**
 * Fire the 'bp_add_rewrite_rules' action, where BP adds its custom rewrite rules.
 *
 * @since BuddyPress 1.9.0
 */
function bp_add_rewrite_rules() {

	/**
	 * Fires inside the 'bp_add_rewrite_rules' function, where BP adds its custom rewrite rules.
	 *
	 * @since BuddyPress 1.9.0
	 */
	do_action( 'bp_add_rewrite_rules' );
}

/**
 * Fire the 'bp_add_permastructs' action, where BP adds its BP-specific permalink structure.
 *
 * @since BuddyPress 1.9.0
 */
function bp_add_permastructs() {

	/**
	 * Fires inside the 'bp_add_permastructs' function, where BP adds its BP-specific permalink structure.
	 *
	 * @since BuddyPress 1.9.0
	 */
	do_action( 'bp_add_permastructs' );
}

/**
 * Fire the 'bp_init_background_updater' action, where BP updates data.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_init_background_updater() {
	global $background_updater;

	include_once buddypress()->plugin_dir . 'bp-core/classes/class-bp-background-updater.php';
	$background_updater = new BP_Background_Updater();

	/**
	 * Fires inside the 'bp_init_background_updater' function, where BP updates data.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	do_action( 'bp_init_background_updater' );
}

/**
 * Fire the 'bp_setup_theme' action.
 *
 * The main purpose of 'bp_setup_theme' is give themes a place to load their
 * BuddyPress-specific functionality.
 *
 * @since BuddyPress 1.6.0
 */
function bp_setup_theme() {

	/**
	 * Fires inside the 'bp_setup_theme' function.
	 *
	 * @since BuddyPress 1.6.0
	 */
	do_action( 'bp_setup_theme' );
}

/**
 * Fire the 'bp_after_setup_theme' action.
 *
 * Piggy-back action for BuddyPress-specific theme actions once the theme has
 * been set up and the theme's functions.php has loaded.
 *
 * Hooked to 'after_setup_theme' with a priority of 100. This allows plenty of
 * time for other themes to load their features, such as BuddyPress support,
 * before our theme compatibility layer kicks in.
 *
 * @since BuddyPress 1.6.0
 */
function bp_after_setup_theme() {

	/**
	 * Fires inside the 'bp_after_setup_theme' function.
	 *
	 * @since BuddyPress 1.7.0
	 */
	do_action( 'bp_after_setup_theme' );
}

/** Theme Compatibility Filter ************************************************/

/**
 * Fire the 'bp_request' filter, a piggy-back of WP's 'request'.
 *
 * @since BuddyPress 1.7.0
 *
 * @see WP::parse_request() for a description of parameters.
 *
 * @param array $query_vars See {@link WP::parse_request()}.
 * @return array $query_vars See {@link WP::parse_request()}.
 */
function bp_request( $query_vars = array() ) {

	/**
	 * Filters the query_vars for the current request.
	 *
	 * @since BuddyPress 1.7.0
	 *
	 * @param array $query_vars Array of query variables.
	 */
	return apply_filters( 'bp_request', $query_vars );
}

/**
 * Fire the 'bp_login_redirect' filter, a piggy-back of WP's 'login_redirect'.
 *
 * @since BuddyPress 1.7.0
 *
 * @param string $redirect_to     See 'login_redirect'.
 * @param string $redirect_to_raw See 'login_redirect'.
 * @param bool   $user            See 'login_redirect'.
 * @return string
 */
function bp_login_redirect( $redirect_to = '', $redirect_to_raw = '', $user = false ) {

	/**
	 * Filters the URL to redirect to after login.
	 *
	 * @since BuddyPress 1.7.0
	 *
	 * @param string           $redirect_to     The redirect destination URL.
	 * @param string           $redirect_to_raw The requested redirect destination URL passed as a parameter.
	 * @param WP_User|WP_Error $user            WP_User object if login was successful, WP_Error object otherwise.
	 */
	return apply_filters( 'bp_login_redirect', $redirect_to, $redirect_to_raw, $user );
}

/**
 * Fire 'bp_template_include', main filter used for theme compatibility and displaying custom BP theme files.
 *
 * Hooked to 'template_include'.
 *
 * @since BuddyPress 1.6.0
 *
 * @param string $template See 'template_include'.
 * @return string Template file to use.
 */
function bp_template_include( $template = '' ) {

	/**
	 * Filters the template to use with template_include.
	 *
	 * @since BuddyPress 1.6.0
	 *
	 * @param string $template The path of the template to include.
	 */
	return apply_filters( 'bp_template_include', $template );
}

/**
 * Fire the 'bp_generate_rewrite_rules' action, where BP generates its rewrite rules.
 *
 * @since BuddyPress 1.7.0
 *
 * @param WP_Rewrite $wp_rewrite See 'generate_rewrite_rules'.
 */
function bp_generate_rewrite_rules( $wp_rewrite ) {

	/**
	 * Fires inside the 'bp_generate_rewrite_rules' function.
	 *
	 * @since BuddyPress 1.7.0
	 *
	 * @param WP_Rewrite $wp_rewrite WP_Rewrite object. Passed by reference.
	 */
	do_action_ref_array( 'bp_generate_rewrite_rules', array( &$wp_rewrite ) );
}

/**
 * Fire the 'bp_allowed_themes' filter.
 *
 * Filter the allowed themes list for BuddyPress-specific themes.
 *
 * @since BuddyPress 1.7.0
 *
 * @param array $themes The path of the template to include.
 * @return array
 */
function bp_allowed_themes( $themes ) {

	/**
	 * Filters the allowed themes list for BuddyPress-specific themes.
	 *
	 * @since BuddyPress 1.7.0
	 *
	 * @param string $template The path of the template to include.
	 */
	return apply_filters( 'bp_allowed_themes', $themes );
}

/** Requests ******************************************************************/

/**
 * The main action used for handling theme-side POST requests.
 *
 * @since BuddyPress 1.9.0
 */
function bp_post_request() {

	// Bail if not a POST action.
	if ( ! bp_is_post_request() ) {
		return;
	}

	// Bail if no action.
	if ( empty( $_POST['action'] ) ) {
		return;
	}

	// Sanitize the POST action.
	$action = sanitize_key( $_POST['action'] );

	/**
	 * Fires at the end of the bp_post_request function.
	 *
	 * This dynamic action is probably the one you want to use. It narrows down
	 * the scope of the 'action' without needing to check it in your function.
	 *
	 * @since BuddyPress 1.9.0
	 */
	do_action( 'bp_post_request_' . $action );

	/**
	 * Fires at the end of the bp_post_request function.
	 *
	 * Use this static action if you don't mind checking the 'action' yourself.
	 *
	 * @since BuddyPress 1.9.0
	 *
	 * @param string $action The action being run.
	 */
	do_action( 'bp_post_request',   $action );
}

/**
 * The main action used for handling theme-side GET requests.
 *
 * @since BuddyPress 1.9.0
 */
function bp_get_request() {

	// Bail if not a POST action.
	if ( ! bp_is_get_request() ) {
		return;
	}

	// Bail if no action.
	if ( empty( $_GET['action'] ) ) {
		return;
	}

	// Sanitize the GET action.
	$action = sanitize_key( $_GET['action'] );

	/**
	 * Fires at the end of the bp_get_request function.
	 *
	 * This dynamic action is probably the one you want to use. It narrows down
	 * the scope of the 'action' without needing to check it in your function.
	 *
	 * @since BuddyPress 1.9.0
	 */
	do_action( 'bp_get_request_' . $action );

	/**
	 * Fires at the end of the bp_get_request function.
	 *
	 * Use this static action if you don't mind checking the 'action' yourself.
	 *
	 * @since BuddyPress 1.9.0
	 *
	 * @param string $action The action being run.
	 */
	do_action( 'bp_get_request',   $action );
}

class Bp_Page_For_Post_Type {

	protected $excludes = array();

	protected $original_slugs = array();

	protected static $instance;

	public static function get_instance() {
		if ( self::$instance === null ) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	public function __construct() {

		// admin init
		add_action( 'admin_init', array( $this, 'admin_init' ) );

		// update post type objects
		add_action( 'registered_post_type', array( $this, 'update_post_type' ), 11, 2 );

		// menu classes
		add_filter( 'wp_nav_menu_objects', array( $this, 'filter_wp_nav_menu_objects' ), 1, 2 );

		// customiser
		add_action( 'customize_register', array( $this, 'action_customize_register' ) );

		// edit.php view
		add_filter( 'display_post_states', array( $this, 'filter_display_post_states' ), 100, 2 );

		// post status changes / deletion
		add_action( 'transition_post_status', array( $this, 'action_transition_post_status' ), 10, 3 );
		add_action( 'deleted_post', array( $this, 'action_deleted_post' ), 10 );

	}

	public function admin_init() {

		// add settings fields

		$cpts = get_post_types( array(), 'objects' );

		add_settings_section( 'page_for_post_type', __( 'Pages for post type archives', 'pfpt' ), '__return_false', 'reading' );

		foreach ( $cpts as $cpt ) {

			if ( ! $cpt->has_archive ) {
				continue;
			}

			$id    = "page_for_{$cpt->name}";
			$value = get_option( $id );

			// flush rewrite rules when the option is changed
			register_setting( 'reading', $id, array( $this, 'validate_field' ) );

			add_settings_field( $id, $cpt->labels->name, array( $this, 'cpt_field' ), 'reading', 'page_for_post_type', array(
				'name'      => $id,
				'post_type' => $cpt,
				'value'     => $value
			) );

		}

	}

	public function cpt_field( $args ) {

		$value = intval( $args['value'] );

		$default = $args['post_type']->name;

		if ( isset( $this->original_slugs[ $args['post_type']->name ] ) ) {
			$default = $this->original_slugs[ $args['post_type']->name ];
		}

		wp_dropdown_pages( array(
			'name'             => esc_attr( $args['name'] ),
			'id'               => esc_attr( $args['name'] . '_dropdown' ),
			'selected'         => $value,
			'show_option_none' => sprintf( __( 'Default (/%s/)' ), $default ),
		) );

	}

	public function action_customize_register( WP_Customize_Manager $wp_customize ) {

		$cpts = get_post_types( array(), 'objects' );

		$wp_customize->add_section( 'page_for_post_type', array(
			'title' => __( 'Pages for post type archives', 'pfpt' ),
		) );

		foreach ( $cpts as $cpt ) {

			if ( ! $cpt->has_archive ) {
				continue;
			}

			$id = "page_for_{$cpt->name}";

			$wp_customize->add_setting( $id, array(
				'type'              => 'option',
				'capability'        => 'manage_options',
				'default'           => 0,
				'sanitize_callback' => array( $this, 'validate_field' ),
			) );
			$wp_customize->add_control( $id, array(
				'type'    => 'dropdown-pages',
				'section' => 'page_for_post_type', // Required, core or custom.
				'label'   => $cpt->labels->name,
			) );

		}

	}

	/**
	 * Add an indicator to show if a page is set as a post type archive.
	 *
	 * @param array   $post_states An array of post states to display after the post title.
	 * @param WP_Post $post        The current post object.
	 * @return array
	 */
	public function filter_display_post_states( $post_states, $post ) {
		$post_type = $post->post_type;
		$cpts      = get_post_types( array( 'public' => true ), 'objects' );

		if ( 'page' === $post_type ) {
			if ( in_array( $post->ID, $this->get_page_ids() ) ) {
				$cpt                                  = array_search( $post->ID, $this->get_page_ids() );
				$post_states["page_for_{$post_type}"] = sprintf( esc_html__( '%1$s archive', 'pfpt' ), $cpts[ $cpt ]->labels->name );
			}
		}

		return $post_states;
	}

	/**
	 * Flush rewrites and checks if the ID has been used already on this save
	 *
	 * @param $new_value
	 * @return int
	 */
	public function validate_field( $new_value ) {
		flush_rewrite_rules();
		if ( in_array( $new_value, $this->excludes ) ) {
			return 0;
		}
		$this->excludes[] = $new_value;
		return intval( $new_value );
	}


	/**
	 * Delete the setting for the corresponding post type if the page status
	 * is transitioned to anything other than published
	 *
	 * @param         $new_status
	 * @param         $old_status
	 * @param WP_Post $post
	 */
	public function action_transition_post_status( $new_status, $old_status, WP_Post $post ) {

		if ( 'publish' !== $new_status ) {
			$post_type = array_search( $post->ID, $this->get_page_ids() );
			if ( $post_type ) {
				delete_option( "page_for_{$post_type}" );
				flush_rewrite_rules();
			}
		}

	}

	/**
	 * Delete relevant option if a page for the archive is deleted
	 *
	 * @param int $post_id
	 */
	public function action_deleted_post( $post_id ) {

		$post_type = array_search( $post_id, $this->get_page_ids() );
		if ( $post_type ) {
			delete_option( "page_for_{$post_type}" );
			flush_rewrite_rules();
		}

	}

	/**
	 * Modifies the post type object to update the permastructure based
	 * on the page chosen
	 *
	 * @param $post_type
	 * @param $args
	 */
	public function update_post_type( $post_type, $args ) {
		global $wp_post_types, $wp_rewrite;

		$post_type_page = get_option( "page_for_{$post_type}" );

		if ( ! $post_type_page ) {
			return;
		}

		// make sure we don't create rules for an unpublished page preview URL
		if ( 'publish' !== get_post_status( $post_type_page ) ) {
			return;
		}

		// get the old slug
		$args->rewrite = (array) $args->rewrite;
		$old_slug      = isset( $args->rewrite['slug'] ) ? $args->rewrite['slug'] : $post_type;

		// store this for our options page
		$this->original_slugs[ $post_type ] = $old_slug;

		// get page slug
		$slug = get_permalink( $post_type_page );
		$slug = str_replace( home_url(), '', $slug );
		$slug = trim( $slug, '/' );

		$args->rewrite     = wp_parse_args( array( 'slug' => $slug ), $args->rewrite );
		$args->has_archive = $slug;

		// rebuild rewrite rules
		if ( is_admin() || '' != get_option( 'permalink_structure' ) ) {

			if ( $args->has_archive ) {
				$archive_slug = $args->has_archive === true ? $args->rewrite['slug'] : $args->has_archive;
				if ( $args->rewrite['with_front'] ) {
					$archive_slug = substr( $wp_rewrite->front, 1 ) . $archive_slug;
				} else {
					$archive_slug = $wp_rewrite->root . $archive_slug;
				}

				add_rewrite_rule( "{$archive_slug}/?$", "index.php?post_type=$post_type", 'top' );
				if ( $args->rewrite['feeds'] && $wp_rewrite->feeds ) {
					$feeds = '(' . trim( implode( '|', $wp_rewrite->feeds ) ) . ')';
					add_rewrite_rule( "{$archive_slug}/feed/$feeds/?$", "index.php?post_type=$post_type" . '&feed=$matches[1]', 'top' );
					add_rewrite_rule( "{$archive_slug}/$feeds/?$", "index.php?post_type=$post_type" . '&feed=$matches[1]', 'top' );
				}
				if ( $args->rewrite['pages'] ) {
					add_rewrite_rule( "{$archive_slug}/{$wp_rewrite->pagination_base}/([0-9]{1,})/?$", "index.php?post_type=$post_type" . '&paged=$matches[1]', 'top' );
				}
			}

			$permastruct_args         = $args->rewrite;
			$permastruct_args['feed'] = $permastruct_args['feeds'];

			// support plugins that enable 'permastruct' option
			if ( isset( $args->rewrite['permastruct'] ) ) {
				$permastruct = str_replace( $old_slug, $slug, $args->rewrite['permastruct'] );
			} else {
				$permastruct = "{$args->rewrite['slug']}/%$post_type%";
			}

			add_permastruct( $post_type, $permastruct, $permastruct_args );

		}

		// update the global
		$wp_post_types[ $post_type ] = $args;
	}

	/**
	 * Make sure menu items for our pages get the correct classes assigned
	 *
	 * @param $sorted_items
	 * @param $args
	 * @return array
	 */
	public function filter_wp_nav_menu_objects( $sorted_items, $args ) {
		global $wp_query;

		$queried_object = get_queried_object();

		if ( ! $queried_object ) {
			return $sorted_items;
		}

		$object_post_type = false;

		if ( is_singular() ) {
			$object_post_type = $queried_object->post_type;
		}

		if ( is_post_type_archive() ) {
			$object_post_type = $queried_object->name;
		}

		if ( is_archive() && is_string( $wp_query->get( 'post_type' ) ) ) {
			$query_post_type  = $wp_query->get( 'post_type' );
			$object_post_type = $query_post_type ?: 'post';
		}

		if ( ! $object_post_type ) {
			return $sorted_items;
		}

		// get page ID array
		$page_ids = $this->get_page_ids();

		if ( ! isset( $page_ids[ $object_post_type ] ) ) {
			return $sorted_items;
		}

		foreach ( $sorted_items as &$item ) {
			if ( $item->type === 'post_type' && $item->object === 'page' && intval( $item->object_id ) === intval( $page_ids[ $object_post_type ] ) ) {
				if ( is_singular( $object_post_type ) ) {
					$item->classes[]             = 'current-menu-item-ancestor';
					$item->current_item_ancestor = true;
					$sorted_items                = $this->add_ancestor_class( $item, $sorted_items );
				}
				if ( is_post_type_archive( $object_post_type ) ) {
					$item->classes[]    = 'current-menu-item';
					$item->current_item = true;
					$sorted_items       = $this->add_ancestor_class( $item, $sorted_items );
				}
				if ( is_archive() && $object_post_type === $wp_query->get( 'post_type' ) ) {
					$sorted_items = $this->add_ancestor_class( $item, $sorted_items );
				}
			}
		}

		return $sorted_items;
	}

	/**
	 * Protected methods
	 */

	/**
	 * Gets an array with post types as keys and corresponding page IDs as values
	 *
	 * @return array
	 */
	protected function get_page_ids() {

		$page_ids = array();

		foreach ( get_post_types( array(), 'objects' ) as $post_type ) {
			if ( ! $post_type->has_archive ) {
				continue;
			}

			if ( 'post' === $post_type->name ) {
				$page_id = get_option( 'page_for_posts' );
			} else {
				$page_id = get_option( "page_for_{$post_type->name}" );
			}

			if ( ! $page_id ) {
				continue;
			}

			$page_ids[ $post_type->name ] = $page_id;
		}

		return $page_ids;
	}

	/**
	 * Recursively set the ancestor class
	 *
	 * @param object $child
	 * @param array  $items
	 * @return array
	 */
	protected function add_ancestor_class( $child, $items ) {

		if ( ! intval( $child->menu_item_parent ) ) {
			return $items;
		}

		foreach ( $items as $item ) {
			if ( intval( $item->ID ) === intval( $child->menu_item_parent ) ) {
				$item->classes[]             = 'current-menu-item-ancestor';
				$item->current_item_ancestor = true;
				if ( intval( $item->menu_item_parent ) ) {
					$items = $this->add_ancestor_class( $item, $items );
				}
				break;
			}
		}

		return $items;
	}

}

if ( ! function_exists( 'get_page_for_post_type' ) ) {

	/**
	 * Get the page ID for the given or current post type
	 *
	 * @param bool|string $post_type
	 * @return bool|int
	 */
	function get_page_for_post_type( $post_type = false ) {
		if ( ! $post_type && is_post_type_archive() ) {
			$post_type = get_queried_object()->name;
		}
		if ( ! $post_type && is_singular() ) {
			$post_type = get_queried_object()->post_type;
		}
		if ( ! $post_type && in_the_loop() ) {
			$post_type = get_post_type();
		}
		if ( $post_type && in_array( $post_type, get_post_types() ) ) {
			return get_option( "page_for_{$post_type}", false );
		}
		return false;
	}

}

