<?php
/**
 * Bootstrap for {@see BB_S3_Image_Offload}.
 *
 * Loaded from {@see bp_setup_core()} so the output-buffer hooks are wired
 * before `template_redirect`/`admin_init` fire. Mirrors the loader pattern
 * used by `bb-debug-asset-fetcher-loader.php`: a small file requires the
 * class and registers the entrypoint hook, keeping `bp-core-loader.php`
 * uncluttered.
 *
 * The offloader's `is_enabled()` guard (filterable) means this file is cheap
 * to include — when disabled no buffering is registered at all.
 *
 * @package BuddyBoss\Core
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Load the S3 image offloader and register its hooks.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return void
 */
function bb_s3_image_offload_init() {
	if ( ! class_exists( 'BB_S3_Image_Offload' ) ) {
		require_once buddypress()->plugin_dir . 'bp-core/classes/class-bb-s3-image-offload.php';
	}
	BB_S3_Image_Offload::instance()->bootstrap();
}
add_action( 'bp_loaded', 'bb_s3_image_offload_init', 3 );
