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
 * Delete meeting.
 *
 * @since BuddyBoss 1.2.10
 *
 * @param array|string $args To delete specific meeting items, use
 *                           $args = array( 'id' => $ids ); Otherwise, to use
 *                           filters for item deletion, the argument format is
 *                           the same as BP_Group_Zoom_Meeting::get().
 *                           See that method for a description.
 *
 * @return bool|int The ID of the meeting on success. False on error.
 */
function bp_zoom_meeting_delete( $args = '' ) {

	// Pass one or more the of following variables to delete by those variables.
	$args = bp_parse_args( $args, array(
		'id'         => false,
		'meeting_id' => false,
		'group_id'   => false,
	) );

	/**
	 * Fires before an meeting item proceeds to be deleted.
	 *
	 * @since BuddyBoss 1.2.10
	 *
	 * @param array $args Array of arguments to be used with the meeting deletion.
	 */
	do_action( 'bp_before_zoom_meeting_delete', $args );

	$meeting_ids_deleted = BP_Group_Zoom_Meeting::delete( $args );
	if ( empty( $meeting_ids_deleted ) ) {
		return false;
	}

	/**
	 * Fires after the meeting item has been deleted.
	 *
	 * @since BuddyBoss 1.2.10
	 *
	 * @param array $args Array of arguments used with the meeting deletion.
	 */
	do_action( 'bp_zoom_meeting_delete', $args );

	/**
	 * Fires after the meeting item has been deleted.
	 *
	 * @since BuddyBoss 1.2.10
	 *
	 * @param array $meeting_ids_deleted Array of affected meeting item IDs.
	 */
	do_action( 'bp_zoom_meeting_deleted_meetings', $meeting_ids_deleted );

	return true;
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

/**
 * Get timezones
 *
 * @since BuddyBoss 1.2.10
 */
function bp_zoom_get_timezone_options() {
	$zones = array(
		"Pacific/Midway"                 => "(GMT-11:00) Midway Island, Samoa ",
		"Pacific/Pago_Pago"              => "(GMT-11:00) Pago Pago ",
		"Pacific/Honolulu"               => "(GMT-10:00) Hawaii ",
		"America/Anchorage"              => "(GMT-8:00) Alaska ",
		"America/Vancouver"              => "(GMT-7:00) Vancouver ",
		"America/Los_Angeles"            => "(GMT-7:00) Pacific Time (US and Canada) ",
		"America/Tijuana"                => "(GMT-7:00) Tijuana ",
		"America/Phoenix"                => "(GMT-7:00) Arizona ",
		"America/Edmonton"               => "(GMT-6:00) Edmonton ",
		"America/Denver"                 => "(GMT-6:00) Mountain Time (US and Canada) ",
		"America/Mazatlan"               => "(GMT-6:00) Mazatlan ",
		"America/Regina"                 => "(GMT-6:00) Saskatchewan ",
		"America/Guatemala"              => "(GMT-6:00) Guatemala ",
		"America/El_Salvador"            => "(GMT-6:00) El Salvador ",
		"America/Managua"                => "(GMT-6:00) Managua ",
		"America/Costa_Rica"             => "(GMT-6:00) Costa Rica ",
		"America/Tegucigalpa"            => "(GMT-6:00) Tegucigalpa ",
		"America/Winnipeg"               => "(GMT-5:00) Winnipeg ",
		"America/Chicago"                => "(GMT-5:00) Central Time (US and Canada) ",
		"America/Mexico_City"            => "(GMT-5:00) Mexico City ",
		"America/Panama"                 => "(GMT-5:00) Panama ",
		"America/Bogota"                 => "(GMT-5:00) Bogota ",
		"America/Lima"                   => "(GMT-5:00) Lima ",
		"America/Caracas"                => "(GMT-4:30) Caracas ",
		"America/Montreal"               => "(GMT-4:00) Montreal ",
		"America/New_York"               => "(GMT-4:00) Eastern Time (US and Canada) ",
		"America/Indianapolis"           => "(GMT-4:00) Indiana (East) ",
		"America/Puerto_Rico"            => "(GMT-4:00) Puerto Rico ",
		"America/Santiago"               => "(GMT-4:00) Santiago ",
		"America/Halifax"                => "(GMT-3:00) Halifax ",
		"America/Montevideo"             => "(GMT-3:00) Montevideo ",
		"America/Araguaina"              => "(GMT-3:00) Brasilia ",
		"America/Argentina/Buenos_Aires" => "(GMT-3:00) Buenos Aires, Georgetown ",
		"America/Sao_Paulo"              => "(GMT-3:00) Sao Paulo ",
		"Canada/Atlantic"                => "(GMT-3:00) Atlantic Time (Canada) ",
		"America/St_Johns"               => "(GMT-2:30) Newfoundland and Labrador ",
		"America/Godthab"                => "(GMT-2:00) Greenland ",
		"Atlantic/Cape_Verde"            => "(GMT-1:00) Cape Verde Islands ",
		"Atlantic/Azores"                => "(GMT+0:00) Azores ",
		"UTC"                            => "(GMT+0:00) Universal Time UTC ",
		"Etc/Greenwich"                  => "(GMT+0:00) Greenwich Mean Time ",
		"Atlantic/Reykjavik"             => "(GMT+0:00) Reykjavik ",
		"Africa/Nouakchott"              => "(GMT+0:00) Nouakchott ",
		"Europe/Dublin"                  => "(GMT+1:00) Dublin ",
		"Europe/London"                  => "(GMT+1:00) London ",
		"Europe/Lisbon"                  => "(GMT+1:00) Lisbon ",
		"Africa/Casablanca"              => "(GMT+1:00) Casablanca ",
		"Africa/Bangui"                  => "(GMT+1:00) West Central Africa ",
		"Africa/Algiers"                 => "(GMT+1:00) Algiers ",
		"Africa/Tunis"                   => "(GMT+1:00) Tunis ",
		"Europe/Belgrade"                => "(GMT+2:00) Belgrade, Bratislava, Ljubljana ",
		"CET"                            => "(GMT+2:00) Sarajevo, Skopje, Zagreb ",
		"Europe/Oslo"                    => "(GMT+2:00) Oslo ",
		"Europe/Copenhagen"              => "(GMT+2:00) Copenhagen ",
		"Europe/Brussels"                => "(GMT+2:00) Brussels ",
		"Europe/Berlin"                  => "(GMT+2:00) Amsterdam, Berlin, Rome, Stockholm, Vienna ",
		"Europe/Amsterdam"               => "(GMT+2:00) Amsterdam ",
		"Europe/Rome"                    => "(GMT+2:00) Rome ",
		"Europe/Stockholm"               => "(GMT+2:00) Stockholm ",
		"Europe/Vienna"                  => "(GMT+2:00) Vienna ",
		"Europe/Luxembourg"              => "(GMT+2:00) Luxembourg ",
		"Europe/Paris"                   => "(GMT+2:00) Paris ",
		"Europe/Zurich"                  => "(GMT+2:00) Zurich ",
		"Europe/Madrid"                  => "(GMT+2:00) Madrid ",
		"Africa/Harare"                  => "(GMT+2:00) Harare, Pretoria ",
		"Europe/Warsaw"                  => "(GMT+2:00) Warsaw ",
		"Europe/Prague"                  => "(GMT+2:00) Prague Bratislava ",
		"Europe/Budapest"                => "(GMT+2:00) Budapest ",
		"Africa/Tripoli"                 => "(GMT+2:00) Tripoli ",
		"Africa/Cairo"                   => "(GMT+2:00) Cairo ",
		"Africa/Johannesburg"            => "(GMT+2:00) Johannesburg ",
		"Europe/Helsinki"                => "(GMT+3:00) Helsinki ",
		"Africa/Nairobi"                 => "(GMT+3:00) Nairobi ",
		"Europe/Sofia"                   => "(GMT+3:00) Sofia ",
		"Europe/Istanbul"                => "(GMT+3:00) Istanbul ",
		"Europe/Athens"                  => "(GMT+3:00) Athens ",
		"Europe/Bucharest"               => "(GMT+3:00) Bucharest ",
		"Asia/Nicosia"                   => "(GMT+3:00) Nicosia ",
		"Asia/Beirut"                    => "(GMT+3:00) Beirut ",
		"Asia/Damascus"                  => "(GMT+3:00) Damascus ",
		"Asia/Jerusalem"                 => "(GMT+3:00) Jerusalem ",
		"Asia/Amman"                     => "(GMT+3:00) Amman ",
		"Europe/Moscow"                  => "(GMT+3:00) Moscow ",
		"Asia/Baghdad"                   => "(GMT+3:00) Baghdad ",
		"Asia/Kuwait"                    => "(GMT+3:00) Kuwait ",
		"Asia/Riyadh"                    => "(GMT+3:00) Riyadh ",
		"Asia/Bahrain"                   => "(GMT+3:00) Bahrain ",
		"Asia/Qatar"                     => "(GMT+3:00) Qatar ",
		"Asia/Aden"                      => "(GMT+3:00) Aden ",
		"Africa/Khartoum"                => "(GMT+3:00) Khartoum ",
		"Africa/Djibouti"                => "(GMT+3:00) Djibouti ",
		"Africa/Mogadishu"               => "(GMT+3:00) Mogadishu ",
		"Europe/Kiev"                    => "(GMT+3:00) Kiev ",
		"Asia/Dubai"                     => "(GMT+4:00) Dubai ",
		"Asia/Muscat"                    => "(GMT+4:00) Muscat ",
		"Asia/Tehran"                    => "(GMT+4:30) Tehran ",
		"Asia/Kabul"                     => "(GMT+4:30) Kabul ",
		"Asia/Baku"                      => "(GMT+5:00) Baku, Tbilisi, Yerevan ",
		"Asia/Yekaterinburg"             => "(GMT+5:00) Yekaterinburg ",
		"Asia/Tashkent"                  => "(GMT+5:00) Islamabad, Karachi, Tashkent ",
		"Asia/Calcutta"                  => "(GMT+5:30) India ",
		"Asia/Kolkata"                   => "(GMT+5:30) Mumbai, Kolkata, New Delhi ",
		"Asia/Kathmandu"                 => "(GMT+5:45) Kathmandu ",
		"Asia/Novosibirsk"               => "(GMT+6:00) Novosibirsk ",
		"Asia/Almaty"                    => "(GMT+6:00) Almaty ",
		"Asia/Dacca"                     => "(GMT+6:00) Dacca ",
		"Asia/Dhaka"                     => "(GMT+6:00) Astana, Dhaka ",
		"Asia/Krasnoyarsk"               => "(GMT+7:00) Krasnoyarsk ",
		"Asia/Bangkok"                   => "(GMT+7:00) Bangkok ",
		"Asia/Saigon"                    => "(GMT+7:00) Vietnam ",
		"Asia/Jakarta"                   => "(GMT+7:00) Jakarta ",
		"Asia/Irkutsk"                   => "(GMT+8:00) Irkutsk, Ulaanbaatar ",
		"Asia/Shanghai"                  => "(GMT+8:00) Beijing, Shanghai ",
		"Asia/Hong_Kong"                 => "(GMT+8:00) Hong Kong ",
		"Asia/Taipei"                    => "(GMT+8:00) Taipei ",
		"Asia/Kuala_Lumpur"              => "(GMT+8:00) Kuala Lumpur ",
		"Asia/Singapore"                 => "(GMT+8:00) Singapore ",
		"Australia/Perth"                => "(GMT+8:00) Perth ",
		"Asia/Yakutsk"                   => "(GMT+9:00) Yakutsk ",
		"Asia/Seoul"                     => "(GMT+9:00) Seoul ",
		"Asia/Tokyo"                     => "(GMT+9:00) Osaka, Sapporo, Tokyo ",
		"Australia/Darwin"               => "(GMT+9:30) Darwin ",
		"Australia/Adelaide"             => "(GMT+9:30) Adelaide ",
		"Asia/Vladivostok"               => "(GMT+10:00) Vladivostok ",
		"Pacific/Port_Moresby"           => "(GMT+10:00) Guam, Port Moresby ",
		"Australia/Brisbane"             => "(GMT+10:00) Brisbane ",
		"Australia/Sydney"               => "(GMT+10:00) Canberra, Melbourne, Sydney ",
		"Australia/Hobart"               => "(GMT+10:00) Hobart ",
		"Asia/Magadan"                   => "(GMT+10:00) Magadan ",
		"SST"                            => "(GMT+11:00) Solomon Islands ",
		"Pacific/Noumea"                 => "(GMT+11:00) New Caledonia ",
		"Asia/Kamchatka"                 => "(GMT+12:00) Kamchatka ",
		"Pacific/Fiji"                   => "(GMT+12:00) Fiji Islands, Marshall Islands ",
		"Pacific/Auckland"               => "(GMT+12:00) Auckland, Wellington"
	);

	return apply_filters( 'bp_zoom_get_timezone_options', $zones );
}

/**
 * Get users list from zoom.
 *
 * @since BuddyBoss 1.2.10
 * @return array $users list of zoom users.
 */
function bp_zoom_get_users() {
	$users = get_transient( 'bp_zoom_users' );

	if ( empty( $users ) ) {
		$encoded_users = bp_zoom_conference()->list_users();
		$encoded_users = json_decode( $encoded_users );
		if ( ! empty( $encoded_users->users ) ) {
			$users = $encoded_users->users;
		}
		set_transient( 'bp_zoom_users', $users );
	}
	return $users;
}

function bp_zoom_nouveau_feedback_messages( $messages ) {
	$messages['meetings-loop-none'] = array(
		'type'    => 'info',
		'message' => __( 'Sorry, no meetings were found.', 'buddyboss' ),
	);
	return $messages;
}
add_filter( 'bp_nouveau_feedback_messages', 'bp_zoom_nouveau_feedback_messages' );

function bp_zoom_group_is_zoom_enabled( $group_id ) {
	if ( ! bp_is_active( 'groups' ) ) {
		return false;
	}
	return groups_get_groupmeta( $group_id, 'bp-group-zoom', true );
}

/**
 * Output the 'checked' value, if needed, for a given status on the group admin screen
 *
 * @since BuddyBoss 1.2.10
 *
 * @param string      $setting The setting you want to check against ('members',
 *                             'mods', or 'admins').
 * @param object|bool $group   Optional. Group object. Default: current group in loop.
 */
function bp_zoom_group_show_manager_setting( $setting, $group = false ) {
	$group_id = isset( $group->id ) ? $group->id : false;

	$status = bp_zoom_group_get_manager( $group_id );

	if ( $setting === $status ) {
		echo ' checked="checked"';
	}
}

/**
 * Get the zoom manager of a group.
 *
 * This function can be used either in or out of the loop.
 *
 * @since BuddyBoss 1.2.10
 *
 * @param int|bool $group_id Optional. The ID of the group whose status you want to
 *                           check. Default: the displayed group, or the current group
 *                           in the loop.
 * @return bool|string Returns false when no group can be found. Otherwise
 *                     returns the group zoom manager, from among 'members',
 *                     'mods', and 'admins'.
 */
function bp_zoom_group_get_manager( $group_id = false ) {
	global $groups_template;

	if ( ! $group_id ) {
		$bp = buddypress();

		if ( isset( $bp->groups->current_group->id ) ) {
			// Default to the current group first.
			$group_id = $bp->groups->current_group->id;
		} elseif ( isset( $groups_template->group->id ) ) {
			// Then see if we're in the loop.
			$group_id = $groups_template->group->id;
		} else {
			return false;
		}
	}

	$manager = groups_get_groupmeta( $group_id, 'bp-group-zoom-manager', true );

	// Backward compatibility. When '$manager' is not set, fall back to a default value.
	if ( ! $manager ) {
		$manager = apply_filters( 'bp_zoom_group_manager_fallback', 'admins' );
	}

	/**
	 * Filters the album status of a group.
	 *
	 * @since BuddyBoss 1.2.10
	 *
	 * @param string $manager Membership level needed to manage albums.
	 * @param int    $group_id      ID of the group whose manager is being checked.
	 */
	return apply_filters( 'bp_zoom_group_get_manager', $manager, $group_id );
}
