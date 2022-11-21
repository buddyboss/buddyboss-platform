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
 * BuddyPress Notices class.
 *
 * Use this class to create, activate, deactivate or delete notices.
 *
 * @since BuddyPress 1.0.0
 */
class BP_Messages_Notice {
	/**
	 * The notice ID.
	 *
	 * @var int
	 */
	public $id = null;

	/**
	 * The subject line for the notice.
	 *
	 * @var string
	 */
	public $subject;

	/**
	 * The content of the notice.
	 *
	 * @var string
	 */
	public $message;

	/**
	 * The date the notice was created.
	 *
	 * @var string
	 */
	public $date_sent;

	/**
	 * Whether the notice is active or not.
	 *
	 * @var int
	 */
	public $is_active;

	/**
	 * Constructor.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param int|null $id Optional. The ID of the current notice.
	 */
	public function __construct( $id = null ) {
		if ( $id ) {
			$this->id = (int) $id;
			$this->populate();
		}
	}

	/**
	 * Populate method.
	 *
	 * Runs during constructor.
	 *
	 * @since BuddyPress 1.0.0
	 */
	public function populate() {
		$notices = self::get(
			array(
				'include'  => $this->id,
				'per_page' => 1,
				'orderby'  => 'id',
			)
		);

		$notice = ( ! empty( $notices['notices'] ) ? current( $notices['notices'] ) : false );

		if ( ! empty( $notice ) ) {
			$this->subject   = $notice->subject;
			$this->message   = $notice->message;
			$this->date_sent = $notice->date_sent;
			$this->is_active = (int) $notice->is_active;
		}
	}

	/**
	 * Saves a notice.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @return bool
	 */
	public function save() {
		global $wpdb;

		$bp = buddypress();

		$this->subject = apply_filters( 'messages_notice_subject_before_save', $this->subject, $this->id );
		$this->message = apply_filters( 'messages_notice_message_before_save', $this->message, $this->id );

		/**
		 * Fires before the current message notice item gets saved.
		 *
		 * Please use this hook to filter the properties above. Each part will be passed in.
		 *
		 * @since BuddyPress 1.0.0
		 *
		 * @param BP_Messages_Notice $this Current instance of the message notice item being saved. Passed by reference.
		 */
		do_action_ref_array( 'messages_notice_before_save', array( &$this ) );

		if ( empty( $this->id ) ) {
			$sql = $wpdb->prepare( "INSERT INTO {$bp->messages->table_name_notices} (subject, message, date_sent, is_active) VALUES (%s, %s, %s, %d)", $this->subject, $this->message, $this->date_sent, $this->is_active );
		} else {
			$sql = $wpdb->prepare( "UPDATE {$bp->messages->table_name_notices} SET subject = %s, message = %s, is_active = %d WHERE id = %d", $this->subject, $this->message, $this->is_active, $this->id );
		}

		if ( ! $wpdb->query( $sql ) ) {
			return false;
		}

		if ( ! $id = $this->id ) {
			$id = $wpdb->insert_id;
		}

		// Now deactivate all notices apart from the new one.
		$wpdb->query( $wpdb->prepare( "UPDATE {$bp->messages->table_name_notices} SET is_active = 0 WHERE id != %d", $id ) );

		bp_update_user_last_activity( bp_loggedin_user_id(), bp_core_current_time() );

		/**
		 * Fires after the current message notice item has been saved.
		 *
		 * @since BuddyPress 1.0.0
		 *
		 * @param BP_Messages_Notice $this Current instance of the message item being saved. Passed by reference.
		 */
		do_action_ref_array( 'messages_notice_after_save', array( &$this ) );

		return true;
	}

	/**
	 * Activates a notice.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @return bool
	 */
	public function activate() {
		$this->is_active = 1;
		return (bool) $this->save();
	}

	/**
	 * Deactivates a notice.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @return bool
	 */
	public function deactivate() {
		$this->is_active = 0;
		return (bool) $this->save();
	}

	/**
	 * Deletes a notice.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @return bool
	 */
	public function delete() {
		global $wpdb;

		/**
		 * Fires before the current message item has been deleted.
		 *
		 * @since BuddyPress 1.0.0
		 *
		 * @param BP_Messages_Notice $this Current instance of the message notice item being deleted.
		 */
		do_action( 'messages_notice_before_delete', $this );

		$bp  = buddypress();
		$sql = $wpdb->prepare( "DELETE FROM {$bp->messages->table_name_notices} WHERE id = %d", $this->id );

		if ( ! $wpdb->query( $sql ) ) {
			return false;
		}

		/**
		 * Fires after the current message item has been deleted.
		 *
		 * @since BuddyPress 2.8.0
		 *
		 * @param BP_Messages_Notice $this Current instance of the message notice item being deleted.
		 */
		do_action( 'messages_notice_after_delete', $this );

		return true;
	}

	/** Static Methods ********************************************************/

	/**
	 * Query for Notices.
	 *
	 * @since BuddyBoss 1.5.4
	 *
	 * @param array $args        {
	 * Array of parameters. All items are optional.
	 *
	 * @type string       $orderby     Optional. Property to sort by. Default: 'date_sent'.
	 * @type string       $order       Optional. Sort order. 'ASC' or 'DESC'. Default: 'DESC'.
	 * @type int          $per_page    Optional. Number of items to return perpage of results. Default: 20.
	 * @type int          $page        Optional. Page offset of results to return. Default: 1.
	 * @type string       $fields      Which fields to return. Specify 'ids' to fetch a list of IDs. Default: 'all'
	 *                                 (return BP_Groups_Group objects).
	 * @type array|string $include     Array or comma-separated list of notice ids to limit results to.
	 * @type array|string $exclude     Array or comma-separated list of notice ids that will be
	 * @type int          $is_active   Fetch only Active notice or not Default null
	 * @type int          $count_total Total count of all Notices matching non-paginated query params.
	 * }
	 *
	 * @return array {
	 * @type array        $notices     Array of notice objects returned by the
	 *                                    paginated query. (IDs only if `fields` is set
	 *                                    to `ids`.)
	 * @type int          $total       Total count of all notices matching non-
	 *                                        paginated query params.
	 * }
	 */
	public static function get( $args = array() ) {
		global $wpdb;

		$bp = buddypress();

		$defaults = array(
			'orderby'     => 'date_sent',
			'order'       => 'DESC',
			'per_page'    => 20,
			'page'        => 1,
			'include'     => false,
			'exclude'     => false,
			'fields'      => 'all',
			'is_active'   => null,
			'count_total' => false,
		);

		$r = bp_parse_args( $args, $defaults, 'bp_messages_notice_get' );

		$sql = array(
			'select'     => 'SELECT DISTINCT mn.id',
			'from'       => "{$bp->messages->table_name_notices} mn",
			'where'      => '',
			'orderby'    => '',
			'pagination' => '',
		);

		$where_conditions = array();

		if ( ! empty( $r['include'] ) ) {
			$include                     = implode( ',', wp_parse_id_list( $r['include'] ) );
			$where_conditions['include'] = "mn.id IN ({$include})";
		}

		if ( ! empty( $r['exclude'] ) ) {
			$exclude                     = implode( ',', wp_parse_id_list( $r['exclude'] ) );
			$where_conditions['exclude'] = "mn.id NOT IN ({$exclude})";
		}

		if ( isset( $r['is_active'] ) ) {
			$where_conditions['is_active'] = "mn.is_active = {$r['is_active']}";
		}

		/* Order/orderby ********************************************/

		$order   = $r['order'];
		$orderby = $r['orderby'];

		// Sanitize 'order'.
		$order = bp_esc_sql_order( $order );

		/**
		 * Filters the converted 'orderby' term.
		 *
		 * @param string $value   Converted 'orderby' term.
		 * @param string $orderby Original orderby value.
		 *
		 * @since BuddyBoss 1.5.4
		 */
		$orderby = apply_filters( 'bp_messages_notice_get_orderby', self::convert_orderby_to_order_by_term( $orderby ), $orderby );

		$sql['orderby'] = "ORDER BY {$orderby} {$order}";

		if ( ! empty( $r['per_page'] ) && ! empty( $r['page'] ) && - 1 !== $r['per_page'] ) {
			$sql['pagination'] = $wpdb->prepare( 'LIMIT %d, %d', intval( ( $r['page'] - 1 ) * $r['per_page'] ), intval( $r['per_page'] ) );
		}

		/**
		 * Filters the Where SQL statement.
		 *
		 * @param array $r                Array of parsed arguments for the get method.
		 * @param array $where_conditions Where conditions SQL statement.
		 *
		 * @since BuddyBoss 1.5.4
		 */
		$where_conditions = apply_filters( 'bp_messages_notice_get_where_conditions', $where_conditions, $r );

		$where = '';
		if ( ! empty( $where_conditions ) ) {
			$sql['where'] = implode( ' AND ', $where_conditions );
			$where        = "WHERE {$sql['where']}";
		}

		/**
		 * Filters the From SQL statement.
		 *
		 * @param array  $r   Array of parsed arguments for the get method.
		 * @param string $sql From SQL statement.
		 *
		 * @since BuddyBoss 1.5.4
		 */
		$sql['from'] = apply_filters( 'bp_messages_notice_get_join_sql', $sql['from'], $r );

		$paged_notices_sql = "{$sql['select']} FROM {$sql['from']} {$where} {$sql['orderby']} {$sql['pagination']}";

		/**
		 * Filters the pagination SQL statement.
		 *
		 * @param string $value Concatenated SQL statement.
		 * @param array  $sql   Array of SQL parts before concatenation.
		 * @param array  $r     Array of parsed arguments for the get method.
		 *
		 * @since BuddyBoss 1.5.4
		 */
		$paged_notices_sql = apply_filters( 'bp_messages_notice_get_paged_sql', $paged_notices_sql, $sql, $r );

		$paged_notice_ids = $wpdb->get_col( $paged_notices_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		$paged_notices = array();

		if ( 'ids' === $r['fields'] ) {
			// We only want the IDs.
			$paged_notices = array_map( 'intval', $paged_notice_ids );
		} elseif ( ! empty( $paged_notice_ids ) ) {
			$notice_ids_sql      = implode( ',', array_map( 'intval', $paged_notice_ids ) );
			$notice_data_objects = $wpdb->get_results( "SELECT mn.* FROM {$bp->messages->table_name_notices} mn WHERE mn.id IN ({$notice_ids_sql})" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			foreach ( (array) $notice_data_objects as $mdata ) {
				$notice_data_objects[ $mdata->id ] = $mdata;
			}
			foreach ( $paged_notice_ids as $paged_notice_id ) {
				$paged_notices[] = $notice_data_objects[ $paged_notice_id ];
			}
		}

		$retval = array(
			'notices' => $paged_notices,
			'total'   => 0,
		);

		if ( ! empty( $r['count_total'] ) ) {
			// Find the total number of groups in the results set.
			$total_notices_sql = "SELECT COUNT(DISTINCT mn.id) FROM {$sql['from']} $where";

			/**
			 * Filters the SQL used to retrieve total group results.
			 *
			 * @param string $t_sql     Concatenated SQL statement used for retrieving total group results.
			 * @param array  $total_sql Array of SQL parts for the query.
			 * @param array  $r         Array of parsed arguments for the get method.
			 *
			 * @since BuddyPress 1.5.0
			 */
			$total_notices_sql = apply_filters( 'bp_messages_notice_get_total_sql', $total_notices_sql, $sql, $r );

			$total_notices   = (int) $wpdb->get_var( $total_notices_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$retval['total'] = $total_notices;
		}

		return $retval;
	}

	/**
	 * Convert the 'orderby' param into a proper SQL term/column.
	 *
	 * @since BuddyBoss 1.5.4
	 *
	 * @param string $orderby Orderby term as passed to get().
	 *
	 * @return string $order_by_term SQL-friendly orderby term.
	 */
	protected static function convert_orderby_to_order_by_term( $orderby ) {
		switch ( $orderby ) {
			case 'id':
				$order_by_term = 'mn.id';
				break;
			case 'date_sent':
			default:
				$order_by_term = 'mn.date_sent';
				break;
		}

		return $order_by_term;
	}


	/**
	 * Pulls up a list of notices.
	 *
	 * To get all notices, pass a value of -1 to pag_num.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param array $args {
	 *     Array of parameters.
	 *     @type int $pag_num  Number of notices per page. Defaults to 20.
	 *     @type int $pag_page The page number.  Defaults to 1.
	 * }
	 * @return object List of notices to display.
	 */
	public static function get_notices( $args = array() ) {
		$r = bp_parse_args(
			$args,
			array(
				'pag_num'  => 20, // Number of notices per page.
				'pag_page' => 1,   // Page number.
			)
		);

		$notices = self::get(
			array(
				'per_page' => $r['pag_num'],
				'page'     => $r['pag_page'],
			)
		);

		$notices = ! empty( $notices['notices'] ) ? $notices['notices'] : array();

		// Integer casting.
		if ( ! empty( $notices ) ) {
			foreach ( (array) $notices as $key => $data ) {
				$notices[ $key ]->id        = (int) $notices[ $key ]->id;
				$notices[ $key ]->is_active = (int) $notices[ $key ]->is_active;
			}
		}

		/**
		 * Filters the array of notices, sorted by date and paginated.
		 *
		 * @since BuddyPress 2.8.0
		 *
		 * @param array $r Array of parameters.
		 */
		return apply_filters( 'messages_notice_get_notices', $notices, $r );
	}

	/**
	 * Returns the total number of recorded notices.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @return int
	 */
	public static function get_total_notice_count() {
		$notices = self::get(
			array(
				'fields'      => 'ids',
				'per_page'    => 1,
				'count_total' => true,
			)
		);

		$notice_count = ! empty( $notices['total'] ) ? $notices['total'] : 0;

		/**
		 * Filters the total number of notices.
		 *
		 * @since BuddyPress 2.8.0
		 */
		return (int) apply_filters( 'messages_notice_get_total_notice_count', $notice_count );
	}

	/**
	 * Returns the active notice that should be displayed on the front end.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @return object The BP_Messages_Notice object.
	 */
	public static function get_active() {
		$notice = wp_cache_get( 'active_notice', 'bp_messages' );

		if ( false === $notice ) {
			$notices = self::get(
				array(
					'fields'    => 'ids',
					'orderby'   => 'id',
					'is_active' => 1,
					'per_page'  => 1,
				)
			);

			$notice_id = ! empty( $notices['notices'] ) ? current( $notices['notices'] ) : false;
			$notice    = new BP_Messages_Notice( $notice_id );

			wp_cache_set( 'active_notice', $notice, 'bp_messages' );
		}

		/**
		 * Gives ability to filter the active notice that should be displayed on the front end.
		 *
		 * @since BuddyPress 2.8.0
		 */
		return apply_filters( 'messages_notice_get_active', $notice );
	}
}
