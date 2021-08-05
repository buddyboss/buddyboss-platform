<?php
/**
 * BuddyBoss Suspend items abstract Classes
 *
 * @since   BuddyBoss 1.5.6
 * @package BuddyBoss\Suspend
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Database interaction class for the BuddyBoss Suspend items.
 *
 * @since BuddyBoss 1.5.6
 */
abstract class BP_Suspend_Abstract {

	/**
	 * Item type
	 *
	 * @var string
	 */
	public $backgroup_diabled = false;

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
	 * White listed DB Fields.
	 *
	 * @var array
	 */
	public static $white_list_keys = array(
		'id',
		'item_id',
		'item_type',
		'hide_sitewide',
		'hide_parent',
		'user_suspended',
		'reported',
		'last_updated',
		'blog_id',
		'blocked_user',
		'action_suspend',
	);

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
	 * Prepare Join sql for exclude Suspended items
	 *
	 * @since BuddyBoss 1.5.6
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
	 * @since BuddyBoss 1.5.6
	 *
	 * @return string|void
	 */
	protected function exclude_where_query() {
		return "( {$this->alias}.user_suspended = 0 OR {$this->alias}.user_suspended IS NULL )";
	}

	/**
	 * Hide related content
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int      $item_id       item id.
	 * @param int|null $hide_sitewide item hidden sitewide or user specific.
	 * @param array    $args          parent args.
	 */
	public function hide_related_content( $item_id, $hide_sitewide, $args = array() ) {
		$args = $this->prepare_suspend_args( $item_id, $hide_sitewide, $args );

		if ( empty( $args['action'] ) ) {
			$args['action'] = 'hide';
		}

		$related_contents = $this->get_related_contents( $item_id, $args );

		foreach ( $related_contents as $content_type => $content_ids ) {
			if ( ! empty( $content_ids ) ) {
				foreach ( $content_ids as $content_id ) {

					/**
					 * Fire before hide item
					 *
					 * @since BuddyBoss 1.6.2
					 *
					 * @param string $content_type content type
					 * @param int    $content_id   item id
					 * @param array  $args         unhide item arguments
					 */
					do_action( 'bb_suspend_hide_before', $content_type, $content_id, $args );

					/**
					 * Add related content of reported item into hidden list
					 *
					 * @since BuddyBoss 1.5.6
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
	 * @since BuddyBoss 1.5.6
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
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int   $item_id       item id.
	 * @param int   $hide_sitewide item hidden sitewide or user specific.
	 * @param array $args          parent args.
	 *
	 * @return array
	 */
	protected function prepare_suspend_args( $item_id, $hide_sitewide, $args = array() ) {
		if ( ! isset( $args['hide_parent'] ) && isset( $hide_sitewide ) ) {
			$args['hide_parent'] = $hide_sitewide;
		}

		return $args;
	}

	/**
	 * Un-hide related content
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int      $item_id       item id.
	 * @param int|null $hide_sitewide item hidden sitewide or user specific.
	 * @param int      $force_all     un-hide for all users.
	 * @param array    $args          parent args.
	 */
	public function unhide_related_content( $item_id, $hide_sitewide, $force_all, $args = array() ) {
		$args = $this->prepare_suspend_args( $item_id, $hide_sitewide, $args );

		if ( empty( $args['action'] ) ) {
			$args['action'] = 'unhide';
		}

		$related_contents = $this->get_related_contents( $item_id, $args );

		foreach ( $related_contents as $content_type => $content_ids ) {
			if ( ! empty( $content_ids ) ) {
				foreach ( $content_ids as $content_id ) {

					/**
					 * Fire before unhide item
					 *
					 * @since BuddyBoss 1.6.2
					 *
					 * @param string $content_type content type
					 * @param int    $content_id   item id
					 * @param array  $args         unhide item arguments
					 */
					do_action( 'bb_suspend_unhide_before', $content_type, $content_id, $args );

					/**
					 * Remove related content of reported item from hidden list.
					 *
					 * @since BuddyBoss 1.5.6
					 *
					 * @param int $content_id    item id
					 * @param int $hide_sitewide item hidden sitewide or user specific
					 */
					do_action( 'bp_suspend_unhide_' . $content_type, $content_id, null, $force_all, $args );
				}
			}
		}
	}

	/**
	 * Handle new suspend entry.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param object $suspended_record Suspended Item Record.
	 * @param int    $item_id          New item ID.
	 * @param int    $user_id          New item User ID.
	 */
	public function handle_new_suspend_entry( $suspended_record, $item_id, $user_id ) {

		if ( empty( $suspended_record ) || empty( $item_id ) || empty( $user_id ) ) {
			return;
		}

		$hide_sitewide  = $suspended_record->hide_sitewide;
		$hide_parent    = $suspended_record->hide_parent;
		$user_suspended = $suspended_record->user_suspended;

		$suspended_id   = $suspended_record->id;
		$reported_users = BP_Core_Suspend::get_suspend_detail( $suspended_id );
		if (
			! empty( $reported_users )
			|| ! empty( $hide_sitewide )
			|| ! empty( $hide_parent )
			|| ! empty( $user_suspended )
		) {
			$suspend_args = array(
				'item_id'        => $item_id,
				'item_type'      => $this->item_type,
				'user_suspended' => $user_suspended,
				'hide_parent'    => false,
				'hide_sitewide'  => false,
			);

			if ( true === $hide_sitewide && BP_Moderation_Members::$moderation_type !== $suspended_record->item_type ) {
				$suspend_args['hide_parent'] = $hide_sitewide;
			}

			if ( BP_Moderation_Members::$moderation_type === $suspended_record->item_type ) {
				$suspend_args['hide_parent']    = false;
				$suspend_args['user_suspended'] = $hide_sitewide;
			} elseif ( true === $hide_sitewide ) {
				$suspend_args['hide_parent'] = $hide_sitewide;
			}

			$current_suspended_id = BP_Core_Suspend::add_suspend( $suspend_args );

			if ( ! empty( $reported_users ) ) {
				$reported_users[] = $user_id;
				foreach ( $reported_users as $user_id ) {
					BP_Core_Suspend::add_suspend_details(
						array(
							'suspend_id' => $current_suspended_id,
							'user_id'    => $user_id,
						)
					);
				}
			}
		}
	}

	/**
	 * Return whitelisted keys from array arguments.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param array $args Array of arguments.
	 *
	 * @return array|mixed
	 */
	public static function validate_keys( $args = array() ) {
		if ( empty( $args ) ) {
			return $args;
		}

		return array_intersect_key( $args, array_flip( self::$white_list_keys ) );
	}

	/**
	 * Return whitelisted keys from array arguments.
	 *
	 * @since BuddyBoss 1.7.5
	 *
	 * @param int    $item_id   Item ID.
	 * @param string $item_type Item type.
	 *
	 * @return bool
	 */
	public static function is_content_reported_hidden( $item_id, $item_type ) {
		global $wpdb, $bp;

		$result = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$bp->moderation->table_name} ms WHERE ms.item_id = %d AND ms.item_type = %s AND ms.reported = 1 AND ms.hide_sitewide = 1", $item_id, $item_type ) ); // phpcs:ignore

		return ! empty( $result );
	}

}
