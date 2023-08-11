<?php

/**
 * Forums BuddyBoss Activity Class
 *
 * @package BuddyBoss\Forums
 * @since bbPress (r3395)
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'BBP_BuddyPress_Activity' ) ) :
	/**
	 * Loads BuddyBoss Activity extension
	 *
	 * @since bbPress (r3395)
	 */
	class BBP_BuddyPress_Activity {

		/** Variables *************************************************************/

		/**
		 * The name of the BuddyBoss component, used in activity feeds
		 *
		 * @var string
		 */
		private $component = '';

		/**
		 * Forum Create Activity Action
		 *
		 * @var string
		 */
		private $forum_create = '';

		/**
		 * Topic Create Activity Action
		 *
		 * @var string
		 */
		private $topic_create = '';

		/**
		 * Topic Close Activity Action
		 *
		 * @var string
		 */
		private $topic_close = '';

		/**
		 * Topic Edit Activity Action
		 *
		 * @var string
		 */
		private $topic_edit = '';

		/**
		 * Topic Open Activity Action
		 *
		 * @var string
		 */
		private $topic_open = '';

		/**
		 * Reply Create Activity Action
		 *
		 * @var string
		 */
		private $reply_create = '';

		/**
		 * Reply Edit Activity Action
		 *
		 * @var string
		 */
		private $reply_edit = '';

		/** Setup Methods *********************************************************/

		/**
		 * The Forums BuddyBoss Activity loader
		 *
		 * @since bbPress (r3395)
		 */
		public function __construct() {
			$this->setup_globals();
			$this->setup_actions();
			$this->setup_filters();
			$this->fully_loaded();
		}

		/**
		 * Extension variables
		 *
		 * @since bbPress (r3395)
		 * @access private
		 * @uses apply_filters() Calls various filters
		 */
		private function setup_globals() {

			// The name of the BuddyBoss component, used in activity feeds
			$this->component = 'bbpress';

			// Forums
			$this->forum_create = 'bbp_forum_create';

			// Topics
			$this->topic_create = 'bbp_topic_create';
			$this->topic_edit   = 'bbp_topic_edit';
			$this->topic_close  = 'bbp_topic_close';
			$this->topic_open   = 'bbp_topic_open';

			// Replies
			$this->reply_create = 'bbp_reply_create';
			$this->reply_edit   = 'bbp_reply_edit';
		}

		/**
		 * Setup the actions
		 *
		 * @since bbPress (r3395)
		 * @access private
		 * @uses add_filter() To add various filters
		 * @uses add_action() To add various actions
		 */
		private function setup_actions() {

			// Register the activity stream actions
			add_action( 'bp_register_activity_actions', array( $this, 'register_activity_actions' ) );

			// Hook into topic and reply creation
			add_action( 'bbp_new_topic', array( $this, 'topic_create' ), 10, 4 );
			add_action( 'bbp_new_reply', array( $this, 'reply_create' ), 10, 5 );

			// Hook into topic and reply status changes
			add_action( 'edit_post', array( $this, 'topic_update' ), 10, 2 );
			add_action( 'edit_post', array( $this, 'reply_update' ), 10, 2 );

			// Hook into topic and reply delete.
			add_action( 'bbp_delete_topic', array( $this, 'topic_delete' ), 10, 1 );
			add_action( 'bbp_delete_reply', array( $this, 'reply_delete' ), 10, 1 );
		}

		/**
		 * Setup the filters
		 *
		 * @since bbPress (r3395)
		 * @access private
		 * @uses add_filter() To add various filters
		 * @uses add_action() To add various actions
		 */
		private function setup_filters() {

			// Link directly to the topic or reply
			add_filter( 'bp_activity_get_permalink', array( $this, 'activity_get_permalink' ), 10, 2 );

			// todo: we don't need this anymore because reply and topic notification mention permalink will always be their own links not activity's
			// todo: but keeping this handle backward compatibility
			// topic or reply mention notification permalink
			add_filter( 'bp_activity_new_at_mention_permalink', array( $this, 'activity_get_notification_permalink' ), 10, 4 );

			// Forum Activity scope to fetch the subscribed forums and topics feed.
			add_filter( 'bp_activity_set_forums_scope_args', array( $this, 'activity_forums_scope' ), 10, 2 );

			// Filter group icon for activity title.
			add_filter( 'bp_get_activity_secondary_avatar', array( $this, 'activity_secondary_avatar' ) );

			// Filter for single topic.
			add_filter( 'bbp_is_single_topic', array( $this, 'set_single_topic' ) );

			// Filter discussion.
			add_filter( 'bp_get_activity_content_body', array( $this, 'before_activity_content' ), 10, 2 );

			// Meta button for activity discussion.
			add_filter( 'bb_nouveau_get_activity_inner_buttons', array( $this, 'nouveau_get_activity_entry_buttons' ), 10, 2 );

			// Allow slash in topic reply.
			add_filter( 'bbp_activity_topic_create_excerpt', 'addslashes', 5 );
			add_filter( 'bbp_activity_reply_create_excerpt', 'addslashes', 5 );
		}

		/**
		 * Allow the variables, actions, and filters to be modified by third party
		 * plugins and themes.
		 *
		 * @since bbPress (r3902)
		 */
		private function fully_loaded() {
			do_action_ref_array( 'bbp_buddypress_activity_loaded', array( $this ) );
		}

		/** Methods ***************************************************************/

		/**
		 * Register our activity actions with BuddyBoss
		 *
		 * @since bbPress (r3395)
		 * @uses bp_activity_set_action()
		 */
		public function register_activity_actions() {

			// Sitewide activity stream items
			bp_activity_set_action(
				$this->component,
				$this->topic_create,
				esc_html__( 'New forum discussion', 'buddyboss' ),
				array( $this, 'bbp_format_activity_action_new_topic' ),
				esc_html__( 'Discussions', 'buddyboss' ),
				array( 'activity', 'member', 'member_groups', 'group' )
				
			);

			bp_activity_set_action(
				$this->component,
				$this->reply_create,
				esc_html__( 'New forum reply', 'buddyboss' ),
				array( $this, 'bbp_format_activity_action_new_reply' ),
				esc_html__( 'Replies', 'buddyboss' ),
				array( 'activity', 'member', 'member_groups', 'group' )
			);

		}

		/**
		 * Wrapper for recoding Forums actions to the BuddyBoss activity stream
		 *
		 * @since bbPress (r3395)
		 * @param type $args Array of arguments for bp_activity_add()
		 * @uses bbp_get_current_user_id()
		 * @uses bp_core_current_time()
		 * @uses bbp_parse_args()
		 * @uses aplly_filters()
		 * @uses bp_activity_add()
		 * @return type Activity ID if successful, false if not
		 */
		private function record_activity( $args = array() ) {

			// Default activity args
			$activity = bbp_parse_args(
				$args,
				array(
					'id'                => null,
					'user_id'           => bbp_get_current_user_id(),
					'type'              => '',
					'action'            => '',
					'item_id'           => '',
					'secondary_item_id' => '',
					'content'           => '',
					'primary_link'      => '',
					'component'         => $this->component,
					'recorded_time'     => bp_core_current_time(),
					'hide_sitewide'     => false,
				),
				'record_activity'
			);

			// Add the activity
			return bp_activity_add( $activity );
		}

		/**
		 * Wrapper for deleting Forums actions from BuddyBoss activity stream
		 *
		 * @since bbPress (r3395)
		 * @param type $args Array of arguments for bp_activity_add()
		 * @uses bbp_get_current_user_id()
		 * @uses bp_core_current_time()
		 * @uses bbp_parse_args()
		 * @uses aplly_filters()
		 * @uses bp_activity_add()
		 * @return type Activity ID if successful, false if not
		 */
		public function delete_activity( $args = '' ) {

			// Default activity args
			$activity = bbp_parse_args(
				$args,
				array(
					'item_id'           => false,
					'component'         => $this->component,
					'type'              => false,
					'user_id'           => false,
					'secondary_item_id' => false,
				),
				'delete_activity'
			);

			// Delete the activity
			bp_activity_delete_by_item_id( $activity );
		}

		/**
		 * Check for an existing activity stream entry for a given post_id
		 *
		 * @param int $post_id ID of the topic or reply
		 * @uses get_post_meta()
		 * @uses bp_activity_get_specific()
		 * @return int if an activity id is verified, false if not
		 */
		private static function get_activity_id( $post_id = 0 ) {

			// Try to get the activity ID of the post
			$activity_id = (int) get_post_meta( $post_id, '_bbp_activity_id', true );

			// Bail if no activity ID is in post meta
			if ( empty( $activity_id ) ) {
				return null;
			}

			// Get the activity stream item, bail if it doesn't exist
			$existing = new BP_Activity_Activity( $activity_id );
			if ( empty( $existing->component ) ) {
				return null;
			}

			// Return the activity ID since we've verified the connection
			return $activity_id;
		}

		/**
		 * Maybe link directly to topics and replies in activity stream entries
		 *
		 * @since bbPress (r3399)
		 * @param string $link
		 * @param mixed  $activity_object
		 * @return string The link to the activity stream item
		 */
		public function activity_get_permalink( $link = '', $activity_object = false ) {

			// Setup the array of actions to link directly to
			$disabled_actions = array(
				$this->topic_create,
				$this->reply_create,
			);

			// Check if this activity stream action is directly linked
			if ( in_array( $activity_object->type, $disabled_actions ) ) {
				$link = $activity_object->primary_link;
			}

			return $link;
		}

		/**
		 * - Generate permalink for reply and topic mention notification.
		 *
		 * @since BuddyBoss 1.2.5
		 *
		 * @param $link
		 * @param $item_id
		 * @param $secondary_item_id
		 * @param $total_items
		 *
		 * @return string
		 */
		function activity_get_notification_permalink( $link, $item_id, $secondary_item_id, $total_items ) {

			remove_filter( 'bp_activity_get_permalink', array( $this, 'activity_get_permalink' ), 10, 2 );
			$link = bp_activity_get_permalink( $item_id );
			add_filter( 'bp_activity_get_permalink', array( $this, 'activity_get_permalink' ), 10, 2 );

			return $link;
		}

		/**
		 * Render the activity content for discussion activity.
		 *
		 * @since BuddyBoss 1.7.2
		 *
		 * @param string $content  Activit content.
		 * @param object $activity Activit data.
		 *
		 * @uses bbp_get_reply()           Get reply post data.
		 * @uses bbp_get_topic_permalink() Get discussion permalink.
		 *
		 * @return string
		 */
		public function before_activity_content( $content, $activity ) {
			global $activities_template;

			// When the activity type does not match with the topic or reply.
			if ( ! in_array( $activity->type, array( $this->topic_create, $this->reply_create ), true ) ) {
				return $content;
			}

			// Set topic id when activity component is not groups.
			if ( $this->component === $activity->component ) {
				// Set topic id when activity type topic.
				$topic_id = $activity->item_id;

				// Set topic id when activity type reply.
				if ( $this->reply_create === $activity->type ) {
					$topic    = bbp_get_reply( $topic_id );
					$topic_id = $topic->post_parent;
				}
			}

			// Set topic id when activity component is groups.
			if ( 'groups' === $activity->component ) {
				// Set topic id when activity type topic.
				$topic_id = $activity->secondary_item_id;

				// Set topic id when activity type reply.
				if ( $this->reply_create === $activity->type ) {
					$topic    = bbp_get_reply( $topic_id );
					$topic_id = $topic->post_parent;
				}
			}

			// Topic.
			$topic_permalink = ( ! empty( $topic->ID ) && bbp_is_reply( $topic->ID ) ) ? bbp_get_reply_url( $topic->ID ) : bbp_get_topic_permalink( $topic_id );
			$topic_title     = get_post_field( 'post_title', $topic_id, 'raw' );
			$reply_to_text   = ( ! empty( $topic->ID ) && bbp_is_reply( $topic->ID ) ) ? sprintf( '<span class="bb-reply-lable">%1$s</span>', esc_html__( 'Reply to', 'buddyboss' ) ) : '';

			if ( ! bb_is_rest() ) {
				// Check if link embed or link preview and append the content accordingly.
				$post_id    = ( ! empty( $topic->ID ) && bbp_is_reply( $topic->ID ) ) ? $topic->ID : $topic_id;
				$link_embed = get_post_meta( $post_id, '_link_embed', true );
				if ( ! empty( $link_embed ) ) {
					if ( bbp_is_reply( $post_id ) ) {
						$content = bbp_reply_content_autoembed_paragraph( $content, $post_id );
					} else {
						$content = bbp_topic_content_autoembed_paragraph( $content, $post_id );
					}
				} else {
					$content = bb_forums_link_preview( $content, $post_id );
				}
			}

			if ( ! empty( $reply_to_text ) && ! empty( $topic_title ) ) {
				$content = sprintf( '<p class = "activity-discussion-title-wrap"><a href="%1$s">%2$s %3$s</a></p> <div class="bb-content-inr-wrap">%4$s</div>', esc_url( $topic_permalink ), $reply_to_text, $topic_title, $content );
			} elseif ( empty( $reply_to_text ) && ! empty( $topic_title ) ) {
				$content = sprintf( '<p class = "activity-discussion-title-wrap"><a href="%1$s">%2$s</a></p> <div class="bb-content-inr-wrap">%3$s</div>', esc_url( $topic_permalink ), $topic_title, $content );
			} elseif ( ! empty( $reply_to_text ) && empty( $topic_title ) ) {
				$content = sprintf( '<p class = "activity-discussion-title-wrap"><a href="%1$s">%2$s</a></p> <div class="bb-content-inr-wrap">%3$s</div>', esc_url( $topic_permalink ), $reply_to_text, $content );
			}

			/**
			 * Filters the activity content for forum.
			 *
			 * @since BuddyBoss 2.3.50
			 *
			 * @param array $content  Activity content
			 * @param array $activity Activity object
			 */
			return apply_filters( 'bb_forum_before_activity_content', $content, $activity );
		}

		/**
		 * Meta button for activity discussion.
		 *
		 * @since BuddyBoss 1.7.2
		 *
		 * @param array $buttons     Array of buttons.
		 * @param int   $activity_id Activity ID.
		 *
		 * @uses  bp_activity_get_specific() Get activity post data.
		 * @uses  bbp_get_topic_forum_id()   Get forum id from topic id.
		 * @uses  get_post_field()           Get specific WP post field.
		 *
		 * @return array
		 */
		public function nouveau_get_activity_entry_buttons( $buttons, $activity_id ) {

			// Get activity post data.
			$activities = bp_activity_get_specific( array( 'activity_ids' => $activity_id ) );

			if ( empty( $activities['activities'] ) ) {
				return $buttons;
			}

			$activity = array_shift( $activities['activities'] );

			// Set default meta buttons when the activity type is not topic.
			if ( $this->topic_create === $activity->type ) {

				// Set topic id when the activity component is not groups.
				if ( $this->component === $activity->component ) {
					$topic_id = $activity->item_id;
				}

				// Set topic id when the activity component is groups.
				if ( 'groups' === $activity->component ) {
					$topic_id = $activity->secondary_item_id;
				}

				// New meta button as 'Join discussion'.
				$buttons['activity_discussionsss'] = array(
					'id'                => 'activity_discussionsss',
					'position'          => 5,
					'component'         => 'activity',
					'must_be_logged_in' => true,
					'button_element'    => 'a',
					'link_text'         => sprintf(
						'<span class="bp-screen-reader-text">%1$s</span> <span class="comment-count">%2$s</span>',
						__( 'Join Discussion', 'buddyboss' ),
						__( 'Join Discussion', 'buddyboss' )
					),
					'button_attr'       => array(
						'class'         => 'button bb-icon-l bb-icon-comments-square bp-secondary-action',
						'aria-expanded' => 'false',
						'href'          => bbp_get_topic_permalink( $topic_id ),
					),
				);
			}

			// Set default meta buttons when the activity type is not reply.
			if ( $this->reply_create === $activity->type ) {

				// Set topic id when the activity component is not groups.
				if ( $this->component === $activity->component ) {
					$reply_id = $activity->item_id;
					$topic    = bbp_get_reply( $reply_id );
					$topic_id = $topic->post_parent;
				}

				// Set topic id when the activity component is groups.
				if ( 'groups' === $activity->component ) {
					$reply_id = $activity->secondary_item_id;
					$topic    = bbp_get_reply( $reply_id );
					$topic_id = $topic->post_parent;
				}

				// Redirect to.
				$redirect_to = bbp_get_redirect_to();

				// Get the reply URL.
				$reply_url = bbp_get_reply_url( $reply_id, $redirect_to );

				// New meta button as 'Join discussion'.
				$buttons['activity_reply_discussion'] = array(
					'id'                => 'activity_reply_discussion',
					'position'          => 5,
					'component'         => 'activity',
					'must_be_logged_in' => true,
					'button_element'    => 'a',
					'link_text'         => sprintf(
						'<span class="bp-screen-reader-text">%1$s</span> <span class="comment-count">%2$s</span>',
						__( 'Join Discussion', 'buddyboss' ),
						__( 'Join Discussion', 'buddyboss' )
					),
					'button_attr'       => array(
						'class'         => 'button bb-icon-l bb-icon-comments-square bp-secondary-action',
						'aria-expanded' => 'false',
						'href'          => $reply_url,
					),
				);
			}

			return $buttons;
		}

		/** Topics ****************************************************************/

		/**
		 * Record an activity stream entry when a topic is created or updated
		 *
		 * @since bbPress (r3395)
		 * @param int   $topic_id
		 * @param int   $forum_id
		 * @param array $anonymous_data
		 * @param int   $topic_author_id
		 * @uses bbp_get_topic_id()
		 * @uses bbp_get_forum_id()
		 * @uses bbp_get_user_profile_link()
		 * @uses bbp_get_topic_permalink()
		 * @uses bbp_get_topic_title()
		 * @uses bbp_get_topic_content()
		 * @uses bbp_get_forum_permalink()
		 * @uses bbp_get_forum_title()
		 * @uses bp_create_excerpt()
		 * @uses apply_filters()
		 * @return Bail early if topic is by anonymous user
		 */
		public function topic_create( $topic_id = 0, $forum_id = 0, $anonymous_data = array(), $topic_author_id = 0 ) {

			// Bail early if topic is by anonymous user
			if ( ! empty( $anonymous_data ) ) {
				return;
			}

			// Validate activity data
			$user_id  = (int) $topic_author_id;
			$topic_id = bbp_get_topic_id( $topic_id );
			$forum_id = bbp_get_forum_id( $forum_id );

			// Bail if user is not active
			if ( bbp_is_user_inactive( $user_id ) ) {
				return;
			}

			// Bail if topic is not published
			if ( ! bbp_is_topic_published( $topic_id ) ) {
				return;
			}

			// User link for topic author
			$user_link = bbp_get_user_profile_link( $user_id );

			// Topic
			$topic_permalink = bbp_get_topic_permalink( $topic_id );
			$topic_title     = get_post_field( 'post_title', $topic_id, 'raw' );
			$topic_content   = get_post_field( 'post_content', $topic_id, 'raw' );
			$topic_link      = '<a href="' . $topic_permalink . '">' . $topic_title . '</a>';

			// Forum
			$forum_permalink = bbp_get_forum_permalink( $forum_id );
			$forum_title     = get_post_field( 'post_title', $forum_id, 'raw' );
			$forum_link      = '<a href="' . $forum_permalink . '">' . $forum_title . '</a>';

			// Activity action & text
			$activity_text    = sprintf( esc_html__( '%1$s started the discussion %2$s in the forum %3$s', 'buddyboss' ), $user_link, $topic_link, $forum_link );
			$activity_action  = apply_filters( 'bbp_activity_topic_create', $activity_text, $user_id, $topic_id, $forum_id );
			$activity_content = apply_filters( 'bbp_activity_topic_create_excerpt', $topic_content );

			// Remove activity's notification for mentions.
			add_action( 'bp_activity_before_save', function() {
				remove_action( 'bp_activity_after_save', 'bp_activity_at_name_send_emails' );
			}, 99 );

			// Compile and record the activity stream results
			$activity_id = $this->record_activity(
				array(
					'id'                => $this->get_activity_id( $topic_id ),
					'user_id'           => $user_id,
					'action'            => $activity_action,
					'content'           => $activity_content,
					'primary_link'      => $topic_permalink,
					'type'              => $this->topic_create,
					'item_id'           => $topic_id,
					'secondary_item_id' => $forum_id,
					'recorded_time'     => get_post_time( 'Y-m-d H:i:s', true, $topic_id ),
					'hide_sitewide'     => ! bbp_is_forum_public( $forum_id, false ),
				)
			);

			// Add the activity entry ID as a meta value to the topic
			if ( ! empty( $activity_id ) ) {
				update_post_meta( $topic_id, '_bbp_activity_id', $activity_id );
			}
		}

		/**
		 * Delete the activity stream entry when a topic is spammed, trashed, or deleted
		 *
		 * @param int $topic_id
		 * @uses bp_activity_delete()
		 */
		public function topic_delete( $topic_id = 0 ) {
			// Get activity ID, bail if it doesn't exist
			if ( $activity_id = $this->get_activity_id( $topic_id ) ) {
				return bp_activity_delete( array( 'id' => $activity_id ) );
			}

			return false;
		}

		/**
		 * Update the activity stream entry when a topic status changes
		 *
		 * @param int $post_id
		 * @param obj $post
		 * @uses get_post_type()
		 * @uses bbp_get_topic_post_type()
		 * @uses bbp_get_topic_id()
		 * @uses bbp_is_topic_anonymous()
		 * @uses bbp_get_public_status_id()
		 * @uses bbp_get_closed_status_id()
		 * @uses bbp_get_topic_forum_id()
		 * @uses bbp_get_topic_author_id()
		 * @return Bail early if not a topic, or topic is by anonymous user
		 */
		public function topic_update( $topic_id = 0, $post = null ) {

			// Bail early if not a topic
			if ( get_post_type( $post ) !== bbp_get_topic_post_type() ) {
				return;
			}

			$topic_id = bbp_get_topic_id( $topic_id );

			// Bail early if topic is by anonymous user
			if ( bbp_is_topic_anonymous( $topic_id ) ) {
				return;
			}

			// Action based on new status
			if ( in_array( $post->post_status, array( bbp_get_public_status_id(), bbp_get_closed_status_id() ) ) ) {

				// Validate topic data
				$forum_id        = bbp_get_topic_forum_id( $topic_id );
				$topic_author_id = bbp_get_topic_author_id( $topic_id );

				$this->topic_create( $topic_id, $forum_id, array(), $topic_author_id );
			} elseif( bbp_get_spam_status_id() === $post->post_status ) {

				// Mark related activity as spam if topic marked as spam.
				if ( $activity_id = $this->get_activity_id( $topic_id ) ) {

					$activity = new BP_Activity_Activity( $activity_id );

					if ( empty( $activity->id ) ) {
						return false;
					}

					do_action( 'bp_activity_before_action_spam_activity', $activity->id, $activity );

					// Mark as spam.
					bp_activity_mark_as_spam( $activity );
					$activity->save();
				}
				return false;
			} else {
				$this->topic_delete( $topic_id );
			}
		}

		/** Replies ***************************************************************/

		/**
		 * Record an activity stream entry when a reply is created
		 *
		 * @since bbPress (r3395)
		 * @param int   $topic_id
		 * @param int   $forum_id
		 * @param array $anonymous_data
		 * @param int   $topic_author_id
		 * @uses bbp_get_reply_id()
		 * @uses bbp_get_topic_id()
		 * @uses bbp_get_forum_id()
		 * @uses bbp_get_user_profile_link()
		 * @uses bbp_get_reply_url()
		 * @uses bbp_get_reply_content()
		 * @uses bbp_get_topic_permalink()
		 * @uses bbp_get_topic_title()
		 * @uses bbp_get_forum_permalink()
		 * @uses bbp_get_forum_title()
		 * @uses bp_create_excerpt()
		 * @uses apply_filters()
		 * @return Bail early if topic is by anonywous user
		 */
		public function reply_create( $reply_id = 0, $topic_id = 0, $forum_id = 0, $anonymous_data = array(), $reply_author_id = 0 ) {

			// Do not log activity of anonymous users
			if ( ! empty( $anonymous_data ) ) {
				return;
			}

			// Validate activity data
			$user_id  = (int) $reply_author_id;
			$reply_id = bbp_get_reply_id( $reply_id );
			$topic_id = bbp_get_topic_id( $topic_id );
			$forum_id = bbp_get_forum_id( $forum_id );

			// Bail if user is not active
			if ( bbp_is_user_inactive( $user_id ) ) {
				return;
			}

			// Bail if reply is not published
			if ( ! bbp_is_reply_published( $reply_id ) ) {
				return;
			}

			// Setup links for activity stream
			$user_link = bbp_get_user_profile_link( $user_id );

			// Reply
			$reply_url     = bbp_get_reply_url( $reply_id );
			$reply_content = get_post_field( 'post_content', $reply_id, 'raw' );

			// Topic
			$topic_permalink = bbp_get_topic_permalink( $topic_id );
			$topic_title     = get_post_field( 'post_title', $topic_id, 'raw' );
			$topic_link      = '<a href="' . $topic_permalink . '">' . $topic_title . '</a>';

			// Forum
			$forum_permalink = bbp_get_forum_permalink( $forum_id );
			$forum_title     = get_post_field( 'post_title', $forum_id, 'raw' );
			$forum_link      = '<a href="' . $forum_permalink . '">' . $forum_title . '</a>';

			// Activity action & text
			$activity_text    = sprintf( esc_html__( '%1$s replied to the discussion %2$s in the forum %3$s', 'buddyboss' ), $user_link, $topic_link, $forum_link );
			$activity_action  = apply_filters( 'bbp_activity_reply_create', $activity_text, $user_id, $reply_id, $topic_id );
			$activity_content = apply_filters( 'bbp_activity_reply_create_excerpt', $reply_content );

			// Remove activity's notification for mentions.
			add_action( 'bp_activity_before_save', function() {
				remove_action( 'bp_activity_after_save', 'bp_activity_at_name_send_emails' );
			}, 99 );

			// Compile and record the activity stream results
			$activity_id = $this->record_activity(
				array(
					'id'                => $this->get_activity_id( $reply_id ),
					'user_id'           => $user_id,
					'action'            => $activity_action,
					'content'           => $activity_content,
					'primary_link'      => $reply_url,
					'type'              => $this->reply_create,
					'item_id'           => $reply_id,
					'secondary_item_id' => $topic_id,
					'recorded_time'     => get_post_time( 'Y-m-d H:i:s', true, $reply_id ),
					'hide_sitewide'     => ! bbp_is_forum_public( $forum_id, false ),
				)
			);

			// Add the activity entry ID as a meta value to the reply
			if ( ! empty( $activity_id ) ) {
				update_post_meta( $reply_id, '_bbp_activity_id', $activity_id );
			}
		}

		/**
		 * Delete the activity stream entry when a reply is spammed, trashed, or deleted
		 *
		 * @param int $reply_id
		 * @uses get_post_meta()
		 * @uses bp_activity_delete()
		 */
		public function reply_delete( $reply_id ) {

			// Get activity ID, bail if it doesn't exist
			if ( $activity_id = $this->get_activity_id( $reply_id ) ) {
				return bp_activity_delete( array( 'id' => $activity_id ) );
			}

			return false;
		}

		/**
		 * Update the activity stream entry when a reply status changes
		 *
		 * @param int $post_id
		 * @param obj $post
		 * @uses get_post_type()
		 * @uses bbp_get_reply_post_type()
		 * @uses bbp_get_reply_id()
		 * @uses bbp_is_reply_anonymous()
		 * @uses bbp_get_public_status_id()
		 * @uses bbp_get_closed_status_id()
		 * @uses bbp_get_reply_topic_id()
		 * @uses bbp_get_reply_forum_id()
		 * @uses bbp_get_reply_author_id()
		 * @return Bail early if not a reply, or reply is by anonymous user
		 */
		public function reply_update( $reply_id, $post ) {

			// Bail early if not a reply
			if ( get_post_type( $post ) !== bbp_get_reply_post_type() ) {
				return;
			}

			$reply_id = bbp_get_reply_id( $reply_id );

			// Bail early if reply is by anonymous user
			if ( bbp_is_reply_anonymous( $reply_id ) ) {
				return;
			}

			// Action based on new status
			if ( bbp_get_public_status_id() === $post->post_status ) {

				// Validate reply data
				$topic_id        = bbp_get_reply_topic_id( $reply_id );
				$forum_id        = bbp_get_reply_forum_id( $reply_id );
				$reply_author_id = bbp_get_reply_author_id( $reply_id );

				$this->reply_create( $reply_id, $topic_id, $forum_id, array(), $reply_author_id );
			} elseif( bbp_get_spam_status_id() === $post->post_status ) {

				// Mark related activity as spam if reply marked as spam.
				if ( $activity_id = $this->get_activity_id( $reply_id ) ) {

					$activity = new BP_Activity_Activity( $activity_id );

					if ( empty( $activity->id ) ) {
						return false;
					}

					do_action( 'bp_activity_before_action_spam_activity', $activity->id, $activity );

					// Mark as spam.
					bp_activity_mark_as_spam( $activity );
					$activity->save();
				}
				return false;
			} else {
				$this->reply_delete( $reply_id );
			}
		}
		public function group_forum_topic_activity_action_callback( $action, $activity ) {
			$user_id  = $activity->user_id;
			$topic_id = $activity->secondary_item_id;
			$forum_id = bbp_get_topic_forum_id( $topic_id );

			// User
			$user_link = bbp_get_user_profile_link( $user_id );

			// Topic
			$topic_permalink = bbp_get_topic_permalink( $topic_id );
			$topic_title     = get_post_field( 'post_title', $topic_id, 'raw' );
			$topic_link      = '<a href="' . $topic_permalink . '">' . $topic_title . '</a>';

			// Group
			$group_id = bbp_forum_recursive_group_id( $forum_id );

			if ( empty( $group_id ) ) {
				$group_id = current( bbp_get_forum_group_ids( $forum_id ) );
			}

			$group      = groups_get_group( $group_id );
			$group_link = bp_get_group_link( $group );

			return sprintf(
				esc_html__( '%1$s started the discussion %2$s in the group %3$s', 'buddyboss' ),
				$user_link,
				$topic_link,
				$group_link
			);
		}

		public function group_forum_reply_activity_action_callback( $action, $activity ) {
			$user_id  = $activity->user_id;
			$reply_id = $activity->secondary_item_id;
			$forum_id = bbp_get_reply_forum_id( $reply_id );
			$topic_id = bbp_get_reply_topic_id( $reply_id );

			// User
			$user_link = bbp_get_user_profile_link( $user_id );

			// Topic
			$topic_permalink = bbp_get_topic_permalink( $topic_id );
			$topic_title     = get_post_field( 'post_title', $topic_id, 'raw' );
			$topic_link      = '<a href="' . $topic_permalink . '">' . $topic_title . '</a>';

			// Group
			$group_id = bbp_forum_recursive_group_id( $forum_id );

			if ( empty( $group_id ) ) {
				$group_id = current( bbp_get_forum_group_ids( $forum_id ) );
			}

			$group      = groups_get_group( $group_id );
			$group_link = bp_get_group_link( $group );

			return sprintf(
				esc_html__( '%1$s replied to the discussion %2$s in the group %3$s', 'buddyboss' ),
				$user_link,
				$topic_link,
				$group_link
			);
		}

		/**
		 * Set up activity arguments for use with the 'forum' scope.
		 *
		 * For details on the syntax, see {@link BP_Activity_Query}.
		 *
		 * @since BuddyBoss 1.5.5
		 *
		 * @param array $retval Empty array by default.
		 * @param array $filter Current activity arguments.
		 *
		 * @return array
		 */
		public function activity_forums_scope( $retval = array(), $filter = array() ) {

			// Determine the user_id.
			if ( ! empty( $filter['user_id'] ) ) {
				$user_id = $filter['user_id'];
			} else {
				$user_id = bp_displayed_user_id()
					? bp_displayed_user_id()
					: bp_loggedin_user_id();
			}

			$forum_ids = bbp_get_user_subscribed_forum_ids( $user_id );
			$topic_ids = bbp_get_user_subscribed_topic_ids( $user_id );

			if ( empty( $forum_ids ) ) {
				$forum_ids = array( 0 );
			}

			if ( empty( $topic_ids ) ) {
				$topic_ids = array( 0 );
			}

			$retval = array(
				'relation' => 'AND',
				array(
					'column'  => 'component',
					'compare' => '=',
					'value'   => 'bbpress',
				),
				array(
					'relation' => 'OR',
					array(
						'column'  => 'secondary_item_id',
						'compare' => 'IN',
						'value'   => (array) $forum_ids,
					),
					array(
						'column'  => 'secondary_item_id',
						'compare' => 'IN',
						'value'   => (array) $topic_ids,
					),
				),

				// we should only be able to view sitewide activity content for those the user.
				// is following.
				array(
					'column' => 'hide_sitewide',
					'value'  => 0,
				),

			);

			return $retval;
		}

		/**
		 * Remove the group icon from the discussion and reply title.
		 *
		 * @since BuddyBoss 1.7.2
		 *
		 * @param string $avatar The secondary avatar for current activity.
		 *
		 * @return string
		 */
		public function activity_secondary_avatar( $avatar ) {
			global $activities_template;

			// Set empty group icon when activity type is topic.
			if ( $this->topic_create === $activities_template->activity->type ) {
				return '';
			}

			// Set empty group icon when activity type is reply.
			if ( $this->reply_create === $activities_template->activity->type ) {
				return '';
			}

			return $avatar;
		}

		/**
		 * Generate "bb-modal bb-modal-box" class for quick reply form.
		 *
		 * @since BuddyBoss 1.7.2
		 *
		 * @param boolean $single_topic Single topic status.
		 *
		 * @uses bp_is_active()             Checking is it acitvity page.
		 * @uses bp_is_activity_component() Checking is it activity component.
		 *
		 * @return Boolean
		 */
		public function set_single_topic( $single_topic ) {
			// Default value when activity is disable.
			if ( ! bp_is_active( 'activity' ) ) {
				return $single_topic;
			}

			// Set true when current component is activity.
			if ( bp_is_activity_component() ) {
				return true;
			}

			return $single_topic;
		}

		/**
		 * Formats the dynamic activity action for new topics.
		 *
		 * @since bbPress 2.6.0 (r6370)
		 * @since BuddyBoss 2.4.00
		 *
		 * @param string $action   The current action string.
		 * @param object $activity The activity object.
		 *
		 * @return string The formatted activity action.
		 */
		function bbp_format_activity_action_new_topic( $action, $activity ) {
			$action = $this->bbp_format_activity_action_new_post( bbp_get_topic_post_type(), $action, $activity );

			/**
			* Filters the formatted activity action new topic string.
			*
			* @since bbPress 2.6.0 (r6370)
			* @since BuddyBoss 2.4.00
			*
			* @param string               $action   Activity action string value
			* @param BP_Activity_Activity $activity Activity item object
			*/
			return apply_filters( 'bbp_format_activity_action_new_topic', $action, $activity );
		}

		/**
		* Formats the dynamic activity action for new replies.
		*
		* @since bbPress 2.6.0 (r6370)
		* @since BuddyBoss 2.4.00
		*
		* @param string $action   The current action string.
		* @param object $activity The activity object.
		*
		* @return string The formatted activity action.
		*/
		function bbp_format_activity_action_new_reply( $action, $activity ) {
			$action = $this->bbp_format_activity_action_new_post( bbp_get_reply_post_type(), $action, $activity );

			/**
			* Filters the formatted activity action new reply string.
			*
			* @since bbPress 2.6.0 (r6370)
		 	* @since BuddyBoss 2.4.00
			*
			* @param string               $action   Activity action string value.
			* @param BP_Activity_Activity $activity Activity item object.
			*/
			return apply_filters( 'bbp_format_activity_action_new_reply', $action, $activity );
		}

		/**
		 * Generic function to format the dynamic activity title for topics/replies.
		 *
		 * @since bbPress 2.6.0 (r6370)
		 * @since BuddyBoss 2.4.00
		 *
		 * @param string               $type     The type of post. Expects `topic` or `reply`.
		 * @param string               $action   The current action string.
		 * @param BP_Activity_Activity $activity The activity object.
		 *
		 * @return string The formatted activity action.
		 */
		function bbp_format_activity_action_new_post( $type = '', $action = '', $activity = false ) {

			// Get actions.
			$actions = $this->bbp_get_activity_actions();

			// Bail early if we don't have a valid type.
			if ( ! in_array( $type, array_keys( $actions ), true ) ) {
				return $action;
			}

			// Bail if intercepted.
			$intercept = bbp_maybe_intercept( __FUNCTION__, func_get_args() );
			if ( bbp_is_intercepted( $intercept ) ) {
				return $intercept;
			}

			// Groups component
			if ( 'groups' === $activity->component ) {
				if ( 'topic' === $type ) {
					$topic_id = bbp_get_topic_id( $activity->secondary_item_id );
					$forum_id = bbp_get_topic_forum_id( $topic_id );
				} else {
					$topic_id = bbp_get_reply_topic_id( $activity->secondary_item_id );
					$forum_id = bbp_get_topic_forum_id( $topic_id );
				}

			// General component (bbpress/forums/other).
			} else {
				if ( 'topic' === $type ) {
					$topic_id = bbp_get_topic_id( $activity->item_id );
					$forum_id = bbp_get_forum_id( $activity->secondary_item_id );
				} else {
					$topic_id = bbp_get_topic_id( $activity->secondary_item_id );
					$forum_id = bbp_get_topic_forum_id( $topic_id );
				}
			}

			// User link for topic author
			$user_link = bbp_get_user_profile_link( $activity->user_id );

			// Topic link
			$topic_permalink = bbp_get_topic_permalink( $topic_id );
			$topic_title     = get_post_field( 'post_title', $topic_id, 'raw' );
			$topic_link      = '<a href="' . esc_url( $topic_permalink ) . '">' . esc_html( $topic_title ) . '</a>';

			// Forum link
			$forum_permalink = bbp_get_forum_permalink( $forum_id );
			$forum_title     = get_post_field( 'post_title', $forum_id, 'raw' );
			$forum_link      = '<a href="' . esc_url( $forum_permalink ) . '">' . esc_html( $forum_title ) . '</a>';

			// Format
			$activity_action = sprintf( $actions[ $type ], $user_link, $topic_link, $forum_link );

			/**
			* Filters the formatted activity action new activity string.
			*
			* @since bbPress 2.6.0 (r6370)
			* @since BuddyBoss 2.4.00
			*
			* @param string               $activity_action Activity action string value.
			* @param string               $type            The type of post. Expects `topic` or `reply`.
			* @param string               $action          The current action string.
			* @param BP_Activity_Activity $activity        The activity object.
			*/
			return apply_filters( 'bbp_format_activity_action_new_post', $activity_action, $type, $action, $activity );
		}

		/**
		 * Return an array of allowed activity actions.
		 *
		 * @since bbPress 2.6.0 (r6370)
		 * @since BuddyBoss 2.4.00
		 *
		 * @return array
		 */
		function bbp_get_activity_actions() {

			// Filter & return.
			return (array) apply_filters( 'bbp_get_activity_actions', array(
				'topic' => esc_html__( '%1$s started the discussion %2$s in the forum %3$s', 'buddyboss' ),
				'reply' => esc_html__( '%1$s replied to the discussion %2$s in the forum %3$s', 'buddyboss' )
			) );
		}
	}
endif;
