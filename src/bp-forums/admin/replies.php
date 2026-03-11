<?php

/**
 * Forums Replies Admin Class
 *
 * @package BuddyBoss\Administration
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'BBP_Replies_Admin' ) ) :
	/**
	 * Loads Forums replies admin area
	 *
	 * @since bbPress (r2464)
	 */
	class BBP_Replies_Admin {

		/** Variables *************************************************************/

		/**
		 * @var The post type of this admin component
		 */
		private $post_type = '';

		/** Functions *************************************************************/

		/**
		 * The main Forums admin loader
		 *
		 * @since bbPress (r2515)
		 *
		 * @uses BBP_Replies_Admin::setup_globals() Setup the globals needed
		 * @uses BBP_Replies_Admin::setup_actions() Setup the hooks and actions
		 * @uses BBP_Replies_Admin::setup_actions() Setup the help text
		 */
		public function __construct() {
			$this->setup_globals();
			$this->setup_actions();
		}

		/**
		 * Setup the admin hooks, actions and filters
		 *
		 * @since bbPress (r2646)
		 * @access private
		 *
		 * @uses add_action() To add various actions
		 * @uses add_filter() To add various filters
		 * @uses bbp_get_forum_post_type() To get the forum post type
		 * @uses bbp_get_topic_post_type() To get the topic post type
		 * @uses bbp_get_reply_post_type() To get the reply post type
		 */
		private function setup_actions() {

			// Add some general styling to the admin area
			add_action( 'bbp_admin_head', array( $this, 'admin_head' ) );

			// Messages
			add_filter( 'post_updated_messages', array( $this, 'updated_messages' ) );

			// Reply metabox actions
			add_action( 'add_meta_boxes', array( $this, 'attributes_metabox' ) );
			add_action( 'save_post', array( $this, 'attributes_metabox_save' ) );

			// Anonymous metabox actions
			add_action( 'add_meta_boxes', array( $this, 'author_metabox' ) );

			// Contextual Help
			add_action( 'load-post.php', array( $this, 'new_help' ) );
			add_action( 'load-post-new.php', array( $this, 'new_help' ) );
		}

		/**
		 * Should we bail out of this method?
		 *
		 * @since bbPress (r4067)
		 * @return boolean
		 */
		private function bail() {
			if ( ! isset( get_current_screen()->post_type ) || ( $this->post_type !== get_current_screen()->post_type ) ) {
				return true;
			}

			return false;
		}

		/**
		 * Admin globals
		 *
		 * @since bbPress (r2646)
		 * @access private
		 */
		private function setup_globals() {
			$this->post_type = bbp_get_reply_post_type();
		}

		/** Contextual Help *******************************************************/

		/**
		 * Contextual help for Forums reply edit page
		 *
		 * @since bbPress (r3119)
		 * @uses get_current_screen()
		 */
		public function new_help() {

			if ( $this->bail() ) {
				return;
			}

			$customize_display = '<p>' . __( 'The title field and the big reply editing Area are fixed in place, but you can reposition all the other boxes using drag and drop, and can minimize or expand them by clicking the title bar of each box. Use the Screen Options tab to unhide more boxes (Excerpt, Send Trackbacks, Custom Fields, Discussion, Slug, Author) or to choose a 1- or 2-column layout for this screen.', 'buddyboss' ) . '</p>';

			get_current_screen()->add_help_tab(
				array(
					'id'      => 'customize-display',
					'title'   => __( 'Customizing This Display', 'buddyboss' ),
					'content' => $customize_display,
				)
			);

			get_current_screen()->add_help_tab(
				array(
					'id'      => 'title-reply-editor',
					'title'   => __( 'Title and Reply Editor', 'buddyboss' ),
					'content' =>
						  '<p>' . __( '<strong>Title</strong> - Enter a title for your reply. After you enter a title, you\'ll see the permalink below, which you can edit.', 'buddyboss' ) . '</p>' .
						  '<p>' . __( '<strong>Reply Editor</strong> - Enter the text for your reply. There are two modes of editing: Visual and HTML. Choose the mode by clicking on the appropriate tab. Visual mode gives you a WYSIWYG editor. Click the last icon in the row to get a second row of controls. The HTML mode allows you to enter raw HTML along with your reply text. You can insert media files by clicking the icons above the reply editor and following the directions. You can go to the distraction-free writing screen via the Fullscreen icon in Visual mode (second to last in the top row) or the Fullscreen button in HTML mode (last in the row). Once there, you can make buttons visible by hovering over the top area. Exit Fullscreen back to the regular reply editor.', 'buddyboss' ) . '</p>',
				)
			);

			$publish_box = '<p>' . __( '<strong>Publish</strong> - You can set the terms of publishing your reply in the Publish box. For Status, Visibility, and Publish (immediately), click on the Edit link to reveal more options. Visibility includes options for password-protecting a reply or making it stay at the top of your blog indefinitely (sticky). Publish (immediately) allows you to set a future or past date and time, so you can schedule a reply to be published in the future or backdate a reply.', 'buddyboss' ) . '</p>';

			if ( current_theme_supports( 'reply-thumbnails' ) && post_type_supports( 'reply', 'thumbnail' ) ) {
				$publish_box .= '<p>' . __( '<strong>Featured Image</strong> - This allows you to associate an image with your reply without inserting it. This is usually useful only if your theme makes use of the featured image as a reply thumbnail on the home page, a custom header, etc.', 'buddyboss' ) . '</p>';
			}

			get_current_screen()->add_help_tab(
				array(
					'id'      => 'reply-attributes',
					'title'   => __( 'Reply Attributes', 'buddyboss' ),
					'content' =>
						  '<p>' . __( 'Select the attributes that your reply should have:', 'buddyboss' ) . '</p>' .
						  '<ul>' .
							  '<li>' . __( '<strong>Forum</strong> dropdown determines the parent forum that the reply belongs to. Select the forum, or leave the default (Use Forum of Discussion) to post the reply in forum of the discussion.', 'buddyboss' ) . '</li>' .
							  '<li>' . __( '<strong>Discussion</strong> determines the parent discussion that the reply belongs to.', 'buddyboss' ) . '</li>' .
							  '<li>' . __( '<strong>Reply To</strong> determines the threading of the reply.', 'buddyboss' ) . '</li>' .
						  '</ul>',
				)
			);

			get_current_screen()->add_help_tab(
				array(
					'id'      => 'publish-box',
					'title'   => __( 'Publish Box', 'buddyboss' ),
					'content' => $publish_box,
				)
			);

			// Help Sidebar
			get_current_screen()->set_help_sidebar(
				'<p><strong>' . __( 'For more information:', 'buddyboss' ) . '</strong></p>' .
				'<p>' . __( '<a href="https://www.buddyboss.com/resources/">Documentation</a>', 'buddyboss' ) . '</p>'
			);
		}

		/**
		 * Add the reply attributes metabox
		 *
		 * @since bbPress (r2746)
		 *
		 * @uses bbp_get_reply_post_type() To get the reply post type
		 * @uses add_meta_box() To add the metabox
		 * @uses do_action() Calls 'bbp_reply_attributes_metabox'
		 */
		public function attributes_metabox() {

			if ( $this->bail() ) {
				return;
			}

			add_meta_box(
				'bbp_reply_attributes',
				__( 'Reply Attributes', 'buddyboss' ),
				'bbp_reply_metabox',
				$this->post_type,
				'normal',
				'high'
			);

			do_action( 'bbp_reply_attributes_metabox' );
		}

		/**
		 * Pass the reply attributes for processing
		 *
		 * @since bbPress (r2746)
		 *
		 * @param int $reply_id Reply id
		 * @uses current_user_can() To check if the current user is capable of
		 *                           editing the reply
		 * @uses do_action() Calls 'bbp_reply_attributes_metabox_save' with the
		 *                    reply id and parent id
		 * @return int Parent id
		 */
		public function attributes_metabox_save( $reply_id ) {

			if ( $this->bail() ) {
				return $reply_id;
			}

			// Bail if doing an autosave
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return $reply_id;
			}

			// Bail if not a post request
			if ( ! bbp_is_post_request() ) {
				return $reply_id;
			}

			// Check action exists
			if ( empty( $_POST['action'] ) ) {
				return $reply_id;
			}

			// Nonce check
			if ( empty( $_POST['bbp_reply_metabox'] ) || ! wp_verify_nonce( $_POST['bbp_reply_metabox'], 'bbp_reply_metabox_save' ) ) {
				return $reply_id;
			}

			// Current user cannot edit this reply
			if ( ! current_user_can( 'edit_reply', $reply_id ) ) {
				return $reply_id;
			}

			// Get the reply meta post values
			$topic_id = ! empty( $_POST['parent_id'] ) ? (int) $_POST['parent_id'] : 0;
			$forum_id = ! empty( $_POST['bbp_forum_id'] ) ? (int) $_POST['bbp_forum_id'] : bbp_get_topic_forum_id( $topic_id );
			$reply_to = ! empty( $_POST['bbp_reply_to'] ) ? (int) $_POST['bbp_reply_to'] : 0;

			// Get reply author data
			$anonymous_data = bbp_filter_anonymous_post_data();
			$author_id      = bbp_get_reply_author_id( $reply_id );
			$is_edit        = (bool) isset( $_POST['save'] );

			// Formally update the reply
			bbp_update_reply( $reply_id, $topic_id, $forum_id, $anonymous_data, $author_id, $is_edit, $reply_to );

			// Allow other fun things to happen
			do_action( 'bbp_reply_attributes_metabox_save', $reply_id, $topic_id, $forum_id, $reply_to );
			do_action( 'bbp_author_metabox_save', $reply_id, $anonymous_data );

			// Send notifications for new replies created in admin.
			// Only send if it's a new reply (not an edit), reply is published, and topic is set.
			if ( ! $is_edit && ! empty( $topic_id ) && function_exists( 'bbp_is_reply_published' ) && bbp_is_reply_published( $reply_id ) ) {
				// Check if notification was already sent (prevent duplicates).
				$notification_sent = get_post_meta( $reply_id, '_bbp_admin_notification_sent', true );
				if ( empty( $notification_sent ) ) {
					// Additional check: verify this is a new reply by checking if post was created recently.
					$post_date     = get_post_field( 'post_date', $reply_id );
					$post_modified = get_post_field( 'post_modified', $reply_id );
					// If post_date and post_modified are the same (or very close), it's likely a new post.
					$time_diff = abs( strtotime( $post_modified ) - strtotime( $post_date ) );
					// Consider it new if created within the last 2 minutes (allows for processing time).
					if ( $time_diff < 120 ) {
						// Send notification to topic subscribers.
						if ( function_exists( 'bbp_notify_topic_subscribers' ) ) {
							bbp_notify_topic_subscribers( $reply_id, $topic_id, $forum_id, $anonymous_data, $author_id );
							// Mark as sent to prevent duplicates.
							update_post_meta( $reply_id, '_bbp_admin_notification_sent', true );
						}
					}
				}
			}

			return $reply_id;
		}

		/**
		 * Add the author info metabox
		 *
		 * Allows editing of information about an author
		 *
		 * @since bbPress (r2828)
		 *
		 * @uses bbp_get_topic() To get the topic
		 * @uses bbp_get_reply() To get the reply
		 * @uses bbp_get_topic_post_type() To get the topic post type
		 * @uses bbp_get_reply_post_type() To get the reply post type
		 * @uses add_meta_box() To add the metabox
		 * @uses do_action() Calls 'bbp_author_metabox' with the topic/reply
		 *                    id
		 */
		public function author_metabox() {

			if ( $this->bail() ) {
				return;
			}

			// Bail if post_type is not a reply
			if ( empty( $_GET['action'] ) || ( 'edit' !== $_GET['action'] ) ) {
				return;
			}

			// Add the metabox
			add_meta_box(
				'bbp_author_metabox',
				__( 'Author Information', 'buddyboss' ),
				'bbp_author_metabox',
				$this->post_type,
				'side',
				'high'
			);

			do_action( 'bbp_author_metabox', get_the_ID() );
		}

		/**
		 * Add some general styling to the admin area
		 *
		 * @since bbPress (r2464)
		 *
		 * @uses bbp_get_forum_post_type() To get the forum post type
		 * @uses bbp_get_topic_post_type() To get the topic post type
		 * @uses bbp_get_reply_post_type() To get the reply post type
		 * @uses sanitize_html_class() To sanitize the classes
		 * @uses do_action() Calls 'bbp_admin_head'
		 */
		public function admin_head() {

			if ( $this->bail() ) {
				return;
			}

			?>

		<style media="screen">
		/*<![CDATA[*/

			strong.label {
				display: inline-block;
				width: 74px;
			}

			body.post-type-reply #titlediv #titlewrap {
				display: none;
			}

			body.post-type-reply #titlediv {
				box-shadow: none;
			}

		/*]]>*/
		</style>

			<?php
		}

		/**
		 * Custom user feedback messages for reply post type
		 *
		 * @since bbPress (r3080)
		 *
		 * @global int $post_ID
		 * @uses bbp_get_topic_permalink()
		 * @uses wp_post_revision_title()
		 * @uses esc_url()
		 * @uses add_query_arg()
		 *
		 * @param array $messages
		 *
		 * @return array
		 */
		public function updated_messages( $messages ) {
			global $post_ID;

			if ( $this->bail() ) {
				return $messages;
			}

			// URL for the current topic
			$topic_url = bbp_get_topic_permalink( bbp_get_reply_topic_id( $post_ID ) );

			// Current reply's post_date
			$post_date = bbp_get_global_post_field( 'post_date', 'raw' );

			// Messages array
			$messages[ $this->post_type ] = array(
				0  => '', // Left empty on purpose

			// Updated
				1  => sprintf( __( 'Reply updated. <a href="%s">View topic</a>', 'buddyboss' ), $topic_url ),

				// Custom field updated
				2  => __( 'Custom field updated.', 'buddyboss' ),

				// Custom field deleted
				3  => __( 'Custom field deleted.', 'buddyboss' ),

				// Reply updated
				4  => __( 'Reply updated.', 'buddyboss' ),

				// Restored from revision
				// translators: %s: date and time of the revision
				5  => isset( $_GET['revision'] )
						? sprintf( __( 'Reply restored to revision from %s', 'buddyboss' ), wp_post_revision_title( (int) $_GET['revision'], false ) )
						: false,

				// Reply created
				6  => sprintf( __( 'Reply created. <a href="%s">View topic</a>', 'buddyboss' ), $topic_url ),

				// Reply saved
				7  => __( 'Reply saved.', 'buddyboss' ),

				// Reply submitted
				8  => sprintf( __( 'Reply submitted. <a target="_blank" href="%s">Preview topic</a>', 'buddyboss' ), esc_url( add_query_arg( 'preview', 'true', $topic_url ) ) ),

				// Reply scheduled
				9  => sprintf(
					__( 'Reply scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview topic</a>', 'buddyboss' ),
					// translators: Publish box date format, see http://php.net/date
						date_i18n(
							__( 'M j, Y @ G:i', 'buddyboss' ),
							strtotime( $post_date )
						),
					$topic_url
				),

				// Reply draft updated
				10 => sprintf( __( 'Reply draft updated. <a target="_blank" href="%s">Preview topic</a>', 'buddyboss' ), esc_url( add_query_arg( 'preview', 'true', $topic_url ) ) ),
			);

			return $messages;
		}
	}
endif; // class_exists check

/**
 * Setup Forums Replies Admin
 *
 * This is currently here to make hooking and unhooking of the admin UI easy.
 * It could use dependency injection in the future, but for now this is easier.
 *
 * @since bbPress (r2596)
 *
 * @uses BBP_Replies_Admin
 */
function bbp_admin_replies() {
	bbpress()->admin->replies = new BBP_Replies_Admin();
}
