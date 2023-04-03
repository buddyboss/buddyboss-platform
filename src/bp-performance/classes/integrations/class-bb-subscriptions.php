<?php
/**
 * BuddyBoss Performance Subscription Integration.
 *
 * @package BuddyBoss\Performance
 */

namespace BuddyBoss\Performance\Integration;

use BuddyBoss\Performance\Cache;
use BuddyBoss\Performance\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Subscription Integration Class.
 *
 * @package BuddyBossApp\Performance
 */
class BB_Subscriptions extends Integration_Abstract {

	/**
	 * Add(Start) Integration
	 *
	 * @return mixed|void
	 */
	public function set_up() {
		$this->register( 'bb-subscriptions' );

		$purge_events = array(
			'bb_create_subscription', // When new subscription created.
			'bb_delete_subscription', // When subscription deleted.
			'bb_subscriptions_after_save', // When subscription add/update.
			'bb_subscriptions_after_delete_subscription', // When subscription deleted.

			'deleted_user', // when user deleted.
		);

		$this->purge_event( 'bb-subscriptions', $purge_events );

		/**
		 * Support for single items purge
		 */
		$purge_single_events = array(
			'bb_create_subscription'                          => 2, // When subscription created.
			'bb_delete_subscription'                          => 1, // When subscription created.
			'bb_subscriptions_after_save'                     => 1, // When subscription add/update.
			'bb_subscriptions_after_delete_subscription'      => 2, // When subscription deleted.
			'bb_subscriptions_after_update_secondary_item_id' => 1, // When subscription updated.

			// Add Author Embed Support.
			'profile_update'                                  => 1, // User updated on site.
			'deleted_user'                                    => 1, // User deleted on site.
			'xprofile_avatar_uploaded'                        => 1, // User avatar photo updated.
			'bp_core_delete_existing_avatar'                  => 1, // User avatar photo deleted.
			'bp_core_delete_existing_avatar'                  => 1, // User avatar photo deleted.
		);

		$this->purge_single_events( $purge_single_events );

		$is_component_active    = Helper::instance()->get_app_settings( 'cache_component', 'buddyboss-app' );
		$settings               = Helper::instance()->get_app_settings( 'cache_bb_subscription', 'buddyboss-app' );
		$cache_bb_subscriptions = isset( $is_component_active ) && isset( $settings ) && $is_component_active && $settings;

		if ( $cache_bb_subscriptions ) {
			$this->cache_endpoint(
				'buddyboss/v1/subscriptions',
				Cache::instance()->month_in_seconds * 60,
				array(
					'unique_id' => 'id',
				),
				true
			);

			$this->cache_endpoint(
				'buddyboss/v1/subscriptions/<id>',
				Cache::instance()->month_in_seconds * 60,
				array(),
				false
			);
		}
	}

	/******************************** Subscription Events ********************************/
	/**
	 * When Subscription created.
	 *
	 * @param array $args            Array of Subscription data to create.
	 * @param int   $subscription_id Subscription ID.
	 */
	public function event_bb_create_subscription( $args, $subscription_id ) {
		$this->purge_item_cache_by_item_id( $subscription_id );
	}

	/**
	 * When Subscription created.
	 *
	 * @param int $subscription_id Subscription ID.
	 */
	public function event_bb_delete_subscription( $subscription_id ) {
		$this->purge_item_cache_by_item_id( $subscription_id );
	}

	/**
	 * When Subscription created/updated.
	 *
	 * @param BB_Subscriptions $subscription Subscription object.
	 */
	public function event_bb_subscriptions_after_save( $subscription ) {
		if ( ! empty( $subscription->id ) ) {
			$this->purge_item_cache_by_item_id( $subscription->id );
		}
	}

	/**
	 * When Subscription deleted.
	 *
	 * @param BB_Subscriptions $subscription    Subscription object.
	 * @param int              $subscription_id Subscription ID.
	 */
	public function event_bb_subscriptions_after_delete_subscription( $subscription, $subscription_id ) {
		$this->purge_item_cache_by_item_id( $subscription_id );
	}

	/**
	 * When the subscription secondary item ID has been updated.
	 *
	 * @param array $args Subscription arguments.
	 */
	public function event_bb_subscriptions_after_update_secondary_item_id( $args ) {
		if ( empty( $args['type'] ) || empty( $args['item_id'] ) ) {
			return;
		}

		$subscription_ids = bb_get_subscriptions(
			array(
				'type'    => $args['type'],
				'item_id' => $args['item_id'],
				'fields'  => 'id',
			),
			true
		);

		if ( ! empty( $subscription_ids['subscriptions'] ) ) {
			foreach ( $subscription_ids['subscriptions'] as $subscription_id ) {
				$this->purge_item_cache_by_item_id( $subscription_id );
			}
		}
	}

	/****************************** Author Embed Support *****************************/
	/**
	 * User updated on site
	 *
	 * @param int $user_id User ID.
	 */
	public function event_profile_update( $user_id ) {
		$this->purge_item_cache_by_user_id( $user_id );
	}

	/**
	 * User deleted on site
	 *
	 * @param int $user_id User ID.
	 */
	public function event_deleted_user( $user_id ) {
		$this->purge_item_cache_by_user_id( $user_id );
	}

	/**
	 * User avatar photo updated
	 *
	 * @param int $user_id User ID.
	 */
	public function event_xprofile_avatar_uploaded( $user_id ) {
		$this->purge_item_cache_by_user_id( $user_id );
	}

	/**
	 * User avatar photo deleted
	 *
	 * @param array $args Array of arguments used for avatar deletion.
	 */
	public function event_bp_core_delete_existing_avatar( $args ) {
		$user_id = ! empty( $args['item_id'] ) ? absint( $args['item_id'] ) : 0;
		if ( ! empty( $user_id ) ) {
			if ( isset( $args['object'] ) && 'user' === $args['object'] ) {
				$this->purge_item_cache_by_user_id( $user_id );
			}
		}
	}

	/*********************************** Functions ***********************************/
	/**
	 * Get Subscriptions ids from user ID.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return array
	 */
	private function get_subscription_ids_by_userid( $user_id ) {
		$all_subscription = bb_get_subscriptions(
			array(
				'user_id' => $user_id,
				'fields'  => 'id',
				'status'  => null,
			)
		);

		if ( ! empty( $all_subscription['subscriptions'] ) ) {
			return $all_subscription['subscriptions'];
		}

		return array();
	}

	/**
	 * Purge item cache by user id.
	 *
	 * @param int $user_id User ID.
	 */
	private function purge_item_cache_by_user_id( $user_id ) {
		$subscription_ids = $this->get_subscription_ids_by_userid( $user_id );
		if ( ! empty( $subscription_ids ) ) {
			foreach ( $subscription_ids as $subscription_id ) {
				$this->purge_item_cache_by_item_id( $subscription_id );
			}
		}
	}

	/**
	 * Purge item cache by item id.
	 *
	 * @param int $subscription_id Subscription ID.
	 */
	private function purge_item_cache_by_item_id( $subscription_id ) {
		Cache::instance()->purge_by_group( 'bb-subscriptions_' . $subscription_id );
	}
}
