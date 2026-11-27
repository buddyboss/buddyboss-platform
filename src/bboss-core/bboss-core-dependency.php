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

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Fire the 'bp_include' action, where plugins should include files.
 *
 * @since BuddyPress 1.2.5
 */
function bboss_include() {

	/**
	 * Fires inside the 'bp_include' function, where plugins should include files.
	 *
	 * @since BuddyPress 1.2.5
	 */
	do_action( 'bp_include' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_include' ) && ! function_exists( 'bp_include' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_include()}.
	 *
	 * @deprecated Use bboss_include() instead.
	 */
	function bp_include() {
		return bboss_include();
	}
}
// @bbwt-com-end compat-layer


/**
 * Fire the 'bp_late_include' action for loading conditional files.
 *
 * @since BuddyPress 3.0.0
 */
function bboss_late_include() {

	/**
	 * Fires the 'bp_late_include' action.
	 *
	 * Allow for conditional includes on certain pages.
	 *
	 * @since BuddyPress 3.0.0
	 */
	do_action( 'bp_late_include' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_late_include' ) && ! function_exists( 'bp_late_include' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_late_include()}.
	 *
	 * @deprecated Use bboss_late_include() instead.
	 */
	function bp_late_include() {
		return bboss_late_include();
	}
}
// @bbwt-com-end compat-layer


/**
 * Fire the 'bp_setup_components' action, where plugins should initialize components.
 *
 * @since BuddyPress 1.6.0
 */
function bboss_setup_components() {

	/**
	 * Fires inside the 'bp_setup_components' function, where plugins should initialize components.
	 *
	 * @since BuddyPress 1.6.0
	 */
	do_action( 'bp_setup_components' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_setup_components' ) && ! function_exists( 'bp_setup_components' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_setup_components()}.
	 *
	 * @deprecated Use bboss_setup_components() instead.
	 */
	function bp_setup_components() {
		return bboss_setup_components();
	}
}
// @bbwt-com-end compat-layer


/**
 * Fire the 'bp_setup_components' action, where plugins should initialize components.
 *
 * @since BuddyPress 1.6.0
 */
function bboss_setup_integrations() {

	/**
	 * Fires inside the 'bp_setup_integrations' function, where plugins should initialize components.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	do_action( 'bp_setup_integrations' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_setup_integrations' ) && ! function_exists( 'bp_setup_integrations' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_setup_integrations()}.
	 *
	 * @deprecated Use bboss_setup_integrations() instead.
	 */
	function bp_setup_integrations() {
		return bboss_setup_integrations();
	}
}
// @bbwt-com-end compat-layer


/**
 * Fire the 'bp_setup_canonical_stack' action, where plugins should set up their canonical URL.
 *
 * @since BuddyPress 2.1.0
 */
function bboss_setup_canonical_stack() {

	/**
	 * Fires inside the 'bp_setup_canonical_stack' function, where plugins should set up their canonical URL.
	 *
	 * @since BuddyPress 2.1.0
	 */
	do_action( 'bp_setup_canonical_stack' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_setup_canonical_stack' ) && ! function_exists( 'bp_setup_canonical_stack' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_setup_canonical_stack()}.
	 *
	 * @deprecated Use bboss_setup_canonical_stack() instead.
	 */
	function bp_setup_canonical_stack() {
		return bboss_setup_canonical_stack();
	}
}
// @bbwt-com-end compat-layer


/**
 * Fire the 'bp_register_taxonomies' action, where plugins should register taxonomies.
 *
 * @since BuddyPress 2.2.0
 */
function bboss_register_taxonomies() {

	/**
	 * Fires inside the 'bp_register_taxonomies' function, where plugins should register taxonomies.
	 *
	 * @since BuddyPress 2.2.0
	 */
	do_action( 'bp_register_taxonomies' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_register_taxonomies' ) && ! function_exists( 'bp_register_taxonomies' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_register_taxonomies()}.
	 *
	 * @deprecated Use bboss_register_taxonomies() instead.
	 */
	function bp_register_taxonomies() {
		return bboss_register_taxonomies();
	}
}
// @bbwt-com-end compat-layer


/**
 * Fire the 'bp_register_post_types' action, where plugins should register post types.
 *
 * @since BuddyPress 2.5.0
 */
function bboss_register_post_types() {

	/**
	 * Fires inside the 'bp_register_post_types' function, where plugins should register post types.
	 *
	 * @since BuddyPress 2.5.0
	 */
	do_action( 'bp_register_post_types' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_register_post_types' ) && ! function_exists( 'bp_register_post_types' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_register_post_types()}.
	 *
	 * @deprecated Use bboss_register_post_types() instead.
	 */
	function bp_register_post_types() {
		return bboss_register_post_types();
	}
}
// @bbwt-com-end compat-layer


/**
 * Fire the 'bp_setup_globals' action, where plugins should initialize global settings.
 *
 * @since BuddyPress 1.2.0
 */
function bboss_setup_globals() {

	/**
	 * Fires inside the 'bp_setup_globals' function, where plugins should initialize global settings.
	 *
	 * @since BuddyPress 1.2.0
	 */
	do_action( 'bp_setup_globals' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_setup_globals' ) && ! function_exists( 'bp_setup_globals' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_setup_globals()}.
	 *
	 * @deprecated Use bboss_setup_globals() instead.
	 */
	function bp_setup_globals() {
		return bboss_setup_globals();
	}
}
// @bbwt-com-end compat-layer


/**
 * Fire the 'bp_setup_nav' action, where plugins should register their navigation items.
 *
 * @since BuddyPress 1.2.0
 */
function bboss_setup_nav() {

	/**
	 * Fires inside the 'bp_setup_nav' function, where plugins should register their navigation items.
	 *
	 * @since BuddyPress 1.2.0
	 */
	do_action( 'bp_setup_nav' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_setup_nav' ) && ! function_exists( 'bp_setup_nav' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_setup_nav()}.
	 *
	 * @deprecated Use bboss_setup_nav() instead.
	 */
	function bp_setup_nav() {
		return bboss_setup_nav();
	}
}
// @bbwt-com-end compat-layer


/**
 * Fire the 'bp_setup_admin_bar' action, where plugins should add items to the WP admin bar.
 *
 * @since BuddyPress 1.5.0
 */
function bboss_setup_admin_bar() {
	if ( bboss_use_wp_admin_bar() ) {

		/**
		 * Fires inside the 'bp_setup_admin_bar' function, where plugins should add items to the WP admin bar.
		 *
		 * This hook will only fire if bboss_use_wp_admin_bar() returns true.
		 *
		 * @since BuddyPress 1.5.0
		 */
		do_action( 'bp_setup_admin_bar', array() );
	}
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_setup_admin_bar' ) && ! function_exists( 'bp_setup_admin_bar' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_setup_admin_bar()}.
	 *
	 * @deprecated Use bboss_setup_admin_bar() instead.
	 */
	function bp_setup_admin_bar() {
		return bboss_setup_admin_bar();
	}
}
// @bbwt-com-end compat-layer


/**
 * Fire the 'bp_setup_title' action, where plugins should modify the page title.
 *
 * @since BuddyPress 1.5.0
 */
function bboss_setup_title() {

	/**
	 * Fires inside the 'bp_setup_title' function, where plugins should modify the page title.
	 *
	 * @since BuddyPress 1.5.0
	 */
	do_action( 'bp_setup_title' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_setup_title' ) && ! function_exists( 'bp_setup_title' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_setup_title()}.
	 *
	 * @deprecated Use bboss_setup_title() instead.
	 */
	function bp_setup_title() {
		return bboss_setup_title();
	}
}
// @bbwt-com-end compat-layer


/**
 * Fire the 'bp_register_widgets' action, where plugins should register widgets.
 *
 * @since BuddyPress 1.2.0
 */
function bboss_setup_widgets() {

	/**
	 * Fires inside the 'bp_register_widgets' function, where plugins should register widgets.
	 *
	 * @since BuddyPress 1.2.0
	 */
	do_action( 'bp_register_widgets' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_setup_widgets' ) && ! function_exists( 'bp_setup_widgets' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_setup_widgets()}.
	 *
	 * @deprecated Use bboss_setup_widgets() instead.
	 */
	function bp_setup_widgets() {
		return bboss_setup_widgets();
	}
}
// @bbwt-com-end compat-layer


/**
 * Fire the 'bp_register_member_types' action, where plugins should register profile types.
 *
 * @since BuddyPress 2.3.0
 */
function bboss_register_member_types() {

	/**
	 * Fires inside bboss_register_member_types(), so plugins can register profile types.
	 *
	 * @since BuddyPress 2.3.0
	 */
	do_action( 'bp_register_member_types' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_register_member_types' ) && ! function_exists( 'bp_register_member_types' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_register_member_types()}.
	 *
	 * @deprecated Use bboss_register_member_types() instead.
	 */
	function bp_register_member_types() {
		return bboss_register_member_types();
	}
}
// @bbwt-com-end compat-layer


/**
 * Fire the 'bp_setup_cache_groups' action, where cache groups are registered.
 *
 * @since BuddyPress 2.2.0
 */
function bboss_setup_cache_groups() {

	/**
	 * Fires inside the 'bp_setup_cache_groups' function, where cache groups are registered.
	 *
	 * @since BuddyPress 2.2.0
	 */
	do_action( 'bp_setup_cache_groups' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_setup_cache_groups' ) && ! function_exists( 'bp_setup_cache_groups' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_setup_cache_groups()}.
	 *
	 * @deprecated Use bboss_setup_cache_groups() instead.
	 */
	function bp_setup_cache_groups() {
		return bboss_setup_cache_groups();
	}
}
// @bbwt-com-end compat-layer


/**
 * Set up the currently logged-in user.
 *
 * @since BuddyPress 1.7.0
 *
 * @link https://buddypress.trac.wordpress.org/ticket/6046
 * @link https://core.trac.wordpress.org/ticket/24169
 */
function bboss_setup_current_user() {

	/**
	 * Fires to set up the current user setup process.
	 *
	 * @since BuddyPress 1.7.0
	 */
	do_action( 'bp_setup_current_user' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_setup_current_user' ) && ! function_exists( 'bp_setup_current_user' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_setup_current_user()}.
	 *
	 * @deprecated Use bboss_setup_current_user() instead.
	 */
	function bp_setup_current_user() {
		return bboss_setup_current_user();
	}
}
// @bbwt-com-end compat-layer


/**
 * Fire the 'bp_init' action, BuddyPress's main initialization hook.
 *
 * @since BuddyPress 1.2.5
 */
function bboss_init() {

	/**
	 * Fires inside the 'bp_init' function, BuddyPress' main initialization hook.
	 *
	 * @since BuddyPress 1.2.0
	 */
	do_action( 'bp_init' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_init' ) && ! function_exists( 'bp_init' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_init()}.
	 *
	 * @deprecated Use bboss_init() instead.
	 */
	function bp_init() {
		return bboss_init();
	}
}
// @bbwt-com-end compat-layer


/**
 * Fire the 'bp_rest_api_init' action, where BuddyPress registers REST API endpoints.
 *
 * @since BuddyPress 2.6.0
 */
function bboss_rest_api_init() {

	/**
	 * Fires the 'bp_rest_api_init' function, where BuddyPress registers REST API endpoints.
	 *
	 * @since BuddyPress 2.6.0
	 */
	do_action( 'bp_rest_api_init' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_rest_api_init' ) && ! function_exists( 'bp_rest_api_init' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_rest_api_init()}.
	 *
	 * @deprecated Use bboss_rest_api_init() instead.
	 */
	function bp_rest_api_init() {
		return bboss_rest_api_init();
	}
}
// @bbwt-com-end compat-layer


/**
 * BB Blocks Init hook.
 *
 * @since BuddyBoss 2.9.00
 */
function bboss_blocks_init() {

	/**
	 * Hook here to register your BuddyBoss blocks.
	 *
	 * @since BuddyBoss 2.9.00
	 */
	do_action( 'bb_blocks_init' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_blocks_init' ) && ! function_exists( 'bb_blocks_init' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_blocks_init()}.
	 *
	 * @deprecated Use bboss_blocks_init() instead.
	 */
	function bb_blocks_init() {
		return bboss_blocks_init();
	}
}
// @bbwt-com-end compat-layer


/**
 * Fire the 'bp_customize_register' action when the Customizer has loaded,
 * allowing scripts and styles to be initialized.
 *
 * @since BuddyPress 2.5.0
 *
 * @param WP_Customize_Manager $customizer Customizer instance.
 */
function bboss_customize_register( WP_Customize_Manager $customizer ) {

	/**
	 * Fires once the Customizer has loaded, allow scripts and styles to be initialized.
	 *
	 * @since BuddyPress 2.5.0
	 *
	 * @param WP_Customize_Manager $customizer Customizer instance.
	 */
	do_action( 'bp_customize_register', $customizer );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_customize_register' ) && ! function_exists( 'bp_customize_register' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_customize_register()}.
	 *
	 * @deprecated Use bboss_customize_register() instead.
	 */
	function bp_customize_register( WP_Customize_Manager $customizer ) {
		return bboss_customize_register( $customizer );
	}
}
// @bbwt-com-end compat-layer


/**
 * Fire the 'bp_loaded' action, which fires after BP's core plugin files have been loaded.
 *
 * Attached to 'plugins_loaded'.
 *
 * @since BuddyPress 1.2.0
 */
function bboss_loaded() {

	/**
	 * Fires inside the 'bp_loaded' function, which fires after BP's core plugin files have been loaded.
	 *
	 * @since BuddyPress 1.2.5
	 */
	do_action( 'bp_loaded' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_loaded' ) && ! function_exists( 'bp_loaded' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_loaded()}.
	 *
	 * @deprecated Use bboss_loaded() instead.
	 */
	function bp_loaded() {
		return bboss_loaded();
	}
}
// @bbwt-com-end compat-layer


/**
 * Fire the 'bp_ready' action, which runs after BP is set up and the page is about to render.
 *
 * Attached to 'wp'.
 *
 * @since BuddyPress 1.6.0
 */
function bboss_ready() {

	/**
	 * Fires inside the 'bp_ready' function, which runs after BP is set up and the page is about to render.
	 *
	 * @since BuddyPress 1.6.0
	 */
	do_action( 'bp_ready' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_ready' ) && ! function_exists( 'bp_ready' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_ready()}.
	 *
	 * @deprecated Use bboss_ready() instead.
	 */
	function bp_ready() {
		return bboss_ready();
	}
}
// @bbwt-com-end compat-layer


/**
 * Fire the 'bp_actions' action, which runs just before rendering.
 *
 * Attach potential template actions, such as catching form requests or routing
 * custom URLs.
 *
 * @since BuddyPress 1.5.0
 */
function bboss_actions() {

	/**
	 * Fires inside the 'bp_actions' function, which runs just before rendering.
	 *
	 * @since BuddyPress 1.5.0
	 */
	do_action( 'bp_actions' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_actions' ) && ! function_exists( 'bp_actions' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_actions()}.
	 *
	 * @deprecated Use bboss_actions() instead.
	 */
	function bp_actions() {
		return bboss_actions();
	}
}
// @bbwt-com-end compat-layer


/**
 * Fire the 'bp_screens' action, which runs just before rendering.
 *
 * Runs just after 'bp_actions'. Use this hook to attach your template
 * loaders.
 *
 * @since BuddyPress 1.5.0
 */
function bboss_screens() {

	/**
	 * Fires inside the 'bp_screens' function, which runs just before rendering.
	 *
	 * Runs just after 'bp_actions'. Use this hook to attach your template loaders.
	 *
	 * @since BuddyPress 1.5.0
	 */
	do_action( 'bp_screens' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_screens' ) && ! function_exists( 'bp_screens' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_screens()}.
	 *
	 * @deprecated Use bboss_screens() instead.
	 */
	function bp_screens() {
		return bboss_screens();
	}
}
// @bbwt-com-end compat-layer


/**
 * Fire 'bp_widgets_init', which runs after widgets have been set up.
 *
 * Hooked to 'widgets_init'.
 *
 * @since BuddyPress 1.6.0
 */
function bboss_widgets_init() {

	/**
	 * Fires inside the 'bp_widgets_init' function, which runs after widgets have been set up.
	 *
	 * Hooked to 'widgets_init'.
	 *
	 * @since BuddyPress 1.6.0
	 */
	do_action( 'bp_widgets_init' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_widgets_init' ) && ! function_exists( 'bp_widgets_init' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_widgets_init()}.
	 *
	 * @deprecated Use bboss_widgets_init() instead.
	 */
	function bp_widgets_init() {
		return bboss_widgets_init();
	}
}
// @bbwt-com-end compat-layer


/**
 * Fire 'bp_head', which is used to hook scripts and styles in the <head>.
 *
 * Hooked to 'wp_head'.
 *
 * @since BuddyPress 1.6.0
 */
function bboss_head() {

	/**
	 * Fires inside the 'bp_head' function, which runs on 'wp_head'.
	 *
	 * @since BuddyPress 1.6.0
	 */
	do_action( 'bp_head' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_head' ) && ! function_exists( 'bp_head' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_head()}.
	 *
	 * @deprecated Use bboss_head() instead.
	 */
	function bp_head() {
		return bboss_head();
	}
}
// @bbwt-com-end compat-layer


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
function bboss_template_redirect() {

	/**
	 * Fires inside the 'bp_template_redirect' function.
	 *
	 * @since BuddyPress 1.6.0
	 */
	do_action( 'bp_template_redirect' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_template_redirect' ) && ! function_exists( 'bp_template_redirect' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_template_redirect()}.
	 *
	 * @deprecated Use bboss_template_redirect() instead.
	 */
	function bp_template_redirect() {
		return bboss_template_redirect();
	}
}
// @bbwt-com-end compat-layer


/** Theme Helpers *************************************************************/

/**
 * Fire the 'bp_register_theme_packages' action.
 *
 * The main action used registering theme packages.
 *
 * @since BuddyPress 1.7.0
 */
function bboss_register_theme_packages() {

	/**
	 * Fires inside the 'bp_register_theme_packages' function.
	 *
	 * @since BuddyPress 1.7.0
	 */
	do_action( 'bp_register_theme_packages' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_register_theme_packages' ) && ! function_exists( 'bp_register_theme_packages' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_register_theme_packages()}.
	 *
	 * @deprecated Use bboss_register_theme_packages() instead.
	 */
	function bp_register_theme_packages() {
		return bboss_register_theme_packages();
	}
}
// @bbwt-com-end compat-layer


/**
 * Fire the 'bp_enqueue_scripts' action, where BP enqueues its CSS and JS.
 *
 * @since BuddyPress 1.6.0
 */
function bboss_enqueue_scripts() {

	/**
	 * Fires inside the 'bp_enqueue_scripts' function, where BP enqueues its CSS and JS.
	 *
	 * @since BuddyPress 1.6.0
	 */
	do_action( 'bp_enqueue_scripts' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_enqueue_scripts' ) && ! function_exists( 'bp_enqueue_scripts' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_enqueue_scripts()}.
	 *
	 * @deprecated Use bboss_enqueue_scripts() instead.
	 */
	function bp_enqueue_scripts() {
		return bboss_enqueue_scripts();
	}
}
// @bbwt-com-end compat-layer


/**
 * Fires the 'bp_enqueue_embed_scripts' action in the <head> for BP oEmbeds.
 *
 * @since BuddyPress 2.6.0
 */
function bboss_enqueue_embed_scripts() {
	if ( ! bboss_is_buddypress() ) {
		return;
	}

	/**
	 * Enqueue CSS and JS files for BuddyPress embeds.
	 *
	 * @since BuddyPress 2.6.0
	 */
	do_action( 'bp_enqueue_embed_scripts' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_enqueue_embed_scripts' ) && ! function_exists( 'bp_enqueue_embed_scripts' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_enqueue_embed_scripts()}.
	 *
	 * @deprecated Use bboss_enqueue_embed_scripts() instead.
	 */
	function bp_enqueue_embed_scripts() {
		return bboss_enqueue_embed_scripts();
	}
}
// @bbwt-com-end compat-layer


/**
 * Fire the 'bp_add_rewrite_tag' action, where BP adds its custom rewrite tags.
 *
 * @since BuddyPress 1.8.0
 */
function bboss_add_rewrite_tags() {

	/**
	 * Fires inside the 'bp_add_rewrite_tags' function, where BP adds its custom rewrite tags.
	 *
	 * @since BuddyPress 1.8.0
	 */
	do_action( 'bp_add_rewrite_tags' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_add_rewrite_tags' ) && ! function_exists( 'bp_add_rewrite_tags' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_add_rewrite_tags()}.
	 *
	 * @deprecated Use bboss_add_rewrite_tags() instead.
	 */
	function bp_add_rewrite_tags() {
		return bboss_add_rewrite_tags();
	}
}
// @bbwt-com-end compat-layer


/**
 * Fire the 'bp_add_rewrite_rules' action, where BP adds its custom rewrite rules.
 *
 * @since BuddyPress 1.9.0
 */
function bboss_add_rewrite_rules() {

	/**
	 * Fires inside the 'bp_add_rewrite_rules' function, where BP adds its custom rewrite rules.
	 *
	 * @since BuddyPress 1.9.0
	 */
	do_action( 'bp_add_rewrite_rules' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_add_rewrite_rules' ) && ! function_exists( 'bp_add_rewrite_rules' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_add_rewrite_rules()}.
	 *
	 * @deprecated Use bboss_add_rewrite_rules() instead.
	 */
	function bp_add_rewrite_rules() {
		return bboss_add_rewrite_rules();
	}
}
// @bbwt-com-end compat-layer


/**
 * Fire the 'bp_add_permastructs' action, where BP adds its BP-specific permalink structure.
 *
 * @since BuddyPress 1.9.0
 */
function bboss_add_permastructs() {

	/**
	 * Fires inside the 'bp_add_permastructs' function, where BP adds its BP-specific permalink structure.
	 *
	 * @since BuddyPress 1.9.0
	 */
	do_action( 'bp_add_permastructs' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_add_permastructs' ) && ! function_exists( 'bp_add_permastructs' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_add_permastructs()}.
	 *
	 * @deprecated Use bboss_add_permastructs() instead.
	 */
	function bp_add_permastructs() {
		return bboss_add_permastructs();
	}
}
// @bbwt-com-end compat-layer


/**
 * Fire the 'bp_init_background_updater' action, where BP updates data.
 *
 * @since BuddyBoss 1.0.0
 */
function bboss_init_background_updater() {
	global $bp_background_updater;

	if ( ! class_exists( 'BBoss_Background_Updater' ) ) {
		include_once bboss()->plugin_dir . 'bboss-core/classes/class-bboss-background-updater.php';
	}
	$bp_background_updater = new BBoss_Background_Updater();

	/**
	 * Fires inside the 'bp_init_background_updater' function, where BP updates data.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	do_action( 'bp_init_background_updater' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_init_background_updater' ) && ! function_exists( 'bp_init_background_updater' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_init_background_updater()}.
	 *
	 * @deprecated Use bboss_init_background_updater() instead.
	 */
	function bp_init_background_updater() {
		return bboss_init_background_updater();
	}
}
// @bbwt-com-end compat-layer


/**
 * Fire the 'bb_init_background_updater' action, where BB updates data.
 *
 * @since BuddyBoss 2.4.20
 */
function bboss_bb_init_background_updater() {
	global $bb_background_updater;

	if ( ! class_exists( 'BBoss_BB_Background_Updater' ) ) {
		include_once bboss()->plugin_dir . 'bboss-core/classes/class-bboss-bb-background-updater.php';
	}

	$bb_background_updater = new BBoss_BB_Background_Updater();

	/**
	 * Fires inside the 'bb_init_background_updater' function, where BB updates data.
	 *
	 * @since BuddyBoss 2.4.20
	 */
	do_action( 'bb_init_background_updater' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_bb_init_background_updater' ) && ! function_exists( 'bb_init_background_updater' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_bb_init_background_updater()}.
	 *
	 * @deprecated Use bboss_bb_init_background_updater() instead.
	 */
	function bb_init_background_updater() {
		return bboss_bb_init_background_updater();
	}
}
// @bbwt-com-end compat-layer


/**
 * Fire the 'bp_setup_theme' action.
 *
 * The main purpose of 'bp_setup_theme' is give themes a place to load their
 * BuddyPress-specific functionality.
 *
 * @since BuddyPress 1.6.0
 */
function bboss_setup_theme() {

	/**
	 * Fires inside the 'bp_setup_theme' function.
	 *
	 * @since BuddyPress 1.6.0
	 */
	do_action( 'bp_setup_theme' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_setup_theme' ) && ! function_exists( 'bp_setup_theme' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_setup_theme()}.
	 *
	 * @deprecated Use bboss_setup_theme() instead.
	 */
	function bp_setup_theme() {
		return bboss_setup_theme();
	}
}
// @bbwt-com-end compat-layer


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
function bboss_after_setup_theme() {

	/**
	 * Fires inside the 'bp_after_setup_theme' function.
	 *
	 * @since BuddyPress 1.7.0
	 */
	do_action( 'bp_after_setup_theme' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_after_setup_theme' ) && ! function_exists( 'bp_after_setup_theme' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_after_setup_theme()}.
	 *
	 * @deprecated Use bboss_after_setup_theme() instead.
	 */
	function bp_after_setup_theme() {
		return bboss_after_setup_theme();
	}
}
// @bbwt-com-end compat-layer


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
function bboss_request( $query_vars = array() ) {

	/**
	 * Filters the query_vars for the current request.
	 *
	 * @since BuddyPress 1.7.0
	 *
	 * @param array $query_vars Array of query variables.
	 */
	return apply_filters( 'bp_request', $query_vars );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_request' ) && ! function_exists( 'bp_request' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_request()}.
	 *
	 * @deprecated Use bboss_request() instead.
	 */
	function bp_request( $query_vars = array() ) {
		return bboss_request( $query_vars );
	}
}
// @bbwt-com-end compat-layer


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
function bboss_login_redirect( $redirect_to = '', $redirect_to_raw = '', $user = false ) {

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
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_login_redirect' ) && ! function_exists( 'bp_login_redirect' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_login_redirect()}.
	 *
	 * @deprecated Use bboss_login_redirect() instead.
	 */
	function bp_login_redirect( $redirect_to = '', $redirect_to_raw = '', $user = false ) {
		return bboss_login_redirect( $redirect_to, $redirect_to_raw, $user );
	}
}
// @bbwt-com-end compat-layer


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
function bboss_template_include( $template = '' ) {

	/**
	 * Filters the template to use with template_include.
	 *
	 * @since BuddyPress 1.6.0
	 *
	 * @param string $template The path of the template to include.
	 */
	return apply_filters( 'bp_template_include', $template );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_template_include' ) && ! function_exists( 'bp_template_include' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_template_include()}.
	 *
	 * @deprecated Use bboss_template_include() instead.
	 */
	function bp_template_include( $template = '' ) {
		return bboss_template_include( $template );
	}
}
// @bbwt-com-end compat-layer


/**
 * Fire the 'bp_generate_rewrite_rules' action, where BP generates its rewrite rules.
 *
 * @since BuddyPress 1.7.0
 *
 * @param WP_Rewrite $wp_rewrite See 'generate_rewrite_rules'.
 */
function bboss_generate_rewrite_rules( $wp_rewrite ) {

	/**
	 * Fires inside the 'bp_generate_rewrite_rules' function.
	 *
	 * @since BuddyPress 1.7.0
	 *
	 * @param WP_Rewrite $wp_rewrite WP_Rewrite object. Passed by reference.
	 */
	do_action_ref_array( 'bp_generate_rewrite_rules', array( &$wp_rewrite ) );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_generate_rewrite_rules' ) && ! function_exists( 'bp_generate_rewrite_rules' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_generate_rewrite_rules()}.
	 *
	 * @deprecated Use bboss_generate_rewrite_rules() instead.
	 */
	function bp_generate_rewrite_rules( $wp_rewrite ) {
		return bboss_generate_rewrite_rules( $wp_rewrite );
	}
}
// @bbwt-com-end compat-layer


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
function bboss_allowed_themes( $themes ) {

	/**
	 * Filters the allowed themes list for BuddyPress-specific themes.
	 *
	 * @since BuddyPress 1.7.0
	 *
	 * @param string $template The path of the template to include.
	 */
	return apply_filters( 'bp_allowed_themes', $themes );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_allowed_themes' ) && ! function_exists( 'bp_allowed_themes' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_allowed_themes()}.
	 *
	 * @deprecated Use bboss_allowed_themes() instead.
	 */
	function bp_allowed_themes( $themes ) {
		return bboss_allowed_themes( $themes );
	}
}
// @bbwt-com-end compat-layer


/** Requests ******************************************************************/

/**
 * The main action used for handling theme-side POST requests.
 *
 * @since BuddyPress 1.9.0
 */
function bboss_post_request() {

	// Bail if not a POST action.
	if ( ! bboss_is_post_request() ) {
		return;
	}

	// Bail if no action.
	if ( empty( $_POST['action'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- only dispatches to the bp_post_request_{action} hooks; nothing is changed here.
		return;
	}

	// Sanitize the POST action.
	$action = sanitize_key( $_POST['action'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- only dispatches to the bp_post_request_{action} hooks; nothing is changed here.

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
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_post_request' ) && ! function_exists( 'bp_post_request' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_post_request()}.
	 *
	 * @deprecated Use bboss_post_request() instead.
	 */
	function bp_post_request() {
		return bboss_post_request();
	}
}
// @bbwt-com-end compat-layer


/**
 * The main action used for handling theme-side GET requests.
 *
 * @since BuddyPress 1.9.0
 */
function bboss_get_request() {

	// Bail if not a POST action.
	if ( ! bboss_is_get_request() ) {
		return;
	}

	// Bail if no action.
	if ( empty( $_GET['action'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- only dispatches to the bp_get_request_{action} hooks; nothing is changed here.
		return;
	}

	// Sanitize the GET action.
	$action = sanitize_key( $_GET['action'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- only dispatches to the bp_get_request_{action} hooks; nothing is changed here.

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
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_get_request' ) && ! function_exists( 'bp_get_request' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_get_request()}.
	 *
	 * @deprecated Use bboss_get_request() instead.
	 */
	function bp_get_request() {
		return bboss_get_request();
	}
}
// @bbwt-com-end compat-layer


/**
 * Fire the 'bb_init_email_background_updater' action, where BP email updates data.
 *
 * @since BuddyBoss 1.8.1
 */
function bboss_init_email_background_updater() {
	global $bb_email_background_updater;

	if ( ! class_exists( 'BBoss_Background_Updater' ) ) {
		include_once bboss()->plugin_dir . 'bboss-core/classes/class-bboss-background-updater.php';
	}
	$bb_email_background_updater = new BBoss_Email_Background_Updater();

	/**
	 * Fires inside the 'bb_init_email_background_updater' function, where BP email updates data.
	 *
	 * @since BuddyBoss 1.8.1
	 */
	do_action( 'bb_init_email_background_updater' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_init_email_background_updater' ) && ! function_exists( 'bb_init_email_background_updater' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_init_email_background_updater()}.
	 *
	 * @deprecated Use bboss_init_email_background_updater() instead.
	 */
	function bb_init_email_background_updater() {
		return bboss_init_email_background_updater();
	}
}
// @bbwt-com-end compat-layer


/**
 * Fire the 'bb_init_notifications_background_updater' action, where BP Notification updates data.
 *
 * @since BuddyBoss 1.9.0
 */
function bboss_init_notifications_background_updater() {

	if ( ! bboss_is_active( 'notifications' ) ) {
		return;
	}
	global $bb_notifications_background_updater;

	if ( ! class_exists( 'BBoss_Background_Updater' ) ) {
		include_once bboss()->plugin_dir . 'bboss-core/classes/class-bboss-background-updater.php';
	}
	$bb_notifications_background_updater = new BBoss_Notifications_Background_Updater();

	/**
	 * Fires inside the 'bb_init_notifications_background_updater' function, where BP Notification updates data.
	 *
	 * @since BuddyBoss 1.9.0
	 */
	do_action( 'bb_init_notifications_background_updater' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_init_notifications_background_updater' ) && ! function_exists( 'bb_init_notifications_background_updater' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_init_notifications_background_updater()}.
	 *
	 * @deprecated Use bboss_init_notifications_background_updater() instead.
	 */
	function bb_init_notifications_background_updater() {
		return bboss_init_notifications_background_updater();
	}
}
// @bbwt-com-end compat-layer

