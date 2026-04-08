<?php
/**
 * BuddyBoss Admin Placeholder Features Provider.
 *
 * Fetches placeholder feature data from a remote S3 endpoint, caches it
 * using a 6-hour transient, and injects unregistered placeholder objects
 * into the bb_admin_get_features AJAX response.
 *
 * Cache can be cleared by visiting any admin page with ?bb_clear_placeholder_cache=1
 *
 * @package BuddyBoss\Core\Administration
 * @since   BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;


/**
 * Fetch and cache the placeholder features catalog from S3.
 *
 * Returns the decoded JSON data array on success, or false on failure.
 * Uses a WordPress transient for caching to avoid hitting S3 on every request.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return array|false Decoded catalog data with 'items' key, or false on failure.
 */
function bb_get_placeholder_features_data() {
	$data = get_transient( 'bb_placeholder_features_data' );

	if ( false !== $data ) {
		return $data;
	}

	$response = wp_remote_get(
		'https://bb-features-marketing.s3.amazonaws.com/bb-features.json',
		array(
			'timeout' => 10,
		)
	);

	if ( is_wp_error( $response ) ) {
		return false;
	}

	$code = wp_remote_retrieve_response_code( $response );
	if ( 200 !== $code ) {
		return false;
	}

	$body = wp_remote_retrieve_body( $response );
	$data = json_decode( $body, true );

	if ( ! is_array( $data ) || empty( $data['items'] ) || ! is_array( $data['items'] ) ) {
		return false;
	}

	set_transient( 'bb_placeholder_features_data', $data, 6 * HOUR_IN_SECONDS );

	return $data;
}

/**
 * Determine the plugin status for a placeholder feature.
 *
 * Checks whether the plugin is in the user's addon plan, installed, and/or active.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param array $item Catalog item with 'id' and optional 'plugin_file'.
 * @return string One of: 'not_in_plan', 'not_installed', 'installed_inactive'.
 */
function bb_get_placeholder_plugin_status( $item ) {
	$plugin_file = isset( $item['plugin_file'] ) ? $item['plugin_file'] : '';

	if ( empty( $plugin_file ) ) {
		return 'not_in_plan';
	}

	// Step 1: Check if this product is in the user's addon plan.
	// This must come first — if not in plan, always show upgrade badge
	// regardless of install status.
	$in_plan = false;
	if ( class_exists( '\\BuddyBoss\\Core\\Admin\\Mothership\\BB_Addons_Manager' ) ) {
		$plugin_slug = dirname( $plugin_file );
		$product     = \BuddyBoss\Core\Admin\Mothership\BB_Addons_Manager::checkProductBySlug( $plugin_slug );
		$in_plan     = ! empty( $product );
	}

	if ( ! $in_plan ) {
		return 'not_in_plan';
	}

	// Step 2: Product is in plan — check install/active status.
	if ( ! function_exists( 'is_plugin_active' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	$plugin_path  = WP_PLUGIN_DIR . '/' . $plugin_file;
	$is_installed = file_exists( $plugin_path );

	if ( ! $is_installed ) {
		return 'not_installed';
	}

	return 'installed_inactive';
}

/**
 * Append placeholder features to the AJAX features response.
 *
 * Fetches the remote JSON catalog (cached via transient), deduplicates
 * against BB_Feature_Registry, and returns the augmented features array.
 * Placeholders are only appended for product IDs not already registered
 * in the registry, ensuring no duplicate cards appear when the
 * corresponding plugin is installed and active.
 *
 * Each placeholder includes a `plugin_status` field:
 * - 'not_in_plan': User doesn't have this product — show upgrade badge.
 * - 'not_installed': User has the plan but plugin not installed — show "Install & Activate".
 * - 'installed_inactive': Plugin installed but not activated — show "Activate".
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param array $features Existing formatted feature objects from AJAX handler.
 * @return array Features array with placeholder objects appended for unregistered products.
 */
function bb_admin_inject_placeholder_features( $features ) {
	$data = bb_get_placeholder_features_data();

	if ( false === $data ) {
		return $features;
	}

	if ( ! function_exists( 'bb_feature_registry' ) ) {
		return $features;
	}

	$registry = bb_feature_registry();

	foreach ( $data['items'] as $item ) {
		if ( empty( $item['id'] ) ) {
			continue;
		}

		// Skip if already registered — deduplication.
		// bb_get_feature() returns null for IDs not in the registry.
		if ( null !== $registry->bb_get_feature( $item['id'] ) ) {
			continue;
		}

		$plugin_status = bb_get_placeholder_plugin_status( $item );
		$plugin_file   = isset( $item['plugin_file'] ) ? $item['plugin_file'] : '';

		$plugin_slug = ! empty( $plugin_file ) ? dirname( $plugin_file ) : '';

		$features[] = array(
			'id'                => sanitize_key( $item['id'] ),
			'label'             => isset( $item['label'] ) ? sanitize_text_field( $item['label'] ) : '',
			'description'       => isset( $item['description'] ) ? sanitize_text_field( $item['description'] ) : '',
			'category'          => isset( $item['category'] ) ? sanitize_key( $item['category'] ) : 'add-ons',
			'license_tier'      => isset( $item['upgrade_tier'] ) ? sanitize_key( $item['upgrade_tier'] ) : 'plus',
			'status'            => 'inactive',
			'available'         => false,
			'required'          => false,
			'settings_route'    => '',
			'icon'              => isset( $item['icon'] ) ? $item['icon'] : null,
			'is_placeholder'    => true,
			'plugin_status'     => $plugin_status,
			'plugin_slug'       => $plugin_slug,
			'upgrade_tier'      => isset( $item['upgrade_tier'] ) ? sanitize_key( $item['upgrade_tier'] ) : 'plus',
			'upgrade_url'       => isset( $item['upgrade_url'] ) ? esc_url_raw( $item['upgrade_url'] ) : '',
			'upgrade_image_url' => isset( $item['upgrade_image_url'] ) ? esc_url_raw( $item['upgrade_image_url'] ) : '',
			'order'             => isset( $item['order'] ) ? (int) $item['order'] : 999,
		);
	}

	return $features;
}

/**
 * Hook the placeholder feature provider into the features AJAX response filter.
 *
 * @since BuddyBoss [BBVERSION]
 */
add_filter( 'bb_admin_features_response', 'bb_admin_inject_placeholder_features' );

/**
 * Mark DRM-locked features in the AJAX features response.
 *
 * Each addon plugin declares its DRM product slug when registering a feature
 * via `bb_register_feature( 'feature-id', array( 'drm_product_slug' => 'buddyboss-gamification' ) )`.
 *
 * This function checks each feature for a `drm_product_slug` in the registry,
 * then checks if that addon is DRM-locked. If locked, adds `is_drm_locked`,
 * `upgrade_tier`, and `upgrade_url` to the feature response. This disables
 * the feature in the admin UI only — the actual option value remains unchanged.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param array $features Formatted feature objects from the AJAX handler.
 * @return array Features array with DRM lock data added where applicable.
 */
function bb_admin_mark_drm_locked_features( $features ) {
	if ( ! class_exists( '\\BuddyBoss\\Core\\Admin\\DRM\\BB_DRM_Registry' ) ) {
		return $features;
	}

	if ( ! function_exists( 'bb_feature_registry' ) ) {
		return $features;
	}

	$registry = bb_feature_registry();

	// Build a lookup from the S3 catalog for upgrade_tier/url/image data.
	$catalog_lookup = array();
	$catalog_data   = bb_get_placeholder_features_data();
	if ( false !== $catalog_data ) {
		foreach ( $catalog_data['items'] as $item ) {
			if ( ! empty( $item['id'] ) ) {
				$catalog_lookup[ $item['id'] ] = $item;
			}
		}
	}

	foreach ( $features as &$feature ) {
		// Skip placeholders — they already have their own badge.
		if ( ! empty( $feature['is_placeholder'] ) ) {
			continue;
		}

		$feature_id = isset( $feature['id'] ) ? $feature['id'] : '';
		if ( empty( $feature_id ) ) {
			continue;
		}

		// Look up the feature's drm_product_slug from the registry.
		$registered = $registry->bb_get_feature( $feature_id );
		if ( ! $registered || empty( $registered['drm_product_slug'] ) ) {
			continue;
		}

		$product_slug = $registered['drm_product_slug'];

		if ( ! \BuddyBoss\Core\Admin\DRM\BB_DRM_Registry::should_lock_addon_features( $product_slug ) ) {
			continue;
		}

		// Feature's addon is DRM-locked — add lock data.
		$feature['is_drm_locked'] = true;

		// Pull upgrade data from the S3 catalog if available, fallback to feature's own data.
		if ( isset( $catalog_lookup[ $feature_id ] ) ) {
			$catalog_item                 = $catalog_lookup[ $feature_id ];
			$feature['upgrade_tier']      = isset( $catalog_item['upgrade_tier'] ) ? $catalog_item['upgrade_tier'] : $feature['license_tier'];
			$feature['upgrade_url']       = isset( $catalog_item['upgrade_url'] ) ? $catalog_item['upgrade_url'] : 'https://www.buddyboss.com/pricing/';
			$feature['upgrade_image_url'] = isset( $catalog_item['upgrade_image_url'] ) ? $catalog_item['upgrade_image_url'] : '';
		} else {
			// No S3 catalog entry — use feature's own license_tier.
			$feature['upgrade_tier']      = isset( $feature['license_tier'] ) ? $feature['license_tier'] : 'pro';
			$feature['upgrade_url']       = 'https://www.buddyboss.com/pricing/';
			$feature['upgrade_image_url'] = '';
		}
	}

	unset( $feature );

	return $features;
}

/**
 * Hook DRM lock detection into the features AJAX response.
 * Runs after placeholder injection (priority 20) so placeholders are already in place.
 *
 * @since BuddyBoss [BBVERSION]
 */
add_filter( 'bb_admin_features_response', 'bb_admin_mark_drm_locked_features', 20 );

/**
 * Clear the placeholder features transient cache via query parameter.
 *
 * Visit any admin page with ?bb_clear_placeholder_cache=1 to force a fresh
 * fetch from S3 on the next AJAX request.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_maybe_clear_placeholder_features_cache() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only cache clear, admin-only.
	if ( ! empty( $_GET['bb_clear_placeholder_cache'] ) && current_user_can( 'manage_options' ) ) {
		delete_transient( 'bb_placeholder_features_data' );
	}
}
add_action( 'admin_init', 'bb_maybe_clear_placeholder_features_cache' );

/**
 * Clear the placeholder features cache when the license status changes.
 *
 * Hooks into the existing {plugin_id}_license_status_changed action fired by
 * BB_Mothership_Loader::handle_license_status_change(). This ensures that
 * plugin_status is re-evaluated when the license is activated or deactivated,
 * so the correct card state (upgrade badge vs install/activate button) is
 * shown immediately.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_clear_placeholder_cache_on_license_change() {
	delete_transient( 'bb_placeholder_features_data' );
}

/**
 * Register the license change hook with the dynamic plugin ID.
 *
 * Must run after the Mothership loader initializes so the plugin ID is available.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_register_placeholder_cache_clear_hooks() {
	if ( ! class_exists( '\\BuddyBoss\\Core\\Admin\\Mothership\\BB_Plugin_Connector' ) ) {
		return;
	}

	$plugin_id = get_option( 'buddyboss_dynamic_plugin_id', '' );
	if ( empty( $plugin_id ) && defined( 'PLATFORM_EDITION' ) ) {
		$plugin_id = PLATFORM_EDITION;
	}

	if ( ! empty( $plugin_id ) ) {
		add_action( $plugin_id . '_license_status_changed', 'bb_clear_placeholder_cache_on_license_change' );
	}
}
add_action( 'admin_init', 'bb_register_placeholder_cache_clear_hooks' );
