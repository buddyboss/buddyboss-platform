<?php
/**
 * Deprecated functions.
 *
 * @deprecated BuddyPress 2.6.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Print the generation time in the footer of the site.
 *
 * @since BuddyPress 1.0.0
 * @deprecated BuddyPress 2.6.0
 */
function bboss_core_print_generation_time() {
	?>

<!-- Generated in <?php timer_stop( 1 ); ?> seconds. (<?php echo esc_html( get_num_queries() ); ?> q) -->

	<?php
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_core_print_generation_time' ) && ! function_exists( 'bp_core_print_generation_time' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_core_print_generation_time()}.
	 *
	 * @deprecated Use bboss_core_print_generation_time() instead.
	 */
	function bp_core_print_generation_time() {
		return bboss_core_print_generation_time();
	}
}
// @bbwt-com-end compat-layer


/**
 * Sort the navigation menu items.
 *
 * The sorting is split into a separate function because it can only happen
 * after all plugins have had a chance to register their navigation items.
 *
 * @since BuddyPress 1.0.0
 * @deprecated BuddyPress 2.6.0
 *
 * @return bool|null Returns false on failure.
 */
function bboss_core_sort_nav_items() {
	_deprecated_function( __FUNCTION__, '2.6' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_core_sort_nav_items' ) && ! function_exists( 'bp_core_sort_nav_items' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_core_sort_nav_items()}.
	 *
	 * @deprecated Use bboss_core_sort_nav_items() instead.
	 */
	function bp_core_sort_nav_items() {
		return bboss_core_sort_nav_items();
	}
}
// @bbwt-com-end compat-layer


/**
 * Sort all subnavigation arrays.
 *
 * @since BuddyPress 1.1.0
 * @deprecated BuddyPress 2.6.0
 *
 * @return bool|null Returns false on failure.
 */
function bboss_core_sort_subnav_items() {
	_deprecated_function( __FUNCTION__, '2.6' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_core_sort_subnav_items' ) && ! function_exists( 'bp_core_sort_subnav_items' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_core_sort_subnav_items()}.
	 *
	 * @deprecated Use bboss_core_sort_subnav_items() instead.
	 */
	function bp_core_sort_subnav_items() {
		return bboss_core_sort_subnav_items();
	}
}
// @bbwt-com-end compat-layer

