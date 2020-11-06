<?php
/**
 * BuddyBoss Moderation items abstract Classes
 *
 * @package BuddyBoss\Moderation
 * @since   BuddyBoss 1.5.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Database interaction class for the BuddyBoss moderation items.
 *
 * @since BuddyBoss 1.5.4
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
	public $alias = 'mo';

	/**
	 * Prepare Join sql for exclude Blocked items
	 *
	 * @since BuddyBoss 1.5.4
	 *
	 * @param string $item_id_field Items ID field name with alias of table.
	 *
	 * @return string|void
	 */
	protected function exclude_joint_query( $item_id_field ) {
		global $wpdb;
		$bp = buddypress();

		return ' ' . $wpdb->prepare( "LEFT JOIN {$bp->moderation->table_name} {$this->alias} ON ( {$this->alias}.item_id = $item_id_field AND {$this->alias}.item_type = %s )", $this->item_type ); // phpcs:ignore
	}

	/**
	 * Prepare Where sql for exclude Blocked items
	 *
	 * @return string|void
	 *
	 * @since BuddyBoss 1.5.4
	 */
	protected function exclude_where_query() {
		return "( {$this->alias}.hide_sitewide = 0 OR {$this->alias}.hide_sitewide IS NULL )";
	}

	/**
	 * Retrieve sitewide hidden items ids of particular item type.
	 *
	 * @since BuddyBoss 1.5.4
	 *
	 * @param string $type         Moderation items type.
	 * @param bool   $user_include Include item which report by current user even if it's not hidden.
	 *
	 * @return array $moderation See BP_Moderation::get() for description.
	 */
	public static function get_sitewide_hidden_item_ids( $type, $user_include = false ) {
		$hidden_ids  = array();
		$moderations = bp_moderation_get_sitewide_hidden_item_ids( $type, $user_include );

		if ( ! empty( $moderations ) && ! empty( $moderations['moderations'] ) ) {
			$hidden_ids = wp_list_pluck( $moderations['moderations'], 'item_id' );
		}

		return $hidden_ids;
	}

	/**
	 * Get Content owner id.
	 *
	 * @since BuddyBoss 1.5.4
	 *
	 * @param integer $item_id Content item id.
	 */
	abstract public static function get_content_owner_id( $item_id );

	/**
	 * Get class from content type.
	 *
	 * @since BuddyBoss 1.5.4
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
	 * Report content
	 *
	 * @since BuddyBoss 1.5.4
	 *
	 * @param array $args Content data.
	 *
	 * @return string
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

		$args['category_id'] = isset( $args['category_id'] ) && 'other' !== $args['category_id'] ? $args['category_id'] : 0;

		if ( empty( $moderation->id ) ) {
			$moderation->item_id   = $args['content_id'];
			$moderation->item_type = $args['content_type'];
		}

		$moderation->category_id = isset( $args['category_id'] ) ? $args['category_id'] : 0;
		$moderation->content     = ! empty( $args['note'] ) ? $args['note'] : '';

		$moderation->user_id      = get_current_user_id();
		$moderation->last_updated = current_time( 'mysql' );

		$moderation->save();

		return $moderation;
	}
}
