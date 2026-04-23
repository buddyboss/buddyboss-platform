<?php
/**
 * LearnDash Integration Feature Configuration
 *
 * Registers the LearnDash integration as a feature in the BuddyBoss Platform.
 *
 * @package BuddyBoss\Features\Integrations\LearnDash
 * @since BuddyBoss 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Register LearnDash integration feature
bb_register_integration(
	'learndash',
	array(
		'label'                 => __( 'LearnDash', 'buddyboss' ),
		'description'           => __( 'Build advanced online courses with quizzes, drip content, and certifications for professional and scalable learning.', 'buddyboss' ),
		'icon'                  => array(
			'type'  => 'font',
			'class' => 'bb-icons-rl bb-icons-rl-graduation-cap',
		),
		'license_tier'          => 'free',
		'required_plugin_const' => 'LEARNDASH_VERSION', // Check if LearnDash is installed

		// PHP loader: Only loads when LearnDash is installed AND feature is enabled
		'php_loader'            => function() {
			require_once __DIR__ . '/loader.php';
		},

		'settings_route'        => '/settings/learndash',
		'order'                 => 210,
	)
);
