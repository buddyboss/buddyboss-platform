<?php
/**
 * Blogs feature settings sanitize callbacks.
 *
 * @since   BuddyBoss [BBVERSION]
 * @package BuddyBoss\Blogging
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sanitize the blog social links toggle list.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param array $value Raw toggle list value keyed by platform.
 *
 * @return array Sanitized platform => 0|1 map limited to known platforms.
 */
function bb_blog_sanitize_social_links( $value ) {
	$allowed = array( 'facebook', 'linkedin', 'x', 'whatsapp', 'email' );
	$clean   = array();

	if ( ! is_array( $value ) ) {
		$value = array();
	}

	foreach ( $allowed as $platform ) {
		$clean[ $platform ] = empty( $value[ $platform ] ) ? 0 : 1;
	}

	/**
	 * Filter the sanitized blog social links.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $clean Sanitized platform map.
	 * @param array $value Raw submitted value.
	 */
	return apply_filters( 'bb_blog_sanitize_social_links', $clean, $value );
}

/**
 * Whether the Blog Page Settings are available on this site.
 *
 * The Page Settings (social links, related posts, author bio) render only
 * through the BuddyBoss Theme or ReadyLaunch blog templates, so the fields
 * are disabled when neither is available.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return bool
 */
function bb_blog_page_settings_is_available() {
	$available = function_exists( 'buddyboss_theme_get_option' ) || bb_is_readylaunch_enabled();

	/**
	 * Filter whether the Blog Page Settings are available.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param bool $available Whether a supported renderer (BuddyBoss Theme or
	 *                        ReadyLaunch) is available.
	 */
	return (bool) apply_filters( 'bb_blog_page_settings_is_available', $available );
}

/**
 * The blog social share platform keys and their default enabled state.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return int[] Platform key => default 0|1.
 */
function bb_blog_social_link_platforms() {
	return array(
		'facebook' => 1,
		'linkedin' => 1,
		'x'        => 0,
		'whatsapp' => 0,
		'email'    => 0,
	);
}

/**
 * Map of platform blog options to their buddyboss-theme Redux equivalents.
 *
 * Covers the 1:1 boolean options only. The social links list maps to the
 * theme's single `blog_share_box` switch and is handled separately.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return string[] Platform option name => theme Redux option key.
 */
function bb_blog_theme_sync_map() {
	return array(
		'bb_blog_related_posts' => 'blog_related_switch',
		'bb_blog_author_bio'    => 'blog_author_box',
	);
}

/**
 * Re-entrancy guard shared by both sync directions.
 *
 * Prevents the platform->theme writer from re-triggering the theme->platform
 * listener (and vice versa) within the same request.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param bool|null $set Optional. New guard state.
 *
 * @return bool Current guard state.
 */
function bb_blog_theme_sync_in_progress( $set = null ) {
	static $in_progress = false;

	if ( null !== $set ) {
		$in_progress = (bool) $set;
	}

	return $in_progress;
}

/**
 * Sync a platform blog option change into the buddyboss-theme Redux options.
 *
 * Related Posts and Author Bio map 1:1. Any enabled social link maps to the
 * theme's single `blog_share_box` switch (on when at least one platform is
 * enabled, off when none are).
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $option Option name.
 * @param mixed  $value  New option value.
 *
 * @return void
 */
function bb_blog_sync_platform_option_to_theme( $option, $value ) {
	if ( bb_blog_theme_sync_in_progress() || ! function_exists( 'buddyboss_theme_get_option' ) ) {
		return;
	}

	$map       = bb_blog_theme_sync_map();
	$is_social = 0 === strpos( $option, 'bb_blog_social_link_' );

	if ( ! isset( $map[ $option ] ) && ! $is_social ) {
		return;
	}

	$theme_options = get_option( 'buddyboss_theme_options', array() );

	if ( ! is_array( $theme_options ) ) {
		return;
	}

	if ( $is_social ) {
		$theme_key = 'blog_share_box';
		$enabled   = false;

		foreach ( bb_blog_social_link_platforms() as $platform => $default ) {
			if ( (bool) bp_get_option( 'bb_blog_social_link_' . $platform, $default ) ) {
				$enabled = true;
				break;
			}
		}
	} else {
		$theme_key = $map[ $option ];
		$enabled   = (bool) $value;
	}

	$current = isset( $theme_options[ $theme_key ] ) ? (bool) $theme_options[ $theme_key ] : null;

	if ( $current === $enabled ) {
		return;
	}

	$theme_options[ $theme_key ] = $enabled ? '1' : '0';

	bb_blog_theme_sync_in_progress( true );
	update_option( 'buddyboss_theme_options', $theme_options );
	bb_blog_theme_sync_in_progress( false );
}

/**
 * `updated_option` bridge into the platform->theme sync.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $option    Option name.
 * @param mixed  $old_value Previous value.
 * @param mixed  $value     New value.
 *
 * @return void
 */
function bb_blog_sync_platform_option_updated( $option, $old_value, $value ) {
	bb_blog_sync_platform_option_to_theme( $option, $value );
}
add_action( 'updated_option', 'bb_blog_sync_platform_option_updated', 10, 3 );

/**
 * `added_option` bridge into the platform->theme sync.
 *
 * First-time writes fire `added_option` instead of `updated_option`.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $option Option name.
 * @param mixed  $value  Option value.
 *
 * @return void
 */
function bb_blog_sync_platform_option_added( $option, $value ) {
	bb_blog_sync_platform_option_to_theme( $option, $value );
}
add_action( 'added_option', 'bb_blog_sync_platform_option_added', 10, 2 );

/**
 * Sync buddyboss-theme Redux blog option changes back into platform options.
 *
 * Only keys whose value actually changed are written, so an unrelated theme
 * options save never stomps the platform's per-network social selection.
 * Turning the theme share box on when every network is off restores the
 * Facebook + LinkedIn defaults; turning it off disables every network.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $old_value Previous buddyboss_theme_options value.
 * @param mixed $value     New buddyboss_theme_options value.
 *
 * @return void
 */
function bb_blog_sync_theme_options_to_platform( $old_value, $value ) {
	if ( bb_blog_theme_sync_in_progress() || ! is_array( $value ) ) {
		return;
	}

	$old_value = is_array( $old_value ) ? $old_value : array();

	bb_blog_theme_sync_in_progress( true );

	foreach ( bb_blog_theme_sync_map() as $platform_option => $theme_key ) {
		$new = isset( $value[ $theme_key ] ) ? (bool) $value[ $theme_key ] : null;
		$old = isset( $old_value[ $theme_key ] ) ? (bool) $old_value[ $theme_key ] : null;

		if ( null === $new || $new === $old ) {
			continue;
		}

		bp_update_option( $platform_option, $new ? 1 : 0 );
	}

	$new_share = isset( $value['blog_share_box'] ) ? (bool) $value['blog_share_box'] : null;
	$old_share = isset( $old_value['blog_share_box'] ) ? (bool) $old_value['blog_share_box'] : null;

	if ( null !== $new_share && $new_share !== $old_share ) {
		$platforms = bb_blog_social_link_platforms();

		if ( ! $new_share ) {
			foreach ( $platforms as $platform => $default ) {
				bp_update_option( 'bb_blog_social_link_' . $platform, 0 );
			}
		} else {
			$any_enabled = false;

			foreach ( $platforms as $platform => $default ) {
				if ( (bool) bp_get_option( 'bb_blog_social_link_' . $platform, $default ) ) {
					$any_enabled = true;
					break;
				}
			}

			if ( ! $any_enabled ) {
				foreach ( $platforms as $platform => $default ) {
					bp_update_option( 'bb_blog_social_link_' . $platform, $default );
				}
			}
		}
	}

	bb_blog_theme_sync_in_progress( false );
}
add_action( 'update_option_buddyboss_theme_options', 'bb_blog_sync_theme_options_to_platform', 10, 2 );
