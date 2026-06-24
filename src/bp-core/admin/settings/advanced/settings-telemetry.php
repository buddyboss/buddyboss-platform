<?php
/**
 * BuddyBoss Admin Settings - Advanced Telemetry Panel.
 *
 * Registers fields for the Telemetry side panel of the Advanced feature.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Advanced Telemetry panel fields.
 *
 * @since BuddyBoss 3.0.0
 *
 * @return void
 */
function bb_advanced_register_telemetry_fields() {

	$feature_id = 'advanced';
	$panel_id   = 'telemetry';

	// =========================================================================
	// SECTION: Telemetry
	// =========================================================================

	bb_register_feature_section(
		$feature_id,
		$panel_id,
		'advanced_telemetry',
		array(
			'title' => __( 'Telemetry', 'buddyboss-platform' ),
			'order' => 10,
		)
	);

	// Telemetry descriptions per mode — legacy shows different description text
	// dynamically when the user switches between radio buttons. React uses
	// `option_descriptions` so SettingsForm updates the description on select change.
	$current_mode = bp_get_option( 'bb_advanced_telemetry_reporting', 'disable' );

	$option_descriptions = array(
		'complete'  => __( 'Telemetry helps us gather usage statistics and information about your configuration and the features and functionality you use. We aggregate this information to help us improve our product and associate it with your customer record to help us serve you better. We do not gather or send any of your users\' personally identifiable information. To stop contributing towards improving the product, you can disable telemetry.', 'buddyboss-platform' ),
		'anonymous' => __( 'Telemetry helps us gather usage statistics and information about your configuration and the features and functionality you use. We aggregate this information to help us improve our product. By choosing anonymous reporting, your data will not be associated with your customer record, and the way we serve you will be less relevant to you. We do not gather or send any of your users\' personally identifiable information. If you stop contributing towards improving the product, you can disable telemetry.', 'buddyboss-platform' ),
		'disable'   => __( 'Disabling telemetry will stop gathering and reporting usage statistics about your configuration and the features and functionality you use. By disabling telemetry, you will not be contributing towards the improvement of the product and the way we serve you will be less relevant to you.', 'buddyboss-platform' ),
	);

	// Field 1: Telemetry Reporting Mode.
	// All 3 options matching legacy (Complete / Anonymous / Disable telemetry).
	bb_register_feature_field(
		$feature_id,
		$panel_id,
		'advanced_telemetry',
		array(
			'name'                => 'bb_advanced_telemetry_reporting',
			'label'               => __( 'Telemetry', 'buddyboss-platform' ),
			'description'         => $option_descriptions[ $current_mode ] ?? $option_descriptions['disable'],
			'type'                => 'select',
			'default'             => $current_mode,
			'sanitize_callback'   => 'bb_advanced_sanitize_telemetry_reporting',
			'option_descriptions' => $option_descriptions,
			'options'             => array(
				array(
					'value' => 'complete',
					'label' => __( 'Complete reporting', 'buddyboss-platform' ),
				),
				array(
					'value' => 'anonymous',
					'label' => __( 'Anonymous reporting', 'buddyboss-platform' ),
				),
				array(
					'value' => 'disable',
					'label' => __( 'Disable telemetry', 'buddyboss-platform' ),
				),
			),
			'order'               => 10,
		)
	);
}
