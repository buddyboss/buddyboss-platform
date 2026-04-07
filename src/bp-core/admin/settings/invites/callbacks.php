<?php
/**
 * BuddyBoss Admin Settings - Email Invites Callbacks.
 *
 * Lazy registration of dynamic profile type fields and sanitize callbacks
 * for Email Invites feature settings.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Lazily register per-profile-type toggle fields for Email Invites.
 *
 * Profile type fields are registered dynamically because the list of active
 * member types may change. This hook fires when Settings 2.0 fetches feature
 * settings via AJAX, ensuring the field list is always current.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $feature_id The feature being fetched.
 */
function bb_invites_register_profile_type_fields( $feature_id ) {

	if ( 'invites' !== $feature_id ) {
		return;
	}

	// Only register when profile types are enabled and the email invites component is active.
	if ( true !== bp_member_type_enable_disable() || ! bp_is_active( 'invites' ) ) {
		return;
	}

	$member_types = bp_get_active_member_types();

	if ( empty( $member_types ) ) {
		return;
	}

	$order    = 40;
	$is_first = true;

	// Prime post meta cache to avoid N+1 queries in the loop.
	update_postmeta_cache( $member_types );

	foreach ( $member_types as $member_type_id ) {
		$type_name = bp_get_member_type_key( $member_type_id );

		if ( empty( $type_name ) ) {
			continue;
		}

		$member_type_label = get_post_meta( $member_type_id, '_bp_member_type_label_name', true );

		if ( empty( $member_type_label ) ) {
			$member_type_label = $type_name;
		}

		$field_args = array(
			'name'              => 'bp-enable-send-invite-member-type-' . $type_name,
			'label'             => $is_first ? __( 'Allowed Profile Types', 'buddyboss' ) : '',
			'type'              => 'toggle',
			'description'       => $member_type_label,
			'help_text'         => $is_first ? __( 'Only allow the selected profile types to send invites.', 'buddyboss' ) : '',
			'default'           => absint( bp_get_option( 'bp-enable-send-invite-member-type-' . $type_name, 0 ) ),
			'sanitize_callback' => 'absint',
			'conditional'       => array(
				'field' => 'bp-disable-invite-member-type',
				'value' => true,
			),
			'group'             => array(
				'key' => 'allowed_profile_types',
			),
			'order'             => $order,
		);

		bb_register_feature_field(
			'invites',
			'email_invite_settings',
			'email_invite_general',
			$field_args
		);

		$is_first = false;
		$order   += 10;
	}
}
add_action( 'bb_admin_settings_before_get_feature', 'bb_invites_register_profile_type_fields' );
