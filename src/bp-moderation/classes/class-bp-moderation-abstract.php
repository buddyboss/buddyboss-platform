<?php
/**
 * BuddyBoss Moderation items abstract Classes
 *
 * @package BuddyBoss\Moderation
 * @since BuddyBoss 1.5.4
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
	 * Item type
	 *
	 * @var string
	 */
	public $item_type;

	/**
	 * Items ID field name with alias for Join sql conditions
	 *
	 * @var string
	 */
	protected $item_id_field;

	/**
	 * User ID field name with alias for Join sql conditions
	 *
	 * @var string
	 */
	protected $user_id_field;

	/**
	 * Prepare Join sql for exclude Blocked items
	 *
	 * @return string|void
	 *
	 * @since BuddyBoss 1.5.4
	 */
	protected function bp_moderation_exclude_joint_query() {
		global $wpdb;
		$bp = buddypress();

		return $wpdb->prepare( "LEFT JOIN {$bp->moderation->table_name} mo ON ( mo.item_id = $this->item_id_field AND mo.item_type = %s )", $this->item_type ); // phpcs:ignore
	}

	/**
	 * Prepare Where sql for exclude Blocked items
	 *
	 * @return string|void
	 *
	 * @since BuddyBoss 1.5.4
	 */
	protected function bp_moderation_exclude_where_query() {
		global $wpdb;

		return '( mo.hide_sitewide = 0 OR mo.hide_sitewide IS NULL )';
	}

}
