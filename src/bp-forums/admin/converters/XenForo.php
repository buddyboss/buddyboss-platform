<?php

/**
 *  Implementation of XenForo converter.
 *
 * @since bbPress (r5145)
 * @link Codex Docs http://codex.bbpress.org/import-forums/xenforo
 */
class XenForo extends BBP_Converter_Base {

	/**
	 * Main constructor
	 *
	 * @uses XenForo::setup_globals()
	 */
	function __construct() {
		parent::__construct();
		$this->setup_globals();
	}

	/**
	 * Sets up the field mappings
	 */
	public function setup_globals() {

		// Setup smiley URL & path.
		$this->bbcode_parser_properties = array(
			'smiley_url' => false,
			'smiley_dir' => false,
		);

		/** Forum Section */

		// Forum id (Stored in postmeta).
		$this->field_map[] = array(
			'from_tablename' => 'node',
			'from_fieldname' => 'node_id',
			'to_type'        => 'forum',
			'to_fieldname'   => '_bbp_old_forum_id',
		);

		// Forum parent id (If no parent, then 0. Stored in postmeta).
		$this->field_map[] = array(
			'from_tablename' => 'node',
			'from_fieldname' => 'parent_node_id',
			'to_type'        => 'forum',
			'to_fieldname'   => '_bbp_old_forum_parent_id',
		);

		// Forum topic count (Stored in postmeta)
		// Note: We join the 'forum' table because 'node' does not include topic counts.
		$this->field_map[] = array(
			'from_tablename'  => 'forum',
			'from_fieldname'  => 'discussion_count',
			'join_tablename'  => 'node',
			'join_type'       => 'LEFT',
			'join_expression' => 'USING (node_id) WHERE node.node_type_id = "Category" OR node.node_type_id = "Forum" ',
			'to_type'         => 'forum',
			'to_fieldname'    => '_bbp_topic_count',
		);

		// Forum reply count (Stored in postmeta)
		// Note: We join the 'forum' table because 'node' does not include reply counts.
		$this->field_map[] = array(
			'from_tablename'  => 'forum',
			'from_fieldname'  => 'message_count',
			'join_tablename'  => 'node',
			'join_type'       => 'LEFT',
			'join_expression' => 'USING (node_id) WHERE node.node_type_id = "Category" OR node.node_type_id = "Forum" ',
			'to_type'         => 'forum',
			'to_fieldname'    => '_bbp_reply_count',
		);

		// Forum total topic count (Includes unpublished topics, Stored in postmeta)
		// Note: We join the 'forum' table because 'node' does not include topic counts.
		$this->field_map[] = array(
			'from_tablename'  => 'forum',
			'from_fieldname'  => 'discussion_count',
			'join_tablename'  => 'node',
			'join_type'       => 'LEFT',
			'join_expression' => 'USING (node_id) WHERE node.node_type_id = "Category" OR node.node_type_id = "Forum" ',
			'to_type'         => 'forum',
			'to_fieldname'    => '_bbp_total_topic_count',
		);

		// Forum total reply count (Includes unpublished replies, Stored in postmeta)
		// Note: We join the 'forum' table because 'node' does not include reply counts.
		$this->field_map[] = array(
			'from_tablename'  => 'forum',
			'from_fieldname'  => 'message_count',
			'join_tablename'  => 'node',
			'join_type'       => 'LEFT',
			'join_expression' => 'USING (node_id) WHERE node.node_type_id = "Category" OR node.node_type_id = "Forum" ',
			'to_type'         => 'forum',
			'to_fieldname'    => '_bbp_total_reply_count',
		);

		// Forum title.
		$this->field_map[] = array(
			'from_tablename' => 'node',
			'from_fieldname' => 'title',
			'to_type'        => 'forum',
			'to_fieldname'   => 'post_title',
		);

		// Forum slug (Clean name to avoid confilcts)
		// 'node_name' only has slug for explictly named forums.
		$this->field_map[] = array(
			'from_tablename'  => 'node',
			'from_fieldname'  => 'node_name',
			'to_type'         => 'forum',
			'to_fieldname'    => 'post_name',
			'callback_method' => 'callback_slug',
		);

		// Forum description.
		$this->field_map[] = array(
			'from_tablename'  => 'node',
			'from_fieldname'  => 'description',
			'to_type'         => 'forum',
			'to_fieldname'    => 'post_content',
			'callback_method' => 'callback_null',
		);

		// Forum display order (Starts from 1).
		$this->field_map[] = array(
			'from_tablename' => 'node',
			'from_fieldname' => 'display_order',
			'to_type'        => 'forum',
			'to_fieldname'   => 'menu_order',
		);

		// Forum type (Category = Category or Forum = Forum, Stored in postmeta).
		$this->field_map[] = array(
			'from_tablename'  => 'node',
			'from_fieldname'  => 'node_type_id',
			'to_type'         => 'forum',
			'to_fieldname'    => '_bbp_forum_type',
			'callback_method' => 'callback_forum_type',
		);

		// Forum status (Unlocked = 1 or Locked = 0, Stored in postmeta)
		// Note: We join the 'forum' table because 'node' does not include forum status.
		$this->field_map[] = array(
			'from_tablename'  => 'forum',
			'from_fieldname'  => 'allow_posting',
			'join_tablename'  => 'node',
			'join_type'       => 'LEFT',
			'join_expression' => 'USING (node_id) WHERE node.node_type_id = "Category" OR node.node_type_id = "Forum" ',
			'to_type'         => 'forum',
			'to_fieldname'    => '_bbp_status',
			'callback_method' => 'callback_forum_status',
		);

		// Forum dates.
		$this->field_map[] = array(
			'to_type'      => 'forum',
			'to_fieldname' => 'post_date',
			'default'      => date( 'Y-m-d H:i:s' ),
		);
		$this->field_map[] = array(
			'to_type'      => 'forum',
			'to_fieldname' => 'post_date_gmt',
			'default'      => date( 'Y-m-d H:i:s' ),
		);
		$this->field_map[] = array(
			'to_type'      => 'forum',
			'to_fieldname' => 'post_modified',
			'default'      => date( 'Y-m-d H:i:s' ),
		);
		$this->field_map[] = array(
			'to_type'      => 'forum',
			'to_fieldname' => 'post_modified_gmt',
			'default'      => date( 'Y-m-d H:i:s' ),
		);

		/** Forum Subscriptions Section */

		// Subscribed forum ID (Stored in usermeta).
		$this->field_map[] = array(
			'from_tablename' => 'forum_watch',
			'from_fieldname' => 'node_id',
			'to_type'        => 'forum_subscriptions',
			'to_fieldname'   => '_bbp_forum_subscriptions',
		);

		// Subscribed user ID (Stored in usermeta).
		$this->field_map[] = array(
			'from_tablename'  => 'forum_watch',
			'from_fieldname'  => 'user_id',
			'to_type'         => 'forum_subscriptions',
			'to_fieldname'    => 'user_id',
			'callback_method' => 'callback_userid',
		);

		/** Topic Section */

		// Old topic id (Stored in postmeta).
		$this->field_map[] = array(
			'from_tablename' => 'thread',
			'from_fieldname' => 'thread_id',
			'to_type'        => 'topic',
			'to_fieldname'   => '_bbp_old_topic_id',
		);

		// Topic reply count (Stored in postmeta).
		$this->field_map[] = array(
			'from_tablename'  => 'thread',
			'from_fieldname'  => 'reply_count',
			'to_type'         => 'topic',
			'to_fieldname'    => '_bbp_reply_count',
			'callback_method' => 'callback_topic_reply_count',
		);

		// Topic total reply count (Includes unpublished replies, Stored in postmeta).
		$this->field_map[] = array(
			'from_tablename'  => 'thread',
			'from_fieldname'  => 'reply_count',
			'to_type'         => 'topic',
			'to_fieldname'    => '_bbp_total_reply_count',
			'callback_method' => 'callback_topic_reply_count',
		);

		// Topic parent forum id (If no parent, then 0. Stored in postmeta).
		$this->field_map[] = array(
			'from_tablename'  => 'thread',
			'from_fieldname'  => 'node_id',
			'to_type'         => 'topic',
			'to_fieldname'    => '_bbp_forum_id',
			'callback_method' => 'callback_forumid',
		);

		// Topic author.
		$this->field_map[] = array(
			'from_tablename'  => 'thread',
			'from_fieldname'  => 'user_id',
			'to_type'         => 'topic',
			'to_fieldname'    => 'post_author',
			'callback_method' => 'callback_userid',
		);

		// Topic author name (Stored in postmeta as _bbp_anonymous_name).
		$this->field_map[] = array(
			'from_tablename' => 'thread',
			'from_fieldname' => 'username',
			'to_type'        => 'topic',
			'to_fieldname'   => '_bbp_old_topic_author_name_id',
		);

		// Is the topic anonymous (Stored in postmeta).
		$this->field_map[] = array(
			'from_tablename'  => 'thread',
			'from_fieldname'  => 'user_id',
			'to_type'         => 'topic',
			'to_fieldname'    => '_bbp_old_is_topic_anonymous_id',
			'callback_method' => 'callback_check_anonymous',
		);

		// Topic title.
		$this->field_map[] = array(
			'from_tablename' => 'thread',
			'from_fieldname' => 'title',
			'to_type'        => 'topic',
			'to_fieldname'   => 'post_title',
		);

		// Topic slug (Clean name to avoid conflicts).
		$this->field_map[] = array(
			'from_tablename'  => 'thread',
			'from_fieldname'  => 'title',
			'to_type'         => 'topic',
			'to_fieldname'    => 'post_name',
			'callback_method' => 'callback_slug',
		);

		// Topic content.
		// Note: We join the 'post' table because 'thread' table does not include content.
		$this->field_map[] = array(
			'from_tablename'  => 'post',
			'from_fieldname'  => 'message',
			'join_tablename'  => 'thread',
			'join_type'       => 'INNER',
			'join_expression' => 'ON thread.first_post_id = post.post_id',
			'to_type'         => 'topic',
			'to_fieldname'    => 'post_content',
			'callback_method' => 'callback_html',
		);

		// Topic status (Visible or Deleted).
		$this->field_map[] = array(
			'from_tablename'  => 'thread',
			'from_fieldname'  => 'discussion_state',
			'to_type'         => 'topic',
			'to_fieldname'    => 'post_status',
			'callback_method' => 'callback_status',
		);

		// Topic status (Open = 1 or Closed = 0).
		$this->field_map[] = array(
			'from_tablename'  => 'thread',
			'from_fieldname'  => 'discussion_open',
			'to_type'         => 'topic',
			'to_fieldname'    => '_bbp_old_closed_status_id',
			'callback_method' => 'callback_topic_status',
		);

		// Topic parent forum id (If no parent, then 0).
		$this->field_map[] = array(
			'from_tablename'  => 'thread',
			'from_fieldname'  => 'node_id',
			'to_type'         => 'topic',
			'to_fieldname'    => 'post_parent',
			'callback_method' => 'callback_forumid',
		);

		// Sticky status (Stored in postmeta).
		$this->field_map[] = array(
			'from_tablename'  => 'thread',
			'from_fieldname'  => 'sticky',
			'to_type'         => 'topic',
			'to_fieldname'    => '_bbp_old_sticky_status_id',
			'callback_method' => 'callback_sticky_status',
		);

		// Topic dates.
		$this->field_map[] = array(
			'from_tablename'  => 'thread',
			'from_fieldname'  => 'post_date',
			'to_type'         => 'topic',
			'to_fieldname'    => 'post_date',
			'callback_method' => 'callback_datetime',
		);
		$this->field_map[] = array(
			'from_tablename'  => 'thread',
			'from_fieldname'  => 'post_date',
			'to_type'         => 'topic',
			'to_fieldname'    => 'post_date_gmt',
			'callback_method' => 'callback_datetime',
		);
		$this->field_map[] = array(
			'from_tablename'  => 'thread',
			'from_fieldname'  => 'last_post_date',
			'to_type'         => 'topic',
			'to_fieldname'    => 'post_modified',
			'callback_method' => 'callback_datetime',
		);
		$this->field_map[] = array(
			'from_tablename'  => 'thread',
			'from_fieldname'  => 'last_post_date',
			'to_type'         => 'topic',
			'to_fieldname'    => 'post_modified_gmt',
			'callback_method' => 'callback_datetime',
		);
		$this->field_map[] = array(
			'from_tablename'  => 'thread',
			'from_fieldname'  => 'last_post_date',
			'to_type'         => 'topic',
			'to_fieldname'    => '_bbp_last_active_time',
			'callback_method' => 'callback_datetime',
		);

		/** Tags Section */

		/**
		 * XenForo Forums do not support topic tags out of the box
		 */

		/** Topic Subscriptions Section */

		// Subscribed topic ID (Stored in usermeta).
		$this->field_map[] = array(
			'from_tablename' => 'thread_watch',
			'from_fieldname' => 'thread_id',
			'to_type'        => 'topic_subscriptions',
			'to_fieldname'   => '_bbp_subscriptions',
		);

		// Subscribed user ID (Stored in usermeta).
		$this->field_map[] = array(
			'from_tablename'  => 'thread_watch',
			'from_fieldname'  => 'user_id',
			'to_type'         => 'topic_subscriptions',
			'to_fieldname'    => 'user_id',
			'callback_method' => 'callback_userid',
		);

		/** Reply Section */

		// Old reply id (Stored in postmeta).
		$this->field_map[] = array(
			'from_tablename' => 'post',
			'from_fieldname' => 'post_id',
			'to_type'        => 'reply',
			'to_fieldname'   => '_bbp_old_reply_id',
		);

		// Join the 'thread' table to exclude topics from being imported as replies.
		$this->field_map[] = array(
			'from_tablename'  => 'thread',
			'from_fieldname'  => 'thread_id',
			'join_tablename'  => 'post',
			'join_type'       => 'LEFT',
			'join_expression' => 'USING (thread_id) WHERE thread.first_post_id != post.post_id',
			'to_type'         => 'reply',
		);

		// Reply parent forum id (If no parent, then 0. Stored in postmeta).
		$this->field_map[] = array(
			'from_tablename'  => 'post',
			'from_fieldname'  => 'thread_id',
			'to_type'         => 'reply',
			'to_fieldname'    => '_bbp_forum_id',
			'callback_method' => 'callback_topicid_to_forumid',
		);

		// Reply parent topic id (If no parent, then 0. Stored in postmeta).
		$this->field_map[] = array(
			'from_tablename'  => 'post',
			'from_fieldname'  => 'thread_id',
			'to_type'         => 'reply',
			'to_fieldname'    => '_bbp_topic_id',
			'callback_method' => 'callback_topicid',
		);

		// Reply author.
		$this->field_map[] = array(
			'from_tablename'  => 'post',
			'from_fieldname'  => 'user_id',
			'to_type'         => 'reply',
			'to_fieldname'    => 'post_author',
			'callback_method' => 'callback_userid',
		);

		// Reply status (Visible or Deleted).
		$this->field_map[] = array(
			'from_tablename'  => 'post',
			'from_fieldname'  => 'message_state',
			'to_type'         => 'reply',
			'to_fieldname'    => 'post_status',
			'callback_method' => 'callback_status',
		);

		// Reply author name (Stored in postmeta as _bbp_anonymous_name).
		$this->field_map[] = array(
			'from_tablename' => 'post',
			'from_fieldname' => 'username',
			'to_type'        => 'reply',
			'to_fieldname'   => '_bbp_old_reply_author_name_id',
		);

		// Is the reply anonymous  (Stored in postmeta).
		$this->field_map[] = array(
			'from_tablename'  => 'post',
			'from_fieldname'  => 'user_id',
			'to_type'         => 'reply',
			'to_fieldname'    => '_bbp_old_is_reply_anonymous_id',
			'callback_method' => 'callback_check_anonymous',
		);

		// Reply content.
		$this->field_map[] = array(
			'from_tablename'  => 'post',
			'from_fieldname'  => 'message',
			'to_type'         => 'reply',
			'to_fieldname'    => 'post_content',
			'callback_method' => 'callback_html',
		);

		// Reply parent topic id (If no parent, then 0).
		$this->field_map[] = array(
			'from_tablename'  => 'post',
			'from_fieldname'  => 'thread_id',
			'to_type'         => 'reply',
			'to_fieldname'    => 'post_parent',
			'callback_method' => 'callback_topicid',
		);

		// Reply dates.
		$this->field_map[] = array(
			'from_tablename'  => 'post',
			'from_fieldname'  => 'post_date',
			'to_type'         => 'reply',
			'to_fieldname'    => 'post_date',
			'callback_method' => 'callback_datetime',
		);
		$this->field_map[] = array(
			'from_tablename'  => 'post',
			'from_fieldname'  => 'post_date',
			'to_type'         => 'reply',
			'to_fieldname'    => 'post_date_gmt',
			'callback_method' => 'callback_datetime',
		);
		$this->field_map[] = array(
			'from_tablename'  => 'post',
			'from_fieldname'  => 'post_date',
			'to_type'         => 'reply',
			'to_fieldname'    => 'post_modified',
			'callback_method' => 'callback_datetime',
		);
		$this->field_map[] = array(
			'from_tablename'  => 'post',
			'from_fieldname'  => 'post_date',
			'to_type'         => 'reply',
			'to_fieldname'    => 'post_modified_gmt',
			'callback_method' => 'callback_datetime',
		);

		/** User Section */

		// Store old user id (Stored in usermeta).
		$this->field_map[] = array(
			'from_tablename' => 'user',
			'from_fieldname' => 'user_id',
			'to_type'        => 'user',
			'to_fieldname'   => '_bbp_old_user_id',
		);

		// User password verify class (Stored in usermeta for verifying password).
		$this->field_map[] = array(
			'to_type'      => 'user',
			'to_fieldname' => '_bbp_class',
			'default'      => 'XenForo',
		);

		// User name.
		$this->field_map[] = array(
			'from_tablename' => 'user',
			'from_fieldname' => 'username',
			'to_type'        => 'user',
			'to_fieldname'   => 'user_login',
		);

		// User email.
		$this->field_map[] = array(
			'from_tablename' => 'user',
			'from_fieldname' => 'email',
			'to_type'        => 'user',
			'to_fieldname'   => 'user_email',
		);

		// User homepage.
		// Note: We join the 'user_profile' table because 'user' does not include user homepage.
		$this->field_map[] = array(
			'from_tablename'  => 'user_profile',
			'from_fieldname'  => 'homepage',
			'join_tablename'  => 'user',
			'join_type'       => 'LEFT',
			'join_expression' => 'USING (user_id)',
			'to_type'         => 'user',
			'to_fieldname'    => 'user_url',
		);

		// User registered.
		$this->field_map[] = array(
			'from_tablename'  => 'user',
			'from_fieldname'  => 'register_date',
			'to_type'         => 'user',
			'to_fieldname'    => 'user_registered',
			'callback_method' => 'callback_datetime',
		);

		// User display name.
		$this->field_map[] = array(
			'from_tablename' => 'user',
			'from_fieldname' => 'username',
			'to_type'        => 'user',
			'to_fieldname'   => 'display_name',
		);

		// Store Custom Title (Stored in usermeta).
		$this->field_map[] = array(
			'from_tablename' => 'user',
			'from_fieldname' => 'custom_title',
			'to_type'        => 'user',
			'to_fieldname'   => '_bbp_xenforo_user_custom_title',
		);

		// Store Status (Stored in usermeta)
		// Note: We join the 'user_profile' table because 'user' does not include user custom XenForo field user status.
		$this->field_map[] = array(
			'from_tablename'  => 'user_profile',
			'from_fieldname'  => 'status',
			'join_tablename'  => 'user',
			'join_type'       => 'LEFT',
			'join_expression' => 'USING (user_id)',
			'to_type'         => 'user',
			'to_fieldname'    => '_bbp_xenforo_user_status',
		);

		// Store Signature (Stored in usermeta)
		// Note: We join the 'user_profile' table because 'user' does not include user custom XenForo field user signature.
		$this->field_map[] = array(
			'from_tablename'  => 'user_profile',
			'from_fieldname'  => 'signature',
			'join_tablename'  => 'user',
			'join_type'       => 'LEFT',
			'join_expression' => 'USING (user_id)',
			'to_fieldname'    => '_bbp_xenforo_user_sig',
			'to_type'         => 'user',
			'callback_method' => 'callback_html',
		);

		// Store Location (Stored in usermeta)
		// Note: We join the 'user_profile' table because 'user' does not include user custom XenForo field user location.
		$this->field_map[] = array(
			'from_tablename'  => 'user_profile',
			'from_fieldname'  => 'location',
			'join_tablename'  => 'user',
			'join_type'       => 'LEFT',
			'join_expression' => 'USING (user_id)',
			'to_type'         => 'user',
			'to_fieldname'    => '_bbp_xenforo_user_location',
		);

		// Store Occupation (Stored in usermeta)
		// Note: We join the 'user_profile' table because 'user' does not include user custom XenForo field user occupation.
		$this->field_map[] = array(
			'from_tablename'  => 'user_profile',
			'from_fieldname'  => 'occupation',
			'join_tablename'  => 'user',
			'join_type'       => 'LEFT',
			'join_expression' => 'USING (user_id)',
			'to_type'         => 'user',
			'to_fieldname'    => '_bbp_xenforo_user_occupation',
		);

		// Store About (Stored in usermeta)
		// Note: We join the 'user_profile' table because 'user' does not include user custom XenForo field user about.
		$this->field_map[] = array(
			'from_tablename'  => 'user_profile',
			'from_fieldname'  => 'about',
			'join_tablename'  => 'user',
			'join_type'       => 'LEFT',
			'join_expression' => 'USING (user_id)',
			'to_type'         => 'user',
			'to_fieldname'    => '_bbp_xenforo_user_about',
			'callback_method' => 'callback_html',
		);
	}

	/**
	 * This method allows us to indicates what is or is not converted for each
	 * converter.
	 */
	public function info() {
		return '';
	}

	/**
	 * This method is to save the salt and password together.  That
	 * way when we authenticate it we can get it out of the database
	 * as one value. Array values are auto sanitized by WordPress.
	 *
	 * @param string $field Field hash.
	 * @param array  $row   Array.
	 */
	public function translate_savepass( $field, $row ) {
		$pass_array = array(
			'hash' => $field,
			'salt' => $row['salt'],
		);
		return $pass_array;
	}

	/**
	 * This method is to take the pass out of the database and compare
	 * to a pass the user has typed in.
	 *
	 * @param string $password Password.
	 * @param string $serialized_pass Serialized password.
	 */
	public function authenticate_pass( $password, $serialized_pass ) {
		$pass_array = unserialize( $serialized_pass ); // phpcs:IGNORE WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize
		switch ( $pass_array['hashFunc'] ) {
			case 'sha256':
				return ( hash( 'sha256', hash( 'sha256', $password ) . $pass_array['salt'] ) == $pass_array['hash'] ); // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
			case 'sha1':
				return ( sha1( sha1( $password ) . $pass_array['salt'] ) == $pass_array['hash'] ); // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
		}
	}

	/**
	 * Translate the forum type from XenForo Capitalised case to WordPress's non-capatilise case strings.
	 *
	 * @param int $status XenForo numeric forum type.
	 *
	 * @return string WordPress safe
	 */
	public function callback_forum_type( $status = 1 ) {
		switch ( $status ) {
			case 'Category':
				$status = 'category';
				break;

			case 'Forum':
			default:
				$status = 'forum';
				break;
		}
		return $status;
	}

	/**
	 * Translate the forum status from XenForo numeric's to WordPress's strings.
	 *
	 * @param int $status XenForo numeric forum status.
	 * @return string WordPress safe
	 */
	public function callback_forum_status( $status = 1 ) {
		switch ( $status ) {
			case 0:
				$status = 'closed';
				break;

			case 1:
			default:
				$status = 'open';
				break;
		}
		return $status;
	}

	/**
	 * Translate the post status from XenForo to WordPress' strings.
	 *
	 * @param int $status XenForo post status.
	 *
	 * @return string WordPress safe
	 */
	public function callback_status( $status = 1 ) {
		switch ( $status ) {
			case 'deleted':
				// Similar to bbp_get_pending_status_id().
				$status = 'pending';
				break;

			case 'visible':
			default:
				// Similar to bbp_get_public_status_id().
				$status = 'publish';
				break;
		}
		return $status;
	}

	/**
	 * Translate the topic status from XenForo numeric's to WordPress's strings.
	 *
	 * @param int $status XenForo numeric topic status.
	 *
	 * @return string WordPress safe
	 */
	public function callback_topic_status( $status = 1 ) {
		switch ( $status ) {
			case 0:
				$status = 'closed';
				break;

			case 1:
			default:
				$status = 'publish';
				break;
		}
		return $status;
	}

	/**
	 * Translate the topic sticky status type from XenForo numeric's to WordPress's strings.
	 *
	 * @param int $status XenForo numeric forum type.
	 *
	 * @return string WordPress safe
	 */
	public function callback_sticky_status( $status = 0 ) {
		switch ( $status ) {
			case 1:
				$status = 'sticky';       // XenForo Sticky 'sticky = 1'.
				break;

			case 0:
			default:
				$status = 'normal';       // XenForo Normal Topic 'sticky = 0'.
				break;
		}
		return $status;
	}

	/**
	 * Verify the topic reply count.
	 *
	 * @param int $count XenForo reply count.
	 *
	 * @return string WordPress safe
	 */
	public function callback_topic_reply_count( $count = 0 ) {
		return (int) $count;
	}

	/**
	 * This callback processes any custom parser.php attributes and custom code with preg_replace.
	 *
	 * @param string $field XenForo content.
	 */
	protected function callback_html( $field ) {

		// Strips Xenforo custom HTML first from $field before parsing $field to parser.php.
		$xenforo_markup = $field;
		$xenforo_markup = html_entity_decode( $xenforo_markup );

		// Replace '[QUOTE]' with '<blockquote>'.
		$xenforo_markup = preg_replace( '/\[QUOTE\]/', '<blockquote>', $xenforo_markup );
		// Replace '[/QUOTE]' with '</blockquote>'.
		$xenforo_markup = preg_replace( '/\[\/QUOTE\]/', '</blockquote>', $xenforo_markup );
		// Replace '[QUOTE=User Name($1)]' with '<em>@$1 wrote:</em><blockquote>".
		$xenforo_markup = preg_replace( '/\[quote=\"(.*?)\,\spost\:\s(.*?)\,\smember\:\s(.*?)\"\](.*?)\[\/quote\]/', '<em>@$1 wrote:</em><blockquote>', $xenforo_markup );
		// Replace '[/quote]' with '</blockquote>'.
		$xenforo_markup = preg_replace( '/\[\/quote\]/', '</blockquote>', $xenforo_markup );

		// Replace '[media=youtube]$1[/media]' with '$1".
		$xenforo_markup = preg_replace( '/\[media\=youtube\](.*?)\[\/media\]/', 'https://youtu.be/$1', $xenforo_markup );
		// Replace '[media=dailymotion]$1[/media]' with '$1".
		$xenforo_markup = preg_replace( '/\[media\=dailymotion\](.*?)\[\/media\]/', 'https://www.dailymotion.com/video/$1', $xenforo_markup );
		// Replace '[media=vimeo]$1[/media]' with '$1".
		$xenforo_markup = preg_replace( '/\[media\=vimeo\](.*?)\[\/media\]/', 'https://vimeo.com/$1', $xenforo_markup );

		// Now that Xenforo custom HTML has been stripped put the cleaned HTML back in $field.
		$field = $xenforo_markup;

		// Parse out any bbCodes in $field with the BBCode 'parser.php'.
		return parent::callback_html( $field );
	}
}
