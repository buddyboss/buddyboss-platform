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

/**
 * Formerly enqueued the legacy autocomplete CSS for composing a message.
 *
 * @since BuddyPress 1.0.0
 * @deprecated BuddyBoss 3.0.0 The legacy autocomplete libraries were removed; BP Nouveau uses jQuery UI autocomplete.
 */
function bboss_messages_add_autocomplete_css() {
}

/**
 * Formerly attached the inline JS that initialised the legacy autocomplete.
 *
 * @since BuddyPress 1.0.0
 * @deprecated BuddyBoss 3.0.0 The legacy autocomplete libraries were removed; BP Nouveau uses jQuery UI autocomplete.
 */
function bboss_messages_autocomplete_init_jsblock() {
}
