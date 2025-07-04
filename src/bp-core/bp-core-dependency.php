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
 * BB Blocks Init hook.
 *
 * @since BuddyBoss 2.9.00
 */
function bb_blocks_init() {

	/**
	 * Hook here to register your BuddyBoss blocks.
	 *
	 * @since BuddyBoss 2.9.00
	 */
	do_action( 'bb_blocks_init' );
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
	global $bp_background_updater;

	if ( ! class_exists( 'BP_Background_Updater' ) ) {
		include_once buddypress()->plugin_dir . 'bp-core/classes/class-bp-background-updater.php';
	}
	$bp_background_updater = new BP_Background_Updater();

	/**
	 * Fires inside the 'bp_init_background_updater' function, where BP updates data.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	do_action( 'bp_init_background_updater' );
}

/**
 * Fire the 'bb_init_background_updater' action, where BB updates data.
 *
 * @since BuddyBoss 2.4.20
 */
function bb_init_background_updater() {
	global $bb_background_updater;

	if ( ! class_exists( 'BB_Background_Updater' ) ) {
		include_once buddypress()->plugin_dir . 'bp-core/classes/class-bb-background-updater.php';
	}

	$bb_background_updater = new BB_Background_Updater();

	/**
	 * Fires inside the 'bb_init_background_updater' function, where BB updates data.
	 *
	 * @since BuddyBoss 2.4.20
	 */
	do_action( 'bb_init_background_updater' );
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
	do_action( 'bp_post_request', $action );
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
	do_action( 'bp_get_request', $action );
}

/**
 * Fire the 'bb_init_email_background_updater' action, where BP email updates data.
 *
 * @since BuddyBoss 1.8.1
 */
function bb_init_email_background_updater() {
	global $bb_email_background_updater;

	if ( ! class_exists( 'BP_Background_Updater' ) ) {
		include_once buddypress()->plugin_dir . 'bp-core/classes/class-bp-background-updater.php';
	}
	$bb_email_background_updater = new BP_Email_Background_Updater();

	/**
	 * Fires inside the 'bb_init_email_background_updater' function, where BP email updates data.
	 *
	 * @since BuddyBoss 1.8.1
	 */
	do_action( 'bb_init_email_background_updater' );
}

/**
 * Fire the 'bb_init_notifications_background_updater' action, where BP Notification updates data.
 *
 * @since BuddyBoss 1.9.0
 */
function bb_init_notifications_background_updater() {

	if ( ! bp_is_active( 'notifications' ) ) {
		return;
	}
	global $bb_notifications_background_updater;

	if ( ! class_exists( 'BP_Background_Updater' ) ) {
		include_once buddypress()->plugin_dir . 'bp-core/classes/class-bp-background-updater.php';
	}
	$bb_notifications_background_updater = new BP_Notifications_Background_Updater();

	/**
	 * Fires inside the 'bb_init_notifications_background_updater' function, where BP Notification updates data.
	 *
	 * @since BuddyBoss 1.9.0
	 */
	do_action( 'bb_init_notifications_background_updater' );
}
