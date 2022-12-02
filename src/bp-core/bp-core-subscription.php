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
 */
function bb_subscriptions_migrate_users_forum_topic() {
	global $wpdb, $bp_background_updater;

	$forum_key = $wpdb->prefix . '_bbp_forum_subscriptions';
	$topic_key = $wpdb->prefix . '_bbp_subscriptions';
	$results   = $wpdb->get_results( $wpdb->prepare( "SELECT SQL_CALC_FOUND_ROWS u.ID, um.meta_key, um.meta_value FROM $wpdb->users AS u INNER JOIN $wpdb->usermeta AS um ON ( u.ID = um.user_id ) WHERE ( um.meta_key = %s OR um.meta_key = %s ) ORDER BY u.ID ASC", $forum_key, $topic_key ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

	if ( count( $results ) > 20 ) {
		$chunk_results = array_chunk( $results, 20 );
		if ( ! empty( $chunk_results ) ) {
			foreach ( $chunk_results as $key => $subscription_meta ) {
				$bp_background_updater->push_to_queue(
					array(
						'callback' => 'bb_migrate_users_forum_topic_subscriptions',
						'args'     => array( $subscription_meta ),
					)
				);
				$bp_background_updater->save()->schedule_event();
			}
		}
	} else {
		bb_migrate_users_forum_topic_subscriptions( $results );
	}
}

/**
 * Migration for forums and topics.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param array $subscription_meta Array of user subscriptions.
 *
 * @return void
 */
function bb_migrate_users_forum_topic_subscriptions( $subscription_meta ) {
	global $wpdb;

	$subscription_tbl = BP_Subscription::get_subscription_tbl();
	$forum_key        = $wpdb->prefix . '_bbp_forum_subscriptions';
	$topic_key        = $wpdb->prefix . '_bbp_subscriptions';
	$forum_post_type  = function_exists( 'bbp_get_forum_post_type' ) ? bbp_get_forum_post_type() : apply_filters( 'bbp_forum_post_type', 'forum' );
	$topic_post_type  = function_exists( 'bbp_get_topic_post_type' ) ? bbp_get_topic_post_type() : apply_filters( 'bbp_topic_post_type', 'topic' );

	if ( ! empty( $subscription_meta ) ) {
		foreach ( $subscription_meta as $user_data ) {

			$place_holder_queries = array();
			$insert_query         = "INSERT INTO {$subscription_tbl} ( user_id, type, item_id, secondary_item_id, date_recorded ) VALUES";

			$subscription_type = '';
			if ( $forum_key === $user_data->meta_key ) {
				$subscription_type = 'forum';
			} elseif ( $topic_key === $user_data->meta_key ) {
				$subscription_type = 'topic';
			}

			// Get all subscribe IDs.
			$meta_value = wp_parse_id_list( $user_data->meta_value );
			if ( ! empty( $meta_value ) ) {
				foreach ( $meta_value as $forum_topic_id ) {
					// Get the forum.
					$forum_topic = get_post( $forum_topic_id );

					if ( ! in_array( $forum_topic->post_type, array( $forum_post_type, $topic_post_type ), true ) ) {
						continue;
					}

					$record_args = array(
						'user_id'           => (int) $user_data->ID,
						'item_id'           => (int) $forum_topic_id,
						'secondary_item_id' => (int) $forum_topic->post_parent,
						'type'              => $subscription_type,
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
