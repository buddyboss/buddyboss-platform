<?php
/**
 * Subscription Functions.
 *
 * @package BuddyBoss\Subscription
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Migration for forums and topics in background.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param bool $is_background The current process is background or not.
 *
 * @return array Return array when it called directly otherwise call recursively.
 */
function bb_subscriptions_migrate_users_forum_topic( $is_background = true ) {
	global $wpdb, $bp_background_updater;

	$offset    = get_option( 'bb_subscriptions_migrate_offset', 0 );
	$forum_key = $wpdb->prefix . '_bbp_forum_subscriptions';
	$topic_key = $wpdb->prefix . '_bbp_subscriptions';
	$results   = $wpdb->get_results( $wpdb->prepare( "SELECT DISTINCT( u.ID ) FROM $wpdb->users AS u INNER JOIN $wpdb->usermeta AS um ON ( u.ID = um.user_id ) WHERE ( um.meta_key = %s OR um.meta_key = %s ) GROUP BY u.ID ORDER BY um.umeta_id ASC LIMIT %d OFFSET %d", $forum_key, $topic_key, 20, $offset ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

	if ( ! empty( $results ) ) {
		if ( $is_background ) {
			$bp_background_updater->push_to_queue(
				array(
					'callback' => 'bb_migrate_users_forum_topic_subscriptions',
					'args'     => array( $results, $offset, $is_background ),
				)
			);
			$bp_background_updater->save()->schedule_event();
		} else {
			return bb_migrate_users_forum_topic_subscriptions( $results, $offset, $is_background );
		}
	} else {
		delete_option( 'bb_subscriptions_migrate_offset' );

		if ( ! $is_background ) {
			/* translators: Status of current action. */
			$statement = __( 'Migrated user forums/topics to new systems&hellip; %s', 'buddyboss' );
			$result    = __( 'Complete!', 'buddyboss' );
			// All done!
			return array(
				'status'  => 1,
				'message' => sprintf( $statement, $result ),
			);
		}
	}
}

/**
 * Migration for forums and topics.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param array $subscription_meta Array of user subscriptions.
 * @param int   $offset            Offset value.
 * @param bool  $is_background     The current process is background or not.
 *
 * @return array Return array when it called directly otherwise call recursively.
 */
function bb_migrate_users_forum_topic_subscriptions( $subscription_meta, $offset = 0, $is_background = true ) {
	global $wpdb;

	$subscription_tbl = BP_Subscription::get_subscription_tbl();
	$forum_post_type  = function_exists( 'bbp_get_forum_post_type' ) ? bbp_get_forum_post_type() : apply_filters( 'bbp_forum_post_type', 'forum' );
	$topic_post_type  = function_exists( 'bbp_get_topic_post_type' ) ? bbp_get_topic_post_type() : apply_filters( 'bbp_topic_post_type', 'topic' );

	if ( ! empty( $subscription_meta ) ) {
		foreach ( $subscription_meta as $user_id ) {

			// Increment the current offset.
			$offset++;

			$place_holder_queries = array();
			$insert_query         = "INSERT INTO {$subscription_tbl} ( user_id, type, item_id, secondary_item_id, date_recorded ) VALUES";

			$forum_subscriptions = get_user_option( '_bbp_forum_subscriptions', $user_id );
			$forum_subscriptions = array_filter( wp_parse_id_list( $forum_subscriptions ) );
			if ( ! empty( $forum_subscriptions ) ) {
				foreach ( $forum_subscriptions as $forum_id ) {
					// Get the forum.
					$forum = get_post( $forum_id );

					if ( $forum_post_type !== $forum->post_type ) {
						continue;
					}

					$record_args = array(
						'user_id'           => (int) $user_id,
						'item_id'           => (int) $forum_id,
						'secondary_item_id' => (int) $forum->post_parent,
						'type'              => 'forum',
					);

					// Get subscription from new table.
					$subscription_exists = BP_Subscription::get( $record_args );

					if ( ! empty( $subscription_exists ) && ! empty( $subscription_exists['subscriptions'] ) ) {
						continue;
					}

					$place_holder_queries[] = $wpdb->prepare( '(%d, %s, %d, %d, %s)', $record_args['user_id'], $record_args['type'], $record_args['item_id'], $record_args['secondary_item_id'], bp_core_current_time() );
				}
			}

			$topic_subscriptions = get_user_option( '_bbp_subscriptions', $user_id );
			$topic_subscriptions = array_filter( wp_parse_id_list( $topic_subscriptions ) );
			if ( ! empty( $topic_subscriptions ) ) {
				foreach ( $topic_subscriptions as $topic_id ) {
					// Get the forum.
					$topic = get_post( $topic_id );

					if ( $topic_post_type !== $topic->post_type ) {
						continue;
					}

					$record_args = array(
						'user_id'           => (int) $user_id,
						'item_id'           => (int) $topic_id,
						'secondary_item_id' => (int) $topic->post_parent,
						'type'              => 'topic',
					);

					// Get subscription from new table.
					$subscription_exists = BP_Subscription::get( $record_args );

					if ( ! empty( $subscription_exists ) && ! empty( $subscription_exists['subscriptions'] ) ) {
						continue;
					}

					$place_holder_queries[] = $wpdb->prepare( '(%d, %s, %d, %d, %s)', $record_args['user_id'], $record_args['type'], $record_args['item_id'], $record_args['secondary_item_id'], bp_core_current_time() );
				}
			}

			if ( ! empty( $place_holder_queries ) ) {
				$place_holder_queries = implode( ', ', $place_holder_queries );
				$wpdb->query( "{$insert_query} {$place_holder_queries}" ); // phpcs:ignore
			}
		}
	}

	// Update the migration offset.
	update_option( 'bb_subscriptions_migrate_offset', $offset );

	if ( $is_background ) {
		bb_subscriptions_migrate_users_forum_topic( $is_background );
	} else {
		$records_updated = sprintf(
		/* translators: total members */
			__( 'The forums/topics successfully migrated for %s members.', 'buddyboss' ),
			bp_core_number_format( $offset )
		);
		return array(
			'status'  => 'running',
			'offset'  => $offset,
			'records' => $records_updated,
		);
	}

}

/**
 * Retrieve all registered subscription types.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return array
 */
function bb_get_subscriptions_types() {
	return array( 'forum', 'topic' );
}

/**
 * Create user subscription.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param array $args {
 *     An array of arguments.
 *     @type string        $type              The type subscription.
 *                                            'Forum', 'topic'.
 *     @type int          $user_id            The ID of the user who created the Subscription.
 *     @type int          $item_id            The ID of forum/topic.
 *     @type int          $secondary_item_id  ID of the parent forum/topic.
 * }
 *
 * @return int|bool|WP_Error
 */
function bb_subscriptions_create_subscription( $args = array() ) {
	$r = bp_parse_args(
		$args,
		array(
			'type'              => '',
			'user_id'           => bp_loggedin_user_id(),
			'item_id'           => 0,
			'secondary_item_id' => 0,
			'date_recorded'     => bp_core_current_time(),
			'error_type'        => 'wp_error',
		),
		'bb_create_subscription'
	);

	// Check if subscription is existed or not?.
	$subscriptions = BP_Subscription::get(
		array(
			'type'              => $r['type'],
			'user_id'           => $r['user_id'],
			'item_id'           => $r['item_id'],
			'secondary_item_id' => $r['secondary_item_id'],
			'no_cache'          => false,
		)
	);

	if ( ! empty( $subscriptions['subscriptions'] ) ) {
		if ( 'wp_error' === $r['error_type'] ) {
			return new WP_Error(
				'bb_subscriptions_create_exists',
				__( 'The subscription is already exists.', 'buddyboss' ),
				array(
					'status' => 400,
				)
			);
		} else {
			return false;
		}
	}

	$new_subscription                    = new BP_Subscription();
	$new_subscription->user_id           = $r['user_id'];
	$new_subscription->type              = $r['type'];
	$new_subscription->item_id           = $r['item_id'];
	$new_subscription->secondary_item_id = $r['secondary_item_id'];
	$new_subscription->date_recorded     = $r['date_recorded'];
	$new_subscription->error_type        = $r['error_type'];
	$new_subscription_created            = $new_subscription->save();

	/**
	 * Fires after create a new subscription.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $r Array of argument to create a new subscription.
	 * @param int|bool|WP_Error $new_subscription_created The ID of new subscription when it's true otherwise return error.
	 */
	do_action_ref_array( 'bb_subscriptions_after_save', array( $r, $new_subscription_created ) );

	return $new_subscription_created;
}

/**
 * Retrieve user subscription by type.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param array $args {
 *     An array of optional arguments.
 *     @type array|string $type               Optional. Array or comma-separated list of subscription types.
 *                                            'Forum', 'topic'.
 *                                            Default: null.
 *     @type int          $user_id            Optional. If provided, results will be limited to subscriptions.
 *                                            Default: Current user ID.
 *     @type int          $per_page           Optional. Number of items to return per page of results.
 *                                            Default: null (no limit).
 * }
 * @see BP_Subscription::get()
 *
 * @return array {
 *     @type array $subscriptions Array of subscription objects returned by the
 *                                paginated query. (IDs only if `fields` is set to `ids`.)
 *     @type int   $total         Total count of all subscriptions matching non-
 *                                paginated query params.
 * }
 */
function bb_get_subscriptions( $args = array() ) {
	static $cache = array();

	$r = bp_parse_args(
		$args,
		array(
			'type'     => array(),
			'user_id'  => bp_loggedin_user_id(),
			'per_page' => null,
			'page'     => null,
			'no_count' => false,
		),
		'bb_get_subscriptions'
	);

	$cache_key = 'bb_get_subscriptions_' . md5( maybe_serialize( $r ) );
	if ( ! isset( $cache[ $cache_key ] ) ) {
		$subscriptions       = BP_Subscription::get( $r );
		$cache[ $cache_key ] = $subscriptions;
	} else {
		$subscriptions = $cache[ $cache_key ];
	}

	return $subscriptions;
}

/**
 * Retrieve all users by subscription type and item id.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param array $args {
 *     An array of optional arguments.
 *     @type array|string $type               Optional. Array or comma-separated list of subscription types.
 *                                            'Forum', 'topic'.
 *                                            Default: null.
 *     @type int          $item_id            Optional. If provided, results will be limited to subscriptions.
 *                                            Default: null.
 *     @type int          $per_page           Optional. Number of items to return per page of results.
 *                                            Default: null (no limit).
 * }
 * @see BP_Subscription::get()
 *
 * @return array {
 *     @type array $subscriptions Array of subscription objects returned by the
 *                                paginated query. (IDs only if `fields` is set to `ids`.)
 *     @type int   $total         Total count of all subscriptions matching non-
 *                                paginated query params.
 * }
 */
function bb_get_subscription_users( $args = array() ) {
	static $cache = array();

	$r = bp_parse_args(
		$args,
		array(
			'type'     => array(),
			'item_id'  => 0,
			'per_page' => null,
			'page'     => null,
			'fields'   => 'user_id',
			'no_count' => false,
		),
		'bb_get_subscription_users'
	);

	$cache_key = 'bb_get_subscription_users_' . md5( maybe_serialize( $r ) );
	if ( ! isset( $cache[ $cache_key ] ) ) {
		$subscriptions       = BP_Subscription::get( $r );
		$cache[ $cache_key ] = $subscriptions;
	} else {
		$subscriptions = $cache[ $cache_key ];
	}

	return $subscriptions;
}
