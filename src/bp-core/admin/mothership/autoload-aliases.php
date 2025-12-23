<?php
/**
 * BuddyBoss Mothership Autoload Aliases
 *
 * This file sets up class aliases to ensure code can always use
 * BuddyBossPlatform\GroundLevel namespace regardless of whether
 * PHP-Scoper was actually used or not.
 *
 * @package BuddyBoss\Core\Admin\Mothership
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Setup namespace aliases for GroundLevel classes.
 * This creates aliases from the actual vendor namespace to BuddyBossPlatform namespace,
 * allowing code to always use BuddyBossPlatform\GroundLevel regardless of whether
 * PHP-Scoper was actually used or not.
 */
function buddyboss_setup_mothership_aliases() {
	// If BuddyBossPlatform namespace already exists (PHP-Scoper was used), no aliasing needed.
	if ( class_exists( 'BuddyBossPlatform\GroundLevel\Container\Container' ) ) {
		return;
	}

	// Define the class mappings - creating BuddyBossPlatform aliases.
	$class_mappings = array(
		// Container classes.
		'BuddyBossPlatform\GroundLevel\Container\Container' => 'GroundLevel\Container\Container',
		'BuddyBossPlatform\GroundLevel\Container\Service'  => 'GroundLevel\Container\Service',
		'BuddyBossPlatform\GroundLevel\Container\Concerns\HasStaticContainer' => 'GroundLevel\Container\Concerns\HasStaticContainer',
		'BuddyBossPlatform\GroundLevel\Container\Contracts\StaticContainerAwareness' => 'GroundLevel\Container\Contracts\StaticContainerAwareness',

		// Mothership classes.
		'BuddyBossPlatform\GroundLevel\Mothership\Service' => 'GroundLevel\Mothership\Service',
		'BuddyBossPlatform\GroundLevel\Mothership\AbstractPluginConnection' => 'GroundLevel\Mothership\AbstractPluginConnection',
		'BuddyBossPlatform\GroundLevel\Mothership\Credentials' => 'GroundLevel\Mothership\Credentials',
		'BuddyBossPlatform\GroundLevel\Mothership\Api\Request' => 'GroundLevel\Mothership\Api\Request',
		'BuddyBossPlatform\GroundLevel\Mothership\Api\Response' => 'GroundLevel\Mothership\Api\Response',
		'BuddyBossPlatform\GroundLevel\Mothership\Api\Request\LicenseActivations' => 'GroundLevel\Mothership\Api\Request\LicenseActivations',
		'BuddyBossPlatform\GroundLevel\Mothership\Api\Request\Products' => 'GroundLevel\Mothership\Api\Request\Products',
		'BuddyBossPlatform\GroundLevel\Mothership\Manager\LicenseManager' => 'GroundLevel\Mothership\Manager\LicenseManager',
		'BuddyBossPlatform\GroundLevel\Mothership\Manager\AddonsManager' => 'GroundLevel\Mothership\Manager\AddonsManager',
		'BuddyBossPlatform\GroundLevel\Mothership\Manager\AddonInstallSkin' => 'GroundLevel\Mothership\Manager\AddonInstallSkin',
		'BuddyBossPlatform\GroundLevel\Mothership\ExtensionType' => 'GroundLevel\Mothership\ExtensionType',
		'BuddyBossPlatform\GroundLevel\InProductNotifications\Service' => 'GroundLevel\InProductNotifications\Service',
	);

	// Create aliases from actual namespace to BuddyBossPlatform namespace.
	foreach ( $class_mappings as $alias => $original ) {
		// Skip if alias already exists.
		if ( class_exists( $alias, false ) || interface_exists( $alias, false ) || trait_exists( $alias, false ) ) {
			continue;
		}

		// Check if the original class/interface/trait exists and create alias.
		if ( class_exists( $original, false ) ) {
			class_alias( $original, $alias );
		} elseif ( interface_exists( $original, false ) ) {
			class_alias( $original, $alias );
		} elseif ( trait_exists( $original, false ) ) {
			class_alias( $original, $alias );
		}
	}
}

// Call the function immediately since we need these aliases available.
buddyboss_setup_mothership_aliases();
