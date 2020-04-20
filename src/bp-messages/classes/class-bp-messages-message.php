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
 * Single message class.
 */
class BP_Messages_Message {

	public static $last_inserted_id;
	/**
	 * ID of the message.
	 *
	 * @var int
	 */
	public $id;

	/**
	 * ID of the message thread.
	 *
	 * @var int
	 */
	public $thread_id;

	/**
	 * ID of the sender.
	 *
	 * @var int
	 */
	public $sender_id;

	/**
	 * Subject line of the message.
	 *
	 * @var string
	 */
	public $subject;

	/**
	 * Content of the message.
	 *
	 * @var string
	 */
	public $message;

	/**
	 * Date the message was sent.
	 *
	 * @var string
	 */
	public $date_sent;

	/**
	 * Thread is hidden.
	 *
	 * @var bool
	 */
	public $is_hidden;

	/**
	 * Mark thread to visible for other participants.
	 *
	 * @var bool
	 */
	public $mark_visible;

	/**
	 * Message recipients.
	 *
	 * @var bool|array
	 */
	public $recipients = false;

	/**
	 * Constructor.
	 *
	 * @param int|null $id Optional. ID of the message.
	 */
	public function __construct( $id = null ) {
		$this->date_sent = bp_core_current_time();
		$this->sender_id = bp_loggedin_user_id();

		if ( ! empty( $id ) ) {
			$this->populate( $id );
		}
	}

	/**
	 * Set up data related to a specific message object.
	 *
	 * @param int $id ID of the message.
	 */
	public function populate( $id ) {
		global $wpdb;

		$bp = buddypress();

		if ( $message = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$bp->messages->table_name_messages} WHERE id = %d", $id ) ) ) {
			$this->id        = (int) $message->id;
			$this->thread_id = (int) $message->thread_id;
			$this->sender_id = (int) $message->sender_id;
			$this->subject   = $message->subject;
			$this->message   = $message->message;
			$this->date_sent = $message->date_sent;
		}
	}

	/**
	 * Send a message.
	 *
	 * @return int|bool ID of the newly created message on success, false on failure.
	 */
	public function send() {
		global $wpdb;

		$bp = buddypress();

		$this->sender_id    = apply_filters( 'messages_message_sender_id_before_save', $this->sender_id, $this->id );
		$this->thread_id    = apply_filters( 'messages_message_thread_id_before_save', $this->thread_id, $this->id );
		$this->subject      = apply_filters( 'messages_message_subject_before_save', $this->subject, $this->id );
		$this->message      = apply_filters( 'messages_message_content_before_save', $this->message, $this->id );
		$this->date_sent    = apply_filters( 'messages_message_date_sent_before_save', $this->date_sent, $this->id );
		$this->is_hidden    = apply_filters( 'messages_message_is_hidden_before_save', $this->is_hidden, $this->id );
		$this->mark_visible = apply_filters( 'messages_message_mark_visible_before_save', $this->mark_visible, $this->id );

		/**
		 * Fires before the current message item gets saved.
		 *
		 * Please use this hook to filter the properties above. Each part will be passed in.
		 *
		 * @since BuddyPress 1.0.0
		 *
		 * @param BP_Messages_Message $this Current instance of the message item being saved. Passed by reference.
		 */
		do_action_ref_array( 'messages_message_before_save', array( &$this ) );

		// Make sure we have at least one recipient before sending.
		if ( empty( $this->recipients ) ) {
			return false;
		}

		$new_thread = false;

		// If we have no thread_id then this is the first message of a new thread.
		if ( empty( $this->thread_id ) ) {
			$this->thread_id = (int) $wpdb->get_var( "SELECT MAX(thread_id) FROM {$bp->messages->table_name_messages}" ) + 1;
			$new_thread      = true;
		}

		// First insert the message into the messages table.
		if ( ! $wpdb->query( $wpdb->prepare( "INSERT INTO {$bp->messages->table_name_messages} ( thread_id, sender_id, subject, message, date_sent ) VALUES ( %d, %d, %s, %s, %s )", $this->thread_id, $this->sender_id, $this->subject, $this->message, $this->date_sent ) ) ) {
			return false;
		}

		static::$last_inserted_id = $this->id = $wpdb->insert_id;

		$recipient_ids = array();

		if ( $new_thread ) {
			// Add an recipient entry for all recipients.
			foreach ( (array) $this->recipients as $recipient ) {
				$wpdb->query( $wpdb->prepare( "INSERT INTO {$bp->messages->table_name_recipients} ( user_id, thread_id, unread_count ) VALUES ( %d, %d, 1 )", $recipient->user_id, $this->thread_id ) );
				$recipient_ids[] = $recipient->user_id;
			}

			// Add a sender recipient entry if the sender is not in the list of recipients.
			if ( ! in_array( $this->sender_id, $recipient_ids ) ) {
				$wpdb->query( $wpdb->prepare( "INSERT INTO {$bp->messages->table_name_recipients} ( user_id, thread_id ) VALUES ( %d, %d )", $this->sender_id, $this->thread_id ) );
			}

			// Mark Hidden thread for sender if `is_hidden` passed.
			if ( true === $this->is_hidden ) {
				$wpdb->query( $wpdb->prepare( "UPDATE {$bp->messages->table_name_recipients} SET is_hidden = %d WHERE thread_id = %d AND user_id = %d", 1, $this->thread_id, $this->sender_id ) );
			}

		} else {
			// Update the unread count for all recipients.
			$wpdb->query( $wpdb->prepare( "UPDATE {$bp->messages->table_name_recipients} SET unread_count = unread_count + 1, is_deleted = 0 WHERE thread_id = %d AND user_id != %d", $this->thread_id, $this->sender_id ) );

			if ( true === $this->mark_visible ) {
				// Mark the thread to visible for all recipients.
				$wpdb->query( $wpdb->prepare( "UPDATE {$bp->messages->table_name_recipients} SET is_hidden = %d WHERE thread_id = %d AND user_id != %d", 0, $this->thread_id, $this->sender_id ) );
			}
		}

		messages_remove_callback_values();

		/**
		 * Fires after the current message item has been saved.
		 *
		 * @since BuddyPress 1.0.0
		 *
		 * @param BP_Messages_Message $this Current instance of the message item being saved. Passed by reference.
		 */
		do_action_ref_array( 'messages_message_after_save', array( &$this ) );

		return $this->id;
	}

	/**
	 * Get a list of recipients for a message.
	 *
	 * @return object $value List of recipients for a message.
	 */
	public function get_recipients() {
		global $wpdb;

		$bp = buddypress();

		return $wpdb->get_results( $wpdb->prepare( "SELECT user_id FROM {$bp->messages->table_name_recipients} WHERE thread_id = %d", $this->thread_id ) );
	}

	/** Static Functions **************************************************/

	/**
	 * Get list of recipient IDs from their usernames.
	 *
	 * @param array $recipient_usernames Usernames of recipients.
	 *
	 * @return bool|array $recipient_ids Array of Recepient IDs.
	 */
	public static function get_recipient_ids( $recipient_usernames ) {
		$recipient_ids = false;

		if ( ! $recipient_usernames ) {
			return $recipient_ids;
		}

		if ( is_array( $recipient_usernames ) ) {
			$rec_un_count = count( $recipient_usernames );

			for ( $i = 0, $count = $rec_un_count; $i < $count; ++ $i ) {
				if ( $rid = bp_core_get_userid( trim( $recipient_usernames[ $i ] ) ) ) {
					$recipient_ids[] = $rid;
				}
			}
		}

		/**
		 * Filters the array of recipients IDs.
		 *
		 * @since BuddyPress 2.8.0
		 *
		 * @param array $recipient_ids Array of recipients IDs that were retrieved based on submitted usernames.
		 * @param array $recipient_usernames Array of recipients usernames that were submitted by a user.
		 */
		return apply_filters( 'messages_message_get_recipient_ids', $recipient_ids, $recipient_usernames );
	}

	/**
	 * Get the ID of the message last sent by the logged-in user for a given thread.
	 *
	 * @param int $thread_id ID of the thread.
	 *
	 * @return int|null ID of the message if found, otherwise null.
	 */
	public static function get_last_sent_for_user( $thread_id ) {
		global $wpdb;

		$bp = buddypress();

		$query = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$bp->messages->table_name_messages} WHERE sender_id = %d AND thread_id = %d ORDER BY date_sent DESC LIMIT 1", bp_loggedin_user_id(), $thread_id ) );

		return is_numeric( $query ) ? (int) $query : $query;
	}

	/**
	 * Check whether a user is the sender of a message.
	 *
	 * @param int $user_id ID of the user.
	 * @param int $message_id ID of the message.
	 *
	 * @return int|null Returns the ID of the message if the user is the
	 *                  sender, otherwise null.
	 */
	public static function is_user_sender( $user_id, $message_id ) {
		global $wpdb;

		$bp = buddypress();

		$query = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$bp->messages->table_name_messages} WHERE sender_id = %d AND id = %d", $user_id, $message_id ) );

		return is_numeric( $query ) ? (int) $query : $query;
	}

	/**
	 * Get the ID of the sender of a message.
	 *
	 * @param int $message_id ID of the message.
	 *
	 * @return int|null The ID of the sender if found, otherwise null.
	 */
	public static function get_message_sender( $message_id ) {
		global $wpdb;

		$bp = buddypress();

		$query = $wpdb->get_var( $wpdb->prepare( "SELECT sender_id FROM {$bp->messages->table_name_messages} WHERE id = %d", $message_id ) );

		return is_numeric( $query ) ? (int) $query : $query;
	}

	/**
	 * Delete all the message send by user
	 *
	 * @BuddyBoss 1.0.0
	 *
	 * @param int $user_id user id whom message should get deleted
	 */
	public static function delete_user_message( $user_id ) {
		global $wpdb;

		$bp = buddypress();

		// Get the message ids in order to delete their metas.
		$message_ids = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT (id) FROM {$bp->messages->table_name_messages} WHERE sender_id = %d", $user_id ) );
		//Get the all thread ids for unread messages
		$thread_ids = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT (thread_id) FROM {$bp->messages->table_name_messages} WHERE sender_id = %d", $user_id ) );

		$subject_deleted_text = apply_filters( 'delete_user_message_subject_text', 'Deleted' );
		$message_deleted_text = '<p> </p>';

		// Delete message meta.
		foreach ( $message_ids as $message_id ) {
			$query = $wpdb->prepare( "UPDATE {$bp->messages->table_name_messages} SET subject= '%s', message= '%s' WHERE id = %d", $subject_deleted_text, $message_deleted_text, $message_id );
			$wpdb->query( $query ); // db call ok; no-cache ok;
			// bp_messages_delete_meta( $message_id );
			bp_messages_update_meta( $message_id, '_gif_raw_data', '' );
			bp_messages_update_meta( $message_id, '_gif_data', '' );
			bp_messages_update_meta( $message_id, 'bp_media_ids', '' );
			bp_messages_update_meta( $message_id, 'bp_messages_deleted', 'yes' );
		}
		// unread theread message.
		if ( ! empty( $thread_ids ) ) {
			$thread_ids = implode( ',', $thread_ids );

			$wpdb->query( "UPDATE {$bp->messages->table_name_recipients} SET unread_count = 0 WHERE thread_id IN ({$thread_ids})" );
		}

		// Delete the thread of user.
		if ( bp_has_message_threads( array( 'user_id' => $user_id, ) ) ) {
			while ( bp_message_threads() ) :
				bp_message_thread();
				$thread_id = bp_get_message_thread_id();
				messages_delete_thread( $thread_id, $user_id );
			endwhile;
		}

		// delete all the meta recipients from user table.
		//$wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->messages->table_name_recipients} WHERE user_id = %d", $user_id ) );
	}

	/**
	 * Get existsing thread which matches the recipients
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param  array   $recipient_ids
	 * @param  integer $sender
	 */
	public static function get_existing_thread( $recipient_ids, $sender = 0 ) {
		global $wpdb;

		$bp = buddypress();

		// add the sender into the recipient list and order by id ascending
		$recipient_ids[] = $sender;
		$recipient_ids   = array_filter( array_unique( array_values( $recipient_ids ) ) );
		sort( $recipient_ids );

		$results = $wpdb->get_results(
			$sql = $wpdb->prepare(
				"SELECT
				r.thread_id as thread_id,
				GROUP_CONCAT(DISTINCT user_id ORDER BY user_id separator ',') as recipient_list,
				MAX(m.date_sent) AS date_sent
			FROM {$bp->messages->table_name_recipients} r
			INNER JOIN {$bp->messages->table_name_messages} m ON m.thread_id = r.thread_id
			GROUP BY r.thread_id
			HAVING recipient_list = %s
			ORDER BY date_sent DESC
			LIMIT 1
			",
				implode( ',', $recipient_ids )
			)
		);

		if ( ! $results ) {
			return null;
		}

		$thread_id = $results[0]->thread_id;

		if ( ! $is_active_recipient = BP_Messages_Thread::is_thread_recipient( $thread_id, $sender ) ) {
			return null;
		}

		return $thread_id;
	}

	/**
	 * Get existsing threads which matches the recipients
	 *
	 * @since BuddyBoss 1.2.9
	 *
	 * @param  array   $recipient_ids
	 * @param  integer $sender
	 */
	public static function get_existing_threads( $recipient_ids, $sender = 0 ) {
		global $wpdb;

		$bp = buddypress();

		// add the sender into the recipient list and order by id ascending
		$recipient_ids[] = $sender;
		$recipient_ids   = array_filter( array_unique( array_values( $recipient_ids ) ) );
		sort( $recipient_ids );

		$results = $wpdb->get_results(
			$sql = $wpdb->prepare(
				"SELECT
				r.thread_id as thread_id,
				GROUP_CONCAT(DISTINCT user_id ORDER BY user_id separator ',') as recipient_list,
				MAX(m.date_sent) AS date_sent
			FROM {$bp->messages->table_name_recipients} r
			INNER JOIN {$bp->messages->table_name_messages} m ON m.thread_id = r.thread_id
			GROUP BY r.thread_id
			HAVING recipient_list = %s
			ORDER BY date_sent DESC
			",
				implode( ',', $recipient_ids )
			)
		);

		if ( ! $results ) {
			return null;
		}

		return $results;
	}
}
