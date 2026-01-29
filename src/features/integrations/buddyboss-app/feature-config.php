<?php
/**
 * BuddyBoss App Integration Feature Configuration
 *
 * Registers the BuddyBoss App integration as a feature in the BuddyBoss Feature Registry.
 *
 * @package BuddyBoss\Features\Integrations\BuddyBossApp
 * @since BuddyBoss 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register BuddyBoss App integration with Feature Registry.
 *
 * This makes the BuddyBoss App integration appear as a feature card in Settings 2.0
 * and enables conditional loading based on feature activation state.
 */
bb_register_integration(
	'buddyboss-app',
	array(
		'label'                 => __( 'BuddyBoss App', 'buddyboss' ),
		'description'           => __( 'Connect your community to native iOS and Android mobile apps with the BuddyBoss App. Requires the BuddyBoss App plugin for configuration and API endpoints.', 'buddyboss' ),
		'icon'                  => array(
			'type'  => 'font',
			'class' => 'bb-icons-rl bb-icons-rl-mobile',
		),
		'license_tier'          => 'free',
		'required_plugin_const' => '', // Plugin check handled in integration class.
		'php_loader'            => function() {
			require_once __DIR__ . '/loader.php';
		},
		'settings_route'        => '/settings/buddyboss-app',
		'order'                 => 240,
	)
);
