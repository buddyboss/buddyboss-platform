<?php
/**
 * Compatibility Integration Feature Configuration
 *
 * Registers the BuddyPress Compatibility integration as a feature in the BuddyBoss Feature Registry.
 *
 * @package BuddyBoss\Features\Integrations\Compatibility
 * @since BuddyBoss 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Compatibility integration with Feature Registry.
 *
 * This makes the BuddyPress Compatibility integration appear as a feature card in Settings 2.0
 * and enables conditional loading based on feature activation state.
 */
bb_register_integration(
	'compatibility',
	array(
		'label'                 => __( 'BuddyPress', 'buddyboss' ),
		'description'           => __( 'Enable compatibility mode to allow third-party BuddyPress plugins and themes to work with BuddyBoss Platform. This adds BuddyPress-style URLs and template structures.', 'buddyboss' ),
		'icon'                  => array(
			'type'  => 'font',
			'class' => 'bb-icons-rl bb-icons-rl-puzzle',
		),
		'license_tier'          => 'free',
		'required_plugin_const' => '', // No external plugin required.
		'php_loader'            => function() {
			require_once __DIR__ . '/loader.php';
		},
		'settings_route'        => '/settings/compatibility',
		'order'                 => 250,
	)
);
