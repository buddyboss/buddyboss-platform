<?php
/**
 * BuddyBoss Zoom AJAX.
 *
 * @package BuddyBoss\Zoom\Ajax
 * @since BuddyBoss 1.2.10
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'BP_Zoom_Ajax' ) ) {
	/**
	 * Class BP_Zoom_Ajax
	 */
	class BP_Zoom_Ajax {
		/**
		 * Your __construct() method will contain configuration options for
		 * your extension.
		 *
		 * @since BuddyBoss 1.2.10
		 */
		function __construct() {
			$this->setup_filters();
			$this->setup_actions();
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
			add_action( 'wp_ajax_zoom_meeting_add', array( $this, 'zoom_meeting_add' ) );
			add_action( 'wp_ajax_zoom_meeting_delete', array( $this, 'zoom_meeting_delete' ) );
			add_action( 'wp_ajax_zoom_meeting_recordings', array( $this, 'zoom_meeting_recordings' ) );
			add_action( 'wp_ajax_zoom_meeting_load_more', array( $this, 'zoom_meeting_load_more' ) );
		}

		/**
		 * Zoom meeting add.
		 * @since BuddyBoss 1.2.10
		 */
		public function zoom_meeting_add() {
			if ( ! bp_is_post_request() ) {
				wp_send_json_error( array( 'error' => __( 'There was a problem when adding. Please try again.', 'buddyboss' ) ) );
			}

			// Nonce check!
			if ( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'bp_zoom_meeting' ) ) {
				wp_send_json_error( array( 'error' => __( 'There was a problem when adding. Please try again.', 'buddyboss' ) ) );
			}

			$user_id = bp_zoom_api_host();

			// check user host.
			if ( empty( $user_id ) ) {
				wp_send_json_error( array( 'error' => __( 'Please choose API Host in the settings and try again.', 'buddyboss' ) ) );
			}

			$id             		= ! empty( $_POST['bp-zoom-meeting-id'] ) ? sanitize_text_field( filter_input( INPUT_POST, 'bp-zoom-meeting-id' ) ) : false;
			$meeting_id        		= ! empty( $_POST['bp-zoom-meeting-zoom-id'] ) ? sanitize_text_field( filter_input( INPUT_POST, 'bp-zoom-meeting-zoom-id' ) ) : false;
			$group_id               = ! empty( $_POST['bp-zoom-meeting-group-id'] ) ? sanitize_text_field( filter_input( INPUT_POST, 'bp-zoom-meeting-group-id' ) ) : false;
			$start_date             = ! empty( $_POST['bp-zoom-meeting-start-date'] ) ? sanitize_text_field( filter_input( INPUT_POST, 'bp-zoom-meeting-start-date' ) ) : bp_core_current_time();
			$timezone               = ! empty( $_POST['bp-zoom-meeting-timezone'] ) ? sanitize_text_field( filter_input( INPUT_POST, 'bp-zoom-meeting-timezone' ) ) : '';
			$duration               = ! empty( $_POST['bp-zoom-meeting-duration'] ) ? sanitize_text_field( filter_input( INPUT_POST, 'bp-zoom-meeting-duration' ) ) : '';
			$password               = ! empty( $_POST['bp-zoom-meeting-password'] ) ? sanitize_text_field( filter_input( INPUT_POST, 'bp-zoom-meeting-password' ) ) : '';
			$meeting_authentication = isset( $_POST['bp-zoom-meeting-registration'] );
			$join_before_host       = isset( $_POST['bp-zoom-meeting-join-before-host'] );
			$host_video             = isset( $_POST['bp-zoom-meeting-host-video'] );
			$participants_video     = isset( $_POST['bp-zoom-meeting-participants-video'] );
			$mute_participants      = isset( $_POST['bp-zoom-meeting-mute-participants'] );
			$waiting_room           = isset( $_POST['bp-zoom-meeting-waiting-room'] );
			$enforce_login          = isset( $_POST['bp-zoom-meeting-authentication'] );
			$auto_recording         = ! empty( $_POST['bp-zoom-meeting-recording'] ) ? sanitize_text_field( filter_input( INPUT_POST, 'bp-zoom-meeting-recording' ) ) : 'none';
			$alternative_host_ids   = ! empty( $_POST['bp-zoom-meeting-alt-host-ids'] ) ? $_POST['bp-zoom-meeting-alt-host-ids'] : false;
			$title          		= ! empty( $_POST['bp-zoom-meeting-title'] ) ? sanitize_text_field( filter_input( INPUT_POST, 'bp-zoom-meeting-title' ) ) : '';

			$data = array(
					'user_id'                => $user_id,
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
					'title'          		 => $title,
			);

			if ( ! empty( $meeting_id ) ) {
				$data['meeting_id'] = $meeting_id;
				$zoom_meeting = bp_zoom_conference()->update_meeting( $data );
			} else {
				$zoom_meeting = bp_zoom_conference()->create_meeting( $data );
			}

			if ( ! empty( $zoom_meeting['code'] ) && in_array( $zoom_meeting['code'], array( 201, 204 ), true ) ) {
				if ( ! empty( $zoom_meeting['response'] ) ) {
					$data['zoom_details']    = serialize( $zoom_meeting['response'] );
					$data['zoom_join_url']   = $zoom_meeting['response']->join_url;
					$data['zoom_start_url']  = $zoom_meeting['response']->start_url;
					$data['zoom_meeting_id'] = $zoom_meeting['response']->id;
				}

				if ( ! empty( $id ) ) {
					$data['id'] = $id;
				}

				if ( ! empty( $meeting_id ) ) {
					$data['zoom_meeting_id'] = $meeting_id;
				}

				if ( ! empty( $group_id ) ) {
					$data['group_id'] = $group_id;
				}

				if ( is_array( $alternative_host_ids ) ) {
					$data['alternative_host_ids'] = implode( ',', $alternative_host_ids );
				}

				$id = bp_zoom_meeting_add( $data );

				if ( ! $id ) {
					wp_send_json_error( array( 'error' => __( 'There was a error saving the meeting.', 'buddyboss' ) ) );
				}

				$group_link = bp_get_group_permalink( groups_get_group( $group_id ) );
				$redirect_url = trailingslashit( $group_link . 'zoom/meetings/' . $id );
				wp_send_json_success( array( 'redirect_url' => $redirect_url ) );
			}

			if ( ! empty( $zoom_meeting['code'] ) && in_array( $zoom_meeting['code'], array( 300, 404, 400 ), true ) ) {
				wp_send_json_error( array( 'error' => $zoom_meeting['response']->message ) );
			}
		}

		/**
		 * Zoom meeting delete
		 * @since BuddyBoss 1.2.10
		 */
		public function zoom_meeting_delete() {
			if ( ! bp_is_post_request() ) {
				return;
			}

			// Nonce check!
			if ( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'bp_zoom_meeting_delete' ) ) {
				return;
			}

			$id         = ! empty( $_POST['id'] ) ? sanitize_text_field( filter_input( INPUT_POST, 'id' ) ) : false;
			$meeting_id = ! empty( $_POST['meeting_id'] ) ? sanitize_text_field( filter_input( INPUT_POST, 'meeting_id' ) ) : false;

			$meeting_deleted = bp_zoom_conference()->delete_meeting( $meeting_id );

			if ( isset( $meeting_deleted['code'] ) && 204 === $meeting_deleted['code'] && bp_zoom_meeting_delete( array( 'id' => $id ) ) ) {
				wp_send_json_success( array( 'deleted' => true ) );
			}

			wp_send_json_success( array( 'deleted' => $meeting_deleted ) );
		}

		/**
		 * Zoom meeting recordings list
		 * @since BuddyBoss 1.2.10
		 */
		public function zoom_meeting_recordings() {
			$meeting_id = filter_input( INPUT_GET, 'meeting_id' );
			$recordings = bp_zoom_conference()->recordings_by_meeting( $meeting_id );

			if ( ! empty( $recordings['response'] ) ) {
				$recordings = $recordings['response'];
			} else {
				wp_send_json_error( array( 'error' => true ) );
			}

			if ( ! empty( $recordings->recording_count ) && $recordings->recording_count > 0 ) {

				$count = 1;
				ob_start();
				foreach( $recordings->recording_files as $recording_file ) {
					?>
						<div class="recording-list-row-wrap">
							<div class="recording-list-row">
								<div class="recording-list-row-col">
									<p class="clip_title"><?php _e( 'Recording', 'buddyboss' ); ?> <?php echo $count; ?></p>
								</div>
								<div class="recording-list-row-col">
									<div class="video_link">
										<a class="play_btn" href="<?php echo esc_url( $recording_file->play_url ); ?>" target="_blank"><?php _e( 'Play', 'buddyboss' ); ?></a>
									</div>
								</div>
								<div class="recording-list-row-col">
									<div class="video_link">
										<span class="clip_description"><?php echo bp_core_format_size_units( $recording_file->file_size, true ); ?></span>
									</div>
								</div>
								<div class="recording-list-row-col">
									<a href="<?php echo esc_url( $recording_file->download_url ); ?>" target="_blank" class="btn btn-default downloadmeeting downloadclip"><?php _e( 'Download', 'buddyboss' ); ?></a>
								</div>
							</div>
						</div>
					<?php
					$count++;
				}

				wp_send_json_success( array( 'recordings' => ob_get_clean() ) );
			} else {
				wp_send_json_error( array( 'error' => $recordings->message ) );
			}
		}

		/**
		 * Zoom meeting load more list
		 * @since BuddyBoss 1.2.10
		 */
		public function zoom_meeting_load_more() {
			ob_start();
			if ( bp_has_zoom_meetings() ) {
				while ( bp_zoom_meeting() ) {
					bp_the_zoom_meeting();

					bp_get_template_part( 'groups/single/zoom/loop-meeting' );
				}
				if ( bp_zoom_meeting_has_more_items() ) {
					?>
					<div class="load-more">
						<a class="button full outline"
						   href="<?php bp_zoom_meeting_load_more_link(); ?>"><?php esc_html_e( 'Load More', 'buddyboss' ); ?></a>
					</div>
					<?php
				}
			}
			$response = ob_get_clean();
			wp_send_json_success( array( 'contents' => $response ) );
		}
	}

	new BP_Zoom_Ajax();
}

