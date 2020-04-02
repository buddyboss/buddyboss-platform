<?php
use \Firebase\JWT\JWT;

/**
 * Class Connecting Zoom API
 *
 * @since   BuddyBoss 1.2.10
 */
if ( ! class_exists( 'BP_Zoom_Conference_Api' ) ) {

	class BP_Zoom_Conference_Api {

		/**
		 * Zoom API Key
		 *
		 * @var string
		 */
		public $zoom_api_key;

		/**
		 * Zoom API Secret
		 *
		 * @var string
		 */
		public $zoom_api_secret;

		/**
		 * Instance of BP_Zoom_Conference_Api
		 *
		 * @var object
		 */
		protected static $_instance;

		/**
		 * Zoom API URL
		 *
		 * @var string
		 */
		private $api_url = 'https://api.zoom.us/v2/';

		/**
		 * Create only one instance so that it may not Repeat
		 *
		 * @since 1.2.10
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}

			return self::$_instance;
		}

		public function __construct( $zoom_api_key = '', $zoom_api_secret = '' ) {
			$this->zoom_api_key    = $zoom_api_key;
			$this->zoom_api_secret = $zoom_api_secret;
		}

		protected function send_request( $called_function, $data, $request = 'GET' ) {
			$request_url = $this->api_url . $called_function;
			$args        = array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $this->generate_jwt_key(),
					'Content-Type'  => 'application/json'
				)
			);

			if ( 'POST' === $request ) {
				$args['body']   = ! empty( $data ) ? json_encode( $data ) : array();
				$args['method'] = 'POST';
				$response       = wp_remote_post( $request_url, $args );
			} else if ( 'DELETE' === $request ) {
				$args['body']   = ! empty( $data ) ? json_encode( $data ) : array();
				$args['method'] = 'DELETE';
				$response       = wp_remote_request( $request_url, $args );
			} else if ( 'PATCH' === $request ) {
				$args['body']   = ! empty( $data ) ? json_encode( $data ) : array();
				$args['method'] = 'PATCH';
				$response       = wp_remote_request( $request_url, $args );
			} else {
				$args['body'] = ! empty( $data ) ? $data : array();
				$response     = wp_remote_get( $request_url, $args );
			}

			$response = wp_remote_retrieve_body( $response );

			if ( ! $response ) {
				return false;
			}

			return $response;
		}

		/**
		 * Generate JWT Key
		 *
		 * @since BuddyBoss 1.2.10
		 * @return string JWT key
		 */
		private function generate_jwt_key() {
			$key    = $this->zoom_api_key;
			$secret = $this->zoom_api_secret;

			$token = array(
				"iss" => $key,
				"exp" => time() + 3600 //60 seconds as suggested
			);

			return JWT::encode( $token, $secret );
		}

		/**
		 * Creates a User
		 *
		 * @since BuddyBoss 1.2.10
		 *
		 * @param array $data
		 *
		 * @return array|bool|string
		 */
		public function create_user( $data = array() ) {
			$args              = array();
			$args['action']    = $data['action'];
			$args['user_info'] = array(
				'email'      => $data['email'],
				'type'       => $data['type'],
				'first_name' => $data['first_name'],
				'last_name'  => $data['last_name']
			);

			return $this->send_request( 'users', $args, 'POST' );
		}

		/**
		 * Get user list
		 *
		 * @since BuddyBoss 1.2.10
		 * @param $page
		 * @return array
		 */
		public function list_users( $page = 1 ) {
			$args                = array();
			$args['page_size']   = 300;
			$args['page_number'] = absint( $page );

			return $this->send_request( 'users', $args, 'GET' );
		}

		/**
		 * Get users info by user ID
		 *
		 * @since BuddyBoss 1.2.10
		 *
		 * @param $user_id
		 *
		 * @return array|bool|string
		 */
		public function get_user_info( $user_id ) {
			$args = array();

			return $this->send_request( 'users/' . $user_id, $args );
		}

		/**
		 * Delete a User
		 *
		 * @since BuddyBoss 1.2.10
		 * @param $user_id
		 *
		 * @return array|bool|string
		 */
		public function delete_user( $user_id ) {
			return $this->send_request( 'users/' . $user_id, false, 'DELETE' );
		}

		/**
		 * Get Meetings
		 *
		 * @since BuddyBoss 1.2.10
		 * @param $host_id
		 *
		 * @return array
		 */
		public function list_meetings( $host_id ) {
			$args              = array();
			$args['page_size'] = 300;

			return $this->send_request( 'users/' . $host_id . '/meetings', $args, 'GET' );
		}

		/**
		 * Create A meeting API
		 *
		 * @since BuddyBoss 1.2.10
		 * @param array $data
		 *
		 * @return object
		 */
		public function create_meeting( $data = array() ) {
			$post_time  = $data['start_date'];
			$start_time = gmdate( "Y-m-d\TH:i:s", strtotime( $post_time ) );

			$args = array();

			if ( ! empty( $data['alternative_host_ids'] ) ) {
				if ( count( $data['alternative_host_ids'] ) > 1 ) {
					$alternative_host_ids = implode( ",", $data['alternative_host_ids'] );
				} else {
					$alternative_host_ids = $data['alternative_host_ids'][0];
				}
			}

			$args['topic']      = $data['meeting_topic'];
			$args['agenda']     = ! empty( $data['agenda'] ) ? $data['agenda'] : '';
			$args['type']       = ! empty( $data['type'] ) ? $data['type'] : 2; //Scheduled
			$args['start_time'] = $start_time;
			$args['timezone']   = $data['timezone'];
			$args['password']   = ! empty( $data['password'] ) ? $data['password'] : '';
			$args['duration']   = ! empty( $data['duration'] ) ? $data['duration'] : 60;
			$args['settings']   = array(
				'join_before_host'  => ! empty( $data['join_before_host'] ) ? true : false,
				'host_video'        => ! empty( $data['host_video'] ) ? true : false,
				'participant_video' => ! empty( $data['participants_video'] ) ? true : false,
				'mute_upon_entry'   => ! empty( $data['mute_participants'] ) ? true : false,
				'enforce_login'     => ! empty( $data['enforce_login'] ) ? true : false,
				'auto_recording'    => ! empty( $data['auto_recording'] ) ? $data['auto_recording'] : 'none',
				'alternative_hosts' => isset( $alternative_host_ids ) ? $alternative_host_ids : ''
			);

			return $this->send_request( 'users/' . $data['user_id'] . '/meetings', $args, 'POST' );
		}

		/**
		 * Updating Meeting Info
		 *
		 * @since BuddyBoss 1.2.10
		 * @param $update_data
		 *
		 * @return array
		 */
		public function update_meeting_info( $update_data = array() ) {
			$post_time  = $update_data['start_date'];
			$start_time = gmdate( "Y-m-d\TH:i:s", strtotime( $post_time ) );

			$args = array();

			if ( ! empty( $update_data['alternative_host_ids'] ) ) {
				if ( count( $update_data['alternative_host_ids'] ) > 1 ) {
					$alternative_host_ids = implode( ",", $update_data['alternative_host_ids'] );
				} else {
					$alternative_host_ids = $update_data['alternative_host_ids'][0];
				}
			}

			$args['topic']      = $update_data['topic'];
			$args['agenda']     = ! empty( $update_data['agenda'] ) ? $update_data['agenda'] : '';
			$args['type']       = ! empty( $update_data['type'] ) ? $update_data['type'] : 2; //Scheduled
			$args['start_time'] = $start_time;
			$args['timezone']   = $update_data['timezone'];
			$args['password']   = ! empty( $update_data['password'] ) ? $update_data['password'] : '';
			$args['duration']   = ! empty( $update_data['duration'] ) ? $update_data['duration'] : 60;
			$args['settings']   = array(
				'join_before_host'  => ! empty( $update_data['option_jbh'] ) ? true : false,
				'host_video'        => ! empty( $update_data['option_host_video'] ) ? true : false,
				'participant_video' => ! empty( $update_data['option_participants_video'] ) ? true : false,
				'mute_upon_entry'   => ! empty( $update_data['option_mute_participants'] ) ? true : false,
				'enforce_login'     => ! empty( $update_data['option_enforce_login'] ) ? true : false,
				'auto_recording'    => ! empty( $update_data['option_auto_recording'] ) ? $update_data['option_auto_recording'] : 'none',
				'alternative_hosts' => isset( $alternative_host_ids ) ? $alternative_host_ids : ''
			);

			return $this->send_request( 'meetings/' . $update_data['meeting_id'], $args, 'PATCH' );
		}

		/**
		 * Get a Meeting Info
		 *
		 * @since BuddyBoss 1.2.10
		 * @param  int $meeting_id
		 *
		 * @return array
		 */
		public function get_meeting_info( $meeting_id ) {
			$args = array();

			return $this->send_request( 'meetings/' . $meeting_id, $args, 'GET' );
		}

		/**
		 * Delete A Meeting
		 *
		 * @since BuddyBoss 1.2.10
		 * @param int $meeting_id
		 *
		 * @return array
		 */
		public function deleteAMeeting( $meeting_id ) {
			$args = array();

			return $this->send_request( 'meetings/' . $meeting_id, $args, 'DELETE' );
		}

		/**
		 * Get daily account reports by month
		 *
		 * @since BuddyBoss 1.2.10
		 * @param $month
		 * @param $year
		 *
		 * @return bool|mixed
		 */
		public function get_daily_report( $month, $year ) {
			$args          = array();
			$args['year']  = $year;
			$args['month'] = $month;

			return $this->send_request( 'report/daily', $args, 'GET' );
		}

		/**
		 * Get Account Reports
		 *
		 * @since BuddyBoss 1.2.10
		 * @param $zoom_account_from
		 * @param $zoom_account_to
		 *
		 * @return array
		 */
		public function get_account_report( $zoom_account_from, $zoom_account_to ) {
			$args              = array();
			$args['from']      = $zoom_account_from;
			$args['to']        = $zoom_account_to;
			$args['page_size'] = 300;

			return $this->send_request( 'report/users', $args, 'GET' );
		}

		/**
		 * Register webiner participants
		 *
		 * @since BuddyBoss 1.2.10
		 * @param $webinar_id
		 * @param $first_name
		 * @param $last_name
		 * @param $email
		 *
		 * @return mixed
		 */
		public function register_webinar_participants( $webinar_id, $first_name, $last_name, $email ) {
			$data               = array();
			$data['first_name'] = $first_name;
			$data['last_name']  = $last_name;
			$data['email']      = $email;

			return $this->send_request( 'webinars/' . $webinar_id . '/registrants', $data, 'POST' );
		}

		/**
		 * List webinars
		 *
		 * @since BuddyBoss 1.2.10
		 * @param $user_id
		 *
		 * @return bool|mixed
		 */
		public function list_webinar( $user_id ) {
			$data              = array();
			$data['page_size'] = 300;

			return $this->send_request( 'users/' . $user_id . '/webinars', $data, 'GET' );
		}

		/**
		 * List Webinar Participants
		 *
		 * @since BuddyBoss 1.2.10
		 * @param $webinar_id
		 *
		 * @return bool|mixed
		 */
		public function list_webinar_participants( $webinar_id ) {
			$data              = array();
			$data['page_size'] = 300;

			return $this->send_request( 'webinars/' . $webinar_id . '/registrants', $data, 'GET' );
		}

		/**
		 * Get recording by meeting ID
		 *
		 * @since BuddyBoss 1.2.10
		 * @param $meeting_id
		 *
		 * @return bool|mixed
		 */
		public function recordings_by_meeting( $meeting_id ) {
			return $this->send_request( 'meetings/' . $meeting_id . '/recordings', false, 'GET' );
		}

		/**
		 * Get all recordings by USER ID
		 *
		 * @since BuddyBoss 1.2.10
		 * @param $host_id
		 * @param $data array
		 *
		 * @return bool|mixed
		 */
		public function list_recording( $host_id, $data = array() ) {
			$post_data = array();
			$from     = date( 'Y-m-d', strtotime( '-1 year', time() ) );
			$to       = date( 'Y-m-d' );

			$post_data['from'] = ! empty( $data['from'] ) ? $data['from'] : $from;
			$post_data['to']   = ! empty( $data['to'] ) ? $data['to'] : $to;

			return $this->send_request( 'users/' . $host_id . '/recordings', $post_data, 'GET' );
		}
	}

	function bp_zoom_conference() {
		return BP_Zoom_Conference_Api::instance();
	}

	bp_zoom_conference();
}
