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
			// setup zoom.
			bp_zoom_conference()->zoom_api_key    = bp_zoom_api_key();
			bp_zoom_conference()->zoom_api_secret = bp_zoom_api_secret();

			if ( empty( bp_zoom_conference()->zoom_api_key ) || empty( bp_zoom_conference()->zoom_api_secret ) ) {
				return false;
			}

			$this->setup_filters();
			$this->setup_actions();

			// Register the template stack for buddyboss so that theme can overrride.
			bp_register_template_stack( array( $this, 'register_template' ) );
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
			add_action( 'wp_ajax_zoom_meeting_delete', array( $this, 'zoom_meeting_delete' ) );
			add_action( 'wp_ajax_zoom_meeting_recordings', array( $this, 'zoom_meeting_recordings' ) );

			// Adds a zoom metabox to the new BuddyBoss Group Admin UI
			add_action( 'bp_groups_admin_meta_boxes', array( $this, 'group_admin_ui_edit_screen' ) );

			// Saves the zoom options if they come from the BuddyBoss Group Admin UI
			add_action( 'bp_group_admin_edit_after', array( $this, 'edit_screen_save' ) );
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

			// if current group has zoom enable then return.
			if ( bp_zoom_group_is_zoom_enabled( $current_group->id ) ) {
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
			}

			// If the user is a group admin, then show the group admin nav item.
			if ( bp_is_item_admin() ) {
				$admin_link = trailingslashit( $group_link . 'admin' );

				$sub_nav[] = array(
					'name'              => __( 'Zoom', 'buddyboss' ),
					'slug'              => 'zoom',
					'position'          => 100,
					'parent_url'        => $admin_link,
					'parent_slug'       => $current_group->slug . '_manage',
					'screen_function'   => 'groups_screen_group_admin',
					'user_has_access'   => bp_is_item_admin(),
					'show_in_admin_bar' => true,
				);
			}

			foreach ( $sub_nav as $nav ) {
				bp_core_new_subnav_item( $nav, 'groups' );
			}

			// save edit screen options.
			if ( bp_is_groups_component() && bp_is_current_action( 'admin' ) && bp_is_action_variable( 'zoom', 0 ) ) {
				$this->edit_screen_save( $current_group->id );

				// Load zoom admin page.
				add_action( 'bp_screens', array( $this, 'zoom_admin_page' ) );
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
		 * Zoom admin page callback
		 *
		 * @since BuddyBoss 1.2.10
		 */
		public function zoom_admin_page() {
			if ( 'zoom' != bp_get_group_current_admin_tab() ) {
				return false;
			}

			if ( ! bp_is_item_admin() && ! bp_current_user_can( 'bp_moderate' ) ) {
				return false;
			}
			add_action( 'groups_custom_edit_steps', array( $this, 'edit_screen' ) );
			bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'groups/single/home' ) );
		}

		/**
		 * Display zoom page content.
		 *
		 * @since BuddyBoss 1.2.10
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
			$recordings = bp_zoom_conference()->recordings_by_meeting( $meeting_id );

			if ( ! empty( $recordings['response'] ) ) {
				$recordings = json_decode( $recordings['response'] );
			} else {
				wp_send_json_error( array( 'error' => true ) );
			}

			if ( ! empty( $recordings->recording_count ) && $recordings->recording_count > 0 ) {

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
			} else {
				wp_send_json_error( array( 'error' => $recordings->message ) );
			}
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

			$meeting_created = bp_zoom_conference()->create_meeting( $data );
			if ( ! empty( $meeting_created['code'] ) && 201 === $meeting_created['code'] ) {
				$data['zoom_details']    = serialize( $meeting_created['response'] );
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
		 * Adds a zoom metabox to BuddyBoss Group Admin UI
		 *
		 * @since BuddyBoss 1.2.10
		 *
		 * @uses add_meta_box
		 */
		public function group_admin_ui_edit_screen() {
			add_meta_box(
				'bp_zoom_group_admin_ui_meta_box',
				__( 'Zoom Conference', 'buddyboss' ),
				array( $this, 'group_admin_ui_display_metabox' ),
				get_current_screen()->id,
				'side',
				'core'
			);
		}

		/**
		 * Displays the zoom metabox in BuddyBoss Group Admin UI
		 *
		 * @since BuddyBoss 1.2.10
		 *
		 * @param object $item (group object)
		 */
		public function group_admin_ui_display_metabox( $item ) {
			$this->edit_screen( $item );
		}

		/**
		 * Show zoom option form when editing a group
		 *
		 * @since BuddyBoss 1.2.10
		 * @param object $group (the group to edit if in Group Admin UI)
		 * @uses is_admin() To check if we're in the Group Admin UI
		 */
		public function edit_screen( $group = false ) {
			$group_id  = empty( $group->id ) ? bp_get_new_group_id() : $group->id;

			// Should box be checked already?
			$checked = is_admin() ? bp_zoom_group_is_zoom_enabled( $group_id ) : false || bp_zoom_group_is_zoom_enabled( bp_get_group_id() ); ?>

			<h4><?php esc_html_e( 'Group Zoom Settings', 'buddyboss' ); ?></h4>

			<fieldset>
				<legend class="screen-reader-text"><?php esc_html_e( 'Group Zoom Settings', 'buddyboss' ); ?></legend>
				<div class="field-group">
					<p class="checkbox bp-checkbox-wrap">
						<input type="checkbox" name="bp-edit-group-zoom" id="bp-edit-group-zoom" class="bs-styled-checkbox" value="1"<?php checked( $checked ); ?> />
						<label for="bp-edit-group-zoom"><?php esc_html_e( 'Yes. I want this group to have a zoom conference.', 'buddyboss' ); ?></label>
					</p>
				</div>

				<?php if ( ! is_admin() ) : ?>
					<input type="submit" value="<?php esc_attr_e( 'Save Settings', 'buddyboss' ); ?>" />
				<?php endif; ?>

			</fieldset>

			<?php

			// Verify intent
			if ( is_admin() ) {
				wp_nonce_field( 'groups_edit_save_zoom', 'zoom_group_admin_ui' );
			} else {
				wp_nonce_field( 'groups_edit_save_zoom' );
			}
		}

		/**
		 * Save the Group Zoom data on edit
		 *
		 * @since BuddyBoss 1.2.10
		 * @param int $group_id (to handle Group Admin UI hook bp_group_admin_edit_after )
		 */
		public function edit_screen_save( $group_id = 0 ) {

			// Bail if not a POST action
			if ( ! bp_is_post_request() ) {
				return;
			}

			// Admin Nonce check
			if ( is_admin() ) {
				check_admin_referer( 'groups_edit_save_zoom', 'zoom_group_admin_ui' );

				// Theme-side Nonce check
			} elseif ( ! wp_verify_nonce( $_REQUEST[ '_wpnonce' ], 'groups_edit_save_zoom' ) ) {
				return;
			}

			$edit_zoom = ! empty( $_POST['bp-edit-group-zoom'] ) ? true : false;
			$group_id  = ! empty( $group_id ) ? $group_id : bp_get_current_group_id();

			groups_update_groupmeta( $group_id, 'bp-group-zoom', $edit_zoom );

			/**
			 * Add action that fire before user redirect
			 *
			 * @Since BuddyBoss 1.1.5
			 *
			 * @param int $group_id Current group id
			 */
			do_action( 'bp_group_admin_after_edit_screen_save', $group_id );

			// Redirect after save when not in admin
			if ( ! is_admin() ) {
				bp_core_redirect( trailingslashit( bp_get_group_permalink( buddypress()->groups->current_group ) . '/admin/zoom' ) );
			}
		}
	}

	new BP_Group_Zoom();
}

