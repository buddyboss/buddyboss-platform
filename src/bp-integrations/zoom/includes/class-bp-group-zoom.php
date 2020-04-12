<?php
/**
 * BuddyBoss Groups Zoom.
 *
 * @package BuddyBoss\Groups\Zoom
 * @since BuddyBoss 1.2.10
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( bp_is_active( 'groups' ) ) {
	/**
	 * Class BP_Group_Zoom
	 */
	class BP_Group_Zoom {
		/**
		 * Your __construct() method will contain configuration options for
		 * your extension.
		 *
		 * @since BuddyBoss 1.2.10
		 */
		function __construct() {
			$this->setup_filters();
			$this->setup_actions();

			// Register the template stack for buddyboss so that theme can overrride.
			bp_register_template_stack( array( $this, 'register_template' ) );

			bp_zoom_conference()->zoom_api_key    = bp_zoom_api_key();
			bp_zoom_conference()->zoom_api_secret = bp_zoom_api_secret();
		}

		/**
		 * Setup the group zoom class filters
		 *
		 * @since BuddyBoss 1.2.10
		 */
		private function setup_filters() {}

		/**
		 * setup actions.
		 *
		 * @since BuddyBoss 1.2.10
		 */
		public function setup_actions() {
			add_action( 'bp_setup_nav', array( $this, 'setup_nav' ), 100 );
			add_action( 'bp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
			add_action( 'wp_ajax_zoom_meeting_add', array( $this, 'zoom_meeting_add' ) );
			add_action( 'wp_ajax_zoom_meeting_recordings', array( $this, 'zoom_meeting_recordings' ) );
		}

		/**
		 * Setup navigation for group zoom tabs.
		 *
		 * @since BuddyBoss 1.2.10
		 */
		public function setup_nav() {
			// return if no group.
			if ( ! bp_is_group() ) {
				return;
			}

			$current_group = groups_get_current_group();
			$group_link    = bp_get_group_permalink( $current_group );
			$sub_nav       = array();

			$sub_nav[] = array(
				'name'            => __( 'Zoom', 'buddyboss' ),
				'slug'            => 'zoom',
				'parent_url'      => $group_link,
				'parent_slug'     => $current_group->slug,
				'screen_function' => array( $this, 'zoom_page' ),
				'item_css_id'     => 'zoom',
				'position'        => 100,
				'user_has_access' => $current_group->user_has_access,
				'no_access_url'   => $group_link,
			);

			$default_args = array(
				'parent_url'      => trailingslashit( $group_link . 'zoom' ),
				'parent_slug'     => $current_group->slug . '_zoom',
				'screen_function' => array( $this, 'zoom_page' ),
				'user_has_access' => $current_group->user_has_access,
			);

			$sub_nav[] = array_merge(
				array(
					'name'     => __( 'Upcoming Meetings', 'buddyboss' ),
					'slug'     => 'meetings',
					'position' => 10,
				),
				$default_args
			);

			$sub_nav[] = array_merge(
				array(
					'name'     => __( 'Past Meetings', 'buddyboss' ),
					'slug'     => 'past-meetings',
					'position' => 20,
				),
				$default_args
			);

			$sub_nav[] = array_merge(
				array(
					'name'     => __( 'Create Meeting', 'buddyboss' ),
					'slug'     => 'create-meeting',
					'position' => 30,
				),
				$default_args
			);

			foreach ( $sub_nav as $nav ) {
				bp_core_new_subnav_item( $nav, 'groups' );
			}
		}

		/**
		 * Register template path for BP.
		 *
		 * @since BuddyBoss 1.2.10
		 * @return string template path
		 */
		public function register_template() {
			return bp_zoom_integration_path( '/templates' );
		}

		/**
		 * Zoom page callback
		 *
		 * @since BuddyBoss 1.2.10
		 */
		public function zoom_page() {
			add_action( 'bp_template_content', array( $this, 'zoom_page_content' ) );
			bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'groups/single/home' ) );
		}

		/**
		 * Display zoom page content.
		 *
		 * @since BuddyBoss 1.2.0
		 */
		function zoom_page_content() {
			do_action( 'template_notices' );
			bp_get_template_part( 'groups/single/zoom' );
		}

		/**
		 * Enqueue scripts for zoom meeting pages.
		 *
		 * @since BuddyBoss 1.2.10
		 */
		public function enqueue_scripts() {
			if ( ! bp_zoom_is_groups_zoom() ) {
				return;
			}
			wp_enqueue_style( 'jquery-datetimepicker' );
			wp_enqueue_script( 'jquery-datetimepicker' );
			wp_enqueue_script( 'bp-group-zoom-meeting-js', bp_zoom_integration_url( '/assets/js/bp-group-zoom-meeting.js' ), array( 'jquery' ), bp_get_version(), true );
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
			if ( ! bp_is_post_request() ) {
				return;
			}

			// Nonce check!
			if ( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'bp_zoom_new_meeting' ) ) {
				return;
			}

			$user_id                = ! empty( $_POST['bp-zoom-meeting-host'] ) ? sanitize_text_field( filter_input( INPUT_POST, 'bp-zoom-meeting-host' ) ) : '';
			$group_id               = ! empty( $_POST['bp-zoom-meeting-group-id'] ) ? sanitize_text_field( filter_input( INPUT_POST, 'bp-zoom-meeting-group-id' ) ) : false;
			$start_date             = ! empty( $_POST['bp-zoom-meeting-start-date'] ) ? sanitize_text_field( filter_input( INPUT_POST, 'bp-zoom-meeting-start-date' ) ) : bp_core_current_time();
			$timezone               = ! empty( $_POST['bp-zoom-meeting-timezone'] ) ? sanitize_text_field( filter_input( INPUT_POST, 'bp-zoom-meeting-timezone' ) ) : '';
			$duration               = ! empty( $_POST['bp-zoom-meeting-duration'] ) ? sanitize_text_field( filter_input( INPUT_POST, 'bp-zoom-meeting-duration' ) ) : '';
			$password               = ! empty( $_POST['bp-zoom-meeting-password'] ) ? sanitize_text_field( filter_input( INPUT_POST, 'bp-zoom-meeting-password' ) ) : '';
			$enforce_login          = ! empty( $_POST['bp-zoom-meeting-registration'] ) ? filter_input( INPUT_POST, 'bp-zoom-meeting-registration' ) : false;
			$join_before_host       = ! empty( $_POST['bp-zoom-meeting-join-before-host'] ) ? filter_input( INPUT_POST, 'bp-zoom-meeting-join-before-host' ) : false;
			$host_video             = ! empty( $_POST['bp-zoom-meeting-host-video'] ) ? filter_input( INPUT_POST, 'bp-zoom-meeting-host-video' ) : false;
			$participants_video     = ! empty( $_POST['bp-zoom-meeting-participants-video'] ) ? filter_input( INPUT_POST, 'bp-zoom-meeting-participants-video' ) : false;
			$mute_participants      = ! empty( $_POST['bp-zoom-meeting-mute-participants'] ) ? filter_input( INPUT_POST, 'bp-zoom-meeting-mute-participants' ) : false;
			$waiting_room           = ! empty( $_POST['bp-zoom-meeting-waiting-room'] ) ? filter_input( INPUT_POST, 'bp-zoom-meeting-waiting-room' ) : false;
			$meeting_authentication = ! empty( $_POST['bp-zoom-meeting-authentication'] ) ? filter_input( INPUT_POST, 'bp-zoom-meeting-authentication' ) : false;
			$auto_recording         = ! empty( $_POST['bp-zoom-meeting-recording'] ) ? sanitize_text_field( filter_input( INPUT_POST, 'bp-zoom-meeting-recording' ) ) : 'none';
			$alternative_host_ids   = ! empty( $_POST['bp-zoom-meeting-alt-host-ids'] ) ? sanitize_text_field( filter_input( INPUT_POST, 'bp-zoom-meeting-alt-host-ids' ) ) : '';
			$meeting_topic          = ! empty( $_POST['bp-zoom-meeting-title'] ) ? sanitize_text_field( filter_input( INPUT_POST, 'bp-zoom-meeting-title' ) ) : '';

			$data = array(
				'user_id'                => $user_id,
				'group_id'               => $group_id,
				'start_date'             => $start_date,
				'timezone'               => $timezone,
				'duration'               => $duration,
				'password'               => $password,
				'enforce_login'          => $enforce_login,
				'join_before_host'       => $join_before_host,
				'host_video'             => $host_video,
				'participants_video'     => $participants_video,
				'mute_participants'      => $mute_participants,
				'waiting_room'           => $waiting_room,
				'meeting_authentication' => $meeting_authentication,
				'auto_recording'         => $auto_recording,
				'alternative_host_ids'   => $alternative_host_ids,
				'meeting_topic'          => $meeting_topic,
			);

			$meeting_created = json_decode( bp_zoom_conference()->create_meeting( $data ) );
			if ( empty( $meeting_created->error ) ) {
				$data['zoom_details']    = serialize( $meeting_created );
				$data['zoom_join_url']   = $meeting_created->join_url;
				$data['zoom_start_url']  = $meeting_created->start_url;
				$data['zoom_meeting_id'] = $meeting_created->id;

				$meeting_id = bp_zoom_meeting_add( $data );

				if ( ! $meeting_id ) {
					wp_send_json_error( array() );
				}
			}

			$group_link = bp_get_group_permalink( groups_get_group( $group_id ) );
			wp_send_json_success( array( 'redirect_url' => trailingslashit( $group_link . 'zoom' ) ) );
		}
	}

	new BP_Group_Zoom();
}

