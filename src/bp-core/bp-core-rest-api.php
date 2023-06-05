<?php
/**
 * Core REST API functions.
 *
 * @package    BuddyBoss\Core
 * @subpackage Core
 * @since      BuddyBoss 1.3.5
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Is the BP REST plugin is active?
 *
 * @return boolean True if the BP REST plugin is active. False otherwise.
 * @since BuddyBoss 1.3.5
 *
 */
function bp_rest_is_plugin_active() {
	return (bool) has_action( 'bp_rest_api_init', 'bp_rest', 5 );
}

/**
 * Should we use the REST Endpoints of built BuddyBoss?
 *
 * If the BuddyBoss Platform REST API plugin is active, it overrides BuddyBoss REST enpoints.
 * This allows us to carry on maintaining all the BP REST API endpoints from
 * the BuddyBoss Platform REST API plugin on GitHub.
 *
 * @return bool Whether to use the REST Endpoints of built BuddyBoss.
 * @since BuddyBoss 1.3.5
 */
function bp_rest_in_buddypress() {
	return ! bp_rest_is_plugin_active();
}

/**
 * Check the availability of the BP REST API.
 *
 * @return boolean True if the BP REST API is available. False otherwise.
 * @since BuddyBoss 1.3.5
 */
function bp_rest_api_is_available() {

	/**
	 * Filter here to disable the BP REST API.
	 *
	 * The BP REST API requires at least WordPress 4.7.0
	 *
	 * @param boolean $value True if the BP REST API is available. False otherwise.
	 *
	 * @since BuddyBoss 1.3.5
	 */
	return apply_filters( 'bp_rest_api_is_available', function_exists( 'create_initial_rest_routes' ) && bp_rest_in_buddypress() ) || bp_rest_is_plugin_active();
}

/**
 * Register the jQuery.ajax wrapper for BP REST API requests.
 *
 * @since BuddyBoss 1.3.5
 */
function bp_rest_api_register_request_script() {
	if ( ! bp_rest_api_is_available() ) {
		return;
	}

	$dependencies = array( 'jquery' );

	// The wrapper for WP REST API requests was introduced in WordPress 4.9.0.
	if ( wp_script_is( 'wp-api-request', 'registered' ) ) {
		$dependencies = array( 'wp-api-request' );
	}

	wp_register_script(
		'bp-api-request',
		sprintf( '%1$sbp-core/js/bp-api-request%2$s.js', buddypress()->plugin_url, bp_core_get_minified_asset_suffix() ),
		$dependencies,
		bp_get_version(),
		true
	);

	wp_localize_script(
		'bp-api-request',
		'bpApiSettings',
		array(
			'root'            => esc_url_raw( get_rest_url() ),
			'nonce'           => wp_create_nonce( 'wp_rest' ),
			'unexpectedError' => __( 'An unexpected error occured. Please try again.', 'buddyboss' ),
		)
	);
}

add_action( 'bp_init', 'bp_rest_api_register_request_script' );

/**
 * BuddyBoss REST API namespace.
 *
 * @return string
 * @since BuddyBoss 1.3.5
 */
function bp_rest_namespace() {

	/**
	 * Filter API namespace.
	 *
	 * @param string $namespace BuddyBoss core namespace.
	 *
	 * @since BuddyBoss 1.3.5
	 */
	return apply_filters( 'bp_rest_namespace', 'buddyboss' );
}

/**
 * BuddyBoss REST API version.
 *
 * @return string
 * @since BuddyBoss 1.3.5
 */
function bp_rest_version() {

	/**
	 * Filter API version.
	 *
	 * @param string $version BuddyBoss core version.
	 *
	 * @since BuddyBoss 1.3.5
	 *
	 */
	return apply_filters( 'bp_rest_version', 'v1' );
}

/**
 * Get user URL.
 *
 * @param int|array $user_ids User IDs.
 *
 * @return string
 * @since BuddyBoss 1.3.5
 */
function bp_rest_get_user_url( $user_ids ) {
	if ( is_array( $user_ids ) ) {
		return sprintf(
			'/%s/%s/members?include=%s',
			bp_rest_namespace(),
			bp_rest_version(),
			implode( ',', $user_ids )
		);
	}

	return sprintf(
		'/%s/%s/members/%d',
		bp_rest_namespace(),
		bp_rest_version(),
		absint( $user_ids )
	);
}

/**
 * Set headers to let the Client Script be aware of the pagination.
 *
 * @param WP_REST_Response $response The response data.
 * @param integer          $total    The total number of found items.
 * @param integer          $per_page The number of items per page of results.
 *
 * @return WP_REST_Response $response The response data.
 * @since BuddyBoss 1.3.5
 */
function bp_rest_response_add_total_headers( WP_REST_Response $response, $total = 0, $per_page = 0 ) {
	if ( ! $total || ! $per_page ) {
		return $response;
	}

	$total_items = (int) $total;
	$max_pages   = ceil( $total_items / (int) $per_page );

	$response->header( 'X-WP-Total', $total_items );
	$response->header( 'X-WP-TotalPages', (int) $max_pages );

	return $response;
}

/**
 * Convert the input date to RFC3339 format.
 *
 * @param string      $date_gmt Date GMT format.
 * @param string|null $date     Optional. Date object.
 *
 * @return string|null ISO8601/RFC3339 formatted datetime.
 * @since BuddyBoss 1.3.5
 */
function bp_rest_prepare_date_response( $date_gmt, $date = null ) {
	if ( isset( $date ) ) {
		return mysql_to_rfc3339( $date );
	}

	if ( '0000-00-00 00:00:00' === $date_gmt ) {
		return null;
	}

	return mysql_to_rfc3339( $date_gmt );
}

/**
 * Clean up member_type input.
 *
 * @param string $value Comma-separated list of group types.
 *
 * @return array|null
 * @since BuddyBoss 1.3.5
 */
function bp_rest_sanitize_member_types( $value ) {
	if ( empty( $value ) ) {
		return $value;
	}

	$types              = explode( ',', $value );
	$registered_types   = bp_get_member_types();
	$registered_types[] = 'any';
	$valid_types        = array_intersect( $types, $registered_types );

	return ( ! empty( $valid_types ) ) ? $valid_types : null;
}

/**
 * Validate member_type input.
 *
 * @param mixed $value Mixed value.
 *
 * @return WP_Error|boolean
 * @since BuddyBoss 1.3.5
 */
function bp_rest_validate_member_types( $value ) {
	if ( empty( $value ) ) {
		return true;
	}

	$types            = explode( ',', $value );
	$registered_types = bp_get_member_types();

	// Add the special value.
	$registered_types[] = 'any';
	foreach ( $types as $type ) {
		if ( ! in_array( $type, $registered_types, true ) ) {
			return new WP_Error(
				'bp_rest_invalid_member_type',
				sprintf(
				/* translators: %1$s and %2$s is replaced with the registered type(s) */
					__( 'The member type you provided, %1$s, is not one of %2$s.', 'buddyboss' ),
					$type,
					implode( ', ', $registered_types )
				)
			);
		}
	}
}

/**
 * Clean up group_type input.
 *
 * @param string $value Comma-separated list of group types.
 *
 * @return array|null
 * @since BuddyBoss 1.3.5
 */
function bp_rest_sanitize_group_types( $value ) {
	if ( empty( $value ) ) {
		return null;
	}

	$types       = explode( ',', $value );
	$valid_types = array_intersect( $types, bp_groups_get_group_types() );

	return empty( $valid_types ) ? null : $valid_types;
}

/**
 * Validate group_type input.
 *
 * @param mixed $value Mixed value.
 *
 * @return WP_Error|bool
 * @since BuddyBoss 1.3.5
 */
function bp_rest_validate_group_types( $value ) {
	if ( empty( $value ) ) {
		return true;
	}

	$types            = explode( ',', $value );
	$registered_types = bp_groups_get_group_types();
	foreach ( $types as $type ) {
		if ( ! in_array( $type, $registered_types, true ) ) {
			return new WP_Error(
				'bp_rest_invalid_group_type',
				sprintf(
				/* translators: %1$s and %2$s is replaced with the registered types */
					__( 'The group type you provided, %1$s, is not one of %2$s.', 'buddyboss' ),
					$type,
					implode( ', ', $registered_types )
				)
			);
		}
	}
}

/**
 * Clean up an array, comma- or space-separated list of strings.
 *
 * @param array|string $list List of strings.
 *
 * @return array Sanitized array of strings.
 * @since BuddyBoss 1.3.5
 */
function bp_rest_sanitize_string_list( $list ) {
	if ( ! is_array( $list ) ) {
		$list = preg_split( '/[\s,]+/', $list );
	}

	return array_unique( array_map( 'sanitize_text_field', $list ) );
}

/**
 * Get the user object, if the ID is valid.
 *
 * @param int $user_id Supplied user ID.
 *
 * @return WP_User|boolean
 * @since BuddyBoss 1.3.5
 */
function bp_rest_get_user( $user_id ) {
	if ( (int) $user_id <= 0 ) {
		return false;
	}

	$user = get_userdata( (int) $user_id );
	if ( empty( $user ) || ! $user->exists() ) {
		return false;
	}

	return $user;
}

/**
 * Registers a new field on an existing BuddyBoss object.
 *
 * @param string $component_id The name of the *active* component (eg: `activity`, `groups`, `xprofile`).
 *                             Required.
 * @param string $attribute    The attribute name. Required.
 * @param array  $args         {
 *                             Optional. An array of arguments used to handle the registered field.
 * @param string $object_type  The xProfile object type to get. This parameter is only required for
 *                             the Extended Profiles component. Not used for all other components.
 *                             Possible values are `data`, `field` or `group`.
 *
 * @return bool                True if the field has been registered successfully. False otherwise.
 * @since BuddyBoss 1.3.5
 *
 * @see   `register_rest_field()` for a full description.
 * }
 */
function bp_rest_register_field( $component_id, $attribute, $args = array(), $object_type = '' ) {
	$registered_fields = false;

	if ( ! $component_id || ! bp_is_active( $component_id ) || ! $attribute ) {
		return $registered_fields;
	}

	// Use the `bp_` prefix as we're using a WordPress global used for Post Types.
	$field_name = 'bp_' . $component_id;

	// Use the meta type as a suffix for the field name.
	if ( 'xprofile' === $component_id ) {
		if ( ! in_array( $object_type, array( 'data', 'field', 'group' ), true ) ) {
			return $registered_fields;
		}

		$field_name .= '_' . $object_type;
	}

	$args = bp_parse_args(
		$args,
		array(
			'get_callback'    => null,
			'update_callback' => null,
			'schema'          => null,
		),
		'rest_register_field'
	);

	// Register the field.
	register_rest_field( $field_name, $attribute, $args );

	if ( isset( $GLOBALS['wp_rest_additional_fields'][ $field_name ] ) ) {
		$registered_fields = $GLOBALS['wp_rest_additional_fields'][ $field_name ];
	}

	// Check it has been registered.
	return isset( $registered_fields[ $attribute ] );
}

/**
 * Function to check its BuddyBoss rest route or not.
 *
 * @since BuddyBoss 1.8.2
 *
 * @return bool
 */
function bb_is_rest() {
	return ! empty( $GLOBALS['wp']->query_vars['rest_route'] );
}

/**
 * Clean variables using sanitize_text_field. Arrays are cleaned recursively.
 * Non-scalar values are ignored.
 *
 * @param string|array $var Data to sanitize.
 *
 * @since BuddyBoss 2.0.8
 * @return string|array
 */
function bb_input_clean( $var ) {
	if ( is_array( $var ) ) {
		return array_map( 'bb_input_clean', $var );
	} else {
		return is_scalar( $var ) ? sanitize_text_field( $var ) : $var;
	}
}

/**
 * Function to remove mentioned link for moderated and deleted members from content.
 *
 * @since BuddyBoss 2.2.7
 *
 * @param string $content Content.
 *
 * @return string $content
 */
function bb_rest_raw_content( $content ) {

	if ( empty( $content ) ) {
		return $content;
	}

	$content = function_exists( 'bb_moderation_remove_mention_link' ) ? bb_moderation_remove_mention_link( $content ) : $content;
	$content = function_exists( 'bb_mention_remove_deleted_users_link' ) ? bb_mention_remove_deleted_users_link( $content ) : $content;

	/**
	 * Function will return content without mentioned link for moderated/deleted members.
	 *
	 * @since BuddyBoss 2.2.7
	 *
	 * @param string $content Content.
	 */
	return apply_filters( 'bb_rest_raw_content', $content );
}
