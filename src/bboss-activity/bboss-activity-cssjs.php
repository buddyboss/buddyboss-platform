<?php
/**
 * Activity component CSS/JS
 *
 * @package BuddyBoss\Activity
 * @since BuddyPress 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Enqueue @mentions JS.
 *
 * @since BuddyPress 2.1.0
 */
function bboss_activity_mentions_script() {
	if ( ! bboss_activity_maybe_load_mentions_scripts() ) {
		return;
	}

	// Special handling for New/Edit screens in wp-admin.
	if ( is_admin() ) {
		if (
			! get_current_screen() ||
			! in_array( get_current_screen()->base, array( 'page', 'post' ) ) ||
			! post_type_supports( get_current_screen()->post_type, 'editor' ) ) {
			return;
		}
	}

	$min = bboss_core_get_minified_asset_suffix();

	wp_enqueue_script( 'bp-mentions', bboss()->plugin_url . "bboss-core/js/mentions{$min}.js", array( 'jquery', 'jquery-atwho' ), bboss_get_version(), true );
	wp_enqueue_style( 'bp-mentions-css', bboss()->plugin_url . "bboss-core/css/mentions{$min}.css", array(), bboss_get_version() );

	wp_style_add_data( 'bp-mentions-css', 'rtl', true );
	if ( $min ) {
		wp_style_add_data( 'bp-mentions-css', 'suffix', $min );
	}

	wp_localize_script( 'bp-mentions', 'BP_Mentions_Options', bboss_at_mention_default_options() );

	// If the script has been enqueued, let's attach our mentions TinyMCE init callback.
	add_filter( 'tiny_mce_before_init', 'bp_add_mentions_on_tinymce_init', 10, 2 );

	/**
	 * Fires at the end of the Activity Mentions script.
	 *
	 * This is the hook where BP components can add their own prefetched results
	 * friends to the page for quicker @mentions lookups.
	 *
	 * @since BuddyPress 2.1.0
	 */
	do_action( 'bp_activity_mentions_prime_results' );
}
add_action( 'bp_enqueue_scripts', 'bp_activity_mentions_script' );
add_action( 'bp_admin_enqueue_scripts', 'bp_activity_mentions_script' );

/**
 * Bind the mentions listener to a wp_editor instance when TinyMCE initializes.
 *
 * @since BuddyPress 2.3.3
 *
 * @param array  $settings   An array with TinyMCE config.
 * @param string $editor_id Unique editor identifier, e.g. 'content'.
 * @return array  $mceInit   An array with TinyMCE config.
 */
function bboss_add_mentions_on_tinymce_init( $settings, $editor_id ) {
	// We only apply the mentions init to the visual post editor in the WP dashboard.
	if ( 'content' === $editor_id ) {
		$settings['init_instance_callback'] = 'window.bp.mentions.tinyMCEinit';
	}

	return $settings;
}
