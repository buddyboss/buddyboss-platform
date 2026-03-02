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
function bp_core_print_generation_time() {
	?>

<!-- Generated in <?php timer_stop( 1 ); ?> seconds. (<?php echo get_num_queries(); ?> q) -->

	<?php
}

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
function bp_core_sort_nav_items() {
	_deprecated_function( __FUNCTION__, '2.6' );
}

/**
 * Sort all subnavigation arrays.
 *
 * @since BuddyPress 1.1.0
 * @deprecated BuddyPress 2.6.0
 *
 * @return bool|null Returns false on failure.
 */
function bp_core_sort_subnav_items() {
	_deprecated_function( __FUNCTION__, '2.6' );
}
