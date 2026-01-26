<?php
/**
 * Pusher Integration Feature Configuration
 *
 * Registers the Pusher integration as a feature in the BuddyBoss Feature Registry.
 *
 * @package BuddyBoss\Features\Integrations\Pusher
 * @since BuddyBoss 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Pusher integration with Feature Registry.
 *
 * This makes the Pusher integration appear as a feature card in Settings 2.0
 * and enables conditional loading based on feature activation state.
 */
bb_register_integration(
	'pusher',
	array(
		'label'                 => __( 'Pusher', 'buddyboss' ),
		'description'           => __( 'Enable real-time notifications and updates using Pusher service. Provides instant activity updates, live messaging, and presence indicators for an enhanced user experience.', 'buddyboss' ),
		'icon'                  => array(
			'type'  => 'font',
			'class' => 'bb-icons-rl bb-icons-rl-broadcast',
		),
		'license_tier'          => 'free',
		'required_plugin_const' => '', // No external plugin required.
		'php_loader'            => function() {
			require_once __DIR__ . '/loader.php';
		},
		'settings_route'        => '/settings/pusher',
		'order'                 => 220,
	)
);
