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
		return mysql_to_rfc3339( $date ); // phpcs:ignore WordPress.DB.RestrictedFunctions.mysql_to_rfc3339, PHPCompatibility.Extensions.RemovedExtensions.mysql_DeprecatedRemoved
	}

	if ( '0000-00-00 00:00:00' === $date_gmt ) {
		return null;
	}

	return mysql_to_rfc3339( $date_gmt ); // phpcs:ignore WordPress.DB.RestrictedFunctions.mysql_to_rfc3339, PHPCompatibility.Extensions.RemovedExtensions.mysql_DeprecatedRemoved
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

/**
 * Get a copy of a request for an item nested inside another response.
 *
 * A controller sometimes prepares an item that is not the response itself: the
 * `previous` key of a delete, the `activity` key of a pin action, or the
 * comments listed under their parent. The caller's `_fields` addresses the
 * outer response, and WordPress hands such nested items back whole, so the
 * selection must never be allowed to narrow them.
 *
 * Where a controller offers a selection of its own for the nested items, pass
 * that parameter's name as `$fields_param` and it takes the place of `_fields`
 * while the nested item is built. With the parameter absent the item is built
 * in full, exactly as it was before the controllers honoured `_fields`.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param WP_REST_Request $request      Full details about the request.
 * @param string          $fields_param Optional. Name of the request parameter
 *                                      holding the field selection for the
 *                                      nested items. Default ''.
 *
 * @return WP_REST_Request Copy of the request, with `_fields` replaced or removed.
 */
function bb_rest_request_for_nested_item( $request, $fields_param = '' ) {
	$nested_request = clone $request;
	$nested_fields  = ( '' !== $fields_param ) ? $request->get_param( $fields_param ) : '';

	unset( $nested_request['_fields'] );

	if ( ! empty( $nested_fields ) ) {
		$nested_request->set_param( '_fields', $nested_fields );
	}

	return $nested_request;
}

/**
 * Carry a nested item's field selection onto the request that builds it.
 *
 * Some controllers prepare their nested items with a request they synthesise
 * themselves -- the media, video and document attachments listed under an
 * activity, for one. The caller's `_fields` addresses the outer item and
 * WordPress hands a list back whole, so it can never reach them.
 *
 * A controller that offers a selection of its own for those items passes that
 * parameter's name here, and it becomes the `_fields` of the request that
 * builds them. With the parameter absent the request is left untouched and the
 * items are built in full, exactly as they were before.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param WP_REST_Request $nested_request Request the nested items are built with.
 * @param WP_REST_Request $request        Full details about the caller's request.
 * @param string          $fields_param   Name of the request parameter holding
 *                                        the selection for the nested items.
 *
 * @return WP_REST_Request The nested request, for chaining.
 */
function bb_rest_set_nested_item_fields( $nested_request, $request, $fields_param ) {
	$nested_fields = ( $request instanceof WP_REST_Request ) ? $request->get_param( $fields_param ) : '';

	if ( ! empty( $nested_fields ) ) {
		$nested_request->set_param( '_fields', $nested_fields );
	}

	return $nested_request;
}

/**
 * Argument definition for the `embed_fields` request parameter.
 *
 * Controllers whose items carry embeddable links add this to their collection
 * parameters, so that the selection shows up in `OPTIONS` beside the rest. The
 * parameter is read off the request wherever it arrives, so it also works on
 * the routes that declare no arguments of their own.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return array The argument definition.
 */
function bb_rest_embed_fields_param() {
	return array(
		'description'       => __( 'Limit the items returned under `_embedded` to a comma separated list of fields. Send one list for every relation -- `embed_fields=id,name` -- or one list per relation -- `embed_fields[user]=id,name` -- where `*` is the selection the relations without one of their own fall back to. The item\'s own `_fields` cannot reach them, because WordPress builds an embedded item from its link alone.', 'buddyboss' ),
		'default'           => '',
		'type'              => array( 'string', 'object' ),
		'sanitize_callback' => 'bb_rest_parse_embed_fields',
	);
}

/**
 * Parse the field selection a request sent for the items it has embedded.
 *
 * The selection takes one of two shapes. A bare list -- `embed_fields=id,name`,
 * or the `embed_fields[]=id&embed_fields[]=name` a client spells it with just
 * as readily -- is the selection every embeddable relation falls back to, and
 * is held here under `*`. A list per relation -- `embed_fields[user]=id,name`
 * -- names the relations it narrows, leaves the rest whole, and may carry a
 * `*` of its own for the relations it does not name.
 *
 * Parsing a selection that has already been parsed returns it unchanged, so a
 * controller is free to sanitise the parameter with this and the value still
 * reads the same further down.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param array|string $embed_fields Raw value of the `embed_fields` parameter.
 *
 * @return array Comma separated field lists, keyed by link relation.
 */
function bb_rest_parse_embed_fields( $embed_fields ) {
	if ( empty( $embed_fields ) ) {
		return array();
	}

	// Anything that names no relation is the selection for all of them.
	if ( ! is_array( $embed_fields ) || wp_is_numeric_array( $embed_fields ) ) {
		$embed_fields = array( '*' => $embed_fields );
	}

	$selections = array();

	foreach ( $embed_fields as $rel => $fields ) {
		// A list arrives as a string or, one field per key, as an array.
		if ( ! is_scalar( $fields ) && ! is_array( $fields ) ) {
			continue;
		}

		$fields = array_filter( array_map( 'sanitize_text_field', wp_parse_list( $fields ) ), 'strlen' );

		if ( empty( $fields ) ) {
			continue;
		}

		$selections[ $rel ] = implode( ',', $fields );
	}

	return $selections;
}

/**
 * Hold the selections the items embedded in the current response are built with.
 *
 * `WP_REST_Server::embed_links()` builds an embedded item from its link alone:
 * `WP_REST_Request::from_url()` is handed the `href` and nothing else, and the
 * request that asked for the embed is never consulted. The selection therefore
 * has to be waiting for it, keyed by that same `href`.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param array|null $selections Optional. Selections to hold, keyed by `href`.
 *                               Null reads what is held. Default null.
 *
 * @return array Comma separated field lists, keyed by `href`.
 */
function bb_rest_held_embed_fields( $selections = null ) {
	static $held = array();

	if ( is_array( $selections ) ) {
		$held = $selections;
	}

	return $held;
}

/**
 * Remember a request built for an embedded item, or recognise one.
 *
 * The mark cannot travel on the request: a parameter or a header is the
 * client's to send, and a request that arrived wearing one would have its links
 * stripped and would slip past the reset every request of its own performs.
 * The requests are held here by identity instead, in the one structure PHP has
 * for the purpose.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param WP_REST_Request|null $request  Optional. Request to recognise, or to
 *                                       remember when `$remember` is set.
 *                                       Anything else forgets every request
 *                                       held. Default null.
 * @param bool                 $remember Optional. Whether to remember the
 *                                       request rather than recognise it.
 *                                       Default false.
 *
 * @return bool Whether the request is one built for an embedded item.
 */
function bb_rest_embedded_request( $request = null, $remember = false ) {
	static $embedded = null;

	if ( ! $request instanceof WP_REST_Request || ! $embedded instanceof SplObjectStorage ) {
		$embedded = new SplObjectStorage();
	}

	if ( ! $request instanceof WP_REST_Request ) {
		return false;
	}

	if ( $remember ) {
		$embedded->attach( $request );

		return true;
	}

	return $embedded->contains( $request );
}

/**
 * Map the embeddable links of a response to the selection their relation asked for.
 *
 * A collection carries the links of each item inside its data, a single item
 * carries them on the response, and the two spell an attribute differently:
 * the links of an item have `embeddable` beside `href`, the links of a
 * response keep it under `attributes`.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param WP_REST_Response $response   Response the links belong to.
 * @param array            $selections Comma separated field lists, keyed by link relation.
 *
 * @return array Comma separated field lists, keyed by `href`.
 */
function bb_rest_map_embed_fields_to_links( $response, $selections ) {
	$data      = $response->get_data();
	$link_sets = array( $response->get_links() );

	if ( is_array( $data ) ) {
		if ( wp_is_numeric_array( $data ) ) {
			foreach ( $data as $item ) {
				if ( is_array( $item ) && ! empty( $item['_links'] ) && is_array( $item['_links'] ) ) {
					$link_sets[] = $item['_links'];
				}
			}
		} elseif ( ! empty( $data['_links'] ) && is_array( $data['_links'] ) ) {
			$link_sets[] = $data['_links'];
		}
	}

	$map      = array();
	$disputed = array();

	foreach ( $link_sets as $links ) {
		foreach ( (array) $links as $rel => $rel_links ) {
			if ( isset( $selections[ $rel ] ) ) {
				$fields = $selections[ $rel ];
			} elseif ( isset( $selections['*'] ) ) {
				$fields = $selections['*'];
			} else {
				continue;
			}

			foreach ( (array) $rel_links as $link ) {
				$attributes = isset( $link['attributes'] ) ? $link['attributes'] : $link;

				if ( empty( $link['href'] ) || empty( $attributes['embeddable'] ) ) {
					continue;
				}

				/*
				 * WordPress builds and caches an embedded item once per
				 * `href`, so two relations pointing at the same URL cannot be
				 * answered with two selections. Rather than let whichever was
				 * read last decide, neither does: the item is built whole,
				 * which is the only answer that shortchanges no one.
				 */
				if ( isset( $map[ $link['href'] ] ) && $map[ $link['href'] ] !== $fields ) {
					$disputed[ $link['href'] ] = true;
				}

				$map[ $link['href'] ] = $fields;
			}
		}
	}

	return array_diff_key( $map, $disputed );
}

/**
 * Hold the field selection the items of a response are to be embedded with.
 *
 * The response is not touched. All this leaves behind is the selection each
 * embeddable link is owed, which `bb_rest_narrow_embedded_request()` picks up
 * once the server starts building the embeds.
 *
 * The same callback answers the embedded items themselves, since WordPress
 * runs them through this filter too. An item narrowed to a selection that does
 * not name `_links` is answered without them, the way `_fields` answers the
 * items of a collection.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param WP_REST_Response $response Result to send to the client.
 * @param WP_REST_Server   $server   Server instance.
 * @param WP_REST_Request  $request  Request used to generate the response.
 *
 * @return WP_REST_Response The response, untouched but for the links of a narrowed item.
 */
function bb_rest_prepare_embedded_fields( $response, $server, $request ) {
	if ( ! $response instanceof WP_REST_Response || ! $request instanceof WP_REST_Request ) {
		return $response;
	}

	// One of the embedded items, on its way back to the item that asked for it.
	if ( bb_rest_embedded_request( $request ) ) {
		$fields = $request->get_param( '_fields' );

		if ( ! empty( $fields ) && ! rest_is_field_included( '_links', wp_parse_list( $fields ) ) ) {
			foreach ( array_keys( $response->get_links() ) as $rel ) {
				$response->remove_link( $rel );
			}
		}

		return $response;
	}

	// A request of its own: nothing an earlier one held is still owed to it.
	bb_rest_forget_embed_fields();

	if ( 0 !== strpos( ltrim( $request->get_route(), '/' ), bp_rest_namespace() . '/' ) ) {
		return $response;
	}

	$selections = bb_rest_parse_embed_fields( $request->get_param( 'embed_fields' ) );

	if ( empty( $selections ) ) {
		return $response;
	}

	bb_rest_held_embed_fields( bb_rest_map_embed_fields_to_links( $response, $selections ) );

	return $response;
}
add_filter( 'rest_post_dispatch', 'bb_rest_prepare_embedded_fields', 11, 3 );

/**
 * Carry the selection an embedded item is owed onto the request that builds it.
 *
 * This is the only place the selection can reach: WordPress generates the
 * request from the link's `href`, so the `href` is all there is to recognise
 * it by. The request is remembered as well, so that its response is answered
 * as an embedded item rather than as a request of its own.
 *
 * The selection is set as a query parameter, since dispatch replaces the URL
 * parameters of a request wholesale once it matches a route.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param WP_REST_Request|false $request Generated request object, or false if
 *                                       the URL could not be parsed.
 * @param string                $url     URL the request was generated from.
 *
 * @return WP_REST_Request|false The request, narrowed to what its relation asked for.
 */
function bb_rest_narrow_embedded_request( $request, $url ) {
	$held = bb_rest_held_embed_fields();

	if ( empty( $held ) || ! $request instanceof WP_REST_Request ) {
		return $request;
	}

	/*
	 * Every request built while a selection is held is one of the embedded
	 * items, including the ones whose relation asked for nothing: the response
	 * has to be recognised either way, or it would be taken for a request of
	 * its own and would clear what the rest of the embeds are still owed.
	 */
	bb_rest_embedded_request( $request, true );

	if ( isset( $held[ $url ] ) ) {
		$query = $request->get_query_params();

		$query['_fields'] = $held[ $url ];

		$request->set_query_params( $query );
	}

	return $request;
}
add_filter( 'rest_request_from_url', 'bb_rest_narrow_embedded_request', 10, 2 );

/**
 * Forget what a response owed the items embedded in it.
 *
 * The next request of its own clears this anyway, but not every process serves
 * exactly one: `/batch/v1` dispatches several, and WP-CLI and cron dispatch
 * whatever they please. Letting go of the selection the moment the response it
 * belongs to has been assembled keeps it from reaching any of them.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param array $result Response data to send to the client.
 *
 * @return array The response data, untouched.
 */
function bb_rest_forget_embedded_fields( $result ) {
	bb_rest_forget_embed_fields();

	return $result;
}
add_filter( 'rest_pre_echo_response', 'bb_rest_forget_embedded_fields' );

/**
 * Let go of every selection and every embedded request being held.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return void
 */
function bb_rest_forget_embed_fields() {
	bb_rest_held_embed_fields( array() );
	bb_rest_embedded_request();
}

/**
 * Set the global variable for the REST request.
 *
 * @param mixed $response The response data.
 * @param mixed $handler The handler.
 * @param mixed $request The request data.
 *
 * @return mixed $response The response data.
 */
function bb_rest_before_rest_request( $response, $handler, $request ) {
	$GLOBALS['bb_rest_request'] = $request;

	return $response;
}

add_filter( 'rest_request_before_callbacks', 'bb_rest_before_rest_request', 10, 3 );
