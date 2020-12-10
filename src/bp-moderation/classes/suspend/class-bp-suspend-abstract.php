<?php
/**
 * BuddyBoss Suspend items abstract Classes
 *
 * @since   BuddyBoss 2.0.0
 * @package BuddyBoss\Suspend
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Database interaction class for the BuddyBoss Suspend items.
 *
 * @since BuddyBoss 2.0.0
 */
abstract class BP_Suspend_Abstract {

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
	public $alias = 's';

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
			$admins = get_users(
				array(
					'role'   => 'administrator',
					'fields' => 'ID',
				)
			);
			if ( in_array( get_current_user_id(), $admins, true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Prepare Join sql for exclude Suspended items
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param string $item_id_field Items ID field name with alias of table.
	 *
	 * @return string|void
	 */
	protected function exclude_joint_query( $item_id_field ) {
		global $wpdb;
		$bp = buddypress();

		return ' ' . $wpdb->prepare( "LEFT JOIN {$bp->table_prefix}bp_suspend {$this->alias} ON ( {$this->alias}.item_type = %s AND {$this->alias}.item_id = $item_id_field )", $this->item_type ); // phpcs:ignore
	}

	/**
	 * Prepare Where sql for exclude Suspended items
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @return string|void
	 */
	protected function exclude_where_query() {
		return "( {$this->alias}.user_suspended = 0 OR {$this->alias}.user_suspended IS NULL )";
	}

	/**
	 * Hide related content
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param int      $item_id       item id.
	 * @param int|null $hide_sitewide item hidden sitewide or user specific.
	 * @param array    $args          parent args.
	 */
	protected function hide_related_content( $item_id, $hide_sitewide, $args = array() ) {
		$related_contents = $this->get_related_contents( $item_id );
		$args             = $this->prepare_suspend_args( $item_id, $hide_sitewide, $args );
		foreach ( $related_contents as $content_type => $content_ids ) {
			if ( ! empty( $content_ids ) ) {
				foreach ( $content_ids as $content_id ) {
					/**
					 * Add related content of reported item into hidden list
					 *
					 * @since BuddyBoss 2.0.0
					 *
					 * @param int $content_id    item id
					 * @param int $hide_sitewide item hidden sitewide or user specific
					 */
					do_action( 'bp_suspend_hide_' . $content_type, $content_id, null, $args );
				}
			}
		}
	}

	/**
	 * Get item related content
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param int   $item_id item id.
	 * @param array $args    parent args.
	 *
	 * @return array
	 */
	abstract protected function get_related_contents( $item_id, $args );

	/**
	 * Hide related content
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param int   $item_id       item id.
	 * @param int   $hide_sitewide item hidden sitewide or user specific.
	 * @param array $args          parent args.
	 */
	protected function prepare_suspend_args( $item_id, $hide_sitewide, $args ) {
		if ( empty( $args ) ) {
			$args = array();
			if ( isset( $hide_sitewide ) ) {
				$args['hide_parent'] = $hide_sitewide;
			}
		}

		return $args;
	}

	/**
	 * Un-hide related content
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param int      $item_id       item id.
	 * @param int|null $hide_sitewide item hidden sitewide or user specific.
	 * @param int      $force_all     un-hide for all users.
	 * @param array    $args          parent args.
	 */
	protected function unhide_related_content( $item_id, $hide_sitewide, $force_all, $args = array() ) {
		$related_contents = $this->get_related_contents( $item_id, $args );
		$args             = $this->prepare_suspend_args( $item_id, $hide_sitewide, $args );
		foreach ( $related_contents as $content_type => $content_ids ) {
			if ( ! empty( $content_ids ) ) {
				foreach ( $content_ids as $content_id ) {
					/**
					 * Remove related content of reported item from hidden list.
					 *
					 * @since BuddyBoss 2.0.0
					 *
					 * @param int $content_id    item id
					 * @param int $hide_sitewide item hidden sitewide or user specific
					 */
					do_action( 'bp_suspend_unhide_' . $content_type, $content_id, null, $force_all, $args );
				}
			}
		}
	}

}
