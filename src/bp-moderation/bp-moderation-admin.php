<?php
/**
 * BuddyBoss Moderation component admin screen.
 *
 * Registers the "Moderation" admin menu item and redirects to Settings 2.0.
 *
 * @since   BuddyBoss 1.5.6
 * @package BuddyBoss\Moderation
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register the Moderation component admin screen.
 *
 * The old WP_List_Table interface has been replaced by Settings 2.0.
 * This menu item now redirects to the Settings 2.0 Moderation feature page.
 *
 * @since BuddyBoss 1.5.6
 */
function bp_moderation_add_admin_menu() {

	// Add our screen — redirects to Settings 2.0 moderation page.
	$hook = add_submenu_page(
		'buddyboss-platform',
		esc_html__( 'Moderation', 'buddyboss' ),
		esc_html__( 'Moderation', 'buddyboss' ),
		'bp_moderate',
		'bp-moderation',
		'__return_empty_string'
	);

	// Redirect before headers are sent.
	add_action( "load-$hook", 'bp_moderation_admin_redirect' );
}

add_action( bp_core_admin_hook(), 'bp_moderation_add_admin_menu', 100 );

/**
 * Redirect the old Moderation admin page to Settings 2.0.
 *
 * @since BuddyBoss 3.0.0
 */
function bp_moderation_admin_redirect() {
	wp_safe_redirect( admin_url( 'admin.php?page=bb-settings&tab=moderation&panel=flagged_members' ) );
	exit;
}

/**
 * Add moderation component to custom menus array.
 *
 * Several BuddyPress components have top-level menu items in the Dashboard,
 * which all appear together in the middle of the Dashboard menu. This function
 * adds the Moderation page to the array of these menu items.
 *
 * @since BuddyBoss 1.5.6
 *
 * @param array $custom_menus The list of top-level BP menu items.
 *
 * @return array $custom_menus List of top-level BP menu items, with Moderation added.
 */
function bp_moderation_admin_menu_order( $custom_menus = array() ) {
	array_push( $custom_menus, 'bp-moderation' );

	return $custom_menus;
}

add_filter( 'bp_admin_menu_order', 'bp_moderation_admin_menu_order' );

/**
 * Highlight the "Moderation" admin menu item when on Settings 2.0 moderation pages.
 *
 * Outputs inline JS on all bb-settings pages. The script checks the current URL
 * for tab=moderation and swaps the WordPress `current` class accordingly.
 * Also observes URL changes from SPA navigation (history.replaceState).
 *
 * @since BuddyBoss 3.0.0
 */
function bp_moderation_highlight_admin_menu() {
	// Only output on bb-settings pages (covers all SPA-navigated tabs).
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( ! isset( $_GET['page'] ) || 'bb-settings' !== $_GET['page'] ) {
		return;
	}
	?>
	<script>
	( function() {
		var bbModLastUrl = '';

		function bbModHighlightMenu() {
			var params = new URLSearchParams( window.location.search );
			var panel = params.get( 'panel' );
			var isModeration = 'moderation' === params.get( 'tab' ) && ( 'flagged_members' === panel || 'reported_content' === panel );
			var menu = document.getElementById( 'adminmenu' );
			if ( ! menu ) return;
			var items = menu.querySelectorAll( '.toplevel_page_buddyboss-platform ul li' );
			for ( var i = 0; i < items.length; i++ ) {
				var a = items[ i ].querySelector( 'a' );
				if ( ! a ) continue;
				var href = a.getAttribute( 'href' ) || '';
				var isModerationItem = href.indexOf( 'bp-moderation' ) !== -1;
				var isSettingsItem = href.indexOf( 'bp-settings' ) !== -1;
				if ( isModeration && isModerationItem ) {
					items[ i ].classList.add( 'current' );
					a.classList.add( 'current' );
				} else if ( isModeration && items[ i ].classList.contains( 'current' ) ) {
					items[ i ].classList.remove( 'current' );
					a.classList.remove( 'current' );
				} else if ( ! isModeration && isModerationItem && items[ i ].classList.contains( 'current' ) ) {
					// Restore: remove Moderation highlight when leaving flagged/reported panels.
					items[ i ].classList.remove( 'current' );
					a.classList.remove( 'current' );
				} else if ( ! isModeration && isSettingsItem && ! items[ i ].classList.contains( 'current' ) ) {
					// Restore: re-add Settings highlight when leaving flagged/reported panels.
					items[ i ].classList.add( 'current' );
					a.classList.add( 'current' );
				}
			}
		}

		// Run on initial load.
		if ( 'loading' === document.readyState ) {
			document.addEventListener( 'DOMContentLoaded', function() {
				bbModLastUrl = window.location.href;
				bbModHighlightMenu();
			} );
		} else {
			bbModLastUrl = window.location.href;
			bbModHighlightMenu();
		}

		// Listen for popstate (browser back/forward).
		window.addEventListener( 'popstate', function() {
			if ( window.location.href !== bbModLastUrl ) {
				bbModLastUrl = window.location.href;
				bbModHighlightMenu();
			}
		} );

		// Patch history.replaceState to detect SPA navigation.
		var origReplaceState = history.replaceState;
		history.replaceState = function() {
			origReplaceState.apply( this, arguments );
			if ( window.location.href !== bbModLastUrl ) {
				bbModLastUrl = window.location.href;
				bbModHighlightMenu();
			}
		};
	} )();
	</script>
	<?php
}

add_action( 'admin_head', 'bp_moderation_highlight_admin_menu' );
