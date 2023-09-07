<?php
/**
 * BuddyBoss Suspend Document Folder Classes
 *
 * @since   BuddyBoss 1.5.6
 * @package BuddyBoss\Suspend
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Database interaction class for the BuddyBoss Suspend Document Folder.
 *
 * @since BuddyBoss 1.5.6
 */
class BP_Suspend_Folder extends BP_Suspend_Abstract {

	/**
	 * Item type
	 *
	 * @var string
	 */
	public static $type = 'document_folder';

	/**
	 * BP_Suspend_Folder constructor.
	 *
	 * @since BuddyBoss 1.5.6
	 */
	public function __construct() {

		$this->item_type = self::$type;

		// Manage hidden list.
		add_action( "bp_suspend_hide_{$this->item_type}", array( $this, 'manage_hidden_folder' ), 10, 3 );
		add_action( "bp_suspend_unhide_{$this->item_type}", array( $this, 'manage_unhidden_folder' ), 10, 4 );

		// Add moderation data when folder is added.
		add_action( 'bp_document_folder_after_save', array( $this, 'sync_moderation_data_on_save' ), 10, 1 );

		// Delete moderation data when document folder is deleted.
		add_action( 'bp_document_folder_after_delete', array( $this, 'sync_moderation_data_on_delete' ), 10, 1 );

		/**
		 * Suspend code should not add for WordPress backend or IF component is not active or Bypass argument passed for admin
		 */
		if ( ( is_admin() && ! wp_doing_ajax() ) || self::admin_bypass_check() ) {
			return;
		}

		add_filter( 'bp_document_folder_get_join_sql', array( $this, 'update_join_sql' ), 10, 2 );
		add_filter( 'bp_document_folder_get_where_conditions', array( $this, 'update_where_sql' ), 10, 2 );

		add_filter( 'bp_document_search_join_sql_folder', array( $this, 'update_join_sql' ), 10 );
		add_filter( 'bp_document_search_where_conditions_folder', array( $this, 'update_where_sql' ), 10, 2 );
	}

	/**
	 * Get Blocked member's  folder ids
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int    $member_id Member id.
	 * @param string $action    Action name to perform.
	 * @param int    $page      Number of page.
	 *
	 * @return array
	 */
	public static function get_member_folder_ids( $member_id, $action = '', $page = - 1 ) {
		$folder_ids = array();

		$args = array(
			'moderation_query' => false,
			'per_page'         => 0,
			'fields'           => 'ids',
			'user_id'          => $member_id,
		);

		if ( $page > 0 ) {
			$args['per_page'] = self::$item_per_page;
			$args['page']     = $page;
		}

		$folders = BP_Document_Folder::get( $args );

		if ( ! empty( $folders['folders'] ) ) {
			$folder_ids = $folders['folders'];
		}

		if ( 'hide' === $action && ! empty( $folder_ids ) ) {
			foreach ( $folder_ids as $k => $folder_id ) {
				if ( BP_Core_Suspend::check_suspended_content( $folder_id, self::$type, true ) ) {
					unset( $folder_ids[ $k ] );
				}
			}
		}

		return $folder_ids;
	}

	/**
	 * Get Blocked group's  folder ids
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int $group_id Group id.
	 * @param int $page     Number of page.
	 *
	 * @return array
	 */
	public static function get_group_folder_ids( $group_id, $page = - 1 ) {
		$folder_ids = array();

		$args = array(
			'moderation_query' => false,
			'per_page'         => 0,
			'fields'           => 'ids',
			'group_id'         => $group_id,
		);

		if ( $page > 0 ) {
			$args['per_page'] = self::$item_per_page;
			$args['page']     = $page;
		}

		$folders = BP_Document_Folder::get( $args );

		if ( ! empty( $folders['folders'] ) ) {
			$folder_ids = $folders['folders'];
		}

		return $folder_ids;
	}

	/**
	 * Prepare folder Join SQL query to filter blocked folder
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param string $join_sql Folder Join sql.
	 * @param array  $args     Query arguments.
	 *
	 * @return string Join sql
	 */
	public function update_join_sql( $join_sql, $args = array() ) {

		if ( isset( $args['moderation_query'] ) && false === $args['moderation_query'] ) {
			return $join_sql;
		}

		$join_sql .= $this->exclude_joint_query( 'f.id' );

		/**
		 * Filters the hidden folder Where SQL statement.
		 *
		 * @since BuddyBoss 1.5.6
		 *
		 * @param array $join_sql Join sql query
		 * @param array $class    Current class object.
		 */
		$join_sql = apply_filters( 'bp_suspend_document_folder_get_join', $join_sql, $this );

		return $join_sql;
	}

	/**
	 * Prepare folder Where SQL query to filter blocked folder
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param array $where_conditions Folder Where sql.
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
		 * Filters the hidden folder Where SQL statement.
		 *
		 * @since BuddyBoss 1.5.6
		 *
		 * @param array $where Query to hide suspended user's folder.
		 * @param array $class current class object.
		 */
		$where = apply_filters( 'bp_suspend_document_folder_get_where_conditions', $where, $this );

		if ( ! empty( array_filter( $where ) ) ) {
			$exclude_group_sql = '';
			// Allow group medias from blocked/suspended users.
			if ( bp_is_active( 'groups' ) ) {
				$exclude_group_sql = ' OR f.privacy = "grouponly" ';
			}

			$where_conditions['suspend_where'] = '( ( ' . implode( ' AND ', $where ) . ' ) ' . $exclude_group_sql . ' )';
		}

		return $where_conditions;
	}

	/**
	 * Hide related content of folder
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int      $folder_id     Folder id.
	 * @param int|null $hide_sitewide Item hidden sitewide or user specific.
	 * @param array    $args          Parent args.
	 */
	public function manage_hidden_folder( $folder_id, $hide_sitewide, $args = array() ) {
		global $bb_background_updater;

		if ( empty( $folder_id ) ) {
			return;
		}

		$suspend_args = bp_parse_args(
			$args,
			array(
				'item_id'   => $folder_id,
				'item_type' => self::$type,
			)
		);

		if ( ! is_null( $hide_sitewide ) ) {
			$suspend_args['hide_sitewide'] = $hide_sitewide;
		}

		$suspend_args = self::validate_keys( $suspend_args );

		$group_name_args = array_merge(
			$suspend_args,
			array(
				'custom_action' => 'hide',
			)
		);
		$group_name      = $this->bb_moderation_get_action_type( $group_name_args );

		BP_Core_Suspend::add_suspend( $suspend_args );

		$args['parent_id'] = ! empty( $args['parent_id'] ) ? $args['parent_id'] : $this->item_type . '_' . $folder_id;

		if ( $this->background_disabled ) {
			$this->hide_related_content( $folder_id, $hide_sitewide, $args );
		} else {
			$bb_background_updater->data(
				array(
					'type'              => $this->item_type,
					'group'             => $group_name,
					'data_id'           => $folder_id,
					'secondary_data_id' => $args['parent_id'],
					'callback'          => array( $this, 'hide_related_content' ),
					'args'              => array( $folder_id, $hide_sitewide, $args ),
				),
			);
			$bb_background_updater->save()->schedule_event();
		}
	}

	/**
	 * Un-hide related content of folder
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int      $folder_id     Folder id.
	 * @param int|null $hide_sitewide Item hidden sitewide or user specific.
	 * @param int      $force_all     Un-hide for all users.
	 * @param array    $args          Parent args.
	 */
	public function manage_unhidden_folder( $folder_id, $hide_sitewide, $force_all, $args = array() ) {
		global $bb_background_updater;

		if ( empty( $folder_id ) ) {
			return;
		}

		$suspend_args = bp_parse_args(
			$args,
			array(
				'item_id'   => $folder_id,
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
			$folder_author_id = BP_Moderation_Folder::get_content_owner_id( $folder_id );
			if ( isset( $suspend_args['blocked_user'] ) && $folder_author_id === $suspend_args['blocked_user'] ) {
				unset( $suspend_args['blocked_user'] );
			}
		}

		$suspend_args = self::validate_keys( $suspend_args );

		$group_name_args = array_merge(
			$suspend_args,
			array(
				'custom_action' => 'unhide',
			)
		);
		$group_name      = $this->bb_moderation_get_action_type( $group_name_args );

		BP_Core_Suspend::remove_suspend( $suspend_args );

		$args['parent_id'] = ! empty( $args['parent_id'] ) ? $args['parent_id'] : $this->item_type . '_' . $folder_id;

		if ( $this->background_disabled ) {
			$this->unhide_related_content( $folder_id, $hide_sitewide, $force_all, $args );
		} else {
			$bb_background_updater->data(
				array(
					'type'              => $this->item_type,
					'group'             => $group_name,
					'data_id'           => $folder_id,
					'secondary_data_id' => $args['parent_id'],
					'callback'          => array( $this, 'unhide_related_content' ),
					'args'              => array( $folder_id, $hide_sitewide, $force_all, $args ),
				),
			);
			$bb_background_updater->save()->schedule_event();
		}
	}

	/**
	 * Get Folder's comment ids
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int   $folder_id Folder id.
	 * @param array $args      Parent args.
	 *
	 * @return array
	 */
	protected function get_related_contents( $folder_id, $args = array() ) {
		return array();
	}

	/**
	 * Update the suspend table to add new entries.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param BP_Document_Folder $document_folder Current instance of document folder item being saved. Passed by reference.
	 */
	public function sync_moderation_data_on_save( $document_folder ) {

		if ( empty( $document_folder ) || empty( $document_folder->id ) ) {
			return;
		}

		$sub_items     = bp_moderation_get_sub_items( $document_folder->id, BP_Moderation_Folder::$moderation_type );
		$item_sub_id   = isset( $sub_items['id'] ) ? $sub_items['id'] : $document_folder->id;
		$item_sub_type = isset( $sub_items['type'] ) ? $sub_items['type'] : BP_Moderation_Folder::$moderation_type;

		$suspended_record = BP_Core_Suspend::get_recode( $item_sub_id, $item_sub_type );

		if ( empty( $suspended_record ) ) {
			$suspended_record = BP_Core_Suspend::get_recode( $document_folder->user_id, BP_Moderation_Members::$moderation_type );
		}

		if ( empty( $suspended_record ) ) {
			return;
		}

		self::handle_new_suspend_entry( $suspended_record, $document_folder->id, $document_folder->user_id );
	}

	/**
	 * Update the suspend table to delete the folder.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param array $folders Array of document folders.
	 */
	public function sync_moderation_data_on_delete( $folders ) {

		if ( empty( $folders ) ) {
			return;
		}

		foreach ( $folders as $folder ) {
			BP_Core_Suspend::delete_suspend( $folder->id, $this->item_type );
		}
	}
}
