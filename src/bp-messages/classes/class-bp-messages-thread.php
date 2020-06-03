<?php
/**
 * BuddyBoss Messages Classes.
 *
 * @package BuddyBoss\Messages\Classes
 * @since BuddyPress 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * BuddyPress Message Thread class.
 *
 * @since BuddyPress 1.0.0
 */
class BP_Messages_Thread {

	/**
	 * The message thread ID.
	 *
	 * @since BuddyPress 1.0.0
	 * @var int
	 */
	public $thread_id;

	/**
	 * The current messages.
	 *
	 * @since BuddyPress 1.0.0
	 * @var array
	 */
	public $messages;

	public $total_messages;

	/**
	 * The current recipients in the message thread.
	 *
	 * @since BuddyPress 1.0.0
	 * @var array
	 */
	public $recipients;

	/**
	 * The user IDs of all messages in the message thread.
	 *
	 * @since BuddyPress 1.2.0
	 * @var array
	 */
	public $sender_ids;

	/**
	 * The unread count for the logged-in user.
	 *
	 * @since BuddyPress 1.2.0
	 * @var int
	 */
	public $unread_count;

	/**
	 * The content of the last message in this thread.
	 *
	 * @since BuddyPress 1.2.0
	 * @var string
	 */
	public $last_message_content;

	/**
	 * The date of the last message in this thread.
	 *
	 * @since BuddyPress 1.2.0
	 * @var string
	 */
	public $last_message_date;

	/**
	 * The ID of the last message in this thread.
	 *
	 * @since BuddyPress 1.2.0
	 * @var int
	 */
	public $last_message_id;

	/**
	 * The subject of the last message in this thread.
	 *
	 * @since BuddyPress 1.2.0
	 * @var string
	 */
	public $last_message_subject;

	/**
	 * The user ID of the author of the last message in this thread.
	 *
	 * @since BuddyPress 1.2.0
	 * @var int
	 */
	public $last_sender_id;

	/**
	 * Sort order of the messages in this thread (ASC or DESC).
	 *
	 * @since BuddyPress 1.5.0
	 * @var string
	 */
	public $messages_order;

	/**
	 * Last delete message of thread
	 *
	 * @since BuddyBoss 1.0.0
	 * @var object
	 */
	public static $last_deleted_message = null;

	public static $noCache = false;

	/**
	 * Constructor.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @see BP_Messages_Thread::populate() for full description of parameters.
	 *
	 * @param bool   $thread_id ID for the message thread.
	 * @param string $order     Order to display the messages in.
	 * @param array  $args      Array of arguments for thread querying.
	 */
	public function __construct( $thread_id = false, $order = 'ASC', $args = array() ) {
		if ( $thread_id ) {
			$this->populate( $thread_id, $order, $args );
		}
	}

	/**
	 * Populate method.
	 *
	 * Used in constructor.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param int    $thread_id The message thread ID.
	 * @param string $order     The order to sort the messages. Either 'ASC' or 'DESC'.
	 * @param array  $args {
	 *     Array of arguments.
	 *     @type bool $update_meta_cache Whether to pre-fetch metadata for
	 *                                   queried message items. Default: true.
	 * }
	 * @return bool False on failure.
	 */
	public function populate( $thread_id = 0, $order = 'DESC', $args = array() ) {

		if ( 'ASC' !== $order && 'DESC' !== $order ) {
			$order = 'DESC';
		}

		$user_id =
			bp_displayed_user_id() ?
			bp_displayed_user_id() :
			bp_loggedin_user_id();

		// Merge $args with our defaults.
		$r = wp_parse_args(
			$args,
			array(
				'user_id'           => $user_id,
				'update_meta_cache' => true,
				'per_page'          => apply_filters( 'bp_messages_default_per_page', 10 ),
				'before'            => null,
			)
		);

		$this->messages_order   = $order;
		$this->messages_perpage = $r['per_page'];
		$this->messages_before  = $r['before'];
		$this->thread_id        = (int) $thread_id;

		// get the last message deleted for the thread
		static::get_user_last_deleted_message( $this->thread_id );

		// Get messages for thread.
		$this->messages       = self::get_messages( $this->thread_id, $this->messages_before, $this->messages_perpage, $this->messages_order );
		$this->last_message   = self::get_last_message( $this->thread_id );
		$this->total_messages = self::get_messages_count( $this->thread_id );

		if ( empty( $this->messages ) || is_wp_error( $this->messages ) ) {
			return false;
		}

		$this->last_message_id      = ( isset( $this->last_message ) && $this->last_message->id ) ? $this->last_message->id : '';
		$this->last_message_date    = ( isset( $this->last_message ) && $this->last_message->date_sent ) ? $this->last_message->date_sent : '';
		$this->last_sender_id       = ( isset( $this->last_message ) && $this->last_message->sender_id ) ? $this->last_message->sender_id : '';
		$this->last_message_subject = ( isset( $this->last_message ) && $this->last_message->subject ) ? $this->last_message->subject : '';
		$this->last_message_content = ( isset( $this->last_message ) && $this->last_message->message ) ? $this->last_message->message : '';

		$this->first_message_date = self::get_messages_started( $this->thread_id );

		foreach ( (array) $this->messages as $key => $message ) {
			$this->sender_ids[ $message->sender_id ] = $message->sender_id;
		}

		// Fetch the recipients.
		$this->recipients = $this->get_recipients();

		// Get the unread count for the logged in user.
		if ( isset( $this->recipients[ $r['user_id'] ] ) ) {
			$this->unread_count = $this->recipients[ $r['user_id'] ]->unread_count;
		}

		// Grab all message meta.
		if ( true === (bool) $r['update_meta_cache'] ) {
			bp_messages_update_meta_cache( wp_list_pluck( $this->messages, 'id' ) );
		}

		/**
		 * Fires after a BP_Messages_Thread object has been populated.
		 *
		 * @since BuddyPress 2.2.0
		 *
		 * @param BP_Messages_Thread $this Message thread object.
		 */
		do_action( 'bp_messages_thread_post_populate', $this );
	}

	/**
	 * Mark a thread initialized in this class as read.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @see BP_Messages_Thread::mark_as_read()
	 */
	public function mark_read() {
		self::mark_as_read( $this->thread_id );
	}

	/**
	 * Mark a thread initialized in this class as unread.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @see BP_Messages_Thread::mark_as_unread()
	 */
	public function mark_unread() {
		self::mark_as_unread( $this->thread_id );
	}

	/**
	 * Returns recipients for a message thread.
	 *
	 * @since BuddyPress 1.0.0
	 * @since BuddyPress 2.3.0 Added $thread_id as a parameter.
	 *
	 * @param int $thread_id The thread ID.
	 * @return array
	 */
	public function get_recipients( $thread_id = 0 ) {
		global $wpdb;

		if ( empty( $thread_id ) ) {
			$thread_id = $this->thread_id;
		}

		$thread_id = (int) $thread_id;

		$recipients = wp_cache_get( 'thread_recipients_' . $thread_id, 'bp_messages' );
		if ( false === $recipients ) {
			$bp = buddypress();

			$recipients = array();
			$sql        = $wpdb->prepare( "SELECT * FROM {$bp->messages->table_name_recipients} WHERE thread_id = %d", $thread_id );
			$results    = $wpdb->get_results( $sql );

			foreach ( (array) $results as $recipient ) {
				$recipients[ $recipient->user_id ] = $recipient;
			}

			wp_cache_set( 'thread_recipients_' . $thread_id, $recipients, 'bp_messages' );
		}

		// Cast all items from the messages DB table as integers.
		foreach ( (array) $recipients as $key => $data ) {
			$recipients[ $key ] = (object) array_map( 'intval', (array) $data );
		}

		/**
		 * Filters the recipients of a message thread.
		 *
		 * @since BuddyPress 2.2.0
		 *
		 * @param array $recipients Array of recipient objects.
		 * @param int   $thread_id  ID of the current thread.
		 */
		return apply_filters( 'bp_messages_thread_get_recipients', $recipients, $thread_id );
	}

	/** Static Functions ******************************************************/

	/**
	 * Check if the thread contains any deleted recipients and it's last active message
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param  int $thread_id
	 */
	public static function prepare_last_message_status( $thread_id ) {
		global $wpdb;

		$recipients = static::get_recipients_for_thread( $thread_id );

		$deleted_recipients = array_filter(
			$recipients,
			function( $recipient ) {
				return $recipient->is_deleted;
			}
		);

		return array(
			'thread_id'          => $thread_id,
			'deleted_recipients' => $deleted_recipients,
			'last_message'       => static::get_last_message( $thread_id ),
		);
	}

	/**
	 * Update the thread's deleted recipient and set the message deletion status
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param  int $thread_id
	 */
	public static function update_last_message_status( $data ) {
		extract( $data );

		if ( ! $deleted_recipients ) {
			return;
		}

		if ( ! $last_message ) {
			return;
		}

		foreach ( $deleted_recipients as $recipient ) {
			// use add to allow multiple values
			bp_messages_add_meta( $last_message->id, 'deleted_by', $recipient->user_id );
		}

		global $wpdb;
		$bp = buddypress();

		$query = $wpdb->prepare("UPDATE {$bp->messages->table_name_recipients} SET is_deleted = 0 WHERE thread_id = %d AND user_id IN (%s)", $thread_id, implode( ',', wp_list_pluck( $deleted_recipients, 'user_id' ) ) );
		$wpdb->query( $query );
	}

	/**
	 * Get a thread's last message
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param $thread_id
	 *
	 * @return object|null
	 */
	public static function get_last_message( $thread_id ) {
		global $wpdb;

		$bp = buddypress();

		$is_group_thread = self::get_first_message( $thread_id );
		if ( $is_group_thread->id > 0 ) {
			$query = $wpdb->prepare("SELECT m.* FROM {$bp->messages->table_name_messages} m, {$bp->messages->table_name_meta}  mm WHERE m.id = mm.message_id AND m.thread_id = %d  AND ( mm.meta_key != 'group_message_group_joined' OR mm.meta_key != 'group_message_group_left' ) ORDER BY m.date_sent DESC, m.id DESC LIMIT 1", $thread_id );
		} else {
			$query = $wpdb->prepare("SELECT * FROM {$bp->messages->table_name_messages} WHERE thread_id = %d ORDER BY date_sent DESC, id DESC LIMIT 1", $thread_id );
		}

		$messages = $wpdb->get_results( $query );

		return $messages ? (object) $messages[0] : null;
	}

	/**
	 * Get a thread first message
	 *
	 * @since BuddyBoss 1.2.9
	 *
	 * @param $thread_id
	 *
	 * @return object|stdClass
	 */
	public static function get_first_message( $thread_id ) {
		global $wpdb;

		$bp = buddypress();

		$query            = $wpdb->prepare( "SELECT m.* FROM {$bp->messages->table_name_messages} m, {$bp->messages->table_name_meta}  mm WHERE m.id = mm.message_id AND m.thread_id = %d  AND ( mm.meta_key != 'group_message_group_joined' OR mm.meta_key != 'group_message_group_left' ) ORDER BY m.date_sent ASC, m.id ASC LIMIT 1", $thread_id );
		$messages         = $wpdb->get_results( $query );
		$blank_object     = new stdClass();
		$blank_object->id = 0;
		return $messages ? (object) $messages[0] : $blank_object;
	}

	/**
	 * Get all messages associated with a thread.
	 *
	 * @since BuddyPress 2.3.0
	 *
	 * @param int $thread_id The message thread ID.
	 *
	 * @return object List of messages associated with a thread.
	 */
	public static function get_messages( $thread_id = 0, $before = null, $perpage = 10 ) {
		$thread_id = (int) $thread_id;
		$cache_key = "{$thread_id}{$before}{$perpage}";
		$messages  = wp_cache_get( $cache_key, 'bp_messages_threads' );

		if ( false === $messages || static::$noCache ) {
			// if current user isn't the recpient, then return empty array
			if ( ! static::is_thread_recipient( $thread_id ) ) {
				wp_cache_set( $cache_key, array(), 'bp_messages_threads' );
				return array();
			}

			global $wpdb;
			$bp                     = buddypress();
			$last_deleted_id        = static::$last_deleted_message ? static::$last_deleted_message->id : 0;
			$last_deleted_timestamp = static::$last_deleted_message ? static::$last_deleted_message->date_sent : '0000-00-00 00:00:00';

			if ( ! $before ) {
				$before = bp_core_current_time();
				// $before = gmdate( 'Y-m-d H:i:s', ( time() + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS + 1 ) ) );
			}

			$query = $wpdb->prepare( "SELECT * FROM {$bp->messages->table_name_messages} WHERE thread_id = %d AND id > %d AND date_sent > %s AND date_sent <= %s ORDER BY date_sent DESC, id DESC LIMIT %d", $thread_id, $last_deleted_id, $last_deleted_timestamp, $before, $perpage );
			// Always sort by DESC by default.
			$messages = $wpdb->get_results( $query );

			wp_cache_set( $cache_key, (array) $messages, 'bp_messages_threads' );
		}

		// Integer casting.
		foreach ( $messages as $key => $data ) {
			$messages[ $key ]->id        = (int) $messages[ $key ]->id;
			$messages[ $key ]->thread_id = (int) $messages[ $key ]->thread_id;
			$messages[ $key ]->sender_id = (int) $messages[ $key ]->sender_id;
		}

		return $messages;
	}

	/**
	 * Count the totla message in thread
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param  int $thread_id
	 */
	public static function get_messages_count( $thread_id ) {
		global $wpdb;

		$bp        = buddypress();
		$thread_id = (int) $thread_id;

		if ( ! static::is_thread_recipient( $thread_id ) ) {
			return 0;
		}

		$last_deleted_timestamp = static::$last_deleted_message ? static::$last_deleted_message->date_sent : '0000-00-00 00:00:00';

		$results = $wpdb->get_col(
			$sql = $wpdb->prepare(
				"SELECT COUNT(*) FROM {$bp->messages->table_name_messages}
			WHERE thread_id = %d
			AND date_sent > %s
		",
				$thread_id,
				$last_deleted_timestamp
			)
		);

		return intval( $results[0] );
	}

	/**
	 * Get the time of when the message is started, could be the first message
	 * or the last deleted message of the current user
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param  int $thread_id
	 */
	public static function get_messages_started( $thread_id ) {
		global $wpdb;

		$bp        = buddypress();
		$thread_id = (int) $thread_id;

		$last_deleted_timestamp = static::$last_deleted_message ? static::$last_deleted_message->date_sent : '0000-00-00 00:00:00';

		$results = $wpdb->get_col(
			$sql = $wpdb->prepare(
				"SELECT date_sent FROM {$bp->messages->table_name_messages}
			WHERE thread_id = %d
			AND date_sent > %s
			ORDER BY date_sent ASC, id ASC
			LIMIT 1
		",
				$thread_id,
				$last_deleted_timestamp
			)
		);

		return $results[0];
	}

	/**
	 * Get the user's last deleted message in thread
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param  int $thread_id
	 */
	public static function get_user_last_deleted_message( $thread_id ) {
		global $wpdb;
		$bp = buddypress();

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT m.* FROM {$bp->messages->table_name_messages} m
			LEFT JOIN {$bp->messages->table_name_meta} mm ON m.id = mm.message_id
			WHERE m.thread_id = %d
			AND (
				mm.meta_key = 'deleted_by' AND mm.meta_value = %d
			)
			ORDER BY m.date_sent DESC, m.id DESC
			LIMIT 1
		",
				$thread_id,
				bp_loggedin_user_id()
			)
		);

		if ( ! empty( $results ) ) {
			static::$last_deleted_message = (object) $results[0];
		}

		return static::$last_deleted_message;
	}

	/**
	 * Static method to get message recipients by thread ID.
	 *
	 * @since BuddyPress 2.3.0
	 *
	 * @param int $thread_id The thread ID.
	 * @return array
	 */
	public static function get_recipients_for_thread( $thread_id = 0 ) {
		$thread = new self( false );
		return $thread->get_recipients( $thread_id );
	}

	/**
	 * Check if the current user is in the thread's active recipient list
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param  int $thread_id
	 * @param  mix $user_id
	 */
	public static function is_thread_recipient( $thread_id = 0, $user_id = null ) {
		if ( ! $user_id = $user_id ?: bp_loggedin_user_id() ) {
			return true;
		}

		$recipients        = self::get_recipients_for_thread( $thread_id );
		$active_recipients = array_filter(
			$recipients,
			function ( $recipient ) {
				return ! $recipient->is_deleted;
			}
		);

		return in_array( $user_id, wp_list_pluck( $active_recipients, 'user_id' ) );
	}

	/**
	 * Mark messages in a thread as deleted or delete all messages in a thread.
	 *
	 * Note: All messages in a thread are deleted once every recipient in a thread
	 * has marked the thread as deleted.
	 *
	 * @since BuddyPress 1.0.0
	 * @since BuddyPress 2.7.0 The $user_id parameter was added. Previously the current user
	 *              was always assumed.
	 *
	 * @param int $thread_id The message thread ID.
	 * @param int $user_id The ID of the user in the thread to mark messages as
	 *                     deleted for. Defaults to the current logged-in user.
	 *
	 * @return bool
	 */
	public static function delete( $thread_id = 0, $user_id = 0 ) {
		global $wpdb;

		$thread_id = (int) $thread_id;
		$user_id   = (int) $user_id;

		if ( empty( $user_id ) ) {
			$user_id = bp_loggedin_user_id();
		}

		/**
		 * Fires before a message thread is marked as deleted.
		 *
		 * @since BuddyPress 2.2.0
		 * @since BuddyPress 2.7.0 The $user_id parameter was added.
		 *
		 * @param int $thread_id ID of the thread being deleted.
		 * @param int $user_id   ID of the user that the thread is being deleted for.
		 */
		do_action( 'bp_messages_thread_before_mark_delete', $thread_id, $user_id );

		$bp = buddypress();

		// Get the message ids in order to pass to the action.
		$message_ids = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$bp->messages->table_name_messages} WHERE thread_id = %d", $thread_id ) ); // WPCS: db call ok. // WPCS: cache ok.

		$subject_deleted_text = apply_filters( 'delete_user_message_subject_text', 'Deleted' );
		$message_deleted_text = '<p> </p>';

		// Update the message subject & content of particular user messages.
		$update_message_ids = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$bp->messages->table_name_messages} WHERE thread_id = %d AND sender_id = %d", $thread_id, $user_id ) ); // WPCS: db call ok. // WPCS: cache ok.

		/**
		 * Fires before user messages content update.
		 *
		 * @param int   $thread_id          ID of the thread being deleted.
		 * @param array $message_ids        IDs of messages being deleted.
		 * @param int   $user_id            ID of the user the threads messages update for.
		 * @param array $update_message_ids IDs of messages being updated.
		 *
		 * @since BuddyPress 2.2.0
		 */
		do_action( 'bp_messages_thread_messages_before_update', $thread_id, $message_ids, $user_id, $update_message_ids );

		foreach ( $update_message_ids as $message_id ) {
			$query = $wpdb->prepare( "UPDATE {$bp->messages->table_name_messages} SET subject= '%s', message= '%s' WHERE id = %d", $subject_deleted_text, $message_deleted_text, $message_id );
			$wpdb->query( $query ); // db call ok; no-cache ok;
			bp_messages_update_meta( $message_id, 'bp_messages_deleted', 'yes' );
		}

		/**
		 * Fires after user messages content update.
		 *
		 * @param int   $thread_id          ID of the thread being deleted.
		 * @param array $message_ids        IDs of messages being deleted.
		 * @param int   $user_id            ID of the user the threads messages update for.
		 * @param array $update_message_ids IDs of messages being updated.
		 *
		 * @since BuddyPress 2.2.0
		 */
		do_action( 'bp_messages_thread_messages_after_update', $thread_id, $message_ids, $user_id, $update_message_ids );

		// If there is no any messages in thread then delete the complete thread.
		$thread_delete = true;
		foreach ( $message_ids as $message_id ) {
			$is_deleted = bp_messages_get_meta( $message_id, 'bp_messages_deleted', true );
			if ( '' === $is_deleted ) {
				$thread_delete = false;
				break;
			}
		}

		if ( $thread_delete ) {

			/**
			 * Fires before an entire message thread is deleted.
			 *
			 * @param int   $thread_id     ID of the thread being deleted.
			 * @param array $message_ids   IDs of messages being deleted.
			 * @param bool  $thread_delete True entire thread will be deleted.
			 *
			 * @since BuddyPress 2.2.0
			 */
			do_action( 'bp_messages_thread_before_delete', $thread_id, $message_ids, $thread_delete );

			// Removed the thread id from the group meta.
			if ( bp_is_active( 'groups' ) && function_exists( 'bp_disable_group_messages' ) &&  true === bp_disable_group_messages() ) {
				// Get the group id from the first message.
				$first_message    = BP_Messages_Thread::get_first_message( (int) $thread_id );
				$message_group_id = (int) bp_messages_get_meta( $first_message->id, 'group_id', true ); // group id
				if ( $message_group_id > 0 ) {
					$group_thread = (int) groups_get_groupmeta( $message_group_id, 'group_message_thread' );
					if ( $group_thread > 0 && $group_thread === (int) $thread_id ) {
						groups_update_groupmeta( $message_group_id, 'group_message_thread', '' );
					}
				}
			}

			// Delete Message Notifications.
			bp_messages_message_delete_notifications( $thread_id, $message_ids );

			// Delete thread messages.
			$query = $wpdb->prepare( "DELETE FROM {$bp->messages->table_name_messages} WHERE thread_id = %d", $thread_id );
			$wpdb->query( $query ); // db call ok; no-cache ok;

			// Delete messages meta.
			$query = $wpdb->prepare( "DELETE FROM {$bp->messages->table_name_meta} WHERE message_id IN(%s)", implode(',', $message_ids ) );
			$wpdb->query( $query ); // db call ok; no-cache ok;

			// Delete thread.
			$query = $wpdb->prepare( "DELETE FROM {$bp->messages->table_name_recipients} WHERE thread_id = %d", $thread_id );
			$wpdb->query( $query ); // db call ok; no-cache ok;

			/**
			 * Fires before an entire message thread is deleted.
			 *
			 * @param int   $thread_id     ID of the thread being deleted.
			 * @param array $message_ids   IDs of messages being deleted.
			 * @param int   $user_id       ID of the user the threads were deleted for.
			 * @param bool  $thread_delete True entire thread will be deleted.
			 *
			 * @since BuddyPress 2.2.0
			 */
			do_action( 'bp_messages_thread_after_delete', $thread_id, $message_ids, $user_id, $thread_delete );

		}

		return true;
	}

	/**
	 * Get current message threads for a user.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param array $args {
	 *     Array of arguments.
	 *     @type int    $user_id      The user ID.
	 *     @type string $box          The type of mailbox to get. Either 'inbox' or 'sentbox'.
	 *                                Defaults to 'inbox'.
	 *     @type string $type         The type of messages to get. Either 'all' or 'unread'
	 *                                or 'read'. Defaults to 'all'.
	 *     @type int    $limit        The number of messages to get. Defaults to null.
	 *     @type int    $page         The page number to get. Defaults to null.
	 *     @type string $search_terms The search term to use. Defaults to ''.
	 *     @type array  $meta_query   Meta query arguments. See WP_Meta_Query for more details.
	 * }
	 * @return array|bool Array on success. Boolean false on failure.
	 */
	public static function get_current_threads_for_user( $args = array() ) {
		global $wpdb;

		$bp = buddypress();

		// Backward compatibility with old method of passing arguments.
		if ( ! is_array( $args ) || func_num_args() > 1 ) {
			_deprecated_argument( __METHOD__, '2.2.0', sprintf( __( 'Arguments passed to %1$s should be in an associative array. See the inline documentation at %2$s for more details.', 'buddyboss' ), __METHOD__, __FILE__ ) );

			$old_args_keys = array(
				0 => 'user_id',
				1 => 'box',
				2 => 'type',
				3 => 'limit',
				4 => 'page',
				5 => 'search_terms',
			);

			$args = bp_core_parse_args_array( $old_args_keys, func_get_args() );
		}

		$r = bp_parse_args(
			$args,
			array(
				'user_id'      => false,
				'box'          => 'inbox',
				'type'         => 'all',
				'limit'        => null,
				'page'         => null,
				'search_terms' => '',
				'include'      => false,
				'is_hidden'    => false,
				'meta_query'   => array(),
			)
		);

		$pag_sql                       = $type_sql = $search_sql = $user_id_sql = $sender_sql = $having_sql = '';
		$current_user_participants_ids = array();
		$meta_query_sql                = array(
			'join'  => '',
			'where' => '',
		);

		if ( $r['limit'] && $r['page'] ) {
			$pag_sql = $wpdb->prepare( ' LIMIT %d, %d', intval( ( $r['page'] - 1 ) * $r['limit'] ), intval( $r['limit'] ) );
		}

		$r['user_id'] = (int) $r['user_id'];
		$where_sql    = '1 = 1';

		if ( ! empty( $r['include'] ) ) {
			$user_threads_query = $r['include'];
		} else {

			// Do not include hidden threads.
			if ( false === $r['is_hidden'] && '' === $r['search_terms'] ) {
				$user_threads_query = $wpdb->prepare(
					"
			SELECT DISTINCT(thread_id)
			FROM {$bp->messages->table_name_recipients}
			WHERE user_id = %d
			AND is_deleted = 0
			AND is_hidden = 0
		",
					$r['user_id']
				);
			} else {
				$user_threads_query = $wpdb->prepare(
					"
			SELECT DISTINCT(thread_id)
			FROM {$bp->messages->table_name_recipients}
			WHERE user_id = %d
			AND is_deleted = 0
		",
					$r['user_id']
				);
			}

		}

		$group_thread_in = array();
		if ( ! empty( $r['search_terms'] ) ) {

			// Search in xprofile field.
			if (  bp_is_active( 'xprofile' ) ) {
				// Explode the value if there is a space in search term.
				$split_name = explode( ' ', $r['search_terms'] );

				// If space found then add spd.value 2 times in {$bp->profile->table_name_data} table due to first & last name.
				if ( isset( $split_name ) && isset( $split_name[0] ) && isset( $split_name[1] ) && !empty( $split_name ) && !empty( trim( $split_name[0] ) ) && !empty( trim( $split_name[1] ) ) ) {
					$search_terms_like = '%' . bp_esc_like( $r['search_terms'] ) . '%';
					$where_sql         = $wpdb->prepare( 'm.message LIKE %s', $search_terms_like );

					$current_user_participants = $wpdb->get_results(
						$q                     = $wpdb->prepare(
							"
				SELECT DISTINCT(r.user_id), u.display_name, spd.value
				FROM {$bp->messages->table_name_recipients} r
				LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID
				LEFT JOIN {$bp->profile->table_name_data} spd ON r.user_id = spd.user_id
				WHERE r.thread_id IN ($user_threads_query) AND
				( u.display_name LIKE %s OR u.user_login LIKE %s OR u.user_nicename LIKE %s OR spd.value LIKE %s OR spd.value LIKE %s )
			",
							$search_terms_like,
							$search_terms_like,
							$search_terms_like,
							$split_name[0],
							$split_name[1]
						)
					);
				// else single search without space in search_terms
				} else {
					$search_terms_like = '%' . bp_esc_like( $r['search_terms'] ) . '%';
					$where_sql         = $wpdb->prepare( 'm.message LIKE %s', $search_terms_like );

					$current_user_participants = $wpdb->get_results(
						$q                     = $wpdb->prepare(
							"
				SELECT DISTINCT(r.user_id), u.display_name, spd.value
				FROM {$bp->messages->table_name_recipients} r
				LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID
				LEFT JOIN {$bp->profile->table_name_data} spd ON r.user_id = spd.user_id
				WHERE r.thread_id IN ($user_threads_query) AND
				( u.display_name LIKE %s OR u.user_login LIKE %s OR u.user_nicename LIKE %s OR spd.value LIKE %s )
			",
							$search_terms_like,
							$search_terms_like,
							$search_terms_like,
							$search_terms_like
						)
					);
				}
			// Default search if xprofile not active.
			} else {
				$search_terms_like = '%' . bp_esc_like( $r['search_terms'] ) . '%';
				$where_sql         = $wpdb->prepare( 'm.message LIKE %s', $search_terms_like );

				$current_user_participants = $wpdb->get_results(
					$q                     = $wpdb->prepare(
						"
				SELECT DISTINCT(r.user_id), u.display_name
				FROM {$bp->messages->table_name_recipients} r
				LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID
				WHERE r.thread_id IN ($user_threads_query) AND
				( u.display_name LIKE %s OR u.user_login LIKE %s OR u.user_nicename LIKE %s )
			",
						$search_terms_like,
						$search_terms_like,
						$search_terms_like
					)
				);
			}

			$current_user_participants_ids = array_map( 'intval', wp_list_pluck( $current_user_participants, 'user_id' ) );
			$current_user_participants_ids = array_diff( $current_user_participants_ids, array( bp_loggedin_user_id() ) );

			// Search Group Thread via Group Name via search_terms
			$search_terms_like = '%' . bp_esc_like( $r['search_terms'] ) . '%';
			$groups            = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$bp->groups->table_name} g WHERE g.name LIKE %s", $search_terms_like ) );
			$group_creator_ids = array_map( 'intval', wp_list_pluck( $groups, 'creator_id' ) );

			// If Group Found
			if ( !empty( $group_creator_ids ) ) {
				if ( is_array( $current_user_participants_ids ) ) {
					$current_user_participants_ids = array_merge( $current_user_participants_ids, $group_creator_ids );
				} else {
					$current_user_participants_ids = $group_creator_ids;
				}
			}

			// Search for deleted Group OR Deleted Users.
			$value = "(deleted)|(group)|(Deleted)|(group)|(user)|(User)|(del)|(Del)|(dele)|(Dele)|(dele)|(Dele)|(delet)|(Delet)|(use)|(Use)";
			if ( preg_match_all( '/\b' . $value . '\b/i', $r['search_terms'], $dest ) ) {

				// For deleted users.
				$current_user_participants = $wpdb->get_results( 'SELECT DISTINCT user_id FROM ' . $bp->messages->table_name_recipients . ' WHERE user_id NOT IN (SELECT ID FROM ' . $wpdb->users . ')');
				if ( !empty( $current_user_participants ) ) {
					$deleted_user_ids = array_map( 'intval', wp_list_pluck( $current_user_participants, 'user_id' ) );
					if ( is_array( $current_user_participants_ids ) ) {
						$current_user_participants_ids = array_merge( $current_user_participants_ids, $deleted_user_ids );
					} else {
						$current_user_participants_ids = $deleted_user_ids;
					}
				}

				// For deleted groups fetch all thread first.
				$threads    = $wpdb->get_results("SELECT DISTINCT thread_id FROM {$bp->messages->table_name_recipients} " );
				$thread_ids = array_map( 'intval', wp_list_pluck( $threads, 'thread_id' ) );

				// If Group Found
				if ( !empty( $thread_ids ) ) {
					foreach ( $thread_ids as $thread ) {
						// Get the group id from the first message
						$first_message    = BP_Messages_Thread::get_first_message( $thread );
						$message_group_id = (int) bp_messages_get_meta( $first_message->id, 'group_id', true ); // group id
						if ( $message_group_id ) {
							if ( bp_is_active( 'groups' ) ) {
								$group_name = bp_get_group_name( groups_get_group( $message_group_id ) );
							} else {
								$group_name = $wpdb->get_var( "SELECT name FROM {$groups_table} WHERE id = '{$message_group_id}';" ); // db call ok; no-cache ok;
							}
							if ( empty( $group_name ) ) {
								$group_thread_in[] = $thread;
							}
						}
					}
				}

			}

			if ( $current_user_participants_ids ) {
				$user_ids  = implode( ',', array_unique( $current_user_participants_ids ) );
				if ( !empty( $group_thread_in ) ) {
					$thread_in = implode( ',', $group_thread_in );
					$where_sql = $wpdb->prepare(
						"
					(m.message LIKE %s OR r.user_id IN ({$user_ids}) OR r.thread_id IN ({$thread_in}) )
				",
						$search_terms_like
					);
				} else {
					$where_sql = $wpdb->prepare(
						"
					(m.message LIKE %s OR r.user_id IN ({$user_ids}))
				",
						$search_terms_like
					);
				}

			}
		}

		$where_sql .= " AND r.thread_id IN ($user_threads_query)";

		// Process meta query into SQL.
		$meta_query = self::get_meta_query_sql( $r['meta_query'] );
		if ( ! empty( $meta_query['join'] ) ) {
			$meta_query_sql['join'] = $meta_query['join'];
		}
		if ( ! empty( $meta_query['where'] ) ) {
			$meta_query_sql['where'] = $meta_query['where'];
		}

		// Set up SQL array.
		$sql           = array();
		$sql['select'] = 'SELECT m.thread_id, MAX(m.date_sent) AS date_sent, GROUP_CONCAT(DISTINCT r.user_id ORDER BY r.user_id separator \',\' ) as recipient_list';
		$sql['from']   = "FROM {$bp->messages->table_name_recipients} r INNER JOIN {$bp->messages->table_name_messages} m ON m.thread_id = r.thread_id {$meta_query_sql['join']}";
		$sql['where']  = "WHERE {$where_sql} {$meta_query_sql['where']}";
		$sql['misc']   = "GROUP BY m.thread_id {$having_sql} ORDER BY date_sent DESC {$pag_sql}";

		// Get thread IDs.
		$thread_ids = $wpdb->get_results( $qq = implode( ' ', $sql ) );
		// print_r($qq);die();
		if ( empty( $thread_ids ) ) {
			return false;
		}

		// Adjust $sql to work for thread total.
		$sql['select'] = 'SELECT COUNT( DISTINCT m.thread_id )';
		unset( $sql['misc'] );
		$total_threads = $wpdb->get_var( implode( ' ', $sql ) );

		// Sort threads by date_sent.
		foreach ( (array) $thread_ids as $thread ) {
			$sorted_threads[ $thread->thread_id ] = strtotime( $thread->date_sent );
		}

		arsort( $sorted_threads );

		$threads = array();
		foreach ( (array) $sorted_threads as $thread_id => $date_sent ) {
			$threads[] = new BP_Messages_Thread(
				$thread_id,
				'ASC',
				array(
					'update_meta_cache' => false,
				)
			);
		}

		/**
		 * Filters the results of the query for a user's message threads.
		 *
		 * @since BuddyPress 2.2.0
		 *
		 * @param array $value {
		 *     @type array $threads       Array of threads. Passed by reference.
		 *     @type int   $total_threads Number of threads found by the query.
		 * }
		 */
		return apply_filters(
			'bp_messages_thread_current_threads',
			array(
				'threads' => &$threads,
				'total'   => (int) $total_threads,
			)
		);
	}

	/**
	 * Get the SQL for the 'meta_query' param in BP_Messages_Thread::get_current_threads_for_user().
	 *
	 * We use WP_Meta_Query to do the heavy lifting of parsing the meta_query array
	 * and creating the necessary SQL clauses.
	 *
	 * @since BuddyPress 2.2.0
	 *
	 * @param array $meta_query An array of meta_query filters. See the
	 *                          documentation for WP_Meta_Query for details.
	 * @return array $sql_array 'join' and 'where' clauses.
	 */
	public static function get_meta_query_sql( $meta_query = array() ) {
		global $wpdb;

		$sql_array = array(
			'join'  => '',
			'where' => '',
		);

		if ( ! empty( $meta_query ) ) {
			$meta_query = new WP_Meta_Query( $meta_query );

			// WP_Meta_Query expects the table name at
			// $wpdb->messagemeta.
			$wpdb->messagemeta = buddypress()->messages->table_name_meta;

			return $meta_query->get_sql( 'message', 'm', 'id' );
		}

		return $sql_array;
	}

	/**
	 * Mark a thread as read.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param int $thread_id The message thread ID.
	 *
	 * @return false|int Number of threads marked as read or false on error.
	 */
	public static function mark_as_read( $thread_id = 0 ) {
		global $wpdb;

		$user_id =
			bp_displayed_user_id() ?
			bp_displayed_user_id() :
			bp_loggedin_user_id();

		$bp     = buddypress();
		$retval = $wpdb->query( $wpdb->prepare( "UPDATE {$bp->messages->table_name_recipients} SET unread_count = 0 WHERE user_id = %d AND thread_id = %d", $user_id, $thread_id ) );

		wp_cache_delete( 'thread_recipients_' . $thread_id, 'bp_messages' );
		wp_cache_delete( $user_id, 'bp_messages_unread_count' );

		/**
		 * Fires when messages thread was marked as read.
		 *
		 * @since BuddyPress 2.8.0
		 *
		 * @param int $thread_id The message thread ID.
		 */
		do_action( 'messages_thread_mark_as_read', $thread_id );

		return $retval;
	}

	/**
	 * Mark a thread as unread.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param int $thread_id The message thread ID.
	 *
	 * @return false|int Number of threads marked as unread or false on error.
	 */
	public static function mark_as_unread( $thread_id = 0 ) {
		global $wpdb;

		$user_id =
			bp_displayed_user_id() ?
			bp_displayed_user_id() :
			bp_loggedin_user_id();

		$bp     = buddypress();
		$retval = $wpdb->query( $wpdb->prepare( "UPDATE {$bp->messages->table_name_recipients} SET unread_count = 1 WHERE user_id = %d AND thread_id = %d", $user_id, $thread_id ) );

		wp_cache_delete( 'thread_recipients_' . $thread_id, 'bp_messages' );
		wp_cache_delete( $user_id, 'bp_messages_unread_count' );

		/**
		 * Fires when messages thread was marked as unread.
		 *
		 * @since BuddyPress 2.8.0
		 *
		 * @param int $thread_id The message thread ID.
		 */
		do_action( 'messages_thread_mark_as_unread', $thread_id );

		return $retval;
	}

	/**
	 * Returns the total number of message threads for a user.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param int    $user_id The user ID.
	 * @param string $box     The type of mailbox to get. Either 'inbox' or 'sentbox'.
	 *                        Defaults to 'inbox'.
	 * @param string $type    The type of messages to get. Either 'all' or 'unread'.
	 *                        or 'read'. Defaults to 'all'.
	 * @return int $value Total thread count for the provided user.
	 */
	public static function get_total_threads_for_user( $user_id, $box = 'inbox', $type = 'all' ) {
		global $wpdb;

		$exclude_sender = $type_sql = '';
		// $exclude_sender = 'AND sender_only != 1';

		if ( $type === 'unread' ) {
			$type_sql = 'AND unread_count != 0';
		} elseif ( $type === 'read' ) {
			$type_sql = 'AND unread_count = 0';
		}

		$bp = buddypress();

		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(thread_id) FROM {$bp->messages->table_name_recipients} WHERE user_id = %d AND is_deleted = 0 {$exclude_sender} {$type_sql}", $user_id ) );
	}

	/**
	 * Determine if the logged-in user is a sender of any message in a thread.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param int $thread_id The message thread ID.
	 * @return bool
	 */
	public static function user_is_sender( $thread_id ) {
		global $wpdb;

		$bp = buddypress();

		$sender_ids = $wpdb->get_col( $wpdb->prepare( "SELECT sender_id FROM {$bp->messages->table_name_messages} WHERE thread_id = %d", $thread_id ) );

		if ( empty( $sender_ids ) ) {
			return false;
		}

		return in_array( bp_loggedin_user_id(), $sender_ids );
	}

	/**
	 * Returns the userlink of the last sender in a message thread.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param int $thread_id The message thread ID.
	 * @return string|bool The user link on success. Boolean false on failure.
	 */
	public static function get_last_sender( $thread_id ) {
		global $wpdb;

		$bp = buddypress();

		if ( ! $sender_id = $wpdb->get_var( $wpdb->prepare( "SELECT sender_id FROM {$bp->messages->table_name_messages} WHERE thread_id = %d GROUP BY sender_id ORDER BY date_sent LIMIT 1", $thread_id ) ) ) {
			return false;
		}

		return bp_core_get_userlink( $sender_id, true );
	}

	/**
	 * Gets the unread message count for a user.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param int $user_id The user ID.
	 * @return int $unread_count Total inbox unread count for user.
	 */
	public static function get_inbox_count( $user_id = 0 ) {
		global $wpdb;

		if ( empty( $user_id ) ) {
			$user_id = bp_loggedin_user_id();
		}

		$unread_count = wp_cache_get( $user_id, 'bp_messages_unread_count' );

		if ( false === $unread_count ) {
			$bp = buddypress();

			// $unread_count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT SUM(unread_count) FROM {$bp->messages->table_name_recipients} WHERE user_id = %d AND is_deleted = 0 AND sender_only = 0", $user_id ) );
			$unread_count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT SUM(unread_count) FROM {$bp->messages->table_name_recipients} WHERE user_id = %d AND is_deleted = 0", $user_id ) ); // WPCS: db call ok.

			wp_cache_set( $user_id, $unread_count, 'bp_messages_unread_count' );
		}

		/**
		 * Filters a user's unread message count.
		 *
		 * @since BuddyPress 2.2.0
		 *
		 * @param int $unread_count Unread message count.
		 * @param int $user_id      ID of the user.
		 */
		return apply_filters( 'messages_thread_get_inbox_count', (int) $unread_count, $user_id );
	}

	/**
	 * Checks whether a user is a part of a message thread discussion.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param int $thread_id The message thread ID.
	 * @param int $user_id   The user ID.
	 * @return int|null The recorded recipient ID on success, null on failure.
	 */
	public static function check_access( $thread_id, $user_id = 0 ) {

		if ( empty( $user_id ) ) {
			$user_id = bp_loggedin_user_id();
		}

		$recipients = self::get_recipients_for_thread( $thread_id );

		if ( isset( $recipients[ $user_id ] ) && 0 == $recipients[ $user_id ]->is_deleted ) {
			return $recipients[ $user_id ]->id;
		} else {
			return null;
		}
	}

	/**
	 * Checks whether a message thread exists.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param int $thread_id The message thread ID.
	 * @return false|int|null The message thread ID on success, null on failure.
	 */
	public static function is_valid( $thread_id = 0 ) {

		// Bail if no thread ID is passed.
		if ( empty( $thread_id ) ) {
			return false;
		}

		$thread = self::get_messages( $thread_id );

		if ( ! empty( $thread ) ) {
			return $thread_id;
		} else {
			return null;
		}
	}

	/**
	 * Returns a string containing all the message recipient userlinks.
	 *
	 * String is comma-delimited.
	 *
	 * If a message thread has more than four users, the returned string is simply
	 * "X Recipients" where "X" is the number of recipients in the message thread.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param array $recipients Array containing the message recipients (array of objects).
	 * @return string $value String of message recipent userlinks.
	 */
	public static function get_recipient_links( $recipients ) {

		if ( count( $recipients ) >= 5 ) {
			return sprintf( __( '%s Recipients', 'buddyboss' ), number_format_i18n( count( $recipients ) ) );
		}

		$recipient_links = array();

		foreach ( (array) $recipients as $recipient ) {
			$recipient_link = bp_core_get_userlink( $recipient->user_id );

			if ( empty( $recipient_link ) ) {
				$recipient_link = __( 'Deleted User', 'buddyboss' );
			}

			$recipient_links[] = $recipient_link;
		}

		return implode( ', ', (array) $recipient_links );
	}

	/**
	 * Upgrade method for the older BP message thread DB table.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @todo We should remove this.  No one is going to upgrade from v1.1, right?
	 * @return bool
	 */
	public static function update_tables() {
		global $wpdb;

		$bp_prefix = bp_core_get_table_prefix();
		$errors    = false;
		$threads   = $wpdb->get_results( "SELECT * FROM {$bp_prefix}bp_messages_threads" );

		// Nothing to update, just return true to remove the table.
		if ( empty( $threads ) ) {
			return true;
		}

		$bp = buddypress();

		foreach ( (array) $threads as $thread ) {
			$message_ids = maybe_unserialize( $thread->message_ids );

			if ( ! empty( $message_ids ) ) {
				$message_ids = implode( ',', $message_ids );

				// Add the thread_id to the messages table.
				if ( ! $wpdb->query( $wpdb->prepare( "UPDATE {$bp->messages->table_name_messages} SET thread_id = %d WHERE id IN ({$message_ids})", $thread->id ) ) ) {
					$errors = true;
				}
			}
		}

		return (bool) ! $errors;
	}
}
