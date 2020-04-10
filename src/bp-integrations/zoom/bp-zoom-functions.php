<?php
/**
 * Zoom integration helpers
 *
 * @package BuddyBoss\Zoom
 * @since BuddyBoss 1.2.10
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Returns LearnDash path.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_zoom_integration_path( $path = '' ) {
	return trailingslashit( buddypress()->integrations['zoom']->path ) . trim( $path, '/\\' );
}

/**
 * Returns LearnDash url.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_zoom_integration_url( $path = '' ) {
	return trailingslashit( buddypress()->integrations['zoom']->url ) . trim( $path, '/\\' );
}

/**
 * Retrieve an meeting or meetings.
 *
 * The bp_zoom_meeting_get() function shares all arguments with BP_Group_Zoom_Meeting::get().
 * The following is a list of bp_zoom_meeting_get() parameters that have different
 * default values from BP_Group_Zoom_Meeting::get() (value in parentheses is
 * the default for the bp_zoom_meeting_get()).
 *   - 'per_page' (false)
 *
 * @since BuddyBoss 1.2.10
 *
 * @see BP_Group_Zoom_Meeting::get() For more information on accepted arguments
 *      and the format of the returned value.
 *
 * @param array|string $args See BP_Group_Zoom_Meeting::get() for description.
 * @return array $meeting See BP_Group_Zoom_Meeting::get() for description.
 */
function bp_zoom_meeting_get( $args = '' ) {

	$r = bp_parse_args(
		$args,
		array(
			'max'          => false,        // Maximum number of results to return.
			'fields'       => 'all',
			'page'         => 1,            // Page 1 without a per_page will result in no pagination.
			'per_page'     => false,        // results per page
			'sort'         => 'DESC',       // sort ASC or DESC
			'order_by'     => false,       // order by

			// want to limit the query.
			'group_id'     => false,
			'since'        => false,
			'from'         => false,
			'search_terms' => false,        // Pass search terms as a string
			'count_total'  => false,
		),
		'meeting_get'
	);

	$meeting = BP_Group_Zoom_Meeting::get(
		array(
			'page'         => $r['page'],
			'per_page'     => $r['per_page'],
			'group_id'     => $r['group_id'],
			'since'        => $r['since'],
			'from'         => $r['from'],
			'max'          => $r['max'],
			'sort'         => $r['sort'],
			'order_by'     => $r['order_by'],
			'search_terms' => $r['search_terms'],
			'exclude'      => $r['exclude'],
			'count_total'  => $r['count_total'],
			'fields'       => $r['fields'],
		)
	);

	/**
	 * Filters the requested meeting item(s).
	 *
	 * @since BuddyBoss 1.2.10
	 *
	 * @param BP_Group_Zoom_Meeting  $meeting Requested meeting object.
	 * @param array     $r     Arguments used for the meeting query.
	 */
	return apply_filters_ref_array( 'bp_zoom_meeting_get', array( &$meeting, &$r ) );
}

/**
 * Fetch specific meeting items.
 *
 * @since BuddyBoss 1.2.10
 *
 * @see BP_Group_Zoom_Meeting::get() For more information on accepted arguments.
 *
 * @param array|string $args {
 *     All arguments and defaults are shared with BP_Group_Zoom_Meeting::get(),
 *     except for the following:
 *     @type string|int|array Single meeting ID, comma-separated list of IDs,
 *                            or array of IDs.
 * }
 * @return array $activity See BP_Group_Zoom_Meeting::get() for description.
 */
function bp_zoom_meeting_get_specific( $args = '' ) {

	$r = bp_parse_args(
		$args,
		array(
			'meeting_ids' => false,      // A single meeting_id or array of IDs.
			'max'         => false,      // Maximum number of results to return.
			'page'        => 1,          // Page 1 without a per_page will result in no pagination.
			'per_page'    => false,      // Results per page.
			'sort'        => 'DESC',     // Sort ASC or DESC
			'order_by'    => false,     // Order by
			'group_id'    => false,     // Filter by group id
			'since'       => false,     // Return item since date
			'from'        => false,     // Return item from date
		),
		'meeting_get_specific'
	);

	$get_args = array(
		'in'       => $r['meeting_ids'],
		'max'      => $r['max'],
		'page'     => $r['page'],
		'per_page' => $r['per_page'],
		'sort'     => $r['sort'],
		'order_by' => $r['order_by'],
		'group_id' => $r['group_id'],
		'since'    => $r['since'],
		'from'     => $r['from'],
	);

	/**
	 * Filters the requested specific meeting item.
	 *
	 * @since BuddyBoss
	 *
	 * @param BP_Group_Zoom_Meeting      $meeting    Requested meeting object.
	 * @param array         $args     Original passed in arguments.
	 * @param array         $get_args Constructed arguments used with request.
	 */
	return apply_filters( 'bp_zoom_meeting_get_specific', BP_Group_Zoom_Meeting::get( $get_args ), $args, $get_args );
}

/**
 * Add an meeting item.
 *
 * @since BuddyBoss 1.2.10
 *
 * @param array|string $args {
 *     An array of arguments.
 *     @type int|bool $id                Pass an meeting ID to update an existing item, or
 *                                       false to create a new item. Default: false.
 *     @type int|bool $group_id           ID of the blog Default: current group id.
 *     @type string   $title             Optional. The title of the meeting item.

 *     @type string   $error_type        Optional. Error type. Either 'bool' or 'wp_error'. Default: 'bool'.
 * }
 * @return WP_Error|bool|int The ID of the meeting on success. False on error.
 */
function bp_zoom_meeting_add( $args = '' ) {

	$r = bp_parse_args(
		$args,
		array(
			'id'                   => false,                   // Pass an existing media ID to update an existing entry.
			'group_id'             => false,   // Blog ID
			'user_id'              => '',   // user_id of the uploader.
			'title'                => 'hello',                      // title of meeting being added.
			'start_date'           => bp_core_current_time(),
			'timezone'             => '',
			'duration'             => false,
			'join_before_host'     => false,
			'host_video'           => false,
			'participants_video'   => false,
			'mute_participants'    => false,
			'auto_recording'       => 'none',
			'alternative_host_ids' => '',
			'zoom_details'         => '',
			'zoom_start_url'       => '',
			'zoom_join_url'         => '',
			'zoom_meeting_id'      => '',
			'error_type'           => 'bool',
		),
		'meeting_add'
	);

	// Setup meeting to be added.
	$meeting                       = new BP_Group_Zoom_Meeting( $r['id'] );
	$meeting->group_id             = (int) $r['group_id'];
	$meeting->title                = $r['title'];
	$meeting->start_date           = $r['start_date'];
	$meeting->timezone             = $r['timezone'];
	$meeting->duration             = (int) $r['duration'];
	$meeting->join_before_host     = (bool) $r['join_before_host'];
	$meeting->host_video           = (bool) $r['host_video'];
	$meeting->participants_video   = (bool) $r['participants_video'];
	$meeting->mute_participants    = (bool) $r['mute_participants'];
	$meeting->auto_recording       = $r['auto_recording'];
	$meeting->alternative_host_ids = $r['alternative_host_ids'];
	$meeting->zoom_details         = $r['zoom_details'];
	$meeting->zoom_start_url       = $r['zoom_start_url'];
	$meeting->zoom_join_url        = $r['zoom_join_url'];
	$meeting->zoom_meeting_id      = $r['zoom_meeting_id'];
	$meeting->error_type           = $r['error_type'];


	// save media
	$save = $meeting->save();

	if ( 'wp_error' === $r['error_type'] && is_wp_error( $save ) ) {
		return $save;
	} elseif ( 'bool' === $r['error_type'] && false === $save ) {
		return false;
	}

	/**
	 * Fires at the end of the execution of adding a new meeting item, before returning the new meeting item ID.
	 *
	 * @since BuddyBoss 1.2.10
	 *
	 * @param object $meeting Meeting object.
	 */
	do_action( 'bp_zoom_meeting_add', $meeting );

	return $meeting->id;
}

/**
 * Callback function for api key in zoom integration.
 *
 * @since BuddyBoss 1.2.10
 */
function bp_zoom_settings_callback_api_key_field() {
	?>
	<input name="bp-zoom-api-key"
	       id="bp-zoom-api-key"
	       type="text"
	       value="<?php echo esc_html( bp_zoom_api_key() ); ?>"
	       placeholder="<?php _e( 'Zoom API Key', 'buddyboss' ); ?>"
	       aria-label="<?php _e( 'Zoom API Key', 'buddyboss' ); ?>"
	/>
	<?php
}

/**
 * Get Zoom API Key
 *
 * @since BuddyBoss 1.2.10
 * @param string $default
 *
 * @return mixed|void Zoom API Key
 */
function bp_zoom_api_key( $default = '' ) {
	return apply_filters( 'bp_zoom_api_key', bp_get_option( 'bp-zoom-api-key', $default ) );
}

/**
 * Callback function for api secret in zoom integration.
 *
 * @since BuddyBoss 1.2.10
 */
function bp_zoom_settings_callback_api_secret_field() {
	?>
	<input name="bp-zoom-api-secret"
	       id="bp-zoom-api-secret"
	       type="text"
	       value="<?php echo esc_html( bp_zoom_api_secret() ); ?>"
	       placeholder="<?php _e( 'Zoom API Secret', 'buddyboss' ); ?>"
	       aria-label="<?php _e( 'Zoom API Secret', 'buddyboss' ); ?>"
	/>
	<?php
}

/**
 * Get Zoom API Secret
 *
 * @since BuddyBoss 1.2.10
 * @param string $default
 *
 * @return mixed|void Zoom API Key
 */
function bp_zoom_api_secret( $default = '' ) {
	return apply_filters( 'bp_zoom_api_secret', bp_get_option( 'bp-zoom-api-secret', $default ) );
}

/**
 * Group zoom meeting slug for sub nav items.
 *
 * @since BuddyBoss 1.2.10
 * @param $slug
 *
 * @return string slug of nav
 */
function bp_zoom_nouveau_group_secondary_nav_parent_slug( $slug ) {
	if ( ! bp_is_group() ) {
		return $slug;
	}
	return bp_get_current_group_slug() . '_zoom';
}

/**
 * Selected and current class for current nav item in group zoom tabs.
 *
 * @since BuddyBoss 1.2.10
 * @param $classes_str
 * @param $classes
 * @param $nav_item
 *
 * @return string classes for the nav items
 */
function bp_zoom_nouveau_group_secondary_nav_selected_classes( $classes_str, $classes, $nav_item ) {

	if ( bp_is_current_action( 'zoom' ) ) {
		if ( ( empty( bp_action_variable( 0 ) ) || 'meetings' === bp_action_variable( 0 ) ) && 'meetings' === $nav_item->slug ) {
			$classes = array_merge( $classes, array( 'current', 'selected' ) );
		} else if ( 'create-meeting' === bp_action_variable( 0 ) && 'create-meeting' === $nav_item->slug ) {
			$classes = array_merge( $classes, array( 'current', 'selected' ) );
		} else if ( 'past-meetings' === bp_action_variable( 0 ) && 'past-meetings' === $nav_item->slug ) {
			$classes = array_merge( $classes, array( 'current', 'selected' ) );
		}
		return join( ' ', $classes );
	}
	return $classes_str;
}

/**
 * Check if current request is groups zoom or not.
 *
 * @since BuddyBoss 1.2.10
 * @return bool $is_zoom return true if group zoom page otherwise false
 */
function bp_zoom_is_groups_zoom() {
	$is_zoom = false;
	if ( bp_is_groups_component() && bp_is_group() && bp_is_current_action( 'zoom' ) ) {
		$is_zoom = true;
	}

	/**
	 * Filters the current group zoom page or not.
	 *
	 * @since BuddyBoss 1.2.10
	 *
	 * @param bool $is_zoom Current page is groups zoom page or not.
	 */
	return apply_filters( 'bp_zoom_is_groups_zoom', $is_zoom );
}
