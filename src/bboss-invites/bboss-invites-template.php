<?php
/**
 * BuddyBoss Invites Template Functions.
 *
 * @package BuddyBoss\Invites\Templates
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Output the invites component slug.
 *
 * @since BuddyBoss 1.0.0
 */
function bboss_invites_slug() {
	echo esc_attr( bboss_get_invites_slug() );
}
	/**
	 * Return the invites component slug.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @return string
	 */
function bboss_get_invites_slug() {

	/**
	 * Filters the invites component slug.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param string $slug Invites component slug.
	 */
	return apply_filters( 'bp_get_invites_slug', bboss()->invites->slug );
}

/**
 * Output the invites component root slug.
 *
 * @since BuddyBoss 1.0.0
 */
function bboss_invites_root_slug() {
	echo esc_attr( bboss_get_invites_root_slug() );
}
	/**
	 * Return the invites component root slug.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @return string
	 */
function bboss_get_invites_root_slug() {

	/**
	 * Filters the invites component root slug.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param string $root_slug Invites component root slug.
	 */
	return apply_filters( 'bp_get_invites_root_slug', bboss()->invites->root_slug );
}

// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename names of this file's symbols (decision 0013): declared
// here, guarded on the renamed declaration, so each old name exists exactly when the new one does.
if ( function_exists( 'bboss_get_invites_root_slug' ) && ! function_exists( 'bp_get_invites_root_slug' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_get_invites_root_slug()}.
	 *
	 * @deprecated Use bboss_get_invites_root_slug() instead.
	 */
	function bp_get_invites_root_slug() {
		return bboss_get_invites_root_slug();
	}
}
if ( function_exists( 'bboss_get_invites_slug' ) && ! function_exists( 'bp_get_invites_slug' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_get_invites_slug()}.
	 *
	 * @deprecated Use bboss_get_invites_slug() instead.
	 */
	function bp_get_invites_slug() {
		return bboss_get_invites_slug();
	}
}
if ( function_exists( 'bboss_invites_root_slug' ) && ! function_exists( 'bp_invites_root_slug' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_invites_root_slug()}.
	 *
	 * @deprecated Use bboss_invites_root_slug() instead.
	 */
	function bp_invites_root_slug() {
		return bboss_invites_root_slug();
	}
}
if ( function_exists( 'bboss_invites_slug' ) && ! function_exists( 'bp_invites_slug' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_invites_slug()}.
	 *
	 * @deprecated Use bboss_invites_slug() instead.
	 */
	function bp_invites_slug() {
		return bboss_invites_slug();
	}
}
// @bbwt-com-end compat-layer
