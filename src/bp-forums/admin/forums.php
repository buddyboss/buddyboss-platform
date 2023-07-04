<?php

/**
 * Forums Forum Admin Class
 *
 * @package BuddyBoss\Administration
 * @since bbPress (r2464)
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'BBP_Forums_Admin' ) ) :
	/**
	 * Loads forums admin area
	 *
	 * @since bbPress (r2464)
	 */
	class BBP_Forums_Admin {

		/** Variables *************************************************************/

		/**
		 * @var The post type of this admin component
		 */
		private $post_type = '';

		/** Functions *************************************************************/

		/**
		 * The main forums admin loader
		 *
		 * @since bbPress (r2515)
		 *
		 * @uses BBP_Forums_Admin::setup_globals() Setup the globals needed
		 * @uses BBP_Forums_Admin::setup_actions() Setup the hooks and actions
		 * @uses BBP_Forums_Admin::setup_help() Setup the help text
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

			// Metabox actions
			add_action( 'add_meta_boxes', array( $this, 'attributes_metabox' ) );
			add_action( 'add_meta_boxes', array( $this, 'comments_metabox'      ) );
			add_action( 'save_post', array( $this, 'attributes_metabox_save' ) );

			// Column headers.
			add_filter( 'manage_' . $this->post_type . '_posts_columns', array( $this, 'column_headers' ) );

			// Columns (in page row)
			add_action( 'manage_' . $this->post_type . '_posts_custom_column', array( $this, 'column_data' ), 10, 2 );
			add_filter( 'page_row_actions', array( $this, 'row_actions' ), 10, 2 );

			// Contextual Help
			add_action( 'load-edit.php', array( $this, 'edit_help' ) );
			add_action( 'load-post.php', array( $this, 'new_help' ) );
			add_action( 'load-post-new.php', array( $this, 'new_help' ) );

			// Set forum states
			add_filter( 'display_post_states', array( $this, 'bbp_set_hidden_forum_states' ), 10, 2 );

			// Filter post parent for forum type post.
			add_filter( 'wp_insert_post_parent', array( $this, 'forum_parent' ), 10, 3 );
		}

		/**
		 * Should we bail out of this method?
		 *
		 * @since bbPress (r4067)
		 * @return boolean
		 */
		private function bail() {
			if ( ! isset( get_current_screen()->post_type ) || ( $this->post_type != get_current_screen()->post_type ) ) {
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
			$this->post_type = bbp_get_forum_post_type();
		}

		/** Contextual Help *******************************************************/

		/**
		 * Set the forum states if forum is hidden.
		 *
		 * @param $states
		 * @param $post
		 *
		 * @return array
		 * @since BuddyBoss 1.0.0
		 */
		public function bbp_set_hidden_forum_states( $states, $post ) {
			global $post;

			if ( is_array( $post ) && bbp_get_forum_post_type() === get_post_type( $post->ID ) && bbp_get_hidden_status_id() === bbp_get_forum_visibility( $post->ID ) ) {

				$states[] = __( 'Hidden', 'buddyboss' );

			}

			return $states;
		}

		/**
		 * Contextual help for Forums forum edit page
		 *
		 * @since bbPress (r3119)
		 * @uses get_current_screen()
		 */
		public function edit_help() {

			if ( $this->bail() ) {
				return;
			}

			// Overview
			get_current_screen()->add_help_tab(
				array(
					'id'      => 'overview',
					'title'   => __( 'Overview', 'buddyboss' ),
					'content' =>
						  '<p>' . __( 'This screen displays the individual forums on your site. You can customize the display of this screen to suit your workflow.', 'buddyboss' ) . '</p>',
				)
			);

			// Screen Content
			get_current_screen()->add_help_tab(
				array(
					'id'      => 'screen-content',
					'title'   => __( 'Screen Content', 'buddyboss' ),
					'content' =>
						  '<p>' . __( 'You can customize the display of this screen\'s contents in a number of ways:', 'buddyboss' ) . '</p>' .
						  '<ul>' .
							  '<li>' . __( 'You can hide/display columns based on your needs and decide how many forums to list per screen using the Screen Options tab.', 'buddyboss' ) . '</li>' .
							  '<li>' . __( 'You can filter the list of forums by forum status using the text links in the upper left to show All, Published, or Trashed forums. The default view is to show all forums.', 'buddyboss' ) . '</li>' .
							  '<li>' . __( 'You can refine the list to show only forums from a specific month by using the dropdown menus above the forums list. Click the Filter button after making your selection. You also can refine the list by clicking on the forum creator in the forums list.', 'buddyboss' ) . '</li>' .
						  '</ul>',
				)
			);

			// Available Actions
			get_current_screen()->add_help_tab(
				array(
					'id'      => 'action-links',
					'title'   => __( 'Available Actions', 'buddyboss' ),
					'content' =>
						  '<p>' . __( 'Hovering over a row in the forums list will display action links that allow you to manage your forum. You can perform the following actions:', 'buddyboss' ) . '</p>' .
						  '<ul>' .
							  '<li>' . __( '<strong>Edit</strong> takes you to the editing screen for that forum. You can also reach that screen by clicking on the forum title.', 'buddyboss' ) . '</li>' .
							  '<li>' . __( '<strong>Trash</strong> removes your forum from this list and places it in the trash, from which you can permanently delete it.', 'buddyboss' ) . '</li>' .
							  '<li>' . __( '<strong>View</strong> will show you what your draft forum will look like if you publish it. View will take you to your live site to view the forum. Which link is available depends on your forum\'s status.', 'buddyboss' ) . '</li>' .
						  '</ul>',
				)
			);

			// Bulk Actions
			get_current_screen()->add_help_tab(
				array(
					'id'      => 'bulk-actions',
					'title'   => __( 'Bulk Actions', 'buddyboss' ),
					'content' =>
						  '<p>' . __( 'You can also edit or move multiple forums to the trash at once. Select the forums you want to act on using the checkboxes, then select the action you want to take from the Bulk Actions menu and click Apply.', 'buddyboss' ) . '</p>' .
						  '<p>' . __( 'When using Bulk Edit, you can change the status (Published, Private, etc.) for all selected forums at once. To remove a forum from the grouping, just click the x next to its name in the Bulk Edit area that appears.', 'buddyboss' ) . '</p>',
				)
			);

			// Help Sidebar
			get_current_screen()->set_help_sidebar(
				'<p><strong>' . __( 'For more information:', 'buddyboss' ) . '</strong></p>' .
				'<p>' . __( '<a href="https://www.buddyboss.com/resources/">Documentation</a>', 'buddyboss' ) . '</p>'
			);
		}

		/**
		 * Contextual help for Forums forum edit page
		 *
		 * @since bbPress (r3119)
		 * @uses get_current_screen()
		 */
		public function new_help() {

			if ( $this->bail() ) {
				return;
			}

			$customize_display = '<p>' . __( 'The title field and the big forum editing Area are fixed in place, but you can reposition all the other boxes using drag and drop, and can minimize or expand them by clicking the title bar of each box. Use the Screen Options tab to unhide more boxes (Excerpt, Send Trackbacks, Custom Fields, Discussion, Slug, Author) or to choose a 1- or 2-column layout for this screen.', 'buddyboss' ) . '</p>';

			get_current_screen()->add_help_tab(
				array(
					'id'      => 'customize-display',
					'title'   => __( 'Customizing This Display', 'buddyboss' ),
					'content' => $customize_display,
				)
			);

			get_current_screen()->add_help_tab(
				array(
					'id'      => 'title-forum-editor',
					'title'   => __( 'Title and Forum Editor', 'buddyboss' ),
					'content' =>
						  '<p>' . __( '<strong>Title</strong> - Enter a title for your forum. After you enter a title, you\'ll see the permalink below, which you can edit.', 'buddyboss' ) . '</p>' .
						  '<p>' . __( '<strong>Forum Editor</strong> - Enter the text for your forum. There are two modes of editing: Visual and HTML. Choose the mode by clicking on the appropriate tab. Visual mode gives you a WYSIWYG editor. Click the last icon in the row to get a second row of controls. The HTML mode allows you to enter raw HTML along with your forum text. You can insert media files by clicking the icons above the forum editor and following the directions. You can go to the distraction-free writing screen via the Fullscreen icon in Visual mode (second to last in the top row) or the Fullscreen button in HTML mode (last in the row). Once there, you can make buttons visible by hovering over the top area. Exit Fullscreen back to the regular forum editor.', 'buddyboss' ) . '</p>',
				)
			);

			$publish_box = '<p>' . __( '<strong>Publish</strong> - You can set the terms of publishing your forum in the Publish box. For Status, Visibility, and Publish (immediately), click on the Edit link to reveal more options. Visibility includes options for password-protecting a forum or making it stay at the top of your blog indefinitely (sticky). Publish (immediately) allows you to set a future or past date and time, so you can schedule a forum to be published in the future or backdate a forum.', 'buddyboss' ) . '</p>';

			if ( current_theme_supports( 'forum-thumbnails' ) && post_type_supports( 'forum', 'thumbnail' ) ) {
				$publish_box .= '<p>' . __( '<strong>Featured Image</strong> - This allows you to associate an image with your forum without inserting it. This is usually useful only if your theme makes use of the featured image as a forum thumbnail on the home page, a custom header, etc.', 'buddyboss' ) . '</p>';
			}

			get_current_screen()->add_help_tab(
				array(
					'id'      => 'forum-attributes',
					'title'   => __( 'Forum Attributes', 'buddyboss' ),
					'content' =>
						  '<p>' . __( 'Select the attributes that your forum should have:', 'buddyboss' ) . '</p>' .
						  '<ul>' .
							  '<li>' . __( '<strong>Type</strong> indicates if the forum is a category or forum. Categories generally contain other forums.', 'buddyboss' ) . '</li>' .
							  '<li>' . __( '<strong>Status</strong> allows you to close a forum to new discussions and replies.', 'buddyboss' ) . '</li>' .
							  '<li>' . __( '<strong>Visibility</strong> lets you pick the scope of each forum and what users are allowed to access it.', 'buddyboss' ) . '</li>' .
							  '<li>' . __( '<strong>Parent</strong> dropdown determines the parent forum. Select the forum or category from the dropdown, or leave the default (No Parent) to create the forum at the root of your forums.', 'buddyboss' ) . '</li>' .
							  '<li>' . __( '<strong>Order</strong> allows you to order your forums numerically.', 'buddyboss' ) . '</li>' .
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
		 * Add the forum attributes metabox
		 *
		 * @since bbPress (r2746)
		 *
		 * @uses bbp_get_forum_post_type() To get the forum post type
		 * @uses add_meta_box() To add the metabox
		 * @uses do_action() Calls 'bbp_forum_attributes_metabox'
		 */
		public function attributes_metabox() {

			if ( $this->bail() ) {
				return;
			}

			add_meta_box(
				'bbp_forum_attributes',
				__( 'Forum Attributes', 'buddyboss' ),
				'bbp_forum_metabox',
				$this->post_type,
				'side',
				'high'
			);

			do_action( 'bbp_forum_attributes_metabox' );
		}

		/**
		 * Remove comments & discussion meta-boxes if comments are not supported
		 *
		 * @since 2.6.0 bbPress (r6186)
		 */
		public function comments_metabox() {
			if ( ! post_type_supports( $this->post_type, 'comments' ) ) {
				remove_meta_box( 'commentstatusdiv', $this->post_type, 'normal' );
				remove_meta_box( 'commentsdiv',      $this->post_type, 'normal' );
			}
		}

		/**
		 * Pass the forum attributes for processing
		 *
		 * @since bbPress (r2746)
		 *
		 * @param int $forum_id Forum id
		 * @uses current_user_can() To check if the current user is capable of
		 *                           editing the forum
		 * @uses bbp_get_forum() To get the forum
		 * @uses bbp_is_forum_closed() To check if the forum is closed
		 * @uses bbp_is_forum_category() To check if the forum is a category
		 * @uses bbp_is_forum_private() To check if the forum is private
		 * @uses bbp_close_forum() To close the forum
		 * @uses bbp_open_forum() To open the forum
		 * @uses bbp_categorize_forum() To make the forum a category
		 * @uses bbp_normalize_forum() To make the forum normal (not category)
		 * @uses bbp_privatize_forum() To mark the forum as private
		 * @uses bbp_publicize_forum() To mark the forum as public
		 * @uses do_action() Calls 'bbp_forum_attributes_metabox_save' with the
		 *                    forum id
		 * @return int Forum id
		 */
		public function attributes_metabox_save( $forum_id ) {

			if ( $this->bail() ) {
				return $forum_id;
			}

			// Bail if doing an autosave
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return $forum_id;
			}

			// Bail if not a post request
			if ( ! bbp_is_post_request() ) {
				return $forum_id;
			}

			// Nonce check
			if ( empty( $_POST['bbp_forum_metabox'] ) || ! wp_verify_nonce( $_POST['bbp_forum_metabox'], 'bbp_forum_metabox_save' ) ) {
				return $forum_id;
			}

			// Only save for forum post-types
			if ( ! bbp_is_forum( $forum_id ) ) {
				return $forum_id;
			}

			// Bail if current user cannot edit this forum
			if ( ! current_user_can( 'edit_forum', $forum_id ) ) {
				return $forum_id;
			}

			// Parent ID
			$parent_id = ( ! empty( $_POST['parent_id'] ) && is_numeric( $_POST['parent_id'] ) ) ? (int) $_POST['parent_id'] : 0;

			// Update the forum meta bidness
			bbp_update_forum(
				array(
					'forum_id'    => $forum_id,
					'post_parent' => (int) $parent_id,
				)
			);

			do_action( 'bbp_forum_attributes_metabox_save', $forum_id );

			return $forum_id;
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

			global $post, $pagenow;
			if ( ( isset( $post->post_type ) && $post->post_type === bbp_get_forum_post_type() && $pagenow == 'edit.php' ) || ( isset( $post->post_type ) && $post->post_type == bbp_get_forum_post_type() && $pagenow == 'post-new.php' ) || ( isset( $post->post_type ) && $post->post_type == bbp_get_forum_post_type() && $pagenow == 'post.php' ) ) {
				?>
			<style media="screen">
				/*<![CDATA[*/

				#misc-publishing-actions,
				#save-post {
					display: block !important;
				}

				/*]]>*/
			</style>
				<?php
			}
			?>

		<style media="screen">
		/*<![CDATA[*/

			#misc-publishing-actions,
			#save-post {
				display: none;
			}

			strong.label {
				display: inline-block;
				width: 60px;
			}

			#bbp_forum_attributes hr {
				border-style: solid;
				border-width: 1px;
				border-color: #ccc #fff #fff #ccc;
			}

			.column-bbp_forum_topic_count,
			.column-bbp_forum_reply_count,
			.column-bbp_topic_reply_count,
			.column-bbp_topic_voice_count {
				width: 10% !important;
			}

			.column-author,
			.column-bbp_reply_author,
			.column-bbp_topic_author {
				width: 10% !important;
			}

			.column-bbp_topic_forum,
			.column-bbp_reply_forum,
			.column-bbp_reply_topic {
				width: 10% !important;
			}

			.column-bbp_forum_freshness,
			.column-bbp_topic_freshness {
				width: 10% !important;
			}

			.column-bbp_forum_created,
			.column-bbp_topic_created,
			.column-bbp_reply_created {
				width: 15% !important;
			}

			.status-closed {
				background-color: #eaeaea;
			}

			.status-spam {
				background-color: #faeaea;
			}

		/*]]>*/
		</style>

			<?php
		}

		/**
		 * Manage the column headers for the forums page
		 *
		 * @since bbPress (r2485)
		 *
		 * @param array $columns The columns
		 * @uses apply_filters() Calls 'bbp_admin_forums_column_headers' with
		 *                        the columns
		 * @return array $columns Forums forum columns
		 */
		public function column_headers( $columns ) {

			if ( $this->bail() ) {
				return $columns;
			}

			$columns = array(
				'cb'                    => '<input type="checkbox" />',
				'title'                 => __( 'Forum', 'buddyboss' ),
				'bbp_forum_topic_count' => __( 'Discussions', 'buddyboss' ),
				'bbp_forum_reply_count' => __( 'Replies', 'buddyboss' ),
				'author'                => __( 'Creator', 'buddyboss' ),
				'bbp_forum_created'     => __( 'Created', 'buddyboss' ),
				'bbp_forum_freshness'   => __( 'Last Post', 'buddyboss' ),
			);

			return apply_filters( 'bbp_admin_forums_column_headers', $columns );
		}

		/**
		 * Print extra columns for the forums page
		 *
		 * @since bbPress (r2485)
		 *
		 * @param string $column Column
		 * @param int    $forum_id Forum id
		 * @uses bbp_forum_topic_count() To output the forum topic count
		 * @uses bbp_forum_reply_count() To output the forum reply count
		 * @uses get_the_date() Get the forum creation date
		 * @uses get_the_time() Get the forum creation time
		 * @uses esc_attr() To sanitize the forum creation time
		 * @uses bbp_get_forum_last_active_time() To get the time when the forum was
		 *                                    last active
		 * @uses do_action() Calls 'bbp_admin_forums_column_data' with the
		 *                    column and forum id
		 */
		public function column_data( $column, $forum_id ) {

			if ( $this->bail() ) {
				return;
			}

			switch ( $column ) {
				case 'bbp_forum_topic_count':
					bbp_forum_topic_count( $forum_id );
					break;

				case 'bbp_forum_reply_count':
					bbp_forum_reply_count( $forum_id );
					break;

				case 'bbp_forum_created':
					printf(
						'%1$s <br /> %2$s',
						get_the_date(),
						esc_attr( get_the_time() )
					);

					break;

				case 'bbp_forum_freshness':
					$last_active = bbp_get_forum_last_active_time( $forum_id, false );
					if ( ! empty( $last_active ) ) {
						echo esc_html( $last_active );
					} else {
						esc_html_e( 'No Discussions', 'buddyboss' );
					}

					break;

				default:
					do_action( 'bbp_admin_forums_column_data', $column, $forum_id );
					break;
			}
		}

		/**
		 * Forum Row actions
		 *
		 * Remove the quick-edit action link and display the description under
		 * the forum title
		 *
		 * @since bbPress (r2577)
		 *
		 * @param array $actions Actions
		 * @param object $forum Forum object
		 * @uses bbp_forum_content() To output forum description
		 * @return array $actions Actions
		 */
		public function row_actions( $actions, $forum ) {

			if ( $this->bail() ) {
				return $actions;
			}

			unset( $actions['inline hide-if-no-js'] );

			// Only show content if user can read it and there is no password.
			if ( current_user_can( 'read_forum', $forum->ID ) && ! post_password_required( $forum ) ) {

				// Get the forum description.
				$content = bbp_get_forum_content( $forum->ID );

				// Only proceed if there is a description.
				if ( ! empty( $content ) ) {
					echo '<div class="bbp-escaped-content">' . wp_strip_all_tags( wp_trim_excerpt( $content, $forum ) ) . '</div>';
				}
			}

			return $actions;
		}

		/**
		 * Custom user feedback messages for forum post type
		 *
		 * @since bbPress (r3080)
		 *
		 * @global int $post_ID
		 * @uses bbp_get_forum_permalink()
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

			// URL for the current forum
			$forum_url = bbp_get_forum_permalink( $post_ID );

			// Current forum's post_date
			$post_date = bbp_get_global_post_field( 'post_date', 'raw' );

			// Messages array
			$messages[ $this->post_type ] = array(
				0  => '', // Left empty on purpose

			// Updated
				1  => sprintf( __( 'Forum updated. <a href="%s">View forum</a>', 'buddyboss' ), $forum_url ),

				// Custom field updated
				2  => __( 'Custom field updated.', 'buddyboss' ),

				// Custom field deleted
				3  => __( 'Custom field deleted.', 'buddyboss' ),

				// Forum updated
				4  => __( 'Forum updated.', 'buddyboss' ),

				// Restored from revision
				// translators: %s: date and time of the revision
				5  => isset( $_GET['revision'] )
						? sprintf( __( 'Forum restored to revision from %s', 'buddyboss' ), wp_post_revision_title( (int) $_GET['revision'], false ) )
						: false,

				// Forum created
				6  => sprintf( __( 'Forum created. <a href="%s">View forum</a>', 'buddyboss' ), $forum_url ),

				// Forum saved
				7  => __( 'Forum saved.', 'buddyboss' ),

				// Forum submitted
				8  => sprintf( __( 'Forum submitted. <a target="_blank" href="%s">Preview forum</a>', 'buddyboss' ), esc_url( add_query_arg( 'preview', 'true', $forum_url ) ) ),

				// Forum scheduled
				9  => sprintf(
					__( 'Forum scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview forum</a>', 'buddyboss' ),
					// translators: Publish box date format, see http://php.net/date
						date_i18n(
							__( 'M j, Y @ G:i', 'buddyboss' ),
							strtotime( $post_date )
						),
					$forum_url
				),

				// Forum draft updated
				10 => sprintf( __( 'Forum draft updated. <a target="_blank" href="%s">Preview forum</a>', 'buddyboss' ), esc_url( add_query_arg( 'preview', 'true', $forum_url ) ) ),
			);

			return $messages;
		}

		/**
		 * Permission for forum parent, If not then return the current parent.
		 * You can not make the forum a child forum when it's associated with any group.
		 *
		 * @since BuddyBoss 1.7.8
		 *
		 * @param init  $post_parent post parent.
		 * @param init  $post_ID     post ID.
		 * @param array $new_postarr submited post data.
		 *
		 * @return init
		 */
		public function forum_parent( $post_parent, $post_ID, $new_postarr ) {
			if ( bbp_get_forum_post_type() !== $new_postarr['post_type'] ) {
				return $post_parent;
			}

			$group_ids = bbp_get_forum_group_ids( $post_ID );

			if ( empty( $group_ids ) ) {
				return $post_parent;
			}

			$forum = bbp_get_forum( $post_ID );
			return $forum->post_parent;
		}
	}
endif; // class_exists check

/**
 * Setup forums Admin
 *
 * This is currently here to make hooking and unhooking of the admin UI easy.
 * It could use dependency injection in the future, but for now this is easier.
 *
 * @since bbPress (r2596)
 *
 * @uses BBP_Forums_Admin
 */
function bbp_admin_forums() {
	bbpress()->admin->forums = new BBP_Forums_Admin();
}
