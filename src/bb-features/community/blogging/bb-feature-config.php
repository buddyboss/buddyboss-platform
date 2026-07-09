<?php
/**
 * Blogs feature configuration.
 *
 * Registers the free-tier "Blogs" feature (feature id `blogging`) with the
 * BuddyBoss Settings 2.0 feature registry. Auto-discovered by
 * BB_Feature_Autoloader via bb-features/community/blogging/bb-feature-config.php.
 *
 * NOTE: The feature id is `blogging`, NOT `blogs` — the `blogs` namespace is
 * owned by the BuddyPress multisite Sites component (bp-blogs).
 *
 * @since   BuddyBoss [BBVERSION]
 * @package BuddyBoss\Blogging
 */

defined( 'ABSPATH' ) || exit;

bb_register_feature(
	'blogging',
	array(
		'label'              => __( 'Blogs', 'buddyboss' ),
		'description'        => __( "Easily manage your community's general, privacy, and SEO settings.", 'buddyboss' ),
		'icon'               => array(
			'type'  => 'font',
			'class' => 'bb-icons-rl bb-icons-rl-book-open',
		),
		'license_tier'       => 'free',
		'category'           => 'community',
		'standalone'         => true,
		'settings_route'     => '/settings/blogging',
		'order'              => 155,
		'php_loader'         => function () {
			require_once __DIR__ . '/loader.php';
		},
		'is_active_callback' => function () {
			$active_features = bp_get_option( 'bb-active-features', array() );

			return ! empty( $active_features['blogging'] );
		},
	)
);

/**
 * Seed Blogs feature option defaults on activation.
 *
 * The Settings 2.0 AJAX handler reads toggle_list option_prefix values with
 * a hardcoded default of 1, so mixed defaults must exist as real options.
 * Only missing options are written; existing values are never overwritten.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $feature_id The activated feature ID.
 *
 * @return void
 */
function bb_blog_seed_default_options( $feature_id ) {
	if ( 'blogging' !== $feature_id ) {
		return;
	}

	$defaults = array(
		'bb_blog_social_link_facebook' => 1,
		'bb_blog_social_link_linkedin' => 1,
		'bb_blog_social_link_x'        => 0,
		'bb_blog_social_link_whatsapp' => 0,
		'bb_blog_social_link_email'    => 0,
	);

	foreach ( $defaults as $option_name => $default_value ) {
		if ( '__bb_blog_missing__' === bp_get_option( $option_name, '__bb_blog_missing__' ) ) {
			bp_update_option( $option_name, $default_value );
		}
	}
}
add_action( 'bb_feature_activated', 'bb_blog_seed_default_options' );

// Admin settings registration (admin, AJAX, REST and WP-CLI contexts).
if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
	require_once __DIR__ . '/admin/settings.php';
}
