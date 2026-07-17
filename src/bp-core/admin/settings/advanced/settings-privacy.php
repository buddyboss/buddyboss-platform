<?php
/**
 * BuddyBoss Admin Settings - Advanced Privacy Panel.
 *
 * Registers fields for the Privacy side panel of the Advanced feature.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Advanced Privacy panel fields.
 *
 * @since BuddyBoss 3.0.0
 *
 * @return void
 */
function bb_advanced_register_privacy_fields() {

	$feature_id       = 'advanced';
	$panel_id         = 'privacy';
	$registration_url = esc_url( bb_get_feature_settings_url( 'registration' ) );

	// BuddyBoss App: detect if app is installed but Private App is disabled.
	$is_app_public = function_exists( 'bbapp_is_private_app_enabled' ) && ! bbapp_is_private_app_enabled();

	// =========================================================================
	// SECTION: Privacy
	// =========================================================================

	bb_register_feature_section(
		$feature_id,
		$panel_id,
		'advanced_privacy',
		array(
			'title'    => __( 'Privacy', 'buddyboss-platform' ),
			'order'    => 10,
			'help_url' => '636201',
		)
	);

	// Field 1: Private Website.
	bb_register_feature_field(
		$feature_id,
		$panel_id,
		'advanced_privacy',
		array(
			'name'              => 'bp-enable-private-network',
			'label'             => __( 'Private Website', 'buddyboss-platform' ),
			'description'       => __( 'Restrict site access to only logged-in members', 'buddyboss-platform' ),
			'help_text'         => sprintf(
				/* translators: %s: Registration settings link. */
				__( 'Login and <a href="%s">Registration</a> content will remain publicly visible.', 'buddyboss-platform' ),
				$registration_url
			),
			'type'              => 'toggle',
			'default'           => bp_enable_private_network(),
			'invert_value'      => true, // DB stores 1 = public network. Toggle shows "Restrict", so invert for display.
			'sanitize_callback' => 'absint',
			'order'             => 10,
		)
	);

	// Field 2: Public Website Content.
	bb_register_feature_field(
		$feature_id,
		$panel_id,
		'advanced_privacy',
		array(
			'name'              => 'bp-enable-private-network-public-content',
			'label'             => __( 'Public Website Content', 'buddyboss-platform' ),
			'type'              => 'textarea',
			'default'           => bp_enable_private_network_public_content(),
			'sanitize_callback' => 'bb_advanced_sanitize_public_content',
			'placeholder'       => __( 'Enter URLs or URI fragments', 'buddyboss-platform' ),
			'help_text'         => __( 'Enter URLs or URI fragments (e.g. /groups/) to remain publicly visible always. Enter one URL or URI per line.', 'buddyboss-platform' ),
			'conditional'       => array(
				'field' => 'bp-enable-private-network',
				'value' => false,
			),
			'order'             => 20,
		)
	);

	// Field 3: Private REST APIs.
	// When BuddyBoss App is active but Private App is disabled, the toggle is
	// forced OFF + disabled, and a notice explains why (matching legacy behavior).
	$rest_api_help_text = sprintf(
		/* translators: %s: Registration settings link. */
		__( 'Login and <a href="%s">Registration</a> APIs will remain publicly visible.', 'buddyboss-platform' ),
		$registration_url
	);

	if ( $is_app_public ) {
		$rest_api_help_text .= '<br><br>' . sprintf(
			/* translators: %s: BuddyBoss App settings link. */
			__( 'Your BuddyBoss App is currently public. To restrict access to REST APIs for logged-out members, please enable "Private App" in the %s.', 'buddyboss-platform' ),
			sprintf(
				'<a href="%s">%s</a>',
				esc_url( admin_url( 'admin.php?page=bbapp-settings' ) ),
				esc_html__( "BuddyBoss App's settings", 'buddyboss-platform' )
			)
		);
	}

	// Use raw DB value (0) as default — NOT bp_enable_private_rest_apis() which
	// may return a filtered/cached value from $bp->site_options.
	// When App is public, force both disabled and default to false.
	$rest_api_default  = $is_app_public ? 0 : absint( bp_get_option( 'bb-enable-private-rest-apis', 0 ) );
	$rest_api_disabled = $is_app_public;

	bb_register_feature_field(
		$feature_id,
		$panel_id,
		'advanced_privacy',
		array(
			'name'              => 'bb-enable-private-rest-apis',
			'label'             => __( 'Private Rest APIs', 'buddyboss-platform' ),
			'description'       => __( 'Restrict REST API access to only logged-in members', 'buddyboss-platform' ),
			'help_text'         => $rest_api_help_text,
			'type'              => 'toggle',
			'default'           => $rest_api_default,
			'sanitize_callback' => 'absint',
			'disabled'          => $rest_api_disabled,
			'order'             => 30,
		)
	);

	// Field 4: Public REST APIs.
	bb_register_feature_field(
		$feature_id,
		$panel_id,
		'advanced_privacy',
		array(
			'name'              => 'bb-enable-private-rest-apis-public-content',
			'label'             => __( 'Public Rest APIs', 'buddyboss-platform' ),
			'type'              => 'textarea',
			'default'           => bb_enable_private_rest_apis_public_content(),
			'sanitize_callback' => 'bb_advanced_sanitize_public_content',
			'placeholder'       => __( 'Enter REST API endpoint URLs', 'buddyboss-platform' ),
			'help_text'         => __( 'Enter REST API endpoint URLs or URI fragments (e.g. wp-json/wp/v2/pages/&lt;id&gt;) to remain publicly visible always. Enter one URL or URI per line.', 'buddyboss-platform' ),
			'disabled'          => $is_app_public,
			'conditional'       => array(
				'field' => 'bb-enable-private-rest-apis',
				'value' => true,
			),
			'order'             => 40,
		)
	);

	// Field 5: Private RSS Feeds.
	bb_register_feature_field(
		$feature_id,
		$panel_id,
		'advanced_privacy',
		array(
			'name'              => 'bb-enable-private-rss-feeds',
			'label'             => __( 'Private RSS Feeds', 'buddyboss-platform' ),
			'description'       => __( 'Restrict RSS feed access to only logged-in members', 'buddyboss-platform' ),
			'help_text'         => sprintf(
				/* translators: %s: Registration settings link. */
				__( 'Login and <a href="%s">Registration</a> content will remain publicly visible.', 'buddyboss-platform' ),
				$registration_url
			),
			'type'              => 'toggle',
			'default'           => bp_enable_private_rss_feeds(),
			'sanitize_callback' => 'absint',
			'order'             => 50,
		)
	);

	// Field 6: Public RSS Feeds.
	bb_register_feature_field(
		$feature_id,
		$panel_id,
		'advanced_privacy',
		array(
			'name'              => 'bb-enable-private-rss-feeds-public-content',
			'label'             => __( 'Public RSS Feeds', 'buddyboss-platform' ),
			'type'              => 'textarea',
			'default'           => bb_enable_private_rss_feeds_public_content(),
			'sanitize_callback' => 'bb_advanced_sanitize_public_content',
			'placeholder'       => __( 'Enter public RSS feed URLs', 'buddyboss-platform' ),
			'help_text'         => __( 'Enter RSS feed URLs or URI fragments (e.g. /post-name/feed/) to remain publicly visible always. Enter one URL or URI per line.', 'buddyboss-platform' ),
			'conditional'       => array(
				'field' => 'bb-enable-private-rss-feeds',
				'value' => true,
			),
			'order'             => 60,
		)
	);
}
