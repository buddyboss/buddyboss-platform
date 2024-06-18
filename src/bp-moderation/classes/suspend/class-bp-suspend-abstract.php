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
	public $background_disabled = false;

	/**
	 * Item per page
	 *
	 * @var integer
	 */
	public static $item_per_page = 10;

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
	 * @param string $item_type     Item type field name with the alias of table.
	 *
	 * @return string|void
	 */
	protected function exclude_joint_query( $item_id_field, $item_type = '' ) {
		global $wpdb;
		$bp = buddypress();

		if ( empty( $item_type ) ) {
			$item_type = $this->item_type;
		}

		return ' ' . $wpdb->prepare( "LEFT JOIN {$bp->table_prefix}bp_suspend {$this->alias} ON ( {$this->alias}.item_type = %s AND {$this->alias}.item_id = $item_id_field )", $item_type ); // phpcs:ignore
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
	public function hide_related_content( $item_id, $hide_sitewide = 0, $args = array() ) {
		global $bb_background_updater;

		$args = $this->prepare_suspend_args( $item_id, $hide_sitewide, $args );

		if ( empty( $args['action'] ) ) {
			$args['action'] = 'hide';
		}

		if ( empty( $item_id ) ) {
			return;
		}

		$blocked_user   = ! empty( $args['blocked_user'] ) ? $args['blocked_user'] : '';
		$suspended_user = ! empty( $args['user_suspended'] ) ? $args['user_suspended'] : '';

		if ( ! isset( $args['enable_pagination'] ) ) {
			$args['enable_pagination'] = 1;
		}

		$page = 1;

		if ( empty( $args['page'] ) ) {
			$args['page'] = $page;
		} else {
			$page = $args['page'];
		}

		$args['parent_id'] = ! empty( $args['parent_id'] ) ? $args['parent_id'] : $this->item_type . '_' . $item_id;

		$related_contents = array_filter( $this->get_related_contents( $item_id, $args ) );

		if ( ! empty( $related_contents ) ) {
			foreach ( $related_contents as $content_type => $content_ids ) {
				if ( ! empty( $content_ids ) ) {
					foreach ( $content_ids as $content_id ) {

						if (
							BP_Core_Suspend::check_hidden_content( $content_id, $content_type ) ||
							(
								(
									! empty( $args['action_suspend'] ) ||
									! empty( $args['user_suspended'] )
								) &&
								BP_Core_Suspend::check_suspended_content( $content_id, $content_type )
							)
						) {
							continue;
						}

						if (
							! empty( $blocked_user ) &&
							empty( $suspended_user ) &&
							BP_Core_Suspend::check_blocked_user_content( $content_id, $content_type, $blocked_user )
						) {
							continue;
						}

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

						$args['page'] = 1;

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

			if ( $this->background_disabled ) {
				$args['page'] = ++$page;
				$this->hide_related_content( $item_id, $hide_sitewide, $args );
			} else {
				$group_name_args = array_merge(
					$args,
					array(
						'item_id'       => $item_id,
						'item_type'     => $this->item_type,
						'hide_sitewide' => $hide_sitewide,
						'custom_action' => 'hide',
					)
				);
				$group_name      = $this->bb_moderation_get_action_type( $group_name_args );

				$args['page'] = ++$page;

				$parent_id = ! empty( $args['parent_id'] ) ? $args['parent_id'] : $this->item_type . '_' . $item_id;
				$bb_background_updater->data(
					array(
						'type'              => $this->item_type,
						'group'             => $group_name,
						'data_id'           => $item_id,
						'secondary_data_id' => $parent_id,
						'callback'          => array( $this, 'hide_related_content' ),
						'args'              => array( $item_id, $hide_sitewide, $args ),
					),
				);
				$bb_background_updater->save()->schedule_event();
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
	public function unhide_related_content( $item_id, $hide_sitewide = 0, $force_all = 0, $args = array() ) {
		global $bb_background_updater;

		$args = $this->prepare_suspend_args( $item_id, $hide_sitewide, $args );

		if ( empty( $item_id ) ) {
			return;
		}

		if ( empty( $args['action'] ) ) {
			$args['action'] = 'unhide';
		}

		$blocked_user   = ! empty( $args['blocked_user'] ) ? $args['blocked_user'] : '';
		$action_suspend = ! empty( $args['action_suspend'] ) ? $args['action_suspend'] : '';
		$hide_parent    = ! empty( $args['hide_parent'] ) ? $args['hide_parent'] : '';

		if ( ! isset( $args['enable_pagination'] ) ) {
			$args['enable_pagination'] = 1;
		}

		$page = 1;

		if ( empty( $args['page'] ) ) {
			$args['page'] = $page;
		} else {
			$page = $args['page'];
		}

		$args['parent_id'] = ! empty( $args['parent_id'] ) ? $args['parent_id'] : $this->item_type . '_' . $item_id;

		$related_contents = array_filter( $this->get_related_contents( $item_id, $args ) );

		if ( ! empty( $related_contents ) ) {
			foreach ( $related_contents as $content_type => $content_ids ) {
				if ( ! empty( $content_ids ) ) {
					foreach ( $content_ids as $content_id ) {

						if (
							! empty( $blocked_user ) &&
							empty( $action_suspend ) &&
							! BP_Core_Suspend::check_blocked_user_content( $content_id, $content_type, $blocked_user )
						) {
							continue;
						}

						if (
							(
								! empty( $action_suspend )
								|| empty( $hide_parent )
							) &&
							! (
								BP_Core_Suspend::check_hidden_content( $content_id, $content_type ) ||
								(
									(
										! empty( $args['action_suspend'] ) ||
										! empty( $args['user_suspended'] )
									) &&
									BP_Core_Suspend::check_suspended_content( $content_id, $content_type )
								)
							)
						) {
							continue;
						}

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

						$args['page'] = 1;

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

			if ( $this->background_disabled ) {
				$args['page'] = ++$page;
				$this->unhide_related_content( $item_id, $hide_sitewide, $force_all, $args );
			} else {

				$group_name_args = array_merge(
					$args,
					array(
						'item_id'       => $item_id,
						'item_type'     => $this->item_type,
						'hide_sitewide' => $hide_sitewide,
						'custom_action' => 'unhide',
					)
				);
				$group_name      = $this->bb_moderation_get_action_type( $group_name_args );

				$args['page'] = ++$page;

				$parent_id = ! empty( $args['parent_id'] ) ? $args['parent_id'] : $this->item_type . '_' . $item_id;
				$bb_background_updater->data(
					array(
						'type'              => $this->item_type,
						'group'             => $group_name,
						'data_id'           => $item_id,
						'secondary_data_id' => $parent_id,
						'callback'          => array( $this, 'unhide_related_content' ),
						'args'              => array( $item_id, $hide_sitewide, $force_all, $args ),
					),
				);
				$bb_background_updater->save()->schedule_event();
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

		$cache_key = 'bb_is_content_reported_hidden_' . $item_type . '_' . $item_id;
		$result    = wp_cache_get( $cache_key, 'bp_moderation' );

		if ( false === $result ) {
			$result = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$bp->moderation->table_name} ms WHERE ms.item_id = %d AND ms.item_type = %s AND ms.reported = 1 AND ms.hide_sitewide = 1", $item_id, $item_type ) ); // phpcs:ignore
			wp_cache_set( $cache_key, $result, 'bp_moderation' );
		}

		return ! empty( $result );
	}

	/**
	 * Return group name based on argument.
	 *
	 * @since BuddyBoss 2.4.20
	 *
	 * @param array $args Array of arguments.
	 *
	 * @return string
	 */
	public function bb_moderation_get_action_type( $args ) {
		$type = '';
		if (
			empty( $args ) ||
			empty( $args['item_id'] ) ||
			empty( $args['item_type'] )
		) {
			return 'bb_moderation';
		}

		if ( BP_Suspend_Member::$type === $args['item_type'] ) {
			if ( ! empty( $args['action_suspend'] ) ) {
				if ( ! empty( $args['user_suspended'] ) ) {
					$type = 'suspend';
				} else {
					$type = 'unsuspend';
				}
			}
		} elseif ( isset( $args['hide_parent'] ) ) {
			if ( ! empty( $args['hide_parent'] ) ) {
				$type = 'hide_parent';
			} else {
				$type = 'unhide_parent';
			}
		}

		if ( empty( $type ) && ! empty( $args['custom_action'] ) ) {
			$type = $args['custom_action'];
		}

		return 'bb_moderation_' . $type . '_' . $args['item_type'] . '_' . $args['item_id'];
	}

}
