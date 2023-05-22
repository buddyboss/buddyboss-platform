<?php
/**
 * BuddyBoss Profile Search Request
 *
 * @package BuddyBoss\Core\ProfileSearch
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

add_action( 'wp', 'bp_ps_set_request' );
/**
 * Set BuddyBoss Profile Search request.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_ps_set_request() {
	global $post;
	global $shortcode_tags;

	if ( isset( $post->post_type ) && $post->post_type == 'page' ) {
		$saved_shortcodes = $shortcode_tags;
		$shortcode_tags   = array();
		add_shortcode( 'bp_ps_directory', 'bp_ps_save_hidden_filters' );
		do_shortcode( $post->post_content );
		$shortcode_tags = $saved_shortcodes;
	}

	$filters = bp_ps_hidden_filters();
	if ( ! empty( $filters ) ) {
		$cookie = apply_filters( 'bp_ps_cookie_name', 'bp_ps_filters' );
		setcookie( $cookie, http_build_query( $filters ), 0, COOKIEPATH );
	}

	if ( isset( $_REQUEST['bp_ps_debug'] ) ) {
		$cookie = apply_filters( 'bp_ps_cookie_name', 'bp_ps_debug' );
		setcookie( $cookie, 1, 0, COOKIEPATH );
	}

	$persistent = bp_ps_get_option( 'persistent', '1' );
	$new_search = isset( $_REQUEST[ bp_core_get_component_search_query_arg( 'members' ) ] );

	if ( $new_search || ! $persistent ) {
		if ( ! isset( $_REQUEST[ BP_PS_FORM ] ) ) {
			$_REQUEST[ BP_PS_FORM ] = 'clear';
		}
	}

	if ( isset( $_REQUEST[ BP_PS_FORM ] ) ) {

		$cookie = apply_filters( 'bp_ps_cookie_name', 'bp_ps_request' );
		if ( $_REQUEST[ BP_PS_FORM ] != 'clear' ) {
			$filtered_request = array_filter( $_REQUEST );

			/*
			* removing arrays with empty value
			* specifically for date range only
			*/
			foreach ( $filtered_request as $key => $value ) {
				if ( ! is_array( $value ) || strpos( $key, 'date_range' ) === false ) {
					continue;
				}

				foreach ( $value as $subfields ) {

					if ( ! is_array( $subfields ) || empty( $subfields ) ) {
						continue;
					}

					$subfield_checker = array();

					foreach ( $subfields as $subfield ) {
						if ( empty( $subfield ) ) {
							continue;
						}

						$subfield_checker[] = $subfield;
					}
				}

				if ( empty( $subfield_checker ) ) {
					unset( $filtered_request[ $key ] );
				}
			}

			$filtered_request['bp_ps_directory'] = bp_ps_current_page();

			setcookie( $cookie, http_build_query( $filtered_request ), 0, COOKIEPATH );
		} else {
			setcookie( $cookie, '', 0, COOKIEPATH );
		}
	}
}

/**
 * Gets BuddyBoss Profile Search request.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_ps_get_request( $type, $form = 0 ) {
	$current = bp_ps_current_page();

	$cookie  = apply_filters( 'bp_ps_cookie_name', 'bp_ps_request' );
	$request = isset( $_REQUEST[ BP_PS_FORM ] ) ? $_REQUEST : array();
	if ( empty( $request ) && isset( $_COOKIE[ $cookie ] ) ) {
		parse_str( stripslashes( $_COOKIE[ $cookie ] ), $request );
	}

	$cookie  = apply_filters( 'bp_ps_cookie_name', 'bp_ps_filters' );
	$filters = bp_ps_hidden_filters();
	if ( empty( $filters ) && isset( $_COOKIE[ $cookie ] ) ) {
		parse_str( stripslashes( $_COOKIE[ $cookie ] ), $filters );
	}

	switch ( $type ) {
		case 'form':
			if ( isset( $request[ BP_PS_FORM ] ) && $request[ BP_PS_FORM ] != $form ) {
				$request = array();
			}
			break;

		case 'filters':
			if ( isset( $request['bp_ps_directory'] ) && $request['bp_ps_directory'] != $current ) {
				$request = array();
			}
			break;

		case 'search':
			if ( isset( $request['bp_ps_directory'] ) && $request['bp_ps_directory'] != $current ) {
				$request = array();
			}
			if ( isset( $filters['bp_ps_directory'] ) && $filters['bp_ps_directory'] != $current ) {
				$filters = array();
			}
			foreach ( $filters as $key => $value ) {
				$request[ $key ] = $value;
			}
			break;
	}

	return apply_filters( 'bp_ps_request', $request, $type, $form );
}

/**
 * Saves BuddyBoss Profile Search hidden filters.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_ps_save_hidden_filters( $attr, $content ) {
	global $bp_ps_hidden_filters;

	$bp_ps_hidden_filters = array( 'bp_ps_directory' => bp_ps_current_page() );

	list (, $fields) = bp_ps_get_fields();
	$split           = isset( $attr['split'] ) ? $attr['split'] : ',';

	if ( is_array( $attr ) ) {
		foreach ( $attr as $key => $value ) {
			$k = bp_ps_match_key( $key, $fields );
			if ( $k === false ) {
				continue;
			}

			$f      = $fields[ $k ];
			$filter = ( $key == $f->code ) ? '' : substr( $key, strlen( $f->code ) + 1 );
			if ( ! bp_ps_Fields::is_filter( $f, $filter ) ) {
				continue;
			}

			$selector = $filter . ( count( $f->options ) ? '/e' : '' );
			switch ( $selector ) {
				case 'contains':
				case '':
				case 'like':
					$value = trim( addslashes( $value ) );
					if ( $value !== '' ) {
						$bp_ps_hidden_filters[ $key ] = $value;
					}
					break;

				case 'range':
				case 'age_range':
					list ($min, $max) = explode( $split, $value );
					$values           = array();
					if ( ( $min = trim( $min ) ) !== '' ) {
						$values['min'] = $min;
					}
					if ( ( $max = trim( $max ) ) !== '' ) {
						$values['max'] = $max;
					}
					if ( ! empty( $values ) ) {
						$bp_ps_hidden_filters[ $key ] = $values;
					}
					break;

				case 'match_any/e':
				case 'match_all/e':
				case '/e':
				case 'one_of/e':
					$flipped = array_flip( $f->options );
					$values  = explode( $split, $value );
					$keys    = array();
					foreach ( $values as $value ) {
						$value = trim( $value );
						if ( isset( $flipped[ $value ] ) ) {
							$keys[] = addslashes( $flipped[ $value ] );
						}
					}
					if ( ! empty( $keys ) ) {
						$bp_ps_hidden_filters[ $key ] = $keys;
					}
					break;
			}
		}
	}

	add_filter(
		'body_class',
		function ( $classes ) {
			return array_merge( array( 'directory', 'members', 'buddypress' ), $classes );
		}
	);
}

/**
 * Hides BuddyBoss Profile Search filters.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_ps_hidden_filters() {
	global $bp_ps_hidden_filters;

	$filters = isset( $bp_ps_hidden_filters ) ? $bp_ps_hidden_filters : array();
	return apply_filters( 'bp_ps_hidden_filters', $filters );
}

/**
 * Return current BuddyBoss Profile Search page.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_ps_current_page() {
	 $current = defined( 'DOING_AJAX' ) ?
		parse_url( $_SERVER['HTTP_REFERER'], PHP_URL_PATH ) :
		parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );

	return $current;
}

/**
 * Check if BuddyBoss Profile Search debugging is set.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_ps_debug() {
	$cookie = apply_filters( 'bp_ps_cookie_name', 'bp_ps_debug' );
	return isset( $_REQUEST['bp_ps_debug'] ) ? true : ( isset( $_COOKIE[ $cookie ] ) ? true : false );
}

// add_action ('bp_before_directory_members_content', 'bp_ps_display_filters');

/**
 * Output BuddyBoss Profile Search filters template.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_ps_display_filters() {
	$request = bp_ps_get_request( 'filters' );
	if ( ! empty( $request ) ) {
		bp_ps_call_template( 'members/bp-ps-filters', array( $request, true ) );
	}
}

add_filter( 'bp_ajax_querystring', 'bp_ps_filter_members', 99, 2 );

/**
 * Filters and returns BuddyBoss Profile Search query members.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_ps_filter_members( $qs, $object ) {
	if ( ! in_array( $object, array( 'members', 'group_members' ) ) ) {
		return $qs;
	}

	$request = bp_ps_get_request( 'search' );
	if ( empty( $request ) ) {
		return $qs;
	}

	$results = bp_ps_search( $request );
	if ( $results['validated'] ) {
		$args  = bp_parse_args( $qs );
		$users = $results['users'];

		if ( isset( $args['include'] ) ) {
			$included = explode( ',', $args['include'] );
			$users    = array_intersect( $users, $included );
			if ( count( $users ) == 0 ) {
				$users = array( 0 );
			}
		}

		$users           = apply_filters( 'bp_ps_search_results', $users );
		$args['include'] = implode( ',', $users );
		$qs              = build_query( $args );
	}

	return $qs;
}

/**
 * Returns BuddyBoss Profile Search results.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_ps_search( $request, $users = null ) {
	$results = array(
		'users'     => array( 0 ),
		'validated' => true,
	);

	$fields = bp_ps_parse_request( $request );

	$copied_arr = array();

	foreach ( $fields as $f ) {
		// Disable search for some individual field.
		if ( ! apply_filters( 'bp_ps_field_can_filter', true, $f, $request ) ) {
			continue;
		}

		if ( ! isset( $f->filter ) ) {
			continue;
		}
		if ( ! is_callable( $f->search ) ) {
			continue;
		}

		$f = apply_filters( 'bp_ps_field_before_query', $f );

		$found = call_user_func( $f->search, $f );
		$found = apply_filters( 'bp_ps_field_search_results', $found, $f );

		$copied_arr = $found;
		if ( isset( $copied_arr, $f->id ) && ! empty( $copied_arr ) ) {
			foreach ( $copied_arr as $key => $user ) {
				$field_visibility = xprofile_get_field_visibility_level( intval( $f->id ), intval( $user ) );
				if ( 'adminsonly' === $field_visibility && ! current_user_can( 'administrator' ) ) {
					if ( ( $key = array_search( $user, $found ) ) !== false ) {
						unset( $found[ $key ] );
					}
				}
				if ( 'friends' === $field_visibility && ! current_user_can( 'administrator' ) && false === friends_check_friendship( intval( $user ), bp_loggedin_user_id() ) ) {
					if ( ( $key = array_search( $user, $found ) ) !== false ) {
						unset( $found[ $key ] );
					}
				}
			}
		}

		$match_all = apply_filters( 'bp_ps_match_all', true );
		if ( $match_all ) {
			$users = isset( $users ) ? array_intersect( $users, $found ) : $found;
			if ( count( $users ) == 0 ) {
				return $results;
			}
		} else {
			$users = isset( $users ) ? array_merge( $users, $found ) : $found;
		}
	}

	if ( isset( $users ) ) {
		$results['users'] = $users;
	} else {
		$results['validated'] = false;
	}

	return $results;
}

/**
 * Returns escaped $text with backslashes before characters _%\.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_ps_esc_like( $text ) {
	return addcslashes( $text, '_%\\' );
}
