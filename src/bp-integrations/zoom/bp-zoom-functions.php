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
 * The bp_zoom_meeting_get() function shares all arguments with BP_Zoom_Meeting::get().
 * The following is a list of bp_zoom_meeting_get() parameters that have different
 * default values from BP_Zoom_Meeting::get() (value in parentheses is
 * the default for the bp_zoom_meeting_get()).
 *   - 'per_page' (false)
 *
 * @since BuddyBoss 1.2.10
 *
 * @see BP_Zoom_Meeting::get() For more information on accepted arguments
 *      and the format of the returned value.
 *
 * @param array|string $args See BP_Zoom_Meeting::get() for description.
 * @return array $meeting See BP_Zoom_Meeting::get() for description.
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

	$meeting = BP_Zoom_Meeting::get(
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
	 * @param BP_Zoom_Meeting  $meeting Requested meeting object.
	 * @param array     $r     Arguments used for the meeting query.
	 */
	return apply_filters_ref_array( 'bp_zoom_meeting_get', array( &$meeting, &$r ) );
}

/**
 * Fetch specific meeting items.
 *
 * @since BuddyBoss 1.2.10
 *
 * @see BP_Zoom_Meeting::get() For more information on accepted arguments.
 *
 * @param array|string $args {
 *     All arguments and defaults are shared with BP_Zoom_Meeting::get(),
 *     except for the following:
 *     @type string|int|array Single meeting ID, comma-separated list of IDs,
 *                            or array of IDs.
 * }
 * @return array $activity See BP_Zoom_Meeting::get() for description.
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
	 * @param BP_Zoom_Meeting      $meeting    Requested meeting object.
	 * @param array         $args     Original passed in arguments.
	 * @param array         $get_args Constructed arguments used with request.
	 */
	return apply_filters( 'bp_zoom_meeting_get_specific', BP_Zoom_Meeting::get( $get_args ), $args, $get_args );
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
			'id'                     => false,
			'group_id'               => false,
			'user_id'                => '',
			'title'                  => '',
			'start_date'             => bp_core_current_time(),
			'timezone'               => '',
			'duration'               => false,
			'meeting_authentication' => false,
			'enforce_login'          => false,
			'password'               => false,
			'join_before_host'       => false,
			'waiting_room'           => false,
			'host_video'             => false,
			'participants_video'     => false,
			'mute_participants'      => false,
			'auto_recording'         => 'none',
			'alternative_host_ids'   => '',
			'zoom_details'           => '',
			'zoom_start_url'         => '',
			'zoom_join_url'          => '',
			'zoom_meeting_id'        => '',
			'error_type'             => 'bool',
		),
		'meeting_add'
	);

	// Setup meeting to be added.
	$meeting                         = new BP_Zoom_Meeting( $r['id'] );
	$meeting->user_id                = $r['user_id'];
	$meeting->group_id               = (int) $r['group_id'];
	$meeting->title                  = $r['title'];
	$meeting->start_date             = $r['start_date'];
	$meeting->timezone               = $r['timezone'];
	$meeting->duration               = (int) $r['duration'];
	$meeting->meeting_authentication = (bool) $r['meeting_authentication'];
	$meeting->enforce_login          = (bool) $r['enforce_login'];
	$meeting->waiting_room           = (bool) $r['waiting_room'];
	$meeting->join_before_host       = (bool) $r['join_before_host'];
	$meeting->host_video             = (bool) $r['host_video'];
	$meeting->participants_video     = (bool) $r['participants_video'];
	$meeting->mute_participants      = (bool) $r['mute_participants'];
	$meeting->auto_recording         = $r['auto_recording'];
	$meeting->password               = $r['password'];
	$meeting->alternative_host_ids   = $r['alternative_host_ids'];
	$meeting->zoom_details           = $r['zoom_details'];
	$meeting->zoom_start_url         = $r['zoom_start_url'];
	$meeting->zoom_join_url          = $r['zoom_join_url'];
	$meeting->zoom_meeting_id        = $r['zoom_meeting_id'];
	$meeting->error_type             = $r['error_type'];


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
 *                           the same as BP_Zoom_Meeting::get().
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

	$meeting_ids_deleted = BP_Zoom_Meeting::delete( $args );
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
 * Integration > Zoom Conference > Enable
 *
 * @since BuddyBoss 1.2.10
 */
function bp_zoom_settings_callback_enable_field() {
	?>
	<input name="bp-zoom-enable"
		   id="bp-zoom-enable"
		   type="checkbox"
		   value="1"
			<?php checked( bp_zoom_is_zoom_enabled() ); ?>
	/>
	<label for="bp-zoom-enable">
		<?php _e( 'Allow Zoom Conference on site', 'buddyboss' ); ?>
	</label>
	<?php
}

/**
 * Checks if zoom is enabled.
 *
 * @since BuddyBoss 1.2.10
 *
 * @param $default integer
 *
 * @return bool Is zoom enabled or not
 */
function bp_zoom_is_zoom_enabled( $default = 0 ) {
	return (bool) apply_filters( 'bp_zoom_is_zoom_enabled', (bool) bp_get_option( 'bp-zoom-enable', $default ) );
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

function bp_zoom_api_check_connection() {
	$test = bp_zoom_conference()->list_users();
	$test = ! empty( $test['response'] ) ? $test['response'] : false;
	if ( ! empty( $test ) ) {
		if ( $test->code === 124 ) {
			wp_send_json_success( array( 'message' => $test->message ) );
		}

		if ( ! empty( $test->error ) ) {
			wp_send_json_success( array( 'message' => 'Please check your API keys!' ) );
		}

		wp_send_json_success( array( 'message' => 'API Connection is good!' ) );
	}

	wp_send_json_success( array( 'message' => 'Please check your API keys!' ) );
}
add_action( 'wp_ajax_zoom_api_check_connection', 'bp_zoom_api_check_connection' );
add_action( 'wp_ajax_nopriv_zoom_api_check_connection', 'bp_zoom_api_check_connection' );

function bp_zoom_api_check_connection_button() {
	?>
	<p>
		<a class="button" href="#" id="bp-zoom-check-connection"><?php _e( 'Check Connection', 'buddyboss' ); ?></a>
	</p>
	<script>
		jQuery(document).ready(function(){
			jQuery(document).on( 'click', '#bp-zoom-check-connection', function(e){
				e.preventDefault();
				jQuery.ajax({
					type: 'GET',
					url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
					data: { action: 'zoom_api_check_connection' },
					success: function ( response ) {
						if ( typeof response.data !== 'undefined' && response.data.message ) {
							alert(response.data.message);
						}
					}
				});
			});
		});
	</script>
	<?php
}

/**
 * Zoom users list in Settings > Intgrations > Zoom Conference
 *
 * @since BuddyBoss 1.2.10
 */
function bp_zoom_admin_users_list_callback() {
	require_once bp_zoom_integration_path() . '/templates/admin/users.php';
}

/**
 * Add zoom user field to edit profile.
 *
 * @since BuddyBoss 1.2.10
 *
 * @param $user
 */
function bp_zoom_add_zoom_user_profile_field( $user ) {
	$bp_zoom_user        = get_user_meta( $user->ID, 'bp_zoom_user', true );
	$bp_zoom_user_id     = get_user_meta( $user->ID, 'bp_zoom_user_id', true );
	$bp_zoom_user_status = get_user_meta( $user->ID, 'bp_zoom_user_status', true );
	if ( ! empty( $bp_zoom_user_id ) && ! in_array( $bp_zoom_user_status, array( 'active', 'deleted' ), true ) ) {
		$zoom_user_info = bp_zoom_conference()->get_user_info( $bp_zoom_user_id );
		if ( ! empty( $zoom_user_info['code'] ) && 200 === $zoom_user_info['code'] && ! empty( $zoom_user_info['response'] ) ) {
			$bp_zoom_user_status = $zoom_user_info['response']->status;
			update_user_meta( $user->ID, 'bp_zoom_user_status', $bp_zoom_user_status );
		}
	}
	?>
	<h2><?php _e( 'Zoom', 'buddyboss' ); ?></h2>
	<table class="form-table">
		<tr class="bp-zoom-user">
			<th scope="row"><?php _e( 'Zoom User', 'buddyboss' ); ?></th>
			<td>
				<label for="bp_zoom_user">
					<?php if ( current_user_can( 'create_users' ) ) : ?>
						<input name="bp_zoom_user" type="checkbox" id="bp_zoom_user" value="1" <?php checked( '1', $bp_zoom_user ); ?> />
						<?php _e( 'Add to Zoom Conference.', 'buddyboss' ); ?>
					<?php endif; ?>
					<?php echo '[' . $bp_zoom_user_status . ']'; ?>
				</label>
			</td>
		</tr>
	</table>
	<?php
}
add_action( 'show_user_profile', 'bp_zoom_add_zoom_user_profile_field', 9 );
add_action( 'edit_user_profile', 'bp_zoom_add_zoom_user_profile_field', 9 );

/**
 * Save user to zoom conference
 *
 * @since BuddyBoss 1.2.10
 * @param $user_id
 *
 * @return bool
 */
function bp_zoom_save_zoom_user_profile_field( $user_id ) {
	if ( ! current_user_can( 'edit_user', $user_id ) || ! current_user_can( 'create_users' ) ) {
		return false;
	}

	$bp_zoom_user_id = get_user_meta( $user_id, 'bp_zoom_user_id', true );

	if ( isset( $_POST['bp_zoom_user'] ) ) {
		if ( empty( $bp_zoom_user_id ) ) {
			$user = get_userdata( $user_id );

			$create_user = bp_zoom_conference()->create_user(
					array(
							'action'     => 'create',
							'email'      => $user->user_email,
							'type'       => 1,
							'first_name' => ! empty( $user->user_firstname ) ? $user->user_firstname : '',
							'last_name'  => ! empty( $user->user_lastname ) ? $user->user_lastname : '',
					)
			);

			if ( ! empty( $create_user['code'] ) && 201 === $create_user['code'] && ! empty( $create_user['response'] ) ) {
				$bp_zoom_user_id = $create_user['response']->id;
				update_user_meta( $user_id, 'bp_zoom_user_id', $bp_zoom_user_id );
				update_user_meta( $user_id, 'bp_zoom_user', $_POST['bp_zoom_user'] );
			}
		}
	} else {
		if ( ! empty( $bp_zoom_user_id ) ) {
			$delete_user = bp_zoom_conference()->delete_user( $bp_zoom_user_id );

			if ( ! empty( $delete_user['code'] ) && ( 204 === $delete_user['code'] || 429 === $delete_user['code'] ) ) {
				delete_user_meta( $user_id, 'bp_zoom_user_id' );
				delete_user_meta( $user_id, 'bp_zoom_user' );
				update_user_meta( $user_id, 'bp_zoom_user_status', 'deleted' );
			}
		}
	}
}
add_action( 'personal_options_update', 'bp_zoom_save_zoom_user_profile_field', 999 );
add_action( 'edit_user_profile_update', 'bp_zoom_save_zoom_user_profile_field', 999 );

/**
 * WP Users list add zoom user status column.
 *
 * @since BuddyBoss 1.2.10
 * @param $column
 *
 * @return mixed
 */
function bp_zoom_user_list_add_status_column( $column ) {
	$column['bp_zoom_user_status'] = __( 'Zoom', 'buddyboss' );

	return $column;
}
add_filter( 'manage_users_columns', 'bp_zoom_user_list_add_status_column' );
add_filter( 'wpmu_users_columns', 'bp_zoom_user_list_add_status_column' );

/**
 * WP Users list zoom user status column
 *
 * @since BuddyBoss 1.2.10
 * @param $val
 * @param $column_name
 * @param $user_id
 *
 * @return string
 */
function bp_zoom_user_list_status_row( $val, $column_name, $user_id ) {
	switch ( $column_name ) {
		case 'bp_zoom_user_status' :
			return get_the_author_meta( 'bp_zoom_user_status', $user_id );
		default:
	}

	return $val;
}
add_filter( 'manage_users_custom_column', 'bp_zoom_user_list_status_row', 10, 3 );

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
		$encoded_users = $encoded_users['response'];
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

/**
 * Check whether a user is allowed to manage zoom meetings in a given group.
 *
 * @since BuddyBoss 1.2.10
 *
 * @param int $user_id ID of the user.
 * @param int $group_id ID of the group.
 * @return bool true if the user is allowed, otherwise false.
 */
function bp_zoom_groups_can_user_manage_zoom( $user_id, $group_id ) {
	$is_allowed = false;

	if ( ! is_user_logged_in() ) {
		return false;
	}

	// Site admins always have access.
	if ( bp_current_user_can( 'bp_moderate' ) ) {
		return true;
	}

	if ( ! groups_is_user_member( $user_id, $group_id ) ) {
		return false;
	}

	$manager  = bp_zoom_group_get_manager( $group_id );
	$is_admin = groups_is_user_admin( $user_id, $group_id );
	$is_mod   = groups_is_user_mod( $user_id, $group_id );

	if ( 'members' == $manager ) {
		$is_allowed = true;
	} elseif ( 'mods' == $manager && ( $is_mod || $is_admin ) ) {
		$is_allowed = true;
	} elseif ( 'admins' == $manager && $is_admin ) {
		$is_allowed = true;
	}

	return apply_filters( 'bp_zoom_groups_can_user_manage_zoom', $is_allowed );
}

/**
 * Check if single meeting page
 *
 * @since BuddyBoss 1.2.10
 * @return bool true if single meeting page otherwise false.
 */
function bp_zoom_is_single_meeting() {
	return bp_zoom_is_groups_zoom() && is_numeric( bp_action_variable( 1 ) );
}

/**
 * Check if current request is create meeting.
 *
 * @since BuddyBoss 1.2.10
 */
function bp_zoom_is_create_meeting() {
	if ( bp_zoom_is_groups_zoom()() && 'create-meeting' === bp_action_variable( 0 ) ) {
		return true;
	}
	return false;
}

/**
 * Check if current request is create meeting.
 *
 * @since BuddyBoss 1.2.10
 */
function bp_zoom_is_edit_meeting() {
	if ( bp_zoom_is_groups_zoom() && 'meetings' === bp_action_variable( 0 ) && 'edit' === bp_action_variable( 1 ) ) {
		return true;
	}
	return false;
}

function bp_zoom_get_edit_meeting_id() {
	if ( bp_zoom_is_edit_meeting() ) {
		return ( int ) bp_action_variable( 2 );
	}
	return false;
}

/**
 * Get edit meeting.
 *
 * @since BuddyBoss 1.2.10
 * @return object|bool object of the meeting or false if not found.
 */
function bp_zoom_get_edit_meeting() {
	$meeting_id = bp_zoom_get_edit_meeting_id();
	if ( $meeting_id ) {
		$meeting = new BP_Zoom_Meeting( $meeting_id );

		if ( ! empty( $meeting->id ) ) {
			return $meeting;
		}
	}
	return false;
}

/**
 * Get single meeting.
 *
 * @since BuddyBoss 1.2.10
 * @return object|bool object of the meeting or false if not found.
 */
function bp_zoom_get_current_meeting() {
	if ( bp_zoom_is_single_meeting() ) {
		$meeting_id = (int) bp_action_variable( 1 );
		$meeting = new BP_Zoom_Meeting( $meeting_id );

		if ( ! empty( $meeting->id ) ) {
			return $meeting;
		}
	}
	return false;
}

/**
 * Get single meeting id.
 *
 * @since BuddyBoss 1.2.10
 * @return int|bool ID of the meeting or false if not found.
 */
function bp_zoom_get_current_meeting_id() {
	if ( bp_zoom_is_single_meeting() ) {
		return (int) bp_action_variable( 1 );
	}
	return false;
}

/**
 * Check if current user has permission to start meeting.
 *
 * @since BuddyBoss 1.2.10
 * @param $meeting_id
 *
 * @return bool true if user has permission otherwise false.
 */
function bp_zoom_can_current_user_start_meeting( $meeting_id ) {
	// check is user loggedin.
	if ( ! is_user_logged_in() ) {
		return false;
	}

	// get meeting exists.
	$meeting = new BP_Zoom_Meeting( $meeting_id );

	// check meeting exists.
	if ( empty( $meeting->id ) ) {
		return false;
	}

	// get user zoom id.
	$bp_zoom_user_id = get_user_meta( get_current_user_id(), 'bp_zoom_user_id', true );

	// check user has zoom id or not.
	if ( empty( $bp_zoom_user_id ) ) {
		return false;
	}

	// check meeting user id is equal to current user's id or not.
	if ( $meeting->user_id === $bp_zoom_user_id ) {
		return true;
	}

	// return false atleast.
	return false;
}
