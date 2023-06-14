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

		if ( bp_is_active( 'activity' ) ) {
			add_filter( 'bb_moderation_restrict_single_item_' . BP_Suspend_Activity::$type, array( $this, 'unbind_restrict_single_item' ), 10, 1 );
			add_action( 'bb_moderation_' . BP_Suspend_Activity::$type . '_before_delete_suspend', array( $this, 'update_suspend_data_on_activity_delete' ) );
			add_action( 'bb_moderation_' . BP_Suspend_Activity_Comment::$type . '_before_delete_suspend', array( $this, 'update_suspend_data_on_activity_delete' ) );
		}
	}

	/**
	 * Get Blocked member's document ids
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int    $member_id Member id.
	 * @param string $action    Action name to perform.
	 * @param int    $page      Number of page.
	 *
	 * @return array
	 */
	public static function get_member_document_ids( $member_id, $action = '', $page = - 1 ) {
		$document_ids = array();

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

		$documents = BP_Document::get( $args );

		if ( ! empty( $documents['documents'] ) ) {
			$document_ids = $documents['documents'];
		}

		if ( 'hide' === $action && ! empty( $document_ids ) ) {
			foreach ( $document_ids as $k => $document_id ) {
				if ( BP_Core_Suspend::check_suspended_content( $document_id, self::$type, true ) ) {
					unset( $document_ids[ $k ] );
				}
			}
		}

		return $document_ids;
	}

	/**
	 * Get Blocked group's document ids
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int $group_id group id.
	 * @param int $page     Number of page.
	 *
	 * @return array
	 */
	public static function get_group_document_ids( $group_id, $page = - 1 ) {
		$document_ids = array();

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

		$documents = BP_Document::get( $args );

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
	 * @param string $action   Action name to perform.
	 *
	 * @return array Document IDs
	 */
	public static function get_document_ids_meta( $item_id, $function = 'get_post_meta', $action = '' ) {
		$document_ids = array();

		if ( function_exists( $function ) ) {
			if ( ! empty( $item_id ) ) {
				$post_document = $function( $item_id, 'bp_document_ids', true );
				if ( empty( $post_document ) ) {
					$post_document = BP_Document::get_activity_document_id( $item_id );
				}

				if ( ! empty( $post_document ) ) {
					$document_ids = wp_parse_id_list( $post_document );
				}
			}
		}

		if ( 'hide' === $action && ! empty( $document_ids ) ) {
			foreach ( $document_ids as $k => $document_id ) {
				if ( BP_Core_Suspend::check_hidden_content( $document_id, self::$type, true ) ) {
					unset( $document_ids[ $k ] );
				}
			}
		}

		if ( 'unhide' === $action && ! empty( $document_ids ) ) {
			foreach ( $document_ids as $k => $document_id ) {
				if ( self::is_content_reported_hidden( $document_id, self::$type ) ) {
					unset( $document_ids[ $k ] );
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
			$exclude_group_sql = '';
			// Allow group medias from blocked/suspended users.
			if ( bp_is_active( 'groups' ) ) {
				$exclude_group_sql = ' OR d.privacy = "grouponly" ';
			}
			$exclude_group_sql .= ' OR ( d.privacy = "comment" OR d.privacy = "forums" ) ';

			$where_conditions['suspend_where'] = '( ( ' . implode( ' AND ', $where ) . ' ) ' . $exclude_group_sql . ' )';
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

		$suspend_args = bp_parse_args(
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

		if ( $this->background_disabled ) {
			$this->hide_related_content( $document_id, $hide_sitewide, $args );
		} else {
			$bp_background_updater->data(
				array(
					array(
						'callback' => array( $this, 'hide_related_content' ),
						'args'     => array( $document_id, $hide_sitewide, $args ),
					),
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

		$suspend_args = bp_parse_args(
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

		if ( $this->background_disabled ) {
			$this->unhide_related_content( $document_id, $hide_sitewide, $force_all, $args );
		} else {
			$bp_background_updater->data(
				array(
					array(
						'callback' => array( $this, 'unhide_related_content' ),
						'args'     => array( $document_id, $hide_sitewide, $force_all, $args ),
					),
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
		$action           = ! empty( $args['action'] ) ? $args['action'] : '';
		$blocked_user     = ! empty( $args['blocked_user'] ) ? $args['blocked_user'] : '';
		$page             = ! empty( $args['page'] ) ? $args['page'] : - 1;
		$related_contents = array();

		if ( $page > 1 ) {
			return $related_contents;
		}

		$document = new BP_Document( $document_id );

		if ( bp_is_active( 'activity' ) && ! empty( $document ) && ! empty( $document->activity_id ) ) {

			/**
			 * Remove pre-validate check.
			 *
			 * @since BuddyBoss 1.7.5
			 */
			do_action( 'bb_moderation_before_get_related_' . BP_Suspend_Activity::$type );

			$related_contents[ BP_Suspend_Activity_Comment::$type ] = BP_Suspend_Activity_Comment::get_activity_comment_ids( $document->activity_id );

			$activity = new BP_Activity_Activity( $document->activity_id );

			if ( ! empty( $activity ) && ! empty( $activity->type ) ) {
				if ( 'activity_comment' === $activity->type ) {
					$related_contents[ BP_Suspend_Activity_Comment::$type ][] = $activity->id;
				} else {
					$related_contents[ BP_Suspend_Activity::$type ][] = $activity->id;
				}
			}

			if ( 'hide' === $action && ! empty( $document->attachment_id ) ) {
				$attachment_id = $document->attachment_id;

				$parent_activity_id = get_post_meta( $attachment_id, 'bp_document_parent_activity_id', true );
				if ( ! empty( $parent_activity_id ) ) {
					$parent_activity  = new BP_Activity_Activity( $parent_activity_id );
					$parent_media_ids = self::get_document_ids_meta( $parent_activity_id, 'bp_activity_get_meta', $action );

					if (
						empty( $parent_media_ids ) &&
						! empty( $parent_activity ) &&
						! empty( $parent_activity->type ) &&
						empty( wp_strip_all_tags( $parent_activity->content ) )
					) {
						if ( 'activity_comment' === $parent_activity->type ) {
							$related_contents[ BP_Suspend_Activity_Comment::$type ][] = $parent_activity->id;
						} else {
							$related_contents[ BP_Suspend_Activity::$type ][] = $parent_activity->id;
						}
					}
				}
			}

			if ( 'unhide' === $action && ! empty( $document->attachment_id ) ) {
				$attachment_id      = $document->attachment_id;
				$parent_activity_id = get_post_meta( $attachment_id, 'bp_document_parent_activity_id', true );
				if ( ! empty( $parent_activity_id ) ) {
					$parent_activity = new BP_Activity_Activity( $parent_activity_id );
					if (
						! empty( $parent_activity ) &&
						! empty( $parent_activity->type )
					) {
						if ( 'activity_comment' === $parent_activity->type ) {
							$related_contents[ BP_Suspend_Activity_Comment::$type ][] = $parent_activity->id;
						} else {
							$related_contents[ BP_Suspend_Activity::$type ][] = $parent_activity->id;
						}
					}
				}
			}

			/**
			 * Added pre-validate check.
			 *
			 * @since BuddyBoss 1.7.5
			 */
			do_action( 'bb_moderation_after_get_related_' . BP_Suspend_Activity::$type );
		}

		return $related_contents;
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

		if ( empty( $suspended_record ) || bp_moderation_is_content_hidden( $document->id, self::$type ) ) {
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

	/**
	 * Function to un-restrict activity data while deleting the activity.
	 *
	 * @since BuddyBoss 1.7.5
	 *
	 * @param boolean $restrict restrict single item or not.
	 *
	 * @return false
	 */
	public function unbind_restrict_single_item( $restrict ) {

		if ( empty( $restrict ) && did_action( 'bp_document_after_delete' ) ) {
			$restrict = true;
		}

		return $restrict;
	}

	/**
	 * Function to update suspend record on activity delete.
	 *
	 * @since BuddyBoss 1.7.5
	 *
	 * @param object $activity_data activity data.
	 */
	public function update_suspend_data_on_activity_delete( $activity_data ) {
		$secondary_item_id = ! empty( $activity_data->secondary_item_id ) ? $activity_data->secondary_item_id : 0;

		if ( empty( $secondary_item_id ) ) {
			return;
		}

		$documents = bp_activity_get_meta( $secondary_item_id, 'bp_document_ids', true );
		$documents = ! empty( $documents ) ? explode( ',', $documents ) : array();

		if ( ! empty( $documents ) && 1 === count( $documents ) ) {
			foreach ( $documents as $document ) {
				if ( bp_moderation_is_content_hidden( $document, $this->item_type ) && bp_is_active( 'activity' ) ) {
					BP_Core_Suspend::add_suspend(
						array(
							'item_id'     => $secondary_item_id,
							'item_type'   => BP_Suspend_Activity::$type,
							'hide_parent' => 1,
						)
					);
				}
			}
		}
	}
}
