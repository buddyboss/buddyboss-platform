<?php
/**
 * BuddyBoss Groups Zoom Extension.
 *
 * @package BuddyBoss\Groups\Extensions
 * @since BuddyBoss 1.2.10
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'BP_Group_Zoom_Extension' ) && bp_is_active( 'groups' ) ) {
	/**
	 * Class BP_Group_Zoom_Extension
	 */
	class BP_Group_Zoom_Extension extends BP_Group_Extension {
		/**
		 * Your __construct() method will contain configuration options for
		 * your extension, and will pass them to parent::init()
		 *
		 * @since BuddyBoss 1.2.10
		 */
		function __construct() {
			$args = array(
				'slug'              => 'zoom',
				'name'              => __( 'Zoom', 'buddyboss' ),
				'nav_item_position' => 100,
				'enable_nav_item'   => false,
			);

			if ( bp_is_group() ) {
				// Tweak the nav item variable based on if group has zoom or not
				$args['enable_nav_item'] = ( bool ) groups_get_groupmeta( bp_get_current_group_id(), 'bp-group-zoom-conference' );
			}

			$this->setup_filters();
			$this->setup_actions();

			bp_zoom_conference()->zoom_api_key    = bp_zoom_api_key();
			bp_zoom_conference()->zoom_api_secret = bp_zoom_api_secret();

			parent::init( $args );
		}

		public function setup_actions() {
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
			add_action( 'wp_ajax_zoom_meeting_add', array( $this, 'zoom_meeting_add' ) );
			add_action( 'wp_ajax_zoom_meeting_recordings', array( $this, 'zoom_meeting_recordings' ) );
		}

		public function enqueue_scripts() {
			wp_enqueue_script( 'bp-group-zoom-meeting-js', trailingslashit( buddypress()->integration_url ) . 'zoom/assets/js/bp-group-zoom-meeting.js', array( 'jquery' ), bp_get_version(), true );
		}

		public function zoom_meeting_recordings() {
			$meeting_id = filter_input( INPUT_GET, 'meeting_id' );
			$recordings = json_decode( bp_zoom_conference()->recordings_by_meeting( $meeting_id ) );
			if ( empty( $recordings->error ) && $recordings->recording_count > 0 ) {

				$count = 1;
				ob_start();
				foreach( $recordings->recording_files as $recording_file ) {
					?>
							<div class="header_left">
								<div class="video">
									<div class="video_center"></div>
									<div class="video_link">
										<a class="play_btn"
										   href="<?php echo $recording_file->play_url; ?>"
										   target="_blank">Play
										</a>
									</div>
								</div>
							</div>
							<div class="header_right">
								<h3 class="clip_title">Recording <?php echo $count; ?></h3>
								<span class="clip_description"><?php echo bp_media_format_size_units( $recording_file->file_size, true, 'MB' ); ?></span> <br>
								<a href="<?php echo $recording_file->download_url; ?>" target="_blank"
								   class="btn btn-default downloadmeeting downloadclip ipad-hide"
								   data-id="99UqH4qv6WxJG53NtmGOA699LoHmeaa8hiRI_vEOyJ7k3zX7P1bbDFxoF3a9cFc">
									<i class="icon_download my_icon"></i>
									Download</a>
								<a href="javascript:;" class="btn deleteclip relative " aria-label="delete">
									<i class="icon_delete my_icon"></i>
								</a>
							</div>
					<?php
					$count++;
				}

				wp_send_json_success( array( 'recordings' => ob_get_clean() ) );
			}
			wp_send_json_error( array( 'error' => $recordings->error ) );
		}

		public function zoom_meeting_add() {

			$data = array(
				'user_id'              => sanitize_text_field( filter_input( INPUT_POST, 'user_id' ) ),
				'group_id'             => sanitize_text_field( filter_input( INPUT_POST, 'group_id' ) ),
				'start_date'           => sanitize_text_field( filter_input( INPUT_POST, 'start_date' ) ),
				'timezone'             => sanitize_text_field( filter_input( INPUT_POST, 'timezone' ) ),
				'duration'             => sanitize_text_field( filter_input( INPUT_POST, 'duration' ) ),
				'join_before_host'     => filter_input( INPUT_POST, 'join_before_host' ),
				'host_video'           => filter_input( INPUT_POST, 'host_video' ),
				'participants_video'   => filter_input( INPUT_POST, 'participants_video' ),
				'mute_participants'    => filter_input( INPUT_POST, 'mute_participants' ),
				'auto_recording'       => filter_input( INPUT_POST, 'auto_recording' ),
				'alternative_host_ids' => filter_input( INPUT_POST, 'alternative_host_ids', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY ),
				'meeting_topic'        => 'hello',
			);

			$meeting_created = json_decode( bp_zoom_conference()->create_meeting( $data ) );
			if ( empty( $meeting_created->error ) ) {
				$data['zoom_details']    = serialize( $meeting_created );
				$data['zoom_join_url']   = $meeting_created->join_url;
				$data['zoom_start_url']  = $meeting_created->start_url;
				$data['zoom_meeting_id'] = $meeting_created->id;

				$meeting_id = bp_zoom_meeting_add( $data );
			}
			wp_send_json_success( array() );
		}

		/**
		 * Setup the group zoom class filters
		 *
		 * @since BuddyBoss 1.2.10
		 */
		private function setup_filters() {
		}

		/**
		 * display() contains the markup that will be displayed on the main
		 * plugin tab
		 *
		 * @since BuddyBoss 1.2.10
		 */
		function display( $group_id = null ) {
			$group_id = bp_get_group_id();

			?>
			<div class="mtg-list-header clearfix">
				<div class="list-col mtg-date">Start Time</div>
				<div class="list-col mtg-topic">Topic</div>
				<div class="list-col mtg-id"> Meeting ID</div>
				<div class="list-col mtg-action"> &nbsp;</div>
			</div>
			<?php

			if ( bp_has_zoom_meetings() ) {
				while ( bp_zoom_meeting() ) {
					bp_the_zoom_meeting();
					?>
					<div class="clearfix" id="meeting-item" data-id="<?php bp_zoom_meeting_id(); ?>"
					     data-meeting-id="<?php bp_zoom_meeting_zoom_meeting_id(); ?>">
						<div class="list-col mtg-date">
							<?php bp_zoom_meeting_start_date(); ?><br/>
							<?php bp_zoom_meeting_timezone(); ?>
						</div>
						<div class="list-col mtg-topic">
							<a href="<?php bp_zoom_meeting_zoom_join_url(); ?>" class="sort-headers"
							   data="topic"><?php bp_zoom_meeting_title(); ?></a>
						</div>
						<div class="list-col mtg-id"><?php bp_zoom_meeting_zoom_meeting_id(); ?></div>
						<div class="list-col mtg-action">
							<a role="button" target="_blank" href="<?php bp_zoom_meeting_zoom_start_url(); ?>"
							   class="btn btn-default btn-sm">Start</a>
							<a role="button" href="#" class="btn btn-default btn-sm">Delete</a>
						</div>
						<div class="form-group recording-list">

						</div>
					</div>
					<br/>
					<?php
				}

				if ( bp_zoom_meeting_has_more_items() ) {
					echo "more item here";
				}
			}

			?>
			<table class="profile-settings bp-tables-user">
				<tbody>
				<tr>
					<th scope="row"><label for="userId">Meeting Host *</label></th>
					<td>
						<select name="userId" id="user-id" required="">
							<option value="">Select a Host</option>
							<?php
							$encoded_users = bp_zoom_conference()->list_users();
							$decoded_users = json_decode( $encoded_users );
							foreach ( $decoded_users->users as $user ): ?>
							<option value="<?php echo $user->id; ?>"><?php echo $user->first_name . ' ( ' . $user->email . ' )'; ?></option>
							<?php endforeach; ?>
							</option>
						</select>
						<p class="description" id="userId-description">This is host ID for the meeting (Required).</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="start_date">Start Date/Time *</label></th>
					<td>
						<input type="text" name="start_date" id="start_date" data-existingdate="2020-04-02 18:00"
						       required="" class="regular-text" value="2020-04-05 18:00">
						<p class="description" id="start_date-description">Starting Date and Time of the Meeting
							(Required).</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="timezone">Timezone</label></th>
					<td>
						<select id="timezone" name="timezone" id="timezone"
						        class="zvc-hacking-select select2-hidden-accessible" data-select2-id="timezone"
						        tabindex="-1" aria-hidden="true">
							<option value="Pacific/Midway">(GMT-11:00) Midway Island, Samoa</option>
							<option value="Pacific/Pago_Pago">(GMT-11:00) Pago Pago</option>
							<option value="Pacific/Honolulu">(GMT-10:00) Hawaii</option>
							<option value="America/Anchorage">(GMT-8:00) Alaska</option>
							<option value="America/Vancouver">(GMT-7:00) Vancouver</option>
							<option value="America/Los_Angeles">(GMT-7:00) Pacific Time (US and Canada)</option>
							<option value="America/Tijuana">(GMT-7:00) Tijuana</option>
							<option value="America/Phoenix">(GMT-7:00) Arizona</option>
							<option value="America/Edmonton">(GMT-6:00) Edmonton</option>
							<option value="America/Denver">(GMT-6:00) Mountain Time (US and Canada)</option>
							<option value="America/Mazatlan">(GMT-6:00) Mazatlan</option>
							<option value="America/Regina">(GMT-6:00) Saskatchewan</option>
							<option value="America/Guatemala">(GMT-6:00) Guatemala</option>
							<option value="America/El_Salvador">(GMT-6:00) El Salvador</option>
							<option value="America/Managua">(GMT-6:00) Managua</option>
							<option value="America/Costa_Rica">(GMT-6:00) Costa Rica</option>
							<option value="America/Tegucigalpa">(GMT-6:00) Tegucigalpa</option>
							<option value="America/Winnipeg">(GMT-5:00) Winnipeg</option>
							<option value="America/Chicago">(GMT-5:00) Central Time (US and Canada)</option>
							<option value="America/Mexico_City">(GMT-5:00) Mexico City</option>
							<option value="America/Panama">(GMT-5:00) Panama</option>
							<option value="America/Bogota">(GMT-5:00) Bogota</option>
							<option value="America/Lima">(GMT-5:00) Lima</option>
							<option value="America/Caracas">(GMT-4:30) Caracas</option>
							<option value="America/Montreal">(GMT-4:00) Montreal</option>
							<option value="America/New_York">(GMT-4:00) Eastern Time (US and Canada)</option>
							<option value="America/Indianapolis">(GMT-4:00) Indiana (East)</option>
							<option value="America/Puerto_Rico">(GMT-4:00) Puerto Rico</option>
							<option value="America/Santiago">(GMT-4:00) Santiago</option>
							<option value="America/Halifax">(GMT-3:00) Halifax</option>
							<option value="America/Montevideo">(GMT-3:00) Montevideo</option>
							<option value="America/Araguaina">(GMT-3:00) Brasilia</option>
							<option value="America/Argentina/Buenos_Aires">(GMT-3:00) Buenos Aires, Georgetown</option>
							<option value="America/Sao_Paulo">(GMT-3:00) Sao Paulo</option>
							<option value="Canada/Atlantic">(GMT-3:00) Atlantic Time (Canada)</option>
							<option value="America/St_Johns">(GMT-2:30) Newfoundland and Labrador</option>
							<option value="America/Godthab">(GMT-2:00) Greenland</option>
							<option value="Atlantic/Cape_Verde">(GMT-1:00) Cape Verde Islands</option>
							<option value="Atlantic/Azores">(GMT+0:00) Azores</option>
							<option value="UTC">(GMT+0:00) Universal Time UTC</option>
							<option value="Etc/Greenwich">(GMT+0:00) Greenwich Mean Time</option>
							<option value="Atlantic/Reykjavik">(GMT+0:00) Reykjavik</option>
							<option value="Africa/Nouakchott">(GMT+0:00) Nouakchott</option>
							<option value="Europe/Dublin">(GMT+1:00) Dublin</option>
							<option value="Europe/London">(GMT+1:00) London</option>
							<option value="Europe/Lisbon">(GMT+1:00) Lisbon</option>
							<option value="Africa/Casablanca">(GMT+1:00) Casablanca</option>
							<option value="Africa/Bangui">(GMT+1:00) West Central Africa</option>
							<option value="Africa/Algiers">(GMT+1:00) Algiers</option>
							<option value="Africa/Tunis">(GMT+1:00) Tunis</option>
							<option value="Europe/Belgrade">(GMT+2:00) Belgrade, Bratislava, Ljubljana</option>
							<option value="CET">(GMT+2:00) Sarajevo, Skopje, Zagreb</option>
							<option value="Europe/Oslo">(GMT+2:00) Oslo</option>
							<option value="Europe/Copenhagen">(GMT+2:00) Copenhagen</option>
							<option value="Europe/Brussels">(GMT+2:00) Brussels</option>
							<option value="Europe/Berlin">(GMT+2:00) Amsterdam, Berlin, Rome, Stockholm, Vienna</option>
							<option value="Europe/Amsterdam">(GMT+2:00) Amsterdam</option>
							<option value="Europe/Rome">(GMT+2:00) Rome</option>
							<option value="Europe/Stockholm">(GMT+2:00) Stockholm</option>
							<option value="Europe/Vienna">(GMT+2:00) Vienna</option>
							<option value="Europe/Luxembourg">(GMT+2:00) Luxembourg</option>
							<option value="Europe/Paris">(GMT+2:00) Paris</option>
							<option value="Europe/Zurich">(GMT+2:00) Zurich</option>
							<option value="Europe/Madrid">(GMT+2:00) Madrid</option>
							<option value="Africa/Harare">(GMT+2:00) Harare, Pretoria</option>
							<option value="Europe/Warsaw">(GMT+2:00) Warsaw</option>
							<option value="Europe/Prague">(GMT+2:00) Prague Bratislava</option>
							<option value="Europe/Budapest">(GMT+2:00) Budapest</option>
							<option value="Africa/Tripoli">(GMT+2:00) Tripoli</option>
							<option value="Africa/Cairo">(GMT+2:00) Cairo</option>
							<option value="Africa/Johannesburg">(GMT+2:00) Johannesburg</option>
							<option value="Europe/Helsinki">(GMT+3:00) Helsinki</option>
							<option value="Africa/Nairobi">(GMT+3:00) Nairobi</option>
							<option value="Europe/Sofia">(GMT+3:00) Sofia</option>
							<option value="Europe/Istanbul">(GMT+3:00) Istanbul</option>
							<option value="Europe/Athens">(GMT+3:00) Athens</option>
							<option value="Europe/Bucharest">(GMT+3:00) Bucharest</option>
							<option value="Asia/Nicosia">(GMT+3:00) Nicosia</option>
							<option value="Asia/Beirut">(GMT+3:00) Beirut</option>
							<option value="Asia/Damascus">(GMT+3:00) Damascus</option>
							<option value="Asia/Jerusalem">(GMT+3:00) Jerusalem</option>
							<option value="Asia/Amman">(GMT+3:00) Amman</option>
							<option value="Europe/Moscow">(GMT+3:00) Moscow</option>
							<option value="Asia/Baghdad">(GMT+3:00) Baghdad</option>
							<option value="Asia/Kuwait">(GMT+3:00) Kuwait</option>
							<option value="Asia/Riyadh">(GMT+3:00) Riyadh</option>
							<option value="Asia/Bahrain">(GMT+3:00) Bahrain</option>
							<option value="Asia/Qatar">(GMT+3:00) Qatar</option>
							<option value="Asia/Aden">(GMT+3:00) Aden</option>
							<option value="Africa/Khartoum">(GMT+3:00) Khartoum</option>
							<option value="Africa/Djibouti">(GMT+3:00) Djibouti</option>
							<option value="Africa/Mogadishu">(GMT+3:00) Mogadishu</option>
							<option value="Europe/Kiev">(GMT+3:00) Kiev</option>
							<option value="Asia/Dubai">(GMT+4:00) Dubai</option>
							<option value="Asia/Muscat">(GMT+4:00) Muscat</option>
							<option value="Asia/Tehran">(GMT+4:30) Tehran</option>
							<option value="Asia/Kabul">(GMT+4:30) Kabul</option>
							<option value="Asia/Baku">(GMT+5:00) Baku, Tbilisi, Yerevan</option>
							<option value="Asia/Yekaterinburg">(GMT+5:00) Yekaterinburg</option>
							<option value="Asia/Tashkent">(GMT+5:00) Islamabad, Karachi, Tashkent</option>
							<option value="Asia/Calcutta" selected="selected" data-select2-id="5">(GMT+5:30) India
							</option>
							<option value="Asia/Kolkata">(GMT+5:30) Mumbai, Kolkata, New Delhi</option>
							<option value="Asia/Kathmandu">(GMT+5:45) Kathmandu</option>
							<option value="Asia/Novosibirsk">(GMT+6:00) Novosibirsk</option>
							<option value="Asia/Almaty">(GMT+6:00) Almaty</option>
							<option value="Asia/Dacca">(GMT+6:00) Dacca</option>
							<option value="Asia/Dhaka">(GMT+6:00) Astana, Dhaka</option>
							<option value="Asia/Krasnoyarsk">(GMT+7:00) Krasnoyarsk</option>
							<option value="Asia/Bangkok">(GMT+7:00) Bangkok</option>
							<option value="Asia/Saigon">(GMT+7:00) Vietnam</option>
							<option value="Asia/Jakarta">(GMT+7:00) Jakarta</option>
							<option value="Asia/Irkutsk">(GMT+8:00) Irkutsk, Ulaanbaatar</option>
							<option value="Asia/Shanghai">(GMT+8:00) Beijing, Shanghai</option>
							<option value="Asia/Hong_Kong">(GMT+8:00) Hong Kong</option>
							<option value="Asia/Taipei">(GMT+8:00) Taipei</option>
							<option value="Asia/Kuala_Lumpur">(GMT+8:00) Kuala Lumpur</option>
							<option value="Asia/Singapore">(GMT+8:00) Singapore</option>
							<option value="Australia/Perth">(GMT+8:00) Perth</option>
							<option value="Asia/Yakutsk">(GMT+9:00) Yakutsk</option>
							<option value="Asia/Seoul">(GMT+9:00) Seoul</option>
							<option value="Asia/Tokyo">(GMT+9:00) Osaka, Sapporo, Tokyo</option>
							<option value="Australia/Darwin">(GMT+9:30) Darwin</option>
							<option value="Australia/Adelaide">(GMT+9:30) Adelaide</option>
							<option value="Asia/Vladivostok">(GMT+10:00) Vladivostok</option>
							<option value="Pacific/Port_Moresby">(GMT+10:00) Guam, Port Moresby</option>
							<option value="Australia/Brisbane">(GMT+10:00) Brisbane</option>
							<option value="Australia/Sydney">(GMT+10:00) Canberra, Melbourne, Sydney</option>
							<option value="Australia/Hobart">(GMT+10:00) Hobart</option>
							<option value="Asia/Magadan">(GMT+10:00) Magadan</option>
							<option value="SST">(GMT+11:00) Solomon Islands</option>
							<option value="Pacific/Noumea">(GMT+11:00) New Caledonia</option>
							<option value="Asia/Kamchatka">(GMT+12:00) Kamchatka</option>
							<option value="Pacific/Fiji">(GMT+12:00) Fiji Islands, Marshall Islands</option>
							<option value="Pacific/Auckland">(GMT+12:00) Auckland, Wellington</option>
						</select><span class="select2 select2-container select2-container--default" dir="ltr"
						               data-select2-id="4" style="width: 390px;"><span class="selection"><span
									class="select2-selection select2-selection--single" role="combobox"
									aria-haspopup="true" aria-expanded="false" tabindex="0" aria-disabled="false"
									aria-labelledby="select2-timezone-container"><span
										class="select2-selection__rendered" id="select2-timezone-container"
										role="textbox" aria-readonly="true"
										title="(GMT+5:30) India ">(GMT+5:30) India </span><span
										class="select2-selection__arrow" role="presentation"><b role="presentation"></b></span></span></span><span
								class="dropdown-wrapper" aria-hidden="true"></span></span>
						<p class="description" id="timezone-description">Meeting Timezone</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="duration">Duration</label></th>
					<td>
						<input type="number" name="duration" id="duration" class="regular-text" value="60">
						<p class="description" id="duration-description">Meeting duration (minutes). (optional)</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="join_before_host">Join Before Host</label></th>
					<td>
						<p class="description" id="join_before_host-description"><input type="checkbox"
						                                                                name="join_before_host"
						                                                                id="join_before_host" value="1"
						                                                                checked="checked"
						                                                                class="regular-text">Join
							meeting before host start the meeting. Only for scheduled or recurring meetings.</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="option_host_video">Host join start</label></th>
					<td>
						<p class="description" id="option_host_video-description"><input type="checkbox"
						                                                                 name="option_host_video"
						                                                                 id="option_host_video"
						                                                                 value="1" class="regular-text">Start
							video when host join meeting.</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="option_participants_video">Participants Video</label></th>
					<td>
						<p class="description" id="option_participants_video-description"><input type="checkbox"
						                                                                         name="option_participants_video"
						                                                                         id="participants_video"
						                                                                         value="1"
						                                                                         class="regular-text">Start
							video when participants join meeting.</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="option_mute_participants_upon_entry">Mute Participants upon
							entry</label></th>
					<td>
						<p class="description" id="option_mute_participants_upon_entry"><input type="checkbox"
						                                                                       name="option_mute_participants"
						                                                                       id="mute_participants"
						                                                                       value="1"
						                                                                       checked="checked"
						                                                                       class="regular-text">Mutes
							Participants when entering the meeting.</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="option_auto_recording">Auto Recording</label></th>
					<td>
						<select id="auto_recording" name="option_auto_recording">
							<option value="none">No Recordings</option>
							<option value="local">Local</option>
							<option value="cloud" selected="selected">Cloud</option>
						</select>
						<p class="description" id="option_auto_recording_description">Set what type of auto recording
							feature you want to add. Default is none.</p>
					</td>
				</tr>
				</tbody>
			</table>
			<input type="hidden" id="group-id" value="<?php echo $group_id; ?>"/>
			<input type="button" id="save-meeting" value="Save meeting"/>
			<?php
		}

		/**
		 * settings_screen() is the catch-all method for displaying the content
		 * of the edit, create, and Dashboard admin panels
		 */
		function settings_screen( $group_id = null ) {
			$setting = groups_get_groupmeta( $group_id, 'bp-group-zoom-conference' );

			?>
			<p class="bp-controls-wrap">
				<input type="checkbox" name="bp-group-zoom-conference" id="bp-group-zoom-conference"
				       class="bs-styled-checkbox" value="1" <?php echo checked( $setting ); ?> />
				<label for="bp-group-zoom-conference"
				       class="bp-label-text"><?php esc_html_e( 'Enable Zoom Conference', 'buddyboss' ); ?></label>
			</p>
			<?php
		}

		/**
		 * settings_sceren_save() contains the catch-all logic for saving
		 * settings from the edit, create, and Dashboard admin panels
		 */
		function settings_screen_save( $group_id = null ) {
			$setting = '';

			if ( isset( $_POST['bp-group-zoom-conference'] ) ) {
				$setting = $_POST['bp-group-zoom-conference'];
			}

			groups_update_groupmeta( $group_id, 'bp-group-zoom-conference', $setting );
		}
	}

	bp_register_group_extension( 'BP_Group_Zoom_Extension' );
}

