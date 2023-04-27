<?php
/**
 * BuddyBoss Messages Classes.
 *
 * @package BuddyBoss\Messages\Classes
 * @since   BuddyPress 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * BuddyPress Message Thread class.
 *
 * @since BuddyPress 1.0.0
 */
#[\AllowDynamicProperties]
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
	 * @param bool   $thread_id ID for the message thread.
	 * @param string $order     Order to display the messages in.
	 * @param array  $args      Array of arguments for thread querying.
	 *
	 * @see   BP_Messages_Thread::populate() for full description of parameters.
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
	 * @param int    $thread_id         The message thread ID.
	 * @param string $order             The order to sort the messages. Either 'ASC' or 'DESC'.
	 * @param array  $args              {
	 *                                  Array of arguments.
	 *
	 * @type bool    $update_meta_cache Whether to pre-fetch metadata for
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
		$r                      = bp_parse_args(
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

		// get the last message deleted for the thread.
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
		$args['per_page'] = bb_messages_recipients_per_page();
		// Fetch the recipients.
		$this->recipients = $this->get_pagination_recipients( $this->thread_id, $args );

		// Get the unread count for the logged in user.
		$this->unread_count = bb_get_thread_messages_unread_count( $this->thread_id, $r['user_id'] );

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
	 * @see   BP_Messages_Thread::mark_as_read()
	 */
	public function mark_read() {
		self::mark_as_read( $this->thread_id );
	}

	/**
	 * Mark a thread initialized in this class as unread.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @see   BP_Messages_Thread::mark_as_unread()
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
	 *
	 * @return array
	 */
	public function get_recipients( $thread_id = 0 ) {
		if ( empty( $thread_id ) ) {
			$thread_id = $this->thread_id;
		}

		$thread_id = (int) $thread_id;

		$recipients = wp_cache_get( 'thread_recipients_' . $thread_id, 'bp_messages' );

		if ( false === $recipients ) {

			$recipients = array();

			$results = self::get(
				array(
					'per_page'        => - 1,
					'include_threads' => array( $thread_id ),
				)
			);

			if ( ! empty( $results['recipients'] ) ) {
				foreach ( (array) $results['recipients'] as $recipient ) {
					$recipients[ $recipient->user_id ] = $recipient;
				}

				wp_cache_set( 'thread_recipients_' . $thread_id, $recipients, 'bp_messages' );
			}
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
	 * @param int $thread_id The thread ID.
	 *
	 * @return array
	 */
	public static function prepare_last_message_status( $thread_id ) {
		global $wpdb;

		$recipients = static::get_recipients_for_thread( $thread_id );

		$deleted_recipients = array_filter(
			$recipients,
			function ( $recipient ) {
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
	 * @param int $thread_id
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
			// use add to allow multiple values.
			bp_messages_add_meta( $last_message->id, 'deleted_by', $recipient->user_id );
		}

		global $wpdb;
		$bp = buddypress();

		$query = $wpdb->prepare( "UPDATE {$bp->messages->table_name_recipients} SET is_deleted = 0 WHERE thread_id = %d AND user_id IN (%s)", $thread_id, implode( ',', wp_list_pluck( $deleted_recipients, 'user_id' ) ) );
		$wpdb->query( $query );
	}

	/**
	 * Get a thread's last message
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param int $thread_id Message thread ID.
	 *
	 * @return object|null
	 */
	public static function get_last_message( $thread_id, $include_join_left_message = false ) {

		if ( empty( $thread_id ) ) {
			return null;
		}

		$is_group_thread  = self::get_first_message( $thread_id );
		$message_group_id = (int) bp_messages_get_meta( $is_group_thread->id, 'group_id', true ); // group id.

		$args = array(
			'include_threads' => $thread_id,
			'meta_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'relation' => 'AND',
				array(
					'key'     => 'bp_messages_deleted',
					'compare' => 'NOT EXISTS',
				),
			),
			'per_page'        => 1,
			'page'            => 1,
			'count_total'     => false,
		);

		if ( $message_group_id > 0 && false === $include_join_left_message ) {
			$args['meta_query'][] = array(
				'key'     => 'group_message_group_joined',
				'compare' => 'NOT EXISTS',
			);
			$args['meta_query'][] = array(
				'key'     => 'group_message_group_left',
				'compare' => 'NOT EXISTS',
			);
		}

		$messages = BP_Messages_Message::get( $args );

		return ( ! empty( $messages['messages'] ) ? (object) current( $messages['messages'] ) : null );
	}

	/**
	 * Get a thread first message
	 *
	 * @since BuddyBoss 1.2.9
	 *
	 * @param int $thread_id Message thread ID.
	 *
	 * @return object|stdClass
	 */
	public static function get_first_message( $thread_id ) {
		$messages = BP_Messages_Message::get(
			array(
				'include_threads'  => $thread_id,
				'meta_key__not_in' => array(
					'group_message_group_joined',
					'group_message_group_left',
				),
				'order'            => 'ASC',
				'per_page'         => 1,
				'page'             => 1,
				'count_total'      => false,
			)
		);

		$blank_object     = new stdClass();
		$blank_object->id = 0;

		return ( ! empty( $thread_id ) && ! empty( $messages['messages'] ) ? (object) current( $messages['messages'] ) : $blank_object );
	}

	/**
	 * Get all messages associated with a thread.
	 *
	 * @since BuddyPress 2.3.0
	 *
	 * @param int  $thread_id The message thread ID.
	 * @param null $before    Messages to get before a specific date.
	 * @param int  $perpage   Number of messages to retrieve.
	 *
	 * @return object List of messages associated with a thread.
	 */
	public static function get_messages( $thread_id = 0, $before = null, $perpage = 10 ) {
		$thread_id = (int) $thread_id;
		$cache_key = "{$thread_id}{$before}{$perpage}";
		$messages  = wp_cache_get( $cache_key, 'bp_messages_threads' );

		if ( false === $messages || static::$noCache ) {
			// if current user isn't the recpient, then return empty array.
			if ( ! static::is_thread_recipient( $thread_id ) && ! bp_current_user_can( 'bp_moderate' ) ) {
				wp_cache_set( $cache_key, array(), 'bp_messages_threads' );

				return array();
			}

			$last_deleted_timestamp = static::$last_deleted_message ? static::$last_deleted_message->date_sent : '0000-00-00 00:00';

			if ( ! $before ) {
				$before = bp_core_current_time();
				// $before = gmdate( 'Y-m-d H:i:s', ( time() + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS + 1 ) ) );
			}

			// Added last_deleted_id in the sql.
			add_filter( 'bp_messages_message_get_where_conditions', array( __CLASS__, 'bp_filter_messages_message_get_where_conditions' ), 10, 2 );

			$query = BP_Messages_Message::get(
				array(
					'include_threads' => $thread_id,
					'date_query'      => array(
						'after'     => $last_deleted_timestamp,
						'before'    => $before,
						'inclusive' => true,
					),
					'per_page'        => $perpage,
				)
			);

			// Removed last_deleted_id in the sql.
			remove_filter( 'bp_messages_message_get_where_conditions', array( __CLASS__, 'bp_filter_messages_message_get_where_conditions' ), 10, 2 );

			$messages = ( ! empty( $query['messages'] ) ? $query['messages'] : array() );

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
	 * Count the total message in thread
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param int $thread_id The message thread ID.
	 *
	 * @return int
	 */
	public static function get_messages_count( $thread_id ) {
		$thread_id = (int) $thread_id;

		if ( ! static::is_thread_recipient( $thread_id ) ) {
			return 0;
		}

		$last_deleted_timestamp = static::$last_deleted_message ? static::$last_deleted_message->date_sent : '0000-00-00 00:00';

		$results = BP_Messages_Message::get(
			array(
				'fields'          => 'ids',
				'per_page'        => '1',
				'include_threads' => $thread_id,
				'date_query'      => array(
					array(
						'after'     => $last_deleted_timestamp,
						'inclusive' => true,
					),
				),
				'count_total'     => true,
			)
		);

		return ( ! empty( $results['total'] ) ? intval( $results['total'] ) : 0 );
	}

	/**
	 * Get the time of when the message is started, could be the first message
	 * or the last deleted message of the current user
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param int $thread_id The message thread ID.
	 *
	 * @return string
	 */
	public static function get_messages_started( $thread_id ) {
		$thread_id = (int) $thread_id;

		$last_deleted_timestamp = static::$last_deleted_message ? static::$last_deleted_message->date_sent : '0000-00-00 00:00';

		$results = BP_Messages_Message::get(
			array(
				'per_page'        => 1,
				'include_threads' => $thread_id,
				'date_query'      => array(
					array(
						'after'     => $last_deleted_timestamp,
						'inclusive' => true,
					),
				),
				'order'           => 'asc',
			)
		);

		$results = ! empty( $results['messages'] ) ? current( $results['messages'] ) : false;

		return isset( $results->date_sent ) ? $results->date_sent : '';
	}

	/**
	 * Get the user's last deleted message in thread
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param int $thread_id The message thread ID.
	 *
	 * @return null|object
	 */
	public static function get_user_last_deleted_message( $thread_id ) {
		$results = BP_Messages_Message::get(
			array(
				'include_threads' => $thread_id,
				'meta_query'      => array(
					array(
						'key'   => 'deleted_by',
						'value' => bp_loggedin_user_id(),
					),
				),
				'per_page'        => 1,
			)
		);

		if ( ! empty( $results['messages'] ) ) {
			static::$last_deleted_message = (object) current( $results['messages'] );
		}

		return static::$last_deleted_message;
	}

	/**
	 * Static method to get message recipients by thread ID.
	 *
	 * @since BuddyPress 2.3.0
	 *
	 * @param int $thread_id The thread ID.
	 *
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
	 * @param int $thread_id The message thread ID.
	 * @param int $user_id   The ID of the user in the thread.
	 *
	 * @return bool
	 */
	public static function is_thread_recipient( $thread_id = 0, $user_id = 0 ) {
		$user_id = $user_id ? $user_id : bp_loggedin_user_id();
		if ( ! $user_id ) {
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
	 * @param int $user_id   The ID of the user in the thread to mark messages as
	 *                       deleted for. Defaults to the current logged-in user.
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
		$messages = BP_Messages_Message::get(
			array(
				'fields'          => 'ids',
				'include_threads' => array( $thread_id ),
				'per_page'        => - 1,
				'orderby'         => 'id',
			)
		);

		$message_ids = ( isset( $messages['messages'] ) ) ? $messages['messages'] : array();

		$subject_deleted_text = apply_filters( 'delete_user_message_subject_text', '' );
		$message_deleted_text = '';

		// Update the message subject & content of particular user messages.
		$update_messages    = BP_Messages_Message::get(
			array(
				'fields'          => 'ids',
				'include_threads' => array( $thread_id ),
				'user_id'         => $user_id,
				'orderby'         => 'id',
				'per_page'        => - 1,
			)
		);
		$update_message_ids = ( isset( $update_messages['messages'] ) && is_array( $update_messages['messages'] ) ) ? $update_messages['messages'] : array();

		/**
		 * Fires before user messages content update.
		 *
		 * @since BuddyPress 2.2.0
		 *
		 * @param array $message_ids        IDs of messages being deleted.
		 * @param int   $user_id            ID of the user the threads messages update for.
		 * @param array $update_message_ids IDs of messages being updated.
		 *
		 * @param int   $thread_id          ID of the thread being deleted.
		 */
		do_action( 'bp_messages_thread_messages_before_update', $thread_id, $message_ids, $user_id, $update_message_ids );

		if ( ! empty( $update_message_ids ) ) {
			foreach ( $update_message_ids as $message_id ) {
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.QuotedSimplePlaceholder
				$query = $wpdb->prepare( "UPDATE {$bp->messages->table_name_messages} SET message = '%s', subject = '%s', is_deleted = %d WHERE id = %d", $message_deleted_text, $subject_deleted_text, 1, $message_id );
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$wpdb->query( $query ); // db call ok; no-cache ok.
				bp_messages_update_meta( $message_id, 'bp_messages_deleted', 'yes' );
			}
		}

		/**
		 * Fires after user messages content update.
		 *
		 * @since BuddyPress 2.2.0
		 *
		 * @param array $message_ids        IDs of messages being deleted.
		 * @param int   $user_id            ID of the user the threads messages update for.
		 * @param array $update_message_ids IDs of messages being updated.
		 *
		 * @param int   $thread_id          ID of the thread being deleted.
		 */
		do_action( 'bp_messages_thread_messages_after_update', $thread_id, $message_ids, $user_id, $update_message_ids );

		// If there is no any messages in thread then delete the complete thread.
		$thread_delete = true;

		if ( ! empty( $message_ids ) ) {
			foreach ( $message_ids as $message_id ) {
				$is_deleted = bp_messages_get_meta( $message_id, 'bp_messages_deleted', true );
				if ( '' === $is_deleted ) {
					$thread_delete = false;
					break;
				}
			}
		}

		// Group thread will delete only when group is deleted.
		$is_group_thread = false;
		if ( bp_is_active( 'groups' ) && function_exists( 'bp_disable_group_messages' ) && true === bp_disable_group_messages() ) {
			// Get the group id from the first message.
			$first_message    = self::get_first_message( (int) $thread_id );
			$message_group_id = (int) bp_messages_get_meta( $first_message->id, 'group_id', true ); // group id.
			if ( $message_group_id > 0 ) {
				$group_thread = (int) groups_get_groupmeta( $message_group_id, 'group_message_thread' );
				if ( $group_thread > 0 && $group_thread === (int) $thread_id ) {
					$is_group_thread = true;
				}
			}
		}

		if ( $thread_delete && ! $is_group_thread ) {

			/**
			 * Fires before an entire message thread is deleted.
			 *
			 * @since BuddyPress 2.2.0
			 *
			 * @param array $message_ids   IDs of messages being deleted.
			 * @param bool  $thread_delete True entire thread will be deleted.
			 *
			 * @param int   $thread_id     ID of the thread being deleted.
			 */
			do_action( 'bp_messages_thread_before_delete', $thread_id, $message_ids, $thread_delete );

			if ( bp_is_active( 'notifications' ) ) {
				// Delete Message Notifications.
				bp_messages_message_delete_notifications( $thread_id, $message_ids );
			}

			$recipients = self::get_recipients_for_thread( (int) $thread_id );

			// Delete thread messages.
			$query = $wpdb->prepare( "DELETE FROM {$bp->messages->table_name_messages} WHERE thread_id = %d", $thread_id ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			//phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->query( $query ); // db call ok; no-cache ok.

			// Delete messages meta.
			$query_meta = $wpdb->prepare( "DELETE FROM {$bp->messages->table_name_meta} WHERE message_id IN(%s)", implode( ',', $message_ids ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			//phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->query( $query_meta ); // db call ok; no-cache ok.

			// Delete thread.
			$query_recipients = $wpdb->prepare( "DELETE FROM {$bp->messages->table_name_recipients} WHERE thread_id = %d", $thread_id ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			//phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->query( $query_recipients ); // db call ok; no-cache ok.

			/**
			 * Fires after message thread deleted.
			 *
			 * @since BuddyBoss 1.5.6
			 */
			do_action( 'bp_messages_message_delete_thread', $thread_id, $recipients );

			/**
			 * Fires before an entire message thread is deleted.
			 *
			 * @since BuddyPress 2.2.0
			 *
			 * @param array $message_ids   IDs of messages being deleted.
			 * @param int   $user_id       ID of the user the threads were deleted for.
			 * @param bool  $thread_delete True entire thread will be deleted.
			 *
			 * @param int   $thread_id     ID of the thread being deleted.
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
	 * @param array $args         {
	 *                            Array of arguments.
	 *
	 * @type int    $user_id      The user ID.
	 * @type string $box          The type of mailbox to get. Either 'inbox' or 'sentbox'.
	 *                                Defaults to 'inbox'.
	 * @type string $type         The type of messages to get. Either 'all' or 'unread'
	 *                                or 'read'. Defaults to 'all'.
	 * @type int    $limit        The number of messages to get. Defaults to null.
	 * @type int    $page         The page number to get. Defaults to null.
	 * @type string $search_terms The search term to use. Defaults to ''.
	 * @type array  $meta_query   Meta query arguments. See WP_Meta_Query for more details.
	 * }
	 * @return array|bool Array on success. Boolean false on failure.
	 */
	public static function get_current_threads_for_user( $args = array() ) {
		return self::get_threads_for_user( $args );
	}

	/**
	 * Get message threads.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param array $args         {
	 *                            Array of arguments.
	 *
	 * @type int    $user_id      The user ID.
	 * @type string $box          The type of mailbox to get. Either 'inbox' or 'sentbox'.
	 *                                Defaults to 'inbox'.
	 * @type string $type         The type of messages to get. Either 'all' or 'unread'
	 *                                or 'read'. Defaults to 'all'.
	 * @type int    $limit        The number of messages to get. Defaults to null.
	 * @type int    $page         The page number to get. Defaults to null.
	 * @type string $search_terms The search term to use. Defaults to ''.
	 * @type array  $meta_query   Meta query arguments. See WP_Meta_Query for more details.
	 * }
	 * @return array|bool Array on success. Boolean false on failure.
	 */
	public static function get_threads_for_user( $args = array() ) {
		global $wpdb;

		$bp            = buddypress();
		$function_args = func_get_args();

		// Backward compatibility with old method of passing arguments.
		if ( ! is_array( $args ) || count( $function_args ) > 1 ) {
			_deprecated_argument( __METHOD__, '2.2.0', sprintf( __( 'Arguments passed to %1$s should be in an associative array. See the inline documentation at %2$s for more details.', 'buddyboss' ), __METHOD__, __FILE__ ) );

			$old_args_keys = array(
				0 => 'user_id',
				1 => 'box',
				2 => 'type',
				3 => 'limit',
				4 => 'page',
				5 => 'search_terms',
			);

			$args = bp_core_parse_args_array( $old_args_keys, $function_args );
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
				'fields'       => 'all',
				'having_sql'   => false,
				'thread_type'  => 'unarchived',
				'force_cache'  => false,
			)
		);

		$sub_query = '';
		if ( false === bp_disable_group_messages() || ! bp_is_active( 'groups' ) ) {
			$sub_query = "AND m.id NOT IN ( SELECT DISTINCT message_id from {$bp->messages->table_name_meta} WHERE meta_key = 'group_message_users' AND meta_value = 'all' AND message_id IN ( SELECT DISTINCT message_id FROM {$bp->messages->table_name_meta} WHERE meta_key = 'group_message_type' AND meta_value = 'open' ) )";
		} elseif ( bp_is_active( 'groups' ) ) {
			// Determine groups of user.
			$groups = groups_get_groups(
				array(
					'fields'      => 'ids',
					'per_page'    => - 1,
					'user_id'     => $r['user_id'],
					'show_hidden' => true,
				)
			);

			$group_ids     = ( isset( $groups['groups'] ) ? $groups['groups'] : array() );
			$group_ids_sql = '';

			if ( ! empty( $group_ids ) ) {
				$group_ids_sql = implode( ',', array_unique( $group_ids ) );
				$group_ids_sql = "AND ( meta_key = 'group_id' and meta_value NOT IN({$group_ids_sql}) )";
			}

			$sub_query = "AND m.id NOT IN ( SELECT DISTINCT message_id from {$bp->messages->table_name_meta} WHERE 1 = 1 {$group_ids_sql} AND message_id IN ( SELECT DISTINCT message_id from {$bp->messages->table_name_meta} WHERE meta_key  = 'group_message_users' and meta_value = 'all' AND message_id in ( SELECT DISTINCT message_id from {$bp->messages->table_name_meta} where meta_key  = 'group_message_type' and meta_value = 'open' ) ) )";
		}

		$sub_query = apply_filters( 'bb_messages_thread_sub_query', $sub_query, $r );

		$r['meta_query'] = apply_filters( 'bb_messages_meta_query_threads_for_user', $r['meta_query'], $r );

		$pag_sql     = '';
		$type_sql    = '';
		$search_sql  = '';
		$user_id_sql = '';
		$sender_sql  = '';
		$having_sql  = '';

		$meta_query_sql = array(
			'join'  => '',
			'where' => '',
		);

		if ( $r['limit'] && $r['page'] ) {
			$pag_sql = $wpdb->prepare( ' LIMIT %d, %d', intval( ( $r['page'] - 1 ) * $r['limit'] ), intval( $r['limit'] ) );
		}

		$r['user_id'] = (int) $r['user_id'];
		$where_sql    = '1 = 1';

		$additional_where = array();

		if ( 'unarchived' === $r['thread_type'] ) {
			if ( ! empty( $r['include'] ) ) {
				$user_threads_query = $r['include'];
			} elseif ( ! empty( $r['user_id'] ) ) {

				$additional_where[] = 'r.is_deleted = 0';
				$additional_where[] = 'r.user_id = ' . $r['user_id'];

				if ( false === $r['is_hidden'] ) {
					$additional_where[] = 'r.is_hidden = 0';
				}
			}
		} elseif ( 'archived' === $r['thread_type'] ) {
			if ( ! empty( $r['include'] ) ) {
				$user_threads_query = $r['include'];
			} elseif ( ! empty( $r['user_id'] ) ) {
				$additional_where[] = 'r.is_deleted = 0';
				$additional_where[] = 'r.user_id = ' . $r['user_id'];
				$additional_where[] = 'r.is_hidden = 1';
			}
		}

		$group_thread_in = array();
		if ( ! empty( $r['search_terms'] ) ) {
			$current_user_participants_ids = array();

			$search_terms_like = '%' . bp_esc_like( $r['search_terms'] ) . '%';
			$where_sql         = $wpdb->prepare( 'm.message LIKE %s', $search_terms_like );

			$participants_sql           = array();
			$participants_sql['select'] = 'SELECT DISTINCT(r.thread_id)';
			$participants_sql['from']   = "FROM {$bp->messages->table_name_recipients} r LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID";
			$participants_sql['where']  = 'WHERE 1=1';
			if ( ! empty( $user_threads_query ) ) {
				$participants_sql['where'] .= " AND r.thread_id IN ($user_threads_query)";
			} elseif ( ! empty( $additional_where ) ) {
				$participants_sql['where'] .= ' AND r.is_deleted = 0';
			}

			$participants_sql['where_like'] = 'u.display_name LIKE %s OR u.user_login LIKE %s OR u.user_nicename LIKE %s';

			$participants_args = array(
				$search_terms_like,
				$search_terms_like,
				$search_terms_like,
			);

			// Search in xprofile field.
			if ( bp_is_active( 'xprofile' ) ) {
				// Explode the value if there is a space in search term.
				$split_name = explode( ' ', $r['search_terms'] );

				$participants_sql['from'] .= " LEFT JOIN {$bp->profile->table_name_data} spd ON r.user_id = spd.user_id";

				if ( isset( $split_name ) && isset( $split_name[0] ) && isset( $split_name[1] ) && ! empty( $split_name ) && ! empty( trim( $split_name[0] ) ) && ! empty( trim( $split_name[1] ) ) ) {
					$participants_sql['where_like'] .= ' OR spd.value LIKE %s OR spd.value LIKE %s';
					$participants_args[]             = $split_name[0];
					$participants_args[]             = $split_name[1];
				} else {
					$participants_sql['where_like'] .= ' OR spd.value LIKE %s';
					$participants_args[]             = $search_terms_like;
				}
			}

			$participants_sql['where'] .= " AND ( {$participants_sql['where_like']} )";
			$participants_sql           = "{$participants_sql['select']} {$participants_sql['from']} {$participants_sql['where']}";

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$participants_sql        = $wpdb->prepare( $participants_sql, $participants_args );
			$participants_sql_cached = bp_core_get_incremented_cache( $participants_sql, 'bp_messages' );

			if ( false === $participants_sql_cached || true === $r['force_cache'] ) {
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$current_user_participants = $wpdb->get_results( $participants_sql );
				bp_core_set_incremented_cache( $participants_sql, 'bp_messages', $current_user_participants );
			} else {
				$current_user_participants = $participants_sql_cached;
			}

			$current_user_thread_ids = array_map( 'intval', wp_list_pluck( $current_user_participants, 'thread_id' ) );

			$prefix       = apply_filters( 'bp_core_get_table_prefix', $wpdb->base_prefix );
			$groups_table = $prefix . 'bp_groups';
			if ( bp_is_active( 'groups' ) ) {
				$groups_table = $bp->groups->table_name;
				// Search Group Thread via Group Name via search_terms.
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$groups           = $wpdb->get_results( $wpdb->prepare( "SELECT DISTINCT(r.thread_id) FROM {$bp->messages->table_name_recipients} r LEFT JOIN {$groups_table} g ON r.user_id = g.creator_id LEFT JOIN {$bp->messages->table_name_messages} m ON m.thread_id = r.thread_id LEFT JOIN {$bp->messages->table_name_meta} mt ON m.id = mt.message_id WHERE g.name LIKE %s AND r.is_deleted = 0 AND mt.meta_key = 'group_id' AND mt.meta_value = g.id", $search_terms_like ) );
				$group_thread_ids = array_map( 'intval', wp_list_pluck( $groups, 'thread_id' ) );

				if ( ! empty( $group_thread_ids ) ) {
					if ( is_array( $current_user_thread_ids ) ) {
						$current_user_thread_ids = array_merge( $current_user_thread_ids, $group_thread_ids );
					} else {
						$current_user_thread_ids = $group_thread_ids;
					}
				}
			}

			// Search for deleted Group OR Deleted Users.
			$value = '(deleted)|(group)|(Deleted)|(group)|(user)|(User)|(del)|(Del)|(dele)|(Dele)|(dele)|(Dele)|(delet)|(Delet)|(use)|(Use)';
			if ( preg_match_all( '/\b' . $value . '\b/i', $r['search_terms'], $dest ) ) {

				// For deleted users.
				$current_user_participants_query = self::get(
					array(
						'exclude_active_users' => true,
						'per_page'             => - 1,
					)
				);

				$current_user_participants = ( ! empty( $current_user_participants_query['recipients'] ) ) ? array_unique( array_map( 'intval', wp_list_pluck( $current_user_participants_query['recipients'], 'user_id' ) ) ) : array();

				if ( ! empty( $current_user_participants ) ) {
					$deleted_user_ids = $current_user_participants;
					if ( is_array( $current_user_participants_ids ) ) {
						$current_user_participants_ids = array_merge( $current_user_participants_ids, $deleted_user_ids );
					} else {
						$current_user_participants_ids = $deleted_user_ids;
					}
				}

				// For deleted groups fetch all thread first.
				$threads    = self::get(
					array(
						'per_page' => - 1,
					)
				);
				$thread_ids = ( ! empty( $threads['recipients'] ) ) ? array_map( 'intval', wp_list_pluck( $threads['recipients'], 'thread_id' ) ) : array();

				// If Group Found.
				if ( ! empty( $thread_ids ) ) {
					foreach ( $thread_ids as $thread ) {
						// Get the group id from the first message.
						$first_message    = self::get_first_message( $thread );
						$message_group_id = (int) bp_messages_get_meta( $first_message->id, 'group_id', true ); // group id.
						if ( $message_group_id ) {
							if ( bp_is_active( 'groups' ) ) {
								$group_name = bp_get_group_name( groups_get_group( $message_group_id ) );
							} else {
								// phpcs:ignore ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
								$group_name = $wpdb->get_var( "SELECT name FROM {$groups_table} WHERE id = '{$message_group_id}';" ); // db call ok; no-cache ok.
							}
							if ( empty( $group_name ) ) {
								$group_thread_in[] = $thread;
							}
						}
					}
				}
			}

			if ( ! empty( $group_thread_in ) ) {
				if ( is_array( $current_user_thread_ids ) ) {
					$current_user_thread_ids = array_merge( $current_user_thread_ids, $group_thread_in );
				} else {
					$current_user_thread_ids = $group_thread_in;
				}
			}

			$search_where = '';
			if ( ! empty( $current_user_thread_ids ) ) {
				$thread_in     = implode( ',', array_unique( $current_user_thread_ids ) );
				$search_where .= " OR r.thread_id IN ({$thread_in})";
			}

			if ( ! empty( $current_user_participants_ids ) ) {
				$user_ids      = implode( ',', array_unique( $current_user_participants_ids ) );
				$search_where .= " OR r.user_id IN ({$user_ids})";
			}

			if ( ! empty( $search_where ) ) {
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$where_sql = '( ' . $wpdb->prepare( 'm.message LIKE %s', $search_terms_like ) . $search_where . ' )';
			}
		}

		if ( ! empty( $user_threads_query ) ) {
			$where_sql .= " AND r.thread_id IN ($user_threads_query)";
		} elseif ( ! empty( $additional_where ) ) {
			$where_sql .= ' AND ' . implode( ' AND ', $additional_where );
		}

		// Process meta query into SQL.
		$meta_query = self::get_meta_query_sql( $r['meta_query'] );
		if ( ! empty( $meta_query['join'] ) ) {
			$meta_query_sql['join'] = $meta_query['join'];
		}
		if ( ! empty( $meta_query['where'] ) ) {
			$meta_query_sql['where'] = $meta_query['where'];
		}

		if ( ! empty( $r['having_sql'] ) ) {
			$having_sql = $r['having_sql'];
		}

		// Set up SQL array.
		$sql = array();

		if ( ! empty( $r['having_sql'] ) ) {
			if ( strpos( $r['having_sql'], 'HAVING recipient_list' ) !== false ) {
				preg_match_all( '!\d+!', $r['having_sql'], $matches );
				$recipient_list = array_filter( array_unique( bp_array_flatten( $matches ) ) );
				if ( ! empty( $recipient_list ) ) {
					$recipient_list = implode( ',', array_unique( $recipient_list ) );
					$where_sql     .= " AND m.thread_id IN ( SELECT DISTINCT thread_id from {$bp->messages->table_name_recipients} where user_id in ({$recipient_list}) ) ";
				}
			}
			$sql['select'] = 'SELECT m.thread_id, MAX(m.date_sent) AS date_sent, GROUP_CONCAT(DISTINCT r.user_id ORDER BY r.user_id separator \',\' ) as recipient_list';
		} else {
			$sql['select'] = 'SELECT m.thread_id, MAX(m.date_sent) AS date_sent';
		}

		$sql['from']  = "FROM {$bp->messages->table_name_recipients} r INNER JOIN {$bp->messages->table_name_messages} m ON m.thread_id = r.thread_id AND m.is_deleted = 0 {$sub_query} {$meta_query_sql['join']}";
		$sql['where'] = "WHERE {$where_sql} {$meta_query_sql['where']}";
		$sql['misc']  = "GROUP BY m.thread_id {$having_sql} ORDER BY date_sent DESC {$pag_sql}";

		/**
		 * Filters the Where SQL statement.
		 *
		 * @since BuddyBoss 1.5.4
		 *
		 * @param array $r                Array of parsed arguments for the get method.
		 * @param array $where_conditions Where conditions SQL statement.
		 */
		$sql['where'] = apply_filters( 'bp_messages_recipient_get_where_conditions', $sql['where'], $r );

		/**
		 * Filters the From SQL statement.
		 *
		 * @since BuddyBoss 1.5.4
		 *
		 * @param array  $r   Array of parsed arguments for the get method.
		 * @param string $sql From SQL statement.
		 */
		$sql['from'] = apply_filters( 'bp_messages_recipient_get_join_sql', $sql['from'], $r );

		$qq = implode( ' ', $sql );

		$thread_ids_cached = bp_core_get_incremented_cache( $qq, 'bp_messages' );

		if ( false === $thread_ids_cached || true === $r['force_cache'] ) {
			// Get thread IDs.
			$thread_ids = $wpdb->get_results( $qq ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			bp_core_set_incremented_cache( $qq, 'bp_messages', $thread_ids );
		} else {
			$thread_ids = $thread_ids_cached;
		}

		if ( empty( $thread_ids ) ) {
			return false;
		}

		// Adjust $sql to work for thread total.
		$sql['select'] = 'SELECT COUNT( DISTINCT m.thread_id )';
		unset( $sql['misc'] );

		$total_threads_query  = implode( ' ', $sql );
		$total_threads_cached = bp_core_get_incremented_cache( $total_threads_query, 'bp_messages' );

		if ( false === $total_threads_cached || true === $r['force_cache'] ) {
			$total_threads = $wpdb->get_var( $total_threads_query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			bp_core_set_incremented_cache( $total_threads_query, 'bp_messages', $total_threads );
		} else {
			$total_threads = $total_threads_cached;
		}

		// Sort threads by date_sent.
		foreach ( (array) $thread_ids as $thread ) {
			$last_message = self::get_last_message( $thread->thread_id );
			if ( ! empty( $last_message ) && ! empty( $last_message->date_sent ) && $last_message->date_sent !== $thread->date_sent ) {
				$thread->date_sent = $last_message->date_sent;
			}
			$sorted_threads[ $thread->thread_id ] = strtotime( $thread->date_sent );
		}

		arsort( $sorted_threads );

		$threads = array();
		if ( 'ids' === $r['fields'] ) {
			$threads = array_keys( $sorted_threads );
		} elseif ( 'select' === $r['fields'] ) {
			$threads = $thread_ids;
		} else {
			foreach ( (array) $sorted_threads as $thread_id => $date_sent ) {
				$threads[] = new BP_Messages_Thread(
					$thread_id,
					'ASC',
					array(
						'update_meta_cache' => false,
					)
				);
			}
		}

		/**
		 * Filters the results of the query for a user's message threads.
		 *
		 * @since BuddyPress 2.2.0
		 *
		 * @param array $value         {
		 *
		 * @type array  $threads       Array of threads. Passed by reference.
		 * @type int    $total_threads Number of threads found by the query.
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
	 *
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
	 * @param int $user_id   The user the thread will be marked as read.
	 *
	 * @return false|int Number of threads marked as read or false on error.
	 */
	public static function mark_as_read( $thread_id = 0, $user_id = 0 ) {
		global $wpdb;

		if ( empty( $user_id ) ) {
			$user_id =
				bp_displayed_user_id() ?
					bp_displayed_user_id() :
					bp_loggedin_user_id();
		}

		$bp     = buddypress();
		$retval = false;

		// phpcs:ignore
		$is_unread = $wpdb->get_col( $wpdb->prepare( "SELECT unread_count from {$bp->messages->table_name_recipients} WHERE user_id = %d AND thread_id = %d AND unread_count > 0", $user_id, $thread_id ) );

		if ( ! empty( $is_unread ) ) {
			// phpcs:ignore
			$retval = $wpdb->query( $wpdb->prepare( "UPDATE {$bp->messages->table_name_recipients} SET unread_count = 0 WHERE user_id = %d AND thread_id = %d", $user_id, $thread_id ) );

			wp_cache_delete( "bb_thread_message_unread_count_{$user_id}_{$thread_id}", 'bp_messages_unread_count' );
			wp_cache_delete( 'thread_recipients_' . $thread_id, 'bp_messages' );
			wp_cache_delete( $user_id, 'bp_messages_unread_count' );

			/**
			 * Fires when messages thread was marked as read.
			 *
			 * @since BuddyPress 2.8.0
			 * @since BuddyBoss 2.2 Added the `user_id` parameter.
			 * @since BuddyBoss 2.2 Added the `$retval` parameter.
			 *
			 * @param int      $thread_id The message thread ID.
			 * @param int      $user_id   The user the thread will be marked as read.
			 * @param bool|int $num_rows  Number of threads marked as unread or false on error.
			 */
			do_action( 'messages_thread_mark_as_read', $thread_id, $user_id, $retval );
		}

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

		wp_cache_delete( "bb_thread_message_unread_count_{$user_id}_{$thread_id}", 'bp_messages_unread_count' );
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
	 *
	 * @return int $value Total thread count for the provided user.
	 */
	public static function get_total_threads_for_user( $user_id, $box = 'inbox', $type = 'all' ) {

		$args = array(
			'count_total' => true,
			'user_id'     => $user_id,
			'is_deleted'  => 0,
			'per_page'    => 1,
		);

		if ( 'unread' === $type ) {
			$args['is_new'] = 1;
		} elseif ( 'read' === $type ) {
			$args['is_new'] = 0;
		}

		$recipients = self::get( $args );

		return ( ! empty( $recipients['total'] ) ) ? $recipients['total'] : false;
	}

	/**
	 * Determine if the logged-in user is a sender of any message in a thread.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param int $thread_id The message thread ID.
	 *
	 * @return bool
	 */
	public static function user_is_sender( $thread_id ) {

		$senders = BP_Messages_Message::get(
			array(
				'fields'          => 'sender_ids',
				'include_threads' => array( $thread_id ),
				'per_page'        => - 1,
			)
		);

		$sender_ids = ( ! empty( $senders['messages'] ) ) ? $senders['messages'] : array();

		if ( empty( $sender_ids ) ) {
			return false;
		}

		return in_array( bp_loggedin_user_id(), wp_parse_id_list( $sender_ids ), true );
	}

	/**
	 * Returns the userlink of the last sender in a message thread.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param int $thread_id The message thread ID.
	 *
	 * @return string|bool The user link on success. Boolean false on failure.
	 */
	public static function get_last_sender( $thread_id ) {
		$senders = BP_Messages_Message::get(
			array(
				'fields'          => 'sender_ids',
				'include_threads' => array( $thread_id ),
				'per_page'        => 1,
			)
		);

		$sender_id = ( ! empty( $senders['messages'] ) ? current( $senders['messages'] ) : null );

		if ( ! $sender_id ) {
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
	 *
	 * @return int $unread_count Total inbox unread count for user.
	 */
	public static function get_inbox_count( $user_id = 0 ) {

		if ( empty( $user_id ) ) {
			$user_id = bp_loggedin_user_id();
		}

		$unread_count = wp_cache_get( $user_id, 'bp_messages_unread_count' );

		if ( false === $unread_count ) {
			$args = array(
				'user_id'    => $user_id,
				'per_page'   => - 1,
				'is_deleted' => 0,
				'is_hidden'  => 0,
			);

			add_filter( 'bb_messages_thread_sub_query', 'bb_messages_update_unread_count', 10, 2 );

			$threads = self::get_current_threads_for_user(
				array(
					'user_id' => $user_id,
					'limit'   => - 1,
					'fields'  => 'ids',
				)
			);

			remove_filter( 'bb_messages_thread_sub_query', 'bb_messages_update_unread_count', 10, 2 );

			if ( ! empty( $threads['threads'] ) ) {
				$args['exclude_threads'] = $threads['threads'];
			}

			$unread_counts = self::get( $args );

			$unread_count = 0;
			if ( ! empty( $unread_counts['recipients'] ) ) {
				$message_counts = array_column( $unread_counts['recipients'], 'unread_count' );
				$unread_count   = ( ! empty( $message_counts ) ? array_sum( $message_counts ) : $unread_count );
			}

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
	 *
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
	 *
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
	 *
	 * @return string $value String of message recipent userlinks.
	 */
	public static function get_recipient_links( $recipients ) {

		if ( count( $recipients ) >= 5 ) {
			return sprintf( __( '%s Recipients', 'buddyboss' ), bp_core_number_format( count( $recipients ) ) );
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
	 * @return bool
	 * @todo  We should remove this.  No one is going to upgrade from v1.1, right?
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

	/**
	 * Query for recipients.
	 *
	 * @since BuddyBoss 1.5.4
	 *
	 * @param       $args                 {
	 *                                    Array of parameters. All items are optional.
	 *
	 * @type string $orderby              Optional. Property to sort by. 'id'.
	 * @type string $order                Optional. Sort order. 'ASC' or 'DESC'. Default: 'DESC'.
	 * @type int    $per_page             Optional. Number of items to return per page of results.
	 *                                 Default: 20.
	 * @type int    $page                 Optional. Page offset of results to return.
	 *                                 Default: 1.
	 * @type int    $user_id              Optional. If provided, results will be limited to recipients of which the specified user id id provided.
	 *                                 Default: null.
	 * @type int    $is_deleted           Optional. whether to include deleted recipients or not.
	 *                                 Default: null.
	 * @type array  $include              Optional. Array of recipients IDs. Results will include the listed recipients.
	 *                                 Default: false.
	 * @type array  $exclude              Optional. Array of recipients IDs. Results will exclude the listed recipients.
	 *                                 Default: false.
	 * @type array  $include_threads      Optional. Array of thread IDs. Results will include the listed recipients with given thread ids.
	 *                                 Default: false.
	 * @type array  $exclude_threads      Optional. Array of thread IDs. Results will exclude the listed recipients with given thread ids.
	 *                                 Default: false.
	 * @type array  $is_new               Optional. Retried Thread which has unread_count not equal to zero ( unread thread ) if is_new is 1 otherwise return thread which has unread_count equal to zero ( read thread )
	 *                                 Default: Null.
	 * @type string $fields               Which fields to return. Specify 'ids' to fetch a list of IDs.
	 *                                 Default: 'all' (return BP_Messages_Thread objects).
	 * @type int    $count_total          Total count of all messages matching non-paginated query params.
	 * @type bool   $exclude_active_users Special paramter to join with users table.
	 *                                 Default: false.
	 *
	 * }
	 *
	 * @return array
	 */
	public static function get( $args ) {
		global $wpdb;

		$bp = buddypress();

		$defaults = array(
			'orderby'              => 'id',
			'order'                => 'DESC',
			'per_page'             => 20,
			'page'                 => 1,
			'user_id'              => 0,
			'is_deleted'           => false,
			'include'              => false,
			'exclude'              => false,
			'include_threads'      => false,
			'exclude_threads'      => false,
			'is_new'               => null,
			'fields'               => 'all',
			'count_total'          => false,
			'exclude_active_users' => false,
		);

		$r = bp_parse_args( $args, $defaults, 'bp_recipients_recipient_get' );

		$sql = array(
			'select'     => 'SELECT DISTINCT r.id',
			'from'       => "{$bp->messages->table_name_recipients} r",
			'where'      => '',
			'orderby'    => '',
			'pagination' => '',
		);

		$where_conditions = array();

		if ( ! empty( $r['include'] ) ) {
			$include                     = implode( ',', wp_parse_id_list( $r['include'] ) );
			$where_conditions['include'] = "r.id IN ({$include})";
		}

		if ( ! empty( $r['exclude'] ) ) {
			$exclude                     = implode( ',', wp_parse_id_list( $r['exclude'] ) );
			$where_conditions['exclude'] = "r.id NOT IN ({$exclude})";
		}

		if ( ! empty( $r['include_threads'] ) ) {
			$include_threads                     = implode( ',', wp_parse_id_list( $r['include_threads'] ) );
			$where_conditions['include_threads'] = "r.thread_id IN ({$include_threads})";
		}

		if ( ! empty( $r['exclude_threads'] ) ) {
			$exclude_threads                     = implode( ',', wp_parse_id_list( $r['exclude_threads'] ) );
			$where_conditions['exclude_threads'] = "r.thread_id NOT IN ({$exclude_threads})";
		}

		if ( null !== $r['is_new'] ) {
			if ( 1 == $r['is_new'] ) {
				$where_conditions['is_new'] = 'r.unread_count != 0';
			} else {
				$where_conditions['is_new'] = 'r.unread_count = 0';
			}
		}

		if ( ! empty( $r['user_id'] ) ) {
			$where_conditions['user'] = $wpdb->prepare( 'r.user_id = %d', $r['user_id'] );
		}

		if ( false !== $r['is_deleted'] ) {
			$where_conditions['is_deleted'] = $wpdb->prepare( 'r.is_deleted = %d', $r['is_deleted'] );
		}

		if ( isset( $r['is_hidden'] ) ) {
			$where_conditions['is_hidden'] = $wpdb->prepare( 'r.is_hidden = %d', $r['is_hidden'] );
		}

		if ( true === $r['exclude_active_users'] ) {
			$where_conditions['exclude_active_users'] = 'r.user_id NOT IN (SELECT ID FROM ' . $wpdb->users . ')';
		}

		/* Order/orderby ********************************************/

		$order   = $r['order'];
		$orderby = $r['orderby'];

		// Sanitize 'order'.
		$order = bp_esc_sql_order( $order );

		/**
		 * Filters the converted 'orderby' term.
		 *
		 * @since BuddyBoss 1.5.4
		 *
		 * @param string $value   Converted 'orderby' term.
		 * @param string $orderby Original orderby value.
		 */
		$orderby = apply_filters( 'bp_recipients_recipient_get_orderby', self::convert_orderby_to_order_by_term( $orderby ), $orderby );

		$sql['orderby'] = "ORDER BY {$orderby} {$order}";

		if ( ! empty( $r['per_page'] ) && ! empty( $r['page'] ) && - 1 !== $r['per_page'] ) {
			$sql['pagination'] = $wpdb->prepare( 'LIMIT %d, %d', intval( ( $r['page'] - 1 ) * $r['per_page'] ), intval( $r['per_page'] ) );
		}

		/**
		 * Filters the Where SQL statement.
		 *
		 * @since BuddyBoss 1.5.4
		 *
		 * @param array $r                Array of parsed arguments for the get method.
		 * @param array $where_conditions Where conditions SQL statement.
		 */
		$where_conditions = apply_filters( 'bp_recipients_recipient_get_where_conditions', $where_conditions, $r );

		$where = '';
		if ( ! empty( $where_conditions ) ) {
			$sql['where'] = implode( ' AND ', $where_conditions );
			$where        = "WHERE {$sql['where']}";
		}

		/**
		 * Filters the From SQL statement.
		 *
		 * @since BuddyBoss 1.5.4
		 *
		 * @param array  $r   Array of parsed arguments for the get method.
		 * @param string $sql From SQL statement.
		 */
		$sql['from'] = apply_filters( 'bp_recipients_recipient_get_join_sql', $sql['from'], $r );

		$paged_recipients_sql = "{$sql['select']} FROM {$sql['from']} {$where} {$sql['orderby']} {$sql['pagination']}";

		/**
		 * Filters the pagination SQL statement.
		 *
		 * @since BuddyBoss 1.5.4
		 *
		 * @param string $value Concatenated SQL statement.
		 * @param array  $sql   Array of SQL parts before concatenation.
		 * @param array  $r     Array of parsed arguments for the get method.
		 */
		$paged_recipients_sql = apply_filters( 'bp_recipients_recipient_get_paged_sql', $paged_recipients_sql, $sql, $r );

		$cached = bp_core_get_incremented_cache( $paged_recipients_sql, 'bp_messages' );

		if ( false === $cached ) {
			$paged_recipient_ids = $wpdb->get_col( $paged_recipients_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			bp_core_set_incremented_cache( $paged_recipients_sql, 'bp_messages', $paged_recipient_ids );
		} else {
			$paged_recipient_ids = $cached;
		}

		$paged_recipients = array();

		if ( 'ids' === $r['fields'] ) {
			// We only want the IDs.
			$paged_recipients = array_map( 'intval', $paged_recipient_ids );
		} elseif ( ! empty( $paged_recipient_ids ) ) {
			$recipient_ids_sql          = implode( ',', array_map( 'intval', $paged_recipient_ids ) );
			$recipient_data_objects_sql = "SELECT r.* FROM {$bp->messages->table_name_recipients} r WHERE r.id IN ({$recipient_ids_sql})";
			$cached                     = bp_core_get_incremented_cache( $recipient_data_objects_sql, 'bp_messages' );

			if ( false === $cached ) {
				$recipient_data_objects = $wpdb->get_results( $recipient_data_objects_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
				bp_core_set_incremented_cache( $recipient_data_objects_sql, 'bp_messages', $recipient_data_objects );
			} else {
				$recipient_data_objects = $cached;
			}

			foreach ( (array) $recipient_data_objects as $mdata ) {
				$recipient_data_objects[ $mdata->id ] = $mdata;
			}
			foreach ( $paged_recipient_ids as $paged_recipient_id ) {
				$paged_recipients[] = $recipient_data_objects[ $paged_recipient_id ];
			}
		}

		$retval = array(
			'recipients' => $paged_recipients,
			'total'      => 0,
		);

		if ( ! empty( $r['count_total'] ) ) {
			// Find the total number of messages in the results set.
			$total_recipients_sql = "SELECT COUNT(DISTINCT r.id) FROM {$sql['from']} $where";

			/**
			 * Filters the SQL used to retrieve total message results.
			 *
			 * @since BuddyBoss 1.5.4
			 *
			 * @param string $t_sql     Concatenated SQL statement used for retrieving total messages results.
			 * @param array  $total_sql Array of SQL parts for the query.
			 * @param array  $r         Array of parsed arguments for the get method.
			 */
			$total_recipients_sql = apply_filters( 'bp_recipients_recipient_get_total_sql', $total_recipients_sql, $sql, $r );

			$cached = bp_core_get_incremented_cache( $total_recipients_sql, 'bp_messages' );

			if ( false === $cached ) {
				$total_recipients = (int) $wpdb->get_var( $total_recipients_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
				bp_core_set_incremented_cache( $total_recipients_sql, 'bp_messages', $total_recipients );
			} else {
				$total_recipients = $cached;
			}

			$retval['total'] = $total_recipients;
		}

		return $retval;
	}

	/**
	 * Convert the 'orderby' param into a proper SQL term/column.
	 *
	 * @since BuddyPress 1.8.0
	 *
	 * @param string $orderby Orderby term as passed to get().
	 *
	 * @return string $order_by_term SQL-friendly orderby term.
	 */
	protected static function convert_orderby_to_order_by_term( $orderby ) {
		$order_by_term = '';

		switch ( $orderby ) {
			case 'thread_id':
				$order_by_term = 'r.thread_id';
				break;
			case 'id':
			default:
				$order_by_term = 'r.id';
				break;
		}

		return $order_by_term;
	}

	/**
	 * Filters the Where SQL statement.
	 *
	 * @since BuddyBoss 1.5.4
	 *
	 * @param array $where_conditions Where conditions SQL statement.
	 * @param array $r                Array of parsed arguments for the get method.
	 *
	 * @return mixed
	 */
	public static function bp_filter_messages_message_get_where_conditions( $where_conditions, $r ) {
		global $wpdb;
		$last_deleted_id             = static::$last_deleted_message ? static::$last_deleted_message->id : 0;
		$where_conditions['columns'] = $wpdb->prepare( 'm.id > %d', $last_deleted_id );

		return $where_conditions;
	}

	/**
	 * Returns recipients for a message thread with pagination.
	 *
	 * @since BuddyBoss 1.7.6
	 *
	 * @param int   $thread_id The thread ID.
	 * @param array $args      Array of parsed arguments for the get method.
	 *
	 * @return array
	 */
	public function get_pagination_recipients( $thread_id = 0, $args = array() ) {
		if ( empty( $thread_id ) ) {
			$thread_id = $this->thread_id;
		}

		$r = bp_parse_args(
			$args,
			array(
				'per_page'        => bb_messages_recipients_per_page(),
				'include_threads' => array( (int) $thread_id ),
				'count_total'     => true,
			)
		);

		if ( isset( $r['exclude_current_user'] ) && true === (bool) $r['exclude_current_user'] ) {
			// Exclude admins users list in the message.
			$user_id = bp_displayed_user_id() ? bp_displayed_user_id() : bp_loggedin_user_id();
			if ( ! empty( $r['exclude_admin_user'] ) ) {
				$r['exclude_admin_user'] = array_merge( $r['exclude_admin_user'], array( $user_id ) );
			} else {
				$r['exclude_admin_user'] = array( $user_id );
			}
			$r['exclude_admin_user'] = array_unique( $r['exclude_admin_user'] );
		}
		$results    = self::get( $r );
		$recipients = array();

		if ( ! empty( $results['recipients'] ) ) {
			foreach ( (array) $results['recipients'] as $recipient ) {
				$recipients[ $recipient->user_id ] = (object) array_map( 'intval', (array) $recipient );
			}
		}

		if ( isset( $results['total'] ) ) {
			$this->total_recipients_count = $results['total'];
		}

		/**
		 * Filters the recipients of a message thread.
		 *
		 * @since BuddyBoss 1.7.6
		 *
		 * @param array $recipients Array of recipient objects.
		 * @param int   $thread_id  ID of the current thread.
		 */
		return apply_filters( 'bp_messages_thread_get_pagination_recipients', $recipients, $thread_id );
	}

	/**
	 * Checks whether a message thread is archived or not.
	 *
	 * @since BuddyBoss 2.1.4
	 *
	 * @param int $thread_id The message thread ID.
	 * @param int $user_id   The user ID.
	 *
	 * @return boolean
	 */
	public static function is_valid_archived( $thread_id = 0, $user_id = 0 ) {
		global $wpdb;

		$bp = buddypress();

		// Bail if no thread ID is passed.
		if ( empty( $thread_id ) ) {
			return false;
		}

		if ( empty( $user_id ) ) {
			$user_id = bp_loggedin_user_id();
		}

		$is_thread_archived = $wpdb->query( $wpdb->prepare( "SELECT * FROM {$bp->messages->table_name_recipients} WHERE is_hidden = %d AND thread_id = %d AND user_id = %d", 1, $thread_id, $user_id ) );

		if ( 0 < $is_thread_archived ) {
			return true;
		}

		return false;
	}
}
