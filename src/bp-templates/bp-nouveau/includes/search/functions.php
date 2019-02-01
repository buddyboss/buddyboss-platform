<?php
/**
 * Search functions
 *
 * @since BuddyPress 3.0.0
 * @version 3.1.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Scripts for the search component
 *
 * @since BuddyPress 3.0.0
 *
 * @param  array $scripts The array of scripts to register
 *
 * @return array  The same array with the specific search scripts.
 */
function bp_nouveau_search_register_scripts( $scripts = array() ) {

	if ( ! isset( $scripts['bp-nouveau'] ) ) {
		return $scripts;
	}

	return array_merge( $scripts, array(
		'bp-nouveau-search' => array(
			'file'         => 'js/buddypress-search%s.js',
			'dependencies' => array( 'bp-nouveau' ),
			'footer'       => true,
		),
	) );
}

/**
 * Enqueue the search scripts
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_search_enqueue_scripts() {

	$data = array(
		'nonce'              => wp_create_nonce( 'bboss_global_search_ajax' ),
		'action'             => 'bboss_global_search_ajax',
		'debug'              => true,//set it to false on production
		'ajaxurl'            => admin_url( 'admin-ajax.php', is_ssl() ? 'admin' : 'http' ),
		//'search_url'    => home_url( '/' ), Now we are using form[role='search'] selector
		'loading_msg'        => __( "Loading Suggestions", "buddypress-global-search" ),
		'enable_ajax_search' => bp_is_search_autotcomplete_enable(),
		'per_page'           => bp_search_get_form_option( 'bp_search_number_of_results', 5 )
	);

	if ( isset( $_GET["s"] ) ) {
		$data["search_term"] = $_GET["s"];
	}

	wp_enqueue_script( 'bp-nouveau-search' );

	wp_localize_script( 'bp-nouveau-search', 'BP_SEARCH', $data );
}
