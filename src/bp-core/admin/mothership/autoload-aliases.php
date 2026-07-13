<?php
/**
 * BuddyBoss Mothership Autoload Aliases
 *
 * This file sets up class aliases to ensure code can always use
 * BuddyBossPlatform\GroundLevel namespace regardless of whether
 * PHP-Scoper was actually used or not.
 *
 * @package BuddyBoss\Core\Admin\Mothership
 * @since BuddyBoss 2.14.0
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
	// Updated for GroundLevel 7.4.0: the static-container `Service` classes and the
	// HasStaticContainer/StaticContainerAwareness concern+contract were removed in
	// favor of the dependency-injection ServiceProvider pattern.
	$class_mappings = array(
		// Container classes.
		'BuddyBossPlatform\GroundLevel\Container\Container' => 'GroundLevel\Container\Container',
		'BuddyBossPlatform\GroundLevel\Container\ServiceProvider' => 'GroundLevel\Container\ServiceProvider',
		'BuddyBossPlatform\GroundLevel\Container\Resolver' => 'GroundLevel\Container\Resolver',

		// Mothership classes.
		'BuddyBossPlatform\GroundLevel\Mothership\MothershipServiceProvider' => 'GroundLevel\Mothership\MothershipServiceProvider',
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
		'BuddyBossPlatform\GroundLevel\InProductNotifications\IPNServiceProvider' => 'GroundLevel\InProductNotifications\IPNServiceProvider',
	);

	// Create aliases from actual namespace to BuddyBossPlatform namespace.
	foreach ( $class_mappings as $alias => $original ) {
		// Skip if alias already exists. Do NOT autoload the alias here — the prefixed
		// name has no file in a non-scoped build, so autoloading it would be wasted.
		if ( class_exists( $alias, false ) || interface_exists( $alias, false ) || trait_exists( $alias, false ) ) {
			continue;
		}

		// Resolve the original class/interface/trait WITH autoloading. This function runs at
		// mothership-init include time, before anything has referenced the vendor classes, so
		// without autoloading every check would return false and no aliases would be created —
		// which then fatals when BB_Plugin_Connector/BB_Addons_Manager extend the prefixed
		// parent names. (The reverse-alias function already autoloads for the same reason.)
		if ( class_exists( $original ) ) {
			class_alias( $original, $alias );
		} elseif ( interface_exists( $original ) ) {
			class_alias( $original, $alias );
		} elseif ( trait_exists( $original ) ) {
			class_alias( $original, $alias );
		}
	}
}

/**
 * Create reverse aliases (prefixed -> un-prefixed) for service providers referenced by
 * GroundLevel `@inject` docblock annotations.
 *
 * The GroundLevel 7.4.0 dependency resolver reads `@inject \GroundLevel\...::CONST`
 * annotations and resolves them with PHP's `constant()`. Because the vendor packages are
 * PHP-Scoper prefixed to `BuddyBossPlatform\GroundLevel\...`, but the scoper does NOT rewrite
 * class names inside docblock comments, those annotations still reference the un-prefixed
 * class names (e.g. `\GroundLevel\Mothership\MothershipServiceProvider::PARAM_API_BASE_URL`).
 * Without these aliases the resolver throws "@inject references undefined constant" and the
 * Mothership/IPN services fail to boot.
 *
 * @since BuddyBoss [BBVERSION]
 */
function buddyboss_setup_mothership_inject_aliases() {
	$provider_mappings = array(
		// Un-prefixed alias => actual prefixed class.
		'GroundLevel\Mothership\MothershipServiceProvider'             => 'BuddyBossPlatform\GroundLevel\Mothership\MothershipServiceProvider',
		'GroundLevel\InProductNotifications\IPNServiceProvider'        => 'BuddyBossPlatform\GroundLevel\InProductNotifications\IPNServiceProvider',
		'GroundLevel\Component\ComponentServiceProvider'               => 'BuddyBossPlatform\GroundLevel\Component\ComponentServiceProvider',
	);

	foreach ( $provider_mappings as $alias => $original ) {
		if ( class_exists( $alias, false ) ) {
			continue;
		}

		// Allow autoloading here ($original may not be loaded yet this early in boot);
		// class_alias() then makes the un-prefixed name resolve to the real prefixed class
		// so the resolver's constant() lookups on @inject annotations succeed.
		if ( class_exists( $original ) ) {
			class_alias( $original, $alias );
		}
	}
}

// Call the functions immediately since we need these aliases available.
buddyboss_setup_mothership_aliases();
buddyboss_setup_mothership_inject_aliases();
