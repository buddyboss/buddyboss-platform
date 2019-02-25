<?php
/**
 * @package WordPress
 * @subpackage BuddyBoss Media
 */
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) )
	exit;

if ( !class_exists( 'BuddyBoss_Media_Tagging' ) ):

	class BuddyBoss_Media_Tagging {

		private static $instance;
		private $current_activity;

		public static function get_instance() {
			if ( !isset( self::$instance ) ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		private function __construct() {
			if ( function_exists( 'bp_is_active' ) && bp_is_active( 'friends' ) ) {
				if ( buddyboss_media()->option( 'enable_tagging' ) == 'yes' ) {
					$this->load();
				}
			}
		}

		protected function load() {
			add_action( 'bp_activity_entry_meta', array( $this, 'btn_tag_friends' ) );
			add_action( 'wp_footer', array( $this, 'print_tagging_ui' ) );

			//ajax
			add_action( 'wp_ajax_buddyboss_media_get_tags', array( $this, 'ajax_get_tags' ) );
			add_action( 'wp_ajax_buddyboss_media_tag_friends', array( $this, 'ajax_tag_friends' ) );
			add_action( 'wp_ajax_buddyboss_media_tag_complete', array( $this, 'ajax_tag_complete' ) );

			add_filter( 'bboss_media_tagged_friends', array( $this, 'filter_by_tagged_friends' ) );
		}

		public function btn_tag_friends() {
			if ( $this->can_tag_in_activity() ) {
				?>
				<a href="#" class="button bp-secondary-action buddyboss_media_tag" onclick="return buddyboss_media_initiate_tagging( this );" data-activity_id="<?php bp_activity_id(); ?>" title="<?php _e( 'Tag Photo', 'buddyboss-media' ); ?>">
					<?php _e( 'Tag Photo', 'buddyboss-media' ); ?>
				</a>
				<?php
			}
		}

		public function print_tagging_ui() {
			if ( $this->can_user_tag() ) {
				buddyboss_media_load_template( 'tag-friends' );
			}
		}

		/**
		 * Determines if the current user can tag his/her friends
		 * @param type $user_id
		 */
		protected function can_user_tag( $user_id = false ) {
			$can_tag = false;
			/**
			 * Can only tag if all the following conditions are met:
			 * 1. Must be logged in
			 * 2. User Must have atleast one friend
			 */
			if ( !is_user_logged_in() )
				return false;

			if ( !$user_id )
				$user_id = bp_loggedin_user_id();

			if ( $this->get_friends_count_for_user( $user_id ) > 0 ) {
				$can_tag = true;
			}

			return $can_tag;
		}

		/**
		 * Determines if the current user can tag his/her friends in given activity.
		 *
		 * @param mixed $activity
		 * @return boolean
		 */
		protected function can_tag_in_activity( $activity = false ) {
			$can_tag = false;

			if ( $this->can_user_tag() ) {
				global $activities_template;

				// Try to use current activity if none was passed
				if ( empty( $activity ) && !empty( $activities_template->activity ) ) {
					$activity = $activities_template->activity;
				}

				/**
				 * 1. Acitity must be a buddyboss_media activity
				 * 2. Current user must be the author of activity
				 */
				if ( buddyboss_media_compat_get_meta( $activity->id, 'activity.action_keys' ) ) {
					if ( isset( $activity->user_id ) && ( (int) $activity->user_id === bp_loggedin_user_id() ) ) {
						$can_tag = true;
					}
				}
			}

			return (bool) apply_filters( 'buddyboss_media_user_can_tag_in_activity', $can_tag, $activity );
		}

		protected function get_friends_count_for_user( $user_id = false ) {
			if ( !$user_id )
				$user_id = bp_loggedin_user_id();

			//need to cache it, as it will be called multiple times
			if ( ( $friends_count = wp_cache_get( 'friends_count', $user_id ) ) === false ) {
				$friends_count = (int) friends_get_friend_count_for_user( $user_id );
				wp_cache_set( 'friends_count', $friends_count, $user_id );
			}
			return $friends_count;
		}

		public function ajax_get_tags() {
			check_ajax_referer( 'buddyboss_media_tag_friends', 'buddyboss_media_tag_friends_nonce' );

			$activity_id = isset( $_POST[ 'activity_id' ] ) ? (int) $_POST[ 'activity_id' ] : false;
            $exclude_ids = isset( $_POST[ 'exclude_ids' ] ) ? $_POST[ 'exclude_ids' ] : false;
            $search_term = isset( $_POST[ 'search_term' ] ) ? $_POST[ 'search_term' ] : false;

			$friends = $this->get_friends_list( $activity_id, $exclude_ids, $search_term );
			$friends_list = $friends['list'];
			//not using this for now
			//$tagged_friends = $this->activity_tagged_friends( $activity_id );

			$retval = array(
				'friends_list'      => $friends_list,
				'tagged_friends'    => '',
                'show_search'       => false,
                'total'             => isset( $friends['total'] ) ? $friends['total'] : 0,
                'more_i10'          => _x( 'and xx more..', 'xx will be replaced with real number', 'buddyboss-media' ),
			);

            if( $friends['total'] > $friends['args']['per_page'] ){
                $retval[ 'show_search' ] = true;
            }
			if ( empty( $friends_list ) ) {
                if( empty( $search_term ) ){
                    $retval[ 'error' ] = "<div id='message'><p class='error'>" . __( 'You have no friends yet!', 'buddyboss-media' ) . "</p></div>";
                } else {
                    $retval[ 'error' ] = "<div id='message'><p class='error'>" . __( 'Nothing found!', 'buddyboss-media' ) . "</p></div>";
                }
                $retval[ 'friends_list' ] = '';
			}

			die( json_encode( $retval ) );
		}

		protected function get_friends_list( $activity_id = false, $exclude_ids=false, $search_term='' ) {
			$friends = array();

			// User's friends
			$args = apply_filters( 'buddyboss_media_tag_friends_list', array(
				'user_id'       => bp_loggedin_user_id(),
				'type'          => 'alphabetical',
				'per_page'      => 20,
                'exclude'       => $exclude_ids,
                'search_terms'  => $search_term,
			) );

			// User has friends
			if ( bp_has_members( $args ) ) {
				$tagged_friends = array();
				if ( $activity_id ) {
					$tagged_friends = bp_activity_get_meta( $activity_id, 'bboss_media_tagged_friends', true );
				}

				while ( bp_members() ) {
					bp_the_member();

					// Get the user ID of the friend
					$friend_user_id = bp_get_member_user_id();

					$checked = '';
					if ( !empty( $tagged_friends ) ) {
						if ( in_array( $friend_user_id, $tagged_friends ) )
							$checked = ' checked="checked"';
					}

					$friends[] = '<li><input' . $checked . ' type="checkbox" name="friends[]" id="f-' . $friend_user_id . '" value="' . esc_attr( $friend_user_id ) . '" /> ' . bp_get_member_name() . '</li>';
				}
			}
            
            global $members_template;
            return array(
                'list'  => $friends,
                'total' => $members_template->total_member_count,
                'args'  => $args,
            );
		}

		protected function activity_tagged_friends( $activity_id ) {
			$this->current_activity = $activity_id;
			return buddyboss_media_buffer_template_part( 'loop-tagged-friends', false );
		}

		public function filter_by_tagged_friends( $args ) {
			$activity_id = $this->current_activity;
			if ( !$activity_id )
				$activity_id = bp_get_activity_id(); //if we are inside activity looop

			if ( !$activity_id )
				return $args;

			$tagged_friends = bp_activity_get_meta( $activity_id, 'bboss_media_tagged_friends', true );
			if ( !empty( $tagged_friends ) && is_array( $tagged_friends ) ) {
				$args[ 'include' ] = $tagged_friends;
			} else {
				//include no one
				$args[ 'include' ] = array( 99999999 ); //a very large number
			}

			return $args;
		}

		public function ajax_tag_friends() {
			check_ajax_referer( 'buddyboss_media_tag_friends', 'buddyboss_media_tag_friends_nonce' );

			$activity_id = isset( $_POST[ 'activity_id' ] ) ? (int) $_POST[ 'activity_id' ] : false;
			$friend_id	 = isset( $_POST[ 'friend_id' ] ) ? (int) $_POST[ 'friend_id' ] : false;

			if ( !$activity_id || !$friend_id )
				die();

			$this->update_activity_tags( $activity_id, $friend_id );

			die( json_encode( array( 'status'=>true ) ) );
		}

		protected function update_activity_tags( $activity_id, $friend_id ) {
			$tagged_friends		 = bp_activity_get_meta( $activity_id, 'bboss_media_tagged_friends', true );
			$is_already_tagged	 = false;

			if ( !empty( $tagged_friends ) ) {
				if ( in_array( $friend_id, $tagged_friends ) ) {
					$is_already_tagged		 = true;
					//remove user from tagged people list
					$tagged_friends_updated	 = array();
					foreach ( $tagged_friends as $tagged_friend ) {
						if ( $tagged_friend != $friend_id )
							$tagged_friends_updated[] = $tagged_friend;
					}

					$tagged_friends = $tagged_friends_updated;
				}
			}

			if ( !$is_already_tagged ) {
				$tagged_friends[] = $friend_id;
			}

			bp_activity_update_meta( $activity_id, 'bboss_media_tagged_friends', $tagged_friends );
		}

		public function ajax_tag_complete() {
			check_ajax_referer( 'buddyboss_media_tag_friends', 'buddyboss_media_tag_friends_nonce' );

			$activity_id = isset( $_POST[ 'activity_id' ] ) ? (int) $_POST[ 'activity_id' ] : false;

			if ( !$activity_id )
				die();

			if ( bp_is_active( 'notifications' ) ) {
				$notification_handler = BuddyBoss_Media_Tagging_Notifications::get_instance();
				//add buddypress notification
				$notification_handler->notifications_bp( $activity_id );

				//email notifications?
				//$this->notifications_email( $activity_id );
			}

			$retval = array(
				'status' => true,
			);

			//do we need to fetch update activity action?
			if ( isset( $_POST[ 'update_action' ] ) && $_POST[ 'update_action' ] == true ) {
				$retval[ 'activity_action' ] = $this->get_activity_action( $activity_id );

				//tooltip text
				$obj			 = BuddyBoss_Media_Tagging_Hooks::get_instance();
				$tooltip_text	 = $obj->activity_tagging_tooltip_text( $activity_id, false );

				$retval[ 'activity_tooltip' ] = !empty( $tooltip_text ) ? $tooltip_text : false;
			}

			die( json_encode( $retval ) );
		}

		protected function get_activity_action( $activity_id ) {
			$action = "";
			if ( bp_has_activities( array( 'include' => $activity_id ) ) ) {
				while ( bp_activities() ) {
					bp_the_activity();
					$action = bp_get_activity_action();
				}
			}
			return $action;
		}

	}

	// end BuddyBoss_Media_Tagging

	BuddyBoss_Media_Tagging::get_instance();
endif;