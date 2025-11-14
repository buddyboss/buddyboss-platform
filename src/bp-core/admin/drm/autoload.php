<?php
/**
 * BuddyBoss DRM Autoloader
 *
 * Autoload DRM-related classes.
 *
 * @package BuddyBoss\Core\Admin\DRM
 * @since 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Autoloader for DRM classes.
 *
 * @param string $class_name The class name to load.
 */
function bb_drm_autoloader( $class_name ) {
	// Only autoload classes in our namespace.
	if ( strpos( $class_name, 'BuddyBoss\\Core\\Admin\\DRM\\' ) !== 0 ) {
		return;
	}

	// Remove namespace prefix.
	$class_name = str_replace( 'BuddyBoss\\Core\\Admin\\DRM\\', '', $class_name );

	// Convert class name to file name.
	// E.g., BB_DRM_Helper -> class-bb-drm-helper.php
	$class_file = 'class-' . strtolower( str_replace( '_', '-', $class_name ) ) . '.php';

	// Build full path.
	$file = __DIR__ . '/' . $class_file;

	// Load the file if it exists.
	if ( file_exists( $file ) ) {
		require_once $file;
	}
}

// Register the autoloader.
spl_autoload_register( 'bb_drm_autoloader' );

// Explicitly require the helper and base class as they're always needed.
require_once __DIR__ . '/class-bb-drm-helper.php';
require_once __DIR__ . '/class-bb-base-drm.php';
require_once __DIR__ . '/class-bb-drm-nokey.php';
require_once __DIR__ . '/class-bb-drm-invalid.php';
require_once __DIR__ . '/class-bb-drm-addon.php';
require_once __DIR__ . '/class-bb-drm-registry.php';
require_once __DIR__ . '/class-bb-drm-controller.php';
require_once __DIR__ . '/class-bb-notifications.php';
