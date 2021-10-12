<?php
/**
 * @todo add description
 *
 * @package BuddyBoss\Search
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

add_action( 'bp_search_settings_items_to_search', 'bp_search_option_cpt_search' );
/**
 * Print all custom post types on settings screen.
 *
 * @param array $items_to_search
 * @since BuddyBoss 1.0.0
 */
function bp_search_option_cpt_search( $items_to_search ) {
	// all the cpts registered
	$cpts = get_post_types(
		array(
			'public'              => true,
			'publicly_queryable'  => true,
			'exclude_from_search' => false,
		),
		'objects'
	);

	// remove posts
	$cpts['post'] = null;
	unset( $cpts['post'] );

	// remove attachment
	$cpts['attachment'] = null;
	unset( $cpts['attachment'] );

	$cpts = apply_filters( 'bp_search_cpts_to_search', $cpts );

	if ( ! empty( $cpts ) ) {
		foreach ( $cpts as $cpt => $cpt_obj ) {
			$checked = ! empty( $items_to_search ) && in_array( 'cpt-' . $cpt, $items_to_search ) ? ' checked' : '';
			echo "<label><input type='checkbox' value='cpt-{$cpt}' name='bp_search_plugin_options[items-to-search][]' {$checked}>{$cpt_obj->label}</label><br>";
			do_action( 'bp_search_settings_item_' . $cpt, $items_to_search );
		}
	}
}

add_filter( 'bp_search_additional_search_helpers', 'bp_search_helpers_cpts' );
/**
 * Load search helpers for each searchable custom post type.
 *
 * @param array $helpers
 * @return array
 * @since BuddyBoss 1.0.0
 */
function bp_search_helpers_cpts( $helpers ) {

	$post_types          = get_post_types( array( 'public' => true ) );
	$custom_handler_cpts = array( 'post', 'forum', 'topic', 'reply', 'page' );

	foreach ( $post_types as $post_type ) {
		// if name starts with cpt-
		if ( ! in_array( $post_type, $custom_handler_cpts ) && bp_is_search_post_type_enable( $post_type ) ) {
			$searchable_type = 'cpt-' . $post_type;
			$cpt_obj         = get_post_type_object( $post_type );
			// is cpt still valid?
			if ( $cpt_obj && ! is_wp_error( $cpt_obj ) ) {
				require_once buddypress()->plugin_dir . 'bp-search/plugins/search-cpt/class-bp-search-cpt.php';
				$helpers[ $searchable_type ]              = new BP_Search_CPT( $post_type, $searchable_type );
				BP_Search::instance()->searchable_items[] = $searchable_type;
			}
		}
	}

	return $helpers;
}

add_filter( 'bp_search_label_search_type', 'bp_search_label_search_type_cpts' );
/**
 * Change the display text of custom post type search tabs.
 * Change it from 'cpt-movie' to 'Movies' for example.
 *
 * @param string $search_type_label
 * @return string
 * @since BuddyBoss 1.0.0
 */
function bp_search_label_search_type_cpts( $search_type_label ) {
	/**
	 * search type is 'cpt-movie', 'cpt-book' etc.
	 * so removing 'cpt-' gives us the custom post type name
	 */

	// Return label from admin search items options
	$items = bp_search_items();
	if ( isset( $items[ $search_type_label ] ) ) {
		return $items[ $search_type_label ];
	}

	$pos = strpos( $search_type_label, 'cpt-' );
	if ( $pos === 0 ) {
		$cpt_name = str_replace( 'cpt-', '', $search_type_label );

		$cpt_obj = get_post_type_object( $cpt_name );
		if ( $cpt_obj && ! is_wp_error( $cpt_obj ) ) {
			$search_type_label = $cpt_obj->label;
		}
	}

	$pos = strpos( $search_type_label, 'Cpt-' );
	if ( $pos === 0 ) {
		$cpt_name = str_replace( 'Cpt-', '', $search_type_label );

		$cpt_obj = get_post_type_object( $cpt_name );
		if ( $cpt_obj && ! is_wp_error( $cpt_obj ) ) {
			$search_type_label = $cpt_obj->label;
		}
	}
	return $search_type_label;
}
