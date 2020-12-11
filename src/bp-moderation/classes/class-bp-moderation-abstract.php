<?php
/**
 * BuddyBoss Moderation items abstract Classes
 *
 * @since   BuddyBoss 2.0.0
 * @package BuddyBoss\Moderation
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Database interaction class for the BuddyBoss moderation items.
 *
 * @since BuddyBoss 2.0.0
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
	 * @since BuddyBoss 2.0.0
	 *
	 * @return bool
	 */
	public static function admin_bypass_check() {
		$admin_exclude = filter_input( INPUT_GET, 'modbypass', FILTER_SANITIZE_NUMBER_INT );

		if ( empty( $admin_exclude ) ) {
			$admin_exclude = filter_input( INPUT_POST, 'modbypass', FILTER_SANITIZE_NUMBER_INT );
		}

		if ( ! empty( $admin_exclude ) ) {
			$admins = array_map( 'intval', get_users(
				array(
					'role'   => 'administrator',
					'fields' => 'ID',
				)
			) );
			if ( in_array( get_current_user_id(), $admins, true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get class from content type.
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param string $type Content type.
	 *
	 * @return string
	 */
	public static function get_class( $type = '' ) {
		$class = self::class;
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
	 * @since BuddyBoss 2.0.0
	 *
	 * @param integer $item_id Content item id.
	 */
	abstract public static function get_content_owner_id( $item_id );

	/**
	 * Get permalink
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param int $item_id Content item id.
	 *
	 * @return string
	 */
	abstract public static function get_permalink( $item_id );

	/**
	 * Report content
	 *
	 * @since BuddyBoss 2.0.0
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
		} else {
			$is_allow = bp_is_moderation_content_reporting_enable( 0, $args['content_type'] );
		}

		// Return error is moderation setting not enabled.
		if ( empty( $is_allow ) ) {
			return new WP_Error( 'moderation_not_enable', __( 'Moderation not enabled.', 'buddyboss' ) );
		}

		if ( empty( $moderation->id ) ) {
			$moderation->item_id   = $args['content_id'];
			$moderation->item_type = $args['content_type'];
		}

		$moderation->category_id  = isset( $args['category_id'] ) ? $args['category_id'] : 0;
		$moderation->content      = ! empty( $args['note'] ) ? $args['note'] : '';
		$moderation->last_updated = current_time( 'mysql' );

		$moderation->save();

		return $moderation;
	}

	/**
	 * Hide Moderated content
	 *
	 * @since BuddyBoss 2.0.0
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
	 * @since BuddyBoss 2.0.0
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
	 * @since BuddyBoss 2.0.0
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
	 * @since BuddyBoss 2.0.0
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
	 * @since BuddyBoss 2.0.0
	 *
	 * @return string|void
	 */
	protected function exclude_where_query() {
		$blocked_query = $this->blocked_user_query();

		$where = "( ( ( {$this->alias}.hide_parent = 0 OR {$this->alias}.hide_parent IS NULL ) AND ( {$this->alias}.hide_sitewide = 0 OR {$this->alias}.hide_sitewide IS NULL )";
		if ( ! empty( $blocked_query ) ) {
			$where .= " AND {$this->alias}.id IS NULL ) OR ( {$this->alias}.id NOT IN ( $blocked_query )";
		}
		$where .= ' ) )';

		return $where;
	}

	/**
	 * Blocked User filter query
	 *
	 * @return false|string
	 */
	protected function blocked_user_query() {
		$bp = buddypress();

		$hidden_users_ids = bp_moderation_get_hidden_user_ids();
		if ( ! empty( $hidden_users_ids ) ) {
			return "SELECT suspend_id FROM {$bp->table_prefix}bp_suspend_details WHERE `user_id` IN (" . implode( ',', $hidden_users_ids ) . ')';
		}

		return false;
	}
}
