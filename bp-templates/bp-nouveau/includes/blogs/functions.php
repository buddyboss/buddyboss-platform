<?php
/**
 * Blogs functions
 *
 * @since BuddyPress 3.0.0
 * @version 3.1.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Get blog directory navigation menu items.
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_get_blogs_directory_nav_items() {
	$nav_items = array();

	$nav_items['all'] = array(
		'component' => 'blogs',
		'slug'      => 'all', // slug is used because BP_Core_Nav requires it, but it's the scope
		'li_class'  => array( 'selected' ),
		'link'      => bp_get_root_domain() . '/' . bp_get_blogs_root_slug(),
		'text'      => __( 'All Sites', 'buddyboss' ),
		'count'     => bp_get_total_blog_count(),
		'position'  => 5,
	);

	if ( is_user_logged_in() ) {
		$my_blogs_count = bp_get_total_blog_count_for_user( bp_loggedin_user_id() );

		// If the user has blogs create a nav item
		if ( $my_blogs_count ) {
			$nav_items['personal'] = array(
				'component' => 'blogs',
				'slug'      => 'personal', // slug is used because BP_Core_Nav requires it, but it's the scope
				'li_class'  => array(),
				'link'      => bp_loggedin_user_domain() . bp_get_blogs_slug(),
				'text'      => __( 'My Sites', 'buddyboss' ),
				'count'     => $my_blogs_count,
				'position'  => 15,
			);
		}

		// If the user can create blogs, add the create nav
		if ( bp_blog_signup_enabled() ) {
			$nav_items['create'] = array(
				'component' => 'blogs',
				'slug'      => 'create', // slug is used because BP_Core_Nav requires it, but it's the scope
				'li_class'  => array( 'no-ajax', 'site-create', 'create-button' ),
				'link'      => trailingslashit( bp_get_blogs_directory_permalink() . 'create' ),
				'text'      => __( 'Create a Site', 'buddyboss' ),
				'count'     => false,
				'position'  => 999,
			);
		}
	}

	// Check for the deprecated hook :
	$extra_nav_items = bp_nouveau_parse_hooked_dir_nav( 'bp_blogs_directory_blog_types', 'blogs', 20 );

	if ( ! empty( $extra_nav_items ) ) {
		$nav_items = array_merge( $nav_items, $extra_nav_items );
	}

	/**
	 * Use this filter to introduce your custom nav items for the blogs directory.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @param  array $nav_items The list of the blogs directory nav items.
	 */
	return apply_filters( 'bp_nouveau_get_blogs_directory_nav_items', $nav_items );
}

/**
 * Get Dropdown filters for the blogs component
 *
 * @since BuddyPress 3.0.0
 *
 * @param string $context 'directory' or 'user'
 *
 * @return array the filters
 */
function bp_nouveau_get_blogs_filters( $context = '' ) {
	if ( empty( $context ) ) {
		return array();
	}

	$action = '';
	if ( 'user' === $context ) {
		$action = 'bp_member_blog_order_options';
	} elseif ( 'directory' === $context ) {
		$action = 'bp_blogs_directory_order_options';
	}

	/**
	 * Recommended, filter here instead of adding an action to 'bp_member_blog_order_options'
	 * or 'bp_blogs_directory_order_options'
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @param array  the blogs filters.
	 * @param string the context.
	 */
	$filters = apply_filters( 'bp_nouveau_get_blogs_filters', array(
		'active'       => __( 'Recently Active', 'buddyboss' ),
		'newest'       => __( 'Newest', 'buddyboss' ),
		'alphabetical' => __( 'Alphabetical', 'buddyboss' ),
	), $context );

	if ( $action ) {
		return bp_nouveau_parse_hooked_options( $action, $filters );
	}

	return $filters;
}

/**
 * Catch the arguments for buttons
 *
 * @since BuddyPress 3.0.0
 *
 * @param array $buttons The arguments of the button that BuddyPress is about to create.
 *
 * @return array An empty array to stop the button creation process.
 */
function bp_nouveau_blogs_catch_button_args( $button = array() ) {
	// Globalize the arguments so that we can use it  in bp_nouveau_get_blogs_buttons().
	bp_nouveau()->blogs->button_args = $button;

	// return an empty array to stop the button creation process
	return array();
}

/**
 * Inline script to toggle the signup blog form
 *
 * @since BuddyPress 3.0.0
 *
 * @return string Javascript output
 */
function bp_nouveau_get_blog_signup_inline_script() {
	return '
		( function( $ ) {
			if ( $( \'body\' ).hasClass( \'register\' ) ) {
				var blog_checked = $( \'#signup_with_blog\' );

				// hide "Blog Details" block if not checked by default
				if ( ! blog_checked.prop( \'checked\' ) ) {
					$( \'#blog-details\' ).toggle();
				}

				// toggle "Blog Details" block whenever checkbox is checked
				blog_checked.change( function( event ) {
					// Toggle HTML5 required attribute.
					$.each( $( \'#blog-details\' ).find( \'[aria-required]\' ), function( i, input ) {
						$( input ).prop( \'required\',  $( event.target ).prop( \'checked\' ) );
					} );

					$( \'#blog-details\' ).toggle();
				} );
			}
		} )( jQuery );
	';
}

/**
 * Filter bp_get_blog_class().
 * Adds a class if blog item has a latest post.
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_blog_loop_item_has_lastest_post( $classes ) {
	if ( bp_get_blog_latest_post_title() ) {
		$classes[] = 'has-latest-post';
	}

	return $classes;
}
add_filter( 'bp_get_blog_class', 'bp_nouveau_blog_loop_item_has_lastest_post' );
