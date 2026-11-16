<?php
/**
 * BuddyBoss Moderation Functions.
 *
 * Functions for the Moderation component.
 *
 * @since   BuddyBoss 1.5.6
 * @package BuddyBoss\Moderation
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/** Moderation Core functions ************************************************/

/**
 * Function to get the moderation content types.
 *
 * @since BuddyBoss 1.5.6
 *
 * @return mixed|void
 */
function bboss_moderation_content_types() {

	/**
	 * Filter to update content types
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param array $types Content types
	 */
	return apply_filters( 'bp_moderation_content_types', array() );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_moderation_content_types' ) && ! function_exists( 'bp_moderation_content_types' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_moderation_content_types()}.
	 *
	 * @deprecated Use bboss_moderation_content_types() instead.
	 */
	function bp_moderation_content_types() {
		return bboss_moderation_content_types();
	}
}
// @bbwt-com-end compat-layer


/**
 * Function to get specific moderation content type.
 *
 * @since BuddyBoss 1.5.6
 *
 * @param string $key content type key.
 *
 * @return mixed|void
 */
function bboss_moderation_get_content_type( $key ) {

	$content_types = bboss_moderation_content_types();

	/**
	 * Filter to update Content type
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param string $item_type Item item.
	 */
	return apply_filters( 'bp_moderation_get_content_type', key_exists( $key, $content_types ) ? $content_types[ $key ] : '' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_moderation_get_content_type' ) && ! function_exists( 'bp_moderation_get_content_type' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_moderation_get_content_type()}.
	 *
	 * @deprecated Use bboss_moderation_get_content_type() instead.
	 */
	function bp_moderation_get_content_type( $key ) {
		return bboss_moderation_get_content_type( $key );
	}
}
// @bbwt-com-end compat-layer


/**
 * Retrieve an Moderation reports.
 *
 * The bboss_moderation_get() function shares all arguments with
 * BBoss_Moderation::get().
 *
 * @since BuddyBoss 1.5.6
 *
 * @param array|string $args See BBoss_Moderation::get() for description.
 *
 * @return array $moderation See BBoss_Moderation::get() for description.
 * @see   BBoss_Moderation::get() For more information on accepted arguments
 *        and the format of the returned value.
 */
function bboss_moderation_get( $args = '' ) {

	$r = bboss_parse_args(
		$args,
		array(
			'max'               => false,
			// Maximum number of results to return.
			'user_id'           => false,
			// Filter moderation reported by particular user.
			'fields'            => 'all',
			'page'              => 1,
			// Page 1 without a per_page will result in no pagination.
			'per_page'          => false,
			// results per page.
			'sort'              => 'DESC',
			'order_by'          => 'last_updated',
			// sort ASC or DESC.
			// Phpcs:ignore
			'meta_query'        => false,
			// Filter by moderation meta. See WP_Meta_Query for format.
			'date_query'        => false,
			// Filter by date. See first parameter of WP_Date_Query for format.
			'filter_query'      => false,
			'exclude'           => false,
			// Comma-separated list of moderation IDs to exclude.
			'in'                => false,
			// Comma-separated list or array of moderation IDs to which you
			// want to limit the query.
			'exclude_types'     => false,
			// Comma-separated list of moderation item types to exclude.
			'in_types'          => false,
			// Comma-separated list or array of moderation item types to which you
			// want to limit the query.
			'update_meta_cache' => true,
			'display_reporters' => false,
			'count_total'       => false,

			/**
			 * Pass filters as an array -- all filter items can be multiple values comma separated:
			 * array(
			 *     'item_id'       => false, // Item ID to filter on eg. Activity ID, Groups ID, User ID etc.
			 *     'hide_sitewide' => false, // filter by hidden items e.g. 0, 1.
			 *     'blog_id'       => false, // Blog ID to filter on.
			 * );
			 */
			'filter'            => array(),
		),
		'moderation_get'
	);

	$moderation = BBoss_Moderation::get(
		array(
			'page'              => $r['page'],
			'per_page'          => $r['per_page'],
			'user_id'           => $r['user_id'],
			'max'               => $r['max'],
			'sort'              => $r['sort'],
			'order_by'          => $r['order_by'],
			'meta_query'        => $r['meta_query'], // Phpcs:ignore
			'date_query'        => $r['date_query'],
			'filter_query'      => $r['filter_query'],
			'filter'            => $r['filter'],
			'exclude_types'     => $r['exclude_types'],
			'in_types'          => $r['in_types'],
			'exclude'           => $r['exclude'],
			'in'                => $r['in'],
			'update_meta_cache' => $r['update_meta_cache'],
			'display_reporters' => $r['display_reporters'],
			'count_total'       => $r['count_total'],
			'fields'            => $r['fields'],
		)
	);

	/**
	 * Filters the requested moderation item(s).
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param array         $r          Arguments used for the moderation query.
	 *
	 * @param BBoss_Moderation $moderation Requested moderation object.
	 */
	return apply_filters_ref_array(
		'bp_moderation_get',
		array(
			&$moderation,
			&$r,
		)
	);
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_moderation_get' ) && ! function_exists( 'bp_moderation_get' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_moderation_get()}.
	 *
	 * @deprecated Use bboss_moderation_get() instead.
	 */
	function bp_moderation_get( $args = '' ) {
		return bboss_moderation_get( $args );
	}
}
// @bbwt-com-end compat-layer


/**
 * Retrieve sitewide hidden items ids of particular item type.
 *
 * @since BuddyBoss 1.5.6
 *
 * @param bool $force_cache Bypass cache it true.
 *
 * @return array $moderation See BBoss_Moderation::get() for description.
 */
function bboss_moderation_get_hidden_user_ids( $force_cache = false ) {

	static $cache = array();

	$args = array(
		'in_types'          => BBoss_Moderation_Members::$moderation_type,
		'update_meta_cache' => false,
		'filter_query'      => array(
			'relation' => 'OR',
			array(
				'column' => 'hide_sitewide',
				'value'  => 1,
			),
			array(
				'column' => 'user_id',
				'value'  => get_current_user_id(),
			),
		),
	);

	$cache_key = 'bp_moderation_get_hidden_user_ids_' . BBoss_Moderation_Members::$moderation_type . '_' . get_current_user_id();
	if ( isset( $cache[ $cache_key ] ) && false === $force_cache ) {
		return $cache[ $cache_key ];
	}

	$hidden_users = bboss_moderation_get( $args );

	$hidden_users_ids = array();
	if ( ! empty( $hidden_users['moderations'] ) ) {
		$hidden_users_ids = wp_list_pluck( $hidden_users['moderations'], 'item_id' );
	}

	$cache[ $cache_key ] = $hidden_users_ids;

	return $hidden_users_ids;
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_moderation_get_hidden_user_ids' ) && ! function_exists( 'bp_moderation_get_hidden_user_ids' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_moderation_get_hidden_user_ids()}.
	 *
	 * @deprecated Use bboss_moderation_get_hidden_user_ids() instead.
	 */
	function bp_moderation_get_hidden_user_ids( $force_cache = false ) {
		return bboss_moderation_get_hidden_user_ids( $force_cache );
	}
}
// @bbwt-com-end compat-layer


/**
 * Function to get Report button
 *
 * @since BuddyBoss 1.5.6
 *
 * @param array $args button args.
 * @param bool  $html Should return button html or not.
 *
 * @return string|array
 */
function bboss_moderation_get_report_button( $args, $html = true ) {

	if ( ! bboss_is_active( 'moderation' ) || ! is_user_logged_in() ) {
		return ! empty( $html ) ? '' : array();
	}

	$item_id   = isset( $args['button_attr']['data-bp-content-id'] ) ? $args['button_attr']['data-bp-content-id'] : false;
	$item_type = isset( $args['button_attr']['data-bp-content-type'] ) ? $args['button_attr']['data-bp-content-type'] : false;

	/**
	 * Filter to update report link args
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param array  $button  Button args.
	 * @param string $item_id item id.
	 */
	$args = apply_filters( "bp_moderation_{$item_type}_button_args", $args, $item_id );

	if ( empty( $item_id ) || empty( $item_type ) || empty( $args ) ) {
		return ! empty( $html ) ? '' : array();
	}

	// Check the current user's permission.
	$user_can = bboss_moderation_user_can( $item_id, $item_type, false );

	if ( false === (bool) $user_can ) {
		return ! empty( $html ) ? '' : array();
	}

	// Check moderation setting enabled or not.
	if ( BBoss_Moderation_Members::$moderation_type === $item_type ) {
		$button_text          = __( 'Block', 'buddyboss' );
		$reported_button_text = __( 'Blocked', 'buddyboss' );
	} elseif ( BBoss_Moderation_Members::$moderation_type_report === $item_type ) {
		$button_text          = __( 'Report Member', 'buddyboss' );
		$reported_button_text = __( 'Report Member', 'buddyboss' );
	} else {
		$button_text          = bboss_moderation_get_report_button_text( $item_type, $item_id );
		$reported_button_text = bboss_moderation_get_reported_button_text( $item_type, $item_id );
	}

	$sub_items     = bboss_moderation_get_sub_items( $item_id, $item_type );
	$item_sub_id   = isset( $sub_items['id'] ) ? $sub_items['id'] : $item_id;
	$item_sub_type = isset( $sub_items['type'] ) ? $sub_items['type'] : $item_type;

	$args['button_attr'] = bboss_parse_args(
		$args['button_attr'],
		array(
			'id'                   => 'report-content-' . $args['button_attr']['data-bp-content-type'] . '-' . $args['button_attr']['data-bp-content-id'],
			'href'                 => ( BBoss_Moderation_Members::$moderation_type === $item_type ) ? '#block-member' : '#content-report',
			'class'                => 'button item-button bp-secondary-action report-content',
			'data-bp-content-id'   => '',
			'data-bp-content-type' => '',
			'data-bp-nonce'        => wp_create_nonce( 'bp-moderation-content' ),
		)
	);

	$button = bboss_parse_args(
		$args,
		array(
			'link_text' => sprintf( '<span class="bp-screen-reader-text">%s</span><span class="report-label">%s</span>', esc_html( $button_text ), esc_html( $button_text ) ),
		)
	);

	BBoss_Moderation::$no_cache = true;

	$is_reported = bboss_moderation_report_exist( $item_sub_id, $item_sub_type );

	BBoss_Moderation::$no_cache = false;

	if ( $is_reported ) {
		$button['link_text']                = sprintf( '<span class="bp-screen-reader-text">%s</span><span class="report-label">%s</span>', esc_html( $reported_button_text ), esc_html( $reported_button_text ) );
		$button['button_attr']['class']     = str_replace( 'report-content', 'reported-content', $button['button_attr']['class'] );
		$button['button_attr']['item_id']   = $item_id;
		$button['button_attr']['item_type'] = $item_type;
		$button['button_attr']['href']      = '#reported-content';
		unset( $button['button_attr']['data-bp-content-id'] );
		unset( $button['button_attr']['data-bp-content-type'] );
		unset( $button['button_attr']['data-bp-nonce'] );
	}

	$button['button_attr']['reported_type'] = bboss_moderation_get_report_type( $item_type, $item_id );

	/**
	 * Filter to update report link args
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param array  $button      Button args.
	 * @param string $is_reported Item reported.
	 */
	$button = apply_filters( "bp_moderation_{$item_type}_button", $button, $is_reported );

	if ( ! empty( $html ) ) {
		if ( $is_reported ) {
			$button = sprintf( '<a href="%s"  id="%s" class="%s" reported_type="%s" >%s</a>', $button['button_attr']['href'], esc_attr( $button['button_attr']['id'] ), esc_attr( $button['button_attr']['class'] ), $button['button_attr']['reported_type'], wp_kses_post( $button['link_text'] ) );
		} else {
			$button = sprintf( '<a href="%s" id="%s" class="%s" data-bp-content-id="%s" data-bp-content-type="%s" data-bp-nonce="%s" reported_type="%s" >%s</a>', esc_url( $button['button_attr']['href'] ), esc_attr( $button['button_attr']['id'] ), esc_attr( $button['button_attr']['class'] ), esc_attr( $button['button_attr']['data-bp-content-id'] ), esc_attr( $button['button_attr']['data-bp-content-type'] ), esc_attr( $button['button_attr']['data-bp-nonce'] ), $button['button_attr']['reported_type'], wp_kses_post( $button['link_text'] ) );
		}
	}

	/**
	 * Filter to update report link
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param mixed $button Button args or HTML.
	 * @param array $args   button args.
	 * @param bool  $html   Should return button html or not.
	 */
	return apply_filters( 'bp_moderation_get_report_button', $button, $args, $html );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_moderation_get_report_button' ) && ! function_exists( 'bp_moderation_get_report_button' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_moderation_get_report_button()}.
	 *
	 * @deprecated Use bboss_moderation_get_report_button() instead.
	 */
	function bp_moderation_get_report_button( $args, $html = true ) {
		return bboss_moderation_get_report_button( $args, $html );
	}
}
// @bbwt-com-end compat-layer


/**
 * Function to Check content Reported by current user or not.
 *
 * @since BuddyBoss 1.5.6
 *
 * @param int    $item_id          Item id.
 * @param string $item_type        Item type.
 * @param int    $blocking_user_id The ID for the user who blocked user.
 *
 * @return bool
 */
function bboss_moderation_report_exist( $item_id, $item_type, $blocking_user_id = false ) {
	$response = false;

	if ( ! empty( $item_id ) && ! empty( $item_type ) ) {
		$moderation = new BBoss_Moderation( $item_id, $item_type, $blocking_user_id );
		$response   = ( ! empty( $moderation->id ) && ! empty( $moderation->report_id ) && empty( $moderation->user_report ) );
		if ( BBoss_Moderation_Members::$moderation_type_report === $item_type ) {
			$response = ( ! empty( $moderation->id ) && ! empty( $moderation->report_id ) && ! empty( $moderation->user_report ) );
		}
	}

	return $response;
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_moderation_report_exist' ) && ! function_exists( 'bp_moderation_report_exist' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_moderation_report_exist()}.
	 *
	 * @deprecated Use bboss_moderation_report_exist() instead.
	 */
	function bp_moderation_report_exist( $item_id, $item_type, $blocking_user_id = false ) {
		return bboss_moderation_report_exist( $item_id, $item_type, $blocking_user_id );
	}
}
// @bbwt-com-end compat-layer


/**
 * Check whether a user has been marked as a blocked by current user.
 *
 * @since BuddyBoss 1.5.6
 *
 * @param int $user_id          The ID for the current user.
 * @param int $blocking_user_id The ID for the user who blocked user.
 *
 * @return bool True if suspended, otherwise false.
 */
function bboss_moderation_is_user_blocked( $user_id, $blocking_user_id = false ) {
	if ( ! bboss_is_moderation_member_blocking_enable( 0 ) ) {
		return false;
	}

	return bboss_moderation_report_exist( $user_id, BBoss_Moderation_Members::$moderation_type, $blocking_user_id );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_moderation_is_user_blocked' ) && ! function_exists( 'bp_moderation_is_user_blocked' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_moderation_is_user_blocked()}.
	 *
	 * @deprecated Use bboss_moderation_is_user_blocked() instead.
	 */
	function bp_moderation_is_user_blocked( $user_id, $blocking_user_id = false ) {
		return bboss_moderation_is_user_blocked( $user_id, $blocking_user_id );
	}
}
// @bbwt-com-end compat-layer


/**
 * Check user is suspended or not.
 *
 * @since BuddyBoss 1.5.6
 *
 * @param int $user_id user id.
 *
 * @return bool
 */
function bboss_moderation_is_user_suspended( $user_id ) {
	return BBoss_Core_Suspend::check_user_suspend( $user_id );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_moderation_is_user_suspended' ) && ! function_exists( 'bp_moderation_is_user_suspended' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_moderation_is_user_suspended()}.
	 *
	 * @deprecated Use bboss_moderation_is_user_suspended() instead.
	 */
	function bp_moderation_is_user_suspended( $user_id ) {
		return bboss_moderation_is_user_suspended( $user_id );
	}
}
// @bbwt-com-end compat-layer


/**
 * Function to get sub items.
 *
 * @since BuddyBoss 1.5.6
 *
 * @param int    $item_id   Item id.
 * @param string $item_type Item type.
 *
 * @return array
 */
function bboss_moderation_get_sub_items( $item_id, $item_type ) {

	/**
	 * If Sub item id and sub type is empty then actual item is reported otherwise Connected item will be reported
	 * Like For Forum create activity, When reporting Activity it'll report actual forum
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param array  $button      Button args.
	 * @param string $is_reported Item reported.
	 */
	$sub_items = apply_filters( "bp_moderation_{$item_type}_button_sub_items", $item_id );

	if ( empty( $sub_items ) ) {
		$sub_items = array(
			'id'   => $item_id,
			'type' => $item_type,
		);
	}

	return $sub_items;
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_moderation_get_sub_items' ) && ! function_exists( 'bp_moderation_get_sub_items' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_moderation_get_sub_items()}.
	 *
	 * @deprecated Use bboss_moderation_get_sub_items() instead.
	 */
	function bp_moderation_get_sub_items( $item_id, $item_type ) {
		return bboss_moderation_get_sub_items( $item_id, $item_type );
	}
}
// @bbwt-com-end compat-layer


/**
 * Check the user can moderate the current Item or Not.
 *
 * @param int    $item_id         Item ID.
 * @param string $item_type       Item Type.
 * @param string $bypass_validate Should validate items or not.
 *
 * @since BuddyBoss 1.5.6
 *
 * @return bool
 */
function bboss_moderation_can_report( $item_id, $item_type, $bypass_validate = true ) {

	if ( empty( $item_id ) || empty( $item_type ) ) {
		return false;
	}

	/**
	 * Filter to check the current permission.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param bool   $boolean Check its true/false.
	 * @param string $item_id item id.
	 */
	$args = apply_filters( "bp_moderation_{$item_type}_button_args", array( 'id' => '' ), $item_id );

	if ( empty( $args ) ) {
		return false;
	}

	// Check moderation setting enabled or not.
	if ( BBoss_Moderation_Members::$moderation_type === $item_type ) {
		if ( ! bboss_is_moderation_member_blocking_enable( 0 ) ) {
			return false;
		}
	} elseif ( ! bboss_is_moderation_content_reporting_enable( 0, $item_type ) ) {
		return false;
	}

	/**
	 * Filter to check the item_id is valid or not.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param bool   $boolean Check item is valid or not.
	 * @param string $item_id item id.
	 */
	$validate = apply_filters( "bp_moderation_{$item_type}_validate", true, $item_id );

	if ( $bypass_validate && empty( $validate ) ) {
		return false;
	}

	$sub_items     = bboss_moderation_get_sub_items( $item_id, $item_type );
	$item_sub_id   = isset( $sub_items['id'] ) ? $sub_items['id'] : $item_id;
	$item_sub_type = isset( $sub_items['type'] ) ? $sub_items['type'] : $item_type;

	// If Sub type moderation disabled then reporting option should not show.
	if ( in_array( $item_sub_type, array( BBoss_Moderation_Document::$moderation ), true ) && ! bboss_is_moderation_content_reporting_enable( 0, $item_sub_type ) ) {
		return false;
	}

	if ( empty( $item_sub_id ) || empty( $item_sub_type ) ) {
		return false;
	}

	return true;
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_moderation_can_report' ) && ! function_exists( 'bp_moderation_can_report' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_moderation_can_report()}.
	 *
	 * @deprecated Use bboss_moderation_can_report() instead.
	 */
	function bp_moderation_can_report( $item_id, $item_type, $bypass_validate = true ) {
		return bboss_moderation_can_report( $item_id, $item_type, $bypass_validate );
	}
}
// @bbwt-com-end compat-layer


/**
 * Check the user can report the current Item or Not.
 *
 * @param int    $item_id         Item ID.
 * @param string $item_type       Item Type.
 * @param string $bypass_validate Should validate items or not.
 *
 * @since BuddyBoss 1.5.6
 *
 * @return bool
 */
function bboss_moderation_user_can( $item_id, $item_type, $bypass_validate = true ) {

	if ( empty( $item_id ) || empty( $item_type ) ) {
		return false;
	}

	/**
	 * Filter to check the current permission.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param bool   $boolean Check its true/false.
	 * @param string $item_id item id.
	 */
	$args = apply_filters( "bp_moderation_{$item_type}_button_args", array( 'id' => '' ), $item_id );

	if ( empty( $args ) ) {
		return false;
	}

	// Check moderation setting enabled or not.
	if ( BBoss_Moderation_Members::$moderation_type === $item_type ) {
		if ( ! bboss_is_moderation_member_blocking_enable( 0 ) ) {
			return false;
		}
	} elseif ( BBoss_Moderation_Members::$moderation_type_report === $item_type ) {
		if ( ! bboss_is_moderation_member_reporting_enable( 0 ) ) {
			return false;
		}
	} elseif ( ! bboss_is_moderation_content_reporting_enable( 0, $item_type ) ) {
		return false;
	}

	/**
	 * Filter to check the item_id is valid or not.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param bool   $boolean Check item is valid or not.
	 * @param string $item_id item id.
	 */
	$validate = apply_filters( "bp_moderation_{$item_type}_validate", true, $item_id );

	if ( $bypass_validate && empty( $validate ) ) {
		return false;
	}

	$sub_items     = bboss_moderation_get_sub_items( $item_id, $item_type );
	$item_sub_id   = isset( $sub_items['id'] ) ? $sub_items['id'] : $item_id;
	$item_sub_type = isset( $sub_items['type'] ) ? $sub_items['type'] : $item_type;

	// If Sub type moderation disabled then reporting option should not show.
	if ( in_array( $item_sub_type, array( BBoss_Moderation_Document::$moderation ), true ) && ! bboss_is_moderation_content_reporting_enable( 0, $item_sub_type ) ) {
		return false;
	}

	if ( in_array( $item_sub_type, array( BBoss_Moderation_Members::$moderation_type_report ), true ) && bboss_loggedin_user_id() === $item_sub_id ) {
		return false;
	}

	if ( empty( $item_sub_id ) || empty( $item_sub_type ) ) {
		return false;
	}

	$owner_ids = bboss_moderation_get_content_owner_id( $item_sub_id, $item_sub_type );
	if ( ! is_array( $owner_ids ) ) {
		$owner_ids = array( $owner_ids );
	}

	// Hide if content is created by current user.
	if ( in_array( bboss_loggedin_user_id(), $owner_ids, true ) ) {
		return false;
	}

	return true;
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_moderation_user_can' ) && ! function_exists( 'bp_moderation_user_can' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_moderation_user_can()}.
	 *
	 * @deprecated Use bboss_moderation_user_can() instead.
	 */
	function bp_moderation_user_can( $item_id, $item_type, $bypass_validate = true ) {
		return bboss_moderation_user_can( $item_id, $item_type, $bypass_validate );
	}
}
// @bbwt-com-end compat-layer


/**
 * Check the current content is hidden or not.
 *
 * @param int    $item_id   Item ID.
 * @param string $item_type Item Type.
 *
 * @since BuddyBoss 1.5.6
 *
 * @return bool
 */
function bboss_moderation_is_content_hidden( $item_id, $item_type ) {
	if ( empty( $item_id ) || empty( $item_type ) ) {
		return false;
	}

	// Check moderation setting enabled or not.
	if ( BBoss_Moderation_Members::$moderation_type === $item_type ) {
		if ( ! bboss_is_moderation_member_blocking_enable( 0 ) ) {
			return false;
		}
	} elseif ( ! bboss_is_moderation_content_reporting_enable( 0, $item_type ) ) {
		return false;
	}

	return (bool) BBoss_Core_Suspend::check_hidden_content( $item_id, $item_type );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_moderation_is_content_hidden' ) && ! function_exists( 'bp_moderation_is_content_hidden' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_moderation_is_content_hidden()}.
	 *
	 * @deprecated Use bboss_moderation_is_content_hidden() instead.
	 */
	function bp_moderation_is_content_hidden( $item_id, $item_type ) {
		return bboss_moderation_is_content_hidden( $item_id, $item_type );
	}
}
// @bbwt-com-end compat-layer


/** Moderation actions *******************************************************/

/**
 * Function to Report content.
 *
 * @since BuddyBoss 1.5.6
 *
 * @param array $args Report args.
 *
 * @return bool
 */
function bboss_moderation_add( $args = array() ) {
	$response = false;

	if ( ! empty( $args['content_id'] ) && ! empty( $args['content_type'] ) ) {
		// Called member class externally when content_type = report_member.
		if ( BBoss_Moderation_Members::$moderation_type_report === $args['content_type'] ) {
			$class = BBoss_Moderation_Abstract::get_class( BBoss_Moderation_Members::$moderation_type );
		} else {
			$class = BBoss_Moderation_Abstract::get_class( $args['content_type'] );
		}

		if ( method_exists( $class, 'report' ) ) {
			$response = $class::report( $args );
		}
	}

	return $response;
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_moderation_add' ) && ! function_exists( 'bp_moderation_add' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_moderation_add()}.
	 *
	 * @deprecated Use bboss_moderation_add() instead.
	 */
	function bp_moderation_add( $args = array() ) {
		return bboss_moderation_add( $args );
	}
}
// @bbwt-com-end compat-layer


/**
 * Function to hide content.
 *
 * @since BuddyBoss 1.5.6
 *
 * @param array $args Report args.
 *
 * @return bool
 */
function bboss_moderation_hide( $args = array() ) {
	$response = false;

	if ( ! empty( $args['content_id'] ) && ! empty( $args['content_type'] ) ) {
		$class = BBoss_Moderation_Abstract::get_class( $args['content_type'] );

		if ( method_exists( $class, 'hide' ) ) {
			$response = $class::hide( $args );
		}
	}

	return $response;
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_moderation_hide' ) && ! function_exists( 'bp_moderation_hide' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_moderation_hide()}.
	 *
	 * @deprecated Use bboss_moderation_hide() instead.
	 */
	function bp_moderation_hide( $args = array() ) {
		return bboss_moderation_hide( $args );
	}
}
// @bbwt-com-end compat-layer


/**
 * Function to unhide content.
 *
 * @since BuddyBoss 1.5.6
 *
 * @param array $args Report args.
 *
 * @return bool
 */
function bboss_moderation_unhide( $args = array() ) {
	$response = false;

	if ( ! empty( $args['content_id'] ) && ! empty( $args['content_type'] ) ) {
		$class = BBoss_Moderation_Abstract::get_class( $args['content_type'] );

		if ( method_exists( $class, 'unhide' ) ) {
			$response = $class::unhide( $args );
		}
	}

	return $response;
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_moderation_unhide' ) && ! function_exists( 'bp_moderation_unhide' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_moderation_unhide()}.
	 *
	 * @deprecated Use bboss_moderation_unhide() instead.
	 */
	function bp_moderation_unhide( $args = array() ) {
		return bboss_moderation_unhide( $args );
	}
}
// @bbwt-com-end compat-layer


/**
 * Function to unhide content.
 *
 * @since BuddyBoss 1.5.6
 *
 * @param array $args Report args.
 *
 * @return bool
 */
function bboss_moderation_delete( $args = array() ) {
	$response = false;

	if ( ! empty( $args['content_id'] ) && ! empty( $args['content_type'] ) ) {
		$class = BBoss_Moderation_Abstract::get_class( $args['content_type'] );

		if ( method_exists( $class, 'delete' ) ) {
			$response = $class::delete( $args );
		}
	}

	return $response;
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_moderation_delete' ) && ! function_exists( 'bp_moderation_delete' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_moderation_delete()}.
	 *
	 * @deprecated Use bboss_moderation_delete() instead.
	 */
	function bp_moderation_delete( $args = array() ) {
		return bboss_moderation_delete( $args );
	}
}
// @bbwt-com-end compat-layer


/** Meta *********************************************************************/

/**
 * Get metadata for a given moderation item.
 *
 * @since BuddyBoss 1.5.6
 *
 * @param int    $moderation_id ID of the moderation item whose metadata is being requested.
 * @param string $meta_key      Optional. If present, only the metadata matching
 *                              that meta key will be returned. Otherwise, all metadata for the
 *                              moderation item will be fetched.
 * @param bool   $single        Optional. If true, return only the first value of the
 *                              specified meta_key. This parameter has no effect if meta_key is not
 *                              specified. Default: true.
 *
 * @return mixed The meta value(s) being requested.
 */
function bboss_moderation_get_meta( $moderation_id = 0, $meta_key = '', $single = true ) {
	add_filter( 'query', 'bp_filter_metaid_column_name' );
	$retval = get_metadata( 'moderation', $moderation_id, $meta_key, $single );
	remove_filter( 'query', 'bp_filter_metaid_column_name' );

	/**
	 * Filters the metadata for a specified moderation item.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param mixed  $retval        The meta values for the moderation item.
	 * @param int    $moderation_id ID of the moderation item.
	 * @param string $meta_key      Meta key for the value being requested.
	 * @param bool   $single        Whether to return one matched meta key row or all.
	 */
	return apply_filters( 'bp_moderation_get_meta', $retval, $moderation_id, $meta_key, $single );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_moderation_get_meta' ) && ! function_exists( 'bp_moderation_get_meta' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_moderation_get_meta()}.
	 *
	 * @deprecated Use bboss_moderation_get_meta() instead.
	 */
	function bp_moderation_get_meta( $moderation_id = 0, $meta_key = '', $single = true ) {
		return bboss_moderation_get_meta( $moderation_id, $meta_key, $single );
	}
}
// @bbwt-com-end compat-layer


/**
 * Add a piece of moderation metadata.
 *
 * @since BuddyBoss 1.5.6
 *
 * @param int    $moderation_id ID of the moderation item.
 * @param string $meta_key      Metadata key.
 * @param mixed  $meta_value    Metadata value.
 * @param bool   $unique        Optional. Whether to enforce a single metadata value for the
 *                              given key. If true, and the object already has a value for
 *                              the key, no change will be made. Default: false.
 *
 * @return int|bool The meta ID on successful update, false on failure.
 */
function bboss_moderation_add_meta( $moderation_id, $meta_key, $meta_value, $unique = false ) {
	add_filter( 'query', 'bp_filter_metaid_column_name' );
	$retval = add_metadata( 'moderation', $moderation_id, $meta_key, $meta_value, $unique );
	remove_filter( 'query', 'bp_filter_metaid_column_name' );

	return $retval;
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_moderation_add_meta' ) && ! function_exists( 'bp_moderation_add_meta' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_moderation_add_meta()}.
	 *
	 * @deprecated Use bboss_moderation_add_meta() instead.
	 */
	function bp_moderation_add_meta( $moderation_id, $meta_key, $meta_value, $unique = false ) {
		return bboss_moderation_add_meta( $moderation_id, $meta_key, $meta_value, $unique );
	}
}
// @bbwt-com-end compat-layer


/**
 * Update a piece of moderation meta.
 *
 * @since BuddyBoss 1.5.6
 *
 * @param int    $moderation_id ID of the moderation item whose metadata is being updated.
 * @param string $meta_key      Key of the metadata being updated.
 * @param mixed  $meta_value    Value to be set.
 * @param mixed  $prev_value    Optional. If specified, only update existing metadata entries
 *                              with the specified value. Otherwise, update all entries.
 *
 * @return bool|int Returns false on failure. On successful update of existing
 *                  metadata, returns true. On successful creation of new metadata,
 *                  returns the integer ID of the new metadata row.
 */
function bboss_moderation_update_meta( $moderation_id, $meta_key, $meta_value, $prev_value = '' ) {
	add_filter( 'query', 'bp_filter_metaid_column_name' );
	$retval = update_metadata( 'moderation', $moderation_id, $meta_key, $meta_value, $prev_value );
	remove_filter( 'query', 'bp_filter_metaid_column_name' );

	return $retval;
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_moderation_update_meta' ) && ! function_exists( 'bp_moderation_update_meta' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_moderation_update_meta()}.
	 *
	 * @deprecated Use bboss_moderation_update_meta() instead.
	 */
	function bp_moderation_update_meta( $moderation_id, $meta_key, $meta_value, $prev_value = '' ) {
		return bboss_moderation_update_meta( $moderation_id, $meta_key, $meta_value, $prev_value );
	}
}
// @bbwt-com-end compat-layer


/**
 * Delete a meta entry from the DB for an moderation item.
 *
 * @since BuddyBoss 1.5.6
 *
 * @global wpdb  $wpdb          WordPress database abstraction object.
 *
 * @param string $meta_key      Optional. The key of the metadata being deleted. If
 *                              omitted, all metadata associated with the moderation
 *                              item will be deleted.
 * @param string $meta_value    Optional. If present, the metadata will only be
 *                              deleted if the meta_value matches this parameter.
 * @param bool   $delete_all    Optional. If true, delete matching metadata entries
 *                              for all objects, ignoring the specified object_id. Otherwise,
 *                              only delete matching metadata entries for the specified
 *                              moderation item. Default: false.
 * @param int    $moderation_id ID of the moderation item whose metadata is being deleted.
 *
 * @param int    $moderation_id ID of the moderation item whose metadata is being deleted.
 *
 * @return bool True on success, false on failure.
 */
function bboss_moderation_delete_meta( $moderation_id, $meta_key = '', $meta_value = '', $delete_all = false ) {

	// Legacy - if no meta_key is passed, delete all for the item.
	if ( empty( $meta_key ) ) {
		$all_meta = bboss_moderation_get_meta( $moderation_id );
		$keys     = ! empty( $all_meta ) ? array_keys( $all_meta ) : array();

		// With no meta_key, ignore $delete_all.
		$delete_all = false;
	} else {
		$keys = array( $meta_key );
	}

	$retval = true;

	add_filter( 'query', 'bp_filter_metaid_column_name' );
	foreach ( $keys as $key ) {
		$retval = delete_metadata( 'moderation', $moderation_id, $key, $meta_value, $delete_all );
	}
	remove_filter( 'query', 'bp_filter_metaid_column_name' );

	return $retval;
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_moderation_delete_meta' ) && ! function_exists( 'bp_moderation_delete_meta' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_moderation_delete_meta()}.
	 *
	 * @deprecated Use bboss_moderation_delete_meta() instead.
	 */
	function bp_moderation_delete_meta( $moderation_id, $meta_key = '', $meta_value = '', $delete_all = false ) {
		return bboss_moderation_delete_meta( $moderation_id, $meta_key, $meta_value, $delete_all );
	}
}
// @bbwt-com-end compat-layer


/**
 * Get metadata for a given suspend item.
 *
 * @since BuddyBoss 2.7.30
 *
 * @param int    $suspend_id ID of the suspend item whose metadata is being requested.
 * @param string $meta_key   Optional. If present, only the metadata matching
 *                           that meta key will be returned. Otherwise, all metadata for the
 *                           moderation item will be fetched.
 * @param bool   $single     Optional. If true, return only the first value of the
 *                           specified meta_key. This parameter has no effect if meta_key is not
 *                           specified. Default: true.
 *
 * @return mixed The meta value(s) being requested.
 */
function bboss_suspend_get_meta( $suspend_id = 0, $meta_key = '', $single = true ) {
	add_filter( 'query', 'bp_filter_metaid_column_name' );
	$retval = get_metadata( 'suspend', $suspend_id, $meta_key, $single );
	remove_filter( 'query', 'bp_filter_metaid_column_name' );

	/**
	 * Filters the metadata for a specified suspend item.
	 *
	 * @since BuddyBoss 2.7.30
	 *
	 * @param mixed  $retval     The meta values for the moderation item.
	 * @param int    $suspend_id ID of the suspend item.
	 * @param string $meta_key   Meta key for the value being requested.
	 * @param bool   $single     Whether to return one matched meta key row or all.
	 */
	return apply_filters( 'bb_suspend_get_meta', $retval, $suspend_id, $meta_key, $single );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_suspend_get_meta' ) && ! function_exists( 'bb_suspend_get_meta' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_suspend_get_meta()}.
	 *
	 * @deprecated Use bboss_suspend_get_meta() instead.
	 */
	function bb_suspend_get_meta( $suspend_id = 0, $meta_key = '', $single = true ) {
		return bboss_suspend_get_meta( $suspend_id, $meta_key, $single );
	}
}
// @bbwt-com-end compat-layer


/**
 * Add a piece of suspend metadata.
 *
 * @since BuddyBoss 2.7.30
 *
 * @param int    $suspend_id ID of the suspend item.
 * @param string $meta_key   Metadata key.
 * @param mixed  $meta_value Metadata value.
 * @param bool   $unique     Optional. Whether to enforce a single metadata value for the
 *                           given key. If true, and the object already has a value for
 *                           the key, no change will be made. Default: false.
 *
 * @return int|bool The meta ID on successful update, false on failure.
 */
function bboss_suspend_add_meta( $suspend_id, $meta_key, $meta_value, $unique = false ) {
	add_filter( 'query', 'bp_filter_metaid_column_name' );
	$retval = add_metadata( 'suspend', $suspend_id, $meta_key, $meta_value, $unique );
	remove_filter( 'query', 'bp_filter_metaid_column_name' );

	return $retval;
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_suspend_add_meta' ) && ! function_exists( 'bb_suspend_add_meta' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_suspend_add_meta()}.
	 *
	 * @deprecated Use bboss_suspend_add_meta() instead.
	 */
	function bb_suspend_add_meta( $suspend_id, $meta_key, $meta_value, $unique = false ) {
		return bboss_suspend_add_meta( $suspend_id, $meta_key, $meta_value, $unique );
	}
}
// @bbwt-com-end compat-layer


/**
 * Update a piece of suspend meta.
 *
 * @since BuddyBoss 2.7.30
 *
 * @param int    $suspend_id ID of the suspend item whose metadata is being updated.
 * @param string $meta_key   Key of the metadata being updated.
 * @param mixed  $meta_value Value to be set.
 * @param mixed  $prev_value Optional. If specified, only update existing metadata entries
 *                           with the specified value. Otherwise, update all entries.
 *
 * @return bool|int Returns false on failure. On successful update of existing
 *                  metadata returns true. On successful creation of new metadata,
 *                  returns the integer ID of the new metadata row.
 */
function bboss_suspend_update_meta( $suspend_id, $meta_key, $meta_value, $prev_value = '' ) {
	add_filter( 'query', 'bp_filter_metaid_column_name' );
	$retval = update_metadata( 'suspend', $suspend_id, $meta_key, $meta_value, $prev_value );
	remove_filter( 'query', 'bp_filter_metaid_column_name' );

	return $retval;
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_suspend_update_meta' ) && ! function_exists( 'bb_suspend_update_meta' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_suspend_update_meta()}.
	 *
	 * @deprecated Use bboss_suspend_update_meta() instead.
	 */
	function bb_suspend_update_meta( $suspend_id, $meta_key, $meta_value, $prev_value = '' ) {
		return bboss_suspend_update_meta( $suspend_id, $meta_key, $meta_value, $prev_value );
	}
}
// @bbwt-com-end compat-layer


/**
 * Delete a meta entry from the DB for a suspend item.
 *
 * @since BuddyBoss 2.7.30
 *
 * @global wpdb  $wpdb       WordPress database abstraction object.
 *
 * @param string $meta_key   Optional. The key of the metadata being deleted. If
 *                           omitted, all metadata associated with the suspend
 *                           item will be deleted.
 * @param string $meta_value Optional. If present, the metadata will only be
 *                           deleted if the meta_value matches this parameter.
 * @param bool   $delete_all Optional. If true, delete matching metadata entries
 *                           for all objects, ignore the specified object_id.
 *                           Otherwise,
 *                           only delete matching metadata entries for the specified
 *                           moderation item.
 *                           Default: false.
 *
 * @param int    $suspend_id ID of the suspend item whose metadata is being deleted.
 *
 * @return bool True on success, false on failure.
 */
function bboss_suspend_delete_meta( $suspend_id, $meta_key = '', $meta_value = '', $delete_all = false ) {
	// Legacy - if no meta_key is passed, delete all for the item.
	if ( empty( $meta_key ) ) {
		$all_meta = bp_suspend_get_meta( $suspend_id );
		$keys     = ! empty( $all_meta ) ? array_keys( $all_meta ) : array();

		// With no meta_key, ignore $delete_all.
		$delete_all = false;

		unset( $all_meta );
	} else {
		$keys = array( $meta_key );
	}

	$retval = true;

	add_filter( 'query', 'bp_filter_metaid_column_name' );
	foreach ( $keys as $key ) {
		$retval = delete_metadata( 'suspend', $suspend_id, $key, $meta_value, $delete_all );
	}
	remove_filter( 'query', 'bp_filter_metaid_column_name' );

	unset( $keys );

	return $retval;
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_suspend_delete_meta' ) && ! function_exists( 'bb_suspend_delete_meta' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_suspend_delete_meta()}.
	 *
	 * @deprecated Use bboss_suspend_delete_meta() instead.
	 */
	function bb_suspend_delete_meta( $suspend_id, $meta_key = '', $meta_value = '', $delete_all = false ) {
		return bboss_suspend_delete_meta( $suspend_id, $meta_key, $meta_value, $delete_all );
	}
}
// @bbwt-com-end compat-layer



/** Setting *********************************************************************/

/**
 * Checks if Moderation Member blocking feature is enabled.
 *
 * @since BuddyBoss 1.5.6
 *
 * @param int $default bool Optional.Default value true.
 *
 * @return bool Is search autocomplete enabled or not
 * @uses  get_option() To get the bp_search_autocomplete option
 */
function bboss_is_moderation_member_blocking_enable( $default = 0 ) {
	return (bool) apply_filters( 'bp_is_moderation_member_blocking_enable', (bool) get_option( 'bpm_blocking_member_blocking', $default ) );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_is_moderation_member_blocking_enable' ) && ! function_exists( 'bp_is_moderation_member_blocking_enable' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_is_moderation_member_blocking_enable()}.
	 *
	 * @deprecated Use bboss_is_moderation_member_blocking_enable() instead.
	 */
	function bp_is_moderation_member_blocking_enable( $default = 0 ) {
		return bboss_is_moderation_member_blocking_enable( $default );
	}
}
// @bbwt-com-end compat-layer


/**
 * Checks if Moderation Member auto suspend feature is enabled.
 *
 * @since BuddyBoss 1.5.6
 *
 * @param int $default bool Optional.Default value true.
 *
 * @return bool Is search autocomplete enabled or not
 * @uses  get_option() To get the bp_search_autocomplete option
 */
function bboss_is_moderation_auto_suspend_enable( $default = 0 ) {
	return (bool) apply_filters( 'bp_is_moderation_auto_suspend_enable', (bool) get_option( 'bpm_blocking_auto_suspend', $default ) );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_is_moderation_auto_suspend_enable' ) && ! function_exists( 'bp_is_moderation_auto_suspend_enable' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_is_moderation_auto_suspend_enable()}.
	 *
	 * @deprecated Use bboss_is_moderation_auto_suspend_enable() instead.
	 */
	function bp_is_moderation_auto_suspend_enable( $default = 0 ) {
		return bboss_is_moderation_auto_suspend_enable( $default );
	}
}
// @bbwt-com-end compat-layer


/**
 * Checks if Moderation Member auto suspend feature is enabled.
 *
 * @since BuddyBoss 1.5.6
 *
 * @param int $default bool Optional.Default value true.
 *
 * @return bool Is search autocomplete enabled or not
 * @uses  get_option() To get the bp_search_autocomplete option
 */
function bboss_moderation_auto_suspend_threshold( $default = 5 ) {

	return apply_filters( 'bp_moderation_auto_suspend_threshold', (int) bboss_moderation_get_setting( 'bpm_blocking_auto_suspend_threshold', $default ) );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_moderation_auto_suspend_threshold' ) && ! function_exists( 'bp_moderation_auto_suspend_threshold' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_moderation_auto_suspend_threshold()}.
	 *
	 * @deprecated Use bboss_moderation_auto_suspend_threshold() instead.
	 */
	function bp_moderation_auto_suspend_threshold( $default = 5 ) {
		return bboss_moderation_auto_suspend_threshold( $default );
	}
}
// @bbwt-com-end compat-layer


/**
 * Checks if Moderation Member reporting feature is enabled.
 *
 * @since BuddyBoss 2.1.1
 *
 * @param int $default bool Optional.Default value true.
 *
 * @return bool Is member reporting enabled or not.
 * @uses get_option() To get the bb_blocking_member_reporting option.
 */
function bboss_is_moderation_member_reporting_enable( $default = 0 ) {
	return (bool) apply_filters( 'bb_is_moderation_member_reporting_enable', (bool) get_option( 'bb_blocking_member_reporting', $default ) );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_is_moderation_member_reporting_enable' ) && ! function_exists( 'bb_is_moderation_member_reporting_enable' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_is_moderation_member_reporting_enable()}.
	 *
	 * @deprecated Use bboss_is_moderation_member_reporting_enable() instead.
	 */
	function bb_is_moderation_member_reporting_enable( $default = 0 ) {
		return bboss_is_moderation_member_reporting_enable( $default );
	}
}
// @bbwt-com-end compat-layer


/**
 * Checks if Moderation Member auto suspend report feature is enabled.
 *
 * @since BuddyBoss 2.1.1
 *
 * @param int $default bool Optional.Default value true.
 *
 * @return bool Is auto suspend report enabled or not.
 * @uses get_option() To get the bb_reporting_auto_suspend option.
 */
function bboss_is_moderation_auto_suspend_report_enable( $default = 0 ) {
	return (bool) apply_filters( 'bb_is_moderation_auto_suspend_report_enable', (bool) get_option( 'bb_reporting_auto_suspend', $default ) );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_is_moderation_auto_suspend_report_enable' ) && ! function_exists( 'bb_is_moderation_auto_suspend_report_enable' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_is_moderation_auto_suspend_report_enable()}.
	 *
	 * @deprecated Use bboss_is_moderation_auto_suspend_report_enable() instead.
	 */
	function bb_is_moderation_auto_suspend_report_enable( $default = 0 ) {
		return bboss_is_moderation_auto_suspend_report_enable( $default );
	}
}
// @bbwt-com-end compat-layer


/**
 * Checks if Moderation Member auto suspend report feature is enabled.
 *
 * @since BuddyBoss 2.1.1
 *
 * @param int $default bool Optional.Default value true.
 *
 * @return bool Is search autocomplete enabled or not.
 * @uses bboss_moderation_get_setting() To get the bb_reporting_auto_suspend_threshold option.
 */
function bboss_moderation_auto_suspend_report_threshold( $default = 5 ) {
	return apply_filters( 'bb_moderation_auto_suspend_report_threshold', (int) bboss_moderation_get_setting( 'bb_reporting_auto_suspend_threshold', $default ) );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_moderation_auto_suspend_report_threshold' ) && ! function_exists( 'bb_moderation_auto_suspend_report_threshold' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_moderation_auto_suspend_report_threshold()}.
	 *
	 * @deprecated Use bboss_moderation_auto_suspend_report_threshold() instead.
	 */
	function bb_moderation_auto_suspend_report_threshold( $default = 5 ) {
		return bboss_moderation_auto_suspend_report_threshold( $default );
	}
}
// @bbwt-com-end compat-layer


/**
 * Checks if Moderation Member auto suspend email notification feature is enabled.
 *
 * @since BuddyBoss 1.5.6
 *
 * @param int $default bool Optional.Default value true.
 *
 * @return bool Is search autocomplete enabled or not
 * @uses  get_option() To get the bp_search_autocomplete option
 */
function bboss_is_moderation_blocking_email_notification_enable( $default = 0 ) {
	return (bool) apply_filters( 'bp_is_moderation_blocking_email_notification_enable', (bool) get_option( 'bpm_blocking_email_notification', $default ) );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_is_moderation_blocking_email_notification_enable' ) && ! function_exists( 'bp_is_moderation_blocking_email_notification_enable' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_is_moderation_blocking_email_notification_enable()}.
	 *
	 * @deprecated Use bboss_is_moderation_blocking_email_notification_enable() instead.
	 */
	function bp_is_moderation_blocking_email_notification_enable( $default = 0 ) {
		return bboss_is_moderation_blocking_email_notification_enable( $default );
	}
}
// @bbwt-com-end compat-layer


/**
 * Checks if Moderation content reporting feature is enabled.
 *
 * @since BuddyBoss 1.5.6
 *
 * @param int    $default      bool Optional.Default value true.
 * @param string $content_type content type.
 *
 * @return bool Is search autocomplete enabled or not
 * @uses  get_option() To get the bp_search_autocomplete option
 */
function bboss_is_moderation_content_reporting_enable( $default = 0, $content_type = '' ) {

	if ( BBoss_Moderation_Members::$moderation_type_report === $content_type ) {
		$content_type = BBoss_Moderation_Members::$moderation_type_report;

		// Check for folder type and content type as document.
	} elseif ( BBoss_Moderation_Folder::$moderation_type === $content_type ) {
		$content_type = BBoss_Moderation_Document::$moderation_type;

		// Check for album type and content type as media.
	} elseif ( BBoss_Moderation_Album::$moderation_type === $content_type ) {
		$content_type = BBoss_Moderation_Media::$moderation_type;

		// Check for message type and content type as user.
	} elseif ( BBoss_Moderation_Message::$moderation_type === $content_type ) {
		return bboss_is_moderation_member_blocking_enable( 0 );
	}

	$settings = (array) get_option( 'bpm_reporting_content_reporting', array() );

	if ( BBoss_Moderation_Members::$moderation_type_report === $content_type ) {
		$settings[ $content_type ] = bboss_is_moderation_member_reporting_enable();
	}

	if ( ! isset( $settings[ $content_type ] ) || empty( $settings[ $content_type ] ) ) {
		if ( empty( $settings ) ) {
			$settings = array();
		}
		$settings[ $content_type ] = $default;
	}

	return (bool) apply_filters( 'bp_is_moderation_content_reporting_enable', (bool) $settings[ $content_type ], $content_type );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_is_moderation_content_reporting_enable' ) && ! function_exists( 'bp_is_moderation_content_reporting_enable' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_is_moderation_content_reporting_enable()}.
	 *
	 * @deprecated Use bboss_is_moderation_content_reporting_enable() instead.
	 */
	function bp_is_moderation_content_reporting_enable( $default = 0, $content_type = '' ) {
		return bboss_is_moderation_content_reporting_enable( $default, $content_type );
	}
}
// @bbwt-com-end compat-layer


/**
 * Checks if Moderation content auto hide feature is enabled.
 *
 * @since BuddyBoss 1.5.6
 *
 * @param int    $default bool Optional.Default value true.
 * @param string $content_type content type.
 *
 * @return bool Is search autocomplete enabled or not
 * @uses  get_option() To get the bp_search_autocomplete option
 */
function bboss_is_moderation_auto_hide_enable( $default = 0, $content_type = '' ) {
	$is_enabled = bboss_is_moderation_content_reporting_enable( false, $content_type );
	if ( empty( $is_enabled ) ) {
		return false;
	}

	$settings = get_option( 'bpm_reporting_auto_hide', array() );
	if ( ! isset( $settings[ $content_type ] ) || empty( $settings[ $content_type ] ) ) {
		if ( empty( $settings ) ) {
			$settings = array();
		}
		$settings[ $content_type ] = $default;
	}

	return (bool) apply_filters( 'bp_is_moderation_auto_hide_enable', (bool) $settings[ $content_type ], $content_type );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_is_moderation_auto_hide_enable' ) && ! function_exists( 'bp_is_moderation_auto_hide_enable' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_is_moderation_auto_hide_enable()}.
	 *
	 * @deprecated Use bboss_is_moderation_auto_hide_enable() instead.
	 */
	function bp_is_moderation_auto_hide_enable( $default = 0, $content_type = '' ) {
		return bboss_is_moderation_auto_hide_enable( $default, $content_type );
	}
}
// @bbwt-com-end compat-layer


/**
 * Get threshold velue for content reporting.
 *
 * @since BuddyBoss 1.5.6
 *
 * @param int    $default bool Optional.Default value true.
 * @param string $content_type content type.
 *
 * @return bool Is search autocomplete enabled or not
 * @uses  get_option() To get the bp_search_autocomplete option
 */
function bboss_moderation_reporting_auto_hide_threshold( $default = 5, $content_type = '' ) {

	$settings = get_option( 'bpm_reporting_auto_hide_threshold', array() );

	if ( ! isset( $settings[ $content_type ] ) || empty( $settings[ $content_type ] ) ) {
		if ( empty( $settings ) ) {
			$settings = array();
		}
		$settings[ $content_type ] = $default;
	}

	return apply_filters( 'bp_moderation_reporting_auto_hide_threshold', (int) $settings[ $content_type ], $content_type );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_moderation_reporting_auto_hide_threshold' ) && ! function_exists( 'bp_moderation_reporting_auto_hide_threshold' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_moderation_reporting_auto_hide_threshold()}.
	 *
	 * @deprecated Use bboss_moderation_reporting_auto_hide_threshold() instead.
	 */
	function bp_moderation_reporting_auto_hide_threshold( $default = 5, $content_type = '' ) {
		return bboss_moderation_reporting_auto_hide_threshold( $default, $content_type );
	}
}
// @bbwt-com-end compat-layer


/**
 * Checks if Moderation content auto hide email notification feature is enabled.
 *
 * @since BuddyBoss 1.5.6
 *
 * @param int $default bool Optional.Default value true.
 *
 * @return bool Is search autocomplete enabled or not
 * @uses  get_option() To get the bp_search_autocomplete option
 */
function bboss_is_moderation_reporting_email_notification_enable( $default = 0 ) {
	return (bool) apply_filters( 'bp_is_moderation_reporting_email_notification_enable', (bool) get_option( 'bpm_reporting_email_notification', $default ) );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_is_moderation_reporting_email_notification_enable' ) && ! function_exists( 'bp_is_moderation_reporting_email_notification_enable' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_is_moderation_reporting_email_notification_enable()}.
	 *
	 * @deprecated Use bboss_is_moderation_reporting_email_notification_enable() instead.
	 */
	function bp_is_moderation_reporting_email_notification_enable( $default = 0 ) {
		return bboss_is_moderation_reporting_email_notification_enable( $default );
	}
}
// @bbwt-com-end compat-layer


/** Other *********************************************************************/

/**
 * Function get content owner id.
 *
 * @since BuddyBoss 1.5.6
 *
 * @param int    $moderation_item_id   content id.
 * @param string $moderation_item_type content type.
 *
 * @return int|array
 */
function bboss_moderation_get_content_owner_id( $moderation_item_id, $moderation_item_type ) {

	$user_ids = 0;
	$class    = BBoss_Moderation_Abstract::get_class( $moderation_item_type );

	if ( method_exists( $class, 'get_content_owner_id' ) ) {
		$user_ids = $class::get_content_owner_id( $moderation_item_id );
	}

	return is_array( $user_ids ) ? $user_ids : (int) $user_ids;
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_moderation_get_content_owner_id' ) && ! function_exists( 'bp_moderation_get_content_owner_id' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_moderation_get_content_owner_id()}.
	 *
	 * @deprecated Use bboss_moderation_get_content_owner_id() instead.
	 */
	function bp_moderation_get_content_owner_id( $moderation_item_id, $moderation_item_type ) {
		return bboss_moderation_get_content_owner_id( $moderation_item_id, $moderation_item_type );
	}
}
// @bbwt-com-end compat-layer


/**
 * Function to get the content based on type.
 *
 * @since BuddyBoss 1.5.6
 *
 * @param int    $moderation_item_id   moderation id to get the content.
 * @param string $moderation_item_type item type.
 *
 * @return string
 */
function bboss_moderation_get_permalink( $moderation_item_id, $moderation_item_type ) {

	$link  = '';
	$class = BBoss_Moderation_Abstract::get_class( $moderation_item_type );

	if ( method_exists( $class, 'get_permalink' ) ) {
		$link = $class::get_permalink( $moderation_item_id );
	}

	return $link;
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_moderation_get_permalink' ) && ! function_exists( 'bp_moderation_get_permalink' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_moderation_get_permalink()}.
	 *
	 * @deprecated Use bboss_moderation_get_permalink() instead.
	 */
	function bp_moderation_get_permalink( $moderation_item_id, $moderation_item_type ) {
		return bboss_moderation_get_permalink( $moderation_item_id, $moderation_item_type );
	}
}
// @bbwt-com-end compat-layer


/**
 * Function to send member suspension email
 *
 * @since BuddyBoss 1.5.6
 *
 * @param string $email  user email address.
 * @param array  $tokens user details.
 *
 * @return bool|BBoss_Email|WP_Error
 */
function bboss_moderation_member_suspend_email( $email, $tokens ) {
	return bboss_send_email(
		'user-moderation-email',
		$email,
		array(
			'tokens' => array(
				'user.name'    => $tokens['user_name'],
				'timesblocked' => $tokens['times_blocked'],
				'user.link'    => $tokens['member_link'],
				'reportlink'   => $tokens['report_link'],
			),
		)
	);
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_moderation_member_suspend_email' ) && ! function_exists( 'bp_moderation_member_suspend_email' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_moderation_member_suspend_email()}.
	 *
	 * @deprecated Use bboss_moderation_member_suspend_email() instead.
	 */
	function bp_moderation_member_suspend_email( $email, $tokens ) {
		return bboss_moderation_member_suspend_email( $email, $tokens );
	}
}
// @bbwt-com-end compat-layer


/**
 * Function to send email when content is auto hidden
 *
 * @since BuddyBoss 1.5.6
 *
 * @param string $email  user email.
 * @param array  $tokens email tokens.
 *
 * @return bool|BBoss_Email|WP_Error
 */
function bboss_moderation_content_hide_email( $email, $tokens ) {
	return bboss_send_email(
		'content-moderation-email',
		$email,
		array(
			'tokens' => array(
				'content.type'  => $tokens['content_type'],
				'content.owner' => $tokens['content_owner'],
				'timesreported' => $tokens['content_timesreported'],
				'content.link'  => $tokens['content_link'],
				'reportlink'    => $tokens['content_reportlink'],
			),
		)
	);
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_moderation_content_hide_email' ) && ! function_exists( 'bp_moderation_content_hide_email' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_moderation_content_hide_email()}.
	 *
	 * @deprecated Use bboss_moderation_content_hide_email() instead.
	 */
	function bp_moderation_content_hide_email( $email, $tokens ) {
		return bboss_moderation_content_hide_email( $email, $tokens );
	}
}
// @bbwt-com-end compat-layer


/**
 * Function to get the moderation item count based on status.
 *
 * @since BuddyBoss 1.5.6
 *
 * @param array $args arguments array.
 *
 * @return mixed|void
 */
function bboss_moderation_item_count( $args = array() ) {
	$moderation_request_args = array(
		'per_page'    => 1,
		'count_total' => true,
	);

	$moderation_request_args = bboss_parse_args( $args, $moderation_request_args );

	$result = BBoss_Moderation::get( $moderation_request_args );

	return apply_filters( 'bp_moderation_item_count', ! empty( $result['total'] ) ? $result['total'] : 0 );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_moderation_item_count' ) && ! function_exists( 'bp_moderation_item_count' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_moderation_item_count()}.
	 *
	 * @deprecated Use bboss_moderation_item_count() instead.
	 */
	function bp_moderation_item_count( $args = array() ) {
		return bboss_moderation_item_count( $args );
	}
}
// @bbwt-com-end compat-layer


/**
 * Function to get content report type.
 *
 * @since BuddyBoss 1.7.3
 *
 * @param string $item_type Item type.
 * @param int    $item_id   Item id.
 *
 * @return mixed|void
 */
function bboss_moderation_get_report_type( $item_type, $item_id ) {
	if ( ! $item_type || ! $item_id ) {
		return false;
	}

	/**
	 * Filters the reported content type
	 *
	 * @since BuddyBoss 1.7.3
	 *
	 * @param string $content_type Content type.
	 * @param int    $item_id      Item id.
	 */
	return apply_filters( "bp_moderation_{$item_type}_report_content_type", esc_html__( 'Post', 'buddyboss' ), $item_id );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_moderation_get_report_type' ) && ! function_exists( 'bp_moderation_get_report_type' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_moderation_get_report_type()}.
	 *
	 * @deprecated Use bboss_moderation_get_report_type() instead.
	 */
	function bp_moderation_get_report_type( $item_type, $item_id ) {
		return bboss_moderation_get_report_type( $item_type, $item_id );
	}
}
// @bbwt-com-end compat-layer


/**
 * Function to get report button text.
 *
 * @since BuddyBoss 1.7.3
 *
 * @param string $item_type Item type.
 * @param int    $item_id   Item id.
 *
 * @return false|mixed|void
 */
function bboss_moderation_get_report_button_text( $item_type, $item_id ) {
	if ( ! $item_type || ! $item_id ) {
		return false;
	}

	/**
	 * Filters the report button text for different components
	 *
	 * @since BuddyBoss 1.7.2
	 *
	 * @param string $button_text Button text.
	 * @param int    $item_id     Item id.
	 */
	return apply_filters( "bb_moderation_{$item_type}_report_button_text", esc_html__( 'Report', 'buddyboss' ), $item_id );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_moderation_get_report_button_text' ) && ! function_exists( 'bp_moderation_get_report_button_text' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_moderation_get_report_button_text()}.
	 *
	 * @deprecated Use bboss_moderation_get_report_button_text() instead.
	 */
	function bp_moderation_get_report_button_text( $item_type, $item_id ) {
		return bboss_moderation_get_report_button_text( $item_type, $item_id );
	}
}
// @bbwt-com-end compat-layer


/**
 * Function to get reported button text.
 *
 * @since BuddyBoss 1.7.3
 *
 * @param string $item_type Item type.
 * @param int    $item_id   Item id.
 *
 * @return false|mixed|void
 */
function bboss_moderation_get_reported_button_text( $item_type, $item_id ) {
	if ( ! $item_type || ! $item_id ) {
		return false;
	}

	/**
	 * Filters the reported button text for different components
	 *
	 * @since BuddyBoss 1.7.2
	 *
	 * @param string $button_text Button text.
	 * @param int    $item_id     Item id.
	 */
	return apply_filters( "bb_moderation_{$item_type}_reported_button_text", esc_html__( 'Reported', 'buddyboss' ), $item_id );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_moderation_get_reported_button_text' ) && ! function_exists( 'bp_moderation_get_reported_button_text' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_moderation_get_reported_button_text()}.
	 *
	 * @deprecated Use bboss_moderation_get_reported_button_text() instead.
	 */
	function bp_moderation_get_reported_button_text( $item_type, $item_id ) {
		return bboss_moderation_get_reported_button_text( $item_type, $item_id );
	}
}
// @bbwt-com-end compat-layer


/**
 * Function to get reporting categories show when fields array.
 *
 * @since BuddyBoss 2.1.1
 *
 * @return array
 */
function bboss_moderation_get_reporting_category_fields_array() {

	$result = array(
		'content'         => esc_html__( 'Content', 'buddyboss' ),
		'members'         => esc_html__( 'Members', 'buddyboss' ),
		'content_members' => esc_html__( 'Content & Members', 'buddyboss' ),
	);

	/**
	 * Filters the reported button text for different components
	 *
	 * @since BuddyBoss 2.1.1
	 *
	 * @param array $result Options for show when field.
	 */
	return apply_filters( 'bb_moderation_get_reporting_category_fields_array', $result );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_moderation_get_reporting_category_fields_array' ) && ! function_exists( 'bb_moderation_get_reporting_category_fields_array' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_moderation_get_reporting_category_fields_array()}.
	 *
	 * @deprecated Use bboss_moderation_get_reporting_category_fields_array() instead.
	 */
	function bb_moderation_get_reporting_category_fields_array() {
		return bboss_moderation_get_reporting_category_fields_array();
	}
}
// @bbwt-com-end compat-layer


/**
 * Fetch the user id by blocked by.
 *
 * @since BuddyBoss 2.1.4
 *
 * @param int  $user_id User id.
 * @param bool $force   Whether to bypass the static cache or not.
 *
 * @return array|mixed
 */
function bboss_moderation_get_blocked_by_user_ids( $user_id = 0, $force = false ) {
	static $cache = array();
	global $wpdb;
	if ( empty( $user_id ) ) {
		$user_id = bboss_loggedin_user_id();
	}

	$cache_key = 'bb_moderation_blocked_by_' . $user_id;
	if ( ! isset( $cache[ $cache_key ] ) || $force ) {
		$sql  = bboss_moderation_get_blocked_by_sql( $user_id );
		$data = $wpdb->get_col( $sql ); // phpcs:ignore
		$data = ! empty( $data ) ? array_map( 'intval', $data ) : array();

		$cache[ $cache_key ] = $data;
	} else {
		$data = $cache[ $cache_key ];
	}

	return $data;
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_moderation_get_blocked_by_user_ids' ) && ! function_exists( 'bb_moderation_get_blocked_by_user_ids' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_moderation_get_blocked_by_user_ids()}.
	 *
	 * @deprecated Use bboss_moderation_get_blocked_by_user_ids() instead.
	 */
	function bb_moderation_get_blocked_by_user_ids( $user_id = 0, $force = false ) {
		return bboss_moderation_get_blocked_by_user_ids( $user_id, $force );
	}
}
// @bbwt-com-end compat-layer


/**
 * Return SQL to fetch the query for blocked by users.
 *
 * @since BuddyBoss 2.2.4
 *
 * @param int $user_id User ID.
 *
 * @return string|void
 */
function bboss_moderation_get_blocked_by_sql( $user_id = 0 ) {
	global $wpdb;
	$bp = bboss();

	if ( empty( $user_id ) ) {
		$user_id = bboss_loggedin_user_id();
	}

	$type = BBoss_Moderation_Members::$moderation_type;

	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	return $wpdb->prepare( "SELECT DISTINCT m.user_id FROM {$bp->moderation->table_name} s LEFT JOIN {$bp->moderation->table_name_reports} m ON m.moderation_id = s.id WHERE s.item_type = %s AND s.item_id = %d AND s.reported != 0 AND m.user_report != 1", $type, $user_id );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_moderation_get_blocked_by_sql' ) && ! function_exists( 'bb_moderation_get_blocked_by_sql' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_moderation_get_blocked_by_sql()}.
	 *
	 * @deprecated Use bboss_moderation_get_blocked_by_sql() instead.
	 */
	function bb_moderation_get_blocked_by_sql( $user_id = 0 ) {
		return bboss_moderation_get_blocked_by_sql( $user_id );
	}
}
// @bbwt-com-end compat-layer


/**
 * Check whether a user has been marked as a blocked by another user.
 *
 * @since BuddyBoss 2.1.4
 *
 * @param int $user_id The ID for the user.
 *
 * @return bool
 */
function bboss_moderation_is_user_blocked_by( $user_id ) {
	if ( ! bboss_is_moderation_member_blocking_enable( 0 ) ) {
		return false;
	}

	$blocked_by_members = bboss_moderation_get_blocked_by_user_ids();

	return ( ! empty( $blocked_by_members ) && in_array( (int) $user_id, $blocked_by_members, true ) );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_moderation_is_user_blocked_by' ) && ! function_exists( 'bb_moderation_is_user_blocked_by' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_moderation_is_user_blocked_by()}.
	 *
	 * @deprecated Use bboss_moderation_is_user_blocked_by() instead.
	 */
	function bb_moderation_is_user_blocked_by( $user_id ) {
		return bboss_moderation_is_user_blocked_by( $user_id );
	}
}
// @bbwt-com-end compat-layer


/**
 * Group organizers should be able to see the names/avatars of members in all places in their group,
 * even if they’re blocked by that member.
 *
 * @since BuddyBoss 2.1.4
 *
 * @param string $avatar_url     Updated avatar url.
 * @param string $old_avatar_url Old avatar url before updated.
 * @param array  $params         Array of parameters for the request.
 *
 * @return string $avatar_url    Updated avatar url.
 */
function bboss_moderation_fetch_avatar_url_filter( $avatar_url, $old_avatar_url, $params ) {
	// Check for group.
	if (
		bboss_is_active( 'groups' ) &&
		(
			bboss_is_group_members() ||
			(
				function_exists( 'bboss_bbp_is_forum_group_forum' ) && bboss_bbp_is_forum_group_forum()
			) ||
			(
				function_exists( 'bboss_get_group_current_admin_tab' ) &&
				'manage-members' === bboss_get_group_current_admin_tab()
			) ||
			(
				wp_doing_ajax() &&
				isset( $_POST['action'] ) &&
				'video_get_video_description' === $_POST['action']
			)
		)
	) {
		$group_id = bboss_get_current_group_id();
		if (
			bboss_groups_is_user_admin( bboss_loggedin_user_id(), $group_id ) ||
			bboss_groups_is_user_mod( bboss_loggedin_user_id(), $group_id )
		) {
			return $old_avatar_url;
		}
	}

	// Check for activity.
	$activity_id = bboss_is_active( 'activity' ) && function_exists( 'bboss_get_activity_id' ) ? bboss_get_activity_id() : 0;
	if ( ! empty( $activity_id ) ) {
		$activity = new BBoss_Activity_Activity( $activity_id );

		// check activity exists.
		if ( empty( $activity->id ) ) {
			return $avatar_url;
		}

		$activity_component = $activity->component;
		$group_id           = bboss_is_active( 'groups' ) && bboss()->groups->id === $activity->component ? $activity->item_id : 0;

		if ( ! empty( $group_id ) && 'groups' === $activity_component ) {
			if (
				bboss_groups_is_user_admin( bboss_loggedin_user_id(), $group_id ) ||
				bboss_groups_is_user_mod( bboss_loggedin_user_id(), $group_id )
			) {
				return $old_avatar_url;
			}
		}
	}

	return $avatar_url;
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_moderation_fetch_avatar_url_filter' ) && ! function_exists( 'bb_moderation_fetch_avatar_url_filter' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_moderation_fetch_avatar_url_filter()}.
	 *
	 * @deprecated Use bboss_moderation_fetch_avatar_url_filter() instead.
	 */
	function bb_moderation_fetch_avatar_url_filter( $avatar_url, $old_avatar_url, $params ) {
		return bboss_moderation_fetch_avatar_url_filter( $avatar_url, $old_avatar_url, $params );
	}
}
// @bbwt-com-end compat-layer


/**
 * Function to fetch the blocked by user label.
 *
 * @since BuddyBoss 2.1.4
 *
 * @param string $value   Current user display name.
 * @param int    $user_id User id.
 *
 * @return mixed|void
 */
function bboss_moderation_is_blocked_label( $value, $user_id = 0 ) {

	/**
	 * Filter to update is_blocked_label.
	 *
	 * @since BuddyBoss 2.1.4
	 *
	 * @param string $value   Current user display name.
	 * @param int    $user_id User id.
	 */
	return apply_filters( 'bb_moderation_is_blocked_label', $value, $user_id );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_moderation_is_blocked_label' ) && ! function_exists( 'bb_moderation_is_blocked_label' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_moderation_is_blocked_label()}.
	 *
	 * @deprecated Use bboss_moderation_is_blocked_label() instead.
	 */
	function bb_moderation_is_blocked_label( $value, $user_id = 0 ) {
		return bboss_moderation_is_blocked_label( $value, $user_id );
	}
}
// @bbwt-com-end compat-layer


/**
 * Function to fetch the blocked user label.
 *
 * @since BuddyBoss 2.1.4
 *
 * @param string $value   Current user display name.
 * @param int    $user_id User id.
 *
 * @return mixed|void
 */
function bboss_moderation_has_blocked_label( $value, $user_id = 0 ) {

	/**
	 * Filter to update has_blocked_label.
	 *
	 * @since BuddyBoss 2.1.4
	 *
	 * @param string $value   Current user display name.
	 * @param int    $user_id User id.
	 */
	return apply_filters( 'bb_moderation_has_blocked_label', $value, $user_id );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_moderation_has_blocked_label' ) && ! function_exists( 'bb_moderation_has_blocked_label' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_moderation_has_blocked_label()}.
	 *
	 * @deprecated Use bboss_moderation_has_blocked_label() instead.
	 */
	function bb_moderation_has_blocked_label( $value, $user_id = 0 ) {
		return bboss_moderation_has_blocked_label( $value, $user_id );
	}
}
// @bbwt-com-end compat-layer


/**
 * Function to fetch the blocked by user avatar.
 *
 * @since BuddyBoss 2.1.4
 *
 * @param int   $user_id User id.
 * @param array $args    Arguments passed to get_avatar_data(), after processing.
 *
 * @return mixed|void
 */
function bboss_moderation_is_blocked_avatar( $user_id, $args = array() ) {

	/**
	 * Filter to update is_blocked_avatar.
	 *
	 * @since BuddyBoss 2.1.4
	 *
	 * @param string Get default avatar image URL based on settings.
	 * @param int   $user_id User id.
	 * @param array $args    Arguments passed to get_avatar_data(), after processing.
	 */
	return apply_filters( 'bb_moderation_is_blocked_avatar', bboss_attachments_get_default_profile_group_avatar_image( array( 'object' => 'user' ) ), $user_id, $args );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_moderation_is_blocked_avatar' ) && ! function_exists( 'bb_moderation_is_blocked_avatar' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_moderation_is_blocked_avatar()}.
	 *
	 * @deprecated Use bboss_moderation_is_blocked_avatar() instead.
	 */
	function bb_moderation_is_blocked_avatar( $user_id, $args = array() ) {
		return bboss_moderation_is_blocked_avatar( $user_id, $args );
	}
}
// @bbwt-com-end compat-layer


/**
 * Function to fetch the blocked user avatar.
 *
 * @since BuddyBoss 2.1.4
 *
 * @param string $avatar_url Current avatar URL.
 * @param int    $user_id    User id.
 * @param array  $args       Arguments passed to get_avatar_data(), after processing.
 *
 * @return mixed|void
 */
function bboss_moderation_has_blocked_avatar( $avatar_url, $user_id, $args = array() ) {

	/**
	 * Filter to update has_blocked_avatar.
	 *
	 * @since BuddyBoss 2.1.4
	 *
	 * @param string $avatar_url Current avatar URL.
	 * @param int    $user_id    User id.
	 * @param array  $args       Arguments passed to get_avatar_data(), after processing.
	 */
	return apply_filters( 'bb_moderation_has_blocked_avatar', $avatar_url, $user_id, $args );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_moderation_has_blocked_avatar' ) && ! function_exists( 'bb_moderation_has_blocked_avatar' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_moderation_has_blocked_avatar()}.
	 *
	 * @deprecated Use bboss_moderation_has_blocked_avatar() instead.
	 */
	function bb_moderation_has_blocked_avatar( $avatar_url, $user_id, $args = array() ) {
		return bboss_moderation_has_blocked_avatar( $avatar_url, $user_id, $args );
	}
}
// @bbwt-com-end compat-layer


/**
 * Function to fetch the suspended by user label.
 *
 * @since BuddyBoss 2.1.4
 *
 * @param int $user_id User id.
 *
 * @return mixed|void
 */
function bboss_moderation_is_suspended_label( $user_id = 0 ) {

	/**
	 * Filter to update is_suspended_label.
	 *
	 * @since BuddyBoss 2.1.4
	 *
	 * @param string Default suspended label.
	 * @param int $user_id User id.
	 */
	return apply_filters( 'bb_moderation_is_suspended_label', esc_html__( 'Unknown Member', 'buddyboss' ), $user_id );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_moderation_is_suspended_label' ) && ! function_exists( 'bb_moderation_is_suspended_label' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_moderation_is_suspended_label()}.
	 *
	 * @deprecated Use bboss_moderation_is_suspended_label() instead.
	 */
	function bb_moderation_is_suspended_label( $user_id = 0 ) {
		return bboss_moderation_is_suspended_label( $user_id );
	}
}
// @bbwt-com-end compat-layer


/**
 * Function to fetch the suspended by user avatar.
 *
 * @since BuddyBoss 2.1.4
 *
 * @param int   $user_id User id.
 * @param array $args    Arguments passed to get_avatar_data(), after processing.
 *
 * @return mixed|void
 */
function bboss_moderation_is_suspended_avatar( $user_id = 0, $args = array() ) {

	/**
	 * Filter to update is_suspended_avatar.
	 *
	 * @since BuddyBoss 2.1.4
	 *
	 * @param string Get default avatar image URL based on settings.
	 * @param int   $user_id User id.
	 * @param array $args    Arguments passed to get_avatar_data(), after processing.
	 */
	return apply_filters( 'bb_moderation_is_suspended_avatar', bboss_attachments_get_default_profile_group_avatar_image( array( 'object' => 'user' ) ), $user_id, $args );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_moderation_is_suspended_avatar' ) && ! function_exists( 'bb_moderation_is_suspended_avatar' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_moderation_is_suspended_avatar()}.
	 *
	 * @deprecated Use bboss_moderation_is_suspended_avatar() instead.
	 */
	function bb_moderation_is_suspended_avatar( $user_id = 0, $args = array() ) {
		return bboss_moderation_is_suspended_avatar( $user_id, $args );
	}
}
// @bbwt-com-end compat-layer


/**
 * Function to fetch the delete user's label.
 *
 * @since BuddyBoss 2.2.4
 *
 * @return mixed|void
 */
function bboss_moderation_is_deleted_label() {

	/**
	 * Filter to fetch the delete user's label.
	 *
	 * @since BuddyBoss 2.2.4
	 *
	 * @param string Default delete label.
	 */
	return apply_filters( 'bb_moderation_is_deleted_label', esc_html__( 'Unknown Member', 'buddyboss' ) );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_moderation_is_deleted_label' ) && ! function_exists( 'bb_moderation_is_deleted_label' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_moderation_is_deleted_label()}.
	 *
	 * @deprecated Use bboss_moderation_is_deleted_label() instead.
	 */
	function bb_moderation_is_deleted_label() {
		return bboss_moderation_is_deleted_label();
	}
}
// @bbwt-com-end compat-layer


/**
 * Function to fetch the delete users avatar.
 *
 * @since BuddyBoss 2.2.4
 *
 * @return mixed|void
 */
function bboss_moderation_is_deleted_avatar() {

	/**
	 * Filter to fetch the delete users avatar.
	 *
	 * @since BuddyBoss 2.2.4
	 *
	 * @param string Get default avatar image URL based on settings.
	 */
	return apply_filters( 'bb_moderation_is_deleted_avatar', bboss_attachments_get_default_profile_group_avatar_image( array( 'object' => 'user' ) ) );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_moderation_is_deleted_avatar' ) && ! function_exists( 'bb_moderation_is_deleted_avatar' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_moderation_is_deleted_avatar()}.
	 *
	 * @deprecated Use bboss_moderation_is_deleted_avatar() instead.
	 */
	function bb_moderation_is_deleted_avatar() {
		return bboss_moderation_is_deleted_avatar();
	}
}
// @bbwt-com-end compat-layer


/**
 * Function will return text when current user blocked to other user.
 *
 * @since BuddyBoss 2.2.4
 *
 * @param string $value     Current content.
 * @param string $item_type Moderation type.
 * @param int    $item_id   Item id for the content. i.e - comment_id etc
 *
 * @return string
 */
function bboss_moderation_has_blocked_message( $value, $item_type = '', $item_id = 0 ) {

	/**
	 * Filter will return text when current user blocked to other user.
	 *
	 * @since BuddyBoss 2.2.4
	 *
	 * @param string $value     Current content.
	 * @param string $item_type Moderation type.
	 * @param int    $item_id   Item id for the content. i.e - comment_id etc
	 */

	return apply_filters( 'bb_moderation_has_blocked_message', $value, $item_type, $item_id );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_moderation_has_blocked_message' ) && ! function_exists( 'bb_moderation_has_blocked_message' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_moderation_has_blocked_message()}.
	 *
	 * @deprecated Use bboss_moderation_has_blocked_message() instead.
	 */
	function bb_moderation_has_blocked_message( $value, $item_type = '', $item_id = 0 ) {
		return bboss_moderation_has_blocked_message( $value, $item_type, $item_id );
	}
}
// @bbwt-com-end compat-layer


/**
 * Function will return text when current user blocked by other user.
 *
 * @since BuddyBoss 2.2.4
 *
 * @param string $value     Current content.
 * @param string $item_type Moderation type.
 * @param int    $item_id   Item id for the content. i.e - comment_id etc
 *
 * @return string
 */
function bboss_moderation_is_blocked_message( $value, $item_type = '', $item_id = 0 ) {

	/**
	 * Filter will return text when current user blocked by other user.
	 *
	 * @since BuddyBoss 2.2.4
	 *
	 * @param string $value     Current content.
	 * @param string $item_type Moderation type.
	 * @param int    $item_id   Item id for the content. i.e - comment_id etc
	 */
	return apply_filters( 'bb_moderation_is_blocked_message', $value, $item_type, $item_id );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_moderation_is_blocked_message' ) && ! function_exists( 'bb_moderation_is_blocked_message' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_moderation_is_blocked_message()}.
	 *
	 * @deprecated Use bboss_moderation_is_blocked_message() instead.
	 */
	function bb_moderation_is_blocked_message( $value, $item_type = '', $item_id = 0 ) {
		return bboss_moderation_is_blocked_message( $value, $item_type, $item_id );
	}
}
// @bbwt-com-end compat-layer


/**
 * Function will return text when user is suspended.
 *
 * @since BuddyBoss 2.2.4
 *
 * @param string $value     Current content.
 * @param string $item_type Moderation type.
 * @param int    $item_id   Item id for the content. i.e - comment_id etc
 *
 * @return string
 */
function bboss_moderation_is_suspended_message( $value, $item_type = '', $item_id = 0 ) {

	/**
	 * Filter will return text when user is suspended.
	 *
	 * @since BuddyBoss 2.2.4
	 *
	 * @param string $value     Current content.
	 * @param string $item_type Moderation type.
	 * @param int    $item_id   Item id for the content. i.e - comment_id etc
	 */

	return apply_filters( 'bb_moderation_is_suspended_message', $value, $item_type, $item_id );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_moderation_is_suspended_message' ) && ! function_exists( 'bb_moderation_is_suspended_message' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_moderation_is_suspended_message()}.
	 *
	 * @deprecated Use bboss_moderation_is_suspended_message() instead.
	 */
	function bb_moderation_is_suspended_message( $value, $item_type = '', $item_id = 0 ) {
		return bboss_moderation_is_suspended_message( $value, $item_type, $item_id );
	}
}
// @bbwt-com-end compat-layer


/**
 * Function will remove mention link from content if mentioned member is blocked/blokedby/suspended.
 *
 * @since BuddyBoss 2.2.7
 *
 * @param mixed $content Content.
 *
 * @return mixed
 */
function bboss_moderation_remove_mention_link( $content ) {

	if ( empty( $content ) ) {
		return $content;
	}

	$usernames = bboss_find_mentions_by_at_sign( array(), $content );

	// No mentions? Stop now!
	if ( empty( $usernames ) ) {
		return $content;
	}

	foreach ( (array) $usernames as $user_id => $username ) {
		if (
			bboss_moderation_is_user_blocked( $user_id ) ||
			bboss_moderation_is_user_blocked_by( $user_id ) ||
			bboss_moderation_is_user_suspended( $user_id )
		) {
			preg_match_all( "'<a\b[^>]*>@(.*?)<\/a>'si", $content, $content_matches, PREG_SET_ORDER );
			if ( ! empty( $content_matches ) ) {
				foreach ( $content_matches as $match ) {
					if ( false !== strpos( $match[0], '@' . $username ) ) {
						$content = str_replace( $match[0], '@' . $username, $content );
					}
				}
			}
		}
	}

	return $content;
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_moderation_remove_mention_link' ) && ! function_exists( 'bb_moderation_remove_mention_link' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_moderation_remove_mention_link()}.
	 *
	 * @deprecated Use bboss_moderation_remove_mention_link() instead.
	 */
	function bb_moderation_remove_mention_link( $content ) {
		return bboss_moderation_remove_mention_link( $content );
	}
}
// @bbwt-com-end compat-layer


/**
 * Fetch all suspended user_ids.
 *
 * @since BuddyBoss 2.2.7
 *
 * @param bool $force Bypass cache or not.
 *
 * @return array|mixed
 */
function bboss_moderation_get_suspended_user_ids( $force = false ) {
	static $cache = array();
	global $wpdb, $bp;

	$cache_key = 'bb_moderation_suspended_user_ids';
	if ( ! isset( $cache[ $cache_key ] ) || $force ) {
		$result = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT item_id FROM {$bp->moderation->table_name} WHERE item_type = %s AND user_suspended = 1", BBoss_Suspend_Member::$type ) ); // phpcs:ignore
		$data   = ! empty( $result ) ? array_map( 'intval', $result ) : array();

		$cache[ $cache_key ] = $data;
	} else {
		$data = $cache[ $cache_key ];
	}

	return $data;
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_moderation_get_suspended_user_ids' ) && ! function_exists( 'bb_moderation_get_suspended_user_ids' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_moderation_get_suspended_user_ids()}.
	 *
	 * @deprecated Use bboss_moderation_get_suspended_user_ids() instead.
	 */
	function bb_moderation_get_suspended_user_ids( $force = false ) {
		return bboss_moderation_get_suspended_user_ids( $force );
	}
}
// @bbwt-com-end compat-layer


/**
 * Fetch all moderated user or check the user is moderated or not.
 *
 * @since BuddyBoss 2.2.7
 *
 * @param int $user_id User ID.
 *
 * @return array|bool
 */
function bboss_moderation_moderated_user_ids( $user_id = 0 ) {
	static $cache = array();

	$cache_key = 'bb_moderation_moderated_user_ids';

	if ( ! empty( $user_id ) ) {
		$cache_key = 'bb_moderation_moderated_user_' . $user_id;
	}

	if ( isset( $cache[ $cache_key ] ) ) {
		return $cache[ $cache_key ];
	}

	$hidden_users_ids   = bboss_moderation_get_hidden_user_ids(); // Blocked user_ids.
	$blocked_by_members = bboss_moderation_get_blocked_by_user_ids(); // Blocked by user_ids.
	$suspended_user_id  = bboss_moderation_get_suspended_user_ids();

	$all_users = array();
	if ( ! empty( $hidden_users_ids ) ) {
		$all_users = array_unique( array_merge( $hidden_users_ids, $all_users ) );
	}

	if ( ! empty( $blocked_by_members ) ) {
		$all_users = array_unique( array_merge( $blocked_by_members, $all_users ) );
	}

	if ( ! empty( $suspended_user_id ) ) {
		$all_users = array_unique( array_merge( $suspended_user_id, $all_users ) );
	}

	if ( empty( $user_id ) ) {
		$cache[ $cache_key ] = $all_users;

		return $all_users;
	}

	$retval = in_array( $user_id, $all_users, true );

	$cache[ $cache_key ] = $retval;

	return $retval;
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_moderation_moderated_user_ids' ) && ! function_exists( 'bb_moderation_moderated_user_ids' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_moderation_moderated_user_ids()}.
	 *
	 * @deprecated Use bboss_moderation_moderated_user_ids() instead.
	 */
	function bb_moderation_moderated_user_ids( $user_id = 0 ) {
		return bboss_moderation_moderated_user_ids( $user_id );
	}
}
// @bbwt-com-end compat-layer


/**
 * Function will allow to send specific email/notification.
 *
 * @since BuddyBoss 2.2.7
 *
 * @param array $args It will contain type, recipient id, gorup id and author id.
 *
 * @return bool
 */
function bboss_moderation_allowed_specific_notification( $args ) {

	$r = bboss_parse_args(
		$args,
		array(
			'type'              => '',
			'group_id'          => '',
			'recipient_user_id' => '',
			'sender_id'         => '',
		)
	);

	$retval = false;
	if ( empty( $r['recipient_user_id'] ) ) {
		return $retval;
	}

	switch ( $r['type'] ) {
		case 'activity':
			if (
				bboss_moderation_is_user_suspended( $r['recipient_user_id'] ) ||
				(
					(
						empty( $r['group_id'] ) &&
						(
							bboss_moderation_is_user_blocked( $r['recipient_user_id'] ) ||
							bboss_moderation_is_user_blocked_by( $r['recipient_user_id'] )
						)
					) ||
					(
						bboss_is_active( 'groups' ) &&
						! empty( $r['group_id'] ) &&
						bboss_moderation_is_user_blocked_by( $r['recipient_user_id'] ) &&
						(
							! bboss_groups_is_user_admin( $r['sender_id'], $r['group_id'] ) &&
							! bboss_groups_is_user_mod( $r['sender_id'], $r['group_id'] )
						)
					)
				)
			) {
				$retval = true;
			}
			break;
		case 'messages':
			if (
				bboss_moderation_is_user_suspended( $r['recipient_user_id'] ) ||
				(
					(
						empty( $r['group_id'] ) &&
						(
							bboss_moderation_is_user_blocked( $r['recipient_user_id'] ) ||
							bboss_moderation_is_user_blocked_by( $r['recipient_user_id'] )
						)
					) ||
					(
						bboss_is_active( 'groups' ) &&
						! empty( $r['group_id'] ) &&
						bboss_moderation_is_user_blocked_by( $r['recipient_user_id'] ) &&
						(
							! bboss_groups_is_user_admin( $r['recipient_user_id'], $r['group_id'] ) &&
							! bboss_groups_is_user_mod( $r['recipient_user_id'], $r['group_id'] )
						)
					)
				)
			) {
				$retval = true;
			}
			break;
		case 'forums':
		case 'groups':
			if (
				bboss_moderation_is_user_suspended( $r['recipient_user_id'] ) ||
				(
					bboss_moderation_is_user_blocked_by( $r['recipient_user_id'] ) &&
					(
						(
							empty( $r['group_id'] ) &&
							(int) $r['recipient_user_id'] !== (int) $r['sender_id']
						)
						||
						(
							bboss_is_active( 'groups' ) &&
							! empty( $r['group_id'] ) &&
							(
								! bboss_groups_is_user_admin( $r['recipient_user_id'], $r['group_id'] ) &&
								! bboss_groups_is_user_mod( $r['recipient_user_id'], $r['group_id'] )
							)
						)
					)
				)
			) {
				$retval = true;
			}
			break;
		default:
			$retval = false;
	}

	return $retval;
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_moderation_allowed_specific_notification' ) && ! function_exists( 'bb_moderation_allowed_specific_notification' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_moderation_allowed_specific_notification()}.
	 *
	 * @deprecated Use bboss_moderation_allowed_specific_notification() instead.
	 */
	function bb_moderation_allowed_specific_notification( $args ) {
		return bboss_moderation_allowed_specific_notification( $args );
	}
}
// @bbwt-com-end compat-layer


/**
 * If the content has been changed by these filters bb_moderation_has_blocked_message,
 * bb_moderation_is_blocked_message, bb_moderation_is_suspended_message then
 * it will hide forums activity content from activity screen
 * which is created by blocked/blocked/suspended member.
 *
 * @since BuddyBoss 2.3.50
 *
 * @param int $activity_id Activity Id.
 *
 * @return bool
 */
function bboss_moderation_to_hide_forum_activity( $activity_id ) {
	$activity_data = new BBoss_Activity_Activity( $activity_id );
	$hide_activity = false;
	if (
		bboss_is_active( 'activity' ) &&
		bboss_is_active( 'forums' ) &&
		in_array(
			$activity_data->type,
			array(
				'bbp_reply_create',
			),
			true
		)
	) {
		if ( bboss_moderation_is_user_blocked( $activity_data->user_id ) ) {
			$content = bboss_moderation_has_blocked_message( $activity_data->content, BBoss_Moderation_Forum_Replies::$moderation_type, $activity_data->id );
			if ( $activity_data->content !== $content ) {
				$hide_activity = true;
			}
		}
		if ( bboss_moderation_is_user_blocked_by( $activity_data->user_id ) ) {
			$content = bboss_moderation_is_blocked_message( $activity_data->content, BBoss_Moderation_Forum_Replies::$moderation_type, $activity_data->id );
			if ( $activity_data->content !== $content ) {
				$hide_activity = true;
			}
		}
		if ( bboss_moderation_is_user_suspended( $activity_data->user_id ) ) {
			$content = bboss_moderation_is_suspended_message( $activity_data->content, BBoss_Moderation_Forum_Replies::$moderation_type, $activity_data->id );
			if ( $activity_data->content !== $content ) {
				$hide_activity = true;
			}
		}
	}

	return $hide_activity;
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_moderation_to_hide_forum_activity' ) && ! function_exists( 'bb_moderation_to_hide_forum_activity' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_moderation_to_hide_forum_activity()}.
	 *
	 * @deprecated Use bboss_moderation_to_hide_forum_activity() instead.
	 */
	function bb_moderation_to_hide_forum_activity( $activity_id ) {
		return bboss_moderation_to_hide_forum_activity( $activity_id );
	}
}
// @bbwt-com-end compat-layer


/**
 * Run Migration related to the Moderation.
 *
 * @since BuddyBoss 2.4.20
 *
 * @return void
 */
function bboss_moderation_migration_on_update() {
	global $wpdb;

	/**
	 * Migrate old data to new background jobs.
	 *
	 * @since BuddyBoss 2.4.20
	 */
	$sql = "DELETE FROM {$wpdb->options} WHERE `option_name` LIKE 'wp_1_bp_updater_batch_%' AND `option_value` LIKE '%BP_Suspend_%'";
	$wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

	// Run migration only if not run previously.
	$moderation_repaired = bboss_get_option( 'bb_moderation_migration_run', false );
	if ( true !== $moderation_repaired ) {
		$suspend_request_args = array(
			'in_types' => array( BBoss_Moderation_Members::$moderation_type ),
			'reported' => false,
			'per_page' => false,
		);

		$suspend_requests = BBoss_Moderation::get( $suspend_request_args );

		if ( ! empty( $suspend_requests['moderations'] ) ) {
			foreach ( $suspend_requests['moderations'] as $data ) {

				// update data for the blocked users.
				if ( ! empty( $data->reported ) ) {
					$args = array(
						'item_id'     => $data->item_id,
						'item_type'   => $data->item_type,
						'user_report' => ( $data->user_report ?? 0 ),
						'blog_id'     => $data->blog_id,
					);

					BBoss_Core_Suspend::add_suspend( $args );
				}

				// updated data based on suspend entries.
				if ( isset( $data->user_suspended ) ) {
					if ( ! empty( $data->user_suspended ) ) {
						BBoss_Suspend_Member::suspend_user( $data->item_id );
					} elseif (
						empty( $data->user_suspended ) &&
						empty( $data->user_report )
					) {
						BBoss_Suspend_Member::unsuspend_user( $data->item_id, array( 'hide_sitewide' => 1 ) );
					}
				}
			}
		}

		$hidden_request_args = array(
			'exclude_types' => array( BBoss_Moderation_Members::$moderation_type ),
			'per_page'      => false,
		);

		$hidden_requests = BBoss_Moderation::get( $hidden_request_args );

		if ( ! empty( $hidden_requests['moderations'] ) ) {
			foreach ( $hidden_requests['moderations'] as $data ) {

				// update data for the hidden data.
				if ( isset( $data->hide_sitewide ) ) {
					if ( ! empty( $data->hide_sitewide ) ) {
						$moderation = new BBoss_Moderation( $data->item_id, $data->item_type );
						$moderation->hide();
					} else {
						$args = array(
							'content_id'   => $data->item_id,
							'content_type' => $data->item_type,
						);

						if ( ! empty( $args['content_id'] ) && ! empty( $args['content_type'] ) ) {
							$moderation = new BBoss_Moderation( $args['content_id'], $args['content_type'] );

							if ( method_exists( $moderation, 'unhide_related_content' ) ) {
								$moderation->hide_sitewide = 0;

								$moderation->unhide_related_content();
								bboss_moderation_delete_meta( $moderation->id, '_hide_by' );

								/**
								 * Fires after a moderation report item has been unhide
								 *
								 * @since BuddyBoss 1.5.6
								 *
								 * @param BBoss_Moderation $this current class object.
								 */
								do_action( 'bp_moderation_after_unhide', $moderation );
							}
						}
					}
				}
			}
		}

		bboss_update_option( 'bb_moderation_migration_run', true );
	}

	/**
	 * Create new database table for suspend meta.
	 *
	 * @since BuddyBoss 2.7.30
	 */
	$bp_prefix  = function_exists( 'bboss_core_get_table_prefix' ) ? bboss_core_get_table_prefix() : $wpdb->base_prefix;
	$table_name = $bp_prefix . 'bp_suspend_meta';
	// Check if the 'bp_suspend_meta' table exists.
	$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ); // phpcs:ignore

	if ( $table_exists !== $table_name ) {
		$sql             = array();
		$charset_collate = $GLOBALS['wpdb']->get_charset_collate();
		$bp_prefix       = bboss_core_get_table_prefix();

		$sql[] = "CREATE TABLE {$bp_prefix}bp_suspend_meta (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			suspend_id bigint(20) NOT NULL,
			meta_key varchar(255) DEFAULT NULL,
			meta_value longtext DEFAULT NULL,
			PRIMARY KEY (id),
			KEY suspend_id (suspend_id),
			KEY meta_key (meta_key(191))
		) {$charset_collate};";

		if ( ! function_exists( 'dbDelta' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}

		dbDelta( $sql );
	}

	/**
	 * Add indexes for performance improvement.
	 *
	 * @since BuddyBoss 2.7.30
	 */
	$index_exists = $wpdb->get_var( "SHOW INDEX FROM {$bp_prefix}bp_suspend_details WHERE Key_name = 'user_id'" );
	if ( empty( $index_exists ) ) {
		$wpdb->query( "CREATE INDEX user_id ON {$bp_prefix}bp_suspend_details(user_id)" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	$index_exists = $wpdb->get_var( "SHOW INDEX FROM {$bp_prefix}bp_suspend WHERE Key_name = 'suspend_conditions'" );
	if ( empty( $index_exists ) ) {
		$wpdb->query( "CREATE INDEX suspend_conditions ON {$bp_prefix}bp_suspend(user_suspended, hide_parent, hide_sitewide)" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
	}
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_moderation_migration_on_update' ) && ! function_exists( 'bb_moderation_migration_on_update' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_moderation_migration_on_update()}.
	 *
	 * @deprecated Use bboss_moderation_migration_on_update() instead.
	 */
	function bb_moderation_migration_on_update() {
		return bboss_moderation_migration_on_update();
	}
}
// @bbwt-com-end compat-layer


/**
 * Fetch all moderated user or check the user is moderated or not SQL.
 *
 * @since BuddyBoss 2.8.10
 *
 * @param int $user_id User ID.
 *
 * @return array|bool
 */
function bboss_moderation_moderated_user_ids_sql( $user_id = 0 ) {
	global $wpdb, $bp;

	static $cache = array();

	if ( empty( $user_id ) ) {
		$user_id = bboss_loggedin_user_id();
	}

	if ( ! empty( $user_id ) ) {
		$cache_key = 'bb_moderation_moderated_user_' . $user_id;
	}

	if ( isset( $cache[ $cache_key ] ) ) {
		return $cache[ $cache_key ];
	}

	$query = "SELECT DISTINCT user_id FROM (
		SELECT ms.item_id as user_id
		FROM
		    {$bp->moderation->table_name} ms
		LEFT JOIN
		    {$bp->moderation->table_name_reports} m
		    ON ms.id = m.moderation_id
		WHERE
		    ms.item_type = %s
		    AND (
		        (ms.reported = 1 AND m.user_report = 0 AND m.user_id = %d )
		        OR ms.user_suspended = 1
		    )
		UNION
		SELECT m.user_id as user_id
		FROM
		    {$bp->moderation->table_name} ms
		LEFT JOIN
		    {$bp->moderation->table_name_reports} m
		    ON ms.id = m.moderation_id
		WHERE
		    ms.item_type = %s
		    AND (
			(ms.item_id = %d AND ms.reported != 0 AND m.user_report != 1)
		) ) AS combined_results";

	// Prepare the query with parameters.
	$retval = $wpdb->prepare(
		$query, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- the literal query built just above (table names from buddypress(), four placeholders bound here).
		BBoss_Moderation_Members::$moderation_type, // ms.item_type = 'user'.
		$user_id,                                // m.user_id = 2.
		BBoss_Moderation_Members::$moderation_type, // ms.item_type = 'user'.
		$user_id                                 // ms.item_id = 2.
	);

	$cache[ $cache_key ] = $retval;

	return $retval;
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_moderation_moderated_user_ids_sql' ) && ! function_exists( 'bb_moderation_moderated_user_ids_sql' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_moderation_moderated_user_ids_sql()}.
	 *
	 * @deprecated Use bboss_moderation_moderated_user_ids_sql() instead.
	 */
	function bb_moderation_moderated_user_ids_sql( $user_id = 0 ) {
		return bboss_moderation_moderated_user_ids_sql( $user_id );
	}
}
// @bbwt-com-end compat-layer


/**
 * Default reporting categories that ship with BuddyBoss Platform.
 *
 * Keeping the source strings here lets us re-translate them at render
 * time without depending on the locale that was active during install.
 *
 * @since 3.5.1
 *
 * @return array
 */
function bboss_moderation_get_default_report_categories() {
	return array(
		'offensive'      => array(
			'name'        => __( 'Offensive', 'buddyboss' ),
			'description' => __( 'Contains abusive or derogatory content', 'buddyboss' ),
		),
		'inappropriate'  => array(
			'name'        => __( 'Inappropriate', 'buddyboss' ),
			'description' => __( 'Contains mature or sensitive content', 'buddyboss' ),
		),
		'misinformation' => array(
			'name'        => __( 'Misinformation', 'buddyboss' ),
			'description' => __( 'Contains misleading or false information', 'buddyboss' ),
		),
		'harassment'     => array(
			'name'        => __( 'Harassment', 'buddyboss' ),
			'description' => __( 'Harassment or bullying behavior', 'buddyboss' ),
		),
		'suspicious'     => array(
			'name'        => __( 'Suspicious', 'buddyboss' ),
			'description' => __( 'Contains spam, fake content or potential malware', 'buddyboss' ),
		),
		'other'          => array(
			'name'        => __( 'Other', 'buddyboss' ),
			'description' => '',
		),
	);
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_moderation_get_default_report_categories' ) && ! function_exists( 'bp_moderation_get_default_report_categories' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_moderation_get_default_report_categories()}.
	 *
	 * @deprecated Use bboss_moderation_get_default_report_categories() instead.
	 */
	function bp_moderation_get_default_report_categories() {
		return bboss_moderation_get_default_report_categories();
	}
}
// @bbwt-com-end compat-layer


/**
 * Translate a default reporting-category term's name/description on the fly.
 *
 * Site-created terms (and default terms whose name/description has been
 * customised by the site admin) are returned untouched.
 *
 * @param WP_Term|object $term Term object.
 *
 * @since 3.5.1
 * 
 * @return WP_Term|object
 */
function bboss_moderation_maybe_translate_report_term( $term ) {
	if ( empty( $term ) || empty( $term->term_id ) || 'bpm_category' !== $term->taxonomy ) {
		return $term;
	}

	$key = get_term_meta( (int) $term->term_id, '_bp_moderation_default_key', true );

	// Fallback for installs that pre-date the meta marker: match by slug.
	if ( empty( $key ) && ! empty( $term->slug ) ) {
		$defaults = bboss_moderation_get_default_report_categories();
		if ( isset( $defaults[ $term->slug ] ) ) {
			$key = $term->slug;
		}
	}

	if ( empty( $key ) ) {
		return $term;
	}

	$defaults = bboss_moderation_get_default_report_categories();
	if ( ! isset( $defaults[ $key ] ) ) {
		return $term;
	}

	$term->name        = $defaults[ $key ]['name'];
	$term->description = $defaults[ $key ]['description'];

	return $term;
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_moderation_maybe_translate_report_term' ) && ! function_exists( 'bp_moderation_maybe_translate_report_term' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_moderation_maybe_translate_report_term()}.
	 *
	 * @deprecated Use bboss_moderation_maybe_translate_report_term() instead.
	 */
	function bp_moderation_maybe_translate_report_term( $term ) {
		return bboss_moderation_maybe_translate_report_term( $term );
	}
}
// @bbwt-com-end compat-layer

