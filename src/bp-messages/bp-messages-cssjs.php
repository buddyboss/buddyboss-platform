<?php
/**
 * BuddyBoss Messages CSS and JS.
 *
 * @package BuddyBoss\Messages\Scripts
 * @since BuddyPress 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Enqueue the JS for messages autocomplete.
 */
function messages_add_autocomplete_js() {

	// Include the autocomplete JS for composing a message.
	if ( bp_is_messages_component() && bp_is_current_action( 'compose' ) ) {
		$min = bp_core_get_minified_asset_suffix();
		$url = buddypress()->plugin_url . 'bp-messages/js/';

		wp_enqueue_script( 'bp-jquery-autocomplete', "{$url}autocomplete/jquery.autocomplete{$min}.js", array( 'jquery' ), bp_get_version() );
		wp_enqueue_script( 'bp-jquery-autocomplete-fb', "{$url}autocomplete/jquery.autocompletefb{$min}.js", array( 'jquery' ), bp_get_version() );
		wp_enqueue_script( 'bp-jquery-bgiframe', "{$url}autocomplete/jquery.bgiframe{$min}.js", array( 'jquery' ), bp_get_version() );
		wp_enqueue_script( 'bp-jquery-dimensions', "{$url}autocomplete/jquery.dimensions{$min}.js", array( 'jquery' ), bp_get_version() );

		// Initialize the autocomplete via an inline script attached to the enqueued handle
		// instead of printing a raw <script> tag on wp_head.
		$autocomplete_init_js = 'jQuery(document).ready(function() {' .
			'var acfb = jQuery("ul.first").autoCompletefb({urlLookup: ajaxurl});' .
			'jQuery("#send_message_form").submit( function() {' .
			'var users = document.getElementById("send-to-usernames").className;' .
			'document.getElementById("send-to-usernames").value = String(users);' .
			'});' .
			'});';
		wp_add_inline_script( 'bp-jquery-autocomplete-fb', $autocomplete_init_js );
	}
}
add_action( 'bp_enqueue_scripts', 'messages_add_autocomplete_js' );

/**
 * Enqueue the CSS for messages autocomplete.
 *
 * @todo Why do we call wp_print_styles()?
 */
function messages_add_autocomplete_css() {
	if ( bp_is_messages_component() && bp_is_current_action( 'compose' ) ) {
		$min = bp_core_get_minified_asset_suffix();
		$url = buddypress()->plugin_url . 'bp-messages/css/';

		wp_enqueue_style( 'bp-messages-autocomplete', "{$url}autocomplete/jquery.autocompletefb{$min}.css", array(), bp_get_version() );

		wp_style_add_data( 'bp-messages-autocomplete', 'rtl', true );
		if ( $min ) {
			wp_style_add_data( 'bp-messages-autocomplete', 'suffix', $min );
		}

		wp_print_styles();
	}
}
add_action( 'wp_head', 'messages_add_autocomplete_css' );

/**
 * Print inline JS for initializing the messages autocomplete.
 *
 * @deprecated The autocomplete initialization is now attached to the enqueued
 *             `bp-jquery-autocomplete-fb` handle via wp_add_inline_script() in
 *             messages_add_autocomplete_js(). This function is retained for
 *             backward compatibility only and is no longer hooked to wp_head.
 */
function messages_autocomplete_init_jsblock() {
	$autocomplete_init_js = 'jQuery(document).ready(function() {' .
		'var acfb = jQuery("ul.first").autoCompletefb({urlLookup: ajaxurl});' .
		'jQuery("#send_message_form").submit( function() {' .
		'var users = document.getElementById("send-to-usernames").className;' .
		'document.getElementById("send-to-usernames").value = String(users);' .
		'});' .
		'});';
	wp_print_inline_script_tag( $autocomplete_init_js );
}
