<?php
/**
 * BuddyBoss Suspend Document Classes
 *
 * @since   BuddyBoss 1.5.6
 * @package BuddyBoss\Suspend
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Database interaction class for the BuddyBoss Suspend Document.
 *
 * @since BuddyBoss 1.5.6
 */
class BP_Suspend_Document extends BP_Suspend_Abstract {

	/**
	 * Item type
	 *
	 * @var string
	 */
	public static $type = 'document';

	/**
	 * BP_Suspend_Document constructor.
	 *
	 * @since BuddyBoss 1.5.6
	 */
	public function __construct() {

		$this->item_type = self::$type;

		// Manage hidden list.
		add_action( "bp_suspend_hide_{$this->item_type}", array( $this, 'manage_hidden_document' ), 10, 3 );
		add_action( "bp_suspend_unhide_{$this->item_type}", array( $this, 'manage_unhidden_document' ), 10, 4 );

		// Add moderation data when document is added.
		add_action( 'bp_document_after_save', array( $this, 'sync_moderation_data_on_save' ), 10, 1 );

		// Delete moderation data when document is deleted.
		add_action( 'bp_document_after_delete', array( $this, 'sync_moderation_data_on_delete' ), 10, 1 );

		/**
		 * Suspend code should not add for WordPress backend or IF component is not active or Bypass argument passed for admin
		 */
		if ( ( is_admin() && ! wp_doing_ajax() ) || self::admin_bypass_check() ) {
			return;
		}

		add_filter( 'bp_document_get_join_sql', array( $this, 'update_join_sql' ), 10, 2 );
		add_filter( 'bp_document_get_where_conditions', array( $this, 'update_where_sql' ), 10, 2 );

		add_filter( 'bp_document_search_join_sql_document', array( $this, 'update_join_sql' ), 10 );
		add_filter( 'bp_document_search_where_conditions_document', array( $this, 'update_where_sql' ), 10, 2 );
	}

	/**
	 * Get Blocked member's document ids
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int $member_id member id.
	 *
	 * @return array
	 */
	public static function get_member_document_ids( $member_id ) {
		$document_ids = array();

		$documents = BP_Document::get(
			array(
				'moderation_query' => false,
				'per_page'         => 0,
				'fields'           => 'ids',
				'user_id'          => $member_id,
			)
		);

		if ( ! empty( $documents['documents'] ) ) {
			$document_ids = $documents['documents'];
		}

		return $document_ids;
	}

	/**
	 * Get Blocked group's document ids
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int $group_id group id.
	 *
	 * @return array
	 */
	public static function get_group_document_ids( $group_id ) {
		$document_ids = array();

		$documents = BP_Document::get(
			array(
				'moderation_query' => false,
				'per_page'         => 0,
				'fields'           => 'ids',
				'group_id'         => $group_id,
			)
		);

		if ( ! empty( $documents['documents'] ) ) {
			$document_ids = $documents['documents'];
		}

		return $document_ids;
	}

	/**
	 * Get Document ids of blocked item [ Forums/topics/replies/activity etc ] from meta
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int    $item_id  item id.
	 * @param string $function Function Name to get meta.
	 *
	 * @return array Document IDs
	 */
	public static function get_document_ids_meta( $item_id, $function = 'get_post_meta' ) {
		$document_ids = array();

		if ( function_exists( $function ) ) {
			if ( ! empty( $item_id ) ) {
				$post_document = $function( $item_id, 'bp_document_ids', true );
				if ( empty( $post_document ) ){
					$post_document = BP_Document::get_activity_document_id( $item_id );
				}

				if ( ! empty( $post_document )  ){
					$document_ids  = wp_parse_id_list( $post_document );
				}
			}
		}

		return $document_ids;
	}

	/**
	 * Prepare document Join SQL query to filter blocked Document
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param string $join_sql Document Join sql.
	 * @param array  $args     Query arguments.
	 *
	 * @return string Join sql
	 */
	public function update_join_sql( $join_sql, $args = array() ) {

		if ( isset( $args['moderation_query'] ) && false === $args['moderation_query'] ) {
			return $join_sql;
		}

		$join_sql .= $this->exclude_joint_query( 'd.id' );

		/**
		 * Filters the hidden document Where SQL statement.
		 *
		 * @since BuddyBoss 1.5.6
		 *
		 * @param array $join_sql Join sql query
		 * @param array $class    current class object.
		 */
		$join_sql = apply_filters( 'bp_suspend_document_get_join', $join_sql, $this );

		return $join_sql;
	}

	/**
	 * Prepare document Where SQL query to filter blocked Document
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param array $where_conditions Document Where sql.
	 * @param array $args             Query arguments.
	 *
	 * @return mixed Where SQL
	 */
	public function update_where_sql( $where_conditions, $args = array() ) {
		if ( isset( $args['moderation_query'] ) && false === $args['moderation_query'] ) {
			return $where_conditions;
		}

		$where                  = array();
		$where['suspend_where'] = $this->exclude_where_query();

		/**
		 * Filters the hidden document Where SQL statement.
		 *
		 * @since BuddyBoss 1.5.6
		 *
		 * @param array $where Query to hide suspended user's document.
		 * @param array $class current class object.
		 */
		$where = apply_filters( 'bp_suspend_document_get_where_conditions', $where, $this );

		if ( ! empty( array_filter( $where ) ) ) {
			$where_conditions['suspend_where'] = '( ' . implode( ' AND ', $where ) . ' )';
		}

		return $where_conditions;
	}

	/**
	 * Hide related content of document
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int      $document_id   document id.
	 * @param int|null $hide_sitewide item hidden sitewide or user specific.
	 * @param array    $args          parent args.
	 */
	public function manage_hidden_document( $document_id, $hide_sitewide, $args = array() ) {
		global $bp_background_updater;

		$suspend_args = wp_parse_args(
			$args,
			array(
				'item_id'   => $document_id,
				'item_type' => self::$type,
			)
		);

		if ( ! is_null( $hide_sitewide ) ) {
			$suspend_args['hide_sitewide'] = $hide_sitewide;
		}

		$suspend_args = self::validate_keys( $suspend_args );

		BP_Core_Suspend::add_suspend( $suspend_args );

		if ( $this->backgroup_diabled || ! empty( $args ) ) {
			$this->hide_related_content( $document_id, $hide_sitewide, $args );
		} else {
			$bp_background_updater->push_to_queue(
				array(
					'callback' => array( $this, 'hide_related_content' ),
					'args'     => array( $document_id, $hide_sitewide, $args ),
				)
			);
			$bp_background_updater->save()->schedule_event();
		}
	}

	/**
	 * Un-hide related content of document
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int      $document_id   document id.
	 * @param int|null $hide_sitewide item hidden sitewide or user specific.
	 * @param int      $force_all     un-hide for all users.
	 * @param array    $args          parent args.
	 */
	public function manage_unhidden_document( $document_id, $hide_sitewide, $force_all, $args = array() ) {
		global $bp_background_updater;

		$suspend_args = wp_parse_args(
			$args,
			array(
				'item_id'   => $document_id,
				'item_type' => self::$type,
			)
		);

		if ( ! is_null( $hide_sitewide ) ) {
			$suspend_args['hide_sitewide'] = $hide_sitewide;
		}

		if (
			isset( $suspend_args['author_compare'] ) &&
			true === (bool) $suspend_args['author_compare'] &&
			isset( $suspend_args['type'] ) &&
			$suspend_args['type'] !== self::$type
		) {
			$document_author_id = BP_Moderation_Document::get_content_owner_id( $document_id );
			if ( isset( $suspend_args['blocked_user'] ) && $document_author_id === $suspend_args['blocked_user'] ) {
				unset( $suspend_args['blocked_user'] );
			}
		}

		$suspend_args = self::validate_keys( $suspend_args );

		BP_Core_Suspend::remove_suspend( $suspend_args );

		if ( $this->backgroup_diabled || ! empty( $args ) ) {
			$this->unhide_related_content( $document_id, $hide_sitewide, $force_all, $args );
		} else {
			$bp_background_updater->push_to_queue(
				array(
					'callback' => array( $this, 'unhide_related_content' ),
					'args'     => array( $document_id, $hide_sitewide, $force_all, $args ),
				)
			);
			$bp_background_updater->save()->schedule_event();
		}
	}

	/**
	 * Get Document's comment ids
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int   $document_id Document id.
	 * @param array $args        parent args.
	 *
	 * @return array
	 */
	protected function get_related_contents( $document_id, $args = array() ) {
		return array();
	}

	/**
	 * Update the suspend table to add new entries.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param BP_Document $document Current instance of document item being saved. Passed by reference.
	 */
	public function sync_moderation_data_on_save( $document ) {

		if ( empty( $document ) || empty( $document->id ) ) {
			return;
		}

		$sub_items     = bp_moderation_get_sub_items( $document->id, BP_Moderation_Document::$moderation_type );
		$item_sub_id   = isset( $sub_items['id'] ) ? $sub_items['id'] : $document->id;
		$item_sub_type = isset( $sub_items['type'] ) ? $sub_items['type'] : BP_Moderation_Document::$moderation_type;

		$suspended_record = BP_Core_Suspend::get_recode( $item_sub_id, $item_sub_type );

		if ( empty( $suspended_record ) ) {
			$suspended_record = BP_Core_Suspend::get_recode( $document->user_id, BP_Moderation_Members::$moderation_type );
		}

		if ( empty( $suspended_record ) ) {
			return;
		}

		self::handle_new_suspend_entry( $suspended_record, $document->id, $document->user_id );
	}

	/**
	 * Update the suspend table to delete the document.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param array $documents Array of document.
	 */
	public function sync_moderation_data_on_delete( $documents ) {

		if ( empty( $documents ) ) {
			return;
		}

		foreach ( $documents as $document ) {
			BP_Core_Suspend::delete_suspend( $document->id, $this->item_type );
		}
	}
}
