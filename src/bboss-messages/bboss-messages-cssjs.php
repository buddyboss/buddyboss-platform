<?php
/**
 * BuddyBoss Messages CSS and JS.
 *
 * The legacy "Autocomplete - jQuery plugin 1.0 Beta" (2007) and its helpers
 * (autocompletefb, bgiframe, dimensions) were only enqueued by the BP Legacy
 * template pack's compose screen. BP Nouveau, the only template pack, removed
 * that action and uses jQuery UI autocomplete, so the libraries were dead code
 * and have been dropped (3.0.0). The three functions stay as no-ops so code
 * that hooks, removes or checks them keeps working.
 *
 * @package BuddyBoss\Messages\Scripts
 * @since BuddyPress 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Formerly enqueued the legacy autocomplete JS for composing a message.
 *
 * @since BuddyPress 1.0.0
 * @deprecated BuddyBoss 3.0.0 The legacy autocomplete libraries were removed; BP Nouveau uses jQuery UI autocomplete.
 */
function bboss_messages_add_autocomplete_js() {
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_messages_add_autocomplete_js' ) && ! function_exists( 'messages_add_autocomplete_js' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_messages_add_autocomplete_js()}.
	 *
	 * @deprecated Use bboss_messages_add_autocomplete_js() instead.
	 */
	function messages_add_autocomplete_js() {
		return bboss_messages_add_autocomplete_js();
	}
}
// @bbwt-com-end compat-layer


/**
 * Formerly enqueued the legacy autocomplete CSS for composing a message.
 *
 * @since BuddyPress 1.0.0
 * @deprecated BuddyBoss 3.0.0 The legacy autocomplete libraries were removed; BP Nouveau uses jQuery UI autocomplete.
 */
function bboss_messages_add_autocomplete_css() {
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_messages_add_autocomplete_css' ) && ! function_exists( 'messages_add_autocomplete_css' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_messages_add_autocomplete_css()}.
	 *
	 * @deprecated Use bboss_messages_add_autocomplete_css() instead.
	 */
	function messages_add_autocomplete_css() {
		return bboss_messages_add_autocomplete_css();
	}
}
// @bbwt-com-end compat-layer


/**
 * Formerly attached the inline JS that initialised the legacy autocomplete.
 *
 * @since BuddyPress 1.0.0
 * @deprecated BuddyBoss 3.0.0 The legacy autocomplete libraries were removed; BP Nouveau uses jQuery UI autocomplete.
 */
function bboss_messages_autocomplete_init_jsblock() {
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_messages_autocomplete_init_jsblock' ) && ! function_exists( 'messages_autocomplete_init_jsblock' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_messages_autocomplete_init_jsblock()}.
	 *
	 * @deprecated Use bboss_messages_autocomplete_init_jsblock() instead.
	 */
	function messages_autocomplete_init_jsblock() {
		return bboss_messages_autocomplete_init_jsblock();
	}
}
// @bbwt-com-end compat-layer

