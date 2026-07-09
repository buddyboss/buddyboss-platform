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

// Admin settings registration (admin, AJAX, REST and WP-CLI contexts).
if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
	require_once __DIR__ . '/admin/settings.php';
}
