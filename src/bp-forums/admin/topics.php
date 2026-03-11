<?php

/**
 * Forums Topics Admin Class
 *
 * @package BuddyBoss\Administration
 * @since bbPress (r2464)
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'BBP_Topics_Admin' ) ) :
	/**
	 * Loads Forums topics admin area
	 *
	 * @since bbPress (r2464)
	 */
	class BBP_Topics_Admin {

		/** Variables *************************************************************/

		/**
		 * @var The post type of this admin component
		 */
		private $post_type = '';

		/** Functions *************************************************************/

		/**
		 * The main Forums topics admin loader
		 *
		 * @since bbPress (r2515)
		 *
		 * @uses BBP_Topics_Admin::setup_globals() Setup the globals needed
		 * @uses BBP_Topics_Admin::setup_actions() Setup the hooks and actions
		 * @uses BBP_Topics_Admin::setup_help() Setup the help text
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

			// Topic metabox actions
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
			$this->post_type = bbp_get_topic_post_type();
		}

		/** Contextual Help *******************************************************/

		/**
		 * Contextual help for Forums topic edit page
		 *
		 * @since bbPress (r3119)
		 * @uses get_current_screen()
		 */
		public function new_help() {

			if ( $this->bail() ) {
				return;
			}

			$customize_display = '<p>' . __( 'The title field and the big discussion editing Area are fixed in place, but you can reposition all the other boxes using drag and drop, and can minimize or expand them by clicking the title bar of each box. Use the Screen Options tab to unhide more boxes (Excerpt, Send Trackbacks, Custom Fields, Discussion, Slug, Author) or to choose a 1- or 2-column layout for this screen.', 'buddyboss' ) . '</p>';

			get_current_screen()->add_help_tab(
				array(
					'id'      => 'customize-display',
					'title'   => __( 'Customizing This Display', 'buddyboss' ),
					'content' => $customize_display,
				)
			);

			get_current_screen()->add_help_tab(
				array(
					'id'      => 'title-topic-editor',
					'title'   => __( 'Title and Discussion Editor', 'buddyboss' ),
					'content' =>
						  '<p>' . __( '<strong>Title</strong> - Enter a title for your discussion. After you enter a title, you\'ll see the permalink below, which you can edit.', 'buddyboss' ) . '</p>' .
						  '<p>' . __( '<strong>Discussion Editor</strong> - Enter the text for your discussion. There are two modes of editing: Visual and HTML. Choose the mode by clicking on the appropriate tab. Visual mode gives you a WYSIWYG editor. Click the last icon in the row to get a second row of controls. The HTML mode allows you to enter raw HTML along with your discussion text. You can insert media files by clicking the icons above the discussion editor and following the directions. You can go to the distraction-free writing screen via the Fullscreen icon in Visual mode (second to last in the top row) or the Fullscreen button in HTML mode (last in the row). Once there, you can make buttons visible by hovering over the top area. Exit Fullscreen back to the regular discussion editor.', 'buddyboss' ) . '</p>',
				)
			);

			$publish_box = '<p>' . __( '<strong>Publish</strong> - You can set the terms of publishing your discussion in the Publish box. For Status, Visibility, and Publish (immediately), click on the Edit link to reveal more options. Visibility includes options for password-protecting a discussion or making it stay at the top of your blog indefinitely (sticky). Publish (immediately) allows you to set a future or past date and time, so you can schedule a discussion to be published in the future or backdate a discussion.', 'buddyboss' ) . '</p>';

			if ( current_theme_supports( 'topic-thumbnails' ) && post_type_supports( 'topic', 'thumbnail' ) ) {
				$publish_box .= '<p>' . __( '<strong>Featured Image</strong> - This allows you to associate an image with your discussion without inserting it. This is usually useful only if your theme makes use of the featured image as a discussion thumbnail on the home page, a custom header, etc.', 'buddyboss' ) . '</p>';
			}

			get_current_screen()->add_help_tab(
				array(
					'id'      => 'topic-attributes',
					'title'   => __( 'Discussion Attributes', 'buddyboss' ),
					'content' =>
						  '<p>' . __( 'Select the attributes that your discussion should have:', 'buddyboss' ) . '</p>' .
						  '<ul>' .
							  '<li>' . __( '<strong>Forum</strong> dropdown determines the parent forum that the discussion belongs to. Select the forum or category from the dropdown, or leave the default (No Forum) to post the discussion without an assigned forum.', 'buddyboss' ) . '</li>' .
							  '<li>' . __( '<strong>Discussion Type</strong> dropdown indicates the sticky status of the discussion. Selecting the super sticky option would stick the discussion to the front of your forums, i.e. the discussion index, sticky option would stick the discussion to its respective forum. Selecting normal would not stick the discussion anywhere.', 'buddyboss' ) . '</li>' .
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

			get_current_screen()->set_help_sidebar(
				'<p><strong>' . __( 'For more information:', 'buddyboss' ) . '</strong></p>' .
				'<p>' . __( '<a href="https://www.buddyboss.com/resources/">Documentation</a>', 'buddyboss' ) . '</p>'
			);
		}

		/**
		 * Add the topic attributes metabox
		 *
		 * @since bbPress (r2744)
		 *
		 * @uses bbp_get_topic_post_type() To get the topic post type
		 * @uses add_meta_box() To add the metabox
		 * @uses do_action() Calls 'bbp_topic_attributes_metabox'
		 */
		public function attributes_metabox() {

			if ( $this->bail() ) {
				return;
			}

			add_meta_box(
				'bbp_topic_attributes',
				__( 'Discussion Attributes', 'buddyboss' ),
				'bbp_topic_metabox',
				$this->post_type,
				'side',
				'high'
			);

			do_action( 'bbp_topic_attributes_metabox' );
		}

		/**
		 * Pass the topic attributes for processing
		 *
		 * @since bbPress (r2746)
		 *
		 * @param int $topic_id Topic id
		 * @uses current_user_can() To check if the current user is capable of
		 *                           editing the topic
		 * @uses do_action() Calls 'bbp_topic_attributes_metabox_save' with the
		 *                    topic id and parent id
		 * @return int Parent id
		 */
		public function attributes_metabox_save( $topic_id ) {

			if ( $this->bail() ) {
				return $topic_id;
			}

			// Bail if doing an autosave
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return $topic_id;
			}

			// Bail if not a post request
			if ( ! bbp_is_post_request() ) {
				return $topic_id;
			}

			// Nonce check
			if ( empty( $_POST['bbp_topic_metabox'] ) || ! wp_verify_nonce( $_POST['bbp_topic_metabox'], 'bbp_topic_metabox_save' ) ) {
				return $topic_id;
			}

			// Bail if current user cannot edit this topic
			if ( ! current_user_can( 'edit_topic', $topic_id ) ) {
				return $topic_id;
			}

			// Get the forum ID
			$forum_id = ! empty( $_POST['parent_id'] ) ? (int) $_POST['parent_id'] : 0;

			// Get topic author data
			$anonymous_data = bbp_filter_anonymous_post_data();
			$author_id      = bbp_get_topic_author_id( $topic_id );
			$is_edit        = (bool) isset( $_POST['save'] );

			// Formally update the topic
			bbp_update_topic( $topic_id, $forum_id, $anonymous_data, $author_id, $is_edit );

			$old_forum_id = ! empty( $_POST['old_parent_id'] ) ? $_POST['old_parent_id'] : 0;
			if (
				! empty( $old_forum_id ) &&
				$forum_id !== $old_forum_id
			) {

				// Get forum stickies.
				$old_stickies = bbp_get_stickies( $old_forum_id );

				// Only proceed if stickies are found.
				if ( ! empty( $old_stickies ) ) {

					// Define local variables.
					$updated_stickies = array();

					// Loop through stickies of forum and add misses to the updated array.
					foreach ( (array) $old_stickies as $sticky_topic_id ) {
						if ( $topic_id !== $sticky_topic_id ) {
							$updated_stickies[] = $sticky_topic_id;
						}
					}

					// If stickies are different, update or delete them.
					if ( $updated_stickies !== $old_stickies ) {

						// No more stickies so delete the meta.
						if ( empty( $updated_stickies ) ) {
							delete_post_meta( $old_forum_id, '_bbp_sticky_topics' );

							// Still stickies so update the meta.
						} else {
							update_post_meta( $old_forum_id, '_bbp_sticky_topics', $updated_stickies );
						}
					}
				}
			}

			// Stickies
			if ( ! empty( $_POST['bbp_stick_topic'] ) && in_array( $_POST['bbp_stick_topic'], array( 'stick', 'super', 'unstick' ) ) ) {

				// What's the haps?
				switch ( $_POST['bbp_stick_topic'] ) {

					// Sticky in this forum
					case 'stick':
						bbp_stick_topic( $topic_id );
						break;

					// Super sticky in all forums
					case 'super':
						if ( bb_is_group_forum_topic( $topic_id ) ) {
							bbp_stick_topic( $topic_id );
						} else {
							bbp_stick_topic( $topic_id, true );
						}
						break;

					// Normal
					case 'unstick':
					default:
						bbp_unstick_topic( $topic_id );
						break;
				}
			}

			// Allow other fun things to happen
			do_action( 'bbp_topic_attributes_metabox_save', $topic_id, $forum_id );
			do_action( 'bbp_author_metabox_save', $topic_id, $anonymous_data );

			// Send notifications for new topics created in admin.
			// Only send if it's a new topic (not an edit), topic is published, and forum is set.
			if ( ! $is_edit && ! empty( $forum_id ) && function_exists( 'bbp_is_topic_published' ) && bbp_is_topic_published( $topic_id ) ) {
				// Check if notification was already sent (prevent duplicates).
				$notification_sent = get_post_meta( $topic_id, '_bbp_admin_notification_sent', true );
				if ( empty( $notification_sent ) ) {
					// Additional check: verify this is a new topic by checking if post was created recently.
					$post_date     = get_post_field( 'post_date', $topic_id );
					$post_modified = get_post_field( 'post_modified', $topic_id );
					// If post_date and post_modified are the same (or very close), it's likely a new post.
					$time_diff = abs( strtotime( $post_modified ) - strtotime( $post_date ) );
					// Consider it new if created within the last 2 minutes (allows for processing time).
					if ( $time_diff < 120 ) {
						// Send notification to forum subscribers.
						if ( function_exists( 'bbp_notify_forum_subscribers' ) ) {
							bbp_notify_forum_subscribers( $topic_id, $forum_id, $anonymous_data, $author_id );
							// Mark as sent to prevent duplicates.
							update_post_meta( $topic_id, '_bbp_admin_notification_sent', true );
						}
					}
				}
			}

			return $topic_id;
		}

		/**
		 * Add the author info metabox
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

			// Bail if post_type is not a topic
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
				width: 60px;
			}

		/*]]>*/
		</style>

			<?php
		}

		/**
		 * Custom user feedback messages for topic post type
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
			$topic_url = bbp_get_topic_permalink( $post_ID );

			// Current topic's post_date
			$post_date = bbp_get_global_post_field( 'post_date', 'raw' );

			// Messages array
			$messages[ $this->post_type ] = array(
				0  => '', // Left empty on purpose

			// Updated
				1  => sprintf( __( 'Discussion updated. <a href="%s">View discussion</a>', 'buddyboss' ), $topic_url ),

				// Custom field updated
				2  => __( 'Custom field updated.', 'buddyboss' ),

				// Custom field deleted
				3  => __( 'Custom field deleted.', 'buddyboss' ),

				// Discussion updated
				4  => __( 'Discussion updated.', 'buddyboss' ),

				// Restored from revision
				// translators: %s: date and time of the revision
				5  => isset( $_GET['revision'] )
						? sprintf( __( 'Discussion restored to revision from %s', 'buddyboss' ), wp_post_revision_title( (int) $_GET['revision'], false ) )
						: false,

				// Discussion created
				6  => sprintf( __( 'Discussion created. <a href="%s">View discussion</a>', 'buddyboss' ), $topic_url ),

				// Discussion saved
				7  => __( 'Discussion saved.', 'buddyboss' ),

				// Discussion submitted
				8  => sprintf( __( 'Discussion submitted. <a target="_blank" href="%s">Preview discussion</a>', 'buddyboss' ), esc_url( add_query_arg( 'preview', 'true', $topic_url ) ) ),

				// Discussion scheduled
				9  => sprintf(
					__( 'Discussion scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview discussion</a>', 'buddyboss' ),
					// translators: Publish box date format, see http://php.net/date
						date_i18n(
							__( 'M j, Y @ G:i', 'buddyboss' ),
							strtotime( $post_date )
						),
					$topic_url
				),

				// Discussion draft updated
				10 => sprintf( __( 'Discussion draft updated. <a target="_blank" href="%s">Preview discussion</a>', 'buddyboss' ), esc_url( add_query_arg( 'preview', 'true', $topic_url ) ) ),
			);

			return $messages;
		}
	}
endif; // class_exists check

/**
 * Setup Forums Topics Admin
 *
 * This is currently here to make hooking and unhooking of the admin UI easy.
 * It could use dependency injection in the future, but for now this is easier.
 *
 * @since bbPress (r2596)
 *
 * @uses BBP_Forums_Admin
 */
function bbp_admin_topics() {
	bbpress()->admin->topics = new BBP_Topics_Admin();
}
