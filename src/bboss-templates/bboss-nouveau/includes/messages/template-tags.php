<?php
/**
 * Messages template tags
 *
 * @since BuddyPress 3.0.0
 * @version 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Fire specific hooks into the private messages template.
 *
 * @since BuddyPress 3.0.0
 *
 * @param string $when   Optional. Either 'before' or 'after'.
 * @param string $suffix Optional. Use it to add terms at the end of the hook name.
 */
function bboss_nouveau_messages_hook( $when = '', $suffix = '' ) {
	$hook = array( 'bp' );

	if ( $when ) {
		$hook[] = $when;
	}

	// It's a message hook
	$hook[] = 'message';

	if ( $suffix ) {
		if ( 'compose_content' === $suffix ) {
			$hook[2] = 'messages';
		}

		$hook[] = $suffix;
	}

	bboss_nouveau_hook( $hook );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_nouveau_messages_hook' ) && ! function_exists( 'bp_nouveau_messages_hook' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_nouveau_messages_hook()}.
	 *
	 * @deprecated Use bboss_nouveau_messages_hook() instead.
	 */
	function bp_nouveau_messages_hook( $when = '', $suffix = '' ) {
		return bboss_nouveau_messages_hook( $when, $suffix );
	}
}
// @bbwt-com-end compat-layer


/**
 * Load the new Messages User Interface
 *
 * @since BuddyPress 3.0.0
 */
function bboss_nouveau_messages_member_interface() {
	/**
	 * Fires before the member messages content.
	 *
	 * @since BuddyPress 1.2.0
	 */
	do_action( 'bp_before_member_messages_content' );

	// Load the Private messages UI
	bboss_get_template_part( 'common/js-templates/messages/index' );

	/**
	 * Fires after the member messages content.
	 *
	 * @since BuddyPress 1.2.0
	 */
	do_action( 'bp_after_member_messages_content' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_nouveau_messages_member_interface' ) && ! function_exists( 'bp_nouveau_messages_member_interface' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_nouveau_messages_member_interface()}.
	 *
	 * @deprecated Use bboss_nouveau_messages_member_interface() instead.
	 */
	function bp_nouveau_messages_member_interface() {
		return bboss_nouveau_messages_member_interface();
	}
}
// @bbwt-com-end compat-layer


 /**
 * Output the Member's messages search form.
 *
 * @since BuddyPress 3.0.0
 * @since BuddyPress 3.2.0 Move the function into Template Tags and use a template part.
 */
function bboss_nouveau_message_search_form() {
    $search_form_html = bboss_buffer_template_part( 'common/js-templates/messages/search-form', null, false );

	/**
	 * Filters the private message component search form.
	 *
	 * @since BuddyPress 2.2.0
	 *
	 * @param string $search_form_html HTML markup for the message search form.
	 */
	echo apply_filters( 'bp_message_search_form', $search_form_html ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- buffered search-form template HTML (contains <form>/<input> controls); wp_kses_post would strip the form controls.
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_nouveau_message_search_form' ) && ! function_exists( 'bp_nouveau_message_search_form' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_nouveau_message_search_form()}.
	 *
	 * @deprecated Use bboss_nouveau_message_search_form() instead.
	 */
	function bp_nouveau_message_search_form() {
		return bboss_nouveau_message_search_form();
	}
}
// @bbwt-com-end compat-layer

