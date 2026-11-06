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
function bboss_messages_add_autocomplete_js() {

	// Include the autocomplete JS for composing a message.
	if ( bboss_is_messages_component() && bboss_is_current_action( 'compose' ) ) {
		$min = bboss_core_get_minified_asset_suffix();
		$url = bboss()->plugin_url . 'bp-messages/js/';

		wp_enqueue_script( 'bp-jquery-autocomplete', "{$url}autocomplete/jquery.autocomplete{$min}.js", array( 'jquery' ), bboss_get_version() );
		wp_enqueue_script( 'bp-jquery-autocomplete-fb', "{$url}autocomplete/jquery.autocompletefb{$min}.js", array( 'jquery' ), bboss_get_version() );
		wp_enqueue_script( 'bp-jquery-bgiframe', "{$url}autocomplete/jquery.bgiframe{$min}.js", array( 'jquery' ), bboss_get_version() );
		wp_enqueue_script( 'bp-jquery-dimensions', "{$url}autocomplete/jquery.dimensions{$min}.js", array( 'jquery' ), bboss_get_version() );

		// Attach the init to the autocompletefb handle it depends on (after the enqueue, so the handle exists).
		bboss_messages_autocomplete_init_jsblock();
	}
}
add_action( 'bp_enqueue_scripts', 'bboss_messages_add_autocomplete_js' );

/**
 * Enqueue the CSS for messages autocomplete.
 *
 * @todo Why do we call wp_print_styles()?
 */
function bboss_messages_add_autocomplete_css() {
	if ( bboss_is_messages_component() && bboss_is_current_action( 'compose' ) ) {
		$min = bboss_core_get_minified_asset_suffix();
		$url = bboss()->plugin_url . 'bp-messages/css/';

		wp_enqueue_style( 'bp-messages-autocomplete', "{$url}autocomplete/jquery.autocompletefb{$min}.css", array(), bboss_get_version() );

		wp_style_add_data( 'bp-messages-autocomplete', 'rtl', true );
		if ( $min ) {
			wp_style_add_data( 'bp-messages-autocomplete', 'suffix', $min );
		}

		wp_print_styles();
	}
}
add_action( 'wp_head', 'bboss_messages_add_autocomplete_css' );

/**
 * Attach the inline JS for initializing the messages autocomplete to the bp-jquery-autocomplete-fb script.
 */
function bboss_messages_autocomplete_init_jsblock() {
	$inline_js = <<<'JS'
	jQuery(document).ready(function() {
		var acfb = jQuery("ul.first").autoCompletefb({urlLookup: ajaxurl});

		jQuery('#send_message_form').submit( function() {
			var users = document.getElementById('send-to-usernames').className;
			document.getElementById('send-to-usernames').value = String(users);
		});
	});
JS;

	wp_add_inline_script( 'bp-jquery-autocomplete-fb', $inline_js );
}
