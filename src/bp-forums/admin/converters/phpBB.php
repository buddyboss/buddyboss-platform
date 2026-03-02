<?php

/**
 * Implementation of phpBB v3 Converter.
 *
 * @since bbPress (r4689)
 * @link Codex Docs http://codex.bbpress.org/import-forums/phpbb
 */
class phpBB extends BBP_Converter_Base {

	/**
	 * Main Constructor
	 */
	public function __construct() {
		parent::__construct();
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

		// Old forum id (Stored in postmeta).
		$this->field_map[] = array(
			'from_tablename' => 'forums',
			'from_fieldname' => 'forum_id',
			'to_type'        => 'forum',
			'to_fieldname'   => '_bbp_old_forum_id',
		);

		// Forum parent id (If no parent, then 0, Stored in postmeta).
		$this->field_map[] = array(
			'from_tablename' => 'forums',
			'from_fieldname' => 'parent_id',
			'to_type'        => 'forum',
			'to_fieldname'   => '_bbp_old_forum_parent_id',
		);

		// Forum topic count (Stored in postmeta).
		$this->field_map[] = array(
			'from_tablename' => 'forums',
			'from_fieldname' => 'forum_topics_approved',
			'to_type'        => 'forum',
			'to_fieldname'   => '_bbp_topic_count',
		);

		// Forum reply count (Stored in postmeta).
		$this->field_map[] = array(
			'from_tablename' => 'forums',
			'from_fieldname' => 'forum_posts_approved',
			'to_type'        => 'forum',
			'to_fieldname'   => '_bbp_reply_count',
		);

		// Forum total topic count (Includes unpublished topics, Stored in postmeta).
		$this->field_map[] = array(
			'from_tablename' => 'forums',
			'from_fieldname' => 'forum_topics_approved',
			'to_type'        => 'forum',
			'to_fieldname'   => '_bbp_total_topic_count',
		);

		// Forum total reply count (Includes unpublished replies, Stored in postmeta).
		$this->field_map[] = array(
			'from_tablename' => 'forums',
			'from_fieldname' => 'forum_posts_approved',
			'to_type'        => 'forum',
			'to_fieldname'   => '_bbp_total_reply_count',
		);

		// Forum title.
		$this->field_map[] = array(
			'from_tablename' => 'forums',
			'from_fieldname' => 'forum_name',
			'to_type'        => 'forum',
			'to_fieldname'   => 'post_title',
		);

		// Forum slug (Clean name to avoid conflicts).
		$this->field_map[] = array(
			'from_tablename'  => 'forums',
			'from_fieldname'  => 'forum_name',
			'to_type'         => 'forum',
			'to_fieldname'    => 'post_name',
			'callback_method' => 'callback_slug',
		);

		// Forum description.
		$this->field_map[] = array(
			'from_tablename'  => 'forums',
			'from_fieldname'  => 'forum_desc',
			'to_type'         => 'forum',
			'to_fieldname'    => 'post_content',
			'callback_method' => 'callback_null',
		);

		// Forum display order (Starts from 1).
		$this->field_map[] = array(
			'from_tablename' => 'forums',
			'from_fieldname' => 'left_id',
			'to_type'        => 'forum',
			'to_fieldname'   => 'menu_order',
		);

		// Forum type (Category = 0 or Forum = 1, Stored in postmeta).
		$this->field_map[] = array(
			'from_tablename'  => 'forums',
			'from_fieldname'  => 'forum_type',
			'to_type'         => 'forum',
			'to_fieldname'    => '_bbp_forum_type',
			'callback_method' => 'callback_forum_type',
		);

		// Forum status (Unlocked = 0 or Locked = 1, Stored in postmeta).
		$this->field_map[] = array(
			'from_tablename'  => 'forums',
			'from_fieldname'  => 'forum_status',
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
			'from_tablename' => 'forums_watch',
			'from_fieldname' => 'forum_id',
			'to_type'        => 'forum_subscriptions',
			'to_fieldname'   => '_bbp_forum_subscriptions',
		);

		// Subscribed user ID (Stored in usermeta).
		$this->field_map[] = array(
			'from_tablename'  => 'forums_watch',
			'from_fieldname'  => 'user_id',
			'to_type'         => 'forum_subscriptions',
			'to_fieldname'    => 'user_id',
			'callback_method' => 'callback_userid',
		);

		/** Topic Section */

		// Old topic id (Stored in postmeta).
		$this->field_map[] = array(
			'from_tablename' => 'topics',
			'from_fieldname' => 'topic_id',
			'to_type'        => 'topic',
			'to_fieldname'   => '_bbp_old_topic_id',
		);

		// Topic reply count (Stored in postmeta).
		$this->field_map[] = array(
			'from_tablename'  => 'topics',
			'from_fieldname'  => 'topic_posts_approved',
			'to_type'         => 'topic',
			'to_fieldname'    => '_bbp_reply_count',
			'callback_method' => 'callback_topic_reply_count',
		);

		// Topic total reply count (Includes unpublished replies, Stored in postmeta).
		$this->field_map[] = array(
			'from_tablename'  => 'topics',
			'from_fieldname'  => 'topic_posts_approved',
			'to_type'         => 'topic',
			'to_fieldname'    => '_bbp_total_reply_count',
			'callback_method' => 'callback_topic_reply_count',
		);

		// Topic parent forum id (If no parent, then 0. Stored in postmeta).
		$this->field_map[] = array(
			'from_tablename'  => 'topics',
			'from_fieldname'  => 'forum_id',
			'to_type'         => 'topic',
			'to_fieldname'    => '_bbp_forum_id',
			'callback_method' => 'callback_forumid',
		);

		// Topic author.
		$this->field_map[] = array(
			'from_tablename'  => 'topics',
			'from_fieldname'  => 'topic_poster',
			'to_type'         => 'topic',
			'to_fieldname'    => 'post_author',
			'callback_method' => 'callback_userid',
		);

		// Topic author name (Stored in postmeta as _bbp_anonymous_name).
		$this->field_map[] = array(
			'from_tablename' => 'topics',
			'from_fieldname' => 'topic_first_poster_name',
			'to_type'        => 'topic',
			'to_fieldname'   => '_bbp_old_topic_author_name_id',
		);

		// Is the topic anonymous (Stored in postmeta).
		$this->field_map[] = array(
			'from_tablename'  => 'topics',
			'from_fieldname'  => 'topic_poster',
			'to_type'         => 'topic',
			'to_fieldname'    => '_bbp_old_is_topic_anonymous_id',
			'callback_method' => 'callback_check_anonymous',
		);

		// Topic Author ip (Stored in postmeta).
		$this->field_map[] = array(
			'from_tablename'  => 'posts',
			'from_fieldname'  => 'poster_ip',
			'join_tablename'  => 'topics',
			'join_type'       => 'INNER',
			'join_expression' => 'USING (topic_id) WHERE posts.post_id = topics.topic_first_post_id',
			'to_type'         => 'topic',
			'to_fieldname'    => '_bbp_author_ip',
		);

		// Topic content.
		// Note: We join the 'posts' table because 'topics' does not include topic content.
		$this->field_map[] = array(
			'from_tablename'  => 'posts',
			'from_fieldname'  => 'post_text',
			'join_tablename'  => 'topics',
			'join_type'       => 'INNER',
			'join_expression' => 'USING (topic_id) WHERE posts.post_id = topics.topic_first_post_id',
			'to_type'         => 'topic',
			'to_fieldname'    => 'post_content',
			'callback_method' => 'callback_html',
		);

		// Topic title.
		$this->field_map[] = array(
			'from_tablename' => 'topics',
			'from_fieldname' => 'topic_title',
			'to_type'        => 'topic',
			'to_fieldname'   => 'post_title',
		);

		// Topic slug (Clean name to avoid conflicts).
		$this->field_map[] = array(
			'from_tablename'  => 'topics',
			'from_fieldname'  => 'topic_title',
			'to_type'         => 'topic',
			'to_fieldname'    => 'post_name',
			'callback_method' => 'callback_slug',
		);

		// Topic status (Open or Closed).
		$this->field_map[] = array(
			'from_tablename'  => 'topics',
			'from_fieldname'  => 'topic_status',
			'to_type'         => 'topic',
			'to_fieldname'    => '_bbp_old_closed_status_id',
			'callback_method' => 'callback_topic_status',
		);

		// Topic parent forum id (If no parent, then 0).
		$this->field_map[] = array(
			'from_tablename'  => 'topics',
			'from_fieldname'  => 'forum_id',
			'to_type'         => 'topic',
			'to_fieldname'    => 'post_parent',
			'callback_method' => 'callback_forumid',
		);

		// Sticky status (Stored in postmeta).
		$this->field_map[] = array(
			'from_tablename'  => 'topics',
			'from_fieldname'  => 'topic_type',
			'to_type'         => 'topic',
			'to_fieldname'    => '_bbp_old_sticky_status_id',
			'callback_method' => 'callback_sticky_status',
		);

		// Topic dates.
		$this->field_map[] = array(
			'from_tablename'  => 'topics',
			'from_fieldname'  => 'topic_time',
			'to_type'         => 'topic',
			'to_fieldname'    => 'post_date',
			'callback_method' => 'callback_datetime',
		);
		$this->field_map[] = array(
			'from_tablename'  => 'topics',
			'from_fieldname'  => 'topic_time',
			'to_type'         => 'topic',
			'to_fieldname'    => 'post_date_gmt',
			'callback_method' => 'callback_datetime',
		);
		$this->field_map[] = array(
			'from_tablename'  => 'topics',
			'from_fieldname'  => 'topic_time',
			'to_type'         => 'topic',
			'to_fieldname'    => 'post_modified',
			'callback_method' => 'callback_datetime',
		);
		$this->field_map[] = array(
			'from_tablename'  => 'topics',
			'from_fieldname'  => 'topic_time',
			'to_type'         => 'topic',
			'to_fieldname'    => 'post_modified_gmt',
			'callback_method' => 'callback_datetime',
		);
		$this->field_map[] = array(
			'from_tablename'  => 'topics',
			'from_fieldname'  => 'topic_last_post_time',
			'to_type'         => 'topic',
			'to_fieldname'    => '_bbp_last_active_time',
			'callback_method' => 'callback_datetime',
		);

		/** Tags Section */

		/**
		 * phpBB Forums do not support topic tags.
		 */

		/** Topic Subscriptions Section */

		// Subscribed topic ID (Stored in usermeta).
		$this->field_map[] = array(
			'from_tablename' => 'topics_watch',
			'from_fieldname' => 'topic_id',
			'to_type'        => 'topic_subscriptions',
			'to_fieldname'   => '_bbp_subscriptions',
		);

		// Subscribed user ID (Stored in usermeta).
		$this->field_map[] = array(
			'from_tablename'  => 'topics_watch',
			'from_fieldname'  => 'user_id',
			'to_type'         => 'topic_subscriptions',
			'to_fieldname'    => 'user_id',
			'callback_method' => 'callback_userid',
		);

		/** Favorites Section */

		// Favorited topic ID (Stored in usermeta).
		$this->field_map[] = array(
			'from_tablename' => 'bookmarks',
			'from_fieldname' => 'topic_id',
			'to_type'        => 'favorites',
			'to_fieldname'   => '_bbp_favorites',
		);

		// Favorited user ID (Stored in usermeta).
		$this->field_map[] = array(
			'from_tablename'  => 'bookmarks',
			'from_fieldname'  => 'user_id',
			'to_type'         => 'favorites',
			'to_fieldname'    => 'user_id',
			'callback_method' => 'callback_userid',
		);

		/** Reply Section */

		// Old reply id (Stored in postmeta).
		$this->field_map[] = array(
			'from_tablename' => 'posts',
			'from_fieldname' => 'post_id',
			'to_type'        => 'reply',
			'to_fieldname'   => '_bbp_old_reply_id',
		);

		// Setup reply section table joins.
		$this->field_map[] = array(
			'from_tablename'  => 'topics',
			'from_fieldname'  => 'topic_id',
			'join_tablename'  => 'posts',
			'join_type'       => 'LEFT',
			'join_expression' => 'USING (topic_id) WHERE posts.post_id != topics.topic_first_post_id',
			'to_type'         => 'reply',
		);

		// Reply parent forum id (If no parent, then 0. Stored in postmeta).
		$this->field_map[] = array(
			'from_tablename'  => 'posts',
			'from_fieldname'  => 'forum_id',
			'to_type'         => 'reply',
			'to_fieldname'    => '_bbp_forum_id',
			'callback_method' => 'callback_forumid',
		);

		// Reply parent topic id (If no parent, then 0. Stored in postmeta).
		$this->field_map[] = array(
			'from_tablename'  => 'posts',
			'from_fieldname'  => 'topic_id',
			'to_type'         => 'reply',
			'to_fieldname'    => '_bbp_topic_id',
			'callback_method' => 'callback_topicid',
		);

		// Reply author ip (Stored in postmeta).
		$this->field_map[] = array(
			'from_tablename' => 'posts',
			'from_fieldname' => 'poster_ip',
			'to_type'        => 'reply',
			'to_fieldname'   => '_bbp_author_ip',
		);

		// Reply author.
		$this->field_map[] = array(
			'from_tablename'  => 'posts',
			'from_fieldname'  => 'poster_id',
			'to_type'         => 'reply',
			'to_fieldname'    => 'post_author',
			'callback_method' => 'callback_userid',
		);

		// Reply author name (Stored in postmeta as _bbp_anonymous_name).
		$this->field_map[] = array(
			'from_tablename' => 'posts',
			'from_fieldname' => 'post_username',
			'to_type'        => 'reply',
			'to_fieldname'   => '_bbp_old_reply_author_name_id',
		);

		// Is the reply anonymous (Stored in postmeta).
		$this->field_map[] = array(
			'from_tablename'  => 'posts',
			'from_fieldname'  => 'poster_id',
			'to_type'         => 'reply',
			'to_fieldname'    => '_bbp_old_is_reply_anonymous_id',
			'callback_method' => 'callback_check_anonymous',
		);

		// Reply content.
		$this->field_map[] = array(
			'from_tablename'  => 'posts',
			'from_fieldname'  => 'post_text',
			'to_type'         => 'reply',
			'to_fieldname'    => 'post_content',
			'callback_method' => 'callback_html',
		);

		// Reply parent topic id (If no parent, then 0).
		$this->field_map[] = array(
			'from_tablename'  => 'posts',
			'from_fieldname'  => 'topic_id',
			'to_type'         => 'reply',
			'to_fieldname'    => 'post_parent',
			'callback_method' => 'callback_topicid',
		);

		// Reply dates.
		$this->field_map[] = array(
			'from_tablename'  => 'posts',
			'from_fieldname'  => 'post_time',
			'to_type'         => 'reply',
			'to_fieldname'    => 'post_date',
			'callback_method' => 'callback_datetime',
		);
		$this->field_map[] = array(
			'from_tablename'  => 'posts',
			'from_fieldname'  => 'post_time',
			'to_type'         => 'reply',
			'to_fieldname'    => 'post_date_gmt',
			'callback_method' => 'callback_datetime',
		);
		$this->field_map[] = array(
			'from_tablename'  => 'posts',
			'from_fieldname'  => 'post_time',
			'to_type'         => 'reply',
			'to_fieldname'    => 'post_modified',
			'callback_method' => 'callback_datetime',
		);
		$this->field_map[] = array(
			'from_tablename'  => 'posts',
			'from_fieldname'  => 'post_time',
			'to_type'         => 'reply',
			'to_fieldname'    => 'post_modified_gmt',
			'callback_method' => 'callback_datetime',
		);

		/** User Section */

		// Store old user id (Stored in usermeta).
		// Don't import users with id 2, these are phpBB bot/crawler accounts.
		$this->field_map[] = array(
			'from_tablename' => 'users',
			'from_fieldname' => 'user_id',
			'to_type'        => 'user',
			'to_fieldname'   => '_bbp_old_user_id',
		);

		// Store old user password (Stored in usermeta serialized with salt).
		$this->field_map[] = array(
			'from_tablename'  => 'users',
			'from_fieldname'  => 'user_password',
			'to_type'         => 'user',
			'to_fieldname'    => '_bbp_password',
			'callback_method' => 'callback_savepass',
		);

		// Store old user salt (This is only used for the SELECT row info for the above password save).
		$this->field_map[] = array(
			'from_tablename' => 'users',
			'from_fieldname' => 'user_form_salt',
			'to_type'        => 'user',
			'to_fieldname'   => '',
		);

		// User password verify class (Stored in usermeta for verifying password).
		$this->field_map[] = array(
			'to_type'      => 'user',
			'to_fieldname' => '_bbp_class',
			'default'      => 'phpBB',
		);

		// User name.
		$this->field_map[] = array(
			'from_tablename' => 'users',
			'from_fieldname' => 'username',
			'to_type'        => 'user',
			'to_fieldname'   => 'user_login',
		);

		// User email.
		$this->field_map[] = array(
			'from_tablename' => 'users',
			'from_fieldname' => 'user_email',
			'to_type'        => 'user',
			'to_fieldname'   => 'user_email',
		);

		// User homepage.
		$this->field_map[] = array(
			'from_tablename'  => 'profile_fields_data',
			'from_fieldname'  => 'pf_phpbb_website',
			'join_tablename'  => 'users',
			'join_type'       => 'LEFT',
			'join_expression' => 'USING (user_id) WHERE users.user_type !=2',
			'to_type'         => 'user',
			'to_fieldname'    => 'user_url',
		);

		// User registered.
		$this->field_map[] = array(
			'from_tablename'  => 'users',
			'from_fieldname'  => 'user_regdate',
			'to_type'         => 'user',
			'to_fieldname'    => 'user_registered',
			'callback_method' => 'callback_datetime',
		);

		// User Yahoo (Stored in usermeta).
		$this->field_map[] = array(
			'from_tablename' => 'profile_fields_data',
			'from_fieldname' => 'pf_phpbb_yahoo',
			'to_type'        => 'user',
			'to_fieldname'   => '_bbp_phpbb_user_yim',
		);

		// Store ICQ (Stored in usermeta).
		$this->field_map[] = array(
			'from_tablename' => 'profile_fields_data',
			'from_fieldname' => 'pf_phpbb_icq',
			'to_type'        => 'user',
			'to_fieldname'   => '_bbp_phpbb_user_icq',
		);

		// Store Facebook (Stored in usermeta).
		$this->field_map[] = array(
			'from_tablename' => 'profile_fields_data',
			'from_fieldname' => 'pf_phpbb_facebook',
			'to_type'        => 'user',
			'to_fieldname'   => '_bbp_phpbb_user_facebook',
		);

		// Store Skype (Stored in usermeta).
		$this->field_map[] = array(
			'from_tablename' => 'profile_fields_data',
			'from_fieldname' => 'pf_phpbb_skype',
			'to_type'        => 'user',
			'to_fieldname'   => '_bbp_phpbb_user_skype',
		);

		// Store Twitter (Stored in usermeta).
		$this->field_map[] = array(
			'from_tablename' => 'profile_fields_data',
			'from_fieldname' => 'pf_phpbb_twitter',
			'to_type'        => 'user',
			'to_fieldname'   => '_bbp_phpbb_user_twitter',
		);

		// Store Youtube (Stored in usermeta).
		$this->field_map[] = array(
			'from_tablename' => 'profile_fields_data',
			'from_fieldname' => 'pf_phpbb_youtube',
			'to_type'        => 'user',
			'to_fieldname'   => '_bbp_phpbb_user_youtube',
		);

		// Store Jabber.
		$this->field_map[] = array(
			'from_tablename' => 'users',
			'from_fieldname' => 'user_jabber',
			'to_type'        => 'user',
			'to_fieldname'   => '_bbp_phpbb_user_jabber',
		);

		// Store Occupation (Stored in usermeta).
		$this->field_map[] = array(
			'from_tablename' => 'profile_fields_data',
			'from_fieldname' => 'pf_phpbb_occupation',
			'to_type'        => 'user',
			'to_fieldname'   => '_bbp_phpbb_user_occ',
		);

		// Store Interests (Stored in usermeta).
		$this->field_map[] = array(
			'from_tablename' => 'profile_fields_data',
			'from_fieldname' => 'pf_phpbb_interests',
			'to_type'        => 'user',
			'to_fieldname'   => '_bbp_phpbb_user_interests',
		);

		// Store Signature (Stored in usermeta).
		$this->field_map[] = array(
			'from_tablename'  => 'users',
			'from_fieldname'  => 'user_sig',
			'to_type'         => 'user',
			'to_fieldname'    => '_bbp_phpbb_user_sig',
			'callback_method' => 'callback_html',
		);

		// Store Location (Stored in usermeta).
		$this->field_map[] = array(
			'from_tablename' => 'profile_fields_data',
			'from_fieldname' => 'pf_phpbb_location',
			'to_type'        => 'user',
			'to_fieldname'   => '_bbp_phpbb_user_from',
		);

		// Store Avatar Filename (Stored in usermeta).
		$this->field_map[] = array(
			'from_tablename' => 'users',
			'from_fieldname' => 'user_avatar',
			'to_type'        => 'user',
			'to_fieldname'   => '_bbp_phpbb_user_avatar',
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
	 * as one value.
	 */
	public function callback_savepass( $field, $row ) {
		return array(
			'hash' => $field,
			'salt' => $row['user_form_salt'],
		);
	}

	/**
	 * Check for correct password
	 *
	 * @param string $password The password in plain text
	 * @param string $hash The stored password hash
	 *
	 * @link Original source for password functions http://openwall.com/phpass/
	 * @link phpass is now included in WP Core https://core.trac.wordpress.org/browser/trunk/wp-includes/class-phpass.php
	 *
	 * @return bool Returns true if the password is correct, false if not.
	 */
	public function authenticate_pass( $password, $serialized_pass ) {
		$itoa64     = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
		$pass_array = unserialize( $serialized_pass );
		if ( strlen( $pass_array['hash'] ) == 34 ) {
			return ( $this->_hash_crypt_private( $password, $pass_array['hash'], $itoa64 ) === $pass_array['hash'] ) ? true : false;
		}

		return ( md5( $password ) === $pass_array['hash'] ) ? true : false;
	}

	/**
	 * The crypt function/replacement
	 */
	private function _hash_crypt_private( $password, $setting, &$itoa64 ) {
		$output = '*';

		// Check for correct hash
		if ( substr( $setting, 0, 3 ) != '$H$' ) {
			return $output;
		}

		$count_log2 = strpos( $itoa64, $setting[3] );

		if ( $count_log2 < 7 || $count_log2 > 30 ) {
			return $output;
		}

		$count = 1 << $count_log2;
		$salt  = substr( $setting, 4, 8 );

		if ( strlen( $salt ) != 8 ) {
			return $output;
		}

		/**
		 * We're kind of forced to use MD5 here since it's the only
		 * cryptographic primitive available in all versions of PHP
		 * currently in use.  To implement our own low-level crypto
		 * in PHP would result in much worse performance and
		 * consequently in lower iteration counts and hashes that are
		 * quicker to crack (by non-PHP code).
		 */
		if ( floatval( phpversion() ) >= 5 ) {
			$hash = md5( $salt . $password, true );
			do {
				$hash = md5( $hash . $password, true );
			} while ( --$count );
		} else {
			$hash = pack( 'H*', md5( $salt . $password ) );
			do {
				$hash = pack( 'H*', md5( $hash . $password ) );
			} while ( --$count );
		}

		$output  = substr( $setting, 0, 12 );
		$output .= $this->_hash_encode64( $hash, 16, $itoa64 );

		return $output;
	}

	/**
	 * Encode hash
	 */
	private function _hash_encode64( $input, $count, &$itoa64 ) {
		$output = '';
		$i      = 0;

		do {
			$value   = ord( $input[ $i++ ] );
			$output .= $itoa64[ $value & 0x3f ];

			if ( $i < $count ) {
				$value |= ord( $input[ $i ] ) << 8;
			}

			$output .= $itoa64[ ( $value >> 6 ) & 0x3f ];

			if ( $i++ >= $count ) {
				break;
			}

			if ( $i < $count ) {
				$value |= ord( $input[ $i ] ) << 16;
			}

			$output .= $itoa64[ ( $value >> 12 ) & 0x3f ];

			if ( $i++ >= $count ) {
				break;
			}

			$output .= $itoa64[ ( $value >> 18 ) & 0x3f ];
		} while ( $i < $count );

		return $output;
	}

	/**
	 * Translate the forum type from phpBB v3.x numerics to WordPress's strings.
	 *
	 * @param int $status phpBB v3.x numeric forum type.
	 * @return string WordPress safe
	 */
	public function callback_forum_type( $status = 1 ) {
		switch ( $status ) {
			case 0:
				$status = 'category';
				break;

			case 1:
			default:
				$status = 'forum';
				break;
		}
		return $status;
	}

	/**
	 * Translate the forum status from phpBB v3.x numerics to WordPress's strings.
	 *
	 * @param int $status phpBB v3.x numeric forum status.
	 * @return string WordPress safe
	 */
	public function callback_forum_status( $status = 0 ) {
		switch ( $status ) {
			case 1:
				$status = 'closed';
				break;

			case 0:
			default:
				$status = 'open';
				break;
		}
		return $status;
	}

	/**
	 * Translate the topic status from phpBB v3.x numerics to WordPress's strings.
	 *
	 * @param int $status phpBB v3.x numeric topic status.
	 * @return string WordPress safe
	 */
	public function callback_topic_status( $status = 0 ) {
		switch ( $status ) {
			case 1:
				$status = 'closed';
				break;

			case 0:
			default:
				$status = 'publish';
				break;
		}
		return $status;
	}

	/**
	 * Translate the topic sticky status type from phpBB 3.x numerics to WordPress's strings.
	 *
	 * @param int $status phpBB 3.x numeric forum type.
	 * @return string WordPress safe
	 */
	public function callback_sticky_status( $status = 0 ) {
		switch ( $status ) {
			case 3:
				$status = 'super-sticky'; // phpBB Global Sticky 'topic_type = 3'.
				break;

			case 2:
				$status = 'super-sticky'; // phpBB Announcement Sticky 'topic_type = 2'.
				break;

			case 1:
				$status = 'sticky';       // phpBB Sticky 'topic_type = 1'.
				break;

			case 0:
			default:
				$status = 'normal';       // phpBB normal topic 'topic_type = 0'.
				break;
		}
		return $status;
	}

	/**
	 * Verify the topic reply count.
	 *
	 * @param int $count phpBB v3.x reply count.
	 * @return string WordPress safe
	 */
	public function callback_topic_reply_count( $count = 1 ) {
		$count = absint( (int) $count - 1 );
		return $count;
	}

	/**
	 * This callback processes any custom parser.php attributes and custom code with preg_replace.
	 */
	protected function callback_html( $field ) {

		// Strips custom phpBB 'magic_url' and 'bbcode_uid' first from $field before parsing $field to parser.php
		$phpbb_uid = $field;
		$phpbb_uid = html_entity_decode( $phpbb_uid, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401 );

		// Replace '[b:XXXXXXX]' with '<strong>'
		$phpbb_uid = preg_replace( '/\[b:(.*?)\]/', '<strong>', $phpbb_uid );
		// Replace '[/b:XXXXXXX]' with '</strong>'
		$phpbb_uid = preg_replace( '/\[\/b:(.*?)\]/', '</strong>', $phpbb_uid );

		// Replace '[i:XXXXXXX]' with '<em>'
		$phpbb_uid = preg_replace( '/\[i:(.*?)\]/', '<em>', $phpbb_uid );
		// Replace '[/i:XXXXXXX]' with '</em>'
		$phpbb_uid = preg_replace( '/\[\/i:(.*?)\]/', '</em>', $phpbb_uid );

		// Replace '[u:XXXXXXX]' with '<u>'
		$phpbb_uid = preg_replace( '/\[u:(.*?)\]/', '<u>', $phpbb_uid );
		// Replace '[/u:XXXXXXX]' with '</u>'
		$phpbb_uid = preg_replace( '/\[\/u:(.*?)\]/', '</u>', $phpbb_uid );

		// Replace '[quote:XXXXXXX]' with '<blockquote>'
		$phpbb_uid = preg_replace( '/\[quote:(.*?)\]/', '<blockquote>', $phpbb_uid );
		// Replace '[quote="$1"]' with '<em>$1 wrote:</em><blockquote>"
		$phpbb_uid = preg_replace( '/\[quote="(.*?)":(.*?)\]/', '<em>@$1 wrote:</em><blockquote>', $phpbb_uid );
		// Replace '[/quote:XXXXXXX]' with '</blockquote>'
		$phpbb_uid = preg_replace( '/\[\/quote:(.*?)\]/', '</blockquote>', $phpbb_uid );

		// Replace '[img:XXXXXXX]' with '<img src="'
		$phpbb_uid = preg_replace( '/\[img:(.*?)\]/', '<img src="', $phpbb_uid );
		// Replace '[/img:XXXXXXX]' with ' alt="">'
		$phpbb_uid = preg_replace( '/\[\/img:(.*?)\]/', '" alt="">', $phpbb_uid );

		// Replace '<!-- s$1 --><img src=\"{SMILIES_PATH}$2 -->' with '$1'
		$phpbb_uid = preg_replace( '/<!-- s(.*?) --><img src=\"{SMILIES_PATH}(.*?)-->/', '$1', $phpbb_uid );

		// Replace '<!-- m --><a class="postlink" href="$1">$1</a><!-- m -->' with '$1'
		$phpbb_uid = preg_replace( '/\<!-- m --\>\<a class="postlink" href="([^\[]+?)"\>([^\[]+?)\<\/a\>\<!-- m --\>/', '$1', $phpbb_uid );

		// Replace '[url:XXXXXXX]$1[/url:XXXXXXX]' with '<a href="http://$1">$1</a>'
		$phpbb_uid = preg_replace( '/\[url:(?:[^\]]+)\]([^\[]+?)\[\/url:(?:[^\]]+)\]/', '<a href="http://$1">$1</a>', $phpbb_uid );
		// Replace '[url=http://$1:XXXXXXX]$3[/url:XXXXXXX]' with '<a href="http://$1">$3</a>'
		$phpbb_uid = preg_replace( '/\[url\=http\:\/\/(.*?)\:(.*?)\](.*?)\[\/url:(.*?)\]/i', '<a href="http://$1">$3</a>', $phpbb_uid );
		// Replace '[url=https://$1:XXXXXXX]$3[/url:XXXXXXX]' with '<a href="http://$1">$3</a>'
		$phpbb_uid = preg_replace( '/\[url\=https\:\/\/(.*?)\:(.*?)\](.*?)\[\/url:(.*?)\]/i', '<a href="https://$1">$3</a>', $phpbb_uid );

		// Replace '[email:XXXXXXX]' with '<a href="mailto:$2">$2</a>'
		$phpbb_uid = preg_replace( '/\[email:(.*?)\](.*?)\[\/email:(.*?)\]/', '<a href="mailto:$2">$2</a>', $phpbb_uid );
		// Replace '<!-- e -->no.one@domain.adr<!-- e -->' with '$1'
		$phpbb_uid = preg_replace( '/\<!-- e --\>(.*?)\<!-- e --\>/', '$1', $phpbb_uid );

		// Replace '[code:XXXXXXX]' with '<pre><code>'
		$phpbb_uid = preg_replace( '/\[code:(.*?)\]/', '<pre><code>', $phpbb_uid );
		// Replace '[/code:XXXXXXX]' with '</code></pre>'
		$phpbb_uid = preg_replace( '/\[\/code:(.*?)\]/', '</code></pre>', $phpbb_uid );

		// Replace '[color=$1:XXXXXXXX]' with '<span style="color:$1">'
		$phpbb_uid = preg_replace( '/\[color=(.*?)\:(.*?)\]/', '<span style="color: $1">', $phpbb_uid );
		// Replace '[/color:XXXXXXX]' with '</span>'
		$phpbb_uid = preg_replace( '/\[\/color:(.*?)\]/', '</span>', $phpbb_uid );

		// Replace '[size=$1:XXXXXXXX]' with '<span style="font-size:$1%;">$3</span>'
		$phpbb_uid = preg_replace( '/\[size=(.*?):(.*?)\]/', '<span style="font-size:$1%;">', $phpbb_uid );
		// Replace '[/size:XXXXXXX]' with ''
		$phpbb_uid = preg_replace( '/\[\/size:(.*?)\]/', '</span>', $phpbb_uid );

		// Replace '[list]' with '<ul>'
		$phpbb_uid = preg_replace( '/\[list\]+/', '<ul>', $phpbb_uid );
		// Replace '[list:XXXXXXX]' with '<ul>'
		$phpbb_uid = preg_replace( '/\[list:(.*?)\]/', '<ul>', $phpbb_uid );
		// Replace '[list=a:XXXXXXX]' with '<ol type="a">'
		$phpbb_uid = preg_replace( '/\[list=a:(.*?)\]/', '<ol type="a">', $phpbb_uid );
		// Replace '[list=1:XXXXXXX]' with '<ol>'
		$phpbb_uid = preg_replace( '/\[list=1:(.*?)\]/', '<ol>', $phpbb_uid );
		// Replace '[*:XXXXXXX]' with '<li>'
		$phpbb_uid = preg_replace( '/\[\*:(.*?)\]/', '<li>', $phpbb_uid );
		// Replace '[/*:m:XXXXXXX]' with '</li>'
		$phpbb_uid = preg_replace( '/\[\/\*:m:(.*?)\]/', '</li>', $phpbb_uid );
		// Replace '[/list]' with '</ul>'
		$phpbb_uid = preg_replace( '/\[\/list\]+/', '</ul>', $phpbb_uid );
		// Replace '[/list:u:XXXXXXX]' with '</ul>'
		$phpbb_uid = preg_replace( '/\[\/list:u:(.*?)\]/', '</ul>', $phpbb_uid );
		// Replace '[/list:o:XXXXXXX]' with '</ol>'
		$phpbb_uid = preg_replace( '/\[\/list:o:(.*?)\]/', '</ol>', $phpbb_uid );

		// Replace '<s>' with ''
		$phpbb_uid = preg_replace( '/\<s\>+/', '', $phpbb_uid );
		// Replace '</s>' with ''
		$phpbb_uid = preg_replace( '/\<\/s\>+/', '', $phpbb_uid );

		// Replace '<e>' with ''
		$phpbb_uid = preg_replace( '/\<e\>+/', '', $phpbb_uid );
		// Replace '</e>' with ''
		$phpbb_uid = preg_replace( '/\<\/e\>+/', '', $phpbb_uid );

		// Replace '<LIST>' with ''
		$phpbb_uid = preg_replace( '/\<LIST\>+/', '', $phpbb_uid );
		// Replace '</LIST>' with ''
		$phpbb_uid = preg_replace( '/\<\/LIST\>+/', '', $phpbb_uid );

		// Replace '[*]' with ''
		$phpbb_uid = preg_replace( '/\[\*\]+/', '', $phpbb_uid );

		// Now that phpBB's 'magic_url' and 'bbcode_uid' have been stripped put the cleaned HTML back in $field
		$field = $phpbb_uid;

		// Parse out any bbCodes in $field with the BBCode 'parser.php'
		return parent::callback_html( $field );
	}
}
