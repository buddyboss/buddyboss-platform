<?php
/**
 * Xprofile Ajax functions
 *
 * @since BuddyBoss 1.1.6
 * @version 1.1.6
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

add_action( 'admin_init', function() {
	$ajax_actions = array(
		array(
			'xprofile_get_field' => array(
				'function' => 'bp_nouveau_ajax_xprofile_get_field',
				'nopriv'   => true,
			),
		)
	);

	foreach ( $ajax_actions as $ajax_action ) {
		$action = key( $ajax_action );

		add_action( 'wp_ajax_' . $action, $ajax_action[ $action ]['function'] );

		if ( ! empty( $ajax_action[ $action ]['nopriv'] ) ) {
			add_action( 'wp_ajax_nopriv_' . $action, $ajax_action[ $action ]['function'] );
		}
	}
}, 12 );



add_action( 'wp_ajax_nopriv_', 'bp_get_xprofile_field_ajax' );

/**
 * Ajax callback for get the conditional field based on the selected member type.
 *
 * @since BuddyBoss 1.1.6
 *
 */
function bp_nouveau_ajax_xprofile_get_field() {
	global $wpdb;

	$response = array(
		'feedback' => sprintf(
			'<div class="bp-feedback error bp-ajax-message"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
			esc_html__( 'There was a problem performing this action. Please try again.', 'buddyboss' )
		),
	);

	// Bail if not a GET action.
	if ( ! bp_is_get_request() ) {
		wp_send_json_error( $response );
	}

	if ( empty( $_GET['_wpnonce'] ) ) {
		wp_send_json_error( $response );
	}

	// Use default nonce
	$nonce = $_GET['_wpnonce'];
	$check = 'bp-core-register-page-js';

	// Nonce check!
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, $check ) ) {
		wp_send_json_error( $response );
	}

	$bp                        = buddypress();
	$member_type_id            = filter_input( INPUT_GET, 'type', FILTER_VALIDATE_INT );
	$existing_fields           = bb_filter_input_string( INPUT_GET, 'fields' );
	$existing_fields_exclude   = bb_filter_input_string( INPUT_GET, 'fields' );
	$existing_fields_fixed_ids = bb_filter_input_string( INPUT_GET, 'fixedIds' );
	$prevId                    = bb_filter_input_string( INPUT_GET, 'prevId' );
	$member_type_key           = bp_get_member_type_key( $member_type_id );
	$prev_type_key             = bp_get_member_type_key( $prevId );
	$existing_fields_arr       = explode( ',', $existing_fields );
	$signup_group_id           = bp_xprofile_base_group_id();

	//FOr prev data
	$get_prev_ids = [];
	if ( 0 < strlen( $prev_type_key ) ) {
		$query           = "SELECT object_id FROM {$bp->profile->table_name_meta} WHERE meta_key = 'member_type' AND meta_value = '{$prev_type_key}' AND object_type = 'field'";
		$get_db_prev_ids = $wpdb->get_results( $query );
		if ( isset( $get_db_prev_ids ) ) {
			foreach ( $get_db_prev_ids as $id ) {
				$get_prev_ids[] = $id->object_id;
			}
		}
	}

	$query      = "SELECT object_id FROM {$bp->profile->table_name_meta} WHERE meta_key = 'member_type' AND meta_value = '{$member_type_key}' AND object_type = 'field'";
	$get_db_ids = $wpdb->get_results( $query );

	$new_fields = array();
	if ( isset( $get_db_ids ) ) {
		foreach ( $get_db_ids as $id ) {
			if ( ! in_array( $id->object_id, $existing_fields_arr ) ) {
				$field = xprofile_get_field( $id->object_id, null, false );
				if ( $field->group_id === (int) $signup_group_id ) {
					if ( ! in_array( $id->object_id, $get_prev_ids ) ) {
						$new_fields[] = $id->object_id;
					}
					$existing_fields_arr[] = $id->object_id;
				}
			}
		}
	}

	$existing_fields  = implode( ',', $existing_fields_arr );
	$include_fields   = implode( ',', $new_fields );
	$fixed_fields_arr = explode( ',', $existing_fields_fixed_ids );

	ob_start();

	if ( bp_has_profile( "profile_group_id=$signup_group_id&exclude_fields=$existing_fields_exclude&include_fields=$include_fields" ) ) :

		while ( bp_profile_groups() ) : bp_the_profile_group();
			while ( bp_profile_fields() ) : bp_the_profile_field();
				?>
				<div<?php bp_field_css_class( 'editfield ajax_added' ); bp_field_data_attribute(); ?>>
					<fieldset>
						<?php
						$field_type = bp_xprofile_create_field_type( bp_get_the_profile_field_type() );
						$field_type->edit_field_html();
						?>
					</fieldset>
				</div>
			<?php
			endwhile;
		endwhile;
	endif;

	$content                = ob_get_clean();
	$response               = array();
	$response['field_ids']  = $existing_fields;
	$response['field_html'] = $content;


	wp_send_json_success( $response );

}
