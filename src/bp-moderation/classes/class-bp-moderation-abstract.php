<?php
/**
 * BuddyBoss Moderation items abstract Classes
 *
 * @since   BuddyBoss 1.5.6
 * @package BuddyBoss\Moderation
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Database interaction class for the BuddyBoss moderation items.
 *
 * @since BuddyBoss 1.5.6
 */
abstract class BP_Moderation_Abstract {

	/**
	 * Moderation classes
	 *
	 * @var array
	 */
	public static $moderation;

	/**
	 * Item type
	 *
	 * @var string
	 */
	public $item_type;

	/**
	 * Item type
	 *
	 * @var string
	 */
	public $alias;

	/**
	 * Check whether bypass argument pass for admin user or not.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @return bool
	 */
	public static function admin_bypass_check() {
		$admin_exclude = filter_input( INPUT_GET, 'modbypass', FILTER_SANITIZE_NUMBER_INT );

		if ( empty( $admin_exclude ) ) {
			$admin_exclude = filter_input( INPUT_POST, 'modbypass', FILTER_SANITIZE_NUMBER_INT );
		}

		if ( ! empty( $admin_exclude ) ) {
			$admins = array_map(
				'intval',
				get_users(
					array(
						'role'   => 'administrator',
						'fields' => 'ID',
					)
				)
			);
			if ( in_array( get_current_user_id(), $admins, true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get class from content type.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param string $type Content type.
	 *
	 * @return string
	 */
	public static function get_class( $type = '' ) {
		$class = new stdClass();
		if ( ! empty( $type ) && ! empty( self::$moderation ) && isset( self::$moderation[ $type ] ) ) {
			if ( class_exists( self::$moderation[ $type ] ) ) {
				$class = self::$moderation[ $type ];
			}
		}

		return $class;
	}

	/**
	 * Get Content owner id.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int|array $item_id Content item id.
	 */
	abstract public static function get_content_owner_id( $item_id );

	/**
	 * Get permalink
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int $item_id Content item id.
	 *
	 * @return string
	 */
	abstract public static function get_permalink( $item_id );

	/**
	 * Report content
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param array $args Content data.
	 *
	 * @return BP_Moderation|WP_Error
	 */
	public static function report( $args ) {
		$moderation = new BP_Moderation( $args['content_id'], $args['content_type'] );

		// Get Moderation settings.
		if ( BP_Moderation_Members::$moderation_type === $args['content_type'] ) {
			$is_allow = bp_is_moderation_member_blocking_enable();
		} elseif ( BP_Moderation_Members::$moderation_type_report === $args['content_type'] ) {
			$is_allow = bb_is_moderation_member_reporting_enable();
		} else {
			$is_allow = bp_is_moderation_content_reporting_enable( 0, $args['content_type'] );
		}

		// Return error is moderation setting not enabled.
		if ( empty( $is_allow ) ) {
			return new WP_Error( 'moderation_not_enable', __( 'Moderation not enabled.', 'buddyboss' ) );
		}

		if ( empty( $moderation->id ) ) {
			$moderation->item_id   = $args['content_id'];
			$moderation->item_type = ( BP_Moderation_Members::$moderation_type_report === $args['content_type'] ? BP_Moderation_Members::$moderation_type : $args['content_type'] );
		}

		$moderation->category_id  = isset( $args['category_id'] ) ? $args['category_id'] : 0;
		$moderation->user_report  = isset( $args['user_report'] ) ? $args['user_report'] : 0;
		$moderation->content      = ! empty( $args['note'] ) ? $args['note'] : '';
		$moderation->last_updated = current_time( 'mysql' );

		$moderation->save();

		return $moderation;
	}

	/**
	 * Hide Moderated content
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param array $args Content data.
	 *
	 * @return BP_Moderation|WP_Error
	 */
	public static function hide( $args ) {
		$moderation = new BP_Moderation( $args['content_id'], $args['content_type'] );

		$moderation->hide();

		return $moderation;
	}

	/**
	 * Unhide Moderated content
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param array $args Content data.
	 *
	 * @return BP_Moderation|WP_Error
	 */
	public static function unhide( $args ) {
		$moderation = new BP_Moderation( $args['content_id'], $args['content_type'] );

		$moderation->unhide();

		return $moderation;
	}

	/**
	 * Delete Moderated report
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param array $args Content data.
	 *
	 * @return BP_Moderation|WP_Error
	 */
	public static function delete( $args ) {
		$moderation        = new BP_Moderation( $args['content_id'], $args['content_type'] );
		$args['force_all'] = isset( $args['force_all'] ) ? $args['force_all'] : false;
		$moderation->delete( $args['force_all'] );

		return $moderation;
	}

	/**
	 * Retrieve sitewide hidden items ids of particular item type.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param string $type         Moderation items type.
	 * @param bool   $user_include Include item which report by current user even if it's not hidden.
	 *
	 * @return array $moderation See BP_Moderation::get() for description.
	 */
	public static function get_sitewide_hidden_item_ids( $type, $user_include = false ) {
		$hidden_ids = array();

		return $hidden_ids;
	}

	/**
	 * Prepare Where sql for exclude Blocked items
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param bool $blocked_user_query If true then blocked user query will fire.
	 *
	 * @return string|void
	 */
	protected function exclude_where_query( $blocked_user_query = true ) {
		$where = '';

		$where .= "( {$this->alias}.hide_parent = 0 OR {$this->alias}.hide_parent IS NULL ) AND
		( {$this->alias}.hide_sitewide = 0 OR {$this->alias}.hide_sitewide IS NULL )";

		if ( true === $blocked_user_query ) {
			$blocked_query = $this->blocked_user_query();
			if ( ! empty( $blocked_query ) ) {
				if ( ! empty( $where ) ) {
					$where .= ' AND ';
				}
				$where .= "( ( {$this->alias}.id NOT IN ( $blocked_query ) ) OR {$this->alias}.id IS NULL )";
			}
		}

		return $where;
	}

	/**
	 * Blocked User filter query
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @return false|string
	 */
	protected function blocked_user_query() {
		$bp = buddypress();

		if ( bp_is_moderation_member_blocking_enable( 0 ) ) {

			$hidden_users_ids = bp_moderation_get_hidden_user_ids();
			if ( ! empty( $hidden_users_ids ) ) {
				return "SELECT suspend_id FROM {$bp->table_prefix}bp_suspend_details WHERE `user_id` IN (" . implode( ',', $hidden_users_ids ) . ')';
			}
		}

		return false;
	}

	/**
	 * Reporting Setting enabled for current content.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @return bool
	 */
	protected function is_reporting_enabled() {
		return bp_is_moderation_content_reporting_enable( 0, $this->item_type );
	}

	/**
	 * Member blocking content enabled
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @return bool
	 */
	protected function is_member_blocking_enabled() {
		return bp_is_moderation_member_blocking_enable( 0 );
	}

	/**
	 * Check content is hidden or not.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int $item_id Item id.
	 *
	 * @return bool
	 */
	protected function is_content_hidden( $item_id ) {
		if ( ( $this->is_member_blocking_enabled() && BP_Core_Suspend::check_blocked_content( $item_id, $this->item_type ) ) ||
			( $this->is_reporting_enabled() && BP_Core_Suspend::check_hidden_content( $item_id, $this->item_type ) ) ) {
			return true;
		}
		return false;
	}
}
