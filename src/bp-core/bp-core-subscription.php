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
 * Function to get forum post type.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return string
 */
function bb_get_subscription_forum_post_type() {
	return function_exists( 'bbp_get_forum_post_type' ) ? bbp_get_forum_post_type() : apply_filters( 'bbp_forum_post_type', 'forum' );
}

/**
 * Function to get topic post type.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return string
 */
function bb_get_subscription_topic_post_type() {
	return function_exists( 'bbp_get_topic_post_type' ) ? bbp_get_topic_post_type() : apply_filters( 'bbp_topic_post_type', 'topic' );
}

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
	$forum_post_type  = bb_get_subscription_forum_post_type();
	$topic_post_type  = bb_get_subscription_topic_post_type();

	if ( ! empty( $subscription_meta ) ) {
		foreach ( $subscription_meta as $user_data ) {

			$place_holder_queries = array();
			$insert_query         = "INSERT INTO {$subscription_tbl} ( user_id, type, item_id, secondary_item_id, date_recorded ) VALUES";

			// Get all subscribe IDs.
			$meta_value = wp_parse_id_list( $user_data->meta_value );
			if ( ! empty( $meta_value ) ) {
				foreach ( $meta_value as $forum_topic_id ) {
					// Get the forum.
					$forum_topic = get_post( $forum_topic_id );

					if ( ! in_array( $forum_topic->post_type, array( $forum_post_type, $topic_post_type ), true ) ) {
						continue;
					}

					// Retrieve the subscription type using the post type.
					$subscription_type = bb_get_subscription_type_by_post_type( $forum_topic->post_type );

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
 * Function to get types of subscription.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return array
 */
function bb_get_subscription_types() {
	return array(
		bb_get_subscription_forum_post_type() => 'forum',
		bb_get_subscription_topic_post_type() => 'topic',
	);
}

/**
 * Function to get type of subscription for particular post type.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $post_type Post type.
 *
 * @return string
 */
function bb_get_subscription_type_by_post_type( $post_type ) {
	$subscription_types = bb_get_subscription_types();

	if ( ! empty( $subscription_types ) && isset( $subscription_types[ $post_type ] ) ) {
		return $subscription_types[ $post_type ];
	}

	return false;
}
