<?php
/**
 * BuddyBoss Moderation Messages Classes
 *
 * @package BuddyBoss\Moderation
 * @since BuddyBoss 1.5.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Database interaction class for the BuddyBoss moderation Messages.
 *
 * @since BuddyBoss 1.5.4
 */
class BP_Moderation_Messages extends BP_Moderation_Abstract {

	/**
	 * Item type
	 *
	 * @var string
	 */
	public static $moderation_type = 'message';

	/**
	 * BP_Moderation_Group constructor.
	 *
	 * @since BuddyBoss 1.5.4
	 */
	public function __construct() {

		/**
		 * Moderation code should not add for WordPress backend & IF component is not active
		 */
		if ( ( is_admin() && ! wp_doing_ajax() ) || ! bp_is_active( 'messages' ) ) {
			return;
		}

		$this->item_type = self::$moderation_type;

		// Message
		add_filter( 'bp_messages_message_get_join_sql', array( $this, 'update_join_sql' ), 10, 2 );
		add_filter( 'bp_messages_message_get_where_conditions', array( $this, 'update_where_sql' ), 10, 2 );

		// Recipient
		add_filter( 'bp_messages_recipient_get_join_sql', array( $this, 'update_join_sql' ), 10, 2 );
		add_filter( 'bp_messages_recipient_get_where_conditions', array( $this, 'update_where_sql' ), 10, 2 );
	}

	/**
	 * Prepare Groups Join SQL query to filter blocked Messages
	 *
	 * @since BuddyBoss 1.5.4
	 *
	 * @param string $join_sql Messages Join sql.
	 * @param array  $args     Messages args.
	 *
	 * @return string Join sql
	 */
	public function update_join_sql( $join_sql, $args ) {

		if ( isset( $args['moderation_query'] ) && false === $args['moderation_query'] ) {
			return $join_sql;
		}

		$actionName = current_filter();

		$item_id_field = 'm.thread_id';
		if ( 'bp_messages_recipient_get_join_sql' === $actionName ) {
			$item_id_field = 'r.thread_id';
		}

		$join_sql .= $this->exclude_joint_query( $item_id_field );

		return $join_sql;
	}

	/**
	 * Prepare Messages Where SQL query to filter blocked Messages
	 *
	 * @since BuddyBoss 1.5.4
	 *
	 * @param string|array $where_conditions Messages Where sql.
	 * @param array        $args             Messages args.
	 *
	 * @return mixed Where SQL
	 */
	public function update_where_sql( $where_conditions, $args ) {

		if ( isset( $args['moderation_query'] ) && false === $args['moderation_query'] ) {
			return $where_conditions;
		}

		$actionName = current_filter();

		$where                   = array();
		$where['messages_where'] = $this->exclude_where_query();

		/**
		 * Exclude block member activity
		 */
		$members_where = $this->exclude_member_message_query();
		if ( $members_where ) {
			$where['members_where'] = $members_where;
		}

		/**
		 * Filters the Messages Moderation Where SQL statement.
		 *
		 * @since BuddyBoss 1.5.4
		 *
		 * @param array $where array of Messages moderation where query.
		 */
		$where = apply_filters( 'bp_moderation_messages_get_where_conditions', $where );

		if ( 'bp_messages_recipient_get_where_conditions' === $actionName ) {
			$where_conditions .= ' AND ( ' . implode( ' AND ', $where ) . ' )';
		} else {
			$where_conditions['moderation_where'] = '( ' . implode( ' AND ', $where ) . ' )';
		}

		return $where_conditions;
	}

	/**
	 * Get SQL for Exclude Blocked Members related Messages
	 *
	 * @return string|bool
	 */
	private function exclude_member_message_query() {
		$actionName = current_filter();

		$user_id_field = 'm.sender_id';
		if ( 'bp_messages_recipient_get_join_sql' === $actionName ) {
			$user_id_field = 'r.user_id';
		}

		$sql                = false;
		$hidden_members_ids = BP_Moderation_Members::get_sitewide_hidden_ids();
		if ( ! empty( $hidden_members_ids ) ) {
			$sql = "( {$user_id_field} NOT IN ( " . implode( ',', $hidden_members_ids ) . ' ) )';
		}

		return $sql;
	}

	/**
	 * Get Blocked Message threads ids
	 *
	 * @return array
	 */
	public static function get_sitewide_hidden_ids() {
		return self::get_sitewide_hidden_item_ids( self::$moderation_type );
	}

	/**
	 * Get Blocked Messages ids
	 *
	 * @return array
	 */
	public static function get_sitewide_messages_hidden_ids() {
		$messages_ids = array();
		$threads = self::get_sitewide_hidden_ids();
		$results = BP_Messages_Message::get( array(
			'fields'           => 'ids',
			'include_threads'  => $threads,
			'moderation_query' => false,
		) );
		if ( ! empty( $results['messages'] ) ){
			$messages_ids = $results['messages'];
		}
		return $messages_ids;
	}

}
