<?php

/**
 * Plugin Dependency
 *
 * The purpose of the following hooks is to mimic the behavior of something
 * called 'plugin dependency' which enables a plugin to have plugins of their
 * own in a safe and reliable way.
 *
 * We do this in Forums by mirroring existing WordPress hooks in many places
 * allowing dependant plugins to hook into the Forums specific ones, thus
 * guaranteeing proper code execution only when Forums is active.
 *
 * The following functions are wrappers for hookss, allowing them to be
 * manually called and/or piggy-backed on top of other hooks if needed.
 *
 * @todo use anonymous functions when PHP minimun requirement allows (5.3)
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/** Activation Actions ********************************************************/

/**
 * Runs on Forums activation
 *
 * @since bbPress (r2509)
 * @uses register_uninstall_hook() To register our own uninstall hook
 * @uses do_action() Calls 'bbp_activation' hook
 */
function bboss_bbp_activation() {
	do_action( 'bbp_activation' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_bbp_activation' ) && ! function_exists( 'bbp_activation' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_bbp_activation()}.
	 *
	 * @deprecated Use bboss_bbp_activation() instead.
	 */
	function bbp_activation() {
		return bboss_bbp_activation();
	}
}
// @bbwt-com-end compat-layer


/**
 * Runs on Forums deactivation
 *
 * @since bbPress (r2509)
 * @uses do_action() Calls 'bbp_deactivation' hook
 */
function bboss_bbp_deactivation() {
	do_action( 'bbp_deactivation' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_bbp_deactivation' ) && ! function_exists( 'bbp_deactivation' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_bbp_deactivation()}.
	 *
	 * @deprecated Use bboss_bbp_deactivation() instead.
	 */
	function bbp_deactivation() {
		return bboss_bbp_deactivation();
	}
}
// @bbwt-com-end compat-layer


/**
 * Runs when uninstalling Forums
 *
 * @since bbPress (r2509)
 * @uses do_action() Calls 'bbp_uninstall' hook
 */
function bboss_bbp_uninstall() {
	do_action( 'bbp_uninstall' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_bbp_uninstall' ) && ! function_exists( 'bbp_uninstall' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_bbp_uninstall()}.
	 *
	 * @deprecated Use bboss_bbp_uninstall() instead.
	 */
	function bbp_uninstall() {
		return bboss_bbp_uninstall();
	}
}
// @bbwt-com-end compat-layer


/** Main Actions **************************************************************/

/**
 * Main action responsible for constants, globals, and includes
 *
 * @since bbPress (r2599)
 * @uses do_action() Calls 'bbp_loaded'
 */
function bboss_bbp_loaded() {
	do_action( 'bbp_loaded' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_bbp_loaded' ) && ! function_exists( 'bbp_loaded' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_bbp_loaded()}.
	 *
	 * @deprecated Use bboss_bbp_loaded() instead.
	 */
	function bbp_loaded() {
		return bboss_bbp_loaded();
	}
}
// @bbwt-com-end compat-layer


/**
 * Setup constants
 *
 * @since bbPress (r2599)
 * @uses do_action() Calls 'bbp_constants'
 */
function bboss_bbp_constants() {
	do_action( 'bbp_constants' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_bbp_constants' ) && ! function_exists( 'bbp_constants' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_bbp_constants()}.
	 *
	 * @deprecated Use bboss_bbp_constants() instead.
	 */
	function bbp_constants() {
		return bboss_bbp_constants();
	}
}
// @bbwt-com-end compat-layer


/**
 * Setup globals BEFORE includes
 *
 * @since bbPress (r2599)
 * @uses do_action() Calls 'bbp_boot_strap_globals'
 */
function bboss_bbp_boot_strap_globals() {
	do_action( 'bbp_boot_strap_globals' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_bbp_boot_strap_globals' ) && ! function_exists( 'bbp_boot_strap_globals' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_bbp_boot_strap_globals()}.
	 *
	 * @deprecated Use bboss_bbp_boot_strap_globals() instead.
	 */
	function bbp_boot_strap_globals() {
		return bboss_bbp_boot_strap_globals();
	}
}
// @bbwt-com-end compat-layer


/**
 * Include files
 *
 * @since bbPress (r2599)
 * @uses do_action() Calls 'bbp_includes'
 */
function bboss_bbp_includes() {
	do_action( 'bbp_includes' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_bbp_includes' ) && ! function_exists( 'bbp_includes' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_bbp_includes()}.
	 *
	 * @deprecated Use bboss_bbp_includes() instead.
	 */
	function bbp_includes() {
		return bboss_bbp_includes();
	}
}
// @bbwt-com-end compat-layer


/**
 * Setup globals AFTER includes
 *
 * @since bbPress (r2599)
 * @uses do_action() Calls 'bbp_setup_globals'
 */
function bboss_bbp_setup_globals() {
	do_action( 'bbp_setup_globals' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_bbp_setup_globals' ) && ! function_exists( 'bbp_setup_globals' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_bbp_setup_globals()}.
	 *
	 * @deprecated Use bboss_bbp_setup_globals() instead.
	 */
	function bbp_setup_globals() {
		return bboss_bbp_setup_globals();
	}
}
// @bbwt-com-end compat-layer


/**
 * Register any objects before anything is initialized
 *
 * @since bbPress (r4180)
 * @uses do_action() Calls 'bbp_register'
 */
function bboss_bbp_register() {
	do_action( 'bbp_register' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_bbp_register' ) && ! function_exists( 'bbp_register' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_bbp_register()}.
	 *
	 * @deprecated Use bboss_bbp_register() instead.
	 */
	function bbp_register() {
		return bboss_bbp_register();
	}
}
// @bbwt-com-end compat-layer


/**
 * Initialize any code after everything has been loaded
 *
 * @since bbPress (r2599)
 * @uses do_action() Calls 'bbp_init'
 */
function bboss_bbp_init() {
	do_action( 'bbp_init' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_bbp_init' ) && ! function_exists( 'bbp_init' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_bbp_init()}.
	 *
	 * @deprecated Use bboss_bbp_init() instead.
	 */
	function bbp_init() {
		return bboss_bbp_init();
	}
}
// @bbwt-com-end compat-layer


/**
 * Initialize widgets
 *
 * @since bbPress (r3389)
 * @uses do_action() Calls 'bbp_widgets_init'
 */
function bboss_bbp_widgets_init() {
	do_action( 'bbp_widgets_init' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_bbp_widgets_init' ) && ! function_exists( 'bbp_widgets_init' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_bbp_widgets_init()}.
	 *
	 * @deprecated Use bboss_bbp_widgets_init() instead.
	 */
	function bbp_widgets_init() {
		return bboss_bbp_widgets_init();
	}
}
// @bbwt-com-end compat-layer


/**
 * Initialize roles
 *
 * @since bbPress (r6147)
 *
 * @param WP_Roles $wp_roles The main WordPress roles global
 *
 * @uses do_action() Calls 'bbp_roles_init'
 */
function bboss_bbp_roles_init( $wp_roles = null ) {
	do_action( 'bbp_roles_init', $wp_roles );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_bbp_roles_init' ) && ! function_exists( 'bbp_roles_init' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_bbp_roles_init()}.
	 *
	 * @deprecated Use bboss_bbp_roles_init() instead.
	 */
	function bbp_roles_init( $wp_roles = null ) {
		return bboss_bbp_roles_init( $wp_roles );
	}
}
// @bbwt-com-end compat-layer


/**
 * Setup the currently logged-in user
 *
 * @since bbPress (r2695)
 * @uses do_action() Calls 'bbp_setup_current_user'
 */
function bboss_bbp_setup_current_user() {
	do_action( 'bbp_setup_current_user' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_bbp_setup_current_user' ) && ! function_exists( 'bbp_setup_current_user' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_bbp_setup_current_user()}.
	 *
	 * @deprecated Use bboss_bbp_setup_current_user() instead.
	 */
	function bbp_setup_current_user() {
		return bboss_bbp_setup_current_user();
	}
}
// @bbwt-com-end compat-layer


/** Supplemental Actions ******************************************************/

/**
 * Setup the post types
 *
 * @since bbPress (r2464)
 * @uses do_action() Calls 'bbp_register_post_type'
 */
function bboss_bbp_register_post_types() {
	do_action( 'bbp_register_post_types' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_bbp_register_post_types' ) && ! function_exists( 'bbp_register_post_types' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_bbp_register_post_types()}.
	 *
	 * @deprecated Use bboss_bbp_register_post_types() instead.
	 */
	function bbp_register_post_types() {
		return bboss_bbp_register_post_types();
	}
}
// @bbwt-com-end compat-layer


/**
 * Setup the post statuses
 *
 * @since bbPress (r2727)
 * @uses do_action() Calls 'bbp_register_post_statuses'
 */
function bboss_bbp_register_post_statuses() {
	do_action( 'bbp_register_post_statuses' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_bbp_register_post_statuses' ) && ! function_exists( 'bbp_register_post_statuses' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_bbp_register_post_statuses()}.
	 *
	 * @deprecated Use bboss_bbp_register_post_statuses() instead.
	 */
	function bbp_register_post_statuses() {
		return bboss_bbp_register_post_statuses();
	}
}
// @bbwt-com-end compat-layer


/**
 * Register the built in Forums taxonomies
 *
 * @since bbPress (r2464)
 * @uses do_action() Calls 'bbp_register_taxonomies'
 */
function bboss_bbp_register_taxonomies() {
	do_action( 'bbp_register_taxonomies' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_bbp_register_taxonomies' ) && ! function_exists( 'bbp_register_taxonomies' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_bbp_register_taxonomies()}.
	 *
	 * @deprecated Use bboss_bbp_register_taxonomies() instead.
	 */
	function bbp_register_taxonomies() {
		return bboss_bbp_register_taxonomies();
	}
}
// @bbwt-com-end compat-layer


/**
 * Register the default Forums views
 *
 * @since bbPress (r2789)
 * @uses do_action() Calls 'bbp_register_views'
 */
function bboss_bbp_register_views() {
	do_action( 'bbp_register_views' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_bbp_register_views' ) && ! function_exists( 'bbp_register_views' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_bbp_register_views()}.
	 *
	 * @deprecated Use bboss_bbp_register_views() instead.
	 */
	function bbp_register_views() {
		return bboss_bbp_register_views();
	}
}
// @bbwt-com-end compat-layer


/**
 * Register the default Forums shortcodes
 *
 * @since bbPress (r4211)
 * @uses do_action() Calls 'bbp_register_shortcodes'
 */
function bboss_bbp_register_shortcodes() {
	do_action( 'bbp_register_shortcodes' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_bbp_register_shortcodes' ) && ! function_exists( 'bbp_register_shortcodes' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_bbp_register_shortcodes()}.
	 *
	 * @deprecated Use bboss_bbp_register_shortcodes() instead.
	 */
	function bbp_register_shortcodes() {
		return bboss_bbp_register_shortcodes();
	}
}
// @bbwt-com-end compat-layer


/**
 * Enqueue Forums specific CSS and JS
 *
 * @since bbPress (r3373)
 * @uses do_action() Calls 'bbp_enqueue_scripts'
 */
function bboss_bbp_enqueue_scripts() {
	do_action( 'bbp_enqueue_scripts' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_bbp_enqueue_scripts' ) && ! function_exists( 'bbp_enqueue_scripts' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_bbp_enqueue_scripts()}.
	 *
	 * @deprecated Use bboss_bbp_enqueue_scripts() instead.
	 */
	function bbp_enqueue_scripts() {
		return bboss_bbp_enqueue_scripts();
	}
}
// @bbwt-com-end compat-layer


/**
 * Add the Forums-specific rewrite tags
 *
 * @since bbPress (r2753)
 * @uses do_action() Calls 'bbp_add_rewrite_tags'
 */
function bboss_bbp_add_rewrite_tags() {
	do_action( 'bbp_add_rewrite_tags' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_bbp_add_rewrite_tags' ) && ! function_exists( 'bbp_add_rewrite_tags' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_bbp_add_rewrite_tags()}.
	 *
	 * @deprecated Use bboss_bbp_add_rewrite_tags() instead.
	 */
	function bbp_add_rewrite_tags() {
		return bboss_bbp_add_rewrite_tags();
	}
}
// @bbwt-com-end compat-layer


/**
 * Add the Forums-specific rewrite rules
 *
 * @since bbPress (r4918)
 * @uses do_action() Calls 'bbp_add_rewrite_rules'
 */
function bboss_bbp_add_rewrite_rules() {
	do_action( 'bbp_add_rewrite_rules' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_bbp_add_rewrite_rules' ) && ! function_exists( 'bbp_add_rewrite_rules' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_bbp_add_rewrite_rules()}.
	 *
	 * @deprecated Use bboss_bbp_add_rewrite_rules() instead.
	 */
	function bbp_add_rewrite_rules() {
		return bboss_bbp_add_rewrite_rules();
	}
}
// @bbwt-com-end compat-layer


/**
 * Add the Forums-specific permalink structures
 *
 * @since bbPress (r4918)
 * @uses do_action() Calls 'bbp_add_permastructs'
 */
function bboss_bbp_add_permastructs() {
	do_action( 'bbp_add_permastructs' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_bbp_add_permastructs' ) && ! function_exists( 'bbp_add_permastructs' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_bbp_add_permastructs()}.
	 *
	 * @deprecated Use bboss_bbp_add_permastructs() instead.
	 */
	function bbp_add_permastructs() {
		return bboss_bbp_add_permastructs();
	}
}
// @bbwt-com-end compat-layer


/**
 * Add the Forums-specific login forum action
 *
 * @since bbPress (r2753)
 * @uses do_action() Calls 'bbp_login_form_login'
 */
function bboss_bbp_login_form_login() {
	do_action( 'bbp_login_form_login' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_bbp_login_form_login' ) && ! function_exists( 'bbp_login_form_login' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_bbp_login_form_login()}.
	 *
	 * @deprecated Use bboss_bbp_login_form_login() instead.
	 */
	function bbp_login_form_login() {
		return bboss_bbp_login_form_login();
	}
}
// @bbwt-com-end compat-layer


/** User Actions **************************************************************/

/**
 * The main action for hooking into when a user account is updated
 *
 * @since bbPress (r4304)
 *
 * @param int   $user_id ID of user being edited
 * @param array $old_user_data The old, unmodified user data
 * @uses do_action() Calls 'bbp_profile_update'
 */
function bboss_bbp_profile_update( $user_id = 0, $old_user_data = array() ) {
	do_action( 'bbp_profile_update', $user_id, $old_user_data );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_bbp_profile_update' ) && ! function_exists( 'bbp_profile_update' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_bbp_profile_update()}.
	 *
	 * @deprecated Use bboss_bbp_profile_update() instead.
	 */
	function bbp_profile_update( $user_id = 0, $old_user_data = array() ) {
		return bboss_bbp_profile_update( $user_id, $old_user_data );
	}
}
// @bbwt-com-end compat-layer


/**
 * The main action for hooking into a user being registered
 *
 * @since bbPress (r4304)
 * @param int $user_id ID of user being edited
 * @uses do_action() Calls 'bbp_user_register'
 */
function bboss_bbp_user_register( $user_id = 0 ) {
	do_action( 'bbp_user_register', $user_id );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_bbp_user_register' ) && ! function_exists( 'bbp_user_register' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_bbp_user_register()}.
	 *
	 * @deprecated Use bboss_bbp_user_register() instead.
	 */
	function bbp_user_register( $user_id = 0 ) {
		return bboss_bbp_user_register( $user_id );
	}
}
// @bbwt-com-end compat-layer


/** Final Action **************************************************************/

/**
 * Forums has loaded and initialized everything, and is okay to go
 *
 * @since bbPress (r2618)
 * @uses do_action() Calls 'bbp_ready'
 */
function bboss_bbp_ready() {
	do_action( 'bbp_ready' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_bbp_ready' ) && ! function_exists( 'bbp_ready' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_bbp_ready()}.
	 *
	 * @deprecated Use bboss_bbp_ready() instead.
	 */
	function bbp_ready() {
		return bboss_bbp_ready();
	}
}
// @bbwt-com-end compat-layer


/** Theme Permissions *********************************************************/

/**
 * The main action used for redirecting Forums theme actions that are not
 * permitted by the current_user
 *
 * @since bbPress (r3605)
 * @uses do_action()
 */
function bboss_bbp_template_redirect() {
	do_action( 'bbp_template_redirect' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_bbp_template_redirect' ) && ! function_exists( 'bbp_template_redirect' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_bbp_template_redirect()}.
	 *
	 * @deprecated Use bboss_bbp_template_redirect() instead.
	 */
	function bbp_template_redirect() {
		return bboss_bbp_template_redirect();
	}
}
// @bbwt-com-end compat-layer


/** Theme Helpers *************************************************************/

/**
 * The main action used for executing code before the theme has been setup
 *
 * @since bbPress (r3829)
 * @uses do_action()
 */
function bboss_bbp_register_theme_packages() {
	do_action( 'bbp_register_theme_packages' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_bbp_register_theme_packages' ) && ! function_exists( 'bbp_register_theme_packages' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_bbp_register_theme_packages()}.
	 *
	 * @deprecated Use bboss_bbp_register_theme_packages() instead.
	 */
	function bbp_register_theme_packages() {
		return bboss_bbp_register_theme_packages();
	}
}
// @bbwt-com-end compat-layer


/**
 * The main action used for executing code before the theme has been setup
 *
 * @since bbPress (r3732)
 * @uses do_action()
 */
function bboss_bbp_setup_theme() {
	do_action( 'bbp_setup_theme' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_bbp_setup_theme' ) && ! function_exists( 'bbp_setup_theme' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_bbp_setup_theme()}.
	 *
	 * @deprecated Use bboss_bbp_setup_theme() instead.
	 */
	function bbp_setup_theme() {
		return bboss_bbp_setup_theme();
	}
}
// @bbwt-com-end compat-layer


/**
 * The main action used for executing code after the theme has been setup
 *
 * @since bbPress (r3732)
 * @uses do_action()
 */
function bboss_bbp_after_setup_theme() {
	do_action( 'bbp_after_setup_theme' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_bbp_after_setup_theme' ) && ! function_exists( 'bbp_after_setup_theme' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_bbp_after_setup_theme()}.
	 *
	 * @deprecated Use bboss_bbp_after_setup_theme() instead.
	 */
	function bbp_after_setup_theme() {
		return bboss_bbp_after_setup_theme();
	}
}
// @bbwt-com-end compat-layer


/**
 * The main action used for handling theme-side POST requests
 *
 * @since bbPress (r4550)
 * @uses do_action()
 */
function bboss_bbp_post_request() {

	// Bail if not a POST action
	if ( ! bboss_bbp_is_post_request() ) {
		return;
	}

	// Bail if no action, or if not a string (arrays not supported)
	if ( empty( $_POST['action'] ) || ! is_string( $_POST['action'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- dispatcher only; every handler hooked to bbp_post_request verifies its own nonce.
		return;
	}

	// Sanitize the POST action
	$action = sanitize_key( $_POST['action'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- dispatcher only; every handler hooked to bbp_post_request verifies its own nonce.

	// Bail if action was totally invalid
	if ( empty( $action ) ) {
		return;
	}

	// This dynamic action is probably the one you want to use. It narrows down
	// the scope of the 'action' without needing to check it in your function.
	do_action( 'bbp_post_request_' . $action );

	// Use this static action if you don't mind checking the 'action' yourself.
	do_action( 'bbp_post_request',   $action );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_bbp_post_request' ) && ! function_exists( 'bbp_post_request' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_bbp_post_request()}.
	 *
	 * @deprecated Use bboss_bbp_post_request() instead.
	 */
	function bbp_post_request() {
		return bboss_bbp_post_request();
	}
}
// @bbwt-com-end compat-layer


/**
 * The main action used for handling theme-side GET requests
 *
 * @since bbPress (r4550)
 * @uses do_action()
 */
function bboss_bbp_get_request() {

	// Bail if not a POST action
	if ( ! bboss_bbp_is_get_request() ) {
		return;
	}

	// Bail if no action, or if not a string (arrays not supported)
	if ( empty( $_GET['action'] ) || ! is_string( $_GET['action'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- dispatcher only; every handler hooked to bbp_get_request verifies its own nonce.
		return;
	}

	// Sanitize the GET action
	$action = sanitize_key( $_GET['action'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- dispatcher only; every handler hooked to bbp_get_request verifies its own nonce.

	// Bail if action was totally invalid
	if ( empty( $action ) ) {
		return;
	}

	// This dynamic action is probably the one you want to use. It narrows down
	// the scope of the 'action' without needing to check it in your function.
	do_action( 'bbp_get_request_' . $action );

	// Use this static action if you don't mind checking the 'action' yourself.
	do_action( 'bbp_get_request',   $action );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_bbp_get_request' ) && ! function_exists( 'bbp_get_request' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_bbp_get_request()}.
	 *
	 * @deprecated Use bboss_bbp_get_request() instead.
	 */
	function bbp_get_request() {
		return bboss_bbp_get_request();
	}
}
// @bbwt-com-end compat-layer


/** Filters *******************************************************************/

/**
 * Filter the plugin locale and domain.
 *
 * @since bbPress (r4213)
 *
 * @param string $locale
 * @param string $domain
 */
function bboss_bbp_plugin_locale( $locale = '', $domain = '' ) {
	return apply_filters( 'bbp_plugin_locale', $locale, $domain );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_bbp_plugin_locale' ) && ! function_exists( 'bbp_plugin_locale' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_bbp_plugin_locale()}.
	 *
	 * @deprecated Use bboss_bbp_plugin_locale() instead.
	 */
	function bbp_plugin_locale( $locale = '', $domain = '' ) {
		return bboss_bbp_plugin_locale( $locale, $domain );
	}
}
// @bbwt-com-end compat-layer


/**
 * Piggy back filter for WordPress's 'request' filter
 *
 * @since bbPress (r3758)
 * @param array $query_vars
 * @return array
 */
function bboss_bbp_request( $query_vars = array() ) {
	return apply_filters( 'bbp_request', $query_vars );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_bbp_request' ) && ! function_exists( 'bbp_request' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_bbp_request()}.
	 *
	 * @deprecated Use bboss_bbp_request() instead.
	 */
	function bbp_request( $query_vars = array() ) {
		return bboss_bbp_request( $query_vars );
	}
}
// @bbwt-com-end compat-layer


/**
 * The main filter used for theme compatibility and displaying custom Forums
 * theme files.
 *
 * @since bbPress (r3311)
 * @uses apply_filters()
 * @param string $template
 * @return string Template file to use
 */
function bboss_bbp_template_include( $template = '' ) {
	return apply_filters( 'bbp_template_include', $template );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_bbp_template_include' ) && ! function_exists( 'bbp_template_include' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_bbp_template_include()}.
	 *
	 * @deprecated Use bboss_bbp_template_include() instead.
	 */
	function bbp_template_include( $template = '' ) {
		return bboss_bbp_template_include( $template );
	}
}
// @bbwt-com-end compat-layer


/**
 * Generate Forums-specific rewrite rules
 *
 * @since bbPress (r2688)
 * @deprecated since Forums (r4918)
 * @param WP_Rewrite $wp_rewrite
 * @uses do_action() Calls 'bbp_generate_rewrite_rules' with {@link WP_Rewrite}
 */
function bboss_bbp_generate_rewrite_rules( $wp_rewrite ) {
	do_action_ref_array( 'bbp_generate_rewrite_rules', array( &$wp_rewrite ) );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_bbp_generate_rewrite_rules' ) && ! function_exists( 'bbp_generate_rewrite_rules' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_bbp_generate_rewrite_rules()}.
	 *
	 * @deprecated Use bboss_bbp_generate_rewrite_rules() instead.
	 */
	function bbp_generate_rewrite_rules( $wp_rewrite ) {
		return bboss_bbp_generate_rewrite_rules( $wp_rewrite );
	}
}
// @bbwt-com-end compat-layer


/**
 * Filter the allowed themes list for Forums specific themes
 *
 * @since bbPress (r2944)
 * @uses apply_filters() Calls 'bbp_allowed_themes' with the allowed themes list
 */
function bboss_bbp_allowed_themes( $themes ) {
	return apply_filters( 'bbp_allowed_themes', $themes );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_bbp_allowed_themes' ) && ! function_exists( 'bbp_allowed_themes' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_bbp_allowed_themes()}.
	 *
	 * @deprecated Use bboss_bbp_allowed_themes() instead.
	 */
	function bbp_allowed_themes( $themes ) {
		return bboss_bbp_allowed_themes( $themes );
	}
}
// @bbwt-com-end compat-layer


/**
 * Maps forum/topic/reply caps to built in WordPress caps
 *
 * @since bbPress (r2593)
 *
 * @param array  $caps Capabilities for meta capability
 * @param string $cap Capability name
 * @param int    $user_id User id
 * @param mixed  $args Arguments
 */
function bboss_bbp_map_meta_caps( $caps = array(), $cap = '', $user_id = 0, $args = array() ) {
	return apply_filters( 'bbp_map_meta_caps', $caps, $cap, $user_id, $args );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_bbp_map_meta_caps' ) && ! function_exists( 'bbp_map_meta_caps' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_bbp_map_meta_caps()}.
	 *
	 * @deprecated Use bboss_bbp_map_meta_caps() instead.
	 */
	function bbp_map_meta_caps( $caps = array(), $cap = '', $user_id = 0, $args = array() ) {
		return bboss_bbp_map_meta_caps( $caps, $cap, $user_id, $args );
	}
}
// @bbwt-com-end compat-layer


/**
 * Setup the user engagements strategy.
 *
 * @since 2.6.0 bbPress (r6875)
 * @since BuddyBoss 2.3.4
 */
function bboss_bbp_setup_engagements() {
	do_action( 'bbp_setup_engagements' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_bbp_setup_engagements' ) && ! function_exists( 'bbp_setup_engagements' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_bbp_setup_engagements()}.
	 *
	 * @deprecated Use bboss_bbp_setup_engagements() instead.
	 */
	function bbp_setup_engagements() {
		return bboss_bbp_setup_engagements();
	}
}
// @bbwt-com-end compat-layer


